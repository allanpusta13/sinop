# AI Implementation Plan — Layout Mismatch Fix

Status: `PENDING`

Revision 3, then revised on **council #3's binary verdicts: ACCEPT ×4, REJECT ×1.**

- **Product ACCEPT** — cut the two unrelated blueprint-hygiene fixes (scope said "two" doc corrections, four were listed).
- **Security ACCEPT** — named the diff-grep's owner and its limits; added the `getWidgets()` membership check.
- **Architecture REJECT** — cleared by one edit: **split commit A into A1 (additive infolist roots) and A2 (owner-visible dashboard reflow)**, so the risky change reverts independently.
- **QA ACCEPT** — sign-off made a dated written record; the reflow stated as an **inferred** consequence, not a measured result.
- **Skeptic ACCEPT** — contributed the round's most material finding: nothing verified the **rendered** result of the 9 infolist fixes. Added step 7 (one width assertion, no PII) and step 8 (§7O.5 may itself be stale).

Revision 2 was the 5/5-MODIFY plan; Product named the acceptable shape directly — *"10 edits + translate 10 strings. Ship that; park the rest."* Everything the council called housekeeping is under "Not in This Plan."

## Objective

Fix the two parent/child grid mismatches found by the 2026-10-16 layout audit, and pin the one
that silently drifted. Two councils rejected a global 12-column migration; the defect is
mismatch, not resolution.

Visible output: `Dashboard::getColumns()` returns the blueprint's value; 9 infolist view pages
stop rendering cards in a third of the width; one infolist stops shipping untranslated strings.

## Scope

