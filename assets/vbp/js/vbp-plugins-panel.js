/**
 * Visual Builder Pro - Plugins Panel
 *
 * Panel de gestión de plugins y extensiones en la interfaz del editor.
 *
 * @package Flavor_Platform
 * @since 2.3.0
 */

(function() {
    'use strict';

    /**
     * Store de Alpine para el panel de plugins
     */
    document.addEventListener('alpine:init', function() {
        Alpine.store('vbpPluginsPanel', {
            /**
             * Estado del panel
             */
            isOpen: false,

            /**
             * Tab activa: 'plugins', 'themes', 'marketplace'
             */
            activeTab: 'plugins',

            /**
             * Plugins cargados
             */
            plugins: {},

            /**
             * Estado de carga
             */
            isLoading: false,

            /**
             * Filtro de búsqueda
             */
            searchQuery: '',

            /**
             * Filtro de categoría
             */
            filterCategory: 'all',

            /**
             * Plugin seleccionado para detalles
             */
            selectedPlugin: null,

            /**
             * Categorías de plugins
             */
            categories: {
                all: { id: 'all', name: 'Todos', icon: 'apps' },
                blocks: { id: 'blocks', name: 'Bloques', icon: 'view_module' },
                design: { id: 'design', name: 'Diseño', icon: 'palette' },
                productivity: { id: 'productivity', name: 'Productividad', icon: 'flash_on' },
                integration: { id: 'integration', name: 'Integraciones', icon: 'link' },
                developer: { id: 'developer', name: 'Desarrollo', icon: 'code' }
            },

            /**
             * Inicialización
             */
            init: function() {
                this.loadPlugins();
            },

            /**
             * Abrir panel
             */
            open: function(tab) {
                this.activeTab = tab || 'plugins';
                this.isOpen = true;
                this.loadPlugins();
            },

            /**
             * Cerrar panel
             */
            close: function() {
                this.isOpen = false;
                this.selectedPlugin = null;
            },

            /**
             * Toggle panel
             */
            toggle: function() {
                if (this.isOpen) {
                    this.close();
                } else {
                    this.open();
                }
            },

            /**
             * Cambiar tab
             */
            setTab: function(tab) {
                this.activeTab = tab;
                this.selectedPlugin = null;
            },

            /**
             * Cargar plugins desde el servidor y API JS
             */
            loadPlugins: function() {
                var self = this;
                this.isLoading = true;

                // Obtener plugins de la API JS
                if (window.VBP && typeof window.VBP.getAllPlugins === 'function') {
                    this.plugins = window.VBP.getAllPlugins();
                }

                // También cargar desde el servidor
                if (typeof vbpData !== 'undefined' && vbpData.restUrl) {
                    fetch(vbpData.restUrl + 'plugins', {
                        method: 'GET',
                        headers: {
                            'X-WP-Nonce': vbpData.restNonce
                        },
                        credentials: 'same-origin'
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        if (data.plugins) {
                            // Merge plugins del servidor con los locales
                            Object.keys(data.plugins).forEach(function(pluginId) {
                                if (!self.plugins[pluginId]) {
                                    self.plugins[pluginId] = data.plugins[pluginId];
                                } else {
                                    // Actualizar estado de activación desde el servidor
                                    self.plugins[pluginId].isActive = data.active && data.active.indexOf(pluginId) > -1;
                                }
                            });
                        }
                        self.isLoading = false;
                    })
                    .catch(function(loadError) {
                        console.warn('[VBP Plugins Panel] Error cargando plugins:', loadError);
                        self.isLoading = false;
                    });
                } else {
                    this.isLoading = false;
                }
            },

            /**
             * Obtener plugins filtrados
             */
            getFilteredPlugins: function() {
                var self = this;
                var pluginsList = Object.values(this.plugins);

                // Filtrar por categoría
                if (this.filterCategory !== 'all') {
                    pluginsList = pluginsList.filter(function(plugin) {
                        return plugin.category === self.filterCategory;
                    });
                }

                // Filtrar por búsqueda
                if (this.searchQuery.trim()) {
                    var searchLower = this.searchQuery.toLowerCase();
                    pluginsList = pluginsList.filter(function(plugin) {
                        return (
                            plugin.name.toLowerCase().indexOf(searchLower) > -1 ||
                            (plugin.description && plugin.description.toLowerCase().indexOf(searchLower) > -1)
                        );
                    });
                }

                // Ordenar: activos primero, luego por nombre
                pluginsList.sort(function(pluginA, pluginB) {
                    if (pluginA.isActive && !pluginB.isActive) return -1;
                    if (!pluginA.isActive && pluginB.isActive) return 1;
                    return pluginA.name.localeCompare(pluginB.name);
                });

                return pluginsList;
            },

            /**
             * Activar plugin
             */
            activatePlugin: function(pluginId) {
                var self = this;

                if (window.VBP && typeof window.VBP.activatePlugin === 'function') {
                    var activationResult = window.VBP.activatePlugin(pluginId);
                    if (activationResult) {
                        if (this.plugins[pluginId]) {
                            this.plugins[pluginId].isActive = true;
                        }
                        this.showToast('Plugin activado', 'success');
                    } else {
                        this.showToast('Error al activar plugin', 'error');
                    }
                }
            },

            /**
             * Desactivar plugin
             */
            deactivatePlugin: function(pluginId) {
                if (window.VBP && typeof window.VBP.deactivatePlugin === 'function') {
                    var deactivationResult = window.VBP.deactivatePlugin(pluginId);
                    if (deactivationResult) {
                        if (this.plugins[pluginId]) {
                            this.plugins[pluginId].isActive = false;
                        }
                        this.showToast('Plugin desactivado', 'info');
                    } else {
                        this.showToast('Error al desactivar plugin', 'error');
                    }
                }
            },

            /**
             * Toggle plugin
             */
            togglePlugin: function(pluginId) {
                var plugin = this.plugins[pluginId];
                if (!plugin) return;

                if (plugin.isActive) {
                    this.deactivatePlugin(pluginId);
                } else {
                    this.activatePlugin(pluginId);
                }
            },

            /**
             * Ver detalles de un plugin
             */
            viewPluginDetails: function(pluginId) {
                this.selectedPlugin = this.plugins[pluginId] || null;
            },

            /**
             * Cerrar detalles
             */
            closeDetails: function() {
                this.selectedPlugin = null;
            },

            /**
             * Obtener plugins activos count
             */
            getActiveCount: function() {
                return Object.values(this.plugins).filter(function(plugin) {
                    return plugin.isActive;
                }).length;
            },

            /**
             * Mostrar toast
             */
            showToast: function(message, type) {
                var toastStore = Alpine.store('vbpToast');
                if (toastStore && typeof toastStore.show === 'function') {
                    toastStore.show(message, type || 'info', 2000);
                }
            },

            /**
             * Obtener icono de categoría
             */
            getCategoryIcon: function(categoryId) {
                var category = this.categories[categoryId];
                return category ? category.icon : 'extension';
            },

            /**
             * Obtener nombre de categoría
             */
            getCategoryName: function(categoryId) {
                var category = this.categories[categoryId];
                return category ? category.name : categoryId;
            }
        });
    });

    /**
     * Inicializar panel después de Alpine
     */
    document.addEventListener('alpine:initialized', function() {
        var store = Alpine.store('vbpPluginsPanel');
        if (store && typeof store.init === 'function') {
            store.init();
        }
    });

    /**
     * Registrar comando en la paleta de comandos
     */
    document.addEventListener('vbp:command-palette:ready', function() {
        if (window.VBP && typeof window.VBP.addCommand === 'function') {
            // Comando para abrir panel de plugins
            window.VBP.addCommand({
                id: 'open-plugins-panel',
                title: 'Abrir panel de plugins',
                icon: 'extension',
                category: 'editor',
                keywords: ['plugins', 'extensiones', 'addon'],
                action: function() {
                    var store = Alpine.store('vbpPluginsPanel');
                    if (store) {
                        store.open('plugins');
                    }
                }
            });

            // Comando para abrir panel de temas
            window.VBP.addCommand({
                id: 'open-themes-panel',
                title: 'Abrir panel de temas',
                icon: 'palette',
                category: 'editor',
                keywords: ['temas', 'themes', 'color', 'apariencia'],
                action: function() {
                    var store = Alpine.store('vbpEditorThemes');
                    if (store) {
                        store.openPanel();
                    }
                }
            });

            // Comando para cambiar tema
            window.VBP.addCommand({
                id: 'toggle-theme',
                title: 'Cambiar tema claro/oscuro',
                icon: 'dark_mode',
                category: 'editor',
                keywords: ['tema', 'dark', 'light', 'oscuro', 'claro'],
                shortcut: 'Ctrl+Shift+T',
                action: function() {
                    var store = Alpine.store('vbpEditorThemes');
                    if (store) {
                        store.toggle();
                    }
                }
            });
        }
    });

})();
