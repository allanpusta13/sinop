import { test, expect } from "@playwright/test";
import { freshSeed, tinker } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";
import {
    makeWarehouse,
    makeVariant,
    makeConfirmedRequisition,
} from "./helpers/fixtures";

/**
 * Scenarios 3, 4, 14 — Loss, soft-delete, base-unit deletion.
 */
test.describe("Loss, Soft-Delete, Base-Unit Deletion", () => {
    test.beforeAll(() => {
        freshSeed();
    });

    test("3 — loss write-off from the recordLoss modal", async ({ page }) => {
        const from = makeWarehouse("Loss From");
        const to = makeWarehouse("Loss To");
        const variant = makeVariant("SKU-LOSS-AA");

        const { requisitionId, itemId } = makeConfirmedRequisition(
            from.id,
            to.id,
            variant.id,
            10,
        );

        // Dispatch via service to move to Dispatched.
        tinker(`
      \\Illuminate\\Support\\Facades\\Auth::login(\\App\\Models\\User::where('email', 'admin@example.test')->first());
      app(\\App\\Services\\InventoryService::class)->dispatchTransfer(\\App\\Models\\TransferRequisition::find(${requisitionId}));
    `);

        await loginAsAdmin(page);

        // Loss via the recordLoss modal on the requisition view page.
        await page.goto("/admin/transfer-requisitions");
        const row = page
            .locator('[role="listitem"]')
            .filter({
                has: page.locator(
                    `a[href$="/transfer-requisitions/${requisitionId}"]`,
                ),
            })
            .first();
        await row.locator('button[aria-label="Actions"]').click();
        // Row actions live in the row's ActionGroup menu: Filament renders
        // each entry with role `menuitem` inside `menu "Actions"`, not as a
        // standalone `<button>`.
        await page
            .getByRole("menuitem", { name: "Record loss", exact: true })
            .click();

        // The modal's selects are plain native <select> elements (Filament
        // does not enhance them into the custom `.fi-select-input-btn`
        // widget), so drive them with Playwright's `selectOption`. Target
        // by the action-schema-prefixed id to avoid the list page's
        // table-filter selects.
        await page
            .locator('select[id$="transfer_requisition_item_id"]')
            .selectOption({ label: "SKU-LOSS-AA" });

        // "Loss category" is required; it's another native <select>.
        await page
            .locator('select[id$="loss_category"]')
            .selectOption({ label: "Damage" });

        // Fill lost qty (id carries the mounted-action schema prefix).
        await page.locator('input[id$="lost_base_qty"]').fill("10");

        // Role-based lookup excludes the hidden row ActionGroup "Confirm".
        await page.getByRole("button", { name: "Confirm", exact: true }).click();

        // Verify the loss ledger row exists.
        const count = tinker(`
      echo \\App\\Models\\LossLedger::where('product_variant_id', ${variant.id})->count();
    `);
        expect(count).toContain("1");
    });

    test("4 — soft-delete guard blocks a product family with variants", async ({
        page,
    }) => {
        const variant = makeVariant("SKU-GUARD-AA");

        await loginAsAdmin(page);

        // Product deletion via the UI must throw (guard).
        const result = tinker(`
      try {
        \\App\\Models\\Product::find(${variant.id} ? \\App\\Models\\ProductVariant::find(${variant.id})->product_id : 1)->delete();
        echo 'DELETED';
      } catch (\\App\\Exceptions\\ProductFamilyHasVariantsException $e) {
        echo 'GUARDED';
      }
    `);

        expect(result).toContain("GUARDED");
    });

    test("14 — base-unit self-conversion row is undeletable via the UI", async ({
        page,
    }) => {
        const variant = makeVariant("SKU-BASE-AA", "pc");

        await loginAsAdmin(page);

        // Verify the observer created the base row.
        const baseRow = tinker(`
      echo \\App\\Models\\ProductVariantUnitConversion::where('product_variant_id', ${variant.id})
        ->where('unit_name', 'pc')
        ->where('base_unit_ratio', 1)
        ->count();
    `);
        expect(baseRow).toContain("1");

        // The action's UI hides the delete button on the base row.
        await page.goto("/admin/products");
        const row = page
            .locator('[role="listitem"]')
            .filter({ has: page.locator(`a[href$="/products/${variant.id}"]`) })
            .first();
        await row.locator('button[aria-label="Actions"]').click();
        await page
            .getByRole("menuitem", { name: "Manage units", exact: true })
            .click();

        // The base-unit row should not have a delete button.
        const deleteButtonsOnBaseRow = await page
            .locator("text=pc")
            .locator(
                'xpath=ancestor::div[contains(@class, "fi-fo-repeater-item")]',
            )
            .locator('button[aria-label*="Delete"]')
            .count();

        expect(deleteButtonsOnBaseRow).toBe(0);
    });
});
