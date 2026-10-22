<?php

declare(strict_types=1);

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Tests\Feature\ImportEngine\Fixtures\ProductImportDefinitionFixture;

if (! function_exists('registerFixtureImportDefinition')) {
    /** Register the engine fixture definition in the imports registry. */
    function registerFixtureImportDefinition(): void
    {
        config()->set('imports.definitions', [
            ProductImportDefinitionFixture::KEY => ProductImportDefinitionFixture::class,
        ]);
    }
}

if (! function_exists('importTempFile')) {
    /** Write content to a temp file with the given extension and return its physical path. */
    function importTempFile(string $content, string $extension = 'csv'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sinop_import_test_').'.'.$extension;
        file_put_contents($path, $content);

        return $path;
    }
}

if (! function_exists('importFixtureCsv')) {
    /**
     * Write a temp CSV file with the fixture columns (Name, Category)
     * for the given [name, category] pairs and return its physical path.
     *
     * @param  array<int, array{0: string, 1: string}>  $pairs
     */
    function importFixtureCsv(array $pairs): string
    {
        $lines = ['Name,Category'];

        foreach ($pairs as [$name, $category]) {
            $lines[] = sprintf('"%s","%s"', $name, $category);
        }

        return importTempFile(implode("\n", $lines));
    }
}

if (! function_exists('importTempXlsx')) {
    /**
     * Build an XLSX file from an array of sheets (each an array of
     * rows) and return its physical path.
     *
     * @param  array<int, array<int, array<int, mixed>>>  $sheets
     */
    function importTempXlsx(array $sheets): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sinop_import_test_').'.xlsx';
        $writer = new XlsxWriter;
        $writer->openToFile($path);

        $first = true;

        foreach ($sheets as $rows) {
            if (! $first) {
                $writer->addNewSheetAndMakeItCurrent();
            }

            $first = false;

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }
        }

        $writer->close();

        return $path;
    }
}
