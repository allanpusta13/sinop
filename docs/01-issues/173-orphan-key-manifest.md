# Issue #173 — Orphan Translation Key Disposition Manifest (100% Parity Reconciled)

## Executive Summary
This document provides an exhaustive, evidence-based audit of all translation keys across canonical English (`lang/en`), Spanish (`lang/es`), and Tagalog (`lang/tl`).

- **Canonical Locale:** `en`
- **Target Locales:** `es`, `tl`
- **Total Canonical Keys:** 997 keys across 19 namespaces.
- **Initial Orphans (Session Baseline):** 258 orphan keys per locale across `forms`, `tables`, and `widgets` namespaces.
- **Canonical Structure:** `lang/en/forms.php`, `lang/en/tables.php`, and `lang/en/widgets.php` are intentionally-empty canonical namespaces (`return [];`) per blueprint §0A.15.
- **Disposition Strategy:** **Retire (Delete)** verified vendor stubs with zero code references.
- **Valid Translations Preserved:** All 551 `resources.php` keys, 63 `direct_transfers.php` keys, 33 `common.php` keys, 26 `dashboard.php` keys, 141 `validation.php` keys, and all active namespaces are 100% preserved and structurally aligned across `en`, `es`, and `tl`.
- **Final Parity Status:** `Missing: 0`, `Orphans: 0`, `Placeholders: 0`.

---

## Disposed Namespaces & Counts per Locale

| Locale | Namespace | Starting Orphans | Reconciled | Retired | Justification & Verification Evidence |
|---|---|---|---|---|---|
| `es` | `forms` | 140 | 0 | 140 | Unused Filament/Laravel form stubs (`forms.required`, `forms.optional`, etc.). Canonical `lang/en/forms.php` is `[]`. Codebase search confirmed 0 references in `app/`, `resources/`, `database/`, `tests/`. |
| `es` | `tables` | 83 | 0 | 83 | Unused Filament table stubs (`tables.actions`, `tables.no_records`, etc.). Canonical `lang/en/tables.php` is `[]`. Codebase search confirmed 0 references. |
| `es` | `widgets` | 35 | 0 | 35 | Unused Filament widget stubs (`widgets.loading`, `widgets.no_data`, etc.). Canonical `lang/en/widgets.php` is `[]`. Codebase search confirmed 0 references. |
| `tl` | `forms` | 140 | 0 | 140 | Unused Filament/Laravel form stubs. Canonical `lang/en/forms.php` is `[]`. Codebase search confirmed 0 references. |
| `tl` | `tables` | 83 | 0 | 83 | Unused Filament table stubs. Canonical `lang/en/tables.php` is `[]`. Codebase search confirmed 0 references. |
| `tl` | `widgets` | 35 | 0 | 35 | Unused Filament widget stubs. Canonical `lang/en/widgets.php` is `[]`. Codebase search confirmed 0 references. |

---

## Retained & Preserved Active Translation Namespaces

| Namespace | `en` Keys | `es` Keys | `tl` Keys | Mismatches | Status |
|---|---|---|---|---|---|
| `actions` | 3 | 3 | 3 | 0 | Preserved & Parity Verified |
| `attributes` | 0 | 0 | 0 | 0 | Intentionally empty |
| `auth` | 3 | 3 | 3 | 0 | Preserved & Parity Verified |
| `common` | 33 | 33 | 33 | 0 | Preserved & Parity Verified |
| `dashboard` | 26 | 26 | 26 | 0 | Preserved & Parity Verified |
| `direct_transfers` | 63 | 63 | 63 | 0 | Preserved & Parity Verified |
| `enums` | 46 | 46 | 46 | 0 | Preserved & Parity Verified |
| `errors` | 68 | 68 | 68 | 0 | Preserved & Parity Verified |
| `forms` | 0 | 0 | 0 | 0 | Intentionally empty |
| `navigation` | 6 | 6 | 6 | 0 | Preserved & Parity Verified |
| `notifications` | 11 | 11 | 11 | 0 | Preserved & Parity Verified |
| `pagination` | 2 | 2 | 2 | 0 | Preserved & Parity Verified |
| `passwords` | 5 | 5 | 5 | 0 | Preserved & Parity Verified |
| `resources` | 551 | 551 | 551 | 0 | Preserved & Parity Verified |
| `stn` | 15 | 15 | 15 | 0 | Preserved & Parity Verified |
| `tables` | 0 | 0 | 0 | 0 | Intentionally empty |
| `validation` | 141 | 141 | 141 | 0 | Preserved & Parity Verified (including `validation.role.invalid`) |
| `widgets` | 0 | 0 | 0 | 0 | Intentionally empty |
| `wizards` | 24 | 24 | 24 | 0 | Preserved & Parity Verified |
| **TOTAL** | **997** | **997** | **997** | **0** | **100% Parity Clean** |

---

## Verification Evidence & Automated Tests
- **Parity Verification Command:** `TranslationKeyParityTest`
- **Parity Outcome:**
  ```
  # Translation key parity (§0A.15)
  Canonical locale: en
  Locales: en, es, tl
  Canonical keys: 997

  Missing keys: 0
  Orphan keys (no canonical equivalent): 0
  Identity keys (value === key): 0

  ## Placeholder Counts (Incomplete Translations)
  - es: 0 placeholders across 0 files
  - tl: 0 placeholders across 0 files
  ```
