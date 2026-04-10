<?php
/**
 * Caché de respuestas FAQ para optimizar tokens
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Platform_FAQ_Cache {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Prefijo de caché
     */
    const CACHE_PREFIX = 'flavor_chat_faq_';

    /**
     * TTL de caché (1 hora)
     */
    const CACHE_TTL = HOUR_IN_SECONDS;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Platform_FAQ_Cache
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
    private function __construct() {}

    /**
     * Busca una respuesta cacheada para una pregunta
     *
     * @param string $question
     * @param string $language
     * @return string|null
     */
    public function find_cached_response($question, $language = 'es') {
        // Normalizar pregunta
        $normalized = $this->normalize_question($question);

        // Buscar en FAQs configuradas
        $settings = flavor_get_main_settings();
        $faqs = $settings['faqs'] ?? [];

        foreach ($faqs as $faq) {
            $faq_normalized = $this->normalize_question($faq['pregunta'] ?? '');

            // Coincidencia exacta o muy similar
            if ($this->questions_match($normalized, $faq_normalized)) {
                return $faq['respuesta'] ?? null;
            }
        }

        // Buscar en caché de transients
        $cache_key = self::CACHE_PREFIX . md5($normalized . $language);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        return null;
    }

    /**
     * Guarda una respuesta en caché
     *
     * @param string $question
     * @param string $response
     * @param string $language
     */
    public function cache_response($question, $response, $language = 'es') {
        $normalized = $this->normalize_question($question);
        $cache_key = self::CACHE_PREFIX . md5($normalized . $language);

        set_transient($cache_key, $response, self::CACHE_TTL);
    }

    /**
     * Normaliza una pregunta para comparación
     *
     * @param string $question
     * @return string
     */
    private function normalize_question($question) {
        $normalized = mb_strtolower($question);

        // Quitar signos de puntuación
        $normalized = preg_replace('/[¿?¡!.,;:]/u', '', $normalized);

        // Quitar palabras comunes
        $stopwords = ['el', 'la', 'los', 'las', 'un', 'una', 'de', 'del', 'al', 'a', 'en', 'que', 'qué', 'cómo', 'como', 'cuál', 'cual', 'es', 'son', 'tengo', 'puedo', 'me', 'mi', 'su', 'sus', 'por', 'para'];
        $words = explode(' ', $normalized);
        $words = array_diff($words, $stopwords);
        $normalized = implode(' ', $words);

        // Quitar espacios extra
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));

        return $normalized;
    }

    /**
     * Compara si dos preguntas son similares
     *
     * @param string $q1
     * @param string $q2
     * @return bool
     */
    private function questions_match($q1, $q2) {
        if (empty($q1) || empty($q2)) {
            return false;
        }

        // Coincidencia exacta
        if ($q1 === $q2) {
            return true;
        }

        // Similitud con levenshtein (para preguntas cortas)
        if (strlen($q1) < 50 && strlen($q2) < 50) {
            $distance = levenshtein($q1, $q2);
            $max_length = max(strlen($q1), strlen($q2));

            if ($distance / $max_length < 0.3) { // 70% similitud
                return true;
            }
        }

        // Verificar si todas las palabras clave están presentes
        $words1 = explode(' ', $q1);
        $words2 = explode(' ', $q2);

        $common = array_intersect($words1, $words2);
        $similarity = count($common) / max(count($words1), count($words2));

        return $similarity >= 0.7;
    }

    /**
     * Limpia el caché de FAQs
     */
    public function clear_cache() {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_" . self::CACHE_PREFIX . "%'"
        );
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_" . self::CACHE_PREFIX . "%'"
        );
    }
}

if (!class_exists('Flavor_Chat_FAQ_Cache', false)) {
    class_alias('Flavor_Platform_FAQ_Cache', 'Flavor_Chat_FAQ_Cache');
}
