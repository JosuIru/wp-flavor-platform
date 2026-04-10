<?php
/**
 * Traductor de Contenido con IA
 *
 * Traduce contenido entre idiomas:
 * - Español, Euskera, Catalán, Gallego, Inglés
 * - Posts, páginas, eventos, productos
 * - Mantiene formato y estructura
 *
 * @package FlavorPlatform
 * @since 3.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Content_Translator {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Motor de IA activo
     */
    private $engine = null;

    /**
     * Idiomas soportados
     */
    private $supported_languages = [
        'es' => 'Español',
        'eu' => 'Euskera',
        'ca' => 'Catalán',
        'gl' => 'Gallego',
        'en' => 'English',
        'fr' => 'Français',
        'pt' => 'Português',
    ];

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
     * Constructor
     */
    private function __construct() {
        add_action('wp_ajax_flavor_translate_content', [$this, 'ajax_translate']);
        add_action('wp_ajax_flavor_translate_field', [$this, 'ajax_translate_field']);
        add_action('wp_ajax_flavor_detect_language', [$this, 'ajax_detect_language']);
    }

    /**
     * Obtiene el motor de IA
     */
    private function get_engine() {
        if ($this->engine === null && class_exists('Flavor_Engine_Manager')) {
            $manager = Flavor_Engine_Manager::get_instance();
            $this->engine = $manager->get_active_engine();
        }
        return $this->engine;
    }

    /**
     * Verifica si el traductor está disponible
     */
    public function is_available() {
        $engine = $this->get_engine();
        return $engine && $engine->is_configured();
    }

    /**
     * Obtiene idiomas soportados
     */
    public function get_supported_languages() {
        return $this->supported_languages;
    }

    /**
     * Handler AJAX para traducir contenido completo
     */
    public function ajax_translate() {
        check_ajax_referer('flavor_translate', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['error' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $content = wp_kses_post($_POST['content'] ?? '');
        $source_lang = sanitize_text_field($_POST['source_lang'] ?? 'es');
        $target_lang = sanitize_text_field($_POST['target_lang'] ?? 'en');
        $preserve_html = isset($_POST['preserve_html']) ? (bool) $_POST['preserve_html'] : true;

        $result = $this->translate($content, $source_lang, $target_lang, $preserve_html);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Handler AJAX para traducir un campo específico
     */
    public function ajax_translate_field() {
        check_ajax_referer('flavor_translate', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['error' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $field_name = sanitize_text_field($_POST['field_name'] ?? '');
        $target_lang = sanitize_text_field($_POST['target_lang'] ?? 'en');

        if (!$post_id || !$field_name) {
            wp_send_json_error(['error' => __('Parámetros inválidos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $content = get_post_meta($post_id, $field_name, true);
        if (empty($content)) {
            $post = get_post($post_id);
            if ($field_name === 'post_title') {
                $content = $post->post_title;
            } elseif ($field_name === 'post_content') {
                $content = $post->post_content;
            } elseif ($field_name === 'post_excerpt') {
                $content = $post->post_excerpt;
            }
        }

        if (empty($content)) {
            wp_send_json_error(['error' => __('Campo vacío', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $result = $this->translate($content, 'auto', $target_lang, true);

        if ($result['success']) {
            // Guardar traducción como meta
            $meta_key = "_{$field_name}_{$target_lang}";
            update_post_meta($post_id, $meta_key, $result['translated']);
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Handler AJAX para detectar idioma
     */
    public function ajax_detect_language() {
        check_ajax_referer('flavor_translate', 'nonce');

        $text = sanitize_textarea_field($_POST['text'] ?? '');

        if (empty($text)) {
            wp_send_json_error(['error' => __('Texto vacío', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $detected = $this->detect_language($text);
        wp_send_json_success(['language' => $detected]);
    }

    /**
     * Traduce contenido
     *
     * @param string $content Contenido a traducir
     * @param string $source_lang Idioma origen (o 'auto')
     * @param string $target_lang Idioma destino
     * @param bool $preserve_html Preservar HTML
     * @return array
     */
    public function translate($content, $source_lang, $target_lang, $preserve_html = true) {
        if (!$this->is_available()) {
            return [
                'success' => false,
                'error' => __('El motor de IA no está disponible', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        if (empty(trim($content))) {
            return [
                'success' => false,
                'error' => __('Contenido vacío', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Validar idioma destino
        if (!isset($this->supported_languages[$target_lang])) {
            return [
                'success' => false,
                'error' => __('Idioma no soportado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Auto-detectar idioma origen si es necesario
        if ($source_lang === 'auto') {
            $source_lang = $this->detect_language($content);
        }

        // No traducir si origen = destino
        if ($source_lang === $target_lang) {
            return [
                'success' => true,
                'translated' => $content,
                'source_lang' => $source_lang,
                'target_lang' => $target_lang,
                'unchanged' => true,
            ];
        }

        // Cache key
        $cache_key = 'flavor_trans_' . md5($content . $source_lang . $target_lang);
        $cached_translation = get_transient($cache_key);
        if ($cached_translation !== false) {
            return [
                'success' => true,
                'translated' => $cached_translation,
                'source_lang' => $source_lang,
                'target_lang' => $target_lang,
                'cached' => true,
            ];
        }

        // Preparar contenido para traducción
        $placeholders = [];
        $prepared_content = $content;

        if ($preserve_html) {
            // Extraer y preservar etiquetas HTML
            $prepared_content = $this->extract_html_placeholders($content, $placeholders);
        }

        // Construir prompt
        $source_name = $this->supported_languages[$source_lang] ?? $source_lang;
        $target_name = $this->supported_languages[$target_lang] ?? $target_lang;

        $system_prompt = $this->build_translation_system_prompt($target_lang);
        $user_prompt = $this->build_translation_prompt($prepared_content, $source_name, $target_name);

        try {
            $response = $this->get_engine()->send_message(
                [['role' => 'user', 'content' => $user_prompt]],
                $system_prompt,
                []
            );

            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? __('Error en la traducción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }

            $translated = $response['response'];

            // Restaurar placeholders HTML
            if ($preserve_html && !empty($placeholders)) {
                $translated = $this->restore_html_placeholders($translated, $placeholders);
            }

            // Cache por 24 horas
            set_transient($cache_key, $translated, DAY_IN_SECONDS);

            return [
                'success' => true,
                'translated' => $translated,
                'source_lang' => $source_lang,
                'target_lang' => $target_lang,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Detecta el idioma del texto
     */
    public function detect_language($text) {
        // Detección básica por patrones
        $text_lower = mb_strtolower($text);

        // Euskera - palabras características
        $euskera_patterns = ['eta', 'da', 'dira', 'dut', 'duzu', 'nahi', 'egon', 'egin', 'zer', 'nola'];
        $euskera_matches = 0;
        foreach ($euskera_patterns as $pattern) {
            if (preg_match('/\b' . $pattern . '\b/', $text_lower)) {
                $euskera_matches++;
            }
        }
        if ($euskera_matches >= 3) {
            return 'eu';
        }

        // Catalán - palabras características
        $catalan_patterns = ['i', 'el', 'la', 'amb', 'que', 'és', 'per', 'com', 'més', 'però'];
        $catalan_matches = 0;
        foreach ($catalan_patterns as $pattern) {
            if (preg_match('/\b' . $pattern . '\b/', $text_lower)) {
                $catalan_matches++;
            }
        }
        // Ç es muy característica del catalán
        if (strpos($text_lower, 'ç') !== false) {
            $catalan_matches += 2;
        }
        if ($catalan_matches >= 4) {
            return 'ca';
        }

        // Gallego - palabras características
        $gallego_patterns = ['e', 'ou', 'non', 'máis', 'que', 'con', 'para', 'como', 'pero', 'ten'];
        $gallego_matches = 0;
        foreach ($gallego_patterns as $pattern) {
            if (preg_match('/\b' . $pattern . '\b/', $text_lower)) {
                $gallego_matches++;
            }
        }
        if ($gallego_matches >= 4) {
            return 'gl';
        }

        // Inglés - palabras características
        $english_patterns = ['the', 'and', 'is', 'are', 'to', 'in', 'for', 'of', 'with', 'that'];
        $english_matches = 0;
        foreach ($english_patterns as $pattern) {
            if (preg_match('/\b' . $pattern . '\b/', $text_lower)) {
                $english_matches++;
            }
        }
        if ($english_matches >= 4) {
            return 'en';
        }

        // Por defecto español
        return 'es';
    }

    /**
     * Extrae HTML y lo reemplaza con placeholders
     */
    private function extract_html_placeholders($content, &$placeholders) {
        $placeholder_index = 0;

        // Preservar etiquetas completas
        $content = preg_replace_callback(
            '/<[^>]+>/',
            function($matches) use (&$placeholders, &$placeholder_index) {
                $placeholder = "[[HTML_{$placeholder_index}]]";
                $placeholders[$placeholder] = $matches[0];
                $placeholder_index++;
                return $placeholder;
            },
            $content
        );

        return $content;
    }

    /**
     * Restaura placeholders HTML
     */
    private function restore_html_placeholders($content, $placeholders) {
        foreach ($placeholders as $placeholder => $html) {
            $content = str_replace($placeholder, $html, $content);
        }
        return $content;
    }

    /**
     * Construye system prompt para traducción
     */
    private function build_translation_system_prompt($target_lang) {
        $prompts = [
            'eu' => "Eres un traductor experto en euskera batua. Traduce de forma natural y correcta, respetando la gramática euskaldun. Mantén el registro y tono del original.",
            'ca' => "Eres un traductor experto en catalán estándar. Traduce de forma natural y correcta, respetando la gramática catalana. Mantén el registro y tono del original.",
            'gl' => "Eres un traductor experto en gallego normativo. Traduce de forma natural y correcta, respetando la gramática gallega. Mantén el registro y tono del original.",
            'en' => "You are an expert translator into English. Translate naturally and accurately, maintaining the register and tone of the original.",
            'fr' => "Vous êtes un traducteur expert en français. Traduisez de manière naturelle et précise, en respectant le registre et le ton de l'original.",
            'pt' => "Você é um tradutor especialista em português. Traduza de forma natural e precisa, mantendo o registro e o tom do original.",
            'es' => "Eres un traductor experto en español. Traduce de forma natural y correcta, respetando el registro y tono del original.",
        ];

        $base_prompt = $prompts[$target_lang] ?? $prompts['es'];
        $base_prompt .= "\n\nInstrucciones importantes:";
        $base_prompt .= "\n- Mantén los placeholders [[HTML_X]] intactos";
        $base_prompt .= "\n- No traduzcas nombres propios de personas o lugares";
        $base_prompt .= "\n- Mantén URLs y emails sin modificar";
        $base_prompt .= "\n- Responde SOLO con la traducción, sin explicaciones";

        return $base_prompt;
    }

    /**
     * Construye prompt de usuario para traducción
     */
    private function build_translation_prompt($content, $source_name, $target_name) {
        return "Traduce el siguiente texto de {$source_name} a {$target_name}:\n\n{$content}";
    }

    /**
     * Traduce un post completo
     */
    public function translate_post($post_id, $target_lang) {
        $post = get_post($post_id);
        if (!$post) {
            return ['success' => false, 'error' => __('Post no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $results = [];

        // Traducir título
        if (!empty($post->post_title)) {
            $title_result = $this->translate($post->post_title, 'auto', $target_lang, false);
            if ($title_result['success']) {
                update_post_meta($post_id, "_post_title_{$target_lang}", $title_result['translated']);
                $results['title'] = $title_result['translated'];
            }
        }

        // Traducir contenido
        if (!empty($post->post_content)) {
            $content_result = $this->translate($post->post_content, 'auto', $target_lang, true);
            if ($content_result['success']) {
                update_post_meta($post_id, "_post_content_{$target_lang}", $content_result['translated']);
                $results['content'] = $content_result['translated'];
            }
        }

        // Traducir excerpt
        if (!empty($post->post_excerpt)) {
            $excerpt_result = $this->translate($post->post_excerpt, 'auto', $target_lang, false);
            if ($excerpt_result['success']) {
                update_post_meta($post_id, "_post_excerpt_{$target_lang}", $excerpt_result['translated']);
                $results['excerpt'] = $excerpt_result['translated'];
            }
        }

        return [
            'success' => true,
            'translations' => $results,
            'target_lang' => $target_lang,
        ];
    }

    /**
     * Traduce múltiples textos en batch
     */
    public function translate_batch($texts, $source_lang, $target_lang) {
        $results = [];

        foreach ($texts as $key => $text) {
            $result = $this->translate($text, $source_lang, $target_lang, false);
            $results[$key] = $result['success'] ? $result['translated'] : $text;
        }

        return [
            'success' => true,
            'translations' => $results,
        ];
    }
}

// Inicializar
add_action('init', function() {
    Flavor_Content_Translator::get_instance();
});
