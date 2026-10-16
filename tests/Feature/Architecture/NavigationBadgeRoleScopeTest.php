<?php

declare(strict_types=1);

use App\Enums\InTransitStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\TransferRequisitionStatus;
use App\Filament\Resources\InTransits\InTransitResource;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Filament\Resources\SalesOrders\SalesOrderResource;
use App\Filament\Resources\TransferRequisitions\TransferRequisitionResource;
use App\Models\InTransit;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\TransferRequisition;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * §12 Pest coverage — `NavigationBadgeRoleScopeTest`.
 *
 * Companion to `BadgeScopeTest` (§23.6). Where `BadgeScopeTest` covers
 * the five-tier resolver and the four badge-bearing resources' happy
 * paths, this file pins the ROLE-SCOPE boundary arms, including the
 * v13.10 BranchManager arms (§1B.1a extended tier):
 *
 *   - branch_manager_badge_ignores_counterpart_warehouse_for_single_assignment
 *   - branch_manager_badge_union_of_assigned
 *   - branch_manager_badge_no_counterpart_widening
 *
 * A BranchManager follows the warehouse-assignment tier (assigned
 * warehouses only) with no widening to the full system.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    TransferRequisitionResource::flushBadgeScope();
    PurchaseOrderResource::flushBadgeScope();
    SalesOrderResource::flushBadgeScope();
    InTransitResource::flushBadgeScope();
});

// ---------------------------------------------------------------------------
// TransferRequisition — from/to endpoint scope
// ---------------------------------------------------------------------------

it('transfer_requisition_badge_counts_from_and_to_for_multi_warehouse', function () {
    $a = Warehouse::factory()->create();
    $b = Warehouse::factory()->create();
    $c = Warehouse::factory()->create();

    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach([$a->id, $b->id]);
    $this->actingAs($bm);

    // Touches A (from) — counts.
    TransferRequisition::factory()->count(2)->create([
        'from_warehouse_id' => $a->id,
        'to_warehouse_id' => $c->id,
        'status' => TransferRequisitionStatus::Requested,
    ]);

    // Touches B (to) — counts.
    TransferRequisition::factory()->count(3)->create([
        'from_warehouse_id' => $c->id,
        'to_warehouse_id' => $b->id,
        'status' => TransferRequisitionStatus::Requested,
    ]);

    // Touches neither — must not count.
    TransferRequisition::factory()->count(7)->create([
        'from_warehouse_id' => $c->id,
        'to_warehouse_id' => Warehouse::factory()->create()->id,
        'status' => TransferRequisitionStatus::Requested,
    ]);

    expect(TransferRequisitionResource::getNavigationBadge())->toBe('5');
});

it('transfer_requisition_badge_ignores_counterpart_warehouse_for_single_warehouse_user', function () {
    $assigned = Warehouse::factory()->create();
    $counterpart = Warehouse::factory()->create();

    $staff = User::factory()->warehouseStaff()->create();
    $staff->warehouses()->attach($assigned->id);
    $this->actingAs($staff);

    // Assigned as `from`, counterpart as `to`.
    TransferRequisition::factory()->count(2)->create([
        'from_warehouse_id' => $assigned->id,
        'to_warehouse_id' => $counterpart->id,
        'status' => TransferRequisitionStatus::Requested,
    ]);

    // Counterpart as `from`, assigned as `to`.
    TransferRequisition::factory()->count(1)->create([
        'from_warehouse_id' => $counterpart->id,
        'to_warehouse_id' => $assigned->id,
        'status' => TransferRequisitionStatus::Requested,
    ]);

    // Neither endpoint assigned (through the counterpart) — excluded.
    TransferRequisition::factory()->count(4)->create([
        'from_warehouse_id' => $counterpart->id,
        'to_warehouse_id' => Warehouse::factory()->create()->id,
        'status' => TransferRequisitionStatus::Requested,
    ]);

    expect(TransferRequisitionResource::getNavigationBadge())->toBe('3');
});

// ---------------------------------------------------------------------------
// Single-warehouse scoping on the warehouse_id resources
// ---------------------------------------------------------------------------

it('purchase_order_badge_counts_only_assigned_single_warehouse', function () {
    $assigned = Warehouse::factory()->create();
    $other = Warehouse::factory()->create();

    $staff = User::factory()->warehouseStaff()->create();
    $staff->warehouses()->attach($assigned->id);
    $this->actingAs($staff);

    PurchaseOrder::factory()->count(3)->create([
        'warehouse_id' => $assigned->id,
        'status' => PurchaseOrderStatus::Ordered,
    ]);
    PurchaseOrder::factory()->count(9)->create([
        'warehouse_id' => $other->id,
        'status' => PurchaseOrderStatus::Ordered,
    ]);

    expect(PurchaseOrderResource::getNavigationBadge())->toBe('3');
});

