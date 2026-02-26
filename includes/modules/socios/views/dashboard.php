<?php
/**
 * Vista Dashboard - Socios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_socios = $wpdb->prefix . 'flavor_socios';
$tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';
$tabla_pagos = $wpdb->prefix . 'flavor_socios_pagos';

// Estadísticas
$total_socios = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_socios");
$socios_activos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_socios WHERE estado = 'activo'");
$socios_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_socios WHERE estado = 'pendiente'");
$cuotas_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cuotas WHERE estado = 'pendiente'");
$cuotas_pagadas_mes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cuotas WHERE estado = 'pagada' AND MONTH(fecha_pago) = MONTH(CURDATE())");
$ingresos_mes = $wpdb->get_var("SELECT SUM(importe) FROM $tabla_cuotas WHERE estado = 'pagada' AND MONTH(fecha_pago) = MONTH(CURDATE())");

// Socios por tipo
$socios_por_tipo = $wpdb->get_results(
    "SELECT tipo, COUNT(*) as total
     FROM $tabla_socios
     WHERE estado = 'activo'
     GROUP BY tipo
     ORDER BY total DESC"
);

// Últimos socios
$ultimos_socios = $wpdb->get_results(
    "SELECT s.*, u.display_name
     FROM $tabla_socios s
     LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
     ORDER BY s.fecha_alta DESC
     LIMIT 10"
);

// Cuotas pendientes de pago
$cuotas_pendientes_lista = $wpdb->get_results(
    "SELECT c.*, s.numero as numero_socio, u.display_name as nombre_socio
     FROM $tabla_cuotas c
     INNER JOIN $tabla_socios s ON c.socio_id = s.id
     LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
     WHERE c.estado = 'pendiente'
     ORDER BY c.fecha_cargo ASC
     LIMIT 10"
);

// Datos para gráficos (últimos 12 meses)
$ingresos_por_mes = $wpdb->get_results(
    "SELECT DATE_FORMAT(fecha_pago, '%Y-%m') as mes, SUM(importe) as total
     FROM $tabla_cuotas
     WHERE fecha_pago >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     AND estado = 'pagada'
     GROUP BY DATE_FORMAT(fecha_pago, '%Y-%m')
     ORDER BY mes ASC"
);

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Dashboard - Gestión de Socios', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">

    <!-- Accesos Rápidos -->
    <div class="socios-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=socios-listado'); ?>" class="socios-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-groups" style="font-size: 24px; color: #2271b1;"></span>
            <span><?php echo esc_html__('Socios', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=socios-cuotas'); ?>" class="socios-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-money-alt" style="font-size: 24px; color: #00a32a;"></span>
            <span><?php echo esc_html__('Cuotas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=socios-pagos'); ?>" class="socios-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-clipboard" style="font-size: 24px; color: #8c52ff;"></span>
            <span><?php echo esc_html__('Pagos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=socios-reportes'); ?>" class="socios-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-chart-bar" style="font-size: 24px; color: #dba617;"></span>
            <span><?php echo esc_html__('Reportes', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=socios-configuracion'); ?>" class="socios-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px; color: #646970;"></span>
            <span><?php echo esc_html__('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #3b82f6;">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($total_socios); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Total Socios', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #10b981;">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($socios_activos); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Socios Activos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #f59e0b;">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($socios_pendientes); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Pendientes Validación', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #ef4444;">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($cuotas_pendientes); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Cuotas Pendientes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #06b6d4;">
                <span class="dashicons dashicons-awards"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($cuotas_pagadas_mes); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Cuotas Pagadas (mes)', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #14b8a6;">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($ingresos_mes, 2); ?>€</div>
                <div class="flavor-stat-label"><?php echo esc_html__('Ingresos (mes)', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <!-- Gráficos y tablas -->
    <div class="flavor-dashboard-row">
        <div class="flavor-dashboard-col-8">
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h2><?php echo esc_html__('Ingresos - Últimos 12 meses', 'flavor-chat-ia'); ?></h2>
                </div>
                <div class="flavor-card-body">
                    <canvas id="chartIngresos" height="80"></canvas>
                </div>
            </div>
        </div>

        <div class="flavor-dashboard-col-4">
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h2><?php echo esc_html__('Socios por Tipo', 'flavor-chat-ia'); ?></h2>
                </div>
                <div class="flavor-card-body">
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Total', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($socios_por_tipo)): ?>
                                <?php foreach ($socios_por_tipo as $tipo): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html(ucfirst($tipo->tipo)); ?></strong>
                                        </td>
                                        <td>
                                            <span class="flavor-badge flavor-badge-info">
                                                <?php echo number_format($tipo->total); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="flavor-no-data"><?php echo esc_html__('No hay datos', 'flavor-chat-ia'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Cuotas pendientes -->
    <div class="flavor-card">
        <div class="flavor-card-header">
            <h2><?php echo esc_html__('Cuotas Pendientes de Pago', 'flavor-chat-ia'); ?></h2>
        </div>
        <div class="flavor-card-body">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Socio', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Número', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Periodo', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Importe', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Fecha Cargo', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cuotas_pendientes_lista)): ?>
                        <?php foreach ($cuotas_pendientes_lista as $cuota): ?>
                            <tr>
                                <td><strong><?php echo esc_html($cuota->nombre_socio); ?></strong></td>
                                <td><?php echo esc_html($cuota->numero_socio); ?></td>
                                <td><?php echo esc_html($cuota->periodo); ?></td>
                                <td><?php echo number_format($cuota->importe, 2); ?>€</td>
                                <td><?php echo date('d/m/Y', strtotime($cuota->fecha_cargo)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="flavor-no-data"><?php echo esc_html__('No hay cuotas pendientes', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Últimos socios -->
    <div class="flavor-card">
        <div class="flavor-card-header">
            <h2><?php echo esc_html__('Últimos Socios Registrados', 'flavor-chat-ia'); ?></h2>
        </div>
        <div class="flavor-card-body">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Número', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Nombre', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Fecha Alta', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ultimos_socios)): ?>
                        <?php foreach ($ultimos_socios as $socio): ?>
                            <tr>
                                <td><?php echo esc_html($socio->numero); ?></td>
                                <td><strong><?php echo esc_html($socio->display_name); ?></strong></td>
                                <td><?php echo esc_html(ucfirst($socio->tipo)); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($socio->fecha_alta)); ?></td>
                                <td>
                                    <span class="flavor-badge flavor-badge-<?php echo $socio->estado === 'activo' ? 'success' : 'warning'; ?>">
                                        <?php echo esc_html(ucfirst($socio->estado)); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="flavor-no-data"><?php echo esc_html__('No hay socios registrados', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.flavor-stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.flavor-stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.flavor-stat-content {
    flex: 1;
}

.flavor-stat-value {
    font-size: 28px;
    font-weight: bold;
    color: #1e293b;
}

.flavor-stat-label {
    font-size: 13px;
    color: #64748b;
    margin-top: 4px;
}

.flavor-dashboard-row {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 20px;
    margin: 20px 0;
}

.flavor-dashboard-col-8 {
    grid-column: span 8;
}

.flavor-dashboard-col-4 {
    grid-column: span 4;
}

.flavor-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 20px;
}

.flavor-card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
}

.flavor-card-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.flavor-card-body {
    padding: 20px;
}

.flavor-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.flavor-badge-success {
    background: #d1fae5;
    color: #065f46;
}

.flavor-badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.flavor-badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.flavor-no-data {
    text-align: center;
    color: #94a3b8;
    padding: 20px !important;
}

@media (max-width: 782px) {
    .flavor-dashboard-col-8,
    .flavor-dashboard-col-4 {
        grid-column: span 12;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    const ctx = document.getElementById('chartIngresos');
    if (ctx) {
        const data = <?php echo json_encode($ingresos_por_mes); ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => {
                    const [year, month] = d.mes.split('-');
                    return month + '/' + year.substring(2);
                }),
                datasets: [{
                    label: 'Ingresos (€)',
                    data: data.map(d => parseFloat(d.total)),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: '#3b82f6',
                    borderWidth: 1
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
                            callback: function(value) {
                                return value + '€';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
