import { test } from "@playwright/test";
import { loginAsAdmin } from "./helpers/login";
import * as fs from "fs";

const OUT = ".issue-tmp/layout-audit";

test("index table column widths at 1440", async ({ page }) => {
    test.setTimeout(8 * 60_000);
    fs.mkdirSync(OUT, { recursive: true });
    await loginAsAdmin(page);
    const out: Record<string, unknown> = {};

    const lists = [
        "/admin/users",
        "/admin/products",
        "/admin/warehouses",
        "/admin/stock-movements",
        "/admin/purchase-orders",
        "/admin/sales-orders",
        "/admin/direct-transfers",
        "/admin/transfer-requisitions",
        "/admin/in-transits",
        "/admin/loss-ledgers",
    ];

    for (const url of lists) {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(url, { waitUntil: "domcontentloaded" });
        await page.waitForTimeout(1600);
        out[url] = await page.evaluate(() => {
            const table = document.querySelector("table");
            if (!table) return { err: "no table" };
            const wrap = table.closest(".fi-ta-content-ctn") as HTMLElement | null;
            const heads = Array.from(table.querySelectorAll("thead th")).map((th) => ({
                label: (th.textContent ?? "").trim().slice(0, 22),
                w: Math.round(th.getBoundingClientRect().width),
            }));
            return {
                tableW: Math.round(table.getBoundingClientRect().width),
                wrapW: wrap ? wrap.clientWidth : null,
                scrollW: wrap ? wrap.scrollWidth : null,
                needsScroll: wrap ? wrap.scrollWidth - wrap.clientWidth : 0,
                headers: heads,
                actionCellW: (() => {
                    const c = table.querySelector("tbody td:last-child");
                    return c ? Math.round(c.getBoundingClientRect().width) : null;
                })(),
            };
        });
    }

    fs.writeFileSync(`${OUT}/tables.json`, JSON.stringify(out, null, 1));
    console.log("TABLES_DONE");
});
