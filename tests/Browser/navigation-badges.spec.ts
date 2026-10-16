import { test, expect } from "@playwright/test";
import { freshSeed, tinker } from "./helpers/artisan";
import { loginAsAdmin } from "./helpers/login";
import { makeWarehouse } from "./helpers/fixtures";

/**
 * Scenarios 15, 15b, 15c — Navigation badges.
 *
 * Badge counts are whole-database counts scoped by the acting user's
 * warehouse authority (`ScopesNavigationBadges`), so:
 *   - the canonical seed already contributes one Requested requisition
 *     (TR-2), and
 *   - a `beforeAll` seed would let each test's rows inflate the next
 *     test's badge.
 * Reseed per test (`beforeEach`) and assert the count the database
 * actually holds for that scope rather than a hardcoded literal.
 */
test.describe("Navigation Badges", () => {
    test.beforeEach(() => {
        freshSeed();
    });

    test("15 — badge count visible on Transfer Requisitions sidebar item", async ({
        page,
    }) => {
        // Seed one Requested requisition.
        tinker(`
      $from = \\App\\Models\\Warehouse::factory()->create();
      $to = \\App\\Models\\Warehouse::factory()->create();
      \\App\\Models\\TransferRequisition::factory()->requested()->create([
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
      ]);
    `);

        await loginAsAdmin(page);

        // Admin scope = every warehouse; badge = all Requested rows.
        const count = tinker(`
      echo \\App\\Models\\TransferRequisition::where('status', \\App\\Enums\\TransferRequisitionStatus::Requested->value)->count();
    `).trim();

        const sidebarEntry = page.locator(
            'a:has-text("Transfer Requisitions")',
        );
        await expect(
            sidebarEntry.getByText(count, { exact: true }),
        ).toBeVisible();
    });

    test("15b — single-warehouse staff sees only their warehouse in the badge", async ({
        page,
    }) => {
        // Create a staff user assigned to one warehouse, plus requisitions
        // in two warehouses.
        const warehouseA = makeWarehouse("Badge A");
        const warehouseB = makeWarehouse("Badge B");

        tinker(`
      $staff = \\App\\Models\\User::factory()->create(['email' => 'badge.test@example.test']);
      $staff->warehouses()->attach(${warehouseA.id});

      // Requisition touching A — counted.
      \\App\\Models\\TransferRequisition::factory()->requested()->create([
        'from_warehouse_id' => ${warehouseA.id},
        'to_warehouse_id' => ${warehouseB.id},
      ]);
      // Requisition touching B only — not counted.
      $other = \\App\\Models\\Warehouse::factory()->create();
      \\App\\Models\\TransferRequisition::factory()->requested()->create([
        'from_warehouse_id' => ${warehouseB.id},
        'to_warehouse_id' => $other->id,
      ]);
    `);

        await page.goto("/admin/login");
        await page.fill('input[id="form.email"]', "badge.test@example.test");
        await page.fill('input[id="form.password"]', "password");
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/admin(?!\/login)/);

        // Staff scope = assigned warehouses only; badge = Requested rows
        // whose from/to endpoints include warehouse A.
        const count = tinker(`
      echo \\App\\Models\\TransferRequisition::where('status', \\App\\Enums\\TransferRequisitionStatus::Requested->value)
        ->where(fn ($q) => $q->where('from_warehouse_id', ${warehouseA.id})->orWhere('to_warehouse_id', ${warehouseA.id}))
        ->count();
    `).trim();

        const sidebarEntry = page.locator(
            'a:has-text("Transfer Requisitions")',
        );
        await expect(
            sidebarEntry.getByText(count, { exact: true }),
        ).toBeVisible();
    });

    test("15c — admin with empty pivot sees the full count", async ({
        page,
    }) => {
        tinker(`
      $a = \\App\\Models\\Warehouse::factory()->create();
      $b = \\App\\Models\\Warehouse::factory()->create();
      \\App\\Models\\TransferRequisition::factory()->count(3)->requested()->create([
        'from_warehouse_id' => $a->id,
        'to_warehouse_id' => $b->id,
      ]);
    `);

        await loginAsAdmin(page);

        // Admin scope = every warehouse; badge = all Requested rows
        // (canonical seed's TR-2 counts here too).
        const count = tinker(`
      echo \\App\\Models\\TransferRequisition::where('status', \\App\\Enums\\TransferRequisitionStatus::Requested->value)->count();
    `).trim();

        const sidebarEntry = page.locator(
            'a:has-text("Transfer Requisitions")',
        );
        await expect(
            sidebarEntry.getByText(count, { exact: true }),
        ).toBeVisible();
    });
});
