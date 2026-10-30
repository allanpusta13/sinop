# SINOP — Product Requirements Document (PRD)

**Working product name:** SINOP — Inventory & Warehouse Management  
**Tagline:** Every Stock Movement. Accounted For.  
**Document status:** Draft for review  
**Version:** 0.1  
**Audience:** Product, design, engineering, QA, and AI coding agents  
**Product scope:** Single-business / single-tenant inventory and warehouse operations  
**Primary market:** Filipino SMEs, especially retailers, restaurants, and wholesale distributors

> Naming note: SINOP is the working product name. Trademark, domain, and market-conflict clearance have not been established by this document. Do not make legal-clearance claims.

---

## 1. Executive summary

SINOP is practical inventory and warehouse management software for small and medium-sized businesses that need to know what stock they have, where it is, why it changed, and who recorded or approved the change.

The product is built around a traceable stock-movement ledger. On-hand stock is derived from accepted inventory movements rather than being casually overwritten as a number. Business users should be able to manage products and variants, purchasing, sales, multiple warehouses, transfers, receiving, losses, counterparties, imports/exports, and operational reports without needing enterprise-software expertise.

SINOP should feel dependable, straightforward, and efficient. It should prioritize correctness, clear workflows, and actionable information over decorative dashboards, buzzwords, or feature density.

## 2. Problem statement

SMEs often manage inventory through spreadsheets, disconnected apps, manual adjustments, or undocumented staff processes. This can lead to:
- Stock counts that cannot be reconciled to the transactions that caused them.
- Unclear responsibility for receiving, dispatching, transferring, or adjusting stock.
- Conflicting SKU, unit, and price records.
- Limited visibility into stock across multiple warehouses.
- Difficult month-end reconciliation and audit preparation.
- Import/export workflows that silently create duplicates or corrupt records.

SINOP should make inventory records explainable and operational workflows controlled, while keeping routine tasks accessible to non-technical staff.

## 3. Product vision and principles

### Vision
Give Filipino businesses a dependable, understandable record of every stock movement.

### Product principles
1. **Practical:** Solve real operational problems; avoid features that do not improve accuracy, speed, or visibility.
2. **Simple:** Make the next action obvious and keep common workflows short.
3. **Honest:** Show meaningful status, validation errors, and limitations. Never imply a process succeeded if it did not.
4. **Traceable:** Important changes must have a clear actor, timestamp, reason, and source record.
5. **Controlled:** Authorization and workflow state—not UI hiding alone—must protect sensitive actions.
6. **Recoverable:** Use previews, explicit confirmations, safe retries, and clear error reporting for consequential operations.
7. **Filipino SME fit:** Use familiar business language and sensible local defaults, without forcing decorative Filipino motifs.

## 4. Goals and success measures

### Product goals
- Provide a reliable inventory source of truth based on a movement ledger.
- Support more than one warehouse within one business.
- Connect purchasing, sales, receiving, transfers, and losses to inventory movements.
- Reduce avoidable stock discrepancies and undocumented adjustments.
- Make master data and transaction data importable/exportable through controlled workflows.
- Make it easy to identify low stock, pending work, and exceptions requiring attention.

### Suggested success metrics
These are proposed measures; establish baselines before setting targets.
- Percentage of stock-changing transactions with a complete source record and audit trail.
- Import completion rate and rejected-row rate, segmented by validation reason.
- Number of duplicate master-data records created through imports.
- Time required to complete common receiving and transfer workflows.
- Number and age of pending transfers, receiving tasks, and exception records.
- Reconciliation differences found during stock counts.
- User-reported task completion difficulty and support requests per workflow.

Do not claim “zero shrinkage” or guaranteed elimination of stock discrepancies. The product can improve controls and visibility but cannot guarantee outcomes.

## 5. Target users and roles

### Primary users
- **Business owner / administrator:** Oversees operations, configuration, access, reporting, and auditability.
- **Branch or warehouse manager:** Coordinates one or more assigned warehouses, staff, receiving, dispatch, and transfers within granted scope.
- **Warehouse staff:** Executes permitted receiving, picking, dispatch, and count tasks within assigned scope.
- **Purchasing staff:** Maintains suppliers and purchase workflows.
- **Sales / operations staff:** Maintains customer and sales workflows and checks stock availability.
- **Auditor / reviewer:** Reads relevant records, reports, and audit history without making unauthorized changes.

