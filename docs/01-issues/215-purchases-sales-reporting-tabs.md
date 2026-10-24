# Issue #215 — Purchases & Sales Reporting Tabs Design Document

## Problem & Context
Operators require unified analytics panels within Filament to evaluate procurement performance and sales revenue velocity without slowing down database transactions.

## Recommendation & Specifications
1. **Reporting Structure:**
   - **Purchases Tab:** Total Spend, PO Cycle Time, Supplier Fulfill Rate, Top Vendors by Spend, Pending Receipts.
   - **Sales Tab:** Total Revenue, Gross Margin (fed by #212 COGS), Top Performing Variants, Orders by Warehouse.
2. **Filters & Preset Integration:**
   - Presets: Today, 7 Days, 30 Days, 90 Days, Year-to-Date (YTD), Quarter-to-Date (QTD), Custom Range (integrating with #217 presets).
3. **Performance & Query Optimization:**
   - Implement database index strategy on `created_at`, `status`, `warehouse_id`.
   - Utilize Livewire async loading for heavy summary widgets.
   - Cache aggregate metrics with 15-minute expiration tags.
4. **Export Requirements:**
   - CSV and XLSX exports using chunked streaming queries (`OpenSpout`/`League CSV`).
