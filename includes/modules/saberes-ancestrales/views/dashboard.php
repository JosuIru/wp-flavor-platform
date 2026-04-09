<?php
/**
 * Vista Dashboard - Saberes Ancestrales
 *
 * Dashboard administrativo para preservación de conocimientos tradicionales.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas del módulo
$tabla_saberes = $wpdb->prefix . 'flavor_saberes';
$tabla_transmisiones = $wpdb->prefix . 'flavor_saberes_transmisiones';
$tabla_maestros = $wpdb->prefix . 'flavor_saberes_maestros';
$tabla_aprendices = $wpdb->prefix . 'flavor_saberes_aprendices';

// Verificar si las tablas existen
$tabla_saberes_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_saberes)) === $tabla_saberes;

if (!$tabla_saberes_existe) {
    ?>
    <div class="dm-card">
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Módulo en preparación', 'flavor-platform'); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Saberes Ancestrales aún no han sido creadas. Activa el módulo completamente para generar la estructura necesaria.', 'flavor-platform'); ?></p>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Estadísticas principales
$total_saberes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_saberes}");
$saberes_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_saberes} WHERE estado = 'activo'");
$saberes_documentados = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_saberes} WHERE documentado = 1");
$saberes_mes = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_saberes} WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
);

// Transmisiones
$tabla_transmisiones_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_transmisiones)) === $tabla_transmisiones;
$total_transmisiones = $tabla_transmisiones_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_transmisiones}") : 0;
$transmisiones_activas = $tabla_transmisiones_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_transmisiones} WHERE estado = 'en_curso'") : 0;
$transmisiones_completadas = $tabla_transmisiones_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_transmisiones} WHERE estado = 'completada'") : 0;

// Maestros
$tabla_maestros_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_maestros)) === $tabla_maestros;
$total_maestros = $tabla_maestros_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_maestros}") : 0;
$maestros_activos = $tabla_maestros_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_maestros} WHERE activo = 1") : 0;

// Aprendices
$tabla_aprendices_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_aprendices)) === $tabla_aprendices;
$total_aprendices = $tabla_aprendices_existe ? (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_aprendices}") : 0;

// Tasa de documentación
$tasa_documentacion = $total_saberes > 0 ? round(($saberes_documentados / $total_saberes) * 100) : 0;

// Actividad semanal
$actividad_semanal = $wpdb->get_results(
    "SELECT DATE(fecha_creacion) as fecha, COUNT(*) as total
     FROM {$tabla_saberes}
     WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(fecha_creacion)
     ORDER BY fecha ASC"
);

// Por categoría/tipo
$por_categoria = $wpdb->get_results(
    "SELECT categoria, COUNT(*) as total
     FROM {$tabla_saberes}
     WHERE estado = 'activo'
     GROUP BY categoria
     ORDER BY total DESC
     LIMIT 6"
);

// Saberes más populares (más transmisiones)
$saberes_populares = $tabla_transmisiones_existe ? $wpdb->get_results(
    "SELECT s.id, s.titulo, s.categoria, s.documentado,
            COUNT(t.id) as num_transmisiones
     FROM {$tabla_saberes} s
     LEFT JOIN {$tabla_transmisiones} t ON s.id = t.saber_id
     WHERE s.estado = 'activo'
     GROUP BY s.id
     ORDER BY num_transmisiones DESC
     LIMIT 5"
) : [];

// Maestros destacados
$maestros_top = $tabla_maestros_existe && $tabla_transmisiones_existe ? $wpdb->get_results(
    "SELECT m.id, m.nombre, m.especialidad,
            COUNT(t.id) as transmisiones
     FROM {$tabla_maestros} m
     LEFT JOIN {$tabla_transmisiones} t ON m.id = t.maestro_id
     WHERE m.activo = 1
     GROUP BY m.id
     ORDER BY transmisiones DESC
     LIMIT 5"
) : [];

// Saberes recientes
$saberes_recientes = $wpdb->get_results(
    "SELECT s.*
     FROM {$tabla_saberes} s
     ORDER BY s.fecha_creacion DESC
     LIMIT 5"
);
?>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--warning dm-stat-card--horizontal">
        <span class="dashicons dashicons-book-alt dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($total_saberes); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Saberes Registrados', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--success dm-stat-card--horizontal">
        <span class="dashicons dashicons-welcome-learn-more dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($total_transmisiones); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Transmisiones', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--purple dm-stat-card--horizontal">
        <span class="dashicons dashicons-businessman dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($total_maestros); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Maestros', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--info dm-stat-card--horizontal">
        <span class="dashicons dashicons-groups dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($total_aprendices); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Aprendices', 'flavor-platform'); ?></div>
        </div>
    </div>
</div>

<?php if ($transmisiones_activas > 0): ?>
<div class="dm-alert dm-alert--success">
    <span class="dashicons dashicons-yes-alt"></span>
    <div>
        <strong><?php printf(esc_html__('%s transmisiones en curso', 'flavor-platform'), number_format_i18n($transmisiones_activas)); ?></strong>
        <span><?php esc_html_e('El conocimiento se está transmitiendo activamente.', 'flavor-platform'); ?></span>
    </div>
</div>
<?php endif; ?>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Saberes Esta Semana', 'flavor-platform'); ?>
            </h3>
            <span class="dm-card__subtitle"><?php printf(esc_html__('%s este mes', 'flavor-platform'), number_format_i18n($saberes_mes)); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($actividad_semanal)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('No hay registros esta semana.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <?php
                $max_saberes = max(array_column($actividad_semanal, 'total'));
                $dias_semana = [
                    __('Dom', 'flavor-platform'), __('Lun', 'flavor-platform'), __('Mar', 'flavor-platform'),
                    __('Mié', 'flavor-platform'), __('Jue', 'flavor-platform'), __('Vie', 'flavor-platform'),
                    __('Sáb', 'flavor-platform')
                ];
                ?>
                <div class="dm-chart-bars">
                    <?php foreach ($actividad_semanal as $dia): ?>
                        <?php
                        $altura = $max_saberes > 0 ? ($dia->total / $max_saberes) * 100 : 5;
                        $fecha = new DateTime($dia->fecha);
                        $dia_nombre = $dias_semana[(int)$fecha->format('w')];
                        ?>
                        <div class="dm-chart-bars__item">
                            <span class="dm-chart-bars__value"><?php echo esc_html($dia->total); ?></span>
                            <div class="dm-chart-bars__bar" style="height: <?php echo max(4, $altura); ?>px; background: linear-gradient(180deg, #b45309 0%, #d97706 100%);"></div>
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
                <span class="dashicons dashicons-category"></span>
                <?php esc_html_e('Por Categoría', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($por_categoria)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-list-view"></span>
                    <p><?php esc_html_e('No hay saberes registrados.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <div class="dm-data-list">
                    <?php foreach ($por_categoria as $cat): ?>
                        <div class="dm-data-list__item">
                            <span class="dm-data-list__label"><?php echo esc_html(ucfirst(str_replace('_', ' ', $cat->categoria))); ?></span>
                            <span class="dm-data-list__value"><?php echo esc_html($cat->total); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-star-filled"></span>
                <?php esc_html_e('Saberes Más Transmitidos', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($saberes_populares)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-book"></span>
                    <p><?php esc_html_e('No hay transmisiones todavía.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($saberes_populares as $index => $saber): ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($saber->titulo); ?></strong>
                                <span class="dm-ranking__meta">
                                    <?php echo esc_html(ucfirst($saber->categoria)); ?>
                                    <?php if ($saber->documentado): ?>
                                        &bull; <span style="color: var(--dm-success);">✓ <?php esc_html_e('Documentado', 'flavor-platform'); ?></span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--warning">
                                <?php echo esc_html($saber->num_transmisiones); ?>
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
                <span class="dashicons dashicons-awards"></span>
                <?php esc_html_e('Maestros Destacados', 'flavor-platform'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($maestros_top)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-businessman"></span>
                    <p><?php esc_html_e('No hay maestros registrados.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($maestros_top as $index => $maestro): ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__avatar" style="background: #b45309;">
                                <?php echo mb_substr($maestro->nombre ?: __('M', 'flavor-platform'), 0, 1); ?>
                            </div>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($maestro->nombre ?: __('Maestro', 'flavor-platform')); ?></strong>
                                <?php if ($maestro->especialidad): ?>
                                <span class="dm-ranking__meta"><?php echo esc_html($maestro->especialidad); ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="dm-badge dm-badge--success">
                                <?php echo esc_html($maestro->transmisiones); ?> <?php esc_html_e('trans.', 'flavor-platform'); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dm-card">
    <div class="dm-card__header">
        <h3 class="dm-card__title">
            <span class="dashicons dashicons-clock"></span>
            <?php esc_html_e('Saberes Recientes', 'flavor-platform'); ?>
        </h3>
    </div>
    <div class="dm-card__body">
        <?php if (empty($saberes_recientes)): ?>
            <div class="dm-empty">
                <span class="dashicons dashicons-book-alt"></span>
                <p><?php esc_html_e('No hay saberes registrados todavía.', 'flavor-platform'); ?></p>
            </div>
        <?php else: ?>
            <ul class="dm-list">
                <?php foreach ($saberes_recientes as $saber): ?>
                    <li class="dm-list__item">
                        <div class="dm-list__content">
                            <strong class="dm-list__title"><?php echo esc_html($saber->titulo); ?></strong>
                            <span class="dm-list__meta">
                                <?php echo esc_html(ucfirst($saber->categoria)); ?>
                                &bull;
                                <?php echo esc_html(human_time_diff(strtotime($saber->fecha_creacion), current_time('timestamp'))); ?>
                            </span>
                        </div>
                        <span class="dm-badge dm-badge--<?php echo $saber->documentado ? 'success' : 'secondary'; ?>">
                            <?php echo $saber->documentado ? esc_html__('Documentado', 'flavor-platform') : esc_html__('Pendiente', 'flavor-platform'); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--secondary">
        <span class="dashicons dashicons-media-document dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($saberes_documentados); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Documentados', 'flavor-platform'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--success">
        <span class="dashicons dashicons-yes-alt dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($transmisiones_completadas); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Trans. Completadas', 'flavor-platform'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--info">
        <span class="dashicons dashicons-admin-users dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($maestros_activos); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Maestros Activos', 'flavor-platform'); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--primary">
        <span class="dashicons dashicons-chart-pie dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($tasa_documentacion); ?>%</div>
        <div class="dm-stat-card__label"><?php esc_html_e('Documentación', 'flavor-platform'); ?></div>
    </div>
</div>
