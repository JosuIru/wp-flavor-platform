<?php
/**
 * Vista Admin: Feed de Actividad de Comunidades
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
$tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';
$tabla_publicaciones = $wpdb->prefix . 'flavor_comunidades_publicaciones';
$tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

// Verificar si las tablas existen
$tabla_actividad_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_actividad}'") === $tabla_actividad;
$tabla_publicaciones_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_publicaciones}'") === $tabla_publicaciones;

// Filtros
$comunidad_filtro = isset($_GET['comunidad']) ? absint($_GET['comunidad']) : 0;
$tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$periodo = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : '7';

// Paginación
$por_pagina = 30;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Fecha de inicio según periodo
$fecha_inicio = date('Y-m-d H:i:s', strtotime("-{$periodo} days"));

// Obtener comunidades para el filtro
$comunidades = $wpdb->get_results("SELECT id, nombre FROM {$tabla_comunidades} WHERE estado = 'activa' ORDER BY nombre ASC");

// Tipos de actividad
$tipos_actividad = [
    'publicacion' => __('Publicación', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'comentario'  => __('Comentario', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'reaccion'    => __('Reacción', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'nuevo_miembro' => __('Nuevo miembro', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'evento'      => __('Evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$iconos_tipo = [
    'publicacion' => 'dashicons-format-status',
    'comentario'  => 'dashicons-admin-comments',
    'reaccion'    => 'dashicons-heart',
    'nuevo_miembro' => 'dashicons-groups',
    'evento'      => 'dashicons-calendar-alt',
];

$colores_tipo = [
    'publicacion' => '#3b82f6',
    'comentario'  => '#10b981',
    'reaccion'    => '#ef4444',
    'nuevo_miembro' => '#8b5cf6',
    'evento'      => '#f59e0b',
];

// Obtener actividades
$actividades = [];
$total_items = 0;

if ($tabla_actividad_existe) {
    $where = ["a.created_at >= %s"];
    $params = [$fecha_inicio];

    if ($comunidad_filtro) {
        $where[] = 'a.comunidad_id = %d';
        $params[] = $comunidad_filtro;
    }

    if ($tipo_filtro) {
        $where[] = 'a.tipo = %s';
        $params[] = $tipo_filtro;
    }

    $where_sql = implode(' AND ', $where);

    $sql_count = "SELECT COUNT(*) FROM {$tabla_actividad} a WHERE {$where_sql}";
    $sql_items = "
        SELECT a.*, u.display_name, c.nombre as comunidad_nombre
        FROM {$tabla_actividad} a
        LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
        LEFT JOIN {$tabla_comunidades} c ON a.comunidad_id = c.id
        WHERE {$where_sql}
        ORDER BY a.created_at DESC
        LIMIT %d OFFSET %d
    ";

    $total_items = $wpdb->get_var($wpdb->prepare($sql_count, $params));
    $actividades = $wpdb->get_results($wpdb->prepare($sql_items, array_merge($params, [$por_pagina, $offset])));
} elseif ($tabla_publicaciones_existe) {
    // Fallback: usar tabla de publicaciones si no existe tabla de actividad
    $where = ["p.created_at >= %s"];
    $params = [$fecha_inicio];

    if ($comunidad_filtro) {
        $where[] = 'p.comunidad_id = %d';
        $params[] = $comunidad_filtro;
    }

    $where_sql = implode(' AND ', $where);

    $sql_count = "SELECT COUNT(*) FROM {$tabla_publicaciones} p WHERE {$where_sql}";
    $sql_items = "
        SELECT p.id, p.user_id, p.comunidad_id, p.contenido, p.created_at, 'publicacion' as tipo,
               u.display_name, c.nombre as comunidad_nombre
        FROM {$tabla_publicaciones} p
        LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
        LEFT JOIN {$tabla_comunidades} c ON p.comunidad_id = c.id
        WHERE {$where_sql}
        ORDER BY p.created_at DESC
        LIMIT %d OFFSET %d
    ";

    $total_items = $wpdb->get_var($wpdb->prepare($sql_count, $params));
    $actividades = $wpdb->get_results($wpdb->prepare($sql_items, array_merge($params, [$por_pagina, $offset])));
}

$total_paginas = ceil($total_items / $por_pagina);

// Estadísticas rápidas
$stats = [
    'total_actividad' => 0,
    'publicaciones_hoy' => 0,
    'usuarios_activos' => 0,
    'comunidades_activas' => 0,
];

if ($tabla_actividad_existe) {
    $stats['total_actividad'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_actividad} WHERE created_at >= %s",
        $fecha_inicio
    ));
    $stats['publicaciones_hoy'] = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_actividad} WHERE DATE(created_at) = CURDATE() AND tipo = 'publicacion'"
    );
    $stats['usuarios_activos'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM {$tabla_actividad} WHERE created_at >= %s",
        $fecha_inicio
    ));
    $stats['comunidades_activas'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT comunidad_id) FROM {$tabla_actividad} WHERE created_at >= %s",
        $fecha_inicio
    ));
} elseif ($tabla_publicaciones_existe) {
    $stats['total_actividad'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_publicaciones} WHERE created_at >= %s",
        $fecha_inicio
    ));
    $stats['publicaciones_hoy'] = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_publicaciones} WHERE DATE(created_at) = CURDATE()"
    );
    $stats['usuarios_activos'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM {$tabla_publicaciones} WHERE created_at >= %s",
        $fecha_inicio
    ));
    $stats['comunidades_activas'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT comunidad_id) FROM {$tabla_publicaciones} WHERE created_at >= %s",
        $fecha_inicio
    ));
}
?>

<div class="wrap">
    <!-- Migas de pan -->
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=comunidades-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-admin-multisite" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Feed de Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-rss"></span>
        <?php _e('Feed de Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Estadísticas rápidas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #3b82f6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['total_actividad']); ?></div>
            <div style="color: #646970; font-size: 13px;">
                <?php printf(__('Actividad en %s días', FLAVOR_PLATFORM_TEXT_DOMAIN), $periodo); ?>
            </div>
        </div>
        <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #10b981; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['publicaciones_hoy']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Publicaciones hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #8b5cf6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['usuarios_activos']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Usuarios activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #f59e0b; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['comunidades_activas']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Comunidades activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="comunidades-actividad">

            <div class="alignleft actions" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <select name="periodo" onchange="this.form.submit()">
                    <option value="1" <?php selected($periodo, '1'); ?>><?php _e('Últimas 24 horas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="7" <?php selected($periodo, '7'); ?>><?php _e('Últimos 7 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="30" <?php selected($periodo, '30'); ?>><?php _e('Últimos 30 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="90" <?php selected($periodo, '90'); ?>><?php _e('Últimos 90 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>

                <select name="comunidad">
                    <option value=""><?php _e('Todas las comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($comunidades as $com): ?>
                    <option value="<?php echo esc_attr($com->id); ?>" <?php selected($comunidad_filtro, $com->id); ?>>
                        <?php echo esc_html($com->nombre); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <?php if ($tabla_actividad_existe): ?>
                <select name="tipo">
                    <option value=""><?php _e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($tipos_actividad as $slug => $label): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($tipo_filtro, $slug); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <button type="submit" class="button"><?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

                <?php if ($comunidad_filtro || $tipo_filtro || $periodo !== '7'): ?>
                <a href="<?php echo admin_url('admin.php?page=comunidades-actividad'); ?>" class="button"><?php _e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                <?php endif; ?>
            </div>
        </form>

        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(_n('%s elemento', '%s elementos', $total_items, FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($total_items)); ?>
            </span>
        </div>
    </div>

    <!-- Feed de actividad -->
    <?php if (empty($actividades)): ?>
    <div style="background: #fff; padding: 40px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <span class="dashicons dashicons-rss" style="font-size: 48px; color: #c3c4c7;"></span>
        <p style="color: #646970; font-size: 14px;"><?php _e('No hay actividad en el periodo seleccionado.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 1px; background: #c3c4c7;">
        <?php foreach ($actividades as $actividad): ?>
        <?php
        $tipo = $actividad->tipo ?? 'publicacion';
        $icono = $iconos_tipo[$tipo] ?? 'dashicons-marker';
        $color = $colores_tipo[$tipo] ?? '#646970';
        ?>
        <div style="background: #fff; padding: 15px 20px; display: flex; gap: 15px; align-items: flex-start;">
            <!-- Avatar -->
            <div style="flex-shrink: 0;">
                <?php echo get_avatar($actividad->user_id ?? 0, 40); ?>
            </div>

            <!-- Contenido -->
            <div style="flex: 1; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap;">
                    <strong style="color: #1d2327;">
                        <?php echo esc_html($actividad->display_name ?: __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                    </strong>
                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 3px; font-size: 11px; background: <?php echo esc_attr($color); ?>20; color: <?php echo esc_attr($color); ?>;">
                        <span class="dashicons <?php echo esc_attr($icono); ?>" style="font-size: 12px; width: 12px; height: 12px;"></span>
                        <?php echo esc_html($tipos_actividad[$tipo] ?? ucfirst($tipo)); ?>
                    </span>
                    <span style="color: #646970; font-size: 12px;">
                        <?php _e('en', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <a href="<?php echo admin_url('admin.php?page=comunidades-editar&id=' . $actividad->comunidad_id); ?>" style="color: #2271b1; text-decoration: none;">
                            <?php echo esc_html($actividad->comunidad_nombre ?: __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                        </a>
                    </span>
                </div>

                <?php if (!empty($actividad->contenido)): ?>
                <div style="color: #1d2327; line-height: 1.5; margin-bottom: 8px;">
                    <?php
                    $contenido = wp_strip_all_tags($actividad->contenido);
                    $contenido_corto = mb_strlen($contenido) > 200 ? mb_substr($contenido, 0, 200) . '...' : $contenido;
                    echo nl2br(esc_html($contenido_corto));
                    ?>
                </div>
                <?php endif; ?>

                <div style="color: #646970; font-size: 12px;">
                    <span class="dashicons dashicons-clock" style="font-size: 12px; width: 12px; height: 12px; vertical-align: middle;"></span>
                    <?php
                    $fecha = strtotime($actividad->created_at ?? 'now');
                    echo esc_html(human_time_diff($fecha, current_time('timestamp'))) . ' ' . __('ago', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    ?>
                    <span style="margin-left: 10px;" title="<?php echo esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $fecha)); ?>">
                        <?php echo esc_html(date_i18n('d M Y, H:i', $fecha)); ?>
                    </span>
                </div>
            </div>

            <!-- Acciones -->
            <div style="flex-shrink: 0;">
                <a href="<?php echo admin_url('admin.php?page=comunidades-publicaciones&comunidad=' . $actividad->comunidad_id); ?>"
                   class="button button-small" title="<?php esc_attr_e('Ver publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-visibility" style="margin-top: 3px;"></span>
                </a>
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
</div>
