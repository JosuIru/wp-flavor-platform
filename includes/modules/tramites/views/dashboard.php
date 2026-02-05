<?php
/**
 * Vista Dashboard - Módulo Trámites
 *
 * Panel de control con estadísticas de solicitudes y trámites
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_tramites = $wpdb->prefix . 'flavor_tramites';
$tabla_solicitudes = $wpdb->prefix . 'flavor_tramites_solicitudes';

// Estadísticas generales
$total_solicitudes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes");
$solicitudes_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'pendiente'");
$solicitudes_aprobadas_mes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'aprobada' AND MONTH(fecha_resolucion) = MONTH(CURRENT_DATE())");

// Tiempo promedio de resolución (en días)
$tiempo_promedio = $wpdb->get_var("
    SELECT AVG(DATEDIFF(fecha_resolucion, fecha_solicitud))
    FROM $tabla_solicitudes
    WHERE estado = 'aprobada' AND fecha_resolucion IS NOT NULL
");

// Estadísticas por estado
$stats_estado = $wpdb->get_results("
    SELECT estado, COUNT(*) as total
    FROM $tabla_solicitudes
    GROUP BY estado
");

// Estadísticas por tipo de trámite
$stats_tipo = $wpdb->get_results("
    SELECT tipo_tramite, COUNT(*) as total
    FROM $tabla_solicitudes
    WHERE estado IN ('pendiente', 'en_revision')
    GROUP BY tipo_tramite
    ORDER BY total DESC
    LIMIT 8
");

// Tasa de aprobación
$total_procesadas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado IN ('aprobada', 'rechazada')");
$total_aprobadas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'aprobada'");
$tasa_aprobacion = $total_procesadas > 0 ? round(($total_aprobadas / $total_procesadas) * 100, 1) : 0;

// Solicitudes urgentes sin procesar
$solicitudes_urgentes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE prioridad = 'alta' AND estado = 'pendiente'");

// Tendencia últimos 7 días
$tendencia_semana = $wpdb->get_results("
    SELECT DATE(fecha_solicitud) as fecha, COUNT(*) as total
    FROM $tabla_solicitudes
    WHERE fecha_solicitud >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(fecha_solicitud)
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
        <span class="dashicons dashicons-text-page" style="color: #2271b1;"></span>
        Dashboard de Trámites
    </h1>

    <hr class="wp-header-end">

    <!-- Métricas principales -->
    <div class="flavor-metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;">Total Solicitudes</p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($total_solicitudes); ?></h2>
                </div>
                <span class="dashicons dashicons-portfolio" style="font-size: 40px; color: #2271b1; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #f0b849; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;">Pendientes</p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($solicitudes_pendientes); ?></h2>
                    <p style="margin: 5px 0 0 0; color: #d63638; font-size: 12px; font-weight: 600;">
                        <?php echo $solicitudes_urgentes; ?> urgentes
                    </p>
                </div>
                <span class="dashicons dashicons-clock" style="font-size: 40px; color: #f0b849; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;">Aprobadas (Mes)</p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($solicitudes_aprobadas_mes); ?></h2>
                    <p style="margin: 5px 0 0 0; color: #646970; font-size: 12px;">
                        Tasa: <?php echo $tasa_aprobacion; ?>%
                    </p>
                </div>
                <span class="dashicons dashicons-yes-alt" style="font-size: 40px; color: #00a32a; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;">Tiempo Promedio</p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;">
                        <?php echo $tiempo_promedio ? round($tiempo_promedio) : '0'; ?> días
                    </h2>
                </div>
                <span class="dashicons dashicons-calendar-alt" style="font-size: 40px; color: #2271b1; opacity: 0.3;"></span>
            </div>
        </div>

    </div>

    <!-- Gráficos -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin: 20px 0;">

        <div class="flavor-chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0;">
                <span class="dashicons dashicons-chart-line"></span>
                Tendencia Últimos 7 Días
            </h3>
            <canvas id="chart-tendencia" style="max-height: 300px;"></canvas>
        </div>

        <div class="flavor-chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0;">
                <span class="dashicons dashicons-chart-pie"></span>
                Por Estado
            </h3>
            <canvas id="chart-estado" style="max-height: 300px;"></canvas>
        </div>

    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">

        <div class="flavor-chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0;">
                <span class="dashicons dashicons-category"></span>
                Trámites Más Solicitados
            </h3>
            <canvas id="chart-tipos" style="max-height: 300px;"></canvas>
        </div>

        <div class="flavor-table-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0;">
                <span class="dashicons dashicons-list-view"></span>
                Solicitudes Recientes
            </h3>
            <?php if (!empty($solicitudes_recientes)): ?>
                <table class="wp-list-table widefat">
                    <tbody>
                        <?php foreach ($solicitudes_recientes as $solicitud): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($solicitud->numero_solicitud); ?></strong><br>
                                <small><?php echo esc_html($solicitud->tipo_tramite); ?></small>
                            </td>
                            <td style="text-align: right;">
                                <?php
                                $estado_colores = [
                                    'pendiente' => '#f0b849',
                                    'en_revision' => '#2271b1',
                                    'aprobada' => '#00a32a',
                                    'rechazada' => '#d63638'
                                ];
                                $color = $estado_colores[$solicitud->estado] ?? '#646970';
                                ?>
                                <span style="background: <?php echo $color; ?>; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                    <?php echo esc_html(ucfirst($solicitud->estado)); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #646970; text-align: center; padding: 20px 0;">No hay solicitudes recientes</p>
            <?php endif; ?>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Gráfico de tendencia
    new Chart(document.getElementById('chart-tendencia'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function($t) { return date('d/m', strtotime($t->fecha)); }, $tendencia_semana)); ?>,
            datasets: [{
                label: 'Solicitudes',
                data: <?php echo json_encode(array_column($tendencia_semana, 'total')); ?>,
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

    // Gráfico de estado
    new Chart(document.getElementById('chart-estado'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($stats_estado, 'estado')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($stats_estado, 'total')); ?>,
                backgroundColor: ['rgba(240, 184, 73, 0.8)', 'rgba(34, 113, 177, 0.8)', 'rgba(0, 163, 42, 0.8)', 'rgba(214, 54, 56, 0.8)']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Gráfico de tipos
    new Chart(document.getElementById('chart-tipos'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($stats_tipo, 'tipo_tramite')); ?>,
            datasets: [{
                label: 'Solicitudes',
                data: <?php echo json_encode(array_column($stats_tipo, 'total')); ?>,
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
