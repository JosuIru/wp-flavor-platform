<?php
/**
 * Dashboard de Contabilidad
 *
 * Panel administrativo para gestión contable con balance de ingresos/gastos,
 * métricas fiscales y comparativas.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_movimientos = $wpdb->prefix . 'flavor_contabilidad_movimientos';
$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_movimientos'") === $tabla_movimientos;

// Configuración del módulo
$settings = get_option('flavor_module_contabilidad_settings', [
    'simbolo_moneda' => '€',
    'categorias_gasto' => ['compras', 'servicios', 'nominas', 'impuestos', 'alquiler', 'suministros', 'marketing', 'otros'],
    'categorias_ingreso' => ['ventas', 'suscripciones', 'servicios', 'subvenciones', 'donaciones', 'otros'],
]);
$simbolo_moneda = $settings['simbolo_moneda'] ?? '€';

// Fechas de referencia
$primer_dia_mes = date('Y-m-01');
$ultimo_dia_mes = date('Y-m-t');
$primer_dia_anio = date('Y-01-01');
$hoy = date('Y-m-d');
$mes_anterior_inicio = date('Y-m-01', strtotime('-1 month'));
$mes_anterior_fin = date('Y-m-t', strtotime('-1 month'));

// Estadísticas mensuales
$ingresos_mes = 0;
$gastos_mes = 0;
$iva_repercutido_mes = 0;
$iva_soportado_mes = 0;
$total_movimientos_mes = 0;

// Estadísticas anuales
$ingresos_anio = 0;
$gastos_anio = 0;
$iva_repercutido_anio = 0;
$iva_soportado_anio = 0;

// Mes anterior para comparativa
$ingresos_mes_anterior = 0;
$gastos_mes_anterior = 0;

if ($tabla_existe) {
    // Mes actual
    $stats_mes = $wpdb->get_row($wpdb->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN tipo_movimiento = 'ingreso' AND estado = 'confirmado' THEN total ELSE 0 END), 0) as ingresos,
            COALESCE(SUM(CASE WHEN tipo_movimiento = 'gasto' AND estado = 'confirmado' THEN total ELSE 0 END), 0) as gastos,
            COALESCE(SUM(CASE WHEN tipo_movimiento = 'ingreso' AND estado = 'confirmado' THEN iva_importe ELSE 0 END), 0) as iva_repercutido,
            COALESCE(SUM(CASE WHEN tipo_movimiento = 'gasto' AND estado = 'confirmado' THEN iva_importe ELSE 0 END), 0) as iva_soportado,
            COUNT(*) as total
        FROM $tabla_movimientos
        WHERE fecha_movimiento >= %s AND fecha_movimiento <= %s",
        $primer_dia_mes,
        $ultimo_dia_mes
    ));

    if ($stats_mes) {
        $ingresos_mes = (float) $stats_mes->ingresos;
        $gastos_mes = (float) $stats_mes->gastos;
        $iva_repercutido_mes = (float) $stats_mes->iva_repercutido;
        $iva_soportado_mes = (float) $stats_mes->iva_soportado;
        $total_movimientos_mes = (int) $stats_mes->total;
    }

    // Año actual
    $stats_anio = $wpdb->get_row($wpdb->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN tipo_movimiento = 'ingreso' AND estado = 'confirmado' THEN total ELSE 0 END), 0) as ingresos,
            COALESCE(SUM(CASE WHEN tipo_movimiento = 'gasto' AND estado = 'confirmado' THEN total ELSE 0 END), 0) as gastos,
            COALESCE(SUM(CASE WHEN tipo_movimiento = 'ingreso' AND estado = 'confirmado' THEN iva_importe ELSE 0 END), 0) as iva_repercutido,
            COALESCE(SUM(CASE WHEN tipo_movimiento = 'gasto' AND estado = 'confirmado' THEN iva_importe ELSE 0 END), 0) as iva_soportado
        FROM $tabla_movimientos
        WHERE fecha_movimiento >= %s AND estado = 'confirmado'",
        $primer_dia_anio
    ));

    if ($stats_anio) {
        $ingresos_anio = (float) $stats_anio->ingresos;
        $gastos_anio = (float) $stats_anio->gastos;
        $iva_repercutido_anio = (float) $stats_anio->iva_repercutido;
        $iva_soportado_anio = (float) $stats_anio->iva_soportado;
    }

    // Mes anterior
    $stats_anterior = $wpdb->get_row($wpdb->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN tipo_movimiento = 'ingreso' AND estado = 'confirmado' THEN total ELSE 0 END), 0) as ingresos,
            COALESCE(SUM(CASE WHEN tipo_movimiento = 'gasto' AND estado = 'confirmado' THEN total ELSE 0 END), 0) as gastos
        FROM $tabla_movimientos
        WHERE fecha_movimiento >= %s AND fecha_movimiento <= %s",
        $mes_anterior_inicio,
        $mes_anterior_fin
    ));

    if ($stats_anterior) {
        $ingresos_mes_anterior = (float) $stats_anterior->ingresos;
        $gastos_mes_anterior = (float) $stats_anterior->gastos;
    }
}

$balance_mes = $ingresos_mes - $gastos_mes;
$balance_anio = $ingresos_anio - $gastos_anio;
$iva_liquidar = $iva_repercutido_mes - $iva_soportado_mes;

// Variación respecto al mes anterior
$variacion_ingresos = $ingresos_mes_anterior > 0
    ? round((($ingresos_mes - $ingresos_mes_anterior) / $ingresos_mes_anterior) * 100, 1)
    : 0;
$variacion_gastos = $gastos_mes_anterior > 0
    ? round((($gastos_mes - $gastos_mes_anterior) / $gastos_mes_anterior) * 100, 1)
    : 0;

// Últimos movimientos
$ultimos_movimientos = [];
if ($tabla_existe) {
    $ultimos_movimientos = $wpdb->get_results(
        "SELECT id, fecha_movimiento, tipo_movimiento, concepto, categoria, total, estado
         FROM $tabla_movimientos
         ORDER BY fecha_movimiento DESC, id DESC
         LIMIT 10"
    );
}

// Distribución por categoría (gastos del mes)
$gastos_por_categoria = [];
if ($tabla_existe) {
    $gastos_por_categoria = $wpdb->get_results($wpdb->prepare(
        "SELECT categoria, SUM(total) as total
         FROM $tabla_movimientos
         WHERE tipo_movimiento = 'gasto' AND estado = 'confirmado'
         AND fecha_movimiento >= %s AND fecha_movimiento <= %s
         GROUP BY categoria
         ORDER BY total DESC
         LIMIT 6",
        $primer_dia_mes,
        $ultimo_dia_mes
    ));
}

// Evolución mensual (últimos 6 meses)
$evolucion_mensual = [];
if ($tabla_existe) {
    $evolucion_mensual = $wpdb->get_results(
        "SELECT
            DATE_FORMAT(fecha_movimiento, '%Y-%m') as mes,
            COALESCE(SUM(CASE WHEN tipo_movimiento = 'ingreso' AND estado = 'confirmado' THEN total ELSE 0 END), 0) as ingresos,
            COALESCE(SUM(CASE WHEN tipo_movimiento = 'gasto' AND estado = 'confirmado' THEN total ELSE 0 END), 0) as gastos
        FROM $tabla_movimientos
        WHERE fecha_movimiento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha_movimiento, '%Y-%m')
        ORDER BY mes ASC"
    );
}

// Movimientos pendientes (borradores)
$movimientos_borrador = 0;
if ($tabla_existe) {
    $movimientos_borrador = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_movimientos WHERE estado = 'borrador'");
}

// Top orígenes de ingresos
$top_ingresos = [];
if ($tabla_existe) {
    $top_ingresos = $wpdb->get_results($wpdb->prepare(
        "SELECT modulo_origen, SUM(total) as total
         FROM $tabla_movimientos
         WHERE tipo_movimiento = 'ingreso' AND estado = 'confirmado'
         AND fecha_movimiento >= %s
         GROUP BY modulo_origen
         ORDER BY total DESC
         LIMIT 5",
        $primer_dia_anio
    ));
}
?>

<div class="wrap dm-dashboard">
    <?php flavor_dashboard_help('contabilidad'); ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-chart-pie" style="color: #0ea5e9;"></span>
            <h1><?php esc_html_e('Contabilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-nuevo')); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Nuevo Movimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-card">
        <div class="dm-action-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-list-view"></span>
                <span><?php esc_html_e('Movimientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-informes')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-analytics"></span>
                <span><?php esc_html_e('Informes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos&tipo=ingreso')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-arrow-down-alt"></span>
                <span><?php esc_html_e('Ingresos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos&tipo=gasto')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-arrow-up-alt"></span>
                <span><?php esc_html_e('Gastos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>
    </div>

    <!-- Balance del Mes Destacado -->
    <div class="dm-card dm-card--highlight" style="background: linear-gradient(135deg, <?php echo $balance_mes >= 0 ? '#10b981' : '#ef4444'; ?> 0%, <?php echo $balance_mes >= 0 ? '#059669' : '#dc2626'; ?> 100%); color: white;">
        <div class="dm-card__header">
            <h3><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e('Balance del Mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-card__body" style="text-align: center; padding: 20px;">
            <div style="font-size: 2.5em; font-weight: bold; margin-bottom: 10px;">
                <?php echo $balance_mes >= 0 ? '+' : ''; ?><?php echo esc_html(number_format($balance_mes, 2)); ?><?php echo esc_html($simbolo_moneda); ?>
            </div>
            <p style="opacity: 0.9; margin: 0;">
                <?php printf(
                    esc_html__('Ingresos: %s | Gastos: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    number_format($ingresos_mes, 2) . $simbolo_moneda,
                    number_format($gastos_mes, 2) . $simbolo_moneda
                ); ?>
            </p>
        </div>
    </div>

    <!-- Estadísticas Principales -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-arrow-down-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html(number_format($ingresos_mes, 2)); ?><?php echo esc_html($simbolo_moneda); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Ingresos (mes)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php if ($variacion_ingresos != 0): ?>
                <span class="dm-stat-card__meta" style="color: <?php echo $variacion_ingresos >= 0 ? '#10b981' : '#ef4444'; ?>">
                    <?php echo $variacion_ingresos >= 0 ? '↑' : '↓'; ?> <?php echo esc_html(abs($variacion_ingresos)); ?>% vs mes anterior
                </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--danger">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-arrow-up-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html(number_format($gastos_mes, 2)); ?><?php echo esc_html($simbolo_moneda); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Gastos (mes)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php if ($variacion_gastos != 0): ?>
                <span class="dm-stat-card__meta" style="color: <?php echo $variacion_gastos <= 0 ? '#10b981' : '#ef4444'; ?>">
                    <?php echo $variacion_gastos >= 0 ? '↑' : '↓'; ?> <?php echo esc_html(abs($variacion_gastos)); ?>% vs mes anterior
                </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-calculator"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html(number_format($iva_liquidar, 2)); ?><?php echo esc_html($simbolo_moneda); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('IVA a liquidar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="dm-stat-card__meta">
                    Rep: <?php echo esc_html(number_format($iva_repercutido_mes, 2)); ?> | Sop: <?php echo esc_html(number_format($iva_soportado_mes, 2)); ?>
                </span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html(number_format($balance_anio, 2)); ?><?php echo esc_html($simbolo_moneda); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Balance Anual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <?php if ($movimientos_borrador > 0): ?>
    <!-- Alerta de borradores -->
    <div class="dm-alert dm-alert--warning">
        <span class="dashicons dashicons-edit"></span>
        <div class="dm-alert__content">
            <strong><?php echo esc_html($movimientos_borrador); ?> <?php esc_html_e('movimientos en borrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
            <p><?php esc_html_e('Revisa y confirma los movimientos pendientes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos&estado=borrador')); ?>" class="button">
                <?php esc_html_e('Ver Borradores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="dm-grid dm-grid--2">
        <!-- Últimos Movimientos -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Últimos Movimientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=contabilidad-movimientos')); ?>" class="dm-card__link">
                    <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <div class="dm-card__body">
                <?php if (!empty($ultimos_movimientos)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Concepto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="text-align: right;"><?php esc_html_e('Importe', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_movimientos as $mov):
                            $es_ingreso = $mov->tipo_movimiento === 'ingreso';
                            $color = $es_ingreso ? '#10b981' : '#ef4444';
                            $signo = $es_ingreso ? '+' : '-';
                        ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n('d/m', strtotime($mov->fecha_movimiento))); ?></td>
                            <td>
                                <?php echo esc_html(mb_strimwidth($mov->concepto, 0, 30, '...')); ?>
                                <?php if ($mov->estado === 'borrador'): ?>
                                <span class="dm-badge dm-badge--secondary"><?php esc_html_e('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; color: <?php echo esc_attr($color); ?>; font-weight: bold;">
                                <?php echo esc_html($signo . number_format($mov->total, 2) . $simbolo_moneda); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="dm-empty"><?php esc_html_e('No hay movimientos registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Distribución de Gastos -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Gastos por Categoría (mes)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body">
                <?php if (!empty($gastos_por_categoria) && $gastos_mes > 0): ?>
                <div class="dm-distribution">
                    <?php
                    $colores = ['danger', 'warning', 'info', 'primary', 'success', 'secondary'];
                    $indice_color = 0;
                    foreach ($gastos_por_categoria as $cat):
                        $porcentaje = ($cat->total / $gastos_mes) * 100;
                        $color = $colores[$indice_color % count($colores)];
                        $indice_color++;
                    ?>
                    <div class="dm-distribution__item">
                        <div class="dm-distribution__label">
                            <span><?php echo esc_html(ucfirst($cat->categoria ?: __('Sin categoría', FLAVOR_PLATFORM_TEXT_DOMAIN))); ?></span>
                            <span><?php echo esc_html(number_format($cat->total, 2) . $simbolo_moneda); ?></span>
                        </div>
                        <div class="dm-progress">
                            <div class="dm-progress__bar dm-progress__bar--<?php echo esc_attr($color); ?>" style="width: <?php echo esc_attr($porcentaje); ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="dm-empty"><?php esc_html_e('Sin gastos este mes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Gráfico de Evolución -->
    <?php if (!empty($evolucion_mensual)): ?>
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Evolución Últimos 6 Meses', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-card__body">
            <canvas id="chart-evolucion-contabilidad" height="100"></canvas>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart === 'undefined') {
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = initContabilidadChart;
            document.head.appendChild(script);
        } else {
            initContabilidadChart();
        }

        function initContabilidadChart() {
            var ctx = document.getElementById('chart-evolucion-contabilidad');
            if (!ctx) return;

            var datos = <?php echo wp_json_encode(array_map(function($row) {
                $meses = ['01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr', '05' => 'May', '06' => 'Jun',
                          '07' => 'Jul', '08' => 'Ago', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'];
                $mes_num = substr($row->mes, 5, 2);
                return [
                    'mes' => $meses[$mes_num] ?? $row->mes,
                    'ingresos' => (float) $row->ingresos,
                    'gastos' => (float) $row->gastos
                ];
            }, $evolucion_mensual)); ?>;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: datos.map(function(d) { return d.mes; }),
                    datasets: [
                        {
                            label: '<?php esc_html_e("Ingresos", FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                            data: datos.map(function(d) { return d.ingresos; }),
                            backgroundColor: 'rgba(16, 185, 129, 0.8)',
                            borderColor: '#10b981',
                            borderWidth: 1
                        },
                        {
                            label: '<?php esc_html_e("Gastos", FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                            data: datos.map(function(d) { return d.gastos; }),
                            backgroundColor: 'rgba(239, 68, 68, 0.8)',
                            borderColor: '#ef4444',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString() + '<?php echo esc_js($simbolo_moneda); ?>';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
    </script>
    <?php endif; ?>

    <div class="dm-grid dm-grid--2">
        <!-- Orígenes de Ingresos -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Orígenes de Ingresos (año)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body">
                <?php if (!empty($top_ingresos)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="text-align: right;"><?php esc_html_e('Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_ingresos as $origen): ?>
                        <tr>
                            <td><?php echo esc_html(ucfirst($origen->modulo_origen ?: __('Manual', FLAVOR_PLATFORM_TEXT_DOMAIN))); ?></td>
                            <td style="text-align: right; font-weight: bold; color: #10b981;">
                                <?php echo esc_html(number_format($origen->total, 2) . $simbolo_moneda); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="dm-empty"><?php esc_html_e('Sin datos de orígenes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resumen Fiscal -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-portfolio"></span> <?php esc_html_e('Resumen Fiscal Anual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body">
                <table class="dm-table">
                    <tbody>
                        <tr>
                            <td><?php esc_html_e('Base Imponible Ingresos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td style="text-align: right; font-weight: bold;">
                                <?php echo esc_html(number_format($ingresos_anio - $iva_repercutido_anio, 2) . $simbolo_moneda); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('IVA Repercutido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td style="text-align: right;">
                                <?php echo esc_html(number_format($iva_repercutido_anio, 2) . $simbolo_moneda); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Base Imponible Gastos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td style="text-align: right; font-weight: bold;">
                                <?php echo esc_html(number_format($gastos_anio - $iva_soportado_anio, 2) . $simbolo_moneda); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('IVA Soportado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                            <td style="text-align: right;">
                                <?php echo esc_html(number_format($iva_soportado_anio, 2) . $simbolo_moneda); ?>
                            </td>
                        </tr>
                        <tr style="border-top: 2px solid #e2e8f0;">
                            <td><strong><?php esc_html_e('Resultado Fiscal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                            <td style="text-align: right; font-weight: bold; color: <?php echo $balance_anio >= 0 ? '#10b981' : '#ef4444'; ?>">
                                <?php echo esc_html(number_format($balance_anio, 2) . $simbolo_moneda); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Métricas Adicionales -->
    <div class="dm-grid dm-grid--3">
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Movimientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body" style="text-align: center;">
                <div style="font-size: 2em; font-weight: bold; color: #0ea5e9;">
                    <?php echo esc_html($total_movimientos_mes); ?>
                </div>
                <p style="color: #64748b; margin: 5px 0 0;"><?php esc_html_e('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>

        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Ticket Medio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body" style="text-align: center;">
                <?php
                $ingresos_count = 0;
                if ($tabla_existe) {
                    $ingresos_count = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $tabla_movimientos
                         WHERE tipo_movimiento = 'ingreso' AND estado = 'confirmado'
                         AND fecha_movimiento >= %s AND fecha_movimiento <= %s",
                        $primer_dia_mes,
                        $ultimo_dia_mes
                    ));
                }
                $ticket_medio = $ingresos_count > 0 ? $ingresos_mes / $ingresos_count : 0;
                ?>
                <div style="font-size: 2em; font-weight: bold; color: #10b981;">
                    <?php echo esc_html(number_format($ticket_medio, 2) . $simbolo_moneda); ?>
                </div>
                <p style="color: #64748b; margin: 5px 0 0;"><?php esc_html_e('Por ingreso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>

        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Margen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body" style="text-align: center;">
                <?php
                $margen = $ingresos_mes > 0 ? round(($balance_mes / $ingresos_mes) * 100, 1) : 0;
                ?>
                <div style="font-size: 2em; font-weight: bold; color: <?php echo $margen >= 0 ? '#10b981' : '#ef4444'; ?>;">
                    <?php echo esc_html($margen); ?>%
                </div>
                <p style="color: #64748b; margin: 5px 0 0;"><?php esc_html_e('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    </div>
</div>