it('sales_order_badge_counts_only_assigned_single_warehouse', function () {
    $assigned = Warehouse::factory()->create();
    $other = Warehouse::factory()->create();

    $staff = User::factory()->warehouseStaff()->create();
    $staff->warehouses()->attach($assigned->id);
    $this->actingAs($staff);

    SalesOrder::factory()->count(2)->confirmed()->create(['warehouse_id' => $assigned->id]);
    SalesOrder::factory()->count(6)->confirmed()->create(['warehouse_id' => $other->id]);

    expect(SalesOrderResource::getNavigationBadge())->toBe('2');
});

it('in_transit_badge_scopes_through_requisition_endpoints', function () {
    $assigned = Warehouse::factory()->create();
    $counterpart = Warehouse::factory()->create();
    $elsewhere = Warehouse::factory()->create();

    $staff = User::factory()->warehouseStaff()->create();
    $staff->warehouses()->attach($assigned->id);
    $this->actingAs($staff);

    $mine = TransferRequisition::factory()->create([
        'from_warehouse_id' => $assigned->id,
        'to_warehouse_id' => $counterpart->id,
    ]);
    $foreign = TransferRequisition::factory()->create([
        'from_warehouse_id' => $counterpart->id,
        'to_warehouse_id' => $elsewhere->id,
    ]);

    InTransit::factory()->count(2)->create([
        'transfer_requisition_id' => $mine->id,
        'status' => InTransitStatus::InTransit,
    ]);
    InTransit::factory()->count(5)->create([
        'transfer_requisition_id' => $foreign->id,
        'status' => InTransitStatus::InTransit,
    ]);

    expect(InTransitResource::getNavigationBadge())->toBe('2');
});

it('single_warehouse_user_never_sees_documents_touching_other_warehouse', function () {
    $assigned = Warehouse::factory()->create();
    $other = Warehouse::factory()->create();

    $staff = User::factory()->warehouseStaff()->create();
    $staff->warehouses()->attach($assigned->id);
    $this->actingAs($staff);

    PurchaseOrder::factory()->count(4)->create([
        'warehouse_id' => $other->id,
        'status' => PurchaseOrderStatus::Ordered,
    ]);
    SalesOrder::factory()->count(4)->confirmed()->create(['warehouse_id' => $other->id]);

    expect(PurchaseOrderResource::getNavigationBadge())->toBeNull();
    expect(SalesOrderResource::getNavigationBadge())->toBeNull();
});

// ---------------------------------------------------------------------------
// BranchManager arms (§1B.1a extended tier)
// ---------------------------------------------------------------------------

it('branch_manager_badge_ignores_counterpart_warehouse_for_single_assignment', function () {
    $assigned = Warehouse::factory()->create();
    $counterpart = Warehouse::factory()->create();
    $elsewhere = Warehouse::factory()->create();

    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($assigned->id);
    $this->actingAs($bm);

    // Assigned endpoint — counts (2).
    TransferRequisition::factory()->count(2)->create([
        'from_warehouse_id' => $assigned->id,
        'to_warehouse_id' => $counterpart->id,
        'status' => TransferRequisitionStatus::Requested,
    ]);

    // Counterpart only — excluded, even though the counterpart appears
    // as an endpoint of an in-scope document.
    TransferRequisition::factory()->count(6)->create([
        'from_warehouse_id' => $counterpart->id,
        'to_warehouse_id' => $elsewhere->id,
        'status' => TransferRequisitionStatus::Requested,
    ]);

    expect(TransferRequisitionResource::getNavigationBadge())->toBe('2');
});

it('branch_manager_badge_union_of_assigned', function () {
    $a = Warehouse::factory()->create();
    $b = Warehouse::factory()->create();
    $c = Warehouse::factory()->create();

    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach([$a->id, $b->id]);
    $this->actingAs($bm);

    PurchaseOrder::factory()->count(4)->create([
        'warehouse_id' => $a->id,
        'status' => PurchaseOrderStatus::Ordered,
    ]);
    PurchaseOrder::factory()->count(6)->create([
        'warehouse_id' => $b->id,
        'status' => PurchaseOrderStatus::Ordered,
    ]);
    PurchaseOrder::factory()->count(20)->create([
        'warehouse_id' => $c->id,
        'status' => PurchaseOrderStatus::Ordered,
    ]);

    expect(PurchaseOrderResource::getNavigationBadge())->toBe('10');
});

it('branch_manager_badge_no_counterpart_widening', function () {
    $a = Warehouse::factory()->create();
    $b = Warehouse::factory()->create();
    $outside = Warehouse::factory()->create();

    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($a->id);
    $this->actingAs($bm);

    // A requisition whose OTHER endpoint is B — B is NOT assigned to this
    // manager, so a naive both-endpoint widening would pull in the row
    // below. Only the A-endpoint row counts.
    TransferRequisition::factory()->create([
        'from_warehouse_id' => $a->id,
        'to_warehouse_id' => $b->id,
        'status' => TransferRequisitionStatus::Requested,
    ]);
    TransferRequisition::factory()->count(5)->create([
        'from_warehouse_id' => $b->id,
        'to_warehouse_id' => $outside->id,
        'status' => TransferRequisitionStatus::Requested,
    ]);

    expect(TransferRequisitionResource::getNavigationBadge())->toBe('1');
});
