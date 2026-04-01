<?php
/**
 * Sistema de caché de objeto para Flavor Multilingual
 *
 * Proporciona caché persistente para queries frecuentes
 * usando wp_cache (compatible con Redis, Memcached, etc.)
 *
 * @package FlavorMultilingual
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_ML_Object_Cache {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Grupo de caché
     */
    const CACHE_GROUP = 'flavor_multilingual';

    /**
     * TTL por defecto (1 hora)
     */
    const DEFAULT_TTL = 3600;

    /**
     * Caché en memoria para la petición actual
     */
    private $runtime_cache = array();

    /**
     * Estadísticas de caché
     */
    private $stats = array(
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
    );

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Registrar grupo de caché como no persistente si no hay object cache externo
        if (!wp_using_ext_object_cache()) {
            wp_cache_add_non_persistent_groups(array(self::CACHE_GROUP));
        }

        // Limpiar caché en eventos relevantes
        add_action('flavor_ml_translation_saved', array($this, 'invalidate_translation_cache'), 10, 4);
        add_action('flavor_ml_language_updated', array($this, 'invalidate_language_cache'));
        add_action('flavor_ml_settings_updated', array($this, 'flush_all'));
    }

    /**
     * Obtener valor de caché
     *
     * @param string $key Clave de caché
     * @param string $subgroup Subgrupo opcional
     * @return mixed|false Valor o false si no existe
     */
    public function get($key, $subgroup = '') {
        $cache_key = $this->build_key($key, $subgroup);

        // Primero buscar en caché de runtime
        if (isset($this->runtime_cache[$cache_key])) {
            $this->stats['hits']++;
            return $this->runtime_cache[$cache_key];
        }

        // Buscar en wp_cache
        $value = wp_cache_get($cache_key, self::CACHE_GROUP);

        if ($value !== false) {
            $this->stats['hits']++;
            $this->runtime_cache[$cache_key] = $value;
            return $value;
        }

        $this->stats['misses']++;
        return false;
    }

    /**
     * Establecer valor en caché
     *
     * @param string $key Clave de caché
     * @param mixed $value Valor a guardar
     * @param string $subgroup Subgrupo opcional
     * @param int $ttl Tiempo de vida en segundos
     * @return bool
     */
    public function set($key, $value, $subgroup = '', $ttl = null) {
        $cache_key = $this->build_key($key, $subgroup);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        // Guardar en runtime
        $this->runtime_cache[$cache_key] = $value;

        // Guardar en wp_cache
        $result = wp_cache_set($cache_key, $value, self::CACHE_GROUP, $ttl);

        if ($result) {
            $this->stats['writes']++;
        }

        return $result;
    }

    /**
     * Eliminar valor de caché
     *
     * @param string $key Clave de caché
     * @param string $subgroup Subgrupo opcional
     * @return bool
     */
    public function delete($key, $subgroup = '') {
        $cache_key = $this->build_key($key, $subgroup);

        unset($this->runtime_cache[$cache_key]);

        return wp_cache_delete($cache_key, self::CACHE_GROUP);
    }

    /**
     * Obtener o establecer (get or set)
     *
     * @param string $key Clave de caché
     * @param callable $callback Función para generar valor si no existe
     * @param string $subgroup Subgrupo opcional
     * @param int $ttl Tiempo de vida
     * @return mixed
     */
    public function remember($key, $callback, $subgroup = '', $ttl = null) {
        $value = $this->get($key, $subgroup);

        if ($value !== false) {
            return $value;
        }

        $value = call_user_func($callback);

        if ($value !== null && $value !== false) {
            $this->set($key, $value, $subgroup, $ttl);
        }

        return $value;
    }

    /**
     * Construir clave de caché
     */
    private function build_key($key, $subgroup = '') {
        $prefix = $subgroup ? $subgroup . ':' : '';
        return $prefix . md5($key);
    }

    /**
     * Invalidar caché de traducción
     */
    public function invalidate_translation_cache($object_type, $object_id, $language, $field = '') {
        // Invalidar caché específica
        $patterns = array(
            "translation:{$object_type}:{$object_id}:{$language}",
            "translation:{$object_type}:{$object_id}:*",
            "translations:{$object_type}:{$object_id}",
            "progress:{$object_type}:{$object_id}",
        );

        foreach ($patterns as $pattern) {
            $this->delete($pattern, 'translations');
        }

        // Invalidar caché de estadísticas
        $this->delete('stats:global', 'stats');
        $this->delete("stats:{$language}", 'stats');

        // Acción para extensiones
        do_action('flavor_ml_cache_invalidated', $object_type, $object_id, $language);
    }

    /**
     * Invalidar caché de idiomas
     */
    public function invalidate_language_cache() {
        $this->delete('active_languages', 'languages');
        $this->delete('all_languages', 'languages');
        $this->delete('default_language', 'languages');

        // Limpiar runtime
        foreach ($this->runtime_cache as $key => $value) {
            if (strpos($key, 'languages') !== false) {
                unset($this->runtime_cache[$key]);
            }
        }
    }

    /**
     * Limpiar toda la caché del addon
     */
    public function flush_all() {
        // Limpiar runtime
        $this->runtime_cache = array();

        // Si hay object cache externo, intentar flush del grupo
        if (wp_using_ext_object_cache() && function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group(self::CACHE_GROUP);
        } else {
            // Fallback: eliminar transients conocidos
            $this->flush_transients();
        }

        do_action('flavor_ml_cache_flushed');
    }

    /**
     * Limpiar transients del addon
     */
    private function flush_transients() {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_flavor_ml_%'
             OR option_name LIKE '_transient_timeout_flavor_ml_%'"
        );
    }

    /**
     * Obtener estadísticas
     */
    public function get_stats() {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hit_rate = $total > 0 ? round(($this->stats['hits'] / $total) * 100, 2) : 0;

        return array_merge($this->stats, array(
            'hit_rate' => $hit_rate,
            'runtime_items' => count($this->runtime_cache),
            'using_external' => wp_using_ext_object_cache(),
        ));
    }

    // =========================================
    // HELPERS PARA CASOS DE USO ESPECÍFICOS
    // =========================================

    /**
     * Caché de idiomas activos
     */
    public function get_active_languages() {
        return $this->remember('active_languages', function() {
            global $wpdb;
            $table = $wpdb->prefix . 'flavor_languages';

            return $wpdb->get_results(
                "SELECT * FROM {$table}
                 WHERE is_active = 1
                 ORDER BY sort_order ASC",
                ARRAY_A
            );
        }, 'languages', 3600);
    }

    /**
     * Caché de traducción individual
     */
    public function get_translation($object_type, $object_id, $language, $field) {
        $key = "{$object_type}:{$object_id}:{$language}:{$field}";

        return $this->remember($key, function() use ($object_type, $object_id, $language, $field) {
            global $wpdb;
            $table = $wpdb->prefix . 'flavor_translations';

            return $wpdb->get_var($wpdb->prepare(
                "SELECT translation FROM {$table}
                 WHERE object_type = %s
                 AND object_id = %d
                 AND language_code = %s
                 AND field_name = %s",
                $object_type, $object_id, $language, $field
            ));
        }, 'translations', 1800);
    }

    /**
     * Caché de todas las traducciones de un objeto
     */
    public function get_object_translations($object_type, $object_id, $language) {
        $key = "{$object_type}:{$object_id}:{$language}:all";

        return $this->remember($key, function() use ($object_type, $object_id, $language) {
            global $wpdb;
            $table = $wpdb->prefix . 'flavor_translations';

            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT field_name, translation, status
                 FROM {$table}
                 WHERE object_type = %s
                 AND object_id = %d
                 AND language_code = %s",
                $object_type, $object_id, $language
            ), ARRAY_A);

            $translations = array();
            foreach ($results as $row) {
                $translations[$row['field_name']] = array(
                    'translation' => $row['translation'],
                    'status' => $row['status']
                );
            }

            return $translations;
        }, 'translations', 1800);
    }

    /**
     * Caché de estadísticas globales
     */
    public function get_global_stats() {
        return $this->remember('global', function() {
            global $wpdb;
            $table = $wpdb->prefix . 'flavor_translations';

            return array(
                'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"),
                'published' => (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$table} WHERE status = 'published'"
                ),
                'pending' => (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$table} WHERE status IN ('draft', 'pending', 'in_progress')"
                ),
                'review' => (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$table} WHERE status = 'needs_review'"
                ),
            );
        }, 'stats', 300); // 5 minutos para stats
    }

    /**
     * Caché de progreso por idioma
     */
    public function get_language_progress($language) {
        $key = "progress:{$language}";

        return $this->remember($key, function() use ($language) {
            global $wpdb;
            $table = $wpdb->prefix . 'flavor_translations';

            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT CONCAT(object_type, ':', object_id))
                 FROM {$table}
                 WHERE language_code = %s",
                $language
            ));

            $completed = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT CONCAT(object_type, ':', object_id))
                 FROM {$table}
                 WHERE language_code = %s
                 AND status = 'published'",
                $language
            ));

            return array(
                'total' => $total,
                'completed' => $completed,
                'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0
            );
        }, 'stats', 300);
    }

    /**
     * Caché de memoria de traducción (sugerencias)
     */
    public function get_tm_suggestions($text, $source_lang, $target_lang, $limit = 5) {
        $key = md5($text) . ":{$source_lang}:{$target_lang}";

        return $this->remember($key, function() use ($text, $source_lang, $target_lang, $limit) {
            $memory = Flavor_Translation_Memory::get_instance();
            return $memory->search($text, $source_lang, $target_lang, $limit);
        }, 'tm', 600); // 10 minutos
    }

    /**
     * Caché de glosario
     */
    public function get_glossary_terms($source_lang, $target_lang) {
        $key = "glossary:{$source_lang}:{$target_lang}";

        return $this->remember($key, function() use ($source_lang, $target_lang) {
            global $wpdb;
            $table = $wpdb->prefix . 'flavor_translation_glossary';

            return $wpdb->get_results($wpdb->prepare(
                "SELECT source_term, target_term, case_sensitive
                 FROM {$table}
                 WHERE source_lang = %s AND target_lang = %s
                 ORDER BY LENGTH(source_term) DESC",
                $source_lang, $target_lang
            ), ARRAY_A);
        }, 'glossary', 3600);
    }

    /**
     * Precalentar caché para un post
     */
    public function warm_post_cache($post_id) {
        $languages = $this->get_active_languages();

        foreach ($languages as $lang) {
            $this->get_object_translations('post', $post_id, $lang['code']);
        }
    }

    /**
     * Precalentar caché para frontend (página actual)
     */
    public function warm_frontend_cache() {
        // Idiomas activos
        $this->get_active_languages();

        // Estadísticas si es admin
        if (is_admin()) {
            $this->get_global_stats();
        }

        // Post actual
        if (is_singular()) {
            global $post;
            if ($post) {
                $this->warm_post_cache($post->ID);
            }
        }
    }
}
