/**
 * JavaScript Frontend para Parkings
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var FlavorParkings = {
        config: window.flavorParkingsConfig || {},
        mapa: null,
        marcadores: [],

        init: function() {
            this.bindEvents();
            this.initMapa();
            this.initCountdowns();
        },

        bindEvents: function() {
            // Formulario de reserva
            $(document).on('submit', '.flavor-form-reserva', this.reservar.bind(this));

            // Cancelar reserva
            $(document).on('click', '.flavor-btn-cancelar', this.cancelarReserva.bind(this));

            // Inscribirse en lista de espera
            $(document).on('submit', '.flavor-form-lista-espera', this.inscribirListaEspera.bind(this));

            // Liberar plaza temporalmente
            $(document).on('click', '.flavor-btn-liberar', this.liberarPlaza.bind(this));

            // Filtros
            $(document).on('change', '#filtro-tipo, #filtro-zona', this.filtrar.bind(this));

            // Selección de plaza en mapa
            $(document).on('click', '.flavor-plaza-card', this.seleccionarPlaza.bind(this));

            // Calcular precio al cambiar fechas
            $(document).on('change', '#fecha_inicio, #fecha_fin', this.calcularPrecio.bind(this));

            // Ver detalles
            $(document).on('click', '.flavor-btn-detalles', this.verDetalles.bind(this));
        },

        /**
         * Inicializa el mapa de plazas
         */
        initMapa: function() {
            var $contenedor = $('#mapa-parkings');
            if (!$contenedor.length || typeof L === 'undefined') return;

            var lat = parseFloat($contenedor.data('lat')) || 40.4168;
            var lng = parseFloat($contenedor.data('lng')) || -3.7038;
            var zoom = parseInt($contenedor.data('zoom')) || 15;

            this.mapa = L.map('mapa-parkings').setView([lat, lng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.mapa);

            this.cargarPlazas();
        },

        /**
         * Carga las plazas en el mapa
         */
        cargarPlazas: function() {
            var self = this;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_parkings_buscar_plazas',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success && response.data.plazas) {
                        self.renderizarPlazas(response.data.plazas);
                    }
                }
            });
        },

        /**
         * Renderiza las plazas en el mapa
         */
        renderizarPlazas: function(plazas) {
            var self = this;

            // Limpiar marcadores existentes
            this.marcadores.forEach(function(marcador) {
                self.mapa.removeLayer(marcador);
            });
            this.marcadores = [];

            var iconoDisponible = L.divIcon({
                className: 'flavor-marker disponible',
                html: '<span class="dashicons dashicons-car"></span>',
                iconSize: [36, 36]
            });

            var iconoOcupada = L.divIcon({
                className: 'flavor-marker ocupada',
                html: '<span class="dashicons dashicons-car"></span>',
                iconSize: [36, 36]
            });

            var iconoReservada = L.divIcon({
                className: 'flavor-marker reservada',
                html: '<span class="dashicons dashicons-car"></span>',
                iconSize: [36, 36]
            });

            plazas.forEach(function(plaza) {
                if (!plaza.lat || !plaza.lng) return;

                var icono;
                switch (plaza.estado) {
                    case 'disponible':
                        icono = iconoDisponible;
                        break;
                    case 'ocupada':
                        icono = iconoOcupada;
                        break;
                    default:
                        icono = iconoReservada;
                }

                var marcador = L.marker([plaza.lat, plaza.lng], { icon: icono })
                    .addTo(self.mapa);

                var popupContent = self.crearPopup(plaza);
                marcador.bindPopup(popupContent);

                self.marcadores.push(marcador);
            });

            // Ajustar vista al conjunto de marcadores
            if (this.marcadores.length > 0) {
                var grupo = L.featureGroup(this.marcadores);
                this.mapa.fitBounds(grupo.getBounds().pad(0.1));
            }
        },

        /**
         * Crea el contenido del popup
         */
        crearPopup: function(plaza) {
            var estadoClase = 'flavor-estado-' + plaza.estado;
            var estadoTexto = this.config.strings['estado_' + plaza.estado] || plaza.estado;

            var html = '<div class="flavor-popup-plaza">';
            html += '<h4>' + plaza.codigo + '</h4>';
            html += '<span class="flavor-badge ' + estadoClase + '">' + estadoTexto + '</span>';
            html += '<p><strong>' + this.config.strings.tipo + ':</strong> ' + plaza.tipo + '</p>';
            html += '<p><strong>' + this.config.strings.zona + ':</strong> ' + plaza.zona + '</p>';

            if (plaza.precio_mes) {
                html += '<p><strong>' + this.config.strings.precio + ':</strong> ' + plaza.precio_mes + '€/mes</p>';
            }

            if (plaza.estado === 'disponible') {
                html += '<button class="flavor-btn flavor-btn-sm flavor-btn-reservar" data-plaza-id="' + plaza.id + '">';
                html += this.config.strings.reservar + '</button>';
            }

            html += '</div>';

            return html;
        },

        /**
         * Realiza una reserva
         */
        reservar: function(e) {
            e.preventDefault();

            var self = this;
            var $form = $(e.currentTarget);
            var $btn = $form.find('button[type="submit"]');

            var datosReserva = {
                action: 'flavor_parkings_reservar',
                nonce: this.config.nonce,
                plaza_id: $form.find('[name="plaza_id"]').val(),
                fecha_inicio: $form.find('[name="fecha_inicio"]').val(),
                fecha_fin: $form.find('[name="fecha_fin"]').val(),
                tipo_reserva: $form.find('[name="tipo_reserva"]').val(),
                vehiculo: $form.find('[name="vehiculo"]').val(),
                matricula: $form.find('[name="matricula"]').val(),
                notas: $form.find('[name="notas"]').val()
            };

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: datosReserva,
                success: function(response) {
                    if (response.success) {
                        self.showToast(response.data.message || self.config.strings.reservado, 'success');

                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1500);
                        } else {
                            // Recargar lista de plazas
                            self.cargarPlazas();
                            $form[0].reset();
                        }
                    } else {
                        self.showToast(response.data.message || self.config.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(self.config.strings.error, 'error');
                },
                complete: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Cancela una reserva
         */
        cancelarReserva: function(e) {
            e.preventDefault();

            if (!confirm(this.config.strings.confirmar_cancelar)) {
                return;
            }

            var self = this;
            var $btn = $(e.currentTarget);
            var reservaId = $btn.data('reserva-id');

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_parkings_cancelar_reserva',
                    nonce: this.config.nonce,
                    reserva_id: reservaId
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(response.data.message || self.config.strings.cancelado, 'success');

                        // Eliminar la fila de la reserva
                        $btn.closest('.flavor-reserva-card, tr').fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        self.showToast(response.data.message || self.config.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(self.config.strings.error, 'error');
                },
                complete: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Inscribe en lista de espera
         */
        inscribirListaEspera: function(e) {
            e.preventDefault();

            var self = this;
            var $form = $(e.currentTarget);
            var $btn = $form.find('button[type="submit"]');

            var datos = {
                action: 'flavor_parkings_lista_espera',
                nonce: this.config.nonce,
                tipo_plaza: $form.find('[name="tipo_plaza"]').val(),
                zona_preferida: $form.find('[name="zona_preferida"]').val(),
                vehiculo: $form.find('[name="vehiculo"]').val(),
                matricula: $form.find('[name="matricula"]').val(),
                notas: $form.find('[name="notas"]').val()
            };

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: datos,
                success: function(response) {
                    if (response.success) {
                        self.showToast(response.data.message || self.config.strings.inscrito, 'success');

                        if (response.data.posicion) {
                            self.mostrarPosicion(response.data.posicion);
                        }
                    } else {
                        self.showToast(response.data.message || self.config.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(self.config.strings.error, 'error');
                },
                complete: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Libera plaza temporalmente
         */
        liberarPlaza: function(e) {
            e.preventDefault();

            var self = this;
            var $btn = $(e.currentTarget);
            var plazaId = $btn.data('plaza-id');

            // Mostrar modal de fechas
            var fechaInicio = prompt(this.config.strings.fecha_liberacion_inicio);
            if (!fechaInicio) return;

            var fechaFin = prompt(this.config.strings.fecha_liberacion_fin);
            if (!fechaFin) return;

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_parkings_liberar_plaza',
                    nonce: this.config.nonce,
                    plaza_id: plazaId,
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(response.data.message || self.config.strings.liberado, 'success');
                        location.reload();
                    } else {
                        self.showToast(response.data.message || self.config.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showToast(self.config.strings.error, 'error');
                },
                complete: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Filtra plazas
         */
        filtrar: function() {
            var tipo = $('#filtro-tipo').val();
            var zona = $('#filtro-zona').val();

            var url = new URL(window.location.href);

            if (tipo) {
                url.searchParams.set('tipo', tipo);
            } else {
                url.searchParams.delete('tipo');
            }

            if (zona) {
                url.searchParams.set('zona', zona);
            } else {
                url.searchParams.delete('zona');
            }

            window.location.href = url.toString();
        },

        /**
         * Selecciona una plaza para reservar
         */
        seleccionarPlaza: function(e) {
            var $card = $(e.currentTarget);
            var plazaId = $card.data('plaza-id');

            // Desmarcar otras
            $('.flavor-plaza-card').removeClass('selected');
            $card.addClass('selected');

            // Actualizar campo oculto
            $('[name="plaza_id"]').val(plazaId);

            // Scroll al formulario
            $('html, body').animate({
                scrollTop: $('.flavor-form-reserva').offset().top - 100
            }, 500);
        },

        /**
         * Calcula el precio de la reserva
         */
        calcularPrecio: function() {
            var fechaInicio = $('#fecha_inicio').val();
            var fechaFin = $('#fecha_fin').val();
            var plazaId = $('[name="plaza_id"]').val();

            if (!fechaInicio || !fechaFin || !plazaId) return;

            var inicio = new Date(fechaInicio);
            var fin = new Date(fechaFin);
            var dias = Math.ceil((fin - inicio) / (1000 * 60 * 60 * 24));

            if (dias <= 0) {
                $('#precio-estimado').text(this.config.strings.fechas_invalidas);
                return;
            }

            var $plazaCard = $('.flavor-plaza-card[data-plaza-id="' + plazaId + '"]');
            var precioDia = parseFloat($plazaCard.data('precio-dia')) || 5;

            var precioTotal = dias * precioDia;
            $('#precio-estimado').html(
                '<strong>' + dias + '</strong> ' + this.config.strings.dias +
                ' × ' + precioDia + '€ = <strong>' + precioTotal.toFixed(2) + '€</strong>'
            );
        },

        /**
         * Ver detalles de plaza
         */
        verDetalles: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var plazaId = $btn.data('plaza-id');

            // Centrar mapa en la plaza
            if (this.mapa) {
                var marcador = this.marcadores.find(function(m) {
                    return m.options && m.options.plazaId === plazaId;
                });

                if (marcador) {
                    this.mapa.setView(marcador.getLatLng(), 18);
                    marcador.openPopup();
                }
            }
        },

        /**
         * Muestra la posición en lista de espera
         */
        mostrarPosicion: function(posicion) {
            var $indicador = $('.flavor-posicion-espera');
            if ($indicador.length) {
                $indicador.find('.posicion').text(posicion);
                $indicador.addClass('actualizado');
                setTimeout(function() {
                    $indicador.removeClass('actualizado');
                }, 2000);
            }
        },

        /**
         * Inicializa countdowns para reservas que expiran
         */
        initCountdowns: function() {
            var self = this;

            $('.flavor-countdown').each(function() {
                var $countdown = $(this);
                var expira = new Date($countdown.data('expira')).getTime();

                var intervalo = setInterval(function() {
                    var ahora = new Date().getTime();
                    var diferencia = expira - ahora;

                    if (diferencia < 0) {
                        clearInterval(intervalo);
                        $countdown.text(self.config.strings.expirado);
                        $countdown.addClass('expirado');
                        return;
                    }

                    var dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
                    var horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    var minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));

                    var texto = '';
                    if (dias > 0) texto += dias + 'd ';
                    if (horas > 0 || dias > 0) texto += horas + 'h ';
                    texto += minutos + 'm';

                    $countdown.text(texto);
                }, 60000); // Actualizar cada minuto
            });
        },

        /**
         * Muestra notificación toast
         */
        showToast: function(mensaje, tipo) {
            var $toast = $('<div class="flavor-toast ' + (tipo || '') + '">' + mensaje + '</div>');
            $('body').append($toast);

            setTimeout(function() {
                $toast.addClass('fade-out');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 3000);
        }
    };

    // Inicializar
    $(document).ready(function() {
        FlavorParkings.init();
    });

    // Estilos dinámicos
    var estilos = document.createElement('style');
    estilos.textContent = `
        .flavor-toast.fade-out { opacity: 0; transform: translateX(100px); transition: all 0.3s ease; }
        .flavor-btn.loading { pointer-events: none; opacity: 0.7; }
        .flavor-marker { display: flex; align-items: center; justify-content: center; border-radius: 50%; color: white; font-size: 18px; }
        .flavor-marker.disponible { background: #22c55e; }
        .flavor-marker.ocupada { background: #ef4444; }
        .flavor-marker.reservada { background: #f59e0b; }
        .flavor-plaza-card.selected { border-color: var(--flavor-primary); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        .flavor-posicion-espera.actualizado { animation: pulse 0.5s ease; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
    `;
    document.head.appendChild(estilos);

})(jQuery);
