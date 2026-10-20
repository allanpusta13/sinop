<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/export_helpers.php';

use App\Enums\StockMovementType;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportService;
use Illuminate\Support\Facades\App;

beforeEach(function () {
    actingAsAdmin();
    seedExportGraph();
});

/**
 * The full #225 + #226 registry, in the order the blueprint declares it.
 *
 * @return array<int, string>
 */
function exportRegistryKeys(): array
{
    return [
        'products.families',
        'products.variants',
        'products.current_prices',
        'products.unit_conversions',
        'warehouses',
        'suppliers',
        'customers',
        'stock_movements',
        'loss_ledgers',
        'transfer_requisitions',
        'transfer_requisitions.items',
        'transfer_requisitions.revisions',
        'in_transits',
        'purchase_orders',
        'purchase_orders.items',
        'sales_orders',
        'sales_orders.items',
        'direct_transfers',
        'direct_transfers.items',
    ];
}

test('registers the full #225 and #226 surface', function () {
    $keys = array_keys(app(ExportService::class)->definitions());

    expect($keys)->toBe(exportRegistryKeys());
});

test('declares unique column keys', function (string $key) {
    $definition = app(ExportService::class)->resolveDefinition($key);

    $keys = array_map(
        static fn (ExportColumn $column): string => $column->key,
        $definition->columns(),
    );

    expect($keys)->toBe(array_values(array_unique($keys)))
        ->and($keys)->not->toBeEmpty();
})->with(exportRegistryKeys());

test('selects every declared column', function (string $key) {
    $definition = app(ExportService::class)->resolveDefinition($key);

    $row = $definition->query(exportTableQueryFor($key))->toBase()->first();

    expect($row)->not->toBeNull();

    foreach ($definition->columns() as $column) {
        expect(property_exists($row, $column->key))
            ->toBeTrue("Definition [{$key}] declares column [{$column->key}] its query never selects.");
    }
})->with(exportRegistryKeys());

test('exports enum columns as their backing value', function () {
    $definition = app(ExportService::class)->resolveDefinition('stock_movements');

    $row = $definition->query(exportTableQueryFor('stock_movements'))->toBase()->first();

    expect($row->type)->toBeString()
        ->and($row->type)->toBe(StockMovementType::Adjustment->value)
        ->and($row->type)->not->toBe(StockMovementType::Adjustment->name);
});

test('authorizes an admin for every export', function () {
    $service = app(ExportService::class);
    $user = auth()->user();

    $permitted = array_values(array_filter(
        exportRegistryKeys(),
        static fn (string $key): bool => $service->resolveDefinition($key)->can($user),
    ));

    expect($permitted)->toBe(exportRegistryKeys());
});

test('resolves a translated type label for every export in every locale', function () {
    foreach (['en', 'es', 'tl'] as $locale) {
        App::setLocale($locale);

        foreach (config('exports.definitions') as $key => $class) {
            $labelKey = app($class)->labelKey();
            $label = __($labelKey);

            // A dotted key nested under a string sibling resolves to the raw
            // key itself, which reads as a label but never translates.
            expect(is_string($label))->toBeTrue("[$locale] [$key] label is not a string.");
            expect($label)->not->toBe($labelKey, "[$locale] [$key] label key never resolves.");
            expect(trim($label))->not->toBe('', "[$locale] [$key] label is blank.");
        }
    }

    App::setLocale('en');
});

/**
 * Fields that must never reach an export file: hard exclusions from
 * the spec, spec-silent internals, credentials and control records.
 */
dataset('excluded fields', [
    ['products.families', ['id', 'is_active', 'deleted_at', 'created_at', 'updated_at']],
    ['products.variants', ['id', 'product_id', 'images', 'deleted_at', 'created_at', 'updated_at']],
    ['products.current_prices', ['id', 'product_variant_id', 'is_current', 'set_by', 'notes', 'deleted_at', 'created_at', 'updated_at']],
    ['products.unit_conversions', ['id', 'product_variant_id', 'deleted_at', 'created_at', 'updated_at']],
    ['warehouses', ['id', 'deleted_at', 'created_at', 'updated_at']],
    ['suppliers', ['id', 'deleted_at', 'created_at', 'updated_at']],
    ['customers', ['id', 'deleted_at', 'created_at', 'updated_at']],
    ['stock_movements', ['id', 'idempotency_key', 'user_id', 'updated_at', 'deleted_at']],
    ['loss_ledgers', ['id', 'idempotency_key', 'user_id', 'updated_at', 'deleted_at']],
    ['transfer_requisitions', ['id', 'approved_at', 'dispatched_at', 'completed_at', 'cancelled_at', 'user_id', 'updated_at', 'deleted_at']],
    ['transfer_requisitions.items', ['id', 'user_id', 'updated_at', 'deleted_at']],
    ['transfer_requisitions.revisions', ['id', 'user_id', 'updated_at', 'deleted_at']],
    ['in_transits', ['id', 'user_id', 'updated_at', 'deleted_at']],
    ['purchase_orders', ['id', 'cancelled_at', 'user_id', 'updated_at', 'deleted_at']],
    ['purchase_orders.items', ['id', 'user_id', 'updated_at', 'deleted_at']],
    ['sales_orders', ['id', 'ordered_at', 'cancelled_at', 'user_id', 'updated_at', 'deleted_at']],
    ['sales_orders.items', ['id', 'user_id', 'updated_at', 'deleted_at']],
    ['direct_transfers', ['id', 'user_id', 'updated_at', 'deleted_at']],
    ['direct_transfers.items', ['id', 'user_id', 'updated_at', 'deleted_at']],
]);

test('never declares an excluded field', function (string $key, array $forbidden) {
    $definition = app(ExportService::class)->resolveDefinition($key);

    $keys = array_map(
        static fn (ExportColumn $column): string => $column->key,
        $definition->columns(),
    );

    expect(array_values(array_intersect($keys, $forbidden)))->toBe([]);
})->with('excluded fields');
