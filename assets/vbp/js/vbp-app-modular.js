/**
 * Visual Builder Pro - App Modular Loader
 * Carga y combina los módulos de la aplicación
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

(function() {
    'use strict';

    // Fallback de vbpLog si no está definido
    if (!window.vbpLog) {
        window.vbpLog = {
            log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
            warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
            error: function() { console.error.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); }
        };
    }

    /**
     * Lista de módulos disponibles
     */
    var APP_MODULES = [
        { name: 'vbp-app-split-screen', feature: null },
        { name: 'vbp-app-page-settings', feature: null },
        { name: 'vbp-app-templates', feature: null },
        { name: 'vbp-app-version-history', feature: null },
        { name: 'vbp-app-unsplash', feature: null },
        { name: 'vbp-app-revisions', feature: null },
        { name: 'vbp-app-import-export', feature: null },
        { name: 'vbp-app-commands', feature: null },
        { name: 'vbp-app-collaboration', feature: 'collaboration' },
        { name: 'vbp-app-audit-log', feature: 'audit_log' },
        { name: 'vbp-app-workflows', feature: 'workflows' },
        { name: 'vbp-app-mobile', feature: null },
        { name: 'vbp-app-multisite', feature: 'multisite' }
    ];

    function getEnabledModules() {
        var config = typeof VBP_Config !== 'undefined' ? VBP_Config : {};
        var features = config.features || {};

        return APP_MODULES.filter(function(module) {
            return !module.feature || !!features[module.feature];
        });
    }

    /**
     * Cargar módulo dinámicamente
     */
    function loadModule(moduleName) {
        return new Promise(function(resolve, reject) {
            // Si ya está cargado, resolver inmediatamente
            var globalName = 'VBPApp' + moduleName.split('-').slice(2).map(function(part) {
                return part.charAt(0).toUpperCase() + part.slice(1);
            }).join('');

            if (window[globalName]) {
                resolve(window[globalName]);
                return;
            }

            // Cargar script
            var config = typeof VBP_Config !== 'undefined' ? VBP_Config : {};
            var baseUrl = config.assetsUrl || '/wp-content/plugins/flavor-platform/assets/vbp/';
            var script = document.createElement('script');
            script.src = baseUrl + 'js/modules/' + moduleName + '.js';
            script.async = true;

            script.onload = function() {
                if (window[globalName]) {
                    resolve(window[globalName]);
                } else {
                    vbpLog.warn('Módulo cargado pero no encontrado:', globalName);
                    resolve({});
                }
            };

            script.onerror = function() {
                vbpLog.warn('Error cargando módulo:', moduleName);
                resolve({}); // Resolver con objeto vacío para no bloquear
            };

            document.head.appendChild(script);
        });
    }

    /**
     * Cargar todos los módulos
     */
    function loadAllModules() {
        return Promise.all(getEnabledModules().map(function(module) {
            return loadModule(module.name);
        }));
    }

    /**
     * Mezclar módulos en el objeto principal
     */
    function mixinModules(target, modules) {
        modules.forEach(function(module) {
            if (module && typeof module === 'object') {
                Object.keys(module).forEach(function(key) {
                    // No sobrescribir propiedades existentes a menos que sean funciones stub
                    if (typeof target[key] === 'undefined' ||
                        (typeof target[key] === 'function' && target[key].toString().includes('// stub'))) {
                        target[key] = module[key];
                    }
                });
            }
        });
        return target;
    }

    /**
     * Inicializar sistema modular
     */
    window.VBPAppModular = {
        modules: {},
        loaded: false,

        /**
         * Cargar e inicializar todos los módulos
         */
        init: function() {
            var self = this;
            return loadAllModules().then(function(loadedModules) {
                // Mapear módulos cargados
                var enabledModules = getEnabledModules();
                enabledModules.forEach(function(module, index) {
                    self.modules[module.name] = loadedModules[index] || {};
                });
                self.loaded = true;
                vbpLog.log('Módulos de app cargados:', Object.keys(self.modules).length);
                return self.modules;
            });
        },

        /**
         * Obtener métodos combinados de todos los módulos
         */
        getMixins: function() {
            var combined = {};
            var self = this;
            Object.keys(this.modules).forEach(function(name) {
                Object.assign(combined, self.modules[name]);
            });
            return combined;
        },

        /**
         * Aplicar mixins a un objeto Alpine
         */
        applyTo: function(target) {
            return mixinModules(target, Object.values(this.modules));
        }
    };

    /**
     * Extender vbpApp con módulos
     * Esta función se llama desde vbp-app.js para mezclar los módulos
     * Usa Object.defineProperties para copiar getters correctamente
     */
    window.extendVBPApp = function(appObject) {
        /**
         * Mezcla propiedades de source a target, incluyendo getters/setters
         * Solo copia propiedades que no existen en target (no sobrescribe)
         */
        function safeExtend(target, source) {
            if (!source || typeof source !== 'object') return;

            var descriptors = Object.getOwnPropertyDescriptors(source);
            Object.keys(descriptors).forEach(function(key) {
                // No sobrescribir propiedades existentes
                if (!(key in target)) {
                    try {
                        Object.defineProperty(target, key, descriptors[key]);
                    } catch (e) {
                        // Si falla, intentar asignación simple
                        try {
                            target[key] = source[key];
                        } catch (e2) {
                            vbpLog.warn('No se pudo extender propiedad:', key);
                        }
                    }
                }
            });
        }

        // Extender con cada módulo si está disponible
        if (window.VBPAppSplitScreen) {
            safeExtend(appObject, window.VBPAppSplitScreen);
        }
        if (window.VBPAppPageSettings) {
            safeExtend(appObject, window.VBPAppPageSettings);
        }
        if (window.VBPAppTemplates) {
            safeExtend(appObject, window.VBPAppTemplates);
        }
        if (window.VBPAppVersionHistory) {
            safeExtend(appObject, window.VBPAppVersionHistory);
        }
        if (window.VBPAppUnsplash) {
            safeExtend(appObject, window.VBPAppUnsplash);
        }
        if (window.VBPAppRevisions) {
            safeExtend(appObject, window.VBPAppRevisions);
        }
        if (window.VBPAppImportExport) {
            safeExtend(appObject, window.VBPAppImportExport);
        }
        if (window.VBPAppCommands) {
            safeExtend(appObject, window.VBPAppCommands);
        }
        if (window.VBPAppDesignTokens) {
            safeExtend(appObject, window.VBPAppDesignTokens);
        }
        if (window.VBPAppCollaboration) {
            safeExtend(appObject, window.VBPAppCollaboration);
        }
        if (window.VBPAppAuditLog) {
            safeExtend(appObject, window.VBPAppAuditLog);
        }
        if (window.VBPAppWorkflows) {
            safeExtend(appObject, window.VBPAppWorkflows);
        }
        if (window.VBPAppMobile) {
            safeExtend(appObject, window.VBPAppMobile);
        }
        if (window.VBPAppMultisite) {
            safeExtend(appObject, window.VBPAppMultisite);
        }

        return appObject;
    };

    // Auto-inicializar si el documento está listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.VBPAppModular.init();
        });
    } else {
        window.VBPAppModular.init();
    }

})();
