/**
 * Dashboard Unificado - Core Utilities
 *
 * Funciones utilitarias, helpers AJAX, notificaciones toast
 * y otras utilidades compartidas.
 *
 * @package FlavorChatIA
 * @since 4.0.0
 */

(function($, window) {
    'use strict';

    /**
     * Namespace principal del Dashboard
     */
    window.FlavorDashboard = window.FlavorDashboard || {};

    /**
     * Configuracion global
     */
    FlavorDashboard.config = {
        ajaxUrl: window.fudDashboard?.ajaxUrl || ajaxurl,
        restUrl: window.fudDashboard?.restUrl || '',
        nonce: window.fudDashboard?.nonce || '',
        restNonce: window.fudDashboard?.restNonce || '',
        refreshInterval: window.fudDashboard?.refreshInterval || 120000,
        userId: window.fudDashboard?.userId || 0,
        i18n: window.fudDashboard?.i18n || {},
        userPreferences: window.fudDashboard?.userPreferences || {}
    };

    /**
     * Utilidades AJAX
     */
    FlavorDashboard.ajax = {
        /**
         * Realiza una peticion AJAX
         *
         * @param {string} action Accion AJAX
         * @param {Object} data Datos a enviar
         * @param {Object} options Opciones adicionales
         * @returns {Promise}
         */
        request: function(action, data, options) {
            data = data || {};
            options = options || {};

            var requestData = $.extend({
                action: action,
                nonce: FlavorDashboard.config.nonce
            }, data);

            var ajaxOptions = $.extend({
                url: FlavorDashboard.config.ajaxUrl,
                type: 'POST',
                data: requestData,
                dataType: 'json'
            }, options);

            return $.ajax(ajaxOptions);
        },

        /**
         * Peticion GET simplificada
         */
        get: function(action, data) {
            return this.request(action, data, { type: 'GET' });
        },

        /**
         * Peticion POST simplificada
         */
        post: function(action, data) {
            return this.request(action, data, { type: 'POST' });
        }
    };

    /**
     * Utilidades REST API
     */
    FlavorDashboard.rest = {
        /**
         * Peticion REST
         */
        request: function(endpoint, method, data) {
            method = method || 'GET';

            return $.ajax({
                url: FlavorDashboard.config.restUrl + endpoint,
                type: method,
                data: data,
                dataType: 'json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', FlavorDashboard.config.restNonce);
                }
            });
        },

        get: function(endpoint, data) {
            return this.request(endpoint, 'GET', data);
        },

        post: function(endpoint, data) {
            return this.request(endpoint, 'POST', data);
        }
    };

    /**
     * Sistema de notificaciones Toast
     */
    FlavorDashboard.toast = {
        container: null,
        template: null,
        timeout: 4000,

        /**
         * Inicializa el sistema de toast
         */
        init: function() {
            this.container = $('#fud-toast-container');
            this.template = $('#fud-toast-template').html();

            if (!this.container.length) {
                this.container = $('<div id="fud-toast-container" class="fud-toast-container"></div>');
                $('body').append(this.container);
            }
        },

        /**
         * Muestra un toast
         *
         * @param {string} message Mensaje
         * @param {string} type Tipo: success, error, warning, info
         * @param {number} duration Duracion en ms
         */
        show: function(message, type, duration) {
            if (!this.container) {
                this.init();
            }

            type = type || 'info';
            duration = duration || this.timeout;

            var icons = {
                success: 'dashicons-yes-alt',
                error: 'dashicons-dismiss',
                warning: 'dashicons-warning',
                info: 'dashicons-info'
            };

            var html = this.template
                .replace('{{type}}', type)
                .replace('{{icon}}', icons[type] || icons.info)
                .replace('{{message}}', message);

            var $toast = $(html);
            this.container.append($toast);

            // Auto-cerrar
            var self = this;
            setTimeout(function() {
                self.close($toast);
            }, duration);

            // Cerrar al hacer clic
            $toast.find('.fud-toast__close').on('click', function() {
                self.close($toast);
            });

            return $toast;
        },

        /**
         * Cierra un toast
         */
        close: function($toast) {
            $toast.css({
                opacity: 0,
                transform: 'translateX(100%)'
            });

            setTimeout(function() {
                $toast.remove();
            }, 300);
        },

        /**
         * Atajos
         */
        success: function(message, duration) {
            return this.show(message, 'success', duration);
        },

        error: function(message, duration) {
            return this.show(message, 'error', duration);
        },

        warning: function(message, duration) {
            return this.show(message, 'warning', duration);
        },

        info: function(message, duration) {
            return this.show(message, 'info', duration);
        }
    };

    /**
     * Sistema de indicadores de carga
     */
    FlavorDashboard.loading = {
        /**
         * Muestra indicador de carga en un contenedor
         */
        show: function($container, message) {
            message = message || FlavorDashboard.config.i18n.loading || 'Cargando...';

            var html = '<div class="fud-loading-state">' +
                '<span class="fud-loading-spinner"></span>' +
                '<span class="fud-loading-text">' + message + '</span>' +
                '</div>';

            $container.html(html);
        },

        /**
         * Oculta indicador de carga
         */
        hide: function($container) {
            $container.find('.fud-loading-state').remove();
        },

        /**
         * Muestra overlay de carga sobre un elemento
         */
        overlay: function($element) {
            var $overlay = $('<div class="fud-loading-overlay"><span class="fud-loading-spinner"></span></div>');
            $element.css('position', 'relative').append($overlay);
            return $overlay;
        },

        /**
         * Remueve overlay
         */
        removeOverlay: function($element) {
            $element.find('.fud-loading-overlay').remove();
        }
    };

    /**
     * Utilidades de validacion
     */
    FlavorDashboard.validate = {
        email: function(value) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(value);
        },

        required: function(value) {
            return value !== null && value !== undefined && value !== '';
        },

        minLength: function(value, min) {
            return value && value.length >= min;
        },

        maxLength: function(value, max) {
            return !value || value.length <= max;
        },

        numeric: function(value) {
            return !isNaN(parseFloat(value)) && isFinite(value);
        }
    };

    /**
     * Utilidades de formato
     */
    FlavorDashboard.format = {
        /**
         * Formatea un numero
         */
        number: function(num, decimals) {
            decimals = decimals || 0;
            return parseFloat(num).toLocaleString(undefined, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        },

        /**
         * Formatea precio
         */
        currency: function(num, symbol) {
            symbol = symbol || '$';
            return symbol + this.number(num, 2);
        },

        /**
         * Tiempo relativo
         */
        timeAgo: function(dateString) {
            var date = new Date(dateString);
            var now = new Date();
            var diff = Math.floor((now - date) / 1000);

            if (diff < 60) {
                return FlavorDashboard.config.i18n.justNow || 'Ahora mismo';
            } else if (diff < 3600) {
                var mins = Math.floor(diff / 60);
                var template = FlavorDashboard.config.i18n.minutesAgo || 'Hace %d minutos';
                return template.replace('%d', mins);
            } else if (diff < 86400) {
                var hours = Math.floor(diff / 3600);
                return 'Hace ' + hours + ' hora' + (hours > 1 ? 's' : '');
            } else {
                var days = Math.floor(diff / 86400);
                return 'Hace ' + days + ' día' + (days > 1 ? 's' : '');
            }
        },

        /**
         * Trunca texto
         */
        truncate: function(str, length, suffix) {
            length = length || 100;
            suffix = suffix || '...';

            if (str.length <= length) {
                return str;
            }

            return str.substring(0, length - suffix.length) + suffix;
        }
    };

    /**
     * Utilidades de almacenamiento
     */
    FlavorDashboard.storage = {
        prefix: 'fud_',

        get: function(key, defaultValue) {
            try {
                var value = localStorage.getItem(this.prefix + key);
                return value !== null ? JSON.parse(value) : defaultValue;
            } catch (e) {
                return defaultValue;
            }
        },

        set: function(key, value) {
            try {
                localStorage.setItem(this.prefix + key, JSON.stringify(value));
                return true;
            } catch (e) {
                return false;
            }
        },

        remove: function(key) {
            try {
                localStorage.removeItem(this.prefix + key);
                return true;
            } catch (e) {
                return false;
            }
        },

        clear: function() {
            try {
                var prefix = this.prefix;
                Object.keys(localStorage).forEach(function(key) {
                    if (key.startsWith(prefix)) {
                        localStorage.removeItem(key);
                    }
                });
                return true;
            } catch (e) {
                return false;
            }
        }
    };

    /**
     * Sistema de eventos
     */
    FlavorDashboard.events = {
        /**
         * Emite un evento personalizado
         */
        emit: function(eventName, data) {
            $(document).trigger('fud:' + eventName, data);
        },

        /**
         * Escucha un evento personalizado
         */
        on: function(eventName, callback) {
            $(document).on('fud:' + eventName, callback);
        },

        /**
         * Deja de escuchar un evento
         */
        off: function(eventName, callback) {
            $(document).off('fud:' + eventName, callback);
        }
    };

    /**
     * Utilidades de debug
     */
    FlavorDashboard.debug = {
        enabled: false,

        log: function() {
            if (this.enabled && console && console.log) {
                console.log.apply(console, ['[FUD]'].concat(Array.prototype.slice.call(arguments)));
            }
        },

        warn: function() {
            if (this.enabled && console && console.warn) {
                console.warn.apply(console, ['[FUD]'].concat(Array.prototype.slice.call(arguments)));
            }
        },

        error: function() {
            if (console && console.error) {
                console.error.apply(console, ['[FUD]'].concat(Array.prototype.slice.call(arguments)));
            }
        }
    };

    /**
     * Sistema de Filtros de Categoria
     * @since 4.1.0
     */
    FlavorDashboard.categoryFilters = {
        currentFilter: 'all',
        $filters: null,
        $groups: null,

        /**
         * Inicializa los filtros de categoria
         */
        init: function() {
            this.$filters = $('.fl-category-filter');
            this.$groups = $('.fl-widget-group');

            if (!this.$filters.length) {
                return;
            }

            this.bindEvents();
            FlavorDashboard.debug.log('Filtros de categoria inicializados');
        },

        /**
         * Vincula eventos
         */
        bindEvents: function() {
            var self = this;

            // Click en filtros
            this.$filters.on('click', function(e) {
                e.preventDefault();
                var categoria = $(this).data('category');
                self.filterByCategory(categoria);
            });

            // Soporte de teclado
            this.$filters.on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });
        },

        /**
         * Filtra widgets por categoria
         */
        filterByCategory: function(categoria) {
            this.currentFilter = categoria;

            // Actualizar estado de filtros
            this.$filters
                .removeClass('fl-category-filter--active')
                .attr('aria-selected', 'false');

            this.$filters.filter('[data-category="' + categoria + '"]')
                .addClass('fl-category-filter--active')
                .attr('aria-selected', 'true');

            // Mostrar/ocultar grupos
            if (categoria === 'all') {
                this.$groups.show().attr('aria-hidden', 'false');
            } else {
                this.$groups.each(function() {
                    var $grupo = $(this);
                    var categoriaGrupo = $grupo.data('category');

                    if (categoriaGrupo === categoria) {
                        $grupo.show().attr('aria-hidden', 'false');
                    } else {
                        $grupo.hide().attr('aria-hidden', 'true');
                    }
                });
            }

            // Anunciar cambio para lectores de pantalla
            FlavorDashboard.a11y.announce(
                categoria === 'all'
                    ? 'Mostrando todos los widgets'
                    : 'Mostrando widgets de ' + categoria
            );

            // Emitir evento
            FlavorDashboard.events.emit('categoryFilter', { category: categoria });
        }
    };

    /**
     * Sistema de Grupos de Widgets
     * @since 4.1.0
     */
    FlavorDashboard.widgetGroups = {
        $groups: null,

        /**
         * Inicializa los grupos de widgets
         */
        init: function() {
            this.$groups = $('.fl-widget-group');

            if (!this.$groups.length) {
                return;
            }

            this.bindEvents();
            FlavorDashboard.debug.log('Grupos de widgets inicializados');
        },

        /**
         * Vincula eventos
         */
        bindEvents: function() {
            var self = this;

            // Toggle de grupos
            $(document).on('click', '.fl-widget-group__toggle', function(e) {
                e.preventDefault();
                var $toggle = $(this);
                var categoria = $toggle.data('category');
                self.toggleGroup(categoria);
            });
        },

        /**
         * Alterna el estado de un grupo
         */
        toggleGroup: function(categoria) {
            var $grupo = this.$groups.filter('[data-category="' + categoria + '"]');
            var $toggle = $grupo.find('.fl-widget-group__toggle');
            var $contenido = $grupo.find('.fl-widget-group__content');
            var estaColapsado = $grupo.hasClass('fl-widget-group--collapsed');

            if (estaColapsado) {
                // Expandir
                $grupo.removeClass('fl-widget-group--collapsed');
                $toggle.attr('aria-expanded', 'true');
                $contenido.removeAttr('hidden').slideDown(200);
            } else {
                // Colapsar
                $grupo.addClass('fl-widget-group--collapsed');
                $toggle.attr('aria-expanded', 'false');
                $contenido.slideUp(200, function() {
                    $(this).attr('hidden', 'hidden');
                });
            }

            // Guardar preferencia
            this.saveGroupState(categoria, !estaColapsado);

            // Emitir evento
            FlavorDashboard.events.emit('groupToggle', {
                category: categoria,
                collapsed: !estaColapsado
            });
        },

        /**
         * Guarda el estado del grupo en el servidor
         */
        saveGroupState: function(categoria, colapsado) {
            FlavorDashboard.ajax.post('flavor_client_dashboard_save_preferences', {
                preference_type: 'collapsed_category',
                category: categoria,
                collapsed: colapsado ? 1 : 0
            }).done(function(response) {
                FlavorDashboard.debug.log('Estado de grupo guardado:', categoria, colapsado);
            }).fail(function() {
                FlavorDashboard.debug.warn('Error al guardar estado de grupo');
            });
        },

        /**
         * Expande todos los grupos
         */
        expandAll: function() {
            var self = this;
            this.$groups.each(function() {
                var categoria = $(this).data('category');
                if ($(this).hasClass('fl-widget-group--collapsed')) {
                    self.toggleGroup(categoria);
                }
            });
        },

        /**
         * Colapsa todos los grupos
         */
        collapseAll: function() {
            var self = this;
            this.$groups.each(function() {
                var categoria = $(this).data('category');
                if (!$(this).hasClass('fl-widget-group--collapsed')) {
                    self.toggleGroup(categoria);
                }
            });
        }
    };

    /**
     * Accesibilidad
     * @since 4.1.0
     */
    FlavorDashboard.a11y = {
        $liveRegion: null,

        /**
         * Inicializa helpers de accesibilidad
         */
        init: function() {
            this.$liveRegion = $('#fl-live-announcer');

            if (!this.$liveRegion.length) {
                this.$liveRegion = $('<div id="fl-live-announcer" class="fl-sr-only" role="status" aria-live="polite" aria-atomic="true"></div>');
                $('body').append(this.$liveRegion);
            }
        },

        /**
         * Anuncia un mensaje para lectores de pantalla
         */
        announce: function(message, priority) {
            priority = priority || 'polite';

            if (!this.$liveRegion.length) {
                this.init();
            }

            this.$liveRegion
                .attr('aria-live', priority)
                .text('');

            // Forzar reflow para que el anuncio se registre
            setTimeout(function() {
                this.$liveRegion.text(message);
            }.bind(this), 100);
        }
    };

    /**
     * Inicializacion
     */
    $(function() {
        FlavorDashboard.toast.init();
        FlavorDashboard.a11y.init();
        FlavorDashboard.categoryFilters.init();
        FlavorDashboard.widgetGroups.init();
        FlavorDashboard.debug.enabled = typeof WP_DEBUG !== 'undefined' && WP_DEBUG;
        FlavorDashboard.debug.log('Dashboard Core inicializado');
    });

})(jQuery, window);
