<?php
/**
 * Dashboard de Facturas
 *
 * Panel administrativo para gestión de facturación con métricas,
 * alertas y accesos rápidos.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_facturas = $wpdb->prefix . 'flavor_facturas';
$tabla_lineas = $wpdb->prefix . 'flavor_facturas_lineas';
$tabla_pagos = $wpdb->prefix . 'flavor_facturas_pagos';

$tabla_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_facturas)) === $tabla_facturas;

if (!$tabla_existe) {
    echo '<div class="wrap"><div class="dm-alert dm-alert--warning"><span class="dashicons dashicons-warning"></span>'
        . esc_html__('La tabla de facturas no está disponible. Activa el módulo para crear las tablas.', 'flavor-platform')
        . '</div></div>';
    return;
}

$ahora = current_time('mysql');
$inicio_mes = gmdate('Y-m-01 00:00:00', current_time('timestamp', true));
$inicio_anio = gmdate('Y-01-01 00:00:00', current_time('timestamp', true));

$total_facturas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_facturas}");
$facturas_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_facturas} WHERE estado = 'pendiente'");
$facturas_pagadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_facturas} WHERE estado = 'pagada'");
$facturas_vencidas = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_facturas} WHERE estado = 'pendiente' AND fecha_vencimiento < %s",
    $ahora
));

$total_facturado = (float) $wpdb->get_var("SELECT COALESCE(SUM(total), 0) FROM {$tabla_facturas} WHERE estado != 'anulada'");
$total_pendiente = (float) $wpdb->get_var("SELECT COALESCE(SUM(total), 0) FROM {$tabla_facturas} WHERE estado = 'pendiente'");
$total_cobrado = (float) $wpdb->get_var("SELECT COALESCE(SUM(total), 0) FROM {$tabla_facturas} WHERE estado = 'pagada'");
$importe_vencido = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(total), 0) FROM {$tabla_facturas} WHERE estado = 'pendiente' AND fecha_vencimiento < %s",
    $ahora
));

$facturas_mes = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_facturas} WHERE created_at >= %s", $inicio_mes
));
$facturado_mes = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(total), 0) FROM {$tabla_facturas} WHERE estado != 'anulada' AND created_at >= %s", $inicio_mes
));
$facturado_anio = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(total), 0) FROM {$tabla_facturas} WHERE estado != 'anulada' AND created_at >= %s", $inicio_anio
));
$iva_repercutido = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(total_iva), 0) FROM {$tabla_facturas} WHERE estado != 'anulada' AND created_at >= %s", $inicio_anio
));

$ultimas_facturas = $wpdb->get_results("SELECT * FROM {$tabla_facturas} ORDER BY created_at DESC LIMIT 8");

$en_7_dias = gmdate('Y-m-d', strtotime('+7 days', current_time('timestamp', true)));
$proximas_vencer = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$tabla_facturas} WHERE estado = 'pendiente' AND fecha_vencimiento BETWEEN %s AND %s ORDER BY fecha_vencimiento ASC LIMIT 5",
    $ahora, $en_7_dias
));

$evolucion_mensual = $wpdb->get_results(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as periodo, COUNT(*) as cantidad,
            SUM(CASE WHEN estado != 'anulada' THEN total ELSE 0 END) as facturado,
            SUM(CASE WHEN estado = 'pagada' THEN total ELSE 0 END) as cobrado
     FROM {$tabla_facturas} WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY periodo ASC"
);

$top_clientes = $wpdb->get_results(
    "SELECT cliente_nombre, cliente_email, COUNT(*) as num_facturas, SUM(total) as total_facturado
     FROM {$tabla_facturas} WHERE estado != 'anulada' GROUP BY cliente_email ORDER BY total_facturado DESC LIMIT 5"
);

$por_estado = $wpdb->get_results(
    "SELECT estado, COUNT(*) as total, SUM(total) as importe FROM {$tabla_facturas} GROUP BY estado ORDER BY total DESC"
);

$estado_labels = [
    'borrador' => __('Borrador', 'flavor-platform'),
    'pendiente' => __('Pendiente', 'flavor-platform'),
    'pagada' => __('Pagada', 'flavor-platform'),
    'vencida' => __('Vencida', 'flavor-platform'),
    'anulada' => __('Anulada', 'flavor-platform'),
];
$estado_badges = [
    'borrador' => 'dm-badge--secondary',
    'pendiente' => 'dm-badge--warning',
    'pagada' => 'dm-badge--success',
    'vencida' => 'dm-badge--error',
    'anulada' => 'dm-badge--secondary',
];
?>

<div class="wrap dm-dashboard">
    <?php if (function_exists('flavor_dashboard_help')) { flavor_dashboard_help('facturas'); } ?>

    <div class="dm-header">
        <div class="dm-header__content">
            <h1 class="dm-header__title">
                <span class="dashicons dashicons-media-spreadsheet"></span>
                <?php esc_html_e('Dashboard de Facturación', 'flavor-platform'); ?>
            </h1>
            <p class="dm-header__description"><?php esc_html_e('Control de facturación, cobros pendientes y métricas financieras.', 'flavor-platform'); ?></p>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=facturas-nueva')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nueva factura', 'flavor-platform'); ?>
            </a>
        </div>
    </div>

    <div class="dm-card">
        <h2 class="dm-card__title"><span class="dashicons dashicons-admin-links"></span> <?php esc_html_e('Accesos Rápidos', 'flavor-platform'); ?></h2>
        <div class="dm-action-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=facturas-listado')); ?>" class="dm-action-card">
                <span class="dashicons dashicons-list-view dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Todas', 'flavor-platform'); ?></span>
                <span class="dm-badge"><?php echo number_format_i18n($total_facturas); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=facturas-listado&estado=pendiente')); ?>" class="dm-action-card dm-action-card--warning">
                <span class="dashicons dashicons-clock dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Pendientes', 'flavor-platform'); ?></span>
                <?php if ($facturas_pendientes > 0): ?><span class="dm-badge dm-badge--warning"><?php echo number_format_i18n($facturas_pendientes); ?></span><?php endif; ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=facturas-listado&estado=vencida')); ?>" class="dm-action-card dm-action-card--error">
                <span class="dashicons dashicons-warning dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Vencidas', 'flavor-platform'); ?></span>
                <?php if ($facturas_vencidas > 0): ?><span class="dm-badge dm-badge--error"><?php echo number_format_i18n($facturas_vencidas); ?></span><?php endif; ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=facturas-config')); ?>" class="dm-action-card">
                <span class="dashicons dashicons-admin-settings dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Configuración', 'flavor-platform'); ?></span>
            </a>
        </div>
    </div>

    <div class="dm-stats-grid">
        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-chart-line"></span></div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($facturado_mes, 2); ?> €</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Facturado este mes', 'flavor-platform'); ?></div>
                <small class="dm-text-muted"><?php printf(esc_html__('%s facturas', 'flavor-platform'), number_format_i18n($facturas_mes)); ?></small>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-clock"></span></div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_pendiente, 2); ?> €</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Pendiente de cobro', 'flavor-platform'); ?></div>
                <small class="dm-text-muted"><?php printf(esc_html__('%s facturas', 'flavor-platform'), number_format_i18n($facturas_pendientes)); ?></small>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--error">
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-warning"></span></div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($importe_vencido, 2); ?> €</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Importe vencido', 'flavor-platform'); ?></div>
                <small class="dm-text-muted"><?php printf(esc_html__('%s facturas', 'flavor-platform'), number_format_i18n($facturas_vencidas)); ?></small>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-chart-bar"></span></div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($facturado_anio, 2); ?> €</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Facturado este año', 'flavor-platform'); ?></div>
                <small class="dm-text-muted"><?php printf(esc_html__('IVA: %s €', 'flavor-platform'), number_format_i18n($iva_repercutido, 2)); ?></small>
            </div>
        </div>
    </div>

    <?php if ($facturas_vencidas > 0 || count($proximas_vencer) > 0): ?>
    <div class="dm-alert dm-alert--warning">
        <span class="dashicons dashicons-warning"></span>
        <div>
            <strong><?php esc_html_e('Alertas de facturación', 'flavor-platform'); ?></strong>
            <ul style="margin: 8px 0 0; padding-left: 20px;">
                <?php if ($facturas_vencidas > 0): ?>
                    <li><?php printf(esc_html__('%s facturas vencidas por importe de %s €', 'flavor-platform'), number_format_i18n($facturas_vencidas), number_format_i18n($importe_vencido, 2)); ?></li>
                <?php endif; ?>
                <?php if (count($proximas_vencer) > 0): ?>
                    <li><?php printf(esc_html__('%s facturas vencen en los próximos 7 días', 'flavor-platform'), count($proximas_vencer)); ?></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <div class="dm-grid dm-grid--2">
        <div class="dm-card">
            <h3 class="dm-card__title"><span class="dashicons dashicons-media-spreadsheet"></span> <?php esc_html_e('Últimas facturas', 'flavor-platform'); ?></h3>
            <?php if (empty($ultimas_facturas)): ?>
                <p class="dm-text-muted"><?php esc_html_e('No hay facturas registradas.', 'flavor-platform'); ?></p>
            <?php else: ?>
                <table class="dm-table dm-table--compact">
                    <thead><tr><th><?php esc_html_e('Número', 'flavor-platform'); ?></th><th><?php esc_html_e('Cliente', 'flavor-platform'); ?></th><th><?php esc_html_e('Total', 'flavor-platform'); ?></th><th><?php esc_html_e('Estado', 'flavor-platform'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($ultimas_facturas as $factura): ?>
                            <tr>
                                <td><a href="<?php echo esc_url(admin_url('admin.php?page=facturas-ver&id=' . $factura->id)); ?>"><?php echo esc_html($factura->numero); ?></a></td>
                                <td><?php echo esc_html($factura->cliente_nombre ?: '-'); ?></td>
                                <td><strong><?php echo number_format_i18n($factura->total, 2); ?> €</strong></td>
                                <td><span class="dm-badge <?php echo esc_attr($estado_badges[$factura->estado] ?? 'dm-badge--secondary'); ?>"><?php echo esc_html($estado_labels[$factura->estado] ?? $factura->estado); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top: 12px;"><a href="<?php echo esc_url(admin_url('admin.php?page=facturas-listado')); ?>" class="dm-link"><?php esc_html_e('Ver todas las facturas', 'flavor-platform'); ?> →</a></p>
            <?php endif; ?>
        </div>

        <div class="dm-card">
            <h3 class="dm-card__title"><span class="dashicons dashicons-businessman"></span> <?php esc_html_e('Mejores clientes', 'flavor-platform'); ?></h3>
            <?php if (empty($top_clientes)): ?>
                <p class="dm-text-muted"><?php esc_html_e('No hay datos de clientes.', 'flavor-platform'); ?></p>
            <?php else: ?>
                <table class="dm-table dm-table--compact">
                    <thead><tr><th><?php esc_html_e('Cliente', 'flavor-platform'); ?></th><th><?php esc_html_e('Facturas', 'flavor-platform'); ?></th><th><?php esc_html_e('Total', 'flavor-platform'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($top_clientes as $cliente): ?>
                            <tr>
                                <td><strong><?php echo esc_html($cliente->cliente_nombre ?: 'Sin nombre'); ?></strong><?php if ($cliente->cliente_email): ?><br><small class="dm-text-muted"><?php echo esc_html($cliente->cliente_email); ?></small><?php endif; ?></td>
                                <td><?php echo number_format_i18n($cliente->num_facturas); ?></td>
                                <td><strong><?php echo number_format_i18n($cliente->total_facturado, 2); ?> €</strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($evolucion_mensual)): ?>
    <div class="dm-card">
        <h3 class="dm-card__title"><span class="dashicons dashicons-chart-area"></span> <?php esc_html_e('Evolución mensual', 'flavor-platform'); ?></h3>
        <div class="dm-chart-container" style="height: 300px;"><canvas id="chart-evolucion-facturas"></canvas></div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart === 'undefined') return;
        const ctx = document.getElementById('chart-evolucion-facturas');
        if (!ctx) return;
        const data = <?php echo wp_json_encode(array_map(function($item) {
            return ['periodo' => $item->periodo, 'facturado' => (float) $item->facturado, 'cobrado' => (float) $item->cobrado];
        }, $evolucion_mensual)); ?>;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.periodo),
                datasets: [
                    { label: '<?php esc_html_e('Facturado', 'flavor-platform'); ?>', data: data.map(d => d.facturado), backgroundColor: 'rgba(59, 130, 246, 0.8)', borderRadius: 4 },
                    { label: '<?php esc_html_e('Cobrado', 'flavor-platform'); ?>', data: data.map(d => d.cobrado), backgroundColor: 'rgba(34, 197, 94, 0.8)', borderRadius: 4 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return value.toLocaleString('es-ES') + ' €'; } } } } }
        });
    });
    </script>
    <?php endif; ?>

    <div class="dm-grid dm-grid--2">
        <div class="dm-card">
            <h3 class="dm-card__title"><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e('Distribución por estado', 'flavor-platform'); ?></h3>
            <?php if (!empty($por_estado)): $total_importe = array_sum(array_column($por_estado, 'importe')); ?>
                <div class="dm-progress-list">
                    <?php foreach ($por_estado as $estado): $porcentaje = $total_importe > 0 ? ($estado->importe / $total_importe) * 100 : 0; ?>
                        <div class="dm-progress-item">
                            <div class="dm-progress-item__header">
                                <span class="dm-badge <?php echo esc_attr($estado_badges[$estado->estado] ?? 'dm-badge--secondary'); ?>"><?php echo esc_html($estado_labels[$estado->estado] ?? $estado->estado); ?></span>
                                <span><?php echo number_format_i18n($estado->total); ?> facturas</span>
                            </div>
                            <div class="dm-progress-bar"><div class="dm-progress-bar__fill" style="width: <?php echo esc_attr($porcentaje); ?>%;"></div></div>
                            <small class="dm-text-muted"><?php echo number_format_i18n($estado->importe, 2); ?> €</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="dm-card">
            <h3 class="dm-card__title"><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Próximas a vencer', 'flavor-platform'); ?></h3>
            <?php if (empty($proximas_vencer)): ?>
                <div class="dm-alert dm-alert--success" style="margin: 0;"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('No hay facturas próximas a vencer.', 'flavor-platform'); ?></div>
            <?php else: ?>
                <ul class="dm-list">
                    <?php foreach ($proximas_vencer as $factura): ?>
                        <li class="dm-list__item">
                            <div class="dm-list__content"><strong><?php echo esc_html($factura->numero); ?></strong><span class="dm-text-muted"> - <?php echo esc_html($factura->cliente_nombre); ?></span></div>
                            <div class="dm-list__meta"><span class="dm-badge dm-badge--warning"><?php echo esc_html(date_i18n('d M', strtotime($factura->fecha_vencimiento))); ?></span><strong><?php echo number_format_i18n($factura->total, 2); ?> €</strong></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="dm-card">
        <h3 class="dm-card__title"><span class="dashicons dashicons-analytics"></span> <?php esc_html_e('Resumen del ejercicio', 'flavor-platform'); ?></h3>
        <div class="dm-stats-inline">
            <div class="dm-stats-inline__item"><span class="dm-stats-inline__label"><?php esc_html_e('Total facturado', 'flavor-platform'); ?></span><span class="dm-stats-inline__value"><?php echo number_format_i18n($facturado_anio, 2); ?> €</span></div>
            <div class="dm-stats-inline__item"><span class="dm-stats-inline__label"><?php esc_html_e('Total cobrado', 'flavor-platform'); ?></span><span class="dm-stats-inline__value dm-text-success"><?php echo number_format_i18n($total_cobrado, 2); ?> €</span></div>
            <div class="dm-stats-inline__item"><span class="dm-stats-inline__label"><?php esc_html_e('Pendiente', 'flavor-platform'); ?></span><span class="dm-stats-inline__value dm-text-warning"><?php echo number_format_i18n($total_pendiente, 2); ?> €</span></div>
            <div class="dm-stats-inline__item"><span class="dm-stats-inline__label"><?php esc_html_e('IVA repercutido', 'flavor-platform'); ?></span><span class="dm-stats-inline__value"><?php echo number_format_i18n($iva_repercutido, 2); ?> €</span></div>
        </div>
    </div>
</div>
