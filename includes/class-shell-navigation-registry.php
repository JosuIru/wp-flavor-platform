<?php
/**
 * Flavor Shell Navigation Registry
 *
 * Sistema centralizado para registrar subpáginas y badges de módulos
 * que se mostrarán en el Flavor Admin Shell.
 *
 * @package FlavorChatIA
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Registry para navegación del Shell
 */
class Flavor_Shell_Navigation_Registry {

    /**
     * Instancia singleton
     *
     * @var Flavor_Shell_Navigation_Registry|null
     */
    private static $instance = null;

    /**
     * Subpáginas registradas por módulo
     * Formato: ['modulo-dashboard' => [subpages...]]
     *
     * @var array
     */
    private $module_subpages = [];

    /**
     * Callbacks para obtener badges
     * Formato: ['page-slug' => callable]
     *
     * @var array
     */
    private $badge_callbacks = [];

    /**
     * Cache de badges calculados (en memoria)
     *
     * @var array|null
     */
    private $badges_cache = null;

    /**
     * Clave de transient para cache de badges
     */
    const BADGES_TRANSIENT_KEY = 'flavor_shell_badges_cache';

    /**
     * Duración del cache de badges en segundos (5 minutos)
     */
    const BADGES_CACHE_DURATION = 300;

    /**
     * Mapa de página hija -> página padre
     * Para detectar en qué módulo estamos
     *
     * @var array
     */
    private $child_to_parent_map = [];

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Shell_Navigation_Registry
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
        // Hook para que los módulos registren sus navegaciones
        add_action('admin_init', [$this, 'trigger_registrations'], 5);

