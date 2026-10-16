<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\User;

/**
 * PurchaseOrder policy — §8.7.
 *
 * v13.10 role model (Q4, Q10): BranchManager holds the purchasing
 * lifecycle (create, update, order, cancel) — its assigned warehouse;
 * `receivePurchase` is Admin + BranchManager + WarehouseStaff (Q10 —
 * WarehouseStaff receives POs at its single warehouse). Admin-only
 * abilities (restore, forceDelete, deleteAny, viewAuditFilters) remain
 * admin-only, and `view` stays readable by both operational roles.
 */
class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PurchaseOrder $o): bool
    {
        return $user->isAdmin() || $user->isAuditor() || $user->warehouses->contains($o->warehouse_id);
    }

    public function create(User $user): bool
    {
        // Q4 — purchase authoring is Admin + BranchManager only;
        // WarehouseStaff is receive-only.
        return $user->isAdmin() || $user->isBranchManager();
    }

    public function update(User $user, PurchaseOrder $o): bool
    {
        // Q4 — draft editing is Admin + BranchManager (assigned warehouse).
        return $o->status === PurchaseOrderStatus::Draft
            && ($user->isAdmin()
                || ($user->isBranchManager()
                    && $user->warehouses->contains($o->warehouse_id)));
    }

    public function delete(User $user, PurchaseOrder $o): bool
    {
        // Q4 — draft/cancelled deletion stays Admin-only (§8.7).
        return $user->isAdmin() && in_array($o->status, [
            PurchaseOrderStatus::Draft,
            PurchaseOrderStatus::Cancelled,
        ], true);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, PurchaseOrder $o): bool
    {
        return $user->isAdmin();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, PurchaseOrder $o): bool
    {
        return $user->isAdmin();
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function orderPurchase(User $user, PurchaseOrder $o): bool
    {
        // Q4 — ordering is Admin + BranchManager (assigned warehouse);
        // WarehouseStaff is receive-only and never orders.
        return $user->isAdmin()
            || ($user->isBranchManager()
                && $user->warehouses->contains($o->warehouse_id));
    }

    public function receivePurchase(User $user, PurchaseOrder $o): bool
    {
        // Q4/Q10 — receiving is Admin + BranchManager + WarehouseStaff
        // (assigned warehouse). WarehouseStaff receives POs (Q10).
        return $user->isAdmin()
            || (($user->isBranchManager() || $user->isWarehouseStaff())
                && $user->warehouses->contains($o->warehouse_id));
    }

    public function cancelPurchase(User $user, PurchaseOrder $o): bool
    {
        // Q4 — cancellation is Admin + BranchManager (assigned warehouse);
        // WarehouseStaff is receive-only and never cancels.
        return $o->canBeCancelled()
            && ($user->isAdmin()
                || ($user->isBranchManager()
                    && $user->warehouses->contains($o->warehouse_id)));
    }

    public function viewAuditFilters(User $user): bool
    {
        return $user->isAdmin() || $user->isAuditor();
    }
}
