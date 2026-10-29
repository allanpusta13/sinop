# Issue #173 — Provenance of the 847 / 860 / 589 figures

**Purpose.** This artifact exists specifically to resolve the reviewer finding
*"the 589 delta has no provenance."* It documents that **there is no 589 artifact to
document** — the number was a planning artifact, not a measurement.

**Authority:** Phase 3 owner decision **D2 = (c)**.

**Status of this artifact:** `Observed` for every file:line citation below;
`Inferred` where labelled.

---

## 1. Where 847 and 860 came from

Both numbers are **pre-catalogue planning estimates** — authored *before* the `es`/`tl`
catalogues were restructured, and never recomputed against the committed files.

Independent reproduction, `docs/02-research/2026-10-23-i18n-count-verification.md`:

- `:102-103` — reproduces the orphan count as **258**, and records the issue's 847
  claim as **"claim not reproducible"**.
- `:156` — concludes `860` and `847` are *"pre-catalogue planning estimates, never
  recomputed against the committed files."*

Corroboration — the same planning estimates appear in blueprint changelog prose:

- `docs/00-project/blueprint.md:49` — historical changelog: *"847 orphan keys per
  locale flagged in a sidecar."*
- `docs/00-project/blueprint.md:15304` — historical narrative of the O-PARITY close
  (*"847 orphan keys per locale … flagged, never dropped"*), now annotated
  `*(Superseded …)*`.
- `docs/00-project/blueprint.md:20216`, `:20237` — v0.1 change-table rows (historical
  record of what those revisions recorded).

The *active* `v0.1 state` / `v0.1 update` notes at `docs/00-project/blueprint.md:1160`
and `:1162` have been corrected to state the actual disposition.

---

## 2. The actual retired count is 258

`docs/01-issues/i18n-parity-orphans-es.txt` and `-tl.txt` — `Observed`:

- 272 lines each; **258 key rows**, all dispositioned `RETIRED`.
- Namespace markers: `forms (140 keys)`, `tables (83 keys)`, `widgets (35 keys)`;
  `140 + 83 + 35 = 258`.
- Zero rows carry `RECONCILED`, `PREVIOUSLY_REMOVED`, or `NOT_A_KEY` — the issue's
  *Reconcile path* was never exercised.
- Header `# TOTAL AUDITED RETIRED ORPHANS IN ES: 258` (and `TL`).

Corroborated by `docs/01-issues/173-orphan-key-manifest.md:19-26` (disposition table,
per-locale per-namespace).

---

## 3. The 589 delta has no artifact — it was prose only

The equation `847 − 589 − 258 = 0` appears in exactly three documents, all **report
prose**, all written after the fact, none backed by an inventory file:

| Citation | Form |
|---|---|
| `TARGETED_CORRECTION_REPORT.md:16` | `Historical Estimate (847) − Backfill (#172: 589) − Retired (258) = 0 Unexplained Keys` |
| `FINAL_SCOPED_CORRECTION_REPORT.md:16` | identical |
| `REVIEW_COUNCIL_REMEDIATION_REPORT.txt:17-18` | `Previously Handled (#172 Content Backfill) \| 589 keys (textual placeholders translated in #172)` … `Unexplained Delta \| 0 keys ($847 − 589 − 258 = 0$)` |

**No inventory, manifest, sidecar, test output, or reproduction script anywhere in the
tree enumerates 589 keys.** Every occurrence is a restatement of the same sentence.

`Inferred:` 589 was derived by subtraction — `847 − 258` — from two planning
estimates, then presented as a measured quantity. It was never counted.

---

## 4. Consequences

1. **847 is retired as a criterion.** It was never reproducible against the committed
   catalogues. See `docs/01-issues/173-issue-text-amendment.md`.
2. **589 is not evidence.** It must not be cited as a backfill measurement in any
   future report, plan, or review.
3. **258 is the only artifact-backed orphan figure** — and it is a *disposition*
   record (all retired), not a residual gap.
4. **The canonical count is closed: 996.** Loader re-derived at PR #238 head
   `9ac18567bb22ff293eeece503129fc1d0deefbed` on 2026-10-27 by two independent
   paths — `php docs/02-research/i18n-count-reproduction.php` and
   `php artisan test --filter=TranslationKeyParityTest` (which writes
   `storage/logs/i18n-parity.md`: `Canonical keys: 996`). Owner decision **D3**
   and residual risk **R-4** are discharged. The **995** at
   `docs/02-research/2026-10-23-i18n-count-verification.md:96` is a stale
   2026-10-23 snapshot: the counting rule is byte-identical in both producers
   (`TranslationKeyParityTest.php:59-107`, `i18n-count-reproduction.php:9-40`),
   so no rule difference can produce a 1-key delta; and `lang/en` was last
   changed by `aa54af6` (2026-10-24 04:51 UTC) after that snapshot. The added key
   is **`validation.role.invalid`**: `grep -c "=> '" lang/en/validation.php` gives
   140 versus 139 at `aa54af6^` — exactly one leaf — and `aa54af6` adds
   `'role' =>` / `'invalid' => 'The selected role is invalid.'`. Observed, by
   direct key diff. See
   `docs/01-issues/2026-10-28-g1-evidence-ac-reconciliation.md` §3.1.
