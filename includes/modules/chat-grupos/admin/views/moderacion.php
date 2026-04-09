<?php
/**
 * Vista Admin: Moderacion de Chat de Grupos
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
$tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
$tabla_reportes = $wpdb->prefix . 'flavor_chat_grupos_reportes';

// Mensajes reportados
$reportes_pendientes = $wpdb->get_results(
    "SELECT r.*, m.contenido, m.usuario_id, m.fecha_creacion as fecha_mensaje, g.nombre as grupo_nombre
     FROM $tabla_reportes r
     LEFT JOIN $tabla_mensajes m ON r.mensaje_id = m.id
     LEFT JOIN $tabla_grupos g ON m.grupo_id = g.id
     WHERE r.estado = 'pendiente'
     ORDER BY r.fecha_reporte DESC
     LIMIT 50"
);

// Estadisticas
$total_reportes_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_reportes WHERE estado = 'pendiente'");
$total_reportes_revisados = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_reportes WHERE estado != 'pendiente'");
?>

<div class="wrap flavor-chat-moderacion">
    <h1><?php _e('Moderacion de Chat', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

    <div class="flavor-stats-row">
        <div class="flavor-stat-card">
            <span class="flavor-stat-numero"><?php echo number_format_i18n($total_reportes_pendientes); ?></span>
            <span class="flavor-stat-label"><?php _e('Reportes pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="flavor-stat-card">
            <span class="flavor-stat-numero"><?php echo number_format_i18n($total_reportes_revisados); ?></span>
            <span class="flavor-stat-label"><?php _e('Reportes revisados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <h2><?php _e('Mensajes reportados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

    <?php if (empty($reportes_pendientes)): ?>
    <div class="flavor-empty-state">
        <span class="dashicons dashicons-yes-alt"></span>
        <p><?php _e('No hay mensajes reportados pendientes de revision.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php else: ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="column-primary"><?php _e('Mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Grupo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Motivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reportes_pendientes as $reporte):
                $usuario = get_userdata($reporte->usuario_id);
                $reportador = get_userdata($reporte->reportado_por);
            ?>
            <tr data-reporte-id="<?php echo esc_attr($reporte->id); ?>">
                <td class="column-primary">
                    <div class="flavor-mensaje-contenido">
                        <?php echo esc_html(wp_trim_words($reporte->contenido ?? '[Mensaje eliminado]', 20)); ?>
                    </div>
                    <small class="flavor-mensaje-fecha">
                        <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($reporte->fecha_mensaje ?? $reporte->fecha_reporte))); ?>
                    </small>
                </td>
                <td><?php echo esc_html($reporte->grupo_nombre ?? '-'); ?></td>
                <td>
                    <?php if ($usuario): ?>
                    <a href="<?php echo get_edit_user_link($usuario->ID); ?>"><?php echo esc_html($usuario->display_name); ?></a>
                    <?php else: ?>
                    -
                    <?php endif; ?>
                </td>
                <td>
                    <span class="flavor-motivo"><?php echo esc_html(ucfirst($reporte->motivo ?? 'inapropiado')); ?></span>
                    <?php if ($reportador): ?>
                    <br><small><?php printf(__('Reportado por: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($reportador->display_name)); ?></small>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($reporte->fecha_reporte))); ?></td>
                <td>
                    <button class="button button-small flavor-aprobar-reporte" data-id="<?php echo esc_attr($reporte->id); ?>" data-action="eliminar">
                        <?php _e('Eliminar mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button class="button button-small flavor-rechazar-reporte" data-id="<?php echo esc_attr($reporte->id); ?>">
                        <?php _e('Descartar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>
</div>

<style>
.flavor-stats-row {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}
.flavor-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    min-width: 150px;
}
.flavor-stat-numero {
    display: block;
    font-size: 32px;
    font-weight: 600;
    color: #2271b1;
}
.flavor-stat-label {
    display: block;
    color: #666;
    margin-top: 5px;
}
.flavor-empty-state {
    text-align: center;
    padding: 40px;
    background: #f0f6fc;
    border-radius: 4px;
}
.flavor-empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #2271b1;
}
.flavor-mensaje-contenido {
    max-width: 300px;
    word-wrap: break-word;
}
.flavor-mensaje-fecha {
    color: #666;
}
.flavor-motivo {
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}
</style>
