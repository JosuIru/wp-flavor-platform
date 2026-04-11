// @ts-check
const { test: setup, expect } = require('@playwright/test');
const path = require('path');
const fs = require('fs');

const authFile = path.join(__dirname, '.auth/user.json');

/**
 * Setup test to authenticate with WordPress admin.
 */
setup('authenticate', async ({ page, baseURL }) => {
    // Create .auth directory if it doesn't exist
    const authDir = path.dirname(authFile);
    if (!fs.existsSync(authDir)) {
        fs.mkdirSync(authDir, { recursive: true });
    }

    // Navigate to login page
    await page.goto(`${baseURL}/wp-login.php`);

    // Fill login form
    await page.locator('#user_login').fill(process.env.WP_ADMIN_USER || 'admin');
    await page.locator('#user_pass').fill(process.env.WP_ADMIN_PASS || 'admin');

    // Submit form
    await page.locator('#wp-submit').click();

    // Wait for redirect to admin dashboard
    await page.waitForURL(/\/wp-admin\//);

    // Verify we're logged in
    await expect(page.locator('#wpadminbar')).toBeVisible();

    // Save authentication state
    await page.context().storageState({ path: authFile });
});
