<?php
/**
 * Vista Dashboard - Chat Interno
 *
 * Dashboard administrativo para gestión de mensajería interna.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas del módulo
$tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';
$tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';
$tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

// Verificar si las tablas existen
$tabla_conversaciones_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_conversaciones)) === $tabla_conversaciones;

if (!$tabla_conversaciones_existe) {
    ?>
    <div class="dm-card">
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Módulo en preparación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Chat Interno aún no han sido creadas. Activa el módulo completamente para generar la estructura necesaria.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Estadísticas
$total_conversaciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_conversaciones}");
$conversaciones_activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_conversaciones} WHERE estado = 'activa'");

$tabla_mensajes_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_mensajes)) === $tabla_mensajes;
$total_mensajes = $tabla_mensajes_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_mensajes}") : 0;
$mensajes_hoy = $tabla_mensajes_existe ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_mensajes} WHERE DATE(fecha_creacion) = %s",
    current_time('Y-m-d')
)) : 0;
$mensajes_semana = $tabla_mensajes_existe ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_mensajes} WHERE fecha_creacion >= %s",
    gmdate('Y-m-d', strtotime('-7 days'))
)) : 0;

$tabla_participantes_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_participantes)) === $tabla_participantes;
$usuarios_activos = $tabla_participantes_existe ? (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_participantes}") : 0;

// Actividad reciente por día (últimos 7 días) - ordenado ASC para el gráfico
$actividad_diaria = $tabla_mensajes_existe ? $wpdb->get_results(
    "SELECT DATE(fecha_creacion) as fecha, COUNT(*) as total
     FROM {$tabla_mensajes}
     WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(fecha_creacion)
     ORDER BY fecha ASC"
) : [];

// Mensajes no leídos
$mensajes_no_leidos = $tabla_mensajes_existe ? (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_mensajes} WHERE leido = 0 AND eliminado = 0"
) : 0;

// Conversaciones con mensajes pendientes
$conversaciones_pendientes = $tabla_mensajes_existe ? (int) $wpdb->get_var(
    "SELECT COUNT(DISTINCT conversacion_id) FROM {$tabla_mensajes} WHERE leido = 0 AND eliminado = 0"
) : 0;

// Usuarios más activos
$usuarios_top = $tabla_mensajes_existe ? $wpdb->get_results(
    "SELECT m.remitente_id, u.display_name, COUNT(*) as total_mensajes
     FROM {$tabla_mensajes} m
     LEFT JOIN {$wpdb->users} u ON u.ID = m.remitente_id
     WHERE m.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY m.remitente_id
     ORDER BY total_mensajes DESC
     LIMIT 5"
) : [];
?>

<!-- Aviso de Privacidad -->
<div class="dm-alert dm-alert--info" style="margin-bottom: 16px;">
    <span class="dashicons dashicons-lock"></span>
    <div>
        <strong><?php esc_html_e('Mensajería Privada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
        <span><?php esc_html_e('Los mensajes entre usuarios son privados. Este panel solo muestra estadísticas agregadas, no contenido de conversaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </div>
</div>

<!-- Acciones Rápidas -->
<div class="dm-quick-actions">
    <a href="<?php echo esc_url(admin_url('admin.php?page=chat-interno-configuracion')); ?>" class="dm-quick-action">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=chat-interno-usuarios')); ?>" class="dm-quick-action">
        <span class="dashicons dashicons-groups"></span>
        <?php esc_html_e('Usuarios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=chat-interno-reportes')); ?>" class="dm-quick-action">
        <span class="dashicons dashicons-flag"></span>
        <?php esc_html_e('Reportes/Denuncias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=chat-interno-bloqueos')); ?>" class="dm-quick-action dm-quick-action--outline">
        <span class="dashicons dashicons-dismiss"></span>
        <?php esc_html_e('Usuarios Bloqueados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>
</div>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--primary dm-stat-card--horizontal">
        <span class="dashicons dashicons-format-chat dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($total_conversaciones); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Conversaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--success dm-stat-card--horizontal">
        <span class="dashicons dashicons-admin-users dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($usuarios_activos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Usuarios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--purple dm-stat-card--horizontal">
        <span class="dashicons dashicons-email-alt dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($total_mensajes)); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--warning dm-stat-card--horizontal">
        <span class="dashicons dashicons-clock dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($mensajes_hoy); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Mensajes Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>
</div>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Actividad Semanal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <span class="dm-card__subtitle"><?php printf(esc_html__('%s mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($mensajes_semana)); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($actividad_diaria)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('No hay actividad en los últimos 7 días.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <?php
                $max_mensajes = max(array_column($actividad_diaria, 'total'));
                $dias_semana = [
                    __('Dom', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Lun', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Mar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Mié', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Jue', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Vie', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    __('Sáb', FLAVOR_PLATFORM_TEXT_DOMAIN)
                ];
                ?>
                <div class="dm-chart-bars">
                    <?php foreach ($actividad_diaria as $dia): ?>
                        <?php
                        $altura = $max_mensajes > 0 ? ($dia->total / $max_mensajes) * 100 : 5;
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

            <?php if ($mensajes_no_leidos > 0): ?>
            <div class="dm-alert dm-alert--warning" style="margin-top: 16px;">
                <span class="dashicons dashicons-email"></span>
                <div>
                    <strong><?php printf(esc_html__('%s mensajes sin leer', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($mensajes_no_leidos)); ?></strong>
                    <span><?php printf(esc_html__('en %s conversaciones', FLAVOR_PLATFORM_TEXT_DOMAIN), $conversaciones_pendientes); ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-awards"></span>
                <?php esc_html_e('Usuarios Más Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <span class="dm-card__subtitle"><?php esc_html_e('Últimos 30 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($usuarios_top)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-admin-users"></span>
                    <p><?php esc_html_e('No hay datos de actividad todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($usuarios_top as $index => $usuario): ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__avatar">
                                <?php echo mb_substr($usuario->display_name ?: __('U', FLAVOR_PLATFORM_TEXT_DOMAIN), 0, 1); ?>
                            </div>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($usuario->display_name ?: __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                            </div>
                            <span class="dm-badge dm-badge--info">
                                <?php echo esc_html($usuario->total_mensajes); ?> <?php esc_html_e('msgs', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dm-stats-grid dm-stats-grid--3">
    <div class="dm-stat-card dm-stat-card--info">
        <span class="dashicons dashicons-admin-comments dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($conversaciones_activas); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Conversaciones Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--error">
        <span class="dashicons dashicons-email dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($mensajes_no_leidos)); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Mensajes Sin Leer', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>

    <div class="dm-stat-card dm-stat-card--secondary">
        <span class="dashicons dashicons-visibility dm-stat-card__icon"></span>
        <div class="dm-stat-card__value"><?php echo esc_html($conversaciones_pendientes); ?></div>
        <div class="dm-stat-card__label"><?php esc_html_e('Conv. con Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>
</div>

<!-- Acceso Frontend -->
<div class="dm-card">
    <div class="dm-card__header">
        <h3 class="dm-card__title">
            <span class="dashicons dashicons-external"></span>
            <?php esc_html_e('Páginas Públicas del Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h3>
        <span class="dm-card__subtitle"><?php esc_html_e('Shortcodes disponibles para el frontend', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </div>
    <div class="dm-card__body">
        <div class="dm-link-grid">
            <a href="<?php echo esc_url(home_url('/mensajes/')); ?>" target="_blank" class="dm-link-card">
                <span class="dashicons dashicons-email-alt"></span>
                <div>
                    <strong><?php esc_html_e('Bandeja de Entrada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <span>[chat_interno_inbox]</span>
                </div>
            </a>
            <a href="<?php echo esc_url(home_url('/mensajes/nuevo/')); ?>" target="_blank" class="dm-link-card">
                <span class="dashicons dashicons-plus-alt2"></span>
                <div>
                    <strong><?php esc_html_e('Iniciar Conversación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <span>[chat_interno_iniciar]</span>
                </div>
            </a>
            <div class="dm-link-card" style="cursor: default; opacity: 0.7;">
                <span class="dashicons dashicons-format-chat"></span>
                <div>
                    <strong><?php esc_html_e('Ver Conversación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                    <span>[chat_interno_conversacion id="X"]</span>
                </div>
            </div>
        </div>

        <div class="dm-alert dm-alert--info" style="margin-top: 16px;">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Tip:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                <span><?php esc_html_e('Usa estos shortcodes en cualquier página para mostrar la funcionalidad de chat interno a los usuarios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>
</div>
