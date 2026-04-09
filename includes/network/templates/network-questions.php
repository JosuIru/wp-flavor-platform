<?php
/**
 * Template: Preguntas a la Red
 * Shortcode: [flavor_network_questions]
 */
if (!defined('ABSPATH')) exit;
?>
<div class="flavor-network-widget flavor-network-questions-widget" data-categoria="<?php echo esc_attr($atts['categoria']); ?>" data-limite="<?php echo esc_attr($atts['limite']); ?>">
    <div class="fn-filters" style="margin-bottom:15px;display:flex;gap:10px;flex-wrap:wrap;">
        <select class="fn-questions-categoria-filtro">
            <option value=""><?php _e('Todas las categorias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="general"><?php _e('General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="tecnica"><?php _e('Tecnica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="comercial"><?php _e('Comercial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="logistica"><?php _e('Logistica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="legal"><?php _e('Legal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="otra"><?php _e('Otra', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
        </select>
        <select class="fn-questions-estado-filtro">
            <option value=""><?php _e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="abierta"><?php _e('Abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="respondida"><?php _e('Respondidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
        </select>
        <input type="text" class="fn-questions-busqueda fn-search-input" placeholder="<?php _e('Buscar pregunta...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" style="flex:1;min-width:150px;">
        <button class="fn-questions-buscar-btn fn-search-btn"><?php _e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
    </div>
    <div class="fn-questions-results">
        <div class="fn-loading"><?php _e('Cargando preguntas...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>
    <div class="fn-question-detail" style="display:none;">
        <div class="fn-question-detail-header"></div>
        <div class="fn-question-answers-list"></div>
    </div>
</div>
