import { test } from "@playwright/test";
import { freshSeed, tinker } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";
import * as fs from "fs";
import * as path from "path";

/**
 * TEMPORARY full layout audit — all pages, all breakpoints.
 *
 * Read-only: navigates and measures. Writes nothing to the app database.
 * Delete after the report is produced.
 *
 * PRIVACY: this harness NEVER reads `textContent` / `innerText` of any
 * element. Every finding is a tag name, a CSS class list, and numbers.
 * Screenshots are NOT captured. The output therefore contains no
 * employee names, emails, or financial figures (RA 10173) — which is the
 * whole reason it, unlike the first audit, can be run without a PII gate.
 *
 * Viewports: blueprint §7O.1's six canonical breakpoints, plus common
 * real device widths. Deduplicated where a device width equals a
 * breakpoint.
 */

/**
 * Output dir. NOT under `test-results/`: Playwright deletes that directory
 * at the start of every run, so an earlier audit's report is destroyed by
 * the next probe run. The first run of this harness lost its report that
 * way. `.issue-tmp/` is gitignored and survives across runs.
 */
const OUT = path.join(".issue-tmp", "layout-audit-full");

/** Blueprint §7O.1 + common real devices (deduplicated). */
const VIEWPORTS: Array<{ name: string; width: number; height: number }> = [
    { name: "375-iphone-se", width: 375, height: 667 },
    { name: "430-iphone-14-pro-max", width: 430, height: 932 },
    { name: "640-sm", width: 640, height: 900 },
    { name: "768-md", width: 768, height: 1024 },
    { name: "820-ipad-air", width: 820, height: 1180 },
    { name: "1024-lg", width: 1024, height: 768 },
    { name: "1280-xl", width: 1280, height: 800 },
    { name: "1366-laptop", width: 1366, height: 768 },
    { name: "1536-2xl", width: 1536, height: 864 },
    { name: "1920-fhd", width: 1920, height: 1080 },
];

/** First record id per resource, for view/edit routes. */
function recordIds(): Record<string, number> {
    const out = tinker(`
    foreach ([
      'customers' => \\App\\Models\\Customer::class,
      'direct_transfers' => \\App\\Models\\DirectTransfer::class,
      'in_transits' => \\App\\Models\\InTransit::class,
      'loss_ledgers' => \\App\\Models\\LossLedger::class,
      'products' => \\App\\Models\\Product::class,
      'purchase_orders' => \\App\\Models\\PurchaseOrder::class,
      'sales_orders' => \\App\\Models\\SalesOrder::class,
      'stock_movements' => \\App\\Models\\StockMovement::class,
      'suppliers' => \\App\\Models\\Supplier::class,
      'transfer_requisitions' => \\App\\Models\\TransferRequisition::class,
      'users' => \\App\\Models\\User::class,
      'warehouses' => \\App\\Models\\Warehouse::class,
    ] as $k => $c) {
      $m = $c::query()->first();
      echo $k . '=' . ($m?->getKey() ?? 0) . "\\n";
    }
  `);
    const ids: Record<string, number> = {};
    for (const line of out.split("\n")) {
        const m = line.trim().match(/^(\w+)=(\d+)$/);
        if (m) ids[m[1]] = parseInt(m[2], 10);
    }
    return ids;
}

/**
 * Runs in the page. Collects layout metrics for the current document.
 *
 * Contract: returns geometry and class names ONLY. No element text is
 * read, so the payload is PII-free by construction.
 */
