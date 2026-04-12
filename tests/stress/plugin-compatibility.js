/**
 * VBP Plugin Compatibility Tests
 * Tests de compatibilidad con plugins populares de WordPress
 *
 * @package FlavorPlatform
 * @since 3.4.0
 */

/**
 * Lista de plugins populares a testear
 * Incluye plugins que podrian causar conflictos con VBP
 */
const POPULAR_PLUGINS = [
    // E-commerce
    {
        slug: 'woocommerce',
        name: 'WooCommerce',
        category: 'ecommerce',
        potentialConflicts: ['post-types', 'rest-api', 'scripts']
    },

    // SEO
    {
        slug: 'yoast-seo',
        name: 'Yoast SEO',
        category: 'seo',
        potentialConflicts: ['meta-boxes', 'post-edit']
    },
    {
        slug: 'all-in-one-seo-pack',
        name: 'All in One SEO',
        category: 'seo',
        potentialConflicts: ['meta-boxes', 'post-edit']
    },
    {
        slug: 'rank-math',
        name: 'Rank Math',
        category: 'seo',
        potentialConflicts: ['meta-boxes', 'post-edit', 'rest-api']
    },

    // Forms
    {
        slug: 'contact-form-7',
        name: 'Contact Form 7',
        category: 'forms',
        potentialConflicts: ['shortcodes', 'scripts']
    },
    {
        slug: 'wpforms-lite',
        name: 'WPForms Lite',
        category: 'forms',
        potentialConflicts: ['shortcodes', 'scripts', 'gutenberg']
    },
    {
        slug: 'forminator',
        name: 'Forminator',
        category: 'forms',
        potentialConflicts: ['shortcodes', 'scripts']
    },

    // Security
    {
        slug: 'wordfence',
        name: 'Wordfence',
        category: 'security',
        potentialConflicts: ['rest-api', 'admin-ajax', 'cron']
    },
    {
        slug: 'sucuri-scanner',
        name: 'Sucuri Security',
        category: 'security',
        potentialConflicts: ['rest-api', 'file-access']
    },
    {
        slug: 'better-wp-security',
        name: 'iThemes Security',
        category: 'security',
        potentialConflicts: ['rest-api', 'admin-access']
    },

    // Cache
    {
        slug: 'w3-total-cache',
        name: 'W3 Total Cache',
        category: 'cache',
        potentialConflicts: ['caching', 'scripts', 'styles']
    },
    {
        slug: 'wp-super-cache',
        name: 'WP Super Cache',
        category: 'cache',
        potentialConflicts: ['caching', 'dynamic-content']
    },
    {
        slug: 'litespeed-cache',
        name: 'LiteSpeed Cache',
        category: 'cache',
        potentialConflicts: ['caching', 'scripts', 'styles']
    },
    {
        slug: 'wp-rocket',
        name: 'WP Rocket',
        category: 'cache',
        potentialConflicts: ['caching', 'lazy-load', 'scripts']
    },

    // Page Builders (competidores)
    {
        slug: 'elementor',
        name: 'Elementor',
        category: 'page-builder',
        potentialConflicts: ['editor', 'post-types', 'scripts', 'styles']
    },
    {
        slug: 'beaver-builder-lite-version',
        name: 'Beaver Builder',
        category: 'page-builder',
        potentialConflicts: ['editor', 'post-types', 'scripts']
    },
    {
        slug: 'divi-builder',
        name: 'Divi Builder',
        category: 'page-builder',
        potentialConflicts: ['editor', 'post-types', 'scripts', 'styles']
    },

    // Multilingual
    {
        slug: 'wpml',
        name: 'WPML',
        category: 'multilingual',
        potentialConflicts: ['post-types', 'rest-api', 'translations']
    },
    {
        slug: 'polylang',
        name: 'Polylang',
        category: 'multilingual',
        potentialConflicts: ['post-types', 'rest-api', 'translations']
    },
    {
        slug: 'translatepress-multilingual',
        name: 'TranslatePress',
        category: 'multilingual',
        potentialConflicts: ['frontend', 'scripts']
    },

    // Custom Fields
    {
        slug: 'advanced-custom-fields',
        name: 'ACF',
        category: 'custom-fields',
        potentialConflicts: ['meta-boxes', 'rest-api', 'fields']
    },
    {
        slug: 'meta-box',
        name: 'Meta Box',
        category: 'custom-fields',
        potentialConflicts: ['meta-boxes', 'rest-api']
    },

    // Backup & Migration
    {
        slug: 'updraftplus',
        name: 'UpdraftPlus',
        category: 'backup',
        potentialConflicts: ['database', 'files']
    },
    {
        slug: 'duplicator',
        name: 'Duplicator',
        category: 'backup',
        potentialConflicts: ['database', 'files']
    },

    // Media
    {
        slug: 'regenerate-thumbnails',
        name: 'Regenerate Thumbnails',
        category: 'media',
        potentialConflicts: ['images', 'media-library']
    },
    {
        slug: 'imagify',
        name: 'Imagify',
        category: 'media',
        potentialConflicts: ['images', 'optimization']
    },
    {
        slug: 'smush',
        name: 'Smush',
        category: 'media',
        potentialConflicts: ['images', 'lazy-load']
    },

    // Admin / Dashboard
    {
        slug: 'jetpack',
        name: 'Jetpack',
        category: 'multi-purpose',
        potentialConflicts: ['scripts', 'styles', 'rest-api', 'cdn']
    },
    {
        slug: 'akismet',
        name: 'Akismet',
        category: 'anti-spam',
        potentialConflicts: ['forms', 'comments']
    },

    // Redirects
    {
        slug: 'redirection',
        name: 'Redirection',
        category: 'redirects',
        potentialConflicts: ['routing', 'rest-api']
    },
    {
        slug: '301-redirects',
        name: 'Simple 301 Redirects',
        category: 'redirects',
        potentialConflicts: ['routing']
    },

    // SSL
    {
        slug: 'really-simple-ssl',
        name: 'Really Simple SSL',
        category: 'ssl',
        potentialConflicts: ['urls', 'mixed-content']
    },

    // Analytics
    {
        slug: 'google-analytics-for-wordpress',
        name: 'MonsterInsights',
        category: 'analytics',
        potentialConflicts: ['scripts', 'tracking']
    },
    {
        slug: 'google-site-kit',
        name: 'Site Kit by Google',
        category: 'analytics',
        potentialConflicts: ['scripts', 'rest-api']
    }
];

