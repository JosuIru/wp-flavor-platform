<?php
/**
 * Vista Dashboard - Módulo Incidencias
 *
 * Panel de control con estadísticas y métricas de incidencias urbanas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

// Verificar existencia de tabla
$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_incidencias'") === $tabla_incidencias;

// Inicializar variables
$total_incidencias = 0;
$incidencias_abiertas = 0;
$incidencias_resueltas_mes = 0;
$incidencias_sin_asignar = 0;
$tiempo_promedio_resolucion = 0;
$stats_estado = [];
$stats_categoria = [];
$stats_prioridad = [];
$incidencias_votadas = [];
$resueltas_semana = [];
$tablas_disponibles = $tabla_existe;

if ($tabla_existe) {
    $total_incidencias = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias");
    $incidencias_abiertas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado IN ('pendiente', 'en_proceso')");
    $incidencias_resueltas_mes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado = 'resuelta' AND MONTH(fecha_resolucion) = MONTH(CURRENT_DATE()) AND YEAR(fecha_resolucion) = YEAR(CURRENT_DATE())");
    $incidencias_sin_asignar = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado = 'pendiente' AND asignado_a IS NULL");
    $tiempo_promedio_resolucion = (float) $wpdb->get_var("SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_reporte, fecha_resolucion)) FROM $tabla_incidencias WHERE estado = 'resuelta' AND fecha_resolucion IS NOT NULL");

    $stats_estado = $wpdb->get_results("SELECT estado, COUNT(*) as total FROM $tabla_incidencias GROUP BY estado");
    $stats_categoria = $wpdb->get_results("SELECT categoria, COUNT(*) as total FROM $tabla_incidencias WHERE estado IN ('pendiente', 'en_proceso') GROUP BY categoria ORDER BY total DESC LIMIT 10");
    $stats_prioridad = $wpdb->get_results("SELECT prioridad, COUNT(*) as total FROM $tabla_incidencias WHERE estado IN ('pendiente', 'en_proceso') GROUP BY prioridad");
    $incidencias_votadas = $wpdb->get_results("SELECT id, numero_incidencia, titulo, categoria, votos_ciudadanos FROM $tabla_incidencias WHERE estado IN ('pendiente', 'en_proceso') ORDER BY votos_ciudadanos DESC LIMIT 5");
    $resueltas_semana = $wpdb->get_results("SELECT DATE(fecha_resolucion) as fecha, COUNT(*) as total FROM $tabla_incidencias WHERE fecha_resolucion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(fecha_resolucion) ORDER BY fecha ASC");
}

// Mapeo de estados a badges
$estado_badge_classes = [
    'pendiente' => 'dm-badge--warning',
    'en_proceso' => 'dm-badge--info',
    'resuelta' => 'dm-badge--success',
    'cerrada' => 'dm-badge--secondary',
    'rechazada' => 'dm-badge--error',
];

$estado_labels = [
    'pendiente' => __('Pendiente', 'flavor-chat-ia'),
    'en_proceso' => __('En proceso', 'flavor-chat-ia'),
    'resuelta' => __('Resuelta', 'flavor-chat-ia'),
    'cerrada' => __('Cerrada', 'flavor-chat-ia'),
    'rechazada' => __('Rechazada', 'flavor-chat-ia'),
];
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('incidencias');
    }
    ?>

    <?php if (!$tablas_disponibles): ?>
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <p><?php esc_html_e('Falta la tabla del módulo Incidencias o aún no hay reportes registrados.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-warning"></span>
            <h1><?php esc_html_e('Dashboard de Incidencias Urbanas', 'flavor-chat-ia'); ?></h1>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=incidencias-abiertas')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-warning"></span>
            <span><?php esc_html_e('Abiertas', 'flavor-chat-ia'); ?></span>
            <?php if ($incidencias_abiertas > 0): ?>
                <span class="dm-badge dm-badge--error"><?php echo number_format_i18n($incidencias_abiertas); ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=incidencias-todas')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-list-view"></span>
            <span><?php esc_html_e('Todas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=incidencias-mapa')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-location-alt"></span>
            <span><?php esc_html_e('Mapa', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=incidencias-config')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-settings"></span>
            <span><?php esc_html_e('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('incidencias', '')); ?>" class="dm-quick-links__item" target="_blank">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal público', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Métricas principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_incidencias); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Incidencias', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-analytics"></span></div>
        </div>

        <div class="dm-stat-card dm-stat-card--error">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($incidencias_abiertas); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Abiertas', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__meta"><?php printf(esc_html__('%s sin asignar', 'flavor-chat-ia'), number_format_i18n($incidencias_sin_asignar)); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-warning"></span></div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($incidencias_resueltas_mes); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Resueltas (mes)', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-yes-alt"></span></div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($tiempo_promedio_resolucion ? round($tiempo_promedio_resolucion) : 0); ?>h</div>
            <div class="dm-stat-card__label"><?php esc_html_e('Tiempo Promedio', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__meta"><?php esc_html_e('de resolución', 'flavor-chat-ia'); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-clock"></span></div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e('Distribución por Estado', 'flavor-chat-ia'); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-estado"></canvas>
            </div>
        </div>

        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e('Por Prioridad', 'flavor-chat-ia'); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-prioridad"></canvas>
            </div>
        </div>
    </div>

    <!-- Categorías y votadas -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-category"></span> <?php esc_html_e('Categorías Más Reportadas', 'flavor-chat-ia'); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-categorias"></canvas>
            </div>
        </div>

        <div class="dm-card">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-thumbs-up"></span> <?php esc_html_e('Incidencias Más Votadas', 'flavor-chat-ia'); ?></h3>
            </div>
            <?php if (!empty($incidencias_votadas)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Número', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Título', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Votos', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incidencias_votadas as $incidencia_votada): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($incidencia_votada->numero_incidencia); ?></strong>
                                <span class="dm-table__subtitle"><?php echo esc_html(ucfirst($incidencia_votada->categoria)); ?></span>
                            </td>
                            <td><?php echo esc_html($incidencia_votada->titulo); ?></td>
                            <td>
                                <span class="dm-badge dm-badge--primary">
                                    <span class="dashicons dashicons-thumbs-up" style="font-size: 12px; width: 12px; height: 12px;"></span>
                                    <?php echo number_format_i18n($incidencia_votada->votos_ciudadanos); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-thumbs-up"></span>
                    <p><?php esc_html_e('No hay incidencias con votos', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tendencia de resolución -->
    <div class="dm-card dm-card--chart">
        <div class="dm-card__header">
            <h3><span class="dashicons dashicons-chart-line"></span> <?php esc_html_e('Tendencia de Resolución (Últimos 7 Días)', 'flavor-chat-ia'); ?></h3>
        </div>
        <div class="dm-card__chart">
            <canvas id="chart-tendencia"></canvas>
        </div>
    </div>

    <!-- Distribución por estado en lista -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Resumen por Estado', 'flavor-chat-ia'); ?></h3>
        </div>
        <?php if (!empty($stats_estado)): ?>
            <div class="dm-badge-list">
                <?php foreach ($stats_estado as $stat): ?>
                    <div class="dm-badge-list__item">
                        <span class="dm-badge <?php echo esc_attr($estado_badge_classes[$stat->estado] ?? 'dm-badge--secondary'); ?>">
                            <?php echo esc_html($estado_labels[$stat->estado] ?? ucfirst($stat->estado)); ?>
                        </span>
                        <span class="dm-badge-list__value"><?php echo number_format_i18n((int) $stat->total); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    if (typeof Chart === 'undefined') return;

    const rootStyles = getComputedStyle(document.documentElement);
    const primaryColor = rootStyles.getPropertyValue('--dm-primary').trim() || '#3b82f6';
    const successColor = rootStyles.getPropertyValue('--dm-success').trim() || '#22c55e';
    const warningColor = rootStyles.getPropertyValue('--dm-warning').trim() || '#f59e0b';
    const errorColor = rootStyles.getPropertyValue('--dm-error').trim() || '#ef4444';

    // Gráfico de Estado
    const ctxEstado = document.getElementById('chart-estado');
    if (ctxEstado) {
        new Chart(ctxEstado, {
            type: 'bar',
            data: {
                labels: <?php echo wp_json_encode(array_map(function($s) use ($estado_labels) { return $estado_labels[$s->estado] ?? ucfirst($s->estado); }, $stats_estado)); ?>,
                datasets: [{
                    label: '<?php esc_html_e('Incidencias', 'flavor-chat-ia'); ?>',
                    data: <?php echo wp_json_encode(array_column($stats_estado, 'total')); ?>,
                    backgroundColor: [warningColor + 'cc', primaryColor + 'cc', successColor + 'cc', '#6b7280cc', errorColor + 'cc'],
                    borderColor: [warningColor, primaryColor, successColor, '#6b7280', errorColor],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    // Gráfico de Prioridad
    const ctxPrioridad = document.getElementById('chart-prioridad');
    if (ctxPrioridad) {
        new Chart(ctxPrioridad, {
            type: 'doughnut',
            data: {
                labels: <?php echo wp_json_encode(array_map(function($s) { return ucfirst($s->prioridad); }, $stats_prioridad)); ?>,
                datasets: [{
                    data: <?php echo wp_json_encode(array_column($stats_prioridad, 'total')); ?>,
                    backgroundColor: [successColor + 'cc', warningColor + 'cc', '#f97316cc', errorColor + 'cc'],
                    borderColor: [successColor, warningColor, '#f97316', errorColor],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // Gráfico de Categorías
    const ctxCategorias = document.getElementById('chart-categorias');
    if (ctxCategorias) {
        new Chart(ctxCategorias, {
            type: 'bar',
            data: {
                labels: <?php echo wp_json_encode(array_map(function($s) { return ucfirst($s->categoria); }, $stats_categoria)); ?>,
                datasets: [{
                    label: '<?php esc_html_e('Incidencias', 'flavor-chat-ia'); ?>',
                    data: <?php echo wp_json_encode(array_column($stats_categoria, 'total')); ?>,
                    backgroundColor: primaryColor + 'cc',
                    borderColor: primaryColor,
                    borderWidth: 2
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    // Gráfico de Tendencia
    const ctxTendencia = document.getElementById('chart-tendencia');
    if (ctxTendencia) {
        new Chart(ctxTendencia, {
            type: 'line',
            data: {
                labels: <?php echo wp_json_encode(array_map(function($r) { return date_i18n('d/m', strtotime($r->fecha)); }, $resueltas_semana)); ?>,
                datasets: [{
                    label: '<?php esc_html_e('Incidencias Resueltas', 'flavor-chat-ia'); ?>',
                    data: <?php echo wp_json_encode(array_column($resueltas_semana, 'total')); ?>,
                    borderColor: successColor,
                    backgroundColor: successColor + '20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
});
</script>
