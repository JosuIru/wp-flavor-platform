<?php
/**
 * Adaptador de API
 *
 * Traduce entre los formatos de API de diferentes sistemas
 * para que las mismas APKs funcionen con todos
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase adaptadora de API
 */
class Flavor_API_Adapter {

    /**
     * Detector de plugins
     */
    private $plugin_detector;

    /**
     * Constructor
     *
     * @param Flavor_Plugin_Detector $detector
     */
    public function __construct($detector) {
        $this->plugin_detector = $detector;
    }

    /**
     * POST /unified-api/v1/chat
     * Endpoint unificado para chat - detecta qué sistema usar
     */
    public function unified_chat($request) {
        $has_flavor = $this->plugin_detector->is_flavor_chat_active();
        $has_calendario = $this->plugin_detector->is_calendario_active();

        // Priorizar Flavor Chat IA si está activo
        if ($has_flavor) {
            return $this->forward_to_flavor_chat($request);
        } elseif ($has_calendario) {
            return $this->forward_to_calendario_chat($request);
        }

        return new WP_Error(
            'no_chat_system',
            __('No hay ningún sistema de chat activo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ['status' => 503]
        );
    }

    /**
     * GET /unified-api/v1/site-info
     * Información del sitio unificada
     */
    public function unified_site_info($request) {
        $has_flavor = $this->plugin_detector->is_flavor_chat_active();
        $has_calendario = $this->plugin_detector->is_calendario_active();

        $info = [
            'site_name' => get_bloginfo('name'),
            'site_url' => get_site_url(),
            'description' => get_bloginfo('description'),
            'logo_url' => $this->get_site_logo(),
            'systems_active' => [],
        ];

        // Añadir info de cada sistema
        if ($has_calendario) {
            $info['systems_active'][] = 'calendario-experiencias';
            $info['calendario'] = $this->get_calendario_site_info();
        }

        if ($has_flavor) {
            $info['systems_active'][] = FLAVOR_PLATFORM_TEXT_DOMAIN;
            $info['flavor'] = $this->get_flavor_site_info();
        }

        return new WP_REST_Response($info, 200);
    }

    /**
     * Reenvía petición a Flavor Chat IA
     */
    private function forward_to_flavor_chat($request) {
        if (!class_exists('Flavor_Platform_Core')) {
            return new WP_Error('flavor_not_available', 'Flavor Chat no disponible');
        }

        $core = Flavor_Platform_Core::get_instance();

        // Adaptar parámetros al formato de Flavor
        $message = $request->get_param('message');
        $session_id = $request->get_param('session_id');
        $user_id = get_current_user_id();

        // Procesar con Flavor Chat
        $response = $core->process_message($message, $session_id, $user_id);

        // Adaptar respuesta al formato esperado por las apps
        return new WP_REST_Response([
            'success' => true,
            'response' => $response['message'] ?? '',
            'session_id' => $response['session_id'] ?? $session_id,
            'suggestions' => $response['suggestions'] ?? [],
            'actions' => $response['actions'] ?? [],
            'system' => FLAVOR_PLATFORM_TEXT_DOMAIN,
        ], 200);
    }

    /**
     * Reenvía petición a wp-calendario-experiencias
     */
    private function forward_to_calendario_chat($request) {
        // El sistema calendario ya tiene su propia API
        // Simplemente devolvemos indicación de que use su endpoint nativo
        return new WP_REST_Response([
            'success' => true,
            'use_native_api' => true,
            'api_namespace' => 'chat-ia-mobile/v1',
            'endpoint' => '/wp-json/chat-ia-mobile/v1/chat',
            'system' => 'calendario-experiencias',
        ], 200);
    }

    /**
     * Obtiene logo del sitio
     */
    private function get_site_logo() {
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            return wp_get_attachment_image_url($logo_id, 'full');
        }
        return '';
    }

    /**
     * Info específica de wp-calendario-experiencias
     */
    private function get_calendario_site_info() {
        // Info básica que las apps necesitan
        return [
            'has_reservas' => true,
            'has_tickets' => true,
            'has_qr' => true,
            'api_version' => '1.0',
        ];
    }

