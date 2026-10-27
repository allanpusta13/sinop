# Issue #205 — Issue-Text Amendment (owner paste sheet)

**Status:** prepared for manual paste. `gh` was unavailable this pass, so the issue
body was **not** written by this session. No GitHub write occurred.

**Authority:** Phase 3 owner decision **D1 = (b) + (c)** — update the issue text and
the internal docs; **code is NOT changed**.

**Target:** `allanpusta13/sinop` issue **#205**, section *Resolver behavior*.

---

## 1. Authorized edit — the "throws" bullet

**Current text** (as read earlier this session):

> - [ ] An out-of-domain role value causes the resolver to throw (fail-closed), NOT silently widen to the Admin/Auditor tier

**Replacement:**

> - [ ] fails closed by resolving to an empty scope (`[]`) for an out-of-domain role, and never widens to another tier. The `UserRoleCast` throws on read; the model boundary catches and denies; the resolver returns an empty set. A new `UserRole` case must be wired into the resolver — until then it resolves to no scope.

---

## 2. Flagged, NOT authorized — cast-class bullet (for owner review only)

`Resolver behavior` / `Cast contract` also carries:

> - [ ] `User::$casts['role'] = UserRole::class`

Observed on disk: `app/Models/User.php:61` is `'role' => UserRoleCast::class,` with
`use App\Casts\UserRoleCast;` at `:7`. The project uses the custom cast
`App\Casts\UserRoleCast`, not the `UserRole` enum directly.

This bullet is **outside** the D1(b) authorization and was **not** rewritten here.
Owner may fold it into the same paste.

---

## 3. Why the replacement is accurate

| Claim | Evidence |
|---|---|
| Cast throws on read | `app/Casts/UserRoleCast.php` — `get()` throws `ValueError` for an invalid backing value |
| Model boundary catches | `app/Models/User.php:75-86`, `:88-122`, `:132-146` — `try { … } catch (ValueError $e) { return false; }` |
| Resolver returns `[]` | `app/Filament/Support/Concerns/ScopesNavigationBadges.php:129-137` — `default => []` |
| Resolver does not throw | The `match (true)` chain calls only the catching `is*()` helpers, so no `ValueError` reaches the resolver frame |
