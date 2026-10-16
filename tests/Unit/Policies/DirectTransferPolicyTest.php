<?php

declare(strict_types=1);

use App\Models\DirectTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\DirectTransferPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->policy = new DirectTransferPolicy);

function makeDtPolicy(): array
{
    $from = Warehouse::factory()->create();
    $to = Warehouse::factory()->create();
    $dt = DirectTransfer::factory()->create([
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
    ]);

    return ['dt' => $dt, 'from' => $from, 'to' => $to];
}

it('viewAny excludes warehouse staff only', function () {
    foreach ([User::factory()->admin()->create(), User::factory()->auditor()->create(),
        User::factory()->branchManager()->create()] as $user) {
        expect($this->policy->viewAny($user))->toBeTrue();
    }

    // Q18 — WarehouseStaff holds no direct-transfer ability at all.
    expect($this->policy->viewAny(User::factory()->create()))->toBeFalse();
});

it('view requires admin/auditor or branch manager with BOTH endpoints', function () {
    ['dt' => $dt, 'from' => $from, 'to' => $to] = makeDtPolicy();

    expect($this->policy->view(User::factory()->admin()->create(), $dt))->toBeTrue();
    expect($this->policy->view(User::factory()->auditor()->create(), $dt))->toBeTrue();

    $both = User::factory()->branchManager()->create();
    $both->warehouses()->attach([$from->id, $to->id]);
    expect($this->policy->view($both, $dt))->toBeTrue();

    // Only one endpoint — denied.
    $one = User::factory()->branchManager()->create();
    $one->warehouses()->attach($from->id);
    expect($this->policy->view($one, $dt))->toBeFalse();

    // Neither — denied.
    expect($this->policy->view(User::factory()->branchManager()->create(), $dt))->toBeFalse();

    // Warehouse staff — denied even with both endpoints.
    $staff = User::factory()->create();
    $staff->warehouses()->attach([$from->id, $to->id]);
    expect($this->policy->view($staff, $dt))->toBeFalse();
});

it('create is admin or branch manager only', function () {
    expect($this->policy->create(User::factory()->admin()->create()))->toBeTrue();
    expect($this->policy->create(User::factory()->branchManager()->create()))->toBeTrue();

    expect($this->policy->create(User::factory()->create()))->toBeFalse();
    expect($this->policy->create(User::factory()->auditor()->create()))->toBeFalse();
});

it('update is denied for everyone (fire-and-forget)', function () {
    ['dt' => $dt] = makeDtPolicy();
    foreach ([User::factory()->admin()->create(), User::factory()->auditor()->create(),
        User::factory()->create(), User::factory()->branchManager()->create()] as $user) {
        expect($this->policy->update($user, $dt))->toBeFalse();
    }
});

it('delete and deleteAny are admin-only', function () {
    ['dt' => $dt] = makeDtPolicy();
    expect($this->policy->delete(User::factory()->admin()->create(), $dt))->toBeTrue();
    expect($this->policy->deleteAny(User::factory()->admin()->create()))->toBeTrue();

    foreach ([User::factory()->auditor()->create(), User::factory()->create(),
        User::factory()->branchManager()->create()] as $user) {
        expect($this->policy->delete($user, $dt))->toBeFalse();
        expect($this->policy->deleteAny($user))->toBeFalse();
    }
});

it('viewAuditFilters is admin or auditor only', function () {
    expect($this->policy->viewAuditFilters(User::factory()->admin()->create(), 'X'))->toBeTrue();
    expect($this->policy->viewAuditFilters(User::factory()->auditor()->create(), 'X'))->toBeTrue();
    expect($this->policy->viewAuditFilters(User::factory()->create(), 'X'))->toBeFalse();
    expect($this->policy->viewAuditFilters(User::factory()->branchManager()->create(), 'X'))->toBeFalse();
});

it('grants branch manager create with two assignments', function () {
    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach(Warehouse::factory()->count(2)->create()->pluck('id'));
    expect($this->policy->create($bm))->toBeTrue();
});
