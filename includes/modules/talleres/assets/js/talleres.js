/**
 * Talleres Module - Frontend JavaScript
 *
 * @package FlavorPlatform
 * @subpackage Modules/Talleres
 * @since 3.1.0
 */

(function($) {
    'use strict';

    // Objeto principal del módulo
    window.FlavorTalleres = {

        /**
         * Inicializa el módulo
         */
        init: function() {
            this.bindEvents();
            this.initFiltros();
        },

        /**
         * Bindea eventos
         */
        bindEvents: function() {
            // Inscripción a taller
            $(document).on('submit', '.flavor-taller-inscripcion form', this.handleInscripcion.bind(this));

            // Filtros
            $(document).on('change', '.flavor-talleres-filtros select', this.handleFiltroChange.bind(this));
            $(document).on('input', '.flavor-talleres-filtros input[type="search"]', this.debounce(this.handleBusqueda.bind(this), 300));

            // Cancelar inscripción
            $(document).on('click', '.flavor-taller-cancelar', this.handleCancelacion.bind(this));
        },

        /**
         * Inicializa filtros
         */
        initFiltros: function() {
            var $filtros = $('.flavor-talleres-filtros');
            if ($filtros.length && window.location.search) {
                var params = new URLSearchParams(window.location.search);
                params.forEach(function(value, key) {
                    $filtros.find('[name="' + key + '"]').val(value);
                });
            }
        },

        /**
         * Maneja el envío del formulario de inscripción
         */
        handleInscripcion: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $button = $form.find('button[type="submit"]');
            var originalText = $button.text();

            // Validar formulario
            if (!this.validarFormulario($form)) {
                return;
            }

            // Deshabilitar botón
            $button.prop('disabled', true).text(flavorTalleresData.i18n.procesando || 'Procesando...');

            $.ajax({
                url: flavorTalleresData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_taller_inscripcion',
                    nonce: flavorTalleresData.nonce,
                    taller_id: $form.find('[name="taller_id"]').val(),
                    datos: $form.serialize()
                },
                success: function(response) {
                    if (response.success) {
                        // Mostrar mensaje de éxito
                        FlavorTalleres.mostrarMensaje('success', response.data.mensaje);

                        // Actualizar UI
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else {
                            // Recargar sección de plazas
                            FlavorTalleres.actualizarPlazas($form.find('[name="taller_id"]').val());
                        }
                    } else {
                        FlavorTalleres.mostrarMensaje('error', response.data.mensaje || flavorTalleresData.i18n.error);
                    }
                },
                error: function() {
                    FlavorTalleres.mostrarMensaje('error', flavorTalleresData.i18n.errorConexion || 'Error de conexión');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Maneja cambio de filtro
         */
        handleFiltroChange: function(e) {
            var $filtros = $(e.target).closest('.flavor-talleres-filtros');
            this.aplicarFiltros($filtros);
        },

        /**
         * Maneja búsqueda
         */
        handleBusqueda: function(e) {
            var $filtros = $(e.target).closest('.flavor-talleres-filtros');
            this.aplicarFiltros($filtros);
        },

        /**
         * Aplica filtros y recarga grid
         */
        aplicarFiltros: function($filtros) {
            var params = new URLSearchParams();

            $filtros.find('select, input').each(function() {
                var $input = $(this);
                var value = $input.val();
                if (value) {
                    params.set($input.attr('name'), value);
                }
            });

            // Actualizar URL
            var newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.pushState({}, '', newUrl);

            // Recargar grid via AJAX
            this.recargarGrid(Object.fromEntries(params));
        },

        /**
         * Recarga el grid de talleres
         */
        recargarGrid: function(filtros) {
            var $grid = $('.flavor-talleres-grid');
            if (!$grid.length) return;

            $grid.addClass('loading');

            $.ajax({
                url: flavorTalleresData.ajaxUrl,
                type: 'GET',
                data: $.extend({
                    action: 'flavor_talleres_grid'
                }, filtros),
                success: function(response) {
                    if (response.success) {
                        $grid.html(response.data.html);
                    }
                },
                complete: function() {
                    $grid.removeClass('loading');
                }
            });
        },

        /**
         * Maneja cancelación de inscripción
         */
        handleCancelacion: function(e) {
            e.preventDefault();

            if (!confirm(flavorTalleresData.i18n.confirmarCancelacion || '¿Seguro que quieres cancelar tu inscripción?')) {
                return;
            }

            var $button = $(e.target);
            var inscripcionId = $button.data('inscripcion-id');

            $button.prop('disabled', true);

            $.ajax({
                url: flavorTalleresData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_taller_cancelar',
                    nonce: flavorTalleresData.nonce,
                    inscripcion_id: inscripcionId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorTalleres.mostrarMensaje('success', response.data.mensaje);
                        $button.closest('.flavor-inscripcion-item').fadeOut();
                    } else {
                        FlavorTalleres.mostrarMensaje('error', response.data.mensaje);
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    FlavorTalleres.mostrarMensaje('error', flavorTalleresData.i18n.errorConexion);
                    $button.prop('disabled', false);
                }
            });
        },

        /**
         * Valida formulario de inscripción
         */
        validarFormulario: function($form) {
            var valid = true;
            $form.find('[required]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('error');
                    valid = false;
                } else {
                    $(this).removeClass('error');
                }
            });
            return valid;
        },

        /**
         * Actualiza contador de plazas
         */
        actualizarPlazas: function(tallerId) {
            var $plazas = $('[data-taller-plazas="' + tallerId + '"]');
            if ($plazas.length) {
                var actual = parseInt($plazas.data('actual'), 10) || 0;
                $plazas.data('actual', actual + 1);
                $plazas.text($plazas.data('total') - actual - 1 + ' plazas disponibles');
            }
        },

        /**
         * Muestra mensaje de feedback
         */
        mostrarMensaje: function(tipo, mensaje) {
            var $mensaje = $('<div class="flavor-mensaje flavor-mensaje--' + tipo + '">' + mensaje + '</div>');

            // Remover mensajes anteriores
            $('.flavor-mensaje').remove();

            // Insertar nuevo mensaje
            $('.flavor-taller-inscripcion, .flavor-talleres-grid').first().before($mensaje);

            // Auto-ocultar después de 5 segundos
            setTimeout(function() {
                $mensaje.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Función debounce
         */
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        if ($('.flavor-talleres-grid, .flavor-taller-single').length) {
            FlavorTalleres.init();
        }
    });

})(jQuery);
