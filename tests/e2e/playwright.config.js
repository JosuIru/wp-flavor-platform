// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Configuración de Playwright para tests E2E de Flavor Platform
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
    testDir: './specs',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: [
        ['html', { outputFolder: '../coverage/e2e-report' }],
        ['list'],
    ],
    use: {
        baseURL: process.env.WP_URL || 'http://sitio-prueba.local',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] },
        },
        {
            name: 'webkit',
            use: { ...devices['Desktop Safari'] },
        },
        {
            name: 'mobile-chrome',
            use: { ...devices['Pixel 5'] },
        },
        {
            name: 'mobile-safari',
            use: { ...devices['iPhone 12'] },
        },
    ],

    /* Configuración del servidor de desarrollo local */
    // webServer: {
    //     command: 'npm run start',
    //     url: 'http://sitio-prueba.local',
    //     reuseExistingServer: !process.env.CI,
    // },
});
