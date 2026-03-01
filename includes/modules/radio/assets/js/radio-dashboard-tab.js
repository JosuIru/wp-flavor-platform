/**
 * JavaScript para Radio Dashboard Tabs
 *
 * @package FlavorChatIA
 * @subpackage Radio
 */

(function($) {
    'use strict';

    /**
     * Radio Dashboard Tab Controller
     */
    const RadioDashboardTab = {

        // Audio player instance
        audioPlayer: null,

        // Estado del player
        isPlaying: false,

        // Intervalo para actualizar oyentes
        oyentesInterval: null,

        /**
         * Inicializar
         */
        init: function() {
            this.bindEvents();
            this.initMiniPlayer();
            this.startOyentesUpdate();
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Toggle favorito
            $(document).on('click', '.btn-favorito', this.handleToggleFavorito.bind(this));

            // Escuchar programa
            $(document).on('click', '.btn-escuchar', this.handleEscucharPrograma.bind(this));

            // Mini player controls
            $(document).on('click', '#mini-player-toggle', this.handleTogglePlay.bind(this));
            $(document).on('input', '#mini-player-volume', this.handleVolumeChange.bind(this));
        },

        /**
         * Inicializar mini player
         */
        initMiniPlayer: function() {
            const $playBtn = $('#mini-player-toggle');

            if (!$playBtn.length) {
                return;
            }

            const streamUrl = $playBtn.data('stream');

            if (!streamUrl) {
                return;
            }

            // Crear instancia de Audio
            this.audioPlayer = new Audio();
            this.audioPlayer.preload = 'none';
            this.audioPlayer.src = streamUrl;

            // Eventos del audio
            this.audioPlayer.addEventListener('playing', () => {
                this.isPlaying = true;
                this.updatePlayButton(true);
            });

            this.audioPlayer.addEventListener('pause', () => {
                this.isPlaying = false;
                this.updatePlayButton(false);
            });

            this.audioPlayer.addEventListener('error', (event) => {
                console.error('Error de audio:', event);
                this.isPlaying = false;
                this.updatePlayButton(false);
                this.showNotification(flavorRadioDashboard.strings.error, 'error');
            });

            // Establecer volumen inicial
            const savedVolume = localStorage.getItem('flavor_radio_volume');
            const volumeSlider = document.getElementById('mini-player-volume');

            if (volumeSlider) {
                const volume = savedVolume ? parseInt(savedVolume) : 80;
                volumeSlider.value = volume;
                this.audioPlayer.volume = volume / 100;
            }
        },

        /**
         * Toggle play/pause
         */
        handleTogglePlay: function(event) {
            event.preventDefault();

            if (!this.audioPlayer) {
                return;
            }

            if (this.isPlaying) {
                this.audioPlayer.pause();
            } else {
                const playPromise = this.audioPlayer.play();

                if (playPromise !== undefined) {
                    playPromise.catch(error => {
                        console.error('Error al reproducir:', error);
                        this.showNotification(flavorRadioDashboard.strings.error, 'error');
                    });
                }
            }
        },

        /**
         * Actualizar botón de play
         */
        updatePlayButton: function(playing) {
            const $btn = $('#mini-player-toggle');
            const $icon = $btn.find('.dashicons');

            if (playing) {
                $btn.addClass('playing');
                $icon.removeClass('dashicons-controls-play').addClass('dashicons-controls-pause');
            } else {
                $btn.removeClass('playing');
                $icon.removeClass('dashicons-controls-pause').addClass('dashicons-controls-play');
            }
        },

        /**
         * Cambiar volumen
         */
        handleVolumeChange: function(event) {
            const volume = parseInt(event.target.value);

            if (this.audioPlayer) {
                this.audioPlayer.volume = volume / 100;
            }

            localStorage.setItem('flavor_radio_volume', volume);
        },

        /**
         * Escuchar programa específico
         */
        handleEscucharPrograma: function(event) {
            event.preventDefault();

            // Simplemente iniciar el stream general
            if (!this.isPlaying && this.audioPlayer) {
                $('#mini-player-toggle').trigger('click');
            }
        },

        /**
         * Toggle favorito
         */
        handleToggleFavorito: function(event) {
            event.preventDefault();

            const $btn = $(event.currentTarget);
            const programaId = $btn.data('programa-id');

            if (!programaId) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: flavorRadioDashboard.ajaxurl,
                type: 'POST',
                data: {
                    action: 'flavor_radio_toggle_favorito',
                    nonce: flavorRadioDashboard.nonce,
                    programa_id: programaId
                },
                success: (response) => {
                    if (response.success) {
                        if (response.data.es_favorito) {
                            $btn.addClass('active');
                            this.showNotification(flavorRadioDashboard.strings.added_favorite, 'success');
                        } else {
                            $btn.removeClass('active');
                            // Remover la card si estamos en la vista de favoritos
                            const $card = $btn.closest('.programa-card');
                            if ($card.length) {
                                $card.fadeOut(300, function() {
                                    $(this).remove();
                                    // Verificar si quedan programas
                                    if ($('.programas-favoritos-grid .programa-card').length === 0) {
                                        RadioDashboardTab.showEmptyState();
                                    }
                                });
                            }
                            this.showNotification(flavorRadioDashboard.strings.removed_favorite, 'success');
                        }

                        // Actualizar contador
                        this.updateFavoritosCount(response.data.total_favoritos);
                    } else {
                        this.showNotification(response.data.mensaje || 'Error', 'error');
                    }
                },
                error: () => {
                    this.showNotification(flavorRadioDashboard.strings.error, 'error');
                },
                complete: () => {
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Mostrar empty state cuando no hay favoritos
         */
        showEmptyState: function() {
            const $grid = $('.programas-favoritos-grid');

            if ($grid.length) {
                $grid.replaceWith(`
                    <div class="empty-state">
                        <span class="empty-icon dashicons dashicons-format-audio"></span>
                        <h4>No tienes programas favoritos</h4>
                        <p>Explora nuestra programación y marca tus programas favoritos para seguirlos.</p>
                        <a href="${window.location.origin}/radio/" class="btn btn-primary">
                            <span class="dashicons dashicons-microphone"></span>
                            Explorar Programas
                        </a>
                    </div>
                `);
            }
        },

        /**
         * Actualizar contador de favoritos
         */
        updateFavoritosCount: function(count) {
            const $badge = $('[data-tab="radio-mis-programas"] .tab-badge');

            if ($badge.length) {
                if (count > 0) {
                    $badge.text(count).show();
                } else {
                    $badge.hide();
                }
            }

            // Actualizar stats si existen
            const $statNumero = $('.programas-stats .stat-numero').first();
            if ($statNumero.length) {
                $statNumero.text(count);
            }
        },

        /**
         * Iniciar actualización de oyentes
         */
        startOyentesUpdate: function() {
            const $oyentes = $('#mini-player-oyentes');

            if (!$oyentes.length) {
                return;
            }

            // Actualizar inmediatamente
            this.updateOyentes();

            // Actualizar cada 30 segundos
            this.oyentesInterval = setInterval(() => {
                this.updateOyentes();
            }, 30000);
        },

        /**
         * Actualizar contador de oyentes
         */
        updateOyentes: function() {
            const $count = $('#mini-player-oyentes .oyentes-count');

            if (!$count.length) {
                return;
            }

            $.ajax({
                url: flavorRadioDashboard.ajaxurl,
                type: 'GET',
                data: {
                    action: 'flavor_radio_reportar_oyente',
                    session_id: this.getSessionId()
                },
                success: (response) => {
                    if (response.success && response.data && response.data.oyentes !== undefined) {
                        $count.text(response.data.oyentes);
                    }
                }
            });
        },

        /**
         * Obtener session ID para oyentes
         */
        getSessionId: function() {
            let sessionId = localStorage.getItem('flavor_radio_session');

            if (!sessionId) {
                sessionId = 'radio_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
                localStorage.setItem('flavor_radio_session', sessionId);
            }

            return sessionId;
        },

        /**
         * Mostrar notificación
         */
        showNotification: function(message, type) {
            type = type || 'info';

            // Remover notificaciones existentes
            $('.radio-notification').remove();

            const $notification = $(`
                <div class="radio-notification radio-notification-${type}">
                    <span class="notification-message">${message}</span>
                    <button type="button" class="notification-close">&times;</button>
                </div>
            `);

            $('body').append($notification);

            // Mostrar con animación
            setTimeout(() => {
                $notification.addClass('show');
            }, 10);

            // Auto-ocultar después de 3 segundos
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            }, 3000);

            // Cerrar manualmente
            $notification.find('.notification-close').on('click', function() {
                $notification.removeClass('show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            });
        },

        /**
         * Destruir (cleanup)
         */
        destroy: function() {
            if (this.oyentesInterval) {
                clearInterval(this.oyentesInterval);
            }

            if (this.audioPlayer) {
                this.audioPlayer.pause();
                this.audioPlayer = null;
            }
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        // Verificar que estamos en una página con tabs de radio
        if ($('.flavor-dashboard-tab.radio-mis-programas, .flavor-dashboard-tab.radio-mis-dedicatorias, .flavor-dashboard-tab.radio-mis-propuestas').length > 0) {
            RadioDashboardTab.init();
        }

        // También inicializar si existe el mini player
        if ($('#radio-mini-player').length > 0) {
            RadioDashboardTab.init();
        }
    });

    // Cleanup al salir de la página
    $(window).on('beforeunload', function() {
        RadioDashboardTab.destroy();
    });

    // Exponer globalmente para debug
    window.RadioDashboardTab = RadioDashboardTab;

})(jQuery);

/**
 * Estilos para notificaciones (inline para evitar dependencia de CSS adicional)
 */
(function() {
    const style = document.createElement('style');
    style.textContent = `
        .radio-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 40px 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            z-index: 10000;
            transform: translateY(100%);
            opacity: 0;
            transition: all 0.3s ease;
            max-width: 350px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .radio-notification.show {
            transform: translateY(0);
            opacity: 1;
        }

        .radio-notification-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .radio-notification-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .radio-notification-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #3b82f6;
        }

        .notification-close {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.7;
            line-height: 1;
            padding: 0;
            width: 24px;
            height: 24px;
        }

        .notification-close:hover {
            opacity: 1;
        }
    `;
    document.head.appendChild(style);
})();
