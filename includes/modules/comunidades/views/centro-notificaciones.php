<?php
/**
 * Vista: Centro de Notificaciones de Comunidades
 *
 * Panel para gestionar notificaciones cross-comunidad
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_id = get_current_user_id();
$preferencias = get_user_meta($usuario_id, 'flavor_notificaciones_comunidades', true);
$preferencias = is_array($preferencias) ? $preferencias : [];

// Tipos de notificación con configuración
$tipos_notificacion = [
    'nueva_publicacion' => [
        'label' => __('Nuevas publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'desc'  => __('Cuando alguien publica en tus comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon'  => '📝',
    ],
    'nuevo_evento' => [
        'label' => __('Nuevos eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'desc'  => __('Cuando se crea un evento en tus comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon'  => '📅',
    ],
    'nuevo_miembro' => [
        'label' => __('Nuevos miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'desc'  => __('Cuando alguien se une a comunidades que administras', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon'  => '👋',
    ],
    'recurso_compartido' => [
        'label' => __('Recursos compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'desc'  => __('Cuando se comparte contenido entre comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon'  => '📦',
    ],
    'mencion' => [
        'label' => __('Menciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'desc'  => __('Cuando alguien te menciona', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon'  => '💬',
    ],
    'notificar_comunidades_relacionadas' => [
        'label' => __('Actividad en comunidades relacionadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'desc'  => __('Publicaciones relevantes en comunidades similares', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon'  => '🏘️',
    ],
    'notificar_eventos_red' => [
        'label' => __('Eventos de la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'desc'  => __('Eventos en comunidades de tu misma categoría', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon'  => '🗓️',
    ],
    'contenido_federado' => [
        'label' => __('Contenido de la red federada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'desc'  => __('Contenido relevante de otros nodos de la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon'  => '🌐',
    ],
    'crosspost' => [
        'label' => __('Cross-posting', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'desc'  => __('Cuando se comparte contenido de otras comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon'  => '🔄',
    ],
];
?>

<div class="flavor-centro-notificaciones" data-nonce="<?php echo esc_attr(wp_create_nonce('flavor_comunidades_nonce')); ?>">
    <div class="flavor-notif-notice" id="centro-notificaciones-notice" style="display:none;"></div>

    <!-- Cabecera -->
    <header class="flavor-notif-header">
        <h2 class="flavor-notif-titulo">
            <span class="dashicons dashicons-bell"></span>
            <?php esc_html_e('Notificaciones de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <span class="flavor-notif-badge" id="contador-no-leidas">0</span>
        </h2>

        <div class="flavor-notif-acciones">
            <button type="button" class="flavor-btn-secundario" id="marcar-todas-leidas">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Marcar todas como leídas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="flavor-btn-secundario" id="abrir-preferencias">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php esc_html_e('Preferencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    </header>

    <!-- Lista de notificaciones -->
    <div class="flavor-notif-lista" id="lista-notificaciones">
        <div class="flavor-notif-cargando">
            <span class="flavor-spinner"></span>
            <?php esc_html_e('Cargando notificaciones...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </div>
    </div>

    <!-- Modal de preferencias -->
    <div class="flavor-modal" id="modal-preferencias" style="display:none;">
        <div class="flavor-modal-overlay"></div>
        <div class="flavor-modal-contenido">
            <header class="flavor-modal-header">
                <h3>
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e('Preferencias de notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <button type="button" class="flavor-modal-cerrar">&times;</button>
            </header>

            <div class="flavor-modal-body">
                <p class="flavor-preferencias-intro">
                    <?php esc_html_e('Elige qué notificaciones deseas recibir de tus comunidades.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>

                <form id="form-preferencias" class="flavor-preferencias-form">
                    <?php foreach ($tipos_notificacion as $tipo => $config):
                        $activo = !isset($preferencias[$tipo]) || $preferencias[$tipo] !== false;
                    ?>
                    <label class="flavor-preferencia-item">
                        <div class="flavor-preferencia-toggle">
                            <input type="checkbox"
                                   name="<?php echo esc_attr($tipo); ?>"
                                   value="true"
                                   <?php checked($activo); ?>>
                            <span class="flavor-toggle-slider"></span>
                        </div>
                        <div class="flavor-preferencia-info">
                            <span class="flavor-preferencia-icon"><?php echo $config['icon']; ?></span>
                            <div class="flavor-preferencia-texto">
                                <strong><?php echo esc_html($config['label']); ?></strong>
                                <small><?php echo esc_html($config['desc']); ?></small>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </form>
            </div>

            <footer class="flavor-modal-footer">
                <button type="button" class="flavor-btn-secundario" id="cancelar-preferencias">
                    <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="flavor-btn-primario" id="guardar-preferencias">
                    <?php esc_html_e('Guardar preferencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </footer>
        </div>
    </div>
</div>

<style>
.flavor-centro-notificaciones {
    max-width: 800px;
    margin: 0 auto;
    font-family: var(--gc-font-family, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
}

.flavor-notif-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--gc-gray-200, #e5e7eb);
}

.flavor-notif-titulo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 1.4em;
    color: var(--gc-gray-900, #111827);
}

.flavor-notif-titulo .dashicons {
    color: var(--gc-primary, #2e7d32);
}

.flavor-notif-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 8px;
    background: var(--gc-danger, #ef4444);
    color: white;
    border-radius: 12px;
    font-size: 0.7em;
    font-weight: 600;
}

.flavor-notif-badge:empty,
.flavor-notif-badge[data-count="0"] {
    display: none;
}

.flavor-notif-acciones {
    display: flex;
    gap: 8px;
}

.flavor-btn-secundario {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: white;
    border: 1px solid var(--gc-gray-300, #d1d5db);
    border-radius: var(--gc-button-radius, 6px);
    color: var(--gc-gray-700, #374151);
    font-size: 0.9em;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-btn-secundario:hover {
    background: var(--gc-gray-50, #f9fafb);
    border-color: var(--gc-gray-400, #9ca3af);
}

.flavor-btn-secundario .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.flavor-btn-primario {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    background: var(--gc-primary, #2e7d32);
    border: none;
    border-radius: var(--gc-button-radius, 6px);
    color: white;
    font-size: 0.95em;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.flavor-btn-primario:hover {
    background: var(--gc-primary-dark, #1b5e20);
}

/* Lista de notificaciones */
.flavor-notif-lista {
    background: white;
    border: 1px solid var(--gc-gray-200, #e5e7eb);
    border-radius: var(--gc-border-radius, 12px);
    overflow: hidden;
}

.flavor-notif-cargando {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 40px;
    color: var(--gc-gray-500, #6b7280);
}

.flavor-spinner {
    width: 24px;
    height: 24px;
    border: 3px solid var(--gc-gray-200, #e5e7eb);
    border-top-color: var(--gc-primary, #2e7d32);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.flavor-notif-item {
    display: flex;
    gap: 14px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--gc-gray-100, #f3f4f6);
    transition: background 0.2s;
    cursor: pointer;
}

.flavor-notif-item:hover {
    background: var(--gc-gray-50, #f9fafb);
}

.flavor-notif-item:last-child {
    border-bottom: none;
}

.flavor-notif-item.no-leida {
    background: rgba(46, 125, 50, 0.05);
}

.flavor-notif-item.no-leida::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--gc-primary, #2e7d32);
}

.flavor-notif-item {
    position: relative;
}

.flavor-notif-icono {
    font-size: 28px;
    line-height: 1;
}

.flavor-notif-contenido {
    flex: 1;
    min-width: 0;
}

.flavor-notif-titulo-item {
    margin: 0 0 4px;
    font-size: 0.95em;
    font-weight: 600;
    color: var(--gc-gray-900, #111827);
}

.flavor-notif-mensaje {
    margin: 0 0 6px;
    font-size: 0.9em;
    color: var(--gc-gray-600, #4b5563);
    line-height: 1.4;
}

.flavor-notif-fecha {
    font-size: 0.8em;
    color: var(--gc-gray-400, #9ca3af);
}

.flavor-notif-vacia {
    text-align: center;
    padding: 60px 20px;
    color: var(--gc-gray-500, #6b7280);
}

.flavor-notif-vacia .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 16px;
    color: var(--gc-gray-300, #d1d5db);
}

.flavor-notif-notice {
    margin-bottom: 16px;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 0.95em;
}

.flavor-notif-notice.error {
    background: #fee2e2;
    color: #991b1b;
}

.flavor-notif-notice.success {
    background: #dcfce7;
    color: #166534;
}

/* Modal */
.flavor-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
}

.flavor-modal-contenido {
    position: relative;
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    background: white;
    border-radius: var(--gc-border-radius, 12px);
    display: flex;
    flex-direction: column;
    box-shadow: var(--gc-shadow-xl, 0 25px 50px -12px rgba(0, 0, 0, 0.25));
}

.flavor-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--gc-gray-200, #e5e7eb);
}

.flavor-modal-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.1em;
    color: var(--gc-gray-900, #111827);
}

.flavor-modal-header .dashicons {
    color: var(--gc-primary, #2e7d32);
}

.flavor-modal-cerrar {
    background: none;
    border: none;
    font-size: 28px;
    color: var(--gc-gray-400, #9ca3af);
    cursor: pointer;
    line-height: 1;
}

.flavor-modal-cerrar:hover {
    color: var(--gc-gray-600, #4b5563);
}

.flavor-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 24px;
}

.flavor-preferencias-intro {
    margin: 0 0 20px;
    color: var(--gc-gray-600, #4b5563);
}

.flavor-preferencia-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid var(--gc-gray-100, #f3f4f6);
    cursor: pointer;
}

.flavor-preferencia-item:last-child {
    border-bottom: none;
}

.flavor-preferencia-toggle {
    position: relative;
}

.flavor-preferencia-toggle input {
    position: absolute;
    opacity: 0;
}

.flavor-toggle-slider {
    display: block;
    width: 44px;
    height: 24px;
    background: var(--gc-gray-300, #d1d5db);
    border-radius: 12px;
    transition: background 0.2s;
    position: relative;
}

.flavor-toggle-slider::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: transform 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.flavor-preferencia-toggle input:checked + .flavor-toggle-slider {
    background: var(--gc-primary, #2e7d32);
}

.flavor-preferencia-toggle input:checked + .flavor-toggle-slider::after {
    transform: translateX(20px);
}

.flavor-preferencia-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.flavor-preferencia-icon {
    font-size: 24px;
}

.flavor-preferencia-texto {
    display: flex;
    flex-direction: column;
}

.flavor-preferencia-texto strong {
    font-size: 0.95em;
    color: var(--gc-gray-900, #111827);
}

.flavor-preferencia-texto small {
    font-size: 0.8em;
    color: var(--gc-gray-500, #6b7280);
    margin-top: 2px;
}

.flavor-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 24px;
    border-top: 1px solid var(--gc-gray-200, #e5e7eb);
}

@media (max-width: 600px) {
    .flavor-notif-header {
        flex-direction: column;
        align-items: stretch;
    }

    .flavor-notif-acciones {
        justify-content: center;
    }

    .flavor-modal-contenido {
        width: 95%;
        max-height: 90vh;
    }
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var contenedor = document.querySelector('.flavor-centro-notificaciones');
        if (!contenedor) return;

        var nonce = contenedor.dataset.nonce;
        var listaNotificaciones = document.getElementById('lista-notificaciones');
        var contadorBadge = document.getElementById('contador-no-leidas');
        var modalPreferencias = document.getElementById('modal-preferencias');
        var formPreferencias = document.getElementById('form-preferencias');
        var notice = document.getElementById('centro-notificaciones-notice');

        // Cargar notificaciones al inicio
        cargarNotificaciones();

        // Marcar todas como leídas
        document.getElementById('marcar-todas-leidas').addEventListener('click', function() {
            marcarNotificacion(0, true);
        });

        // Abrir modal de preferencias
        document.getElementById('abrir-preferencias').addEventListener('click', function() {
            modalPreferencias.style.display = 'flex';
        });

        // Cerrar modal
        document.querySelector('.flavor-modal-cerrar').addEventListener('click', cerrarModal);
        document.querySelector('.flavor-modal-overlay').addEventListener('click', cerrarModal);
        document.getElementById('cancelar-preferencias').addEventListener('click', cerrarModal);

        // Guardar preferencias
        document.getElementById('guardar-preferencias').addEventListener('click', guardarPreferencias);

        function cerrarModal() {
            modalPreferencias.style.display = 'none';
        }

        function mostrarAviso(mensaje, tipo) {
            if (!notice) return;
            notice.className = 'flavor-notif-notice ' + (tipo || 'error');
            notice.textContent = mensaje;
            notice.style.display = 'block';
        }

        function cargarNotificaciones() {
            var formData = new FormData();
            formData.append('action', 'comunidades_obtener_notificaciones');
            formData.append('nonce', nonce);
            formData.append('limite', 30);

            fetch(flavorComunidadesConfig?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    renderizarNotificaciones(data.data.notificaciones);
                    actualizarContador(data.data.no_leidas);
                } else {
                    listaNotificaciones.innerHTML = '<div class="flavor-notif-vacia">' +
                        '<span class="dashicons dashicons-warning"></span>' +
                        '<p>' + (data.data?.message || 'Error al cargar notificaciones') + '</p>' +
                        '</div>';
                }
            })
            .catch(function(err) {
                console.error('Error:', err);
                mostrarAviso('<?php echo esc_js(__('Error al cargar notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>', 'error');
            });
        }

        function renderizarNotificaciones(notificaciones) {
            if (!notificaciones || notificaciones.length === 0) {
                listaNotificaciones.innerHTML = '<div class="flavor-notif-vacia">' +
                    '<span class="dashicons dashicons-bell"></span>' +
                    '<h3><?php echo esc_js(__('No tienes notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></h3>' +
                    '<p><?php echo esc_js(__('Las notificaciones de tus comunidades aparecerán aquí.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></p>' +
                    '</div>';
                return;
            }

            var html = '';
            notificaciones.forEach(function(notif) {
                var claseLeida = notif.is_read == 0 ? 'no-leida' : '';
                var fecha = calcularTiempoRelativo(notif.created_at);

                html += '<div class="flavor-notif-item ' + claseLeida + '" data-id="' + notif.id + '" data-link="' + (notif.link || '') + '">' +
                    '<span class="flavor-notif-icono">' + (notif.icon || '🔔') + '</span>' +
                    '<div class="flavor-notif-contenido">' +
                        '<h4 class="flavor-notif-titulo-item">' + escapeHtml(notif.title) + '</h4>' +
                        '<p class="flavor-notif-mensaje">' + escapeHtml(notif.message) + '</p>' +
                        '<span class="flavor-notif-fecha">' + fecha + '</span>' +
                    '</div>' +
                '</div>';
            });

            listaNotificaciones.innerHTML = html;

            // Añadir eventos a cada notificación
            listaNotificaciones.querySelectorAll('.flavor-notif-item').forEach(function(item) {
                item.addEventListener('click', function() {
                    var id = item.dataset.id;
                    var link = item.dataset.link;

                    if (item.classList.contains('no-leida')) {
                        marcarNotificacion(id, false);
                        item.classList.remove('no-leida');
                    }

                    if (link) {
                        window.location.href = link;
                    }
                });
            });
        }

        function marcarNotificacion(id, todas) {
            var formData = new FormData();
            formData.append('action', 'comunidades_marcar_notificacion_leida');
            formData.append('nonce', nonce);
            if (todas) {
                formData.append('marcar_todas', 'true');
            } else {
                formData.append('notificacion_id', id);
            }

            fetch(flavorComunidadesConfig?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    actualizarContador(data.data.no_leidas);
                    if (todas) {
                        listaNotificaciones.querySelectorAll('.no-leida').forEach(function(item) {
                            item.classList.remove('no-leida');
                        });
                    }
                }
            });
        }

        function guardarPreferencias() {
            var formData = new FormData();
            formData.append('action', 'comunidades_guardar_preferencias_notificacion');
            formData.append('nonce', nonce);

            var checkboxes = formPreferencias.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(function(checkbox) {
                formData.append('preferencias[' + checkbox.name + ']', checkbox.checked ? 'true' : 'false');
            });

            fetch(flavorComunidadesConfig?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    cerrarModal();
                    mostrarAviso('<?php echo esc_js(__('Preferencias guardadas', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>', 'success');
                } else {
                    mostrarAviso(data.data?.message || '<?php echo esc_js(__('No se pudieron guardar las preferencias', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>', 'error');
                }
            })
            .catch(function() {
                mostrarAviso('<?php echo esc_js(__('Error de conexión al guardar preferencias', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>', 'error');
            });
        }

        function actualizarContador(count) {
            contadorBadge.textContent = count;
            contadorBadge.dataset.count = count;
        }

        function calcularTiempoRelativo(fecha) {
            var ahora = new Date();
            var fechaNotif = new Date(fecha);
            var diff = Math.floor((ahora - fechaNotif) / 1000);

            if (diff < 60) return '<?php echo esc_js(__('Ahora mismo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
            if (diff < 3600) return Math.floor(diff / 60) + ' min';
            if (diff < 86400) return Math.floor(diff / 3600) + ' h';
            if (diff < 604800) return Math.floor(diff / 86400) + ' d';
            return fechaNotif.toLocaleDateString();
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
})();
</script>
