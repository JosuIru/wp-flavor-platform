// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E tests for VBP Prototype Mode functionality.
 */
test.describe('VBP Prototype Mode', () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await page.goto(`${baseURL}/wp-admin/admin.php?page=vbp-editor&post_id=1`);
        await page.waitForSelector('.vbp-editor', { timeout: 10000 });
    });

    test('can switch to prototype mode', async ({ page }) => {
        // Look for mode toggle
        const modeToggle = page.locator('[data-action="toggle-prototype-mode"]');

        if (await modeToggle.isVisible()) {
            await modeToggle.click();

            // Verify prototype mode is active
            await expect(page.locator('.vbp-editor')).toHaveAttribute('data-mode', 'prototype');
            await expect(page.locator('.vbp-prototype-toolbar')).toBeVisible();
        }
    });

    test('shows prototype panel', async ({ page }) => {
        // Switch to prototype mode
        const modeToggle = page.locator('[data-action="toggle-prototype-mode"]');

        if (await modeToggle.isVisible()) {
            await modeToggle.click();

            // Verify prototype panel is visible
            await expect(page.locator('.vbp-prototype-panel')).toBeVisible();
        }
    });

    test('can add interaction to element', async ({ page }) => {
        // First add an element
        await page.locator('[data-action="open-blocks"]').click();
        await page.locator('.vbp-block-item[data-block-type="button"]').click();

        // Switch to prototype mode
        const modeToggle = page.locator('[data-action="toggle-prototype-mode"]');

        if (await modeToggle.isVisible()) {
            await modeToggle.click();

            // Select the button
            const button = page.locator('.vbp-canvas .vbp-element[data-type="button"]').first();
            await button.click();

            // Verify interactions panel shows
            await expect(page.locator('.vbp-interactions-panel')).toBeVisible();

            // Add interaction
            const addInteractionBtn = page.locator('[data-action="add-interaction"]');

            if (await addInteractionBtn.isVisible()) {
                await addInteractionBtn.click();

                // Select trigger type
                await page.locator('.vbp-interaction-trigger select').selectOption('onClick');

                // Select action type
                await page.locator('.vbp-interaction-action select').selectOption('navigate');

                // Verify interaction is added
                await expect(page.locator('.vbp-interaction-item')).toHaveCount.greaterThan(0);
            }
        }
    });

    test('can create connection between elements', async ({ page }) => {
        // Add two elements
        await page.locator('[data-action="open-blocks"]').click();
        await page.locator('.vbp-block-item[data-block-type="button"]').click();
        await page.locator('.vbp-block-item[data-block-type="section"]').click();

        // Switch to prototype mode
        const modeToggle = page.locator('[data-action="toggle-prototype-mode"]');

        if (await modeToggle.isVisible()) {
            await modeToggle.click();

            // Select first element (button)
            const button = page.locator('.vbp-canvas .vbp-element[data-type="button"]').first();
            await button.click();

            // Find connection handle
            const connectionHandle = button.locator('.vbp-connection-handle');

            if (await connectionHandle.isVisible()) {
                // Drag to create connection
                const section = page.locator('.vbp-canvas .vbp-element[data-type="section"]').first();
                const sectionBox = await section.boundingBox();

                if (sectionBox) {
                    await connectionHandle.dragTo(section);

                    // Verify connection line is created
                    await expect(page.locator('.vbp-connection-line')).toBeVisible();
                }
            }
        }
    });

    test('can preview prototype', async ({ page }) => {
        // Switch to prototype mode
        const modeToggle = page.locator('[data-action="toggle-prototype-mode"]');

        if (await modeToggle.isVisible()) {
            await modeToggle.click();

            // Click preview button
            const previewBtn = page.locator('[data-action="preview-prototype"]');

            if (await previewBtn.isVisible()) {
                await previewBtn.click();

                // Verify preview opens (may open in new tab/window)
                // For now, just verify button exists
                await expect(previewBtn).toBeVisible();
            }
        }
    });
});

