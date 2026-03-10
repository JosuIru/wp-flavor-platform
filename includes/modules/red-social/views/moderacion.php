<?php
/**
 * Vista Admin: Moderación de Red Social
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas
$tabla_posts = $wpdb->prefix . 'flavor_red_social_posts';
$tabla_reportes = $wpdb->prefix . 'flavor_red_social_reportes';
$tabla_comentarios = $wpdb->prefix . 'flavor_red_social_comentarios';

// Verificar existencia de tablas
$tabla_posts_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_posts'") === $tabla_posts;
$tabla_reportes_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_reportes'") === $tabla_reportes;

// Filtros
$tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'pendiente';

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Estadísticas de moderación
$stats = [
    'pendientes_revision' => 0,
    'reportes_pendientes' => 0,
    'moderados_hoy' => 0,
    'posts_eliminados' => 0,
];

if ($tabla_posts_existe) {
    $stats['pendientes_revision'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_posts WHERE estado = 'pendiente'");
    $stats['moderados_hoy'] = (int) $wpdb->get_var("
        SELECT COUNT(*) FROM $tabla_posts
        WHERE estado IN ('publicado', 'rechazado')
        AND DATE(fecha_modificacion) = CURDATE()
    ");
    $stats['posts_eliminados'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_posts WHERE estado = 'eliminado'");
}

if ($tabla_reportes_existe) {
    $stats['reportes_pendientes'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_reportes WHERE estado = 'pendiente'");
}

// Obtener contenido para moderar
$items_moderacion = [];
$total_items = 0;

if ($tabla_posts_existe) {
    $where = ['1=1'];
    $params = [];

    if ($tipo_filtro === 'reportes' && $tabla_reportes_existe) {
        // Mostrar posts reportados
        $sql_count = "SELECT COUNT(DISTINCT r.post_id) FROM $tabla_reportes r WHERE r.estado = 'pendiente'";
        $sql_items = "
            SELECT p.*, u.display_name,
                   COUNT(r.id) as total_reportes,
                   GROUP_CONCAT(DISTINCT r.motivo SEPARATOR ', ') as motivos
            FROM $tabla_reportes r
            INNER JOIN $tabla_posts p ON r.post_id = p.id
            LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
            WHERE r.estado = 'pendiente'
            GROUP BY r.post_id
            ORDER BY total_reportes DESC, p.fecha_creacion DESC
            LIMIT %d OFFSET %d
        ";
        $total_items = $wpdb->get_var($sql_count);
        $items_moderacion = $wpdb->get_results($wpdb->prepare($sql_items, $por_pagina, $offset));
    } else {
        // Mostrar posts pendientes de aprobación
        if ($estado_filtro) {
            $where[] = 'p.estado = %s';
            $params[] = $estado_filtro;
        }

        $where_sql = implode(' AND ', $where);

        $sql_count = "SELECT COUNT(*) FROM $tabla_posts p WHERE $where_sql";
        $sql_items = "
            SELECT p.*, u.display_name
            FROM $tabla_posts p
            LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
            WHERE $where_sql
            ORDER BY p.fecha_creacion DESC
            LIMIT %d OFFSET %d
        ";

        if (!empty($params)) {
            $total_items = $wpdb->get_var($wpdb->prepare($sql_count, $params));
            $items_moderacion = $wpdb->get_results($wpdb->prepare($sql_items, array_merge($params, [$por_pagina, $offset])));
        } else {
            $total_items = $wpdb->get_var($sql_count);
            $items_moderacion = $wpdb->get_results($wpdb->prepare($sql_items, $por_pagina, $offset));
        }
    }
}

$total_paginas = ceil($total_items / $por_pagina);

// Estados
$estados = [
    'pendiente'  => __('Pendiente', 'flavor-chat-ia'),
    'publicado'  => __('Publicado', 'flavor-chat-ia'),
    'rechazado'  => __('Rechazado', 'flavor-chat-ia'),
    'eliminado'  => __('Eliminado', 'flavor-chat-ia'),
];

$colores_estado = [
    'pendiente'  => '#dba617',
    'publicado'  => '#00a32a',
    'rechazado'  => '#d63638',
    'eliminado'  => '#787c82',
];

// Motivos de reporte
$motivos_reporte = [
    'spam'          => __('Spam', 'flavor-chat-ia'),
    'acoso'         => __('Acoso', 'flavor-chat-ia'),
    'contenido_inadecuado' => __('Contenido inadecuado', 'flavor-chat-ia'),
    'desinformacion' => __('Desinformación', 'flavor-chat-ia'),
    'otro'          => __('Otro', 'flavor-chat-ia'),
];
?>

<div class="wrap">
    <!-- Migas de pan -->
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=flavor-red-social-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-share" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Red Social', 'flavor-chat-ia'); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Moderación', 'flavor-chat-ia'); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-shield"></span>
        <?php _e('Moderación de Contenido', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Tarjetas de estadísticas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #dba617; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['pendientes_revision']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Pendientes de revisión', 'flavor-chat-ia'); ?></div>
        </div>
        <?php if ($tabla_reportes_existe): ?>
        <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #d63638; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['reportes_pendientes']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Reportes pendientes', 'flavor-chat-ia'); ?></div>
        </div>
        <?php endif; ?>
        <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['moderados_hoy']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Moderados hoy', 'flavor-chat-ia'); ?></div>
        </div>
        <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #787c82; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['posts_eliminados']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Eliminados', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <!-- Pestañas de tipo -->
    <div style="margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=flavor-red-social-moderacion&estado=pendiente'); ?>"
           class="button <?php echo ($tipo_filtro !== 'reportes' && $estado_filtro === 'pendiente') ? 'button-primary' : ''; ?>">
            <span class="dashicons dashicons-clock" style="margin-top: 3px;"></span>
            <?php _e('Pendientes', 'flavor-chat-ia'); ?>
            <?php if ($stats['pendientes_revision'] > 0): ?>
            <span style="background: #d63638; color: #fff; padding: 0 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;">
                <?php echo number_format($stats['pendientes_revision']); ?>
            </span>
            <?php endif; ?>
        </a>
        <?php if ($tabla_reportes_existe): ?>
        <a href="<?php echo admin_url('admin.php?page=flavor-red-social-moderacion&tipo=reportes'); ?>"
           class="button <?php echo $tipo_filtro === 'reportes' ? 'button-primary' : ''; ?>">
            <span class="dashicons dashicons-flag" style="margin-top: 3px;"></span>
            <?php _e('Reportes', 'flavor-chat-ia'); ?>
            <?php if ($stats['reportes_pendientes'] > 0): ?>
            <span style="background: #d63638; color: #fff; padding: 0 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;">
                <?php echo number_format($stats['reportes_pendientes']); ?>
            </span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <a href="<?php echo admin_url('admin.php?page=flavor-red-social-moderacion&estado=rechazado'); ?>"
           class="button <?php echo $estado_filtro === 'rechazado' ? 'button-primary' : ''; ?>">
            <span class="dashicons dashicons-no" style="margin-top: 3px;"></span>
            <?php _e('Rechazados', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=flavor-red-social-moderacion&estado=eliminado'); ?>"
           class="button <?php echo $estado_filtro === 'eliminado' ? 'button-primary' : ''; ?>">
            <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
            <?php _e('Eliminados', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <!-- Lista de contenido -->
    <div class="tablenav top">
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(_n('%s elemento', '%s elementos', $total_items, 'flavor-chat-ia'), number_format($total_items)); ?>
            </span>
        </div>
    </div>

    <?php if (empty($items_moderacion)): ?>
    <div style="background: #fff; padding: 40px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <span class="dashicons dashicons-yes-alt" style="font-size: 48px; color: #00a32a;"></span>
        <p style="color: #646970; font-size: 14px;"><?php _e('No hay contenido pendiente de moderación.', 'flavor-chat-ia'); ?></p>
    </div>
    <?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 15px;">
        <?php foreach ($items_moderacion as $item): ?>
        <div style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid <?php echo esc_attr($colores_estado[$item->estado ?? 'pendiente']); ?>; padding: 15px 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
                <div style="flex: 1;">
                    <!-- Cabecera -->
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <?php echo get_avatar($item->usuario_id, 36); ?>
                        <div>
                            <strong><?php echo esc_html($item->display_name ?: __('Usuario eliminado', 'flavor-chat-ia')); ?></strong>
                            <br>
                            <small style="color: #646970;">
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->fecha_creacion))); ?>
                            </small>
                        </div>
                        <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; background: <?php echo esc_attr($colores_estado[$item->estado ?? 'pendiente']); ?>; color: #fff; margin-left: auto;">
                            <?php echo esc_html($estados[$item->estado ?? 'pendiente'] ?? ucfirst($item->estado ?? 'pendiente')); ?>
                        </span>
                    </div>

                    <!-- Contenido -->
                    <div style="background: #f6f7f7; padding: 12px; border-radius: 4px; margin-bottom: 10px;">
                        <?php
                        $contenido = wp_strip_all_tags($item->contenido ?? '');
                        $contenido_mostrar = mb_strlen($contenido) > 300 ? mb_substr($contenido, 0, 300) . '...' : $contenido;
                        echo nl2br(esc_html($contenido_mostrar));
                        ?>
                    </div>

                    <?php if (!empty($item->total_reportes)): ?>
                    <!-- Info de reportes -->
                    <div style="background: #fcf0f1; border: 1px solid #d63638; padding: 10px 12px; border-radius: 4px; margin-bottom: 10px;">
                        <strong style="color: #d63638;">
                            <span class="dashicons dashicons-flag" style="font-size: 16px;"></span>
                            <?php printf(_n('%s reporte', '%s reportes', $item->total_reportes, 'flavor-chat-ia'), number_format($item->total_reportes)); ?>
                        </strong>
                        <?php if (!empty($item->motivos)): ?>
                        <br><small style="color: #646970;"><?php _e('Motivos:', 'flavor-chat-ia'); ?> <?php echo esc_html($item->motivos); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Acciones -->
                <div style="display: flex; flex-direction: column; gap: 8px; min-width: 140px;">
                    <?php if ($item->estado === 'pendiente'): ?>
                    <button type="button" class="button button-primary" style="width: 100%;"
                            onclick="flavorModerar(<?php echo esc_attr($item->id); ?>, 'aprobar')">
                        <span class="dashicons dashicons-yes" style="margin-top: 3px;"></span>
                        <?php _e('Aprobar', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="button" class="button" style="width: 100%; color: #d63638; border-color: #d63638;"
                            onclick="flavorModerar(<?php echo esc_attr($item->id); ?>, 'rechazar')">
                        <span class="dashicons dashicons-no" style="margin-top: 3px;"></span>
                        <?php _e('Rechazar', 'flavor-chat-ia'); ?>
                    </button>
                    <?php endif; ?>

                    <?php if ($item->estado !== 'eliminado'): ?>
                    <button type="button" class="button" style="width: 100%;"
                            onclick="flavorModerar(<?php echo esc_attr($item->id); ?>, 'eliminar')">
                        <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
                        <?php _e('Eliminar', 'flavor-chat-ia'); ?>
                    </button>
                    <?php else: ?>
                    <button type="button" class="button" style="width: 100%;"
                            onclick="flavorModerar(<?php echo esc_attr($item->id); ?>, 'restaurar')">
                        <span class="dashicons dashicons-undo" style="margin-top: 3px;"></span>
                        <?php _e('Restaurar', 'flavor-chat-ia'); ?>
                    </button>
                    <?php endif; ?>

                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $item->usuario_id); ?>"
                       class="button" style="width: 100%; text-align: center;">
                        <span class="dashicons dashicons-admin-users" style="margin-top: 3px;"></span>
                        <?php _e('Ver usuario', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_paginas,
                'current' => $pagina_actual,
            ]);
            ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Reglas de moderación -->
    <div class="postbox" style="margin-top: 30px;">
        <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
            <span class="dashicons dashicons-info"></span>
            <?php _e('Guía de Moderación', 'flavor-chat-ia'); ?>
        </h2>
        <div class="inside">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <h4 style="margin: 0 0 10px; color: #00a32a;">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Contenido permitido', 'flavor-chat-ia'); ?>
                    </h4>
                    <ul style="margin: 0; padding-left: 20px; color: #646970;">
                        <li><?php _e('Publicaciones respetuosas', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Contenido original', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Debates constructivos', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Información verificable', 'flavor-chat-ia'); ?></li>
                    </ul>
                </div>
                <div>
                    <h4 style="margin: 0 0 10px; color: #d63638;">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php _e('Contenido prohibido', 'flavor-chat-ia'); ?>
                    </h4>
                    <ul style="margin: 0; padding-left: 20px; color: #646970;">
                        <li><?php _e('Spam y publicidad no autorizada', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Contenido ofensivo o de odio', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Acoso a otros usuarios', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Desinformación', 'flavor-chat-ia'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function flavorModerar(postId, accion) {
    if (!confirm('<?php echo esc_js(__('¿Confirmar esta acción de moderación?', 'flavor-chat-ia')); ?>')) {
        return;
    }

    // Aquí iría la llamada AJAX para moderar
    console.log('Moderando post', postId, 'con acción', accion);

    // Por ahora, recargar la página
    // En producción, usar AJAX y actualizar la UI sin recargar
    // window.location.reload();

    alert('<?php echo esc_js(__('Acción de moderación registrada. Implementar endpoint AJAX para guardar.', 'flavor-chat-ia')); ?>');
}
</script>
