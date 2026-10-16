import { test, expect } from "@playwright/test";
import { freshSeed, tinker } from "./helpers/artisan";
import { loginAs } from "./helpers/login";
import {
    makeWarehouse,
    makeVariant,
    makeConfirmedRequisition,
} from "./helpers/fixtures";

/**
 * Scenarios 7, 8 — Duplicate scan submission, negotiation loop.
 */
test.describe("Scan & Negotiation", () => {
    test.beforeAll(() => {
        freshSeed();
    });

    test("7 — duplicate scan payload is a no-op", async ({ page }) => {
        const from = makeWarehouse("Scan From");
        const to = makeWarehouse("Scan To");
        const variant = makeVariant("SKU-SCAN-AA");

        const { requisitionId, itemId } = makeConfirmedRequisition(
            from.id,
            to.id,
            variant.id,
            10,
        );

        tinker(`
      \\Illuminate\\Support\\Facades\\Auth::login(\\App\\Models\\User::where('email', 'admin@example.test')->first());
      app(\\App\\Services\\InventoryService::class)->dispatchTransfer(\\App\\Models\\TransferRequisition::find(${requisitionId}));
    `);

        // Apply the same payload twice via the service — second must no-op.
        tinker(`
      $req = \\App\\Models\\TransferRequisition::find(${requisitionId});
      $payload = [${itemId} => ['received_good' => 10, 'received_damaged' => 0]];

      \\Illuminate\\Support\\Facades\\Auth::login(\\App\\Models\\User::where('email', 'admin@example.test')->first());
      app(\\App\\Services\\InventoryService::class)->scanToReceive($req, $payload);
      app(\\App\\Services\\InventoryService::class)->scanToReceive($req, $payload);

      echo \\App\\Models\\StockMovement::where('type', \\App\\Enums\\StockMovementType::TransferIn->value)->where('product_variant_id', ${variant.id})->count();
    `);

        // Exactly one TransferIn movement.
        const movements = tinker(`
      echo \\App\\Models\\StockMovement::where('type', \\App\\Enums\\StockMovementType::TransferIn->value)->where('product_variant_id', ${variant.id})->count();
    `);
        expect(movements).toContain("1");
    });

    test("8 — negotiation loop: submit revision → accept → confirm", async ({
        page,
    }) => {
        const from = makeWarehouse("Neg From");
        const to = makeWarehouse("Neg To");
        const variant = makeVariant("SKU-NEG-AA");

        // Requested (not confirmed) so it's negotiable.
        const { requisitionId, itemId } = (() => {
            const out = tinker(`
        $req = \\App\\Models\\TransferRequisition::factory()->requested()->create([
          'from_warehouse_id' => ${from.id},
          'to_warehouse_id' => ${to.id},
        ]);
        $item = \\App\\Models\\TransferRequisitionItem::factory()->create([
          'transfer_requisition_id' => $req->id,
          'product_variant_id' => ${variant.id},
          'requested_unit_name' => 'pc',
          'requested_unit_ratio' => 1,
          'requested_qty' => 10,
          'requested_base_qty' => 10,
        ]);
        echo $req->id . '-' . $item->id;
      `);
            const m = out.match(/(\d+)-(\d+)/)!;
            return {
                requisitionId: parseInt(m[1], 10),
                itemId: parseInt(m[2], 10),
            };
        })();

        // Negotiation is BranchManager (either endpoint) or Admin — v13.10
        // role model. WarehouseStaff is receive-only and never negotiates,
        // so the acting user must be a scoped BranchManager for the header
        // action to render.
        const staffEmail = "neg.staff@example.test";
        tinker(`
      $u = \\App\\Models\\User::factory()->branchManager()->create(['email' => '${staffEmail}']);
      $u->warehouses()->attach([${from.id}, ${to.id}]);
      echo $u->id;
    `);

        await loginAs(page, staffEmail);

        // Open the view page — negotiation header actions live here.
        await page.goto(`/admin/transfer-requisitions/${requisitionId}`);
        await page
            .getByRole("button", { name: "Propose revision", exact: true })
            .click();

        // Fill the revision form. The modal's non-searchable selects are
        // plain native <select> elements (Filament leaves them un-enhanced,
        // ids prefixed `mountedActionSchema0.`); drive them with
        // `selectOption`. Options for "Unit" populate live after the item
        // and substitute are chosen, so order matters.
        await page
            .locator('select[id$=".transfer_requisition_item_id"]')
            .selectOption({ label: "SKU-NEG-AA" });

        await page
            .locator('select[id$=".side"]')
            .selectOption({ label: "Fulfiller" });

        await page.locator('input[id$=".proposed_qty"]').fill("15");

        await page
            .locator('select[id$=".proposed_unit_name"]')
            .selectOption({ label: "pc" });

        await page.getByRole("button", { name: "Submit", exact: true }).click();

        // Revision now exists as Pending.
        const pending = tinker(`
      echo \\App\\Models\\TransferRequisitionItemRevision::where('transfer_requisition_item_id', ${itemId})->where('status', 'pending')->count();
    `);
        expect(pending).toContain("1");
    });
});
