/**
 * Visual Builder Pro - Theme Manager
 * Gestión de temas claro/oscuro
 *
 * @package Flavor_Chat_IA
 * @since 2.0.18
 */

(function() {
    'use strict';

    /**
     * Clave para localStorage
     */
    var STORAGE_KEY = 'vbp_theme';

    /**
     * Temas disponibles
     */
    var THEMES = {
        DARK: 'dark',
        LIGHT: 'light',
        SYSTEM: 'system'
    };

    /**
     * Store Alpine para gestión del tema
     */
    document.addEventListener('alpine:init', function() {
        Alpine.store('vbpTheme', {
            /**
             * Tema actual: 'dark', 'light', o 'system'
             */
            current: THEMES.SYSTEM,

            /**
             * Tema efectivo (resuelto): 'dark' o 'light'
             */
            resolved: THEMES.DARK,

            /**
             * Indica si el sistema prefiere modo claro
             */
            systemPrefersDark: true,

            /**
             * Inicializar tema
             */
            init: function() {
                var self = this;

                // Detectar preferencia del sistema
                this.detectSystemPreference();

                // Cargar tema guardado
                this.loadSavedTheme();

                // Aplicar tema inicial
                this.applyTheme();

                // Escuchar cambios en preferencia del sistema
                if (window.matchMedia) {
                    var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                    mediaQuery.addEventListener('change', function(e) {
                        self.systemPrefersDark = e.matches;
                        if (self.current === THEMES.SYSTEM) {
                            self.resolveTheme();
                            self.applyTheme();
                        }
                    });
                }
            },

            /**
             * Detectar preferencia del sistema operativo
             */
            detectSystemPreference: function() {
                if (window.matchMedia) {
                    this.systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                } else {
                    this.systemPrefersDark = true; // Default a oscuro si no hay soporte
                }
            },

            /**
             * Cargar tema guardado desde localStorage
             */
            loadSavedTheme: function() {
                try {
                    var savedTheme = localStorage.getItem(STORAGE_KEY);
                    if (savedTheme && (savedTheme === THEMES.DARK || savedTheme === THEMES.LIGHT || savedTheme === THEMES.SYSTEM)) {
                        this.current = savedTheme;
                    } else {
                        this.current = THEMES.SYSTEM;
                    }
                } catch (e) {
                    this.current = THEMES.SYSTEM;
                }
                this.resolveTheme();
            },

            /**
             * Guardar tema en localStorage
             */
            saveTheme: function() {
                try {
                    localStorage.setItem(STORAGE_KEY, this.current);
                } catch (e) {
                    // localStorage no disponible
                }
            },

            /**
             * Resolver tema efectivo
             */
            resolveTheme: function() {
                if (this.current === THEMES.SYSTEM) {
                    this.resolved = this.systemPrefersDark ? THEMES.DARK : THEMES.LIGHT;
                } else {
                    this.resolved = this.current;
                }
            },

            /**
             * Aplicar tema al DOM
             */
            applyTheme: function() {
                var root = document.documentElement;
                root.setAttribute('data-vbp-theme', this.resolved);

                // Actualizar meta theme-color para móviles
                var metaThemeColor = document.querySelector('meta[name="theme-color"]');
                if (!metaThemeColor) {
                    metaThemeColor = document.createElement('meta');
                    metaThemeColor.name = 'theme-color';
                    document.head.appendChild(metaThemeColor);
                }
                metaThemeColor.content = this.resolved === THEMES.DARK ? '#1a1a1a' : '#ffffff';
            },

            /**
             * Establecer tema
             * @param {string} theme - 'dark', 'light', o 'system'
             */
            setTheme: function(theme) {
                if (theme === THEMES.DARK || theme === THEMES.LIGHT || theme === THEMES.SYSTEM) {
                    this.current = theme;
                    this.resolveTheme();
                    this.applyTheme();
                    this.saveTheme();

                    // Emitir evento personalizado
                    window.dispatchEvent(new CustomEvent('vbp-theme-changed', {
                        detail: { theme: this.current, resolved: this.resolved }
                    }));
                }
            },

            /**
             * Cambiar entre temas (toggle)
             */
            toggle: function() {
                if (this.resolved === THEMES.DARK) {
                    this.setTheme(THEMES.LIGHT);
                } else {
                    this.setTheme(THEMES.DARK);
                }
            },

            /**
             * Ciclar entre temas: dark -> light -> system -> dark
             */
            cycle: function() {
                switch (this.current) {
                    case THEMES.DARK:
                        this.setTheme(THEMES.LIGHT);
                        break;
                    case THEMES.LIGHT:
                        this.setTheme(THEMES.SYSTEM);
                        break;
                    case THEMES.SYSTEM:
                    default:
                        this.setTheme(THEMES.DARK);
                        break;
                }
            },

            /**
             * Verificar si es modo oscuro
             * @returns {boolean}
             */
            isDark: function() {
                return this.resolved === THEMES.DARK;
            },

            /**
             * Verificar si es modo claro
             * @returns {boolean}
             */
            isLight: function() {
                return this.resolved === THEMES.LIGHT;
            },

            /**
             * Verificar si está en modo sistema
             * @returns {boolean}
             */
            isSystem: function() {
                return this.current === THEMES.SYSTEM;
            },

            /**
             * Obtener icono del tema actual
             * @returns {string} Emoji o icono del tema
             */
            getIcon: function() {
                switch (this.current) {
                    case THEMES.DARK:
                        return '🌙';
                    case THEMES.LIGHT:
                        return '☀️';
                    case THEMES.SYSTEM:
                    default:
                        return '💻';
                }
            },

            /**
             * Obtener nombre del tema actual
             * @returns {string}
             */
            getLabel: function() {
                switch (this.current) {
                    case THEMES.DARK:
                        return 'Oscuro';
                    case THEMES.LIGHT:
                        return 'Claro';
                    case THEMES.SYSTEM:
                    default:
                        return 'Sistema';
                }
            }
        });
    });

    /**
     * Inicialización temprana (antes de Alpine)
     * Previene flash de tema incorrecto
     */
    (function earlyInit() {
        try {
            var savedTheme = localStorage.getItem(STORAGE_KEY);
            var theme = THEMES.DARK;

            if (savedTheme === THEMES.LIGHT) {
                theme = THEMES.LIGHT;
            } else if (savedTheme === THEMES.SYSTEM || !savedTheme) {
                // Detectar preferencia del sistema
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
                    theme = THEMES.LIGHT;
                }
            }

            document.documentElement.setAttribute('data-vbp-theme', theme);
        } catch (e) {
            document.documentElement.setAttribute('data-vbp-theme', THEMES.DARK);
        }
    })();

    /**
     * Inicializar store después de que Alpine esté listo
     */
    document.addEventListener('alpine:initialized', function() {
        var store = Alpine.store('vbpTheme');
        if (store && typeof store.init === 'function') {
            store.init();
        }
    });

    /**
     * Atajo de teclado para cambiar tema (Ctrl+Shift+T)
     */
    document.addEventListener('keydown', function(e) {
        // Ctrl+Shift+T (o Cmd+Shift+T en Mac)
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 't') {
            e.preventDefault();
            var store = Alpine.store('vbpTheme');
            if (store && typeof store.toggle === 'function') {
                store.toggle();

                // Mostrar notificación si el toast está disponible
                var toastStore = Alpine.store('vbpToast');
                if (toastStore && typeof toastStore.show === 'function') {
                    var label = store.getLabel();
                    toastStore.show('Tema: ' + label, 'info', 1500);
                }
            }
        }
    });

})();

