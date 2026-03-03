/**
 * Ayuda Vecinal - JavaScript Frontend para Shortcodes
 */
(function($) {
    'use strict';

    // Verificar que los datos están disponibles
    if (typeof ayudaVecinalData === 'undefined') {
        console.warn('Ayuda Vecinal: datos de configuración no encontrados');
        return;
    }

    /**
     * Controlador principal de Ayuda Vecinal
     */
    const AyudaVecinal = {

        /**
         * Inicialización
         */
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initMapa();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Formulario de solicitar ayuda
            $(document).on('submit', '#form-solicitar-ayuda', this.handleSubmitSolicitud.bind(this));

            // Formulario de ofrecer ayuda
            $(document).on('submit', '#form-ofrecer-ayuda', this.handleSubmitOferta.bind(this));

            // Botón ofrecer ayuda en tarjeta
            $(document).on('click', '.btn-ofrecer-ayuda', this.handleOfrecerAyudaTarjeta.bind(this));

            // Filtros
            $(document).on('change', '.mapa-filtro', this.handleMapaFiltro.bind(this));

            // Mi ubicación en mapa
            $(document).on('click', '#mapa-btn-mi-ubicacion', this.handleMiUbicacion.bind(this));

            // Tabs de mis ayudas
            $(document).on('click', '.tab-btn', this.handleTabClick.bind(this));

            // Acciones en lista de ayudas
            $(document).on('click', '.btn-ver-respuestas', this.handleVerRespuestas.bind(this));
            $(document).on('click', '.btn-aceptar-respuesta', this.handleAceptarRespuesta.bind(this));
            $(document).on('click', '.btn-cancelar-solicitud', this.handleCancelarSolicitud.bind(this));
            $(document).on('click', '.btn-completar-solicitud', this.handleCompletarSolicitud.bind(this));
            $(document).on('click', '.btn-retirar-oferta', this.handleRetirarOferta.bind(this));
            $(document).on('click', '.btn-desactivar-oferta', this.handleDesactivarOferta.bind(this));
        },

        /**
         * Enviar solicitud de ayuda
         */
        handleSubmitSolicitud: function(evento) {
            evento.preventDefault();

            const formulario = $(evento.currentTarget);
            const botonSubmit = formulario.find('button[type="submit"]');
            const textoOriginal = botonSubmit.text();

            // Validación básica
            if (!this.validarFormulario(formulario)) {
                return;
            }

            botonSubmit.prop('disabled', true).text('Enviando...');

            const datosFormulario = formulario.serialize();

            $.ajax({
                url: ayudaVecinalData.ajax_url,
                type: 'POST',
                data: datosFormulario + '&action=ayuda_vecinal_crear_solicitud',
                success: function(respuesta) {
                    if (respuesta.success) {
                        AyudaVecinal.mostrarNotificacion(respuesta.data.message, 'exito');
                        formulario[0].reset();
                    } else {
                        AyudaVecinal.mostrarNotificacion(respuesta.data.message || ayudaVecinalData.strings.error_general, 'error');
                    }
                },
                error: function() {
                    AyudaVecinal.mostrarNotificacion(ayudaVecinalData.strings.error_general, 'error');
                },
                complete: function() {
                    botonSubmit.prop('disabled', false).text(textoOriginal);
                }
            });
        },

        /**
         * Enviar oferta de ayuda
         */
        handleSubmitOferta: function(evento) {
            evento.preventDefault();

            const formulario = $(evento.currentTarget);
            const botonSubmit = formulario.find('button[type="submit"]');
            const textoOriginal = botonSubmit.text();
            const idOferta = formulario.data('oferta-id') || '';

            if (!this.validarFormulario(formulario)) {
                return;
            }

            botonSubmit.prop('disabled', true).text('Guardando...');

            let datosFormulario = formulario.serialize();
            if (idOferta) {
                datosFormulario += '&oferta_id=' + idOferta;
            }

            $.ajax({
                url: ayudaVecinalData.ajax_url,
                type: 'POST',
                data: datosFormulario + '&action=ayuda_vecinal_crear_oferta',
                success: function(respuesta) {
                    if (respuesta.success) {
                        AyudaVecinal.mostrarNotificacion(respuesta.data.message, 'exito');
                    } else {
                        AyudaVecinal.mostrarNotificacion(respuesta.data.message || ayudaVecinalData.strings.error_general, 'error');
                    }
                },
                error: function() {
                    AyudaVecinal.mostrarNotificacion(ayudaVecinalData.strings.error_general, 'error');
                },
                complete: function() {
                    botonSubmit.prop('disabled', false).text(textoOriginal);
                }
            });
        },

        /**
         * Ofrecer ayuda desde tarjeta
         */
        handleOfrecerAyudaTarjeta: function(evento) {
            evento.preventDefault();

            if (!ayudaVecinalData.user_logged_in) {
                this.mostrarNotificacion(ayudaVecinalData.strings.login_requerido, 'aviso');
                return;
            }

            const boton = $(evento.currentTarget);
            const idSolicitud = boton.data('solicitud-id');

            // Mostrar modal para escribir mensaje
            this.mostrarModalOfrecerAyuda(idSolicitud);
        },

        /**
         * Mostrar modal para ofrecer ayuda
         */
        mostrarModalOfrecerAyuda: function(idSolicitud) {
            // Crear modal si no existe
            let modal = $('#modal-ofrecer-ayuda');

            if (!modal.length) {
                modal = $(`
                    <div id="modal-ofrecer-ayuda" class="av-modal">
                        <div class="av-modal-contenido">
                            <div class="av-modal-header">
                                <h3>Ofrecer ayuda</h3>
                                <button class="av-modal-cerrar">&times;</button>
                            </div>
                            <form id="form-responder-solicitud">
                                <input type="hidden" name="solicitud_id" value="">
                                <input type="hidden" name="ayuda_vecinal_nonce" value="${$('#form-solicitar-ayuda input[name="ayuda_vecinal_nonce"]').val() || ''}">
                                <div class="form-grupo">
                                    <label for="respuesta-mensaje">Mensaje para el solicitante</label>
                                    <textarea id="respuesta-mensaje" name="mensaje" rows="4"
                                        placeholder="Cuéntale cómo puedes ayudar, tu disponibilidad, etc."></textarea>
                                </div>
                                <div class="form-grupo">
                                    <label for="respuesta-disponibilidad">Disponibilidad propuesta</label>
                                    <input type="datetime-local" id="respuesta-disponibilidad" name="disponibilidad">
                                </div>
                                <div class="form-acciones">
                                    <button type="submit" class="btn-principal">Enviar oferta</button>
                                    <button type="button" class="btn-secundario av-modal-cerrar">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                `);
                $('body').append(modal);

                // Eventos del modal
                modal.on('click', '.av-modal-cerrar', function() {
                    modal.removeClass('activo');
                });

                modal.on('click', function(e) {
                    if ($(e.target).is('.av-modal')) {
                        modal.removeClass('activo');
                    }
                });

                modal.on('submit', '#form-responder-solicitud', this.handleEnviarRespuesta.bind(this));
            }

            // Configurar y mostrar
            modal.find('input[name="solicitud_id"]').val(idSolicitud);
            modal.find('textarea, input[type="datetime-local"]').val('');
            modal.addClass('activo');
        },

        /**
         * Enviar respuesta a solicitud
         */
        handleEnviarRespuesta: function(evento) {
            evento.preventDefault();

            const formulario = $(evento.currentTarget);
            const botonSubmit = formulario.find('button[type="submit"]');
            const textoOriginal = botonSubmit.text();

            botonSubmit.prop('disabled', true).text('Enviando...');

            $.ajax({
                url: ayudaVecinalData.ajax_url,
                type: 'POST',
                data: formulario.serialize() + '&action=ayuda_vecinal_responder_solicitud',
                success: function(respuesta) {
                    if (respuesta.success) {
                        AyudaVecinal.mostrarNotificacion(respuesta.data.message, 'exito');
                        $('#modal-ofrecer-ayuda').removeClass('activo');
                    } else {
                        AyudaVecinal.mostrarNotificacion(respuesta.data.message || ayudaVecinalData.strings.error_general, 'error');
                    }
                },
                error: function() {
                    AyudaVecinal.mostrarNotificacion(ayudaVecinalData.strings.error_general, 'error');
                },
                complete: function() {
                    botonSubmit.prop('disabled', false).text(textoOriginal);
                }
            });
        },

        /**
         * Inicializar tabs
         */
        initTabs: function() {
            // Ya manejado por CSS, solo para interactividad
        },

        /**
         * Manejar click en tab
         */
        handleTabClick: function(evento) {
            evento.preventDefault();

            const tab = $(evento.currentTarget);
            const idTab = tab.data('tab');
            const contenedor = tab.closest('.ayuda-vecinal-mis-ayudas');

            // Actualizar tabs
            contenedor.find('.tab-btn').removeClass('activo');
            tab.addClass('activo');

            // Actualizar contenido
            contenedor.find('.tab-content').removeClass('activo');
            contenedor.find('#tab-' + idTab).addClass('activo');
        },

        /**
         * Inicializar mapa
         */
        initMapa: function() {
            const contenedorMapa = $('#ayuda-vecinal-mapa');

            if (!contenedorMapa.length || typeof L === 'undefined') {
                return;
            }

            const config = contenedorMapa.data('config');

            // Crear mapa
            this.mapa = L.map('ayuda-vecinal-mapa').setView([config.lat_centro, config.lng_centro], config.zoom);

            // Añadir capa de tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(this.mapa);

            // Grupo de marcadores
            this.marcadoresMapa = L.layerGroup().addTo(this.mapa);

            // Cargar solicitudes
            this.cargarSolicitudesMapa();
        },

        /**
         * Cargar solicitudes en el mapa
         */
        cargarSolicitudesMapa: function(filtros) {
            if (!this.mapa) return;

            const config = $('#ayuda-vecinal-mapa').data('config');
            filtros = filtros || {};

            $.ajax({
                url: config.ajax_url,
                type: 'GET',
                data: {
                    action: 'ayuda_vecinal_get_solicitudes_mapa',
                    categoria: filtros.categoria || '',
                    urgencia: filtros.urgencia || ''
                },
                success: function(respuesta) {
                    if (respuesta.success) {
                        AyudaVecinal.actualizarMarcadores(respuesta.data.marcadores);
                    }
                }
            });
        },

        /**
         * Actualizar marcadores del mapa
         */
        actualizarMarcadores: function(marcadores) {
            if (!this.marcadoresMapa) return;

            this.marcadoresMapa.clearLayers();

            const iconosUrgencia = {
                'urgente': this.crearIconoMarcador('#ef4444'),
                'alta': this.crearIconoMarcador('#f97316'),
                'media': this.crearIconoMarcador('#eab308'),
                'baja': this.crearIconoMarcador('#22c55e')
            };

            marcadores.forEach(function(marcador) {
                const icono = iconosUrgencia[marcador.urgencia] || iconosUrgencia.media;

                const marcadorLeaflet = L.marker([marcador.lat, marcador.lng], { icon: icono })
                    .bindPopup(`
                        <div class="popup-solicitud">
                            <h4>${marcador.titulo}</h4>
                            <div class="meta">
                                <span>${marcador.categoria}</span> |
                                <span>${marcador.ubicacion || 'Sin ubicación'}</span>
                            </div>
                            <button class="btn-ver-detalle" data-id="${marcador.id}">Ver detalle</button>
                        </div>
                    `);

                AyudaVecinal.marcadoresMapa.addLayer(marcadorLeaflet);
            });
        },

        /**
         * Crear icono de marcador personalizado
         */
        crearIconoMarcador: function(color) {
            return L.divIcon({
                className: 'marcador-personalizado',
                html: `<div style="background-color: ${color}; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });
        },

        /**
         * Manejar filtro del mapa
         */
        handleMapaFiltro: function() {
            const categoria = $('#mapa-filtro-categoria').val();
            const urgencia = $('#mapa-filtro-urgencia').val();

            this.cargarSolicitudesMapa({ categoria: categoria, urgencia: urgencia });
        },

        /**
         * Manejar mi ubicación
         */
        handleMiUbicacion: function() {
            if (!this.mapa || !navigator.geolocation) {
                this.mostrarNotificacion('Tu navegador no soporta geolocalización', 'aviso');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function(posicion) {
                    AyudaVecinal.mapa.setView([posicion.coords.latitude, posicion.coords.longitude], 15);

                    // Añadir marcador de ubicación actual
                    if (AyudaVecinal.marcadorUbicacion) {
                        AyudaVecinal.mapa.removeLayer(AyudaVecinal.marcadorUbicacion);
                    }

                    AyudaVecinal.marcadorUbicacion = L.marker([posicion.coords.latitude, posicion.coords.longitude], {
                        icon: L.divIcon({
                            className: 'mi-ubicacion',
                            html: '<div style="background-color: #3b82f6; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);"></div>',
                            iconSize: [16, 16],
                            iconAnchor: [8, 8]
                        })
                    }).addTo(AyudaVecinal.mapa);
                },
                function() {
                    AyudaVecinal.mostrarNotificacion('No se pudo obtener tu ubicación', 'error');
                }
            );
        },

        /**
         * Ver respuestas de una solicitud
         */
        handleVerRespuestas: function(evento) {
            const idSolicitud = $(evento.currentTarget).data('solicitud-id');
            this.restRequest(String(idSolicitud) + '/respuestas', {
                method: 'GET'
            }).done((respuestas) => {
                this.mostrarModalRespuestas(idSolicitud, respuestas || []);
            }).fail((xhr) => {
                this.mostrarNotificacion(this.obtenerMensajeError(xhr), 'error');
            });
        },

        /**
         * Aceptar respuesta
         */
        handleAceptarRespuesta: function(evento) {
            evento.preventDefault();

            if (!confirm('¿Aceptar esta ayuda y asignar a esta persona?')) {
                return;
            }

            const idRespuesta = $(evento.currentTarget).data('respuesta-id');

            this.restRequest('respuestas/' + String(idRespuesta) + '/aceptar', {
                method: 'POST'
            }).done((respuesta) => {
                this.mostrarNotificacion((respuesta && respuesta.message) || ayudaVecinalData.strings.respuesta_aceptada, 'exito');
                $('#modal-respuestas-ayuda').removeClass('activo');
                window.location.reload();
            }).fail((xhr) => {
                this.mostrarNotificacion(this.obtenerMensajeError(xhr), 'error');
            });
        },

        /**
         * Cancelar solicitud
         */
        handleCancelarSolicitud: function(evento) {
            evento.preventDefault();

            if (!confirm('¿Estás seguro de que quieres cancelar esta solicitud?')) {
                return;
            }

            const idSolicitud = $(evento.currentTarget).data('solicitud-id');

            this.restRequest(String(idSolicitud), {
                method: 'DELETE'
            }).done((respuesta) => {
                this.mostrarNotificacion((respuesta && respuesta.message) || ayudaVecinalData.strings.solicitud_cancelada, 'exito');
                window.location.reload();
            }).fail((xhr) => {
                this.mostrarNotificacion(this.obtenerMensajeError(xhr), 'error');
            });
        },

        /**
         * Completar solicitud
         */
        handleCompletarSolicitud: function(evento) {
            evento.preventDefault();

            if (!confirm('¿Confirmas que la ayuda ha sido completada?')) {
                return;
            }

            const idSolicitud = $(evento.currentTarget).data('solicitud-id');

            this.restRequest(String(idSolicitud), {
                method: 'PUT',
                data: {
                    estado: 'completada'
                }
            }).done((respuesta) => {
                this.mostrarNotificacion((respuesta && respuesta.message) || ayudaVecinalData.strings.solicitud_completada, 'exito');
                window.location.reload();
            }).fail((xhr) => {
                this.mostrarNotificacion(this.obtenerMensajeError(xhr), 'error');
            });
        },

        /**
         * Retirar oferta
         */
        handleRetirarOferta: function(evento) {
            evento.preventDefault();

            if (!confirm('¿Estás seguro de que quieres retirar tu oferta de ayuda?')) {
                return;
            }

            const boton = $(evento.currentTarget);
            const idRespuesta = boton.data('respuesta-id');

            this.restRequest('respuestas/' + String(idRespuesta) + '/retirar', {
                method: 'POST'
            }).done((respuesta) => {
                this.mostrarNotificacion((respuesta && respuesta.message) || 'Tu oferta de ayuda ha sido retirada.', 'exito');
                window.location.reload();
            }).fail((xhr) => {
                this.mostrarNotificacion(this.obtenerMensajeError(xhr), 'error');
            });
        },

        /**
         * Desactivar oferta
         */
        handleDesactivarOferta: function(evento) {
            evento.preventDefault();

            if (!confirm('¿Quieres desactivar tu oferta de ayuda?')) {
                return;
            }

            const boton = $(evento.currentTarget);
            const idOferta = boton.data('oferta-id');

            this.restRequest('ofertas/' + String(idOferta), {
                method: 'PUT',
                data: {
                    activa: false
                }
            }).done((respuesta) => {
                this.mostrarNotificacion((respuesta && respuesta.message) || 'Tu oferta ha sido desactivada.', 'exito');
                window.location.reload();
            }).fail((xhr) => {
                this.mostrarNotificacion(this.obtenerMensajeError(xhr), 'error');
            });
        },

        /**
         * Peticion REST al modulo
         */
        restRequest: function(path, opciones) {
            opciones = opciones || {};

            const ajaxOptions = {
                url: ayudaVecinalData.rest_url + path.replace(/^\/+/, ''),
                type: opciones.method || 'GET',
                headers: {
                    'X-WP-Nonce': ayudaVecinalData.rest_nonce
                }
            };

            if (ajaxOptions.type === 'GET') {
                ajaxOptions.data = opciones.data || {};
            } else {
                ajaxOptions.contentType = 'application/json; charset=utf-8';
                ajaxOptions.data = JSON.stringify(opciones.data || {});
                ajaxOptions.processData = false;
            }

            return $.ajax(ajaxOptions);
        },

        /**
         * Mostrar respuestas en modal
         */
        mostrarModalRespuestas: function(idSolicitud, respuestas) {
            let modal = $('#modal-respuestas-ayuda');

            if (!modal.length) {
                modal = $(`
                    <div id="modal-respuestas-ayuda" class="av-modal">
                        <div class="av-modal-contenido">
                            <div class="av-modal-header">
                                <h3>Respuestas recibidas</h3>
                                <button class="av-modal-cerrar">&times;</button>
                            </div>
                            <div class="av-respuestas-contenido"></div>
                        </div>
                    </div>
                `);
                $('body').append(modal);

                modal.on('click', '.av-modal-cerrar', function() {
                    modal.removeClass('activo');
                });

                modal.on('click', function(e) {
                    if ($(e.target).is('.av-modal')) {
                        modal.removeClass('activo');
                    }
                });
            }

            const contenedor = modal.find('.av-respuestas-contenido');
            const html = respuestas.length ? respuestas.map(function(respuesta) {
                const disponibilidad = respuesta.disponibilidad_propuesta
                    ? `<div class="av-respuesta-meta"><strong>Disponibilidad:</strong> ${respuesta.disponibilidad_propuesta}</div>`
                    : '';

                return `
                    <div class="av-respuesta-item">
                        <div class="av-respuesta-head">
                            <strong>${respuesta.ayudante && respuesta.ayudante.nombre ? respuesta.ayudante.nombre : 'Vecino'}</strong>
                            <span class="av-respuesta-estado estado-${respuesta.estado || 'pendiente'}">${respuesta.estado || 'pendiente'}</span>
                        </div>
                        <div class="av-respuesta-mensaje">${respuesta.mensaje || ''}</div>
                        ${disponibilidad}
                        <div class="av-respuesta-meta">${respuesta.fecha_formateada || ''}</div>
                        ${respuesta.estado === 'pendiente' ? `<div class="av-respuesta-acciones"><button class="btn-principal btn-aceptar-respuesta" data-respuesta-id="${respuesta.id}" data-solicitud-id="${idSolicitud}">Aceptar ayuda</button></div>` : ''}
                    </div>
                `;
            }).join('') : `<div class="av-empty-state"><p>${ayudaVecinalData.strings.sin_respuestas}</p></div>`;

            contenedor.html(html);
            modal.addClass('activo');
        },

        /**
         * Obtener mensaje util desde una respuesta REST
         */
        obtenerMensajeError: function(xhr) {
            if (xhr && xhr.responseJSON) {
                if (xhr.responseJSON.message) {
                    return xhr.responseJSON.message;
                }
                if (xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    return xhr.responseJSON.data.message;
                }
            }

            return ayudaVecinalData.strings.error_general;
        },

        /**
         * Validar formulario
         */
        validarFormulario: function(formulario) {
            let valido = true;

            formulario.find('[required]').each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('error');
                    valido = false;
                } else {
                    $(this).removeClass('error');
                }
            });

            if (!valido) {
                this.mostrarNotificacion('Por favor, completa todos los campos obligatorios', 'aviso');
            }

            return valido;
        },

        /**
         * Mostrar notificación
         */
        mostrarNotificacion: function(mensaje, tipo) {
            // Eliminar notificaciones anteriores
            $('.av-notificacion').remove();

            const clases = {
                'exito': 'av-notificacion-exito',
                'error': 'av-notificacion-error',
                'aviso': 'av-notificacion-aviso'
            };

            const notificacion = $(`
                <div class="av-notificacion ${clases[tipo] || ''}">
                    <span class="av-notificacion-texto">${mensaje}</span>
                    <button class="av-notificacion-cerrar">&times;</button>
                </div>
            `);

            $('body').append(notificacion);

            // Animar entrada
            setTimeout(function() {
                notificacion.addClass('visible');
            }, 10);

            // Auto-cerrar después de 5 segundos
            setTimeout(function() {
                notificacion.removeClass('visible');
                setTimeout(function() {
                    notificacion.remove();
                }, 300);
            }, 5000);

            // Cerrar al hacer click
            notificacion.on('click', '.av-notificacion-cerrar', function() {
                notificacion.removeClass('visible');
                setTimeout(function() {
                    notificacion.remove();
                }, 300);
            });
        }
    };

    // Estilos adicionales para notificaciones y modal
    const estilosAdicionales = `
        <style>
            .av-notificacion {
                position: fixed;
                bottom: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                background: #1f2937;
                color: white;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                display: flex;
                align-items: center;
                gap: 1rem;
                z-index: 10000;
                transform: translateY(100px);
                opacity: 0;
                transition: all 0.3s ease;
            }
            .av-notificacion.visible {
                transform: translateY(0);
                opacity: 1;
            }
            .av-notificacion-exito { background: #059669; }
            .av-notificacion-error { background: #dc2626; }
            .av-notificacion-aviso { background: #d97706; }
            .av-notificacion-cerrar {
                background: none;
                border: none;
                color: white;
                font-size: 1.25rem;
                cursor: pointer;
                opacity: 0.7;
            }
            .av-notificacion-cerrar:hover { opacity: 1; }

            .av-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 10001;
                align-items: center;
                justify-content: center;
            }
            .av-modal.activo { display: flex; }
            .av-modal-contenido {
                background: white;
                border-radius: 12px;
                padding: 1.5rem;
                max-width: 500px;
                width: 90%;
                max-height: 90vh;
                overflow-y: auto;
            }
            .av-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1.5rem;
            }
            .av-modal-header h3 { margin: 0; }
            .av-modal-cerrar {
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: #6b7280;
            }

            .av-respuestas-contenido {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .av-respuesta-item {
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                padding: 1rem;
                background: #fff;
            }

            .av-respuesta-head {
                display: flex;
                justify-content: space-between;
                gap: 1rem;
                margin-bottom: 0.5rem;
            }

            .av-respuesta-mensaje {
                color: #374151;
                margin-bottom: 0.5rem;
                white-space: pre-wrap;
            }

            .av-respuesta-meta {
                color: #6b7280;
                font-size: 0.875rem;
            }

            .av-respuesta-acciones {
                margin-top: 0.75rem;
            }

            .av-respuesta-estado {
                font-size: 0.75rem;
                border-radius: 999px;
                padding: 0.2rem 0.6rem;
                background: #e5e7eb;
                color: #374151;
                text-transform: capitalize;
            }

            .estado-pendiente {
                background: #fef3c7;
                color: #92400e;
            }

            .estado-aceptada {
                background: #dcfce7;
                color: #166534;
            }

            .form-grupo input.error,
            .form-grupo select.error,
            .form-grupo textarea.error {
                border-color: #ef4444;
            }
        </style>
    `;

    $('head').append(estilosAdicionales);

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        AyudaVecinal.init();
    });

})(jQuery);
