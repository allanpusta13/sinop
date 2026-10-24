# Issue #217 — AdminReviewFilters Period Presets Design Document

## Problem & Context
Admin and Auditor review dashboards require consistent, intuitive date filter shortcuts across all reporting panels while preserving existing manual custom date picker capabilities.

## Recommendation & Specifications
1. **Period Presets Proposed:**
   - `7d`: Last 7 Days
   - `30d`: Last 30 Days
   - `90d`: Last 90 Days
   - `qtd`: Quarter to Date
   - `ytd`: Year to Date
   - `custom`: Explicit start and end date pickers
2. **Backward Compatibility:**
   - Existing manual date picker behavior must be preserved when `custom` is selected or when date inputs are directly edited.
3. **Translation Parity:**
   - Add preset keys to `lang/en/common.php`, `lang/es/common.php`, `lang/tl/common.php` (`period_presets.7d`, `period_presets.30d`, etc.) to maintain 100% translation key parity.
