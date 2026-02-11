<?php
/**
 * Interfaz del Visual Builder
 *
 * @var WP_Post $post
 * @var string $mode
 * @var array $data
 * @var array $settings
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="flavor-visual-builder" class="flavor-vb-wrapper" data-post-id="<?php echo esc_attr($post->ID); ?>">

    <!-- Header del Builder -->
    <div class="flavor-vb-header">
        <div class="flavor-vb-header-left">
            <h2><?php _e('Flavor Visual Builder', 'flavor-chat-ia'); ?></h2>
            <span class="flavor-vb-version">v<?php echo Flavor_Visual_Builder::VERSION; ?></span>
        </div>

        <div class="flavor-vb-header-center">
            <!-- Selector de Modo -->
            <div class="flavor-vb-mode-selector">
                <button type="button"
                        class="flavor-vb-mode-btn <?php echo $mode === Flavor_Visual_Builder::MODE_SECTIONS ? 'active' : ''; ?>"
                        data-mode="sections">
                    <span class="dashicons dashicons-grid-view"></span>
                    <?php _e('Secciones', 'flavor-chat-ia'); ?>
                </button>
                <button type="button"
                        class="flavor-vb-mode-btn <?php echo $mode === Flavor_Visual_Builder::MODE_COMPONENTS ? 'active' : ''; ?>"
                        data-mode="components">
                    <span class="dashicons dashicons-editor-table"></span>
                    <?php _e('Componentes', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>

        <div class="flavor-vb-header-right">
            <button type="button" class="button flavor-vb-btn-undo" title="<?php esc_attr_e('Deshacer', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-undo"></span>
            </button>
            <button type="button" class="button flavor-vb-btn-redo" title="<?php esc_attr_e('Rehacer', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-redo"></span>
            </button>
            <button type="button" class="button flavor-vb-btn-preview">
                <span class="dashicons dashicons-visibility"></span>
                <?php _e('Vista Previa', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="button button-primary flavor-vb-btn-save">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Guardar', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="flavor-vb-main">

        <!-- Sidebar Izquierdo: Componentes/Secciones -->
        <div class="flavor-vb-sidebar flavor-vb-sidebar-left">

            <!-- Modo Secciones -->
            <div class="flavor-vb-panel flavor-vb-panel-sections" style="display: <?php echo $mode === Flavor_Visual_Builder::MODE_SECTIONS ? 'block' : 'none'; ?>;">
                <h3><?php _e('Secciones Disponibles', 'flavor-chat-ia'); ?></h3>
                <p class="description"><?php _e('Arrastra y suelta secciones predefinidas al canvas', 'flavor-chat-ia'); ?></p>

                <div class="flavor-vb-sections-list">
                    <div class="flavor-vb-section-item" data-section="hero">
                        <span class="dashicons dashicons-cover-image"></span>
                        <span class="flavor-vb-label"><?php _e('Hero', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-vb-section-item" data-section="features">
                        <span class="dashicons dashicons-star-filled"></span>
                        <span class="flavor-vb-label"><?php _e('Features', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-vb-section-item" data-section="cta">
                        <span class="dashicons dashicons-megaphone"></span>
                        <span class="flavor-vb-label"><?php _e('Call to Action', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-vb-section-item" data-section="testimonials">
                        <span class="dashicons dashicons-format-quote"></span>
                        <span class="flavor-vb-label"><?php _e('Testimonios', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-vb-section-item" data-section="pricing">
                        <span class="dashicons dashicons-cart"></span>
                        <span class="flavor-vb-label"><?php _e('Pricing', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-vb-section-item" data-section="faq">
                        <span class="dashicons dashicons-editor-help"></span>
                        <span class="flavor-vb-label"><?php _e('FAQ', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-vb-section-item" data-section="contact">
                        <span class="dashicons dashicons-email"></span>
                        <span class="flavor-vb-label"><?php _e('Contacto', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-vb-section-item" data-section="team">
                        <span class="dashicons dashicons-groups"></span>
                        <span class="flavor-vb-label"><?php _e('Equipo', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Modo Componentes -->
            <div class="flavor-vb-panel flavor-vb-panel-components" style="display: <?php echo $mode === Flavor_Visual_Builder::MODE_COMPONENTS ? 'block' : 'none'; ?>;">
                <h3><?php _e('Componentes', 'flavor-chat-ia'); ?></h3>
                <p class="description"><?php _e('Arrastra componentes individuales para máxima flexibilidad', 'flavor-chat-ia'); ?></p>

                <div class="flavor-vb-components-list">
                    <div class="flavor-vb-component-item" data-component="heading">
                        <span class="dashicons dashicons-editor-textcolor"></span>
                        <span class="flavor-vb-label"><?php _e('Título', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-vb-component-item" data-component="text">
                        <span class="dashicons dashicons-editor-paragraph"></span>
                        <span class="flavor-vb-label"><?php _e('Texto', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-vb-component-item" data-component="button">
                        <span class="dashicons dashicons-admin-links"></span>
                        <span class="flavor-vb-label"><?php _e('Botón', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-vb-component-item" data-component="image">
                        <span class="dashicons dashicons-format-image"></span>
                        <span class="flavor-vb-label"><?php _e('Imagen', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-vb-component-item" data-component="video">
                        <span class="dashicons dashicons-video-alt3"></span>
                        <span class="flavor-vb-label"><?php _e('Video', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-vb-component-item" data-component="spacer">
                        <span class="dashicons dashicons-minus"></span>
                        <span class="flavor-vb-label"><?php _e('Espaciador', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-vb-component-item" data-component="divider">
                        <span class="dashicons dashicons-leftright"></span>
                        <span class="flavor-vb-label"><?php _e('Divisor', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-vb-component-item" data-component="columns">
                        <span class="dashicons dashicons-columns"></span>
                        <span class="flavor-vb-label"><?php _e('Columnas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Canvas Central -->
        <div class="flavor-vb-canvas">
            <div class="flavor-vb-canvas-inner" id="flavor-vb-canvas-content">

                <!-- Mensaje cuando está vacío -->
                <div class="flavor-vb-empty-state" id="flavor-vb-empty-state">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <h3><?php _e('Comienza a crear', 'flavor-chat-ia'); ?></h3>
                    <p><?php _e('Arrastra secciones o componentes desde la barra lateral', 'flavor-chat-ia'); ?></p>
                </div>

                <!-- Aquí se renderizan las secciones/componentes -->

            </div>
        </div>

        <!-- Sidebar Derecho: Propiedades -->
        <div class="flavor-vb-sidebar flavor-vb-sidebar-right">
            <div class="flavor-vb-panel flavor-vb-panel-properties">
                <h3><?php _e('Propiedades', 'flavor-chat-ia'); ?></h3>
                <p class="description"><?php _e('Selecciona un elemento para editar sus propiedades', 'flavor-chat-ia'); ?></p>

                <div id="flavor-vb-properties-content" class="flavor-vb-properties-content">
                    <!-- Las propiedades se cargan dinámicamente -->
                    <div class="flavor-vb-no-selection">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <p><?php _e('Ningún elemento seleccionado', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <div class="flavor-vb-footer">
        <div class="flavor-vb-footer-left">
            <span class="flavor-vb-status" id="flavor-vb-status">
                <?php _e('Listo', 'flavor-chat-ia'); ?>
            </span>
        </div>
        <div class="flavor-vb-footer-right">
            <span class="flavor-vb-info">
                <?php _e('Última modificación:', 'flavor-chat-ia'); ?>
                <span id="flavor-vb-last-saved"><?php echo get_the_modified_date('d/m/Y H:i', $post); ?></span>
            </span>
        </div>
    </div>

    <!-- Modal de Preview -->
    <div id="flavor-vb-preview-modal" class="flavor-vb-modal" style="display: none;">
        <div class="flavor-vb-modal-overlay"></div>
        <div class="flavor-vb-modal-content">
            <div class="flavor-vb-modal-header">
                <h2><?php _e('Vista Previa', 'flavor-chat-ia'); ?></h2>
                <button type="button" class="flavor-vb-modal-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="flavor-vb-modal-body">
                <iframe id="flavor-vb-preview-frame"></iframe>
            </div>
        </div>
    </div>

</div>

<!-- Hidden field para guardar datos -->
<input type="hidden" name="flavor_vb_mode" id="flavor_vb_mode" value="<?php echo esc_attr($mode); ?>">
<input type="hidden" name="flavor_vb_data" id="flavor_vb_data" value="<?php echo esc_attr(json_encode($data)); ?>">
<input type="hidden" name="flavor_vb_settings" id="flavor_vb_settings" value="<?php echo esc_attr(json_encode($settings)); ?>">

<style>
/* Estilos inline básicos - se moverán a CSS externo */
#flavor-visual-builder {
    background: #f0f0f1;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin: 20px 0;
}

