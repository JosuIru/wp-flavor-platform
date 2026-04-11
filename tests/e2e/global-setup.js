/**
 * Playwright Global Setup
 *
 * This file runs once before all tests.
 * Used to set up test environment, authenticate, etc.
 *
 * @package FlavorPlatform
 */

const { chromium } = require('@playwright/test');

/**
 * Global setup function
 *
 * @param {Object} config - Playwright config
 */
async function globalSetup(config) {
    const baseURL = config.projects[0].use.baseURL || 'http://localhost:8080';

    console.log('\n=== Global Setup ===');
    console.log(`Base URL: ${baseURL}`);

    // Wait for WordPress to be ready
    console.log('Waiting for WordPress to be ready...');

    const maxRetries = 30;
    let retries = 0;
    let wpReady = false;

    while (retries < maxRetries && !wpReady) {
        try {
            const response = await fetch(baseURL, { method: 'HEAD' });
            if (response.ok) {
                wpReady = true;
                console.log('WordPress is ready!');
            }
        } catch (error) {
            retries++;
            console.log(`Waiting for WordPress... (${retries}/${maxRetries})`);
            await new Promise((resolve) => setTimeout(resolve, 2000));
        }
    }

    if (!wpReady) {
        throw new Error('WordPress did not become ready in time');
    }

    // Create authenticated state for admin user
    console.log('Creating authenticated state...');

    const browser = await chromium.launch();
    const adminContext = await browser.newContext();
    const adminPage = await adminContext.newPage();

    try {
        // Login as admin
        await adminPage.goto(`${baseURL}/wp-login.php`);
        await adminPage.fill('#user_login', 'admin');
        await adminPage.fill('#user_pass', 'admin');
        await adminPage.click('#wp-submit');

        // Wait for dashboard
        await adminPage.waitForURL('**/wp-admin/**', { timeout: 10000 });
        console.log('Admin login successful');

        // Save authentication state
        await adminContext.storageState({
            path: 'tests/e2e/.auth/admin.json',
        });
        console.log('Admin auth state saved');
    } catch (error) {
        console.error('Admin login failed:', error.message);
        // Continue without auth state - tests will handle login
    }

    await browser.close();

    console.log('=== Global Setup Complete ===\n');
}

module.exports = globalSetup;
