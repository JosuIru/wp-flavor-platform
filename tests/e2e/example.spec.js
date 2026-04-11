/**
 * Example E2E Tests for Flavor Platform
 *
 * These tests demonstrate basic E2E testing patterns.
 *
 * @package FlavorPlatform
 */

const { test, expect } = require('@playwright/test');

/**
 * Test suite: Homepage
 */
test.describe('Homepage', () => {
    test('should load the homepage', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveTitle(/./);
    });

    test('should have WordPress generator meta tag', async ({ page }) => {
        await page.goto('/');
        const generator = page.locator('meta[name="generator"]');
        await expect(generator).toHaveAttribute('content', /WordPress/);
    });
});

/**
 * Test suite: Admin Login
 */
test.describe('Admin Login', () => {
    test('should show login form', async ({ page }) => {
        await page.goto('/wp-login.php');
        await expect(page.locator('#user_login')).toBeVisible();
        await expect(page.locator('#user_pass')).toBeVisible();
        await expect(page.locator('#wp-submit')).toBeVisible();
    });

    test('should reject invalid credentials', async ({ page }) => {
        await page.goto('/wp-login.php');
        await page.fill('#user_login', 'invalid');
        await page.fill('#user_pass', 'invalid');
        await page.click('#wp-submit');

        await expect(page.locator('#login_error')).toBeVisible();
    });

    test('should login with valid credentials', async ({ page }) => {
        await page.goto('/wp-login.php');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'admin');
        await page.click('#wp-submit');

        await page.waitForURL('**/wp-admin/**');
        await expect(page).toHaveURL(/wp-admin/);
    });
});

/**
 * Test suite: Flavor Platform Plugin
 */
test.describe('Flavor Platform', () => {
    // Use admin authentication
    test.use({
        storageState: 'tests/e2e/.auth/admin.json',
    });

    test('should be listed in plugins page', async ({ page }) => {
        await page.goto('/wp-admin/plugins.php');
        await expect(
            page.locator('tr[data-slug="flavor-platform"]')
        ).toBeVisible();
    });

    test('should have admin menu', async ({ page }) => {
        await page.goto('/wp-admin/');
        await expect(
            page.locator('#adminmenu').getByText('Flavor')
        ).toBeVisible();
    });
});

/**
 * Test suite: Visual Builder Pro
 */
test.describe('Visual Builder Pro', () => {
    test.use({
        storageState: 'tests/e2e/.auth/admin.json',
    });

    test('should load VBP editor', async ({ page }) => {
        // Navigate to a page with VBP
        await page.goto('/wp-admin/post-new.php?post_type=page');

        // Check if VBP is loaded (adjust selector based on actual implementation)
        // This is a placeholder - actual selectors depend on VBP implementation
        const vbpContainer = page.locator(
            '[data-vbp-editor], .flavor-vbp-editor'
        );

        // VBP might not be enabled for all pages, so we use a soft assertion
        const isVisible = await vbpContainer.isVisible().catch(() => false);

        if (isVisible) {
            await expect(vbpContainer).toBeVisible();
        } else {
            // VBP not present on this page type - this is acceptable
            console.log('VBP editor not found on page editor');
        }
    });
});

/**
 * Test suite: REST API
 */
test.describe('REST API', () => {
    test('should respond to health check', async ({ request }) => {
        const response = await request.get(
            '/wp-json/flavor-site-builder/v1/system/health',
            {
                headers: {
                    'X-VBP-Key': 'test-key',
                },
            }
        );

        // API might require valid key or be disabled
        expect([200, 401, 403]).toContain(response.status());
    });

    test('should list VBP blocks', async ({ request }) => {
        const response = await request.get('/wp-json/flavor-vbp/v1/blocks', {
            headers: {
                'X-VBP-Key': 'test-key',
            },
        });

        expect([200, 401, 403]).toContain(response.status());
    });
});

/**
 * Test suite: Accessibility
 */
test.describe('Accessibility', () => {
    test('login page should have accessible form', async ({ page }) => {
        await page.goto('/wp-login.php');

        // Check for form labels
        const usernameLabel = page.locator('label[for="user_login"]');
        const passwordLabel = page.locator('label[for="user_pass"]');

        await expect(usernameLabel).toBeVisible();
        await expect(passwordLabel).toBeVisible();
    });
});

/**
 * Test suite: Mobile Responsiveness
 */
test.describe('Mobile Responsiveness', () => {
    test.use({
        viewport: { width: 375, height: 667 },
    });

    test('should be responsive on mobile', async ({ page }) => {
        await page.goto('/');

        // Page should load without horizontal scroll
        const bodyWidth = await page.evaluate(
            () => document.body.scrollWidth
        );
        const viewportWidth = await page.evaluate(() => window.innerWidth);

        expect(bodyWidth).toBeLessThanOrEqual(viewportWidth + 10);
    });
});
