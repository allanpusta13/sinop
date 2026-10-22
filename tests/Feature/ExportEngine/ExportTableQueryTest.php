<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/export_helpers.php';

use App\Enums\InTransitStatus;
use App\Filament\Resources\InTransits\InTransitResource;
use App\Filament\Resources\InTransits\Pages\ListInTransits;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Models\InTransit;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\TransferRequisition;
use App\Models\TransferRequisitionItem;
use App\Models\Warehouse;
use App\Services\Export\ExportService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

if (! function_exists('exportSnapshotFromPage')) {
    /**
     * Compile a definition against the query the page itself would hand
     * the engine, optionally driving the table first so the search,
     * filters and sort are part of the compiled snapshot.
     *
     * @param  (callable(object): void)|null  $drive
     * @return array{0: string, 1: array<int, mixed>}
     */
    function exportSnapshotFromPage(string $page, string $key, ?callable $drive = null): array
    {
        $testable = Livewire::test($page);

        if ($drive !== null) {
            $drive($testable);
        }

        $service = app(ExportService::class);
        $definition = $service->resolveDefinition($key);

        return $service->snapshot(
            $definition,
            $definition->query($testable->instance()->getTableQueryForExport()),
        );
    }
}

/*
 * These tests drive the engine with a *real* Filament table query rather
 * than a fixture-built one. A fixture query carries none of the bare
 * column references Filament produces, so the ambiguity and Expression
 * bugs those references cause only surface here.
 */

test('compiles the products table query with a search term applied', function () {
    actingAsAdmin();
    seedExportGraph();

    // The table searches `name` and `sku` bare; the definition joins
    // `products as pf`, which also has a `name`.
    [$sql, $bindings] = exportSnapshotFromPage(
        ListProducts::class,
        'products.variants',
        fn (object $testable) => $testable->searchTable('Main'),
    );

    expect(DB::select($sql, $bindings))->toBeArray();
});

test('compiles the stock movements query with a filter and a relationship sort', function () {
    actingAsAdmin();
    seedExportGraph();

    // The `created_at` filter and the `productVariant.sku` sort both
    // produce column references that the joined `users` table makes
    // ambiguous, and a relationship sort arrives as an Expression.
    [$sql, $bindings] = exportSnapshotFromPage(
        ListStockMovements::class,
        'stock_movements',
        fn (object $testable) => $testable
            ->filterTable('type', 'adjustment')
            ->sortTable('productVariant.sku', 'asc'),
    );

    expect(DB::select($sql, $bindings))->toBeArray();
});

test('qualifies a bare sort column against the definition own table', function () {
    actingAsAdmin();
    seedExportGraph();

    $service = app(ExportService::class);
    $definition = $service->resolveDefinition('stock_movements');

    [$sql] = $service->snapshot(
        $definition,
        $definition->query(StockMovement::query()->orderBy('created_at', 'desc')),
    );

    $order = substr($sql, (int) strpos($sql, 'order by'));

    expect($order)->toContain(DB::getQueryGrammar()->wrap('stock_movements.created_at'));
});

test('leaves a relationship sort untouched', function () {
    actingAsAdmin();
    seedExportGraph();

    $service = app(ExportService::class);
    $definition = $service->resolveDefinition('stock_movements');

    // Filament sorts by a relationship with a sub-select, which arrives as
    // an already-compiled Expression rather than a column name.
    $subquery = ProductVariant::select('sku')
        ->whereColumn('product_variants.id', 'stock_movements.product_variant_id');

    [$sql, $bindings] = $service->snapshot(
        $definition,
        $definition->query(StockMovement::query()->orderBy($subquery, 'asc')),
    );

    expect(DB::select($sql, $bindings))->toBeArray();
});

test('appends the primary key so paged generation is deterministic', function () {
    actingAsAdmin();

    $service = app(ExportService::class);
    $key = 'transfer_requisitions.items';
    $definition = $service->resolveDefinition($key);

    // A child definition builds a fresh builder with no sort of its own,
    // so limit/offset paging would otherwise have nothing to hold onto.
    [$sql] = $service->snapshot(
        $definition,
        $definition->query(exportTableQueryFor($key)),
    );

    $order = substr($sql, (int) strpos($sql, 'order by'));

    expect($order)->toContain('order by')
        ->and($order)->toContain(DB::getQueryGrammar()->wrap('transfer_requisition_items.id'));
});

