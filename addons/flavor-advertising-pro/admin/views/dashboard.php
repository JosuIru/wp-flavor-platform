<?php
/**
 * Vista del Dashboard Principal de Publicidad
 *
 * @package FlavorPlatform
 * @subpackage Advertising
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
}

// Obtener estadísticas del mes actual.
// Esquema real: tabla flavor_ad_stats (ad_id, date, impressions, clicks, revenue),
// tabla flavor_ad_transactions (ad_id, amount, status) y los anuncios son posts del
// CPT flavor_ad (no existe ninguna tabla flavor_advertising_*).
global $wpdb;
$tabla_estadisticas = $wpdb->prefix . 'flavor_ad_stats';
$tabla_transacciones = $wpdb->prefix . 'flavor_ad_transactions';

$fecha_inicio_mes = date('Y-m-01');
$fecha_fin_mes = date('Y-m-t');

// Consultar estadísticas del mes
$estadisticas_mes = $wpdb->get_row($wpdb->prepare(
    "SELECT
        SUM(impressions) as total_impresiones,
        SUM(clicks) as total_clicks,
        SUM(revenue) as total_ingresos
    FROM {$tabla_estadisticas}
    WHERE date >= %s AND date <= %s",
    $fecha_inicio_mes,
    $fecha_fin_mes
));

$total_impresiones = $estadisticas_mes->total_impresiones ?? 0;
$total_clicks = $estadisticas_mes->total_clicks ?? 0;
$total_ingresos = $estadisticas_mes->total_ingresos ?? 0;
$ctr_promedio = $total_impresiones > 0 ? ($total_clicks / $total_impresiones) * 100 : 0;

// Obtener ingresos pendientes de pago
$ingresos_pendientes = $wpdb->get_var(
    "SELECT SUM(amount) FROM {$tabla_transacciones} WHERE status = 'pending'"
) ?? 0;

// Anuncios activos (publicados) vs pausados (borrador/pendiente) sobre el CPT flavor_ad
$conteo_anuncios = wp_count_posts('flavor_ad');
$anuncios_activos = (int) ($conteo_anuncios->publish ?? 0);
$anuncios_pausados = (int) (($conteo_anuncios->draft ?? 0) + ($conteo_anuncios->pending ?? 0));

// Top 5 anuncios por rendimiento (el nombre se resuelve desde el post)
$filas_top_anuncios = $wpdb->get_results($wpdb->prepare(
    "SELECT
        ad_id,
        SUM(impressions) as impresiones,
        SUM(clicks) as clicks,
        SUM(revenue) as ingresos,
        CASE
            WHEN SUM(impressions) > 0 THEN (SUM(clicks) / SUM(impressions)) * 100
            ELSE 0
        END as ctr
    FROM {$tabla_estadisticas}
    WHERE date >= %s
    GROUP BY ad_id
    ORDER BY ingresos DESC
    LIMIT 5",
    $fecha_inicio_mes
));

$top_anuncios = [];
foreach ((array) $filas_top_anuncios as $fila) {
    $titulo = get_the_title($fila->ad_id);
    $fila->nombre = $titulo !== '' ? $titulo : sprintf(__('Anuncio #%d', FLAVOR_PLATFORM_TEXT_DOMAIN), (int) $fila->ad_id);
    $top_anuncios[] = $fila;
}

// Datos para gráfica (últimos 30 días)
$datos_grafica = $wpdb->get_results(
    "SELECT
        date as fecha,
        SUM(impressions) as impresiones,
        SUM(clicks) as clicks
    FROM {$tabla_estadisticas}
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY date
    ORDER BY date ASC"
);

$fechas_grafica = [];
$impresiones_grafica = [];
$clicks_grafica = [];

foreach ((array) $datos_grafica as $dato) {
    $fechas_grafica[] = date('d/m', strtotime($dato->fecha));
    $impresiones_grafica[] = (int) $dato->impresiones;
    $clicks_grafica[] = (int) $dato->clicks;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('Dashboard de Publicidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

    <!-- Estadísticas principales -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">

        <!-- Total Impresiones -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Total Impresiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($total_impresiones, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <!-- Total Clicks -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Total Clicks', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html(number_format($total_clicks, 0, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <!-- CTR Promedio -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('CTR Promedio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #00a32a;">
                <?php echo esc_html(number_format($ctr_promedio, 2)); ?>%
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <!-- Ingresos Totales -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Ingresos Totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #00a32a;">
                €<?php echo esc_html(number_format($total_ingresos, 2, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <!-- Ingresos Pendientes -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Pendientes de Pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #d63638;">
                €<?php echo esc_html(number_format($ingresos_pendientes, 2, ',', '.')); ?>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Acumulado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <!-- Anuncios Activos -->
        <div class="card" style="padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase;">
                <?php esc_html_e('Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p style="margin: 0; font-size: 24px; font-weight: bold;">
                <span style="color: #00a32a;"><?php echo esc_html($anuncios_activos); ?></span>
                <span style="color: #666; font-size: 18px;">/</span>
                <span style="color: #d63638;"><?php echo esc_html($anuncios_pausados); ?></span>
            </p>
            <p class="description" style="margin: 10px 0 0 0;">
                <?php esc_html_e('Activos / Pausados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

    </div>

    <!-- Gráfica de rendimiento -->
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2><?php esc_html_e('Rendimiento - Últimos 30 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <canvas id="flavor-performance-chart" style="max-height: 300px;"></canvas>
    </div>

    <!-- Top 5 Anuncios -->
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2><?php esc_html_e('Top 5 Anuncios por Rendimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Impresiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Clicks', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('CTR', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Ingresos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($top_anuncios)) : ?>
                    <?php foreach ($top_anuncios as $anuncio) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($anuncio->nombre); ?></strong></td>
                            <td><?php echo esc_html(number_format($anuncio->impresiones, 0, ',', '.')); ?></td>
                            <td><?php echo esc_html(number_format($anuncio->clicks, 0, ',', '.')); ?></td>
                            <td><?php echo esc_html(number_format($anuncio->ctr, 2)); ?>%</td>
                            <td><strong>€<?php echo esc_html(number_format($anuncio->ingresos, 2, ',', '.')); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">
                            <?php esc_html_e('No hay datos disponibles para este período.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Acciones rápidas -->
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2><?php esc_html_e('Acciones Rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-advertising-create')); ?>" class="button button-primary button-large">
                <?php esc_html_e('Crear Nuevo Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-advertising-advertisers')); ?>" class="button button-large">
                <?php esc_html_e('Ver Anunciantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-advertising-payments')); ?>" class="button button-large">
                <?php esc_html_e('Gestionar Pagos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-advertising-network')); ?>" class="button button-large">
                <?php esc_html_e('Red Global', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
    // Datos para la gráfica
    const fechas = <?php echo json_encode($fechas_grafica); ?>;
    const impresiones = <?php echo json_encode($impresiones_grafica); ?>;
    const clicks = <?php echo json_encode($clicks_grafica); ?>;

    // Crear gráfica
    const ctx = document.getElementById('flavor-performance-chart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: fechas,
                datasets: [
                    {
                        label: '<?php esc_html_e('Impresiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                        data: impresiones,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: '<?php esc_html_e('Clicks', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                        data: clicks,
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>
