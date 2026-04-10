<?php
/**
 * Vista de Gestión de Reservas - Parkings
 *
 * @package FlavorPlatform
 * @subpackage Parkings
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
$tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';
$tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';

// Filtros
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'todos';
$filtro_plaza = isset($_GET['plaza_id']) ? intval($_GET['plaza_id']) : 0;

// Paginación
$elementos_por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $elementos_por_pagina;

// Construir consulta
$where = "WHERE 1=1";

if ($filtro_estado !== 'todos') {
    $where .= $wpdb->prepare(" AND r.estado = %s", $filtro_estado);
}

if ($filtro_plaza > 0) {
    $where .= $wpdb->prepare(" AND r.plaza_id = %d", $filtro_plaza);
}

$total_reservas = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_reservas} r {$where}");

$reservas = $wpdb->get_results(
    "SELECT
        r.*,
        u.display_name as nombre_usuario,
        p.numero_plaza,
        p.direccion,
        p.zona
    FROM {$tabla_reservas} r
    INNER JOIN {$wpdb->users} u ON r.usuario_id = u.ID
    INNER JOIN {$tabla_plazas} p ON r.plaza_id = p.id
    {$where}
    ORDER BY r.fecha_inicio DESC
    LIMIT {$elementos_por_pagina} OFFSET {$offset}"
);

$total_paginas = ceil($total_reservas / $elementos_por_pagina);

// Estadísticas
$stats = $wpdb->get_row(
    "SELECT
        SUM(CASE WHEN estado = 'activa' THEN 1 ELSE 0 END) as activas,
        SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
        SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
        SUM(CASE WHEN estado IN ('activa', 'completada') THEN precio_total ELSE 0 END) as ingresos
    FROM {$tabla_reservas}"
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Gestión de Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <hr class="wp-header-end">

    <!-- Estadísticas rápidas -->
    <div class="flavor-stats-mini" style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($stats->activas, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #00a32a;">
                <?php echo esc_html(number_format($stats->completadas, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Completadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #d63638;">
                <?php echo esc_html(number_format($stats->canceladas, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Canceladas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #00a32a;">
                €<?php echo esc_html(number_format($stats->ingresos, 2, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Ingresos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card" style="padding: 15px; margin: 20px 0;">
        <form method="get" action="">
            <input type="hidden" name="page" value="flavor-parkings-reservas">
            <div style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                <div>
                    <label for="estado"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="estado" id="estado" class="regular-text">
                        <option value="todos" <?php selected($filtro_estado, 'todos'); ?>><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="activa" <?php selected($filtro_estado, 'activa'); ?>><?php esc_html_e('Activa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="completada" <?php selected($filtro_estado, 'completada'); ?>><?php esc_html_e('Completada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="cancelada" <?php selected($filtro_estado, 'cancelada'); ?>><?php esc_html_e('Cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-reservas')); ?>" class="button"><?php esc_html_e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
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
                    <th><?php esc_html_e('Plaza', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Fin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Duración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Precio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reservas)) : ?>
                    <?php foreach ($reservas as $reserva) : ?>
                        <?php
                        $duracion_horas = (strtotime($reserva->fecha_fin) - strtotime($reserva->fecha_inicio)) / 3600;
                        ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($reserva->id); ?></strong></td>
                            <td><?php echo esc_html($reserva->nombre_usuario); ?></td>
                            <td>
                                <strong><?php echo esc_html($reserva->numero_plaza); ?></strong>
                                <br><small style="color: #666;"><?php echo esc_html($reserva->zona); ?></small>
                            </td>
                            <td>
                                <?php echo date('d/m/Y H:i', strtotime($reserva->fecha_inicio)); ?>
                            </td>
                            <td>
                                <?php echo date('d/m/Y H:i', strtotime($reserva->fecha_fin)); ?>
                            </td>
                            <td>
                                <?php echo number_format($duracion_horas, 1); ?> h
                            </td>
                            <td><strong>€<?php echo number_format($reserva->precio_total, 2, ',', '.'); ?></strong></td>
                            <td>
                                <?php
                                $colores = ['activa' => '#2271b1', 'completada' => '#00a32a', 'cancelada' => '#d63638'];
                                ?>
                                <span class="badge" style="background: <?php echo esc_attr($colores[$reserva->estado] ?? '#666'); ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">
                                    <?php echo esc_html(ucfirst($reserva->estado)); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-reservas&action=ver&reserva_id=' . $reserva->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <div style="color: #666;">
                                <span class="dashicons dashicons-calendar" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('No se encontraron reservas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
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
                <span class="displaying-num"><?php printf(esc_html__('%s reservas', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($total_reservas)); ?></span>
                <?php echo paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'prev_text' => '&laquo;', 'next_text' => '&raquo;', 'total' => $total_paginas, 'current' => $pagina_actual]); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
