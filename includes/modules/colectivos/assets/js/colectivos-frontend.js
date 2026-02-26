/**
 * JavaScript frontend para Colectivos y Asociaciones
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var FlavorColectivos = {
        config: window.flavorColectivosConfig || {},

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Formulario crear colectivo
            $(document).on('submit', '#flavor-form-crear-colectivo', this.crearColectivo.bind(this));

            // Unirse a colectivo
            $(document).on('click', '.flavor-unirse-colectivo', this.unirse.bind(this));

            // Salir de colectivo
            $(document).on('click', '.flavor-salir-colectivo', this.salir.bind(this));

            // Confirmar asistencia a asamblea
            $(document).on('click', '.flavor-confirmar-asistencia', this.confirmarAsistencia.bind(this));
        },

        crearColectivo: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $btn = $form.find('button[type="submit"]');
            var btnText = $btn.html();

            var formData = new FormData($form[0]);
            formData.append('action', 'flavor_colectivos_crear');

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + this.config.strings.procesando);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else {
                            FlavorColectivos.showNotice(response.data.message, 'success');
                        }
                    } else {
                        FlavorColectivos.showNotice(response.data.message || FlavorColectivos.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorColectivos.showNotice(FlavorColectivos.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        unirse: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var colectivoId = $btn.data('colectivo-id');
            var btnText = $btn.html();

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_colectivos_unirse',
                    nonce: this.config.nonce,
                    colectivo_id: colectivoId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorColectivos.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        FlavorColectivos.showNotice(response.data.message || FlavorColectivos.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorColectivos.showNotice(FlavorColectivos.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        salir: function(e) {
            e.preventDefault();

            if (!confirm(this.config.strings.confirmarSalir)) {
                return;
            }

            var $btn = $(e.currentTarget);
            var colectivoId = $btn.data('colectivo-id');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_colectivos_salir',
                    nonce: this.config.nonce,
                    colectivo_id: colectivoId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorColectivos.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        FlavorColectivos.showNotice(response.data.message || FlavorColectivos.config.strings.error, 'error');
                    }
                }
            });
        },

        confirmarAsistencia: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var asambleaId = $btn.data('asamblea-id');
            var btnText = $btn.html();

            $btn.prop('disabled', true).text('...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_colectivos_confirmar_asistencia',
                    nonce: this.config.nonce,
                    asamblea_id: asambleaId
                },
                success: function(response) {
                    if (response.success) {
                        $btn.removeClass('flavor-btn-primary').addClass('flavor-btn-success')
                            .html('<span class="dashicons dashicons-yes"></span> Confirmado');
                        FlavorColectivos.showNotice(response.data.message, 'success');
                    } else {
                        FlavorColectivos.showNotice(response.data.message || FlavorColectivos.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        showNotice: function(message, type) {
            var $notice = $('<div class="flavor-notice flavor-notice-' + type + '">' + message + '</div>');
            $('body').append($notice);

            setTimeout(function() {
                $notice.addClass('show');
            }, 10);

            setTimeout(function() {
                $notice.removeClass('show');
                setTimeout(function() {
                    $notice.remove();
                }, 300);
            }, 3000);
        }
    };

    $(document).ready(function() {
        FlavorColectivos.init();
    });

    // CSS para notificaciones
    var style = document.createElement('style');
    style.textContent = `
        .flavor-notice {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            z-index: 10000;
            opacity: 0;
            transform: translateX(100px);
            transition: all 0.3s ease;
        }
        .flavor-notice.show {
            opacity: 1;
            transform: translateX(0);
        }
        .flavor-notice-success {
            background: #22c55e;
        }
        .flavor-notice-error {
            background: #ef4444;
        }
        .dashicons.spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);

})(jQuery);
