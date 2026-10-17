# Issue: Admin layout audit — infolist width loss, Users table overflow, dashboard card squish

- **Date:** 2026-10-16
- **Status:** Open (audit only — no fix applied)
- **Severity:** MEDIUM (visual/UX; no data or security impact)
- **Scope:** All Filament admin surfaces — pages, sections, infolists, tables, forms, modals
- **Method:** Standalone Playwright measurement at 1440 / 1280 / 768 / 375 px viewports
- **Branch audited:** `develop` (no code changed)

## Summary

Four confirmed defects. Everything else measured clean.

| # | Surface | Defect | Severity |
|---|---|---|---|
| 1 | 9 infolist view pages | Outer grid missing `columnSpanFull()` → cards render at 349/163/536 px in a 1096 px area, ~248 px dead space | MEDIUM |
| 2 | `/admin/users` index | Three labelled row actions widen the actions cell to 334 px → table 1255 px in a 1096 px wrapper, forced horizontal scroll | LOW |
| 3 | Dashboard quick actions | 4 cards sized against a 2-column grid → 125×276 px at 1440 (taller than wide), 78×276 px at 768 | MEDIUM |
| 4 | Dashboard chart widgets | Oversized — up to 1048×699 px per chart | LOW |

Retracted after re-measurement (recorded here so they are not re-reported):

- "Uneven infolist rows / dashboard rows" — a mid-load artifact. Once widgets finish loading, `uneven: 0` on every dashboard row at 1440.
- "Dashboard widgets hang at 768/375" — not a bug. `before 3–4` skeletons → `after 0` once the page is scrolled. Widgets lazy-load on scroll via Livewire intersection loading.
- "Users table clipped" — it does not clip. The inner `.fi-ta-content-ctn` is `overflow-x: auto`, so the table scrolls. The real (smaller) finding is that it *has* to scroll.

## Finding 1 — Infolist view pages waste ~248 px and mis-size their cards (highest impact)

**Symptom.** Every view page except Users lays its cards out in roughly three-quarters of the available width and left-aligns them, leaving a dead gap on the right. Cards are sized 349 / 163 / 536 px instead of balanced thirds.

**Measured at 1440 px** (page content area = 1096 px):

```
products--view           Identity  x=312 w=349 | Pricing    x=685 w=163 | Unit conversions x=312 w=536
purchase-orders--view    Profile   x=312 w=349 | Sign-offs  x=685 w=163 | Line items       x=312 w=536
sales-orders--view       Profile   x=312 w=349 | Sign-offs  x=685 w=163 | Line items       x=312 w=536
transfer-requisitions--view Profile x=312 w=349 | Sign-offs x=685 w=163 | Manifest         x=312 w=536
direct-transfers--view   Profile   x=312 w=349 | Authorization x=685 w=163 | Manifest      x=312 w=536
warehouses--view         Profile   x=312 w=349 | Status     x=685 w=163 | Assigned staff   x=312 w=536
transfer-requisitions / stock-movements / in-transits / loss-ledgers — same shape

users--view (CORRECT)    x=312 w=723 | x=1059 w=349 | x=312 w=1096
```

**Root cause — proven by measured ancestor chains, not inferred.**

Walking up from the first section on each page:

```
broken:products / purchase-orders / warehouses
  0 .fi-sc-section                                   w=349
  2 .fi-grid-col  md:fi-grid-col-span xl:fi-grid-col-span  w=349
  3 .fi-grid  md:fi-grid-cols xl:fi-grid-cols        w=536   tracks: 162.6 162.6 162.7
  7 .fi-grid  lg:fi-grid-cols                        w=1096  tracks: 536 536
  8 .fi-sc-component                                 w=1096

correct:users
  0 .fi-sc-section                                   w=723
  2 .fi-grid-col  lg:fi-grid-col-span                w=723
  3 .fi-grid  lg:fi-grid-cols                        w=1096  tracks: 349.3 349.3 349.3
```

The broken pages' infolist root sits inside a **536 px** `.fi-grid-col` — a single track of the page-level 2-column grid. The inner 3-column grid therefore divides 536 px, giving the 162 px tracks and the observed 349 / 163 card widths. `users--view`'s infolist root owns the **full 1096 px**, so its 3-column grid divides correctly.

The difference is one call:

| File | Root grid | `->columnSpanFull()` |
|---|---|---|
| `app/Filament/Resources/Users/Schemas/UserInfolist.php:21` | `Grid::make(3)` | **present (line 22)** |
| all 9 others | `Grid::make(['default' => 1, 'md' => N, 'xl' => N])` | **absent** |

Affected files (all under `app/Filament/Resources/*/Schemas/`):

```
DirectTransferInfolist.php:21
InTransitInfolist.php:22
LossLedgerInfolist.php:18
ProductInfolist.php:31
PurchaseOrderInfolist.php:21
SalesOrderInfolist.php:21
StockMovementInfolist.php:21
TransferRequisitionInfolist.php:21
WarehouseInfolist.php:24
```

**Not the cause:** the array-vs-integer `Grid::make()` form. An earlier hypothesis blamed the array form's explicit `default => 1` suppressing the base `fi-grid-cols` class. The measured classes show the nested grids *do* receive `md:fi-grid-cols xl:fi-grid-cols` and *do* resolve 3 tracks — the column count is correct. Only the parent's width is wrong. `columnSpanFull()` is the single differentiator.

