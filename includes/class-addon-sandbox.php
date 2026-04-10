<?php
/**
 * Sistema de Sandbox de Seguridad para Addons
 *
 * Ejecuta addons en un entorno controlado con límites de recursos
 * y validación de código para prevenir comportamientos maliciosos
 *
 * @package FlavorPlatform
 * @subpackage Addons
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para sandbox de seguridad
 *
 * @since 3.0.0
 */
class Flavor_Addon_Sandbox {

    /**
     * Instancia singleton
     *
     * @var Flavor_Addon_Sandbox
     */
    private static $instancia = null;

    /**
     * Addons en sandbox
     *
     * @var array
     */
    private $addons_sandboxed = [];

    /**
     * Recursos consumidos por addon
     *
     * @var array
     */
    private $resource_usage = [];

    /**
     * Límites de recursos
     *
     * @var array
     */
    private $resource_limits = [
        'max_execution_time' => 30, // segundos
        'max_memory' => 128 * 1024 * 1024, // 128 MB
        'max_db_queries' => 100,
        'max_file_size' => 10 * 1024 * 1024, // 10 MB
        'max_http_requests' => 20,
    ];

    /**
     * Funciones prohibidas
     *
     * @var array
     */
    private $prohibited_functions = [
        'exec',
        'shell_exec',
        'system',
        'passthru',
        'proc_open',
        'popen',
        'eval',
        'assert',
        'create_function',
        'phpinfo',
        'dl',
        'extract', // Puede sobrescribir variables
    ];

    /**
     * Clases prohibidas
     *
     * @var array
     */
    private $prohibited_classes = [
        'ReflectionFunction',
        'ReflectionMethod',
        'ReflectionClass',
    ];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Addon_Sandbox
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
        // Monitorear queries de BD
        add_filter('query', [$this, 'monitor_db_query']);

        // Monitorear requests HTTP
        add_action('http_api_debug', [$this, 'monitor_http_request'], 10, 5);

