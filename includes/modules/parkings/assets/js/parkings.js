/**
 * Parkings Module - Frontend JavaScript
 *
 * @package FlavorPlatform
 * @subpackage Modules/Parkings
 * @since 3.1.0
 */

(function($) {
    'use strict';

    const parkingsConfig = typeof flavorParkingsData !== 'undefined'
        ? flavorParkingsData
        : (typeof flavorParkingsConfig !== 'undefined' ? flavorParkingsConfig : (typeof flavorParkings !== 'undefined' ? flavorParkings : {}));

    // Objeto principal del módulo
    window.FlavorParkings = {

        map: null,
        markers: [],

        /**
         * Inicializa el módulo
         */
        init: function() {
            this.bindEvents();
            this.initMapa();
        },

        /**
         * Bindea eventos
         */
        bindEvents: function() {
            // Reservar plaza
            $(document).on('submit', '.flavor-parking-reserva form', this.handleReserva.bind(this));

            // Cancelar reserva
            $(document).on('click', '.flavor-parking-cancelar', this.handleCancelacion.bind(this));

            // Filtros
            $(document).on('change', '.flavor-parkings-filtros select', this.handleFiltroChange.bind(this));

            // Ver disponibilidad de parking
            $(document).on('click', '.btn-ver-parking', this.handleVerDisponibilidad.bind(this));

            // Actualizar disponibilidad en tiempo real
            if (parkingsConfig.autoRefresh) {
                setInterval(this.actualizarDisponibilidad.bind(this), 30000);
            }
        },

        /**
         * Inicializa el mapa
         */
        initMapa: function() {
            var $mapContainer = $('#flavor-parkings-map');
            if (!$mapContainer.length || typeof L === 'undefined') {
                return;
            }

            // Crear mapa
            this.map = L.map('flavor-parkings-map').setView(
                [parkingsConfig.defaultLat || 40.4168, parkingsConfig.defaultLng ||  -3.7038],
                parkingsConfig.defaultZoom || 13
            );

            // Añadir capa de tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.map);

            // Cargar parkings
            this.cargarParkings();
        },

        /**
         * Carga parkings en el mapa
         */
        cargarParkings: function() {
            var self = this;

            $.ajax({
                url: parkingsConfig.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'flavor_parkings_lista'
                },
                success: function(response) {
                    if (response.success && response.data.parkings) {
                        self.mostrarParkingsEnMapa(response.data.parkings);
                    }
                }
            });
        },

        /**
         * Muestra parkings en el mapa
         */
        mostrarParkingsEnMapa: function(parkings) {
            var self = this;

            // Limpiar markers existentes
            this.markers.forEach(function(marker) {
                self.map.removeLayer(marker);
            });
            this.markers = [];

            // Añadir nuevos markers
            parkings.forEach(function(parking) {
                if (parking.lat && parking.lng) {
                    var iconColor = parking.disponibles > 5 ? 'green' : (parking.disponibles > 0 ? 'orange' : 'red');

                    var icon = L.divIcon({
                        className: 'flavor-parking-marker flavor-parking-marker--' + iconColor,
                        html: '<span class="plazas">' + parking.disponibles + '</span>',
                        iconSize: [40, 40]
                    });

                    var marker = L.marker([parking.lat, parking.lng], { icon: icon })
                        .addTo(self.map)
                        .bindPopup(self.crearPopupParking(parking));

                    self.markers.push(marker);
                }
            });

            // Ajustar vista si hay markers
            if (this.markers.length > 0) {
                var group = L.featureGroup(this.markers);
                this.map.fitBounds(group.getBounds().pad(0.1));
            }
        },

        /**
         * Crea el contenido del popup de un parking
         */
        crearPopupParking: function(parking) {
            return '<div class="flavor-parking-popup">' +
                '<h4>' + parking.nombre + '</h4>' +
                '<p class="direccion">' + parking.direccion + '</p>' +
                '<p class="disponibilidad">' +
                    '<strong>' + parking.disponibles + '</strong> plazas disponibles de ' + parking.total +
                '</p>' +
                '<p class="precio">' + parking.precio_hora + '€/hora</p>' +
                '<a href="' + parking.url + '" class="flavor-btn flavor-btn--small">Ver detalles</a>' +
            '</div>';
        },

        /**
         * Maneja la reserva de plaza
         */
        handleReserva: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $button = $form.find('button[type="submit"]');
            var originalText = $button.text();

            $button.prop('disabled', true).text((parkingsConfig.i18n || parkingsConfig.strings || {}).procesando || 'Procesando...');

            $.ajax({
                url: parkingsConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_parking_reservar',
                    nonce: parkingsConfig.nonce,
                    parking_id: $form.find('[name="parking_id"]').val(),
                    fecha_entrada: $form.find('[name="fecha_entrada"]').val(),
                    fecha_salida: $form.find('[name="fecha_salida"]').val(),
                    matricula: $form.find('[name="matricula"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        FlavorParkings.mostrarMensaje('success', response.data.mensaje);
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                    } else {
                        FlavorParkings.mostrarMensaje('error', response.data.mensaje || (parkingsConfig.i18n || parkingsConfig.strings || {}).error);
                    }
                },
                error: function() {
                    FlavorParkings.mostrarMensaje('error', (parkingsConfig.i18n || parkingsConfig.strings || {}).errorConexion || 'Error de conexión');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Maneja cancelación de reserva
         */
        handleCancelacion: function(e) {
            e.preventDefault();

            if (!confirm((parkingsConfig.i18n || parkingsConfig.strings || {}).confirmarCancelacion || '¿Seguro que quieres cancelar esta reserva?')) {
                return;
            }

            var $button = $(e.target);
            var reservaId = $button.data('reserva-id');

            $button.prop('disabled', true);

            $.ajax({
                url: parkingsConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_parking_cancelar',
                    nonce: parkingsConfig.nonce,
                    reserva_id: reservaId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorParkings.mostrarMensaje('success', response.data.mensaje);
                        $button.closest('.flavor-reserva-item').fadeOut();
                    } else {
                        FlavorParkings.mostrarMensaje('error', response.data.mensaje);
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    FlavorParkings.mostrarMensaje('error', (parkingsConfig.i18n || parkingsConfig.strings || {}).errorConexion);
                    $button.prop('disabled', false);
                }
            });
        },

        /**
         * Maneja cambio de filtro
         */
        handleFiltroChange: function(e) {
            var filtros = {};
            $('.flavor-parkings-filtros select').each(function() {
                var $select = $(this);
                if ($select.val()) {
                    filtros[$select.attr('name')] = $select.val();
                }
            });

            this.filtrarParkings(filtros);
        },

        /**
         * Filtra parkings
         */
        filtrarParkings: function(filtros) {
            var $lista = $('.flavor-parkings-lista');
            $lista.addClass('loading');

            $.ajax({
                url: parkingsConfig.ajaxUrl,
                type: 'GET',
                data: $.extend({
                    action: 'flavor_parkings_lista'
                }, filtros),
                success: function(response) {
                    if (response.success) {
                        $lista.html(response.data.html);
                        if (FlavorParkings.map) {
                            FlavorParkings.mostrarParkingsEnMapa(response.data.parkings);
                        }
                    }
                },
                complete: function() {
                    $lista.removeClass('loading');
                }
            });
        },

        /**
         * Actualiza disponibilidad en tiempo real
         */
        actualizarDisponibilidad: function() {
            $.ajax({
                url: parkingsConfig.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'flavor_parkings_disponibilidad'
                },
                success: function(response) {
                    if (response.success && response.data.disponibilidad) {
                        response.data.disponibilidad.forEach(function(parking) {
                            var $card = $('[data-parking-id="' + parking.id + '"]');
                            if ($card.length) {
                                $card.find('.plazas-disponibles').text(parking.disponibles);

                                // Actualizar clase de estado
                                $card.removeClass('disponible lleno casi-lleno');
                                if (parking.disponibles === 0) {
                                    $card.addClass('lleno');
                                } else if (parking.disponibles <= 5) {
                                    $card.addClass('casi-lleno');
                                } else {
                                    $card.addClass('disponible');
                                }
                            }
                        });
                    }
                }
            });
        },

        /**
         * Maneja ver disponibilidad de parking
         */
        handleVerDisponibilidad: function(e) {
            e.preventDefault();

            var $button = $(e.target).closest('.btn-ver-parking');
            var parkingId = $button.data('parking-id');

            // Crear modal si no existe
            if (!$('#flavor-modal-disponibilidad').length) {
                $('body').append(
                    '<div id="flavor-modal-disponibilidad" class="flavor-modal" style="display:none;">' +
                        '<div class="flavor-modal-overlay"></div>' +
                        '<div class="flavor-modal-content">' +
                            '<button class="flavor-modal-close">&times;</button>' +
                            '<div class="flavor-modal-body"></div>' +
                        '</div>' +
                    '</div>'
                );

                $(document).on('click', '#flavor-modal-disponibilidad .flavor-modal-close, #flavor-modal-disponibilidad .flavor-modal-overlay', function() {
                    $('#flavor-modal-disponibilidad').fadeOut();
                });
            }

            var $modal = $('#flavor-modal-disponibilidad');
            var $body = $modal.find('.flavor-modal-body');

            $body.html('<div class="flavor-loading"><span class="dashicons dashicons-update spin"></span> Cargando...</div>');
            $modal.fadeIn();

            $.ajax({
                url: parkingsConfig.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'flavor_parkings_disponibilidad',
                    parking_id: parkingId
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var html = '<h3>' + (data.nombre || 'Parking') + '</h3>' +
                            '<div class="flavor-disponibilidad-info">' +
                                '<p><strong>Plazas libres:</strong> ' + (data.disponibles || 0) + ' de ' + (data.total || 0) + '</p>' +
                                '<p><strong>Ocupación:</strong> ' + (data.ocupacion || 0) + '%</p>' +
                                (data.precio_hora ? '<p><strong>Precio/hora:</strong> ' + data.precio_hora + '€</p>' : '') +
                                (data.precio_mes ? '<p><strong>Precio/mes:</strong> ' + data.precio_mes + '€</p>' : '') +
                            '</div>';
                        $body.html(html);
                    } else {
                        $body.html('<p class="flavor-error">No se pudo cargar la información.</p>');
                    }
                },
                error: function() {
                    $body.html('<p class="flavor-error">Error de conexión.</p>');
                }
            });
        },

        /**
         * Muestra mensaje de feedback
         */
        mostrarMensaje: function(tipo, mensaje) {
            var $mensaje = $('<div class="flavor-mensaje flavor-mensaje--' + tipo + '">' + mensaje + '</div>');

            $('.flavor-mensaje').remove();
            $('.flavor-parking-reserva, .flavor-parkings-lista').first().before($mensaje);

            setTimeout(function() {
                $mensaje.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        if ($('.flavor-parkings-lista, .flavor-parking-single, #flavor-parkings-map').length) {
            FlavorParkings.init();
        }
    });

})(jQuery);