test('keeps a user chosen sort ahead of the primary key tiebreaker', function () {
    actingAsAdmin();
    seedExportGraph();

    $service = app(ExportService::class);
    $definition = $service->resolveDefinition('stock_movements');

    // A definition served from its own List page carries the table's sort
    // through, so the tiebreaker has to land behind it rather than
    // replace it.
    [$sql] = $service->snapshot(
        $definition,
        $definition->query(
            StockMovement::query()->orderBy('created_at', 'desc'),
        ),
    );

    // Both columns also appear in the select list, so the ordering has to
    // be read from the order clause itself.
    $order = substr($sql, (int) strpos($sql, 'order by'));
    $grammar = DB::getQueryGrammar();

    $userSort = strpos($order, $grammar->wrap('stock_movements.created_at'));
    $tiebreaker = strpos($order, $grammar->wrap('stock_movements.id'));

    expect($userSort)->not->toBeFalse()
        ->and($tiebreaker)->not->toBeFalse()
        ->and($userSort)->toBeLessThan($tiebreaker);
});

test('exports only the in-transits rows a warehouse scoped user can see', function () {
    $variant = ProductVariant::factory()->create();
    $mine = Warehouse::factory()->create();
    $theirs = Warehouse::factory()->create();

    $reachable = TransferRequisition::factory()->confirmed()->create([
        'from_warehouse_id' => $mine->getKey(),
        'to_warehouse_id' => $mine->getKey(),
    ]);
    $hidden = TransferRequisition::factory()->confirmed()->create([
        'from_warehouse_id' => $theirs->getKey(),
        'to_warehouse_id' => $theirs->getKey(),
    ]);

    foreach ([$reachable, $hidden] as $requisition) {
        $item = TransferRequisitionItem::factory()->create([
            'transfer_requisition_id' => $requisition->getKey(),
            'product_variant_id' => $variant->getKey(),
        ]);

        InTransit::factory()->create([
            'transfer_requisition_id' => $requisition->getKey(),
            'transfer_requisition_item_id' => $item->getKey(),
            'product_variant_id' => $variant->getKey(),
        ]);
    }

    actingAsStaff($mine);

    $service = app(ExportService::class);
    $definition = $service->resolveDefinition('in_transits');

    [$sql, $bindings] = $service->snapshot(
        $definition,
        $definition->query(InTransitResource::getEloquentQuery()),
    );

    $requisitionIds = array_map(
        static fn (object $row): int => (int) $row->transfer_requisition_id,
        DB::select($sql, $bindings),
    );

    expect($requisitionIds)->toHaveCount(1)
        ->and($requisitionIds)->toContain($reachable->getKey())
        ->and($requisitionIds)->not->toContain($hidden->getKey());
});

test('carries the in-transits table filters into the file', function () {
    $variant = ProductVariant::factory()->create();
    $requisition = TransferRequisition::factory()->confirmed()->create();

    InTransit::factory()->create([
        'transfer_requisition_id' => $requisition->getKey(),
        'transfer_requisition_item_id' => TransferRequisitionItem::factory()->create([
            'transfer_requisition_id' => $requisition->getKey(),
            'product_variant_id' => $variant->getKey(),
        ])->getKey(),
        'product_variant_id' => $variant->getKey(),
        'status' => InTransitStatus::InTransit,
    ]);

    InTransit::factory()->create([
        'transfer_requisition_id' => $requisition->getKey(),
        'transfer_requisition_item_id' => TransferRequisitionItem::factory()->create([
            'transfer_requisition_id' => $requisition->getKey(),
            'product_variant_id' => $variant->getKey(),
        ])->getKey(),
        'product_variant_id' => $variant->getKey(),
        'status' => InTransitStatus::Cleared,
    ]);

    // A definition that rebuilds its own query instead of starting from the
    // page's would silently drop this filter and return both rows.
    [$sql, $bindings] = exportSnapshotFromPage(
        ListInTransits::class,
        'in_transits',
        fn (object $testable) => $testable->filterTable('status', 'cleared'),
    );

    $rows = DB::select($sql, $bindings);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->status)->toBe(InTransitStatus::Cleared->value);
});

test('can narrow every export that declares a warehouse scope', function () {
    actingAsAdmin();
    seedExportGraph();

    $service = app(ExportService::class);
    $narrowed = [];

    foreach ($service->definitions() as $key => $class) {
        $definition = $service->resolveDefinition($key);
        $scope = $definition->warehouseScope();

        if ($scope === []) {
            continue;
        }

        $narrowed[] = $key;

        [$sql, $bindings] = $service->snapshot($definition, $definition->query(exportTableQueryFor($key)));
        [$sql, $bindings] = $service->narrowToScope($sql, $bindings, $definition, [1]);

        // Proves the scope columns really are selected by the definition
        // — a missing join alias or a misnamed column surfaces here as a
        // query error rather than as a quietly over-broad file.
        expect(DB::select($sql, $bindings))->toBeArray();
    }

    expect($narrowed)->not->toBeEmpty();
});

test('leaves the master data exports with no warehouse scope at all', function () {
    $service = app(ExportService::class);

    foreach (['products.families', 'products.variants', 'products.current_prices', 'products.unit_conversions', 'suppliers', 'customers'] as $key) {
        expect($service->resolveDefinition($key)->warehouseScope())->toBe([]);
    }
});
