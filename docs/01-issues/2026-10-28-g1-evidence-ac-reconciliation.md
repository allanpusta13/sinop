# G1 — Evidence & Acceptance-Criteria Reconciliation (#173, #205, #207)

**Pinned revision:** `9ac18567bb22ff293eeece503129fc1d0deefbed` — PR #238 head
(`feature/173-218-orchestration-closure-14910590968341907157`, base `develop`).
**Working tree at pin:** clean (`git status --porcelain` → empty).
**Date:** 2026-10-27 (reconciliation pass); report completed 2026-10-28. Filename carries the
completion date, matching the latest verification evidence (fresh fetch and count
re-confirmation, 2026-10-28).
**Scope:** eliminate contradictions between implementation, tests, documentation and issue
acceptance criteria for #173, #205, #207 using existing evidence and targeted fixes. Not a
re-audit; not a new testing loop.

**Headline:** **no code or test change is warranted for any of the three issues.** All three
implementations are correct and evidence-backed at the pinned revision. Every surviving
contradiction is stale wording in a report or in the GitHub issue body.

**One material finding, surfaced rather than fixed:** a **pre-existing fail-open in
`DirectTransferPolicy::viewAny()`** — the only location in the codebase where a malformed role
*grants* a permission instead of denying it (§3.4). Owner-ruled **out of G1's scope**; tracked in
a separate follow-up issue — **filed as #240** (https://github.com/allanpusta13/sinop/issues/240).
It is a defence-in-depth gap, not an exploitable path, and is not introduced by this change set.
**G1 changes no authorization code.**

---

## 1. Verification performed at the pin

