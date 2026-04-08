<?php
/**
 * API de Compatibilidad de Módulos
 *
 * Endpoint de diagnóstico que devuelve la matriz de compatibilidad
 * de módulos en los 3 niveles (WordPress, Flutter, API)
 *
 * @package FlavorChatIA
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Compatibility_API {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'flavor-platform/v1';

    /**
     * Módulos conocidos con sus configuraciones
     */
    private $known_modules = array(
        'eventos' => array('category' => 'community', 'flutter_folder' => 'eventos'),
        'socios' => array('category' => 'community', 'flutter_folder' => 'socios'),
        'foros' => array('category' => 'social', 'flutter_folder' => 'foros'),
        'marketplace' => array('category' => 'commerce', 'flutter_folder' => 'marketplace'),
        'reservas' => array('category' => 'booking', 'flutter_folder' => 'reservas'),
        'cursos' => array('category' => 'education', 'flutter_folder' => 'cursos'),
        'talleres' => array('category' => 'education', 'flutter_folder' => 'talleres'),
        'grupos-consumo' => array('category' => 'commerce', 'flutter_folder' => 'grupos_consumo'),
        'banco-tiempo' => array('category' => 'community', 'flutter_folder' => 'banco_tiempo'),
        'carpooling' => array('category' => 'mobility', 'flutter_folder' => 'carpooling'),
        'bicicletas-compartidas' => array('category' => 'mobility', 'flutter_folder' => 'bicicletas_compartidas'),
        'espacios-comunes' => array('category' => 'booking', 'flutter_folder' => 'espacios_comunes'),
        'transparencia' => array('category' => 'governance', 'flutter_folder' => 'transparencia'),
        'participacion' => array('category' => 'governance', 'flutter_folder' => 'participacion'),
        'incidencias' => array('category' => 'management', 'flutter_folder' => 'incidencias'),
        'tramites' => array('category' => 'management', 'flutter_folder' => 'tramites'),
        'multimedia' => array('category' => 'media', 'flutter_folder' => 'multimedia'),
        'radio' => array('category' => 'media', 'flutter_folder' => 'radio'),
        'podcast' => array('category' => 'media', 'flutter_folder' => 'podcast'),
        'biblioteca' => array('category' => 'education', 'flutter_folder' => 'biblioteca'),
        'comunidades' => array('category' => 'community', 'flutter_folder' => 'comunidades'),
        'red-social' => array('category' => 'social', 'flutter_folder' => 'red_social'),
        'chat-interno' => array('category' => 'social', 'flutter_folder' => 'chat_interno'),
        'chat-grupos' => array('category' => 'social', 'flutter_folder' => 'chat_grupos'),
        'woocommerce' => array('category' => 'commerce', 'flutter_folder' => 'woocommerce'),
        'parkings' => array('category' => 'mobility', 'flutter_folder' => 'parkings'),
        'huertos-urbanos' => array('category' => 'environment', 'flutter_folder' => 'huertos_urbanos'),
        'compostaje' => array('category' => 'environment', 'flutter_folder' => 'compostaje'),
        'reciclaje' => array('category' => 'environment', 'flutter_folder' => 'reciclaje'),
        'presupuestos-participativos' => array('category' => 'governance', 'flutter_folder' => 'presupuestos_participativos'),
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
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Registrar rutas de la API
     */
    public function register_routes() {
        // Matriz de compatibilidad completa
        register_rest_route(self::API_NAMESPACE, '/modules/compatibility', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_compatibility_matrix'),
            'permission_callback' => array($this, 'check_api_key'),
        ));

        // Verificar módulo específico
        register_rest_route(self::API_NAMESPACE, '/modules/(?P<id>[a-z0-9-]+)/check', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'check_module'),
            'permission_callback' => array($this, 'check_api_key'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // Resumen rápido para Flutter
        register_rest_route(self::API_NAMESPACE, '/modules/supported', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_supported_modules'),
            'permission_callback' => array($this, 'check_api_key'),
        ));

        // Diagnóstico completo
        register_rest_route(self::API_NAMESPACE, '/diagnostics', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_diagnostics'),
            'permission_callback' => array($this, 'check_api_key'),
        ));
    }

    /**
     * Verificar API key
     */
    public function check_api_key($request) {
        // Permitir acceso público para diagnósticos básicos
        $public_routes = array('/modules/supported', '/diagnostics');
        $route = $request->get_route();

        foreach ($public_routes as $public_route) {
            if (strpos($route, $public_route) !== false) {
                return true;
            }
        }

        $api_key = flavor_get_vbp_api_key_from_request( $request );
        return flavor_check_vbp_automation_access( $api_key, 'diagnostics_admin' );
    }

    /**
     * Obtener matriz de compatibilidad completa
     */
    public function get_compatibility_matrix($request) {
        $wordpress_modules = $this->get_wordpress_modules();
        $flutter_modules = $this->get_flutter_modules();
        $api_endpoints = $this->get_api_endpoints();

        $matrix = array();
        $summary = array(
            'full_support' => 0,
            'partial_support' => 0,
            'no_support' => 0,
        );

        foreach ($this->known_modules as $module_id => $config) {
            $wp_active = isset($wordpress_modules[$module_id]) && $wordpress_modules[$module_id];
            $flutter_available = in_array($config['flutter_folder'], $flutter_modules);
            $api_available = in_array($module_id, $api_endpoints);

            $support_level = ($wp_active ? 1 : 0) + ($flutter_available ? 1 : 0) + ($api_available ? 1 : 0);

            $matrix[$module_id] = array(
                'id' => $module_id,
                'category' => $config['category'],
                'flutter_folder' => $config['flutter_folder'],
                'wordpress' => $wp_active,
                'flutter' => $flutter_available,
                'api' => $api_available,
                'support_level' => $support_level,
                'support_text' => $support_level . '/3',
                'recommendation' => $this->get_recommendation($support_level, $wp_active, $flutter_available, $api_available),
            );

            if ($support_level === 3) {
                $summary['full_support']++;
            } elseif ($support_level >= 1) {
                $summary['partial_support']++;
            } else {
                $summary['no_support']++;
            }
        }

        return rest_ensure_response(array(
            'matrix' => $matrix,
            'summary' => $summary,
            'generated_at' => current_time('mysql'),
        ));
    }

    /**
     * Verificar módulo específico
     */
    public function check_module($request) {
        $module_id = $request->get_param('id');

        if (!isset($this->known_modules[$module_id])) {
            return new WP_Error(
                'module_not_found',
                'Módulo no encontrado en la lista de módulos conocidos',
                array('status' => 404)
            );
        }

        $config = $this->known_modules[$module_id];
        $wordpress_modules = $this->get_wordpress_modules();
        $flutter_modules = $this->get_flutter_modules();
        $api_endpoints = $this->get_api_endpoints();

        $wp_active = isset($wordpress_modules[$module_id]) && $wordpress_modules[$module_id];
        $flutter_available = in_array($config['flutter_folder'], $flutter_modules);
        $api_available = in_array($module_id, $api_endpoints);

        $support_level = ($wp_active ? 1 : 0) + ($flutter_available ? 1 : 0) + ($api_available ? 1 : 0);

        $issues = array();
        if (!$wp_active) {
            $issues[] = 'Módulo no activo en WordPress';
        }
        if (!$flutter_available) {
            $issues[] = 'Sin template Flutter (carpeta: ' . $config['flutter_folder'] . ')';
        }
        if (!$api_available) {
            $issues[] = 'Endpoint API no disponible';
        }

        return rest_ensure_response(array(
            'id' => $module_id,
            'category' => $config['category'],
            'levels' => array(
                'wordpress' => array(
                    'available' => $wp_active,
                    'message' => $wp_active ? 'Módulo activo' : 'Módulo no activo',
                ),
                'flutter' => array(
                    'available' => $flutter_available,
                    'folder' => $config['flutter_folder'],
                    'message' => $flutter_available ? 'Template disponible' : 'Sin template',
                ),
                'api' => array(
                    'available' => $api_available,
                    'namespace' => 'flavor-' . $module_id . '/v1',
                    'message' => $api_available ? 'Endpoint activo' : 'Endpoint no disponible',
                ),
            ),
            'support_level' => $support_level,
            'support_text' => $support_level . '/3',
            'issues' => $issues,
            'recommendation' => $this->get_recommendation($support_level, $wp_active, $flutter_available, $api_available),
            'can_enable' => $support_level >= 2,
            'fallback_available' => !$flutter_available && $wp_active && $api_available,
        ));
    }

    /**
     * Obtener módulos con soporte completo (para Flutter)
     */
    public function get_supported_modules($request) {
        $wordpress_modules = $this->get_wordpress_modules();
        $flutter_modules = $this->get_flutter_modules();
        $api_endpoints = $this->get_api_endpoints();

        $full_support = array();
        $partial_support = array();

        foreach ($this->known_modules as $module_id => $config) {
            $wp_active = isset($wordpress_modules[$module_id]) && $wordpress_modules[$module_id];
            $flutter_available = in_array($config['flutter_folder'], $flutter_modules);
            $api_available = in_array($module_id, $api_endpoints);

            $support_level = ($wp_active ? 1 : 0) + ($flutter_available ? 1 : 0) + ($api_available ? 1 : 0);

            $module_info = array(
                'id' => $module_id,
                'flutter_folder' => $config['flutter_folder'],
                'category' => $config['category'],
            );

            if ($support_level === 3) {
                $full_support[] = $module_info;
            } elseif ($support_level === 2) {
                $module_info['missing'] = !$wp_active ? 'wordpress' : (!$flutter_available ? 'flutter' : 'api');
                $module_info['fallback'] = !$flutter_available ? 'webview' : null;
                $partial_support[] = $module_info;
            }
        }

        return rest_ensure_response(array(
            'full_support' => $full_support,
            'partial_support' => $partial_support,
            'enable_automatically' => array_column($full_support, 'id'),
            'ask_permission' => array_column($partial_support, 'id'),
        ));
    }

    /**
     * Obtener diagnóstico completo del sistema
     */
    public function get_diagnostics($request) {
        global $wpdb;

        $diagnostics = array(
            'platform' => array(
                'wordpress_version' => get_bloginfo('version'),
                'php_version' => phpversion(),
                'plugin_version' => defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : 'unknown',
                'site_url' => get_site_url(),
                'is_multisite' => is_multisite(),
            ),
            'modules' => array(
                'total_known' => count($this->known_modules),
                'wordpress_active' => count(array_filter($this->get_wordpress_modules())),
                'flutter_templates' => count($this->get_flutter_modules()),
                'api_endpoints' => count($this->get_api_endpoints()),
            ),
            'database' => array(
                'flavor_tables' => $this->count_flavor_tables(),
            ),
            'apis' => array(
                'vbp_claude' => $this->check_api_available('flavor-vbp/v1/claude/status'),
                'site_builder' => $this->check_api_available('flavor-site-builder/v1/system/health'),
                'app_discovery' => $this->check_api_available('app-discovery/v1/info'),
                'multilingual' => $this->check_api_available('flavor-multilingual/v1/languages'),
            ),
            'recommendations' => $this->generate_recommendations(),
        );

        return rest_ensure_response($diagnostics);
    }

    /**
     * Obtener módulos de WordPress activos
     */
    private function get_wordpress_modules() {
        $active_modules = get_option('flavor_active_modules', array());

        if (!is_array($active_modules)) {
            $active_modules = array();
        }

        $result = array();
        foreach ($this->known_modules as $module_id => $config) {
            $result[$module_id] = in_array($module_id, $active_modules);
        }

        return $result;
    }

    /**
     * Obtener módulos Flutter disponibles
     */
    private function get_flutter_modules() {
        // En producción, esto se configuraría desde la instalación
        // Por ahora, retornamos los conocidos como disponibles
        $flutter_base = WP_CONTENT_DIR . '/plugins/flavor-chat-ia/mobile-apps/lib/features/modules';

        $available = array();

        if (is_dir($flutter_base)) {
            $directories = glob($flutter_base . '/*', GLOB_ONLYDIR);
            foreach ($directories as $dir) {
                $folder_name = basename($dir);
                // Verificar si tiene screen
                $has_screen = !empty(glob($dir . '/*_screen.dart'));
                if ($has_screen) {
                    $available[] = $folder_name;
                }
            }
        } else {
            // Fallback: asumir que todos los conocidos están disponibles
            foreach ($this->known_modules as $module_id => $config) {
                $available[] = $config['flutter_folder'];
            }
        }

        return $available;
    }

    /**
     * Obtener endpoints API disponibles
     */
    private function get_api_endpoints() {
        $available = array();

        foreach ($this->known_modules as $module_id => $config) {
            $namespace = 'flavor-' . $module_id . '/v1';

            // Verificar si el namespace está registrado
            $routes = rest_get_server()->get_routes($namespace);
            if (!empty($routes)) {
                $available[] = $module_id;
            }
        }

        return $available;
    }

    /**
     * Generar recomendación según nivel de soporte
     */
    private function get_recommendation($level, $wp, $flutter, $api) {
        switch ($level) {
            case 3:
                return array(
                    'action' => 'enable',
                    'message' => 'Habilitar automáticamente',
                    'ask_permission' => false,
                );
            case 2:
                $missing = !$wp ? 'WordPress' : (!$flutter ? 'Flutter' : 'API');
                $fallback = !$flutter ? 'WebView' : 'ninguno';
                return array(
                    'action' => 'ask',
                    'message' => "Pedir permiso al usuario (falta: $missing)",
                    'missing_level' => $missing,
                    'fallback' => $fallback,
                    'ask_permission' => true,
                );
            case 1:
                return array(
                    'action' => 'warn',
                    'message' => 'Advertir y no recomendar',
                    'ask_permission' => false,
                );
            default:
                return array(
                    'action' => 'skip',
                    'message' => 'No disponible',
                    'ask_permission' => false,
                );
        }
    }

    /**
     * Contar tablas de Flavor en la base de datos
     */
    private function count_flavor_tables() {
        global $wpdb;

        $tables = $wpdb->get_results(
            "SHOW TABLES LIKE '{$wpdb->prefix}flavor%'"
        );

        return count($tables);
    }

    /**
     * Verificar si una API está disponible
     */
    private function check_api_available($endpoint) {
        $routes = rest_get_server()->get_routes();
        $namespace = '/' . explode('/', $endpoint)[0];

        foreach ($routes as $route => $handlers) {
            if (strpos($route, $namespace) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generar recomendaciones basadas en el estado actual
     */
    private function generate_recommendations() {
        $recommendations = array();

        $wordpress_modules = $this->get_wordpress_modules();
        $active_count = count(array_filter($wordpress_modules));

        if ($active_count === 0) {
            $recommendations[] = array(
                'type' => 'warning',
                'message' => 'No hay módulos activos en WordPress',
                'action' => 'Activar módulos desde Flavor Platform > Módulos',
            );
        }

        if ($active_count > 15) {
            $recommendations[] = array(
                'type' => 'info',
                'message' => "Hay $active_count módulos activos",
                'action' => 'Considerar desactivar módulos no utilizados para mejorar rendimiento',
            );
        }

        return $recommendations;
    }
}

// Inicializar
Flavor_Module_Compatibility_API::get_instance();
