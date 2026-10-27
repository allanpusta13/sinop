# Status

## Current workstream — issue audit CLOSED (2026-10-27)

Closure report: [docs/01-issues/2026-10-27-issue-audit-closure.md](docs/01-issues/2026-10-27-issue-audit-closure.md)
Audit report (read-only, unmodified): [docs/01-issues/2026-10-26-issue-audit-173-218.md](docs/01-issues/2026-10-26-issue-audit-173-218.md)

- **§Prerequisite discharged.** The classifier that blocked the 2026-10-26 run recovered. `git`,
  `php artisan test`, `vendor/bin/pest`, `vendor/bin/phpstan analyse` and `composer show` all
  executed. No HALT required; no verdict rests on inference.
- **Green tails (D-A satisfied):**
  - Pest full suite `passed` — baseline **2107 / 2103 passed / 4 skipped / 4813 assertions** →
    final **2108 / 2104 / 4 skipped / 4817 assertions**. +1 test, +1 passed, +4 assertions,
    **0 new failures**.
  - PHPStan level 7 — **365 → 365**, zero drift. Note: not zero in absolute terms; 365
    pre-existing errors are unbaselined project debt. "Green vs baseline" = non-regression.
- **Three authorised edits applied, each independently verified:**
  - **D-C** — `docs/01-issues/210-supplier-shipment-tracking.md:22` no longer recommends
    auto-backorder tracking; now defers to #214 (DO-NOT-IMPLEMENT). Prose only, 1 line.
  - **D-D** — `TranslationKeyParityTest` gained an additive zero-catalogue-orphans assertion.
    **Mutation-proven red→green**: injected a probe orphan into `lang/es/common.php` → RED naming
    `common.__dd_orphan_probe` (996 canonical / 0 missing / 1 orphan); reverted → GREEN. Probe
    never committed.
  - **D-G** — `UserTest.php` gained `it('rejects invalid role assignment through the model set
    path')`, guarding the model-level assign+save contract that was previously unguarded
    (only the cast-level `set()` was covered). Owner-authorised explicitly; the sole test edit
    besides D-D's assertion.
- **D-G's "resolver throw" target was NOT implemented — resolved in favour of D1.** D1
  (owner-approved) requires the resolver to fail closed with `[]`; the AC demanded a throw. D1
  wins under the authority order. The `[]` contract is already guarded by
  `ScopesNavigationBadgesTest.php:233`.
- **D-H — no fail-open regression.** `app/Models/User.php:81-85` catches `ValueError` and returns
  `false`, i.e. **denies**. The catch does not fall through to a default or widened role.
- **Verdicts:** #173 **`CLOSED` (owner-signed 2026-10-26)**; #205 `SATISFIED` — evidence gap
  closed; #207 `SATISFIED` — re-proved (sweep of `app`/`config`/`database`/`resources`/`routes`:
  zero refs; glob finds only the guard test); #214 `EXCLUDED`; #218 unchanged
  (`TRIGGER-NOT-MET` local / `UNABLE-TO-MEASURE` prod); #210–#213, #215–#217 **`BLOCKED-gap`** —
  no §0.3 owner decisions supplied, so the blocks persist.
- **Recorded, not changed:** D-E test-name mapping table (the three #205 cited test names do not
  exist on disk; mapped to the tests that do deliver each contract); D-F CD-5 amended to accept
  `UserRoleCast` as compliant (matches `User.php:61`).
- **R-1 RESOLVED (option A).** The 13 hand-maintained CI-provenance lines (Head SHA, CI Run URL,
  runner summary, fixed verification clause) were moved byte-for-byte out of the test-generated
  `storage/logs/i18n-parity.md` into `storage/logs/i18n-parity.header.md`, and
  `storage/logs/.gitignore` gained a negation so the header is tracked. The log is now purely a
  test artifact: `TranslationKeyParityTest` is untouched, a full-suite run no longer alters it,
  and rerunning leaves it byte-identical (verified by hash). The header lines were **not**
  contiguous in the original (2 after the generated title, 11 after the canonical-key block);
  extraction adapted accordingly.
