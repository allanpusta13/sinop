import { test, expect } from "@playwright/test";
import { freshSeed, tinker } from "./helpers/artisan";
import { loginAsAdmin, expectNotification } from "./helpers/login";
import {
    makeWarehouse,
    makeVariant,
    makeOrderedPurchaseOrder,
    makeConfirmedSalesOrder,
} from "./helpers/fixtures";

/**
 * Scenarios 9, 10, 19 — Purchase lifecycle (over-receive), sales
 * lifecycle (over-return), sales dispatch self-reservation.
 *
 * Every lifecycle transition and its guard is driven through the real
 * Filament UI. Seeding still goes through `tinker()` (there is no UI
 * path to create a warehouse/variant or to adjust opening stock), but
 * no assertion depends on a service call the browser never made.
 *
 * Over-quantity is blocked by the modal's own field, not by the service
 * guard: `TextInput::maxValue()` registers BOTH the HTML5 `max`
 * attribute and a server-side `max:` rule
 * (`vendor/filament/forms/src/Components/TextInput.php:111`). Playwright
 * `fill()` bypasses Livewire's widget clamp, but the browser's native
 * constraint validation still refuses to submit — no request reaches
 * the server at all. So "the UI cannot over-receive" is the honest
 * browser-level claim. The service-level guard contract itself is
 * covered by Pest (`tests/Unit/Services/GuardsOutstandingQuantityTest.php`,
 * `tests/Feature/Concurrency/ConcurrencyTest.php`).
 */

/**
 * Open a row action from the list page's row ActionGroup menu, then wait
 * for its modal field to mount.
 *
 * Waits on the field rather than on `.fi-modal` itself: the page always
 * contains a hidden `database-notifications` dialog (and one lazily
 * mounted dialog per action), so `.fi-modal` is present but not visible
 * before the action opens.
 */
async function openRowAction(
    page: import("@playwright/test").Page,
    listPath: string,
    recordPath: string,
    actionLabel: string,
    readyField: string,
): Promise<void> {
    await page.goto(listPath);
    const row = page
        .locator('[role="listitem"]')
        .filter({ has: page.locator(`a[href$="${recordPath}"]`) })
        .first();
    await row.locator('button[aria-label="Actions"]').click();
    // Table row actions render as `menuitem` entries inside the row's
    // `menu "Actions"` (the row ActionGroup), not as `<button>`s.
    await page
        .getByRole("menuitem", { name: actionLabel, exact: true })
        .click();
    await page.locator(readyField).waitFor({ state: "visible" });
}

/** Filament's modal submit — distinct from the hidden row ActionGroup entry. */
const confirmInModal = (page: import("@playwright/test").Page) =>
    page.getByRole("button", { name: "Confirm", exact: true });

