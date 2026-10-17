import { test } from "@playwright/test";
import { tinker } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";

/**
 * Are the remaining "bleeding" flags real, or contained by a scroll
 * container like the tables were?
 *
 * Candidates: wizard header steps on *-create pages, and dashboard
 * "squished" widgets (suspected to be legitimately half-width children of
 * a 2-column dashboard, i.e. the already-recorded §7O.5 drift, not a new
 * defect). Both are checked structurally here. No element text read.
 */
test("bleed + squish provenance", async ({ page }) => {
    test.setTimeout(5 * 60_000);

    const id = tinker(`
      echo \\App\\Models\\Product::query()->first()?->getKey() ?? 0;
    `).trim();

    await loginAsAdmin(page);

    const inspect = async (url: string, width: number, sel: string) => {
        await page.setViewportSize({ width, height: 900 });
        await page.goto(url, { waitUntil: "domcontentloaded" });
        await page.waitForTimeout(900);
        const out = await page.evaluate((s: string) => {
            const node = document.querySelector(s);
            if (!node) return { status: "absent" };
            const chain: Array<Record<string, unknown>> = [];
            let cur: Element | null = node;
            let depth = 0;
            while (cur && depth < 9) {
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
                    right: Math.round(r.right),
                    scrollW: (cur as HTMLElement).scrollWidth,
                    overflowX: cs.overflowX,
                    canScroll:
                        (cur as HTMLElement).scrollWidth > Math.round(r.width) + 1 &&
                        ["auto", "scroll"].includes(cs.overflowX),
                });
                cur = cur.parentElement;
                depth++;
            }
            return { status: "ok", vw: document.documentElement.clientWidth, chain };
        }, sel);
        console.log(`PROBE url=${url} vp=${width} sel=${sel} ${JSON.stringify(out)}`);
    };

    await inspect("/admin/purchase-orders/create", 1024, ".fi-sc-wizard-header");
    await inspect("/admin/purchase-orders/create", 768, ".fi-sc-wizard-header");
    await inspect("/admin", 1920, "div.fi-section.fi-loading-section.fi-grid-col");
    await inspect("/admin", 1024, "div.fi-section.fi-loading-section.fi-grid-col");
    await inspect(`/admin/products/${id}/edit`, 375, ".fi-tabs");
});
