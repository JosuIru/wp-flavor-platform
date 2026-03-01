<?php
/**
 * Gestión de Membresías y Solicitudes de Unión a Grupos de Consumo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar solicitudes de unión y membresías de grupos de consumo
 */
class Flavor_GC_Membership {

    /**
     * Instancia singleton
     * @var Flavor_GC_Membership|null
     */
    private static $instancia = null;

    /**
     * Tabla de consumidores
     * @var string
     */
    private $tabla_consumidores;

    /**
     * Tabla de solicitudes
     * @var string
     */
    private $tabla_solicitudes;

    /**
     * Preferencias alimentarias disponibles
     */
    const PREFERENCIAS_ALIMENTARIAS = [
        'vegetariano' => 'Vegetariano',
        'vegano' => 'Vegano',
        'sin_gluten' => 'Sin gluten',
        'sin_lactosa' => 'Sin lactosa',
        'pescetariano' => 'Pescetariano',
        'sin_frutos_secos' => 'Sin frutos secos',
        'organico' => 'Solo productos ecologicos',
        'km0' => 'Preferencia KM0',
    ];

    /**
     * Opciones de como nos conociste
     */
    const COMO_NOS_CONOCISTE = [
        'amigo' => 'A traves de un amigo/conocido',
        'redes_sociales' => 'Redes sociales',
        'buscador' => 'Buscador de internet',
        'evento' => 'En un evento o feria',
        'prensa' => 'Prensa o medios',
        'asociacion' => 'Otra asociacion o colectivo',
        'otro' => 'Otro',
    ];

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';
        $this->tabla_solicitudes = $wpdb->prefix . 'flavor_gc_solicitudes';

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_GC_Membership
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // Crear tabla de solicitudes si no existe
        add_action('init', [$this, 'crear_tabla_solicitudes'], 5);

        // Registrar shortcodes
        add_shortcode('gc_formulario_union', [$this, 'shortcode_formulario_union']);
        add_shortcode('gc_grupos_lista', [$this, 'shortcode_grupos_lista']);

        // AJAX handlers (publico y privado)
        add_action('wp_ajax_gc_solicitar_union', [$this, 'ajax_solicitar_union']);
        add_action('wp_ajax_nopriv_gc_solicitar_union', [$this, 'ajax_solicitar_union']);
        add_action('wp_ajax_gc_aprobar_solicitud', [$this, 'ajax_aprobar_solicitud']);
        add_action('wp_ajax_gc_rechazar_solicitud', [$this, 'ajax_rechazar_solicitud']);
        add_action('wp_ajax_gc_obtener_solicitud', [$this, 'ajax_obtener_solicitud']);

        // Hook para aprobar/rechazar desde consumidor manager
        add_action('gc_consumidor_estado_cambiado', [$this, 'on_estado_cambiado'], 10, 3);