### Authorization requirements
- Enforce permissions on the server using policies and validated workflow transitions.
- A user must not gain access to a warehouse, transaction, or action merely by changing a URL, request payload, record identifier, or UI state.
- Warehouse-scoped roles must only operate within assigned warehouse scope.
- Higher-level managers may manage multiple warehouses only where explicitly granted.
- Warehouse staff should not be able to dispatch stock if their assigned role is receive-only.
- Privileged opening-stock operations must be restricted to explicitly authorized administrators.
- Destructive or financially/operationally consequential actions require an explicit permission and an audit record.

Exact role names and permission matrix must be confirmed against the approved application authorization model before implementation.

## 6. Scope

### In scope
1. **Product catalog**
   - Product families/products and sellable variants/SKUs.
   - SKU and barcode handling, status, categories, and relevant descriptive attributes.
   - Variant-level unit and price information where applicable.
2. **Units of measure**
   - Base units and controlled conversions.
   - Prevent unsafe replacement or deletion of a base unit already used by transactions.
3. **Warehouses**
   - Warehouse master data and active status.
   - Warehouse-level stock visibility and permissions.
4. **Suppliers and customers**
   - Master data, identifiers, contact details, status, and transaction relationships.
5. **Purchasing and receiving**
   - Purchase records and receipt workflow.
   - Accepted receipts generate the appropriate stock movements.
6. **Sales and dispatch**
   - Sales records and fulfilment/dispatch workflow.
   - Stock deductions must be represented by valid movements.
7. **Inventory ledger**
   - Immutable or correction-by-reversal movement history for posted movements.
   - Source linkage, actor, timestamp, warehouse, variant, quantity, unit, and reason/reference as appropriate.
   - Derived stock balances and movement history.
8. **Warehouse transfers**
   - Request/approval where required, dispatch, in-transit state, receiving, and discrepancy handling.
   - Source and destination warehouses remain explicit.
9. **Losses and adjustments**
   - Documented loss records with reason, authorization, and corresponding ledger effect.
   - Corrections must preserve history rather than silently rewrite posted movements.
10. **Stock counts and reconciliation**
    - Count sessions, comparison to expected balance, review of variance, and controlled posting.
    - Exact workflow and conflict handling require detailed design before implementation.
11. **Imports**
    - Preview and validate before commit.
    - Report row-level errors and conflicts.
    - Provide idempotent or safely repeatable behavior where feasible.
    - Product-family, variant, unit conversion, current-price, warehouse, supplier/customer, and privileged opening-stock imports.
12. **Exports**
    - Master-data exports for useful backup, audit, and migration scenarios.
    - Transaction/ledger exports for orders, stock movements, loss ledger, and transfers.
    - CSV/XLSX where useful and supported.
    - Protect spreadsheet consumers from formula injection.
13. **Reporting and dashboard**
    - Current stock by warehouse and variant.
    - Low/out-of-stock items where thresholds are configured.
    - Pending receipts/transfers and operational exceptions.
    - Movement history and audit-oriented reports.
14. **Auditability**
    - Record important changes and consequential actions with actor, time, action, target, and relevant context.
15. **Access and settings**
    - Business-level settings, users/roles, units, stock thresholds, and other approved configuration.

### Out of scope unless separately approved
- Multi-tenant SaaS tenancy and tenant switching. The current product is single-tenant: one business installation, potentially with multiple warehouses.
- Accounting general ledger, tax filing, payroll, HR, and full ERP.
- Marketplace integrations, shipping-carrier integrations, and ecommerce synchronization.
- AI forecasting, AI assistants, blockchain, Web3, or other advanced-tech features without a validated business need.
- Historical price imports. Current-price import is limited to current cost/sale prices through the canonical price-writing path.
- Direct manual editing of derived stock balances.
- Uncontrolled opening balances on warehouse/variant pairs that already have stock movements.
- Import paths that bypass domain services, validation, authorization, or ledger invariants.

## 7. Core domain rules and invariants

These rules are product-level requirements. Implementation details must follow the approved architecture and actual schema.

