import { test } from "@playwright/test";
import { loginAsAdmin } from "./helpers/login";

test("compare table scroll containers", async ({ page }) => {
    test.setTimeout(5 * 60_000);
    await loginAsAdmin(page);
    await page.setViewportSize({ width: 1280, height: 900 });

    for (const url of ["/admin/users", "/admin/stock-movements"]) {
        await page.goto(url, { waitUntil: "domcontentloaded" });
        await page.waitForTimeout(1500);
        const r = await page.evaluate(() => {
            const table = document.querySelector("table");
            if (!table) return { err: "no table" };
            // Walk up to the outermost table container, reporting overflow.
            const chain: any[] = [];
            let el: Element | null = table;
            for (let i = 0; i < 8 && el; i++) {
                const cs = getComputedStyle(el);
                chain.push({
                    tag: el.tagName.toLowerCase(),
                    cls: String(el.className).replace(/\s+/g, " ").slice(0, 70),
                    client: (el as HTMLElement).clientWidth,
                    scroll: (el as HTMLElement).scrollWidth,
                    overflowX: cs.overflowX,
                    display: cs.display,
                });
                el = el.parentElement;
            }
            return { chain, tableW: Math.round(table.getBoundingClientRect().width) };
        });
        console.log(`PROBE_CMP ${url} ` + JSON.stringify(r, null, 1));
    }
});
