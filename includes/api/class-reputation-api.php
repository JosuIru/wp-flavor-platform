<?php
/**
 * API REST para el sistema de reputación
 *
 * @package Flavor_Platform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Reputation_API {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    private $namespace = 'flavor/v1';

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('rest_api_init', [$this, 'registrar_rutas']);
    }

    /**
     * Callback de permisos públicos con rate limiting.
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function public_permission_with_rate_limit($request) {
        $client_ip = $this->get_client_ip();
        $transient_key = 'reputation_api_rate_' . md5($client_ip);
        $request_count = (int) get_transient($transient_key);
        $rate_limit = 60; // peticiones por minuto

        if ($request_count >= $rate_limit) {
            return new WP_Error(
                'rate_limit_exceeded',
                __('Demasiadas peticiones. Intente de nuevo en un minuto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 429]
            );
        }

        set_transient($transient_key, $request_count + 1, MINUTE_IN_SECONDS);

        return true;
    }

    /**
     * Obtiene la IP del cliente.
     *
     * @return string
     */
    private function get_client_ip() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip_list = explode(',', sanitize_text_field(wp_unslash($_SERVER[$header])));
                return trim($ip_list[0]);
            }
        }

        return '127.0.0.1';
    }

    /**
     * Registrar rutas de la API
     */
    public function registrar_rutas() {
        // Obtener reputación de usuario
        register_rest_route($this->namespace, '/reputation/user/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_reputacion_usuario'],
            'permission_callback' => [$this, 'check_self_or_admin'],
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);

        // Obtener reputación del usuario actual
        register_rest_route($this->namespace, '/reputation/me', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_mi_reputacion'],
            'permission_callback' => [$this, 'check_auth']
        ]);

        // Obtener leaderboard
        register_rest_route($this->namespace, '/reputation/leaderboard', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_leaderboard'],
            'permission_callback' => [$this, 'public_permission_with_rate_limit'],
            'args' => [
                'periodo' => [
                    'default' => 'total',
                    'enum' => ['total', 'semana', 'mes']
                ],
                'limite' => [
                    'default' => 10,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    }
                ]
            ]
        ]);

        // Obtener badges disponibles
        register_rest_route($this->namespace, '/reputation/badges', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_badges'],
            'permission_callback' => [$this, 'public_permission_with_rate_limit']
        ]);

        // Obtener badges de un usuario
        register_rest_route($this->namespace, '/reputation/user/(?P<id>\d+)/badges', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_badges_usuario'],
            'permission_callback' => [$this, 'check_self_or_admin'],
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);

        // Obtener historial de puntos
        register_rest_route($this->namespace, '/reputation/me/history', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_historial'],
            'permission_callback' => [$this, 'check_auth'],
            'args' => [
                'per_page' => [
                    'default' => 20,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 50;
                    }
                ],
                'page' => [
                    'default' => 1,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ]
            ]
        ]);

        // Destacar/quitar destacado de un badge
        register_rest_route($this->namespace, '/reputation/badges/(?P<badge_id>\d+)/highlight', [
            'methods'             => 'POST',
            'callback'            => [$this, 'toggle_badge_destacado'],
            'permission_callback' => [$this, 'check_auth'],
            'args' => [
                'badge_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);

        // Obtener configuración de puntos (admin)
        register_rest_route($this->namespace, '/reputation/config', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_config'],
            'permission_callback' => [$this, 'check_admin']
        ]);
    }

    /**
     * Verificar autenticación
     */
    public function check_auth() {
        return is_user_logged_in();
    }

    /**
     * Verificar si es admin
     */
    public function check_admin() {
        return current_user_can('manage_options');
    }

    /**
     * Verificar si el usuario autenticado puede consultar datos del usuario indicado.
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function check_self_or_admin($request) {
        $usuario_id = (int) $request->get_param('id');
        return is_user_logged_in() && (get_current_user_id() === $usuario_id || current_user_can('manage_options'));
    }

    /**
     * Obtener reputación de un usuario
     */
    public function get_reputacion_usuario($request) {
        $usuario_id = (int) $request->get_param('id');

        if (!get_userdata($usuario_id)) {
            return new WP_Error('usuario_no_existe', 'Usuario no encontrado', ['status' => 404]);
        }

        $reputacion = flavor_reputation()->get_reputacion_usuario($usuario_id);

        return rest_ensure_response([
            'success' => true,
            'data' => $reputacion
        ]);
    }

    /**
     * Obtener reputación del usuario actual
     */
    public function get_mi_reputacion($request) {
        $usuario_id = get_current_user_id();
        $reputacion = flavor_reputation()->get_reputacion_usuario($usuario_id);

        return rest_ensure_response([
            'success' => true,
            'data' => $reputacion
        ]);
    }

    /**
     * Obtener leaderboard
     */
    public function get_leaderboard($request) {
        $periodo = $request->get_param('periodo');
        $limite = (int) $request->get_param('limite');

        $leaderboard = flavor_reputation()->get_leaderboard($periodo, $limite);

        return rest_ensure_response([
            'success' => true,
            'periodo' => $periodo,
            'data' => $leaderboard
        ]);
    }

    /**
     * Obtener badges disponibles
     */
    public function get_badges($request) {
        $badges = flavor_reputation()->get_badges_disponibles();

        return rest_ensure_response([
            'success' => true,
            'data' => $badges
        ]);
    }

    /**
     * Obtener badges de un usuario
     */
    public function get_badges_usuario($request) {
        $usuario_id = (int) $request->get_param('id');

        if (!get_userdata($usuario_id)) {
            return new WP_Error('usuario_no_existe', 'Usuario no encontrado', ['status' => 404]);
        }

        $badges = flavor_reputation()->get_badges_usuario($usuario_id);

        return rest_ensure_response([
            'success' => true,
            'data' => $badges
        ]);
    }

    /**
     * Obtener historial de puntos
     */
    public function get_historial($request) {
        $usuario_id = get_current_user_id();
        $per_page = (int) $request->get_param('per_page');
        $page = (int) $request->get_param('page');
        $offset = ($page - 1) * $per_page;

        $historial = flavor_reputation()->get_historial_puntos($usuario_id, $per_page, $offset);

        return rest_ensure_response([
            'success' => true,
            'page' => $page,
            'per_page' => $per_page,
            'data' => $historial
        ]);
    }

    /**
     * Toggle destacar badge
     */
    public function toggle_badge_destacado($request) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        $badge_id = (int) $request->get_param('badge_id');
        $prefix = $wpdb->prefix . 'flavor_';

        // Verificar que el usuario tiene el badge
        $usuario_badge = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}social_usuario_badges
             WHERE usuario_id = %d AND badge_id = %d",
            $usuario_id,
            $badge_id
        ));

        if (!$usuario_badge) {
            return new WP_Error('badge_no_encontrado', 'No tienes este badge', ['status' => 404]);
        }

        // Toggle destacado
        $nuevo_estado = $usuario_badge->destacado ? 0 : 1;

        // Si se va a destacar, quitar destacado de otros (máximo 3 destacados)
        if ($nuevo_estado === 1) {
            $destacados_actuales = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$prefix}social_usuario_badges
                 WHERE usuario_id = %d AND destacado = 1",
                $usuario_id
            ));

            if ($destacados_actuales >= 3) {
                return new WP_Error('max_destacados', 'Ya tienes 3 badges destacados', ['status' => 400]);
            }
        }

        $wpdb->update(
            $prefix . 'social_usuario_badges',
            ['destacado' => $nuevo_estado],
            ['id' => $usuario_badge->id]
        );

        return rest_ensure_response([
            'success' => true,
            'destacado' => (bool) $nuevo_estado,
            'message' => $nuevo_estado ? 'Badge destacado' : 'Badge quitado de destacados'
        ]);
    }

    /**
     * Obtener configuración (admin)
     */
    public function get_config($request) {
        return rest_ensure_response([
            'success' => true,
            'puntos' => flavor_reputation()->get_puntos_config(),
            'niveles' => flavor_reputation()->get_niveles_config()
        ]);
    }
}
