import { test, Page } from "@playwright/test";
import { tinker } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";
import * as fs from "fs";

const OUT = ".issue-tmp/layout-audit";

/** Walk ancestors of the first section, printing class + width. */
async function chainOf(page: Page) {
    return page.evaluate(() => {
        const sec = document.querySelector(".fi-sc-section");
        if (!sec) return { err: "no section" };
        const chain: unknown[] = [];
        let el: Element | null = sec;
        for (let i = 0; i < 9 && el; i++) {
            const b = el.getBoundingClientRect();
            chain.push({
                cls: String(el.className).replace(/\s+/g, " ").slice(0, 62),
                w: Math.round(b.width),
                tracks: getComputedStyle(el).gridTemplateColumns,
            });
            el = el.parentElement;
        }
        return { chain };
    });
}

async function formFields(page: Page) {
    return page.evaluate(() => {
        const f = Array.from(document.querySelectorAll(".fi-fo-field")).map((el) => {
            const b = el.getBoundingClientRect();
            const lbl = el.querySelector(".fi-fo-field-label");
            return {
                label: (lbl?.textContent ?? "").trim().slice(0, 26),
                x: Math.round(b.x),
                w: Math.round(b.width),
            };
        });
        const content = document.querySelector(".fi-page-content");
        return {
            fields: f,
            narrow: f.filter((x) => x.w > 0 && x.w < 300),
            contentW: content ? Math.round(content.getBoundingClientRect().width) : null,
            docOverflow:
                document.documentElement.scrollWidth - document.documentElement.clientWidth,
        };
    });
}

test("final: chains, form fields, modals", async ({ page }) => {
    test.setTimeout(15 * 60_000);
    fs.mkdirSync(OUT, { recursive: true });

    const raw = tinker(`
    foreach ([
      'products' => \\App\\Models\\Product::class,
      'users' => \\App\\Models\\User::class,
      'orders' => \\App\\Models\\PurchaseOrder::class,
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
    const out: Record<string, unknown> = {};

    // ---- 1. infolist ancestor chains: broken vs correct ----
    const views = [
        { k: "broken:products", u: `/admin/products/${ids.products}` },
        { k: "broken:purchase-orders", u: `/admin/purchase-orders/${ids.orders}` },
        { k: "broken:warehouses", u: `/admin/warehouses/${ids.warehouses}` },
        { k: "correct:users", u: `/admin/users/${ids.users}` },
    ];
    out.chains = {};
    for (const v of views) {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(v.u, { waitUntil: "domcontentloaded" });
        await page
            .waitForFunction(
                () => document.querySelectorAll(".fi-loading-section").length === 0,
                null,
                { timeout: 15_000 },
            )
            .catch(() => {});
        await page.waitForTimeout(1200);
        (out.chains as Record<string, unknown>)[v.k] = await chainOf(page);
    }

    // ---- 2. form field widths ----
    out.forms = {};
    for (const u of [
        "/admin/products/create",
        "/admin/users/create",
        "/admin/purchase-orders/create",
        "/admin/direct-transfers/create",
    ]) {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(u, { waitUntil: "domcontentloaded" });
        await page.waitForTimeout(1400);
        (out.forms as Record<string, unknown>)[u] = await formFields(page);
    }

    // ---- 3. auto-discovered modals: click every action BUTTON (not link) ----
    const modals: unknown[] = [];
    for (const list of ["/admin/users", "/admin/warehouses", "/admin/products"]) {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(list, { waitUntil: "domcontentloaded" });
        await page.waitForTimeout(1600);

        const labels: string[] = await page.evaluate(() =>
            Array.from(document.querySelectorAll("table button")).map((b) =>
                (b.textContent ?? "").trim(),
            ),
        );
        out[`buttons:${list}`] = labels;

        for (const label of labels) {
            if (!label || /sort|filter|search|column|per page|toggle/i.test(label)) continue;
            await page.goto(list, { waitUntil: "domcontentloaded" });
            await page.waitForTimeout(1400);
            try {
                await page
                    .locator(`table button:has-text("${label}")`)
                    .first()
                    .click({ timeout: 8000 });
            } catch {
                modals.push({ list, label, err: "click failed" });
                continue;
            }
            await page.waitForTimeout(1400);
            const m = await page.evaluate(() => {
                const el = document.querySelector(".fi-modal-window, .fi-modal");
                if (!el) return { err: "no modal" };
                const b = el.getBoundingClientRect();
                const cs = getComputedStyle(el);
                const fields = Array.from(el.querySelectorAll(".fi-fo-field")).map((f) =>
                    Math.round(f.getBoundingClientRect().width),
                );
                return {
                    w: Math.round(b.width),
                    h: Math.round(b.height),
                    x: Math.round(b.x),
                    y: Math.round(b.y),
                    vw: window.innerWidth,
                    vh: window.innerHeight,
                    maxH: cs.maxHeight,
                    overflowY: cs.overflowY,
                    scrollH: (el as HTMLElement).scrollHeight,
                    fieldCount: fields.length,
                    fieldWidths: fields.slice(0, 8),
                    grids: Array.from(el.querySelectorAll(".fi-grid")).map((g) => ({
                        cls: String(g.className).replace(/\s+/g, " ").slice(0, 46),
                        w: Math.round(g.getBoundingClientRect().width),
                        tracks: getComputedStyle(g).gridTemplateColumns,
                    })),
                    overflowing: Array.from(el.querySelectorAll("*"))
                        .filter((c) => {
                            const r = c.getBoundingClientRect();
                            return r.width > 0 && r.right > b.right + 2;
                        })
                        .slice(0, 6)
                        .map((c) => String(c.className).replace(/\s+/g, " ").slice(0, 46)),
                };
            });
            modals.push({ list, label, ...m });
            await page.screenshot({
                path: `${OUT}/modal-${list.replace(/\//g, "_")}-${label.replace(/\W+/g, "-")}.png`,
            });
            await page.keyboard.press("Escape").catch(() => {});
            await page.waitForTimeout(500);
        }
    }
    out.modals = modals;

    fs.writeFileSync(`${OUT}/final.json`, JSON.stringify(out, null, 1));
    console.log(`FINAL_DONE modals=${modals.length}`);
});
