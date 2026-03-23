<?php
/**
 * Vista Dashboard - Chat Grupos
 *
 * Dashboard administrativo para gestión de grupos de chat.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Tablas del módulo
$tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
$tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
$tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

// Verificar si las tablas existen
$tabla_grupos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_grupos)) === $tabla_grupos;

if (!$tabla_grupos_existe) {
    ?>
    <div class="dm-card">
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Módulo en preparación', 'flavor-chat-ia'); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Chat Grupos aún no han sido creadas. Activa el módulo completamente para generar la estructura necesaria.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Estadísticas
$total_grupos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_grupos}");
$grupos_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_grupos} WHERE estado = 'activo'");
$grupos_publicos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_grupos} WHERE tipo = 'publico'");
$grupos_privados = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_grupos} WHERE tipo = 'privado'");
$grupos_secretos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_grupos} WHERE tipo = 'secreto'");

$tabla_miembros_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_miembros)) === $tabla_miembros;
$total_miembros = $tabla_miembros_existe ? (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_miembros}") : 0;

$tabla_mensajes_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_mensajes)) === $tabla_mensajes;
$total_mensajes = $tabla_mensajes_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_mensajes}") : 0;
$mensajes_hoy = $tabla_mensajes_existe ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_mensajes} WHERE DATE(fecha_creacion) = %s",
    current_time('Y-m-d')
)) : 0;
$mensajes_semana = $tabla_mensajes_existe ? (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_mensajes} WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
) : 0;

// Actividad diaria (últimos 7 días)
$actividad_diaria = $tabla_mensajes_existe ? $wpdb->get_results(
    "SELECT DATE(fecha_creacion) as fecha, COUNT(*) as total
     FROM {$tabla_mensajes}
     WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(fecha_creacion)
     ORDER BY fecha ASC"
) : [];

// Grupos más activos (por mensajes en los últimos 30 días)
$grupos_mas_activos = $tabla_mensajes_existe ? $wpdb->get_results(
    "SELECT g.id, g.nombre, g.tipo, g.color, g.miembros_count,
            COUNT(m.id) as total_mensajes
     FROM {$tabla_grupos} g
     LEFT JOIN {$tabla_mensajes} m ON g.id = m.grupo_id
        AND m.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     WHERE g.estado = 'activo'
     GROUP BY g.id
     HAVING total_mensajes > 0
     ORDER BY total_mensajes DESC
     LIMIT 5"
) : [];

// Grupos recientes
$grupos_recientes = $wpdb->get_results(
    "SELECT g.*,
            (SELECT COUNT(*) FROM {$tabla_miembros} m WHERE m.grupo_id = g.id) as num_miembros
     FROM {$tabla_grupos} g
     ORDER BY g.fecha_creacion DESC
     LIMIT 5"
);
?>

<div class="dm-stats-grid dm-stats-grid--4">
    <div class="dm-stat-card dm-stat-card--primary dm-stat-card--horizontal">
        <span class="dashicons dashicons-groups dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($total_grupos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Grupos', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--success dm-stat-card--horizontal">
        <span class="dashicons dashicons-yes-alt dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($grupos_activos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Grupos Activos', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--purple dm-stat-card--horizontal">
        <span class="dashicons dashicons-admin-users dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($total_miembros); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Usuarios en Grupos', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <div class="dm-stat-card dm-stat-card--warning dm-stat-card--horizontal">
        <span class="dashicons dashicons-format-chat dm-stat-card__icon"></span>
        <div class="dm-stat-card__content">
            <div class="dm-stat-card__value"><?php echo esc_html($mensajes_hoy); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Mensajes Hoy', 'flavor-chat-ia'); ?></div>
        </div>
    </div>
</div>

<div class="dm-grid dm-grid--2">
    <div class="dm-card">
        <div class="dm-card__header">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Actividad Semanal', 'flavor-chat-ia'); ?>
            </h3>
            <span class="dm-card__subtitle"><?php printf(esc_html__('%s mensajes', 'flavor-chat-ia'), number_format_i18n($mensajes_semana)); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($actividad_diaria)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('No hay actividad en los últimos 7 días.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <?php
                $max_mensajes = max(array_column($actividad_diaria, 'total'));
                $dias_semana = [
                    __('Dom', 'flavor-chat-ia'),
                    __('Lun', 'flavor-chat-ia'),
                    __('Mar', 'flavor-chat-ia'),
                    __('Mié', 'flavor-chat-ia'),
                    __('Jue', 'flavor-chat-ia'),
                    __('Vie', 'flavor-chat-ia'),
                    __('Sáb', 'flavor-chat-ia')
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
                <?php esc_html_e('Distribución de Grupos', 'flavor-chat-ia'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <div class="dm-data-list">
                <div class="dm-data-list__item">
                    <span class="dm-data-list__label"><?php esc_html_e('Grupos Públicos', 'flavor-chat-ia'); ?></span>
                    <span class="dm-data-list__value"><?php echo esc_html($grupos_publicos); ?></span>
                </div>
                <div class="dm-data-list__item">
                    <span class="dm-data-list__label"><?php esc_html_e('Grupos Privados', 'flavor-chat-ia'); ?></span>
                    <span class="dm-data-list__value"><?php echo esc_html($grupos_privados); ?></span>
                </div>
                <div class="dm-data-list__item">
                    <span class="dm-data-list__label"><?php esc_html_e('Grupos Secretos', 'flavor-chat-ia'); ?></span>
                    <span class="dm-data-list__value"><?php echo esc_html($grupos_secretos); ?></span>
                </div>
                <div class="dm-data-list__item dm-data-list__item--highlight">
                    <span class="dm-data-list__label"><?php esc_html_e('Total Mensajes', 'flavor-chat-ia'); ?></span>
                    <span class="dm-data-list__value"><?php echo esc_html(number_format_i18n($total_mensajes)); ?></span>
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
                <?php esc_html_e('Grupos Más Activos', 'flavor-chat-ia'); ?>
            </h3>
            <span class="dm-card__subtitle"><?php esc_html_e('Últimos 30 días', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="dm-card__body">
            <?php if (empty($grupos_mas_activos)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-groups"></span>
                    <p><?php esc_html_e('No hay actividad reciente en los grupos.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-ranking">
                    <?php foreach ($grupos_mas_activos as $index => $grupo): ?>
                        <li class="dm-ranking__item">
                            <span class="dm-ranking__position"><?php echo ($index + 1); ?></span>
                            <div class="dm-ranking__avatar" style="background: <?php echo esc_attr($grupo->color ?: '#6366f1'); ?>;">
                                <?php echo mb_substr($grupo->nombre, 0, 1); ?>
                            </div>
                            <div class="dm-ranking__content">
                                <strong class="dm-ranking__name"><?php echo esc_html($grupo->nombre); ?></strong>
                                <span class="dm-ranking__meta">
                                    <?php echo esc_html($grupo->miembros_count); ?> <?php esc_html_e('miembros', 'flavor-chat-ia'); ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--info">
                                <?php echo esc_html(number_format_i18n($grupo->total_mensajes)); ?> <?php esc_html_e('msgs', 'flavor-chat-ia'); ?>
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
                <?php esc_html_e('Grupos Recientes', 'flavor-chat-ia'); ?>
            </h3>
        </div>
        <div class="dm-card__body">
            <?php if (empty($grupos_recientes)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-groups"></span>
                    <p><?php esc_html_e('No hay grupos creados todavía.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <ul class="dm-list">
                    <?php foreach ($grupos_recientes as $grupo): ?>
                        <li class="dm-list__item">
                            <div class="dm-list__content">
                                <strong class="dm-list__title"><?php echo esc_html($grupo->nombre); ?></strong>
                                <span class="dm-list__meta">
                                    <?php echo esc_html($grupo->num_miembros); ?> <?php esc_html_e('miembros', 'flavor-chat-ia'); ?>
                                    &bull;
                                    <?php echo esc_html(human_time_diff(strtotime($grupo->fecha_creacion), current_time('timestamp'))); ?>
                                </span>
                            </div>
                            <span class="dm-badge dm-badge--<?php echo $grupo->estado === 'activo' ? 'success' : 'warning'; ?>">
                                <?php echo esc_html(ucfirst($grupo->tipo)); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
