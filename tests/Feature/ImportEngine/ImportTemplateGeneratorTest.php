<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/import_helpers.php';

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

test('rejects unsupported template formats', function () {
    expect(fn () => app(ImportTemplateGenerator::class)->streamDownload(new ProductImportDefinitionFixture, 'pdf'))
        ->toThrow(InvalidArgumentException::class, __('imports.errors.unsupported_format'));
});
