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

// Obtener estadísticas del mes actual
global $wpdb;
$tabla_estadisticas = $wpdb->prefix . 'flavor_advertising_stats';
$tabla_anuncios = $wpdb->prefix . 'flavor_advertising_ads';

$fecha_inicio_mes = date('Y-m-01 00:00:00');
$fecha_actual = current_time('mysql');

// Consultar estadísticas
$estadisticas_mes = $wpdb->get_row($wpdb->prepare(
    "SELECT
        SUM(impresiones) as total_impresiones,
        SUM(clicks) as total_clicks,
        SUM(ingresos) as total_ingresos
    FROM {$tabla_estadisticas}
    WHERE fecha >= %s AND fecha <= %s",
    $fecha_inicio_mes,
    $fecha_actual
));

$total_impresiones = $estadisticas_mes->total_impresiones ?? 0;
$total_clicks = $estadisticas_mes->total_clicks ?? 0;
$total_ingresos = $estadisticas_mes->total_ingresos ?? 0;
$ctr_promedio = $total_impresiones > 0 ? ($total_clicks / $total_impresiones) * 100 : 0;

// Obtener ingresos pendientes de pago
$ingresos_pendientes = $wpdb->get_var(
    "SELECT SUM(monto) FROM {$wpdb->prefix}flavor_advertising_payments WHERE estado = 'pending'"
) ?? 0;

// Anuncios activos vs pausados
$anuncios_activos = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_anuncios} WHERE estado = 'activo'");
$anuncios_pausados = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_anuncios} WHERE estado = 'pausado'");

// Top 5 anuncios por rendimiento
$top_anuncios = $wpdb->get_results(
    "SELECT
        a.id,
        a.nombre,
        SUM(s.impresiones) as impresiones,
        SUM(s.clicks) as clicks,
        SUM(s.ingresos) as ingresos,
        CASE
            WHEN SUM(s.impresiones) > 0 THEN (SUM(s.clicks) / SUM(s.impresiones)) * 100
            ELSE 0
        END as ctr
    FROM {$tabla_anuncios} a
    LEFT JOIN {$tabla_estadisticas} s ON a.id = s.anuncio_id
    WHERE s.fecha >= '{$fecha_inicio_mes}'
    GROUP BY a.id
    ORDER BY ingresos DESC
    LIMIT 5"
);

// Datos para gráfica (últimos 30 días)
$datos_grafica = $wpdb->get_results(
    "SELECT
        DATE(fecha) as fecha,
        SUM(impresiones) as impresiones,
        SUM(clicks) as clicks
    FROM {$tabla_estadisticas}
    WHERE fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(fecha)
    ORDER BY fecha ASC"
);

$fechas_grafica = [];
$impresiones_grafica = [];
$clicks_grafica = [];

foreach ($datos_grafica as $dato) {
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
