import { test, Page } from "@playwright/test";
import { tinker } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";
import * as fs from "fs";

/**
 * Layout audit: forms, modals, console errors. Read-only.
 * Writes outside test-results so Playwright cleanup cannot remove it.
 */
const OUT = ".issue-tmp/layout-audit";

type Finding = Record<string, unknown>;

async function measureForm(page: Page, key: string) {
    const r = await page.evaluate(() => {
        const vw = window.innerWidth;
        const content = document.querySelector(".fi-page-content") ?? document.body;
        const results: Record<string, unknown> = {};

        // Sections / fieldsets and their widths.
        const sections = Array.from(
            document.querySelectorAll(".fi-sc-section, .fi-fo-fieldset"),
        ).map((el) => {
            const b = el.getBoundingClientRect();
            const label = el.querySelector(".fi-section-header-heading, legend");
            return {
                label: (label?.textContent ?? "").trim().slice(0, 40),
                x: Math.round(b.x),
                w: Math.round(b.width),
            };
        });
        results.sections = sections;

        // Field wrapper widths (are inputs full width, or narrow/uneven?).
        const fields = Array.from(
            document.querySelectorAll(".fi-fo-field-wrp, .fi-fo-component-ctn"),
        ).map((el) => {
            const b = el.getBoundingClientRect();
            return { w: Math.round(b.width), x: Math.round(b.x) };
        });
        const widths = fields.map((f) => f.w).sort((a, b) => b - a);
        results.fieldCount = fields.length;
        results.fieldWidths = widths.slice(0, 12);
        results.fieldWidthMin = widths.length ? widths[widths.length - 1] : null;

        // Tabs / repeaters / grids inside the form.
        results.grids = Array.from(document.querySelectorAll(".fi-grid")).map((el) => {
            const cs = getComputedStyle(el);
            return {
                cls: String(el.className).replace(/\s+/g, " ").slice(0, 60),
                w: Math.round(el.getBoundingClientRect().width),
                tracks: cs.gridTemplateColumns,
            };
        });
        results.repeaters = Array.from(document.querySelectorAll(".fi-fo-repeater")).map(
            (el) => ({ w: Math.round(el.getBoundingClientRect().width) }),
        );

        // Anything extending past the content box.
        const cr = content.getBoundingClientRect();
        results.overflowing = Array.from(content.querySelectorAll("*"))
            .filter((el) => {
                const b = el.getBoundingClientRect();
                return b.width > 0 && (b.right > cr.right + 2 || b.x < cr.x - 2);
            })
            .slice(0, 10)
            .map((el) => ({
                cls: String(el.className).replace(/\s+/g, " ").slice(0, 50),
                x: Math.round(el.getBoundingClientRect().x),
                right: Math.round(el.getBoundingClientRect().right),
            }));

        results.docOverflow =
            document.documentElement.scrollWidth - document.documentElement.clientWidth;
        results.pageContentW = Math.round(cr.width);
        results.vw = vw;
        return results;
    });
    return { key, url: page.url(), ...r };
}

async function measureModal(page: Page) {
    return page.evaluate(() => {
        const modal = document.querySelector(".fi-modal-window, .fi-modal");
        if (!modal) return { err: "no modal" };
        const b = modal.getBoundingClientRect();
        const body = modal.querySelector(".fi-modal-content");
        const fields = Array.from(
            modal.querySelectorAll(".fi-fo-field-wrp, .fi-fo-component-ctn"),
        ).map((el) => Math.round(el.getBoundingClientRect().width));
        return {
            w: Math.round(b.width),
            h: Math.round(b.height),
            x: Math.round(b.x),
            vw: window.innerWidth,
            maxH: getComputedStyle(modal).maxHeight,
            overflowY: getComputedStyle(modal).overflowY,
            bodyScroll: body ? (body as HTMLElement).scrollHeight : null,
            fieldCount: fields.length,
            fieldWidths: fields.slice(0, 10),
            grids: Array.from(modal.querySelectorAll(".fi-grid")).map((el) => ({
                w: Math.round(el.getBoundingClientRect().width),
                tracks: getComputedStyle(el).gridTemplateColumns,
            })),
        };
    });
}

