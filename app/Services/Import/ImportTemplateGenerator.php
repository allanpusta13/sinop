<?php

declare(strict_types=1);

namespace App\Services\Import;

use InvalidArgumentException;
use League\Csv\Writer as CsvWriter;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use SplTempFileObject;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ImportTemplateGenerator — produces downloadable CSV and XLSX import
 * templates for any definition: localized headers, one clearly marked
 * sample row, and short field descriptions.
 *
 * The CSV description block sits after a blank row; every row of it
 * starts with "#" in its first cell so the ImportParser treats it as a
 * comment block when the template is uploaded back. XLSX templates put
 * descriptions on a second sheet, which the parser never reads. The
 * sample row carries the "#" marker in its first cell in both formats,
 * so a freshly downloaded template parsed back yields headers only.
 */
class ImportTemplateGenerator
{
    public function streamDownload(ImportDefinition $definition, string $format): StreamedResponse
    {
        $format = strtolower($format);

        if (! in_array($format, ['csv', 'xlsx'], true)) {
            throw new InvalidArgumentException(__('imports.errors.unsupported_format'));
        }

        [$headers, $sample, $descriptions] = $this->templateData($definition);

        $filename = 'sinop_import_'.str_replace('.', '_', $definition->key())."_template.{$format}";

        if ($format === 'xlsx') {
            return $this->xlsxResponse($headers, $sample, $descriptions, $filename);
        }

        return $this->csvResponse($headers, $sample, $descriptions, $filename);
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>, 2: array<int, array{0: string, 1: string}>}
     */
    protected function templateData(ImportDefinition $definition): array
    {
        $headers = [];
        $sample = [];
        $descriptions = [];

        foreach ($definition->columns() as $column) {
            $headers[] = $column->label();
            $sample[] = $column->sample;
            $descriptions[] = [$column->label(), $column->description()];
        }

        return [$headers, $sample, $descriptions];
    }

    /**
     * Prefix the sample row with the comment marker so uploading a freshly
     * downloaded template feeds headers only into the wizard instead of one
     * bogus data row. The marker lands in the first cell even when that
     * sample is empty, because ImportParser reads the first non-empty cell.
     *
     * @param  array<int, string>  $sample
     * @return array<int, string>
     */
    protected function markSampleRow(array $sample): array
    {
        $sample[0] = ImportParser::COMMENT_MARKER.((string) ($sample[0] ?? '') !== '' ? ' '.$sample[0] : '');

        return $sample;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string>  $sample
     * @param  array<int, array{0: string, 1: string}>  $descriptions
     */
    protected function csvResponse(array $headers, array $sample, array $descriptions, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $sample, $descriptions) {
            $csv = CsvWriter::createFromFileObject(new SplTempFileObject);
            $csv->setNewline("\n");
            $csv->insertOne($headers);
            $csv->insertOne($this->markSampleRow($sample));
            $csv->insertOne([]);
            $csv->insertOne([ImportParser::COMMENT_MARKER, __('imports.template.field'), __('imports.template.description')]);

            foreach ($descriptions as [$label, $description]) {
                $csv->insertOne([ImportParser::COMMENT_MARKER, $label, $description]);
            }

            $csv->output();
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string>  $sample
     * @param  array<int, array{0: string, 1: string}>  $descriptions
     */
    protected function xlsxResponse(array $headers, array $sample, array $descriptions, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $sample, $descriptions) {
            $tempPath = tempnam(sys_get_temp_dir(), 'sinop_tpl_');
            $writer = new XlsxWriter;
            $writer->openToFile($tempPath);

            // An aborted download kills the script at the first output
            // flush — inside readfile() below — before @unlink runs, so
            // the cleanup is also registered as a shutdown fallback.
            // The writer is already closed by then, which is what makes
            // the unlink succeed on Windows.
            register_shutdown_function(static function () use ($tempPath): void {
                if (is_string($tempPath) && is_file($tempPath)) {
                    @unlink($tempPath);
                }
            });

            $writer->addRow(Row::fromValues($headers));
            $writer->addRow(Row::fromValues($this->markSampleRow($sample)));

            $writer->addNewSheetAndMakeItCurrent();
            $writer->getCurrentSheet()->setName(__('imports.template.descriptions_sheet'));
            $writer->addRow(Row::fromValues([
                __('imports.template.field'),
                __('imports.template.description'),
            ], (new Style)->setFontBold()));

            foreach ($descriptions as [$label, $description]) {
                $writer->addRow(Row::fromValues([$label, $description]));
            }

            $writer->close();

            readfile($tempPath);
            @unlink($tempPath);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
