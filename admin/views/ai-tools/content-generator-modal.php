<?php
/**
 * Modal del Generador de Contenido IA
 *
 * Modal que aparece al hacer clic en "Generar con IA" en campos de texto.
 *
 * @package FlavorChatIA
 * @since 3.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Modal Generador de Contenido IA -->
<div id="flavor-ai-content-modal" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-container">
        <div class="flavor-modal-header">
            <div class="modal-title">
                <span class="dashicons dashicons-edit"></span>
                <h2><?php esc_html_e('Generar Contenido con IA', 'flavor-chat-ia'); ?></h2>
            </div>
            <button type="button" class="modal-close" id="content-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>

        <div class="flavor-modal-body">
            <!-- Tipo de contenido -->
            <div class="form-group">
                <label for="ai-content-type"><?php esc_html_e('Tipo de contenido', 'flavor-chat-ia'); ?></label>
                <select id="ai-content-type" class="regular-text">
                    <option value="evento_descripcion"><?php esc_html_e('Descripción de evento', 'flavor-chat-ia'); ?></option>
                    <option value="evento_titulo"><?php esc_html_e('Títulos de evento', 'flavor-chat-ia'); ?></option>
                    <option value="post_blog"><?php esc_html_e('Artículo de blog', 'flavor-chat-ia'); ?></option>
                    <option value="pagina_bienvenida"><?php esc_html_e('Página de bienvenida', 'flavor-chat-ia'); ?></option>
                    <option value="email_notificacion"><?php esc_html_e('Email de notificación', 'flavor-chat-ia'); ?></option>
                    <option value="descripcion_modulo"><?php esc_html_e('Descripción de módulo', 'flavor-chat-ia'); ?></option>
                    <option value="faq"><?php esc_html_e('Preguntas frecuentes', 'flavor-chat-ia'); ?></option>
                    <option value="slogan"><?php esc_html_e('Slogans creativos', 'flavor-chat-ia'); ?></option>
                    <option value="bio"><?php esc_html_e('Biografía/Descripción', 'flavor-chat-ia'); ?></option>
                    <option value="general"><?php esc_html_e('Contenido general', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Contexto / Descripción -->
            <div class="form-group">
                <label for="ai-content-context"><?php esc_html_e('Describe qué quieres generar', 'flavor-chat-ia'); ?></label>
                <textarea id="ai-content-context" rows="4" class="large-text" placeholder="<?php esc_attr_e('Ejemplo: Un evento de cocina saludable para familias el próximo sábado en el centro cívico...', 'flavor-chat-ia'); ?>"></textarea>
            </div>

            <!-- Opciones avanzadas (colapsable) -->
            <details class="advanced-options">
                <summary><?php esc_html_e('Opciones avanzadas', 'flavor-chat-ia'); ?></summary>

                <div class="options-grid">
                    <!-- Tono -->
                    <div class="form-group">
                        <label for="ai-content-tone"><?php esc_html_e('Tono', 'flavor-chat-ia'); ?></label>
                        <select id="ai-content-tone">
                            <option value="profesional"><?php esc_html_e('Profesional', 'flavor-chat-ia'); ?></option>
                            <option value="cercano"><?php esc_html_e('Cercano y amigable', 'flavor-chat-ia'); ?></option>
                            <option value="formal"><?php esc_html_e('Formal', 'flavor-chat-ia'); ?></option>
                            <option value="entusiasta"><?php esc_html_e('Entusiasta', 'flavor-chat-ia'); ?></option>
                            <option value="informativo"><?php esc_html_e('Informativo', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <!-- Longitud -->
                    <div class="form-group">
                        <label for="ai-content-length"><?php esc_html_e('Longitud', 'flavor-chat-ia'); ?></label>
                        <select id="ai-content-length">
                            <option value="corto"><?php esc_html_e('Corto (50-100 palabras)', 'flavor-chat-ia'); ?></option>
                            <option value="medio" selected><?php esc_html_e('Medio (150-250 palabras)', 'flavor-chat-ia'); ?></option>
                            <option value="largo"><?php esc_html_e('Largo (300-500 palabras)', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <!-- Idioma -->
                    <div class="form-group">
                        <label for="ai-content-language"><?php esc_html_e('Idioma', 'flavor-chat-ia'); ?></label>
                        <select id="ai-content-language">
                            <option value="es" selected><?php esc_html_e('Español', 'flavor-chat-ia'); ?></option>
                            <option value="eu"><?php esc_html_e('Euskera', 'flavor-chat-ia'); ?></option>
                            <option value="ca"><?php esc_html_e('Catalán', 'flavor-chat-ia'); ?></option>
                            <option value="gl"><?php esc_html_e('Gallego', 'flavor-chat-ia'); ?></option>
                            <option value="en"><?php esc_html_e('Inglés', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>
            </details>

            <!-- Resultado -->
            <div class="form-group result-area" id="ai-content-result-area" style="display: none;">
                <label><?php esc_html_e('Contenido generado', 'flavor-chat-ia'); ?></label>
                <div class="result-container">
                    <div class="result-content" id="ai-content-result"></div>
                    <div class="result-actions">
                        <button type="button" class="button" id="ai-content-copy">
                            <span class="dashicons dashicons-admin-page"></span>
                            <?php esc_html_e('Copiar', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" class="button button-primary" id="ai-content-insert">
                            <span class="dashicons dashicons-yes"></span>
                            <?php esc_html_e('Insertar', 'flavor-chat-ia'); ?>
                        </button>
                        <button type="button" class="button" id="ai-content-regenerate">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Regenerar', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Estado de generación -->
            <div class="generation-status" id="ai-generation-status" style="display: none;">
                <div class="spinner is-active"></div>
                <span><?php esc_html_e('Generando contenido...', 'flavor-chat-ia'); ?></span>
            </div>

            <!-- Error -->
            <div class="generation-error" id="ai-generation-error" style="display: none;">
                <span class="dashicons dashicons-warning"></span>
                <span class="error-message"></span>
            </div>
        </div>

        <div class="flavor-modal-footer">
            <button type="button" class="button" id="content-modal-cancel">
                <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="button button-primary" id="ai-content-generate">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php esc_html_e('Generar', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </div>
</div>

<style>
/* Modal Base */
.flavor-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
}

