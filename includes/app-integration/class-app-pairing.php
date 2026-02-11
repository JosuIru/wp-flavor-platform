<?php
/**
 * Clase para manejar el emparejamiento de apps mediante QR
 *
 * @package Flavor_Chat_IA
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_App_Pairing {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = 'flavor/v1';

    /**
     * Duración del token en segundos (5 minutos)
     */
    const TOKEN_EXPIRATION = 300;

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
     * Constructor
     */
    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registrar rutas de la API
     */
    public function register_routes() {
        // POST /app/generate-pair-token - Generar token de emparejamiento
        register_rest_route(self::API_NAMESPACE, '/app/generate-pair-token', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'generate_pair_token'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        // POST /app/validate-pair - Validar token y crear sesión
        register_rest_route(self::API_NAMESPACE, '/app/validate-pair', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'validate_pair'],
            'permission_callback' => '__return_true',
            'args' => [
                'token' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // DELETE /app/revoke-session - Revocar sesión de app
        register_rest_route(self::API_NAMESPACE, '/app/revoke-session', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'revoke_session'],
            'permission_callback' => 'is_user_logged_in',
        ]);
    }

    /**
     * POST /app/generate-pair-token
     * Genera un token temporal para emparejamiento QR
     */
    public function generate_pair_token($request) {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return new WP_Error(
                'no_auth',
                __('Debes iniciar sesión', 'flavor-chat-ia'),
                ['status' => 401]
            );
        }

        // Generar token único
        $token = $this->generate_secure_token();

        // Guardar token en transient (expira en 5 minutos)
        $token_data = [
            'user_id' => $user_id,
            'created_at' => time(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        set_transient('flavor_pair_token_' . $token, $token_data, self::TOKEN_EXPIRATION);

        // Log del evento
        do_action('flavor_pair_token_generated', $user_id, $token);

        return rest_ensure_response([
            'success' => true,
            'token' => $token,
            'expires_in' => self::TOKEN_EXPIRATION,
            'qr_data' => $this->generate_qr_data($token),
        ]);
    }

    /**
     * POST /app/validate-pair
     * Valida el token QR y crea una sesión persistente
     */
    public function validate_pair($request) {
        $token = $request->get_param('token');

        // Recuperar datos del token
        $token_data = get_transient('flavor_pair_token_' . $token);

        if (false === $token_data) {
            return new WP_Error(
                'invalid_token',
                __('Token inválido o expirado', 'flavor-chat-ia'),
                ['status' => 401]
            );
        }

        $user_id = $token_data['user_id'];

        // Eliminar el token usado
        delete_transient('flavor_pair_token_' . $token);

        // Crear sesión persistente para la app
        $session_token = $this->generate_secure_token();
        $session_data = [
            'user_id' => $user_id,
            'created_at' => time(),
            'last_used' => time(),
            'device_info' => [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ],
        ];

        // Guardar sesión (expira en 30 días)
        set_transient('flavor_app_session_' . $session_token, $session_data, 30 * DAY_IN_SECONDS);

        // Guardar lista de sesiones del usuario
        $user_sessions = get_user_meta($user_id, 'flavor_app_sessions', true);
        if (!is_array($user_sessions)) {
            $user_sessions = [];
        }
        $user_sessions[$session_token] = $session_data['created_at'];
        update_user_meta($user_id, 'flavor_app_sessions', $user_sessions);

        // Log del evento
        do_action('flavor_pair_validated', $user_id, $session_token);

        // Obtener información del usuario
        $user = get_userdata($user_id);

        return rest_ensure_response([
            'success' => true,
            'session_token' => $session_token,
            'user' => [
                'id' => $user_id,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'roles' => $user->roles,
            ],
        ]);
    }

    /**
     * DELETE /app/revoke-session
     * Revoca la sesión actual de la app
     */
    public function revoke_session($request) {
        $user_id = get_current_user_id();

        // Obtener token desde el header Authorization
        $auth_header = $request->get_header('Authorization');
        if (empty($auth_header)) {
            return new WP_Error(
                'no_token',
                __('No se proporcionó token de sesión', 'flavor-chat-ia'),
                ['status' => 400]
            );
        }

        // Extraer token (formato: "Bearer TOKEN")
        $token_parts = explode(' ', $auth_header);
        if (count($token_parts) !== 2 || $token_parts[0] !== 'Bearer') {
            return new WP_Error(
                'invalid_format',
                __('Formato de token inválido', 'flavor-chat-ia'),
                ['status' => 400]
            );
        }

        $session_token = $token_parts[1];

        // Eliminar sesión
        delete_transient('flavor_app_session_' . $session_token);

        // Eliminar de la lista de sesiones del usuario
        $user_sessions = get_user_meta($user_id, 'flavor_app_sessions', true);
        if (is_array($user_sessions) && isset($user_sessions[$session_token])) {
            unset($user_sessions[$session_token]);
            update_user_meta($user_id, 'flavor_app_sessions', $user_sessions);
        }

        // Log del evento
        do_action('flavor_session_revoked', $user_id, $session_token);

        return rest_ensure_response([
            'success' => true,
            'message' => __('Sesión revocada correctamente', 'flavor-chat-ia'),
        ]);
    }

    /**
     * Genera un token seguro
     */
    private function generate_secure_token() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Genera los datos para el código QR
     */
    private function generate_qr_data($token) {
        $site_url = home_url();

        return [
            'type' => 'flavor_app_pair',
            'token' => $token,
            'site_url' => $site_url,
            'expires_at' => time() + self::TOKEN_EXPIRATION,
        ];
    }

    /**
     * Valida una sesión de app
     *
     * @param string $session_token Token de sesión
     * @return int|false ID de usuario o false si es inválida
     */
    public static function validate_session($session_token) {
        $session_data = get_transient('flavor_app_session_' . $session_token);

        if (false === $session_data) {
            return false;
        }

        // Actualizar last_used
        $session_data['last_used'] = time();
        set_transient('flavor_app_session_' . $session_token, $session_data, 30 * DAY_IN_SECONDS);

        return $session_data['user_id'];
    }
}

// Inicializar
Flavor_App_Pairing::get_instance();
