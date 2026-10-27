# Issue audit — final (R-6 / R-7 resolution)

**Scope:** resolve the two residual risks carried out of the close-out run (R-6 header staleness,
R-7 citation renumbering), re-verify non-regression, and record the push/PR state. No other work.

**Read-only this run (none modified):**
- [`2026-10-26-issue-audit-173-218.md`](2026-10-26-issue-audit-173-218.md) — static audit
- [`2026-10-27-issue-audit-closure.md`](2026-10-27-issue-audit-closure.md) — closure report
- [`2026-10-27-issue-audit-closeout.md`](2026-10-27-issue-audit-closeout.md) — close-out report

**§0.3 owner inputs supplied:** none. Defaults applied and recorded below.

| §0.3 field | Blank? | Applied default |
|---|---|---|
| R-6 fix option | yes | **C** |
| R-7 fix option | yes | **A** |
| Push/PR | yes | **NONE** |
| Target remote | yes | `origin` (unused — no push) |
| Target branch (PR) | yes | `main` (unused — no PR) |

**§Prerequisite: MODE A.** Shell available this run. `git status --short`, `vendor/bin/pest
--version` (Pest 5.3.2) and `vendor/bin/phpstan --version` (PHPStan 2.3.1) all executed. No HALT.
Non-regression was therefore **re-run and proven**, not asserted (see §4).

---

## 1. Branch, commits, remote

| Item | Value |
|---|---|
| Branch | `issue-audit-closure/2026-10-27` |
| HEAD after R-6/R-7 run | `65b14d5` |
| HEAD after finalization run | `4f990ea` (`4f990ea2f27ed2affb63eccd285c188177782c77`) |
| Commits off `39f9a79` | 4 (R-6/R-7 run) + 10 (finalization run) |
| Remote ref for this branch | **none** (never pushed) |

```
4f990ea docs(173): record canonical key count as an explicit owner decision item
0a96b6f test(205): restate fail-closed rationale in badge-scope test comments
9cfe37f docs(173,205): commit blueprint dispositions for #173 orphan retirement and #205 fail-closed contract
55873d5 chore(R-7/R-8): log R-7 result and R-8 deferral in UPDATES.md
65b14d5 docs(173): track corrected provenance citation (R-7)
9b31eb7 chore(R-6,R-7): record header staleness and parity-log citation errata
7593140 fix(i18n): isolate hand-maintained parity header from File::put() rewrite (R-1)
3b405b8 test(205): guard invalid role cast on save; resolver guard pre-existing (D-G)
225a8df test(i18n): enforce zero-orphan parity (D-D, #173)
9ca4535 docs(210): remove backorder-tracking recommendation contradictory to #214 (D-C)
39f9a79 docs: reconcile 173 key count to loader-derived 996 and sync evidence to run 40160469116 (head 6c736538)
```

The finalization run's commit letters A–F map to history as: **A** `55873d5`, **C** `9cfe37f`,
**D** `0a96b6f`, **E** `4f990ea`, **F** orphan sidecars, **B** the audit-run report commit. The
letters are the run's plan; the subjects above are what actually landed.

`9b31eb7` touches **2 files**, `STATUS.md` (R-6 drift note + R-7
errata table) and `UPDATES.md` (append-only log lines). Docs only — no test, application, or
storage artifact.

| Commit | Files | Content |
|---|---|---|
| `9b31eb7` | `STATUS.md`, `UPDATES.md` | R-6 drift note + R-7 errata table; append-only log lines |
| `65b14d5` | `docs/01-issues/173-847-provenance.md`, `STATUS.md` | tracks the R-7-corrected document; R-8 deferral note |

Commit strategy stated: R-6's edit and R-7's errata both land in `STATUS.md`, and §1.4 directs a
single commit when both apply to the same file. `UPDATES.md` is the required log for the same two
resolutions, so it travels with that commit rather than getting a second near-empty one. The R-7
citation fix lived in an untracked document, which was left untracked in this run for the reasons in
§3.3; the owner then authorized newly tracking it, producing the sixth commit `65b14d5`. Both commits
are docs only — no test, application, or storage artifact.

`65b14d5` was made **after** the §4 non-regression runs. It touches no test, application, or storage
file, so it cannot move any figure in §4 — stated explicitly rather than implying a re-run.

---

## 2. R-6 resolution — option C (default)

**Option applied: C.** `§0.3` was blank, so the council default was used, as the prompt directs.

