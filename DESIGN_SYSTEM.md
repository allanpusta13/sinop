# SINOP — Design System

**Product:** SINOP — Inventory & Warehouse Management  
**Tagline:** Every Stock Movement. Accounted For.  
**Status:** Initial design-system specification  
**Version:** 0.1  
**Audience:** Product design, frontend engineering, QA, and AI coding agents

> Brand clearance is still an open business/legal task. This design system defines a working product direction and does not assert trademark clearance.

---

## 1. Design intent

SINOP should look and behave like dependable business software: calm, clear, compact, and accountable.

The visual language should communicate **practical control**, not futuristic technology or enterprise theatre. The interface should help staff answer:
- What needs my attention?
- What stock is available, and where?
- What changed, why, and who recorded it?
- What action can I safely take next?

### Design principles

1. **Useful before beautiful:** Every visual element must aid scanning, understanding, or action.
2. **Clear hierarchy:** The primary task and most important exception should be easy to find.
3. **Quiet confidence:** Use restrained navy, neutral surfaces, and a limited teal accent.
4. **Operational density:** Show enough information to work efficiently, without cramming every metric onto one screen.
5. **Honest states:** Pending, failed, partial, and completed states must be distinct and truthful.
6. **Safety through clarity:** Explain consequences before stock-changing or destructive actions.
7. **Consistency over novelty:** Reuse common patterns for tables, filters, forms, confirmations, and status.
8. **Accessible by default:** Color is never the only status cue; focus, contrast, labels, and keyboard access matter.

## 2. Brand and voice

### Brand attributes
- Practical
- Simple
- Honest
- Traceable
- Dependable
- Filipino-built, without relying on cultural decoration

### Recommended product lockup
**SINOP**  
*Inventory & Warehouse Management*

**Tagline:** Every Stock Movement. Accounted For.

Supporting description:
> Practical inventory software built for Filipino businesses.

### Writing style
- Use short, direct labels and sentences.
- Prefer verbs that describe the task: “Receive,” “Dispatch,” “Transfer,” “Record loss,” “Import,” “Export.”
- Explain technical failures in plain language and include a next step.
- Never shame users for mistakes.
- Do not use hype, buzzwords, or unsupported guarantees.

**Prefer**
- “This SKU already has stock movements. Use stock reconciliation instead.”
- “12 rows need attention. Download the error report to review them.”
- “Transfer dispatched. Waiting for the destination warehouse to receive it.”

**Avoid**
- “Oopsie!”
- “Revolutionary AI-powered inventory experience.”
- “100% shrinkage prevention guaranteed.”
- “Success!” when only part of an operation completed.

## 3. Color tokens

Use semantic tokens rather than scattering raw hex values through components. The values below are a starting palette and must be checked against actual text/background contrast before release.

### Core palette

| Token | Suggested value | Purpose |
|---|---|---|
| `color.brand.navy` | `#142B4A` | Brand, primary navigation, strong text accents |
| `color.brand.navy-hover` | `#1D3B60` | Hover state for navy controls |
| `color.brand.teal` | `#0F766E` | Primary accent for positive actions and selected states |
| `color.brand.teal-hover` | `#115E59` | Hover state for teal controls |
| `color.neutral.0` | `#FFFFFF` | Main surface |
| `color.neutral.50` | `#F8FAFC` | App background / subtle section surface |
| `color.neutral.100` | `#F1F5F9` | Muted surface |
| `color.neutral.200` | `#E2E8F0` | Borders and dividers |
| `color.neutral.300` | `#CBD5E1` | Stronger borders / disabled controls |
| `color.neutral.500` | `#64748B` | Secondary text |
| `color.neutral.700` | `#334155` | Body text |
| `color.neutral.900` | `#0F172A` | Headings and high-emphasis text |

### Semantic colors

| Token | Suggested value | Use |
|---|---|---|
| `color.success` | `#166534` | Completed/valid |
| `color.success-bg` | `#F0FDF4` | Success surface |
| `color.warning` | `#92400E` | Needs review / approaching threshold |
| `color.warning-bg` | `#FFFBEB` | Warning surface |
| `color.danger` | `#B91C1C` | Rejected, destructive, critical failure |
| `color.danger-bg` | `#FEF2F2` | Error surface |
| `color.info` | `#1D4ED8` | Informational state |
| `color.info-bg` | `#EFF6FF` | Informational surface |

### Color rules
- Use navy for structural emphasis and primary navigation.
- Use teal sparingly for the main action, active selection, or meaningful positive emphasis.
- Use neutral surfaces for most of the application.
- Reserve semantic colors for their stated meanings; do not use them as decoration.
- Do not rely on color alone: pair status colors with text and, where helpful, an icon.
- Validate WCAG 2.2 AA contrast for text, icons, borders, and focus indicators in actual combinations.
- Do not introduce gradients, glowing effects, neon palettes, or multiple competing accent colors.

