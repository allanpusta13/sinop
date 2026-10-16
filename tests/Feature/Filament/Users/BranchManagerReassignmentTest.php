<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\UserPolicy;
use App\Services\WarehouseAssignmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * §12 Pest coverage — `BranchManagerReassignmentTest` (Q11–Q14).
 *
 * `UserPolicy::reassignWarehouses()` is the authorization boundary for
 * the UsersTable `reassignWarehouses` action and
 * `WarehouseAssignmentService::assign()`:
 *
 *   - Admin may reassign anywhere.
 *   - BranchManager may reassign only a WarehouseStaff target whose
 *     CURRENT assignments are a subset of the actor's own warehouses
 *     (Q11 — own scope, both endpoints in scope).
 *   - BranchManager may never reassign itself (Q13) or another manager
 *     (Q12).
 *   - WarehouseStaff / Auditor may never reassign anyone.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new UserPolicy;
});

it('bm_can_reassign_staff_within_scope', function () {
    $a = Warehouse::factory()->create();
    $b = Warehouse::factory()->create();

    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach([$a->id, $b->id]);

    $staff = User::factory()->create(['role' => UserRole::WarehouseStaff->value]);
    $staff->warehouses()->attach($a->id);

    expect($this->policy->reassignWarehouses($bm, $staff))->toBeTrue();
});

it('bm_cannot_reassign_outside_scope', function () {
    $mine = Warehouse::factory()->create();
    $foreign = Warehouse::factory()->create();

    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($mine->id);

    $staff = User::factory()->create(['role' => UserRole::WarehouseStaff->value]);
    $staff->warehouses()->attach($foreign->id);

    expect($this->policy->reassignWarehouses($bm, $staff))->toBeFalse();
});

it('bm_cannot_reassign_other_branch_managers', function () {
    $a = Warehouse::factory()->create();

    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($a->id);

    $otherBm = User::factory()->branchManager()->create();
    $otherBm->warehouses()->attach($a->id);

    expect($this->policy->reassignWarehouses($bm, $otherBm))->toBeFalse();
});

it('bm_cannot_reassign_self', function () {
    $a = Warehouse::factory()->create();

    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($a->id);

    expect($this->policy->reassignWarehouses($bm, $bm))->toBeFalse();
});

it('staff_cannot_reassign_anyone', function () {
    $a = Warehouse::factory()->create();

    $staff = User::factory()->create(['role' => UserRole::WarehouseStaff->value]);
    $staff->warehouses()->attach($a->id);

    $target = User::factory()->create(['role' => UserRole::WarehouseStaff->value]);
    $target->warehouses()->attach($a->id);

    expect($this->policy->reassignWarehouses($staff, $target))->toBeFalse();

    $auditor = User::factory()->auditor()->create();
    expect($this->policy->reassignWarehouses($auditor, $target))->toBeFalse();
});

it('admin_can_reassign_anywhere', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->create(['role' => UserRole::WarehouseStaff->value]);
    $staff->warehouses()->attach(Warehouse::factory()->create()->id);

    expect($this->policy->reassignWarehouses($admin, $staff))->toBeTrue();
});

it('service_enforces_the_policy_before_writing', function () {
    // WarehouseAssignmentService::assign() calls Gate::authorize() first,
    // so an out-of-scope reassignment is denied at the boundary.
    $mine = Warehouse::factory()->create();
    $foreign = Warehouse::factory()->create();

    $bm = User::factory()->branchManager()->create();
    $bm->warehouses()->attach($mine->id);

    $staff = User::factory()->create(['role' => UserRole::WarehouseStaff->value]);
    $staff->warehouses()->attach($foreign->id);

    $this->actingAs($bm);

    expect(fn () => app(WarehouseAssignmentService::class)->assign($bm, $staff, [$mine->id]))
        ->toThrow(AuthorizationException::class);

    expect($staff->fresh()->warehouses()->pluck('warehouses.id')->all())->toBe([$foreign->id]);
});