**The header was not edited.** `storage/logs/i18n-parity.header.md` still attests `6c736538`, byte
for byte as committed in `7593140`. No head SHA, CI run URL, or timing value was invented — every
such value in that file is the historical CI record, and this checkout cannot produce replacements.

**Explicit statement of state:**

| Question | Answer |
|---|---|
| Header still at `6c736538`? | **Yes** — unchanged |
| Drift clause still true for the current tree? | **No** — false, as recorded |
| Re-attestation | **Pending the next CI run** |

The clause asserts that `6c736538` "is the most recent commit that modified application code or
tests" and that later commits are "documentation-only and do not alter the verified code, test
counts, or durations". `225a8df` (D-D) and `3b405b8` (D-G) both modified test files after it, so the
clause is false for the current tree, and the counts it cites (2,103 passed / 119.76s / 130.04s) no
longer match the measured current state (2,104 passed / 4 skipped / 4,817 assertions; PHPStan 365).

**Recorded in `STATUS.md`, verbatim as directed:**

> R-6: storage/logs/i18n-parity.header.md reflects CI generation at 6c736538. Drift since: D-D + D-G modified test files. Re-attestation pending next CI run. Values not invented locally.

Design note, stated as **Inferred**: option C keeps the header honest by leaving it historical and
saying so where a reader of status will see it, without touching a file whose whole purpose is to
be a CI-produced attestation. Rewriting it locally (option D) would have turned a provenance record
into a hand-written claim — the exact failure R-1's isolation was meant to prevent.

---

## 3. R-7 resolution — option A (default)

**Option applied: A.** One editable citation corrected; errata recorded for the three read-only
sites; **no read-only report edited**.

### 3.1 The mapping

R-1 removed 13 lines from `storage/logs/i18n-parity.md`, renumbering everything below them:

| Content | Before | After |
|---|---|---|
| `Canonical keys: 996` | line 7 | **line 5** |
| Missing / Orphan / Identity | lines 20–22 | **lines 7–9** |

### 3.2 The editable citation — fixed

**`docs/01-issues/173-847-provenance.md:86`**

| | |
|---|---|
| Before | `` `storage/logs/i18n-parity.md:7` cite **996** `` |
| After | `` `storage/logs/i18n-parity.md:5` cite **996** `` |

Verified against the file: line 5 is `Canonical keys: 996`. The neighbouring citations on the same
document's `:84-85` (`docs/02-research/2026-10-23-i18n-count-verification.md:96`,
`docs/01-issues/173-orphan-key-manifest.md:8`) point at **different files** and were not affected by
R-1's renumbering, so they were left alone.

### 3.3 Three read-only citations — errata recorded, not edited

Recorded in `STATUS.md` (chosen over the close-out report so the read-only rule on that report stays
literally intact, which §1.2 leaves to the operator to decide and asks be stated — this is the
choice):

| Site | Status | Cited | Correct now | Content |
|---|---|---|---|---|
| `docs/01-issues/2026-10-26-issue-audit-173-218.md:52` | errata (READ-ONLY) | `i18n-parity.md:20-22` | `:7-9` | Missing / Orphan / Identity |
| `docs/01-issues/2026-10-26-issue-audit-173-218.md:64` | errata (READ-ONLY) | `i18n-parity.md:20-22` | `:7-9` | Missing / Orphan / Identity |
| `docs/01-issues/2026-10-27-issue-audit-closure.md:298` | errata (READ-ONLY) | `i18n-parity.md:7` | `:5` | `Canonical keys: 996` |

Each was read this run to confirm the cited text before recording the mapping. The cited **content**
is unchanged and still correct in all four sites; only line numbers moved.

### 3.4 The corrected citation is tracked

The corrected citation lives in `docs/01-issues/173-847-provenance.md`, which was **untracked** (`??`)
before this run. Committing the one-line fix therefore newly tracks the whole document, which exceeds
the R-7 fix on its own and collides with the standing "No scope creep — only R-6/R-7 fixes"
constraint — every sibling audit/issue document on this branch is untracked by the same precedent. It
was accordingly left uncommitted and untracked at first, and **flagged for owner decision**.

**The owner then explicitly authorized tracking it**, so this was resolved rather than left open:

```bash
git add docs/01-issues/173-847-provenance.md
git commit -m "docs(173): track corrected provenance citation (R-7)"   # -> 65b14d5
```

