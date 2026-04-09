<?php
/**
 * Vista Dashboard - Socios
 *
 * Dashboard administrativo operativo para membresía, cuotas y seguimiento.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_socios = $wpdb->prefix . 'flavor_socios';
$tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';
$tabla_pagos = $wpdb->prefix . 'flavor_socios_pagos';

$tabla_socios_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_socios)) === $tabla_socios;
$tabla_cuotas_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_cuotas)) === $tabla_cuotas;
$tabla_pagos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_pagos)) === $tabla_pagos;

if (!$tabla_socios_existe) {
    echo '<div class="dm-alert dm-alert--warning">' . esc_html__('La tabla principal de miembros no está disponible en esta instalación.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
    return;
}

$hoy = current_time('Y-m-d');
$inicio_mes = gmdate('Y-m-01 00:00:00', current_time('timestamp', true));
$hace_30_dias = gmdate('Y-m-d H:i:s', strtotime('-30 days', current_time('timestamp', true)));

$total_socios = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_socios}");
$socios_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_socios} WHERE estado = 'activo'");
$socios_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_socios} WHERE estado = 'pendiente'");
$socios_suspendidos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_socios} WHERE estado IN ('suspendido', 'moroso')");
$socios_baja_mes = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_socios} WHERE estado = 'baja' AND fecha_baja >= %s",
    gmdate('Y-m-01', current_time('timestamp', true))
));
$altas_mes = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_socios} WHERE fecha_alta >= %s",
    gmdate('Y-m-01', current_time('timestamp', true))
));

$tipos_socio = $wpdb->get_results(
    "SELECT tipo_socio, COUNT(*) as total
     FROM {$tabla_socios}
     WHERE estado = 'activo'
     GROUP BY tipo_socio
     ORDER BY total DESC
     LIMIT 6"
);

$por_estado = $wpdb->get_results(
    "SELECT estado, COUNT(*) as total
     FROM {$tabla_socios}
     GROUP BY estado
     ORDER BY total DESC"
);

$nuevas_altas = $wpdb->get_results(
    "SELECT s.*, u.display_name, u.user_email
     FROM {$tabla_socios} s
     LEFT JOIN {$wpdb->users} u ON u.ID = s.usuario_id
     ORDER BY s.fecha_alta DESC
     LIMIT 8"
);

$mensual_altas = $wpdb->get_results(
    "SELECT DATE_FORMAT(fecha_alta, '%Y-%m') as periodo, COUNT(*) as total
     FROM {$tabla_socios}
     WHERE fecha_alta >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
     GROUP BY DATE_FORMAT(fecha_alta, '%Y-%m')
     ORDER BY periodo ASC"
);

$cuotas_pendientes = 0;
$cuotas_vencidas = 0;
$importe_pendiente = 0.0;
$cuotas_pagadas_mes = 0;
$importe_pagado_mes = 0.0;
$cuotas_criticas = [];
$actividad_cuotas = [];

if ($tabla_cuotas_existe) {
    $cuotas_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_cuotas} WHERE estado = 'pendiente'");
    $cuotas_vencidas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_cuotas} WHERE estado = 'vencida'");
    $importe_pendiente = (float) $wpdb->get_var("SELECT COALESCE(SUM(importe), 0) FROM {$tabla_cuotas} WHERE estado IN ('pendiente', 'vencida')");
    $cuotas_pagadas_mes = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_cuotas} WHERE estado = 'pagada' AND fecha_pago >= %s",
        $inicio_mes
    ));
    $importe_pagado_mes = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(importe), 0) FROM {$tabla_cuotas} WHERE estado = 'pagada' AND fecha_pago >= %s",
        $inicio_mes
    ));

    $cuotas_criticas = $wpdb->get_results(
        "SELECT c.*, s.numero_socio, s.estado as socio_estado, u.display_name, u.user_email
         FROM {$tabla_cuotas} c
         LEFT JOIN {$tabla_socios} s ON s.id = c.socio_id
         LEFT JOIN {$wpdb->users} u ON u.ID = s.usuario_id
         WHERE c.estado IN ('pendiente', 'vencida')
         ORDER BY
            CASE c.estado WHEN 'vencida' THEN 1 ELSE 2 END,
            c.fecha_cargo ASC
         LIMIT 8"
    );

    $actividad_cuotas = $wpdb->get_results($wpdb->prepare(
        "SELECT c.*, s.numero_socio, u.display_name
         FROM {$tabla_cuotas} c
         LEFT JOIN {$tabla_socios} s ON s.id = c.socio_id
         LEFT JOIN {$wpdb->users} u ON u.ID = s.usuario_id
         WHERE COALESCE(c.fecha_pago, c.fecha_cargo) >= %s
         ORDER BY COALESCE(c.fecha_pago, c.fecha_cargo) DESC
         LIMIT 8",
        $hace_30_dias
    ));
}

$pagos_pendientes = 0;
$pagos_fallidos = 0;
if ($tabla_pagos_existe) {
    $pagos_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_pagos} WHERE estado = 'pendiente'");
    $pagos_fallidos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_pagos} WHERE estado IN ('fallido', 'cancelado')");
}

$tasa_activacion = $total_socios > 0 ? round(($socios_activos / $total_socios) * 100, 1) : 0;

$estado_labels = [
    'activo' => __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'suspendido' => __('Suspendido', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'moroso' => __('Moroso', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'baja' => __('Baja', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$estado_badge_classes = [
    'activo' => 'dm-badge--success',
    'pendiente' => 'dm-badge--warning',
    'suspendido' => 'dm-badge--warning',
    'moroso' => 'dm-badge--error',
    'baja' => 'dm-badge--secondary',
];

$cuota_badge_classes = [
    'pendiente' => 'dm-badge--warning',
    'pagada' => 'dm-badge--success',
    'vencida' => 'dm-badge--error',
    'condonada' => 'dm-badge--secondary',
    'cancelada' => 'dm-badge--secondary',
    'devuelta' => 'dm-badge--warning',
];
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('socios');
    }
    ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-groups"></span>
            <h1><?php esc_html_e('Dashboard de Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        </div>
        <p class="dm-header__description">
            <?php esc_html_e('Panel operativo para controlar la salud de la membresía, las cuotas pendientes y las altas o bajas del mes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=socios-listado')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-groups"></span>
            <span><?php esc_html_e('Listado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=socios-cuotas')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-money-alt"></span>
            <span><?php esc_html_e('Cuotas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=socios-pagos')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-cart"></span>
            <span><?php esc_html_e('Pagos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=socios-altas-bajas')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-randomize"></span>
            <span><?php esc_html_e('Altas/Bajas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=socios-config')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-settings"></span>
            <span><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/mi-portal/socios/')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal de miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
    </div>

    <!-- KPIs Principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($socios_activos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Miembros activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php printf(esc_html__('%s%% activación', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($tasa_activacion, 1)); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($socios_pendientes + $socios_suspendidos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Pendientes y suspendidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php printf(esc_html__('%s pendientes de alta', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($socios_pendientes)); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($cuotas_pagadas_mes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Cuotas cobradas este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php echo esc_html(number_format_i18n($importe_pagado_mes, 2)); ?> EUR</div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-clipboard"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($importe_pendiente, 2)); ?> €</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Deuda abierta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php printf(esc_html__('%s cuotas abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($cuotas_pendientes + $cuotas_vencidas)); ?></div>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas + Alertas -->
    <div class="dm-grid dm-grid--2">
        <!-- Acciones Rápidas -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Acciones rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>
            <div class="dm-action-grid dm-action-grid--2">
                <a href="<?php echo esc_url(admin_url('admin.php?page=socios-listado&estado=activo')); ?>" class="dm-action-card dm-action-card--primary">
                    <span class="dashicons dashicons-groups"></span>
                    <div class="dm-action-card__content">
                        <strong><?php esc_html_e('Miembros activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <span><?php esc_html_e('Abrir base activa de membresía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <?php if ($socios_activos > 0) : ?>
                        <span class="dm-badge dm-badge--primary"><?php echo number_format_i18n($socios_activos); ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=socios-listado&estado=pendiente')); ?>" class="dm-action-card <?php echo $socios_pendientes > 0 ? 'dm-action-card--warning' : ''; ?>">
                    <span class="dashicons dashicons-id-alt"></span>
                    <div class="dm-action-card__content">
                        <strong><?php esc_html_e('Pendientes de alta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <span><?php esc_html_e('Validar nuevas solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <?php if ($socios_pendientes > 0) : ?>
                        <span class="dm-badge dm-badge--warning"><?php echo number_format_i18n($socios_pendientes); ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=socios-cuotas&estado=pendiente')); ?>" class="dm-action-card <?php echo $cuotas_pendientes > 0 ? 'dm-action-card--warning' : 'dm-action-card--success'; ?>">
                    <span class="dashicons dashicons-money-alt"></span>
                    <div class="dm-action-card__content">
                        <strong><?php esc_html_e('Cuotas pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <span><?php esc_html_e('Gestionar cobros abiertos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <?php if ($cuotas_pendientes > 0) : ?>
                        <span class="dm-badge dm-badge--warning"><?php echo number_format_i18n($cuotas_pendientes); ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=socios-altas-bajas')); ?>" class="dm-action-card">
                    <span class="dashicons dashicons-randomize"></span>
                    <div class="dm-action-card__content">
                        <strong><?php esc_html_e('Altas y bajas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <span><?php esc_html_e('Registrar movimientos de membresía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Panel de Alertas -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Alertas de membresía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>
            <div class="dm-focus-list">
                <div class="dm-focus-list__item <?php echo $socios_pendientes > 0 ? 'dm-focus-list__item--warning' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Solicitudes pendientes de validar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($socios_pendientes); ?></span>
                </div>
                <div class="dm-focus-list__item <?php echo $cuotas_vencidas > 0 ? 'dm-focus-list__item--error' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Cuotas vencidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($cuotas_vencidas); ?></span>
                </div>
                <div class="dm-focus-list__item <?php echo $pagos_fallidos > 0 ? 'dm-focus-list__item--warning' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Pagos fallidos o cancelados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($pagos_fallidos); ?></span>
                </div>
                <div class="dm-focus-list__item <?php echo $socios_baja_mes > 0 ? 'dm-focus-list__item--warning' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Bajas este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($socios_baja_mes); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid de 3 columnas: Estados, Tipos, Altas -->
    <div class="dm-grid dm-grid--3">
        <!-- Distribución por estado -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Distribución por estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
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
                    <p><?php esc_html_e('No hay estados registrados todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tipos de socio -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Tipos de socio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>
            <?php if (!empty($tipos_socio)) : ?>
                <ol class="dm-ranking">
                    <?php foreach ($tipos_socio as $tipo) : ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__label"><?php echo esc_html($tipo->tipo_socio ?: __('Sin tipo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                            <span class="dm-ranking__value"><?php echo number_format_i18n((int) $tipo->total); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else : ?>
                <div class="dm-empty">
                    <p><?php esc_html_e('No hay tipos de socio con actividad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Altas mensuales -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Altas mensuales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>
            <?php if (!empty($mensual_altas)) : ?>
                <?php
                $max_mes = max(array_map(static function ($row) {
                    return (int) $row->total;
                }, $mensual_altas));
                ?>
                <div class="dm-trend">
                    <?php foreach ($mensual_altas as $mes) :
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
                    <p><?php esc_html_e('No hay histórico suficiente de altas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Grid 2 columnas: Cuotas críticas + Nuevas altas -->
    <div class="dm-grid dm-grid--2">
        <!-- Cuotas críticas -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Cuotas críticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=socios-cuotas&estado=pendiente')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Abrir cuotas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <?php if (!empty($cuotas_criticas)) : ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Socio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Periodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Importe', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Acción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cuotas_criticas as $cuota) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($cuota->display_name ?: __('Sin usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                                    <span class="dm-table__subtitle">#<?php echo esc_html($cuota->numero_socio ?: '-'); ?></span>
                                </td>
                                <td>
                                    <?php echo esc_html($cuota->periodo ?: date_i18n('M Y', strtotime($cuota->fecha_cargo))); ?>
                                    <span class="dm-table__muted"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($cuota->fecha_cargo))); ?></span>
                                </td>
                                <td><?php echo esc_html(number_format_i18n((float) $cuota->importe, 2)); ?> EUR</td>
                                <td>
                                    <span class="dm-badge <?php echo esc_attr($cuota_badge_classes[$cuota->estado] ?? 'dm-badge--secondary'); ?>">
                                        <?php echo esc_html(ucfirst((string) $cuota->estado)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a class="dm-btn dm-btn--sm" href="<?php echo esc_url(admin_url('admin.php?page=socios-cuotas&estado=' . rawurlencode($cuota->estado))); ?>">
                                        <?php esc_html_e('Gestionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <p><?php esc_html_e('No hay cuotas críticas ahora mismo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Nuevas altas -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Nuevas altas y cambios recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=socios-listado')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Abrir listado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <?php if (!empty($nuevas_altas)) : ?>
                <div class="dm-item-list">
                    <?php foreach ($nuevas_altas as $socio) : ?>
                        <div class="dm-item-list__item">
                            <div class="dm-item-list__content">
                                <strong><?php echo esc_html($socio->display_name ?: __('Usuario sin nombre', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                                <span class="dm-item-list__subtitle">#<?php echo esc_html($socio->numero_socio ?: '-'); ?> · <?php echo esc_html($socio->tipo_socio ?: __('Sin tipo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                                <span class="dm-item-list__muted"><?php echo esc_html($socio->user_email ?: ''); ?></span>
                            </div>
                            <div class="dm-item-list__meta">
                                <span class="dm-item-list__date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($socio->fecha_alta))); ?></span>
                                <span class="dm-badge <?php echo esc_attr($estado_badge_classes[$socio->estado] ?? 'dm-badge--secondary'); ?>">
                                    <?php echo esc_html($estado_labels[$socio->estado] ?? ucfirst((string) $socio->estado)); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-groups"></span>
                    <p><?php esc_html_e('No hay altas recientes registradas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Grid 2 columnas: Actividad cuotas + Foco -->
    <div class="dm-grid dm-grid--2">
        <!-- Actividad de cuotas -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Actividad de cuotas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <span class="dm-card__meta"><?php esc_html_e('Últimos 30 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <?php if (!empty($actividad_cuotas)) : ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Socio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Periodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Cambio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actividad_cuotas as $cuota) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($cuota->display_name ?: __('Sin usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                                    <span class="dm-table__subtitle">#<?php echo esc_html($cuota->numero_socio ?: '-'); ?></span>
                                </td>
                                <td><?php echo esc_html($cuota->periodo ?: '-'); ?></td>
                                <td>
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($cuota->fecha_pago ?: $cuota->fecha_cargo))); ?>
                                    <span class="dm-table__muted"><?php echo esc_html(number_format_i18n((float) $cuota->importe, 2)); ?> EUR</span>
                                </td>
                                <td>
                                    <span class="dm-badge <?php echo esc_attr($cuota_badge_classes[$cuota->estado] ?? 'dm-badge--secondary'); ?>">
                                        <?php echo esc_html(ucfirst((string) $cuota->estado)); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php esc_html_e('No hay actividad reciente de cuotas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Foco recomendado -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Foco recomendado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <span class="dm-card__meta"><?php printf(esc_html__('%s altas este mes', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($altas_mes)); ?></span>
            </div>
            <div class="dm-focus-list">
                <div class="dm-focus-list__item dm-focus-list__item--info">
                    <span class="dm-focus-list__label">
                        <?php printf(esc_html__('%s cuotas abiertas necesitan seguimiento de cobro o regularización.', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($cuotas_pendientes + $cuotas_vencidas)); ?>
                    </span>
                </div>
                <div class="dm-focus-list__item dm-focus-list__item--info">
                    <span class="dm-focus-list__label">
                        <?php printf(esc_html__('%s pagos en espera o con incidencia requieren revisión manual.', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($pagos_pendientes + $pagos_fallidos)); ?>
                    </span>
                </div>
                <div class="dm-focus-list__item dm-focus-list__item--info">
                    <span class="dm-focus-list__label">
                        <?php printf(esc_html__('%s miembros están fuera de estado activo y pueden requerir decisión operativa.', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($socios_pendientes + $socios_suspendidos + $socios_baja_mes)); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
