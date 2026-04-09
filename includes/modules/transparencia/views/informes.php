<?php
/**
 * Vista: Informes - Módulo Transparencia
 *
 * Informes y estadísticas detalladas del portal de transparencia.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$prefijo = $wpdb->prefix . 'flavor_transparencia_';
$tabla_datos = $prefijo . 'documentos_publicos';
$tabla_solicitudes = $prefijo . 'solicitudes';
$tabla_gastos = $prefijo . 'gastos';

// Verificar tablas
$datos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_datos)) === $tabla_datos;
$solicitudes_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_solicitudes)) === $tabla_solicitudes;
$gastos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_gastos)) === $tabla_gastos;

// Período seleccionado
$periodo = isset($_GET['periodo']) ? sanitize_key($_GET['periodo']) : 'mes';
$year = isset($_GET['year']) ? absint($_GET['year']) : (int) date('Y');

// Calcular fechas según período
switch ($periodo) {
    case 'semana':
        $fecha_inicio = date('Y-m-d', strtotime('-7 days'));
        $periodo_label = __('Última semana', FLAVOR_PLATFORM_TEXT_DOMAIN);
        break;
    case 'trimestre':
        $fecha_inicio = date('Y-m-d', strtotime('-3 months'));
        $periodo_label = __('Último trimestre', FLAVOR_PLATFORM_TEXT_DOMAIN);
        break;
    case 'year':
        $fecha_inicio = $year . '-01-01';
        $periodo_label = sprintf(__('Año %d', FLAVOR_PLATFORM_TEXT_DOMAIN), $year);
        break;
    case 'mes':
    default:
        $fecha_inicio = date('Y-m-d', strtotime('-30 days'));
        $periodo_label = __('Último mes', FLAVOR_PLATFORM_TEXT_DOMAIN);
        break;
}

// Estadísticas de documentos
$stats_docs = [
    'total' => 0,
    'publicados' => 0,
    'por_categoria' => [],
    'visitas_total' => 0,
    'descargas_total' => 0,
];

if ($datos_existe) {
    $stats_docs['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_datos");
    $stats_docs['publicados'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_datos WHERE estado = 'publicado'");
    $stats_docs['visitas_total'] = (int) $wpdb->get_var("SELECT COALESCE(SUM(visitas), 0) FROM $tabla_datos");
    $stats_docs['descargas_total'] = (int) $wpdb->get_var("SELECT COALESCE(SUM(descargas), 0) FROM $tabla_datos");

    $stats_docs['por_categoria'] = $wpdb->get_results(
        "SELECT categoria, COUNT(*) as total, SUM(visitas) as visitas, SUM(descargas) as descargas
         FROM $tabla_datos
         WHERE estado = 'publicado'
         GROUP BY categoria
         ORDER BY total DESC",
        ARRAY_A
    );

    // Tendencia de publicaciones
    $tendencia_publicaciones = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(fecha_publicacion) as fecha, COUNT(*) as total
         FROM $tabla_datos
         WHERE fecha_publicacion >= %s AND estado = 'publicado'
         GROUP BY DATE(fecha_publicacion)
         ORDER BY fecha ASC",
        $fecha_inicio
    ));
}

// Estadísticas de solicitudes
$stats_solicitudes = [
    'total' => 0,
    'pendientes' => 0,
    'resueltas' => 0,
    'denegadas' => 0,
    'tiempo_promedio' => 0,
    'tasa_resolucion' => 0,
];

if ($solicitudes_existe) {
    $stats_solicitudes['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes");
    $stats_solicitudes['pendientes'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado IN ('recibida', 'en_tramite')");
    $stats_solicitudes['resueltas'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'resuelta'");
    $stats_solicitudes['denegadas'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'denegada'");

    $stats_solicitudes['tiempo_promedio'] = (float) $wpdb->get_var(
        "SELECT AVG(DATEDIFF(fecha_resolucion, fecha_solicitud))
         FROM $tabla_solicitudes
         WHERE estado = 'resuelta' AND fecha_resolucion IS NOT NULL"
    );

    $total_cerradas = $stats_solicitudes['resueltas'] + $stats_solicitudes['denegadas'];
    if ($total_cerradas > 0) {
        $stats_solicitudes['tasa_resolucion'] = round(($stats_solicitudes['resueltas'] / $total_cerradas) * 100, 1);
    }

    // Tendencia de solicitudes
    $tendencia_solicitudes = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(fecha_solicitud) as fecha, COUNT(*) as total
         FROM $tabla_solicitudes
         WHERE fecha_solicitud >= %s
         GROUP BY DATE(fecha_solicitud)
         ORDER BY fecha ASC",
        $fecha_inicio
    ));
}

// Estadísticas de gastos
$stats_gastos = [
    'total_importe' => 0,
    'total_registros' => 0,
    'por_categoria' => [],
];

if ($gastos_existe) {
    $stats_gastos['total_registros'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_gastos WHERE ejercicio = $year");
    $stats_gastos['total_importe'] = (float) $wpdb->get_var("SELECT COALESCE(SUM(importe_total), 0) FROM $tabla_gastos WHERE ejercicio = $year");

    $stats_gastos['por_categoria'] = $wpdb->get_results($wpdb->prepare(
        "SELECT categoria, COUNT(*) as registros, SUM(importe_total) as importe
         FROM $tabla_gastos
         WHERE ejercicio = %d
         GROUP BY categoria
         ORDER BY importe DESC
         LIMIT 10",
        $year
    ), ARRAY_A);
}

// Top documentos
$top_documentos = [];
if ($datos_existe) {
    $top_documentos = $wpdb->get_results(
        "SELECT titulo, categoria, visitas, descargas
         FROM $tabla_datos
         WHERE estado = 'publicado'
         ORDER BY visitas DESC
         LIMIT 10"
    );
}

// Preparar datos para gráficos
$tendencia_pub_labels = array_map(function($t) { return date_i18n('d M', strtotime($t->fecha)); }, $tendencia_publicaciones ?? []);
$tendencia_pub_data = array_map(function($t) { return (int) $t->total; }, $tendencia_publicaciones ?? []);

$tendencia_sol_labels = array_map(function($t) { return date_i18n('d M', strtotime($t->fecha)); }, $tendencia_solicitudes ?? []);
$tendencia_sol_data = array_map(function($t) { return (int) $t->total; }, $tendencia_solicitudes ?? []);

$cat_labels = array_map(function($c) { return ucfirst($c['categoria'] ?? 'Otros'); }, $stats_docs['por_categoria']);
$cat_data = array_map(function($c) { return (int) $c['total']; }, $stats_docs['por_categoria']);
?>

<div class="wrap flavor-transparencia-informes">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-chart-bar"></span>
        <?php esc_html_e('Informes de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>
    <hr class="wp-header-end">

    <!-- Filtro de período -->
    <div class="dm-card" style="margin-bottom: 20px;">
        <form method="get" class="dm-filters" style="padding: 15px 20px;">
            <input type="hidden" name="page" value="transparencia-informes">
            <div class="dm-filters__row" style="display: flex; gap: 15px; align-items: center;">
                <label><?php esc_html_e('Período:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="periodo" onchange="this.form.submit()">
                    <option value="semana" <?php selected($periodo, 'semana'); ?>><?php esc_html_e('Última semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="mes" <?php selected($periodo, 'mes'); ?>><?php esc_html_e('Último mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="trimestre" <?php selected($periodo, 'trimestre'); ?>><?php esc_html_e('Último trimestre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="year" <?php selected($periodo, 'year'); ?>><?php esc_html_e('Año completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>

                <?php if ($periodo === 'year'): ?>
                    <select name="year" onchange="this.form.submit()">
                        <?php for ($y = (int) date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php selected($year, $y); ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                <?php endif; ?>

                <span class="dm-badge dm-badge--info"><?php echo esc_html($periodo_label); ?></span>

                <button type="button" class="button" onclick="window.print();">
                    <span class="dashicons dashicons-printer"></span>
                    <?php esc_html_e('Imprimir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Métricas principales -->
    <div class="dm-stats-grid dm-stats-grid--4" style="margin-bottom: 20px;">
        <div class="dm-stat-card dm-stat-card--primary">
            <span class="dashicons dashicons-media-spreadsheet dm-stat-card__icon"></span>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($stats_docs['publicados']); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Documentos Publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <span class="dashicons dashicons-yes-alt dm-stat-card__icon"></span>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($stats_solicitudes['resueltas']); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Solicitudes Resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div class="dm-stat-card__meta"><?php printf(esc_html__('Tasa: %s%%', FLAVOR_PLATFORM_TEXT_DOMAIN), $stats_solicitudes['tasa_resolucion']); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <span class="dashicons dashicons-visibility dm-stat-card__icon"></span>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($stats_docs['visitas_total']); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Visitas Totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <span class="dashicons dashicons-download dm-stat-card__icon"></span>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($stats_docs['descargas_total']); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Descargas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2" style="margin-bottom: 20px;">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-chart-line"></span> <?php esc_html_e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__chart" style="height: 250px; padding: 15px;">
                <canvas id="chart-publicaciones"></canvas>
            </div>
        </div>

        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e('Por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__chart" style="height: 250px; padding: 15px;">
                <canvas id="chart-categorias"></canvas>
            </div>
        </div>
    </div>

    <!-- Tablas de detalle -->
    <div class="dm-grid dm-grid--2">
        <!-- Top documentos -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e('Top 10 Documentos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Documento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 80px;"><?php esc_html_e('Visitas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 80px;"><?php esc_html_e('Descargas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_documentos)): ?>
                        <tr><td colspan="3" class="description"><?php esc_html_e('Sin datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($top_documentos as $doc): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html(wp_trim_words($doc->titulo, 8)); ?></strong>
                                    <br><span class="description"><?php echo esc_html(ucfirst($doc->categoria)); ?></span>
                                </td>
                                <td><?php echo number_format_i18n($doc->visitas); ?></td>
                                <td><?php echo number_format_i18n($doc->descargas); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Resumen por categoría -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-category"></span> <?php esc_html_e('Resumen por Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 80px;"><?php esc_html_e('Docs', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 80px;"><?php esc_html_e('Visitas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 80px;"><?php esc_html_e('Descargas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stats_docs['por_categoria'])): ?>
                        <tr><td colspan="4" class="description"><?php esc_html_e('Sin datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($stats_docs['por_categoria'] as $cat): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucfirst($cat['categoria'] ?? 'Otros')); ?></strong></td>
                                <td><?php echo number_format_i18n($cat['total']); ?></td>
                                <td><?php echo number_format_i18n($cat['visitas'] ?? 0); ?></td>
                                <td><?php echo number_format_i18n($cat['descargas'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Indicadores de rendimiento -->
    <div class="dm-card" style="margin-top: 20px;">
        <div class="dm-card__header">
            <h3><span class="dashicons dashicons-performance"></span> <?php esc_html_e('Indicadores de Rendimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-card__body">
            <div class="dm-stats-grid dm-stats-grid--4">
                <div class="dm-kpi">
                    <div class="dm-kpi__value"><?php echo number_format_i18n(round($stats_solicitudes['tiempo_promedio'], 1)); ?></div>
                    <div class="dm-kpi__label"><?php esc_html_e('Días promedio de resolución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="dm-kpi__target"><?php esc_html_e('Objetivo: < 30 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>

                <div class="dm-kpi">
                    <div class="dm-kpi__value"><?php echo $stats_solicitudes['tasa_resolucion']; ?>%</div>
                    <div class="dm-kpi__label"><?php esc_html_e('Tasa de resolución positiva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="dm-kpi__target"><?php esc_html_e('Objetivo: > 90%', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>

                <div class="dm-kpi">
                    <div class="dm-kpi__value"><?php echo number_format_i18n($stats_solicitudes['pendientes']); ?></div>
                    <div class="dm-kpi__label"><?php esc_html_e('Solicitudes pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="dm-kpi__target"><?php esc_html_e('Objetivo: < 10', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>

                <div class="dm-kpi">
                    <div class="dm-kpi__value"><?php echo number_format_i18n($stats_docs['publicados']); ?></div>
                    <div class="dm-kpi__label"><?php esc_html_e('Documentos publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <div class="dm-kpi__target"><?php esc_html_e('Actualización continua', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-transparencia-informes .dm-kpi {
    text-align: center;
    padding: 20px;
    background: var(--dm-bg-secondary, #f8fafc);
    border-radius: var(--dm-radius, 8px);
}
.flavor-transparencia-informes .dm-kpi__value {
    font-size: 32px;
    font-weight: 700;
    color: var(--dm-primary, #3b82f6);
    line-height: 1;
}
.flavor-transparencia-informes .dm-kpi__label {
    font-size: 13px;
    color: var(--dm-text, #1e293b);
    margin-top: 8px;
    font-weight: 500;
}
.flavor-transparencia-informes .dm-kpi__target {
    font-size: 11px;
    color: var(--dm-text-muted, #94a3b8);
    margin-top: 4px;
}
@media print {
    .dm-filters, .page-title-action, .wp-header-end { display: none !important; }
    .dm-card { break-inside: avoid; }
}
</style>

<script>
jQuery(document).ready(function($) {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js no disponible');
        return;
    }

    var primaryColor = '#3b82f6';
    var colors = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];

    // Gráfico de publicaciones
    var ctxPub = document.getElementById('chart-publicaciones');
    if (ctxPub) {
        new Chart(ctxPub.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo wp_json_encode($tendencia_pub_labels); ?>,
                datasets: [{
                    label: '<?php esc_attr_e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                    data: <?php echo wp_json_encode($tendencia_pub_data); ?>,
                    borderColor: primaryColor,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // Gráfico de categorías
    var ctxCat = document.getElementById('chart-categorias');
    if (ctxCat) {
        new Chart(ctxCat.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo wp_json_encode($cat_labels); ?>,
                datasets: [{
                    data: <?php echo wp_json_encode($cat_data); ?>,
                    backgroundColor: colors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                },
                cutout: '50%'
            }
        });
    }
});
</script>
