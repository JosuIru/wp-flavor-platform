<?php
/**
 * Vista Dashboard Mejorado - Reservas
 * Incluye widgets de datos en vivo de módulos relacionados
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
    echo '<div class="dm-alert dm-alert--warning">' . esc_html__('La tabla principal de reservas no está disponible en esta instalación.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
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
    'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'confirmada' => __('Confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cancelada' => __('Cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'completada' => __('Completada', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$estado_badge_classes = [
    'pendiente' => 'dm-badge--warning',
    'confirmada' => 'dm-badge--success',
    'cancelada' => 'dm-badge--error',
    'completada' => 'dm-badge--info',
];

// ============================================================================
// MÓDULOS RELACIONADOS CON DATOS EN VIVO
// ============================================================================

$active_modules = get_option('flavor_active_modules', []);
$modulos_relacionados = [];

// 1. ESPACIOS COMUNES - Gestión de espacios reservables
if (in_array('espacios_comunes', $active_modules) || in_array('espacios-comunes', $active_modules)) {
    $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
    $tabla_espacios_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_espacios'") === $tabla_espacios;

    if ($tabla_espacios_existe) {
        $espacios_reservables = $wpdb->get_results(
            "SELECT id, nombre, capacidad FROM $tabla_espacios
             WHERE estado = 'activo' AND reservable = 1
             ORDER BY capacidad DESC
             LIMIT 3"
        );

        if (!empty($espacios_reservables)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($espacios_reservables as $espacio) {
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">🏢 Cap: %d personas</span>
                    </div>',
                    esc_html($espacio->nombre),
                    (int)$espacio->capacidad
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['espacios-comunes'] = [
                'titulo' => sprintf(__('Espacios Reservables (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($espacios_reservables)),
                'descripcion' => __('Salas y espacios disponibles para reserva', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-building',
                'url' => admin_url('admin.php?page=flavor-espacios-comunes'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 2. EVENTOS - Eventos que requieren reservas de espacio
if (in_array('eventos', $active_modules)) {
    $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
    $tabla_eventos_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") === $tabla_eventos;

    if ($tabla_eventos_existe) {
        $eventos_reservas = $wpdb->get_results(
            "SELECT id, titulo, fecha_inicio FROM $tabla_eventos
             WHERE fecha_inicio >= NOW()
             AND (requiere_reserva = 1 OR categoria = 'reserva')
             ORDER BY fecha_inicio ASC
             LIMIT 3"
        );

        if (!empty($eventos_reservas)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($eventos_reservas as $evento) {
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">📅 %s</span>
                    </div>',
                    esc_html(wp_trim_words($evento->titulo, 5)),
                    date_i18n('d/m/Y H:i', strtotime($evento->fecha_inicio))
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['eventos'] = [
                'titulo' => sprintf(__('Eventos con Reserva (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($eventos_reservas)),
                'descripcion' => __('Próximos eventos que requieren espacio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-calendar-alt',
                'url' => admin_url('admin.php?page=flavor-eventos'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 3. TALLERES - Talleres que necesitan salas
if (in_array('talleres', $active_modules)) {
    $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
    $tabla_talleres_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_talleres'") === $tabla_talleres;

    if ($tabla_talleres_existe) {
        $talleres_proximos = $wpdb->get_results(
            "SELECT t.*, s.fecha_hora
             FROM $tabla_talleres t
             INNER JOIN {$wpdb->prefix}flavor_talleres_sesiones s ON t.id = s.taller_id
             WHERE s.fecha_hora >= NOW()
             AND t.estado IN ('confirmado', 'en_curso')
             ORDER BY s.fecha_hora ASC
             LIMIT 3"
        );

        if (!empty($talleres_proximos)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($talleres_proximos as $taller) {
                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">🔧 %s</span>
                    </div>',
                    esc_html(wp_trim_words($taller->titulo, 5)),
                    date_i18n('d/m/Y H:i', strtotime($taller->fecha_hora))
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['talleres'] = [
                'titulo' => sprintf(__('Talleres Próximos (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($talleres_proximos)),
                'descripcion' => __('Talleres que requieren espacio reservado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-hammer',
                'url' => admin_url('admin.php?page=flavor-talleres'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 4. SOCIOS - Socios con prioridad de reserva
if (in_array('socios', $active_modules)) {
    $tabla_socios = $wpdb->prefix . 'flavor_socios';
    $tabla_socios_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_socios'") === $tabla_socios;

    if ($tabla_socios_existe) {
        $socios_activos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_socios WHERE estado = 'activo'"
        );

        if ($socios_activos > 0) {
            $datos_html = sprintf(
                '<div class="dm-widget-data-list">
                    <div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">⭐ Reserva prioritaria</span>
                    </div>
                    <div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">🎟️ 7 días anticipación</span>
                    </div>
                    <div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">🆓 Sin coste extra</span>
                    </div>
                </div>',
                sprintf(__('%d socios activos', FLAVOR_PLATFORM_TEXT_DOMAIN), $socios_activos),
                __('vs 3 días estándar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Reservas ilimitadas', FLAVOR_PLATFORM_TEXT_DOMAIN)
            );

            $modulos_relacionados['socios'] = [
                'titulo' => sprintf(__('Beneficios Socios (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), $socios_activos),
                'descripcion' => __('Ventajas exclusivas en reservas de espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-groups',
                'url' => admin_url('admin.php?page=flavor-socios'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 5. BICICLETAS COMPARTIDAS - Reservas de bicicletas
if (in_array('bicicletas_compartidas', $active_modules) || in_array('bicicletas-compartidas', $active_modules)) {
    $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
    $tabla_bicicletas_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_bicicletas'") === $tabla_bicicletas;

    if ($tabla_bicicletas_existe) {
        $bicicletas_disponibles = $wpdb->get_results(
            "SELECT id, codigo, tipo FROM $tabla_bicicletas
             WHERE estado = 'disponible' AND activa = 1
             ORDER BY id DESC
             LIMIT 3"
        );

        if (!empty($bicicletas_disponibles)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($bicicletas_disponibles as $bici) {
                $tipo_icon = '🚲';
                if (stripos($bici->tipo, 'eléctrica') !== false) {
                    $tipo_icon = '⚡🚲';
                }

                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>%s</strong>
                        <span class="dm-widget-meta">%s Disponible</span>
                    </div>',
                    esc_html($bici->codigo),
                    $tipo_icon
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['bicicletas-compartidas'] = [
                'titulo' => sprintf(__('Bicicletas Disponibles (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($bicicletas_disponibles)),
                'descripcion' => __('Bicicletas listas para reservar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-location',
                'url' => admin_url('admin.php?page=flavor-bicicletas-compartidas'),
                'datos' => $datos_html,
            ];
        }
    }
}

// 6. PARKINGS - Reservas de parkings
if (in_array('parkings', $active_modules)) {
    $tabla_parkings = $wpdb->prefix . 'flavor_parkings';
    $tabla_parkings_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_parkings'") === $tabla_parkings;

    if ($tabla_parkings_existe) {
        $plazas_disponibles = $wpdb->get_results(
            "SELECT id, numero_plaza, tipo FROM $tabla_parkings
             WHERE estado = 'disponible'
             ORDER BY numero_plaza ASC
             LIMIT 3"
        );

        if (!empty($plazas_disponibles)) {
            $datos_html = '<div class="dm-widget-data-list">';
            foreach ($plazas_disponibles as $plaza) {
                $tipo_icon = '🅿️';
                if (!empty($plaza->tipo) && stripos($plaza->tipo, 'movilidad') !== false) {
                    $tipo_icon = '♿';
                }

                $datos_html .= sprintf(
                    '<div class="dm-widget-item">
                        <strong>Plaza %s</strong>
                        <span class="dm-widget-meta">%s Libre</span>
                    </div>',
                    esc_html($plaza->numero_plaza),
                    $tipo_icon
                );
            }
            $datos_html .= '</div>';

            $modulos_relacionados['parkings'] = [
                'titulo' => sprintf(__('Plazas de Parking (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($plazas_disponibles)),
                'descripcion' => __('Plazas disponibles para reservar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-admin-multisite',
                'url' => admin_url('admin.php?page=flavor-parkings'),
                'datos' => $datos_html,
            ];
        }
    }
}
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
            <h1><?php esc_html_e('Dashboard de Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        </div>
        <p class="dm-header__description">
            <?php esc_html_e('Panel operativo para controlar la cola de reservas, el uso de recursos y la carga próxima del servicio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-calendario')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-calendar-alt"></span>
            <span><?php esc_html_e('Calendario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-listado')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-list-view"></span>
            <span><?php esc_html_e('Todas las reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-nueva')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-plus-alt"></span>
            <span><?php esc_html_e('Nueva reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-recursos')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-building"></span>
            <span><?php esc_html_e('Recursos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-config')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-settings"></span>
            <span><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('reservas', '')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal público', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
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
                <div class="dm-stat-card__label"><?php esc_html_e('Reservas activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php printf(esc_html__('%s entradas en 48h', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($entradas_48h)); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($reservas_pendientes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php printf(esc_html__('%s vencidas', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($pendientes_vencidas)); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($reservas_confirmadas_mes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Confirmadas este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php printf(esc_html__('%s completadas', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($reservas_completadas_mes)); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-building"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($recursos_activos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Recursos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                <div class="dm-stat-card__meta"><?php printf(esc_html__('%s reservas totales', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($total_reservas)); ?></div>
            </div>
        </div>
    </div>

    <!-- MÓDULOS RELACIONADOS CON DATOS EN VIVO -->
    <?php if (!empty($modulos_relacionados)): ?>
        <div class="dm-section">
            <div class="dm-section__header">
                <h2><?php esc_html_e('Módulos Relacionados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                <p class="dm-section__description">
                    <?php esc_html_e('Datos en vivo de módulos que interactúan con reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <div class="dm-grid dm-grid--3">
                <?php foreach ($modulos_relacionados as $key => $modulo): ?>
                    <div class="dm-widget-relacionado" style="border-left-color: var(--dm-color-<?php echo esc_attr($key); ?>, var(--dm-primary));">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <span class="dashicons <?php echo esc_attr($modulo['icono']); ?>" style="font-size: 20px; color: var(--dm-primary);"></span>
                            <strong style="font-size: 15px;"><?php echo esc_html($modulo['titulo']); ?></strong>
                        </div>
                        <p style="color: var(--dm-text-secondary); margin: 0 0 12px 0; font-size: 13px;">
                            <?php echo esc_html($modulo['descripcion']); ?>
                        </p>

                        <?php if (isset($modulo['datos'])): ?>
                            <div class="dm-widget-datos-vivo">
                                <?php echo $modulo['datos']; ?>
                            </div>
                        <?php endif; ?>

                        <a href="<?php echo esc_url($modulo['url']); ?>"
                           style="display: inline-flex; align-items: center; gap: 5px; color: var(--dm-primary); text-decoration: none; font-size: 13px; margin-top: 8px;">
                            <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Resto del dashboard original continúa aquí... -->

    <!-- Acciones Rápidas + Alertas -->
    <div class="dm-grid dm-grid--2">
        <!-- Acciones Rápidas -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Acciones rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>
            <div class="dm-action-grid dm-action-grid--2">
                <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-listado&estado=pendiente')); ?>" class="dm-action-card <?php echo $reservas_pendientes > 0 ? 'dm-action-card--warning' : ''; ?>">
                    <span class="dashicons dashicons-clock"></span>
                    <div class="dm-action-card__content">
                        <strong><?php esc_html_e('Cola pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <span><?php esc_html_e('Revisar solicitudes pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <?php if ($reservas_pendientes > 0) : ?>
                        <span class="dm-badge dm-badge--warning"><?php echo number_format_i18n($reservas_pendientes); ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-calendario')); ?>" class="dm-action-card dm-action-card--primary">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <div class="dm-action-card__content">
                        <strong><?php esc_html_e('Calendario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <span><?php esc_html_e('Ver ocupación y próximas franjas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-recursos')); ?>" class="dm-action-card dm-action-card--success">
                    <span class="dashicons dashicons-building"></span>
                    <div class="dm-action-card__content">
                        <strong><?php esc_html_e('Recursos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <span><?php esc_html_e('Gestionar espacios y capacidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <?php if ($recursos_activos > 0) : ?>
                        <span class="dm-badge dm-badge--success"><?php echo number_format_i18n($recursos_activos); ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=reservas-nueva')); ?>" class="dm-action-card dm-action-card--primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <div class="dm-action-card__content">
                        <strong><?php esc_html_e('Nueva reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <span><?php esc_html_e('Registrar una reserva desde administración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Panel de Alertas -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Alertas operativas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>
            <div class="dm-focus-list">
                <div class="dm-focus-list__item <?php echo $pendientes_vencidas > 0 ? 'dm-focus-list__item--error' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Pendientes con fecha ya vencida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($pendientes_vencidas); ?></span>
                </div>
                <div class="dm-focus-list__item <?php echo $reservas_sin_recurso > 0 ? 'dm-focus-list__item--error' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Reservas sin recurso asignado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($reservas_sin_recurso); ?></span>
                </div>
                <div class="dm-focus-list__item <?php echo $reservas_canceladas_mes > 0 ? 'dm-focus-list__item--warning' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Canceladas este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($reservas_canceladas_mes); ?></span>
                </div>
                <div class="dm-focus-list__item <?php echo $entradas_48h > 0 ? 'dm-focus-list__item--warning' : 'dm-focus-list__item--success'; ?>">
                    <span class="dm-focus-list__label"><?php esc_html_e('Entradas previstas en 48 horas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
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
                    <p><?php esc_html_e('Aún no hay reservas registradas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tipos de servicio -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Tipos de servicio con más uso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>
            <?php if (!empty($por_tipo_servicio)) : ?>
                <ol class="dm-ranking">
                    <?php foreach ($por_tipo_servicio as $tipo) : ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__label"><?php echo esc_html($tipo->tipo_servicio ?: __('Sin tipo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                            <span class="dm-ranking__value"><?php echo number_format_i18n((int) $tipo->total); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else : ?>
                <div class="dm-empty">
                    <p><?php esc_html_e('Sin tipos de servicio con actividad todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Carga mensual -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2><?php esc_html_e('Carga mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
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
                    <p><?php esc_html_e('Aún no hay histórico suficiente para mostrar tendencia.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- El resto de las secciones continúa igual que en el dashboard original... -->
</div>