.flavor-vb-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 20px;
    background: #fff;
    border-bottom: 1px solid #c3c4c7;
}

.flavor-vb-header-left h2 {
    margin: 0;
    font-size: 18px;
    display: inline-block;
    margin-right: 10px;
}

.flavor-vb-version {
    color: #757575;
    font-size: 12px;
}

.flavor-vb-mode-selector {
    display: flex;
    gap: 5px;
}

.flavor-vb-mode-btn {
    padding: 8px 16px;
    border: 1px solid #c3c4c7;
    background: #fff;
    cursor: pointer;
    border-radius: 3px;
    transition: all 0.2s;
}

.flavor-vb-mode-btn.active {
    background: #2271b1;
    color: #fff;
    border-color: #2271b1;
}

.flavor-vb-header-right {
    display: flex;
    gap: 8px;
}

.flavor-vb-main {
    display: flex;
    min-height: 600px;
}

.flavor-vb-sidebar {
    width: 280px;
    background: #fff;
    border-right: 1px solid #c3c4c7;
    overflow-y: auto;
}

.flavor-vb-sidebar-right {
    border-right: none;
    border-left: 1px solid #c3c4c7;
}

.flavor-vb-canvas {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
}

.flavor-vb-canvas-inner {
    background: #fff;
    min-height: 500px;
    border-radius: 4px;
    padding: 20px;
}

.flavor-vb-empty-state {
    text-align: center;
    padding: 100px 20px;
    color: #757575;
}

.flavor-vb-empty-state .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    opacity: 0.3;
}

.flavor-vb-panel {
    padding: 20px;
}

.flavor-vb-panel h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
}

.flavor-vb-sections-list,
.flavor-vb-components-list {
    display: grid;
    gap: 10px;
    margin-top: 15px;
}

.flavor-vb-section-item,
.flavor-vb-component-item {
    display: flex;
    align-items: center;
    padding: 12px;
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    cursor: move;
    transition: all 0.2s;
}

.flavor-vb-section-item:hover,
.flavor-vb-component-item:hover {
    background: #e0e0e0;
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.flavor-vb-section-item .dashicons,
.flavor-vb-component-item .dashicons {
    margin-right: 10px;
    color: #2271b1;
}

.flavor-vb-footer {
    display: flex;
    justify-content: space-between;
    padding: 10px 20px;
    background: #fff;
    border-top: 1px solid #c3c4c7;
    font-size: 12px;
    color: #757575;
}

.flavor-vb-no-selection {
    text-align: center;
    padding: 40px 20px;
    color: #757575;
}

.flavor-vb-no-selection .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    opacity: 0.3;
}
</style>
