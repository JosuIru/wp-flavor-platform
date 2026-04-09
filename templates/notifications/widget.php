<?php
/**
 * Template: Widget de Notificaciones
 *
 * Variables disponibles:
 * - $notifications: Array de notificaciones
 * - $unread_count: Contador de no leídas
 * - $atts: Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-notifications-widget" id="flavor-notifications-widget">
    <div class="flavor-notifications-header">
        <div class="flavor-notifications-title">
            <span class="flavor-notifications-icon">🔔</span>
            <h3><?php _e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <?php if ($unread_count > 0) : ?>
                <span class="flavor-notifications-badge"><?php echo esc_html($unread_count); ?></span>
            <?php endif; ?>
        </div>

        <div class="flavor-notifications-actions">
            <?php if (!empty($notifications)) : ?>
                <button type="button" class="flavor-notifications-mark-all" data-action="mark-all-read">
                    <span class="dashicons dashicons-yes"></span>
                    <?php _e('Marcar todas como leídas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="flavor-notifications-list">
        <?php if (empty($notifications)) : ?>
            <div class="flavor-notifications-empty">
                <div class="flavor-notifications-empty-icon">📭</div>
                <p><?php _e('No tienes notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        <?php else : ?>
            <?php foreach ($notifications as $notification) : ?>
                <div class="flavor-notification-item <?php echo $notification['is_read'] ? 'is-read' : 'is-unread'; ?> flavor-notification-<?php echo esc_attr($notification['type']); ?>"
                     data-id="<?php echo esc_attr($notification['id']); ?>"
                     data-module="<?php echo esc_attr($notification['module_id']); ?>">

                    <div class="flavor-notification-icon">
                        <?php
                        $icons = [
                            'info' => '📘',
                            'success' => '✅',
                            'warning' => '⚠️',
                            'error' => '❌',
                        ];
                        echo $icons[$notification['type']] ?? '📘';
                        ?>
                    </div>

                    <div class="flavor-notification-content">
                        <div class="flavor-notification-header-row">
                            <h4 class="flavor-notification-title"><?php echo esc_html($notification['title']); ?></h4>
                            <?php if (!empty($notification['module_id'])) : ?>
                                <span class="flavor-notification-module"><?php echo esc_html(ucfirst(str_replace('_', ' ', $notification['module_id']))); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="flavor-notification-message">
                            <?php echo wp_kses_post($notification['message']); ?>
                        </div>

                        <div class="flavor-notification-meta">
                            <span class="flavor-notification-time">
                                <?php echo human_time_diff(strtotime($notification['created_at']), current_time('timestamp')); ?> <?php _e('atrás', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>

                            <?php if (!$notification['is_read']) : ?>
                                <span class="flavor-notification-unread-indicator">•</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flavor-notification-actions-cell">
                        <?php if (!empty($notification['link'])) : ?>
                            <a href="<?php echo esc_url($notification['link']); ?>"
                               class="flavor-notification-action flavor-notification-view"
                               title="<?php esc_attr_e('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <span class="dashicons dashicons-external"></span>
                            </a>
                        <?php endif; ?>

                        <?php if (!$notification['is_read']) : ?>
                            <button type="button"
                                    class="flavor-notification-action flavor-notification-mark-read"
                                    data-id="<?php echo esc_attr($notification['id']); ?>"
                                    title="<?php esc_attr_e('Marcar como leída', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <span class="dashicons dashicons-yes"></span>
                            </button>
                        <?php endif; ?>

                        <button type="button"
                                class="flavor-notification-action flavor-notification-delete"
                                data-id="<?php echo esc_attr($notification['id']); ?>"
                                title="<?php esc_attr_e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($atts['show_all_link'] === 'yes' && !empty($notifications)) : ?>
        <div class="flavor-notifications-footer">
            <a href="<?php echo Flavor_Chat_Helpers::get_action_url('', '') . '#notifications'; ?>" class="flavor-notifications-view-all">
                <?php _e('Ver todas las notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.flavor-notifications-widget {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
}

.flavor-notifications-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.flavor-notifications-title {
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-notifications-title h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.flavor-notifications-icon {
    font-size: 20px;
}

.flavor-notifications-badge {
    background: #ef4444;
    color: white;
    font-size: 12px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 12px;
    min-width: 20px;
    text-align: center;
}

.flavor-notifications-mark-all {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: #f3f4f6;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-notifications-mark-all:hover {
    background: #e5e7eb;
}

.flavor-notifications-list {
    max-height: 400px;
    overflow-y: auto;
}

.flavor-notifications-empty {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
}

.flavor-notifications-empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.flavor-notification-item {
    display: flex;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.2s;
}

.flavor-notification-item:hover {
    background: #f9fafb;
}

.flavor-notification-item.is-unread {
    background: #eff6ff;
}

.flavor-notification-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.flavor-notification-content {
    flex: 1;
    min-width: 0;
}

.flavor-notification-header-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.flavor-notification-title {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: #111827;
}

.flavor-notification-module {
    font-size: 11px;
    padding: 2px 8px;
    background: #e5e7eb;
    color: #6b7280;
    border-radius: 4px;
    text-transform: capitalize;
}

.flavor-notification-message {
    font-size: 13px;
    color: #4b5563;
    margin-bottom: 8px;
    line-height: 1.5;
}

.flavor-notification-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #9ca3af;
}

.flavor-notification-unread-indicator {
    color: #3b82f6;
    font-size: 16px;
}

.flavor-notification-actions-cell {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
}

.flavor-notification-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    color: #6b7280;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-notification-action:hover {
    background: #f3f4f6;
    color: #111827;
}

.flavor-notification-delete:hover {
    background: #fee2e2;
    color: #dc2626;
}

.flavor-notifications-footer {
    padding: 12px 20px;
    border-top: 1px solid #e5e7eb;
    text-align: center;
}

.flavor-notifications-view-all {
    color: #3b82f6;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.flavor-notifications-view-all:hover {
    text-decoration: underline;
}

/* Tipos de notificación */
.flavor-notification-success .flavor-notification-icon {
    color: #10b981;
}

.flavor-notification-warning .flavor-notification-icon {
    color: #f59e0b;
}

.flavor-notification-error .flavor-notification-icon {
    color: #ef4444;
}
</style>
