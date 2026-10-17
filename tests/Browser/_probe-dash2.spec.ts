import { test, Page } from "@playwright/test";
import { loginAsAdmin } from "./helpers/login";
import * as fs from "fs";

const OUT = ".issue-tmp/layout-audit";

async function measure(page: Page, vw: number) {
    return page.evaluate((vw) => {
        const secs = Array.from(
            document.querySelectorAll(".fi-sc-section, .fi-section, .fi-wi"),
        )
            .map((el) => {
                const b = el.getBoundingClientRect();
                const h = el.querySelector(
                    ".fi-section-header-heading, .fi-wi-header-heading, h3, h2",
                );
                return {
                    label: (h?.textContent ?? "").trim().slice(0, 34),
                    x: Math.round(b.x),
                    y: Math.round(b.y),
                    w: Math.round(b.width),
                    h: Math.round(b.height),
                };
            })
            .filter((s) => s.w > 0);

        // Rows: group sections sharing the same y.
        const rows: Record<number, number[]> = {};
        secs.forEach((s) => {
            const k = Math.round(s.y / 12) * 12;
            (rows[k] ??= []).push(s.w);
        });

        // Chart canvases: how empty is each one?
        const charts = Array.from(document.querySelectorAll("canvas")).map((c) => {
            const b = c.getBoundingClientRect();
            return { w: Math.round(b.width), h: Math.round(b.height) };
        });

        return {
            vw,
            secs,
            rows: Object.entries(rows).map(([y, ws]) => ({
                y: Number(y),
                widths: ws,
                uneven: Math.max(...ws) - Math.min(...ws),
            })),
            charts,
            docOverflow:
                document.documentElement.scrollWidth - document.documentElement.clientWidth,
            loading: document.querySelectorAll(".fi-loading-section").length,
            bodyH: document.body.scrollHeight,
        };
    }, vw);
}

test("dashboard at 3 viewports, fully loaded", async ({ page }) => {
    test.setTimeout(10 * 60_000);
    fs.mkdirSync(OUT, { recursive: true });
    await loginAsAdmin(page);

    const out: unknown[] = [];
    for (const vw of [1440, 768, 375]) {
        await page.setViewportSize({ width: vw, height: 900 });
        await page.goto("/admin", { waitUntil: "domcontentloaded" });
        // Dashboard widgets lazy-load; wait for skeletons to clear, hard.
        await page
            .waitForFunction(
                () => document.querySelectorAll(".fi-loading-section").length === 0,
                null,
                { timeout: 40_000 },
            )
            .catch(() => {});
        await page.waitForTimeout(3000);
        out.push(await measure(page, vw));
        await page.screenshot({
            path: `${OUT}/dash-loaded-${vw}.png`,
            fullPage: true,
        });
    }
    fs.writeFileSync(`${OUT}/dashboard.json`, JSON.stringify(out, null, 1));
    console.log("DASH_DONE");
});
