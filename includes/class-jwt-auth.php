<?php
/**
 * Autenticación JWT para REST API
 *
 * Intercepta todas las peticiones REST y verifica el token JWT
 * automáticamente, estableciendo el usuario actual.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar autenticación JWT en REST API
 */
class Flavor_JWT_Auth {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Interceptar autenticación de usuario antes de las peticiones REST
        add_filter('determine_current_user', [$this, 'determine_current_user'], 20);
    }

    /**
     * Determinar usuario actual desde token JWT
     *
     * @param int|bool $user Usuario actual
     * @return int|bool
     */
    public function determine_current_user($user) {
        // Si ya hay un usuario autenticado, no hacer nada
        if ($user) {
            return $user;
        }

        // Solo procesar peticiones REST
        if (!defined('REST_REQUEST') || !REST_REQUEST) {
            return $user;
        }

        // Obtener token del header Authorization
        $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

        // También verificar alternativa 'Authorization'
        if (empty($auth_header) && function_exists('getallheaders')) {
            $headers = getallheaders();
            $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        }

        if (empty($auth_header)) {
            return $user;
        }

        // Extraer token del formato "Bearer TOKEN"
        $token_parts = explode(' ', $auth_header);
        if (count($token_parts) !== 2 || $token_parts[0] !== 'Bearer') {
            return $user;
        }

        $token = $token_parts[1];

        // Verificar token
        $user_id = $this->verify_token($token);

        if ($user_id) {
            return $user_id;
        }

        return $user;
    }

    /**
     * Verificar token JWT
     *
     * @param string $token Token JWT
     * @return int|false ID del usuario o false si es inválido
     */
    private function verify_token($token) {
        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            return false;
        }

        $payload_json = base64_decode($parts[0]);
        $signature = $parts[1];

        // Usar la clave secreta de WordPress
        $secret_key = defined('AUTH_KEY') ? AUTH_KEY : '';
        $expected_signature = hash_hmac('sha256', $payload_json, $secret_key);

        // Verificar firma
        if (!hash_equals($expected_signature, $signature)) {
            return false;
        }

        $payload = json_decode($payload_json, true);

        // Verificar que el token no haya expirado
        if (!$payload || !isset($payload['user_id']) || !isset($payload['expires_at'])) {
            return false;
        }

        if ($payload['expires_at'] < time()) {
            return false;
        }

        return (int) $payload['user_id'];
    }
}

// Inicializar
Flavor_JWT_Auth::get_instance();