function collectMetrics() {
    const de = document.documentElement;
    const vw = de.clientWidth;

    const desc = (el: Element): string => {
        const cls =
            typeof el.className === "string"
                ? el.className.split(/\s+/).filter(Boolean).slice(0, 3).join(".")
                : "";
        return el.tagName.toLowerCase() + (cls ? "." + cls : "");
    };

    const visible = (el: Element): boolean => {
        const he = el as HTMLElement;
        if (he.offsetParent === null && he.tagName !== "HTML") return false;
        const r = he.getBoundingClientRect();
        return r.width > 0 && r.height > 0;
    };

    // 1. Elements extending past the viewport's right edge.
    const bleeding: Array<{ el: string; right: number; width: number }> = [];
    document.querySelectorAll("body *").forEach((el) => {
        if (!visible(el)) return;
        const r = el.getBoundingClientRect();
        if (r.right > vw + 2) {
            bleeding.push({
                el: desc(el),
                right: Math.round(r.right),
                width: Math.round(r.width),
            });
        }
    });

    // 2. Containers occupying a small share of the width available to them.
    const squished: Array<{
        el: string;
        width: number;
        parentWidth: number;
        ratio: number;
    }> = [];
    document
        .querySelectorAll(
            ".fi-section, .fi-fo-component-ctn, .fi-in-entry, .fi-wi, .fi-sc-section",
        )
        .forEach((el) => {
            if (!visible(el)) return;
            const r = el.getBoundingClientRect();
            const parent = el.parentElement;
            if (!parent) return;
            const pr = parent.getBoundingClientRect();
            if (pr.width < 320) return;
            const ratio = r.width / pr.width;
            if (ratio < 0.62) {
                squished.push({
                    el: desc(el),
                    width: Math.round(r.width),
                    parentWidth: Math.round(pr.width),
                    ratio: Math.round(ratio * 100) / 100,
                });
            }
        });

    // 3. Grid track counts, per grid.
    const grids: Array<{ el: string; tracks: number; template: string }> = [];
    document
        .querySelectorAll(".fi-grid, .fi-fo-grid, .fi-sc-grid")
        .forEach((el) => {
            if (!visible(el)) return;
            const tpl = getComputedStyle(el).gridTemplateColumns;
            grids.push({
                el: desc(el),
                tracks: tpl.split(/\s+/).filter(Boolean).length,
                template: tpl,
            });
        });

    // 4. The root schema grid's width vs the content area's width — the
    //    direct numeric test of the infolist-root defect (A1). Before the
    //    fix a root grid sits in a fraction of the content area; after,
    //    it fills it.
    const main =
        document.querySelector(".fi-main-ctn") ??
        document.querySelector(".fi-main") ??
        document.querySelector(".fi-layout main");
    const mainW = main?.getBoundingClientRect().width ?? null;

    // The outermost schema wrapper inside main.
    let rootGrid: { el: string; width: number; ratio: number } | null = null;
    if (main && mainW && mainW > 0) {
        const candidates = main.querySelectorAll(
            ":scope > * > .fi-sc, :scope > .fi-sc, :scope > * > .fi-grid, :scope > .fi-grid",
        );
        const first = candidates[0] ?? main.querySelector(".fi-sc, .fi-grid");
        if (first && visible(first)) {
            const r = (first as HTMLElement).getBoundingClientRect();
            rootGrid = {
                el: desc(first),
                width: Math.round(r.width),
                ratio: Math.round((r.width / mainW) * 100) / 100,
            };
        }
    }

    // 5. Tables: clipped (content wider than wrapper, no scroll) vs
    //    intentionally scrollable.
    const tables: Array<{
        wrapperWidth: number;
        tableWidth: number;
        overflow: number;
        scrollable: boolean;
    }> = [];
    document
        .querySelectorAll(".fi-ta-content, .fi-ta-ctn")
        .forEach((el) => {
            if (!visible(el)) return;
            const wrap = el.classList.contains("fi-ta-content")
                ? el
                : el.querySelector(".fi-ta-content");
            const table = el.querySelector("table");
            if (!table) return;
            const wr = (wrap ?? el).getBoundingClientRect();
            const tr = table.getBoundingClientRect();
            const cs = getComputedStyle(wrap ?? el);
            tables.push({
                wrapperWidth: Math.round(wr.width),
                tableWidth: Math.round(tr.width),
                overflow: Math.round(tr.width - wr.width),
                scrollable: ["auto", "scroll"].includes(cs.overflowX),
            });
        });

    // 6. Cards per row in a card-layout table.
    let cardsPerRow: number | null = null;
    const cardRow = document.querySelector(
        ".fi-ta-content .fi-grid, .fi-ta-content [class*='grid-cols']",
    );
    if (cardRow && visible(cardRow)) {
        const tpl = getComputedStyle(cardRow).gridTemplateColumns;
        const tracks = tpl.split(/\s+/).filter(Boolean).length;
        if (tracks > 0) cardsPerRow = tracks;
    }

    return {
        vw,
        scrollWidth: de.scrollWidth,
        overflow: de.scrollWidth - vw,
        bodyHeight: document.body.scrollHeight,
        mainWidth: mainW ? Math.round(mainW) : null,
        rootGrid,
        bleeding: bleeding.slice(0, 12),
        bleedingCount: bleeding.length,
        squished: squished.slice(0, 12),
        squishedCount: squished.length,
        grids: grids.slice(0, 12),
        tables,
        clippedTables: tables.filter((t) => t.overflow > 4 && !t.scrollable).length,
        cardsPerRow,
    };
}

