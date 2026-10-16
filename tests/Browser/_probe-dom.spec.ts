import { test } from "@playwright/test";
import { loginAsAdmin } from "./helpers/login";
import * as fs from "fs";

test("dump real filament v5 class names", async ({ page }) => {
    test.setTimeout(5 * 60_000);
    await loginAsAdmin(page);
    await page.setViewportSize({ width: 1440, height: 900 });
    const out: Record<string, unknown> = {};

    await page.goto("/admin/users/create", { waitUntil: "domcontentloaded" });
    await page.waitForTimeout(1800);
    out.createForm = await page.evaluate(() => {
        const classes = new Map<string, number>();
        document.querySelectorAll(".fi-page-content *").forEach((el) => {
            String(el.className)
                .split(/\s+/)
                .filter((c) => c.startsWith("fi-fo") || c.startsWith("fi-sc-"))
                .forEach((c) => classes.set(c, (classes.get(c) ?? 0) + 1));
        });
        return {
            formClasses: [...classes.entries()].sort((a, b) => b[1] - a[1]).slice(0, 30),
            inputs: Array.from(document.querySelectorAll("input,select,textarea"))
                .map((el) => el.id)
                .filter(Boolean)
                .slice(0, 12),
            bodyText: document.body.innerText.slice(0, 300),
        };
    });

    await page.goto("/admin/users", { waitUntil: "domcontentloaded" });
    await page.waitForTimeout(2000);
    out.list = await page.evaluate(() => {
        const row = document.querySelector("table tbody tr");
        const cell = row?.querySelector("td:last-child, .fi-ta-cell:last-child");
        const btns = Array.from(document.querySelectorAll("table button, table a")).map((el) => ({
            tag: el.tagName.toLowerCase(),
            cls: String(el.className).replace(/\s+/g, " ").slice(0, 60),
            label: el.getAttribute("aria-label") ?? el.textContent?.trim().slice(0, 25),
            role: el.getAttribute("role"),
        }));
        return { cellHtml: cell?.innerHTML.slice(0, 500), btns: btns.slice(0, 20) };
    });

    fs.writeFileSync(".issue-tmp/layout-audit/dom.json", JSON.stringify(out, null, 1));
    console.log("DOM_DONE");
});
