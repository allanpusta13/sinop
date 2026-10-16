<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\TransferRequisitionStatus;
use App\Models\TransferRequisition;
use App\Models\User;

/**
 * TransferRequisition policy — §8.3.
 *
 * v13.10 role model (Q2, Q10): BranchManager holds the full operational
 * lifecycle (create, update, submit, confirm, dispatch, negotiate,
 * cancel, accept/reject revision) — either endpoint in its assigned set.
 * WarehouseStaff is receive-only: `receive` and `recordLoss` are its
 * only operational abilities (to-endpoint). Admin-only abilities
 * (delete, restore, forceDelete, viewAuditFilters) remain admin-only,
 * and `view` stays readable by both operational roles.
 */
class TransferRequisitionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TransferRequisition $r): bool
    {
        return $user->isAdmin()
            || $user->isAuditor()
            || $user->warehouses->contains($r->from_warehouse_id)
            || $user->warehouses->contains($r->to_warehouse_id);
    }

    public function create(User $user): bool
    {
        // Q2 — requisition authoring is Admin + BranchManager only.
        // WarehouseStaff is receive-only and never authors requisitions;
        // auditors are read-only and never create operational documents.
        return $user->isAdmin() || $user->isBranchManager();
    }

    public function update(User $user, TransferRequisition $r): bool
    {
        // Q2 — draft editing is Admin + BranchManager only (either endpoint
        // for BranchManager); WarehouseStaff is receive-only.
        return $r->status === TransferRequisitionStatus::Draft
            && ($user->isAdmin()
                || ($user->isBranchManager()
                    && ($user->warehouses->contains($r->from_warehouse_id)
                        || $user->warehouses->contains($r->to_warehouse_id))));
    }

    public function delete(User $user, TransferRequisition $r): bool
    {
        return $user->isAdmin()
            && in_array($r->status, [
                TransferRequisitionStatus::Draft,
                TransferRequisitionStatus::Cancelled,
            ], true);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, TransferRequisition $r): bool
    {
        return $user->isAdmin();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, TransferRequisition $r): bool
    {
        return $user->isAdmin();
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function submitRequest(User $user, TransferRequisition $r): bool
    {
        // Q2 — Submit is Admin + BranchManager (either endpoint) only;
        // WarehouseStaff is receive-only and never submits.
        return $r->status === TransferRequisitionStatus::Draft
            && ($user->isAdmin()
                || ($user->isBranchManager()
                    && ($user->warehouses->contains($r->from_warehouse_id)
                        || $user->warehouses->contains($r->to_warehouse_id))));
    }

    public function confirm(User $user, TransferRequisition $r): bool
    {
        // Q2 — Confirm is Admin + BranchManager (either endpoint) only;
        // WarehouseStaff is receive-only and never confirms.
        return $user->isAdmin()
            || ($user->isBranchManager()
                && ($user->warehouses->contains($r->from_warehouse_id)
                    || $user->warehouses->contains($r->to_warehouse_id)));
    }

    public function dispatch(User $user, TransferRequisition $r): bool
    {
        // Q2 — Dispatch is Admin + BranchManager (from endpoint) only;
        // WarehouseStaff is receive-only and never dispatches.
        return $user->isAdmin()
            || ($user->isBranchManager()
                && $user->warehouses->contains($r->from_warehouse_id));
    }

    public function receive(User $user, TransferRequisition $r): bool
    {
        // Q2/Q10 — Receiving is Admin + BranchManager + WarehouseStaff
        // (to endpoint). WarehouseStaff is receive-only by design.
        return $user->isAdmin()
            || (($user->isBranchManager() || $user->isWarehouseStaff())
                && $user->warehouses->contains($r->to_warehouse_id));
    }

    public function recordLoss(User $user, TransferRequisition $r): bool
    {
        // Q2 — Recording loss on receipt is Admin + BranchManager +
        // WarehouseStaff (to endpoint), mirroring receive().
        return $user->isAdmin()
            || (($user->isBranchManager() || $user->isWarehouseStaff())
                && $user->warehouses->contains($r->to_warehouse_id));
    }

    public function cancel(User $user, TransferRequisition $r): bool
    {
        // Q2 — Cancel is Admin + BranchManager (from endpoint) only;
        // WarehouseStaff is receive-only and never cancels.
        return $r->canBeCancelled()
            && ($user->isAdmin()
                || ($user->isBranchManager()
                    && $user->warehouses->contains($r->from_warehouse_id)));
    }

    public function negotiate(User $user, TransferRequisition $r): bool
    {
        // Q2 — Negotiation is Admin + BranchManager (either endpoint) only;
        // WarehouseStaff is receive-only.
        return $this->isNegotiable($r) && (
            $user->isAdmin()
            || ($user->isBranchManager()
                && ($user->warehouses->contains($r->from_warehouse_id)
                    || $user->warehouses->contains($r->to_warehouse_id)))
        );
    }

    public function acceptRevision(User $user, TransferRequisition $r): bool
    {
        // Q2 — Accepting a revision is Admin + BranchManager (either
        // endpoint) only; WarehouseStaff is receive-only.
        return $this->negotiate($user, $r);
    }

    public function rejectRevision(User $user, TransferRequisition $r): bool
    {
        // Q2 — Rejecting a revision is Admin + BranchManager (either
        // endpoint) only; WarehouseStaff is receive-only.
        return $this->negotiate($user, $r);
    }

    /**
     * `$modelClass` is the `Model::class` string forwarded by the call
     * site (`->can('viewAuditFilters', Model::class)`): Laravel resolves
     * the owning policy from that class-string and passes it through as
     * the second argument. The decision itself is role-only (A8).
     *
     * ⚠ BranchManager is NOT granted audit-filter authority — auditor
     * and admin only, per §8.
     */
    public function viewAuditFilters(User $user): bool
    {
        return $user->isAdmin() || $user->isAuditor();
    }

    /**
     * §6.3 `assertNegotiable()` boundary — the three negotiation states.
     */
    private function isNegotiable(TransferRequisition $r): bool
    {
        return in_array($r->status, [
            TransferRequisitionStatus::Requested,
            TransferRequisitionStatus::UnderReviewFulfiller,
            TransferRequisitionStatus::UnderReviewRequestor,
        ], true);
    }
}
