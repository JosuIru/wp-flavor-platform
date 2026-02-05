<?php
/**
 * Sistema de Directorio de Negocios
 *
 * Permite que las apps descubran y se conecten a diferentes
 * negocios/comunidades que tengan los plugins instalados
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gestiona el directorio público de negocios
 */
class Flavor_Business_Directory {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * URL del servidor central de directorio
     */
    const DIRECTORY_SERVER = 'https://directory.flavorapps.com'; // Cambiar a tu servidor

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
        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // Registrar endpoints
        add_action('rest_api_init', [$this, 'register_endpoints']);

        // Sincronizar con servidor central cada hora
        add_action('flavor_sync_directory', [$this, 'sync_with_central_server']);
        if (!wp_next_scheduled('flavor_sync_directory')) {
            wp_schedule_event(time(), 'hourly', 'flavor_sync_directory');
        }

        // Limpiar evento al desactivar
        register_deactivation_hook(__FILE__, [$this, 'clear_scheduled_events']);
    }

    /**
     * Registra endpoints REST
     */
    public function register_endpoints() {
        // Listar negocios disponibles (público)
        register_rest_route('app-discovery/v1', '/businesses', [
            'methods' => 'GET',
            'callback' => [$this, 'get_public_businesses'],
            'permission_callback' => '__return_true',
            'args' => [
                'region' => [
                    'description' => 'Filtrar por región',
                    'type' => 'string',
                    'required' => false,
                ],
                'category' => [
                    'description' => 'Filtrar por categoría',
                    'type' => 'string',
                    'required' => false,
                ],
                'search' => [
                    'description' => 'Buscar por nombre',
                    'type' => 'string',
                    'required' => false,
                ],
                'limit' => [
                    'description' => 'Límite de resultados',
                    'type' => 'integer',
                    'default' => 50,
                ],
            ],
        ]);

        // Registrar este negocio en el directorio
        register_rest_route('app-discovery/v1', '/businesses/register', [
            'methods' => 'POST',
            'callback' => [$this, 'register_business'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        // Verificar un negocio específico
        register_rest_route('app-discovery/v1', '/businesses/verify', [
            'methods' => 'POST',
            'callback' => [$this, 'verify_business'],
            'permission_callback' => '__return_true',
            'args' => [
                'url' => [
                    'description' => 'URL del negocio a verificar',
                    'type' => 'string',
                    'required' => true,
                    'validate_callback' => function($param) {
                        return filter_var($param, FILTER_VALIDATE_URL);
                    },
                ],
            ],
        ]);
    }

    /**
     * GET /app-discovery/v1/businesses
     * Lista negocios públicos disponibles
     */
    public function get_public_businesses($request) {
        $region = $request->get_param('region');
        $category = $request->get_param('category');
        $search = $request->get_param('search');
        $limit = $request->get_param('limit');

        // Obtener negocios cacheados
        $businesses = $this->get_cached_businesses();

        // Aplicar filtros
        if ($region) {
            $businesses = array_filter($businesses, function($business) use ($region) {
                return isset($business['region']) && $business['region'] === $region;
            });
        }

        if ($category) {
            $businesses = array_filter($businesses, function($business) use ($category) {
                return isset($business['category']) && $business['category'] === $category;
            });
        }

        if ($search) {
            $search = strtolower($search);
            $businesses = array_filter($businesses, function($business) use ($search) {
                $name = strtolower($business['name']);
                $description = strtolower($business['description'] ?? '');
                return strpos($name, $search) !== false || strpos($description, $search) !== false;
            });
        }

        // Limitar resultados
        $businesses = array_slice($businesses, 0, $limit);

        return new WP_REST_Response([
            'success' => true,
            'businesses' => array_values($businesses),
            'total' => count($businesses),
            'regions' => $this->get_available_regions(),
            'categories' => $this->get_available_categories(),
        ], 200);
    }

    /**
     * POST /app-discovery/v1/businesses/register
     * Registra este negocio en el directorio
     */
    public function register_business($request) {
        $config = get_option('flavor_apps_config', []);

        // Verificar que el negocio esté configurado para ser público
        if (!isset($config['public_in_directory']) || !$config['public_in_directory']) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Este negocio no está configurado como público en el directorio', 'flavor-chat-ia'),
            ], 400);
        }

        $business_data = $this->get_current_business_data();

        // Registrar en servidor central (si existe)
        $registered = $this->register_in_central_server($business_data);

        if ($registered) {
            update_option('flavor_business_registered', true);
            update_option('flavor_business_last_sync', time());

            return new WP_REST_Response([
                'success' => true,
                'message' => __('Negocio registrado exitosamente en el directorio', 'flavor-chat-ia'),
                'business' => $business_data,
            ], 200);
        } else {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Error al registrar en el servidor central', 'flavor-chat-ia'),
            ], 500);
        }
    }

    /**
     * POST /app-discovery/v1/businesses/verify
     * Verifica si una URL tiene el plugin instalado
     */
    public function verify_business($request) {
        $url = $request->get_param('url');

        // Limpiar URL
        $url = untrailingslashit($url);

        // Intentar conectar con el endpoint de info
        $info_url = $url . '/wp-json/app-discovery/v1/info';

        $response = wp_remote_get($info_url, [
            'timeout' => 10,
            'sslverify' => true,
        ]);

        if (is_wp_error($response)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('No se pudo conectar con el negocio', 'flavor-chat-ia'),
                'error' => $response->get_error_message(),
            ], 400);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['active_systems'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('El sitio no tiene el plugin instalado', 'flavor-chat-ia'),
            ], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Negocio verificado correctamente', 'flavor-chat-ia'),
            'business' => [
                'url' => $url,
                'name' => $data['app_name'] ?? $data['site_name'] ?? '',
                'description' => $data['app_description'] ?? $data['site_description'] ?? '',
                'systems' => $data['active_systems'] ?? [],
            ],
        ], 200);
    }

    /**
     * Obtiene datos del negocio actual
     */
    private function get_current_business_data() {
        $config = get_option('flavor_apps_config', []);
        $detector = new Flavor_Plugin_Detector();
        $systems = $detector->detect_active_systems();

        // Obtener módulos activos
        $modules = [];
        if ($detector->is_flavor_chat_active()) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            $active_modules = $loader->get_loaded_modules();
            foreach ($active_modules as $module_id => $module) {
                $modules[] = $module_id;
            }
        }

        return [
            'url' => get_site_url(),
            'name' => isset($config['app_name']) ? $config['app_name'] : get_bloginfo('name'),
            'description' => isset($config['app_description']) ? $config['app_description'] : get_bloginfo('description'),
            'logo' => $this->get_logo_url(),
            'region' => isset($config['business_region']) ? $config['business_region'] : '',
            'category' => isset($config['business_category']) ? $config['business_category'] : '',
            'systems' => array_column($systems, 'id'),
            'modules' => $modules,
            'language' => get_locale(),
            'timezone' => wp_timezone_string(),
            'last_updated' => current_time('c'),
        ];
    }

    /**
     * Obtiene URL del logo
     */
    private function get_logo_url() {
        $config = get_option('flavor_apps_config', []);
        $logo_id = isset($config['app_logo']) ? $config['app_logo'] : get_theme_mod('custom_logo');

        if ($logo_id) {
            return wp_get_attachment_image_url($logo_id, 'medium');
        }

        return get_site_icon_url();
    }

    /**
     * Obtiene negocios cacheados
     */
    private function get_cached_businesses() {
        $cached = get_transient('flavor_businesses_cache');

        if ($cached === false) {
            // Si no hay caché, obtener del servidor central
            $this->sync_with_central_server();
            $cached = get_transient('flavor_businesses_cache');

            if ($cached === false) {
                // Si aún no hay datos, devolver array vacío
                return [];
            }
        }

        return $cached;
    }

    /**
     * Sincroniza con servidor central
     */
    public function sync_with_central_server() {
        // Por ahora, usar método local
        // En producción, esto consultaría un servidor central

        $businesses = [];

        // Incluir este negocio si está configurado como público
        $config = get_option('flavor_apps_config', []);
        if (isset($config['public_in_directory']) && $config['public_in_directory']) {
            $businesses[] = $this->get_current_business_data();
        }

        // TODO: Consultar servidor central real
        // $response = wp_remote_get(self::DIRECTORY_SERVER . '/api/businesses');

        // Cachear por 1 hora
        set_transient('flavor_businesses_cache', $businesses, HOUR_IN_SECONDS);

        return $businesses;
    }

    /**
     * Registra en servidor central
     */
    private function register_in_central_server($business_data) {
        // TODO: Implementar registro en servidor central real
        // Por ahora, solo simular éxito

        // $response = wp_remote_post(self::DIRECTORY_SERVER . '/api/businesses/register', [
        //     'body' => json_encode($business_data),
        //     'headers' => ['Content-Type' => 'application/json'],
        // ]);

        // return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;

        return true;
    }

    /**
     * Obtiene regiones disponibles
     */
    private function get_available_regions() {
        return [
            'euskal_herria' => __('Euskal Herria', 'flavor-chat-ia'),
            'cataluna' => __('Cataluña', 'flavor-chat-ia'),
            'madrid' => __('Madrid', 'flavor-chat-ia'),
            'andalucia' => __('Andalucía', 'flavor-chat-ia'),
            'other_spain' => __('Otras regiones de España', 'flavor-chat-ia'),
            'international' => __('Internacional', 'flavor-chat-ia'),
        ];
    }

    /**
     * Obtiene categorías disponibles
     */
    private function get_available_categories() {
        return [
            'cooperativa' => __('Cooperativa', 'flavor-chat-ia'),
            'asociacion' => __('Asociación', 'flavor-chat-ia'),
            'comunidad' => __('Comunidad', 'flavor-chat-ia'),
            'grupo_consumo' => __('Grupo de Consumo', 'flavor-chat-ia'),
            'economia_social' => __('Economía Social', 'flavor-chat-ia'),
            'comercio_local' => __('Comercio Local', 'flavor-chat-ia'),
            'other' => __('Otra', 'flavor-chat-ia'),
        ];
    }

    /**
     * Limpia eventos programados
     */
    public function clear_scheduled_events() {
        wp_clear_scheduled_hook('flavor_sync_directory');
    }
}

// Inicializar directorio
add_action('plugins_loaded', function() {
    if (class_exists('Flavor_Chat_IA')) {
        Flavor_Business_Directory::get_instance();
    }
}, 20);
