# Issue #205 — Issue-Text Amendment (owner paste sheet)

**Status:** the live #205 body carries this sheet's authorized edit set. Applied and
read back (see §8 for the 2026-10-29 paste set and §9 for the Cast contract bullet).
Live `updatedAt`: `2026-10-29T09:01:36Z`. One item is **held** — the *Test coverage*
block, §10. It is stale on the live body and not yet applied.

**Authority:** Phase 3 owner decision **D1 = (b) + (c)** — update the issue text and
the internal docs; **code is NOT changed**.

**Target:** `allanpusta13/sinop` issue **#205**.

---

## 1. Authorized edit — the "throws" bullet

**Current text** (as read earlier this session):

> - [ ] An out-of-domain role value causes the resolver to throw (fail-closed), NOT silently widen to the Admin/Auditor tier

**Replacement:**

> - [ ] fails closed at the cast/model/resolver boundary: the `UserRoleCast` throws on read, the model boundary catches and denies, and the resolver returns an empty scope (`[]`) for an out-of-domain role. A new `UserRole` case must be wired into the resolver; until then it resolves to no scope.

**Revision note (owner review, 2026-10-28).** An earlier draft read *"…and never widens to
another tier."* That claim is **false at the policy layer**: `DirectTransferPolicy.php:26`
grants `viewAny` to a malformed role (#240). Pasting it would have re-created the exact
AC-vs-evidence contradiction G1 exists to eliminate. The bullet is therefore **scoped** to the
cast/model/resolver boundary, and the policy layer is scoped out explicitly below.

---

## 2. Authorized edit — drop two stale requirements, add the behaviour-neutral note

**Current text** (as read earlier this session), in *Resolver behavior* / *Cast contract*:

> - [ ] `User::$casts['role'] = UserRole::class`
> - [ ] An exception from the resolver on an out-of-domain value

**Replacement:**

> - [ ] `User::$casts['role']` binds a role cast. The project binds the custom
>       `App\Casts\UserRoleCast` (`app/Models/User.php:61`), not the `UserRole` enum
>       directly. **This is behaviourally neutral, not a gap:** Laravel's native enum
>       cast resolves through `getEnumCaseFromValue()`
>       (`vendor/laravel/framework/src/Illuminate/Database/Eloquent/Concerns/HasAttributes.php:1326-1331`)
>       via `$enumClass::from($value)`, which raises `ValueError` on **both** the read and
>       the set path. Swapping casts would not make the system any more fail-closed (the
>       same model-level catches would still be required), and the custom cast adds a
>       rate-limited `invalid_user_role` warning the native cast lacks.
> - [ ] ~~An exception from the resolver on an out-of-domain value~~; **removed.** The
>       resolver does not and should not throw: it composes only the catching `is*()`
>       helpers, so no `ValueError` ever reaches its frame. The fail-closed guarantee
>       lives at the **model boundary**, provided policies compose the `is*()` helpers
>       positively rather than negating them; the model boundary alone cannot prevent a
>       negating policy from inverting the result. The resolver's contract is an empty scope.

**Scoping line — required in the paste** (place in the AC or a *Known limitations / Related*
block):

> Policy-layer handling is not covered by this contract. A known fail-open exists at
> `app/Policies/DirectTransferPolicy.php:26` (deny-by-negation:
> `return ! $user->isWarehouseStaff()` grants `viewAny` for a malformed role). It is
> pre-existing, outside this change set, and tracked in #240. All other policy negation sites
> are guard-form and fail closed; see the G1 report's negation inventory.

---

## 3. Why the replacement is accurate

| Claim | Evidence |
|---|---|
| Cast throws on read | `app/Casts/UserRoleCast.php:29-55` — `get()` throws `ValueError` for an invalid backing value |
| Model boundary catches | `app/Models/User.php:75-86`, `:88-122`, `:132-146` — `try { … } catch (ValueError $e) { return false; }` |
| Resolver returns `[]` | `app/Filament/Support/Concerns/ScopesNavigationBadges.php:112-138` — `default => []` |
| Resolver does not throw | The `match (true)` chain calls only the catching `is*()` helpers, so no `ValueError` reaches the resolver frame |
| Cast swap is behaviour-neutral | `HasAttributes.php:1326-1331` — native cast uses `$enumClass::from()`, which throws `ValueError` on read and set |

---

## 4. Required cross-reference — the DC-5 follow-up

The same investigation surfaced a **pre-existing fail-open that this issue does not
cover and that is NOT fixed here**:

- `app/Policies/DirectTransferPolicy.php:26` — `return ! $user->isWarehouseStaff();`.
  Every other policy composes positive predicates, where a malformed role makes the
  helper `false` and therefore denies; this one negates, so a malformed role yields
  `! false` → `true` and **grants `viewAny`**. The helper returns `false` rather than
  throwing (`app/Models/User.php:106-113`, `return false` at `:111`), so the negation is
  genuinely reached.
- Blast radius is bounded — `view()` still requires Admin/Auditor/BranchManager — and it
  needs out-of-band data (`users.role` is `NOT NULL`, and the cast throws on write).
- `UserPolicy.php:61` was checked and is **fail-closed** (guard-clause polarity).
  `InventoryService.php:215,:880` is inconsistent with `hasAccessToWarehouse()` and is
  included in the follow-up.

**The #205 paste must link the follow-up issue #240**, so the negated-predicate finding is not
lost when #205 closes. Analysis:
`docs/01-issues/2026-10-28-g1-evidence-ac-reconciliation.md` §3.4.

Filed: **#240** — https://github.com/allanpusta13/sinop/issues/240 (*Fail-open: malformed role
grants viewAny via negated predicate in DirectTransferPolicy*).

