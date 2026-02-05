/**
 * Chat Grupos Frontend JavaScript
 * Flavor Chat IA - Sistema de Chat de Grupos
 */

(function($) {
    'use strict';

    const FlavorChatGrupos = {
        ajaxurl: flavorChatGrupos?.ajaxurl || '/wp-admin/admin-ajax.php',
        resturl: flavorChatGrupos?.resturl || '/wp-json/flavor/v1/chat-grupos/',
        nonce: flavorChatGrupos?.nonce || '',
        userId: flavorChatGrupos?.user_id || 0,
        userName: flavorChatGrupos?.user_name || '',
        userAvatar: flavorChatGrupos?.user_avatar || '',
        strings: flavorChatGrupos?.strings || {},
        pollingInterval: flavorChatGrupos?.polling_interval || 3000,
        typingTimeout: flavorChatGrupos?.typing_timeout || 3000,
        grupoActual: null,
        pollingTimer: null,
        typingTimer: null,
        lastMessageId: 0,
    };

    $(document).ready(function() {
        if ($('#flavor-chat-grupos-app').length) {
            FlavorChatGrupos.init();
        }
        if ($('#cg-explorar').length) {
            FlavorChatGrupos.initExplorar();
        }
        if ($('#cg-form-crear').length) {
            FlavorChatGrupos.initCrearForm();
        }
    });

    /**
     * Inicialización principal
     */
    FlavorChatGrupos.init = function() {
        this.bindEvents();
        this.loadMisGrupos();
    };

    /**
     * Bind de eventos
     */
    FlavorChatGrupos.bindEvents = function() {
        const self = this;

        // Seleccionar grupo
        $(document).on('click', '.cg-grupo-item', function() {
            const grupoId = $(this).data('id');
            self.abrirGrupo(grupoId);
        });

        // Enviar mensaje
        $(document).on('click', '.cg-btn-enviar', function() {
            self.enviarMensaje();
        });

        $('#cg-mensaje-input').on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                self.enviarMensaje();
            }
        });

        // Typing indicator
        $('#cg-mensaje-input').on('input', function() {
            self.sendTyping();
        });

        // Auto-resize textarea
        $('#cg-mensaje-input').on('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // Crear grupo
        $(document).on('click', '.cg-btn-crear', function() {
            self.mostrarModalCrear();
        });

        // Unirse a grupo
        $(document).on('click', '.cg-btn-unirse', function() {
            const grupoId = $(this).data('id');
            self.unirseGrupo(grupoId, $(this));
        });

        // Reaccionar a mensaje
        $(document).on('click', '.cg-reaccion', function() {
            const mensajeId = $(this).closest('.cg-mensaje').data('id');
            const emoji = $(this).data('emoji');
            self.toggleReaccion(mensajeId, emoji);
        });

        // Añadir reacción
        $(document).on('click', '.cg-btn-reaccionar', function() {
            const mensajeId = $(this).closest('.cg-mensaje').data('id');
            self.mostrarEmojiPicker(mensajeId, $(this));
        });

        // Responder mensaje
        $(document).on('click', '.cg-btn-responder', function() {
            const mensaje = $(this).closest('.cg-mensaje');
            self.prepararRespuesta(mensaje.data('id'), mensaje.find('.cg-mensaje-texto').text());
        });

        // Eliminar mensaje
        $(document).on('click', '.cg-btn-eliminar-msg', function() {
            const mensajeId = $(this).closest('.cg-mensaje').data('id');
            if (confirm('¿Eliminar este mensaje?')) {
                self.eliminarMensaje(mensajeId);
            }
        });

        // Scroll para cargar más mensajes
        $('.cg-mensajes-container').on('scroll', function() {
            if (this.scrollTop === 0 && self.grupoActual) {
                self.cargarMasMensajes();
            }
        });

        // Buscar en grupo
        $(document).on('click', '.cg-btn-buscar', function() {
            self.mostrarBusqueda();
        });

        // Info del grupo
        $(document).on('click', '.cg-btn-info', function() {
            self.mostrarPanelInfo();
        });

        // Adjuntar archivo
        $(document).on('click', '.cg-btn-adjuntar', function() {
            self.abrirSelectorArchivo();
        });
    };

    /**
     * Cargar mis grupos
     */
    FlavorChatGrupos.loadMisGrupos = function() {
        const self = this;
        const container = $('#cg-mis-grupos');

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_chat_grupos_messages',
                grupo_id: 0, // Special case to get groups list
            },
            success: function(response) {
                // Use REST instead
                self.fetchMisGrupos();
            }
        });
    };

    FlavorChatGrupos.fetchMisGrupos = function() {
        const self = this;
        const container = $('#cg-mis-grupos');

        $.ajax({
            url: this.resturl,
            type: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', self.nonce);
            },
            success: function(response) {
                if (response.success && response.grupos) {
                    self.renderGruposLista(container, response.grupos);
                } else {
                    container.html('<div class="cg-empty"><p>No perteneces a ningún grupo aún</p></div>');
                }
            },
            error: function() {
                container.html('<div class="cg-empty"><p>Error al cargar grupos</p></div>');
            }
        });
    };

    /**
     * Renderizar lista de grupos
     */
    FlavorChatGrupos.renderGruposLista = function(container, grupos) {
        if (!grupos.length) {
            container.html('<div class="cg-empty"><p>No perteneces a ningún grupo aún</p></div>');
            return;
        }

        let html = '';
        grupos.forEach(function(grupo) {
            const inicial = grupo.nombre.charAt(0).toUpperCase();
            const badgeHtml = grupo.mensajes_no_leidos > 0
                ? `<span class="cg-grupo-item-badge">${grupo.mensajes_no_leidos > 99 ? '99+' : grupo.mensajes_no_leidos}</span>`
                : '';

            html += `
                <div class="cg-grupo-item" data-id="${grupo.id}">
                    <div class="cg-grupo-item-avatar" style="background-color: ${grupo.color || '#2271b1'}">
                        ${grupo.imagen_url ? `<img src="${grupo.imagen_url}" alt="">` : inicial}
                    </div>
                    <div class="cg-grupo-item-info">
                        <div class="cg-grupo-item-nombre">${grupo.nombre}</div>
                        <div class="cg-grupo-item-preview">
                            ${grupo.ultimo_mensaje ? grupo.ultimo_mensaje.texto : grupo.descripcion || ''}
                        </div>
                    </div>
                    <div class="cg-grupo-item-meta">
                        <span class="cg-grupo-item-fecha">${grupo.ultimo_mensaje ? grupo.ultimo_mensaje.fecha : ''}</span>
                        ${badgeHtml}
                    </div>
                </div>
            `;
        });

        container.html(html);
    };

    /**
     * Abrir grupo
     */
    FlavorChatGrupos.abrirGrupo = function(grupoId) {
        const self = this;

        // Marcar como activo
        $('.cg-grupo-item').removeClass('active');
        $(`.cg-grupo-item[data-id="${grupoId}"]`).addClass('active');

        // Mostrar contenedor de chat
        $('.cg-no-grupo-seleccionado').hide();
        $('.cg-chat-container').show();

        // Limpiar
        this.grupoActual = grupoId;
        this.lastMessageId = 0;
        $('#cg-mensajes').html('<div class="cg-loading">Cargando mensajes...</div>');

        // Cargar info del grupo
        this.cargarInfoGrupo(grupoId);

        // Cargar mensajes
        this.cargarMensajes(grupoId);

        // Iniciar polling
        this.iniciarPolling();
    };

    /**
     * Cargar info del grupo
     */
    FlavorChatGrupos.cargarInfoGrupo = function(grupoId) {
        const self = this;

        $.ajax({
            url: this.resturl + grupoId,
            type: 'GET',
            success: function(response) {
                if (response.success && response.grupo) {
                    const grupo = response.grupo;
                    $('.cg-grupo-avatar').css('background-color', grupo.color);
                    $('.cg-grupo-nombre').text(grupo.nombre);
                    $('.cg-grupo-miembros').text(grupo.miembros_count + ' miembros');
                }
            }
        });
    };

    /**
     * Cargar mensajes
     */
    FlavorChatGrupos.cargarMensajes = function(grupoId, antesDeId) {
        const self = this;
        const container = $('#cg-mensajes');

        const params = {
            action: 'flavor_chat_grupos_messages',
            grupo_id: grupoId,
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
                    if (antesDeId) {
                        // Prepend older messages
                        const oldScrollHeight = container[0].scrollHeight;
                        self.prependMensajes(container, response.mensajes);
                        container[0].scrollTop = container[0].scrollHeight - oldScrollHeight;
                    } else {
                        self.renderMensajes(container, response.mensajes);
                        self.scrollToBottom();
                    }

                    if (response.mensajes.length > 0) {
                        const ultimoId = response.mensajes[response.mensajes.length - 1].id;
                        if (ultimoId > self.lastMessageId) {
                            self.lastMessageId = ultimoId;
                        }
                    }
                } else {
                    container.html('<div class="cg-empty"><p>' + (self.strings.sin_mensajes || 'No hay mensajes') + '</p></div>');
                }
            },
            error: function() {
                container.html('<div class="cg-empty"><p>' + (self.strings.error || 'Error al cargar') + '</p></div>');
            }
        });
    };

    /**
     * Renderizar mensajes
     */
    FlavorChatGrupos.renderMensajes = function(container, mensajes) {
        if (!mensajes.length) {
            container.html('<div class="cg-empty"><span class="dashicons dashicons-format-chat"></span><p>' + (this.strings.sin_mensajes || 'No hay mensajes aún') + '</p></div>');
            return;
        }

        let html = '';
        let fechaAnterior = '';

        mensajes.forEach(function(msg) {
            const fecha = msg.fecha.split(' ')[0];
            if (fecha !== fechaAnterior) {
                html += `<div class="cg-fecha-separador"><span>${fecha}</span></div>`;
                fechaAnterior = fecha;
            }
            html += FlavorChatGrupos.renderMensaje(msg);
        });

        container.html(html);
    };

    /**
     * Renderizar un mensaje
     */
    FlavorChatGrupos.renderMensaje = function(msg) {
        const esMio = msg.es_mio;
        const clasesMensaje = ['cg-mensaje'];
        if (esMio) clasesMensaje.push('mio');
        if (msg.tipo === 'sistema') clasesMensaje.push('cg-mensaje-sistema');

        if (msg.tipo === 'sistema') {
            return `
                <div class="${clasesMensaje.join(' ')}" data-id="${msg.id}">
                    <div class="cg-mensaje-contenido">
                        <span class="cg-mensaje-texto">${msg.mensaje}</span>
                    </div>
                </div>
            `;
        }

        let reaccionesHtml = '';
        if (msg.reacciones && msg.reacciones.length) {
            reaccionesHtml = '<div class="cg-mensaje-reacciones">';
            msg.reacciones.forEach(function(r) {
                const activa = r.yo_reaccione ? 'activa' : '';
                reaccionesHtml += `<span class="cg-reaccion ${activa}" data-emoji="${r.emoji}"><span class="cg-reaccion-emoji">${r.emoji}</span><span class="cg-reaccion-count">${r.count}</span></span>`;
            });
            reaccionesHtml += '</div>';
        }

        let adjuntosHtml = '';
        if (msg.adjuntos && msg.adjuntos.length) {
            adjuntosHtml = '<div class="cg-adjuntos">';
            msg.adjuntos.forEach(function(adj) {
                if (adj.es_imagen) {
                    adjuntosHtml += `<img src="${adj.url}" class="cg-adjunto-imagen" alt="">`;
                } else {
                    adjuntosHtml += `<a href="${adj.url}" class="cg-adjunto" target="_blank"><span class="cg-adjunto-icon"><span class="dashicons dashicons-media-default"></span></span>${adj.nombre}</a>`;
                }
            });
            adjuntosHtml += '</div>';
        }

        let respondeAHtml = '';
        if (msg.responde_a) {
            respondeAHtml = `<div class="cg-mensaje-responde-a">En respuesta a mensaje #${msg.responde_a}</div>`;
        }

        const contenidoTexto = msg.eliminado
            ? `<span class="cg-mensaje-eliminado">${this.strings.mensaje_eliminado || 'Mensaje eliminado'}</span>`
            : msg.mensaje_html || msg.mensaje;

        const editadoHtml = msg.editado ? `<span class="cg-mensaje-editado">(${this.strings.mensaje_editado || 'editado'})</span>` : '';

        return `
            <div class="${clasesMensaje.join(' ')}" data-id="${msg.id}">
                <img src="${msg.autor_avatar}" class="cg-mensaje-avatar" alt="">
                <div class="cg-mensaje-contenido">
                    <div class="cg-mensaje-header">
                        <span class="cg-mensaje-autor">${msg.autor_nombre}</span>
                        <span class="cg-mensaje-fecha">${msg.fecha_humana}</span>
                        ${editadoHtml}
                    </div>
                    ${respondeAHtml}
                    <div class="cg-mensaje-texto">${contenidoTexto}</div>
                    ${adjuntosHtml}
                    ${reaccionesHtml}
                </div>
                <div class="cg-mensaje-acciones">
                    <button class="cg-btn-reaccionar" title="Reaccionar"><span class="dashicons dashicons-smiley"></span></button>
                    <button class="cg-btn-responder" title="Responder"><span class="dashicons dashicons-undo"></span></button>
                    ${esMio ? '<button class="cg-btn-eliminar-msg" title="Eliminar"><span class="dashicons dashicons-trash"></span></button>' : ''}
                </div>
            </div>
        `;
    };

    /**
     * Prepend mensajes antiguos
     */
    FlavorChatGrupos.prependMensajes = function(container, mensajes) {
        let html = '';
        mensajes.forEach(function(msg) {
            html += FlavorChatGrupos.renderMensaje(msg);
        });
        container.prepend(html);
    };

    /**
     * Enviar mensaje
     */
    FlavorChatGrupos.enviarMensaje = function() {
        const self = this;
        const input = $('#cg-mensaje-input');
        const mensaje = input.val().trim();

        if (!mensaje || !this.grupoActual) return;

        const btn = $('.cg-btn-enviar');
        btn.prop('disabled', true);

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_grupos_send',
                nonce: this.nonce,
                grupo_id: this.grupoActual,
                mensaje: mensaje,
                responde_a: this.respondeA || null,
            },
            success: function(response) {
                if (response.success) {
                    input.val('').css('height', 'auto');
                    self.respondeA = null;
                    $('.cg-respuesta-preview').remove();

                    // Add message to chat
                    const container = $('#cg-mensajes');
                    container.find('.cg-empty').remove();
                    container.append(self.renderMensaje(response.mensaje));
                    self.scrollToBottom();
                    self.lastMessageId = response.mensaje.id;

                    // Update sidebar
                    self.actualizarSidebarGrupo(self.grupoActual, mensaje);
                } else {
                    alert(response.error || 'Error al enviar');
                }
            },
            complete: function() {
                btn.prop('disabled', false);
                input.focus();
            }
        });
    };

    /**
     * Preparar respuesta
     */
    FlavorChatGrupos.prepararRespuesta = function(mensajeId, texto) {
        this.respondeA = mensajeId;

        $('.cg-respuesta-preview').remove();
        const preview = $(`<div class="cg-respuesta-preview">Respondiendo a: "${texto.substring(0, 50)}..." <button class="cg-btn-cancelar-respuesta">×</button></div>`);
        $('.cg-input-container').prepend(preview);

        preview.find('.cg-btn-cancelar-respuesta').on('click', function() {
            FlavorChatGrupos.respondeA = null;
            preview.remove();
        });

        $('#cg-mensaje-input').focus();
    };

    /**
     * Toggle reacción
     */
    FlavorChatGrupos.toggleReaccion = function(mensajeId, emoji) {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_grupos_react',
                nonce: this.nonce,
                mensaje_id: mensajeId,
                emoji: emoji,
            },
            success: function(response) {
                if (response.success) {
                    // Reload message reactions
                    self.cargarMensajes(self.grupoActual);
                }
            }
        });
    };

    /**
     * Eliminar mensaje
     */
    FlavorChatGrupos.eliminarMensaje = function(mensajeId) {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_grupos_delete_msg',
                nonce: this.nonce,
                mensaje_id: mensajeId,
            },
            success: function(response) {
                if (response.success) {
                    const msg = $(`.cg-mensaje[data-id="${mensajeId}"]`);
                    msg.find('.cg-mensaje-texto').html('<span class="cg-mensaje-eliminado">' + (self.strings.mensaje_eliminado || 'Mensaje eliminado') + '</span>');
                    msg.find('.cg-mensaje-acciones').remove();
                }
            }
        });
    };

    /**
     * Enviar indicador de escritura
     */
    FlavorChatGrupos.sendTyping = function() {
        const self = this;

        if (this.typingTimer) {
            clearTimeout(this.typingTimer);
        }

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_grupos_typing',
                nonce: this.nonce,
                grupo_id: this.grupoActual,
            },
            success: function(response) {
                if (response.success && response.data.escribiendo.length) {
                    const nombres = response.data.escribiendo.map(e => e.nombre).join(', ');
                    $('.cg-escribiendo').text(nombres + ' ' + (self.strings.escribiendo || 'escribiendo...')).show();
                } else {
                    $('.cg-escribiendo').hide();
                }
            }
        });

        this.typingTimer = setTimeout(function() {
            $('.cg-escribiendo').hide();
        }, this.typingTimeout);
    };

    /**
     * Iniciar polling de mensajes nuevos
     */
    FlavorChatGrupos.iniciarPolling = function() {
        const self = this;

        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
        }

        this.pollingTimer = setInterval(function() {
            self.checkNuevosMensajes();
        }, this.pollingInterval);
    };

    /**
     * Verificar mensajes nuevos
     */
    FlavorChatGrupos.checkNuevosMensajes = function() {
        const self = this;

        if (!this.grupoActual) return;

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_chat_grupos_messages',
                grupo_id: this.grupoActual,
                desde: this.lastMessageId,
                limite: 20,
            },
            success: function(response) {
                if (response.success && response.mensajes && response.mensajes.length) {
                    const container = $('#cg-mensajes');
                    const wasAtBottom = self.isScrolledToBottom();

                    response.mensajes.forEach(function(msg) {
                        if (msg.id > self.lastMessageId) {
                            container.append(self.renderMensaje(msg));
                            self.lastMessageId = msg.id;
                        }
                    });

                    if (wasAtBottom) {
                        self.scrollToBottom();
                    }
                }
            }
        });
    };

    /**
     * Cargar más mensajes (scroll arriba)
     */
    FlavorChatGrupos.cargarMasMensajes = function() {
        if (!this.grupoActual) return;

        const primerMensaje = $('.cg-mensaje').first();
        if (!primerMensaje.length) return;

        const primerId = primerMensaje.data('id');
        this.cargarMensajes(this.grupoActual, primerId);
    };

    /**
     * Unirse a grupo
     */
    FlavorChatGrupos.unirseGrupo = function(grupoId, btn) {
        const self = this;
        const textoOriginal = btn.text();
        btn.prop('disabled', true).text('...');

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_grupos_join',
                nonce: this.nonce,
                grupo_id: grupoId,
            },
            success: function(response) {
                if (response.success) {
                    btn.removeClass('cg-btn-outline cg-btn-unirse')
                       .addClass('cg-btn-primary')
                       .text('Abrir');
                    btn.off('click').on('click', function() {
                        self.abrirGrupo(grupoId);
                    });
                    self.fetchMisGrupos();
                } else {
                    alert(response.error || 'Error al unirse');
                    btn.prop('disabled', false).text(textoOriginal);
                }
            },
            error: function() {
                btn.prop('disabled', false).text(textoOriginal);
            }
        });
    };

    /**
     * Actualizar sidebar después de enviar mensaje
     */
    FlavorChatGrupos.actualizarSidebarGrupo = function(grupoId, mensaje) {
        const item = $(`.cg-grupo-item[data-id="${grupoId}"]`);
        item.find('.cg-grupo-item-preview').text((this.strings.tu || 'Tú') + ': ' + mensaje.substring(0, 30));
        item.find('.cg-grupo-item-fecha').text(this.strings.ahora || 'ahora');

        // Mover al principio
        item.prependTo('#cg-mis-grupos');
    };

    /**
     * Scroll helpers
     */
    FlavorChatGrupos.scrollToBottom = function() {
        const container = document.querySelector('.cg-mensajes-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    };

    FlavorChatGrupos.isScrolledToBottom = function() {
        const container = document.querySelector('.cg-mensajes-container');
        if (!container) return true;
        return container.scrollHeight - container.scrollTop - container.clientHeight < 100;
    };

    /**
     * Mostrar panel de info del grupo
     */
    FlavorChatGrupos.mostrarPanelInfo = function() {
        const panel = $('.cg-panel-info');
        if (panel.is(':visible')) {
            panel.hide();
        } else {
            panel.show();
            this.cargarInfoCompleta();
        }
    };

    FlavorChatGrupos.cargarInfoCompleta = function() {
        // Cargar info completa del grupo y miembros
        if (!this.grupoActual) return;

        $.ajax({
            url: this.resturl + this.grupoActual,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    // Render panel info
                    FlavorChatGrupos.renderPanelInfo(response);
                }
            }
        });
    };

    FlavorChatGrupos.renderPanelInfo = function(data) {
        const grupo = data.grupo;
        const admins = data.admins;

        let html = `
            <div class="cg-panel-header">
                <h4>Info del grupo</h4>
                <button class="cg-btn-cerrar-panel"><span class="dashicons dashicons-no-alt"></span></button>
            </div>
            <div class="cg-panel-body">
                <div class="cg-panel-section">
                    <h5>Descripción</h5>
                    <p>${grupo.descripcion || 'Sin descripción'}</p>
                </div>
                <div class="cg-panel-section">
                    <h5>${grupo.miembros_count} miembros</h5>
                    <div class="cg-panel-admins">
        `;

        admins.forEach(function(admin) {
            html += `<div class="cg-admin-item"><img src="${admin.avatar}" alt=""><span>${admin.nombre}</span><small>${admin.rol}</small></div>`;
        });

        html += `
                    </div>
                </div>
            </div>
        `;

        $('.cg-panel-info').html(html);

        $('.cg-btn-cerrar-panel').on('click', function() {
            $('.cg-panel-info').hide();
        });
    };

    /**
     * Abrir selector de archivos
     */
    FlavorChatGrupos.abrirSelectorArchivo = function() {
        const self = this;
        const input = $('<input type="file" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">');

        input.on('change', function() {
            if (this.files.length) {
                self.subirArchivo(this.files[0]);
            }
        });

        input.click();
    };

    FlavorChatGrupos.subirArchivo = function(file) {
        const self = this;
        const formData = new FormData();
        formData.append('action', 'flavor_chat_grupos_upload');
        formData.append('nonce', this.nonce);
        formData.append('grupo_id', this.grupoActual);
        formData.append('archivo', file);

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Enviar mensaje con adjunto
                    self.enviarMensajeConAdjunto(response.data);
                } else {
                    alert(response.data || 'Error al subir archivo');
                }
            }
        });
    };

    FlavorChatGrupos.enviarMensajeConAdjunto = function(adjunto) {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_grupos_send',
                nonce: this.nonce,
                grupo_id: this.grupoActual,
                mensaje: adjunto.nombre,
                adjuntos: JSON.stringify([adjunto]),
            },
            success: function(response) {
                if (response.success) {
                    $('#cg-mensajes').append(self.renderMensaje(response.mensaje));
                    self.scrollToBottom();
                    self.lastMessageId = response.mensaje.id;
                }
            }
        });
    };

    /**
     * Explorar grupos
     */
    FlavorChatGrupos.initExplorar = function() {
        const self = this;

        this.cargarGruposExplorar();

        $('#cg-buscar-grupos').on('input', debounce(function() {
            self.cargarGruposExplorar();
        }, 300));

        $('#cg-filtro-categoria').on('change', function() {
            self.cargarGruposExplorar();
        });
    };

    FlavorChatGrupos.cargarGruposExplorar = function(pagina) {
        const self = this;
        const container = $('#cg-explorar-resultados');
        pagina = pagina || 1;

        const busqueda = $('#cg-buscar-grupos').val();
        const categoria = $('#cg-filtro-categoria').val();

        container.html('<div class="cg-loading">Cargando...</div>');

        $.ajax({
            url: this.resturl + 'explorar',
            type: 'GET',
            data: {
                busqueda: busqueda,
                categoria: categoria,
                pagina: pagina,
            },
            success: function(response) {
                if (response.success && response.grupos.length) {
                    self.renderGruposExplorar(container, response.grupos);
                    self.renderPaginacion(response.paginas, response.pagina_actual);
                } else {
                    container.html('<div class="cg-empty"><p>No se encontraron grupos</p></div>');
                    $('#cg-paginacion').empty();
                }
            }
        });
    };

    FlavorChatGrupos.renderGruposExplorar = function(container, grupos) {
        let html = '';

        grupos.forEach(function(grupo) {
            html += `
                <div class="cg-grupo-card">
                    <div class="cg-grupo-card-header" style="background-color: ${grupo.color || '#2271b1'}">
                        ${grupo.imagen_url ? `<img src="${grupo.imagen_url}" alt="">` : ''}
                    </div>
                    <div class="cg-grupo-card-body">
                        <h4>${grupo.nombre}</h4>
                        <p>${grupo.descripcion || ''}</p>
                        <div class="cg-grupo-card-meta">
                            <span><span class="dashicons dashicons-groups"></span> ${grupo.miembros}</span>
                            <span><span class="dashicons dashicons-admin-comments"></span> ${grupo.mensajes}</span>
                        </div>
                    </div>
                    <div class="cg-grupo-card-footer">
                        ${grupo.es_miembro
                            ? `<button class="cg-btn cg-btn-primary cg-btn-block" onclick="FlavorChatGrupos.abrirGrupo(${grupo.id})">Abrir</button>`
                            : `<button class="cg-btn cg-btn-outline cg-btn-block cg-btn-unirse" data-id="${grupo.id}">Unirse</button>`
                        }
                    </div>
                </div>
            `;
        });

        container.html(html);
    };

    FlavorChatGrupos.renderPaginacion = function(totalPaginas, paginaActual) {
        if (totalPaginas <= 1) {
            $('#cg-paginacion').empty();
            return;
        }

        let html = '';
        for (let i = 1; i <= totalPaginas; i++) {
            const activa = i === paginaActual ? 'active' : '';
            html += `<button class="cg-paginacion-btn ${activa}" data-pagina="${i}">${i}</button>`;
        }

        $('#cg-paginacion').html(html);

        $('#cg-paginacion').off('click').on('click', '.cg-paginacion-btn', function() {
            const pagina = $(this).data('pagina');
            FlavorChatGrupos.cargarGruposExplorar(pagina);
        });
    };

    /**
     * Formulario de crear grupo
     */
    FlavorChatGrupos.initCrearForm = function() {
        const self = this;

        $('#cg-form-crear').on('submit', function(e) {
            e.preventDefault();
            self.crearGrupo($(this));
        });
    };

    FlavorChatGrupos.crearGrupo = function(form) {
        const self = this;
        const btn = form.find('button[type="submit"]');
        const textoOriginal = btn.text();

        btn.prop('disabled', true).text('Creando...');

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_chat_grupos_create',
                nonce: this.nonce,
                nombre: form.find('#cg-nombre').val(),
                descripcion: form.find('#cg-descripcion').val(),
                tipo: form.find('#cg-tipo').val(),
                categoria: form.find('#cg-categoria').val(),
                color: form.find('#cg-color').val(),
            },
            success: function(response) {
                if (response.success) {
                    alert('Grupo creado correctamente');
                    form[0].reset();
                    if (response.grupo_id) {
                        window.location.href = '?grupo=' + response.slug;
                    }
                } else {
                    alert(response.error || 'Error al crear el grupo');
                }
            },
            complete: function() {
                btn.prop('disabled', false).text(textoOriginal);
            }
        });
    };

    /**
     * Utilidad: debounce
     */
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Exponer globalmente
    window.FlavorChatGrupos = FlavorChatGrupos;

})(jQuery);