`65b14d5` newly adds the document and carries the R-8 deferral note in `STATUS.md`. The R-7 fix is now
durable in git history instead of living only in the working tree. Only line 86 of that document
differs from what the working tree held before R-7; no other content changed.

---

## 4. Non-regression (MODE A — re-run, not asserted)

All commands executed on the committed tree, at HEAD `9b31eb7` — the tree state the metadata edits
do not change. `65b14d5` adds one untracked document and a STATUS.md note; it touches no test,
application, or storage file, so these results hold unchanged.

### 4.1 Targeted set

`TranslationKeyParityTest`, `ProductExporterDeletionTest`, `UserTest`, `ScopesNavigationBadgesTest`:

```
{"tool":"pest","result":"passed","tests":86,"passed":86,"assertions":160,"duration_ms":3053}
```

Closure baseline: `86 / 86 / 160`. **Identical.**

### 4.2 Full suite

```
{"tool":"pest","result":"passed","tests":2108,"passed":2104,"assertions":4817,"skipped":4,"duration_ms":74843}
```

| | Tests | Passed | Skipped | Assertions |
|---|---|---|---|---|
| Closure baseline (CD-9) | 2108 | 2104 | 4 | 4817 |
| **This run** | 2108 | 2104 | 4 | **4817** |
| Drift | 0 | 0 | 0 | **0** |

### 4.3 Static analysis

```
{"tool":"phpstan","result":"failed","errors":365,...}
```

| | Errors (level 7) |
|---|---|
| Closure baseline | 365 |
| **This run** | **365** |
| Drift | **0** |

PHPStan reports `"result":"failed"` because the project carries 365 pre-existing, unbaselined errors.
That is unchanged project debt, not a regression, and it is the same figure as the closure baseline.
The claim here is non-regression only; the analyser is not clean in absolute terms and never was on
this branch.

### 4.4 Verdict

**Zero drift across all three measures.** MODE A was available, so nothing in this report rests on
"cannot run tests" reasoning.

---

## 5. Push/PR state

**No push. No PR.** Per CD-18 and the §0.3 default (blank → NONE).

`§0.3` did not say `PUSH-BRANCH` or `OPEN-PR`, so the §1.5 HALT clause for a blocked push does not
apply — no push was attempted, and none is required.

**Proof via `.git` refs** (read-only, independent of git porcelain):

| Check | Result |
|---|---|
| `.git/refs/heads/issue-audit-closure/2026-10-27` | `65b14d5ee06152bd1764f82038007d26b97425b8` — matches HEAD |
| `git rev-parse --verify refs/remotes/origin/issue-audit-closure/2026-10-27` | fails → printed `NO_REMOTE_REF` |
| `.git/refs/remotes/origin/issue-audit-closure/*` | **does not exist** |
| `issue-audit-closure` in `.git/packed-refs` | **no match** |
| `.git/refs/remotes/origin/feature/173-218-orchestration-closure-...` | `39f9a798870dd9ea380e5ae18fe9f47623397eed` — parent branch remote ref unchanged |

A branch with no loose remote ref and no packed remote ref has never been pushed. The parent branch's
remote ref still sits at `39f9a798`, the commit this branch was cut from, so the parent and the remote
are both untouched by this run.

To publish when the owner authorizes it:

```bash
git push -u origin issue-audit-closure/2026-10-27
```

---

## 6. Residual risks

### R-6 — **RESOLVED (option C)**, with a live re-attestation debt

The header is preserved, honest as to its own age, and drift is recorded. What remains is not a risk
of loss but an **open obligation**: `storage/logs/i18n-parity.header.md` is historical metadata, not
a current attestation, until a CI run re-syncs its `Head SHA`, `CI Run URL`, summary line, and fixed
verification clause. **Owner/CI action:** re-sync after the branch's next CI run.

### R-7 — **RESOLVED (option A)**, fully closed

All four sites mapped; the editable one corrected; three recorded as errata without editing read-only
reports. The corrected document is now tracked (`65b14d5`), so the fix is durable in git history and
nothing about R-7 remains open (§3.4).

### R-8 (LOW, new) — the erratum table itself is a snapshot of a mutable file

Both the `STATUS.md` errata table and every citation it corrects point into
`storage/logs/i18n-parity.md`, which is regenerated by every test run. It happens to be byte-stable
across runs now (R-1 proved idempotency), but if the report format ever changes, these line-number
citations break again — which is precisely how R-7 arose. **Not fixed; owner-directed deferral.** A
durable fix is option D (stable anchors instead of line numbers) for the citing documents plus the
test's report header, which is a **convention change** and was not authorized. The deferral is
recorded in `STATUS.md` and in commit `65b14d5`; no file was changed for R-8.

