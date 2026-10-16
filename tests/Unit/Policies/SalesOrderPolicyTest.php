<?php

declare(strict_types=1);

use App\Enums\SalesOrderStatus;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\SalesOrderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->policy = new SalesOrderPolicy);

function makeSoPolicy(SalesOrderStatus $status = SalesOrderStatus::Draft): array
{
    $warehouse = Warehouse::factory()->create();
    $order = SalesOrder::factory()->create([
        'warehouse_id' => $warehouse->id,
        'status' => $status,
    ]);

    return ['order' => $order, 'warehouse' => $warehouse];
}

it('viewAny is universal', function () {
    foreach ([User::factory()->admin()->create(), User::factory()->auditor()->create(),
        User::factory()->create(), User::factory()->branchManager()->create()] as $user) {
        expect($this->policy->viewAny($user))->toBeTrue();
    }
});

it('view grants admin/auditor/assigned-staff', function () {
    ['order' => $order, 'warehouse' => $w] = makeSoPolicy();
    expect($this->policy->view(User::factory()->admin()->create(), $order))->toBeTrue();
    expect($this->policy->view(User::factory()->auditor()->create(), $order))->toBeTrue();
    $staff = User::factory()->create();
    $staff->warehouses()->attach($w->id);
    expect($this->policy->view($staff, $order))->toBeTrue();
});

it('create is admin or branch manager only', function () {
    expect($this->policy->create(User::factory()->admin()->create()))->toBeTrue();
    expect($this->policy->create(User::factory()->branchManager()->create()))->toBeTrue();

    expect($this->policy->create(User::factory()->auditor()->create()))->toBeFalse();
    expect($this->policy->create(User::factory()->create()))->toBeFalse();
});

it('update is draft + branch manager warehouse member', function () {
    ['order' => $draft, 'warehouse' => $w] = makeSoPolicy(SalesOrderStatus::Draft);
    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($w->id);
    expect($this->policy->update($bm, $draft))->toBeTrue();

    ['order' => $confirmed, 'warehouse' => $w2] = makeSoPolicy(SalesOrderStatus::Confirmed);
    $bm2 = User::factory()->branchManager()->create();
    $bm2->warehouses()->attach($w2->id);
    expect($this->policy->update($bm2, $confirmed))->toBeFalse();

    // Q5 — WarehouseStaff is read-only on sales orders.
    $staff = User::factory()->create();
    $staff->warehouses()->attach($w->id);
    expect($this->policy->update($staff, $draft))->toBeFalse();
});

it('delete is admin + draft/cancelled only', function () {
    $admin = User::factory()->admin()->create();
    ['order' => $draft] = makeSoPolicy(SalesOrderStatus::Draft);
    ['order' => $dispatched] = makeSoPolicy(SalesOrderStatus::Dispatched);

    expect($this->policy->delete($admin, $draft))->toBeTrue();
    expect($this->policy->delete($admin, $dispatched))->toBeFalse();
    expect($this->policy->delete(User::factory()->create(), $draft))->toBeFalse();
});

it('admin-only abilities are admin-only', function () {
    ['order' => $order] = makeSoPolicy();
    $admin = User::factory()->admin()->create();
    foreach (['deleteAny', 'restore', 'restoreAny', 'forceDelete', 'forceDeleteAny'] as $ability) {
        expect($this->policy->{$ability}($admin, $order))->toBeTrue();
        expect($this->policy->{$ability}(User::factory()->create(), $order))->toBeFalse();
    }
});

it('confirmSalesOrder is branch manager + warehouse member', function () {
    ['order' => $order, 'warehouse' => $w] = makeSoPolicy(SalesOrderStatus::Draft);
    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($w->id);
    expect($this->policy->confirmSalesOrder($bm, $order))->toBeTrue();
    expect($this->policy->confirmSalesOrder(User::factory()->auditor()->create(), $order))->toBeFalse();

    // Q5 — WarehouseStaff is read-only on sales orders.
    $staff = User::factory()->create();
    $staff->warehouses()->attach($w->id);
    expect($this->policy->confirmSalesOrder($staff, $order))->toBeFalse();
});

it('dispatchSale is branch manager + warehouse member', function () {
    ['order' => $order, 'warehouse' => $w] = makeSoPolicy(SalesOrderStatus::Confirmed);
    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($w->id);
    expect($this->policy->dispatchSale($bm, $order))->toBeTrue();

    $staff = User::factory()->create();
    $staff->warehouses()->attach($w->id);
    expect($this->policy->dispatchSale($staff, $order))->toBeFalse();
});

it('recordSalesReturn is branch manager + warehouse member', function () {
    ['order' => $order, 'warehouse' => $w] = makeSoPolicy(SalesOrderStatus::Dispatched);
    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($w->id);
    expect($this->policy->recordSalesReturn($bm, $order))->toBeTrue();

    $staff = User::factory()->create();
    $staff->warehouses()->attach($w->id);
    expect($this->policy->recordSalesReturn($staff, $order))->toBeFalse();
});

it('cancelSalesOrder honors canBeCancelled boundary', function () {
    ['order' => $order, 'warehouse' => $w] = makeSoPolicy(SalesOrderStatus::Confirmed);
    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($w->id);
    expect($this->policy->cancelSalesOrder($bm, $order))->toBeTrue();

    ['order' => $dispatched, 'warehouse' => $w2] = makeSoPolicy(SalesOrderStatus::Dispatched);
    $bm2 = User::factory()->branchManager()->create();
    $bm2->warehouses()->attach($w2->id);
    expect($this->policy->cancelSalesOrder($bm2, $dispatched))->toBeFalse();

    $staff = User::factory()->create();
    $staff->warehouses()->attach($w->id);
    expect($this->policy->cancelSalesOrder($staff, $order))->toBeFalse();
});

it('viewAuditFilters is admin or auditor only', function () {
    expect($this->policy->viewAuditFilters(User::factory()->admin()->create(), 'X'))->toBeTrue();
    expect($this->policy->viewAuditFilters(User::factory()->auditor()->create(), 'X'))->toBeTrue();
    expect($this->policy->viewAuditFilters(User::factory()->branchManager()->create(), 'X'))->toBeFalse();
});
