import { test } from "@playwright/test";
import { tinker } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";

test("probe infolist grid spans", async ({ page }) => {
    test.setTimeout(5 * 60_000);
    const raw = tinker(`echo \\App\\Models\\ProductVariant::query()->first()->id;`);
    const pid = parseInt(raw.match(/(\d+)\s*$/m)![1], 10);

    await loginAsAdmin(page);
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(`/admin/products/${pid}`, { waitUntil: "domcontentloaded" });
    await page.waitForTimeout(1800);

    const r = await page.evaluate(() => {
        const out: any[] = [];
        document.querySelectorAll(".fi-grid, .fi-sc-section").forEach((el) => {
            const b = el.getBoundingClientRect();
            if (b.width < 2) return;
            const cs = getComputedStyle(el);
            const parent = el.parentElement;
            const pb = parent?.getBoundingClientRect();
            out.push({
                cls: String(el.className).replace(/\s+/g, " ").slice(0, 70),
                width: Math.round(b.width),
                parentWidth: pb ? Math.round(pb.width) : null,
                parentCls: parent ? String(parent.className).replace(/\s+/g, " ").slice(0, 50) : null,
                tracks: cs.gridTemplateColumns,
                gridColumn: cs.gridColumn,
                flexBasis: cs.flexBasis,
                tag: el.tagName.toLowerCase(),
            });
        });
        // Sibling order under the root grid.
        const rootGrid = document.querySelector(".fi-grid.fi-sc");
        const kids = rootGrid
            ? Array.from(rootGrid.children).map((k) => ({
                  cls: String(k.className).replace(/\s+/g, " ").slice(0, 60),
                  w: Math.round(k.getBoundingClientRect().width),
                  gridColumn: getComputedStyle(k).gridColumn,
              }))
            : [];
        return { out, kids };
    });
    console.log("PROBE_GRID " + JSON.stringify(r, null, 1));
});
