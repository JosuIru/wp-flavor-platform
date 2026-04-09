<?php
/**
 * Vista fullscreen para visualizar estados
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Chat_Estados
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
?>

<div class="flavor-estados-fullscreen" data-flavor-estados-fullscreen style="display:none;">
    <!-- Overlay oscuro de fondo -->
    <div class="estados-fullscreen-overlay"></div>

    <!-- Contenedor principal -->
    <div class="estados-fullscreen-container">
        <!-- Header con info del usuario y controles -->
        <div class="estados-fullscreen-header">
            <div class="estados-header-left">
                <button type="button" class="btn-cerrar-fullscreen" aria-label="<?php esc_attr_e('Cerrar', 'flavor-platform'); ?>">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
                <div class="estados-user-info">
                    <img src="" alt="" class="estado-user-avatar">
                    <div class="estado-user-meta">
                        <span class="estado-user-nombre"></span>
                        <span class="estado-tiempo"></span>
                    </div>
                </div>
            </div>
            <div class="estados-header-right">
                <button type="button" class="btn-pausar-estado" aria-label="<?php esc_attr_e('Pausar', 'flavor-platform'); ?>">
                    <span class="dashicons dashicons-controls-pause"></span>
                </button>
                <button type="button" class="btn-silenciar-estado" aria-label="<?php esc_attr_e('Silenciar', 'flavor-platform'); ?>">
                    <span class="dashicons dashicons-controls-volumeon"></span>
                </button>
                <button type="button" class="btn-opciones-estado" aria-label="<?php esc_attr_e('Opciones', 'flavor-platform'); ?>">
                    <span class="dashicons dashicons-ellipsis"></span>
                </button>
            </div>
        </div>

        <!-- Barras de progreso -->
        <div class="estados-progress-container">
            <!-- Se generan dinámicamente por JS -->
        </div>

        <!-- Área de contenido del estado -->
        <div class="estados-content-area">
            <!-- Navegación izquierda (estado anterior) -->
            <div class="estado-nav estado-nav-prev">
                <button type="button" class="btn-estado-prev" aria-label="<?php esc_attr_e('Estado anterior', 'flavor-platform'); ?>">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
            </div>

            <!-- Contenido del estado actual -->
            <div class="estado-contenido-wrapper">
                <div class="estado-contenido">
                    <!-- Contenido dinámico: imagen, video, texto -->
                </div>

                <!-- Texto superpuesto si existe -->
                <div class="estado-texto-overlay"></div>
            </div>

            <!-- Navegación derecha (siguiente estado) -->
            <div class="estado-nav estado-nav-next">
                <button type="button" class="btn-estado-next" aria-label="<?php esc_attr_e('Siguiente estado', 'flavor-platform'); ?>">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>
        </div>

        <!-- Footer con respuesta rápida -->
        <div class="estados-fullscreen-footer">
            <div class="estado-respuesta-wrapper">
                <input type="text" class="estado-respuesta-input"
                       placeholder="<?php esc_attr_e('Enviar mensaje...', 'flavor-platform'); ?>"
                       maxlength="500">
                <button type="button" class="btn-enviar-respuesta" aria-label="<?php esc_attr_e('Enviar', 'flavor-platform'); ?>">
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </button>
            </div>

            <!-- Reacciones rápidas -->
            <div class="estado-reacciones-rapidas">
                <button type="button" class="btn-reaccion" data-emoji="❤️" aria-label="<?php esc_attr_e('Me encanta', 'flavor-platform'); ?>">❤️</button>
                <button type="button" class="btn-reaccion" data-emoji="😂" aria-label="<?php esc_attr_e('Me divierte', 'flavor-platform'); ?>">😂</button>
                <button type="button" class="btn-reaccion" data-emoji="😮" aria-label="<?php esc_attr_e('Me sorprende', 'flavor-platform'); ?>">😮</button>
                <button type="button" class="btn-reaccion" data-emoji="👏" aria-label="<?php esc_attr_e('Aplauso', 'flavor-platform'); ?>">👏</button>
                <button type="button" class="btn-reaccion" data-emoji="🔥" aria-label="<?php esc_attr_e('Fuego', 'flavor-platform'); ?>">🔥</button>
            </div>
        </div>
    </div>

    <!-- Menú de opciones -->
    <div class="estados-menu-opciones" style="display:none;">
        <div class="menu-opciones-overlay"></div>
        <div class="menu-opciones-contenido">
            <button type="button" class="opcion-silenciar">
                <span class="dashicons dashicons-hidden"></span>
                <?php esc_html_e('Silenciar estados de este usuario', 'flavor-platform'); ?>
            </button>
            <button type="button" class="opcion-reportar">
                <span class="dashicons dashicons-flag"></span>
                <?php esc_html_e('Reportar estado', 'flavor-platform'); ?>
            </button>
            <button type="button" class="opcion-cancelar">
                <?php esc_html_e('Cancelar', 'flavor-platform'); ?>
            </button>
        </div>
    </div>
</div>

<style>
/* Variables CSS para estados fullscreen */
:root {
    --estados-fs-bg: #000000;
    --estados-fs-overlay: rgba(0, 0, 0, 0.95);
    --estados-fs-text: #ffffff;
    --estados-fs-muted: rgba(255, 255, 255, 0.7);
    --estados-fs-progress-bg: rgba(255, 255, 255, 0.3);
    --estados-fs-progress-fill: #ffffff;
    --estados-fs-nav-bg: rgba(255, 255, 255, 0.1);
    --estados-fs-input-bg: rgba(255, 255, 255, 0.1);
    --estados-fs-z-index: 999999;
}

