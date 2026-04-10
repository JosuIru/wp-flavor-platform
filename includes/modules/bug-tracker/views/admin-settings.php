<?php
/**
 * Vista de configuración del Bug Tracker
 *
 * @package Flavor_Platform
 * @subpackage Bug_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener configuración actual
$settings = $this->get_settings();
$channels = $this->get_channels();
$canales = $channels ? $channels->obtener_canales() : [];

// Procesar formulario de configuración
if (isset($_POST['guardar_config']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? '')), 'flavor_bug_tracker_settings')) {
    $nuevos_settings = [
        'captura_automatica' => isset($_POST['captura_automatica']),
        'capturar_warnings' => isset($_POST['capturar_warnings']),
        'capturar_notices' => isset($_POST['capturar_notices']),
        'capturar_deprecations' => isset($_POST['capturar_deprecations']),
        'notificar_admins_inapp' => isset($_POST['notificar_admins_inapp']),
        'agrupar_duplicados' => isset($_POST['agrupar_duplicados']),
        'limite_notificaciones_hora' => intval($_POST['limite_notificaciones_hora'] ?? 10),
        'limpiar_resueltos_dias' => intval($_POST['limpiar_resueltos_dias'] ?? 30),
        'plugins_monitorizados' => array_filter(array_map('sanitize_text_field', explode("\n", $_POST['plugins_monitorizados'] ?? ''))),
    ];

    update_option('flavor_bug_tracker_settings', $nuevos_settings);
    $settings = $nuevos_settings;

    echo '<div class="notice notice-success"><p>' . esc_html__('Configuración guardada correctamente.', 'flavor-platform') . '</p></div>';
}

// Procesar formulario de canal
if (isset($_POST['guardar_canal']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce_canal'] ?? '')), 'flavor_bug_tracker_canal')) {
    $datos_canal = [
        'nombre' => sanitize_text_field($_POST['canal_nombre'] ?? ''),
        'tipo' => sanitize_text_field($_POST['canal_tipo'] ?? 'email'),
        'webhook_url' => esc_url_raw($_POST['canal_webhook_url'] ?? ''),
        'email_destinatarios' => sanitize_textarea_field($_POST['canal_email_destinatarios'] ?? ''),
        'severidad_minima' => sanitize_text_field($_POST['canal_severidad_minima'] ?? 'high'),
        'activo' => isset($_POST['canal_activo']),
    ];

    $canal_id = intval($_POST['canal_id'] ?? 0);

    if ($canal_id > 0) {
        $channels->actualizar_canal($canal_id, $datos_canal);
        echo '<div class="notice notice-success"><p>' . esc_html__('Canal actualizado correctamente.', 'flavor-platform') . '</p></div>';
    } else {
        $channels->crear_canal($datos_canal);
        echo '<div class="notice notice-success"><p>' . esc_html__('Canal creado correctamente.', 'flavor-platform') . '</p></div>';
    }

    $canales = $channels->obtener_canales();
}

// Eliminar canal
if (isset($_GET['eliminar_canal']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'eliminar_canal')) {
    $canal_id = intval($_GET['eliminar_canal']);
    $channels->eliminar_canal($canal_id);
    $canales = $channels->obtener_canales();
    echo '<div class="notice notice-success"><p>' . esc_html__('Canal eliminado correctamente.', 'flavor-platform') . '</p></div>';
}

// Probar canal
if (isset($_GET['probar_canal']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? '')), 'probar_canal')) {
    $canal_id = intval($_GET['probar_canal']);
    $resultado = $channels->probar_canal($canal_id);
    if ($resultado) {
        echo '<div class="notice notice-success"><p>' . esc_html__('Mensaje de prueba enviado correctamente.', 'flavor-platform') . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . esc_html__('Error al enviar mensaje de prueba.', 'flavor-platform') . '</p></div>';
    }
}
?>

<style>
.flavor-bug-tracker-settings {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (max-width: 1200px) {
    .flavor-bug-tracker-settings {
        grid-template-columns: 1fr;
    }
}
.flavor-bug-tracker-settings .settings-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}
.flavor-bug-tracker-settings .settings-card h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}
.flavor-bug-tracker-settings .form-row {
    margin-bottom: 15px;
}
.flavor-bug-tracker-settings .form-row label {
    display: block;
    font-weight: 500;
    margin-bottom: 5px;
}
.flavor-bug-tracker-settings .form-row .description {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}
.flavor-bug-tracker-settings .checkbox-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}
.flavor-bug-tracker-settings .checkbox-row label {
    margin: 0;
    font-weight: normal;
}
.channels-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
.channels-table th,
.channels-table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
    text-align: left;
}
.channels-table th {
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    color: #666;
}
.channel-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}
.channel-badge.slack { background: #4a154b; color: white; }
.channel-badge.discord { background: #5865f2; color: white; }
.channel-badge.email { background: #2563eb; color: white; }
.channel-badge.webhook { background: #6b7280; color: white; }
.channel-status {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}
.channel-status.activo { background: #16a34a; }
.channel-status.inactivo { background: #dc2626; }
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    align-items: center;
    justify-content: center;
}
.modal-overlay.active {
    display: flex;
}
.modal-content {
    background: white;
    border-radius: 8px;
    padding: 20px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}
.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}
</style>

<div class="flavor-bug-tracker-settings">
    <!-- Configuración General -->
    <div class="settings-card">
        <h3><?php esc_html_e('Configuración General', 'flavor-platform'); ?></h3>

        <form method="post">
            <?php wp_nonce_field('flavor_bug_tracker_settings'); ?>

            <div class="form-row">
                <div class="checkbox-row">
                    <input type="checkbox" id="captura_automatica" name="captura_automatica" <?php checked($settings['captura_automatica'] ?? true); ?>>
                    <label for="captura_automatica"><?php esc_html_e('Captura automática de errores PHP', 'flavor-platform'); ?></label>
                </div>
                <p class="description"><?php esc_html_e('Captura automáticamente errores y excepciones de los plugins monitorizados.', 'flavor-platform'); ?></p>
            </div>

            <div class="form-row">
                <label><?php esc_html_e('Tipos de errores a capturar:', 'flavor-platform'); ?></label>
                <div class="checkbox-row">
                    <input type="checkbox" id="capturar_warnings" name="capturar_warnings" <?php checked($settings['capturar_warnings'] ?? false); ?>>
                    <label for="capturar_warnings"><?php esc_html_e('Warnings (E_WARNING)', 'flavor-platform'); ?></label>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" id="capturar_notices" name="capturar_notices" <?php checked($settings['capturar_notices'] ?? false); ?>>
                    <label for="capturar_notices"><?php esc_html_e('Notices (E_NOTICE)', 'flavor-platform'); ?></label>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" id="capturar_deprecations" name="capturar_deprecations" <?php checked($settings['capturar_deprecations'] ?? false); ?>>
                    <label for="capturar_deprecations"><?php esc_html_e('Deprecations (E_DEPRECATED)', 'flavor-platform'); ?></label>
                </div>
                <p class="description"><?php esc_html_e('Los errores fatales (E_ERROR, E_PARSE) siempre se capturan.', 'flavor-platform'); ?></p>
            </div>

            <div class="form-row">
                <label for="plugins_monitorizados"><?php esc_html_e('Plugins a monitorizar (uno por línea):', 'flavor-platform'); ?></label>
                <textarea id="plugins_monitorizados" name="plugins_monitorizados" rows="4" style="width: 100%;"><?php echo esc_textarea(implode("\n", $settings['plugins_monitorizados'] ?? ['flavor-platform', 'flavor-landing', 'flavor-license-server'])); ?></textarea>
                <p class="description"><?php esc_html_e('Nombre de la carpeta del plugin (ej: flavor-chat-ia).', 'flavor-platform'); ?></p>
            </div>

            <div class="form-row">
                <div class="checkbox-row">
                    <input type="checkbox" id="agrupar_duplicados" name="agrupar_duplicados" <?php checked($settings['agrupar_duplicados'] ?? true); ?>>
                    <label for="agrupar_duplicados"><?php esc_html_e('Agrupar errores duplicados', 'flavor-platform'); ?></label>
                </div>
                <p class="description"><?php esc_html_e('Los errores idénticos incrementan el contador en lugar de crear nuevos registros.', 'flavor-platform'); ?></p>
            </div>

            <div class="form-row">
                <div class="checkbox-row">
                    <input type="checkbox" id="notificar_admins_inapp" name="notificar_admins_inapp" <?php checked($settings['notificar_admins_inapp'] ?? true); ?>>
                    <label for="notificar_admins_inapp"><?php esc_html_e('Notificar administradores en el panel', 'flavor-platform'); ?></label>
                </div>
            </div>

            <div class="form-row">
                <label for="limite_notificaciones_hora"><?php esc_html_e('Límite de notificaciones por hora:', 'flavor-platform'); ?></label>
                <input type="number" id="limite_notificaciones_hora" name="limite_notificaciones_hora" value="<?php echo esc_attr($settings['limite_notificaciones_hora'] ?? 10); ?>" min="0" max="100" style="width: 80px;">
                <p class="description"><?php esc_html_e('Máximo de notificaciones por canal por hora. 0 = sin límite.', 'flavor-platform'); ?></p>
            </div>

            <div class="form-row">
                <label for="limpiar_resueltos_dias"><?php esc_html_e('Eliminar bugs resueltos después de (días):', 'flavor-platform'); ?></label>
                <input type="number" id="limpiar_resueltos_dias" name="limpiar_resueltos_dias" value="<?php echo esc_attr($settings['limpiar_resueltos_dias'] ?? 30); ?>" min="0" max="365" style="width: 80px;">
                <p class="description"><?php esc_html_e('0 = no eliminar automáticamente.', 'flavor-platform'); ?></p>
            </div>

            <button type="submit" name="guardar_config" class="button button-primary">
                <?php esc_html_e('Guardar Configuración', 'flavor-platform'); ?>
            </button>
        </form>
    </div>

    <!-- Canales de Notificación -->
    <div class="settings-card">
        <h3><?php esc_html_e('Canales de Notificación', 'flavor-platform'); ?></h3>

        <p><?php esc_html_e('Configura los canales donde se enviarán las notificaciones de bugs.', 'flavor-platform'); ?></p>

        <button type="button" class="button button-primary" id="btn-nuevo-canal">
            + <?php esc_html_e('Nuevo Canal', 'flavor-platform'); ?>
        </button>

        <?php if (!empty($canales)) : ?>
            <table class="channels-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Nombre', 'flavor-platform'); ?></th>
                        <th><?php esc_html_e('Tipo', 'flavor-platform'); ?></th>
                        <th><?php esc_html_e('Severidad Mín.', 'flavor-platform'); ?></th>
                        <th><?php esc_html_e('Estado', 'flavor-platform'); ?></th>
                        <th><?php esc_html_e('Acciones', 'flavor-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($canales as $canal) : ?>
                        <tr>
                            <td><?php echo esc_html($canal->nombre); ?></td>
                            <td>
                                <span class="channel-badge <?php echo esc_attr($canal->tipo); ?>">
                                    <?php echo esc_html(strtoupper($canal->tipo)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(ucfirst($canal->severidad_minima)); ?></td>
                            <td>
                                <span class="channel-status <?php echo $canal->activo ? 'activo' : 'inactivo'; ?>" title="<?php echo $canal->activo ? esc_attr__('Activo', 'flavor-platform') : esc_attr__('Inactivo', 'flavor-platform'); ?>"></span>
                            </td>
                            <td>
                                <button type="button" class="button button-small btn-editar-canal" data-canal='<?php echo esc_attr(wp_json_encode($canal)); ?>'>
                                    <?php esc_html_e('Editar', 'flavor-platform'); ?>
                                </button>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'flavor-bug-tracker', 'tab' => 'settings', 'probar_canal' => $canal->id], admin_url('admin.php')), 'probar_canal')); ?>" class="button button-small">
                                    <?php esc_html_e('Probar', 'flavor-platform'); ?>
                                </a>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'flavor-bug-tracker', 'tab' => 'settings', 'eliminar_canal' => $canal->id], admin_url('admin.php')), 'eliminar_canal')); ?>" class="button button-small" onclick="return confirm('<?php echo esc_js(__('¿Eliminar este canal?', 'flavor-platform')); ?>');">
                                    <?php esc_html_e('Eliminar', 'flavor-platform'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p style="color: #666; margin-top: 15px;">
                <?php esc_html_e('No hay canales configurados. Crea uno para recibir notificaciones de bugs.', 'flavor-platform'); ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para crear/editar canal -->
<div class="modal-overlay" id="modal-canal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-titulo"><?php esc_html_e('Nuevo Canal', 'flavor-platform'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>

        <form method="post" id="form-canal">
            <?php wp_nonce_field('flavor_bug_tracker_canal', '_wpnonce_canal'); ?>
            <input type="hidden" name="canal_id" id="canal_id" value="0">

            <div class="form-row">
                <label for="canal_nombre"><?php esc_html_e('Nombre:', 'flavor-platform'); ?></label>
                <input type="text" id="canal_nombre" name="canal_nombre" required style="width: 100%;">
            </div>

            <div class="form-row">
                <label for="canal_tipo"><?php esc_html_e('Tipo:', 'flavor-platform'); ?></label>
                <select id="canal_tipo" name="canal_tipo" style="width: 100%;">
                    <option value="email"><?php esc_html_e('Email', 'flavor-platform'); ?></option>
                    <option value="slack"><?php esc_html_e('Slack', 'flavor-platform'); ?></option>
                    <option value="discord"><?php esc_html_e('Discord', 'flavor-platform'); ?></option>
                    <option value="webhook"><?php esc_html_e('Webhook Genérico', 'flavor-platform'); ?></option>
                </select>
            </div>

            <div class="form-row campo-webhook" style="display: none;">
                <label for="canal_webhook_url"><?php esc_html_e('URL del Webhook:', 'flavor-platform'); ?></label>
                <input type="url" id="canal_webhook_url" name="canal_webhook_url" style="width: 100%;" placeholder="https://hooks.slack.com/services/...">
            </div>

            <div class="form-row campo-email">
                <label for="canal_email_destinatarios"><?php esc_html_e('Destinatarios (separados por coma):', 'flavor-platform'); ?></label>
                <textarea id="canal_email_destinatarios" name="canal_email_destinatarios" rows="2" style="width: 100%;" placeholder="admin@ejemplo.com, dev@ejemplo.com"></textarea>
            </div>

            <div class="form-row">
                <label for="canal_severidad_minima"><?php esc_html_e('Severidad mínima para notificar:', 'flavor-platform'); ?></label>
                <select id="canal_severidad_minima" name="canal_severidad_minima" style="width: 100%;">
                    <option value="critical"><?php esc_html_e('Solo Críticos', 'flavor-platform'); ?></option>
                    <option value="high" selected><?php esc_html_e('Alta y superior', 'flavor-platform'); ?></option>
                    <option value="medium"><?php esc_html_e('Media y superior', 'flavor-platform'); ?></option>
                    <option value="low"><?php esc_html_e('Baja y superior', 'flavor-platform'); ?></option>
                    <option value="info"><?php esc_html_e('Todos', 'flavor-platform'); ?></option>
                </select>
            </div>

            <div class="form-row">
                <div class="checkbox-row">
                    <input type="checkbox" id="canal_activo" name="canal_activo" checked>
                    <label for="canal_activo"><?php esc_html_e('Canal activo', 'flavor-platform'); ?></label>
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="button modal-close"><?php esc_html_e('Cancelar', 'flavor-platform'); ?></button>
                <button type="submit" name="guardar_canal" class="button button-primary"><?php esc_html_e('Guardar Canal', 'flavor-platform'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var $modal = $('#modal-canal');

    // Mostrar/ocultar campos según tipo
    function toggleCamposTipo() {
        var tipo = $('#canal_tipo').val();
        if (tipo === 'email') {
            $('.campo-email').show();
            $('.campo-webhook').hide();
        } else {
            $('.campo-email').hide();
            $('.campo-webhook').show();
        }
    }

    $('#canal_tipo').on('change', toggleCamposTipo);

    // Nuevo canal
    $('#btn-nuevo-canal').on('click', function() {
        $('#modal-titulo').text('<?php echo esc_js(__('Nuevo Canal', 'flavor-platform')); ?>');
        $('#form-canal')[0].reset();
        $('#canal_id').val(0);
        $('#canal_activo').prop('checked', true);
        toggleCamposTipo();
        $modal.addClass('active');
    });

    // Editar canal
    $('.btn-editar-canal').on('click', function() {
        var canal = $(this).data('canal');
        $('#modal-titulo').text('<?php echo esc_js(__('Editar Canal', 'flavor-platform')); ?>');
        $('#canal_id').val(canal.id);
        $('#canal_nombre').val(canal.nombre);
        $('#canal_tipo').val(canal.tipo);
        $('#canal_webhook_url').val(canal.webhook_url || '');
        $('#canal_email_destinatarios').val(canal.email_destinatarios || '');
        $('#canal_severidad_minima').val(canal.severidad_minima);
        $('#canal_activo').prop('checked', canal.activo == 1);
        toggleCamposTipo();
        $modal.addClass('active');
    });

    // Cerrar modal
    $('.modal-close, .modal-overlay').on('click', function(e) {
        if (e.target === this) {
            $modal.removeClass('active');
        }
    });

    // Inicializar
    toggleCamposTipo();
});
</script>
