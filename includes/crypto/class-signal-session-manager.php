<?php
/**
 * Gestor de sesiones Signal Protocol (Double Ratchet)
 *
 * Implementa el algoritmo Double Ratchet para cifrado de mensajes
 * con forward secrecy y break-in recovery.
 *
 * El Double Ratchet combina:
 * - Diffie-Hellman Ratchet: Renueva claves DH periódicamente
 * - Symmetric-key Ratchet: Deriva nuevas claves por mensaje
 *
 * @package FlavorChatIA
 * @subpackage Crypto
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/trait-crypto-helpers.php';
require_once __DIR__ . '/class-signal-key-manager.php';

class Flavor_Signal_Session_Manager {

    use Flavor_Crypto_Helpers_Trait;

    /**
     * Máximo número de claves de mensaje a almacenar para mensajes fuera de orden
     */
    const MAX_SKIP_KEYS = 1000;

    /**
     * @var wpdb
     */
    private $wpdb;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var Flavor_Signal_Key_Manager
     */
    private $key_manager;

    /**
     * Constructor
     *
     * @param Flavor_Signal_Key_Manager|null $key_manager
     */
    public function __construct($key_manager = null) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . 'flavor_';
        $this->key_manager = $key_manager ?: new Flavor_Signal_Key_Manager();
    }

    /**
     * Inicia una nueva sesión X3DH con otro usuario
     *
     * @param int $usuario_local_id ID del usuario local
     * @param string $dispositivo_local_id Dispositivo local
     * @param int $usuario_remoto_id ID del usuario remoto
     * @param string $dispositivo_remoto_id Dispositivo remoto
     * @return array|WP_Error Session data o error
     */
    public function iniciar_sesion($usuario_local_id, $dispositivo_local_id, $usuario_remoto_id, $dispositivo_remoto_id) {
        // Verificar si ya existe una sesión
        $sesion_existente = $this->obtener_sesion($usuario_local_id, $dispositivo_local_id, $usuario_remoto_id, $dispositivo_remoto_id);
        if ($sesion_existente) {
            return $sesion_existente;
        }

        // Obtener las claves locales
        $identity_local = $this->key_manager->obtener_identity_key($usuario_local_id, $dispositivo_local_id);
        if (!$identity_local) {
            return new WP_Error('sin_claves', 'El usuario local no tiene claves E2E configuradas');
        }

        // Obtener el PreKey Bundle del usuario remoto
        $prekey_bundle = $this->key_manager->obtener_prekey_bundle($usuario_remoto_id, $dispositivo_remoto_id, $usuario_local_id);
        if (is_wp_error($prekey_bundle)) {
            return $prekey_bundle;
        }

        // Ejecutar X3DH
        $resultado_x3dh = $this->ejecutar_x3dh_iniciador(
            $usuario_local_id,
            $dispositivo_local_id,
            $prekey_bundle
        );

        if (is_wp_error($resultado_x3dh)) {
            return $resultado_x3dh;
        }

        // Crear estado inicial del ratchet
        $estado_ratchet = $this->crear_estado_ratchet_iniciador(
            $resultado_x3dh['shared_secret'],
            $resultado_x3dh['ephemeral_public'],
            $this->base64_url_decode($prekey_bundle['signed_prekey']['public'])
        );

        // Generar ID de sesión
        $session_id = $this->generar_session_id($usuario_local_id, $dispositivo_local_id, $usuario_remoto_id, $dispositivo_remoto_id);

        // Guardar sesión
        $this->guardar_sesion(
            $session_id,
            $usuario_local_id,
            $dispositivo_local_id,
            $usuario_remoto_id,
            $dispositivo_remoto_id,
            $estado_ratchet
        );

        return [
            'success' => true,
            'session_id' => $session_id,
            'identity_key_remoto' => $prekey_bundle['identity_key']['public'],
            'fingerprint_remoto' => $prekey_bundle['identity_key']['fingerprint'],
            // Datos necesarios para el mensaje inicial (para que el receptor pueda inicializar)
            'x3dh_header' => [
                'identity_key' => $this->base64_url_encode($identity_local['public_key']),
                'ephemeral_key' => $this->base64_url_encode($resultado_x3dh['ephemeral_public']),
                'one_time_prekey_id' => $resultado_x3dh['one_time_prekey_id'],
            ],
        ];
    }

    /**
     * Ejecuta el protocolo X3DH como iniciador
     *
     * @param int $usuario_local_id
     * @param string $dispositivo_local_id
     * @param array $prekey_bundle Bundle del destinatario
     * @return array|WP_Error
     */
    private function ejecutar_x3dh_iniciador($usuario_local_id, $dispositivo_local_id, $prekey_bundle) {
        // Obtener claves locales
        $identity_private = $this->key_manager->obtener_identity_key_privada($usuario_local_id, $dispositivo_local_id);
        if (!$identity_private) {
            return new WP_Error('sin_clave_privada', 'No se pudo obtener la clave privada de identidad');
        }

        // Convertir Ed25519 a Curve25519
        $identity_private_curve = $this->ed25519_a_curve25519_privada($identity_private);

        // Generar par de claves efímeras
        $ephemeral = $this->generar_par_claves_curve25519();

        // Decodificar claves del bundle
        $identity_remote = $this->base64_url_decode($prekey_bundle['identity_key']['public']);
        $signed_prekey_remote = $this->base64_url_decode($prekey_bundle['signed_prekey']['public']);
        $signed_prekey_signature = $this->base64_url_decode($prekey_bundle['signed_prekey']['signature']);

        // Convertir identity remota de Ed25519 a Curve25519
        $identity_remote_curve = $this->ed25519_a_curve25519_publica($identity_remote);

        // Verificar la firma del signed prekey
        if (!$this->verificar_firma_ed25519($signed_prekey_signature, $signed_prekey_remote, $identity_remote)) {
            $this->limpiar_memoria($identity_private);
            $this->limpiar_memoria($identity_private_curve);
            return new WP_Error('firma_invalida', 'La firma del Signed PreKey es inválida');
        }

        // Calcular X3DH:
        // DH1 = DH(IK_A, SPK_B)
        $dh1 = $this->realizar_dh($identity_private_curve, $signed_prekey_remote);

        // DH2 = DH(EK_A, IK_B)
        $dh2 = $this->realizar_dh($ephemeral['private'], $identity_remote_curve);

        // DH3 = DH(EK_A, SPK_B)
        $dh3 = $this->realizar_dh($ephemeral['private'], $signed_prekey_remote);

        // DH4 = DH(EK_A, OPK_B) si hay one-time prekey
        $dh4 = '';
        $one_time_prekey_id = null;
        if (!empty($prekey_bundle['one_time_prekey'])) {
            $one_time_prekey = $this->base64_url_decode($prekey_bundle['one_time_prekey']['public']);
            $dh4 = $this->realizar_dh($ephemeral['private'], $one_time_prekey);
            $one_time_prekey_id = $prekey_bundle['one_time_prekey']['id'];
        }

        // Concatenar todos los DH y derivar la clave compartida
        $dh_concatenado = $this->concatenar_bytes($dh1, $dh2, $dh3, $dh4);

        $shared_secret = $this->hkdf(
            $dh_concatenado,
            32,
            'FlavorE2E_X3DH'
        );

        // Limpiar memoria
        $this->limpiar_memoria($identity_private);
        $this->limpiar_memoria($identity_private_curve);
        $this->limpiar_memoria($ephemeral['private']);
        $this->limpiar_memoria($dh1);
        $this->limpiar_memoria($dh2);
        $this->limpiar_memoria($dh3);
        if ($dh4) $this->limpiar_memoria($dh4);

        return [
            'shared_secret' => $shared_secret,
            'ephemeral_public' => $ephemeral['public'],
            'one_time_prekey_id' => $one_time_prekey_id,
        ];
    }

    /**
     * Procesa un mensaje X3DH entrante y crea la sesión como receptor
     *
     * @param int $usuario_local_id
     * @param string $dispositivo_local_id
     * @param int $usuario_remoto_id
     * @param string $dispositivo_remoto_id
     * @param array $x3dh_header Header X3DH del mensaje
     * @return array|WP_Error
     */
    public function procesar_x3dh_receptor($usuario_local_id, $dispositivo_local_id, $usuario_remoto_id, $dispositivo_remoto_id, $x3dh_header) {
        // Verificar si ya existe sesión
        $sesion_existente = $this->obtener_sesion($usuario_local_id, $dispositivo_local_id, $usuario_remoto_id, $dispositivo_remoto_id);
        if ($sesion_existente) {
            return $sesion_existente;
        }

        // Obtener claves locales
        $identity_private = $this->key_manager->obtener_identity_key_privada($usuario_local_id, $dispositivo_local_id);
        $signed_prekey = $this->key_manager->obtener_signed_prekey($usuario_local_id, $dispositivo_local_id);

        if (!$identity_private || !$signed_prekey) {
            return new WP_Error('sin_claves', 'No se encontraron las claves locales necesarias');
        }

        // Convertir Ed25519 a Curve25519
        $identity_private_curve = $this->ed25519_a_curve25519_privada($identity_private);

        // Decodificar claves del header
        $identity_remote = $this->base64_url_decode($x3dh_header['identity_key']);
        $ephemeral_remote = $this->base64_url_decode($x3dh_header['ephemeral_key']);
        $identity_remote_curve = $this->ed25519_a_curve25519_publica($identity_remote);

        // Obtener clave privada del signed prekey (necesitamos almacenarla temporalmente)
        // Por ahora usamos la misma derivación que para identity key
        $signed_prekey_private = $this->obtener_signed_prekey_privada($usuario_local_id, $dispositivo_local_id);
        if (!$signed_prekey_private) {
            return new WP_Error('sin_signed_prekey', 'No se encontró la clave privada del Signed PreKey');
        }

        // Calcular X3DH (orden inverso):
        // DH1 = DH(SPK_B, IK_A)
        $dh1 = $this->realizar_dh($signed_prekey_private, $identity_remote_curve);

        // DH2 = DH(IK_B, EK_A)
        $dh2 = $this->realizar_dh($identity_private_curve, $ephemeral_remote);

        // DH3 = DH(SPK_B, EK_A)
        $dh3 = $this->realizar_dh($signed_prekey_private, $ephemeral_remote);

        // DH4 si se usó one-time prekey
        $dh4 = '';
        if (!empty($x3dh_header['one_time_prekey_id'])) {
            $one_time_prekey_private = $this->obtener_one_time_prekey_privada(
                $usuario_local_id,
                $dispositivo_local_id,
                $x3dh_header['one_time_prekey_id']
            );
            if ($one_time_prekey_private) {
                $dh4 = $this->realizar_dh($one_time_prekey_private, $ephemeral_remote);
                $this->limpiar_memoria($one_time_prekey_private);
            }
        }

        // Derivar shared secret
        $dh_concatenado = $this->concatenar_bytes($dh1, $dh2, $dh3, $dh4);
        $shared_secret = $this->hkdf($dh_concatenado, 32, 'FlavorE2E_X3DH');

        // Crear estado del ratchet como receptor
        $estado_ratchet = $this->crear_estado_ratchet_receptor(
            $shared_secret,
            $ephemeral_remote,
            $signed_prekey['public_key']
        );

        // Guardar sesión
        $session_id = $this->generar_session_id($usuario_local_id, $dispositivo_local_id, $usuario_remoto_id, $dispositivo_remoto_id);

        $this->guardar_sesion(
            $session_id,
            $usuario_local_id,
            $dispositivo_local_id,
            $usuario_remoto_id,
            $dispositivo_remoto_id,
            $estado_ratchet
        );

        // Limpiar memoria
        $this->limpiar_memoria($identity_private);
        $this->limpiar_memoria($identity_private_curve);
        $this->limpiar_memoria($signed_prekey_private);

        return [
            'success' => true,
            'session_id' => $session_id,
        ];
    }

    /**
     * Crea el estado inicial del ratchet para el iniciador
     *
     * @param string $shared_secret Secreto X3DH
     * @param string $dh_public Clave pública DH local
     * @param string $dh_remote Clave pública DH remota (SPK)
     * @return array
     */
    private function crear_estado_ratchet_iniciador($shared_secret, $dh_public, $dh_remote) {
        // Generar par de claves DH para el ratchet
        $dh_pair = $this->generar_par_claves_curve25519();

        // Derivar root key y chain key
        $claves = $this->derivar_claves($shared_secret, [
            'root_key' => 32,
            'chain_key_send' => 32,
        ], 'FlavorE2E_Init');

        return [
            'dh_pair' => [
                'public' => base64_encode($dh_pair['public']),
                'private' => base64_encode($dh_pair['private']),
            ],
            'dh_remote_public' => base64_encode($dh_remote),
            'root_key' => base64_encode($claves['root_key']),
            'chain_key_send' => base64_encode($claves['chain_key_send']),
            'chain_key_recv' => null,
            'message_number_send' => 0,
            'message_number_recv' => 0,
            'previous_chain_length' => 0,
            'skipped_keys' => [],
        ];
    }

    /**
     * Crea el estado inicial del ratchet para el receptor
     *
     * @param string $shared_secret Secreto X3DH
     * @param string $dh_remote Clave pública efímera del iniciador
     * @param string $dh_public Clave pública SPK local
     * @return array
     */
    private function crear_estado_ratchet_receptor($shared_secret, $dh_remote, $dh_public) {
        $claves = $this->derivar_claves($shared_secret, [
            'root_key' => 32,
            'chain_key_recv' => 32,
        ], 'FlavorE2E_Init');

        return [
            'dh_pair' => null, // Se generará al enviar
            'dh_remote_public' => base64_encode($dh_remote),
            'root_key' => base64_encode($claves['root_key']),
            'chain_key_send' => null,
            'chain_key_recv' => base64_encode($claves['chain_key_recv']),
            'message_number_send' => 0,
            'message_number_recv' => 0,
            'previous_chain_length' => 0,
            'skipped_keys' => [],
        ];
    }

    /**
     * Cifra un mensaje usando la sesión establecida
     *
     * @param string $session_id ID de la sesión
     * @param string $mensaje Mensaje en texto plano
     * @param int $usuario_id ID del usuario que envía
     * @return array|WP_Error ['ciphertext' => string, 'header' => array]
     */
    public function cifrar_mensaje($session_id, $mensaje, $usuario_id) {
        $sesion = $this->obtener_sesion_por_id($session_id);
        if (!$sesion) {
            return new WP_Error('sin_sesion', 'No existe una sesión con este ID');
        }

        $estado = $sesion['estado'];

        // Si no tenemos chain_key_send, necesitamos hacer un DH ratchet
        if (empty($estado['chain_key_send'])) {
            $estado = $this->ejecutar_dh_ratchet_envio($estado);
        }

        // Derivar la clave de mensaje
        $chain_key = base64_decode($estado['chain_key_send']);
        $claves_mensaje = $this->derivar_claves($chain_key, [
            'message_key' => 32,
            'next_chain_key' => 32,
        ], 'FlavorE2E_Chain');

        // Cifrar el mensaje
        $resultado_cifrado = $this->cifrar_aes_gcm(
            $mensaje,
            $claves_mensaje['message_key'],
            json_encode(['n' => $estado['message_number_send']])
        );

        // Construir header del mensaje
        $header = [
            'dh' => base64_encode(base64_decode($estado['dh_pair']['public'])),
            'pn' => $estado['previous_chain_length'],
            'n' => $estado['message_number_send'],
        ];

        // Actualizar estado
        $estado['chain_key_send'] = base64_encode($claves_mensaje['next_chain_key']);
        $estado['message_number_send']++;

        // Guardar estado actualizado
        $this->actualizar_estado_sesion($session_id, $estado);

        // Limpiar memoria
        $this->limpiar_memoria($chain_key);
        $this->limpiar_memoria($claves_mensaje['message_key']);

        return [
            'ciphertext' => $this->base64_url_encode($resultado_cifrado['nonce'] . $resultado_cifrado['ciphertext']),
            'header' => $header,
        ];
    }

    /**
     * Descifra un mensaje usando la sesión establecida
     *
     * @param string $session_id ID de la sesión
     * @param string $ciphertext Mensaje cifrado (base64)
     * @param array $header Header del mensaje
     * @param int $usuario_id ID del usuario que recibe
     * @return string|WP_Error Mensaje descifrado o error
     */
    public function descifrar_mensaje($session_id, $ciphertext, $header, $usuario_id) {
        $sesion = $this->obtener_sesion_por_id($session_id);
        if (!$sesion) {
            return new WP_Error('sin_sesion', 'No existe una sesión con este ID');
        }

        $estado = $sesion['estado'];
        $ciphertext_bin = $this->base64_url_decode($ciphertext);

        // Extraer nonce y ciphertext
        $nonce_length = SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES;
        if (!sodium_crypto_aead_aes256gcm_is_available()) {
            $nonce_length = SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES;
        }

        $nonce = substr($ciphertext_bin, 0, $nonce_length);
        $encrypted = substr($ciphertext_bin, $nonce_length);

        // Intentar descifrar con claves saltadas primero
        $mensaje = $this->intentar_claves_saltadas($estado, $header, $encrypted, $nonce);
        if ($mensaje !== false) {
            $this->actualizar_estado_sesion($session_id, $estado);
            return $mensaje;
        }

        // Verificar si necesitamos hacer DH ratchet
        $dh_remoto_nuevo = base64_decode($header['dh']);
        $dh_remoto_actual = !empty($estado['dh_remote_public']) ? base64_decode($estado['dh_remote_public']) : '';

        if ($dh_remoto_nuevo !== $dh_remoto_actual) {
            // Saltar mensajes anteriores si los hay
            if (!empty($estado['chain_key_recv'])) {
                $this->saltar_claves_mensaje($estado, $header['pn']);
            }
            // Ejecutar DH ratchet
            $estado = $this->ejecutar_dh_ratchet_recepcion($estado, $dh_remoto_nuevo);
        }

        // Saltar hasta el número de mensaje correcto
        $this->saltar_claves_mensaje($estado, $header['n']);

        // Derivar clave de mensaje
        $chain_key = base64_decode($estado['chain_key_recv']);
        $claves_mensaje = $this->derivar_claves($chain_key, [
            'message_key' => 32,
            'next_chain_key' => 32,
        ], 'FlavorE2E_Chain');

        // Descifrar
        $mensaje = $this->descifrar_aes_gcm(
            $encrypted,
            $claves_mensaje['message_key'],
            $nonce,
            json_encode(['n' => $header['n']])
        );

        if ($mensaje === false) {
            return new WP_Error('descifrado_fallido', 'No se pudo descifrar el mensaje');
        }

        // Actualizar estado
        $estado['chain_key_recv'] = base64_encode($claves_mensaje['next_chain_key']);
        $estado['message_number_recv'] = $header['n'] + 1;

        $this->actualizar_estado_sesion($session_id, $estado);

        // Limpiar memoria
        $this->limpiar_memoria($chain_key);
        $this->limpiar_memoria($claves_mensaje['message_key']);

        return $mensaje;
    }

    /**
     * Ejecuta un paso del DH ratchet para envío
     *
     * @param array $estado Estado actual
     * @return array Estado actualizado
     */
    private function ejecutar_dh_ratchet_envio($estado) {
        // Generar nuevo par DH
        $nuevo_dh = $this->generar_par_claves_curve25519();

        // Calcular DH output
        $dh_remoto = base64_decode($estado['dh_remote_public']);
        $dh_output = $this->realizar_dh($nuevo_dh['private'], $dh_remoto);

        // Derivar nuevas claves
        $root_key = base64_decode($estado['root_key']);
        $material = $this->concatenar_bytes($root_key, $dh_output);
        $claves = $this->derivar_claves($material, [
            'root_key' => 32,
            'chain_key_send' => 32,
        ], 'FlavorE2E_Ratchet');

        $estado['previous_chain_length'] = $estado['message_number_send'];
        $estado['message_number_send'] = 0;
        $estado['dh_pair'] = [
            'public' => base64_encode($nuevo_dh['public']),
            'private' => base64_encode($nuevo_dh['private']),
        ];
        $estado['root_key'] = base64_encode($claves['root_key']);
        $estado['chain_key_send'] = base64_encode($claves['chain_key_send']);

        return $estado;
    }

    /**
     * Ejecuta un paso del DH ratchet para recepción
     *
     * @param array $estado Estado actual
     * @param string $dh_remoto_nuevo Nueva clave DH pública remota
     * @return array Estado actualizado
     */
    private function ejecutar_dh_ratchet_recepcion($estado, $dh_remoto_nuevo) {
        $root_key = base64_decode($estado['root_key']);

        // Primer ratchet: para recibir
        if (!empty($estado['dh_pair'])) {
            $dh_privado = base64_decode($estado['dh_pair']['private']);
            $dh_output = $this->realizar_dh($dh_privado, $dh_remoto_nuevo);
            $material = $this->concatenar_bytes($root_key, $dh_output);
            $claves = $this->derivar_claves($material, [
                'root_key' => 32,
                'chain_key_recv' => 32,
            ], 'FlavorE2E_Ratchet');
            $root_key = $claves['root_key'];
            $estado['chain_key_recv'] = base64_encode($claves['chain_key_recv']);
        }

        // Segundo ratchet: para enviar
        $nuevo_dh = $this->generar_par_claves_curve25519();
        $dh_output = $this->realizar_dh($nuevo_dh['private'], $dh_remoto_nuevo);
        $material = $this->concatenar_bytes($root_key, $dh_output);
        $claves = $this->derivar_claves($material, [
            'root_key' => 32,
            'chain_key_send' => 32,
        ], 'FlavorE2E_Ratchet');

        $estado['dh_pair'] = [
            'public' => base64_encode($nuevo_dh['public']),
            'private' => base64_encode($nuevo_dh['private']),
        ];
        $estado['dh_remote_public'] = base64_encode($dh_remoto_nuevo);
        $estado['root_key'] = base64_encode($claves['root_key']);
        $estado['chain_key_send'] = base64_encode($claves['chain_key_send']);
        $estado['previous_chain_length'] = $estado['message_number_recv'];
        $estado['message_number_recv'] = 0;

        return $estado;
    }

    /**
     * Salta claves de mensaje para manejo de mensajes fuera de orden
     *
     * @param array &$estado Estado actual (por referencia)
     * @param int $hasta Número de mensaje hasta donde saltar
     */
    private function saltar_claves_mensaje(&$estado, $hasta) {
        if (!isset($estado['chain_key_recv']) || $estado['message_number_recv'] >= $hasta) {
            return;
        }

        $chain_key = base64_decode($estado['chain_key_recv']);

        while ($estado['message_number_recv'] < $hasta && count($estado['skipped_keys']) < self::MAX_SKIP_KEYS) {
            $claves = $this->derivar_claves($chain_key, [
                'message_key' => 32,
                'next_chain_key' => 32,
            ], 'FlavorE2E_Chain');

            // Guardar clave saltada
            $estado['skipped_keys'][] = [
                'dh' => $estado['dh_remote_public'],
                'n' => $estado['message_number_recv'],
                'key' => base64_encode($claves['message_key']),
            ];

            $chain_key = $claves['next_chain_key'];
            $estado['message_number_recv']++;
        }

        $estado['chain_key_recv'] = base64_encode($chain_key);
    }

    /**
     * Intenta descifrar con claves saltadas
     *
     * @param array &$estado Estado actual
     * @param array $header Header del mensaje
     * @param string $encrypted Datos cifrados
     * @param string $nonce Nonce
     * @return string|false Mensaje o false
     */
    private function intentar_claves_saltadas(&$estado, $header, $encrypted, $nonce) {
        foreach ($estado['skipped_keys'] as $index => $clave_saltada) {
            if ($clave_saltada['dh'] === $header['dh'] && $clave_saltada['n'] === $header['n']) {
                $message_key = base64_decode($clave_saltada['key']);
                $mensaje = $this->descifrar_aes_gcm(
                    $encrypted,
                    $message_key,
                    $nonce,
                    json_encode(['n' => $header['n']])
                );

                if ($mensaje !== false) {
                    // Eliminar la clave usada
                    unset($estado['skipped_keys'][$index]);
                    $estado['skipped_keys'] = array_values($estado['skipped_keys']);
                    return $mensaje;
                }
            }
        }

        return false;
    }

    /**
     * Genera un ID único de sesión
     *
     * @param int $local_user
     * @param string $local_device
     * @param int $remote_user
     * @param string $remote_device
     * @return string
     */
    private function generar_session_id($local_user, $local_device, $remote_user, $remote_device) {
        $datos = "{$local_user}:{$local_device}:{$remote_user}:{$remote_device}";
        return hash('sha256', $datos);
    }

    /**
     * Guarda una sesión en la base de datos
     *
     * @param string $session_id
     * @param int $local_user
     * @param string $local_device
     * @param int $remote_user
     * @param string $remote_device
     * @param array $estado
     */
    private function guardar_sesion($session_id, $local_user, $local_device, $remote_user, $remote_device, $estado) {
        $estado_cifrado = $this->cifrar_estado_sesion($estado, $local_user);

        $this->wpdb->insert(
            $this->prefix . 'e2e_sessions',
            [
                'session_id' => $session_id,
                'usuario_local_id' => $local_user,
                'dispositivo_local_id' => $local_device,
                'usuario_remoto_id' => $remote_user,
                'dispositivo_remoto_id' => $remote_device,
                'state_encrypted' => $estado_cifrado,
                'root_key_hash' => hash('sha256', $estado['root_key']),
            ],
            ['%s', '%d', '%s', '%d', '%s', '%s', '%s']
        );
    }

    /**
     * Obtiene una sesión por sus participantes
     *
     * @param int $local_user
     * @param string $local_device
     * @param int $remote_user
     * @param string $remote_device
     * @return array|null
     */
    public function obtener_sesion($local_user, $local_device, $remote_user, $remote_device) {
        $session_id = $this->generar_session_id($local_user, $local_device, $remote_user, $remote_device);
        return $this->obtener_sesion_por_id($session_id);
    }

    /**
     * Obtiene una sesión por su ID
     *
     * @param string $session_id
     * @return array|null
     */
    public function obtener_sesion_por_id($session_id) {
        $resultado = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->prefix}e2e_sessions WHERE session_id = %s",
                $session_id
            ),
            ARRAY_A
        );

        if (!$resultado) {
            return null;
        }

        $estado = $this->descifrar_estado_sesion($resultado['state_encrypted'], $resultado['usuario_local_id']);

        return [
            'session_id' => $resultado['session_id'],
            'usuario_local_id' => (int) $resultado['usuario_local_id'],
            'dispositivo_local_id' => $resultado['dispositivo_local_id'],
            'usuario_remoto_id' => (int) $resultado['usuario_remoto_id'],
            'dispositivo_remoto_id' => $resultado['dispositivo_remoto_id'],
            'estado' => $estado,
            'message_number' => (int) $resultado['message_number'],
        ];
    }

    /**
     * Actualiza el estado de una sesión
     *
     * @param string $session_id
     * @param array $estado
     */
    private function actualizar_estado_sesion($session_id, $estado) {
        $sesion = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT usuario_local_id FROM {$this->prefix}e2e_sessions WHERE session_id = %s",
                $session_id
            )
        );

        if (!$sesion) {
            return;
        }

        $estado_cifrado = $this->cifrar_estado_sesion($estado, $sesion->usuario_local_id);

        $this->wpdb->update(
            $this->prefix . 'e2e_sessions',
            [
                'state_encrypted' => $estado_cifrado,
                'message_number' => max($estado['message_number_send'], $estado['message_number_recv']),
            ],
            ['session_id' => $session_id],
            ['%s', '%d'],
            ['%s']
        );
    }

    /**
     * Cifra el estado de la sesión para almacenamiento
     *
     * @param array $estado
     * @param int $usuario_id
     * @return string
     */
    private function cifrar_estado_sesion($estado, $usuario_id) {
        $json = json_encode($estado);
        $clave = $this->derivar_clave_estado($usuario_id);
        $resultado = $this->cifrar_aes_gcm($json, $clave, 'session_state');

        return base64_encode($resultado['nonce'] . $resultado['ciphertext']);
    }

    /**
     * Descifra el estado de una sesión
     *
     * @param string $estado_cifrado
     * @param int $usuario_id
     * @return array|null
     */
    private function descifrar_estado_sesion($estado_cifrado, $usuario_id) {
        $datos = base64_decode($estado_cifrado);
        $nonce_length = SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES;
        if (!sodium_crypto_aead_aes256gcm_is_available()) {
            $nonce_length = SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES;
        }

        $nonce = substr($datos, 0, $nonce_length);
        $ciphertext = substr($datos, $nonce_length);

        $clave = $this->derivar_clave_estado($usuario_id);
        $json = $this->descifrar_aes_gcm($ciphertext, $clave, $nonce, 'session_state');

        if ($json === false) {
            return null;
        }

        return json_decode($json, true);
    }

    /**
     * Deriva una clave para cifrar estados de sesión
     *
     * @param int $usuario_id
     * @return string
     */
    private function derivar_clave_estado($usuario_id) {
        $clave_maestra = $this->obtener_clave_maestra_servidor();
        return $this->hkdf($clave_maestra, 32, "session_state_{$usuario_id}");
    }

    /**
     * Obtiene la clave privada del Signed PreKey (temporal)
     * NOTA: Esta implementación requiere que almacenemos las claves privadas de prekeys
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @return string|false
     */
    private function obtener_signed_prekey_privada($usuario_id, $dispositivo_id) {
        // Por ahora derivamos de la identity key
        // En una implementación más completa, almacenaríamos las claves de prekey
        $identity_private = $this->key_manager->obtener_identity_key_privada($usuario_id, $dispositivo_id);
        if (!$identity_private) {
            return false;
        }

        // Derivar clave para el prekey actual
        $prekey = $this->key_manager->obtener_signed_prekey($usuario_id, $dispositivo_id);
        if (!$prekey) {
            return false;
        }

        // Generar deterministicamente desde la identity key + prekey_id
        $seed = $this->hkdf($identity_private, 32, "signed_prekey_{$prekey['prekey_id']}");

        // Recrear el par de claves
        $keypair = sodium_crypto_box_seed_keypair($seed);

        return sodium_crypto_box_secretkey($keypair);
    }

    /**
     * Obtiene la clave privada de una One-Time PreKey
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @param int $prekey_id
     * @return string|false
     */
    private function obtener_one_time_prekey_privada($usuario_id, $dispositivo_id, $prekey_id) {
        $identity_private = $this->key_manager->obtener_identity_key_privada($usuario_id, $dispositivo_id);
        if (!$identity_private) {
            return false;
        }

        // Derivar determinísticamente
        $seed = $this->hkdf($identity_private, 32, "one_time_prekey_{$prekey_id}");
        $keypair = sodium_crypto_box_seed_keypair($seed);

        return sodium_crypto_box_secretkey($keypair);
    }

    /**
     * Verifica si existe una sesión activa entre dos usuarios
     *
     * @param int $usuario_a
     * @param int $usuario_b
     * @return bool
     */
    public function sesion_existe($usuario_a, $usuario_b) {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->prefix}e2e_sessions
                 WHERE (usuario_local_id = %d AND usuario_remoto_id = %d)
                 OR (usuario_local_id = %d AND usuario_remoto_id = %d)",
                $usuario_a, $usuario_b,
                $usuario_b, $usuario_a
            )
        );

        return $count > 0;
    }

    /**
     * Elimina una sesión
     *
     * @param string $session_id
     * @return bool
     */
    public function eliminar_sesion($session_id) {
        return $this->wpdb->delete(
            $this->prefix . 'e2e_sessions',
            ['session_id' => $session_id],
            ['%s']
        ) !== false;
    }

    /**
     * Guarda o actualiza una sesión desde el cliente (API REST)
     *
     * @param array $datos Datos de la sesión
     * @return bool|WP_Error
     */
    public function guardar_sesion_cliente($datos) {
        $session_id = sanitize_text_field($datos['session_id'] ?? '');
        $usuario_local_id = (int) ($datos['usuario_local_id'] ?? 0);
        $dispositivo_local_id = sanitize_text_field($datos['dispositivo_local_id'] ?? '');
        $usuario_remoto_id = (int) ($datos['usuario_remoto_id'] ?? 0);
        $dispositivo_remoto_id = sanitize_text_field($datos['dispositivo_remoto_id'] ?? '');
        $state_encrypted = $datos['state_encrypted'] ?? '';

        if (empty($session_id) || empty($state_encrypted)) {
            return new WP_Error('datos_invalidos', 'Datos de sesión incompletos');
        }

        // Verificar si la sesión ya existe
        $existente = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->prefix}e2e_sessions WHERE session_id = %s",
                $session_id
            )
        );

        if ($existente) {
            // Actualizar
            $resultado = $this->wpdb->update(
                $this->prefix . 'e2e_sessions',
                [
                    'state_encrypted' => $state_encrypted,
                    'version' => $this->wpdb->get_var("SELECT version FROM {$this->prefix}e2e_sessions WHERE session_id = '$session_id'") + 1,
                ],
                ['session_id' => $session_id],
                ['%s', '%d'],
                ['%s']
            );
        } else {
            // Insertar nueva
            $resultado = $this->wpdb->insert(
                $this->prefix . 'e2e_sessions',
                [
                    'session_id' => $session_id,
                    'usuario_local_id' => $usuario_local_id,
                    'dispositivo_local_id' => $dispositivo_local_id,
                    'usuario_remoto_id' => $usuario_remoto_id,
                    'dispositivo_remoto_id' => $dispositivo_remoto_id,
                    'state_encrypted' => $state_encrypted,
                    'version' => 1,
                ],
                ['%s', '%d', '%s', '%d', '%s', '%s', '%d']
            );
        }

        return $resultado !== false;
    }

    /**
     * Obtiene una sesión por ID para el cliente (API REST)
     *
     * @param string $session_id ID de la sesión
     * @param int $usuario_id Usuario que solicita (para verificar permisos)
     * @return array|null
     */
    public function obtener_sesion_cliente($session_id, $usuario_id) {
        $sesion = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->prefix}e2e_sessions
                 WHERE session_id = %s
                 AND (usuario_local_id = %d OR usuario_remoto_id = %d)",
                $session_id,
                $usuario_id,
                $usuario_id
            ),
            ARRAY_A
        );

        if (!$sesion) {
            return null;
        }

        return [
            'session_id' => $sesion['session_id'],
            'usuario_local_id' => (int) $sesion['usuario_local_id'],
            'dispositivo_local_id' => $sesion['dispositivo_local_id'],
            'usuario_remoto_id' => (int) $sesion['usuario_remoto_id'],
            'dispositivo_remoto_id' => $sesion['dispositivo_remoto_id'],
            'state_encrypted' => $sesion['state_encrypted'],
            'version' => (int) $sesion['version'],
            'created_at' => $sesion['created_at'],
            'updated_at' => $sesion['updated_at'],
        ];
    }

    /**
     * Lista sesiones activas de un usuario
     *
     * @param int $usuario_id
     * @return array
     */
    public function listar_sesiones_usuario($usuario_id) {
        $sesiones = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT session_id, usuario_local_id, dispositivo_local_id,
                        usuario_remoto_id, dispositivo_remoto_id, version,
                        created_at, updated_at
                 FROM {$this->prefix}e2e_sessions
                 WHERE usuario_local_id = %d OR usuario_remoto_id = %d
                 ORDER BY updated_at DESC",
                $usuario_id,
                $usuario_id
            ),
            ARRAY_A
        );

        return array_map(function($s) use ($usuario_id) {
            return [
                'session_id' => $s['session_id'],
                'es_local' => (int) $s['usuario_local_id'] === $usuario_id,
                'usuario_remoto_id' => (int) $s['usuario_local_id'] === $usuario_id
                    ? (int) $s['usuario_remoto_id']
                    : (int) $s['usuario_local_id'],
                'dispositivo_remoto_id' => (int) $s['usuario_local_id'] === $usuario_id
                    ? $s['dispositivo_remoto_id']
                    : $s['dispositivo_local_id'],
                'version' => (int) $s['version'],
                'updated_at' => $s['updated_at'],
            ];
        }, $sesiones);
    }
}
