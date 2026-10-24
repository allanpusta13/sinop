# Issue #205 Summary — Out-of-Domain Role Fail-Closed Contract

- **Classification:** IMPLEMENT NOW
- **Result:**
  - Implemented `App\Casts\UserRoleCast` which safely catches out-of-domain/invalid raw DB role values, logs a warning (`invalid_user_role`), and returns `null`.
  - Confirmed fail-closed access: when `role` is `null`, `isAdmin()`, `isAuditor()`, `isWarehouseStaff()`, `isBranchManager()`, and `hasAccessToWarehouse($id)` all evaluate to `false`.
  - Enforced strict write path validation throwing `InvalidArgumentException` on direct attempts to set invalid role values.
  - Added Artisan read-only audit command `users:audit-roles` (`app/Console/Commands/AuditUserRoles.php`).
  - Added unit test cases in `tests/Unit/Models/UserTest.php`.
- **Artifacts:** `app/Casts/UserRoleCast.php`, `app/Console/Commands/AuditUserRoles.php`, `.patches/issue-205.patch`
