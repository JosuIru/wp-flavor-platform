/**
 * JavaScript Frontend para Compostaje Comunitario
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var FlavorCompostaje = {
        config: window.flavorCompostajeConfig || {},
        mapa: null,
        marcadores: [],

        init: function() {
            this.bindEvents();
            this.initMapas();
            this.initFormularios();
        },

        bindEvents: function() {
            // Cambio de categoría en formulario
            $(document).on('change', 'input[name="categoria"]', this.cambiarCategoria.bind(this));

            // Cambio de material
            $(document).on('change', '#tipo_material', this.actualizarPuntosPreview.bind(this));

            // Cambio de cantidad
            $(document).on('input', '#cantidad_kg', this.actualizarPuntosPreview.bind(this));

            // Envío de formulario de aportación
            $(document).on('submit', '#flavor-form-aportacion', this.enviarAportacion.bind(this));

            // Inscribirse a turno
            $(document).on('click', '.flavor-btn-inscribir-turno', this.inscribirTurno.bind(this));

            // Cancelar turno
            $(document).on('click', '.flavor-btn-cancelar-turno', this.cancelarTurno.bind(this));

            // Aportar desde lista de puntos
            $(document).on('click', '.flavor-btn-aportar', this.abrirFormularioAportacion.bind(this));

            // Ver en mapa
            $(document).on('click', '.flavor-btn-ver-mapa', this.centrarMapa.bind(this));

            // Filtros de ranking
            $(document).on('click', '.flavor-ranking-filtros .flavor-btn', this.filtrarRanking.bind(this));

            // Preview de foto
            $(document).on('change', '#foto', this.previewFoto.bind(this));

            // Buscar puntos por ubicación
            $(document).on('click', '.flavor-btn-mi-ubicacion', this.buscarPuntosCercanos.bind(this));
        },

        /**
         * Inicializa los mapas Leaflet
         */
        initMapas: function() {
            var self = this;

            $('.flavor-mapa-container').each(function() {
                var $contenedor = $(this);
                var idMapa = $contenedor.attr('id');

                if (!idMapa) {
                    idMapa = 'flavor-mapa-' + Math.random().toString(36).substr(2, 9);
                    $contenedor.attr('id', idMapa);
                }

                // Evitar reinicializar
                if ($contenedor.data('mapa-init')) return;

                var puntosData = $contenedor.data('puntos');
                var tipo = $contenedor.data('tipo');

                // Crear mapa
                var mapa = L.map(idMapa).setView([40.416775, -3.703790], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(mapa);

                // Guardar referencia
                $contenedor.data('mapa-init', true);
                $contenedor.data('leaflet-mapa', mapa);

                // Añadir marcadores si hay datos
                if (puntosData && puntosData.length > 0) {
                    self.añadirMarcadores(mapa, puntosData);
                }

                // Si es tipo cercanos, intentar geolocalización
                if (tipo === 'cercanos') {
                    self.geolocalizarYBuscar(mapa);
                }

                // Fix para mapas en tabs ocultos
                setTimeout(function() {
                    mapa.invalidateSize();
                }, 100);
            });
        },

        /**
         * Añade marcadores al mapa
         */
        añadirMarcadores: function(mapa, puntos) {
            var self = this;
            var bounds = [];

            puntos.forEach(function(punto) {
                var lat = parseFloat(punto.latitud);
                var lng = parseFloat(punto.longitud);

                if (isNaN(lat) || isNaN(lng)) return;

                var color = self.obtenerColorFase(punto.fase_actual);
                var icono = L.divIcon({
                    className: 'flavor-marker-custom',
                    html: '<div style="background:' + color + ';width:24px;height:24px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3);"></div>',
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                });

                var marcador = L.marker([lat, lng], { icon: icono })
                    .addTo(mapa)
                    .bindPopup(self.crearPopup(punto));

                bounds.push([lat, lng]);
            });

            // Ajustar vista
            if (bounds.length > 0) {
                mapa.fitBounds(bounds, { padding: [50, 50] });
            }
        },

        /**
         * Obtiene color según la fase del compostaje
         */
        obtenerColorFase: function(fase) {
            var colores = {
                'recepcion': '#22c55e',
                'activo': '#f97316',
                'maduracion': '#f59e0b',
                'listo': '#3b82f6',
                'mantenimiento': '#6b7280'
            };
            return colores[fase] || '#22c55e';
        },

        /**
         * Crea contenido del popup
         */
        crearPopup: function(punto) {
            var html = '<div class="flavor-popup">';
            html += '<h4>' + punto.nombre + '</h4>';
            html += '<p>' + punto.direccion + '</p>';
            html += '<p><strong>Estado:</strong> ' + punto.fase_actual + '</p>';
            html += '<p><strong>Llenado:</strong> ' + punto.nivel_llenado_pct + '%</p>';
            if (punto.horario_apertura) {
                html += '<p><strong>Horario:</strong> ' + punto.horario_apertura + '</p>';
            }
            html += '<button class="flavor-btn flavor-btn-sm flavor-btn-primary flavor-btn-aportar" data-punto-id="' + punto.id + '">';
            html += 'Aportar</button>';
            html += '</div>';
            return html;
        },

        /**
         * Geolocaliza al usuario y busca puntos cercanos
         */
        geolocalizarYBuscar: function(mapa) {
            var self = this;

            if (!navigator.geolocation) return;

            navigator.geolocation.getCurrentPosition(function(posicion) {
                var lat = posicion.coords.latitude;
                var lng = posicion.coords.longitude;

                mapa.setView([lat, lng], 14);

                // Marcador de ubicación del usuario
                L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: 'flavor-marker-usuario',
                        html: '<div style="background:#3b82f6;width:16px;height:16px;border-radius:50%;border:3px solid #fff;box-shadow:0 0 0 3px rgba(59,130,246,0.3);"></div>',
                        iconSize: [16, 16],
                        iconAnchor: [8, 8]
                    })
                }).addTo(mapa).bindPopup('Tu ubicación');

                // Buscar puntos cercanos
                self.buscarPuntosAjax(lat, lng, mapa);
            });
        },

        /**
         * Busca puntos de compostaje por AJAX
         */
        buscarPuntosAjax: function(lat, lng, mapa) {
            var self = this;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_compostaje_buscar_puntos',
                    lat: lat,
                    lng: lng,
                    radio: 5
                },
                success: function(response) {
                    if (response.success && response.data.puntos) {
                        self.añadirMarcadores(mapa, response.data.puntos);
                    }
                }
            });
        },

        /**
         * Centra el mapa en unas coordenadas
         */
        centrarMapa: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var lat = parseFloat($btn.data('lat'));
            var lng = parseFloat($btn.data('lng'));

            // Buscar el mapa más cercano
            var $mapa = $btn.closest('.flavor-compostaje-lista').find('.flavor-mapa-container');
            if (!$mapa.length) {
                $mapa = $('.flavor-mapa-container').first();
            }

            var mapa = $mapa.data('leaflet-mapa');
            if (mapa) {
                mapa.setView([lat, lng], 16);
            }
        },

        /**
         * Inicializa formularios
         */
        initFormularios: function() {
            // Cargar materiales de la categoría seleccionada
            this.cambiarCategoria();
        },

        /**
         * Cambia la categoría de material
         */
        cambiarCategoria: function() {
            var categoria = $('input[name="categoria"]:checked').val() || 'verde';
            var materiales = this.config.materiales[categoria] || {};
            var $select = $('#tipo_material');

            $select.html('<option value="">' + this.config.strings.cargando + '</option>');

            var opciones = '<option value="">Selecciona el tipo...</option>';
            $.each(materiales, function(codigo, data) {
                opciones += '<option value="' + codigo + '" data-puntos="' + data.puntos + '">' + data.nombre + '</option>';
            });

            $select.html(opciones);
            this.actualizarPuntosPreview();
        },

        /**
         * Actualiza el preview de puntos
         */
        actualizarPuntosPreview: function() {
            var $select = $('#tipo_material');
            var $option = $select.find(':selected');
            var puntosBase = parseInt($option.data('puntos')) || 0;
            var cantidad = parseFloat($('#cantidad_kg').val()) || 0;

            var puntosTotal = Math.round(puntosBase * cantidad);

            if (puntosTotal > 0) {
                $('#puntos-estimados').text(puntosTotal);
                $('#puntos-preview').show();

                // Mostrar info del material
                var nombre = $option.text();
                if (nombre && nombre !== 'Selecciona el tipo...') {
                    $('#material-info').text(puntosBase + ' puntos por kg');
                } else {
                    $('#material-info').text('');
                }
            } else {
                $('#puntos-preview').hide();
                $('#material-info').text('');
            }
        },

        /**
         * Preview de foto
         */
        previewFoto: function(e) {
            var archivo = e.target.files[0];
            var $preview = $('#foto-preview');

            if (archivo) {
                var lector = new FileReader();
                lector.onload = function(ev) {
                    $preview.html('<img src="' + ev.target.result + '" alt="Preview">');
                };
                lector.readAsDataURL(archivo);
            } else {
                $preview.empty();
            }
        },

        /**
         * Envía formulario de aportación
         */
        enviarAportacion: function(e) {
            e.preventDefault();

            var self = this;
            var $form = $(e.currentTarget);
            var $btn = $form.find('button[type="submit"]');
            var formData = new FormData($form[0]);

            formData.append('action', 'flavor_compostaje_registrar');
            formData.append('nonce', this.config.nonce);

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.showToast(response.data.message, 'success');

                        // Mostrar puntos obtenidos
                        if (response.data.puntos) {
                            setTimeout(function() {
                                self.showToast('+' + response.data.puntos + ' puntos obtenidos!', 'success');
                            }, 1500);
                        }

                        // Resetear formulario
                        $form[0].reset();
                        $('#puntos-preview').hide();
                        $('#foto-preview').empty();
                        self.cambiarCategoria();

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
         * Inscribirse a un turno
         */
        inscribirTurno: function(e) {
            e.preventDefault();

            var self = this;
            var $btn = $(e.currentTarget);
            var turnoId = $btn.data('turno-id');

            if (!confirm(this.config.strings.confirmar_inscripcion)) {
                return;
            }

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_compostaje_inscribir_turno',
                    nonce: this.config.nonce,
                    turno_id: turnoId
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(response.data.message, 'success');

                        // Actualizar UI
                        var $card = $btn.closest('.flavor-turno-card');
                        $card.addClass('inscrito');

                        $btn.removeClass('flavor-btn-primary flavor-btn-inscribir-turno')
                            .addClass('flavor-btn-danger flavor-btn-cancelar-turno')
                            .html('<span class="dashicons dashicons-no-alt"></span> Cancelar');

                        // Actualizar plazas
                        var $plazas = $card.find('.flavor-turno-plazas');
                        var textoPlazas = $plazas.text();
                        var match = textoPlazas.match(/(\d+)\/(\d+)/);
                        if (match) {
                            var disponibles = parseInt(match[1]) - 1;
                            var total = parseInt(match[2]);
                            $plazas.html('<span class="dashicons dashicons-groups"></span> ' + disponibles + '/' + total + ' plazas');
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
         * Cancelar inscripción a turno
         */
        cancelarTurno: function(e) {
            e.preventDefault();

            var self = this;
            var $btn = $(e.currentTarget);
            var turnoId = $btn.data('turno-id');

            if (!confirm(this.config.strings.confirmar_cancelar)) {
                return;
            }

            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_compostaje_cancelar_inscripcion',
                    nonce: this.config.nonce,
                    turno_id: turnoId
                },
                success: function(response) {
                    if (response.success) {
                        self.showToast(response.data.message, 'success');

                        // Actualizar UI
                        var $card = $btn.closest('.flavor-turno-card');
                        $card.removeClass('inscrito');

                        $btn.removeClass('flavor-btn-danger flavor-btn-cancelar-turno')
                            .addClass('flavor-btn-primary flavor-btn-inscribir-turno')
                            .html('<span class="dashicons dashicons-yes-alt"></span> Inscribirme');

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
         * Abre formulario de aportación para un punto específico
         */
        abrirFormularioAportacion: function(e) {
            e.preventDefault();

            var puntoId = $(e.currentTarget).data('punto-id');

            // Si hay formulario en la página, seleccionar el punto
            var $select = $('#punto_id');
            if ($select.length) {
                $select.val(puntoId);
                $('html, body').animate({
                    scrollTop: $select.offset().top - 100
                }, 500);
            } else {
                // Redirigir a página de aportación
                window.location.href = window.location.pathname + '?punto_id=' + puntoId + '&seccion=registrar';
            }
        },

        /**
         * Busca puntos cercanos a la ubicación del usuario
         */
        buscarPuntosCercanos: function(e) {
            e.preventDefault();

            var self = this;
            var $btn = $(e.currentTarget);
            var $mapa = $btn.closest('.flavor-mapa-wrapper').find('.flavor-mapa-container');
            var mapa = $mapa.data('leaflet-mapa');

            if (!navigator.geolocation) {
                this.showToast('La geolocalización no está disponible', 'error');
                return;
            }

            $btn.addClass('loading');

            navigator.geolocation.getCurrentPosition(
                function(posicion) {
                    var lat = posicion.coords.latitude;
                    var lng = posicion.coords.longitude;

                    if (mapa) {
                        mapa.setView([lat, lng], 14);
                        self.buscarPuntosAjax(lat, lng, mapa);
                    }

                    $btn.removeClass('loading');
                },
                function() {
                    self.showToast('No se pudo obtener tu ubicación', 'error');
                    $btn.removeClass('loading');
                }
            );
        },

        /**
         * Filtrar ranking por período
         */
        filtrarRanking: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var periodo = $btn.data('periodo');

            // Actualizar botones activos
            $btn.siblings().removeClass('active');
            $btn.addClass('active');

            // Recargar ranking (simplificado - idealmente sería AJAX)
            var url = new URL(window.location.href);
            url.searchParams.set('periodo', periodo);
            window.location.href = url.toString();
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

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        FlavorCompostaje.init();
    });

    // Re-inicializar mapas cuando se muestran tabs ocultos
    $(document).on('shown.bs.tab click', '[data-toggle="tab"], .flavor-tab-link', function() {
        setTimeout(function() {
            $('.flavor-mapa-container').each(function() {
                var mapa = $(this).data('leaflet-mapa');
                if (mapa) {
                    mapa.invalidateSize();
                }
            });
        }, 100);
    });

    // Estilos adicionales para toast fade-out
    var estilos = document.createElement('style');
    estilos.textContent = '.flavor-toast.fade-out { opacity: 0; transform: translateX(100px); transition: all 0.3s ease; }';
    document.head.appendChild(estilos);

})(jQuery);
