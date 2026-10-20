import { test, expect, Locator, Page } from "@playwright/test";
import * as fs from "fs";
import { freshSeed, tinker } from "./helpers/artisan";
import {
    loginAsAdmin,
    loginAsSingleWarehouse,
    expectNotification,
} from "./helpers/login";

/**
 * Export action (#225 master data, #226 transactions and stock ledger)
 * browser journey — the surfaces Pest cannot cover: the real header
 * action opening its modal, the type/format ToggleButtons, the
 * synchronous "Export ready" toast with its download link, an actual
 * file arriving over the wire, and the warehouse narrowing a restricted
 * user sees in the rows they receive.
 *
 * Every export here stays under the 100-row inline threshold
 * (`ExportService::QUEUE_THRESHOLD`) except the final test, which
 * deliberately pushes past it to assert the queued path.
 */

/**
 * Last meaningful line of a tinker snippet's stdout — drops ANSI colour
 * codes, blank lines and PsySH's bare prompt.
 */
function lastOutputLine(raw: string): string {
    const lines = raw
        .replace(/\u001b\[[0-9;]*m/g, "")
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter((line) => line !== "" && !/^>+$/.test(line));

    return lines[lines.length - 1] ?? "";
}

/** The header row of a CSV export, with League CSV's quoting removed. */
function headerLine(csv: string): string {
    return csv.split("\n")[0].replace(/"/g, "");
}

/** Open the shared Export modal on the List page currently shown. */
async function openExport(page: Page): Promise<Locator> {
    await page
        .getByRole("button", { name: "Export", exact: true })
        .first()
        .click();

    // The dialog is named after its heading, so a row action's modal
    // opened on the same page can never be mistaken for it. Its own box
    // is collapsed (the panel is absolutely positioned), so the check
    // lands on the heading the dialog is named for.
    const dialog = page.getByRole("dialog", { name: "Export data" });
    await expect(
        dialog.getByRole("heading", { name: "Export data" }),
    ).toBeVisible();

    return dialog;
}

/**
 * Submit the open Export modal, wait for the inline completion toast and
 * its download link, then actually pull the file off the wire.
 */
async function exportToFile(
    page: Page,
    dialog: Locator,
): Promise<{ filename: string; text: string; bytes: Buffer }> {
    await dialog.getByRole("button", { name: "Export", exact: true }).click();

    const downloadLink = page.getByRole("link", { name: "Download file" });
    await expectNotification(page, /Export ready/);
    await expect(downloadLink).toHaveAttribute(
        "href",
        /\/admin\/exports\/.+\/download/,
    );

    // The link opens in a new tab (SPA-mode escape hatch), so the
    // download event belongs to the context, not to this page.
    const [download] = await Promise.all([
        page.context().waitForEvent("download"),
        downloadLink.click(),
    ]);

    const filePath = await download.path();
    expect(filePath).not.toBeNull();

    const bytes = fs.readFileSync(filePath as string);

    return {
        filename: download.suggestedFilename(),
        text: bytes.toString("utf-8"),
        bytes,
    };
}

/** The seeded warehouse codes, in the order the seeder made them. */
function seededWarehouseCodes(): string[] {
    const out = tinker(`
      echo implode('|', \\App\\Models\\Warehouse::query()->limit(3)->pluck('code')->all());
    `);

    return lastOutputLine(out).split("|");
}

test.describe("Export action", () => {
    test.beforeAll(() => {
        freshSeed();
    });

    test("admin exports warehouses as CSV and downloads the file", async ({
        page,
    }) => {
        await loginAsAdmin(page);
        await page.goto("/admin/warehouses");

        const dialog = await openExport(page);
        await expect(dialog).toContainText(
            "The export follows the current table filters and your warehouse access.",
        );

        // One exportable type on this page → the type selector is not
        // rendered at all; only the format choice is.
        await expect(
            dialog.getByRole("group", { name: /^Data to export\*?$/ }),
        ).toHaveCount(0);

        const formatGroup = dialog.getByRole("group", {
            name: /^File format\*?$/,
        });
        await expect(formatGroup).toBeVisible();
        await expect(
            formatGroup.locator(".fi-btn").filter({ hasText: "CSV" }),
        ).toBeVisible();
        await expect(
            formatGroup.locator(".fi-btn").filter({ hasText: "XLSX" }),
        ).toBeVisible();

        const file = await exportToFile(page, dialog);

        await expectNotification(
            page,
            /Warehouses export finished — 3 rows/,
        );

        expect(file.filename).toMatch(/\.csv$/);

        // The header row carries the same labels the table shows.
        expect(headerLine(file.text)).toBe(
            "Code,Name,Location,Status,Staff,Ledger entries",
        );

        // One `WH-####` code per exported warehouse — the seeded three.
        // (Warehouse locations are multi-line faker addresses, so the
        // file is asserted on its cells rather than on line count.)
        expect(file.text.match(/WH-\d{4}/g)).toHaveLength(3);
        for (const code of seededWarehouseCodes()) {
            expect(file.text).toContain(code);
        }
    });

    test("products export offers four types and the chosen type decides the file", async ({
        page,
    }) => {
        await loginAsAdmin(page);
        await page.goto("/admin/products");

        const dialog = await openExport(page);

        const typeGroup = dialog.getByRole("group", {
            name: /^Data to export\*?$/,
        });
        await expect(typeGroup).toBeVisible();
        for (const label of [
            "Product families",
            "Product variants",
            "Current prices",
            "Unit conversions",
        ]) {
            await expect(
                typeGroup.locator(".fi-btn").filter({ hasText: label }),
            ).toBeVisible();
        }

        // Switch the type live — the export runs against whichever
        // definition is selected when the form is submitted.
        await typeGroup
            .locator(".fi-btn")
            .filter({ hasText: "Unit conversions" })
            .click();

        const file = await exportToFile(page, dialog);

        await expectNotification(
            page,
            /Unit conversions export finished/,
        );

        expect(file.filename).toMatch(/\.csv$/);
        expect(headerLine(file.text)).toBe(
            "SKU,Unit,Base unit ratio,Default purchase unit,Default transfer unit",
        );
    });

    test("warehouse-scoped staff download only their own warehouse", async ({
        page,
    }) => {
        const ownCode = lastOutputLine(
            tinker(`
      $user = \\App\\Models\\User::where('email', 'staff.wh0@example.test')->first();
      echo $user->warehouses()->pluck('code')->first();
    `),
        );
        expect(ownCode).toMatch(/^WH-\d{4}$/);

        await loginAsSingleWarehouse(page);
        await page.goto("/admin/warehouses");

        const dialog = await openExport(page);
        const file = await exportToFile(page, dialog);

        // Server-side scope, not a filtered table view: the file the
        // browser receives holds exactly one warehouse — theirs.
        await expectNotification(
            page,
            /Warehouses export finished — 1 rows/,
        );
        expect(headerLine(file.text)).toBe(
            "Code,Name,Location,Status,Staff,Ledger entries",
        );

        const codes = file.text.match(/WH-\d{4}/g) ?? [];
        expect(codes).toHaveLength(1);
        expect(codes[0]).toBe(ownCode);
    });

    test("xlsx export downloads a real spreadsheet, not a renamed CSV", async ({
        page,
    }) => {
        await loginAsAdmin(page);
        await page.goto("/admin/warehouses");

        const dialog = await openExport(page);
        const formatGroup = dialog.getByRole("group", {
            name: /^File format\*?$/,
        });
        await formatGroup.locator(".fi-btn").filter({ hasText: "XLSX" }).click();

        const file = await exportToFile(page, dialog);

        await expectNotification(page, /Warehouses export finished/);

        expect(file.filename).toMatch(/\.xlsx$/);

        // XLSX is a ZIP container — "PK" magic bytes, never CSV text.
        expect(file.bytes.subarray(0, 2).toString("latin1")).toBe("PK");
        expect(file.text.startsWith("Code,Name")).toBe(false);
    });

    test("exports over the queue threshold report as queued and hand out no link", async ({
        page,
    }) => {
        // Push past QUEUE_THRESHOLD (100). Three-digit codes cannot
        // collide with the seeder's four-digit `WH-####` ones.
        tinker(`
      foreach (range(1, 110) as $i) {
        \\App\\Models\\Warehouse::factory()->create([
          'code' => 'WH-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
        ]);
      }
    `);

        await loginAsAdmin(page);
        await page.goto("/admin/warehouses");

        const dialog = await openExport(page);
        await dialog.getByRole("button", { name: "Export", exact: true }).click();

        // The page is not blocked and no download is offered yet — the
        // link only arrives later through a database notification.
        await expectNotification(page, "Export queued");
        await expectNotification(page, /is being generated/);
        await expect(
            page.getByRole("link", { name: "Download file" }),
        ).toHaveCount(0);

        const jobs = Number(
            lastOutputLine(tinker("echo \\DB::table('jobs')->count();")),
        );
        expect(jobs).toBeGreaterThan(0);
    });
});
