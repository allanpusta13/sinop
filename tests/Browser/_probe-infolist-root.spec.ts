import { test } from "@playwright/test";
import { tinker } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";

/**
 * Measures the ACTUAL infolist root grid on every reachable `--view` page.
 *
 * The full-audit `rootGrid` metric cannot do this: it falls back to the
 * first `.fi-sc`/`.fi-grid` anywhere under main, which on index pages is a
 * page wrapper with the *same* class descriptor and the *same* ratio as an
 * infolist root. So a uniform 0.91 there proves nothing about A1.
 *
 * Here the node is pinned structurally: the outermost grid-ish element
 * that is a descendant of `div.fi-page-content` (the infolist root is its
 * first child), plus the computed `--col-span-default` it emits.
 *
 * Read-only. No element text is read.
 */
test("infolist root grid, per view page", async ({ page }) => {
    test.setTimeout(10 * 60_000);

    const ids = tinker(`
      foreach ([
        'direct-transfers' => \\App\\Models\\DirectTransfer::class,
        'products' => \\App\\Models\\Product::class,
        'purchase-orders' => \\App\\Models\\PurchaseOrder::class,
        'sales-orders' => \\App\\Models\\SalesOrder::class,
        'stock-movements' => \\App\\Models\\StockMovement::class,
        'transfer-requisitions' => \\App\\Models\\TransferRequisition::class,
        'users' => \\App\\Models\\User::class,
        'warehouses' => \\App\\Models\\Warehouse::class,
        'in-transits' => \\App\\Models\\InTransit::class,
        'loss-ledgers' => \\App\\Models\\LossLedger::class,
      ] as $k => $c) {
        $m = $c::query()->first();
        echo $k . '=' . ($m?->getKey() ?? 0) . "\\n";
      }
    `);
    const idMap: Record<string, number> = {};
    for (const line of ids.split("\n")) {
        const m = line.trim().match(/^([\w-]+)=(\d+)$/);
        if (m) idMap[m[1]] = parseInt(m[2], 10);
    }

    await loginAsAdmin(page);

    for (const width of [1024, 1440]) {
        await page.setViewportSize({ width, height: 900 });
        for (const [key, id] of Object.entries(idMap)) {
            if (!id) {
                console.log(`ROOT vp=${width} ${key} NO_RECORD`);
                continue;
            }
            await page.goto(`/admin/${key}/${id}`, { waitUntil: "domcontentloaded" });
            await page.waitForTimeout(600);

            const out = await page.evaluate(() => {
                const desc = (el: Element) => {
                    const c =
                        typeof el.className === "string"
                            ? el.className.split(/\s+/).filter(Boolean).slice(0, 2).join(".")
                            : "";
                    return el.tagName.toLowerCase() + (c ? "." + c : "");
                };
                const content = document.querySelector("div.fi-page-content");
                const main = document.querySelector(".fi-main-ctn");
                if (!content) return { status: "no-page-content" };
                const mainW = main?.getBoundingClientRect().width ?? 0;

                // Outermost grid-ish descendant of page-content.
                let root: Element | null = null;
                const all = content.querySelectorAll(".fi-sc, .fi-grid, .fi-sc-grid");
                for (const el of all) {
                    let p: Element | null = el.parentElement;
                    let owns = true;
                    while (p && p !== content) {
                        if (p.matches(".fi-sc, .fi-grid, .fi-sc-grid")) {
                            owns = false;
                            break;
                        }
                        p = p.parentElement;
                    }
                    if (owns) {
                        root = el;
                        break;
                    }
                }
                if (!root) return { status: "no-root-grid", mainW: Math.round(mainW) };

                const r = root.getBoundingClientRect();
                const cs = getComputedStyle(root);
                return {
                    status: "ok",
                    el: desc(root),
                    width: Math.round(r.width),
                    mainW: Math.round(mainW),
                    ratio: Math.round((r.width / mainW) * 100) / 100,
                    spanDefault: cs.getPropertyValue("--col-span-default").trim(),
                    spanXl: cs.getPropertyValue("--col-span-xl").trim(),
                    tracks: cs.gridTemplateColumns.split(/\s+/).filter(Boolean).length,
                };
            });

            console.log(`ROOT vp=${width} ${key} ${JSON.stringify(out)}`);
        }
    }
});
