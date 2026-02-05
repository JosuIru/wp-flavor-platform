<?php
/**
 * Vista Dashboard - Módulo Compostaje
 * Panel principal con estadísticas de compostaje
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_composteras = $wpdb->prefix . 'flavor_composteras';
$tabla_depositos_compostaje = $wpdb->prefix . 'flavor_compostaje_depositos';
$tabla_recogidas_compost = $wpdb->prefix . 'flavor_compostaje_recogidas';
$tabla_mantenimiento = $wpdb->prefix . 'flavor_compostaje_mantenimiento';

// Estadísticas generales
$total_composteras = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_composteras WHERE estado != 'inactiva'");
$total_depositos_mes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_depositos_compostaje WHERE MONTH(fecha_deposito) = MONTH(CURRENT_DATE())");
$total_kg_organicos_mes = $wpdb->get_var("SELECT SUM(cantidad_kg) FROM $tabla_depositos_compostaje WHERE MONTH(fecha_deposito) = MONTH(CURRENT_DATE())");
$compost_listo = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_composteras WHERE estado = 'listo_recoger'");

// Estadísticas por compostera
$stats_composteras = $wpdb->get_results("
    SELECT c.*,
           COUNT(d.id) as total_depositos,
           SUM(d.cantidad_kg) as total_kg_depositado
    FROM $tabla_composteras c
    LEFT JOIN $tabla_depositos_compostaje d ON c.id = d.compostera_id
    WHERE MONTH(d.fecha_deposito) = MONTH(CURRENT_DATE())
    GROUP BY c.id
    ORDER BY total_kg_depositado DESC
    LIMIT 5
");

// Usuarios más activos
$usuarios_activos_compostaje = $wpdb->get_results("
    SELECT u.ID, u.display_name,
           COUNT(d.id) as total_depositos,
           SUM(d.cantidad_kg) as total_kg
    FROM {$wpdb->users} u
    INNER JOIN $tabla_depositos_compostaje d ON u.ID = d.usuario_id
    WHERE MONTH(d.fecha_deposito) = MONTH(CURRENT_DATE())
    GROUP BY u.ID
    ORDER BY total_kg DESC
    LIMIT 10
");

// Evolución mensual (últimos 6 meses)
$evolucion_compostaje = $wpdb->get_results("
    SELECT DATE_FORMAT(fecha_deposito, '%Y-%m') as mes,
           SUM(cantidad_kg) as total_kg,
           COUNT(*) as total_depositos
    FROM $tabla_depositos_compostaje
    WHERE fecha_deposito >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mes
    ORDER BY mes ASC
");

// Tareas de mantenimiento pendientes
$mantenimiento_pendiente = $wpdb->get_results("
    SELECT m.*, c.nombre as compostera_nombre
    FROM $tabla_mantenimiento m
    INNER JOIN $tabla_composteras c ON m.compostera_id = c.id
    WHERE m.estado = 'pendiente'
    ORDER BY m.fecha_programada ASC
    LIMIT 5
");

// Composteras que necesitan atención
$composteras_atencion = $wpdb->get_results("
    SELECT *
    FROM $tabla_composteras
    WHERE estado IN ('llena', 'mantenimiento', 'problema')
    ORDER BY estado DESC
    LIMIT 5
");
?>

<div class="wrap flavor-compostaje-dashboard">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-site"></span>
        <?php echo esc_html__('Dashboard de Compostaje', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Tarjetas de estadísticas principales -->
    <div class="flavor-stats-cards">
        <div class="flavor-stat-card flavor-stat-primary">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-admin-site"></span>
            </div>
            <div class="flavor-stat-content">
                <h3><?php echo number_format($total_composteras); ?></h3>
                <p><?php echo esc_html__('Composteras Activas', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <div class="flavor-stat-card flavor-stat-success">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="flavor-stat-content">
                <h3><?php echo number_format($total_kg_organicos_mes, 2); ?> kg</h3>
                <p><?php echo esc_html__('Orgánicos Compostados', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <div class="flavor-stat-card flavor-stat-info">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-clipboard"></span>
            </div>
            <div class="flavor-stat-content">
                <h3><?php echo number_format($total_depositos_mes); ?></h3>
                <p><?php echo esc_html__('Depósitos este Mes', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <div class="flavor-stat-card flavor-stat-warning">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-yes"></span>
            </div>
            <div class="flavor-stat-content">
                <h3><?php echo number_format($compost_listo); ?></h3>
                <p><?php echo esc_html__('Compost Listo', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </div>

    <div class="flavor-dashboard-grid">
        <!-- Gráfica de evolución -->
        <div class="flavor-dashboard-widget flavor-widget-large">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Evolución del Compostaje', 'flavor-chat-ia'); ?></h2>
                <span class="flavor-widget-subtitle"><?php echo esc_html__('Últimos 6 meses', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-widget-body">
                <canvas id="grafica-evolucion-compostaje" height="80"></canvas>
            </div>
        </div>

        <!-- Composteras más activas -->
        <div class="flavor-dashboard-widget">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Composteras Más Activas', 'flavor-chat-ia'); ?></h2>
                <span class="flavor-widget-subtitle"><?php echo esc_html__('Este mes', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-widget-body">
                <?php if (!empty($stats_composteras)) : ?>
                    <div class="flavor-composteras-list">
                        <?php foreach ($stats_composteras as $compostera) : ?>
                            <div class="flavor-compostera-item">
                                <div class="flavor-compostera-info">
                                    <strong><?php echo esc_html($compostera->nombre); ?></strong>
                                    <span><?php echo sprintf(__('%s kg • %s depósitos', 'flavor-chat-ia'), number_format($compostera->total_kg_depositado, 2), number_format($compostera->total_depositos)); ?></span>
                                </div>
                                <div class="flavor-compostera-nivel">
                                    <div class="flavor-nivel-bar">
                                        <div class="flavor-nivel-fill" style="width: <?php echo min(100, ($compostera->nivel_llenado ?? 0)); ?>%;"></div>
                                    </div>
                                    <span><?php echo ($compostera->nivel_llenado ?? 0); ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="flavor-no-data"><?php echo esc_html__('No hay datos disponibles.', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Usuarios más activos -->
        <div class="flavor-dashboard-widget">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Usuarios Más Activos', 'flavor-chat-ia'); ?></h2>
                <span class="flavor-widget-subtitle"><?php echo esc_html__('Este mes', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-widget-body">
                <?php if (!empty($usuarios_activos_compostaje)) : ?>
                    <div class="flavor-ranking-list">
                        <?php foreach ($usuarios_activos_compostaje as $index => $usuario) : ?>
                            <div class="flavor-ranking-item">
                                <span class="flavor-ranking-position"><?php echo $index + 1; ?></span>
                                <div class="flavor-ranking-user">
                                    <?php echo get_avatar($usuario->ID, 32); ?>
                                    <div class="flavor-ranking-info">
                                        <strong><?php echo esc_html($usuario->display_name); ?></strong>
                                        <span><?php echo sprintf(__('%s kg • %s depósitos', 'flavor-chat-ia'), number_format($usuario->total_kg, 2), number_format($usuario->total_depositos)); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="flavor-no-data"><?php echo esc_html__('No hay usuarios activos este mes.', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mantenimiento pendiente -->
        <div class="flavor-dashboard-widget">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Mantenimiento Pendiente', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="flavor-widget-body">
                <?php if (!empty($mantenimiento_pendiente)) : ?>
                    <div class="flavor-alert-list">
                        <?php foreach ($mantenimiento_pendiente as $tarea) : ?>
                            <div class="flavor-alert-item">
                                <span class="dashicons dashicons-admin-tools"></span>
                                <div class="flavor-alert-content">
                                    <strong><?php echo esc_html($tarea->compostera_nombre); ?></strong>
                                    <span><?php echo esc_html($tarea->tipo_mantenimiento); ?> - <?php echo date_i18n(get_option('date_format'), strtotime($tarea->fecha_programada)); ?></span>
                                </div>
                                <a href="<?php echo admin_url('admin.php?page=flavor-compostaje-mantenimiento&id=' . $tarea->id); ?>" class="button button-small">
                                    <?php echo esc_html__('Ver', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="flavor-success-message">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <p><?php echo esc_html__('No hay tareas de mantenimiento pendientes.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Composteras que necesitan atención -->
        <div class="flavor-dashboard-widget">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Composteras que Necesitan Atención', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="flavor-widget-body">
                <?php if (!empty($composteras_atencion)) : ?>
                    <div class="flavor-alert-list">
                        <?php foreach ($composteras_atencion as $compostera) : ?>
                            <div class="flavor-alert-item flavor-alert-<?php echo esc_attr($compostera->estado); ?>">
                                <span class="dashicons dashicons-location-alt"></span>
                                <div class="flavor-alert-content">
                                    <strong><?php echo esc_html($compostera->nombre); ?></strong>
                                    <span>
                                        <?php
                                        $estados_labels = [
                                            'llena' => __('Compostera llena', 'flavor-chat-ia'),
                                            'mantenimiento' => __('En mantenimiento', 'flavor-chat-ia'),
                                            'problema' => __('Problema reportado', 'flavor-chat-ia'),
                                        ];
                                        echo esc_html($estados_labels[$compostera->estado] ?? $compostera->estado);
                                        ?>
                                    </span>
                                </div>
                                <a href="<?php echo admin_url('admin.php?page=flavor-compostaje-composteras&action=edit&id=' . $compostera->id); ?>" class="button button-small">
                                    <?php echo esc_html__('Ver', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="flavor-success-message">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <p><?php echo esc_html__('Todas las composteras están operativas.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Impacto ambiental -->
        <div class="flavor-dashboard-widget flavor-widget-highlight">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Impacto Ambiental', 'flavor-chat-ia'); ?></h2>
                <span class="flavor-widget-subtitle"><?php echo esc_html__('Este mes', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-widget-body">
                <div class="flavor-impact-stats">
                    <div class="flavor-impact-item">
                        <span class="dashicons dashicons-admin-site"></span>
                        <div>
                            <strong><?php echo number_format($total_kg_organicos_mes * 0.5, 2); ?> kg</strong>
                            <p><?php echo esc_html__('CO₂ evitado', 'flavor-chat-ia'); ?></p>
                        </div>
                    </div>
                    <div class="flavor-impact-item">
                        <span class="dashicons dashicons-carrot"></span>
                        <div>
                            <strong><?php echo number_format($total_kg_organicos_mes * 0.3, 2); ?> kg</strong>
                            <p><?php echo esc_html__('Compost producido', 'flavor-chat-ia'); ?></p>
                        </div>
                    </div>
                    <div class="flavor-impact-item">
                        <span class="dashicons dashicons-trash"></span>
                        <div>
                            <strong><?php echo number_format($total_kg_organicos_mes, 2); ?> kg</strong>
                            <p><?php echo esc_html__('Residuos evitados', 'flavor-chat-ia'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const datosEvolucion = <?php echo json_encode($evolucion_compostaje); ?>;

    // Gráfica de evolución mensual
    const ctxEvolucion = document.getElementById('grafica-evolucion-compostaje');
    if (ctxEvolucion) {
        new Chart(ctxEvolucion, {
            type: 'line',
            data: {
                labels: datosEvolucion.map(d => {
                    const [year, month] = d.mes.split('-');
                    const date = new Date(year, month - 1);
                    return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: '<?php echo esc_js(__('Kg compostados', 'flavor-chat-ia')); ?>',
                    data: datosEvolucion.map(d => parseFloat(d.total_kg)),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
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
                            callback: function(value) {
                                return value + ' kg';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<style>
.flavor-compostaje-dashboard {
    padding: 20px;
}

.flavor-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.flavor-stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid;
}

.flavor-stat-primary { border-left-color: #0073aa; }
.flavor-stat-success { border-left-color: #28a745; }
.flavor-stat-info { border-left-color: #17a2b8; }
.flavor-stat-warning { border-left-color: #ffc107; }

.flavor-stat-icon {
    font-size: 40px;
    opacity: 0.8;
}

.flavor-stat-content h3 {
    margin: 0;
    font-size: 28px;
    font-weight: 600;
}

.flavor-stat-content p {
    margin: 5px 0 0;
    color: #666;
    font-size: 14px;
}

.flavor-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.flavor-widget-large {
    grid-column: span 2;
}

.flavor-dashboard-widget {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.flavor-widget-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.flavor-widget-header h2 {
    margin: 0;
    font-size: 18px;
}

.flavor-widget-subtitle {
    color: #666;
    font-size: 13px;
}

.flavor-widget-body {
    padding: 20px;
}

.flavor-composteras-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.flavor-compostera-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.flavor-compostera-info {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.flavor-compostera-info strong {
    font-size: 14px;
}

.flavor-compostera-info span {
    font-size: 12px;
    color: #666;
}

.flavor-compostera-nivel {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-nivel-bar {
    width: 100px;
    height: 10px;
    background: #e0e0e0;
    border-radius: 5px;
    overflow: hidden;
}

.flavor-nivel-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    transition: width 0.3s;
}

.flavor-ranking-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.flavor-ranking-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.flavor-ranking-position {
    font-size: 20px;
    font-weight: 700;
    color: #0073aa;
    min-width: 30px;
    text-align: center;
}

.flavor-ranking-user {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.flavor-ranking-info {
    display: flex;
    flex-direction: column;
}

.flavor-ranking-info strong {
    font-size: 14px;
}

.flavor-ranking-info span {
    font-size: 12px;
    color: #666;
}

.flavor-alert-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.flavor-alert-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    border-radius: 6px;
    background: #f8f9fa;
}

.flavor-alert-item.flavor-alert-llena {
    background: #fff3cd;
}

.flavor-alert-item.flavor-alert-mantenimiento {
    background: #f8d7da;
}

.flavor-alert-item.flavor-alert-problema {
    background: #f8d7da;
}

.flavor-alert-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.flavor-alert-content strong {
    font-size: 14px;
}

.flavor-alert-content span {
    font-size: 12px;
    color: #666;
}

.flavor-success-message {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #d4edda;
    border-radius: 6px;
    color: #155724;
}

.flavor-widget-highlight {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: #fff;
}

.flavor-widget-highlight .flavor-widget-header {
    border-bottom-color: rgba(255,255,255,0.2);
}

.flavor-widget-highlight h2,
.flavor-widget-highlight .flavor-widget-subtitle {
    color: #fff;
}

.flavor-impact-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}

.flavor-impact-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-impact-item .dashicons {
    font-size: 40px;
    opacity: 0.9;
}

.flavor-impact-item strong {
    display: block;
    font-size: 24px;
    margin-bottom: 5px;
}

.flavor-impact-item p {
    margin: 0;
    font-size: 13px;
    opacity: 0.9;
}

.flavor-no-data {
    text-align: center;
    color: #666;
    padding: 40px 20px;
}

@media (max-width: 1200px) {
    .flavor-widget-large {
        grid-column: span 1;
    }
}

@media (max-width: 768px) {
    .flavor-dashboard-grid {
        grid-template-columns: 1fr;
    }

    .flavor-stats-cards {
        grid-template-columns: 1fr;
    }
}
</style>
