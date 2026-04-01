<?php
/**
 * Motor de traducción con IA - Optimizado
 *
 * Características:
 * - Integración con caché multinivel
 * - Memoria de traducción con fuzzy matching
 * - Inyección de glosario en prompts
 * - Rate limiting para evitar exceso de llamadas
 * - Fallback entre motores de IA
 * - Segmentación de contenido largo
 * - Métricas de uso y rendimiento
 * - Preservación de estructuras especiales (VBP, Gutenberg, shortcodes)
 *
 * @package FlavorMultilingual
 * @since 1.0.0
 * @updated 1.3.0
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
     * Motores de IA disponibles en orden de preferencia
     *
     * @var array
     */
    private $available_engines = array('claude', 'openai', 'deepseek', 'mistral');

    /**
     * Límite de caracteres por segmento
     *
     * @var int
     */
    private $segment_limit = 3000;

    /**
     * Límite de llamadas por minuto
     *
     * @var int
     */
    private $rate_limit = 30;

    /**
     * Umbral de similitud para memoria de traducción (0-1)
     *
     * @var float
     */
    private $tm_threshold = 0.85;

    /**
     * Métricas de la sesión actual
     *
     * @var array
     */
    private $metrics = array(
        'api_calls'     => 0,
        'tokens_used'   => 0,
        'cache_hits'    => 0,
        'tm_hits'       => 0,
        'time_saved_ms' => 0,
    );

    /**
     * Patrones a preservar durante la traducción
     *
     * @var array
     */
    private $preserve_patterns = array();

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
        $this->tm_threshold = (float) Flavor_Multilingual::get_option('tm_threshold', 0.85);

        // Registrar métricas al finalizar
        add_action('shutdown', array($this, 'save_metrics'));

        // Definir patrones a preservar
        $this->preserve_patterns = array(
            'shortcodes'  => '/\[[\w_-]+(?:\s+[^\]]+)?\](?:.*?\[\/[\w_-]+\])?/s',
            'gutenberg'   => '/<!-- wp:[^>]+-->.*?<!-- \/wp:[^>]+ -->/s',
            'vbp_blocks'  => '/"type"\s*:\s*"[^"]+"/s',
            'variables'   => '/\{\{[^}]+\}\}/',
            'php_tags'    => '/<\?(?:php)?.*?\?>/s',
        );
    }

    /**
     * Establece el motor de IA a usar
     *
     * @param string $engine Nombre del motor
     */
    public function set_engine($engine) {
        if (in_array($engine, $this->available_engines)) {
            $this->engine = $engine;
        }
    }

    /**
     * Traduce texto con optimizaciones completas
     *
     * @param string $text     Texto a traducir
     * @param string $from_lang Idioma origen (código)
     * @param string $to_lang   Idioma destino (código)
     * @param array  $options   Opciones adicionales
     * @return string|WP_Error Texto traducido o error
     */
    public function translate($text, $from_lang, $to_lang, $options = array()) {
        $defaults = array(
            'context'       => '',
            'is_html'       => false,
            'use_cache'     => true,
            'use_tm'        => true,
            'use_glossary'  => true,
            'field_type'    => 'content',
        );
        $options = wp_parse_args($options, $defaults);

        // Validaciones básicas
        if (empty($text)) {
            return '';
        }

        if ($from_lang === $to_lang) {
            return $text;
        }

        $start_time = microtime(true);

        // 1. Verificar caché
        if ($options['use_cache']) {
            $cached = $this->get_from_cache($text, $from_lang, $to_lang);
            if ($cached !== false) {
                $this->metrics['cache_hits']++;
                $this->metrics['time_saved_ms'] += (microtime(true) - $start_time) * 1000;
                return $cached;
            }
        }

        // 2. Buscar en memoria de traducción
        if ($options['use_tm'] && class_exists('Flavor_Translation_Memory')) {
            $tm_result = $this->search_translation_memory($text, $from_lang, $to_lang);
            if ($tm_result !== false) {
                $this->metrics['tm_hits']++;
                $this->metrics['time_saved_ms'] += (microtime(true) - $start_time) * 1000;

                // Guardar en caché para próximas consultas
                if ($options['use_cache']) {
                    $this->save_to_cache($text, $from_lang, $to_lang, $tm_result);
                }

                return $tm_result;
            }
        }

        // 3. Verificar rate limiting
        if (!$this->check_rate_limit()) {
            return new WP_Error(
                'rate_limit_exceeded',
                __('Límite de traducciones por minuto excedido. Intenta de nuevo en unos segundos.', 'flavor-multilingual')
            );
        }

        // 4. Preparar texto (preservar patrones especiales)
        $preserved = array();
        $prepared_text = $this->preserve_special_content($text, $preserved);

        // 5. Segmentar si es necesario
        if (strlen($prepared_text) > $this->segment_limit) {
            $translation = $this->translate_segmented($prepared_text, $from_lang, $to_lang, $options);
        } else {
            $translation = $this->translate_single($prepared_text, $from_lang, $to_lang, $options);
        }

        if (is_wp_error($translation)) {
            return $translation;
        }

        // 6. Restaurar contenido preservado
        $translation = $this->restore_special_content($translation, $preserved);

        // 7. Guardar en caché y TM
        if ($options['use_cache']) {
            $this->save_to_cache($text, $from_lang, $to_lang, $translation);
        }

        if ($options['use_tm'] && class_exists('Flavor_Translation_Memory')) {
            $this->save_to_translation_memory($text, $from_lang, $to_lang, $translation);
        }

        return $translation;
    }

    /**
     * Alias para compatibilidad
     */
    public function translate_text($text, $from_lang, $to_lang, $context = '') {
        return $this->translate($text, $from_lang, $to_lang, array(
            'context' => $context,
            'is_html' => false,
        ));
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
        return $this->translate($html, $from_lang, $to_lang, array(
            'context' => $context,
            'is_html' => true,
        ));
    }

    /**
     * Traduce un segmento individual
     *
     * @param string $text     Texto
     * @param string $from_lang Origen
     * @param string $to_lang   Destino
     * @param array  $options   Opciones
     * @return string|WP_Error
     */
    private function translate_single($text, $from_lang, $to_lang, $options) {
        // Obtener términos del glosario
        $glossary_terms = array();
        if ($options['use_glossary'] && class_exists('Flavor_Translation_Memory')) {
            $tm = Flavor_Translation_Memory::get_instance();
            $glossary_terms = $tm->get_glossary_for_text($text, $from_lang, $to_lang);
        }

        // Construir prompt optimizado
        $prompt = $this->build_optimized_prompt($text, $from_lang, $to_lang, $options, $glossary_terms);

        // Llamar a la IA con fallback
        $result = $this->call_ai_with_fallback($prompt);

        if (is_wp_error($result)) {
            return $result;
        }

        return $this->clean_response($result);
    }

    /**
     * Traduce contenido largo en segmentos
     *
     * @param string $text     Texto largo
     * @param string $from_lang Origen
     * @param string $to_lang   Destino
     * @param array  $options   Opciones
     * @return string|WP_Error
     */
    private function translate_segmented($text, $from_lang, $to_lang, $options) {
        // Segmentar por párrafos o puntos
        $segments = $this->segment_text($text);
        $translated_segments = array();

        foreach ($segments as $segment) {
            if (empty(trim($segment))) {
                $translated_segments[] = $segment;
                continue;
            }

            $translated = $this->translate_single($segment, $from_lang, $to_lang, $options);

            if (is_wp_error($translated)) {
                // Si falla un segmento, devolver el original
                $translated_segments[] = $segment;
            } else {
                $translated_segments[] = $translated;
            }
        }

        return implode("\n\n", $translated_segments);
    }

    /**
     * Segmenta texto largo de forma inteligente
     *
     * @param string $text Texto a segmentar
     * @return array
     */
    private function segment_text($text) {
        // Primero intentar por párrafos dobles
        $paragraphs = preg_split('/\n\s*\n/', $text);

        $segments = array();
        $current_segment = '';

        foreach ($paragraphs as $paragraph) {
            if (strlen($current_segment) + strlen($paragraph) < $this->segment_limit) {
                $current_segment .= ($current_segment ? "\n\n" : '') . $paragraph;
            } else {
                if ($current_segment) {
                    $segments[] = $current_segment;
                }
                $current_segment = $paragraph;
            }
        }

        if ($current_segment) {
            $segments[] = $current_segment;
        }

        return $segments;
    }

    /**
     * Construye prompt optimizado con glosario
     *
     * @param string $text           Texto
     * @param string $from           Origen
     * @param string $to             Destino
     * @param array  $options        Opciones
     * @param array  $glossary_terms Términos del glosario
     * @return string
     */
    private function build_optimized_prompt($text, $from, $to, $options, $glossary_terms = array()) {
        $from_name = $this->get_language_name($from);
        $to_name = $this->get_language_name($to);

        $prompt = "Eres un traductor profesional especializado en contenido web.\n\n";
        $prompt .= "TAREA: Traducir de {$from_name} a {$to_name}.\n\n";

        // Reglas base
        $prompt .= "REGLAS OBLIGATORIAS:\n";
        $prompt .= "1. Mantén el tono y estilo del original\n";
        $prompt .= "2. No traduzcas nombres propios ni marcas\n";
        $prompt .= "3. Adapta expresiones idiomáticas naturalmente\n";

        if ($options['is_html']) {
            $prompt .= "4. Mantén TODA la estructura HTML intacta\n";
            $prompt .= "5. Solo traduce texto visible, NO atributos\n";
            $prompt .= "6. Preserva shortcodes [ejemplo] sin modificar\n";
            $prompt .= "7. Preserva placeholders {{variable}} sin modificar\n";
        }

        // Inyectar glosario
        if (!empty($glossary_terms)) {
            $prompt .= "\nTERMINOLOGÍA OBLIGATORIA (usar estas traducciones exactas):\n";
            foreach ($glossary_terms as $term) {
                $prompt .= "- \"{$term['source']}\" → \"{$term['target']}\"\n";
            }
        }

        // Contexto
        if (!empty($options['context'])) {
            $prompt .= "\nCONTEXTO: {$options['context']}\n";
        }

        // Campo específico
        if (!empty($options['field_type'])) {
            $field_hints = array(
                'title'       => 'Es un título, debe ser conciso e impactante.',
                'excerpt'     => 'Es un resumen, mantén brevedad.',
                'meta_desc'   => 'Es meta descripción SEO, máximo 160 caracteres.',
                'button'      => 'Es texto de botón, debe ser corto y accionable.',
                'menu'        => 'Es elemento de menú, muy breve.',
            );
            if (isset($field_hints[$options['field_type']])) {
                $prompt .= "\nNOTA: {$field_hints[$options['field_type']]}\n";
            }
        }

        $prompt .= "\nTEXTO A TRADUCIR:\n---\n{$text}\n---\n\n";
        $prompt .= "Responde SOLO con la traducción, sin explicaciones.";

        return apply_filters('flavor_multilingual_ai_prompt', $prompt, $text, $from, $to, $glossary_terms);
    }

    /**
     * Llama a la IA con fallback entre motores
     *
     * @param string $prompt Prompt a enviar
     * @return string|WP_Error
     */
    private function call_ai_with_fallback($prompt) {
        // Orden de preferencia: motor configurado primero, luego los demás
        $engines_to_try = array_merge(
            array($this->engine),
            array_diff($this->available_engines, array($this->engine))
        );

        $last_error = null;

        foreach ($engines_to_try as $engine) {
            $result = $this->call_ai_engine($prompt, $engine);

            if (!is_wp_error($result)) {
                return $result;
            }

            $last_error = $result;

            // Log del fallo
            error_log(sprintf(
                '[Flavor ML] Motor %s falló: %s. Probando siguiente...',
                $engine,
                $result->get_error_message()
            ));
        }

        return $last_error ?: new WP_Error('all_engines_failed', __('Todos los motores de IA fallaron.', 'flavor-multilingual'));
    }

    /**
     * Llama a un motor de IA específico
     *
     * @param string $prompt Prompt
     * @param string $engine Motor a usar
     * @return string|WP_Error
     */
    private function call_ai_engine($prompt, $engine = null) {
        $engine = $engine ?: $this->engine;

        if (!class_exists('Flavor_Engine_Manager')) {
            return new WP_Error(
                'engine_not_found',
                __('El sistema de IA de Flavor no está disponible.', 'flavor-multilingual')
            );
        }

        try {
            $engine_manager = Flavor_Engine_Manager::get_instance();

            $start = microtime(true);

            $response = $engine_manager->send_message($prompt, array(
                'engine'      => $engine,
                'max_tokens'  => 4096,
                'temperature' => 0.2, // Muy baja para traducciones consistentes
            ));

            $elapsed = (microtime(true) - $start) * 1000;

            if (is_wp_error($response)) {
                return $response;
            }

            // Registrar métricas
            $this->metrics['api_calls']++;
            if (isset($response['usage']['total_tokens'])) {
                $this->metrics['tokens_used'] += $response['usage']['total_tokens'];
            }

            // Incrementar contador de rate limit
            $this->increment_rate_counter();

            return $response['content'] ?? '';

        } catch (Exception $e) {
            return new WP_Error('ai_error', $e->getMessage());
        }
    }

    // ================================================================
    // CACHÉ
    // ================================================================

    /**
     * Obtiene traducción del caché
     *
     * @param string $text      Texto original
     * @param string $from_lang Origen
     * @param string $to_lang   Destino
     * @return string|false
     */
    private function get_from_cache($text, $from_lang, $to_lang) {
        if (!class_exists('Flavor_Translation_Cache')) {
            return false;
        }

        $cache = Flavor_Translation_Cache::get_instance();
        $key = $this->build_cache_key($text, $from_lang, $to_lang);

        return $cache->get($key);
    }

    /**
     * Guarda traducción en caché
     *
     * @param string $text        Texto original
     * @param string $from_lang   Origen
     * @param string $to_lang     Destino
     * @param string $translation Traducción
     */
    private function save_to_cache($text, $from_lang, $to_lang, $translation) {
        if (!class_exists('Flavor_Translation_Cache')) {
            return;
        }

        $cache = Flavor_Translation_Cache::get_instance();
        $key = $this->build_cache_key($text, $from_lang, $to_lang);

        // TTL de 1 semana para traducciones
        $cache->set($key, $translation, WEEK_IN_SECONDS);
    }

    /**
     * Construye clave de caché
     *
     * @param string $text      Texto
     * @param string $from_lang Origen
     * @param string $to_lang   Destino
     * @return string
     */
    private function build_cache_key($text, $from_lang, $to_lang) {
        return 'ai_' . md5($text) . "_{$from_lang}_{$to_lang}";
    }

    // ================================================================
    // MEMORIA DE TRADUCCIÓN
    // ================================================================

    /**
     * Busca en la memoria de traducción
     *
     * @param string $text      Texto a buscar
     * @param string $from_lang Origen
     * @param string $to_lang   Destino
     * @return string|false
     */
    private function search_translation_memory($text, $from_lang, $to_lang) {
        $tm = Flavor_Translation_Memory::get_instance();

        // Buscar coincidencia exacta primero
        $exact = $tm->find_exact($text, $from_lang, $to_lang);
        if ($exact) {
            return $exact;
        }

        // Buscar coincidencia similar
        $similar = $tm->find_similar($text, $from_lang, $to_lang, $this->tm_threshold);
        if ($similar && $similar['similarity'] >= 0.95) {
            // Si es muy similar (95%+), usar directamente
            return $similar['translation'];
        }

        return false;
    }

    /**
     * Guarda en la memoria de traducción
     *
     * @param string $source      Texto original
     * @param string $from_lang   Origen
     * @param string $to_lang     Destino
     * @param string $translation Traducción
     */
    private function save_to_translation_memory($source, $from_lang, $to_lang, $translation) {
        $tm = Flavor_Translation_Memory::get_instance();
        $tm->add_entry($source, $translation, $from_lang, $to_lang, array(
            'source' => 'ai_translator',
            'engine' => $this->engine,
        ));
    }

    // ================================================================
    // PRESERVACIÓN DE CONTENIDO ESPECIAL
    // ================================================================

    /**
     * Preserva contenido especial antes de traducir
     *
     * @param string $text      Texto original
     * @param array  $preserved Array donde guardar contenido preservado
     * @return string Texto con placeholders
     */
    private function preserve_special_content($text, &$preserved) {
        $counter = 0;

        foreach ($this->preserve_patterns as $type => $pattern) {
            $text = preg_replace_callback($pattern, function($match) use (&$preserved, &$counter, $type) {
                $placeholder = "[[PRESERVE_{$counter}]]";
                $preserved[$placeholder] = array(
                    'type'    => $type,
                    'content' => $match[0],
                );
                $counter++;
                return $placeholder;
            }, $text);
        }

        return $text;
    }

    /**
     * Restaura contenido preservado después de traducir
     *
     * @param string $text      Texto traducido
     * @param array  $preserved Contenido preservado
     * @return string
     */
    private function restore_special_content($text, $preserved) {
        foreach ($preserved as $placeholder => $data) {
            $text = str_replace($placeholder, $data['content'], $text);
        }

        return $text;
    }

    // ================================================================
    // RATE LIMITING
    // ================================================================

    /**
     * Verifica si se puede hacer otra llamada
     *
     * @return bool
     */
    private function check_rate_limit() {
        $key = 'flavor_ml_rate_' . date('Y-m-d-H-i');
        $count = (int) get_transient($key);

        return $count < $this->rate_limit;
    }

    /**
     * Incrementa contador de rate limit
     */
    private function increment_rate_counter() {
        $key = 'flavor_ml_rate_' . date('Y-m-d-H-i');
        $count = (int) get_transient($key);
        set_transient($key, $count + 1, 120); // 2 minutos de vida
    }

    // ================================================================
    // TRADUCCIÓN DE POST COMPLETO
    // ================================================================

    /**
     * Traduce un post completo
     *
     * @param int    $post_id ID del post
     * @param string $to_lang Idioma destino
     * @param array  $options Opciones
     * @return array|WP_Error
     */
    public function translate_post($post_id, $to_lang, $options = array()) {
        $post = get_post($post_id);

        if (!$post) {
            return new WP_Error('post_not_found', __('Post no encontrado.', 'flavor-multilingual'));
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();

        $translations = array();

        // Traducir título
        if (!empty($post->post_title)) {
            $result = $this->translate($post->post_title, $from_lang, $to_lang, array(
                'context'    => 'Título de página/artículo',
                'field_type' => 'title',
            ));

            if (!is_wp_error($result)) {
                $translations['title'] = $result;
            }
        }

        // Traducir contenido
        if (!empty($post->post_content)) {
            $result = $this->translate($post->post_content, $from_lang, $to_lang, array(
                'context'    => 'Contenido principal',
                'is_html'    => true,
                'field_type' => 'content',
            ));

            if (!is_wp_error($result)) {
                $translations['content'] = $result;
            }
        }

        // Traducir excerpt
        if (!empty($post->post_excerpt)) {
            $result = $this->translate($post->post_excerpt, $from_lang, $to_lang, array(
                'context'    => 'Resumen del artículo',
                'field_type' => 'excerpt',
            ));

            if (!is_wp_error($result)) {
                $translations['excerpt'] = $result;
            }
        }

        // Meta descripción SEO
        $meta_desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true)
            ?: get_post_meta($post_id, 'rank_math_description', true)
            ?: get_post_meta($post_id, '_flavor_meta_description', true);

        if (!empty($meta_desc)) {
            $result = $this->translate($meta_desc, $from_lang, $to_lang, array(
                'context'    => 'Meta descripción SEO',
                'field_type' => 'meta_desc',
            ));

            if (!is_wp_error($result)) {
                $translations['meta_description'] = $result;
            }
        }

        do_action('flavor_multilingual_post_translated', $post_id, $to_lang, $translations);

        return $translations;
    }

    /**
     * Traduce múltiples textos en batch
     *
     * @param array  $texts    Array de textos
     * @param string $from_lang Origen
     * @param string $to_lang   Destino
     * @return array
     */
    public function translate_batch($texts, $from_lang, $to_lang) {
        if (empty($texts)) {
            return array();
        }

        $results = array();

        foreach ($texts as $key => $text) {
            $result = $this->translate($text, $from_lang, $to_lang);
            $results[$key] = is_wp_error($result) ? $text : $result;
        }

        return $results;
    }

    // ================================================================
    // MÉTRICAS
    // ================================================================

    /**
     * Guarda métricas de la sesión
     */
    public function save_metrics() {
        if ($this->metrics['api_calls'] === 0) {
            return;
        }

        // Acumular métricas diarias
        $today = date('Y-m-d');
        $daily_key = 'flavor_ml_metrics_' . $today;
        $daily = get_option($daily_key, array(
            'api_calls'     => 0,
            'tokens_used'   => 0,
            'cache_hits'    => 0,
            'tm_hits'       => 0,
            'time_saved_ms' => 0,
        ));

        foreach ($this->metrics as $key => $value) {
            $daily[$key] += $value;
        }

        update_option($daily_key, $daily, false);

        // Log si está en debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Flavor ML] Sesión: %d llamadas API, %d tokens, %d cache hits, %d TM hits, %dms ahorrados',
                $this->metrics['api_calls'],
                $this->metrics['tokens_used'],
                $this->metrics['cache_hits'],
                $this->metrics['tm_hits'],
                $this->metrics['time_saved_ms']
            ));
        }
    }

    /**
     * Obtiene métricas de un período
     *
     * @param int $days Días hacia atrás
     * @return array
     */
    public function get_metrics($days = 7) {
        $metrics = array(
            'api_calls'     => 0,
            'tokens_used'   => 0,
            'cache_hits'    => 0,
            'tm_hits'       => 0,
            'time_saved_ms' => 0,
            'daily'         => array(),
        );

        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $daily = get_option('flavor_ml_metrics_' . $date, array());

            if (!empty($daily)) {
                $metrics['daily'][$date] = $daily;

                foreach ($daily as $key => $value) {
                    if (isset($metrics[$key])) {
                        $metrics[$key] += $value;
                    }
                }
            }
        }

        // Calcular eficiencia
        $total_requests = $metrics['api_calls'] + $metrics['cache_hits'] + $metrics['tm_hits'];
        if ($total_requests > 0) {
            $metrics['cache_efficiency'] = round(($metrics['cache_hits'] / $total_requests) * 100, 1);
            $metrics['tm_efficiency'] = round(($metrics['tm_hits'] / $total_requests) * 100, 1);
            $metrics['api_percentage'] = round(($metrics['api_calls'] / $total_requests) * 100, 1);
        }

        return $metrics;
    }

    // ================================================================
    // UTILIDADES
    // ================================================================

    /**
     * Limpia la respuesta de la IA
     *
     * @param string $response Respuesta cruda
     * @return string
     */
    private function clean_response($response) {
        $response = trim($response);

        // Eliminar comillas envolventes
        if (preg_match('/^["\'](.+)["\']$/s', $response, $matches)) {
            $response = $matches[1];
        }

        // Eliminar prefijos comunes
        $prefixes = array(
            '/^(Traducci[oó]n|Translation|Here\'s the translation|Aquí está la traducción):\s*/i',
            '/^(Resultado|Result):\s*/i',
        );

        foreach ($prefixes as $pattern) {
            $response = preg_replace($pattern, '', $response);
        }

        return trim($response);
    }

    /**
     * Obtiene el nombre del idioma
     *
     * @param string $code Código
     * @return string
     */
    private function get_language_name($code) {
        $languages = Flavor_Multilingual::$default_languages;
        return $languages[$code]['name'] ?? $code;
    }

    /**
     * Obtiene los idiomas soportados
     *
     * @return array
     */
    public function get_supported_languages() {
        return array_keys(Flavor_Multilingual::$default_languages);
    }

    /**
     * Obtiene los motores disponibles
     *
     * @return array
     */
    public function get_available_engines() {
        return $this->available_engines;
    }

    /**
     * Obtiene el motor actual
     *
     * @return string
     */
    public function get_current_engine() {
        return $this->engine;
    }
}