        // Filtro para badge en menu admin
        add_filter('gc_admin_menu_badge', [$this, 'obtener_badge_solicitudes']);
    }

    /**
     * Crea la tabla de solicitudes si no existe
     */
    public function crear_tabla_solicitudes() {
        global $wpdb;

        $tabla_solicitudes = $wpdb->prefix . 'flavor_gc_solicitudes';

        // Verificar si la tabla ya existe
        $tabla_existe = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $tabla_solicitudes
        ));

        if ($tabla_existe === $tabla_solicitudes) {
            return true;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql_solicitudes = "CREATE TABLE IF NOT EXISTS $tabla_solicitudes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            grupo_id bigint(20) unsigned NOT NULL,
            motivacion text DEFAULT NULL,
            preferencias_alimentarias text DEFAULT NULL,
            alergias text DEFAULT NULL,
            como_nos_conocio varchar(100) DEFAULT NULL,
            acepta_normas tinyint(1) DEFAULT 0,
            estado enum('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
            motivo_rechazo text DEFAULT NULL,
            fecha_solicitud datetime DEFAULT NULL,
            fecha_resolucion datetime DEFAULT NULL,
            resuelto_por bigint(20) unsigned DEFAULT NULL,
            notas_admin text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_grupo (usuario_id, grupo_id),
            KEY grupo_id (grupo_id),
            KEY estado (estado),
            KEY fecha_solicitud (fecha_solicitud)
        ) $charset_collate;";

        // Usar dbDelta si estamos en admin o cargar upgrade.php si es necesario
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta($sql_solicitudes);

        return true;
    }

    /**
     * Solicitar union a un grupo
     *
     * @param int   $grupo_id   ID del grupo
     * @param int   $usuario_id ID del usuario
     * @param array $datos      Datos de la solicitud
     * @return array Resultado de la operacion
     */
    public function solicitar_union($grupo_id, $usuario_id, $datos = []) {
        global $wpdb;

        // Asegurar que la tabla existe
        $this->crear_tabla_solicitudes();

        // Validar usuario
        $usuario = get_user_by('ID', $usuario_id);
        if (!$usuario) {
            return [
                'success' => false,
                'error' => __('Usuario no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Validar grupo
        $grupo = get_post($grupo_id);
        if (!$grupo || $grupo->post_type !== 'gc_grupo') {
            return [
                'success' => false,
                'error' => __('Grupo de consumo no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Verificar si ya es miembro
        $membresia_existente = $this->es_miembro($grupo_id, $usuario_id);
        if ($membresia_existente) {
            return [
                'success' => false,
                'error' => __('Ya eres miembro de este grupo de consumo.', 'flavor-chat-ia'),
            ];
        }

        // Verificar si tiene solicitud pendiente
        $solicitud_pendiente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_solicitudes}
             WHERE usuario_id = %d AND grupo_id = %d AND estado = 'pendiente'",
            $usuario_id,
            $grupo_id
        ));

        if ($solicitud_pendiente) {
            return [
                'success' => false,
                'error' => __('Ya tienes una solicitud pendiente para este grupo.', 'flavor-chat-ia'),
            ];
        }

        // Verificar si tuvo solicitud rechazada recientemente (30 dias)
        $solicitud_rechazada = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_solicitudes}
             WHERE usuario_id = %d AND grupo_id = %d
             AND estado = 'rechazada'
             AND fecha_resolucion > DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $usuario_id,
            $grupo_id
        ));

        if ($solicitud_rechazada) {
            return [
                'success' => false,
                'error' => __('Tu solicitud anterior fue rechazada. Podras volver a solicitar en 30 dias.', 'flavor-chat-ia'),
            ];
        }

        // Validar datos requeridos
        if (empty($datos['acepta_normas'])) {
            return [
                'success' => false,
                'error' => __('Debes aceptar las normas del grupo para continuar.', 'flavor-chat-ia'),
            ];
        }

        // Preparar preferencias alimentarias
        $preferencias_array = [];
        if (!empty($datos['preferencias_alimentarias']) && is_array($datos['preferencias_alimentarias'])) {
            $preferencias_array = array_filter($datos['preferencias_alimentarias'], function($pref) {
                return array_key_exists($pref, self::PREFERENCIAS_ALIMENTARIAS);
            });
        }

        // Insertar o actualizar solicitud
        $datos_insertar = [
            'usuario_id' => $usuario_id,
            'grupo_id' => $grupo_id,
            'motivacion' => sanitize_textarea_field($datos['motivacion'] ?? ''),
            'preferencias_alimentarias' => !empty($preferencias_array) ? wp_json_encode($preferencias_array) : null,
            'alergias' => sanitize_textarea_field($datos['alergias'] ?? ''),
            'como_nos_conocio' => sanitize_text_field($datos['como_nos_conocio'] ?? ''),
            'acepta_normas' => 1,
            'estado' => 'pendiente',
            'fecha_solicitud' => current_time('mysql'),
        ];

        // Intentar insertar, o actualizar si ya existe (solicitud rechazada antigua)
        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tabla_solicitudes} WHERE usuario_id = %d AND grupo_id = %d",
            $usuario_id,
            $grupo_id
        ));

        if ($existente) {
            $resultado = $wpdb->update(
                $this->tabla_solicitudes,
                $datos_insertar,
                ['id' => $existente],
                ['%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s'],
                ['%d']
            );
            $solicitud_id = $existente;
        } else {
            $resultado = $wpdb->insert(
                $this->tabla_solicitudes,
                $datos_insertar,
                ['%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
            );
            $solicitud_id = $wpdb->insert_id;
        }

        if ($resultado === false) {
            // Log del error para debugging
            $db_error = $wpdb->last_error;
            flavor_log_error( 'Solicitud Union Error: ' . $db_error, 'GruposConsumo' );

            // Si es admin, mostrar el error real para debugging
            $mensaje_error = __('Error al procesar la solicitud. Por favor, intentalo de nuevo.', 'flavor-chat-ia');
            if (current_user_can('manage_options') && !empty($db_error)) {
                $mensaje_error .= ' (DB: ' . esc_html($db_error) . ')';
            }

            return [
                'success' => false,
                'error' => $mensaje_error,
            ];
        }

        // Notificar al coordinador
        $this->notificar_coordinador($grupo_id, [
            'solicitud_id' => $solicitud_id,
            'usuario_id' => $usuario_id,
            'usuario_nombre' => $usuario->display_name,
            'usuario_email' => $usuario->user_email,
        ]);

        // Disparar accion
        do_action('gc_solicitud_union_creada', $solicitud_id, $grupo_id, $usuario_id);

        return [
            'success' => true,
            'solicitud_id' => $solicitud_id,
            'mensaje' => __('Tu solicitud ha sido enviada. Te notificaremos cuando sea revisada.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Aprobar una solicitud
     *
     * @param int $consumidor_id ID del consumidor (o solicitud_id si se usa desde solicitudes)
     * @param int $resuelto_por  ID del usuario que aprueba (opcional)
     * @return array Resultado
     */
    public function aprobar_solicitud($consumidor_id, $resuelto_por = null) {
        global $wpdb;

        if (!$resuelto_por) {
            $resuelto_por = get_current_user_id();
        }

        // Intentar obtener desde tabla de solicitudes primero
        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_solicitudes} WHERE id = %d",
            $consumidor_id
        ));

        if ($solicitud) {
            // Es una solicitud, aprobarla y crear consumidor
            if ($solicitud->estado !== 'pendiente') {
                return [
                    'success' => false,
                    'error' => __('Esta solicitud ya fue procesada.', 'flavor-chat-ia'),
                ];
            }

            // Actualizar solicitud
            $wpdb->update(
                $this->tabla_solicitudes,
                [
                    'estado' => 'aprobada',
                    'fecha_resolucion' => current_time('mysql'),
                    'resuelto_por' => $resuelto_por,
                ],
                ['id' => $consumidor_id],
                ['%s', '%s', '%d'],
                ['%d']
            );

            // Crear consumidor usando el manager existente
            $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();

            // Decodificar preferencias
            $preferencias = '';
            if ($solicitud->preferencias_alimentarias) {
                $prefs_array = json_decode($solicitud->preferencias_alimentarias, true);
                if (is_array($prefs_array)) {
                    $preferencias = implode(', ', array_map(function($pref) {
                        return self::PREFERENCIAS_ALIMENTARIAS[$pref] ?? $pref;
                    }, $prefs_array));
                }
            }

            $resultado_alta = $consumidor_manager->alta_consumidor(
                $solicitud->usuario_id,
                $solicitud->grupo_id,
                'consumidor',
                [
                    'preferencias' => $preferencias,
                    'alergias' => $solicitud->alergias,
                ]
            );

            if (!$resultado_alta['success']) {
                return $resultado_alta;
            }

            // Cambiar estado a activo directamente
            $nuevo_consumidor_id = $resultado_alta['consumidor_id'];
            $consumidor_manager->cambiar_estado($nuevo_consumidor_id, 'activo');

            // Notificar al usuario
            $this->notificar_usuario_aprobado($solicitud->usuario_id, $solicitud->grupo_id);

            // Disparar accion
            do_action('gc_solicitud_aprobada', $consumidor_id, $solicitud->usuario_id, $solicitud->grupo_id);

            return [
                'success' => true,
                'consumidor_id' => $nuevo_consumidor_id,
                'mensaje' => __('Solicitud aprobada. El usuario ya es miembro del grupo.', 'flavor-chat-ia'),
            ];
        }

        // Si no es solicitud, puede ser consumidor existente en estado pendiente
        $consumidor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_consumidores} WHERE id = %d",
            $consumidor_id
        ));

        if (!$consumidor) {
            return [
                'success' => false,
                'error' => __('Solicitud no encontrada.', 'flavor-chat-ia'),
            ];
        }

        // Usar el manager para cambiar estado
        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        $resultado = $consumidor_manager->cambiar_estado($consumidor_id, 'activo');

        if ($resultado['success']) {
            $this->notificar_usuario_aprobado($consumidor->usuario_id, $consumidor->grupo_id);
            do_action('gc_membresia_aprobada', $consumidor_id, $consumidor->usuario_id, $consumidor->grupo_id);
        }

        return $resultado;
    }

    /**
     * Rechazar una solicitud
     *
     * @param int    $consumidor_id ID del consumidor o solicitud
     * @param string $motivo        Motivo del rechazo
     * @param int    $resuelto_por  ID del usuario que rechaza
     * @return array Resultado
     */
    public function rechazar_solicitud($consumidor_id, $motivo = '', $resuelto_por = null) {
        global $wpdb;

        if (!$resuelto_por) {
            $resuelto_por = get_current_user_id();
        }

        // Intentar desde tabla de solicitudes
        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_solicitudes} WHERE id = %d",
            $consumidor_id
        ));

        if ($solicitud) {
            if ($solicitud->estado !== 'pendiente') {
                return [
                    'success' => false,
                    'error' => __('Esta solicitud ya fue procesada.', 'flavor-chat-ia'),
                ];
            }

            $resultado = $wpdb->update(
                $this->tabla_solicitudes,
                [
                    'estado' => 'rechazada',
                    'motivo_rechazo' => sanitize_textarea_field($motivo),
                    'fecha_resolucion' => current_time('mysql'),
                    'resuelto_por' => $resuelto_por,
                ],
                ['id' => $consumidor_id],
                ['%s', '%s', '%s', '%d'],
                ['%d']
            );

            if ($resultado === false) {
                return [
                    'success' => false,
                    'error' => __('Error al procesar el rechazo.', 'flavor-chat-ia'),
                ];
            }

            // Notificar al usuario
            $this->notificar_usuario_rechazado($solicitud->usuario_id, $solicitud->grupo_id, $motivo);

            // Disparar accion
            do_action('gc_solicitud_rechazada', $consumidor_id, $solicitud->usuario_id, $solicitud->grupo_id, $motivo);

            return [
                'success' => true,
                'mensaje' => __('Solicitud rechazada correctamente.', 'flavor-chat-ia'),
            ];
        }

        // Si es consumidor pendiente
        $consumidor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_consumidores} WHERE id = %d",
            $consumidor_id
        ));

        if (!$consumidor) {
            return [
                'success' => false,
                'error' => __('Solicitud no encontrada.', 'flavor-chat-ia'),
            ];
        }

        // Dar de baja
        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        $resultado = $consumidor_manager->cambiar_estado($consumidor_id, 'baja');

        if ($resultado['success']) {
            $this->notificar_usuario_rechazado($consumidor->usuario_id, $consumidor->grupo_id, $motivo);
        }

        return $resultado;
    }

    /**
     * Verificar si un usuario es miembro de un grupo
     *
     * @param int $grupo_id   ID del grupo
     * @param int $usuario_id ID del usuario
     * @return bool
     */
    public function es_miembro($grupo_id, $usuario_id) {
        global $wpdb;

        $consumidor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_consumidores}
             WHERE grupo_id = %d AND usuario_id = %d AND estado IN ('activo', 'suspendido')",
            $grupo_id,
            $usuario_id
        ));

        return !empty($consumidor);
    }

    /**
     * Obtener el estado de membresia de un usuario en un grupo
     *
     * @param int $grupo_id   ID del grupo
     * @param int $usuario_id ID del usuario
     * @return string|null Estado o null si no existe relacion
     */
    public function obtener_estado_membresia($grupo_id, $usuario_id) {
        global $wpdb;

        // Primero verificar si es miembro
        $consumidor = $wpdb->get_row($wpdb->prepare(
            "SELECT estado FROM {$this->tabla_consumidores}
             WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id,
            $usuario_id
        ));

        if ($consumidor) {
            return $consumidor->estado;
        }

        // Si no es miembro, verificar si tiene solicitud pendiente
        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT estado FROM {$this->tabla_solicitudes}
             WHERE grupo_id = %d AND usuario_id = %d AND estado = 'pendiente'",
            $grupo_id,
            $usuario_id
        ));

        if ($solicitud) {
            return 'solicitud_pendiente';
        }

        return null;
    }

    /**
     * Obtener solicitudes pendientes de un grupo
     *
     * @param int   $grupo_id ID del grupo
     * @param array $filtros  Filtros opcionales
     * @param int   $limite   Limite de resultados
     * @param int   $offset   Offset para paginacion
     * @return array
     */
    public function obtener_solicitudes_pendientes($grupo_id, $filtros = [], $limite = 20, $offset = 0) {
        global $wpdb;

        $where_clauses = ['s.grupo_id = %d', "s.estado = 'pendiente'"];
        $parametros = [$grupo_id];

        if (!empty($filtros['busqueda'])) {
            $busqueda = '%' . $wpdb->esc_like(sanitize_text_field($filtros['busqueda'])) . '%';
            $where_clauses[] = '(u.display_name LIKE %s OR u.user_email LIKE %s)';
            $parametros[] = $busqueda;
            $parametros[] = $busqueda;
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Contar total
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_solicitudes} s
             LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
             WHERE {$where_sql}",
            ...$parametros
        ));

        // Obtener registros
        $parametros[] = $limite;
        $parametros[] = $offset;

        $solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.display_name, u.user_email, u.user_registered
             FROM {$this->tabla_solicitudes} s
             LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
             WHERE {$where_sql}
             ORDER BY s.fecha_solicitud DESC
             LIMIT %d OFFSET %d",
            ...$parametros
        ));

        return [
            'solicitudes' => $solicitudes,
            'total' => (int) $total,
            'paginas' => ceil($total / $limite),
        ];
    }

    /**
     * Obtener todas las solicitudes (para admin)
     *
     * @param int    $grupo_id ID del grupo
     * @param string $estado   Estado a filtrar (pendiente, aprobada, rechazada, o vacio para todas)
     * @param int    $limite   Limite
     * @param int    $offset   Offset
     * @return array
     */
    public function obtener_solicitudes($grupo_id, $estado = '', $limite = 20, $offset = 0) {
        global $wpdb;

        $where_clauses = ['s.grupo_id = %d'];
        $parametros = [$grupo_id];

        if (!empty($estado)) {
            $where_clauses[] = 's.estado = %s';
            $parametros[] = sanitize_text_field($estado);
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Contar total
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_solicitudes} s WHERE {$where_sql}",
            ...$parametros
        ));

        // Obtener registros
        $parametros[] = $limite;
        $parametros[] = $offset;

        $solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*,
                    u.display_name, u.user_email,
                    r.display_name as resuelto_por_nombre
             FROM {$this->tabla_solicitudes} s
             LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
             LEFT JOIN {$wpdb->users} r ON s.resuelto_por = r.ID
             WHERE {$where_sql}
             ORDER BY s.fecha_solicitud DESC
             LIMIT %d OFFSET %d",
            ...$parametros
        ));

        return [
            'solicitudes' => $solicitudes,
            'total' => (int) $total,
            'paginas' => ceil($total / $limite),
        ];
    }

    /**
     * Obtener miembros de un grupo
     *
     * @param int    $grupo_id ID del grupo
     * @param string $estado   Estado a filtrar
     * @return array
     */
    public function obtener_miembros($grupo_id, $estado = 'activo') {
        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        return $consumidor_manager->listar_consumidores($grupo_id, ['estado' => $estado]);
    }

    /**
     * Contar solicitudes pendientes (para badge)
     *
     * @param int|null $grupo_id ID del grupo (null para todos)
     * @return int
     */
    public function contar_solicitudes_pendientes($grupo_id = null) {
        global $wpdb;

        if ($grupo_id) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_solicitudes} WHERE grupo_id = %d AND estado = 'pendiente'",
                $grupo_id
            ));
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tabla_solicitudes} WHERE estado = 'pendiente'"
        );
    }

    /**
     * Obtener una solicitud por ID
     *
     * @param int $solicitud_id ID de la solicitud
     * @return object|null
     */
    public function obtener_solicitud($solicitud_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT s.*,
                    u.display_name, u.user_email, u.user_registered,
                    g.post_title as grupo_nombre,
                    r.display_name as resuelto_por_nombre
             FROM {$this->tabla_solicitudes} s
             LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
             LEFT JOIN {$wpdb->posts} g ON s.grupo_id = g.ID
             LEFT JOIN {$wpdb->users} r ON s.resuelto_por = r.ID
             WHERE s.id = %d",
            $solicitud_id
        ));
    }

    // ========================================
    // Notificaciones
    // ========================================

    /**
     * Notificar al coordinador de nueva solicitud
     *
     * @param int   $grupo_id  ID del grupo
     * @param array $solicitud Datos de la solicitud
     */
    private function notificar_coordinador($grupo_id, $solicitud) {
        // Obtener coordinadores del grupo
        global $wpdb;

        $coordinadores = $wpdb->get_col($wpdb->prepare(
            "SELECT usuario_id FROM {$this->tabla_consumidores}
             WHERE grupo_id = %d AND rol = 'coordinador' AND estado = 'activo'",
            $grupo_id
        ));

        // Añadir administradores
        $admins = get_users(['role' => 'administrator', 'fields' => 'ID']);
        $destinatarios = array_unique(array_merge($coordinadores, $admins));

        if (empty($destinatarios)) {
            return;
        }

        $grupo = get_post($grupo_id);

        $datos = [
            'titulo' => __('Nueva solicitud de union', 'flavor-chat-ia'),
            'mensaje' => sprintf(
                __('%s ha solicitado unirse al grupo "%s". Revisa la solicitud para aprobarla o rechazarla.', 'flavor-chat-ia'),
                $solicitud['usuario_nombre'],
                $grupo ? $grupo->post_title : ''
            ),
            'enlace' => admin_url('admin.php?page=gc-solicitudes&grupo_id=' . $grupo_id),
            'enlace_texto' => __('Ver solicitudes pendientes', 'flavor-chat-ia'),
        ];

        // Usar el sistema de notificaciones existente
        if (class_exists('Flavor_GC_Notification_Channels')) {
            $notification_channels = Flavor_GC_Notification_Channels::get_instance();
            // Enviar por email a coordinadores
            foreach ($destinatarios as $destinatario) {
                $email = get_userdata($destinatario)->user_email;
                if ($email) {
                    wp_mail(
                        $email,
                        '[' . get_bloginfo('name') . '] ' . $datos['titulo'],
                        $this->generar_email_notificacion($datos),
                        ['Content-Type: text/html; charset=UTF-8']
                    );
                }
            }
        }

        // Disparar accion para integraciones adicionales
        do_action('gc_notificar_coordinador_solicitud', $grupo_id, $solicitud, $destinatarios);
    }

    /**
     * Notificar al usuario que su solicitud fue aprobada
     *
     * @param int $usuario_id ID del usuario
     * @param int $grupo_id   ID del grupo
     */
    private function notificar_usuario_aprobado($usuario_id, $grupo_id) {
        $usuario = get_userdata($usuario_id);
        $grupo = get_post($grupo_id);

        if (!$usuario || !$grupo) {
            return;
        }

        $datos = [
            'titulo' => __('Tu solicitud ha sido aprobada', 'flavor-chat-ia'),
            'mensaje' => sprintf(
                __('Enhorabuena! Tu solicitud para unirte al grupo "%s" ha sido aprobada. Ya puedes empezar a hacer pedidos.', 'flavor-chat-ia'),
                $grupo->post_title
            ),
            'enlace' => home_url('/mi-portal/grupos-consumo/'),
            'enlace_texto' => __('Ir a Mi Grupo', 'flavor-chat-ia'),
        ];

        wp_mail(
            $usuario->user_email,
            '[' . get_bloginfo('name') . '] ' . $datos['titulo'],
            $this->generar_email_notificacion($datos),
            ['Content-Type: text/html; charset=UTF-8']
        );

        // Disparar accion
        do_action('gc_notificar_usuario_aprobado', $usuario_id, $grupo_id);
    }

    /**
     * Notificar al usuario que su solicitud fue rechazada
     *
     * @param int    $usuario_id ID del usuario
     * @param int    $grupo_id   ID del grupo
     * @param string $motivo     Motivo del rechazo
     */
    private function notificar_usuario_rechazado($usuario_id, $grupo_id, $motivo = '') {
        $usuario = get_userdata($usuario_id);
        $grupo = get_post($grupo_id);

        if (!$usuario || !$grupo) {
            return;
        }

        $mensaje = sprintf(
            __('Lo sentimos, tu solicitud para unirte al grupo "%s" no ha sido aprobada.', 'flavor-chat-ia'),
            $grupo->post_title
        );

        if (!empty($motivo)) {
            $mensaje .= "\n\n" . __('Motivo:', 'flavor-chat-ia') . ' ' . $motivo;
        }

        $mensaje .= "\n\n" . __('Si tienes dudas, puedes contactar con los coordinadores del grupo.', 'flavor-chat-ia');

        $datos = [
            'titulo' => __('Sobre tu solicitud de union', 'flavor-chat-ia'),
            'mensaje' => $mensaje,
        ];

        wp_mail(
            $usuario->user_email,
            '[' . get_bloginfo('name') . '] ' . $datos['titulo'],
            $this->generar_email_notificacion($datos),
            ['Content-Type: text/html; charset=UTF-8']
        );

        // Disparar accion
        do_action('gc_notificar_usuario_rechazado', $usuario_id, $grupo_id, $motivo);
    }

    /**
     * Generar HTML de email de notificacion
     *
     * @param array $datos Datos del email
     * @return string HTML del email
     */
    private function generar_email_notificacion($datos) {
        $sitio_nombre = get_bloginfo('name');
        $sitio_url = home_url();

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px; background: #f9f9f9; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .btn { display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . esc_html($sitio_nombre) . '</h1>
        </div>
        <div class="content">';

        if (!empty($datos['titulo'])) {
            $html .= '<h2 style="margin-top: 0;">' . esc_html($datos['titulo']) . '</h2>';
        }

        if (!empty($datos['mensaje'])) {
            $html .= '<p>' . nl2br(esc_html($datos['mensaje'])) . '</p>';
        }

        if (!empty($datos['enlace'])) {
            $html .= '<p><a href="' . esc_url($datos['enlace']) . '" class="btn">' .
                     esc_html($datos['enlace_texto'] ?? __('Ver mas', 'flavor-chat-ia')) . '</a></p>';
        }

        $html .= '</div>
        <div class="footer">
            <p>Este email fue enviado desde ' . esc_html($sitio_nombre) . '</p>
            <p><a href="' . esc_url($sitio_url) . '">' . esc_html($sitio_url) . '</a></p>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    // ========================================
    // Shortcode
    // ========================================

    /**
     * Shortcode para el formulario de union
     *
     * @param array $atributos Atributos del shortcode
     * @return string HTML del formulario
     */
    public function shortcode_formulario_union($atributos) {
        $atributos = shortcode_atts([
            'grupo_id' => 0,
            'mostrar_normas' => 'true',
        ], $atributos);

        // Obtener grupo_id desde atributo, URL o query var
        $grupo_id = absint($atributos['grupo_id']);
        if (!$grupo_id && isset($_GET['grupo'])) {
            $grupo_id = absint($_GET['grupo']);
        }
        if (!$grupo_id) {
            $grupo_id = get_query_var('gc_grupo_id', 0);
        }
        if (!$grupo_id) {
            // Intentar obtener el primer grupo disponible
            $grupos = get_posts([
                'post_type' => 'gc_grupo',
                'posts_per_page' => 1,
                'post_status' => 'publish',
            ]);
            if (!empty($grupos)) {
                $grupo_id = $grupos[0]->ID;
            }
        }

        if (!$grupo_id) {
            // Si no hay grupo_id, usar el grupo virtual del sitio (ID 1)
            $grupo_id = 1;
        }

        $grupo = get_post($grupo_id);

        // Si no existe el post, crear grupo virtual para el sitio actual
        if (!$grupo || !in_array($grupo->post_type, ['gc_grupo', 'gc_grupo_virtual'], true)) {
            $grupo = new stdClass();
            $grupo->ID = $grupo_id;
            $grupo->post_title = sprintf(__('Grupo de Consumo de %s', 'flavor-chat-ia'), get_bloginfo('name'));
            $grupo->post_excerpt = '';
            $grupo->post_type = 'gc_grupo_virtual';
        }

        // Verificar si el usuario esta logueado
        if (!is_user_logged_in()) {
            return $this->render_mensaje_no_logueado($grupo);
        }

        $usuario_id = get_current_user_id();
        $estado_membresia = $this->obtener_estado_membresia($grupo_id, $usuario_id);

        // Si ya es miembro
        if ($estado_membresia === 'activo') {
            return $this->render_mensaje_ya_miembro($grupo);
        }

        // Si esta suspendido
        if ($estado_membresia === 'suspendido') {
            return $this->render_mensaje_suspendido($grupo);
        }

        // Si tiene solicitud pendiente
        if ($estado_membresia === 'solicitud_pendiente') {
            return $this->render_mensaje_solicitud_pendiente($grupo);
        }

        // Si no es miembro, mostrar formulario
        return $this->render_formulario_union($grupo, $atributos);
    }

    /**
     * Renderizar mensaje para usuarios no logueados
     */
    private function render_mensaje_no_logueado($grupo) {
        ob_start();
        ?>
        <div class="gc-union-container gc-union-no-logueado">
            <div class="gc-union-icon">
                <span class="dashicons dashicons-lock"></span>
            </div>
            <h3><?php _e('Inicia sesion para unirte', 'flavor-chat-ia'); ?></h3>
            <p><?php printf(
                __('Para unirte al grupo "%s" necesitas tener una cuenta e iniciar sesion.', 'flavor-chat-ia'),
                esc_html($grupo->post_title)
            ); ?></p>
            <div class="gc-union-acciones">
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="gc-btn gc-btn-primary">
                    <?php _e('Iniciar sesion', 'flavor-chat-ia'); ?>
                </a>
                <?php if (get_option('users_can_register')): ?>
                    <a href="<?php echo esc_url(wp_registration_url()); ?>" class="gc-btn gc-btn-outline">
                        <?php _e('Crear cuenta', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar mensaje para usuarios que ya son miembros
     */
    private function render_mensaje_ya_miembro($grupo) {
        ob_start();
        ?>
        <div class="gc-union-container gc-union-miembro">
            <div class="gc-union-icon gc-icon-success">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <h3><?php _e('Ya eres miembro', 'flavor-chat-ia'); ?></h3>
            <p><?php printf(
                __('Ya eres miembro del grupo "%s". Puedes acceder a todos los beneficios.', 'flavor-chat-ia'),
                esc_html($grupo->post_title)
            ); ?></p>
            <div class="gc-union-acciones">
                <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/')); ?>" class="gc-btn gc-btn-primary">
                    <?php _e('Ir a mi grupo', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar mensaje para usuarios suspendidos
     */
    private function render_mensaje_suspendido($grupo) {
        ob_start();
        ?>
        <div class="gc-union-container gc-union-suspendido">
            <div class="gc-union-icon gc-icon-warning">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <h3><?php _e('Membresia suspendida', 'flavor-chat-ia'); ?></h3>
            <p><?php printf(
                __('Tu membresia en "%s" esta actualmente suspendida. Contacta con los coordinadores para mas informacion.', 'flavor-chat-ia'),
                esc_html($grupo->post_title)
            ); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar mensaje para solicitud pendiente
     */
    private function render_mensaje_solicitud_pendiente($grupo) {
        ob_start();
        ?>
        <div class="gc-union-container gc-union-pendiente">
            <div class="gc-union-icon gc-icon-info">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <h3><?php _e('Solicitud pendiente', 'flavor-chat-ia'); ?></h3>
            <p><?php printf(
                __('Tu solicitud para unirte a "%s" esta pendiente de aprobacion. Te notificaremos cuando sea revisada.', 'flavor-chat-ia'),
                esc_html($grupo->post_title)
            ); ?></p>
            <div class="gc-union-info-adicional">
                <p><small><?php _e('Las solicitudes suelen revisarse en un plazo de 24-48 horas.', 'flavor-chat-ia'); ?></small></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar el formulario de union
     */
    private function render_formulario_union($grupo, $atributos) {
        $mostrar_normas = filter_var($atributos['mostrar_normas'], FILTER_VALIDATE_BOOLEAN);

        // Obtener normas del grupo (meta o contenido)
        $normas_grupo = get_post_meta($grupo->ID, '_gc_normas_grupo', true);
        if (empty($normas_grupo)) {
            $normas_grupo = __('Al unirte a este grupo de consumo te comprometes a:
- Respetar los plazos de pedidos y recogidas
- Pagar puntualmente tus pedidos
- Participar en las asambleas y turnos de reparto cuando sea necesario
- Avisar con antelacion si no puedes recoger tu pedido
- Respetar a los demas miembros y productores', 'flavor-chat-ia');
        }

        ob_start();
        ?>
        <div class="gc-formulario-union-wrapper">
            <div class="gc-formulario-header">
                <h3><?php printf(__('Unirse a "%s"', 'flavor-chat-ia'), esc_html($grupo->post_title)); ?></h3>
                <p><?php _e('Completa el siguiente formulario para solicitar tu ingreso al grupo.', 'flavor-chat-ia'); ?></p>
            </div>

            <form id="gc-formulario-union" class="gc-formulario-union" data-grupo-id="<?php echo esc_attr($grupo->ID); ?>">
                <?php wp_nonce_field('gc_solicitar_union', 'gc_union_nonce'); ?>
                <input type="hidden" name="grupo_id" value="<?php echo esc_attr($grupo->ID); ?>">

                <!-- Motivacion -->
                <div class="gc-campo">
                    <label for="gc-motivacion">
                        <?php _e('Por que quieres unirte a este grupo?', 'flavor-chat-ia'); ?>
                        <span class="gc-requerido">*</span>
                    </label>
                    <textarea
                        id="gc-motivacion"
                        name="motivacion"
                        rows="4"
                        required
                        placeholder="<?php esc_attr_e('Cuentanos un poco sobre ti y por que te interesa formar parte del grupo...', 'flavor-chat-ia'); ?>"
                    ></textarea>
                </div>

                <!-- Preferencias alimentarias -->
                <div class="gc-campo">
                    <label><?php _e('Preferencias alimentarias', 'flavor-chat-ia'); ?></label>
                    <p class="gc-campo-descripcion"><?php _e('Selecciona las que apliquen a ti o tu familia:', 'flavor-chat-ia'); ?></p>
                    <div class="gc-checkboxes-grid">
                        <?php foreach (self::PREFERENCIAS_ALIMENTARIAS as $valor => $etiqueta): ?>
                            <label class="gc-checkbox-item">
                                <input type="checkbox" name="preferencias_alimentarias[]" value="<?php echo esc_attr($valor); ?>">
                                <span><?php echo esc_html($etiqueta); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Alergias -->
                <div class="gc-campo">
                    <label for="gc-alergias"><?php _e('Alergias o intolerancias alimentarias', 'flavor-chat-ia'); ?></label>
                    <textarea
                        id="gc-alergias"
                        name="alergias"
                        rows="2"
                        placeholder="<?php esc_attr_e('Indica cualquier alergia o intolerancia que debamos conocer...', 'flavor-chat-ia'); ?>"
                    ></textarea>
                </div>

                <!-- Como nos conociste -->
                <div class="gc-campo">
                    <label for="gc-como-conociste">
                        <?php _e('Como nos conociste?', 'flavor-chat-ia'); ?>
                        <span class="gc-requerido">*</span>
                    </label>
                    <select id="gc-como-conociste" name="como_nos_conocio" required>
                        <option value=""><?php _e('Selecciona una opcion...', 'flavor-chat-ia'); ?></option>
                        <?php foreach (self::COMO_NOS_CONOCISTE as $valor => $etiqueta): ?>
                            <option value="<?php echo esc_attr($valor); ?>"><?php echo esc_html($etiqueta); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Normas del grupo -->
                <?php if ($mostrar_normas): ?>
                <div class="gc-campo gc-normas-container">
                    <label><?php _e('Normas del grupo', 'flavor-chat-ia'); ?></label>
                    <div class="gc-normas-texto">
                        <?php echo wp_kses_post(wpautop($normas_grupo)); ?>
                    </div>
                    <label class="gc-checkbox-item gc-checkbox-normas">
                        <input type="checkbox" name="acepta_normas" value="1" required>
                        <span>
                            <?php _e('He leido y acepto las normas del grupo de consumo', 'flavor-chat-ia'); ?>
                            <span class="gc-requerido">*</span>
                        </span>
                    </label>
                </div>
                <?php endif; ?>

                <!-- Boton enviar -->
                <div class="gc-campo gc-campo-submit">
                    <button type="submit" class="gc-btn gc-btn-primary gc-btn-lg">
                        <span class="gc-btn-texto"><?php _e('Enviar solicitud', 'flavor-chat-ia'); ?></span>
                        <span class="gc-btn-loading" style="display: none;">
                            <span class="gc-spinner"></span>
                            <?php _e('Enviando...', 'flavor-chat-ia'); ?>
                        </span>
                    </button>
                </div>

                <!-- Mensaje de resultado -->
                <div id="gc-union-resultado" class="gc-mensaje" style="display: none;"></div>
            </form>
        </div>

        <style>
        .gc-formulario-union-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .gc-formulario-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .gc-formulario-header h3 {
            margin: 0 0 10px;
            color: #1d2327;
        }
        .gc-formulario-header p {
            color: #646970;
            margin: 0;
        }
        .gc-campo {
            margin-bottom: 20px;
        }
        .gc-campo label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1d2327;
        }
        .gc-campo-descripcion {
            font-size: 13px;
            color: #646970;
            margin: 0 0 10px;
        }
        .gc-requerido {
            color: #d63638;
        }
        .gc-campo input[type="text"],
        .gc-campo input[type="email"],
        .gc-campo textarea,
        .gc-campo select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .gc-campo input:focus,
        .gc-campo textarea:focus,
        .gc-campo select:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        .gc-checkboxes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
        }
        .gc-checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.2s;
            font-weight: normal;
        }
        .gc-checkbox-item:hover {
            background: #f9f9f9;
        }
        .gc-checkbox-item input:checked + span {
            color: #4CAF50;
            font-weight: 500;
        }
        .gc-checkbox-item input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        .gc-normas-container {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .gc-normas-texto {
            background: #fff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            max-height: 200px;
            overflow-y: auto;
            font-size: 14px;
            line-height: 1.6;
        }
        .gc-checkbox-normas {
            margin-bottom: 0 !important;
            background: #fff;
        }
        .gc-campo-submit {
            margin-top: 30px;
            text-align: center;
        }
        .gc-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .gc-btn-primary {
            background: #4CAF50;
            color: #fff;
        }
        .gc-btn-primary:hover {
            background: #43a047;
        }
        .gc-btn-outline {
            background: transparent;
            border: 2px solid #4CAF50;
            color: #4CAF50;
        }
        .gc-btn-lg {
            padding: 14px 32px;
            font-size: 17px;
        }
        .gc-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .gc-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: gc-spin 0.8s linear infinite;
        }
        @keyframes gc-spin {
            to { transform: rotate(360deg); }
        }
        .gc-mensaje {
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .gc-mensaje-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .gc-mensaje-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .gc-union-container {
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 0 auto;
        }
        .gc-union-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .gc-union-icon .dashicons {
            font-size: 64px;
            width: 64px;
            height: 64px;
        }
        .gc-icon-success { color: #4CAF50; }
        .gc-icon-warning { color: #ff9800; }
        .gc-icon-info { color: #2196F3; }
        .gc-union-acciones {
            margin-top: 25px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('#gc-formulario-union').on('submit', function(e) {
                e.preventDefault();

                var $form = $(this);
                var $btn = $form.find('button[type="submit"]');
                var $resultado = $('#gc-union-resultado');

                // Deshabilitar boton
                $btn.prop('disabled', true);
                $btn.find('.gc-btn-texto').hide();
                $btn.find('.gc-btn-loading').show();
                $resultado.hide();

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: $form.serialize() + '&action=gc_solicitar_union',
                    success: function(response) {
                        if (response.success) {
                            $resultado
                                .removeClass('gc-mensaje-error')
                                .addClass('gc-mensaje-success')
                                .html('<strong><?php _e('Solicitud enviada', 'flavor-chat-ia'); ?></strong><br>' + response.data.mensaje)
                                .show();

                            // Ocultar formulario y mostrar mensaje de exito
                            $form.find('.gc-campo').fadeOut();
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $resultado
                                .removeClass('gc-mensaje-success')
                                .addClass('gc-mensaje-error')
                                .html('<strong><?php _e('Error', 'flavor-chat-ia'); ?></strong><br>' + (response.data.error || response.data.mensaje))
                                .show();

                            $btn.prop('disabled', false);
                            $btn.find('.gc-btn-texto').show();
                            $btn.find('.gc-btn-loading').hide();
                        }
                    },
                    error: function() {
                        $resultado
                            .removeClass('gc-mensaje-success')
                            .addClass('gc-mensaje-error')
                            .html('<?php _e('Error de conexion. Por favor, intentalo de nuevo.', 'flavor-chat-ia'); ?>')
                            .show();

                        $btn.prop('disabled', false);
                        $btn.find('.gc-btn-texto').show();
                        $btn.find('.gc-btn-loading').hide();
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    // ========================================
    // AJAX Handlers
    // ========================================

    /**
     * AJAX: Solicitar union
     */
    public function ajax_solicitar_union() {
        // Verificar nonce
        if (!check_ajax_referer('gc_solicitar_union', 'gc_union_nonce', false)) {
            wp_send_json_error(['error' => __('Error de seguridad. Recarga la pagina e intentalo de nuevo.', 'flavor-chat-ia')]);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => __('Debes iniciar sesion.', 'flavor-chat-ia')]);
        }

        // Asegurar que la tabla existe antes de continuar
        $this->crear_tabla_solicitudes();

        $grupo_id = isset($_POST['grupo_id']) ? absint($_POST['grupo_id']) : 0;
        $usuario_id = get_current_user_id();

        if (!$grupo_id) {
            wp_send_json_error(['error' => __('Debes seleccionar un grupo de consumo.', 'flavor-chat-ia')]);
        }

        $datos = [
            'motivacion' => isset($_POST['motivacion']) ? sanitize_textarea_field($_POST['motivacion']) : '',
            'preferencias_alimentarias' => isset($_POST['preferencias_alimentarias']) ? array_map('sanitize_text_field', (array)$_POST['preferencias_alimentarias']) : [],
            'alergias' => isset($_POST['alergias']) ? sanitize_textarea_field($_POST['alergias']) : '',
            'como_nos_conocio' => isset($_POST['como_nos_conocio']) ? sanitize_text_field($_POST['como_nos_conocio']) : '',
            'acepta_normas' => isset($_POST['acepta_normas']) ? absint($_POST['acepta_normas']) : 0,
        ];

        try {
            $resultado = $this->solicitar_union($grupo_id, $usuario_id, $datos);

            if ($resultado['success']) {
                wp_send_json_success($resultado);
            } else {
                wp_send_json_error($resultado);
            }
        } catch (Exception $e) {
            wp_send_json_error(['error' => __('Error interno. Por favor, contacta al administrador.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Aprobar solicitud
     */
    public function ajax_aprobar_solicitud() {
        check_ajax_referer('gc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options') && !current_user_can('gc_gestionar_consumidores')) {
            wp_send_json_error(['error' => __('No tienes permisos para realizar esta accion.', 'flavor-chat-ia')]);
        }

        $solicitud_id = isset($_POST['solicitud_id']) ? absint($_POST['solicitud_id']) : 0;

        if (!$solicitud_id) {
            wp_send_json_error(['error' => __('Solicitud no especificada.', 'flavor-chat-ia')]);
        }

        $resultado = $this->aprobar_solicitud($solicitud_id);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Rechazar solicitud
     */
    public function ajax_rechazar_solicitud() {
        check_ajax_referer('gc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options') && !current_user_can('gc_gestionar_consumidores')) {
            wp_send_json_error(['error' => __('No tienes permisos para realizar esta accion.', 'flavor-chat-ia')]);
        }

        $solicitud_id = isset($_POST['solicitud_id']) ? absint($_POST['solicitud_id']) : 0;
        $motivo = isset($_POST['motivo']) ? sanitize_textarea_field($_POST['motivo']) : '';

        if (!$solicitud_id) {
            wp_send_json_error(['error' => __('Solicitud no especificada.', 'flavor-chat-ia')]);
        }

        $resultado = $this->rechazar_solicitud($solicitud_id, $motivo);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Obtener detalles de solicitud
     */
    public function ajax_obtener_solicitud() {
        check_ajax_referer('gc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options') && !current_user_can('gc_gestionar_consumidores')) {
            wp_send_json_error(['error' => __('No tienes permisos.', 'flavor-chat-ia')]);
        }

        $solicitud_id = isset($_POST['solicitud_id']) ? absint($_POST['solicitud_id']) : 0;

        $solicitud = $this->obtener_solicitud($solicitud_id);

        if (!$solicitud) {
            wp_send_json_error(['error' => __('Solicitud no encontrada.', 'flavor-chat-ia')]);
        }

        // Decodificar preferencias
        $preferencias = [];
        if ($solicitud->preferencias_alimentarias) {
            $prefs_array = json_decode($solicitud->preferencias_alimentarias, true);
            if (is_array($prefs_array)) {
                foreach ($prefs_array as $pref) {
                    $preferencias[] = self::PREFERENCIAS_ALIMENTARIAS[$pref] ?? $pref;
                }
            }
        }

        wp_send_json_success([
            'solicitud' => [
                'id' => $solicitud->id,
                'usuario_nombre' => $solicitud->display_name,
                'usuario_email' => $solicitud->user_email,
                'grupo_nombre' => $solicitud->grupo_nombre,
                'motivacion' => $solicitud->motivacion,
                'preferencias' => $preferencias,
                'alergias' => $solicitud->alergias,
                'como_nos_conocio' => self::COMO_NOS_CONOCISTE[$solicitud->como_nos_conocio] ?? $solicitud->como_nos_conocio,
                'fecha_solicitud' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($solicitud->fecha_solicitud)),
                'estado' => $solicitud->estado,
                'motivo_rechazo' => $solicitud->motivo_rechazo,
                'resuelto_por_nombre' => $solicitud->resuelto_por_nombre,
                'fecha_resolucion' => $solicitud->fecha_resolucion ? date_i18n(get_option('date_format'), strtotime($solicitud->fecha_resolucion)) : null,
            ],
        ]);
    }

    /**
     * Callback cuando cambia el estado de un consumidor
     */
    public function on_estado_cambiado($consumidor_id, $nuevo_estado, $estado_anterior) {
        // Si se aprueba desde el manager de consumidores
        if ($estado_anterior === 'pendiente' && $nuevo_estado === 'activo') {
            global $wpdb;
            $consumidor = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->tabla_consumidores} WHERE id = %d",
                $consumidor_id
            ));
            if ($consumidor) {
                $this->notificar_usuario_aprobado($consumidor->usuario_id, $consumidor->grupo_id);
            }
        }
    }

    /**
     * Obtener numero de solicitudes pendientes para badge
     *
     * @return int
     */
    public function obtener_badge_solicitudes() {
        return $this->contar_solicitudes_pendientes();
    }

    /**
     * Shortcode para mostrar lista de grupos de consumo disponibles
     * Si se pasa ?grupo=ID en la URL, muestra el formulario de unión
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del listado o formulario
     */
    public function shortcode_grupos_lista($atts) {
        $atts = shortcode_atts([
            'mostrar_red' => true,  // Mostrar grupos de la red de nodos
            'grupo' => 0,           // ID del grupo para mostrar formulario directamente
        ], $atts);

        // Convertir string a boolean
        if (is_string($atts['mostrar_red'])) {
            $atts['mostrar_red'] = filter_var($atts['mostrar_red'], FILTER_VALIDATE_BOOLEAN);
        }

        // Verificar si se pide formulario de un grupo específico
        $grupo_id_url = isset($_GET['grupo']) ? absint($_GET['grupo']) : 0;
        $grupo_id = $grupo_id_url ?: absint($atts['grupo']);

        // Si hay un grupo_id, mostrar formulario de unión
        if ($grupo_id) {
            return $this->shortcode_formulario_union(['grupo_id' => $grupo_id]);
        }

        $usuario_id = get_current_user_id();
        $grupos_locales = $this->obtener_grupos_disponibles();
        $membresias_usuario = $this->obtener_membresias_usuario($usuario_id);
        $solicitudes_pendientes = $this->obtener_solicitudes_usuario($usuario_id);

        ob_start();
        ?>
        <div class="gc-grupos-lista">
            <div class="gc-grupos-header">
                <h3><?php esc_html_e('Grupos de Consumo Disponibles', 'flavor-chat-ia'); ?></h3>
                <p class="gc-grupos-descripcion">
                    <?php esc_html_e('Únete a un grupo de consumo para acceder a productos locales y ecológicos a precios justos.', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <?php if (empty($grupos_locales)): ?>
                <div class="gc-grupos-vacio">
                    <span class="dashicons dashicons-info"></span>
                    <p><?php esc_html_e('No hay grupos de consumo disponibles en este momento.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="gc-grupos-grid">
                    <?php foreach ($grupos_locales as $grupo):
                        $grupo_id = $grupo->ID;
                        $es_grupo_virtual = (isset($grupo->post_type) && $grupo->post_type === 'gc_grupo_virtual');
                        $es_miembro = isset($membresias_usuario[$grupo_id]);
                        $tiene_solicitud_pendiente = isset($solicitudes_pendientes[$grupo_id]);

                        // Imagen: para grupos virtuales usar logo del sitio o imagen por defecto
                        if ($es_grupo_virtual) {
                            $custom_logo_id = get_theme_mod('custom_logo');
                            $imagen_url = $custom_logo_id ? wp_get_attachment_image_url($custom_logo_id, 'medium') : '';
                        } else {
                            $imagen_url = get_the_post_thumbnail_url($grupo_id, 'medium');
                        }
                        if (!$imagen_url) {
                            $imagen_url = FLAVOR_CHAT_IA_URL . 'assets/images/default-grupo.png';
                        }

                        $descripcion = $es_grupo_virtual
                            ? $grupo->post_excerpt
                            : (get_post_meta($grupo_id, '_gc_descripcion', true) ?: $grupo->post_excerpt);
                        $descripcion = $descripcion ?: __('Grupo de consumo local', 'flavor-chat-ia');

                        $ubicacion = $es_grupo_virtual ? '' : get_post_meta($grupo_id, '_gc_ubicacion', true);
                        $total_miembros = $this->contar_miembros_grupo($grupo_id);
                    ?>
                        <div class="gc-grupo-card <?php echo $es_miembro ? 'es-miembro' : ''; ?>">
                            <div class="gc-grupo-imagen">
                                <img src="<?php echo esc_url($imagen_url); ?>" alt="<?php echo esc_attr($grupo->post_title); ?>">
                                <?php if ($es_miembro): ?>
                                    <span class="gc-badge-miembro"><?php esc_html_e('Eres miembro', 'flavor-chat-ia'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="gc-grupo-contenido">
                                <h4 class="gc-grupo-nombre"><?php echo esc_html($grupo->post_title); ?></h4>
                                <?php if ($ubicacion): ?>
                                    <p class="gc-grupo-ubicacion">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php echo esc_html($ubicacion); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="gc-grupo-descripcion"><?php echo esc_html(wp_trim_words($descripcion, 20)); ?></p>
                                <div class="gc-grupo-stats">
                                    <span class="gc-grupo-miembros">
                                        <span class="dashicons dashicons-groups"></span>
                                        <?php printf(esc_html__('%d miembros', 'flavor-chat-ia'), $total_miembros); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="gc-grupo-acciones">
                                <?php if ($es_miembro): ?>
                                    <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/')); ?>" class="gc-btn gc-btn-primary">
                                        <?php esc_html_e('Ir al grupo', 'flavor-chat-ia'); ?>
                                    </a>
                                <?php elseif ($tiene_solicitud_pendiente): ?>
                                    <button class="gc-btn gc-btn-disabled" disabled>
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php esc_html_e('Solicitud pendiente', 'flavor-chat-ia'); ?>
                                    </button>
                                <?php elseif ($usuario_id): ?>
                                    <a href="<?php echo esc_url(add_query_arg('grupo', $grupo_id, home_url('/mi-portal/grupos-consumo/unirme/'))); ?>"
                                       class="gc-btn gc-btn-primary gc-btn-unirse"
                                       data-grupo-id="<?php echo esc_attr($grupo_id); ?>">
                                        <span class="dashicons dashicons-plus-alt"></span>
                                        <?php esc_html_e('Solicitar unión', 'flavor-chat-ia'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo esc_url(wp_login_url(home_url('/mi-portal/grupos-consumo/unirme/'))); ?>" class="gc-btn gc-btn-secondary">
                                        <?php esc_html_e('Inicia sesión para unirte', 'flavor-chat-ia'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($atts['mostrar_red']): ?>
                <div class="gc-red-nodos">
                    <div class="gc-red-nodos-header">
                        <h4>
                            <span class="dashicons dashicons-networking"></span>
                            <?php esc_html_e('Buscar en la Red de Nodos', 'flavor-chat-ia'); ?>
                        </h4>
                        <p><?php esc_html_e('Explora grupos de consumo, bancos de tiempo y comunidades de otros nodos de la red.', 'flavor-chat-ia'); ?></p>
                    </div>
                    <?php
                    // Mostrar contenido de la red usando el shortcode de comunidades
                    echo do_shortcode('[flavor_red_comunidades tipos="grupos_consumo,banco_tiempo,comunidades" limite="6" columnas="3" busqueda="true"]');
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .gc-grupos-lista {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .gc-grupos-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .gc-grupos-header h3 {
            margin: 0 0 10px;
            font-size: 1.5em;
            color: var(--gc-gray-900);
            font-family: var(--gc-font-headings);
        }
        .gc-grupos-descripcion {
            color: var(--gc-gray-500);
            margin: 0;
        }
        .gc-grupos-vacio {
            text-align: center;
            padding: 40px;
            background: var(--gc-gray-100);
            border-radius: var(--gc-border-radius);
        }
        .gc-grupos-vacio .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: var(--gc-gray-500);
            margin-bottom: 15px;
        }
        .gc-grupos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }
        .gc-grupo-card {
            background: #fff;
            border: 1px solid var(--gc-gray-300);
            border-radius: var(--gc-border-radius);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .gc-grupo-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--gc-shadow-lg);
        }
        .gc-grupo-card.es-miembro {
            border-color: var(--gc-success);
        }
        .gc-grupo-imagen {
            position: relative;
            height: 180px;
            overflow: hidden;
        }
        .gc-grupo-imagen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .gc-badge-miembro {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--gc-success);
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }
        .gc-grupo-contenido {
            padding: 16px;
        }
        .gc-grupo-nombre {
            margin: 0 0 8px;
            font-size: 1.2em;
            color: var(--gc-gray-900);
            font-family: var(--gc-font-headings);
        }
        .gc-grupo-ubicacion {
            display: flex;
            align-items: center;
            gap: 4px;
            color: var(--gc-gray-500);
            font-size: 0.9em;
            margin: 0 0 8px;
        }
        .gc-grupo-ubicacion .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        .gc-grupo-descripcion {
            color: var(--gc-gray-500);
            font-size: 0.9em;
            margin: 0 0 12px;
            line-height: 1.5;
        }
        .gc-grupo-stats {
            display: flex;
            gap: 16px;
        }
        .gc-grupo-miembros {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.85em;
            color: var(--gc-gray-500);
        }
        .gc-grupo-miembros .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }
        .gc-grupo-acciones {
            padding: 12px 16px;
            border-top: 1px solid var(--gc-gray-200);
        }
        .gc-grupos-lista .gc-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            padding: 10px 16px;
            border: none;
            border-radius: var(--gc-button-radius);
            font-size: 0.95em;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .gc-grupos-lista .gc-btn-primary {
            background: var(--gc-primary);
            color: #fff;
        }
        .gc-grupos-lista .gc-btn-primary:hover {
            background: var(--gc-primary-dark);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: var(--gc-shadow);
        }
        .gc-grupos-lista .gc-btn-secondary {
            background: var(--gc-gray-100);
            color: var(--gc-gray-700);
        }
        .gc-grupos-lista .gc-btn-secondary:hover {
            background: var(--gc-gray-200);
        }
        .gc-grupos-lista .gc-btn-disabled {
            background: var(--gc-gray-100);
            color: var(--gc-gray-500);
            cursor: not-allowed;
        }
        .gc-grupos-lista .gc-btn .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        .gc-red-nodos {
            margin-top: 50px;
            padding: 30px;
            background: linear-gradient(135deg, var(--gc-gray-100) 0%, var(--gc-gray-200) 100%);
            border-radius: var(--gc-border-radius);
            border: 1px solid var(--gc-gray-300);
        }
        .gc-red-nodos-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .gc-red-nodos-header h4 {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 12px;
            font-size: 1.3em;
            color: var(--gc-gray-900);
            font-family: var(--gc-font-headings);
        }
        .gc-red-nodos-header h4 .dashicons {
            color: var(--gc-primary);
        }
        .gc-red-nodos-header p {
            color: var(--gc-gray-500);
            margin: 0;
            max-width: 600px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .gc-grupos-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtener grupos de consumo disponibles
     * Si el CPT gc_grupo no existe, crea un grupo virtual basado en la configuración del sitio
     *
     * @return array Lista de grupos (WP_Post objects o stdClass para grupo virtual)
     */
    private function obtener_grupos_disponibles() {
        // Verificar si el CPT existe
        if (post_type_exists('gc_grupo')) {
            $args = [
                'post_type' => 'gc_grupo',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
            ];

            $grupos = get_posts($args);

            if (!empty($grupos)) {
                return $grupos;
            }
        }

        // Si no hay CPT o no hay grupos, crear un grupo virtual para este sitio
        $grupo_virtual = new stdClass();
        $grupo_virtual->ID = 1; // ID virtual
        $grupo_virtual->post_title = sprintf(__('Grupo de Consumo de %s', 'flavor-chat-ia'), get_bloginfo('name'));
        $grupo_virtual->post_excerpt = __('Únete a nuestro grupo de consumo local para acceder a productos frescos y ecológicos directamente de productores cercanos.', 'flavor-chat-ia');
        $grupo_virtual->post_type = 'gc_grupo_virtual';

        return [$grupo_virtual];
    }

    /**
     * Obtener membresias activas de un usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array Array indexado por grupo_id
     */
    private function obtener_membresias_usuario($usuario_id) {
        if (!$usuario_id) {
            return [];
        }

        global $wpdb;
        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT grupo_id, rol, estado FROM {$this->tabla_consumidores}
             WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ), OBJECT_K);

        $membresias = [];
        foreach ($resultados as $row) {
            $membresias[$row->grupo_id] = $row;
        }

        return $membresias;
    }

    /**
     * Obtener solicitudes pendientes de un usuario
     *
     * @param int $usuario_id ID del usuario
     * @return array Array indexado por grupo_id
     */
    private function obtener_solicitudes_usuario($usuario_id) {
        if (!$usuario_id) {
            return [];
        }

        global $wpdb;
        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT grupo_id, estado, fecha_solicitud FROM {$this->tabla_solicitudes}
             WHERE usuario_id = %d AND estado = 'pendiente'",
            $usuario_id
        ));

        $solicitudes = [];
        foreach ($resultados as $row) {
            $solicitudes[$row->grupo_id] = $row;
        }

        return $solicitudes;
    }

    /**
     * Contar miembros activos de un grupo
     *
     * @param int $grupo_id ID del grupo
     * @return int Total de miembros
     */
    private function contar_miembros_grupo($grupo_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_consumidores}
             WHERE grupo_id = %d AND estado = 'activo'",
            $grupo_id
        ));
    }
}
