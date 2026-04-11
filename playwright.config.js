// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Playwright configuration for VBP E2E tests.
 *
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
    // Test directory
    testDir: './tests/e2e',

    // Test file patterns
    testMatch: '**/*.spec.js',

    // Maximum time one test can run
    timeout: 30 * 1000,

    // Maximum time expect() should wait
    expect: {
        timeout: 5000,
    },

    // Fail the build on CI if you accidentally left test.only in the source code
    forbidOnly: !!process.env.CI,

    // Retry on CI only
    retries: process.env.CI ? 2 : 0,

    // Limit workers on CI
    workers: process.env.CI ? 1 : undefined,

    // Reporter
    reporter: [
        ['html', { outputFolder: 'tests/e2e-results/html' }],
        ['json', { outputFile: 'tests/e2e-results/results.json' }],
        ['list'],
    ],

    // Shared settings for all the projects below
    use: {
        // Base URL for navigation
        baseURL: process.env.WP_BASE_URL || 'http://sitio-prueba.local',

        // Collect trace when retrying the failed test
        trace: 'on-first-retry',

        // Screenshot on failure
        screenshot: 'only-on-failure',

        // Video on failure
        video: 'retain-on-failure',

        // Viewport
        viewport: { width: 1280, height: 720 },

        // Context options
        contextOptions: {
            ignoreHTTPSErrors: true,
        },

        // Authentication state
        storageState: process.env.STORAGE_STATE || undefined,
    },

    // Configure projects for major browsers
    projects: [
        // Setup project to authenticate
        {
            name: 'setup',
            testMatch: /.*\.setup\.js/,
        },

        // Desktop browsers
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                storageState: 'tests/e2e/.auth/user.json',
            },
            dependencies: ['setup'],
        },
        {
            name: 'firefox',
            use: {
                ...devices['Desktop Firefox'],
                storageState: 'tests/e2e/.auth/user.json',
            },
            dependencies: ['setup'],
        },
        {
            name: 'webkit',
            use: {
                ...devices['Desktop Safari'],
                storageState: 'tests/e2e/.auth/user.json',
            },
            dependencies: ['setup'],
        },

        // Mobile viewports
        {
            name: 'Mobile Chrome',
            use: {
                ...devices['Pixel 5'],
                storageState: 'tests/e2e/.auth/user.json',
            },
            dependencies: ['setup'],
        },
        {
            name: 'Mobile Safari',
            use: {
                ...devices['iPhone 12'],
                storageState: 'tests/e2e/.auth/user.json',
            },
            dependencies: ['setup'],
        },
    ],

    // Local dev server configuration
    webServer: process.env.CI
        ? undefined
        : {
              // Use existing local server
              command: 'echo "Using existing WordPress server"',
              url: process.env.WP_BASE_URL || 'http://sitio-prueba.local',
              reuseExistingServer: true,
          },

    // Output folder for test artifacts
    outputDir: 'tests/e2e-results',
});