## 4. Typography

Use the application's existing, well-supported sans-serif stack unless there is an approved reason to change it. Prefer system-friendly fonts that render clearly on Windows and common browsers.

Suggested stack:
`Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif`

| Token | Size | Weight | Use |
|---|---:|---:|---|
| `type.xs` | 12px | 400–500 | Helper text, secondary metadata |
| `type.sm` | 14px | 400–500 | Dense table text, secondary labels |
| `type.base` | 16px | 400 | Default body and form input |
| `type.lg` | 18px | 500–600 | Section headings |
| `type.xl` | 20px | 600 | Page subheadings |
| `type.2xl` | 24px | 600–700 | Page title |
| `type.3xl` | 30px | 600–700 | Rare high-level overview heading |

### Typography rules
- Use sentence case for labels and headings.
- Use tabular numerals for quantities, money, and aligned numeric columns where available.
- Do not use large marketing-style headlines inside everyday operations screens.
- Keep helper text readable; do not solve density by shrinking everything.
- Use boldness to express hierarchy rather than using many font sizes.

## 5. Spacing, sizing, and shape

Use a consistent 4px base scale.

| Token | Value | Typical use |
|---|---:|---|
| `space.1` | 4px | Tight icon/text gap |
| `space.2` | 8px | Compact component gap |
| `space.3` | 12px | Form field internals |
| `space.4` | 16px | Standard component spacing |
| `space.5` | 20px | Related groups |
| `space.6` | 24px | Card/section spacing |
| `space.8` | 32px | Major section spacing |
| `space.10` | 40px | Page-level separation |
| `space.12` | 48px | Rare large separation |

### Radius
- Small controls: 6px.
- Inputs and buttons: 6–8px.
- Cards and panels: 8–12px.
- Dialogs/drawers: 12px.
- Avoid excessive pill shapes except for compact status badges or explicitly designed chips.

### Elevation and borders
- Prefer clear borders over heavy shadows.
- Use a subtle shadow only where it clarifies layering, such as a popover or modal.
- Avoid floating-card dashboards where every block appears equally important.
- Separate dense data tables with row dividers and whitespace, not ornamental containers.

## 6. Layout and responsive behavior

### Desktop-first operational layout
- Persistent navigation should make major modules easy to reach.
- Keep page title, primary action, and key context near the top.
- Put filters close to the table/report they affect.
- Align forms to a consistent content grid and group related fields.
- Use wide layouts for dense tables, but avoid full-width forms with excessive line lengths.
- Keep important record identity (SKU, warehouse, status) visible while reviewing details.

### Responsive behavior
- Desktop: prioritize efficient data scanning and multi-column forms where sensible.
- Tablet: reduce secondary columns, preserve key identifiers and primary actions.
- Mobile/small screens: stack forms, convert complex tables to a usable horizontal scroll or record-list presentation, and keep critical actions reachable.
- Never hide an authorization or safety requirement merely because the screen is small.

### Suggested page structure
1. Breadcrumb or section context, when useful.
2. Page title and concise explanatory subtitle.
3. Primary action aligned consistently.
4. Key alerts or pending-work notice, only if actionable.
5. Filters and search.
6. Main table or workflow content.
7. Pagination and secondary actions.

## 7. Components and interaction patterns

### Buttons
- **Primary:** one dominant action per view or form section; navy or approved teal treatment.
- **Secondary:** neutral background or outlined style.
- **Tertiary:** text action for low-impact options.
- **Destructive:** danger styling, explicit action label, and confirmation when consequential.
- Include visible hover, focus, active, disabled, and loading states.
- Prevent repeated submissions while a consequential action is processing.
- Button labels should state the action: “Confirm receipt,” not “OK.”

### Forms
- Every input has a visible label.
- Mark required fields consistently and explain non-obvious constraints.
- Put validation errors next to the relevant field; provide a summary for long forms.
- Preserve safe user input after validation failure where possible.
- Use appropriate controls for units, warehouses, statuses, and related records; avoid free text when a controlled relationship is required.
- Explain the business impact of stock-changing fields.
- Group long forms into meaningful sections rather than one giant field list.

### Tables
- Support server-side search, filtering, sorting, and pagination for large datasets.
- Keep SKU/code, item name, warehouse, quantity, status, and date easy to scan where relevant.
- Right-align numeric quantities and monetary values; use consistent precision and units.
- Use sticky headers only when they improve long-table usability.
- Row actions should be predictable and permission-aware.
- Avoid placing many icon-only actions in every row; use named actions or a compact overflow menu.
- Empty states should explain how to add/import records or clear filters.
- Show loading, no-results, and failure states distinctly.

