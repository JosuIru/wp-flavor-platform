<?php
/**
 * Gestor principal del sistema de restaurante
 *
 * @package Flavor_Restaurant_Ordering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Restaurant_Manager {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Configuración del restaurante
     */
    private $settings = [];

    /**
     * Cache de configuración de menú
     */
    private $menu_config_cache = null;

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
        $this->load_settings();
        $this->init_hooks();
    }

    /**
     * Cargar configuración
     */
    private function load_settings() {
        $this->settings = get_option('flavor_restaurant_settings', []);

        // Valores por defecto si no existe configuración
        $defaults = [
            'menu_cpts' => [
                'dishes' => [],
                'drinks' => [],
                'desserts' => [],
            ],
            'table_prefix' => 'MESA',
            'order_statuses' => [
                'pending' => 'Pendiente',
                'preparing' => 'Preparando',
                'ready' => 'Listo',
                'served' => 'Servido',
                'completed' => 'Completado',
                'cancelled' => 'Cancelado'
            ],
            'enable_table_qr' => true,
            'enable_notifications' => true,
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'tax_rate' => 10
        ];

        $this->settings = wp_parse_args($this->settings, $defaults);
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Filtro para agregar información de restaurante al sistema info
        add_filter('flavor_app_system_info', [$this, 'add_restaurant_to_system_info'], 10, 1);
    }

    /**
     * Agregar información de restaurante al system info
     */
    public function add_restaurant_to_system_info($info) {
        $info['restaurant'] = [
            'available' => true,
            'version' => FLAVOR_RESTAURANT_VERSION,
            'endpoints' => [
                'menu' => rest_url('restaurant/v1/menu'),
                'tables' => rest_url('restaurant/v1/tables'),
                'orders' => rest_url('restaurant/v1/orders'),
                'order_status' => rest_url('restaurant/v1/order-status'),
            ],
            'features' => [
                'qr_codes' => $this->settings['enable_table_qr'],
                'notifications' => $this->settings['enable_notifications'],
            ],
            'currency' => [
                'code' => $this->settings['currency'],
                'symbol' => $this->settings['currency_symbol'],
            ],
            'tax_rate' => $this->settings['tax_rate'],
        ];

        return $info;
    }

    /**
     * Obtener configuración
     */
    public function get_settings($key = null) {
        if ($key === null) {
            return $this->settings;
        }

        return isset($this->settings[$key]) ? $this->settings[$key] : null;
    }

    /**
     * Actualizar configuración
     */
    public function update_settings($key, $value) {
        $this->settings[$key] = $value;
        update_option('flavor_restaurant_settings', $this->settings);
        $this->menu_config_cache = null; // Limpiar cache
        return true;
    }

    /**
     * Obtener CPTs configurados como menú
     */
    public function get_menu_cpts() {
        if ($this->menu_config_cache !== null) {
            return $this->menu_config_cache;
        }

        $menu_cpts = $this->settings['menu_cpts'];
        $result = [];

        foreach ($menu_cpts as $category => $cpt_slugs) {
            if (empty($cpt_slugs)) {
                continue;
            }

            foreach ($cpt_slugs as $cpt_slug) {
                $post_type_obj = get_post_type_object($cpt_slug);

                if ($post_type_obj) {
                    $result[$category][] = [
                        'slug' => $cpt_slug,
                        'name' => $post_type_obj->labels->name,
                        'singular_name' => $post_type_obj->labels->singular_name,
                        'total_items' => wp_count_posts($cpt_slug)->publish ?? 0
                    ];
                }
            }
        }

        $this->menu_config_cache = $result;
        return $result;
    }

    /**
     * Verificar si un CPT es parte del menú
     */
    public function is_menu_cpt($post_type) {
        $menu_cpts = $this->settings['menu_cpts'];

        foreach ($menu_cpts as $category => $cpt_slugs) {
            if (in_array($post_type, $cpt_slugs)) {
                return $category;
            }
        }

        return false;
    }

    /**
     * Obtener categoría de un CPT
     */
    public function get_cpt_category($post_type) {
        return $this->is_menu_cpt($post_type);
    }

    /**
     * Obtener menú completo para la app
     */
    public function get_full_menu() {
        $menu_structure = $this->get_menu_cpts();
        $menu_data = [];

        foreach ($menu_structure as $category => $cpts) {
            $menu_data[$category] = [
                'label' => $this->get_category_label($category),
                'icon' => $this->get_category_icon($category),
                'color' => $this->get_category_color($category),
                'items' => []
            ];

            foreach ($cpts as $cpt_info) {
                $items = $this->get_menu_items($cpt_info['slug']);
                $menu_data[$category]['items'] = array_merge(
                    $menu_data[$category]['items'],
                    $items
                );
            }
        }

        return $menu_data;
    }

    /**
     * Obtener items de menú de un CPT específico
     */
    public function get_menu_items($post_type, $args = []) {
        $defaults = [
            'posts_per_page' => -1,
            'post_type' => $post_type,
            'post_status' => 'publish',
            'orderby' => 'menu_order title',
            'order' => 'ASC'
        ];

        $args = wp_parse_args($args, $defaults);
        $query = new WP_Query($args);
        $items = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $items[] = $this->format_menu_item(get_post());
            }
            wp_reset_postdata();
        }

        return $items;
    }

    /**
     * Formatear item de menú para la app
     */
    private function format_menu_item($post) {
        $category = $this->get_cpt_category($post->post_type);

        $item = [
            'id' => $post->ID,
            'name' => $post->post_title,
            'description' => wp_trim_words($post->post_content, 30, '...'),
            'post_type' => $post->post_type,
            'category' => $category,
            'price' => $this->get_item_price($post->ID),
            'image' => null,
            'available' => true,
            'allergens' => $this->get_item_allergens($post->ID),
            'customizations' => $this->get_item_customizations($post->ID),
        ];

        // Imagen destacada
        if (has_post_thumbnail($post->ID)) {
            $image_id = get_post_thumbnail_id($post->ID);
            $item['image'] = [
                'url' => wp_get_attachment_image_url($image_id, 'large'),
                'thumbnail' => wp_get_attachment_image_url($image_id, 'thumbnail'),
                'medium' => wp_get_attachment_image_url($image_id, 'medium'),
                'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
            ];
        }

        // Disponibilidad
        $available = get_post_meta($post->ID, '_menu_item_available', true);
        if ($available !== '') {
            $item['available'] = (bool) $available;
        }

        return apply_filters('flavor_restaurant_format_menu_item', $item, $post);
    }

    /**
     * Obtener precio de un item
     */
    private function get_item_price($post_id) {
        // Intentar obtener precio de diferentes fuentes
        $price = get_post_meta($post_id, '_price', true);

        if (empty($price)) {
            $price = get_post_meta($post_id, 'price', true);
        }

        if (empty($price) && function_exists('wc_get_product')) {
            $product = wc_get_product($post_id);
            if ($product) {
                $price = $product->get_price();
            }
        }

        return $price ? floatval($price) : 0;
    }

    /**
     * Obtener alérgenos de un item
     */
    private function get_item_allergens($post_id) {
        $allergens = get_post_meta($post_id, '_allergens', true);

        if (empty($allergens)) {
            return [];
        }

        if (is_string($allergens)) {
            return array_map('trim', explode(',', $allergens));
        }

        return (array) $allergens;
    }

    /**
     * Obtener personalizaciones disponibles
     */
    private function get_item_customizations($post_id) {
        $customizations = get_post_meta($post_id, '_customizations', true);

        if (empty($customizations)) {
            return [];
        }

        return (array) $customizations;
    }

    /**
     * Obtener etiqueta de categoría
     */
    private function get_category_label($category) {
        $labels = [
            'dishes' => 'Platos',
            'drinks' => 'Bebidas',
            'desserts' => 'Postres',
        ];

        return $labels[$category] ?? ucfirst($category);
    }

    /**
     * Obtener icono de categoría
     */
    private function get_category_icon($category) {
        $icons = [
            'dishes' => 'restaurant',
            'drinks' => 'local_bar',
            'desserts' => 'cake',
        ];

        return $icons[$category] ?? 'restaurant_menu';
    }

    /**
     * Obtener color de categoría
     */
    private function get_category_color($category) {
        $colors = [
            'dishes' => '#FF5722',
            'drinks' => '#2196F3',
            'desserts' => '#E91E63',
        ];

        return $colors[$category] ?? '#9E9E9E';
    }

    /**
     * Obtener estados de pedido disponibles
     */
    public function get_order_statuses() {
        return $this->settings['order_statuses'];
    }

    /**
     * Validar estado de pedido
     */
    public function is_valid_status($status) {
        return isset($this->settings['order_statuses'][$status]);
    }

    /**
     * Calcular total con impuestos
     */
    public function calculate_total($subtotal) {
        $tax_rate = $this->settings['tax_rate'] / 100;
        $tax = $subtotal * $tax_rate;

        return [
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'total' => round($subtotal + $tax, 2),
            'tax_rate' => $this->settings['tax_rate']
        ];
    }

    /**
     * Formatear precio para mostrar
     */
    public function format_price($amount) {
        $symbol = $this->settings['currency_symbol'];
        $formatted = number_format($amount, 2, ',', '.');

        return $symbol . ' ' . $formatted;
    }
}