    /**
     * Info específica de Flavor Chat IA
     */
    private function get_flavor_site_info() {
        $profiles = Flavor_App_Profiles::get_instance();
        $loader = Flavor_Platform_Module_Loader::get_instance();

        return [
            'profile' => $profiles->obtener_perfil_activo(),
            'active_modules' => array_keys($loader->get_loaded_modules()),
            'api_version' => '1.0',
        ];
    }

    /**
     * Adapta respuesta de módulo al formato estándar
     *
     * @param string $module_id ID del módulo
     * @param array $data Datos del módulo
     * @return array Datos adaptados
     */
    public function adapt_module_response($module_id, $data) {
        // Formato estándar para las apps
        $adapted = [
            'success' => isset($data['success']) ? $data['success'] : true,
            'module' => $module_id,
            'data' => $data,
            'timestamp' => current_time('timestamp'),
        ];

        // Adaptaciones específicas por módulo si es necesario
        switch ($module_id) {
            case 'grupos_consumo':
                $adapted = $this->adapt_grupos_consumo($adapted);
                break;
            case 'banco_tiempo':
                $adapted = $this->adapt_banco_tiempo($adapted);
                break;
            case 'marketplace':
                $adapted = $this->adapt_marketplace($adapted);
                break;
        }

        return $adapted;
    }

    /**
     * Adaptaciones específicas para Grupos de Consumo
     */
    private function adapt_grupos_consumo($response) {
        // Asegurar campos necesarios para la app
        if (isset($response['data']['pedidos'])) {
            foreach ($response['data']['pedidos'] as &$pedido) {
                // Asegurar campos mínimos
                $pedido['id'] = $pedido['id'] ?? 0;
                $pedido['titulo'] = $pedido['titulo'] ?? '';
                $pedido['estado'] = $pedido['estado'] ?? 'desconocido';
                $pedido['precio_final'] = isset($pedido['precio_final']) ? floatval($pedido['precio_final']) : 0;
                $pedido['imagen'] = $pedido['imagen'] ?? '';

                // Convertir fechas a formato ISO
                if (isset($pedido['fecha_cierre'])) {
                    $pedido['fecha_cierre_iso'] = $this->mysql_to_iso($pedido['fecha_cierre']);
                }
                if (isset($pedido['fecha_entrega'])) {
                    $pedido['fecha_entrega_iso'] = $this->mysql_to_iso($pedido['fecha_entrega']);
                }
            }
        }

        return $response;
    }

    /**
     * Adaptaciones específicas para Banco de Tiempo
     */
    private function adapt_banco_tiempo($response) {
        // Adaptar formato de servicios
        if (isset($response['data']['servicios'])) {
            foreach ($response['data']['servicios'] as &$servicio) {
                $servicio['duracion_minutos'] = isset($servicio['duracion']) ? intval($servicio['duracion']) : 60;
                $servicio['creditos'] = isset($servicio['horas']) ? floatval($servicio['horas']) : 1.0;
            }
        }

        return $response;
    }

    /**
     * Adaptaciones específicas para Marketplace
     */
    private function adapt_marketplace($response) {
        // Adaptar anuncios
        if (isset($response['data']['anuncios'])) {
            foreach ($response['data']['anuncios'] as &$anuncio) {
                // Asegurar tipo de anuncio
                $anuncio['tipo'] = $anuncio['tipo'] ?? 'regalo';

                // Convertir precio a float
                if (isset($anuncio['precio'])) {
                    $anuncio['precio'] = floatval($anuncio['precio']);
                }
            }
        }

        return $response;
    }

    /**
     * Convierte fecha MySQL a ISO 8601
     */
    private function mysql_to_iso($mysql_date) {
        if (empty($mysql_date)) {
            return '';
        }

        $timestamp = strtotime($mysql_date);
        return date('c', $timestamp); // ISO 8601
    }

    /**
     * Convierte fecha ISO a MySQL
     */
    private function iso_to_mysql($iso_date) {
        if (empty($iso_date)) {
            return '';
        }

        $timestamp = strtotime($iso_date);
        return date('Y-m-d H:i:s', $timestamp);
    }
}