### Status badges
- Use compact badges with short text.
- Always include a text label; color is supplementary.
- Use a consistent vocabulary across modules.
- Do not use status badges as decorative category labels.
- Suggested examples: Draft, Pending approval, In transit, Partially received, Received, Posted, Rejected, Reversed, Cancelled.

### Confirmation dialogs
Use confirmation for actions that post stock movements, finalize a transaction, cancel after stock effect, reverse a posted record, commit an import, or perform a destructive operation.

A confirmation should state:
1. What will happen.
2. Which record(s), warehouse(s), and quantities are affected.
3. Whether the action can be reversed and how.
4. The exact confirmation action.

Do not use generic “Are you sure?” copy when the consequence can be stated.

### Notifications and feedback
- Confirm only what actually succeeded.
- Distinguish full success, partial completion, validation failure, and system failure.
- For imports, provide totals for created, updated, skipped/rejected, plus a downloadable error report where applicable.
- For long-running operations, show progress or a clear queued state.
- Do not use a success toast as the only record of an important transaction.

### Filters and search
- Keep commonly used filters visible.
- Provide a clear way to remove one filter or reset all filters.
- Make active filters visible.
- Do not silently change the user's search or date range.
- Keep filter values when navigating back from a record if the framework permits it.

### Charts and dashboard cards
- Add a chart only when it answers a real operational question.
- Prefer exceptions, pending actions, and stock summaries over vanity metrics.
- Label the period, unit, and calculation basis.
- Provide a table or accessible equivalent for chart data.
- Avoid charts that imply precision unsupported by the underlying data.

## 8. Inventory-specific UI rules

### Quantities and units
- Display the unit beside every quantity.
- Keep on-hand, available, reserved, and in-transit values distinct where supported by the domain.
- Do not display a value as “available” unless reservation and in-transit semantics are defined.
- Use consistent decimal precision and rounding.
- Never permit an ordinary user to edit a derived stock total directly.

### Stock movement history
A movement detail should show, as applicable:
- SKU and product name.
- Warehouse and direction/effect.
- Quantity and unit.
- Movement/source type and linked record.
- Date/time and actor.
- Reason/reference.
- Reversal/correction linkage, if applicable.

### Transfers
Make source warehouse, destination warehouse, transfer state, dispatched quantity, received quantity, and discrepancy status easy to distinguish. Do not imply that dispatched stock has already been received.

### Import preview
- Clearly distinguish valid, warning, and rejected rows.
- Explain duplicate matching and update behavior before commit.
- Show row number, field, error reason, and a practical resolution.
- Show create/update/reject counts and any warnings before confirmation.
- Make it explicit whether commit is all-or-nothing or allows valid rows to proceed; the implementation contract must decide this.
- Opening-stock import must prominently state that it is a privileged setup flow and cannot overwrite existing balances.

### Export
- Let users see the selected dataset, filters, format, and approximate scope before export where feasible.
- Explain when an export excludes data because of permissions or filters.
- Treat exported files as potentially sensitive and protect download access.

## 9. Accessibility requirements

Target WCAG 2.2 AA for supported user-facing workflows.

- All controls must be keyboard accessible.
- Focus indicators must be visible and not rely only on a subtle color change.
- Form fields must have programmatic and visible labels.
- Errors must be associated with the relevant field and understandable without color.
- Status must be conveyed through text as well as color.
- Maintain adequate contrast for text and important UI boundaries.
- Use semantic headings and predictable focus order.
- Dialogs must manage focus and return it when closed.
- Do not rely on hover-only explanations.
- Respect reduced-motion preferences; avoid unnecessary animation.
- Provide accessible table headers and meaningful action names.

## 10. Motion and feedback

Motion should be minimal and functional:
- Short transitions for hover, focus, expansion, and dialog appearance.
- No animated backgrounds, parallax, bouncing elements, or decorative motion.
- Avoid animation that delays a user trying to complete a task.
- Respect reduced-motion preferences.
- Loading indicators should communicate that an action is in progress, not disguise a slow operation.

## 11. Iconography and imagery

- Prefer one consistent, legible outline icon set already available in the application.
- Use icons to reinforce labels, not replace important text.
- Avoid mixing outline, filled, and illustrated styles in the same navigation context.
- Use no decorative stock photos in core operational screens.
- The brand mark should be simple, scalable, and legible at favicon size.
- Do not use AI, blockchain, circuit-board, glowing-neon, or generic futuristic motifs.
- Filipino context may inform the brand story but is not required as a visual motif.

