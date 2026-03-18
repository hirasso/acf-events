/**
 * External dependencies
 */
import { request } from "@playwright/test";
import type { FullConfig } from "@playwright/test";

/**
 * WordPress dependencies
 * @see https://github.com/WordPress/gutenberg/blob/6b77e06605ce687684d508990b4bc68e07518867/packages/e2e-test-utils-playwright/README.md
 */
import { RequestUtils } from "@wordpress/e2e-test-utils-playwright";

async function globalSetup(config: FullConfig) {
  const { storageState, baseURL } = config.projects[0].use;
  const storageStatePath =
    typeof storageState === "string" ? storageState : undefined;

  const requestContext = await request.newContext({
    baseURL,
  });

  const requestUtils = new RequestUtils(requestContext, {
    storageStatePath,
  });

  // Authenticate and save the storageState to disk.
  await requestUtils.setupRest();

  // Reset the test environment before running the tests.
  await Promise.all([
    requestUtils.activateTheme("twentytwentyfive"),
    // requestUtils.deleteAllPosts(),
    // requestUtils.deleteAllBlocks(),
    // requestUtils.resetPreferences(),
    requestUtils.activatePlugin("acf-events-setup/setup-e2e.php"),
  ]);

  await requestContext.dispose();
}

export default globalSetup;