---

## 5. Cited test names — stale, do not create

The issue names three tests that **do not exist on disk** (grep across `tests/` → 0 hits).
D-E maps them to the delivered tests; do not create the named ones.

**Verified 2026-10-29 (observed, direct grep + file read).** Two of the three cited *classes*
exist; only the *method* names are phantom. `tests/Feature/Architecture/BadgeScopeTest.php` is a
real file (17 tests, all badge-count scoping — it contains **no** out-of-domain case).
`tests/Unit/Models/UserModelTest.php` does **not** exist at all; the model tests live in
`tests/Unit/Models/UserTest.php`. The behaviour the AC describes **is** covered, under different
names — so this is a naming defect, **not** a coverage gap:

| Cited (absent) | Delivered — real test name | Site |
|---|---|---|
| `role_cast_throws_on_invalid_value` | `it('fails closed when role attribute contains an invalid value')` | `tests/Unit/Models/UserTest.php:519` |
| `role_cast_throws_on_invalid_value` | `it('rejects invalid role assignment on set')` | `tests/Unit/Models/UserTest.php:544` |
| `role_cast_throws_on_invalid_value` | `it('rejects invalid role assignment through the model set path')` | `tests/Unit/Models/UserTest.php:581` |
| `out_of_domain_role_throws_on_read` | `it('denies warehouse access to a user with an invalid role even if assigned in pivot')` | `tests/Unit/Models/UserTest.php:552` |
| `resolver_fails_closed_for_unknown_role` | `it('fails closed for an out-of-domain role rather than widening')` | `tests/Unit/Listeners/Filament/Support/Concerns/ScopesNavigationBadgesTest.php:233` |
| `resolver_fails_closed_for_unknown_role` | `it('hasBadgeScope fails closed for an out-of-domain role')` | `tests/Unit/Listeners/Filament/Support/Concerns/ScopesNavigationBadgesTest.php:289` |

Note the wording drift the cited names encode: they say **"throws"**, the delivered tests say
**"fails closed"** (deny / empty scope). That is the D1 contract, not a missing assertion — the
cast throws, the model helpers catch and deny, the resolver returns `[]`.

---

## 6. Context paragraph replacement (owner-supplied 2026-10-28; "§3" of the owner's message)

**Minimal diff — the citation sentence is kept, only the final clause is replaced.** The false
clause is *"The resolver reads `role` inside its comparison chain, so an out-of-domain value fails
closed by throwing."*

**Current text (live, as read 2026-10-29):**

> Blueprint §1B.1a, §27 v13.9 row 11. `users.role` carries the `UserRole` enum cast — an out-of-domain value cannot be persisted through the model and cannot be read back (the cast throws). The resolver reads `role` inside its comparison chain, so an out-of-domain value fails closed by throwing.

**Replacement — merged result, to be posted as the Context paragraph:**

> Blueprint §1B.1a, §27 v13.9 row 11. `users.role` carries the `UserRole` enum cast — an out-of-domain value cannot be persisted through the model and cannot be read back (the cast throws). The resolver fails closed by composition: it composes only the catching `is*()` helpers (`isAdmin()`, `isAuditor()`, `isWarehouseStaff()`, `isBranchManager()`) and falls through to `default => []` (`app/Filament/.../ScopesNavigationBadges.php:129-137`). It does not read `$user->role` directly and does not throw. The fail-closed guarantee lives at the model boundary, where the cast's throw is caught and denied.

---

## 7. Resolver behavior bullet 1 replacement (owner-supplied 2026-10-28; "§4" of the owner's message)

**Current text (live, as read 2026-10-29).** The whole bullet body is the false clause, so the
`- [ ]` marker is the only element preserved by the replacement:

> - [ ] `ScopesNavigationBadges::badgeScopedWarehouseIds()` reads `$user->role` inside its `match (true)` chain

**Replacement:**

> - [ ] Resolver behavior: composes only the catching `is*()` helpers and returns an empty scope (`[]`) for out-of-domain values. It must not read `$user->role` directly and must not throw. A new `UserRole` case must be wired into the resolver; until then it resolves to no scope.

---

## 8. Execution record (2026-10-29)

- **Context edit — minimal diff.** Rationale: only the final sentence was false; the citation and the
  preceding facts remain accurate. A whole-paragraph replacement would have churned reviewed text for
  no evidential gain. Citations preserved verbatim.
