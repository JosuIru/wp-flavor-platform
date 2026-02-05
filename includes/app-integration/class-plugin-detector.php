<?php
/**
 * Detector de Plugins Activos
 *
 * Detecta qué sistemas están activos (wp-calendario-experiencias, Flavor Chat IA)
 * y devuelve información estructurada para las apps móviles
 *
 * @package FlavorChatIA
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
        return class_exists('Flavor_Chat_IA');
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
        $version = defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '1.5.0';

        // Obtener perfil activo
        $profiles = Flavor_App_Profiles::get_instance();
        $active_profile = $profiles->obtener_perfil_activo();

        // Obtener módulos activos
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $active_modules = array_keys($loader->get_loaded_modules());

        return [
            'id' => 'flavor-chat-ia',
            'name' => 'Flavor Chat IA',
            'active' => true,
            'version' => $version,
            'api_namespace' => 'flavor-chat-ia/v1',
            'profile' => $active_profile,
            'modules' => $active_modules,
            'features' => $this->get_flavor_features($active_modules),
            'endpoints' => $this->get_flavor_endpoints($active_modules),
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
     * Obtiene endpoints de Flavor Chat IA según módulos
     *
     * @param array $modules Módulos activos
     * @return array
     */
    private function get_flavor_endpoints($modules) {
        $base = '/wp-json/flavor-chat-ia/v1';

        $endpoints = [
            'discovery' => '/wp-json/app-discovery/v1/info',
            'modules' => '/wp-json/app-discovery/v1/modules',
            'theme' => '/wp-json/app-discovery/v1/theme',
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
