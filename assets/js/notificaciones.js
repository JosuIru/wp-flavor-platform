/**
 * Sistema de Notificaciones - JavaScript
 *
 * Integrado con Flavor_Notification_Manager
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    /**
     * Configuración
     */
    const config = window.flavorNotificacionesConfig || {
        ajaxUrl: '/wp-admin/admin-ajax.php',
        restUrl: '/wp-json/flavor-notifications/v1',
        nonce: '',
        restNonce: '',
        strings: {
            marcarLeida: 'Marcar como leída',
            marcarTodas: 'Marcar todas como leídas',
            sinNotificaciones: 'No tienes notificaciones',
            verTodas: 'Ver todas',
            hace: 'hace',
            cargando: 'Cargando...'
        },
        pollInterval: 60000
    };

    /**
     * Estado de la aplicación
     */
    let state = {
        isLoading: false,
        lastUpdate: null,
        pollTimer: null,
        dropdownOpen: false
    };

    /**
     * Inicialización
     */
    function init() {
        bindEvents();
        startPolling();
    }

    /**
     * Vincular eventos
     */
    function bindEvents() {
        // Toggle dropdown
        $(document).on('click', '.flavor-notificaciones-trigger', toggleDropdown);

        // Cerrar dropdown al hacer clic fuera
        $(document).on('click', closeDropdownOutside);

        // Marcar una notificación como leída
        $(document).on('click', '.flavor-btn-marcar-leida', marcarLeida);

        // Marcar todas como leídas
        $(document).on('click', '.flavor-btn-marcar-todas', marcarTodasLeidas);

        // Cerrar con ESC
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && state.dropdownOpen) {
                closeDropdown();
            }
        });

        // Click en notificación con enlace marca como leída
        $(document).on('click', '.flavor-notificacion a.flavor-notificacion-titulo', function(e) {
            const $notificacion = $(this).closest('.flavor-notificacion');
            if ($notificacion.hasClass('no-leida')) {
                const id = $notificacion.data('id');
                marcarLeidaSilencioso(id);
            }
        });
    }

    /**
     * Toggle del dropdown
     */
    function toggleDropdown(e) {
        e.preventDefault();
        e.stopPropagation();

        const $wrapper = $(this).closest('.flavor-notificaciones-badge-wrapper');
        const $dropdown = $wrapper.find('.flavor-notificaciones-dropdown');

        if (state.dropdownOpen) {
            closeDropdown();
        } else {
            openDropdown($dropdown);
        }
    }

    /**
     * Abrir dropdown
     */
    function openDropdown($dropdown) {
        state.dropdownOpen = true;
        $dropdown.stop().slideDown(200);
        actualizarNotificaciones();
    }

    /**
     * Cerrar dropdown
     */
    function closeDropdown() {
        state.dropdownOpen = false;
        $('.flavor-notificaciones-dropdown').stop().slideUp(200);
    }

    /**
     * Cerrar dropdown al hacer clic fuera
     */
    function closeDropdownOutside(e) {
        if (state.dropdownOpen) {
            const $target = $(e.target);
            if (!$target.closest('.flavor-notificaciones-badge-wrapper').length) {
                closeDropdown();
            }
        }
    }

    /**
     * Marcar una notificación como leída
     */
    function marcarLeida(e) {
        e.preventDefault();
        e.stopPropagation();

        const $btn = $(this);
        const $notificacion = $btn.closest('.flavor-notificacion');
        const id = $btn.data('id') || $notificacion.data('id');

        if (!id || state.isLoading) {
            return;
        }

        $notificacion.addClass('marcando-leida');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_mark_notification_read',
                nonce: config.nonce,
                notification_id: id
            },
            success: function(response) {
                if (response.success) {
                    $notificacion
                        .removeClass('no-leida marcando-leida')
                        .find('.flavor-btn-marcar-leida')
                        .remove();

                    if (response.data && response.data.unread_count !== undefined) {
                        actualizarContador(response.data.unread_count, true);
                    } else {
                        actualizarContador(-1);
                    }
                } else {
                    $notificacion.removeClass('marcando-leida');
                    console.error('Error al marcar notificación:', response.data);
                }
            },
            error: function(xhr, status, error) {
                $notificacion.removeClass('marcando-leida');
                console.error('Error AJAX:', error);
            }
        });
    }

    /**
     * Marcar como leída sin feedback visual (para clicks en enlaces)
     */
    function marcarLeidaSilencioso(id) {
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_mark_notification_read',
                nonce: config.nonce,
                notification_id: id
            }
        });
    }

    /**
     * Marcar todas como leídas
     */
    function marcarTodasLeidas(e) {
        e.preventDefault();

        if (state.isLoading) {
            return;
        }

        const $btn = $(this);
        const $widget = $btn.closest('.flavor-notificaciones-widget');
        const $notificaciones = $widget.find('.flavor-notificacion.no-leida');

        if ($notificaciones.length === 0) {
            return;
        }

        $btn.prop('disabled', true);
        $notificaciones.addClass('marcando-leida');

        // Enviar notification_id = 0 para marcar todas
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_mark_notification_read',
                nonce: config.nonce,
                notification_id: 0
            },
            success: function(response) {
                if (response.success) {
                    $notificaciones
                        .removeClass('no-leida marcando-leida')
                        .find('.flavor-btn-marcar-leida')
                        .remove();

                    actualizarContador(0, true);

                    // Ocultar el botón de marcar todas
                    $btn.fadeOut(200);

                    // Actualizar badge del header
                    $widget.find('.flavor-notificaciones-header .flavor-badge').fadeOut(200);
                } else {
                    $notificaciones.removeClass('marcando-leida');
                    $btn.prop('disabled', false);
                    console.error('Error:', response.data);
                }
            },
            error: function(xhr, status, error) {
                $notificaciones.removeClass('marcando-leida');
                $btn.prop('disabled', false);
                console.error('Error AJAX:', error);
            }
        });
    }

    /**
     * Actualizar el contador de notificaciones
     */
    function actualizarContador(cambio, setAbsoluto) {
        const $contadores = $('.flavor-notificaciones-count');
        const $badges = $('.flavor-notificaciones-header .flavor-badge');

        $contadores.each(function() {
            const $contador = $(this);
            let actual = parseInt($contador.text()) || 0;
            let nuevo;

            if (setAbsoluto) {
                nuevo = cambio;
            } else {
                nuevo = Math.max(0, actual + cambio);
            }

            if (nuevo > 0) {
                $contador.text(nuevo).show();
            } else {
                $contador.hide();
            }
        });

        $badges.each(function() {
            const $badge = $(this);
            let actual = parseInt($badge.text()) || 0;
            let nuevo;

            if (setAbsoluto) {
                nuevo = cambio;
            } else {
                nuevo = Math.max(0, actual + cambio);
            }

            if (nuevo > 0) {
                $badge.text(nuevo).show();
            } else {
                $badge.hide();
            }
        });
    }

    /**
     * Actualizar lista de notificaciones vía AJAX
     */
    function actualizarNotificaciones() {
        if (state.isLoading) {
            return;
        }

        const $listas = $('.flavor-notificaciones-lista');

        if ($listas.length === 0) {
            return;
        }

        state.isLoading = true;

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_get_notifications',
                nonce: config.nonce,
                limit: 10
            },
            success: function(response) {
                state.isLoading = false;
                state.lastUpdate = new Date();

                if (response.success && response.data) {
                    renderizarNotificaciones(response.data.notifications || []);
                    if (response.data.unread_count !== undefined) {
                        actualizarContador(response.data.unread_count, true);
                    }
                }
            },
            error: function(xhr, status, error) {
                state.isLoading = false;
                console.error('Error al actualizar notificaciones:', error);
            }
        });
    }

    /**
     * Renderizar lista de notificaciones
     */
    function renderizarNotificaciones(notificaciones) {
        const $listas = $('.flavor-notificaciones-lista');

        $listas.each(function() {
            const $lista = $(this);
            const $widget = $lista.closest('.flavor-notificaciones-widget');
            const limite = parseInt($widget.data('limite')) || 10;
            const esCompacto = $widget.closest('.flavor-notificaciones-dropdown').length > 0;

            if (notificaciones.length === 0) {
                $lista.html(renderEstadoVacio());
                return;
            }

            let html = '';
            const notifsAMostrar = notificaciones.slice(0, limite);

            notifsAMostrar.forEach(function(notif) {
                html += renderNotificacion(notif, esCompacto);
            });

            $lista.html(html);
        });
    }

    /**
     * Renderizar una notificación individual
     */
    function renderNotificacion(notif, compact) {
        const clases = ['flavor-notificacion'];
        if (!notif.is_read) {
            clases.push('no-leida');
        }
        if (compact) {
            clases.push('compact');
        }

        let titulo;
        if (notif.link) {
            titulo = `<a href="${escapeHtml(notif.link)}" class="flavor-notificacion-titulo">${escapeHtml(notif.title)}</a>`;
        } else {
            titulo = `<span class="flavor-notificacion-titulo">${escapeHtml(notif.title)}</span>`;
        }

        let mensaje = '';
        if (!compact && notif.message) {
            mensaje = `<p class="flavor-notificacion-mensaje">${escapeHtml(notif.message)}</p>`;
        }

        let botonMarcar = '';
        if (!notif.is_read) {
            botonMarcar = `
                <button type="button" class="flavor-btn-marcar-leida"
                        data-id="${notif.id}"
                        title="${config.strings.marcarLeida}">
                    <span class="dashicons dashicons-dismiss"></span>
                </button>
            `;
        }

        const tiempoTranscurrido = calcularTiempoTranscurrido(notif.created_at);

        return `
            <div class="${clases.join(' ')}" data-id="${notif.id}">
                <div class="flavor-notificacion-icono" style="color: ${escapeHtml(notif.color || '#3b82f6')}">
                    <span class="dashicons ${escapeHtml(notif.icon || 'dashicons-bell')}"></span>
                </div>
                <div class="flavor-notificacion-contenido">
                    ${titulo}
                    ${mensaje}
                    <span class="flavor-notificacion-tiempo">${tiempoTranscurrido}</span>
                </div>
                ${botonMarcar}
            </div>
        `;
    }

    /**
     * Calcular tiempo transcurrido
     */
    function calcularTiempoTranscurrido(fechaStr) {
        if (!fechaStr) return '';

        const fecha = new Date(fechaStr.replace(' ', 'T'));
        const ahora = new Date();
        const diff = Math.floor((ahora - fecha) / 1000);

        if (diff < 60) {
            return config.strings.hace + ' un momento';
        }

        const intervalos = [
            { segundos: 31536000, singular: 'año', plural: 'años' },
            { segundos: 2592000, singular: 'mes', plural: 'meses' },
            { segundos: 604800, singular: 'semana', plural: 'semanas' },
            { segundos: 86400, singular: 'día', plural: 'días' },
            { segundos: 3600, singular: 'hora', plural: 'horas' },
            { segundos: 60, singular: 'minuto', plural: 'minutos' }
        ];

        for (const intervalo of intervalos) {
            const cantidad = Math.floor(diff / intervalo.segundos);
            if (cantidad >= 1) {
                const nombre = cantidad === 1 ? intervalo.singular : intervalo.plural;
                return `${config.strings.hace} ${cantidad} ${nombre}`;
            }
        }

        return config.strings.hace + ' un momento';
    }

    /**
     * Renderizar estado vacío
     */
    function renderEstadoVacio() {
        return `
            <div class="flavor-notificaciones-vacio">
                <span class="dashicons dashicons-bell"></span>
                <p>${config.strings.sinNotificaciones}</p>
            </div>
        `;
    }

    /**
     * Escapar HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Iniciar polling para nuevas notificaciones
     */
    function startPolling() {
        if (config.pollInterval > 0) {
            state.pollTimer = setInterval(checkNuevasNotificaciones, config.pollInterval);
        }
    }

    /**
     * Detener polling
     */
    function stopPolling() {
        if (state.pollTimer) {
            clearInterval(state.pollTimer);
            state.pollTimer = null;
        }
    }

    /**
     * Verificar si hay nuevas notificaciones
     */
    function checkNuevasNotificaciones() {
        // Solo verificar si la pestaña está visible
        if (document.hidden) {
            return;
        }

        $.ajax({
            url: config.restUrl + '/unread-count',
            type: 'GET',
            headers: {
                'X-WP-Nonce': config.restNonce
            },
            success: function(response) {
                if (response.unread_count !== undefined) {
                    const $contadores = $('.flavor-notificaciones-count');
                    const actualCount = parseInt($contadores.first().text()) || 0;

                    if (response.unread_count > actualCount) {
                        // Hay nuevas notificaciones
                        actualizarContador(response.unread_count, true);

                        // Si el dropdown está abierto, actualizar la lista
                        if (state.dropdownOpen) {
                            actualizarNotificaciones();
                        }

                        // Emitir evento personalizado
                        $(document).trigger('flavor:nuevas-notificaciones', [response.unread_count]);
                    } else if (response.unread_count !== actualCount) {
                        // El contador cambió (tal vez se leyeron desde otro lugar)
                        actualizarContador(response.unread_count, true);
                    }
                }
            }
        });
    }

    /**
     * API pública
     */
    window.FlavorNotificaciones = {
        actualizar: actualizarNotificaciones,
        marcarLeida: function(id) {
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_mark_notification_read',
                    nonce: config.nonce,
                    notification_id: id
                }
            });
        },
        marcarTodasLeidas: function() {
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_mark_notification_read',
                    nonce: config.nonce,
                    notification_id: 0
                },
                success: function() {
                    actualizarNotificaciones();
                }
            });
        },
        getContador: function() {
            return parseInt($('.flavor-notificaciones-count').first().text()) || 0;
        },
        startPolling: startPolling,
        stopPolling: stopPolling
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(init);

    // Pausar polling cuando la pestaña no está visible
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopPolling();
        } else {
            startPolling();
            // Verificar inmediatamente al volver
            checkNuevasNotificaciones();
        }
    });

})(jQuery);
