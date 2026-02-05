<?php
/**
 * Template: Banco de Tiempo de la Red
 * Shortcode: [flavor_network_time_offers]
 */
if (!defined('ABSPATH')) exit;
?>
<div class="flavor-network-widget flavor-network-time-offers-widget" data-tipo="<?php echo esc_attr($atts['tipo']); ?>" data-limite="<?php echo esc_attr($atts['limite']); ?>">
    <div class="fn-filters" style="margin-bottom:15px;display:flex;gap:10px;flex-wrap:wrap;">
        <select class="fn-tiempo-tipo-filtro">
            <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
            <option value="oferta"><?php _e('Ofertas', 'flavor-chat-ia'); ?></option>
            <option value="demanda"><?php _e('Demandas', 'flavor-chat-ia'); ?></option>
        </select>
    </div>
    <div class="fn-time-offers-results">
        <div class="fn-loading"><?php _e('Cargando ofertas de tiempo...', 'flavor-chat-ia'); ?></div>
    </div>
</div>
