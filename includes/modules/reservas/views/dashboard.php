<?php
/**
 * Vista Dashboard - Reservas
 *
 * Dashboard administrativo operativo para reservas y recursos.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_reservas = $wpdb->prefix . 'flavor_reservas';
$tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

$tabla_reservas_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_reservas)) === $tabla_reservas;
$tabla_recursos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_recursos)) === $tabla_recursos;

if (!$tabla_reservas_existe) {
    echo '<div class="dm-alert dm-alert--warning">' . esc_html__('La tabla principal de reservas no está disponible en esta instalación.', 'flavor-chat-ia') . '</div>';
    return;
}

$hoy = current_time('Y-m-d');
$ahora = current_time('mysql');
$en_48h = gmdate('Y-m-d H:i:s', strtotime('+48 hours', current_time('timestamp', true)));
$hace_7_dias = gmdate('Y-m-d H:i:s', strtotime('-7 days', current_time('timestamp', true)));
$inicio_mes = gmdate('Y-m-01 00:00:00', current_time('timestamp', true));

$total_reservas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_reservas}");
$reservas_hoy = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_reservas} WHERE fecha_reserva = %s",
    $hoy
));
$reservas_activas = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_reservas} WHERE fecha_inicio >= %s AND estado IN ('pendiente', 'confirmada')",
    $ahora
));
$reservas_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_reservas} WHERE estado = 'pendiente'");
$reservas_confirmadas_mes = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_reservas} WHERE estado = 'confirmada' AND created_at >= %s",
    $inicio_mes
));
$reservas_canceladas_mes = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_reservas} WHERE estado = 'cancelada' AND created_at >= %s",
    $inicio_mes
));
$reservas_completadas_mes = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_reservas} WHERE estado = 'completada' AND created_at >= %s",
    $inicio_mes
));

$pendientes_vencidas = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_reservas}
     WHERE estado = 'pendiente'
       AND fecha_inicio < %s",
    $ahora
));

$reservas_sin_recurso = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_reservas}
     WHERE (recurso_id IS NULL OR recurso_id = 0)
       AND estado IN ('pendiente', 'confirmada')"
);

$entradas_48h = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_reservas}
     WHERE fecha_inicio BETWEEN %s AND %s
       AND estado IN ('pendiente', 'confirmada')",
    $ahora,
    $en_48h
));

$total_personas_mes = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(num_personas), 0)
     FROM {$tabla_reservas}
     WHERE created_at >= %s
       AND estado IN ('pendiente', 'confirmada', 'completada')",
    $inicio_mes
));

$total_recursos = 0;
$recursos_activos = 0;
$recursos_con_reservas = [];

if ($tabla_recursos_existe) {
    $total_recursos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_recursos}");
    $recursos_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_recursos} WHERE estado = 'activo' AND activo = 1");
    $recursos_con_reservas = $wpdb->get_results(
        "SELECT r.id, r.nombre, r.tipo, COUNT(rv.id) as total
         FROM {$tabla_recursos} r
         LEFT JOIN {$tabla_reservas} rv ON rv.recurso_id = r.id
         WHERE r.estado = 'activo' AND r.activo = 1
         GROUP BY r.id, r.nombre, r.tipo
         ORDER BY total DESC, r.nombre ASC
         LIMIT 6"
    );
}

$promedio_personas = $total_reservas > 0 ? round($total_personas_mes / max(1, ($reservas_confirmadas_mes + $reservas_pendientes + $reservas_completadas_mes)), 1) : 0;

$por_estado = $wpdb->get_results(
    "SELECT estado, COUNT(*) as total
     FROM {$tabla_reservas}
     GROUP BY estado
     ORDER BY total DESC"
);

$por_tipo_servicio = $wpdb->get_results(
    "SELECT tipo_servicio, COUNT(*) as total
     FROM {$tabla_reservas}
     GROUP BY tipo_servicio
     ORDER BY total DESC
     LIMIT 6"
);

$sql_recurso_select = $tabla_recursos_existe ? 'r.nombre as recurso_nombre, r.tipo as recurso_tipo' : "'' as recurso_nombre, '' as recurso_tipo";
$sql_recurso_join = $tabla_recursos_existe ? "LEFT JOIN {$tabla_recursos} r ON r.id = rv.recurso_id" : '';

$proximas_reservas = $wpdb->get_results($wpdb->prepare(
    "SELECT rv.*, {$sql_recurso_select}
     FROM {$tabla_reservas} rv
     {$sql_recurso_join}
     WHERE rv.fecha_inicio >= %s
     ORDER BY rv.fecha_inicio ASC
     LIMIT 8",
    $ahora
));

$cola_prioritaria = $wpdb->get_results($wpdb->prepare(
    "SELECT rv.*, {$sql_recurso_select}
     FROM {$tabla_reservas} rv
     {$sql_recurso_join}
     WHERE rv.estado IN ('pendiente', 'confirmada')
     ORDER BY
        CASE rv.estado WHEN 'pendiente' THEN 1 ELSE 2 END,
        CASE WHEN rv.fecha_inicio < %s THEN 1 ELSE 2 END,
        rv.fecha_inicio ASC
     LIMIT 8",
    $ahora
));

$actividad_reciente = $wpdb->get_results($wpdb->prepare(
    "SELECT rv.*, {$sql_recurso_select}
     FROM {$tabla_reservas} rv
     {$sql_recurso_join}
     WHERE rv.updated_at >= %s
     ORDER BY rv.updated_at DESC
     LIMIT 8",
    $hace_7_dias
));

$mensual = $wpdb->get_results(
    "SELECT DATE_FORMAT(fecha_reserva, '%Y-%m') as periodo, COUNT(*) as total
     FROM {$tabla_reservas}
     WHERE fecha_reserva >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
     GROUP BY DATE_FORMAT(fecha_reserva, '%Y-%m')
     ORDER BY periodo ASC"
);

$estado_labels = [
    'pendiente' => __('Pendiente', 'flavor-chat-ia'),
    'confirmada' => __('Confirmada', 'flavor-chat-ia'),
    'cancelada' => __('Cancelada', 'flavor-chat-ia'),
    'completada' => __('Completada', 'flavor-chat-ia'),
];

$estado_badge_classes = [
    'pendiente' => 'dm-badge--warning',
    'confirmada' => 'dm-badge--success',
    'cancelada' => 'dm-badge--error',
    'completada' => 'dm-badge--info',
];
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('reservas');
    }
    ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-calendar-alt"></span>
            <h1><?php esc_html_e('Dashboard de Reservas', 'flavor-chat-ia'); ?></h1>
        </div>
        <p class="dm-header__description">
            <?php esc_html_e('Panel operativo para controlar la cola de reservas, el uso de recursos y la carga próxima del servicio.', 'flavor-chat-ia'); ?>
        </p>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-calendario')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-calendar-alt"></span>
            <span><?php esc_html_e('Calendario', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-listado')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-list-view"></span>
            <span><?php esc_html_e('Todas las reservas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-nueva')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-plus-alt"></span>
            <span><?php esc_html_e('Nueva reserva', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-recursos')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-building"></span>
            <span><?php esc_html_e('Recursos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-config')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-settings"></span>
            <span><?php esc_html_e('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/mi-portal/reservas/')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal público', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- KPIs Principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($reservas_activas); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Reservas activas', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php printf(esc_html__('%s entradas en 48h', 'flavor-chat-ia'), number_format_i18n($entradas_48h)); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($reservas_pendientes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php printf(esc_html__('%s vencidas', 'flavor-chat-ia'), number_format_i18n($pendientes_vencidas)); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($reservas_confirmadas_mes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Confirmadas este mes', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php printf(esc_html__('%s completadas', 'flavor-chat-ia'), number_format_i18n($reservas_completadas_mes)); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-building"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($recursos_activos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Recursos activos', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php printf(esc_html__('%s reservas totales', 'flavor-chat-ia'), number_format_i18n($total_reservas)); ?></div>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas + Alertas -->
    <div class="dm-grid dm-grid--2">
        <!-- Acciones Rápidas -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Acciones rápidas', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="dm-action-grid dm-action-grid--2">
                <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-listado&estado=pendiente')); ?>" class="dm-action-card <?php echo $reservas_pendientes > 0 ? 'dm-action-card--warning' : ''; ?>">
                    <span class="dashicons dashicons-clock"></span>
                    <div class="dm-action-card__content">
                        <strong><?php esc_html_e('Cola pendiente', 'flavor-chat-ia'); ?></strong>
                        <span><?php esc_html_e('Revisar solicitudes pendientes', 'flavor-chat-ia'); ?></span>
                    </div>
                    <?php if ($reservas_pendientes > 0) : ?>
                        <span class="dm-badge dm-badge--warning"><?php echo number_format_i18n($reservas_pendientes); ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-calendario')); ?>" class="dm-action-card dm-action-card--primary">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <div class="dm-action-card__content">
                        <strong><?php esc_html_e('Calendario', 'flavor-chat-ia'); ?></strong>
                        <span><?php esc_html_e('Ver ocupación y próximas franjas', 'flavor-chat-ia'); ?></span>
                    </div>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-recursos')); ?>" class="dm-action-card dm-action-card--success">
                    <span class="dashicons dashicons-building"></span>
                    <div class="dm-action-card__content">
                        <strong><?php esc_html_e('Recursos', 'flavor-chat-ia'); ?></strong>
                        <span><?php esc_html_e('Gestionar espacios y capacidad', 'flavor-chat-ia'); ?></span>
                    </div>
                    <?php if ($recursos_activos > 0) : ?>
                        <span class="dm-badge dm-badge--success"><?php echo number_format_i18n($recursos_activos); ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-nueva')); ?>" class="dm-action-card dm-action-card--primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <div class="dm-action-card__content">
                        <strong><?php esc_html_e('Nueva reserva', 'flavor-chat-ia'); ?></strong>
                        <span><?php esc_html_e('Registrar una reserva desde administración', 'flavor-chat-ia'); ?></span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Panel de Alertas -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Alertas operativas', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="dm-focus-list">
                <div class="dm-focus-list__item <?php echo $pendientes_vencidas > 0 ? 'dm-focus-list__item--error' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Pendientes con fecha ya vencida', 'flavor-chat-ia'); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($pendientes_vencidas); ?></span>
                </div>
                <div class="dm-focus-list__item <?php echo $reservas_sin_recurso > 0 ? 'dm-focus-list__item--error' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Reservas sin recurso asignado', 'flavor-chat-ia'); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($reservas_sin_recurso); ?></span>
                </div>
                <div class="dm-focus-list__item <?php echo $reservas_canceladas_mes > 0 ? 'dm-focus-list__item--warning' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Canceladas este mes', 'flavor-chat-ia'); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($reservas_canceladas_mes); ?></span>
                </div>
                <div class="dm-focus-list__item <?php echo $entradas_48h > 0 ? 'dm-focus-list__item--warning' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Entradas previstas en 48 horas', 'flavor-chat-ia'); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($entradas_48h); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid de 3 columnas: Estados, Tipos, Carga -->
    <div class="dm-grid dm-grid--3">
        <!-- Distribución por estado -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Distribución por estado', 'flavor-chat-ia'); ?></h2>
            </div>
            <?php if (!empty($por_estado)) : ?>
                <div class="dm-badge-list">
                    <?php foreach ($por_estado as $row) : ?>
                        <div class="dm-badge-list__item">
                            <span class="dm-badge <?php echo esc_attr($estado_badge_classes[$row->estado] ?? 'dm-badge--secondary'); ?>">
                                <?php echo esc_html($estado_labels[$row->estado] ?? ucfirst((string) $row->estado)); ?>
                            </span>
                            <span class="dm-badge-list__value"><?php echo number_format_i18n((int) $row->total); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="dm-empty">
                    <p><?php esc_html_e('Aún no hay reservas registradas.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tipos de servicio -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Tipos de servicio con más uso', 'flavor-chat-ia'); ?></h2>
            </div>
            <?php if (!empty($por_tipo_servicio)) : ?>
                <ol class="dm-ranking">
                    <?php foreach ($por_tipo_servicio as $tipo) : ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__label"><?php echo esc_html($tipo->tipo_servicio ?: __('Sin tipo', 'flavor-chat-ia')); ?></span>
                            <span class="dm-ranking__value"><?php echo number_format_i18n((int) $tipo->total); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else : ?>
                <div class="dm-empty">
                    <p><?php esc_html_e('Sin tipos de servicio con actividad todavía.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Carga mensual -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Carga mensual', 'flavor-chat-ia'); ?></h2>
            </div>
            <?php if (!empty($mensual)) : ?>
                <?php
                $max_mes = max(array_map(static function ($row) {
                    return (int) $row->total;
                }, $mensual));
                ?>
                <div class="dm-trend">
                    <?php foreach ($mensual as $mes) :
                        $altura = $max_mes > 0 ? max(18, (int) round(((int) $mes->total / $max_mes) * 100)) : 18;
                    ?>
                        <div class="dm-trend__item">
                            <div class="dm-trend__bar" style="height: <?php echo esc_attr($altura); ?>px;"></div>
                            <span class="dm-trend__value"><?php echo number_format_i18n((int) $mes->total); ?></span>
                            <span class="dm-trend__label"><?php echo esc_html(date_i18n('M y', strtotime($mes->periodo . '-01'))); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="dm-empty">
                    <p><?php esc_html_e('Aún no hay histórico suficiente para mostrar tendencia.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Grid 2 columnas: Cola prioritaria + Próximas reservas -->
    <div class="dm-grid dm-grid--2">
        <!-- Cola prioritaria -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Cola prioritaria', 'flavor-chat-ia'); ?></h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-listado&estado=pendiente')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Abrir listado', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php if (!empty($cola_prioritaria)) : ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Reserva', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Cliente', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Acción', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cola_prioritaria as $reserva) : ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo absint($reserva->id); ?></strong>
                                    <span class="dm-table__subtitle"><?php echo esc_html($reserva->recurso_nombre ?: __('Sin recurso', 'flavor-chat-ia')); ?></span>
                                </td>
                                <td><?php echo esc_html($reserva->nombre_cliente ?: __('Sin cliente', 'flavor-chat-ia')); ?></td>
                                <td>
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($reserva->fecha_reserva))); ?>
                                    <span class="dm-table__muted"><?php echo esc_html(substr((string) $reserva->hora_inicio, 0, 5)); ?> - <?php echo esc_html(substr((string) $reserva->hora_fin, 0, 5)); ?></span>
                                </td>
                                <td>
                                    <span class="dm-badge <?php echo esc_attr($estado_badge_classes[$reserva->estado] ?? 'dm-badge--secondary'); ?>">
                                        <?php echo esc_html($estado_labels[$reserva->estado] ?? ucfirst((string) $reserva->estado)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a class="dm-btn dm-btn--sm" href="<?php echo esc_url(add_query_arg([
                                        'page' => 'reservas-listado',
                                        'fecha' => $reserva->fecha_reserva,
                                        's' => $reserva->nombre_cliente,
                                    ], admin_url('admin.php'))); ?>"><?php esc_html_e('Revisar', 'flavor-chat-ia'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <p><?php esc_html_e('No hay reservas prioritarias ahora mismo.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Próximas reservas -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Próximas reservas', 'flavor-chat-ia'); ?></h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-calendario')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Ver calendario', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php if (!empty($proximas_reservas)) : ?>
                <div class="dm-item-list">
                    <?php foreach ($proximas_reservas as $reserva) : ?>
                        <div class="dm-item-list__item">
                            <div class="dm-item-list__content">
                                <strong>#<?php echo absint($reserva->id); ?> · <?php echo esc_html($reserva->recurso_nombre ?: __('Sin recurso', 'flavor-chat-ia')); ?></strong>
                                <span class="dm-item-list__subtitle"><?php echo esc_html($reserva->nombre_cliente ?: __('Cliente no identificado', 'flavor-chat-ia')); ?></span>
                            </div>
                            <div class="dm-item-list__meta">
                                <span class="dm-item-list__date"><?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($reserva->fecha_inicio))); ?></span>
                                <span class="dm-badge <?php echo esc_attr($estado_badge_classes[$reserva->estado] ?? 'dm-badge--secondary'); ?>">
                                    <?php echo esc_html($estado_labels[$reserva->estado] ?? ucfirst((string) $reserva->estado)); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php esc_html_e('No hay reservas futuras registradas.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Grid 2 columnas: Actividad + Recursos -->
    <div class="dm-grid dm-grid--2">
        <!-- Actividad reciente -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Actividad reciente', 'flavor-chat-ia'); ?></h2>
                <span class="dm-card__meta"><?php esc_html_e('Últimos 7 días', 'flavor-chat-ia'); ?></span>
            </div>
            <?php if (!empty($actividad_reciente)) : ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Reserva', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Cambio', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Personas', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Acción', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actividad_reciente as $reserva) : ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo absint($reserva->id); ?></strong>
                                    <span class="dm-table__subtitle"><?php echo esc_html($reserva->recurso_nombre ?: __('Sin recurso', 'flavor-chat-ia')); ?></span>
                                </td>
                                <td>
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($reserva->updated_at))); ?>
                                    <span class="dm-table__muted"><?php echo esc_html($estado_labels[$reserva->estado] ?? ucfirst((string) $reserva->estado)); ?></span>
                                </td>
                                <td><?php echo number_format_i18n((int) $reserva->num_personas); ?></td>
                                <td>
                                    <a class="dm-btn dm-btn--sm" href="<?php echo esc_url(add_query_arg([
                                        'page' => 'reservas-listado',
                                        'fecha' => $reserva->fecha_reserva,
                                        's' => $reserva->nombre_cliente,
                                    ], admin_url('admin.php'))); ?>"><?php esc_html_e('Abrir', 'flavor-chat-ia'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-update"></span>
                    <p><?php esc_html_e('No hay cambios recientes en reservas.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recursos con más actividad -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Recursos con más actividad', 'flavor-chat-ia'); ?></h2>
                <span class="dm-card__meta"><?php printf(esc_html__('%s recursos totales', 'flavor-chat-ia'), number_format_i18n($total_recursos)); ?></span>
            </div>
            <?php if (!empty($recursos_con_reservas)) : ?>
                <ol class="dm-ranking">
                    <?php foreach ($recursos_con_reservas as $recurso) : ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__label">
                                <?php echo esc_html($recurso->nombre); ?>
                                <?php if (!empty($recurso->tipo)) : ?>
                                    <small class="dm-text-muted"><?php echo esc_html($recurso->tipo); ?></small>
                                <?php endif; ?>
                            </span>
                            <span class="dm-ranking__value"><?php echo number_format_i18n((int) $recurso->total); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else : ?>
                <div class="dm-empty">
                    <p><?php esc_html_e('No hay recursos activos con actividad todavía.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>

            <div class="dm-focus-list" style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--dm-border);">
                <h3 style="margin: 0 0 12px; font-size: 14px; font-weight: 600;"><?php esc_html_e('Foco recomendado', 'flavor-chat-ia'); ?></h3>
                <div class="dm-focus-list__item dm-focus-list__item--info">
                    <span class="dm-focus-list__label">
                        <?php printf(esc_html__('%s reservas hoy requieren seguimiento de operación inmediata.', 'flavor-chat-ia'), number_format_i18n($reservas_hoy)); ?>
                    </span>
                </div>
                <div class="dm-focus-list__item dm-focus-list__item--info">
                    <span class="dm-focus-list__label">
                        <?php printf(esc_html__('%s personas previstas este mes en reservas activas o completadas.', 'flavor-chat-ia'), number_format_i18n($total_personas_mes)); ?>
                    </span>
                </div>
                <div class="dm-focus-list__item dm-focus-list__item--info">
                    <span class="dm-focus-list__label">
                        <?php printf(esc_html__('%s personas por reserva de media en la actividad reciente.', 'flavor-chat-ia'), number_format_i18n($promedio_personas, 1)); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
