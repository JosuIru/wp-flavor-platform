/**
 * Carpooling Dashboard Tab Scripts
 *
 * Funcionalidad JavaScript para los tabs del dashboard de usuario de Carpooling
 */

(function($) {
    'use strict';

    /**
     * Carpooling Dashboard Manager
     */
    var CarpoolingDashboard = {
        /**
         * Inicializacion
         */
        init: function() {
            this.bindEvents();
            this.initMiniMaps();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Cancelar viaje (como conductor)
            $(document).on('click', '.btn-cancelar-viaje', this.handleCancelarViaje.bind(this));

            // Finalizar viaje (como conductor)
            $(document).on('click', '.btn-finalizar-viaje', this.handleFinalizarViaje.bind(this));

            // Cancelar reserva (como pasajero)
            $(document).on('click', '.btn-cancelar-reserva', this.handleCancelarReserva.bind(this));

            // Valorar viaje
            $(document).on('click', '.btn-valorar', this.handleValorar.bind(this));

            // Cerrar modal de valoracion
            $(document).on('click', '.modal-valoracion-overlay, .btn-cerrar-modal', this.cerrarModalValoracion.bind(this));
            $(document).on('click', '.modal-valoracion', function(e) {
                e.stopPropagation();
            });

            // Seleccionar estrellas
            $(document).on('click', '.valoracion-estrellas .estrella', this.handleSeleccionarEstrella.bind(this));

            // Enviar valoracion
            $(document).on('submit', '#form-valoracion', this.handleEnviarValoracion.bind(this));
        },

        /**
         * Cancelar viaje
         */
        handleCancelarViaje: function(e) {
            e.preventDefault();
            var $boton = $(e.currentTarget);
            var viajeId = $boton.data('viaje-id');
            var self = this;

            if (!confirm(carpoolingDashboard.i18n.confirmarCancelar)) {
                return;
            }

            $boton.prop('disabled', true).addClass('cargando');

            $.ajax({
                url: carpoolingDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'carpooling_dashboard_cancelar_viaje',
                    nonce: carpoolingDashboard.nonce,
                    viaje_id: viajeId
                },
                success: function(response) {
                    if (response.success) {
                        // Remover la card del viaje
                        $boton.closest('.carpooling-viaje-card').fadeOut(300, function() {
                            $(this).remove();
                            self.actualizarContadorBadge('carpooling-mis-viajes');
                        });
                        self.mostrarNotificacion(response.data.message, 'success');
                    } else {
                        self.mostrarNotificacion(response.data.message || carpoolingDashboard.i18n.error, 'error');
                        $boton.prop('disabled', false).removeClass('cargando');
                    }
                },
                error: function() {
                    self.mostrarNotificacion(carpoolingDashboard.i18n.error, 'error');
                    $boton.prop('disabled', false).removeClass('cargando');
                }
            });
        },

        /**
         * Finalizar viaje
         */
        handleFinalizarViaje: function(e) {
            e.preventDefault();
            var $boton = $(e.currentTarget);
            var viajeId = $boton.data('viaje-id');
            var self = this;

            if (!confirm(carpoolingDashboard.i18n.confirmarFinalizar)) {
                return;
            }

            $boton.prop('disabled', true).addClass('cargando');

            $.ajax({
                url: carpoolingDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'carpooling_dashboard_finalizar_viaje',
                    nonce: carpoolingDashboard.nonce,
                    viaje_id: viajeId
                },
                success: function(response) {
                    if (response.success) {
                        // Mover a historial o actualizar estado
                        $boton.closest('.carpooling-viaje-card')
                            .removeClass('estado-activo estado-completo')
                            .addClass('estado-finalizado')
                            .find('.viaje-acciones').html('<span class="estado estado-finalizado">Finalizado</span>');

                        self.mostrarNotificacion(response.data.message, 'success');
                        self.actualizarContadorBadge('carpooling-mis-viajes');
                    } else {
                        self.mostrarNotificacion(response.data.message || carpoolingDashboard.i18n.error, 'error');
                        $boton.prop('disabled', false).removeClass('cargando');
                    }
                },
                error: function() {
                    self.mostrarNotificacion(carpoolingDashboard.i18n.error, 'error');
                    $boton.prop('disabled', false).removeClass('cargando');
                }
            });
        },

        /**
         * Cancelar reserva
         */
        handleCancelarReserva: function(e) {
            e.preventDefault();
            var $boton = $(e.currentTarget);
            var reservaId = $boton.data('reserva-id');
            var self = this;

            if (!confirm(carpoolingDashboard.i18n.confirmarCancelarReserva)) {
                return;
            }

            $boton.prop('disabled', true).addClass('cargando');

            $.ajax({
                url: carpoolingDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'carpooling_dashboard_cancelar_reserva',
                    nonce: carpoolingDashboard.nonce,
                    reserva_id: reservaId
                },
                success: function(response) {
                    if (response.success) {
                        $boton.closest('.carpooling-reserva-card').fadeOut(300, function() {
                            $(this).remove();
                            self.actualizarContadorBadge('carpooling-mis-reservas');
                        });
                        self.mostrarNotificacion(response.data.message, 'success');
                    } else {
                        self.mostrarNotificacion(response.data.message || carpoolingDashboard.i18n.error, 'error');
                        $boton.prop('disabled', false).removeClass('cargando');
                    }
                },
                error: function() {
                    self.mostrarNotificacion(carpoolingDashboard.i18n.error, 'error');
                    $boton.prop('disabled', false).removeClass('cargando');
                }
            });
        },

        /**
         * Abrir modal de valoracion
         */
        handleValorar: function(e) {
            e.preventDefault();
            var $boton = $(e.currentTarget);
            var reservaId = $boton.data('reserva-id');
            var conductorId = $boton.data('conductor-id');

            this.abrirModalValoracion(reservaId, conductorId);
        },

        /**
         * Crear y mostrar modal de valoracion
         */
        abrirModalValoracion: function(reservaId, conductorId) {
            var modalHtml = '\
                <div class="modal-valoracion-overlay">\
                    <div class="modal-valoracion">\
                        <button type="button" class="btn-cerrar-modal">&times;</button>\
                        <h3>Valora tu viaje</h3>\
                        <form id="form-valoracion">\
                            <input type="hidden" name="reserva_id" value="' + reservaId + '">\
                            <input type="hidden" name="conductor_id" value="' + conductorId + '">\
                            <input type="hidden" name="puntuacion" value="0">\
                            \
                            <div class="valoracion-estrellas">\
                                <span class="estrella" data-valor="1"><span class="dashicons dashicons-star-empty"></span></span>\
                                <span class="estrella" data-valor="2"><span class="dashicons dashicons-star-empty"></span></span>\
                                <span class="estrella" data-valor="3"><span class="dashicons dashicons-star-empty"></span></span>\
                                <span class="estrella" data-valor="4"><span class="dashicons dashicons-star-empty"></span></span>\
                                <span class="estrella" data-valor="5"><span class="dashicons dashicons-star-empty"></span></span>\
                            </div>\
                            \
                            <div class="valoracion-comentario">\
                                <label for="valoracion-texto">Comentario (opcional)</label>\
                                <textarea id="valoracion-texto" name="comentario" rows="3" placeholder="Comparte tu experiencia..."></textarea>\
                            </div>\
                            \
                            <div class="valoracion-acciones">\
                                <button type="button" class="btn btn-outline btn-cerrar-modal">Cancelar</button>\
                                <button type="submit" class="btn btn-primary btn-enviar-valoracion" disabled>Enviar valoracion</button>\
                            </div>\
                        </form>\
                    </div>\
                </div>';

            $('body').append(modalHtml);

            // Agregar estilos del modal si no existen
            if (!$('#modal-valoracion-styles').length) {
                var styles = '\
                    <style id="modal-valoracion-styles">\
                        .modal-valoracion-overlay {\
                            position: fixed;\
                            top: 0;\
                            left: 0;\
                            right: 0;\
                            bottom: 0;\
                            background: rgba(0, 0, 0, 0.5);\
                            display: flex;\
                            align-items: center;\
                            justify-content: center;\
                            z-index: 99999;\
                        }\
                        .modal-valoracion {\
                            background: #fff;\
                            border-radius: 12px;\
                            padding: 2rem;\
                            max-width: 400px;\
                            width: 90%;\
                            position: relative;\
                        }\
                        .modal-valoracion .btn-cerrar-modal {\
                            position: absolute;\
                            top: 1rem;\
                            right: 1rem;\
                            background: none;\
                            border: none;\
                            font-size: 1.5rem;\
                            cursor: pointer;\
                            color: #6b7280;\
                        }\
                        .modal-valoracion h3 {\
                            margin: 0 0 1.5rem 0;\
                            text-align: center;\
                        }\
                        .valoracion-estrellas {\
                            display: flex;\
                            justify-content: center;\
                            gap: 0.5rem;\
                            margin-bottom: 1.5rem;\
                        }\
                        .valoracion-estrellas .estrella {\
                            cursor: pointer;\
                            transition: transform 0.1s;\
                        }\
                        .valoracion-estrellas .estrella:hover {\
                            transform: scale(1.2);\
                        }\
                        .valoracion-estrellas .estrella .dashicons {\
                            font-size: 32px;\
                            width: 32px;\
                            height: 32px;\
                            color: #d1d5db;\
                        }\
                        .valoracion-estrellas .estrella.activa .dashicons,\
                        .valoracion-estrellas .estrella.hover .dashicons {\
                            color: #f59e0b;\
                        }\
                        .valoracion-estrellas .estrella.activa .dashicons {\
                            content: "\\f155";\
                        }\
                        .valoracion-comentario {\
                            margin-bottom: 1.5rem;\
                        }\
                        .valoracion-comentario label {\
                            display: block;\
                            margin-bottom: 0.5rem;\
                            font-weight: 500;\
                        }\
                        .valoracion-comentario textarea {\
                            width: 100%;\
                            padding: 0.75rem;\
                            border: 1px solid #e5e7eb;\
                            border-radius: 8px;\
                            resize: vertical;\
                        }\
                        .valoracion-acciones {\
                            display: flex;\
                            gap: 0.75rem;\
                            justify-content: flex-end;\
                        }\
                        .valoracion-acciones .btn {\
                            padding: 0.625rem 1.25rem;\
                            border-radius: 8px;\
                            font-weight: 500;\
                            cursor: pointer;\
                        }\
                        .valoracion-acciones .btn-outline {\
                            background: transparent;\
                            border: 1px solid #e5e7eb;\
                        }\
                        .valoracion-acciones .btn-primary {\
                            background: #3b82f6;\
                            color: #fff;\
                            border: none;\
                        }\
                        .valoracion-acciones .btn-primary:disabled {\
                            opacity: 0.5;\
                            cursor: not-allowed;\
                        }\
                    </style>';
                $('head').append(styles);
            }
        },

        /**
         * Cerrar modal de valoracion
         */
        cerrarModalValoracion: function() {
            $('.modal-valoracion-overlay').remove();
        },

        /**
         * Seleccionar estrella
         */
        handleSeleccionarEstrella: function(e) {
            var $estrella = $(e.currentTarget);
            var valor = $estrella.data('valor');
            var $contenedor = $estrella.closest('.valoracion-estrellas');

            // Actualizar visualizacion
            $contenedor.find('.estrella').each(function() {
                var $this = $(this);
                var esteValor = $this.data('valor');

                if (esteValor <= valor) {
                    $this.addClass('activa')
                         .find('.dashicons')
                         .removeClass('dashicons-star-empty')
                         .addClass('dashicons-star-filled');
                } else {
                    $this.removeClass('activa')
                         .find('.dashicons')
                         .removeClass('dashicons-star-filled')
                         .addClass('dashicons-star-empty');
                }
            });

            // Actualizar valor oculto
            $contenedor.closest('form').find('input[name="puntuacion"]').val(valor);

            // Habilitar boton de enviar
            $contenedor.closest('form').find('.btn-enviar-valoracion').prop('disabled', false);
        },

        /**
         * Enviar valoracion
         */
        handleEnviarValoracion: function(e) {
            e.preventDefault();
            var $form = $(e.currentTarget);
            var $boton = $form.find('.btn-enviar-valoracion');
            var self = this;

            var datos = {
                action: 'carpooling_dashboard_valorar',
                nonce: carpoolingDashboard.nonce,
                reserva_id: $form.find('input[name="reserva_id"]').val(),
                puntuacion: $form.find('input[name="puntuacion"]').val(),
                comentario: $form.find('textarea[name="comentario"]').val()
            };

            if (parseInt(datos.puntuacion) < 1) {
                self.mostrarNotificacion('Selecciona una puntuacion', 'error');
                return;
            }

            $boton.prop('disabled', true).text('Enviando...');

            $.ajax({
                url: carpoolingDashboard.ajaxUrl,
                type: 'POST',
                data: datos,
                success: function(response) {
                    if (response.success) {
                        self.cerrarModalValoracion();
                        self.mostrarNotificacion(carpoolingDashboard.i18n.gracias, 'success');

                        // Actualizar UI - ocultar boton valorar
                        $('.btn-valorar[data-reserva-id="' + datos.reserva_id + '"]')
                            .replaceWith('<span class="valorado"><span class="dashicons dashicons-star-filled"></span>' + datos.puntuacion + '</span>');
                    } else {
                        self.mostrarNotificacion(response.data.message || carpoolingDashboard.i18n.error, 'error');
                        $boton.prop('disabled', false).text('Enviar valoracion');
                    }
                },
                error: function() {
                    self.mostrarNotificacion(carpoolingDashboard.i18n.error, 'error');
                    $boton.prop('disabled', false).text('Enviar valoracion');
                }
            });
        },

        /**
         * Inicializar mini mapas
         */
        initMiniMaps: function() {
            if (typeof L === 'undefined') {
                return;
            }

            var self = this;

            // Mapa del proximo viaje en estadisticas
            var $mapaProximo = $('#mapa-proximo-viaje');
            if ($mapaProximo.length && $mapaProximo.data('origen-lat')) {
                self.crearMiniMapa($mapaProximo[0]);
            }

            // Mini mapas en las cards de viajes
            $('.viaje-mapa-mini-thumb').each(function() {
                self.crearMiniMapaThumbnail(this);
            });
        },

        /**
         * Crear mini mapa
         */
        crearMiniMapa: function(container) {
            var origenLat = parseFloat(container.dataset.origenLat);
            var origenLng = parseFloat(container.dataset.origenLng);
            var destinoLat = parseFloat(container.dataset.destinoLat);
            var destinoLng = parseFloat(container.dataset.destinoLng);

            if (isNaN(origenLat) || isNaN(destinoLat)) {
                return;
            }

            var mapa = L.map(container, {
                scrollWheelZoom: false,
                dragging: false,
                zoomControl: false,
                attributionControl: false
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapa);

            // Marcadores personalizados
            var iconoOrigen = L.divIcon({
                className: 'mapa-marker mapa-marker-origen',
                html: '<div style="background:#10b981;width:12px;height:12px;border-radius:50%;border:2px solid #fff;box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            });

            var iconoDestino = L.divIcon({
                className: 'mapa-marker mapa-marker-destino',
                html: '<div style="background:#ef4444;width:12px;height:12px;border-radius:50%;border:2px solid #fff;box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            });

            L.marker([origenLat, origenLng], {icon: iconoOrigen}).addTo(mapa);
            L.marker([destinoLat, destinoLng], {icon: iconoDestino}).addTo(mapa);

            // Linea de ruta
            L.polyline([
                [origenLat, origenLng],
                [destinoLat, destinoLng]
            ], {
                color: '#3b82f6',
                weight: 3,
                dashArray: '8, 8',
                opacity: 0.8
            }).addTo(mapa);

            // Ajustar vista
            var bounds = L.latLngBounds([
                [origenLat, origenLng],
                [destinoLat, destinoLng]
            ]);
            mapa.fitBounds(bounds, {padding: [20, 20]});
        },

        /**
         * Crear mini mapa thumbnail (estatico/imagen)
         */
        crearMiniMapaThumbnail: function(container) {
            var origenLat = parseFloat(container.dataset.origenLat);
            var origenLng = parseFloat(container.dataset.origenLng);
            var destinoLat = parseFloat(container.dataset.destinoLat);
            var destinoLng = parseFloat(container.dataset.destinoLng);

            if (isNaN(origenLat) || isNaN(destinoLat)) {
                return;
            }

            // Calcular centro
            var centerLat = (origenLat + destinoLat) / 2;
            var centerLng = (origenLng + destinoLng) / 2;

            // Usar imagen estatica de OpenStreetMap
            var zoom = 10;
            var width = 80;
            var height = 60;

            // Usar API de tiles estaticos
            var imgUrl = 'https://staticmap.openstreetmap.de/staticmap.php' +
                '?center=' + centerLat + ',' + centerLng +
                '&zoom=' + zoom +
                '&size=' + width + 'x' + height +
                '&maptype=mapnik' +
                '&markers=' + origenLat + ',' + origenLng + ',lightblue' +
                '&markers=' + destinoLat + ',' + destinoLng + ',red';

            $(container).css({
                'background-image': 'url(' + imgUrl + ')',
                'background-size': 'cover',
                'background-position': 'center'
            });
        },

        /**
         * Actualizar contador en badge del tab
         */
        actualizarContadorBadge: function(tabId) {
            var $badge = $('[data-tab="' + tabId + '"] .tab-badge, #tab-' + tabId + ' .tab-badge');
            if ($badge.length) {
                var count = parseInt($badge.text()) || 0;
                if (count > 0) {
                    count--;
                    if (count === 0) {
                        $badge.remove();
                    } else {
                        $badge.text(count);
                    }
                }
            }
        },

        /**
         * Mostrar notificacion
         */
        mostrarNotificacion: function(mensaje, tipo) {
            tipo = tipo || 'info';

            // Remover notificaciones anteriores
            $('.carpooling-notificacion').remove();

            var iconos = {
                success: 'yes-alt',
                error: 'warning',
                info: 'info'
            };

            var $notificacion = $('\
                <div class="carpooling-notificacion carpooling-notificacion-' + tipo + '">\
                    <span class="dashicons dashicons-' + iconos[tipo] + '"></span>\
                    <span class="mensaje">' + mensaje + '</span>\
                    <button type="button" class="cerrar">&times;</button>\
                </div>');

            // Estilos inline si no existen
            if (!$('#notificacion-styles').length) {
                $('head').append('\
                    <style id="notificacion-styles">\
                        .carpooling-notificacion {\
                            position: fixed;\
                            top: 32px;\
                            right: 20px;\
                            z-index: 99999;\
                            display: flex;\
                            align-items: center;\
                            gap: 0.75rem;\
                            padding: 1rem 1.25rem;\
                            border-radius: 8px;\
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);\
                            animation: slideIn 0.3s ease;\
                            max-width: 400px;\
                        }\
                        .carpooling-notificacion-success {\
                            background: #ecfdf5;\
                            border: 1px solid #10b981;\
                            color: #065f46;\
                        }\
                        .carpooling-notificacion-error {\
                            background: #fef2f2;\
                            border: 1px solid #ef4444;\
                            color: #991b1b;\
                        }\
                        .carpooling-notificacion-info {\
                            background: #eff6ff;\
                            border: 1px solid #3b82f6;\
                            color: #1e40af;\
                        }\
                        .carpooling-notificacion .cerrar {\
                            background: none;\
                            border: none;\
                            font-size: 1.25rem;\
                            cursor: pointer;\
                            opacity: 0.6;\
                            margin-left: auto;\
                        }\
                        .carpooling-notificacion .cerrar:hover {\
                            opacity: 1;\
                        }\
                        @keyframes slideIn {\
                            from { transform: translateX(100%); opacity: 0; }\
                            to { transform: translateX(0); opacity: 1; }\
                        }\
                    </style>');
            }

            $('body').append($notificacion);

            // Cerrar al hacer click
            $notificacion.find('.cerrar').on('click', function() {
                $notificacion.fadeOut(200, function() {
                    $(this).remove();
                });
            });

            // Auto cerrar despues de 5 segundos
            setTimeout(function() {
                $notificacion.fadeOut(200, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Inicializar cuando el DOM este listo
    $(document).ready(function() {
        CarpoolingDashboard.init();
    });

    // Re-inicializar mapas cuando se cambia de tab (por si se carga con AJAX)
    $(document).on('flavor_dashboard_tab_loaded', function(e, tabId) {
        if (tabId && tabId.indexOf('carpooling') !== -1) {
            setTimeout(function() {
                CarpoolingDashboard.initMiniMaps();
            }, 100);
        }
    });

})(jQuery);