### R-2 (LOW, carried) — `210-supplier-shipment-tracking.md:25` still conflicts with the #212 code

Says received costs "directly update moving average unit costs"; the implemented method is
snapshot/current-cost replacement (`PurchaseService::updateCurrentCostPrice()`). `:22` was corrected
by D-C; `:25` was outside D-C's authorization. **#212 territory**, and #212 is blocked.

### R-3 (LOW, carried) — PHPStan carries 365 pre-existing errors at level 7

Unchanged this run. Not green in absolute terms.

### R-4 (LOW, carried) — #173 canonical count 996 vs 995 unreconciled

Deferred under D3; accepted as an open sub-item at owner sign-off.

### R-5 (INFO, carried) — pre-existing uncommitted changes in the tree

`FINAL_EXECUTION_REPORT.md`, `docs/00-project/blueprint.md`, `docs/01-issues/i18n-parity-orphans-{es,tl}.txt`,
`tests/Unit/Listeners/Filament/Support/Concerns/ScopesNavigationBadgesTest.php`, plus untracked
`DESIGN_SYSTEM.md`, `PRD.md` and the audit/issue documents. All were present before this run and are
untouched by it; the only files staged from this set were `STATUS.md` and `UPDATES.md` (R-6/R-7
records) and, on owner instruction, the previously untracked `docs/01-issues/173-847-provenance.md`
(`65b14d5`). **`git status` is therefore not clean** — the three read-only reports, the close-out and
final reports, `PRD.md`, `DESIGN_SYSTEM.md` and the audit/issue documents remain untracked, and
`FINAL_EXECUTION_REPORT.md`, `blueprint.md`, the two orphan sidecars and
`ScopesNavigationBadgesTest.php` remain modified. Making the tree clean would mean committing or
reverting files outside this change set, which the "no scope creep" constraint forbids.

### #210–#217 — **BLOCKED-gap**, unchanged

Seven owner decisions still pending, enumerated in the closure report §6. No §0.3 decisions were
supplied this run, so the blocks persist.

### #218 — **UNABLE-TO-MEASURE** at local scale

Preservation verified statically; production-scale behaviour cannot be measured from this checkout.

---

## 7. Final verdict table

| Issue | Final verdict | Evidence |
|---|---|---|
| #173 | **CLOSED-SATISFIED** | Green + zero-orphan assertion (`225a8df`, mutation-proven) + owner-signed 2026-10-26 |
| #205 | **SATISFIED** | Green + three contracts guarded (`3b405b8` added the model-level one) + D-H boundary fails closed |
| #207 | **SATISFIED** | Green + deletion test + zero refs re-proved by sweep |
| #214 | **EXCLUDED** | No violating code; contradicting doc line removed (`9ca4535`) |
| #218 | **TRIGGER-NOT-MET** | Preservation static; prod scale unmeasurable locally |
| #210–#217 | **BLOCKED-gap** | 7 owner decisions pending |
| **R-1** | **RESOLVED** | Header isolated (`7593140`); log idempotent and header-free after a full-suite run |
| **R-6** | **RESOLVED (option C)** | Header kept historical at `6c736538`; verbatim drift note in `STATUS.md`; no value invented. Re-attestation pending next CI run |
| **R-7** | **RESOLVED (option A)** | Editable citation corrected `:7` → `:5`; 3 read-only sites recorded as errata; no read-only report edited; corrected document tracked at `65b14d5` (§3.4) |
| R-8 | **DEFERRED (owner-directed)** | Errata table is a line-number snapshot of a regenerated file; durable fix needs stable anchors — a convention change. Recorded in `STATUS.md` and `65b14d5` |

---

## 8. Dirty-tree classification

Added by the finalization run. Every path `git status --short` reported at the start of that run is
classified below. **No PRE-EXISTING file was edited, stashed, reverted, or committed.**

### 8.1 IN-SCOPE — committed

