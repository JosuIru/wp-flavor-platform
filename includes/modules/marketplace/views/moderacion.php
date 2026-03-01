<?php
/**
 * Vista Moderacion - Marketplace
 *
 * Panel de moderacion de anuncios y gestion de reportes
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_reportes = $wpdb->prefix . 'flavor_marketplace_reportes';

// Procesar acciones de moderacion
if (isset($_POST['accion_moderacion']) && isset($_POST['anuncio_id']) && wp_verify_nonce($_POST['_wpnonce'], 'moderacion_marketplace')) {
    $anuncio_id = absint($_POST['anuncio_id']);
    $accion = sanitize_text_field($_POST['accion_moderacion']);

    switch ($accion) {
        case 'aprobar':
            wp_update_post([
                'ID' => $anuncio_id,
                'post_status' => 'publish'
            ]);
            $mensaje_exito = __('Anuncio aprobado correctamente.', 'flavor-chat-ia');
            break;

        case 'rechazar':
            wp_update_post([
                'ID' => $anuncio_id,
                'post_status' => 'draft'
            ]);
            // Notificar al autor
            $anuncio = get_post($anuncio_id);
            if ($anuncio) {
                $autor = get_userdata($anuncio->post_author);
                if ($autor) {
                    $motivo = isset($_POST['motivo_rechazo']) ? sanitize_textarea_field($_POST['motivo_rechazo']) : '';
                    wp_mail(
                        $autor->user_email,
                        __('Tu anuncio ha sido rechazado', 'flavor-chat-ia'),
                        sprintf(
                            __("Hola %s,\n\nTu anuncio \"%s\" ha sido rechazado por los moderadores.\n\nMotivo: %s\n\nPuedes editarlo y volver a enviarlo para revision.", 'flavor-chat-ia'),
                            $autor->display_name,
                            $anuncio->post_title,
                            $motivo ?: __('No especificado', 'flavor-chat-ia')
                        )
                    );
                }
            }
            $mensaje_exito = __('Anuncio rechazado y autor notificado.', 'flavor-chat-ia');
            break;

        case 'eliminar':
            wp_trash_post($anuncio_id);
            $mensaje_exito = __('Anuncio movido a la papelera.', 'flavor-chat-ia');
            break;
    }
}

// Procesar acciones de reportes
if (isset($_POST['accion_reporte']) && isset($_POST['reporte_id']) && wp_verify_nonce($_POST['_wpnonce'], 'reporte_marketplace')) {
    $reporte_id = absint($_POST['reporte_id']);
    $accion = sanitize_text_field($_POST['accion_reporte']);

    // Verificar si existe la tabla de reportes
    $tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_reportes}'") === $tabla_reportes;

    if ($tabla_existe) {
        switch ($accion) {
            case 'resolver':
                $wpdb->update($tabla_reportes, ['estado' => 'resuelto', 'fecha_resolucion' => current_time('mysql')], ['id' => $reporte_id]);
                $mensaje_exito = __('Reporte marcado como resuelto.', 'flavor-chat-ia');
                break;

            case 'descartar':
                $wpdb->update($tabla_reportes, ['estado' => 'descartado', 'fecha_resolucion' => current_time('mysql')], ['id' => $reporte_id]);
                $mensaje_exito = __('Reporte descartado.', 'flavor-chat-ia');
                break;

            case 'eliminar_anuncio':
                $reporte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla_reportes} WHERE id = %d", $reporte_id));
                if ($reporte && $reporte->anuncio_id) {
                    wp_trash_post($reporte->anuncio_id);
                    $wpdb->update($tabla_reportes, ['estado' => 'resuelto', 'fecha_resolucion' => current_time('mysql')], ['id' => $reporte_id]);
                    $mensaje_exito = __('Anuncio eliminado y reporte resuelto.', 'flavor-chat-ia');
                }
                break;

            case 'suspender_usuario':
                $reporte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tabla_reportes} WHERE id = %d", $reporte_id));
                if ($reporte && $reporte->usuario_reportado_id) {
                    update_user_meta($reporte->usuario_reportado_id, '_marketplace_suspendido', 1);
                    update_user_meta($reporte->usuario_reportado_id, '_marketplace_fecha_suspension', current_time('mysql'));
                    $wpdb->update($tabla_reportes, ['estado' => 'resuelto', 'fecha_resolucion' => current_time('mysql')], ['id' => $reporte_id]);
                    $mensaje_exito = __('Usuario suspendido del marketplace.', 'flavor-chat-ia');
                }
                break;
        }
    }
}

// Obtener anuncios pendientes de moderacion
$anuncios_pendientes = get_posts([
    'post_type'      => 'marketplace_item',
    'post_status'    => 'pending',
    'posts_per_page' => 50,
    'orderby'        => 'date',
    'order'          => 'ASC',
]);

// Verificar y obtener reportes si existe la tabla
$tabla_reportes_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_reportes}'") === $tabla_reportes;
$reportes_pendientes = [];
$total_reportes_pendientes = 0;

if ($tabla_reportes_existe) {
    $reportes_pendientes = $wpdb->get_results(
        "SELECT r.*, p.post_title as anuncio_titulo, u1.display_name as reportador_nombre, u2.display_name as reportado_nombre
         FROM {$tabla_reportes} r
         LEFT JOIN {$wpdb->posts} p ON r.anuncio_id = p.ID
         LEFT JOIN {$wpdb->users} u1 ON r.usuario_reportador_id = u1.ID
         LEFT JOIN {$wpdb->users} u2 ON r.usuario_reportado_id = u2.ID
         WHERE r.estado = 'pendiente'
         ORDER BY r.fecha_reporte DESC
         LIMIT 50"
    );

    $total_reportes_pendientes = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_reportes} WHERE estado = 'pendiente'"
    );
}

// Estadisticas de moderacion
$total_pendientes = count($anuncios_pendientes);
$aprobados_hoy = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts}
     WHERE post_type = 'marketplace_item'
     AND post_status = 'publish'
     AND post_modified >= %s",
    date('Y-m-d 00:00:00')
));

$rechazados_hoy = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts}
     WHERE post_type = 'marketplace_item'
     AND post_status = 'draft'
     AND post_modified >= %s",
    date('Y-m-d 00:00:00')
));

// Tab activa
$tab_activa = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'pendientes';
?>

<div class="wrap flavor-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-shield"></span>
        <?php esc_html_e('Moderacion del Marketplace', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <?php if (!empty($mensaje_exito)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($mensaje_exito); ?></p>
        </div>
    <?php endif; ?>

    <!-- KPIs de moderacion -->
    <div class="moderacion-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; border-left: 4px solid #dba617; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0; font-size: 28px; color: #dba617;"><?php echo esc_html($total_pendientes); ?></h3>
            <p style="margin: 5px 0 0; color: #666;"><?php esc_html_e('Pendientes de Revision', 'flavor-chat-ia'); ?></p>
        </div>
        <div style="background: #fff; border-left: 4px solid #00a32a; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0; font-size: 28px; color: #00a32a;"><?php echo esc_html($aprobados_hoy); ?></h3>
            <p style="margin: 5px 0 0; color: #666;"><?php esc_html_e('Aprobados Hoy', 'flavor-chat-ia'); ?></p>
        </div>
        <div style="background: #fff; border-left: 4px solid #d63638; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0; font-size: 28px; color: #d63638;"><?php echo esc_html($rechazados_hoy); ?></h3>
            <p style="margin: 5px 0 0; color: #666;"><?php esc_html_e('Rechazados Hoy', 'flavor-chat-ia'); ?></p>
        </div>
        <div style="background: #fff; border-left: 4px solid #d63638; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0; font-size: 28px; color: #d63638;"><?php echo esc_html($total_reportes_pendientes); ?></h3>
            <p style="margin: 5px 0 0; color: #666;"><?php esc_html_e('Reportes Pendientes', 'flavor-chat-ia'); ?></p>
        </div>
    </div>

    <!-- Tabs -->
    <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
        <a href="<?php echo esc_url(add_query_arg('tab', 'pendientes')); ?>"
           class="nav-tab <?php echo $tab_activa === 'pendientes' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-clock" style="vertical-align: middle;"></span>
            <?php esc_html_e('Anuncios Pendientes', 'flavor-chat-ia'); ?>
            <?php if ($total_pendientes > 0): ?>
                <span class="count" style="background: #dba617; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 5px;">
                    <?php echo esc_html($total_pendientes); ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'reportes')); ?>"
           class="nav-tab <?php echo $tab_activa === 'reportes' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-warning" style="vertical-align: middle;"></span>
            <?php esc_html_e('Reportes de Abuso', 'flavor-chat-ia'); ?>
            <?php if ($total_reportes_pendientes > 0): ?>
                <span class="count" style="background: #d63638; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 5px;">
                    <?php echo esc_html($total_reportes_pendientes); ?>
                </span>
            <?php endif; ?>
        </a>
    </nav>

    <?php if ($tab_activa === 'pendientes'): ?>
        <!-- Tab: Anuncios Pendientes -->
        <div class="postbox">
            <h2 class="hndle" style="padding: 12px;">
                <span class="dashicons dashicons-visibility" style="margin-right: 8px;"></span>
                <?php esc_html_e('Anuncios Pendientes de Moderacion', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside">
                <?php if (!empty($anuncios_pendientes)): ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th style="width: 80px;"><?php esc_html_e('Imagen', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Anuncio', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Precio', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Autor', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                                <th style="width: 280px;"><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($anuncios_pendientes as $anuncio):
                                $tipos = wp_get_post_terms($anuncio->ID, 'marketplace_tipo');
                                $precio = get_post_meta($anuncio->ID, '_marketplace_precio', true);
                                $autor = get_userdata($anuncio->post_author);
                            ?>
                                <tr>
                                    <td>
                                        <?php if (has_post_thumbnail($anuncio->ID)): ?>
                                            <?php echo get_the_post_thumbnail($anuncio->ID, [60, 60], ['style' => 'border-radius: 4px;']); ?>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-format-image" style="font-size: 40px; color: #ccc;"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($anuncio->post_title); ?></strong>
                                        <div style="color: #666; font-size: 12px; margin-top: 5px;">
                                            <?php echo esc_html(wp_trim_words($anuncio->post_content, 20)); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($tipos)): ?>
                                            <span class="tag" style="background: #e0e0e0; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                                <?php echo esc_html($tipos[0]->name); ?>
                                            </span>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($precio): ?>
                                            <strong style="color: #27ae60;"><?php echo esc_html(number_format($precio, 2)); ?> &euro;</strong>
                                        <?php else: ?>
                                            <em style="color: #999;">—</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($autor): ?>
                                            <?php echo get_avatar($autor->ID, 24, '', '', ['style' => 'border-radius: 50%; vertical-align: middle; margin-right: 5px;']); ?>
                                            <?php echo esc_html($autor->display_name); ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(human_time_diff(strtotime($anuncio->post_date))); ?>
                                        <br>
                                        <small style="color: #666;"><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($anuncio->post_date))); ?></small>
                                    </td>
                                    <td>
                                        <form method="post" style="display: inline-block;">
                                            <?php wp_nonce_field('moderacion_marketplace'); ?>
                                            <input type="hidden" name="anuncio_id" value="<?php echo esc_attr($anuncio->ID); ?>">
                                            <button type="submit" name="accion_moderacion" value="aprobar" class="button button-primary button-small" title="<?php esc_attr_e('Aprobar', 'flavor-chat-ia'); ?>">
                                                <span class="dashicons dashicons-yes" style="vertical-align: middle;"></span>
                                                <?php esc_html_e('Aprobar', 'flavor-chat-ia'); ?>
                                            </button>
                                        </form>
                                        <button type="button" class="button button-small btn-rechazar" data-id="<?php echo esc_attr($anuncio->ID); ?>" title="<?php esc_attr_e('Rechazar', 'flavor-chat-ia'); ?>" style="color: #d63638;">
                                            <span class="dashicons dashicons-no" style="vertical-align: middle;"></span>
                                            <?php esc_html_e('Rechazar', 'flavor-chat-ia'); ?>
                                        </button>
                                        <a href="<?php echo esc_url(get_edit_post_link($anuncio->ID)); ?>" class="button button-small" title="<?php esc_attr_e('Editar', 'flavor-chat-ia'); ?>">
                                            <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <span class="dashicons dashicons-yes-alt" style="font-size: 48px; color: #00a32a;"></span>
                        <h3><?php esc_html_e('No hay anuncios pendientes', 'flavor-chat-ia'); ?></h3>
                        <p><?php esc_html_e('Todos los anuncios han sido revisados.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($tab_activa === 'reportes'): ?>
        <!-- Tab: Reportes de Abuso -->
        <div class="postbox">
            <h2 class="hndle" style="padding: 12px;">
                <span class="dashicons dashicons-warning" style="margin-right: 8px; color: #d63638;"></span>
                <?php esc_html_e('Reportes de Fraude y Abusos', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside">
                <?php if (!$tabla_reportes_existe): ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <span class="dashicons dashicons-info" style="font-size: 48px; color: #dba617;"></span>
                        <h3><?php esc_html_e('Sistema de reportes no configurado', 'flavor-chat-ia'); ?></h3>
                        <p><?php esc_html_e('La tabla de reportes no existe. Ejecuta la instalacion del modulo para crearla.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php elseif (!empty($reportes_pendientes)): ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Anuncio/Usuario', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Motivo', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Reportado por', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                                <th style="width: 250px;"><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportes_pendientes as $reporte):
                                $iconos_tipo = [
                                    'fraude' => 'dashicons-flag',
                                    'spam' => 'dashicons-megaphone',
                                    'contenido_inapropiado' => 'dashicons-hidden',
                                    'falso' => 'dashicons-dismiss',
                                    'otro' => 'dashicons-warning',
                                ];
                                $colores_tipo = [
                                    'fraude' => '#d63638',
                                    'spam' => '#dba617',
                                    'contenido_inapropiado' => '#826eb4',
                                    'falso' => '#00a0d2',
                                    'otro' => '#666',
                                ];
                                $tipo_reporte = $reporte->tipo ?? 'otro';
                            ?>
                                <tr>
                                    <td>
                                        <span class="dashicons <?php echo esc_attr($iconos_tipo[$tipo_reporte] ?? 'dashicons-warning'); ?>"
                                              style="color: <?php echo esc_attr($colores_tipo[$tipo_reporte] ?? '#666'); ?>; margin-right: 5px;"></span>
                                        <strong style="text-transform: capitalize;">
                                            <?php echo esc_html(str_replace('_', ' ', $tipo_reporte)); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($reporte->anuncio_id && $reporte->anuncio_titulo): ?>
                                            <a href="<?php echo esc_url(get_edit_post_link($reporte->anuncio_id)); ?>">
                                                <?php echo esc_html($reporte->anuncio_titulo); ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($reporte->reportado_nombre): ?>
                                            <br><small style="color: #666;"><?php esc_html_e('Usuario:', 'flavor-chat-ia'); ?> <?php echo esc_html($reporte->reportado_nombre); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($reporte->descripcion ?? '—'); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($reporte->reportador_nombre ?? __('Anonimo', 'flavor-chat-ia')); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(human_time_diff(strtotime($reporte->fecha_reporte))); ?>
                                        <br>
                                        <small style="color: #666;"><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($reporte->fecha_reporte))); ?></small>
                                    </td>
                                    <td>
                                        <form method="post" style="display: inline-flex; gap: 5px; flex-wrap: wrap;">
                                            <?php wp_nonce_field('reporte_marketplace'); ?>
                                            <input type="hidden" name="reporte_id" value="<?php echo esc_attr($reporte->id); ?>">

                                            <button type="submit" name="accion_reporte" value="resolver" class="button button-small" title="<?php esc_attr_e('Marcar como resuelto', 'flavor-chat-ia'); ?>">
                                                <span class="dashicons dashicons-yes" style="vertical-align: middle;"></span>
                                            </button>
                                            <button type="submit" name="accion_reporte" value="descartar" class="button button-small" title="<?php esc_attr_e('Descartar reporte', 'flavor-chat-ia'); ?>">
                                                <span class="dashicons dashicons-dismiss" style="vertical-align: middle;"></span>
                                            </button>
                                            <?php if ($reporte->anuncio_id): ?>
                                                <button type="submit" name="accion_reporte" value="eliminar_anuncio" class="button button-small" style="color: #d63638;" title="<?php esc_attr_e('Eliminar anuncio', 'flavor-chat-ia'); ?>"
                                                        onclick="return confirm('<?php esc_attr_e('Seguro que deseas eliminar este anuncio?', 'flavor-chat-ia'); ?>');">
                                                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($reporte->usuario_reportado_id): ?>
                                                <button type="submit" name="accion_reporte" value="suspender_usuario" class="button button-small" style="color: #d63638;" title="<?php esc_attr_e('Suspender usuario', 'flavor-chat-ia'); ?>"
                                                        onclick="return confirm('<?php esc_attr_e('Seguro que deseas suspender a este usuario del marketplace?', 'flavor-chat-ia'); ?>');">
                                                    <span class="dashicons dashicons-admin-users" style="vertical-align: middle;"></span>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <span class="dashicons dashicons-shield" style="font-size: 48px; color: #00a32a;"></span>
                        <h3><?php esc_html_e('No hay reportes pendientes', 'flavor-chat-ia'); ?></h3>
                        <p><?php esc_html_e('No se han reportado problemas recientemente.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para rechazar anuncio -->
<div id="modal-rechazar" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%;">
        <h3 style="margin-top: 0;">
            <span class="dashicons dashicons-no" style="color: #d63638;"></span>
            <?php esc_html_e('Rechazar Anuncio', 'flavor-chat-ia'); ?>
        </h3>
        <form method="post">
            <?php wp_nonce_field('moderacion_marketplace'); ?>
            <input type="hidden" name="anuncio_id" id="rechazar-anuncio-id" value="">
            <input type="hidden" name="accion_moderacion" value="rechazar">

            <p>
                <label for="motivo_rechazo"><strong><?php esc_html_e('Motivo del rechazo:', 'flavor-chat-ia'); ?></strong></label>
                <textarea name="motivo_rechazo" id="motivo_rechazo" rows="4" style="width: 100%; margin-top: 8px;"
                          placeholder="<?php esc_attr_e('Explica el motivo del rechazo...', 'flavor-chat-ia'); ?>"></textarea>
            </p>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="button" onclick="cerrarModalRechazar()">
                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                </button>
                <button type="submit" class="button button-primary" style="background: #d63638; border-color: #d63638;">
                    <?php esc_html_e('Rechazar Anuncio', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.btn-rechazar').on('click', function() {
        var anuncioId = $(this).data('id');
        $('#rechazar-anuncio-id').val(anuncioId);
        $('#modal-rechazar').css('display', 'flex');
    });
});

function cerrarModalRechazar() {
    document.getElementById('modal-rechazar').style.display = 'none';
}

document.getElementById('modal-rechazar').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalRechazar();
    }
});
</script>
