<?php
/**
 * Gestión de assets (JS/CSS) del Chat IA
 *
 * @package CalendarioExperiencias
 * @subpackage ChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Evitar redeclaración si ya existe (ej: desde chat-ia-addon)
if (class_exists('Chat_IA_Assets')) {
    return;
}

class Chat_IA_Assets {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Chat_IA_Assets
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Flag para indicar si se debe cargar assets (shortcode presente)
     */
    private $force_load_assets = false;

    /**
     * Detecta si la petición actual pertenece al portal dinámico.
     */
    private function is_dynamic_portal_request() {
        if (is_admin()) {
            return false;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';

        return strpos($request_uri, '/mi-portal') !== false;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Detectar shortcode en el contenido para cargar assets
        add_filter('the_content', [$this, 'detect_shortcode_in_content'], 5);
        add_filter('widget_text', [$this, 'detect_shortcode_in_content'], 5);
    }

    /**
     * Detecta si hay shortcode en el contenido y fuerza carga de assets
     *
     * @param string $content
     * @return string
     */
    public function detect_shortcode_in_content($content) {
        // El portal dinámico renderiza su propio contenido y no debe volver a
        // inspeccionarlo con has_shortcode() sobre blobs HTML grandes.
        if ($this->is_dynamic_portal_request()) {
            return $content;
        }

        if (has_shortcode($content, 'chat_ia')) {
            $this->force_load_assets = true;
            // Encolar assets si aún no se han cargado
            if (!wp_script_is('chat-ia-widget', 'enqueued')) {
                $this->do_enqueue_assets();
            }
        }
        return $content;
    }

    /**
     * Fuerza la carga de assets (llamado desde el shortcode)
     */
    public function force_enqueue_assets() {
        $this->force_load_assets = true;

        // Si ya se cargaron los assets, no hacer nada
        if (wp_script_is('chat-ia-widget', 'enqueued')) {
            return;
        }

        // Intentar encolar normalmente
        $this->do_enqueue_assets();

        // Si el hook wp_head ya pasó, imprimir assets directamente en el footer
        if (did_action('wp_head') && !did_action('wp_footer')) {
            add_action('wp_footer', [$this, 'print_late_assets'], 5);
        }
    }

    /**
     * Imprime assets tardíamente si el shortcode se cargó después de wp_enqueue_scripts
     */
    public function print_late_assets() {
        // Solo si los assets no se imprimieron aún
        if (!wp_script_is('chat-ia-widget', 'done')) {
            wp_print_styles('chat-ia-widget');
            wp_print_scripts('chat-ia-widget');
        }
    }

    /**
     * Registra y encola assets del frontend
     */
    public function enqueue_frontend_assets() {
        $settings = get_option('chat_ia_settings', []);

        // Cargar si hay widget flotante habilitado o si se forzó (shortcode)
        if (empty($settings['show_floating_widget']) && !$this->force_load_assets) {
            return;
        }

        $this->do_enqueue_assets();
    }

    /**
     * Realiza el encolado de assets
     */
    private function do_enqueue_assets() {
        // Evitar cargar múltiples veces
        if (wp_script_is('chat-ia-widget', 'enqueued')) {
            return;
        }

        $settings = get_option('chat_ia_settings', []);

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // CSS
        wp_enqueue_style(
            'chat-ia-widget',
            CHAT_IA_ADDON_URL . "assets/css/chat-widget{$sufijo_asset}.css",
            [],
            CHAT_IA_ADDON_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'chat-ia-widget',
            CHAT_IA_ADDON_URL . "assets/js/chat-widget{$sufijo_asset}.js",
            ['jquery'],
            CHAT_IA_ADDON_VERSION,
            true
        );

        // Localización
        wp_localize_script('chat-ia-widget', 'chatIaConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chat_ia_nonce'),
            'language' => $this->detect_language(),
            'assistantName' => $settings['assistant_name'] ?? __('Asistente Virtual', 'chat-ia-addon'),
            'strings' => $this->get_frontend_strings(),
            'showFloating' => !empty($settings['show_floating_widget']),
            'streamingEnabled' => true,
        ]);
    }

    /**
     * Registra y encola assets del admin
     *
     * @param string $hook
     */
    public function enqueue_admin_assets($hook) {
        // Detectar si estamos en páginas del Calendario Experiencias
        $is_calendario_page = strpos($hook, 'calendario') !== false
            || strpos($hook, 'reservas') !== false
            || strpos($hook, 'chat-ia') !== false;

        // Si no es página del plugin, no cargar nada
        if (!$is_calendario_page) {
            return;
        }

        // Media uploader para el avatar
        wp_enqueue_media();

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // CSS Admin
        wp_enqueue_style(
            'chat-ia-admin',
            CHAT_IA_ADDON_URL . "assets/css/chat-admin{$sufijo_asset}.css",
            [],
            CHAT_IA_ADDON_VERSION
        );

        // JavaScript Admin
        wp_enqueue_script(
            'chat-ia-admin',
            CHAT_IA_ADDON_URL . "assets/js/chat-admin{$sufijo_asset}.js",
            ['jquery'],
            CHAT_IA_ADDON_VERSION,
            true
        );

        // Localización Admin
        wp_localize_script('chat-ia-admin', 'chatIaAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chat_ia_admin_nonce'),
            'strings' => $this->get_admin_strings(),
        ]);

        // Cargar burbuja flotante del admin (asistente contextual)
        $this->enqueue_admin_bubble_assets();
    }

    /**
     * Carga assets de la burbuja flotante del admin
     */
    private function enqueue_admin_bubble_assets() {
        $settings = get_option('chat_ia_settings', []);

        // Verificar si el chat está habilitado y configurado
        $is_configured = false;
        if (class_exists('Chat_IA_Engine_Manager')) {
            $engine_manager = Chat_IA_Engine_Manager::get_instance();
            $active_engine = $engine_manager->get_active_engine();
            $is_configured = $active_engine && $active_engine->is_configured();
        }

        // Solo cargar si hay motor de IA configurado
        if (!$is_configured) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // CSS Burbuja Admin
        wp_enqueue_style(
            'chat-ia-admin-bubble',
            CHAT_IA_ADDON_URL . "assets/css/admin-bubble{$sufijo_asset}.css",
            [],
            CHAT_IA_ADDON_VERSION
        );

        // JavaScript Burbuja Admin
        wp_enqueue_script(
            'chat-ia-admin-bubble',
            CHAT_IA_ADDON_URL . "assets/js/admin-bubble{$sufijo_asset}.js",
            ['jquery'],
            CHAT_IA_ADDON_VERSION,
            true
        );

        // Localización Burbuja Admin
        wp_localize_script('chat-ia-admin-bubble', 'chatIAAdminBubble', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chat_ia_admin_bubble_nonce'),
        ]);
    }

    /**
     * Detecta el idioma actual
     *
     * @return string
     */
    private function detect_language() {
        // Primero intentar con WPML
        if (defined('ICL_LANGUAGE_CODE')) {
            return ICL_LANGUAGE_CODE;
        }

        // Intentar con Polylang
        if (function_exists('pll_current_language')) {
            return pll_current_language();
        }

        // Usar el idioma de WordPress
        $locale = get_locale();
        $language = substr($locale, 0, 2);

        // Mapear a idiomas soportados
        $supported = ['es', 'eu', 'en', 'fr', 'ca'];
        if (in_array($language, $supported)) {
            return $language;
        }

        return 'es';
    }

    /**
     * Obtiene las cadenas de texto para el frontend
     *
     * @return array
     */
    private function get_frontend_strings() {
        return [
            'placeholder' => __('Escribe tu mensaje...', 'chat-ia-addon'),
            'send' => __('Enviar', 'chat-ia-addon'),
            'thinking' => __('Pensando...', 'chat-ia-addon'),
            'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'chat-ia-addon'),
            'connectionError' => __('Error de conexión. Comprueba tu conexión a internet.', 'chat-ia-addon'),
            'minimized' => __('Chat minimizado', 'chat-ia-addon'),
            'addToCart' => __('Añadir al carrito', 'chat-ia-addon'),
            'viewCart' => __('Ver carrito', 'chat-ia-addon'),
            'reservationReady' => __('¡Reserva lista!', 'chat-ia-addon'),
            'contactOptions' => __('Opciones de contacto', 'chat-ia-addon'),
            'close' => __('Cerrar', 'chat-ia-addon'),
            'open' => __('Abrir chat', 'chat-ia-addon'),
            'newConversation' => __('Nueva conversación', 'chat-ia-addon'),
            'messagesRemaining' => __('mensajes restantes', 'chat-ia-addon'),
            // Sugerencias inteligentes (Fase 5)
            'bookNow' => __('Reservar ahora', 'chat-ia-addon'),
            'viewDates' => __('Ver fechas', 'chat-ia-addon'),
            'paymentMethods' => __('Formas de pago', 'chat-ia-addon'),
            'location' => __('Cómo llegar', 'chat-ia-addon'),
            'contact' => __('Contactar', 'chat-ia-addon'),
            'checkAvailability' => __('Ver disponibilidad', 'chat-ia-addon'),
        ];
    }

    /**
     * Obtiene las cadenas de texto para el admin
     *
     * @return array
     */
    private function get_admin_strings() {
        return [
            'saving' => __('Guardando...', 'chat-ia-addon'),
            'saved' => __('Guardado', 'chat-ia-addon'),
            'error' => __('Error', 'chat-ia-addon'),
            'verifying' => __('Verificando...', 'chat-ia-addon'),
            'validKey' => __('API key válida', 'chat-ia-addon'),
            'invalidKey' => __('API key no válida', 'chat-ia-addon'),
            'confirmDelete' => __('¿Estás seguro de que deseas eliminar este elemento?', 'chat-ia-addon'),
            'loading' => __('Cargando...', 'chat-ia-addon'),
        ];
    }
}
