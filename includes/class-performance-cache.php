<?php
/**
 * Sistema de Cache de Rendimiento
 *
 * Cache inteligente para clases, rutas, estadísticas y configuraciones
 * Mejora significativamente el rendimiento reduciendo consultas a BD
 *
 * @package FlavorPlatform
 * @subpackage Performance
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestión de cache de rendimiento
 *
 * @since 3.0.0
 */
class Flavor_Performance_Cache {

    /**
     * Instancia singleton
     *
     * @var Flavor_Performance_Cache
     */
    private static $instancia = null;

    /**
     * Cache en memoria durante la request
     *
     * @var array
     */
    private $memoria_cache = [];

    /**
     * Estadísticas de uso del cache
     *
     * @var array
     */
    private $estadisticas = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
    ];

    /**
     * Grupos de cache y sus tiempos de expiración (en segundos)
     *
     * @var array
     */
    private $expiracion_grupos = [
        'addons' => HOUR_IN_SECONDS,           // 1 hora
        'modulos' => HOUR_IN_SECONDS,          // 1 hora
        'rutas' => DAY_IN_SECONDS,             // 24 horas
        'clases' => DAY_IN_SECONDS,            // 24 horas
        'estadisticas' => 5 * MINUTE_IN_SECONDS, // 5 minutos
        'configuracion' => HOUR_IN_SECONDS,    // 1 hora
        'permisos' => HOUR_IN_SECONDS,         // 1 hora
        'perfiles' => HOUR_IN_SECONDS,         // 1 hora
    ];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Performance_Cache
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Limpiar cache cuando se activan/desactivan addons o módulos
        add_action('flavor_addon_activated', [$this, 'limpiar_cache_addons']);
        add_action('flavor_addon_deactivated', [$this, 'limpiar_cache_addons']);
        add_action('flavor_module_activated', [$this, 'limpiar_cache_modulos']);
        add_action('flavor_module_deactivated', [$this, 'limpiar_cache_modulos']);

        // Limpiar cache cuando se guardan opciones importantes
        add_action('update_option_flavor_chat_settings', [$this, 'limpiar_cache_configuracion']);
        add_action('update_option_flavor_app_profile', [$this, 'limpiar_cache_perfiles']);

        // Mostrar estadísticas en debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('shutdown', [$this, 'mostrar_estadisticas']);
        }
    }

    /**
     * Obtiene un valor del cache
     *
     * @param string $clave Clave del cache
     * @param string $grupo Grupo del cache
     * @return mixed|false Valor o false si no existe
     */
    public function get($clave, $grupo = 'default') {
        $clave_completa = $this->generar_clave($clave, $grupo);

        // Primero buscar en cache de memoria
        if (isset($this->memoria_cache[$clave_completa])) {
            $this->estadisticas['hits']++;
            return $this->memoria_cache[$clave_completa];
        }

        // Buscar en transients de WordPress
        $valor = get_transient($clave_completa);

        if ($valor !== false) {
            // Guardar en cache de memoria para siguiente uso
            $this->memoria_cache[$clave_completa] = $valor;
            $this->estadisticas['hits']++;
            return $valor;
        }

        $this->estadisticas['misses']++;
        return false;
    }

    /**
     * Guarda un valor en el cache
     *
     * @param string $clave Clave del cache
     * @param mixed $valor Valor a guardar
     * @param string $grupo Grupo del cache
     * @param int $expiracion Tiempo de expiración en segundos (opcional)
     * @return bool
     */
    public function set($clave, $valor, $grupo = 'default', $expiracion = null) {
        $clave_completa = $this->generar_clave($clave, $grupo);

        // Guardar en cache de memoria
        $this->memoria_cache[$clave_completa] = $valor;

        // Determinar tiempo de expiración
        if ($expiracion === null) {
            $expiracion = isset($this->expiracion_grupos[$grupo])
                ? $this->expiracion_grupos[$grupo]
                : HOUR_IN_SECONDS;
        }

        // Guardar en transients
        $resultado = set_transient($clave_completa, $valor, $expiracion);

        if ($resultado) {
            $this->estadisticas['sets']++;
        }

        return $resultado;
    }

    /**
     * Elimina un valor del cache
     *
     * @param string $clave Clave del cache
     * @param string $grupo Grupo del cache
     * @return bool
     */
    public function delete($clave, $grupo = 'default') {
        $clave_completa = $this->generar_clave($clave, $grupo);

        // Eliminar de memoria
        unset($this->memoria_cache[$clave_completa]);

        // Eliminar transient
        return delete_transient($clave_completa);
    }

    /**
     * Limpia todo el cache de un grupo
     *
     * @param string $grupo Grupo a limpiar
     * @return int Número de claves eliminadas
     */
    public function limpiar_grupo($grupo) {
        global $wpdb;

        $prefijo = $this->generar_clave('', $grupo);
        $patron = '_transient_' . $prefijo . '%';

        $query = $wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $patron
        );

        $eliminados = $wpdb->query($query);

        // Limpiar también timeout transients
        $patron_timeout = '_transient_timeout_' . $prefijo . '%';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $patron_timeout
        ));

        // Limpiar cache de memoria de este grupo
        foreach ($this->memoria_cache as $clave => $valor) {
            if (strpos($clave, $prefijo) === 0) {
                unset($this->memoria_cache[$clave]);
            }
        }

        return $eliminados;
    }

    /**
     * Limpia todo el cache
     *
     * @return bool
     */
    public function limpiar_todo() {
        global $wpdb;

        // Limpiar memoria
        $this->memoria_cache = [];

        // Eliminar todos los transients de flavor
        $patron = '_transient_flavor_cache_%';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $patron
        ));

        $patron_timeout = '_transient_timeout_flavor_cache_%';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $patron_timeout
        ));

        return true;
    }

    /**
     * Obtiene o genera un valor cacheado con callback
     *
     * @param string $clave Clave del cache
     * @param callable $callback Función para generar el valor si no existe en cache
     * @param string $grupo Grupo del cache
     * @param int $expiracion Tiempo de expiración
     * @return mixed
     */
    public function remember($clave, $callback, $grupo = 'default', $expiracion = null) {
        $valor = $this->get($clave, $grupo);

        if ($valor === false) {
            $valor = call_user_func($callback);
            $this->set($clave, $valor, $grupo, $expiracion);
        }

        return $valor;
    }

    /**
     * Genera una clave completa para el cache
     *
     * @param string $clave Clave base
     * @param string $grupo Grupo
     * @return string
     */
    private function generar_clave($clave, $grupo) {
        return 'flavor_cache_' . $grupo . '_' . $clave;
    }

    /**
     * Limpia cache de addons
     *
     * @return void
     */
    public function limpiar_cache_addons() {
        $this->limpiar_grupo('addons');
    }

    /**
     * Limpia cache de módulos
     *
     * @return void
     */
    public function limpiar_cache_modulos() {
        $this->limpiar_grupo('modulos');
    }

    /**
     * Limpia cache de configuración
     *
     * @return void
     */
    public function limpiar_cache_configuracion() {
        $this->limpiar_grupo('configuracion');
    }

    /**
     * Limpia cache de perfiles
     *
     * @return void
     */
    public function limpiar_cache_perfiles() {
        $this->limpiar_grupo('perfiles');
    }

    /**
     * Obtiene estadísticas de uso del cache
     *
     * @return array
     */
    public function get_estadisticas() {
        $total_requests = $this->estadisticas['hits'] + $this->estadisticas['misses'];
        $hit_rate = $total_requests > 0
            ? round(($this->estadisticas['hits'] / $total_requests) * 100, 2)
            : 0;

        return array_merge($this->estadisticas, [
            'total_requests' => $total_requests,
            'hit_rate' => $hit_rate,
            'memoria_items' => count($this->memoria_cache),
        ]);
    }

    /**
     * Muestra estadísticas de cache en debug mode
     *
     * @return void
     */
    public function mostrar_estadisticas() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $stats = $this->get_estadisticas();

        flavor_log_debug( '=== Flavor Performance Cache Stats ===', 'Cache' );
        flavor_log_debug( 'Hits: ' . $stats['hits'], 'Cache' );
        flavor_log_debug( 'Misses: ' . $stats['misses'], 'Cache' );
        flavor_log_debug( 'Sets: ' . $stats['sets'], 'Cache' );
        flavor_log_debug( 'Hit Rate: ' . $stats['hit_rate'] . '%', 'Cache' );
        flavor_log_debug( 'Memory Items: ' . $stats['memoria_items'], 'Cache' );
    }

    /**
     * Precarga datos comunes en el cache
     *
     * @return void
     */
    public function precarga() {
        // Precarga addons activos
        $this->remember('addons_activos', function() {
            return Flavor_Addon_Manager::get_active_addons();
        }, 'addons');

        // Precarga módulos activos
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $this->remember('modulos_activos', function() {
                return Flavor_Platform_Module_Loader::get_instance()->get_loaded_modules();
            }, 'modulos');
        }

        // Precarga configuración principal
        $this->remember('settings_principal', function() {
            return get_option('flavor_chat_settings', []);
        }, 'configuracion');
    }

    /**
     * Obtiene tamaño estimado del cache en MB
     *
     * @return float
     */
    public function get_tamano_cache() {
        global $wpdb;

        $query = "SELECT SUM(LENGTH(option_value)) as total
                  FROM $wpdb->options
                  WHERE option_name LIKE '_transient_flavor_cache_%'";

        $resultado = $wpdb->get_var($query);

        return $resultado ? round($resultado / 1024 / 1024, 2) : 0;
    }

    /**
     * Obtiene lista de claves en cache de un grupo
     *
     * @param string $grupo Grupo a listar
     * @return array
     */
    public function listar_claves_grupo($grupo) {
        global $wpdb;

        $prefijo = '_transient_' . $this->generar_clave('', $grupo);

        $query = $wpdb->prepare(
            "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
            $prefijo . '%'
        );

        $resultados = $wpdb->get_col($query);

        return array_map(function($clave) use ($prefijo) {
            return str_replace($prefijo, '', $clave);
        }, $resultados);
    }
}

/**
 * Función helper para acceder al cache
 *
 * @return Flavor_Performance_Cache
 */
function flavor_cache() {
    return Flavor_Performance_Cache::get_instance();
}