### Stock as a derived value
- The inventory ledger is the source of truth for posted stock movement.
- On-hand balance is calculated from accepted movements using the relevant variant, warehouse, unit/conversion, and movement semantics.
- Do not provide a generic “edit quantity” control that overwrites the derived balance.
- Every posted movement must have a valid origin or documented adjustment source.
- Corrections to posted movements should use controlled reversal/correction records, preserving the original history.
- Define and test behavior for concurrency, idempotency, duplicate submissions, and transaction rollback.

### Posting and workflow state
- Draft, pending, approved, dispatched, received, posted, cancelled, and reversed are examples of possible states; the final state model must be defined per workflow.
- A state transition must be validated server-side.
- A failed transition must not partially update stock or leave orphaned ledger entries.
- A posted transaction must not be silently edited in a way that changes historical stock.
- Any cancellation after stock effect must use a controlled compensating movement where allowed.

### Transfers
- Transfer stock must be linked to both source and destination warehouses.
- Dispatch reduces available stock at the source and creates an in-transit quantity according to the approved ledger model.
- Receiving records what actually arrived and supports authorized discrepancy handling.
- The same transferred quantity must not be deducted or added twice.
- Transfers must be traceable from request through dispatch and receipt.

### Opening stock
- Opening-stock import is a privileged setup/reconciliation workflow, not a general stock adjustment mechanism.
- Allow it only for explicitly authorized administrators.
- Only accept warehouse/variant pairs with no existing stock movements.
- If movements already exist, reject the row and direct the user to the approved reconciliation/count workflow.
- Create controlled opening entries; never write directly to a stock-movements table outside the canonical domain path.
- Existing balances must not be overwritten.
- For this import contract, “updated count” is always zero; existing-balance changes are rejected.

### Import/export
- Validate file type, size, encoding, headers, row shape, values, and references.
- Preview parsed rows, create/update/reject counts, and conflicts before commit.
- Do not silently skip errors or claim success for a partial failure.
- Reject ambiguous duplicate rows where a safe deterministic resolution is unavailable.
- Variant updates use SKU matching with preview/confirmation and barcode-conflict rejection.
- Unit-conversion updates use variant SKU plus unit name; never replace/delete a base unit through import.
- Current-price imports update only current cost/sale values through the canonical price writer; no historical price import.
- Warehouse updates use warehouse code with confirmation.
- Supplier/customer matching rules must be explicit and stable.
- Spreadsheet exports must neutralize formula-injection payloads.
- Authorization applies to both UI and underlying import/export endpoints.

## 8. Primary workflows

### A. Set up a new business
1. Configure business preferences and units.
2. Create warehouses.
3. Create product families and variants/SKUs.
4. Add suppliers and customers.
5. Import opening stock only through the privileged, validated setup flow.
6. Review stock-by-warehouse and confirm imported results.

### B. Receive a purchase
1. Select or create the purchase record.
2. Select destination warehouse and expected items.
3. Record received quantities and discrepancies.
4. Validate permissions, units, and referenced records.
5. Confirm receipt.
6. Post the appropriate ledger movements atomically.
7. Show the receipt and resulting stock history.

### C. Transfer stock between warehouses
1. Create a transfer from source to destination warehouse.
2. Validate permissions and available stock.
3. Submit for required approval, if applicable.
4. Dispatch and record the in-transit state.
5. Destination warehouse records received quantities.
6. Resolve discrepancies through an authorized workflow.
7. Show traceable source, destination, dispatch, receipt, and movement history.

### D. Record a loss
1. Select warehouse, SKU, quantity, unit, and reason.
2. Show the expected stock effect before confirmation.
3. Validate permission and business rules.
4. Confirm and post the corresponding movement.
5. Preserve who recorded and approved it and why.

### E. Import master data
1. Download the correct template or view the required column contract.
2. Upload a supported file.
3. Parse and validate without committing changes.
4. Show row-level status and actionable errors.
5. Review duplicates and update conflicts.
6. Confirm commit.
7. Show created/updated/rejected totals and provide a downloadable error report.

### F. Export records
1. Choose data type, permitted filters, and format.
2. Validate authorization and export scope.
3. Generate a consistent export with safe spreadsheet values.
4. Show clear completion or failure state.
5. Record export activity where required by the audit policy.

