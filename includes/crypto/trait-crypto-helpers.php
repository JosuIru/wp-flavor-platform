<?php
/**
 * Trait con funciones auxiliares criptográficas
 *
 * Proporciona utilidades comunes para operaciones criptográficas
 * utilizando libsodium (incluido en PHP 7.2+)
 *
 * @package FlavorChatIA
 * @subpackage Crypto
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Flavor_Crypto_Helpers_Trait {

    /**
     * Versión actual del protocolo E2E
     */
    const E2E_PROTOCOL_VERSION = 1;

    /**
     * Longitud de claves en bytes
     */
    const KEY_LENGTH = SODIUM_CRYPTO_BOX_SECRETKEYBYTES; // 32 bytes
    const NONCE_LENGTH = SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES; // 12 bytes
    const MAC_LENGTH = 16;

    /**
     * Genera bytes aleatorios criptográficamente seguros
     *
     * @param int $longitud Número de bytes a generar
     * @return string Bytes aleatorios
     */
    protected function generar_bytes_aleatorios($longitud = 32) {
        return random_bytes($longitud);
    }

    /**
     * Genera un ID de dispositivo único
     *
     * @return string ID de dispositivo de 64 caracteres hex
     */
    protected function generar_dispositivo_id() {
        return bin2hex($this->generar_bytes_aleatorios(32));
    }

    /**
     * Genera un par de claves Curve25519 para ECDH
     *
     * @return array ['public' => string, 'private' => string]
     */
    protected function generar_par_claves_curve25519() {
        $keypair = sodium_crypto_box_keypair();

        return [
            'public' => sodium_crypto_box_publickey($keypair),
            'private' => sodium_crypto_box_secretkey($keypair),
        ];
    }

    /**
     * Genera un par de claves Ed25519 para firmas
     *
     * @return array ['public' => string, 'private' => string]
     */
    protected function generar_par_claves_ed25519() {
        $keypair = sodium_crypto_sign_keypair();

        return [
            'public' => sodium_crypto_sign_publickey($keypair),
            'private' => sodium_crypto_sign_secretkey($keypair),
        ];
    }

    /**
     * Convierte una clave Ed25519 a Curve25519 para ECDH
     *
     * @param string $clave_ed25519_publica Clave pública Ed25519
     * @return string Clave pública Curve25519
     */
    protected function ed25519_a_curve25519_publica($clave_ed25519_publica) {
        return sodium_crypto_sign_ed25519_pk_to_curve25519($clave_ed25519_publica);
    }

    /**
     * Convierte una clave privada Ed25519 a Curve25519
     *
     * @param string $clave_ed25519_privada Clave privada Ed25519
     * @return string Clave privada Curve25519
     */
    protected function ed25519_a_curve25519_privada($clave_ed25519_privada) {
        return sodium_crypto_sign_ed25519_sk_to_curve25519($clave_ed25519_privada);
    }

    /**
     * Realiza un intercambio de claves Diffie-Hellman
     *
     * @param string $clave_privada_local Clave privada local (Curve25519)
     * @param string $clave_publica_remota Clave pública remota (Curve25519)
     * @return string Secreto compartido de 32 bytes
     */
    protected function realizar_dh($clave_privada_local, $clave_publica_remota) {
        return sodium_crypto_scalarmult($clave_privada_local, $clave_publica_remota);
    }

    /**
     * HKDF (HMAC-based Key Derivation Function) usando SHA-256
     *
     * @param string $material_clave Material de entrada para derivación
     * @param int $longitud Longitud de la clave de salida
     * @param string $info Contexto de aplicación
     * @param string $sal Sal opcional (puede ser vacía)
     * @return string Clave derivada
     */
    protected function hkdf($material_clave, $longitud = 32, $info = '', $sal = '') {
        // Si no hay sal, usar una cadena de ceros del tamaño del hash
        if (empty($sal)) {
            $sal = str_repeat("\0", 32);
        }

        // Paso 1: Extract
        $prk = hash_hmac('sha256', $material_clave, $sal, true);

        // Paso 2: Expand
        $salida = '';
        $bloque_anterior = '';
        $contador = 1;

        while (strlen($salida) < $longitud) {
            $bloque = hash_hmac(
                'sha256',
                $bloque_anterior . $info . chr($contador),
                $prk,
                true
            );
            $salida .= $bloque;
            $bloque_anterior = $bloque;
            $contador++;
        }

        return substr($salida, 0, $longitud);
    }

    /**
     * Deriva múltiples claves de un material de entrada
     *
     * @param string $material_clave Material de entrada
     * @param array $longitudes Array de longitudes para cada clave
     * @param string $info Contexto
     * @return array Claves derivadas
     */
    protected function derivar_claves($material_clave, $longitudes, $info = '') {
        $longitud_total = array_sum($longitudes);
        $material_derivado = $this->hkdf($material_clave, $longitud_total, $info);

        $claves = [];
        $offset = 0;

        foreach ($longitudes as $nombre => $longitud) {
            $claves[$nombre] = substr($material_derivado, $offset, $longitud);
            $offset += $longitud;
        }

        return $claves;
    }

    /**
     * Cifra datos usando AES-256-GCM
     *
     * @param string $texto_plano Datos a cifrar
     * @param string $clave Clave de 32 bytes
     * @param string $datos_adicionales Datos autenticados adicionales (AAD)
     * @return array ['ciphertext' => string, 'nonce' => string, 'tag' => string]
     */
    protected function cifrar_aes_gcm($texto_plano, $clave, $datos_adicionales = '') {
        if (!sodium_crypto_aead_aes256gcm_is_available()) {
            // Fallback a ChaCha20-Poly1305 si AES-GCM no está disponible
            return $this->cifrar_chacha20_poly1305($texto_plano, $clave, $datos_adicionales);
        }

        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES);

        $ciphertext = sodium_crypto_aead_aes256gcm_encrypt(
            $texto_plano,
            $datos_adicionales,
            $nonce,
            $clave
        );

        return [
            'ciphertext' => $ciphertext,
            'nonce' => $nonce,
        ];
    }

    /**
     * Descifra datos usando AES-256-GCM
     *
     * @param string $ciphertext Datos cifrados (incluye tag)
     * @param string $clave Clave de 32 bytes
     * @param string $nonce Nonce usado para cifrar
     * @param string $datos_adicionales Datos autenticados adicionales (AAD)
     * @return string|false Texto plano o false si falla verificación
     */
    protected function descifrar_aes_gcm($ciphertext, $clave, $nonce, $datos_adicionales = '') {
        if (!sodium_crypto_aead_aes256gcm_is_available()) {
            return $this->descifrar_chacha20_poly1305($ciphertext, $clave, $nonce, $datos_adicionales);
        }

        try {
            return sodium_crypto_aead_aes256gcm_decrypt(
                $ciphertext,
                $datos_adicionales,
                $nonce,
                $clave
            );
        } catch (SodiumException $e) {
            return false;
        }
    }

    /**
     * Cifra usando ChaCha20-Poly1305 (fallback si AES-GCM no disponible)
     *
     * @param string $texto_plano Datos a cifrar
     * @param string $clave Clave de 32 bytes
     * @param string $datos_adicionales AAD
     * @return array ['ciphertext' => string, 'nonce' => string]
     */
    protected function cifrar_chacha20_poly1305($texto_plano, $clave, $datos_adicionales = '') {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);

        $ciphertext = sodium_crypto_aead_chacha20poly1305_ietf_encrypt(
            $texto_plano,
            $datos_adicionales,
            $nonce,
            $clave
        );

        return [
            'ciphertext' => $ciphertext,
            'nonce' => $nonce,
        ];
    }

    /**
     * Descifra usando ChaCha20-Poly1305
     *
     * @param string $ciphertext Datos cifrados
     * @param string $clave Clave de 32 bytes
     * @param string $nonce Nonce
     * @param string $datos_adicionales AAD
     * @return string|false Texto plano o false si falla
     */
    protected function descifrar_chacha20_poly1305($ciphertext, $clave, $nonce, $datos_adicionales = '') {
        try {
            return sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
                $ciphertext,
                $datos_adicionales,
                $nonce,
                $clave
            );
        } catch (SodiumException $e) {
            return false;
        }
    }

    /**
     * Firma datos usando Ed25519
     *
     * @param string $mensaje Mensaje a firmar
     * @param string $clave_privada Clave privada Ed25519
     * @return string Firma de 64 bytes
     */
    protected function firmar_ed25519($mensaje, $clave_privada) {
        return sodium_crypto_sign_detached($mensaje, $clave_privada);
    }

    /**
     * Verifica una firma Ed25519
     *
     * @param string $firma Firma a verificar
     * @param string $mensaje Mensaje original
     * @param string $clave_publica Clave pública Ed25519
     * @return bool True si la firma es válida
     */
    protected function verificar_firma_ed25519($firma, $mensaje, $clave_publica) {
        try {
            return sodium_crypto_sign_verify_detached($firma, $mensaje, $clave_publica);
        } catch (SodiumException $e) {
            return false;
        }
    }

    /**
     * Codifica datos a Base64 URL-safe
     *
     * @param string $datos Datos binarios
     * @return string Cadena Base64 URL-safe
     */
    protected function base64_url_encode($datos) {
        return rtrim(strtr(base64_encode($datos), '+/', '-_'), '=');
    }

    /**
     * Decodifica Base64 URL-safe
     *
     * @param string $datos Cadena Base64 URL-safe
     * @return string Datos binarios
     */
    protected function base64_url_decode($datos) {
        return base64_decode(strtr($datos, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($datos)) % 4));
    }

    /**
     * Genera un fingerprint legible de una clave pública
     * Formato: grupos de 5 dígitos decimales (60 dígitos total)
     *
     * @param string $clave_publica Clave pública
     * @return string Fingerprint en formato "12345 67890 ..."
     */
    protected function generar_fingerprint($clave_publica) {
        // Hash de la clave pública
        $hash = hash('sha256', $clave_publica, true);

        // Convertir a dígitos
        $numeros = [];
        for ($i = 0; $i < 30; $i++) {
            // Tomar 2 bytes y convertir a número 0-99999
            $valor = unpack('n', substr($hash . $hash, $i, 2))[1];
            $numeros[] = str_pad($valor % 100000, 5, '0', STR_PAD_LEFT);
        }

        // Tomar solo los primeros 12 grupos (60 dígitos)
        $grupos = array_slice($numeros, 0, 12);

        return implode(' ', $grupos);
    }

    /**
     * Genera un código de recuperación de 16 caracteres
     * Formato: XXXX-XXXX-XXXX-XXXX (letras y números)
     *
     * @return string Código de recuperación
     */
    protected function generar_codigo_recuperacion() {
        $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Sin I, O, 0, 1 para evitar confusión
        $codigo = '';
        $bytes_aleatorios = random_bytes(16);

        for ($i = 0; $i < 16; $i++) {
            $indice = ord($bytes_aleatorios[$i]) % strlen($caracteres);
            $codigo .= $caracteres[$indice];
        }

        // Formatear como XXXX-XXXX-XXXX-XXXX
        return implode('-', str_split($codigo, 4));
    }

    /**
     * Deriva una clave de cifrado a partir de un código de recuperación
     *
     * @param string $codigo_recuperacion Código de recuperación
     * @param string $sal Sal para derivación
     * @return string Clave de 32 bytes
     */
    protected function derivar_clave_de_codigo($codigo_recuperacion, $sal = '') {
        // Normalizar código (quitar guiones, mayúsculas)
        $codigo_normalizado = strtoupper(str_replace('-', '', $codigo_recuperacion));

        // Usar Argon2id para derivación resistente a fuerza bruta
        if (empty($sal)) {
            $sal = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        }

        return sodium_crypto_pwhash(
            32, // Longitud de clave
            $codigo_normalizado,
            $sal,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
        );
    }

    /**
     * Cifra una clave privada para almacenamiento seguro
     *
     * @param string $clave_privada Clave privada a cifrar
     * @param int $usuario_id ID del usuario
     * @return string Clave privada cifrada (base64)
     */
    protected function cifrar_clave_privada($clave_privada, $usuario_id) {
        // Derivar clave de cifrado usando el user_id y una clave maestra del servidor
        $clave_maestra = $this->obtener_clave_maestra_servidor();
        $clave_cifrado = $this->hkdf($clave_maestra, 32, "user_key_{$usuario_id}");

        $resultado = $this->cifrar_aes_gcm($clave_privada, $clave_cifrado, "private_key_{$usuario_id}");

        // Combinar nonce + ciphertext
        return base64_encode($resultado['nonce'] . $resultado['ciphertext']);
    }

    /**
     * Descifra una clave privada almacenada
     *
     * @param string $clave_cifrada Clave privada cifrada (base64)
     * @param int $usuario_id ID del usuario
     * @return string|false Clave privada o false si falla
     */
    protected function descifrar_clave_privada($clave_cifrada, $usuario_id) {
        $datos = base64_decode($clave_cifrada);
        if ($datos === false || strlen($datos) < SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES) {
            return false;
        }

        $nonce = substr($datos, 0, SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES);
        $ciphertext = substr($datos, SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES);

        $clave_maestra = $this->obtener_clave_maestra_servidor();
        $clave_cifrado = $this->hkdf($clave_maestra, 32, "user_key_{$usuario_id}");

        return $this->descifrar_aes_gcm($ciphertext, $clave_cifrado, $nonce, "private_key_{$usuario_id}");
    }

    /**
     * Obtiene o genera la clave maestra del servidor
     * Se almacena en la base de datos de forma segura
     *
     * @return string Clave maestra de 32 bytes
     */
    protected function obtener_clave_maestra_servidor() {
        $clave_almacenada = get_option('flavor_e2e_server_master_key');

        if (!$clave_almacenada) {
            // Generar nueva clave maestra
            $clave_nueva = random_bytes(32);
            update_option('flavor_e2e_server_master_key', base64_encode($clave_nueva));
            return $clave_nueva;
        }

        return base64_decode($clave_almacenada);
    }

    /**
     * Limpia memoria de datos sensibles
     *
     * @param string &$datos Datos a limpiar
     */
    protected function limpiar_memoria(&$datos) {
        if (is_string($datos)) {
            sodium_memzero($datos);
        }
    }

    /**
     * Concatena múltiples valores binarios
     *
     * @param mixed ...$valores Valores a concatenar
     * @return string Concatenación binaria
     */
    protected function concatenar_bytes(...$valores) {
        return implode('', $valores);
    }

    /**
     * Compara dos cadenas en tiempo constante
     *
     * @param string $cadena_a Primera cadena
     * @param string $cadena_b Segunda cadena
     * @return bool True si son iguales
     */
    protected function comparar_constante($cadena_a, $cadena_b) {
        return hash_equals($cadena_a, $cadena_b);
    }

    /**
     * Verifica si libsodium está disponible con las funciones necesarias
     *
     * @return array ['disponible' => bool, 'errores' => array]
     */
    public static function verificar_requisitos_crypto() {
        $errores = [];

        if (!extension_loaded('sodium')) {
            $errores[] = 'Extension sodium no está cargada';
            return ['disponible' => false, 'errores' => $errores];
        }

        // Verificar funciones necesarias
        $funciones_requeridas = [
            'sodium_crypto_box_keypair',
            'sodium_crypto_sign_keypair',
            'sodium_crypto_scalarmult',
            'sodium_crypto_aead_chacha20poly1305_ietf_encrypt',
            'sodium_crypto_sign_detached',
            'sodium_crypto_pwhash',
        ];

        foreach ($funciones_requeridas as $funcion) {
            if (!function_exists($funcion)) {
                $errores[] = "Función {$funcion} no disponible";
            }
        }

        return [
            'disponible' => empty($errores),
            'aes_gcm_disponible' => sodium_crypto_aead_aes256gcm_is_available(),
            'errores' => $errores,
        ];
    }
}
