<?php
/**
 * Flavor Cache Manager
 *
 * Gestor centralizado de caché usando transients de WordPress.
 * Optimiza queries frecuentes con TTL configurable.
 *
 * @package Flavor_Platform
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Cache_Manager {

    /**
     * Prefijo para todas las claves de caché
     */
    const PREFIX = 'flavor_cache_';

    /**
     * TTL por defecto (5 minutos)
     */
    const DEFAULT_TTL = 300;

    /**
     * TTL para analytics (15 minutos)
     */
    const ANALYTICS_TTL = 900;

    /**
     * TTL para KPIs del dashboard (10 minutos)
     */
    const KPIS_TTL = 600;

    /**
     * TTL para badges (2 minutos - deben estar relativamente frescos)
     */
    const BADGES_TTL = 120;

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Cache en memoria para evitar múltiples get_transient en misma request
     *
     * @var array
     */
    private $memory_cache = [];

    /**
     * Obtener instancia singleton
     *
     * @return self
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
        // Hooks para invalidación automática de caché
        add_action('flavor_module_activated', [$this, 'invalidate_all']);
        add_action('flavor_module_deactivated', [$this, 'invalidate_all']);
        add_action('flavor_settings_updated', [$this, 'invalidate_all']);
    }

    /**
     * Obtener valor de caché o ejecutar callback si no existe
     *
     * @param string   $key      Clave única para el valor
     * @param callable $callback Función que genera el valor si no está en caché
     * @param int      $ttl      Tiempo de vida en segundos (default: 300)
     * @return mixed Valor cacheado o resultado del callback
     */
    public static function remember($key, $callback, $ttl = self::DEFAULT_TTL) {
        $instance = self::get_instance();
        $cache_key = self::PREFIX . md5($key);

        // Primero revisar caché en memoria (misma request)
        if (isset($instance->memory_cache[$cache_key])) {
            return $instance->memory_cache[$cache_key];
        }

        // Luego revisar transient
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            $instance->memory_cache[$cache_key] = $cached;
            return $cached;
        }

        // Ejecutar callback y cachear
        $value = $callback();

        // Solo cachear si el valor no es null (permite cachear false, 0, arrays vacíos)
        if ($value !== null) {
            set_transient($cache_key, $value, $ttl);
            $instance->memory_cache[$cache_key] = $value;
        }

        return $value;
    }

    /**
     * Obtener valor de caché sin callback
     *
     * @param string $key Clave del valor
     * @return mixed|null Valor o null si no existe
     */
    public static function get($key) {
        $instance = self::get_instance();
        $cache_key = self::PREFIX . md5($key);

        if (isset($instance->memory_cache[$cache_key])) {
            return $instance->memory_cache[$cache_key];
        }

        $cached = get_transient($cache_key);
        if ($cached !== false) {
            $instance->memory_cache[$cache_key] = $cached;
            return $cached;
        }

        return null;
    }

    /**
     * Establecer valor en caché
     *
     * @param string $key   Clave del valor
     * @param mixed  $value Valor a cachear
     * @param int    $ttl   Tiempo de vida en segundos
     * @return bool True si se guardó correctamente
     */
    public static function set($key, $value, $ttl = self::DEFAULT_TTL) {
        $instance = self::get_instance();
        $cache_key = self::PREFIX . md5($key);

        $instance->memory_cache[$cache_key] = $value;
        return set_transient($cache_key, $value, $ttl);
    }

    /**
     * Eliminar valor específico de caché
     *
     * @param string $key Clave del valor
     * @return bool True si se eliminó correctamente
     */
    public static function forget($key) {
        $instance = self::get_instance();
        $cache_key = self::PREFIX . md5($key);

        unset($instance->memory_cache[$cache_key]);
        return delete_transient($cache_key);
    }

    /**
     * Invalidar caché de analytics
     *
     * @return int Número de transients eliminados
     */
    public static function invalidate_analytics() {
        global $wpdb;

        $count = $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_" . self::PREFIX . "%analytics%'
             OR option_name LIKE '_transient_timeout_" . self::PREFIX . "%analytics%'"
        );

        // Limpiar memoria
        $instance = self::get_instance();
        foreach ($instance->memory_cache as $key => $value) {
            if (strpos($key, 'analytics') !== false) {
                unset($instance->memory_cache[$key]);
            }
        }

        return $count;
    }

    /**
     * Invalidar caché de KPIs
     *
     * @return int Número de transients eliminados
     */
    public static function invalidate_kpis() {
        global $wpdb;

        $count = $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_" . self::PREFIX . "%kpi%'
             OR option_name LIKE '_transient_timeout_" . self::PREFIX . "%kpi%'"
        );

        $instance = self::get_instance();
        foreach ($instance->memory_cache as $key => $value) {
            if (strpos($key, 'kpi') !== false) {
                unset($instance->memory_cache[$key]);
            }
        }

        return $count;
    }

    /**
     * Invalidar caché de badges del shell
     *
     * @return int Número de transients eliminados
     */
    public static function invalidate_badges() {
        global $wpdb;

        $count = $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_" . self::PREFIX . "%badge%'
             OR option_name LIKE '_transient_timeout_" . self::PREFIX . "%badge%'"
        );

        $instance = self::get_instance();
        foreach ($instance->memory_cache as $key => $value) {
            if (strpos($key, 'badge') !== false) {
                unset($instance->memory_cache[$key]);
            }
        }

        return $count;
    }

    /**
     * Invalidar toda la caché de Flavor
     *
     * @return int Número de transients eliminados
     */
    public static function invalidate_all() {
        global $wpdb;

        $count = $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_" . self::PREFIX . "%'
             OR option_name LIKE '_transient_timeout_" . self::PREFIX . "%'"
        );

        // Limpiar memoria
        $instance = self::get_instance();
        $instance->memory_cache = [];

        return $count;
    }

    /**
     * Invalidar caché para un módulo específico
     *
     * @param string $module_slug Slug del módulo
     * @return int Número de transients eliminados
     */
    public static function invalidate_module($module_slug) {
        global $wpdb;

        $safe_slug = sanitize_key($module_slug);

        $count = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE %s
             OR option_name LIKE %s",
            '_transient_' . self::PREFIX . '%' . $safe_slug . '%',
            '_transient_timeout_' . self::PREFIX . '%' . $safe_slug . '%'
        ));

        $instance = self::get_instance();
        foreach ($instance->memory_cache as $key => $value) {
            if (strpos($key, $safe_slug) !== false) {
                unset($instance->memory_cache[$key]);
            }
        }

        return $count;
    }

    /**
     * Obtener estadísticas de caché
     *
     * @return array Estadísticas de uso
     */
    public static function get_stats() {
        global $wpdb;

        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_" . self::PREFIX . "%'
             AND option_name NOT LIKE '_transient_timeout_%'"
        );

        $size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_" . self::PREFIX . "%'
             AND option_name NOT LIKE '_transient_timeout_%'"
        );

        $instance = self::get_instance();

        return [
            'transient_count' => (int) $count,
            'transient_size_bytes' => (int) $size,
            'transient_size_kb' => round((int) $size / 1024, 2),
            'memory_cache_count' => count($instance->memory_cache),
        ];
    }

    /**
     * Generar clave de caché con contexto de usuario
     *
     * @param string $base_key Clave base
     * @param int    $user_id  ID de usuario (opcional, usa el actual)
     * @return string Clave completa
     */
    public static function user_key($base_key, $user_id = null) {
        $user_id = $user_id ?: get_current_user_id();
        return $base_key . '_user_' . $user_id;
    }

    /**
     * Generar clave de caché con contexto de período
     *
     * @param string $base_key Clave base
     * @param int    $dias     Número de días del período
     * @return string Clave completa
     */
    public static function period_key($base_key, $dias) {
        return $base_key . '_period_' . (int) $dias . 'd';
    }
}

/**
 * Función helper global para acceso rápido al cache
 *
 * @param string        $key      Clave del valor
 * @param callable|null $callback Callback opcional para remember
 * @param int           $ttl      TTL opcional
 * @return mixed
 */
if (!function_exists('flavor_cache_manager_get_or_remember')) {
    function flavor_cache_manager_get_or_remember($key, $callback = null, $ttl = 300) {
        if ($callback !== null) {
            return Flavor_Cache_Manager::remember($key, $callback, $ttl);
        }
        return Flavor_Cache_Manager::get($key);
    }
}

// Alias histórico. Guardado detrás de function_exists porque
// includes/class-performance-cache.php declara otra flavor_cache() con
// distinta firma (devuelve singleton del legacy Performance_Cache). El
// guard evita el fatal "Cannot redeclare" cuando ambos archivos se cargan
// en la misma request.
if (!function_exists('flavor_cache')) {
    function flavor_cache($key, $callback = null, $ttl = 300) {
        return flavor_cache_manager_get_or_remember($key, $callback, $ttl);
    }
}
