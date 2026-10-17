import { test } from "@playwright/test";
import { loginAsAdmin } from "./helpers/login";

/**
 * Are the "clipped tables" real, or another detector artifact?
 *
 * The full audit recorded users--index / stock-movements--index as
 * `scrollable:false` with a table far wider than its wrapper. Filament
 * tables normally scroll horizontally. If the true ancestor does scroll,
 * `clippedTables` is a false positive exactly like `rootGrid` was.
 *
 * Walks the real ancestor chain and reports computed overflow-x on each
 * level plus whether that level can actually scroll. No element text read.
 */
test("table overflow chain", async ({ page }) => {
    test.setTimeout(5 * 60_000);
    await loginAsAdmin(page);

    for (const [url, width] of [
        ["/admin/users", 1024],
        ["/admin/stock-movements", 1024],
    ] as Array<[string, number]>) {
        await page.setViewportSize({ width, height: 900 });
        await page.goto(url, { waitUntil: "domcontentloaded" });
        await page.waitForTimeout(800);

        const out = await page.evaluate(() => {
            const table = document.querySelector(".fi-ta-ctn table");
            if (!table) return { status: "no-table" };
            const chain: Array<Record<string, unknown>> = [];
            let cur: Element | null = table.parentElement;
            let depth = 0;
            while (cur && depth < 8) {
                const cs = getComputedStyle(cur);
                const r = cur.getBoundingClientRect();
                chain.push({
                    el:
                        cur.tagName.toLowerCase() +
                        "." +
                        (typeof cur.className === "string"
                            ? cur.className.split(/\s+/).filter(Boolean).slice(0, 3).join(".")
                            : ""),
                    w: Math.round(r.width),
                    scrollW: (cur as HTMLElement).scrollWidth,
                    overflowX: cs.overflowX,
                    canScroll:
                        (cur as HTMLElement).scrollWidth > Math.round(r.width) + 1 &&
                        ["auto", "scroll"].includes(cs.overflowX),
                });
                cur = cur.parentElement;
                depth++;
            }
            const de = document.documentElement;
            return {
                status: "ok",
                docScrollW: de.scrollWidth,
                docClientW: de.clientWidth,
                chain,
            };
        });

        console.log(`TBL url=${url} vp=${width} ${JSON.stringify(out)}`);
    }
});
