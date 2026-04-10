<?php
/**
 * Vista Admin: Miembros de Comunidades
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
$tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

// Verificar si las tablas existen
$tabla_miembros_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_miembros}'") === $tabla_miembros;

// Filtros
$comunidad_filtro = isset($_GET['comunidad']) ? absint($_GET['comunidad']) : 0;
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$rol_filtro = isset($_GET['rol']) ? sanitize_text_field($_GET['rol']) : '';
$buscar = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Construir query
$where = ['1=1'];
$params = [];

if ($comunidad_filtro) {
    $where[] = 'm.comunidad_id = %d';
    $params[] = $comunidad_filtro;
}

if ($estado_filtro) {
    $where[] = 'm.estado = %s';
    $params[] = $estado_filtro;
}

if ($rol_filtro) {
    $where[] = 'm.rol = %s';
    $params[] = $rol_filtro;
}

if ($buscar) {
    $where[] = '(u.display_name LIKE %s OR u.user_email LIKE %s)';
    $params[] = '%' . $wpdb->esc_like($buscar) . '%';
    $params[] = '%' . $wpdb->esc_like($buscar) . '%';
}

$where_sql = implode(' AND ', $where);

// Obtener miembros
$miembros = [];
$total_items = 0;

if ($tabla_miembros_existe) {
    $sql_count = "SELECT COUNT(*) FROM {$tabla_miembros} m
                  LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
                  LEFT JOIN {$tabla_comunidades} c ON m.comunidad_id = c.id
                  WHERE {$where_sql}";

    $sql_items = "SELECT m.*, u.display_name, u.user_email, c.nombre as comunidad_nombre
                  FROM {$tabla_miembros} m
                  LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
                  LEFT JOIN {$tabla_comunidades} c ON m.comunidad_id = c.id
                  WHERE {$where_sql}
                  ORDER BY m.fecha_union DESC
                  LIMIT %d OFFSET %d";

    if (!empty($params)) {
        $total_items = $wpdb->get_var($wpdb->prepare($sql_count, $params));
        $miembros = $wpdb->get_results($wpdb->prepare($sql_items, array_merge($params, [$por_pagina, $offset])));
    } else {
        $total_items = $wpdb->get_var($sql_count);
        $miembros = $wpdb->get_results($wpdb->prepare($sql_items, $por_pagina, $offset));
    }
}

$total_paginas = ceil($total_items / $por_pagina);

// Obtener comunidades para el filtro
$comunidades = $wpdb->get_results("SELECT id, nombre FROM {$tabla_comunidades} ORDER BY nombre ASC");

// Roles y estados
$roles = [
    'miembro' => __('Miembro', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'moderador' => __('Moderador', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'admin' => __('Administrador', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$estados = [
    'activo' => __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'suspendido' => __('Suspendido', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'expulsado' => __('Expulsado', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$colores_estado = [
    'activo' => '#00a32a',
    'pendiente' => '#dba617',
    'suspendido' => '#d63638',
    'expulsado' => '#787c82',
];

$colores_rol = [
    'admin' => '#2271b1',
    'moderador' => '#8c52ff',
    'miembro' => '#646970',
];
?>

<div class="wrap">
    <!-- Migas de pan -->
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=comunidades-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-admin-multisite" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups"></span>
        <?php _e('Miembros de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="comunidades-miembros">

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

                <select name="rol">
                    <option value=""><?php _e('Todos los roles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($roles as $slug => $label): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($rol_filtro, $slug); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <input type="search" name="s" value="<?php echo esc_attr($buscar); ?>" placeholder="<?php esc_attr_e('Buscar usuario...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">

                <button type="submit" class="button"><?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

                <?php if ($comunidad_filtro || $estado_filtro || $rol_filtro || $buscar): ?>
                <a href="<?php echo admin_url('admin.php?page=comunidades-miembros'); ?>" class="button"><?php _e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
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
                <th scope="col"><?php _e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 100px;"><?php _e('Rol', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 100px;"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 140px;"><?php _e('Fecha unión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col" style="width: 100px;"><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($miembros)): ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px;">
                    <span class="dashicons dashicons-groups" style="font-size: 48px; color: #c3c4c7;"></span>
                    <p style="color: #646970;"><?php _e('No se encontraron miembros.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($miembros as $miembro): ?>
                <tr>
                    <td><code><?php echo esc_html($miembro->id); ?></code></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php echo get_avatar($miembro->user_id, 32); ?>
                            <div>
                                <strong><?php echo esc_html($miembro->display_name ?: __('Usuario eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                                <br><small style="color: #646970;"><?php echo esc_html($miembro->user_email); ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=comunidades-editar&id=' . $miembro->comunidad_id); ?>">
                            <?php echo esc_html($miembro->comunidad_nombre ?: __('Comunidad eliminada', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                        </a>
                    </td>
                    <td>
                        <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; background: <?php echo esc_attr($colores_rol[$miembro->rol] ?? '#646970'); ?>; color: #fff;">
                            <?php echo esc_html($roles[$miembro->rol] ?? ucfirst($miembro->rol)); ?>
                        </span>
                    </td>
                    <td>
                        <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; background: <?php echo esc_attr($colores_estado[$miembro->estado] ?? '#646970'); ?>; color: #fff;">
                            <?php echo esc_html($estados[$miembro->estado] ?? ucfirst($miembro->estado)); ?>
                        </span>
                    </td>
                    <td>
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($miembro->fecha_union))); ?>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $miembro->user_id); ?>" class="button button-small" title="<?php esc_attr_e('Ver usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <span class="dashicons dashicons-admin-users" style="margin-top: 3px;"></span>
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
