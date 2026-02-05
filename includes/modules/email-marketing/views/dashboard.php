<?php
/**
 * Vista: Dashboard de Email Marketing
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

$tracking = Flavor_EM_Tracking::get_instance();
$estadisticas = $tracking->get_estadisticas_globales('30 days');

global $wpdb;

// Últimas campañas
$ultimas_campanias = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}flavor_em_campanias
     ORDER BY creado_en DESC LIMIT 5"
);

// Automatizaciones activas
$automatizaciones_activas = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}flavor_em_automatizaciones
     WHERE estado = 'activa'
     ORDER BY total_inscritos DESC LIMIT 5"
);
?>

<div class="wrap em-dashboard">
    <h1><?php _e('Email Marketing', 'flavor-chat-ia'); ?></h1>

    <!-- Métricas principales -->
    <div class="em-metrics-grid">
        <div class="em-metric-card">
            <div class="em-metric-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="em-metric-content">
                <span class="em-metric-value"><?php echo number_format($estadisticas['suscriptores']['total']); ?></span>
                <span class="em-metric-label"><?php _e('Suscriptores activos', 'flavor-chat-ia'); ?></span>
                <?php if ($estadisticas['suscriptores']['nuevos'] > 0): ?>
                    <span class="em-metric-change positive">
                        +<?php echo $estadisticas['suscriptores']['nuevos']; ?> <?php _e('este mes', 'flavor-chat-ia'); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="em-metric-card">
            <div class="em-metric-icon">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="em-metric-content">
                <span class="em-metric-value"><?php echo number_format($estadisticas['emails']['enviados']); ?></span>
                <span class="em-metric-label"><?php _e('Emails enviados', 'flavor-chat-ia'); ?></span>
                <span class="em-metric-sublabel"><?php _e('Últimos 30 días', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="em-metric-card">
            <div class="em-metric-icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="em-metric-content">
                <span class="em-metric-value"><?php echo $estadisticas['tasas_promedio']['apertura']; ?>%</span>
                <span class="em-metric-label"><?php _e('Tasa de apertura', 'flavor-chat-ia'); ?></span>
                <span class="em-metric-sublabel"><?php _e('Promedio', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="em-metric-card">
            <div class="em-metric-icon">
                <span class="dashicons dashicons-admin-links"></span>
            </div>
            <div class="em-metric-content">
                <span class="em-metric-value"><?php echo $estadisticas['tasas_promedio']['clicks']; ?>%</span>
                <span class="em-metric-label"><?php _e('Tasa de clicks', 'flavor-chat-ia'); ?></span>
                <span class="em-metric-sublabel"><?php _e('Promedio', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <!-- Acciones rápidas -->
    <div class="em-quick-actions">
        <a href="<?php echo admin_url('admin.php?page=flavor-em-campanias&action=new'); ?>" class="button button-primary button-hero">
            <span class="dashicons dashicons-edit"></span>
            <?php _e('Nueva campaña', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=flavor-em-suscriptores'); ?>" class="button button-secondary button-hero">
            <span class="dashicons dashicons-upload"></span>
            <?php _e('Importar suscriptores', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=flavor-em-automatizaciones&action=new'); ?>" class="button button-secondary button-hero">
            <span class="dashicons dashicons-controls-repeat"></span>
            <?php _e('Nueva automatización', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <div class="em-dashboard-grid">
        <!-- Últimas campañas -->
        <div class="em-dashboard-widget">
            <h2><?php _e('Últimas campañas', 'flavor-chat-ia'); ?></h2>

            <?php if (empty($ultimas_campanias)): ?>
                <p class="em-empty"><?php _e('No hay campañas aún.', 'flavor-chat-ia'); ?></p>
            <?php else: ?>
                <table class="em-table">
                    <thead>
                        <tr>
                            <th><?php _e('Campaña', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Enviados', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Apertura', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimas_campanias as $campania): ?>
                            <?php
                            $tasa_apertura = $campania->total_enviados > 0
                                ? round(($campania->total_abiertos / $campania->total_enviados) * 100, 1)
                                : 0;
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=flavor-em-campanias&action=edit&id=' . $campania->id); ?>">
                                        <?php echo esc_html($campania->nombre); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="em-badge em-badge-<?php echo esc_attr($campania->estado); ?>">
                                        <?php echo esc_html(ucfirst($campania->estado)); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($campania->total_enviados); ?></td>
                                <td><?php echo $tasa_apertura; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="em-widget-footer">
                    <a href="<?php echo admin_url('admin.php?page=flavor-em-campanias'); ?>">
                        <?php _e('Ver todas las campañas', 'flavor-chat-ia'); ?> &rarr;
                    </a>
                </p>
            <?php endif; ?>
        </div>

        <!-- Automatizaciones activas -->
        <div class="em-dashboard-widget">
            <h2><?php _e('Automatizaciones activas', 'flavor-chat-ia'); ?></h2>

            <?php if (empty($automatizaciones_activas)): ?>
                <p class="em-empty"><?php _e('No hay automatizaciones activas.', 'flavor-chat-ia'); ?></p>
            <?php else: ?>
                <table class="em-table">
                    <thead>
                        <tr>
                            <th><?php _e('Nombre', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Trigger', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Inscritos', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Completados', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($automatizaciones_activas as $auto): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=flavor-em-automatizaciones&action=edit&id=' . $auto->id); ?>">
                                        <?php echo esc_html($auto->nombre); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $auto->trigger_tipo))); ?></td>
                                <td><?php echo number_format($auto->total_inscritos); ?></td>
                                <td><?php echo number_format($auto->total_completados); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="em-widget-footer">
                    <a href="<?php echo admin_url('admin.php?page=flavor-em-automatizaciones'); ?>">
                        <?php _e('Ver automatizaciones', 'flavor-chat-ia'); ?> &rarr;
                    </a>
                </p>
            <?php endif; ?>
        </div>

        <!-- Gráfico de crecimiento -->
        <div class="em-dashboard-widget em-widget-full">
            <h2><?php _e('Crecimiento de suscriptores', 'flavor-chat-ia'); ?></h2>

            <div class="em-chart-container">
                <canvas id="em-chart-crecimiento" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('em-chart-crecimiento');
    if (!ctx || typeof Chart === 'undefined') return;

    const data = <?php echo wp_json_encode($estadisticas['crecimiento_diario']); ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.fecha),
            datasets: [{
                label: '<?php _e('Nuevos suscriptores', 'flavor-chat-ia'); ?>',
                data: data.map(d => d.total),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.3,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                    }
                }
            }
        }
    });
});
</script>
