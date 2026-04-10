<?php
/**
 * API de Lazy Loading para apps móviles
 * 
 * Endpoints optimizados para carga bajo demanda de recursos.
 * Soporta campos específicos, paginación y caché.
 *
 * @package Flavor_Platform
 * @subpackage API
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Flavor_Lazy_Loading_API
 */
class Flavor_Lazy_Loading_API {

    /**
     * Namespace de la API
     */
    const NAMESPACE = 'flavor-app/v2';

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtener instancia singleton
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
     * Registra las rutas
     */
    public function register_routes() {
        // GET /modules - Lista resumida de módulos
        register_rest_route(self::NAMESPACE, '/modules', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_modules_list'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'fields' => array(
                    'type' => 'string',
                    'description' => 'Campos a incluir (separados por coma)',
                    'default' => 'id,name,icon,enabled',
                ),
                'enabled_only' => array(
                    'type' => 'boolean',
                    'default' => true,
                ),
            ),
        ));

        // GET /modules/{id} - Detalle de módulo
        register_rest_route(self::NAMESPACE, '/modules/(?P<id>[a-z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_module_detail'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'fields' => array(
                    'type' => 'string',
                    'description' => 'Campos a incluir',
                ),
            ),
        ));

        // GET /modules/{id}/content - Contenido de módulo
        register_rest_route(self::NAMESPACE, '/modules/(?P<id>[a-z0-9_-]+)/content', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_module_content'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'string',
                ),
                'page' => array(
                    'type' => 'integer',
                    'default' => 1,
                ),
                'per_page' => array(
                    'type' => 'integer',
                    'default' => 20,
                    'maximum' => 100,
                ),
                'orderby' => array(
                    'type' => 'string',
                    'default' => 'date',
                ),
                'order' => array(
                    'type' => 'string',
                    'enum' => array('asc', 'desc'),
                    'default' => 'desc',
                ),
            ),
        ));

        // GET /resources/{type} - Recursos genéricos
        register_rest_route(self::NAMESPACE, '/resources/(?P<type>[a-z_]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_resources'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('icons', 'colors', 'fonts', 'images', 'templates'),
                ),
                'category' => array(
                    'type' => 'string',
                ),
            ),
        ));

        // GET /prefetch - Prefetch de múltiples recursos
        register_rest_route(self::NAMESPACE, '/prefetch', array(
            'methods' => 'POST',
            'callback' => array($this, 'prefetch_resources'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'resources' => array(
                    'required' => true,
                    'type' => 'array',
                    'description' => 'Array de recursos a prefetch',
                ),
            ),
        ));
    }

    /**
     * Verifica permisos
     */
    public function check_permission($request) {
        $api_key = flavor_get_vbp_api_key_from_request( $request );
        return flavor_check_vbp_automation_access( $api_key, 'lazy_loading' ) || is_user_logged_in();
    }

    /**
     * GET /modules - Lista resumida
     */
    public function get_modules_list($request) {
        $fields = explode(',', $request->get_param('fields'));
        $enabled_only = $request->get_param('enabled_only');

        // Obtener módulos activos
        $active_modules = get_option('flavor_active_modules', array());
        
        // Obtener todos los módulos disponibles
        $all_modules = $this->get_all_available_modules();

        $result = array();

        foreach ($all_modules as $module_id => $module_info) {
            if ($enabled_only && !in_array($module_id, $active_modules)) {
                continue;
            }

            $module_data = array();

            foreach ($fields as $field) {
                $field = trim($field);
                switch ($field) {
                    case 'id':
                        $module_data['id'] = $module_id;
                        break;
                    case 'name':
                        $module_data['name'] = $module_info['name'] ?? $module_id;
                        break;
                    case 'icon':
                        $module_data['icon'] = $module_info['icon'] ?? 'extension';
                        break;
                    case 'enabled':
                        $module_data['enabled'] = in_array($module_id, $active_modules);
                        break;
                    case 'description':
                        $module_data['description'] = $module_info['description'] ?? '';
                        break;
                    case 'category':
                        $module_data['category'] = $module_info['category'] ?? 'other';
                        break;
                }
            }

            if (!empty($module_data)) {
                $result[] = $module_data;
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'modules' => $result,
            'total' => count($result),
            'cached_until' => date('c', time() + 300), // 5 minutos
        ));
    }

    /**
     * GET /modules/{id} - Detalle completo
     */
    public function get_module_detail($request) {
        $module_id = $request->get_param('id');
        $fields = $request->get_param('fields');

        // Verificar que el módulo existe
        $all_modules = $this->get_all_available_modules();
        
        if (!isset($all_modules[$module_id])) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Módulo no encontrado',
            ), 404);
        }

        $module_info = $all_modules[$module_id];
        $active_modules = get_option('flavor_active_modules', array());
        $is_active = in_array($module_id, $active_modules);

        $result = array(
            'id' => $module_id,
            'name' => $module_info['name'] ?? $module_id,
            'description' => $module_info['description'] ?? '',
            'icon' => $module_info['icon'] ?? 'extension',
            'enabled' => $is_active,
            'category' => $module_info['category'] ?? 'other',
            'config' => $this->get_module_config($module_id),
            'capabilities' => $this->get_module_capabilities($module_id),
            'endpoints' => $this->get_module_endpoints($module_id),
        );

        // Filtrar campos si se especificaron
        if ($fields) {
            $requested_fields = array_map('trim', explode(',', $fields));
            $result = array_intersect_key($result, array_flip($requested_fields));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'module' => $result,
            'cached_until' => date('c', time() + 600), // 10 minutos
        ));
    }

    /**
     * GET /modules/{id}/content - Contenido de módulo
     */
    public function get_module_content($request) {
        $module_id = $request->get_param('id');
        $page = intval($request->get_param('page'));
        $per_page = intval($request->get_param('per_page'));
        $orderby = $request->get_param('orderby');
        $order = strtoupper($request->get_param('order'));

        // Verificar módulo activo
        $active_modules = get_option('flavor_active_modules', array());
        if (!in_array($module_id, $active_modules)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Módulo no activo',
            ), 400);
        }

        // Obtener contenido según el tipo de módulo
        $content = $this->fetch_module_content($module_id, $page, $per_page, $orderby, $order);

        return new WP_REST_Response(array(
            'success' => true,
            'items' => $content['items'],
            'total' => $content['total'],
            'page' => $page,
            'per_page' => $per_page,
            'has_more' => ($page * $per_page) < $content['total'],
            'cached_until' => date('c', time() + 120), // 2 minutos
        ));
    }

    /**
     * GET /resources/{type} - Recursos estáticos
     */
    public function get_resources($request) {
        $type = $request->get_param('type');
        $category = $request->get_param('category');

        $resources = array();

        switch ($type) {
            case 'icons':
                $resources = $this->get_available_icons($category);
                break;
            case 'colors':
                $resources = $this->get_color_palette($category);
                break;
            case 'fonts':
                $resources = $this->get_available_fonts();
                break;
            case 'images':
                $resources = $this->get_media_library($category);
                break;
            case 'templates':
                $resources = $this->get_available_templates($category);
                break;
        }

        return new WP_REST_Response(array(
            'success' => true,
            'type' => $type,
            'resources' => $resources,
            'total' => count($resources),
            'cached_until' => date('c', time() + 3600), // 1 hora
        ));
    }

    /**
     * POST /prefetch - Prefetch múltiple
     */
    public function prefetch_resources($request) {
        $resources = $request->get_param('resources');
        $results = array();

        foreach ($resources as $resource) {
            $type = $resource['type'] ?? '';
            $id = $resource['id'] ?? '';

            switch ($type) {
                case 'module':
                    $all_modules = $this->get_all_available_modules();
                    if (isset($all_modules[$id])) {
                        $results[$type . '_' . $id] = array(
                            'success' => true,
                            'data' => array(
                                'id' => $id,
                                'name' => $all_modules[$id]['name'] ?? $id,
                                'icon' => $all_modules[$id]['icon'] ?? 'extension',
                            ),
                        );
                    }
                    break;

                case 'config':
                    $results['config'] = array(
                        'success' => true,
                        'data' => array(
                            'site_name' => get_bloginfo('name'),
                            'site_url' => home_url(),
                            'active_modules' => get_option('flavor_active_modules', array()),
                        ),
                    );
                    break;

                case 'navigation':
                    $results['navigation'] = array(
                        'success' => true,
                        'data' => $this->get_navigation_config(),
                    );
                    break;
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'results' => $results,
            'prefetched' => count($results),
            'cached_until' => date('c', time() + 300),
        ));
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    /**
     * Obtiene todos los módulos disponibles
     */
    private function get_all_available_modules() {
        // Cachear resultado
        static $modules = null;

        if ($modules !== null) {
            return $modules;
        }

        $modules = array();

        // Obtener módulos registrados en el sistema
        if (class_exists('Flavor_Module_Manager')) {
            $manager = Flavor_Module_Manager::get_instance();
            if (method_exists($manager, 'get_all_modules')) {
                $modules = $manager->get_all_modules();
            }
        }

        // Si no hay módulos del manager, usar lista por defecto
        if (empty($modules)) {
            $modules = array(
                'eventos' => array('name' => 'Eventos', 'icon' => 'calendar_today', 'category' => 'community'),
                'socios' => array('name' => 'Socios', 'icon' => 'people', 'category' => 'community'),
                'marketplace' => array('name' => 'Marketplace', 'icon' => 'storefront', 'category' => 'commerce'),
                'grupos-consumo' => array('name' => 'Grupos de Consumo', 'icon' => 'shopping_basket', 'category' => 'commerce'),
                'foros' => array('name' => 'Foros', 'icon' => 'forum', 'category' => 'social'),
                'reservas' => array('name' => 'Reservas', 'icon' => 'event_available', 'category' => 'services'),
                'talleres' => array('name' => 'Talleres', 'icon' => 'handyman', 'category' => 'education'),
                'cursos' => array('name' => 'Cursos', 'icon' => 'school', 'category' => 'education'),
                'biblioteca' => array('name' => 'Biblioteca', 'icon' => 'library_books', 'category' => 'education'),
                'encuestas' => array('name' => 'Encuestas', 'icon' => 'poll', 'category' => 'participation'),
                'transparencia' => array('name' => 'Transparencia', 'icon' => 'visibility', 'category' => 'governance'),
                'incidencias' => array('name' => 'Incidencias', 'icon' => 'report_problem', 'category' => 'support'),
            );
        }

        return $modules;
    }

    /**
     * Obtiene configuración de módulo
     */
    private function get_module_config($module_id) {
        return get_option("flavor_module_{$module_id}_config", array());
    }

    /**
     * Obtiene capacidades de módulo
     */
    private function get_module_capabilities($module_id) {
        $capabilities = array('view');

        if (is_user_logged_in()) {
            $capabilities[] = 'interact';

            if (current_user_can('edit_posts')) {
                $capabilities[] = 'create';
                $capabilities[] = 'edit';
            }

            if (current_user_can('delete_posts')) {
                $capabilities[] = 'delete';
            }
        }

        return $capabilities;
    }

    /**
     * Obtiene endpoints de módulo
     */
    private function get_module_endpoints($module_id) {
        return array(
            'list' => "/flavor-app/v2/modules/{$module_id}/content",
            'detail' => "/flavor-app/v2/modules/{$module_id}/content/{id}",
            'create' => "/flavor-app/v2/modules/{$module_id}/content",
            'update' => "/flavor-app/v2/modules/{$module_id}/content/{id}",
            'delete' => "/flavor-app/v2/modules/{$module_id}/content/{id}",
        );
    }

    /**
     * Obtiene contenido de módulo
     */
    private function fetch_module_content($module_id, $page, $per_page, $orderby, $order) {
        // Mapeo de módulos a post types
        $post_type_map = array(
            'eventos' => 'flavor_event',
            'talleres' => 'flavor_workshop',
            'cursos' => 'flavor_course',
            'biblioteca' => 'flavor_book',
            'marketplace' => 'flavor_product',
            'foros' => 'flavor_topic',
            'reservas' => 'flavor_reservation',
        );

        $post_type = $post_type_map[$module_id] ?? "flavor_{$module_id}";

        // Verificar si el post type existe
        if (!post_type_exists($post_type)) {
            return array('items' => array(), 'total' => 0);
        }

        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
            'post_status' => 'publish',
        );

        $query = new WP_Query($args);
        $items = array();

        foreach ($query->posts as $post) {
            $items[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'excerpt' => wp_trim_words($post->post_content, 30),
                'date' => $post->post_date,
                'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium'),
                'permalink' => get_permalink($post->ID),
            );
        }

        return array(
            'items' => $items,
            'total' => $query->found_posts,
        );
    }

    /**
     * Obtiene iconos disponibles
     */
    private function get_available_icons($category = null) {
        $icons = array(
            'navigation' => array('home', 'menu', 'arrow_back', 'close', 'search'),
            'action' => array('add', 'edit', 'delete', 'save', 'share'),
            'content' => array('article', 'image', 'video', 'file', 'folder'),
            'social' => array('person', 'people', 'group', 'chat', 'forum'),
            'commerce' => array('shopping_cart', 'storefront', 'payment', 'receipt'),
            'calendar' => array('event', 'calendar_today', 'schedule', 'alarm'),
        );

        if ($category && isset($icons[$category])) {
            return $icons[$category];
        }

        return $icons;
    }

    /**
     * Obtiene paleta de colores
     */
    private function get_color_palette($category = null) {
        return array(
            'primary' => array('#6366f1', '#818cf8', '#4f46e5'),
            'success' => array('#22c55e', '#4ade80', '#16a34a'),
            'warning' => array('#f59e0b', '#fbbf24', '#d97706'),
            'error' => array('#ef4444', '#f87171', '#dc2626'),
            'neutral' => array('#6b7280', '#9ca3af', '#374151'),
        );
    }

    /**
     * Obtiene fuentes disponibles
     */
    private function get_available_fonts() {
        return array(
            array('name' => 'Inter', 'family' => 'Inter, sans-serif'),
            array('name' => 'Roboto', 'family' => 'Roboto, sans-serif'),
            array('name' => 'Open Sans', 'family' => '"Open Sans", sans-serif'),
            array('name' => 'Poppins', 'family' => 'Poppins, sans-serif'),
            array('name' => 'Lato', 'family' => 'Lato, sans-serif'),
        );
    }

    /**
     * Obtiene media library
     */
    private function get_media_library($category = null) {
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $attachments = get_posts($args);
        $images = array();

        foreach ($attachments as $attachment) {
            $images[] = array(
                'id' => $attachment->ID,
                'url' => wp_get_attachment_url($attachment->ID),
                'thumbnail' => wp_get_attachment_image_url($attachment->ID, 'thumbnail'),
                'alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
            );
        }

        return $images;
    }

    /**
     * Obtiene plantillas disponibles
     */
    private function get_available_templates($category = null) {
        return array(
            array('id' => 'landing', 'name' => 'Landing Page', 'category' => 'page'),
            array('id' => 'portal', 'name' => 'Portal Dashboard', 'category' => 'dashboard'),
            array('id' => 'list', 'name' => 'Lista de Items', 'category' => 'content'),
            array('id' => 'detail', 'name' => 'Detalle de Item', 'category' => 'content'),
        );
    }

    /**
     * Obtiene configuración de navegación
     */
    private function get_navigation_config() {
        $active_modules = get_option('flavor_active_modules', array());
        $app_config = get_option('flavor_app_config', array());

        $navigation = $app_config['navigation'] ?? array(
            'style' => 'bottom_tabs',
            'tabs' => array(
                array('id' => 'home', 'label' => 'Inicio', 'icon' => 'home'),
            ),
        );

        return $navigation;
    }
}

// Inicializar
// Flavor_Lazy_Loading_API::get_instance();