### In This Plan
1. **Invariant net (declarations only).** 2 assertions. Verify RED first. *(Project TDD rule; also Architecture's preference.)*
2. **9 infolist root grids** — add `->columnSpanFull()`, array form retained. Purely additive, view pages only.
3. **`Dashboard::getColumns()`** — reconcile to §7O.5. Owner visual sign-off.
4. **`UserInfolist`** — array form + translate its 10 raw strings. Separate commit.
5. **Doc corrections** — two specific, load-bearing ones (below).

### Not in This Plan (each needs its own decision; none blocked by this work)
- **12-column migration** — rejected by two councils. Not performed.
- **`UsersTable` row actions** — blueprint F31 (`:1292`) forbids the `ActionGroup`. **Owner-deferred.**
- **Dead `->modalWidth(Width::Large)`** (8 tables, 13 sites) — real, unrelated, invisible. Own plan.
- **Pixel baseline / screenshot sweep** — `test-results/`, `docs/00-project/screenshots/` and
  `.issue-tmp/layout-audit/` carry employee names, emails, and financial figures (RA 10173).
  Not worth new capture for a two-line fix. **Owner eyeballs the reflow instead.**
- **§7M.7 r2 amendment** — governing-document change; separately approved, never bundled.
- **Shared infolist root-grid helper** — 10 callsites is real DRY and would *prevent* recurrence a
  test only catches, but it is a new project convention. Separate proposal.
- **F4 / chart height** — sits downstream of a false blueprint premise (`:20276`). Dropped.
- **Blueprint hygiene fixes `:20276` (`maxHeight`) and `:15510` (`Grid::make(3)`)** — real but unrelated to the layout mismatch, and `:20276` belongs to the dropped chart work. Own plan. *(Cut on Product's verdict: this plan's scope names **two** doc corrections; four were listed.)*
- Any new package. Analytics, a11y, contrast, keyboard nav, dark mode.

## Governing Inputs

- Blueprint §7O.5 (`:12944`), §7M.7 r2 (`:12823`), §7O.4, §7O.8 r5 (`:12973`), §10 (`:14061-14071`), F31 (`:1292`), §0A.7.
- Precedent: `tests/Feature/Architecture/FormFieldSpanTest.php` (line parser, no framework boot).
- Existing spec: `tests/Feature/Filament/Widgets/DashboardWidgetsTest.php:78-94`.

## Discovery Findings (observed)

**The two mismatches — both literal string-vs-clause contradictions.**
- `Dashboard.php:29-38` returns `['default'=>1,'sm'=>1,'md'=>2,'lg'=>2,'xl'=>2]`. §7O.5 mandates `['default'=>1,'md'=>2,'xl'=>4]`. Extra `sm`/`lg` keys; `xl` capped at 2.
- 9 infolist root grids omit `->columnSpanFull()`: DirectTransfers:21, InTransits:22, LossLedgers:18, Products:22, PurchaseOrders:21, SalesOrders:21, StockMovements:21, TransferRequisitions:21, Warehouses:24. `UserInfolist.php:21` is the sole holder — and the sole integer `Grid::make(3)` form.

**Reflow scope: 8 of 9 widgets, not 9.** Widget xl spans vs parent xl — `4`×1, `2`×4, `1`×4.
Parent 2→4: `xl=4` (QuickActions) stays full, **unchanged**; four `xl=2` shrink to half; four `xl=1` to quarter.

**Vendor mechanism.** `ComponentAttributeBag.php:158-176` emits spans as CSS custom properties, not
compiled classes. `CanSpanColumns.php:45-50` — `columnSpanFull()` is `columnSpan(['default'=>'full'])`.
Any integer span is legal at zero CSS cost; 3/2/4/1 all divide 12. Migration = PHP churn, no layout change.

**The gap that let the drift survive.** `DashboardWidgetsTest.php:78-94` pins all 9 widget spans but
**not** `getColumns()`. Zero tests reference infolist grids.

**Doc corrections in scope (2):**
- `docs/01-issues/2026-10-16-layout-audit.md:147` recommends `QuickActionsWidget`→`xl=2` — the fix council rejected. Durable, reads authoritative, future agents will apply it. → correction banner.
- `blueprint.md:15258-15279` cites **14** `ResponsiveSpanTest::*` methods plus `TableArchitectureTest::*`, `AllResourcesTest::*`, `AllActionsTest::*`, `AllFormFieldsTest::*`, `NavigationBadgeTest::*`, `RuntimeCompletenessTest::*`. **None exist on disk.** → mark unbuilt. *(All five seats rejected revision 1's proposal to create a test to make the citation true.)*

## Council Review

- **Council #1** — 12-column proposal: 3 REJECT / 2 MODIFY. No endorsement.
- **Council #2** — revision 1 of this plan: 5/5 MODIFY, 0 ACCEPT. Fixes endorsed 5/5; plan shape rejected by all. Directives applied: fixes-first, drop the unimplementable parent-track invariant (Architecture), state the declaration-only limit (QA), split layout from i18n commits (Security), reconfirm-or-retract gate (Skeptic), cut ~60% (Product).
- **Council #3 — binary verdict on revision 3: ACCEPT ×4, REJECT ×1.** Product, Security, QA, Skeptic ACCEPT. Architecture REJECT, cleared by the A1/A2 commit split. Every seat found a real defect; all five are applied. **No seat dissents from the plan as it now stands.**
  - Unresolved by design, routed to the **owner** as step 8: whether §7O.5 or the code is the stale artifact. A council cannot settle this; it is a product decision.
  - Still-contested artifact: the 2-assertion invariant net. *Skeptic* holds it is self-serving — the failure was nobody consulting the blueprint, and a declaration test would not have caught the auditor's misjudgment. *Architecture* wants it. Kept, cheap, limits stated. **Recorded, not hidden.**
  - **Note on process:** this was the 4th council round on a change of ~21 code lines. Skeptic was asked directly whether the plan had been calibrated to its auditors rather than the code; it answered ACCEPT and named its own falsifier. Flagging the cost honestly — further rounds have poor return.

## Implementation Tasks

### 1. Invariant net — declarations only. Verify RED.
- [ ] New test in `tests/Feature/Architecture/`: (a) every infolist root `Grid` carries `->columnSpanFull()`; (b) `Dashboard::getColumns()` equals §7O.5 exactly. `FormFieldSpanTest.php` pattern — line parser, no framework boot.
- [ ] Run against current code and record the **exact failure list** — this is the RED, and the baseline the original audit could not produce.
- [ ] Docblock states the limit: *asserts declared values and rendered class strings; does NOT assert computed pixels; green does not prove viewport correctness.*
- [ ] **Not included:** child-span-vs-parent-track comparison. A line parser cannot resolve nesting; `FormFieldSpanTest` only inspects self-contained chains on one node. A mismatch guard needs runtime or a hand-maintained `(surface, breakpoint) → parent tracks` table — separate proposal.

### 2. Infolist roots — additive, low risk, revert-isolated. **Commit A1.**
- [ ] Add `->columnSpanFull()` to the 9 roots; array form retained.
- [ ] Net assertion (a) goes green.
- [ ] Lands **alone and first**. Purely additive, view pages only; if anything is wrong it reverts without touching the dashboard.

### 3. Dashboard — owner-visible reflow. **Commit A2, only after A1.**
- [ ] Reconcile `getColumns()` to `['default'=>1,'md'=>2,'xl'=>4]`.
- [ ] Owner opens `/admin` at 1280 and 1440. 8 widgets resize; QuickActions does not move.
- [ ] **Owner visual sign-off — this is the gate. A2 does not land without it.** Recorded as a **dated written note naming the viewports (1280 / 1440) and what resized** — not a verbal pass. *(QA: an unrecorded sign-off leaves the reflow claim unfalsifiable.)*
- [ ] Net assertion (b) goes green.
- [ ] *Split from A1 on Architecture's verdict: the safety of revert isolation is the point of separating commits — bundling the invisible additive change with the owner-visible global reflow destroys it and contradicts the A/B split's own rationale.*

### 4. `UserInfolist` — normalise. **Commit B (i18n + schema only).**
- [ ] Array form + translate the 10 raw strings.
- [ ] Kept out of commit A1/A2 so a layout diff never contains a string/authz change.
- [ ] **Enforcement, named (Security verdict):** run before each commit —
      `git show <A1|A2> | grep -E 'visible\(|hidden\(|canView|can\(|authorize|->query\('` must return nothing.
      Also assert commit A2 does **not** alter `getWidgets()` membership — a span change must not change *which* widgets render.
      **Owned by:** the releasing developer, run manually pre-commit; not wired to CI. If it is ever skipped, treat A1/A2 as unverified. *(Known limit: a literal grep misses `Model::where(`, `DB::raw`, `Gate::`-style authz. It holds because A1/A2's scope cannot physically express authz — not because the grep is exhaustive.)*

### 5. Doc corrections — the two the scope names.
- [ ] Correction banner on `docs/01-issues/2026-10-16-layout-audit.md` linking this plan.
- [ ] Mark the fictional test-catalogue blocks at `blueprint.md:15234-15279` unbuilt.

### 6. Verify.
- [ ] Focused Pest run on the new test + `DashboardWidgetsTest`; then the broader suite; report exactly what ran.
- [ ] `vendor/bin/pint --dirty --format agent`.
- [ ] Confirm `.issue-tmp/layout-audit/`, `test-results/`, `storage/debugbar/` remain untracked.

### 7. Rendered check for the 9 infolists — closes the verification gap. *(Skeptic's finding; not in revisions 1–3.)*
The net asserts the **declared string** `columnSpanFull()`. It never asserts the **rendered reflow**. Owner sign-off covers the dashboard only. So the 9 infolist fixes would otherwise ship with **zero rendered verification**: if the call is insufficient on any root, the net goes green and the ~248px gap persists undetected.
- [ ] **One** Playwright assertion on **one** infolist view page: the infolist root's width equals the content-area width. **No screenshots, no PII** — a number compared to a number.
- [ ] Run it against current code first (expect FAIL — the gap), then after step 2 (expect PASS).
- [ ] This is the minimum reproduction of the audit's own Finding-1 measurement, without its PII cost.
- [ ] *If a single page's assertion is judged too thin to generalise to all 9, that is a scope decision for the owner — not a silent widening.*

### 8. Spec check before Task 3 — *Skeptic's stated falsification condition.*
Task 3 rewrites working code to match §7O.5. Confirm §7O.5 is not itself the stale artifact:
- [ ] Owner states the dashboard should be 4 columns at `xl`, not the current 2. **If the 2-column dashboard was the intended behaviour, §7O.5 is wrong and Task 3 must not proceed — fix the doc instead.**
- [ ] Recorded before A2 lands. Same gate as the visual sign-off.
- [ ] *(This is the one place the plan could rewrite correct code to satisfy a wrong specification. Both directions are cheap to state; guessing is not.)*

## Acceptance Criteria

| # | Passes when |
|---|---|
| 1 | Net fails on current code with an exact violation list; passes after; no framework boot |
| 2 | 9 roots carry `columnSpanFull()`; array form intact |
| 3 | `getColumns()` strictly equals `['default'=>1,'md'=>2,'xl'=>4]`; `getWidgets()` membership unchanged; dated sign-off note recorded. **The actual pixel reflow is an inferred consequence of the vendor span mechanism, not a measured result — the plan does not prove it** |
| 4 | 10 strings translated; `TranslationCompletenessTest` green; A1/A2 diff-grep clean |
| 5 | Banner + unbuilt marks present (2, matching scope); no oracle test created to satisfy a citation |
| 6 | Full suite green; Pint clean; exactly what ran is reported |
| 7 | One infolist root width == content width; FAILs before step 2, PASSes after — rendered proof, no PII artifact |
| 8 | Owner has stated 4-column-at-xl is intended (or §7O.5 is corrected and A2 is dropped) |

## Stop Conditions

Stop for material product, architecture, security, data, dependency, destructive, or deviation
decisions. Specifically: any diff mixing layout with visibility/authorization/query logic; any
change breaking §10; any file deletion without approval; any new package; any screenshot capture
before a PII decision; any attempt to implement the parent-track invariant with a line parser.

## Approval Metadata
- Council status: council #2-reviewed (5/5 MODIFY on revision 1); revision 3 restructured to the shape Product named. Council #3 binary verdict on revision 3: **ACCEPT ×4 (Product, Security, QA, Skeptic), REJECT ×1 (Architecture — cleared by the A1/A2 commit split)**.
- User approval: **Alvin — PARTIAL.** Approved verbatim: *"approve step 1 and A1"*.
- Approved date: 2026-10-17
- **Authorized scope: step 1 (invariant net) + step 2 / commit A1 (9 infolist roots only).**
- **NOT authorized — each still requires its own decision:** step 3 / commit A2 (dashboard reflow) and its step-8 spec check; step 4 / commit B (`UserInfolist` i18n); step 5 (doc corrections); step 6 (broader verify); **step 7 (rendered width assertion)**.
- **Recorded risk of this partial approval:** A1 ships without step 7, so the 9 infolist fixes land with **no rendered verification** — exactly the Skeptic gap step 7 was added to close. Net assertion (a) going green proves the declared string is present, not that the reflow renders. Accepted by the owner implicitly in approving step 1 + A1 only.

## Completion Metadata
- Status: not started — **no production file touched**
- Tests:
- Evidence:
- Deviations:
- Completion date:
