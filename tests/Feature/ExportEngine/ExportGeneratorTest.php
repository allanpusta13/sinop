<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/export_helpers.php';

use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Export\ExportFileStore;
use App\Services\Export\ExportGenerator;
use App\Services\Export\ExportService;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

beforeEach(function () {
    Storage::fake('local');
    actingAsAdmin();
});

/**
 * @return array<int, array<int, string>>
 */
function exportedCsvRecords(string $filename): array
{
    $path = Storage::disk('local')->path('exports/'.auth()->id().'/'.$filename);

    return array_values(iterator_to_array(CsvReader::createFromPath($path, 'r')->getRecords(), false));
}

test('writes a csv with a localized header row', function () {
    Warehouse::factory()->create(['code' => 'MAIN', 'name' => 'Main', 'location' => 'HQ']);

    $result = app(ExportService::class)->export(Warehouse::query(), 'warehouses', 'csv', auth()->user());

    $records = exportedCsvRecords($result['filename']);

    expect($records[0])->toBe([
        __('resources.warehouses.table.code'),
        __('resources.warehouses.table.name'),
        __('resources.warehouses.table.location'),
        __('resources.warehouses.table.status'),
        __('resources.warehouses.table.staff'),
        __('resources.warehouses.table.ledger_entries'),
    ])->and($records[1][0])->toBe('MAIN')
        ->and($records[1][1])->toBe('Main')
        ->and($records[1][2])->toBe('HQ');
});

test('writes an xlsx workbook with the same headers', function () {
    Warehouse::factory()->create(['code' => 'MAIN']);

    $result = app(ExportService::class)->export(Warehouse::query(), 'warehouses', 'xlsx', auth()->user());

    $path = Storage::disk('local')->path('exports/'.auth()->id().'/'.$result['filename']);

    $reader = new XlsxReader;
    $reader->open($path);

    $rows = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
    }

    $reader->close();

    expect($rows[0][0])->toBe(__('resources.warehouses.table.code'))
        ->and($rows[1][0])->toBe('MAIN')
        ->and($result['rows'])->toBe(1);
});

test('rejects an unsupported format', function () {
    expect(fn () => app(ExportService::class)->export(Warehouse::query(), 'warehouses', 'pdf', auth()->user()))
        ->toThrow(InvalidArgumentException::class, __('exports.errors.unsupported_format'));
});

test('writes an empty cell for a null value', function () {
    $supplier = Supplier::factory()->create(['email' => null, 'contact_person' => null]);

    $result = app(ExportService::class)->export(Supplier::query(), 'suppliers', 'csv', auth()->user());

    $records = exportedCsvRecords($result['filename']);
    $emailIndex = array_search(__('resources.suppliers.table.email'), $records[0], true);
    $contactIndex = array_search(__('resources.suppliers.table.contact'), $records[0], true);

    expect($emailIndex)->not->toBeFalse()
        ->and($contactIndex)->not->toBeFalse()
        ->and($records[1][$emailIndex])->toBe('')
        ->and($records[1][$contactIndex])->toBe('')
        ->and($records)->toHaveCount(2)
        ->and(Supplier::query()->count())->toBe(1)
        ->and($supplier->email)->toBeNull();
});

test('pages past the generator page size', function () {
    $user = auth()->user();
    Warehouse::factory()->count(ExportGenerator::PAGE_SIZE + 5)->create();

    $service = app(ExportService::class);
    $definition = $service->resolveDefinition('warehouses');
    [$sql, $bindings] = $service->snapshot($definition, $definition->query(Warehouse::query()));

    $fileStore = app(ExportFileStore::class);
    $fileStore->ensureUserDirectory((int) $user->getKey());
    $filename = $fileStore->allocateFilename((int) $user->getKey(), 'warehouses', 'csv');

    $rows = app(ExportGenerator::class)->generate(
        $definition,
        $sql,
        $bindings,
        'csv',
        $fileStore->absolutePath((int) $user->getKey(), $filename),
    );

    expect($rows)->toBe(ExportGenerator::PAGE_SIZE + 5);

    $records = exportedCsvRecords($filename);

    expect($records)->toHaveCount(ExportGenerator::PAGE_SIZE + 6);

    $codes = array_slice(array_column($records, 0), 1);

    expect(array_unique($codes))->toHaveCount(ExportGenerator::PAGE_SIZE + 5);
});

/**
 * @return array<int, array<int, mixed>>
 */
