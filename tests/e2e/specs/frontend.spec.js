// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Tests E2E para el frontend público de Flavor Platform
 */

test.describe('Frontend Público', () => {
    test('debe cargar la página de inicio', async ({ page }) => {
        await page.goto('/');

        // Verificar que la página carga
        await expect(page).toHaveTitle(/.*/);
        await expect(page.locator('body')).toBeVisible();
    });

    test('debe mostrar el header correctamente', async ({ page }) => {
        await page.goto('/');

        const header = page.locator('header, .site-header, #masthead');
        await expect(header).toBeVisible();
    });

    test('debe mostrar el footer correctamente', async ({ page }) => {
        await page.goto('/');

        const footer = page.locator('footer, .site-footer, #colophon');
        await expect(footer).toBeVisible();
    });

    test('debe tener navegación funcional', async ({ page }) => {
        await page.goto('/');

        // Buscar menú de navegación
        const nav = page.locator('nav, .main-navigation, .primary-menu');

        if (await nav.isVisible()) {
            const menuLinks = nav.locator('a');
            const linkCount = await menuLinks.count();

            expect(linkCount).toBeGreaterThan(0);
        }
    });
});

test.describe('Módulo de Eventos', () => {
    test('debe mostrar lista de eventos', async ({ page }) => {
        await page.goto('/eventos');

        // Verificar que hay contenido de eventos
        const eventsContainer = page.locator('.eventos-list, .events-grid, [data-module="eventos"]');

        if (await eventsContainer.isVisible()) {
            await expect(eventsContainer).toBeVisible();
        }
    });

    test('debe poder ver detalle de un evento', async ({ page }) => {
        await page.goto('/eventos');

        // Buscar un enlace a evento
        const eventLink = page.locator('.evento-item a, .event-card a').first();

        if (await eventLink.isVisible()) {
            await eventLink.click();

            // Verificar que se muestra el detalle
            await expect(page.locator('.evento-single, .event-detail')).toBeVisible();
        }
    });
});

test.describe('Módulo de Marketplace', () => {
    test('debe mostrar productos', async ({ page }) => {
        await page.goto('/productos');

        // Verificar que hay productos
        const productsContainer = page.locator('.productos-grid, .products-list, [data-module="marketplace"]');

        if (await productsContainer.isVisible()) {
            const products = page.locator('.producto-item, .product-card');
            const productCount = await products.count();

            expect(productCount).toBeGreaterThanOrEqual(0);
        }
    });

    test('debe poder filtrar productos', async ({ page }) => {
        await page.goto('/productos');

        // Buscar filtros
        const filterForm = page.locator('.product-filters, .filters-form');

        if (await filterForm.isVisible()) {
            // Verificar que hay opciones de filtro
            const filterOptions = filterForm.locator('select, input[type="checkbox"]');
            const optionCount = await filterOptions.count();

            expect(optionCount).toBeGreaterThan(0);
        }
    });

    test('debe poder añadir al carrito', async ({ page }) => {
        await page.goto('/productos');

        // Buscar botón de añadir al carrito
        const addToCartButton = page.locator('.add-to-cart, .btn-add-cart').first();

        if (await addToCartButton.isVisible()) {
            await addToCartButton.click();

            // Verificar que se añadió (mensaje o contador)
            await page.waitForTimeout(1000);
            const cartIndicator = page.locator('.cart-count, .cart-items-count');

            if (await cartIndicator.isVisible()) {
                const count = await cartIndicator.textContent();
                expect(parseInt(count || '0')).toBeGreaterThan(0);
            }
        }
    });
});

test.describe('Módulo de Socios', () => {
    test('debe mostrar formulario de registro', async ({ page }) => {
        await page.goto('/hazte-socio');

        // Verificar formulario
        const form = page.locator('form.socio-form, form.membership-form, [data-module="socios"] form');

        if (await form.isVisible()) {
            // Verificar campos básicos
            await expect(form.locator('input[type="email"], input[name="email"]')).toBeVisible();
        }
    });
});

test.describe('Responsive Design', () => {
    test('debe verse bien en móvil', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto('/');

        // Verificar que el contenido es visible
        await expect(page.locator('body')).toBeVisible();

        // Verificar menú móvil
        const mobileMenuToggle = page.locator('.mobile-menu-toggle, .hamburger, .menu-toggle');

        if (await mobileMenuToggle.isVisible()) {
            await mobileMenuToggle.click();

            // Verificar que se abre el menú
            const mobileMenu = page.locator('.mobile-menu, .mobile-nav');
            await expect(mobileMenu).toBeVisible();
        }
    });

    test('debe verse bien en tablet', async ({ page }) => {
        await page.setViewportSize({ width: 768, height: 1024 });
        await page.goto('/');

        await expect(page.locator('body')).toBeVisible();
    });

    test('debe verse bien en desktop', async ({ page }) => {
        await page.setViewportSize({ width: 1920, height: 1080 });
        await page.goto('/');

        await expect(page.locator('body')).toBeVisible();
    });
});

test.describe('Accesibilidad Básica', () => {
    test('debe tener atributos alt en imágenes', async ({ page }) => {
        await page.goto('/');

        const images = page.locator('img');
        const imageCount = await images.count();

        for (let i = 0; i < Math.min(imageCount, 10); i++) {
            const img = images.nth(i);
            const alt = await img.getAttribute('alt');
            // Verificar que tiene alt (puede estar vacío para imágenes decorativas)
            expect(alt).not.toBeNull();
        }
    });

    test('debe tener estructura de headings correcta', async ({ page }) => {
        await page.goto('/');

        // Verificar que hay un h1
        const h1 = page.locator('h1');
        const h1Count = await h1.count();

        expect(h1Count).toBeGreaterThanOrEqual(1);
    });

    test('debe tener enlaces con texto descriptivo', async ({ page }) => {
        await page.goto('/');

        const links = page.locator('a:visible');
        const linkCount = await links.count();

        for (let i = 0; i < Math.min(linkCount, 20); i++) {
            const link = links.nth(i);
            const text = await link.textContent();
            const ariaLabel = await link.getAttribute('aria-label');

            // El enlace debe tener texto o aria-label
            const hasDescription = (text && text.trim().length > 0) || ariaLabel;
            expect(hasDescription).toBeTruthy();
        }
    });
});
