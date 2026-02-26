<?php
/**
 * Vista Dashboard - Talleres
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_talleres = $wpdb->prefix . 'flavor_talleres';
$tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
$tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';

// Estadísticas
$total_talleres = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_talleres WHERE estado != 'borrador'");
$talleres_activos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_talleres WHERE estado IN ('publicado', 'confirmado', 'en_curso')");
$proximos_talleres = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_talleres WHERE estado = 'confirmado'");
$total_participantes = $wpdb->get_var("SELECT SUM(inscritos_actuales) FROM $tabla_talleres");
$inscripciones_mes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_inscripciones WHERE MONTH(fecha_inscripcion) = MONTH(CURDATE())");
$ingresos_mes = $wpdb->get_var("SELECT SUM(precio_pagado) FROM $tabla_inscripciones WHERE MONTH(fecha_inscripcion) = MONTH(CURDATE())");

// Talleres populares
$talleres_populares = $wpdb->get_results(
    "SELECT t.*, u.display_name as organizador
     FROM $tabla_talleres t
     LEFT JOIN {$wpdb->users} u ON t.organizador_id = u.ID
     WHERE t.estado IN ('publicado', 'confirmado', 'en_curso')
     ORDER BY t.inscritos_actuales DESC
     LIMIT 5"
);

// Próximos talleres
$proximos = $wpdb->get_results(
    "SELECT t.*, s.fecha_hora, u.display_name as organizador
     FROM $tabla_talleres t
     INNER JOIN $tabla_sesiones s ON t.id = s.taller_id
     LEFT JOIN {$wpdb->users} u ON t.organizador_id = u.ID
     WHERE t.estado = 'confirmado'
     AND s.fecha_hora >= NOW()
     ORDER BY s.fecha_hora ASC
     LIMIT 5"
);

// Inscripciones por día
$inscripciones_por_dia = $wpdb->get_results(
    "SELECT DATE(fecha_inscripcion) as fecha, COUNT(*) as total
     FROM $tabla_inscripciones
     WHERE fecha_inscripcion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(fecha_inscripcion)
     ORDER BY fecha ASC"
);

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Dashboard - Talleres Prácticos', 'flavor-chat-ia'); ?></h1>
    <hr class="wp-header-end">

    <!-- Accesos Rápidos -->
    <div class="talleres-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=talleres-listado'); ?>" class="talleres-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-hammer" style="font-size: 24px; color: #2271b1;"></span>
            <span><?php echo esc_html__('Talleres', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=talleres-inscripciones'); ?>" class="talleres-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-clipboard" style="font-size: 24px; color: #00a32a;"></span>
            <span><?php echo esc_html__('Inscripciones', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=talleres-materiales'); ?>" class="talleres-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-page" style="font-size: 24px; color: #8c52ff;"></span>
            <span><?php echo esc_html__('Materiales', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=talleres-configuracion'); ?>" class="talleres-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px; color: #646970;"></span>
            <span><?php echo esc_html__('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #3b82f6;">
                <span class="dashicons dashicons-welcome-learn-more"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($total_talleres); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Total Talleres', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #10b981;">
                <span class="dashicons dashicons-yes"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($talleres_activos); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Talleres Activos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #f59e0b;">
                <span class="dashicons dashicons-calendar"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($proximos_talleres); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Próximos Talleres', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #8b5cf6;">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($total_participantes); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Total Participantes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #06b6d4;">
                <span class="dashicons dashicons-tickets"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($inscripciones_mes); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Inscripciones (mes)', 'flavor-chat-ia'); ?></div>
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
                    <h2><?php echo esc_html__('Inscripciones - Últimos 30 días', 'flavor-chat-ia'); ?></h2>
                </div>
                <div class="flavor-card-body">
                    <canvas id="chartInscripciones" height="80"></canvas>
                </div>
            </div>
        </div>

        <div class="flavor-dashboard-col-4">
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h2><?php echo esc_html__('Talleres Más Populares', 'flavor-chat-ia'); ?></h2>
                </div>
                <div class="flavor-card-body">
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Taller', 'flavor-chat-ia'); ?></th>
                                <th><?php echo esc_html__('Inscritos', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($talleres_populares)): ?>
                                <?php foreach ($talleres_populares as $taller): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html(wp_trim_words($taller->titulo, 4)); ?></strong>
                                            <br>
                                            <small class="flavor-text-muted"><?php echo esc_html($taller->organizador); ?></small>
                                        </td>
                                        <td>
                                            <span class="flavor-badge flavor-badge-primary">
                                                <?php echo $taller->inscritos_actuales; ?>/<?php echo $taller->max_participantes; ?>
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

    <!-- Próximos talleres -->
    <div class="flavor-card">
        <div class="flavor-card-header">
            <h2><?php echo esc_html__('Próximos Talleres', 'flavor-chat-ia'); ?></h2>
        </div>
        <div class="flavor-card-body">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Taller', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Organizador', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Participantes', 'flavor-chat-ia'); ?></th>
                        <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($proximos)): ?>
                        <?php foreach ($proximos as $taller): ?>
                            <tr>
                                <td><strong><?php echo esc_html($taller->titulo); ?></strong></td>
                                <td><?php echo esc_html($taller->organizador); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($taller->fecha_hora)); ?></td>
                                <td><?php echo $taller->inscritos_actuales; ?>/<?php echo $taller->max_participantes; ?></td>
                                <td>
                                    <span class="flavor-badge flavor-badge-success">
                                        <?php echo ucfirst($taller->estado); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="flavor-no-data"><?php echo esc_html__('No hay talleres próximos', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
<?php include plugin_dir_path(__FILE__) . '../../assets/css/admin-common.css'; ?>
</style>

<script>
jQuery(document).ready(function($) {
    const ctx = document.getElementById('chartInscripciones');
    if (ctx) {
        const data = <?php echo json_encode($inscripciones_por_dia); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => {
                    const fecha = new Date(d.fecha);
                    return fecha.getDate() + '/' + (fecha.getMonth() + 1);
                }),
                datasets: [{
                    label: 'Inscripciones',
                    data: data.map(d => parseInt(d.total)),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
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
                            precision: 0
                        }
                    }
                }
            }
        });
    }
});
</script>
