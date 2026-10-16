<?php

declare(strict_types=1);
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\DirectTransfers\DirectTransferResource;
use App\Filament\Resources\InTransits\InTransitResource;
use App\Filament\Resources\LossLedgers\LossLedgerResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Filament\Resources\SalesOrders\SalesOrderResource;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Filament\Resources\TransferRequisitions\TransferRequisitionResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Warehouses\WarehouseResource;
use App\Filament\Support\Concerns\ScopesNavigationBadges;
use App\Filament\Support\Filters\AdminReviewFilters;
use App\Filament\Widgets\ActiveInTransitWidget;
use App\Filament\Widgets\LowStockAlertsWidget;
use App\Filament\Widgets\PendingFulfillmentWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentMovementsWidget;
use App\Filament\Widgets\SalesRevenueTrendWidget;
use App\Filament\Widgets\SalesVsPurchasesWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\TopSellingVariantsWidget;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Widgets\Widget;

/**
 * §23.2 — Resource Completeness.
 *
 * Every Resource and Page class named in §18.2 must be autoloadable.
 * Missing classes are the failure mode that hides as "route not found"
 * in production.
 */

// ---------------------------------------------------------------------------
// Resources — 12 classes
// ---------------------------------------------------------------------------

it('autoloads every Filament Resource class', function (string $class) {
    expect(class_exists($class))->toBeTrue(
        "Resource class {$class} does not exist."
    );
    expect(is_subclass_of($class, Filament\Resources\Resource::class))->toBeTrue();
})->with([
    ProductResource::class,
    TransferRequisitionResource::class,
    DirectTransferResource::class,
    InTransitResource::class,
    StockMovementResource::class,
    LossLedgerResource::class,
    WarehouseResource::class,
    UserResource::class,
    PurchaseOrderResource::class,
    SalesOrderResource::class,
    SupplierResource::class,
    CustomerResource::class,
]);

// ---------------------------------------------------------------------------
// Pages — every Page referenced by a Resource's getPages()
// ---------------------------------------------------------------------------

it('autoloads every Filament Page referenced by a resource', function (string $resourceClass) {
    $pages = $resourceClass::getPages();

    foreach ($pages as $key => $registration) {
        $pageClass = $registration->getPage();

        expect(class_exists($pageClass))->toBeTrue(
            "Page '{$key}' for {$resourceClass} does not exist: {$pageClass}"
        );

        expect(is_subclass_of($pageClass, Page::class))
            ->toBeTrue("{$pageClass} is not a Filament Page.");
    }
})->with([
    ProductResource::class,
    TransferRequisitionResource::class,
    DirectTransferResource::class,
    InTransitResource::class,
    StockMovementResource::class,
    LossLedgerResource::class,
    WarehouseResource::class,
    UserResource::class,
    PurchaseOrderResource::class,
    SalesOrderResource::class,
    SupplierResource::class,
    CustomerResource::class,
]);

// ---------------------------------------------------------------------------
// Widgets — 9 classes
// ---------------------------------------------------------------------------

it('autoloads every dashboard widget', function (string $class) {
    expect(class_exists($class))->toBeTrue(
        "Widget {$class} does not exist."
    );
    expect(is_subclass_of($class, Widget::class))->toBeTrue();
})->with([
    StatsOverviewWidget::class,
    LowStockAlertsWidget::class,
    RecentMovementsWidget::class,
    SalesRevenueTrendWidget::class,
    ActiveInTransitWidget::class,
    SalesVsPurchasesWidget::class,
    TopSellingVariantsWidget::class,
    PendingFulfillmentWidget::class,
    QuickActionsWidget::class,
]);

// ---------------------------------------------------------------------------
// Support classes
// ---------------------------------------------------------------------------

it('autoloads the shared Filament support classes', function (string $class) {
    expect(class_exists($class) || trait_exists($class))->toBeTrue();
})->with([
    ScopesNavigationBadges::class,
    AdminReviewFilters::class,
    // App\Filament\Support\Wizards\WizardReviewStep::class,
]);

// ---------------------------------------------------------------------------
// Blade views referenced by widgets and wizards
// ---------------------------------------------------------------------------

it('declares the quick-actions blade view referenced by QuickActionsWidget', function () {
    expect(view()->exists('filament.widgets.quick-actions'))->toBeTrue();
});

it('declares every wizard review blade view', function (string $view) {
    expect(view()->exists($view))->toBeTrue("Blade view {$view} does not exist.");
})->with([
    'filament.wizards.transfer-review',
    'filament.wizards.purchase-order-review',
    'filament.wizards.sales-order-review',
    'filament.wizards.direct-transfer-review',
]);

// ---------------------------------------------------------------------------
// STN views
// ---------------------------------------------------------------------------

it('declares the STN print and scan blade views', function (string $view) {
    expect(view()->exists($view))->toBeTrue();
})->with([
    'stn.print',
    'stn.scan',
    'livewire.stn.scan-form',
]);
