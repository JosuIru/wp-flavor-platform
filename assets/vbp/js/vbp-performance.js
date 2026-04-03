/**
 * Visual Builder Pro - Performance Utilities
 * Utilidades para mejorar el rendimiento del editor
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

// Fallback de vbpLog si no está definido
if (!window.vbpLog) {
    window.vbpLog = {
        log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); },
        error: function() { console.error.apply(console, ['[VBP]'].concat(Array.prototype.slice.call(arguments))); }
    };
}

window.VBPPerformance = (function() {
    'use strict';

    /**
     * Cache para memoización
     */
    var memoCache = new Map();
    var CACHE_MAX_SIZE = 100;

    /**
     * Debounce - Retrasa la ejecución hasta que pase el tiempo especificado
     * @param {Function} func - Función a ejecutar
     * @param {number} wait - Tiempo de espera en ms
     * @param {boolean} immediate - Ejecutar inmediatamente en el primer llamado
     * @returns {Function}
     */
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this;
            var args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    /**
     * Throttle - Limita la ejecución a una vez por período
     * @param {Function} func - Función a ejecutar
     * @param {number} limit - Tiempo mínimo entre ejecuciones en ms
     * @returns {Function}
     */
    function throttle(func, limit) {
        var lastCall = 0;
        var lastResult;
        return function() {
            var now = Date.now();
            if (now - lastCall >= limit) {
                lastCall = now;
                lastResult = func.apply(this, arguments);
            }
            return lastResult;
        };
    }

    /**
     * Memoize - Cache de resultados de funciones
     * @param {Function} func - Función a memorizar
     * @param {Function} keyResolver - Función para generar la clave del cache
     * @returns {Function}
     */
    function memoize(func, keyResolver) {
        var cache = new Map();

        var memoized = function() {
            var key = keyResolver ? keyResolver.apply(this, arguments) : arguments[0];

            if (cache.has(key)) {
                return cache.get(key);
            }

            var result = func.apply(this, arguments);

            // Limitar tamaño del cache
            if (cache.size >= CACHE_MAX_SIZE) {
                var firstKey = cache.keys().next().value;
                cache.delete(firstKey);
            }

            cache.set(key, result);
            return result;
        };

        memoized.cache = cache;
        memoized.clear = function() { cache.clear(); };

        return memoized;
    }

    /**
     * RequestAnimationFrame con throttle
     * @param {Function} callback - Función a ejecutar
     * @returns {Function}
     */
    function rafThrottle(callback) {
        var requestId = null;
        var lastArgs;

        var later = function(context) {
            return function() {
                requestId = null;
                callback.apply(context, lastArgs);
            };
        };

        var throttled = function() {
            lastArgs = arguments;
            if (requestId === null) {
                requestId = requestAnimationFrame(later(this));
            }
        };

        throttled.cancel = function() {
            if (requestId) {
                cancelAnimationFrame(requestId);
                requestId = null;
            }
        };

        return throttled;
    }

    /**
     * Batch DOM updates usando requestAnimationFrame
     * @param {Function} callback - Función con actualizaciones DOM
     */
    var batchUpdateQueue = [];
    var batchUpdateScheduled = false;

    function batchDOMUpdate(callback) {
        batchUpdateQueue.push(callback);

        if (!batchUpdateScheduled) {
            batchUpdateScheduled = true;
            requestAnimationFrame(function() {
                var queue = batchUpdateQueue.slice();
                batchUpdateQueue = [];
                batchUpdateScheduled = false;

                queue.forEach(function(cb) {
                    try {
                        cb();
                    } catch (e) {
                        vbpLog.error('Batch update error:', e);
                    }
                });
            });
        }
    }

    /**
     * Lazy load de elementos cuando entran en viewport
     * @param {Element} element - Elemento a observar
     * @param {Function} callback - Función cuando es visible
     * @param {Object} options - Opciones del IntersectionObserver
     */
    var lazyObserver = null;
    var lazyCallbacks = new WeakMap();

    function lazyLoad(element, callback, options) {
        if (!lazyObserver) {
            lazyObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var cb = lazyCallbacks.get(entry.target);
                        if (cb) {
                            cb(entry.target);
                            lazyObserver.unobserve(entry.target);
                            lazyCallbacks.delete(entry.target);
                        }
                    }
                });
            }, options || { rootMargin: '50px' });
        }

        lazyCallbacks.set(element, callback);
        lazyObserver.observe(element);
    }

    /**
     * Defer - Ejecuta función cuando el navegador está idle
     * @param {Function} callback - Función a ejecutar
     * @param {Object} options - Opciones para requestIdleCallback
     */
    function defer(callback, options) {
        if ('requestIdleCallback' in window) {
            return requestIdleCallback(callback, options || { timeout: 2000 });
        } else {
            return setTimeout(callback, 1);
        }
    }

    /**
     * Virtual scroll helper - Calcula qué elementos mostrar
     * @param {number} scrollTop - Posición del scroll
     * @param {number} containerHeight - Altura del contenedor
     * @param {number} itemHeight - Altura de cada item
     * @param {number} totalItems - Total de items
     * @param {number} buffer - Items extra a renderizar
     * @returns {Object} - { startIndex, endIndex, offsetY }
     */
    function getVisibleRange(scrollTop, containerHeight, itemHeight, totalItems, buffer) {
        buffer = buffer || 5;

        var startIndex = Math.max(0, Math.floor(scrollTop / itemHeight) - buffer);
        var visibleCount = Math.ceil(containerHeight / itemHeight) + (buffer * 2);
        var endIndex = Math.min(totalItems, startIndex + visibleCount);
        var offsetY = startIndex * itemHeight;

        return {
            startIndex: startIndex,
            endIndex: endIndex,
            offsetY: offsetY,
            visibleCount: visibleCount
        };
    }

    /**
     * Measure - Mide el tiempo de ejecución de una función
     * @param {string} name - Nombre para el log
     * @param {Function} func - Función a medir
     * @returns {*} - Resultado de la función
     */
    function measure(name, func) {
        var start = performance.now();
        var result = func();
        var end = performance.now();
        vbpLog.log('Performance ' + name + ': ' + (end - start).toFixed(2) + 'ms');
        return result;
    }

    /**
     * Shallow compare - Compara dos objetos superficialmente
     * @param {Object} obj1
     * @param {Object} obj2
     * @returns {boolean}
     */
    function shallowEqual(obj1, obj2) {
        if (obj1 === obj2) return true;
        if (!obj1 || !obj2) return false;

        var keys1 = Object.keys(obj1);
        var keys2 = Object.keys(obj2);

        if (keys1.length !== keys2.length) return false;

        for (var i = 0; i < keys1.length; i++) {
            if (obj1[keys1[i]] !== obj2[keys1[i]]) return false;
        }

        return true;
    }

    /**
     * Clone profundo optimizado
     * @param {*} obj - Objeto a clonar
     * @returns {*}
     */
    function deepClone(obj) {
        if (obj === null || typeof obj !== 'object') return obj;
        if (obj instanceof Date) return new Date(obj);
        if (obj instanceof Array) {
            return obj.map(function(item) { return deepClone(item); });
        }
        if (obj instanceof Object) {
            var copy = {};
            for (var key in obj) {
                if (obj.hasOwnProperty(key)) {
                    copy[key] = deepClone(obj[key]);
                }
            }
            return copy;
        }
        return obj;
    }

    // API pública
    return {
        debounce: debounce,
        throttle: throttle,
        memoize: memoize,
        rafThrottle: rafThrottle,
        batchDOMUpdate: batchDOMUpdate,
        lazyLoad: lazyLoad,
        defer: defer,
        getVisibleRange: getVisibleRange,
        measure: measure,
        shallowEqual: shallowEqual,
        deepClone: deepClone
    };
})();
