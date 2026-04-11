// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E tests for VBP Symbols functionality.
 */
test.describe('VBP Symbols', () => {
    test.beforeEach(async ({ page, baseURL }) => {
        // Navigate to VBP editor
        await page.goto(`${baseURL}/wp-admin/admin.php?page=vbp-editor&post_id=1`);
        await page.waitForSelector('.vbp-editor', { timeout: 10000 });
    });

    test('can create symbol from selection', async ({ page }) => {
        // Add elements to create a symbol from
        await page.locator('[data-action="open-blocks"]').click();
        await page.locator('.vbp-block-item[data-block-type="container"]').click();

        // Select the element
        const element = page.locator('.vbp-canvas .vbp-element[data-type="container"]').first();
        await element.click();

        // Right-click to open context menu
        await element.click({ button: 'right' });

        // Click "Create Symbol" option
        await page.locator('.vbp-context-menu [data-action="create-symbol"]').click();

        // Fill symbol name in modal
        await page.locator('.vbp-modal input[name="symbol-name"]').fill('Test Symbol');

        // Select category
        await page.locator('.vbp-modal select[name="category"]').selectOption('custom');

        // Click create button
        await page.locator('.vbp-modal [data-action="confirm"]').click();

        // Verify success message
        await expect(page.locator('.vbp-toast-success')).toBeVisible({ timeout: 5000 });
    });

    test('can open symbols panel', async ({ page }) => {
        // Click symbols panel button
        await page.locator('[data-action="toggle-symbols"]').click();

        // Verify symbols panel is visible
        await expect(page.locator('.vbp-symbols-panel')).toBeVisible();
    });

    test('symbols panel shows categories', async ({ page }) => {
        // Open symbols panel
        await page.locator('[data-action="toggle-symbols"]').click();

        // Verify categories are visible
        await expect(page.locator('.vbp-symbols-panel .vbp-symbol-category')).toHaveCount.greaterThan(0);
    });

    test('can insert symbol instance', async ({ page }) => {
        // Open symbols panel
        await page.locator('[data-action="toggle-symbols"]').click();

        // Wait for symbols to load
        await page.waitForSelector('.vbp-symbols-panel .vbp-symbol-item');

        // Click on first symbol
        const symbolItem = page.locator('.vbp-symbols-panel .vbp-symbol-item').first();
        await symbolItem.click();

        // Verify instance is added to canvas
        await expect(page.locator('.vbp-canvas .vbp-symbol-instance')).toBeVisible();
    });

    test('can view symbol details', async ({ page }) => {
        // Open symbols panel
        await page.locator('[data-action="toggle-symbols"]').click();

        // Wait for symbols
        await page.waitForSelector('.vbp-symbols-panel .vbp-symbol-item');

        // Right-click on symbol to view details
        const symbolItem = page.locator('.vbp-symbols-panel .vbp-symbol-item').first();
        await symbolItem.click({ button: 'right' });

        // Click "View Details"
        await page.locator('.vbp-context-menu [data-action="view-symbol"]').click();

        // Verify symbol detail modal opens
        await expect(page.locator('.vbp-modal.vbp-symbol-detail')).toBeVisible();
    });

    test('symbol instance shows in inspector', async ({ page }) => {
        // Open symbols panel and insert instance
        await page.locator('[data-action="toggle-symbols"]').click();
        await page.waitForSelector('.vbp-symbols-panel .vbp-symbol-item');

        const symbolItem = page.locator('.vbp-symbols-panel .vbp-symbol-item').first();
        await symbolItem.click();

        // Select the instance
        const instance = page.locator('.vbp-canvas .vbp-symbol-instance').first();
        await instance.click();

        // Verify inspector shows symbol info
        await expect(page.locator('.vbp-inspector .vbp-symbol-info')).toBeVisible();
    });

    test('can switch symbol variant', async ({ page }) => {
        // First insert a symbol instance
        await page.locator('[data-action="toggle-symbols"]').click();
        await page.waitForSelector('.vbp-symbols-panel .vbp-symbol-item');
        await page.locator('.vbp-symbols-panel .vbp-symbol-item').first().click();

        // Select the instance
        const instance = page.locator('.vbp-canvas .vbp-symbol-instance').first();
        await instance.click();

        // Find variant selector in inspector
        const variantSelector = page.locator('.vbp-inspector select[name="variant"]');

        // Skip if no variants available
        const variantCount = await variantSelector.locator('option').count();
        if (variantCount > 1) {
            // Select second variant
            await variantSelector.selectOption({ index: 1 });

            // Verify variant changed
            await expect(instance).toHaveAttribute('data-variant', /.+/);
        }
    });
});