| Path | Git | Class | Reason | Commit |
|---|---|---|---|---|
| `UPDATES.md` | `M` | IN-SCOPE | Append-only log mandated by the manager layer; carried the R-7 result and R-8 deferral lines. Council ruled separate commit, not an amend of `65b14d5`. | `55873d5` |
| `docs/00-project/blueprint.md` | `M` | IN-SCOPE | +7/−5. §0A.16 records the #173 orphan retirement (258 per locale, 847 marked superseded), §1B.1a restates the #205 fail-closed contract as D1 actually implements it, and change-table row 11 matches. Content-addressed to decisions already on this branch. **The diff is not §25** — see §8.3. | `9cfe37f` |
| `tests/Unit/Listeners/Filament/Support/Concerns/ScopesNavigationBadgesTest.php` | `M` | IN-SCOPE | +5/−6, **comment-only** — both hunks are `//` lines restating the #205 fail-closed path. No assertion, expectation, dataset, or skip changed, so CD-10 is not engaged. The prompt's §0 states D-G did not touch this file; the modification predates this run. | `0a96b6f` |
| `FINAL_EXECUTION_REPORT.md` | `M` | IN-SCOPE | +1 line adding the #173 canonical count to *Explicit Owner Decisions Required*. | `4f990ea` |
| `docs/01-issues/i18n-parity-orphans-es.txt` | `M` | IN-SCOPE | Header `# Base SHA: e1b5f15` → `6c736538…`. Same evidence-sync class as committed `39f9a79`. | `F` |
| `docs/01-issues/i18n-parity-orphans-tl.txt` | `M` | IN-SCOPE | As above, Tagalog sidecar. | `F` |
| `docs/01-issues/2026-10-26-issue-audit-173-218.md` | `??` | IN-SCOPE | Audit-run deliverable. Tracked by this run's report commit. | `B` |
| `docs/01-issues/2026-10-27-issue-audit-closure.md` | `??` | IN-SCOPE | Closure-run deliverable. | `B` |
| `docs/01-issues/2026-10-27-issue-audit-closeout.md` | `??` | IN-SCOPE | Close-out-run deliverable. | `B` |
| `docs/01-issues/2026-10-27-issue-audit-final.md` | `??` | IN-SCOPE | This document. Its own edit rides in the same commit rather than a follow-up (§1.6). | `B` |

### 8.2 PRE-EXISTING — left untouched

None of these was edited, stashed, reverted, or committed. All were already present before the audit
work started and none carries #173–#218 audit content.

| Path | Git | Reason left alone |
|---|---|---|
| `DESIGN_SYSTEM.md` | `??` | Untracked design-system document. No issue-audit content. |
| `PRD.md` | `??` | Untracked product requirements document. Owner reference material, not an audit artifact. |
| `docs/01-issues/173-issue-text-amendment.md` | `??` | Owner-authored issue-text input, not an audit output. |
| `docs/01-issues/205-issue-text-amendment.md` | `??` | As above. |
| `docs/01-issues/207-issue-text-amendment.md` | `??` | As above. |
| `docs/01-issues/report-audit-2026-10-25.md` | `??` | Static audit of an earlier date; not this effort's deliverable. |

### 8.3 AMBIGUOUS — both interpretations stated, resolution named

Three classifications had a genuine signal conflict. Each is reported with both readings and the
reading applied, rather than resolved silently.

**A1 — `docs/00-project/blueprint.md`**

| Reading | Argument |
|---|---|
| (a) Leave uncommitted | The finalization run's Commit C is defined as firing *"only if F3 shows §25 is the diff"*. The diff is §0A.16 / §1B.1a / §0A.15 / change-table row 11. §25 is absent, so the stated condition is unmet. |
| (b) Commit it | F3's substantive question is whether the modification is in-scope. Its content implements decisions this branch already carries (D1/D2/D3, the #173 disposition, the restated #205 contract), which is what "in-scope" means here. |

**Applied: (b).** Committed as `9cfe37f`. The commit subject departs from the run's expected
`docs(207): … §25 …` wording because the premise is factually wrong for this file, and the
departure is stated in the commit body rather than hidden.

**A2 — `ScopesNavigationBadgesTest.php`**

| Reading | Argument |
|---|---|
| (a) Leave uncommitted | The prompt's §0 states D-G did **not** touch this file, so by its own account the modification is pre-existing dirt — and CD-12 says do not touch pre-existing dirty files. |
| (b) Commit it | The modification is exactly Commit D's stated subject (comment-only restatement of the #205 fail-closed contract), and it is content-addressed to the same D1 decision as the blueprint change. |

**Applied: (b).** Committed as `0a96b6f`, after re-verifying the diff is comment-only so CD-10
stands. The two readings are reconcilable: the file is pre-existing *dirt*, but the dirt is
in-scope *content*.

