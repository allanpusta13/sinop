<?php

declare(strict_types=1);

namespace App\Services\Import;

use DateInterval;
use DateTimeInterface;
use League\Csv\Info;
use League\Csv\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;
use Throwable;

/**
 * ImportParser — parses CSV and XLSX import files into normalized
 * headers and 1-based row records for the whole import suite.
 *
 * Conventions (shared with the proven product importer):
 * - The first non-empty row is always the header row.
 * - Fully blank rows are skipped.
 * - Rows whose first non-empty cell starts with "#" are comment rows
 *   (used by templates for their field-description block) and are
 *   skipped as well.
 * - Delimiters are auto-detected for CSV; only the first sheet of an
 *   XLSX file is read.
 * - `headers()` shares the same parsers but stops right after the
 *   header row, for the mapping step's per-render reads.
 */
class ImportParser
{
    public const COMMENT_MARKER = '#';

    /**
     * Parse a CSV or XLSX file and return headers and 1-based row records.
     *
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public function parse(string $filePath, ?string $originalName = null): array
    {
        return $this->parseContent($filePath, $originalName, headersOnly: false);
    }

    /**
     * Read only the header row, stopping before any data row is
     * consumed. The mapping step renders on every Livewire update, so
     * it must not re-parse a 10k-row file per keystroke — the headers
     * are all it ever uses (for the select options and the guesses).
     *
     * @return array<int, string>
     */
    public function headers(string $filePath, ?string $originalName = null): array
    {
        return $this->parseContent($filePath, $originalName, headersOnly: true)['headers'];
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    protected function parseContent(string $filePath, ?string $originalName, bool $headersOnly): array
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException(__('imports.errors.unreadable_file'));
        }

        $extension = strtolower(pathinfo($originalName ?? $filePath, PATHINFO_EXTENSION));

        $result = $extension === 'xlsx'
            ? $this->parseXlsx($filePath, $headersOnly)
            : $this->parseCsv($filePath, $headersOnly);

        $result['headers'] = $this->deduplicateLabels($result['headers']);

        return $result;
    }

    /**
     * Two columns may legitimately share a label ("SKU" twice). The
     * mapping step keys its options by label and the engine resolves a
     * mapped label back to its column, so a duplicate would silently
     * collapse into the first column. Later copies get a numeric
     * suffix instead; single occurrences stay untouched.
     *
     * @param  array<int, string>  $headers
     * @return array<int, string>
     */
    protected function deduplicateLabels(array $headers): array
    {
        $seen = [];

        foreach ($headers as $index => $label) {
            $seen[$label] = ($seen[$label] ?? 0) + 1;

            if ($seen[$label] === 1) {
                continue;
            }

            $candidate = $label.' ('.$seen[$label].')';

            while (isset($seen[$candidate])) {
                $seen[$label]++;
                $candidate = $label.' ('.$seen[$label].')';
            }

            $seen[$candidate] = 1;
            $headers[$index] = $candidate;
        }

        return $headers;
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    protected function parseCsv(string $filePath, bool $headersOnly = false): array
    {
        try {
            $csv = CsvReader::createFromPath($filePath, 'r');

            $delimiters = [',', ';', "\t", '|'];

            try {
                $stats = Info::getDelimiterStats($csv, $delimiters, 10);
                arsort($stats);
                $delimiter = key($stats) ?: ',';
                $csv->setDelimiter($delimiter);
            } catch (Throwable) {
                $csv->setDelimiter(',');
            }
        } catch (Throwable $exception) {
            throw new RuntimeException(__('imports.errors.unreadable_file'), 0, $exception);
        }

        $headers = [];
        $rows = [];
        $rowNumber = 0;

        foreach ($csv->getRecords() as $record) {
            $trimmed = array_map(function ($val) {
                if (is_string($val)) {
                    $val = preg_replace('/^\xEF\xBB\xBF/', '', $val);

                    return trim($val);
                }

                return $val;
            }, $record);

            $nonEmpty = array_filter($trimmed, fn ($v) => $v !== null && $v !== '');

            if (empty($nonEmpty)) {
                continue;
            }

            if ($this->isCommentRecord($trimmed)) {
                continue;
            }

            if (empty($headers)) {
                $headers = array_map(fn ($val): string => (string) $val, $trimmed);

                if ($headersOnly) {
                    break;
                }

                continue;
            }

            $rowNumber++;
            $rows[$rowNumber] = $trimmed;
        }

        if (empty($headers)) {
            throw new RuntimeException(__('imports.errors.empty_file'));
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    protected function parseXlsx(string $filePath, bool $headersOnly = false): array
    {
        $reader = new XlsxReader;

        try {
            $reader->open($filePath);
        } catch (Throwable $exception) {
            // The file exists and was uploaded — failing to open it is
            // corrupt content, not a missing file.
            throw new RuntimeException(__('imports.errors.corrupt_file'), 0, $exception);
        }

        $headers = [];
        $rows = [];
        $rowNumber = 0;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $trimmed = array_map(function ($val) {
                        if (is_string($val)) {
                            return trim($val);
                        }

                        if ($val instanceof DateTimeInterface) {
                            return $val->format('Y-m-d H:i:s');
                        }

                        if ($val instanceof DateInterval) {
                            return $val->format('%R%y-%m-%d');
                        }

                        return $val;
                    }, $row->toArray());

                    $nonEmpty = array_filter($trimmed, fn ($v) => $v !== null && $v !== '');

                    if (empty($nonEmpty)) {
                        continue;
                    }

                    if ($this->isCommentRecord($trimmed)) {
                        continue;
                    }

                    if (empty($headers)) {
                        $headers = array_map(function ($val): string {
                            if ($val instanceof DateTimeInterface) {
                                return $val->format('Y-m-d H:i:s');
                            }

                            if ($val instanceof DateInterval) {
                                return $val->format('%R%y-%m-%d');
                            }

                            return (string) ($val ?? '');
                        }, $trimmed);

                        if ($headersOnly) {
                            break 2;
                        }

                        continue;
                    }

                    $rowNumber++;
                    $rows[$rowNumber] = $trimmed;
                }

                break;
            }
        } catch (Throwable $exception) {
            // A zip that opens but falls apart mid-read (truncated
            // shared strings, broken sheet XML) is corrupt content too.
            throw new RuntimeException(__('imports.errors.corrupt_file'), 0, $exception);
        } finally {
            $reader->close();
        }

        if (empty($headers)) {
            throw new RuntimeException(__('imports.errors.empty_file'));
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * A comment record starts with "#" in its first non-empty cell
     * (the template description-block convention).
     *
     * @param  array<int, mixed>  $record
     */
    protected function isCommentRecord(array $record): bool
    {
        foreach ($record as $value) {
            if ($value === null) {
                continue;
            }

            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            return str_starts_with($value, self::COMMENT_MARKER);
        }

        return false;
    }
}
