/**
 * Visual Builder Pro - Sistema de Internacionalización (i18n)
 *
 * Proporciona funciones de traducción para JavaScript similares a las de WordPress PHP.
 * Las traducciones se cargan desde PHP vía wp_localize_script.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.3.0
 */

(function(window) {
    'use strict';

    /**
     * Sistema de internacionalización VBP
     */
    var VBPi18n = {
        /**
         * Almacén de strings traducidos
         * @type {Object}
         */
        strings: {},

        /**
         * Idioma actual
         * @type {string}
         */
        locale: 'es_ES',

        /**
         * Modo debug - muestra keys no encontradas
         * @type {boolean}
         */
        debug: false,

        /**
         * Cargar traducciones desde objeto PHP
         *
         * @param {Object} translations - Objeto con traducciones {key: value}
         * @param {string} locale - Código de idioma (opcional)
         */
        load: function(translations, locale) {
            if (translations && typeof translations === 'object') {
                // Merge con traducciones existentes
                for (var key in translations) {
                    if (translations.hasOwnProperty(key)) {
                        this.strings[key] = translations[key];
                    }
                }
            }
            if (locale) {
                this.locale = locale;
            }
        },

        /**
         * Traducir string
         *
         * @param {string} key - Clave del string o string por defecto
         * @param {string} fallback - String de fallback si no se encuentra traducción
         * @returns {string} String traducido o fallback
         */
        __: function(key, fallback) {
            // Si existe traducción, usarla
            if (this.strings.hasOwnProperty(key)) {
                return this.strings[key];
            }

            // Debug: mostrar keys no encontradas
            if (this.debug && typeof console !== 'undefined') {
                console.warn('[VBPi18n] Missing translation for key:', key);
            }

            // Retornar fallback o la key como último recurso
            return fallback !== undefined ? fallback : key;
        },

        /**
         * Traducir con contexto
         *
         * @param {string} key - Clave del string
         * @param {string} context - Contexto para desambiguación
         * @param {string} fallback - String de fallback
         * @returns {string} String traducido
         */
        _x: function(key, context, fallback) {
            var contextKey = context + '|' + key;
            if (this.strings.hasOwnProperty(contextKey)) {
                return this.strings[contextKey];
            }
            return this.__(key, fallback);
        },

        /**
         * Traducir con sprintf-style substitution
         *
         * @param {string} key - Clave del string con placeholders
         * @param {...*} args - Argumentos para sustituir
         * @returns {string} String traducido con sustituciones
         *
         * @example
         * VBPi18n._s('elements_selected', 3); // "3 elementos seleccionados"
         * VBPi18n._s('saved_at', '14:30'); // "Guardado a las 14:30"
         */
        _s: function(key) {
            var str = this.__(key);
            var args = Array.prototype.slice.call(arguments, 1);

            if (args.length === 0) {
                return str;
            }

            // Reemplazar %s, %d, %1$s, %2$s, etc.
            var argIndex = 0;

            // Primero manejar placeholders posicionales (%1$s, %2$d, etc.)
            str = str.replace(/%(\d+)\$([sd])/g, function(match, position, type) {
                var index = parseInt(position, 10) - 1;
                if (index >= 0 && index < args.length) {
                    return String(args[index]);
                }
                return match;
            });

            // Luego manejar placeholders simples (%s, %d)
            str = str.replace(/%([sd])/g, function(match, type) {
                if (argIndex < args.length) {
                    return String(args[argIndex++]);
                }
                return match;
            });

            return str;
        },

        /**
         * Traducir con plurales
         *
         * @param {string} single - String singular
         * @param {string} plural - String plural
         * @param {number} count - Número para determinar forma
         * @returns {string} String en forma correcta
         *
         * @example
         * VBPi18n._n('1 elemento', '%d elementos', 5); // "5 elementos"
         * VBPi18n._n('1 item', '%d items', 1); // "1 item"
         */
        _n: function(single, plural, count) {
            // Buscar traducción con formato plural
            var pluralKey = single + '|' + plural;
            var translatedSingle = this.__(single);
            var translatedPlural = this.strings.hasOwnProperty(pluralKey + '_plural')
                ? this.strings[pluralKey + '_plural']
                : this.__(plural);

            var result = count === 1 ? translatedSingle : translatedPlural;

            // Sustituir %d con el número
            return result.replace(/%d/g, String(count));
        },

        /**
         * Traducir con plurales y contexto
         *
         * @param {string} single - String singular
         * @param {string} plural - String plural
         * @param {number} count - Número para determinar forma
         * @param {string} context - Contexto
         * @returns {string}
         */
        _nx: function(single, plural, count, context) {
            var contextSingle = context + '|' + single;
            var contextPlural = context + '|' + plural;

            var translatedSingle = this.strings.hasOwnProperty(contextSingle)
                ? this.strings[contextSingle]
                : this.__(single);
            var translatedPlural = this.strings.hasOwnProperty(contextPlural)
                ? this.strings[contextPlural]
                : this.__(plural);

            var result = count === 1 ? translatedSingle : translatedPlural;
            return result.replace(/%d/g, String(count));
        },

        /**
         * Registrar strings adicionales en runtime
         *
         * @param {string} key - Clave del string
         * @param {string} value - Valor traducido
         */
        register: function(key, value) {
            this.strings[key] = value;
        },

        /**
         * Registrar múltiples strings
         *
         * @param {Object} strings - Objeto {key: value}
         */
        registerMany: function(strings) {
            this.load(strings);
        },

        /**
         * Verificar si existe traducción para una key
         *
         * @param {string} key - Clave a verificar
         * @returns {boolean}
         */
        has: function(key) {
            return this.strings.hasOwnProperty(key);
        },

        /**
         * Obtener todas las keys registradas
         *
         * @returns {Array} Lista de keys
         */
        getKeys: function() {
            return Object.keys(this.strings);
        },

        /**
         * Activar modo debug
         */
        enableDebug: function() {
            this.debug = true;
        },

        /**
         * Desactivar modo debug
         */
        disableDebug: function() {
            this.debug = false;
        }
    };

    // Exponer globalmente
    window.VBPi18n = VBPi18n;

    /**
     * Alias global para traducción rápida
     *
     * @param {string} key - Clave o string a traducir
     * @param {string} fallback - Fallback si no existe traducción
     * @returns {string} String traducido
     *
     * @example
     * __('save'); // "Guardar"
     * __('my_custom_key', 'Default text'); // "Default text" si no existe
     */
    window.__ = function(key, fallback) {
        return VBPi18n.__(key, fallback);
    };

    /**
     * Alias global para traducción con sustitución
     *
     * @example
     * _s('selected_count', 5); // "5 elementos seleccionados"
     */
    window._s = function() {
        return VBPi18n._s.apply(VBPi18n, arguments);
    };

    /**
     * Alias global para plurales
     *
     * @example
     * _n('1 elemento', '%d elementos', count);
     */
    window._n = function(single, plural, count) {
        return VBPi18n._n(single, plural, count);
    };

    /**
     * Alias global para traducción con contexto
     *
     * @example
     * _x('Post', 'noun'); // vs _x('Post', 'verb')
     */
    window._x = function(key, context, fallback) {
        return VBPi18n._x(key, context, fallback);
    };

    // Cargar traducciones si ya están disponibles (desde wp_localize_script)
    if (typeof VBP_Translations !== 'undefined') {
        VBPi18n.load(VBP_Translations);
    }

    // Escuchar evento de carga de traducciones adicionales
    document.addEventListener('vbp:i18n:load', function(event) {
        if (event.detail && event.detail.translations) {
            VBPi18n.load(event.detail.translations, event.detail.locale);
        }
    });

    // Integración con Alpine.js - hacer disponible en stores
    document.addEventListener('alpine:init', function() {
        if (typeof Alpine !== 'undefined') {
            // Hacer accesibles las funciones de i18n en Alpine
            Alpine.magic('__', function() {
                return function(key, fallback) {
                    return VBPi18n.__(key, fallback);
                };
            });

            Alpine.magic('_s', function() {
                return function() {
                    return VBPi18n._s.apply(VBPi18n, arguments);
                };
            });

            Alpine.magic('_n', function() {
                return function(single, plural, count) {
                    return VBPi18n._n(single, plural, count);
                };
            });
        }
    });

})(window);
