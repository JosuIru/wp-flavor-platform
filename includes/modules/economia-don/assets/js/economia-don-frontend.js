/**
 * JavaScript frontend para Economía del Don
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var FlavorEconomiaDon = {
        config: window.flavorEconomiaDonConfig || {},

        init: function() {
            this.bindEvents();
            this.initFileUpload();
        },

        bindEvents: function() {
            // Ofrecer don
            $(document).on('submit', '#flavor-form-ofrecer-don', this.ofrecerDon.bind(this));

            // Solicitar don
            $(document).on('click', '.flavor-solicitar-don', this.solicitarDon.bind(this));

            // Confirmar entrega
            $(document).on('click', '.flavor-confirmar-entrega', this.confirmarEntrega.bind(this));

            // Agradecer
            $(document).on('submit', '#flavor-form-agradecer', this.agradecer.bind(this));

            // Tabs mis dones
            $(document).on('click', '.flavor-mis-dones-tab', this.cambiarTab.bind(this));

            // Filtros
            $(document).on('change', '#filtro-tipo-don', this.filtrarDones.bind(this));

            // Cancelar don
            $(document).on('click', '.flavor-cancelar-don', this.cancelarDon.bind(this));
        },

        initFileUpload: function() {
            var $preview = $('#preview-imagen-don');
            var $input = $('#imagen_don');

            if (!$input.length) return;

            $input.on('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $preview.html('<img src="' + e.target.result + '" alt="Preview">').show();
                    };
                    reader.readAsDataURL(file);
                }
            });
        },

        ofrecerDon: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $btn = $form.find('button[type="submit"]');
            var btnText = $btn.html();

            var formData = new FormData($form[0]);
            formData.append('action', 'flavor_economia_don_ofrecer');

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + this.config.strings.procesando);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        FlavorEconomiaDon.showNotice(response.data.message, 'success');
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1500);
                        }
                    } else {
                        FlavorEconomiaDon.showNotice(response.data.message || FlavorEconomiaDon.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorEconomiaDon.showNotice(FlavorEconomiaDon.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        solicitarDon: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var donId = $btn.data('don-id');
            var btnText = $btn.html();

            // Mostrar modal de mensaje opcional
            var mensaje = prompt(this.config.strings.mensajeSolicitud || '¿Quieres añadir un mensaje al donante? (opcional)', '');

            if (mensaje === null) return; // Cancelado

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_economia_don_solicitar',
                    nonce: this.config.nonce,
                    don_id: donId,
                    mensaje: mensaje
                },
                success: function(response) {
                    if (response.success) {
                        FlavorEconomiaDon.showNotice(response.data.message, 'success');
                        $btn.removeClass('flavor-btn-primary').addClass('flavor-btn-success')
                            .html('<span class="dashicons dashicons-yes"></span> Solicitado');
                    } else {
                        FlavorEconomiaDon.showNotice(response.data.message || FlavorEconomiaDon.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorEconomiaDon.showNotice(FlavorEconomiaDon.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        confirmarEntrega: function(e) {
            e.preventDefault();

            if (!confirm(this.config.strings.confirmarEntrega || '¿Confirmas que has entregado este don?')) {
                return;
            }

            var $btn = $(e.currentTarget);
            var donId = $btn.data('don-id');
            var receptorId = $btn.data('receptor-id');

            $btn.prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_economia_don_confirmar_entrega',
                    nonce: this.config.nonce,
                    don_id: donId,
                    receptor_id: receptorId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorEconomiaDon.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        FlavorEconomiaDon.showNotice(response.data.message || FlavorEconomiaDon.config.strings.error, 'error');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    FlavorEconomiaDon.showNotice(FlavorEconomiaDon.config.strings.error, 'error');
                    $btn.prop('disabled', false);
                }
            });
        },

        agradecer: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $btn = $form.find('button[type="submit"]');
            var btnText = $btn.html();

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=flavor_economia_don_agradecer',
                success: function(response) {
                    if (response.success) {
                        FlavorEconomiaDon.showNotice(response.data.message, 'success');
                        $form.find('textarea').val('');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        FlavorEconomiaDon.showNotice(response.data.message || FlavorEconomiaDon.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorEconomiaDon.showNotice(FlavorEconomiaDon.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        cambiarTab: function(e) {
            e.preventDefault();

            var $tab = $(e.currentTarget);
            var target = $tab.data('target');

            $('.flavor-mis-dones-tab').removeClass('activo');
            $tab.addClass('activo');

            $('.flavor-mis-dones-contenido').hide();
            $('#' + target).show();
        },

        filtrarDones: function(e) {
            var tipo = $(e.target).val();

            if (!tipo) {
                $('.flavor-don-card').show();
            } else {
                $('.flavor-don-card').hide();
                $('.flavor-don-card[data-tipo="' + tipo + '"]').show();
            }
        },

        cancelarDon: function(e) {
            e.preventDefault();

            if (!confirm(this.config.strings.confirmarCancelar || '¿Seguro que deseas cancelar este don?')) {
                return;
            }

            var $btn = $(e.currentTarget);
            var donId = $btn.data('don-id');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_economia_don_cancelar',
                    nonce: this.config.nonce,
                    don_id: donId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorEconomiaDon.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        FlavorEconomiaDon.showNotice(response.data.message || FlavorEconomiaDon.config.strings.error, 'error');
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
        FlavorEconomiaDon.init();
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
        #preview-imagen-don {
            margin-top: 0.5rem;
            display: none;
        }
        #preview-imagen-don img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            object-fit: cover;
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
