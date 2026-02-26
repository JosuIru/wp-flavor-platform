/**
 * JavaScript frontend para Círculos de Cuidados
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var FlavorCirculosCuidados = {
        config: window.flavorCirculosCuidadosConfig || {},
        mapa: null,

        init: function() {
            this.bindEvents();
            this.initMapa();
        },

        bindEvents: function() {
            // Crear círculo
            $(document).on('submit', '#flavor-form-crear-circulo', this.crearCirculo.bind(this));

            // Unirse a círculo
            $(document).on('click', '.flavor-unirse-circulo', this.unirseCirculo.bind(this));

            // Salir de círculo
            $(document).on('click', '.flavor-salir-circulo', this.salirCirculo.bind(this));

            // Publicar necesidad
            $(document).on('submit', '#flavor-form-necesidad', this.publicarNecesidad.bind(this));

            // Responder necesidad
            $(document).on('click', '.flavor-responder-necesidad', this.responderNecesidad.bind(this));

            // Confirmar horas
            $(document).on('click', '.flavor-confirmar-horas', this.confirmarHoras.bind(this));

            // Filtros
            $(document).on('change', '#filtro-tipo-circulo', this.filtrarCirculos.bind(this));
        },

        initMapa: function() {
            var $mapa = $('#mapa-circulos');
            if (!$mapa.length || typeof L === 'undefined') return;

            var lat = parseFloat(this.config.lat) || 40.4168;
            var lng = parseFloat(this.config.lng) || -3.7038;

            this.mapa = L.map('mapa-circulos').setView([lat, lng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(this.mapa);

            // Cargar marcadores
            this.cargarMarcadores();
        },

        cargarMarcadores: function() {
            var self = this;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_circulos_obtener_mapa'
                },
                success: function(response) {
                    if (response.success && response.data.circulos) {
                        response.data.circulos.forEach(function(circulo) {
                            if (circulo.latitud && circulo.longitud) {
                                var icono = L.divIcon({
                                    className: 'flavor-marker-circulo',
                                    html: '<div class="flavor-marker-pin" style="background: #ec4899;"><span class="dashicons dashicons-heart"></span></div>',
                                    iconSize: [30, 42],
                                    iconAnchor: [15, 42]
                                });

                                var marker = L.marker([circulo.latitud, circulo.longitud], {icon: icono}).addTo(self.mapa);

                                marker.bindPopup(
                                    '<div class="flavor-popup-circulo">' +
                                    '<h4>' + circulo.nombre + '</h4>' +
                                    '<p>' + circulo.tipo + '</p>' +
                                    '<a href="' + circulo.url + '" class="flavor-btn flavor-btn-sm flavor-btn-primary">Ver círculo</a>' +
                                    '</div>'
                                );
                            }
                        });
                    }
                }
            });
        },

        crearCirculo: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $btn = $form.find('button[type="submit"]');
            var btnText = $btn.html();

            var formData = new FormData($form[0]);
            formData.append('action', 'flavor_circulos_crear');

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + this.config.strings.procesando);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        FlavorCirculosCuidados.showNotice(response.data.message, 'success');
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1500);
                        }
                    } else {
                        FlavorCirculosCuidados.showNotice(response.data.message || FlavorCirculosCuidados.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorCirculosCuidados.showNotice(FlavorCirculosCuidados.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        unirseCirculo: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var circuloId = $btn.data('circulo-id');
            var btnText = $btn.html();

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_circulos_unirse',
                    nonce: this.config.nonce,
                    circulo_id: circuloId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorCirculosCuidados.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        FlavorCirculosCuidados.showNotice(response.data.message || FlavorCirculosCuidados.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorCirculosCuidados.showNotice(FlavorCirculosCuidados.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        salirCirculo: function(e) {
            e.preventDefault();

            if (!confirm(this.config.strings.confirmarSalir || '¿Seguro que deseas salir de este círculo?')) {
                return;
            }

            var $btn = $(e.currentTarget);
            var circuloId = $btn.data('circulo-id');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_circulos_salir',
                    nonce: this.config.nonce,
                    circulo_id: circuloId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorCirculosCuidados.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        FlavorCirculosCuidados.showNotice(response.data.message || FlavorCirculosCuidados.config.strings.error, 'error');
                    }
                }
            });
        },

        publicarNecesidad: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $btn = $form.find('button[type="submit"]');
            var btnText = $btn.html();

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + this.config.strings.procesando);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=flavor_circulos_publicar_necesidad',
                success: function(response) {
                    if (response.success) {
                        FlavorCirculosCuidados.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        FlavorCirculosCuidados.showNotice(response.data.message || FlavorCirculosCuidados.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorCirculosCuidados.showNotice(FlavorCirculosCuidados.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        responderNecesidad: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var necesidadId = $btn.data('necesidad-id');
            var btnText = $btn.html();

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_circulos_responder_necesidad',
                    nonce: this.config.nonce,
                    necesidad_id: necesidadId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorCirculosCuidados.showNotice(response.data.message, 'success');
                        $btn.removeClass('flavor-btn-primary').addClass('flavor-btn-success')
                            .html('<span class="dashicons dashicons-yes"></span> Me ofrezco');
                    } else {
                        FlavorCirculosCuidados.showNotice(response.data.message || FlavorCirculosCuidados.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorCirculosCuidados.showNotice(FlavorCirculosCuidados.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        confirmarHoras: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var apoyoId = $btn.data('apoyo-id');
            var horas = $btn.data('horas') || prompt('¿Cuántas horas de apoyo has dado?', '1');

            if (!horas) return;

            $btn.prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_circulos_confirmar_horas',
                    nonce: this.config.nonce,
                    apoyo_id: apoyoId,
                    horas: horas
                },
                success: function(response) {
                    if (response.success) {
                        FlavorCirculosCuidados.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        FlavorCirculosCuidados.showNotice(response.data.message || FlavorCirculosCuidados.config.strings.error, 'error');
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        filtrarCirculos: function(e) {
            var tipo = $(e.target).val();

            if (!tipo) {
                $('.flavor-circulo-card').show();
            } else {
                $('.flavor-circulo-card').hide();
                $('.flavor-circulo-card[data-tipo="' + tipo + '"]').show();
            }
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
        FlavorCirculosCuidados.init();
    });

    // CSS para notificaciones y marcadores
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
        .flavor-marker-pin {
            width: 30px;
            height: 30px;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .flavor-marker-pin .dashicons {
            transform: rotate(45deg);
            color: #fff;
            font-size: 14px;
        }
        .flavor-popup-circulo h4 {
            margin: 0 0 0.5rem;
        }
        .flavor-popup-circulo p {
            margin: 0 0 0.5rem;
            color: #6b7280;
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
