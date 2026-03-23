<?php
/**
 * Dashboard de Avisos Municipales
 *
 * Vista principal del panel de administración del módulo.
 *
 * Variables disponibles:
 *   $stats - Array con estadísticas
 *   $avisos_recientes - Array de avisos recientes
 *   $avisos_urgentes - Array de avisos urgentes
 *   $categorias - Array de categorías
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Colores de prioridad
$prioridad_classes = [
    'urgente' => 'dm-badge--error',
    'alta'    => 'dm-badge--warning',
    'media'   => 'dm-badge--info',
    'baja'    => 'dm-badge--success',
];

$prioridad_icons = [
    'urgente' => 'warning',
    'alta'    => 'flag',
    'media'   => 'info',
    'baja'    => 'yes-alt',
];
?>

<div class="wrap dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('avisos_municipales');
    }
    ?>

    <!-- Cabecera -->
    <div class="dm-header">
        <div class="dm-header__content">
            <h1 class="dm-header__title">
                <span class="dashicons dashicons-megaphone"></span>
                <?php esc_html_e('Avisos Municipales', 'flavor-chat-ia'); ?>
            </h1>
            <p class="dm-header__description">
                <?php esc_html_e('Comunicados oficiales, cortes de servicio y notificaciones a la ciudadanía', 'flavor-chat-ia'); ?>
            </p>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-nuevo')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nuevo Aviso', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Alerta de avisos urgentes -->
    <?php if (!empty($avisos_urgentes)): ?>
    <div class="dm-alert dm-alert--error">
        <span class="dashicons dashicons-warning"></span>
        <div>
            <strong><?php printf(
                _n('%d aviso urgente activo', '%d avisos urgentes activos', count($avisos_urgentes), 'flavor-chat-ia'),
                count($avisos_urgentes)
            ); ?></strong>
            <div style="margin-top: 4px;">
                <?php foreach (array_slice($avisos_urgentes, 0, 2) as $aviso_urgente): ?>
                    <span style="margin-right: 12px;"><?php echo esc_html($aviso_urgente->titulo); ?></span>
                <?php endforeach; ?>
                <?php if (count($avisos_urgentes) > 2): ?>
                    <span>+<?php echo count($avisos_urgentes) - 2; ?> <?php esc_html_e('más', 'flavor-chat-ia'); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tarjetas de estadísticas -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-megaphone"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['activos']); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Avisos Activos', 'flavor-chat-ia'); ?></div>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-activos')); ?>" class="dm-stat-card__link">
                <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?> &rarr;
            </a>
        </div>

        <div class="dm-stat-card dm-stat-card--error">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['urgentes']); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Urgentes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['proximos_expirar']); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Próximos a expirar', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($stats['visualizaciones_mes']); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Visualizaciones (mes)', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <!-- Grid de contenido principal -->
    <div class="dm-grid dm-grid--2">

        <!-- Últimos avisos -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2 class="dm-card__title">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Últimos Avisos', 'flavor-chat-ia'); ?>
                </h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-activos')); ?>" class="dm-card__action">
                    <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <div class="dm-card__body dm-card__body--no-padding">
                <?php if (!empty($avisos_recientes)): ?>
                <ul class="dm-list">
                    <?php foreach ($avisos_recientes as $aviso): ?>
                    <li class="dm-list__item">
                        <div class="dm-list__icon">
                            <span class="dashicons dashicons-<?php echo esc_attr($prioridad_icons[$aviso->prioridad] ?? 'info'); ?>"></span>
                        </div>
                        <div class="dm-list__content">
                            <div class="dm-list__title"><?php echo esc_html($aviso->titulo); ?></div>
                            <div class="dm-list__meta">
                                <span class="dm-badge <?php echo esc_attr($prioridad_classes[$aviso->prioridad] ?? 'dm-badge--info'); ?>">
                                    <?php echo esc_html(ucfirst($aviso->prioridad)); ?>
                                </span>
                                <span><?php echo esc_html($aviso->categoria ?: __('Sin categoría', 'flavor-chat-ia')); ?></span>
                                <span><?php echo esc_html(human_time_diff(strtotime($aviso->created_at), current_time('timestamp'))); ?></span>
                            </div>
                        </div>
                        <div class="dm-list__actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-nuevo&editar=' . $aviso->id)); ?>" class="dm-btn dm-btn--sm dm-btn--ghost">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="dm-empty-state">
                    <span class="dashicons dashicons-megaphone"></span>
                    <p><?php esc_html_e('No hay avisos publicados', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-nuevo')); ?>" class="dm-btn dm-btn--primary dm-btn--sm">
                        <?php esc_html_e('Crear primer aviso', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Acciones rápidas y categorías -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h2 class="dm-card__title">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php esc_html_e('Acciones Rápidas', 'flavor-chat-ia'); ?>
                </h2>
            </div>
            <div class="dm-card__body">
                <div class="dm-quick-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-nuevo')); ?>" class="dm-quick-action">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <span><?php esc_html_e('Nuevo Aviso', 'flavor-chat-ia'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-nuevo&prioridad=urgente')); ?>" class="dm-quick-action dm-quick-action--danger">
                        <span class="dashicons dashicons-warning"></span>
                        <span><?php esc_html_e('Aviso Urgente', 'flavor-chat-ia'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-archivo')); ?>" class="dm-quick-action">
                        <span class="dashicons dashicons-archive"></span>
                        <span><?php esc_html_e('Ver Archivo', 'flavor-chat-ia'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-config')); ?>" class="dm-quick-action">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <span><?php esc_html_e('Configuración', 'flavor-chat-ia'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('avisos_municipales', '')); ?>" class="dm-quick-action" target="_blank">
                        <span class="dashicons dashicons-external"></span>
                        <span><?php esc_html_e('Portal público', 'flavor-chat-ia'); ?></span>
                    </a>
                </div>

                <?php if (!empty($categorias)): ?>
                <h3 class="dm-subtitle" style="margin-top: 24px;">
                    <?php esc_html_e('Por Categoría', 'flavor-chat-ia'); ?>
                </h3>
                <div class="dm-tags">
                    <?php foreach ($categorias as $categoria): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=avisos-activos&categoria=' . urlencode($categoria->slug ?? $categoria->nombre))); ?>"
                       class="dm-tag"
                       style="--tag-color: <?php echo esc_attr($categoria->color ?? '#6366f1'); ?>">
                        <?php if (!empty($categoria->icono)): ?>
                        <span class="dashicons dashicons-<?php echo esc_attr($categoria->icono); ?>"></span>
                        <?php endif; ?>
                        <?php echo esc_html($categoria->nombre); ?>
                        <?php if (isset($categoria->count) && $categoria->count > 0): ?>
                        <span class="dm-tag__count"><?php echo number_format_i18n($categoria->count); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Estadísticas adicionales -->
    <div class="dm-grid dm-grid--3" style="margin-top: 24px;">
        <div class="dm-card dm-card--compact">
            <div class="dm-card__body">
                <div class="dm-mini-stat">
                    <span class="dashicons dashicons-archive"></span>
                    <div>
                        <div class="dm-mini-stat__value"><?php echo number_format_i18n($stats['total']); ?></div>
                        <div class="dm-mini-stat__label"><?php esc_html_e('Total histórico', 'flavor-chat-ia'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dm-card dm-card--compact">
            <div class="dm-card__body">
                <div class="dm-mini-stat">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <div>
                        <div class="dm-mini-stat__value"><?php echo number_format_i18n($stats['este_mes']); ?></div>
                        <div class="dm-mini-stat__label"><?php esc_html_e('Publicados este mes', 'flavor-chat-ia'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dm-card dm-card--compact">
            <div class="dm-card__body">
                <div class="dm-mini-stat">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <div>
                        <div class="dm-mini-stat__value"><?php echo number_format_i18n($stats['confirmaciones']); ?></div>
                        <div class="dm-mini-stat__label"><?php esc_html_e('Confirmaciones de lectura', 'flavor-chat-ia'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
