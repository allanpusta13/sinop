<?php

declare(strict_types=1);

use App\Enums\TransferRequisitionStatus;
use App\Models\TransferRequisition;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\TransferRequisitionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->policy = new TransferRequisitionPolicy);

function trPolicyRequisition(TransferRequisitionStatus $status = TransferRequisitionStatus::Draft): array
{
    $from = Warehouse::factory()->create();
    $to = Warehouse::factory()->create();
    $req = TransferRequisition::factory()->create([
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
        'status' => $status,
    ]);

    return ['req' => $req, 'from' => $from, 'to' => $to];
}

it('viewAny is universal', function () {
    foreach ([User::factory()->admin()->create(), User::factory()->auditor()->create(),
        User::factory()->create(), User::factory()->branchManager()->create()] as $user) {
        expect($this->policy->viewAny($user))->toBeTrue();
    }
});

it('view grants admin/auditor/assigned-staff', function () {
    ['req' => $req, 'from' => $from] = trPolicyRequisition();

    expect($this->policy->view(User::factory()->admin()->create(), $req))->toBeTrue();
    expect($this->policy->view(User::factory()->auditor()->create(), $req))->toBeTrue();

    $staff = User::factory()->create();
    $staff->warehouses()->attach($from->id);
    expect($this->policy->view($staff, $req))->toBeTrue();

    $stranger = User::factory()->create();
    expect($this->policy->view($stranger, $req))->toBeFalse();
});

it('create is admin or branch manager only', function () {
    expect($this->policy->create(User::factory()->admin()->create()))->toBeTrue();
    expect($this->policy->create(User::factory()->branchManager()->create()))->toBeTrue();

    // Q2 — WarehouseStaff is receive-only and never authors requisitions.
    expect($this->policy->create(User::factory()->create()))->toBeFalse();

    $auditor = User::factory()->auditor()->create();
    $auditor->warehouses()->attach(Warehouse::factory()->count(2)->create()->pluck('id'));
    expect($this->policy->create($auditor))->toBeFalse();
});

it('create grants branch manager without assignments', function () {
    // Q2 — the assignment precondition is enforced by the service and the
    // scoped warehouse selects, not by the policy.
    expect($this->policy->create(User::factory()->branchManager()->create()))->toBeTrue();
});

it('update is draft-only on either endpoint for branch manager', function () {
    ['req' => $draft, 'from' => $from, 'to' => $to] = trPolicyRequisition(TransferRequisitionStatus::Draft);

    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($from->id);
    expect($this->policy->update($bm, $draft))->toBeTrue();

    $bmTo = User::factory()->branchManager()->create();
    $bmTo->warehouses()->attach($to->id);
    expect($this->policy->update($bmTo, $draft))->toBeTrue();

    expect($this->policy->update($bm, $draft->fresh()->setAttribute('status', TransferRequisitionStatus::Confirmed)))->toBeFalse();

    // Q2 — WarehouseStaff is receive-only.
    $staff = User::factory()->create();
    $staff->warehouses()->attach($from->id);
    expect($this->policy->update($staff, $draft))->toBeFalse();
});

it('delete is admin + draft/cancelled only', function () {
    $admin = User::factory()->admin()->create();
    ['req' => $draft] = trPolicyRequisition(TransferRequisitionStatus::Draft);
    ['req' => $dispatched] = trPolicyRequisition(TransferRequisitionStatus::Dispatched);

    expect($this->policy->delete($admin, $draft))->toBeTrue();
    expect($this->policy->delete($admin, $dispatched))->toBeFalse();
    expect($this->policy->delete(User::factory()->create(), $draft))->toBeFalse();
});

it('admin-only abilities are admin-only', function () {
    ['req' => $req] = trPolicyRequisition();
    foreach (['deleteAny', 'restoreAny', 'forceDeleteAny'] as $ability) {
        expect($this->policy->{$ability}(User::factory()->admin()->create()))->toBeTrue();
        expect($this->policy->{$ability}(User::factory()->create()))->toBeFalse();
        expect($this->policy->{$ability}(User::factory()->branchManager()->create()))->toBeFalse();
    }
});

it('submitRequest requires draft + either endpoint for branch manager', function () {
    ['req' => $draft, 'from' => $from, 'to' => $to] = trPolicyRequisition(TransferRequisitionStatus::Draft);

    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($from->id);
    expect($this->policy->submitRequest($bm, $draft))->toBeTrue();

    $bmTo = User::factory()->branchManager()->create();
    $bmTo->warehouses()->attach($to->id);
    expect($this->policy->submitRequest($bmTo, $draft))->toBeTrue();

    $auditor = User::factory()->auditor()->create();
    $auditor->warehouses()->attach($from->id);
    expect($this->policy->submitRequest($auditor, $draft))->toBeFalse();

    // Q2 — WarehouseStaff is receive-only.
    $staff = User::factory()->create();
    $staff->warehouses()->attach($from->id);
    expect($this->policy->submitRequest($staff, $draft))->toBeFalse();
});

