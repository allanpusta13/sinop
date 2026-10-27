# Root-level report audit — Sinop PR #238

**Purpose.** Durable audit trail for the 12 root-level `*_REPORT*` files. They
contradict each other on the same quantities. **No file was deleted in this pass** —
deletion is destructive and was not authorized. This document records each file's
headline claim and its status so the owner can delete with confidence later.

**Authority:** Phase 3 owner-authorized cleanup **B5**.

**Note on the filename:** the owner-specified name is
`report-audit-2026-10-25.md`; the pass executed on **2026-10-26**. The
owner-specified name is preserved verbatim so the Phase C file list matches.

---

## Authoritative

| File | Headline claim |
|---|---|
| `FINAL_EXECUTION_REPORT.md` | Last CI-verified code head `6c736538e4fb2b5d904a395c2423238038c7f41f`; run 40160469116; 2,103 passed / 0 failed / 4 skipped; 996 canonical keys; #173/#205/#207 **IMPLEMENTED**, #210–#217 SPEC DELIVERED, #216/#218 gate, #214 excluded |

This is the only report whose metadata matches the current code-bearing head and the
current CI run. All others cite a superseded head, a superseded run, or both.

---

## Superseded — contradicts `FINAL_EXECUTION_REPORT.md`

| File | Headline claim | Contradiction |
|---|---|---|
| `FINAL_I18N_AND_EVIDENCE_CORRECTION_REPORT.md` | Head `fe0ba03cdac6a655089cfb5beccb2b4e05576ed8`; run **40108512464**; 0 missing / 0 orphan / 0 placeholder | Superseded head and run |
| `FINAL_CORRECTION_REPORT.md` | Head `fe0ba03c…`; run 40108512464; "Cause of 1070" | Superseded head and run; **1070** vs authoritative **996** |
| `FINAL_SCOPED_CORRECTION_REPORT.md` | Head `fe0ba03c…`; run 40108512464; `847 − 589 − 258 = 0` | Superseded head and run; 847/589 retired — see `173-847-provenance.md` |
| `REVIEW_COUNCIL_REMEDIATION_REPORT.txt` | Working base `e1b5f15…`; reviewed head `d700f2c0…`; "Previously Handled (#172): 589 keys"; "Throw-on-read verified" | Superseded base and head; 589 has no artifact; the resolver returns `[]`, it does not throw |
| `TARGETED_CORRECTION_REPORT.md` | Reviewed head `1636150b…`; run **40107067644**; `847 − 589 − 258 = 0` | Superseded head and run; 847/589 retired |
| `FINAL_REMEDIATION_REPORT.txt` | `ScopesNavigationBadgesTest.php:250` reclassified `[STALE TEST]`; expectation moved `toThrow` → `toBeNull()` → `toBe([])` | Records an intermediate expectation; superseded by the D1(b)+(c) doc correction |
| `CI_TEST_FIX_REPORT.md` | "Under the #205 throw-on-read contract"; `UserTest` assertions moved to `toThrow(ValueError::class)` | Accurate about the *cast*; the resolver claim is corrected in `blueprint.md:20248`. Superseded by `FINAL_EXECUTION_REPORT.md` |
| `LOCALIZATION_CLEANUP_REPORT.md` | **1070** active keys per locale (es/tl/en), 0 missing, 0 orphan | **1070** vs authoritative **996**; pre-disposition state |
| `REMEDIATION_REPORT.txt` | Status table — #173/#205/#207 "Implementation Complete"; #218 benchmark, trigger not met | Pre-dates the current head; superseded by `FINAL_EXECUTION_REPORT.md` |
| `docs/01-issues/CONSOLIDATED_STATUS_REPORT.md` | "authoritative, reconciled status summary" for all 12 issues; all GitHub issues remain OPEN | Self-declared authoritative; H1 superseded by `FINAL_EXECUTION_REPORT.md`. Its "all issues remain OPEN" statement was verified |

---

## Non-evidence

| File | Headline claim | Status |
|---|---|---|
| `REPORT_CONVENTION.txt` | Discovered commit convention `<issue-ref>: <type>: <subject>` + a proposed 4-commit audit table | **Non-evidence** — a convention note and a commit proposal, not a verification record. Its commit plan was never executed |

---

## The two contradictions that matter

1. **Canonical key count: 1070 vs 997 vs 996.** `LOCALIZATION_CLEANUP_REPORT.md`
   says 1070; `FINAL_I18N_AND_EVIDENCE_CORRECTION_REPORT.md` / `FINAL_CORRECTION_REPORT.md`
   say 997; `FINAL_EXECUTION_REPORT.md` and the three evidence files say 996.
   The 995-vs-996 delta is owner decision **D3**, still deferred.
2. **"Throw-on-read verified" vs `expect(...)->toBe([])`.** `REVIEW_COUNCIL_REMEDIATION_REPORT.txt`
   and `CI_TEST_FIX_REPORT.md` assert the resolver throws; the live assertion at
   `tests/Unit/Listeners/Filament/Support/Concerns/ScopesNavigationBadgesTest.php:250`
   is `toBe([])`. Corrected under **D1**.