- **R-7 RESOLVED (option A, default) — renumbering citation corrected; errata recorded for the
  read-only sites.** Removing the R-1 header renumbered the parity log: `Canonical keys` moved
  line 7 → 5, and Missing/Orphan/Identity moved 20–22 → 7–9. Four citation sites referenced the old
  numbers. The one editable site was fixed; the three inside read-only reports were **not** edited
  and are recorded here as errata instead (cited *content* unchanged and still correct — only the
  line numbers moved):
  | Site | Status | Cited | Correct now | Content |
  |---|---|---|---|---|
  | `docs/01-issues/173-847-provenance.md:86` | **fixed** | `i18n-parity.md:7` | `:5` | `Canonical keys: 996` |
  | `docs/01-issues/2026-10-26-issue-audit-173-218.md:52` | errata (READ-ONLY) | `i18n-parity.md:20-22` | `:7-9` | Missing / Orphan / Identity |
  | `docs/01-issues/2026-10-26-issue-audit-173-218.md:64` | errata (READ-ONLY) | `i18n-parity.md:20-22` | `:7-9` | Missing / Orphan / Identity |
  | `docs/01-issues/2026-10-27-issue-audit-closure.md:298` | errata (READ-ONLY) | `i18n-parity.md:7` | `:5` | `Canonical keys: 996` |
- **R-6 RESOLVED (option C, default) — header kept as historical; drift recorded here.**
  `storage/logs/i18n-parity.header.md` was **not** edited and still attests `6c736538`. It is
  historical, not current. Its fixed verification clause asserts `6c736538` "is the most recent
  commit that modified application code or tests" and that later commits are "documentation-only";
  D-D (`225a8df`) and D-G (`3b405b8`) both modified test files after it, so the clause is false for
  the current tree, and its cited runner counts (2,103 passed / 119.76s / 130.04s) no longer match
  the current 2,104 / 4 skipped / 4,817. Re-attestation needs a real CI run (head SHA, run URL,
  per-version timings) which this checkout cannot produce; the values were **not** invented.
  Verbatim drift note, as directed:
  > R-6: storage/logs/i18n-parity.header.md reflects CI generation at 6c736538. Drift since: D-D + D-G modified test files. Re-attestation pending next CI run. Values not invented locally.
