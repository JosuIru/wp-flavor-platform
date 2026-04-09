<?php
/**
 * Gestor principal de privacidad y RGPD
 *
 * @package Flavor_Chat_IA
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Privacy_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Prefijo de tablas
     */
    private $prefix;

    /**
     * Tipos de consentimiento disponibles
     */
    const CONSENT_TYPES = [
        'terminos_uso' => [
            'label' => 'Términos de uso',
            'descripcion' => 'Acepto los términos y condiciones de uso del servicio',
            'obligatorio' => true
        ],
        'politica_privacidad' => [
            'label' => 'Política de privacidad',
            'descripcion' => 'He leído y acepto la política de privacidad',
            'obligatorio' => true
        ],
        'comunicaciones_comerciales' => [
            'label' => 'Comunicaciones comerciales',
            'descripcion' => 'Acepto recibir comunicaciones comerciales y promociones',
            'obligatorio' => false
        ],
        'newsletter' => [
            'label' => 'Newsletter',
            'descripcion' => 'Deseo suscribirme al boletín informativo',
            'obligatorio' => false
        ],
        'compartir_datos_red' => [
            'label' => 'Compartir datos en red',
            'descripcion' => 'Acepto que mis datos públicos sean visibles en la red federada',
            'obligatorio' => false
        ],
        'analytics' => [
            'label' => 'Cookies de análisis',
            'descripcion' => 'Acepto el uso de cookies de análisis para mejorar el servicio',
            'obligatorio' => false
        ],
        'perfil_publico' => [
            'label' => 'Perfil público',
            'descripcion' => 'Acepto que mi perfil sea visible para otros usuarios',
            'obligatorio' => false
        ]
    ];

    /**
     * Estados de solicitud
     */
    const REQUEST_STATUS = [
        'pendiente' => 'Pendiente de procesamiento',
        'procesando' => 'En proceso',
        'completado' => 'Completado',
        'rechazado' => 'Rechazado'
    ];

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
        global $wpdb;
        $this->prefix = $wpdb->prefix . 'flavor_';

        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX handlers
        add_action('wp_ajax_flavor_privacy_get_data', [$this, 'ajax_get_user_data']);
        add_action('wp_ajax_flavor_privacy_export', [$this, 'ajax_request_export']);
        add_action('wp_ajax_flavor_privacy_delete', [$this, 'ajax_request_deletion']);
        add_action('wp_ajax_flavor_privacy_update_consent', [$this, 'ajax_update_consent']);
        add_action('wp_ajax_flavor_privacy_get_consents', [$this, 'ajax_get_consents']);

        // Shortcodes
        add_shortcode('flavor_privacidad', [$this, 'render_privacy_panel']);
        add_shortcode('flavor_consentimientos', [$this, 'render_consent_form']);

        // Cron para procesar solicitudes
        add_action('flavor_process_privacy_requests', [$this, 'process_pending_requests']);
        if (!wp_next_scheduled('flavor_process_privacy_requests')) {
            wp_schedule_event(time(), 'hourly', 'flavor_process_privacy_requests');
        }

        // Cron para limpiar exportaciones antiguas
        add_action('flavor_cleanup_exports', [$this, 'cleanup_old_exports']);
        if (!wp_next_scheduled('flavor_cleanup_exports')) {
            wp_schedule_event(time(), 'daily', 'flavor_cleanup_exports');
        }

        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        // Hook de registro de usuario para consentimientos iniciales
        add_action('user_register', [$this, 'handle_registration_consents']);

        // Integración con WordPress Privacy Tools
        add_filter('wp_privacy_personal_data_exporters', [$this, 'register_data_exporter']);
        add_filter('wp_privacy_personal_data_erasers', [$this, 'register_data_eraser']);
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        $namespace = 'flavor-app/v1';

        // Obtener datos del usuario
        register_rest_route($namespace, '/privacidad/mis-datos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_user_data'],
            'permission_callback' => [$this, 'check_user_permission']
        ]);

        // Solicitar exportación
        register_rest_route($namespace, '/privacidad/exportar', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_request_export'],
            'permission_callback' => [$this, 'check_user_permission']
        ]);

        // Solicitar eliminación
        register_rest_route($namespace, '/privacidad/eliminar-cuenta', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_request_deletion'],
            'permission_callback' => [$this, 'check_user_permission']
        ]);

        // Obtener consentimientos
        register_rest_route($namespace, '/privacidad/consentimientos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_consents'],
            'permission_callback' => [$this, 'check_user_permission']
        ]);

        // Actualizar consentimientos
        register_rest_route($namespace, '/privacidad/consentimientos', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_update_consents'],
            'permission_callback' => [$this, 'check_user_permission']
        ]);

        // Estado de solicitudes
        register_rest_route($namespace, '/privacidad/solicitudes', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_requests'],
            'permission_callback' => [$this, 'check_user_permission']
        ]);

        // Descargar exportación
        register_rest_route($namespace, '/privacidad/descargar/(?P<token>[a-zA-Z0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_download_export'],
            'permission_callback' => [$this, 'check_download_token_permission']
        ]);

        // Solicitar rectificación de datos
        register_rest_route($namespace, '/privacidad/rectificar', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_request_rectification'],
            'permission_callback' => [$this, 'check_user_permission']
        ]);

        // Obtener campos rectificables
        register_rest_route($namespace, '/privacidad/campos-rectificables', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_rectifiable_fields'],
            'permission_callback' => [$this, 'check_user_permission']
        ]);
    }

    /**
     * Verificar permisos de usuario
     */
    public function check_user_permission() {
        return is_user_logged_in();
    }

    /**
     * Verificar permisos de admin
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }

    /**
     * Verifica si el token de descarga corresponde a una exportación válida.
     *
     * @param WP_REST_Request $request Request actual.
     * @return bool
     */
    public function check_download_token_permission($request) {
        return (bool) $this->find_export_request_by_token($request->get_param('token'));
    }

    // =========================================================================
    // GESTIÓN DE CONSENTIMIENTOS
    // =========================================================================

    /**
     * Obtener consentimientos de un usuario
     */
    public function get_user_consents($usuario_id) {
        global $wpdb;
        $tabla_consentimientos = $this->prefix . 'privacy_consents';

        $consentimientos_guardados = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo_consentimiento, consentido, fecha
             FROM {$tabla_consentimientos}
             WHERE usuario_id = %d
             ORDER BY fecha DESC",
            $usuario_id
        ), ARRAY_A);

        // Crear array indexado por tipo
        $consentimientos_por_tipo = [];
        foreach ($consentimientos_guardados as $consentimiento) {
            $tipo = $consentimiento['tipo_consentimiento'];
            // Solo guardar el más reciente
            if (!isset($consentimientos_por_tipo[$tipo])) {
                $consentimientos_por_tipo[$tipo] = $consentimiento;
            }
        }

        // Combinar con tipos disponibles
        $resultado = [];
        foreach (self::CONSENT_TYPES as $tipo => $info) {
            $guardado = $consentimientos_por_tipo[$tipo] ?? null;
            $resultado[$tipo] = [
                'tipo' => $tipo,
                'label' => $info['label'],
                'descripcion' => $info['descripcion'],
                'obligatorio' => $info['obligatorio'],
                'consentido' => $guardado ? (bool)$guardado['consentido'] : false,
                'fecha' => $guardado ? $guardado['fecha'] : null
            ];
        }

        return $resultado;
    }

    /**
     * Guardar consentimiento
     */
    public function save_consent($usuario_id, $tipo_consentimiento, $consentido) {
        global $wpdb;
        $tabla_consentimientos = $this->prefix . 'privacy_consents';

        // Validar tipo
        if (!isset(self::CONSENT_TYPES[$tipo_consentimiento])) {
            return new WP_Error('tipo_invalido', 'Tipo de consentimiento no válido');
        }

        // No permitir rechazar consentimientos obligatorios si ya están aceptados
        if (self::CONSENT_TYPES[$tipo_consentimiento]['obligatorio'] && !$consentido) {
            // Verificar si hay consentimiento previo
            $consentimiento_previo = $wpdb->get_var($wpdb->prepare(
                "SELECT consentido FROM {$tabla_consentimientos}
                 WHERE usuario_id = %d AND tipo_consentimiento = %s
                 ORDER BY fecha DESC LIMIT 1",
                $usuario_id,
                $tipo_consentimiento
            ));

            if ($consentimiento_previo) {
                return new WP_Error(
                    'consentimiento_obligatorio',
                    'No se puede retirar el consentimiento de un campo obligatorio. Para ello debe solicitar la eliminación de su cuenta.'
                );
            }
        }

        $resultado = $wpdb->insert(
            $tabla_consentimientos,
            [
                'usuario_id' => $usuario_id,
                'tipo_consentimiento' => $tipo_consentimiento,
                'consentido' => $consentido ? 1 : 0,
                'ip_address' => $this->get_user_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
                'fecha' => current_time('mysql')
            ],
            ['%d', '%s', '%d', '%s', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('db_error', 'Error al guardar el consentimiento');
        }

        // Disparar acción
        do_action('flavor_consent_updated', $usuario_id, $tipo_consentimiento, $consentido);

        return true;
    }

    /**
     * Guardar múltiples consentimientos
     */
    public function save_multiple_consents($usuario_id, $consentimientos) {
        $resultados = [];
        foreach ($consentimientos as $tipo => $consentido) {
            $resultado = $this->save_consent($usuario_id, $tipo, $consentido);
            $resultados[$tipo] = is_wp_error($resultado) ? $resultado->get_error_message() : true;
        }
        return $resultados;
    }

    /**
     * Verificar si usuario tiene consentimiento específico
     */
    public function has_consent($usuario_id, $tipo_consentimiento) {
        global $wpdb;
        $tabla_consentimientos = $this->prefix . 'privacy_consents';

        $consentido = $wpdb->get_var($wpdb->prepare(
            "SELECT consentido FROM {$tabla_consentimientos}
             WHERE usuario_id = %d AND tipo_consentimiento = %s
             ORDER BY fecha DESC LIMIT 1",
            $usuario_id,
            $tipo_consentimiento
        ));

        return (bool)$consentido;
    }

    /**
     * Verificar si usuario tiene todos los consentimientos obligatorios
     */
    public function has_required_consents($usuario_id) {
        foreach (self::CONSENT_TYPES as $tipo => $info) {
            if ($info['obligatorio'] && !$this->has_consent($usuario_id, $tipo)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Manejar consentimientos en registro
     */
    public function handle_registration_consents($usuario_id) {
        // Buscar campos de consentimiento en POST
        foreach (self::CONSENT_TYPES as $tipo => $info) {
            $campo = 'consent_' . $tipo;
            if (isset($_POST[$campo])) {
                $this->save_consent($usuario_id, $tipo, !empty($_POST[$campo]));
            }
        }
    }

    // =========================================================================
    // GESTIÓN DE SOLICITUDES DE PRIVACIDAD
    // =========================================================================

    /**
     * Crear solicitud de exportación
     */
    public function create_export_request($usuario_id) {
        global $wpdb;
        $tabla_solicitudes = $this->prefix . 'privacy_requests';

        // Verificar si hay solicitud pendiente reciente (últimas 24h)
        $solicitud_reciente = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_solicitudes}
             WHERE usuario_id = %d
             AND tipo = 'exportar'
             AND estado IN ('pendiente', 'procesando')
             AND fecha_solicitud > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            $usuario_id
        ));

        if ($solicitud_reciente > 0) {
            return new WP_Error(
                'solicitud_existente',
                'Ya tienes una solicitud de exportación en proceso. Por favor, espera a que se complete.'
            );
        }

        // Verificar límite semanal
        $solicitudes_semana = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_solicitudes}
             WHERE usuario_id = %d
             AND tipo = 'exportar'
             AND fecha_solicitud > DATE_SUB(NOW(), INTERVAL 7 DAY)",
            $usuario_id
        ));

        if ($solicitudes_semana >= 2) {
            return new WP_Error(
                'limite_semanal',
                'Has alcanzado el límite de solicitudes de exportación por semana (2). Por favor, intenta de nuevo más tarde.'
            );
        }

        $resultado = $wpdb->insert(
            $tabla_solicitudes,
            [
                'usuario_id' => $usuario_id,
                'tipo' => 'exportar',
                'estado' => 'pendiente',
                'fecha_solicitud' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('db_error', 'Error al crear la solicitud');
        }

        $solicitud_id = $wpdb->insert_id;

        // Notificar al usuario
        $usuario = get_userdata($usuario_id);
        if ($usuario) {
            $this->send_notification_email(
                $usuario->user_email,
                'export_requested',
                ['nombre' => $usuario->display_name]
            );
        }

        // Disparar acción
        do_action('flavor_privacy_export_requested', $usuario_id, $solicitud_id);

        return $solicitud_id;
    }

    /**
     * Crear solicitud de eliminación
     */
    public function create_deletion_request($usuario_id, $motivo = '') {
        global $wpdb;
        $tabla_solicitudes = $this->prefix . 'privacy_requests';

        // Verificar si hay solicitud pendiente
        $solicitud_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_solicitudes}
             WHERE usuario_id = %d
             AND tipo = 'eliminar'
             AND estado IN ('pendiente', 'procesando')",
            $usuario_id
        ));

        if ($solicitud_existente) {
            return new WP_Error(
                'solicitud_existente',
                'Ya tienes una solicitud de eliminación pendiente.'
            );
        }

        $datos_solicitud = [
            'motivo' => sanitize_textarea_field($motivo),
            'ip' => $this->get_user_ip(),
            'fecha_solicitud_iso' => current_time('c')
        ];

        $resultado = $wpdb->insert(
            $tabla_solicitudes,
            [
                'usuario_id' => $usuario_id,
                'tipo' => 'eliminar',
                'estado' => 'pendiente',
                'datos' => wp_json_encode($datos_solicitud),
                'fecha_solicitud' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('db_error', 'Error al crear la solicitud');
        }

        $solicitud_id = $wpdb->insert_id;

        // Notificar al usuario
        $usuario = get_userdata($usuario_id);
        if ($usuario) {
            $this->send_notification_email(
                $usuario->user_email,
                'deletion_requested',
                ['nombre' => $usuario->display_name]
            );
        }

        // Notificar a administradores
        $this->notify_admins_deletion_request($usuario_id, $solicitud_id);

        // Disparar acción
        do_action('flavor_privacy_deletion_requested', $usuario_id, $solicitud_id);

        return $solicitud_id;
    }

    /**
     * Obtener solicitudes de un usuario
     */
    public function get_user_requests($usuario_id) {
        global $wpdb;
        $tabla_solicitudes = $this->prefix . 'privacy_requests';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, tipo, estado, fecha_solicitud, fecha_procesado, datos
             FROM {$tabla_solicitudes}
             WHERE usuario_id = %d
             ORDER BY fecha_solicitud DESC
             LIMIT 20",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Procesar solicitudes pendientes (cron)
     */
    public function process_pending_requests() {
        global $wpdb;
        $tabla_solicitudes = $this->prefix . 'privacy_requests';

        // Obtener solicitudes de exportación pendientes
        $solicitudes_exportacion = $wpdb->get_results(
            "SELECT * FROM {$tabla_solicitudes}
             WHERE tipo = 'exportar'
             AND estado = 'pendiente'
             ORDER BY fecha_solicitud ASC
             LIMIT 5"
        );

        foreach ($solicitudes_exportacion as $solicitud) {
            $this->process_export_request($solicitud);
        }

        // Las solicitudes de eliminación requieren revisión manual
        // pero notificamos a admins si hay pendientes
        $eliminaciones_pendientes = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_solicitudes}
             WHERE tipo = 'eliminar' AND estado = 'pendiente'"
        );

        if ($eliminaciones_pendientes > 0) {
            // Notificar a admins una vez al día
            $ultima_notificacion = get_transient('flavor_deletion_admin_notification');
            if (!$ultima_notificacion) {
                $this->notify_admins_pending_deletions($eliminaciones_pendientes);
                set_transient('flavor_deletion_admin_notification', time(), DAY_IN_SECONDS);
            }
        }
    }

    /**
     * Procesar solicitud de exportación
     */
    private function process_export_request($solicitud) {
        global $wpdb;
        $tabla_solicitudes = $this->prefix . 'privacy_requests';

        // Marcar como procesando
        $wpdb->update(
            $tabla_solicitudes,
            ['estado' => 'procesando'],
            ['id' => $solicitud->id],
            ['%s'],
            ['%d']
        );

        try {
            // Obtener exportador
            $exporter = Flavor_Data_Exporter::get_instance();
            $resultado = $exporter->export_user_data($solicitud->usuario_id);

            if (is_wp_error($resultado)) {
                throw new Exception($resultado->get_error_message());
            }

            // Generar token de descarga
            $token = wp_generate_password(32, false);
            $token_hash = wp_hash($token);

            // Actualizar solicitud
            $datos = [
                'archivo' => $resultado['archivo'],
                'token_hash' => $token_hash,
                'expira' => date('Y-m-d H:i:s', strtotime('+48 hours'))
            ];

            $wpdb->update(
                $tabla_solicitudes,
                [
                    'estado' => 'completado',
                    'fecha_procesado' => current_time('mysql'),
                    'datos' => wp_json_encode($datos)
                ],
                ['id' => $solicitud->id],
                ['%s', '%s', '%s'],
                ['%d']
            );

            // Notificar al usuario
            $usuario = get_userdata($solicitud->usuario_id);
            if ($usuario) {
                $url_descarga = add_query_arg([
                    'action' => 'flavor_download_export',
                    'token' => $token,
                    'request_id' => $solicitud->id
                ], home_url());

                $this->send_notification_email(
                    $usuario->user_email,
                    'export_ready',
                    [
                        'nombre' => $usuario->display_name,
                        'url_descarga' => $url_descarga,
                        'expira_en' => '48 horas'
                    ]
                );
            }

        } catch (Exception $e) {
            // Marcar como error
            $wpdb->update(
                $tabla_solicitudes,
                [
                    'estado' => 'rechazado',
                    'motivo_rechazo' => $e->getMessage(),
                    'fecha_procesado' => current_time('mysql')
                ],
                ['id' => $solicitud->id],
                ['%s', '%s', '%s'],
                ['%d']
            );

            error_log('Flavor Privacy: Error procesando exportación #' . $solicitud->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Procesar solicitud de eliminación (admin)
     */
    public function process_deletion_request($solicitud_id, $aprobar = true, $motivo_rechazo = '') {
        global $wpdb;
        $tabla_solicitudes = $this->prefix . 'privacy_requests';

        if (!current_user_can('manage_options')) {
            return new WP_Error('sin_permisos', 'No tienes permisos para procesar esta solicitud');
        }

        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_solicitudes} WHERE id = %d",
            $solicitud_id
        ));

        if (!$solicitud) {
            return new WP_Error('no_encontrada', 'Solicitud no encontrada');
        }

        if ($solicitud->estado !== 'pendiente') {
            return new WP_Error('estado_invalido', 'Esta solicitud ya fue procesada');
        }

        if (!$aprobar) {
            // Rechazar solicitud
            $wpdb->update(
                $tabla_solicitudes,
                [
                    'estado' => 'rechazado',
                    'motivo_rechazo' => sanitize_textarea_field($motivo_rechazo),
                    'fecha_procesado' => current_time('mysql')
                ],
                ['id' => $solicitud_id],
                ['%s', '%s', '%s'],
                ['%d']
            );

            // Notificar al usuario
            $usuario = get_userdata($solicitud->usuario_id);
            if ($usuario) {
                $this->send_notification_email(
                    $usuario->user_email,
                    'deletion_rejected',
                    [
                        'nombre' => $usuario->display_name,
                        'motivo' => $motivo_rechazo
                    ]
                );
            }

            return true;
        }

        // Aprobar y ejecutar eliminación
        $wpdb->update(
            $tabla_solicitudes,
            ['estado' => 'procesando'],
            ['id' => $solicitud_id],
            ['%s'],
            ['%d']
        );

        try {
            $this->delete_user_data($solicitud->usuario_id);

            $wpdb->update(
                $tabla_solicitudes,
                [
                    'estado' => 'completado',
                    'fecha_procesado' => current_time('mysql')
                ],
                ['id' => $solicitud_id],
                ['%s', '%s'],
                ['%d']
            );

            // Notificar al usuario (si el email aún existe)
            $usuario = get_userdata($solicitud->usuario_id);
            if ($usuario) {
                $this->send_notification_email(
                    $usuario->user_email,
                    'deletion_completed',
                    ['nombre' => $usuario->display_name]
                );

                // Eliminar cuenta de WordPress (opcional, requiere confirmación adicional)
                // require_once(ABSPATH . 'wp-admin/includes/user.php');
                // wp_delete_user($solicitud->usuario_id);
            }

            return true;

        } catch (Exception $e) {
            $wpdb->update(
                $tabla_solicitudes,
                [
                    'estado' => 'rechazado',
                    'motivo_rechazo' => 'Error técnico: ' . $e->getMessage(),
                    'fecha_procesado' => current_time('mysql')
                ],
                ['id' => $solicitud_id],
                ['%s', '%s', '%s'],
                ['%d']
            );

            return new WP_Error('error_eliminacion', $e->getMessage());
        }
    }

    // =========================================================================
    // ELIMINACIÓN DE DATOS
    // =========================================================================

    /**
     * Eliminar todos los datos de un usuario
     */
    public function delete_user_data($usuario_id, $anonimizar = true) {
        global $wpdb;

        // Lista de tablas y campos de usuario
        $tablas_usuario = $this->get_user_data_tables();

        foreach ($tablas_usuario as $tabla_info) {
            $tabla = $this->prefix . $tabla_info['tabla'];
            $campo_usuario = $tabla_info['campo'];

            // Verificar si la tabla existe
            $tabla_existe = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $tabla
            ));

            if (!$tabla_existe) {
                continue;
            }

            if ($anonimizar && isset($tabla_info['anonimizar'])) {
                // Anonimizar en lugar de eliminar (para estadísticas)
                $campos_anonimizar = $tabla_info['anonimizar'];
                $set_clauses = [];
                $valores = [];

                foreach ($campos_anonimizar as $campo => $valor) {
                    $set_clauses[] = "`{$campo}` = %s";
                    $valores[] = $valor;
                }

                $valores[] = $usuario_id;
                $sql = "UPDATE `{$tabla}` SET " . implode(', ', $set_clauses) . " WHERE `{$campo_usuario}` = %d";
                $wpdb->query($wpdb->prepare($sql, $valores));
            } else {
                // Eliminar completamente
                $wpdb->delete($tabla, [$campo_usuario => $usuario_id], ['%d']);
            }
        }

        // Eliminar user meta de Flavor
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
            $usuario_id,
            'flavor_%'
        ));

        // Disparar acción para que otros módulos limpien sus datos
        do_action('flavor_user_data_deleted', $usuario_id, $anonimizar);

        return true;
    }

    /**
     * Obtener lista de tablas con datos de usuario
     */
    private function get_user_data_tables() {
        return [
            // Red Social
            ['tabla' => 'social_publicaciones', 'campo' => 'autor_id', 'anonimizar' => ['contenido' => '[Contenido eliminado]']],
            ['tabla' => 'social_comentarios', 'campo' => 'autor_id', 'anonimizar' => ['comentario' => '[Comentario eliminado]']],
            ['tabla' => 'social_reacciones', 'campo' => 'usuario_id'],
            ['tabla' => 'social_seguimientos', 'campo' => 'seguidor_id'],
            ['tabla' => 'social_seguimientos', 'campo' => 'seguido_id'],
            ['tabla' => 'social_historias', 'campo' => 'autor_id'],
            ['tabla' => 'social_notificaciones', 'campo' => 'usuario_id'],
            ['tabla' => 'social_guardados', 'campo' => 'usuario_id'],
            ['tabla' => 'social_perfiles', 'campo' => 'usuario_id'],

            // Comunidades
            ['tabla' => 'comunidades_miembros', 'campo' => 'usuario_id'],

            // Eventos
            ['tabla' => 'eventos_inscripciones', 'campo' => 'usuario_id'],

            // Cursos
            ['tabla' => 'cursos_inscripciones', 'campo' => 'usuario_id'],

            // Reservas
            ['tabla' => 'reservas', 'campo' => 'usuario_id'],

            // Mensajes
            ['tabla' => 'mensajes', 'campo' => 'remitente_id', 'anonimizar' => ['contenido' => '[Mensaje eliminado]']],
            ['tabla' => 'mensajes', 'campo' => 'destinatario_id'],

            // Notificaciones generales
            ['tabla' => 'notificaciones', 'campo' => 'usuario_id'],

            // Foros
            ['tabla' => 'foros_temas', 'campo' => 'autor_id', 'anonimizar' => ['contenido' => '[Contenido eliminado]']],

            // Chat
            ['tabla' => 'chat_grupos_miembros', 'campo' => 'usuario_id'],

            // Marketplace
            ['tabla' => 'marketplace', 'campo' => 'usuario_id'],

            // Banco del tiempo
            ['tabla' => 'banco_tiempo_saldo', 'campo' => 'usuario_id'],

            // Reciclaje
            ['tabla' => 'reciclaje_puntos', 'campo' => 'usuario_id'],

            // Huertos
            ['tabla' => 'huertos_asignaciones', 'campo' => 'usuario_id'],

            // Colectivos
            ['tabla' => 'colectivos_miembros', 'campo' => 'usuario_id'],

            // Socios
            ['tabla' => 'socios', 'campo' => 'usuario_id'],

            // Incidencias
            ['tabla' => 'incidencias', 'campo' => 'usuario_id', 'anonimizar' => ['descripcion' => '[Descripción eliminada]']],

            // Trámites
            ['tabla' => 'tramites', 'campo' => 'usuario_id'],

            // Presupuestos participativos
            ['tabla' => 'presupuestos_propuestas', 'campo' => 'usuario_id', 'anonimizar' => ['descripcion' => '[Descripción eliminada]']],

            // Fichajes
            ['tabla' => 'fichajes', 'campo' => 'usuario_id'],

            // Biblioteca
            ['tabla' => 'biblioteca_prestamos', 'campo' => 'usuario_id'],

            // Carpooling
            ['tabla' => 'carpooling_viajes', 'campo' => 'conductor_id'],

            // Grupos de consumo
            ['tabla' => 'grupos_consumo_miembros', 'campo' => 'usuario_id'],

            // Compostaje
            ['tabla' => 'compostaje_aportes', 'campo' => 'usuario_id'],

            // Ayuda vecinal
            ['tabla' => 'ayuda_vecinal', 'campo' => 'usuario_id', 'anonimizar' => ['descripcion' => '[Descripción eliminada]']],

            // Recursos compartidos
            ['tabla' => 'recursos_compartidos', 'campo' => 'usuario_id'],

            // Facturas
            ['tabla' => 'facturas', 'campo' => 'usuario_id'],

            // Bicicletas
            ['tabla' => 'bicicletas_alquileres', 'campo' => 'usuario_id'],

            // Radio
            ['tabla' => 'radio_dedicatorias', 'campo' => 'usuario_id'],
            ['tabla' => 'radio_chat', 'campo' => 'usuario_id'],
            ['tabla' => 'radio_oyentes', 'campo' => 'usuario_id'],
            ['tabla' => 'radio_propuestas', 'campo' => 'usuario_id'],

            // Privacidad
            ['tabla' => 'privacy_consents', 'campo' => 'usuario_id'],

            // Estados de chat (tipo WhatsApp Status)
            ['tabla' => 'chat_estados', 'campo' => 'usuario_id'],
            ['tabla' => 'chat_estados_vistas', 'campo' => 'usuario_id'],

            // Encuestas
            ['tabla' => 'encuestas_respuestas', 'campo' => 'usuario_id'],

            // Moderación (reportes realizados por el usuario)
            ['tabla' => 'moderation_reports', 'campo' => 'reportador_id'],

            // Reputación y gamificación
            ['tabla' => 'social_reputacion', 'campo' => 'usuario_id'],
            ['tabla' => 'social_usuario_badges', 'campo' => 'usuario_id'],
            ['tabla' => 'social_historial_puntos', 'campo' => 'usuario_id'],
            ['tabla' => 'social_engagement', 'campo' => 'usuario_id'],
        ];
    }

    // =========================================================================
    // REST HANDLERS
    // =========================================================================

    /**
     * REST: Obtener datos del usuario
     */
    public function rest_get_user_data(WP_REST_Request $request) {
        $usuario_id = get_current_user_id();

        try {
            $exporter = Flavor_Data_Exporter::get_instance();
            $datos = $exporter->get_user_data_summary($usuario_id);

            return new WP_REST_Response([
                'success' => true,
                'data' => $datos
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * REST: Solicitar exportación
     */
    public function rest_request_export(WP_REST_Request $request) {
        $usuario_id = get_current_user_id();
        $resultado = $this->create_export_request($usuario_id);

        if (is_wp_error($resultado)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $resultado->get_error_message()
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Solicitud de exportación creada. Recibirás un email cuando esté lista.',
            'solicitud_id' => $resultado
        ], 200);
    }

    /**
     * REST: Solicitar eliminación
     */
    public function rest_request_deletion(WP_REST_Request $request) {
        $usuario_id = get_current_user_id();
        $motivo = $request->get_param('motivo') ?? '';

        $resultado = $this->create_deletion_request($usuario_id, $motivo);

        if (is_wp_error($resultado)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $resultado->get_error_message()
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Solicitud de eliminación creada. Un administrador la revisará pronto.',
            'solicitud_id' => $resultado
        ], 200);
    }

    /**
     * REST: Obtener consentimientos
     */
    public function rest_get_consents(WP_REST_Request $request) {
        $usuario_id = get_current_user_id();
        $consentimientos = $this->get_user_consents($usuario_id);

        return new WP_REST_Response([
            'success' => true,
            'data' => $consentimientos
        ], 200);
    }

    /**
     * REST: Actualizar consentimientos
     */
    public function rest_update_consents(WP_REST_Request $request) {
        $usuario_id = get_current_user_id();
        $consentimientos = $request->get_param('consentimientos');

        if (!is_array($consentimientos)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Formato de datos inválido'
            ], 400);
        }

        $resultados = $this->save_multiple_consents($usuario_id, $consentimientos);

        // Verificar si hubo errores
        $errores = array_filter($resultados, function($r) {
            return $r !== true;
        });

        if (!empty($errores)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Algunos consentimientos no pudieron actualizarse',
                'errores' => $errores
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Consentimientos actualizados correctamente'
        ], 200);
    }

    /**
     * REST: Obtener solicitudes
     */
    public function rest_get_requests(WP_REST_Request $request) {
        $usuario_id = get_current_user_id();
        $solicitudes = $this->get_user_requests($usuario_id);

        return new WP_REST_Response([
            'success' => true,
            'data' => $solicitudes
        ], 200);
    }

    /**
     * REST: Descargar exportación
     */
    public function rest_download_export(WP_REST_Request $request) {
        $token = $request->get_param('token');
        $match = $this->find_export_request_by_token($token);

        if ($match) {
            $datos = $match['datos'];
            $archivo = $datos['archivo'] ?? '';

            if (!file_exists($archivo)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'El archivo de exportación ya no está disponible'
                ], 404);
            }

            // SEGURIDAD: Validar que el archivo está en directorio permitido (prevenir Path Traversal)
            $upload_dir = wp_upload_dir();
            $directorio_permitido = realpath($upload_dir['basedir'] . '/flavor-exports');
            $ruta_real_archivo = realpath($archivo);

            if (!$directorio_permitido || !$ruta_real_archivo || strpos($ruta_real_archivo, $directorio_permitido) !== 0) {
                error_log('[Flavor Privacy] Intento de Path Traversal bloqueado: ' . $archivo);
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Archivo no permitido'
                ], 403);
            }

            if (isset($datos['expira']) && strtotime($datos['expira']) < time()) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'El enlace de descarga ha expirado'
                ], 410);
            }

            // Sanitizar nombre de archivo para header
            $nombre_archivo_seguro = 'mis-datos-' . gmdate('Y-m-d') . '.zip';

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $nombre_archivo_seguro . '"');
            header('Content-Length: ' . filesize($ruta_real_archivo));
            readfile($ruta_real_archivo);
            exit;
        }

        return new WP_REST_Response([
            'success' => false,
            'message' => 'Enlace de descarga inválido o expirado'
        ], 404);
    }

    /**
     * Busca una solicitud de exportación válida a partir de un token.
     *
     * @param string $token Token en claro recibido por request.
     * @return array|null
     */
    private function find_export_request_by_token($token) {
        if (!is_string($token) || strlen($token) < 24) {
            return null;
        }

        global $wpdb;
        $tabla_solicitudes = $this->prefix . 'privacy_requests';

        $solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla_solicitudes}
             WHERE tipo = 'exportar'
             AND estado = 'completado'
             AND fecha_procesado > DATE_SUB(NOW(), INTERVAL 48 HOUR)"
        ));

        foreach ($solicitudes as $solicitud) {
            $datos = json_decode($solicitud->datos, true);
            if (!$datos || !isset($datos['token_hash'])) {
                continue;
            }

            if (wp_check_password($token, $datos['token_hash'])) {
                return [
                    'solicitud' => $solicitud,
                    'datos' => $datos,
                ];
            }
        }

        return null;
    }

    /**
     * REST: Obtener campos rectificables
     */
    public function rest_get_rectifiable_fields(WP_REST_Request $request) {
        $usuario_id = get_current_user_id();
        $user = get_userdata($usuario_id);

        if (!$user) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // Campos básicos del perfil de WordPress
        $campos_basicos = [
            'display_name' => [
                'label' => 'Nombre visible',
                'valor' => $user->display_name,
                'editable' => true,
                'tipo' => 'text'
            ],
            'first_name' => [
                'label' => 'Nombre',
                'valor' => $user->first_name,
                'editable' => true,
                'tipo' => 'text'
            ],
            'last_name' => [
                'label' => 'Apellidos',
                'valor' => $user->last_name,
                'editable' => true,
                'tipo' => 'text'
            ],
            'description' => [
                'label' => 'Biografía',
                'valor' => $user->description,
                'editable' => true,
                'tipo' => 'textarea'
            ],
            'user_url' => [
                'label' => 'Sitio web',
                'valor' => $user->user_url,
                'editable' => true,
                'tipo' => 'url'
            ],
            'user_email' => [
                'label' => 'Email',
                'valor' => $user->user_email,
                'editable' => true,
                'requiere_verificacion' => true,
                'tipo' => 'email'
            ]
        ];

        // Campos adicionales del perfil social si existe
        global $wpdb;
        $tabla_perfil = $this->prefix . 'social_perfiles';
        $perfil_social = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_perfil} WHERE usuario_id = %d",
            $usuario_id
        ), ARRAY_A);

        $campos_sociales = [];
        if ($perfil_social) {
            $campos_sociales = [
                'telefono' => [
                    'label' => 'Teléfono',
                    'valor' => $perfil_social['telefono'] ?? '',
                    'editable' => true,
                    'tipo' => 'tel'
                ],
                'direccion' => [
                    'label' => 'Dirección',
                    'valor' => $perfil_social['direccion'] ?? '',
                    'editable' => true,
                    'tipo' => 'text'
                ],
                'fecha_nacimiento' => [
                    'label' => 'Fecha de nacimiento',
                    'valor' => $perfil_social['fecha_nacimiento'] ?? '',
                    'editable' => true,
                    'tipo' => 'date'
                ]
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'basicos' => $campos_basicos,
                'sociales' => $campos_sociales
            ]
        ], 200);
    }

    /**
     * REST: Solicitar rectificación de datos
     */
    public function rest_request_rectification(WP_REST_Request $request) {
        $usuario_id = get_current_user_id();
        $campo = sanitize_key($request->get_param('campo'));
        $valor_nuevo = $request->get_param('valor_nuevo');
        $motivo = sanitize_textarea_field($request->get_param('motivo') ?? '');

        if (empty($campo) || $valor_nuevo === null) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Faltan datos requeridos'
            ], 400);
        }

        $resultado = $this->create_rectification_request($usuario_id, $campo, $valor_nuevo, $motivo);

        if (is_wp_error($resultado)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $resultado->get_error_message()
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Solicitud de rectificación creada',
            'solicitud_id' => $resultado
        ], 200);
    }

    /**
     * Crear solicitud de rectificación
     *
     * @param int    $usuario_id  ID del usuario
     * @param string $campo       Campo a rectificar
     * @param mixed  $valor_nuevo Nuevo valor
     * @param string $motivo      Motivo de la rectificación
     * @return int|WP_Error ID de solicitud o error
     */
    public function create_rectification_request($usuario_id, $campo, $valor_nuevo, $motivo = '') {
        global $wpdb;
        $tabla_solicitudes = $this->prefix . 'privacy_requests';

        // Campos que pueden editarse directamente sin aprobación
        $campos_autoeditable = ['display_name', 'first_name', 'last_name', 'description', 'user_url', 'telefono', 'direccion', 'fecha_nacimiento'];

        // Campos que requieren aprobación
        $campos_requieren_aprobacion = ['user_email'];

        if (!in_array($campo, array_merge($campos_autoeditable, $campos_requieren_aprobacion))) {
            return new WP_Error('campo_no_rectificable', 'Este campo no puede ser rectificado');
        }

        // Si es un campo autoeditable, aplicar directamente
        if (in_array($campo, $campos_autoeditable)) {
            $resultado = $this->apply_rectification($usuario_id, $campo, $valor_nuevo);
            if (is_wp_error($resultado)) {
                return $resultado;
            }

            // Registrar en historial de solicitudes como completado
            $wpdb->insert(
                $tabla_solicitudes,
                [
                    'usuario_id' => $usuario_id,
                    'tipo' => 'rectificar',
                    'estado' => 'completado',
                    'datos' => wp_json_encode([
                        'campo' => $campo,
                        'valor_nuevo' => $valor_nuevo,
                        'motivo' => $motivo,
                        'aplicado_automaticamente' => true
                    ]),
                    'fecha_solicitud' => current_time('mysql'),
                    'fecha_procesado' => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s']
            );

            return $wpdb->insert_id;
        }

        // Si requiere aprobación (ej: email), crear solicitud pendiente
        $wpdb->insert(
            $tabla_solicitudes,
            [
                'usuario_id' => $usuario_id,
                'tipo' => 'rectificar',
                'estado' => 'pendiente',
                'datos' => wp_json_encode([
                    'campo' => $campo,
                    'valor_nuevo' => sanitize_text_field($valor_nuevo),
                    'motivo' => $motivo,
                    'requiere_verificacion' => true
                ]),
                'fecha_solicitud' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );

        $solicitud_id = $wpdb->insert_id;

        // Si es cambio de email, enviar verificación
        if ($campo === 'user_email') {
            $this->send_email_verification($usuario_id, $valor_nuevo, $solicitud_id);
        }

        return $solicitud_id;
    }

    /**
     * Aplicar rectificación de un campo
     *
     * @param int    $usuario_id ID del usuario
     * @param string $campo      Campo a rectificar
     * @param mixed  $valor      Nuevo valor
     * @return bool|WP_Error
     */
    private function apply_rectification($usuario_id, $campo, $valor) {
        global $wpdb;

        // Campos de WordPress user
        $campos_wp_user = ['display_name', 'first_name', 'last_name', 'description', 'user_url', 'user_email'];

        if (in_array($campo, $campos_wp_user)) {
            if ($campo === 'user_email') {
                // Verificar que el email no esté en uso
                $email_existe = email_exists($valor);
                if ($email_existe && $email_existe !== $usuario_id) {
                    return new WP_Error('email_en_uso', 'Este email ya está en uso por otro usuario');
                }
            }

            $resultado = wp_update_user([
                'ID' => $usuario_id,
                $campo => sanitize_text_field($valor)
            ]);

            if (is_wp_error($resultado)) {
                return $resultado;
            }

            return true;
        }

        // Campos del perfil social
        $campos_perfil = ['telefono', 'direccion', 'fecha_nacimiento'];
        if (in_array($campo, $campos_perfil)) {
            $tabla_perfil = $this->prefix . 'social_perfiles';

            // Verificar si existe el perfil
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tabla_perfil} WHERE usuario_id = %d",
                $usuario_id
            ));

            if ($existe) {
                $wpdb->update(
                    $tabla_perfil,
                    [$campo => sanitize_text_field($valor)],
                    ['usuario_id' => $usuario_id],
                    ['%s'],
                    ['%d']
                );
            } else {
                $wpdb->insert(
                    $tabla_perfil,
                    [
                        'usuario_id' => $usuario_id,
                        $campo => sanitize_text_field($valor)
                    ],
                    ['%d', '%s']
                );
            }

            return true;
        }

        return new WP_Error('campo_desconocido', 'Campo no reconocido');
    }

    /**
     * Enviar verificación de cambio de email
     *
     * @param int    $usuario_id   ID del usuario
     * @param string $nuevo_email  Nuevo email
     * @param int    $solicitud_id ID de la solicitud
     */
    private function send_email_verification($usuario_id, $nuevo_email, $solicitud_id) {
        $token = wp_generate_password(32, false);
        $token_hash = wp_hash($token);

        // Guardar token en la solicitud
        global $wpdb;
        $tabla_solicitudes = $this->prefix . 'privacy_requests';

        $datos_existentes = $wpdb->get_var($wpdb->prepare(
            "SELECT datos FROM {$tabla_solicitudes} WHERE id = %d",
            $solicitud_id
        ));

        $datos = json_decode($datos_existentes, true) ?: [];
        $datos['verification_token_hash'] = $token_hash;
        $datos['verification_expires'] = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $wpdb->update(
            $tabla_solicitudes,
            ['datos' => wp_json_encode($datos)],
            ['id' => $solicitud_id],
            ['%s'],
            ['%d']
        );

        // Enviar email
        $url_verificacion = add_query_arg([
            'action' => 'flavor_verify_email',
            'token' => $token,
            'request_id' => $solicitud_id
        ], home_url());

        $usuario = get_userdata($usuario_id);
        $subject = '[' . get_bloginfo('name') . '] Verifica tu nuevo email';
        $body = sprintf(
            "Hola %s,\n\n" .
            "Has solicitado cambiar tu dirección de email a: %s\n\n" .
            "Para confirmar este cambio, haz clic en el siguiente enlace:\n%s\n\n" .
            "Este enlace expirará en 24 horas.\n\n" .
            "Si no solicitaste este cambio, puedes ignorar este mensaje.",
            $usuario->display_name,
            $nuevo_email,
            $url_verificacion
        );

        wp_mail($nuevo_email, $subject, $body);
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Obtener datos del usuario
     */
    public function ajax_get_user_data() {
        check_ajax_referer('flavor_privacy_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'No autorizado']);
        }

        $usuario_id = get_current_user_id();
        $exporter = Flavor_Data_Exporter::get_instance();
        $datos = $exporter->get_user_data_summary($usuario_id);

        wp_send_json_success($datos);
    }

    /**
     * AJAX: Solicitar exportación
     */
    public function ajax_request_export() {
        check_ajax_referer('flavor_privacy_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'No autorizado']);
        }

        $resultado = $this->create_export_request(get_current_user_id());

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'Solicitud de exportación creada. Recibirás un email cuando esté lista.',
            'solicitud_id' => $resultado
        ]);
    }

    /**
     * AJAX: Solicitar eliminación
     */
    public function ajax_request_deletion() {
        check_ajax_referer('flavor_privacy_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'No autorizado']);
        }

        $motivo = isset($_POST['motivo']) ? sanitize_textarea_field($_POST['motivo']) : '';
        $resultado = $this->create_deletion_request(get_current_user_id(), $motivo);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'Solicitud de eliminación creada. Un administrador la revisará.',
            'solicitud_id' => $resultado
        ]);
    }

    /**
     * AJAX: Actualizar consentimiento
     */
    public function ajax_update_consent() {
        check_ajax_referer('flavor_privacy_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'No autorizado']);
        }

        $tipo = isset($_POST['tipo']) ? sanitize_key($_POST['tipo']) : '';
        $consentido = isset($_POST['consentido']) ? filter_var($_POST['consentido'], FILTER_VALIDATE_BOOLEAN) : false;

        $resultado = $this->save_consent(get_current_user_id(), $tipo, $consentido);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Consentimiento actualizado']);
    }

    /**
     * AJAX: Obtener consentimientos
     */
    public function ajax_get_consents() {
        check_ajax_referer('flavor_privacy_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'No autorizado']);
        }

        $consentimientos = $this->get_user_consents(get_current_user_id());
        wp_send_json_success($consentimientos);
    }

    // =========================================================================
    // SHORTCODES Y VISTAS
    // =========================================================================

    /**
     * Renderizar panel de privacidad
     */
    public function render_privacy_panel($atts = []) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-notice flavor-notice-warning">' .
                   '<p>Debes iniciar sesión para acceder a tu panel de privacidad.</p>' .
                   '<a href="' . wp_login_url(flavor_current_request_url()) . '" class="flavor-btn flavor-btn-primary">Iniciar sesión</a>' .
                   '</div>';
        }

        $usuario_id = get_current_user_id();
        $consentimientos = $this->get_user_consents($usuario_id);
        $solicitudes = $this->get_user_requests($usuario_id);

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/privacy/views/privacy-panel.php';
        return ob_get_clean();
    }

    /**
     * Renderizar formulario de consentimientos
     */
    public function render_consent_form($atts = []) {
        $atts = shortcode_atts([
            'tipos' => '', // Lista de tipos separados por coma
            'obligatorios_solo' => false
        ], $atts);

        if (!is_user_logged_in()) {
            return '';
        }

        $usuario_id = get_current_user_id();
        $consentimientos = $this->get_user_consents($usuario_id);

        // Filtrar si es necesario
        if (!empty($atts['tipos'])) {
            $tipos_filtro = array_map('trim', explode(',', $atts['tipos']));
            $consentimientos = array_filter($consentimientos, function($c) use ($tipos_filtro) {
                return in_array($c['tipo'], $tipos_filtro);
            });
        }

        if ($atts['obligatorios_solo']) {
            $consentimientos = array_filter($consentimientos, function($c) {
                return $c['obligatorio'];
            });
        }

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/privacy/views/consent-form.php';
        return ob_get_clean();
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if (!is_user_logged_in()) {
            return;
        }

        wp_enqueue_style(
            'flavor-privacy',
            FLAVOR_CHAT_IA_URL . 'includes/privacy/assets/privacy.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-privacy',
            FLAVOR_CHAT_IA_URL . 'includes/privacy/assets/privacy.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-privacy', 'flavorPrivacy', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_privacy_nonce'),
            'restUrl' => rest_url('flavor-app/v1/privacidad/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'strings' => [
                'confirmExport' => '¿Solicitar exportación de todos tus datos? Recibirás un email cuando esté lista.',
                'confirmDelete' => '¿Estás seguro de que quieres solicitar la eliminación de tu cuenta? Esta acción es irreversible.',
                'processing' => 'Procesando...',
                'success' => 'Operación completada',
                'error' => 'Error al procesar la solicitud'
            ]
        ]);
    }

    // =========================================================================
    // ADMIN
    // =========================================================================

    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            'Privacidad y RGPD',
            'Privacidad',
            'manage_options',
            'flavor-privacy',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Renderizar página de admin
     */
    public function render_admin_page() {
        global $wpdb;
        $tabla_solicitudes = $this->prefix . 'privacy_requests';

        // Procesar acciones
        if (isset($_POST['action']) && $_POST['action'] === 'process_request') {
            check_admin_referer('flavor_privacy_admin');
            $solicitud_id = intval($_POST['solicitud_id']);
            $aprobar = $_POST['decision'] === 'aprobar';
            $motivo = sanitize_textarea_field($_POST['motivo_rechazo'] ?? '');
            $this->process_deletion_request($solicitud_id, $aprobar, $motivo);
        }

        // Obtener solicitudes pendientes
        $solicitudes_pendientes = $wpdb->get_results(
            "SELECT r.*, u.user_email, u.display_name
             FROM {$tabla_solicitudes} r
             LEFT JOIN {$wpdb->users} u ON r.usuario_id = u.ID
             WHERE r.estado = 'pendiente'
             ORDER BY r.fecha_solicitud ASC"
        );

        // Estadísticas
        $estadisticas = [
            'total_solicitudes' => $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes}"),
            'pendientes' => $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado = 'pendiente'"),
            'completadas' => $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado = 'completado'"),
            'exportaciones' => $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE tipo = 'exportar'"),
            'eliminaciones' => $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE tipo = 'eliminar'")
        ];

        include FLAVOR_CHAT_IA_PATH . 'includes/privacy/views/admin-privacy.php';
    }

    // =========================================================================
    // INTEGRACIÓN WORDPRESS PRIVACY
    // =========================================================================

    /**
     * Registrar exportador de datos para WordPress Privacy Tools
     */
    public function register_data_exporter($exporters) {
        $exporters[FLAVOR_PLATFORM_TEXT_DOMAIN] = [
            'exporter_friendly_name' => 'Flavor Chat IA',
            'callback' => [$this, 'wp_privacy_exporter']
        ];
        return $exporters;
    }

    /**
     * Exportador para WordPress Privacy Tools
     */
    public function wp_privacy_exporter($email, $page = 1) {
        $user = get_user_by('email', $email);
        if (!$user) {
            return ['data' => [], 'done' => true];
        }

        $exporter = Flavor_Data_Exporter::get_instance();
        $datos = $exporter->get_exportable_data($user->ID);

        $export_items = [];
        foreach ($datos as $grupo => $items) {
            foreach ($items as $item) {
                $export_items[] = [
                    'group_id' => 'flavor-' . sanitize_key($grupo),
                    'group_label' => ucfirst($grupo),
                    'item_id' => 'flavor-' . $grupo . '-' . ($item['id'] ?? uniqid()),
                    'data' => array_map(function($key, $value) {
                        return ['name' => $key, 'value' => is_array($value) ? wp_json_encode($value) : $value];
                    }, array_keys($item), array_values($item))
                ];
            }
        }

        return [
            'data' => $export_items,
            'done' => true
        ];
    }

    /**
     * Registrar borrador de datos para WordPress Privacy Tools
     */
    public function register_data_eraser($erasers) {
        $erasers[FLAVOR_PLATFORM_TEXT_DOMAIN] = [
            'eraser_friendly_name' => 'Flavor Chat IA',
            'callback' => [$this, 'wp_privacy_eraser']
        ];
        return $erasers;
    }

    /**
     * Borrador para WordPress Privacy Tools
     */
    public function wp_privacy_eraser($email, $page = 1) {
        $user = get_user_by('email', $email);
        if (!$user) {
            return [
                'items_removed' => false,
                'items_retained' => false,
                'messages' => [],
                'done' => true
            ];
        }

        $this->delete_user_data($user->ID, true);

        return [
            'items_removed' => true,
            'items_retained' => false,
            'messages' => ['Datos de Flavor Chat IA eliminados o anonimizados'],
            'done' => true
        ];
    }

    // =========================================================================
    // UTILIDADES
    // =========================================================================

    /**
     * Obtener IP del usuario
     */
    private function get_user_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = sanitize_text_field($_SERVER[$key]);
                // Si hay múltiples IPs, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }

    /**
     * Enviar email de notificación
     */
    private function send_notification_email($to, $tipo, $datos = []) {
        $plantillas = [
            'export_requested' => [
                'subject' => 'Solicitud de exportación de datos recibida',
                'body' => "Hola {nombre},\n\nHemos recibido tu solicitud de exportación de datos. Te notificaremos cuando esté lista para descargar.\n\nEste proceso puede tardar hasta 48 horas."
            ],
            'export_ready' => [
                'subject' => 'Tu exportación de datos está lista',
                'body' => "Hola {nombre},\n\nTu exportación de datos está lista para descargar.\n\nEnlace de descarga: {url_descarga}\n\nEste enlace expirará en {expira_en}."
            ],
            'deletion_requested' => [
                'subject' => 'Solicitud de eliminación de cuenta recibida',
                'body' => "Hola {nombre},\n\nHemos recibido tu solicitud de eliminación de cuenta. Un administrador la revisará y te notificaremos el resultado."
            ],
            'deletion_completed' => [
                'subject' => 'Tu cuenta ha sido eliminada',
                'body' => "Hola {nombre},\n\nTu cuenta y todos tus datos personales han sido eliminados de nuestra plataforma.\n\nGracias por haber sido parte de nuestra comunidad."
            ],
            'deletion_rejected' => [
                'subject' => 'Solicitud de eliminación de cuenta rechazada',
                'body' => "Hola {nombre},\n\nTu solicitud de eliminación de cuenta ha sido rechazada por el siguiente motivo:\n\n{motivo}\n\nSi tienes alguna pregunta, contacta con nosotros."
            ]
        ];

        if (!isset($plantillas[$tipo])) {
            return false;
        }

        $plantilla = $plantillas[$tipo];
        $subject = '[' . get_bloginfo('name') . '] ' . $plantilla['subject'];
        $body = $plantilla['body'];

        // Reemplazar variables
        foreach ($datos as $key => $value) {
            $body = str_replace('{' . $key . '}', $value, $body);
        }

        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        return wp_mail($to, $subject, $body, $headers);
    }

    /**
     * Notificar a administradores sobre solicitud de eliminación
     */
    private function notify_admins_deletion_request($usuario_id, $solicitud_id) {
        $admins = get_users(['role' => 'administrator']);
        $usuario = get_userdata($usuario_id);

        $subject = '[' . get_bloginfo('name') . '] Nueva solicitud de eliminación de cuenta';
        $body = sprintf(
            "Se ha recibido una nueva solicitud de eliminación de cuenta:\n\n" .
            "Usuario: %s (ID: %d)\n" .
            "Email: %s\n" .
            "Solicitud ID: %d\n\n" .
            "Por favor, revisa y procesa esta solicitud en el panel de administración.",
            $usuario->display_name,
            $usuario_id,
            $usuario->user_email,
            $solicitud_id
        );

        foreach ($admins as $admin) {
            wp_mail($admin->user_email, $subject, $body);
        }
    }

    /**
     * Notificar a administradores sobre eliminaciones pendientes
     */
    private function notify_admins_pending_deletions($count) {
        $admins = get_users(['role' => 'administrator']);

        $subject = '[' . get_bloginfo('name') . '] Solicitudes de eliminación pendientes';
        $body = sprintf(
            "Hay %d solicitud(es) de eliminación de cuenta pendiente(s) de revisión.\n\n" .
            "Por favor, accede al panel de administración para procesarlas.",
            $count
        );

        foreach ($admins as $admin) {
            wp_mail($admin->user_email, $subject, $body);
        }
    }

    /**
     * Limpiar exportaciones antiguas
     */
    public function cleanup_old_exports() {
        $upload_dir = wp_upload_dir();
        $exports_dir = $upload_dir['basedir'] . '/flavor-exports/';

        if (!is_dir($exports_dir)) {
            return;
        }

        $archivos = glob($exports_dir . '*.zip');
        $limite = strtotime('-48 hours');

        foreach ($archivos as $archivo) {
            if (filemtime($archivo) < $limite) {
                @unlink($archivo);
            }
        }
    }
}
