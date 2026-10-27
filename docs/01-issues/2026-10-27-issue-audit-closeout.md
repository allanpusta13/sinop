# Issue audit close-out — R-1 fix, commit split, status finalization

**Mode:** final housekeeping. Scope limited to the R-1 fix, the commit split, status docs, and this report.
**Prior documents (READ-ONLY — neither was modified):**
- Static audit: [`2026-10-26-issue-audit-173-218.md`](2026-10-26-issue-audit-173-218.md)
- Closure report: [`2026-10-27-issue-audit-closure.md`](2026-10-27-issue-audit-closure.md)

**§0.3 owner inputs supplied:** none. **R-1 option: A** (default). **Push/PR: NONE** (default).
**Branch: `issue-audit-closure/2026-10-27`** (default; no branch name was supplied).

The `§0.3` block was blank, so the two documented defaults were applied. The default for R-1 is
option A because R-1 is a destructive side-effect rather than a feature: the minimal safe fix is
reversible and introduces no new behaviour, so it did not warrant stopping for a decision.

---

## 1. Branch & commits

| Item | Value |
|---|---|
| Branch | `issue-audit-closure/2026-10-27` (created off the audit branch at `39f9a79`) |
| HEAD | `7593140` |
| Commits this run | 4 (atomic, one concern each) |
| Pushed | **No** |
| Parent branch | `feature/173-218-orchestration-closure-14910590968341907157` unchanged at `39f9a798` |

```
7593140 fix(i18n): isolate hand-maintained parity header from File::put() rewrite (R-1)
3b405b8 test(205): guard invalid role cast on save; resolver guard pre-existing (D-G)
225a8df test(i18n): enforce zero-orphan parity (D-D, #173)
9ca4535 docs(210): remove backorder-tracking recommendation contradictory to #214 (D-C)
39f9a79 docs: reconcile 173 key count to loader-derived 996 and sync evidence to run 40160469116 (head 6c736538)
```

### 1.1 Atomicity verified

Each commit touches only its own concern — `git show --stat` per commit:

| Commit | Files | Change |
|---|---|---|
| `9ca4535` D-C | `docs/01-issues/210-supplier-shipment-tracking.md` | 1 file, +1 / −1 |
| `225a8df` D-D | `tests/Unit/Lang/TranslationKeyParityTest.php` | 1 file, +7 |
| `3b405b8` D-G | `tests/Unit/Models/UserTest.php` | 1 file, +16 |
| `7593140` R-1 | `storage/logs/.gitignore`, `storage/logs/i18n-parity.header.md`, `storage/logs/i18n-parity.md` | 3 files, +14 / −13 |

Body snippets:

- **`9ca4535`** — the under-shipment bullet recommended "automatically generate backorder tracking
  or mark remaining line items as cancelled", contradicting #214's owner-mandated
  DO-NOT-IMPLEMENT. Rewritten to flag for manager review and state backorder generation is out of
  scope for v1 (#214). Prose only.
- **`225a8df`** — the orphan bucket was computed and reported but never asserted, so zero catalogue
  orphans was recorded rather than guarded. Adds one assertion requiring the orphan bucket to be
  empty, noting the `es`/`tl` retired-orphan sidecars are a disposition record and must never be
  re-added to satisfy it. Strengthening only; mutation-proven.
- **`3b405b8`** — the model-level assign+save path had no guard (existing coverage called
  `UserRoleCast::set()` directly). Adds the model-path guard and records that D-G's third target, a
  resolver throw, is deliberately absent because D1 requires an empty set; that contract is
  pre-existing coverage at `ScopesNavigationBadgesTest:233` (`:289` for the `hasBadgeScope` variant).
- **`7593140`** — R-1, detailed in §2.

### 1.2 Files deliberately NOT committed

The working tree also carried modifications that predate this run and are unrelated to any
authorized edit. They were left untouched and uncommitted:

`FINAL_EXECUTION_REPORT.md`, `STATUS.md`, `UPDATES.md`, `docs/00-project/blueprint.md`,
`docs/01-issues/i18n-parity-orphans-es.txt`, `docs/01-issues/i18n-parity-orphans-tl.txt`,
`tests/Unit/Listeners/Filament/Support/Concerns/ScopesNavigationBadgesTest.php`, plus untracked
`DESIGN_SYSTEM.md`, `PRD.md`, and five audit/amendment documents.

Two of these are this run's own reporting artifacts — `STATUS.md` and `UPDATES.md` were finalized
per §1.4 but not committed, matching the precedent that the audit and closure reports were never
committed either. `ScopesNavigationBadgesTest.php` is modified in the tree but **not by this run**;
its modification predates the closure and is not part of the change set.

---

## 2. R-1 resolution — option A

### 2.1 The defect

`TranslationKeyParityTest` rewrites `storage/logs/i18n-parity.md` with `File::put()` on every run.
The committed file additionally carried 13 hand-maintained lines the test does not generate — a
`Head SHA`, a `CI Run URL`, a runner summary block, and a fixed verification clause — added by
`39f9a79`. Every full-suite run silently destroyed that CI-provenance metadata, and no test guarded
it. The closure run's own baselines stripped it.

### 2.2 The prompt's premise was wrong, and was adapted

R1-A specified extracting "the first 13 lines" via `git show HEAD:… | head -13`. **That does not
match this file.** The hand-maintained lines are not contiguous and are not at the top:

| Committed line(s) | Content | Origin |
|---|---|---|
| 1–2 | `# Translation key parity (§0A.15)` + blank | generated by the test |
| **3–4** | `Head SHA:`, `CI Run URL:` | **hand-maintained** |
| 5–7 | `Canonical locale` / `Locales` / `Canonical keys` | generated by the test |
| **8–18** | blank, `## Runner Test Summary Line`, 2 summary lines, blank, `## Fixed Verification Clause`, 5 clause lines | **hand-maintained** |
| 19+ | `Missing keys` / orphan / identity / placeholder sections | generated by the test |

The count is 13 as the prompt expected; the location is not. Extraction used
`sed -n '3,4p;8,18p'` rather than `head -13`, which the prompt explicitly permitted ("verify line
count; adjust if not exactly 13"). The extracted file is **exactly 13 lines**.

### 2.3 What changed

| File | Change |
|---|---|
| `storage/logs/i18n-parity.header.md` | **new** — the 13 hand-maintained lines, byte-for-byte, in document order |
| `storage/logs/i18n-parity.md` | 13 hand-maintained lines removed; now purely test-generated |
| `storage/logs/.gitignore` | `+!i18n-parity.header.md` — the file is `*` plus explicit negations, so the new tracked file needed one |
| `tests/Unit/Lang/TranslationKeyParityTest.php` | **untouched** — no CD-10 exception was needed |

### 2.4 Verification that isolation holds

- **Header extraction is byte-exact.** `git show HEAD:storage/logs/i18n-parity.md | sed -n '3,4p;8,18p'`
  wrote the header; line count = **13**, matching the committed file's 13 hand-maintained lines.
- **Regenerated body is byte-identical to the old body minus the header.** Expected body built as
  `sed -n '1,2p;5,7p;19,$p'` from HEAD; the test was run to regenerate the log; `diff` returned
  **empty** → `IDENTICAL-GENERATED-BODY`.
- **Header is absent from the log.** `grep -c "Head SHA\|Fixed Verification Clause"` on the
  regenerated log → **0**.
- **Idempotent.** SHA-256 of the log before and after a second test run:
  `0b823fc1dacd91059ef8ca969bf39bb24fd9f7733cf49a2618ba86cf114e34fc` both times → no churn.
- **Survives a full-suite run.** After the complete `php artisan test` run, the log is still 30
  lines with 0 header matches. This is the decisive proof: a full run previously destroyed the
  header and now does not.
- `TranslationKeyParityTest` passes throughout: `{"result":"passed","tests":1,"passed":1,"assertions":5}`.

### 2.5 Head of the log, post-fix

```
1  # Translation key parity (§0A.15)
2
3  Canonical locale: en
4  Locales: en, es, tl
5  Canonical keys: 996
6
7  Missing keys: 0
8  Orphan keys (no canonical equivalent): 0
9  Identity keys (value === key): 0
```

---

## 3. Non-regression proof

All commands executed on the committed tree. Raw logs: `storage/logs/closeout-suite.txt`,
`storage/logs/closeout-phpstan.txt` (both gitignored).

### 3.1 Targeted set (§1.2)

`TranslationKeyParityTest`, `ProductExporterDeletionTest`, `UserTest`, `ScopesNavigationBadgesTest`:

```
{"tool":"pest","result":"passed","tests":86,"passed":86,"assertions":160,"duration_ms":3307}
```

The prompt named a "BadgeScopeTest read" guard; the only badge-scope test file in the repo is
`ScopesNavigationBadgesTest.php`, which holds the `hasBadgeScope` fail-closed case at `:289`. No
file named `BadgeScopeTest` exists — the reference resolves to that file.

### 3.2 Full suite vs closure baseline

| Run | Result |
|---|---|
| Closure baseline (CD-9) | `passed` — 2108 / 2104 passed / 4 skipped / **4817** assertions |
| **This run** | `passed` — 2108 / 2104 passed / 4 skipped / **4817** assertions |
| Drift | **Zero** |

```
{"tool":"pest","result":"passed","tests":2108,"passed":2104,"assertions":4817,"duration_ms":74945,"skipped":4}
```

### 3.3 Static analysis vs closure baseline

| Run | Result |
|---|---|
| Closure baseline | 365 errors (level 7) |
| **This run** | **365** errors |
| Drift | **Zero** |

Unchanged as expected: `tests/` is not in `phpstan.neon`'s `paths`, and R-1 touched no analysed
file (`storage/` is outside the analysed set). PHPStan is **not** clean in absolute terms — the 365
are pre-existing, unbaselined project debt. The claim is non-regression only.

### 3.4 Verdict

**No drift. Zero new failures, zero new static errors.** The commit gate in §1.2 was satisfied, so
the commit split proceeded.

---

## 4. STATUS.md / UPDATES.md delta

`UPDATES.md` is append-only; all prior lines are intact. Three lines appended
(`DONE` ×2, `NEEDS-DECISION` ×1). `STATUS.md`'s active workstream section was updated to its
closed state.

One line per issue, as finalized:

| Item | Final state |
|---|---|
| **#173** | **CLOSED** — owner-signed 2026-10-26; canonical 996-vs-995 deferred (D3) |
| **#205** | **SATISFIED** — green, three contracts guarded (D-G added the model-level one), boundary fail-closed (D-H confirmed) |
| **#207** | **SATISFIED** — green, zero refs re-proved by sweep, deletion test delivered |
| **#214** | **EXCLUDED** — no violating code; the contradicting doc line landed as D-C (`9ca4535`) |
| **#218** | **TRIGGER-NOT-MET / UNABLE-TO-MEASURE** — preservation verified statically; prod scale unmeasurable locally |
| **#210–#217** | **BLOCKED-gap** — decisions enumerated in closure report §6; no change this run |
| **R-1** | **RESOLVED (option A)** — header isolated; superseded line-shift follow-up logged |

**Accuracy note on #207:** the closure report's DoD row for #207 reads "file removed, zero refs,
tests verified". This run did **not** update `docs/00-project/blueprint.md` — that file is modified
in the working tree by prior work, not by this run — so the close-out makes no "blueprint updated"
claim for #207 beyond what was already true before it.

---

## 5. Push/PR state

**No push. No PR. No `git push`, no `gh` write, no network write of any kind.**

Per CD-18 and the §0.3 default. The four commits exist only on the local branch
`issue-audit-closure/2026-10-27`. The parent branch and any remote are untouched.

To publish when the owner authorizes it:

```bash
git push -u origin issue-audit-closure/2026-10-27
```

---

## 6. Residual risks

### R-1 — **RESOLVED (option A)**. Header isolated and the log proven idempotent across runs. See §2.

### R-6 (MEDIUM, NEW) — the isolated header is now factually stale and cannot be re-synced locally

Moving the header preserved it; it did not keep it *true*. The fixed verification clause asserts
that `6c736538` "is the most recent commit that modified application code or tests" and that
subsequent commits are "documentation-only and do not alter the verified code, test counts, or
durations". Since then, `225a8df` (D-D) and `3b405b8` (D-G) have modified test files, so the clause
is now **false**, and the runner counts it cites (2,103 passed / 119.76s / 130.04s) no longer match
the current 2,104 / 4 skipped / 4,817 assertions.

Re-syncing it correctly requires a CI run — a real head SHA, a real run URL, and real per-version
timings — which **cannot be produced from this local checkout**. Inventing plausible values would
fabricate evidence, so the header was committed verbatim and this gap is recorded instead.

**Owner action:** after the branch's next CI run, re-sync the header's `Head SHA`, `CI Run URL`,
summary line, and clause to that run. Until then the header is historical metadata, not a current
attestation.

### R-7 (LOW, NEW) — R-1 renumbered the log, invalidating four citation sites

Removing lines from the generated log shifted every line below them. Mapping:

| Cited location | Cited line | Actual line now | Content |
|---|---|---|---|
| `docs/01-issues/173-847-provenance.md:86` | `i18n-parity.md:7` | **5** | `Canonical keys: 996` |
| `docs/01-issues/2026-10-26-issue-audit-173-218.md:52` | `i18n-parity.md:20-22` | **7–9** | Missing / Orphan / Identity |
| `docs/01-issues/2026-10-26-issue-audit-173-218.md:64` | `i18n-parity.md:20-22` | **7–9** | Missing / Orphan / Identity |
| `docs/01-issues/2026-10-27-issue-audit-closure.md:298` | `i18n-parity.md:7` | **5** | `Canonical keys: 996` |

The cited *content* is unchanged and still correct; only the line numbers moved. Three of the four
sites are in documents this run was instructed to treat as read-only, so **none was edited** —
correcting them would have violated the read-only constraint. **Owner approval required** for a
citation-only doc correction.

This is inherent to option A: any fix that removes lines from a test-generated file renumbers it.
Preserving numbering would have required option D (keep and rewrite the header), which the prompt
flagged as brittle and gated behind a CD-10 exception.

### R-2 (LOW, carried) — `210-supplier-shipment-tracking.md:25` still conflicts with the #212 code

The line says received shipment item unit costs "directly update moving average unit costs", while
the implemented methodology is snapshot/current-cost replacement
(`PurchaseService::updateCurrentCostPrice()`). `:22` was corrected by D-C; `:25` was not, because
D-C's authorization was specific to the line recommending the #214-excluded backorder behaviour.
**#212 territory**, and #212 is blocked.

### R-3 (LOW, carried) — PHPStan carries 365 pre-existing errors at level 7

Unchanged. The analyser is not green in absolute terms and never was on this branch.

### R-4 (LOW, carried) — #173 canonical count 996 vs 995 unreconciled

Deferred under D3 and accepted as an open sub-item at owner sign-off.

### R-5 (INFO, carried) — pre-existing uncommitted changes in the tree

Listed in §1.2. Present before this run; not part of any commit made here.

### #210–#217 — **BLOCKED-gap**, no change this run

Still blocked on product decisions the agent may not make. No §0.3 decisions were supplied.

### #218 — **UNABLE-TO-MEASURE** at local scale

Preservation verified statically; the production-scale behaviour cannot be measured from this
checkout. No amount of local shell access changes this.

---

## 7. Final verdict table

| Issue | Final verdict | Evidence |
|---|---|---|
| #173 | **CLOSED-SATISFIED** | Owner-signed 2026-10-26; parity strengthened (`225a8df`, mutation-proven); suite green |
| #205 | **SATISFIED** | Green; three contracts guarded (model-level guard added `3b405b8`; resolver contract pre-existing at `ScopesNavigationBadgesTest:233`); D-H boundary fail-closed |
| #207 | **SATISFIED** | Green; deletion test delivered; zero refs re-proved by sweep of `app`/`config`/`database`/`resources`/`routes` |
| #214 | **EXCLUDED** | No violating code; contradicting doc line removed (`9ca4535`) |
| #218 | **TRIGGER-NOT-MET / UNABLE-TO-MEASURE** | Preservation static; prod scale unmeasurable locally |
| #210–#217 | **BLOCKED-gap** | 7 decisions pending, enumerated in closure report §6 |
| **R-1** | **RESOLVED (A)** | Header isolated `7593140`; log idempotent, byte-identical body, header-free after a full-suite run |
| R-6 | **OPEN — owner action** | Header now stale; re-sync needs a CI run (§6) |
| R-7 | **OPEN — owner approval** | 4 citation line numbers shifted; read-only docs not edited (§6) |

---

## 8. Definition of done

| Criterion | Status |
|---|---|
| §Prerequisite: git / pest / phpstan reachable | **Met** — Pest 5.3.2, PHPStan 2.3.1, git OK; no HALT |
| R-1 fixed per §0.3 (default A applied + default noted) | **Met** — §2; default noted at the head of this report |
| Four commits applied per §1.3, atomic | **Met** — §1, §1.1 |
| Non-regression vs closure baseline proven, no drift | **Met** — §3 (suite 4817 assertions, PHPStan 365, both unchanged) |
| STATUS.md / UPDATES.md finalized, append-only | **Met** — §4; all prior `UPDATES.md` lines intact |
| Push/PR per §0.3 (default none) | **Met** — §5; nothing pushed |
| Close-out report delivered at the specified path | **Met** — this file |
| Audit report + closure report untouched | **Met** — neither modified; their stale line citations recorded as R-7 rather than edited |
| No scope creep | **Met** — only the R-1 fix, the commit split, status docs, and this report; 3 files in the R-1 commit, 1 file each in the other three |
| CD-18 honored (no push unless instructed) | **Met** — §5 |

One deviation from the prompt's literal text, disclosed: R1-A's `head -13` extraction did not match
the file's real structure and was adapted to `sed -n '3,4p;8,18p'` under the prompt's own
"adjust if not exactly 13" instruction (§2.2). The 13-line count is as specified.
