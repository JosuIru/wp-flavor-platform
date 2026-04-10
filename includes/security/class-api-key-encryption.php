<?php
/**
 * Encriptación de API Keys
 *
 * Proporciona encriptación AES-256-GCM para almacenar API keys de forma segura
 * en la base de datos de WordPress.
 *
 * @package FlavorPlatform
 * @since 3.1.0
 * @security CRITICAL - Maneja datos sensibles
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_API_Key_Encryption {

    /**
     * Instancia singleton
     *
     * @var Flavor_API_Key_Encryption|null
     */
    private static $instance = null;

    /**
     * Método de encriptación
     */
    private const CIPHER_METHOD = 'aes-256-gcm';

    /**
     * Longitud del tag de autenticación
     */
    private const TAG_LENGTH = 16;

    /**
     * Prefijo para identificar valores encriptados
     */
    private const ENCRYPTED_PREFIX = 'enc:v1:';

    /**
     * Caché de keys desencriptadas (solo en memoria, nunca persistida)
     *
     * @var array
     */
    private $decrypted_cache = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_API_Key_Encryption
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Limpiar caché al finalizar el script
        register_shutdown_function([$this, 'clear_cache']);
    }

    /**
     * Obtiene la clave de encriptación derivada de las sales de WordPress
     *
     * @return string Clave de 32 bytes para AES-256
     */
    private function get_encryption_key() {
        // Usamos múltiples sales para mayor seguridad
        $combined_salt = wp_salt('auth') . wp_salt('secure_auth') . wp_salt('logged_in');

        // Derivar clave usando HKDF (Hash-based Key Derivation Function)
        if (function_exists('hash_hkdf')) {
            return hash_hkdf('sha256', $combined_salt, 32, 'flavor_api_key_encryption');
        }

        // Fallback para PHP < 7.1.2
        return substr(hash('sha256', $combined_salt . 'flavor_api_key_encryption', true), 0, 32);
    }

    /**
     * Encripta una API key
     *
     * @param string $api_key La API key en texto plano
     * @return string La API key encriptada con prefijo
     */
    public function encrypt($api_key) {
        // Si está vacía, no encriptar
        if (empty($api_key)) {
            return '';
        }

        // Si ya está encriptada, retornar sin cambios
        if ($this->is_encrypted($api_key)) {
            return $api_key;
        }

        // Verificar que el método de encriptación esté disponible
        if (!in_array(self::CIPHER_METHOD, openssl_get_cipher_methods())) {
            // Fallback a método menos seguro pero funcional
            return $this->encrypt_fallback($api_key);
        }

        $key = $this->get_encryption_key();

        // Generar IV aleatorio
        $iv_length = openssl_cipher_iv_length(self::CIPHER_METHOD);
        $iv = openssl_random_pseudo_bytes($iv_length);

        // Encriptar con GCM (proporciona autenticación)
        $tag = '';
        $encrypted = openssl_encrypt(
            $api_key,
            self::CIPHER_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($encrypted === false) {
            // Log error pero no exponer detalles
            flavor_log_error( 'API key encryption failed', 'Security' );
            return $api_key; // Retornar sin encriptar para no perder datos
        }

        // Combinar: IV + Tag + Encrypted data
        $combined = $iv . $tag . $encrypted;

        // Codificar en base64 y agregar prefijo
        return self::ENCRYPTED_PREFIX . base64_encode($combined);
    }

    /**
     * Desencripta una API key
     *
     * @param string $encrypted_key La API key encriptada
     * @return string La API key en texto plano
     */
    public function decrypt($encrypted_key) {
        // Si está vacía, retornar vacío
        if (empty($encrypted_key)) {
            return '';
        }

        // Si no está encriptada, retornar como está (compatibilidad con keys antiguas)
        if (!$this->is_encrypted($encrypted_key)) {
            return $encrypted_key;
        }

        // Verificar caché en memoria
        $cache_key = md5($encrypted_key);
        if (isset($this->decrypted_cache[$cache_key])) {
            return $this->decrypted_cache[$cache_key];
        }

        // Verificar si es fallback
        if (strpos($encrypted_key, self::ENCRYPTED_PREFIX . 'fb:') === 0) {
            return $this->decrypt_fallback($encrypted_key);
        }

        // Remover prefijo y decodificar
        $data = base64_decode(substr($encrypted_key, strlen(self::ENCRYPTED_PREFIX)));

        if ($data === false) {
            flavor_log_error( 'API key decryption failed: invalid base64', 'Security' );
            return '';
        }

        $key = $this->get_encryption_key();
        $iv_length = openssl_cipher_iv_length(self::CIPHER_METHOD);

        // Extraer componentes
        $iv = substr($data, 0, $iv_length);
        $tag = substr($data, $iv_length, self::TAG_LENGTH);
        $encrypted = substr($data, $iv_length + self::TAG_LENGTH);

        // Desencriptar
        $decrypted = openssl_decrypt(
            $encrypted,
            self::CIPHER_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            flavor_log_error( 'API key decryption failed', 'Security' );
            return '';
        }

        // Guardar en caché en memoria
        $this->decrypted_cache[$cache_key] = $decrypted;

        return $decrypted;
    }

    /**
     * Verifica si un valor está encriptado
     *
     * @param string $value El valor a verificar
     * @return bool True si está encriptado
     */
    public function is_encrypted($value) {
        return strpos($value, self::ENCRYPTED_PREFIX) === 0;
    }

    /**
     * Enmascara una API key para mostrarla de forma segura en UI/logs.
     *
     * @param string $api_key API key en texto plano.
     * @return string
     */
    public function mask_key( $api_key ) {
        $api_key = (string) $api_key;

        if ( strlen( $api_key ) < 8 ) {
            return '****';
        }

        return substr( $api_key, 0, 3 ) . '****' . substr( $api_key, -3 );
    }

    /**
     * Encriptación fallback para sistemas sin AES-256-GCM
     *
     * @param string $api_key
     * @return string
     */
    private function encrypt_fallback($api_key) {
        $key = $this->get_encryption_key();
        $iv = openssl_random_pseudo_bytes(16);

        $encrypted = openssl_encrypt(
            $api_key,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            return $api_key;
        }

        // HMAC para integridad
        $hmac = hash_hmac('sha256', $iv . $encrypted, $key, true);

        return self::ENCRYPTED_PREFIX . 'fb:' . base64_encode($iv . $hmac . $encrypted);
    }

    /**
     * Desencriptación fallback
     *
     * @param string $encrypted_key
     * @return string
     */
    private function decrypt_fallback($encrypted_key) {
        $data = base64_decode(substr($encrypted_key, strlen(self::ENCRYPTED_PREFIX . 'fb:')));

        if ($data === false) {
            return '';
        }

        $key = $this->get_encryption_key();

        $iv = substr($data, 0, 16);
        $hmac = substr($data, 16, 32);
        $encrypted = substr($data, 48);

        // Verificar HMAC
        $calculated_hmac = hash_hmac('sha256', $iv . $encrypted, $key, true);
        if (!hash_equals($hmac, $calculated_hmac)) {
            flavor_log_error( 'HMAC verification failed', 'Security' );
            return '';
        }

        $decrypted = openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * Limpia la caché en memoria
     */
    public function clear_cache() {
        $this->decrypted_cache = [];
    }

    /**
     * Enmascara una API key para mostrar en UI
     *
     * @param string $api_key API key (encriptada o no)
     * @return string API key enmascarada (ej: sk-...abc123)
     */
    public function mask_for_display($api_key) {
        // Desencriptar si es necesario
        $plain = $this->decrypt($api_key);

        if (empty($plain)) {
            return '';
        }

        $length = strlen($plain);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        // Mostrar primeros 4 y últimos 4 caracteres
        return substr($plain, 0, 4) . str_repeat('*', $length - 8) . substr($plain, -4);
    }

    /**
     * Valida el formato básico de una API key
     *
     * @param string $api_key
     * @param string $provider claude|openai|deepseek|mistral
     * @return bool
     */
    public function validate_format($api_key, $provider) {
        $plain = $this->decrypt($api_key);

        if (empty($plain)) {
            return false;
        }

        switch ($provider) {
            case 'claude':
                // Anthropic keys: sk-ant-api03-...
                return preg_match('/^sk-ant-[a-zA-Z0-9\-_]{20,}$/', $plain) === 1;

            case 'openai':
                // OpenAI keys: sk-...
                return preg_match('/^sk-[a-zA-Z0-9]{20,}$/', $plain) === 1;

            case 'deepseek':
                // DeepSeek keys
                return strlen($plain) >= 20;

            case 'mistral':
                // Mistral keys
                return strlen($plain) >= 20;

            default:
                return strlen($plain) >= 10;
        }
    }

    /**
     * Re-encripta todas las API keys almacenadas (para rotación de claves)
     *
     * @return array Resultado de la operación
     */
    public function rotate_encryption() {
        $settings = flavor_get_main_settings();
        $key_fields = ['api_key', 'claude_api_key', 'openai_api_key', 'deepseek_api_key', 'mistral_api_key'];

        $results = ['success' => 0, 'failed' => 0];

        foreach ($key_fields as $field) {
            if (!empty($settings[$field])) {
                // Desencriptar con clave actual
                $plain = $this->decrypt($settings[$field]);

                if (!empty($plain)) {
                    // Re-encriptar
                    $settings[$field] = $this->encrypt($plain);
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
            }
        }

        flavor_update_main_settings($settings);

        return $results;
    }
}

/**
 * Función helper global para encriptar API keys
 *
 * @param string $api_key
 * @return string
 */
function flavor_encrypt_api_key($api_key) {
    return Flavor_API_Key_Encryption::get_instance()->encrypt($api_key);
}

/**
 * Función helper global para desencriptar API keys
 *
 * @param string $encrypted_key
 * @return string
 */
function flavor_decrypt_api_key($encrypted_key) {
    return Flavor_API_Key_Encryption::get_instance()->decrypt($encrypted_key);
}
