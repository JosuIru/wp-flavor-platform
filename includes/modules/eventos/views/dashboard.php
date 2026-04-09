<?php
/**
 * Dashboard de Eventos
 *
 * Dashboard administrativo operativo para programación, ocupación e inscripciones.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_eventos = $wpdb->prefix . 'flavor_eventos';
$tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';

$tabla_eventos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_eventos)) === $tabla_eventos;
$tabla_inscripciones_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_inscripciones)) === $tabla_inscripciones;

if (!$tabla_eventos_existe) {
    echo '<div class="wrap"><div class="dm-alert dm-alert--warning"><span class="dashicons dashicons-warning"></span>' . esc_html__('La tabla principal de eventos no está disponible en esta instalación.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div></div>';
    return;
}

$ahora = current_time('mysql');
$inicio_mes = gmdate('Y-m-01 00:00:00', current_time('timestamp', true));
$en_7_dias = gmdate('Y-m-d H:i:s', strtotime('+7 days', current_time('timestamp', true)));
$hace_7_dias = gmdate('Y-m-d H:i:s', strtotime('-7 days', current_time('timestamp', true)));

$total_eventos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_eventos}");
$proximos_publicados = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_eventos} WHERE estado = 'publicado' AND fecha_inicio >= %s",
    $ahora
));
$eventos_semana = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_eventos}
     WHERE estado = 'publicado'
       AND fecha_inicio BETWEEN %s AND %s",
    $ahora,
    $en_7_dias
));
$inscripcion_abierta = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_eventos}
     WHERE estado = 'publicado'
       AND requiere_inscripcion = 1
       AND inscripcion_abierta = 1"
);
$eventos_destacados = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_eventos} WHERE estado = 'publicado' AND es_destacado = 1");
$eventos_borrador = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_eventos} WHERE estado = 'borrador'");
$eventos_cancelados_mes = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_eventos} WHERE estado = 'cancelado' AND updated_at >= %s",
    $inicio_mes
));
$eventos_sin_ubicacion = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_eventos}
     WHERE estado = 'publicado'
       AND ubicacion_tipo != 'online'
       AND (ubicacion_nombre IS NULL OR ubicacion_nombre = '')"
);

$ocupacion_total = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(inscritos_count), 0)
     FROM {$tabla_eventos}
     WHERE estado = 'publicado' AND fecha_inicio >= %s",
    $ahora
));

$aforo_total = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(aforo_maximo), 0)
     FROM {$tabla_eventos}
     WHERE estado = 'publicado' AND fecha_inicio >= %s AND aforo_maximo > 0",
    $ahora
));

$ocupacion_media = $aforo_total > 0 ? round(($ocupacion_total / $aforo_total) * 100, 1) : 0;

$inscripciones_pendientes = 0;
$inscripciones_confirmadas_mes = 0;
$ingresos_mes = 0.0;
$actividad_inscripciones = [];

if ($tabla_inscripciones_existe) {
    $inscripciones_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_inscripciones} WHERE estado = 'pendiente'");
    $inscripciones_confirmadas_mes = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_inscripciones} WHERE estado = 'confirmada' AND created_at >= %s",
        $inicio_mes
    ));
    $ingresos_mes = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(precio_pagado), 0) FROM {$tabla_inscripciones} WHERE estado = 'confirmada' AND created_at >= %s",
        $inicio_mes
    ));
    $actividad_inscripciones = $wpdb->get_results($wpdb->prepare(
        "SELECT i.*, e.titulo as evento_titulo, e.fecha_inicio, e.aforo_maximo, e.inscritos_count
         FROM {$tabla_inscripciones} i
         LEFT JOIN {$tabla_eventos} e ON e.id = i.evento_id
         WHERE i.created_at >= %s
         ORDER BY i.created_at DESC
         LIMIT 8",
        $hace_7_dias
    ));
}

$proximos_eventos = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$tabla_eventos}
     WHERE estado = 'publicado' AND fecha_inicio >= %s
     ORDER BY fecha_inicio ASC
     LIMIT 8",
    $ahora
));

$eventos_presion = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$tabla_eventos}
     WHERE estado = 'publicado' AND fecha_inicio >= %s
     ORDER BY
        CASE
            WHEN aforo_maximo > 0 AND inscritos_count >= aforo_maximo THEN 1
            WHEN fecha_inicio <= %s THEN 2
            ELSE 3
        END,
        fecha_inicio ASC
     LIMIT 8",
    $ahora,
    $en_7_dias
));

$por_categoria = $wpdb->get_results(
    "SELECT categoria, COUNT(*) as total
     FROM {$tabla_eventos}
     GROUP BY categoria
     ORDER BY total DESC
     LIMIT 6"
);

$por_estado = $wpdb->get_results(
    "SELECT estado, COUNT(*) as total
     FROM {$tabla_eventos}
     GROUP BY estado
     ORDER BY total DESC"
);

$mensual = $wpdb->get_results(
    "SELECT DATE_FORMAT(fecha_inicio, '%Y-%m') as periodo, COUNT(*) as total
     FROM {$tabla_eventos}
     WHERE fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
     GROUP BY DATE_FORMAT(fecha_inicio, '%Y-%m')
     ORDER BY periodo ASC"
);

$estado_labels = [
    'borrador' => __('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'publicado' => __('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cancelado' => __('Cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'finalizado' => __('Finalizado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'pospuesto' => __('Pospuesto', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$estado_badges = [
    'borrador' => 'dm-badge--secondary',
    'publicado' => 'dm-badge--success',
    'cancelado' => 'dm-badge--error',
    'finalizado' => 'dm-badge--info',
    'pospuesto' => 'dm-badge--warning',
];
?>

<div class="wrap dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('eventos');
    }
    ?>

    <!-- Cabecera -->
    <div class="dm-header">
        <div class="dm-header__content">
            <h1 class="dm-header__title">
                <span class="dashicons dashicons-calendar"></span>
                <?php esc_html_e('Dashboard de Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>
            <p class="dm-header__description">
                <?php esc_html_e('Panel operativo para programar eventos, controlar inscripciones y detectar cuellos de botella de ocupación.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=eventos-nuevo')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nuevo evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-card">
        <h2 class="dm-card__title">
            <span class="dashicons dashicons-admin-links"></span> <?php esc_html_e('Accesos Rápidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h2>
        <div class="dm-action-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=eventos-proximos')); ?>" class="dm-action-card">
                <span class="dashicons dashicons-calendar-alt dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Próximos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php if ($eventos_semana > 0): ?>
                    <span class="dm-badge dm-badge--warning"><?php echo $eventos_semana; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=eventos-calendario')); ?>" class="dm-action-card dm-action-card--success">
                <span class="dashicons dashicons-calendar dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Calendario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=eventos-asistentes')); ?>" class="dm-action-card dm-action-card--warning">
                <span class="dashicons dashicons-groups dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Asistentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php if ($inscripciones_pendientes > 0): ?>
                    <span class="dm-badge dm-badge--error"><?php echo $inscripciones_pendientes; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=eventos-config')); ?>" class="dm-action-card">
                <span class="dashicons dashicons-admin-settings dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/mi-portal/eventos/')); ?>" class="dm-action-card dm-action-card--purple">
                <span class="dashicons dashicons-external dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Portal público', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-calendar"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($proximos_publicados); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Próximos publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <small class="dm-text-muted"><?php printf(esc_html__('%s esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($eventos_semana)); ?></small>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-edit"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($inscripcion_abierta); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Inscripciones abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <small class="dm-text-muted"><?php printf(esc_html__('%s pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($inscripciones_pendientes)); ?></small>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($inscripciones_confirmadas_mes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Confirmadas este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <small class="dm-text-muted"><?php echo esc_html(number_format_i18n($ingresos_mes, 2)); ?> EUR</small>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-chart-pie"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($ocupacion_media, 1); ?>%</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Ocupación media', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <small class="dm-text-muted"><?php printf(esc_html__('%s personas previstas', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($ocupacion_total)); ?></small>
            </div>
        </div>
    </div>

    <!-- Alertas operativas -->
    <?php if ($inscripciones_pendientes > 0 || $eventos_sin_ubicacion > 0): ?>
    <div class="dm-alert dm-alert--warning">
        <span class="dashicons dashicons-warning"></span>
        <div>
            <strong><?php esc_html_e('Alertas operativas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
            <ul style="margin: 8px 0 0; padding-left: 20px;">
                <?php if ($inscripciones_pendientes > 0): ?>
                    <li><?php printf(esc_html__('%s inscripciones pendientes de gestión', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($inscripciones_pendientes)); ?></li>
                <?php endif; ?>
                <?php if ($eventos_sin_ubicacion > 0): ?>
                    <li><?php printf(esc_html__('%s eventos presenciales sin ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($eventos_sin_ubicacion)); ?></li>
                <?php endif; ?>
                <?php if ($eventos_borrador > 0): ?>
                    <li><?php printf(esc_html__('%s borradores pendientes de publicar', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($eventos_borrador)); ?></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Grid de paneles -->
    <div class="dm-grid dm-grid--3">
        <!-- Distribución por estado -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-tag"></span> <?php esc_html_e('Por estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <?php if (!empty($por_estado)): ?>
                <div class="dm-badge-list">
                    <?php foreach ($por_estado as $row): ?>
                        <div class="dm-badge-item">
                            <span class="dm-badge <?php echo esc_attr($estado_badges[$row->estado] ?? 'dm-badge--secondary'); ?>">
                                <?php echo esc_html($estado_labels[$row->estado] ?? ucfirst((string) $row->estado)); ?>
                            </span>
                            <strong><?php echo number_format_i18n((int) $row->total); ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="dm-text-muted"><?php esc_html_e('Sin estados registrados todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php endif; ?>
        </div>

        <!-- Categorías -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-category"></span> <?php esc_html_e('Top categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <?php if (!empty($por_categoria)): ?>
                <ol class="dm-ranking">
                    <?php foreach ($por_categoria as $categoria): ?>
                        <li>
                            <span><?php echo esc_html($categoria->categoria ?: __('Sin categoría', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                            <strong><?php echo number_format_i18n((int) $categoria->total); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="dm-text-muted"><?php esc_html_e('Aún no hay categorías con actividad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php endif; ?>
        </div>

        <!-- Tendencia mensual -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-line"></span> <?php esc_html_e('Tendencia mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <?php if (!empty($mensual)): ?>
                <div class="dm-trend">
                    <?php
                    $max_mes = max(array_map(static function ($row) {
                        return (int) $row->total;
                    }, $mensual));
                    foreach ($mensual as $mes):
                        $altura = $max_mes > 0 ? max(20, (int) round(((int) $mes->total / $max_mes) * 100)) : 20;
                    ?>
                        <div class="dm-trend__item">
                            <div class="dm-trend__bar" style="height: <?php echo esc_attr($altura); ?>px;"></div>
                            <strong><?php echo number_format_i18n((int) $mes->total); ?></strong>
                            <small><?php echo esc_html(date_i18n('M', strtotime($mes->periodo . '-01'))); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="dm-text-muted"><?php esc_html_e('No hay histórico suficiente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tablas -->
    <div class="dm-grid dm-grid--2">
        <!-- Próximos eventos -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Próximos eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <?php if (!empty($proximos_eventos)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Ocupación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($proximos_eventos, 0, 5) as $evento):
                            $aforo_maximo = (int) $evento->aforo_maximo;
                            $inscritos = (int) $evento->inscritos_count;
                            $ocupacion = $aforo_maximo > 0 ? min(100, round(($inscritos / max(1, $aforo_maximo)) * 100)) : 0;
                            $clase_ocupacion = $ocupacion >= 90 ? 'dm-badge--error' : ($ocupacion >= 70 ? 'dm-badge--warning' : 'dm-badge--success');
                            $categoria_evento = isset($evento->categoria) && $evento->categoria !== '' ? (string) $evento->categoria : __('Sin categoría', FLAVOR_PLATFORM_TEXT_DOMAIN);
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html(wp_trim_words($evento->titulo, 5)); ?></strong>
                                    <div class="dm-table__subtitle"><?php echo esc_html($categoria_evento); ?></div>
                                </td>
                                <td>
                                    <span class="dm-badge dm-badge--info">
                                        <?php echo esc_html(date_i18n('d M', strtotime($evento->fecha_inicio))); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($aforo_maximo > 0): ?>
                                        <div class="dm-progress-wrapper">
                                            <div class="dm-progress" style="width: 60px;">
                                                <div class="dm-progress__bar" style="width: <?php echo $ocupacion; ?>%; background: <?php echo $ocupacion >= 90 ? 'var(--dm-error)' : ($ocupacion >= 70 ? 'var(--dm-warning)' : 'var(--dm-success)'); ?>;"></div>
                                            </div>
                                            <span class="dm-progress__label"><?php echo $ocupacion; ?>%</span>
                                        </div>
                                    <?php else: ?>
                                        <span class="dm-text-muted"><?php echo number_format_i18n($inscritos); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="dm-text-muted"><?php esc_html_e('No hay eventos futuros publicados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php endif; ?>
            <div class="dm-card__footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=eventos-proximos')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Ver agenda completa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>

        <!-- Actividad reciente -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-groups"></span> <?php esc_html_e('Inscripciones recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <?php if (!empty($actividad_inscripciones)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Asistente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $estado_inscripcion_badges = [
                            'pendiente' => 'dm-badge--warning',
                            'confirmada' => 'dm-badge--success',
                            'cancelada' => 'dm-badge--error',
                        ];
                        foreach (array_slice($actividad_inscripciones, 0, 5) as $inscripcion):
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($inscripcion->nombre); ?></strong>
                                    <div class="dm-table__subtitle"><?php echo esc_html($inscripcion->email); ?></div>
                                </td>
                                <td class="dm-table__muted">
                                    <?php echo esc_html(wp_trim_words($inscripcion->evento_titulo ?: __('Evento eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN), 4)); ?>
                                </td>
                                <td>
                                    <span class="dm-badge <?php echo esc_attr($estado_inscripcion_badges[$inscripcion->estado] ?? 'dm-badge--secondary'); ?>">
                                        <?php echo esc_html(ucfirst((string) $inscripcion->estado)); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="dm-text-muted"><?php esc_html_e('No hay movimiento reciente de inscripciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php endif; ?>
            <div class="dm-card__footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=eventos-asistentes')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Gestionar asistentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Resumen -->
    <div class="dm-card">
        <h3 class="dm-card__title">
            <span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e('Foco recomendado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h3>
        <div class="dm-focus-list">
            <div class="dm-focus-item">
                <span class="dm-focus-item__value"><?php echo number_format_i18n($eventos_destacados); ?></span>
                <span class="dm-focus-item__label"><?php esc_html_e('eventos destacados para difusión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="dm-focus-item">
                <span class="dm-focus-item__value"><?php echo number_format_i18n($inscripcion_abierta); ?></span>
                <span class="dm-focus-item__label"><?php esc_html_e('eventos con inscripción abierta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="dm-focus-item">
                <span class="dm-focus-item__value"><?php echo number_format_i18n($ocupacion_media, 1); ?>%</span>
                <span class="dm-focus-item__label"><?php esc_html_e('ocupación media prevista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="dm-focus-item">
                <span class="dm-focus-item__value"><?php echo number_format_i18n($total_eventos); ?></span>
                <span class="dm-focus-item__label"><?php esc_html_e('eventos totales en el sistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>
</div>
