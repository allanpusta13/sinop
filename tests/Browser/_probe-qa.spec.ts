import { test, Page } from "@playwright/test";
import { loginAsAdmin } from "./helpers/login";
import * as fs from "fs";

const OUT = ".issue-tmp/layout-audit";

/** Quick-action cards + dashboard widget slots. */
async function qa(page: Page) {
    return page.evaluate(() => {
        const cards = Array.from(
            document.querySelectorAll('.fi-wi a[href], .fi-wi .grid > *'),
        )
            .filter((el) => (el as HTMLElement).offsetParent !== null)
            .map((el) => {
                const b = el.getBoundingClientRect();
                return { w: Math.round(b.width), h: Math.round(b.height) };
            })
            .filter((c) => c.w > 20);
        // Widget slots in the dashboard grid.
        const slots = Array.from(document.querySelectorAll(".fi-wi")).map((el) => {
            const b = el.getBoundingClientRect();
            return {
                w: Math.round(b.width),
                h: Math.round(b.height),
                cls: String(el.className).replace(/\s+/g, " ").slice(0, 40),
            };
        });
        return {
            cards: cards.slice(0, 12),
            slots,
            loading: document.querySelectorAll(".fi-loading-section").length,
        };
    });
}

test("quick actions + mobile dashboard settling", async ({ page }) => {
    test.setTimeout(12 * 60_000);
    fs.mkdirSync(OUT, { recursive: true });
    await loginAsAdmin(page);
    const out: Record<string, unknown> = {};

    // ---- quick actions at 3 widths (wait for full load at each) ----
    for (const vw of [1440, 768, 375]) {
        await page.setViewportSize({ width: vw, height: 900 });
        await page.goto("/admin", { waitUntil: "domcontentloaded" });
        // Wait for loading to settle up to 90s, sampling.
        let last = -1;
        const timeline: number[] = [];
        for (let i = 0; i < 45; i++) {
            const n = await page.evaluate(
                () => document.querySelectorAll(".fi-loading-section").length,
            );
            timeline.push(n);
            if (n === 0 && last === 0) break;
            last = n;
            await page.waitForTimeout(2000);
        }
        await page.waitForTimeout(1500);
        out[`dash:${vw}`] = { ...(await qa(page)), timeline };
        await page.screenshot({
            path: `${OUT}/dash-final-${vw}.png`,
            fullPage: true,
        });
    }

    fs.writeFileSync(`${OUT}/quickactions.json`, JSON.stringify(out, null, 1));
    console.log("QA_DONE");
});
