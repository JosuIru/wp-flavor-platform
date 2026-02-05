<?php
/**
 * Template: Tablón de la Red
 * Shortcode: [flavor_network_board]
 */
if (!defined('ABSPATH')) exit;
?>
<div class="flavor-network-widget flavor-network-board-widget" data-limite="<?php echo esc_attr($atts['limite']); ?>">
    <div class="fn-board-results">
        <div class="fn-loading"><?php _e('Cargando tablón...', 'flavor-chat-ia'); ?></div>
    </div>
</div>
