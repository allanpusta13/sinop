import { test } from "@playwright/test";
import { loginAsAdmin } from "./helpers/login";
import * as fs from "fs";

const OUT = ".issue-tmp/layout-audit";

test("card-grid lists layout at 1440/768/375", async ({ page }) => {
    test.setTimeout(10 * 60_000);
    fs.mkdirSync(OUT, { recursive: true });
    await loginAsAdmin(page);
    const out: Record<string, unknown> = {};

    const lists = [
        "/admin/products",
        "/admin/warehouses",
        "/admin/purchase-orders",
        "/admin/sales-orders",
        "/admin/direct-transfers",
        "/admin/transfer-requisitions",
    ];

    for (const url of lists) {
        for (const vw of [1440, 768, 375]) {
            await page.setViewportSize({ width: vw, height: 900 });
            await page.goto(url, { waitUntil: "domcontentloaded" });
            await page.waitForTimeout(1500);
            out[`${url}@${vw}`] = await page.evaluate(() => {
                const grid = document.querySelector(
                    ".fi-ta-content-grid, .fi-ta-col-wrap, .fi-ta-content .grid",
                );
                const g = grid as HTMLElement | null;
                const cols = g ? getComputedStyle(g).gridTemplateColumns : null;
                const tracks = cols ? cols.split(" ").filter((x) => x !== "").length : 0;

                // Card elements inside the grid.
                const cards = g
                    ? Array.from(g.children).map((el) => {
                          const b = el.getBoundingClientRect();
                          return { w: Math.round(b.width), h: Math.round(b.height) };
                      })
                    : [];

                // Smallest text box that wraps badly (narrow + tall).
                const cramped = Array.from(document.querySelectorAll("*"))
                    .filter((el) => {
                        const cs = getComputedStyle(el);
                        const b = el.getBoundingClientRect();
                        return (
                            el.children.length === 0 &&
                            (el.textContent ?? "").trim().length > 8 &&
                            b.width > 0 &&
                            b.width < 90 &&
                            b.height > 34
                        );
                    })
                    .slice(0, 5)
                    .map((el) => ({
                        cls: String(el.className).replace(/\s+/g, " ").slice(0, 34),
                        w: Math.round(el.getBoundingClientRect().width),
                        h: Math.round(el.getBoundingClientRect().height),
                        text: (el.textContent ?? "").trim().slice(0, 18),
                    }));

                return {
                    trackCount: tracks,
                    gridW: g ? Math.round(g.getBoundingClientRect().width) : null,
                    cards: cards.slice(0, 4),
                    cramped,
                    docOverflow:
                        document.documentElement.scrollWidth -
                        document.documentElement.clientWidth,
                };
            });
        }
        await page.setViewportSize({ width: 768, height: 900 });
        await page.goto(url, { waitUntil: "domcontentloaded" });
        await page.waitForTimeout(1500);
        await page.screenshot({
            path: `${OUT}/cards-${url.replace(/\//g, "_")}-768.png`,
            fullPage: true,
        });
    }

    fs.writeFileSync(`${OUT}/cards.json`, JSON.stringify(out, null, 1));
    console.log("CARDS_DONE");
});
