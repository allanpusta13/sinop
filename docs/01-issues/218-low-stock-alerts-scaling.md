# Issue #218 — LowStockAlertsWidget Scaling Measurement & Trigger Evaluation

## Classification
**TRIGGER-GATED** (Measure first, implement refactor only if trigger is met).

## Scaling Trigger Conditions
Refactoring to `ProductVariant::batchAvailableQuantity()` with batch query optimizations is triggered **ONLY IF** either condition is met:
1. Active variants count $\approx 5,000$, **OR**
2. Cold-cache / cache-miss query load duration is $\ge 1.0\text{–}2.0\text{ seconds}$.

## Measured Evidence
- **Environment:** Local / CI PHP 8.3 SQLite testing environment
- **Active Variant Count:** `12` active variants in database seed / baseline state.
- **Query Execution Time (Cold Cache):** `< 15ms` (0.015s).
- **Trigger Met:** **NO** (Active variants $12 \ll 5,000$; load time $0.015s \ll 1.0s$).

## Action Taken & Current Status
- Evidence recorded.
- Issue remains **OPEN** with trigger value recorded ($12$ active variants, $15\text{ms}$ query load).
- Existing cache settings, widget configuration, and chart limits remain intact and untouched. No refactoring performed per issue guidelines.
