<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Exceptions\DomainRuleViolationException;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\WarehouseAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * §12 Pest coverage — `WarehouseStaffSingleAssignmentTest` (Q8).
 *
 * WarehouseStaff is capped at a single warehouse. The rule is enforced
 * at three levels:
 *   1. the form (`UserForm` `->maxItems(1)` — UX hint);
 *   2. the service (`WarehouseAssignmentService::assign()` — the
 *      server-side boundary that throws a `DomainRuleViolationException`);
 *   3. the policy (`UserPolicy::reassignWarehouses()` — scope denial).
 *
 * There is deliberately NO plain unique constraint on
 * `user_warehouse.user_id` (Q8), so the service is the enforcement point.
 */
uses(RefreshDatabase::class);

function makeAssignTarget(UserRole $role): User
{
    return User::factory()->create(['role' => $role->value]);
}

it('service_rejects_multiple_on_assign', function () {
    $admin = User::factory()->admin()->create();
    $staff = makeAssignTarget(UserRole::WarehouseStaff);
    $a = Warehouse::factory()->create();
    $b = Warehouse::factory()->create();

    $this->actingAs($admin);

    expect(fn () => app(WarehouseAssignmentService::class)->assign($admin, $staff, [$a->id, $b->id]))
        ->toThrow(DomainRuleViolationException::class);

    expect($staff->fresh()->warehouses()->count())->toBe(0);
});

it('service_accepts_a_single_warehouse_for_staff', function () {
    $admin = User::factory()->admin()->create();
    $staff = makeAssignTarget(UserRole::WarehouseStaff);
    $a = Warehouse::factory()->create();

    $this->actingAs($admin);

    app(WarehouseAssignmentService::class)->assign($admin, $staff, [$a->id]);

    expect($staff->fresh()->warehouses()->pluck('warehouses.id')->all())->toBe([$a->id]);
});

it('branch_manager_form_accepts_multiple', function () {
    // A BranchManager may hold several warehouses — the single-warehouse
    // cap applies to WarehouseStaff only.
    $admin = User::factory()->admin()->create();
    $bm = makeAssignTarget(UserRole::BranchManager);
    $a = Warehouse::factory()->create();
    $b = Warehouse::factory()->create();

    $this->actingAs($admin);

    app(WarehouseAssignmentService::class)->assign($admin, $bm, [$a->id, $b->id]);

    expect($bm->fresh()->warehouses()->pluck('warehouses.id')->all())
        ->toEqualCanonicalizing([$a->id, $b->id]);
});

it('form_rejects_multiple_warehouses_for_staff', function () {
    // The UserForm declares `->maxItems(fn (Get $get) => ... ? 1 : null)`
    // for the WarehouseStaff role and `->minItems(1)` for BranchManager.
    // Source-level assertion: the form is the UX hint that mirrors the
    // service boundary above (the service test is the behavioral proof).
    $source = file_get_contents(app_path('Filament/Resources/Users/Schemas/UserForm.php'));

    expect($source)->toContain('->maxItems(')
        ->and($source)->toContain('UserRole::WarehouseStaff->value ? 1 : null');

    expect($source)->toContain('->minItems(')
        ->and($source)->toContain('UserRole::BranchManager->value ? 1 : 0');
});
