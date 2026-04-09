<?php
/**
 * Vista Dashboard - Modulo Tramites
 *
 * Dashboard administrativo operativo para seguimiento de solicitudes.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_solicitudes = $wpdb->prefix . 'flavor_tramites_solicitudes';
$tabla_tipos = $wpdb->prefix . 'flavor_tramites';

$tabla_solicitudes_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_solicitudes)) === $tabla_solicitudes;
$tabla_tipos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_tipos)) === $tabla_tipos;

// Inicializar variables
$total_solicitudes = 0;
$solicitudes_pendientes = 0;
$solicitudes_urgentes = 0;
$solicitudes_documentacion = 0;
$solicitudes_aprobadas_mes = 0;
$solicitudes_rechazadas_mes = 0;
$solicitudes_hoy = 0;
$tiempo_promedio = 0;
$total_procesadas = 0;
$total_aprobadas = 0;
$tasa_aprobacion = 0;
$pendientes_antiguos = 0;
$sin_asignar = 0;
$solicitudes_recientes = [];
$solicitudes_prioritarias = [];
$tipos_mas_solicitados = [];
$por_estado = [];
$tipos_con_cita = [];
$tablas_disponibles = $tabla_solicitudes_existe;

if ($tabla_solicitudes_existe) {
    $total_solicitudes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes}");
    $solicitudes_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado IN ('pendiente', 'en_revision', 'en_proceso')");
    $solicitudes_urgentes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE prioridad = 'alta' AND estado IN ('pendiente', 'en_revision', 'en_proceso')");
    $solicitudes_documentacion = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado = 'requiere_documentacion'");
    $solicitudes_aprobadas_mes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado = 'aprobada' AND MONTH(fecha_resolucion) = MONTH(CURRENT_DATE()) AND YEAR(fecha_resolucion) = YEAR(CURRENT_DATE())");
    $solicitudes_rechazadas_mes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado = 'rechazada' AND MONTH(fecha_resolucion) = MONTH(CURRENT_DATE()) AND YEAR(fecha_resolucion) = YEAR(CURRENT_DATE())");
    $solicitudes_hoy = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE fecha_solicitud >= %s",
        current_time('Y-m-d 00:00:00')
    ));

    $tiempo_promedio = (float) $wpdb->get_var(
        "SELECT AVG(DATEDIFF(fecha_resolucion, fecha_solicitud))
         FROM {$tabla_solicitudes}
         WHERE estado = 'aprobada' AND fecha_resolucion IS NOT NULL"
    );

    $total_procesadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado IN ('aprobada', 'rechazada')");
    $total_aprobadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado = 'aprobada'");
    $tasa_aprobacion = $total_procesadas > 0 ? round(($total_aprobadas / $total_procesadas) * 100, 1) : 0;

    $pendientes_antiguos = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_solicitudes}
         WHERE estado IN ('pendiente', 'en_revision', 'en_proceso')
           AND fecha_solicitud < %s",
        gmdate('Y-m-d H:i:s', strtotime('-5 days', current_time('timestamp', true)))
    ));

    $sin_asignar = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_solicitudes}
         WHERE estado IN ('pendiente', 'en_revision', 'en_proceso')
           AND (asignado_a IS NULL OR asignado_a = 0)"
    );

    $solicitudes_recientes = $wpdb->get_results(
        "SELECT id, numero_solicitud, fecha_solicitud, nombre_solicitante, estado
         FROM {$tabla_solicitudes}
         ORDER BY fecha_solicitud DESC
         LIMIT 8"
    );

    $solicitudes_prioritarias = $wpdb->get_results(
        "SELECT id, numero_solicitud, tipo_tramite, estado, prioridad, fecha_solicitud
         FROM {$tabla_solicitudes}
         WHERE estado IN ('pendiente', 'en_revision', 'en_proceso', 'requiere_documentacion')
         ORDER BY
            CASE prioridad WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END,
            fecha_solicitud ASC
         LIMIT 8"
    );

    $tipos_mas_solicitados = $wpdb->get_results(
        "SELECT tipo_tramite, COUNT(*) as total
         FROM {$tabla_solicitudes}
         GROUP BY tipo_tramite
         ORDER BY total DESC
         LIMIT 6"
    );

    $por_estado = $wpdb->get_results(
        "SELECT estado, COUNT(*) as total
         FROM {$tabla_solicitudes}
         GROUP BY estado
         ORDER BY total DESC"
    );

    if ($tabla_tipos_existe) {
        $tipos_con_cita = $wpdb->get_results(
            "SELECT nombre, categoria, plazo_resolucion_dias
             FROM {$tabla_tipos}
             WHERE requiere_cita = 1
             ORDER BY orden ASC, nombre ASC
             LIMIT 6"
        );
    }
}

// Mapeos de estado y prioridad a clases dm-badge
$estado_labels = [
    'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'en_revision' => __('En revisión', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'en_proceso' => __('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'requiere_documentacion' => __('Pendiente doc.', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'aprobada' => __('Aprobada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'rechazada' => __('Rechazada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'borrador' => __('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$estado_badge_classes = [
    'pendiente' => 'dm-badge--warning',
    'en_revision' => 'dm-badge--info',
    'en_proceso' => 'dm-badge--info',
    'requiere_documentacion' => 'dm-badge--purple',
    'aprobada' => 'dm-badge--success',
    'rechazada' => 'dm-badge--error',
    'borrador' => 'dm-badge--secondary',
];

$prioridad_badge_classes = [
    'alta' => 'dm-badge--error',
    'media' => 'dm-badge--warning',
    'baja' => 'dm-badge--success',
];
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('tramites');
    }
    ?>

    <?php if (!$tablas_disponibles): ?>
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <p><?php esc_html_e('Faltan tablas del módulo Trámites o aún no hay solicitudes registradas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php endif; ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-text-page"></span>
            <h1><?php esc_html_e('Dashboard de Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=tramites-pendientes')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-clock"></span>
            <span><?php esc_html_e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            <?php if ($solicitudes_pendientes > 0): ?>
                <span class="dm-badge dm-badge--warning"><?php echo number_format_i18n($solicitudes_pendientes); ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=tramites-historial')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-backup"></span>
            <span><?php esc_html_e('Historial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=tramites-tipos')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-category"></span>
            <span><?php esc_html_e('Tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=tramites-config')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-settings"></span>
            <span><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('tramites', '')); ?>" class="dm-quick-links__item" target="_blank">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal ciudadano', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
    </div>

    <!-- KPIs principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_solicitudes); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Solicitudes totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div class="dm-stat-card__meta"><?php esc_html_e('histórico acumulado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-text-page"></span></div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($solicitudes_pendientes); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('En curso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div class="dm-stat-card__meta"><?php printf(esc_html__('%s urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($solicitudes_urgentes)); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-clock"></span></div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($solicitudes_aprobadas_mes); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Aprobadas (mes)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div class="dm-stat-card__meta"><?php printf(esc_html__('%s%% aprobación', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($tasa_aprobacion, 1)); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-yes-alt"></span></div>
        </div>

        <div class="dm-stat-card">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($tiempo_promedio ? round($tiempo_promedio) : 0); ?>d</div>
            <div class="dm-stat-card__label"><?php esc_html_e('Resolución media', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div class="dm-stat-card__meta"><?php printf(esc_html__('%s nuevas hoy', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($solicitudes_hoy)); ?></div>
            <div class="dm-stat-card__icon"><span class="dashicons dashicons-performance"></span></div>
        </div>
    </div>

    <!-- Alertas y distribución -->
    <div class="dm-grid dm-grid--2">
        <!-- Alertas de servicio -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Alertas de servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-focus-list">
                <div class="dm-focus-list__item <?php echo $solicitudes_urgentes > 0 ? 'dm-focus-list__item--error' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Urgentes sin cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($solicitudes_urgentes); ?></span>
                </div>
                <div class="dm-focus-list__item <?php echo $solicitudes_documentacion > 0 ? 'dm-focus-list__item--warning' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Pendientes de documentación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($solicitudes_documentacion); ?></span>
                </div>
                <div class="dm-focus-list__item <?php echo $pendientes_antiguos > 0 ? 'dm-focus-list__item--warning' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Expedientes > 5 días en cola', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($pendientes_antiguos); ?></span>
                </div>
                <div class="dm-focus-list__item <?php echo $sin_asignar > 0 ? 'dm-focus-list__item--info' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Sin asignación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($sin_asignar); ?></span>
                </div>
            </div>
        </div>

        <!-- Resolución del mes -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Resolución del mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-stats-grid dm-stats-grid--3" style="margin-bottom: 0;">
                <div class="dm-stat-card dm-stat-card--success" style="margin-bottom: 0;">
                    <div class="dm-stat-card__value"><?php echo number_format_i18n($solicitudes_aprobadas_mes); ?></div>
                    <div class="dm-stat-card__label"><?php esc_html_e('Aprobadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="dm-stat-card dm-stat-card--error" style="margin-bottom: 0;">
                    <div class="dm-stat-card__value"><?php echo number_format_i18n($solicitudes_rechazadas_mes); ?></div>
                    <div class="dm-stat-card__label"><?php esc_html_e('Rechazadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="dm-stat-card" style="margin-bottom: 0;">
                    <div class="dm-stat-card__value"><?php echo number_format_i18n($total_procesadas); ?></div>
                    <div class="dm-stat-card__label"><?php esc_html_e('Total procesadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Distribución y ranking -->
    <div class="dm-grid dm-grid--2">
        <!-- Por estado -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Distribución por estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <?php if (!empty($por_estado)): ?>
                <div class="dm-badge-list">
                    <?php foreach ($por_estado as $row): ?>
                        <div class="dm-badge-list__item">
                            <span class="dm-badge <?php echo esc_attr($estado_badge_classes[$row->estado] ?? 'dm-badge--secondary'); ?>">
                                <?php echo esc_html($estado_labels[$row->estado] ?? ucfirst((string) $row->estado)); ?>
                            </span>
                            <span class="dm-badge-list__value"><?php echo number_format_i18n((int) $row->total); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <p><?php esc_html_e('Sin datos de estado todavía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tipos más demandados -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Tipos más demandados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <?php if (!empty($tipos_mas_solicitados)): ?>
                <ol class="dm-ranking">
                    <?php foreach ($tipos_mas_solicitados as $tipo): ?>
                        <li>
                            <span><?php echo esc_html($tipo->tipo_tramite ?: __('Sin tipo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                            <strong><?php echo number_format_i18n((int) $tipo->total); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <p><?php esc_html_e('Sin datos suficientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tablas de expedientes -->
    <div class="dm-grid dm-grid--2">
        <!-- Cola prioritaria -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Cola prioritaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=tramites-pendientes')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <?php if (!empty($solicitudes_prioritarias)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Expediente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Prioridad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes_prioritarias as $solicitud): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($solicitud->numero_solicitud ?: '#' . $solicitud->id); ?></strong>
                                    <span class="dm-table__subtitle"><?php echo esc_html($solicitud->tipo_tramite ?: __('Sin tipo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                                </td>
                                <td>
                                    <span class="dm-badge <?php echo esc_attr($estado_badge_classes[$solicitud->estado] ?? 'dm-badge--secondary'); ?>">
                                        <?php echo esc_html($estado_labels[$solicitud->estado] ?? ucfirst((string) $solicitud->estado)); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="dm-badge <?php echo esc_attr($prioridad_badge_classes[$solicitud->prioridad] ?? 'dm-badge--success'); ?>">
                                        <?php echo esc_html(ucfirst((string) ($solicitud->prioridad ?: 'baja'))); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <p><?php esc_html_e('No hay expedientes en la cola prioritaria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Actividad reciente -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Actividad reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=tramites-historial')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Ver historial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <?php if (!empty($solicitudes_recientes)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Solicitante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes_recientes as $solicitud): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($solicitud->numero_solicitud ?: '#' . $solicitud->id); ?></strong>
                                    <span class="dm-table__subtitle"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($solicitud->fecha_solicitud))); ?></span>
                                </td>
                                <td><?php echo esc_html($solicitud->nombre_solicitante ?: __('Sin nombre', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></td>
                                <td>
                                    <span class="dm-badge <?php echo esc_attr($estado_badge_classes[$solicitud->estado] ?? 'dm-badge--secondary'); ?>">
                                        <?php echo esc_html($estado_labels[$solicitud->estado] ?? ucfirst((string) $solicitud->estado)); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-clipboard"></span>
                    <p><?php esc_html_e('No hay solicitudes recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="dm-grid dm-grid--2">
        <!-- Tipos con cita -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Tipos que requieren cita', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <?php if (!empty($tipos_con_cita)): ?>
                <div class="dm-item-list">
                    <?php foreach ($tipos_con_cita as $tipo): ?>
                        <div class="dm-item-list__item">
                            <div class="dm-item-list__content">
                                <strong><?php echo esc_html($tipo->nombre); ?></strong>
                                <span class="dm-item-list__subtitle"><?php echo esc_html($tipo->categoria ?: __('Sin categoría', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                            </div>
                            <div class="dm-item-list__meta">
                                <span class="dm-badge dm-badge--info"><?php printf(esc_html__('%s días', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n((int) $tipo->plazo_resolucion_dias)); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php esc_html_e('No hay tipos con cita obligatoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recomendaciones -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Enfoque recomendado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-alert dm-alert--info" style="margin-bottom: 12px;">
                <span class="dashicons dashicons-info-outline dm-alert__icon"></span>
                <div class="dm-alert__content">
                    <?php esc_html_e('Vaciar primero la cola urgente y los expedientes pendientes de documentación.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </div>
            </div>
            <div class="dm-alert dm-alert--warning" style="margin-bottom: 12px;">
                <span class="dashicons dashicons-warning dm-alert__icon"></span>
                <div class="dm-alert__content">
                    <?php esc_html_e('Revisar expedientes sin asignar para evitar cuellos de botella invisibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </div>
            </div>
            <div class="dm-alert dm-alert--success" style="margin-bottom: 0;">
                <span class="dashicons dashicons-yes dm-alert__icon"></span>
                <div class="dm-alert__content">
                    <?php esc_html_e('Controlar el tiempo medio de resolución por encima del objetivo operativo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </div>
            </div>
        </div>
    </div>
</div>
