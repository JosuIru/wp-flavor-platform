<?php
/**
 * Integración con WPML
 *
 * Gestiona la traducción de contenido y detección de idiomas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase de integración WPML
 */
class Flavor_WPML_Integration {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Si WPML está activo
     */
    private $wpml_active = false;

    /**
     * Idioma actual
     */
    private $current_language = 'es';

    /**
     * Obtiene la instancia singleton
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
        $this->check_wpml();
        $this->init_hooks();
    }

    /**
     * Verifica si WPML está activo
     */
    private function check_wpml() {
        $this->wpml_active = defined('ICL_SITEPRESS_VERSION') || class_exists('SitePress');

        if ($this->wpml_active) {
            flavor_chat_ia_log('WPML detectado y activo', 'info');
        }
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        if (!$this->wpml_active) {
            return;
        }

        // Detectar cambio de idioma
        add_action('init', [$this, 'detect_language'], 20);

        // Registrar strings para traducción
        add_action('init', [$this, 'register_strings']);

        // Traducir respuestas del chat
        add_filter('flavor_chat_ia_message_content', [$this, 'translate_message'], 10, 2);

        // Traducir base de conocimiento
        add_filter('flavor_chat_ia_knowledge_base', [$this, 'translate_knowledge_base']);

        // Filtrar posts por idioma
        add_filter('flavor_chat_ia_query_args', [$this, 'filter_posts_by_language']);
    }

    /**
     * Detecta el idioma actual
     */
    public function detect_language() {
        if ($this->wpml_active && function_exists('icl_get_current_language')) {
            $this->current_language = icl_get_current_language();
        } else {
            $this->current_language = get_locale();
        }

        flavor_chat_ia_log('Idioma detectado: ' . $this->current_language, 'info');
    }

    /**
     * Obtiene el idioma actual
     */
    public function get_current_language() {
        return $this->current_language;
    }

    /**
     * Obtiene el código de idioma ISO (es, en, eu, ca, etc.)
     */
    public function get_language_code() {
        $lang = $this->current_language;

        // Mapeo de códigos WPML a ISO
        $map = [
            'es' => 'es',
            'en' => 'en',
            'eu' => 'eu',
            'ca' => 'ca',
            'fr' => 'fr',
            'de' => 'de',
            'it' => 'it',
            'pt' => 'pt',
        ];

        return $map[$lang] ?? 'es';
    }

    /**
     * Registra strings para traducción en WPML
     */
    public function register_strings() {
        if (!function_exists('icl_register_string')) {
            return;
        }

        // Strings del chat
        $strings = [
            'chat_title' => __('Asistente Virtual', 'flavor-chat-ia'),
            'chat_placeholder' => __('Escribe tu mensaje...', 'flavor-chat-ia'),
            'chat_send' => __('Enviar', 'flavor-chat-ia'),
            'chat_thinking' => __('Pensando...', 'flavor-chat-ia'),
            'chat_error' => __('Error al enviar mensaje', 'flavor-chat-ia'),
        ];

        foreach ($strings as $name => $value) {
            icl_register_string('flavor-chat-ia', $name, $value);
        }
    }

    /**
     * Traduce un mensaje
     */
    public function translate_message($content, $context = '') {
        if (!$this->wpml_active || !function_exists('icl_t')) {
            return $content;
        }

        // Si el contexto tiene un string name, traducir
        if (!empty($context)) {
            return icl_t('flavor-chat-ia', $context, $content);
        }

        return $content;
    }

    /**
     * Traduce la base de conocimiento
     */
    public function translate_knowledge_base($knowledge) {
        if (!$this->wpml_active) {
            return $knowledge;
        }

        $lang = $this->get_language_code();

        // Si hay traducciones específicas por idioma
        if (isset($knowledge['translations'][$lang])) {
            return array_merge($knowledge, $knowledge['translations'][$lang]);
        }

        return $knowledge;
    }

    /**
     * Filtra posts por idioma actual
     */
    public function filter_posts_by_language($args) {
        if (!$this->wpml_active) {
            return $args;
        }

        // Añadir filtro de idioma a la consulta
        $args['suppress_filters'] = false;

        return $args;
    }

    /**
     * Obtiene la traducción de un post
     */
    public function get_post_translation($post_id, $target_lang = null) {
        if (!$this->wpml_active) {
            return $post_id;
        }

        if ($target_lang === null) {
            $target_lang = $this->current_language;
        }

        if (function_exists('icl_object_id')) {
            $translated_id = icl_object_id($post_id, get_post_type($post_id), false, $target_lang);
            return $translated_id ?: $post_id;
        }

        return $post_id;
    }

    /**
     * Obtiene todos los idiomas disponibles
     */
    public function get_available_languages() {
        if (!$this->wpml_active) {
            return ['es' => 'Español'];
        }

        if (function_exists('icl_get_languages')) {
            $languages = icl_get_languages('skip_missing=0');
            $result = [];

            foreach ($languages as $lang) {
                $result[$lang['language_code']] = $lang['native_name'];
            }

            return $result;
        }

        return ['es' => 'Español'];
    }

    /**
     * Marca un post como traducible
     */
    public function register_post_type_for_translation($post_type) {
        if (!$this->wpml_active) {
            return;
        }

        if (function_exists('wpml_register_single_string')) {
            do_action('wpml_register_single_post_type', $post_type);
        }
    }

    /**
     * Genera system prompt multiidioma para el chat
     */
    public function get_multilingual_system_prompt($base_prompt) {
        $lang = $this->get_language_code();
        $lang_name = $this->get_language_name($lang);

        $multilingual_prompt = $base_prompt . "\n\n";
        $multilingual_prompt .= "IMPORTANTE: Debes responder SIEMPRE en {$lang_name}.\n";
        $multilingual_prompt .= "El usuario está navegando el sitio en idioma: {$lang_name} ({$lang})\n";

        if ($this->wpml_active) {
            $available = $this->get_available_languages();
            $multilingual_prompt .= "Idiomas disponibles en el sitio: " . implode(', ', $available) . "\n";
        }

        return $multilingual_prompt;
    }

    /**
     * Obtiene el nombre del idioma
     */
    private function get_language_name($code) {
        $names = [
            'es' => 'Español',
            'en' => 'English',
            'eu' => 'Euskara',
            'ca' => 'Català',
            'fr' => 'Français',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
        ];

        return $names[$code] ?? 'Español';
    }

    /**
     * Verifica si WPML está activo
     */
    public function is_wpml_active() {
        return $this->wpml_active;
    }
}
