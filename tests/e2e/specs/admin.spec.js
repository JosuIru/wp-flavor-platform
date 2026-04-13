// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Tests E2E para el panel de administración de Flavor Platform
 */

test.describe('Flavor Platform Admin', () => {
    test.beforeEach(async ({ page }) => {
        // Login al admin de WordPress
        await page.goto('/wp-login.php');
        await page.fill('#user_login', process.env.WP_ADMIN_USER || 'admin');
        await page.fill('#user_pass', process.env.WP_ADMIN_PASS || 'admin');
        await page.click('#wp-submit');
        await page.waitForURL('**/wp-admin/**');
    });

    test('debe mostrar el menú de Flavor Platform', async ({ page }) => {
        await page.goto('/wp-admin/');

        // Verificar que existe el menú de Flavor Platform
        const flavorMenu = page.locator('#adminmenu').getByText('Flavor Platform');
        await expect(flavorMenu).toBeVisible();
    });

    test('debe cargar la página de configuración', async ({ page }) => {
        await page.goto('/wp-admin/admin.php?page=flavor-platform');

        // Verificar título de la página
        await expect(page.locator('h1')).toContainText('Flavor Platform');
    });

    test('debe mostrar los módulos disponibles', async ({ page }) => {
        await page.goto('/wp-admin/admin.php?page=flavor-platform-modules');

        // Verificar que se muestran módulos
        const modulesContainer = page.locator('.flavor-modules-grid, .modules-list');
        await expect(modulesContainer).toBeVisible();
    });

    test('debe poder activar/desactivar un módulo', async ({ page }) => {
        await page.goto('/wp-admin/admin.php?page=flavor-platform-modules');

        // Buscar un toggle de módulo
        const moduleToggle = page.locator('.module-toggle, .module-switch').first();

        if (await moduleToggle.isVisible()) {
            const initialState = await moduleToggle.isChecked();
            await moduleToggle.click();

            // Esperar a que se guarde
            await page.waitForTimeout(1000);

            // Verificar que cambió el estado
            const newState = await moduleToggle.isChecked();
            expect(newState).not.toBe(initialState);
        }
    });

    test('debe cargar el Visual Builder Pro', async ({ page }) => {
        await page.goto('/wp-admin/admin.php?page=flavor-vbp');

        // Verificar que se carga la interfaz de VBP
        await expect(page.locator('.vbp-container, .visual-builder-pro')).toBeVisible();
    });
});

test.describe('Visual Builder Pro Editor', () => {
    test.beforeEach(async ({ page }) => {
        // Login
        await page.goto('/wp-login.php');
        await page.fill('#user_login', process.env.WP_ADMIN_USER || 'admin');
        await page.fill('#user_pass', process.env.WP_ADMIN_PASS || 'admin');
        await page.click('#wp-submit');
        await page.waitForURL('**/wp-admin/**');
    });

    test('debe crear una nueva página con VBP', async ({ page }) => {
        // Ir a crear nueva página
        await page.goto('/wp-admin/post-new.php?post_type=page');

        // Buscar botón para abrir VBP
        const vbpButton = page.locator('[data-vbp-open], .open-vbp-editor');

        if (await vbpButton.isVisible()) {
            await vbpButton.click();

            // Verificar que se abre el editor
            await expect(page.locator('.vbp-editor, .vbp-canvas')).toBeVisible();
        }
    });

    test('debe mostrar la biblioteca de bloques', async ({ page }) => {
        await page.goto('/wp-admin/admin.php?page=flavor-vbp-editor&post_id=new');

        // Buscar el panel de bloques
        const blocksPanel = page.locator('.vbp-blocks-panel, .block-library');

        if (await blocksPanel.isVisible()) {
            // Verificar que hay bloques disponibles
            const blocks = page.locator('.vbp-block-item, .block-item');
            await expect(blocks.first()).toBeVisible();
        }
    });
});
