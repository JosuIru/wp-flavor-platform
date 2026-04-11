/**
 * Visual Builder Pro - Plugin API
 *
 * Sistema de extensiones JavaScript para VBP.
 * Permite a desarrolladores externos añadir bloques, paneles,
 * atajos de teclado y funcionalidades personalizadas.
 *
 * @package Flavor_Platform
 * @since 2.3.0
 *
 * @example
 * // Registrar un plugin simple
 * VBP.registerPlugin('mi-plugin', {
 *     name: 'Mi Plugin',
 *     version: '1.0.0',
 *     init: function() {
 *         console.log('Plugin inicializado');
 *     }
 * });
 *
 * @example
 * // Plugin con bloques personalizados
 * VBP.registerPlugin('iconos-custom', {
 *     name: 'Pack de Iconos',
 *     blocks: [{
 *         id: 'icono-social',
 *         name: 'Icono Social',
 *         icon: 'share',
 *         category: 'social',
 *         render: function(props) { ... }
 *     }],
 *     init: function() { ... }
 * });
 */

(function() {
    'use strict';

    /**
     * Namespace global de VBP
     */
    window.VBP = window.VBP || {};

    /**
     * Sistema de plugins de VBP
     */
    var PluginSystem = {
        /**
         * Plugins registrados
         */
        plugins: {},

        /**
         * Plugins activos
         */
        activePlugins: [],

        /**
         * Hooks registrados por plugins
         */
        hooks: {},

        /**
         * Estado de inicialización
         */
        initialized: false,

        /**
         * Cola de plugins pendientes de inicialización
         */
        pendingInitQueue: [],

        /**
         * Categorías de plugins
         */
        categories: {
            'blocks': 'Bloques',
            'design': 'Diseño',
            'productivity': 'Productividad',
            'integration': 'Integraciones',
            'developer': 'Desarrollo',
            'general': 'General'
        },

        /**
         * Hooks disponibles
         */
        availableHooks: [
            'vbp:before:render',
            'vbp:after:render',
            'vbp:before:save',
            'vbp:after:save',
            'vbp:block:registered',
            'vbp:inspector:panel',
            'vbp:toolbar:buttons',
            'vbp:context:menu',
            'vbp:keyboard:shortcut',
            'vbp:canvas:init',
            'vbp:element:selected',
            'vbp:element:deselected',
            'vbp:document:loaded',
            'vbp:document:saved',
            'vbp:plugin:activated',
            'vbp:plugin:deactivated',
            'vbp:theme:changed',
            'vbp:breakpoint:changed'
        ]
    };

    /**
     * Registra un plugin en VBP
     *
     * @param {string} pluginId - ID único del plugin
     * @param {Object} config - Configuración del plugin
     * @returns {boolean}
     */
    VBP.registerPlugin = function(pluginId, config) {
        // Validar ID
        if (!pluginId || typeof pluginId !== 'string') {
            console.error('[VBP Plugins] ID de plugin inválido');
            return false;
        }

        // Sanitizar ID
        pluginId = pluginId.toLowerCase().replace(/[^a-z0-9-_]/g, '-');

        // Verificar si ya existe
        if (PluginSystem.plugins[pluginId]) {
            console.warn('[VBP Plugins] Plugin ya registrado:', pluginId);
            return false;
        }

        // Configuración por defecto
        var defaultConfig = {
            id: pluginId,
            name: pluginId,
            description: '',
            version: '1.0.0',
            author: '',
            authorUri: '',
            icon: 'extension',
            category: 'general',
            blocks: [],
            panels: [],
            shortcuts: [],
            toolbar: [],
            contextMenu: [],
            hooks: {},
            styles: [],
            scripts: [],
            settings: {},
            dependencies: [],
            init: null,
            activate: null,
            deactivate: null,
            destroy: null
        };

        // Merge con configuración proporcionada
        var pluginConfig = Object.assign({}, defaultConfig, config);
        pluginConfig.id = pluginId;
        pluginConfig.isActive = false;
        pluginConfig.isInitialized = false;

        // Registrar plugin
        PluginSystem.plugins[pluginId] = pluginConfig;

        // Si el sistema ya está inicializado, inicializar el plugin
        if (PluginSystem.initialized) {
            initializePlugin(pluginId);
        } else {
            PluginSystem.pendingInitQueue.push(pluginId);
        }

        if (window.VBP_DEBUG) {
            console.log('[VBP Plugins] Plugin registrado:', pluginId, pluginConfig);
        }

        return true;
    };

    /**
     * Activa un plugin
     *
     * @param {string} pluginId - ID del plugin
     * @returns {boolean}
     */
    VBP.activatePlugin = function(pluginId) {
        var plugin = PluginSystem.plugins[pluginId];

        if (!plugin) {
            console.error('[VBP Plugins] Plugin no encontrado:', pluginId);
            return false;
        }

        if (plugin.isActive) {
            return true;
        }

        // Verificar dependencias
        if (plugin.dependencies && plugin.dependencies.length > 0) {
            for (var dependencyIndex = 0; dependencyIndex < plugin.dependencies.length; dependencyIndex++) {
                var dependencyPluginId = plugin.dependencies[dependencyIndex];
                if (!VBP.isPluginActive(dependencyPluginId)) {
                    console.error('[VBP Plugins] Dependencia no activa:', dependencyPluginId);
                    return false;
                }
            }
        }

        // Ejecutar callback de activación
        if (typeof plugin.activate === 'function') {
            try {
                plugin.activate();
            } catch (activationError) {
                console.error('[VBP Plugins] Error al activar plugin:', pluginId, activationError);
                return false;
            }
        }

        // Marcar como activo
        plugin.isActive = true;
        PluginSystem.activePlugins.push(pluginId);

        // Registrar bloques del plugin
        if (plugin.blocks && plugin.blocks.length > 0) {
            registerPluginBlocks(pluginId, plugin.blocks);
        }

        // Registrar atajos de teclado
        if (plugin.shortcuts && plugin.shortcuts.length > 0) {
            registerPluginShortcuts(pluginId, plugin.shortcuts);
        }

        // Registrar hooks
        if (plugin.hooks && Object.keys(plugin.hooks).length > 0) {
            registerPluginHooks(pluginId, plugin.hooks);
        }

        // Cargar estilos dinámicamente
        if (plugin.styles && plugin.styles.length > 0) {
            loadPluginStyles(pluginId, plugin.styles);
        }

        // Emitir evento
        VBP.emit('vbp:plugin:activated', { pluginId: pluginId, plugin: plugin });

        // Guardar estado en servidor
        savePluginState(pluginId, true);

        return true;
    };

    /**
     * Desactiva un plugin
     *
     * @param {string} pluginId - ID del plugin
     * @returns {boolean}
     */
    VBP.deactivatePlugin = function(pluginId) {
        var plugin = PluginSystem.plugins[pluginId];

        if (!plugin) {
            console.error('[VBP Plugins] Plugin no encontrado:', pluginId);
            return false;
        }

        if (!plugin.isActive) {
            return true;
        }

        // Verificar si otros plugins dependen de este
        var dependents = getDependentPlugins(pluginId);
        if (dependents.length > 0) {
            console.error('[VBP Plugins] Otros plugins dependen de este:', dependents);
            return false;
        }

        // Ejecutar callback de desactivación
        if (typeof plugin.deactivate === 'function') {
            try {
                plugin.deactivate();
            } catch (deactivationError) {
                console.error('[VBP Plugins] Error al desactivar plugin:', pluginId, deactivationError);
            }
        }

        // Desregistrar bloques
        if (plugin.blocks && plugin.blocks.length > 0) {
            unregisterPluginBlocks(pluginId);
        }

        // Desregistrar atajos
        if (plugin.shortcuts && plugin.shortcuts.length > 0) {
            unregisterPluginShortcuts(pluginId);
        }

        // Desregistrar hooks
        if (plugin.hooks) {
            unregisterPluginHooks(pluginId);
        }

        // Descargar estilos
        if (plugin.styles && plugin.styles.length > 0) {
            unloadPluginStyles(pluginId);
        }

        // Marcar como inactivo
        plugin.isActive = false;
        var pluginIndex = PluginSystem.activePlugins.indexOf(pluginId);
        if (pluginIndex > -1) {
            PluginSystem.activePlugins.splice(pluginIndex, 1);
        }

        // Emitir evento
        VBP.emit('vbp:plugin:deactivated', { pluginId: pluginId, plugin: plugin });

        // Guardar estado en servidor
        savePluginState(pluginId, false);

        return true;
    };

    /**
     * Verifica si un plugin está activo
     *
     * @param {string} pluginId - ID del plugin
     * @returns {boolean}
     */
    VBP.isPluginActive = function(pluginId) {
        var plugin = PluginSystem.plugins[pluginId];
        return plugin ? plugin.isActive : false;
    };

    /**
     * Obtiene un plugin por ID
     *
     * @param {string} pluginId - ID del plugin
     * @returns {Object|null}
     */
    VBP.getPlugin = function(pluginId) {
        return PluginSystem.plugins[pluginId] || null;
    };

    /**
     * Obtiene todos los plugins
     *
     * @returns {Object}
     */
    VBP.getAllPlugins = function() {
        return PluginSystem.plugins;
    };

    /**
     * Obtiene plugins activos
     *
     * @returns {Array}
     */
    VBP.getActivePlugins = function() {
        return PluginSystem.activePlugins.map(function(pluginId) {
            return PluginSystem.plugins[pluginId];
        }).filter(Boolean);
    };

    /**
     * Obtiene plugins por categoría
     *
     * @param {string} category - Categoría
     * @returns {Array}
     */
    VBP.getPluginsByCategory = function(category) {
        var result = [];
        Object.keys(PluginSystem.plugins).forEach(function(pluginId) {
            var plugin = PluginSystem.plugins[pluginId];
            if (plugin.category === category) {
                result.push(plugin);
            }
        });
        return result;
    };

    /**
     * Sistema de eventos para plugins
     */
    VBP.on = function(eventName, callback, pluginId) {
        if (!PluginSystem.hooks[eventName]) {
            PluginSystem.hooks[eventName] = [];
        }
        PluginSystem.hooks[eventName].push({
            callback: callback,
            pluginId: pluginId || 'core'
        });
    };

    VBP.off = function(eventName, callback) {
        if (!PluginSystem.hooks[eventName]) return;

        PluginSystem.hooks[eventName] = PluginSystem.hooks[eventName].filter(function(handler) {
            return handler.callback !== callback;
        });
    };

    VBP.emit = function(eventName, data) {
        if (!PluginSystem.hooks[eventName]) return;

        PluginSystem.hooks[eventName].forEach(function(handler) {
            try {
                handler.callback(data);
            } catch (eventError) {
                console.error('[VBP Plugins] Error en handler de evento:', eventName, eventError);
            }
        });

        // También emitir como evento DOM para compatibilidad
        document.dispatchEvent(new CustomEvent(eventName, { detail: data }));
    };

    /**
     * API para añadir bloques desde plugins
     */
    VBP.addBlock = function(blockConfig, pluginId) {
        pluginId = pluginId || 'custom';

        if (!blockConfig.id) {
            console.error('[VBP Plugins] Bloque sin ID');
            return false;
        }

        // Añadir al catálogo de bloques
        var store = typeof Alpine !== 'undefined' ? Alpine.store('vbpCatalog') : null;
        if (store && typeof store.addBlock === 'function') {
            store.addBlock(Object.assign({}, blockConfig, { pluginId: pluginId }));
            return true;
        }

        // Guardar para añadir después cuando el store esté listo
        if (!VBP._pendingBlocks) VBP._pendingBlocks = [];
        VBP._pendingBlocks.push(Object.assign({}, blockConfig, { pluginId: pluginId }));

        return true;
    };

    /**
     * API para añadir panel en el inspector
     */
    VBP.addInspectorPanel = function(panelConfig, pluginId) {
        pluginId = pluginId || 'custom';

        if (!panelConfig.id || !panelConfig.title) {
            console.error('[VBP Plugins] Panel sin ID o título');
            return false;
        }

        // Emitir evento para que el inspector lo recoja
        VBP.emit('vbp:inspector:panel', {
            panel: Object.assign({}, panelConfig, { pluginId: pluginId }),
            action: 'add'
        });

        return true;
    };

    /**
     * API para añadir botón a la toolbar
     */
    VBP.addToolbarButton = function(buttonConfig, pluginId) {
        pluginId = pluginId || 'custom';

        if (!buttonConfig.id) {
            console.error('[VBP Plugins] Botón sin ID');
            return false;
        }

        VBP.emit('vbp:toolbar:buttons', {
            button: Object.assign({}, buttonConfig, { pluginId: pluginId }),
            action: 'add'
        });

        return true;
    };

    /**
     * API para añadir atajo de teclado
     */
    VBP.addShortcut = function(shortcutConfig, pluginId) {
        pluginId = pluginId || 'custom';

        if (!shortcutConfig.keys || !shortcutConfig.action) {
            console.error('[VBP Plugins] Atajo sin teclas o acción');
            return false;
        }

        var store = typeof Alpine !== 'undefined' ? Alpine.store('vbpKeyboard') : null;
        if (store && typeof store.registerShortcut === 'function') {
            store.registerShortcut(
                shortcutConfig.keys,
                shortcutConfig.action,
                shortcutConfig.description || '',
                pluginId
            );
            return true;
        }

        // Guardar para registrar después
        if (!VBP._pendingShortcuts) VBP._pendingShortcuts = [];
        VBP._pendingShortcuts.push(Object.assign({}, shortcutConfig, { pluginId: pluginId }));

        return true;
    };

    /**
     * API para añadir item al menú contextual
     */
    VBP.addContextMenuItem = function(itemConfig, pluginId) {
        pluginId = pluginId || 'custom';

        if (!itemConfig.id || !itemConfig.label) {
            console.error('[VBP Plugins] Item de menú sin ID o label');
            return false;
        }

        VBP.emit('vbp:context:menu', {
            item: Object.assign({}, itemConfig, { pluginId: pluginId }),
            action: 'add'
        });

        return true;
    };

    /**
     * Obtiene la configuración de un plugin
     */
    VBP.getPluginSettings = function(pluginId) {
        var plugin = PluginSystem.plugins[pluginId];
        if (!plugin) return null;

        // Intentar cargar desde localStorage
        try {
            var stored = localStorage.getItem('vbp_plugin_settings_' + pluginId);
            if (stored) {
                return JSON.parse(stored);
            }
        } catch (storageError) {
            // Ignorar error de localStorage
        }

        return plugin.settings || {};
    };

    /**
     * Guarda la configuración de un plugin
     */
    VBP.setPluginSettings = function(pluginId, settings) {
        var plugin = PluginSystem.plugins[pluginId];
        if (!plugin) return false;

        plugin.settings = Object.assign({}, plugin.settings, settings);

        // Guardar en localStorage
        try {
            localStorage.setItem('vbp_plugin_settings_' + pluginId, JSON.stringify(plugin.settings));
        } catch (storageError) {
            console.warn('[VBP Plugins] No se pudo guardar configuración en localStorage');
        }

        return true;
    };

    // ============================================
    // Funciones internas
    // ============================================

    /**
     * Inicializa un plugin
     */
    function initializePlugin(pluginId) {
        var plugin = PluginSystem.plugins[pluginId];
        if (!plugin || plugin.isInitialized) return;

        // Ejecutar función init si existe
        if (typeof plugin.init === 'function') {
            try {
                plugin.init();
            } catch (initError) {
                console.error('[VBP Plugins] Error al inicializar plugin:', pluginId, initError);
            }
        }

        plugin.isInitialized = true;
    }

    /**
     * Registra bloques de un plugin
     */
    function registerPluginBlocks(pluginId, blocks) {
        blocks.forEach(function(block) {
            VBP.addBlock(block, pluginId);
        });
    }

    /**
     * Desregistra bloques de un plugin
     */
    function unregisterPluginBlocks(pluginId) {
        var store = typeof Alpine !== 'undefined' ? Alpine.store('vbpCatalog') : null;
        if (store && typeof store.removeBlocksByPlugin === 'function') {
            store.removeBlocksByPlugin(pluginId);
        }
    }

    /**
     * Registra atajos de teclado de un plugin
     */
    function registerPluginShortcuts(pluginId, shortcuts) {
        shortcuts.forEach(function(shortcut) {
            VBP.addShortcut(shortcut, pluginId);
        });
    }

    /**
     * Desregistra atajos de un plugin
     */
    function unregisterPluginShortcuts(pluginId) {
        var store = typeof Alpine !== 'undefined' ? Alpine.store('vbpKeyboard') : null;
        if (store && typeof store.unregisterByPlugin === 'function') {
            store.unregisterByPlugin(pluginId);
        }
    }

    /**
     * Registra hooks de un plugin
     */
    function registerPluginHooks(pluginId, hooks) {
        Object.keys(hooks).forEach(function(hookName) {
            VBP.on(hookName, hooks[hookName], pluginId);
        });
    }

    /**
     * Desregistra hooks de un plugin
     */
    function unregisterPluginHooks(pluginId) {
        Object.keys(PluginSystem.hooks).forEach(function(hookName) {
            PluginSystem.hooks[hookName] = PluginSystem.hooks[hookName].filter(function(handler) {
                return handler.pluginId !== pluginId;
            });
        });
    }

    /**
     * Carga estilos de un plugin
     */
    function loadPluginStyles(pluginId, styles) {
        styles.forEach(function(styleUrl, index) {
            var linkElement = document.createElement('link');
            linkElement.rel = 'stylesheet';
            linkElement.href = typeof styleUrl === 'string' ? styleUrl : styleUrl.src;
            linkElement.id = 'vbp-plugin-style-' + pluginId + '-' + index;
            document.head.appendChild(linkElement);
        });
    }

    /**
     * Descarga estilos de un plugin
     */
    function unloadPluginStyles(pluginId) {
        var styleElements = document.querySelectorAll('[id^="vbp-plugin-style-' + pluginId + '"]');
        styleElements.forEach(function(element) {
            element.remove();
        });
    }

    /**
     * Obtiene plugins que dependen de uno dado
     */
    function getDependentPlugins(pluginId) {
        var dependents = [];
        PluginSystem.activePlugins.forEach(function(activePluginId) {
            var plugin = PluginSystem.plugins[activePluginId];
            if (plugin && plugin.dependencies && plugin.dependencies.indexOf(pluginId) > -1) {
                dependents.push(activePluginId);
            }
        });
        return dependents;
    }

    /**
     * Guarda estado de plugin en servidor
     */
    function savePluginState(pluginId, isActive) {
        if (typeof vbpData === 'undefined' || !vbpData.ajaxUrl) return;

        var formData = new FormData();
        formData.append('action', isActive ? 'vbp_activate_plugin' : 'vbp_deactivate_plugin');
        formData.append('nonce', vbpData.nonce);
        formData.append('plugin_id', pluginId);

        fetch(vbpData.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).catch(function(fetchError) {
            console.warn('[VBP Plugins] No se pudo guardar estado en servidor:', fetchError);
        });
    }

    /**
     * Inicialización del sistema de plugins
     */
    function initPluginSystem() {
        if (PluginSystem.initialized) return;

        // Inicializar plugins pendientes
        PluginSystem.pendingInitQueue.forEach(function(pluginId) {
            initializePlugin(pluginId);
        });
        PluginSystem.pendingInitQueue = [];

        // Cargar estado de plugins activos desde servidor
        loadActivePluginsFromServer();

        // Procesar bloques pendientes
        if (VBP._pendingBlocks && VBP._pendingBlocks.length > 0) {
            VBP._pendingBlocks.forEach(function(block) {
                VBP.addBlock(block, block.pluginId);
            });
            VBP._pendingBlocks = [];
        }

        // Procesar atajos pendientes
        if (VBP._pendingShortcuts && VBP._pendingShortcuts.length > 0) {
            VBP._pendingShortcuts.forEach(function(shortcut) {
                VBP.addShortcut(shortcut, shortcut.pluginId);
            });
            VBP._pendingShortcuts = [];
        }

        PluginSystem.initialized = true;

        if (window.VBP_DEBUG) {
            console.log('[VBP Plugins] Sistema de plugins inicializado');
        }
    }

    /**
     * Carga plugins activos desde el servidor
     */
    function loadActivePluginsFromServer() {
        if (typeof vbpData === 'undefined' || !vbpData.restUrl) return;

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
            if (data.active && Array.isArray(data.active)) {
                data.active.forEach(function(pluginId) {
                    if (PluginSystem.plugins[pluginId] && !PluginSystem.plugins[pluginId].isActive) {
                        VBP.activatePlugin(pluginId);
                    }
                });
            }
        })
        .catch(function(fetchError) {
            if (window.VBP_DEBUG) {
                console.warn('[VBP Plugins] No se pudieron cargar plugins activos:', fetchError);
            }
        });
    }

    // Inicializar cuando Alpine esté listo
    document.addEventListener('alpine:initialized', function() {
        // Dar tiempo a que los stores se inicialicen
        setTimeout(initPluginSystem, 100);
    });

    // Fallback si Alpine tarda en inicializarse
    if (document.readyState === 'complete') {
        setTimeout(function() {
            if (!PluginSystem.initialized) {
                initPluginSystem();
            }
        }, 2000);
    } else {
        window.addEventListener('load', function() {
            setTimeout(function() {
                if (!PluginSystem.initialized) {
                    initPluginSystem();
                }
            }, 2000);
        });
    }

    // Exponer para debugging
    if (window.VBP_DEBUG) {
        VBP._pluginSystem = PluginSystem;
    }

})();
