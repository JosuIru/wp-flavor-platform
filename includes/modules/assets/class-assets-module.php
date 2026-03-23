<?php
/**
 * Módulo de Assets y Recursos Compartidos
 *
 * Gestiona recursos CSS, JS y plantillas compartidas entre módulos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase del módulo de Assets
 */
class Flavor_Assets_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'assets';
        $this->name = 'Assets y Recursos';
        $this->description = 'Gestión de recursos compartidos (CSS, JS, plantillas) para todos los módulos';
        $this->icon = 'dashicons-media-code';
        $this->color = '#8b5cf6';

        parent::__construct();
        $this->cargar_frontend_controller();
    }

    /**
     * Inicializar módulo
     */
    public function init() {
        // Registrar assets comunes
        add_action('admin_enqueue_scripts', [$this, 'registrar_assets_admin']);
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets_frontend']);

        // Shortcodes de utilidad
        add_shortcode('flavor_icon', [$this, 'shortcode_icon']);
        add_shortcode('flavor_badge', [$this, 'shortcode_badge']);

        // Admin pages
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);
    }

    /**
     * Registrar páginas admin del módulo.
     *
     * @return void
     */
    public function registrar_paginas_admin() {
        add_submenu_page(
            'flavor-chat-ia',
            __('Assets y Recursos', 'flavor-chat-ia'),
            __('Assets', 'flavor-chat-ia'),
            'manage_options',
            'flavor-assets',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Render admin page.
     *
     * @return void
     */
    public function render_admin_page() {
        echo '<div class="wrap flavor-modulo-page">';
        if (method_exists($this, 'render_page_header')) {
            $this->render_page_header(__('Assets y Recursos', 'flavor-chat-ia'));
        }
        $vista = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($vista)) {
            include $vista;
        } else {
            echo '<div class="notice notice-warning inline"><p>' . esc_html__('El panel de assets no está disponible en este momento.', 'flavor-chat-ia') . '</p></div>';
        }
        echo '</div>';
    }

    /**
     * Configuración admin canónica.
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'assets',
            'label' => __('Assets y Recursos', 'flavor-chat-ia'),
            'icon' => 'dashicons-media-code',
            'capability' => 'manage_options',
            'categoria' => 'recursos',
            'paginas' => [
                [
                    'slug' => 'flavor-assets',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_page'],
                ],
            ],
        ];
    }

    /**
     * Registrar assets de admin
     */
    public function registrar_assets_admin() {
        $base_url = plugins_url('', __FILE__);
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        // CSS común de admin
        wp_enqueue_style(
            'flavor-admin-common',
            $base_url . '/css/admin-common.css',
            [],
            $version
        );
    }

    /**
     * Registrar assets de frontend
     */
    public function registrar_assets_frontend() {
        $base_url = plugins_url('', __FILE__);
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        // Registrar (no encolar) CSS de utilidades
        wp_register_style(
            'flavor-utilities',
            $base_url . '/css/utilities.css',
            [],
            $version
        );

        // Registrar helpers JS
        wp_register_script(
            'flavor-helpers',
            $base_url . '/js/helpers.js',
            ['jquery'],
            $version,
            true
        );
    }

    /**
     * Shortcode: Icono
     * Uso: [flavor_icon icon="dashicons-star" color="#f59e0b"]
     */
    public function shortcode_icon($atts) {
        $atts = shortcode_atts([
            'icon' => 'dashicons-admin-generic',
            'color' => '#374151',
            'size' => '20',
        ], $atts);

        return sprintf(
            '<span class="dashicons %s" style="color: %s; font-size: %spx; width: %spx; height: %spx;"></span>',
            esc_attr($atts['icon']),
            esc_attr($atts['color']),
            esc_attr($atts['size']),
            esc_attr($atts['size']),
            esc_attr($atts['size'])
        );
    }

    /**
     * Shortcode: Badge
     * Uso: [flavor_badge text="Nuevo" color="green"]
     */
    public function shortcode_badge($atts) {
        $atts = shortcode_atts([
            'text' => '',
            'color' => 'blue',
        ], $atts);

        $colors = [
            'blue' => '#3b82f6',
            'green' => '#10b981',
            'yellow' => '#f59e0b',
            'red' => '#ef4444',
            'purple' => '#8b5cf6',
            'gray' => '#6b7280',
        ];

        $bg_color = $colors[$atts['color']] ?? $colors['blue'];

        return sprintf(
            '<span class="flavor-badge" style="background-color: %s; color: #fff; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; display: inline-block;">%s</span>',
            esc_attr($bg_color),
            esc_html($atts['text'])
        );
    }

    /**
     * Obtener plantilla compartida
     */
    public static function get_template($template_name, $variables = []) {
        $template_path = dirname(__FILE__) . '/templates/' . $template_name . '.php';

        if (!file_exists($template_path)) {
            return '';
        }

        extract($variables);

        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Cargar frontend controller
     */
    private function cargar_frontend_controller() {
        $archivo_controller = dirname(__FILE__) . '/frontend/class-assets-frontend-controller.php';
        if (file_exists($archivo_controller)) {
            require_once $archivo_controller;
            Flavor_Assets_Frontend_Controller::get_instance();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return __('El módulo Assets gestiona recursos compartidos (CSS, JS, plantillas) utilizados por otros módulos de Flavor Platform.', 'flavor-chat-ia');
    }

    /**
     * Configuración del renderer
     */
    public static function get_renderer_config(): array {
        return [
            'module' => 'assets',
            'title' => __('Assets y Recursos', 'flavor-chat-ia'),
            'subtitle' => __('Gestión de recursos compartidos del sistema.', 'flavor-chat-ia'),
            'icon' => '📦',
            'color' => 'purple',
            'tabs' => [
                'info' => [
                    'label' => __('Información', 'flavor-chat-ia'),
                    'icon' => 'ℹ️',
                    'content' => 'shortcode:assets_info',
                ],
            ],
            'features' => [
                'has_archive' => false,
                'has_single' => false,
                'has_dashboard' => true,
                'has_search' => false,
            ],
        ];
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    if (class_exists('Flavor_Chat_Module_Base')) {
        new Flavor_Assets_Module();
    }
}, 20);
