<?php
/**
 * Template: Alertas Solidarias
 * Shortcode: [flavor_network_alerts]
 */
if (!defined('ABSPATH')) exit;
?>
<div class="flavor-network-widget flavor-network-alerts-widget" data-limite="<?php echo esc_attr($atts['limite']); ?>">
    <div class="fn-alerts-results">
        <div class="fn-loading"><?php _e('Cargando alertas...', 'flavor-chat-ia'); ?></div>
    </div>
</div>
