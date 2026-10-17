import { test } from "@playwright/test";
import { tinker } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";

/**
 * Last two unflagged-by-structured-probe cells:
 * transfer-requisitions--view (bleed 9/19) and loss-ledgers--index
 * (clipped 1). Same method as the others: walk the true ancestor chain and
 * report which level actually scrolls. No element text read.
 */
test("residual flags provenance", async ({ page }) => {
    test.setTimeout(5 * 60_000);

    const trId = tinker(`
      echo \\App\\Models\\TransferRequisition::query()->first()?->getKey() ?? 0;
    `).trim();

    await loginAsAdmin(page);

    for (const [url, width, sel] of [
        [`/admin/transfer-requisitions/${trId}`, 1024, "table"],
        [`/admin/transfer-requisitions/${trId}`, 768, "table"],
        ["/admin/loss-ledgers", 1024, ".fi-ta-ctn"],
        ["/admin/loss-ledgers", 768, ".fi-ta-ctn"],
    ] as Array<[string, number, string]>) {
        await page.setViewportSize({ width, height: 900 });
        await page.goto(url, { waitUntil: "domcontentloaded" });
        await page.waitForTimeout(900);
        const out = await page.evaluate((s: string) => {
            const node = document.querySelector(s);
            if (!node) return { status: "absent" };
            const chain: Array<Record<string, unknown>> = [];
            let cur: Element | null = node;
            let d = 0;
            while (cur && d < 8) {
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
                    overflowsViewport: Math.round(r.right) > document.documentElement.clientWidth + 2,
                });
                cur = cur.parentElement;
                d++;
            }
            return { status: "ok", vw: document.documentElement.clientWidth, chain };
        }, sel);
        console.log(`RES url=${url} vp=${width} ${JSON.stringify(out)}`);
    }
});
