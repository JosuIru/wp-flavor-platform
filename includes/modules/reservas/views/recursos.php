<?php
/**
 * Vista Admin: Recursos para Reservas
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';
$tabla_reservas = $wpdb->prefix . 'flavor_reservas';

$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_recursos}'") === $tabla_recursos;

// Filtros
$categoria_filtro = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Obtener recursos
$recursos = [];
$total_items = 0;

if ($tabla_existe) {
    $where = ['1=1'];
    $params = [];

    if ($categoria_filtro) {
        $where[] = 'categoria = %s';
        $params[] = $categoria_filtro;
    }

    if ($estado_filtro) {
        $where[] = 'estado = %s';
        $params[] = $estado_filtro;
    }

    $where_sql = implode(' AND ', $where);

    $sql_count = "SELECT COUNT(*) FROM {$tabla_recursos} WHERE {$where_sql}";
    $sql_items = "SELECT * FROM {$tabla_recursos} WHERE {$where_sql} ORDER BY nombre ASC LIMIT %d OFFSET %d";

    if (!empty($params)) {
        $total_items = $wpdb->get_var($wpdb->prepare($sql_count, $params));
        $recursos = $wpdb->get_results($wpdb->prepare($sql_items, array_merge($params, [$por_pagina, $offset])));
    } else {
        $total_items = $wpdb->get_var($sql_count);
        $recursos = $wpdb->get_results($wpdb->prepare($sql_items, $por_pagina, $offset));
    }
}

$total_paginas = ceil($total_items / $por_pagina);

// Categorías y estados
$categorias = ['sala' => __('Sala', FLAVOR_PLATFORM_TEXT_DOMAIN), 'equipo' => __('Equipo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'vehiculo' => __('Vehículo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'otro' => __('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN)];
$estados = ['disponible' => __('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN), 'mantenimiento' => __('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN), 'no_disponible' => __('No disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)];
$colores_estado = ['disponible' => '#00a32a', 'mantenimiento' => '#dba617', 'no_disponible' => '#d63638'];
?>

<div class="wrap">
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=reservas-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-calendar-alt" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Recursos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-home"></span>
        <?php _e('Gestión de Recursos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <a href="<?php echo admin_url('admin.php?page=reservas-nuevo-recurso'); ?>" class="page-title-action">
        <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
        <?php _e('Añadir Recurso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>

    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="reservas-recursos">
            <div class="alignleft actions" style="display: flex; gap: 8px;">
                <select name="categoria">
                    <option value=""><?php _e('Todas las categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($categorias as $slug => $label): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($categoria_filtro, $slug); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="estado">
                    <option value=""><?php _e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($estados as $slug => $label): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($estado_filtro, $slug); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button"><?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>
        </form>
        <div class="tablenav-pages">
            <span class="displaying-num"><?php printf(_n('%s recurso', '%s recursos', $total_items, FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($total_items)); ?></span>
        </div>
    </div>

    <!-- Tabla -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;"><?php _e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th><?php _e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 120px;"><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 100px;"><?php _e('Capacidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 120px;"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 100px;"><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recursos)): ?>
            <tr><td colspan="6" style="text-align: center; padding: 40px;">
                <span class="dashicons dashicons-admin-home" style="font-size: 48px; color: #c3c4c7;"></span>
                <p style="color: #646970;"><?php _e('No se encontraron recursos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </td></tr>
            <?php else: ?>
                <?php foreach ($recursos as $rec): ?>
                <tr>
                    <td><code><?php echo esc_html($rec->id); ?></code></td>
                    <td>
                        <strong><?php echo esc_html($rec->nombre); ?></strong>
                        <?php if (!empty($rec->descripcion)): ?>
                        <br><small style="color: #646970;"><?php echo esc_html(wp_trim_words($rec->descripcion, 10)); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($categorias[$rec->categoria] ?? ucfirst($rec->categoria ?? 'otro')); ?></td>
                    <td><?php echo esc_html($rec->capacidad ?? '-'); ?></td>
                    <td>
                        <span style="padding: 3px 8px; border-radius: 3px; font-size: 11px; background: <?php echo esc_attr($colores_estado[$rec->estado] ?? '#646970'); ?>; color: #fff;">
                            <?php echo esc_html($estados[$rec->estado] ?? ucfirst($rec->estado ?? 'disponible')); ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=reservas-editar-recurso&id=' . $rec->id); ?>" class="button button-small">
                            <span class="dashicons dashicons-edit" style="margin-top: 3px;"></span>
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
            <?php echo paginate_links(['base' => add_query_arg('paged', '%#%'), 'total' => $total_paginas, 'current' => $pagina_actual]); ?>
        </div>
    </div>
    <?php endif; ?>
</div>