/**
 * Tipos de verificaciones de compatibilidad
 */
const COMPATIBILITY_CHECKS = {
    /**
     * Verificar que el editor VBP carga correctamente
     */
    editorLoads: {
        name: 'Editor loads',
        description: 'El editor VBP carga sin errores',
        async test(context) {
            try {
                // Simular carga del editor
                const editorInitialized = await context.initializeEditor();
                return {
                    passed: editorInitialized,
                    message: editorInitialized ? 'Editor cargado' : 'Error al cargar editor'
                };
            } catch (error) {
                return { passed: false, message: error.message };
            }
        }
    },

    /**
     * Verificar que se pueden agregar elementos
     */
    canAddElements: {
        name: 'Can add elements',
        description: 'Se pueden agregar elementos al canvas',
        async test(context) {
            try {
                const initialCount = context.getElementCount();
                await context.addElement({ type: 'text', content: 'Test' });
                const newCount = context.getElementCount();
                return {
                    passed: newCount > initialCount,
                    message: `Elementos: ${initialCount} -> ${newCount}`
                };
            } catch (error) {
                return { passed: false, message: error.message };
            }
        }
    },

    /**
     * Verificar que se puede guardar
     */
    canSave: {
        name: 'Can save',
        description: 'Se puede guardar el contenido',
        async test(context) {
            try {
                const result = await context.save();
                return {
                    passed: result.success,
                    message: result.success ? 'Guardado exitoso' : 'Error al guardar'
                };
            } catch (error) {
                return { passed: false, message: error.message };
            }
        }
    },

    /**
     * Verificar que se puede publicar
     */
    canPublish: {
        name: 'Can publish',
        description: 'Se puede publicar la pagina',
        async test(context) {
            try {
                const result = await context.publish();
                return {
                    passed: result.success,
                    message: result.success ? 'Publicacion exitosa' : 'Error al publicar'
                };
            } catch (error) {
                return { passed: false, message: error.message };
            }
        }
    },

    /**
     * Verificar que no hay errores JavaScript
     */
    noJSErrors: {
        name: 'No JS errors',
        description: 'Sin errores JavaScript en consola',
        async test(context) {
            const errors = context.getJSErrors();
            return {
                passed: errors.length === 0,
                message: errors.length === 0
                    ? 'Sin errores JS'
                    : `${errors.length} errores: ${errors.slice(0, 3).join(', ')}`
            };
        }
    },

    /**
     * Verificar que no hay conflictos de scripts
     */
    noScriptConflicts: {
        name: 'No script conflicts',
        description: 'Los scripts no entran en conflicto',
        async test(context) {
            const conflicts = context.detectScriptConflicts();
            return {
                passed: conflicts.length === 0,
                message: conflicts.length === 0
                    ? 'Sin conflictos de scripts'
                    : `Conflictos: ${conflicts.join(', ')}`
            };
        }
    },

    /**
     * Verificar que la REST API funciona
     */
    restApiWorks: {
        name: 'REST API works',
        description: 'La REST API responde correctamente',
        async test(context) {
            try {
                const response = await context.testRestEndpoint();
                return {
                    passed: response.status === 200,
                    message: `Status: ${response.status}`
                };
            } catch (error) {
                return { passed: false, message: error.message };
            }
        }
    },

    /**
     * Verificar que los estilos no se sobrescriben
     */
    stylesIntact: {
        name: 'Styles intact',
        description: 'Los estilos VBP no son sobrescritos',
        async test(context) {
            const stylesOk = context.verifyVBPStyles();
            return {
                passed: stylesOk,
                message: stylesOk ? 'Estilos intactos' : 'Estilos sobrescritos'
            };
        }
    }
};

