/**
 * Biodiversidad Local - JavaScript
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const Biodiversidad = {
        init: function() {
            this.bindEvents();
            this.initMap();
        },

        bindEvents: function() {
            $(document).on('click', '.bl-categoria-btn', this.filtrarCategoria.bind(this));
            $(document).on('click', '.bl-tab', this.cambiarTab.bind(this));
            $(document).on('submit', '.bl-form-avistamiento', this.registrarAvistamiento.bind(this));
            $(document).on('submit', '.bl-form-especie', this.registrarEspecie.bind(this));
            $(document).on('submit', '.bl-form-proyecto', this.crearProyecto.bind(this));
            $(document).on('click', '.bl-btn-participar', this.participarProyecto.bind(this));
            $(document).on('click', '.bl-validar-btn', this.validarAvistamiento.bind(this));
            $(document).on('change', '.bl-habitat-checkbox input', this.toggleHabitatSelection.bind(this));
            $(document).on('click', '.bl-btn-ubicacion', this.obtenerUbicacion.bind(this));
        },

        filtrarCategoria: function(e) {
            const $btn = $(e.currentTarget);
            const categoria = $btn.data('categoria');

            $('.bl-categoria-btn').removeClass('activo');
            $btn.addClass('activo');

            if (categoria === 'todos') {
                $('.bl-especie-card').show();
            } else {
                $('.bl-especie-card').hide();
                $(`.bl-especie-card[data-categoria="${categoria}"]`).show();
            }
        },

        cambiarTab: function(e) {
            const $tab = $(e.currentTarget);
            const tabId = $tab.data('tab');

            $('.bl-tab').removeClass('activo');
            $tab.addClass('activo');

            $('.bl-tab-contenido').hide();
            $(`#${tabId}`).show();
        },

        registrarAvistamiento: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true).text('Registrando...');

            $.ajax({
                url: flavorBiodiversidad.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bl_registrar_avistamiento',
                    nonce: flavorBiodiversidad.nonce,
                    especie_id: $form.find('[name="especie_id"]').val(),
                    descripcion: $form.find('[name="descripcion"]').val(),
                    latitud: $form.find('[name="latitud"]').val(),
                    longitud: $form.find('[name="longitud"]').val(),
                    cantidad: $form.find('[name="cantidad"]').val() || 1,
                    habitat: $form.find('[name="habitat"]').val(),
                    fecha: $form.find('[name="fecha"]').val()
                },
                success: (response) => {
                    $btn.prop('disabled', false).text('Registrar Avistamiento');
                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $form[0].reset();
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false).text('Registrar Avistamiento');
                    this.mostrarError(flavorBiodiversidad.i18n.error);
                }
            });
        },

        registrarEspecie: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            const habitats = [];
            $form.find('.bl-habitat-checkbox input:checked').each(function() {
                habitats.push($(this).val());
            });

            $btn.prop('disabled', true).text('Enviando...');

            $.ajax({
                url: flavorBiodiversidad.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bl_registrar_especie',
                    nonce: flavorBiodiversidad.nonce,
                    nombre_comun: $form.find('[name="nombre_comun"]').val(),
                    nombre_cientifico: $form.find('[name="nombre_cientifico"]').val(),
                    descripcion: $form.find('[name="descripcion"]').val(),
                    categoria: $form.find('[name="categoria"]').val(),
                    estado_conservacion: $form.find('[name="estado_conservacion"]').val(),
                    habitats: habitats
                },
                success: (response) => {
                    $btn.prop('disabled', false).text('Proponer Especie');
                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $form[0].reset();
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false).text('Proponer Especie');
                    this.mostrarError(flavorBiodiversidad.i18n.error);
                }
            });
        },

        crearProyecto: function(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
            const $btn = $form.find('button[type="submit"]');

            $btn.prop('disabled', true).text('Creando...');

            $.ajax({
                url: flavorBiodiversidad.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bl_crear_proyecto',
                    nonce: flavorBiodiversidad.nonce,
                    titulo: $form.find('[name="titulo"]').val(),
                    descripcion: $form.find('[name="descripcion"]').val(),
                    tipo: $form.find('[name="tipo"]').val(),
                    fecha_inicio: $form.find('[name="fecha_inicio"]').val(),
                    ubicacion: $form.find('[name="ubicacion"]').val(),
                    participantes_max: $form.find('[name="participantes_max"]').val()
                },
                success: (response) => {
                    $btn.prop('disabled', false).text('Crear Proyecto');
                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $form[0].reset();
                    } else {
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false).text('Crear Proyecto');
                    this.mostrarError(flavorBiodiversidad.i18n.error);
                }
            });
        },

        participarProyecto: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const proyectoId = $btn.data('proyecto');

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorBiodiversidad.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bl_participar_proyecto',
                    nonce: flavorBiodiversidad.nonce,
                    proyecto_id: proyectoId
                },
                success: (response) => {
                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $btn.text('Participando').removeClass('bl-btn--primary').addClass('bl-btn--secondary');
                        const $contador = $btn.closest('.bl-proyecto-card').find('.bl-participantes-count');
                        $contador.text(response.data.participantes);
                    } else {
                        $btn.prop('disabled', false);
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorBiodiversidad.i18n.error);
                }
            });
        },

        validarAvistamiento: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const avistamientoId = $btn.data('avistamiento');
            const esValido = $btn.hasClass('bl-validar-btn--si');

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorBiodiversidad.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bl_validar_avistamiento',
                    nonce: flavorBiodiversidad.nonce,
                    avistamiento_id: avistamientoId,
                    es_valido: esValido
                },
                success: (response) => {
                    if (response.success) {
                        this.mostrarExito(response.data.message);
                        $btn.closest('.bl-avistamiento-item__acciones').html(
                            `<span class="bl-validacion-hecha">Validado (${response.data.validaciones_positivas}/${response.data.validaciones_total})</span>`
                        );
                    } else {
                        $btn.prop('disabled', false);
                        this.mostrarError(response.data.message);
                    }
                },
                error: () => {
                    $btn.prop('disabled', false);
                    this.mostrarError(flavorBiodiversidad.i18n.error);
                }
            });
        },

        toggleHabitatSelection: function(e) {
            const $checkbox = $(e.currentTarget);
            const $label = $checkbox.closest('.bl-habitat-checkbox');

            if ($checkbox.is(':checked')) {
                $label.addClass('checked');
            } else {
                $label.removeClass('checked');
            }
        },

        obtenerUbicacion: function(e) {
            e.preventDefault();

            if (!navigator.geolocation) {
                this.mostrarError('Tu navegador no soporta geolocalización');
                return;
            }

            const $btn = $(e.currentTarget);
            const textoOriginal = $btn.text();
            $btn.prop('disabled', true).text('Obteniendo ubicación...');

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    $('[name="latitud"]').val(position.coords.latitude);
                    $('[name="longitud"]').val(position.coords.longitude);
                    $btn.prop('disabled', false).text(textoOriginal);
                    this.mostrarExito('Ubicación obtenida');

                    // Si hay mapa, centrar en la ubicación
                    if (this.map) {
                        this.map.setView([position.coords.latitude, position.coords.longitude], 15);
                        if (this.marker) {
                            this.marker.setLatLng([position.coords.latitude, position.coords.longitude]);
                        }
                    }
                },
                (error) => {
                    $btn.prop('disabled', false).text(textoOriginal);
                    this.mostrarError('No se pudo obtener la ubicación');
                }
            );
        },

        initMap: function() {
            const $mapaContainer = $('#bl-mapa');
            if ($mapaContainer.length === 0) return;

            // Verificar si Leaflet está disponible
            if (typeof L === 'undefined') {
                // Cargar Leaflet dinámicamente
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(link);

                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.onload = () => this.crearMapa($mapaContainer);
                document.body.appendChild(script);
            } else {
                this.crearMapa($mapaContainer);
            }
        },

        crearMapa: function($container) {
            const lat = parseFloat($container.data('lat')) || 40.4168;
            const lng = parseFloat($container.data('lng')) || -3.7038;
            const zoom = parseInt($container.data('zoom')) || 12;

            this.map = L.map('bl-mapa').setView([lat, lng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.map);

            // Cargar avistamientos si hay datos
            const avistamientos = $container.data('avistamientos') || [];
            this.cargarMarcadores(avistamientos);

            // Si es formulario de registro, permitir clic para seleccionar ubicación
            if ($container.hasClass('bl-mapa-seleccionar')) {
                this.map.on('click', (e) => {
                    $('[name="latitud"]').val(e.latlng.lat.toFixed(6));
                    $('[name="longitud"]').val(e.latlng.lng.toFixed(6));

                    if (this.marker) {
                        this.marker.setLatLng(e.latlng);
                    } else {
                        this.marker = L.marker(e.latlng).addTo(this.map);
                    }
                });
            }
        },

        cargarMarcadores: function(avistamientos) {
            if (!this.map || !avistamientos.length) return;

            const categoriaColores = {
                flora: '#22c55e',
                fauna_vertebrados: '#f97316',
                fauna_invertebrados: '#a855f7'
            };

            avistamientos.forEach(av => {
                const color = categoriaColores[av.categoria] || '#3b82f6';

                const icono = L.divIcon({
                    className: 'bl-marker',
                    html: `<div style="background: ${color}; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);"></div>`,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                });

                L.marker([av.lat, av.lng], { icon: icono })
                    .addTo(this.map)
                    .bindPopup(`
                        <div class="bl-popup">
                            <strong>${av.especie}</strong>
                            <p>${av.fecha} - ${av.cantidad} ejemplar(es)</p>
                            ${av.url ? `<a href="${av.url}">Ver detalles</a>` : ''}
                        </div>
                    `);
            });
        },

        mostrarExito: function(mensaje) {
            this.mostrarNotificacion(mensaje, 'success');
        },

        mostrarError: function(mensaje) {
            this.mostrarNotificacion(mensaje, 'error');
        },

        mostrarNotificacion: function(mensaje, tipo) {
            const $notif = $(`<div class="bl-notificacion bl-notificacion--${tipo}">${mensaje}</div>`);
            $('body').append($notif);

            setTimeout(() => $notif.addClass('mostrar'), 10);
            setTimeout(() => {
                $notif.removeClass('mostrar');
                setTimeout(() => $notif.remove(), 300);
            }, 4000);
        }
    };

    $(document).ready(function() {
        Biodiversidad.init();
    });

})(jQuery);
