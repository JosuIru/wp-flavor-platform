/**
 * Lighthouse Puppeteer Script
 * Script personalizado para preparar paginas antes de medir con Lighthouse
 *
 * @package Flavor_Platform
 * @since 2.3.0
 */

'use strict';

/**
 * Script que se ejecuta antes de cada medicion de Lighthouse
 *
 * @param {import('puppeteer').Browser} browser - Instancia del browser
 * @param {Object} context - Contexto de Lighthouse CI
 */
module.exports = async (browser, context) => {
    const page = await browser.newPage();

    // Configurar viewport
    await page.setViewport({
        width: 1350,
        height: 940,
        deviceScaleFactor: 1
    });

    // Configurar user agent
    await page.setUserAgent(
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 VBPPerformanceTest/1.0'
    );

    // Interceptar requests para medir
    await page.setRequestInterception(true);

    const resourceStats = {
        css: 0,
        js: 0,
        images: 0,
        other: 0,
        total: 0
    };

    page.on('request', (request) => {
        const resourceType = request.resourceType();

        switch (resourceType) {
            case 'stylesheet':
                resourceStats.css++;
                break;
            case 'script':
                resourceStats.js++;
                break;
            case 'image':
                resourceStats.images++;
                break;
            default:
                resourceStats.other++;
        }
        resourceStats.total++;

        request.continue();
    });

    // Navegar a la URL
    console.log(`[VBP Lighthouse] Navegando a: ${context.url}`);

    try {
        await page.goto(context.url, {
            waitUntil: 'networkidle0',
            timeout: 30000
        });

        // Esperar a que el contenido VBP se cargue
        await page.waitForSelector('.vbp-content, .flavor-content, [data-vbp-page]', {
            timeout: 10000
        }).catch(() => {
            console.log('[VBP Lighthouse] No se encontro contenido VBP especifico');
        });

        // Esperar un poco mas para que se estabilice
        await page.waitForTimeout(2000);

        // Recoger metricas del DOM
        const domMetrics = await page.evaluate(() => {
            return {
                domSize: document.querySelectorAll('*').length,
                images: document.querySelectorAll('img').length,
                scripts: document.querySelectorAll('script').length,
                stylesheets: document.querySelectorAll('link[rel="stylesheet"]').length,
                vbpElements: document.querySelectorAll('[data-vbp-element]').length
            };
        });

        console.log('[VBP Lighthouse] Metricas del DOM:', domMetrics);
        console.log('[VBP Lighthouse] Recursos cargados:', resourceStats);

        // Scrollear para cargar lazy content
        await autoScroll(page);

        // Esperar a que terminen las animaciones
        await page.waitForTimeout(1000);

    } catch (error) {
        console.error('[VBP Lighthouse] Error durante la carga:', error.message);
    }

    // Cerrar la pagina (Lighthouse abrira una nueva)
    await page.close();
};

/**
 * Auto scroll para cargar contenido lazy
 */
async function autoScroll(page) {
    await page.evaluate(async () => {
        await new Promise((resolve) => {
            let totalHeight = 0;
            const distance = 300;
            const delay = 100;

            const timer = setInterval(() => {
                const scrollHeight = document.body.scrollHeight;
                window.scrollBy(0, distance);
                totalHeight += distance;

                if (totalHeight >= scrollHeight) {
                    clearInterval(timer);
                    window.scrollTo(0, 0); // Volver arriba
                    resolve();
                }
            }, delay);
        });
    });
}
