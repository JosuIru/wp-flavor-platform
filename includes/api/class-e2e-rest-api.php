<?php
/**
 * REST API para cifrado E2E
 *
 * Proporciona endpoints para gestión de claves,
 * intercambio de prekey bundles y dispositivos.
 *
 * @package FlavorPlatform
 * @subpackage API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_E2E_REST_API {

    /**
     * Namespace de la API
     */
    const NAMESPACE = 'flavor/v1';

    /**
     * @var Flavor_Signal_Key_Manager
     */
    private $key_manager;

    /**
     * @var Flavor_Signal_Session_Manager
     */
    private $session_manager;

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'registrar_rutas']);
    }

    /**
     * Carga las dependencias de crypto
     */
    private function cargar_dependencias() {
        if (!$this->key_manager) {
            require_once FLAVOR_PLATFORM_PATH . 'includes/crypto/class-signal-key-manager.php';
            require_once FLAVOR_PLATFORM_PATH . 'includes/crypto/class-signal-session-manager.php';

            $this->key_manager = new Flavor_Signal_Key_Manager();
            $this->session_manager = new Flavor_Signal_Session_Manager($this->key_manager);
        }
    }

    /**
     * Registra las rutas REST
     */
    public function registrar_rutas() {
        // Verificar requisitos del sistema
        register_rest_route(self::NAMESPACE, '/e2e/status', [
            'methods' => 'GET',
            'callback' => [$this, 'obtener_estado'],
            'permission_callback' => [$this, 'verificar_autenticacion'],
        ]);

        // Registrar claves de un nuevo dispositivo
        register_rest_route(self::NAMESPACE, '/e2e/keys/register', [
            'methods' => 'POST',
            'callback' => [$this, 'registrar_claves'],
            'permission_callback' => [$this, 'verificar_autenticacion'],
            'args' => [
                'dispositivo_id' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'dispositivo_nombre' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'dispositivo_tipo' => [
                    'required' => false,
                    'type' => 'string',
                    'enum' => ['web', 'android', 'ios', 'desktop'],
                    'default' => 'web',
                ],
            ],
        ]);

        // Obtener PreKey Bundle de un usuario
        register_rest_route(self::NAMESPACE, '/e2e/prekey-bundle/(?P<usuario_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'obtener_prekey_bundle'],
            'permission_callback' => [$this, 'verificar_autenticacion'],
            'args' => [
                'usuario_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                ],
                'dispositivo_id' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Dispositivo específico. Si no se proporciona, devuelve todos los dispositivos.',
                ],
            ],
        ]);

        // Iniciar sesión E2E con otro usuario
        register_rest_route(self::NAMESPACE, '/e2e/session/init', [
            'methods' => 'POST',
            'callback' => [$this, 'iniciar_sesion'],
            'permission_callback' => [$this, 'verificar_autenticacion'],
            'args' => [
                'usuario_remoto_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
                'dispositivo_local_id' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'dispositivo_remoto_id' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);

        // Verificar si existe sesión
        register_rest_route(self::NAMESPACE, '/e2e/session/check', [
            'methods' => 'GET',
            'callback' => [$this, 'verificar_sesion'],
            'permission_callback' => [$this, 'verificar_autenticacion'],
            'args' => [
                'usuario_remoto_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        // Registrar dispositivo
        register_rest_route(self::NAMESPACE, '/e2e/device/register', [
            'methods' => 'POST',
            'callback' => [$this, 'registrar_dispositivo'],
            'permission_callback' => [$this, 'verificar_autenticacion'],
            'args' => [
                'nombre' => [
                    'required' => false,
                    'type' => 'string',
                ],
                'tipo' => [
                    'required' => false,
                    'type' => 'string',
                    'enum' => ['web', 'android', 'ios', 'desktop'],
                ],
            ],
        ]);

        // Listar dispositivos del usuario
        register_rest_route(self::NAMESPACE, '/e2e/devices', [
            'methods' => 'GET',
            'callback' => [$this, 'listar_dispositivos'],
            'permission_callback' => [$this, 'verificar_autenticacion'],
        ]);

        // Revocar dispositivo
        register_rest_route(self::NAMESPACE, '/e2e/device/(?P<dispositivo_id>[a-zA-Z0-9]+)/revoke', [
            'methods' => 'POST',
            'callback' => [$this, 'revocar_dispositivo'],
            'permission_callback' => [$this, 'verificar_autenticacion'],
            'args' => [
                'dispositivo_id' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);

        // Verificar identidad
        register_rest_route(self::NAMESPACE, '/e2e/verify', [
            'methods' => 'POST',
            'callback' => [$this, 'verificar_identidad'],
            'permission_callback' => [$this, 'verificar_autenticacion'],
            'args' => [
                'usuario_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
                'dispositivo_id' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'fingerprint' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);

        // Obtener identidades verificadas
        register_rest_route(self::NAMESPACE, '/e2e/verified', [
            'methods' => 'GET',
            'callback' => [$this, 'obtener_verificadas'],
            'permission_callback' => [$this, 'verificar_autenticacion'],
        ]);

        // Crear backup de claves
        register_rest_route(self::NAMESPACE, '/e2e/backup/create', [
            'methods' => 'POST',
            'callback' => [$this, 'crear_backup'],
            'permission_callback' => [$this, 'verificar_autenticacion'],
        ]);

        // Restaurar desde backup
        register_rest_route(self::NAMESPACE, '/e2e/backup/restore', [
            'methods' => 'POST',
            'callback' => [$this, 'restaurar_backup'],
            'permission_callback' => [$this, 'verificar_autenticacion'],
            'args' => [
                'codigo_recuperacion' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);

        // Regenerar prekeys
        register_rest_route(self::NAMESPACE, '/e2e/prekeys/regenerate', [
            'methods' => 'POST',
            'callback' => [$this, 'regenerar_prekeys'],
            'permission_callback' => [$this, 'verificar_autenticacion'],
            'args' => [
                'dispositivo_id' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'tipo' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['signed', 'onetime', 'ambas'],
                ],
            ],
        ]);
    }

    /**
     * Verifica que el usuario esté autenticado
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function verificar_autenticacion($request) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return new WP_Error(
                'rest_unauthorized',
                __('Debes iniciar sesión para usar E2E.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 401]
            );
        }

        return true;
    }

    /**
     * GET /e2e/status - Estado del sistema E2E
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function obtener_estado($request) {
        $this->cargar_dependencias();

        $usuario_id = get_current_user_id();

        // Verificar requisitos criptográficos
        require_once FLAVOR_PLATFORM_PATH . 'includes/crypto/trait-crypto-helpers.php';

        // Crear una clase temporal para usar el método estático del trait
        $crypto_check = new class {
            use Flavor_Crypto_Helpers_Trait;
        };

        $requisitos = $crypto_check::verificar_requisitos_crypto();

        // Verificar si el usuario tiene claves
        $tiene_claves = $this->key_manager->usuario_tiene_claves($usuario_id);

        // Obtener configuración E2E
        $e2e_habilitado = $this->obtener_configuracion_e2e();

        return rest_ensure_response([
            'e2e_habilitado' => $e2e_habilitado,
            'requisitos_crypto' => $requisitos,
            'usuario_tiene_claves' => $tiene_claves,
            'dispositivos' => $tiene_claves ? $this->key_manager->obtener_dispositivos_con_claves($usuario_id) : [],
        ]);
    }

    /**
     * POST /e2e/keys/register - Registrar claves para un dispositivo
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function registrar_claves($request) {
        $this->cargar_dependencias();

        $usuario_id = get_current_user_id();
        $dispositivo_id = $request->get_param('dispositivo_id');

        // Si no se proporciona dispositivo_id, generar uno
        if (empty($dispositivo_id)) {
            require_once FLAVOR_PLATFORM_PATH . 'includes/crypto/trait-crypto-helpers.php';
            $helper = new class {
                use Flavor_Crypto_Helpers_Trait;
                public function generar_id() {
                    return $this->generar_dispositivo_id();
                }
            };
            $dispositivo_id = $helper->generar_id();
        }

        // Verificar límite de dispositivos (máximo 5)
        $dispositivos_actuales = $this->key_manager->obtener_dispositivos_con_claves($usuario_id);
        if (count($dispositivos_actuales) >= 5) {
            return new WP_Error(
                'limite_dispositivos',
                __('Has alcanzado el límite de 5 dispositivos. Revoca alguno para añadir otro.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 400]
            );
        }

        // Inicializar claves
        $resultado = $this->key_manager->inicializar_claves($usuario_id, $dispositivo_id);

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        // Registrar información del dispositivo
        $this->registrar_info_dispositivo(
            $usuario_id,
            $dispositivo_id,
            $request->get_param('dispositivo_nombre'),
            $request->get_param('dispositivo_tipo'),
            count($dispositivos_actuales) === 0 // Es primario si es el primer dispositivo
        );

        return rest_ensure_response([
            'success' => true,
            'dispositivo_id' => $dispositivo_id,
            'fingerprint' => $resultado['fingerprint'],
            'identity_key_public' => $resultado['identity_key_public'],
        ]);
    }

    /**
     * GET /e2e/prekey-bundle/{usuario_id} - Obtener PreKey Bundle
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function obtener_prekey_bundle($request) {
        $this->cargar_dependencias();

        $usuario_id_solicitante = get_current_user_id();
        $usuario_id_objetivo = $request->get_param('usuario_id');
        $dispositivo_id = $request->get_param('dispositivo_id');

        // No permitir obtener el propio bundle
        if ($usuario_id_objetivo == $usuario_id_solicitante) {
            return new WP_Error(
                'bundle_propio',
                __('No puedes solicitar tu propio PreKey Bundle.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 400]
            );
        }

        // Si se especifica un dispositivo
        if ($dispositivo_id) {
            $bundle = $this->key_manager->obtener_prekey_bundle($usuario_id_objetivo, $dispositivo_id, $usuario_id_solicitante);

            if (is_wp_error($bundle)) {
                return $bundle;
            }

            return rest_ensure_response($bundle);
        }

        // Obtener bundles de todos los dispositivos
        $dispositivos = $this->key_manager->obtener_dispositivos_con_claves($usuario_id_objetivo);

        if (empty($dispositivos)) {
            return new WP_Error(
                'sin_claves',
                __('El usuario no tiene claves E2E configuradas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 404]
            );
        }

        $bundles = [];
        foreach ($dispositivos as $dispositivo) {
            $bundle = $this->key_manager->obtener_prekey_bundle(
                $usuario_id_objetivo,
                $dispositivo['dispositivo_id'],
                $usuario_id_solicitante
            );

            if (!is_wp_error($bundle)) {
                $bundles[] = $bundle;
            }
        }

        return rest_ensure_response([
            'usuario_id' => $usuario_id_objetivo,
            'bundles' => $bundles,
        ]);
    }

    /**
     * POST /e2e/session/init - Iniciar sesión E2E
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function iniciar_sesion($request) {
        $this->cargar_dependencias();

        $usuario_local_id = get_current_user_id();
        $usuario_remoto_id = $request->get_param('usuario_remoto_id');
        $dispositivo_local_id = $request->get_param('dispositivo_local_id');
        $dispositivo_remoto_id = $request->get_param('dispositivo_remoto_id');

        $resultado = $this->session_manager->iniciar_sesion(
            $usuario_local_id,
            $dispositivo_local_id,
            $usuario_remoto_id,
            $dispositivo_remoto_id
        );

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        return rest_ensure_response($resultado);
    }

    /**
     * GET /e2e/session/check - Verificar si existe sesión
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function verificar_sesion($request) {
        $this->cargar_dependencias();

        $usuario_local_id = get_current_user_id();
        $usuario_remoto_id = $request->get_param('usuario_remoto_id');

        $existe = $this->session_manager->sesion_existe($usuario_local_id, $usuario_remoto_id);

        return rest_ensure_response([
            'sesion_existe' => $existe,
        ]);
    }

    /**
     * POST /e2e/device/register - Registrar dispositivo
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function registrar_dispositivo($request) {
        // Este endpoint es un alias de registrar_claves
        return $this->registrar_claves($request);
    }

    /**
     * GET /e2e/devices - Listar dispositivos
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function listar_dispositivos($request) {
        $this->cargar_dependencias();

        $usuario_id = get_current_user_id();
        $dispositivos = $this->key_manager->obtener_dispositivos_con_claves($usuario_id);

        return rest_ensure_response([
            'dispositivos' => $dispositivos,
            'total' => count($dispositivos),
            'limite' => 5,
        ]);
    }

    /**
     * POST /e2e/device/{id}/revoke - Revocar dispositivo
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function revocar_dispositivo($request) {
        $this->cargar_dependencias();

        $usuario_id = get_current_user_id();
        $dispositivo_id = $request->get_param('dispositivo_id');

        // Verificar que el dispositivo pertenece al usuario
        $dispositivos = $this->key_manager->obtener_dispositivos_con_claves($usuario_id);
        $dispositivo_encontrado = false;

        foreach ($dispositivos as $dispositivo) {
            if ($dispositivo['dispositivo_id'] === $dispositivo_id) {
                $dispositivo_encontrado = true;
                break;
            }
        }

        if (!$dispositivo_encontrado) {
            return new WP_Error(
                'dispositivo_no_encontrado',
                __('Dispositivo no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 404]
            );
        }

        // Eliminar claves del dispositivo
        $eliminado = $this->key_manager->eliminar_claves_dispositivo($usuario_id, $dispositivo_id);

        if (!$eliminado) {
            return new WP_Error(
                'error_revocacion',
                __('Error al revocar el dispositivo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 500]
            );
        }

        // Marcar como revocado en la tabla de dispositivos
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'flavor_e2e_devices',
            [
                'revoked' => 1,
                'revoked_at' => current_time('mysql'),
            ],
            [
                'usuario_id' => $usuario_id,
                'dispositivo_id' => $dispositivo_id,
            ],
            ['%d', '%s'],
            ['%d', '%s']
        );

        return rest_ensure_response([
            'success' => true,
            'mensaje' => __('Dispositivo revocado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * POST /e2e/verify - Verificar identidad de otro usuario
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function verificar_identidad($request) {
        $this->cargar_dependencias();

        global $wpdb;

        $usuario_verificador_id = get_current_user_id();
        $usuario_verificado_id = $request->get_param('usuario_id');
        $dispositivo_id = $request->get_param('dispositivo_id');
        $fingerprint_proporcionado = $request->get_param('fingerprint');

        // Obtener la identity key del usuario a verificar
        $identity_key = $this->key_manager->obtener_identity_key($usuario_verificado_id, $dispositivo_id);

        if (!$identity_key) {
            return new WP_Error(
                'usuario_sin_claves',
                __('El usuario no tiene claves E2E para este dispositivo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 404]
            );
        }

        // Normalizar fingerprints para comparación
        $fingerprint_real = preg_replace('/\s+/', '', $identity_key['fingerprint']);
        $fingerprint_normalizado = preg_replace('/\s+/', '', $fingerprint_proporcionado);

        if ($fingerprint_real !== $fingerprint_normalizado) {
            return new WP_Error(
                'fingerprint_no_coincide',
                __('El código de seguridad no coincide. Verifica que estés comparando con el dispositivo correcto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 400]
            );
        }

        // Guardar verificación
        $tabla = $wpdb->prefix . 'flavor_e2e_verified_identities';

        // Eliminar verificación anterior si existe
        $wpdb->delete(
            $tabla,
            [
                'usuario_verificador_id' => $usuario_verificador_id,
                'usuario_verificado_id' => $usuario_verificado_id,
                'dispositivo_verificado_id' => $dispositivo_id,
            ],
            ['%d', '%d', '%s']
        );

        // Insertar nueva verificación
        $wpdb->insert(
            $tabla,
            [
                'usuario_verificador_id' => $usuario_verificador_id,
                'usuario_verificado_id' => $usuario_verificado_id,
                'dispositivo_verificado_id' => $dispositivo_id,
                'fingerprint' => $fingerprint_real,
            ],
            ['%d', '%d', '%s', '%s']
        );

        return rest_ensure_response([
            'success' => true,
            'verificado' => true,
            'mensaje' => __('Identidad verificada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * GET /e2e/verified - Obtener identidades verificadas
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function obtener_verificadas($request) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        $tabla = $wpdb->prefix . 'flavor_e2e_verified_identities';

        $verificadas = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vi.*, u.display_name as nombre_usuario
                 FROM {$tabla} vi
                 LEFT JOIN {$wpdb->users} u ON vi.usuario_verificado_id = u.ID
                 WHERE vi.usuario_verificador_id = %d
                 ORDER BY vi.verified_at DESC",
                $usuario_id
            ),
            ARRAY_A
        );

        return rest_ensure_response([
            'verificadas' => array_map(function($v) {
                return [
                    'usuario_id' => (int) $v['usuario_verificado_id'],
                    'nombre' => $v['nombre_usuario'],
                    'dispositivo_id' => $v['dispositivo_verificado_id'],
                    'fingerprint' => $v['fingerprint'],
                    'verificado_en' => $v['verified_at'],
                ];
            }, $verificadas),
        ]);
    }

    /**
     * POST /e2e/backup/create - Crear backup de claves
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function crear_backup($request) {
        $this->cargar_dependencias();

        global $wpdb;

        $usuario_id = get_current_user_id();

        // Verificar que el usuario tiene claves
        if (!$this->key_manager->usuario_tiene_claves($usuario_id)) {
            return new WP_Error(
                'sin_claves',
                __('No tienes claves E2E para respaldar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 400]
            );
        }

        // Generar código de recuperación
        require_once FLAVOR_PLATFORM_PATH . 'includes/crypto/trait-crypto-helpers.php';
        $helper = new class {
            use Flavor_Crypto_Helpers_Trait;

            public function generar_codigo() {
                return $this->generar_codigo_recuperacion();
            }

            public function derivar_clave($codigo, $sal) {
                return $this->derivar_clave_de_codigo($codigo, $sal);
            }

            public function cifrar($datos, $clave) {
                return $this->cifrar_aes_gcm($datos, $clave, 'backup');
            }

            public function encode($datos) {
                return $this->base64_url_encode($datos);
            }
        };

        $codigo_recuperacion = $helper->generar_codigo();
        $sal = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $clave_backup = $helper->derivar_clave($codigo_recuperacion, $sal);

        // Recopilar datos a respaldar
        $dispositivos = $this->key_manager->obtener_dispositivos_con_claves($usuario_id);
        $datos_backup = [];

        foreach ($dispositivos as $dispositivo) {
            // Obtener identity key (incluyendo la privada)
            $identity_key_cifrada = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT private_key_encrypted FROM {$wpdb->prefix}flavor_e2e_identity_keys
                     WHERE usuario_id = %d AND dispositivo_id = %s",
                    $usuario_id,
                    $dispositivo['dispositivo_id']
                )
            );

            $datos_backup['dispositivos'][] = [
                'dispositivo_id' => $dispositivo['dispositivo_id'],
                'identity_key_encrypted' => $identity_key_cifrada,
            ];
        }

        // Cifrar todo el bundle
        $json_datos = json_encode($datos_backup);
        $resultado_cifrado = $helper->cifrar($json_datos, $clave_backup);

        // Combinar sal + nonce + ciphertext
        $bundle_cifrado = base64_encode($sal . $resultado_cifrado['nonce'] . $resultado_cifrado['ciphertext']);

        // Hash del código para verificación
        $codigo_hash = hash('sha256', strtoupper(str_replace('-', '', $codigo_recuperacion)));

        // Guardar backup
        $tabla = $wpdb->prefix . 'flavor_e2e_key_backups';

        // Eliminar backups anteriores
        $wpdb->delete($tabla, ['usuario_id' => $usuario_id], ['%d']);

        // Insertar nuevo backup
        $wpdb->insert(
            $tabla,
            [
                'usuario_id' => $usuario_id,
                'backup_key_hash' => $codigo_hash,
                'encrypted_bundle' => $bundle_cifrado,
                'version' => 1,
            ],
            ['%d', '%s', '%s', '%d']
        );

        return rest_ensure_response([
            'success' => true,
            'codigo_recuperacion' => $codigo_recuperacion,
            'mensaje' => __('Guarda este código en un lugar seguro. Lo necesitarás para recuperar tus claves.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * POST /e2e/backup/restore - Restaurar desde backup
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function restaurar_backup($request) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        $codigo_recuperacion = $request->get_param('codigo_recuperacion');

        // Normalizar código
        $codigo_normalizado = strtoupper(str_replace('-', '', $codigo_recuperacion));
        $codigo_hash = hash('sha256', $codigo_normalizado);

        // Buscar backup
        $tabla = $wpdb->prefix . 'flavor_e2e_key_backups';
        $backup = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tabla} WHERE usuario_id = %d AND backup_key_hash = %s",
                $usuario_id,
                $codigo_hash
            ),
            ARRAY_A
        );

        if (!$backup) {
            return new WP_Error(
                'backup_no_encontrado',
                __('Código de recuperación inválido o no existe backup para este usuario.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 404]
            );
        }

        // Descifrar backup
        require_once FLAVOR_PLATFORM_PATH . 'includes/crypto/trait-crypto-helpers.php';
        $helper = new class {
            use Flavor_Crypto_Helpers_Trait;

            public function derivar_clave($codigo, $sal) {
                return $this->derivar_clave_de_codigo($codigo, $sal);
            }

            public function descifrar($ciphertext, $clave, $nonce) {
                return $this->descifrar_aes_gcm($ciphertext, $clave, $nonce, 'backup');
            }
        };

        $bundle = base64_decode($backup['encrypted_bundle']);
        $sal_length = SODIUM_CRYPTO_PWHASH_SALTBYTES;
        $nonce_length = SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES;
        if (!sodium_crypto_aead_aes256gcm_is_available()) {
            $nonce_length = SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES;
        }

        $sal = substr($bundle, 0, $sal_length);
        $nonce = substr($bundle, $sal_length, $nonce_length);
        $ciphertext = substr($bundle, $sal_length + $nonce_length);

        $clave_backup = $helper->derivar_clave($codigo_recuperacion, $sal);
        $datos_json = $helper->descifrar($ciphertext, $clave_backup, $nonce);

        if ($datos_json === false) {
            return new WP_Error(
                'descifrado_fallido',
                __('No se pudo descifrar el backup. Verifica el código de recuperación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 400]
            );
        }

        $datos = json_decode($datos_json, true);

        return rest_ensure_response([
            'success' => true,
            'dispositivos_recuperados' => count($datos['dispositivos'] ?? []),
            'mensaje' => __('Backup restaurado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * POST /e2e/prekeys/regenerate - Regenerar prekeys
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function regenerar_prekeys($request) {
        $this->cargar_dependencias();

        $usuario_id = get_current_user_id();
        $dispositivo_id = $request->get_param('dispositivo_id');
        $tipo = $request->get_param('tipo');

        // Verificar que el dispositivo existe
        $identity_key = $this->key_manager->obtener_identity_key($usuario_id, $dispositivo_id);
        if (!$identity_key) {
            return new WP_Error(
                'dispositivo_no_encontrado',
                __('Dispositivo no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 404]
            );
        }

        $resultado = ['success' => true];

        if ($tipo === 'signed' || $tipo === 'ambas') {
            try {
                $this->key_manager->generar_signed_prekey($usuario_id, $dispositivo_id);
                $resultado['signed_prekey_regenerada'] = true;
            } catch (Exception $e) {
                return new WP_Error('error_signed', $e->getMessage(), ['status' => 500]);
            }
        }

        if ($tipo === 'onetime' || $tipo === 'ambas') {
            $generadas = $this->key_manager->generar_one_time_prekeys($usuario_id, $dispositivo_id, 100);
            $resultado['one_time_prekeys_generadas'] = $generadas;
        }

        return rest_ensure_response($resultado);
    }

    /**
     * Registra información del dispositivo en la BD
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @param string|null $nombre
     * @param string $tipo
     * @param bool $is_primary
     */
    private function registrar_info_dispositivo($usuario_id, $dispositivo_id, $nombre, $tipo, $is_primary) {
        global $wpdb;

        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';

        // Generar nombre automático si no se proporciona
        if (empty($nombre)) {
            $nombre = $this->generar_nombre_dispositivo($user_agent, $tipo);
        }

        $wpdb->insert(
            $wpdb->prefix . 'flavor_e2e_devices',
            [
                'usuario_id' => $usuario_id,
                'dispositivo_id' => $dispositivo_id,
                'nombre' => $nombre,
                'tipo' => $tipo ?: 'web',
                'user_agent' => $user_agent,
                'is_primary' => $is_primary ? 1 : 0,
                'last_seen' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%d', '%s']
        );
    }

    /**
     * Genera un nombre descriptivo para el dispositivo
     *
     * @param string $user_agent
     * @param string $tipo
     * @return string
     */
    private function generar_nombre_dispositivo($user_agent, $tipo) {
        $navegador = 'Navegador';
        $so = '';

        // Detectar navegador
        if (strpos($user_agent, 'Firefox') !== false) {
            $navegador = 'Firefox';
        } elseif (strpos($user_agent, 'Chrome') !== false) {
            $navegador = 'Chrome';
        } elseif (strpos($user_agent, 'Safari') !== false) {
            $navegador = 'Safari';
        } elseif (strpos($user_agent, 'Edge') !== false) {
            $navegador = 'Edge';
        }

        // Detectar SO
        if (strpos($user_agent, 'Windows') !== false) {
            $so = 'Windows';
        } elseif (strpos($user_agent, 'Mac') !== false) {
            $so = 'Mac';
        } elseif (strpos($user_agent, 'Linux') !== false) {
            $so = 'Linux';
        } elseif (strpos($user_agent, 'Android') !== false) {
            $so = 'Android';
        } elseif (strpos($user_agent, 'iOS') !== false || strpos($user_agent, 'iPhone') !== false) {
            $so = 'iOS';
        }

        if ($tipo === 'android') {
            return 'Android' . ($so ? " ({$so})" : '');
        } elseif ($tipo === 'ios') {
            return 'iPhone/iPad';
        } elseif ($tipo === 'desktop') {
            return 'Aplicación de escritorio' . ($so ? " ({$so})" : '');
        }

        return "{$navegador}" . ($so ? " en {$so}" : '');
    }

    /**
     * Obtiene la configuración E2E del chat
     *
     * @return bool
     */
    private function obtener_configuracion_e2e() {
        $opciones_chat = get_option('flavor_chat_interno_settings', []);
        return !empty($opciones_chat['encriptacion_e2e']);
    }
}

// Inicializar la API
new Flavor_E2E_REST_API();
