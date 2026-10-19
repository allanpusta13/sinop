import { test, expect, Page, Locator } from "@playwright/test";
import { freshSeed, tinker } from "./helpers/artisan";
import { loginAsAdmin, expectNotification } from "./helpers/login";
import { makeWarehouse } from "./helpers/fixtures";

/**
 * Import wizard (§28.3) browser journey — issues #219/#220/#223.
 *
 * Covers the surfaces Pest cannot: the real Livewire upload round-trip,
 * mode ToggleButtons (icons + live template-link switching), keyword
 * mapping guesses, the preview summary cards (with their status icons),
 * the reject path with row-level errors, and the final sync execution.
 */

/**
 * Advance one wizard step.
 *
 * Filament v5 + Livewire v4: FilePond's post-upload state commit can
 * race the step-transition commit, so the server occasionally
 * validates against a pre-upload snapshot ("The import file field is
 * required."). The state lands a moment later, so a bounded retry of
 * the same UI click is enough — no re-upload needed. On the mapping
 * step the transition also runs freezePreview() server-side, which can
 * take several seconds; once the step has advanced the (hidden) Next
 * button is skipped and only the signal is awaited.
 */
async function advanceStep(page: Page, nextStepSignal: Locator) {
    for (let attempt = 0; attempt < 3; attempt++) {
        const nextButton = page.locator('button:has-text("Next")');

        if (await nextButton.isVisible()) {
            await nextButton.click();
        }

        try {
            await nextStepSignal.waitFor({ state: "visible", timeout: 8000 });
            return;
        } catch {
            // Retry the transition or keep waiting for a slow round-trip.
        }
    }
    await expect(nextStepSignal).toBeVisible();
}

