import { test } from "@playwright/test";
import { tinker } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";
import * as fs from "fs";

/**
 * Final screenshot capture for the layout audit report.
 * Writes outside test-results so Playwright's cleanup cannot remove it.
 */
const OUT = ".issue-tmp/layout-audit";

test("capture audit surfaces", async ({ page }) => {
    test.setTimeout(10 * 60_000);
    fs.mkdirSync(OUT, { recursive: true });

    const raw = tinker(`
    foreach ([
      'direct_transfers' => \\App\\Models\\DirectTransfer::class,
      'products' => \\App\\Models\\Product::class,
      'purchase_orders' => \\App\\Models\\PurchaseOrder::class,
      'sales_orders' => \\App\\Models\\SalesOrder::class,
      'transfer_requisitions' => \\App\\Models\\TransferRequisition::class,
      'users' => \\App\\Models\\User::class,
      'warehouses' => \\App\\Models\\Warehouse::class,
    ] as $k => $c) {
      $m = $c::query()->first();
      echo $k . '=' . ($m?->getKey() ?? 0) . "\\n";
    }
  `);
    const ids: Record<string, number> = {};
    for (const line of raw.split("\n")) {
        const m = line.trim().match(/^(\w+)=(\d+)$/);
        if (m) ids[m[1]] = parseInt(m[2], 10);
    }

    await loginAsAdmin(page);

    const shots: Array<{ key: string; url: string; w: number }> = [
        { key: "dashboard-1440", url: "/admin", w: 1440 },
        { key: "dashboard-768", url: "/admin", w: 768 },
        { key: "dashboard-375", url: "/admin", w: 375 },
        { key: "users-index-1440", url: "/admin/users", w: 1440 },
        { key: "users-index-1280", url: "/admin/users", w: 1280 },
        { key: "users-index-375", url: "/admin/users", w: 375 },
        { key: "users-view-1440", url: `/admin/users/${ids.users}`, w: 1440 },
        { key: "users-edit-1440", url: `/admin/users/${ids.users}/edit`, w: 1440 },
        { key: "products-view-1440", url: `/admin/products/${ids.products}`, w: 1440 },
        { key: "products-create-1440", url: "/admin/products/create", w: 1440 },
        { key: "purchase-orders-view-1440", url: `/admin/purchase-orders/${ids.purchase_orders}`, w: 1440 },
        { key: "sales-orders-view-1440", url: `/admin/sales-orders/${ids.sales_orders}`, w: 1440 },
        { key: "transfer-requisitions-view-1440", url: `/admin/transfer-requisitions/${ids.transfer_requisitions}`, w: 1440 },
        { key: "direct-transfers-view-1440", url: `/admin/direct-transfers/${ids.direct_transfers}`, w: 1440 },
        { key: "direct-transfers-create-768", url: "/admin/direct-transfers/create", w: 768 },
        { key: "warehouses-view-1440", url: `/admin/warehouses/${ids.warehouses}`, w: 1440 },
        { key: "stock-movements-1440", url: "/admin/stock-movements", w: 1440 },
        { key: "stock-movements-375", url: "/admin/stock-movements", w: 375 },
        { key: "in-transits-1440", url: "/admin/in-transits", w: 1440 },
        { key: "loss-ledgers-1440", url: "/admin/loss-ledgers", w: 1440 },
    ];

    for (const s of shots) {
        await page.setViewportSize({ width: s.w, height: 900 });
        await page.goto(s.url, { waitUntil: "domcontentloaded" });
        await page
            .waitForFunction(
                () => document.querySelectorAll(".fi-loading-section").length === 0,
                null,
                { timeout: 20_000 },
            )
            .catch(() => {});
        await page.waitForTimeout(1200);
        await page.screenshot({ path: `${OUT}/${s.key}.png`, fullPage: true });
    }
    console.log("SHOTS=" + shots.length);
});