**Impact:** 9 of 10 view pages, the most-visited read surface in the admin. Cards are unreadably narrow — the `Pricing`/`Sign-offs` column renders at 163 px.

## Finding 2 — `/admin/users` index forces horizontal scroll

**Measured at 1440 px:**

```
table width      1255
wrapper width    1096
needs scroll      159
actions cell      334      <-- the widest cell by 104 px
```

Every other index list fits its wrapper:

```
stock-movements     table 1096  wrapper 1096  needs scroll   0   actions cell  89
in-transits         table 1096  wrapper 1096  needs scroll   0
loss-ledgers        table 1096  wrapper 1096  needs scroll   0
```

**Cause:** `app/Filament/Resources/Users/Tables/UsersTable.php` declares three labelled record actions — Edit, Reassign warehouses, Delete. Filament renders them **inline**, not collapsed into an overflow menu, so the actions cell needs 334 px. The table does not clip; `.fi-ta-content-ctn` is `overflow-x: auto` and scrolls, which is why the leftmost name column is cut off in the default view.

**Minor side observation:** `EditAction::make()->modalWidth(Width::Large)` has no effect — the Edit action renders as a link (`fi-ac-link-action` with an `href`), not a modal.

## Finding 3 — Dashboard quick-action cards are squished

**Measured:**

| Viewport | Card size | Card count |
|---|---|---|
| 1440 | 125 × 276 px | 4 |
| 768 | 78 × 276 px | 4 |
| 375 | ~78 × 128 px | 4 |

Cards are taller than wide by 2.2× at 1440 and 3.5× at 768, so the labels wrap to vertical ("New / transfer").

**Cause — arithmetic-confirmed.** `app/Filament/Widgets/QuickActionsWidget.php:25` declares `$columnSpan = ['default' => 1, 'md' => 2, 'xl' => 4]`, but `app/Filament/Pages/Dashboard.php:29` gives the dashboard only **2** columns at md/lg/xl. A span of 4 in a 2-column grid clamps to 2 *tracks*, i.e. the full 1096 px — but the widget shares its row with `PendingFulfillmentWidget`, so it receives one 536 px track. `resources/views/filament/widgets/quick-actions.blade.php:2` then splits that 536 px into `md:grid-cols-4`: 4 × 125 px + 3 × 12 px gap = 536 px exactly.

Compare `TopSellingVariantsWidget.php:23` — `['default' => 1, 'md' => 1, 'xl' => 1]` — which renders correctly at 536 px.

## Finding 4 — Dashboard charts are oversized

Chart canvas sizes at 1440: 1048×699 (Sales Revenue Trend, Sales vs Purchases), 488×325 (Inventory Value, Net Movement, Top Selling Variants). Two full-width charts at ~700 px tall each produce a ~3300 px dashboard that is mostly chart.

## Verified clean

- **No document-level horizontal overflow anywhere.** `docOverflow = 0` across all 41 routes × 3 viewports, all 10 index lists × 3, all 6 create forms, all 2 edit forms.
- **Console errors: 0** on all audited surfaces. (An earlier count of 6 came from the sweep including `/admin/in-transits/0` and `/admin/loss-ledgers/0` — 404s caused by empty tables in the test database returning `first()` = null, not a defect.)
- **Create/edit forms.** Fields lay out in even 512 / 536 / 1048 px columns; zero narrow or orphaned fields on products, users, purchase-orders, direct-transfers. Behaviour at 768 is correct (2 columns collapse to full width).
- **Card-grid index lists** (products, warehouses, purchase-orders, sales-orders, direct-transfers, transfer-requisitions) — 3 tracks at 1440, 2 at 768, 1 at 375. Cards 339×193 / 328×193 / 311×284 px. No cramped text.
- **Modal** (`Reassign warehouses`) — centred, ~896 px wide, internally consistent. No overflow.
- **Dashboard rows** at 1440 (fully loaded) — every row's children share an identical width (`uneven: 0`).
- **`users--view`** — reference implementation, correct.
- **Users index at 375 / stock-movements at 375** — stacked mobile cards render correctly.

## Suggested fixes (not applied — none authorised)

1. Finding 1: add `->columnSpanFull()` to the root `Grid::make(...)` in the 9 infolists. One line each. Aligns them with `UserInfolist`.
2. Finding 3: change `QuickActionsWidget` `$columnSpan` to `['default' => 1, 'md' => 2, 'xl' => 2]`, matching the dashboard's actual 2-column grid. Alternatively raise `Dashboard::getColumns()`.
3. Finding 2: collapse the three Users row actions into an `ActionGroup`, or shorten the Reassign label.
4. Finding 4: reduce chart height via `getMaxHeight`/chart options, or make the two large charts share a row.

## Evidence

Screenshots and raw measurements: `.issue-tmp/layout-audit/` (temporary, untracked). Key files:
`final.json` (ancestor chains, form fields), `tables.json`, `cards.json`, `quickactions.json`, `dashboard.json`, `surfaces.json`, `lazy.json`, `dom.json`, plus 40+ full-page PNGs.

## Out of scope

Accessibility, contrast, keyboard navigation, and dark-mode rendering were not audited — this audit covers layout and sizing only.
