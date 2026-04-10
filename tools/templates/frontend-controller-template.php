<?php
/**
 * Frontend Controller para {{MODULE_NAME}}
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plantilla de controlador frontend para el módulo {{MODULE_NAME}}
 */
class Flavor_Module_Frontend_Controller_Template {

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
            'flavor-{{MODULE_SLUG}}',
            $base_url . '/assets/css/{{MODULE_SLUG}}.css',
            ['flavor-modules-common'],
            $version
        );

        // JS
        wp_register_script(
            'flavor-{{MODULE_SLUG}}',
            $base_url . '/assets/js/{{MODULE_SLUG}}.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-{{MODULE_SLUG}}', 'flavor{{MODULE_CAMEL}}', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('{{MODULE_SLUG}}_nonce'),
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
        wp_enqueue_style('flavor-{{MODULE_SLUG}}');
        wp_enqueue_script('flavor-{{MODULE_SLUG}}');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        if (!shortcode_exists('{{MODULE_SLUG}}_listado')) {
            add_shortcode('{{MODULE_SLUG}}_listado', [$this, 'shortcode_listado']);
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
        echo '<div class="flavor-{{MODULE_SLUG}}-listado">';
        echo '<p>' . __('Módulo {{MODULE_NAME}} - Listado', 'flavor-platform') . '</p>';
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['{{MODULE_SLUG}}'] = [
            'titulo' => __('{{MODULE_NAME}}', 'flavor-platform'),
            'icono' => 'dashicons-admin-generic',
            'callback' => [$this, 'render_tab_principal'],
            'orden' => 50,
            'modulo' => '{{MODULE_SLUG}}',
        ];

        return $tabs;
    }

    /**
     * Renderizar tab principal
     */
    public function render_tab_principal() {
        $this->encolar_assets();
        echo '<div class="flavor-{{MODULE_SLUG}}-tab">';
        echo '<h3>' . esc_html__('{{MODULE_NAME}}', 'flavor-platform') . '</h3>';
        echo '<p>' . esc_html__('Contenido del tab de {{MODULE_NAME}}.', 'flavor-platform') . '</p>';
        echo '</div>';
    }
}