## 9. Functional requirements

Priority legend: **P0** = correctness/security blocker; **P1** = core product; **P2** = valuable follow-up.

| ID | Priority | Requirement | Acceptance criteria |
|---|---|---|---|
| FR-001 | P0 | Ledger-derived stock | Stock shown for a warehouse/SKU is derived from valid posted movements; no generic balance overwrite exists. |
| FR-002 | P0 | Atomic stock posting | A failed business transaction cannot leave a partial stock effect or orphaned movement. |
| FR-003 | P0 | Server-side authorization | Unauthorized requests are rejected even when sent outside the UI. |
| FR-004 | P0 | Traceable movements | Each posted movement links to a valid source and contains required audit context. |
| FR-005 | P0 | Duplicate submission protection | Retrying a stock-changing action does not double-post its effect. |
| FR-006 | P1 | Product and variant catalog | Users with permission can create, view, update, search, and filter products/variants under defined uniqueness rules. |
| FR-007 | P1 | Warehouse management | Authorized users can manage warehouses and view stock only within their permitted scope. |
| FR-008 | P1 | Supplier/customer records | Authorized users can maintain counterparties and link them to relevant transactions. |
| FR-009 | P1 | Purchasing and receiving | Confirmed receipts create valid ledger movements and preserve source linkage. |
| FR-010 | P1 | Sales and dispatch | Confirmed fulfilment posts valid stock deductions under the approved stock-availability rules. |
| FR-011 | P1 | Transfers | Dispatch, in-transit, receiving, and discrepancy states are traceable and do not double-count stock. |
| FR-012 | P1 | Loss recording | Losses require reason and authorization and create a traceable stock effect. |
| FR-013 | P1 | Import preview | No imported records are committed before preview/validation and explicit confirmation. |
| FR-014 | P1 | Row-level import errors | Invalid rows show the row, field, reason, and next action; successful rows are not falsely reported when transaction atomicity fails. |
| FR-015 | P1 | Opening-stock safeguards | Only authorized users can import opening stock for warehouse/SKU pairs with no prior movements. |
| FR-016 | P1 | Current-price import | Current cost/sale prices use the canonical writer; historical price import is not offered. |
| FR-017 | P1 | Safe exports | Exported spreadsheet cells are protected against formula injection; endpoint access is authorized. |
| FR-018 | P1 | Operational overview | Dashboard surfaces relevant stock, low-stock alerts, pending work, and exceptions with drill-down paths. |
| FR-019 | P1 | Audit history | Authorized users can inspect relevant changes and consequential actions. |
| FR-020 | P2 | Stock count workflow | Users can record counts, review variance, and post approved reconciliation movements without rewriting history. |
| FR-021 | P2 | Saved filters/report presets | Common operational queries can be reused without complicating the primary interface. |
| FR-022 | P2 | Helpful templates | Import templates and column definitions are discoverable and versioned/documented. |

## 10. Non-functional requirements

### Security and privacy
- Apply least privilege and server-side authorization.
- Validate all input on the server; treat uploaded files as untrusted.
- Prevent IDOR, mass assignment, injection, unsafe file handling, and formula injection.
- Protect credentials, session state, exports, and audit data.
- Avoid exposing sensitive data in logs, error messages, or downloadable reports.
- Apply CSRF/session protections and secure file storage/download controls as appropriate to the framework.
- Destructive or high-impact actions must require explicit confirmation and a permission check.

### Data integrity and reliability
- Use database transactions for related business and ledger writes.
- Enforce important uniqueness and referential integrity constraints at the database level where appropriate.
- Use decimal-safe quantity and price handling; do not use floating-point arithmetic for authoritative monetary or stock quantities.
- Define rounding and conversion rules explicitly.
- Handle concurrent posting and stock availability checks consistently.
- Make background jobs, imports, and retries safe and observable.

### Performance
- Paginate large tables and use server-side filtering/search.
- Avoid N+1 query patterns and unbounded report queries.
- Stream or queue large imports/exports where appropriate, with visible progress and failure states.
- Define measurable response-time and data-volume targets after baseline profiling.