test.describe("Purchase & Sales", () => {
    test.beforeAll(() => {
        freshSeed();
    });

    test("9 — purchase receive is blocked above the outstanding quantity", async ({
        page,
    }) => {
        const warehouse = makeWarehouse("PO WH");
        const variant = makeVariant("SKU-PO-AA");

        const { orderId, itemId } = makeOrderedPurchaseOrder(
            warehouse.id,
            variant.id,
            10,
        );

        await loginAsAdmin(page);
        await openRowAction(
            page,
            "/admin/purchase-orders",
            `/purchase-orders/${orderId}`,
            "Receive",
            `input[id$="received.${itemId}"]`,
        );

        // The receive line defaults to the full outstanding quantity.
        const qtyInput = page.locator(`input[id$="received.${itemId}"]`);
        await expect(qtyInput).toHaveValue("10");
        await expect(qtyInput).toHaveAttribute("max", "10");

        // Stage an over-receive. `fill()` sets the value directly, so the
        // invalid value does reach the field — but the browser's own
        // constraint validation blocks the submit.
        await qtyInput.fill("999");
        const overflow = await qtyInput.evaluate(
            (el) => (el as HTMLInputElement).validity.rangeOverflow,
        );
        expect(overflow).toBe(true);

        await confirmInModal(page).click();
        await page.waitForTimeout(3000);

        // Still in the modal, no success toast, and nothing was written.
        await expect(page.locator(".fi-no-notification")).toHaveCount(0);
        await expect(qtyInput).toBeVisible();
        expect(await tinker(`
      echo \\App\\Models\\PurchaseOrderItem::find(${itemId})->received_base_qty;
    `)).toContain("0");

        // A valid quantity does go through the same modal.
        await qtyInput.fill("10");
        await confirmInModal(page).click();
        await expectNotification(page, /purchase received/i);

        expect(await tinker(`
      echo \\App\\Models\\PurchaseOrderItem::find(${itemId})->received_base_qty;
    `)).toContain("10");
    });

    test("10 — sales return is blocked above the dispatched quantity", async ({
        page,
    }) => {
        const warehouse = makeWarehouse("SO WH");
        const variant = makeVariant("SKU-SO-AA");

        const { orderId, itemId } = makeConfirmedSalesOrder(
            warehouse.id,
            variant.id,
            10,
        );

        await loginAsAdmin(page);

        // Dispatch the full 10 through the UI, so the return guard is
        // exercised against a real browser-driven dispatch.
        await openRowAction(
            page,
            "/admin/sales-orders",
            `/sales-orders/${orderId}`,
            "Dispatch",
            `.fi-modal input[id$="dispatch.${itemId}"]`,
        );
        await confirmInModal(page).click();
        await expectNotification(page, /sale dispatched/i);

        // Return via the recordReturn modal.
        await openRowAction(
            page,
            "/admin/sales-orders",
            `/sales-orders/${orderId}`,
            "Return",
            '.fi-modal select[id$="sales_order_item_id"]',
        );

        await page
            .locator('.fi-modal select[id$="sales_order_item_id"]')
            .selectOption({ index: 1 });

        const qtyInput = page.locator('.fi-modal input[id$="returned_base_qty"]');
        // Max is server-computed as dispatched − already returned.
        await expect(qtyInput).toHaveAttribute("max", "10");

        await qtyInput.fill("999");
        await confirmInModal(page).click();
        await page.waitForTimeout(3000);

        await expect(page.locator(".fi-no-notification")).toHaveCount(0);
        await expect(qtyInput).toBeVisible();
        expect(await tinker(`
      echo \\App\\Models\\SalesOrderItem::find(${itemId})->alreadyReturnedBaseQty();
    `)).toContain("0");

        // A valid partial return goes through.
        await qtyInput.fill("4");
        await confirmInModal(page).click();
        await expectNotification(page, /return recorded/i);
    });

    test("19 — sales dispatch succeeds for own-reserved stock", async ({
        page,
    }) => {
        // §6.5: dispatch excludes the order's own reservation from availability.
        // On-hand 5, order reserves 5. Dispatch of 5 must succeed.
        const warehouse = makeWarehouse("Self-Res WH");
        const variant = makeVariant("SKU-SR-AA");

        const { orderId, itemId } = makeConfirmedSalesOrder(
            warehouse.id,
            variant.id,
            5,
            5,
        );

        await loginAsAdmin(page);
        await openRowAction(
            page,
            "/admin/sales-orders",
            `/sales-orders/${orderId}`,
            "Dispatch",
            `.fi-modal input[id$="dispatch.${itemId}"]`,
        );

        // The cap is `min(outstanding, available)` with available computed
        // EXCLUDING this order's own reservation — so it reaches the full 5.
        const qtyInput = page.locator(`input[id$="dispatch.${itemId}"]`);
        await expect(qtyInput).toHaveAttribute("max", "5");
        await expect(page.locator(`label[for$="dispatch.${itemId}"]`)).toContainText(
            "available 5",
        );

        await confirmInModal(page).click();
        await expectNotification(page, /sale dispatched/i);

        await page.goto(`/admin/sales-orders/${orderId}`);
        await expect(page.locator("body")).toContainText("Dispatched");
    });
});
