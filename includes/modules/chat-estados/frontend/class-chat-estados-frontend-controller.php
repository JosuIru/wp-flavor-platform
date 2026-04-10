<?php
/**
 * Frontend Controller para Chat Estados
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Chat Estados
 */
class Flavor_Platform_Estados_Frontend_Controller {

    /**
     * Instancia única
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
     * Constructor privado
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Inicializar hooks y filtros
     */
    private function init() {
        // Registrar assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);

        // Registrar shortcodes
        add_action('init', [$this, 'registrar_shortcodes']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 10, 1);
    }

    /**
     * Registrar assets CSS y JS
     */
    public function registrar_assets() {
        $base_url = plugins_url('', dirname(dirname(__FILE__)));
        $version = FLAVOR_PLATFORM_VERSION ?? '1.0.0';

        // CSS
        wp_register_style(
            'flavor-chat-estados',
            $base_url . '/assets/css/chat-estados.css',
            ['flavor-modules-common'],
            $version
        );

        // JS
        wp_register_script(
            'flavor-chat-estados',
            $base_url . '/assets/js/chat-estados.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-chat-estados', 'flavorChatEstados', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chat-estados_nonce'),
            'i18n' => [
                'error' => __('Ha ocurrido un error', 'flavor-platform'),
                'cargando' => __('Cargando...', 'flavor-platform'),
            ],
        ]);
    }

    /**
     * Encolar assets cuando sea necesario
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-chat-estados');
        wp_enqueue_script('flavor-chat-estados');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        if (!shortcode_exists('chat-estados_listado')) {
            add_shortcode('chat-estados_listado', [$this, 'shortcode_listado']);
        }
    }

    /**
     * Shortcode: Listado
     */
    public function shortcode_listado($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'limite' => 12,
        ], $atts);

        ob_start();
        echo '<div class="flavor-chat-estados-listado">';
        echo '<p>' . __('Módulo Chat Estados - Listado', 'flavor-platform') . '</p>';
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['chat-estados'] = [
            'titulo' => __('Chat Estados', 'flavor-platform'),
            'icono' => 'dashicons-admin-generic',
            'callback' => [$this, 'render_tab_principal'],
            'orden' => 50,
            'modulo' => 'chat-estados',
        ];

        return $tabs;
    }

    /**
     * Renderizar tab principal
     */
    public function render_tab_principal() {
        $this->encolar_assets();
        echo '<div class="flavor-chat-estados-tab">';
        echo '<h3>' . esc_html__('Chat Estados', 'flavor-platform') . '</h3>';
        echo '<p>' . esc_html__('Contenido del tab de Chat Estados.', 'flavor-platform') . '</p>';
        echo '</div>';
    }
}

if (!class_exists('Flavor_Chat_Estados_Frontend_Controller', false)) {
    class_alias('Flavor_Platform_Estados_Frontend_Controller', 'Flavor_Chat_Estados_Frontend_Controller');
}
