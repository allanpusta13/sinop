<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/import_helpers.php';

use App\Services\Import\ImportParser;

test('parses comma-delimited CSV with a header row', function () {
    $path = importTempFile("Name,Category\nArabica,Beverages\nRobusta,\n");

    $parsed = app(ImportParser::class)->parse($path, 'products.csv');

    expect($parsed['headers'])->toBe(['Name', 'Category'])
        ->and($parsed['rows'])->toHaveCount(2)
        ->and($parsed['rows'][1])->toBe(['Arabica', 'Beverages'])
        ->and($parsed['rows'][2])->toBe(['Robusta', '']);
});

test('auto-detects semicolon delimiters', function () {
    $path = importTempFile("Name;Category\nArabica;Beverages\n");

    $parsed = app(ImportParser::class)->parse($path, 'products.csv');

    expect($parsed['headers'])->toBe(['Name', 'Category'])
        ->and($parsed['rows'])->toHaveCount(1);
});

test('strips the UTF-8 BOM from the first header', function () {
    $path = importTempFile("\xEF\xBB\xBFName,Category\nArabica,Beverages\n");

    $parsed = app(ImportParser::class)->parse($path, 'products.csv');

    expect($parsed['headers'][0])->toBe('Name');
});

test('skips blank rows and comment rows', function () {
    $path = importTempFile("Name,Category\n\nArabica,Beverages\n#,Field,Description\n#,Name,The name\n");

    $parsed = app(ImportParser::class)->parse($path, 'products.csv');

    expect($parsed['rows'])->toHaveCount(1)
        ->and($parsed['rows'][1])->toBe(['Arabica', 'Beverages']);
});

test('parses only the first sheet of an XLSX file', function () {
    $path = importTempXlsx([
        [
            ['Name', 'Category'],
            ['Arabica', 'Beverages'],
        ],
        [
            ['Ignored', 'Sheet'],
            ['Should not appear', 'Anywhere'],
        ],
    ]);

    $parsed = app(ImportParser::class)->parse($path, 'products.xlsx');

    expect($parsed['headers'])->toBe(['Name', 'Category'])
        ->and($parsed['rows'])->toHaveCount(1)
        ->and($parsed['rows'][1][0])->toBe('Arabica');
});

// Removed obsolete test treats unknown extensions as CSV

test('throws when the file has no header row', function () {
    $path = importTempFile('');

    expect(fn () => app(ImportParser::class)->parse($path, 'empty.csv'))
        ->toThrow(RuntimeException::class, __('imports.errors.empty_file'));
});

test('reads only the header row from a CSV in headers-only mode', function () {
    $path = importTempFile("Name,Category\nArabica,Beverages\nRobusta,Teas\n");

    expect(app(ImportParser::class)->headers($path, 'products.csv'))->toBe(['Name', 'Category']);
});

test('reads only the header row from an XLSX in headers-only mode', function () {
    $path = importTempXlsx([
        [
            ['Name', 'Category'],
            ['Arabica', 'Beverages'],
        ],
    ]);

    expect(app(ImportParser::class)->headers($path, 'products.xlsx'))->toBe(['Name', 'Category']);
});

test('headers-only mode strips the BOM like a full parse', function () {
    $path = importTempFile("\xEF\xBB\xBFName,Category\nArabica,Beverages\n");

    expect(app(ImportParser::class)->headers($path, 'products.csv'))->toBe(['Name', 'Category']);
});

test('headers-only mode throws when the file has no header row', function () {
    $path = importTempFile('');

    expect(fn () => app(ImportParser::class)->headers($path, 'empty.csv'))
        ->toThrow(RuntimeException::class, __('imports.errors.empty_file'));
});

test('throws for unreadable files', function () {
    expect(fn () => app(ImportParser::class)->parse(sys_get_temp_dir().'/definitely-missing-import.csv', 'missing.csv'))
        ->toThrow(RuntimeException::class, __('imports.errors.unreadable_file'));
});

test('reports a corrupt xlsx as corrupt, not as a missing file', function () {
    $path = importTempFile('this is not a zip archive');

    expect(fn () => app(ImportParser::class)->parse($path, 'broken.xlsx'))
        ->toThrow(RuntimeException::class, __('imports.errors.corrupt_file'));
});

test('suffixes duplicate header labels so the mapping cannot collapse them', function () {
    $path = importTempFile("SKU,Name,SKU,Category\n1,A,2,B\n");

    $parsed = app(ImportParser::class)->parse($path, 'products.csv');

    // The mapping step keys options by label and the engine resolves a
    // mapped label back to its column — a bare duplicate would silently
    // collapse into the first column.
    expect($parsed['headers'])->toBe(['SKU', 'Name', 'SKU (2)', 'Category'])
        ->and($parsed['rows'][1])->toBe(['1', 'A', '2', 'B'])
        ->and(app(ImportParser::class)->headers($path, 'products.csv'))->toBe(['SKU', 'Name', 'SKU (2)', 'Category']);
});