- Lower-severity: R-2 `210-...md:25` still says received costs update a moving average, against
  the implemented snapshot/current-cost method (#212 territory, blocked); R-3 PHPStan 365
  pre-existing errors at level 7; R-4 #173 canonical 996-vs-995 still deferred under D3;
  R-5 pre-existing uncommitted changes in the tree are not part of this change set.
- **Committed, not pushed.** Branch `issue-audit-closure/2026-10-27` (off the audit branch at
  `39f9a79`), five atomic commits: `9ca4535` D-C, `225a8df` D-D, `3b405b8` D-G, `7593140` R-1, then
  the R-6/R-7 status records ("chore(R-6,R-7): record header staleness and parity-log citation
  errata", the branch tip). Non-regression re-proven on the committed tree: suite `passed`
  2108 / 2104 / 4 skipped / 4817 assertions (closure baseline unchanged), targeted 86 / 86 / 160, and
  PHPStan 365 → 365 — zero drift on all three. **No push, no PR** (CD-18); proved via `.git` refs —
  no remote ref for this branch, parent remote still `39f9a798`.
  Close-out report: [docs/01-issues/2026-10-27-issue-audit-closeout.md](docs/01-issues/2026-10-27-issue-audit-closeout.md)
  Final report: [docs/01-issues/2026-10-27-issue-audit-final.md](docs/01-issues/2026-10-27-issue-audit-final.md)

### Prior — audit run (2026-10-26)

- Blocked at the §Prerequisite: the permission classifier refused every new command all session,
  so the suite never ran and **no verdict was closed** in that run. Recorded in the audit's §5/§8.
- **Headline of that run:** the supplied acceptance criteria **contradicted four recorded owner
  decisions (D1–D4)** already applied on this branch. The recorded decision was applied in each
  case and the conflict flagged, not overwritten:
  - **D1** — owner rewrote #205 to require the resolver to fail closed with `[]`; the AC demanded
    it *throw*. Code NOT changed.
  - **D2/D3** — owner retired the 847 criterion; orphan sidecars are a *disposition record*, not
    to be emptied. Catalogue orphans 0; sidecar line count 272 by design.
  - **D4** — owner directed naming the delivered test; the AC's `RepositoryMapTest` /
    `RuntimeCompletenessTest` are phantom citations.
- No regression found then either: #212's implemented methodology is snapshot/current-cost
  replacement (`PurchaseService::updateCurrentCostPrice()`), not an unapproved weighted average.
  #213 has no half-built return workflow. #217's presets match the blueprint verbatim.
- Open: #167 has no artifact on disk (parity inferred from the green §0A.15 test).

## Done
- Aligned `tests/Unit/Policies/*` with the updated (v13.10 role model) policies.
  - PurchaseOrderPolicyTest, SalesOrderPolicyTest, TransferRequisitionPolicyTest, DirectTransferPolicyTest, ProductVariantPolicyTest.
  - Operational lifecycle abilities now asserted for BranchManager; WarehouseStaff asserted receive-only / read-only; DirectTransfer viewAny asserted false for WarehouseStaff.
- Restored the standalone Playwright E2E suite to green: **24 passed / 24**.
  - Locator drift fixed against Filament v5.9's real a11y tree: enhanced (searchable) Select trigger is a `<button>`, not `combobox`; row actions in an ActionGroup dropdown are `menuitem` (including `->url()` link actions), not `button`/`link`.
  - Root-cause fixes for the suite not running at all: missing `playwright.config.ts` restored; `.env.testing` repointed off the dev SQLite file to the MySQL test DB; admin theme added to `vite.config.js` inputs (panel 500'd with "Unable to locate file in Vite manifest").
- Made the suite runnable by the owner with no env vars: `npx playwright test`.
  - Bundled Chromium revision 1248 was missing; installed headless shell first (`npx playwright install chromium --only-shell`).
  - `playwright.config.ts` `baseURL` had drifted to the dev vhost `http://sinop.test` (dev DB) — restored to `http://127.0.0.1:8123` (managed test server).
  - `SERVER_PORT` now derives from `BASE_URL`, so the managed server's port and the client URL can never disagree.
  - `reuseExistingServer` is now opt-in (`PLAYWRIGHT_REUSE_SERVER=1`); two stale servers were being silently reused, causing a split-brain (browser served dev DB while the specs' `tinker()` read the test DB).
- Rewrote `tests/Browser/purchase-and-sales.spec.ts` (9, 10, 19) to drive the real Filament UI instead of calling services via `tinker()`.
  - Each now opens the row ActionGroup, fills the action modal, clicks Confirm, and asserts the success toast + DB state. Zero service calls from the specs.
  - Corrected a wrong premise in the old file: `TextInput::maxValue()` registers BOTH the HTML5 `max` attribute and a server-side `max:` rule ([TextInput.php:111](vendor/filament/forms/src/Components/TextInput.php:111)), so over-quantity never reaches the service guard — the browser refuses to submit (verified: `validity.rangeOverflow === true`, no POST issued, DB unchanged). The UI *is* a valid oracle.
  - Service-level guard contract stays covered by Pest (`GuardsOutstandingQuantityTest`, `ConcurrencyTest`, `PurchaseServiceTest`, `SalesServiceTest`) — no coverage lost.
  - Added an optional `stockQty` param to `makeConfirmedSalesOrder` so test 19 can seed on-hand == reserved (5/5) without bypassing the UI.
- Fixed `npx playwright test --ui` showing no tests: UI mode launches a **headed** browser, and only the headless shell (build 1248) was installed, so no full `chromium-1248` existed.
  - Installed the full build: `npx playwright install chromium` → `chromium-1248/chrome-win64/chrome.exe` present.
  - Verified: headed run passes; `--ui --ui-port=8899` boots clean ("Listening on http://localhost:8899", no error). UI mode still needs port 8123 free (a leftover `php artisan serve` blocks it — the config's intentional loud failure, not a silent wrong-DB reuse).

- Ran a read-only Playwright layout audit of every admin surface (41 routes × 1440/1280/768/375; index tables, card-grid lists, 6 create forms, 2 edit forms, infolist view pages, action modals). **No code changed — audit and report only.**
  - Full report: [docs/01-issues/2026-10-16-layout-audit.md](docs/01-issues/2026-10-16-layout-audit.md)
  - **Defect 1 (MEDIUM):** 9 of 10 infolist view pages render cards at 349/163/536 px inside a 1096 px content area and waste ~248 px on the right. Root cause proven by measured ancestor chains: the infolist root grid sits in a 536 px `.fi-grid-col`, not the full width. `UserInfolist.php:22` has `->columnSpanFull()` on the root `Grid`; the other 9 do not. Fix = one line each.
  - **Defect 2 (LOW):** `/admin/users` index needs 159 px of horizontal scroll (table 1255 in a 1096 wrapper) because three labelled row actions widen the actions cell to 334 px. Every other index list fits.
  - **Defect 3 (MEDIUM):** dashboard quick-action cards are 125×276 px at 1440 and 78×276 px at 768 — taller than wide, labels wrapping vertical. `QuickActionsWidget` declares `xl => 4` against a dashboard that only has 2 columns.
  - **Defect 4 (LOW):** two dashboard charts render at 1048×699 px, producing a ~3300 px page.
  - **Verified clean:** zero document-level horizontal overflow and zero console errors on all audited surfaces; forms lay out in even columns at 1440 and collapse correctly at 768; all 6 card-grid lists use 3/2/1 tracks correctly; the action modal is centred and correct; dashboard rows are even once widgets finish loading.
  - **Retracted after re-measurement** (were mid-load or mis-read artifacts, not bugs): "uneven infolist/dashboard rows"; "dashboard widgets hang at 768/375" (they lazy-load on scroll — skeletons 3–4 go to 0 once scrolled); "users table clipped" (it scrolls, `overflow-x: auto`).
  - **Not the cause of Defect 1:** the array-vs-integer `Grid::make()` form. The nested grids do get the correct track count; only the parent width is wrong.
- Ran the 5-seat council on all 4 audit findings. Strict recommendations only; **no fix applied, no file modified**.
  - Governing authority the audit never consulted: `docs/00-project/blueprint.md` — §7M.7 rule 2 (`:12823`), §7O.5 (`:12944`), §10 (`:14071`, `:14085`), §7N, F31 (`:1292`).
  - **Finding 1 (infolist width):** unanimous FIX WITH CHANGES. Framing inverted — the 9 files conform to §7M.7 rule 2; the blueprint's own canonical sample (`:8107`) omits the root span. `UserInfolist` is the outlier (violates the rule, only infolist with raw untranslated strings). Fix = add `->columnSpanFull()` **keeping** the array form, amend §7M.7 rule 2 + §7O.4 + §7O.8 #5, normalise `UserInfolist`. **Owner approval — convention change.**
  - **Finding 2 (users scroll):** majority REJECT as defect. Blueprint F31 forbids collapsing `EditAction` into an `ActionGroup`; `§7L.2` renders Users flat by design; auto-scroll is §7N behaviour. Real buried finding: `->modalWidth(Width::Large)` is dead config in **8 tables** (13 sites), not one. Separate cleanup task.
  - **Finding 3 (quick-action cards):** real defect, wrong direction. Root cause is `Dashboard::getColumns()` returning `xl => 2` against §7O.5's mandated `xl => 4` — **not** the widget span, which matches §10 and is pinned by `DashboardWidgetsTest.php:93`. Fix the Dashboard, leave widget + test. Add the blueprint-cited `ResponsiveSpanTest::dashboard_get_columns_returns_breakpoint_array()` — cited at blueprint `:15263`, **does not exist on disk**. **Owner approval — reflows all 9 widgets.**
  - **Finding 4 (charts):** DROP. Height is aspect-derived (`1096/699 ≈ 1.57`); likely a symptom of Finding 3. No objective acceptance threshold. `ChartWidget::$maxHeight` **does** exist (`ChartWidget.php:36,101`) — blueprint changelog `:20276` is wrong.
  - **Cross-cutting:** audit never read the blueprint; both MEDIUMs are really LOW; harness trust low (3 retractions, `report.json` missing, no 1280px data, "~248px" doesn't derive); no regression guard exists for any fix.
  - **Security seat:** `QuickActionsWidget::actions()` emits all four `Resource::getUrl('create')` unconditionally (`canView()` = `user !== null`). No authz bypass (server policy 403 on the page) but advertises create routes to roles that cannot use them. Optional hardening, separate.
  - **PII:** `.issue-tmp/layout-audit/` PNGs contain employee names/emails + financial figures (RA 10173). Must stay untracked; verify seed provenance before any sharing.
  - Recommended order if approved: **3 → re-measure 4 → 1 → 2-cleanup.**
- Council #2 on the owner's 12-column proposal ("set the columns to 12, resize each accordingly on all screen sizes"): **REJECT** — Product REJECT, Security MODIFY, Architecture REJECT, QA MODIFY (hard stop on "all screen sizes"), Skeptic REJECT. **No seat endorsed it. No code changed.**
  - **Verified vendor mechanism (decisive):** `ComponentAttributeBag.php:158-176` emits spans as CSS custom properties (`--col-span-{bp}: span N / span N`), not compiled classes; `CanSpanColumns.php:26-40` accepts any integer, no whitelist. So 12 buys **zero CSS/cascade/browser benefit** — PHP churn only. And 3/2/4/1 all divide 12, so every existing layout already maps exactly: **no new layout is producible.**
  - **Skeptic's framing correction:** the defects are **parent/child mismatches**, not resolution limits. Rescaling children while parent counts stay put reproduces the defect at larger spread. Right question = "does every span match its container's column count, and does every root grid own full width?"
  - **Root cause identified:** `QuickActionsWidget` `xl=>4` matches §10; it is `Dashboard::getColumns()` (`Dashboard.php:29-38`, `xl=>2`) that deviates from §7O.5 (`:12944`, `xl=>4`). Widening the Dashboard is one line and already a recorded deviation.
  - **Regression net does not exist.** `DashboardWidgetsTest.php:78-94` is the repo's only executable layout spec; it does **not** pin `Dashboard::getColumns()`. `blueprint.md:15263` cites `ResponsiveSpanTest::dashboard_get_columns_returns_breakpoint_array()` — **that test does not exist on disk**; the blueprint believed it was guarded, and was not. Zero tests reference infolist grids.
  - **QA blocker:** correctness unverifiable at *either* end. Before: audit retracted 3×, `report.json` missing, 1024/1280 never measured, all modal probes errored. After: class-level only (Pest has no layout engine). Both ends unverified.
  - **Blueprint errors (verified):** `:20276` falsely claims `->maxHeight()` was removed as non-existent (`ChartWidget.php:36,101` declares it); `:15263` phantom test; `:15510` contradicts §7M.7 r2.
  - **Step-count correction (observed, supersedes the earlier "9"):** infolist root grids are **DirectTransfers:21, InTransits:22, LossLedgers:18, Products:22, PurchaseOrders:21, SalesOrders:21, StockMovements:21, TransferRequisitions:21, Warehouses:24**; `UserInfolist.php:21` is the sole holder of root `columnSpanFull()`. LossLedgers (5 blocks) and StockMovements (2) would take more than one call; scope is **9 files**, not "9 lines".
  - **Plan written to `docs/00-project/ai/plans/pending/2026-10-16-layout-invariant-remediation.md`** (PENDING — owner approval required). Priority order: **P0** layout regression net (additive, no gate) → **P1** Dashboard cols per §7O.5 (owner; reflows all 9 widgets) → **P2** infolist root span + blueprint §7M.7 r2 amendment (owner) → **P3** `UserInfolist` normalisation → **P4** blueprint corrections → **P5** dead `modalWidth` → **P6** re-measure charts → **P7** PII cleanup → **P8** artifact deletion (destructive gate).
  - **QA's key point:** P0's new assertions must **fail** against current code before P1/P2 — that failure is the baseline proof the audit could not produce.
  - **Unresolved (Inferred, not Observed):** whether an over-count span clamps to full width (Product) or to one track (Architecture). Audit measurement of 536 px = one track backs Architecture. Does not change any verdict.
- Council #3 on the *plan itself* (revision 1 critiqued by all 5 seats as 5 separate agents): **Product MODIFY, Security MODIFY, Architecture MODIFY, QA MODIFY, Skeptic MODIFY. No seat accepted it.** Plan rewritten to revision 2. **No production file touched.**
  - **Product:** value order inverted — owner-visible fixes sat at P3/P6 behind plumbing; "regression net" oversells a declaration-checker. Fixes moved first; net renamed.
  - **Security:** PII capture was sequenced *before* the gate constraining it; also 2C bundled layout with i18n, violating the "keep layout diffs separable" control. Phase 0 PII precondition added; commits split A (layout) / B (i18n) with a diff-grep assertion (`visible(`, `hidden(`, `canView`, `can(`, `authorize`, `->query(`) as mechanism, not prose.
  - **Architecture:** the "child span ≤ parent track count" invariant is **unimplementable by the cited precedent** — `FormFieldSpanTest` inspects self-contained chains on one node via regex and does no nesting/scope resolution. Removed from the plan; a mismatch guard would need runtime or a hand-maintained `(surface, breakpoint) → parent tracks` table, as a separate proposal.
  - **QA:** no acceptance criteria (the exact thing QA criticised the audit for). Criteria block added per phase; the declaration-only limit is now stated in-body and in the test docblock; 1024 coverage cut as gold-plating (`lg` inherits `md=2` identically before/after); pixel baseline demoted from Phase 0 blocker to a one-time evidence artifact at 1280 + 1440, requiring screenshot+JSON pair, `deviceScaleFactor: 1`, animations off, seeded data, wait on `networkidle` AND `.fi-loading-section` count 0.
  - **Skeptic:** plan was downstream of an artifact whose evidence is absent. Gate 0.5 added — reconfirm each finding by direct source read, **retract rather than remediate** if it does not reproduce.
  - **Phantom-test finding corrected upward (verified this session):** blueprint `:15258-15271` cites **14** `ResponsiveSpanTest::*` methods; **zero exist**. Adjacent catalogue blocks are fiction too — `TableArchitectureTest` `:15273-15279`, `AllResourcesTest`/`AllActionsTest`/`AllFormFieldsTest` `:15249-15252`, `NavigationBadgeTest` `:15243-15248`, `RuntimeCompletenessTest` `:15234-15237`. Revision 1 had recorded **1** phantom method. (`WarehouseScopeTest`, `NavigationBadgeRoleScopeTest` do exist; cited correctly.) All 5 seats rejected revision 1's proposal to *create* `ResponsiveSpanTest` to make `:15263` true — backwards. Mark the blocks unbuilt instead.
  - **Reflow count corrected (verified this session): 8 of 9 widgets, not 9.** Widget xl spans vs parent xl: `4`×1, `2`×4, `1`×4. At parent 2 all 9 are full-width; at parent 4, `xl=4` (QuickActions) stays full (**unchanged**), the four `xl=2` shrink to half, the four `xl=1` shrink to quarter. QuickActions is the one widget the fix does not visibly move.
  - Additions: audit-doc correction banner (it recommends the rejected fix at `:147`), gate for the optional shared infolist root-grid helper (10 callsites = justified DRY, but a new convention needing approval), F31/UsersTable deferral now explicit rather than silent.
- Council #4 (binary verdict on plan revision 3): **ACCEPT ×4, REJECT ×1.** Every seat found a real defect; all applied. Plan is now revision 3-final. **No production file touched.**
  - **Product ACCEPT** — internal count error: scope said "**two**" doc corrections, task listed **four**. Cut `:20276` (`maxHeight`) and `:15510` (`Grid::make(3)`) as unrelated hygiene belonging to the dropped chart work. Confirmed payload: **10 lines** touching the defect (9 × `->columnSpanFull()` + 1 `getColumns()` array).
  - **Security ACCEPT** — diff-grep was declared a control but had no owner and no implementation. Now: named command, owned by the releasing developer, run pre-commit, not wired to CI; limits stated (a literal grep misses `Model::where(`, `DB::raw`, `Gate::` authz — it holds because A1/A2's scope cannot physically express authz). Added check: commit A2 must not alter `getWidgets()` membership (**verified this session** — `Dashboard.php:47-60`, a separate method from `getColumns()` at `:29-38`).
  - **Architecture REJECT** — cleared by one edit. Steps 2 and 3 shared commit A, defeating the revert isolation that was the point of splitting commits. Now **A1** (9 infolist roots; additive, view-only, lands first) and **A2** (dashboard reflow; owner-visible, sign-off gates it).
  - **QA ACCEPT** — sign-off must be a **dated written record** naming viewports and what resized, not a verbal pass. The pixel reflow is stated in AC#3 as an **inferred consequence** of the vendor span mechanism, **not a measured result**.
  - **Skeptic ACCEPT — most material finding of the round:** the net asserts the *declared string* `columnSpanFull()`, never the *rendered reflow*; owner sign-off covers the dashboard only. So the **9 infolist fixes would have shipped with zero rendered verification** — call insufficient on any root = net green, ~248px gap persists, undetected. Added **step 7**: one Playwright width assertion on one infolist view page (root width == content width), no screenshots, no PII, FAIL before / PASS after. Added **step 8**: confirm §7O.5 is not itself stale — **owner states the dashboard should be 4-column at `xl`**; if the current 2-column dashboard was intended, §7O.5 is wrong and Task 3 must not proceed. (`:12944` verified literal — but a correct-looking spec is not proof of a correct spec; that is a product call.)
  - Skeptic's real-change count: **~21 code lines** total (9 span adds, 1 array 5→3 keys, `UserInfolist` form + 10 translations, 1 test file with 2 assertions, 2 doc marks). Prose ~130 lines. The change is not inflated; the *prose* is — 4 council rounds.
  - **Skeptic was asked directly** whether the plan had been calibrated to its auditors rather than the code. Answered ACCEPT, named its falsifier. Further rounds have poor return; flagged in the plan.

## In progress
- **Step 1 + commit A1 EXECUTED 2026-10-17** on branch `fix/layout-invariant-a1` (off `develop`). Not committed, not pushed.
  - Plan moved to `docs/00-project/ai/plans/approved/2026-10-16-layout-invariant-remediation.md`; approval metadata filled (**partial approval: step 1 + A1 only**); `Infofist` typo fixed.
  - New net: `tests/Feature/Architecture/LayoutInvariantTest.php` (2 assertions, line parser, no framework boot).
  - A1: `->columnSpanFull()` added to the 9 infolist root grids. Array form retained. View-only.
  - **RED captured before any production edit:** 9 infolist violations + dashboard drift, exact list recorded (Products root is `:31`, not `:22` — the plan's `:22` pointed at the docblock). Dashboard failure: `+sm, +lg, xl 4→2`.
  - **Mutation-tested both assertions** (not just "run and see green"): widened dashboard drift (`xl` 2→3) **FAILS**; dropping one root's span **FAILS**. Both guards demonstrably detect their violation.
  - Evidence: focused net green (1 passed / 1 skipped); `tests/Feature/Architecture` + `tests/Feature/Filament/Widgets` → **258 tests, 257 passed, 1 skipped**; full suite → **2089 tests, 2085 passed, 4 skipped**; Pint clean; A1 authz diff-grep → **0 matches**.

- **Full re-audit run 2026-10-17** (read-only, no production change): 41 routes × 10 viewports = **410 loads**; harness `tests/Browser/_layout-audit-full.spec.ts`, report `.issue-tmp/layout-audit-full/report.json`. 0 pages scroll horizontally at any width (`overflow=0` in all 410).
  - **RETRACTED — the audit's `rootGrid` metric is broken.** It reported 0.91–0.96 uniformly on all 39 non-404 routes *including index pages and the dashboard*, which have no infolist root. Its DOM fallback (first `.fi-sc`/`.fi-grid` under main) resolves to the **page content wrapper** (same class descriptor `div.fi-grid.fi-sc.fi-sc-has-gap`, same 680px/744px at 1024) everywhere. My earlier claim that "all 7 reachable infolists sit at 0.91–0.96, hence A1 works" **does not hold and is withdrawn**. Probe: `tests/Browser/_probe-infolist-root.spec.ts` re-pins the node structurally and *still* catches the wrapper — the metric is not salvageable by selector tweaks.
  - **A1 rendered effect PROVEN, but only on one page.** Toggling `->columnSpanFull()` off `ProductInfolist.php` and re-running `_probe-a1-verify.spec.ts`: infolist root grid (`div.fi-grid.md:fi-grid-cols.xl:fi-grid-cols.fi-sc`) = **536px / 0.46** without, **1096px / 0.94** with. Causality established, then restored and verified restored. This supersedes the retracted claim as the only valid A1 evidence.
  - **8 of the 9 A1 infolists remain rendered-unverified.** Only Products was toggled. `InTransit` and `LossLedger` have **zero** rendered coverage at all — no seeded records, so both `--view` routes 404 at every viewport (20 of 410 cells).
  - **5 detector false positives confirmed by ancestor-chain probes** (each walks computed `overflow-x` + `scrollWidth` and asks whether that level can actually scroll): `clippedTables` (9 cells) — the real scroller is `div.fi-ta-content-ctn` (`overflow-x:auto`, 1254>680, canScroll true), not the `.fi-ta-content` node the detector checked; wizard-header `bleeding` (12 cells) — `ol.fi-sc-wizard-header` is itself `overflow-x:auto` canScroll true (intended step scroll); tab `bleeding` at 375 (2 cells) — `div.fi-tabs.fi-contained` canScroll true; table `bleeding` on users/stock-movements/loss-ledgers — same real scroller; `squished` (6 cells) — every one is `div.fi-section.fi-loading-section`, an unloaded **skeleton**, not final layout.
  - **ONE genuine new defect (outside approved scope, not fixed):** `transfer-requisitions--view` — a `RepeatableEntry` `table()` renders **725px inside a 632px** `.fi-in-table-repeatable`, and **no** ancestor scrolls (`canScroll:false`, `overflow-x:visible` all 8 levels). Content spills past the card and to `right=1061` on a 1024 viewport. 2 cells (768, 1024). Same widget class as ProductInfolist's `unitConversions`.
  - `test-results/` is untracked but **not** in `.gitignore` (only `.issue-tmp/` is) — accidental `git add` risk for Playwright output.

## Blocked
- (none)

## Next
- **Commit A1 when the owner authorizes a commit.** Nothing is committed; `CLAUDE.md` forbids committing without an approved plan saying so. Work sits on `fix/layout-invariant-a1`.
- **Known-drift skip is a live obligation.** The dashboard assertion is `markTestSkipped` while `getColumns()` matches the pinned wrong map (`default 1, sm 1, md 2, lg 2, xl 2`). It flips to a real assertion the moment A2 lands. A skip that outlives A2 hides the violation it records.
- **A1 landed without step 7** (rendered width assertion) — the owner approved step 1 + A1 only. So the 9 infolist fixes currently have **no rendered verification**: the net proves the declared string is present, not that the reflow renders. Statement of the Skeptic gap, not a new finding. Step 7 remains the fix.
- **Owner must answer step 8 before A2:** should the dashboard be **4 columns at `xl`** (blueprint §7O.5) or is the current **2-column** layout the intended behaviour? If the latter, §7O.5 is the stale artifact and Task 3 must not run. Council cannot settle this — product decision.
- **Still needs an owner decision:** where permanent audit evidence lives (PII); whether the optional shared infolist root-grid helper (10 callsites) becomes a project convention; the separately-approved §7M.7 r2 amendment; deletion approval for the temporary probe specs and `.issue-tmp/layout-audit/`.
- Owner review of the updated test expectations.
- Owner decision on tracked-config changes made outside the strict "fix the tests" scope: `vite.config.js` and `.gitignore` (plus new `playwright.config.ts`, `.env.testing.example`, `package.json` scripts).
- Owner decision on the audit findings (council recommendations, all owner-gated):
  - **F3 (dashboard columns)** — approve restoring `Dashboard::getColumns()` to §7O.5's `['default'=>1,'md'=>2,'xl'=>4]` + adding the missing `ResponsiveSpanTest` case. Reflows all 9 widgets; needs visual sign-off. **Do this first** — likely resolves F4.
  - **F1 (infolist span)** — approve adding `->columnSpanFull()` to the 9 infolists **keeping** the array form, amending blueprint §7M.7 rule 2 + §7O.4 + §7O.8 #5, and normalising `UserInfolist` (array form + translate its 10 raw strings).
  - **F2 (users scroll)** — recommend **no code change**. Separate optional cleanup: remove dead `->modalWidth(Width::Large)` from 8 tables (13 sites).
  - **F4 (chart size)** — recommend **drop**. Re-measure after F3. If still needed, `ChartWidget::$maxHeight` is available but introducing it is a new widget convention needing sign-off.
  - Also unresolved: blueprint changelog `:20276` wrongly claims `->maxHeight()` was "removed as non-existent" — blueprint is wrong, code has it.
- Owner decision on where permanent audit evidence should live (`docs/00-project/screenshots/` vs keeping `.issue-tmp/layout-audit/` temporary). **PII note:** those PNGs contain employee names/emails and financial figures — must stay untracked (RA 10173).
- Owner approval needed before deleting any audit artifact: 8 temporary `tests/Browser/_*.spec.ts` probes plus `.issue-tmp/layout-audit/`.

## Notes
- Full-suite verification after the UI rewrite: `npx playwright test` → **24 passed (3.2m)** on the default port with bundled Chromium, no env vars.
- `PLAYWRIGHT_CHANNEL` is an optional escape hatch only; not required.
- Both browser builds for Chromium 1248 are now installed (headless shell + full). `--ui` and `--headed` require the full build.
- `tests/Browser/purchase-and-sales.spec.ts` is the reference pattern for driving a Filament row ActionGroup modal: wait on the modal's config field (`input[id$="received.<id>"]`), not on `.fi-modal` — the page always holds a hidden `database-notifications` dialog plus one lazily mounted dialog per action, so `.fi-modal` is present but invisible.
- Council finding (Security seat, separate from the 4 items): `QuickActionsWidget::actions()` emits all four `Resource::getUrl('create')` links unconditionally; its `canView()` only checks `auth()->user() !== null`. No authorization bypass — the destination pages are policy-gated server-side (403) — but it advertises create routes to roles that cannot use them. Optional hardening, not approved.
- Nothing committed or pushed.