## 12. Data visualization rules

- Always name the metric and state the time range or scope.
- Show units and explain whether values are current snapshots or historical totals.
- Use consistent scales for comparisons.
- Avoid 3D charts and decorative gauges.
- Use warning/danger colors only for meaningful thresholds.
- Never fabricate data for empty states or demos without clearly labeling it as sample data.
- Provide a table or text summary for essential chart information.

## 13. Design tokens and implementation

- Keep tokens centralized in the project's existing styling system.
- Map semantic tokens to the framework's supported theme/configuration mechanism instead of hardcoding colors in individual pages.
- Reuse existing components and Filament styling conventions where they meet these requirements.
- Do not introduce a new UI framework, icon package, font package, or design-token dependency without review and approval.
- The current project stack uses Laravel, Filament, Livewire, Alpine, and Tailwind; verify the current repository configuration before implementing any specific API or theme hook.
- Do not assume component APIs from older Filament versions. Use the version installed in the repository and its current documentation.
- Ensure dark mode behavior is deliberately defined if the application supports it; do not auto-invert semantic colors without checking contrast.

### Suggested token names

```css
:root {
  --sinop-brand-navy: #142B4A;
  --sinop-brand-navy-hover: #1D3B60;
  --sinop-brand-teal: #0F766E;
  --sinop-brand-teal-hover: #115E59;

  --sinop-surface: #FFFFFF;
  --sinop-surface-subtle: #F8FAFC;
  --sinop-surface-muted: #F1F5F9;
  --sinop-border: #E2E8F0;

  --sinop-text-primary: #0F172A;
  --sinop-text-secondary: #475569;
  --sinop-text-muted: #64748B;

  --sinop-success: #166534;
  --sinop-success-bg: #F0FDF4;
  --sinop-warning: #92400E;
  --sinop-warning-bg: #FFFBEB;
  --sinop-danger: #B91C1C;
  --sinop-danger-bg: #FEF2F2;
  --sinop-info: #1D4ED8;
  --sinop-info-bg: #EFF6FF;

  --sinop-radius-control: 0.375rem;
  --sinop-radius-panel: 0.625rem;
  --sinop-space-unit: 0.25rem;
}
```

These are reference token names, not a requirement to create a separate CSS file if the existing Tailwind/Filament theme can represent them more appropriately.

## 14. Page-level design guidance

### Overview dashboard
- Start with pending tasks and important exceptions.
- Show concise stock summaries by warehouse or category where useful.
- Provide clear drill-down links.
- Avoid a wall of equally weighted KPI cards.
- Never show a metric without its definition and date/scope.

### Stock list
- Default to a useful search and filter experience.
- Make SKU, product, warehouse, on-hand quantity, and status easy to scan.
- Distinguish current derived stock from any count snapshot or in-transit value.
- Provide a direct path to movement history.

### Product/variant detail
- Put identity and SKU near the top.
- Group variant attributes, units, current prices, and status.
- Show stock by warehouse and recent movement history.
- Keep editing master data separate from posting inventory movements.

### Transaction detail
- Show current workflow status, source record, responsible actors, dates, and stock effect.
- Make next allowed action obvious.
- Explain why a transition is unavailable.
- Preserve posted transaction history.

### Import workflow
- Use a clear step sequence: Select file → Validate and preview → Review conflicts → Confirm → Results.
- Never commit on file selection alone.
- Make the opening-stock privilege and restrictions unmistakable.

### Reports
- Put filters and reporting scope up front.
- Display the generated time and relevant warehouse/date range.
- Provide an export action where authorized.
- Prefer actionable data tables over decorative visualization.

## 15. Definition of done for UI work

A UI change is ready only when:
- It follows the product voice and token system.
- Layout works at supported desktop and smaller viewport sizes.
- Keyboard navigation and visible focus work.
- Inputs have labels and useful validation feedback.
- Loading, empty, error, disabled, and success states are considered.
- Permission restrictions are enforced server-side, not only hidden in the UI.
- Stock-changing actions clearly communicate warehouse, SKU, quantity, and consequence.
- Tables remain usable with realistic data volume.
- Semantic colors are not the only status cue.
- Automated tests and required project checks pass.
- Screenshots or visual evidence are supplied for substantial visual changes where the project workflow expects them.

## 16. Open design decisions

- Final brand/name clearance.
- Whether dark mode is supported and its contrast-tested tokens.
- Exact responsive breakpoints based on the existing application.
- Final role-specific navigation and actions.
- Exact status vocabulary and color mapping for every workflow.
- Chart library and reporting interaction, if charts are required.
- Exact empty/loading patterns supported by current Filament components.

Do not resolve these by adding dependencies or inventing business rules without approval.
