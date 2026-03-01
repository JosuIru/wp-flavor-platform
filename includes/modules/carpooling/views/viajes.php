<?php
/**
 * Vista de Gestión de Viajes - Carpooling
 *
 * @package FlavorChatIA
 * @subpackage Carpooling
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'flavor-chat-ia'));
}

global $wpdb;
$tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';
$tabla_reservas = $wpdb->prefix . 'flavor_carpooling_reservas';
$tabla_valoraciones = $wpdb->prefix . 'flavor_carpooling_valoraciones';

// Obtener filtros
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'todos';
$filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? sanitize_text_field($_GET['fecha_desde']) : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? sanitize_text_field($_GET['fecha_hasta']) : '';

// Paginación
$elementos_por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $elementos_por_pagina;

// Construir consulta
$where = "WHERE 1=1";

if ($filtro_estado !== 'todos') {
    $where .= $wpdb->prepare(" AND v.estado = %s", $filtro_estado);
}

if (!empty($filtro_busqueda)) {
    $where .= $wpdb->prepare(" AND (v.origen LIKE %s OR v.destino LIKE %s)",
        '%' . $wpdb->esc_like($filtro_busqueda) . '%',
        '%' . $wpdb->esc_like($filtro_busqueda) . '%'
    );
}

if (!empty($filtro_fecha_desde)) {
    $where .= $wpdb->prepare(" AND DATE(v.fecha_salida) >= %s", $filtro_fecha_desde);
}

if (!empty($filtro_fecha_hasta)) {
    $where .= $wpdb->prepare(" AND DATE(v.fecha_salida) <= %s", $filtro_fecha_hasta);
}

// Obtener total de registros
$total_viajes = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_viajes} v {$where}"
);

// Obtener viajes
$viajes = $wpdb->get_results(
    "SELECT
        v.*,
        u.display_name as nombre_conductor,
        COALESCE((SELECT AVG(puntuacion) FROM {$tabla_valoraciones} WHERE valorado_id = v.conductor_id), 0) as valoracion_promedio,
        (SELECT COUNT(*) FROM {$tabla_reservas} WHERE viaje_id = v.id) as total_reservas
    FROM {$tabla_viajes} v
    INNER JOIN {$wpdb->users} u ON v.conductor_id = u.ID
    {$where}
    ORDER BY v.fecha_salida DESC
    LIMIT {$elementos_por_pagina} OFFSET {$offset}"
);

$total_paginas = ceil($total_viajes / $elementos_por_pagina);

// Estadísticas rápidas
$stats_estados = $wpdb->get_results(
    "SELECT estado, COUNT(*) as total FROM {$tabla_viajes} GROUP BY estado"
);

$stats = [
    'activo' => 0,
    'completado' => 0,
    'cancelado' => 0,
    'pendiente' => 0
];

foreach ($stats_estados as $stat) {
    $stats[$stat->estado] = (int) $stat->total;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Gestión de Viajes', 'flavor-chat-ia'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-viajes&action=nuevo')); ?>" class="page-title-action">
        <?php esc_html_e('Añadir Nuevo', 'flavor-chat-ia'); ?>
    </a>
    <hr class="wp-header-end">

    <!-- Estadísticas rápidas -->
    <div class="flavor-stats-mini" style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($stats['activo'], 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Activos', 'flavor-chat-ia'); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #00a32a;">
                <?php echo esc_html(number_format($stats['completado'], 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Completados', 'flavor-chat-ia'); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #d63638;">
                <?php echo esc_html(number_format($stats['cancelado'], 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Cancelados', 'flavor-chat-ia'); ?>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card" style="padding: 15px; margin: 20px 0;">
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-carpooling-viajes', 'flavor-chat-ia'); ?>">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">

                <div>
                    <label for="estado"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></label>
                    <select name="estado" id="estado" class="regular-text">
                        <option value="<?php echo esc_attr__('todos', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'todos'); ?>><?php esc_html_e('Todos los estados', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('activo', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'activo'); ?>><?php esc_html_e('Activo', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('pendiente', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'pendiente'); ?>><?php esc_html_e('Pendiente', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('completado', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'completado'); ?>><?php esc_html_e('Completado', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('cancelado', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'cancelado'); ?>><?php esc_html_e('Cancelado', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div>
                    <label for="fecha_desde"><?php esc_html_e('Desde', 'flavor-chat-ia'); ?></label>
                    <input type="date" name="fecha_desde" id="fecha_desde" class="regular-text" value="<?php echo esc_attr($filtro_fecha_desde); ?>">
                </div>

                <div>
                    <label for="fecha_hasta"><?php esc_html_e('Hasta', 'flavor-chat-ia'); ?></label>
                    <input type="date" name="fecha_hasta" id="fecha_hasta" class="regular-text" value="<?php echo esc_attr($filtro_fecha_hasta); ?>">
                </div>

                <div>
                    <label for="busqueda"><?php esc_html_e('Buscar ruta', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="s" id="busqueda" class="regular-text" placeholder="<?php esc_attr_e('Origen o destino', 'flavor-chat-ia'); ?>" value="<?php echo esc_attr($filtro_busqueda); ?>">
                </div>

                <div>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-viajes')); ?>" class="button">
                        <?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?>
                    </a>
                </div>

            </div>
        </form>
    </div>

    <!-- Lista de viajes -->
    <div class="card" style="padding: 0; margin: 20px 0;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php esc_html_e('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Ruta', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Conductor', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Fecha y Hora', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Plazas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Precio', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Reservas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($viajes)) : ?>
                    <?php foreach ($viajes as $viaje) : ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($viaje->id); ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($viaje->origen); ?></strong>
                                <span style="color: #666;"> → </span>
                                <strong><?php echo esc_html($viaje->destino); ?></strong>
                                                            </td>
                            <td>
                                <?php echo esc_html($viaje->nombre_conductor); ?>
                                <br>
                                <small style="color: #666;">
                                    <?php echo str_repeat('⭐', round($viaje->valoracion_promedio)); ?>
                                    <?php echo number_format($viaje->valoracion_promedio, 1); ?>
                                </small>
                            </td>
                            <td>
                                <?php echo esc_html(date('d/m/Y', strtotime($viaje->fecha_salida))); ?>
                                <br>
                                <small><?php echo esc_html(date('H:i', strtotime($viaje->fecha_salida))); ?></small>
                            </td>
                            <td>
                                <span style="color: <?php echo $viaje->plazas_ocupadas >= $viaje->plazas_disponibles ? '#d63638' : '#00a32a'; ?>;">
                                    <?php echo esc_html($viaje->plazas_ocupadas); ?> / <?php echo esc_html($viaje->plazas_disponibles); ?>
                                </span>
                            </td>
                            <td>
                                <strong>€<?php echo esc_html(number_format($viaje->precio_por_plaza, 2, ',', '.')); ?></strong>
                            </td>
                            <td>
                                <?php
                                $color_estado = [
                                    'activo' => '#00a32a',
                                    'completado' => '#2271b1',
                                    'cancelado' => '#d63638',
                                    'pendiente' => '#dba617'
                                ];
                                ?>
                                <span class="badge" style="background: <?php echo esc_attr($color_estado[$viaje->estado] ?? '#666'); ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">
                                    <?php echo esc_html(ucfirst($viaje->estado)); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-reservas&viaje_id=' . $viaje->id)); ?>">
                                    <?php echo esc_html($viaje->total_reservas); ?> <?php esc_html_e('reservas', 'flavor-chat-ia'); ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-viajes&action=ver&viaje_id=' . $viaje->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-viajes&action=editar&viaje_id=' . $viaje->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <div style="color: #666;">
                                <span class="dashicons dashicons-car" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('No se encontraron viajes con los filtros seleccionados.', 'flavor-chat-ia'); ?></p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($total_paginas > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(
                        esc_html__('%s viajes', 'flavor-chat-ia'),
                        number_format_i18n($total_viajes)
                    ); ?>
                </span>
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_paginas,
                    'current' => $pagina_actual
                ]);
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
@media (max-width: 782px) {
    .flavor-stats-mini {
        flex-direction: column;
    }
    .wp-list-table td {
        font-size: 12px;
    }
}
</style>