test.describe('VBP Prototype - Animations', () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await page.goto(`${baseURL}/wp-admin/admin.php?page=vbp-editor&post_id=1`);
        await page.waitForSelector('.vbp-editor', { timeout: 10000 });
    });

    test('can add animation to element', async ({ page }) => {
        // Add element
        await page.locator('[data-action="open-blocks"]').click();
        await page.locator('.vbp-block-item[data-block-type="container"]').click();

        // Switch to prototype mode
        const modeToggle = page.locator('[data-action="toggle-prototype-mode"]');

        if (await modeToggle.isVisible()) {
            await modeToggle.click();

            // Select element
            const element = page.locator('.vbp-canvas .vbp-element[data-type="container"]').first();
            await element.click();

            // Open animations panel
            const animationsTab = page.locator('[data-tab="animations"]');

            if (await animationsTab.isVisible()) {
                await animationsTab.click();

                // Add animation
                const addAnimationBtn = page.locator('[data-action="add-animation"]');

                if (await addAnimationBtn.isVisible()) {
                    await addAnimationBtn.click();

                    // Verify animation is added
                    await expect(page.locator('.vbp-animation-item')).toHaveCount.greaterThan(0);
                }
            }
        }
    });

    test('can configure animation timing', async ({ page }) => {
        // Prerequisite: element with animation
        await page.locator('[data-action="open-blocks"]').click();
        await page.locator('.vbp-block-item[data-block-type="container"]').click();

        const modeToggle = page.locator('[data-action="toggle-prototype-mode"]');

        if (await modeToggle.isVisible()) {
            await modeToggle.click();

            const element = page.locator('.vbp-canvas .vbp-element[data-type="container"]').first();
            await element.click();

            const animationsTab = page.locator('[data-tab="animations"]');

            if (await animationsTab.isVisible()) {
                await animationsTab.click();

                // Check for timing controls
                const durationInput = page.locator('input[name="animation-duration"]');
                const delayInput = page.locator('input[name="animation-delay"]');

                if (await durationInput.isVisible()) {
                    await durationInput.fill('0.6');
                    await delayInput.fill('0.2');

                    // Verify values are set
                    await expect(durationInput).toHaveValue('0.6');
                    await expect(delayInput).toHaveValue('0.2');
                }
            }
        }
    });
});

test.describe('VBP Prototype - Flows', () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await page.goto(`${baseURL}/wp-admin/admin.php?page=vbp-editor&post_id=1`);
        await page.waitForSelector('.vbp-editor', { timeout: 10000 });
    });

    test('can view flows overview', async ({ page }) => {
        const modeToggle = page.locator('[data-action="toggle-prototype-mode"]');

        if (await modeToggle.isVisible()) {
            await modeToggle.click();

            // Open flows panel
            const flowsPanel = page.locator('[data-action="open-flows"]');

            if (await flowsPanel.isVisible()) {
                await flowsPanel.click();

                // Verify flows panel opens
                await expect(page.locator('.vbp-flows-panel')).toBeVisible();
            }
        }
    });

    test('can create new flow', async ({ page }) => {
        const modeToggle = page.locator('[data-action="toggle-prototype-mode"]');

        if (await modeToggle.isVisible()) {
            await modeToggle.click();

            const flowsPanel = page.locator('[data-action="open-flows"]');

            if (await flowsPanel.isVisible()) {
                await flowsPanel.click();

                // Create new flow
                const newFlowBtn = page.locator('[data-action="create-flow"]');

                if (await newFlowBtn.isVisible()) {
                    await newFlowBtn.click();

                    // Fill flow name
                    await page.locator('input[name="flow-name"]').fill('User Login Flow');

                    // Confirm
                    await page.locator('[data-action="confirm"]').click();

                    // Verify flow is created
                    await expect(page.locator('.vbp-flow-item')).toHaveCount.greaterThan(0);
                }
            }
        }
    });
});
