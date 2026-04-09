<?php
/**
 * Vista Participantes - Módulo Compostaje
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_depositos = $wpdb->prefix . 'flavor_compostaje_depositos';

// Verificar si la tabla existe
$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_depositos'") === $tabla_depositos;

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Filtros
$filtro_busqueda = isset($_GET['busqueda']) ? sanitize_text_field($_GET['busqueda']) : '';
$filtro_actividad = isset($_GET['actividad']) ? sanitize_text_field($_GET['actividad']) : '';

// Construir WHERE
$where_clauses = ['1=1'];
$where_values = [];

if (!empty($filtro_busqueda)) {
    $where_clauses[] = "(u.display_name LIKE %s OR u.user_email LIKE %s)";
    $busqueda_like = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
    $where_values[] = $busqueda_like;
    $where_values[] = $busqueda_like;
}

$having_sql = '';
if ($filtro_actividad === 'activos') {
    $having_sql = "HAVING MAX(d.fecha_deposito) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
} elseif ($filtro_actividad === 'inactivos') {
    $having_sql = "HAVING MAX(d.fecha_deposito) < DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
}

$where_sql = implode(' AND ', $where_clauses);

// Datos reales o demo
if ($tabla_existe) {
    // Estadísticas
    $total_participantes = (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_depositos");
    $participantes_activos = (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_depositos WHERE fecha_deposito >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $total_kg_depositados = (float) $wpdb->get_var("SELECT COALESCE(SUM(cantidad_kg), 0) FROM $tabla_depositos");
    $promedio_por_participante = $total_participantes > 0 ? round($total_kg_depositados / $total_participantes, 2) : 0;

    // Top contribuyentes
    $top_contribuyentes = $wpdb->get_results("
        SELECT u.display_name, SUM(d.cantidad_kg) as total_kg
        FROM {$wpdb->users} u
        INNER JOIN $tabla_depositos d ON u.ID = d.usuario_id
        GROUP BY u.ID
        ORDER BY total_kg DESC
        LIMIT 5
    ");

    // Contar total
    $query_count = "
        SELECT COUNT(DISTINCT u.ID)
        FROM {$wpdb->users} u
        INNER JOIN $tabla_depositos d ON u.ID = d.usuario_id
        WHERE $where_sql
    ";
    if (!empty($where_values)) {
        $total_registros = (int) $wpdb->get_var($wpdb->prepare($query_count, $where_values));
    } else {
        $total_registros = (int) $wpdb->get_var($query_count);
    }

    // Obtener participantes
    $query = "
        SELECT u.ID, u.display_name, u.user_email,
               COUNT(d.id) as total_depositos,
               SUM(d.cantidad_kg) as total_kg,
               MAX(d.fecha_deposito) as ultimo_deposito,
               MIN(d.fecha_deposito) as primer_deposito
        FROM {$wpdb->users} u
        INNER JOIN $tabla_depositos d ON u.ID = d.usuario_id
        WHERE $where_sql
        GROUP BY u.ID
        $having_sql
        ORDER BY total_kg DESC
        LIMIT %d OFFSET %d
    ";

    $query_values = array_merge($where_values, [$por_pagina, $offset]);
    $participantes = $wpdb->get_results($wpdb->prepare($query, $query_values));

    $usar_demo = empty($participantes) && empty($filtro_busqueda) && empty($filtro_actividad);
} else {
    $usar_demo = true;
}

// Demo data
if ($usar_demo) {
    $total_participantes = 45;
    $participantes_activos = 32;
    $total_kg_depositados = 1856.5;
    $promedio_por_participante = 41.26;
    $total_registros = 12;

    $top_contribuyentes = [
        (object) ['display_name' => 'María García', 'total_kg' => 156.8],
        (object) ['display_name' => 'Carlos Rodríguez', 'total_kg' => 142.3],
        (object) ['display_name' => 'Ana Martínez', 'total_kg' => 128.7],
        (object) ['display_name' => 'Pedro López', 'total_kg' => 115.2],
        (object) ['display_name' => 'Laura Sánchez', 'total_kg' => 98.4],
    ];

    $participantes = [
        (object) ['ID' => 1, 'display_name' => 'María García', 'user_email' => 'maria.garcia@ejemplo.com', 'total_depositos' => 48, 'total_kg' => 156.8, 'ultimo_deposito' => date('Y-m-d', strtotime('-2 days')), 'primer_deposito' => '2023-03-15'],
        (object) ['ID' => 2, 'display_name' => 'Carlos Rodríguez', 'user_email' => 'carlos.r@ejemplo.com', 'total_depositos' => 42, 'total_kg' => 142.3, 'ultimo_deposito' => date('Y-m-d', strtotime('-1 day')), 'primer_deposito' => '2023-04-20'],
        (object) ['ID' => 3, 'display_name' => 'Ana Martínez', 'user_email' => 'ana.m@ejemplo.com', 'total_depositos' => 38, 'total_kg' => 128.7, 'ultimo_deposito' => date('Y-m-d'), 'primer_deposito' => '2023-05-10'],
        (object) ['ID' => 4, 'display_name' => 'Pedro López', 'user_email' => 'pedro.l@ejemplo.com', 'total_depositos' => 35, 'total_kg' => 115.2, 'ultimo_deposito' => date('Y-m-d', strtotime('-5 days')), 'primer_deposito' => '2023-06-01'],
        (object) ['ID' => 5, 'display_name' => 'Laura Sánchez', 'user_email' => 'laura.s@ejemplo.com', 'total_depositos' => 32, 'total_kg' => 98.4, 'ultimo_deposito' => date('Y-m-d', strtotime('-3 days')), 'primer_deposito' => '2023-07-15'],
        (object) ['ID' => 6, 'display_name' => 'Miguel Torres', 'user_email' => 'miguel.t@ejemplo.com', 'total_depositos' => 28, 'total_kg' => 85.6, 'ultimo_deposito' => date('Y-m-d', strtotime('-40 days')), 'primer_deposito' => '2023-08-20'],
        (object) ['ID' => 7, 'display_name' => 'Carmen Ruiz', 'user_email' => 'carmen.r@ejemplo.com', 'total_depositos' => 25, 'total_kg' => 72.3, 'ultimo_deposito' => date('Y-m-d', strtotime('-7 days')), 'primer_deposito' => '2023-09-05'],
        (object) ['ID' => 8, 'display_name' => 'Francisco Díaz', 'user_email' => 'fran.d@ejemplo.com', 'total_depositos' => 22, 'total_kg' => 65.8, 'ultimo_deposito' => date('Y-m-d', strtotime('-10 days')), 'primer_deposito' => '2023-10-12'],
    ];
}

$total_paginas = ceil($total_registros / $por_pagina);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups" style="color: #795548;"></span>
        <?php echo esc_html__('Participantes - Compostaje', 'flavor-platform'); ?>
    </h1>

    <?php if ($usar_demo): ?>
        <div class="notice notice-info" style="margin: 15px 0;">
            <p><span class="dashicons dashicons-info"></span> <?php echo esc_html__('Mostrando datos de demostración.', 'flavor-platform'); ?></p>
        </div>
    <?php endif; ?>

    <hr class="wp-header-end">

    <!-- Estadísticas -->
    <div class="participantes-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #795548; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #795548;"><?php echo number_format($total_participantes); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Total Participantes', 'flavor-platform'); ?></div>
                </div>
                <span class="dashicons dashicons-groups" style="font-size: 32px; color: #795548; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #28a745;"><?php echo number_format($participantes_activos); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Activos (30 días)', 'flavor-platform'); ?></div>
                </div>
                <span class="dashicons dashicons-yes-alt" style="font-size: 32px; color: #28a745; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #17a2b8;"><?php echo number_format($total_kg_depositados, 1); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Kg Totales', 'flavor-platform'); ?></div>
                </div>
                <span class="dashicons dashicons-chart-bar" style="font-size: 32px; color: #17a2b8; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #f39c12; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #f39c12;"><?php echo number_format($promedio_por_participante, 1); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Kg/Participante', 'flavor-platform'); ?></div>
                </div>
                <span class="dashicons dashicons-performance" style="font-size: 32px; color: #f39c12; opacity: 0.3;"></span>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
        <!-- Filtros -->
        <div style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'compostaje-participantes'); ?>">

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Buscar', 'flavor-platform'); ?></label>
                    <input type="text" name="busqueda" value="<?php echo esc_attr($filtro_busqueda); ?>"
                           placeholder="<?php echo esc_attr__('Nombre o email...', 'flavor-platform'); ?>" style="min-width: 200px;">
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Actividad', 'flavor-platform'); ?></label>
                    <select name="actividad" style="min-width: 150px;">
                        <option value=""><?php echo esc_html__('Todos', 'flavor-platform'); ?></option>
                        <option value="activos" <?php selected($filtro_actividad, 'activos'); ?>><?php echo esc_html__('Activos (30 días)', 'flavor-platform'); ?></option>
                        <option value="inactivos" <?php selected($filtro_actividad, 'inactivos'); ?>><?php echo esc_html__('Inactivos', 'flavor-platform'); ?></option>
                    </select>
                </div>

                <button type="submit" class="button button-primary"><?php echo esc_html__('Filtrar', 'flavor-platform'); ?></button>

                <?php if (!empty($filtro_busqueda) || !empty($filtro_actividad)): ?>
                    <a href="<?php echo admin_url('admin.php?page=' . esc_attr($_GET['page'] ?? 'compostaje-participantes')); ?>" class="button">
                        <?php echo esc_html__('Limpiar', 'flavor-platform'); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Top Contribuyentes -->
        <div style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h4 style="margin: 0 0 15px 0; font-size: 14px; color: #666;">
                <span class="dashicons dashicons-star-filled" style="font-size: 16px; color: #ffc107;"></span>
                <?php echo esc_html__('Top Contribuyentes', 'flavor-platform'); ?>
            </h4>
            <?php foreach ($top_contribuyentes as $index => $top): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; <?php echo $index < count($top_contribuyentes) - 1 ? 'border-bottom: 1px solid #eee;' : ''; ?>">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="background: <?php echo $index < 3 ? '#ffc107' : '#e9ecef'; ?>; color: <?php echo $index < 3 ? '#000' : '#666'; ?>; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">
                            <?php echo $index + 1; ?>
                        </span>
                        <span style="font-weight: 500;"><?php echo esc_html($top->display_name); ?></span>
                    </div>
                    <span style="color: #28a745; font-weight: 600;"><?php echo number_format($top->total_kg, 1); ?> kg</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tabla -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-platform'); ?></th>
                    <th><?php echo esc_html__('Usuario', 'flavor-platform'); ?></th>
                    <th><?php echo esc_html__('Email', 'flavor-platform'); ?></th>
                    <th style="width: 100px; text-align: right;"><?php echo esc_html__('Total Kg', 'flavor-platform'); ?></th>
                    <th style="width: 90px; text-align: center;"><?php echo esc_html__('Depósitos', 'flavor-platform'); ?></th>
                    <th style="width: 110px;"><?php echo esc_html__('Último', 'flavor-platform'); ?></th>
                    <th style="width: 110px;"><?php echo esc_html__('Desde', 'flavor-platform'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Estado', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($participantes)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-groups" style="font-size: 48px; color: #ddd;"></span>
                            <p style="color: #666; margin-top: 10px;"><?php echo esc_html__('No hay participantes.', 'flavor-platform'); ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($participantes as $participante):
                        $dias_desde_ultimo = floor((time() - strtotime($participante->ultimo_deposito)) / 86400);
                        $es_activo = $dias_desde_ultimo <= 30;
                    ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($participante->ID); ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($participante->display_name); ?></strong>
                                <?php if ($participante->total_kg >= 100): ?>
                                    <span class="dashicons dashicons-star-filled" style="color: #ffc107; font-size: 14px;" title="<?php echo esc_attr__('Contribuyente destacado', 'flavor-platform'); ?>"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($participante->user_email); ?>">
                                    <?php echo esc_html($participante->user_email); ?>
                                </a>
                            </td>
                            <td style="text-align: right;">
                                <strong style="color: #28a745;"><?php echo number_format($participante->total_kg, 2); ?></strong> kg
                            </td>
                            <td style="text-align: center;">
                                <span style="background: #e9ecef; padding: 4px 10px; border-radius: 12px; font-weight: 600;">
                                    <?php echo number_format($participante->total_depositos); ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n('d/m/Y', strtotime($participante->ultimo_deposito)); ?></td>
                            <td><?php echo date_i18n('d/m/Y', strtotime($participante->primer_deposito)); ?></td>
                            <td>
                                <?php if ($es_activo): ?>
                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; background: #28a74520; color: #28a745;">
                                        <?php echo esc_html__('Activo', 'flavor-platform'); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; background: #6c757d20; color: #6c757d;">
                                        <?php echo esc_html__('Inactivo', 'flavor-platform'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
        <div class="tablenav bottom" style="margin-top: 20px;">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf(esc_html__('%s participantes', 'flavor-platform'), number_format($total_registros)); ?></span>
                <span class="pagination-links">
                    <?php
                    $url_base = admin_url('admin.php?page=' . esc_attr($_GET['page'] ?? 'compostaje-participantes'));
                    if (!empty($filtro_busqueda)) $url_base .= '&busqueda=' . urlencode($filtro_busqueda);
                    if (!empty($filtro_actividad)) $url_base .= '&actividad=' . urlencode($filtro_actividad);

                    if ($pagina_actual > 1): ?>
                        <a class="first-page button" href="<?php echo esc_url($url_base . '&paged=1'); ?>">&laquo;</a>
                        <a class="prev-page button" href="<?php echo esc_url($url_base . '&paged=' . ($pagina_actual - 1)); ?>">&lsaquo;</a>
                    <?php endif; ?>

                    <span class="paging-input"><?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a class="next-page button" href="<?php echo esc_url($url_base . '&paged=' . ($pagina_actual + 1)); ?>">&rsaquo;</a>
                        <a class="last-page button" href="<?php echo esc_url($url_base . '&paged=' . $total_paginas); ?>">&raquo;</a>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transition: all 0.2s ease;
}
</style>
