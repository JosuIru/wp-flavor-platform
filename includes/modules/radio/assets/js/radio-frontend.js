/**
 * Radio Frontend JavaScript
 * Flavor Chat IA - Radio Comunitaria
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
        sessionId: null,
        heartbeatInterval: null,
        chatInterval: null,
        lastChatId: 0,
        currentEmisionId: 0,
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

        this.initPlayer();
        this.initProgramacion();
        this.initDedicatorias();
        this.initChat();
        this.initProponer();
        this.initPodcasts();
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
        const autoplay = player.data('autoplay') === 'true';

        if (!streamUrl) return;

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
        const streamUrl = player.data('stream');
        const isHD = player.find('.radio-btn-hd').hasClass('active');
        const streamUrlHD = player.data('stream-hd');

        if (this.audio.paused) {
            this.audio.src = isHD && streamUrlHD ? streamUrlHD : streamUrl;
            this.audio.load();
            this.audio.play().catch(function(e) {
                console.log('Error al reproducir:', e);
            });
        } else {
            this.audio.pause();
            this.audio.src = '';
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
        return `
            <div class="chat-mensaje ${m.destacado ? 'destacado' : ''}">
                <img src="${m.autor.avatar}" class="chat-mensaje-avatar" alt="">
                <div class="chat-mensaje-content">
                    <span class="chat-mensaje-autor">${m.autor.nombre}</span>
                    <p class="chat-mensaje-texto">${m.mensaje}</p>
                    <span class="chat-mensaje-fecha">${new Date(m.fecha).toLocaleTimeString()}</span>
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

    // Exponer globalmente
    window.FlavorRadio = FlavorRadio;

})(jQuery);
