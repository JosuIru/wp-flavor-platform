/**
 * JavaScript frontend para Biodiversidad Local
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    var FlavorBiodiversidad = {
        config: window.flavorBiodiversidadConfig || {},
        mapa: null,
        marcadorUbicacion: null,

        init: function() {
            this.bindEvents();
            this.initAutocomplete();
        },

        bindEvents: function() {
            // Formulario reportar avistamiento
            $(document).on('submit', '#flavor-form-avistamiento', this.reportarAvistamiento.bind(this));

            // Obtener ubicacion
            $(document).on('click', '#btn-obtener-ubicacion', this.obtenerUbicacion.bind(this));

            // Unirse a proyecto
            $(document).on('click', '.flavor-unirse-proyecto', this.unirseProyecto.bind(this));
        },

        initAutocomplete: function() {
            var $input = $('#especie_buscar');
            var $resultados = $('#especie_sugerencias');
            var $hidden = $('#especie_id');
            var self = this;
            var timeoutId;

            if (!$input.length) return;

            $input.on('input', function() {
                var termino = $(this).val();

                clearTimeout(timeoutId);

                if (termino.length < 2) {
                    $resultados.removeClass('activo').empty();
                    return;
                }

                timeoutId = setTimeout(function() {
                    self.buscarEspecies(termino, $resultados, $hidden, $input);
                }, 300);
            });

            // Cerrar al hacer clic fuera
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.flavor-autocomplete-container').length) {
                    $resultados.removeClass('activo');
                }
            });
        },

        buscarEspecies: function(termino, $resultados, $hidden, $input) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_biodiversidad_buscar_especies',
                    termino: termino
                },
                success: function(response) {
                    if (response.success && response.data.especies.length > 0) {
                        var html = '';
                        response.data.especies.forEach(function(especie) {
                            html += '<div class="flavor-autocomplete-item" data-id="' + especie.id + '" data-nombre="' + especie.nombre_comun + '">';
                            html += '<strong>' + especie.nombre_comun + '</strong>';
                            html += '<em>' + especie.nombre_cientifico + '</em>';
                            html += '</div>';
                        });
                        $resultados.html(html).addClass('activo');

                        // Click en item
                        $resultados.find('.flavor-autocomplete-item').on('click', function() {
                            var id = $(this).data('id');
                            var nombre = $(this).data('nombre');
                            $input.val(nombre);
                            $hidden.val(id);
                            $resultados.removeClass('activo');
                        });
                    } else {
                        $resultados.html('<div class="flavor-autocomplete-item">No se encontraron especies</div>').addClass('activo');
                    }
                }
            });
        },

        obtenerUbicacion: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var $status = $('#ubicacion-status');
            var $latitud = $('#latitud');
            var $longitud = $('#longitud');
            var $mapa = $('#mini-mapa-ubicacion');
            var self = this;

            if (!navigator.geolocation) {
                $status.text('Tu navegador no soporta geolocalización').addClass('error');
                return;
            }

            $btn.prop('disabled', true);
            $status.text('Obteniendo ubicación...').removeClass('success error');

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;

                    $latitud.val(lat);
                    $longitud.val(lng);
                    $status.text('Ubicación obtenida').addClass('success');
                    $btn.prop('disabled', false);

                    // Mostrar mini mapa
                    $mapa.show();
                    self.mostrarMiniMapa(lat, lng);
                },
                function(error) {
                    var mensaje = 'Error al obtener ubicación';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            mensaje = 'Permiso denegado';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            mensaje = 'Ubicación no disponible';
                            break;
                        case error.TIMEOUT:
                            mensaje = 'Tiempo agotado';
                            break;
                    }
                    $status.text(mensaje).addClass('error');
                    $btn.prop('disabled', false);
                }
            );
        },

        mostrarMiniMapa: function(lat, lng) {
            if (typeof L === 'undefined') return;

            if (this.mapa) {
                this.mapa.setView([lat, lng], 15);
                if (this.marcadorUbicacion) {
                    this.marcadorUbicacion.setLatLng([lat, lng]);
                }
            } else {
                this.mapa = L.map('mini-mapa-ubicacion').setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(this.mapa);

                this.marcadorUbicacion = L.marker([lat, lng], {draggable: true}).addTo(this.mapa);

                // Permitir arrastrar el marcador
                var self = this;
                this.marcadorUbicacion.on('dragend', function(e) {
                    var pos = e.target.getLatLng();
                    $('#latitud').val(pos.lat);
                    $('#longitud').val(pos.lng);
                });
            }
        },

        reportarAvistamiento: function(e) {
            e.preventDefault();

            var $form = $(e.target);
            var $btn = $form.find('button[type="submit"]');
            var btnText = $btn.html();

            // Validar ubicacion
            if (!$('#latitud').val() || !$('#longitud').val()) {
                this.showNotice(this.config.strings.ubicacionRequerida, 'error');
                return;
            }

            var formData = new FormData($form[0]);
            formData.append('action', 'flavor_biodiversidad_reportar');

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + this.config.strings.enviando);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        FlavorBiodiversidad.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.href = window.location.pathname;
                        }, 2000);
                    } else {
                        FlavorBiodiversidad.showNotice(response.data.message || FlavorBiodiversidad.config.strings.error, 'error');
                        $btn.prop('disabled', false).html(btnText);
                    }
                },
                error: function() {
                    FlavorBiodiversidad.showNotice(FlavorBiodiversidad.config.strings.error, 'error');
                    $btn.prop('disabled', false).html(btnText);
                }
            });
        },

        unirseProyecto: function(e) {
            e.preventDefault();

            var $btn = $(e.currentTarget);
            var proyectoId = $btn.data('proyecto-id');
            var btnText = $btn.html();

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_biodiversidad_unirse_proyecto',
                    nonce: this.config.nonce,
                    proyecto_id: proyectoId
                },
                success: function(response) {
                    if (response.success) {
                        FlavorBiodiversidad.showNotice(response.data.message, 'success');
                        $btn.removeClass('flavor-btn-primary').addClass('flavor-btn-success')
                            .html('<span class="dashicons dashicons-yes"></span> Participando');
                    } else {
                        FlavorBiodiversidad.showNotice(response.data.message || FlavorBiodiversidad.config.strings.error, 'error');
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
        FlavorBiodiversidad.init();
    });

    // CSS para notificaciones y estados
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
        #ubicacion-status.error {
            color: #ef4444;
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
