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

test('treats unknown extensions as CSV', function () {
    $path = importTempFile("Name,Category\nArabica,Beverages\n", 'txt');

    $parsed = app(ImportParser::class)->parse($path, 'products.txt');

    expect($parsed['headers'])->toBe(['Name', 'Category']);
});

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
