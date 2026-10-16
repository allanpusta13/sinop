import { test, expect } from "@playwright/test";
import { freshSeed } from "./helpers/artisan";
import { loginAsAdmin, expectNotification } from "./helpers/login";
import {
    makeWarehouse,
    makeVariant,
    makeConfirmedRequisition,
} from "./helpers/fixtures";

/**
 * Scenario 1 — Full Transfer Lifecycle.
 *
 * Confirm → Dispatch → Scan-to-Receive → Completed.
 * The upstream create/submit/confirm steps are seeded by the fixture
 * helper; this spec drives the two UI transitions that matter.
 */
test.describe("Scenario 1 — Full Transfer Lifecycle", () => {
    test.beforeAll(() => {
        freshSeed();
    });

    test("confirms, dispatches, receives a requisition end-to-end", async ({
        page,
    }) => {
        const from = makeWarehouse("Source WH");
        const to = makeWarehouse("Destination WH");
        const variant = makeVariant("SKU-LIFE-AA");

        const { requisitionId } = makeConfirmedRequisition(
            from.id,
            to.id,
            variant.id,
            10,
        );

        await loginAsAdmin(page);

        // Open the requisition view page.
        await page.goto(`/admin/transfer-requisitions/${requisitionId}`);
        await expect(page.locator("body")).toContainText("Confirmed");

        // Dispatch — table action, opens a confirmation modal.
        await page.goto("/admin/transfer-requisitions");
        const rowHref = `a[href$="/transfer-requisitions/${requisitionId}"]`;
        const dispatchRow = page
            .locator('[role="listitem"]')
            .filter({ has: page.locator(rowHref) })
            .first();
        await dispatchRow.locator('button[aria-label="Actions"]').click();
        // Table row actions render as `menuitem` entries inside the row's
        // `menu "Actions"` (the row ActionGroup), not as `<button>`s.
        await page
            .getByRole("menuitem", { name: "Dispatch", exact: true })
            .click();
        // The dispatch action shares its "Confirm" label with the row
        // ActionGroup entry; role-based lookup excludes the hidden one and
        // resolves to the modal's submit button inside the alertdialog.
        await page.getByRole("button", { name: "Confirm", exact: true }).click();

        await expectNotification(page, /dispatch/i);

        // Status is now Dispatched.
        await page.goto(`/admin/transfer-requisitions/${requisitionId}`);
        await expect(page.locator("body")).toContainText("Dispatched");

        // Receive via the STN scan link — signed URL.
        // The scan route is on a signed URL; we need the print page which
        // renders the signed scan QR. Simplest path: hit the receive action
        // which redirects to the signed URL.
        await page.goto("/admin/transfer-requisitions");
        const receiveRow = page
            .locator('[role="listitem"]')
            .filter({ has: page.locator(rowHref) })
            .first();
        await receiveRow.locator('button[aria-label="Actions"]').click();
        // `scanToReceive` is a `->url()` action, so Filament renders an
        // `<a>` — but inside the row's dropdown every entry gets
        // `role="menuitem"` (support/components/dropdown/list/item), so the
        // accessible role is `menuitem`, not `link`.
        await page
            .getByRole("menuitem", { name: "Receive", exact: true })
            .click();

        // Now on the scan page. Fill the good qty input and submit.
        await page.waitForURL(/\/stn\/.+\/scan/);
        const firstQtyInput = page.locator('input[type="number"]').first();
        await firstQtyInput.fill("10");
        // `submit()` posts over Livewire; wait for that request to complete
        // before navigating away, otherwise the XHR is aborted and the
        // intake never commits.
        const submitDone = page.waitForResponse(
            (resp) =>
                resp.url().includes("/livewire") &&
                resp.url().endsWith("/update") &&
                resp.request().method() === "POST",
        );
        await page.locator('button:has-text("Submit scan")').click();
        await submitDone;

        // Back to the requisition — status is Completed.
        await page.goto(`/admin/transfer-requisitions/${requisitionId}`);
        await expect(page.locator("body")).toContainText("Completed");
    });
});
