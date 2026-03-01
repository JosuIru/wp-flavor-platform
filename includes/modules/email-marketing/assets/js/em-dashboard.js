/**
 * Email Marketing Dashboard JavaScript
 *
 * Maneja las interacciones del dashboard del usuario
 */

(function($) {
    'use strict';

    // Verificar que jQuery y las variables están disponibles
    if (typeof $ === 'undefined' || typeof flavorEMDashboard === 'undefined') {
        return;
    }

    var EMDashboard = {
        /**
         * Inicialización
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Toggle de suscripción
            $(document).on('change', '.em-toggle-suscripcion', this.handleToggleSuscripcion);

            // Formulario de preferencias
            $(document).on('submit', '#em-preferencias-form', this.handleGuardarPreferencias);

            // Crear suscriptor
            $(document).on('click', '#em-crear-suscriptor', this.handleCrearSuscriptor);

            // Baja total
            $(document).on('click', '#em-baja-total', this.handleBajaTotal);

            // Click en email del historial
            $(document).on('click', '.em-email-item', this.handleClickEmail);

            // Filtros del historial
            $(document).on('change', '#em-filtro-campania, #em-filtro-estado', this.handleFiltroHistorial);
        },

        /**
         * Toggle suscripción a lista
         */
        handleToggleSuscripcion: function() {
            var $toggle = $(this);
            var $card = $toggle.closest('.em-lista-card');
            var listaId = $toggle.data('lista-id');
            var isChecked = $toggle.is(':checked');

            // Deshabilitar mientras procesa
            $toggle.prop('disabled', true);

            $.ajax({
                url: flavorEMDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'em_dashboard_toggle_suscripcion',
                    nonce: flavorEMDashboard.nonce,
                    lista_id: listaId,
                    accion: isChecked ? 'suscribir' : 'baja'
                },
                success: function(response) {
                    if (response.success) {
                        // Actualizar estado visual
                        if (response.data.estado === 'activo') {
                            $card.removeClass('em-lista-disponible').addClass('em-lista-activa');
                        } else {
                            $card.removeClass('em-lista-activa').addClass('em-lista-disponible');
                        }

                        EMDashboard.mostrarNotificacion(response.data.message, 'success');
                    } else {
                        // Revertir cambio
                        $toggle.prop('checked', !isChecked);
                        EMDashboard.mostrarNotificacion(response.data.message, 'error');
                    }
                },
                error: function() {
                    // Revertir cambio
                    $toggle.prop('checked', !isChecked);
                    EMDashboard.mostrarNotificacion(flavorEMDashboard.strings.error, 'error');
                },
                complete: function() {
                    $toggle.prop('disabled', false);
                }
            });
        },

        /**
         * Guardar preferencias de email
         */
        handleGuardarPreferencias: function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $mensaje = $form.find('.em-form-mensaje');
            var originalText = $submitBtn.text();

            // Estado de carga
            $submitBtn.prop('disabled', true).text(flavorEMDashboard.strings.guardando);
            $mensaje.text('').removeClass('em-error');

            // Obtener datos del formulario
            var frecuencia = $form.find('input[name="frecuencia"]:checked').val();
            var tiposSeleccionados = [];
            $form.find('input[name="tipos[]"]:checked').each(function() {
                tiposSeleccionados.push($(this).val());
            });
            var formato = $form.find('input[name="formato"]:checked').val();

            $.ajax({
                url: flavorEMDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'em_dashboard_guardar_preferencias',
                    nonce: $form.find('#em_dashboard_nonce').val(),
                    frecuencia: frecuencia,
                    tipos: tiposSeleccionados,
                    formato: formato
                },
                success: function(response) {
                    if (response.success) {
                        $mensaje.text(flavorEMDashboard.strings.guardado);
                        EMDashboard.mostrarNotificacion(response.data.message, 'success');
                    } else {
                        $mensaje.text(response.data.message).addClass('em-error');
                        EMDashboard.mostrarNotificacion(response.data.message, 'error');
                    }
                },
                error: function() {
                    $mensaje.text(flavorEMDashboard.strings.error).addClass('em-error');
                    EMDashboard.mostrarNotificacion(flavorEMDashboard.strings.error, 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);

                    // Limpiar mensaje después de un tiempo
                    setTimeout(function() {
                        $mensaje.fadeOut(300, function() {
                            $(this).text('').show();
                        });
                    }, 3000);
                }
            });
        },

        /**
         * Crear suscriptor para el usuario
         */
        handleCrearSuscriptor: function() {
            var $btn = $(this);
            var originalText = $btn.text();

            $btn.prop('disabled', true).text(flavorEMDashboard.strings.guardando);

            $.ajax({
                url: flavorEMDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'em_dashboard_toggle_suscripcion',
                    nonce: flavorEMDashboard.nonce,
                    lista_id: 0, // Se creará con la lista por defecto
                    accion: 'crear'
                },
                success: function(response) {
                    if (response.success) {
                        EMDashboard.mostrarNotificacion(response.data.message, 'success');
                        // Recargar la página para mostrar las suscripciones
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        EMDashboard.mostrarNotificacion(response.data.message, 'error');
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    EMDashboard.mostrarNotificacion(flavorEMDashboard.strings.error, 'error');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Darse de baja de todas las listas
         */
        handleBajaTotal: function() {
            if (!confirm(flavorEMDashboard.strings.confirmar_baja)) {
                return;
            }

            var $btn = $(this);
            var originalText = $btn.text();

            $btn.prop('disabled', true).addClass('em-loading');

            // Desactivar todos los toggles activos
            $('.em-toggle-suscripcion:checked').each(function() {
                $(this).prop('checked', false).trigger('change');
            });

            setTimeout(function() {
                $btn.prop('disabled', false).removeClass('em-loading');
                EMDashboard.mostrarNotificacion('Te has dado de baja de todas las listas', 'success');
            }, 1000);
        },

        /**
         * Click en un email del historial
         */
        handleClickEmail: function() {
            var $item = $(this);
            var emailId = $item.data('email-id');

            // Si ya está leído, no hacer nada
            if ($item.hasClass('em-leido')) {
                return;
            }

            // Marcar visualmente como leído
            $item.removeClass('em-no-leido').addClass('em-leido');
            $item.find('.em-email-indicador .dashicons')
                .removeClass('dashicons-marker')
                .addClass('dashicons-yes-alt');

            // Enviar petición AJAX
            $.ajax({
                url: flavorEMDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'em_dashboard_marcar_email_leido',
                    nonce: flavorEMDashboard.nonce,
                    email_id: emailId
                },
                error: function() {
                    // Revertir si hay error
                    $item.removeClass('em-leido').addClass('em-no-leido');
                    $item.find('.em-email-indicador .dashicons')
                        .removeClass('dashicons-yes-alt')
                        .addClass('dashicons-marker');
                }
            });
        },

        /**
         * Filtrar historial de emails
         */
        handleFiltroHistorial: function() {
            var campaniaId = $('#em-filtro-campania').val();
            var estadoFiltro = $('#em-filtro-estado').val();

            $('.em-email-item').each(function() {
                var $item = $(this);
                var mostrar = true;

                // Filtro de campaña (si lo implementamos con data attributes)
                // Por ahora solo filtramos por estado visual

                // Filtro de estado
                if (estadoFiltro === 'abierto' && !$item.hasClass('em-leido')) {
                    mostrar = false;
                } else if (estadoFiltro === 'no_abierto' && $item.hasClass('em-leido')) {
                    mostrar = false;
                }

                $item.toggle(mostrar);
            });

            // Mostrar mensaje si no hay resultados
            var visiblesCount = $('.em-email-item:visible').length;
            var $noResults = $('.em-historial-no-resultados');

            if (visiblesCount === 0) {
                if ($noResults.length === 0) {
                    $('.em-historial-lista').after(
                        '<p class="em-historial-no-resultados em-empty-message">No hay emails que coincidan con los filtros seleccionados.</p>'
                    );
                }
            } else {
                $noResults.remove();
            }
        },

        /**
         * Mostrar notificación
         */
        mostrarNotificacion: function(mensaje, tipo) {
            // Verificar si existe un sistema de notificaciones global
            if (typeof window.flavorNotify === 'function') {
                window.flavorNotify(mensaje, tipo);
                return;
            }

            // Fallback: crear notificación propia
            var $notificacion = $('<div class="em-notificacion em-notificacion-' + tipo + '">' +
                '<span class="em-notificacion-mensaje">' + mensaje + '</span>' +
                '<button type="button" class="em-notificacion-cerrar">&times;</button>' +
                '</div>');

            // Estilos inline para la notificación
            $notificacion.css({
                position: 'fixed',
                bottom: '20px',
                right: '20px',
                padding: '1rem 1.5rem',
                borderRadius: '8px',
                backgroundColor: tipo === 'success' ? '#10b981' : '#ef4444',
                color: '#fff',
                boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                zIndex: 99999,
                display: 'flex',
                alignItems: 'center',
                gap: '1rem',
                animation: 'em-slide-in 0.3s ease'
            });

            $notificacion.find('.em-notificacion-cerrar').css({
                background: 'none',
                border: 'none',
                color: '#fff',
                fontSize: '1.25rem',
                cursor: 'pointer',
                padding: '0',
                lineHeight: '1'
            });

            // Agregar al DOM
            $('body').append($notificacion);

            // Evento para cerrar
            $notificacion.find('.em-notificacion-cerrar').on('click', function() {
                $notificacion.fadeOut(300, function() {
                    $(this).remove();
                });
            });

            // Auto-cerrar después de 4 segundos
            setTimeout(function() {
                $notificacion.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        EMDashboard.init();
    });

    // Agregar estilos de animación
    $('<style>')
        .prop('type', 'text/css')
        .html('@keyframes em-slide-in { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }')
        .appendTo('head');

})(jQuery);
