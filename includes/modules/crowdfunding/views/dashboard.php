<?php
/**
 * Dashboard de Crowdfunding - Vista Admin
 *
 * @package FlavorPlatform
 * @since 3.3.0
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Obtener estadísticas reales
$tabla_proyectos = $wpdb->prefix . 'flavor_crowdfunding_proyectos';
$tabla_aportaciones = $wpdb->prefix . 'flavor_crowdfunding_aportaciones';

// Verificar si las tablas existen
$tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_proyectos)) === $tabla_proyectos;
$tabla_aportaciones_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_aportaciones)) === $tabla_aportaciones;

$total_proyectos = 0;
$activos = 0;
$exitosos = 0;
$total_recaudado_eur = 0.0;
$total_recaudado_semilla = 0.0;
$total_aportantes = 0;
$total_aportaciones = 0;
$proyectos_activos = [];
$por_tipo = [];
$ultimas_aportaciones = [];
$tasa_exito = 0;

if ($tabla_existe) {
    $total_proyectos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_proyectos}");
    $activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_proyectos} WHERE estado = 'activo'");
    $exitosos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_proyectos} WHERE estado = 'exitoso'");

    $total_recaudado_eur = (float) $wpdb->get_var("SELECT COALESCE(SUM(recaudado_eur), 0) FROM {$tabla_proyectos}");
    $total_recaudado_semilla = (float) $wpdb->get_var("SELECT COALESCE(SUM(recaudado_semilla), 0) FROM {$tabla_proyectos}");

    $total_aportantes = $tabla_aportaciones_existe ? (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_aportaciones} WHERE estado = 'completada' AND usuario_id IS NOT NULL") : 0;
    $total_aportaciones = $tabla_aportaciones_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_aportaciones} WHERE estado = 'completada'") : 0;

    // Proyectos activos destacados
    $proyectos_activos = $wpdb->get_results("
        SELECT id, titulo, tipo, estado, objetivo_eur, recaudado_eur, aportantes_count, fecha_fin,
               ROUND((recaudado_eur / NULLIF(objetivo_eur, 0)) * 100, 1) as porcentaje
        FROM {$tabla_proyectos}
        WHERE estado = 'activo'
        ORDER BY recaudado_eur DESC
        LIMIT 5
    ", ARRAY_A) ?: [];

    // Por tipo
    $por_tipo = $wpdb->get_results("
        SELECT tipo, COUNT(*) as total, SUM(recaudado_eur) as recaudado
        FROM {$tabla_proyectos}
        GROUP BY tipo
        ORDER BY total DESC
    ", ARRAY_A) ?: [];

    // Últimas aportaciones
    $ultimas_aportaciones = $tabla_aportaciones_existe ? $wpdb->get_results("
        SELECT a.*, p.titulo as proyecto_titulo
        FROM {$tabla_aportaciones} a
        LEFT JOIN {$tabla_proyectos} p ON a.proyecto_id = p.id
        WHERE a.estado = 'completada'
        ORDER BY a.fecha_pago DESC
        LIMIT 5
    ", ARRAY_A) : [];
    $ultimas_aportaciones = $ultimas_aportaciones ?: [];

    // Tasa de éxito
    $finalizados = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_proyectos} WHERE estado IN ('exitoso', 'fallido')");
    $tasa_exito = $finalizados > 0 ? round(($exitosos / $finalizados) * 100, 1) : 0;
}

// Labels para tipos
$tipos_labels = [
    'album' => __('Álbum/Grabación', 'flavor-platform'),
    'tour' => __('Gira/Tour', 'flavor-platform'),
    'produccion' => __('Producción', 'flavor-platform'),
    'equipamiento' => __('Equipamiento', 'flavor-platform'),
    'espacio' => __('Espacio', 'flavor-platform'),
    'evento' => __('Evento', 'flavor-platform'),
    'social' => __('Proyecto Social', 'flavor-platform'),
    'emergencia' => __('Emergencia', 'flavor-platform'),
    'otro' => __('Otro', 'flavor-platform'),
];

$tipo_colores = [
    'album' => '#ec4899',
    'tour' => '#f59e0b',
    'produccion' => '#8b5cf6',
    'equipamiento' => '#06b6d4',
    'espacio' => '#3b82f6',
    'evento' => '#10b981',
    'social' => '#84cc16',
    'emergencia' => '#ef4444',
    'otro' => '#6b7280',
];
?>

<div class="dm-dashboard">
    <?php if (!$tabla_existe): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <strong><?php esc_html_e('Sin datos disponibles:', 'flavor-platform'); ?></strong>
        <?php esc_html_e('Faltan tablas del módulo Crowdfunding o aún no hay proyectos creados.', 'flavor-platform'); ?>
    </div>
    <?php endif; ?>

    <!-- Cabecera -->
    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-heart"></span>
            <div>
                <h1><?php esc_html_e('Crowdfunding', 'flavor-platform'); ?></h1>
                <p><?php esc_html_e('Financiación colectiva para proyectos culturales y comunitarios', 'flavor-platform'); ?></p>
            </div>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(home_url('/crowdfunding/crear/')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nuevo Proyecto', 'flavor-platform'); ?>
            </a>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="dm-stats-grid dm-stats-grid--6">
        <div class="dm-stat-card dm-stat-card--primary">
            <span class="dashicons dashicons-portfolio dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_proyectos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Total Proyectos', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <span class="dashicons dashicons-controls-play dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($activos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Activos', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <span class="dashicons dashicons-money-alt dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_recaudado_eur, 0); ?>€</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Recaudado (EUR)', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <span class="dashicons dashicons-carrot dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_recaudado_semilla, 0); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('SEMILLA', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--pink">
            <span class="dashicons dashicons-groups dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_aportantes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Mecenas', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card">
            <span class="dashicons dashicons-yes-alt dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo $tasa_exito; ?>%</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Tasa de Éxito', 'flavor-platform'); ?></div>
            </div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="dm-quick-links">
        <h2 class="dm-quick-links__title">
            <span class="dashicons dashicons-admin-links"></span>
            <?php esc_html_e('Accesos Rápidos', 'flavor-platform'); ?>
        </h2>
        <div class="dm-quick-links__grid">
            <a href="<?php echo esc_url(home_url('/crowdfunding/')); ?>" class="dm-quick-links__item">
                <span class="dashicons dashicons-list-view"></span>
                <span><?php esc_html_e('Todos los proyectos', 'flavor-platform'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/crowdfunding/crear/')); ?>" class="dm-quick-links__item dm-quick-links__item--success">
                <span class="dashicons dashicons-plus-alt"></span>
                <span><?php esc_html_e('Crear proyecto', 'flavor-platform'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/crowdfunding/?estado=activo')); ?>" class="dm-quick-links__item dm-quick-links__item--primary">
                <span class="dashicons dashicons-controls-play"></span>
                <span><?php esc_html_e('Proyectos activos', 'flavor-platform'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/crowdfunding/mis-aportaciones/')); ?>" class="dm-quick-links__item dm-quick-links__item--pink">
                <span class="dashicons dashicons-heart"></span>
                <span><?php esc_html_e('Mis aportaciones', 'flavor-platform'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/crowdfunding/mis-proyectos/')); ?>" class="dm-quick-links__item dm-quick-links__item--warning">
                <span class="dashicons dashicons-portfolio"></span>
                <span><?php esc_html_e('Mis proyectos', 'flavor-platform'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/crowdfunding/estadisticas/')); ?>" class="dm-quick-links__item dm-quick-links__item--purple">
                <span class="dashicons dashicons-chart-bar"></span>
                <span><?php esc_html_e('Estadísticas', 'flavor-platform'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/mi-portal/crowdfunding/')); ?>" class="dm-quick-links__item" target="_blank">
                <span class="dashicons dashicons-external"></span>
                <span><?php esc_html_e('Portal público', 'flavor-platform'); ?></span>
            </a>
        </div>
    </div>

    <!-- Gráficos y tablas -->
    <div class="dm-grid dm-grid--2">
        <!-- Por tipo -->
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-category"></span>
                    <?php esc_html_e('Recaudación por Tipo', 'flavor-platform'); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-por-tipo"></canvas>
            </div>
        </div>

        <!-- Últimas aportaciones -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-heart"></span>
                    <?php esc_html_e('Últimas Aportaciones', 'flavor-platform'); ?>
                </h3>
            </div>
            <div class="dm-card__body">
                <?php if (!empty($ultimas_aportaciones)): ?>
                    <ul class="dm-activity-list">
                        <?php foreach ($ultimas_aportaciones as $aportacion): ?>
                        <li class="dm-activity-list__item">
                            <span class="dm-activity-list__icon dm-activity-list__icon--pink">
                                <span class="dashicons dashicons-heart"></span>
                            </span>
                            <div class="dm-activity-list__content">
                                <div class="dm-activity-list__title">
                                    <?php
                                    $nombre = $aportacion['anonimo'] ? __('Anónimo', 'flavor-platform') : esc_html($aportacion['nombre']);
                                    $moneda_simbolo = $aportacion['moneda'] === 'eur' ? '€' : ' ' . strtoupper($aportacion['moneda']);
                                    printf(
                                        esc_html__('%s aportó %s%s', 'flavor-platform'),
                                        '<strong>' . $nombre . '</strong>',
                                        number_format_i18n($aportacion['importe'], $aportacion['moneda'] === 'eur' ? 2 : 0),
                                        $moneda_simbolo
                                    );
                                    ?>
                                </div>
                                <div class="dm-activity-list__meta">
                                    <?php echo esc_html($aportacion['proyecto_titulo']); ?>
                                    • <?php echo esc_html(human_time_diff(strtotime($aportacion['fecha_pago']))); ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="dm-empty"><?php esc_html_e('No hay aportaciones recientes.', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Proyectos activos -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3>
                <span class="dashicons dashicons-portfolio"></span>
                <?php esc_html_e('Proyectos en Curso', 'flavor-platform'); ?>
            </h3>
        </div>
        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Proyecto', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Tipo', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Progreso', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Mecenas', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Tiempo', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($proyectos_activos)): ?>
                    <?php foreach ($proyectos_activos as $proyecto): ?>
                    <?php
                    $dias_restantes = max(0, floor((strtotime($proyecto['fecha_fin']) - time()) / 86400));
                    $porcentaje = floatval($proyecto['porcentaje'] ?? 0);
                    $progreso_clase = $porcentaje >= 100 ? 'dm-progress__fill--success' : ($porcentaje >= 50 ? '' : 'dm-progress__fill--warning');
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(home_url('/crowdfunding/' . $proyecto['id'] . '/')); ?>" class="dm-link">
                                <?php echo esc_html(wp_trim_words($proyecto['titulo'], 6)); ?>
                            </a>
                        </td>
                        <td>
                            <span class="dm-badge dm-badge--sm" style="background: <?php echo esc_attr($tipo_colores[$proyecto['tipo']] ?? '#6b7280'); ?>; color: white;">
                                <?php echo esc_html($tipos_labels[$proyecto['tipo']] ?? $proyecto['tipo']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="dm-progress-inline">
                                <div class="dm-progress dm-progress--sm">
                                    <div class="dm-progress__fill <?php echo esc_attr($progreso_clase); ?>" style="width: <?php echo min($porcentaje, 100); ?>%;"></div>
                                </div>
                                <span class="dm-progress-inline__text">
                                    <?php echo number_format_i18n($proyecto['recaudado_eur'], 0); ?>€
                                    <span class="dm-text-muted">/ <?php echo number_format_i18n($proyecto['objetivo_eur'], 0); ?>€</span>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="dm-text-muted"><?php echo number_format_i18n($proyecto['aportantes_count']); ?></span>
                        </td>
                        <td>
                            <span class="dm-badge dm-badge--sm <?php echo $dias_restantes <= 3 ? 'dm-badge--error' : ($dias_restantes <= 7 ? 'dm-badge--warning' : 'dm-badge--success'); ?>">
                                <?php printf(esc_html__('%d días', 'flavor-platform'), $dias_restantes); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="dm-table__empty">
                            <span class="dashicons dashicons-portfolio"></span>
                            <?php esc_html_e('No hay proyectos activos', 'flavor-platform'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="dm-card__footer">
            <a href="<?php echo esc_url(home_url('/crowdfunding/')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                <?php esc_html_e('Ver todos los proyectos', 'flavor-platform'); ?>
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;

    var tipoColores = <?php echo wp_json_encode($tipo_colores); ?>;
    var tiposData = <?php echo wp_json_encode(array_map(function($t) use ($tipos_labels) {
        return [
            'tipo' => $t['tipo'],
            'label' => $tipos_labels[$t['tipo']] ?? $t['tipo'],
            'value' => (float) $t['recaudado']
        ];
    }, $por_tipo)); ?>;

    var chartCanvas = document.getElementById('chart-por-tipo');
    if (chartCanvas && tiposData.length > 0) {
        new Chart(chartCanvas, {
            type: 'doughnut',
            data: {
                labels: tiposData.map(function(t) { return t.label; }),
                datasets: [{
                    data: tiposData.map(function(t) { return t.value; }),
                    backgroundColor: tiposData.map(function(t) { return tipoColores[t.tipo] || '#6b7280'; }),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, usePointStyle: true }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed.toLocaleString() + '€';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
