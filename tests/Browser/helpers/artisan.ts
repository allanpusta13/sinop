import { execSync, ExecSyncOptions } from "child_process";

const OPTS: ExecSyncOptions = {
    encoding: "utf-8",
    // 45s was too tight against the configured MySQL target (E2E seeds the
    // dev database per .env). Playwright's own test timeout is 90s.
    timeout: 120_000,
    stdio: "pipe",
};

/**
 * E2E runs under APP_ENV=testing so Laravel loads `.env.testing`
 * (dedicated MySQL DB) instead of `.env` (dev DB). Without this,
 * `freshSeed()`'s `migrate:fresh` would wipe the developer database.
 */
const ENV = "--env=testing";

/** Test database the E2E suite is allowed to touch. */
const TEST_DB = "filament-inventory_test";

/** Run `php artisan <command>` in the testing environment. */
export function artisan(command: string): string {
    return execSync(`php artisan ${ENV} ${command}`, OPTS) as unknown as string;
}

/**
 * Run a PHP snippet through tinker by piping it to stdin.
 *
 * `--execute="..."` is fragile: any newline in the payload (leading, trailing,
 * or between statements) makes PsySH silently parse nothing — the snippet
 * neither runs nor prints — and shell quoting mangles `$` and `"`. Piping to
 * stdin sidesteps all of it: multi-statement snippets, comments, and variable
 * interpolation all work unchanged.
 */
export function tinker(code: string): string {
    return execSync(`php artisan ${ENV} tinker`, {
        ...OPTS,
        input: code,
    }) as unknown as string;
}

/**
 * Fail-closed guard (H1): resolve the database the `--env=testing`
 * process actually connects to. If `.env.testing` is missing or
 * malformed, Laravel silently falls back to `.env` (dev DB) — abort
 * before any destructive command rather than trusting the flag.
 */
function assertTestDatabase(): void {
    const resolved = tinker(
        "echo \\DB::connection()->getDatabaseName();",
    ).trim();

    if (resolved !== TEST_DB) {
        throw new Error(
            `[db-isolation] refusing destructive command: --env=testing resolved to database "${resolved}", expected "${TEST_DB}". ` +
                "Ensure .env.testing exists (see .env.testing.example).",
        );
    }
}

/** Fresh schema + canonical Phase 02 seed (guarded — H1). */
export function freshSeed(): void {
    assertTestDatabase();
    artisan("migrate:fresh --seed --force");
}

/** Truncate the ledger tables — used between some specs. */
export function cleanLedger(): void {
    tinker(`
    \\App\\Models\\StockMovement::query()->delete();
    \\App\\Models\\StockMovementIdempotencyKey::query()->delete();
    \\App\\Models\\InTransit::query()->delete();
    \\App\\Models\\LossLedger::query()->delete();
  `);
}
