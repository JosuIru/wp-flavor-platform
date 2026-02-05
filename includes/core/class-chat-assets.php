<?php
/**
 * Gestión de assets (CSS/JS) del chat
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Assets {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Chat_Assets
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
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Encola los assets
     */
    public function enqueue_assets() {
        $settings = get_option('flavor_chat_ia_settings', []);

        if (empty($settings['enabled'])) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // CSS
        wp_enqueue_style(
            'flavor-chat-ia',
            FLAVOR_CHAT_IA_URL . "assets/css/chat-widget{$sufijo_asset}.css",
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // JS
        wp_enqueue_script(
            'flavor-chat-ia',
            FLAVOR_CHAT_IA_URL . "assets/js/chat-widget{$sufijo_asset}.js",
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        // Appearance settings
        $appearance = $settings['appearance'] ?? [];

        // Quick actions for JS
        $quick_actions_js = $this->get_quick_actions_for_js($settings);

        // Variables para JS
        wp_localize_script('flavor-chat-ia', 'flavorChatConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_chat_nonce'),
            'language' => $this->get_current_language(),
            'strings' => $this->get_translated_strings(),
            'appearance' => [
                'primaryColor' => $appearance['primary_color'] ?? '#0073aa',
                'headerBg' => $appearance['header_bg'] ?? '#1e3a5f',
                'userBubble' => $appearance['user_bubble'] ?? '#0073aa',
                'assistantBubble' => $appearance['assistant_bubble'] ?? '#f0f0f0',
                'welcomeMessage' => $appearance['welcome_message'] ?? __('¡Hola! ¿En qué puedo ayudarte?', 'flavor-chat-ia'),
                'placeholder' => $appearance['placeholder'] ?? __('Escribe tu mensaje...', 'flavor-chat-ia'),
            ],
            'quickActions' => $quick_actions_js,
            'streamingEnabled' => true,
        ]);
    }

    /**
     * Obtiene el idioma actual
     * Compatible con WPML, Polylang y locale de WordPress
     *
     * @return string
     */
    private function get_current_language() {
        $language = 'es';

        // WPML (prioridad alta)
        if (defined('ICL_LANGUAGE_CODE')) {
            $language = ICL_LANGUAGE_CODE;
        }
        // Polylang
        elseif (function_exists('pll_current_language')) {
            $language = pll_current_language('slug');
        }
        // WordPress locale
        else {
            $locale = get_locale();
            $language = substr($locale, 0, 2);
        }

        // Mapear a idiomas soportados
        $supported_languages = ['es', 'eu', 'en', 'fr', 'ca'];
        if (in_array($language, $supported_languages)) {
            return $language;
        }

        return 'es'; // Fallback a español
    }

    /**
     * Obtiene las traducciones para JS
     *
     * @return array
     */
    private function get_translated_strings() {
        return [
            'sending' => __('Enviando...', 'flavor-chat-ia'),
            'error' => __('Error al enviar mensaje', 'flavor-chat-ia'),
            'placeholder' => __('Escribe tu mensaje...', 'flavor-chat-ia'),
            'connecting' => __('Conectando...', 'flavor-chat-ia'),
            'typing' => __('Escribiendo...', 'flavor-chat-ia'),
        ];
    }

    /**
     * Obtiene las acciones rápidas para JS
     *
     * @param array $settings
     * @return array
     */
    private function get_quick_actions_for_js($settings) {
        $quick_actions = $settings['quick_actions'] ?? [];
        $custom_actions = $settings['custom_quick_actions'] ?? [];
        $result = [];

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

        foreach ($quick_actions as $id => $action) {
            if (!empty($action['enabled']) && !empty($action['label'])) {
                $result[] = [
                    'id' => $id,
                    'label' => $action['label'],
                    'prompt' => $action['prompt'] ?? '',
                    'icon' => $icons_map[$action['icon'] ?? 'info'] ?? 'ℹ️',
                ];
            }
        }

        foreach ($custom_actions as $index => $action) {
            if (!empty($action['label'])) {
                $result[] = [
                    'id' => 'custom_' . $index,
                    'label' => $action['label'],
                    'prompt' => $action['prompt'] ?? '',
                    'icon' => '',
                ];
            }
        }

        return $result;
    }
}
