<?php
/**
 * Vista Dashboard - Foros
 *
 * Panel principal con estadísticas de temas y actividad
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_foros = $wpdb->prefix . 'flavor_foros';
$tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
$tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

$tabla_foros_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_foros'");
$tabla_hilos_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_hilos'");
$tabla_respuestas_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_respuestas'");
$tablas_disponibles = ($tabla_foros_existe || $tabla_hilos_existe || $tabla_respuestas_existe);

$total_foros = 0;
$total_hilos = 0;
$total_respuestas = 0;
$usuarios_activos = 0;

if ($tabla_foros_existe) {
    $total_foros = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_foros WHERE estado = 'activo'");
}

if ($tabla_hilos_existe) {
    $total_hilos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_hilos WHERE estado != 'eliminado'");
    $usuarios_activos = $wpdb->get_var(
        "SELECT COUNT(DISTINCT autor_id) FROM $tabla_hilos WHERE estado != 'eliminado'"
    );
}

if ($tabla_respuestas_existe) {
    $total_respuestas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_respuestas WHERE estado = 'visible'");
}

$hilos_recientes = [];
if ($tabla_hilos_existe && $tabla_foros_existe) {
    $hilos_recientes = $wpdb->get_results(
        "SELECT h.*, f.nombre AS nombre_foro, u.display_name AS nombre_autor
         FROM $tabla_hilos h
         LEFT JOIN $tabla_foros f ON f.id = h.foro_id
         LEFT JOIN {$wpdb->users} u ON u.ID = h.autor_id
         WHERE h.estado != 'eliminado'
         ORDER BY h.ultima_actividad DESC
         LIMIT 10"
    );
}

$estado_badge_classes = [
    'abierto' => 'dm-badge--success',
    'cerrado' => 'dm-badge--error',
    'fijado' => 'dm-badge--info',
];
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('foros');
    }
    ?>

    <?php if (!$tablas_disponibles): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <p><?php esc_html_e('Faltan tablas del módulo Foros o aún no hay actividad registrada.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php endif; ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-format-chat"></span>
            <h1><?php esc_html_e('Dashboard - Foros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-foros-listado')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-format-chat"></span>
            <span><?php esc_html_e('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=foros-hilos')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-comments"></span>
            <span><?php esc_html_e('Hilos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=foros-listado')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-category"></span>
            <span><?php esc_html_e('Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=foros-moderacion')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-shield"></span>
            <span><?php esc_html_e('Moderación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/mi-portal/foros/')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </a>
    </div>

    <!-- Estadísticas Principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-category"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_foros); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Foros Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-format-chat"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_hilos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Hilos/Temas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-admin-comments"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_respuestas); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Respuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($usuarios_activos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Usuarios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
    </div>

    <!-- Hilos Recientes -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h2>
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Hilos Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <?php if (!empty($hilos_recientes)) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=foros-hilos')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Ver todos los hilos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($hilos_recientes)) : ?>
            <table class="dm-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php esc_html_e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Autor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Foro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 80px;"><?php esc_html_e('Respuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 120px;"><?php esc_html_e('Última Act.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th style="width: 100px;"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hilos_recientes as $hilo) : ?>
                        <tr>
                            <td><strong>#<?php echo absint($hilo->id); ?></strong></td>
                            <td>
                                <?php if ($hilo->es_fijado) : ?>
                                    <span class="dashicons dashicons-admin-post dm-text-primary" title="<?php esc_attr_e('Fijado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></span>
                                <?php endif; ?>
                                <?php echo esc_html($hilo->titulo); ?>
                            </td>
                            <td><?php echo esc_html($hilo->nombre_autor ?: __('Anónimo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></td>
                            <td><?php echo esc_html($hilo->nombre_foro ?: '-'); ?></td>
                            <td style="text-align: center;"><?php echo absint($hilo->respuestas_count); ?></td>
                            <td>
                                <?php
                                if ($hilo->ultima_actividad) {
                                    $diferencia = human_time_diff(strtotime($hilo->ultima_actividad), current_time('timestamp'));
                                    printf(esc_html__('hace %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($diferencia));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="dm-badge <?php echo esc_attr($estado_badge_classes[$hilo->estado] ?? 'dm-badge--warning'); ?>">
                                    <?php echo esc_html(ucfirst($hilo->estado)); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="dm-empty">
                <span class="dashicons dashicons-format-chat"></span>
                <p><?php esc_html_e('No hay hilos registrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
