import { test, Page } from "@playwright/test";
import { loginAsAdmin } from "./helpers/login";
import * as fs from "fs";

const OUT = ".issue-tmp/layout-audit";

async function probe(page: Page) {
    return page.evaluate(() => {
        // Quick-action links: anchor inside the dashboard whose href is a create page.
        const links = Array.from(document.querySelectorAll("a[href]"))
            .filter((a) => {
                const h = a.getAttribute("href") ?? "";
                if (!/create|transfer|purchase|sale/i.test(h)) return false;
                const b = a.getBoundingClientRect();
                return b.width > 10 && b.height > 10 && b.top > 500;
            })
            .map((a) => {
                const b = a.getBoundingClientRect();
                return {
                    text: (a.textContent ?? "").trim().replace(/\s+/g, " ").slice(0, 24),
                    x: Math.round(b.x),
                    w: Math.round(b.width),
                    h: Math.round(b.height),
                };
            });

        // Which widgets are still skeletons, and their headings.
        const loading = Array.from(document.querySelectorAll(".fi-loading-section")).map(
            (el) => {
                const wi = el.closest("[wire\\:id], .fi-wi, section, div");
                const head = wi?.querySelector("h2,h3,.fi-ta-header-heading");
                const b = el.getBoundingClientRect();
                return {
                    h: Math.round(b.height),
                    heading: (head?.textContent ?? "").trim().slice(0, 40),
                    parentCls: String(wi?.className ?? "").replace(/\s+/g, " ").slice(0, 50),
                };
            },
        );

        // Stat cards in the top row.
        const stats = Array.from(document.querySelectorAll("a[href], div"))
            .filter((el) => {
                const t = el.textContent ?? "";
                return /On hand|Pending|In transit|Write-off/.test(t) && t.length < 120;
            })
            .map((el) => {
                const b = el.getBoundingClientRect();
                return { w: Math.round(b.width), h: Math.round(b.height) };
            })
            .filter((r) => r.w > 60 && r.w < 900)
            .slice(0, 6);

        return { links, loading, stats };
    });
}

test("quick action rects + failing widgets", async ({ page }) => {
    test.setTimeout(10 * 60_000);
    fs.mkdirSync(OUT, { recursive: true });
    await loginAsAdmin(page);
    const out: Record<string, unknown> = {};

    for (const vw of [1440, 768, 375]) {
        await page.setViewportSize({ width: vw, height: 900 });
        await page.goto("/admin", { waitUntil: "domcontentloaded" });
        await page.waitForTimeout(20_000);
        out[vw] = await probe(page);
    }
    fs.writeFileSync(`${OUT}/qa2.json`, JSON.stringify(out, null, 1));
    console.log("QA2_DONE");
});
