# Issue #213 — PurchaseReturn Workflow Design Document

## Problem & Context
When received goods are defective, incorrect, or over-shipped, warehouse staff must initiate a return to the supplier. A formal lifecycle, approval mechanism, refund/credit note tracking, and stock-movement writer are required.

## Recommendation & Lifecycle
1. **Lifecycle:** `Draft` -> `PendingApproval` -> `Approved` -> `DispatchedToSupplier` -> `CreditNoteIssued` / `RefundProcessed`.
2. **Stock-Movement Writer:**
   - Upon `DispatchedToSupplier`, system executes a `StockMovement` of type `PurchaseReturn` to deduct quantity from current warehouse available balance.
   - Decrements warehouse inventory balance; writes cost ledger entry at the current moving average cost (#212).
3. **Accounting & Financial Effects:**
   - Generates credit note record associated with supplier balance.
   - Adjusts total inventory valuation downwards in ledger accounts.

## Impacted Systems
- **#210 (Shipment Tracking):** Directly fed by shipment inspection rejection entries.
- **#212 (COGS Costing):** Cost accounting uses moving average cost at time of return dispatch.