### Accessibility and usability
- Support keyboard navigation, visible focus, clear labels, accessible validation, and sufficient contrast.
- Do not communicate status using color alone.
- Keep forms predictable and errors next to the relevant field or row.
- Design for desktop-first operational work with responsive support for tablets and smaller screens.

### Maintainability
- Follow the repository's approved architecture and coding conventions.
- Reuse domain services for all stock-changing paths.
- Keep UI components thin; business rules belong in validated application/domain services.
- Test invariants at unit, feature, authorization, and database integration levels.

## 11. UX and content requirements

- Use plain language: “Receive stock,” “Dispatch transfer,” “Record loss,” “Available stock,” and “Review import errors.”
- Prefer actionable empty states over blank tables.
- Distinguish available, reserved, in-transit, and on-hand stock where the domain model supports them; do not collapse unlike concepts into one number.
- Always display warehouse and SKU context on stock-changing forms.
- Before confirmation, summarize the action, affected records, quantity, and warehouse.
- Make irreversible or consequential actions visually distinct without relying on alarmist design.
- Explain why a row/action was rejected and how to fix it.
- Do not use fabricated metrics, fake success notifications, or decorative charts without an operational purpose.
- Dashboard priorities should be: exceptions and pending actions first, then useful stock summaries, then secondary analytics.

## 12. Proposed navigation

Final navigation must be reconciled with the existing application and authorization model.

- **Overview**
- **Inventory**
  - Stock by warehouse
  - Stock movements / ledger
  - Stock counts and reconciliation
  - Losses
- **Catalog**
  - Products and variants
  - Units and conversions
  - Categories (if supported)
- **Operations**
  - Purchases and receiving
  - Sales and dispatch
  - Warehouse transfers
- **Contacts**
  - Suppliers
  - Customers
- **Reports**
  - Stock status
  - Movement history
  - Losses
  - Transfers
  - Transaction exports
- **Data tools**
  - Imports
  - Exports
  - Templates / import history (if supported)
- **Administration**
  - Users and roles
  - Warehouses
  - Business settings
  - Audit history

Avoid duplicate navigation items where the current Filament resource structure already provides a clearer route.

## 13. Acceptance and release gates

A release affecting stock, permissions, imports, exports, or ledger history must not be considered complete until:
- Domain invariants are covered by automated tests.
- Authorization is tested for allowed and denied roles and cross-warehouse access.
- Failure, retry, duplicate submission, and transaction rollback cases are tested.
- Import tests cover malformed files, duplicate rows, conflicting identifiers, partial/atomic failure behavior, and row-level error reporting.
- Export tests cover authorization, filters, large datasets where relevant, and formula-injection protection.
- Database-specific behavior is tested against the supported production database where feasible; SQLite-only simulation is not sufficient for database-specific guarantees.
- Documentation matches actual implemented behavior.
- The change has passed the repository's required CI, static analysis, formatting, and test gates.
- Any unresolved product/security decision is documented and explicitly approved rather than silently guessed.

## 14. Open decisions

Resolve these through the project's normal review and approval process:
1. Exact role/permission matrix and warehouse scope rules.
2. Final transaction state machines for purchases, sales, transfers, losses, and stock counts.
3. Reservation/available-stock semantics, if reservations are in scope.
4. Negative-stock policy and concurrency behavior.
5. Unit-conversion precision and rounding rules.
6. Exact SKU/barcode uniqueness and product active-status behavior.
7. Import file size/row limits, supported encodings, and sync-vs-queued thresholds.
8. Import transaction strategy for mixed valid/invalid rows.
9. Audit retention and export-activity logging policy.
10. Report definitions and stock valuation method, if valuation is required.
11. Whether barcode scanning, batch/lot tracking, expiry dates, serial numbers, or multiple currencies are needed.
12. Final name and brand clearance.

Do not invent answers to these questions in implementation. Escalate material changes to product/security/architecture review.

## 15. Document governance

- This PRD defines product intent, not permission to bypass repository governance.
- Approved requirements and plans take precedence according to the project's established authority order.
- Non-trivial changes must follow the repository's required review-council and planning gates.
- Implementation work should be issue-scoped, tested, and documented.
- Never create or modify production data, change repository protections, merge, push, or perform destructive actions without the required explicit authorization.
