<?php
/**
 * Vista de Gestión de Conductores - Carpooling
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
$tabla_conductores = $wpdb->prefix . 'flavor_carpooling_conductores';
$tabla_vehiculos = $wpdb->prefix . 'flavor_carpooling_vehiculos';
$tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';

// Obtener filtros
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'todos';
$filtro_verificacion = isset($_GET['verificacion']) ? sanitize_text_field($_GET['verificacion']) : 'todos';
$filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Paginación
$elementos_por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $elementos_por_pagina;

// Construir consulta
$where = "WHERE 1=1";

if ($filtro_estado !== 'todos') {
    $where .= $wpdb->prepare(" AND c.estado = %s", $filtro_estado);
}

if ($filtro_verificacion === 'verificado') {
    $where .= " AND c.verificado = 1";
} elseif ($filtro_verificacion === 'pendiente') {
    $where .= " AND c.verificado = 0";
}

if (!empty($filtro_busqueda)) {
    $where .= $wpdb->prepare(" AND (u.display_name LIKE %s OR u.user_email LIKE %s)",
        '%' . $wpdb->esc_like($filtro_busqueda) . '%',
        '%' . $wpdb->esc_like($filtro_busqueda) . '%'
    );
}

// Obtener total de registros
$total_conductores = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_conductores} c
    INNER JOIN {$wpdb->users} u ON c.usuario_id = u.ID
    {$where}"
);

// Obtener conductores
$conductores = $wpdb->get_results(
    "SELECT
        c.*,
        u.display_name,
        u.user_email,
        (SELECT COUNT(*) FROM {$tabla_viajes} WHERE conductor_id = c.id) as total_viajes,
        (SELECT COUNT(*) FROM {$tabla_viajes} WHERE conductor_id = c.id AND estado = 'completado') as viajes_completados,
        (SELECT COUNT(*) FROM {$tabla_vehiculos} WHERE conductor_id = c.id) as total_vehiculos
    FROM {$tabla_conductores} c
    INNER JOIN {$wpdb->users} u ON c.usuario_id = u.ID
    {$where}
    ORDER BY c.fecha_registro DESC
    LIMIT {$elementos_por_pagina} OFFSET {$offset}"
);

$total_paginas = ceil($total_conductores / $elementos_por_pagina);

// Estadísticas rápidas
$stats_totales = $wpdb->get_row(
    "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
        SUM(CASE WHEN verificado = 1 THEN 1 ELSE 0 END) as verificados,
        SUM(CASE WHEN verificado = 0 THEN 1 ELSE 0 END) as pendientes
    FROM {$tabla_conductores}"
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Gestión de Conductores', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">

    <!-- Estadísticas rápidas -->
    <div class="flavor-stats-mini" style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($stats_totales->total, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Total', 'flavor-chat-ia'); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #00a32a;">
                <?php echo esc_html(number_format($stats_totales->activos, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Activos', 'flavor-chat-ia'); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($stats_totales->verificados, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Verificados', 'flavor-chat-ia'); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #d63638;">
                <?php echo esc_html(number_format($stats_totales->pendientes, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card" style="padding: 15px; margin: 20px 0;">
        <form method="get" action="">
            <input type="hidden" name="page" value="flavor-carpooling-conductores">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">

                <div>
                    <label for="estado"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></label>
                    <select name="estado" id="estado" class="regular-text">
                        <option value="todos" <?php selected($filtro_estado, 'todos'); ?>><?php esc_html_e('Todos los estados', 'flavor-chat-ia'); ?></option>
                        <option value="activo" <?php selected($filtro_estado, 'activo'); ?>><?php esc_html_e('Activo', 'flavor-chat-ia'); ?></option>
                        <option value="inactivo" <?php selected($filtro_estado, 'inactivo'); ?>><?php esc_html_e('Inactivo', 'flavor-chat-ia'); ?></option>
                        <option value="suspendido" <?php selected($filtro_estado, 'suspendido'); ?>><?php esc_html_e('Suspendido', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div>
                    <label for="verificacion"><?php esc_html_e('Verificación', 'flavor-chat-ia'); ?></label>
                    <select name="verificacion" id="verificacion" class="regular-text">
                        <option value="todos" <?php selected($filtro_verificacion, 'todos'); ?>><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></option>
                        <option value="verificado" <?php selected($filtro_verificacion, 'verificado'); ?>><?php esc_html_e('Verificados', 'flavor-chat-ia'); ?></option>
                        <option value="pendiente" <?php selected($filtro_verificacion, 'pendiente'); ?>><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div>
                    <label for="busqueda"><?php esc_html_e('Buscar', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="s" id="busqueda" class="regular-text" placeholder="<?php esc_attr_e('Nombre o email', 'flavor-chat-ia'); ?>" value="<?php echo esc_attr($filtro_busqueda); ?>">
                </div>

                <div>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-conductores')); ?>" class="button">
                        <?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?>
                    </a>
                </div>

            </div>
        </form>
    </div>

    <!-- Lista de conductores -->
    <div class="card" style="padding: 0; margin: 20px 0;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php esc_html_e('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Conductor', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Valoración', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Viajes', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Vehículos', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Verificación', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Registro', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($conductores)) : ?>
                    <?php foreach ($conductores as $conductor) : ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($conductor->id); ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($conductor->display_name); ?></strong>
                                <br>
                                <small style="color: #666;"><?php echo esc_html($conductor->user_email); ?></small>
                                <?php if ($conductor->telefono) : ?>
                                    <br>
                                    <small style="color: #666;">📱 <?php echo esc_html($conductor->telefono); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size: 18px;">
                                    <?php echo str_repeat('⭐', round($conductor->valoracion_promedio)); ?>
                                </div>
                                <small style="color: #666;">
                                    <?php echo number_format($conductor->valoracion_promedio, 2); ?> / 5.0
                                    <br>
                                    (<?php echo esc_html($conductor->total_valoraciones); ?> valoraciones)
                                </small>
                            </td>
                            <td>
                                <strong><?php echo esc_html($conductor->viajes_completados); ?></strong>
                                <span style="color: #666;"> / </span>
                                <?php echo esc_html($conductor->total_viajes); ?>
                                <br>
                                <small style="color: #666;">
                                    <?php esc_html_e('Completados / Total', 'flavor-chat-ia'); ?>
                                </small>
                            </td>
                            <td>
                                <?php echo esc_html($conductor->total_vehiculos); ?>
                                <?php if ($conductor->total_vehiculos > 0) : ?>
                                    <br>
                                    <small>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-conductores&action=vehiculos&conductor_id=' . $conductor->id)); ?>">
                                            <?php esc_html_e('Ver vehículos', 'flavor-chat-ia'); ?>
                                        </a>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($conductor->verificado) : ?>
                                    <span class="badge" style="background: #00a32a; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">
                                        ✓ <?php esc_html_e('Verificado', 'flavor-chat-ia'); ?>
                                    </span>
                                    <?php if ($conductor->fecha_verificacion) : ?>
                                        <br>
                                        <small style="color: #666;"><?php echo date('d/m/Y', strtotime($conductor->fecha_verificacion)); ?></small>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <span class="badge" style="background: #d63638; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">
                                        ⚠ <?php esc_html_e('Pendiente', 'flavor-chat-ia'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $color_estado = [
                                    'activo' => '#00a32a',
                                    'inactivo' => '#666',
                                    'suspendido' => '#d63638'
                                ];
                                ?>
                                <span class="badge" style="background: <?php echo esc_attr($color_estado[$conductor->estado] ?? '#666'); ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">
                                    <?php echo esc_html(ucfirst($conductor->estado)); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($conductor->fecha_registro)); ?>
                                <br>
                                <small style="color: #666;"><?php echo human_time_diff(strtotime($conductor->fecha_registro), current_time('timestamp')); ?></small>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-conductores&action=ver&conductor_id=' . $conductor->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Ver Perfil', 'flavor-chat-ia'); ?>
                                </a>
                                <?php if (!$conductor->verificado) : ?>
                                    <br><br>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-conductores&action=verificar&conductor_id=' . $conductor->id)); ?>" class="button button-small button-primary">
                                        <?php esc_html_e('Verificar', 'flavor-chat-ia'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <div style="color: #666;">
                                <span class="dashicons dashicons-admin-users" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('No se encontraron conductores con los filtros seleccionados.', 'flavor-chat-ia'); ?></p>
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
                        esc_html__('%s conductores', 'flavor-chat-ia'),
                        number_format_i18n($total_conductores)
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
