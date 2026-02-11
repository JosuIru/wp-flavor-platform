<?php
/**
 * Layout API - REST API para configuración de layouts
 *
 * Proporciona endpoints para que las apps móviles
 * obtengan la configuración de menús y footers.
 *
 * @package Flavor_Chat_IA
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Layout_API {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'flavor-layouts/v1';

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
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registrar rutas de la API
     */
    public function register_routes() {
        // GET /flavor-layouts/v1/config
        register_rest_route(self::API_NAMESPACE, '/config', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_layout_config'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // GET /flavor-layouts/v1/menus
        register_rest_route(self::API_NAMESPACE, '/menus', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_menus'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // GET /flavor-layouts/v1/menus/{id}
        register_rest_route(self::API_NAMESPACE, '/menus/(?P<id>[a-z0-9-]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_menu'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_key',
                ],
            ],
        ]);

        // GET /flavor-layouts/v1/footers
        register_rest_route(self::API_NAMESPACE, '/footers', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_footers'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // GET /flavor-layouts/v1/footers/{id}
        register_rest_route(self::API_NAMESPACE, '/footers/(?P<id>[a-z0-9-]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_footer'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_key',
                ],
            ],
        ]);

        // GET /flavor-layouts/v1/navigation
        register_rest_route(self::API_NAMESPACE, '/navigation', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_navigation'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args' => [
                'location' => [
                    'type' => 'string',
                    'default' => 'primary',
                    'sanitize_callback' => 'sanitize_key',
                ],
            ],
        ]);

        // GET /flavor-layouts/v1/theme
        register_rest_route(self::API_NAMESPACE, '/theme', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_theme_config'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // POST /flavor-layouts/v1/config (requiere autenticación)
        register_rest_route(self::API_NAMESPACE, '/config', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'update_layout_config'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);
    }

    /**
     * Verificar permisos de administrador
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }

    /**
     * GET /config - Obtener configuración completa
     */
    public function get_layout_config(WP_REST_Request $request) {
        $registry = flavor_layout_registry();

        return rest_ensure_response([
            'success' => true,
            'data' => $registry->export_for_mobile(),
        ]);
    }

    /**
     * GET /menus - Listar todos los menús
     */
    public function get_menus(WP_REST_Request $request) {
        $registry = flavor_layout_registry();
        $menus = $registry->get_menus();
        $active_layout = $registry->get_active_layout();

        $formatted_menus = [];
        foreach ($menus as $menu_id => $menu) {
            $formatted_menus[] = $this->format_menu($menu_id, $menu, $active_layout['menu'] === $menu_id);
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $formatted_menus,
            'active' => $active_layout['menu'],
        ]);
    }

    /**
     * GET /menus/{id} - Obtener un menú específico
     */
    public function get_menu(WP_REST_Request $request) {
        $menu_id = $request->get_param('id');
        $registry = flavor_layout_registry();
        $menu = $registry->get_menu($menu_id);

        if (!$menu) {
            return new WP_Error(
                'menu_not_found',
                __('Menú no encontrado', 'flavor-chat-ia'),
                ['status' => 404]
            );
        }

        $active_layout = $registry->get_active_layout();

        return rest_ensure_response([
            'success' => true,
            'data' => $this->format_menu($menu_id, $menu, $active_layout['menu'] === $menu_id),
        ]);
    }

    /**
     * GET /footers - Listar todos los footers
     */
    public function get_footers(WP_REST_Request $request) {
        $registry = flavor_layout_registry();
        $footers = $registry->get_footers();
        $active_layout = $registry->get_active_layout();

        $formatted_footers = [];
        foreach ($footers as $footer_id => $footer) {
            $formatted_footers[] = $this->format_footer($footer_id, $footer, $active_layout['footer'] === $footer_id);
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $formatted_footers,
            'active' => $active_layout['footer'],
        ]);
    }

    /**
     * GET /footers/{id} - Obtener un footer específico
     */
    public function get_footer(WP_REST_Request $request) {
        $footer_id = $request->get_param('id');
        $registry = flavor_layout_registry();
        $footer = $registry->get_footer($footer_id);

        if (!$footer) {
            return new WP_Error(
                'footer_not_found',
                __('Footer no encontrado', 'flavor-chat-ia'),
                ['status' => 404]
            );
        }

        $active_layout = $registry->get_active_layout();

        return rest_ensure_response([
            'success' => true,
            'data' => $this->format_footer($footer_id, $footer, $active_layout['footer'] === $footer_id),
        ]);
    }

    /**
     * GET /navigation - Obtener items de navegación
     */
    public function get_navigation(WP_REST_Request $request) {
        $location = $request->get_param('location');

        $menu_locations = get_nav_menu_locations();
        $menu_items = [];

        if (isset($menu_locations[$location])) {
            $menu_object = wp_get_nav_menu_object($menu_locations[$location]);

            if ($menu_object) {
                $items = wp_get_nav_menu_items($menu_object->term_id);

                foreach ($items as $item) {
                    $menu_items[] = [
                        'id' => $item->ID,
                        'title' => $item->title,
                        'url' => $item->url,
                        'target' => $item->target,
                        'icon' => get_post_meta($item->ID, '_menu_item_icon', true) ?: 'link',
                        'parent' => (int) $item->menu_item_parent,
                        'order' => $item->menu_order,
                        'classes' => implode(' ', array_filter($item->classes)),
                    ];
                }

                // Organizar jerárquicamente
                $menu_items = $this->build_menu_tree($menu_items);
            }
        }

        return rest_ensure_response([
            'success' => true,
            'location' => $location,
            'data' => $menu_items,
        ]);
    }

    /**
     * GET /theme - Obtener configuración del tema
     */
    public function get_theme_config(WP_REST_Request $request) {
        $design_settings = get_option('flavor_design_settings', []);
        $layout_settings = get_option('flavor_layout_settings', []);

        return rest_ensure_response([
            'success' => true,
            'data' => [
                'colors' => [
                    'primary' => $design_settings['primary_color'] ?? '#3b82f6',
                    'secondary' => $design_settings['secondary_color'] ?? '#8b5cf6',
                    'accent' => $design_settings['accent_color'] ?? '#f59e0b',
                    'success' => $design_settings['success_color'] ?? '#10b981',
                    'warning' => $design_settings['warning_color'] ?? '#f59e0b',
                    'error' => $design_settings['error_color'] ?? '#ef4444',
                    'background' => $design_settings['background_color'] ?? '#ffffff',
                    'text' => $design_settings['text_color'] ?? '#1f2937',
                    'text_secondary' => $design_settings['text_secondary_color'] ?? '#6b7280',
                ],
                'typography' => [
                    'heading_font' => $design_settings['heading_font'] ?? 'Inter',
                    'body_font' => $design_settings['body_font'] ?? 'Inter',
                    'base_size' => $design_settings['base_font_size'] ?? '16px',
                ],
                'spacing' => [
                    'border_radius' => $design_settings['border_radius'] ?? '8px',
                    'container_width' => $design_settings['container_width'] ?? '1200px',
                ],
                'branding' => [
                    'logo_url' => $this->get_logo_url(),
                    'site_name' => get_bloginfo('name'),
                    'site_description' => get_bloginfo('description'),
                ],
                'contact' => [
                    'phone' => $layout_settings['contact_phone'] ?? '',
                    'email' => $layout_settings['contact_email'] ?? get_option('admin_email'),
                    'address' => $layout_settings['contact_address'] ?? '',
                    'hours' => $layout_settings['business_hours'] ?? '',
                ],
                'social' => $layout_settings['social_links'] ?? [],
                'apps' => [
                    'app_store_url' => $layout_settings['app_store_url'] ?? '',
                    'play_store_url' => $layout_settings['play_store_url'] ?? '',
                ],
            ],
        ]);
    }

    /**
     * POST /config - Actualizar configuración de layout
     */
    public function update_layout_config(WP_REST_Request $request) {
        $menu_id = sanitize_key($request->get_param('menu'));
        $footer_id = sanitize_key($request->get_param('footer'));

        $registry = flavor_layout_registry();

        if (!empty($menu_id) && $registry->get_menu($menu_id)) {
            $settings = get_option('flavor_layout_settings', []);
            $settings['active_menu'] = $menu_id;
            update_option('flavor_layout_settings', $settings);
        }

        if (!empty($footer_id) && $registry->get_footer($footer_id)) {
            $settings = get_option('flavor_layout_settings', []);
            $settings['active_footer'] = $footer_id;
            update_option('flavor_layout_settings', $settings);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Configuración actualizada', 'flavor-chat-ia'),
            'data' => $registry->export_for_mobile(),
        ]);
    }

    /**
     * Formatear menú para API
     */
    private function format_menu($menu_id, $menu, $is_active) {
        return [
            'id' => $menu_id,
            'name' => $menu['name'],
            'description' => $menu['description'],
            'icon' => $menu['icon'],
            'mobile_behavior' => $menu['mobile_behavior'],
            'recommended_for' => $menu['recommended_for'],
            'supports' => $menu['supports'],
            'settings' => $menu['settings'],
            'is_active' => $is_active,
        ];
    }

    /**
     * Formatear footer para API
     */
    private function format_footer($footer_id, $footer, $is_active) {
        return [
            'id' => $footer_id,
            'name' => $footer['name'],
            'description' => $footer['description'],
            'icon' => $footer['icon'],
            'recommended_for' => $footer['recommended_for'],
            'supports' => $footer['supports'],
            'settings' => $footer['settings'],
            'is_active' => $is_active,
        ];
    }

    /**
     * Construir árbol de menú jerárquico
     */
    private function build_menu_tree($items, $parent_id = 0) {
        $tree = [];

        foreach ($items as $item) {
            if ($item['parent'] == $parent_id) {
                $children = $this->build_menu_tree($items, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }

        // Ordenar por menu_order
        usort($tree, function($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $tree;
    }

    /**
     * Obtener URL del logo
     */
    private function get_logo_url() {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            return wp_get_attachment_image_url($custom_logo_id, 'full');
        }
        return '';
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }
}

// Inicializar
add_action('init', function() {
    Flavor_Layout_API::get_instance();
});
