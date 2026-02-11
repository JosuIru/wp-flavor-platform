<?php
/**
 * Vista Dashboard - Módulo Presupuestos Participativos
 *
 * Panel de control con estadísticas de presupuestos participativos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_proyectos = $wpdb->prefix . 'flavor_presupuestos_proyectos';
$tabla_votos_pp = $wpdb->prefix . 'flavor_presupuestos_votos';

// Presupuesto total y asignado
$presupuesto_total = 500000; // Ejemplo: 500k euros
$presupuesto_asignado = $wpdb->get_var("SELECT SUM(presupuesto_solicitado) FROM $tabla_proyectos WHERE estado = 'aprobado'") ?? 0;
$presupuesto_disponible = $presupuesto_total - $presupuesto_asignado;
$porcentaje_asignado = $presupuesto_total > 0 ? round(($presupuesto_asignado / $presupuesto_total) * 100, 1) : 0;

// Estadísticas de proyectos
$total_proyectos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos");
$proyectos_votacion = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos WHERE estado = 'en_votacion'");
$proyectos_aprobados = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos WHERE estado = 'aprobado'");

// Participación
$total_votos_pp = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_votos_pp");
$votantes_unicos_pp = $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_votos_pp");

// Proyectos por estado
$stats_proyectos_estado = $wpdb->get_results("
    SELECT estado, COUNT(*) as total
    FROM $tabla_proyectos
    GROUP BY estado
");

// Proyectos más votados
$proyectos_mas_votados = $wpdb->get_results("
    SELECT p.*, COUNT(v.id) as total_votos
    FROM $tabla_proyectos p
    LEFT JOIN $tabla_votos_pp v ON p.id = v.proyecto_id
    WHERE p.estado = 'en_votacion'
    GROUP BY p.id
    ORDER BY total_votos DESC
    LIMIT 5
");

// Distribución presupuestaria por categoría
$distribucion_categoria = $wpdb->get_results("
    SELECT categoria, SUM(presupuesto_solicitado) as total_presupuesto
    FROM $tabla_proyectos
    WHERE estado IN ('en_votacion', 'aprobado')
    GROUP BY categoria
    ORDER BY total_presupuesto DESC
    LIMIT 6
");

// Tendencia de votación últimos 14 días
$tendencia_votos = $wpdb->get_results("
    SELECT DATE(fecha_voto) as fecha, COUNT(*) as total
    FROM $tabla_votos_pp
    WHERE fecha_voto >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(fecha_voto)
    ORDER BY fecha ASC
");
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-money-alt" style="color: #00a32a;"></span>
        <?php echo esc_html__('Dashboard de Presupuestos Participativos', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Métricas principales -->
    <div class="flavor-metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Presupuesto Total', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($presupuesto_total, 0, ',', '.'); ?> €</h2>
                    <p style="margin: 5px 0 0 0; color: #646970; font-size: 12px;">
                        <?php echo esc_html__('para el ejercicio actual', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <span class="dashicons dashicons-money-alt" style="font-size: 40px; color: #00a32a; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Presupuesto Asignado', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($presupuesto_asignado, 0, ',', '.'); ?> €</h2>
                    <p style="margin: 5px 0 0 0; color: #646970; font-size: 12px;">
                        <?php echo $porcentaje_asignado; ?>% del total
                    </p>
                </div>
                <span class="dashicons dashicons-chart-pie" style="font-size: 40px; color: #2271b1; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #f0b849; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Proyectos en Votación', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($proyectos_votacion); ?></h2>
                    <p style="margin: 5px 0 0 0; color: #646970; font-size: 12px;">
                        de <?php echo $total_proyectos; ?> totales
                    </p>
                </div>
                <span class="dashicons dashicons-portfolio" style="font-size: 40px; color: #f0b849; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #d63638; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Participación', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($votantes_unicos_pp); ?></h2>
                    <p style="margin: 5px 0 0 0; color: #646970; font-size: 12px;">
                        <?php echo esc_html__('ciudadanos han votado', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <span class="dashicons dashicons-groups" style="font-size: 40px; color: #d63638; opacity: 0.3;"></span>
            </div>
        </div>

    </div>

    <!-- Indicador de presupuesto -->
    <div class="postbox" style="margin: 20px 0;">
        <div class="postbox-header">
            <h2><?php echo esc_html__('Estado del Presupuesto', 'flavor-chat-ia'); ?></h2>
        </div>
        <div class="inside">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 30px; padding: 20px;">
                <div style="text-align: center;">
                    <div style="font-size: 14px; color: #646970; margin-bottom: 10px; text-transform: uppercase;"><?php echo esc_html__('Total Disponible', 'flavor-chat-ia'); ?></div>
                    <div style="font-size: 36px; font-weight: 700; color: #00a32a;">
                        <?php echo number_format($presupuesto_total, 0, ',', '.'); ?> €
                    </div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 14px; color: #646970; margin-bottom: 10px; text-transform: uppercase;"><?php echo esc_html__('Asignado', 'flavor-chat-ia'); ?></div>
                    <div style="font-size: 36px; font-weight: 700; color: #2271b1;">
                        <?php echo number_format($presupuesto_asignado, 0, ',', '.'); ?> €
                    </div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 14px; color: #646970; margin-bottom: 10px; text-transform: uppercase;"><?php echo esc_html__('Disponible', 'flavor-chat-ia'); ?></div>
                    <div style="font-size: 36px; font-weight: 700; color: #f0b849;">
                        <?php echo number_format($presupuesto_disponible, 0, ',', '.'); ?> €
                    </div>
                </div>
            </div>
            <div style="background: #f0f0f1; height: 30px; border-radius: 15px; overflow: hidden; margin: 20px;">
                <div style="background: linear-gradient(90deg, #00a32a 0%, #2271b1 100%); height: 100%; width: <?php echo $porcentaje_asignado; ?>%; transition: width 0.5s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px;">
                    <?php echo $porcentaje_asignado; ?>% Asignado
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">

        <div class="flavor-chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0;">
                <span class="dashicons dashicons-chart-line"></span>
                <?php echo esc_html__('Tendencia de Votación (14 días)', 'flavor-chat-ia'); ?>
            </h3>
            <canvas id="chart-tendencia-votos" style="max-height: 300px;"></canvas>
        </div>

        <div class="flavor-chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0;">
                <span class="dashicons dashicons-chart-pie"></span>
                <?php echo esc_html__('Proyectos por Estado', 'flavor-chat-ia'); ?>
            </h3>
            <canvas id="chart-proyectos-estado" style="max-height: 300px;"></canvas>
        </div>

    </div>

    <!-- Distribución y proyectos más votados -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">

        <div class="postbox">
            <div class="postbox-header">
                <h2><?php echo esc_html__('Distribución Presupuestaria por Categoría', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="inside">
                <canvas id="chart-distribucion-categoria" style="max-height: 300px;"></canvas>
            </div>
        </div>

        <div class="postbox">
            <div class="postbox-header">
                <h2><?php echo esc_html__('Proyectos Más Votados', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="inside">
                <?php if (!empty($proyectos_mas_votados)): ?>
                    <table class="wp-list-table widefat">
                        <tbody>
                            <?php foreach ($proyectos_mas_votados as $proyecto): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($proyecto->titulo); ?></strong><br>
                                    <small style="color: #00a32a; font-weight: 600;">
                                        <?php echo number_format($proyecto->presupuesto_solicitado, 0, ',', '.'); ?> €
                                    </small>
                                </td>
                                <td style="text-align: right;">
                                    <div style="font-size: 18px; font-weight: 600; color: #2271b1;">
                                        <?php echo number_format($proyecto->total_votos); ?>
                                    </div>
                                    <small style="color: #646970;"><?php echo esc_html__('votos', 'flavor-chat-ia'); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #646970; text-align: center; padding: 20px 0;"><?php echo esc_html__('No hay proyectos en votación', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Gráfico de tendencia de votos
    new Chart(document.getElementById('chart-tendencia-votos'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function($t) { return date('d/m', strtotime($t->fecha)); }, $tendencia_votos)); ?>,
            datasets: [{
                label: 'Votos',
                data: <?php echo json_encode(array_column($tendencia_votos, 'total')); ?>,
                borderColor: 'rgb(0, 163, 42)',
                backgroundColor: 'rgba(0, 163, 42, 0.1)',
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

    // Gráfico de proyectos por estado
    new Chart(document.getElementById('chart-proyectos-estado'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($stats_proyectos_estado, 'estado')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($stats_proyectos_estado, 'total')); ?>,
                backgroundColor: [
                    'rgba(240, 184, 73, 0.8)',
                    'rgba(34, 113, 177, 0.8)',
                    'rgba(0, 163, 42, 0.8)',
                    'rgba(214, 54, 56, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Gráfico de distribución por categoría
    new Chart(document.getElementById('chart-distribucion-categoria'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($distribucion_categoria, 'categoria')); ?>,
            datasets: [{
                label: 'Presupuesto (€)',
                data: <?php echo json_encode(array_column($distribucion_categoria, 'total_presupuesto')); ?>,
                backgroundColor: 'rgba(0, 163, 42, 0.8)',
                borderColor: 'rgb(0, 163, 42)',
                borderWidth: 2
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return new Intl.NumberFormat('es-ES', {
                                style: 'currency',
                                currency: 'EUR',
                                minimumFractionDigits: 0
                            }).format(context.parsed.x);
                        }
                    }
                }
            },
            scales: {
                x: { beginAtZero: true }
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
