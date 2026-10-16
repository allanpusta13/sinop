import { test, expect } from "@playwright/test";
import { freshSeed } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";
import { makeWarehouse, makeVariant } from "./helpers/fixtures";

/**
 * Scenarios 12, 13 — Wizard submit-button visibility, unit select flow.
 */
test.describe("Wizard & Unit Select", () => {
    test.beforeAll(() => {
        freshSeed();
    });

    test("12 — submit button only appears on the last wizard step", async ({
        page,
    }) => {
        const from = makeWarehouse("Wiz12 From");
        const to = makeWarehouse("Wiz12 To");
        const variant = makeVariant("SKU-W12-AA");

        await loginAsAdmin(page);
        await page.goto("/admin/direct-transfers/create");

        // Step 1 — no "Create" button visible.
        await expect(
            page.locator('button:has-text("Create")'),
        ).not.toBeVisible();

        // Step 1 selects are native <select> (un-enhanced by Filament).
        await page
            .locator("select")
            .first()
            .selectOption({ label: "Wiz12 From" });
        await page.locator("select").nth(1).selectOption({ label: "Wiz12 To" });
        await page.locator('button:has-text("Next")').click();
        await expect(
            page.locator('button:has-text("Create")'),
        ).not.toBeVisible();

        // Step 2 must be filled or per-step validation halts the wizard
        // and "Create" never renders. The items repeater is `->minItems(1)`
        // so Filament already pads one row; clicking "Add to items" would
        // create a second row whose selects share the same accessible name.
        // `product_variant_id` is an enhanced (searchable) Select driven by
        // Choices.js: its trigger is a <button> whose accessible name is the
        // field label, and its options are `[role="option"]` entries.
        // `unit_name` is a native <select> driven with `selectOption`.
        await page.waitForLoadState("networkidle");
        await page
            .getByRole("button", { name: "Product variant id" })
            .first()
            .click();
        const variantOption = page.getByRole("option", { name: "SKU-W12-AA" });
        await variantOption.waitFor({ state: "visible" });
        await variantOption.click();
        await page
            .getByRole("combobox", { name: "Unit name" })
            .first()
            .selectOption({ label: "pc" });
        await page.locator('input[type="number"]').last().fill("1");
        await page
            .locator("textarea")
            .fill("Wizard step visibility check");
        await page.locator('button:has-text("Next")').click();

        // Step 3 — Create is now visible.
        await expect(page.locator('button:has-text("Create")')).toBeVisible();
    });

    test("13 — unit select sources from the variant's own conversions", async ({
        page,
    }) => {
        const from = makeWarehouse("US From");
        const to = makeWarehouse("US To");
        const variant = makeVariant("SKU-US-AA", "pc");

        await loginAsAdmin(page);
        await page.goto("/admin/direct-transfers/create");

        // Step-1 warehouse selects are native <select>; a native <option>
        // is not clickable, so drive them with `selectOption`.
        await page.locator("select").first().selectOption({ label: "US From" });
        await page.locator("select").nth(1).selectOption({ label: "US To" });

        await page.locator('button:has-text("Next")').click();
        // The repeater is `->minItems(1)`: one row already exists, so no
        // "Add to items" click (it would duplicate the row's accessible
        // names). Scope locators to `.first()`.
        await page.waitForLoadState("networkidle");

        // Select the variant (enhanced Select trigger is a <button>,
        // options are `[role="option"]`), then inspect the unit select.
        await page
            .getByRole("button", { name: "Product variant id" })
            .first()
            .click();
        const variantOption = page.getByRole("option", { name: "SKU-US-AA" });
        await variantOption.waitFor({ state: "visible" });
        await variantOption.click();

        // The unit select should show 'pc' — sourced from the variant's own
        // conversion rows (the base-unit self-conversion the observer
        // materializes), never free text.
        await expect(
            page
                .getByRole("combobox", { name: "Unit name" })
                .first()
                .locator("option", { hasText: "pc" }),
        ).toHaveCount(1);
    });
});