- **Numbering note (deviation, numbering only).** The owner's message labelled the two texts "§3" and
  "§4" and the record "§6". This sheet already used §3 (*Why the replacement is accurate*) and §4
  (*Required cross-reference — the DC-5 follow-up*). The supplied texts are therefore recorded here as
  §6 and §7, and this record as §8. **No owner-supplied text was altered** — only the section numbers.
- **Edit set for the paste:** §1 (sheet §1) + Context (sheet §6) + resolver bullet 1 (sheet §7) +
  scoping line C (sheet §2). **§2(i) is NOT applied** — owner condition 1 makes §1 the only AC
  replacement; §2(ii) is discharged (no removal record for a line that is absent from the body).
- **Not in scope and left stale, flagged for a separate decision:** the *Test coverage* block still
  names `BadgeScopeTest::out_of_domain_role_throws_on_read`,
  `UserModelTest::role_cast_throws_on_invalid_value`, and
  `ScopesNavigationBadgesTest::resolver_fails_closed_for_unknown_role` — none exist on disk (§5).
  The authorized edit set does not cover that section, so it was not touched. **Superseded by §10**
  (investigated) and **§9** (the §2(i) "not applied" line is corrected there).

---

## 9. Execution record — Cast contract bullet applied (2026-10-29)

**Authorized by:** owner, 2026-10-29 — *"Apply [B]'s first bullet as an in-place
replacement of the live Cast contract bullet, verbatim from the sheet. … This is an
authorized edit."*

**Scope error corrected.** §8 recorded sheet §2(i) as "NOT applied", on the reading
that [B] held one bullet (the phantom one). [B] held two; only the **second** was
phantom. [B]'s **first** bullet is the text applied here.

| Step | Detail |
|---|---|
| Target line | live body line 6, byte offset 659–705 — `- [ ] \`User::$casts['role'] = UserRole::class\`` (47 bytes) |
| Replacement | sheet §2 lines 41–49, blockquote markers stripped, soft wraps flattened to a single line — verbatim, 684 bytes |
| Write | `gh issue edit 205 --body-file …` → https://github.com/allanpusta13/sinop/issues/205 |
| Server timestamp | `updatedAt 2026-10-29T09:01:36Z` (read back twice; body size stable) |

**Byte proof.** Live body 4468 → 5106 bytes. The replaced span moved 47 → 684 bytes
(delta +637). Everything outside the line is byte-identical — `cmp` on the replaced
span against `repl.txt`, on the original span against `target.txt`, and on the
prefix/suffix around them. This is a single-line replacement, not a body rewrite.

**Read-back — one honest caveat.** Equal except a trailing newline. Authored candidate
5105 bytes; live body 5106 bytes; the first 5105 bytes compare identical and the live
body carries one **extra** trailing `\n` (4 vs 3). Attributed to the GitHub write path
appending a trailing newline; content-identical, and **no byte-exact
trailing-newline claim** is made (same posture as the #173 fence line-ending item).

**Transport-damage check on the written body:** zero hits for `theresult`,
`raisesValueError`, `UserRoleenum`, `negation:return`, `docs 01-issues`, `$user.role`
(unhyphenated).

**Old false line:** `UserRole::class` — 0 occurrences in the live body.

---

## 10. HELD — Test coverage block (owner item 2, investigated 2026-10-29)

**Finding: tests exist under different names.** The AC claims stand; only the *names* are
phantom. Nothing was deleted — deleting the claim would hide the mapping.

- `tests/Feature/Architecture/BadgeScopeTest.php` **is** a real file (17 badge-count tests,
  no out-of-domain case).
- `tests/Unit/Models/UserModelTest.php` does **not** exist; the model tests live in
  `tests/Unit/Models/UserTest.php`.
- The behaviour the three cited names describe **is** covered — see the §5 mapping table.
  So this is a **naming defect**, and the owner's rule resolves it to path **(a)**:
  replace the AC bullets with the real test names.

**Status: HELD — not applied to the live body.** The replacement wording is new public
text that is not sourced from this sheet, and the owner's rule selects between (a) and (b)
on report. Awaiting that call.

**Proposed replacement (draft — awaiting confirmation):**

> ### Test coverage
> - [ ] `tests/Unit/Models/UserTest.php` — `fails closed when role attribute contains an invalid value` (`:519`), `rejects invalid role assignment on set` (`:544`), `rejects invalid role assignment through the model set path` (`:581`), `denies warehouse access to a user with an invalid role even if assigned in pivot` (`:552`)
> - [ ] `tests/Unit/Listeners/Filament/Support/Concerns/ScopesNavigationBadgesTest.php` — `fails closed for an out-of-domain role rather than widening` (`:233`), `hasBadgeScope fails closed for an out-of-domain role` (`:289`)

All six names verified on disk by direct grep on 2026-10-29 (file, line, and exact
`it('…')` string). No phantom name is carried forward, and no `- [ ]` marker wording
from the live list is altered beyond the names themselves.
