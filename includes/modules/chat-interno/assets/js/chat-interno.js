/**
 * Chat Interno Frontend JavaScript
 * Flavor Chat IA - Sistema de Mensajeria Privada
 */

(function($) {
    'use strict';

    // Configuracion global
    const FlavorChatInterno = {
        ajaxurl: flavorChatInterno?.ajaxurl || '/wp-admin/admin-ajax.php',
        resturl: flavorChatInterno?.resturl || '/wp-json/flavor/v1/chat-interno/',
        nonce: flavorChatInterno?.nonce || '',
        userId: flavorChatInterno?.user_id || 0,
        userName: flavorChatInterno?.user_name || '',
        userAvatar: flavorChatInterno?.user_avatar || '',
        strings: flavorChatInterno?.strings || {},
        pollingInterval: flavorChatInterno?.polling_interval || 3000,
        typingTimeout: flavorChatInterno?.typing_timeout || 3000,
        maxFileSize: flavorChatInterno?.max_file_size || 26214400,
        allowedTypes: flavorChatInterno?.allowed_types || [],

        // Estado
        conversacionActual: null,
        pollingTimer: null,
        typingTimer: null,
        lastMessageId: 0,
        isTyping: false,
        respondiendoA: null,
        pendingScroll: false,
    };

    // Inicializacion
    $(document).ready(function() {
        if ($('#flavor-chat-interno-app').length) {
            FlavorChatInterno.init();
        }
        if ($('#flavor-chat-interno-single').length) {
            FlavorChatInterno.initSingle();
        }
        // Botones de iniciar chat en otros lugares
        FlavorChatInterno.initBotonesIniciarChat();
    });

    /**
     * Inicializacion principal
     */
    FlavorChatInterno.init = function() {
        this.bindEvents();
        this.loadConversaciones();
        this.requestNotificationPermission();

        // Actualizar estado periodicamente
        setInterval(() => this.actualizarEstado(), 60000);
    };

    /**
     * Inicializacion modo single (conversacion individual)
     */
    FlavorChatInterno.initSingle = function() {
        const container = $('#flavor-chat-interno-single');
        const conversacionId = container.data('conversacion');

        if (conversacionId) {
            this.conversacionActual = conversacionId;
            this.bindEventsSingle();
            this.cargarMensajes(conversacionId);
            this.iniciarPolling();
        }
    };

    /**
     * Inicializar botones de iniciar chat externos
     */
    FlavorChatInterno.initBotonesIniciarChat = function() {
        const self = this;

        $(document).on('click', '.ci-btn-iniciar-chat', function(e) {
            e.preventDefault();
            const usuarioId = $(this).data('usuario');
            if (usuarioId) {
                self.iniciarConversacion(usuarioId);
            }
        });
    };

    /**
     * Bind de eventos
     */
    FlavorChatInterno.bindEvents = function() {
        const self = this;

        // Seleccionar conversacion
        $(document).on('click', '.ci-conversacion-item', function() {
            const conversacionId = $(this).data('id');
            self.abrirConversacion(conversacionId);
        });

        // Enviar mensaje
        $(document).on('click', '#ci-btn-enviar', function() {
            self.enviarMensaje();
        });

        $('#ci-mensaje-input').on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                self.enviarMensaje();
            }
        });

        // Habilitar/deshabilitar boton enviar
        $('#ci-mensaje-input').on('input', function() {
            const tieneTexto = $(this).val().trim().length > 0;
            $('#ci-btn-enviar').prop('disabled', !tieneTexto);
            self.sendTypingIndicator();
            self.autoResizeTextarea(this);
        });

        // Nuevo mensaje
        $(document).on('click', '.ci-btn-nuevo, .ci-btn-nuevo-main', function() {
            self.mostrarModalNuevo();
        });

        // Buscar usuarios en modal
        $('#ci-buscar-usuario').on('input', debounce(function() {
            self.buscarUsuarios($(this).val());
        }, 300));

        // Seleccionar usuario en resultados
        $(document).on('click', '.ci-usuario-resultado-item', function() {
            self.seleccionarUsuario($(this));
        });

        // Enviar nuevo mensaje
        $(document).on('click', '#ci-btn-enviar-nuevo', function() {
            self.enviarNuevoMensaje();
        });

        // Cerrar modal
        $(document).on('click', '.ci-modal-overlay, .ci-modal-cerrar, .ci-modal-cancelar', function() {
            self.cerrarModal();
        });

        // Volver (mobile)
        $(document).on('click', '.ci-btn-back', function() {
            self.volverALista();
        });

        // Responder mensaje
        $(document).on('click', '.ci-btn-responder', function() {
            const mensaje = $(this).closest('.ci-mensaje');
            self.prepararRespuesta(mensaje);
        });

        // Cancelar respuesta
        $(document).on('click', '#ci-respuesta-cancelar', function() {
            self.cancelarRespuesta();
        });

        // Eliminar mensaje
        $(document).on('click', '.ci-btn-eliminar', function() {
            const mensajeId = $(this).closest('.ci-mensaje').data('id');
            self.confirmarEliminarMensaje(mensajeId);
        });

        // Editar mensaje
        $(document).on('click', '.ci-btn-editar', function() {
            const mensaje = $(this).closest('.ci-mensaje');
            self.habilitarEdicion(mensaje);
        });

        // Menu de conversacion
        $(document).on('click', '.ci-btn-menu', function(e) {
            e.stopPropagation();
            self.mostrarMenuConversacion($(this));
        });

        // Acciones del menu
        $(document).on('click', '.ci-dropdown-item', function() {
            const action = $(this).data('action');
            self.ejecutarAccionMenu(action);
        });

        // Cerrar menu al hacer clic fuera
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.ci-dropdown-menu, .ci-btn-menu').length) {
                $('#ci-menu-conversacion').hide();
            }
        });

        // Panel info
        $(document).on('click', '.ci-btn-info, .ci-usuario-info', function() {
            self.mostrarPanelInfo();
        });

        $(document).on('click', '.ci-panel-cerrar', function() {
            self.cerrarPanelInfo();
        });

        // Adjuntar archivo
        $(document).on('click', '.ci-btn-adjuntar', function() {
            $('#ci-file-input').click();
        });

        $('#ci-file-input').on('change', function() {
            self.procesarArchivo(this.files[0]);
        });

        // Scroll para cargar mas
        $('.ci-mensajes-container').on('scroll', function() {
            if (this.scrollTop === 0 && self.conversacionActual) {
                self.cargarMasMensajes();
            }
        });

        // Buscar conversaciones
        $('#ci-buscar-conversacion').on('input', debounce(function() {
            self.filtrarConversaciones($(this).val());
        }, 300));

        // Toggle archivados
        $(document).on('click', '#ci-toggle-archivados', function(e) {
            e.preventDefault();
            self.toggleArchivados();
        });

        // Buscar en chat
        $(document).on('click', '.ci-btn-buscar', function() {
            self.mostrarBusquedaEnChat();
        });
    };

    /**
     * Bind eventos para single chat
     */
    FlavorChatInterno.bindEventsSingle = function() {
        const self = this;

        $('#ci-btn-enviar').on('click', function() {
            self.enviarMensaje();
        });

        $('#ci-mensaje-input').on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                self.enviarMensaje();
            }
        });

        $('#ci-mensaje-input').on('input', function() {
            const tieneTexto = $(this).val().trim().length > 0;
            $('#ci-btn-enviar').prop('disabled', !tieneTexto);
            self.sendTypingIndicator();
            self.autoResizeTextarea(this);
        });
    };

    // =========================================================================
    // CARGA DE DATOS
    // =========================================================================

    /**
     * Cargar conversaciones
     */
    FlavorChatInterno.loadConversaciones = function(incluirArchivadas = false) {
        const self = this;
        const container = $('#ci-conversaciones');

        container.html('<div class="ci-loading"><span class="ci-spinner"></span></div>');

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_chat_interno_conversaciones',
                nonce: this.nonce,
                archivadas: incluirArchivadas ? 1 : 0,
            },
            success: function(response) {
                if (response.success) {
                    self.renderConversaciones(container, response.conversaciones);
                } else {
                    container.html('<div class="ci-empty"><p>' + response.error + '</p></div>');
                }
            },
            error: function() {
                container.html('<div class="ci-empty"><p>' + self.strings.error + '</p></div>');
            }
        });
    };

    /**
     * Renderizar lista de conversaciones
     */
    FlavorChatInterno.renderConversaciones = function(container, conversaciones) {
        if (!conversaciones || !conversaciones.length) {
            container.html(`
                <div class="ci-empty">
                    <span class="dashicons dashicons-email-alt"></span>
                    <p>${this.strings.sin_mensajes || 'No tienes conversaciones'}</p>
                </div>
            `);
            return;
        }

        let html = '';
        conversaciones.forEach(conv => {
            const estadoClase = conv.con_usuario.estado === 'online' ? 'online' : 'offline';
            const noLeidoClase = conv.no_leidos > 0 ? 'no-leido' : '';
            const archivadaClase = conv.archivado ? 'archivada' : '';

            let previewHtml = '';
            if (conv.ultimo_remitente_soy_yo) {
                previewHtml = `<span class="ci-yo">${this.strings.tu || 'Tu'}:</span> `;
            }
            previewHtml += this.escapeHtml(conv.ultimo_mensaje || '');

            let iconoPreview = '';
            if (conv.ultimo_mensaje_tipo === 'imagen') {
                iconoPreview = '<span class="dashicons dashicons-format-image"></span> ';
            } else if (conv.ultimo_mensaje_tipo === 'archivo') {
                iconoPreview = '<span class="dashicons dashicons-media-default"></span> ';
            } else if (conv.ultimo_mensaje_tipo === 'audio') {
                iconoPreview = '<span class="dashicons dashicons-format-audio"></span> ';
            }

            html += `
                <div class="ci-conversacion-item ${noLeidoClase} ${archivadaClase}" data-id="${conv.id}">
                    <div class="ci-conversacion-avatar">
                        <img src="${conv.con_usuario.avatar}" alt="">
                        <span class="ci-estado-indicador ${estadoClase}"></span>
                    </div>
                    <div class="ci-conversacion-info">
                        <div class="ci-conversacion-header">
                            <span class="ci-conversacion-nombre">${this.escapeHtml(conv.con_usuario.nombre)}</span>
                            <span class="ci-conversacion-fecha">${conv.fecha}</span>
                        </div>
                        <div class="ci-conversacion-preview">
                            ${iconoPreview}${previewHtml}
                        </div>
                    </div>
                    <div class="ci-conversacion-meta">
                        ${conv.no_leidos > 0 ? `<span class="ci-badge-no-leidos">${conv.no_leidos > 99 ? '99+' : conv.no_leidos}</span>` : ''}
                        ${conv.silenciado ? '<span class="ci-icono-silenciado dashicons dashicons-controls-volumeoff"></span>' : ''}
                    </div>
                </div>
            `;
        });

        container.html(html);
    };

    /**
     * Abrir conversacion
     */
    FlavorChatInterno.abrirConversacion = function(conversacionId) {
        const self = this;

        // Marcar como activa
        $('.ci-conversacion-item').removeClass('active');
        $(`.ci-conversacion-item[data-id="${conversacionId}"]`).addClass('active').removeClass('no-leido');
        $(`.ci-conversacion-item[data-id="${conversacionId}"] .ci-badge-no-leidos`).remove();

        // Mostrar chat
        $('#ci-placeholder').hide();
        $('#ci-chat-container').show();

        // En mobile, ocultar sidebar
        if ($(window).width() <= 900) {
            $('.ci-sidebar').addClass('oculto');
        }

        // Limpiar estado anterior
        this.detenerPolling();
        this.conversacionActual = conversacionId;
        this.lastMessageId = 0;
        this.cancelarRespuesta();

        $('#ci-mensajes').html('<div class="ci-loading"><span class="ci-spinner"></span></div>');

        // Cargar mensajes
        this.cargarMensajes(conversacionId);
    };

    /**
     * Cargar mensajes
     */
    FlavorChatInterno.cargarMensajes = function(conversacionId, antesDeId = 0) {
        const self = this;
        const container = $('#ci-mensajes');

        const params = {
            action: 'flavor_chat_interno_mensajes',
            nonce: this.nonce,
            conversacion_id: conversacionId,
            limite: 50,
        };

        if (antesDeId) {
            params.antes_de = antesDeId;
        }

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: params,
            success: function(response) {
                if (response.success) {
                    // Actualizar header
                    if (response.conversacion && response.conversacion.con_usuario) {
                        self.actualizarHeaderChat(response.conversacion.con_usuario);
                    }

                    if (antesDeId) {
                        // Prepend mensajes antiguos
                        const oldScrollHeight = container[0].scrollHeight;
                        self.prependMensajes(container, response.mensajes);
                        container[0].scrollTop = container[0].scrollHeight - oldScrollHeight;
                    } else {
                        self.renderMensajes(container, response.mensajes);
                        self.scrollToBottom();
                    }

                    // Actualizar ultimo mensaje
                    if (response.mensajes.length > 0) {
                        const ultimoId = response.mensajes[response.mensajes.length - 1].id;
                        if (ultimoId > self.lastMessageId) {
                            self.lastMessageId = ultimoId;
                        }
                    }

                    // Iniciar polling
                    if (!antesDeId) {
                        self.iniciarPolling();
                    }
                } else {
                    container.html('<div class="ci-empty"><p>' + response.error + '</p></div>');
                }
            },
            error: function() {
                container.html('<div class="ci-empty"><p>' + self.strings.error + '</p></div>');
            }
        });
    };

    /**
     * Cargar mas mensajes (scroll hacia arriba)
     */
    FlavorChatInterno.cargarMasMensajes = function() {
        if (!this.conversacionActual || this.pendingScroll) return;

        const primerMensaje = $('#ci-mensajes .ci-mensaje').first();
        if (!primerMensaje.length) return;

        const primerMensajeId = primerMensaje.data('id');
        if (!primerMensajeId) return;

        this.pendingScroll = true;
        this.cargarMensajes(this.conversacionActual, primerMensajeId);

        // Reset flag despues de un tiempo
        setTimeout(() => {
            this.pendingScroll = false;
        }, 1000);
    };

    /**
     * Actualizar header del chat
     */
    FlavorChatInterno.actualizarHeaderChat = function(usuario) {
        $('#ci-chat-avatar').attr('src', usuario.avatar);
        $('#ci-chat-nombre').text(usuario.nombre);

        const estadoTexto = usuario.estado === 'online'
            ? this.strings.online || 'En linea'
            : this.strings.offline || 'Desconectado';
        $('#ci-chat-estado')
            .text(estadoTexto)
            .toggleClass('online', usuario.estado === 'online');
    };

    /**
     * Renderizar mensajes
     */
    FlavorChatInterno.renderMensajes = function(container, mensajes) {
        if (!mensajes || !mensajes.length) {
            container.html(`
                <div class="ci-empty">
                    <span class="dashicons dashicons-format-chat"></span>
                    <p>${this.strings.sin_mensajes || 'No hay mensajes'}</p>
                </div>
            `);
            return;
        }

        let html = '';
        let fechaAnterior = '';
        let remitentAnterior = null;

        mensajes.forEach((msg, index) => {
            // Separador de fecha
            const fecha = msg.fecha.split(' ')[0];
            if (fecha !== fechaAnterior) {
                html += `<div class="ci-fecha-separador"><span>${this.formatearFecha(fecha)}</span></div>`;
                fechaAnterior = fecha;
                remitentAnterior = null;
            }

            // Verificar si es continuacion
            const esContinuacion = remitentAnterior === msg.remitente_id;
            remitentAnterior = msg.remitente_id;

            html += this.renderMensaje(msg, esContinuacion);
        });

        container.html(html);
    };

    /**
     * Prepend mensajes antiguos
     */
    FlavorChatInterno.prependMensajes = function(container, mensajes) {
        if (!mensajes || !mensajes.length) return;

        let html = '';
        let fechaAnterior = '';
        let remitentAnterior = null;

        // Obtener primera fecha actual
        const primerSeparador = container.find('.ci-fecha-separador').first();
        if (primerSeparador.length) {
            fechaAnterior = primerSeparador.find('span').text();
        }

        mensajes.forEach(msg => {
            const fecha = msg.fecha.split(' ')[0];
            if (fecha !== fechaAnterior) {
                html += `<div class="ci-fecha-separador"><span>${this.formatearFecha(fecha)}</span></div>`;
                fechaAnterior = fecha;
                remitentAnterior = null;
            }

            const esContinuacion = remitentAnterior === msg.remitente_id;
            remitentAnterior = msg.remitente_id;

            html += this.renderMensaje(msg, esContinuacion);
        });

        container.prepend(html);
    };

    /**
     * Renderizar un mensaje
     */
    FlavorChatInterno.renderMensaje = function(msg, esContinuacion = false) {
        const esMio = msg.es_mio;
        const clases = ['ci-mensaje'];
        if (esMio) clases.push('mio');
        if (esContinuacion) clases.push('continuacion');
        if (msg.tipo === 'sistema') clases.push('ci-mensaje-sistema');

        // Mensaje de sistema
        if (msg.tipo === 'sistema') {
            return `
                <div class="${clases.join(' ')}" data-id="${msg.id}">
                    <div class="ci-mensaje-burbuja">
                        <span class="ci-mensaje-texto">${msg.mensaje}</span>
                    </div>
                </div>
            `;
        }

        // Contenido del mensaje
        let contenidoHtml = '';

        // Responde a
        if (msg.responde_a) {
            contenidoHtml += `
                <div class="ci-mensaje-responde-a">
                    <span class="ci-mensaje-responde-a-autor">${this.escapeHtml(msg.responde_a.autor)}</span>
                    ${this.escapeHtml(msg.responde_a.mensaje)}
                </div>
            `;
        }

        // Texto o estado eliminado
        if (msg.eliminado) {
            contenidoHtml += `
                <span class="ci-mensaje-eliminado">
                    <span class="dashicons dashicons-dismiss"></span>
                    ${this.strings.mensaje_eliminado || 'Mensaje eliminado'}
                </span>
            `;
        } else {
            contenidoHtml += `<span class="ci-mensaje-texto">${msg.mensaje_html || this.escapeHtml(msg.mensaje)}</span>`;
        }

        // Adjuntos
        if (msg.adjunto && !msg.eliminado) {
            contenidoHtml += this.renderAdjunto(msg.adjunto);
        }

        // Acciones del mensaje
        let accionesHtml = '';
        if (!msg.eliminado) {
            accionesHtml = `
                <div class="ci-mensaje-acciones">
                    <button type="button" class="ci-btn-responder" title="Responder">
                        <span class="dashicons dashicons-undo"></span>
                    </button>
                    ${esMio ? `
                        <button type="button" class="ci-btn-editar" title="Editar">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="ci-btn-eliminar" title="Eliminar">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    ` : ''}
                </div>
            `;
        }

        // Meta del mensaje
        let metaHtml = `<span class="ci-mensaje-hora">${msg.hora}</span>`;
        if (msg.editado) {
            metaHtml += `<span class="ci-mensaje-editado">${this.strings.mensaje_editado || 'editado'}</span>`;
        }
        if (esMio) {
            const estadoIcono = msg.leido ? 'yes-alt' : 'yes';
            const estadoClase = msg.leido ? 'leido' : '';
            metaHtml += `<span class="ci-mensaje-estado ${estadoClase}"><span class="dashicons dashicons-${estadoIcono}"></span></span>`;
        }

        return `
            <div class="${clases.join(' ')}" data-id="${msg.id}">
                <img src="${msg.remitente_avatar}" alt="" class="ci-mensaje-avatar">
                <div class="ci-mensaje-contenido">
                    <div class="ci-mensaje-burbuja">
                        ${contenidoHtml}
                        ${accionesHtml}
                    </div>
                    <div class="ci-mensaje-meta">
                        ${metaHtml}
                    </div>
                </div>
            </div>
        `;
    };

    /**
     * Renderizar adjunto
     */
    FlavorChatInterno.renderAdjunto = function(adjunto) {
        if (!adjunto) return '';

        let html = '<div class="ci-mensaje-adjunto">';

        if (adjunto.es_imagen) {
            html += `<img src="${adjunto.url}" class="ci-adjunto-imagen" alt="${this.escapeHtml(adjunto.nombre)}" loading="lazy">`;
        } else if (adjunto.es_audio) {
            html += `<audio controls class="ci-adjunto-audio"><source src="${adjunto.url}" type="${adjunto.tipo}"></audio>`;
        } else {
            const tamano = this.formatearTamano(adjunto.tamano);
            html += `
                <a href="${adjunto.url}" class="ci-adjunto-archivo" target="_blank" download>
                    <span class="ci-adjunto-icono">
                        <span class="dashicons dashicons-media-default"></span>
                    </span>
                    <span class="ci-adjunto-info">
                        <span class="ci-adjunto-nombre">${this.escapeHtml(adjunto.nombre)}</span>
                        <span class="ci-adjunto-tamano">${tamano}</span>
                    </span>
                </a>
            `;
        }

        html += '</div>';
        return html;
    };

    // =========================================================================
    // ENVIO DE MENSAJES
    // =========================================================================

    /**
     * Enviar mensaje
     */
    FlavorChatInterno.enviarMensaje = function() {
        const input = $('#ci-mensaje-input');
        const mensaje = input.val().trim();

        if (!mensaje || !this.conversacionActual) return;

        const self = this;
        const btnEnviar = $('#ci-btn-enviar');

        btnEnviar.prop('disabled', true);

        const datos = {
            action: 'flavor_chat_interno_enviar',
            nonce: this.nonce,
            conversacion_id: this.conversacionActual,
            mensaje: mensaje,
            tipo: 'texto',
        };

        if (this.respondiendoA) {
            datos.responde_a = this.respondiendoA.id;
        }

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: datos,
            success: function(response) {
                if (response.success) {
                    // Limpiar input
                    input.val('').trigger('input');
                    self.cancelarRespuesta();

                    // Agregar mensaje a la lista
                    self.agregarMensajeALista(response.mensaje);
                    self.scrollToBottom();

                    // Actualizar last message id
                    self.lastMessageId = response.mensaje.id;

                    // Actualizar preview en lista
                    self.actualizarPreviewConversacion(self.conversacionActual, response.mensaje);
                } else {
                    self.mostrarError(response.error);
                }
            },
            error: function() {
                self.mostrarError(self.strings.error);
            },
            complete: function() {
                btnEnviar.prop('disabled', false);
            }
        });
    };

    /**
     * Agregar mensaje a la lista
     */
    FlavorChatInterno.agregarMensajeALista = function(msg) {
        const container = $('#ci-mensajes');

        // Quitar empty state si existe
        container.find('.ci-empty').remove();

        // Verificar si necesita separador de fecha
        const fechaHoy = new Date().toISOString().split('T')[0];
        const ultimoSeparador = container.find('.ci-fecha-separador').last();

        if (!ultimoSeparador.length || ultimoSeparador.find('span').text() !== this.formatearFecha(fechaHoy)) {
            container.append(`<div class="ci-fecha-separador"><span>${this.formatearFecha(fechaHoy)}</span></div>`);
        }

        // Agregar mensaje
        const html = this.renderMensaje(msg, false);
        container.append(html);
    };

    /**
     * Actualizar preview de conversacion en la lista
     */
    FlavorChatInterno.actualizarPreviewConversacion = function(conversacionId, mensaje) {
        const item = $(`.ci-conversacion-item[data-id="${conversacionId}"]`);
        if (!item.length) return;

        const preview = item.find('.ci-conversacion-preview');
        let previewText = `<span class="ci-yo">${this.strings.tu || 'Tu'}:</span> `;
        previewText += this.escapeHtml(mensaje.mensaje).substring(0, 50);

        preview.html(previewText);
        item.find('.ci-conversacion-fecha').text(this.strings.ahora || 'ahora');

        // Mover al inicio
        item.parent().prepend(item);
    };

    // =========================================================================
    // NUEVO MENSAJE
    // =========================================================================

    /**
     * Mostrar modal de nuevo mensaje
     */
    FlavorChatInterno.mostrarModalNuevo = function() {
        $('#ci-modal-nuevo').show();
        $('#ci-buscar-usuario').val('').focus();
        $('#ci-nuevo-mensaje').val('');
        $('#ci-nuevo-usuario-id').val('');
        $('#ci-usuarios-resultado').html('').removeClass('visible');
        $('#ci-btn-enviar-nuevo').prop('disabled', true);
    };

    /**
     * Cerrar modal
     */
    FlavorChatInterno.cerrarModal = function() {
        $('.ci-modal').hide();
    };

    /**
     * Buscar usuarios
     */
    FlavorChatInterno.buscarUsuarios = function(query) {
        const container = $('#ci-usuarios-resultado');

        if (query.length < 2) {
            container.html('').removeClass('visible');
            return;
        }

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_chat_interno_usuarios',
                nonce: this.nonce,
                query: query,
            },
            success: function(response) {
                if (response.success && response.usuarios.length) {
                    let html = '';
                    response.usuarios.forEach(u => {
                        const estadoClase = u.estado === 'online' ? 'online' : '';
                        html += `
                            <div class="ci-usuario-resultado-item" data-id="${u.id}" data-nombre="${u.nombre}" data-avatar="${u.avatar}">
                                <img src="${u.avatar}" alt="">
                                <span>${u.nombre}</span>
                            </div>
                        `;
                    });
                    container.html(html).addClass('visible');
                } else {
                    container.html('<div style="padding: 1rem; color: #666;">No se encontraron usuarios</div>').addClass('visible');
                }
            }
        });
    };

    /**
     * Seleccionar usuario
     */
    FlavorChatInterno.seleccionarUsuario = function(item) {
        const usuarioId = item.data('id');
        const nombre = item.data('nombre');

        $('#ci-buscar-usuario').val(nombre);
        $('#ci-nuevo-usuario-id').val(usuarioId);
        $('#ci-usuarios-resultado').removeClass('visible');

        $('.ci-usuario-resultado-item').removeClass('seleccionado');
        item.addClass('seleccionado');

        this.verificarFormNuevo();
    };

    /**
     * Verificar formulario nuevo mensaje
     */
    FlavorChatInterno.verificarFormNuevo = function() {
        const tieneUsuario = $('#ci-nuevo-usuario-id').val();
        const tieneMensaje = $('#ci-nuevo-mensaje').val().trim().length > 0;

        $('#ci-btn-enviar-nuevo').prop('disabled', !tieneUsuario);
    };

    /**
     * Enviar nuevo mensaje
     */
    FlavorChatInterno.enviarNuevoMensaje = function() {
        const usuarioId = $('#ci-nuevo-usuario-id').val();
        const mensaje = $('#ci-nuevo-mensaje').val().trim();

        if (!usuarioId) return;

        this.iniciarConversacion(usuarioId, mensaje);
    };

    /**
     * Iniciar conversacion
     */
    FlavorChatInterno.iniciarConversacion = function(usuarioId, mensajeInicial = '') {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_interno_iniciar',
                nonce: this.nonce,
                usuario_id: usuarioId,
                mensaje: mensajeInicial,
            },
            success: function(response) {
                if (response.success) {
                    self.cerrarModal();
                    self.loadConversaciones();

                    // Abrir la conversacion
                    setTimeout(() => {
                        self.abrirConversacion(response.conversacion_id);
                    }, 500);
                } else {
                    self.mostrarError(response.error);
                }
            },
            error: function() {
                self.mostrarError(self.strings.error);
            }
        });
    };

    // =========================================================================
    // POLLING Y TIEMPO REAL
    // =========================================================================

    /**
     * Iniciar polling
     */
    FlavorChatInterno.iniciarPolling = function() {
        this.detenerPolling();

        const self = this;
        this.pollingTimer = setInterval(() => {
            self.pollNuevosMensajes();
        }, this.pollingInterval);
    };

    /**
     * Detener polling
     */
    FlavorChatInterno.detenerPolling = function() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;
        }
    };

    /**
     * Poll nuevos mensajes
     */
    FlavorChatInterno.pollNuevosMensajes = function() {
        if (!this.conversacionActual) return;

        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_chat_interno_poll',
                nonce: this.nonce,
                conversacion_id: this.conversacionActual,
                ultimo_mensaje_id: this.lastMessageId,
            },
            success: function(response) {
                if (response.success) {
                    // Agregar nuevos mensajes
                    if (response.mensajes && response.mensajes.length) {
                        response.mensajes.forEach(msg => {
                            if (!msg.es_mio) {
                                self.agregarMensajeALista(msg);
                                self.mostrarNotificacion(msg);
                            }
                            if (msg.id > self.lastMessageId) {
                                self.lastMessageId = msg.id;
                            }
                        });
                        self.scrollToBottomIfNear();
                    }

                    // Typing indicator
                    self.mostrarTypingIndicator(response.escribiendo);

                    // Actualizar estados de lectura
                    if (response.ultimo_leido_por_otro) {
                        self.actualizarEstadosLectura(response.ultimo_leido_por_otro);
                    }

                    // Actualizar estado del usuario
                    if (response.estado_otro_usuario) {
                        const estadoTexto = response.estado_otro_usuario === 'online'
                            ? self.strings.online
                            : self.strings.offline;
                        $('#ci-chat-estado')
                            .text(estadoTexto)
                            .toggleClass('online', response.estado_otro_usuario === 'online');
                    }
                }
            }
        });
    };

    /**
     * Mostrar typing indicator
     */
    FlavorChatInterno.mostrarTypingIndicator = function(escribiendo) {
        const indicator = $('#ci-typing');

        if (escribiendo) {
            const nombre = $('#ci-chat-nombre').text();
            indicator.find('.ci-typing-text').text(nombre + ' ' + (this.strings.escribiendo || 'escribiendo...'));
            indicator.show();
        } else {
            indicator.hide();
        }
    };

    /**
     * Enviar typing indicator
     */
    FlavorChatInterno.sendTypingIndicator = function() {
        if (!this.conversacionActual) return;

        const self = this;

        // Limpiar timer anterior
        if (this.typingTimer) {
            clearTimeout(this.typingTimer);
        }

        // Enviar solo si no estaba typing
        if (!this.isTyping) {
            this.isTyping = true;
            this.enviarTyping(true);
        }

        // Reset despues del timeout
        this.typingTimer = setTimeout(() => {
            self.isTyping = false;
            self.enviarTyping(false);
        }, this.typingTimeout);
    };

    /**
     * Enviar estado typing al servidor
     */
    FlavorChatInterno.enviarTyping = function(escribiendo) {
        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_interno_typing',
                nonce: this.nonce,
                conversacion_id: this.conversacionActual,
                escribiendo: escribiendo ? 1 : 0,
            }
        });
    };

    /**
     * Actualizar estados de lectura
     */
    FlavorChatInterno.actualizarEstadosLectura = function(ultimoLeidoId) {
        $('.ci-mensaje.mio').each(function() {
            const mensajeId = $(this).data('id');
            if (mensajeId <= ultimoLeidoId) {
                const estado = $(this).find('.ci-mensaje-estado');
                estado.addClass('leido');
                estado.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-yes-alt');
            }
        });
    };

    // =========================================================================
    // ACCIONES DE MENSAJE
    // =========================================================================

    /**
     * Preparar respuesta
     */
    FlavorChatInterno.prepararRespuesta = function(mensajeElement) {
        const mensajeId = mensajeElement.data('id');
        const texto = mensajeElement.find('.ci-mensaje-texto').text();
        const autor = mensajeElement.hasClass('mio')
            ? (this.strings.tu || 'Tu')
            : $('#ci-chat-nombre').text();

        this.respondiendoA = {
            id: mensajeId,
            texto: texto.substring(0, 100),
            autor: autor,
        };

        $('#ci-respuesta-texto').text(autor + ': ' + texto.substring(0, 100));
        $('#ci-respuesta-preview').show();
        $('#ci-mensaje-input').focus();
    };

    /**
     * Cancelar respuesta
     */
    FlavorChatInterno.cancelarRespuesta = function() {
        this.respondiendoA = null;
        $('#ci-respuesta-preview').hide();
    };

    /**
     * Confirmar eliminar mensaje
     */
    FlavorChatInterno.confirmarEliminarMensaje = function(mensajeId) {
        if (confirm(this.strings.confirmar_eliminar || 'Eliminar este mensaje?')) {
            this.eliminarMensaje(mensajeId);
        }
    };

    /**
     * Eliminar mensaje
     */
    FlavorChatInterno.eliminarMensaje = function(mensajeId) {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_interno_eliminar_msg',
                nonce: this.nonce,
                mensaje_id: mensajeId,
                para_todos: 1,
            },
            success: function(response) {
                if (response.success) {
                    const mensaje = $(`.ci-mensaje[data-id="${mensajeId}"]`);
                    mensaje.find('.ci-mensaje-texto').html(`
                        <span class="ci-mensaje-eliminado">
                            <span class="dashicons dashicons-dismiss"></span>
                            ${self.strings.mensaje_eliminado || 'Mensaje eliminado'}
                        </span>
                    `);
                    mensaje.find('.ci-mensaje-adjunto, .ci-mensaje-acciones').remove();
                }
            }
        });
    };

    /**
     * Habilitar edicion de mensaje
     */
    FlavorChatInterno.habilitarEdicion = function(mensajeElement) {
        const textoElement = mensajeElement.find('.ci-mensaje-texto');
        const textoOriginal = textoElement.text();

        const input = $('<input type="text" class="ci-editar-input">')
            .val(textoOriginal)
            .css({
                width: '100%',
                padding: '0.5rem',
                border: '1px solid var(--ci-primary)',
                borderRadius: '8px',
            });

        textoElement.replaceWith(input);
        input.focus().select();

        const self = this;
        const mensajeId = mensajeElement.data('id');

        input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                self.guardarEdicion(mensajeId, $(this).val(), textoOriginal);
            } else if (e.key === 'Escape') {
                input.replaceWith(`<span class="ci-mensaje-texto">${textoOriginal}</span>`);
            }
        });

        input.on('blur', function() {
            if ($(this).val() !== textoOriginal) {
                self.guardarEdicion(mensajeId, $(this).val(), textoOriginal);
            } else {
                input.replaceWith(`<span class="ci-mensaje-texto">${textoOriginal}</span>`);
            }
        });
    };

    /**
     * Guardar edicion de mensaje
     */
    FlavorChatInterno.guardarEdicion = function(mensajeId, nuevoTexto, textoOriginal) {
        const self = this;

        if (!nuevoTexto.trim()) {
            $(`.ci-mensaje[data-id="${mensajeId}"] .ci-editar-input`)
                .replaceWith(`<span class="ci-mensaje-texto">${textoOriginal}</span>`);
            return;
        }

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_interno_editar_msg',
                nonce: this.nonce,
                mensaje_id: mensajeId,
                mensaje: nuevoTexto,
            },
            success: function(response) {
                const mensaje = $(`.ci-mensaje[data-id="${mensajeId}"]`);
                const input = mensaje.find('.ci-editar-input');

                if (response.success) {
                    input.replaceWith(`<span class="ci-mensaje-texto">${response.mensaje_html}</span>`);

                    // Agregar indicador de editado si no existe
                    if (!mensaje.find('.ci-mensaje-editado').length) {
                        mensaje.find('.ci-mensaje-hora').after(`<span class="ci-mensaje-editado">${self.strings.mensaje_editado || 'editado'}</span>`);
                    }
                } else {
                    input.replaceWith(`<span class="ci-mensaje-texto">${textoOriginal}</span>`);
                    self.mostrarError(response.error);
                }
            },
            error: function() {
                $(`.ci-mensaje[data-id="${mensajeId}"] .ci-editar-input`)
                    .replaceWith(`<span class="ci-mensaje-texto">${textoOriginal}</span>`);
            }
        });
    };

    // =========================================================================
    // ARCHIVOS
    // =========================================================================

    /**
     * Procesar archivo
     */
    FlavorChatInterno.procesarArchivo = function(file) {
        if (!file) return;

        // Validar tamano
        if (file.size > this.maxFileSize) {
            this.mostrarError(this.strings.archivo_grande || 'El archivo es demasiado grande');
            return;
        }

        // Validar tipo
        const tiposPermitidos = this.allowedTypes;
        if (tiposPermitidos.length && !tiposPermitidos.includes(file.type)) {
            this.mostrarError(this.strings.tipo_no_permitido || 'Tipo de archivo no permitido');
            return;
        }

        this.subirArchivo(file);
    };

    /**
     * Subir archivo
     */
    FlavorChatInterno.subirArchivo = function(file) {
        const self = this;
        const formData = new FormData();

        formData.append('action', 'flavor_chat_interno_upload');
        formData.append('nonce', this.nonce);
        formData.append('file', file);

        // Mostrar indicador de carga
        const btnAdjuntar = $('.ci-btn-adjuntar');
        btnAdjuntar.prop('disabled', true).find('.dashicons').removeClass('dashicons-paperclip').addClass('dashicons-update ci-spin');

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    self.enviarMensajeConArchivo(response.archivo);
                } else {
                    self.mostrarError(response.error);
                }
            },
            error: function() {
                self.mostrarError(self.strings.error);
            },
            complete: function() {
                btnAdjuntar.prop('disabled', false).find('.dashicons').removeClass('dashicons-update ci-spin').addClass('dashicons-paperclip');
                $('#ci-file-input').val('');
            }
        });
    };

    /**
     * Enviar mensaje con archivo
     */
    FlavorChatInterno.enviarMensajeConArchivo = function(archivo) {
        const self = this;

        const tipo = archivo.es_imagen ? 'imagen' : (archivo.es_audio ? 'audio' : 'archivo');

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_interno_enviar',
                nonce: this.nonce,
                conversacion_id: this.conversacionActual,
                mensaje: archivo.nombre,
                tipo: tipo,
                adjunto_url: archivo.url,
                adjunto_nombre: archivo.nombre,
                adjunto_tamano: archivo.tamano,
                adjunto_tipo: archivo.tipo,
            },
            success: function(response) {
                if (response.success) {
                    response.mensaje.adjunto = archivo;
                    self.agregarMensajeALista(response.mensaje);
                    self.scrollToBottom();
                    self.lastMessageId = response.mensaje.id;
                }
            }
        });
    };

    // =========================================================================
    // MENU Y PANEL
    // =========================================================================

    /**
     * Mostrar menu de conversacion
     */
    FlavorChatInterno.mostrarMenuConversacion = function(btn) {
        const menu = $('#ci-menu-conversacion');
        const offset = btn.offset();

        menu.css({
            top: offset.top + btn.outerHeight() + 5,
            left: offset.left - menu.outerWidth() + btn.outerWidth(),
        }).show();
    };

    /**
     * Ejecutar accion del menu
     */
    FlavorChatInterno.ejecutarAccionMenu = function(action) {
        $('#ci-menu-conversacion').hide();

        switch (action) {
            case 'archivar':
                this.archivarConversacion();
                break;
            case 'silenciar':
                this.silenciarConversacion();
                break;
            case 'bloquear':
                this.confirmarBloquear();
                break;
        }
    };

    /**
     * Archivar conversacion
     */
    FlavorChatInterno.archivarConversacion = function() {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_interno_archivar',
                nonce: this.nonce,
                conversacion_id: this.conversacionActual,
                archivar: 1,
            },
            success: function(response) {
                if (response.success) {
                    self.volverALista();
                    self.loadConversaciones();
                }
            }
        });
    };

    /**
     * Silenciar conversacion
     */
    FlavorChatInterno.silenciarConversacion = function() {
        // TODO: Implementar
        console.log('Silenciar conversacion');
    };

    /**
     * Confirmar bloquear usuario
     */
    FlavorChatInterno.confirmarBloquear = function() {
        if (!confirm(this.strings.confirmar_bloquear || 'Bloquear a este usuario?')) {
            return;
        }

        this.bloquearUsuario();
    };

    /**
     * Bloquear usuario
     */
    FlavorChatInterno.bloquearUsuario = function() {
        const self = this;

        // Obtener ID del otro usuario de los datos
        const nombre = $('#ci-chat-nombre').text();

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_interno_bloquear',
                nonce: this.nonce,
                // Necesitamos obtener el usuario_id del otro participante
                conversacion_id: this.conversacionActual,
            },
            success: function(response) {
                if (response.success) {
                    self.volverALista();
                    self.loadConversaciones();
                }
            }
        });
    };

    /**
     * Mostrar panel de info
     */
    FlavorChatInterno.mostrarPanelInfo = function() {
        const panel = $('#ci-panel-info');

        // Obtener info del usuario
        const nombre = $('#ci-chat-nombre').text();
        const avatar = $('#ci-chat-avatar').attr('src');
        const estado = $('#ci-chat-estado').text();

        const contenido = `
            <div class="ci-panel-usuario">
                <img src="${avatar}" alt="" class="ci-panel-avatar">
                <h4 class="ci-panel-nombre">${nombre}</h4>
                <span class="ci-panel-estado-texto">${estado}</span>
                <div class="ci-panel-acciones">
                    <button type="button" class="ci-panel-btn" data-action="silenciar">
                        <span class="dashicons dashicons-controls-volumeoff"></span>
                        Silenciar
                    </button>
                    <button type="button" class="ci-panel-btn danger" data-action="bloquear">
                        <span class="dashicons dashicons-dismiss"></span>
                        Bloquear
                    </button>
                </div>
            </div>
            <div class="ci-panel-seccion">
                <div class="ci-panel-seccion-titulo">Opciones</div>
                <div class="ci-panel-item" data-action="buscar">
                    <span class="dashicons dashicons-search"></span>
                    Buscar en conversacion
                </div>
                <div class="ci-panel-item" data-action="archivar">
                    <span class="dashicons dashicons-archive"></span>
                    Archivar conversacion
                </div>
            </div>
        `;

        $('#ci-panel-contenido').html(contenido);
        panel.show();

        if ($(window).width() <= 900) {
            panel.addClass('visible');
        }
    };

    /**
     * Cerrar panel de info
     */
    FlavorChatInterno.cerrarPanelInfo = function() {
        $('#ci-panel-info').hide().removeClass('visible');
    };

    /**
     * Volver a lista (mobile)
     */
    FlavorChatInterno.volverALista = function() {
        this.detenerPolling();
        this.conversacionActual = null;

        $('.ci-sidebar').removeClass('oculto');
        $('#ci-chat-container').hide();
        $('#ci-placeholder').show();
        $('.ci-conversacion-item').removeClass('active');
    };

    /**
     * Toggle archivados
     */
    FlavorChatInterno.toggleArchivados = function() {
        const btn = $('#ci-toggle-archivados');
        const mostrandoArchivados = btn.hasClass('activo');

        btn.toggleClass('activo');
        this.loadConversaciones(!mostrandoArchivados);
    };

    /**
     * Filtrar conversaciones
     */
    FlavorChatInterno.filtrarConversaciones = function(query) {
        const items = $('.ci-conversacion-item');

        if (!query) {
            items.show();
            return;
        }

        query = query.toLowerCase();
        items.each(function() {
            const nombre = $(this).find('.ci-conversacion-nombre').text().toLowerCase();
            const preview = $(this).find('.ci-conversacion-preview').text().toLowerCase();

            if (nombre.includes(query) || preview.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    };

    // =========================================================================
    // UTILIDADES
    // =========================================================================

    /**
     * Scroll al fondo
     */
    FlavorChatInterno.scrollToBottom = function() {
        const container = $('.ci-mensajes-container');
        container.scrollTop(container[0].scrollHeight);
    };

    /**
     * Scroll al fondo si esta cerca
     */
    FlavorChatInterno.scrollToBottomIfNear = function() {
        const container = $('.ci-mensajes-container');
        const scrollTop = container.scrollTop();
        const scrollHeight = container[0].scrollHeight;
        const clientHeight = container[0].clientHeight;

        // Si esta a menos de 100px del fondo
        if (scrollHeight - scrollTop - clientHeight < 100) {
            this.scrollToBottom();
        }
    };

    /**
     * Auto resize textarea
     */
    FlavorChatInterno.autoResizeTextarea = function(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    };

    /**
     * Formatear fecha
     */
    FlavorChatInterno.formatearFecha = function(fecha) {
        const hoy = new Date();
        const fechaObj = new Date(fecha);

        const esHoy = fechaObj.toDateString() === hoy.toDateString();

        const ayer = new Date(hoy);
        ayer.setDate(ayer.getDate() - 1);
        const esAyer = fechaObj.toDateString() === ayer.toDateString();

        if (esHoy) {
            return 'Hoy';
        } else if (esAyer) {
            return this.strings.ayer || 'Ayer';
        } else {
            return fechaObj.toLocaleDateString('es-ES', {
                day: 'numeric',
                month: 'long',
                year: fechaObj.getFullYear() !== hoy.getFullYear() ? 'numeric' : undefined,
            });
        }
    };

    /**
     * Formatear tamano de archivo
     */
    FlavorChatInterno.formatearTamano = function(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    /**
     * Escape HTML
     */
    FlavorChatInterno.escapeHtml = function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    /**
     * Mostrar error
     */
    FlavorChatInterno.mostrarError = function(mensaje) {
        // Puedes reemplazar esto con un sistema de notificaciones mas elegante
        alert(mensaje);
    };

    /**
     * Actualizar estado del usuario
     */
    FlavorChatInterno.actualizarEstado = function() {
        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_interno_estado',
                nonce: this.nonce,
            }
        });
    };

    /**
     * Mostrar notificacion del navegador
     */
    FlavorChatInterno.mostrarNotificacion = function(mensaje) {
        // Verificar si las notificaciones estan permitidas
        if (!("Notification" in window) || Notification.permission !== "granted") {
            return;
        }

        // No mostrar si la ventana esta activa
        if (document.hasFocus()) {
            return;
        }

        const titulo = this.strings.nuevo_mensaje || 'Nuevo mensaje';
        const opciones = {
            body: mensaje.remitente_nombre + ': ' + mensaje.mensaje.substring(0, 100),
            icon: mensaje.remitente_avatar,
            tag: 'chat-interno-' + mensaje.id,
        };

        const notificacion = new Notification(titulo, opciones);

        notificacion.onclick = function() {
            window.focus();
            notificacion.close();
        };

        // Cerrar automaticamente
        setTimeout(() => notificacion.close(), 5000);
    };

    /**
     * Solicitar permiso de notificaciones
     */
    FlavorChatInterno.requestNotificationPermission = function() {
        if ("Notification" in window && Notification.permission === "default") {
            Notification.requestPermission();
        }
    };

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Debounce
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Hacer disponible globalmente
    window.FlavorChatInterno = FlavorChatInterno;

})(jQuery);
