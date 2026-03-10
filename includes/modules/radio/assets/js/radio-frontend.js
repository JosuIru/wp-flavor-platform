/**
 * Radio Frontend JavaScript
 * Flavor Chat IA - Radio Comunitaria
 * Version 2.0 - Con mejoras avanzadas
 */

(function($) {
    'use strict';

    const radioConfig = typeof flavorRadioConfig !== 'undefined'
        ? flavorRadioConfig
        : (typeof flavorRadio !== 'undefined' ? flavorRadio : {});

    const FlavorRadio = {
        ajaxurl: radioConfig.ajaxurl || radioConfig.ajaxUrl || '/wp-admin/admin-ajax.php',
        resturl: radioConfig.resturl || radioConfig.restUrl || '/wp-json/flavor/v1/radio/',
        nonce: radioConfig.nonce || '',
        user_id: radioConfig.user_id || radioConfig.userId || 0,
        strings: radioConfig.strings || radioConfig.i18n || {},
        audio: null,
        audioContext: null,
        analyser: null,
        sessionId: null,
        heartbeatInterval: null,
        chatInterval: null,
        metadataInterval: null,
        lastChatId: 0,
        currentEmisionId: 0,
        currentChannel: 0,
        favoritos: [],
        canales: radioConfig.canales || [],
        emojisRapidos: ['👍', '❤️', '🔥', '😂', '👏', '🎵', '🙌', '💜'],
    };

    if (radioConfig.streamUrl) {
        FlavorRadio.streamUrl = radioConfig.streamUrl;
    }
    if (radioConfig.streamHdUrl) {
        FlavorRadio.streamHdUrl = radioConfig.streamHdUrl;
    }
    if (radioConfig.nombreRadio) {
        FlavorRadio.nombreRadio = radioConfig.nombreRadio;
    }
    if (typeof radioConfig.chatHabilitado !== 'undefined') {
        FlavorRadio.chatHabilitado = radioConfig.chatHabilitado;
    }

    $(document).ready(function() {
        FlavorRadio.init();
    });

    FlavorRadio.init = function() {
        this.sessionId = localStorage.getItem('radio_session_id') || this.generateSessionId();
        localStorage.setItem('radio_session_id', this.sessionId);

        // Cargar favoritos del usuario
        this.loadFavoritos();

        this.initPlayer();
        this.initVisualizer();
        this.initProgramacion();
        this.initDedicatorias();
        this.initChat();
        this.initChatReacciones();
        this.initProponer();
        this.initPodcasts();
        this.initFavoritos();
        this.initShare();
        this.initCanales();
        this.initCalendario();
        this.initMetadataPolling();
    };

    FlavorRadio.generateSessionId = function() {
        return 'rs_' + Math.random().toString(36).substr(2, 9) + Date.now().toString(36);
    };

    // =========================================================================
    // Player
    // =========================================================================

    FlavorRadio.initPlayer = function() {
        const self = this;
        const player = $('.flavor-radio-player');

        if (!player.length) return;

        const streamUrl = player.data('stream');
        const streamUrlHD = player.data('stream-hd');
        const useLocalPlaylist = player.data('use-local') === 'true' || player.data('use-local') === true;
        const autoplay = player.data('autoplay') === 'true';

        // Inicializar modo de reproducción
        this.playMode = streamUrl ? 'stream' : (useLocalPlaylist ? 'local' : null);
        this.localPlaylist = [];
        this.currentTrackIndex = 0;

        if (!streamUrl && !useLocalPlaylist) return;

        // Si es playlist local, cargar la playlist inicial
        if (this.playMode === 'local') {
            this.loadLocalPlaylist();
        }

        // Crear elemento de audio
        this.audio = new Audio();
        this.audio.preload = 'none';

        // Controles
        player.find('.radio-btn-play').on('click', function() {
            self.togglePlay(player);
        });

        player.find('.radio-btn-mute').on('click', function() {
            self.toggleMute($(this));
        });

        player.find('.radio-volume-slider').on('input', function() {
            self.setVolume($(this).val());
        });

        player.find('.radio-btn-hd').on('click', function() {
            self.toggleHD(player, streamUrl, streamUrlHD);
        });

        // Eventos de audio
        this.audio.addEventListener('playing', function() {
            player.find('.radio-btn-play').addClass('playing');
            player.find('.radio-visualizer').addClass('playing');
            self.startHeartbeat();
        });

        this.audio.addEventListener('pause', function() {
            player.find('.radio-btn-play').removeClass('playing');
            player.find('.radio-visualizer').removeClass('playing');
            self.stopHeartbeat();
        });

        this.audio.addEventListener('error', function() {
            self.showToast(self.strings.error || 'Error de conexión', 'error');
        });

        // Inicializar modo local si aplica
        if (this.playMode === 'local') {
            this.initLocalMode();
        }

        // Cargar programa actual
        this.loadProgramaActual(player);

        // Actualizar cada 30 segundos
        setInterval(function() {
            self.loadProgramaActual(player);
        }, 30000);

        // Autoplay
        if (autoplay) {
            setTimeout(function() {
                self.togglePlay(player);
            }, 1000);
        }
    };

    FlavorRadio.togglePlay = function(player) {
        const self = this;
        const streamUrl = player.data('stream');
        const isHD = player.find('.radio-btn-hd').hasClass('active');
        const streamUrlHD = player.data('stream-hd');

        if (this.audio.paused) {
            // Modo streaming externo
            if (this.playMode === 'stream') {
                this.audio.src = isHD && streamUrlHD ? streamUrlHD : streamUrl;
                this.audio.load();
                this.audio.play().catch(function(e) {
                    console.log('Error al reproducir:', e);
                });
            }
            // Modo playlist local
            else if (this.playMode === 'local') {
                if (this.currentTrack && this.currentTrack.url) {
                    this.audio.src = this.currentTrack.url;
                    this.audio.load();
                    this.audio.play().catch(function(e) {
                        console.log('Error al reproducir:', e);
                    });
                } else {
                    // Cargar track actual antes de reproducir
                    this.loadLocalPlaylist();
                    setTimeout(function() {
                        if (self.currentTrack && self.currentTrack.url) {
                            self.audio.src = self.currentTrack.url;
                            self.audio.load();
                            self.audio.play().catch(function(e) {
                                console.log('Error al reproducir:', e);
                            });
                        }
                    }, 500);
                }
            }
        } else {
            this.audio.pause();
            // Solo limpiar src en modo stream para detener buffer
            if (this.playMode === 'stream') {
                this.audio.src = '';
            }
        }
    };

    FlavorRadio.toggleMute = function(btn) {
        this.audio.muted = !this.audio.muted;
        btn.toggleClass('muted', this.audio.muted);
    };

    FlavorRadio.setVolume = function(value) {
        this.audio.volume = value / 100;
    };

    FlavorRadio.toggleHD = function(player, streamUrl, streamUrlHD) {
        if (!streamUrlHD) return;

        const btn = player.find('.radio-btn-hd');
        const wasPlaying = !this.audio.paused;

        btn.toggleClass('active');

        if (wasPlaying) {
            const newUrl = btn.hasClass('active') ? streamUrlHD : streamUrl;
            this.audio.src = newUrl;
            this.audio.load();
            this.audio.play();
        }
    };

    // =========================================================================
    // Playlist Local (cuando no hay servidor de streaming)
    // =========================================================================

    FlavorRadio.loadLocalPlaylist = function() {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: { action: 'flavor_radio_get_current_track' },
            success: function(response) {
                if (response.success && response.data) {
                    self.currentTrack = response.data;
                    self.updateNowPlaying(response.data);

                    // Si hay URL del track actual, configurarlo
                    if (response.data.url) {
                        self.audio.src = response.data.url;

                        // Si hay posición inicial, saltar a ella
                        if (response.data.posicion > 0) {
                            self.audio.currentTime = response.data.posicion;
                        }
                    }
                }
            }
        });
    };

    FlavorRadio.playNextLocalTrack = function() {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: { action: 'flavor_radio_get_current_track' },
            success: function(response) {
                if (response.success && response.data && response.data.url) {
                    self.currentTrack = response.data;
                    self.updateNowPlaying(response.data);

                    self.audio.src = response.data.url;
                    self.audio.load();
                    self.audio.play().catch(function(e) {
                        console.log('Error al reproducir siguiente track:', e);
                    });
                }
            }
        });
    };

    FlavorRadio.updateNowPlaying = function(track) {
        const player = $('.flavor-radio-player');

        if (track.titulo) {
            player.find('.radio-now-title, .radio-programa-nombre').text(track.titulo);
        }
        if (track.artista) {
            player.find('.radio-now-artist').text(track.artista);
        }
        if (track.cover) {
            player.find('.radio-now-cover img').attr('src', track.cover);
        }
        if (track.siguiente) {
            player.find('.radio-next-track').text('Siguiente: ' + track.siguiente.titulo);
        }

        // Actualizar barra de progreso si hay duración
        if (track.duracion) {
            player.find('.radio-track-duration').text(this.formatTime(track.duracion));
        }
    };

    FlavorRadio.formatTime = function(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return mins + ':' + (secs < 10 ? '0' : '') + secs;
    };

    FlavorRadio.initLocalMode = function() {
        const self = this;

        if (this.playMode !== 'local') return;

        // Cuando termina un track, cargar el siguiente
        this.audio.addEventListener('ended', function() {
            self.playNextLocalTrack();
        });

        // Actualizar tiempo actual
        this.audio.addEventListener('timeupdate', function() {
            const player = $('.flavor-radio-player');
            player.find('.radio-track-current').text(self.formatTime(self.audio.currentTime));

            // Actualizar barra de progreso
            if (self.currentTrack && self.currentTrack.duracion > 0) {
                const progress = (self.audio.currentTime / self.currentTrack.duracion) * 100;
                player.find('.radio-progress-bar').css('width', progress + '%');
            }
        });

        // Sincronizar cada minuto para mantenerse en sync con la programación
        setInterval(function() {
            if (self.audio.paused) return;

            $.ajax({
                url: self.ajaxurl,
                type: 'GET',
                data: { action: 'flavor_radio_get_current_track' },
                success: function(response) {
                    if (response.success && response.data) {
                        // Si el track cambió, actualizar
                        if (response.data.id !== self.currentTrack?.id) {
                            self.currentTrack = response.data;
                            self.updateNowPlaying(response.data);
                            self.audio.src = response.data.url;
                            self.audio.load();
                            self.audio.play();
                        }
                    }
                }
            });
        }, 60000);
    };

    FlavorRadio.loadProgramaActual = function(player) {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: { action: 'flavor_radio_programa_actual' },
            success: function(response) {
                if (response.success && response.data) {
                    const data = response.data;

                    if (data.en_vivo && data.emision) {
                        player.find('.radio-badge-vivo').show();
                        player.find('.radio-programa-nombre').text(data.emision.titulo);
                        self.currentEmisionId = data.emision.id;
                    } else {
                        player.find('.radio-badge-vivo').hide();
                        if (data.siguiente) {
                            player.find('.radio-programa-nombre').text(
                                'Próximo: ' + data.siguiente.titulo + ' en ' + data.siguiente.en
                            );
                        } else {
                            player.find('.radio-programa-nombre').text(self.strings.sin_emision || 'Sin emisión');
                        }
                        self.currentEmisionId = 0;
                    }

                    // Actualizar oyentes
                    player.find('.radio-oyentes-count').text(data.oyentes || 0);
                }
            }
        });
    };

    FlavorRadio.startHeartbeat = function() {
        const self = this;

        this.reportOyente();

        this.heartbeatInterval = setInterval(function() {
            self.reportOyente();
        }, 30000);
    };

    FlavorRadio.stopHeartbeat = function() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
    };

    FlavorRadio.reportOyente = function() {
        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_radio_reportar_oyente',
                session_id: this.sessionId,
                emision_id: this.currentEmisionId,
                dispositivo: this.detectDevice(),
            },
            success: function(response) {
                if (response.success && response.data.oyentes !== undefined) {
                    $('.radio-oyentes-count').text(response.data.oyentes);
                }
            }
        });
    };

    FlavorRadio.detectDevice = function() {
        const ua = navigator.userAgent.toLowerCase();
        if (/mobile|android|iphone|ipad/.test(ua)) return 'mobile';
        return 'desktop';
    };

    // =========================================================================
    // Programación
    // =========================================================================

    FlavorRadio.initProgramacion = function() {
        const self = this;
        const container = $('.flavor-radio-programacion');

        if (!container.length) return;

        const vista = container.data('vista') || 'semana';
        const dias = container.data('dias') || 7;
        let fechaActual = new Date();

        container.data('fecha', fechaActual.toISOString().split('T')[0]);

        this.loadProgramacion(container);

        // Navegación
        container.find('.programacion-nav-prev').on('click', function() {
            const fecha = new Date(container.data('fecha'));
            fecha.setDate(fecha.getDate() - dias);
            container.data('fecha', fecha.toISOString().split('T')[0]);
            self.loadProgramacion(container);
        });

        container.find('.programacion-nav-next').on('click', function() {
            const fecha = new Date(container.data('fecha'));
            fecha.setDate(fecha.getDate() + dias);
            container.data('fecha', fecha.toISOString().split('T')[0]);
            self.loadProgramacion(container);
        });
    };

    FlavorRadio.loadProgramacion = function(container) {
        const self = this;
        const grid = container.find('.programacion-grid');
        const fecha = container.data('fecha');
        const dias = container.data('dias') || 7;

        grid.html('<div class="radio-loading">' + (this.strings.loading || 'Cargando...') + '</div>');

        // Actualizar título
        const fechaObj = new Date(fecha);
        const fechaFin = new Date(fecha);
        fechaFin.setDate(fechaFin.getDate() + dias - 1);

        const opciones = { month: 'short', day: 'numeric' };
        container.find('.programacion-nav-titulo').text(
            fechaObj.toLocaleDateString('es', opciones) + ' - ' + fechaFin.toLocaleDateString('es', opciones)
        );

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_radio_programacion',
                fecha: fecha,
                dias: dias,
            },
            success: function(response) {
                if (response.success && response.data.programacion) {
                    self.renderProgramacion(grid, response.data.programacion);
                } else {
                    grid.html('<div class="radio-empty"><span class="dashicons dashicons-calendar-alt"></span><p>No hay programación para estas fechas</p></div>');
                }
            }
        });
    };

    FlavorRadio.renderProgramacion = function(container, programacion) {
        if (!programacion.length) {
            container.html('<div class="radio-empty"><span class="dashicons dashicons-calendar-alt"></span><p>No hay programación</p></div>');
            return;
        }

        let html = '';
        programacion.forEach(function(dia) {
            html += `<div class="programacion-dia">
                <div class="programacion-dia-header">${dia.dia_nombre}, ${dia.fecha}</div>`;

            dia.emisiones.forEach(function(em) {
                const enVivo = em.estado === 'en_emision' ? 'en-vivo' : '';
                html += `
                    <div class="programacion-emision ${enVivo}">
                        <div class="programacion-hora">${em.hora_inicio}</div>
                        ${em.imagen ? `<img src="${em.imagen}" class="programacion-imagen" alt="">` : ''}
                        <div class="programacion-info">
                            <div class="programacion-titulo">${em.titulo}</div>
                            <div class="programacion-meta">
                                ${em.locutor ? em.locutor + ' · ' : ''}${em.hora_inicio} - ${em.hora_fin}
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
        });

        container.html(html);
    };

    // =========================================================================
    // Dedicatorias
    // =========================================================================

    FlavorRadio.initDedicatorias = function() {
        const self = this;
        const container = $('.flavor-radio-dedicatorias');

        if (!container.length) return;

        // Cargar mis dedicatorias
        this.loadMisDedicatorias(container);

        // Form submit
        container.find('#radio-form-dedicatoria').on('submit', function(e) {
            e.preventDefault();
            self.enviarDedicatoria($(this));
        });
    };

    FlavorRadio.enviarDedicatoria = function(form) {
        const self = this;
        const btn = form.find('button[type="submit"]');

        btn.prop('disabled', true).text('Enviando...');

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_radio_enviar_dedicatoria',
                nonce: this.nonce,
                de: form.find('[name="de"]').val(),
                para: form.find('[name="para"]').val(),
                mensaje: form.find('[name="mensaje"]').val(),
                cancion_titulo: form.find('[name="cancion_titulo"]').val(),
                cancion_artista: form.find('[name="cancion_artista"]').val(),
            },
            success: function(response) {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-heart"></span> Enviar Dedicatoria');

                if (response.success) {
                    self.showToast(response.data.mensaje || 'Dedicatoria enviada', 'success');
                    form[0].reset();
                    self.loadMisDedicatorias($('.flavor-radio-dedicatorias'));
                } else {
                    self.showToast(response.data || 'Error', 'error');
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-heart"></span> Enviar Dedicatoria');
                self.showToast('Error de conexión', 'error');
            }
        });
    };

    FlavorRadio.loadMisDedicatorias = function(container) {
        const lista = container.find('.mis-dedicatorias-lista');

        if (!this.user_id) {
            lista.html('<p style="color: #9ca3af;">Inicia sesión para ver tu historial</p>');
            return;
        }

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: { action: 'flavor_radio_mis_dedicatorias' },
            success: function(response) {
                if (response.success && response.data.dedicatorias && response.data.dedicatorias.length) {
                    let html = '';
                    response.data.dedicatorias.forEach(function(d) {
                        html += `
                            <div class="dedicatoria-item">
                                <div class="dedicatoria-icono"><span class="dashicons dashicons-heart"></span></div>
                                <div class="dedicatoria-content">
                                    <div class="dedicatoria-header">De: ${d.de} → Para: ${d.para}</div>
                                    <div class="dedicatoria-mensaje">${d.mensaje}</div>
                                    <span class="dedicatoria-estado ${d.estado}">${d.estado}</span>
                                </div>
                            </div>
                        `;
                    });
                    lista.html(html);
                } else {
                    lista.html('<p style="color: #9ca3af;">No has enviado dedicatorias aún</p>');
                }
            }
        });
    };

    // =========================================================================
    // Chat
    // =========================================================================

    FlavorRadio.initChat = function() {
        const self = this;
        const container = $('.flavor-radio-chat');

        if (!container.length) return;

        // Form submit
        container.find('.radio-chat-form').on('submit', function(e) {
            e.preventDefault();
            self.enviarMensajeChat($(this), container);
        });

        // Cargar mensajes iniciales
        this.loadChatMensajes(container);

        // Poll para nuevos mensajes
        this.chatInterval = setInterval(function() {
            if (self.currentEmisionId > 0) {
                self.loadChatMensajes(container, true);
            }
        }, 3000);
    };

    FlavorRadio.loadChatMensajes = function(container, incremental) {
        const self = this;
        const mensajesDiv = container.find('.radio-chat-mensajes');
        const emisionId = this.currentEmisionId;

        if (!emisionId) {
            container.find('.radio-chat-status').text('Sin emisión activa').removeClass('conectado');
            mensajesDiv.html('<p style="text-align: center; color: #9ca3af; padding: 2rem;">El chat estará disponible cuando haya una emisión en vivo</p>');
            return;
        }

        container.find('.radio-chat-status').text('Conectado').addClass('conectado');

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_radio_chat_mensajes',
                emision_id: emisionId,
                desde: incremental ? this.lastChatId : 0,
            },
            success: function(response) {
                if (response.success && response.data.mensajes) {
                    if (incremental && response.data.mensajes.length > 0) {
                        // Añadir solo nuevos mensajes
                        response.data.mensajes.forEach(function(m) {
                            mensajesDiv.append(self.renderMensajeChat(m));
                            self.lastChatId = Math.max(self.lastChatId, m.id);
                        });
                        mensajesDiv.scrollTop(mensajesDiv[0].scrollHeight);
                    } else if (!incremental) {
                        // Reemplazar todo
                        if (response.data.mensajes.length > 0) {
                            let html = '';
                            response.data.mensajes.forEach(function(m) {
                                html += self.renderMensajeChat(m);
                                self.lastChatId = Math.max(self.lastChatId, m.id);
                            });
                            mensajesDiv.html(html);
                            mensajesDiv.scrollTop(mensajesDiv[0].scrollHeight);
                        } else {
                            mensajesDiv.html('<p style="text-align: center; color: #9ca3af; padding: 2rem;">Sé el primero en comentar</p>');
                        }
                    }
                }
            }
        });
    };

    FlavorRadio.renderMensajeChat = function(m) {
        let reaccionesHtml = '<div class="chat-mensaje-reacciones">';
        if (m.reacciones && m.reacciones.length > 0) {
            m.reacciones.forEach(r => {
                const miReaccion = r.usuarios && r.usuarios.includes(this.user_id) ? 'mi-reaccion' : '';
                reaccionesHtml += `<span class="chat-reaccion ${miReaccion}" data-emoji="${r.emoji}">
                    <span class="emoji">${r.emoji}</span>
                    <span class="count">${r.count}</span>
                </span>`;
            });
        }
        reaccionesHtml += '<button class="btn-add-reaccion">+</button></div>';

        return `
            <div class="chat-mensaje ${m.destacado ? 'destacado' : ''}" data-id="${m.id}">
                <img src="${m.autor.avatar}" class="chat-mensaje-avatar" alt="">
                <div class="chat-mensaje-content">
                    <span class="chat-mensaje-autor">${m.autor.nombre}</span>
                    <p class="chat-mensaje-texto">${m.mensaje}</p>
                    <span class="chat-mensaje-fecha">${new Date(m.fecha).toLocaleTimeString()}</span>
                    ${reaccionesHtml}
                </div>
            </div>
        `;
    };

    FlavorRadio.enviarMensajeChat = function(form, container) {
        const self = this;
        const input = form.find('input[name="mensaje"]');
        const mensaje = input.val().trim();

        if (!mensaje || !this.currentEmisionId) return;

        input.prop('disabled', true);

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_radio_chat_mensaje',
                nonce: this.nonce,
                emision_id: this.currentEmisionId,
                mensaje: mensaje,
            },
            success: function(response) {
                input.prop('disabled', false);

                if (response.success) {
                    input.val('');
                    const mensajesDiv = container.find('.radio-chat-mensajes');

                    // Si está vacío con el mensaje de "sé el primero"
                    if (mensajesDiv.find('.chat-mensaje').length === 0) {
                        mensajesDiv.empty();
                    }

                    mensajesDiv.append(self.renderMensajeChat(response.data.mensaje));
                    self.lastChatId = Math.max(self.lastChatId, response.data.mensaje.id);
                    mensajesDiv.scrollTop(mensajesDiv[0].scrollHeight);
                } else {
                    self.showToast(response.data || 'Error', 'error');
                }
            },
            error: function() {
                input.prop('disabled', false);
                self.showToast('Error de conexión', 'error');
            }
        });
    };

    // =========================================================================
    // Proponer Programa
    // =========================================================================

    FlavorRadio.initProponer = function() {
        const self = this;
        const form = $('#radio-form-proponer');

        if (!form.length) return;

        form.on('submit', function(e) {
            e.preventDefault();
            self.enviarPropuesta($(this));
        });
    };

    FlavorRadio.enviarPropuesta = function(form) {
        const self = this;
        const btn = form.find('button[type="submit"]');

        btn.prop('disabled', true).text('Enviando...');

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_radio_proponer_programa',
                nonce: this.nonce,
                nombre: form.find('[name="nombre"]').val(),
                descripcion: form.find('[name="descripcion"]').val(),
                categoria: form.find('[name="categoria"]').val(),
                frecuencia: form.find('[name="frecuencia"]').val(),
                horario: form.find('[name="horario"]').val(),
                experiencia: form.find('[name="experiencia"]').val(),
                demo_url: form.find('[name="demo_url"]').val(),
            },
            success: function(response) {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-microphone"></span> Enviar Propuesta');

                if (response.success) {
                    self.showToast(response.data.mensaje || 'Propuesta enviada', 'success');
                    form[0].reset();
                } else {
                    self.showToast(response.data || 'Error', 'error');
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-microphone"></span> Enviar Propuesta');
                self.showToast('Error de conexión', 'error');
            }
        });
    };

    // =========================================================================
    // Podcasts
    // =========================================================================

    FlavorRadio.initPodcasts = function() {
        const self = this;
        const container = $('.flavor-radio-podcasts');

        if (!container.length) return;

        this.loadPodcasts(container);

        // Filtro por programa
        container.find('.podcasts-filtro-programa').on('change', function() {
            container.data('programa', $(this).val());
            self.loadPodcasts(container);
        });

        // Click para reproducir
        container.on('click', '.podcast-play button', function() {
            const url = $(this).data('url');
            self.playPodcast(url, $(this));
        });
    };

    FlavorRadio.loadPodcasts = function(container) {
        const self = this;
        const lista = container.find('.podcasts-lista');
        const programaId = container.data('programa') || 0;
        const limite = container.data('limite') || 10;

        lista.html('<div class="radio-loading">Cargando podcasts...</div>');

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_radio_podcasts',
                programa_id: programaId,
                limite: limite,
            },
            success: function(response) {
                if (response.success && response.data.podcasts && response.data.podcasts.length) {
                    self.renderPodcasts(lista, response.data.podcasts);
                } else {
                    lista.html('<div class="radio-empty"><span class="dashicons dashicons-playlist-audio"></span><p>No hay podcasts disponibles</p></div>');
                }
            }
        });
    };

    FlavorRadio.renderPodcasts = function(container, podcasts) {
        let html = '';
        podcasts.forEach(function(pod) {
            html += `
                <div class="podcast-item" data-id="${pod.id}">
                    ${pod.imagen
                        ? `<img src="${pod.imagen}" class="podcast-imagen" alt="">`
                        : '<div class="podcast-imagen-placeholder"><span class="dashicons dashicons-microphone"></span></div>'
                    }
                    <div class="podcast-info">
                        <div class="podcast-titulo">${pod.titulo}</div>
                        <div class="podcast-programa">${pod.programa.nombre}</div>
                        <div class="podcast-meta">
                            <span>${pod.duracion}</span>
                            <span>${pod.reproducciones} reproducciones</span>
                            <span>${pod.fecha_humana}</span>
                        </div>
                    </div>
                    <div class="podcast-play">
                        <button data-url="${pod.archivo}">
                            <span class="dashicons dashicons-controls-play"></span>
                        </button>
                    </div>
                </div>
            `;
        });
        container.html(html);
    };

    FlavorRadio.playPodcast = function(url, btn) {
        // Pausar stream si está reproduciéndose
        if (this.audio && !this.audio.paused) {
            this.audio.pause();
            $('.radio-btn-play').removeClass('playing');
        }

        // Crear audio para podcast
        if (!this.podcastAudio) {
            this.podcastAudio = new Audio();
        }

        const isPlaying = !this.podcastAudio.paused && this.podcastAudio.src === url;

        if (isPlaying) {
            this.podcastAudio.pause();
            btn.find('.dashicons').removeClass('dashicons-controls-pause').addClass('dashicons-controls-play');
        } else {
            // Pausar cualquier otro podcast
            $('.podcast-play button .dashicons').removeClass('dashicons-controls-pause').addClass('dashicons-controls-play');

            this.podcastAudio.src = url;
            this.podcastAudio.play();
            btn.find('.dashicons').removeClass('dashicons-controls-play').addClass('dashicons-controls-pause');
        }
    };

    // =========================================================================
    // Utilidades
    // =========================================================================

    FlavorRadio.showToast = function(message, type) {
        type = type || 'info';

        if (!$('.radio-toast-container').length) {
            $('body').append('<div class="radio-toast-container"></div>');
        }

        const toast = $(`<div class="radio-toast ${type}">${message}</div>`);
        $('.radio-toast-container').append(toast);

        setTimeout(function() {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
    };

    // =========================================================================
    // Visualizador de Onda (Web Audio API)
    // =========================================================================

    FlavorRadio.initVisualizer = function() {
        const canvas = document.getElementById('radio-visualizer-canvas');
        if (!canvas) return;

        this.visualizerCanvas = canvas;
        this.visualizerCtx = canvas.getContext('2d');
    };

    FlavorRadio.startVisualizer = function() {
        if (!this.audio || !this.visualizerCanvas) return;

        try {
            if (!this.audioContext) {
                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                this.analyser = this.audioContext.createAnalyser();
                this.analyser.fftSize = 256;

                const source = this.audioContext.createMediaElementSource(this.audio);
                source.connect(this.analyser);
                this.analyser.connect(this.audioContext.destination);
            }

            if (this.audioContext.state === 'suspended') {
                this.audioContext.resume();
            }

            this.drawVisualizer();
        } catch (e) {
            console.log('Visualizer not supported:', e);
        }
    };

    FlavorRadio.drawVisualizer = function() {
        if (!this.analyser || !this.visualizerCtx) return;

        const canvas = this.visualizerCanvas;
        const ctx = this.visualizerCtx;
        const bufferLength = this.analyser.frequencyBinCount;
        const dataArray = new Uint8Array(bufferLength);

        const draw = () => {
            if (this.audio.paused) return;

            requestAnimationFrame(draw);
            this.analyser.getByteFrequencyData(dataArray);

            ctx.fillStyle = 'rgba(139, 92, 246, 0.1)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            const barWidth = (canvas.width / bufferLength) * 2.5;
            let barHeight;
            let x = 0;

            for (let i = 0; i < bufferLength; i++) {
                barHeight = (dataArray[i] / 255) * canvas.height;

                const gradient = ctx.createLinearGradient(0, canvas.height - barHeight, 0, canvas.height);
                gradient.addColorStop(0, '#a78bfa');
                gradient.addColorStop(1, '#7c3aed');

                ctx.fillStyle = gradient;
                ctx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);

                x += barWidth + 1;
            }
        };

        draw();
    };

    FlavorRadio.stopVisualizer = function() {
        // El visualizador se detiene automáticamente cuando el audio se pausa
    };

    // =========================================================================
    // Metadatos Shoutcast/Icecast
    // =========================================================================

    FlavorRadio.initMetadataPolling = function() {
        const self = this;
        const metadataUrl = radioConfig.metadataUrl;

        if (!metadataUrl) return;

        this.fetchMetadata();
        this.metadataInterval = setInterval(function() {
            self.fetchMetadata();
        }, 15000); // Cada 15 segundos
    };

    FlavorRadio.fetchMetadata = function() {
        const self = this;
        const metadataUrl = radioConfig.metadataUrl;

        if (!metadataUrl) return;

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_radio_get_metadata',
                nonce: this.nonce,
            },
            success: function(response) {
                if (response.success && response.data) {
                    self.updateNowPlaying(response.data);
                }
            }
        });
    };

    FlavorRadio.updateNowPlaying = function(data) {
        const container = $('#radio-now-playing');
        if (!container.length) return;

        container.find('.now-playing-title').text(data.title || 'Sin información');
        container.find('.now-playing-artist').text(data.artist || '');

        if (data.title && data.title !== this.lastSongTitle) {
            this.lastSongTitle = data.title;
            container.addClass('song-changed');
            setTimeout(() => container.removeClass('song-changed'), 500);
        }
    };

    // =========================================================================
    // Sistema de Favoritos
    // =========================================================================

    FlavorRadio.initFavoritos = function() {
        const self = this;

        // Botones de favorito
        $(document).on('click', '.btn-favorito', function(e) {
            e.preventDefault();
            const programaId = $(this).data('programa-id');
            self.toggleFavorito(programaId, $(this));
        });

        // Botones de notificación
        $(document).on('click', '.btn-notificacion', function(e) {
            e.preventDefault();
            const programaId = $(this).data('programa-id');
            self.toggleNotificacion(programaId, $(this));
        });
    };

    FlavorRadio.loadFavoritos = function() {
        const self = this;

        if (!this.user_id) {
            this.favoritos = JSON.parse(localStorage.getItem('radio_favoritos') || '[]');
            return;
        }

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: { action: 'flavor_radio_mis_favoritos' },
            success: function(response) {
                if (response.success) {
                    self.favoritos = response.data.favoritos || [];
                    self.updateFavoritosUI();
                }
            }
        });
    };

    FlavorRadio.toggleFavorito = function(programaId, btn) {
        const self = this;
        const isFavorito = this.favoritos.includes(programaId);

        btn.prop('disabled', true);

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_radio_toggle_favorito',
                nonce: this.nonce,
                programa_id: programaId,
            },
            success: function(response) {
                btn.prop('disabled', false);

                if (response.success) {
                    if (isFavorito) {
                        self.favoritos = self.favoritos.filter(id => id !== programaId);
                        btn.removeClass('activo');
                        self.showToast('Eliminado de favoritos', 'info');
                    } else {
                        self.favoritos.push(programaId);
                        btn.addClass('activo');
                        self.showToast('Añadido a favoritos', 'success');
                    }
                    self.updateFavoritosUI();
                }
            },
            error: function() {
                btn.prop('disabled', false);
            }
        });
    };

    FlavorRadio.toggleNotificacion = function(programaId, btn) {
        const self = this;

        btn.prop('disabled', true);

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_radio_toggle_notificacion',
                nonce: this.nonce,
                programa_id: programaId,
            },
            success: function(response) {
                btn.prop('disabled', false);

                if (response.success) {
                    btn.toggleClass('activo');
                    const activo = btn.hasClass('activo');
                    self.showToast(
                        activo ? 'Notificaciones activadas' : 'Notificaciones desactivadas',
                        'info'
                    );
                }
            },
            error: function() {
                btn.prop('disabled', false);
            }
        });
    };

    FlavorRadio.updateFavoritosUI = function() {
        const self = this;
        $('.btn-favorito').each(function() {
            const programaId = $(this).data('programa-id');
            $(this).toggleClass('activo', self.favoritos.includes(programaId));
        });
    };

    // =========================================================================
    // Compartir en Redes Sociales
    // =========================================================================

    FlavorRadio.initShare = function() {
        const self = this;

        $(document).on('click', '.btn-share', function(e) {
            e.preventDefault();
            const red = $(this).data('red');
            const tipo = $(this).data('tipo');
            const id = $(this).data('id');

            self.share(red, tipo, id);
        });

        $(document).on('click', '.btn-share-song', function(e) {
            e.preventDefault();
            self.shareCurrentSong();
        });
    };

    FlavorRadio.share = function(red, tipo, id) {
        const baseUrl = window.location.origin;
        let shareUrl, shareText, shareTitle;

        switch (tipo) {
            case 'programa':
                shareUrl = `${baseUrl}/radio/programa/${id}`;
                shareTitle = 'Escuchando en ' + (radioConfig.nombreRadio || 'Radio Comunitaria');
                shareText = 'Escucha este programa en la radio comunitaria';
                break;
            case 'podcast':
                shareUrl = `${baseUrl}/radio/podcast/${id}`;
                shareTitle = 'Podcast recomendado';
                shareText = 'Te recomiendo este podcast';
                break;
            case 'dedicatoria':
                shareUrl = `${baseUrl}/radio/dedicatorias`;
                shareTitle = '¡Envía una dedicatoria!';
                shareText = 'Envía dedicatorias a tus seres queridos en la radio';
                break;
            default:
                shareUrl = radioConfig.radioUrl || baseUrl + '/radio';
                shareTitle = radioConfig.nombreRadio || 'Radio Comunitaria';
                shareText = 'Escucha la radio comunitaria';
        }

        this.openShareWindow(red, shareUrl, shareText);
    };

    FlavorRadio.shareCurrentSong = function() {
        const title = $('#radio-now-playing .now-playing-title').text();
        const artist = $('#radio-now-playing .now-playing-artist').text();

        if (!title) {
            this.showToast('No hay canción reproduciéndose', 'info');
            return;
        }

        const shareText = `🎵 Escuchando: ${title}${artist ? ' - ' + artist : ''} en ${radioConfig.nombreRadio || 'Radio Comunitaria'}`;

        // Mostrar modal de compartir
        this.showShareModal(shareText, radioConfig.radioUrl || window.location.href);
    };

    FlavorRadio.showShareModal = function(text, url) {
        const self = this;
        const modal = $(`
            <div class="share-modal visible">
                <div class="share-modal-content">
                    <h4>Compartir</h4>
                    <div class="share-preview">
                        <p>${text}</p>
                    </div>
                    <div class="radio-share-buttons" style="justify-content: center;">
                        <button class="btn-share twitter" data-red="twitter" title="Twitter">
                            <span class="dashicons dashicons-twitter"></span>
                        </button>
                        <button class="btn-share facebook" data-red="facebook" title="Facebook">
                            <span class="dashicons dashicons-facebook"></span>
                        </button>
                        <button class="btn-share whatsapp" data-red="whatsapp" title="WhatsApp">
                            <span class="dashicons dashicons-whatsapp"></span>
                        </button>
                        <button class="btn-share telegram" data-red="telegram" title="Telegram">
                            <span class="dashicons dashicons-admin-comments"></span>
                        </button>
                        <button class="btn-share copy-link" data-red="copy" title="Copiar enlace">
                            <span class="dashicons dashicons-admin-links"></span>
                        </button>
                    </div>
                    <button class="btn btn-outline" style="margin-top: 1rem; width: 100%;">Cerrar</button>
                </div>
            </div>
        `);

        $('body').append(modal);

        modal.find('.btn-share').on('click', function() {
            const red = $(this).data('red');
            self.openShareWindow(red, url, text);
            modal.remove();
        });

        modal.find('.btn-outline').on('click', function() {
            modal.remove();
        });

        modal.on('click', function(e) {
            if (e.target === this) modal.remove();
        });
    };

    FlavorRadio.openShareWindow = function(red, url, text) {
        let shareUrl;
        const encodedUrl = encodeURIComponent(url);
        const encodedText = encodeURIComponent(text);

        switch (red) {
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?text=${encodedText}&url=${encodedUrl}`;
                break;
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}&quote=${encodedText}`;
                break;
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${encodedText}%20${encodedUrl}`;
                break;
            case 'telegram':
                shareUrl = `https://t.me/share/url?url=${encodedUrl}&text=${encodedText}`;
                break;
            case 'copy':
                navigator.clipboard.writeText(url).then(() => {
                    this.showToast('Enlace copiado al portapapeles', 'success');
                });
                return;
        }

        window.open(shareUrl, '_blank', 'width=600,height=400');
    };

    // =========================================================================
    // Reacciones en Chat
    // =========================================================================

    FlavorRadio.initChatReacciones = function() {
        const self = this;

        // Click en reacción existente
        $(document).on('click', '.chat-reaccion', function(e) {
            e.preventDefault();
            const mensajeId = $(this).closest('.chat-mensaje').data('id');
            const emoji = $(this).data('emoji');
            self.toggleReaccion(mensajeId, emoji);
        });

        // Abrir picker de emojis
        $(document).on('click', '.btn-add-reaccion', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const mensajeId = $(this).closest('.chat-mensaje').data('id');
            self.showEmojiPicker($(this), mensajeId);
        });

        // Cerrar picker al hacer click fuera
        $(document).on('click', function() {
            $('.emoji-picker').remove();
        });
    };

    FlavorRadio.showEmojiPicker = function(btn, mensajeId) {
        const self = this;
        $('.emoji-picker').remove();

        let html = '<div class="emoji-picker">';
        this.emojisRapidos.forEach(emoji => {
            html += `<button data-emoji="${emoji}">${emoji}</button>`;
        });
        html += '</div>';

        const picker = $(html);
        btn.parent().css('position', 'relative').append(picker);

        picker.find('button').on('click', function(e) {
            e.stopPropagation();
            const emoji = $(this).data('emoji');
            self.toggleReaccion(mensajeId, emoji);
            picker.remove();
        });
    };

    FlavorRadio.toggleReaccion = function(mensajeId, emoji) {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_radio_chat_reaccion',
                nonce: this.nonce,
                mensaje_id: mensajeId,
                emoji: emoji,
            },
            success: function(response) {
                if (response.success) {
                    self.updateReaccionesUI(mensajeId, response.data.reacciones);
                }
            }
        });
    };

    FlavorRadio.updateReaccionesUI = function(mensajeId, reacciones) {
        const container = $(`.chat-mensaje[data-id="${mensajeId}"] .chat-mensaje-reacciones`);
        if (!container.length) return;

        let html = '';
        reacciones.forEach(r => {
            const miReaccion = r.usuarios.includes(this.user_id) ? 'mi-reaccion' : '';
            html += `
                <span class="chat-reaccion ${miReaccion}" data-emoji="${r.emoji}">
                    <span class="emoji">${r.emoji}</span>
                    <span class="count">${r.count}</span>
                </span>
            `;
        });
        html += '<button class="btn-add-reaccion">+</button>';

        container.html(html);
    };

    // =========================================================================
    // Múltiples Canales
    // =========================================================================

    FlavorRadio.initCanales = function() {
        const self = this;

        if (!this.canales || this.canales.length <= 1) return;

        $(document).on('click', '.canal-tab', function() {
            const canalId = $(this).data('canal-id');
            self.cambiarCanal(canalId);
        });
    };

    FlavorRadio.cambiarCanal = function(canalId) {
        const self = this;
        const canal = this.canales.find(c => c.id === canalId);

        if (!canal) return;

        const wasPlaying = this.audio && !this.audio.paused;

        if (wasPlaying) {
            this.audio.pause();
        }

        this.currentChannel = canalId;

        // Actualizar UI
        $('.canal-tab').removeClass('activo');
        $(`.canal-tab[data-canal-id="${canalId}"]`).addClass('activo');

        // Actualizar stream URL
        const player = $('.flavor-radio-player');
        player.data('stream', canal.url);
        player.data('stream-hd', canal.url_hd || '');

        // Actualizar info
        player.find('.radio-nombre').text(canal.nombre);
        player.find('.radio-slogan').text(canal.descripcion || '');

        // Reanudar si estaba reproduciendo
        if (wasPlaying) {
            setTimeout(() => {
                this.audio.src = canal.url;
                this.audio.play();
            }, 100);
        }

        this.showToast(`Cambiado a: ${canal.nombre}`, 'info');
    };

    // =========================================================================
    // Calendario de Eventos
    // =========================================================================

    FlavorRadio.initCalendario = function() {
        const self = this;
        const container = $('.radio-calendario');

        if (!container.length) return;

        this.calendarioFecha = new Date();
        this.loadCalendario();

        container.find('.calendario-nav-prev').on('click', function() {
            self.calendarioFecha.setMonth(self.calendarioFecha.getMonth() - 1);
            self.loadCalendario();
        });

        container.find('.calendario-nav-next').on('click', function() {
            self.calendarioFecha.setMonth(self.calendarioFecha.getMonth() + 1);
            self.loadCalendario();
        });

        // Click en día
        $(document).on('click', '.calendario-dia:not(.otro-mes)', function() {
            const fecha = $(this).data('fecha');
            self.showEventosDelDia(fecha);
        });
    };

    FlavorRadio.loadCalendario = function() {
        const self = this;
        const container = $('.radio-calendario');
        const año = this.calendarioFecha.getFullYear();
        const mes = this.calendarioFecha.getMonth();

        // Actualizar título
        const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                       'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        container.find('.calendario-titulo').text(`${meses[mes]} ${año}`);

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_radio_calendario_eventos',
                año: año,
                mes: mes + 1,
            },
            success: function(response) {
                if (response.success) {
                    self.renderCalendario(response.data.eventos);
                }
            }
        });
    };

    FlavorRadio.renderCalendario = function(eventos) {
        const container = $('.calendario-grid');
        const año = this.calendarioFecha.getFullYear();
        const mes = this.calendarioFecha.getMonth();

        const primerDia = new Date(año, mes, 1);
        const ultimoDia = new Date(año, mes + 1, 0);
        const diasEnMes = ultimoDia.getDate();
        const primerDiaSemana = primerDia.getDay() || 7; // 1-7 (Lun-Dom)

        const hoy = new Date();
        const esHoyMes = hoy.getMonth() === mes && hoy.getFullYear() === año;

        let html = '';

        // Headers
        ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'].forEach(d => {
            html += `<div class="calendario-dia-header">${d}</div>`;
        });

        // Días del mes anterior
        const diasPrevMes = new Date(año, mes, 0).getDate();
        for (let i = primerDiaSemana - 1; i > 0; i--) {
            html += `<div class="calendario-dia otro-mes"><span class="numero">${diasPrevMes - i + 1}</span></div>`;
        }

        // Días del mes actual
        for (let dia = 1; dia <= diasEnMes; dia++) {
            const fecha = `${año}-${String(mes + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
            const esHoy = esHoyMes && hoy.getDate() === dia;
            const eventosDelDia = eventos.filter(e => e.fecha === fecha);

            html += `<div class="calendario-dia${esHoy ? ' hoy' : ''}" data-fecha="${fecha}">
                <span class="numero">${dia}</span>`;

            eventosDelDia.slice(0, 2).forEach(evento => {
                html += `<div class="calendario-evento${evento.especial ? ' especial' : ''}">${evento.titulo}</div>`;
            });

            if (eventosDelDia.length > 2) {
                html += `<div class="calendario-evento">+${eventosDelDia.length - 2} más</div>`;
            }

            html += '</div>';
        }

        // Días del siguiente mes
        const totalCeldas = container.find('.calendario-dia-header').length + diasEnMes + primerDiaSemana - 1;
        const diasSiguienteMes = 42 - totalCeldas;
        for (let i = 1; i <= diasSiguienteMes && (primerDiaSemana - 1 + diasEnMes) < 42; i++) {
            html += `<div class="calendario-dia otro-mes"><span class="numero">${i}</span></div>`;
        }

        container.html(html);
    };

    FlavorRadio.showEventosDelDia = function(fecha) {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_radio_eventos_dia',
                fecha: fecha,
            },
            success: function(response) {
                if (response.success && response.data.eventos.length > 0) {
                    self.showEventosModal(fecha, response.data.eventos);
                }
            }
        });
    };

    FlavorRadio.showEventosModal = function(fecha, eventos) {
        const fechaObj = new Date(fecha);
        const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const fechaFormateada = fechaObj.toLocaleDateString('es', opciones);

        let eventosHtml = '';
        eventos.forEach(evento => {
            eventosHtml += `
                <div class="evento-item" style="margin-bottom: 1rem; padding: 1rem; background: var(--radio-gray-50); border-radius: 8px;">
                    <div style="font-weight: 600; margin-bottom: 0.25rem;">${evento.titulo}</div>
                    <div style="font-size: 0.875rem; color: var(--radio-gray-500);">
                        ${evento.hora_inicio} - ${evento.hora_fin}
                        ${evento.programa ? ' · ' + evento.programa : ''}
                    </div>
                    ${evento.descripcion ? `<p style="margin: 0.5rem 0 0; font-size: 0.9rem;">${evento.descripcion}</p>` : ''}
                </div>
            `;
        });

        const modal = $(`
            <div class="evento-detalle-modal">
                <div class="evento-detalle">
                    <div class="evento-detalle-header">
                        <h3 style="margin: 0;">Eventos del ${fechaFormateada}</h3>
                    </div>
                    <div class="evento-detalle-body">
                        ${eventosHtml}
                        <button class="btn btn-primary" style="width: 100%;">Cerrar</button>
                    </div>
                </div>
            </div>
        `);

        $('body').append(modal);

        modal.find('.btn').on('click', () => modal.remove());
        modal.on('click', function(e) {
            if (e.target === this) modal.remove();
        });
    };

    // =========================================================================
    // Transcripción de Podcasts
    // =========================================================================

    FlavorRadio.loadTranscripcion = function(podcastId, container) {
        const self = this;

        $.ajax({
            url: this.ajaxurl,
            type: 'GET',
            data: {
                action: 'flavor_radio_podcast_transcripcion',
                podcast_id: podcastId,
            },
            success: function(response) {
                if (response.success && response.data.transcripcion) {
                    self.renderTranscripcion(container, response.data.transcripcion);
                }
            }
        });
    };

    FlavorRadio.renderTranscripcion = function(container, transcripcion) {
        const self = this;

        let html = `
            <div class="podcast-transcripcion">
                <h5><span class="dashicons dashicons-text"></span> Transcripción</h5>
                <div class="transcripcion-buscar">
                    <input type="text" placeholder="Buscar en la transcripción..." id="transcripcion-busqueda">
                </div>
                <div class="transcripcion-texto">
        `;

        transcripcion.segments.forEach(segment => {
            html += `<span class="transcripcion-timestamp" data-time="${segment.start}">${this.formatTime(segment.start)}</span>${segment.text} `;
        });

        html += '</div></div>';

        container.html(html);

        // Buscar en transcripción
        container.find('#transcripcion-busqueda').on('input', function() {
            const query = $(this).val().toLowerCase();
            self.highlightTranscripcion(container, query);
        });

        // Click en timestamp para saltar
        container.find('.transcripcion-timestamp').on('click', function() {
            const time = $(this).data('time');
            if (self.podcastAudio) {
                self.podcastAudio.currentTime = time;
            }
        });
    };

    FlavorRadio.highlightTranscripcion = function(container, query) {
        const texto = container.find('.transcripcion-texto');
        let html = texto.data('original') || texto.html();

        if (!texto.data('original')) {
            texto.data('original', html);
        }

        if (query.length < 2) {
            texto.html(html);
            return;
        }

        const regex = new RegExp(`(${query})`, 'gi');
        html = html.replace(regex, '<span class="transcripcion-highlight">$1</span>');
        texto.html(html);
    };

    FlavorRadio.formatTime = function(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };

    // Exponer globalmente
    window.FlavorRadio = FlavorRadio;

})(jQuery);
