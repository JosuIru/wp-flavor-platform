<?php
/**
 * Vista: Estadísticas de Email Marketing
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

$tracking = Flavor_EM_Tracking::get_instance();

// Obtener período
$periodo = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : '30 days';
$campania_id = isset($_GET['campania']) ? absint($_GET['campania']) : 0;

// Estadísticas globales o de campaña
if ($campania_id) {
    $datos = $tracking->get_estadisticas_campania($campania_id);
    $tipo = 'campania';
} else {
    $datos = $tracking->get_estadisticas_globales($periodo);
    $tipo = 'global';
}
?>

<div class="wrap em-estadisticas">
    <h1><?php _e('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

    <!-- Selector de período -->
    <?php if ($tipo === 'global'): ?>
        <div class="em-periodo-selector">
            <a href="<?php echo esc_url(add_query_arg('periodo', '7 days')); ?>"
               class="<?php echo $periodo === '7 days' ? 'active' : ''; ?>">
                <?php _e('7 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('periodo', '30 days')); ?>"
               class="<?php echo $periodo === '30 days' ? 'active' : ''; ?>">
                <?php _e('30 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('periodo', '90 days')); ?>"
               class="<?php echo $periodo === '90 days' ? 'active' : ''; ?>">
                <?php _e('90 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('periodo', '1 year')); ?>"
               class="<?php echo $periodo === '1 year' ? 'active' : ''; ?>">
                <?php _e('1 año', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="em-campania-info">
            <a href="<?php echo admin_url('admin.php?page=flavor-em-estadisticas'); ?>" class="em-volver">
                &larr; <?php _e('Volver a estadísticas generales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <h2><?php echo esc_html($datos['campania']['nombre']); ?></h2>
            <p class="em-campania-asunto"><?php echo esc_html($datos['campania']['asunto']); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($tipo === 'global'): ?>
        <!-- Métricas globales -->
        <div class="em-stats-grid">
            <div class="em-stat-card">
                <div class="em-stat-icon em-icon-users">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="em-stat-content">
                    <span class="em-stat-value"><?php echo number_format($datos['suscriptores']['total']); ?></span>
                    <span class="em-stat-label"><?php _e('Suscriptores totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="em-stat-change <?php echo $datos['suscriptores']['crecimiento_neto'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($datos['suscriptores']['crecimiento_neto'] >= 0 ? '+' : '') . $datos['suscriptores']['crecimiento_neto']; ?>
                        <?php _e('neto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </div>
            </div>

            <div class="em-stat-card">
                <div class="em-stat-icon em-icon-email">
                    <span class="dashicons dashicons-email-alt"></span>
                </div>
                <div class="em-stat-content">
                    <span class="em-stat-value"><?php echo number_format($datos['emails']['enviados']); ?></span>
                    <span class="em-stat-label"><?php _e('Emails enviados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <div class="em-stat-card">
                <div class="em-stat-icon em-icon-open">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
                <div class="em-stat-content">
                    <span class="em-stat-value"><?php echo $datos['tasas_promedio']['apertura']; ?>%</span>
                    <span class="em-stat-label"><?php _e('Tasa de apertura promedio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <div class="em-stat-card">
                <div class="em-stat-icon em-icon-click">
                    <span class="dashicons dashicons-admin-links"></span>
                </div>
                <div class="em-stat-content">
                    <span class="em-stat-value"><?php echo $datos['tasas_promedio']['clicks']; ?>%</span>
                    <span class="em-stat-label"><?php _e('Tasa de clicks promedio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>
        </div>

        <!-- Gráfico de crecimiento -->
        <div class="em-chart-section">
            <h2><?php _e('Crecimiento de suscriptores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="em-chart-container">
                <canvas id="em-chart-crecimiento" height="300"></canvas>
            </div>
        </div>

    <?php else: ?>
        <!-- Métricas de campaña -->
        <div class="em-stats-grid">
            <div class="em-stat-card">
                <div class="em-stat-icon em-icon-send">
                    <span class="dashicons dashicons-email"></span>
                </div>
                <div class="em-stat-content">
                    <span class="em-stat-value"><?php echo number_format($datos['metricas']['enviados']); ?></span>
                    <span class="em-stat-label"><?php _e('Enviados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <div class="em-stat-card">
                <div class="em-stat-icon em-icon-open">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
                <div class="em-stat-content">
                    <span class="em-stat-value"><?php echo number_format($datos['metricas']['abiertos']); ?></span>
                    <span class="em-stat-label"><?php _e('Aperturas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="em-stat-rate"><?php echo $datos['metricas']['tasa_apertura']; ?>%</span>
                </div>
            </div>

            <div class="em-stat-card">
                <div class="em-stat-icon em-icon-click">
                    <span class="dashicons dashicons-admin-links"></span>
                </div>
                <div class="em-stat-content">
                    <span class="em-stat-value"><?php echo number_format($datos['metricas']['clicks']); ?></span>
                    <span class="em-stat-label"><?php _e('Clicks', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="em-stat-rate"><?php echo $datos['metricas']['tasa_clicks']; ?>%</span>
                </div>
            </div>

            <div class="em-stat-card">
                <div class="em-stat-icon em-icon-unsub">
                    <span class="dashicons dashicons-dismiss"></span>
                </div>
                <div class="em-stat-content">
                    <span class="em-stat-value"><?php echo number_format($datos['metricas']['bajas']); ?></span>
                    <span class="em-stat-label"><?php _e('Bajas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="em-stat-rate"><?php echo $datos['metricas']['tasa_bajas']; ?>%</span>
                </div>
            </div>
        </div>

        <div class="em-stats-row">
            <!-- URLs más clickeadas -->
            <div class="em-stats-panel">
                <h3><?php _e('Enlaces más clickeados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <?php if (!empty($datos['urls_clickeadas'])): ?>
                    <table class="em-table-simple">
                        <thead>
                            <tr>
                                <th><?php _e('URL', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php _e('Clicks', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($datos['urls_clickeadas'] as $url): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url($url->url_clickeada); ?>" target="_blank">
                                            <?php
                                            $url_display = parse_url($url->url_clickeada, PHP_URL_PATH);
                                            echo esc_html(strlen($url_display) > 40 ? substr($url_display, 0, 40) . '...' : $url_display);
                                            ?>
                                        </a>
                                    </td>
                                    <td><?php echo number_format($url->total); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="em-no-data"><?php _e('No hay datos de clicks aún.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>

            <!-- Dispositivos -->
            <div class="em-stats-panel">
                <h3><?php _e('Dispositivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <?php if (!empty($datos['dispositivos'])): ?>
                    <div class="em-dispositivos-chart">
                        <canvas id="em-chart-dispositivos" height="200"></canvas>
                    </div>
                <?php else: ?>
                    <p class="em-no-data"><?php _e('No hay datos de dispositivos aún.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Aperturas por hora -->
        <div class="em-stats-panel em-panel-full">
            <h3><?php _e('Aperturas por hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <?php if (!empty($datos['aperturas_por_hora'])): ?>
                <div class="em-chart-container">
                    <canvas id="em-chart-horas" height="200"></canvas>
                </div>
            <?php else: ?>
                <p class="em-no-data"><?php _e('No hay datos de aperturas aún.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;

    <?php if ($tipo === 'global' && !empty($datos['crecimiento_diario'])): ?>
    // Gráfico de crecimiento
    new Chart(document.getElementById('em-chart-crecimiento'), {
        type: 'line',
        data: {
            labels: <?php echo wp_json_encode(array_column($datos['crecimiento_diario'], 'fecha')); ?>,
            datasets: [{
                label: '<?php _e('Nuevos suscriptores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                data: <?php echo wp_json_encode(array_column($datos['crecimiento_diario'], 'total')); ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.3,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        }
    });
    <?php endif; ?>

    <?php if ($tipo === 'campania' && !empty($datos['dispositivos'])): ?>
    // Gráfico de dispositivos
    new Chart(document.getElementById('em-chart-dispositivos'), {
        type: 'doughnut',
        data: {
            labels: <?php echo wp_json_encode(array_column($datos['dispositivos'], 'dispositivo')); ?>,
            datasets: [{
                data: <?php echo wp_json_encode(array_column($datos['dispositivos'], 'total')); ?>,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        }
    });
    <?php endif; ?>

    <?php if ($tipo === 'campania' && !empty($datos['aperturas_por_hora'])): ?>
    // Gráfico de horas
    new Chart(document.getElementById('em-chart-horas'), {
        type: 'bar',
        data: {
            labels: <?php echo wp_json_encode(array_map(function($h) { return $h->hora . ':00'; }, $datos['aperturas_por_hora'])); ?>,
            datasets: [{
                label: '<?php _e('Aperturas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>',
                data: <?php echo wp_json_encode(array_column($datos['aperturas_por_hora'], 'total')); ?>,
                backgroundColor: '#3b82f6',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        }
    });
    <?php endif; ?>
});
</script>
