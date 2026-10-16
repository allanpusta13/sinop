import { test } from "@playwright/test";
import { freshSeed, tinker } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";
import * as fs from "fs";
import * as path from "path";

/**
 * TEMPORARY layout audit harness (visual-design-audit skill).
 *
 * Read-only: navigates, measures, screenshots. Writes nothing to the app
 * database. Deleted after the audit report is produced.
 *
 * Measures, per page per viewport:
 *   - document horizontal overflow
 *   - elements bleeding past the right edge of the viewport
 *   - content containers that occupy a small fraction of available width
 *     ("squished into one side")
 *   - grid track counts
 */

const OUT = path.join("test-results", "layout-audit");

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

const VIEWPORTS = [
    { name: "mobile-375", width: 375, height: 812 },
    { name: "tablet-768", width: 768, height: 1024 },
    { name: "desktop-1440", width: 1440, height: 900 },
];

/** Runs in the page. Collects layout metrics for the current document. */
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

    // Elements extending past the viewport's right edge.
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

    // Content containers occupying a small share of the available width.
    const squished: Array<{
        el: string;
        width: number;
        parentWidth: number;
        ratio: number;
    }> = [];
    document
        .querySelectorAll(".fi-section, .fi-fo-component-ctn, .fi-in-entry, .fi-wi")
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

    // Grid track counts.
    const grids: Array<{ el: string; tracks: number; template: string }> = [];
    document.querySelectorAll(".fi-grid, .fi-fo-grid").forEach((el) => {
        if (!visible(el)) return;
        const tpl = getComputedStyle(el).gridTemplateColumns;
        grids.push({
            el: desc(el),
            tracks: tpl.split(/\s+/).filter(Boolean).length,
            template: tpl,
        });
    });

    // Main content region vs viewport.
    const main = document.querySelector(".fi-main, .fi-layout main");
    const mainR = main?.getBoundingClientRect();

    return {
        vw,
        scrollWidth: de.scrollWidth,
        overflow: de.scrollWidth - vw,
        bodyHeight: document.body.scrollHeight,
        mainWidth: mainR ? Math.round(mainR.width) : null,
        mainRatio: mainR ? Math.round((mainR.width / vw) * 100) / 100 : null,
        bleeding: bleeding.slice(0, 12),
        bleedingCount: bleeding.length,
        squished: squished.slice(0, 12),
        squishedCount: squished.length,
        grids: grids.slice(0, 10),
    };
}

type PageReport = Record<string, unknown>;

test.describe("LAYOUT AUDIT", () => {
    test.beforeAll(() => {
        freshSeed();
    });

    test("walk every page at three viewports", async ({ page }) => {
        test.setTimeout(30 * 60_000);
        fs.mkdirSync(OUT, { recursive: true });

        const ids = recordIds();
        console.log("AUDIT_IDS=" + JSON.stringify(ids));

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

        const report: Record<string, PageReport> = {};
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

        for (const route of routes) {
            report[route.key] = {};
            for (const vp of VIEWPORTS) {
                await page.setViewportSize({ width: vp.width, height: vp.height });
                const resp = await page
                    .goto(route.url, { waitUntil: "domcontentloaded" })
                    .catch(() => null);
                const status = resp?.status() ?? 0;
                await page.waitForTimeout(700);

                const m = await page.evaluate(collectMetrics);
                (report[route.key] as Record<string, unknown>)[vp.name] = {
                    status,
                    ...m,
                };

                await page.screenshot({
                    path: path.join(OUT, `${route.key}__${vp.name}.png`),
                    fullPage: true,
                });
            }
            const d = (report[route.key] as Record<string, Record<string, number>>)["desktop-1440"];
            console.log(
                `AUDIT_PAGE=${route.key} status=${d.status} overflow=${d.overflow} ` +
                    `bleeding=${d.bleedingCount} squished=${d.squishedCount} mainRatio=${d.mainRatio}`,
            );
        }

        fs.writeFileSync(
            path.join(OUT, "report.json"),
            JSON.stringify(report, null, 2),
        );
        fs.writeFileSync(
            path.join(OUT, "console-errors.json"),
            JSON.stringify(consoleErrors, null, 2),
        );
        console.log("AUDIT_CONSOLE_ERRORS=" + consoleErrors.length);
        console.log("AUDIT_DONE=" + routes.length + " routes");
    });
});