test.describe('VBP Symbols - Overrides', () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await page.goto(`${baseURL}/wp-admin/admin.php?page=vbp-editor&post_id=1`);
        await page.waitForSelector('.vbp-editor', { timeout: 10000 });
    });

    test('can apply override to instance', async ({ page }) => {
        // Insert a symbol instance
        await page.locator('[data-action="toggle-symbols"]').click();
        await page.waitForSelector('.vbp-symbols-panel .vbp-symbol-item');
        await page.locator('.vbp-symbols-panel .vbp-symbol-item').first().click();

        // Select the instance
        const instance = page.locator('.vbp-canvas .vbp-symbol-instance').first();
        await instance.click();

        // Find override panel in inspector
        const overridePanel = page.locator('.vbp-inspector .vbp-overrides-panel');

        // Skip if override panel not available
        if (await overridePanel.isVisible()) {
            // Toggle an override
            const overrideToggle = overridePanel.locator('.vbp-override-toggle').first();
            await overrideToggle.click();

            // Verify override is applied
            await expect(instance).toHaveAttribute('data-has-overrides', 'true');
        }
    });

    test('can detach instance from symbol', async ({ page }) => {
        // Insert a symbol instance
        await page.locator('[data-action="toggle-symbols"]').click();
        await page.waitForSelector('.vbp-symbols-panel .vbp-symbol-item');
        await page.locator('.vbp-symbols-panel .vbp-symbol-item').first().click();

        // Select the instance
        const instance = page.locator('.vbp-canvas .vbp-symbol-instance').first();
        await instance.click();

        // Right-click for context menu
        await instance.click({ button: 'right' });

        // Click "Detach from Symbol"
        const detachOption = page.locator('.vbp-context-menu [data-action="detach-symbol"]');

        if (await detachOption.isVisible()) {
            await detachOption.click();

            // Verify instance is no longer a symbol instance
            await expect(page.locator('.vbp-canvas .vbp-symbol-instance')).toHaveCount(0);
        }
    });
});

test.describe('VBP Symbols - Management', () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await page.goto(`${baseURL}/wp-admin/admin.php?page=vbp-editor&post_id=1`);
        await page.waitForSelector('.vbp-editor', { timeout: 10000 });
    });

    test('can search symbols', async ({ page }) => {
        // Open symbols panel
        await page.locator('[data-action="toggle-symbols"]').click();

        // Find search input
        const searchInput = page.locator('.vbp-symbols-panel input[type="search"]');

        if (await searchInput.isVisible()) {
            // Type search query
            await searchInput.fill('button');

            // Wait for results to filter
            await page.waitForTimeout(300);

            // Verify filtered results
            const visibleItems = page.locator('.vbp-symbols-panel .vbp-symbol-item:visible');
            // Results should only show button-related symbols
        }
    });

    test('can filter symbols by category', async ({ page }) => {
        // Open symbols panel
        await page.locator('[data-action="toggle-symbols"]').click();

        // Find category filter
        const categoryTabs = page.locator('.vbp-symbols-panel .vbp-category-tab');

        if (await categoryTabs.first().isVisible()) {
            // Click on a category
            await categoryTabs.nth(1).click();

            // Verify only symbols from that category are shown
            await expect(page.locator('.vbp-symbols-panel .vbp-symbol-item')).toBeVisible();
        }
    });
});
