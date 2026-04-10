<?php
/**
 * Vista Admin: Publicaciones de Comunidades
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
$tabla_publicaciones = $wpdb->prefix . 'flavor_comunidades_publicaciones';

// Verificar si las tablas existen
$tabla_publicaciones_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_publicaciones}'") === $tabla_publicaciones;

// Filtros
$comunidad_filtro = isset($_GET['comunidad']) ? absint($_GET['comunidad']) : 0;
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$buscar = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Construir query
$where = ['1=1'];
$params = [];

if ($comunidad_filtro) {
    $where[] = 'p.comunidad_id = %d';
    $params[] = $comunidad_filtro;
}

if ($estado_filtro) {
    $where[] = 'p.estado = %s';
    $params[] = $estado_filtro;
}

if ($buscar) {
    $where[] = '(p.contenido LIKE %s OR u.display_name LIKE %s)';
    $params[] = '%' . $wpdb->esc_like($buscar) . '%';
    $params[] = '%' . $wpdb->esc_like($buscar) . '%';
}

$where_sql = implode(' AND ', $where);

// Obtener publicaciones
$publicaciones = [];
$total_items = 0;

if ($tabla_publicaciones_existe) {
    $sql_count = "SELECT COUNT(*) FROM {$tabla_publicaciones} p
                  LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
                  LEFT JOIN {$tabla_comunidades} c ON p.comunidad_id = c.id
                  WHERE {$where_sql}";

    $sql_items = "SELECT p.*, u.display_name, c.nombre as comunidad_nombre
                  FROM {$tabla_publicaciones} p
                  LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
                  LEFT JOIN {$tabla_comunidades} c ON p.comunidad_id = c.id
                  WHERE {$where_sql}
                  ORDER BY p.created_at DESC
                  LIMIT %d OFFSET %d";

    if (!empty($params)) {
        $total_items = $wpdb->get_var($wpdb->prepare($sql_count, $params));
        $publicaciones = $wpdb->get_results($wpdb->prepare($sql_items, array_merge($params, [$por_pagina, $offset])));
    } else {
        $total_items = $wpdb->get_var($sql_count);
        $publicaciones = $wpdb->get_results($wpdb->prepare($sql_items, $por_pagina, $offset));
    }
}

$total_paginas = ceil($total_items / $por_pagina);

// Obtener comunidades para el filtro
$comunidades = $wpdb->get_results("SELECT id, nombre FROM {$tabla_comunidades} ORDER BY nombre ASC");

// Estados
$estados = [
    'publicado'  => __('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'pendiente'  => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'rechazado'  => __('Rechazado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'eliminado'  => __('Eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$colores_estado = [
    'publicado'  => '#00a32a',
    'pendiente'  => '#dba617',
    'rechazado'  => '#d63638',
    'eliminado'  => '#787c82',
];

// Estadísticas rápidas
$stats = [];
if ($tabla_publicaciones_existe) {
    $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_publicaciones}");
    $stats['hoy'] = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_publicaciones} WHERE DATE(created_at) = CURDATE()");
    $stats['pendientes'] = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_publicaciones} WHERE estado = 'pendiente'");
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
        <span style="color: #1d2327;"><?php _e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-format-chat"></span>
        <?php _e('Publicaciones de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Estadísticas rápidas -->
    <?php if (!empty($stats)): ?>
    <div style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
        <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div style="font-size: 24px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['total']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Total publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #00a32a; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div style="font-size: 24px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['hoy']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Publicaciones hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <?php if ($stats['pendientes'] > 0): ?>
        <div style="background: #fff; padding: 15px 20px; border-left: 4px solid #dba617; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div style="font-size: 24px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['pendientes']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Pendientes de moderación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="comunidades-publicaciones">

            <div class="alignleft actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <select name="comunidad">
                    <option value=""><?php _e('Todas las comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($comunidades as $com): ?>
                    <option value="<?php echo esc_attr($com->id); ?>" <?php selected($comunidad_filtro, $com->id); ?>>
                        <?php echo esc_html($com->nombre); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select name="estado">
                    <option value=""><?php _e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($estados as $slug => $label): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($estado_filtro, $slug); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <input type="search" name="s" value="<?php echo esc_attr($buscar); ?>" placeholder="<?php esc_attr_e('Buscar en contenido...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">

                <button type="submit" class="button"><?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

                <?php if ($comunidad_filtro || $estado_filtro || $buscar): ?>
                <a href="<?php echo admin_url('admin.php?page=comunidades-publicaciones'); ?>" class="button"><?php _e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                <?php endif; ?>
            </div>
        </form>

        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(_n('%s elemento', '%s elementos', $total_items, FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($total_items)); ?>
            </span>
        </div>
    </div>

    <!-- Tabla -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" style="width: 60px;"><?php _e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 180px;"><?php _e('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 150px;"><?php _e('Autor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 90px;"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 140px;"><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($publicaciones)): ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">
                    <span class="dashicons dashicons-format-chat" style="font-size: 48px; color: #c3c4c7;"></span>
                    <p style="color: #646970;"><?php _e('No se encontraron publicaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($publicaciones as $pub): ?>
                <tr>
                    <td><code><?php echo esc_html($pub->id); ?></code></td>
                    <td>
                        <div style="max-width: 400px;">
                            <?php
                            $contenido = wp_strip_all_tags($pub->contenido ?? '');
                            $contenido_corto = mb_strlen($contenido) > 150 ? mb_substr($contenido, 0, 150) . '...' : $contenido;
                            echo esc_html($contenido_corto);
                            ?>
                        </div>
                        <?php if (!empty($pub->imagen)): ?>
                        <small style="color: #646970;">
                            <span class="dashicons dashicons-format-image" style="font-size: 14px;"></span>
                            <?php _e('Con imagen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=comunidades-editar&id=' . $pub->comunidad_id); ?>">
                            <?php echo esc_html($pub->comunidad_nombre ?: __('Comunidad eliminada', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                        </a>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <?php echo get_avatar($pub->user_id, 24); ?>
                            <span><?php echo esc_html($pub->display_name ?: __('Usuario eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                        </div>
                    </td>
                    <td>
                        <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; background: <?php echo esc_attr($colores_estado[$pub->estado ?? 'publicado'] ?? '#646970'); ?>; color: #fff;">
                            <?php echo esc_html($estados[$pub->estado ?? 'publicado'] ?? ucfirst($pub->estado ?? 'publicado')); ?>
                        </span>
                    </td>
                    <td>
                        <span title="<?php echo esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($pub->created_at))); ?>">
                            <?php echo esc_html(human_time_diff(strtotime($pub->created_at), current_time('timestamp'))); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php
            $pagination_args = [
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_paginas,
                'current' => $pagina_actual,
            ];
            echo paginate_links($pagination_args);
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>
