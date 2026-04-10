<?php
/**
 * Vista de Estadísticas - Módulo Campañas
 *
 * @package FlavorPlatform
 * @subpackage Campanias
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Obtener rango de fechas
$fecha_inicio = isset($_GET['fecha_inicio']) ? sanitize_text_field($_GET['fecha_inicio']) : date('Y-m-d', strtotime('-30 days'));
$fecha_fin = isset($_GET['fecha_fin']) ? sanitize_text_field($_GET['fecha_fin']) : date('Y-m-d');

// Estadísticas generales
$tabla_campanias = $wpdb->prefix . 'flavor_campanias';
$tabla_firmas = $wpdb->prefix . 'flavor_campanias_firmas';
$tabla_donaciones = $wpdb->prefix . 'flavor_campanias_donaciones';

// Total de campañas
$total_campanias = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_campanias}");
$campanias_activas = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_campanias} WHERE estado = 'activa'");
$campanias_completadas = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_campanias} WHERE estado = 'completada'");

// Total de firmas
$total_firmas = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_firmas}");
$firmas_periodo = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_firmas} WHERE DATE(fecha_firma) BETWEEN %s AND %s",
    $fecha_inicio,
    $fecha_fin
));

// Donaciones (si aplica)
$total_donaciones = $wpdb->get_var("SELECT COALESCE(SUM(monto), 0) FROM {$tabla_donaciones}");
$donaciones_periodo = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(monto), 0) FROM {$tabla_donaciones} WHERE DATE(fecha) BETWEEN %s AND %s",
    $fecha_inicio,
    $fecha_fin
));

// Campañas más populares
$campanias_populares = $wpdb->get_results("
    SELECT c.*, COUNT(f.id) as total_firmas
    FROM {$tabla_campanias} c
    LEFT JOIN {$tabla_firmas} f ON c.id = f.campania_id
    GROUP BY c.id
    ORDER BY total_firmas DESC
    LIMIT 5
");

// Firmas por día (últimos 30 días)
$firmas_por_dia = $wpdb->get_results($wpdb->prepare("
    SELECT DATE(fecha_firma) as fecha, COUNT(*) as cantidad
    FROM {$tabla_firmas}
    WHERE DATE(fecha_firma) BETWEEN %s AND %s
    GROUP BY DATE(fecha_firma)
    ORDER BY fecha ASC
", $fecha_inicio, $fecha_fin));

// Preparar datos para gráfico
$labels_grafico = [];
$datos_grafico = [];
foreach ($firmas_por_dia as $dia) {
    $labels_grafico[] = date_i18n('d M', strtotime($dia->fecha));
    $datos_grafico[] = (int) $dia->cantidad;
}

// Categorías de campañas
$categorias_stats = $wpdb->get_results("
    SELECT categoria, COUNT(*) as total, SUM(
        (SELECT COUNT(*) FROM {$tabla_firmas} f WHERE f.campania_id = c.id)
    ) as firmas_total
    FROM {$tabla_campanias} c
    WHERE categoria IS NOT NULL AND categoria != ''
    GROUP BY categoria
    ORDER BY total DESC
");
?>

<div class="wrap flavor-campanias-estadisticas">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-chart-bar"></span>
        <?php esc_html_e('Estadísticas de Campañas', 'flavor-platform'); ?>
    </h1>
    <hr class="wp-header-end">

    <!-- Filtro de fechas -->
    <div class="dm-filter-bar">
        <form method="get" class="dm-date-filter">
            <input type="hidden" name="page" value="flavor-campanias">
            <input type="hidden" name="subpage" value="campanias-estadisticas">
            <div class="dm-filter-group">
                <label><?php esc_html_e('Desde', 'flavor-platform'); ?></label>
                <input type="date" name="fecha_inicio" value="<?php echo esc_attr($fecha_inicio); ?>">
            </div>
            <div class="dm-filter-group">
                <label><?php esc_html_e('Hasta', 'flavor-platform'); ?></label>
                <input type="date" name="fecha_fin" value="<?php echo esc_attr($fecha_fin); ?>">
            </div>
            <button type="submit" class="button"><?php esc_html_e('Filtrar', 'flavor-platform'); ?></button>
        </form>
    </div>

    <!-- KPIs principales -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card">
            <div class="dm-stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <span class="dashicons dashicons-megaphone"></span>
            </div>
            <div class="dm-stat-content">
                <span class="dm-stat-value"><?php echo number_format_i18n($total_campanias); ?></span>
                <span class="dm-stat-label"><?php esc_html_e('Total Campañas', 'flavor-platform'); ?></span>
            </div>
        </div>

        <div class="dm-stat-card">
            <div class="dm-stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dm-stat-content">
                <span class="dm-stat-value"><?php echo number_format_i18n($campanias_activas); ?></span>
                <span class="dm-stat-label"><?php esc_html_e('Campañas Activas', 'flavor-platform'); ?></span>
            </div>
        </div>

        <div class="dm-stat-card">
            <div class="dm-stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <span class="dashicons dashicons-edit-page"></span>
            </div>
            <div class="dm-stat-content">
                <span class="dm-stat-value"><?php echo number_format_i18n($total_firmas); ?></span>
                <span class="dm-stat-label"><?php esc_html_e('Total Firmas', 'flavor-platform'); ?></span>
            </div>
        </div>

        <div class="dm-stat-card">
            <div class="dm-stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="dm-stat-content">
                <span class="dm-stat-value"><?php echo number_format_i18n($firmas_periodo); ?></span>
                <span class="dm-stat-label"><?php esc_html_e('Firmas en Período', 'flavor-platform'); ?></span>
            </div>
        </div>
    </div>

    <div class="dm-stats-row">
        <!-- Gráfico de firmas -->
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-chart-area"></span> <?php esc_html_e('Evolución de Firmas', 'flavor-platform'); ?></h3>
            </div>
            <div class="dm-card__body">
                <canvas id="firmas-chart" height="250"></canvas>
            </div>
        </div>

        <!-- Campañas más populares -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e('Campañas Más Populares', 'flavor-platform'); ?></h3>
            </div>
            <div class="dm-card__body">
                <?php if ($campanias_populares): ?>
                    <ul class="dm-popular-list">
                        <?php foreach ($campanias_populares as $campania): ?>
                            <li class="dm-popular-item">
                                <div class="dm-popular-info">
                                    <span class="dm-popular-title"><?php echo esc_html($campania->titulo); ?></span>
                                    <span class="dm-popular-meta">
                                        <?php
                                        $progreso = $campania->meta_firmas > 0
                                            ? min(100, round(($campania->total_firmas / $campania->meta_firmas) * 100))
                                            : 0;
                                        printf(
                                            esc_html__('%s firmas de %s (%d%%)', 'flavor-platform'),
                                            number_format_i18n($campania->total_firmas),
                                            number_format_i18n($campania->meta_firmas ?: 0),
                                            $progreso
                                        );
                                        ?>
                                    </span>
                                </div>
                                <div class="dm-progress-mini">
                                    <div class="dm-progress-bar" style="width: <?php echo $progreso; ?>%;"></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="dm-empty"><?php esc_html_e('No hay campañas registradas.', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Estadísticas por categoría -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><span class="dashicons dashicons-category"></span> <?php esc_html_e('Estadísticas por Categoría', 'flavor-platform'); ?></h3>
        </div>
        <div class="dm-card__body">
            <?php if ($categorias_stats): ?>
                <table class="widefat striped dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Categoría', 'flavor-platform'); ?></th>
                            <th class="num"><?php esc_html_e('Campañas', 'flavor-platform'); ?></th>
                            <th class="num"><?php esc_html_e('Total Firmas', 'flavor-platform'); ?></th>
                            <th class="num"><?php esc_html_e('Promedio Firmas', 'flavor-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias_stats as $cat): ?>
                            <tr>
                                <td>
                                    <span class="dm-category-badge"><?php echo esc_html(ucfirst($cat->categoria)); ?></span>
                                </td>
                                <td class="num"><?php echo number_format_i18n($cat->total); ?></td>
                                <td class="num"><?php echo number_format_i18n($cat->firmas_total ?: 0); ?></td>
                                <td class="num">
                                    <?php echo number_format_i18n($cat->total > 0 ? round($cat->firmas_total / $cat->total) : 0); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="dm-empty"><?php esc_html_e('No hay datos de categorías disponibles.', 'flavor-platform'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resumen de donaciones si hay -->
    <?php if ($total_donaciones > 0): ?>
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><span class="dashicons dashicons-money-alt"></span> <?php esc_html_e('Donaciones', 'flavor-platform'); ?></h3>
        </div>
        <div class="dm-card__body">
            <div class="dm-donation-stats">
                <div class="dm-donation-stat">
                    <span class="dm-donation-value"><?php echo number_format_i18n($total_donaciones, 2); ?> &euro;</span>
                    <span class="dm-donation-label"><?php esc_html_e('Total Recaudado', 'flavor-platform'); ?></span>
                </div>
                <div class="dm-donation-stat">
                    <span class="dm-donation-value"><?php echo number_format_i18n($donaciones_periodo, 2); ?> &euro;</span>
                    <span class="dm-donation-label"><?php esc_html_e('En el Período', 'flavor-platform'); ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.flavor-campanias-estadisticas { max-width: 1400px; }
.dm-filter-bar { background: #fff; padding: 15px 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.dm-date-filter { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
.dm-filter-group { display: flex; align-items: center; gap: 8px; }
.dm-filter-group label { font-weight: 600; font-size: 13px; }
.dm-filter-group input { padding: 6px 10px; border: 1px solid #dcdcde; border-radius: 4px; }

.dm-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
@media (max-width: 1200px) { .dm-stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .dm-stats-grid { grid-template-columns: 1fr; } }

.dm-stat-card { background: #fff; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.dm-stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.dm-stat-icon .dashicons { font-size: 28px; width: 28px; height: 28px; color: #fff; }
.dm-stat-content { display: flex; flex-direction: column; }
.dm-stat-value { font-size: 28px; font-weight: 700; color: #1d2327; line-height: 1.2; }
.dm-stat-label { font-size: 13px; color: #646970; margin-top: 2px; }

.dm-stats-row { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; margin-bottom: 25px; }
@media (max-width: 1024px) { .dm-stats-row { grid-template-columns: 1fr; } }

.dm-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.dm-card--chart { min-height: 350px; }
.dm-card__header { padding: 15px 20px; border-bottom: 1px solid #f0f0f1; }
.dm-card__header h3 { margin: 0; font-size: 14px; display: flex; align-items: center; gap: 8px; }
.dm-card__header .dashicons { color: #2271b1; }
.dm-card__body { padding: 20px; }

.dm-popular-list { margin: 0; padding: 0; list-style: none; }
.dm-popular-item { padding: 12px 0; border-bottom: 1px solid #f0f0f1; }
.dm-popular-item:last-child { border-bottom: none; }
.dm-popular-info { margin-bottom: 8px; }
.dm-popular-title { display: block; font-weight: 600; font-size: 13px; color: #1d2327; margin-bottom: 2px; }
.dm-popular-meta { font-size: 12px; color: #646970; }
.dm-progress-mini { height: 6px; background: #f0f0f1; border-radius: 3px; overflow: hidden; }
.dm-progress-bar { height: 100%; background: linear-gradient(90deg, #667eea, #764ba2); border-radius: 3px; transition: width 0.3s; }

.dm-table { border: none; }
.dm-table th { font-weight: 600; font-size: 13px; }
.dm-table td.num, .dm-table th.num { text-align: right; }
.dm-category-badge { display: inline-block; padding: 4px 10px; background: #f0f0f1; border-radius: 12px; font-size: 12px; font-weight: 500; }

.dm-empty { color: #646970; font-style: italic; text-align: center; padding: 30px; }

.dm-donation-stats { display: flex; gap: 40px; justify-content: center; }
.dm-donation-stat { text-align: center; }
.dm-donation-value { display: block; font-size: 32px; font-weight: 700; color: #00a32a; }
.dm-donation-label { font-size: 13px; color: #646970; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('firmas-chart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels_grafico); ?>,
                datasets: [{
                    label: '<?php esc_html_e('Firmas', 'flavor-platform'); ?>',
                    data: <?php echo json_encode($datos_grafico); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>
