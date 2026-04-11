/**
 * Visual Builder Pro - Editor Themes System
 *
 * Sistema completo de temas para el editor VBP.
 * Soporta temas predefinidos, personalizados e importación/exportación.
 *
 * @package Flavor_Platform
 * @since 2.3.0
 */

(function() {
    'use strict';

    /**
     * Clave para localStorage
     */
    var STORAGE_KEY = 'vbp_editor_theme';
    var CUSTOM_THEMES_KEY = 'vbp_custom_themes';

    /**
     * Temas predefinidos del editor
     */
    var EDITOR_THEMES = {
        light: {
            id: 'light',
            name: 'Claro',
            description: 'Tema claro por defecto, ideal para ambientes bien iluminados',
            icon: 'wb_sunny',
            category: 'default',
            colors: {
                bgPrimary: '#ffffff',
                bgSecondary: '#f8fafc',
                bgTertiary: '#f1f5f9',
                textPrimary: '#1e293b',
                textSecondary: '#475569',
                accent: '#3b82f6',
                accentLight: '#60a5fa',
                success: '#22c55e',
                warning: '#f59e0b',
                error: '#ef4444',
                borderLight: '#e2e8f0',
                borderMedium: '#cbd5e1'
            }
        },
        dark: {
            id: 'dark',
            name: 'Oscuro',
            description: 'Tema oscuro para reducir fatiga visual en ambientes con poca luz',
            icon: 'dark_mode',
            category: 'default',
            colors: {
                bgPrimary: '#0f172a',
                bgSecondary: '#1e293b',
                bgTertiary: '#334155',
                textPrimary: '#f1f5f9',
                textSecondary: '#cbd5e1',
                accent: '#60a5fa',
                accentLight: '#93c5fd',
                success: '#4ade80',
                warning: '#fbbf24',
                error: '#f87171',
                borderLight: '#334155',
                borderMedium: '#475569'
            }
        },
        midnight: {
            id: 'midnight',
            name: 'Azul Medianoche',
            description: 'Tema oscuro con tonos azules profundos',
            icon: 'nights_stay',
            category: 'dark',
            colors: {
                bgPrimary: '#0c1929',
                bgSecondary: '#142337',
                bgTertiary: '#1d3048',
                textPrimary: '#e0e7ef',
                textSecondary: '#a8b9cb',
                accent: '#5ba4f5',
                accentLight: '#82bdf8',
                success: '#4ade80',
                warning: '#fbbf24',
                error: '#f87171',
                borderLight: '#1d3048',
                borderMedium: '#264060'
            }
        },
        forest: {
            id: 'forest',
            name: 'Verde Bosque',
            description: 'Tema oscuro con tonos verdes naturales',
            icon: 'park',
            category: 'dark',
            colors: {
                bgPrimary: '#0d1f17',
                bgSecondary: '#142d22',
                bgTertiary: '#1c3d2e',
                textPrimary: '#e0f0e8',
                textSecondary: '#a8ccb8',
                accent: '#4ade80',
                accentLight: '#86efac',
                success: '#4ade80',
                warning: '#fbbf24',
                error: '#f87171',
                borderLight: '#1c3d2e',
                borderMedium: '#245239'
            }
        },
        'high-contrast': {
            id: 'high-contrast',
            name: 'Alto Contraste',
            description: 'Tema de alto contraste para mejor accesibilidad',
            icon: 'contrast',
            category: 'accessibility',
            colors: {
                bgPrimary: '#000000',
                bgSecondary: '#0a0a0a',
                bgTertiary: '#171717',
                textPrimary: '#ffffff',
                textSecondary: '#e5e5e5',
                accent: '#fbbf24',
                accentLight: '#fcd34d',
                success: '#22c55e',
                warning: '#fbbf24',
                error: '#ef4444',
                borderLight: '#404040',
                borderMedium: '#525252'
            }
        },
        system: {
            id: 'system',
            name: 'Sistema',
            description: 'Usa el tema de tu sistema operativo',
            icon: 'computer',
            category: 'default',
            colors: null // Se resuelve dinámicamente
        }
    };

    /**
     * Categorías de temas
     */
    var THEME_CATEGORIES = {
        default: {
            id: 'default',
            name: 'Predeterminados',
            description: 'Temas incluidos con VBP'
        },
        dark: {
            id: 'dark',
            name: 'Temas Oscuros',
            description: 'Variaciones del tema oscuro'
        },
        accessibility: {
            id: 'accessibility',
            name: 'Accesibilidad',
            description: 'Temas optimizados para accesibilidad'
        },
        custom: {
            id: 'custom',
            name: 'Personalizados',
            description: 'Tus temas personalizados'
        }
    };

    /**
     * Store de Alpine para gestión de temas del editor
     */
    document.addEventListener('alpine:init', function() {
        Alpine.store('vbpEditorThemes', {
            /**
             * ID del tema actual
             */
            currentThemeId: 'system',

            /**
             * Tema actual resuelto (para 'system')
             */
            resolvedThemeId: 'dark',

            /**
             * Preferencia del sistema
             */
            systemPrefersDark: true,

            /**
             * Temas disponibles (predefinidos + custom)
             */
            themes: Object.assign({}, EDITOR_THEMES),

            /**
             * Temas personalizados
             */
            customThemes: {},

            /**
             * Panel de temas abierto
             */
            isPanelOpen: false,

            /**
             * Editor de tema abierto
             */
            isEditorOpen: false,

            /**
             * Tema en edición
             */
            editingTheme: null,

            /**
             * Inicialización
             */
            init: function() {
                var self = this;

                // Detectar preferencia del sistema
                this.detectSystemPreference();

                // Cargar temas personalizados
                this.loadCustomThemes();

                // Cargar tema guardado
                this.loadSavedTheme();

                // Aplicar tema inicial
                this.applyTheme();

                // Escuchar cambios en preferencia del sistema
                if (window.matchMedia) {
                    var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                    try {
                        // Navegadores modernos
                        mediaQuery.addEventListener('change', function(mediaEvent) {
                            self.systemPrefersDark = mediaEvent.matches;
                            if (self.currentThemeId === 'system') {
                                self.resolveTheme();
                                self.applyTheme();
                            }
                        });
                    } catch (compatError) {
                        // Safari antiguo
                        mediaQuery.addListener(function(mediaEvent) {
                            self.systemPrefersDark = mediaEvent.matches;
                            if (self.currentThemeId === 'system') {
                                self.resolveTheme();
                                self.applyTheme();
                            }
                        });
                    }
                }
            },

            /**
             * Detectar preferencia del sistema
             */
            detectSystemPreference: function() {
                if (window.matchMedia) {
                    this.systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                } else {
                    this.systemPrefersDark = true;
                }
            },

            /**
             * Cargar temas personalizados desde localStorage
             */
            loadCustomThemes: function() {
                try {
                    var customThemesStored = localStorage.getItem(CUSTOM_THEMES_KEY);
                    if (customThemesStored) {
                        this.customThemes = JSON.parse(customThemesStored);
                        // Añadir a themes disponibles
                        var self = this;
                        Object.keys(this.customThemes).forEach(function(themeId) {
                            self.themes[themeId] = self.customThemes[themeId];
                        });
                    }
                } catch (loadError) {
                    console.warn('[VBP Themes] Error cargando temas personalizados:', loadError);
                }
            },

            /**
             * Cargar tema guardado
             */
            loadSavedTheme: function() {
                try {
                    var savedThemeId = localStorage.getItem(STORAGE_KEY);
                    if (savedThemeId && this.themes[savedThemeId]) {
                        this.currentThemeId = savedThemeId;
                    } else {
                        this.currentThemeId = 'system';
                    }
                } catch (loadError) {
                    this.currentThemeId = 'system';
                }
                this.resolveTheme();
            },

            /**
             * Guardar tema en localStorage y user meta
             */
            saveTheme: function() {
                // Guardar en localStorage
                try {
                    localStorage.setItem(STORAGE_KEY, this.currentThemeId);
                } catch (saveError) {
                    console.warn('[VBP Themes] Error guardando tema:', saveError);
                }

                // Guardar en user meta via AJAX
                this.saveThemeToServer();
            },

            /**
             * Guardar tema en servidor
             */
            saveThemeToServer: function() {
                if (typeof vbpData === 'undefined' || !vbpData.ajaxUrl) return;

                var formData = new FormData();
                formData.append('action', 'vbp_save_editor_theme');
                formData.append('nonce', vbpData.nonce);
                formData.append('theme_id', this.currentThemeId);

                fetch(vbpData.ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                }).catch(function(fetchError) {
                    console.warn('[VBP Themes] Error guardando tema en servidor:', fetchError);
                });
            },

            /**
             * Resolver tema actual
             */
            resolveTheme: function() {
                if (this.currentThemeId === 'system') {
                    this.resolvedThemeId = this.systemPrefersDark ? 'dark' : 'light';
                } else {
                    this.resolvedThemeId = this.currentThemeId;
                }
            },

            /**
             * Aplicar tema al DOM
             */
            applyTheme: function() {
                var root = document.documentElement;

                // Añadir clase de transición
                root.classList.add('vbp-theme-transitioning');

                // Aplicar atributo de tema
                root.setAttribute('data-vbp-theme', this.resolvedThemeId);

                // Quitar clase de transición después de la animación
                setTimeout(function() {
                    root.classList.remove('vbp-theme-transitioning');
                }, 50);

                // Actualizar meta theme-color
                this.updateMetaThemeColor();

                // Emitir evento
                window.dispatchEvent(new CustomEvent('vbp-editor-theme-changed', {
                    detail: {
                        themeId: this.currentThemeId,
                        resolvedThemeId: this.resolvedThemeId,
                        theme: this.themes[this.resolvedThemeId]
                    }
                }));

                // Emitir via VBP si está disponible
                if (window.VBP && typeof window.VBP.emit === 'function') {
                    window.VBP.emit('vbp:theme:changed', {
                        themeId: this.currentThemeId,
                        resolvedThemeId: this.resolvedThemeId
                    });
                }
            },

            /**
             * Actualizar meta theme-color
             */
            updateMetaThemeColor: function() {
                var theme = this.themes[this.resolvedThemeId];
                if (!theme || !theme.colors) return;

                var metaThemeColor = document.querySelector('meta[name="theme-color"]');
                if (!metaThemeColor) {
                    metaThemeColor = document.createElement('meta');
                    metaThemeColor.name = 'theme-color';
                    document.head.appendChild(metaThemeColor);
                }
                metaThemeColor.content = theme.colors.bgPrimary;
            },

            /**
             * Cambiar tema
             * @param {string} themeId - ID del tema
             */
            setTheme: function(themeId) {
                if (!this.themes[themeId]) {
                    console.warn('[VBP Themes] Tema no encontrado:', themeId);
                    return false;
                }

                this.currentThemeId = themeId;
                this.resolveTheme();
                this.applyTheme();
                this.saveTheme();

                // Notificación toast
                var toastStore = Alpine.store('vbpToast');
                if (toastStore && typeof toastStore.show === 'function') {
                    var themeName = this.themes[themeId].name;
                    toastStore.show('Tema: ' + themeName, 'info', 1500);
                }

                return true;
            },

            /**
             * Cambiar entre temas (toggle dark/light)
             */
            toggle: function() {
                var newThemeId = this.resolvedThemeId === 'dark' ? 'light' : 'dark';
                this.setTheme(newThemeId);
            },

            /**
             * Ciclar entre todos los temas predefinidos
             */
            cycle: function() {
                var themeOrder = ['light', 'dark', 'midnight', 'forest', 'system'];
                var currentIndex = themeOrder.indexOf(this.currentThemeId);
                var nextIndex = (currentIndex + 1) % themeOrder.length;
                this.setTheme(themeOrder[nextIndex]);
            },

            /**
             * Obtener tema actual
             * @returns {Object}
             */
            getCurrentTheme: function() {
                return this.themes[this.resolvedThemeId] || this.themes.dark;
            },

            /**
             * Verificar si es modo oscuro
             * @returns {boolean}
             */
            isDark: function() {
                var darkThemeIds = ['dark', 'midnight', 'forest', 'high-contrast'];
                return darkThemeIds.indexOf(this.resolvedThemeId) > -1;
            },

            /**
             * Obtener temas por categoría
             * @param {string} categoryId - ID de categoría
             * @returns {Array}
             */
            getThemesByCategory: function(categoryId) {
                var self = this;
                return Object.keys(this.themes)
                    .filter(function(themeId) {
                        return self.themes[themeId].category === categoryId;
                    })
                    .map(function(themeId) {
                        return self.themes[themeId];
                    });
            },

            /**
             * Obtener todas las categorías con sus temas
             * @returns {Array}
             */
            getCategoriesWithThemes: function() {
                var self = this;
                var categoriesArray = Object.keys(THEME_CATEGORIES).map(function(categoryId) {
                    return {
                        id: categoryId,
                        name: THEME_CATEGORIES[categoryId].name,
                        description: THEME_CATEGORIES[categoryId].description,
                        themes: self.getThemesByCategory(categoryId)
                    };
                });
                return categoriesArray.filter(function(category) {
                    return category.themes.length > 0;
                });
            },

            /**
             * Abrir panel de temas
             */
            openPanel: function() {
                this.isPanelOpen = true;
            },

            /**
             * Cerrar panel de temas
             */
            closePanel: function() {
                this.isPanelOpen = false;
            },

            /**
             * Toggle panel de temas
             */
            togglePanel: function() {
                this.isPanelOpen = !this.isPanelOpen;
            },

            // ============================================
            // Temas personalizados
            // ============================================

            /**
             * Crear nuevo tema personalizado
             * @param {Object} config - Configuración del tema
             * @returns {Object|null}
             */
            createCustomTheme: function(config) {
                if (!config.name) {
                    console.error('[VBP Themes] El tema necesita un nombre');
                    return null;
                }

                var themeId = 'custom_' + Date.now();
                var newTheme = {
                    id: themeId,
                    name: config.name,
                    description: config.description || 'Tema personalizado',
                    icon: config.icon || 'palette',
                    category: 'custom',
                    colors: Object.assign({}, this.themes.dark.colors, config.colors || {})
                };

                // Registrar tema
                this.themes[themeId] = newTheme;
                this.customThemes[themeId] = newTheme;

                // Guardar
                this.saveCustomThemes();

                return newTheme;
            },

            /**
             * Editar tema personalizado
             * @param {string} themeId - ID del tema
             * @param {Object} updates - Actualizaciones
             * @returns {boolean}
             */
            updateCustomTheme: function(themeId, updates) {
                if (!this.customThemes[themeId]) {
                    console.error('[VBP Themes] Tema personalizado no encontrado:', themeId);
                    return false;
                }

                // Actualizar
                Object.assign(this.customThemes[themeId], updates);
                Object.assign(this.themes[themeId], updates);

                // Guardar
                this.saveCustomThemes();

                // Re-aplicar si es el tema actual
                if (this.currentThemeId === themeId) {
                    this.applyTheme();
                }

                return true;
            },

            /**
             * Eliminar tema personalizado
             * @param {string} themeId - ID del tema
             * @returns {boolean}
             */
            deleteCustomTheme: function(themeId) {
                if (!this.customThemes[themeId]) {
                    console.error('[VBP Themes] Tema personalizado no encontrado:', themeId);
                    return false;
                }

                // Si es el tema actual, cambiar a dark
                if (this.currentThemeId === themeId) {
                    this.setTheme('dark');
                }

                // Eliminar
                delete this.customThemes[themeId];
                delete this.themes[themeId];

                // Guardar
                this.saveCustomThemes();

                return true;
            },

            /**
             * Guardar temas personalizados
             */
            saveCustomThemes: function() {
                try {
                    localStorage.setItem(CUSTOM_THEMES_KEY, JSON.stringify(this.customThemes));
                } catch (saveError) {
                    console.warn('[VBP Themes] Error guardando temas personalizados:', saveError);
                }
            },

            /**
             * Duplicar tema como personalizado
             * @param {string} themeId - ID del tema a duplicar
             * @param {string} newName - Nombre del nuevo tema
             * @returns {Object|null}
             */
            duplicateTheme: function(themeId, newName) {
                var sourceTheme = this.themes[themeId];
                if (!sourceTheme) {
                    console.error('[VBP Themes] Tema fuente no encontrado:', themeId);
                    return null;
                }

                return this.createCustomTheme({
                    name: newName || sourceTheme.name + ' (copia)',
                    description: 'Basado en ' + sourceTheme.name,
                    colors: Object.assign({}, sourceTheme.colors)
                });
            },

            // ============================================
            // Importación / Exportación
            // ============================================

            /**
             * Exportar tema a JSON
             * @param {string} themeId - ID del tema
             * @returns {string}
             */
            exportTheme: function(themeId) {
                var theme = this.themes[themeId];
                if (!theme) {
                    console.error('[VBP Themes] Tema no encontrado:', themeId);
                    return null;
                }

                var exportData = {
                    version: '1.0',
                    theme: {
                        name: theme.name,
                        description: theme.description,
                        icon: theme.icon,
                        colors: theme.colors
                    }
                };

                return JSON.stringify(exportData, null, 2);
            },

            /**
             * Importar tema desde JSON
             * @param {string} jsonString - JSON del tema
             * @returns {Object|null}
             */
            importTheme: function(jsonString) {
                try {
                    var importData = JSON.parse(jsonString);

                    if (!importData.theme || !importData.theme.name) {
                        throw new Error('Formato de tema inválido');
                    }

                    return this.createCustomTheme(importData.theme);
                } catch (importError) {
                    console.error('[VBP Themes] Error importando tema:', importError);
                    return null;
                }
            },

            /**
             * Descargar tema como archivo JSON
             * @param {string} themeId - ID del tema
             */
            downloadTheme: function(themeId) {
                var jsonContent = this.exportTheme(themeId);
                if (!jsonContent) return;

                var theme = this.themes[themeId];
                var blob = new Blob([jsonContent], { type: 'application/json' });
                var url = URL.createObjectURL(blob);
                var downloadLink = document.createElement('a');
                downloadLink.href = url;
                downloadLink.download = 'vbp-theme-' + theme.name.toLowerCase().replace(/\s+/g, '-') + '.json';
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
                URL.revokeObjectURL(url);
            },

            // ============================================
            // Editor de tema
            // ============================================

            /**
             * Abrir editor de tema
             * @param {string} themeId - ID del tema (null para nuevo)
             */
            openEditor: function(themeId) {
                if (themeId && this.customThemes[themeId]) {
                    this.editingTheme = Object.assign({}, this.customThemes[themeId], {
                        colors: Object.assign({}, this.customThemes[themeId].colors)
                    });
                } else {
                    // Nuevo tema basado en el tema actual
                    var baseTheme = this.getCurrentTheme();
                    this.editingTheme = {
                        id: null,
                        name: 'Mi Tema',
                        description: '',
                        icon: 'palette',
                        colors: Object.assign({}, baseTheme.colors)
                    };
                }
                this.isEditorOpen = true;
            },

            /**
             * Cerrar editor de tema
             */
            closeEditor: function() {
                this.isEditorOpen = false;
                this.editingTheme = null;
            },

            /**
             * Guardar tema desde editor
             */
            saveEditingTheme: function() {
                if (!this.editingTheme) return;

                if (this.editingTheme.id && this.customThemes[this.editingTheme.id]) {
                    // Actualizar existente
                    this.updateCustomTheme(this.editingTheme.id, this.editingTheme);
                } else {
                    // Crear nuevo
                    var newTheme = this.createCustomTheme(this.editingTheme);
                    if (newTheme) {
                        this.setTheme(newTheme.id);
                    }
                }

                this.closeEditor();
            },

            /**
             * Preview de color en el editor
             * @param {string} colorKey - Clave del color
             * @param {string} value - Valor del color
             */
            previewColor: function(colorKey, value) {
                if (!this.editingTheme) return;
                this.editingTheme.colors[colorKey] = value;

                // Preview en vivo aplicando temporalmente
                var root = document.documentElement;
                var cssVarName = '--vbp-' + colorKey.replace(/([A-Z])/g, '-$1').toLowerCase();
                root.style.setProperty(cssVarName, value);
            },

            /**
             * Resetear preview
             */
            resetPreview: function() {
                // Quitar estilos inline y re-aplicar tema actual
                document.documentElement.removeAttribute('style');
                this.applyTheme();
            },

            /**
             * Obtener icono del tema
             * @param {string} themeId - ID del tema
             * @returns {string}
             */
            getThemeIcon: function(themeId) {
                var theme = this.themes[themeId];
                if (!theme) return 'palette';
                return theme.icon || 'palette';
            }
        });
    });

    /**
     * Inicialización temprana para evitar flash
     */
    (function earlyThemeInit() {
        try {
            var savedThemeId = localStorage.getItem(STORAGE_KEY);
            var themeToApply = 'dark';

            if (savedThemeId && EDITOR_THEMES[savedThemeId]) {
                if (savedThemeId === 'system') {
                    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    themeToApply = prefersDark ? 'dark' : 'light';
                } else {
                    themeToApply = savedThemeId;
                }
            } else if (savedThemeId && savedThemeId.startsWith('custom_')) {
                // Es un tema personalizado, cargar después
                themeToApply = 'dark';
            }

            document.documentElement.setAttribute('data-vbp-theme', themeToApply);
        } catch (initError) {
            document.documentElement.setAttribute('data-vbp-theme', 'dark');
        }
    })();

    /**
     * Inicializar store después de Alpine
     */
    document.addEventListener('alpine:initialized', function() {
        var store = Alpine.store('vbpEditorThemes');
        if (store && typeof store.init === 'function') {
            store.init();
        }
    });

    /**
     * Atajo de teclado para cambiar tema (Ctrl+Shift+T)
     */
    document.addEventListener('keydown', function(keyEvent) {
        // Ctrl+Shift+T (o Cmd+Shift+T en Mac)
        if ((keyEvent.ctrlKey || keyEvent.metaKey) && keyEvent.shiftKey && keyEvent.key.toLowerCase() === 't') {
            keyEvent.preventDefault();
            var store = Alpine.store('vbpEditorThemes');
            if (store && typeof store.toggle === 'function') {
                store.toggle();
            }
        }

        // Alt+T para abrir panel de temas
        if (keyEvent.altKey && keyEvent.key.toLowerCase() === 't' && !keyEvent.ctrlKey && !keyEvent.metaKey) {
            keyEvent.preventDefault();
            var themeStore = Alpine.store('vbpEditorThemes');
            if (themeStore && typeof themeStore.togglePanel === 'function') {
                themeStore.togglePanel();
            }
        }
    });

    /**
     * Exponer API global para temas
     */
    window.VBP = window.VBP || {};

    VBP.themes = {
        /**
         * Obtener tema actual
         * @returns {string}
         */
        getCurrent: function() {
            var store = typeof Alpine !== 'undefined' ? Alpine.store('vbpEditorThemes') : null;
            return store ? store.currentThemeId : 'dark';
        },

        /**
         * Establecer tema
         * @param {string} themeId - ID del tema
         */
        set: function(themeId) {
            var store = typeof Alpine !== 'undefined' ? Alpine.store('vbpEditorThemes') : null;
            if (store && typeof store.setTheme === 'function') {
                store.setTheme(themeId);
            }
        },

        /**
         * Toggle dark/light
         */
        toggle: function() {
            var store = typeof Alpine !== 'undefined' ? Alpine.store('vbpEditorThemes') : null;
            if (store && typeof store.toggle === 'function') {
                store.toggle();
            }
        },

        /**
         * Verificar si es modo oscuro
         * @returns {boolean}
         */
        isDark: function() {
            var store = typeof Alpine !== 'undefined' ? Alpine.store('vbpEditorThemes') : null;
            return store ? store.isDark() : true;
        },

        /**
         * Obtener todos los temas
         * @returns {Object}
         */
        getAll: function() {
            var store = typeof Alpine !== 'undefined' ? Alpine.store('vbpEditorThemes') : null;
            return store ? store.themes : EDITOR_THEMES;
        },

        /**
         * Crear tema personalizado
         * @param {Object} config - Configuración
         * @returns {Object|null}
         */
        create: function(config) {
            var store = typeof Alpine !== 'undefined' ? Alpine.store('vbpEditorThemes') : null;
            if (store && typeof store.createCustomTheme === 'function') {
                return store.createCustomTheme(config);
            }
            return null;
        },

        /**
         * Importar tema
         * @param {string} json - JSON del tema
         * @returns {Object|null}
         */
        import: function(json) {
            var store = typeof Alpine !== 'undefined' ? Alpine.store('vbpEditorThemes') : null;
            if (store && typeof store.importTheme === 'function') {
                return store.importTheme(json);
            }
            return null;
        },

        /**
         * Exportar tema
         * @param {string} themeId - ID del tema
         * @returns {string|null}
         */
        export: function(themeId) {
            var store = typeof Alpine !== 'undefined' ? Alpine.store('vbpEditorThemes') : null;
            if (store && typeof store.exportTheme === 'function') {
                return store.exportTheme(themeId);
            }
            return null;
        }
    };

})();