        // AJAX endpoint para actualizar badges
        add_action('wp_ajax_flavor_shell_get_badges', [$this, 'ajax_get_badges']);
    }

    /**
     * Disparar registros de módulos
     */
    public function trigger_registrations() {
        /**
         * Hook para que los módulos registren sus subpáginas y badges
         *
         * @param Flavor_Shell_Navigation_Registry $registry Instancia del registry
         */
        do_action('flavor_shell_register_navigation', $this);
    }

    /**
     * Registrar subpáginas de un módulo/dashboard
     *
     * @param string $parent_slug Slug del dashboard padre (ej: 'eventos-dashboard')
     * @param array  $subpages    Array de subpáginas con formato:
     *                            [
     *                                'slug'  => 'eventos-proximos',
     *                                'label' => 'Próximos',
     *                                'icon'  => 'dashicons-calendar',
     *                            ]
     * @return void
     */
    public function register_module_subpages($parent_slug, array $subpages) {
        $this->module_subpages[$parent_slug] = $subpages;

        // Construir mapa inverso para detectar página padre
        foreach ($subpages as $subpage) {
            if (!empty($subpage['slug'])) {
                $this->child_to_parent_map[$subpage['slug']] = $parent_slug;
            }
        }
    }

    /**
     * Registrar callback para obtener badge de una página
     *
     * @param string   $page_slug Slug de la página
     * @param callable $callback  Función que retorna el conteo (int)
     * @param string   $severity  Severidad: 'info', 'warning', 'danger' (default: 'info')
     * @return void
     */
    public function register_badge_callback($page_slug, callable $callback, $severity = 'info') {
        $this->badge_callbacks[$page_slug] = [
            'callback' => $callback,
            'severity' => $severity,
        ];
    }

    /**
     * Obtener subpáginas de un módulo
     *
     * @param string $parent_slug Slug del dashboard padre
     * @return array
     */
    public function get_module_subpages($parent_slug) {
        return $this->module_subpages[$parent_slug] ?? [];
    }

    /**
     * Verificar si un módulo tiene subpáginas registradas
     *
     * @param string $parent_slug Slug del dashboard padre
     * @return bool
     */
    public function has_subpages($parent_slug) {
        return !empty($this->module_subpages[$parent_slug]);
    }

    /**
     * Obtener el dashboard padre de una página
     *
     * @param string $current_page Página actual
     * @return string|null Slug del padre o null si no existe
     */
    public function get_parent_dashboard($current_page) {
        // Si la página actual ES un dashboard con subpáginas, retornar ella misma
        if (isset($this->module_subpages[$current_page])) {
            return $current_page;
        }

        // Si es una subpágina, retornar su padre
        return $this->child_to_parent_map[$current_page] ?? null;
    }

    /**
     * Obtener badge para una página específica
     *
     * @param string $page_slug Slug de la página
     * @return array|null ['count' => int, 'severity' => string] o null
     */
    public function get_badge($page_slug) {
        if (!isset($this->badge_callbacks[$page_slug])) {
            return null;
        }

        $config = $this->badge_callbacks[$page_slug];
        $count = call_user_func($config['callback']);

        if ($count <= 0) {
            return null;
        }

        return [
            'count' => (int) $count,
            'severity' => $config['severity'],
        ];
    }

    /**
     * Obtener todos los badges (con cache en transients)
     *
     * @param bool $force_refresh Forzar recálculo ignorando cache
     * @return array Formato: ['page-slug' => ['count' => int, 'severity' => string]]
     */
    public function get_all_badges($force_refresh = false) {
        // Primero verificar cache en memoria
        if (null !== $this->badges_cache && !$force_refresh) {
            return $this->badges_cache;
        }

        // Luego verificar transient
        if (!$force_refresh) {
            $cached = get_transient(self::BADGES_TRANSIENT_KEY);
            if (false !== $cached) {
                $this->badges_cache = $cached;
                return $this->badges_cache;
            }
        }

        // Calcular badges
        $this->badges_cache = [];

        foreach ($this->badge_callbacks as $slug => $config) {
            $count = call_user_func($config['callback']);

            if ($count > 0) {
                $this->badges_cache[$slug] = [
                    'count' => (int) $count,
                    'severity' => $config['severity'],
                ];
            }
        }

        // Guardar en transient
        set_transient(self::BADGES_TRANSIENT_KEY, $this->badges_cache, self::BADGES_CACHE_DURATION);

        return $this->badges_cache;
    }

    /**
     * AJAX handler para obtener badges actualizados
     */
    public function ajax_get_badges() {
        check_ajax_referer('flavor_admin_shell', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => 'No autorizado']);
        }

        // Forzar recálculo
        $badges = $this->get_all_badges(true);

        // Calcular badges agregados para dashboards
        $aggregated = [];
        foreach ($this->module_subpages as $parent_slug => $subpages) {
            $agg_badge = $this->get_aggregated_badge($parent_slug);
            if ($agg_badge) {
                $aggregated[$parent_slug] = $agg_badge;
            }
        }

        wp_send_json_success([
            'badges' => $badges,
            'aggregated' => $aggregated,
            'timestamp' => time(),
        ]);
    }

    /**
     * Calcular badge agregado para un dashboard padre
     * (suma de todos los badges de sus subpáginas)
     *
     * @param string $parent_slug Slug del dashboard padre
     * @return array|null ['count' => int, 'severity' => string] o null
     */
    public function get_aggregated_badge($parent_slug) {
        $subpages = $this->get_module_subpages($parent_slug);

        if (empty($subpages)) {
            // Si no tiene subpáginas, retornar su propio badge
            return $this->get_badge($parent_slug);
        }

        $total_count = 0;
        $max_severity = 'info';
        $severity_order = ['info' => 1, 'warning' => 2, 'danger' => 3];

        // Badge del propio dashboard padre
        $parent_badge = $this->get_badge($parent_slug);
        if ($parent_badge) {
            $total_count += $parent_badge['count'];
            if ($severity_order[$parent_badge['severity']] > $severity_order[$max_severity]) {
                $max_severity = $parent_badge['severity'];
            }
        }

        // Badges de subpáginas
        foreach ($subpages as $subpage) {
            $badge = $this->get_badge($subpage['slug']);
            if ($badge) {
                $total_count += $badge['count'];
                if ($severity_order[$badge['severity']] > $severity_order[$max_severity]) {
                    $max_severity = $badge['severity'];
                }
            }
        }

        if ($total_count <= 0) {
            return null;
        }

        return [
            'count' => $total_count,
            'severity' => $max_severity,
        ];
    }

    /**
     * Obtener todos los módulos registrados
     *
     * @return array Lista de slugs de dashboards padres
     */
    public function get_registered_modules() {
        return array_keys($this->module_subpages);
    }

    /**
     * Limpiar cache de badges
     *
     * @return void
     */
    public function clear_cache() {
        $this->badges_cache = null;
    }
}

// Inicializar singleton
Flavor_Shell_Navigation_Registry::get_instance();
