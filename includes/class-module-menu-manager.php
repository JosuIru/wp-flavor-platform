<?php
/**
 * Gestor Automático de Menús de Módulos
 *
 * Crea y gestiona automáticamente los menús de WordPress cuando se activan/desactivan módulos
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Menu_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Nombre del menú principal
     */
    const MAIN_MENU_LOCATION = 'primary';
    const MAIN_MENU_NAME = 'Menu Principal Flavor';

    /**
     * Categorías de módulos para organización
     */
    private $module_categories = [
        'servicios' => [
            'label' => 'Servicios',
            'icon' => '🏪',
            'modules' => ['woocommerce', 'marketplace', 'bares', 'tienda_local', 'facturas'],
            'order' => 10,
        ],
        'comunidad' => [
            'label' => 'Comunidad',
            'icon' => '👥',
            'modules' => ['eventos', 'talleres', 'red_social', 'foros', 'comunidades', 'colectivos'],
            'order' => 20,
        ],
        'solidaridad' => [
            'label' => 'Solidaridad',
            'icon' => '🤝',
            'modules' => ['banco_tiempo', 'ayuda_vecinal', 'grupos_consumo'],
            'order' => 30,
        ],
        'espacios' => [
            'label' => 'Espacios',
            'icon' => '🏛️',
            'modules' => ['espacios_comunes', 'huertos_urbanos', 'parkings', 'bicicletas_compartidas'],
            'order' => 40,
        ],
        'cultura' => [
            'label' => 'Cultura',
            'icon' => '🎨',
            'modules' => ['biblioteca', 'multimedia', 'podcast', 'radio'],
            'order' => 50,
        ],
        'gestion' => [
            'label' => 'Gestión',
            'icon' => '⚙️',
            'modules' => ['incidencias', 'tramites', 'fichaje_empleados', 'socios', 'clientes'],
            'order' => 60,
        ],
        'participacion' => [
            'label' => 'Participación',
            'icon' => '🗳️',
            'modules' => ['participacion', 'presupuestos_participativos', 'transparencia', 'avisos_municipales'],
            'order' => 70,
        ],
        'sostenibilidad' => [
            'label' => 'Sostenibilidad',
            'icon' => '🌱',
            'modules' => ['reciclaje', 'compostaje', 'carpooling'],
            'order' => 80,
        ],
        'comunicacion' => [
            'label' => 'Comunicación',
            'icon' => '💬',
            'modules' => ['chat_grupos', 'chat_interno', 'email_marketing'],
            'order' => 90,
        ],
    ];

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
        // Hooks para activación/desactivación de módulos
        add_action('flavor_module_activated', [$this, 'on_module_activated'], 10, 1);
        add_action('flavor_module_deactivated', [$this, 'on_module_deactivated'], 10, 1);

        // Hook para regenerar menú completo
        add_action('admin_init', [$this, 'maybe_regenerate_menu']);

        // Registrar ubicación de menú si no existe
        add_action('after_setup_theme', [$this, 'register_menu_location']);
    }

    /**
     * Registra la ubicación del menú principal
     */
    public function register_menu_location() {
        register_nav_menus([
            self::MAIN_MENU_LOCATION => __('Menú Principal', 'flavor-chat-ia'),
        ]);
    }

    /**
     * Cuando se activa un módulo, añadirlo al menú
     */
    public function on_module_activated($module_id) {
        flavor_log_debug( "Módulo activado: {$module_id}", 'MenuManager' );
        $this->add_module_to_menu($module_id);
    }

    /**
     * Cuando se desactiva un módulo, quitarlo del menú
     */
    public function on_module_deactivated($module_id) {
        flavor_log_debug( "Módulo desactivado: {$module_id}", 'MenuManager' );
        $this->remove_module_from_menu($module_id);
    }

    /**
     * Verifica si hay que regenerar el menú (primera vez)
     */
    public function maybe_regenerate_menu() {
        if (get_option('flavor_menu_generated')) {
            return;
        }

        $this->regenerate_full_menu();
        update_option('flavor_menu_generated', time());
    }

    /**
     * Regenera el menú completo desde cero
     */
    public function regenerate_full_menu() {
        flavor_log_debug( 'Regenerando menú completo...', 'MenuManager' );

        // Obtener o crear el menú
        $menu_id = $this->get_or_create_menu();

        if (!$menu_id) {
            flavor_log_error( 'No se pudo crear el menú', 'MenuManager' );
            return false;
        }

        // Limpiar items existentes del plugin
        $this->clean_flavor_menu_items($menu_id);

        // Añadir items fijos
        $this->add_static_menu_items($menu_id);

        // Añadir módulos activos organizados por categorías
        $this->add_modules_by_categories($menu_id);

        // Asignar menú a la ubicación
        $locations = get_theme_mod('nav_menu_locations', []);
        $locations[self::MAIN_MENU_LOCATION] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);

        flavor_log_debug( 'Menú regenerado correctamente', 'MenuManager' );
        return true;
    }

    /**
     * Obtiene o crea el menú principal
     */
    private function get_or_create_menu() {
        $menu_name = self::MAIN_MENU_NAME;
        $menu = wp_get_nav_menu_object($menu_name);

        if (!$menu) {
            $menu_id = wp_create_nav_menu($menu_name);
            flavor_log_debug( "Menú creado: ID {$menu_id}", 'MenuManager' );
            return $menu_id;
        }

        return $menu->term_id;
    }

    /**
     * Limpia items del menú que pertenecen al plugin
     */
    private function clean_flavor_menu_items($menu_id) {
        $items = wp_get_nav_menu_items($menu_id);

        if (!$items) {
            return;
        }

        foreach ($items as $item) {
            // Solo eliminar items que tienen metadata de Flavor
            $is_flavor_item = get_post_meta($item->ID, '_flavor_menu_item', true);
            if ($is_flavor_item) {
                wp_delete_post($item->ID, true);
            }
        }
    }

    /**
     * Añade items estáticos al menú
     */
    private function add_static_menu_items($menu_id) {
        $static_items = [
            [
                'title' => __('Inicio', 'flavor-chat-ia'),
                'url' => home_url('/'),
                'order' => 1,
                'icon' => '🏠',
            ],
            [
                'title' => __('Mi Portal', 'flavor-chat-ia'),
                'url' => Flavor_Chat_Helpers::get_action_url('', ''),
                'order' => 2,
                'icon' => '👤',
                'require_login' => true,
            ],
            [
                'title' => __('Servicios', 'flavor-chat-ia'),
                'url' => home_url('/servicios/'),
                'order' => 3,
                'icon' => '📋',
            ],
        ];

        foreach ($static_items as $item_data) {
            $this->add_menu_item($menu_id, $item_data);
        }
    }

    /**
     * Añade módulos organizados por categorías
     */
    private function add_modules_by_categories($menu_id) {
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $active_modules = $loader->get_loaded_modules();

        // Agrupar módulos activos por categoría
        $modules_by_category = [];

        foreach ($active_modules as $module_id => $instance) {
            $category = $this->get_module_category($module_id);

            if (!isset($modules_by_category[$category])) {
                $modules_by_category[$category] = [];
            }

            $modules_by_category[$category][] = [
                'id' => $module_id,
                'instance' => $instance,
            ];
        }

        // Ordenar categorías por prioridad
        uksort($modules_by_category, function($a, $b) {
            $order_a = $this->module_categories[$a]['order'] ?? 999;
            $order_b = $this->module_categories[$b]['order'] ?? 999;
            return $order_a - $order_b;
        });

        // Crear items de menú por categoría
        $base_order = 100;

        foreach ($modules_by_category as $category_key => $modules) {
            $category = $this->module_categories[$category_key] ?? ['label' => ucfirst($category_key), 'icon' => '📁'];

            // Crear item padre de categoría
            $parent_item_data = [
                'title' => $category['label'],
                'url' => '#',
                'order' => $base_order,
                'icon' => $category['icon'] ?? '',
                'classes' => ['flavor-menu-category'],
            ];

            $parent_id = $this->add_menu_item($menu_id, $parent_item_data);
            $base_order += 100;

            // Añadir módulos como sub-items
            $sub_order = 1;
            foreach ($modules as $module_data) {
                $module_id = $module_data['id'];
                $instance = $module_data['instance'];

                $module_info = $this->get_module_info($module_id, $instance);

                $item_data = [
                    'title' => $module_info['name'],
                    'url' => home_url('/' . str_replace('_', '-', $module_id) . '/'),
                    'order' => $sub_order++,
                    'parent_id' => $parent_id,
                    'module_id' => $module_id,
                    'icon' => $module_info['icon'] ?? '',
                ];

                // Verificar permisos
                if (method_exists($instance, 'get_visibility')) {
                    $visibility = $instance->get_visibility();
                    if ($visibility === 'private' || $visibility === 'members_only') {
                        $item_data['require_login'] = true;
                    }
                }

                $this->add_menu_item($menu_id, $item_data);
            }
        }
    }

    /**
     * Añade un módulo individual al menú
     */
    private function add_module_to_menu($module_id) {
        $menu_id = $this->get_or_create_menu();

        if (!$menu_id) {
            return false;
        }

        // Obtener categoría del módulo
        $category_key = $this->get_module_category($module_id);
        $category = $this->module_categories[$category_key] ?? null;

        if (!$category) {
            flavor_log_debug( "WARN: Módulo '{$module_id}' sin categoría asignada", 'MenuManager' );
            return false;
        }

        // Buscar o crear item padre de categoría
        $parent_id = $this->find_or_create_category_item($menu_id, $category_key);

        // Obtener info del módulo
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $instance = $loader->get_module_instance($module_id);
        $module_info = $this->get_module_info($module_id, $instance);

        // Crear item del módulo
        $item_data = [
            'title' => $module_info['name'],
            'url' => home_url('/' . str_replace('_', '-', $module_id) . '/'),
            'parent_id' => $parent_id,
            'module_id' => $module_id,
            'icon' => $module_info['icon'] ?? '',
        ];

        return $this->add_menu_item($menu_id, $item_data);
    }

    /**
     * Elimina un módulo del menú
     */
    private function remove_module_from_menu($module_id) {
        $menu_id = $this->get_or_create_menu();

        if (!$menu_id) {
            return false;
        }

        $items = wp_get_nav_menu_items($menu_id);

        if (!$items) {
            return false;
        }

        foreach ($items as $item) {
            $item_module_id = get_post_meta($item->ID, '_flavor_module_id', true);

            if ($item_module_id === $module_id) {
                wp_delete_post($item->ID, true);
                flavor_log_debug( "Item eliminado: {$module_id}", 'MenuManager' );
            }
        }

        return true;
    }

    /**
     * Busca o crea item de categoría
     */
    private function find_or_create_category_item($menu_id, $category_key) {
        $items = wp_get_nav_menu_items($menu_id);
        $category = $this->module_categories[$category_key];

        // Buscar si ya existe
        if ($items) {
            foreach ($items as $item) {
                $item_category = get_post_meta($item->ID, '_flavor_category_key', true);
                if ($item_category === $category_key) {
                    return $item->ID;
                }
            }
        }

        // Crear nuevo
        $item_data = [
            'title' => $category['label'],
            'url' => '#',
            'order' => $category['order'],
            'icon' => $category['icon'] ?? '',
            'category_key' => $category_key,
            'classes' => ['flavor-menu-category'],
        ];

        return $this->add_menu_item($menu_id, $item_data);
    }

    /**
     * Añade un item al menú de WordPress
     */
    private function add_menu_item($menu_id, $data) {
        $defaults = [
            'title' => '',
            'url' => '',
            'order' => 0,
            'parent_id' => 0,
            'classes' => [],
            'icon' => '',
            'module_id' => '',
            'category_key' => '',
            'require_login' => false,
        ];

        $data = wp_parse_args($data, $defaults);

        $item_id = wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title' => $data['title'],
            'menu-item-url' => $data['url'],
            'menu-item-status' => 'publish',
            'menu-item-type' => 'custom',
            'menu-item-position' => $data['order'],
            'menu-item-parent-id' => $data['parent_id'],
            'menu-item-classes' => implode(' ', $data['classes']),
        ]);

        if (is_wp_error($item_id)) {
            flavor_log_error( 'Error al crear item: ' . $item_id->get_error_message(), 'MenuManager' );
            return false;
        }

        // Añadir metadata
        update_post_meta($item_id, '_flavor_menu_item', true);

        if ($data['module_id']) {
            update_post_meta($item_id, '_flavor_module_id', $data['module_id']);
        }

        if ($data['category_key']) {
            update_post_meta($item_id, '_flavor_category_key', $data['category_key']);
        }

        if ($data['icon']) {
            update_post_meta($item_id, '_flavor_icon', $data['icon']);
        }

        if ($data['require_login']) {
            update_post_meta($item_id, '_flavor_require_login', true);
        }

        return $item_id;
    }

    /**
     * Obtiene la categoría de un módulo
     */
    private function get_module_category($module_id) {
        foreach ($this->module_categories as $category_key => $category) {
            if (in_array($module_id, $category['modules'])) {
                return $category_key;
            }
        }

        return 'otros'; // Categoría por defecto
    }

    /**
     * Obtiene información de un módulo
     */
    private function get_module_info($module_id, $instance = null) {
        $default = [
            'name' => ucfirst(str_replace(['_', '-'], ' ', $module_id)),
            'icon' => '📄',
        ];

        if (!$instance) {
            return $default;
        }

        $info = [
            'name' => method_exists($instance, 'get_name') ? $instance->get_name() : $default['name'],
            'icon' => $this->get_module_icon($module_id),
        ];

        return $info;
    }

    /**
     * Obtiene icono de un módulo
     */
    private function get_module_icon($module_id) {
        $icons = [
            // Servicios
            'woocommerce' => '🛒',
            'marketplace' => '🏪',
            'bares' => '🍺',
            'tienda_local' => '🏬',
            'facturas' => '📄',

            // Comunidad
            'eventos' => '📅',
            'talleres' => '🎨',
            'red_social' => '👥',
            'foros' => '💬',
            'comunidades' => '🏘️',
            'colectivos' => '👨‍👩‍👧‍👦',

            // Solidaridad
            'banco_tiempo' => '⏰',
            'ayuda_vecinal' => '🤝',
            'grupos_consumo' => '🌱',

            // Espacios
            'espacios_comunes' => '🏛️',
            'huertos_urbanos' => '🌻',
            'parkings' => '🅿️',
            'bicicletas_compartidas' => '🚴',

            // Cultura
            'biblioteca' => '📚',
            'multimedia' => '🎬',
            'podcast' => '🎙️',
            'radio' => '📻',

            // Gestión
            'incidencias' => '🔧',
            'tramites' => '📋',
            'fichaje_empleados' => '🕐',
            'socios' => '👤',
            'clientes' => '👥',

            // Participación
            'participacion' => '🗳️',
            'presupuestos_participativos' => '💰',
            'transparencia' => '🔍',
            'avisos_municipales' => '📢',

            // Sostenibilidad
            'reciclaje' => '♻️',
            'compostaje' => '🌱',
            'carpooling' => '🚗',

            // Comunicación
            'chat_grupos' => '💬',
            'chat_interno' => '💭',
            'email_marketing' => '📧',
        ];

        return $icons[$module_id] ?? '📄';
    }

    /**
     * Fuerza regeneración del menú (método público para usar desde admin)
     */
    public function force_regenerate() {
        delete_option('flavor_menu_generated');
        return $this->regenerate_full_menu();
    }
}

// Inicializar
Flavor_Module_Menu_Manager::get_instance();
