# Issue #173 — Orphan Translation Key Disposition Manifest (Remediated)

## a) Reconcile vs Retire Counts
- **Starting Count:** 847 orphan keys per locale reported originally across all namespaces.
- **Audited Catalogue Breakdown (at session baseline):**
  - Total catalogue keys in `es`: 258 orphans (`forms`: 140, `tables`: 83, `widgets`: 35).
  - Total catalogue keys in `tl`: 258 orphans (`forms`: 140, `tables`: 83, `widgets`: 35).
  - Previously reconciled in prior workstream (#172): 589 keys.
  - Remaining active orphan keys audited: 258 keys per locale.
- **Reconciled Count:** 0 keys added to `en` (all 258 keys belonged to vendor stubs for intentionally-empty canonical namespaces).
- **Retired Count:** 258 keys deleted from `es` (`lang/es/forms.php`, `lang/es/tables.php`, `lang/es/widgets.php`) and 258 keys deleted from `tl` (`lang/tl/forms.php`, `lang/tl/tables.php`, `lang/tl/widgets.php`).
- **Reconciliation Accounting:** $847 \text{ starting} - 589 \text{ prior backfill} = 258 \text{ audited} - 258 \text{ retired} = 0 \text{ remaining orphans}$.

## b) Retired Keys Table & Search Justification

| Namespace | Key / Range | Locale | Disposition | Justification | Evidence / Search Patterns |
|---|---|---|---|---|---|
| `forms` | `forms.required` ... `forms.validation_error` (140 keys) | `es`, `tl` | Retired | Unused vendor stubs. Canonical `lang/en/forms.php` is `[]`. | Searched `__("forms.*")`, `@lang('forms.*')`, `forms.{$var}`, `validation.attributes` |
| `tables` | `tables.actions` ... `tables.filter_button` (83 keys) | `es`, `tl` | Retired | Unused vendor stubs. Canonical `lang/en/tables.php` is `[]`. | Searched `__("tables.*")`, `@lang('tables.*')`, Filament table headers |
| `widgets` | `widgets.loading` ... `widgets.last_updated` (35 keys) | `es`, `tl` | Retired | Unused vendor stubs. Canonical `lang/en/widgets.php` is `[]`. | Searched `__("widgets.*")`, `@lang('widgets.*')`, Widget headings |

### Comprehensive Search Patterns Verified
All retired keys were checked against:
1. Interpolated / composed keys: `__("forms.{$key}")`, `"{$prefix}.title"`
2. Enum labels: `UserRole::getLabel()`
3. Validation attributes mapping: `lang/*/validation.php`
4. Blade directives: `@lang()`, `trans()`
5. Filament schemas and table column definitions
6. Mail and notification classes
7. Pluralization / sibling keys (`_one`, `_many`)

## c) Diff Summary
- `lang/en/`: Unchanged (canonical `forms.php`, `tables.php`, `widgets.php` remain `[]`).
- `lang/en/validation.php`: Added `validation.role.invalid` for #205.
- `lang/es/`: `forms.php` (140 removed), `tables.php` (83 removed), `widgets.php` (35 removed).
- `lang/tl/`: `forms.php` (140 removed), `tables.php` (83 removed), `widgets.php` (35 removed).

## d) TranslationKeyParityTest Semantics Verification
- **Test File:** `tests/Unit/Lang/TranslationKeyParityTest.php`
- **Test Name:** `it('all_locales_match_canonical_key_set')`
- **Direction:** Two-way equality ($en = es = tl$). Enforces that every locale has the exact same key set as canonical `en`.
- **Storage Format:** PHP array files (`return [...]`).
- **Test Output:**
```
# Translation key parity (§0A.15)

Canonical locale: en
Locales: en, es, tl
Canonical keys: 268

Missing keys: 0
Orphan keys (no canonical equivalent): 0
Identity keys (value === key): 0

## Placeholder Counts (Incomplete Translations)
- es: 0 placeholders across 0 files
- tl: 0 placeholders across 0 files
```

## e) Validation Role Key Check
`validation.role.invalid` was added synchronously to `lang/en/validation.php`, `lang/es/validation.php`, and `lang/tl/validation.php`.
- Key present in `en`: **YES**
- Key present in `es`: **YES**
- Key present in `tl`: **YES**
- Orphan Status: **NO** (present across all 3 locales in exact parity).
