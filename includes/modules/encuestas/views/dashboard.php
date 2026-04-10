<?php
/**
 * Vista Dashboard - Encuestas
 *
 * Dashboard administrativo para gestión de encuestas y formularios.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas del módulo
$tabla_encuestas = $wpdb->prefix . 'flavor_encuestas';
$tabla_campos = $wpdb->prefix . 'flavor_encuestas_campos';
$tabla_respuestas = $wpdb->prefix . 'flavor_encuestas_respuestas';
$tabla_valores = $wpdb->prefix . 'flavor_encuestas_valores';

// Verificar si las tablas existen
$tabla_encuestas_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_encuestas)) === $tabla_encuestas;

if (!$tabla_encuestas_existe) {
    ?>
    <div class="dm-card">
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Módulo en preparación', 'flavor-platform'); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Encuestas aún no han sido creadas. Activa el módulo completamente para generar la estructura necesaria.', 'flavor-platform'); ?></p>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Estadísticas principales
$total_encuestas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_encuestas}");
$encuestas_activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_encuestas} WHERE estado = 'activa'");
$encuestas_borrador = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_encuestas} WHERE estado = 'borrador'");
$encuestas_cerradas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_encuestas} WHERE estado = 'cerrada'");

$tabla_respuestas_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_respuestas)) === $tabla_respuestas;
$total_respuestas = $tabla_respuestas_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_respuestas}") : 0;
$respuestas_hoy = $tabla_respuestas_existe ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_respuestas} WHERE DATE(fecha_creacion) = %s",
    current_time('Y-m-d')
)) : 0;
$respuestas_semana = $tabla_respuestas_existe ? (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_respuestas} WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
) : 0;

// Respuestas completas vs parciales
$respuestas_completas = $tabla_respuestas_existe ? (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_respuestas} WHERE completada = 1"
) : 0;
$tasa_completado = $total_respuestas > 0 ? round(($respuestas_completas / $total_respuestas) * 100) : 0;

// Actividad diaria (últimos 7 días)
$actividad_diaria = $tabla_respuestas_existe ? $wpdb->get_results(
    "SELECT DATE(fecha_creacion) as fecha, COUNT(*) as total
     FROM {$tabla_respuestas}
     WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(fecha_creacion)
     ORDER BY fecha ASC"
) : [];

// Encuestas más respondidas
$encuestas_populares = $tabla_respuestas_existe ? $wpdb->get_results(
    "SELECT e.id, e.titulo, e.tipo, e.estado,
            COUNT(r.id) as total_respuestas,
            SUM(CASE WHEN r.completada = 1 THEN 1 ELSE 0 END) as completadas
     FROM {$tabla_encuestas} e
     LEFT JOIN {$tabla_respuestas} r ON e.id = r.encuesta_id
     WHERE e.estado IN ('activa', 'cerrada')
     GROUP BY e.id
     HAVING total_respuestas > 0
     ORDER BY total_respuestas DESC
     LIMIT 5"
) : [];

// Encuestas recientes
$encuestas_recientes = $wpdb->get_results(
    "SELECT e.*,
            (SELECT COUNT(*) FROM {$tabla_respuestas} r WHERE r.encuesta_id = e.id) as num_respuestas
     FROM {$tabla_encuestas} e
     ORDER BY e.fecha_creacion DESC
     LIMIT 5"
);

// Distribución por tipo
$por_tipo = $wpdb->get_results(
    "SELECT tipo, COUNT(*) as total
     FROM {$tabla_encuestas}
     GROUP BY tipo
     ORDER BY total DESC"
);
?>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--primary dm-stat-card--horizontal">
        <span class="dashicons dashicons-forms dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($total_encuestas); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Encuestas', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--success dm-stat-card--horizontal">
        <span class="dashicons dashicons-yes-alt dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($encuestas_activas); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Encuestas Activas', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--purple dm-stat-card--horizontal">
        <span class="dashicons dashicons-feedback dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_respuestas)); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Respuestas', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--warning dm-stat-card--horizontal">
        <span class="dashicons dashicons-clock dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($respuestas_hoy); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Respuestas Hoy', 'flavor-platform'); ?></div>
        </div>
    </div>
</div>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Actividad Semanal', 'flavor-platform'); ?>
            </h3>
            <span class="dm-card__subtitle"><?php printf(esc_html__('%s respuestas', 'flavor-platform'), number_format_i18n($respuestas_semana)); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($actividad_diaria)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('No hay actividad en los últimos 7 días.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <?php
                $max_respuestas = max(array_column($actividad_diaria, 'total'));
                $dias_semana = [
                    __('Dom', 'flavor-platform'),
                    __('Lun', 'flavor-platform'),
                    __('Mar', 'flavor-platform'),
                    __('Mié', 'flavor-platform'),
                    __('Jue', 'flavor-platform'),
                    __('Vie', 'flavor-platform'),
                    __('Sáb', 'flavor-platform')
                ];
                ?>
                <div class="dm-chart-bars">
                    <?php foreach ($actividad_diaria as $dia): ?>
                        <?php
                        $altura = $max_respuestas > 0 ? ($dia->total / $max_respuestas) * 100 : 5;
                        $fecha = new DateTime($dia->fecha);
                        $dia_nombre = $dias_semana[(int)$fecha->format('w')];
                        ?>
                        <div class="dm-chart-bars__item">
                            <span class="dm-chart-bars__value"><?php echo esc_html($dia->total); ?></span>
                            <div class="dm-chart-bars__bar dm-chart-bars__bar--purple" style="height: <?php echo max(4, $altura); ?>px;"></div>
                            <span class="dm-chart-bars__label"><?php echo esc_html($dia_nombre); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-pie"></span>
                <?php esc_html_e('Estadísticas', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <div class="dm-data-list">
                <div class="dm-data-list__item">
                    <span class="dm-data-list__label"><?php esc_html_e('Encuestas en borrador', 'flavor-platform'); ?></span>
                    <span class="dm-data-list__value"><?php echo esc_html($encuestas_borrador); ?></span>
                </div>
                <div class="dm-data-list__item">
                    <span class="dm-data-list__label"><?php esc_html_e('Encuestas cerradas', 'flavor-platform'); ?></span>
                    <span class="dm-data-list__value"><?php echo esc_html($encuestas_cerradas); ?></span>
                </div>
                <div class="dm-data-list__item">
                    <span class="dm-data-list__label"><?php esc_html_e('Respuestas completas', 'flavor-platform'); ?></span>
                    <span class="dm-data-list__value"><?php echo esc_html(number_format_i18n($respuestas_completas)); ?></span>
                </div>
                <div class="dm-data-list__item dm-data-list__item--highlight">
                    <span class="dm-data-list__label"><?php esc_html_e('Tasa de completado', 'flavor-platform'); ?></span>
                    <span class="dm-data-list__value"><?php echo esc_html($tasa_completado); ?>%</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-star-filled"></span>
                <?php esc_html_e('Encuestas Más Respondidas', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($encuestas_populares)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-forms"></span>
                    <p><?php esc_html_e('No hay respuestas todavía.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($encuestas_populares as $index => $encuesta): ?>
                        <?php $tasa_enc = $encuesta->total_respuestas > 0 ? round(($encuesta->completadas / $encuesta->total_respuestas) * 100) : 0; ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($encuesta->titulo); ?></strong>
                                <span class="dm-ranking__meta">
                                    <?php echo esc_html(ucfirst($encuesta->tipo)); ?>
                                    &bull;
                                    <?php echo esc_html($tasa_enc); ?>% <?php esc_html_e('completado', 'flavor-platform'); ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--<?php echo $encuesta->estado === 'activa' ? 'success' : 'secondary'; ?>">
                                <?php echo esc_html(number_format_i18n($encuesta->total_respuestas)); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-clock"></span>
                <?php esc_html_e('Encuestas Recientes', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($encuestas_recientes)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-forms"></span>
                    <p><?php esc_html_e('No hay encuestas creadas todavía.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-list">
                    <?php foreach ($encuestas_recientes as $encuesta): ?>
                        <li class="dm-list__item">
                            <div class="dm-list__content">
                                <strong class="dm-list__title"><?php echo esc_html($encuesta->titulo); ?></strong>
                                <span class="dm-list__meta">
                                    <?php echo esc_html($encuesta->num_respuestas); ?> <?php esc_html_e('respuestas', 'flavor-platform'); ?>
                                    &bull;
                                    <?php echo esc_html(human_time_diff(strtotime($encuesta->fecha_creacion), current_time('timestamp'))); ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--<?php
                                echo $encuesta->estado === 'activa' ? 'success' :
                                    ($encuesta->estado === 'borrador' ? 'warning' : 'secondary');
                            ?>">
                                <?php echo esc_html(ucfirst($encuesta->estado)); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($por_tipo)): ?>
<div class="dm-stats-grid dm-stats-grid--3">
    <?php
    $colores_tipo = [
        'encuesta' => 'primary',
        'formulario' => 'success',
        'quiz' => 'purple'
    ];
    foreach ($por_tipo as $tipo):
        $color_clase = $colores_tipo[$tipo->tipo] ?? 'secondary';
    ?>
    <div class="dm-stat-card dm-stat-card--<?php echo esc_attr($color_clase); ?>">
        <span class="dashicons dashicons-<?php echo $tipo->tipo === 'quiz' ? 'welcome-learn-more' : ($tipo->tipo === 'formulario' ? 'editor-table' : 'forms'); ?> dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($tipo->total); ?></div>
        <div class="dm-stat-card__label"><?php echo esc_html(ucfirst($tipo->tipo)); ?>s</div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
