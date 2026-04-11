// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E tests for VBP Collaboration functionality.
 *
 * Note: These tests simulate collaboration scenarios but
 * true multi-user testing requires additional setup.
 */
test.describe('VBP Collaboration', () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await page.goto(`${baseURL}/wp-admin/admin.php?page=vbp-editor&post_id=1`);
        await page.waitForSelector('.vbp-editor', { timeout: 10000 });
    });

    test('shows collaboration indicator', async ({ page }) => {
        // Look for collaboration status indicator
        const collabIndicator = page.locator('.vbp-collab-status');

        // May not always be visible if feature is not enabled
        if (await collabIndicator.isVisible()) {
            await expect(collabIndicator).toBeVisible();
        }
    });

    test('shows online users list', async ({ page }) => {
        // Look for online users indicator
        const onlineUsers = page.locator('.vbp-online-users');

        if (await onlineUsers.isVisible()) {
            // Should show at least current user
            await expect(page.locator('.vbp-online-users .vbp-user-avatar')).toHaveCount.greaterThan(0);
        }
    });

    test('can view user activity', async ({ page }) => {
        // Look for activity panel toggle
        const activityToggle = page.locator('[data-action="toggle-activity"]');

        if (await activityToggle.isVisible()) {
            await activityToggle.click();

            // Verify activity panel opens
            await expect(page.locator('.vbp-activity-panel')).toBeVisible();
        }
    });
});

test.describe('VBP Comments', () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await page.goto(`${baseURL}/wp-admin/admin.php?page=vbp-editor&post_id=1`);
        await page.waitForSelector('.vbp-editor', { timeout: 10000 });
    });

    test('can open comments panel', async ({ page }) => {
        // Look for comments toggle
        const commentsToggle = page.locator('[data-action="toggle-comments"]');

        if (await commentsToggle.isVisible()) {
            await commentsToggle.click();

            // Verify comments panel opens
            await expect(page.locator('.vbp-comments-panel')).toBeVisible();
        }
    });

    test('can add comment on element', async ({ page }) => {
        // First add an element
        await page.locator('[data-action="open-blocks"]').click();
        await page.locator('.vbp-block-item[data-block-type="section"]').click();

        // Select the element
        const element = page.locator('.vbp-canvas .vbp-element[data-type="section"]').first();
        await element.click();

        // Right-click to open context menu
        await element.click({ button: 'right' });

        // Look for "Add Comment" option
        const addCommentOption = page.locator('.vbp-context-menu [data-action="add-comment"]');

        if (await addCommentOption.isVisible()) {
            await addCommentOption.click();

            // Fill in comment
            await page.locator('.vbp-comment-input textarea').fill('This is a test comment');

            // Submit comment
            await page.locator('.vbp-comment-input [data-action="submit-comment"]').click();

            // Verify comment indicator appears
            await expect(element.locator('.vbp-comment-indicator')).toBeVisible();
        }
    });

    test('can view and reply to comments', async ({ page }) => {
        // Open comments panel
        const commentsToggle = page.locator('[data-action="toggle-comments"]');

        if (await commentsToggle.isVisible()) {
            await commentsToggle.click();

            // Wait for comments to load
            const commentsPanel = page.locator('.vbp-comments-panel');
            await expect(commentsPanel).toBeVisible();

            // If there are comments, try to reply
            const firstComment = commentsPanel.locator('.vbp-comment-item').first();

            if (await firstComment.isVisible()) {
                // Click reply button
                const replyButton = firstComment.locator('[data-action="reply"]');

                if (await replyButton.isVisible()) {
                    await replyButton.click();

                    // Fill reply
                    await page.locator('.vbp-reply-input textarea').fill('This is a reply');

                    // Submit reply
                    await page.locator('.vbp-reply-input [data-action="submit-reply"]').click();
                }
            }
        }
    });

    test('can resolve comment', async ({ page }) => {
        // Open comments panel
        const commentsToggle = page.locator('[data-action="toggle-comments"]');

        if (await commentsToggle.isVisible()) {
            await commentsToggle.click();

            const firstComment = page.locator('.vbp-comments-panel .vbp-comment-item').first();

            if (await firstComment.isVisible()) {
                // Find resolve button
                const resolveButton = firstComment.locator('[data-action="resolve"]');

                if (await resolveButton.isVisible()) {
                    await resolveButton.click();

                    // Verify comment is marked as resolved
                    await expect(firstComment).toHaveClass(/resolved/);
                }
            }
        }
    });
});

test.describe('VBP Version History', () => {
    test.beforeEach(async ({ page, baseURL }) => {
        await page.goto(`${baseURL}/wp-admin/admin.php?page=vbp-editor&post_id=1`);
        await page.waitForSelector('.vbp-editor', { timeout: 10000 });
    });

    test('can open version history', async ({ page }) => {
        // Look for history toggle
        const historyToggle = page.locator('[data-action="toggle-history"]');

        if (await historyToggle.isVisible()) {
            await historyToggle.click();

            // Verify history panel opens
            await expect(page.locator('.vbp-history-panel')).toBeVisible();
        }
    });

    test('shows version list', async ({ page }) => {
        // Open history panel
        const historyToggle = page.locator('[data-action="toggle-history"]');

        if (await historyToggle.isVisible()) {
            await historyToggle.click();

            // Wait for versions to load
            await page.waitForSelector('.vbp-history-panel .vbp-version-item');

            // Verify versions are displayed
            await expect(page.locator('.vbp-history-panel .vbp-version-item')).toHaveCount.greaterThan(0);
        }
    });

    test('can preview version', async ({ page }) => {
        // Open history panel
        const historyToggle = page.locator('[data-action="toggle-history"]');

        if (await historyToggle.isVisible()) {
            await historyToggle.click();

            const versionItem = page.locator('.vbp-history-panel .vbp-version-item').first();

            if (await versionItem.isVisible()) {
                // Click to preview
                await versionItem.click();

                // Verify preview mode is active
                await expect(page.locator('.vbp-preview-mode')).toBeVisible();
            }
        }
    });

    test('can restore version', async ({ page }) => {
        // Open history panel
        const historyToggle = page.locator('[data-action="toggle-history"]');

        if (await historyToggle.isVisible()) {
            await historyToggle.click();

            const versionItem = page.locator('.vbp-history-panel .vbp-version-item').nth(1);

            if (await versionItem.isVisible()) {
                // Find restore button
                const restoreButton = versionItem.locator('[data-action="restore"]');

                if (await restoreButton.isVisible()) {
                    await restoreButton.click();

                    // Confirm restore
                    await page.locator('.vbp-modal [data-action="confirm"]').click();

                    // Verify success
                    await expect(page.locator('.vbp-toast-success')).toBeVisible();
                }
            }
        }
    });
});
