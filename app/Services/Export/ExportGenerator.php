<?php

declare(strict_types=1);

namespace App\Services\Export;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use League\Csv\Writer as CsvWriter;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * ExportGenerator — writes an export file to disk from a definition,
 * a snapshot SQL string and its bindings.
 *
 * The snapshot keeps the queued path and the inline path on exactly
 * the same code: ExportService compiles the scoped/filtered query once
 * (`toBase()` applies global scopes), passes SQL + bindings, and the
 * generator pages with `limit`/`offset` ints appended to the snapshot —
 * never hydrating a model, so enum casts and other mutators can never
 * rewrite a cell.
 *
 * CSV cells are written as the database's own text with formula
 * injection escaped; XLSX cells are written typed, so numbers stay
 * numbers in a spreadsheet, and text is forced into string cells so a
 * leading "=" can never become a live formula.
 */
class ExportGenerator
{
    /**
     * Rows fetched per database round-trip while writing the file.
     */
    public const PAGE_SIZE = 500;

    /**
     * Write the export file for the given snapshot.
     *
     * @param  array<int, mixed>  $bindings
     * @return int number of data rows written (excluding the header)
     *
     * @throws InvalidArgumentException when the format is not supported
     */
    public function generate(ExportDefinition $definition, string $sql, array $bindings, string $format, string $physicalPath): int
    {
        $format = strtolower($format);

        if (! in_array($format, ['csv', 'xlsx'], true)) {
            throw new InvalidArgumentException(__('exports.errors.unsupported_format'));
        }

        $columns = $definition->columns();
        $headers = array_map(static fn (ExportColumn $column): string => $column->label(), $columns);

        $writer = null;

        if ($format === 'xlsx') {
            $writer = new XlsxWriter;
            $writer->openToFile($physicalPath);

            // Force string cells for text: OpenSpout's Cell::fromValue()
            // turns any leading "=" into a live FormulaCell (CWE-1236),
            // and user-supplied text — notes, names, reasons — reaches
            // these cells directly. A StringCell is written as text and
            // never evaluated by a spreadsheet.
            $append = static fn (array $cells): mixed => $writer->addRow(new Row(array_map(
                static fn (mixed $cell): Cell => is_string($cell) && $cell !== ''
                    ? new StringCell($cell, null)
                    : Cell::fromValue($cell),
                $cells,
            )));
        } else {
            $csv = CsvWriter::createFromPath($physicalPath, 'w+');
            $csv->setNewline("\n");

            $append = static fn (array $cells): mixed => $csv->insertOne($cells);
        }

        $append($headers);

        $rows = 0;
        $offset = 0;

        while (true) {
            $page = $this->page($sql, $bindings, $offset);

            if ($page === []) {
                break;
            }

            foreach ($page as $row) {
                $append($this->cells($row, $columns, $format));
                $rows++;
            }

            if (count($page) < self::PAGE_SIZE) {
                break;
            }

            $offset += self::PAGE_SIZE;
        }

        if ($format === 'xlsx') {
            $writer->close();
        }

        return $rows;
    }

    /**
     * Fetch one page of raw rows from the snapshot SQL.
     *
     * @param  array<int, mixed>  $bindings
     * @return array<int, object>
     */
    protected function page(string $sql, array $bindings, int $offset): array
    {
        return DB::select(
            $sql.' limit '.self::PAGE_SIZE.' offset '.$offset,
            $bindings
        );
    }

    /**
     * @param  array<int, ExportColumn>  $columns
     * @return array<int, string|int|float>
     */
    protected function cells(object $row, array $columns, string $format): array
    {
        $cells = [];

        foreach ($columns as $column) {
            $cells[] = $this->format($row->{$column->key} ?? null, $column->cast, $format);
        }

        return $cells;
    }

    /**
     * Cast one cell for the target format.
     *
     * CSV keeps the database's own decimal text so a `DECIMAL(15,4)` money
     * value survives the round trip intact — casting through a float first
     * would drop trailing zeros (`1234.5000` → `1234.5`) and round anything
     * past PHP's default precision. XLSX gets real typed values instead, so
     * quantities and prices are numbers a spreadsheet can sum rather than
     * quoted text.
     */
    protected function format(mixed $value, string $cast, string $format): string|int|float
    {
        if ($value === null) {
            return '';
        }

        $typed = match ($cast) {
            ExportColumn::CAST_INT => $format === 'csv' ? (string) $value : (int) $value,
            ExportColumn::CAST_FLOAT => $format === 'csv' ? (string) $value : (float) $value,
            ExportColumn::CAST_DATETIME => $this->dateTime($value),
            default => (string) $value,
        };

        if ($format === 'csv' && is_string($typed)) {
            return $this->escapeFormula($typed);
        }

        return $typed;
    }

    /**
     * Normalise a date or datetime to `Y-m-d H:i:s` whatever the driver
     * returned, so the same value reads the same on MySQL and SQLite.
     */
    protected function dateTime(mixed $value): string
    {
        $timestamp = strtotime((string) $value);

        return $timestamp === false ? (string) $value : date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Neutralise CSV formula injection (CWE-1236). A cell beginning with
     * `=`, `+`, `-`, `@`, TAB or CR is executed as a formula when the file
     * is opened in a spreadsheet, and user-supplied text — product names,
     * SKUs, reference codes, notes — reaches these cells directly.
     *
     * Numeric text is left untouched so negative numbers and signed
     * quantities are not corrupted. XLSX is guarded in the writer
     * instead: text goes into explicit string cells, which a
     * spreadsheet never evaluates.
     */
    protected function escapeFormula(string $value): string
    {
        if (preg_match('/\A[=+\-@\t\r]/', $value) === 1 && ! is_numeric($value)) {
            return "'".$value;
        }

        return $value;
    }
}
