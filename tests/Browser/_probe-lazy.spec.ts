import { test } from "@playwright/test";
import { loginAsAdmin } from "./helpers/login";
import * as fs from "fs";

const OUT = ".issue-tmp/layout-audit";

test("do skeletons clear on scroll? identify widgets", async ({ page }) => {
    test.setTimeout(10 * 60_000);
    fs.mkdirSync(OUT, { recursive: true });
    await loginAsAdmin(page);
    const out: Record<string, unknown> = {};

    for (const vw of [375, 768]) {
        await page.setViewportSize({ width: vw, height: 900 });
        await page.goto("/admin", { waitUntil: "domcontentloaded" });
        await page.waitForTimeout(12_000);

        const count = () =>
            page.evaluate(() => document.querySelectorAll(".fi-loading-section").length);

        const before = await count();

        // Identify: read the Livewire snapshot of each loading section's parent.
        const ids = await page.evaluate(() =>
            Array.from(document.querySelectorAll(".fi-loading-section")).map((el) => {
                let n: Element | null = el;
                for (let i = 0; i < 6 && n; i++) {
                    const wire = n.getAttribute("wire:snapshot");
                    if (wire) {
                        const m = wire.match(/"class":"([^"]+)"/g);
                        return {
                            cls: String(n.className).replace(/\s+/g, " ").slice(0, 40),
                            widgets: m
                                ? m.map((x) => x.replace(/"class":"|"/g, "").split("\\").pop())
                                : [],
                        };
                    }
                    n = n.parentElement;
                }
                return { cls: String(el.className).slice(0, 40), widgets: [] };
            }),
        );

        // Scroll the whole page slowly to trigger any lazy loading.
        await page.evaluate(async () => {
            const step = window.innerHeight * 0.8;
            for (let y = 0; y < document.body.scrollHeight; y += step) {
                window.scrollTo(0, y);
                await new Promise((r) => setTimeout(r, 700));
            }
            window.scrollTo(0, document.body.scrollHeight);
        });
        await page.waitForTimeout(6000);

        const after = await count();
        await page.screenshot({ path: `${OUT}/dash-scrolled-${vw}.png`, fullPage: true });
        out[vw] = { before, after, ids };
    }

    fs.writeFileSync(`${OUT}/lazy.json`, JSON.stringify(out, null, 1));
    console.log("LAZY_DONE");
});
