<?php
/**
 * Template: Mapa de la Red
 * Shortcode: [flavor_network_map]
 */
if (!defined('ABSPATH')) exit;
?>
<div class="flavor-network-widget flavor-network-map-widget" data-zoom="<?php echo esc_attr($atts['zoom']); ?>">
    <div class="fn-map-filters">
        <select class="fn-map-filter-tipo">
            <option value=""><?php _e('Todos los tipos', 'flavor-chat-ia'); ?></option>
            <?php foreach (Flavor_Network_Node::TIPOS_ENTIDAD as $clave_tipo => $etiqueta_tipo): ?>
                <option value="<?php echo esc_attr($clave_tipo); ?>" <?php selected($atts['tipo'], $clave_tipo); ?>>
                    <?php echo esc_html($etiqueta_tipo); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select class="fn-map-filter-nivel">
            <option value=""><?php _e('Todos los niveles', 'flavor-chat-ia'); ?></option>
            <?php foreach (Flavor_Network_Node::NIVELES_CONSCIENCIA as $clave_nivel => $etiqueta_nivel): ?>
                <option value="<?php echo esc_attr($clave_nivel); ?>"><?php echo esc_html($etiqueta_nivel); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="fn-map-filter-btn"><?php _e('Filtrar', 'flavor-chat-ia'); ?></button>
        <button type="button" class="fn-map-locate-btn"><?php _e('Mi ubicación', 'flavor-chat-ia'); ?></button>
        <input type="number" class="fn-map-radio" value="50" min="5" max="500" style="width:70px;" title="<?php esc_attr_e('Radio en km', 'flavor-chat-ia'); ?>">
        <button type="button" class="fn-map-nearby-btn"><?php _e('Buscar cerca', 'flavor-chat-ia'); ?></button>
    </div>
    <div class="fn-map-container">
        <div class="fn-map-render" style="height:<?php echo esc_attr($atts['altura']); ?>;"></div>
    </div>
</div>
