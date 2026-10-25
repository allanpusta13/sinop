# Translation key parity (§0A.15)

Date: 2026-10-24
Head SHA: fe0ba03cdac6a655089cfb5beccb2b4e05576ed8
CI Run URL: https://github.com/allanpusta13/sinop/actions/runs/40108512464
Canonical locale: en
Locales: en, es, tl
Canonical keys: 997

## Runner Test Summary Line
`Tests: 2103 passed, 0 failed, 4 skipped (4813 assertions)`
`Duration: 104.67s`

## Per-Locale Parity Accounting
| Locale | Canonical Keys | Pre-Audit Orphans | Post-Audit Orphans | Retired Keys | Reconciled Keys |
|---|---|---|---|---|---|
| `es` | 997 | 258 | 0 | 258 | 0 |
| `tl` | 997 | 258 | 0 | 258 | 0 |

## Delta Accounting
- Historical Blueprint Pre-Catalogue Estimate: 847
- Handled in #172 Content Backfill: 589
- Audited Orphan Inventory in PR #238: 258
- Reconciliation: $847 - 589 \text{ (backfill)} - 258 \text{ (retired)} = 0 \text{ unexplained keys}$.

## Fixed Verification Clause
This document reflects CI run https://github.com/allanpusta13/sinop/actions/runs/40108512464 on code head fe0ba03cdac6a655089cfb5beccb2b4e05576ed8. Commits after this SHA are documentation-only and do not alter the verified code or test counts.

## Test Output Details
- Missing keys: 0
- Orphan keys (no canonical equivalent): 0
- Identity keys (value === key): 0
- Placeholders: es: 0, tl: 0
