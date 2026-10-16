<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\DirectTransfer;
use App\Models\InTransit;
use App\Models\LossLedger;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\TransferRequisition;
use App\Models\TransferRequisitionItemRevision;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\CustomerPolicy;
use App\Policies\DirectTransferPolicy;
use App\Policies\InTransitPolicy;
use App\Policies\LossLedgerPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProductVariantPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\SalesOrderPolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\TransferRequisitionItemRevisionPolicy;
use App\Policies\TransferRequisitionPolicy;
use App\Policies\UserPolicy;
use App\Policies\WarehousePolicy;
use Illuminate\Support\Facades\Gate;

/**
 * §23.3 — Policy Registration.
 *
 * Every model→policy binding must be registered EXPLICITLY by the map in
 * AppServiceProvider::registerPolicies().
 *
 * NOTE: `Gate::getPolicyFor()` alone is a weak oracle. `Gate::resolvePolicy()`
 * falls back to `guessPolicyName()`, which auto-discovers
 * `App\Models\X` → `App\Policies\XPolicy` for every pair here — so a suite
 * asserting only `getPolicyFor()` passes even with `registerPolicies()`
 * deleted. The primary assertions below read the explicit `Gate::$policies`
 * map instead.
 */
$policyMap = [
    [Product::class,             ProductPolicy::class],
    [ProductVariant::class,      ProductVariantPolicy::class],
    [TransferRequisition::class, TransferRequisitionPolicy::class],
    [InTransit::class,           InTransitPolicy::class],
    [StockMovement::class,       StockMovementPolicy::class],
    [LossLedger::class,          LossLedgerPolicy::class],
    [PurchaseOrder::class,       PurchaseOrderPolicy::class],
    [SalesOrder::class,          SalesOrderPolicy::class],
    [Supplier::class,            SupplierPolicy::class],
    [Customer::class,            CustomerPolicy::class],
    [Warehouse::class,           WarehousePolicy::class],
    [User::class,                UserPolicy::class],
    [DirectTransfer::class,      DirectTransferPolicy::class],
    [TransferRequisitionItemRevision::class, TransferRequisitionItemRevisionPolicy::class],
];

/** The explicit model→policy map held by the Gate (not auto-discovery). */
$explicitPolicyMap = function (): array {
    $gate = Gate::getFacadeRoot();
    $property = new ReflectionProperty($gate, 'policies');
    $property->setAccessible(true);

    return $property->getValue($gate);
};

it('registers every model→policy pair explicitly in the Gate map', function (string $model, string $policy) use ($explicitPolicyMap) {
    expect($explicitPolicyMap()[$model] ?? null)->toBe($policy);
})->with($policyMap);

it('resolves every model to its policy through the Gate', function (string $model, string $policy) {
    expect(Gate::getPolicyFor($model))->toBeInstanceOf($policy);
})->with($policyMap);

it('removes StockMovementPolicy::createDirectTransfer', function () {
    // §8.5: createDirectTransfer() moved to DirectTransferPolicy::create().
    // The old ability must not exist.
    $reflection = new ReflectionClass(StockMovementPolicy::class);

    expect($reflection->hasMethod('createDirectTransfer'))->toBeFalse();
});

it('declares DirectTransferPolicy::create instead', function () {
    $reflection = new ReflectionClass(DirectTransferPolicy::class);

    expect($reflection->hasMethod('create'))->toBeTrue();
});

it('registers a policy for every §8 model in the explicit Gate map', function () use ($explicitPolicyMap) {
    // §17.4: canonical registration site is AppServiceProvider::registerPolicies()
    // (Laravel 11+ slim skeleton). Assert the EXPLICIT map, not auto-discovery.
    $allModels = [
        Product::class,
        ProductVariant::class,
        TransferRequisition::class,
        InTransit::class,
        StockMovement::class,
        LossLedger::class,
        PurchaseOrder::class,
        SalesOrder::class,
        Supplier::class,
        Customer::class,
        Warehouse::class,
        User::class,
        DirectTransfer::class,
        TransferRequisitionItemRevision::class,
    ];

    $explicit = $explicitPolicyMap();

    foreach ($allModels as $model) {
        expect($explicit[$model] ?? null)->not->toBeNull(
            "No policy explicitly registered for {$model}."
        );
    }
});
