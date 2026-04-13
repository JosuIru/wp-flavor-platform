/**
 * Lighthouse CI Configuration for VBP
 * Configuracion de Lighthouse CI para medir rendimiento de paginas VBP
 *
 * @package Flavor_Platform
 * @since 2.3.0
 */

module.exports = {
    ci: {
        collect: {
            // URLs a testear - se pueden sobrescribir via CLI
            url: [
                'http://localhost/vbp-test-10/',
                'http://localhost/vbp-test-50/',
                'http://localhost/vbp-test-100/'
            ],

            // Numero de ejecuciones por URL
            numberOfRuns: 3,

            // Configuracion de Chrome
            settings: {
                preset: 'desktop',
                onlyCategories: ['performance'],
                throttlingMethod: 'devtools',
                // Sin throttling para desarrollo local
                throttling: {
                    cpuSlowdownMultiplier: 1,
                    rttMs: 0,
                    throughputKbps: 0
                }
            },

            // Configuracion de Puppeteer
            puppeteerScript: './tools/lighthouse-puppeteer-script.js',
            puppeteerLaunchOptions: {
                headless: true,
                args: [
                    '--no-sandbox',
                    '--disable-gpu',
                    '--disable-dev-shm-usage'
                ]
            }
        },

        assert: {
            // Configuracion de aserciones
            preset: 'lighthouse:recommended',

            assertions: {
                // Core Web Vitals
                'first-contentful-paint': ['error', { maxNumericValue: 2000 }],
                'largest-contentful-paint': ['error', { maxNumericValue: 2500 }],
                'cumulative-layout-shift': ['error', { maxNumericValue: 0.1 }],
                'total-blocking-time': ['error', { maxNumericValue: 300 }],
                'max-potential-fid': ['warn', { maxNumericValue: 100 }],

                // Performance Score
                'categories:performance': ['error', { minScore: 0.9 }],

                // Tamano de recursos
                'total-byte-weight': ['warn', { maxNumericValue: 500000 }], // 500KB
                'unminified-css': 'warn',
                'unminified-javascript': 'warn',
                'unused-css-rules': 'warn',
                'unused-javascript': 'warn',

                // Optimizaciones
                'render-blocking-resources': 'warn',
                'uses-responsive-images': 'warn',
                'uses-optimized-images': 'warn',
                'uses-webp-images': 'warn',
                'efficient-animated-content': 'warn',

                // DOM
                'dom-size': ['warn', { maxNumericValue: 1500 }],

                // JavaScript
                'bootup-time': ['warn', { maxNumericValue: 2000 }],
                'mainthread-work-breakdown': ['warn', { maxNumericValue: 3000 }],

                // Fuentes
                'font-display': 'warn',

                // Desactivar auditorias no aplicables
                'service-worker': 'off',
                'works-offline': 'off',
                'installable-manifest': 'off'
            }
        },

        upload: {
            // Guardar resultados localmente
            target: 'filesystem',
            outputDir: './lighthouse-results',

            // Formato de archivos
            reportFilenamePattern: '%%DATETIME%%-%%URL%%-report.%%EXTENSION%%'
        }
    }
};