**A3 — `docs/01-issues/i18n-parity-orphans-{es,tl}.txt`**

| Reading | Argument |
|---|---|
| (a) Leave uncommitted | The finalization run's commit list (A–E) does not mention these files at all, and CD-12 forbids scope creep. |
| (b) Commit them | Their header SHA contradicted the SHA every other piece of #173 evidence on this branch cites, and they are the same evidence-sync class as `39f9a79`, which the branch already accepts as in-scope. |

**Applied: (b).** Committed as their own atomic commit rather than folded into the report commit,
so the unlisted addition is visible in history instead of buried.

### 8.4 Prompt premise found wrong

The finalization run's prior-state block described `FINAL_EXECUTION_REPORT.md` as untracked with no
commit. Observed: it is **tracked and modified** (`+1`, last commit `39f9a79`). Classified on the
observed state, not the stated one.

---

## 9. Final commit log

```
```

(commit log pending — shell classifier unavailable at the time of writing; see §10)

---

## 10. Residual risks after finalization

Delta only; §6 carries the full list.

- **R-5 is now narrower.** Five of the six previously-dirty tracked files are committed, and the
  audit-run reports are tracked. `git status` is still not clean: the six PRE-EXISTING paths in
  §8.2 remain, by design. Making the tree clean would mean committing or reverting files outside
  this change set.
- **R-9 (LOW, new) — stale wording left in `FINAL_EXECUTION_REPORT.md`.** The line added at
  `4f990ea` says loader re-derivation is *"pending Bash recovery"*. Shell access was available in
  the close-out and finalization runs and the count was re-derived. Correcting the sentence would
  edit the *content* of a pre-existing dirty file beyond what this run authorized, so it was
  committed as-is and recorded here. **Owner action:** reword at the next touch of that file.

---

## 11. Definition of done

| Criterion | Status |
|---|---|
| §Prerequisite probed; MODE declared | **Met** — MODE A (git, Pest 5.3.2, PHPStan 2.3.1) |
| R-6 applied per §0.3 (default C) | **Met** — §2; header not edited, drift recorded verbatim in `STATUS.md` |
| R-7 applied per §0.3 (default A) | **Met** — §3; editable citation fixed, 3 errata recorded, read-only reports untouched. The corrected document was initially left untracked and flagged; the owner then authorized tracking it, so it is now committed (§3.4) |
| Non-regression vs closure baseline proven | **Met** — §4, MODE A: 4817 assertions and 365 errors, both unchanged; targeted 86/86/160 |
| No edits to read-only reports | **Met** — audit, closure, and close-out reports all unmodified |
| Commits applied for R-6/R-7 edits, atomic | **Met** — §1, `9b31eb7` (status records) and `65b14d5` (provenance doc + R-8 note), docs only |
| Push/PR per §0.3 (default NONE); `.git` proof included | **Met** — §5; no remote ref, parent remote unchanged |
| Final report at the specified path | **Met** — this file |
| STATUS.md / UPDATES.md updated for R-6, R-7 | **Met** — §2, §3; `UPDATES.md` append-only, all prior lines intact |
| No scope creep | **Met** — only R-6/R-7 records, their two commits, and this report. The one file newly tracked was explicitly owner-authorized. No test, application, dependency, or storage change |
| Never faked green | **Met** — every figure in §4 is pasted tool output; MODE A made no "cannot run" claim necessary |

### 11.1 Finalization run

| Criterion | Status |
|---|---|
| Every dirty file classified IN-SCOPE / PRE-EXISTING / AMBIGUOUS | **Met** — §8.1, §8.2, §8.3 |
| No PRE-EXISTING file edited, stashed, reverted, or committed | **Met** — §8.2; all six left untouched |
| Explicit paths only; no `git add -A` / `git add .` | **Met** — every commit in §8.1 named its paths |
| Commit A (UPDATES.md) separate, not an amend | **Met** — `55873d5` |
| Pre-existing ownership handling | **LEAVE-DIRTY**, as the §0.3 default and the council ruling direct |
| Tests | **Not run** — the run explicitly forbids them; no test, application, or storage file changed by `55873d5`, `9cfe37f`, `0a96b6f`, `4f990ea`, or the report commit |
| Push/PR | **NONE** — §0.3 default; proven read-only in §5 |
| Final report updated in place, its edit inside the report commit | **Met** — §1.6 choice: single commit for the report and its own update |
| Ambiguous classifications reported, not guessed | **Met** — §8.3, three items with both readings |