test("audit forms, modals, console errors", async ({ page }) => {
    test.setTimeout(15 * 60_000);
    fs.mkdirSync(OUT, { recursive: true });

    const consoleErrors: Array<Record<string, unknown>> = [];
    page.on("console", (msg) => {
        if (msg.type() === "error") {
            consoleErrors.push({ url: page.url(), text: msg.text().slice(0, 300) });
        }
    });

    const raw = tinker(`
    foreach ([
      'products' => \\App\\Models\\Product::class,
      'orders' => \\App\\Models\\PurchaseOrder::class,
      'users' => \\App\\Models\\User::class,
      'transfers' => \\App\\Models\\DirectTransfer::class,
      'warehouses' => \\App\\Models\\Warehouse::class,
      'requisitions' => \\App\\Models\\TransferRequisition::class,
      'sales' => \\App\\Models\\SalesOrder::class,
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

    const findings: Finding[] = [];

    // ---- CREATE forms, desktop ----
    const createForms = [
        "/admin/products/create",
        "/admin/warehouses/create",
        "/admin/purchase-orders/create",
        "/admin/sales-orders/create",
        "/admin/direct-transfers/create",
        "/admin/transfer-requisitions/create",
    ];
    for (const url of createForms) {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(url, { waitUntil: "domcontentloaded" });
        await page
            .waitForFunction(
                () => document.querySelectorAll(".fi-loading-section").length === 0,
                null,
                { timeout: 20_000 },
            )
            .catch(() => {});
        await page.waitForTimeout(900);
        findings.push(await measureForm(page, `create:${url}:1440`));
        await page.screenshot({
            path: `${OUT}/form-${url.replace(/\//g, "_")}-1440.png`,
            fullPage: true,
        });
    }

    // ---- EDIT forms ----
    const editForms = [
        `/admin/products/${ids.products}/edit`,
        `/admin/users/${ids.users}/edit`,
    ];
    for (const url of editForms) {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(url, { waitUntil: "domcontentloaded" });
        await page.waitForTimeout(1200);
        findings.push(await measureForm(page, `edit:${url}:1440`));
        await page.screenshot({
            path: `${OUT}/form-${url.replace(/\//g, "_")}-1440.png`,
            fullPage: true,
        });
    }

    // ---- CREATE form at tablet: does it collapse sanely? ----
    await page.setViewportSize({ width: 768, height: 900 });
    await page.goto("/admin/products/create", { waitUntil: "domcontentloaded" });
    await page.waitForTimeout(1200);
    findings.push(await measureForm(page, "create:/admin/products/create:768"));

    // ---- MODALS via record action menu (role=menuitem) ----
    const modalTargets: Array<{ list: string; action: string; field: string }> = [
        { list: "/admin/users", action: "Edit", field: "form.name" },
        { list: "/admin/warehouses", action: "Edit", field: "form.name" },
        { list: "/admin/products", action: "Edit", field: "form.sku" },
    ];
    const modals: Finding[] = [];
    for (const t of modalTargets) {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(t.list, { waitUntil: "domcontentloaded" });
        await page.waitForTimeout(1500);

        const trigger = page.locator('button[aria-label*="Action"], .fi-ta-actions button').first();
        try {
            await trigger.click({ timeout: 8000 });
        } catch {
            modals.push({ list: t.list, action: t.action, err: "no action trigger" });
            continue;
        }
        const item = page.getByRole("menuitem", { name: t.action }).first();
        try {
            await item.click({ timeout: 8000 });
        } catch {
            modals.push({ list: t.list, action: t.action, err: "menuitem not found" });
            continue;
        }
        try {
            await page.waitForSelector(`#${t.field}`, { timeout: 15_000 });
        } catch {
            modals.push({ list: t.list, action: t.action, err: "modal field not shown" });
            continue;
        }
        await page.waitForTimeout(700);
        modals.push({ list: t.list, action: t.action, ...(await measureModal(page)) });
        await page.screenshot({
            path: `${OUT}/modal-${t.list.replace(/\//g, "_")}-${t.action}.png`,
        });
        await page.keyboard.press("Escape").catch(() => {});
        await page.waitForTimeout(400);
    }
    findings.push({ key: "modals", modals });

    fs.writeFileSync(`${OUT}/surfaces.json`, JSON.stringify(findings, null, 1));
    fs.writeFileSync(
        `${OUT}/console-errors.json`,
        JSON.stringify(consoleErrors, null, 1),
    );
    console.log(`SURFACES=${findings.length} CONSOLE_ERRORS=${consoleErrors.length}`);
});
