import { defineConfig, devices } from "@playwright/test";

/**
 * Standalone Playwright config for the browser E2E suite (blueprint §12).
 *
 * Not Pest Browser. Scenarios share one database and run serially
 * (workers: 1, fullyParallel: false); the app is served by a dedicated
 * `php artisan serve` instance booted with APP_ENV=testing so Laravel
 * reads `.env.testing` (the dedicated test DB) instead of the dev `.env`.
 *
 * The dev site (EnvKit, port 80) is never touched.
 */
const PORT = 8123;
// The suite must run against a server THIS process manages, booted with
// APP_ENV=testing, never against the EnvKit dev vhost (`sinop.test`), which
// serves the dev database. Overriding this also moves the managed server's
// port, so the two can never disagree.
const BASE_URL = process.env.PLAYWRIGHT_BASE_URL ?? `http://127.0.0.1:${PORT}`;
const SERVER_PORT = Number(new URL(BASE_URL).port) || PORT;

export default defineConfig({
    testDir: "./tests/Browser",
    fullyParallel: false,
    workers: 1,
    timeout: 90_000,
    expect: {
        timeout: 15_000,
    },
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI
        ? [["github"], ["html", { open: "never" }]]
        : [["list"]],
    use: {
        baseURL: BASE_URL,
        trace: "retain-on-failure",
        screenshot: "only-on-failure",
    },
    projects: [
        {
            name: "chromium",
            // Uses Playwright's bundled Chromium (fetched by
            // `npx playwright install chromium --only-shell`).
            // `PLAYWRIGHT_CHANNEL` is an optional escape hatch that swaps in
            // a system browser channel (e.g. `chrome`, `msedge`) instead; it
            // is NOT required for a normal run.
            use: {
                ...devices["Desktop Chrome"],
                ...(process.env.PLAYWRIGHT_CHANNEL
                    ? { channel: process.env.PLAYWRIGHT_CHANNEL }
                    : {}),
            },
        },
    ],
    webServer: {
        command: `php artisan serve --port=${SERVER_PORT}`,
        url: BASE_URL,
        // Reuse only when explicitly allowed. A leftover server from an
        // earlier run was booted under a different `.env`, and reusing it
        // silently serves the wrong database while `tinker()` helpers still
        // read the test DB — the suite then asserts against a database the
        // browser never saw. Default to a fresh server; set
        // PLAYWRIGHT_REUSE_SERVER=1 to opt in for fast local iteration.
        reuseExistingServer: process.env.PLAYWRIGHT_REUSE_SERVER === "1",
        timeout: 60_000,
        env: { APP_ENV: "testing" },
    },
});
