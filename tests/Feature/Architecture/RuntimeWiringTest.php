<?php

declare(strict_types=1);

use App\Events\InventoryBelowReorderPoint;
use App\Events\LossRecorded;
use App\Events\PurchaseOrderCancelled;
use App\Events\PurchaseOrderReceived;
use App\Events\SalesOrderDispatched;
use App\Events\TransferCancelled;
use App\Events\TransferConfirmed;
use App\Events\TransferDispatched;
use App\Events\TransferReceived;
use App\Exceptions\ProductFamilyHasVariantsException;
use App\Listeners\NotifyInventoryBelowReorderPoint;
use App\Listeners\NotifyLossRecorded;
use App\Listeners\NotifyPurchaseOrderCancelled;
use App\Listeners\NotifyPurchaseOrderReceived;
use App\Listeners\NotifySalesOrderDispatched;
use App\Listeners\NotifyTransferCancelled;
use App\Listeners\NotifyTransferConfirmed;
use App\Listeners\NotifyTransferDispatched;
use App\Listeners\NotifyTransferReceived;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantUnitConversion;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\InventoryServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;

/**
 * §17 — Runtime Wiring.
 *
 * Verifies that AppServiceProvider::boot() has registered observers,
 * event→listener pairs, and the Logout/Login badge-scope flush.
 */
it('registers ProductObserver on the Product model', function () {
    // §17.3: the observer must be registered before any variant is
    // created so the base-unit self-conversion row (F19) exists.
    // Practical assertion: dispatch a Product deletion with variants and
    // confirm the guard fires — if the observer is missing, no exception.
    $product = Product::factory()->create();
    ProductVariant::factory()->create(['product_id' => $product->id]);

    expect(fn () => $product->delete())
        ->toThrow(ProductFamilyHasVariantsException::class);
});

it('registers ProductVariantObserver on the ProductVariant model', function () {
    $variant = ProductVariant::factory()->create(['base_unit_name' => 'pc']);

    expect(ProductVariantUnitConversion::where('product_variant_id', $variant->id)
        ->where('unit_name', 'pc')
        ->exists())->toBeTrue();
});

it('registers a listener for the TransferConfirmed event', function () {
    expect(Event::hasListeners(TransferConfirmed::class))->toBeTrue();
});

it('registers a listener for the TransferCancelled event', function () {
    expect(Event::hasListeners(TransferCancelled::class))->toBeTrue();
});

it('registers a listener for the TransferDispatched event', function () {
    expect(Event::hasListeners(TransferDispatched::class))->toBeTrue();
});

it('registers a listener for the TransferReceived event', function () {
    expect(Event::hasListeners(TransferReceived::class))->toBeTrue();
});

it('registers a listener for the LossRecorded event', function () {
    expect(Event::hasListeners(LossRecorded::class))->toBeTrue();
});

it('registers a listener for the PurchaseOrderReceived event', function () {
    expect(Event::hasListeners(PurchaseOrderReceived::class))->toBeTrue();
});

it('registers a listener for the PurchaseOrderCancelled event', function () {
    expect(Event::hasListeners(PurchaseOrderCancelled::class))->toBeTrue();
});

it('registers a listener for the SalesOrderDispatched event', function () {
    expect(Event::hasListeners(SalesOrderDispatched::class))->toBeTrue();
});

it('registers a listener for the InventoryBelowReorderPoint event', function () {
    expect(Event::hasListeners(InventoryBelowReorderPoint::class))->toBeTrue();
});

it('registers a Logout listener for badge scope flush', function () {
    expect(Event::hasListeners(Logout::class))->toBeTrue();
});

it('registers a Login listener for badge scope flush', function () {
    expect(Event::hasListeners(Login::class))->toBeTrue();
});

it('every event implements ShouldDispatchAfterCommit', function (string $eventClass) {
    // §22.1a/§22.2 — after-commit delivery for events dispatched from
    // inside a service transaction.
    expect(is_subclass_of($eventClass, ShouldDispatchAfterCommit::class))
        ->toBeTrue("{$eventClass} does not implement ShouldDispatchAfterCommit.");
})->with([
    TransferConfirmed::class,
    TransferCancelled::class,
    TransferDispatched::class,
    TransferReceived::class,
    LossRecorded::class,
    PurchaseOrderReceived::class,
    PurchaseOrderCancelled::class,
    SalesOrderDispatched::class,
    InventoryBelowReorderPoint::class,
]);

it('every listener implements ShouldQueue', function (string $listenerClass) {
    // §22.4 — notification sends are queued; stock mutations remain sync.
    expect(is_subclass_of($listenerClass, ShouldQueue::class))
        ->toBeTrue("{$listenerClass} does not implement ShouldQueue.");
})->with([
    NotifyTransferConfirmed::class,
    NotifyTransferCancelled::class,
    NotifyTransferDispatched::class,
    NotifyTransferReceived::class,
    NotifyLossRecorded::class,
    NotifyPurchaseOrderReceived::class,
    NotifyPurchaseOrderCancelled::class,
    NotifySalesOrderDispatched::class,
    NotifyInventoryBelowReorderPoint::class,
]);

it('every provider is registered in bootstrap/providers.php', function (string $providerClass) {
    $providers = require base_path('bootstrap/providers.php');

    expect($providers)->toContain($providerClass);
})->with([
    AppServiceProvider::class,
    InventoryServiceProvider::class,
    AdminPanelProvider::class,
]);

it('does not register legacy providers (AuthServiceProvider, EventServiceProvider)', function () {
    // §17.2 (Laravel 13 slim skeleton): policy registration and event
    // wiring live in AppServiceProvider::boot(), not dedicated providers.
    $providers = require base_path('bootstrap/providers.php');

    expect($providers)->not->toContain(AuthServiceProvider::class);
    expect($providers)->not->toContain(EventServiceProvider::class);
});

it('registers the currency config key', function () {
    // Phase 00.6 — required for every ->money(config('app.currency')) call.
    expect(config('app.currency'))->toBeString()->not->toBe('');
});
