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
            <option value=""><?php _e('Todas las colaboraciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="compra_colectiva"><?php _e('Compras colectivas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="logistica"><?php _e('Logística compartida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="proyecto"><?php _e('Proyectos conjuntos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="alianza"><?php _e('Alianzas temáticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="hermanamiento"><?php _e('Hermanamientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="mentoria"><?php _e('Mentoría cruzada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
        </select>
    </div>
    <div class="fn-collaborations-results">
        <div class="fn-loading"><?php _e('Cargando colaboraciones...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>
</div>
