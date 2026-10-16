import { test } from "@playwright/test";
import { tinker } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";
import * as fs from "fs";

/**
 * Final audit pass: measurements + screenshots in one run, so the JSON
 * survives alongside the PNGs.
 */

const OUT = "test-results/layout-audit";

test.describe("AUDIT FINAL", () => {
    test("measure and capture", async ({ page }) => {
        test.setTimeout(20 * 60_000);
        fs.mkdirSync(OUT, { recursive: true });

        const raw = tinker(`
      foreach ([
        'customers' => \\App\\Models\\Customer::class,
        'direct_transfers' => \\App\\Models\\DirectTransfer::class,
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
        for (const line of raw.split("\n")) {
            const m = line.trim().match(/^(\w+)=(\d+)$/);
            if (m) ids[m[1]] = parseInt(m[2], 10);
        }

        await loginAsAdmin(page);

        const targets: Array<{ key: string; url: string; w: number }> = [
            { key: "dashboard", url: "/admin", w: 1440 },
            { key: "dashboard", url: "/admin", w: 768 },
            { key: "dashboard", url: "/admin", w: 375 },
            { key: "users--index", url: "/admin/users", w: 1440 },
            { key: "users--index", url: "/admin/users", w: 768 },
            { key: "users--index", url: "/admin/users", w: 375 },
            { key: "users--view", url: `/admin/users/${ids.users}`, w: 1440 },
            { key: "users--edit", url: `/admin/users/${ids.users}/edit`, w: 1440 },
            { key: "users--create", url: "/admin/users/create", w: 1440 },
            { key: "products--view", url: `/admin/products/${ids.products}`, w: 1440 },
            { key: "products--edit", url: `/admin/products/${ids.products}/edit`, w: 1440 },
            { key: "products--create", url: "/admin/products/create", w: 1440 },
            { key: "purchase-orders--view", url: `/admin/purchase-orders/${ids.purchase_orders}`, w: 1440 },
            { key: "sales-orders--view", url: `/admin/sales-orders/${ids.sales_orders}`, w: 1440 },
            { key: "transfer-requisitions--view", url: `/admin/transfer-requisitions/${ids.transfer_requisitions}`, w: 1440 },
            { key: "direct-transfers--view", url: `/admin/direct-transfers/${ids.direct_transfers}`, w: 1440 },
            { key: "direct-transfers--create", url: "/admin/direct-transfers/create", w: 768 },
            { key: "warehouses--view", url: `/admin/warehouses/${ids.warehouses}`, w: 1440 },
            { key: "suppliers--create", url: "/admin/suppliers/create", w: 1440 },
            { key: "customers--edit", url: `/admin/customers/${ids.customers}/edit`, w: 1440 },
            { key: "in-transits--index", url: "/admin/in-transits", w: 1440 },
            { key: "loss-ledgers--index", url: "/admin/loss-ledgers", w: 1440 },
            { key: "stock-movements--index", url: "/admin/stock-movements", w: 1440 },
            { key: "stock-movements--index", url: "/admin/stock-movements", w: 768 },
            { key: "stock-movements--index", url: "/admin/stock-movements", w: 375 },
        ];

        const out: any = {};

        for (const t of targets) {
            await page.setViewportSize({ width: t.w, height: 900 });
            await page.goto(t.url, { waitUntil: "domcontentloaded" });
            await page.waitForTimeout(1300);

            out[`${t.key}__w${t.w}`] = await page.evaluate(() => {
                const vis = (el: Element) => {
                    const r = el.getBoundingClientRect();
                    return r.width > 1 && r.height > 1;
                };
                const label = (el: Element) =>
                    el.querySelector(".fi-section-header-heading")?.textContent?.trim() ||
                    el.className.slice(0, 30);

                const sections: any[] = [];
                document.querySelectorAll(".fi-section, .fi-sc-section").forEach((el) => {
                    if (!vis(el)) return;
                    const r = el.getBoundingClientRect();
                    sections.push({
                        heading: label(el),
                        x: Math.round(r.x),
                        width: Math.round(r.width),
                        height: Math.round(r.height),
                    });
                });

                const tables = [...document.querySelectorAll(".fi-ta-ctn")].map((el) => {
                    const wrap = el.querySelector(".fi-ta-content")!;
                    const table = el.querySelector("table")!;
                    if (!wrap || !table) return null;
                    const w = getComputedStyle(wrap);
                    return {
                        wrapper: Math.round(wrap.getBoundingClientRect().width),
                        table: Math.round(table.getBoundingClientRect().width),
                        overflowX: w.overflowX,
                        scrolls:
                            ["auto", "scroll"].includes(w.overflowX) &&
                            wrap.scrollWidth > wrap.clientWidth,
                    };
                }).filter(Boolean);

                const docOverflow =
                    document.documentElement.scrollWidth - document.documentElement.clientWidth;

                // Cards sharing a row, to spot an outlier.
                const gridRows: any[] = [];
                document.querySelectorAll(".fi-grid, .fi-sc").forEach((g) => {
                    if (!vis(g)) return;
                    const kids = Array.from(g.children).filter(vis);
                    if (kids.length < 2) return;
                    const ws = kids.map((k) => Math.round(k.getBoundingClientRect().width));
                    const max = Math.max(...ws);
                    const min = Math.min(...ws);
                    if (max - min > 40) {
                        gridRows.push({
                            childWidths: ws,
                            max,
                            min,
                            classes: String(g.className).slice(0, 60),
                        });
                    }
                });

                return { docOverflow, sections, tables, unevenRows: gridRows };
            });

            await page.screenshot({
                path: `${OUT}/${t.key}__w${t.w}.png`,
                fullPage: true,
            });
        }

        fs.writeFileSync(`${OUT}/final.json`, JSON.stringify(out, null, 2));

        for (const [k, v] of Object.entries(out as any)) {
            const d = v as any;
            console.log(
                `F=${k} docOverflow=${d.docOverflow} sections=${d.sections.length} ` +
                    `clippedTables=${d.tables.filter((t: any) => t.table > t.wrapper + 4 && !t.scrolls).length} ` +
                    `unevenRows=${d.unevenRows.length}`,
            );
        }
    });
});
