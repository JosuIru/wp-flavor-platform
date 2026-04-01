<?php
/**
 * Sistema de caché para traducciones
 *
 * Implementa caché multinivel para mejorar el rendimiento:
 * - Nivel 1: Caché estática en memoria (por request)
 * - Nivel 2: Object cache de WordPress (Redis/Memcached si disponible)
 * - Nivel 3: Transients de WordPress (fallback a BD)
 *
 * @package FlavorMultilingual
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Translation_Cache {

    /**
     * Instancia singleton
     *
     * @var Flavor_Translation_Cache|null
     */
    private static $instance = null;

    /**
     * Caché en memoria (nivel 1)
     *
     * @var array
     */
    private $memory_cache = array();

    /**
     * Prefijo para claves de caché
     *
     * @var string
     */
    private $cache_prefix = 'flavor_ml_';

    /**
     * Grupo de caché para object cache
     *
     * @var string
     */
    private $cache_group = 'flavor_multilingual';

    /**
     * TTL por defecto para transients (1 hora)
     *
     * @var int
     */
    private $default_ttl = 3600;

    /**
     * Estadísticas de caché
     *
     * @var array
     */
    private $stats = array(
        'hits'   => 0,
        'misses' => 0,
        'writes' => 0,
    );

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Translation_Cache
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
        // Registrar hook de limpieza al finalizar el request
        add_action('shutdown', array($this, 'maybe_log_stats'));

        // Hooks para invalidar caché
        add_action('flavor_multilingual_translation_saved', array($this, 'invalidate_translation'), 10, 4);
        add_action('flavor_multilingual_translation_deleted', array($this, 'invalidate_translation'), 10, 4);
        add_action('flavor_multilingual_language_changed', array($this, 'flush_all'));

        // Warmup de caché para contenido popular
        add_action('wp', array($this, 'maybe_warmup_cache'));
    }

    // ================================================================
    // OPERACIONES BÁSICAS DE CACHÉ
    // ================================================================

    /**
     * Obtiene un valor del caché
     *
     * @param string $key Clave
     * @return mixed|false Valor o false si no existe
     */
    public function get($key) {
        $full_key = $this->get_full_key($key);

        // Nivel 1: Memoria
        if (isset($this->memory_cache[$full_key])) {
            $this->stats['hits']++;
            return $this->memory_cache[$full_key];
        }

        // Nivel 2: Object Cache
        if ($this->has_object_cache()) {
            $value = wp_cache_get($full_key, $this->cache_group);
            if ($value !== false) {
                $this->memory_cache[$full_key] = $value;
                $this->stats['hits']++;
                return $value;
            }
        }

        // Nivel 3: Transients
        $value = get_transient($full_key);
        if ($value !== false) {
            $this->memory_cache[$full_key] = $value;
            if ($this->has_object_cache()) {
                wp_cache_set($full_key, $value, $this->cache_group, $this->default_ttl);
            }
            $this->stats['hits']++;
            return $value;
        }

        $this->stats['misses']++;
        return false;
    }

    /**
     * Guarda un valor en el caché
     *
     * @param string $key   Clave
     * @param mixed  $value Valor
     * @param int    $ttl   Tiempo de vida en segundos (opcional)
     * @return bool
     */
    public function set($key, $value, $ttl = null) {
        $full_key = $this->get_full_key($key);
        $ttl = $ttl ?? $this->default_ttl;

        // Nivel 1: Memoria
        $this->memory_cache[$full_key] = $value;

        // Nivel 2: Object Cache
        if ($this->has_object_cache()) {
            wp_cache_set($full_key, $value, $this->cache_group, $ttl);
        }

        // Nivel 3: Transients
        set_transient($full_key, $value, $ttl);

        $this->stats['writes']++;

        return true;
    }

    /**
     * Elimina un valor del caché
     *
     * @param string $key Clave
     * @return bool
     */
    public function delete($key) {
        $full_key = $this->get_full_key($key);

        // Nivel 1: Memoria
        unset($this->memory_cache[$full_key]);

        // Nivel 2: Object Cache
        if ($this->has_object_cache()) {
            wp_cache_delete($full_key, $this->cache_group);
        }

        // Nivel 3: Transients
        delete_transient($full_key);

        return true;
    }

    /**
     * Verifica si existe una clave en caché
     *
     * @param string $key Clave
     * @return bool
     */
    public function exists($key) {
        return $this->get($key) !== false;
    }

    // ================================================================
    // OPERACIONES DE TRADUCCIONES
    // ================================================================

    /**
     * Obtiene una traducción del caché
     *
     * @param string $type      Tipo de objeto
     * @param int    $object_id ID del objeto
     * @param string $lang      Código de idioma
     * @param string $field     Campo
     * @return string|false
     */
    public function get_translation($type, $object_id, $lang, $field) {
        $key = $this->build_translation_key($type, $object_id, $lang, $field);
        return $this->get($key);
    }

    /**
     * Guarda una traducción en caché
     *
     * @param string $type      Tipo de objeto
     * @param int    $object_id ID del objeto
     * @param string $lang      Código de idioma
     * @param string $field     Campo
     * @param string $value     Valor traducido
     * @param int    $ttl       TTL opcional
     * @return bool
     */
    public function set_translation($type, $object_id, $lang, $field, $value, $ttl = null) {
        $key = $this->build_translation_key($type, $object_id, $lang, $field);
        return $this->set($key, $value, $ttl);
    }

    /**
     * Obtiene todas las traducciones de un objeto del caché
     *
     * @param string $type      Tipo de objeto
     * @param int    $object_id ID del objeto
     * @return array|false
     */
    public function get_all_translations($type, $object_id) {
        $key = "all_{$type}_{$object_id}";
        return $this->get($key);
    }

    /**
     * Guarda todas las traducciones de un objeto en caché
     *
     * @param string $type         Tipo de objeto
     * @param int    $object_id    ID del objeto
     * @param array  $translations Traducciones
     * @param int    $ttl          TTL opcional
     * @return bool
     */
    public function set_all_translations($type, $object_id, $translations, $ttl = null) {
        $key = "all_{$type}_{$object_id}";
        return $this->set($key, $translations, $ttl);
    }

    /**
     * Invalida el caché de una traducción
     *
     * @param string $type      Tipo de objeto
     * @param int    $object_id ID del objeto
     * @param string $lang      Código de idioma
     * @param string $field     Campo (opcional, si no se pasa invalida todos los campos)
     */
    public function invalidate_translation($type, $object_id, $lang, $field = null) {
        if ($field) {
            $key = $this->build_translation_key($type, $object_id, $lang, $field);
            $this->delete($key);
        }

        // Siempre invalidar el caché de todas las traducciones
        $this->delete("all_{$type}_{$object_id}");

        // Invalidar caché de idiomas si es contenido público
        if (in_array($type, array('post', 'page', 'product'))) {
            $this->delete("content_{$object_id}");
        }

        // Disparar acción para que otros sistemas invaliden su caché
        do_action('flavor_ml_cache_invalidated', $type, $object_id, $lang, $field);
    }

    // ================================================================
    // CACHÉ DE STRINGS
    // ================================================================

    /**
     * Obtiene una traducción de string del caché
     *
     * @param string $original String original
     * @param string $lang     Código de idioma
     * @param string $domain   Dominio (opcional)
     * @return string|false
     */
    public function get_string($original, $lang, $domain = 'default') {
        $key = $this->build_string_key($original, $lang, $domain);
        return $this->get($key);
    }

    /**
     * Guarda una traducción de string en caché
     *
     * @param string $original    String original
     * @param string $lang        Código de idioma
     * @param string $translation Traducción
     * @param string $domain      Dominio (opcional)
     * @param int    $ttl         TTL opcional
     * @return bool
     */
    public function set_string($original, $lang, $translation, $domain = 'default', $ttl = null) {
        $key = $this->build_string_key($original, $lang, $domain);
        return $this->set($key, $translation, $ttl);
    }

    // ================================================================
    // WARMUP Y PRELOAD
    // ================================================================

    /**
     * Precarga traducciones en caché si es necesario
     */
    public function maybe_warmup_cache() {
        // Solo en frontend y si no está en caché ya
        if (is_admin() || $this->get('warmup_done_' . date('Y-m-d')) !== false) {
            return;
        }

        // Marcar como hecho para hoy
        $this->set('warmup_done_' . date('Y-m-d'), true, DAY_IN_SECONDS);

        // Programar warmup en background
        if (!wp_next_scheduled('flavor_ml_cache_warmup')) {
            wp_schedule_single_event(time() + 5, 'flavor_ml_cache_warmup');
        }
    }

    /**
     * Ejecuta el warmup de caché
     */
    public function do_warmup() {
        global $wpdb;

        $core = Flavor_Multilingual_Core::get_instance();
        $default_lang = $core->get_default_language();

        // Precargar traducciones de los 50 posts más visitados/recientes
        $posts = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_status = 'publish'
            AND post_type IN ('post', 'page')
            ORDER BY post_modified DESC
            LIMIT 50
        ");

        $storage = Flavor_Translation_Storage::get_instance();

        foreach ($posts as $post_id) {
            $translations = $storage->get_all_translations('post', $post_id);
            $this->set_all_translations('post', $post_id, $translations, HOUR_IN_SECONDS * 6);
        }

        // Precargar idiomas activos
        $languages = $core->get_active_languages();
        $this->set('active_languages', $languages, DAY_IN_SECONDS);
    }

    /**
     * Precarga traducciones para una página específica
     *
     * @param int $post_id ID del post
     */
    public function preload_for_post($post_id) {
        if ($this->get_all_translations('post', $post_id) !== false) {
            return; // Ya está en caché
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $translations = $storage->get_all_translations('post', $post_id);
        $this->set_all_translations('post', $post_id, $translations);
    }

    // ================================================================
    // LIMPIEZA
    // ================================================================

    /**
     * Limpia todo el caché
     */
    public function flush_all() {
        // Nivel 1: Memoria
        $this->memory_cache = array();

        // Nivel 2: Object Cache - limpiar grupo
        if ($this->has_object_cache()) {
            wp_cache_flush_group($this->cache_group);
        }

        // Nivel 3: Transients - limpiar por prefijo
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_' . $this->cache_prefix . '%',
            '_transient_timeout_' . $this->cache_prefix . '%'
        ));

        do_action('flavor_ml_cache_flushed');
    }

    /**
     * Limpia caché de un idioma específico
     *
     * @param string $lang Código de idioma
     */
    public function flush_language($lang) {
        global $wpdb;

        // Limpiar transients que contengan el idioma
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_' . $this->cache_prefix . '%_' . $lang . '_%'
        ));

        // Limpiar memoria
        foreach ($this->memory_cache as $key => $value) {
            if (strpos($key, "_{$lang}_") !== false) {
                unset($this->memory_cache[$key]);
            }
        }

        do_action('flavor_ml_cache_language_flushed', $lang);
    }

    /**
     * Limpia caché expirado
     */
    public function cleanup_expired() {
        global $wpdb;

        // WordPress limpia transients automáticamente, pero podemos forzar
        $wpdb->query("
            DELETE a, b FROM {$wpdb->options} a
            INNER JOIN {$wpdb->options} b ON b.option_name = REPLACE(a.option_name, '_timeout', '')
            WHERE a.option_name LIKE '_transient_timeout_%'
            AND a.option_value < UNIX_TIMESTAMP()
        ");
    }

    // ================================================================
    // UTILIDADES
    // ================================================================

    /**
     * Construye la clave completa con prefijo
     *
     * @param string $key Clave
     * @return string
     */
    private function get_full_key($key) {
        return $this->cache_prefix . md5($key);
    }

    /**
     * Construye una clave para traducción
     *
     * @param string $type      Tipo de objeto
     * @param int    $object_id ID del objeto
     * @param string $lang      Código de idioma
     * @param string $field     Campo
     * @return string
     */
    private function build_translation_key($type, $object_id, $lang, $field) {
        return "{$type}_{$object_id}_{$lang}_{$field}";
    }

    /**
     * Construye una clave para string
     *
     * @param string $original String original
     * @param string $lang     Código de idioma
     * @param string $domain   Dominio
     * @return string
     */
    private function build_string_key($original, $lang, $domain) {
        return "str_" . md5($original) . "_{$lang}_{$domain}";
    }

    /**
     * Verifica si hay object cache disponible (Redis, Memcached, etc.)
     *
     * @return bool
     */
    private function has_object_cache() {
        return wp_using_ext_object_cache();
    }

    /**
     * Obtiene las estadísticas de caché
     *
     * @return array
     */
    public function get_stats() {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hit_rate = $total > 0 ? round(($this->stats['hits'] / $total) * 100, 2) : 0;

        return array_merge($this->stats, array(
            'total'        => $total,
            'hit_rate'     => $hit_rate,
            'memory_items' => count($this->memory_cache),
            'object_cache' => $this->has_object_cache(),
        ));
    }

    /**
     * Registra estadísticas si está en modo debug
     */
    public function maybe_log_stats() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $stats = $this->get_stats();
        if ($stats['total'] > 0) {
            error_log(sprintf(
                '[Flavor ML Cache] Hits: %d, Misses: %d, Hit Rate: %s%%, Memory Items: %d',
                $stats['hits'],
                $stats['misses'],
                $stats['hit_rate'],
                $stats['memory_items']
            ));
        }
    }

    /**
     * Obtiene el tamaño estimado del caché en memoria
     *
     * @return int Bytes
     */
    public function get_memory_size() {
        return strlen(serialize($this->memory_cache));
    }
}

// Programar warmup de caché
add_action('flavor_ml_cache_warmup', function() {
    $cache = Flavor_Translation_Cache::get_instance();
    $cache->do_warmup();
});

// Limpiar caché expirado diariamente
add_action('wp_scheduled_delete', function() {
    $cache = Flavor_Translation_Cache::get_instance();
    $cache->cleanup_expired();
});
