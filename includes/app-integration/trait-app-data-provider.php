<?php
/**
 * Trait para que los módulos proporcionen datos a las apps
 *
 * Este trait permite que cualquier módulo exponga sus datos
 * de forma estructurada para el consumo de apps nativas.
 *
 * @package Flavor_Chat_IA
 * @subpackage App_Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Flavor_App_Data_Provider {

    /**
     * Registrar hooks para proporcionar datos a la app
     */
    protected function register_app_data_hooks() {
        $module_id = $this->get_module_id();

        // Hook para datos de componentes específicos
        add_filter("flavor_app_component_data_{$module_id}_grid", [$this, 'provide_grid_data'], 10, 2);
        add_filter("flavor_app_component_data_{$module_id}_lista", [$this, 'provide_list_data'], 10, 2);
        add_filter("flavor_app_component_data_{$module_id}_mapa", [$this, 'provide_map_data'], 10, 2);
        add_filter("flavor_app_component_data_{$module_id}_estadisticas", [$this, 'provide_stats_data'], 10, 2);
        add_filter("flavor_app_component_data_{$module_id}_calendario", [$this, 'provide_calendar_data'], 10, 2);

        // Hook genérico para datos del módulo
        add_filter("flavor_module_{$module_id}_app_data", [$this, 'provide_module_data'], 10, 2);

        // Registrar endpoints REST específicos del módulo
        add_action('rest_api_init', [$this, 'register_module_app_endpoints']);
    }

    /**
     * Obtener ID del módulo (debe ser implementado por cada módulo)
     */
    abstract protected function get_module_id();

    /**
     * Registrar endpoints REST del módulo para apps
     */
    public function register_module_app_endpoints() {
        $module_id = $this->get_module_id();
        $namespace = 'flavor-app/v1';

        // Endpoint de listado
        register_rest_route($namespace, "/modules/{$module_id}/items", [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'api_get_items'],
            'permission_callback' => '__return_true',
            'args' => [
                'page' => ['type' => 'integer', 'default' => 1],
                'per_page' => ['type' => 'integer', 'default' => 10],
                'search' => ['type' => 'string', 'default' => ''],
                'category' => ['type' => 'string', 'default' => ''],
                'orderby' => ['type' => 'string', 'default' => 'date'],
                'order' => ['type' => 'string', 'default' => 'DESC'],
            ],
        ]);

        // Endpoint de detalle
        register_rest_route($namespace, "/modules/{$module_id}/items/(?P<id>\d+)", [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'api_get_item'],
            'permission_callback' => '__return_true',
        ]);

        // Endpoint de categorías
        register_rest_route($namespace, "/modules/{$module_id}/categories", [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'api_get_categories'],
            'permission_callback' => '__return_true',
        ]);

        // Endpoint de estadísticas
        register_rest_route($namespace, "/modules/{$module_id}/stats", [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'api_get_stats'],
            'permission_callback' => '__return_true',
        ]);

        // Endpoint de ubicaciones para mapa
        register_rest_route($namespace, "/modules/{$module_id}/locations", [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'api_get_locations'],
            'permission_callback' => '__return_true',
            'args' => [
                'bounds' => ['type' => 'string', 'default' => ''], // formato: lat1,lng1,lat2,lng2
                'category' => ['type' => 'string', 'default' => ''],
            ],
        ]);

        // Endpoint para crear item (requiere autenticación)
        register_rest_route($namespace, "/modules/{$module_id}/items", [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'api_create_item'],
            'permission_callback' => [$this, 'check_create_permission'],
        ]);

        // Permitir que los módulos añadan más endpoints
        do_action("flavor_app_register_endpoints_{$module_id}", $namespace);
    }

    /**
     * Verificar permiso de creación
     */
    public function check_create_permission($request) {
        // Verificar token de API
        $token = $request->get_header('X-Flavor-Token');
        if ($token) {
            $valid_tokens = get_option('flavor_apps_tokens', []);
            foreach ($valid_tokens as $token_data) {
                if ($token_data['token'] === $token) {
                    return true;
                }
            }
        }

        // O verificar que el usuario esté logueado
        return is_user_logged_in();
    }

    /**
     * API: Obtener listado de items
     */
    public function api_get_items($request) {
        $params = [
            'page' => $request->get_param('page'),
            'per_page' => min($request->get_param('per_page'), 50), // máximo 50
            'search' => $request->get_param('search'),
            'category' => $request->get_param('category'),
            'orderby' => $request->get_param('orderby'),
            'order' => $request->get_param('order'),
        ];

        $result = $this->get_items_for_app($params);

        return rest_ensure_response([
            'success' => true,
            'module' => $this->get_module_id(),
            'items' => $result['items'],
            'pagination' => [
                'page' => $params['page'],
                'per_page' => $params['per_page'],
                'total' => $result['total'],
                'total_pages' => ceil($result['total'] / $params['per_page']),
            ],
        ]);
    }

    /**
     * API: Obtener item específico
     */
    public function api_get_item($request) {
        $item_id = $request->get_param('id');
        $item = $this->get_item_for_app($item_id);

        if (!$item) {
            return new WP_Error('not_found', 'Item no encontrado', ['status' => 404]);
        }

        return rest_ensure_response([
            'success' => true,
            'module' => $this->get_module_id(),
            'item' => $item,
        ]);
    }

    /**
     * API: Obtener categorías
     */
    public function api_get_categories($request) {
        $categories = $this->get_categories_for_app();

        return rest_ensure_response([
            'success' => true,
            'module' => $this->get_module_id(),
            'categories' => $categories,
        ]);
    }

    /**
     * API: Obtener estadísticas
     */
    public function api_get_stats($request) {
        $stats = $this->get_stats_for_app();

        return rest_ensure_response([
            'success' => true,
            'module' => $this->get_module_id(),
            'stats' => $stats,
        ]);
    }

    /**
     * API: Obtener ubicaciones para mapa
     */
    public function api_get_locations($request) {
        $bounds = $request->get_param('bounds');
        $category = $request->get_param('category');

        $bounds_array = null;
        if ($bounds) {
            $parts = explode(',', $bounds);
            if (count($parts) === 4) {
                $bounds_array = [
                    'sw_lat' => floatval($parts[0]),
                    'sw_lng' => floatval($parts[1]),
                    'ne_lat' => floatval($parts[2]),
                    'ne_lng' => floatval($parts[3]),
                ];
            }
        }

        $locations = $this->get_locations_for_app($bounds_array, $category);

        return rest_ensure_response([
            'success' => true,
            'module' => $this->get_module_id(),
            'locations' => $locations,
        ]);
    }

    /**
     * API: Crear item
     */
    public function api_create_item($request) {
        $data = $request->get_json_params();

        $result = $this->create_item_from_app($data);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response([
            'success' => true,
            'module' => $this->get_module_id(),
            'item' => $result,
        ]);
    }

    /**
     * Proporcionar datos para componente grid
     */
    public function provide_grid_data($data, $context) {
        $params = [
            'page' => $context['page'] ?? 1,
            'per_page' => $context['limit'] ?? 10,
            'category' => $context['category'] ?? '',
        ];

        return $this->get_items_for_app($params);
    }

    /**
     * Proporcionar datos para componente lista
     */
    public function provide_list_data($data, $context) {
        return $this->provide_grid_data($data, $context);
    }

    /**
     * Proporcionar datos para componente mapa
     */
    public function provide_map_data($data, $context) {
        return [
            'markers' => $this->get_locations_for_app(null, $context['category'] ?? ''),
            'center' => $this->get_map_center(),
            'zoom' => $context['zoom'] ?? 14,
        ];
    }

    /**
     * Proporcionar datos para componente estadísticas
     */
    public function provide_stats_data($data, $context) {
        return $this->get_stats_for_app();
    }

    /**
     * Proporcionar datos para componente calendario
     */
    public function provide_calendar_data($data, $context) {
        $start = $context['start'] ?? date('Y-m-01');
        $end = $context['end'] ?? date('Y-m-t');

        return [
            'events' => $this->get_events_for_app($start, $end),
        ];
    }

    /**
     * Proporcionar datos generales del módulo
     */
    public function provide_module_data($data, $context) {
        return [
            'module_id' => $this->get_module_id(),
            'module_name' => $this->get_module_name(),
            'endpoints' => $this->get_module_endpoints(),
            'capabilities' => $this->get_module_capabilities(),
        ];
    }

    // =====================================================
    // Métodos que deben ser implementados por cada módulo
    // =====================================================

    /**
     * Obtener items para la app (debe ser implementado)
     */
    protected function get_items_for_app($params) {
        // Implementación por defecto vacía
        return [
            'items' => [],
            'total' => 0,
        ];
    }

    /**
     * Obtener un item específico para la app
     */
    protected function get_item_for_app($item_id) {
        return null;
    }

    /**
     * Obtener categorías para la app
     */
    protected function get_categories_for_app() {
        return [];
    }

    /**
     * Obtener estadísticas para la app
     */
    protected function get_stats_for_app() {
        return [];
    }

    /**
     * Obtener ubicaciones para la app
     */
    protected function get_locations_for_app($bounds = null, $category = '') {
        return [];
    }

    /**
     * Obtener centro del mapa
     */
    protected function get_map_center() {
        return [
            'lat' => 40.4168, // Madrid por defecto
            'lng' => -3.7038,
        ];
    }

    /**
     * Obtener eventos para calendario
     */
    protected function get_events_for_app($start, $end) {
        return [];
    }

    /**
     * Crear item desde la app
     */
    protected function create_item_from_app($data) {
        return new WP_Error('not_implemented', 'Creación no implementada para este módulo');
    }

    /**
     * Obtener nombre del módulo
     */
    protected function get_module_name() {
        return ucwords(str_replace(['-', '_'], ' ', $this->get_module_id()));
    }

    /**
     * Obtener endpoints del módulo
     */
    protected function get_module_endpoints() {
        $module_id = $this->get_module_id();
        $base = rest_url("flavor-app/v1/modules/{$module_id}");

        return [
            'items' => $base . '/items',
            'categories' => $base . '/categories',
            'stats' => $base . '/stats',
            'locations' => $base . '/locations',
        ];
    }

    /**
     * Obtener capacidades del módulo
     */
    protected function get_module_capabilities() {
        return [
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
            'has_map' => false,
            'has_calendar' => false,
            'has_categories' => true,
            'has_search' => true,
        ];
    }

    // =====================================================
    // Helpers para formatear datos
    // =====================================================

    /**
     * Formatear item para respuesta de app
     */
    protected function format_item_for_app($post, $extra_fields = []) {
        $item = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'excerpt' => wp_trim_words($post->post_content, 30),
            'content' => apply_filters('the_content', $post->post_content),
            'date' => $post->post_date_gmt,
            'modified' => $post->post_modified_gmt,
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'author' => [
                'id' => $post->post_author,
                'name' => get_the_author_meta('display_name', $post->post_author),
                'avatar' => get_avatar_url($post->post_author, ['size' => 96]),
            ],
            'featured_image' => $this->get_featured_image_data($post->ID),
        ];

        // Merge con campos extra
        return array_merge($item, $extra_fields);
    }

    /**
     * Obtener datos de imagen destacada
     */
    protected function get_featured_image_data($post_id) {
        $thumbnail_id = get_post_thumbnail_id($post_id);

        if (!$thumbnail_id) {
            return null;
        }

        return [
            'id' => $thumbnail_id,
            'url' => wp_get_attachment_url($thumbnail_id),
            'thumbnail' => wp_get_attachment_image_url($thumbnail_id, 'thumbnail'),
            'medium' => wp_get_attachment_image_url($thumbnail_id, 'medium'),
            'large' => wp_get_attachment_image_url($thumbnail_id, 'large'),
            'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true),
        ];
    }

    /**
     * Formatear categoría para respuesta de app
     */
    protected function format_category_for_app($term) {
        return [
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
            'count' => $term->count,
            'parent' => $term->parent,
            'icon' => get_term_meta($term->term_id, 'icon', true) ?: null,
            'color' => get_term_meta($term->term_id, 'color', true) ?: null,
        ];
    }

    /**
     * Formatear ubicación para mapa
     */
    protected function format_location_for_app($id, $title, $lat, $lng, $extra = []) {
        return array_merge([
            'id' => $id,
            'title' => $title,
            'latitude' => floatval($lat),
            'longitude' => floatval($lng),
        ], $extra);
    }

    /**
     * Formatear estadística
     */
    protected function format_stat_for_app($label, $value, $icon = null, $trend = null) {
        return [
            'label' => $label,
            'value' => $value,
            'formatted_value' => is_numeric($value) ? number_format_i18n($value) : $value,
            'icon' => $icon,
            'trend' => $trend, // 'up', 'down', 'neutral'
        ];
    }

    /**
     * Formatear evento para calendario
     */
    protected function format_event_for_app($id, $title, $start, $end = null, $extra = []) {
        return array_merge([
            'id' => $id,
            'title' => $title,
            'start' => $start, // ISO 8601
            'end' => $end,
            'all_day' => empty($end),
        ], $extra);
    }
}
