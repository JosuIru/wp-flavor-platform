/**
 * VBP Build Configuration
 *
 * Define los bundles, chunks y configuracion de lazy loading para Visual Builder Pro.
 * Este archivo es usado por el script de build para generar assets optimizados.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 3.5.0
 */

module.exports = {
    /**
     * Version del build system
     */
    version: '1.0.0',

    /**
     * Directorio base para assets VBP
     */
    baseDir: '../',

    /**
     * Directorio de salida para bundles
     */
    outputDir: '../dist/',

    /**
     * Bundles principales
     *
     * Cada bundle agrupa archivos relacionados que se cargan juntos.
     * El orden dentro de cada bundle es importante para dependencias.
     */
    bundles: {
        /**
         * Core Bundle - SIEMPRE cargado
         * Contiene el estado, utilidades y funciones esenciales
         * Target: ~80-100KB minificado
         */
        'vbp-core': {
            files: [
                'js/vbp-theme.js',
                'js/vbp-performance.js',
                'js/vbp-store-catalog.js',
                'js/vbp-store-style-helpers.js',
                'js/vbp-store-tree-helpers.js',
                'js/vbp-store-mutation-helpers.js',
                'js/vbp-store-history-helpers.js',
                'js/vbp-store.js',
                'js/vbp-store-modals.js',
                'js/vbp-toast.js',
                'js/vbp-api.js',
                'js/vbp-history.js'
            ],
            priority: 'critical',
            preload: true,
            description: 'Estado central, API y utilidades esenciales'
        },

        /**
         * Editor Bundle - Cargado al abrir el editor
         * Canvas, inspector, capas y edicion basica
         * Target: ~120-150KB minificado
         */
        'vbp-editor': {
            files: [
                'js/vbp-canvas-utils.js',
                'js/vbp-canvas.js',
                'js/vbp-canvas-resize.js',
                'js/vbp-layers.js',
                'js/vbp-inspector.js',
                'js/vbp-inspector-utils.js',
                'js/vbp-inspector-modals.js',
                'js/vbp-inspector-media.js',
                'js/vbp-rulers.js',
                'js/vbp-text-editor.js',
                'js/vbp-inline-editor.js',
                'js/vbp-richtext.js',
                'js/vbp-breadcrumbs.js',
                'js/vbp-link-search.js',
                'js/vbp-zoom-utils.js'
            ],
            dependencies: ['vbp-core'],
            priority: 'high',
            preload: true,
            description: 'Canvas, inspector y herramientas de edicion'
        },

        /**
         * Keyboard Bundle - Atajos de teclado y command palette
         * Target: ~40-50KB minificado
         */
        'vbp-keyboard': {
            files: [
                'js/vbp-keyboard-modular.js',
                'js/vbp-keyboard-loader.js',
                'js/vbp-command-palette.js',
                'js/vbp-accessibility.js',
                'js/modules/vbp-keyboard-clipboard.js',
                'js/modules/vbp-keyboard-editors.js',
                'js/modules/vbp-keyboard-selection.js',
                'js/modules/vbp-keyboard-tools.js',
                'js/modules/vbp-keyboard-transform.js'
            ],
            dependencies: ['vbp-core'],
            priority: 'high',
            preload: true,
            description: 'Sistema de atajos de teclado y command palette'
        },

        /**
         * Symbols Bundle - Sistema de simbolos e instancias
         * Target: ~60-70KB minificado
         */
        'vbp-symbols': {
            files: [
                'js/vbp-symbols.js',
                'js/vbp-symbols-panel.js',
                'js/vbp-symbols-commands.js',
                'js/vbp-instance-inspector.js',
                'js/vbp-instance-renderer.js',
                'js/vbp-swap-modal.js'
            ],
            dependencies: ['vbp-core', 'vbp-editor'],
            priority: 'normal',
            lazy: true,
            trigger: 'symbols-panel-open',
            description: 'Sistema de componentes reutilizables (simbolos)'
        },

        /**
         * Animation Bundle - Constructor de animaciones CSS
         * Target: ~30-40KB minificado
         */
        'vbp-animation': {
            files: [
                'js/vbp-animations.js',
                'js/vbp-animation-builder.js'
            ],
            dependencies: ['vbp-core', 'vbp-editor'],
            priority: 'low',
            lazy: true,
            trigger: 'animation-panel-open',
            description: 'Constructor visual de animaciones CSS'
        },

        /**
         * Responsive Bundle - Sistema de variantes responsive
         * Target: ~35-45KB minificado
         */
        'vbp-responsive': {
            files: [
                'js/vbp-constraints.js',
                'js/vbp-responsive-variants.js',
                'js/vbp-responsive-panel.js',
                'js/vbp-spacing-indicators.js'
            ],
            dependencies: ['vbp-core', 'vbp-editor'],
            priority: 'normal',
            lazy: true,
            trigger: 'responsive-mode-active',
            description: 'Sistema de diseño responsive y constraints'
        },

        /**
         * Prototype Bundle - Modo prototipado interactivo
         * Target: ~50-60KB minificado
         */
        'vbp-prototype': {
            files: [
                'js/vbp-prototype-mode.js',
                'js/vbp-prototype-panel.js'
            ],
            dependencies: ['vbp-core', 'vbp-editor', 'vbp-animation'],
            priority: 'low',
            lazy: true,
            trigger: 'prototype-mode-enabled',
            description: 'Sistema de prototipado con interacciones'
        },

        /**
         * Collaboration Bundle - Colaboracion en tiempo real
         * Target: ~40-50KB minificado
         */
        'vbp-collab': {
            files: [
                'js/vbp-realtime-collab.js',
                'js/vbp-comments.js',
                'js/modules/vbp-app-collaboration.js'
            ],
            dependencies: ['vbp-core'],
            priority: 'low',
            lazy: true,
            trigger: 'collaboration-enabled',
            featureFlag: 'collaboration',
            description: 'Colaboracion en tiempo real y comentarios'
        },

        /**
         * AI Bundle - Funciones de IA
         * Target: ~40-50KB minificado
         */
        'vbp-ai': {
            files: [
                'js/vbp-ai-assistant.js',
                'js/vbp-ai-layout.js',
                'js/vbp-ai-layout-panel.js'
            ],
            dependencies: ['vbp-core', 'vbp-editor'],
            priority: 'low',
            lazy: true,
            trigger: 'ai-panel-open',
            featureFlag: 'ai',
            description: 'Asistente IA y generacion de layouts'
        },

        /**
         * Branching Bundle - Sistema de ramas de diseno
         * Target: ~25-35KB minificado
         */
        'vbp-branching': {
            files: [
                'js/modules/vbp-app-branching.js',
                'js/modules/vbp-branch-panel.js'
            ],
            dependencies: ['vbp-core', 'vbp-app'],
            priority: 'low',
            lazy: true,
            trigger: 'branching-panel-open',
            featureFlag: 'branching',
            description: 'Sistema de ramas para experimentos de diseno'
        },

        /**
         * App Modules Bundle - Modulos de aplicacion
         * Target: ~80-100KB minificado
         */
        'vbp-app': {
            files: [
                'js/vbp-app.js',
                'js/vbp-app-modular.js',
                'js/modules/vbp-app-commands.js',
                'js/modules/vbp-app-page-settings.js',
                'js/modules/vbp-app-templates.js',
                'js/modules/vbp-app-import-export.js',
                'js/modules/vbp-app-revisions.js',
                'js/modules/vbp-app-version-history.js',
                'js/modules/vbp-app-unsplash.js',
                'js/modules/vbp-app-split-screen.js',
                'js/modules/vbp-app-mobile.js'
            ],
            dependencies: ['vbp-core'],
            priority: 'high',
            preload: true,
            description: 'Modulos de aplicacion y paneles'
        },

        /**
         * Advanced Bundle - Funciones avanzadas opcionales
         * Target: ~60-80KB minificado
         */
        'vbp-advanced': {
            files: [
                'js/vbp-global-styles.js',
                'js/vbp-global-styles-panel.js',
                'js/vbp-asset-manager.js',
                'js/vbp-bulk-edit.js',
                'js/vbp-minimap.js',
                'js/vbp-module-preview.js',
                'js/vbp-component-library.js',
                'js/vbp-help-system.js'
            ],
            dependencies: ['vbp-core', 'vbp-editor'],
            priority: 'low',
            lazy: true,
            trigger: 'advanced-features-used',
            description: 'Estilos globales, asset manager y herramientas avanzadas'
        },

        /**
         * Export Bundle - Exportacion de codigo
         * Target: ~30-40KB minificado
         */
        'vbp-export': {
            files: [
                'js/modules/vbp-keyboard-export.js',
                'js/modules/vbp-keyboard-figma.js',
                'js/modules/vbp-app-design-tokens.js'
            ],
            dependencies: ['vbp-core'],
            priority: 'low',
            lazy: true,
            trigger: 'export-panel-open',
            description: 'Exportacion a React, Vue, Figma'
        },

        /**
         * Admin Bundle - Funciones de administrador
         * Target: ~30-40KB minificado
         */
        'vbp-admin': {
            files: [
                'js/modules/vbp-app-audit-log.js',
                'js/modules/vbp-app-workflows.js',
                'js/modules/vbp-app-multisite.js'
            ],
            dependencies: ['vbp-core', 'vbp-app'],
            priority: 'low',
            lazy: true,
            trigger: 'admin-features-enabled',
            featureFlag: 'audit_log',
            description: 'Audit log, workflows y multisite'
        }
    },

    /**
     * CSS Bundles
     */
    cssBundles: {
        /**
         * Core CSS - Siempre cargado
         */
        'vbp-core': {
            files: [
                'css/editor-core.css',
                'css/editor-toolbar.css',
                'css/editor-statusbar.css',
                'css/editor-tooltips.css',
                'css/editor-toast.css',
                'css/vbp-design-tokens.css'
            ],
            priority: 'critical',
            preload: true
        },

        /**
         * Editor CSS
         */
        'vbp-editor': {
            files: [
                'css/editor-canvas.css',
                'css/editor-panels.css',
                'css/editor-rulers.css',
                'css/editor-responsive.css',
                'css/editor-selectors.css',
                'css/editor-richtext.css',
                'css/editor-command-palette.css',
                'css/editor-ux-improvements.css',
                'css/editor-preview-sections.css',
                'css/vbp-blocks-enhanced.css',
                'css/vbp-mobile.css'
            ],
            dependencies: ['vbp-core'],
            priority: 'high',
            preload: true
        },

        /**
         * Symbols CSS
         */
        'vbp-symbols': {
            files: [
                'css/symbols-panel.css',
                'css/instance-inspector.css',
                'css/vbp-swap-modal.css'
            ],
            lazy: true,
            trigger: 'symbols-panel-open'
        },

        /**
         * Animation CSS
         */
        'vbp-animation': {
            files: [
                'css/animations.css',
                'css/animation-builder.css'
            ],
            lazy: true,
            trigger: 'animation-panel-open'
        },

        /**
         * Responsive CSS
         */
        'vbp-responsive': {
            files: [
                'css/constraints.css',
                'css/responsive-variants.css',
                'css/smart-guides.css',
                'css/spacing-indicators.css'
            ],
            lazy: true,
            trigger: 'responsive-mode-active'
        },

        /**
         * Prototype CSS
         */
        'vbp-prototype': {
            files: [
                'css/prototype-mode.css'
            ],
            lazy: true,
            trigger: 'prototype-mode-enabled'
        },

        /**
         * Collaboration CSS
         */
        'vbp-collab': {
            files: [
                'css/vbp-collaboration.css',
                'css/realtime-collab.css'
            ],
            lazy: true,
            trigger: 'collaboration-enabled',
            featureFlag: 'collaboration'
        },

        /**
         * AI CSS
         */
        'vbp-ai': {
            files: [
                'css/editor-ai-assistant.css',
                'css/ai-layout.css'
            ],
            lazy: true,
            trigger: 'ai-panel-open',
            featureFlag: 'ai'
        },

        /**
         * Branching CSS
         */
        'vbp-branching': {
            files: [
                'css/branching.css'
            ],
            lazy: true,
            trigger: 'branching-panel-open',
            featureFlag: 'branching'
        },

        /**
         * Advanced CSS
         */
        'vbp-advanced': {
            files: [
                'css/global-styles-panel.css',
                'css/asset-manager.css',
                'css/vbp-bulk-edit.css',
                'css/editor-minimap.css',
                'css/editor-help-system.css'
            ],
            lazy: true,
            trigger: 'advanced-features-used'
        },

        /**
         * Admin CSS
         */
        'vbp-admin': {
            files: [
                'css/vbp-audit-log.css',
                'css/vbp-workflows.css',
                'css/vbp-multisite.css'
            ],
            lazy: true,
            trigger: 'admin-features-enabled',
            featureFlag: 'audit_log'
        },

        /**
         * Frontend CSS - Para renderizado en frontend
         */
        'vbp-frontend': {
            files: [
                'css/frontend-components.css',
                'css/popup-frontend.css'
            ],
            context: 'frontend',
            priority: 'high'
        }
    },

    /**
     * Vendor dependencies
     */
    vendor: {
        'alpinejs': {
            files: ['vendor/alpine.min.js', 'vendor/alpine-collapse.min.js'],
            priority: 'critical',
            external: false
        },
        'sortablejs': {
            files: ['vendor/sortable.min.js'],
            priority: 'high',
            external: false
        },
        'fontawesome': {
            files: ['vendor/fontawesome.min.css'],
            type: 'css',
            priority: 'high',
            external: false
        },
        'material-icons': {
            files: ['vendor/material-icons.css'],
            type: 'css',
            priority: 'high',
            external: false
        }
    },

    /**
     * Configuracion de terser para minificacion JS
     */
    terserOptions: {
        production: {
            compress: {
                drop_console: false,
                drop_debugger: true,
                pure_funcs: ['console.debug', 'console.trace']
            },
            mangle: {
                reserved: ['Alpine', 'VBP_Config', 'vbpStore', 'vbpApp']
            },
            format: {
                comments: false
            }
        },
        development: {
            compress: false,
            mangle: false,
            format: {
                comments: 'some'
            },
            sourceMap: true
        }
    },

    /**
     * Configuracion de cssnano para minificacion CSS
     */
    cssnanoOptions: {
        production: {
            preset: ['default', {
                discardComments: { removeAll: true },
                normalizeWhitespace: true,
                minifyFontValues: true,
                minifyGradients: true,
                colormin: true,
                reduceIdents: false,
                mergeIdents: false
            }]
        },
        development: {
            preset: ['default', {
                discardComments: false,
                normalizeWhitespace: false
            }]
        }
    },

    /**
     * Critical CSS configuration
     */
    criticalCss: {
        enabled: true,
        inlineThreshold: 14000, // Inline CSS smaller than 14KB
        extractSelectors: [
            '.vbp-editor',
            '.vbp-toolbar',
            '.vbp-canvas',
            '.vbp-panel',
            '[x-data]',
            '[x-show]'
        ]
    },

    /**
     * Code splitting configuration
     */
    codeSplitting: {
        enabled: true,
        minChunkSize: 10000, // 10KB minimum chunk size
        maxInitialRequests: 4,
        maxAsyncRequests: 6
    }
};
