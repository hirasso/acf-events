import { test, expect } from "@playwright/test";

test.describe("WP Admin", () => {
  test("Can access the admin dashboard", async ({ page }) => {
    await page.goto("/wp-admin/index.php");
    await expect(page.locator('h1')).toContainText('Dashboard');
  });
});
