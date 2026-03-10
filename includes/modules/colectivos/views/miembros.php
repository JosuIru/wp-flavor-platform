<?php
/**
 * Vista Admin: Miembros de Colectivos
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
$tabla_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';

// Verificar tablas
$tabla_miembros_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_miembros}'") === $tabla_miembros;

// Filtros
$colectivo_filtro = isset($_GET['colectivo']) ? absint($_GET['colectivo']) : 0;
$rol_filtro = isset($_GET['rol']) ? sanitize_text_field($_GET['rol']) : '';
$buscar = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Roles
$roles = [
    'admin'     => __('Administrador', 'flavor-chat-ia'),
    'moderador' => __('Moderador', 'flavor-chat-ia'),
    'miembro'   => __('Miembro', 'flavor-chat-ia'),
];

$colores_rol = [
    'admin'     => '#2271b1',
    'moderador' => '#8b5cf6',
    'miembro'   => '#646970',
];

// Obtener colectivos
$colectivos = $wpdb->get_results("SELECT id, nombre FROM {$tabla_colectivos} WHERE estado = 'activo' ORDER BY nombre ASC");

// Obtener miembros
$miembros = [];
$total_items = 0;

if ($tabla_miembros_existe) {
    $where = ["m.estado = 'activo'"];
    $params = [];

    if ($colectivo_filtro) {
        $where[] = 'm.colectivo_id = %d';
        $params[] = $colectivo_filtro;
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

    $sql_count = "SELECT COUNT(*) FROM {$tabla_miembros} m
                  LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
                  WHERE {$where_sql}";

    $sql_items = "SELECT m.*, u.display_name, u.user_email, c.nombre as colectivo_nombre
                  FROM {$tabla_miembros} m
                  LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
                  LEFT JOIN {$tabla_colectivos} c ON m.colectivo_id = c.id
                  WHERE {$where_sql}
                  ORDER BY m.created_at DESC
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
?>

<div class="wrap">
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=flavor-colectivos-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-groups" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Colectivos', 'flavor-chat-ia'); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Miembros', 'flavor-chat-ia'); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-businessperson"></span>
        <?php _e('Miembros de Colectivos', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="colectivos-miembros">

            <div class="alignleft actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <select name="colectivo">
                    <option value=""><?php _e('Todos los colectivos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($colectivos as $col): ?>
                    <option value="<?php echo esc_attr($col->id); ?>" <?php selected($colectivo_filtro, $col->id); ?>>
                        <?php echo esc_html($col->nombre); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select name="rol">
                    <option value=""><?php _e('Todos los roles', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($roles as $slug => $label): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($rol_filtro, $slug); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <input type="search" name="s" value="<?php echo esc_attr($buscar); ?>" placeholder="<?php esc_attr_e('Buscar...', 'flavor-chat-ia'); ?>">

                <button type="submit" class="button"><?php _e('Filtrar', 'flavor-chat-ia'); ?></button>

                <?php if ($colectivo_filtro || $rol_filtro || $buscar): ?>
                <a href="<?php echo admin_url('admin.php?page=colectivos-miembros'); ?>" class="button"><?php _e('Limpiar', 'flavor-chat-ia'); ?></a>
                <?php endif; ?>
            </div>
        </form>

        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(_n('%s miembro', '%s miembros', $total_items, 'flavor-chat-ia'), number_format($total_items)); ?>
            </span>
        </div>
    </div>

    <!-- Tabla -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" style="width: 50px;"><?php _e('ID', 'flavor-chat-ia'); ?></th>
                <th scope="col"><?php _e('Usuario', 'flavor-chat-ia'); ?></th>
                <th scope="col"><?php _e('Colectivo', 'flavor-chat-ia'); ?></th>
                <th scope="col" style="width: 120px;"><?php _e('Rol', 'flavor-chat-ia'); ?></th>
                <th scope="col" style="width: 140px;"><?php _e('Fecha unión', 'flavor-chat-ia'); ?></th>
                <th scope="col" style="width: 100px;"><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($miembros)): ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">
                    <span class="dashicons dashicons-groups" style="font-size: 48px; color: #c3c4c7;"></span>
                    <p style="color: #646970;"><?php _e('No se encontraron miembros.', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($miembros as $m): ?>
                <tr>
                    <td><code><?php echo esc_html($m->id); ?></code></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php echo get_avatar($m->user_id, 36); ?>
                            <div>
                                <strong><?php echo esc_html($m->display_name ?: __('Usuario eliminado', 'flavor-chat-ia')); ?></strong>
                                <br><small style="color: #646970;"><?php echo esc_html($m->user_email); ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=colectivos-editar&id=' . $m->colectivo_id); ?>">
                            <?php echo esc_html($m->colectivo_nombre ?: __('Colectivo eliminado', 'flavor-chat-ia')); ?>
                        </a>
                    </td>
                    <td>
                        <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; background: <?php echo esc_attr($colores_rol[$m->rol] ?? '#646970'); ?>; color: #fff;">
                            <?php echo esc_html($roles[$m->rol] ?? ucfirst($m->rol)); ?>
                        </span>
                    </td>
                    <td>
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($m->created_at))); ?>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $m->user_id); ?>" class="button button-small" title="<?php esc_attr_e('Ver usuario', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-admin-users" style="margin-top: 3px;"></span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

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