test.describe("Import wizard", () => {
    test.beforeAll(() => {
        freshSeed();
    });

    test("upload step renders the mode selector with icons and mode-aware template links", async ({
        page,
    }) => {
        await loginAsAdmin(page);
        await page.goto("/admin/products");

        await page
            .getByRole("button", { name: "Import", exact: true })
            .click();

        await expect(
            page.getByText("Import Products and Families"),
        ).toBeVisible();

        // Mode ToggleButtons — both options render with their icons
        // (Filament v5's Radio has no icon support; §28.3). The mode
        // field wrapper is the a11y group named after its label; the
        // regex anchors against the required-marker asterisk.
        const modeGroup = page.getByRole("group", {
            name: /^Import type\*?$/,
        });
        await expect(modeGroup).toBeVisible();

        const familiesMode = page
            .locator(".fi-btn")
            .filter({ hasText: "Product Families" });
        const variantsMode = page
            .locator(".fi-btn")
            .filter({ hasText: "Product Variants" });
        await expect(familiesMode).toBeVisible();
        await expect(variantsMode).toBeVisible();
        await expect(familiesMode.locator("svg")).toHaveCount(1);
        await expect(variantsMode.locator("svg")).toHaveCount(1);

        // Template links point at the selected (default: families)
        // definition and carry format-distinct icons. The route is
        // path-based: /admin/imports/{key}/template/{format}.
        const csvLink = page.getByRole("link", {
            name: "Download CSV template",
        });
        const xlsxLink = page.getByRole("link", {
            name: "Download XLSX template",
        });
        await expect(csvLink).toHaveAttribute(
            "href",
            /\/imports\/products\.families\/template\/csv/,
        );
        await expect(xlsxLink).toHaveAttribute(
            "href",
            /\/imports\/products\.families\/template\/xlsx/,
        );
        await expect(csvLink.locator("svg")).toHaveCount(1);
        await expect(xlsxLink.locator("svg")).toHaveCount(1);

        // Switching the mode re-renders the template links live
        // (mode is ->live(); the link URL closure reads $get('mode')).
        await page
            .getByText("Product Variants", { exact: true })
            .click();
        await expect(csvLink).toHaveAttribute(
            "href",
            /\/imports\/products\.variants\/template\/csv/,
        );
        await expect(xlsxLink).toHaveAttribute(
            "href",
            /\/imports\/products\.variants\/template\/xlsx/,
        );
    });

    test("families import runs end to end through upload, mapping and preview", async ({
        page,
    }) => {
        const csv = [
            "Family Name,Category",
            "E2E Family Alpha,Beverages",
            "E2E Family Beta,Snacks",
        ].join("\n");

        await loginAsAdmin(page);
        await page.goto("/admin/products");
        await page
            .getByRole("button", { name: "Import", exact: true })
            .click();

        // Upload step — families is the default mode; feed a CSV buffer
        // straight into the FileUpload input (hidden input accepts
        // setInputFiles; the chip appearing signals the Livewire
        // temporary upload finished).
        await page.locator('input[type="file"]').setInputFiles({
            name: "families-e2e.csv",
            mimeType: "text/csv",
            buffer: Buffer.from(csv),
        });
        await expect(
            page.getByText("families-e2e.csv").first(),
        ).toBeVisible();

        // Mapping step — keyword guessing pre-selects the file headers.
        // Searchable Selects render as <button> triggers whose visible
        // text is the selected option, not the label.
        const nameTrigger = page
            .getByRole("button", { name: "Family Name" })
            .first();
        await advanceStep(page, nameTrigger);
        await expect(nameTrigger).toContainText("Family Name");
        await expect(
            page
                .getByRole("button", { name: "Family Category" })
                .first(),
        ).toContainText("Category");

        // Preview step — frozen counts on the summary cards. The cards
        // carry status icons (one svg per card; the thead also uses
        // bg-zinc-50 but is not a <div>, so the locator stays unique).
        const totalCard = page.locator("div.bg-zinc-50").filter({
            hasText: "Total rows",
        });
        await advanceStep(page, totalCard);
        await expect(totalCard).toContainText("2");
        await expect(
            page.locator("div.bg-emerald-50").filter({
                hasText: "To create",
            }),
        ).toContainText("2");
        await expect(
            page.locator("div.bg-rose-50").filter({
                hasText: "To reject",
            }),
        ).toContainText("0");
        await expect(page.locator("div.bg-zinc-50 svg")).toHaveCount(1);
        await expect(page.locator("div.bg-emerald-50 svg")).toHaveCount(1);
        await expect(page.locator("div.bg-rose-50 svg")).toHaveCount(1);

        // Row details — both rows are ready-to-create.
        await expect(
            page.getByText("Will create a new record"),
        ).toHaveCount(2);

        // Execute — sync path (2 rows < queue threshold).
        await page.locator('button:has-text("Run import")').click();
        await expectNotification(page, /Import completed/);
        await expectNotification(page, /Created: 2/);

        // Database proof.
        const counts = tinker(`
      echo \\App\\Models\\Product::where('name', 'E2E Family Alpha')->count()
        . '-' . \\App\\Models\\Product::where('name', 'E2E Family Beta')->count();
    `).trim();
        expect(counts).toBe("1-1");
    });

    test("warehouse name conflicts reject in preview and never write the row", async ({
        page,
    }) => {
        // An existing warehouse whose name the CSV will collide with
        // (name_conflict: code identity, never name-merge — §28.4).
        makeWarehouse("E2E Existing WH");

        const csv = [
            "Code,Name",
            "WH-E2E-OK,E2E Warehouse North",
            "WH-E2E-CL,E2E Existing WH",
        ].join("\n");

        await loginAsAdmin(page);
        await page.goto("/admin/warehouses");
        await page
            .getByRole("button", { name: "Import", exact: true })
            .click();
        await expect(page.getByText("Import warehouses")).toBeVisible();

        // Single-definition resources render no mode selector (§28.3).
        await expect(
            page.getByRole("group", { name: /^Import type\*?$/ }),
        ).toHaveCount(0);

        await page.locator('input[type="file"]').setInputFiles({
            name: "warehouses-e2e.csv",
            mimeType: "text/csv",
            buffer: Buffer.from(csv),
        });
        await expect(
            page.getByText("warehouses-e2e.csv").first(),
        ).toBeVisible();

        const codeTrigger = page
            .getByRole("button", { name: "Code" })
            .first();
        await advanceStep(page, codeTrigger);
        await expect(codeTrigger).toContainText("Code");

        // Preview — one create, one reject with the name_conflict
        // reason rendered in the row details.
        const rejectCard = page.locator("div.bg-rose-50").filter({
            hasText: "To reject",
        });
        await advanceStep(page, rejectCard);
        await expect(
            page.locator("div.bg-emerald-50").filter({
                hasText: "To create",
            }),
        ).toContainText("1");
        await expect(rejectCard).toContainText("1");
        await expect(
            page.getByText('No warehouse with code "WH-E2E-CL"'),
        ).toBeVisible();

        await page.locator('button:has-text("Run import")').click();
        await expectNotification(page, /Import completed/);
        await expectNotification(page, /Created: 1/);
        await expectNotification(page, /Rejected: 1/);

        // Database proof — the accepted row exists, the conflicting
        // row was never written.
        const counts = tinker(`
      echo \\App\\Models\\Warehouse::where('code', 'WH-E2E-OK')->count()
        . '-' . \\App\\Models\\Warehouse::where('code', 'WH-E2E-CL')->count();
    `).trim();
        expect(counts).toBe("1-0");
    });
});
