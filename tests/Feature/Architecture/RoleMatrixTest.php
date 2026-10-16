<?php

declare(strict_types=1);

use App\Enums\TransferRequisitionStatus;
use App\Enums\UserRole;
use App\Models\ProductVariant;
use App\Models\TransferRequisition;
use App\Models\TransferRequisitionItem;
use App\Models\TransferRequisitionItemRevision;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\DirectTransferPolicy;
use App\Policies\ProductVariantPolicy;
use App\Policies\TransferRequisitionItemRevisionPolicy;
use App\Policies\TransferRequisitionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * §12 Pest coverage — `RoleMatrixTest` (Q1–Q5, Q9).
 *
 * The cross-role permission matrix for the v13.10 role model:
 * WarehouseStaff is receive-only; BranchManager holds the broader
 * operational lifecycle.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->trPolicy = new TransferRequisitionPolicy;
    $this->variantPolicy = new ProductVariantPolicy;
    $this->dtPolicy = new DirectTransferPolicy;
    $this->revisionPolicy = new TransferRequisitionItemRevisionPolicy;
});

/**
 * A requisition owned at `from` and received at `to`.
 *
 * @return array{req: TransferRequisition, from: Warehouse, to: Warehouse}
 */
function roleMatrixRequisition(?TransferRequisitionStatus $status = null): array
{
    $from = Warehouse::factory()->create();
    $to = Warehouse::factory()->create();

    $req = TransferRequisition::factory()->create([
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
        'status' => $status ?? TransferRequisitionStatus::Dispatched,
    ]);

    return ['req' => $req, 'from' => $from, 'to' => $to];
}

it('ws_receive_permitted_on_tr', function () {
    ['req' => $req, 'to' => $to] = roleMatrixRequisition();

    $staff = User::factory()->create(['role' => UserRole::WarehouseStaff->value]);
    $staff->warehouses()->attach($to->id);

    expect($this->trPolicy->receive($staff, $req))->toBeTrue();
    expect($this->trPolicy->recordLoss($staff, $req))->toBeTrue();
});

it('ws_create_denied_on_tr', function () {
    $staff = User::factory()->create(['role' => UserRole::WarehouseStaff->value]);

    expect($this->trPolicy->create($staff))->toBeFalse();
    expect($this->trPolicy->submitRequest($staff, roleMatrixRequisition(TransferRequisitionStatus::Draft)['req']))->toBeFalse();
});

it('ws_dispatch_denied_on_tr', function () {
    ['req' => $req, 'from' => $from] = roleMatrixRequisition();

    $staff = User::factory()->create(['role' => UserRole::WarehouseStaff->value]);
    $staff->warehouses()->attach($from->id);

    expect($this->trPolicy->dispatch($staff, $req))->toBeFalse();
    expect($this->trPolicy->cancel($staff, $req))->toBeFalse();

    // Dispatch remains available to the BranchManager at the from-endpoint.
    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($from->id);
    expect($this->trPolicy->dispatch($bm, $req))->toBeTrue();
});

it('ws_adjust_stock_denied', function () {
    $staff = User::factory()->create(['role' => UserRole::WarehouseStaff->value]);
    $variant = ProductVariant::factory()->create();

    expect($this->variantPolicy->adjustStock($staff, $variant))->toBeFalse();
});

it('ws_direct_transfer_denied', function () {
    $staff = User::factory()->create(['role' => UserRole::WarehouseStaff->value]);
    $warehouse = Warehouse::factory()->create();
    $staff->warehouses()->attach($warehouse->id);

    // Q18 — WarehouseStaff holds no direct-transfer ability at all,
    // including the navigation gate.
    expect($this->dtPolicy->viewAny($staff))->toBeFalse();
    expect($this->dtPolicy->create($staff))->toBeFalse();
});

it('bm_create_permitted_on_tr', function () {
    $a = Warehouse::factory()->create();
    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($a->id);

    expect($this->trPolicy->create($bm))->toBeTrue();

    $req = TransferRequisition::factory()->create([
        'from_warehouse_id' => $a->id,
        'to_warehouse_id' => Warehouse::factory()->create()->id,
        'status' => TransferRequisitionStatus::Draft,
    ]);

    expect($this->trPolicy->submitRequest($bm, $req))->toBeTrue();
});

it('bm_adjust_stock_permitted', function () {
    $bm = User::factory()->branchManager()->create();
    $variant = ProductVariant::factory()->create();

    expect($this->variantPolicy->adjustStock($bm, $variant))->toBeTrue();
});

it('revision_policy_resolves_warehouse_access_via_user_predicate', function () {
    // HALT-2 regression: TransferRequisitionItemRevisionPolicy must call
    // `$user->hasAccessToWarehouse()` (a real User predicate) via the
    // `item` relation — never the non-existent `canAccessWarehouse()`
    // nor the wrong relation name.
    $from = Warehouse::factory()->create();
    $to = Warehouse::factory()->create();

    $req = TransferRequisition::factory()->create([
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
    ]);
    $item = TransferRequisitionItem::factory()->create(['transfer_requisition_id' => $req->id]);
    $revision = TransferRequisitionItemRevision::factory()->create([
        'transfer_requisition_item_id' => $item->id,
    ]);

    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($from->id);

    // Resolves without a fatal error and grants the in-scope manager.
    expect($this->revisionPolicy->view($bm, $revision))->toBeTrue();

    $stranger = User::factory()->branchManager()->create();
    $stranger->warehouses()->attach(Warehouse::factory()->create()->id);

    expect($this->revisionPolicy->view($stranger, $revision))->toBeFalse();

    // Source guard: the phantom predicate must not reappear.
    $source = file_get_contents(app_path('Policies/TransferRequisitionItemRevisionPolicy.php'));
    expect($source)->not->toContain('canAccessWarehouse')
        ->and($source)->toContain('hasAccessToWarehouse')
        ->and($source)->toContain('$revision->item->transferRequisition');
});
