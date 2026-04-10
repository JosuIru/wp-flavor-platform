<?php
/**
 * Trait Dashboard Cache
 *
 * Proporciona caché de estadísticas para dashboards de módulos.
 * Reduce drásticamente las queries SQL en páginas de admin.
 *
 * USO:
 * 1. En el módulo: use Flavor_Dashboard_Cache_Trait;
 * 2. En el dashboard: $stats = $module->get_cached_dashboard_stats();
 *
 * @package FlavorPlatform
 * @since 3.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Flavor_Dashboard_Cache_Trait {

    /**
     * TTL por defecto de caché de dashboard (5 minutos)
     */
    protected int $dashboard_cache_ttl = 300;

    /**
     * Obtiene estadísticas de dashboard con caché
     *
     * @param callable|null $compute_stats_callback Función que calcula las estadísticas
     * @return array Estadísticas (de caché o calculadas)
     */
    public function get_cached_dashboard_stats(?callable $compute_stats_callback = null): array {
        $module_id = method_exists($this, 'get_id') ? $this->get_id() : 'unknown';
        $cache_key = 'flavor_dash_' . $module_id;

        // Intentar obtener de caché
        $cached_stats = get_transient($cache_key);

        if ($cached_stats !== false && is_array($cached_stats)) {
            return $cached_stats;
        }

        // Calcular estadísticas
        if ($compute_stats_callback && is_callable($compute_stats_callback)) {
            $stats = $compute_stats_callback();
        } elseif (method_exists($this, 'compute_dashboard_stats')) {
            $stats = $this->compute_dashboard_stats();
        } else {
            $stats = [];
        }

        // Guardar en caché
        if (!empty($stats)) {
            set_transient($cache_key, $stats, $this->get_dashboard_cache_ttl());
        }

        return $stats;
    }

    /**
     * Invalida la caché de dashboard del módulo
     *
     * Llamar cuando se modifican datos (crear/actualizar/eliminar)
     *
     * @return bool
     */
    public function invalidate_dashboard_cache(): bool {
        $module_id = method_exists($this, 'get_id') ? $this->get_id() : 'unknown';
        return delete_transient('flavor_dash_' . $module_id);
    }

    /**
     * Obtiene el TTL de caché para este módulo
     *
     * @return int Segundos
     */
    protected function get_dashboard_cache_ttl(): int {
        // En modo debug, caché más corta (1 minuto)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return 60;
        }

        return $this->dashboard_cache_ttl;
    }

    /**
     * Establece el TTL de caché
     *
     * @param int $seconds
     */
    protected function set_dashboard_cache_ttl(int $seconds): void {
        $this->dashboard_cache_ttl = max(60, min($seconds, 3600)); // Entre 1 min y 1 hora
    }

    /**
     * Hook para invalidar caché al modificar datos
     *
     * Registrar en init() del módulo:
     * add_action('flavor_module_{id}_data_changed', [$this, 'invalidate_dashboard_cache']);
     */
    public function setup_cache_invalidation_hooks(): void {
        $module_id = method_exists($this, 'get_id') ? $this->get_id() : 'unknown';

        // Hooks genéricos para invalidar caché
        add_action("flavor_module_{$module_id}_created", [$this, 'invalidate_dashboard_cache']);
        add_action("flavor_module_{$module_id}_updated", [$this, 'invalidate_dashboard_cache']);
        add_action("flavor_module_{$module_id}_deleted", [$this, 'invalidate_dashboard_cache']);
    }
}

/**
 * Helper function para obtener stats cacheadas desde dashboard view
 *
 * @param string $module_id ID del módulo
 * @param callable $compute_callback Función que calcula estadísticas
 * @param int $ttl TTL en segundos (default 300 = 5 min)
 * @return array
 */
function flavor_get_dashboard_stats(string $module_id, callable $compute_callback, int $ttl = 300): array {
    $cache_key = 'flavor_dash_' . sanitize_key($module_id);

    // Intentar obtener de caché
    $cached = get_transient($cache_key);
    if ($cached !== false && is_array($cached)) {
        return $cached;
    }

    // Calcular
    $stats = $compute_callback();

    // Guardar
    if (!empty($stats) && is_array($stats)) {
        // TTL más corto en debug
        $actual_ttl = (defined('WP_DEBUG') && WP_DEBUG) ? min($ttl, 60) : $ttl;
        set_transient($cache_key, $stats, $actual_ttl);
    }

    return is_array($stats) ? $stats : [];
}

/**
 * Invalida caché de dashboard de un módulo
 *
 * @param string $module_id
 * @return bool
 */
function flavor_invalidate_dashboard_cache(string $module_id): bool {
    return delete_transient('flavor_dash_' . sanitize_key($module_id));
}

/**
 * Invalida todas las cachés de dashboards
 *
 * @return int Número de cachés invalidadas
 */
function flavor_invalidate_all_dashboard_caches(): int {
    global $wpdb;

    $count = $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_flavor_dash_%'
            OR option_name LIKE '_transient_timeout_flavor_dash_%'"
    );

    return (int) ($count / 2); // Cada transient tiene 2 filas
}
