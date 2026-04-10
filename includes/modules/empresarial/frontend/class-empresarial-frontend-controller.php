<?php
/**
 * Frontend Controller para Empresarial
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Empresarial
 */
class Flavor_Empresarial_Frontend_Controller {

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
            'flavor-empresarial',
            $base_url . '/assets/css/empresarial.css',
            ['flavor-modules-common'],
            $version
        );

        // JS
        wp_register_script(
            'flavor-empresarial',
            $base_url . '/assets/js/empresarial.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-empresarial', 'flavorEmpresarial', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('empresarial_nonce'),
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
        wp_enqueue_style('flavor-empresarial');
        wp_enqueue_script('flavor-empresarial');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        if (!shortcode_exists('empresarial_listado')) {
            add_shortcode('empresarial_listado', [$this, 'shortcode_listado']);
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
        echo '<div class="flavor-empresarial-listado">';
        echo '<p>' . __('Módulo Empresarial - Listado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['empresarial'] = [
            'titulo' => __('Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-admin-generic',
            'callback' => [$this, 'render_tab_principal'],
            'orden' => 50,
            'modulo' => 'empresarial',
        ];

        return $tabs;
    }

    /**
     * Renderizar tab principal
     */
    public function render_tab_principal() {
        $this->encolar_assets();
        echo '<div class="flavor-empresarial-tab">';
        echo '<h3>' . esc_html__('Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        echo '<p>' . esc_html__('Contenido del tab de Empresarial.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        echo '</div>';
    }
}
