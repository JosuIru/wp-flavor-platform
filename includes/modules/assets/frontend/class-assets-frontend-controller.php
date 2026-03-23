<?php
/**
 * Frontend Controller del módulo Assets
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controlador de frontend para Assets
 */
class Flavor_Assets_Frontend_Controller {

    /**
     * Instancia única (Singleton)
     */
    private static $instance = null;

    /**
     * Obtener instancia única
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado (Singleton)
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Inicializar hooks
     */
    private function init() {
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);
        add_action('init', [$this, 'registrar_shortcodes']);
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 10, 1);
    }

    /**
     * Registrar assets de frontend
     */
    public function registrar_assets() {
        $base_url = plugins_url('', dirname(__FILE__));
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        // CSS público de assets
        wp_register_style(
            'flavor-assets-public',
            $base_url . '/css/assets-public.css',
            [],
            $version
        );

        // JS público de assets
        wp_register_script(
            'flavor-assets-public',
            $base_url . '/js/assets-public.js',
            ['jquery'],
            $version,
            true
        );
    }

    /**
     * Registrar shortcodes
     */
    public function registrar_shortcodes() {
        add_shortcode('assets_info', [$this, 'shortcode_info']);
        add_shortcode('assets_listado', [$this, 'shortcode_listado']);
    }

    /**
     * Shortcode: Información de assets
     */
    public function shortcode_info($atts) {
        wp_enqueue_style('flavor-assets-public');

        ob_start();
        ?>
        <div class="flavor-assets-info">
            <div class="assets-info-header">
                <h3>Assets y Recursos Compartidos</h3>
                <p>Sistema de recursos centralizados para todos los módulos de Flavor Platform</p>
            </div>
            <div class="assets-info-grid">
                <div class="info-card">
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <h4>CSS Compartido</h4>
                    <p>Estilos comunes reutilizables en admin y frontend</p>
                </div>
                <div class="info-card">
                    <span class="dashicons dashicons-media-code"></span>
                    <h4>JavaScript Helpers</h4>
                    <p>Funciones JS compartidas entre módulos</p>
                </div>
                <div class="info-card">
                    <span class="dashicons dashicons-editor-code"></span>
                    <h4>Templates</h4>
                    <p>Sistema de plantillas compartidas</p>
                </div>
                <div class="info-card">
                    <span class="dashicons dashicons-shortcode"></span>
                    <h4>Shortcodes</h4>
                    <p>Utilidades como iconos y badges</p>
                </div>
            </div>
        </div>
        <style>
            .flavor-assets-info {
                padding: 32px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .assets-info-header {
                text-align: center;
                margin-bottom: 32px;
            }
            .assets-info-header h3 {
                margin: 0 0 8px;
                font-size: 24px;
                color: #1e293b;
            }
            .assets-info-header p {
                margin: 0;
                color: #64748b;
                font-size: 15px;
            }
            .assets-info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
            .assets-info-grid .info-card {
                padding: 24px;
                background: #f8fafc;
                border-radius: 8px;
                text-align: center;
            }
            .assets-info-grid .info-card .dashicons {
                font-size: 40px;
                width: 40px;
                height: 40px;
                color: #8b5cf6;
                margin-bottom: 12px;
            }
            .assets-info-grid .info-card h4 {
                margin: 0 0 8px;
                font-size: 16px;
                color: #1e293b;
            }
            .assets-info-grid .info-card p {
                margin: 0;
                font-size: 14px;
                color: #64748b;
                line-height: 1.6;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Listado de recursos
     */
    public function shortcode_listado($atts) {
        wp_enqueue_style('flavor-assets-public');

        $atts = shortcode_atts([
            'tipo' => 'todos', // todos, css, js, shortcodes
        ], $atts);

        ob_start();
        ?>
        <div class="flavor-assets-listado">
            <h3>Recursos Disponibles</h3>
            <div class="assets-list">
                <?php if (in_array($atts['tipo'], ['todos', 'css'])): ?>
                    <div class="asset-item">
                        <span class="dashicons dashicons-admin-appearance"></span>
                        <div class="asset-info">
                            <h4>flavor-admin-common</h4>
                            <p>CSS común para páginas de administración</p>
                            <code>wp_enqueue_style('flavor-admin-common')</code>
                        </div>
                    </div>
                    <div class="asset-item">
                        <span class="dashicons dashicons-admin-appearance"></span>
                        <div class="asset-info">
                            <h4>flavor-utilities</h4>
                            <p>Clases de utilidad para frontend</p>
                            <code>wp_enqueue_style('flavor-utilities')</code>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (in_array($atts['tipo'], ['todos', 'js'])): ?>
                    <div class="asset-item">
                        <span class="dashicons dashicons-media-code"></span>
                        <div class="asset-info">
                            <h4>flavor-helpers</h4>
                            <p>Funciones JavaScript helpers</p>
                            <code>wp_enqueue_script('flavor-helpers')</code>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (in_array($atts['tipo'], ['todos', 'shortcodes'])): ?>
                    <div class="asset-item">
                        <span class="dashicons dashicons-shortcode"></span>
                        <div class="asset-info">
                            <h4>[flavor_icon]</h4>
                            <p>Renderizar iconos dashicons personalizables</p>
                            <code>[flavor_icon icon="dashicons-star" color="#f59e0b" size="20"]</code>
                        </div>
                    </div>
                    <div class="asset-item">
                        <span class="dashicons dashicons-shortcode"></span>
                        <div class="asset-info">
                            <h4>[flavor_badge]</h4>
                            <p>Crear badges coloridos</p>
                            <code>[flavor_badge text="Nuevo" color="green"]</code>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <style>
            .flavor-assets-listado {
                padding: 24px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .flavor-assets-listado h3 {
                margin: 0 0 24px;
                font-size: 20px;
                color: #1e293b;
            }
            .assets-list {
                display: grid;
                gap: 16px;
            }
            .asset-item {
                display: flex;
                gap: 16px;
                padding: 16px;
                background: #f8fafc;
                border-radius: 8px;
                align-items: flex-start;
            }
            .asset-item .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
                color: #8b5cf6;
                flex-shrink: 0;
                margin-top: 2px;
            }
            .asset-info h4 {
                margin: 0 0 4px;
                font-size: 15px;
                font-weight: 600;
                color: #1e293b;
            }
            .asset-info p {
                margin: 0 0 8px;
                font-size: 14px;
                color: #64748b;
            }
            .asset-info code {
                display: block;
                background: #e0e7ff;
                color: #4338ca;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 13px;
                font-family: monospace;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Registrar tabs en el dashboard de usuario
     */
    public function registrar_tabs($tabs) {
        // Assets es un módulo de sistema, no requiere tab de usuario
        return $tabs;
    }

    /**
     * Renderizar tab principal (no usado)
     */
    public function render_tab_principal() {
        echo '<div class="flavor-assets-tab">';
        echo '<p>' . __('Este módulo gestiona recursos compartidos del sistema.', 'flavor-chat-ia') . '</p>';
        echo '</div>';
    }
}