/**
 * Contexto mock para pruebas de compatibilidad
 */
class MockCompatibilityContext {
    constructor(pluginSlug) {
        this.pluginSlug = pluginSlug;
        this.elements = [];
        this.jsErrors = [];
        this.editorState = 'uninitialized';
        this.mockResponses = {};
    }

    async initializeEditor() {
        // Simular inicializacion con posibles conflictos segun el plugin
        const conflictPlugins = ['elementor', 'beaver-builder-lite-version', 'divi-builder'];

        await this.simulateDelay(100);

        if (conflictPlugins.includes(this.pluginSlug)) {
            // 20% de probabilidad de conflicto con page builders
            if (Math.random() < 0.2) {
                this.jsErrors.push(`Conflict with ${this.pluginSlug}: editor initialization failed`);
                return false;
            }
        }

        this.editorState = 'initialized';
        return true;
    }

    getElementCount() {
        return this.elements.length;
    }

    async addElement(element) {
        await this.simulateDelay(50);
        element.id = `element-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        this.elements.push(element);
        return element;
    }

    async save() {
        await this.simulateDelay(200);

        // Cache plugins pueden causar problemas de guardado
        const cachePlugins = ['w3-total-cache', 'wp-super-cache', 'litespeed-cache', 'wp-rocket'];
        if (cachePlugins.includes(this.pluginSlug) && Math.random() < 0.1) {
            return { success: false, error: 'Cache conflict' };
        }

        return { success: true, timestamp: Date.now() };
    }

    async publish() {
        await this.simulateDelay(300);

        // Security plugins pueden bloquear publicacion
        const securityPlugins = ['wordfence', 'sucuri-scanner', 'better-wp-security'];
        if (securityPlugins.includes(this.pluginSlug) && Math.random() < 0.05) {
            return { success: false, error: 'Security block' };
        }

        return { success: true, timestamp: Date.now() };
    }

    getJSErrors() {
        return this.jsErrors;
    }

    detectScriptConflicts() {
        const conflicts = [];

        // Detectar conflictos conocidos
        if (this.pluginSlug === 'jetpack') {
            // Jetpack a veces carga jQuery UI que puede conflictuar
            if (Math.random() < 0.1) {
                conflicts.push('jQuery UI version mismatch');
            }
        }

        if (this.pluginSlug === 'elementor') {
            // Elementor usa su propia version de algunas librerias
            if (Math.random() < 0.15) {
                conflicts.push('Editor namespace collision');
            }
        }

        return conflicts;
    }

    async testRestEndpoint() {
        await this.simulateDelay(100);

        // Plugins de seguridad pueden bloquear REST API
        const blockingPlugins = ['wordfence', 'better-wp-security'];
        if (blockingPlugins.includes(this.pluginSlug) && Math.random() < 0.05) {
            return { status: 403, error: 'Forbidden by security plugin' };
        }

        return { status: 200, data: { success: true } };
    }

    verifyVBPStyles() {
        // Page builders pueden sobrescribir estilos
        const styleConflictPlugins = ['elementor', 'divi-builder', 'beaver-builder-lite-version'];
        if (styleConflictPlugins.includes(this.pluginSlug)) {
            return Math.random() > 0.1; // 10% de conflicto
        }
        return true;
    }

    simulateDelay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

/**
 * Clase principal para tests de compatibilidad
 */
class PluginCompatibilityTest {
    constructor(options = {}) {
        this.options = {
            verbose: options.verbose || false,
            timeout: options.timeout || 30000,
            retries: options.retries || 1
        };
        this.results = {};
    }

    /**
     * Testear compatibilidad con un plugin especifico
     * @param {string} pluginSlug - Slug del plugin
     * @returns {Promise<Object>} Resultados del test
     */
    async testWithPlugin(pluginSlug) {
        const pluginInfo = POPULAR_PLUGINS.find(p => p.slug === pluginSlug);
        if (!pluginInfo) {
            return {
                pluginSlug,
                error: 'Plugin not in test list',
                results: {}
            };
        }

        if (this.options.verbose) {
            console.log(`\nTesting compatibility with: ${pluginInfo.name}`);
        }

        const context = new MockCompatibilityContext(pluginSlug);
        const results = {};

        for (const [checkId, check] of Object.entries(COMPATIBILITY_CHECKS)) {
            let attempts = 0;
            let lastResult = null;

            while (attempts < this.options.retries) {
                attempts++;
                try {
                    lastResult = await Promise.race([
                        check.test(context),
                        new Promise((_, reject) =>
                            setTimeout(() => reject(new Error('Check timeout')), this.options.timeout)
                        )
                    ]);

                    if (lastResult.passed) break;
                } catch (error) {
                    lastResult = { passed: false, message: error.message };
                }
            }

            results[checkId] = {
                ...lastResult,
                checkName: check.name,
                attempts
            };

            if (this.options.verbose) {
                const status = lastResult.passed ? '  ' : '  ';
                console.log(`  ${status} ${check.name}: ${lastResult.message}`);
            }
        }

        // Calcular puntuacion general
        const checks = Object.values(results);
        const passed = checks.filter(c => c.passed).length;
        const total = checks.length;

        return {
            pluginSlug,
            pluginName: pluginInfo.name,
            category: pluginInfo.category,
            potentialConflicts: pluginInfo.potentialConflicts,
            results,
            summary: {
                passed,
                total,
                percentage: Math.round((passed / total) * 100),
                compatible: passed === total
            }
        };
    }

    /**
     * Testear todos los plugins
     * @returns {Promise<Object>} Resultados de todos los tests
     */
    async runAll() {
        console.log('VBP Plugin Compatibility Tests');
        console.log('==============================\n');

        const allResults = {};
        const categoryResults = {};

        for (const plugin of POPULAR_PLUGINS) {
            const result = await this.testWithPlugin(plugin.slug);
            allResults[plugin.slug] = result;

            // Agrupar por categoria
            if (!categoryResults[plugin.category]) {
                categoryResults[plugin.category] = [];
            }
            categoryResults[plugin.category].push(result);
        }

        return this.generateReport(allResults, categoryResults);
    }

    /**
     * Testear plugins de una categoria especifica
     * @param {string} category - Categoria a testear
     * @returns {Promise<Object>} Resultados
     */
    async runCategory(category) {
        const pluginsInCategory = POPULAR_PLUGINS.filter(p => p.category === category);

        if (pluginsInCategory.length === 0) {
            return { error: `No plugins found in category: ${category}` };
        }

        console.log(`Testing ${category} plugins...`);

        const results = {};
        for (const plugin of pluginsInCategory) {
            results[plugin.slug] = await this.testWithPlugin(plugin.slug);
        }

        return {
            category,
            pluginCount: pluginsInCategory.length,
            results,
            summary: this.summarizeResults(Object.values(results))
        };
    }

    /**
     * Generar reporte de compatibilidad
     */
    generateReport(allResults, categoryResults) {
        const allPluginResults = Object.values(allResults);
        const compatible = allPluginResults.filter(r => r.summary && r.summary.compatible);
        const partiallyCompatible = allPluginResults.filter(
            r => r.summary && !r.summary.compatible && r.summary.percentage >= 75
        );
        const incompatible = allPluginResults.filter(
            r => r.summary && r.summary.percentage < 75
        );

        console.log('\n==============================');
        console.log('Compatibility Report');
        console.log('==============================\n');

        console.log(`Total plugins tested: ${allPluginResults.length}`);
        console.log(`Fully compatible: ${compatible.length}`);
        console.log(`Partially compatible: ${partiallyCompatible.length}`);
        console.log(`Incompatible: ${incompatible.length}`);

        console.log('\n--- By Category ---');
        for (const [category, results] of Object.entries(categoryResults)) {
            const catCompatible = results.filter(r => r.summary && r.summary.compatible).length;
            console.log(`${category}: ${catCompatible}/${results.length} compatible`);
        }

        if (incompatible.length > 0) {
            console.log('\n--- Incompatible Plugins ---');
            incompatible.forEach(r => {
                console.log(`  ${r.pluginName}: ${r.summary.percentage}%`);
                const failedChecks = Object.values(r.results).filter(c => !c.passed);
                failedChecks.forEach(c => {
                    console.log(`    - ${c.checkName}: ${c.message}`);
                });
            });
        }

        return {
            total: allPluginResults.length,
            compatible: compatible.length,
            partiallyCompatible: partiallyCompatible.length,
            incompatible: incompatible.length,
            compatibilityRate: Math.round((compatible.length / allPluginResults.length) * 100),
            byCategory: categoryResults,
            details: allResults
        };
    }

    /**
     * Resumir resultados
     */
    summarizeResults(results) {
        const compatible = results.filter(r => r.summary && r.summary.compatible).length;
        return {
            total: results.length,
            compatible,
            partiallyCompatible: results.filter(
                r => r.summary && !r.summary.compatible && r.summary.percentage >= 75
            ).length,
            incompatible: results.filter(r => r.summary && r.summary.percentage < 75).length,
            compatibilityRate: Math.round((compatible / results.length) * 100)
        };
    }
}

/**
 * Matriz de compatibilidad conocida
 */
const KNOWN_COMPATIBILITY_MATRIX = {
    // Plugins con compatibilidad verificada
    verified: [
        'akismet',
        'contact-form-7',
        'yoast-seo',
        'updraftplus',
        'redirection',
        'really-simple-ssl'
    ],

    // Plugins que requieren configuracion especial
    requiresConfig: {
        'w3-total-cache': [
            'Excluir scripts VBP de minificacion',
            'No cachear paginas de admin'
        ],
        'wp-rocket': [
            'Excluir /wp-admin/ de cache',
            'No diferir scripts VBP'
        ],
        'wordfence': [
            'Permitir REST API para usuarios autenticados'
        ]
    },

    // Plugins con conflictos conocidos
    knownConflicts: {
        'elementor': [
            'No activar en mismas paginas que VBP',
            'Posible conflicto de namespaces JS'
        ],
        'divi-builder': [
            'Evitar uso simultaneo',
            'Puede sobrescribir estilos'
        ]
    }
};

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        POPULAR_PLUGINS,
        COMPATIBILITY_CHECKS,
        PluginCompatibilityTest,
        MockCompatibilityContext,
        KNOWN_COMPATIBILITY_MATRIX
    };
}

if (typeof window !== 'undefined') {
    window.VBPPluginCompatibility = {
        POPULAR_PLUGINS,
        COMPATIBILITY_CHECKS,
        PluginCompatibilityTest,
        KNOWN_COMPATIBILITY_MATRIX
    };
}
