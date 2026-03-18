import { defineConfig, devices } from "@playwright/test";

import { fileURLToPath } from "node:url";
import path from "node:path";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

import { execSync } from "node:child_process";
import { log } from "console";

const dd = function (...args: any[]) {
  log(...args);
  process.exit(1);
};

function getStatus() {
  const output = execSync("pnpm run wp-env-test status --json", {
    encoding: "utf-8",
  });
  const json = output.split("\n").find((line) => line.trim().startsWith("{"));
  return json ? JSON.parse(json) : undefined;
}

export const authFile = path.join(__dirname, "playwright/.auth/user.json");

const isCI = Boolean(process.env.CI);

export const baseURL = new URL(getStatus().urls.development);

/**
 * See https://playwright.dev/website/test-configuration.
 */
export default defineConfig({
  /* Run this file before starting the tests */
  globalSetup: require.resolve('./playwright.setup.ts'),
  /* Run this file after all the tests have finished */
  // globalTeardown: path.resolve(__dirname, './playwright.teardown.ts'),
  /* Directory containing the test files */
  testDir: "./tests",
  /* Folder for test artifacts: screenshots, videos, ... */
  outputDir: "./results",
  /* Timeout individual tests after 5 seconds */
  timeout: 10_000,
  /* Run tests in files in parallel */
  fullyParallel: true,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: isCI,
  /* Retry on CI only */
  retries: isCI ? 1 : 0,
  /* Limit parallel workers on CI, use default locally. */
  workers: isCI ? 1 : undefined,
  // Limit the number of failures on CI to save resources
  maxFailures: isCI ? 10 : undefined,
  /* Reporter to use. See https://playwright.dev/website/test-reporters */
  reporter: isCI
    ? [
        ["dot"],
        ["github"],
        ["json", { outputFile: "../../playwright-results.json" }],
      ]
    : [
        ["list"],
        ["html", { outputFolder: "./reports/html", open: "on-failure" }],
      ],

  expect: {
    /* Timeout async expect matchers after 3 seconds */
    timeout: 3_000,
  },

  /* Shared settings for all the projects below. See https://playwright.dev/website/api/class-testoptions. */
  use: {
    baseURL: baseURL.href,
    headless: true,
    viewport: {
      width: 960,
      height: 700,
    },
    ignoreHTTPSErrors: true,
    locale: "en-US",
    contextOptions: {
      reducedMotion: "reduce",
      strictSelectors: true,
    },
    storageState: process.env.STORAGE_STATE_PATH,
    actionTimeout: 10_000, // 10 seconds.
    trace: "retain-on-failure",
    screenshot: "only-on-failure",
    video: "on-first-retry",
  },

  /* Configure projects for setup and major browsers */
  projects: [
    { name: "setup", testMatch: /.*\.setup\.ts/ },
    {
      name: "chromium",
      use: {
        ...devices["Desktop Chrome"],
        storageState: authFile,
      },
      dependencies: ["setup"],
    },
    {
      name: "firefox",
      use: {
        ...devices["Desktop Firefox"],
        storageState: authFile,
      },
      dependencies: ["setup"],
    },
    {
      name: "webkit",
      use: {
        ...devices["Desktop Safari"],
        storageState: authFile,
      },
      dependencies: ["setup"],
    },
  ],

  /* Run your local dev server before starting the tests */
  webServer: {
    command: 'pnpm run wp-env-test start',
    url: baseURL.href,
    timeout: 120_000,
    reuseExistingServer: true,
  },
});
