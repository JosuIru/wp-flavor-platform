<?php
/**
 * Template: Eventos de la Red
 * Shortcode: [flavor_network_events]
 */
if (!defined('ABSPATH')) exit;
?>
<div class="flavor-network-widget flavor-network-events-widget" data-limite="<?php echo esc_attr($atts['limite']); ?>">
    <div class="fn-events-results">
        <div class="fn-loading"><?php _e('Cargando eventos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>
</div>
