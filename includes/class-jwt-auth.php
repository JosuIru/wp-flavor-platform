<?php
/**
 * Autenticación JWT para REST API
 *
 * Intercepta todas las peticiones REST y verifica el token JWT
 * automáticamente, estableciendo el usuario actual.
 *
 * @package FlavorPlatform
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
     * Verificar token JWT (formato estándar: header.payload.signature)
     *
     * @param string $token Token JWT
     * @return int|false ID del usuario o false si es inválido
     */
    private function verify_token($token) {
        $parts = explode('.', $token);

        // JWT estándar tiene 3 partes: header.payload.signature
        if (count($parts) !== 3) {
            return false;
        }

        list($header_b64, $payload_b64, $signature_b64) = $parts;

        // Decodificar signature (base64url)
        $signature = $this->base64url_decode($signature_b64);
        if ($signature === false) {
            return false;
        }

        // Usar la clave secreta de WordPress
        $secret_key = defined('AUTH_KEY') ? AUTH_KEY : '';
        if (empty($secret_key)) {
            return false;
        }

        // La firma se calcula sobre header.payload (sin decodificar)
        $data_to_verify = $header_b64 . '.' . $payload_b64;
        $expected_signature = hash_hmac('sha256', $data_to_verify, $secret_key, true);

        // Verificar firma con comparación segura
        if (!hash_equals($expected_signature, $signature)) {
            return false;
        }

        // Decodificar payload
        $payload_json = $this->base64url_decode($payload_b64);
        if ($payload_json === false) {
            return false;
        }

        $payload = json_decode($payload_json, true);

        // Verificar estructura del payload
        if (!is_array($payload) || !isset($payload['user_id']) || !isset($payload['exp'])) {
            // Compatibilidad: también aceptar 'expires_at'
            if (!isset($payload['expires_at'])) {
                return false;
            }
            $payload['exp'] = $payload['expires_at'];
        }

        // Verificar que el token no haya expirado
        if ($payload['exp'] < time()) {
            return false;
        }

        // Verificar que el usuario existe
        $user_id = (int) $payload['user_id'];
        if ($user_id <= 0 || !get_user_by('id', $user_id)) {
            return false;
        }

        return $user_id;
    }

    /**
     * Decodificar base64url (variante URL-safe de base64)
     *
     * @param string $data Datos codificados en base64url
     * @return string|false Datos decodificados o false si falla
     */
    private function base64url_decode($data) {
        // Reemplazar caracteres URL-safe por estándar base64
        $base64 = strtr($data, '-_', '+/');

        // Agregar padding si es necesario
        $padding = strlen($base64) % 4;
        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($base64, true);

        return $decoded;
    }

    /**
     * Generar token JWT para un usuario
     *
     * @param int $user_id ID del usuario
     * @param int $expiration Tiempo de expiración en segundos (default: 7 días)
     * @return string Token JWT
     */
    public static function generate_token($user_id, $expiration = 604800) {
        $secret_key = defined('AUTH_KEY') ? AUTH_KEY : '';

        // Header estándar JWT
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        // Payload con claims estándar
        $payload = [
            'user_id' => (int) $user_id,
            'iat'     => time(),
            'exp'     => time() + $expiration,
            'iss'     => get_site_url()
        ];

        $header_b64 = self::base64url_encode(wp_json_encode($header));
        $payload_b64 = self::base64url_encode(wp_json_encode($payload));

        $signature = hash_hmac('sha256', $header_b64 . '.' . $payload_b64, $secret_key, true);
        $signature_b64 = self::base64url_encode($signature);

        return $header_b64 . '.' . $payload_b64 . '.' . $signature_b64;
    }

    /**
     * Codificar en base64url
     *
     * @param string $data Datos a codificar
     * @return string Datos codificados en base64url
     */
    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

// Inicializar
Flavor_JWT_Auth::get_instance();
