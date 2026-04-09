<?php
/**
 * Template: Catálogo de la Red
 * Shortcode: [flavor_network_catalog]
 */
if (!defined('ABSPATH')) exit;
?>
<div class="flavor-network-widget flavor-network-catalog-widget" data-nodo="<?php echo esc_attr($atts['nodo']); ?>" data-tipo="<?php echo esc_attr($atts['tipo']); ?>">
    <div class="fn-catalog-results">
        <div class="fn-loading"><?php _e('Cargando catálogo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>
</div>
