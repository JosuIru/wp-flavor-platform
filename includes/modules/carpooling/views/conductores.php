<?php
/**
 * Vista de Gestión de Conductores - Carpooling
 *
 * Nota: Los conductores se derivan de usuarios que han publicado viajes,
 * ya que no existe tabla separada de conductores.
 *
 * @package FlavorPlatform
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
$tabla_vehiculos = $wpdb->prefix . 'flavor_carpooling_vehiculos';
$tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';
$tabla_valoraciones = $wpdb->prefix . 'flavor_carpooling_valoraciones';

// Obtener filtros
$filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Paginación
$elementos_por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $elementos_por_pagina;

// Construir consulta para conductores (usuarios que han creado viajes)
$where = "WHERE 1=1";

if (!empty($filtro_busqueda)) {
    $where .= $wpdb->prepare(" AND (u.display_name LIKE %s OR u.user_email LIKE %s)",
        '%' . $wpdb->esc_like($filtro_busqueda) . '%',
        '%' . $wpdb->esc_like($filtro_busqueda) . '%'
    );
}

// Obtener total de conductores únicos
$total_conductores = $wpdb->get_var(
    "SELECT COUNT(DISTINCT v.conductor_id)
    FROM {$tabla_viajes} v
    INNER JOIN {$wpdb->users} u ON v.conductor_id = u.ID
    {$where}"
);

// Obtener conductores con estadísticas
$conductores = $wpdb->get_results(
    "SELECT
        u.ID as id,
        u.display_name,
        u.user_email,
        u.user_registered as fecha_registro,
        COUNT(v.id) as total_viajes,
        SUM(CASE WHEN v.estado = 'finalizado' THEN 1 ELSE 0 END) as viajes_completados,
        (SELECT COUNT(*) FROM {$tabla_vehiculos} WHERE propietario_id = u.ID) as total_vehiculos,
        COALESCE((SELECT AVG(puntuacion) FROM {$tabla_valoraciones} WHERE valorado_id = u.ID), 0) as valoracion_promedio,
        (SELECT COUNT(*) FROM {$tabla_valoraciones} WHERE valorado_id = u.ID) as total_valoraciones
    FROM {$tabla_viajes} v
    INNER JOIN {$wpdb->users} u ON v.conductor_id = u.ID
    {$where}
    GROUP BY v.conductor_id
    ORDER BY total_viajes DESC
    LIMIT {$elementos_por_pagina} OFFSET {$offset}"
);

$total_paginas = ceil($total_conductores / $elementos_por_pagina);

// Estadísticas rápidas
$stats_totales = (object) [
    'total' => $total_conductores ?? 0,
    'activos' => $wpdb->get_var("SELECT COUNT(DISTINCT conductor_id) FROM {$tabla_viajes} WHERE estado = 'activo'") ?? 0,
    'con_viajes_completados' => $wpdb->get_var("SELECT COUNT(DISTINCT conductor_id) FROM {$tabla_viajes} WHERE estado = 'finalizado'") ?? 0
];
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Gestión de Conductores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <hr class="wp-header-end">

    <!-- Estadísticas rápidas -->
    <div class="flavor-stats-mini" style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($stats_totales->total, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Total Conductores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #00a32a;">
                <?php echo esc_html(number_format($stats_totales->activos, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Con viajes activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($stats_totales->con_viajes_completados, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Con viajes completados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card" style="padding: 15px; margin: 20px 0;">
        <form method="get" action="">
            <input type="hidden" name="page" value="flavor-carpooling-conductores">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">

                <div>
                    <label for="busqueda"><?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" name="s" id="busqueda" class="regular-text" placeholder="<?php esc_attr_e('Nombre o email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" value="<?php echo esc_attr($filtro_busqueda); ?>">
                </div>

                <div>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-conductores')); ?>" class="button">
                        <?php esc_html_e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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
                    <th style="width: 50px;"><?php esc_html_e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Conductor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Valoracion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Vehiculos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Registro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
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
                                    <?php esc_html_e('Completados / Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </small>
                            </td>
                            <td>
                                <?php echo esc_html($conductor->total_vehiculos); ?>
                                <?php if ($conductor->total_vehiculos > 0) : ?>
                                    <br>
                                    <small>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-conductores&action=vehiculos&conductor_id=' . $conductor->id)); ?>">
                                            <?php esc_html_e('Ver vehiculos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </a>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($conductor->fecha_registro)); ?>
                                <br>
                                <small style="color: #666;"><?php echo human_time_diff(strtotime($conductor->fecha_registro), current_time('timestamp')); ?></small>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-carpooling-viajes&conductor_id=' . $conductor->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Ver Viajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <div style="color: #666;">
                                <span class="dashicons dashicons-admin-users" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('No se encontraron conductores.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginacion -->
    <?php if ($total_paginas > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(
                        esc_html__('%s conductores', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
