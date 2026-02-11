<?php
/**
 * Vista Dashboard - Módulo Participación
 *
 * Panel de control con estadísticas de participación ciudadana
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
$tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';
$tabla_votos = $wpdb->prefix . 'flavor_votos';

// Estadísticas generales
$total_propuestas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_propuestas");
$propuestas_activas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_propuestas WHERE estado = 'activa'");
$total_votaciones = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_votaciones");
$votaciones_activas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_votaciones WHERE estado = 'activa' AND fecha_inicio <= NOW() AND fecha_fin >= NOW()");

// Participación total
$total_votos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_votos");
$votantes_unicos = $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_votos");

// Tasa de participación (estimado)
$total_usuarios = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} WHERE user_status = 0");
$tasa_participacion = $total_usuarios > 0 ? round(($votantes_unicos / $total_usuarios) * 100, 1) : 0;

// Propuestas por estado
$stats_propuestas_estado = $wpdb->get_results("
    SELECT estado, COUNT(*) as total
    FROM $tabla_propuestas
    GROUP BY estado
");

// Votaciones recientes
$votaciones_recientes = $wpdb->get_results("
    SELECT v.*, COUNT(vo.id) as total_votos
    FROM $tabla_votaciones v
    LEFT JOIN $tabla_votos vo ON v.id = vo.votacion_id
    WHERE v.estado = 'activa'
    GROUP BY v.id
    ORDER BY v.fecha_inicio DESC
    LIMIT 5
");

// Propuestas más votadas
$propuestas_populares = $wpdb->get_results("
    SELECT *
    FROM $tabla_propuestas
    WHERE estado IN ('activa', 'en_revision')
    ORDER BY votos_favor DESC
    LIMIT 5
");

// Tendencia de participación (últimos 30 días)
$tendencia_participacion = $wpdb->get_results("
    SELECT DATE(fecha_voto) as fecha, COUNT(*) as total_votos
    FROM $tabla_votos
    WHERE fecha_voto >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(fecha_voto)
    ORDER BY fecha ASC
");
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups" style="color: #2271b1;"></span>
        <?php echo esc_html__('Dashboard de Participación Ciudadana', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Métricas principales -->
    <div class="flavor-metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Propuestas Activas', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($propuestas_activas); ?></h2>
                    <p style="margin: 5px 0 0 0; color: #646970; font-size: 12px;">
                        de <?php echo $total_propuestas; ?> totales
                    </p>
                </div>
                <span class="dashicons dashicons-lightbulb" style="font-size: 40px; color: #2271b1; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Votaciones Activas', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($votaciones_activas); ?></h2>
                    <p style="margin: 5px 0 0 0; color: #646970; font-size: 12px;">
                        <?php echo esc_html__('en curso ahora', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <span class="dashicons dashicons-thumbs-up" style="font-size: 40px; color: #00a32a; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #f0b849; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Participación Total', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($total_votos); ?></h2>
                    <p style="margin: 5px 0 0 0; color: #646970; font-size: 12px;">
                        <?php echo esc_html__('votos emitidos', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <span class="dashicons dashicons-chart-area" style="font-size: 40px; color: #f0b849; opacity: 0.3;"></span>
            </div>
        </div>

        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #d63638; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Tasa Participación', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo $tasa_participacion; ?>%</h2>
                    <p style="margin: 5px 0 0 0; color: #646970; font-size: 12px;">
                        <?php echo number_format($votantes_unicos); ?> ciudadanos
                    </p>
                </div>
                <span class="dashicons dashicons-admin-users" style="font-size: 40px; color: #d63638; opacity: 0.3;"></span>
            </div>
        </div>

    </div>

    <!-- Gráficos -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin: 20px 0;">

        <div class="flavor-chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0;">
                <span class="dashicons dashicons-chart-line"></span>
                <?php echo esc_html__('Tendencia de Participación (30 días)', 'flavor-chat-ia'); ?>
            </h3>
            <canvas id="chart-tendencia" style="max-height: 300px;"></canvas>
        </div>

        <div class="flavor-chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0;">
                <span class="dashicons dashicons-chart-pie"></span>
                <?php echo esc_html__('Propuestas por Estado', 'flavor-chat-ia'); ?>
            </h3>
            <canvas id="chart-propuestas-estado" style="max-height: 300px;"></canvas>
        </div>

    </div>

    <!-- Listas -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">

        <div class="postbox">
            <div class="postbox-header">
                <h2><?php echo esc_html__('Votaciones Activas', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="inside">
                <?php if (!empty($votaciones_recientes)): ?>
                    <table class="wp-list-table widefat">
                        <tbody>
                            <?php foreach ($votaciones_recientes as $votacion): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($votacion->titulo); ?></strong><br>
                                    <small style="color: #646970;">
                                        Finaliza: <?php echo date('d/m/Y', strtotime($votacion->fecha_fin)); ?>
                                    </small>
                                </td>
                                <td style="text-align: right;">
                                    <div style="font-size: 18px; font-weight: 600; color: #2271b1;">
                                        <?php echo number_format($votacion->total_votos); ?>
                                    </div>
                                    <small style="color: #646970;"><?php echo esc_html__('votos', 'flavor-chat-ia'); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #646970; text-align: center; padding: 20px 0;"><?php echo esc_html__('No hay votaciones activas', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="postbox">
            <div class="postbox-header">
                <h2><?php echo esc_html__('Propuestas Más Populares', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="inside">
                <?php if (!empty($propuestas_populares)): ?>
                    <table class="wp-list-table widefat">
                        <tbody>
                            <?php foreach ($propuestas_populares as $propuesta): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($propuesta->titulo); ?></strong><br>
                                    <small style="color: #646970;">
                                        <?php echo esc_html($propuesta->categoria); ?>
                                    </small>
                                </td>
                                <td style="text-align: right;">
                                    <div style="color: #00a32a;">
                                        <span class="dashicons dashicons-thumbs-up"></span>
                                        <?php echo number_format($propuesta->votos_favor); ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #646970; text-align: center; padding: 20px 0;"><?php echo esc_html__('No hay propuestas disponibles', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>
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
            labels: <?php echo json_encode(array_map(function($t) { return date('d/m', strtotime($t->fecha)); }, $tendencia_participacion)); ?>,
            datasets: [{
                label: 'Votos',
                data: <?php echo json_encode(array_column($tendencia_participacion, 'total_votos')); ?>,
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

    // Gráfico de propuestas por estado
    new Chart(document.getElementById('chart-propuestas-estado'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($stats_propuestas_estado, 'estado')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($stats_propuestas_estado, 'total')); ?>,
                backgroundColor: [
                    'rgba(34, 113, 177, 0.8)',
                    'rgba(0, 163, 42, 0.8)',
                    'rgba(240, 184, 73, 0.8)',
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

});
</script>

<style>
.flavor-metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease;
}
</style>
