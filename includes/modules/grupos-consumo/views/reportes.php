<?php
/**
 * Vista Admin: Reportes y Estadísticas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Período de análisis
$periodo = sanitize_text_field($_GET['periodo'] ?? 'mes');
$fecha_inicio = '';
$fecha_fin = current_time('Y-m-d');

switch ($periodo) {
    case 'semana':
        $fecha_inicio = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'mes':
        $fecha_inicio = date('Y-m-01');
        break;
    case 'trimestre':
        $fecha_inicio = date('Y-m-d', strtotime('-3 months'));
        break;
    case 'anio':
        $fecha_inicio = date('Y-01-01');
        break;
    case 'custom':
        $fecha_inicio = sanitize_text_field($_GET['desde'] ?? date('Y-m-01'));
        $fecha_fin = sanitize_text_field($_GET['hasta'] ?? current_time('Y-m-d'));
        break;
}

// Estadísticas generales
$stats_pedidos = $wpdb->get_row($wpdb->prepare(
    "SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as importe
     FROM {$wpdb->prefix}flavor_gc_pedidos
     WHERE fecha_pedido BETWEEN %s AND %s",
    $fecha_inicio . ' 00:00:00',
    $fecha_fin . ' 23:59:59'
));

// Pedidos por estado
$pedidos_por_estado = $wpdb->get_results($wpdb->prepare(
    "SELECT estado, COUNT(*) as total, COALESCE(SUM(total), 0) as importe
     FROM {$wpdb->prefix}flavor_gc_pedidos
     WHERE fecha_pedido BETWEEN %s AND %s
     GROUP BY estado",
    $fecha_inicio . ' 00:00:00',
    $fecha_fin . ' 23:59:59'
));

// Evolución de pedidos (últimos 12 períodos)
$evolucion = $wpdb->get_results($wpdb->prepare(
    "SELECT DATE_FORMAT(fecha_pedido, %s) as periodo,
            COUNT(*) as pedidos,
            COALESCE(SUM(total), 0) as importe
     FROM {$wpdb->prefix}flavor_gc_pedidos
     WHERE fecha_pedido BETWEEN %s AND %s
     GROUP BY periodo
     ORDER BY periodo ASC",
    $periodo === 'anio' ? '%Y-%m' : '%Y-%m-%d',
    $fecha_inicio . ' 00:00:00',
    $fecha_fin . ' 23:59:59'
));

// Top 10 productos
$top_productos = $wpdb->get_results($wpdb->prepare(
    "SELECT c.producto_id, p.post_title as nombre,
            SUM(c.cantidad_total) as cantidad,
            SUM(c.total) as importe
     FROM {$wpdb->prefix}flavor_gc_consolidado c
     LEFT JOIN {$wpdb->posts} p ON c.producto_id = p.ID
     LEFT JOIN {$wpdb->posts} ciclo ON c.ciclo_id = ciclo.ID
     LEFT JOIN {$wpdb->postmeta} pm ON ciclo.ID = pm.post_id AND pm.meta_key = '_gc_fecha_cierre'
     WHERE pm.meta_value BETWEEN %s AND %s
     GROUP BY c.producto_id
     ORDER BY cantidad DESC
     LIMIT 10",
    $fecha_inicio,
    $fecha_fin
));

// Top 10 consumidores
$top_consumidores = $wpdb->get_results($wpdb->prepare(
    "SELECT p.usuario_id, u.display_name, u.user_email,
            COUNT(*) as num_pedidos,
            SUM(p.total) as importe
     FROM {$wpdb->prefix}flavor_gc_pedidos p
     LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
     WHERE p.fecha_pedido BETWEEN %s AND %s
     GROUP BY p.usuario_id
     ORDER BY importe DESC
     LIMIT 10",
    $fecha_inicio . ' 00:00:00',
    $fecha_fin . ' 23:59:59'
));

// Estadísticas de suscripciones
$stats_suscripciones = $wpdb->get_row(
    "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'activa' THEN 1 ELSE 0 END) as activas,
        SUM(CASE WHEN estado = 'pausada' THEN 1 ELSE 0 END) as pausadas,
        SUM(CASE WHEN estado = 'activa' THEN importe ELSE 0 END) as mrr
     FROM {$wpdb->prefix}flavor_gc_suscripciones"
);

// Estadísticas de consumidores
$stats_consumidores = $wpdb->get_row(
    "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
        SUM(saldo_pendiente) as saldo_total
     FROM {$wpdb->prefix}flavor_gc_consumidores"
);

// Productores activos
$num_productores = $wpdb->get_var(
    "SELECT COUNT(DISTINCT productor_id)
     FROM {$wpdb->prefix}flavor_gc_consolidado
     WHERE productor_id > 0"
);

// Distribución por frecuencia de suscripción
$suscripciones_frecuencia = $wpdb->get_results(
    "SELECT frecuencia, COUNT(*) as total, SUM(importe) as importe
     FROM {$wpdb->prefix}flavor_gc_suscripciones
     WHERE estado = 'activa'
     GROUP BY frecuencia"
);
?>

<div class="wrap gc-admin-reportes">
    <h1><?php _e('Reportes y Estadísticas', 'flavor-chat-ia'); ?></h1>

    <!-- Filtro de período -->
    <div class="gc-filtro-periodo">
        <form method="get">
            <input type="hidden" name="page" value="gc-reportes">
            <div class="gc-filtro-grid">
                <div class="gc-filtro-item">
                    <label><?php _e('Período:', 'flavor-chat-ia'); ?></label>
                    <select name="periodo" id="gc-periodo-select">
                        <option value="semana" <?php selected($periodo, 'semana'); ?>><?php _e('Última semana', 'flavor-chat-ia'); ?></option>
                        <option value="mes" <?php selected($periodo, 'mes'); ?>><?php _e('Este mes', 'flavor-chat-ia'); ?></option>
                        <option value="trimestre" <?php selected($periodo, 'trimestre'); ?>><?php _e('Último trimestre', 'flavor-chat-ia'); ?></option>
                        <option value="anio" <?php selected($periodo, 'anio'); ?>><?php _e('Este año', 'flavor-chat-ia'); ?></option>
                        <option value="custom" <?php selected($periodo, 'custom'); ?>><?php _e('Personalizado', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
                <div class="gc-filtro-item gc-filtro-custom" style="<?php echo $periodo !== 'custom' ? 'display:none;' : ''; ?>">
                    <label><?php _e('Desde:', 'flavor-chat-ia'); ?></label>
                    <input type="date" name="desde" value="<?php echo esc_attr($fecha_inicio); ?>">
                </div>
                <div class="gc-filtro-item gc-filtro-custom" style="<?php echo $periodo !== 'custom' ? 'display:none;' : ''; ?>">
                    <label><?php _e('Hasta:', 'flavor-chat-ia'); ?></label>
                    <input type="date" name="hasta" value="<?php echo esc_attr($fecha_fin); ?>">
                </div>
                <div class="gc-filtro-item">
                    <button type="submit" class="button button-primary"><?php _e('Aplicar', 'flavor-chat-ia'); ?></button>
                </div>
            </div>
        </form>
    </div>

    <!-- KPIs Principales -->
    <div class="gc-kpis-grid">
        <div class="gc-kpi-card">
            <div class="gc-kpi-icon"><span class="dashicons dashicons-cart"></span></div>
            <div class="gc-kpi-content">
                <span class="gc-kpi-value"><?php echo number_format($stats_pedidos->total); ?></span>
                <span class="gc-kpi-label"><?php _e('Pedidos', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="gc-kpi-card gc-kpi-highlight">
            <div class="gc-kpi-icon"><span class="dashicons dashicons-money-alt"></span></div>
            <div class="gc-kpi-content">
                <span class="gc-kpi-value"><?php echo number_format($stats_pedidos->importe, 2, ',', '.'); ?>€</span>
                <span class="gc-kpi-label"><?php _e('Facturación', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="gc-kpi-card">
            <div class="gc-kpi-icon"><span class="dashicons dashicons-groups"></span></div>
            <div class="gc-kpi-content">
                <span class="gc-kpi-value"><?php echo number_format($stats_consumidores->activos ?? 0); ?></span>
                <span class="gc-kpi-label"><?php _e('Consumidores activos', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="gc-kpi-card">
            <div class="gc-kpi-icon"><span class="dashicons dashicons-heart"></span></div>
            <div class="gc-kpi-content">
                <span class="gc-kpi-value"><?php echo number_format($stats_suscripciones->activas ?? 0); ?></span>
                <span class="gc-kpi-label"><?php _e('Suscripciones activas', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="gc-kpi-card">
            <div class="gc-kpi-icon"><span class="dashicons dashicons-update"></span></div>
            <div class="gc-kpi-content">
                <span class="gc-kpi-value"><?php echo number_format($stats_suscripciones->mrr ?? 0, 2, ',', '.'); ?>€</span>
                <span class="gc-kpi-label"><?php _e('MRR (Ingresos recurrentes)', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="gc-kpi-card">
            <div class="gc-kpi-icon"><span class="dashicons dashicons-store"></span></div>
            <div class="gc-kpi-content">
                <span class="gc-kpi-value"><?php echo number_format($num_productores); ?></span>
                <span class="gc-kpi-label"><?php _e('Productores', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="gc-graficos-grid">
        <!-- Evolución temporal -->
        <div class="gc-grafico-card gc-grafico-wide">
            <h2><?php _e('Evolución de Pedidos', 'flavor-chat-ia'); ?></h2>
            <div class="gc-chart-container">
                <canvas id="gc-chart-evolucion" height="250"></canvas>
            </div>
        </div>

        <!-- Pedidos por estado -->
        <div class="gc-grafico-card">
            <h2><?php _e('Pedidos por Estado', 'flavor-chat-ia'); ?></h2>
            <div class="gc-chart-container">
                <canvas id="gc-chart-estados" height="200"></canvas>
            </div>
        </div>

        <!-- Suscripciones por frecuencia -->
        <div class="gc-grafico-card">
            <h2><?php _e('Suscripciones por Frecuencia', 'flavor-chat-ia'); ?></h2>
            <div class="gc-chart-container">
                <canvas id="gc-chart-frecuencias" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Tablas de rankings -->
    <div class="gc-tablas-grid">
        <!-- Top Productos -->
        <div class="gc-tabla-card">
            <h2><?php _e('Top 10 Productos', 'flavor-chat-ia'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php _e('Producto', 'flavor-chat-ia'); ?></th>
                        <th class="text-right"><?php _e('Cantidad', 'flavor-chat-ia'); ?></th>
                        <th class="text-right"><?php _e('Importe', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_productos)): ?>
                        <tr><td colspan="4" class="text-center"><?php _e('Sin datos', 'flavor-chat-ia'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($top_productos as $i => $producto): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo esc_html($producto->nombre); ?></td>
                                <td class="text-right"><?php echo number_format($producto->cantidad, 2, ',', '.'); ?></td>
                                <td class="text-right"><?php echo number_format($producto->importe, 2, ',', '.'); ?>€</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Top Consumidores -->
        <div class="gc-tabla-card">
            <h2><?php _e('Top 10 Consumidores', 'flavor-chat-ia'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php _e('Consumidor', 'flavor-chat-ia'); ?></th>
                        <th class="text-right"><?php _e('Pedidos', 'flavor-chat-ia'); ?></th>
                        <th class="text-right"><?php _e('Importe', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_consumidores)): ?>
                        <tr><td colspan="4" class="text-center"><?php _e('Sin datos', 'flavor-chat-ia'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($top_consumidores as $i => $consumidor): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td>
                                    <?php echo esc_html($consumidor->display_name); ?>
                                    <br><small><?php echo esc_html($consumidor->user_email); ?></small>
                                </td>
                                <td class="text-right"><?php echo number_format($consumidor->num_pedidos); ?></td>
                                <td class="text-right"><?php echo number_format($consumidor->importe, 2, ',', '.'); ?>€</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Acciones de exportación -->
    <div class="gc-acciones-exportar">
        <h2><?php _e('Exportar Datos', 'flavor-chat-ia'); ?></h2>
        <div class="gc-export-buttons">
            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=gc_exportar_pedidos'), 'gc_exportar_pedidos'); ?>"
               class="button">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Exportar Pedidos', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=gc_exportar_consumidores'), 'gc_exportar_consumidores'); ?>"
               class="button">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Exportar Consumidores', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=gc_exportar_suscripciones'), 'gc_exportar_suscripciones'); ?>"
               class="button">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Exportar Suscripciones', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>
</div>

<style>
.gc-admin-reportes {
    max-width: 1400px;
}

.gc-filtro-periodo {
    background: #fff;
    padding: 15px 20px;
    margin: 20px 0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gc-filtro-grid {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.gc-filtro-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.gc-filtro-item label {
    font-size: 12px;
    color: #666;
}

.gc-filtro-item select,
.gc-filtro-item input[type="date"] {
    padding: 8px;
    min-width: 150px;
}

.gc-kpis-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.gc-kpi-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.gc-kpi-highlight {
    background: linear-gradient(135deg, #2c5530 0%, #4a7c59 100%);
    color: #fff;
}

.gc-kpi-highlight .gc-kpi-icon {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

.gc-kpi-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #f0f7f1;
    color: #2c5530;
    display: flex;
    align-items: center;
    justify-content: center;
}

.gc-kpi-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.gc-kpi-content {
    display: flex;
    flex-direction: column;
}

.gc-kpi-value {
    font-size: 24px;
    font-weight: bold;
    line-height: 1.2;
}

.gc-kpi-label {
    font-size: 13px;
    opacity: 0.8;
}

.gc-graficos-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin: 20px 0;
}

.gc-grafico-wide {
    grid-column: span 2;
}

.gc-grafico-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gc-grafico-card h2 {
    margin: 0 0 15px;
    font-size: 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.gc-chart-container {
    position: relative;
}

.gc-tablas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.gc-tabla-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.gc-tabla-card h2 {
    margin: 0 0 15px;
    font-size: 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.gc-tabla-card table {
    margin: 0;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

.gc-acciones-exportar {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin: 20px 0;
}

.gc-acciones-exportar h2 {
    margin: 0 0 15px;
    font-size: 16px;
}

.gc-export-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.gc-export-buttons .button .dashicons {
    margin-right: 5px;
    vertical-align: middle;
}

@media (max-width: 782px) {
    .gc-graficos-grid {
        grid-template-columns: 1fr;
    }

    .gc-grafico-wide {
        grid-column: span 1;
    }

    .gc-tablas-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
jQuery(document).ready(function($) {
    // Toggle período personalizado
    $('#gc-periodo-select').on('change', function() {
        if ($(this).val() === 'custom') {
            $('.gc-filtro-custom').show();
        } else {
            $('.gc-filtro-custom').hide();
        }
    });

    // Gráfico de evolución
    var ctxEvolucion = document.getElementById('gc-chart-evolucion');
    if (ctxEvolucion) {
        var datosEvolucion = <?php echo wp_json_encode($evolucion); ?>;
        new Chart(ctxEvolucion, {
            type: 'line',
            data: {
                labels: datosEvolucion.map(function(d) { return d.periodo; }),
                datasets: [{
                    label: 'Pedidos',
                    data: datosEvolucion.map(function(d) { return d.pedidos; }),
                    borderColor: '#2c5530',
                    backgroundColor: 'rgba(44, 85, 48, 0.1)',
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y'
                }, {
                    label: 'Importe (€)',
                    data: datosEvolucion.map(function(d) { return parseFloat(d.importe); }),
                    borderColor: '#4a7c59',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    tension: 0.3,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Pedidos'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Importe (€)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });
    }

    // Gráfico de estados
    var ctxEstados = document.getElementById('gc-chart-estados');
    if (ctxEstados) {
        var datosEstados = <?php echo wp_json_encode($pedidos_por_estado); ?>;
        var coloresEstados = {
            'pendiente': '#ffc107',
            'confirmado': '#17a2b8',
            'preparando': '#6c757d',
            'listo': '#28a745',
            'entregado': '#2c5530',
            'cancelado': '#dc3545'
        };
        new Chart(ctxEstados, {
            type: 'doughnut',
            data: {
                labels: datosEstados.map(function(d) { return d.estado.charAt(0).toUpperCase() + d.estado.slice(1); }),
                datasets: [{
                    data: datosEstados.map(function(d) { return d.total; }),
                    backgroundColor: datosEstados.map(function(d) { return coloresEstados[d.estado] || '#999'; })
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Gráfico de frecuencias de suscripción
    var ctxFrecuencias = document.getElementById('gc-chart-frecuencias');
    if (ctxFrecuencias) {
        var datosFrecuencias = <?php echo wp_json_encode($suscripciones_frecuencia); ?>;
        var nombresFrecuencias = {
            'semanal': 'Semanal',
            'quincenal': 'Quincenal',
            'mensual': 'Mensual'
        };
        new Chart(ctxFrecuencias, {
            type: 'bar',
            data: {
                labels: datosFrecuencias.map(function(d) { return nombresFrecuencias[d.frecuencia] || d.frecuencia; }),
                datasets: [{
                    label: 'Suscripciones',
                    data: datosFrecuencias.map(function(d) { return d.total; }),
                    backgroundColor: ['#2c5530', '#4a7c59', '#6b9b7a']
                }]
            },
            options: {
                responsive: true,
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