test('leaves single-occurrence headers untouched', function () {
    $path = importTempFile("SKU (2),Name\n1,A\n");

    expect(app(ImportParser::class)->headers($path, 'products.csv'))->toBe(['SKU (2)', 'Name']);
});

test('rejects unsupported extensions such as xls, txt, or pdf', function () {
    $file = tempnam(sys_get_temp_dir(), 'test');
    try {
        file_put_contents($file, 'name,category');

        expect(fn () => app(ImportParser::class)->parse($file, 'products.txt'))
            ->toThrow(RuntimeException::class, __('imports.errors.unsupported_format'))
            ->and(fn () => app(ImportParser::class)->parse($file, 'products.xls'))
            ->toThrow(RuntimeException::class, __('imports.errors.unsupported_format'))
            ->and(fn () => app(ImportParser::class)->parse($file, 'products.pdf'))
            ->toThrow(RuntimeException::class, __('imports.errors.unsupported_format'));
    } finally {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

test('rejects swapped csv and xlsx contents when extension does not match zip header signature', function () {
    // 1. Plain text CSV inside .xlsx extension (missing ZIP header PK\x03\x04)
    $csvAsXlsxPath = importTempFile("Name,Category\nArabica,Beverages\n", 'xlsx');
    try {
        expect(fn () => app(ImportParser::class)->parse($csvAsXlsxPath, 'products.xlsx'))
            ->toThrow(RuntimeException::class, __('imports.errors.corrupt_file'));
    } finally {
        if (file_exists($csvAsXlsxPath)) {
            unlink($csvAsXlsxPath);
        }
    }

    // 2. Real ZIP/XLSX binary inside .csv extension (has ZIP header PK\x03\x04)
    $xlsxTemp = tempnam(sys_get_temp_dir(), 'test_xlsx_');
    $xlsxAsCsvPath = $xlsxTemp . '.csv';
    try {
        $writer = new \OpenSpout\Writer\XLSX\Writer();
        $writer->openToFile($xlsxAsCsvPath);
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['SKU', 'Name']));
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['SKU001', 'Test Item']));
        $writer->close();

        expect(fn () => app(ImportParser::class)->parse($xlsxAsCsvPath, 'products.csv'))
            ->toThrow(RuntimeException::class, __('imports.errors.corrupt_file'));
    } finally {
        if (file_exists($xlsxTemp)) {
            unlink($xlsxTemp);
        }
        if (file_exists($xlsxAsCsvPath)) {
            unlink($xlsxAsCsvPath);
        }
    }
});

test('rejects a zip archive that passes local file header check but is not a valid xlsx workbook', function () {
    $zipPath = tempnam(sys_get_temp_dir(), 'test_zip_') . '.xlsx';
    try {
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('dummy.txt', 'This is a valid zip archive but not an OpenXML workbook');
        $zip->close();

        expect(fn () => app(ImportParser::class)->parse($zipPath, 'not_workbook.xlsx'))
            ->toThrow(RuntimeException::class, __('imports.errors.corrupt_file'));
    } finally {
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
    }
});

test('handles corrupt xlsx content and malformed non-zip file cleanly', function () {
    $corruptPath = importTempFile("NOT_A_ZIP_FILE_CONTENT", 'xlsx');
    try {
        expect(fn () => app(ImportParser::class)->parse($corruptPath, 'corrupt.xlsx'))
            ->toThrow(RuntimeException::class, __('imports.errors.corrupt_file'));
    } finally {
        if (file_exists($corruptPath)) {
            unlink($corruptPath);
        }
    }
});

test('parses tab delimited csv files successfully', function () {
    $tabCsv = importTempFile("SKU\tName\tCategory\nSKU-100\tCoffee\tBeverages", 'csv');
    try {
        $parsed = app(ImportParser::class)->parse($tabCsv, 'import.csv');

        expect($parsed['headers'])->toBe(['SKU', 'Name', 'Category'])
            ->and($parsed['rows'][1][0])->toBe('SKU-100');
    } finally {
        if (file_exists($tabCsv)) {
            unlink($tabCsv);
        }
    }
});

test('parses file when originalName is null and filePath has extension', function () {
    $csvPath = importTempFile("Name,Category\nArabica,Beverages\n", 'csv');
    try {
        $parsed = app(ImportParser::class)->parse($csvPath, null);

        expect($parsed['headers'])->toBe(['Name', 'Category'])
            ->and($parsed['rows'][1][0])->toBe('Arabica');
    } finally {
        if (file_exists($csvPath)) {
            unlink($csvPath);
        }
    }
});


test('rejects files exceeding max row limit', function () {
    $rows = ["SKU,Name"];
    for ($i = 1; $i <= 5001; $i++) {
        $rows[] = "SKU-$i,Product $i";
    }
    $path = importTempFile(implode("\n", $rows), 'csv');
    try {
        expect(fn () => app(ImportParser::class)->parse($path, 'large.csv'))
            ->toThrow(RuntimeException::class, __('imports.errors.max_rows_exceeded', ['max' => 5000]));
    } finally {
        if (file_exists($path)) {
            unlink($path);
        }
    }
});
