/**
 * JavaScript frontend para Bicicletas Compartidas
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var FlavorBicicletas = {
        config: window.flavorBicicletasConfig || {},
        mapa: null,
        marcadores: [],

        init: function() {
            this.bindEvents();
            this.initMapa();
            this.initTiempoTranscurrido();
        },

        bindEvents: function() {
            // Reservar bicicleta
            $(document).on('click', '.flavor-btn-reservar', this.reservarBicicleta.bind(this));

            // Devolver bicicleta (abrir modal)
            $(document).on('click', '.flavor-btn-devolver', this.abrirModalDevolucion.bind(this));

            // Confirmar devolución
            $(document).on('submit', '#form-devolver-bicicleta', this.devolverBicicleta.bind(this));

            // Reportar problema
            $(document).on('click', '.flavor-btn-reportar', this.reportarProblema.bind(this));

            // Cerrar modal
            $(document).on('click', '.flavor-modal-cerrar, .flavor-modal-cerrar-btn', this.cerrarModal.bind(this));

            // Usar ubicación para estaciones
            $(document).on('click', '.flavor-btn-ubicacion', this.usarUbicacion.bind(this));

            // Filtro de tipo
            $(document).on('change', '#filtro-tipo-bici', this.filtrarPorTipo.bind(this));

            // Click fuera del modal
            $(document).on('click', '.flavor-modal', function(e) {
                if ($(e.target).hasClass('flavor-modal')) {
                    FlavorBicicletas.cerrarModal();
                }
            });
        },

        initMapa: function() {
            var $mapaContainer = $('#flavor-mapa-estaciones');
            if (!$mapaContainer.length || typeof L === 'undefined') return;

            var lat = parseFloat($mapaContainer.data('lat')) || 40.4168;
            var lng = parseFloat($mapaContainer.data('lng')) || -3.7038;
            var zoom = parseInt($mapaContainer.data('zoom')) || 13;

            this.mapa = L.map('flavor-mapa-estaciones').setView([lat, lng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.mapa);

            // Cargar estaciones
            var estacionesData = $('#estaciones-data').html();
            if (estacionesData) {
                try {
                    var estaciones = JSON.parse(estacionesData);
                    this.mostrarEstacionesEnMapa(estaciones);
                } catch (e) {
                    console.error('Error parsing estaciones:', e);
                }
            }
        },

        mostrarEstacionesEnMapa: function(estaciones) {
            var self = this;

            estaciones.forEach(function(estacion) {
                var icono = L.divIcon({
                    className: 'flavor-marcador-estacion',
                    html: '<div class="flavor-marcador-contenido ' +
                          (estacion.disponibles > 0 ? 'disponible' : 'lleno') + '">' +
                          '<span class="dashicons dashicons-location-alt"></span>' +
                          '<span class="contador">' + estacion.disponibles + '</span>' +
                          '</div>',
                    iconSize: [40, 40],
                    iconAnchor: [20, 40]
                });

                var marcador = L.marker([estacion.lat, estacion.lng], { icon: icono })
                    .addTo(self.mapa)
                    .bindPopup(self.crearPopupEstacion(estacion));

                self.marcadores.push(marcador);
            });
        },

        crearPopupEstacion: function(estacion) {
            return '<div class="flavor-popup-estacion">' +
                '<h4>' + estacion.nombre + '</h4>' +
                '<p>' + estacion.direccion + '</p>' +
                '<div class="flavor-popup-stats">' +
                '<span class="disponibles">' + estacion.disponibles + ' disponibles</span>' +
                '<span class="capacidad">/ ' + estacion.capacidad + '</span>' +
                '</div>' +
                (estacion.horario ? '<p class="horario">' + estacion.horario + '</p>' : '') +
                '<a href="?estacion=' + estacion.id + '" class="flavor-btn flavor-btn-sm flavor-btn-primary">Ver bicicletas</a>' +
                '</div>';
        },

        initTiempoTranscurrido: function() {
            var $tiempo = $('.flavor-tiempo-transcurrido[data-inicio]');
            if (!$tiempo.length) return;

            setInterval(function() {
                $tiempo.each(function() {
                    var inicio = new Date($(this).data('inicio')).getTime();
                    var ahora = Date.now();
                    var diff = Math.floor((ahora - inicio) / 1000);

                    var horas = Math.floor(diff / 3600);
                    var minutos = Math.floor((diff % 3600) / 60);
                    var segundos = diff % 60;

                    var texto = '';
                    if (horas > 0) {
                        texto = horas + 'h ' + minutos + 'min';
                    } else if (minutos > 0) {
                        texto = minutos + ' min';
                    } else {
                        texto = segundos + ' seg';
                    }

                    $(this).text(texto);
                });
            }, 1000);
        },

        reservarBicicleta: function(e) {
            e.preventDefault();

            if (!confirm(this.config.strings.confirmarReserva)) {
                return;
            }

            var $btn = $(e.currentTarget);
            var bicicletaId = $btn.data('bicicleta-id');
            var btnTexto = $btn.html();

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_bicicletas_reservar',
                    nonce: this.config.nonce,
                    bicicleta_id: bicicletaId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorBicicletas.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        FlavorBicicletas.showNotice(response.data.message || FlavorBicicletas.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnTexto);
                    }
                },
                error: function() {
                    FlavorBicicletas.showNotice(FlavorBicicletas.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnTexto);
                }
            });
        },

        abrirModalDevolucion: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var prestamoId = $btn.data('prestamo-id');
            var bicicletaId = $btn.data('bicicleta-id');

            $('#devolver-prestamo-id').val(prestamoId);
            $('#devolver-bicicleta-id').val(bicicletaId);

            // Cargar estaciones
            this.cargarEstaciones();

            $('#modal-devolucion').show();
        },

        cargarEstaciones: function() {
            var $select = $('#devolver-estacion');
            $select.html('<option value="">Cargando...</option>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_bicicletas_buscar_estaciones',
                    nonce: this.config.nonce,
                    lat: 0,
                    lng: 0,
                    radio: 100
                },
                success: function(response) {
                    if (response.success && response.data.estaciones) {
                        var opciones = '<option value="">' + 'Selecciona una estación' + '</option>';
                        response.data.estaciones.forEach(function(est) {
                            opciones += '<option value="' + est.id + '">' +
                                est.nombre + ' (' + est.disponibles + '/' + est.capacidad + ')' +
                                '</option>';
                        });
                        $select.html(opciones);
                    }
                }
            });
        },

        devolverBicicleta: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $btn = $form.find('button[type="submit"]');
            var btnTexto = $btn.html();

            var estacionId = $('#devolver-estacion').val();
            if (!estacionId) {
                this.showNotice('Selecciona una estación', 'error');
                return;
            }

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Procesando...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_bicicletas_devolver',
                    nonce: this.config.nonce,
                    prestamo_id: $('#devolver-prestamo-id').val(),
                    bicicleta_id: $('#devolver-bicicleta-id').val(),
                    estacion_id: estacionId,
                    kilometros: $form.find('[name="kilometros"]').val(),
                    incidencias: $form.find('[name="incidencias"]').val(),
                    valoracion: $form.find('[name="valoracion"]:checked').val() || 0
                },
                success: function(response) {
                    if (response.success) {
                        FlavorBicicletas.showNotice(response.data.message, 'success');
                        FlavorBicicletas.cerrarModal();
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        FlavorBicicletas.showNotice(response.data.message || FlavorBicicletas.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnTexto);
                    }
                },
                error: function() {
                    FlavorBicicletas.showNotice(FlavorBicicletas.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnTexto);
                }
            });
        },

        reportarProblema: function(e) {
            e.preventDefault();

            var bicicletaId = $(e.currentTarget).data('bicicleta-id');
            var descripcion = prompt('Describe el problema con la bicicleta:');

            if (!descripcion) return;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_bicicletas_reportar_problema',
                    nonce: this.config.nonce,
                    bicicleta_id: bicicletaId,
                    descripcion: descripcion,
                    tipo_problema: 'otro'
                },
                success: function(response) {
                    if (response.success) {
                        FlavorBicicletas.showNotice(response.data.message, 'success');
                    } else {
                        FlavorBicicletas.showNotice(response.data.message || FlavorBicicletas.config.strings.error, 'error');
                    }
                }
            });
        },

        usarUbicacion: function(e) {
            e.preventDefault();

            if (!navigator.geolocation) {
                this.showNotice(this.config.strings.sinUbicacion, 'error');
                return;
            }

            var $btn = $(e.currentTarget);
            $btn.prop('disabled', true);

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;

                    // Buscar estaciones cercanas
                    $.ajax({
                        url: FlavorBicicletas.config.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'flavor_bicicletas_buscar_estaciones',
                            nonce: FlavorBicicletas.config.nonce,
                            lat: lat,
                            lng: lng,
                            radio: 5
                        },
                        success: function(response) {
                            if (response.success && response.data.estaciones.length > 0) {
                                var $select = $('#devolver-estacion');
                                var opciones = '<option value="">' + 'Selecciona una estación' + '</option>';
                                response.data.estaciones.forEach(function(est) {
                                    opciones += '<option value="' + est.id + '">' +
                                        est.nombre + ' (' + est.distancia + ' km)' +
                                        '</option>';
                                });
                                $select.html(opciones);
                                // Seleccionar la más cercana
                                $select.val(response.data.estaciones[0].id);
                            }
                            $btn.prop('disabled', false);
                        },
                        error: function() {
                            $btn.prop('disabled', false);
                        }
                    });
                },
                function() {
                    FlavorBicicletas.showNotice(FlavorBicicletas.config.strings.sinUbicacion, 'error');
                    $btn.prop('disabled', false);
                }
            );
        },

        filtrarPorTipo: function(e) {
            var tipo = $(e.target).val();

            if (!tipo) {
                $('.flavor-bicicleta-card').show();
            } else {
                $('.flavor-bicicleta-card').hide();
                $('.flavor-bicicleta-card[data-tipo="' + tipo + '"]').show();
            }
        },

        cerrarModal: function() {
            $('.flavor-modal').hide();
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
        FlavorBicicletas.init();
    });

    // CSS para notificaciones y marcadores
    var estilos = document.createElement('style');
    estilos.textContent = `
        .flavor-notice {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            z-index: 10001;
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
        .flavor-marcador-contenido {
            width: 40px;
            height: 40px;
            background: #3b82f6;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .flavor-marcador-contenido.lleno {
            background: #f59e0b;
        }
        .flavor-marcador-contenido .dashicons,
        .flavor-marcador-contenido .contador {
            transform: rotate(45deg);
        }
        .flavor-marcador-contenido .contador {
            position: absolute;
            bottom: -8px;
            right: -8px;
            background: #1e293b;
            color: #fff;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
        }
        .flavor-popup-estacion h4 {
            margin: 0 0 0.5rem;
        }
        .flavor-popup-estacion p {
            margin: 0.25rem 0;
            font-size: 0.9rem;
            color: #64748b;
        }
        .flavor-popup-stats {
            margin: 0.5rem 0;
        }
        .flavor-popup-stats .disponibles {
            font-weight: 600;
            color: #22c55e;
        }
    `;
    document.head.appendChild(estilos);

})(jQuery);
