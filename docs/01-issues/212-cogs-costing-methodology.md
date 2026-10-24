# Issue #212 — COGS Costing Methodology Decision Document

## Executive Summary
This document evaluates inventory valuation methodologies (Weighted-Average Cost, FIFO, and Lot/Batch-Level Tracking) for Sinop. Pending sign-off on this recommendation, **snapshot cost remains the current active approach**.

## Problem & Context
Sinop currently captures inventory valuation at the time of movement via a static snapshot cost on `ProductVariant`. As transaction volume grows across multiple warehouses, Sinop requires an official Cost of Goods Sold (COGS) methodology to accurately reflect gross margins, valuation, and loss ledger calculations on inventory movements, sales, and returns.

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
- **Mechanism:** Tracks individual serial numbers or explicit lot IDs with dedicated unit purchase prices.
- **Pros:** Precise per-item cost tracking; essential for high-value or perishable items.
- **Cons:** High operational overhead for warehouse staff scanning individual lot tags on high-frequency stock movements.

## Worked Numeric Examples

### Scenario Setup:
- Starting Inventory: 100 units @ $10.00/unit (Total = $1,000)
- Purchase Batch 1: 100 units @ $14.00/unit (Total = $1,400)
- Sale Event 1: 150 units sold @ $25.00/unit (Revenue = $3,750)
- Loss Event 1: 10 units lost/damaged.

### Comparison Table:
| Metric | Weighted-Average (Option 1) | FIFO (Option 2) | Lot-Level (Option 3 - Lot A) |
|---|---|---|---|
| **Unit Cost Before Sale** | $12.00 ($2,400 / 200) | Layer 1: $10 (100), Layer 2: $14 (100) | $10.00 or $14.00 |
| **COGS for Sale (150 units)** | **$1,800.00** (150 × $12.00) | **$1,700.00** (100×$10 + 50×$14) | **$1,500.00** (150 × $10) |
| **Gross Margin ($)** | **$1,950.00** | **$2,050.00** | **$2,250.00** |
| **Loss Ledger Cost (10 units)** | **$120.00** (10 × $12.00) | **$140.00** (10 × $14.00) | **$140.00** (10 × $14) |
| **Ending Inventory Value (40 units)**| **$480.00** (40 × $12.00) | **$560.00** (40 × $14.00) | **$560.00** (40 × $14) |

## Recommendation & Status
- **Recommendation:** Adopt **Weighted-Average Costing** for Sinop v2.
- **Current Active State:** **Snapshot cost remains in place pending formal sign-off.**

## Impacted Systems & Cross-Issue Dependencies
- **#210 (Supplier Shipment):** Received PO unit costs feed the moving average calculation.
- **#213 (Purchase Returns):** Returns reduce moving average cost pool using credit note values.
- **#215 (Reporting):** Margin metrics on Sales & Purchases tabs will reflect moving average COGS.

## Acceptance Criteria for Future Implementation
1. `ProductVariant` maintains `unit_cost` via moving average calculation on stock receipts.
2. `StockMovement` records historical unit cost at moment of transaction for immutable ledger auditing.
