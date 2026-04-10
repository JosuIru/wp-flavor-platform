<?php
/**
 * Gestor de dispositivos E2E
 *
 * Maneja el registro, vinculación y revocación de dispositivos
 * para el cifrado multi-dispositivo.
 *
 * @package FlavorPlatform
 * @subpackage Crypto
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Device_Manager {

    /**
     * Máximo número de dispositivos por usuario
     */
    const MAX_DISPOSITIVOS = 5;

    /**
     * @var wpdb
     */
    private $wpdb;

    /**
     * @var string
     */
    private $tabla_devices;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tabla_devices = $wpdb->prefix . 'flavor_e2e_devices';
    }

    /**
     * Registra un nuevo dispositivo para un usuario
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @param array $datos Datos adicionales del dispositivo
     * @return bool|WP_Error
     */
    public function registrar_dispositivo($usuario_id, $dispositivo_id, $datos = []) {
        // Verificar límite de dispositivos
        $total_dispositivos = $this->contar_dispositivos_activos($usuario_id);

        if ($total_dispositivos >= self::MAX_DISPOSITIVOS) {
            return new WP_Error(
                'limite_dispositivos',
                sprintf(
                    __('Has alcanzado el límite de %d dispositivos. Revoca alguno para añadir otro.', 'flavor-platform'),
                    self::MAX_DISPOSITIVOS
                )
            );
        }

        // Verificar si el dispositivo ya existe
        $existe = $this->obtener_dispositivo($usuario_id, $dispositivo_id);
        if ($existe && !$existe['revoked']) {
            return new WP_Error(
                'dispositivo_existente',
                __('Este dispositivo ya está registrado.', 'flavor-platform')
            );
        }

        // Determinar si es el dispositivo primario
        $es_primario = $total_dispositivos === 0 ? 1 : 0;

        // Insertar o actualizar dispositivo
        if ($existe) {
            // Reactivar dispositivo revocado
            $resultado = $this->wpdb->update(
                $this->tabla_devices,
                [
                    'nombre' => sanitize_text_field($datos['nombre'] ?? $existe['nombre']),
                    'tipo' => sanitize_text_field($datos['tipo'] ?? $existe['tipo']),
                    'user_agent' => sanitize_text_field($datos['user_agent'] ?? ''),
                    'revoked' => 0,
                    'revoked_at' => null,
                    'last_seen' => current_time('mysql'),
                ],
                [
                    'usuario_id' => $usuario_id,
                    'dispositivo_id' => $dispositivo_id,
                ],
                ['%s', '%s', '%s', '%d', null, '%s'],
                ['%d', '%s']
            );
        } else {
            $resultado = $this->wpdb->insert(
                $this->tabla_devices,
                [
                    'usuario_id' => $usuario_id,
                    'dispositivo_id' => $dispositivo_id,
                    'nombre' => sanitize_text_field($datos['nombre'] ?? $this->generar_nombre_automatico($datos)),
                    'tipo' => sanitize_text_field($datos['tipo'] ?? 'web'),
                    'user_agent' => sanitize_text_field($datos['user_agent'] ?? ''),
                    'is_primary' => $es_primario,
                    'last_seen' => current_time('mysql'),
                ],
                ['%d', '%s', '%s', '%s', '%s', '%d', '%s']
            );
        }

        return $resultado !== false;
    }

    /**
     * Obtiene información de un dispositivo
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @return array|null
     */
    public function obtener_dispositivo($usuario_id, $dispositivo_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tabla_devices}
                 WHERE usuario_id = %d AND dispositivo_id = %s",
                $usuario_id,
                $dispositivo_id
            ),
            ARRAY_A
        );
    }

    /**
     * Obtiene todos los dispositivos activos de un usuario
     *
     * @param int $usuario_id
     * @return array
     */
    public function obtener_dispositivos($usuario_id) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tabla_devices}
                 WHERE usuario_id = %d AND revoked = 0
                 ORDER BY is_primary DESC, last_seen DESC",
                $usuario_id
            ),
            ARRAY_A
        );
    }

    /**
     * Cuenta los dispositivos activos de un usuario
     *
     * @param int $usuario_id
     * @return int
     */
    public function contar_dispositivos_activos($usuario_id) {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_devices}
                 WHERE usuario_id = %d AND revoked = 0",
                $usuario_id
            )
        );
    }

    /**
     * Revoca un dispositivo
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @return bool|WP_Error
     */
    public function revocar_dispositivo($usuario_id, $dispositivo_id) {
        $dispositivo = $this->obtener_dispositivo($usuario_id, $dispositivo_id);

        if (!$dispositivo) {
            return new WP_Error(
                'dispositivo_no_encontrado',
                __('Dispositivo no encontrado.', 'flavor-platform')
            );
        }

        if ($dispositivo['revoked']) {
            return new WP_Error(
                'ya_revocado',
                __('Este dispositivo ya está revocado.', 'flavor-platform')
            );
        }

        // Si es el dispositivo primario, promover otro
        if ($dispositivo['is_primary']) {
            $this->promover_siguiente_dispositivo($usuario_id, $dispositivo_id);
        }

        $resultado = $this->wpdb->update(
            $this->tabla_devices,
            [
                'revoked' => 1,
                'revoked_at' => current_time('mysql'),
                'is_primary' => 0,
            ],
            [
                'usuario_id' => $usuario_id,
                'dispositivo_id' => $dispositivo_id,
            ],
            ['%d', '%s', '%d'],
            ['%d', '%s']
        );

        if ($resultado === false) {
            return new WP_Error(
                'error_revocacion',
                __('Error al revocar el dispositivo.', 'flavor-platform')
            );
        }

        // Notificar otros dispositivos sobre la revocación
        do_action('flavor_e2e_dispositivo_revocado', $usuario_id, $dispositivo_id);

        return true;
    }

    /**
     * Promueve el siguiente dispositivo más reciente a primario
     *
     * @param int $usuario_id
     * @param string $excluir_dispositivo_id
     */
    private function promover_siguiente_dispositivo($usuario_id, $excluir_dispositivo_id) {
        $siguiente = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tabla_devices}
                 WHERE usuario_id = %d
                 AND dispositivo_id != %s
                 AND revoked = 0
                 ORDER BY last_seen DESC
                 LIMIT 1",
                $usuario_id,
                $excluir_dispositivo_id
            ),
            ARRAY_A
        );

        if ($siguiente) {
            $this->wpdb->update(
                $this->tabla_devices,
                ['is_primary' => 1],
                ['id' => $siguiente['id']],
                ['%d'],
                ['%d']
            );
        }
    }

    /**
     * Establece un dispositivo como primario
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @return bool
     */
    public function establecer_primario($usuario_id, $dispositivo_id) {
        // Quitar primario de todos los dispositivos del usuario
        $this->wpdb->update(
            $this->tabla_devices,
            ['is_primary' => 0],
            ['usuario_id' => $usuario_id],
            ['%d'],
            ['%d']
        );

        // Establecer el nuevo primario
        $resultado = $this->wpdb->update(
            $this->tabla_devices,
            ['is_primary' => 1],
            [
                'usuario_id' => $usuario_id,
                'dispositivo_id' => $dispositivo_id,
                'revoked' => 0,
            ],
            ['%d'],
            ['%d', '%s', '%d']
        );

        return $resultado !== false;
    }

    /**
     * Actualiza la última actividad de un dispositivo
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     */
    public function actualizar_ultima_actividad($usuario_id, $dispositivo_id) {
        $this->wpdb->update(
            $this->tabla_devices,
            ['last_seen' => current_time('mysql')],
            [
                'usuario_id' => $usuario_id,
                'dispositivo_id' => $dispositivo_id,
            ],
            ['%s'],
            ['%d', '%s']
        );
    }

    /**
     * Renombra un dispositivo
     *
     * @param int $usuario_id
     * @param string $dispositivo_id
     * @param string $nuevo_nombre
     * @return bool
     */
    public function renombrar_dispositivo($usuario_id, $dispositivo_id, $nuevo_nombre) {
        $resultado = $this->wpdb->update(
            $this->tabla_devices,
            ['nombre' => sanitize_text_field($nuevo_nombre)],
            [
                'usuario_id' => $usuario_id,
                'dispositivo_id' => $dispositivo_id,
            ],
            ['%s'],
            ['%d', '%s']
        );

        return $resultado !== false;
    }

    /**
     * Genera un código de vinculación para añadir un nuevo dispositivo
     *
     * @param int $usuario_id
     * @return array ['codigo' => string, 'expira' => int]
     */
    public function generar_codigo_vinculacion($usuario_id) {
        // Generar código de 6 dígitos
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Almacenar temporalmente (expira en 5 minutos)
        $expiracion = time() + 300;

        set_transient(
            "flavor_e2e_vinculacion_{$usuario_id}",
            [
                'codigo' => $codigo,
                'expira' => $expiracion,
            ],
            300
        );

        return [
            'codigo' => $codigo,
            'expira' => $expiracion,
        ];
    }

    /**
     * Verifica un código de vinculación
     *
     * @param int $usuario_id
     * @param string $codigo
     * @return bool
     */
    public function verificar_codigo_vinculacion($usuario_id, $codigo) {
        $datos = get_transient("flavor_e2e_vinculacion_{$usuario_id}");

        if (!$datos) {
            return false;
        }

        if ($datos['expira'] < time()) {
            delete_transient("flavor_e2e_vinculacion_{$usuario_id}");
            return false;
        }

        if ($datos['codigo'] !== $codigo) {
            return false;
        }

        // Código válido, eliminar
        delete_transient("flavor_e2e_vinculacion_{$usuario_id}");

        return true;
    }

    /**
     * Genera un nombre automático para el dispositivo
     *
     * @param array $datos
     * @return string
     */
    private function generar_nombre_automatico($datos) {
        $tipo = $datos['tipo'] ?? 'web';
        $user_agent = $datos['user_agent'] ?? '';

        $nombre_base = __('Dispositivo', 'flavor-platform');

        // Detectar navegador/SO desde user agent
        if ($user_agent) {
            if (strpos($user_agent, 'Chrome') !== false) {
                $nombre_base = 'Chrome';
            } elseif (strpos($user_agent, 'Firefox') !== false) {
                $nombre_base = 'Firefox';
            } elseif (strpos($user_agent, 'Safari') !== false) {
                $nombre_base = 'Safari';
            } elseif (strpos($user_agent, 'Edge') !== false) {
                $nombre_base = 'Edge';
            }

            if (strpos($user_agent, 'Windows') !== false) {
                $nombre_base .= ' (Windows)';
            } elseif (strpos($user_agent, 'Mac') !== false) {
                $nombre_base .= ' (Mac)';
            } elseif (strpos($user_agent, 'Linux') !== false) {
                $nombre_base .= ' (Linux)';
            }
        }

        if ($tipo === 'android') {
            $nombre_base = __('Android', 'flavor-platform');
        } elseif ($tipo === 'ios') {
            $nombre_base = __('iPhone/iPad', 'flavor-platform');
        } elseif ($tipo === 'desktop') {
            $nombre_base = __('Aplicación de escritorio', 'flavor-platform');
        }

        return $nombre_base;
    }

    /**
     * Obtiene estadísticas de dispositivos para un usuario
     *
     * @param int $usuario_id
     * @return array
     */
    public function obtener_estadisticas($usuario_id) {
        $dispositivos = $this->obtener_dispositivos($usuario_id);

        $estadisticas = [
            'total_activos' => count($dispositivos),
            'limite' => self::MAX_DISPOSITIVOS,
            'disponibles' => self::MAX_DISPOSITIVOS - count($dispositivos),
            'por_tipo' => [],
            'ultimo_activo' => null,
        ];

        foreach ($dispositivos as $dispositivo) {
            $tipo = $dispositivo['tipo'] ?? 'web';
            $estadisticas['por_tipo'][$tipo] = ($estadisticas['por_tipo'][$tipo] ?? 0) + 1;

            if (!$estadisticas['ultimo_activo'] || $dispositivo['last_seen'] > $estadisticas['ultimo_activo']['last_seen']) {
                $estadisticas['ultimo_activo'] = $dispositivo;
            }
        }

        return $estadisticas;
    }

    /**
     * Limpia dispositivos inactivos (más de 90 días sin actividad)
     *
     * @return int Número de dispositivos eliminados
     */
    public function limpiar_dispositivos_inactivos() {
        // Marcar como revocados (no eliminar completamente para auditoría)
        $resultado = $this->wpdb->query(
            "UPDATE {$this->tabla_devices}
             SET revoked = 1, revoked_at = NOW()
             WHERE revoked = 0
             AND last_seen < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );

        return $resultado ?: 0;
    }

    /**
     * Verifica si un usuario tiene al menos un dispositivo activo
     *
     * @param int $usuario_id
     * @return bool
     */
    public function tiene_dispositivo_activo($usuario_id) {
        return $this->contar_dispositivos_activos($usuario_id) > 0;
    }

    /**
     * Obtiene el dispositivo primario de un usuario
     *
     * @param int $usuario_id
     * @return array|null
     */
    public function obtener_dispositivo_primario($usuario_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tabla_devices}
                 WHERE usuario_id = %d AND is_primary = 1 AND revoked = 0",
                $usuario_id
            ),
            ARRAY_A
        );
    }
}
