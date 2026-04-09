<?php
/**
 * Template: Alertas Solidarias
 * Shortcode: [flavor_network_alerts]
 */
if (!defined('ABSPATH')) exit;
?>
<div class="flavor-network-widget flavor-network-alerts-widget" data-limite="<?php echo esc_attr($atts['limite']); ?>">
    <div class="fn-alerts-results">
        <div class="fn-loading"><?php _e('Cargando alertas...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>
</div>
