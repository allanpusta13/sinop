<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/import_helpers.php';

use App\Services\Import\ImportParser;
use App\Services\Import\ImportTemplateGenerator;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Tests\Feature\ImportEngine\Fixtures\ProductImportDefinitionFixture;

beforeEach(function () {
    registerFixtureImportDefinition();
    actingAsAdmin();
});

test('streams a CSV template with headers, a marked sample row and a comment description block', function () {
    $response = $this->get(route('imports.template', [
        'import' => ProductImportDefinitionFixture::KEY,
        'format' => 'csv',
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    $lines = explode("\n", trim($content));

    expect($lines[0])->toBe('"Family Name","Family Category (optional)"')
        ->and($lines[1])->toContain('(Sample)')
        ->and($content)->toContain('#,Field,Description')
        ->and($content)->toContain('#,"Family Name"');
});

test('streams an XLSX template whose first sheet holds headers and the sample row', function () {
    $response = $this->get(route('imports.template', [
        'import' => ProductImportDefinitionFixture::KEY,
        'format' => 'xlsx',
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $tmp = importTempFile($response->streamedContent(), 'xlsx');

    $reader = new XlsxReader;
    $reader->open($tmp);

    $firstSheetRows = [];

    foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
        if ($sheetIndex !== 1) {
            break;
        }

        foreach ($sheet->getRowIterator() as $row) {
            $firstSheetRows[] = array_map(
                static fn ($cell) => $cell->getValue(),
                $row->getCells(),
            );
        }
    }

    $reader->close();

    expect($firstSheetRows[0])->toBe(['Family Name', 'Family Category (optional)'])
        ->and(implode(' ', $firstSheetRows[1]))->toContain('(Sample)');
});

test('parses a freshly downloaded CSV template back as headers only', function () {
    $response = $this->get(route('imports.template', [
        'import' => ProductImportDefinitionFixture::KEY,
        'format' => 'csv',
    ]));

    $parsed = app(ImportParser::class)->parse(importTempFile($response->streamedContent(), 'csv'), 'template.csv');

    expect($parsed['headers'])->toBe(['Family Name', 'Family Category (optional)'])
        ->and($parsed['rows'])->toBe([]);
});

test('parses a freshly downloaded XLSX template back as headers only', function () {
    $response = $this->get(route('imports.template', [
        'import' => ProductImportDefinitionFixture::KEY,
        'format' => 'xlsx',
    ]));

    $parsed = app(ImportParser::class)->parse(importTempFile($response->streamedContent(), 'xlsx'), 'template.xlsx');

    expect($parsed['headers'])->toBe(['Family Name', 'Family Category (optional)'])
        ->and($parsed['rows'])->toBe([]);
});

test('holds the XLSX field descriptions on a sheet of their own', function () {
    $response = $this->get(route('imports.template', [
        'import' => ProductImportDefinitionFixture::KEY,
        'format' => 'xlsx',
    ]));

    $tmp = importTempFile($response->streamedContent(), 'xlsx');

    $reader = new XlsxReader;
    $reader->open($tmp);

    $sheets = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        $rows = [];

        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = array_map(
                static fn ($cell) => $cell->getValue(),
                $row->getCells(),
            );
        }

        $sheets[$sheet->getName()] = $rows;
    }

    $reader->close();

    $names = array_keys($sheets);

    expect($names)->toHaveCount(2)
        ->and($names[0])->not->toBe(__('imports.template.descriptions_sheet'))
        ->and($sheets[$names[0]])->toHaveCount(2)
        ->and($names[1])->toBe(__('imports.template.descriptions_sheet'))
        ->and(implode(' ', $sheets[$names[1]][1]))->toContain('Family Name');
});

test('rejects unsupported template formats', function () {
    expect(fn () => app(ImportTemplateGenerator::class)->streamDownload(new ProductImportDefinitionFixture, 'pdf'))
        ->toThrow(InvalidArgumentException::class, __('imports.errors.unsupported_format'));
});
