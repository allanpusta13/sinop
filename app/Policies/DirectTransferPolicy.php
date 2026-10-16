<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DirectTransfer;
use App\Models\User;

/**
 * DirectTransfer policy — §8.13.
 *
 * v13.10 role model (Q3, Q18): WarehouseStaff is removed entirely from
 * this resource — it holds no direct-transfer ability. `create` is Admin
 * + BranchManager; `view` is Admin + Auditor + BranchManager with both
 * endpoints in the actor's assigned set. `update` is denied
 * (fire-and-forget); `delete` and audit filters stay admin-only.
 */
class DirectTransferPolicy
{
    public function viewAny(User $user): bool
    {
        // Q18 — WarehouseStaff holds no direct-transfer ability at all,
        // so the resource is hidden from its navigation and list. Filament
        // drops a resource from navigation when `viewAny` is denied.
        return ! $user->isWarehouseStaff();
    }

    public function view(User $user, DirectTransfer $t): bool
    {
        // Q3/Q18 — Admin + Auditor see all; BranchManager requires both
        // endpoints in its assigned set. WarehouseStaff is denied.
        return $user->isAdmin()
            || $user->isAuditor()
            || ($user->isBranchManager()
                && $user->warehouses->contains($t->from_warehouse_id)
                && $user->warehouses->contains($t->to_warehouse_id));
    }

    public function create(User $user): bool
    {
        // Q3/Q18 — direct-transfer authoring is Admin + BranchManager only.
        // WarehouseStaff holds no direct-transfer ability. Admins hold
        // global scope with a possibly empty pivot and must not be gated
        // on assignments; BranchManager's both-endpoint precondition is
        // enforced by the service (§6.2) and the scoped warehouse selects
        // (§7C.1).
        return $user->isAdmin() || $user->isBranchManager();
    }

    public function update(User $user, DirectTransfer $t): bool
    {
        return false; // fire-and-forget: no edit
    }

    public function delete(User $user, DirectTransfer $t): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * `$modelClass` is the `Model::class` string forwarded by the call
     * site (`->can('viewAuditFilters', Model::class)`): Laravel resolves
     * the owning policy from that class-string and passes it through as
     * the second argument. The decision itself is role-only (A8).
     *
     * ⚠ BranchManager is NOT granted audit-filter authority.
     */
    public function viewAuditFilters(User $user): bool
    {
        return $user->isAdmin() || $user->isAuditor();
    }
}
