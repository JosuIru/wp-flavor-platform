<?php
/**
 * Vista Producción de Compost
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_recogidas = $wpdb->prefix . 'flavor_compostaje_recogidas';
$tabla_composteras = $wpdb->prefix . 'flavor_compostaje_composteras';

// Verificar si la tabla existe
$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_recogidas'") === $tabla_recogidas;

// Paginación
$por_pagina = 25;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Filtros
$filtro_compostera = isset($_GET['compostera_id']) ? intval($_GET['compostera_id']) : 0;
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? sanitize_text_field($_GET['fecha_desde']) : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? sanitize_text_field($_GET['fecha_hasta']) : '';

// Construir WHERE
$where_clauses = ['1=1'];
$where_values = [];

if ($filtro_compostera > 0) {
    $where_clauses[] = "r.compostera_id = %d";
    $where_values[] = $filtro_compostera;
}

if (!empty($filtro_fecha_desde)) {
    $where_clauses[] = "r.fecha_recogida >= %s";
    $where_values[] = $filtro_fecha_desde;
}

if (!empty($filtro_fecha_hasta)) {
    $where_clauses[] = "r.fecha_recogida <= %s";
    $where_values[] = $filtro_fecha_hasta;
}

$where_sql = implode(' AND ', $where_clauses);

// Datos reales o demo
if ($tabla_existe) {
    // Estadísticas
    $total_recogidas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_recogidas");
    $total_kg = (float) $wpdb->get_var("SELECT COALESCE(SUM(cantidad_kg), 0) FROM $tabla_recogidas");
    $kg_mes_actual = (float) $wpdb->get_var("SELECT COALESCE(SUM(cantidad_kg), 0) FROM $tabla_recogidas WHERE MONTH(fecha_recogida) = MONTH(CURDATE()) AND YEAR(fecha_recogida) = YEAR(CURDATE())");
    $participantes_activos = (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_recogidas WHERE fecha_recogida >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");

    // Promedio por recogida
    $promedio_kg = $total_recogidas > 0 ? round($total_kg / $total_recogidas, 2) : 0;

    // Composteras para filtro
    $composteras_disponibles = $wpdb->get_results("SELECT id, nombre FROM $tabla_composteras ORDER BY nombre");

    // Producción por mes (últimos 6 meses)
    $produccion_por_mes = $wpdb->get_results("
        SELECT DATE_FORMAT(fecha_recogida, '%Y-%m') as mes,
               SUM(cantidad_kg) as total_kg,
               COUNT(*) as recogidas
        FROM $tabla_recogidas
        WHERE fecha_recogida >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha_recogida, '%Y-%m')
        ORDER BY mes ASC
    ");

    // Contar total
    $query_count = "SELECT COUNT(*) FROM $tabla_recogidas r WHERE $where_sql";
    if (!empty($where_values)) {
        $total_registros = (int) $wpdb->get_var($wpdb->prepare($query_count, $where_values));
    } else {
        $total_registros = (int) $wpdb->get_var($query_count);
    }

    // Obtener recogidas
    $query = "
        SELECT r.*, c.nombre as compostera_nombre, u.display_name as usuario_nombre
        FROM $tabla_recogidas r
        LEFT JOIN $tabla_composteras c ON r.compostera_id = c.id
        LEFT JOIN {$wpdb->users} u ON r.usuario_id = u.ID
        WHERE $where_sql
        ORDER BY r.fecha_recogida DESC
        LIMIT %d OFFSET %d
    ";

    $query_values = array_merge($where_values, [$por_pagina, $offset]);
    $recogidas = $wpdb->get_results($wpdb->prepare($query, $query_values));

    $usar_demo = empty($recogidas) && $filtro_compostera === 0 && empty($filtro_fecha_desde);
} else {
    $usar_demo = true;
}

// Demo data
if ($usar_demo) {
    $total_recogidas = 324;
    $total_kg = 1856.5;
    $kg_mes_actual = 145.8;
    $participantes_activos = 28;
    $promedio_kg = 5.73;
    $total_registros = 12;

    $composteras_disponibles = [
        (object) ['id' => 1, 'nombre' => 'Compostera A'],
        (object) ['id' => 2, 'nombre' => 'Compostera B'],
        (object) ['id' => 3, 'nombre' => 'Compostera C'],
    ];

    $produccion_por_mes = [
        (object) ['mes' => date('Y-m', strtotime('-5 months')), 'total_kg' => 125.3, 'recogidas' => 42],
        (object) ['mes' => date('Y-m', strtotime('-4 months')), 'total_kg' => 138.7, 'recogidas' => 48],
        (object) ['mes' => date('Y-m', strtotime('-3 months')), 'total_kg' => 156.2, 'recogidas' => 52],
        (object) ['mes' => date('Y-m', strtotime('-2 months')), 'total_kg' => 142.9, 'recogidas' => 47],
        (object) ['mes' => date('Y-m', strtotime('-1 month')), 'total_kg' => 168.4, 'recogidas' => 58],
        (object) ['mes' => date('Y-m'), 'total_kg' => 145.8, 'recogidas' => 51],
    ];

    $recogidas = [
        (object) ['id' => 1, 'fecha_recogida' => date('Y-m-d'), 'cantidad_kg' => 3.5, 'compostera_nombre' => 'Compostera A', 'usuario_nombre' => 'María García', 'tipo_residuo' => 'Vegetal', 'notas' => 'Restos de cocina'],
        (object) ['id' => 2, 'fecha_recogida' => date('Y-m-d', strtotime('-1 day')), 'cantidad_kg' => 2.8, 'compostera_nombre' => 'Compostera B', 'usuario_nombre' => 'Carlos Rodríguez', 'tipo_residuo' => 'Mixto', 'notas' => ''],
        (object) ['id' => 3, 'fecha_recogida' => date('Y-m-d', strtotime('-1 day')), 'cantidad_kg' => 4.2, 'compostera_nombre' => 'Compostera A', 'usuario_nombre' => 'Ana Martínez', 'tipo_residuo' => 'Vegetal', 'notas' => 'Hojas y ramas pequeñas'],
        (object) ['id' => 4, 'fecha_recogida' => date('Y-m-d', strtotime('-2 days')), 'cantidad_kg' => 5.1, 'compostera_nombre' => 'Compostera C', 'usuario_nombre' => 'Pedro López', 'tipo_residuo' => 'Vegetal', 'notas' => ''],
        (object) ['id' => 5, 'fecha_recogida' => date('Y-m-d', strtotime('-2 days')), 'cantidad_kg' => 2.3, 'compostera_nombre' => 'Compostera A', 'usuario_nombre' => 'Laura Sánchez', 'tipo_residuo' => 'Mixto', 'notas' => 'Restos de poda'],
        (object) ['id' => 6, 'fecha_recogida' => date('Y-m-d', strtotime('-3 days')), 'cantidad_kg' => 6.8, 'compostera_nombre' => 'Compostera B', 'usuario_nombre' => 'Miguel Torres', 'tipo_residuo' => 'Vegetal', 'notas' => 'Del huerto'],
        (object) ['id' => 7, 'fecha_recogida' => date('Y-m-d', strtotime('-3 days')), 'cantidad_kg' => 3.9, 'compostera_nombre' => 'Compostera C', 'usuario_nombre' => 'Carmen Ruiz', 'tipo_residuo' => 'Vegetal', 'notas' => ''],
        (object) ['id' => 8, 'fecha_recogida' => date('Y-m-d', strtotime('-4 days')), 'cantidad_kg' => 4.5, 'compostera_nombre' => 'Compostera A', 'usuario_nombre' => 'Francisco Díaz', 'tipo_residuo' => 'Mixto', 'notas' => 'Restos de cocina y jardín'],
    ];
}

$total_paginas = ceil($total_registros / $por_pagina);

// Tipos de residuo
$tipos_residuo = [
    'Vegetal' => '#28a745',
    'Mixto' => '#ffc107',
    'Jardín' => '#17a2b8',
];
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-carrot" style="color: #795548;"></span>
        <?php echo esc_html__('Producción de Compost', 'flavor-platform'); ?>
    </h1>

    <?php if ($usar_demo): ?>
        <div class="notice notice-info" style="margin: 15px 0;">
            <p><span class="dashicons dashicons-info"></span> <?php echo esc_html__('Mostrando datos de demostración.', 'flavor-platform'); ?></p>
        </div>
    <?php endif; ?>

    <hr class="wp-header-end">

    <!-- Estadísticas -->
    <div class="produccion-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #795548; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #795548;"><?php echo number_format($total_recogidas); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Total Recogidas', 'flavor-platform'); ?></div>
                </div>
                <span class="dashicons dashicons-archive" style="font-size: 32px; color: #795548; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #28a745;"><?php echo number_format($total_kg, 1); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Kg Totales', 'flavor-platform'); ?></div>
                </div>
                <span class="dashicons dashicons-chart-bar" style="font-size: 32px; color: #28a745; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #17a2b8;"><?php echo number_format($kg_mes_actual, 1); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Kg Este Mes', 'flavor-platform'); ?></div>
                </div>
                <span class="dashicons dashicons-calendar" style="font-size: 32px; color: #17a2b8; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #6f42c1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #6f42c1;"><?php echo number_format($participantes_activos); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Participantes Activos', 'flavor-platform'); ?></div>
                </div>
                <span class="dashicons dashicons-groups" style="font-size: 32px; color: #6f42c1; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #f39c12; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 28px; font-weight: bold; color: #f39c12;"><?php echo number_format($promedio_kg, 2); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php echo esc_html__('Kg/Recogida', 'flavor-platform'); ?></div>
                </div>
                <span class="dashicons dashicons-performance" style="font-size: 32px; color: #f39c12; opacity: 0.3;"></span>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
        <!-- Filtros -->
        <div style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <form method="get" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'compostaje-produccion'); ?>">

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Compostera', 'flavor-platform'); ?></label>
                    <select name="compostera_id" style="min-width: 150px;">
                        <option value="0"><?php echo esc_html__('Todas', 'flavor-platform'); ?></option>
                        <?php foreach ($composteras_disponibles as $compostera): ?>
                            <option value="<?php echo esc_attr($compostera->id); ?>" <?php selected($filtro_compostera, $compostera->id); ?>>
                                <?php echo esc_html($compostera->nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Desde', 'flavor-platform'); ?></label>
                    <input type="date" name="fecha_desde" value="<?php echo esc_attr($filtro_fecha_desde); ?>" style="width: 140px;">
                </div>

                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 12px;"><?php echo esc_html__('Hasta', 'flavor-platform'); ?></label>
                    <input type="date" name="fecha_hasta" value="<?php echo esc_attr($filtro_fecha_hasta); ?>" style="width: 140px;">
                </div>

                <button type="submit" class="button button-primary"><?php echo esc_html__('Filtrar', 'flavor-platform'); ?></button>

                <?php if ($filtro_compostera > 0 || !empty($filtro_fecha_desde) || !empty($filtro_fecha_hasta)): ?>
                    <a href="<?php echo admin_url('admin.php?page=' . esc_attr($_GET['page'] ?? 'compostaje-produccion')); ?>" class="button">
                        <?php echo esc_html__('Limpiar', 'flavor-platform'); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Gráfico de producción -->
        <div style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #666;">
                <span class="dashicons dashicons-chart-area" style="font-size: 16px;"></span>
                <?php echo esc_html__('Producción Mensual (kg)', 'flavor-platform'); ?>
            </h4>
            <canvas id="chartProduccion" height="100"></canvas>
        </div>
    </div>

    <!-- Tabla -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-platform'); ?></th>
                    <th style="width: 110px;"><?php echo esc_html__('Fecha', 'flavor-platform'); ?></th>
                    <th><?php echo esc_html__('Usuario', 'flavor-platform'); ?></th>
                    <th><?php echo esc_html__('Compostera', 'flavor-platform'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Tipo', 'flavor-platform'); ?></th>
                    <th style="width: 100px; text-align: right;"><?php echo esc_html__('Cantidad', 'flavor-platform'); ?></th>
                    <th><?php echo esc_html__('Notas', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recogidas)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <span class="dashicons dashicons-carrot" style="font-size: 48px; color: #ddd;"></span>
                            <p style="color: #666; margin-top: 10px;"><?php echo esc_html__('No hay recogidas registradas.', 'flavor-platform'); ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recogidas as $recogida):
                        $tipo_color = $tipos_residuo[$recogida->tipo_residuo ?? 'Mixto'] ?? '#666';
                    ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($recogida->id); ?></strong></td>
                            <td><?php echo date_i18n('d/m/Y', strtotime($recogida->fecha_recogida)); ?></td>
                            <td><strong><?php echo esc_html($recogida->usuario_nombre ?: '-'); ?></strong></td>
                            <td>
                                <span style="background: #e9ecef; padding: 2px 8px; border-radius: 4px; font-size: 12px;">
                                    <?php echo esc_html($recogida->compostera_nombre ?: '-'); ?>
                                </span>
                            </td>
                            <td>
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; background: <?php echo esc_attr($tipo_color); ?>20; color: <?php echo esc_attr($tipo_color); ?>;">
                                    <?php echo esc_html($recogida->tipo_residuo ?? 'Mixto'); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <strong><?php echo number_format($recogida->cantidad_kg, 2); ?></strong> kg
                            </td>
                            <td style="color: #666; font-size: 13px;">
                                <?php echo esc_html($recogida->notas ?: '-'); ?>
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
                <span class="displaying-num"><?php printf(esc_html__('%s recogidas', 'flavor-platform'), number_format($total_registros)); ?></span>
                <span class="pagination-links">
                    <?php
                    $url_base = admin_url('admin.php?page=' . esc_attr($_GET['page'] ?? 'compostaje-produccion'));
                    if ($filtro_compostera > 0) $url_base .= '&compostera_id=' . $filtro_compostera;
                    if (!empty($filtro_fecha_desde)) $url_base .= '&fecha_desde=' . urlencode($filtro_fecha_desde);
                    if (!empty($filtro_fecha_hasta)) $url_base .= '&fecha_hasta=' . urlencode($filtro_fecha_hasta);

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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
    const ctx = document.getElementById('chartProduccion');
    if (ctx) {
        const data = <?php echo json_encode($produccion_por_mes); ?>;
        const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => {
                    const [year, month] = d.mes.split('-');
                    return meses[parseInt(month) - 1];
                }),
                datasets: [{
                    label: 'Kg',
                    data: data.map(d => parseFloat(d.total_kg)),
                    backgroundColor: '#28a74580',
                    borderColor: '#28a745',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>

<style>
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transition: all 0.2s ease;
}
</style>
