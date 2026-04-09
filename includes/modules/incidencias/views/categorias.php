<?php
/**
 * Vista Categorías - Módulo Incidencias
 *
 * Gestión de categorías de incidencias urbanas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

// Obtener categorías predefinidas
$categorias_config = [
    'alumbrado' => [
        'nombre' => 'Alumbrado Público',
        'icono' => 'dashicons-lightbulb',
        'color' => '#f0b849'
    ],
    'limpieza' => [
        'nombre' => 'Limpieza y Residuos',
        'icono' => 'dashicons-trash',
        'color' => '#00a32a'
    ],
    'via_publica' => [
        'nombre' => 'Vía Pública',
        'icono' => 'dashicons-admin-site-alt3',
        'color' => '#2271b1'
    ],
    'mobiliario' => [
        'nombre' => 'Mobiliario Urbano',
        'icono' => 'dashicons-building',
        'color' => '#646970'
    ],
    'parques' => [
        'nombre' => 'Parques y Jardines',
        'icono' => 'dashicons-palmtree',
        'color' => '#00a32a'
    ],
    'ruido' => [
        'nombre' => 'Ruidos y Molestias',
        'icono' => 'dashicons-megaphone',
        'color' => '#d63638'
    ],
    'agua' => [
        'nombre' => 'Agua y Alcantarillado',
        'icono' => 'dashicons-flag',
        'color' => '#2271b1'
    ],
    'señalizacion' => [
        'nombre' => 'Señalización',
        'icono' => 'dashicons-info',
        'color' => '#ff8c00'
    ],
    'otros' => [
        'nombre' => 'Otros',
        'icono' => 'dashicons-editor-help',
        'color' => '#646970'
    ]
];

// Obtener estadísticas por categoría
$stats_categorias = $wpdb->get_results("
    SELECT
        categoria,
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
        SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) as resueltas,
        AVG(CASE WHEN estado = 'resuelta' AND fecha_resolucion IS NOT NULL
            THEN TIMESTAMPDIFF(HOUR, fecha_reporte, fecha_resolucion)
            ELSE NULL END) as tiempo_promedio_resolucion
    FROM $tabla_incidencias
    GROUP BY categoria
");

// Convertir a array asociativo
$stats_por_categoria = [];
foreach ($stats_categorias as $stat) {
    $stats_por_categoria[$stat->categoria] = $stat;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-category"></span>
        <?php echo esc_html__('Gestión de Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Grid de categorías -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-top: 20px;">

        <?php foreach ($categorias_config as $slug_categoria => $config_categoria): ?>
            <?php
            $stats = $stats_por_categoria[$slug_categoria] ?? null;
            $total = $stats ? $stats->total : 0;
            $pendientes = $stats ? $stats->pendientes : 0;
            $en_proceso = $stats ? $stats->en_proceso : 0;
            $resueltas = $stats ? $stats->resueltas : 0;
            $tiempo_promedio = $stats && $stats->tiempo_promedio_resolucion ? round($stats->tiempo_promedio_resolucion) : 0;

            $tasa_resolucion = $total > 0 ? round(($resueltas / $total) * 100) : 0;
            ?>

            <div class="postbox" style="border-left: 4px solid <?php echo $config_categoria['color']; ?>;">
                <div class="postbox-header">
                    <h2 style="display: flex; align-items: center; gap: 10px;">
                        <span class="dashicons <?php echo $config_categoria['icono']; ?>" style="color: <?php echo $config_categoria['color']; ?>;"></span>
                        <?php echo esc_html($config_categoria['nombre']); ?>
                    </h2>
                </div>
                <div class="inside">

                    <!-- Métrica principal -->
                    <div style="text-align: center; padding: 20px 0; border-bottom: 1px solid #dcdcde;">
                        <h3 style="margin: 0; font-size: 48px; color: <?php echo $config_categoria['color']; ?>;">
                            <?php echo number_format($total); ?>
                        </h3>
                        <p style="margin: 5px 0 0 0; color: #646970; text-transform: uppercase; font-size: 11px; letter-spacing: 1px;">
                            <?php echo esc_html__('Total de Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </div>

                    <!-- Desglose por estado -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding: 15px 0; border-bottom: 1px solid #dcdcde;">
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: 600; color: #f0b849;">
                                <?php echo $pendientes; ?>
                            </div>
                            <div style="font-size: 11px; color: #646970; text-transform: uppercase;"><?php echo esc_html__('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: 600; color: #2271b1;">
                                <?php echo $en_proceso; ?>
                            </div>
                            <div style="font-size: 11px; color: #646970; text-transform: uppercase;"><?php echo esc_html__('En Proceso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: 600; color: #00a32a;">
                                <?php echo $resueltas; ?>
                            </div>
                            <div style="font-size: 11px; color: #646970; text-transform: uppercase;"><?php echo esc_html__('Resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                        </div>
                    </div>

                    <!-- Métricas adicionales -->
                    <div style="padding: 15px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span style="color: #646970; font-size: 13px;"><?php echo esc_html__('Tasa de Resolución:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <strong style="font-size: 16px; color: <?php echo $config_categoria['color']; ?>;">
                                <?php echo $tasa_resolucion; ?>%
                            </strong>
                        </div>
                        <div style="background: #f0f0f1; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: <?php echo $config_categoria['color']; ?>; height: 100%; width: <?php echo $tasa_resolucion; ?>%; transition: width 0.3s ease;"></div>
                        </div>
                    </div>

                    <div style="padding: 10px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #646970; font-size: 13px;">
                                <span class="dashicons dashicons-clock" style="font-size: 14px; vertical-align: middle;"></span>
                                <?php echo esc_html__('Tiempo Promedio:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                            <strong style="font-size: 16px;">
                                <?php echo $tiempo_promedio; ?>h
                            </strong>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div style="padding-top: 15px; border-top: 1px solid #dcdcde;">
                        <a href="<?php echo admin_url('admin.php?page=incidencias-todas&categoria=' . $slug_categoria); ?>" class="button button-primary" style="width: 100%; text-align: center;">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php echo esc_html__('Ver Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>

                </div>
            </div>

        <?php endforeach; ?>

    </div>

    <!-- Resumen general -->
    <div class="postbox" style="margin-top: 30px;">
        <div class="postbox-header">
            <h2>
                <span class="dashicons dashicons-chart-pie"></span>
                <?php echo esc_html__('Distribución por Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
        </div>
        <div class="inside">
            <canvas id="chart-distribucion-categorias" style="max-height: 400px;"></canvas>
        </div>
    </div>

    <!-- Comparativa de rendimiento -->
    <div class="postbox" style="margin-top: 20px;">
        <div class="postbox-header">
            <h2>
                <span class="dashicons dashicons-chart-bar"></span>
                <?php echo esc_html__('Comparativa de Rendimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
        </div>
        <div class="inside">
            <canvas id="chart-rendimiento-categorias" style="max-height: 400px;"></canvas>
        </div>
    </div>

</div>

<!-- Scripts de Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Datos de categorías
    const categoriasData = <?php echo json_encode(array_map(function($slug, $config) use ($stats_por_categoria) {
        $stats = $stats_por_categoria[$slug] ?? null;
        return [
            'slug' => $slug,
            'nombre' => $config['nombre'],
            'color' => $config['color'],
            'total' => $stats ? $stats->total : 0,
            'pendientes' => $stats ? $stats->pendientes : 0,
            'en_proceso' => $stats ? $stats->en_proceso : 0,
            'resueltas' => $stats ? $stats->resueltas : 0,
            'tiempo_promedio' => $stats && $stats->tiempo_promedio_resolucion ? round($stats->tiempo_promedio_resolucion) : 0
        ];
    }, array_keys($categorias_config), $categorias_config)); ?>;

    // Gráfico de distribución
    const ctxDistribucion = document.getElementById('chart-distribucion-categorias');
    if (ctxDistribucion) {
        new Chart(ctxDistribucion, {
            type: 'doughnut',
            data: {
                labels: categoriasData.map(c => c.nombre),
                datasets: [{
                    data: categoriasData.map(c => c.total),
                    backgroundColor: categoriasData.map(c => c.color + 'CC'),
                    borderColor: categoriasData.map(c => c.color),
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Gráfico de rendimiento
    const ctxRendimiento = document.getElementById('chart-rendimiento-categorias');
    if (ctxRendimiento) {
        new Chart(ctxRendimiento, {
            type: 'bar',
            data: {
                labels: categoriasData.map(c => c.nombre),
                datasets: [
                    {
                        label: 'Pendientes',
                        data: categoriasData.map(c => c.pendientes),
                        backgroundColor: 'rgba(240, 184, 73, 0.8)',
                        borderColor: 'rgb(240, 184, 73)',
                        borderWidth: 2
                    },
                    {
                        label: 'En Proceso',
                        data: categoriasData.map(c => c.en_proceso),
                        backgroundColor: 'rgba(34, 113, 177, 0.8)',
                        borderColor: 'rgb(34, 113, 177)',
                        borderWidth: 2
                    },
                    {
                        label: 'Resueltas',
                        data: categoriasData.map(c => c.resueltas),
                        backgroundColor: 'rgba(0, 163, 42, 0.8)',
                        borderColor: 'rgb(0, 163, 42)',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    x: {
                        stacked: false
                    },
                    y: {
                        stacked: false,
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    }

});
</script>

<style>
.postbox {
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

.postbox:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}
</style>