/* Contenedor fullscreen */
.flavor-estados-fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: var(--estados-fs-z-index);
    background: var(--estados-fs-bg);
}

.estados-fullscreen-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--estados-fs-overlay);
}

.estados-fullscreen-container {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    max-width: 500px;
    margin: 0 auto;
}

/* Header */
.estados-fullscreen-header {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: linear-gradient(to bottom, rgba(0,0,0,0.7) 0%, transparent 100%);
    z-index: 10;
}

.estados-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.estados-header-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-cerrar-fullscreen,
.btn-pausar-estado,
.btn-silenciar-estado,
.btn-opciones-estado {
    background: none;
    border: none;
    color: var(--estados-fs-text);
    font-size: 20px;
    padding: 8px;
    cursor: pointer;
    opacity: 0.9;
    transition: opacity 0.2s;
}

.btn-cerrar-fullscreen:hover,
.btn-pausar-estado:hover,
.btn-silenciar-estado:hover,
.btn-opciones-estado:hover {
    opacity: 1;
}

.estados-user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.estado-user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--estados-fs-text);
}

.estado-user-meta {
    display: flex;
    flex-direction: column;
}

.estado-user-nombre {
    color: var(--estados-fs-text);
    font-size: 14px;
    font-weight: 600;
}

.estado-tiempo {
    color: var(--estados-fs-muted);
    font-size: 12px;
}

/* Barras de progreso */
.estados-progress-container {
    position: absolute;
    top: 8px;
    left: 16px;
    right: 16px;
    display: flex;
    gap: 4px;
    z-index: 11;
}

.estado-progress-bar {
    flex: 1;
    height: 3px;
    background: var(--estados-fs-progress-bg);
    border-radius: 2px;
    overflow: hidden;
}

.estado-progress-fill {
    height: 100%;
    background: var(--estados-fs-progress-fill);
    width: 0%;
    transition: width 0.1s linear;
}

.estado-progress-bar.completado .estado-progress-fill {
    width: 100%;
}

.estado-progress-bar.activo .estado-progress-fill {
    animation: progress-animation var(--estado-duracion, 5s) linear forwards;
}

@keyframes progress-animation {
    from { width: 0%; }
    to { width: 100%; }
}

/* Área de contenido */
.estados-content-area {
    flex: 1;
    display: flex;
    align-items: stretch;
    position: relative;
}

.estado-nav {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 30%;
    display: flex;
    align-items: center;
    z-index: 5;
}

.estado-nav-prev {
    left: 0;
    justify-content: flex-start;
    padding-left: 8px;
}

.estado-nav-next {
    right: 0;
    justify-content: flex-end;
    padding-right: 8px;
}

