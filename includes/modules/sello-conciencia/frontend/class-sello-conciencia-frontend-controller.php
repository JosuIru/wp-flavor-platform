<?php
/**
 * Frontend Controller para Sello de Conciencia
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Sello de Conciencia
 */
class Flavor_Sello_Conciencia_Frontend_Controller {

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
            'flavor-sello-conciencia',
            $base_url . '/assets/css/sello-conciencia.css',
            ['flavor-modules-common'],
            $version
        );

        // JS
        wp_register_script(
            'flavor-sello-conciencia',
            $base_url . '/assets/js/sello-conciencia.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-sello-conciencia', 'flavorSelloConciencia', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sello-conciencia_nonce'),
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
        wp_enqueue_style('flavor-sello-conciencia');
        wp_enqueue_script('flavor-sello-conciencia');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        if (!shortcode_exists('sello-conciencia_listado')) {
            add_shortcode('sello-conciencia_listado', [$this, 'shortcode_listado']);
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
        echo '<div class="flavor-sello-conciencia-listado">';
        echo '<p>' . __('Módulo Sello de Conciencia - Listado', 'flavor-platform') . '</p>';
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['sello-conciencia'] = [
            'titulo' => __('Sello de Conciencia', 'flavor-platform'),
            'icono' => 'dashicons-admin-generic',
            'callback' => [$this, 'render_tab_principal'],
            'orden' => 50,
            'modulo' => 'sello-conciencia',
        ];

        return $tabs;
    }

    /**
     * Renderizar tab principal
     */
    public function render_tab_principal() {
        $this->encolar_assets();
        echo '<div class="flavor-sello-conciencia-tab">';
        echo '<h3>' . esc_html__('Sello de Conciencia', 'flavor-platform') . '</h3>';
        echo '<p>' . esc_html__('Contenido del tab de Sello de Conciencia.', 'flavor-platform') . '</p>';
        echo '</div>';
    }
}
