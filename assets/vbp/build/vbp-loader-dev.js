/**
 * VBP Lazy Loader (Development Version)
 *
 * Carga bundles y archivos individuales bajo demanda.
 * Esta version carga archivos individuales para facilitar debugging.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 3.5.0
 */
(function() {
    'use strict';

    // Configuracion inyectada desde PHP
    const config = window.VBP_LazyConfig || {
        mode: 'individual',
        baseUrl: '/wp-content/plugins/flavor-platform/assets/vbp/',
        bundles: { js: {}, css: {} },
        triggers: {},
        featureFlags: {},
        loadedBundles: []
    };

    // Estado interno
    const loadedBundles = new Set(config.loadedBundles || []);
    const loadingPromises = new Map();
    const eventListeners = new Map();

    /**
     * Log de desarrollo
     */
    function devLog(message, data) {
        if (typeof console !== 'undefined' && console.debug) {
            console.debug('[VBP Loader]', message, data || '');
        }
    }

    /**
     * Carga un script JavaScript
     *
     * @param {string} url URL del script
     * @returns {Promise}
     */
    function loadScript(url) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = url;
            script.async = true;

            script.onload = () => {
                devLog('Script cargado:', url);
                resolve();
            };

            script.onerror = () => {
                console.error('[VBP Loader] Error cargando script:', url);
                reject(new Error('Failed to load: ' + url));
            };

            document.head.appendChild(script);
        });
    }

    /**
     * Carga un archivo CSS
     *
     * @param {string} url URL del CSS
     * @returns {Promise}
     */
    function loadStylesheet(url) {
        return new Promise((resolve) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = url;

            link.onload = () => {
                devLog('CSS cargado:', url);
                resolve();
            };

            link.onerror = () => {
                console.warn('[VBP Loader] Error cargando CSS (continuando):', url);
                resolve(); // No bloquear por CSS faltante
            };

            document.head.appendChild(link);
        });
    }

    /**
     * Obtiene la URL de un archivo, usando version minificada si disponible
     *
     * @param {string} archivo Ruta relativa del archivo
     * @param {string} tipo 'js' o 'css'
     * @returns {string}
     */
    function getFileUrl(archivo, tipo) {
        // En desarrollo, usar archivos sin minificar
        const useMinified = config.mode === 'bundled';
        let rutaArchivo = archivo;

        if (useMinified) {
            rutaArchivo = archivo.replace('.' + tipo, '.min.' + tipo);
        }

        return config.baseUrl + rutaArchivo;
    }

    /**
     * Carga un bundle de JavaScript
     *
     * @param {string} bundleName Nombre del bundle
     * @returns {Promise}
     */
    async function loadJsBundle(bundleName) {
        const claveBundleLoaded = 'js:' + bundleName;

        if (loadedBundles.has(claveBundleLoaded)) {
            devLog('Bundle ya cargado:', bundleName);
            return Promise.resolve();
        }

        if (loadingPromises.has(claveBundleLoaded)) {
            return loadingPromises.get(claveBundleLoaded);
        }

        const bundleInfo = config.bundles.js[bundleName];

        if (!bundleInfo) {
            console.warn('[VBP Loader] Bundle no encontrado:', bundleName);
            return Promise.reject(new Error('Bundle not found: ' + bundleName));
        }

        devLog('Cargando bundle JS:', bundleName);

        // Cargar dependencias primero
        const loadDeps = (bundleInfo.dependencies || []).map(dep => loadJsBundle(dep));

        const loadPromise = Promise.all(loadDeps).then(async () => {
            if (config.mode === 'bundled' && bundleInfo.file) {
                // Cargar bundle compilado
                await loadScript(config.baseUrl + bundleInfo.file);
            } else {
                // Cargar archivos individuales
                const files = bundleInfo.files || [];
                for (const archivo of files) {
                    const url = getFileUrl(archivo, 'js');
                    await loadScript(url);
                }
            }

            loadedBundles.add(claveBundleLoaded);
            loadingPromises.delete(claveBundleLoaded);
            dispatchEvent('bundle:loaded', { type: 'js', name: bundleName });
        }).catch(error => {
            loadingPromises.delete(claveBundleLoaded);
            throw error;
        });

        loadingPromises.set(claveBundleLoaded, loadPromise);
        return loadPromise;
    }

    /**
     * Carga un bundle de CSS
     *
     * @param {string} bundleName Nombre del bundle
     * @returns {Promise}
     */
    async function loadCssBundle(bundleName) {
        const claveBundleLoaded = 'css:' + bundleName;

        if (loadedBundles.has(claveBundleLoaded)) {
            return Promise.resolve();
        }

        const bundleInfo = config.bundles.css[bundleName];

        if (!bundleInfo) {
            console.warn('[VBP Loader] CSS Bundle no encontrado:', bundleName);
            return Promise.resolve(); // No bloquear por CSS faltante
        }

        devLog('Cargando bundle CSS:', bundleName);

        // Cargar dependencias CSS
        const loadDeps = (bundleInfo.dependencies || []).map(dep => loadCssBundle(dep));
        await Promise.all(loadDeps);

        if (config.mode === 'bundled' && bundleInfo.file) {
            // Cargar bundle compilado
            await loadStylesheet(config.baseUrl + bundleInfo.file);
        } else {
            // Cargar archivos individuales en paralelo
            const files = bundleInfo.files || [];
            const loadPromises = files.map(archivo => {
                const url = getFileUrl(archivo, 'css');
                return loadStylesheet(url);
            });
            await Promise.all(loadPromises);
        }

        loadedBundles.add(claveBundleLoaded);
        dispatchEvent('bundle:loaded', { type: 'css', name: bundleName });
    }

    /**
     * Carga bundles asociados a un trigger
     *
     * @param {string} triggerName Nombre del trigger
     * @returns {Promise}
     */
    async function loadByTrigger(triggerName) {
        const bundles = config.triggers[triggerName] || [];
        devLog('Trigger activado:', triggerName, bundles);

        const promises = [];

        for (const bundleName of bundles) {
            // Cargar tanto JS como CSS
            if (config.bundles.js[bundleName]) {
                promises.push(loadJsBundle(bundleName));
            }
            if (config.bundles.css[bundleName]) {
                promises.push(loadCssBundle(bundleName));
            }
        }

        return Promise.all(promises);
    }

    /**
     * Carga bundles asociados a feature flags
     *
     * @param {string} flagName Nombre del feature flag
     * @returns {Promise}
     */
    async function loadByFeatureFlag(flagName) {
        const bundles = config.featureFlags[flagName] || [];
        devLog('Feature flag activada:', flagName, bundles);

        const promises = [];

        for (const bundleName of bundles) {
            if (config.bundles.js[bundleName]) {
                promises.push(loadJsBundle(bundleName));
            }
            if (config.bundles.css[bundleName]) {
                promises.push(loadCssBundle(bundleName));
            }
        }

        return Promise.all(promises);
    }

    /**
     * Precarga bundles criticos
     */
    function preloadCritical() {
        // En modo bundled, agregar hints de preload
        if (config.mode === 'bundled') {
            const criticalBundles = ['vbp-core', 'vbp-editor', 'vbp-keyboard'];

            criticalBundles.forEach(bundleName => {
                // Preload JS
                if (config.bundles.js[bundleName] && config.bundles.js[bundleName].file) {
                    const link = document.createElement('link');
                    link.rel = 'preload';
                    link.href = config.baseUrl + config.bundles.js[bundleName].file;
                    link.as = 'script';
                    document.head.appendChild(link);
                }

                // Preload CSS
                if (config.bundles.css[bundleName] && config.bundles.css[bundleName].file) {
                    const link = document.createElement('link');
                    link.rel = 'preload';
                    link.href = config.baseUrl + config.bundles.css[bundleName].file;
                    link.as = 'style';
                    document.head.appendChild(link);
                }
            });
        }

        devLog('Preload completado');
    }

    /**
     * Registra un listener de eventos
     *
     * @param {string} eventName Nombre del evento
     * @param {Function} callback Callback
     */
    function addEventListener(eventName, callback) {
        if (!eventListeners.has(eventName)) {
            eventListeners.set(eventName, []);
        }
        eventListeners.get(eventName).push(callback);
    }

    /**
     * Remueve un listener de eventos
     *
     * @param {string} eventName Nombre del evento
     * @param {Function} callback Callback
     */
    function removeEventListener(eventName, callback) {
        if (eventListeners.has(eventName)) {
            const listeners = eventListeners.get(eventName);
            const index = listeners.indexOf(callback);
            if (index > -1) {
                listeners.splice(index, 1);
            }
        }
    }

    /**
     * Dispara un evento
     *
     * @param {string} eventName Nombre del evento
     * @param {object} data Datos del evento
     */
    function dispatchEvent(eventName, data) {
        if (eventListeners.has(eventName)) {
            eventListeners.get(eventName).forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error('[VBP Loader] Error en listener:', error);
                }
            });
        }

        // Tambien disparar evento DOM
        const customEvent = new CustomEvent('vbp:' + eventName, { detail: data });
        document.dispatchEvent(customEvent);
    }

    /**
     * Obtiene estadisticas del loader
     *
     * @returns {object}
     */
    function getStats() {
        return {
            mode: config.mode,
            loadedBundles: Array.from(loadedBundles),
            pendingLoads: loadingPromises.size,
            availableBundles: {
                js: Object.keys(config.bundles.js),
                css: Object.keys(config.bundles.css)
            },
            triggers: Object.keys(config.triggers),
            featureFlags: Object.keys(config.featureFlags)
        };
    }

    /**
     * Verifica si un bundle esta cargado
     *
     * @param {string} type 'js' o 'css'
     * @param {string} name Nombre del bundle
     * @returns {boolean}
     */
    function isLoaded(type, name) {
        return loadedBundles.has(type + ':' + name);
    }

    /**
     * Carga todos los bundles de una feature flag si esta activa
     */
    function loadActiveFeatures() {
        // Revisar VBP_Config.features si existe
        const features = window.VBP_Config?.features || {};

        Object.entries(features).forEach(([flagName, isActive]) => {
            if (isActive && config.featureFlags[flagName]) {
                loadByFeatureFlag(flagName).catch(error => {
                    console.warn('[VBP Loader] Error cargando feature:', flagName, error);
                });
            }
        });
    }

    // Exponer API global
    window.VBPLoader = {
        // Carga de bundles
        loadJs: loadJsBundle,
        loadCss: loadCssBundle,
        loadByTrigger: loadByTrigger,
        loadByFeatureFlag: loadByFeatureFlag,

        // Preload
        preloadCritical: preloadCritical,

        // Estado
        isLoaded: isLoaded,
        getStats: getStats,

        // Eventos
        on: addEventListener,
        off: removeEventListener,

        // Configuracion
        config: config,
        mode: config.mode
    };

    // Inicializacion automatica
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            preloadCritical();
            loadActiveFeatures();
        });
    } else {
        preloadCritical();
        loadActiveFeatures();
    }

    devLog('VBP Loader inicializado', {
        mode: config.mode,
        bundles: getStats().availableBundles
    });

})();
