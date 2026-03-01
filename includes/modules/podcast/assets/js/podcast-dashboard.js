/**
 * JavaScript para el Dashboard Tab de Podcast
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

(function($) {
    'use strict';

    /**
     * Configuracion global
     */
    const CONFIG = window.flavorPodcastDashboard || {
        ajaxUrl: '/wp-admin/admin-ajax.php',
        nonce: '',
        strings: {}
    };

    /**
     * Estado del reproductor de audio
     */
    let audioElement = null;
    let currentPlayingElement = null;

    /**
     * Inicializacion principal
     */
    function init() {
        bindEvents();
        initAudioPlayer();
    }

    /**
     * Vincula todos los eventos
     */
    function bindEvents() {
        // Eventos de suscripciones
        $(document).on('click', '.flavor-btn-cancelar-suscripcion', handleCancelarSuscripcion);

        // Eventos de historial
        $(document).on('click', '.flavor-btn-eliminar-historial', handleEliminarHistorial);
        $(document).on('click', '.flavor-btn-limpiar-historial', handleLimpiarHistorial);
        $(document).on('click', '.flavor-btn-continuar', handleContinuarReproduccion);

        // Eventos de favoritos
        $(document).on('click', '.flavor-btn-quitar-favorito', handleToggleFavorito);

        // Eventos de descargas
        $(document).on('click', '.flavor-btn-descargar', handleDescargar);
        $(document).on('click', '.flavor-btn-eliminar-descarga', handleEliminarDescarga);

        // Eventos del mini player
        $(document).on('click', '.flavor-mini-player-btn-play', handlePlayPause);
    }

    /**
     * Inicializa el reproductor de audio
     */
    function initAudioPlayer() {
        audioElement = new Audio();

        audioElement.addEventListener('play', function() {
            if (currentPlayingElement) {
                currentPlayingElement.closest('.flavor-mini-player').addClass('is-playing');
            }
        });

        audioElement.addEventListener('pause', function() {
            if (currentPlayingElement) {
                currentPlayingElement.closest('.flavor-mini-player').removeClass('is-playing');
            }
        });

        audioElement.addEventListener('ended', function() {
            if (currentPlayingElement) {
                currentPlayingElement.closest('.flavor-mini-player').removeClass('is-playing');
            }
            currentPlayingElement = null;
        });

        audioElement.addEventListener('error', function() {
            showNotification(CONFIG.strings.error || 'Error al cargar el audio', 'error');
            if (currentPlayingElement) {
                currentPlayingElement.closest('.flavor-mini-player').removeClass('is-playing');
            }
        });
    }

    /**
     * Maneja play/pause del mini player
     */
    function handlePlayPause(event) {
        event.preventDefault();
        const button = $(this);
        const miniPlayer = button.closest('.flavor-mini-player');
        const audioUrl = miniPlayer.data('audio-url');
        const episodioId = miniPlayer.data('episodio-id');

        if (!audioUrl) {
            showNotification(CONFIG.strings.error || 'URL de audio no disponible', 'error');
            return;
        }

        // Si es el mismo audio, toggle play/pause
        if (currentPlayingElement && currentPlayingElement.is(button)) {
            if (audioElement.paused) {
                audioElement.play();
            } else {
                audioElement.pause();
            }
            return;
        }

        // Pausar audio anterior si existe
        if (currentPlayingElement) {
            currentPlayingElement.closest('.flavor-mini-player').removeClass('is-playing');
        }

        // Reproducir nuevo audio
        currentPlayingElement = button;
        audioElement.src = audioUrl;
        audioElement.play();

        // Registrar reproduccion via AJAX
        registrarReproduccion(episodioId);
    }

    /**
     * Maneja continuar reproduccion desde posicion
     */
    function handleContinuarReproduccion(event) {
        event.preventDefault();
        const button = $(this);
        const episodioId = button.data('episodio-id');
        const posicion = button.data('posicion') || 0;
        const item = button.closest('.flavor-historial-item');
        const miniPlayer = item.find('.flavor-mini-player');
        const audioUrl = miniPlayer.data('audio-url');

        if (!audioUrl) {
            showNotification(CONFIG.strings.error || 'URL de audio no disponible', 'error');
            return;
        }

        // Pausar audio anterior si existe
        if (currentPlayingElement) {
            currentPlayingElement.closest('.flavor-mini-player').removeClass('is-playing');
        }

        // Reproducir desde posicion
        currentPlayingElement = miniPlayer.find('.flavor-mini-player-btn-play');
        audioElement.src = audioUrl;
        audioElement.currentTime = posicion;
        audioElement.play();

        registrarReproduccion(episodioId);
    }

    /**
     * Maneja cancelar suscripcion
     */
    function handleCancelarSuscripcion(event) {
        event.preventDefault();

        if (!confirm(CONFIG.strings.confirmCancelar || 'Cancelar esta suscripcion?')) {
            return;
        }

        const button = $(this);
        const serieId = button.data('serie-id');
        const card = button.closest('.flavor-suscripcion-card');

        card.addClass('flavor-loading');

        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_podcast_cancelar_suscripcion',
                nonce: CONFIG.nonce,
                serie_id: serieId
            },
            success: function(response) {
                if (response.success) {
                    card.addClass('flavor-removing');
                    setTimeout(function() {
                        card.remove();
                        actualizarContador('.flavor-suscripciones-tab');
                    }, 300);
                    showNotification(response.data.message, 'success');
                } else {
                    card.removeClass('flavor-loading');
                    showNotification(response.data.message || CONFIG.strings.error, 'error');
                }
            },
            error: function() {
                card.removeClass('flavor-loading');
                showNotification(CONFIG.strings.error || 'Error de conexion', 'error');
            }
        });
    }

    /**
     * Maneja eliminar del historial
     */
    function handleEliminarHistorial(event) {
        event.preventDefault();

        if (!confirm(CONFIG.strings.confirmEliminar || 'Eliminar este elemento?')) {
            return;
        }

        const button = $(this);
        const reproduccionId = button.data('reproduccion-id');
        const item = button.closest('.flavor-historial-item');

        item.addClass('flavor-loading');

        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_podcast_eliminar_historial',
                nonce: CONFIG.nonce,
                reproduccion_id: reproduccionId
            },
            success: function(response) {
                if (response.success) {
                    item.addClass('flavor-removing');
                    setTimeout(function() {
                        item.remove();
                        verificarListaVacia('.flavor-historial-lista', '.flavor-historial-tab');
                    }, 300);
                    showNotification(response.data.message || CONFIG.strings.eliminado, 'success');
                } else {
                    item.removeClass('flavor-loading');
                    showNotification(response.data.message || CONFIG.strings.error, 'error');
                }
            },
            error: function() {
                item.removeClass('flavor-loading');
                showNotification(CONFIG.strings.error || 'Error de conexion', 'error');
            }
        });
    }

    /**
     * Maneja limpiar todo el historial
     */
    function handleLimpiarHistorial(event) {
        event.preventDefault();

        if (!confirm('Eliminar todo el historial? Esta accion no se puede deshacer.')) {
            return;
        }

        const button = $(this);
        const lista = $('.flavor-historial-lista');

        lista.addClass('flavor-loading');

        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_podcast_eliminar_historial',
                nonce: CONFIG.nonce,
                limpiar_todo: 'true'
            },
            success: function(response) {
                if (response.success) {
                    lista.find('.flavor-historial-item').addClass('flavor-removing');
                    setTimeout(function() {
                        lista.empty();
                        button.remove();
                        mostrarEstadoVacio('.flavor-historial-tab', 'backup', 'Sin historial', 'Los episodios que escuches apareceran aqui.');
                    }, 300);
                    showNotification(response.data.message, 'success');
                } else {
                    lista.removeClass('flavor-loading');
                    showNotification(response.data.message || CONFIG.strings.error, 'error');
                }
            },
            error: function() {
                lista.removeClass('flavor-loading');
                showNotification(CONFIG.strings.error || 'Error de conexion', 'error');
            }
        });
    }

    /**
     * Maneja toggle de favorito
     */
    function handleToggleFavorito(event) {
        event.preventDefault();

        const button = $(this);
        const episodioId = button.data('episodio-id');
        const card = button.closest('.flavor-favorito-card');

        button.addClass('flavor-loading');

        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_podcast_toggle_favorito',
                nonce: CONFIG.nonce,
                episodio_id: episodioId
            },
            success: function(response) {
                button.removeClass('flavor-loading');

                if (response.success) {
                    if (response.data.action === 'removed') {
                        card.addClass('flavor-removing');
                        setTimeout(function() {
                            card.remove();
                            actualizarContador('.flavor-favoritos-tab');
                            verificarListaVacia('.flavor-favoritos-grid', '.flavor-favoritos-tab');
                        }, 300);
                    }
                    showNotification(response.data.message, 'success');
                } else {
                    showNotification(response.data.message || CONFIG.strings.error, 'error');
                }
            },
            error: function() {
                button.removeClass('flavor-loading');
                showNotification(CONFIG.strings.error || 'Error de conexion', 'error');
            }
        });
    }

    /**
     * Maneja descarga de episodio
     */
    function handleDescargar(event) {
        event.preventDefault();

        const button = $(this);
        const episodioId = button.data('episodio-id');
        const url = button.data('url');

        if (!url) {
            showNotification(CONFIG.strings.error || 'URL no disponible', 'error');
            return;
        }

        // Registrar descarga
        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_podcast_registrar_descarga',
                nonce: CONFIG.nonce,
                episodio_id: episodioId,
                dispositivo: detectarDispositivo()
            }
        });

        // Iniciar descarga
        const link = document.createElement('a');
        link.href = url;
        link.download = '';
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showNotification(CONFIG.strings.descargaIniciada || 'Descarga iniciada', 'success');
    }

    /**
     * Maneja eliminar descarga
     */
    function handleEliminarDescarga(event) {
        event.preventDefault();

        if (!confirm(CONFIG.strings.confirmEliminar || 'Eliminar esta descarga?')) {
            return;
        }

        const button = $(this);
        const descargaId = button.data('descarga-id');
        const item = button.closest('.flavor-descarga-item');

        item.addClass('flavor-loading');

        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_podcast_eliminar_descarga',
                nonce: CONFIG.nonce,
                descarga_id: descargaId
            },
            success: function(response) {
                if (response.success) {
                    item.addClass('flavor-removing');
                    setTimeout(function() {
                        item.remove();
                        actualizarContadorDescargas();
                        verificarListaVacia('.flavor-descargas-lista', '.flavor-descargas-tab');
                    }, 300);
                    showNotification(response.data.message || CONFIG.strings.eliminado, 'success');
                } else {
                    item.removeClass('flavor-loading');
                    showNotification(response.data.message || CONFIG.strings.error, 'error');
                }
            },
            error: function() {
                item.removeClass('flavor-loading');
                showNotification(CONFIG.strings.error || 'Error de conexion', 'error');
            }
        });
    }

    /**
     * Registra reproduccion via AJAX
     */
    function registrarReproduccion(episodioId) {
        if (!episodioId) return;

        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_podcast_registrar_reproduccion',
                nonce: CONFIG.nonce,
                episodio_id: episodioId
            }
        });
    }

    /**
     * Actualiza contador en el badge
     */
    function actualizarContador(tabSelector) {
        const tab = $(tabSelector);
        const badge = tab.find('.flavor-badge-count');
        const items = tab.find('.flavor-suscripcion-card, .flavor-favorito-card, .flavor-descarga-item').not('.flavor-removing');

        if (badge.length) {
            badge.text(items.length);
        }
    }

    /**
     * Actualiza contador de descargas con espacio
     */
    function actualizarContadorDescargas() {
        actualizarContador('.flavor-descargas-tab');
        // El espacio total se recalcularia en el servidor
    }

    /**
     * Verifica si la lista esta vacia y muestra estado vacio
     */
    function verificarListaVacia(listaSelector, tabSelector) {
        const lista = $(listaSelector);
        const items = lista.children().not('.flavor-removing');

        if (items.length === 0) {
            const tab = $(tabSelector);
            const iconClass = getIconForTab(tabSelector);
            const titulo = getTituloVacioForTab(tabSelector);
            const mensaje = getMensajeVacioForTab(tabSelector);

            mostrarEstadoVacio(tabSelector, iconClass, titulo, mensaje);
        }
    }

    /**
     * Muestra estado vacio en un tab
     */
    function mostrarEstadoVacio(tabSelector, iconClass, titulo, mensaje) {
        const tab = $(tabSelector);
        const contenido = tab.find('.flavor-historial-lista, .flavor-favoritos-grid, .flavor-descargas-lista, .flavor-suscripciones-grid');

        if (contenido.length) {
            contenido.replaceWith(`
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-${iconClass}"></span>
                    <h3>${titulo}</h3>
                    <p>${mensaje}</p>
                </div>
            `);
        }
    }

    /**
     * Obtiene icono segun el tab
     */
    function getIconForTab(tabSelector) {
        const icons = {
            '.flavor-suscripciones-tab': 'rss',
            '.flavor-historial-tab': 'backup',
            '.flavor-favoritos-tab': 'heart',
            '.flavor-descargas-tab': 'download'
        };
        return icons[tabSelector] || 'info';
    }

    /**
     * Obtiene titulo vacio segun el tab
     */
    function getTituloVacioForTab(tabSelector) {
        const titulos = {
            '.flavor-suscripciones-tab': 'No tienes suscripciones',
            '.flavor-historial-tab': 'Sin historial',
            '.flavor-favoritos-tab': 'Sin favoritos',
            '.flavor-descargas-tab': 'Sin descargas'
        };
        return titulos[tabSelector] || 'Sin elementos';
    }

    /**
     * Obtiene mensaje vacio segun el tab
     */
    function getMensajeVacioForTab(tabSelector) {
        const mensajes = {
            '.flavor-suscripciones-tab': 'Explora nuestro catalogo y suscribete a las series que te interesen.',
            '.flavor-historial-tab': 'Los episodios que escuches apareceran aqui.',
            '.flavor-favoritos-tab': 'Marca episodios como favoritos para encontrarlos facilmente.',
            '.flavor-descargas-tab': 'Descarga episodios para escucharlos sin conexion.'
        };
        return mensajes[tabSelector] || 'No hay elementos para mostrar.';
    }

    /**
     * Detecta el tipo de dispositivo
     */
    function detectarDispositivo() {
        const ua = navigator.userAgent;

        if (/android/i.test(ua)) {
            return 'Android';
        }
        if (/iPad|iPhone|iPod/.test(ua)) {
            return 'iOS';
        }
        if (/Windows/.test(ua)) {
            return 'Windows';
        }
        if (/Mac/.test(ua)) {
            return 'Mac';
        }
        if (/Linux/.test(ua)) {
            return 'Linux';
        }

        return 'Desconocido';
    }

    /**
     * Muestra una notificacion al usuario
     */
    function showNotification(message, type) {
        type = type || 'info';

        // Remover notificaciones anteriores
        $('.flavor-notification').remove();

        const iconos = {
            success: 'yes-alt',
            error: 'warning',
            info: 'info'
        };

        const notification = $(`
            <div class="flavor-notification flavor-notification-${type}">
                <span class="dashicons dashicons-${iconos[type] || 'info'}"></span>
                <span class="flavor-notification-text">${message}</span>
            </div>
        `);

        $('body').append(notification);

        // Animar entrada
        setTimeout(function() {
            notification.addClass('flavor-notification-visible');
        }, 10);

        // Auto-ocultar
        setTimeout(function() {
            notification.removeClass('flavor-notification-visible');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Estilos inline para notificaciones
     */
    function addNotificationStyles() {
        if ($('#flavor-notification-styles').length) return;

        $('head').append(`
            <style id="flavor-notification-styles">
                .flavor-notification {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 14px 20px;
                    background: #1f2937;
                    color: #fff;
                    border-radius: 10px;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                    z-index: 999999;
                    transform: translateY(100px);
                    opacity: 0;
                    transition: all 0.3s ease;
                }
                .flavor-notification-visible {
                    transform: translateY(0);
                    opacity: 1;
                }
                .flavor-notification-success {
                    background: #059669;
                }
                .flavor-notification-error {
                    background: #dc2626;
                }
                .flavor-notification .dashicons {
                    font-size: 20px;
                    width: 20px;
                    height: 20px;
                }
            </style>
        `);
    }

    // Inicializar cuando el DOM este listo
    $(document).ready(function() {
        addNotificationStyles();
        init();
    });

})(jQuery);
