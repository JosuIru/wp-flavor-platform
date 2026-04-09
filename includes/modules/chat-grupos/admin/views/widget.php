<?php
/**
 * Vista: Widget Dashboard de Chat de Grupos
 *
 * Variables disponibles:
 * - $estadisticas: array con totales
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$estadisticas = $estadisticas ?? [
    'total_grupos' => 0,
    'total_mensajes' => 0,
    'total_miembros' => 0,
    'mensajes_hoy' => 0,
];
?>

<div class="flavor-chat-widget">
    <div class="flavor-widget-stats">
        <div class="flavor-widget-stat">
            <span class="flavor-widget-stat-numero"><?php echo number_format_i18n($estadisticas['total_grupos']); ?></span>
            <span class="flavor-widget-stat-label"><?php _e('Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="flavor-widget-stat">
            <span class="flavor-widget-stat-numero"><?php echo number_format_i18n($estadisticas['total_miembros']); ?></span>
            <span class="flavor-widget-stat-label"><?php _e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="flavor-widget-stat">
            <span class="flavor-widget-stat-numero"><?php echo number_format_i18n($estadisticas['total_mensajes']); ?></span>
            <span class="flavor-widget-stat-label"><?php _e('Mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="flavor-widget-stat flavor-widget-stat-highlight">
            <span class="flavor-widget-stat-numero"><?php echo number_format_i18n($estadisticas['mensajes_hoy']); ?></span>
            <span class="flavor-widget-stat-label"><?php _e('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <div class="flavor-widget-acciones">
        <a href="<?php echo admin_url('admin.php?page=chat-grupos'); ?>" class="button">
            <?php _e('Ver grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=chat-grupos-moderacion'); ?>" class="button">
            <?php _e('Moderacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>
</div>

<style>
.flavor-chat-widget {
    padding: 10px 0;
}
.flavor-widget-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}
.flavor-widget-stat {
    text-align: center;
    padding: 10px;
    background: #f6f7f7;
    border-radius: 4px;
}
.flavor-widget-stat-highlight {
    background: #d4edda;
}
.flavor-widget-stat-numero {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: #1e1e1e;
}
.flavor-widget-stat-label {
    display: block;
    font-size: 11px;
    color: #646970;
    text-transform: uppercase;
}
.flavor-widget-acciones {
    display: flex;
    gap: 10px;
}
</style>
