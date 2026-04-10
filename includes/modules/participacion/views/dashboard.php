<?php
/**
 * Vista Dashboard - Módulo Participación
 *
 * Panel de control con estadísticas de participación ciudadana
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
$tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';
$tabla_votos = $wpdb->prefix . 'flavor_votos';

// Verificar tablas
$tabla_propuestas_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_propuestas'");
$tabla_votaciones_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_votaciones'");
$tabla_votos_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_votos'");
$tablas_disponibles = ($tabla_propuestas_existe || $tabla_votaciones_existe || $tabla_votos_existe);

// Estadísticas generales
$total_propuestas = 0;
$propuestas_activas = 0;
$total_votaciones = 0;
$votaciones_activas = 0;
$total_votos = 0;
$votantes_unicos = 0;
$tasa_participacion = 0;

if ($tabla_propuestas_existe) {
    $total_propuestas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_propuestas");
    $propuestas_activas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_propuestas WHERE estado = 'activa'");
}

if ($tabla_votaciones_existe) {
    $total_votaciones = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_votaciones");
    $votaciones_activas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_votaciones WHERE estado = 'activa' AND fecha_inicio <= NOW() AND fecha_fin >= NOW()");
}

if ($tabla_votos_existe) {
    $total_votos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_votos");
    $votantes_unicos = $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_votos");
}

// Tasa de participación (estimado)
$total_usuarios = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} WHERE user_status = 0");
$tasa_participacion = $total_usuarios > 0 ? round(($votantes_unicos / $total_usuarios) * 100, 1) : 0;

// Propuestas por estado
$stats_propuestas_estado = [];
if ($tabla_propuestas_existe) {
    $stats_propuestas_estado = $wpdb->get_results("
        SELECT estado, COUNT(*) as total
        FROM $tabla_propuestas
        GROUP BY estado
    ");
}

// Votaciones recientes
$votaciones_recientes = [];
if ($tabla_votaciones_existe && $tabla_votos_existe) {
    $votaciones_recientes = $wpdb->get_results("
        SELECT v.*, COUNT(vo.id) as total_votos
        FROM $tabla_votaciones v
        LEFT JOIN $tabla_votos vo ON v.id = vo.votacion_id
        WHERE v.estado = 'activa'
        GROUP BY v.id
        ORDER BY v.fecha_inicio DESC
        LIMIT 5
    ");
}

// Propuestas más votadas
$propuestas_populares = [];
if ($tabla_propuestas_existe) {
    $propuestas_populares = $wpdb->get_results("
        SELECT *
        FROM $tabla_propuestas
        WHERE estado IN ('activa', 'en_revision')
        ORDER BY votos_favor DESC
        LIMIT 5
    ");
}

// Tendencia de participación (últimos 30 días)
$tendencia_participacion = [];
if ($tabla_votos_existe) {
    $tendencia_participacion = $wpdb->get_results("
        SELECT DATE(fecha_voto) as fecha, COUNT(*) as total_votos
        FROM $tabla_votos
        WHERE fecha_voto >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(fecha_voto)
        ORDER BY fecha ASC
    ");
}

?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('participacion');
    }
    ?>

    <?php if (!$tablas_disponibles): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <p><?php esc_html_e('Faltan tablas del módulo Participación o aún no hay actividad registrada.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php endif; ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-groups"></span>
            <h1><?php esc_html_e('Dashboard de Participación Ciudadana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=participacion-propuestas')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-lightbulb"></span>
            <span><?php esc_html_e('Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=participacion-votaciones')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-megaphone"></span>
            <span><?php esc_html_e('Votaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=participacion-debates')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-format-chat"></span>
            <span><?php esc_html_e('Debates', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=participacion-resultados')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-chart-bar"></span>
            <span><?php esc_html_e('Resultados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=participacion-config')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-settings"></span>
            <span><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/mi-portal/participacion/')); ?>" class="dm-quick-links__item" target="_blank">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal público', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
    </div>

    <!-- Métricas principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-lightbulb"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($propuestas_activas); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Propuestas Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php printf(esc_html__('de %s totales', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($total_propuestas)); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-thumbs-up"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($votaciones_activas); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Votaciones Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('en curso ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-chart-area"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_votos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Participación Total', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('votos emitidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--error">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html($tasa_participacion); ?>%</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Tasa Participación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php echo number_format_i18n($votantes_unicos); ?> <?php esc_html_e('ciudadanos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e('Tendencia de Participación (30 días)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-tendencia"></canvas>
            </div>
        </div>

        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php esc_html_e('Propuestas por Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-propuestas-estado"></canvas>
            </div>
        </div>
    </div>

    <!-- Listas -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-megaphone"></span>
                    <?php esc_html_e('Votaciones Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
            </div>
            <?php if (!empty($votaciones_recientes)) : ?>
                <div class="dm-item-list">
                    <?php foreach ($votaciones_recientes as $votacion) : ?>
                        <div class="dm-item-list__item">
                            <div class="dm-item-list__content">
                                <strong><?php echo esc_html($votacion->titulo); ?></strong>
                                <span class="dm-item-list__muted">
                                    <?php esc_html_e('Finaliza:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    <?php echo esc_html(date_i18n('d/m/Y', strtotime($votacion->fecha_fin))); ?>
                                </span>
                            </div>
                            <div class="dm-item-list__meta">
                                <span class="dm-text-primary dm-text-lg"><?php echo number_format_i18n($votacion->total_votos); ?></span>
                                <span class="dm-item-list__muted"><?php esc_html_e('votos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-megaphone"></span>
                    <p><?php esc_html_e('No hay votaciones activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="dm-card">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-lightbulb"></span>
                    <?php esc_html_e('Propuestas Más Populares', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
            </div>
            <?php if (!empty($propuestas_populares)) : ?>
                <div class="dm-item-list">
                    <?php foreach ($propuestas_populares as $propuesta) : ?>
                        <div class="dm-item-list__item">
                            <div class="dm-item-list__content">
                                <strong><?php echo esc_html($propuesta->titulo); ?></strong>
                                <span class="dm-item-list__muted"><?php echo esc_html($propuesta->categoria); ?></span>
                            </div>
                            <div class="dm-item-list__meta dm-text-success">
                                <span class="dashicons dashicons-thumbs-up"></span>
                                <span><?php echo number_format_i18n($propuesta->votos_favor); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-lightbulb"></span>
                    <p><?php esc_html_e('No hay propuestas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartTendencia = document.getElementById('chart-tendencia');
    const chartEstado = document.getElementById('chart-propuestas-estado');

    if (chartTendencia) {
        new Chart(chartTendencia, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(!empty($tendencia_participacion) ? array_map(function($t) { return date('d/m', strtotime($t->fecha)); }, $tendencia_participacion) : ['01/03', '05/03', '10/03', '15/03', '20/03', '25/03']); ?>,
                datasets: [{
                    label: 'Votos',
                    data: <?php echo json_encode(!empty($tendencia_participacion) ? array_column($tendencia_participacion, 'total_votos') : [12, 19, 8, 25, 32, 28]); ?>,
                    borderColor: 'var(--dm-primary, #3b82f6)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    if (chartEstado) {
        new Chart(chartEstado, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(!empty($stats_propuestas_estado) ? array_column($stats_propuestas_estado, 'estado') : ['activa', 'aprobada', 'pendiente', 'rechazada']); ?>,
                datasets: [{
                    data: <?php echo json_encode(!empty($stats_propuestas_estado) ? array_column($stats_propuestas_estado, 'total') : [12, 8, 15, 10]); ?>,
                    backgroundColor: [
                        'var(--dm-primary, #3b82f6)',
                        'var(--dm-success, #22c55e)',
                        'var(--dm-warning, #f59e0b)',
                        'var(--dm-error, #ef4444)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
});
</script>