| Evidence | Command | Result |
|---|---|---|
| Tree state | `git status --porcelain` | empty (clean) |
| Canonical key count (repo's own script) | `php docs/02-research/i18n-count-reproduction.php` | `Canonical (en) key count: 996` |
| Canonical key count (parity test's own report) | `php artisan test --filter=TranslationKeyParityTest` | `passed`, 1 test, 5 assertions |
| Parity log content | `storage/logs/i18n-parity.md` | `Canonical keys: 996` / `Missing 0` / `Orphan 0` / `Identity 0` / placeholders `es 0`, `tl 0` |
| Orphan sidecars | `wc -l` | 272 lines each |
| Orphan sidecar rows | `grep -cE '^[^#[:space:]]'` | 258 each |
| Orphan sidecar self-attestation | `tail` | `# TOTAL AUDITED RETIRED ORPHANS IN ES: 258` (and `TL`) |
| Targeted suites | `php artisan test --filter="TranslationKeyParityTest\|TranslationCompletenessTest\|ProductExporterDeletionTest\|BadgeScopeTest\|ScopesNavigationBadgesTest\|UserTest"` | **112 passed / 112, 193 assertions, 0 failed** |
| Exporter file | `test -e app/Filament/Exports/ProductExporter.php` | absent |
| Exporter directory | `test -d app/Filament/Exports` | **absent** (`app/Filament/` holds only `Pages/ Resources/ Support/ Widgets/`) |
| Exporter references | repo-wide `Grep "ProductExporter"` | only the guard test + docs/report prose + `.patches/RUNLOG.md` |
| Phantom test classes | `Glob **/{RepositoryMapTest,RuntimeCompletenessTest}*` | **no files** |
| Missing research scripts/docs | `ls docs/02-research/`, `Glob docs/00-project/ai/references/i18n-*` | 2 cited scripts and 2 cited reference docs **do not exist** |
| `lang/en` history | `git log --format='%h %ci %s' -- lang/en/` | last touched by `aa54af6` **2026-10-24 04:51:20 +0000** |
| Research-doc history | `git log … -- docs/02-research/2026-10-23-i18n-count-verification.md` | last touched by `e1b5f15` **2026-10-24 10:38:22 +0800 (02:38 UTC)** |
| Reproduction-script history | `git log … -- docs/02-research/i18n-count-reproduction.php` | last touched by `e1b5f15` (02:38 UTC) |
| The `+1` leaf (delta proof) | `grep -c "=> '" lang/en/validation.php` vs `git show aa54af6^:…` | **140** vs **139** — exactly one added leaf |
| The `+1` key (identity) | `grep -n "'role'" lang/en/validation.php` + `git diff aa54af6^ aa54af6` | `:181 'role' =>`; diff adds `'invalid' => 'The selected role is invalid.'` |
| Policy-layer negated-helper scan | `Grep "isWarehouseStaff\|isAdmin\|…" app/Policies/` | exactly one negation: `DirectTransferPolicy.php:26`; plus `UserPolicy.php:61` |
| Native enum-cast semantics | read `HasAttributes.php:1326-1331` (`getEnumCaseFromValue`) | `$enumClass::from()` → `ValueError` on read **and** set — cast swap is behaviour-neutral |

Reproduced canonical breakdown (`lang/en`, 19 namespaces, loader rule):

```
actions 3, attributes 0, auth 3, common 33, dashboard 26, direct_transfers 63,
enums 46, errors 68, forms 0, navigation 6, notifications 11, pagination 2,
passwords 5, resources 551, stn 15, tables 0, validation 140, widgets 0, wizards 24
→ 996
```

---

## 2. Pass/fail matrix

| # | Requirement | Verdict | Basis |
|---|---|---|---|
| **#173** | One authoritative reproducible canonical count | **PASS — 996** | Reproduced twice at the pin, two independent paths (§1) |
| **#173** | Locale parity demonstrated under documented rules | **PASS** | Parity test green: `Missing 0`, `Orphan 0`, `Identity 0` |
| **#173** | Retired keys have documented disposition | **PASS** | 258 rows/locale, all `RETIRED`, self-attested total, justification per row |
| **#173** | 847 retired as a criterion; no residual resort to it | **PASS (docs)** | Recorded in `173-847-provenance.md` §4.1; **still live in the GitHub issue body** → §4 D-5 |
| **#173** | No residual 995/996 contradiction | **FAIL → resolved by this report** | §3.1 |
| **#205** | Malformed role cannot grant access (fail closed) | **PASS with one exception** | Cast/model/resolver all deny; **`DirectTransferPolicy::viewAny()` grants on malformed role** — §3.4, **DC-5** |
| **#205** | `UserRoleCast` vs `UserRole::class` is a hardening gap | **PASS — no gap** | Native cast also throws on read and set; behaviour-neutral (§3.2) |
| **#205** | Each component's invalid-role behaviour specified | **PASS (code + tests)** | Behaviour matrix §3.2 |
| **#205** | Tests verify the contract | **PASS (substance)** | 6 delivered tests; **cited names do not exist** → §3.2 |
| **#205** | Issue wording matches implementation | **FAIL** | GitHub body still says *resolver throws* and `UserRole::class` → §4 D-5 |
| **#207** | Exporter removed; no prohibited live refs | **PASS** | File absent, directory absent, zero executable refs |
| **#207** | Regression coverage protects the intended outcome | **PASS** | PHP-native 5-root sweep, self-excluding; 2/2 green |
| **#207** | Docs and issue wording reflect current architecture | **FAIL (issue only)** | Blueprint amended; GitHub body still names phantom classes → §4 D-5 |
| **Shared** | All results cite the same pinned revision | **PASS** | `9ac1856` throughout |
| **Shared** | No push / merge / issue edit | **PASS** | No remote ref touched; no GitHub write performed |

---

## 3. Disputed points, resolved

### 3.1 #173 — canonical count 995 vs 996, and 847 vs 258

**Resolved: 996 is authoritative. 995 is a stale snapshot, not a counting-rule difference.**

- The counting rule is **byte-identical** in both places that produce the number: the parity
  test's `keyParityFlatten`/`keyParityCatalogue` (`tests/Unit/Lang/TranslationKeyParityTest.php:59-107`)
  and the standalone reproduction (`docs/02-research/i18n-count-reproduction.php:9-40`). A
  rule difference therefore cannot produce a 1-key delta.
- At the pin, **both paths yield 996** (§1). The `995` figure appears only as prose and as a
  quoted "expected output" block in
  `docs/02-research/2026-10-23-i18n-count-verification.md:71,79,92,96,121,153`.
- That block is **internally contradicted by the same file**: §2 claims
  `es: total=865 placeholders`, while §6 (`:167-170`) states the #172 backfill has run and
  *"0 markers remain in either locale"* — and the parity test today reports `es 0`, `tl 0`.
  A block that asserts 865 placeholders inside a document that asserts 0 markers was not
  re-run; it is a stale 2026-10-23 quote.
- Wall-clock ordering confirms the drift direction: the reproduction script and that doc were
  last touched by `e1b5f15` at **02:38 UTC**, `lang/en` was last touched by `aa54af6` at
  **04:51 UTC** on 2026-10-24. The 995 snapshot predates the `lang/en` change.
- **OBSERVED — the `+1` is a new `validation.role.*` block added by `aa54af6`:**
  - `git show aa54af6^:lang/en/validation.php | grep -c "=> '"` → **139**;
    `grep -c "=> '" lang/en/validation.php` → **140**. Delta = **exactly one leaf**.
  - The added leaf sits under a new top-level block: `lang/en/validation.php:181` now has
    `'role' =>`, and `aa54af6`'s diff adds
    `+ 'invalid' => 'The selected role is invalid.',` — i.e. the canonical key
    **`validation.role.invalid`**.
  - `aa54af6 --stat` touches exactly one `lang/en` file (`validation.php`); the rest of that
    commit's lang changes are `es`/`tl`.
  - Therefore `lang/en` was **995 at `aa54af6^`** and **996 after**. This is catalogue growth,
    **not** a counting-rule difference — confirming §3.1's reasoning by direct key diff rather
    than by inference. Independently reproduced by the #173 investigation pass.

**847:** retired as a criterion. It is a pre-catalogue planning estimate
(`docs/00-project/blueprint.md:49`, authored two days before the catalogues existed) and is
not reproducible against the committed files. The artifact-backed figure is **258 per locale**
(`forms` 140 + `tables` 83 + `widgets` 35), all `RETIRED`. The issue's `wc -l → 0`
verification command was wrong by construction: the sidecars are a *disposition record*, not a
to-be-emptied enumeration, so they stay at 272 lines / 258 rows.

### 3.2 #205 — does an invalid role throw, or resolve to an empty scope?

**Resolved per component. The implementation is correct; the AC's `throws` wording for the
resolver is the stale half.**

Contract as implemented at the pin:

| Path | Valid role | Out-of-domain persisted value | Out-of-domain supplied value |
|---|---|---|---|
| `UserRoleCast::set()` (`app/Casts/UserRoleCast.php:58-76`) | stores `->value` | — | throws `InvalidArgumentException` |
| `UserRoleCast::get()` (`:29-55`) | returns `UserRole` | throws `ValueError` (+ deduped `invalid_user_role` warning) | — |
| `User::canAccessPanel()` (`app/Models/User.php:75-86`) | `role instanceof UserRole` | catches `ValueError` → **`false`** | — |
| `isAdmin/isAuditor/isWarehouseStaff/isBranchManager()` (`:88-122`) | exact identity compare | catch `ValueError` → **`false`** | — |
| `hasAccessToWarehouse()` (`:132-146`) | role + pivot check | catch `ValueError` → **`false`** | — |
| `ScopesNavigationBadges::badgeScopedWarehouseIds()` (`app/Filament/Support/Concerns/ScopesNavigationBadges.php:120-140`) | Admin/Auditor → all; Staff/BM → assigned | helpers return `false` → **`default => []`** | — |

- The cast **does** throw on read — the AC bullet `Reading an out-of-domain value from the DB
  throws on access` is **true**.
- The resolver **does not** throw: its `match (true)` chain calls only the catching `is*()`
  helpers, so no `ValueError` reaches the resolver frame. It returns `[]`. That is the
  owner-mandated **D1** contract (`STATUS.md:102-103`, `205-issue-text-amendment.md:13-23`), and
  D1 outranks the AC under the authority order. **Changing the code to throw would be a
  regression against a recorded owner decision** — not done.
- `User::$casts['role']` is **`UserRoleCast::class`** (`app/Models/User.php:61`), not
  `UserRole::class` as the AC states. D-F amended CD-5 to accept `UserRoleCast` as compliant
  (`2026-10-27-issue-audit-closure.md:317-324`, `STATUS.md:40-41`).
- **Cited test names do not exist.** `BadgeScopeTest::out_of_domain_role_throws_on_read`,
  `UserModelTest::role_cast_throws_on_invalid_value` and
  `ScopesNavigationBadgesTest::resolver_fails_closed_for_unknown_role` — grep across `tests/`
  returns 0 hits. The contracts are delivered by:

  | Cited (absent) | Delivered |
  |---|---|
  | `role_cast_throws_on_invalid_value` | `tests/Unit/Models/UserTest.php:544` `rejects invalid role assignment on set`; `:519` `fails closed when role attribute contains an invalid value` |
  | `out_of_domain_role_throws_on_read` | `tests/Unit/Models/UserTest.php:552` `denies warehouse access to a user with an invalid role even if assigned in pivot` |
  | — (no cited equivalent) | `tests/Unit/Models/UserTest.php:581` `rejects invalid role assignment through the model set path` |
  | `resolver_fails_closed_for_unknown_role` | `tests/Unit/Listeners/Filament/Support/Concerns/ScopesNavigationBadgesTest.php:233` `fails closed for an out-of-domain role rather than widening`; `:289` `hasBadgeScope fails closed for an out-of-domain role` |

**Fail-closed confirmed for every path named above.** The permissive branches
(`isAdmin() || isAuditor()`) require an exact enum identity and are guarded by the catching
helpers; a malformed value cannot reach them. **One exception exists outside these paths — see
§3.4**, which is the single place a malformed role *does* grant something.

**The `UserRoleCast` vs native `UserRole::class` question is behaviourally neutral.** Laravel's
native enum cast resolves through `getEnumCaseFromValue()`
(`vendor/laravel/framework/src/Illuminate/Database/Eloquent/Concerns/HasAttributes.php:1326-1331`)
using `$enumClass::from($value)`, which raises `ValueError` on both the read and the set path.
So switching `User::$casts['role']` to `UserRole::class` would **not** make the system more
fail-closed — it would still throw, and the same model-level catches would still be required.
The current custom cast is therefore not a weakening; it adds the deduped
`invalid_user_role` warning that the native cast lacks. **No change is warranted for this
bullet either**; the AC's `UserRole::class` expectation is a stale description, not a
hardening requirement.

**LOW precision note (no change made).** The issue states the `default => []` branch is
"only reachable if a future `UserRole` case lacks a matching tier". Strictly, it is also
reachable when the `role` attribute is absent (an unpersisted `User`): `null === UserRole::Admin`
is `false` without throwing, so all four helpers return `false` and the chain falls through to
`default`. The `users.role` column is `NOT NULL DEFAULT 'warehouse_staff'`
(`database/migrations/2026_10_15_085800_add_role_columns_to_users_table.php:16`), so this is
unreachable for any persisted user. The behaviour is correct and fail-closed either way; only
the "only reachable" phrasing is imprecise. Recorded rather than rewritten — the resolver's own
comment already documents the branch as defensive.

### 3.3 #207 — is removal real, and does the regression test protect the right thing?

**Resolved: removal is real, and the test protects the actual requirement.**

- `app/Filament/Exports/ProductExporter.php` **absent**; `app/Filament/Exports/` directory
  **absent** (`app/Filament/` contains only `Pages/ Resources/ Support/ Widgets/`).
- **Path-history precision (audit-trail hardening).** After a fresh
  `git fetch --all --prune` (2026-10-28 08:01:15 +0800, `origin` =
  `https://github.com/allanpusta13/sinop.git`, 65 refs), `git log --all --full-history --
  app/Filament/Exports/` returns **no commits**, and `--diff-filter=D` is empty. So the claim is
  **"absent from all fetched history"**, not a global "never committed" — `--all` sees only refs
  present in this clone and cannot speak for deleted unmerged branches. **No deletion event
  occurred**, so the issue's "Remove ProductExporter" title describes the requirement, not a
  recorded removal.
- Reference classification — **zero EXECUTABLE references**. Every hit is either
  (b) the guard test that asserts absence, or (c) historical prose:
  - (b) `tests/Feature/Architecture/ProductExporterDeletionTest.php`
  - (c) `docs/00-project/blueprint.md` (struck through at `:20051`, checklist at `:20103`,
    change table `:20251`), `docs/01-issues/207-summary.md`, `docs/01-issues/207-issue-text-amendment.md`,
    the audit/closure/close-out reports, `REMEDIATION_REPORT.txt`,
    `REVIEW_COUNCIL_REMEDIATION_REPORT.txt`, `FINAL_*_REPORT.md`, `TARGETED_CORRECTION_REPORT.md`,
    `REPORT_CONVENTION.txt`, `.patches/RUNLOG.md`
- The regression test asserts the **actual** outcome, not stale naming: it checks class and
  file non-existence and then runs a **PHP-native recursive scan** across the five roots the
  issue names (`app`, `tests`, `config`, `database`, `resources`), self-excluding by filename
  (`ProductExporterDeletionTest.php:18-75`). The shell-`grep` form was deliberately replaced
  because `2>/dev/null` broke under `cmd.exe` and produced a silent vacuous pass (`:29-32`).
  This is a *stronger* guard than the AC's literal `grep` command.
- **Stale AC:** `RepositoryMapTest::product_exporter_does_not_exist` and
  `RuntimeCompletenessTest::no_reference_to_product_exporter` are **phantom citations** — no
  such files exist anywhere in the repo. D4 = (b) directed naming the delivered test instead of
  creating them (`207-issue-text-amendment.md:1-52`); an approved plan
  (`docs/00-project/ai/plans/approved/2026-10-16-layout-invariant-remediation.md:71`) had already
  rejected creating tests to make a citation true. **Not created.**

### 3.4 NEW FINDING — a malformed role *does* grant one permission (§205 contract)

**This is the only verified place where the #205 contract "malformed role data must never grant
permissions" is breached.** It is pre-existing, not introduced by the G1 change set, and it is
**not** fixed here — changing authorization behaviour is an owner decision.

`app/Policies/DirectTransferPolicy.php:21-27`:

```php
public function viewAny(User $user): bool
{
    // Q18 — WarehouseStaff holds no direct-transfer ability at all,
    // so the resource is hidden from its navigation and list. Filament
    // drops a resource from navigation when `viewAny` is denied.
    return ! $user->isWarehouseStaff();
}
```

- Every other policy in `app/Policies/` composes **positive** predicates
  (`isAdmin()`, `isAuditor()`, `isBranchManager() || ...`), and every `is*()` helper returns
  `false` on a malformed role. `false` in a positive predicate denies. Fail-closed.
- `viewAny()` **negates** the helper. A malformed persisted role makes `isWarehouseStaff()`
  return `false`, so `! false` → **`true`**: the actor is granted `viewAny` on DirectTransfers.
  A corrupt role is treated as "not warehouse staff", which is not the same statement as
  "holds a valid non-staff role".
- **Blast radius is bounded.** `view()` (`:29-38`) still requires
  `isAdmin() || isAuditor() || (isBranchManager() && ...)`, so a malformed role yields an
  **empty list, not access to records.** The widened permission is navigation/list visibility,
  not row access.
- **Reachability requires out-of-band data.** `users.role` is
  `NOT NULL DEFAULT 'warehouse_staff'` and `UserRoleCast::set()` throws on any non-enum value,
  so the application cannot write a malformed role. Reaching this needs direct DB manipulation
  or a bypassed write path. Severity therefore **LOW–MEDIUM (defence-in-depth gap)**, not an
  exploitable vulnerability.
**DC-5(a) — the helper returns `false`, it does not throw. The fail-open is real.**

The finding hinges on which one `isWarehouseStaff()` does on a malformed role.
`app/Models/User.php:106-113`:

```php
public function isWarehouseStaff(): bool
{
    try {
        return $this->role === UserRole::WarehouseStaff;
    } catch (ValueError $e) {
        return false;            // :111
    }
}
```

It **catches `ValueError` and returns `false`** — it does not rethrow, and the return type is
`bool`. So `! $user->isWarehouseStaff()` evaluates to `true` and `viewAny` is granted. Had the
helper thrown, `!` would never be reached and the request would fail closed with a 500 instead.
**DC-5 is confirmed as a fail-open, not an inconsistent helper contract.**

**Complete negation inventory (repo-wide, `app/`).** Exactly five negations of a role helper
exist, and polarity position decides fail-open vs fail-closed:

| Site | Form | Malformed role | Verdict |
|---|---|---|---|
| `Policies/DirectTransferPolicy.php:26` | **`return ! helper()`** | `! false` → **`true`** | **FAIL-OPEN** |
| `Policies/UserPolicy.php:57` | `if (! helper()) return false;` | enters guard → **`false`** | fail-closed |
| `Policies/UserPolicy.php:61` | `if (! helper()) return false;` | enters guard → **`false`** | fail-closed |
| `Policies/WarehousePolicy.php:54` | `if (! helper()) return false;` | enters guard → **`false`** | fail-closed |
| `Services/InventoryService.php:215`, `:880` | `if (! helper()) { … }` | enters block, then requires pivot membership | see below |

**DC-5(b) — `UserPolicy.php:61` is NOT a second fail-open.** It is a guard-clause negation:
`if (! $target->isWarehouseStaff()) { return false; }` (`:61-63`), reached only after
`isAdmin()` (`:53`) and `! isBranchManager()` (`:57`) have both been evaluated. A malformed
*target* role makes the helper `false`, so the condition is **true** and the method returns
`false` — **denied**. Same for a malformed *actor* role at `:57`, which also returns `false`.
Blast radius is the `reassignWarehouses` ability on a target user, and it denies.
The textual shape is identical; the **polarity position is inverted**, and that is the whole
difference.

**One further inconsistency, weaker than DC-5 (flagged, not fixed).** `InventoryService.php:215`
and `:880` deny a non-Admin actor unless the warehouse is in its pivot set, and explicitly deny
Auditors. A malformed role is neither Admin nor Auditor, so it falls to the pivot test — meaning
a corrupt-role user **with pivot rows** is treated as warehouse staff. This still requires
explicit pivot assignment, so nothing is widened beyond the actor's assigned warehouses; but it
**disagrees with `User::hasAccessToWarehouse()` (`:132-146`), which denies a malformed role
outright.** Two scope mechanisms give different answers for the same corrupt input. Not
reachable without out-of-band data; recorded so the follow-up issue covers the family, not one
line.

**Why this is deferred, not fixed (owner-directed).** The owner ruled that DC-5 must **not** be
remediated inside G1: it is pre-existing, outside this change set, and folding an authorization
change into a docs-only reconciliation would contaminate the evidence trail for a
security-relevant change. It is tracked as **issue #240** — see **DC-5**.
**G1 changes no authorization code.**

**A near-miss worth recording.** The first draft of the #205 amendment asserted the resolver
*"never widens to another tier"*. Owner review rejected it **before paste**, because this section
proves a policy-layer widening exists: pasting that claim while this report documents the
opposite would have re-created the exact AC-vs-evidence contradiction G1 exists to remove. The
bullet is now scoped to the cast/model/resolver boundary, with the policy layer scoped out
explicitly and linked to #240 (§3.2 / `205-issue-text-amendment.md` §1). This is the strongest
argument for the "surface it, don't fix it" discipline: the finding changed the wording of the
very issue that produced it.

**No authorization change was made. The `[]` resolver contract, the throwing cast and the
catching helpers are all untouched.**

---

## 4. Discrepancy register and disposition

| ID | Discrepancy | Class | Disposition |
|---|---|---|---|
| **D-1** | `docs/01-issues/173-847-provenance.md:84-87` — "The canonical count is still open… D3 defers resolution until the loader can run" | **stale report** (now false) | **Corrected** — loader ran at the pin; 996 |
| **D-2** | `docs/01-issues/173-issue-text-amendment.md:25-27,58` — "loader re-derivation pending Bash recovery per D3" | **stale report** (now false) | **Corrected** |
| **D-3** | `docs/02-research/2026-10-23-i18n-count-verification.md:71,79,92,96,121,153` — 995, and `es: total=865 placeholders` | stale snapshot, self-contradicted by its own §6 | **Errata recorded here; historical doc not rewritten** (R-6/R-7/R-8 precedent: dated artifacts stay historical) |
| **D-4** | Same doc `:72-73,172-173` cites 2 scripts and 2 reference docs that **do not exist** | **stale citation / reproducibility gap** | **Errata recorded here**; the surviving `i18n-count-reproduction.php` + the parity test do reproduce every count |
| **D-5** | GitHub #173 body (847 criterion, `wc -l → 0`), #205 body (*resolver throws*, `UserRole::class`), #207 body (phantom test classes) | **stale AC** | **Amendment sheets already prepared, NOT pasted. Blocked on GitHub authorization** — see §5 |
| **D-6** | `FINAL_EXECUTION_REPORT.md:67` — "loader re-derivation pending Bash recovery per D3" | stale report | **Errata recorded here**; owner decision required before editing a root-level report |
| **D-7** | `docs/01-issues/report-audit-2026-10-25.md:32,38,54-57` — 1070 / 997 | pre-disposition states, already annotated superseded **in that same file** | No action — self-annotated |
| **D-8** | `ScopesNavigationBadges` `default => []` "only reachable…" phrasing in #205 | LOW precision | Recorded, no change (§3.2) |
| **D-9** | `storage/logs/i18n-parity.header.md` attests head `6c736538` while tests changed at `225a8df`/`3b405b8` | R-6, carried | Re-attestation needs a real CI run; **values not invented** |
| **D-10** | `app/Policies/DirectTransferPolicy.php:26` — `return ! $user->isWarehouseStaff();` grants `viewAny` to a malformed role; helper returns `false` at `User.php:111` | **NEW — fail-open in authorization** (pre-existing; outside the three issues' AC) | **Confirmed, NOT fixed, NOT a G1 blocker.** Owner-deferred to a follow-up issue — §3.4 / **DC-5** |
| **D-11** | `app/Policies/UserPolicy.php:61` — same textual pattern on a *target* user | **CLEARED** — guard-clause polarity, returns `false` | Fail-closed; **no defect**. Polarity position is the deciding factor (§3.4) |
| **D-12** | `app/Services/InventoryService.php:215,:880` — malformed role falls to the pivot test instead of being denied, disagreeing with `User::hasAccessToWarehouse()` | NEW — weaker than D-10 (requires pivot rows) | **Flagged for the follow-up issue**, not fixed |

### Code / test changes made: **none**

No code or test defect was found in any of the three issues. Under the G1 decision rule —
*do not change a safe fail-closed implementation solely to satisfy an outdated AC* — the
resolver stays fail-closed with `[]`, the cast stays `UserRoleCast`, and the exporter
regression test stays as delivered.

---

## 5. Decisions required from the owner

**DC-1 (#173).** The amended issue text in `173-issue-text-amendment.md` still says the count is
"996 as cited; loader re-derivation pending". It should now read: canonical `en` = **996**,
**loader-verified at `9ac1856`** by two independent paths; `995` was the pre-`aa54af6`
snapshot; D3/R-4 closed. Locally corrected in the amendment sheet (D-2). Pasting to GitHub
needs authorization.

**DC-2 (GitHub writes, #173 / #205 / #207).** Three amendment sheets are ready and unpasted
(`gh` was unavailable when they were written). Per the G1 hard constraints and `CLAUDE.md`, **no
issue edit, push or merge was performed.** Pasting requires explicit authorization. The sheets:
`173-issue-text-amendment.md`, `205-issue-text-amendment.md`, `207-issue-text-amendment.md`.

**DC-3 (D-3/D-4/D-6).** Editing the historical research doc and the root-level execution report,
or leaving them with errata. Precedent (R-6/R-7/R-8) is errata-only for dated artifacts; this
report follows that.

**DC-5 — RESOLVED BY OWNER: deferred to a separate follow-up issue; NOT remediated in G1.**
See §3.4 for the confirmed analysis. Status: **out-of-scope finding surfaced by the #205
investigation, tracked separately — not a G1 blocker.** Analysis is complete to the standard the
owner asked for: DC-5(a) confirms the helper returns `false` rather than throwing (so the
fail-open is real, not a helper-contract inconsistency); DC-5(b) clears `UserPolicy.php:61` as
fail-closed and shows the distinguishing factor is polarity position
(`return ! helper()` vs `if (! helper()) return false;`). The follow-up issue must state the fix
direction as **(c)**: replace deny-by-negation with **positive-predicate composition matching
the other policies**, or an explicit `hasValidRole()` guard — explicitly **not** "flip the `!`",
which only relocates the failure mode. It must also cover the `InventoryService.php:215/:880`
inconsistency (§3.4), since fixing one negated predicate while leaving the family uninspected is
the half-measure to avoid. The follow-up issue text is drafted for sign-off alongside the three
amendments.

**DC-4 (#173 native-speaker review, carried).** `2026-10-23-i18n-count-verification.md:167-170`
records that the #172 backfill is **AI-assisted and not reviewed by a native speaker**. That
requirement is tracked on #172 and is **not** satisfied by this report.

---

## 6. Residual risks

- **R-A (INFO).** `storage/logs/i18n-parity.md` cites its own regenerated line numbers; R-8
  deferred the stable-anchor convention change. Unchanged.
- **R-B (INFO).** `storage/logs/i18n-parity.header.md` is factually stale (D-9). Cannot be
  re-attested locally.
- **R-C — CLOSED.** The #173 `+1` is pinpointed: **`validation.role.invalid`**, added by
  `aa54af6` (§3.1). No longer inferred.
- **R-E (MEDIUM, NEW, OWNER-TRACKED).** The `DirectTransferPolicy::viewAny()` fail-open (§3.4) is
  unremediated by owner decision and is tracked in **#240**. Bounded to
  list/navigation visibility; unreachable without out-of-band data; the only verified breach of
  #205's "malformed role must never grant" contract. **Does not block G1.**
- **R-F (INFO).** `InventoryService.php:215,:880` treats a malformed role as staff-like, requiring
  pivot membership rather than denying as `hasAccessToWarehouse()` does (§3.4). Same follow-up issue.
- **R-D (INFO).** `#210–#217` remain `BLOCKED-gap` on product decisions and `#218` is
  `UNABLE-TO-MEASURE` locally. Outside G1's scope; unchanged.

---

## 7. G1 close-out

| Completion criterion | Status |
|---|---|
| #173 canonical count + parity verified; retired-key disposition documented; reports reconciled | **Met** — 996 loader-verified; 258/locale dispositioned; D-1/D-2 corrected, D-3/D-4 recorded |
| #205 invalid-role behaviour explicitly defined; implementation + tests match; authorization fails closed | **Met for cast/model/resolver** — matrix §3.2; 6 delivered tests green; **one policy-layer fail-open found and escalated (§3.4, DC-5) — outside the issue's stated component scope** |
| #207 exporter removal verified; prohibited references absent; regression coverage valid | **Met** — file + directory absent; zero executable refs; stronger-than-AC guard, 2/2 green |
| Shared evidence cites one pinned revision and is reproducible | **Met** — `9ac1856`, commands recorded in §1 |
| Acceptance criteria and status accurately reflect the verified result | **Met locally; GitHub body updates pending authorization** (DC-2) |
| Only authorized files modified; no push or merge without authorization | **Met** — documentation only; no push, no PR, no issue edit |

**Corroboration.** Three independent read-only investigation passes were run in parallel
(#173, #205, #207) by separate agents at the same pinned revision. Their findings agree with the
direct verification in §1 on every point, and two of them contributed evidence this pass then
re-verified itself: the identity of the `+1` key (OBSERVED, §3.1) and the
`DirectTransferPolicy::viewAny()` fail-open (§3.4). Their conclusions were **not** taken on
trust — each was independently reproduced before being recorded here.

**G1 sign-off is independent of DC-5.** The three issues' acceptance criteria are reconciled by
this report. The owner has ruled that the pre-existing policy fail-open is **not** remediated
here and is tracked in a **separate follow-up issue** — the correct outcome for a finding
*surfaced by* the #205 investigation. It is an out-of-scope tracked finding, **not a G1
blocker**, and must not be silently folded into the three issues' closure.

---

## 8. Verification provenance and its limits

**Tool path.** Every check in §1 ran through the **CLI** — `git`, `php docs/02-research/…`,
`php artisan test`, and repo/grep scans — with no step dependent on an MCP server.

**MCP unavailability, and whether it degraded any check.** `laravel-boost` and `ide` both failed
to connect this session (`laravel-boost`: connection not established; `ide`: `ECONNREFUSED` on
`ws://127.0.0.1:51642`), and `plugin:discord:discord` closed. The only Boost capabilities this
workstream would have used are `database-query` / `database-schema` and `search-docs`. **None
were needed, and none were skipped:** the three issues are reconciled entirely from the committed
catalogue files, the test suite's own generated report, policy/model/cast source, and git
history. No database read informed any verdict — §3.2's runtime behaviour is derived from source
plus the passing test suite, not from a live query. **Verification was CLI-only by necessity, not
by omission**, and no verdict rests on an unperformed tool call.

**Subagent discipline.** Three parallel read-only investigation passes (#173, #205, #207) ran at
the same pin. Their reports arrived only after the sandbox classifier recovered, and **no claim
was accepted on delivery**: the `+1` key identity and the `DirectTransferPolicy` fail-open were
each independently reproduced here before being recorded (§1, §3.1, §3.4). Where a subagent
asserted something this pass could not reproduce, it is labelled as flagged-for-review rather
than stated as fact — `UserPolicy.php:61` was reported as a suspected second fail-open and is
recorded above as **cleared**, not confirmed.

**Sandbox limitation.** The classifier rejected tool calls intermittently through this session.
It did not block any conclusion in this report; the one item it delayed — the per-namespace
identity of the `+1` key — is now **OBSERVED** via direct key diff (§3.1, R-C closed).

---

## 9. Appendix — DC-5 follow-up issue (FILED as #240, 2026-10-28)

**Filed:** https://github.com/allanpusta13/sinop/issues/240. The text below is the filed body,
kept here so the report and the issue cannot drift.

**Title:** `Fail-open: malformed role grants viewAny via negated predicate in DirectTransferPolicy`

**Body:**

> ## Summary
>
> A malformed persisted `users.role` value **grants** one permission instead of denying it.
> Every other authorization surface composes positive predicates, where a malformed role makes
> the helper return `false` and therefore denies. `DirectTransferPolicy::viewAny()` **negates**
> the helper, inverting the outcome.
>
> Surfaced by the G1 evidence/AC reconciliation pass (`9ac1856`); deliberately **not** fixed
> there. Pre-existing, not introduced by that change set.
>
> ## Reproduction
>
> ```php
> $user = User::factory()->create();
> $user->role = 'auditor';      // any of the four valid values; then corrupt out-of-band
> // simulate corruption without the throwing cast:
> DB::table('users')->where('id', $user->id)->update(['role' => 'not_a_role']);
> $user->refresh();
>
> $user->isWarehouseStaff();                 // false  (User.php:106-113 catches ValueError, :111)
> (new DirectTransferPolicy)->viewAny($user); // TRUE  <-- fail-open
> ```
>
> Requires out-of-band corruption: `users.role` is `NOT NULL DEFAULT 'warehouse_staff'` and
> `UserRoleCast::set()` throws on any non-enum value, so the application cannot write a bad role.
>
> ## Blast radius
>
> **Bounded — list/navigation visibility, not row access.** `view()` (`:29-38`) still requires
> `isAdmin() || isAuditor() || (isBranchManager() && both endpoints in set)`, so a corrupt-role
> actor sees an **empty list**. What is wrongly granted is `viewAny`, which is what Filament uses
> to register the resource in navigation.
>
> ## Sites in scope
>
> | Site | Form | Verdict |
> |---|---|---|
> | `Policies/DirectTransferPolicy.php:26` | **`return ! helper()`** | **FAIL-OPEN** |
> | `Policies/UserPolicy.php:57`, `:61` | `if (! helper()) return false;` | fail-closed — checked |
> | `Policies/WarehousePolicy.php:54` | `if (! helper()) return false;` | fail-closed — checked |
> | `Services/InventoryService.php:215`, `:880` | `if (! helper()) { … }` | inconsistent — a malformed role falls through to the pivot test and is **permitted if assigned**, disagreeing with `User::hasAccessToWarehouse()` (`:132-146`), which denies |
>
> ## Fix direction
>
> Replace **deny-by-negation** with **positive-predicate composition matching the other
> policies** — or an explicit `hasValidRole()` guard. **Not** "flip the `!`": that only relocates
> the failure mode, leaving a malformed role defined by what it is not.
>
> The `InventoryService` inconsistency must be settled in the same change, so the codebase has
> one answer for "what does an unparseable role mean?", not two.
>
> ## Acceptance criteria
>
> - [ ] No authorization decision in `app/` grants a permission on a malformed role.
> - [ ] A mutation test proves the deny: corrupt the role out-of-band, assert `viewAny` is
>       `false` (red under the current code, green after the fix).
> - [ ] `InventoryService` and `User::hasAccessToWarehouse()` agree on malformed-role behaviour.
>
> ## Related
>
> #205 (invalid-role contract). Analysis: `docs/01-issues/2026-10-28-g1-evidence-ac-reconciliation.md` §3.4.
