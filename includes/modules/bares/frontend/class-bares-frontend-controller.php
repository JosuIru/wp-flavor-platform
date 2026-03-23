<?php
/**
 * Frontend Controller para Bares
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Bares
 */
class Flavor_Bares_Frontend_Controller {

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
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        // CSS
        wp_register_style(
            'flavor-bares',
            $base_url . '/assets/css/bares.css',
            ['flavor-modules-common'],
            $version
        );

        // JS
        wp_register_script(
            'flavor-bares',
            $base_url . '/assets/js/bares.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-bares', 'flavorBares', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bares_nonce'),
            'i18n' => [
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encolar assets cuando sea necesario
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-bares');
        wp_enqueue_script('flavor-bares');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        if (!shortcode_exists('bares_listado')) {
            add_shortcode('bares_listado', [$this, 'shortcode_listado']);
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
        echo '<div class="flavor-bares-listado">';
        echo '<p>' . __('Módulo Bares - Listado', 'flavor-chat-ia') . '</p>';
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['bares'] = [
            'titulo' => __('Bares', 'flavor-chat-ia'),
            'icono' => 'dashicons-admin-generic',
            'callback' => [$this, 'render_tab_principal'],
            'orden' => 50,
            'modulo' => 'bares',
        ];

        return $tabs;
    }

    /**
     * Renderizar tab principal
     */
    public function render_tab_principal() {
        $this->encolar_assets();
        echo '<div class="flavor-bares-tab">';
        echo '<h3>' . esc_html__('Bares', 'flavor-chat-ia') . '</h3>';
        echo '<p>' . esc_html__('Contenido del tab de Bares.', 'flavor-chat-ia') . '</p>';
        echo '</div>';
    }
}
