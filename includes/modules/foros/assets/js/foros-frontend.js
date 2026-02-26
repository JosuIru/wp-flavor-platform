/**
 * JavaScript frontend para Foros de Discusion
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var FlavorForos = {
        config: window.flavorForosConfig || {},

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Formulario nuevo tema
            $(document).on('submit', '#flavor-form-nuevo-tema', this.crearTema.bind(this));

            // Formulario respuesta
            $(document).on('submit', '#flavor-form-respuesta', this.responder.bind(this));

            // Votar respuesta
            $(document).on('click', '.flavor-voto-btn', this.votar.bind(this));

            // Marcar como solucion
            $(document).on('click', '.flavor-marcar-solucion', this.marcarSolucion.bind(this));

            // Reportar
            $(document).on('click', '.flavor-reportar', this.reportar.bind(this));
        },

        crearTema: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $btn = $form.find('button[type="submit"]');
            var btnText = $btn.html();

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + this.config.strings.enviando);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=flavor_foros_crear_tema',
                success: function(response) {
                    if (response.success) {
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else {
                            FlavorForos.showNotice(response.data.message, 'success');
                        }
                    } else {
                        FlavorForos.showNotice(response.data.message || FlavorForos.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorForos.showNotice(FlavorForos.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        responder: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $btn = $form.find('button[type="submit"]');
            var btnText = $btn.html();

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + this.config.strings.enviando);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=flavor_foros_responder',
                success: function(response) {
                    if (response.success) {
                        FlavorForos.showNotice(response.data.message, 'success');
                        // Recargar para ver la respuesta
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        FlavorForos.showNotice(response.data.message || FlavorForos.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorForos.showNotice(FlavorForos.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        votar: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            if ($btn.prop('disabled')) return;

            var $item = $btn.closest('.flavor-respuesta-item');
            var respuestaId = $item.data('respuesta-id');
            var tipo = $btn.data('tipo');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_foros_votar',
                    nonce: this.config.nonce,
                    respuesta_id: respuestaId,
                    tipo: tipo
                },
                success: function(response) {
                    if (response.success) {
                        $item.find('.flavor-votos-total').text(response.data.votos);

                        // Toggle active state
                        var $upBtn = $item.find('.flavor-voto-up');
                        var $downBtn = $item.find('.flavor-voto-down');

                        if (tipo === 'positivo') {
                            $upBtn.toggleClass('activo');
                            $downBtn.removeClass('activo');
                        } else {
                            $downBtn.toggleClass('activo');
                            $upBtn.removeClass('activo');
                        }
                    }
                }
            });
        },

        marcarSolucion: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var $item = $btn.closest('.flavor-respuesta-item');
            var respuestaId = $item.data('respuesta-id');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_foros_marcar_solucion',
                    nonce: this.config.nonce,
                    respuesta_id: respuestaId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorForos.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        FlavorForos.showNotice(response.data.message || FlavorForos.config.strings.error, 'error');
                    }
                }
            });
        },

        reportar: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var tipo = $btn.data('tipo');
            var $item = $btn.closest('.flavor-respuesta-item, .flavor-tema-principal');
            var id = $item.data('respuesta-id') || $item.data('tema-id');

            var motivo = prompt('¿Por qué quieres reportar este contenido?');
            if (!motivo) return;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_foros_reportar',
                    nonce: this.config.nonce,
                    tipo: tipo,
                    id: id,
                    motivo: motivo
                },
                success: function(response) {
                    if (response.success) {
                        FlavorForos.showNotice(response.data.message, 'success');
                    } else {
                        FlavorForos.showNotice(response.data.message || FlavorForos.config.strings.error, 'error');
                    }
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
        FlavorForos.init();
    });

    // CSS para notificaciones y spinner
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
