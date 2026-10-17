import { test } from "@playwright/test";
import { tinker } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";

/**
 * Metric-validation probe: does `rootGrid` actually detect the A1 defect?
 *
 * One page, one viewport. Run with A1 present, then with one root's
 * `->columnSpanFull()` removed, and compare. If the ratio does not move,
 * the metric measures the page wrapper — not the infolist root — and
 * proves nothing about A1.
 *
 * Also dumps the ancestor chain width so the measured node is identifiable.
 */
test("probe root-grid ratio on one infolist", async ({ page }) => {
    const raw = tinker(`
      echo \\App\\Models\\Product::query()->first()?->getKey() ?? 0;
    `);
    const id = parseInt(raw.trim(), 10);

    await loginAsAdmin(page);
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(`/admin/products/${id}`, { waitUntil: "domcontentloaded" });
    await page.waitForTimeout(1500);

    const out = await page.evaluate(() => {
        const main =
            document.querySelector(".fi-main-ctn") ??
            document.querySelector(".fi-main");
        const mainW = main?.getBoundingClientRect().width ?? 0;

        const desc = (el: Element) => {
            const c =
                typeof el.className === "string"
                    ? el.className.split(/\s+/).filter(Boolean).slice(0, 4).join(".")
                    : "";
            return el.tagName.toLowerCase() + (c ? "." + c : "");
        };

        // Every grid-ish node under main, with its width and ancestor chain.
        const nodes: Array<Record<string, unknown>> = [];
        main?.querySelectorAll(".fi-sc, .fi-grid, .fi-sc-grid, [class*='fi-sc-']").forEach((el) => {
            const r = el.getBoundingClientRect();
            if (r.width < 2 || r.height < 2) return;
            const chain: string[] = [];
            let cur: Element | null = el;
            for (let i = 0; i < 4 && cur && cur !== main; i++) {
                chain.push(desc(cur));
                cur = cur.parentElement;
            }
            nodes.push({
                el: desc(el),
                width: Math.round(r.width),
                ratio: Math.round((r.width / mainW) * 100) / 100,
                depth: chain.length - 1,
                chain: chain.join(" < "),
                span: getComputedStyle(el).getPropertyValue("--col-span-xl").trim(),
            });
        });

        return { mainW: Math.round(mainW), count: nodes.length, nodes: nodes.slice(0, 25) };
    });

    console.log("PROBE_MAIN_WIDTH=" + out.mainW);
    console.log("PROBE_NODES=" + out.count);
    for (const n of out.nodes as Array<Record<string, unknown>>) {
        console.log(
            `PROBE width=${n.width} ratio=${n.ratio} span="${n.span}" el=${n.el} :: ${n.chain}`,
        );
    }
});
