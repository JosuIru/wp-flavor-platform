<?php
/**
 * Detector de Plugins Activos
 *
 * Detecta qué sistemas están activos (wp-calendario-experiencias, basabere-campamentos, Flavor Chat IA)
 * y devuelve información estructurada para las apps móviles
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para detectar plugins activos y sus capacidades
 */
class Flavor_Plugin_Detector {

    /**
     * Cache de detección
     */
    private $detection_cache = null;

    /**
     * Detecta todos los sistemas activos
     *
     * @return array Array de sistemas activos con sus configuraciones
     */
    public function detect_active_systems() {
        if ($this->detection_cache !== null) {
            return $this->detection_cache;
        }

        $systems = [];

        // Detectar wp-calendario-experiencias
        if ($this->is_calendario_active()) {
            $systems[] = $this->get_calendario_info();
        }

        // Detectar basabere-campamentos
        if ($this->is_basabere_active()) {
            $systems[] = $this->get_basabere_info();
        }

        // Detectar Flavor Chat IA
        if ($this->is_flavor_chat_active()) {
            $systems[] = $this->get_flavor_chat_info();
        }

        $this->detection_cache = $systems;
        return $systems;
    }

    /**
     * Verifica si wp-calendario-experiencias está activo
     *
     * @return bool
     */
    public function is_calendario_active() {
        return class_exists('Calendario_Experiencias') ||
               class_exists('Chat_IA_Addon') ||
               class_exists('Chat_IA_Mobile_API');
    }

    /**
     * Verifica si Flavor Chat IA está activo
     *
     * @return bool
     */
    public function is_flavor_chat_active() {
        return class_exists('Flavor_Platform');
    }

    /**
     * Verifica si basabere-campamentos está activo
     *
     * @return bool
     */
    public function is_basabere_active() {
        return class_exists('Camps_Mobile_API') ||
               class_exists('Basabere_Campamentos') ||
               function_exists('basabere_campamentos_init');
    }

    /**
     * Obtiene información de wp-calendario-experiencias
     *
     * @return array
     */
    private function get_calendario_info() {
        $version = '3.0.0';

        // Intentar obtener versión real
        if (defined('CALENDARIO_VERSION')) {
            $version = CALENDARIO_VERSION;
        }

        return [
            'id' => 'calendario-experiencias',
            'name' => 'Calendario de Experiencias',
            'active' => true,
            'version' => $version,
            'api_namespace' => 'chat-ia-mobile/v1',
            'features' => $this->get_calendario_features(),
            'endpoints' => $this->get_calendario_endpoints(),
        ];
    }

    /**
     * Obtiene información de Flavor Chat IA
     *
     * @return array
     */
    private function get_flavor_chat_info() {
        $version = defined('FLAVOR_PLATFORM_VERSION') ? FLAVOR_PLATFORM_VERSION : '1.5.0';

        // Obtener perfil activo
        $profiles = Flavor_App_Profiles::get_instance();
        $active_profile = $profiles->obtener_perfil_activo();

        // Obtener módulos activos
        $loader = Flavor_Platform_Module_Loader::get_instance();
        $active_modules = array_keys($loader->get_loaded_modules());

        return [
            'id' => FLAVOR_PLATFORM_TEXT_DOMAIN,
            'name' => 'Flavor Chat IA',
            'active' => true,
            'version' => $version,
            'api_namespace' => FLAVOR_PLATFORM_REST_NAMESPACE,
            'api_namespaces' => [FLAVOR_PLATFORM_REST_NAMESPACE, FLAVOR_CHAT_IA_REST_NAMESPACE],
            'profile' => $active_profile,
            'modules' => $active_modules,
            'features' => $this->get_flavor_features($active_modules),
            'endpoints' => $this->get_flavor_endpoints($active_modules),
        ];
    }

    /**
     * Obtiene información de basabere-campamentos
     *
     * @return array
     */
    private function get_basabere_info() {
        $version = '1.0.0';

        // Intentar obtener versión real
        if (defined('BASABERE_VERSION')) {
            $version = BASABERE_VERSION;
        } elseif (defined('BASABERE_CAMPAMENTOS_VERSION')) {
            $version = BASABERE_CAMPAMENTOS_VERSION;
        }

        return [
            'id' => 'basabere-campamentos',
            'name' => 'Basabere Campamentos',
            'active' => true,
            'version' => $version,
            'api_namespace' => 'camps/v1',
            'features' => $this->get_basabere_features(),
            'endpoints' => $this->get_basabere_endpoints(),
        ];
    }

    /**
     * Obtiene características de wp-calendario-experiencias
     *
     * @return array
     */
    private function get_calendario_features() {
        return [
            'reservas',
            'tickets',
            'chat',
            'qr_scanner',
            'dashboard_admin',
            'calendario',
            'experiencias',
        ];
    }

