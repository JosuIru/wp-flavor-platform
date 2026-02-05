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
            'permission_callback' => '__return_true',
        ]);

        // Endpoint de módulos disponibles
        register_rest_route('app-discovery/v1', '/modules', [
            'methods' => 'GET',
            'callback' => [$this, 'get_available_modules'],
            'permission_callback' => '__return_true',
        ]);

        // Endpoint de configuración de tema
        register_rest_route('app-discovery/v1', '/theme', [
            'methods' => 'GET',
            'callback' => [$this, 'get_theme_config'],
            'permission_callback' => '__return_true',
        ]);

        // Endpoint de layouts para apps
        register_rest_route('app-discovery/v1', '/layouts', [
            'methods' => 'GET',
            'callback' => [$this, 'get_layouts_config'],
            'permission_callback' => '__return_true',
        ]);
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
            'permission_callback' => '__return_true',
        ]);

        // Ruta unificada para información del sitio
        register_rest_route('unified-api/v1', '/site-info', [
            'methods' => 'GET',
            'callback' => [$this->api_adapter, 'unified_site_info'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * GET /app-discovery/v1/info
     * Devuelve información completa del sistema
     */
    public function get_system_info($request) {
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
        ];

        return new WP_REST_Response($response, 200);
    }

    /**
     * GET /app-discovery/v1/modules
     * Devuelve módulos disponibles con configuración
     */
    public function get_available_modules($request) {
        $modules = [];

        // Módulos de Flavor Chat IA (backend)
        if ($this->plugin_detector->is_flavor_chat_active()) {
            if (class_exists('Flavor_Chat_Module_Loader')) {
                $loader = Flavor_Chat_Module_Loader::get_instance();
                $flavor_modules = $loader->get_loaded_modules();

                foreach ($flavor_modules as $module_id => $module) {
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

        return new WP_REST_Response([
            'success' => true,
            'modules' => $modules,
            'total' => count($modules),
        ], 200);
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

        // Verificar si el sistema de layouts está disponible
        if (!function_exists('flavor_layout_registry')) {
            // Sin sistema de layouts: usar configuración por defecto compatible con calendario
            return [
                'available' => false,
                'message' => 'Layout system not enabled',
                // Incluir configuración de navegación por defecto para las apps
                'menu' => [
                    'type' => 'bottomNav',
                    'name' => 'Bottom Navigation',
                    'mobile_behavior' => 'bottom_tabs',
                    'config' => [
                        'use_bottom_navigation' => true,
                    ],
                ],
                'footer' => [
                    'type' => 'compact',
                    'name' => 'Compact Footer',
                    'config' => [],
                ],
                // Navegación basada en pestañas del calendario
                'client_tabs' => $client_config['tabs'],
                'default_tab' => $client_config['default_tab'],
                'features' => $client_config['features'],
            ];
        }

        $registry = flavor_layout_registry();
        $active_layout = $registry->get_active_layout();
        $layout_settings = get_option('flavor_layout_settings', []);

        // Obtener items de navegación del menú principal de WordPress
        $navigation_items = $this->get_navigation_items('primary');

        // Determinar si usar bottom navigation o hamburger menu
        $menu_data = $registry->get_menu($active_layout['menu']);
        $use_bottom_nav = $this->should_use_bottom_navigation($menu_data, $layout_settings);

        return [
            'available' => true,
            'version' => '1.0',
            'active_menu' => $active_layout['menu'],
            'active_footer' => $active_layout['footer'],
            'menu' => $this->format_menu_for_mobile($menu_data, $use_bottom_nav),
            'footer' => $this->format_footer_for_mobile($registry->get_footer($active_layout['footer'])),
            'navigation' => $navigation_items,
            // Siempre incluir las pestañas del cliente para compatibilidad
            'client_tabs' => $client_config['tabs'],
            'default_tab' => $client_config['default_tab'],
            'features' => $client_config['features'],
            'components' => [
                'dark_mode' => [
                    'enabled' => !empty($layout_settings['enable_dark_mode']),
                    'auto' => !empty($layout_settings['dark_mode_auto']),
                ],
                'back_to_top' => !empty($layout_settings['enable_back_to_top']),
                'cookie_banner' => !empty($layout_settings['enable_cookie_banner']),
                'announcement_bar' => !empty($layout_settings['enable_announcement_bar']),
            ],
            'settings' => [
                'sticky_header' => !empty($layout_settings['sticky_header']),
                'show_search' => !empty($layout_settings['show_search']),
                'show_cart' => !empty($layout_settings['show_cart']),
                'use_bottom_navigation' => $use_bottom_nav,
            ],
        ];
    }

    /**
     * Determina si usar bottom navigation basado en la configuración
     */
    private function should_use_bottom_navigation($menu_data, $layout_settings) {
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
            ['id' => 'chat', 'label' => 'Chat', 'icon' => 'chat_bubble', 'enabled' => true, 'order' => 0],
            ['id' => 'reservations', 'label' => 'Reservar', 'icon' => 'calendar_today', 'enabled' => true, 'order' => 1],
            ['id' => 'my_tickets', 'label' => 'Mis Tickets', 'icon' => 'confirmation_number', 'enabled' => true, 'order' => 2],
            ['id' => 'info', 'label' => 'Info', 'icon' => 'info', 'enabled' => true, 'order' => 3],
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
            $default_tab = $app_config['default_tab'] ?? 'info';
        } else {
            // Fallback: formato legacy en chat_ia_settings
            $settings = get_option('chat_ia_settings', []);
            $mobile_config = $settings['mobile_apps'] ?? [];
            $client_config = $mobile_config['client_config'] ?? [];

            $tabs_enabled = $client_config['tabs'] ?? $default_tabs;
            $default_tab = $client_config['default_tab'] ?? 'info';
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
        ];

        return [
            'tabs' => $tabs_enabled,
            'default_tab' => $default_tab,
            'features' => $features,
        ];
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

        if (isset($menu_locations[$location])) {
            $menu_object = wp_get_nav_menu_object($menu_locations[$location]);

            if ($menu_object) {
                $menu_items = wp_get_nav_menu_items($menu_object->term_id);

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
