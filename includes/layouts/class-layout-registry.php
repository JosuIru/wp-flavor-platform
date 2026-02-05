<?php
/**
 * Layout Registry - Sistema de Layouts Predefinidos
 *
 * Gestiona menús y footers predefinidos seleccionables
 * para web y APKs móviles.
 *
 * @package Flavor_Chat_IA
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Layout_Registry {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Menús registrados
     */
    private $menus = [];

    /**
     * Footers registrados
     */
    private $footers = [];

    /**
     * Layouts completos (menú + footer)
     */
    private $layouts = [];

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
        $this->register_default_menus();
        $this->register_default_footers();
        $this->register_default_layouts();

        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_layout_styles']);
    }

    /**
     * Inicialización
     */
    public function init() {
        // Permitir que otros plugins registren layouts personalizados
        do_action('flavor_register_layouts', $this);
    }

    /**
     * Registrar menús predefinidos
     */
    private function register_default_menus() {
        // 1. Classic - Logo izquierda + menú horizontal + CTA
        $this->menus['classic'] = [
            'id' => 'classic',
            'name' => __('Classic', 'flavor-chat-ia'),
            'description' => __('Logo a la izquierda con menú horizontal y botón de acción', 'flavor-chat-ia'),
            'icon' => 'dashicons-menu-alt3',
            'preview_image' => 'menu-classic.svg',
            'recommended_for' => ['tienda', 'marketplace', 'coworking'],
            'supports' => ['logo', 'menu', 'cta_button', 'search', 'user_menu'],
            'mobile_behavior' => 'hamburger',
            'settings' => [
                'logo_position' => 'left',
                'menu_position' => 'center',
                'cta_position' => 'right',
                'sticky' => true,
                'transparent_on_hero' => false,
            ],
        ];

        // 2. Centered - Logo centrado + menú debajo
        $this->menus['centered'] = [
            'id' => 'centered',
            'name' => __('Centered', 'flavor-chat-ia'),
            'description' => __('Logo centrado con menú horizontal debajo', 'flavor-chat-ia'),
            'icon' => 'dashicons-align-center',
            'preview_image' => 'menu-centered.svg',
            'recommended_for' => ['restaurante', 'comunidad', 'grupo-consumo'],
            'supports' => ['logo', 'menu', 'social_icons', 'contact_info'],
            'mobile_behavior' => 'hamburger',
            'settings' => [
                'logo_position' => 'center',
                'menu_position' => 'center',
                'show_topbar' => true,
                'sticky' => true,
                'transparent_on_hero' => true,
            ],
        ];

        // 3. Sidebar - Menú hamburguesa + drawer lateral
        $this->menus['sidebar'] = [
            'id' => 'sidebar',
            'name' => __('Sidebar', 'flavor-chat-ia'),
            'description' => __('Menú lateral deslizable tipo app móvil', 'flavor-chat-ia'),
            'icon' => 'dashicons-menu',
            'preview_image' => 'menu-sidebar.svg',
            'recommended_for' => ['barrio', 'ayuntamiento', 'banco-tiempo'],
            'supports' => ['logo', 'menu', 'user_avatar', 'notifications', 'search'],
            'mobile_behavior' => 'sidebar',
            'settings' => [
                'sidebar_position' => 'left',
                'overlay_color' => 'rgba(0,0,0,0.5)',
                'sidebar_width' => '280px',
                'show_user_section' => true,
                'sticky' => true,
            ],
        ];

        // 4. Bottom Nav - Navegación inferior tipo app
        $this->menus['bottom-nav'] = [
            'id' => 'bottom-nav',
            'name' => __('Bottom Navigation', 'flavor-chat-ia'),
            'description' => __('Navegación inferior estilo aplicación móvil', 'flavor-chat-ia'),
            'icon' => 'dashicons-smartphone',
            'preview_image' => 'menu-bottom-nav.svg',
            'recommended_for' => ['barrio', 'comunidad', 'marketplace'],
            'supports' => ['icons', 'labels', 'badges', 'active_indicator'],
            'mobile_behavior' => 'bottom-nav',
            'settings' => [
                'max_items' => 5,
                'show_labels' => true,
                'icon_style' => 'outlined',
                'active_indicator' => 'dot',
                'hide_on_scroll' => false,
            ],
        ];

        // 5. Mega Menu - Menú expandible con categorías
        $this->menus['mega-menu'] = [
            'id' => 'mega-menu',
            'name' => __('Mega Menu', 'flavor-chat-ia'),
            'description' => __('Menú desplegable con múltiples columnas y categorías', 'flavor-chat-ia'),
            'icon' => 'dashicons-grid-view',
            'preview_image' => 'menu-mega.svg',
            'recommended_for' => ['marketplace', 'ayuntamiento', 'tienda'],
            'supports' => ['logo', 'mega_dropdowns', 'featured_items', 'search', 'cta_button'],
            'mobile_behavior' => 'hamburger',
            'settings' => [
                'columns_per_dropdown' => 4,
                'show_featured_image' => true,
                'dropdown_animation' => 'fade',
                'sticky' => true,
            ],
        ];

        // 6. Minimal - Solo logo + hamburguesa
        $this->menus['minimal'] = [
            'id' => 'minimal',
            'name' => __('Minimal', 'flavor-chat-ia'),
            'description' => __('Diseño minimalista con solo logo y menú hamburguesa', 'flavor-chat-ia'),
            'icon' => 'dashicons-minus',
            'preview_image' => 'menu-minimal.svg',
            'recommended_for' => ['restaurante', 'coworking'],
            'supports' => ['logo', 'hamburger', 'dark_mode_toggle'],
            'mobile_behavior' => 'hamburger',
            'settings' => [
                'hamburger_style' => 'animated',
                'fullscreen_menu' => true,
                'transparent_on_hero' => true,
                'sticky' => false,
            ],
        ];
    }

    /**
     * Registrar footers predefinidos
     */
    private function register_default_footers() {
        // 1. Multi-column - 4 columnas con enlaces + redes sociales
        $this->footers['multi-column'] = [
            'id' => 'multi-column',
            'name' => __('Multi-Column', 'flavor-chat-ia'),
            'description' => __('Footer con múltiples columnas de enlaces y redes sociales', 'flavor-chat-ia'),
            'icon' => 'dashicons-columns',
            'preview_image' => 'footer-multi-column.svg',
            'recommended_for' => ['tienda', 'marketplace', 'ayuntamiento'],
            'supports' => ['logo', 'columns', 'social_icons', 'newsletter', 'copyright'],
            'settings' => [
                'columns' => 4,
                'show_logo' => true,
                'show_social' => true,
                'show_newsletter' => false,
                'background_style' => 'dark',
            ],
        ];

        // 2. Compact - Una línea con copyright + enlaces básicos
        $this->footers['compact'] = [
            'id' => 'compact',
            'name' => __('Compact', 'flavor-chat-ia'),
            'description' => __('Footer compacto de una línea con lo esencial', 'flavor-chat-ia'),
            'icon' => 'dashicons-minus',
            'preview_image' => 'footer-compact.svg',
            'recommended_for' => ['barrio', 'comunidad', 'grupo-consumo'],
            'supports' => ['copyright', 'links', 'social_icons'],
            'settings' => [
                'layout' => 'inline',
                'show_logo' => false,
                'background_style' => 'transparent',
            ],
        ];

        // 3. Newsletter - Footer con suscripción destacada
        $this->footers['newsletter'] = [
            'id' => 'newsletter',
            'name' => __('Newsletter', 'flavor-chat-ia'),
            'description' => __('Footer centrado en capturar suscriptores', 'flavor-chat-ia'),
            'icon' => 'dashicons-email-alt',
            'preview_image' => 'footer-newsletter.svg',
            'recommended_for' => ['comunidad', 'grupo-consumo', 'tienda'],
            'supports' => ['newsletter_form', 'logo', 'social_icons', 'copyright'],
            'settings' => [
                'newsletter_position' => 'top',
                'show_benefits' => true,
                'background_style' => 'gradient',
            ],
        ];

        // 4. Contact - Mapa + datos de contacto prominentes
        $this->footers['contact'] = [
            'id' => 'contact',
            'name' => __('Contact', 'flavor-chat-ia'),
            'description' => __('Footer con información de contacto y mapa', 'flavor-chat-ia'),
            'icon' => 'dashicons-location',
            'preview_image' => 'footer-contact.svg',
            'recommended_for' => ['restaurante', 'coworking', 'tienda'],
            'supports' => ['map', 'address', 'phone', 'email', 'hours', 'social_icons'],
            'settings' => [
                'show_map' => true,
                'map_height' => '200px',
                'contact_layout' => 'cards',
                'background_style' => 'light',
            ],
        ];

        // 5. App Download - Botones de descarga de APKs
        $this->footers['app-download'] = [
            'id' => 'app-download',
            'name' => __('App Download', 'flavor-chat-ia'),
            'description' => __('Footer destacando la descarga de aplicaciones móviles', 'flavor-chat-ia'),
            'icon' => 'dashicons-smartphone',
            'preview_image' => 'footer-app-download.svg',
            'recommended_for' => ['marketplace', 'comunidad', 'barrio'],
            'supports' => ['app_buttons', 'qr_code', 'features_list', 'social_icons', 'copyright'],
            'settings' => [
                'show_qr' => true,
                'show_features' => true,
                'app_store_url' => '',
                'play_store_url' => '',
                'background_style' => 'dark',
            ],
        ];
    }

    /**
     * Registrar layouts completos (combinaciones de menú + footer)
     */
    private function register_default_layouts() {
        $this->layouts = [
            'tienda' => [
                'name' => __('Tienda Online', 'flavor-chat-ia'),
                'menu' => 'classic',
                'footer' => 'multi-column',
            ],
            'restaurante' => [
                'name' => __('Restaurante', 'flavor-chat-ia'),
                'menu' => 'centered',
                'footer' => 'contact',
            ],
            'comunidad' => [
                'name' => __('Comunidad', 'flavor-chat-ia'),
                'menu' => 'sidebar',
                'footer' => 'newsletter',
            ],
            'barrio' => [
                'name' => __('Barrio', 'flavor-chat-ia'),
                'menu' => 'bottom-nav',
                'footer' => 'compact',
            ],
            'marketplace' => [
                'name' => __('Marketplace', 'flavor-chat-ia'),
                'menu' => 'mega-menu',
                'footer' => 'app-download',
            ],
            'ayuntamiento' => [
                'name' => __('Ayuntamiento', 'flavor-chat-ia'),
                'menu' => 'mega-menu',
                'footer' => 'multi-column',
            ],
            'coworking' => [
                'name' => __('Coworking', 'flavor-chat-ia'),
                'menu' => 'minimal',
                'footer' => 'contact',
            ],
            'banco-tiempo' => [
                'name' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'menu' => 'sidebar',
                'footer' => 'compact',
            ],
            'grupo-consumo' => [
                'name' => __('Grupo de Consumo', 'flavor-chat-ia'),
                'menu' => 'centered',
                'footer' => 'newsletter',
            ],
        ];
    }

    /**
     * Obtener todos los menús
     */
    public function get_menus() {
        return apply_filters('flavor_layout_menus', $this->menus);
    }

    /**
     * Obtener un menú específico
     */
    public function get_menu($menu_id) {
        return isset($this->menus[$menu_id]) ? $this->menus[$menu_id] : null;
    }

    /**
     * Obtener todos los footers
     */
    public function get_footers() {
        return apply_filters('flavor_layout_footers', $this->footers);
    }

    /**
     * Obtener un footer específico
     */
    public function get_footer($footer_id) {
        return isset($this->footers[$footer_id]) ? $this->footers[$footer_id] : null;
    }

    /**
     * Obtener todos los layouts
     */
    public function get_layouts() {
        return apply_filters('flavor_layouts', $this->layouts);
    }

    /**
     * Obtener layout activo
     */
    public function get_active_layout() {
        $settings = get_option('flavor_layout_settings', []);
        return [
            'menu' => isset($settings['active_menu']) ? $settings['active_menu'] : 'classic',
            'footer' => isset($settings['active_footer']) ? $settings['active_footer'] : 'multi-column',
        ];
    }

    /**
     * Guardar layout activo
     */
    public function set_active_layout($menu_id, $footer_id) {
        $settings = get_option('flavor_layout_settings', []);
        $settings['active_menu'] = sanitize_key($menu_id);
        $settings['active_footer'] = sanitize_key($footer_id);
        update_option('flavor_layout_settings', $settings);
    }

    /**
     * Obtener configuración de layout para menú específico
     */
    public function get_menu_settings($menu_id) {
        $settings = get_option('flavor_layout_settings', []);
        $menu_settings = isset($settings['menu_settings'][$menu_id]) ? $settings['menu_settings'][$menu_id] : [];

        // Combinar con defaults
        $menu = $this->get_menu($menu_id);
        if ($menu && isset($menu['settings'])) {
            return array_merge($menu['settings'], $menu_settings);
        }

        return $menu_settings;
    }

    /**
     * Obtener configuración de layout para footer específico
     */
    public function get_footer_settings($footer_id) {
        $settings = get_option('flavor_layout_settings', []);
        $footer_settings = isset($settings['footer_settings'][$footer_id]) ? $settings['footer_settings'][$footer_id] : [];

        // Combinar con defaults
        $footer = $this->get_footer($footer_id);
        if ($footer && isset($footer['settings'])) {
            return array_merge($footer['settings'], $footer_settings);
        }

        return $footer_settings;
    }

    /**
     * Exportar configuración para APKs (JSON)
     */
    public function export_for_mobile() {
        $active_layout = $this->get_active_layout();
        $menu = $this->get_menu($active_layout['menu']);
        $footer = $this->get_footer($active_layout['footer']);

        $design_settings = get_option('flavor_design_settings', []);

        return [
            'version' => '1.0',
            'exported_at' => current_time('c'),
            'menu' => [
                'type' => $active_layout['menu'],
                'config' => $this->get_menu_settings($active_layout['menu']),
                'mobile_behavior' => $menu['mobile_behavior'] ?? 'hamburger',
            ],
            'footer' => [
                'type' => $active_layout['footer'],
                'config' => $this->get_footer_settings($active_layout['footer']),
            ],
            'theme' => [
                'primary_color' => $design_settings['primary_color'] ?? '#3b82f6',
                'secondary_color' => $design_settings['secondary_color'] ?? '#8b5cf6',
                'accent_color' => $design_settings['accent_color'] ?? '#f59e0b',
                'background_color' => $design_settings['background_color'] ?? '#ffffff',
                'text_color' => $design_settings['text_color'] ?? '#1f2937',
                'font_family' => $design_settings['body_font'] ?? 'Inter',
            ],
            'navigation_items' => $this->get_navigation_items_for_mobile(),
        ];
    }

    /**
     * Obtener items de navegación para móvil
     */
    private function get_navigation_items_for_mobile() {
        $menu_locations = get_nav_menu_locations();
        $menu_items = [];

        if (isset($menu_locations['primary'])) {
            $menu_object = wp_get_nav_menu_object($menu_locations['primary']);
            if ($menu_object) {
                $items = wp_get_nav_menu_items($menu_object->term_id);
                foreach ($items as $item) {
                    $menu_items[] = [
                        'id' => $item->ID,
                        'title' => $item->title,
                        'url' => $item->url,
                        'icon' => get_post_meta($item->ID, '_menu_item_icon', true) ?: 'home',
                        'parent' => $item->menu_item_parent,
                    ];
                }
            }
        }

        return $menu_items;
    }

    /**
     * Encolar estilos del layout activo
     */
    public function enqueue_layout_styles() {
        $active_layout = $this->get_active_layout();

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-layouts',
            FLAVOR_CHAT_IA_URL . "assets/css/layouts{$sufijo_asset}.css",
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-layouts',
            FLAVOR_CHAT_IA_URL . "assets/js/layouts{$sufijo_asset}.js",
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-layouts', 'flavorLayoutConfig', [
            'menu' => $active_layout['menu'],
            'footer' => $active_layout['footer'],
            'menuSettings' => $this->get_menu_settings($active_layout['menu']),
            'footerSettings' => $this->get_footer_settings($active_layout['footer']),
        ]);

        // Note: flavorLayouts is localized by Flavor_Layout_Forms with AJAX data
    }

    /**
     * Registrar un menú personalizado
     */
    public function register_menu($menu_id, $menu_config) {
        $this->menus[$menu_id] = array_merge([
            'id' => $menu_id,
            'name' => $menu_id,
            'description' => '',
            'icon' => 'dashicons-menu',
            'preview_image' => '',
            'recommended_for' => [],
            'supports' => [],
            'mobile_behavior' => 'hamburger',
            'settings' => [],
        ], $menu_config);
    }

    /**
     * Registrar un footer personalizado
     */
    public function register_footer($footer_id, $footer_config) {
        $this->footers[$footer_id] = array_merge([
            'id' => $footer_id,
            'name' => $footer_id,
            'description' => '',
            'icon' => 'dashicons-editor-insertmore',
            'preview_image' => '',
            'recommended_for' => [],
            'supports' => [],
            'settings' => [],
        ], $footer_config);
    }
}

/**
 * Función helper para obtener la instancia
 */
function flavor_layout_registry() {
    return Flavor_Layout_Registry::get_instance();
}
