<?php
/**
 * Frontend Controller para DEX Solana
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de DEX Solana
 */
class Flavor_Dex_Solana_Frontend_Controller {

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
            'flavor-dex-solana',
            $base_url . '/assets/css/dex-solana.css',
            ['flavor-modules-common'],
            $version
        );

        // JS
        wp_register_script(
            'flavor-dex-solana',
            $base_url . '/assets/js/dex-solana.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script
        wp_localize_script('flavor-dex-solana', 'flavorDexSolana', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dex-solana_nonce'),
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
        wp_enqueue_style('flavor-dex-solana');
        wp_enqueue_script('flavor-dex-solana');
    }

    /**
     * Registrar shortcodes del módulo
     */
    public function registrar_shortcodes() {
        if (!shortcode_exists('dex-solana_listado')) {
            add_shortcode('dex-solana_listado', [$this, 'shortcode_listado']);
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
        echo '<div class="flavor-dex-solana-listado">';
        echo '<p>' . __('Módulo DEX Solana - Listado', 'flavor-chat-ia') . '</p>';
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        $tabs['dex-solana'] = [
            'titulo' => __('DEX Solana', 'flavor-chat-ia'),
            'icono' => 'dashicons-admin-generic',
            'callback' => [$this, 'render_tab_principal'],
            'orden' => 50,
            'modulo' => 'dex-solana',
        ];

        return $tabs;
    }

    /**
     * Renderizar tab principal
     */
    public function render_tab_principal() {
        $this->encolar_assets();
        echo '<div class="flavor-dex-solana-tab">';
        echo '<h3>' . esc_html__('DEX Solana', 'flavor-chat-ia') . '</h3>';
        echo '<p>' . esc_html__('Contenido del tab de DEX Solana.', 'flavor-chat-ia') . '</p>';
        echo '</div>';
    }
}