function exportedXlsxRows(string $filename): array
{
    $path = Storage::disk('local')->path('exports/'.auth()->id().'/'.$filename);

    $reader = new XlsxReader;
    $reader->open($path);

    $rows = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
    }

    $reader->close();

    return $rows;
}

test('escapes csv formula injection in user supplied text', function () {
    ProductVariant::factory()->create(['sku' => "=cmd|'/C calc'!A0"]);

    $result = app(ExportService::class)->export(
        ProductVariant::query(),
        'products.variants',
        'csv',
        auth()->user(),
    );

    $records = exportedCsvRecords($result['filename']);
    $skuIndex = array_search(__('resources.products.table.sku'), $records[0], true);

    expect($skuIndex)->not->toBeFalse()
        ->and($records[1][$skuIndex])->toBe("'=cmd|'/C calc'!A0");
});

test('leaves a negative number alone when escaping formulas', function () {
    ProductVariant::factory()->create(['reorder_point' => -5]);

    $result = app(ExportService::class)->export(
        ProductVariant::query(),
        'products.variants',
        'csv',
        auth()->user(),
    );

    $records = exportedCsvRecords($result['filename']);
    $index = array_search(__('resources.products.table.reorder_point'), $records[0], true);

    expect($index)->not->toBeFalse()
        ->and($records[1][$index])->toBe('-5');
});

test('writes money as a real number in xlsx so a spreadsheet can sum it', function () {
    ProductVariantPrice::factory()->create(['cost_price' => 1234.5, 'sale_price' => 9999.99]);

    $result = app(ExportService::class)->export(
        ProductVariantPrice::query(),
        'products.current_prices',
        'xlsx',
        auth()->user(),
    );

    $rows = exportedXlsxRows($result['filename']);
    $costIndex = array_search(__('resources.products.fields.cost_price'), $rows[0], true);

    expect($costIndex)->not->toBeFalse()
        ->and($rows[1][$costIndex])->toBeFloat()
        ->and($rows[1][$costIndex])->toEqualWithDelta(1234.5, 0.0001);
});

test('xlsx formula injection protects formula strings and keeps numeric types', function () {
    ProductVariant::query()->delete();

    ProductVariant::factory()->create([
        'sku' => '=HYPERLINK("http://evil.com", "Click")',
        'name' => '=SUM(1, 2)',
        'barcode' => '+12345678999',
        'base_unit_name' => '-UNIT-A',
        'reorder_point' => 15,
    ]);

    $result = app(ExportService::class)->export(
        ProductVariant::query(),
        'products.variants',
        'xlsx',
        auth()->user(),
    );

    $path = Storage::disk('local')->path('exports/'.auth()->id().'/'.$result['filename']);

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    expect($sheetXml)->not->toBeFalse();

    // Verify structurally that string cells are saved as t="inlineStr"
    expect($sheetXml)->toContain('t="inlineStr"><is><t>=HYPERLINK(&quot;http://evil.com&quot;, &quot;Click&quot;)</t></is>')
        ->and($sheetXml)->toContain('t="inlineStr"><is><t>=SUM(1, 2)</t></is>')
        ->and($sheetXml)->toContain('t="inlineStr"><is><t>+12345678999</t></is>')
        ->and($sheetXml)->toContain('t="inlineStr"><is><t>-UNIT-A</t></is>');

    // Structural XML assertion: verify no formula elements <f...> exist anywhere in sheet XML
    expect(preg_match('/<f[\s>]/i', $sheetXml))->toBe(0);

    // Verify via workbook reader that values match expected strings/numbers
    $rows = exportedXlsxRows($result['filename']);
    $skuIndex = array_search('SKU', $rows[0], true);
    $nameIndex = array_search('Name', $rows[0], true);
    $barcodeIndex = array_search('Barcode', $rows[0], true);
    $unitIndex = array_search('Base unit', $rows[0], true);
    $reorderIndex = array_search('Reorder point', $rows[0], true);

    expect($skuIndex)->not->toBeFalse()
        ->and($nameIndex)->not->toBeFalse()
        ->and($barcodeIndex)->not->toBeFalse()
        ->and($unitIndex)->not->toBeFalse()
        ->and($reorderIndex)->not->toBeFalse()
        ->and($rows[1][$skuIndex])->toBe('=HYPERLINK("http://evil.com", "Click")')
        ->and($rows[1][$nameIndex])->toBe('=SUM(1, 2)')
        ->and($rows[1][$barcodeIndex])->toBe('+12345678999')
        ->and($rows[1][$unitIndex])->toBe('-UNIT-A')
        ->and($rows[1][$reorderIndex])->toBe(15);
});