.flavor-modal-container {
    position: relative;
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    animation: modalIn 0.3s ease;
}

@keyframes modalIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Header */
.flavor-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid #eee;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px 12px 0 0;
    color: white;
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-title .dashicons {
    font-size: 22px;
    width: 22px;
    height: 22px;
}

.modal-title h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 6px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    transition: background 0.2s;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Body */
.flavor-modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 6px;
    color: #333;
}

.form-group select,
.form-group textarea,
.form-group input[type="text"] {
    width: 100%;
}

.form-group textarea {
    min-height: 100px;
    font-family: inherit;
    font-size: 13px;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    resize: vertical;
}

.form-group textarea:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
}

.form-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
    background-color: white;
}

/* Advanced Options */
.advanced-options {
    margin-bottom: 18px;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 0;
}

.advanced-options summary {
    padding: 12px 15px;
    cursor: pointer;
    font-weight: 500;
    font-size: 13px;
    color: #666;
    list-style: none;
    display: flex;
    align-items: center;
    gap: 8px;
}

.advanced-options summary::-webkit-details-marker {
    display: none;
}

.advanced-options summary::before {
    content: '\f345';
    font-family: dashicons;
    font-size: 18px;
    transition: transform 0.2s;
}

.advanced-options[open] summary::before {
    transform: rotate(90deg);
}

.advanced-options[open] summary {
    border-bottom: 1px solid #eee;
}

.options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    padding: 15px;
}

/* Result Area */
.result-area {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
}

.result-container {
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.result-content {
    padding: 15px;
    font-size: 13px;
    line-height: 1.6;
    max-height: 200px;
    overflow-y: auto;
    white-space: pre-wrap;
}

.result-actions {
    display: flex;
    gap: 8px;
    padding: 12px 15px;
    border-top: 1px solid #eee;
    background: #fafafa;
    border-radius: 0 0 6px 6px;
}

.result-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.result-actions .button .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Status */
.generation-status {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #f0f6ff;
    border-radius: 6px;
    color: #0066cc;
}

.generation-status .spinner {
    margin: 0;
}

.generation-error {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #fef2f2;
    border-radius: 6px;
    color: #dc2626;
}

.generation-error .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* Footer */
.flavor-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 18px 24px;
    border-top: 1px solid #eee;
    background: #fafafa;
    border-radius: 0 0 12px 12px;
}

.flavor-modal-footer .button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.flavor-modal-footer .button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
</style>
