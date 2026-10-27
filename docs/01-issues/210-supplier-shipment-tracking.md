# Issue #210 — Supplier Shipment & In-Transit Tracking Design Document

## Problem & Context
Sinop currently tracks Purchase Orders (POs) from creation to fulfillment, but lacks explicit granular visibility into supplier shipments once goods leave the vendor's facility. Warehouse operations require clear real-time visibility into supplier ETAs, in-transit state updates, partial shipments, and systematic receipt mismatch resolutions.

## Options Considered
1. **Basic PO Line Status (Status quo):** Track status directly on the PO record (e.g., Draft -> Ordered -> Received). Limited visibility into in-transit legs and split shipments.
2. **Dedicated SupplierShipment Entity (RECOMMENDED):** Introduce a discrete `SupplierShipment` model linking POs to warehouse receipts. Supports multiple shipments per PO, tracking carrier details, estimated arrival dates, and container/tracking numbers.

## Recommendation
Implement Option 2. Define clear states for supplier shipments and formalize receipt mismatch accounting.

### State Machine
- `Draft` -> `Dispatched` -> `InTransit` -> `ArrivedAtWarehouse` -> `InspectedAndReceived` (or `Rejected`).

### Data Model Implications
- `supplier_shipments`: `id`, `purchase_order_id`, `carrier_name`, `tracking_number`, `dispatched_at`, `expected_delivery_at`, `status`.
- `supplier_shipment_items`: `id`, `supplier_shipment_id`, `purchase_order_item_id`, `shipped_quantity`, `received_quantity`.

### Handling Receipt Mismatches & Accounting
- Over-shipment: Flag for manager review; option to accept into inventory (updating COGS per #212) or create supplier return draft (#213).
- Under-shipment: Flag for manager review; the shortage is recorded against the PO line and the remainder stays open on the PO. Automatic backorder generation is **out of scope for v1** — see #214 (DO-NOT-IMPLEMENT).

## Impacted Systems
- **#212 (COGS Costing):** Received shipment item unit costs directly update moving average unit costs upon inspection.
- **#213 (Purchase Returns):** Rejected or damaged shipment items auto-populate purchase return drafts.