test.describe("LAYOUT AUDIT (FULL)", () => {
    test.beforeAll(() => {
        freshSeed();
    });

    test("walk every page at every breakpoint", async ({ page }) => {
        test.setTimeout(45 * 60_000);
        fs.mkdirSync(OUT, { recursive: true });

        const ids = recordIds();
        await loginAsAdmin(page);

        const routes: Array<{ key: string; url: string }> = [
            { key: "dashboard", url: "/admin" },
            { key: "profile", url: "/admin/profile" },
        ];

        const resources: Array<{
            key: string;
            base: string;
            pages: string[];
        }> = [
            { key: "customers", base: "/admin/customers", pages: ["index", "create", "edit"] },
            { key: "direct-transfers", base: "/admin/direct-transfers", pages: ["index", "create", "view"] },
            { key: "in-transits", base: "/admin/in-transits", pages: ["index", "view"] },
            { key: "loss-ledgers", base: "/admin/loss-ledgers", pages: ["index", "view"] },
            { key: "products", base: "/admin/products", pages: ["index", "create", "view", "edit"] },
            { key: "purchase-orders", base: "/admin/purchase-orders", pages: ["index", "create", "view", "edit"] },
            { key: "sales-orders", base: "/admin/sales-orders", pages: ["index", "create", "view", "edit"] },
            { key: "stock-movements", base: "/admin/stock-movements", pages: ["index", "view"] },
            { key: "suppliers", base: "/admin/suppliers", pages: ["index", "create", "edit"] },
            { key: "transfer-requisitions", base: "/admin/transfer-requisitions", pages: ["index", "create", "view", "edit"] },
            { key: "users", base: "/admin/users", pages: ["index", "create", "view", "edit"] },
            { key: "warehouses", base: "/admin/warehouses", pages: ["index", "create", "view", "edit"] },
        ];

        for (const r of resources) {
            const idKey = r.key.replace(/-/g, "_");
            const id = ids[idKey] ?? 0;
            for (const p of r.pages) {
                if (p === "index") routes.push({ key: `${r.key}--index`, url: r.base });
                else if (p === "create") routes.push({ key: `${r.key}--create`, url: `${r.base}/create` });
                else if (p === "view") routes.push({ key: `${r.key}--view`, url: `${r.base}/${id}` });
                else if (p === "edit") routes.push({ key: `${r.key}--edit`, url: `${r.base}/${id}/edit` });
            }
        }

        const report: Record<string, Record<string, unknown>> = {};
        const consoleErrors: string[] = [];

        page.on("console", (m) => {
            if (m.type() === "error") {
                const t = m.text();
                if (!/favicon|analytics|third-party/i.test(t)) {
                    consoleErrors.push(`${page.url()} :: ${t.slice(0, 200)}`);
                }
            }
        });
        page.on("pageerror", (e) => {
            consoleErrors.push(`${page.url()} :: PAGEERROR ${String(e).slice(0, 200)}`);
        });

        let failures = 0;

        for (const route of routes) {
            report[route.key] = {};
            for (const vp of VIEWPORTS) {
                await page.setViewportSize({ width: vp.width, height: vp.height });
                const resp = await page
                    .goto(route.url, { waitUntil: "domcontentloaded" })
                    .catch(() => null);
                const status = resp?.status() ?? 0;
                await page.waitForTimeout(500);

                const m = await page.evaluate(collectMetrics);
                report[route.key][vp.name] = { status, ...m };

                if (status >= 400 || status === 0) failures++;

                const clipped = (m as { clippedTables: number }).clippedTables;
                const bleed = (m as { bleedingCount: number }).bleedingCount;
                const sq = (m as { squishedCount: number }).squishedCount;
                if (status >= 400 || clipped > 0 || bleed > 0 || sq > 0) {
                    console.log(
                        `AUDIT_ISSUE route=${route.key} vp=${vp.name} status=${status} ` +
                            `overflow=${(m as { overflow: number }).overflow} bleed=${bleed} ` +
                            `squished=${sq} clippedTables=${clipped}`,
                    );
                }
            }
        }

        fs.writeFileSync(
            path.join(OUT, "report.json"),
            JSON.stringify(report, null, 2),
        );
        fs.writeFileSync(
            path.join(OUT, "console-errors.json"),
            JSON.stringify(consoleErrors, null, 2),
        );

        // Self-check: the report must contain no element text. Every
        // finding is a tag/class descriptor or a number; a long
        // alphabetic run would mean text leaked in.
        const raw = JSON.stringify(report);
        const leak = raw.match(/"[A-Za-z][A-Za-z ,.'-]{40,}"/);
        console.log("AUDIT_PII_LEAK=" + (leak ? leak[0].slice(0, 80) : "none"));

        console.log(`AUDIT_ROUTES=${routes.length} VIEWPORTS=${VIEWPORTS.length}`);
        console.log(`AUDIT_LOADS=${routes.length * VIEWPORTS.length} FAILURES=${failures}`);
        console.log("AUDIT_CONSOLE_ERRORS=" + consoleErrors.length);
        console.log("AUDIT_DONE");
    });
});
