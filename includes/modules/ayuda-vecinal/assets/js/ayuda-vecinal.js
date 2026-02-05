/**
 * JavaScript del Módulo Ayuda Vecinal
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const AyudaVecinal = {
        config: {
            ajaxUrl: typeof flavorAyudaVecinal !== 'undefined' ? flavorAyudaVecinal.ajaxUrl : '/wp-admin/admin-ajax.php',
            nonce: typeof flavorAyudaVecinal !== 'undefined' ? flavorAyudaVecinal.nonce : '',
            mapboxToken: typeof flavorAyudaVecinal !== 'undefined' ? flavorAyudaVecinal.mapboxToken : '',
            usuarioId: typeof flavorAyudaVecinal !== 'undefined' ? flavorAyudaVecinal.usuarioId : 0,
        },

        map: null,
        marcadores: [],
        ubicacionUsuario: null,
        solicitudesCache: [],

        /**
         * Inicializar modulo
         */
        init: function() {
            this.bindEvents();
            this.initMapa();
            this.cargarSolicitudesActivas();
            this.initGeolocation();
        },

        /**
         * Bindear eventos
         */
        bindEvents: function() {
            // Formulario solicitud
            $(document).on('submit', '#form-crear-solicitud', this.handleCrearSolicitud.bind(this));
            $(document).on('submit', '#form-crear-oferta', this.handleCrearOferta.bind(this));

            // Botones de accion
            $(document).on('click', '.btn-ofrecer-ayuda', this.handleOfrecerAyuda.bind(this));
            $(document).on('click', '.btn-aceptar-ayudante', this.handleAceptarAyudante.bind(this));
            $(document).on('click', '.btn-completar-ayuda', this.handleCompletarAyuda.bind(this));
            $(document).on('click', '.btn-cancelar-solicitud', this.handleCancelarSolicitud.bind(this));

            // Valoraciones
            $(document).on('click', '.ayuda-estrella', this.handleClickEstrella.bind(this));
            $(document).on('submit', '#form-valorar-ayuda', this.handleValorarAyuda.bind(this));

            // Filtros
            $(document).on('change', '#filtro-categoria', this.filtrarSolicitudes.bind(this));
            $(document).on('change', '#filtro-urgencia', this.filtrarSolicitudes.bind(this));
            $(document).on('input', '#filtro-radio', this.filtrarPorRadio.bind(this));

            // Tabs
            $(document).on('click', '.ayuda-tab', this.handleTabClick.bind(this));

            // Modales
            $(document).on('click', '.ayuda-modal-close', this.cerrarModal.bind(this));
            $(document).on('click', '.ayuda-modal-overlay', this.handleOverlayClick.bind(this));

            // Categoria seleccion
            $(document).on('click', '.ayuda-categoria-item', this.handleCategoriaClick.bind(this));

            // Busqueda
            $(document).on('input', '#buscar-solicitudes', this.debounce(this.buscarSolicitudes.bind(this), 300));

            // Ver detalle
            $(document).on('click', '.btn-ver-detalle', this.verDetalleSolicitud.bind(this));
        },

        /**
         * Inicializar geolocalizacion
         */
        initGeolocation: function() {
            if ('geolocation' in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.ubicacionUsuario = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        this.actualizarDistancias();
                        if (this.map) {
                            this.centrarMapaEnUsuario();
                        }
                    },
                    (error) => {
                        console.log('Geolocation error:', error.message);
                    }
                );
            }
        },

        /**
         * Inicializar mapa
         */
        initMapa: function() {
            const contenedorMapa = document.getElementById('ayuda-mapa');
            if (!contenedorMapa) return;

            if (typeof L === 'undefined') {
                console.warn('Leaflet no cargado');
                return;
            }

            this.map = L.map('ayuda-mapa').setView([40.4168, -3.7038], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.map);

            this.cargarMarcadores();
        },

        /**
         * Centrar mapa en usuario
         */
        centrarMapaEnUsuario: function() {
            if (this.ubicacionUsuario && this.map) {
                this.map.setView([this.ubicacionUsuario.lat, this.ubicacionUsuario.lng], 14);

                L.marker([this.ubicacionUsuario.lat, this.ubicacionUsuario.lng], {
                    icon: L.divIcon({
                        className: 'marcador-usuario',
                        html: '<div class="pulse-marker"></div>',
                        iconSize: [20, 20]
                    })
                }).addTo(this.map).bindPopup('Tu ubicacion');
            }
        },

        /**
         * Cargar marcadores en mapa
         */
        cargarMarcadores: function() {
            this.marcadores.forEach(marcador => this.map.removeLayer(marcador));
            this.marcadores = [];

            this.solicitudesCache.forEach(solicitud => {
                if (solicitud.ubicacion_lat && solicitud.ubicacion_lng) {
                    const colorUrgencia = this.getColorUrgencia(solicitud.urgencia);
                    const icono = L.divIcon({
                        className: 'marcador-solicitud',
                        html: `<div class="marcador-pin" style="background:${colorUrgencia}"></div>`,
                        iconSize: [24, 24]
                    });

                    const marcador = L.marker([solicitud.ubicacion_lat, solicitud.ubicacion_lng], { icon: icono })
                        .addTo(this.map)
                        .bindPopup(this.crearPopupSolicitud(solicitud));

                    this.marcadores.push(marcador);
                }
            });
        },

        /**
         * Crear popup de solicitud
         */
        crearPopupSolicitud: function(solicitud) {
            return `
                <div class="popup-solicitud">
                    <h4>${this.escapeHtml(solicitud.titulo)}</h4>
                    <p class="popup-categoria">${this.getCategoriaIcono(solicitud.categoria)} ${solicitud.categoria}</p>
                    <p class="popup-urgencia urgencia-${solicitud.urgencia}">${solicitud.urgencia}</p>
                    <button class="ayuda-btn ayuda-btn-sm ayuda-btn-primary btn-ver-detalle" data-id="${solicitud.id}">
                        Ver detalle
                    </button>
                </div>
            `;
        },

        /**
         * Cargar solicitudes activas
         */
        cargarSolicitudesActivas: function() {
            const contenedor = $('.ayuda-solicitudes-grid');
            if (!contenedor.length) return;

            this.mostrarLoading(contenedor);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ayuda_vecinal_solicitudes_activas',
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.solicitudesCache = response.data.solicitudes;
                        this.renderSolicitudes(response.data.solicitudes);
                        this.cargarMarcadores();
                    } else {
                        this.mostrarError(contenedor, response.data.message);
                    }
                },
                error: () => {
                    this.mostrarError(contenedor, 'Error al cargar solicitudes');
                }
            });
        },

        /**
         * Renderizar solicitudes
         */
        renderSolicitudes: function(solicitudes) {
            const contenedor = $('.ayuda-solicitudes-grid');

            if (!solicitudes.length) {
                contenedor.html(this.templateEmptyState('No hay solicitudes activas', 'Se el primero en publicar una solicitud de ayuda'));
                return;
            }

            const htmlSolicitudes = solicitudes.map(solicitud => this.templateSolicitudCard(solicitud)).join('');
            contenedor.html(htmlSolicitudes);
        },

        /**
         * Template tarjeta solicitud
         */
        templateSolicitudCard: function(solicitud) {
            const distancia = solicitud.distancia ? `<span class="ayuda-meta-item"><span class="dashicons dashicons-location"></span>${solicitud.distancia} km</span>` : '';

            return `
                <div class="ayuda-solicitud-card" data-id="${solicitud.id}">
                    <div class="ayuda-solicitud-card-header">
                        <span class="ayuda-categoria-badge">
                            <span class="dashicons ${this.getCategoriaIcono(solicitud.categoria)}"></span>
                            ${this.escapeHtml(solicitud.categoria_label || solicitud.categoria)}
                        </span>
                        <span class="ayuda-urgencia-badge ayuda-urgencia-${solicitud.urgencia}">
                            ${solicitud.urgencia}
                        </span>
                    </div>
                    <div class="ayuda-solicitud-card-body">
                        <h3 class="ayuda-solicitud-titulo">${this.escapeHtml(solicitud.titulo)}</h3>
                        <p class="ayuda-solicitud-descripcion">${this.escapeHtml(this.truncarTexto(solicitud.descripcion, 120))}</p>
                        <div class="ayuda-solicitud-meta">
                            <span class="ayuda-meta-item">
                                <span class="dashicons dashicons-clock"></span>
                                ${solicitud.tiempo_publicada}
                            </span>
                            ${distancia}
                            ${solicitud.fecha_necesaria ? `<span class="ayuda-meta-item"><span class="dashicons dashicons-calendar-alt"></span>${solicitud.fecha_necesaria}</span>` : ''}
                        </div>
                    </div>
                    <div class="ayuda-solicitud-card-footer">
                        <div class="ayuda-solicitante">
                            <img src="${solicitud.avatar_url || 'https://secure.gravatar.com/avatar/?d=mp'}" alt="" class="ayuda-solicitante-avatar">
                            <div class="ayuda-solicitante-info">
                                <div class="ayuda-solicitante-nombre">${this.escapeHtml(solicitud.solicitante)}</div>
                                ${solicitud.rating ? `<div class="ayuda-solicitante-rating">${'★'.repeat(Math.round(solicitud.rating))} (${solicitud.rating})</div>` : ''}
                            </div>
                        </div>
                        <button class="ayuda-btn ayuda-btn-primary ayuda-btn-sm btn-ofrecer-ayuda" data-id="${solicitud.id}">
                            Ayudar
                        </button>
                    </div>
                </div>
            `;
        },

        /**
         * Crear solicitud
         */
        handleCrearSolicitud: function(evento) {
            evento.preventDefault();
            const formulario = $(evento.target);
            const botonSubmit = formulario.find('button[type="submit"]');

            if (!this.validarFormulario(formulario)) return;

            botonSubmit.prop('disabled', true).text('Publicando...');

            const datosSolicitud = {
                action: 'ayuda_vecinal_crear_solicitud',
                nonce: this.config.nonce,
                categoria: formulario.find('[name="categoria"]').val(),
                titulo: formulario.find('[name="titulo"]').val(),
                descripcion: formulario.find('[name="descripcion"]').val(),
                urgencia: formulario.find('[name="urgencia"]').val(),
                fecha_necesaria: formulario.find('[name="fecha_necesaria"]').val(),
                duracion_estimada: formulario.find('[name="duracion_estimada"]').val(),
                ubicacion: formulario.find('[name="ubicacion"]').val(),
                ubicacion_lat: this.ubicacionUsuario?.lat || '',
                ubicacion_lng: this.ubicacionUsuario?.lng || ''
            };

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: datosSolicitud,
                success: (response) => {
                    if (response.success) {
                        this.mostrarToast('Solicitud publicada correctamente', 'success');
                        formulario[0].reset();
                        this.cerrarModal();
                        this.cargarSolicitudesActivas();
                    } else {
                        this.mostrarToast(response.data.message || 'Error al publicar', 'error');
                    }
                },
                error: () => {
                    this.mostrarToast('Error de conexion', 'error');
                },
                complete: () => {
                    botonSubmit.prop('disabled', false).text('Publicar Solicitud');
                }
            });
        },

        /**
         * Crear oferta de ayuda
         */
        handleCrearOferta: function(evento) {
            evento.preventDefault();
            const formulario = $(evento.target);
            const botonSubmit = formulario.find('button[type="submit"]');

            if (!this.validarFormulario(formulario)) return;

            botonSubmit.prop('disabled', true).text('Guardando...');

            const datosOferta = {
                action: 'ayuda_vecinal_crear_oferta',
                nonce: this.config.nonce,
                categoria: formulario.find('[name="categoria"]').val(),
                titulo: formulario.find('[name="titulo"]').val(),
                descripcion: formulario.find('[name="descripcion"]').val(),
                habilidades: formulario.find('[name="habilidades"]').val(),
                radio_km: formulario.find('[name="radio_km"]').val(),
                tiene_vehiculo: formulario.find('[name="tiene_vehiculo"]').is(':checked') ? 1 : 0,
                disponibilidad: this.obtenerDisponibilidad(formulario)
            };

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: datosOferta,
                success: (response) => {
                    if (response.success) {
                        this.mostrarToast('Oferta de ayuda registrada', 'success');
                        formulario[0].reset();
                        this.cerrarModal();
                    } else {
                        this.mostrarToast(response.data.message || 'Error al guardar', 'error');
                    }
                },
                error: () => {
                    this.mostrarToast('Error de conexion', 'error');
                },
                complete: () => {
                    botonSubmit.prop('disabled', false).text('Guardar Oferta');
                }
            });
        },

        /**
         * Ofrecer ayuda en solicitud
         */
        handleOfrecerAyuda: function(evento) {
            evento.preventDefault();
            const solicitudId = $(evento.target).data('id');

            if (!this.config.usuarioId) {
                this.mostrarToast('Debes iniciar sesion para ayudar', 'warning');
                return;
            }

            this.abrirModal('modal-ofrecer-ayuda', {
                solicitud_id: solicitudId
            });
        },

        /**
         * Confirmar oferta de ayuda
         */
        confirmarOfertaAyuda: function(solicitudId, mensaje) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ayuda_vecinal_ofrecer_ayuda',
                    nonce: this.config.nonce,
                    solicitud_id: solicitudId,
                    mensaje: mensaje
                },
                success: (response) => {
                    if (response.success) {
                        this.mostrarToast('Tu oferta de ayuda ha sido enviada', 'success');
                        this.cerrarModal();
                    } else {
                        this.mostrarToast(response.data.message || 'Error al enviar oferta', 'error');
                    }
                },
                error: () => {
                    this.mostrarToast('Error de conexion', 'error');
                }
            });
        },

        /**
         * Aceptar ayudante
         */
        handleAceptarAyudante: function(evento) {
            evento.preventDefault();
            const respuestaId = $(evento.target).data('respuesta-id');

            if (!confirm('¿Aceptar a este vecino como ayudante?')) return;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ayuda_vecinal_aceptar_ayudante',
                    nonce: this.config.nonce,
                    respuesta_id: respuestaId
                },
                success: (response) => {
                    if (response.success) {
                        this.mostrarToast('Ayudante aceptado. Coordinense para la ayuda.', 'success');
                        this.cargarMisAyudas();
                    } else {
                        this.mostrarToast(response.data.message || 'Error al aceptar', 'error');
                    }
                },
                error: () => {
                    this.mostrarToast('Error de conexion', 'error');
                }
            });
        },

        /**
         * Completar ayuda
         */
        handleCompletarAyuda: function(evento) {
            evento.preventDefault();
            const solicitudId = $(evento.target).data('id');

            if (!confirm('¿Marcar esta ayuda como completada?')) return;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ayuda_vecinal_completar',
                    nonce: this.config.nonce,
                    solicitud_id: solicitudId
                },
                success: (response) => {
                    if (response.success) {
                        this.mostrarToast('Ayuda marcada como completada', 'success');
                        this.abrirModalValoracion(solicitudId);
                        this.cargarMisAyudas();
                    } else {
                        this.mostrarToast(response.data.message || 'Error', 'error');
                    }
                },
                error: () => {
                    this.mostrarToast('Error de conexion', 'error');
                }
            });
        },

        /**
         * Cancelar solicitud
         */
        handleCancelarSolicitud: function(evento) {
            evento.preventDefault();
            const solicitudId = $(evento.target).data('id');

            if (!confirm('¿Seguro que deseas cancelar esta solicitud?')) return;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ayuda_vecinal_cancelar',
                    nonce: this.config.nonce,
                    solicitud_id: solicitudId
                },
                success: (response) => {
                    if (response.success) {
                        this.mostrarToast('Solicitud cancelada', 'success');
                        this.cargarSolicitudesActivas();
                        this.cargarMisAyudas();
                    } else {
                        this.mostrarToast(response.data.message || 'Error', 'error');
                    }
                },
                error: () => {
                    this.mostrarToast('Error de conexion', 'error');
                }
            });
        },

        /**
         * Click en estrella valoracion
         */
        handleClickEstrella: function(evento) {
            const estrella = $(evento.target);
            const valoracion = estrella.data('valor');
            const contenedor = estrella.closest('.ayuda-estrellas');

            contenedor.find('.ayuda-estrella').removeClass('active');
            contenedor.find('.ayuda-estrella').each(function() {
                if ($(this).data('valor') <= valoracion) {
                    $(this).addClass('active');
                }
            });

            contenedor.data('puntuacion', valoracion);
        },

        /**
         * Enviar valoracion
         */
        handleValorarAyuda: function(evento) {
            evento.preventDefault();
            const formulario = $(evento.target);
            const solicitudId = formulario.find('[name="solicitud_id"]').val();
            const puntuacion = formulario.find('.ayuda-estrellas').data('puntuacion');
            const comentario = formulario.find('[name="comentario"]').val();

            if (!puntuacion) {
                this.mostrarToast('Selecciona una puntuacion', 'warning');
                return;
            }

            const aspectos = {};
            formulario.find('.ayuda-aspecto-estrellas').each(function() {
                const aspecto = $(this).data('aspecto');
                const valor = $(this).data('puntuacion') || 0;
                aspectos[aspecto] = valor;
            });

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ayuda_vecinal_valorar',
                    nonce: this.config.nonce,
                    solicitud_id: solicitudId,
                    puntuacion: puntuacion,
                    aspectos: JSON.stringify(aspectos),
                    comentario: comentario
                },
                success: (response) => {
                    if (response.success) {
                        this.mostrarToast('Gracias por tu valoracion', 'success');
                        this.cerrarModal();
                        if (response.data.puntos_ganados) {
                            this.mostrarToast(`+${response.data.puntos_ganados} puntos de solidaridad`, 'info');
                        }
                    } else {
                        this.mostrarToast(response.data.message || 'Error al valorar', 'error');
                    }
                },
                error: () => {
                    this.mostrarToast('Error de conexion', 'error');
                }
            });
        },

        /**
         * Filtrar solicitudes
         */
        filtrarSolicitudes: function() {
            const categoria = $('#filtro-categoria').val();
            const urgencia = $('#filtro-urgencia').val();

            let solicitudesFiltradas = [...this.solicitudesCache];

            if (categoria) {
                solicitudesFiltradas = solicitudesFiltradas.filter(s => s.categoria === categoria);
            }

            if (urgencia) {
                solicitudesFiltradas = solicitudesFiltradas.filter(s => s.urgencia === urgencia);
            }

            this.renderSolicitudes(solicitudesFiltradas);
        },

        /**
         * Filtrar por radio de distancia
         */
        filtrarPorRadio: function(evento) {
            const radioKm = parseFloat($(evento.target).val());
            $('#valor-radio').text(radioKm + ' km');

            if (!this.ubicacionUsuario) {
                this.mostrarToast('Activa la geolocalizacion para filtrar por distancia', 'warning');
                return;
            }

            const solicitudesFiltradas = this.solicitudesCache.filter(solicitud => {
                if (!solicitud.ubicacion_lat || !solicitud.ubicacion_lng) return true;
                const distancia = this.calcularDistancia(
                    this.ubicacionUsuario.lat,
                    this.ubicacionUsuario.lng,
                    solicitud.ubicacion_lat,
                    solicitud.ubicacion_lng
                );
                return distancia <= radioKm;
            });

            this.renderSolicitudes(solicitudesFiltradas);
        },

        /**
         * Buscar solicitudes
         */
        buscarSolicitudes: function(evento) {
            const termino = $(evento.target).val().toLowerCase();

            if (!termino) {
                this.renderSolicitudes(this.solicitudesCache);
                return;
            }

            const solicitudesFiltradas = this.solicitudesCache.filter(s =>
                s.titulo.toLowerCase().includes(termino) ||
                s.descripcion.toLowerCase().includes(termino) ||
                s.categoria.toLowerCase().includes(termino)
            );

            this.renderSolicitudes(solicitudesFiltradas);
        },

        /**
         * Click en tab
         */
        handleTabClick: function(evento) {
            const tab = $(evento.target);
            const targetId = tab.data('target');

            tab.siblings().removeClass('active');
            tab.addClass('active');

            $('.ayuda-tab-panel').removeClass('active');
            $(targetId).addClass('active');

            if (targetId === '#mis-solicitudes') {
                this.cargarMisSolicitudes();
            } else if (targetId === '#mis-ayudas') {
                this.cargarMisAyudasRealizadas();
            }
        },

        /**
         * Click en categoria
         */
        handleCategoriaClick: function(evento) {
            const item = $(evento.target).closest('.ayuda-categoria-item');
            const categoria = item.data('categoria');

            $('.ayuda-categoria-item').removeClass('active');
            item.addClass('active');

            $('[name="categoria"]').val(categoria);
            $('#filtro-categoria').val(categoria).trigger('change');
        },

        /**
         * Ver detalle solicitud
         */
        verDetalleSolicitud: function(evento) {
            evento.preventDefault();
            const solicitudId = $(evento.target).data('id');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ayuda_vecinal_detalle',
                    nonce: this.config.nonce,
                    solicitud_id: solicitudId
                },
                success: (response) => {
                    if (response.success) {
                        this.mostrarModalDetalle(response.data.solicitud);
                    } else {
                        this.mostrarToast('Error al cargar detalle', 'error');
                    }
                }
            });
        },

        /**
         * Mostrar modal detalle
         */
        mostrarModalDetalle: function(solicitud) {
            const contenido = `
                <div class="ayuda-detalle-header">
                    <span class="ayuda-categoria-badge">
                        <span class="dashicons ${this.getCategoriaIcono(solicitud.categoria)}"></span>
                        ${this.escapeHtml(solicitud.categoria_label)}
                    </span>
                    <span class="ayuda-urgencia-badge ayuda-urgencia-${solicitud.urgencia}">${solicitud.urgencia}</span>
                </div>
                <h2 class="ayuda-detalle-titulo">${this.escapeHtml(solicitud.titulo)}</h2>
                <p class="ayuda-detalle-descripcion">${this.escapeHtml(solicitud.descripcion)}</p>
                <div class="ayuda-detalle-meta">
                    <p><strong>Solicitante:</strong> ${this.escapeHtml(solicitud.solicitante)}</p>
                    <p><strong>Publicado:</strong> ${solicitud.tiempo_publicada}</p>
                    ${solicitud.fecha_necesaria ? `<p><strong>Fecha necesaria:</strong> ${solicitud.fecha_necesaria}</p>` : ''}
                    ${solicitud.duracion_estimada ? `<p><strong>Duracion estimada:</strong> ${solicitud.duracion_estimada} min</p>` : ''}
                    ${solicitud.ubicacion ? `<p><strong>Ubicacion:</strong> ${this.escapeHtml(solicitud.ubicacion)}</p>` : ''}
                </div>
                ${solicitud.puede_ayudar ? `
                <div class="ayuda-detalle-acciones">
                    <button class="ayuda-btn ayuda-btn-primary ayuda-btn-lg ayuda-btn-block btn-ofrecer-ayuda" data-id="${solicitud.id}">
                        <span class="dashicons dashicons-heart"></span>
                        Quiero Ayudar
                    </button>
                </div>
                ` : ''}
            `;

            this.abrirModal('modal-detalle', { contenido: contenido });
        },

        /**
         * Cargar mis ayudas
         */
        cargarMisAyudas: function() {
            this.cargarMisSolicitudes();
            this.cargarMisAyudasRealizadas();
        },

        /**
         * Cargar mis solicitudes
         */
        cargarMisSolicitudes: function() {
            const contenedor = $('#mis-solicitudes-lista');
            if (!contenedor.length) return;

            this.mostrarLoading(contenedor);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ayuda_vecinal_mis_solicitudes',
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderMisSolicitudes(response.data.solicitudes);
                    }
                }
            });
        },

        /**
         * Renderizar mis solicitudes
         */
        renderMisSolicitudes: function(solicitudes) {
            const contenedor = $('#mis-solicitudes-lista');

            if (!solicitudes.length) {
                contenedor.html(this.templateEmptyState('No tienes solicitudes', 'Publica tu primera solicitud de ayuda'));
                return;
            }

            const htmlLista = solicitudes.map(s => `
                <div class="ayuda-lista-item">
                    <div class="ayuda-lista-item-info">
                        <div class="ayuda-lista-item-titulo">${this.escapeHtml(s.titulo)}</div>
                        <div class="ayuda-lista-item-meta">${s.categoria} - ${s.tiempo_publicada}</div>
                    </div>
                    <span class="ayuda-estado-badge ayuda-estado-${s.estado}">${s.estado_label}</span>
                    ${s.estado === 'abierta' ? `<button class="ayuda-btn ayuda-btn-sm btn-cancelar-solicitud" data-id="${s.id}">Cancelar</button>` : ''}
                    ${s.estado === 'en_curso' ? `<button class="ayuda-btn ayuda-btn-sm ayuda-btn-primary btn-completar-ayuda" data-id="${s.id}">Completar</button>` : ''}
                </div>
            `).join('');

            contenedor.html(htmlLista);
        },

        /**
         * Cargar ayudas realizadas
         */
        cargarMisAyudasRealizadas: function() {
            const contenedor = $('#mis-ayudas-lista');
            if (!contenedor.length) return;

            this.mostrarLoading(contenedor);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ayuda_vecinal_mis_ayudas_realizadas',
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderMisAyudasRealizadas(response.data.ayudas);
                    }
                }
            });
        },

        /**
         * Renderizar ayudas realizadas
         */
        renderMisAyudasRealizadas: function(ayudas) {
            const contenedor = $('#mis-ayudas-lista');

            if (!ayudas.length) {
                contenedor.html(this.templateEmptyState('No has realizado ayudas', 'Encuentra solicitudes y ofrece tu ayuda'));
                return;
            }

            const htmlLista = ayudas.map(a => `
                <div class="ayuda-lista-item">
                    <div class="ayuda-lista-item-info">
                        <div class="ayuda-lista-item-titulo">${this.escapeHtml(a.titulo)}</div>
                        <div class="ayuda-lista-item-meta">Ayudaste a ${this.escapeHtml(a.solicitante)} - ${a.fecha}</div>
                    </div>
                    <span class="ayuda-estado-badge ayuda-estado-${a.estado}">${a.estado_label}</span>
                    ${a.puntos ? `<span class="ayuda-puntos">+${a.puntos} pts</span>` : ''}
                </div>
            `).join('');

            contenedor.html(htmlLista);
        },

        /**
         * Abrir modal
         */
        abrirModal: function(modalId, datos) {
            const modal = $(`#${modalId}`);
            if (!modal.length) return;

            if (datos) {
                if (datos.contenido) {
                    modal.find('.ayuda-modal-body').html(datos.contenido);
                }
                if (datos.solicitud_id) {
                    modal.find('[name="solicitud_id"]').val(datos.solicitud_id);
                }
            }

            modal.addClass('active');
            $('body').addClass('ayuda-modal-open');
        },

        /**
         * Cerrar modal
         */
        cerrarModal: function() {
            $('.ayuda-modal-overlay').removeClass('active');
            $('body').removeClass('ayuda-modal-open');
        },

        /**
         * Click en overlay del modal
         */
        handleOverlayClick: function(evento) {
            if ($(evento.target).hasClass('ayuda-modal-overlay')) {
                this.cerrarModal();
            }
        },

        /**
         * Abrir modal valoracion
         */
        abrirModalValoracion: function(solicitudId) {
            this.abrirModal('modal-valorar', { solicitud_id: solicitudId });
        },

        // Utilidades

        /**
         * Mostrar toast
         */
        mostrarToast: function(mensaje, tipo) {
            tipo = tipo || 'info';
            const toast = $(`<div class="ayuda-toast ayuda-toast-${tipo}">${this.escapeHtml(mensaje)}</div>`);
            $('body').append(toast);

            setTimeout(() => toast.addClass('active'), 100);
            setTimeout(() => {
                toast.removeClass('active');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        },

        /**
         * Mostrar loading
         */
        mostrarLoading: function(contenedor) {
            contenedor.html('<div class="ayuda-loading"><div class="ayuda-spinner"></div></div>');
        },

        /**
         * Mostrar error
         */
        mostrarError: function(contenedor, mensaje) {
            contenedor.html(`<div class="ayuda-empty-state"><p class="ayuda-empty-text">${this.escapeHtml(mensaje)}</p></div>`);
        },

        /**
         * Template empty state
         */
        templateEmptyState: function(titulo, texto) {
            return `
                <div class="ayuda-empty-state">
                    <div class="ayuda-empty-icon"><span class="dashicons dashicons-heart"></span></div>
                    <h3 class="ayuda-empty-title">${this.escapeHtml(titulo)}</h3>
                    <p class="ayuda-empty-text">${this.escapeHtml(texto)}</p>
                </div>
            `;
        },

        /**
         * Validar formulario
         */
        validarFormulario: function(formulario) {
            let valido = true;
            formulario.find('[required]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('error');
                    valido = false;
                } else {
                    $(this).removeClass('error');
                }
            });
            return valido;
        },

        /**
         * Obtener disponibilidad del formulario
         */
        obtenerDisponibilidad: function(formulario) {
            const disponibilidad = {};
            formulario.find('.disponibilidad-dia:checked').each(function() {
                const dia = $(this).val();
                disponibilidad[dia] = {
                    manana: formulario.find(`[name="disp_${dia}_manana"]`).is(':checked'),
                    tarde: formulario.find(`[name="disp_${dia}_tarde"]`).is(':checked'),
                    noche: formulario.find(`[name="disp_${dia}_noche"]`).is(':checked')
                };
            });
            return JSON.stringify(disponibilidad);
        },

        /**
         * Calcular distancia entre coordenadas (Haversine)
         */
        calcularDistancia: function(lat1, lng1, lat2, lng2) {
            const radioTierra = 6371;
            const dLat = this.toRad(lat2 - lat1);
            const dLng = this.toRad(lng2 - lng1);
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(this.toRad(lat1)) * Math.cos(this.toRad(lat2)) *
                Math.sin(dLng / 2) * Math.sin(dLng / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return Math.round(radioTierra * c * 10) / 10;
        },

        toRad: function(valor) {
            return valor * Math.PI / 180;
        },

        /**
         * Actualizar distancias en solicitudes
         */
        actualizarDistancias: function() {
            if (!this.ubicacionUsuario) return;

            this.solicitudesCache.forEach(solicitud => {
                if (solicitud.ubicacion_lat && solicitud.ubicacion_lng) {
                    solicitud.distancia = this.calcularDistancia(
                        this.ubicacionUsuario.lat,
                        this.ubicacionUsuario.lng,
                        solicitud.ubicacion_lat,
                        solicitud.ubicacion_lng
                    );
                }
            });

            this.renderSolicitudes(this.solicitudesCache);
        },

        /**
         * Obtener icono de categoria
         */
        getCategoriaIcono: function(categoria) {
            const iconos = {
                compras: 'dashicons-cart',
                cuidado_mayores: 'dashicons-groups',
                cuidado_ninos: 'dashicons-smiley',
                mascotas: 'dashicons-heart',
                transporte: 'dashicons-car',
                tecnologia: 'dashicons-laptop',
                tramites: 'dashicons-media-document',
                reparaciones: 'dashicons-admin-tools',
                compania: 'dashicons-format-chat',
                otro: 'dashicons-plus-alt'
            };
            return iconos[categoria] || 'dashicons-heart';
        },

        /**
         * Obtener color de urgencia
         */
        getColorUrgencia: function(urgencia) {
            const colores = {
                urgente: '#dc2626',
                alta: '#f97316',
                media: '#eab308',
                baja: '#22c55e'
            };
            return colores[urgencia] || '#6b7280';
        },

        /**
         * Truncar texto
         */
        truncarTexto: function(texto, maxLength) {
            if (texto.length <= maxLength) return texto;
            return texto.substring(0, maxLength) + '...';
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(texto) {
            if (!texto) return '';
            const div = document.createElement('div');
            div.textContent = texto;
            return div.innerHTML;
        },

        /**
         * Debounce
         */
        debounce: function(funcionOriginal, espera) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => funcionOriginal.apply(this, args), espera);
            };
        }
    };

    // Inicializar cuando DOM este listo
    $(document).ready(function() {
        AyudaVecinal.init();
    });

    // Exportar para uso externo
    window.AyudaVecinal = AyudaVecinal;

})(jQuery);
