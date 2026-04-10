<?php
/**
 * Dashboard de Publicidad Ética
 *
 * Panel administrativo para gestión de anuncios con métricas de rendimiento,
 * ingresos y reparto comunitario.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_stats = $wpdb->prefix . 'flavor_ads_stats';
$tabla_stats_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_stats'") === $tabla_stats;

// Estadísticas de anuncios por estado
$anuncios_activos = count(get_posts([
    'post_type' => 'flavor_ad',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
]));

$anuncios_pendientes = count(get_posts([
    'post_type' => 'flavor_ad',
    'post_status' => 'pending',
    'posts_per_page' => -1,
    'fields' => 'ids',
]));

$anuncios_borrador = count(get_posts([
    'post_type' => 'flavor_ad',
    'post_status' => 'draft',
    'posts_per_page' => -1,
    'fields' => 'ids',
]));

$total_anuncios = $anuncios_activos + $anuncios_pendientes + $anuncios_borrador;

// Estadísticas de rendimiento
$primer_dia_mes = date('Y-m-01');
$hoy = date('Y-m-d');
$impresiones_mes = 0;
$clics_mes = 0;
$ingresos_mes = 0;
$impresiones_hoy = 0;
$clics_hoy = 0;

if ($tabla_stats_existe) {
    $stats_mes = $wpdb->get_row($wpdb->prepare(
        "SELECT
            COALESCE(SUM(impresiones), 0) as impresiones,
            COALESCE(SUM(clics), 0) as clics,
            COALESCE(SUM(gasto), 0) as gasto
        FROM $tabla_stats
        WHERE fecha >= %s",
        $primer_dia_mes
    ));

    if ($stats_mes) {
        $impresiones_mes = (int) $stats_mes->impresiones;
        $clics_mes = (int) $stats_mes->clics;
        $ingresos_mes = (float) $stats_mes->gasto;
    }

    $stats_hoy = $wpdb->get_row($wpdb->prepare(
        "SELECT
            COALESCE(SUM(impresiones), 0) as impresiones,
            COALESCE(SUM(clics), 0) as clics
        FROM $tabla_stats
        WHERE fecha = %s",
        $hoy
    ));

    if ($stats_hoy) {
        $impresiones_hoy = (int) $stats_hoy->impresiones;
        $clics_hoy = (int) $stats_hoy->clics;
    }
}

$ctr_mes = $impresiones_mes > 0 ? round(($clics_mes / $impresiones_mes) * 100, 2) : 0;
$pool_comunidad = (float) get_option('flavor_ads_pool_comunidad', 0);

// Evolución últimos 7 días
$evolucion_semanal = [];
if ($tabla_stats_existe) {
    $evolucion_semanal = $wpdb->get_results(
        "SELECT
            DATE(fecha) as dia,
            COALESCE(SUM(impresiones), 0) as impresiones,
            COALESCE(SUM(clics), 0) as clics,
            COALESCE(SUM(gasto), 0) as ingresos
        FROM $tabla_stats
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(fecha)
        ORDER BY fecha ASC"
    );
}

// Últimos anuncios
$ultimos_anuncios = get_posts([
    'post_type' => 'flavor_ad',
    'post_status' => ['publish', 'pending', 'draft'],
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC',
]);

// Top anuncios por rendimiento
$top_anuncios = [];
if ($tabla_stats_existe) {
    $top_anuncios = $wpdb->get_results(
        "SELECT
            s.anuncio_id,
            p.post_title as titulo,
            SUM(s.impresiones) as impresiones,
            SUM(s.clics) as clics,
            SUM(s.gasto) as ingresos
        FROM $tabla_stats s
        LEFT JOIN {$wpdb->posts} p ON s.anuncio_id = p.ID
        WHERE s.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY s.anuncio_id
        ORDER BY clics DESC
        LIMIT 5"
    );
}
?>

<div class="wrap dm-dashboard">
    <?php flavor_dashboard_help('advertising'); ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-megaphone" style="color: #f59e0b;"></span>
            <h1><?php esc_html_e('Publicidad Ética', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=flavor_ad')); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Nuevo Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-card">
        <div class="dm-action-grid">
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=flavor_ad&post_status=publish')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-yes-alt"></span>
                <span><?php esc_html_e('Anuncios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=flavor_ad&post_status=pending')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-clock"></span>
                <span><?php esc_html_e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=advertising-campanas')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-chart-line"></span>
                <span><?php esc_html_e('Campañas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=advertising-config')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-admin-generic"></span>
                <span><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>
    </div>

    <!-- Estadísticas Principales -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-megaphone"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html($anuncios_activos); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Anuncios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html(number_format($impresiones_mes)); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Impresiones (mes)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-external"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html(number_format($clics_mes)); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Clics (mes)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="dm-stat-card__meta">CTR: <?php echo esc_html($ctr_mes); ?>%</span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html(number_format($ingresos_mes, 2)); ?>€</span>
                <span class="dm-stat-card__label"><?php esc_html_e('Ingresos (mes)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <?php if ($anuncios_pendientes > 0): ?>
    <!-- Alerta de pendientes -->
    <div class="dm-alert dm-alert--warning">
        <span class="dashicons dashicons-warning"></span>
        <div class="dm-alert__content">
            <strong><?php echo esc_html($anuncios_pendientes); ?> <?php esc_html_e('anuncios pendientes de aprobación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
            <p><?php esc_html_e('Revisa y aprueba los anuncios para que comiencen a mostrarse.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=flavor_ad&post_status=pending')); ?>" class="button">
                <?php esc_html_e('Revisar Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pool Comunitario -->
    <div class="dm-card dm-card--highlight" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
        <div class="dm-card__header">
            <h3><span class="dashicons dashicons-groups"></span> <?php esc_html_e('Reparto Comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-card__body" style="text-align: center; padding: 20px;">
            <div style="font-size: 2.5em; font-weight: bold; margin-bottom: 10px;">
                <?php echo esc_html(number_format($pool_comunidad, 2)); ?>€
            </div>
            <p style="opacity: 0.9; margin: 0;">
                <?php esc_html_e('Pool acumulado para la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
    </div>

    <div class="dm-grid dm-grid--2">
        <!-- Últimos Anuncios -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Últimos Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=flavor_ad')); ?>" class="dm-card__link">
                    <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <div class="dm-card__body">
                <?php if (!empty($ultimos_anuncios)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_anuncios as $anuncio):
                            $estado_class = '';
                            $estado_label = '';
                            switch ($anuncio->post_status) {
                                case 'publish':
                                    $estado_class = 'success';
                                    $estado_label = __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN);
                                    break;
                                case 'pending':
                                    $estado_class = 'warning';
                                    $estado_label = __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN);
                                    break;
                                case 'draft':
                                    $estado_class = 'secondary';
                                    $estado_label = __('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN);
                                    break;
                            }
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(get_edit_post_link($anuncio->ID)); ?>">
                                    <?php echo esc_html($anuncio->post_title); ?>
                                </a>
                            </td>
                            <td>
                                <span class="dm-badge dm-badge--<?php echo esc_attr($estado_class); ?>">
                                    <?php echo esc_html($estado_label); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($anuncio->post_date))); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="dm-empty"><?php esc_html_e('No hay anuncios registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Anuncios por Rendimiento -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Top Rendimiento (30 días)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body">
                <?php if (!empty($top_anuncios)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Clics', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('CTR', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_anuncios as $top):
                            $ctr = $top->impresiones > 0 ? round(($top->clics / $top->impresiones) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td><?php echo esc_html($top->titulo ?: '#' . $top->anuncio_id); ?></td>
                            <td><strong><?php echo esc_html(number_format($top->clics)); ?></strong></td>
                            <td><?php echo esc_html($ctr); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="dm-empty"><?php esc_html_e('Sin datos de rendimiento disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Gráfico de Evolución -->
    <?php if (!empty($evolucion_semanal)): ?>
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Evolución Última Semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-card__body">
            <canvas id="chart-evolucion-ads" height="100"></canvas>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart === 'undefined') {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = initAdsChart;
            document.head.appendChild(script);
        } else {
            initAdsChart();
        }

        function initAdsChart() {
            var ctx = document.getElementById('chart-evolucion-ads');
            if (!ctx) return;

            var datos = <?php echo wp_json_encode(array_map(function($row) {
                return [
                    'dia' => date_i18n('d/m', strtotime($row->dia)),
                    'impresiones' => (int) $row->impresiones,
                    'clics' => (int) $row->clics,
                    'ingresos' => (float) $row->ingresos
                ];
            }, $evolucion_semanal)); ?>;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: datos.map(function(d) { return d.dia; }),
                    datasets: [
                        {
                            label: '<?php esc_html_e("Impresiones", FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                            data: datos.map(function(d) { return d.impresiones; }),
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: '<?php esc_html_e("Clics", FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                            data: datos.map(function(d) { return d.clics; }),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
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
                            title: { display: true, text: '<?php esc_html_e("Impresiones", FLAVOR_PLATFORM_TEXT_DOMAIN); ?>' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: { display: true, text: '<?php esc_html_e("Clics", FLAVOR_PLATFORM_TEXT_DOMAIN); ?>' },
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });
        }
    });
    </script>
    <?php endif; ?>

    <!-- Métricas Hoy -->
    <div class="dm-grid dm-grid--3">
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body" style="text-align: center;">
                <div style="font-size: 1.5em; font-weight: bold; color: #3b82f6;">
                    <?php echo esc_html(number_format($impresiones_hoy)); ?>
                </div>
                <p style="color: #64748b; margin: 5px 0 0;"><?php esc_html_e('Impresiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>&nbsp;</h3>
            </div>
            <div class="dm-card__body" style="text-align: center;">
                <div style="font-size: 1.5em; font-weight: bold; color: #10b981;">
                    <?php echo esc_html(number_format($clics_hoy)); ?>
                </div>
                <p style="color: #64748b; margin: 5px 0 0;"><?php esc_html_e('Clics', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>&nbsp;</h3>
            </div>
            <div class="dm-card__body" style="text-align: center;">
                <?php $ctr_hoy = $impresiones_hoy > 0 ? round(($clics_hoy / $impresiones_hoy) * 100, 2) : 0; ?>
                <div style="font-size: 1.5em; font-weight: bold; color: #f59e0b;">
                    <?php echo esc_html($ctr_hoy); ?>%
                </div>
                <p style="color: #64748b; margin: 5px 0 0;"><?php esc_html_e('CTR', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    </div>

    <!-- Distribución por Estado -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Distribución de Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-card__body">
            <?php if ($total_anuncios > 0): ?>
            <div class="dm-distribution">
                <div class="dm-distribution__item">
                    <div class="dm-distribution__label">
                        <span class="dm-badge dm-badge--success"><?php esc_html_e('Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span><?php echo esc_html($anuncios_activos); ?></span>
                    </div>
                    <div class="dm-progress">
                        <div class="dm-progress__bar dm-progress__bar--success" style="width: <?php echo esc_attr(($anuncios_activos / $total_anuncios) * 100); ?>%"></div>
                    </div>
                </div>
                <div class="dm-distribution__item">
                    <div class="dm-distribution__label">
                        <span class="dm-badge dm-badge--warning"><?php esc_html_e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span><?php echo esc_html($anuncios_pendientes); ?></span>
                    </div>
                    <div class="dm-progress">
                        <div class="dm-progress__bar dm-progress__bar--warning" style="width: <?php echo esc_attr(($anuncios_pendientes / $total_anuncios) * 100); ?>%"></div>
                    </div>
                </div>
                <div class="dm-distribution__item">
                    <div class="dm-distribution__label">
                        <span class="dm-badge dm-badge--secondary"><?php esc_html_e('Borradores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span><?php echo esc_html($anuncios_borrador); ?></span>
                    </div>
                    <div class="dm-progress">
                        <div class="dm-progress__bar dm-progress__bar--secondary" style="width: <?php echo esc_attr(($anuncios_borrador / $total_anuncios) * 100); ?>%"></div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <p class="dm-empty"><?php esc_html_e('No hay anuncios registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