        // Limpiar estadísticas periódicamente
        add_action('flavor_hourly_sandbox_cleanup', [$this, 'cleanup_stats']);
        if (!wp_next_scheduled('flavor_hourly_sandbox_cleanup')) {
            wp_schedule_event(time(), 'hourly', 'flavor_hourly_sandbox_cleanup');
        }
    }

    /**
     * Valida código de un addon antes de ejecutarlo
     *
     * @param string $file_path Ruta al archivo principal del addon
     * @return bool|WP_Error True si es seguro, WP_Error si no
     */
    public function validate_addon_code($file_path) {
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', __('Archivo no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Leer contenido
        $content = file_get_contents($file_path);

        if ($content === false) {
            return new WP_Error('read_error', __('No se pudo leer el archivo.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Verificar funciones prohibidas
        foreach ($this->prohibited_functions as $func) {
            if (preg_match('/\b' . preg_quote($func, '/') . '\s*\(/i', $content)) {
                return new WP_Error('prohibited_function', sprintf(
                    __('El addon usa la función prohibida: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $func
                ));
            }
        }

        // Verificar clases prohibidas
        foreach ($this->prohibited_classes as $class) {
            if (preg_match('/\bnew\s+' . preg_quote($class, '/') . '\s*\(/i', $content)) {
                return new WP_Error('prohibited_class', sprintf(
                    __('El addon usa la clase prohibida: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $class
                ));
            }
        }

        // Verificar código ofuscado (base64_decode sospechoso)
        if (preg_match('/base64_decode\s*\(\s*["\'][A-Za-z0-9+\/=]{50,}/i', $content)) {
            return new WP_Error('obfuscated_code', __('El addon contiene código ofuscado sospechoso.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Verificar archivos adicionales
        $addon_dir = dirname($file_path);
        $this->scan_addon_directory($addon_dir);

        return true;
    }

    /**
     * Escanea directorio de addon en busca de archivos sospechosos
     *
     * @param string $dir_path Ruta al directorio
     * @return void
     */
    private function scan_addon_directory($dir_path) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir_path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->validate_addon_code($file->getPathname());
            }
        }
    }

    /**
     * Ejecuta un addon en sandbox
     *
     * @param string $addon_slug Slug del addon
     * @param callable $callback Función a ejecutar
     * @return mixed|WP_Error Resultado o error
     */
    public function execute_sandboxed($addon_slug, $callback) {
        // Inicializar estadísticas para este addon
        if (!isset($this->resource_usage[$addon_slug])) {
            $this->resource_usage[$addon_slug] = [
                'start_time' => microtime(true),
                'start_memory' => memory_get_usage(true),
                'db_queries' => 0,
                'http_requests' => 0,
            ];
        }

        $this->addons_sandboxed[$addon_slug] = true;

        // Establecer límites
        $old_time_limit = ini_get('max_execution_time');
        set_time_limit($this->resource_limits['max_execution_time']);

        try {
            // Ejecutar callback
            $result = call_user_func($callback);

            // Verificar límites después de ejecución
            $this->check_resource_limits($addon_slug);

            return $result;

        } catch (Exception $e) {
            flavor_platform_log("Error en sandbox para addon {$addon_slug}: " . $e->getMessage(), 'error');
            return new WP_Error('sandbox_error', $e->getMessage());

        } finally {
            // Restaurar límites
            set_time_limit($old_time_limit);
            unset($this->addons_sandboxed[$addon_slug]);
        }
    }

    /**
     * Verifica límites de recursos
     *
     * @param string $addon_slug Slug del addon
     * @return bool|WP_Error True si está dentro de límites, WP_Error si no
     */
    private function check_resource_limits($addon_slug) {
        if (!isset($this->resource_usage[$addon_slug])) {
            return true;
        }

        $usage = $this->resource_usage[$addon_slug];

        // Verificar tiempo de ejecución
        $elapsed_time = microtime(true) - $usage['start_time'];
        if ($elapsed_time > $this->resource_limits['max_execution_time']) {
            return new WP_Error('time_limit_exceeded', sprintf(
                __('El addon excedió el límite de tiempo: %d segundos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $this->resource_limits['max_execution_time']
            ));
        }

        // Verificar memoria
        $memory_used = memory_get_usage(true) - $usage['start_memory'];
        if ($memory_used > $this->resource_limits['max_memory']) {
            return new WP_Error('memory_limit_exceeded', sprintf(
                __('El addon excedió el límite de memoria: %d MB', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $this->resource_limits['max_memory'] / 1024 / 1024
            ));
        }

        // Verificar queries
        if ($usage['db_queries'] > $this->resource_limits['max_db_queries']) {
            return new WP_Error('query_limit_exceeded', sprintf(
                __('El addon excedió el límite de queries: %d queries', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $this->resource_limits['max_db_queries']
            ));
        }

        // Verificar HTTP requests
        if ($usage['http_requests'] > $this->resource_limits['max_http_requests']) {
            return new WP_Error('http_limit_exceeded', sprintf(
                __('El addon excedió el límite de requests HTTP: %d requests', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $this->resource_limits['max_http_requests']
            ));
        }

        return true;
    }

    /**
     * Monitorea queries de base de datos
     *
     * @param string $query Query SQL
     * @return string
     */
    public function monitor_db_query($query) {
        // Identificar addon actual
        $current_addon = $this->get_current_sandboxed_addon();

        if ($current_addon) {
            if (!isset($this->resource_usage[$current_addon])) {
                $this->resource_usage[$current_addon] = ['db_queries' => 0];
            }

            $this->resource_usage[$current_addon]['db_queries']++;

            // Verificar límite
            if ($this->resource_usage[$current_addon]['db_queries'] > $this->resource_limits['max_db_queries']) {
                flavor_platform_log("Addon {$current_addon} excedió límite de queries", 'warning');
            }
        }

        return $query;
    }

    /**
     * Monitorea requests HTTP
     *
     * @param array $response Respuesta HTTP
     * @param string $context Contexto
     * @param string $class Clase
     * @param array $args Argumentos
     * @param string $url URL
     * @return void
     */
    public function monitor_http_request($response, $context, $class, $args, $url) {
        $current_addon = $this->get_current_sandboxed_addon();

        if ($current_addon) {
            if (!isset($this->resource_usage[$current_addon])) {
                $this->resource_usage[$current_addon] = ['http_requests' => 0];
            }

            $this->resource_usage[$current_addon]['http_requests']++;

            // Verificar límite
            if ($this->resource_usage[$current_addon]['http_requests'] > $this->resource_limits['max_http_requests']) {
                flavor_platform_log("Addon {$current_addon} excedió límite de HTTP requests", 'warning');
            }
        }
    }

    /**
     * Obtiene addon actual en sandbox
     *
     * @return string|null
     */
    private function get_current_sandboxed_addon() {
        $addons = array_keys($this->addons_sandboxed);
        return !empty($addons) ? end($addons) : null;
    }

    /**
     * Obtiene estadísticas de uso de recursos
     *
     * @param string $addon_slug Slug del addon
     * @return array|null
     */
    public function get_resource_stats($addon_slug) {
        if (!isset($this->resource_usage[$addon_slug])) {
            return null;
        }

        $usage = $this->resource_usage[$addon_slug];

        return [
            'execution_time' => isset($usage['start_time'])
                ? round(microtime(true) - $usage['start_time'], 3)
                : 0,
            'memory_used' => isset($usage['start_memory'])
                ? round((memory_get_usage(true) - $usage['start_memory']) / 1024 / 1024, 2)
                : 0,
            'db_queries' => $usage['db_queries'] ?? 0,
            'http_requests' => $usage['http_requests'] ?? 0,
        ];
    }

    /**
     * Obtiene todas las estadísticas
     *
     * @return array
     */
    public function get_all_stats() {
        $stats = [];

        foreach ($this->resource_usage as $addon_slug => $usage) {
            $stats[$addon_slug] = $this->get_resource_stats($addon_slug);
        }

        return $stats;
    }

    /**
     * Limpia estadísticas antiguas
     *
     * @return void
     */
    public function cleanup_stats() {
        $this->resource_usage = [];
        flavor_platform_log('Estadísticas de sandbox limpiadas');
    }

    /**
     * Configura límites de recursos personalizados
     *
     * @param array $limits Nuevos límites
     * @return void
     */
    public function set_resource_limits($limits) {
        $this->resource_limits = wp_parse_args($limits, $this->resource_limits);
    }

    /**
     * Obtiene límites actuales
     *
     * @return array
     */
    public function get_resource_limits() {
        return $this->resource_limits;
    }

    /**
     * Verifica si un addon está en lista blanca
     *
     * @param string $addon_slug Slug del addon
     * @return bool
     */
    public function is_whitelisted($addon_slug) {
        $whitelist = get_option('flavor_addon_whitelist', []);
        return in_array($addon_slug, $whitelist);
    }

    /**
     * Añade addon a lista blanca (bypass sandbox)
     *
     * @param string $addon_slug Slug del addon
     * @return bool
     */
    public function add_to_whitelist($addon_slug) {
        $whitelist = get_option('flavor_addon_whitelist', []);

        if (!in_array($addon_slug, $whitelist)) {
            $whitelist[] = $addon_slug;
            update_option('flavor_addon_whitelist', $whitelist);
            flavor_platform_log("Addon {$addon_slug} añadido a whitelist");
        }

        return true;
    }

    /**
     * Remueve addon de lista blanca
     *
     * @param string $addon_slug Slug del addon
     * @return bool
     */
    public function remove_from_whitelist($addon_slug) {
        $whitelist = get_option('flavor_addon_whitelist', []);

        $key = array_search($addon_slug, $whitelist);
        if ($key !== false) {
            unset($whitelist[$key]);
            update_option('flavor_addon_whitelist', array_values($whitelist));
            flavor_platform_log("Addon {$addon_slug} removido de whitelist");
        }

        return true;
    }

    /**
     * Genera reporte de seguridad de un addon
     *
     * @param string $file_path Ruta al archivo principal
     * @return array
     */
    public function generate_security_report($file_path) {
        $report = [
            'file' => $file_path,
            'timestamp' => current_time('mysql'),
            'issues' => [],
            'warnings' => [],
            'safe' => true,
        ];

        // Validar código
        $validation = $this->validate_addon_code($file_path);

        if (is_wp_error($validation)) {
            $report['safe'] = false;
            $report['issues'][] = $validation->get_error_message();
        }

        // Verificar permisos de archivo
        if (is_writable($file_path)) {
            $report['warnings'][] = __('El archivo es escribible, debería ser solo lectura.', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }

        // Verificar tamaño
        $size = filesize($file_path);
        if ($size > $this->resource_limits['max_file_size']) {
            $report['warnings'][] = sprintf(
                __('El archivo es muy grande: %s MB', FLAVOR_PLATFORM_TEXT_DOMAIN),
                round($size / 1024 / 1024, 2)
            );
        }

        return $report;
    }
}

/**
 * Función helper para validar addon
 *
 * @param string $file_path Ruta al archivo
 * @return bool|WP_Error
 */
function flavor_validate_addon($file_path) {
    return Flavor_Addon_Sandbox::get_instance()->validate_addon_code($file_path);
}

/**
 * Función helper para ejecutar en sandbox
 *
 * @param string $addon_slug Slug del addon
 * @param callable $callback Función a ejecutar
 * @return mixed|WP_Error
 */
function flavor_sandbox_execute($addon_slug, $callback) {
    return Flavor_Addon_Sandbox::get_instance()->execute_sandboxed($addon_slug, $callback);
}
