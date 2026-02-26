/**
 * JavaScript del módulo de Estados/Stories
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Chat_Estados
 * @since 1.5.0
 */

(function($) {
    'use strict';

    // Namespace global
    window.FlavorEstados = window.FlavorEstados || {};

    /**
     * Configuración
     */
    FlavorEstados.config = {
        ajaxUrl: flavorEstados?.ajaxUrl || ajaxurl,
        restUrl: flavorEstados?.restUrl || '',
        nonce: flavorEstados?.nonce || '',
        userId: flavorEstados?.userId || 0,
        duracion: flavorEstados?.duracion || 86400,
        duracionVisualizacion: 5000, // 5 segundos por estado
        maxEstadosDia: flavorEstados?.maxEstadosDia || 30,
        strings: flavorEstados?.strings || {}
    };

    /**
     * Estado de la aplicación
     */
    FlavorEstados.state = {
        estados: [],
        misEstados: null,
        currentUser: null,
        currentIndex: 0,
        isPaused: false,
        progressTimer: null,
        touchStartX: 0,
        touchStartY: 0
    };

    /**
     * Colores disponibles para estados de texto
     */
    FlavorEstados.colores = [
        { fondo: '#128C7E', texto: '#FFFFFF' }, // WhatsApp Green
        { fondo: '#075E54', texto: '#FFFFFF' }, // WhatsApp Dark
        { fondo: '#25D366', texto: '#FFFFFF' }, // WhatsApp Light
        { fondo: '#667eea', texto: '#FFFFFF' }, // Purple
        { fondo: '#f5576c', texto: '#FFFFFF' }, // Pink
        { fondo: '#4facfe', texto: '#FFFFFF' }, // Blue
        { fondo: '#43e97b', texto: '#FFFFFF' }, // Green
        { fondo: '#fa709a', texto: '#FFFFFF' }, // Coral
        { fondo: '#1A1A2E', texto: '#FFFFFF' }, // Dark
        { fondo: '#E94560', texto: '#FFFFFF' }, // Red
    ];

    /**
     * Inicialización
     */
    FlavorEstados.init = function() {
        this.bindEvents();
        this.cargarEstados();
    };

    /**
     * Vincular eventos
     */
    FlavorEstados.bindEvents = function() {
        var self = this;

        // Click en estado para ver
        $(document).on('click', '.estado-item:not(.mi-estado)', function() {
            var userId = $(this).data('user-id');
            self.abrirVisor(userId);
        });

        // Click en mi estado
        $(document).on('click', '.estado-item.mi-estado', function() {
            if (self.state.misEstados && self.state.misEstados.estados.length > 0) {
                self.abrirVisor(self.config.userId);
            } else {
                self.abrirCreador();
            }
        });

        // Botón crear estado
        $(document).on('click', '.btn-crear-estado', function(e) {
            e.preventDefault();
            self.abrirCreador();
        });

        // Cerrar visor
        $(document).on('click', '.viewer-close, .estados-viewer-overlay', function(e) {
            if (e.target === this) {
                self.cerrarVisor();
            }
        });

        // Navegación estados
        $(document).on('click', '.estado-nav-area.prev', function() {
            self.estadoAnterior();
        });

        $(document).on('click', '.estado-nav-area.next', function() {
            self.estadoSiguiente();
        });

        // Pausa al mantener presionado
        $(document).on('mousedown touchstart', '.estados-viewer-content', function() {
            self.pausar();
        });

        $(document).on('mouseup touchend', '.estados-viewer-content', function() {
            self.reanudar();
        });

        // Swipe gestures
        $(document).on('touchstart', '.estados-viewer', function(e) {
            self.state.touchStartX = e.touches[0].clientX;
            self.state.touchStartY = e.touches[0].clientY;
        });

        $(document).on('touchend', '.estados-viewer', function(e) {
            var touchEndX = e.changedTouches[0].clientX;
            var touchEndY = e.changedTouches[0].clientY;
            var diffX = self.state.touchStartX - touchEndX;
            var diffY = self.state.touchStartY - touchEndY;

            // Solo si el swipe es más horizontal que vertical
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    self.siguienteUsuario();
                } else {
                    self.usuarioAnterior();
                }
            }
        });

        // Responder estado
        $(document).on('submit', '.estado-responder-form', function(e) {
            e.preventDefault();
            var mensaje = $(this).find('input').val();
            if (mensaje.trim()) {
                self.responderEstado(mensaje);
                $(this).find('input').val('');
            }
        });

        // Reaccionar
        $(document).on('click', '.estado-reaccion-btn', function() {
            var emoji = $(this).data('emoji');
            self.reaccionarEstado(emoji);
        });

        // Ver visualizaciones
        $(document).on('click', '.btn-ver-visualizaciones', function() {
            self.toggleVisualizaciones();
        });

        // Cerrar visualizaciones
        $(document).on('click', '.visualizaciones-close', function() {
            $('.visualizaciones-panel').removeClass('active');
        });

        // Crear estado - seleccionar tipo
        $(document).on('click', '.tipo-estado-btn', function() {
            var tipo = $(this).data('tipo');
            $('.tipo-estado-btn').removeClass('active');
            $(this).addClass('active');
            self.cambiarTipoCreador(tipo);
        });

        // Crear estado - seleccionar color
        $(document).on('click', '.color-option', function() {
            var index = $(this).data('index');
            $('.color-option').removeClass('active');
            $(this).addClass('active');
            self.aplicarColor(index);
        });

        // Crear estado - upload
        $(document).on('change', '#estado-media-input', function() {
            var file = this.files[0];
            if (file) {
                self.uploadMedia(file);
            }
        });

        $(document).on('click', '.crear-estado-upload', function() {
            $('#estado-media-input').click();
        });

        // Crear estado - publicar
        $(document).on('click', '.crear-estado-publicar', function() {
            self.publicarEstado();
        });

        // Crear estado - cerrar
        $(document).on('click', '.crear-estado-close', function() {
            self.cerrarCreador();
        });

        // Eliminar estado propio
        $(document).on('click', '.btn-eliminar-estado', function() {
            var estadoId = self.getCurrentEstado().id;
            if (confirm(self.config.strings.confirmarEliminar)) {
                self.eliminarEstado(estadoId);
            }
        });

        // Silenciar usuario
        $(document).on('click', '.btn-silenciar-usuario', function() {
            var userId = $(this).data('user-id');
            self.silenciarUsuario(userId);
        });

        // Keyboard navigation
        $(document).on('keydown', function(e) {
            if (!$('.estados-viewer-overlay').hasClass('active')) return;

            switch(e.key) {
                case 'ArrowLeft':
                    self.estadoAnterior();
                    break;
                case 'ArrowRight':
                    self.estadoSiguiente();
                    break;
                case 'Escape':
                    self.cerrarVisor();
                    break;
                case ' ':
                    e.preventDefault();
                    self.state.isPaused ? self.reanudar() : self.pausar();
                    break;
            }
        });
    };

    /**
     * Cargar estados
     */
    FlavorEstados.cargarEstados = function() {
        var self = this;

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_estados_obtener',
                nonce: this.config.nonce
            },
            success: function(response) {
                if (response.success) {
                    self.state.misEstados = response.data.mis_estados;
                    self.state.estados = response.data.contactos;
                    self.renderLista();
                }
            }
        });
    };

    /**
     * Renderizar lista de estados
     */
    FlavorEstados.renderLista = function() {
        var $lista = $('.estados-lista-horizontal');
        if (!$lista.length) return;

        $lista.empty();

        // Mi estado
        var miEstadoHtml = this.renderMiEstado();
        $lista.append(miEstadoHtml);

        // Estados de contactos
        this.state.estados.forEach(function(usuario) {
            var html = this.renderEstadoItem(usuario);
            $lista.append(html);
        }.bind(this));
    };

    /**
     * Renderizar mi estado
     */
    FlavorEstados.renderMiEstado = function() {
        var tieneEstados = this.state.misEstados && this.state.misEstados.estados.length > 0;
        var avatar = this.state.misEstados?.autor_avatar || '';
        var sinVer = this.state.misEstados?.sin_ver || 0;

        var ringClass = tieneEstados ? (sinVer > 0 ? 'sin-ver' : 'visto') : '';

        return '<div class="estado-item mi-estado ' + (tieneEstados ? 'tiene-estados' : '') + '" data-user-id="' + this.config.userId + '">' +
            '<div class="estado-avatar-wrapper">' +
            '<div class="estado-ring ' + ringClass + '"></div>' +
            '<img src="' + avatar + '" class="estado-avatar" alt="">' +
            '</div>' +
            '<span class="estado-nombre">' + this.config.strings.tuEstado + '</span>' +
            '</div>';
    };

    /**
     * Renderizar item de estado
     */
    FlavorEstados.renderEstadoItem = function(usuario) {
        var sinVer = usuario.sin_ver || 0;
        var total = usuario.estados.length;
        var ringClass = sinVer > 0 ? 'sin-ver' : 'visto';

        // Si tiene múltiples estados, calcular progreso
        if (total > 1) {
            ringClass = 'segmentado';
        }

        return '<div class="estado-item" data-user-id="' + usuario.usuario_id + '">' +
            '<div class="estado-avatar-wrapper">' +
            '<div class="estado-ring ' + ringClass + '" style="--progreso: ' + ((total - sinVer) / total * 100) + '"></div>' +
            '<img src="' + usuario.autor_avatar + '" class="estado-avatar" alt="">' +
            '</div>' +
            '<span class="estado-nombre">' + this.escapeHtml(usuario.autor_nombre) + '</span>' +
            '</div>';
    };

    /**
     * Abrir visor de estados
     */
    FlavorEstados.abrirVisor = function(userId) {
        var self = this;

        // Encontrar usuario
        var usuario = null;
        if (userId == this.config.userId) {
            usuario = this.state.misEstados;
        } else {
            usuario = this.state.estados.find(function(u) {
                return u.usuario_id == userId;
            });
        }

        if (!usuario || !usuario.estados.length) return;

        this.state.currentUser = usuario;
        this.state.currentIndex = 0;

        // Crear overlay si no existe
        if (!$('#estados-viewer-overlay').length) {
            this.crearVisorHTML();
        }

        // Mostrar
        $('#estados-viewer-overlay').addClass('active');
        $('body').css('overflow', 'hidden');

        // Renderizar primer estado
        this.renderEstadoActual();
        this.iniciarProgreso();
    };

    /**
     * Crear HTML del visor
     */
    FlavorEstados.crearVisorHTML = function() {
        var html =
            '<div id="estados-viewer-overlay" class="estados-viewer-overlay">' +
            '<div class="estados-viewer">' +
            '<div class="estados-progress-bar"></div>' +
            '<div class="estados-viewer-header">' +
            '<img class="viewer-avatar" src="" alt="">' +
            '<div class="viewer-info">' +
            '<div class="viewer-nombre"></div>' +
            '<div class="viewer-tiempo"></div>' +
            '</div>' +
            '<button class="viewer-options" type="button"><span class="dashicons dashicons-ellipsis"></span></button>' +
            '<button class="viewer-close" type="button">&times;</button>' +
            '</div>' +
            '<div class="estados-viewer-content">' +
            '<div class="estado-nav-area prev"></div>' +
            '<div class="estado-nav-area next"></div>' +
            '</div>' +
            '<div class="estados-viewer-footer">' +
            '<form class="estado-responder-form estado-responder-input">' +
            '<input type="text" placeholder="' + this.config.strings.responder + '">' +
            '<button type="submit" class="estado-responder-btn"><span class="dashicons dashicons-arrow-right-alt"></span></button>' +
            '</form>' +
            '<div class="estado-reacciones">' +
            '<button class="estado-reaccion-btn" data-emoji="❤️">❤️</button>' +
            '<button class="estado-reaccion-btn" data-emoji="😂">😂</button>' +
            '<button class="estado-reaccion-btn" data-emoji="😮">😮</button>' +
            '<button class="estado-reaccion-btn" data-emoji="😢">😢</button>' +
            '</div>' +
            '</div>' +
            '<div class="visualizaciones-panel">' +
            '<div class="visualizaciones-header">' +
            '<h4><span class="dashicons dashicons-visibility"></span> Visto por <span class="count">0</span></h4>' +
            '<button class="visualizaciones-close">&times;</button>' +
            '</div>' +
            '<div class="visualizaciones-list"></div>' +
            '</div>' +
            '</div>' +
            '</div>';

        $('body').append(html);
    };

    /**
     * Renderizar estado actual
     */
    FlavorEstados.renderEstadoActual = function() {
        var estado = this.getCurrentEstado();
        if (!estado) return;

        var $viewer = $('.estados-viewer');

        // Header
        $viewer.find('.viewer-avatar').attr('src', this.state.currentUser.autor_avatar);
        $viewer.find('.viewer-nombre').text(this.state.currentUser.autor_nombre);
        $viewer.find('.viewer-tiempo').text(estado.tiempo_relativo);

        // Progress bar
        this.renderProgressBar();

        // Content
        var $content = $viewer.find('.estados-viewer-content');
        $content.find('.estado-content').remove();

        var contentHtml = this.renderEstadoContent(estado);
        $content.prepend(contentHtml);

        // Footer - ocultar responder si es mi estado
        if (this.state.currentUser.usuario_id == this.config.userId) {
            $viewer.find('.estado-responder-form, .estado-reacciones').hide();
            $viewer.find('.estados-viewer-footer').append(
                '<button class="btn-ver-visualizaciones" style="flex:1;background:rgba(255,255,255,0.1);border:none;color:white;padding:12px;border-radius:24px;cursor:pointer;">' +
                '<span class="dashicons dashicons-visibility"></span> ' + estado.visualizaciones_count + ' vistas</button>'
            );
        } else {
            $viewer.find('.estado-responder-form, .estado-reacciones').show();
            $viewer.find('.btn-ver-visualizaciones').remove();

            // Marcar como visto
            this.marcarVisto(estado.id);
        }
    };

    /**
     * Renderizar contenido del estado
     */
    FlavorEstados.renderEstadoContent = function(estado) {
        var html = '';

        switch (estado.tipo) {
            case 'texto':
                html = '<div class="estado-content estado-content-texto" style="background:' + estado.color_fondo + ';color:' + estado.color_texto + '">' +
                    '<div class="estado-texto-inner">' + this.escapeHtml(estado.contenido) + '</div>' +
                    '</div>';
                break;

            case 'imagen':
                html = '<img class="estado-content estado-content-imagen" src="' + estado.media_url + '" alt="">';
                if (estado.contenido) {
                    html += '<div class="estado-caption">' + this.escapeHtml(estado.contenido) + '</div>';
                }
                break;

            case 'video':
                html = '<video class="estado-content estado-content-video" src="' + estado.media_url + '" autoplay muted></video>';
                break;

            case 'ubicacion':
                html = '<div class="estado-content estado-content-ubicacion">' +
                    '<div class="ubicacion-mapa" data-lat="' + estado.ubicacion_lat + '" data-lng="' + estado.ubicacion_lng + '"></div>' +
                    '<div class="ubicacion-nombre">' + this.escapeHtml(estado.ubicacion_nombre) + '</div>' +
                    '</div>';
                break;
        }

        return html;
    };

    /**
     * Renderizar barra de progreso
     */
    FlavorEstados.renderProgressBar = function() {
        var $bar = $('.estados-progress-bar');
        $bar.empty();

        var total = this.state.currentUser.estados.length;
        var current = this.state.currentIndex;

        for (var i = 0; i < total; i++) {
            var segmentClass = '';
            if (i < current) segmentClass = 'completed';
            else if (i === current) segmentClass = 'active';

            $bar.append('<div class="progress-segment ' + segmentClass + '"><div class="progress-fill"></div></div>');
        }

        // Configurar duración
        var estado = this.getCurrentEstado();
        var duracion = estado.tipo === 'video' ? (estado.duracion_media * 1000) : this.config.duracionVisualizacion;
        $bar.find('.progress-segment.active').css('--duration', duracion + 'ms');
    };

    /**
     * Iniciar progreso automático
     */
    FlavorEstados.iniciarProgreso = function() {
        var self = this;
        this.detenerProgreso();

        var estado = this.getCurrentEstado();
        var duracion = estado.tipo === 'video' ? (estado.duracion_media * 1000) : this.config.duracionVisualizacion;

        this.state.progressTimer = setTimeout(function() {
            self.estadoSiguiente();
        }, duracion);
    };

    /**
     * Detener progreso
     */
    FlavorEstados.detenerProgreso = function() {
        if (this.state.progressTimer) {
            clearTimeout(this.state.progressTimer);
            this.state.progressTimer = null;
        }
    };

    /**
     * Pausar
     */
    FlavorEstados.pausar = function() {
        this.state.isPaused = true;
        this.detenerProgreso();
        $('.progress-segment.active .progress-fill').css('animation-play-state', 'paused');
    };

    /**
     * Reanudar
     */
    FlavorEstados.reanudar = function() {
        if (!this.state.isPaused) return;
        this.state.isPaused = false;
        $('.progress-segment.active .progress-fill').css('animation-play-state', 'running');
        this.iniciarProgreso();
    };

    /**
     * Estado siguiente
     */
    FlavorEstados.estadoSiguiente = function() {
        if (this.state.currentIndex < this.state.currentUser.estados.length - 1) {
            this.state.currentIndex++;
            this.renderEstadoActual();
            this.iniciarProgreso();
        } else {
            this.siguienteUsuario();
        }
    };

    /**
     * Estado anterior
     */
    FlavorEstados.estadoAnterior = function() {
        if (this.state.currentIndex > 0) {
            this.state.currentIndex--;
            this.renderEstadoActual();
            this.iniciarProgreso();
        } else {
            this.usuarioAnterior();
        }
    };

    /**
     * Siguiente usuario
     */
    FlavorEstados.siguienteUsuario = function() {
        var currentUserId = this.state.currentUser.usuario_id;
        var index = this.state.estados.findIndex(function(u) {
            return u.usuario_id == currentUserId;
        });

        if (index < this.state.estados.length - 1) {
            this.state.currentUser = this.state.estados[index + 1];
            this.state.currentIndex = 0;
            this.renderEstadoActual();
            this.iniciarProgreso();
        } else {
            this.cerrarVisor();
        }
    };

    /**
     * Usuario anterior
     */
    FlavorEstados.usuarioAnterior = function() {
        var currentUserId = this.state.currentUser.usuario_id;
        var index = this.state.estados.findIndex(function(u) {
            return u.usuario_id == currentUserId;
        });

        if (index > 0) {
            this.state.currentUser = this.state.estados[index - 1];
            this.state.currentIndex = this.state.currentUser.estados.length - 1;
            this.renderEstadoActual();
            this.iniciarProgreso();
        }
    };

    /**
     * Cerrar visor
     */
    FlavorEstados.cerrarVisor = function() {
        this.detenerProgreso();
        $('#estados-viewer-overlay').removeClass('active');
        $('body').css('overflow', '');
        this.state.currentUser = null;
        this.state.currentIndex = 0;

        // Refrescar lista
        this.cargarEstados();
    };

    /**
     * Obtener estado actual
     */
    FlavorEstados.getCurrentEstado = function() {
        if (!this.state.currentUser) return null;
        return this.state.currentUser.estados[this.state.currentIndex];
    };

    /**
     * Marcar como visto
     */
    FlavorEstados.marcarVisto = function(estadoId) {
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_estados_ver',
                nonce: this.config.nonce,
                estado_id: estadoId
            }
        });
    };

    /**
     * Reaccionar a estado
     */
    FlavorEstados.reaccionarEstado = function(emoji) {
        var estado = this.getCurrentEstado();
        if (!estado) return;

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_estados_reaccionar',
                nonce: this.config.nonce,
                estado_id: estado.id,
                emoji: emoji
            },
            success: function() {
                FlavorEstados.showToast('Reacción enviada', 'success');
            }
        });
    };

    /**
     * Responder a estado
     */
    FlavorEstados.responderEstado = function(mensaje) {
        var estado = this.getCurrentEstado();
        if (!estado) return;

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_estados_responder',
                nonce: this.config.nonce,
                estado_id: estado.id,
                mensaje: mensaje
            },
            success: function() {
                FlavorEstados.showToast('Respuesta enviada', 'success');
            }
        });
    };

    /**
     * Toggle visualizaciones
     */
    FlavorEstados.toggleVisualizaciones = function() {
        var $panel = $('.visualizaciones-panel');

        if ($panel.hasClass('active')) {
            $panel.removeClass('active');
            return;
        }

        var estado = this.getCurrentEstado();
        if (!estado) return;

        // Cargar visualizaciones
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_estados_visualizaciones',
                nonce: this.config.nonce,
                estado_id: estado.id
            },
            success: function(response) {
                if (response.success) {
                    FlavorEstados.renderVisualizaciones(response.data);
                    $panel.addClass('active');
                }
            }
        });
    };

    /**
     * Renderizar visualizaciones
     */
    FlavorEstados.renderVisualizaciones = function(visualizaciones) {
        var $list = $('.visualizaciones-list');
        $list.empty();

        $('.visualizaciones-header .count').text(visualizaciones.length);

        if (visualizaciones.length === 0) {
            $list.html('<p style="text-align:center;padding:20px;color:var(--estados-text-muted)">Nadie ha visto este estado aún</p>');
            return;
        }

        visualizaciones.forEach(function(v) {
            var html = '<div class="visualizacion-item">' +
                '<img src="' + (v.avatar || '') + '" alt="">' +
                '<div class="visualizacion-info">' +
                '<div class="visualizacion-nombre">' + FlavorEstados.escapeHtml(v.nombre) + '</div>' +
                '<div class="visualizacion-hora">' + v.fecha_vista + '</div>' +
                '</div>' +
                '</div>';
            $list.append(html);
        });
    };

    // =========================================================================
    // CREAR ESTADO
    // =========================================================================

    /**
     * Abrir creador
     */
    FlavorEstados.abrirCreador = function() {
        if (!$('#crear-estado-overlay').length) {
            this.crearCreadorHTML();
        }

        $('#crear-estado-overlay').addClass('active');
        $('body').css('overflow', 'hidden');

        // Reset
        this.state.nuevoEstado = {
            tipo: 'texto',
            contenido: '',
            color_fondo: this.colores[0].fondo,
            color_texto: this.colores[0].texto,
            media_url: '',
            media_thumbnail: '',
            privacidad: 'todos'
        };

        this.cambiarTipoCreador('texto');
    };

    /**
     * Crear HTML del creador
     */
    FlavorEstados.crearCreadorHTML = function() {
        var coloresHtml = '';
        this.colores.forEach(function(c, i) {
            coloresHtml += '<div class="color-option ' + (i === 0 ? 'active' : '') + '" data-index="' + i + '" style="background:' + c.fondo + '"></div>';
        });

        var html =
            '<div id="crear-estado-overlay" class="crear-estado-overlay">' +
            '<div class="crear-estado-header">' +
            '<button class="crear-estado-close" type="button">&times;</button>' +
            '<h3>Crear estado</h3>' +
            '<button class="crear-estado-publicar" type="button">Publicar</button>' +
            '</div>' +
            '<div class="crear-estado-tipos">' +
            '<button class="tipo-estado-btn active" data-tipo="texto">' +
            '<span class="dashicons dashicons-editor-textcolor"></span>' +
            '<span>Texto</span>' +
            '</button>' +
            '<button class="tipo-estado-btn" data-tipo="imagen">' +
            '<span class="dashicons dashicons-format-image"></span>' +
            '<span>Foto</span>' +
            '</button>' +
            '<button class="tipo-estado-btn" data-tipo="video">' +
            '<span class="dashicons dashicons-video-alt3"></span>' +
            '<span>Video</span>' +
            '</button>' +
            '</div>' +
            '<div class="crear-estado-editor" style="background:' + this.colores[0].fondo + '">' +
            '<textarea placeholder="Escribe tu estado..." maxlength="700"></textarea>' +
            '</div>' +
            '<div class="crear-estado-preview" style="display:none">' +
            '<button class="remove-media" type="button">&times;</button>' +
            '</div>' +
            '<div class="crear-estado-upload" style="display:none">' +
            '<span class="dashicons dashicons-upload upload-icon"></span>' +
            '<p>Haz clic o arrastra un archivo</p>' +
            '<input type="file" id="estado-media-input" accept="image/*,video/*">' +
            '</div>' +
            '<div class="crear-estado-colores">' + coloresHtml + '</div>' +
            '<div class="crear-estado-privacidad">' +
            '<span class="dashicons dashicons-lock privacidad-icon"></span>' +
            '<select class="privacidad-select">' +
            '<option value="todos">Todos mis contactos</option>' +
            '<option value="contactos_excepto">Contactos excepto...</option>' +
            '<option value="solo_compartir">Solo compartir con...</option>' +
            '</select>' +
            '</div>' +
            '</div>';

        $('body').append(html);

        // Eventos del creador
        $('#crear-estado-overlay textarea').on('input', function() {
            FlavorEstados.state.nuevoEstado.contenido = $(this).val();
        });

        $('#crear-estado-overlay .privacidad-select').on('change', function() {
            FlavorEstados.state.nuevoEstado.privacidad = $(this).val();
        });

        $('#crear-estado-overlay .remove-media').on('click', function() {
            FlavorEstados.state.nuevoEstado.media_url = '';
            FlavorEstados.state.nuevoEstado.media_thumbnail = '';
            $('.crear-estado-preview').hide().find('img, video').remove();
            $('.crear-estado-upload').show();
        });
    };

    /**
     * Cambiar tipo de creador
     */
    FlavorEstados.cambiarTipoCreador = function(tipo) {
        this.state.nuevoEstado.tipo = tipo;

        var $editor = $('.crear-estado-editor');
        var $upload = $('.crear-estado-upload');
        var $preview = $('.crear-estado-preview');
        var $colores = $('.crear-estado-colores');

        switch (tipo) {
            case 'texto':
                $editor.show();
                $upload.hide();
                $preview.hide();
                $colores.show();
                break;

            case 'imagen':
            case 'video':
                $editor.hide();
                $colores.hide();
                if (this.state.nuevoEstado.media_url) {
                    $upload.hide();
                    $preview.show();
                } else {
                    $upload.show();
                    $preview.hide();
                }
                break;
        }
    };

    /**
     * Aplicar color
     */
    FlavorEstados.aplicarColor = function(index) {
        var color = this.colores[index];
        this.state.nuevoEstado.color_fondo = color.fondo;
        this.state.nuevoEstado.color_texto = color.texto;

        $('.crear-estado-editor').css({
            background: color.fondo,
            color: color.texto
        });

        $('.crear-estado-editor textarea').css('color', color.texto);
    };

    /**
     * Upload media
     */
    FlavorEstados.uploadMedia = function(file) {
        var self = this;
        var formData = new FormData();
        formData.append('media', file);
        formData.append('action', 'flavor_estados_upload');
        formData.append('nonce', this.config.nonce);

        $('.crear-estado-upload').html('<div class="loading">Subiendo...</div>');

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    self.state.nuevoEstado.media_url = response.data.url;
                    self.state.nuevoEstado.media_thumbnail = response.data.thumbnail;
                    self.state.nuevoEstado.tipo = response.data.type;

                    var $preview = $('.crear-estado-preview');
                    $preview.find('img, video').remove();

                    if (response.data.type === 'video') {
                        $preview.prepend('<video src="' + response.data.url + '" controls></video>');
                    } else {
                        $preview.prepend('<img src="' + response.data.url + '" alt="">');
                    }

                    $preview.show();
                    $('.crear-estado-upload').hide();
                } else {
                    self.showToast(response.data.message || 'Error al subir', 'error');
                    self.cambiarTipoCreador(self.state.nuevoEstado.tipo);
                }
            },
            error: function() {
                self.showToast('Error al subir archivo', 'error');
                self.cambiarTipoCreador(self.state.nuevoEstado.tipo);
            }
        });
    };

    /**
     * Publicar estado
     */
    FlavorEstados.publicarEstado = function() {
        var estado = this.state.nuevoEstado;

        // Validar
        if (estado.tipo === 'texto' && !estado.contenido.trim()) {
            this.showToast('Escribe algo para publicar', 'error');
            return;
        }

        if ((estado.tipo === 'imagen' || estado.tipo === 'video') && !estado.media_url) {
            this.showToast('Selecciona un archivo', 'error');
            return;
        }

        $('.crear-estado-publicar').prop('disabled', true).text('Publicando...');

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_estados_crear',
                nonce: this.config.nonce,
                tipo: estado.tipo,
                contenido: estado.contenido,
                media_url: estado.media_url,
                media_thumbnail: estado.media_thumbnail,
                color_fondo: estado.color_fondo,
                color_texto: estado.color_texto,
                privacidad: estado.privacidad
            },
            success: function(response) {
                if (response.success) {
                    FlavorEstados.showToast(FlavorEstados.config.strings.estadoPublicado, 'success');
                    FlavorEstados.cerrarCreador();
                    FlavorEstados.cargarEstados();
                } else {
                    FlavorEstados.showToast(response.data.message || FlavorEstados.config.strings.errorPublicar, 'error');
                }
            },
            error: function() {
                FlavorEstados.showToast(FlavorEstados.config.strings.errorPublicar, 'error');
            },
            complete: function() {
                $('.crear-estado-publicar').prop('disabled', false).text('Publicar');
            }
        });
    };

    /**
     * Cerrar creador
     */
    FlavorEstados.cerrarCreador = function() {
        $('#crear-estado-overlay').removeClass('active');
        $('body').css('overflow', '');

        // Reset
        $('#crear-estado-overlay textarea').val('');
        $('.crear-estado-preview').hide().find('img, video').remove();
        this.cambiarTipoCreador('texto');
        this.aplicarColor(0);
    };

    /**
     * Eliminar estado
     */
    FlavorEstados.eliminarEstado = function(estadoId) {
        var self = this;

        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_estados_eliminar',
                nonce: this.config.nonce,
                estado_id: estadoId
            },
            success: function(response) {
                if (response.success) {
                    self.showToast('Estado eliminado', 'success');

                    // Si era el último, cerrar
                    if (self.state.currentUser.estados.length <= 1) {
                        self.cerrarVisor();
                    } else {
                        // Eliminar del array y mostrar siguiente
                        self.state.currentUser.estados.splice(self.state.currentIndex, 1);
                        if (self.state.currentIndex >= self.state.currentUser.estados.length) {
                            self.state.currentIndex = self.state.currentUser.estados.length - 1;
                        }
                        self.renderEstadoActual();
                        self.iniciarProgreso();
                    }
                }
            }
        });
    };

    /**
     * Silenciar usuario
     */
    FlavorEstados.silenciarUsuario = function(userId) {
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_estados_silenciar',
                nonce: this.config.nonce,
                usuario_id: userId,
                accion: 'silenciar'
            },
            success: function() {
                FlavorEstados.showToast('Usuario silenciado', 'success');
                FlavorEstados.cerrarVisor();
            }
        });
    };

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Escapar HTML
     */
    FlavorEstados.escapeHtml = function(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    /**
     * Mostrar toast
     */
    FlavorEstados.showToast = function(message, type) {
        type = type || 'info';

        $('.estados-toast').remove();

        var $toast = $('<div class="estados-toast ' + type + '">' + message + '</div>');
        $('body').append($toast);

        setTimeout(function() {
            $toast.addClass('show');
        }, 10);

        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    };

    /**
     * Inicializar cuando DOM esté listo
     */
    $(document).ready(function() {
        if ($('.flavor-estados-container').length || $('[data-flavor-estados]').length) {
            FlavorEstados.init();
        }
    });

})(jQuery);
