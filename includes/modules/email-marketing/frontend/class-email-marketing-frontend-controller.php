<?php
/**
 * Frontend Controller para Email Marketing
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Email Marketing
 */
class Flavor_Email_Marketing_Frontend_Controller {

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
            'flavor-email-marketing',
            $base_url . '/assets/css/email-marketing.css',
            ['flavor-modules-common'],
            $version
        );

        // JS
        wp_register_script(
            'flavor-email-marketing',
            $base_url . '/assets/js/email-marketing.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-email-marketing', 'flavorEmailMarketing', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('email-marketing_nonce'),
            'i18n' => [
                'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Encolar assets cuando sea necesario
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-email-marketing');
        wp_enqueue_script('flavor-email-marketing');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        if (!shortcode_exists('email-marketing_listado')) {
            add_shortcode('email-marketing_listado', [$this, 'shortcode_listado']);
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
        echo '<div class="flavor-email-marketing-listado">';
        echo '<p>' . __('Módulo Email Marketing - Listado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['email-marketing'] = [
            'titulo' => __('Email Marketing', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-admin-generic',
            'callback' => [$this, 'render_tab_principal'],
            'orden' => 50,
            'modulo' => 'email-marketing',
        ];

        return $tabs;
    }

    /**
     * Renderizar tab principal
     */
    public function render_tab_principal() {
        $this->encolar_assets();
        echo '<div class="flavor-email-marketing-tab">';
        echo '<h3>' . esc_html__('Email Marketing', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        echo '<p>' . esc_html__('Contenido del tab de Email Marketing.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        echo '</div>';
    }
}
