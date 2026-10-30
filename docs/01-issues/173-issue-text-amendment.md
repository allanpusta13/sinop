# Issue #173 — Issue-Text Amendment (owner paste sheet)

**Status:** prepared for manual paste. `gh` was unavailable this pass, so the issue
body was **not** written by this session. No GitHub write occurred.

**Authority:** Phase 3 owner decision **D2 = (b) + (c)** — replace the 847
requirement with the loader-derived count; correct `blueprint.md:1160,1162`; write a
provenance artifact for the 847 → 258 delta. **Code is NOT changed.** Sidecar rows
stay as-is.

---

## 1. Authorized edit — retire the 847 criterion

**Current text** (as read earlier this session):

> accepts/requires **847 orphan keys per locale** to be enumerated and disposed

**Replacement:**

> records the orphan set the documented counting rule actually produces. The
> committed catalogues carry **258 orphans per locale** (`forms` 140, `tables` 83,
> `widgets` 35), all dispositioned `RETIRED`; see
> `docs/01-issues/i18n-parity-orphans-{es,tl}.txt`. The canonical `en` catalogue
> carries **996 keys, loader-verified** at PR #238 head `9ac1856` on 2026-10-27
> (`php docs/02-research/i18n-count-reproduction.php`, and
> `TranslationKeyParityTest` via `storage/logs/i18n-parity.md`). The count moved
> from 995 to 996 when `aa54af6` added the key `validation.role.invalid`, a
> one-leaf delta, not a counting-rule difference; owner decision D3 is discharged.
> The earlier 847 figure was a pre-catalogue planning estimate and is retired as a
> criterion; see `docs/01-issues/173-847-provenance.md`.

---

## 2. Authorized edit — the verification command

**Current text** (as read earlier this session):

> ```bash
> wc -l docs/01-issues/i18n-parity-orphans-es.txt
> wc -l docs/01-issues/i18n-parity-orphans-tl.txt
> # → 0 for both after disposition
> ```

**Replacement:**

> ```bash
> # The sidecars are a *disposition record*, not a to-be-emptied enumeration.
> # Verify zero unclassified rows instead of a zero line count.
> wc -l docs/01-issues/i18n-parity-orphans-es.txt   # => 272 total lines
> wc -l docs/01-issues/i18n-parity-orphans-tl.txt   # => 272 total lines
> # NOTE: a bare `grep -c 'RETIRED'` returns 260, not 258, because the header line
> # and the trailing total line also contain the word. Anchor on the row format so
> # the command counts key rows rather than mentions:
> grep -cE '\| es \| RETIRED \|' docs/01-issues/i18n-parity-orphans-es.txt   # => 258 key rows
> grep -cE 'RECONCILED|NOT_A_KEY|PREVIOUSLY_REMOVED' docs/01-issues/i18n-parity-orphans-es.txt  # => 0
> ```

---

## 3. Owner-decision-required line (D3) — RESOLVED 2026-10-27

`#173 canonical count: 996, loader-verified at PR #238 head 9ac1856.`

The loader re-derivation has run and is closed. Superseded text (kept for the record):
`996 as cited; loader re-derivation pending Bash recovery per D3.` The same superseded
line is also recorded in `FINAL_EXECUTION_REPORT.md` → *Explicit Owner Decisions
Required* item 4, which remains uncorrected (root-level historical report; see
`2026-10-28-g1-evidence-ac-reconciliation.md` discrepancy **D-6**).
