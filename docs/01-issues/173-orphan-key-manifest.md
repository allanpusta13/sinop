# Issue #173 — Orphan Translation Key Disposition Manifest

## Summary
- **Canonical Locale:** `en`
- **Target Locales:** `es`, `tl`
- **Initial Orphans:** 258 orphan keys per locale across `forms`, `tables`, and `widgets` namespaces.
- **Canonical State:** `lang/en/forms.php`, `lang/en/tables.php`, and `lang/en/widgets.php` are intentionally-empty canonical namespaces (`return [];`) per blueprint §0A.15.
- **Disposition Decision:** **Retire (Delete)**. All 258 orphan keys in `es` and `tl` belonged to vendor/legacy form, table, and widget stubs that were never declared in canonical `en` and had zero references across `app/`, `resources/`, `database/`, `tests/`, and `config/`.
- **Final Parity:** `Missing: 0`, `Orphans: 0`, `Placeholders: 0`.

## Disposed Namespaces & Counts per Locale
| Locale | Namespace | Orphan Count | Action | Justification |
|---|---|---|---|---|
| `es` | `forms` | 140 | Retired | Canonical `lang/en/forms.php` is `[]`. Zero code references found. |
| `es` | `tables` | 83 | Retired | Canonical `lang/en/tables.php` is `[]`. Zero code references found. |
| `es` | `widgets` | 35 | Retired | Canonical `lang/en/widgets.php` is `[]`. Zero code references found. |
| `tl` | `forms` | 140 | Retired | Canonical `lang/en/forms.php` is `[]`. Zero code references found. |
| `tl` | `tables` | 83 | Retired | Canonical `lang/en/tables.php` is `[]`. Zero code references found. |
| `tl` | `widgets` | 35 | Retired | Canonical `lang/en/widgets.php` is `[]`. Zero code references found. |