    /**
     * Obtiene características de basabere-campamentos
     *
     * @return array
     */
    private function get_basabere_features() {
        return [
            'campamentos',
            'inscripciones',
            'categorias_campamentos',
            'filtros_campamentos',
            'exportar_excel',
            'gestion_admin_campamentos',
            'taxonomias_campamentos',
            'galeria_campamentos',
            'compartir_campamentos',
            'estadisticas_campamentos',
        ];
    }

    /**
     * Obtiene características de Flavor Chat IA según módulos activos
     *
     * @param array $modules Módulos activos
     * @return array
     */
    private function get_flavor_features($modules) {
        $features = ['chat']; // Chat siempre está disponible

        $feature_map = [
            'grupos_consumo' => ['pedidos_colectivos', 'productores', 'repartos'],
            'banco_tiempo' => ['servicios', 'intercambios', 'transacciones'],
            'marketplace' => ['anuncios', 'categorias', 'busqueda'],
            'woocommerce' => ['productos', 'carrito', 'pedidos', 'checkout'],
        ];

        foreach ($modules as $module_id) {
            if (isset($feature_map[$module_id])) {
                $features = array_merge($features, $feature_map[$module_id]);
            }
        }

        return array_unique($features);
    }

    /**
     * Obtiene endpoints de wp-calendario-experiencias
     *
     * @return array
     */
    private function get_calendario_endpoints() {
        $base = '/wp-json/chat-ia-mobile/v1';

        return [
            'auth' => $base . '/auth',
            'chat' => $base . '/chat',
            'public' => $base . '/public',
            'reservations' => $base . '/reservations',
            'admin' => $base . '/admin',
            'client' => $base . '/client',
        ];
    }

    /**
     * Obtiene endpoints de basabere-campamentos
     *
     * @return array
     */
    private function get_basabere_endpoints() {
        $base = '/wp-json/camps/v1';

        return [
            // Endpoints públicos (cliente)
            'camps' => $base . '/camps',
            'camp_detail' => $base . '/camps/{id}',
            'inscribe' => $base . '/camps/{id}/inscribe',

            // Endpoints admin
            'admin_camps' => $base . '/admin/camps',
            'admin_camp_detail' => $base . '/admin/camps/{id}',
            'admin_inscriptions' => $base . '/admin/camps/{id}/inscriptions',
            'admin_stats' => $base . '/admin/stats',
            'admin_export_excel' => $base . '/admin/camps/{id}/export-excel',
            'admin_toggle_inscription' => $base . '/admin/camps/{id}/toggle-inscription',
            'admin_toggle_status' => $base . '/admin/camps/{id}/toggle-status',
            'admin_shareable_link' => $base . '/admin/camps/{id}/shareable-link',
            'admin_taxonomies' => $base . '/admin/taxonomies',
            'admin_upload_image' => $base . '/admin/upload-image',
        ];
    }

    /**
     * Obtiene endpoints de Flavor Chat IA según módulos
     *
     * @param array $modules Módulos activos
     * @return array
     */
    private function get_flavor_endpoints($modules) {
        $base = '/wp-json/' . FLAVOR_PLATFORM_REST_NAMESPACE;
        $legacy_base = '/wp-json/' . FLAVOR_CHAT_IA_REST_NAMESPACE;

        $endpoints = [
            'discovery' => '/wp-json/app-discovery/v1/info',
            'modules' => '/wp-json/app-discovery/v1/modules',
            'theme' => '/wp-json/app-discovery/v1/theme',
            'legacy_base' => $legacy_base,
        ];

        // Endpoints por módulo
        $module_endpoints = [
            'grupos_consumo' => [
                'pedidos' => $base . '/pedidos',
                'mis_pedidos' => $base . '/mis-pedidos',
            ],
            'banco_tiempo' => [
                'servicios' => $base . '/banco-tiempo/servicios',
                'transacciones' => $base . '/banco-tiempo/transacciones',
            ],
            'marketplace' => [
                'anuncios' => $base . '/marketplace/anuncios',
                'categorias' => $base . '/marketplace/categorias',
            ],
            'woocommerce' => [
                'productos' => $base . '/woocommerce/productos',
                'carrito' => $base . '/woocommerce/carrito',
            ],
        ];

        foreach ($modules as $module_id) {
            if (isset($module_endpoints[$module_id])) {
                $endpoints[$module_id] = $module_endpoints[$module_id];
            }
        }

        return $endpoints;
    }

    /**
     * Obtiene capacidades combinadas de todos los sistemas
     *
     * @return array
     */
    public function get_combined_capabilities() {
        $systems = $this->detect_active_systems();
        $capabilities = [];

        foreach ($systems as $system) {
            $capabilities = array_merge($capabilities, $system['features']);
        }

        return array_unique($capabilities);
    }

    /**
     * Verifica si una característica específica está disponible
     *
     * @param string $feature Nombre de la característica
     * @return bool
     */
    public function has_feature($feature) {
        $capabilities = $this->get_combined_capabilities();
        return in_array($feature, $capabilities);
    }

    /**
     * Limpia la cache de detección
     */
    public function clear_cache() {
        $this->detection_cache = null;
    }
}
