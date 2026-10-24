# Issue #205 Summary — Out-of-Domain Role Fail-Closed Contract (Remediated)

## a) UserRole Type & Model Cast Verification
- **UserRole Column Type:** String column (`users.role`), defaulting to `'warehouse_staff'`.
- **UserRole Enum Definition:** Backed PHP string enum (`App\Enums\UserRole: string`) with 4 cases (`admin`, `auditor`, `warehouse_staff`, `branch_manager`).
- **Nullable:** Column is nullable in database schema.
- **Role Assignment:** Single-role per user.
- **Cast Behavior Fix (`UserRoleCast`):**
  - Plain Laravel `UserRole::class` cast throws `ValueError` on database read if an unrecognized string exists in the `role` column.
  - Replaced with custom `App\Casts\UserRoleCast`.
  - `UserRoleCast::get()` uses `UserRole::tryFrom($value)`. If invalid, it logs a warning security event (`invalid_user_role`) and returns `null` **without throwing an exception**.

## b) Fail-Closed Test Assertions List
In `tests/Unit/Models/UserTest.php`:
1. **Assertion 1 (`hasAccessToWarehouse`):**
   `$user->role = null; expect($user->hasAccessToWarehouse(1))->toBeFalse();` — Unrecognized scope/role strictly denies warehouse access and does NOT broaden permissions.
2. **Assertion 2 (Role Predicates):**
   `expect($user->isAdmin())->toBeFalse(); expect($user->isAuditor())->toBeFalse(); expect($user->isWarehouseStaff())->toBeFalse(); expect($user->isBranchManager())->toBeFalse();`
3. **Assertion 3 (Page Render / Login Path Non-Crashing):**
   `$cast->get($user, 'role', 'corrupted_role', []);` returns `null` and allows model instantiation without breaking rendering.
4. **Assertion 4 (Write Path Rejection):**
   `expect(fn() => $cast->set($user, 'role', 'invalid_string', []))->toThrow(InvalidArgumentException::class);`
5. **Assertion 5 (Validation Layer Message Key):**
   Added key `validation.role.invalid` across `en`, `es`, `tl` for form validation errors on field `role`.

## c) Audit Command Evidence (`users:audit-roles`)
Run against current database state:
```
$ php artisan users:audit-roles
Audit clean: 0 users carry invalid or out-of-domain role values.
```

## d) Security Event Logging & Rate Limiting Confirmation
`UserRoleCast::get()` logs security warnings:
```php
Log::warning('invalid_user_role', [
    'user_id' => $model->getKey(),
    'raw_role' => $stringValue,
]);
```
- Logs raw invalid string and user ID.
- Deduped via log driver configuration to prevent log flooding on bad database records.
