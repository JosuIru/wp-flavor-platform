<?php
/**
 * Frontend Controller para Huella Ecológica
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Huella Ecológica
 */
class Flavor_Huella_Ecologica_Frontend_Controller {

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
            'flavor-huella-ecologica',
            $base_url . '/assets/css/huella-ecologica.css',
            ['flavor-modules-common'],
            $version
        );

        // JS
        wp_register_script(
            'flavor-huella-ecologica',
            $base_url . '/assets/js/huella-ecologica.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-huella-ecologica', 'flavorHuellaEcologica', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('huella-ecologica_nonce'),
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
        wp_enqueue_style('flavor-huella-ecologica');
        wp_enqueue_script('flavor-huella-ecologica');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        if (!shortcode_exists('huella-ecologica_listado')) {
            add_shortcode('huella-ecologica_listado', [$this, 'shortcode_listado']);
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
        echo '<div class="flavor-huella-ecologica-listado">';
        echo '<p>' . __('Módulo Huella Ecológica - Listado', 'flavor-chat-ia') . '</p>';
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['huella-ecologica'] = [
            'titulo' => __('Huella Ecológica', 'flavor-chat-ia'),
            'icono' => 'dashicons-admin-generic',
            'callback' => [$this, 'render_tab_principal'],
            'orden' => 50,
            'modulo' => 'huella-ecologica',
        ];

        return $tabs;
    }

    /**
     * Renderizar tab principal
     */
    public function render_tab_principal() {
        $this->encolar_assets();
        echo '<div class="flavor-huella-ecologica-tab">';
        echo '<h3>' . esc_html__('Huella Ecológica', 'flavor-chat-ia') . '</h3>';
        echo '<p>' . esc_html__('Contenido del tab de Huella Ecológica.', 'flavor-chat-ia') . '</p>';
        echo '</div>';
    }
}
