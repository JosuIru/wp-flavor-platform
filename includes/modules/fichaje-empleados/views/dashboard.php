<?php
/**
 * Vista Dashboard - Fichaje de Empleados
 *
 * Dashboard administrativo para control horario.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas del módulo
$tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';
$tabla_empleados = $wpdb->prefix . 'flavor_fichajes_empleados';
$tabla_turnos = $wpdb->prefix . 'flavor_fichajes_turnos';

// Verificar si las tablas existen
$tabla_fichajes_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_fichajes)) === $tabla_fichajes;

if (!$tabla_fichajes_existe) {
    ?>
    <div class="dm-card">
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Módulo en preparación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Fichaje de Empleados aún no han sido creadas. Activa el módulo completamente para generar la estructura necesaria.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Estadísticas principales
$total_fichajes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_fichajes}");
$fichajes_hoy = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_fichajes} WHERE DATE(fecha_entrada) = %s",
    current_time('Y-m-d')
));
$fichajes_mes = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_fichajes} WHERE fecha_entrada >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
);

// Empleados únicos
$empleados_activos = (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_fichajes}");

// Empleados trabajando ahora
$trabajando_ahora = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_fichajes} WHERE fecha_salida IS NULL"
);

// Horas trabajadas (mes actual)
$horas_mes = (float) $wpdb->get_var(
    "SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, fecha_entrada, COALESCE(fecha_salida, NOW()))), 0) / 60
     FROM {$tabla_fichajes}
     WHERE fecha_entrada >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
);

// Promedio horas diarias
$promedio_diario = $fichajes_mes > 0 ? round($horas_mes / max(1, $fichajes_mes) * 60, 1) : 0;

// Fichajes sin salida (potenciales olvidos)
$sin_salida = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_fichajes}
     WHERE fecha_salida IS NULL
     AND DATE(fecha_entrada) < %s",
    current_time('Y-m-d')
));

// Actividad semanal
$actividad_semanal = $wpdb->get_results(
    "SELECT DATE(fecha_entrada) as fecha, COUNT(*) as total
     FROM {$tabla_fichajes}
     WHERE fecha_entrada >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(fecha_entrada)
     ORDER BY fecha ASC"
);

// Empleados más puntuales (más fichajes)
$empleados_top = $wpdb->get_results(
    "SELECT f.usuario_id, u.display_name,
            COUNT(*) as total_fichajes,
            ROUND(AVG(TIMESTAMPDIFF(MINUTE, f.fecha_entrada, f.fecha_salida)) / 60, 1) as promedio_horas
     FROM {$tabla_fichajes} f
     LEFT JOIN {$wpdb->users} u ON u.ID = f.usuario_id
     WHERE f.fecha_entrada >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     AND f.fecha_salida IS NOT NULL
     GROUP BY f.usuario_id
     ORDER BY total_fichajes DESC
     LIMIT 5"
);

// Fichajes recientes
$fichajes_recientes = $wpdb->get_results(
    "SELECT f.*, u.display_name
     FROM {$tabla_fichajes} f
     LEFT JOIN {$wpdb->users} u ON u.ID = f.usuario_id
     ORDER BY f.fecha_entrada DESC
     LIMIT 8"
);

// Distribución por hora de entrada
$por_hora = $wpdb->get_results(
    "SELECT HOUR(fecha_entrada) as hora, COUNT(*) as total
     FROM {$tabla_fichajes}
     WHERE fecha_entrada >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY HOUR(fecha_entrada)
     ORDER BY hora ASC"
);
?>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--primary dm-stat-card--horizontal">
        <span class="dashicons dashicons-clock dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($fichajes_hoy); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Fichajes Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--success dm-stat-card--horizontal">
        <span class="dashicons dashicons-admin-users dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($trabajando_ahora); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Trabajando Ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--purple dm-stat-card--horizontal">
        <span class="dashicons dashicons-groups dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($empleados_activos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Empleados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--info dm-stat-card--horizontal">
        <span class="dashicons dashicons-backup dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($horas_mes, 1)); ?>h</div>
            <div class="dm-stat-card__label"><?php esc_html_e('Horas (Mes)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>
</div>

<?php if ($sin_salida > 0): ?>
<div class="dm-alert dm-alert--warning">
    <span class="dashicons dashicons-warning"></span>
    <div>
        <strong><?php printf(esc_html__('%s fichajes sin salida registrada', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($sin_salida)); ?></strong>
        <span><?php esc_html_e('Pueden ser olvidos de fichaje de salida.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </div>
</div>
<?php endif; ?>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Fichajes Esta Semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <span class="dm-card__subtitle"><?php printf(esc_html__('%s este mes', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($fichajes_mes)); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($actividad_semanal)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('No hay fichajes esta semana.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <?php
                $max_fichajes = max(array_column($actividad_semanal, 'total'));
                $dias_semana = [
                    __('Dom', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Lun', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Mar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Mié', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Jue', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Vie', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Sáb', FLAVOR_PLATFORM_TEXT_DOMAIN)
                ];
                ?>
                <div class="dm-chart-bars">
                    <?php foreach ($actividad_semanal as $dia): ?>
                        <?php
                        $altura = $max_fichajes > 0 ? ($dia->total / $max_fichajes) * 100 : 5;
                        $fecha = new DateTime($dia->fecha);
                        $dia_nombre = $dias_semana[(int)$fecha->format('w')];
                        ?>
                        <div class="dm-chart-bars__item">
                            <span class="dm-chart-bars__value"><?php echo esc_html($dia->total); ?></span>
                            <div class="dm-chart-bars__bar dm-chart-bars__bar--primary" style="height: <?php echo max(4, $altura); ?>px;"></div>
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
                <span class="dashicons dashicons-awards"></span>
                <?php esc_html_e('Empleados Más Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <span class="dm-card__subtitle"><?php esc_html_e('Últimos 30 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($empleados_top)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-admin-users"></span>
                    <p><?php esc_html_e('No hay datos de fichaje todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($empleados_top as $index => $empleado): ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__avatar">
                                <?php echo mb_substr($empleado->display_name ?: __('E', FLAVOR_PLATFORM_TEXT_DOMAIN), 0, 1); ?>
                            </div>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($empleado->display_name ?: __('Empleado', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                                <span class="dm-ranking__meta">
                                    <?php printf(esc_html__('~%sh por fichaje', FLAVOR_PLATFORM_TEXT_DOMAIN), $empleado->promedio_horas); ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--info">
                                <?php echo esc_html($empleado->total_fichajes); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-clock"></span>
                <?php esc_html_e('Fichajes Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($fichajes_recientes)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-clock"></span>
                    <p><?php esc_html_e('No hay fichajes registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-list">
                    <?php foreach ($fichajes_recientes as $fichaje): ?>
                        <li class="dm-list__item">
                            <div class="dm-list__content">
                                <strong class="dm-list__title"><?php echo esc_html($fichaje->display_name ?: __('Empleado', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                                <span class="dm-list__meta">
                                    <?php
                                    $entrada = new DateTime($fichaje->fecha_entrada);
                                    echo esc_html($entrada->format('d/m H:i'));
                                    if ($fichaje->fecha_salida) {
                                        $salida = new DateTime($fichaje->fecha_salida);
                                        echo ' → ' . esc_html($salida->format('H:i'));
                                    }
                                    ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--<?php echo $fichaje->fecha_salida ? 'success' : 'warning'; ?>">
                                <?php echo $fichaje->fecha_salida ? esc_html__('Completado', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Trabajando', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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
                <span class="dashicons dashicons-schedule"></span>
                <?php esc_html_e('Distribución Horaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <span class="dm-card__subtitle"><?php esc_html_e('Hora de entrada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($por_hora)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-schedule"></span>
                    <p><?php esc_html_e('No hay datos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="dm-data-list">
                    <?php
                    $horas_principales = array_slice($por_hora, 0, 6);
                    foreach ($horas_principales as $hora):
                    ?>
                        <div class="dm-data-list__item">
                            <span class="dm-data-list__label"><?php echo esc_html(sprintf('%02d:00 - %02d:59', $hora->hora, $hora->hora)); ?></span>
                            <span class="dm-data-list__value"><?php echo esc_html($hora->total); ?> <?php esc_html_e('fichajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dm-stats-grid dm-stats-grid--3">
    <div class="dm-stat-card dm-stat-card--secondary">
        <span class="dashicons dashicons-database dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_fichajes)); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Total Fichajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--warning">
        <span class="dashicons dashicons-warning dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($sin_salida); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Sin Salida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--info">
        <span class="dashicons dashicons-performance dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($promedio_diario); ?>min</div>
        <div class="dm-stat-card__label"><?php esc_html_e('Promedio/Fichaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>
</div>
