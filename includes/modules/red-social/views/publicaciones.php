<?php
/**
 * Vista Admin: Publicaciones de Red Social
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
$tabla_interacciones = $wpdb->prefix . 'flavor_red_social_interacciones';
$tabla_comentarios = $wpdb->prefix . 'flavor_red_social_comentarios';

// Verificar existencia de tablas
$tabla_posts_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_posts'") === $tabla_posts;

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$buscar = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Construir query
$where = ['1=1'];
$params = [];

if ($estado_filtro) {
    $where[] = 'p.estado = %s';
    $params[] = $estado_filtro;
}

if ($tipo_filtro) {
    $where[] = 'p.tipo = %s';
    $params[] = $tipo_filtro;
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

if ($tabla_posts_existe) {
    $sql_count = "SELECT COUNT(*) FROM {$tabla_posts} p
                  LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
                  WHERE {$where_sql}";

    $sql_items = "SELECT p.*, u.display_name,
                  (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_red_social_interacciones WHERE post_id = p.id) as total_likes,
                  (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_red_social_comentarios WHERE post_id = p.id) as total_comentarios
                  FROM {$tabla_posts} p
                  LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
                  WHERE {$where_sql}
                  ORDER BY p.fecha_creacion DESC
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

// Estados y tipos
$estados = [
    'publicado' => __('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'borrador'  => __('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'eliminado' => __('Eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$tipos = [
    'texto'    => __('Texto', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'imagen'   => __('Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'video'    => __('Video', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'enlace'   => __('Enlace', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'encuesta' => __('Encuesta', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$colores_estado = [
    'publicado' => '#00a32a',
    'pendiente' => '#dba617',
    'borrador'  => '#646970',
    'eliminado' => '#d63638',
];

$iconos_tipo = [
    'texto'    => 'dashicons-text',
    'imagen'   => 'dashicons-format-image',
    'video'    => 'dashicons-video-alt3',
    'enlace'   => 'dashicons-admin-links',
    'encuesta' => 'dashicons-chart-bar',
];
?>

<div class="wrap">
    <!-- Migas de pan -->
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=flavor-red-social-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-share" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-format-status"></span>
        <?php _e('Gestión de Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="flavor-red-social-publicaciones">

            <div class="alignleft actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <select name="estado">
                    <option value=""><?php _e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($estados as $slug => $label): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($estado_filtro, $slug); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select name="tipo">
                    <option value=""><?php _e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($tipos as $slug => $label): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($tipo_filtro, $slug); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <input type="search" name="s" value="<?php echo esc_attr($buscar); ?>" placeholder="<?php esc_attr_e('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">

                <button type="submit" class="button"><?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

                <?php if ($estado_filtro || $tipo_filtro || $buscar): ?>
                <a href="<?php echo admin_url('admin.php?page=flavor-red-social-publicaciones'); ?>" class="button"><?php _e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
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
                <th scope="col" style="width: 50px;"><?php _e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 150px;"><?php _e('Autor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 80px;"><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 90px;"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 100px;"><?php _e('Interacciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 120px;"><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($publicaciones)): ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px;">
                    <span class="dashicons dashicons-format-status" style="font-size: 48px; color: #c3c4c7;"></span>
                    <p style="color: #646970;"><?php _e('No se encontraron publicaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($publicaciones as $pub): ?>
                <tr>
                    <td><code><?php echo esc_html($pub->id); ?></code></td>
                    <td>
                        <div style="max-width: 350px;">
                            <?php
                            $contenido = wp_strip_all_tags($pub->contenido ?? '');
                            $contenido_corto = mb_strlen($contenido) > 120 ? mb_substr($contenido, 0, 120) . '...' : $contenido;
                            echo esc_html($contenido_corto);
                            ?>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <?php echo get_avatar($pub->usuario_id, 24); ?>
                            <span style="font-size: 13px;"><?php echo esc_html($pub->display_name ?: __('Usuario eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                        </div>
                    </td>
                    <td>
                        <?php $tipo = $pub->tipo ?? 'texto'; ?>
                        <span class="dashicons <?php echo esc_attr($iconos_tipo[$tipo] ?? 'dashicons-text'); ?>" title="<?php echo esc_attr($tipos[$tipo] ?? ucfirst($tipo)); ?>" style="color: #646970;"></span>
                    </td>
                    <td>
                        <?php $estado = $pub->estado ?? 'publicado'; ?>
                        <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; background: <?php echo esc_attr($colores_estado[$estado] ?? '#646970'); ?>; color: #fff;">
                            <?php echo esc_html($estados[$estado] ?? ucfirst($estado)); ?>
                        </span>
                    </td>
                    <td>
                        <span title="<?php esc_attr_e('Likes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <span class="dashicons dashicons-heart" style="color: #d63638; font-size: 14px;"></span>
                            <?php echo number_format($pub->total_likes ?? 0); ?>
                        </span>
                        <span title="<?php esc_attr_e('Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" style="margin-left: 8px;">
                            <span class="dashicons dashicons-admin-comments" style="color: #2271b1; font-size: 14px;"></span>
                            <?php echo number_format($pub->total_comentarios ?? 0); ?>
                        </span>
                    </td>
                    <td>
                        <span title="<?php echo esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($pub->fecha_creacion))); ?>">
                            <?php echo esc_html(human_time_diff(strtotime($pub->fecha_creacion), current_time('timestamp'))); ?>
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
