<?php
/**
 * Gestor de claves Signal Protocol (X3DH)
 *
 * Implementa el protocolo X3DH (Extended Triple Diffie-Hellman) para
 * establecer claves de sesión compartidas de forma segura.
 *
 * Claves gestionadas:
 * - Identity Key: Clave de identidad a largo plazo (Ed25519)
 * - Signed PreKey: Clave firmada que rota periódicamente
 * - One-Time PreKeys: Claves de un solo uso para forward secrecy
 *
 * @package FlavorChatIA
 * @subpackage Crypto
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/trait-crypto-helpers.php';

class Flavor_Signal_Key_Manager {

    use Flavor_Crypto_Helpers_Trait;

    /**
     * Duración en días de las Signed PreKeys
     */
    const SIGNED_PREKEY_VALIDITY_DAYS = 30;

    /**
     * Número de One-Time PreKeys a mantener disponibles
     */
    const ONE_TIME_PREKEYS_COUNT = 100;

    /**
     * Umbral para regenerar One-Time PreKeys
     */
    const ONE_TIME_PREKEYS_THRESHOLD = 20;

    /**
     * @var wpdb
     */
    private $wpdb;

    /**
     * @var string Prefijo de tablas
     */
    private $prefix;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . 'flavor_';
    }

    /**
     * Inicializa las claves E2E para un usuario/dispositivo
     * Debe llamarse cuando un usuario accede al chat por primera vez
     *
     * @param int $usuario_id ID del usuario
     * @param string $dispositivo_id ID del dispositivo
     * @return array|WP_Error Datos de claves públicas o error
     */
    public function inicializar_claves($usuario_id, $dispositivo_id) {
        // Verificar requisitos criptográficos
        $requisitos = self::verificar_requisitos_crypto();
        if (!$requisitos['disponible']) {
            return new WP_Error('crypto_no_disponible', 'Requisitos criptográficos no disponibles', $requisitos['errores']);
        }

        // Verificar si ya existen claves para este dispositivo
        $claves_existentes = $this->obtener_identity_key($usuario_id, $dispositivo_id);
        if ($claves_existentes) {
            return new WP_Error('claves_existentes', 'Este dispositivo ya tiene claves registradas');
        }

        $this->wpdb->query('START TRANSACTION');

        try {
            // 1. Generar Identity Key (Ed25519 para firma + conversión a Curve25519 para DH)
            $identity_key = $this->generar_par_claves_ed25519();
            $identity_key_curve = [
                'public' => $this->ed25519_a_curve25519_publica($identity_key['public']),
                'private' => $this->ed25519_a_curve25519_privada($identity_key['private']),
            ];

            // Calcular fingerprint
            $fingerprint = $this->generar_fingerprint($identity_key['public']);

            // Guardar Identity Key
            $this->guardar_identity_key($usuario_id, $dispositivo_id, $identity_key, $fingerprint);

            // 2. Generar Signed PreKey
            $signed_prekey_id = $this->generar_signed_prekey($usuario_id, $dispositivo_id, $identity_key['private']);

            // 3. Generar One-Time PreKeys
            $this->generar_one_time_prekeys($usuario_id, $dispositivo_id, self::ONE_TIME_PREKEYS_COUNT);

            $this->wpdb->query('COMMIT');

            // Limpiar claves privadas de memoria
            $this->limpiar_memoria($identity_key['private']);
            $this->limpiar_memoria($identity_key_curve['private']);

            return [
                'success' => true,
                'usuario_id' => $usuario_id,
                'dispositivo_id' => $dispositivo_id,
                'fingerprint' => $fingerprint,
                'identity_key_public' => $this->base64_url_encode($identity_key['public']),
            ];

        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return new WP_Error('error_generacion', 'Error al generar claves: ' . $e->getMessage());
        }
    }

    /**
     * Guarda la Identity Key en la base de datos
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @param array $identity_key ['public' => string, 'private' => string]
     * @param string $fingerprint
     */
    private function guardar_identity_key($usuario_id, $dispositivo_id, $identity_key, $fingerprint) {
        $clave_privada_cifrada = $this->cifrar_clave_privada($identity_key['private'], $usuario_id);

        $this->wpdb->insert(
            $this->prefix . 'e2e_identity_keys',
            [
                'usuario_id' => $usuario_id,
                'dispositivo_id' => $dispositivo_id,
                'public_key' => base64_encode($identity_key['public']),
                'private_key_encrypted' => $clave_privada_cifrada,
                'key_fingerprint' => $fingerprint,
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Obtiene la Identity Key pública de un usuario/dispositivo
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @return array|null
     */
    public function obtener_identity_key($usuario_id, $dispositivo_id) {
        $resultado = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->prefix}e2e_identity_keys
                 WHERE usuario_id = %d AND dispositivo_id = %s",
                $usuario_id,
                $dispositivo_id
            ),
            ARRAY_A
        );

        if (!$resultado) {
            return null;
        }

        return [
            'id' => (int) $resultado['id'],
            'usuario_id' => (int) $resultado['usuario_id'],
            'dispositivo_id' => $resultado['dispositivo_id'],
            'public_key' => base64_decode($resultado['public_key']),
            'public_key_base64' => $resultado['public_key'],
            'fingerprint' => $resultado['key_fingerprint'],
            'created_at' => $resultado['created_at'],
        ];
    }

    /**
     * Obtiene la clave privada de identidad descifrada
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @return string|false Clave privada o false si falla
     */
    public function obtener_identity_key_privada($usuario_id, $dispositivo_id) {
        $resultado = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT private_key_encrypted FROM {$this->prefix}e2e_identity_keys
                 WHERE usuario_id = %d AND dispositivo_id = %s",
                $usuario_id,
                $dispositivo_id
            )
        );

        if (!$resultado) {
            return false;
        }

        return $this->descifrar_clave_privada($resultado, $usuario_id);
    }

    /**
     * Genera una nueva Signed PreKey y la almacena
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @param string $identity_private_key Clave privada de identidad para firmar
     * @return int ID de la prekey generada
     */
    public function generar_signed_prekey($usuario_id, $dispositivo_id, $identity_private_key = null) {
        // Si no se proporciona la clave privada, obtenerla
        if ($identity_private_key === null) {
            $identity_private_key = $this->obtener_identity_key_privada($usuario_id, $dispositivo_id);
            if (!$identity_private_key) {
                throw new Exception('No se encontró la clave de identidad');
            }
        }

        // Generar par de claves Curve25519 para la prekey
        $prekey = $this->generar_par_claves_curve25519();

        // Generar ID único para la prekey
        $prekey_id = $this->generar_prekey_id($usuario_id, $dispositivo_id, 'signed');

        // Firmar la clave pública con la identity key
        $firma = $this->firmar_ed25519($prekey['public'], $identity_private_key);

        // Calcular fecha de expiración
        $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+' . self::SIGNED_PREKEY_VALIDITY_DAYS . ' days'));

        // Cifrar y guardar
        $clave_privada_cifrada = $this->cifrar_clave_privada($prekey['private'], $usuario_id);

        // Eliminar signed prekeys antiguas del mismo dispositivo
        $this->wpdb->delete(
            $this->prefix . 'e2e_signed_prekeys',
            [
                'usuario_id' => $usuario_id,
                'dispositivo_id' => $dispositivo_id,
            ],
            ['%d', '%s']
        );

        // Insertar nueva
        $this->wpdb->insert(
            $this->prefix . 'e2e_signed_prekeys',
            [
                'usuario_id' => $usuario_id,
                'dispositivo_id' => $dispositivo_id,
                'prekey_id' => $prekey_id,
                'public_key' => base64_encode($prekey['public']),
                'signature' => base64_encode($firma),
                'expires_at' => $fecha_expiracion,
            ],
            ['%d', '%s', '%d', '%s', '%s', '%s']
        );

        // Limpiar memoria
        $this->limpiar_memoria($prekey['private']);
        if ($identity_private_key) {
            $this->limpiar_memoria($identity_private_key);
        }

        return $prekey_id;
    }

    /**
     * Obtiene la Signed PreKey activa de un usuario/dispositivo
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @return array|null
     */
    public function obtener_signed_prekey($usuario_id, $dispositivo_id) {
        $resultado = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->prefix}e2e_signed_prekeys
                 WHERE usuario_id = %d AND dispositivo_id = %s
                 AND expires_at > NOW()
                 ORDER BY created_at DESC LIMIT 1",
                $usuario_id,
                $dispositivo_id
            ),
            ARRAY_A
        );

        if (!$resultado) {
            return null;
        }

        return [
            'prekey_id' => (int) $resultado['prekey_id'],
            'public_key' => base64_decode($resultado['public_key']),
            'public_key_base64' => $resultado['public_key'],
            'signature' => base64_decode($resultado['signature']),
            'signature_base64' => $resultado['signature'],
            'expires_at' => $resultado['expires_at'],
        ];
    }

    /**
     * Genera múltiples One-Time PreKeys
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @param int $cantidad Cantidad de prekeys a generar
     * @return int Número de prekeys generadas
     */
    public function generar_one_time_prekeys($usuario_id, $dispositivo_id, $cantidad = 100) {
        $prekeys_generadas = 0;

        for ($i = 0; $i < $cantidad; $i++) {
            $prekey = $this->generar_par_claves_curve25519();
            $prekey_id = $this->generar_prekey_id($usuario_id, $dispositivo_id, 'onetime');

            $this->wpdb->insert(
                $this->prefix . 'e2e_one_time_prekeys',
                [
                    'usuario_id' => $usuario_id,
                    'dispositivo_id' => $dispositivo_id,
                    'prekey_id' => $prekey_id,
                    'public_key' => base64_encode($prekey['public']),
                    'used' => 0,
                ],
                ['%d', '%s', '%d', '%s', '%d']
            );

            $this->limpiar_memoria($prekey['private']);
            $prekeys_generadas++;
        }

        return $prekeys_generadas;
    }

    /**
     * Obtiene una One-Time PreKey disponible y la marca como usada
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @param int $solicitante_id ID del usuario que solicita la prekey
     * @return array|null
     */
    public function consumir_one_time_prekey($usuario_id, $dispositivo_id, $solicitante_id) {
        // Obtener una prekey no usada
        $prekey = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->prefix}e2e_one_time_prekeys
                 WHERE usuario_id = %d AND dispositivo_id = %s AND used = 0
                 ORDER BY id ASC LIMIT 1",
                $usuario_id,
                $dispositivo_id
            ),
            ARRAY_A
        );

        if (!$prekey) {
            // No hay prekeys disponibles, se usará sin one-time prekey
            return null;
        }

        // Marcar como usada
        $this->wpdb->update(
            $this->prefix . 'e2e_one_time_prekeys',
            [
                'used' => 1,
                'used_at' => current_time('mysql'),
                'used_by_usuario_id' => $solicitante_id,
            ],
            ['id' => $prekey['id']],
            ['%d', '%s', '%d'],
            ['%d']
        );

        // Verificar si necesitamos regenerar prekeys
        $this->verificar_regenerar_prekeys($usuario_id, $dispositivo_id);

        return [
            'prekey_id' => (int) $prekey['prekey_id'],
            'public_key' => base64_decode($prekey['public_key']),
            'public_key_base64' => $prekey['public_key'],
        ];
    }

    /**
     * Verifica si es necesario regenerar One-Time PreKeys
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     */
    private function verificar_regenerar_prekeys($usuario_id, $dispositivo_id) {
        $prekeys_disponibles = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->prefix}e2e_one_time_prekeys
                 WHERE usuario_id = %d AND dispositivo_id = %s AND used = 0",
                $usuario_id,
                $dispositivo_id
            )
        );

        if ($prekeys_disponibles < self::ONE_TIME_PREKEYS_THRESHOLD) {
            // Programar regeneración asíncrona
            wp_schedule_single_event(
                time(),
                'flavor_e2e_regenerar_prekeys',
                [$usuario_id, $dispositivo_id, self::ONE_TIME_PREKEYS_COUNT - $prekeys_disponibles]
            );
        }
    }

    /**
     * Genera un ID único para prekeys
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @param string $tipo 'signed' o 'onetime'
     * @return int
     */
    private function generar_prekey_id($usuario_id, $dispositivo_id, $tipo) {
        $tabla = ($tipo === 'signed')
            ? $this->prefix . 'e2e_signed_prekeys'
            : $this->prefix . 'e2e_one_time_prekeys';

        $max_id = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT MAX(prekey_id) FROM {$tabla}
                 WHERE usuario_id = %d AND dispositivo_id = %s",
                $usuario_id,
                $dispositivo_id
            )
        );

        return ($max_id ?: 0) + 1;
    }

    /**
     * Obtiene el PreKey Bundle completo de un usuario/dispositivo
     * Este bundle se usa para iniciar una sesión X3DH
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @param int $solicitante_id ID del usuario que solicita el bundle
     * @return array|WP_Error
     */
    public function obtener_prekey_bundle($usuario_id, $dispositivo_id, $solicitante_id) {
        // Obtener Identity Key
        $identity_key = $this->obtener_identity_key($usuario_id, $dispositivo_id);
        if (!$identity_key) {
            return new WP_Error('no_identity_key', 'El usuario no tiene claves E2E configuradas');
        }

        // Obtener Signed PreKey
        $signed_prekey = $this->obtener_signed_prekey($usuario_id, $dispositivo_id);
        if (!$signed_prekey) {
            // Intentar regenerar si expiró
            try {
                $this->generar_signed_prekey($usuario_id, $dispositivo_id);
                $signed_prekey = $this->obtener_signed_prekey($usuario_id, $dispositivo_id);
            } catch (Exception $e) {
                return new WP_Error('no_signed_prekey', 'No hay Signed PreKey válida disponible');
            }
        }

        // Obtener y consumir One-Time PreKey (opcional)
        $one_time_prekey = $this->consumir_one_time_prekey($usuario_id, $dispositivo_id, $solicitante_id);

        return [
            'usuario_id' => $usuario_id,
            'dispositivo_id' => $dispositivo_id,
            'identity_key' => [
                'public' => $this->base64_url_encode($identity_key['public_key']),
                'fingerprint' => $identity_key['fingerprint'],
            ],
            'signed_prekey' => [
                'id' => $signed_prekey['prekey_id'],
                'public' => $this->base64_url_encode($signed_prekey['public_key']),
                'signature' => $this->base64_url_encode($signed_prekey['signature']),
            ],
            'one_time_prekey' => $one_time_prekey ? [
                'id' => $one_time_prekey['prekey_id'],
                'public' => $this->base64_url_encode($one_time_prekey['public_key']),
            ] : null,
        ];
    }

    /**
     * Obtiene todos los dispositivos con claves de un usuario
     *
     * @param int $usuario_id
     * @return array Lista de dispositivos
     */
    public function obtener_dispositivos_con_claves($usuario_id) {
        $resultados = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT ik.dispositivo_id, ik.key_fingerprint, ik.created_at,
                        d.nombre, d.tipo, d.is_primary, d.last_seen
                 FROM {$this->prefix}e2e_identity_keys ik
                 LEFT JOIN {$this->prefix}e2e_devices d
                    ON ik.usuario_id = d.usuario_id AND ik.dispositivo_id = d.dispositivo_id
                 WHERE ik.usuario_id = %d
                 ORDER BY ik.created_at DESC",
                $usuario_id
            ),
            ARRAY_A
        );

        return array_map(function($row) {
            return [
                'dispositivo_id' => $row['dispositivo_id'],
                'fingerprint' => $row['key_fingerprint'],
                'nombre' => $row['nombre'] ?: 'Dispositivo desconocido',
                'tipo' => $row['tipo'] ?: 'web',
                'is_primary' => (bool) $row['is_primary'],
                'last_seen' => $row['last_seen'],
                'created_at' => $row['created_at'],
            ];
        }, $resultados);
    }

    /**
     * Verifica si un usuario tiene claves E2E configuradas
     *
     * @param int $usuario_id
     * @return bool
     */
    public function usuario_tiene_claves($usuario_id) {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->prefix}e2e_identity_keys
                 WHERE usuario_id = %d",
                $usuario_id
            )
        );

        return $count > 0;
    }

    /**
     * Elimina todas las claves de un dispositivo
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @return bool
     */
    public function eliminar_claves_dispositivo($usuario_id, $dispositivo_id) {
        $this->wpdb->query('START TRANSACTION');

        try {
            // Eliminar identity key
            $this->wpdb->delete(
                $this->prefix . 'e2e_identity_keys',
                ['usuario_id' => $usuario_id, 'dispositivo_id' => $dispositivo_id],
                ['%d', '%s']
            );

            // Eliminar signed prekeys
            $this->wpdb->delete(
                $this->prefix . 'e2e_signed_prekeys',
                ['usuario_id' => $usuario_id, 'dispositivo_id' => $dispositivo_id],
                ['%d', '%s']
            );

            // Eliminar one-time prekeys
            $this->wpdb->delete(
                $this->prefix . 'e2e_one_time_prekeys',
                ['usuario_id' => $usuario_id, 'dispositivo_id' => $dispositivo_id],
                ['%d', '%s']
            );

            // Eliminar sesiones donde participa este dispositivo
            $this->wpdb->query(
                $this->wpdb->prepare(
                    "DELETE FROM {$this->prefix}e2e_sessions
                     WHERE (usuario_local_id = %d AND dispositivo_local_id = %s)
                     OR (usuario_remoto_id = %d AND dispositivo_remoto_id = %s)",
                    $usuario_id,
                    $dispositivo_id,
                    $usuario_id,
                    $dispositivo_id
                )
            );

            $this->wpdb->query('COMMIT');
            return true;

        } catch (Exception $e) {
            $this->wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * Rota las Signed PreKeys que han expirado o están por expirar
     * Debe ejecutarse periódicamente via cron
     */
    public function rotar_signed_prekeys_expiradas() {
        // Obtener signed prekeys que expiran en los próximos 7 días
        $prekeys_a_rotar = $this->wpdb->get_results(
            "SELECT DISTINCT usuario_id, dispositivo_id
             FROM {$this->prefix}e2e_signed_prekeys
             WHERE expires_at < DATE_ADD(NOW(), INTERVAL 7 DAY)",
            ARRAY_A
        );

        $rotadas = 0;

        foreach ($prekeys_a_rotar as $prekey) {
            try {
                $this->generar_signed_prekey($prekey['usuario_id'], $prekey['dispositivo_id']);
                $rotadas++;
            } catch (Exception $e) {
                error_log("Error rotando signed prekey para usuario {$prekey['usuario_id']}: " . $e->getMessage());
            }
        }

        return $rotadas;
    }

    /**
     * Limpia prekeys usadas y expiradas
     * Debe ejecutarse periódicamente via cron
     *
     * @return int Número de registros eliminados
     */
    public function limpiar_prekeys_antiguas() {
        // Eliminar one-time prekeys usadas hace más de 30 días
        $eliminadas_onetime = $this->wpdb->query(
            "DELETE FROM {$this->prefix}e2e_one_time_prekeys
             WHERE used = 1 AND used_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        // Eliminar signed prekeys expiradas hace más de 7 días
        $eliminadas_signed = $this->wpdb->query(
            "DELETE FROM {$this->prefix}e2e_signed_prekeys
             WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        return $eliminadas_onetime + $eliminadas_signed;
    }
}
