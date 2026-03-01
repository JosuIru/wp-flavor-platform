<?php
/**
 * Vista Dashboard - Módulo Incidencias
 *
 * Panel de control con estadísticas y métricas de incidencias urbanas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener estadísticas
global $wpdb;
$tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

$total_incidencias = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias");
$incidencias_abiertas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado IN ('pendiente', 'en_proceso')");
$incidencias_resueltas_mes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado = 'resuelta' AND MONTH(fecha_resolucion) = MONTH(CURRENT_DATE()) AND YEAR(fecha_resolucion) = YEAR(CURRENT_DATE())");

// Estadísticas por estado
$stats_estado = $wpdb->get_results("
    SELECT estado, COUNT(*) as total
    FROM $tabla_incidencias
    GROUP BY estado
");

// Estadísticas por categoría
$stats_categoria = $wpdb->get_results("
    SELECT categoria, COUNT(*) as total
    FROM $tabla_incidencias
    WHERE estado IN ('pendiente', 'en_proceso')
    GROUP BY categoria
    ORDER BY total DESC
    LIMIT 10
");

// Estadísticas por prioridad
$stats_prioridad = $wpdb->get_results("
    SELECT prioridad, COUNT(*) as total
    FROM $tabla_incidencias
    WHERE estado IN ('pendiente', 'en_proceso')
    GROUP BY prioridad
");

// Tiempo promedio de resolución (en horas)
$tiempo_promedio_resolucion = $wpdb->get_var("
    SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_reporte, fecha_resolucion))
    FROM $tabla_incidencias
    WHERE estado = 'resuelta'
    AND fecha_resolucion IS NOT NULL
");

// Incidencias recientes sin asignar
$incidencias_sin_asignar = $wpdb->get_var("
    SELECT COUNT(*)
    FROM $tabla_incidencias
    WHERE estado = 'pendiente'
    AND asignado_a IS NULL
");

// Incidencias más votadas
$incidencias_votadas = $wpdb->get_results("
    SELECT id, numero_incidencia, titulo, categoria, votos_ciudadanos
    FROM $tabla_incidencias
    WHERE estado IN ('pendiente', 'en_proceso')
    ORDER BY votos_ciudadanos DESC
    LIMIT 5
");

// Tasa de resolución últimos 7 días
$resueltas_semana = $wpdb->get_results("
    SELECT DATE(fecha_resolucion) as fecha, COUNT(*) as total
    FROM $tabla_incidencias
    WHERE fecha_resolucion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(fecha_resolucion)
    ORDER BY fecha ASC
");
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-warning" style="color: #d63638;"></span>
        <?php echo esc_html__('Dashboard de Incidencias Urbanas', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Accesos Rápidos -->
    <div class="incidencias-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=incidencias-listado'); ?>" class="incidencias-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-warning" style="font-size: 24px; color: #d63638;"></span>
            <span><?php echo esc_html__('Incidencias', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=incidencias-mapa'); ?>" class="incidencias-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-location-alt" style="font-size: 24px; color: #2271b1;"></span>
            <span><?php echo esc_html__('Mapa', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=incidencias-config'); ?>" class="incidencias-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px; color: #646970;"></span>
            <span><?php echo esc_html__('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Métricas principales -->
    <div class="flavor-metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 20px 0;">

        <!-- Total Incidencias -->
        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Total Incidencias', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($total_incidencias); ?></h2>
                </div>
                <span class="dashicons dashicons-analytics" style="font-size: 40px; color: #2271b1; opacity: 0.3;"></span>
            </div>
        </div>

        <!-- Abiertas -->
        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #d63638; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Abiertas', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($incidencias_abiertas); ?></h2>
                    <p style="margin: 5px 0 0 0; color: #646970; font-size: 12px;">
                        <?php echo $incidencias_sin_asignar; ?> sin asignar
                    </p>
                </div>
                <span class="dashicons dashicons-warning" style="font-size: 40px; color: #d63638; opacity: 0.3;"></span>
            </div>
        </div>

        <!-- Resueltas este mes -->
        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Resueltas este mes', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;"><?php echo number_format($incidencias_resueltas_mes); ?></h2>
                </div>
                <span class="dashicons dashicons-yes-alt" style="font-size: 40px; color: #00a32a; opacity: 0.3;"></span>
            </div>
        </div>

        <!-- Tiempo promedio resolución -->
        <div class="flavor-metric-card" style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #f0b849; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html__('Tiempo Promedio', 'flavor-chat-ia'); ?></p>
                    <h2 style="margin: 10px 0 0 0; font-size: 32px; font-weight: 600;">
                        <?php echo $tiempo_promedio_resolucion ? round($tiempo_promedio_resolucion) : '0'; ?>h
                    </h2>
                    <p style="margin: 5px 0 0 0; color: #646970; font-size: 12px;"><?php echo esc_html__('de resolución', 'flavor-chat-ia'); ?></p>
                </div>
                <span class="dashicons dashicons-clock" style="font-size: 40px; color: #f0b849; opacity: 0.3;"></span>
            </div>
        </div>

    </div>

    <!-- Gráficos y tablas -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin: 20px 0;">

        <!-- Distribución por Estado -->
        <div class="flavor-chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php echo esc_html__('Distribución por Estado', 'flavor-chat-ia'); ?>
            </h3>
            <canvas id="chart-estado" style="max-height: 300px;"></canvas>
        </div>

        <!-- Distribución por Prioridad -->
        <div class="flavor-chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-chart-pie"></span>
                <?php echo esc_html__('Por Prioridad', 'flavor-chat-ia'); ?>
            </h3>
            <canvas id="chart-prioridad" style="max-height: 300px;"></canvas>
        </div>

    </div>

    <!-- Categorías más reportadas e Incidencias más votadas -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">

        <!-- Categorías -->
        <div class="flavor-table-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-category"></span>
                <?php echo esc_html__('Categorías Más Reportadas', 'flavor-chat-ia'); ?>
            </h3>
            <canvas id="chart-categorias" style="max-height: 300px;"></canvas>
        </div>

        <!-- Más votadas -->
        <div class="flavor-table-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-thumbs-up"></span>
                <?php echo esc_html__('Incidencias Más Votadas', 'flavor-chat-ia'); ?>
            </h3>
            <?php if (!empty($incidencias_votadas)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Número', 'flavor-chat-ia'); ?></th>
                            <th><?php echo esc_html__('Título', 'flavor-chat-ia'); ?></th>
                            <th><?php echo esc_html__('Categoría', 'flavor-chat-ia'); ?></th>
                            <th><?php echo esc_html__('Votos', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incidencias_votadas as $incidencia_votada): ?>
                        <tr>
                            <td><strong><?php echo esc_html($incidencia_votada->numero_incidencia); ?></strong></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=flavor-incidencias-tickets&id=' . $incidencia_votada->id); ?>">
                                    <?php echo esc_html($incidencia_votada->titulo); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html(ucfirst($incidencia_votada->categoria)); ?></td>
                            <td>
                                <span class="dashicons dashicons-thumbs-up" style="color: #2271b1;"></span>
                                <?php echo $incidencia_votada->votos_ciudadanos; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #646970; text-align: center; padding: 40px 0;"><?php echo esc_html__('No hay incidencias con votos', 'flavor-chat-ia'); ?></p>
            <?php endif; ?>
        </div>

    </div>

    <!-- Tendencia de resolución (últimos 7 días) -->
    <div class="flavor-chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin: 20px 0;">
        <h3 style="margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-chart-line"></span>
            <?php echo esc_html__('Tendencia de Resolución (Últimos 7 Días)', 'flavor-chat-ia'); ?>
        </h3>
        <canvas id="chart-tendencia" style="max-height: 250px;"></canvas>
    </div>

</div>

<!-- Scripts de Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Gráfico de Estado
    const ctxEstado = document.getElementById('chart-estado');
    if (ctxEstado) {
        new Chart(ctxEstado, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($stats_estado, 'estado')); ?>,
                datasets: [{
                    label: 'Incidencias',
                    data: <?php echo json_encode(array_column($stats_estado, 'total')); ?>,
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.8)',  // pendiente
                        'rgba(33, 150, 243, 0.8)', // en_proceso
                        'rgba(76, 175, 80, 0.8)',  // resuelta
                        'rgba(158, 158, 158, 0.8)', // cerrada
                        'rgba(244, 67, 54, 0.8)'   // rechazada
                    ],
                    borderColor: [
                        'rgb(255, 193, 7)',
                        'rgb(33, 150, 243)',
                        'rgb(76, 175, 80)',
                        'rgb(158, 158, 158)',
                        'rgb(244, 67, 54)'
                    ],
                    borderWidth: 2
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
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Gráfico de Prioridad
    const ctxPrioridad = document.getElementById('chart-prioridad');
    if (ctxPrioridad) {
        new Chart(ctxPrioridad, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($stats_prioridad, 'prioridad')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($stats_prioridad, 'total')); ?>,
                    backgroundColor: [
                        'rgba(76, 175, 80, 0.8)',  // baja
                        'rgba(255, 193, 7, 0.8)',  // media
                        'rgba(255, 152, 0, 0.8)',  // alta
                        'rgba(244, 67, 54, 0.8)'   // urgente
                    ],
                    borderColor: [
                        'rgb(76, 175, 80)',
                        'rgb(255, 193, 7)',
                        'rgb(255, 152, 0)',
                        'rgb(244, 67, 54)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Gráfico de Categorías
    const ctxCategorias = document.getElementById('chart-categorias');
    if (ctxCategorias) {
        new Chart(ctxCategorias, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($stats_categoria, 'categoria')); ?>,
                datasets: [{
                    label: 'Incidencias',
                    data: <?php echo json_encode(array_column($stats_categoria, 'total')); ?>,
                    backgroundColor: 'rgba(33, 113, 177, 0.8)',
                    borderColor: 'rgb(33, 113, 177)',
                    borderWidth: 2
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Gráfico de Tendencia
    const ctxTendencia = document.getElementById('chart-tendencia');
    if (ctxTendencia) {
        new Chart(ctxTendencia, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($r) { return date('d/m', strtotime($r->fecha)); }, $resueltas_semana)); ?>,
                datasets: [{
                    label: 'Incidencias Resueltas',
                    data: <?php echo json_encode(array_column($resueltas_semana, 'total')); ?>,
                    borderColor: 'rgb(76, 175, 80)',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
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
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

});
</script>

<style>
.flavor-metrics-grid,
.flavor-chart-container,
.flavor-table-container {
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

.flavor-metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease;
}
</style>
