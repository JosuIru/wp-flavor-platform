<?php
/**
 * Clase principal del Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Core {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Flag para evitar renderizar el widget más de una vez
     */
    private static $widget_rendered = false;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Chat_Core
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        // Shortcode para el widget
        add_shortcode('flavor_chat', [$this, 'render_chat_shortcode']);

        // Widget flotante
        add_action('wp_footer', [$this, 'render_floating_widget']);
    }

    /**
     * Renderiza el shortcode del chat
     *
     * @param array $atts
     * @return string
     */
    public function render_chat_shortcode($atts) {
        $settings = get_option('flavor_chat_ia_settings', []);

        if (empty($settings['enabled'])) {
            return '';
        }

        $atts = shortcode_atts([
            'style' => 'embedded', // embedded o floating
            'language' => 'es',
        ], $atts);

        ob_start();
        $this->render_chat_widget($atts['style'], $atts['language']);
        return ob_get_clean();
    }

    /**
     * Renderiza el widget flotante en el footer
     */
    public function render_floating_widget() {
        $settings = get_option('flavor_chat_ia_settings', []);

        if (empty($settings['enabled'])) {
            return;
        }

        if (empty($settings['show_floating_widget'])) {
            return;
        }

        // No mostrar en admin
        if (is_admin()) {
            return;
        }

        $this->render_chat_widget('floating');
    }

    /**
     * Renderiza el widget del chat
     *
     * @param string $style
     * @param string $language
     */
    private function render_chat_widget($style = 'floating', $language = 'es') {
        // Evitar renderizar el widget más de una vez
        if (self::$widget_rendered) {
            return;
        }
        self::$widget_rendered = true;

        $settings = get_option('flavor_chat_ia_settings', []);
        $appearance = $settings['appearance'] ?? [];

        $assistant_name = $settings['assistant_name'] ?? __('Asistente Virtual', 'flavor-chat-ia');
        $position = $appearance['position'] ?? 'bottom-right';
        $primary_color = $appearance['primary_color'] ?? '#0073aa';
        $header_bg = $appearance['header_bg'] ?? '#1e3a5f';
        $user_bubble = $appearance['user_bubble'] ?? $primary_color;
        $assistant_bubble = $appearance['assistant_bubble'] ?? '#f0f0f0';
        $widget_width = $appearance['widget_width'] ?? 380;
        $widget_height = $appearance['widget_height'] ?? 500;
        $border_radius = $appearance['border_radius'] ?? 16;
        $bottom_offset = $appearance['bottom_offset'] ?? 20;
        $side_offset = $appearance['side_offset'] ?? 20;
        $trigger_size = $appearance['trigger_size'] ?? 'medium';
        $trigger_animation = $appearance['trigger_animation'] ?? 'pulse';
        $welcome_message = $appearance['welcome_message'] ?? __('¡Hola! ¿En qué puedo ayudarte?', 'flavor-chat-ia');
        $placeholder = $appearance['placeholder'] ?? __('Escribe tu mensaje...', 'flavor-chat-ia');
        $avatar_url = $appearance['avatar_url'] ?? '';

        // Generar ID de sesión
        $session_id = 'fcia_' . wp_generate_password(16, false);

        // CSS variables - deben coincidir con chat-widget.css
        $css_vars = sprintf(
            '--chat-ia-primary: %s; --chat-ia-header-bg: %s; --chat-ia-user-bubble: %s; --chat-ia-assistant-bubble: %s; --chat-ia-width: %dpx; --chat-ia-height: %dpx; --chat-ia-radius: %dpx; --chat-ia-bottom-offset: %dpx; --chat-ia-side-offset: %dpx;',
            esc_attr($primary_color),
            esc_attr($header_bg),
            esc_attr($user_bubble),
            esc_attr($assistant_bubble),
            intval($widget_width),
            intval($widget_height),
            intval($border_radius),
            intval($bottom_offset),
            intval($side_offset)
        );

        ?>
        <div id="chat-ia-widget"
             class="chat-ia-widget chat-ia-<?php echo esc_attr($style); ?> chat-ia-position-<?php echo esc_attr($position); ?> chat-ia-trigger-<?php echo esc_attr($trigger_size); ?> chat-ia-animation-<?php echo esc_attr($trigger_animation); ?>"
             data-session="<?php echo esc_attr($session_id); ?>"
             data-language="<?php echo esc_attr($language); ?>"
             style="<?php echo $css_vars; ?>">

            <?php if ($style === 'floating'): ?>
            <!-- Botón flotante -->
            <button type="button" id="chat-ia-trigger" class="chat-ia-trigger" aria-label="<?php echo esc_attr__('Abrir chat', 'flavor-chat-ia'); ?>">
                <?php if ($avatar_url): ?>
                <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="chat-ia-trigger-avatar">
                <?php else: ?>
                <svg class="chat-ia-icon-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <?php endif; ?>
                <svg class="chat-ia-icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <?php endif; ?>

            <!-- Contenedor del chat -->
            <div class="chat-ia-container <?php echo $style === 'floating' ? 'chat-ia-hidden' : ''; ?>">
                <!-- Header -->
                <div class="chat-ia-header">
                    <div class="chat-ia-header-info">
                        <div class="chat-ia-avatar">
                            <?php if ($avatar_url): ?>
                            <img src="<?php echo esc_url($avatar_url); ?>" alt="">
                            <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                            </svg>
                            <?php endif; ?>
                        </div>
                        <div class="chat-ia-header-text">
                            <span class="chat-ia-name"><?php echo esc_html($assistant_name); ?></span>
                            <span class="chat-ia-status"><?php esc_html_e('En línea', 'flavor-chat-ia'); ?></span>
                        </div>
                    </div>
                    <?php if ($style === 'floating'): ?>
                    <button type="button" id="chat-ia-minimize" class="chat-ia-minimize" aria-label="<?php echo esc_attr__('Minimizar', 'flavor-chat-ia'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Mensajes -->
                <div id="chat-ia-messages" class="chat-ia-messages" role="log" aria-live="polite">
                    <!-- Mensaje de bienvenida -->
                    <div class="chat-ia-message chat-ia-message-assistant">
                        <div class="chat-ia-message-content">
                            <?php echo esc_html($welcome_message); ?>
                        </div>
                    </div>

                    <?php $this->render_quick_actions($settings); ?>
                </div>

                <!-- Indicador de escritura -->
                <div id="chat-ia-typing" class="chat-ia-typing chat-ia-hidden">
                    <div class="chat-ia-typing-indicator">
                        <span></span><span></span><span></span>
                    </div>
                    <span class="chat-ia-typing-label"><?php esc_html_e('Escribiendo...', 'flavor-chat-ia'); ?></span>
                </div>

                <!-- Input -->
                <div class="chat-ia-input-container">
                    <form id="chat-ia-form" class="chat-ia-form">
                        <!-- Honeypot antispam - campo oculto que los bots rellenan -->
                        <input type="text" name="website_url" id="chat-ia-honeypot" value="" style="position:absolute;left:-9999px;opacity:0;height:0;width:0;" tabindex="-1" autocomplete="off" aria-hidden="true">
                        <!-- Botón micrófono para voz -->
                        <button type="button" id="chat-ia-mic" class="chat-ia-mic" aria-label="<?php echo esc_attr__('Hablar', 'flavor-chat-ia'); ?>" title="<?php echo esc_attr__('Pulsa para hablar', 'flavor-chat-ia'); ?>">
                            <svg class="chat-ia-mic-icon" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                                <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                            </svg>
                            <svg class="chat-ia-mic-stop" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
                                <rect x="6" y="6" width="12" height="12" rx="2"/>
                            </svg>
                        </button>
                        <input type="text"
                               id="chat-ia-input"
                               class="chat-ia-input"
                               placeholder="<?php echo esc_attr($placeholder); ?>"
                               autocomplete="off"
                               required>
                        <button type="submit" id="chat-ia-send" class="chat-ia-send" aria-label="<?php echo esc_attr__('Enviar', 'flavor-chat-ia'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" style="display:block;fill:#fff;">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </form>
                    <!-- Toggle para voz del asistente -->
                    <button type="button" id="chat-ia-tts-toggle" class="chat-ia-tts-toggle" aria-label="<?php echo esc_attr__('Activar/desactivar voz', 'flavor-chat-ia'); ?>" title="<?php echo esc_attr__('Leer respuestas en voz alta', 'flavor-chat-ia'); ?>">
                        <svg class="chat-ia-tts-on" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                        </svg>
                        <svg class="chat-ia-tts-off" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
                            <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
                        </svg>
                    </button>
                </div>

                <!-- Footer con powered by -->
                <div class="chat-ia-footer">
                    <small><?php esc_html_e('Powered by', 'flavor-chat-ia'); ?> <a href="https://flavor.dev" target="_blank" rel="noopener">Flavor Chat IA</a></small>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza las acciones rápidas
     *
     * @param array $settings
     */
    private function render_quick_actions($settings) {
        $quick_actions = $settings['quick_actions'] ?? [];
        $custom_actions = $settings['custom_quick_actions'] ?? [];

        $icons_map = [
            'cart' => '🛒',
            'package' => '📦',
            'truck' => '🚚',
            'refresh' => '🔄',
            'phone' => '📞',
            'question' => '❓',
            'star' => '⭐',
            'info' => 'ℹ️',
        ];

        $has_actions = false;
        foreach ($quick_actions as $action) {
            if (!empty($action['enabled'])) {
                $has_actions = true;
                break;
            }
        }
        if (!$has_actions && empty($custom_actions)) {
            return;
        }

        ?>
        <div id="chat-ia-quick-actions" class="chat-ia-quick-actions">
            <?php foreach ($quick_actions as $id => $action): ?>
                <?php if (!empty($action['enabled']) && !empty($action['label'])): ?>
                <button type="button" class="chat-ia-quick-action" data-prompt="<?php echo esc_attr($action['prompt'] ?? ''); ?>">
                    <span class="chat-ia-quick-icon"><?php echo $icons_map[$action['icon'] ?? 'info'] ?? 'ℹ️'; ?></span>
                    <span><?php echo esc_html($action['label']); ?></span>
                </button>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php foreach ($custom_actions as $action): ?>
                <?php if (!empty($action['label'])): ?>
                <button type="button" class="chat-ia-quick-action" data-prompt="<?php echo esc_attr($action['prompt'] ?? ''); ?>">
                    <span><?php echo esc_html($action['label']); ?></span>
                </button>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Verifica si el chat está habilitado
     *
     * @return bool
     */
    public static function is_enabled() {
        $settings = get_option('flavor_chat_ia_settings', []);

        if (empty($settings['enabled'])) {
            return false;
        }

        // Verificar si hay algún proveedor configurado
        $provider = $settings['active_provider'] ?? 'claude';
        $key_field = $provider . '_api_key';

        // Fallback a api_key legacy
        $has_key = !empty($settings[$key_field]) || !empty($settings['api_key']);

        return $has_key;
    }
}
