<?php
/**
 * Vista Admin: Listado de Reservas
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_reservas = $wpdb->prefix . 'flavor_reservas';
$tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_reservas}'") === $tabla_reservas;

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$recurso_filtro = isset($_GET['recurso']) ? absint($_GET['recurso']) : 0;
$fecha_filtro = isset($_GET['fecha']) ? sanitize_text_field($_GET['fecha']) : '';

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Estados
$estados = [
    'pendiente'  => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'confirmada' => __('Confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cancelada'  => __('Cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'completada' => __('Completada', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$colores_estado = [
    'pendiente'  => '#dba617',
    'confirmada' => '#00a32a',
    'cancelada'  => '#d63638',
    'completada' => '#2271b1',
];

// Obtener recursos
$recursos = [];
if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla_recursos}'") === $tabla_recursos) {
    $recursos = $wpdb->get_results("SELECT id, nombre FROM {$tabla_recursos} ORDER BY nombre ASC");
}

// Obtener reservas
$reservas = [];
$total_items = 0;

if ($tabla_existe) {
    $where = ['1=1'];
    $params = [];

    if ($estado_filtro) {
        $where[] = 'r.estado = %s';
        $params[] = $estado_filtro;
    }

    if ($recurso_filtro) {
        $where[] = 'r.recurso_id = %d';
        $params[] = $recurso_filtro;
    }

    if ($fecha_filtro) {
        $where[] = 'DATE(r.fecha_inicio) = %s';
        $params[] = $fecha_filtro;
    }

    $where_sql = implode(' AND ', $where);

    $sql_count = "SELECT COUNT(*) FROM {$tabla_reservas} r WHERE {$where_sql}";
    $sql_items = "SELECT r.*, u.display_name, rec.nombre as recurso_nombre
                  FROM {$tabla_reservas} r
                  LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                  LEFT JOIN {$tabla_recursos} rec ON r.recurso_id = rec.id
                  WHERE {$where_sql}
                  ORDER BY r.fecha_inicio DESC
                  LIMIT %d OFFSET %d";

    if (!empty($params)) {
        $total_items = $wpdb->get_var($wpdb->prepare($sql_count, $params));
        $reservas = $wpdb->get_results($wpdb->prepare($sql_items, array_merge($params, [$por_pagina, $offset])));
    } else {
        $total_items = $wpdb->get_var($sql_count);
        $reservas = $wpdb->get_results($wpdb->prepare($sql_items, $por_pagina, $offset));
    }
}

$total_paginas = ceil($total_items / $por_pagina);

// Estadísticas
$stats = ['hoy' => 0, 'pendientes' => 0, 'semana' => 0];
if ($tabla_existe) {
    $stats['hoy'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_reservas} WHERE DATE(fecha_inicio) = CURDATE()");
    $stats['pendientes'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_reservas} WHERE estado = 'pendiente'");
    $stats['semana'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_reservas} WHERE fecha_inicio BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
}
?>

<div class="wrap">
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=reservas-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-calendar-alt" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Listado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </nav>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-list-view"></span>
        <?php _e('Listado de Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Estadísticas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['hoy']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #dba617; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['pendientes']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div style="background: #fff; padding: 15px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['semana']); ?></div>
            <div style="color: #646970; font-size: 13px;"><?php _e('Esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="reservas-listado">
            <div class="alignleft actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <select name="estado">
                    <option value=""><?php _e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($estados as $slug => $label): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($estado_filtro, $slug); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="recurso">
                    <option value=""><?php _e('Todos los recursos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($recursos as $rec): ?>
                    <option value="<?php echo esc_attr($rec->id); ?>" <?php selected($recurso_filtro, $rec->id); ?>><?php echo esc_html($rec->nombre); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="fecha" value="<?php echo esc_attr($fecha_filtro); ?>">
                <button type="submit" class="button"><?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <?php if ($estado_filtro || $recurso_filtro || $fecha_filtro): ?>
                <a href="<?php echo admin_url('admin.php?page=reservas-listado'); ?>" class="button"><?php _e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                <?php endif; ?>
            </div>
        </form>
        <div class="tablenav-pages">
            <span class="displaying-num"><?php printf(_n('%s reserva', '%s reservas', $total_items, FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($total_items)); ?></span>
        </div>
    </div>

    <!-- Tabla -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;"><?php _e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th><?php _e('Recurso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th><?php _e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 150px;"><?php _e('Fecha/Hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 100px;"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 100px;"><?php _e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reservas)): ?>
            <tr><td colspan="6" style="text-align: center; padding: 40px;">
                <span class="dashicons dashicons-calendar-alt" style="font-size: 48px; color: #c3c4c7;"></span>
                <p style="color: #646970;"><?php _e('No se encontraron reservas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </td></tr>
            <?php else: ?>
                <?php foreach ($reservas as $r): ?>
                <tr>
                    <td><code><?php echo esc_html($r->id); ?></code></td>
                    <td><strong><?php echo esc_html($r->recurso_nombre ?: __('Recurso eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <?php echo get_avatar($r->user_id, 28); ?>
                            <?php echo esc_html($r->display_name ?: __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                        </div>
                    </td>
                    <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($r->fecha_inicio))); ?></td>
                    <td>
                        <span style="padding: 3px 8px; border-radius: 3px; font-size: 11px; background: <?php echo esc_attr($colores_estado[$r->estado] ?? '#646970'); ?>; color: #fff;">
                            <?php echo esc_html($estados[$r->estado] ?? ucfirst($r->estado)); ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=reservas-editar&id=' . $r->id); ?>" class="button button-small">
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
