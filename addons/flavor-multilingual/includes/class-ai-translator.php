<?php
/**
 * Motor de traducción con IA
 *
 * Utiliza los motores de IA de Flavor Platform para traducir contenido.
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_AI_Translator {

    /**
     * Instancia singleton
     *
     * @var Flavor_AI_Translator|null
     */
    private static $instance = null;

    /**
     * Motor de IA activo
     *
     * @var string
     */
    private $engine = 'claude';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_AI_Translator
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->engine = Flavor_Multilingual::get_option('ai_engine', 'claude');
    }

    /**
     * Establece el motor de IA a usar
     *
     * @param string $engine Nombre del motor
     */
    public function set_engine($engine) {
        $valid = array('claude', 'openai', 'deepseek', 'mistral');
        if (in_array($engine, $valid)) {
            $this->engine = $engine;
        }
    }

    /**
     * Traduce texto plano
     *
     * @param string $text     Texto a traducir
     * @param string $from_lang Idioma origen (código)
     * @param string $to_lang   Idioma destino (código)
     * @param string $context   Contexto opcional para mejor traducción
     * @return string|WP_Error Texto traducido o error
     */
    public function translate_text($text, $from_lang, $to_lang, $context = '') {
        if (empty($text)) {
            return '';
        }

        if ($from_lang === $to_lang) {
            return $text;
        }

        do_action('flavor_multilingual_before_ai_translate', $text, $from_lang, $to_lang);

        $prompt = $this->build_translation_prompt($text, $from_lang, $to_lang, $context, false);
        $result = $this->call_ai_engine($prompt);

        if (is_wp_error($result)) {
            return $result;
        }

        $translation = $this->clean_response($result);

        do_action('flavor_multilingual_after_ai_translate', $text, $translation, $from_lang, $to_lang);

        return $translation;
    }

    /**
     * Traduce contenido HTML preservando estructura
     *
     * @param string $html      HTML a traducir
     * @param string $from_lang Idioma origen
     * @param string $to_lang   Idioma destino
     * @param string $context   Contexto opcional
     * @return string|WP_Error HTML traducido o error
     */
    public function translate_html($html, $from_lang, $to_lang, $context = '') {
        if (empty($html)) {
            return '';
        }

        if ($from_lang === $to_lang) {
            return $html;
        }

        $prompt = $this->build_translation_prompt($html, $from_lang, $to_lang, $context, true);
        $result = $this->call_ai_engine($prompt);

        if (is_wp_error($result)) {
            return $result;
        }

        return $this->clean_response($result);
    }

    /**
     * Traduce un post completo
     *
     * @param int    $post_id ID del post
     * @param string $to_lang Idioma destino
     * @return array|WP_Error Array con traducciones o error
     */
    public function translate_post($post_id, $to_lang) {
        $post = get_post($post_id);

        if (!$post) {
            return new WP_Error('post_not_found', __('Post no encontrado.', 'flavor-multilingual'));
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();

        $translations = array();

        // Traducir título
        if (!empty($post->post_title)) {
            $translated_title = $this->translate_text(
                $post->post_title,
                $from_lang,
                $to_lang,
                'Título de página/artículo'
            );

            if (!is_wp_error($translated_title)) {
                $translations['title'] = $translated_title;
            }
        }

        // Traducir contenido
        if (!empty($post->post_content)) {
            $translated_content = $this->translate_html(
                $post->post_content,
                $from_lang,
                $to_lang,
                'Contenido principal de la página'
            );

            if (!is_wp_error($translated_content)) {
                $translations['content'] = $translated_content;
            }
        }

        // Traducir excerpt
        if (!empty($post->post_excerpt)) {
            $translated_excerpt = $this->translate_text(
                $post->post_excerpt,
                $from_lang,
                $to_lang,
                'Resumen/extracto del artículo'
            );

            if (!is_wp_error($translated_excerpt)) {
                $translations['excerpt'] = $translated_excerpt;
            }
        }

        // Traducir meta SEO si existe
        $meta_description = get_post_meta($post_id, '_yoast_wpseo_metadesc', true)
            ?: get_post_meta($post_id, '_flavor_meta_description', true);

        if (!empty($meta_description)) {
            $translated_meta = $this->translate_text(
                $meta_description,
                $from_lang,
                $to_lang,
                'Meta descripción para SEO'
            );

            if (!is_wp_error($translated_meta)) {
                $translations['meta_description'] = $translated_meta;
            }
        }

        return $translations;
    }

    /**
     * Traduce múltiples textos en batch
     *
     * @param array  $texts    Array de textos a traducir
     * @param string $from_lang Idioma origen
     * @param string $to_lang   Idioma destino
     * @return array Array de traducciones (mismo orden)
     */
    public function translate_batch($texts, $from_lang, $to_lang) {
        if (empty($texts)) {
            return array();
        }

        // Para batches pequeños, traducir uno a uno
        if (count($texts) <= 3) {
            $results = array();
            foreach ($texts as $key => $text) {
                $result = $this->translate_text($text, $from_lang, $to_lang);
                $results[$key] = is_wp_error($result) ? $text : $result;
            }
            return $results;
        }

        // Para batches grandes, usar prompt combinado
        $combined_prompt = $this->build_batch_prompt($texts, $from_lang, $to_lang);
        $result = $this->call_ai_engine($combined_prompt);

        if (is_wp_error($result)) {
            // Fallback: devolver textos originales
            return $texts;
        }

        return $this->parse_batch_response($result, $texts);
    }

    /**
     * Construye el prompt de traducción
     *
     * @param string $text    Texto a traducir
     * @param string $from    Idioma origen
     * @param string $to      Idioma destino
     * @param string $context Contexto adicional
     * @param bool   $is_html Si el contenido es HTML
     * @return string
     */
    private function build_translation_prompt($text, $from, $to, $context = '', $is_html = false) {
        $from_name = $this->get_language_name($from);
        $to_name = $this->get_language_name($to);

        $prompt = "Eres un traductor profesional especializado en contenido web.\n\n";
        $prompt .= "TAREA: Traducir el siguiente contenido de {$from_name} a {$to_name}.\n\n";
        $prompt .= "REGLAS:\n";
        $prompt .= "1. Mantén el tono y estilo del original\n";
        $prompt .= "2. No traduzcas nombres propios, marcas o términos técnicos específicos\n";
        $prompt .= "3. Adapta expresiones idiomáticas al idioma destino\n";

        if ($is_html) {
            $prompt .= "4. Mantén TODA la estructura HTML intacta (etiquetas, atributos, clases)\n";
            $prompt .= "5. Solo traduce el texto visible, NO los atributos HTML\n";
            $prompt .= "6. Si hay shortcodes de WordPress [ejemplo], NO los modifiques\n";
        }

        if (!empty($context)) {
            $prompt .= "\nCONTEXTO: {$context}\n";
        }

        $prompt .= "\nCONTENIDO A TRADUCIR:\n";
        $prompt .= "---\n{$text}\n---\n\n";
        $prompt .= "Responde ÚNICAMENTE con la traducción, sin explicaciones ni comentarios adicionales.";

        return apply_filters('flavor_multilingual_ai_prompt', $prompt, $text, $from, $to);
    }

    /**
     * Construye prompt para traducción batch
     *
     * @param array  $texts Array de textos
     * @param string $from  Idioma origen
     * @param string $to    Idioma destino
     * @return string
     */
    private function build_batch_prompt($texts, $from, $to) {
        $from_name = $this->get_language_name($from);
        $to_name = $this->get_language_name($to);

        $prompt = "Eres un traductor profesional. Traduce los siguientes textos de {$from_name} a {$to_name}.\n\n";
        $prompt .= "FORMATO DE RESPUESTA: Devuelve las traducciones en el mismo orden, separadas por '|||'\n\n";
        $prompt .= "TEXTOS A TRADUCIR:\n";

        foreach ($texts as $i => $text) {
            $num = $i + 1;
            $prompt .= "{$num}. {$text}\n";
        }

        $prompt .= "\nResponde solo con las traducciones separadas por |||";

        return $prompt;
    }

    /**
     * Llama al motor de IA configurado
     *
     * @param string $prompt Prompt a enviar
     * @return string|WP_Error Respuesta o error
     */
    private function call_ai_engine($prompt) {
        // Verificar que existe el Engine Manager de Flavor
        if (!class_exists('Flavor_Engine_Manager')) {
            return new WP_Error(
                'engine_not_found',
                __('El sistema de IA de Flavor no está disponible.', 'flavor-multilingual')
            );
        }

        try {
            $engine_manager = Flavor_Engine_Manager::get_instance();
            $response = $engine_manager->send_message($prompt, array(
                'engine'      => $this->engine,
                'max_tokens'  => 4096,
                'temperature' => 0.3, // Baja temperatura para traducciones consistentes
            ));

            if (is_wp_error($response)) {
                return $response;
            }

            return $response['content'] ?? '';

        } catch (Exception $e) {
            return new WP_Error('ai_error', $e->getMessage());
        }
    }

    /**
     * Limpia la respuesta de la IA
     *
     * @param string $response Respuesta cruda
     * @return string
     */
    private function clean_response($response) {
        // Eliminar posibles prefijos/sufijos de la IA
        $response = trim($response);

        // Eliminar comillas envolventes si las hay
        if (preg_match('/^["\'](.+)["\']$/s', $response, $matches)) {
            $response = $matches[1];
        }

        // Eliminar "Traducción:" o similar al inicio
        $response = preg_replace('/^(Traducci[oó]n|Translation|Here\'s the translation):\s*/i', '', $response);

        return $response;
    }

    /**
     * Parsea respuesta de batch
     *
     * @param string $response Respuesta de la IA
     * @param array  $original Textos originales
     * @return array
     */
    private function parse_batch_response($response, $original) {
        $parts = explode('|||', $response);
        $results = array();

        foreach ($original as $key => $text) {
            if (isset($parts[$key])) {
                $results[$key] = trim($parts[$key]);
            } else {
                $results[$key] = $text; // Fallback al original
            }
        }

        return $results;
    }

    /**
     * Obtiene el nombre del idioma
     *
     * @param string $code Código de idioma
     * @return string
     */
    private function get_language_name($code) {
        $languages = Flavor_Multilingual::$default_languages;
        return $languages[$code]['name'] ?? $code;
    }

    /**
     * Obtiene los idiomas soportados para traducción
     *
     * @return array
     */
    public function get_supported_languages() {
        return array_keys(Flavor_Multilingual::$default_languages);
    }
}
