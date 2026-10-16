<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\DomainRuleViolationException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * WarehouseAssignmentService — the authorized, transactional writer for
 * the `user_warehouse` pivot (§6.2, §8.12, Q8/Q11).
 *
 * The UsersTable `reassignWarehouses` action and any other programmatic
 * reassignment route through here; the form-level `minItems`/`maxItems`
 * (UserForm §7L.1) is a UX hint only and never the boundary.
 *
 * Rules enforced inside the transaction:
 *   - Authorization: `Gate::authorize('reassignWarehouses', $target)`
 *     (UserPolicy §8.12 — Admin anywhere; BranchManager own-scope,
 *     WarehouseStaff-only, non-self; Q6/Q11–Q14).
 *   - WarehouseStaff holds at most one warehouse (§Q8). Enforced here as
 *     the server-side boundary — there is deliberately NO plain unique
 *     constraint on `user_warehouse.user_id` (Q8).
 */
class WarehouseAssignmentService
{
    /**
     * Replace the target user's warehouse assignments.
     *
     * @param  array<int, int|string>  $warehouseIds
     *
     * @throws AuthorizationException
     * @throws DomainRuleViolationException
     */
    public function assign(User $actor, User $target, array $warehouseIds): void
    {
        Gate::authorize('reassignWarehouses', $target);

        $ids = $this->normalizeWarehouseIds($warehouseIds);

        if ($target->role === UserRole::WarehouseStaff && count($ids) > 1) {
            throw new DomainRuleViolationException('errors.warehouse_assignment_count', [
                ':count' => count($ids),
            ]);
        }

        DB::transaction(function () use ($target, $ids): void {
            $target->warehouses()->sync($ids);
        });
    }

    /**
     * Deduplicate and cast the incoming warehouse IDs to int.
     *
     * @param  array<int, int|string>  $warehouseIds
     * @return array<int, int>
     */
    private function normalizeWarehouseIds(array $warehouseIds): array
    {
        return array_values(array_unique(array_map(
            static fn (int|string $id): int => (int) $id,
            $warehouseIds,
        )));
    }
}
