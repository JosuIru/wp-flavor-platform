<?php
/**
 * Template: Colaboraciones de la Red
 * Shortcode: [flavor_network_collaborations]
 */
if (!defined('ABSPATH')) exit;
?>
<div class="flavor-network-widget flavor-network-collaborations-widget" data-tipo="<?php echo esc_attr($atts['tipo']); ?>" data-limite="<?php echo esc_attr($atts['limite']); ?>">
    <div class="fn-filters" style="margin-bottom:15px;display:flex;gap:10px;flex-wrap:wrap;">
        <select class="fn-colab-tipo-filtro">
            <option value=""><?php _e('Todas las colaboraciones', 'flavor-chat-ia'); ?></option>
            <option value="compra_colectiva"><?php _e('Compras colectivas', 'flavor-chat-ia'); ?></option>
            <option value="logistica"><?php _e('Logística compartida', 'flavor-chat-ia'); ?></option>
            <option value="proyecto"><?php _e('Proyectos conjuntos', 'flavor-chat-ia'); ?></option>
            <option value="alianza"><?php _e('Alianzas temáticas', 'flavor-chat-ia'); ?></option>
            <option value="hermanamiento"><?php _e('Hermanamientos', 'flavor-chat-ia'); ?></option>
            <option value="mentoria"><?php _e('Mentoría cruzada', 'flavor-chat-ia'); ?></option>
        </select>
    </div>
    <div class="fn-collaborations-results">
        <div class="fn-loading"><?php _e('Cargando colaboraciones...', 'flavor-chat-ia'); ?></div>
    </div>
</div>
