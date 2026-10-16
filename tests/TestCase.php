<?php

namespace Tests;

use App\Enums\TransferRequisitionStatus;
use App\Enums\UserRole;
use App\Models\ProductVariant;
use App\Models\TransferRequisition;
use App\Models\TransferRequisitionItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON;');

        $this->actingAs(User::factory()->create([
            'name' => config('app.default_user.name'),
            'email' => config('app.default_user.email'),
            'password' => config('app.default_user.password'),
            'role' => UserRole::Admin->value,
        ]));

        $this->withoutVite();
    }

    protected function actingAsAdmin(?User $user = null): User
    {
        return $user ?? actingAsAdmin();
    }

    protected function actingAsAuditor(?User $user = null): User
    {
        return $user ?? actingAsAuditor();
    }

    protected function actingAsBranchManager(?User $user = null): User
    {
        return $user ?? actingAsBranchManager();
    }

    protected function actingAsWarehouseStaff(?User $user = null, ?Warehouse $warehouse = null): User
    {
        $user = $user ?? actingAsWarehouseStaff();

        if ($warehouse) {
            $user->warehouses()->syncWithoutDetaching([$warehouse->id]);
        }

        return $user;
    }

    protected function makeConfirmedRequisition(InventoryService $service, Warehouse $origin, Warehouse $destination, ProductVariant $variant, int $approvedBaseQty = 240): TransferRequisition
    {
        $requisition = TransferRequisition::factory()->create([
            'from_warehouse_id' => $origin->id,
            'to_warehouse_id' => $destination->id,
            'status' => TransferRequisitionStatus::Confirmed,
        ]);

        TransferRequisitionItem::factory()->create([
            'transfer_requisition_id' => $requisition->id,
            'product_variant_id' => $variant->id,
            'requested_unit_name' => 'Box',
            'requested_unit_ratio' => 24,
            'requested_qty' => 10,
            'requested_base_qty' => 240,
            'approved_unit_name' => 'Box',
            'approved_unit_ratio' => 24,
            'approved_qty' => $approvedBaseQty / 24,
            'approved_base_qty' => $approvedBaseQty,
        ]);

        return $requisition->fresh('items');
    }
}
