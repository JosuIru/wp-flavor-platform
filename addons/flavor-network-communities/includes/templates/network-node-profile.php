<?php
/**
 * Template: Perfil de Nodo
 * Shortcode: [flavor_network_node_profile slug="mi-nodo"]
 * También acepta ?nodo=slug en la URL
 */
if (!defined('ABSPATH')) exit;
?>
<div class="flavor-network-widget flavor-network-node-profile-widget" data-slug="<?php echo esc_attr($atts['slug']); ?>">
    <div class="fn-node-profile-content">
        <div class="fn-loading"><?php _e('Cargando perfil...', 'flavor-chat-ia'); ?></div>
    </div>
</div>
