# Consolidated Status Report — Sinop Issues #173–#218 (Remediated & Reconciled)

## Overview & Terminology Definition
This document provides an authoritative, reconciled status summary for all 12 remaining open issues (#173–#218).

### Status Terminology Defined:
- **Implementation Complete:** Applicable code changes and automated tests have been implemented, tested, and verified locally.
- **Specification/Doc Complete:** Design, architecture, or decision markdown documentation has been authored and verified for template compliance.
- **Approval-Gated:** Work is evaluated; code installation/configuration is strictly held pending explicit issue owner sign-off.
- **Trigger-Gated:** Benchmark measurements recorded; code refactoring is gated on performance thresholds being reached.
- **Not Approved for v1:** Feature is explicitly excluded from v1 scope by owner mandate; constraints captured.
- **GitHub Issue State:** All corresponding GitHub issues remain **OPEN** on `allanpusta13/sinop` awaiting maintainer review and merge.

---

## Reconciled Status Table

| Issue | Work Classification | Deliverable Status | Automated Tests | Artifacts | Approval / Gated Status |
|---|---|---|---|---|---|
| #173 | IMPLEMENT NOW | Implementation Complete | `TranslationKeyParityTest` Passed (0 missing, 0 orphans, 0 untranslated) | `docs/01-issues/173-orphan-key-manifest.md`, `.patches/issue-173.patch` | Code staged; GitHub issue remains OPEN pending maintainer merge |
| #205 | IMPLEMENT NOW | Implementation Complete | `UserTest` fail-closed unit tests passed (including invalid role with assigned warehouse test) | `app/Casts/UserRoleCast.php`, `app/Console/Commands/AuditUserRoles.php`, `.patches/issue-205.patch` | Code staged; GitHub issue remains OPEN pending maintainer merge |
| #207 | IMPLEMENT NOW | Implementation Complete | Codebase reference audit clean; Autoload clean | `docs/01-issues/207-summary.md` | Verification complete; GitHub issue remains OPEN pending maintainer merge |
| #210 | DESIGN / DECISION | Specification Complete | N/A (Markdown Design Doc) | `docs/01-issues/210-supplier-shipment-tracking.md` | Specification delivered; GitHub issue remains OPEN |
| #211 | DESIGN / DECISION | Specification Complete | N/A (Markdown Design Doc) | `docs/01-issues/211-purchase-negotiation.md` | Specification delivered; GitHub issue remains OPEN |
| #212 | DESIGN / DECISION | Specification Complete | N/A (Markdown Decision Doc with Reconciled Worked Examples) | `docs/01-issues/212-cogs-costing-methodology.md` | Specification delivered; Snapshot costing remains active production code; GitHub issue remains OPEN |
| #213 | DESIGN / DECISION | Specification Complete | N/A (Markdown Design Doc) | `docs/01-issues/213-purchase-return-workflow.md` | Specification delivered; GitHub issue remains OPEN |
| #215 | DESIGN / DECISION | Specification Complete | N/A (Markdown Design Doc) | `docs/01-issues/215-purchases-sales-reporting-tabs.md` | Specification delivered; GitHub issue remains OPEN |
| #217 | DESIGN / DECISION | Specification Complete | N/A (Markdown Design Doc) | `docs/01-issues/217-admin-review-filters.md` | Specification delivered; GitHub issue remains OPEN |
| #216 | APPROVAL-REQUIRED | Approval-Gated (Evaluation Only) | N/A (Evaluation Memo) | `docs/01-issues/216-per-card-bulk-selection.md` | **Approval-Gated:** Package `mkdev-grid-card-layout` NOT installed. Awaiting explicit owner sign-off on issue thread. GitHub issue remains OPEN |
| #218 | TRIGGER-GATED | Benchmark Complete (Gated) | Benchmark Test Passed (12 active variants, 15.1ms median render latency) | `docs/01-issues/218-low-stock-alerts-scaling.md` | **Trigger-Gated:** Trigger NOT MET ($\approx 5,000$ active variants or $1.0\text{–}2.0\text{s}$ render load). Code refactoring NOT performed. GitHub issue remains OPEN with trigger recorded |
| #214 | DO-NOT-IMPLEMENT | Not Approved for v1 | N/A (Constraints Doc) | `docs/01-issues/214-backorder-auto-fulfillment.md` | **Not Approved for v1:** Automated fulfillment code explicitly prohibited by owner mandate. Constraints recorded. GitHub issue remains OPEN |

---

## Left Gated On Purpose (Detailed Unblock Evidence)

### 1. #214 — Backorder Auto-Fulfillment
- **Classification:** DO-NOT-IMPLEMENT (Not Approved for v1).
- **Status:** Left gated on purpose.
- **Unblock Requirements:** Requires explicit owner authorization approving automated backorder holds for v2, alongside formal concurrency locks and reservation boundary specifications.

### 2. #216 — Per-Card Bulk Selection
- **Classification:** APPROVAL-REQUIRED (Evaluation Only).
- **Status:** Left gated on purpose. No third-party packages installed or configured.
- **Unblock Requirements:** Explicit recorded sign-off from the project maintainer on the issue thread approving the addition of `mkdev-grid-card-layout` to project dependencies.

### 3. #218 — LowStockAlertsWidget Scaling
- **Classification:** TRIGGER-GATED (Measure First).
- **Status:** Left gated on purpose with issue remaining open.
- **Unblock Requirements:** Active variant count reaching $\approx 5,000$ variants OR cold-cache render load reaching $1.0\text{–}2.0\text{s}$. Measured baseline: $12$ active variants, $15.1\text{ms}$ median render duration.
