<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the actor may reassign a target user's warehouses (§8.12).
     *
     * Q6 / Q11–Q14: Admin may reassign anywhere; a BranchManager may
     * reassign only a WarehouseStaff target whose assigned set is a
     * subset of the actor's own warehouses, and may never reassign
     * itself or another manager. WarehouseStaff and Auditor never
     * reassign anyone. The subset rule is the "own warehouses only, both
     * endpoints in scope" boundary (Q11).
     */
    public function reassignWarehouses(User $user, User $target): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isBranchManager() || $user->id === $target->id) {
            return false;
        }

        if (! $target->isWarehouseStaff()) {
            return false;
        }

        $actorIds = $user->warehouses()->pluck('warehouses.id')->all();
        $targetIds = $target->warehouses()->pluck('warehouses.id')->all();

        return count(array_diff($targetIds, $actorIds)) === 0;
    }
}
