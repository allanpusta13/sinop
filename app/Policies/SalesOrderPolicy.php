<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SalesOrderStatus;
use App\Models\SalesOrder;
use App\Models\User;

/**
 * SalesOrder policy — §8.8.
 *
 * v13.10 role model (Q5): BranchManager holds the full sale lifecycle
 * (create, update, confirm, dispatch, record return, cancel) — its
 * assigned warehouse. WarehouseStaff is read-only on sales orders and
 * holds no sale-lifecycle ability. Admin-only abilities remain
 * admin-only; `view` stays readable by both operational roles.
 */
class SalesOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SalesOrder $o): bool
    {
        return $user->isAdmin() || $user->isAuditor() || $user->warehouses->contains($o->warehouse_id);
    }

    public function create(User $user): bool
    {
        // Q5 — sales authoring is Admin + BranchManager only;
        // WarehouseStaff holds no sale-lifecycle ability.
        return $user->isAdmin() || $user->isBranchManager();
    }

    public function update(User $user, SalesOrder $o): bool
    {
        // Q5 — draft editing is Admin + BranchManager (assigned warehouse).
        return $o->status === SalesOrderStatus::Draft
            && ($user->isAdmin()
                || ($user->isBranchManager()
                    && $user->warehouses->contains($o->warehouse_id)));
    }

    public function delete(User $user, SalesOrder $o): bool
    {
        return $user->isAdmin() && in_array($o->status, [
            SalesOrderStatus::Draft,
            SalesOrderStatus::Cancelled,
        ], true);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, SalesOrder $o): bool
    {
        return $user->isAdmin();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, SalesOrder $o): bool
    {
        return $user->isAdmin();
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function confirmSalesOrder(User $user, SalesOrder $o): bool
    {
        // Q5 — Admin + BranchManager (assigned warehouse) only.
        return $user->isAdmin()
            || ($user->isBranchManager()
                && $user->warehouses->contains($o->warehouse_id));
    }

    public function dispatchSale(User $user, SalesOrder $o): bool
    {
        // Q5 — Admin + BranchManager (assigned warehouse) only.
        return $user->isAdmin()
            || ($user->isBranchManager()
                && $user->warehouses->contains($o->warehouse_id));
    }

    public function recordSalesReturn(User $user, SalesOrder $o): bool
    {
        // Q5 — Admin + BranchManager (assigned warehouse) only.
        return $user->isAdmin()
            || ($user->isBranchManager()
                && $user->warehouses->contains($o->warehouse_id));
    }

    public function cancelSalesOrder(User $user, SalesOrder $o): bool
    {
        // Q5 — Admin + BranchManager (assigned warehouse) only.
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
