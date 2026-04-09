<?php
/**
 * Vista Admin: Usuarios de Red Social
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
$tabla_seguidores = $wpdb->prefix . 'flavor_red_social_seguidores';
$tabla_perfiles = $wpdb->prefix . 'flavor_red_social_perfiles';

// Verificar existencia de tablas
$tabla_posts_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_posts'") === $tabla_posts;
$tabla_seguidores_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_seguidores'") === $tabla_seguidores;

// Filtros
$orden = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'publicaciones';
$buscar = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Obtener usuarios con actividad
$usuarios = [];
$total_items = 0;

if ($tabla_posts_existe) {
    $buscar_sql = '';
    if ($buscar) {
        $buscar_sql = $wpdb->prepare(" AND (u.display_name LIKE %s OR u.user_email LIKE %s)",
            '%' . $wpdb->esc_like($buscar) . '%',
            '%' . $wpdb->esc_like($buscar) . '%'
        );
    }

    // Contar total
    $total_items = $wpdb->get_var("
        SELECT COUNT(DISTINCT p.usuario_id)
        FROM {$tabla_posts} p
        LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
        WHERE 1=1 {$buscar_sql}
    ");

    // Ordenamiento
    $order_sql = 'total_publicaciones DESC';
    if ($orden === 'fecha') {
        $order_sql = 'ultima_actividad DESC';
    } elseif ($orden === 'nombre') {
        $order_sql = 'u.display_name ASC';
    }

    // Obtener usuarios
    $usuarios = $wpdb->get_results($wpdb->prepare("
        SELECT
            p.usuario_id,
            u.display_name,
            u.user_email,
            u.user_registered,
            COUNT(p.id) as total_publicaciones,
            MAX(p.fecha_creacion) as ultima_actividad,
            (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_red_social_seguidores WHERE seguido_id = p.usuario_id) as seguidores,
            (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_red_social_seguidores WHERE seguidor_id = p.usuario_id) as siguiendo
        FROM {$tabla_posts} p
        LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
        WHERE 1=1 {$buscar_sql}
        GROUP BY p.usuario_id
        ORDER BY {$order_sql}
        LIMIT %d OFFSET %d
    ", $por_pagina, $offset));
}

$total_paginas = ceil($total_items / $por_pagina);

// Estadísticas generales
$stats = [
    'total_usuarios' => 0,
    'activos_semana' => 0,
    'nuevos_mes' => 0,
];

if ($tabla_posts_existe) {
    $stats['total_usuarios'] = (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_posts}");
    $stats['activos_semana'] = (int) $wpdb->get_var("
        SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_posts}
        WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stats['nuevos_mes'] = (int) $wpdb->get_var("
        SELECT COUNT(DISTINCT p.usuario_id) FROM {$tabla_posts} p
        LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
        WHERE u.user_registered >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
}
?>

<div class="wrap">
    <!-- Migas de pan -->
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=flavor-red-social-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-share" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups"></span>
        <?php _e('Usuarios de la Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Estadísticas rápidas -->
    <div style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
        <div style="background: #fff; padding: 15px 25px; border-left: 4px solid #3b82f6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['total_usuarios']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Total usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div style="background: #fff; padding: 15px 25px; border-left: 4px solid #10b981; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['activos_semana']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Activos esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div style="background: #fff; padding: 15px 25px; border-left: 4px solid #f59e0b; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #1d2327;"><?php echo number_format($stats['nuevos_mes']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Nuevos este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="flavor-red-social-usuarios">

            <div class="alignleft actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <select name="orderby">
                    <option value="publicaciones" <?php selected($orden, 'publicaciones'); ?>><?php _e('Ordenar por publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="fecha" <?php selected($orden, 'fecha'); ?>><?php _e('Ordenar por actividad reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="nombre" <?php selected($orden, 'nombre'); ?>><?php _e('Ordenar por nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>

                <input type="search" name="s" value="<?php echo esc_attr($buscar); ?>" placeholder="<?php esc_attr_e('Buscar usuario...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">

                <button type="submit" class="button"><?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

                <?php if ($buscar || $orden !== 'publicaciones'): ?>
                <a href="<?php echo admin_url('admin.php?page=flavor-red-social-usuarios'); ?>" class="button"><?php _e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                <?php endif; ?>
            </div>
        </form>

        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(_n('%s usuario', '%s usuarios', $total_items, FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($total_items)); ?>
            </span>
        </div>
    </div>

    <!-- Tabla -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" style="width: 50px;"><?php _e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 120px;"><?php _e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 100px;"><?php _e('Seguidores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 100px;"><?php _e('Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 140px;"><?php _e('Última actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 100px;"><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($usuarios)): ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px;">
                    <span class="dashicons dashicons-groups" style="font-size: 48px; color: #c3c4c7;"></span>
                    <p style="color: #646970;"><?php _e('No se encontraron usuarios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><code><?php echo esc_html($usuario->usuario_id); ?></code></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <?php echo get_avatar($usuario->usuario_id, 40); ?>
                            <div>
                                <strong><?php echo esc_html($usuario->display_name ?: __('Usuario eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                                <br><small style="color: #646970;"><?php echo esc_html($usuario->user_email); ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="font-size: 16px; font-weight: 600; color: #1d2327;">
                            <?php echo number_format($usuario->total_publicaciones); ?>
                        </span>
                    </td>
                    <td><?php echo number_format($usuario->seguidores ?? 0); ?></td>
                    <td><?php echo number_format($usuario->siguiendo ?? 0); ?></td>
                    <td>
                        <?php if ($usuario->ultima_actividad): ?>
                        <span title="<?php echo esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($usuario->ultima_actividad))); ?>">
                            <?php echo esc_html(human_time_diff(strtotime($usuario->ultima_actividad), current_time('timestamp'))); ?>
                        </span>
                        <?php else: ?>
                        <span style="color: #646970;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $usuario->usuario_id); ?>" class="button button-small" title="<?php esc_attr_e('Editar usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <span class="dashicons dashicons-edit" style="margin-top: 3px;"></span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=flavor-red-social-publicaciones&usuario=' . $usuario->usuario_id); ?>" class="button button-small" title="<?php esc_attr_e('Ver publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <span class="dashicons dashicons-visibility" style="margin-top: 3px;"></span>
                        </a>
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
