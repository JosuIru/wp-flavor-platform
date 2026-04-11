// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E tests for VBP Editor functionality.
 */
test.describe('VBP Editor', () => {
    const testPageId = 1; // Will be set during setup

    test.beforeEach(async ({ page, baseURL }) => {
        // Navigate to VBP editor
        await page.goto(`${baseURL}/wp-admin/admin.php?page=vbp-editor&post_id=${testPageId}`);

        // Wait for editor to load
        await page.waitForSelector('.vbp-editor', { timeout: 10000 });
    });

    test('loads editor correctly', async ({ page }) => {
        // Verify main editor components are visible
        await expect(page.locator('.vbp-canvas')).toBeVisible();
        await expect(page.locator('.vbp-toolbar')).toBeVisible();
        await expect(page.locator('.vbp-inspector')).toBeVisible();
    });

    test('displays block library', async ({ page }) => {
        // Open block library
        await page.locator('[data-action="open-blocks"]').click();

        // Verify block categories are visible
        await expect(page.locator('.vbp-block-library')).toBeVisible();
        await expect(page.locator('.vbp-block-category')).toHaveCount.greaterThan(0);
    });

    test('can add element to canvas', async ({ page }) => {
        // Open block library
        await page.locator('[data-action="open-blocks"]').click();

        // Wait for library to open
        await page.waitForSelector('.vbp-block-library');

        // Find and click a block (e.g., heading)
        const headingBlock = page.locator('.vbp-block-item[data-block-type="heading"]');
        await headingBlock.click();

        // Verify element was added to canvas
        await expect(page.locator('.vbp-canvas .vbp-element[data-type="heading"]')).toBeVisible();
    });

    test('can select and move element', async ({ page }) => {
        // First add an element
        await page.locator('[data-action="open-blocks"]').click();
        await page.locator('.vbp-block-item[data-block-type="container"]').click();

        // Wait for element to appear
        const element = page.locator('.vbp-canvas .vbp-element[data-type="container"]').first();
        await expect(element).toBeVisible();

        // Click to select
        await element.click();

        // Verify selection indicators appear
        await expect(page.locator('.vbp-selection-box')).toBeVisible();

        // Verify inspector shows element properties
        await expect(page.locator('.vbp-inspector .vbp-inspector-panel')).toBeVisible();
    });

    test('can save page', async ({ page }) => {
        // Make a change (add an element)
        await page.locator('[data-action="open-blocks"]').click();
        await page.locator('.vbp-block-item[data-block-type="text"]').click();

        // Wait for element to be added
        await page.waitForSelector('.vbp-canvas .vbp-element[data-type="text"]');

        // Click save button
        await page.locator('[data-action="save"]').click();

        // Wait for save confirmation
        await expect(page.locator('.vbp-toast-success, .vbp-save-indicator.saved')).toBeVisible({
            timeout: 5000,
        });
    });

    test('shows undo/redo buttons', async ({ page }) => {
        await expect(page.locator('[data-action="undo"]')).toBeVisible();
        await expect(page.locator('[data-action="redo"]')).toBeVisible();
    });

    test('can change viewport size', async ({ page }) => {
        // Find viewport controls
        const viewportControls = page.locator('.vbp-viewport-controls');
        await expect(viewportControls).toBeVisible();

        // Click tablet viewport
        await page.locator('[data-viewport="tablet"]').click();

        // Verify canvas resizes
        const canvas = page.locator('.vbp-canvas-container');
        await expect(canvas).toHaveAttribute('data-viewport', 'tablet');
    });

    test('keyboard shortcut opens command palette', async ({ page }) => {
        // Press Cmd/Ctrl + K
        await page.keyboard.press('Control+k');

        // Verify command palette opens
        await expect(page.locator('.vbp-command-palette')).toBeVisible();

        // Close with Escape
        await page.keyboard.press('Escape');
        await expect(page.locator('.vbp-command-palette')).not.toBeVisible();
    });
});

test.describe('VBP Editor - Element Manipulation', () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await page.goto(`${baseURL}/wp-admin/admin.php?page=vbp-editor&post_id=1`);
        await page.waitForSelector('.vbp-editor', { timeout: 10000 });
    });

    test('can duplicate element', async ({ page }) => {
        // Add element
        await page.locator('[data-action="open-blocks"]').click();
        await page.locator('.vbp-block-item[data-block-type="button"]').click();

        // Select element
        const element = page.locator('.vbp-canvas .vbp-element[data-type="button"]').first();
        await element.click();

        // Duplicate with keyboard shortcut
        await page.keyboard.press('Control+d');

        // Verify there are now two buttons
        await expect(page.locator('.vbp-canvas .vbp-element[data-type="button"]')).toHaveCount(2);
    });

    test('can delete element', async ({ page }) => {
        // Add element
        await page.locator('[data-action="open-blocks"]').click();
        await page.locator('.vbp-block-item[data-block-type="image"]').click();

        // Select element
        const element = page.locator('.vbp-canvas .vbp-element[data-type="image"]').first();
        await element.click();

        // Delete with keyboard
        await page.keyboard.press('Delete');

        // Verify element is removed
        await expect(page.locator('.vbp-canvas .vbp-element[data-type="image"]')).toHaveCount(0);
    });

    test('can change element properties', async ({ page }) => {
        // Add text element
        await page.locator('[data-action="open-blocks"]').click();
        await page.locator('.vbp-block-item[data-block-type="heading"]').click();

        // Select element
        const element = page.locator('.vbp-canvas .vbp-element[data-type="heading"]').first();
        await element.click();

        // Change text in inspector
        const textInput = page.locator('.vbp-inspector input[name="text"]');
        await textInput.fill('New Heading Text');

        // Verify canvas updates
        await expect(element).toContainText('New Heading Text');
    });
});

test.describe('VBP Editor - Layers Panel', () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await page.goto(`${baseURL}/wp-admin/admin.php?page=vbp-editor&post_id=1`);
        await page.waitForSelector('.vbp-editor', { timeout: 10000 });
    });

    test('shows layers panel', async ({ page }) => {
        // Open layers panel
        await page.locator('[data-action="toggle-layers"]').click();

        // Verify panel is visible
        await expect(page.locator('.vbp-layers-panel')).toBeVisible();
    });

    test('layers panel shows document structure', async ({ page }) => {
        // Add some elements
        await page.locator('[data-action="open-blocks"]').click();
        await page.locator('.vbp-block-item[data-block-type="section"]').click();

        // Open layers panel
        await page.locator('[data-action="toggle-layers"]').click();

        // Verify section appears in layers
        await expect(page.locator('.vbp-layers-panel .vbp-layer-item')).toHaveCount.greaterThan(0);
    });

    test('clicking layer selects element', async ({ page }) => {
        // Add element
        await page.locator('[data-action="open-blocks"]').click();
        await page.locator('.vbp-block-item[data-block-type="container"]').click();

        // Open layers panel
        await page.locator('[data-action="toggle-layers"]').click();

        // Click layer item
        const layerItem = page.locator('.vbp-layers-panel .vbp-layer-item').first();
        await layerItem.click();

        // Verify element is selected in canvas
        await expect(page.locator('.vbp-selection-box')).toBeVisible();
    });
});
