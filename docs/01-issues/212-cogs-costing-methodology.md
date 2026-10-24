# Issue #212 — COGS Costing Methodology Decision Document

## Problem & Context
Sinop currently captures inventory valuation at the time of movement via a static snapshot cost on `ProductVariant`. As transaction volume grows across multiple warehouses, Sinop requires an official Cost of Goods Sold (COGS) methodology to accurately reflect gross margins, valuation, and loss ledger calculations on inventory movements, sales, and returns.

**Active Production Behavior Note:** Snapshot cost remains the active current production approach pending explicit sign-off on this recommendation.

## Options Considered

### Option 1: Weighted-Average Costing (Moving Average) [RECOMMENDED]
- **Mechanism:** Re-calculates unit cost dynamically upon every purchase order receipt / stock entry:
  $$\text{New Cost} = \frac{(\text{Current Qty} \times \text{Current Cost}) + (\text{Received Qty} \times \text{Received Cost})}{\text{Current Qty} + \text{Received Qty}}$$
- **Pros:** Computationally efficient, easy to audit, prevents artificial margin spikes, seamless multi-warehouse integration.
- **Cons:** Smooths cost fluctuations during high purchase price volatility.

### Option 2: First-In, First-Out (FIFO)
- **Mechanism:** Maintains a queue of incoming purchase batches and exhausts oldest layers first during sales or transfers.
- **Pros:** Closely matches physical stock flow for non-perishables; accurate balance sheet replacement valuation.
- **Cons:** High complexity in multi-warehouse transfers, partial returns, and inventory reconciliations.

### Option 3: Lot / Serial / Batch-Level Costing
- **Mechanism:** Tracks individual serial numbers or explicit lot IDs with dedicated unit purchase prices. In multi-lot sales where a single lot has insufficient balance, explicit lot allocation rules apply (e.g. 100 units from Lot A @ $10.00 + 50 units from Lot B @ $14.00).
- **Pros:** Precise per-item cost tracking; essential for high-value or perishable items.
- **Cons:** High operational overhead for warehouse staff scanning individual lot tags on high-frequency stock movements.

## Worked Numeric Examples

### Scenario Setup:
- **Starting Inventory (Lot A):** 100 units @ $10.00/unit (Total Value = $1,000.00)
- **Purchase Batch 1 (Lot B):** 100 units @ $14.00/unit (Total Value = $1,400.00)
- **Total Inflow:** 200 units @ Total Cost of $2,400.00.
- **Sale Event 1:** 150 units sold @ $25.00/unit (Total Revenue = $3,750.00).
- **Loss Event 1:** 10 units lost/damaged.

### Explicit Lot Allocation Assumptions (Option 3):
Since Lot A contains only 100 units, selling 150 units under Lot-Level Costing requires exhausting Lot A (100 units @ $10.00 = $1,000.00) and drawing the remaining 50 units from Lot B (50 units @ $14.00 = $700.00). Total COGS = $1,700.00. The 10 units lost are drawn from Lot B (10 units @ $14.00 = $140.00). Remaining inventory consists of 40 units in Lot B (40 units @ $14.00 = $560.00).

### Comparison Table:

| Metric | Weighted-Average (Option 1) | FIFO (Option 2) | Lot-Level Costing (Option 3 - Explicit Allocation) |
|---|---|---|---|
| **Unit Cost Before Sale** | $12.00 ($2,400 / 200) | Layer 1: $10 (100), Layer 2: $14 (100) | Lot A: $10 (100), Lot B: $14 (100) |
| **COGS for Sale (150 units)** | **$1,800.00** (150 × $12.00) | **$1,700.00** (100×$10 + 50×$14) | **$1,700.00** (100×$10 [Lot A] + 50×$14 [Lot B]) |
| **Gross Margin ($)** | **$1,950.00** ($3,750 − $1,800) | **$2,050.00** ($3,750 − $1,700) | **$2,050.00** ($3,750 − $1,700) |
| **Loss Ledger Cost (10 units)** | **$120.00** (10 × $12.00) | **$140.00** (10 × $14.00 from Layer 2) | **$140.00** (10 × $14.00 from Lot B) |
| **Ending Inventory Qty & Value** | **40 units @ $480.00** (40 × $12) | **40 units @ $560.00** (40 × $14) | **40 units @ $560.00** (40 × $14 from Lot B) |

### Physical Quantity and Valuation Reconciliation:
1. **Quantity Reconciliation:** $200 \text{ starting} - 150 \text{ sold} - 10 \text{ lost} = 40 \text{ ending units}$ (reconciles across all options).
2. **Valuation Reconciliation (Weighted Average):** $\$2,400 \text{ total in} - \$1,800 \text{ COGS} - \$120 \text{ loss} = \$480 \text{ ending inventory}$ (100% balanced).
3. **Valuation Reconciliation (FIFO & Lot-Level):** $\$2,400 \text{ total in} - \$1,700 \text{ COGS} - \$140 \text{ loss} = \$560 \text{ ending inventory}$ (100% balanced).

## Recommendation
- **Recommendation:** Adopt **Weighted-Average Costing** for Sinop v2.
- **Current Active State:** **Snapshot cost remains in place pending formal sign-off.**

## Impacted Systems & Cross-Issue Dependencies
- **#210 (Supplier Shipment):** Received PO unit costs feed the moving average calculation.
- **#211 (Purchase Negotiation):** Revised item costs update moving average baselines.
- **#213 (Purchase Returns):** Returns reduce moving average cost pool using credit note values.
- **#215 (Reporting):** Margin metrics on Sales & Purchases tabs will reflect moving average COGS.

## Data Model Implications
- `ProductVariant` maintains `unit_cost` via moving average calculation on stock receipts.
- `StockMovement` records historical unit cost at moment of transaction for immutable ledger auditing.

## Open Questions
1. Should multi-warehouse transfers re-calculate moving average at destination or preserve source unit cost?
2. How should negative inventory scenarios handle moving average calculation adjustments?

## Acceptance Criteria for Future Implementation
1. `ProductVariant` maintains `unit_cost` via moving average calculation on stock receipts.
2. `StockMovement` records historical unit cost at moment of transaction for immutable ledger auditing.
