# Issue #211 — Purchase-Side Negotiation Design Document

## Problem & Context
Before finalizing purchase orders, buyers and suppliers frequently negotiate pricing, minimum order quantities, delivery terms, and line item variant substitutions. Sinop requires a structured revision approval and history workflow.

## Recommendation & Architecture
1. **Negotiation Lifecycle:** `Draft` -> `InNegotiation` -> `RevisionSubmitted` -> `Approved` -> `PO_Issued`.
2. **Substitute Variant Rules:**
   - Buyers or suppliers can propose substitute variants when requested items are unavailable.
   - Requires explicit operator approval on the `PurchaseOrderRevision` line item prior to conversion into an active PO.
3. **Revision History & Auditability:**
   - Every proposal creates an immutable `PurchaseOrderRevision` snapshot.
   - System records timestamp, user/actor, line item delta (price, quantity, substitute SKU), and rationale notes.
4. **Events & Notifications:**
   - Fire `PurchaseNegotiationUpdated` event to trigger Filament notifications and email alerts to buyers and supplier representatives.

## Impacted Systems
- **#213 (Purchase Return Workflow):** Approved revision terms dictate substitute return eligibility.
