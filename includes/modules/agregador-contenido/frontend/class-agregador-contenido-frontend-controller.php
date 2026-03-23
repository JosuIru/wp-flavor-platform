<?php
/**
 * Frontend Controller para Agregador de Contenido
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Agregador de Contenido
 */
class Flavor_Agregador_Contenido_Frontend_Controller {

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
            'flavor-agregador-contenido',
            $base_url . '/assets/css/agregador-contenido.css',
            ['flavor-modules-common'],
            $version
        );

        // JS
        wp_register_script(
            'flavor-agregador-contenido',
            $base_url . '/assets/js/agregador-contenido.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-agregador-contenido', 'flavorAgregadorContenido', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('agregador-contenido_nonce'),
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
        wp_enqueue_style('flavor-agregador-contenido');
        wp_enqueue_script('flavor-agregador-contenido');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        if (!shortcode_exists('agregador-contenido_listado')) {
            add_shortcode('agregador-contenido_listado', [$this, 'shortcode_listado']);
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
        echo '<div class="flavor-agregador-contenido-listado">';
        echo '<p>' . __('Módulo Agregador de Contenido - Listado', 'flavor-chat-ia') . '</p>';
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['agregador-contenido'] = [
            'titulo' => __('Agregador de Contenido', 'flavor-chat-ia'),
            'icono' => 'dashicons-admin-generic',
            'callback' => [$this, 'render_tab_principal'],
            'orden' => 50,
            'modulo' => 'agregador-contenido',
        ];

        return $tabs;
    }

    /**
     * Renderizar tab principal
     */
    public function render_tab_principal() {
        $this->encolar_assets();
        echo '<div class="flavor-agregador-contenido-tab">';
        echo '<h3>' . esc_html__('Agregador de Contenido', 'flavor-chat-ia') . '</h3>';
        echo '<p>' . esc_html__('Contenido del tab de Agregador de Contenido.', 'flavor-chat-ia') . '</p>';
        echo '</div>';
    }
}
