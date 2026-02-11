<?php
/**
 * Vista Dashboard - Modulo Transparencia
 *
 * Panel de control con estadisticas de datos publicos y solicitudes
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_datos = $wpdb->prefix . 'flavor_transparencia_datos';
$tabla_solicitudes = $wpdb->prefix . 'flavor_transparencia_solicitudes';

// Estadisticas generales
$total_datos_publicados = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_datos WHERE estado = 'publicado'");
$total_solicitudes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes");
$solicitudes_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado IN ('recibida', 'en_tramite')");
$solicitudes_resueltas_mes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'resuelta' AND MONTH(fecha_resolucion) = MONTH(CURRENT_DATE())");

// Tiempo promedio de resolucion (en dias)
$tiempo_promedio_resolucion = $wpdb->get_var("
    SELECT AVG(DATEDIFF(fecha_resolucion, fecha_solicitud))
    FROM $tabla_solicitudes
    WHERE estado = 'resuelta' AND fecha_resolucion IS NOT NULL
");

// Estadisticas por estado de solicitudes
$estadisticas_estado_solicitudes = $wpdb->get_results("
    SELECT estado, COUNT(*) as total
    FROM $tabla_solicitudes
    GROUP BY estado
");

// Estadisticas por categoria de datos publicados
$estadisticas_categoria_datos = $wpdb->get_results("
    SELECT categoria, COUNT(*) as total
    FROM $tabla_datos
    WHERE estado = 'publicado'
    GROUP BY categoria
    ORDER BY total DESC
    LIMIT 8
");

// Tasa de resolucion
$total_tramitadas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado IN ('resuelta', 'denegada')");
$total_resueltas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'resuelta'");
$tasa_resolucion = $total_tramitadas > 0 ? round(($total_resueltas / $total_tramitadas) * 100, 1) : 0;

// Importe total presupuestos publicados
$importe_total_presupuestos = $wpdb->get_var("SELECT SUM(importe) FROM $tabla_datos WHERE categoria = 'presupuestos' AND estado = 'publicado'");

// Tendencia ultimos 7 dias
$tendencia_publicaciones_semana = $wpdb->get_results("
    SELECT DATE(fecha_publicacion) as fecha, COUNT(*) as total
    FROM $tabla_datos
    WHERE fecha_publicacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND estado = 'publicado'
    GROUP BY DATE(fecha_publicacion)
    ORDER BY fecha ASC
");

// Solicitudes recientes
$solicitudes_recientes = $wpdb->get_results("
    SELECT *
    FROM $tabla_solicitudes
    ORDER BY fecha_solicitud DESC
    LIMIT 5
");
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-visibility" style="color: #2271b1;"></span>
        <?php echo esc_html__('Dashboard de Transparencia', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Metricas principales -->
    <div class="flavor-metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Datos Publicados', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($total_datos_publicados); ?></h2>
                </div>
                <span class="dashicons dashicons-media-spreadsheet" style="font-size: 40px; color: #2271b1; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #f0b849; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Solicitudes Pendientes', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($solicitudes_pendientes); ?></h2>
                    <p style="margin: 5px 0 0 0; color: #646970; font-size: 12px;">
                        de <?php echo number_format($total_solicitudes); ?> totales
                    </p>
                </div>
                <span class="dashicons dashicons-clock" style="font-size: 40px; color: #f0b849; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Resueltas (Mes)', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($solicitudes_resueltas_mes); ?></h2>
                    <p style="margin: 5px 0 0 0; color: #646970; font-size: 12px;">
                        Tasa: <?php echo $tasa_resolucion; ?>%
                    </p>
                </div>
                <span class="dashicons dashicons-yes-alt" style="font-size: 40px; color: #00a32a; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Tiempo Promedio', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;">
                        <?php echo $tiempo_promedio_resolucion ? round($tiempo_promedio_resolucion) : '0'; ?> dias
                    </h2>
                </div>
                <span class="dashicons dashicons-calendar-alt" style="font-size: 40px; color: #2271b1; opacity: 0.3;"></span>
            </div>
        </div>

    </div>

    <!-- Graficos -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin: 20px 0;">

        <div class="flavor-chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0;">
                <span class="dashicons dashicons-chart-line"></span>
                <?php echo esc_html__('Publicaciones Ultimos 7 Dias', 'flavor-chat-ia'); ?>
            </h3>
            <canvas id="chart-tendencia-transparencia" style="max-height: 300px;"></canvas>
        </div>

        <div class="flavor-chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0;">
                <span class="dashicons dashicons-chart-pie"></span>
                <?php echo esc_html__('Solicitudes por Estado', 'flavor-chat-ia'); ?>
            </h3>
            <canvas id="chart-estado-solicitudes" style="max-height: 300px;"></canvas>
        </div>

    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">

        <div class="flavor-chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0;">
                <span class="dashicons dashicons-category"></span>
                <?php echo esc_html__('Datos por Categoria', 'flavor-chat-ia'); ?>
            </h3>
            <canvas id="chart-categorias-datos" style="max-height: 300px;"></canvas>
        </div>

        <div class="flavor-table-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0;">
                <span class="dashicons dashicons-list-view"></span>
                <?php echo esc_html__('Solicitudes Recientes', 'flavor-chat-ia'); ?>
            </h3>
            <?php if (!empty($solicitudes_recientes)): ?>
                <table class="wp-list-table widefat">
                    <tbody>
                        <?php foreach ($solicitudes_recientes as $solicitud): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo esc_html($solicitud->id); ?></strong><br>
                                <small><?php echo esc_html(wp_trim_words($solicitud->titulo, 8)); ?></small>
                            </td>
                            <td style="text-align: right;">
                                <?php
                                $colores_estado_solicitud = [
                                    'recibida' => '#f0b849',
                                    'en_tramite' => '#2271b1',
                                    'resuelta' => '#00a32a',
                                    'denegada' => '#d63638'
                                ];
                                $color_estado = $colores_estado_solicitud[$solicitud->estado] ?? '#646970';
                                ?>
                                <span style="background: <?php echo $color_estado; ?>; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $solicitud->estado))); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #646970; text-align: center; padding: 20px 0;"><?php echo esc_html__('No hay solicitudes recientes', 'flavor-chat-ia'); ?></p>
            <?php endif; ?>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Grafico de tendencia de publicaciones
    new Chart(document.getElementById('chart-tendencia-transparencia'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function($tendencia) { return date('d/m', strtotime($tendencia->fecha)); }, $tendencia_publicaciones_semana)); ?>,
            datasets: [{
                label: 'Publicaciones',
                data: <?php echo json_encode(array_column($tendencia_publicaciones_semana, 'total')); ?>,
                borderColor: 'rgb(34, 113, 177)',
                backgroundColor: 'rgba(34, 113, 177, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // Grafico de estado de solicitudes
    new Chart(document.getElementById('chart-estado-solicitudes'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map(function($estadistica) { return ucfirst(str_replace('_', ' ', $estadistica->estado)); }, $estadisticas_estado_solicitudes)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($estadisticas_estado_solicitudes, 'total')); ?>,
                backgroundColor: ['rgba(240, 184, 73, 0.8)', 'rgba(34, 113, 177, 0.8)', 'rgba(0, 163, 42, 0.8)', 'rgba(214, 54, 56, 0.8)']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Grafico de categorias de datos
    new Chart(document.getElementById('chart-categorias-datos'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(function($estadistica) { return ucfirst($estadistica->categoria); }, $estadisticas_categoria_datos)); ?>,
            datasets: [{
                label: 'Datos publicados',
                data: <?php echo json_encode(array_column($estadisticas_categoria_datos, 'total')); ?>,
                backgroundColor: 'rgba(34, 113, 177, 0.8)',
                borderColor: 'rgb(34, 113, 177)',
                borderWidth: 2
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

});
</script>

<style>
.flavor-metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease;
}
</style>