/**
 * Sistema de Presets de Colores Globales
 */
(function() {
    'use strict';

    var PRESET_STORAGE_KEY = 'vbp_color_preset';

    /**
     * Presets de colores disponibles
     */
    var COLOR_PRESETS = {
        'modern-blue': {
            id: 'modern-blue',
            nombre: 'Azul Moderno',
            colors: {
                primary: '#3b82f6',
                secondary: '#1e40af',
                accent: '#60a5fa',
                background: '#f8fafc',
                text: '#1e293b'
            }
        },
        'eco-green': {
            id: 'eco-green',
            nombre: 'Verde Ecológico',
            colors: {
                primary: '#22c55e',
                secondary: '#15803d',
                accent: '#86efac',
                background: '#f0fdf4',
                text: '#14532d'
            }
        },
        'warm-orange': {
            id: 'warm-orange',
            nombre: 'Naranja Cálido',
            colors: {
                primary: '#f97316',
                secondary: '#c2410c',
                accent: '#fdba74',
                background: '#fff7ed',
                text: '#7c2d12'
            }
        },
        'elegant-purple': {
            id: 'elegant-purple',
            nombre: 'Violeta Elegante',
            colors: {
                primary: '#8b5cf6',
                secondary: '#6d28d9',
                accent: '#c4b5fd',
                background: '#faf5ff',
                text: '#3b0764'
            }
        },
        'antifa-red': {
            id: 'antifa-red',
            nombre: 'Rojo Antifascista',
            colors: {
                primary: '#dc2626',
                secondary: '#1f2937',
                accent: '#fca5a5',
                background: '#fef2f2',
                text: '#1f2937'
            }
        }
    };

    document.addEventListener('alpine:init', function() {
        Alpine.store('vbpColorPresets', {
            activePreset: null,
            presets: COLOR_PRESETS,

            init: function() {
                this.loadSavedPreset();
            },

            loadSavedPreset: function() {
                try {
                    var saved = localStorage.getItem(PRESET_STORAGE_KEY);
                    if (saved && COLOR_PRESETS[saved]) {
                        this.activePreset = saved;
                        this.applyPreset(saved);
                    }
                } catch (e) {}
            },

            applyPreset: function(presetId) {
                var preset = COLOR_PRESETS[presetId];
                if (!preset) return false;

                this.activePreset = presetId;
                try { localStorage.setItem(PRESET_STORAGE_KEY, presetId); } catch (e) {}

                var canvas = document.querySelector('.vbp-canvas');
                if (canvas) {
                    Object.keys(preset.colors).forEach(function(key) {
                        canvas.style.setProperty('--vbp-' + key, preset.colors[key]);
                    });
                }

                document.dispatchEvent(new CustomEvent('vbp:preset:applied', {
                    detail: { preset: preset }
                }));
                return true;
            },

            getPresetsList: function() {
                var self = this;
                return Object.keys(COLOR_PRESETS).map(function(id) {
                    return {
                        id: id,
                        nombre: COLOR_PRESETS[id].nombre,
                        primary: COLOR_PRESETS[id].colors.primary,
                        secondary: COLOR_PRESETS[id].colors.secondary,
                        active: self.activePreset === id
                    };
                });
            },

            reset: function() {
                this.activePreset = null;
                try { localStorage.removeItem(PRESET_STORAGE_KEY); } catch (e) {}
                var canvas = document.querySelector('.vbp-canvas');
                if (canvas) {
                    Object.keys(COLOR_PRESETS['modern-blue'].colors).forEach(function(key) {
                        canvas.style.removeProperty('--vbp-' + key);
                    });
                }
            }
        });
    });

    document.addEventListener('alpine:initialized', function() {
        var store = Alpine.store('vbpColorPresets');
        if (store && typeof store.init === 'function') {
            store.init();
        }
    });

})();
