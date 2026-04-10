<?php
/**
 * Widget Chatbot Flotante
 *
 * Widget de chat contextual que aparece en todas las páginas Flavor.
 * Proporciona ayuda específica según el módulo actual.
 *
 * @package FlavorPlatform
 * @since 3.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Chatbot Flotante -->
<div id="flavor-ai-chatbot" class="flavor-ai-chatbot" data-module="<?php echo esc_attr($this->current_module); ?>">
    <!-- Botón Toggle -->
    <button type="button" class="chatbot-toggle" id="chatbot-toggle" aria-label="<?php esc_attr_e('Abrir asistente IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        <span class="toggle-icon-open">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C7.59 20 4 16.41 4 12C4 7.59 7.59 4 12 4C16.41 4 20 7.59 20 12C20 16.41 16.41 20 12 20Z" fill="currentColor"/>
                <path d="M12 6C9.79 6 8 7.79 8 10H10C10 8.9 10.9 8 12 8C13.1 8 14 8.9 14 10C14 11.1 13.1 12 12 12C11.45 12 11 12.45 11 13V15H13V13.83C14.74 13.4 16 11.84 16 10C16 7.79 14.21 6 12 6Z" fill="currentColor"/>
                <circle cx="12" cy="18" r="1" fill="currentColor"/>
            </svg>
        </span>
        <span class="toggle-icon-close" style="display: none;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
            </svg>
        </span>
        <span class="chatbot-badge" style="display: none;">1</span>
    </button>

    <!-- Panel del Chat -->
    <div class="chatbot-panel" id="chatbot-panel" style="display: none;">
        <!-- Header -->
        <div class="chatbot-header">
            <div class="header-info">
                <div class="header-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="header-text">
                    <span class="header-title"><?php esc_html_e('Asistente IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="header-module" id="chatbot-module-name">
                        <?php echo esc_html($module_context['name']); ?>
                    </span>
                </div>
            </div>
            <div class="header-actions">
                <button type="button" class="header-btn" id="chatbot-clear" title="<?php esc_attr_e('Limpiar chat', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
                <button type="button" class="header-btn" id="chatbot-minimize" title="<?php esc_attr_e('Minimizar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-minus"></span>
                </button>
            </div>
        </div>

        <!-- Aviso si no está configurado -->
        <?php if (!$is_configured): ?>
        <div class="chatbot-not-configured">
            <span class="dashicons dashicons-warning"></span>
            <p><?php esc_html_e('La IA no está configurada.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-platform-settings')); ?>" class="button button-small">
                <?php esc_html_e('Configurar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
        <?php endif; ?>

        <!-- Área de mensajes -->
        <div class="chatbot-messages" id="chatbot-messages">
            <!-- Mensaje de bienvenida -->
            <div class="message assistant">
                <div class="message-avatar">
                    <span class="dashicons dashicons-format-chat"></span>
                </div>
                <div class="message-content">
                    <p><?php
                        printf(
                            /* translators: %s: nombre del módulo */
                            esc_html__('¡Hola! Soy tu asistente para %s. ¿En qué puedo ayudarte?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            '<strong>' . esc_html($module_context['name']) . '</strong>'
                        );
                    ?></p>
                </div>
            </div>
        </div>

        <!-- Acciones rápidas -->
        <div class="chatbot-quick-actions" id="chatbot-quick-actions">
            <div class="quick-actions-label"><?php esc_html_e('Acciones rápidas:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div class="quick-actions-list">
                <!-- Se llenan dinámicamente según el módulo -->
            </div>
        </div>

        <!-- FAQs del módulo -->
        <div class="chatbot-faqs" id="chatbot-faqs">
            <div class="faqs-label"><?php esc_html_e('Preguntas frecuentes:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <div class="faqs-list">
                <!-- Se llenan dinámicamente según el módulo -->
            </div>
        </div>

        <!-- Input de mensaje -->
        <div class="chatbot-input-area">
            <div class="input-wrapper">
                <textarea
                    id="chatbot-input"
                    placeholder="<?php esc_attr_e('Escribe tu pregunta...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    rows="1"
                    <?php echo !$is_configured ? 'disabled' : ''; ?>
                ></textarea>
                <button
                    type="button"
                    id="chatbot-send"
                    class="send-btn"
                    <?php echo !$is_configured ? 'disabled' : ''; ?>
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 2L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <div class="input-hint">
                <span><?php esc_html_e('Enter para enviar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <!-- Indicador de typing -->
        <div class="chatbot-typing" id="chatbot-typing" style="display: none;">
            <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span class="typing-text"><?php esc_html_e('Pensando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
    </div>
</div>

<style>
/* Estilos del Chatbot Flotante */
.flavor-ai-chatbot {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 99999;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

/* Botón Toggle */
.chatbot-toggle {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
    position: relative;
}

.chatbot-toggle:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
}

.chatbot-toggle:active {
    transform: scale(0.98);
}

.chatbot-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e74c3c;
    color: white;
    font-size: 11px;
    font-weight: 600;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
}

/* Panel del Chat */
.chatbot-panel {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 380px;
    max-height: 520px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Header */
.chatbot-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-icon {
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.header-text {
    display: flex;
    flex-direction: column;
}

.header-title {
    font-weight: 600;
    font-size: 14px;
}

.header-module {
    font-size: 11px;
    opacity: 0.85;
}

.header-actions {
    display: flex;
    gap: 5px;
}

.header-btn {
    background: rgba(255, 255, 255, 0.15);
    border: none;
    border-radius: 6px;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    transition: background 0.2s;
}

.header-btn:hover {
    background: rgba(255, 255, 255, 0.25);
}

.header-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Aviso no configurado */
.chatbot-not-configured {
    padding: 20px;
    text-align: center;
    background: #fff8e5;
    border-bottom: 1px solid #f0e5c8;
}

.chatbot-not-configured .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #d69700;
    margin-bottom: 8px;
}

.chatbot-not-configured p {
    margin: 0 0 12px 0;
    font-size: 13px;
    color: #7a6a2d;
}

/* Área de mensajes */
.chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-height: 280px;
    min-height: 150px;
}

.message {
    display: flex;
    gap: 10px;
    max-width: 90%;
}

.message.user {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.message-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.message.assistant .message-avatar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.message.assistant .message-avatar .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.message.user .message-avatar {
    background: #e0e0e0;
    color: #666;
}

.message-content {
    background: #f5f5f5;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 13px;
    line-height: 1.5;
}

.message.user .message-content {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.message-content p {
    margin: 0;
}

.message-content p + p {
    margin-top: 8px;
}

/* Quick Actions */
.chatbot-quick-actions,
.chatbot-faqs {
    padding: 10px 15px;
    border-top: 1px solid #eee;
    background: #fafafa;
}

.quick-actions-label,
.faqs-label {
    font-size: 11px;
    color: #888;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.quick-actions-list,
.faqs-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.quick-action-chip,
.faq-chip {
    background: white;
    border: 1px solid #ddd;
    border-radius: 16px;
    padding: 5px 12px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 5px;
}

.quick-action-chip:hover,
.faq-chip:hover {
    background: #667eea;
    border-color: #667eea;
    color: white;
}

.quick-action-chip .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Input Area */
.chatbot-input-area {
    padding: 12px 15px;
    border-top: 1px solid #eee;
    background: white;
}

.input-wrapper {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    background: #f5f5f5;
    border-radius: 20px;
    padding: 8px 12px;
}

.chatbot-input-area textarea {
    flex: 1;
    border: none;
    background: transparent;
    resize: none;
    font-size: 13px;
    line-height: 1.4;
    max-height: 80px;
    outline: none;
    font-family: inherit;
}

.chatbot-input-area textarea::placeholder {
    color: #999;
}

.chatbot-input-area textarea:disabled {
    cursor: not-allowed;
}

.send-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    transition: all 0.2s;
    flex-shrink: 0;
}

.send-btn:hover:not(:disabled) {
    transform: scale(1.05);
}

.send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.input-hint {
    text-align: right;
    padding-top: 6px;
}

.input-hint span {
    font-size: 10px;
    color: #999;
}

/* Typing Indicator */
.chatbot-typing {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    background: #fafafa;
}

.typing-indicator {
    display: flex;
    gap: 4px;
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    background: #667eea;
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.4;
    }
    30% {
        transform: translateY(-6px);
        opacity: 1;
    }
}

.typing-text {
    font-size: 12px;
    color: #888;
}

/* Responsive */
@media (max-width: 480px) {
    .chatbot-panel {
        width: calc(100vw - 40px);
        right: -10px;
    }
}

/* Dark Mode Support */
.fls-shell-active.admin-color-flavor-dark .chatbot-panel,
body.flavor-dark-mode .chatbot-panel {
    background: #1e1e2f;
    color: #e0e0e0;
}

body.flavor-dark-mode .chatbot-messages {
    background: #1e1e2f;
}

body.flavor-dark-mode .message.assistant .message-content {
    background: #2a2a3d;
    color: #e0e0e0;
}

body.flavor-dark-mode .chatbot-input-area,
body.flavor-dark-mode .chatbot-quick-actions,
body.flavor-dark-mode .chatbot-faqs {
    background: #252536;
    border-color: #3a3a4d;
}

body.flavor-dark-mode .input-wrapper {
    background: #2a2a3d;
}

body.flavor-dark-mode .chatbot-input-area textarea {
    color: #e0e0e0;
}

body.flavor-dark-mode .quick-action-chip,
body.flavor-dark-mode .faq-chip {
    background: #2a2a3d;
    border-color: #3a3a4d;
    color: #e0e0e0;
}
</style>
