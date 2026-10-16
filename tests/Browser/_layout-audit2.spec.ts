import { test } from "@playwright/test";
import { tinker } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";
import * as fs from "fs";

/**
 * Focused second pass: quantify the two reported symptoms precisely.
 *
 *  SQUISHED  — a card/section occupying far less width than its row siblings,
 *              or a field grid that collapses to 1 track on a wide viewport.
 *  TOO LARGE — a card whose height is dominated by a chart/canvas, or a
 *              single element taller than the viewport.
 *
 * Also distinguishes genuine clipping (table wider than its overflow wrapper
 * with no scroll) from intentional horizontal scroll inside the wrapper.
 */

const OUT = "test-results/layout-audit";

test.describe("LAYOUT AUDIT 2", () => {
    test("quantify squish and oversize", async ({ page }) => {
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
        await page.setViewportSize({ width: 1440, height: 900 });

        const urls: Array<{ key: string; url: string }> = [
            { key: "dashboard", url: "/admin" },
            { key: "stock-movements--index", url: "/admin/stock-movements" },
            { key: "users--index", url: "/admin/users" },
            { key: "products--view", url: `/admin/products/${ids.products}` },
            { key: "products--edit", url: `/admin/products/${ids.products}/edit` },
            { key: "products--create", url: "/admin/products/create" },
            { key: "purchase-orders--view", url: `/admin/purchase-orders/${ids.purchase_orders}` },
            { key: "sales-orders--view", url: `/admin/sales-orders/${ids.sales_orders}` },
            { key: "transfer-requisitions--view", url: `/admin/transfer-requisitions/${ids.transfer_requisitions}` },
            { key: "direct-transfers--view", url: `/admin/direct-transfers/${ids.direct_transfers}/` },
            { key: "warehouses--view", url: `/admin/warehouses/${ids.warehouses}` },
            { key: "users--view", url: `/admin/users/${ids.users}` },
            { key: "customers--create", url: "/admin/customers/create" },
            { key: "in-transits--index", url: "/admin/in-transits" },
            { key: "loss-ledgers--index", url: "/admin/loss-ledgers" },
        ];

        const findings: any = {};

        for (const { key, url } of urls) {
            const resp = await page.goto(url, { waitUntil: "domcontentloaded" });
            await page.waitForTimeout(1200);

            findings[key] = await page.evaluate(() => {
                const vis = (el: Element) => {
                    const he = el as HTMLElement;
                    const r = he.getBoundingClientRect();
                    return r.width > 1 && r.height > 1;
                };

                // 1. Sections: width vs the widest sibling in the same row.
                const sections: any[] = [];
                document.querySelectorAll(".fi-section, .fi-sc-section").forEach((el) => {
                    if (!vis(el)) return;
                    const r = el.getBoundingClientRect();
                    const parent = el.parentElement!;
                    const rowSiblings = Array.from(parent.children).filter(vis);
                    const maxW = Math.max(
                        ...rowSiblings.map((s) => s.getBoundingClientRect().width),
                    );
                    sections.push({
                        heading:
                            el.querySelector(".fi-section-header-heading")?.textContent?.trim() ??
                            el.className.slice(0, 40),
                        width: Math.round(r.width),
                        rowMax: Math.round(maxW),
                        share: Math.round((r.width / maxW) * 100) / 100,
                        height: Math.round(r.height),
                    });
                });

                // 2. Tables: element width vs its overflow wrapper.
                const tables: any[] = [];
                document.querySelectorAll(".fi-ta-content, .fi-ta-ctn").forEach((el) => {
                    if (!vis(el)) return;
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
                        overflowX: cs.overflowX,
                        scrollable: ["auto", "scroll"].includes(cs.overflowX),
                    });
                });

                // 3. Tall elements (charts/canvas dominate height).
                const tall: any[] = [];
                document
                    .querySelectorAll(".fi-wi-chart, canvas, svg.apexcharts-svg, .fi-section")
                    .forEach((el) => {
                        if (!vis(el)) return;
                        const r = el.getBoundingClientRect();
                        if (r.height > 380) {
                            tall.push({
                                el: el.tagName.toLowerCase() + "." + String(el.className).split(/\s+/)[0],
                                height: Math.round(r.height),
                                width: Math.round(r.width),
                            });
                        }
                    });

                // 4. Field grids collapsing to one track on a wide viewport.
                const thinGrids: any[] = [];
                document.querySelectorAll(".fi-fo-grid, .fi-grid").forEach((el) => {
                    if (!vis(el)) return;
                    const cs = getComputedStyle(el);
                    const tracks = cs.gridTemplateColumns.split(/\s+/).filter(Boolean);
                    if (tracks.length === 1 && el.getBoundingClientRect().width > 700) {
                        const owner = el.closest(".fi-section");
                        thinGrids.push({
                            owner:
                                owner?.querySelector(".fi-section-header-heading")?.textContent?.trim() ??
                                "(no section)",
                            width: Math.round(el.getBoundingClientRect().width),
                            classes: String(el.className).slice(0, 80),
                        });
                    }
                });

                // 5. Text wrapping to 3+ lines inside a stat widget.
                const wrapped: any[] = [];
                document.querySelectorAll(".fi-wi-stats-overview-stat, .fi-section").forEach((el) => {
                    if (!vis(el)) return;
                    el.querySelectorAll("p, span, div").forEach((t) => {
                        if (t.children.length) return;
                        const r = t.getBoundingClientRect();
                        const lh = parseFloat(getComputedStyle(t).lineHeight) || 20;
                        const lines = Math.round(r.height / lh);
                        if (lines >= 3 && r.width < 220 && t.textContent!.trim().length > 10) {
                            wrapped.push({
                                text: t.textContent!.trim().slice(0, 60),
                                width: Math.round(r.width),
                                lines,
                            });
                        }
                    });
                });

                return { sections, tables, tall, thinGrids, wrapped: wrapped.slice(0, 8) };
            });
        }

        fs.writeFileSync(
            `${OUT}/quantified.json`,
            JSON.stringify(findings, null, 2),
        );

        for (const [k, v] of Object.entries(findings as any)) {
            const f = v as any;
            console.log(
                `Q=${k} sections=${f.sections.length} ` +
                    `narrowSections=${f.sections.filter((s: any) => s.share < 0.9).length} ` +
                    `tables=${f.tables.length} clippedTables=${
                        f.tables.filter((t: any) => t.overflow > 4 && !t.scrollable).length
                    } ` +
                    `tall=${f.tall.length} thinGrids=${f.thinGrids.length} wrapped=${f.wrapped.length}`,
            );
        }
    });
});
