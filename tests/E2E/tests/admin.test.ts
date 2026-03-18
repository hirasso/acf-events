import { test, expect } from "@playwright/test";

test.describe("WP Admin", () => {
  test("Redirects the admin dashboard to the Pages edit screen", async ({ page }) => {
    await page.goto("/wp-admin/index.php");
    await expect(page.locator('h1')).toContainText('Pages');
    await expect(page).toHaveURL(/edit\.php\?post_type=page/);
  });
});
