<?php
/**
 * Template: Directorio de la Red
 * Shortcode: [flavor_network_directory]
 */
if (!defined('ABSPATH')) exit;
?>
<div class="flavor-network-widget flavor-network-directory-widget" data-limite="<?php echo esc_attr($atts['limite']); ?>">
    <div class="fn-directory-filters">
        <input type="text" class="fn-search-input" placeholder="<?php esc_attr_e('Buscar comunidades, empresas...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        <select class="fn-filter-tipo">
            <option value=""><?php _e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <?php foreach (Flavor_Network_Node::TIPOS_ENTIDAD as $clave_tipo => $etiqueta_tipo): ?>
                <option value="<?php echo esc_attr($clave_tipo); ?>" <?php selected($atts['tipo'], $clave_tipo); ?>>
                    <?php echo esc_html($etiqueta_tipo); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select class="fn-filter-nivel">
            <option value=""><?php _e('Todos los niveles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <?php foreach (Flavor_Network_Node::NIVELES_CONSCIENCIA as $clave_nivel => $etiqueta_nivel): ?>
                <option value="<?php echo esc_attr($clave_nivel); ?>"><?php echo esc_html($etiqueta_nivel); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="fn-search-btn"><?php _e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
    </div>
    <div class="fn-directory-results">
        <div class="fn-loading"><?php _e('Cargando directorio...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>
</div>
