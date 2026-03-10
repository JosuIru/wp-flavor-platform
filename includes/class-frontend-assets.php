<?php
/**
 * Gestor de Assets Frontend para Módulos
 *
 * Encola CSS y JavaScript necesarios para formularios y componentes
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestión de assets frontend
 */
class Flavor_Frontend_Assets {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Detecta si la petición actual pertenece al portal dinámico.
     */
    private function is_dynamic_portal_request() {
        if (is_admin()) {
            return false;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';

        return strpos($request_uri, '/mi-portal') !== false;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Encola los assets CSS y JS
     */
    public function enqueue_assets() {
        // Solo cargar en páginas que lo necesiten (frontend)
        if (is_admin()) {
            return;
        }

        // Las rutas del portal dinámico ya gestionan sus assets por módulo y no
        // deben volver a escanear todo el contenido de la página.
        if ($this->is_dynamic_portal_request()) {
            return;
        }

        // Verificar si hay shortcodes de módulos en el contenido
        global $post;
        $tiene_shortcodes_modulos = false;

        if ($post && is_singular()) {
            $shortcodes_a_buscar = [
                // Módulos genéricos
                'flavor_module_listing',
                'flavor_module_form',
                'flavor_module_detail',
                'flavor_module_dashboard',
                // Landings y secciones
                'flavor_landing',
                'flavor_section',
                // Dashboard usuario
                'flavor_mi_cuenta',
                // Grupos de consumo
                'gc_ciclo_actual',
                'gc_productos',
                'gc_mi_pedido',
                // Marketplace
                'marketplace_listado',
                'marketplace_formulario',
                // Red de comunidades
                'flavor_network_directory',
                'flavor_network_map',
                'flavor_network_board',
                'flavor_network_events',
                'flavor_network_alerts',
                'flavor_network_catalog',
                'flavor_network_collaborations',
                'flavor_network_time_offers',
                'flavor_network_node_profile',
                'flavor_network_questions',
            ];

            foreach ($shortcodes_a_buscar as $shortcode) {
                if (has_shortcode($post->post_content, $shortcode)) {
                    $tiene_shortcodes_modulos = true;
                    break;
                }
            }
        }

        // Permitir forzar la carga via filtro
        $forzar_carga = apply_filters('flavor_force_load_module_assets', false);

        if (!$tiene_shortcodes_modulos && !$forzar_carga) {
            return;
        }

        // Encolar Alpine.js (ligero framework reactivo)
        wp_enqueue_script(
            'alpine',
            'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
            [],
            '3.0',
            true
        );

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // Encolar JS de módulos
        wp_enqueue_script(
            'flavor-modules',
            FLAVOR_CHAT_IA_URL . "assets/js/flavor-modules{$sufijo_asset}.js",
            ['alpine'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        // Localizar script con datos necesarios
        wp_localize_script('flavor-modules', 'flavorModulesData', [
            'apiUrl' => rest_url('flavor/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'userId' => get_current_user_id(),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'i18n' => [
                'loading' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Error', 'flavor-chat-ia'),
                'success' => __('Éxito', 'flavor-chat-ia'),
                'required' => __('Este campo es obligatorio', 'flavor-chat-ia'),
            ],
        ]);

        // Encolar CSS de módulos
        wp_enqueue_style(
            'flavor-modules',
            FLAVOR_CHAT_IA_URL . "assets/css/modules/flavor-modules{$sufijo_asset}.css",
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // Permitir extender con assets adicionales
        do_action('flavor_module_assets_enqueued');
    }

    /**
     * Encolar assets en admin (para previsualizaciones)
     */
    public function enqueue_admin_assets($hook) {
        // Solo en páginas específicas del admin
        $pantallas_permitidas = ['post.php', 'post-new.php', 'page.php', 'page-new.php'];

        if (!in_array($hook, $pantallas_permitidas)) {
            return;
        }

        // Encolar los mismos assets que en frontend para preview
        wp_enqueue_script(
            'alpine-admin',
            'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
            [],
            '3.0',
            true
        );

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_script(
            'flavor-modules-admin',
            FLAVOR_CHAT_IA_URL . "assets/js/flavor-modules{$sufijo_asset}.js",
            ['alpine-admin'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-modules-admin', 'flavorModulesData', [
            'apiUrl' => rest_url('flavor/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'userId' => get_current_user_id(),
            'debug' => true,
            'i18n' => [
                'loading' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Error', 'flavor-chat-ia'),
                'success' => __('Éxito', 'flavor-chat-ia'),
                'required' => __('Este campo es obligatorio', 'flavor-chat-ia'),
            ],
        ]);

        wp_enqueue_style(
            'flavor-modules-admin',
            FLAVOR_CHAT_IA_URL . "assets/css/modules/flavor-modules{$sufijo_asset}.css",
            [],
            FLAVOR_CHAT_IA_VERSION
        );
    }
}
