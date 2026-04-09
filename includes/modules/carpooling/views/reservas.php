<?php
/**
 * Vista de Gestión de Reservas - Carpooling
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
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
}

global $wpdb;
$tabla_reservas = $wpdb->prefix . 'flavor_carpooling_reservas';
$tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';

// Obtener filtros
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'todos';
$filtro_viaje = isset($_GET['viaje_id']) ? intval($_GET['viaje_id']) : 0;
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
    $where .= $wpdb->prepare(" AND r.estado = %s", $filtro_estado);
}

if ($filtro_viaje > 0) {
    $where .= $wpdb->prepare(" AND r.viaje_id = %d", $filtro_viaje);
}

if (!empty($filtro_busqueda)) {
    $where .= $wpdb->prepare(" AND (u.display_name LIKE %s OR u.user_email LIKE %s)",
        '%' . $wpdb->esc_like($filtro_busqueda) . '%',
        '%' . $wpdb->esc_like($filtro_busqueda) . '%'
    );
}

if (!empty($filtro_fecha_desde)) {
    $where .= $wpdb->prepare(" AND DATE(r.fecha_reserva) >= %s", $filtro_fecha_desde);
}

if (!empty($filtro_fecha_hasta)) {
    $where .= $wpdb->prepare(" AND DATE(r.fecha_reserva) <= %s", $filtro_fecha_hasta);
}

// Obtener total de registros
$total_reservas = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_reservas} r
    INNER JOIN {$wpdb->users} u ON r.pasajero_id = u.ID
    {$where}"
);

// Obtener reservas
$reservas = $wpdb->get_results(
    "SELECT
        r.*,
        r.numero_plazas as plazas_reservadas,
        u.display_name as nombre_usuario,
        u.user_email as email_usuario,
        v.origen,
        v.destino,
        v.fecha_salida as fecha_viaje,
        v.precio_por_plaza,
        uc.display_name as nombre_conductor
    FROM {$tabla_reservas} r
    INNER JOIN {$wpdb->users} u ON r.pasajero_id = u.ID
    INNER JOIN {$tabla_viajes} v ON r.viaje_id = v.id
    INNER JOIN {$wpdb->users} uc ON v.conductor_id = uc.ID
    {$where}
    ORDER BY r.fecha_reserva DESC
    LIMIT {$elementos_por_pagina} OFFSET {$offset}"
);

$total_paginas = ceil($total_reservas / $elementos_por_pagina);

// Estadísticas rápidas
$stats = $wpdb->get_row(
    "SELECT
        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
        SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
        SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
        SUM(CASE WHEN estado = 'confirmada' OR estado = 'completada' THEN COALESCE(precio_total, 0) ELSE 0 END) as ingresos_totales
    FROM {$tabla_reservas}"
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Gestión de Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <hr class="wp-header-end">

    <!-- Estadísticas rápidas -->
    <div class="flavor-stats-mini" style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #d63638;">
                <?php echo esc_html(number_format($stats->pendientes, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #00a32a;">
                <?php echo esc_html(number_format($stats->confirmadas, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Confirmadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($stats->completadas, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Completadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #666;">
                <?php echo esc_html(number_format($stats->canceladas, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Canceladas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #00a32a;">
                €<?php echo esc_html(number_format($stats->ingresos_totales, 2, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Ingresos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card" style="padding: 15px; margin: 20px 0;">
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-carpooling-reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">

                <div>
                    <label for="estado"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="estado" id="estado" class="regular-text">
                        <option value="<?php echo esc_attr__('todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtro_estado, 'todos'); ?>><?php esc_html_e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtro_estado, 'pendiente'); ?>><?php esc_html_e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtro_estado, 'confirmada'); ?>><?php esc_html_e('Confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('completada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtro_estado, 'completada'); ?>><?php esc_html_e('Completada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtro_estado, 'cancelada'); ?>><?php esc_html_e('Cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>

                <div>
                    <label for="fecha_desde"><?php esc_html_e('Desde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="date" name="fecha_desde" id="fecha_desde" class="regular-text" value="<?php echo esc_attr($filtro_fecha_desde); ?>">
                </div>

                <div>
                    <label for="fecha_hasta"><?php esc_html_e('Hasta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="date" name="fecha_hasta" id="fecha_hasta" class="regular-text" value="<?php echo esc_attr($filtro_fecha_hasta); ?>">
                </div>

                <div>
                    <label for="busqueda"><?php esc_html_e('Buscar usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" name="s" id="busqueda" class="regular-text" placeholder="<?php esc_attr_e('Nombre o email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" value="<?php echo esc_attr($filtro_busqueda); ?>">
                </div>

                <div>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-reservas')); ?>" class="button">
                        <?php esc_html_e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>

            </div>
        </form>
    </div>

    <!-- Lista de reservas -->
    <div class="card" style="padding: 0; margin: 20px 0;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php esc_html_e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Conductor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Fecha Viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Importe', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reservas)) : ?>
                    <?php foreach ($reservas as $reserva) : ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($reserva->id); ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($reserva->nombre_usuario); ?></strong>
                                <br>
                                <small style="color: #666;"><?php echo esc_html($reserva->email_usuario); ?></small>
                            </td>
                            <td>
                                <strong><?php echo esc_html($reserva->origen); ?></strong>
                                <span style="color: #666;"> → </span>
                                <strong><?php echo esc_html($reserva->destino); ?></strong>
                                <br>
                                <small>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-viajes&action=ver&viaje_id=' . $reserva->viaje_id)); ?>">
                                        <?php esc_html_e('Ver viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> #<?php echo esc_html($reserva->viaje_id); ?>
                                    </a>
                                </small>
                            </td>
                            <td>
                                <?php echo esc_html($reserva->nombre_conductor); ?>
                            </td>
                            <td>
                                <?php echo esc_html(date('d/m/Y', strtotime($reserva->fecha_viaje))); ?>
                                <br>
                                <small><?php echo esc_html(date('H:i', strtotime($reserva->fecha_viaje))); ?></small>
                            </td>
                            <td>
                                <strong><?php echo esc_html($reserva->plazas_reservadas); ?></strong>
                                <?php if ($reserva->plazas_reservadas > 1) : ?>
                                    <span style="color: #666;"><?php echo esc_html__('plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php else : ?>
                                    <span style="color: #666;"><?php echo esc_html__('plaza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>€<?php echo esc_html(number_format($reserva->importe_total, 2, ',', '.')); ?></strong>
                                <br>
                                <small style="color: #666;">
                                    €<?php echo number_format($reserva->precio_por_plaza, 2); ?> × <?php echo $reserva->plazas_reservadas; ?>
                                </small>
                            </td>
                            <td>
                                <?php
                                $color_estado = [
                                    'pendiente' => '#d63638',
                                    'confirmada' => '#00a32a',
                                    'completada' => '#2271b1',
                                    'cancelada' => '#666'
                                ];
                                ?>
                                <span class="badge" style="background: <?php echo esc_attr($color_estado[$reserva->estado] ?? '#666'); ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">
                                    <?php echo esc_html(ucfirst($reserva->estado)); ?>
                                </span>
                                <br>
                                <small style="color: #666;">
                                    <?php echo date('d/m/Y H:i', strtotime($reserva->fecha_reserva)); ?>
                                </small>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-reservas&action=ver&reserva_id=' . $reserva->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                                <?php if ($reserva->estado === 'pendiente') : ?>
                                    <br><br>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-reservas&action=confirmar&reserva_id=' . $reserva->id)); ?>" class="button button-small button-primary">
                                        <?php esc_html_e('Confirmar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <div style="color: #666;">
                                <span class="dashicons dashicons-tickets-alt" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('No se encontraron reservas con los filtros seleccionados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
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
                        esc_html__('%s reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        number_format_i18n($total_reservas)
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
