<?php
/**
 * Dashboard de Estados/Stories
 *
 * Panel administrativo para gestión de estados efímeros tipo WhatsApp/Instagram Stories.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_estados = $wpdb->prefix . 'flavor_chat_estados';
$tabla_visualizaciones = $wpdb->prefix . 'flavor_chat_estados_vistas';
$tabla_reportes = $wpdb->prefix . 'flavor_chat_estados_reportes';

$tabla_estados_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_estados'") === $tabla_estados;

// Estadísticas generales
$total_estados = 0;
$estados_activos = 0;
$estados_24h = 0;
$total_visualizaciones = 0;
$usuarios_con_estados = 0;
$total_reportes_pendientes = 0;

if ($tabla_estados_existe) {
    $total_estados = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_estados");
    $estados_activos = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_estados WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    $estados_24h = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_estados WHERE DATE(created_at) = CURDATE()"
    );
    $usuarios_con_estados = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT user_id) FROM $tabla_estados WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
}

if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_visualizaciones'") === $tabla_visualizaciones) {
    $total_visualizaciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_visualizaciones");
}

if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_reportes'") === $tabla_reportes) {
    $total_reportes_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_reportes WHERE estado = 'pendiente'");
}

// Distribución por tipo
$estados_por_tipo = [];
if ($tabla_estados_existe) {
    $estados_por_tipo = $wpdb->get_results(
        "SELECT tipo, COUNT(*) as cantidad FROM $tabla_estados
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
         GROUP BY tipo ORDER BY cantidad DESC",
        ARRAY_A
    );
}

// Top usuarios activos
$top_usuarios = [];
if ($tabla_estados_existe) {
    $top_usuarios = $wpdb->get_results(
        "SELECT e.user_id, u.display_name, COUNT(*) as total_estados
         FROM $tabla_estados e
         LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
         WHERE e.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
         GROUP BY e.user_id ORDER BY total_estados DESC LIMIT 5"
    );
}

// Últimos estados
$ultimos_estados = [];
if ($tabla_estados_existe) {
    $ultimos_estados = $wpdb->get_results(
        "SELECT e.id, e.tipo, e.created_at, u.display_name
         FROM $tabla_estados e
         LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
         ORDER BY e.created_at DESC LIMIT 8"
    );
}

$tipos_labels = [
    'texto' => __('Texto', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'imagen' => __('Imagen', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'video' => __('Video', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'audio' => __('Audio', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'ubicacion' => __('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="wrap dm-dashboard">
    <?php flavor_dashboard_help('chat_estados'); ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-format-status" style="color: #06b6d4;"></span>
            <h1><?php esc_html_e('Estados / Stories', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=chat-estados-config')); ?>" class="button">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-card">
        <div class="dm-action-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=chat-estados-listado')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-images-alt2"></span>
                <span><?php esc_html_e('Ver Estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=chat-estados-reportes')); ?>" class="dm-action-item" style="position: relative;">
                <span class="dashicons dashicons-flag"></span>
                <span><?php esc_html_e('Reportes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php if ($total_reportes_pendientes > 0): ?>
                <span class="dm-badge dm-badge--danger" style="position: absolute; top: 5px; right: 10px;"><?php echo esc_html($total_reportes_pendientes); ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=chat-estados-estadisticas')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-chart-area"></span>
                <span><?php esc_html_e('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=chat-estados-config')); ?>" class="dm-action-item">
                <span class="dashicons dashicons-admin-generic"></span>
                <span><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>
    </div>

    <!-- Estadísticas Principales -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-share-alt2"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html($estados_activos); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Estados Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="dm-stat-card__meta"><?php esc_html_e('últimas 24h', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html(number_format($total_visualizaciones)); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Visualizaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html($usuarios_con_estados); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Usuarios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-plus-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <span class="dm-stat-card__value"><?php echo esc_html($estados_24h); ?></span>
                <span class="dm-stat-card__label"><?php esc_html_e('Estados Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <?php if ($total_reportes_pendientes > 0): ?>
    <div class="dm-alert dm-alert--danger">
        <span class="dashicons dashicons-flag"></span>
        <div class="dm-alert__content">
            <strong><?php echo esc_html($total_reportes_pendientes); ?> <?php esc_html_e('estados reportados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
            <a href="<?php echo esc_url(admin_url('admin.php?page=chat-estados-reportes')); ?>" class="button">
                <?php esc_html_e('Revisar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="dm-grid dm-grid--2">
        <!-- Últimos Estados -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Últimos Estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body">
                <?php if (!empty($ultimos_estados)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Hace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_estados as $estado): ?>
                        <tr>
                            <td><span class="dm-badge dm-badge--info"><?php echo esc_html($tipos_labels[$estado->tipo] ?? ucfirst($estado->tipo)); ?></span></td>
                            <td><?php echo esc_html($estado->display_name ?: 'Usuario'); ?></td>
                            <td><?php echo esc_html(human_time_diff(strtotime($estado->created_at))); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="dm-empty"><?php esc_html_e('No hay estados recientes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Usuarios Más Activos -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><?php esc_html_e('Top Usuarios (24h)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="dm-card__body">
                <?php if (!empty($top_usuarios)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_usuarios as $usuario): ?>
                        <tr>
                            <td><strong><?php echo esc_html($usuario->display_name ?: '#' . $usuario->user_id); ?></strong></td>
                            <td><span class="dm-badge dm-badge--success"><?php echo esc_html($usuario->total_estados); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="dm-empty"><?php esc_html_e('Sin actividad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Distribución por Tipo -->
    <?php if (!empty($estados_por_tipo)): ?>
    <div class="dm-card">
        <div class="dm-card__header">
            <h3><?php esc_html_e('Distribución por Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="dm-card__body">
            <div class="dm-distribution">
                <?php
                $colores = ['success', 'info', 'warning', 'primary', 'secondary'];
                $total_tipos = array_sum(array_column($estados_por_tipo, 'cantidad'));
                $indice_color = 0;
                foreach ($estados_por_tipo as $tipo_data):
                    $porcentaje = $total_tipos > 0 ? ($tipo_data['cantidad'] / $total_tipos) * 100 : 0;
                    $color = $colores[$indice_color % count($colores)];
                    $indice_color++;
                ?>
                <div class="dm-distribution__item">
                    <div class="dm-distribution__label">
                        <span><?php echo esc_html($tipos_labels[$tipo_data['tipo']] ?? ucfirst($tipo_data['tipo'])); ?></span>
                        <span><?php echo esc_html($tipo_data['cantidad']); ?></span>
                    </div>
                    <div class="dm-progress">
                        <div class="dm-progress__bar dm-progress__bar--<?php echo esc_attr($color); ?>" style="width: <?php echo esc_attr($porcentaje); ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Info -->
    <div class="dm-card" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white;">
        <div class="dm-card__body" style="text-align: center;">
            <span class="dashicons dashicons-clock" style="font-size: 32px; width: 32px; height: 32px;"></span>
            <p style="margin: 10px 0 0; opacity: 0.9;">
                <?php esc_html_e('Los estados desaparecen automáticamente después de 24 horas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>
    </div>
</div>