it('confirm requires either endpoint for branch manager', function () {
    ['req' => $req, 'to' => $to] = trPolicyRequisition(TransferRequisitionStatus::Requested);

    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($to->id);
    expect($this->policy->confirm($bm, $req))->toBeTrue();

    expect($this->policy->confirm(User::factory()->auditor()->create(), $req))->toBeFalse();

    // Q2 — WarehouseStaff is receive-only.
    $staff = User::factory()->create();
    $staff->warehouses()->attach($to->id);
    expect($this->policy->confirm($staff, $req))->toBeFalse();
});

it('dispatch requires source warehouse for branch manager', function () {
    ['req' => $req, 'from' => $from, 'to' => $to] = trPolicyRequisition(TransferRequisitionStatus::Confirmed);
    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($from->id);
    expect($this->policy->dispatch($bm, $req))->toBeTrue();

    // To-endpoint only — denied (dispatch is from-endpoint).
    $bmTo = User::factory()->branchManager()->create();
    $bmTo->warehouses()->attach($to->id);
    expect($this->policy->dispatch($bmTo, $req))->toBeFalse();

    // Q2 — WarehouseStaff is receive-only.
    $staff = User::factory()->create();
    $staff->warehouses()->attach($from->id);
    expect($this->policy->dispatch($staff, $req))->toBeFalse();
});

it('receive requires destination warehouse', function () {
    ['req' => $req, 'to' => $to] = trPolicyRequisition(TransferRequisitionStatus::Dispatched);
    $staff = User::factory()->create();
    $staff->warehouses()->attach($to->id);
    expect($this->policy->receive($staff, $req))->toBeTrue();
});

it('recordLoss requires destination warehouse', function () {
    ['req' => $req, 'to' => $to] = trPolicyRequisition(TransferRequisitionStatus::Dispatched);
    $staff = User::factory()->create();
    $staff->warehouses()->attach($to->id);
    expect($this->policy->recordLoss($staff, $req))->toBeTrue();
});

it('cancel honors canBeCancelled boundary', function () {
    ['req' => $cancelable, 'from' => $from] = trPolicyRequisition(TransferRequisitionStatus::Confirmed);
    ['req' => $terminal] = trPolicyRequisition(TransferRequisitionStatus::Completed);

    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($from->id);

    expect($this->policy->cancel($bm, $cancelable))->toBeTrue();
    expect($this->policy->cancel($bm, $terminal))->toBeFalse();

    // Q2 — WarehouseStaff never cancels.
    $staff = User::factory()->create();
    $staff->warehouses()->attach($from->id);
    expect($this->policy->cancel($staff, $cancelable))->toBeFalse();
});

it('negotiate requires a review state and either endpoint', function () {
    ['req' => $req, 'from' => $from, 'to' => $to] = trPolicyRequisition(TransferRequisitionStatus::UnderReviewFulfiller);

    $fromBm = User::factory()->branchManager()->create();
    $fromBm->warehouses()->attach($from->id);
    expect($this->policy->negotiate($fromBm, $req))->toBeTrue();

    $toBm = User::factory()->branchManager()->create();
    $toBm->warehouses()->attach($to->id);
    expect($this->policy->negotiate($toBm, $req))->toBeTrue();

    expect($this->policy->negotiate(User::factory()->auditor()->create(), $req))->toBeFalse();

    // Q2 — WarehouseStaff is receive-only.
    $staff = User::factory()->create();
    $staff->warehouses()->attach($from->id);
    expect($this->policy->negotiate($staff, $req))->toBeFalse();
});

it('acceptRevision / rejectRevision follow the same shape as negotiate', function () {
    ['req' => $req, 'from' => $from] = trPolicyRequisition(TransferRequisitionStatus::UnderReviewRequestor);
    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($from->id);

    expect($this->policy->acceptRevision($bm, $req))->toBeTrue();
    expect($this->policy->rejectRevision($bm, $req))->toBeTrue();
    expect($this->policy->acceptRevision(User::factory()->auditor()->create(), $req))->toBeFalse();

    // Q2 — WarehouseStaff is receive-only.
    $staff = User::factory()->create();
    $staff->warehouses()->attach($from->id);
    expect($this->policy->acceptRevision($staff, $req))->toBeFalse();
});

it('viewAuditFilters is admin or auditor only', function () {
    expect($this->policy->viewAuditFilters(User::factory()->admin()->create(), 'X'))->toBeTrue();
    expect($this->policy->viewAuditFilters(User::factory()->auditor()->create(), 'X'))->toBeTrue();
    expect($this->policy->viewAuditFilters(User::factory()->create(), 'X'))->toBeFalse();
    expect($this->policy->viewAuditFilters(User::factory()->branchManager()->create(), 'X'))->toBeFalse();
});

it('grants operational abilities to branch manager with warehouse scope', function () {
    ['req' => $req, 'from' => $from, 'to' => $to] = trPolicyRequisition(TransferRequisitionStatus::Confirmed);
    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach([$from->id, $to->id]);

    expect($this->policy->view($bm, $req))->toBeTrue();
    expect($this->policy->dispatch($bm, $req))->toBeTrue();
    expect($this->policy->confirm($bm, $req))->toBeTrue();
});
