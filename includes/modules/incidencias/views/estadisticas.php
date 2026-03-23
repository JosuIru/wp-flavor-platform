<?php
/**
 * Vista Estadísticas - Módulo Incidencias
 *
 * Analytics y métricas de resolución de incidencias
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

// Rango de fechas
$fecha_inicio = isset($_GET['fecha_inicio']) ? sanitize_text_field($_GET['fecha_inicio']) : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? sanitize_text_field($_GET['fecha_fin']) : date('Y-m-d');

// Estadísticas generales del período
$stats_generales = $wpdb->get_row($wpdb->prepare("
    SELECT
        COUNT(*) as total_reportes,
        SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) as total_resueltas,
        SUM(CASE WHEN estado IN ('pendiente', 'en_proceso') THEN 1 ELSE 0 END) as total_abiertas,
        AVG(CASE WHEN estado = 'resuelta' AND fecha_resolucion IS NOT NULL
            THEN TIMESTAMPDIFF(HOUR, fecha_reporte, fecha_resolucion)
            ELSE NULL END) as tiempo_promedio_resolucion,
        MIN(CASE WHEN estado = 'resuelta' AND fecha_resolucion IS NOT NULL
            THEN TIMESTAMPDIFF(HOUR, fecha_reporte, fecha_resolucion)
            ELSE NULL END) as tiempo_minimo,
        MAX(CASE WHEN estado = 'resuelta' AND fecha_resolucion IS NOT NULL
            THEN TIMESTAMPDIFF(HOUR, fecha_reporte, fecha_resolucion)
            ELSE NULL END) as tiempo_maximo
    FROM $tabla_incidencias
    WHERE fecha_reporte BETWEEN %s AND %s
", $fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'));

// Tendencia temporal (por día)
$tendencia_diaria = $wpdb->get_results($wpdb->prepare("
    SELECT
        DATE(fecha_reporte) as fecha,
        COUNT(*) as total_reportadas,
        SUM(CASE WHEN fecha_resolucion IS NOT NULL AND DATE(fecha_resolucion) = DATE(fecha_reporte) THEN 1 ELSE 0 END) as resueltas_mismo_dia
    FROM $tabla_incidencias
    WHERE fecha_reporte BETWEEN %s AND %s
    GROUP BY DATE(fecha_reporte)
    ORDER BY fecha ASC
", $fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'));

// Distribución por hora del día
$distribucion_horaria = $wpdb->get_results($wpdb->prepare("
    SELECT
        HOUR(fecha_reporte) as hora,
        COUNT(*) as total
    FROM $tabla_incidencias
    WHERE fecha_reporte BETWEEN %s AND %s
    GROUP BY HOUR(fecha_reporte)
    ORDER BY hora ASC
", $fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'));

// Top 5 categorías
$top_categorias = $wpdb->get_results($wpdb->prepare("
    SELECT
        categoria,
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) as resueltas
    FROM $tabla_incidencias
    WHERE fecha_reporte BETWEEN %s AND %s
    GROUP BY categoria
    ORDER BY total DESC
    LIMIT 5
", $fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'));

// Estadísticas por prioridad
$stats_prioridad = $wpdb->get_results($wpdb->prepare("
    SELECT
        prioridad,
        COUNT(*) as total,
        AVG(CASE WHEN estado = 'resuelta' AND fecha_resolucion IS NOT NULL
            THEN TIMESTAMPDIFF(HOUR, fecha_reporte, fecha_resolucion)
            ELSE NULL END) as tiempo_promedio
    FROM $tabla_incidencias
    WHERE fecha_reporte BETWEEN %s AND %s
    GROUP BY prioridad
    ORDER BY FIELD(prioridad, 'urgente', 'alta', 'media', 'baja')
", $fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'));

// Comparativa mensual (últimos 6 meses)
$comparativa_mensual = $wpdb->get_results("
    SELECT
        DATE_FORMAT(fecha_reporte, '%Y-%m') as mes,
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) as resueltas
    FROM $tabla_incidencias
    WHERE fecha_reporte >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha_reporte, '%Y-%m')
    ORDER BY mes ASC
");

$tasa_resolucion_periodo = $stats_generales->total_reportes > 0 ?
    round(($stats_generales->total_resueltas / $stats_generales->total_reportes) * 100, 1) : 0;
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-chart-line"></span>
        <?php echo esc_html__('Estadísticas y Analytics', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Filtro de fechas -->
    <div class="postbox" style="margin: 20px 0;">
        <div class="inside">
            <form method="get" action="" style="display: flex; align-items: end; gap: 15px; flex-wrap: wrap;">
                <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-incidencias-estadisticas', 'flavor-chat-ia'); ?>">

                <div>
                    <label><strong><?php echo esc_html__('Fecha Inicio:', 'flavor-chat-ia'); ?></strong></label><br>
                    <input type="date" name="fecha_inicio" value="<?php echo esc_attr($fecha_inicio); ?>" class="regular-text">
                </div>

                <div>
                    <label><strong><?php echo esc_html__('Fecha Fin:', 'flavor-chat-ia'); ?></strong></label><br>
                    <input type="date" name="fecha_fin" value="<?php echo esc_attr($fecha_fin); ?>" class="regular-text">
                </div>

                <div>
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-filter"></span> <?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?>
                    </button>
                    <a href="?page=incidencias-estadisticas" class="button"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs principales -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="flavor-kpi-card" style="background: white; padding: 25px; border-radius: 8px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="color: #646970; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">
                <?php echo esc_html__('Total Reportes', 'flavor-chat-ia'); ?>
            </div>
            <div style="font-size: 42px; font-weight: 700; color: #2271b1; margin-bottom: 5px;">
                <?php echo number_format($stats_generales->total_reportes); ?>
            </div>
            <div style="color: #646970; font-size: 13px;">
                <?php echo esc_html__('en el período seleccionado', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="flavor-kpi-card" style="background: white; padding: 25px; border-radius: 8px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="color: #646970; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">
                <?php echo esc_html__('Tasa de Resolución', 'flavor-chat-ia'); ?>
            </div>
            <div style="font-size: 42px; font-weight: 700; color: #00a32a; margin-bottom: 5px;">
                <?php echo $tasa_resolucion_periodo; ?>%
            </div>
            <div style="color: #646970; font-size: 13px;">
                <?php echo $stats_generales->total_resueltas; ?> de <?php echo $stats_generales->total_reportes; ?> resueltas
            </div>
        </div>

        <div class="flavor-kpi-card" style="background: white; padding: 25px; border-radius: 8px; border-left: 4px solid #f0b849; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="color: #646970; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">
                <?php echo esc_html__('Tiempo Promedio', 'flavor-chat-ia'); ?>
            </div>
            <div style="font-size: 42px; font-weight: 700; color: #f0b849; margin-bottom: 5px;">
                <?php echo $stats_generales->tiempo_promedio_resolucion ? round($stats_generales->tiempo_promedio_resolucion) : 0; ?>h
            </div>
            <div style="color: #646970; font-size: 13px;">
                <?php echo esc_html__('de resolución', 'flavor-chat-ia'); ?>
            </div>
        </div>

        <div class="flavor-kpi-card" style="background: white; padding: 25px; border-radius: 8px; border-left: 4px solid #d63638; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="color: #646970; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">
                <?php echo esc_html__('Incidencias Abiertas', 'flavor-chat-ia'); ?>
            </div>
            <div style="font-size: 42px; font-weight: 700; color: #d63638; margin-bottom: 5px;">
                <?php echo number_format($stats_generales->total_abiertas); ?>
            </div>
            <div style="color: #646970; font-size: 13px;">
                <?php echo esc_html__('pendientes o en proceso', 'flavor-chat-ia'); ?>
            </div>
        </div>

    </div>

    <!-- Gráficos principales -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin: 20px 0;">

        <!-- Tendencia diaria -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php echo esc_html__('Tendencia de Reportes Diarios', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="inside">
                <canvas id="chart-tendencia-diaria" style="max-height: 300px;"></canvas>
            </div>
        </div>

        <!-- Top categorías -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php echo esc_html__('Top 5 Categorías', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="inside">
                <canvas id="chart-top-categorias" style="max-height: 300px;"></canvas>
            </div>
        </div>

    </div>

    <!-- Más gráficos -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">

        <!-- Distribución horaria -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php echo esc_html__('Distribución por Hora del Día', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="inside">
                <canvas id="chart-distribucion-horaria" style="max-height: 250px;"></canvas>
            </div>
        </div>

        <!-- Estadísticas por prioridad -->
        <div class="postbox">
            <div class="postbox-header">
                <h2><?php echo esc_html__('Tiempo de Resolución por Prioridad', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="inside">
                <canvas id="chart-prioridad" style="max-height: 250px;"></canvas>
            </div>
        </div>

    </div>

    <!-- Comparativa mensual -->
    <div class="postbox" style="margin: 20px 0;">
        <div class="postbox-header">
            <h2><?php echo esc_html__('Comparativa Mensual (Últimos 6 Meses)', 'flavor-chat-ia'); ?></h2>
        </div>
        <div class="inside">
            <canvas id="chart-comparativa-mensual" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <!-- Tabla de estadísticas detalladas -->
    <div class="postbox" style="margin: 20px 0;">
        <div class="postbox-header">
            <h2><?php echo esc_html__('Estadísticas Detalladas por Categoría', 'flavor-chat-ia'); ?></h2>
        </div>
        <div class="inside" style="padding: 0;">
            <?php
            $stats_detalladas_categoria = $wpdb->get_results($wpdb->prepare("
                SELECT
                    categoria,
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                    SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) as resueltas,
                    AVG(CASE WHEN estado = 'resuelta' AND fecha_resolucion IS NOT NULL
                        THEN TIMESTAMPDIFF(HOUR, fecha_reporte, fecha_resolucion)
                        ELSE NULL END) as tiempo_promedio
                FROM $tabla_incidencias
                WHERE fecha_reporte BETWEEN %s AND %s
                GROUP BY categoria
                ORDER BY total DESC
            ", $fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'));
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Categoría', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Total', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Pendientes', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('En Proceso', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Resueltas', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Tasa Resolución', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Tiempo Promedio', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats_detalladas_categoria as $stat): ?>
                        <?php $tasa = $stat->total > 0 ? round(($stat->resueltas / $stat->total) * 100, 1) : 0; ?>
                        <tr>
                            <td><strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $stat->categoria))); ?></strong></td>
                            <td><?php echo number_format($stat->total); ?></td>
                            <td><?php echo number_format($stat->pendientes); ?></td>
                            <td><?php echo number_format($stat->en_proceso); ?></td>
                            <td><?php echo number_format($stat->resueltas); ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: #f0f0f1; height: 20px; border-radius: 10px; overflow: hidden;">
                                        <div style="background: #00a32a; height: 100%; width: <?php echo $tasa; ?>%; transition: width 0.3s ease;"></div>
                                    </div>
                                    <strong><?php echo $tasa; ?>%</strong>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo $stat->tiempo_promedio ? round($stat->tiempo_promedio) : 0; ?>h</strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Scripts de Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Tendencia diaria
    const ctxTendencia = document.getElementById('chart-tendencia-diaria');
    if (ctxTendencia) {
        new Chart(ctxTendencia, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($d) { return date('d/m', strtotime($d->fecha)); }, $tendencia_diaria)); ?>,
                datasets: [{
                    label: 'Reportadas',
                    data: <?php echo json_encode(array_column($tendencia_diaria, 'total_reportadas')); ?>,
                    borderColor: 'rgb(34, 113, 177)',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    // Top categorías
    const ctxTopCategorias = document.getElementById('chart-top-categorias');
    if (ctxTopCategorias) {
        new Chart(ctxTopCategorias, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($top_categorias, 'categoria')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($top_categorias, 'total')); ?>,
                    backgroundColor: [
                        'rgba(34, 113, 177, 0.8)',
                        'rgba(0, 163, 42, 0.8)',
                        'rgba(240, 184, 73, 0.8)',
                        'rgba(255, 140, 0, 0.8)',
                        'rgba(100, 105, 112, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Distribución horaria
    const ctxHoraria = document.getElementById('chart-distribucion-horaria');
    if (ctxHoraria) {
        new Chart(ctxHoraria, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($h) { return $h->hora . ':00'; }, $distribucion_horaria)); ?>,
                datasets: [{
                    label: 'Reportes',
                    data: <?php echo json_encode(array_column($distribucion_horaria, 'total')); ?>,
                    backgroundColor: 'rgba(34, 113, 177, 0.8)',
                    borderColor: 'rgb(34, 113, 177)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    // Prioridad
    const ctxPrioridad = document.getElementById('chart-prioridad');
    if (ctxPrioridad) {
        new Chart(ctxPrioridad, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($stats_prioridad, 'prioridad')); ?>,
                datasets: [{
                    label: 'Horas Promedio',
                    data: <?php echo json_encode(array_map(function($p) { return $p->tiempo_promedio ? round($p->tiempo_promedio) : 0; }, $stats_prioridad)); ?>,
                    backgroundColor: [
                        'rgba(244, 67, 54, 0.8)',
                        'rgba(255, 152, 0, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(76, 175, 80, 0.8)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Comparativa mensual
    const ctxMensual = document.getElementById('chart-comparativa-mensual');
    if (ctxMensual) {
        new Chart(ctxMensual, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($m) {
                    $fecha = DateTime::createFromFormat('Y-m', $m->mes);
                    return $fecha ? $fecha->format('M Y') : $m->mes;
                }, $comparativa_mensual)); ?>,
                datasets: [
                    {
                        label: 'Total Reportadas',
                        data: <?php echo json_encode(array_column($comparativa_mensual, 'total')); ?>,
                        backgroundColor: 'rgba(34, 113, 177, 0.8)',
                        borderColor: 'rgb(34, 113, 177)',
                        borderWidth: 2
                    },
                    {
                        label: 'Resueltas',
                        data: <?php echo json_encode(array_column($comparativa_mensual, 'resueltas')); ?>,
                        backgroundColor: 'rgba(0, 163, 42, 0.8)',
                        borderColor: 'rgb(0, 163, 42)',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

});
</script>

<style>
.flavor-kpi-card {
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.flavor-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease;
}
</style>
