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
> `widgets` 35), all dispositioned `RETIRED` — see
> `docs/01-issues/i18n-parity-orphans-{es,tl}.txt`. The canonical `en` catalogue
> carries **996 keys as cited; loader re-derivation pending** (owner decision D3 —
> `docs/02-research/2026-10-23-i18n-count-verification.md:96` reproduces 995; the
> evidence files cite 996; the delta is unreconciled until the loader runs). The 847
> figure was a pre-catalogue planning estimate and is retired as a criterion — see
> `docs/01-issues/173-847-provenance.md`.

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
> wc -l docs/01-issues/i18n-parity-orphans-es.txt   # → 272 (2 header + 258 rows + 12 namespace/rule lines)
> wc -l docs/01-issues/i18n-parity-orphans-tl.txt   # → 272
> grep -c 'RETIRED' docs/01-issues/i18n-parity-orphans-es.txt   # → 258 rows dispositioned
> grep -cE 'RECONCILED|NOT_A_KEY|PREVIOUSLY_REMOVED' docs/01-issues/i18n-parity-orphans-es.txt  # → 0
> ```

---

## 3. Owner-decision-required line (D3)

`#173 canonical count: 996 as cited; loader re-derivation pending Bash recovery per D3.`

This line is also recorded in `FINAL_EXECUTION_REPORT.md` → *Explicit Owner Decisions
Required* item 4.
