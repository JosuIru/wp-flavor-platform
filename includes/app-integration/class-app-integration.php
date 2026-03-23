<?php
/**
 * Sistema de Integración con APKs Móviles
 *
 * Permite que las APKs existentes de wp-calendario-experiencias
 * funcionen con Flavor Chat IA sin recompilar
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador principal de integración con apps móviles
 */
class Flavor_App_Integration {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Detector de plugins
     */
    private $plugin_detector;

    /**
     * Adaptador de API
     */
    private $api_adapter;

    /**
     * Obtiene la instancia singleton
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
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Carga dependencias
     */
    private function load_dependencies() {
        require_once __DIR__ . '/class-plugin-detector.php';
        require_once __DIR__ . '/class-api-adapter.php';

        $this->plugin_detector = new Flavor_Plugin_Detector();
        $this->api_adapter = new Flavor_API_Adapter($this->plugin_detector);
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // Registrar endpoints de descubrimiento
        add_action('rest_api_init', [$this, 'register_discovery_endpoints']);

        // Registrar rutas unificadas (compatibilidad)
        add_action('rest_api_init', [$this, 'register_unified_routes']);
    }

    /**
     * Registra endpoints de descubrimiento
     */
    public function register_discovery_endpoints() {
        // Endpoint principal de descubrimiento
        register_rest_route('app-discovery/v1', '/info', [
            'methods' => 'GET',
            'callback' => [$this, 'get_system_info'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Endpoint de módulos disponibles
        register_rest_route('app-discovery/v1', '/modules', [
            'methods' => 'GET',
            'callback' => [$this, 'get_available_modules'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Endpoint de configuración de tema
        register_rest_route('app-discovery/v1', '/theme', [
            'methods' => 'GET',
            'callback' => [$this, 'get_theme_config'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Endpoint de layouts para apps
        register_rest_route('app-discovery/v1', '/layouts', [
            'methods' => 'GET',
            'callback' => [$this, 'get_layouts_config'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Endpoint de debug para verificar configuración de navegación (solo admin)
        register_rest_route('app-discovery/v1', '/debug-navigation', [
            'methods' => 'GET',
            'callback' => [$this, 'debug_navigation_config'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * GET /app-discovery/v1/debug-navigation
     * Endpoint de debug para verificar la configuración de navegación
     * Solo accesible para administradores
     */
    public function debug_navigation_config($request) {
        $app_config = get_option('flavor_apps_config', []);
        $client_tabs_config = $this->get_client_tabs_config();
        $layout_data = $this->get_layout_data();

        // Verificar caché
        $cache_key = 'flavor_api_system_info';
        $cached_data = get_transient($cache_key);

        return new WP_REST_Response([
            'debug_info' => [
                'timestamp' => current_time('mysql'),
                'php_version' => PHP_VERSION,
            ],
            'raw_config' => [
                'tabs' => $app_config['tabs'] ?? 'NO DEFINIDO',
                'tabs_count' => is_array($app_config['tabs'] ?? null) ? count($app_config['tabs']) : 0,
                'navigation_style' => $app_config['navigation_style'] ?? 'NO DEFINIDO',
                'default_tab' => $app_config['default_tab'] ?? 'NO DEFINIDO',
                'drawer_items' => $app_config['drawer_items'] ?? 'NO DEFINIDO',
                'web_sections_menu' => $app_config['web_sections_menu'] ?? 'NO DEFINIDO',
            ],
            'processed_tabs' => [
                'client_tabs_enabled' => $client_tabs_config['tabs'],
                'client_tabs_count' => count($client_tabs_config['tabs']),
                'default_tab' => $client_tabs_config['default_tab'],
                'features' => $client_tabs_config['features'],
            ],
            'layout_data_summary' => [
                'navigation_items_count' => count($layout_data['navigation'] ?? []),
                'client_tabs_count' => count($layout_data['client_tabs'] ?? []),
                'use_bottom_navigation' => $layout_data['settings']['use_bottom_navigation'] ?? 'N/A',
                'navigation_style' => $layout_data['settings']['navigation_style'] ?? 'N/A',
                'drawer_items_count' => count($layout_data['settings']['drawer_items'] ?? []),
            ],
            'cache_status' => [
                'has_cache' => $cached_data !== false,
                'cache_layouts_tabs_count' => is_array($cached_data) ? count($cached_data['layouts']['client_tabs'] ?? []) : 0,
            ],
            'full_layout_data' => $layout_data,
        ], 200);
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }

    /**
     * GET /app-discovery/v1/layouts
     * Devuelve configuración completa de layouts para apps
     */
    public function get_layouts_config($request) {
        $layout_data = $this->get_layout_data();

        if (!$layout_data['available']) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $layout_data['message'],
            ], 200);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $layout_data,
        ], 200);
    }

    /**
     * Registra rutas unificadas (bridging)
     */
    public function register_unified_routes() {
        // Ruta unificada para chat
        register_rest_route('unified-api/v1', '/chat', [
            'methods' => 'POST',
            'callback' => [$this->api_adapter, 'unified_chat'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Ruta unificada para información del sitio
        register_rest_route('unified-api/v1', '/site-info', [
            'methods' => 'GET',
            'callback' => [$this->api_adapter, 'unified_site_info'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * GET /app-discovery/v1/info
     * Devuelve información completa del sistema
     * Con caché de 5 minutos para reducir carga
     */
    public function get_system_info($request) {
        // Verificar si hay forzado de refresh
        $force_refresh = $request->get_param('refresh') === '1';
        $cache_key = 'flavor_api_system_info';
        $cache_duration = 5 * MINUTE_IN_SECONDS; // 5 minutos

        // Intentar obtener de caché
        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                $cached['from_cache'] = true;
                return new WP_REST_Response($cached, 200);
            }
        }

        $detected_systems = $this->plugin_detector->detect_active_systems();

        // Obtener configuración de app
        $app_config = get_option('flavor_apps_config', []);
        $app_name = isset($app_config['app_name']) ? $app_config['app_name'] : get_bloginfo('name');
        $app_description = isset($app_config['app_description']) ? $app_config['app_description'] : get_bloginfo('description');

        $response = [
            'wordpress_url' => get_site_url(),
            'site_name' => get_bloginfo('name'),
            'site_description' => get_bloginfo('description'),
            'app_name' => $app_name,
            'app_description' => $app_description,
            'active_systems' => $detected_systems,
            'unified_api' => true,
            'api_version' => '1.0',
            'theme' => $this->get_theme_data(),
            'layouts' => $this->get_layout_data(),
            'custom_post_types' => $this->get_configured_cpts_data(),
            'timezone' => wp_timezone_string(),
            'language' => get_locale(),
            'cached_at' => current_time('mysql'),
            'from_cache' => false,
        ];

        // Guardar en caché
        set_transient($cache_key, $response, $cache_duration);

        return new WP_REST_Response($response, 200);
    }

    /**
     * GET /app-discovery/v1/modules
     * Devuelve módulos disponibles con configuración
     * Con caché de 5 minutos para reducir carga
     */
    public function get_available_modules($request) {
        // Verificar si hay forzado de refresh
        $force_refresh = $request->get_param('refresh') === '1';
        $cache_key = 'flavor_api_available_modules';
        $cache_duration = 5 * MINUTE_IN_SECONDS; // 5 minutos

        // Intentar obtener de caché
        if (!$force_refresh) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                $cached['from_cache'] = true;
                return new WP_REST_Response($cached, 200);
            }
        }

        $modules = [];
        $known_modules = $this->get_known_app_modules_catalog();

        // Módulos de Flavor Chat IA - usar get_active_modules_cached() para obtener TODOS los activos
        if ($this->plugin_detector->is_flavor_chat_active()) {
            if (class_exists('Flavor_Chat_Module_Loader')) {
                // Obtener todos los módulos activos (no solo los cargados)
                $active_module_ids = Flavor_Chat_Module_Loader::get_active_modules_cached();
                $loader = Flavor_Chat_Module_Loader::get_instance();
                $loaded_modules = $loader->get_loaded_modules();

                foreach ($active_module_ids as $module_id) {
                    // Si el módulo está cargado, usar sus metadatos
                    if (isset($loaded_modules[$module_id])) {
                        $module = $loaded_modules[$module_id];
                        $modules[] = [
                            'id' => $module_id,
                            'name' => $module->get_name(),
                            'description' => $module->get_description(),
                            'system' => 'flavor-chat-ia',
                            'api_namespace' => 'flavor-chat-ia/v1',
                            'icon' => $this->get_module_icon($module_id),
                            'color' => $this->get_module_color($module_id),
                            'show_in_navigation' => true,
                            'config' => $this->get_module_config($module_id),
                        ];
                    } else {
                        // Módulo activo pero no cargado: usar catálogo
                        $meta = $known_modules[$module_id] ?? [];
                        $modules[] = [
                            'id' => $module_id,
                            'name' => $meta['name'] ?? ucwords(str_replace(['_', '-'], ' ', $module_id)),
                            'description' => $meta['description'] ?? '',
                            'system' => 'flavor-chat-ia',
                            'api_namespace' => 'flavor-chat-ia/v1',
                            'icon' => $meta['icon'] ?? $this->get_module_icon($module_id),
                            'color' => $meta['color'] ?? $this->get_module_color($module_id),
                            'show_in_navigation' => true,
                            'config' => $this->get_module_config($module_id),
                        ];
                    }
                }
            }
        }

        // Módulos Frontend (landings públicas)
        $frontend_modules = $this->get_frontend_modules();
        $modules = array_merge($modules, $frontend_modules);

        // Funcionalidades de wp-calendario-experiencias
        if ($this->plugin_detector->is_calendario_active()) {
            $modules[] = [
                'id' => 'reservas',
                'name' => __('Reservas', 'flavor-chat-ia'),
                'description' => __('Sistema de reservas y tickets', 'flavor-chat-ia'),
                'system' => 'calendario-experiencias',
                'api_namespace' => 'chat-ia-mobile/v1',
                'icon' => 'calendar',
                'color' => '#2196F3',
                'show_in_navigation' => true,
                'config' => [],
            ];
        }

        // Funcionalidades de basabere-campamentos
        if ($this->plugin_detector->is_basabere_active()) {
            $modules[] = [
                'id' => 'campamentos',
                'name' => __('Campamentos', 'flavor-chat-ia'),
                'description' => __('Gestión de campamentos e inscripciones', 'flavor-chat-ia'),
                'system' => 'basabere-campamentos',
                'api_namespace' => 'camps/v1',
                'icon' => 'terrain',
                'color' => '#4CAF50',
                'show_in_navigation' => true,
                'config' => [],
            ];
        }

        // Incluir módulos habilitados en flavor_apps_config (aunque no estén cargados)
        $app_config = get_option('flavor_apps_config', []);
        $enabled_modules = isset($app_config['modules']) && is_array($app_config['modules'])
            ? $app_config['modules']
            : [];

        if (!empty($enabled_modules)) {
            $existing_ids = array_column($modules, 'id');

            foreach ($enabled_modules as $module_id => $module_settings) {
                if (empty($module_settings['enabled'])) {
                    continue;
                }
                if (in_array($module_id, $existing_ids, true)) {
                    continue;
                }
                $meta = $known_modules[$module_id] ?? [];
                $modules[] = [
                    'id' => $module_id,
                    'name' => $meta['name'] ?? ucwords(str_replace(['_', '-'], ' ', $module_id)),
                    'description' => $meta['description'] ?? '',
                    'system' => 'flavor-apps-config',
                    'api_namespace' => $meta['api_namespace'] ?? '',
                    'icon' => $meta['icon'] ?? $this->get_module_icon($module_id),
                    'color' => $meta['color'] ?? $this->get_module_color($module_id),
                    'show_in_navigation' => true,
                    'config' => $this->get_module_config($module_id),
                ];
            }
        }

        // Nota: Los módulos activos ya se obtienen arriba con get_active_modules_cached()
        // No es necesario el fallback a flavor_chat_ia_settings['active_modules']

        $response_data = [
            'success' => true,
            'modules' => $modules,
            'total' => count($modules),
            'cached_at' => current_time('mysql'),
            'from_cache' => false,
        ];

        // Guardar en caché
        set_transient($cache_key, $response_data, $cache_duration);

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Catálogo base de módulos configurables en apps
     */
    private function get_known_app_modules_catalog() {
        return [
            'woocommerce' => [
                'name' => __('WooCommerce', 'flavor-chat-ia'),
                'description' => __('Integración con tienda WooCommerce', 'flavor-chat-ia'),
                'icon' => 'shopping_cart',
                'color' => '#9C27B0',
            ],
            'grupos_consumo' => [
                'name' => __('Grupos de Consumo', 'flavor-chat-ia'),
                'description' => __('Pedidos colectivos y gestión de grupos', 'flavor-chat-ia'),
                'icon' => 'shopping_basket',
                'color' => '#4CAF50',
            ],
            'marketplace' => [
                'name' => __('Marketplace', 'flavor-chat-ia'),
                'description' => __('Anuncios de regalo, venta e intercambio', 'flavor-chat-ia'),
                'icon' => 'store',
                'color' => '#FF9800',
            ],
            'banco_tiempo' => [
                'name' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'description' => __('Intercambio de servicios y tiempo', 'flavor-chat-ia'),
                'icon' => 'access_time',
                'color' => '#2196F3',
            ],
            'facturas' => [
                'name' => __('Facturas', 'flavor-chat-ia'),
                'description' => __('Gestión de facturas para administradores', 'flavor-chat-ia'),
                'icon' => 'receipt',
                'color' => '#607D8B',
            ],
            'fichaje_empleados' => [
                'name' => __('Fichaje de Empleados', 'flavor-chat-ia'),
                'description' => __('Control de horarios y asistencia', 'flavor-chat-ia'),
                'icon' => 'work',
                'color' => '#795548',
            ],
            'eventos' => [
                'name' => __('Eventos', 'flavor-chat-ia'),
                'description' => __('Gestión de eventos comunitarios', 'flavor-chat-ia'),
                'icon' => 'event',
                'color' => '#E91E63',
            ],
            'socios' => [
                'name' => __('Gestión de Miembros', 'flavor-chat-ia'),
                'description' => __('Control de miembros y cuotas', 'flavor-chat-ia'),
                'icon' => 'people',
                'color' => '#3F51B5',
            ],
            'advertising' => [
                'name' => __('Publicidad Ética', 'flavor-chat-ia'),
                'description' => __('Sistema de anuncios éticos', 'flavor-chat-ia'),
                'icon' => 'campaign',
                'color' => '#FF5722',
            ],
            'foros' => [
                'name' => __('Foros', 'flavor-chat-ia'),
                'description' => __('Debates y conversaciones por temas', 'flavor-chat-ia'),
                'icon' => 'forum',
                'color' => '#8E24AA',
            ],
            'red_social' => [
                'name' => __('Red Social', 'flavor-chat-ia'),
                'description' => __('Red social comunitaria', 'flavor-chat-ia'),
                'icon' => 'public',
                'color' => '#009688',
            ],
            'chat_grupos' => [
                'name' => __('Chat de Grupos', 'flavor-chat-ia'),
                'description' => __('Canales y grupos temáticos', 'flavor-chat-ia'),
                'icon' => 'chat',
                'color' => '#03A9F4',
            ],
            'chat_interno' => [
                'name' => __('Chat Interno', 'flavor-chat-ia'),
                'description' => __('Mensajería privada', 'flavor-chat-ia'),
                'icon' => 'chat_bubble',
                'color' => '#0288D1',
            ],
            'comunidades' => [
                'name' => __('Comunidades', 'flavor-chat-ia'),
                'description' => __('Gestión de comunidades', 'flavor-chat-ia'),
                'icon' => 'groups',
                'color' => '#4CAF50',
            ],
            'colectivos' => [
                'name' => __('Colectivos', 'flavor-chat-ia'),
                'description' => __('Asociaciones y cooperativas', 'flavor-chat-ia'),
                'icon' => 'handshake',
                'color' => '#6D4C41',
            ],
            'participacion' => [
                'name' => __('Participación', 'flavor-chat-ia'),
                'description' => __('Votaciones y propuestas', 'flavor-chat-ia'),
                'icon' => 'how_to_vote',
                'color' => '#7CB342',
            ],
            'presupuestos_participativos' => [
                'name' => __('Presupuestos Participativos', 'flavor-chat-ia'),
                'description' => __('Decide inversiones comunitarias', 'flavor-chat-ia'),
                'icon' => 'account_balance',
                'color' => '#5D4037',
            ],
            'transparencia' => [
                'name' => __('Transparencia', 'flavor-chat-ia'),
                'description' => __('Portal de transparencia', 'flavor-chat-ia'),
                'icon' => 'visibility',
                'color' => '#6A1B9A',
            ],
            'avisos' => [
                'name' => __('Avisos Municipales', 'flavor-chat-ia'),
                'description' => __('Comunicados oficiales', 'flavor-chat-ia'),
                'icon' => 'warning',
                'color' => '#F44336',
            ],
            'tramites' => [
                'name' => __('Trámites', 'flavor-chat-ia'),
                'description' => __('Gestión de trámites online', 'flavor-chat-ia'),
                'icon' => 'assignment',
                'color' => '#0097A7',
            ],
            'huertos_urbanos' => [
                'name' => __('Huertos Urbanos', 'flavor-chat-ia'),
                'description' => __('Parcelas y cultivos comunitarios', 'flavor-chat-ia'),
                'icon' => 'eco',
                'color' => '#43A047',
            ],
            'bicicletas' => [
                'name' => __('Bicicletas Compartidas', 'flavor-chat-ia'),
                'description' => __('Sistema de bicicletas comunitarias', 'flavor-chat-ia'),
                'icon' => 'pedal_bike',
                'color' => '#8BC34A',
            ],
            'compostaje' => [
                'name' => __('Compostaje', 'flavor-chat-ia'),
                'description' => __('Compostaje comunitario', 'flavor-chat-ia'),
                'icon' => 'recycling',
                'color' => '#7CB342',
            ],
            'reciclaje' => [
                'name' => __('Reciclaje', 'flavor-chat-ia'),
                'description' => __('Gestión de reciclaje', 'flavor-chat-ia'),
                'icon' => 'recycling',
                'color' => '#10B981',
            ],
            'carpooling' => [
                'name' => __('Carpooling', 'flavor-chat-ia'),
                'description' => __('Viajes compartidos', 'flavor-chat-ia'),
                'icon' => 'directions_car',
                'color' => '#FF9800',
            ],
            'cursos' => [
                'name' => __('Cursos', 'flavor-chat-ia'),
                'description' => __('Plataforma de cursos', 'flavor-chat-ia'),
                'icon' => 'menu_book',
                'color' => '#3F51B5',
            ],
            'podcast' => [
                'name' => __('Podcast', 'flavor-chat-ia'),
                'description' => __('Podcast comunitario', 'flavor-chat-ia'),
                'icon' => 'mic',
                'color' => '#9C27B0',
            ],
            'radio' => [
                'name' => __('Radio', 'flavor-chat-ia'),
                'description' => __('Radio comunitaria', 'flavor-chat-ia'),
                'icon' => 'radio',
                'color' => '#E91E63',
            ],
            'multimedia' => [
                'name' => __('Multimedia', 'flavor-chat-ia'),
                'description' => __('Galería y contenidos multimedia', 'flavor-chat-ia'),
                'icon' => 'perm_media',
                'color' => '#FF7043',
            ],
            'biblioteca' => [
                'name' => __('Biblioteca', 'flavor-chat-ia'),
                'description' => __('Biblioteca comunitaria', 'flavor-chat-ia'),
                'icon' => 'local_library',
                'color' => '#3F51B5',
            ],
            'talleres' => [
                'name' => __('Talleres', 'flavor-chat-ia'),
                'description' => __('Talleres y workshops', 'flavor-chat-ia'),
                'icon' => 'build',
                'color' => '#795548',
            ],
            'incidencias' => [
                'name' => __('Incidencias', 'flavor-chat-ia'),
                'description' => __('Incidencias urbanas', 'flavor-chat-ia'),
                'icon' => 'report_problem',
                'color' => '#D32F2F',
            ],
            'espacios_comunes' => [
                'name' => __('Espacios Comunes', 'flavor-chat-ia'),
                'description' => __('Reservas de espacios', 'flavor-chat-ia'),
                'icon' => 'meeting_room',
                'color' => '#009688',
            ],
            'parkings' => [
                'name' => __('Parkings', 'flavor-chat-ia'),
                'description' => __('Parkings comunitarios', 'flavor-chat-ia'),
                'icon' => 'local_parking',
                'color' => '#5C6BC0',
            ],
            'ayuda_vecinal' => [
                'name' => __('Ayuda Vecinal', 'flavor-chat-ia'),
                'description' => __('Red de ayuda mutua', 'flavor-chat-ia'),
                'icon' => 'volunteer_activism',
                'color' => '#FF7043',
            ],
            'empresarial' => [
                'name' => __('Empresarial', 'flavor-chat-ia'),
                'description' => __('Componentes profesionales', 'flavor-chat-ia'),
                'icon' => 'business',
                'color' => '#1E4B5B',
            ],
            'clientes' => [
                'name' => __('Clientes', 'flavor-chat-ia'),
                'description' => __('CRM básico', 'flavor-chat-ia'),
                'icon' => 'person',
                'color' => '#607D8B',
            ],
            'hosteleria' => [
                'name' => __('Bares y Hostelería', 'flavor-chat-ia'),
                'description' => __('Directorio de bares', 'flavor-chat-ia'),
                'icon' => 'restaurant',
                'color' => '#F57C00',
            ],
            'campamentos' => [
                'name' => __('Campamentos', 'flavor-chat-ia'),
                'description' => __('Campamentos, inscripciones y gestión administrativa', 'flavor-chat-ia'),
                'icon' => 'terrain',
                'color' => '#4CAF50',
                'api_namespace' => 'camps/v1',
            ],
        ];
    }

    /**
     * Obtiene módulos frontend disponibles
     */
    private function get_frontend_modules() {
        $frontend_modules_config = [
            'grupos-consumo' => [
                'name' => __('Grupos de Consumo', 'flavor-chat-ia'),
                'description' => __('Pedidos colectivos de productos locales y ecológicos', 'flavor-chat-ia'),
            ],
            'banco-tiempo' => [
                'name' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'description' => __('Intercambio de servicios y habilidades por horas', 'flavor-chat-ia'),
            ],
            'ayuntamiento' => [
                'name' => __('Ayuntamiento', 'flavor-chat-ia'),
                'description' => __('Portal ciudadano con trámites, noticias y servicios municipales', 'flavor-chat-ia'),
            ],
            'comunidades' => [
                'name' => __('Comunidades', 'flavor-chat-ia'),
                'description' => __('Redes vecinales y grupos de interés común', 'flavor-chat-ia'),
            ],
            'espacios-comunes' => [
                'name' => __('Espacios Comunes', 'flavor-chat-ia'),
                'description' => __('Reserva de salas, locales y espacios compartidos', 'flavor-chat-ia'),
            ],
            'ayuda-vecinal' => [
                'name' => __('Ayuda Vecinal', 'flavor-chat-ia'),
                'description' => __('Red de apoyo mutuo entre vecinos', 'flavor-chat-ia'),
            ],
            'huertos-urbanos' => [
                'name' => __('Huertos Urbanos', 'flavor-chat-ia'),
                'description' => __('Gestión de parcelas y huertos comunitarios', 'flavor-chat-ia'),
            ],
            'biblioteca' => [
                'name' => __('Biblioteca', 'flavor-chat-ia'),
                'description' => __('Catálogo y préstamo de libros comunitarios', 'flavor-chat-ia'),
            ],
            'cursos' => [
                'name' => __('Cursos y Talleres', 'flavor-chat-ia'),
                'description' => __('Formación y actividades educativas', 'flavor-chat-ia'),
            ],
            'podcast' => [
                'name' => __('Podcast', 'flavor-chat-ia'),
                'description' => __('Contenido de audio y episodios', 'flavor-chat-ia'),
            ],
            'radio' => [
                'name' => __('Radio', 'flavor-chat-ia'),
                'description' => __('Emisora de radio comunitaria', 'flavor-chat-ia'),
            ],
            'bicicletas' => [
                'name' => __('Bicicletas', 'flavor-chat-ia'),
                'description' => __('Préstamo y reparación de bicicletas', 'flavor-chat-ia'),
            ],
            'reciclaje' => [
                'name' => __('Reciclaje', 'flavor-chat-ia'),
                'description' => __('Puntos de reciclaje y economía circular', 'flavor-chat-ia'),
            ],
            'tienda-local' => [
                'name' => __('Tienda Local', 'flavor-chat-ia'),
                'description' => __('Directorio de comercios y productos locales', 'flavor-chat-ia'),
            ],
            'incidencias' => [
                'name' => __('Incidencias', 'flavor-chat-ia'),
                'description' => __('Reporte y seguimiento de problemas urbanos', 'flavor-chat-ia'),
            ],
        ];

        $modules = [];

        // Verificar qué módulos frontend están activos
        if (class_exists('Flavor_Frontend_Loader')) {
            $frontend_loader = Flavor_Frontend_Loader::get_instance();
            $controllers = $frontend_loader->get_controllers();

            foreach ($controllers as $module_id => $controller) {
                if (isset($frontend_modules_config[$module_id])) {
                    $config = $frontend_modules_config[$module_id];
                    $modules[] = [
                        'id' => $module_id,
                        'name' => $config['name'],
                        'description' => $config['description'],
                        'system' => 'flavor-frontend',
                        'api_namespace' => 'flavor/v1',
                        'web_url' => home_url('/' . $module_id . '/'),
                        'icon' => $this->get_module_icon($module_id),
                        'color' => $this->get_module_color($module_id),
                        'show_in_navigation' => true,
                        'config' => $this->get_module_config($module_id),
                    ];
                }
            }
        }

        return $modules;
    }

    /**
     * GET /app-discovery/v1/theme
     * Devuelve configuración de tema para las apps
     */
    public function get_theme_config($request) {
        return new WP_REST_Response($this->get_theme_data(), 200);
    }

    /**
     * Obtiene datos del tema actual
     */
    private function get_theme_data() {
        // Obtener configuración de apps (si existe)
        $app_config = get_option('flavor_apps_config', []);

        // Logo: priorizar configuración de apps, luego logo del tema
        $logo_id = isset($app_config['app_logo']) ? $app_config['app_logo'] : get_theme_mod('custom_logo');
        $logo_url = '';
        if ($logo_id) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'full');
        }

        // Colores: priorizar configuración de apps, luego del tema
        $primary_color = isset($app_config['primary_color']) ? $app_config['primary_color'] : get_theme_mod('primary_color', '#4CAF50');
        $secondary_color = isset($app_config['secondary_color']) ? $app_config['secondary_color'] : get_theme_mod('secondary_color', '#8BC34A');
        $accent_color = isset($app_config['accent_color']) ? $app_config['accent_color'] : get_theme_mod('accent_color', '#FF9800');

        // Colores ampliados del configurador de app
        $background_color = isset($app_config['background_color']) ? $app_config['background_color'] : get_theme_mod('background_color', '#FFFFFF');
        $surface_color = isset($app_config['surface_color']) ? $app_config['surface_color'] : '#FFFFFF';
        $text_primary_color = isset($app_config['text_primary_color']) ? $app_config['text_primary_color'] : get_theme_mod('text_color', '#212121');
        $text_secondary_color = isset($app_config['text_secondary_color']) ? $app_config['text_secondary_color'] : '#757575';

        return [
            'primary_color' => $primary_color,
            'secondary_color' => $secondary_color,
            'accent_color' => $accent_color,
            'background_color' => $background_color,
            'surface_color' => $surface_color,
            'text_primary_color' => $text_primary_color,
            'text_secondary_color' => $text_secondary_color,
            'logo_url' => $logo_url,
            'favicon_url' => get_site_icon_url(),
        ];
    }

    /**
     * Obtiene configuración de layouts para las apps
     */
    private function get_layout_data() {
        // Obtener configuración de cliente existente (pestañas del calendario)
        $client_config = $this->get_client_tabs_config();
        $app_config = get_option('flavor_apps_config', []);
        $navigation_style = $app_config['navigation_style'] ?? 'auto';
        $hybrid_show_appbar = !empty($app_config['hybrid_show_appbar']);
        $map_provider = $app_config['map_provider'] ?? 'osm';
        $google_maps_api_key = $app_config['google_maps_api_key'] ?? '';
        $drawer_items = $this->get_drawer_items_payload($app_config);
        $use_bottom_nav = $this->should_use_bottom_navigation(null, []);

        // Fallback de navegación a partir de pestañas habilitadas
        $navigation_items = [];
        $order_index = 0;
        foreach ($client_config['tabs'] as $tab) {
            $tab_id = $tab['id'] ?? '';
            if (!$tab_id) {
                continue;
            }
            $navigation_items[] = [
                'id' => $order_index + 1,
                'title' => $tab['label'] ?? $tab_id,
                'url' => '/' . $tab_id,
                'icon' => $tab['icon'] ?? 'home',
                'parent' => 0,
                'order' => $order_index,
            ];
            $order_index++;
        }

        return [
            'available' => true,
            'version' => '1.0',
            'message' => __('config', 'flavor-chat-ia'),
            'menu' => [
                'type' => $use_bottom_nav ? 'bottomNav' : 'classic',
                'name' => $use_bottom_nav ? 'Bottom Navigation' : 'Classic Menu',
                'mobile_behavior' => $use_bottom_nav ? 'bottom_tabs' : 'hamburger',
                'config' => [
                    'use_bottom_navigation' => $use_bottom_nav,
                ],
            ],
            'footer' => [
                'type' => 'compact',
                'name' => 'Compact Footer',
                'config' => [],
            ],
            'navigation' => $navigation_items,
            // Siempre incluir las pestañas del cliente para compatibilidad
            'client_tabs' => $client_config['tabs'],
            'default_tab' => $client_config['default_tab'],
            'features' => $client_config['features'],
            'settings' => [
                'use_bottom_navigation' => $use_bottom_nav,
                'navigation_style' => $navigation_style,
                'hybrid_show_appbar' => $hybrid_show_appbar,
                'map_provider' => $map_provider,
                'google_maps_api_key' => $google_maps_api_key,
                'drawer_items' => $drawer_items,
            ],
        ];
    }

    /**
     * Obtiene items para el menú hamburguesa en modo híbrido
     *
     * Filtro disponible: 'flavor_app_drawer_items'
     * Permite a módulos y plugins añadir items al menú hamburguesa.
     *
     * Ejemplo de uso:
     * add_filter('flavor_app_drawer_items', function($items) {
     *     $items[] = [
     *         'title' => 'Mi Sección',
     *         'icon' => 'star',
     *         'content_type' => 'module',
     *         'content_ref' => 'mi_modulo',
     *         'api_endpoint' => '/wp-json/flavor-chat-ia/v1/modules/mi_modulo',
     *         'order' => 100,
     *         'depth' => 0,
     *     ];
     *     return $items;
     * });
     */
    private function get_drawer_items_payload($app_config) {
        $saved_drawer_items = $app_config['drawer_items'] ?? [];
        $payload = [];

        // Si hay drawer_items guardados directamente (configuración nueva), usarlos
        if (!empty($saved_drawer_items)) {
            foreach ($saved_drawer_items as $index => $item) {
                // Saltar items no habilitados
                if (empty($item['enabled'])) {
                    continue;
                }

                $content_type = $item['content_type'] ?? 'page';
                $content_ref = $item['content_ref'] ?? '';

                // Obtener content_ref del campo correcto según el tipo
                if ($content_type === 'page' && empty($content_ref)) {
                    $content_ref = $item['content_ref_page'] ?? '';
                } elseif ($content_type === 'cpt' && empty($content_ref)) {
                    $content_ref = $item['content_ref_cpt'] ?? '';
                } elseif ($content_type === 'module' && empty($content_ref)) {
                    $content_ref = $item['content_ref_module'] ?? '';
                }

                // Generar api_endpoint
                $api_endpoint = '';
                if ($content_ref) {
                    $api_endpoint = $this->generate_api_endpoint_for_tab($content_type, $content_ref);
                }

                $payload[] = [
                    'title' => $item['title'] ?? 'Item',
                    'icon' => $item['icon'] ?? 'public',
                    'content_type' => $content_type,
                    'content_ref' => $content_ref,
                    'api_endpoint' => $api_endpoint,
                    'order' => isset($item['order']) ? intval($item['order']) : $index,
                    'depth' => 0,
                ];
            }
        } else {
            // Fallback: usar menú de WordPress si no hay drawer_items guardados
            $menu_items = $this->get_menu_items_for_source($app_config['web_sections_menu'] ?? '');

            foreach ($menu_items as $index => $item) {
                $url = $item['url'] ?? '';
                if (!$url) continue;

                // Procesar contenido para renderizado nativo
                $content_type = 'page';
                $content_ref = '';
                $api_endpoint = '';

                // Intentar inferir content_ref de la URL
                $converted = $this->convert_web_url_to_native($url);
                if ($converted) {
                    $content_type = $converted['content_type'];
                    $content_ref = $converted['content_ref'];
                    $api_endpoint = $this->generate_api_endpoint_for_tab($content_type, $content_ref);
                }

                $payload[] = [
                    'title' => $item['title'] ?? $url,
                    'icon' => 'public',
                    'content_type' => $content_type,
                    'content_ref' => $content_ref,
                    'api_endpoint' => $api_endpoint,
                    'order' => $item['order'] ?? $index,
                    'depth' => $item['depth'] ?? 0,
                ];
            }
        }

        /**
         * Filtro para añadir/modificar items del menú hamburguesa (drawer) de la app móvil.
         *
         * @param array $payload Items actuales del drawer
         * @param array $app_config Configuración completa de la app
         * @return array Items modificados
         */
        $payload = apply_filters('flavor_app_drawer_items', $payload, $app_config);

        // Ordenar por order después del filtro
        usort($payload, function($a, $b) {
            return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
        });

        return $payload;
    }

    /**
     * Resuelve items de menú según fuente seleccionada
     */
    private function get_menu_items_for_source($menu_source) {
        $locations = get_nav_menu_locations();
        $menus = wp_get_nav_menus();
        $menu_id = null;

        if ($menu_source && str_starts_with($menu_source, 'location:')) {
            $location = substr($menu_source, strlen('location:'));
            $menu_id = $locations[$location] ?? null;
        } elseif ($menu_source && str_starts_with($menu_source, 'menu:')) {
            $menu_id = intval(substr($menu_source, strlen('menu:')));
        }

        if (!$menu_id) {
            $menu_id = $locations['primary'] ?? $locations['menu-1'] ?? null;
        }
        if (!$menu_id && !empty($menus)) {
            $menu_id = $menus[0]->term_id;
        }

        $payload = [];
        if ($menu_id) {
            $menu_items = wp_get_nav_menu_items($menu_id);
            if (!empty($menu_items)) {
                $indexed = [];
                foreach ($menu_items as $menu_item) {
                    $indexed[$menu_item->ID] = $menu_item;
                }
                foreach ($menu_items as $menu_item) {
                    $title = $menu_item->title ?? '';
                    $url = $menu_item->url ?? '';
                    if (!$url) continue;
                    $depth = 0;
                    $parent = $menu_item->menu_item_parent ?? 0;
                    while ($parent && isset($indexed[$parent])) {
                        $depth++;
                        $parent = $indexed[$parent]->menu_item_parent ?? 0;
                        if ($depth > 10) break;
                    }
                    $prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
                    $payload[] = [
                        'title' => $prefix . $title,
                        'url' => $url,
                        'order' => isset($menu_item->menu_order) ? intval($menu_item->menu_order) : 0,
                        'depth' => $depth,
                    ];
                }
            }
        }

        return $payload;
    }

    /**
     * Determina si usar bottom navigation basado en la configuración
     */
    private function should_use_bottom_navigation($menu_data, $layout_settings) {
        $app_config = get_option('flavor_apps_config', []);
        $navigation_style = isset($app_config['navigation_style']) ? $app_config['navigation_style'] : 'auto';

        if ($navigation_style === 'bottom') {
            return true;
        }
        if ($navigation_style === 'hamburger') {
            return false;
        }
        if ($navigation_style === 'hybrid') {
            return true;
        }

        if (!empty($menu_data) && !empty($menu_data['mobile_behavior'])) {
            $menu_behavior = $menu_data['mobile_behavior'];
            if (in_array($menu_behavior, ['hamburger', 'sidebar'], true)) {
                return false;
            }
            if (in_array($menu_behavior, ['bottom-nav', 'bottom_tabs'], true)) {
                return true;
            }
        }

        // Si el menú es bottomNav, usar bottom navigation
        if ($menu_data && isset($menu_data['id']) && $menu_data['id'] === 'bottomNav') {
            return true;
        }

        // Si hay configuración explícita en layout_settings
        if (isset($layout_settings['use_bottom_navigation'])) {
            return (bool) $layout_settings['use_bottom_navigation'];
        }

        // Por defecto para móviles: bottom navigation
        return true;
    }

    /**
     * Obtiene la configuración de pestañas del cliente (compatible con calendario)
     */
    private function get_client_tabs_config() {
        // Priorizar configuración nueva (flavor_apps_config) sobre formato legacy (chat_ia_settings)
        $app_config = get_option('flavor_apps_config', []);

        // Pestañas por defecto (compatibles con el sistema de reservas)
        $default_tabs = [
            ['id' => 'chat', 'label' => 'Chat', 'icon' => 'chat_bubble', 'enabled' => true, 'order' => 0, 'content_type' => 'native_screen', 'content_ref' => 'chat'],
            ['id' => 'reservations', 'label' => 'Reservar', 'icon' => 'calendar_today', 'enabled' => true, 'order' => 1, 'content_type' => 'native_screen', 'content_ref' => 'reservations'],
            ['id' => 'my_tickets', 'label' => 'Mis Tickets', 'icon' => 'confirmation_number', 'enabled' => true, 'order' => 2, 'content_type' => 'native_screen', 'content_ref' => 'my_tickets'],
            ['id' => 'info', 'label' => 'Info', 'icon' => 'info', 'enabled' => true, 'order' => 3, 'content_type' => 'native_screen', 'content_ref' => 'info'],
            ['id' => 'modules', 'label' => 'Módulos', 'icon' => 'extension', 'enabled' => false, 'order' => 4, 'content_type' => 'native_screen', 'content_ref' => 'modules'],
            ['id' => 'grupos_consumo', 'label' => 'Grupos Consumo', 'icon' => 'groups', 'enabled' => false, 'order' => 5, 'content_type' => 'module', 'content_ref' => 'grupos_consumo'],
            ['id' => 'banco_tiempo', 'label' => 'Banco de Tiempo', 'icon' => 'handyman', 'enabled' => false, 'order' => 6, 'content_type' => 'module', 'content_ref' => 'banco_tiempo'],
            ['id' => 'marketplace', 'label' => 'Marketplace', 'icon' => 'store', 'enabled' => false, 'order' => 7, 'content_type' => 'module', 'content_ref' => 'marketplace'],
        ];

        // Si hay tabs configurados en flavor_apps_config, usarlos
        if (!empty($app_config['tabs']) && is_array($app_config['tabs'])) {
            $tabs = $app_config['tabs'];
            // Ordenar por campo 'order'
            usort($tabs, function($a, $b) {
                return ($a['order'] ?? 0) - ($b['order'] ?? 0);
            });
            // Filtrar solo los habilitados para la respuesta API
            $tabs_enabled = array_values(array_filter($tabs, function($tab) {
                return !empty($tab['enabled']);
            }));

            // Procesar cada tab para generar formato de API nativo
            foreach ($tabs_enabled as &$tab) {
                $tab = $this->process_tab_for_native_api($tab);
            }
            unset($tab);
            $default_tab = $app_config['default_tab'] ?? 'info';
        } else {
            // Fallback: formato legacy en chat_ia_settings
            $settings = get_option('chat_ia_settings', []);
            $mobile_config = $settings['mobile_apps'] ?? [];
            $client_config = $mobile_config['client_config'] ?? [];

            $tabs_enabled = $client_config['tabs'] ?? $default_tabs;
            $default_tab = $client_config['default_tab'] ?? 'info';

            // Procesar tabs legacy para incluir api_endpoint
            foreach ($tabs_enabled as &$tab) {
                $tab = $this->process_tab_for_native_api($tab);
            }
            unset($tab);
        }

        // Funcionalidades habilitadas (merge de ambas fuentes)
        $settings = $settings ?? get_option('chat_ia_settings', []);
        $mobile_config = $mobile_config ?? ($settings['mobile_apps'] ?? []);
        $client_config = $client_config ?? ($mobile_config['client_config'] ?? []);

        // Determinar features desde módulos configurados o formato legacy
        $modules_config = $app_config['modules'] ?? [];
        $features = [
            'chat_enabled' => !empty($modules_config['chat']['enabled']) || ($client_config['chat_enabled'] ?? true),
            'reservations_enabled' => !empty($modules_config['reservas']['enabled']) || ($client_config['reservations_enabled'] ?? true),
            'my_tickets_enabled' => !empty($modules_config['tickets']['enabled']) || ($client_config['my_tickets_enabled'] ?? true),
            'offline_tickets' => $client_config['offline_tickets'] ?? true,
            'push_notifications' => !empty($modules_config['notificaciones']['enabled']) || ($client_config['push_notifications'] ?? false),
            'biometric_auth' => $client_config['biometric_auth'] ?? false,
            'native_content_api' => true, // Nueva característica: todo se renderiza nativo
        ];

        return [
            'tabs' => $tabs_enabled,
            'default_tab' => $default_tab,
            'features' => $features,
            'native_content_base_url' => rest_url('native-content/v1'),
        ];
    }

    /**
     * Procesa un tab para generar el formato de API nativa
     * Genera el api_endpoint según el content_type
     *
     * @param array $tab Configuración del tab
     * @return array Tab procesado con api_endpoint
     */
    private function process_tab_for_native_api($tab) {
        // Determinar content_type (nuevo sistema vs legacy)
        $content_type = $tab['content_type'] ?? null;

        // Si no hay content_type nuevo, inferir del formato legacy
        if (!$content_type) {
            $legacy_type = $tab['type'] ?? 'native';
            if ($legacy_type === 'web') {
                // Intentar convertir URL a contenido nativo si es posible
                $url = $tab['url'] ?? '';
                $converted = $this->convert_web_url_to_native($url);
                if ($converted) {
                    $content_type = $converted['content_type'];
                    $tab['content_ref'] = $converted['content_ref'];
                } else {
                    // Mantener como web (fallback, aunque preferimos evitarlo)
                    $content_type = 'web_fallback';
                    $tab['web_url'] = $url;
                }
            } else {
                // Tab nativo legacy: usar el ID como referencia
                $content_type = 'native_screen';
                $tab['content_ref'] = $tab['content_ref'] ?? $tab['id'];
            }
        }

        // Obtener content_ref del campo correcto según el tipo
        $content_ref = $tab['content_ref'] ?? '';
        if ($content_type === 'page') {
            $content_ref = $tab['content_ref_page'] ?? $tab['content_ref'] ?? '';
        } elseif ($content_type === 'cpt') {
            $content_ref = $tab['content_ref_cpt'] ?? $tab['content_ref'] ?? '';
        } elseif ($content_type === 'module') {
            $content_ref = $tab['content_ref_module'] ?? $tab['content_ref'] ?? '';
        }

        $tab['content_type'] = $content_type;
        $tab['content_ref'] = $content_ref;

        // Generar api_endpoint según el content_type
        $tab['api_endpoint'] = $this->generate_api_endpoint_for_tab($content_type, $content_ref);

        // Limpiar campos legacy que ya no son necesarios
        unset($tab['type'], $tab['url'], $tab['content_ref_page'], $tab['content_ref_cpt'], $tab['content_ref_module']);

        return $tab;
    }

    /**
     * Convierte una URL web a referencia de contenido nativo si es posible
     *
     * @param string $url URL a convertir
     * @return array|null ['content_type' => ..., 'content_ref' => ...] o null si no se puede convertir
     */
    private function convert_web_url_to_native($url) {
        if (empty($url)) {
            return null;
        }

        // Obtener path relativo
        $site_url = trailingslashit(get_site_url());
        $path = str_replace($site_url, '', $url);
        $path = trim($path, '/');

        if (empty($path)) {
            // Es la home
            return ['content_type' => 'native_screen', 'content_ref' => 'info'];
        }

        // Verificar si es una página
        $page = get_page_by_path($path);
        if ($page && $page->post_status === 'publish') {
            return ['content_type' => 'page', 'content_ref' => $page->post_name];
        }

        // Verificar si es un CPT
        $cpts = get_post_types(['public' => true, '_builtin' => false], 'names');
        foreach ($cpts as $cpt) {
            if (strpos($path, $cpt) === 0) {
                return ['content_type' => 'cpt', 'content_ref' => $cpt];
            }
        }

        return null; // No se pudo convertir
    }

    /**
     * Genera el endpoint de API para un tab según su tipo de contenido
     *
     * @param string $content_type Tipo de contenido
     * @param string $content_ref Referencia del contenido
     * @return string URL del endpoint de API
     */
    private function generate_api_endpoint_for_tab($content_type, $content_ref) {
        $base_url = rest_url('native-content/v1');

        switch ($content_type) {
            case 'native_screen':
                // Pantallas nativas del sistema (info, chat, reservations, etc.)
                return $base_url . '/screen/' . sanitize_key($content_ref);

            case 'page':
                // Página de WordPress
                return $base_url . '/content/page/' . sanitize_title($content_ref);

            case 'cpt':
                // Custom Post Type (listado)
                return $base_url . '/content/list/' . sanitize_key($content_ref);

            case 'module':
                // Módulo del plugin
                return $base_url . '/module/' . sanitize_key($content_ref);

            case 'web_fallback':
                // Fallback a WebView (evitar si es posible)
                return null;

            default:
                return $base_url . '/screen/info';
        }
    }

    /**
     * Formatea menú para uso en apps móviles
     */
    private function format_menu_for_mobile($menu, $use_bottom_nav = true) {
        if (!$menu) {
            return [
                'type' => $use_bottom_nav ? 'bottomNav' : 'classic',
                'name' => $use_bottom_nav ? 'Bottom Navigation' : 'Classic Menu',
                'mobile_behavior' => $use_bottom_nav ? 'bottom_tabs' : 'hamburger',
                'config' => [
                    'use_bottom_navigation' => $use_bottom_nav,
                ],
            ];
        }

        return [
            'type' => $menu['id'] ?? 'classic',
            'name' => $menu['name'] ?? 'Classic Menu',
            'mobile_behavior' => $use_bottom_nav ? 'bottom_tabs' : ($menu['mobile_behavior'] ?? 'hamburger'),
            'config' => array_merge($menu['settings'] ?? [], [
                'use_bottom_navigation' => $use_bottom_nav,
            ]),
        ];
    }

    /**
     * Formatea footer para uso en apps móviles
     */
    private function format_footer_for_mobile($footer) {
        if (!$footer) {
            return [
                'type' => 'compact',
                'config' => [],
            ];
        }

        return [
            'type' => $footer['id'] ?? 'compact',
            'name' => $footer['name'] ?? 'Compact Footer',
            'config' => $footer['settings'] ?? [],
        ];
    }

    /**
     * Obtiene items de navegación del menú
     */
    private function get_navigation_items($location = 'primary') {
        $menu_locations = get_nav_menu_locations();
        $items = [];

        $menu_id = $menu_locations[$location] ?? null;
        if (!$menu_id) {
            $fallback_location = $menu_locations['menu-1'] ?? null;
            $menu_id = $fallback_location;
        }
        if (!$menu_id) {
            $menus = wp_get_nav_menus();
            if (!empty($menus)) {
                $menu_id = $menus[0]->term_id ?? null;
            }
        }

        if ($menu_id) {
            $menu_items = wp_get_nav_menu_items($menu_id);
            foreach ($menu_items as $item) {
                $items[] = [
                    'id' => $item->ID,
                    'title' => $item->title,
                    'url' => $item->url,
                    'icon' => get_post_meta($item->ID, '_menu_item_icon', true) ?: 'link',
                    'parent' => (int) $item->menu_item_parent,
                    'order' => $item->menu_order,
                ];
            }
        }

        return $items;
    }

    /**
     * Detecta colores dominantes de una imagen
     */
    private function detect_colors_from_image($image_url) {
        // Implementación básica
        // En producción, usar una librería de análisis de color
        return null;
    }

    /**
     * Obtiene icono de módulo
     */
    private function get_module_icon($module_id) {
        $icons = [
            // Módulos principales
            'grupos_consumo' => 'shopping_basket',
            'banco_tiempo' => 'schedule',
            'marketplace' => 'store',
            'woocommerce' => 'shopping_cart',
            // Nuevos módulos frontend
            'grupos-consumo' => 'shopping_basket',
            'banco-tiempo' => 'access_time',
            'ayuntamiento' => 'account_balance',
            'comunidades' => 'groups',
            'espacios-comunes' => 'meeting_room',
            'ayuda-vecinal' => 'volunteer_activism',
            'huertos-urbanos' => 'grass',
            'biblioteca' => 'local_library',
            'cursos' => 'school',
            'podcast' => 'podcasts',
            'radio' => 'radio',
            'bicicletas' => 'pedal_bike',
            'reciclaje' => 'recycling',
            'tienda-local' => 'storefront',
            'incidencias' => 'report_problem',
        ];

        return $icons[$module_id] ?? 'extension';
    }

    /**
     * Obtiene color de módulo
     */
    private function get_module_color($module_id) {
        $colors = [
            // Módulos principales
            'grupos_consumo' => '#46b450',
            'banco_tiempo' => '#9b59b6',
            'marketplace' => '#e91e63',
            'woocommerce' => '#96588a',
            // Nuevos módulos frontend
            'grupos-consumo' => '#84cc16',
            'banco-tiempo' => '#8b5cf6',
            'ayuntamiento' => '#1d4ed8',
            'comunidades' => '#f43f5e',
            'espacios-comunes' => '#06b6d4',
            'ayuda-vecinal' => '#f97316',
            'huertos-urbanos' => '#22c55e',
            'biblioteca' => '#6366f1',
            'cursos' => '#a855f7',
            'podcast' => '#14b8a6',
            'radio' => '#ef4444',
            'bicicletas' => '#a3e635',
            'reciclaje' => '#10b981',
            'tienda-local' => '#f59e0b',
            'incidencias' => '#e11d48',
        ];

        return $colors[$module_id] ?? '#666666';
    }

    /**
     * Obtiene configuración específica de módulo
     */
    private function get_module_config($module_id) {
        // Configuración específica por módulo para las apps
        $configs = [
            'grupos_consumo' => [
                'allow_orders_from_app' => true,
                'show_progress_bar' => true,
                'enable_notifications' => true,
                'cache_duration' => 3600,
            ],
            'grupos-consumo' => [
                'allow_orders_from_app' => true,
                'show_progress_bar' => true,
                'enable_notifications' => true,
                'cache_duration' => 3600,
                'api_base' => home_url('/wp-json/flavor/v1/grupos-consumo'),
            ],
            'banco_tiempo' => [
                'allow_create_services' => true,
                'show_balance' => true,
                'enable_transactions' => true,
            ],
            'banco-tiempo' => [
                'allow_create_services' => true,
                'show_balance' => true,
                'enable_transactions' => true,
                'api_base' => home_url('/wp-json/flavor/v1/banco-tiempo'),
            ],
            'marketplace' => [
                'allow_create_ads' => true,
                'show_categories' => true,
                'enable_chat' => true,
            ],
            'woocommerce' => [
                'show_products' => true,
                'enable_cart' => true,
                'checkout_url' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '',
            ],
            'ayuntamiento' => [
                'show_tramites' => true,
                'show_noticias' => true,
                'enable_cita_previa' => true,
                'enable_notifications' => true,
                'api_base' => home_url('/wp-json/flavor/v1/ayuntamiento'),
            ],
            'comunidades' => [
                'allow_create_community' => true,
                'show_events' => true,
                'enable_posts' => true,
                'enable_notifications' => true,
                'api_base' => home_url('/wp-json/flavor/v1/comunidades'),
            ],
            'espacios-comunes' => [
                'allow_reservations' => true,
                'show_calendar' => true,
                'api_base' => home_url('/wp-json/flavor/v1/espacios-comunes'),
            ],
            'ayuda-vecinal' => [
                'allow_create_request' => true,
                'show_map' => true,
                'api_base' => home_url('/wp-json/flavor/v1/ayuda-vecinal'),
            ],
            'huertos-urbanos' => [
                'show_parcelas' => true,
                'enable_reservations' => true,
                'api_base' => home_url('/wp-json/flavor/v1/huertos-urbanos'),
            ],
            'biblioteca' => [
                'allow_loans' => true,
                'show_catalog' => true,
                'api_base' => home_url('/wp-json/flavor/v1/biblioteca'),
            ],
            'cursos' => [
                'allow_enrollment' => true,
                'show_calendar' => true,
                'api_base' => home_url('/wp-json/flavor/v1/cursos'),
            ],
            'podcast' => [
                'enable_player' => true,
                'show_episodes' => true,
                'api_base' => home_url('/wp-json/flavor/v1/podcast'),
            ],
            'radio' => [
                'enable_streaming' => true,
                'show_schedule' => true,
                'api_base' => home_url('/wp-json/flavor/v1/radio'),
            ],
            'bicicletas' => [
                'allow_reservations' => true,
                'show_map' => true,
                'api_base' => home_url('/wp-json/flavor/v1/bicicletas'),
            ],
            'reciclaje' => [
                'show_points' => true,
                'enable_gamification' => true,
                'api_base' => home_url('/wp-json/flavor/v1/reciclaje'),
            ],
            'tienda-local' => [
                'show_products' => true,
                'show_stores' => true,
                'api_base' => home_url('/wp-json/flavor/v1/tienda-local'),
            ],
            'incidencias' => [
                'allow_reports' => true,
                'show_map' => true,
                'enable_notifications' => true,
                'api_base' => home_url('/wp-json/flavor/v1/incidencias'),
            ],
        ];

        return $configs[$module_id] ?? [];
    }

    /**
     * Obtiene datos de CPTs configurados para la app
     *
     * @return array
     */
    private function get_configured_cpts_data() {
        if (!class_exists('Flavor_App_CPT_Manager')) {
            return [
                'available' => false,
                'cpts' => [],
            ];
        }

        $cpt_manager = Flavor_App_CPT_Manager::get_instance();
        $config = get_option('flavor_app_cpts_config', []);

        $cpts = [];
        foreach ($config as $cpt_name => $cpt_config) {
            if (!$cpt_config['enabled'] || !post_type_exists($cpt_name)) {
                continue;
            }

            $post_type = get_post_type_object($cpt_name);
            if (!$post_type) {
                continue;
            }

            $cpts[] = [
                'id' => $cpt_name,
                'name' => $cpt_config['app_name'] ?: $post_type->label,
                'description' => $cpt_config['description'] ?: $post_type->description,
                'icon' => $cpt_config['icon'],
                'color' => $cpt_config['color'],
                'order' => $cpt_config['order'],
                'show_in_navigation' => $cpt_config['show_in_navigation'],
                'endpoint' => rest_url('app-discovery/v1/cpt/' . $cpt_name),
                'total_posts' => wp_count_posts($cpt_name)->publish,
            ];
        }

        // Ordenar por orden configurado
        usort($cpts, function($a, $b) {
            return $a['order'] - $b['order'];
        });

        return [
            'available' => true,
            'endpoint' => rest_url('app-discovery/v1/custom-post-types'),
            'cpts' => $cpts,
            'total' => count($cpts),
        ];
    }
}

// Inicializar integración
add_action('plugins_loaded', function() {
    if (class_exists('Flavor_Chat_IA')) {
        Flavor_App_Integration::get_instance();
    }
}, 20);
