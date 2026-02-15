/**
 * Justicia Restaurativa - JavaScript
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const JusticiaRestaurativa = {
        /**
         * Inicializa el módulo
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Vincula eventos
         */
        bindEvents: function() {
            // Solicitar proceso
            $(document).on('submit', '.jr-form-solicitar', this.handleSolicitarProceso.bind(this));

            // Responder solicitud
            $(document).on('click', '.jr-btn-aceptar', this.handleAceptarProceso.bind(this));
            $(document).on('click', '.jr-btn-rechazar', this.handleRechazarProceso.bind(this));

            // Ser mediador
            $(document).on('submit', '.jr-form-mediador', this.handleSerMediador.bind(this));
        },

        /**
         * Maneja solicitar proceso
         */
        handleSolicitarProceso: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            if (!confirm(jrData.i18n.confirmSolicitud)) {
                return;
            }

            $btn.prop('disabled', true).text(jrData.i18n.enviando);

            $.ajax({
                url: jrData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'jr_solicitar_proceso',
                    nonce: jrData.nonce,
                    tipo: $form.find('[name="tipo"]').val(),
                    descripcion: $form.find('[name="descripcion"]').val(),
                    otra_parte_email: $form.find('[name="otra_parte_email"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        JusticiaRestaurativa.showToast(response.data.message, 'success');
                        $form[0].reset();

                        // Redirigir a mis procesos
                        setTimeout(function() {
                            window.location.href = '/mi-portal/justicia-restaurativa/mis-procesos/';
                        }, 2000);
                    } else {
                        JusticiaRestaurativa.showToast(response.data.message, 'error');
                        $btn.prop('disabled', false).text('Enviar solicitud');
                    }
                },
                error: function() {
                    JusticiaRestaurativa.showToast('Error de conexión', 'error');
                    $btn.prop('disabled', false).text('Enviar solicitud');
                }
            });
        },

        /**
         * Maneja aceptar proceso
         */
        handleAceptarProceso: function(e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const procesoId = $btn.data('proceso');

            $btn.prop('disabled', true).text('Aceptando...');

            $.ajax({
                url: jrData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'jr_responder_solicitud',
                    nonce: jrData.nonce,
                    proceso_id: procesoId,
                    respuesta: 'acepto'
                },
                success: function(response) {
                    if (response.success) {
                        JusticiaRestaurativa.showToast(response.data.message, 'success');
                        location.reload();
                    } else {
                        JusticiaRestaurativa.showToast(response.data.message, 'error');
                        $btn.prop('disabled', false).text('Aceptar participar');
                    }
                },
                error: function() {
                    JusticiaRestaurativa.showToast('Error de conexión', 'error');
                    $btn.prop('disabled', false).text('Aceptar participar');
                }
            });
        },

        /**
         * Maneja rechazar proceso
         */
        handleRechazarProceso: function(e) {
            e.preventDefault();

            if (!confirm('¿Estás seguro de que no deseas participar en este proceso?')) {
                return;
            }

            const $btn = $(e.currentTarget);
            const procesoId = $btn.data('proceso');

            $btn.prop('disabled', true);

            $.ajax({
                url: jrData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'jr_responder_solicitud',
                    nonce: jrData.nonce,
                    proceso_id: procesoId,
                    respuesta: 'rechazo'
                },
                success: function(response) {
                    JusticiaRestaurativa.showToast(response.data.message, 'success');
                    location.reload();
                },
                error: function() {
                    JusticiaRestaurativa.showToast('Error de conexión', 'error');
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Maneja ser mediador
         */
        handleSerMediador: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true).text(jrData.i18n.enviando);

            $.ajax({
                url: jrData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'jr_ser_mediador',
                    nonce: jrData.nonce,
                    formacion: $form.find('[name="formacion"]').val(),
                    motivacion: $form.find('[name="motivacion"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        JusticiaRestaurativa.showToast(response.data.message, 'success');
                        $form[0].reset();
                    } else {
                        JusticiaRestaurativa.showToast(response.data.message, 'error');
                    }
                    $btn.prop('disabled', false).text('Enviar solicitud');
                },
                error: function() {
                    JusticiaRestaurativa.showToast('Error de conexión', 'error');
                    $btn.prop('disabled', false).text('Enviar solicitud');
                }
            });
        },

        /**
         * Muestra toast
         */
        showToast: function(message, type) {
            const $toast = $(`<div class="jr-toast jr-toast--${type}">${message}</div>`);
            $('body').append($toast);

            setTimeout(function() {
                $toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Inicializar
    $(document).ready(function() {
        JusticiaRestaurativa.init();
    });

})(jQuery);