.btn-estado-prev,
.btn-estado-next {
    background: var(--estados-fs-nav-bg);
    border: none;
    color: var(--estados-fs-text);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.3s;
}

.estados-content-area:hover .btn-estado-prev,
.estados-content-area:hover .btn-estado-next {
    opacity: 0.8;
}

.btn-estado-prev:hover,
.btn-estado-next:hover {
    opacity: 1 !important;
    background: rgba(255, 255, 255, 0.2);
}

.estado-contenido-wrapper {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.estado-contenido {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.estado-contenido img,
.estado-contenido video {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.estado-contenido.tipo-texto {
    padding: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.estado-contenido.tipo-texto .texto-principal {
    font-size: 24px;
    line-height: 1.4;
    color: var(--estados-fs-text);
    word-wrap: break-word;
}

.estado-texto-overlay {
    position: absolute;
    bottom: 100px;
    left: 20px;
    right: 20px;
    text-align: center;
    color: var(--estados-fs-text);
    font-size: 16px;
    text-shadow: 0 1px 3px rgba(0,0,0,0.8);
    pointer-events: none;
}

/* Footer */
.estados-fullscreen-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 16px;
    background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 100%);
    z-index: 10;
}

.estado-respuesta-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}

.estado-respuesta-input {
    flex: 1;
    background: var(--estados-fs-input-bg);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 24px;
    padding: 10px 16px;
    color: var(--estados-fs-text);
    font-size: 14px;
}

.estado-respuesta-input::placeholder {
    color: var(--estados-fs-muted);
}

.estado-respuesta-input:focus {
    outline: none;
    border-color: rgba(255,255,255,0.4);
}

.btn-enviar-respuesta {
    background: var(--estados-primary, #25D366);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s;
}

.btn-enviar-respuesta:hover {
    transform: scale(1.05);
}

.estado-reacciones-rapidas {
    display: flex;
    justify-content: center;
    gap: 16px;
}

.btn-reaccion {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 4px;
    transition: transform 0.2s;
}

.btn-reaccion:hover {
    transform: scale(1.3);
}

/* Menú de opciones */
.estados-menu-opciones {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: calc(var(--estados-fs-z-index) + 1);
}

.menu-opciones-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.menu-opciones-contenido {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: #1a1a1a;
    border-radius: 16px 16px 0 0;
    padding: 16px;
    animation: slide-up 0.3s ease;
}

@keyframes slide-up {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

.menu-opciones-contenido button {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: none;
    border: none;
    color: var(--estados-fs-text);
    font-size: 15px;
    cursor: pointer;
    border-radius: 8px;
    transition: background 0.2s;
}

.menu-opciones-contenido button:hover {
    background: rgba(255,255,255,0.1);
}

.menu-opciones-contenido .opcion-reportar {
    color: #ff4444;
}

.menu-opciones-contenido .opcion-cancelar {
    margin-top: 8px;
    justify-content: center;
    border-top: 1px solid rgba(255,255,255,0.1);
    padding-top: 16px;
    color: var(--estados-fs-muted);
}

/* Estados propios - Vistos y estadísticas */
.estado-stats-propios {
    position: absolute;
    bottom: 80px;
    left: 0;
    right: 0;
    text-align: center;
    color: var(--estados-fs-muted);
    font-size: 13px;
}

.estado-stats-propios .icono-vistas {
    margin-right: 4px;
}

/* Animaciones de entrada/salida */
.flavor-estados-fullscreen.entrando {
    animation: fade-in 0.3s ease;
}

.flavor-estados-fullscreen.saliendo {
    animation: fade-out 0.3s ease;
}

@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes fade-out {
    from { opacity: 1; }
    to { opacity: 0; }
}

/* Transiciones entre estados */
.estado-contenido.transicion-izq {
    animation: slide-out-left 0.3s ease;
}

.estado-contenido.transicion-der {
    animation: slide-out-right 0.3s ease;
}

.estado-contenido.entrando-izq {
    animation: slide-in-right 0.3s ease;
}

.estado-contenido.entrando-der {
    animation: slide-in-left 0.3s ease;
}

@keyframes slide-out-left {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(-30px); opacity: 0; }
}

@keyframes slide-out-right {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(30px); opacity: 0; }
}

@keyframes slide-in-left {
    from { transform: translateX(-30px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slide-in-right {
    from { transform: translateX(30px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Responsive */
@media (max-width: 480px) {
    .estados-fullscreen-container {
        max-width: 100%;
    }

    .btn-estado-prev,
    .btn-estado-next {
        width: 32px;
        height: 32px;
    }

    .estado-contenido.tipo-texto .texto-principal {
        font-size: 20px;
    }

    .btn-reaccion {
        font-size: 20px;
    }
}

/* Soporte para teclado y accesibilidad */
.flavor-estados-fullscreen:focus-visible {
    outline: 2px solid var(--estados-primary, #25D366);
    outline-offset: -2px;
}

.btn-cerrar-fullscreen:focus-visible,
.btn-estado-prev:focus-visible,
.btn-estado-next:focus-visible {
    outline: 2px solid var(--estados-fs-text);
    outline-offset: 2px;
}

/* Estado pausado */
.flavor-estados-fullscreen.pausado .estado-progress-bar.activo .estado-progress-fill {
    animation-play-state: paused;
}

/* Indicador de carga */
.estado-loading-indicator {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.estado-loading-indicator .spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--estados-fs-progress-bg);
    border-top-color: var(--estados-fs-text);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
</style>

<script>
(function($) {
    'use strict';

    if (typeof window.FlavorEstadosFullscreen !== 'undefined') {
        return;
    }

    window.FlavorEstadosFullscreen = {
        container: null,
        estados: [],
        usuarioActualIndex: 0,
        estadoActualIndex: 0,
        pausado: false,
        silenciado: false,
        timerProgress: null,
        duracionEstado: 5000, // 5 segundos por defecto

        init: function() {
            this.container = $('[data-flavor-estados-fullscreen]');
            if (!this.container.length) return;

            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Cerrar
            this.container.on('click', '.btn-cerrar-fullscreen, .estados-fullscreen-overlay', function() {
                self.cerrar();
            });

            // Navegación
            this.container.on('click', '.btn-estado-prev, .estado-nav-prev', function(e) {
                e.stopPropagation();
                self.estadoAnterior();
            });

            this.container.on('click', '.btn-estado-next, .estado-nav-next', function(e) {
                e.stopPropagation();
                self.estadoSiguiente();
            });

            // Pausar/Reanudar
            this.container.on('click', '.btn-pausar-estado', function() {
                self.togglePausa();
            });

            // Silenciar
            this.container.on('click', '.btn-silenciar-estado', function() {
                self.toggleSilenciar();
            });

            // Opciones
            this.container.on('click', '.btn-opciones-estado', function() {
                self.mostrarOpciones();
            });

            // Menú opciones
            this.container.on('click', '.menu-opciones-overlay, .opcion-cancelar', function() {
                self.cerrarOpciones();
            });

            this.container.on('click', '.opcion-silenciar', function() {
                self.silenciarUsuario();
            });

            this.container.on('click', '.opcion-reportar', function() {
                self.reportarEstado();
            });

            // Enviar respuesta
            this.container.on('click', '.btn-enviar-respuesta', function() {
                self.enviarRespuesta();
            });

            this.container.on('keypress', '.estado-respuesta-input', function(e) {
                if (e.which === 13) {
                    self.enviarRespuesta();
                }
            });

            // Reacciones
            this.container.on('click', '.btn-reaccion', function() {
                var emoji = $(this).data('emoji');
                self.enviarReaccion(emoji);
            });

            // Teclado
            $(document).on('keydown.estadosFullscreen', function(e) {
                if (!self.container.is(':visible')) return;

                switch(e.which) {
                    case 27: // Escape
                        self.cerrar();
                        break;
                    case 37: // Izquierda
                        self.estadoAnterior();
                        break;
                    case 39: // Derecha
                        self.estadoSiguiente();
                        break;
                    case 32: // Espacio
                        e.preventDefault();
                        self.togglePausa();
                        break;
                }
            });

            // Touch/swipe en móvil
            var touchStartX = 0;
            var touchStartY = 0;

            this.container.on('touchstart', '.estado-contenido-wrapper', function(e) {
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
            });

            this.container.on('touchend', '.estado-contenido-wrapper', function(e) {
                var touchEndX = e.changedTouches[0].clientX;
                var touchEndY = e.changedTouches[0].clientY;
                var diffX = touchEndX - touchStartX;
                var diffY = Math.abs(touchEndY - touchStartY);

                // Solo si es swipe horizontal significativo
                if (Math.abs(diffX) > 50 && diffY < 100) {
                    if (diffX > 0) {
                        self.estadoAnterior();
                    } else {
                        self.estadoSiguiente();
                    }
                }
            });

            // Hold para pausar en móvil
            var holdTimer = null;

            this.container.on('touchstart', '.estado-contenido-wrapper', function() {
                holdTimer = setTimeout(function() {
                    self.pausar();
                }, 200);
            });

            this.container.on('touchend touchcancel', '.estado-contenido-wrapper', function() {
                clearTimeout(holdTimer);
                if (self.pausado) {
                    self.reanudar();
                }
            });
        },

        abrir: function(estadosUsuarios, usuarioIndex, estadoIndex) {
            this.estados = estadosUsuarios || [];
            this.usuarioActualIndex = usuarioIndex || 0;
            this.estadoActualIndex = estadoIndex || 0;

            if (this.estados.length === 0) return;

            this.container.addClass('entrando').show();
            $('body').css('overflow', 'hidden');

            setTimeout(function() {
                FlavorEstadosFullscreen.container.removeClass('entrando');
            }, 300);

            this.mostrarEstado();
        },

        cerrar: function() {
            var self = this;
            this.detenerTimer();

            this.container.addClass('saliendo');

            setTimeout(function() {
                self.container.removeClass('saliendo').hide();
                $('body').css('overflow', '');
                $(document).off('keydown.estadosFullscreen');
            }, 300);
        },

        mostrarEstado: function() {
            var usuario = this.estados[this.usuarioActualIndex];
            if (!usuario || !usuario.estados || !usuario.estados.length) {
                this.siguienteUsuario();
                return;
            }

            var estado = usuario.estados[this.estadoActualIndex];
            if (!estado) {
                this.siguienteUsuario();
                return;
            }

            // Actualizar header
            this.container.find('.estado-user-avatar').attr('src', usuario.avatar);
            this.container.find('.estado-user-nombre').text(usuario.nombre);
            this.container.find('.estado-tiempo').text(estado.tiempo_relativo || '');

            // Generar barras de progreso
            this.generarBarrasProgreso(usuario.estados.length);

            // Mostrar contenido
            this.renderizarContenido(estado);

            // Marcar como visto
            this.marcarVisto(estado.id);

            // Iniciar timer
            this.iniciarTimer(estado.duracion || this.duracionEstado);
        },

        generarBarrasProgreso: function(total) {
            var html = '';
            for (var i = 0; i < total; i++) {
                var clase = '';
                if (i < this.estadoActualIndex) {
                    clase = 'completado';
                } else if (i === this.estadoActualIndex) {
                    clase = 'activo';
                }
                html += '<div class="estado-progress-bar ' + clase + '"><div class="estado-progress-fill"></div></div>';
            }
            this.container.find('.estados-progress-container').html(html);
        },

        renderizarContenido: function(estado) {
            var contenedor = this.container.find('.estado-contenido');
            var textoOverlay = this.container.find('.estado-texto-overlay');
            var html = '';

            contenedor.removeClass('tipo-texto tipo-imagen tipo-video');
            textoOverlay.text('').hide();

            switch (estado.tipo) {
                case 'imagen':
                    contenedor.addClass('tipo-imagen');
                    html = '<img src="' + estado.url + '" alt="">';
                    if (estado.texto) {
                        textoOverlay.text(estado.texto).show();
                    }
                    break;

                case 'video':
                    contenedor.addClass('tipo-video');
                    html = '<video src="' + estado.url + '" ' + (this.silenciado ? 'muted' : '') + ' autoplay playsinline></video>';
                    if (estado.texto) {
                        textoOverlay.text(estado.texto).show();
                    }
                    break;

                case 'texto':
                default:
                    contenedor.addClass('tipo-texto');
                    var bgStyle = estado.fondo ? 'background:' + estado.fondo + ';' : '';
                    html = '<div class="texto-principal" style="' + bgStyle + '">' + this.escapeHtml(estado.texto) + '</div>';
                    break;
            }

            contenedor.html(html);

            // Si es video, sincronizar duración
            if (estado.tipo === 'video') {
                var video = contenedor.find('video')[0];
                if (video) {
                    video.onloadedmetadata = function() {
                        FlavorEstadosFullscreen.ajustarDuracionVideo(video.duration);
                    };
                }
            }
        },

        ajustarDuracionVideo: function(duracion) {
            var duracionMs = duracion * 1000;
            this.detenerTimer();

            var barra = this.container.find('.estado-progress-bar.activo');
            barra.css('--estado-duracion', duracion + 's');
            barra.removeClass('activo').addClass('activo');

            this.timerProgress = setTimeout(function() {
                FlavorEstadosFullscreen.estadoSiguiente();
            }, duracionMs);
        },

        iniciarTimer: function(duracion) {
            var self = this;
            this.detenerTimer();

            var barra = this.container.find('.estado-progress-bar.activo');
            barra.css('--estado-duracion', (duracion / 1000) + 's');

            this.timerProgress = setTimeout(function() {
                self.estadoSiguiente();
            }, duracion);
        },

        detenerTimer: function() {
            if (this.timerProgress) {
                clearTimeout(this.timerProgress);
                this.timerProgress = null;
            }
        },

        estadoSiguiente: function() {
            var usuario = this.estados[this.usuarioActualIndex];

            if (this.estadoActualIndex < usuario.estados.length - 1) {
                this.estadoActualIndex++;
                this.animarTransicion('siguiente');
            } else {
                this.siguienteUsuario();
            }
        },

        estadoAnterior: function() {
            if (this.estadoActualIndex > 0) {
                this.estadoActualIndex--;
                this.animarTransicion('anterior');
            } else if (this.usuarioActualIndex > 0) {
                this.usuarioActualIndex--;
                var usuarioAnterior = this.estados[this.usuarioActualIndex];
                this.estadoActualIndex = usuarioAnterior.estados.length - 1;
                this.animarTransicion('anterior');
            }
        },

        siguienteUsuario: function() {
            if (this.usuarioActualIndex < this.estados.length - 1) {
                this.usuarioActualIndex++;
                this.estadoActualIndex = 0;
                this.animarTransicion('siguiente');
            } else {
                this.cerrar();
            }
        },

        animarTransicion: function(direccion) {
            var self = this;
            var contenido = this.container.find('.estado-contenido');

            var claseOut = direccion === 'siguiente' ? 'transicion-izq' : 'transicion-der';
            var claseIn = direccion === 'siguiente' ? 'entrando-izq' : 'entrando-der';

            contenido.addClass(claseOut);

            setTimeout(function() {
                contenido.removeClass(claseOut);
                self.mostrarEstado();
                contenido.addClass(claseIn);

                setTimeout(function() {
                    contenido.removeClass(claseIn);
                }, 300);
            }, 300);
        },

        togglePausa: function() {
            if (this.pausado) {
                this.reanudar();
            } else {
                this.pausar();
            }
        },

        pausar: function() {
            this.pausado = true;
            this.container.addClass('pausado');
            this.container.find('.btn-pausar-estado .dashicons')
                .removeClass('dashicons-controls-pause')
                .addClass('dashicons-controls-play');

            var video = this.container.find('video')[0];
            if (video) video.pause();
        },

        reanudar: function() {
            this.pausado = false;
            this.container.removeClass('pausado');
            this.container.find('.btn-pausar-estado .dashicons')
                .removeClass('dashicons-controls-play')
                .addClass('dashicons-controls-pause');

            var video = this.container.find('video')[0];
            if (video) video.play();
        },

        toggleSilenciar: function() {
            this.silenciado = !this.silenciado;

            var icono = this.container.find('.btn-silenciar-estado .dashicons');
            if (this.silenciado) {
                icono.removeClass('dashicons-controls-volumeon').addClass('dashicons-controls-volumeoff');
            } else {
                icono.removeClass('dashicons-controls-volumeoff').addClass('dashicons-controls-volumeon');
            }

            var video = this.container.find('video')[0];
            if (video) video.muted = this.silenciado;
        },

        mostrarOpciones: function() {
            this.pausar();
            this.container.find('.estados-menu-opciones').show();
        },

        cerrarOpciones: function() {
            this.container.find('.estados-menu-opciones').hide();
            this.reanudar();
        },

        silenciarUsuario: function() {
            var usuario = this.estados[this.usuarioActualIndex];

            $.post(flavorEstados.ajaxUrl, {
                action: 'flavor_estados_silenciar',
                nonce: flavorEstados.nonce,
                usuario_id: usuario.user_id
            }, function(response) {
                if (response.success) {
                    FlavorEstadosFullscreen.cerrarOpciones();
                    FlavorEstadosFullscreen.siguienteUsuario();
                }
            });
        },

        reportarEstado: function() {
            var estado = this.estados[this.usuarioActualIndex].estados[this.estadoActualIndex];

            if (confirm('<?php echo esc_js(__('¿Estás seguro de que quieres reportar este estado?', 'flavor-platform')); ?>')) {
                $.post(flavorEstados.ajaxUrl, {
                    action: 'flavor_estados_reportar',
                    nonce: flavorEstados.nonce,
                    estado_id: estado.id
                }, function(response) {
                    if (response.success) {
                        alert('<?php echo esc_js(__('Estado reportado. Gracias por tu colaboración.', 'flavor-platform')); ?>');
                        FlavorEstadosFullscreen.cerrarOpciones();
                    }
                });
            }
        },

        enviarRespuesta: function() {
            var input = this.container.find('.estado-respuesta-input');
            var mensaje = input.val().trim();

            if (!mensaje) return;

            var estado = this.estados[this.usuarioActualIndex].estados[this.estadoActualIndex];
            var usuario = this.estados[this.usuarioActualIndex];

            $.post(flavorEstados.ajaxUrl, {
                action: 'flavor_estados_responder',
                nonce: flavorEstados.nonce,
                estado_id: estado.id,
                mensaje: mensaje
            }, function(response) {
                if (response.success) {
                    input.val('');
                    FlavorEstadosFullscreen.mostrarFeedback('<?php echo esc_js(__('Mensaje enviado', 'flavor-platform')); ?>');
                }
            });
        },

        enviarReaccion: function(emoji) {
            var estado = this.estados[this.usuarioActualIndex].estados[this.estadoActualIndex];

            $.post(flavorEstados.ajaxUrl, {
                action: 'flavor_estados_reaccionar',
                nonce: flavorEstados.nonce,
                estado_id: estado.id,
                emoji: emoji
            }, function(response) {
                if (response.success) {
                    FlavorEstadosFullscreen.mostrarFeedback(emoji + ' <?php echo esc_js(__('enviado', 'flavor-platform')); ?>');
                }
            });
        },

        mostrarFeedback: function(mensaje) {
            var feedback = $('<div class="estado-feedback-toast">' + mensaje + '</div>');
            this.container.append(feedback);

            setTimeout(function() {
                feedback.addClass('visible');
            }, 10);

            setTimeout(function() {
                feedback.removeClass('visible');
                setTimeout(function() {
                    feedback.remove();
                }, 300);
            }, 2000);
        },

        marcarVisto: function(estadoId) {
            $.post(flavorEstados.ajaxUrl, {
                action: 'flavor_estados_ver',
                nonce: flavorEstados.nonce,
                estado_id: estadoId
            });
        },

        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        FlavorEstadosFullscreen.init();
    });

})(jQuery);
</script>

<!-- Toast de feedback -->
<style>
.estado-feedback-toast {
    position: fixed;
    bottom: 100px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 10px 20px;
    border-radius: 20px;
    font-size: 14px;
    opacity: 0;
    transition: all 0.3s ease;
    z-index: calc(var(--estados-fs-z-index) + 2);
}

.estado-feedback-toast.visible {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
</style>
