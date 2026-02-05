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
            <option value=""><?php _e('Todas las categorias', 'flavor-chat-ia'); ?></option>
            <option value="general"><?php _e('General', 'flavor-chat-ia'); ?></option>
            <option value="tecnica"><?php _e('Tecnica', 'flavor-chat-ia'); ?></option>
            <option value="comercial"><?php _e('Comercial', 'flavor-chat-ia'); ?></option>
            <option value="logistica"><?php _e('Logistica', 'flavor-chat-ia'); ?></option>
            <option value="legal"><?php _e('Legal', 'flavor-chat-ia'); ?></option>
            <option value="otra"><?php _e('Otra', 'flavor-chat-ia'); ?></option>
        </select>
        <select class="fn-questions-estado-filtro">
            <option value=""><?php _e('Todas', 'flavor-chat-ia'); ?></option>
            <option value="abierta"><?php _e('Abiertas', 'flavor-chat-ia'); ?></option>
            <option value="respondida"><?php _e('Respondidas', 'flavor-chat-ia'); ?></option>
        </select>
        <input type="text" class="fn-questions-busqueda fn-search-input" placeholder="<?php _e('Buscar pregunta...', 'flavor-chat-ia'); ?>" style="flex:1;min-width:150px;">
        <button class="fn-questions-buscar-btn fn-search-btn"><?php _e('Buscar', 'flavor-chat-ia'); ?></button>
    </div>
    <div class="fn-questions-results">
        <div class="fn-loading"><?php _e('Cargando preguntas...', 'flavor-chat-ia'); ?></div>
    </div>
    <div class="fn-question-detail" style="display:none;">
        <div class="fn-question-detail-header"></div>
        <div class="fn-question-answers-list"></div>
    </div>
</div>
