<?php
/**
 * Modulo de Chat Interno para Chat IA
 *
 * Sistema completo de mensajeria privada uno a uno entre usuarios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Chat Interno - Mensajeria privada entre usuarios
 */
class Flavor_Chat_Chat_Interno_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'chat_interno';
        $this->name = 'Chat Interno'; // Translation loaded on init
        $this->description = 'Sistema de mensajeria privada uno a uno entre miembros de la comunidad.'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';
        $existe = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $tabla_conversaciones ) );

        return ! empty( $existe );
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Chat Interno no estan creadas. Se crearan automaticamente al activar.', 'flavor-chat-ia');
        }
        
    return '';
    }

/**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

    /**
     * Verifica si una tabla existe en la base de datos
     *
     * @param string $nombre_tabla Nombre completo de la tabla (con prefijo)
     * @return bool
     */
    private function tabla_existe( $nombre_tabla ) {
        global $wpdb;
        $resultado = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $nombre_tabla ) );
        return ! empty( $resultado );
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'ambas',
            'permite_chat_no_contactos' => true,
            'requiere_verificacion_usuario' => false,
            'permite_archivos' => true,
            'permite_notas_voz' => true,
            'permite_videollamadas' => false,
            'max_tamano_archivo_mb' => 25,
            'eliminar_mensajes_antiguos_dias' => 0, // 0 = nunca
            'encriptacion_e2e' => false,
            'notificaciones_push' => true,
            'mensajes_por_pagina' => 50,
            'permite_editar_mensajes' => true,
            'tiempo_edicion_minutos' => 15,
            'permite_eliminar_mensajes' => true,
            'mostrar_estado_conexion' => true,
            'mostrar_typing_indicator' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX handlers
        add_action('wp_ajax_flavor_chat_interno_conversaciones', [$this, 'ajax_obtener_conversaciones']);
        add_action('wp_ajax_flavor_chat_interno_iniciar', [$this, 'ajax_iniciar_conversacion']);
        add_action('wp_ajax_flavor_chat_interno_mensajes', [$this, 'ajax_obtener_mensajes']);
        add_action('wp_ajax_flavor_chat_interno_enviar', [$this, 'ajax_enviar_mensaje']);
        add_action('wp_ajax_flavor_chat_interno_marcar_leido', [$this, 'ajax_marcar_leido']);
        add_action('wp_ajax_flavor_chat_interno_typing', [$this, 'ajax_typing']);
        add_action('wp_ajax_flavor_chat_interno_buscar', [$this, 'ajax_buscar_mensajes']);
        add_action('wp_ajax_flavor_chat_interno_archivar', [$this, 'ajax_archivar_conversacion']);
        add_action('wp_ajax_flavor_chat_interno_silenciar', [$this, 'ajax_silenciar_conversacion']);
        add_action('wp_ajax_flavor_chat_interno_eliminar_msg', [$this, 'ajax_eliminar_mensaje']);
        add_action('wp_ajax_flavor_chat_interno_editar_msg', [$this, 'ajax_editar_mensaje']);
        add_action('wp_ajax_flavor_chat_interno_bloquear', [$this, 'ajax_bloquear_usuario']);
        add_action('wp_ajax_flavor_chat_interno_desbloquear', [$this, 'ajax_desbloquear_usuario']);
        add_action('wp_ajax_flavor_chat_interno_upload', [$this, 'ajax_subir_archivo']);
        add_action('wp_ajax_flavor_chat_interno_usuarios', [$this, 'ajax_buscar_usuarios']);
        add_action('wp_ajax_flavor_chat_interno_poll', [$this, 'ajax_poll_nuevos_mensajes']);
        add_action('wp_ajax_flavor_chat_interno_estado', [$this, 'ajax_actualizar_estado']);
        add_action('wp_ajax_flavor_chat_interno_info_usuario', [$this, 'ajax_info_usuario']);

        // Dashboard integration
        add_filter('flavor_user_dashboard_tabs', [$this, 'add_dashboard_tab']);

        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // WP Cron para limpieza
        add_action('flavor_chat_interno_limpieza', [$this, 'ejecutar_limpieza_mensajes']);
        if (!wp_next_scheduled('flavor_chat_interno_limpieza')) {
            wp_schedule_event(time(), 'daily', 'flavor_chat_interno_limpieza');
        }

        // Actualizar estado online
        add_action('wp_login', [$this, 'marcar_usuario_online'], 10, 2);
        add_action('wp_logout', [$this, 'marcar_usuario_offline']);

        // Registrar en panel de administracion unificado
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';
        $tabla_estados = $wpdb->prefix . 'flavor_chat_estados_usuario';

        // Verificar si falta alguna de las tablas principales
        $existe_conversaciones = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $tabla_conversaciones ) );
        $existe_estados = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $tabla_estados ) );

        if ( ! $existe_conversaciones || ! $existe_estados ) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';
        $tabla_bloqueados = $wpdb->prefix . 'flavor_chat_bloqueados';
        $tabla_estados = $wpdb->prefix . 'flavor_chat_estados_usuario';

        $sql_conversaciones = "CREATE TABLE IF NOT EXISTS $tabla_conversaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tipo enum('individual','soporte') DEFAULT 'individual',
            estado enum('activa','archivada','bloqueada') DEFAULT 'activa',
            ultimo_mensaje_id bigint(20) unsigned DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY estado (estado),
            KEY fecha_actualizacion (fecha_actualizacion)
        ) $charset_collate;";

        $sql_mensajes = "CREATE TABLE IF NOT EXISTS $tabla_mensajes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conversacion_id bigint(20) unsigned NOT NULL,
            remitente_id bigint(20) unsigned NOT NULL,
            mensaje text NOT NULL,
            mensaje_html text DEFAULT NULL,
            tipo enum('texto','imagen','archivo','audio','ubicacion','sistema') DEFAULT 'texto',
            adjunto_url varchar(500) DEFAULT NULL,
            adjunto_nombre varchar(255) DEFAULT NULL,
            adjunto_tamano bigint(20) DEFAULT NULL,
            adjunto_tipo varchar(100) DEFAULT NULL,
            responde_a bigint(20) unsigned DEFAULT NULL,
            leido tinyint(1) DEFAULT 0,
            fecha_lectura datetime DEFAULT NULL,
            editado tinyint(1) DEFAULT 0,
            fecha_edicion datetime DEFAULT NULL,
            eliminado tinyint(1) DEFAULT 0,
            eliminado_para text DEFAULT NULL COMMENT 'JSON de user_ids',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversacion_id (conversacion_id),
            KEY remitente_id (remitente_id),
            KEY fecha_creacion (fecha_creacion),
            KEY leido (leido),
            FULLTEXT KEY mensaje (mensaje)
        ) $charset_collate;";

        $sql_participantes = "CREATE TABLE IF NOT EXISTS $tabla_participantes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conversacion_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            rol enum('participante','soporte','admin') DEFAULT 'participante',
            silenciado tinyint(1) DEFAULT 0,
            archivado tinyint(1) DEFAULT 0,
            ultimo_mensaje_leido bigint(20) DEFAULT 0,
            escribiendo tinyint(1) DEFAULT 0,
            escribiendo_timestamp datetime DEFAULT NULL,
            notificaciones enum('todas','menciones','ninguna') DEFAULT 'todas',
            fecha_ingreso datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY conversacion_usuario (conversacion_id, usuario_id),
            KEY usuario_id (usuario_id),
            KEY archivado (archivado)
        ) $charset_collate;";

        $sql_bloqueados = "CREATE TABLE IF NOT EXISTS $tabla_bloqueados (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            bloqueado_id bigint(20) unsigned NOT NULL,
            motivo varchar(255) DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_bloqueado (usuario_id, bloqueado_id),
            KEY bloqueado_id (bloqueado_id)
        ) $charset_collate;";

        $sql_estados = "CREATE TABLE IF NOT EXISTS $tabla_estados (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            estado enum('online','ausente','ocupado','offline') DEFAULT 'offline',
            mensaje_estado varchar(255) DEFAULT NULL,
            ultima_actividad datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_conversaciones);
        dbDelta($sql_mensajes);
        dbDelta($sql_participantes);
        dbDelta($sql_bloqueados);
        dbDelta($sql_estados);
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('flavor_chat_inbox', [$this, 'shortcode_inbox']);
        add_shortcode('flavor_chat_conversacion', [$this, 'shortcode_conversacion']);
        add_shortcode('flavor_iniciar_chat', [$this, 'shortcode_iniciar_chat']);
    }

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/chat-interno', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_conversaciones'],
            'permission_callback' => [$this, 'rest_check_permission'],
        ]);

        register_rest_route('flavor/v1', '/chat-interno/conversacion/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_conversacion'],
            'permission_callback' => [$this, 'rest_check_permission'],
        ]);

        register_rest_route('flavor/v1', '/chat-interno/conversacion/(?P<id>\d+)/mensajes', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_mensajes'],
            'permission_callback' => [$this, 'rest_check_permission'],
        ]);

        register_rest_route('flavor/v1', '/chat-interno/conversacion/(?P<id>\d+)/enviar', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_enviar_mensaje'],
            'permission_callback' => [$this, 'rest_check_permission'],
        ]);

        register_rest_route('flavor/v1', '/chat-interno/iniciar', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_iniciar_conversacion'],
            'permission_callback' => [$this, 'rest_check_permission'],
        ]);

        register_rest_route('flavor/v1', '/chat-interno/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_estadisticas'],
            'permission_callback' => [$this, 'rest_check_admin_permission'],
        ]);
    }

    /**
     * Verificar permisos REST
     */
    public function rest_check_permission() {
        return is_user_logged_in();
    }

    /**
     * Verificar permisos admin REST
     */
    public function rest_check_admin_permission() {
        return current_user_can('manage_options');
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        wp_enqueue_style(
            'flavor-chat-interno',
            FLAVOR_CHAT_IA_URL . 'includes/modules/chat-interno/assets/css/chat-interno.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-chat-interno',
            FLAVOR_CHAT_IA_URL . 'includes/modules/chat-interno/assets/js/chat-interno.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-chat-interno', 'flavorChatInterno', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'resturl' => rest_url('flavor/v1/chat-interno/'),
            'nonce' => wp_create_nonce('flavor_chat_interno'),
            'user_id' => get_current_user_id(),
            'user_name' => wp_get_current_user()->display_name,
            'user_avatar' => get_avatar_url(get_current_user_id(), ['size' => 48]),
            'polling_interval' => 3000,
            'typing_timeout' => 3000,
            'max_file_size' => $this->get_settings()['max_tamano_archivo_mb'] * 1024 * 1024,
            'allowed_types' => $this->get_allowed_file_types(),
            'strings' => [
                'escribiendo' => __('escribiendo...', 'flavor-chat-ia'),
                'tu' => __('Tu', 'flavor-chat-ia'),
                'ahora' => __('ahora', 'flavor-chat-ia'),
                'ayer' => __('ayer', 'flavor-chat-ia'),
                'mensaje_eliminado' => __('Mensaje eliminado', 'flavor-chat-ia'),
                'mensaje_editado' => __('editado', 'flavor-chat-ia'),
                'sin_mensajes' => __('No hay mensajes aun. Inicia la conversacion.', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                'usuario_bloqueado' => __('Has bloqueado a este usuario', 'flavor-chat-ia'),
                'bloqueado_por' => __('Este usuario te ha bloqueado', 'flavor-chat-ia'),
                'online' => __('En linea', 'flavor-chat-ia'),
                'offline' => __('Desconectado', 'flavor-chat-ia'),
                'visto' => __('Visto', 'flavor-chat-ia'),
                'enviado' => __('Enviado', 'flavor-chat-ia'),
                'archivo_grande' => __('El archivo es demasiado grande', 'flavor-chat-ia'),
                'tipo_no_permitido' => __('Tipo de archivo no permitido', 'flavor-chat-ia'),
                'confirmar_eliminar' => __('Eliminar este mensaje?', 'flavor-chat-ia'),
                'confirmar_bloquear' => __('Bloquear a este usuario?', 'flavor-chat-ia'),
                'nuevo_mensaje' => __('Nuevo mensaje', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Obtener tipos de archivo permitidos
     */
    private function get_allowed_file_types() {
        return [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'audio/mpeg',
            'audio/wav',
            'audio/ogg',
            'audio/webm',
        ];
    }

    /**
     * Verifica si debe cargar assets
     */
    private function should_load_assets() {
        global $post;
        if (!$post) return false;

        $shortcodes = ['flavor_chat_inbox', 'flavor_chat_conversacion', 'flavor_iniciar_chat'];
        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'conversaciones' => [
                'description' => 'Listar conversaciones activas',
                'params' => ['incluir_archivadas'],
            ],
            'iniciar_chat' => [
                'description' => 'Iniciar nueva conversacion',
                'params' => ['usuario_id', 'mensaje_inicial'],
            ],
            'mensajes' => [
                'description' => 'Ver mensajes de conversacion',
                'params' => ['conversacion_id', 'desde', 'limite', 'antes_de'],
            ],
            'enviar' => [
                'description' => 'Enviar mensaje',
                'params' => ['conversacion_id', 'mensaje', 'tipo', 'responde_a', 'adjuntos'],
            ],
            'marcar_leido' => [
                'description' => 'Marcar mensajes como leidos',
                'params' => ['conversacion_id', 'hasta_mensaje_id'],
            ],
            'buscar_mensajes' => [
                'description' => 'Buscar en mensajes',
                'params' => ['query', 'conversacion_id'],
            ],
            'archivar' => [
                'description' => 'Archivar/desarchivar conversacion',
                'params' => ['conversacion_id', 'archivar'],
            ],
            'silenciar' => [
                'description' => 'Silenciar/activar notificaciones de conversacion',
                'params' => ['conversacion_id', 'silenciar'],
            ],
            'eliminar_mensaje' => [
                'description' => 'Eliminar mensaje',
                'params' => ['mensaje_id', 'para_todos'],
            ],
            'editar_mensaje' => [
                'description' => 'Editar mensaje propio',
                'params' => ['mensaje_id', 'mensaje'],
            ],
            'bloquear_usuario' => [
                'description' => 'Bloquear usuario',
                'params' => ['usuario_id', 'motivo'],
            ],
            'desbloquear_usuario' => [
                'description' => 'Desbloquear usuario',
                'params' => ['usuario_id'],
            ],
            'typing_indicator' => [
                'description' => 'Enviar indicador de escritura',
                'params' => ['conversacion_id', 'escribiendo'],
            ],
            'enviar_archivo' => [
                'description' => 'Enviar archivo adjunto',
                'params' => ['conversacion_id', 'archivo'],
            ],
            'estadisticas_mensajeria' => [
                'description' => 'Estadisticas generales (admin)',
                'params' => ['periodo'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => "Accion no implementada: {$action_name}",
        ];
    }

    // =========================================================================
    // ACCIONES PRINCIPALES
    // =========================================================================

    /**
     * Accion: Listar conversaciones
     */
    private function action_conversaciones($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';
        $tabla_bloqueados = $wpdb->prefix . 'flavor_chat_bloqueados';

        $incluir_archivadas = !empty($params['incluir_archivadas']);

        $where_archivado = $incluir_archivadas ? '' : 'AND p.archivado = 0';

        $conversaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, p.ultimo_mensaje_leido, p.archivado, p.silenciado,
                    m.mensaje as ultimo_mensaje, m.tipo as ultimo_mensaje_tipo,
                    m.fecha_creacion as fecha_ultimo_mensaje,
                    m.remitente_id as ultimo_remitente_id
             FROM $tabla_conversaciones c
             INNER JOIN $tabla_participantes p ON c.id = p.conversacion_id
             LEFT JOIN $tabla_mensajes m ON c.ultimo_mensaje_id = m.id
             WHERE p.usuario_id = %d
             AND c.estado = 'activa'
             $where_archivado
             ORDER BY c.fecha_actualizacion DESC",
            $usuario_id
        ));

        $resultado = [];
        foreach ($conversaciones as $conversacion) {
            // Obtener el otro participante
            $otro_participante_id = $wpdb->get_var($wpdb->prepare(
                "SELECT usuario_id FROM $tabla_participantes
                 WHERE conversacion_id = %d AND usuario_id != %d
                 LIMIT 1",
                $conversacion->id,
                $usuario_id
            ));

            $otro_usuario = $otro_participante_id ? get_userdata($otro_participante_id) : null;

            // Verificar si esta bloqueado
            $esta_bloqueado = $this->esta_bloqueado($usuario_id, $otro_participante_id);
            $me_bloqueo = $this->esta_bloqueado($otro_participante_id, $usuario_id);

            // Contar no leidos
            $mensajes_no_leidos = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_mensajes
                 WHERE conversacion_id = %d
                 AND id > %d
                 AND remitente_id != %d
                 AND eliminado = 0",
                $conversacion->id,
                $conversacion->ultimo_mensaje_leido,
                $usuario_id
            ));

            // Estado del otro usuario
            $estado_otro_usuario = $this->obtener_estado_usuario($otro_participante_id);

            // Preview del mensaje
            $preview_mensaje = $conversacion->ultimo_mensaje;
            if ($conversacion->ultimo_mensaje_tipo === 'imagen') {
                $preview_mensaje = __('Imagen', 'flavor-chat-ia');
            } elseif ($conversacion->ultimo_mensaje_tipo === 'archivo') {
                $preview_mensaje = __('Archivo', 'flavor-chat-ia');
            } elseif ($conversacion->ultimo_mensaje_tipo === 'audio') {
                $preview_mensaje = __('Mensaje de voz', 'flavor-chat-ia');
            }

            $resultado[] = [
                'id' => (int) $conversacion->id,
                'con_usuario' => [
                    'id' => (int) $otro_participante_id,
                    'nombre' => $otro_usuario ? $otro_usuario->display_name : __('Usuario', 'flavor-chat-ia'),
                    'avatar' => $otro_participante_id ? get_avatar_url($otro_participante_id, ['size' => 96]) : '',
                    'estado' => $estado_otro_usuario,
                ],
                'ultimo_mensaje' => $preview_mensaje ? wp_trim_words($preview_mensaje, 10) : '',
                'ultimo_mensaje_tipo' => $conversacion->ultimo_mensaje_tipo,
                'ultimo_remitente_soy_yo' => (int) $conversacion->ultimo_remitente_id === $usuario_id,
                'fecha' => $conversacion->fecha_ultimo_mensaje
                    ? $this->tiempo_relativo($conversacion->fecha_ultimo_mensaje)
                    : '',
                'fecha_raw' => $conversacion->fecha_ultimo_mensaje,
                'no_leidos' => (int) $mensajes_no_leidos,
                'archivado' => (bool) $conversacion->archivado,
                'silenciado' => (bool) $conversacion->silenciado,
                'bloqueado' => $esta_bloqueado,
                'me_bloqueo' => $me_bloqueo,
            ];
        }

        return [
            'success' => true,
            'conversaciones' => $resultado,
            'total' => count($resultado),
        ];
    }

    /**
     * Accion: Iniciar conversacion
     */
    private function action_iniciar_chat($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        $otro_usuario_id = intval($params['usuario_id'] ?? 0);

        if (!$otro_usuario_id) {
            return ['success' => false, 'error' => __('Este usuario te ha bloqueado.', 'flavor-chat-ia')];
        }

        if ($otro_usuario_id === $usuario_id) {
            return ['success' => false, 'error' => __('flavor_chat_conversaciones', 'flavor-chat-ia')];
        }

        // Verificar que el usuario existe
        $otro_usuario = get_userdata($otro_usuario_id);
        if (!$otro_usuario) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        // Verificar bloqueos
        if ($this->esta_bloqueado($usuario_id, $otro_usuario_id)) {
            return ['success' => false, 'error' => __('individual', 'flavor-chat-ia')];
        }
        if ($this->esta_bloqueado($otro_usuario_id, $usuario_id)) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

        // Buscar conversacion existente
        $conversacion_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT p1.conversacion_id
             FROM $tabla_participantes p1
             INNER JOIN $tabla_participantes p2 ON p1.conversacion_id = p2.conversacion_id
             INNER JOIN $tabla_conversaciones c ON p1.conversacion_id = c.id
             WHERE p1.usuario_id = %d
             AND p2.usuario_id = %d
             AND c.tipo = 'individual'",
            $usuario_id,
            $otro_usuario_id
        ));

        if ($conversacion_existente) {
            // Desarchivar si estaba archivada
            $wpdb->update(
                $tabla_participantes,
                ['archivado' => 0],
                ['conversacion_id' => $conversacion_existente, 'usuario_id' => $usuario_id]
            );

            return [
                'success' => true,
                'conversacion_id' => (int) $conversacion_existente,
                'nueva' => false,
            ];
        }

        // Crear nueva conversacion
        $wpdb->insert($tabla_conversaciones, [
            'tipo' => 'individual',
            'estado' => 'activa',
        ]);

        $conversacion_id = $wpdb->insert_id;

        // Agregar participantes
        $wpdb->insert($tabla_participantes, [
            'conversacion_id' => $conversacion_id,
            'usuario_id' => $usuario_id,
            'rol' => 'participante',
        ]);

        $wpdb->insert($tabla_participantes, [
            'conversacion_id' => $conversacion_id,
            'usuario_id' => $otro_usuario_id,
            'rol' => 'participante',
        ]);

        // Enviar mensaje inicial si se proporciona
        if (!empty($params['mensaje_inicial'])) {
            $this->action_enviar([
                'conversacion_id' => $conversacion_id,
                'mensaje' => $params['mensaje_inicial'],
            ]);
        }

        do_action('flavor_gamificacion_agregar_puntos', $usuario_id, 2, 'iniciar_chat');

        return [
            'success' => true,
            'conversacion_id' => $conversacion_id,
            'nueva' => true,
            'mensaje' => __('limite', 'flavor-chat-ia'),
        ];
    }

    /**
     * Accion: Ver mensajes
     */
    private function action_mensajes($params) {
        $usuario_id = get_current_user_id();
        $conversacion_id = intval($params['conversacion_id'] ?? 0);

        if (!$this->usuario_es_participante($usuario_id, $conversacion_id)) {
            return ['success' => false, 'error' => __(' AND m.id < %d', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';

        $limite = min(100, max(10, intval($params['limite'] ?? 50)));
        $antes_de = intval($params['antes_de'] ?? 0);
        $desde = intval($params['desde'] ?? 0);

        $where = "m.conversacion_id = %d";
        $where_values = [$conversacion_id];

        // No mostrar mensajes eliminados para este usuario
        $where .= " AND (m.eliminado = 0 OR (m.eliminado_para IS NULL OR NOT JSON_CONTAINS(m.eliminado_para, %s)))";
        $where_values[] = json_encode($usuario_id);

        if ($antes_de) {
            $where .= " AND m.id < %d";
            $where_values[] = $antes_de;
        }

        if ($desde) {
            $where .= " AND m.id > %d";
            $where_values[] = $desde;
        }

        $mensajes = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as remitente_nombre
             FROM $tabla_mensajes m
             LEFT JOIN {$wpdb->users} u ON m.remitente_id = u.ID
             WHERE $where
             ORDER BY m.id DESC
             LIMIT %d",
            array_merge($where_values, [$limite])
        ));

        $mensajes = array_reverse($mensajes);
        $resultado = [];

        foreach ($mensajes as $mensaje) {
            $es_mio = (int) $mensaje->remitente_id === $usuario_id;

            // Obtener mensaje al que responde si existe
            $mensaje_respondido = null;
            if ($mensaje->responde_a) {
                $mensaje_respondido = $wpdb->get_row($wpdb->prepare(
                    "SELECT m.mensaje, m.tipo, u.display_name as autor
                     FROM $tabla_mensajes m
                     LEFT JOIN {$wpdb->users} u ON m.remitente_id = u.ID
                     WHERE m.id = %d",
                    $mensaje->responde_a
                ));
            }

            $resultado[] = [
                'id' => (int) $mensaje->id,
                'remitente_id' => (int) $mensaje->remitente_id,
                'remitente_nombre' => $mensaje->remitente_nombre ?: __('Usuario', 'flavor-chat-ia'),
                'remitente_avatar' => get_avatar_url($mensaje->remitente_id, ['size' => 48]),
                'mensaje' => $mensaje->eliminado ? '' : $mensaje->mensaje,
                'mensaje_html' => $mensaje->eliminado ? '' : $this->formatear_mensaje($mensaje->mensaje),
                'tipo' => $mensaje->tipo,
                'adjunto' => $mensaje->adjunto_url ? [
                    'url' => $mensaje->adjunto_url,
                    'nombre' => $mensaje->adjunto_nombre,
                    'tamano' => $mensaje->adjunto_tamano,
                    'tipo' => $mensaje->adjunto_tipo,
                    'es_imagen' => strpos($mensaje->adjunto_tipo ?? '', 'image/') === 0,
                    'es_audio' => strpos($mensaje->adjunto_tipo ?? '', 'audio/') === 0,
                ] : null,
                'responde_a' => $mensaje->responde_a ? [
                    'id' => (int) $mensaje->responde_a,
                    'mensaje' => $mensaje_respondido ? wp_trim_words($mensaje_respondido->mensaje, 10) : '',
                    'autor' => $mensaje_respondido ? $mensaje_respondido->autor : '',
                    'tipo' => $mensaje_respondido ? $mensaje_respondido->tipo : 'texto',
                ] : null,
                'leido' => (bool) $mensaje->leido,
                'fecha_lectura' => $mensaje->fecha_lectura,
                'editado' => (bool) $mensaje->editado,
                'eliminado' => (bool) $mensaje->eliminado,
                'fecha' => $mensaje->fecha_creacion,
                'fecha_humana' => $this->tiempo_relativo($mensaje->fecha_creacion),
                'hora' => date('H:i', strtotime($mensaje->fecha_creacion)),
                'es_mio' => $es_mio,
            ];
        }

        // Marcar como leidos los mensajes que no son mios
        if ($usuario_id && !empty($resultado)) {
            $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';
            $ultimo_id = end($resultado)['id'];

            $wpdb->update(
                $tabla_participantes,
                ['ultimo_mensaje_leido' => $ultimo_id],
                ['conversacion_id' => $conversacion_id, 'usuario_id' => $usuario_id]
            );

            // Marcar mensajes del otro usuario como leidos
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_mensajes
                 SET leido = 1, fecha_lectura = NOW()
                 WHERE conversacion_id = %d
                 AND remitente_id != %d
                 AND leido = 0",
                $conversacion_id,
                $usuario_id
            ));
        }

        // Obtener info del otro participante
        $otro_participante = $this->obtener_otro_participante($usuario_id, $conversacion_id);

        return [
            'success' => true,
            'mensajes' => $resultado,
            'hay_mas' => count($mensajes) === $limite,
            'conversacion' => [
                'id' => $conversacion_id,
                'con_usuario' => $otro_participante,
                'escribiendo' => $this->usuario_escribiendo($conversacion_id, $otro_participante['id']),
            ],
        ];
    }

    /**
     * Accion: Enviar mensaje
     */
    private function action_enviar($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        $conversacion_id = intval($params['conversacion_id'] ?? 0);

        if (!$this->usuario_es_participante($usuario_id, $conversacion_id)) {
            return ['success' => false, 'error' => __('El mensaje no puede estar vacio.', 'flavor-chat-ia')];
        }

        // Verificar bloqueos
        $otro_participante = $this->obtener_otro_participante($usuario_id, $conversacion_id);
        if ($this->esta_bloqueado($usuario_id, $otro_participante['id'])) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }
        if ($this->esta_bloqueado($otro_participante['id'], $usuario_id)) {
            return ['success' => false, 'error' => __('flavor_chat_conversaciones', 'flavor-chat-ia')];
        }

        $mensaje = trim($params['mensaje'] ?? '');
        $tipo = in_array($params['tipo'] ?? '', ['texto', 'imagen', 'archivo', 'audio', 'ubicacion'])
            ? $params['tipo']
            : 'texto';

        if (empty($mensaje) && empty($params['adjuntos']) && $tipo === 'texto') {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        if (strlen($mensaje) > 5000) {
            return ['success' => false, 'error' => __('adjuntos', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

        $datos_mensaje = [
            'conversacion_id' => $conversacion_id,
            'remitente_id' => $usuario_id,
            'mensaje' => sanitize_textarea_field($mensaje),
            'mensaje_html' => $this->formatear_mensaje($mensaje),
            'tipo' => $tipo,
        ];

        // Adjuntos
        if (!empty($params['adjuntos'])) {
            $adjunto = $params['adjuntos'];
            $datos_mensaje['adjunto_url'] = esc_url($adjunto['url'] ?? '');
            $datos_mensaje['adjunto_nombre'] = sanitize_file_name($adjunto['nombre'] ?? '');
            $datos_mensaje['adjunto_tamano'] = intval($adjunto['tamano'] ?? 0);
            $datos_mensaje['adjunto_tipo'] = sanitize_text_field($adjunto['tipo'] ?? '');
        }

        // Responde a
        if (!empty($params['responde_a'])) {
            $datos_mensaje['responde_a'] = intval($params['responde_a']);
        }

        $wpdb->insert($tabla_mensajes, $datos_mensaje);
        $mensaje_id = $wpdb->insert_id;

        // Actualizar conversacion
        $wpdb->update(
            $tabla_conversaciones,
            [
                'ultimo_mensaje_id' => $mensaje_id,
                'fecha_actualizacion' => current_time('mysql'),
            ],
            ['id' => $conversacion_id]
        );

        // Actualizar ultimo leido del remitente
        $wpdb->update(
            $tabla_participantes,
            ['ultimo_mensaje_leido' => $mensaje_id, 'escribiendo' => 0],
            ['conversacion_id' => $conversacion_id, 'usuario_id' => $usuario_id]
        );

        // Desarchivar para ambos
        $wpdb->update(
            $tabla_participantes,
            ['archivado' => 0],
            ['conversacion_id' => $conversacion_id]
        );

        // Notificar al otro participante
        $this->enviar_notificacion_mensaje($conversacion_id, $mensaje_id, $otro_participante['id']);

        // Puntos por mensaje
        do_action('flavor_gamificacion_agregar_puntos', $usuario_id, 1, 'mensaje_chat_interno');

        // Actualizar estado online
        $this->actualizar_actividad($usuario_id);

        return [
            'success' => true,
            'mensaje_id' => $mensaje_id,
            'mensaje' => [
                'id' => $mensaje_id,
                'remitente_id' => $usuario_id,
                'remitente_nombre' => wp_get_current_user()->display_name,
                'remitente_avatar' => get_avatar_url($usuario_id, ['size' => 48]),
                'mensaje' => $mensaje,
                'mensaje_html' => $this->formatear_mensaje($mensaje),
                'tipo' => $tipo,
                'adjunto' => !empty($params['adjuntos']) ? $params['adjuntos'] : null,
                'responde_a' => $params['responde_a'] ?? null,
                'leido' => false,
                'editado' => false,
                'eliminado' => false,
                'fecha' => current_time('mysql'),
                'fecha_humana' => __('ahora', 'flavor-chat-ia'),
                'hora' => date('H:i'),
                'es_mio' => true,
            ],
        ];
    }

    /**
     * Accion: Marcar como leido
     */
    private function action_marcar_leido($params) {
        $usuario_id = get_current_user_id();
        $conversacion_id = intval($params['conversacion_id'] ?? 0);

        if (!$this->usuario_es_participante($usuario_id, $conversacion_id)) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

        $hasta_mensaje_id = intval($params['hasta_mensaje_id'] ?? 0);

        if (!$hasta_mensaje_id) {
            // Obtener ultimo mensaje de la conversacion
            $hasta_mensaje_id = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(id) FROM $tabla_mensajes WHERE conversacion_id = %d",
                $conversacion_id
            ));
        }

        if ($hasta_mensaje_id) {
            $wpdb->update(
                $tabla_participantes,
                ['ultimo_mensaje_leido' => $hasta_mensaje_id],
                ['conversacion_id' => $conversacion_id, 'usuario_id' => $usuario_id]
            );

            // Marcar mensajes como leidos
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_mensajes
                 SET leido = 1, fecha_lectura = NOW()
                 WHERE conversacion_id = %d
                 AND id <= %d
                 AND remitente_id != %d
                 AND leido = 0",
                $conversacion_id,
                $hasta_mensaje_id,
                $usuario_id
            ));
        }

        return ['success' => true];
    }

    /**
     * Accion: Buscar mensajes
     */
    private function action_buscar_mensajes($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        $query = trim($params['query'] ?? '');

        if (strlen($query) < 2) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';

        $conversacion_id = intval($params['conversacion_id'] ?? 0);

        $where = "p.usuario_id = %d AND m.eliminado = 0";
        $where_values = [$usuario_id];

        if ($conversacion_id) {
            $where .= " AND m.conversacion_id = %d";
            $where_values[] = $conversacion_id;
        }

        $busqueda = '%' . $wpdb->esc_like($query) . '%';
        $where .= " AND m.mensaje LIKE %s";
        $where_values[] = $busqueda;

        $mensajes = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as remitente_nombre, c.id as conv_id
             FROM $tabla_mensajes m
             INNER JOIN $tabla_participantes p ON m.conversacion_id = p.conversacion_id
             INNER JOIN $tabla_conversaciones c ON m.conversacion_id = c.id
             LEFT JOIN {$wpdb->users} u ON m.remitente_id = u.ID
             WHERE $where
             ORDER BY m.fecha_creacion DESC
             LIMIT 50",
            $where_values
        ));

        $resultado = [];
        foreach ($mensajes as $mensaje) {
            $otro_participante = $this->obtener_otro_participante($usuario_id, $mensaje->conversacion_id);

            $resultado[] = [
                'id' => (int) $mensaje->id,
                'conversacion_id' => (int) $mensaje->conversacion_id,
                'con_usuario' => $otro_participante,
                'mensaje' => $mensaje->mensaje,
                'remitente_nombre' => $mensaje->remitente_nombre,
                'fecha' => $this->tiempo_relativo($mensaje->fecha_creacion),
                'es_mio' => (int) $mensaje->remitente_id === $usuario_id,
            ];
        }

        return [
            'success' => true,
            'resultados' => $resultado,
            'total' => count($resultado),
        ];
    }

    /**
     * Accion: Archivar conversacion
     */
    private function action_archivar($params) {
        $usuario_id = get_current_user_id();
        $conversacion_id = intval($params['conversacion_id'] ?? 0);

        if (!$this->usuario_es_participante($usuario_id, $conversacion_id)) {
            return ['success' => false, 'error' => __('Conversacion archivada.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

        $archivar = isset($params['archivar']) ? (bool) $params['archivar'] : true;

        $wpdb->update(
            $tabla_participantes,
            ['archivado' => $archivar ? 1 : 0],
            ['conversacion_id' => $conversacion_id, 'usuario_id' => $usuario_id]
        );

        return [
            'success' => true,
            'archivado' => $archivar,
            'mensaje' => $archivar
                ? __('Conversacion archivada.', 'flavor-chat-ia')
                : __('Conversacion desarchivada.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Accion: Silenciar conversacion
     */
    private function action_silenciar($params) {
        $usuario_id = get_current_user_id();
        $conversacion_id = intval($params['conversacion_id'] ?? 0);

        if (!$this->usuario_es_participante($usuario_id, $conversacion_id)) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

        $silenciar = isset($params['silenciar']) ? (bool) $params['silenciar'] : true;

        $wpdb->update(
            $tabla_participantes,
            ['silenciado' => $silenciar ? 1 : 0],
            ['conversacion_id' => $conversacion_id, 'usuario_id' => $usuario_id]
        );

        return [
            'success' => true,
            'silenciado' => $silenciar,
            'mensaje' => $silenciar
                ? __('Conversacion silenciada.', 'flavor-chat-ia')
                : __('Notificaciones reactivadas.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Accion: Eliminar mensaje
     */
    private function action_eliminar_mensaje($params) {
        $usuario_id = get_current_user_id();
        $mensaje_id = intval($params['mensaje_id'] ?? 0);

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';

        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_mensajes WHERE id = %d",
            $mensaje_id
        ));

        if (!$mensaje) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        if (!$this->usuario_es_participante($usuario_id, $mensaje->conversacion_id)) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        $settings = $this->get_settings();
        if (!$settings['permite_eliminar_mensajes']) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        $para_todos = !empty($params['para_todos']) && (int) $mensaje->remitente_id === $usuario_id;

        if ($para_todos) {
            // Eliminar para todos
            $wpdb->update(
                $tabla_mensajes,
                ['eliminado' => 1, 'mensaje' => ''],
                ['id' => $mensaje_id]
            );
        } else {
            // Eliminar solo para mi
            $eliminado_para = $mensaje->eliminado_para ? json_decode($mensaje->eliminado_para, true) : [];
            $eliminado_para[] = $usuario_id;

            $wpdb->update(
                $tabla_mensajes,
                ['eliminado_para' => json_encode($eliminado_para)],
                ['id' => $mensaje_id]
            );
        }

        return [
            'success' => true,
            'mensaje' => __('Mensaje eliminado.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Accion: Editar mensaje
     */
    private function action_editar_mensaje($params) {
        $usuario_id = get_current_user_id();
        $mensaje_id = intval($params['mensaje_id'] ?? 0);
        $nuevo_mensaje = trim($params['mensaje'] ?? '');

        if (empty($nuevo_mensaje)) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';

        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_mensajes WHERE id = %d",
            $mensaje_id
        ));

        if (!$mensaje) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        if ((int) $mensaje->remitente_id !== $usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        $settings = $this->get_settings();
        if (!$settings['permite_editar_mensajes']) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        // Verificar tiempo limite
        $tiempo_limite = $settings['tiempo_edicion_minutos'] * 60;
        $tiempo_transcurrido = time() - strtotime($mensaje->fecha_creacion);

        if ($tiempo_transcurrido > $tiempo_limite) {
            return [
                'success' => false,
                'error' => sprintf(
                    __('Solo puedes editar mensajes en los primeros %d minutos.', 'flavor-chat-ia'),
                    $settings['tiempo_edicion_minutos']
                ),
            ];
        }

        $wpdb->update(
            $tabla_mensajes,
            [
                'mensaje' => sanitize_textarea_field($nuevo_mensaje),
                'mensaje_html' => $this->formatear_mensaje($nuevo_mensaje),
                'editado' => 1,
                'fecha_edicion' => current_time('mysql'),
            ],
            ['id' => $mensaje_id]
        );

        return [
            'success' => true,
            'mensaje' => __('Mensaje editado.', 'flavor-chat-ia'),
            'nuevo_mensaje' => $nuevo_mensaje,
            'mensaje_html' => $this->formatear_mensaje($nuevo_mensaje),
        ];
    }

    /**
     * Accion: Bloquear usuario
     */
    private function action_bloquear_usuario($params) {
        $usuario_id = get_current_user_id();
        $bloqueado_id = intval($params['usuario_id'] ?? 0);

        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        if (!$bloqueado_id || $bloqueado_id === $usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        if ($this->esta_bloqueado($usuario_id, $bloqueado_id)) {
            return ['success' => false, 'error' => __('escribiendo', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_bloqueados = $wpdb->prefix . 'flavor_chat_bloqueados';

        $wpdb->insert($tabla_bloqueados, [
            'usuario_id' => $usuario_id,
            'bloqueado_id' => $bloqueado_id,
            'motivo' => sanitize_text_field($params['motivo'] ?? ''),
        ]);

        return [
            'success' => true,
            'mensaje' => __('Usuario bloqueado.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Accion: Desbloquear usuario
     */
    private function action_desbloquear_usuario($params) {
        $usuario_id = get_current_user_id();
        $bloqueado_id = intval($params['usuario_id'] ?? 0);

        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Sin permisos.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_bloqueados = $wpdb->prefix . 'flavor_chat_bloqueados';

        $wpdb->delete($tabla_bloqueados, [
            'usuario_id' => $usuario_id,
            'bloqueado_id' => $bloqueado_id,
        ]);

        return [
            'success' => true,
            'mensaje' => __('Usuario desbloqueado.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Accion: Typing indicator
     */
    private function action_typing_indicator($params) {
        $usuario_id = get_current_user_id();
        $conversacion_id = intval($params['conversacion_id'] ?? 0);
        $escribiendo = !empty($params['escribiendo']);

        if (!$this->usuario_es_participante($usuario_id, $conversacion_id)) {
            return ['success' => false, 'error' => __('SELECT COUNT(*) FROM $tabla_mensajes WHERE eliminado = 0', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

        $wpdb->update(
            $tabla_participantes,
            [
                'escribiendo' => $escribiendo ? 1 : 0,
                'escribiendo_timestamp' => $escribiendo ? current_time('mysql') : null,
            ],
            ['conversacion_id' => $conversacion_id, 'usuario_id' => $usuario_id]
        );

        return ['success' => true];
    }

    /**
     * Accion: Estadisticas de mensajeria (admin)
     */
    private function action_estadisticas_mensajeria($params) {
        if (!current_user_can('manage_options')) {
            return ['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';
        $tabla_bloqueados = $wpdb->prefix . 'flavor_chat_bloqueados';

        $periodo = sanitize_text_field($params['periodo'] ?? '30');
        $fecha_inicio = date('Y-m-d', strtotime("-{$periodo} days"));

        // Total conversaciones
        $total_conversaciones = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_conversaciones"
        );

        // Conversaciones activas en el periodo
        $conversaciones_activas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_conversaciones
             WHERE fecha_actualizacion >= %s",
            $fecha_inicio
        ));

        // Total mensajes
        $total_mensajes = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_mensajes WHERE eliminado = 0"
        );

        // Mensajes en el periodo
        $mensajes_periodo = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_mensajes
             WHERE fecha_creacion >= %s AND eliminado = 0",
            $fecha_inicio
        ));

        // Usuarios activos
        $usuarios_activos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT remitente_id) FROM $tabla_mensajes
             WHERE fecha_creacion >= %s",
            $fecha_inicio
        ));

        // Total usuarios bloqueados
        $total_bloqueados = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_bloqueados"
        );

        // Mensajes por dia
        $mensajes_por_dia = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(fecha_creacion) as fecha, COUNT(*) as total
             FROM $tabla_mensajes
             WHERE fecha_creacion >= %s AND eliminado = 0
             GROUP BY DATE(fecha_creacion)
             ORDER BY fecha ASC",
            $fecha_inicio
        ));

        // Usuarios mas activos
        $usuarios_mas_activos = $wpdb->get_results($wpdb->prepare(
            "SELECT remitente_id, COUNT(*) as total_mensajes, u.display_name
             FROM $tabla_mensajes m
             LEFT JOIN {$wpdb->users} u ON m.remitente_id = u.ID
             WHERE m.fecha_creacion >= %s AND m.eliminado = 0
             GROUP BY remitente_id
             ORDER BY total_mensajes DESC
             LIMIT 10",
            $fecha_inicio
        ));

        return [
            'success' => true,
            'estadisticas' => [
                'periodo_dias' => $periodo,
                'total_conversaciones' => (int) $total_conversaciones,
                'conversaciones_activas' => (int) $conversaciones_activas,
                'total_mensajes' => (int) $total_mensajes,
                'mensajes_periodo' => (int) $mensajes_periodo,
                'usuarios_activos' => (int) $usuarios_activos,
                'total_bloqueados' => (int) $total_bloqueados,
                'promedio_mensajes_dia' => $periodo > 0 ? round($mensajes_periodo / $periodo, 1) : 0,
                'mensajes_por_dia' => $mensajes_por_dia,
                'usuarios_mas_activos' => array_map(function($u) {
                    return [
                        'id' => (int) $u->remitente_id,
                        'nombre' => $u->display_name,
                        'mensajes' => (int) $u->total_mensajes,
                        'avatar' => get_avatar_url($u->remitente_id, ['size' => 48]),
                    ];
                }, $usuarios_mas_activos),
            ],
        ];
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Obtener conversaciones
     */
    public function ajax_obtener_conversaciones() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $resultado = $this->action_conversaciones([
            'incluir_archivadas' => !empty($_GET['archivadas']),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Iniciar conversacion
     */
    public function ajax_iniciar_conversacion() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $resultado = $this->action_iniciar_chat([
            'usuario_id' => intval($_POST['usuario_id'] ?? 0),
            'mensaje_inicial' => sanitize_textarea_field($_POST['mensaje'] ?? ''),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Obtener mensajes
     */
    public function ajax_obtener_mensajes() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $resultado = $this->action_mensajes([
            'conversacion_id' => intval($_GET['conversacion_id'] ?? 0),
            'limite' => intval($_GET['limite'] ?? 50),
            'antes_de' => intval($_GET['antes_de'] ?? 0),
            'desde' => intval($_GET['desde'] ?? 0),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Enviar mensaje
     */
    public function ajax_enviar_mensaje() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $adjuntos = null;
        if (!empty($_POST['adjunto_url'])) {
            $adjuntos = [
                'url' => esc_url($_POST['adjunto_url']),
                'nombre' => sanitize_file_name($_POST['adjunto_nombre'] ?? ''),
                'tamano' => intval($_POST['adjunto_tamano'] ?? 0),
                'tipo' => sanitize_text_field($_POST['adjunto_tipo'] ?? ''),
            ];
        }

        $resultado = $this->action_enviar([
            'conversacion_id' => intval($_POST['conversacion_id'] ?? 0),
            'mensaje' => sanitize_textarea_field($_POST['mensaje'] ?? ''),
            'tipo' => sanitize_text_field($_POST['tipo'] ?? 'texto'),
            'responde_a' => intval($_POST['responde_a'] ?? 0),
            'adjuntos' => $adjuntos,
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Marcar como leido
     */
    public function ajax_marcar_leido() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $resultado = $this->action_marcar_leido([
            'conversacion_id' => intval($_POST['conversacion_id'] ?? 0),
            'hasta_mensaje_id' => intval($_POST['hasta_mensaje_id'] ?? 0),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Typing indicator
     */
    public function ajax_typing() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $resultado = $this->action_typing_indicator([
            'conversacion_id' => intval($_POST['conversacion_id'] ?? 0),
            'escribiendo' => !empty($_POST['escribiendo']),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Buscar mensajes
     */
    public function ajax_buscar_mensajes() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $resultado = $this->action_buscar_mensajes([
            'query' => sanitize_text_field($_GET['query'] ?? ''),
            'conversacion_id' => intval($_GET['conversacion_id'] ?? 0),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Archivar conversacion
     */
    public function ajax_archivar_conversacion() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $resultado = $this->action_archivar([
            'conversacion_id' => intval($_POST['conversacion_id'] ?? 0),
            'archivar' => !empty($_POST['archivar']),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Silenciar conversacion
     */
    public function ajax_silenciar_conversacion() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $resultado = $this->action_silenciar([
            'conversacion_id' => intval($_POST['conversacion_id'] ?? 0),
            'silenciar' => !empty($_POST['silenciar']),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Eliminar mensaje
     */
    public function ajax_eliminar_mensaje() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $resultado = $this->action_eliminar_mensaje([
            'mensaje_id' => intval($_POST['mensaje_id'] ?? 0),
            'para_todos' => !empty($_POST['para_todos']),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Editar mensaje
     */
    public function ajax_editar_mensaje() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $resultado = $this->action_editar_mensaje([
            'mensaje_id' => intval($_POST['mensaje_id'] ?? 0),
            'mensaje' => sanitize_textarea_field($_POST['mensaje'] ?? ''),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Bloquear usuario
     */
    public function ajax_bloquear_usuario() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $resultado = $this->action_bloquear_usuario([
            'usuario_id' => intval($_POST['usuario_id'] ?? 0),
            'motivo' => sanitize_text_field($_POST['motivo'] ?? ''),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Desbloquear usuario
     */
    public function ajax_desbloquear_usuario() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $resultado = $this->action_desbloquear_usuario([
            'usuario_id' => intval($_POST['usuario_id'] ?? 0),
        ]);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Subir archivo
     */
    public function ajax_subir_archivo() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json(['success' => false, 'error' => __('query', 'flavor-chat-ia')]);
        }

        $settings = $this->get_settings();
        if (!$settings['permite_archivos']) {
            wp_send_json(['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')]);
        }

        if (empty($_FILES['file'])) {
            wp_send_json(['success' => false, 'error' => __('search', 'flavor-chat-ia')]);
        }

        $archivo = $_FILES['file'];
        $tamano_maximo = $settings['max_tamano_archivo_mb'] * 1024 * 1024;

        if ($archivo['size'] > $tamano_maximo) {
            wp_send_json([
                'success' => false,
                'error' => sprintf(
                    __('El archivo es demasiado grande. Maximo: %d MB', 'flavor-chat-ia'),
                    $settings['max_tamano_archivo_mb']
                ),
            ]);
        }

        $tipos_permitidos = $this->get_allowed_file_types();
        if (!in_array($archivo['type'], $tipos_permitidos)) {
            wp_send_json(['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')]);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = wp_handle_upload($archivo, ['test_form' => false]);

        if (isset($upload['error'])) {
            wp_send_json(['success' => false, 'error' => $upload['error']]);
        }

        wp_send_json([
            'success' => true,
            'archivo' => [
                'url' => $upload['url'],
                'nombre' => $archivo['name'],
                'tamano' => $archivo['size'],
                'tipo' => $archivo['type'],
                'es_imagen' => strpos($archivo['type'], 'image/') === 0,
                'es_audio' => strpos($archivo['type'], 'audio/') === 0,
            ],
        ]);
    }

    /**
     * AJAX: Buscar usuarios
     */
    public function ajax_buscar_usuarios() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json(['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')]);
        }

        $busqueda = sanitize_text_field($_GET['query'] ?? '');

        if (strlen($busqueda) < 2) {
            wp_send_json(['success' => true, 'usuarios' => []]);
        }

        $usuario_actual = get_current_user_id();

        $usuarios = get_users([
            'search' => '*' . $busqueda . '*',
            'search_columns' => ['user_login', 'user_nicename', 'display_name', 'user_email'],
            'exclude' => [$usuario_actual],
            'number' => 20,
        ]);

        $resultado = [];
        foreach ($usuarios as $usuario) {
            $resultado[] = [
                'id' => $usuario->ID,
                'nombre' => $usuario->display_name,
                'avatar' => get_avatar_url($usuario->ID, ['size' => 48]),
                'estado' => $this->obtener_estado_usuario($usuario->ID),
            ];
        }

        wp_send_json(['success' => true, 'usuarios' => $resultado]);
    }

    /**
     * AJAX: Poll nuevos mensajes
     */
    public function ajax_poll_nuevos_mensajes() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json(['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')]);
        }

        $conversacion_id = intval($_GET['conversacion_id'] ?? 0);
        $ultimo_mensaje_id = intval($_GET['ultimo_mensaje_id'] ?? 0);

        if (!$this->usuario_es_participante($usuario_id, $conversacion_id)) {
            wp_send_json(['success' => false, 'error' => __('mensajes', 'flavor-chat-ia')]);
        }

        // Actualizar actividad
        $this->actualizar_actividad($usuario_id);

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

        // Obtener nuevos mensajes
        $nuevos_mensajes = [];
        if ($ultimo_mensaje_id) {
            $mensajes = $wpdb->get_results($wpdb->prepare(
                "SELECT m.*, u.display_name as remitente_nombre
                 FROM $tabla_mensajes m
                 LEFT JOIN {$wpdb->users} u ON m.remitente_id = u.ID
                 WHERE m.conversacion_id = %d
                 AND m.id > %d
                 AND m.eliminado = 0
                 ORDER BY m.id ASC",
                $conversacion_id,
                $ultimo_mensaje_id
            ));

            foreach ($mensajes as $mensaje) {
                $nuevos_mensajes[] = [
                    'id' => (int) $mensaje->id,
                    'remitente_id' => (int) $mensaje->remitente_id,
                    'remitente_nombre' => $mensaje->remitente_nombre,
                    'remitente_avatar' => get_avatar_url($mensaje->remitente_id, ['size' => 48]),
                    'mensaje' => $mensaje->mensaje,
                    'mensaje_html' => $this->formatear_mensaje($mensaje->mensaje),
                    'tipo' => $mensaje->tipo,
                    'adjunto' => $mensaje->adjunto_url ? [
                        'url' => $mensaje->adjunto_url,
                        'nombre' => $mensaje->adjunto_nombre,
                        'tipo' => $mensaje->adjunto_tipo,
                        'es_imagen' => strpos($mensaje->adjunto_tipo ?? '', 'image/') === 0,
                    ] : null,
                    'fecha' => $mensaje->fecha_creacion,
                    'fecha_humana' => $this->tiempo_relativo($mensaje->fecha_creacion),
                    'hora' => date('H:i', strtotime($mensaje->fecha_creacion)),
                    'es_mio' => (int) $mensaje->remitente_id === $usuario_id,
                ];
            }
        }

        // Verificar typing del otro usuario
        $otro_participante = $this->obtener_otro_participante($usuario_id, $conversacion_id);
        $otro_escribiendo = $this->usuario_escribiendo($conversacion_id, $otro_participante['id']);

        // Verificar mensajes leidos
        $mensajes_leidos = [];
        $ultimo_leido = $wpdb->get_var($wpdb->prepare(
            "SELECT ultimo_mensaje_leido FROM $tabla_participantes
             WHERE conversacion_id = %d AND usuario_id = %d",
            $conversacion_id,
            $otro_participante['id']
        ));

        wp_send_json([
            'success' => true,
            'mensajes' => $nuevos_mensajes,
            'escribiendo' => $otro_escribiendo,
            'ultimo_leido_por_otro' => (int) $ultimo_leido,
            'estado_otro_usuario' => $this->obtener_estado_usuario($otro_participante['id']),
        ]);
    }

    /**
     * AJAX: Actualizar estado
     */
    public function ajax_actualizar_estado() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json(['success' => false]);
        }

        $this->actualizar_actividad($usuario_id);

        wp_send_json(['success' => true]);
    }

    /**
     * AJAX: Info usuario
     */
    public function ajax_info_usuario() {
        check_ajax_referer('flavor_chat_interno', 'nonce');

        $usuario_id = intval($_GET['usuario_id'] ?? 0);
        if (!$usuario_id) {
            wp_send_json(['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')]);
        }

        $usuario = get_userdata($usuario_id);
        if (!$usuario) {
            wp_send_json(['success' => false, 'error' => __('Debes iniciar sesion.', 'flavor-chat-ia')]);
        }

        $yo = get_current_user_id();

        wp_send_json([
            'success' => true,
            'usuario' => [
                'id' => $usuario->ID,
                'nombre' => $usuario->display_name,
                'avatar' => get_avatar_url($usuario->ID, ['size' => 200]),
                'estado' => $this->obtener_estado_usuario($usuario->ID),
                'bloqueado' => $this->esta_bloqueado($yo, $usuario->ID),
                'me_bloqueo' => $this->esta_bloqueado($usuario->ID, $yo),
                'miembro_desde' => date_i18n(get_option('date_format'), strtotime($usuario->user_registered)),
            ],
        ]);
    }

    // =========================================================================
    // REST API HANDLERS
    // =========================================================================

    /**
     * REST: Obtener conversaciones
     */
    public function rest_obtener_conversaciones($request) {
        return rest_ensure_response($this->action_conversaciones([
            'incluir_archivadas' => $request->get_param('archivadas'),
        ]));
    }

    /**
     * REST: Obtener conversacion
     */
    public function rest_obtener_conversacion($request) {
        $conversacion_id = $request->get_param('id');
        $usuario_id = get_current_user_id();

        if (!$this->usuario_es_participante($usuario_id, $conversacion_id)) {
            return new WP_Error('forbidden', 'No tienes acceso a esta conversacion.', ['status' => 403]);
        }

        $otro_participante = $this->obtener_otro_participante($usuario_id, $conversacion_id);

        return rest_ensure_response([
            'success' => true,
            'conversacion' => [
                'id' => (int) $conversacion_id,
                'con_usuario' => $otro_participante,
            ],
        ]);
    }

    /**
     * REST: Obtener mensajes
     */
    public function rest_obtener_mensajes($request) {
        return rest_ensure_response($this->action_mensajes([
            'conversacion_id' => $request->get_param('id'),
            'limite' => $request->get_param('limite') ?? 50,
            'antes_de' => $request->get_param('antes_de'),
            'desde' => $request->get_param('desde'),
        ]));
    }

    /**
     * REST: Enviar mensaje
     */
    public function rest_enviar_mensaje($request) {
        return rest_ensure_response($this->action_enviar([
            'conversacion_id' => $request->get_param('id'),
            'mensaje' => $request->get_param('mensaje'),
            'tipo' => $request->get_param('tipo') ?? 'texto',
            'responde_a' => $request->get_param('responde_a'),
            'adjuntos' => $request->get_param('adjuntos'),
        ]));
    }

    /**
     * REST: Iniciar conversacion
     */
    public function rest_iniciar_conversacion($request) {
        return rest_ensure_response($this->action_iniciar_chat([
            'usuario_id' => $request->get_param('usuario_id'),
            'mensaje_inicial' => $request->get_param('mensaje'),
        ]));
    }

    /**
     * REST: Estadisticas
     */
    public function rest_estadisticas($request) {
        return rest_ensure_response($this->action_estadisticas_mensajeria([
            'periodo' => $request->get_param('periodo') ?? '30',
        ]));
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode: Inbox completo
     */
    public function shortcode_inbox($atts) {
        if (!is_user_logged_in()) {
            return '<div class="ci-login-required">' .
                __('Debes iniciar sesion para ver tus mensajes.', 'flavor-chat-ia') .
                ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Iniciar sesion', 'flavor-chat-ia') . '</a>' .
                '</div>';
        }

        $atts = shortcode_atts([
            'mostrar_archivados' => 'false',
        ], $atts);

        ob_start();
        ?>
        <div id="flavor-chat-interno-app" class="ci-app" data-archivados="<?php echo esc_attr($atts['mostrar_archivados']); ?>">
            <!-- Sidebar con lista de conversaciones -->
            <div class="ci-sidebar">
                <div class="ci-sidebar-header">
                    <h3><?php _e('Mensajes', 'flavor-chat-ia'); ?></h3>
                    <button type="button" class="ci-btn-nuevo" title="<?php esc_attr_e('Nuevo mensaje', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                </div>

                <div class="ci-sidebar-search">
                    <input type="text" id="ci-buscar-conversacion" placeholder="<?php esc_attr_e('Buscar...', 'flavor-chat-ia'); ?>">
                </div>

                <div class="ci-conversaciones-lista" id="ci-conversaciones">
                    <div class="ci-loading">
                        <span class="ci-spinner"></span>
                        <?php _e('Cargando...', 'flavor-chat-ia'); ?>
                    </div>
                </div>

                <div class="ci-sidebar-footer">
                    <a href="javascript:void(0);" class="ci-link-archivados" id="ci-toggle-archivados" onclick="if(typeof ciToggleArchivados==='function')ciToggleArchivados();else alert('<?php echo esc_js(__('Funcionalidad en desarrollo', 'flavor-chat-ia')); ?>');">
                        <span class="dashicons dashicons-archive"></span>
                        <?php _e('Archivados', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>

            <!-- Area principal de chat -->
            <div class="ci-main">
                <div class="ci-no-seleccionado" id="ci-placeholder">
                    <span class="dashicons dashicons-email-alt"></span>
                    <h3><?php _e('Tus mensajes', 'flavor-chat-ia'); ?></h3>
                    <p><?php _e('Selecciona una conversacion o inicia una nueva', 'flavor-chat-ia'); ?></p>
                    <button type="button" class="ci-btn ci-btn-primary ci-btn-nuevo-main">
                        <?php _e('Nuevo mensaje', 'flavor-chat-ia'); ?>
                    </button>
                </div>

                <div class="ci-chat-container" id="ci-chat-container" style="display: none;">
                    <!-- Header de la conversacion -->
                    <div class="ci-chat-header">
                        <button type="button" class="ci-btn-back" title="<?php esc_attr_e('Volver', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-arrow-left-alt2"></span>
                        </button>
                        <div class="ci-usuario-info">
                            <img src="" alt="" class="ci-usuario-avatar" id="ci-chat-avatar">
                            <div class="ci-usuario-datos">
                                <h4 id="ci-chat-nombre"></h4>
                                <span class="ci-usuario-estado" id="ci-chat-estado"></span>
                            </div>
                        </div>
                        <div class="ci-chat-acciones">
                            <button type="button" class="ci-btn-icon ci-btn-buscar" title="<?php esc_attr_e('Buscar', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-search"></span>
                            </button>
                            <button type="button" class="ci-btn-icon ci-btn-info" title="<?php esc_attr_e('Info', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-info-outline"></span>
                            </button>
                            <button type="button" class="ci-btn-icon ci-btn-menu" title="<?php esc_attr_e('Menu', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-ellipsis"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Contenedor de mensajes -->
                    <div class="ci-mensajes-container" id="ci-mensajes">
                    </div>

                    <!-- Typing indicator -->
                    <div class="ci-typing-indicator" id="ci-typing" style="display: none;">
                        <span class="ci-typing-dots">
                            <span></span><span></span><span></span>
                        </span>
                        <span class="ci-typing-text"></span>
                    </div>

                    <!-- Area de respuesta -->
                    <div class="ci-respuesta-preview" id="ci-respuesta-preview" style="display: none;">
                        <div class="ci-respuesta-contenido">
                            <span class="ci-respuesta-label"><?php _e('Respondiendo a:', 'flavor-chat-ia'); ?></span>
                            <span class="ci-respuesta-texto" id="ci-respuesta-texto"></span>
                        </div>
                        <button type="button" class="ci-respuesta-cancelar" id="ci-respuesta-cancelar">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>

                    <!-- Area de input -->
                    <div class="ci-input-container">
                        <div class="ci-input-acciones">
                            <button type="button" class="ci-btn-icon ci-btn-adjuntar" title="<?php esc_attr_e('Adjuntar archivo', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-paperclip"></span>
                            </button>
                            <input type="file" id="ci-file-input" style="display: none;"
                                   accept="image/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx">
                        </div>
                        <div class="ci-input-wrapper">
                            <textarea id="ci-mensaje-input"
                                      placeholder="<?php esc_attr_e('Escribe un mensaje...', 'flavor-chat-ia'); ?>"
                                      rows="1"></textarea>
                        </div>
                        <button type="button" class="ci-btn-enviar" id="ci-btn-enviar" disabled>
                            <span class="dashicons dashicons-arrow-right-alt"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Panel lateral de info -->
            <div class="ci-panel-info" id="ci-panel-info" style="display: none;">
                <div class="ci-panel-header">
                    <h3><?php _e('Informacion', 'flavor-chat-ia'); ?></h3>
                    <button type="button" class="ci-panel-cerrar">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="ci-panel-contenido" id="ci-panel-contenido">
                </div>
            </div>
        </div>

        <!-- Modal nuevo mensaje -->
        <div class="ci-modal" id="ci-modal-nuevo" style="display: none;">
            <div class="ci-modal-overlay"></div>
            <div class="ci-modal-content">
                <div class="ci-modal-header">
                    <h3><?php _e('Nuevo mensaje', 'flavor-chat-ia'); ?></h3>
                    <button type="button" class="ci-modal-cerrar">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="ci-modal-body">
                    <div class="ci-form-group">
                        <label><?php _e('Para:', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="ci-buscar-usuario"
                               placeholder="<?php esc_attr_e('Buscar usuario...', 'flavor-chat-ia'); ?>">
                        <div class="ci-usuarios-resultado" id="ci-usuarios-resultado"></div>
                        <input type="hidden" id="ci-nuevo-usuario-id">
                    </div>
                    <div class="ci-form-group">
                        <label><?php _e('Mensaje:', 'flavor-chat-ia'); ?></label>
                        <textarea id="ci-nuevo-mensaje" rows="4"
                                  placeholder="<?php esc_attr_e('Escribe tu mensaje...', 'flavor-chat-ia'); ?>"></textarea>
                    </div>
                </div>
                <div class="ci-modal-footer">
                    <button type="button" class="ci-btn ci-btn-secondary ci-modal-cancelar">
                        <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="button" class="ci-btn ci-btn-primary" id="ci-btn-enviar-nuevo" disabled>
                        <?php _e('Enviar', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Menu contextual -->
        <div class="ci-dropdown-menu" id="ci-menu-conversacion" style="display: none;">
            <button type="button" class="ci-dropdown-item" data-action="archivar">
                <span class="dashicons dashicons-archive"></span>
                <?php _e('Archivar', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="ci-dropdown-item" data-action="silenciar">
                <span class="dashicons dashicons-controls-volumeoff"></span>
                <?php _e('Silenciar', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="ci-dropdown-item ci-dropdown-danger" data-action="bloquear">
                <span class="dashicons dashicons-dismiss"></span>
                <?php _e('Bloquear usuario', 'flavor-chat-ia'); ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Conversacion individual
     */
    public function shortcode_conversacion($atts) {
        if (!is_user_logged_in()) {
            return '<div class="ci-login-required">' .
                __('Debes iniciar sesion para ver esta conversacion.', 'flavor-chat-ia') .
                '</div>';
        }

        $atts = shortcode_atts([
            'id' => 0,
            'usuario_id' => 0,
        ], $atts);

        $conversacion_id = intval($atts['id']);
        $usuario_id = intval($atts['usuario_id']);

        // Si se proporciona usuario_id, buscar o crear conversacion
        if ($usuario_id && !$conversacion_id) {
            $resultado = $this->action_iniciar_chat(['usuario_id' => $usuario_id]);
            if ($resultado['success']) {
                $conversacion_id = $resultado['conversacion_id'];
            }
        }

        if (!$conversacion_id) {
            return '<div class="ci-error">' . __('Conversacion no encontrada.', 'flavor-chat-ia') . '</div>';
        }

        ob_start();
        ?>
        <div id="flavor-chat-interno-single" class="ci-single-chat" data-conversacion="<?php echo esc_attr($conversacion_id); ?>">
            <div class="ci-chat-container ci-single">
                <div class="ci-chat-header">
                    <div class="ci-usuario-info">
                        <img src="" alt="" class="ci-usuario-avatar" id="ci-chat-avatar">
                        <div class="ci-usuario-datos">
                            <h4 id="ci-chat-nombre"></h4>
                            <span class="ci-usuario-estado" id="ci-chat-estado"></span>
                        </div>
                    </div>
                </div>
                <div class="ci-mensajes-container" id="ci-mensajes"></div>
                <div class="ci-typing-indicator" id="ci-typing" style="display: none;">
                    <span class="ci-typing-dots"><span></span><span></span><span></span></span>
                    <span class="ci-typing-text"></span>
                </div>
                <div class="ci-input-container">
                    <textarea id="ci-mensaje-input" placeholder="<?php esc_attr_e('Escribe un mensaje...', 'flavor-chat-ia'); ?>" rows="1"></textarea>
                    <button type="button" class="ci-btn-enviar" id="ci-btn-enviar" disabled>
                        <span class="dashicons dashicons-arrow-right-alt"></span>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Iniciar chat (boton)
     */
    public function shortcode_iniciar_chat($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $atts = shortcode_atts([
            'usuario_id' => 0,
            'texto' => __('Enviar mensaje', 'flavor-chat-ia'),
            'clase' => '',
        ], $atts);

        $usuario_id = intval($atts['usuario_id']);

        if (!$usuario_id || $usuario_id === get_current_user_id()) {
            return '';
        }

        // Verificar bloqueos
        if ($this->esta_bloqueado(get_current_user_id(), $usuario_id) ||
            $this->esta_bloqueado($usuario_id, get_current_user_id())) {
            return '';
        }

        $clase = 'ci-btn-iniciar-chat';
        if (!empty($atts['clase'])) {
            $clase .= ' ' . esc_attr($atts['clase']);
        }

        return sprintf(
            '<button type="button" class="%s" data-usuario="%d">
                <span class="dashicons dashicons-email"></span>
                %s
            </button>',
            $clase,
            $usuario_id,
            esc_html($atts['texto'])
        );
    }

    // =========================================================================
    // FUNCIONES AUXILIARES
    // =========================================================================

    /**
     * Verificar si usuario es participante de conversacion
     */
    private function usuario_es_participante($usuario_id, $conversacion_id) {
        if (!$usuario_id || !$conversacion_id) {
            return false;
        }

        global $wpdb;
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_participantes
             WHERE conversacion_id = %d AND usuario_id = %d",
            $conversacion_id,
            $usuario_id
        ));
    }

    /**
     * Verificar si un usuario ha bloqueado a otro
     */
    private function esta_bloqueado($quien_bloquea, $bloqueado) {
        global $wpdb;
        $tabla_bloqueados = $wpdb->prefix . 'flavor_chat_bloqueados';

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_bloqueados
             WHERE usuario_id = %d AND bloqueado_id = %d",
            $quien_bloquea,
            $bloqueado
        ));
    }

    /**
     * Obtener el otro participante de una conversacion
     */
    private function obtener_otro_participante($usuario_id, $conversacion_id) {
        global $wpdb;
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

        $otro_id = $wpdb->get_var($wpdb->prepare(
            "SELECT usuario_id FROM $tabla_participantes
             WHERE conversacion_id = %d AND usuario_id != %d
             LIMIT 1",
            $conversacion_id,
            $usuario_id
        ));

        if (!$otro_id) {
            return [
                'id' => 0,
                'nombre' => __('Usuario', 'flavor-chat-ia'),
                'avatar' => '',
                'estado' => 'offline',
            ];
        }

        $otro_usuario = get_userdata($otro_id);

        return [
            'id' => (int) $otro_id,
            'nombre' => $otro_usuario ? $otro_usuario->display_name : __('Usuario', 'flavor-chat-ia'),
            'avatar' => get_avatar_url($otro_id, ['size' => 96]),
            'estado' => $this->obtener_estado_usuario($otro_id),
        ];
    }

    /**
     * Verificar si usuario esta escribiendo
     */
    private function usuario_escribiendo($conversacion_id, $usuario_id) {
        global $wpdb;
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

        $escribiendo = $wpdb->get_row($wpdb->prepare(
            "SELECT escribiendo, escribiendo_timestamp FROM $tabla_participantes
             WHERE conversacion_id = %d AND usuario_id = %d",
            $conversacion_id,
            $usuario_id
        ));

        if (!$escribiendo || !$escribiendo->escribiendo) {
            return false;
        }

        // Verificar que no haya pasado mas de 5 segundos
        $tiempo_transcurrido = time() - strtotime($escribiendo->escribiendo_timestamp);
        return $tiempo_transcurrido < 5;
    }

    /**
     * Obtener estado de usuario
     */
    private function obtener_estado_usuario($usuario_id) {
        global $wpdb;
        $tabla_estados = $wpdb->prefix . 'flavor_chat_estados_usuario';

        $estado = $wpdb->get_row($wpdb->prepare(
            "SELECT estado, ultima_actividad FROM $tabla_estados WHERE usuario_id = %d",
            $usuario_id
        ));

        if (!$estado) {
            return 'offline';
        }

        // Considerar online si la ultima actividad fue hace menos de 5 minutos
        $tiempo_transcurrido = time() - strtotime($estado->ultima_actividad);

        if ($tiempo_transcurrido < 300) {
            return $estado->estado === 'offline' ? 'online' : $estado->estado;
        }

        return 'offline';
    }

    /**
     * Actualizar ultima actividad de usuario
     */
    private function actualizar_actividad($usuario_id) {
        global $wpdb;
        $tabla_estados = $wpdb->prefix . 'flavor_chat_estados_usuario';

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_estados WHERE usuario_id = %d",
            $usuario_id
        ));

        if ($existe) {
            $wpdb->update(
                $tabla_estados,
                [
                    'estado' => 'online',
                    'ultima_actividad' => current_time('mysql'),
                ],
                ['usuario_id' => $usuario_id]
            );
        } else {
            $wpdb->insert($tabla_estados, [
                'usuario_id' => $usuario_id,
                'estado' => 'online',
                'ultima_actividad' => current_time('mysql'),
            ]);
        }
    }

    /**
     * Marcar usuario como online (en login)
     */
    public function marcar_usuario_online($user_login, $user) {
        $this->actualizar_actividad($user->ID);
    }

    /**
     * Marcar usuario como offline (en logout)
     */
    public function marcar_usuario_offline() {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) return;

        global $wpdb;
        $tabla_estados = $wpdb->prefix . 'flavor_chat_estados_usuario';

        $wpdb->update(
            $tabla_estados,
            ['estado' => 'offline'],
            ['usuario_id' => $usuario_id]
        );
    }

    /**
     * Enviar notificacion de nuevo mensaje
     */
    private function enviar_notificacion_mensaje($conversacion_id, $mensaje_id, $destinatario_id) {
        $remitente = wp_get_current_user();
        $settings = $this->get_settings();

        if (!$settings['notificaciones_push']) {
            return;
        }

        // Verificar si el destinatario tiene notificaciones silenciadas
        global $wpdb;
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

        $participacion = $wpdb->get_row($wpdb->prepare(
            "SELECT notificaciones, silenciado FROM $tabla_participantes
             WHERE conversacion_id = %d AND usuario_id = %d",
            $conversacion_id,
            $destinatario_id
        ));

        if ($participacion && ($participacion->silenciado || $participacion->notificaciones === 'ninguna')) {
            return;
        }

        do_action('flavor_notificacion_enviar', $destinatario_id, 'nuevo_mensaje_privado', [
            'conversacion_id' => $conversacion_id,
            'mensaje_id' => $mensaje_id,
            'remitente_id' => $remitente->ID,
            'remitente_nombre' => $remitente->display_name,
        ]);
    }

    /**
     * Formatear mensaje (links, emojis, etc)
     */
    private function formatear_mensaje($mensaje) {
        if (empty($mensaje)) {
            return '';
        }

        // Escapar HTML
        $mensaje = esc_html($mensaje);

        // Convertir URLs en links
        $mensaje = preg_replace(
            '/(https?:\/\/[^\s<]+)/i',
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
            $mensaje
        );

        // Convertir saltos de linea
        $mensaje = nl2br($mensaje);

        // Negrita **texto**
        $mensaje = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $mensaje);

        // Cursiva _texto_
        $mensaje = preg_replace('/\_(.+?)\_/', '<em>$1</em>', $mensaje);

        return $mensaje;
    }

    /**
     * Tiempo relativo (hace X tiempo)
     */
    private function tiempo_relativo($fecha) {
        $timestamp = strtotime($fecha);
        $diferencia = time() - $timestamp;

        if ($diferencia < 60) {
            return __('ahora', 'flavor-chat-ia');
        } elseif ($diferencia < 3600) {
            $minutos = floor($diferencia / 60);
            return sprintf(_n('%d min', '%d min', $minutos, 'flavor-chat-ia'), $minutos);
        } elseif ($diferencia < 86400) {
            $horas = floor($diferencia / 3600);
            return sprintf(_n('%d h', '%d h', $horas, 'flavor-chat-ia'), $horas);
        } elseif ($diferencia < 172800) {
            return __('ayer', 'flavor-chat-ia');
        } elseif ($diferencia < 604800) {
            $dias = floor($diferencia / 86400);
            return sprintf(_n('%d dia', '%d dias', $dias, 'flavor-chat-ia'), $dias);
        } else {
            return date_i18n('j M', $timestamp);
        }
    }

    /**
     * Ejecutar limpieza de mensajes antiguos
     */
    public function ejecutar_limpieza_mensajes() {
        $settings = $this->get_settings();
        $dias = intval($settings['eliminar_mensajes_antiguos_dias']);

        if ($dias <= 0) {
            return;
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';

        $fecha_limite = date('Y-m-d H:i:s', strtotime("-{$dias} days"));

        // Eliminar mensajes antiguos
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_mensajes WHERE fecha_creacion < %s",
            $fecha_limite
        ));

        // Log
        flavor_log_debug( "Limpieza ejecutada. Mensajes anteriores a {$fecha_limite} eliminados.", 'ChatInterno' );
    }

    /**
     * Agregar tab al dashboard de usuario
     */
    public function add_dashboard_tab($tabs) {
        $tabs['mensajes'] = [
            'label' => __('Mensajes', 'flavor-chat-ia'),
            'icon' => 'dashicons-email',
            'callback' => [$this, 'render_dashboard_tab'],
            'orden' => 20,
        ];

        return $tabs;
    }

    /**
     * Renderizar tab del dashboard
     */
    public function render_dashboard_tab() {
        echo do_shortcode('[flavor_chat_inbox]');
    }

    // =========================================================================
    // WEB COMPONENTS
    // =========================================================================

    /**
     * Componentes web del modulo
     */
    public function get_web_components() {
        return [
            'hero_chat_interno' => [
                'label' => __('Hero Chat Interno', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-email',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Mensajeria Privada', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Comunicacion directa y privada entre vecinos', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                ],
                'template' => 'chat-interno/hero',
            ],
            'features_chat' => [
                'label' => __('Caracteristicas del Chat', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-format-chat',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Mensajeria Completa', 'flavor-chat-ia')],
                ],
                'template' => 'chat-interno/features',
            ],
            'cta_app' => [
                'label' => __('CTA Descargar App', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-smartphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Descarga la App', 'flavor-chat-ia')],
                    'descripcion' => ['type' => 'textarea', 'default' => __('Chatea desde cualquier lugar con la app movil', 'flavor-chat-ia')],
                    'boton_ios' => ['type' => 'url', 'default' => '#'],
                    'boton_android' => ['type' => 'url', 'default' => '#'],
                ],
                'template' => 'chat-interno/cta-app',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'chat_conversaciones',
                'description' => 'Ver mis conversaciones privadas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
            [
                'name' => 'chat_enviar_mensaje',
                'description' => 'Enviar mensaje privado a un usuario',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'usuario_id' => [
                            'type' => 'integer',
                            'description' => 'ID del usuario destinatario',
                        ],
                        'mensaje' => [
                            'type' => 'string',
                            'description' => 'Contenido del mensaje',
                        ],
                    ],
                    'required' => ['usuario_id', 'mensaje'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Chat Interno Privado**

Sistema de mensajeria privada uno a uno entre miembros.

**Caracteristicas:**
- Mensajes de texto, voz e imagenes
- Notificaciones en tiempo real
- Indicador de "escribiendo..."
- Confirmacion de lectura
- Busqueda en historial
- Responder mensajes especificos
- Edicion de mensajes (tiempo limitado)
- Eliminacion de mensajes

**Privacidad:**
- Conversaciones privadas entre dos usuarios
- Control de quien puede contactarte
- Bloqueo de usuarios
- Archivado de conversaciones

**Estados de mensaje:**
- Enviado: Mensaje entregado al servidor
- Leido: El destinatario ha visto el mensaje

**Usos:**
- Coordinacion de intercambios
- Consultas sobre servicios
- Comunicacion vecinal privada
- Soporte tecnico
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Puedo eliminar mensajes?',
                'respuesta' => 'Si, puedes eliminar mensajes para ti o para todos los participantes si eres el remitente.',
            ],
            [
                'pregunta' => 'Son seguros mis mensajes?',
                'respuesta' => 'Si, los mensajes son privados y solo los participantes de la conversacion pueden verlos.',
            ],
            [
                'pregunta' => 'Puedo bloquear a alguien?',
                'respuesta' => 'Si, puedes bloquear usuarios para evitar que te contacten.',
            ],
            [
                'pregunta' => 'Se pueden enviar archivos?',
                'respuesta' => 'Si, puedes enviar imagenes, documentos y mensajes de voz.',
            ],
            [
                'pregunta' => 'Como se si alguien leyo mi mensaje?',
                'respuesta' => 'Veras un indicador de "visto" cuando el destinatario haya leido tu mensaje.',
            ],
        ];
    }

    /**
     * Configuracion para el panel de administracion unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'chat_interno',
            'label' => __('Chat Interno', 'flavor-chat-ia'),
            'icon' => 'dashicons-testimonial',
            'capability' => 'manage_options',
            'categoria' => 'comunicacion',
            'paginas' => [
                [
                    'slug' => 'flavor-chat-interno-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'flavor-chat-interno-conversaciones',
                    'titulo' => __('Conversaciones', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_conversaciones'],
                    'badge' => [$this, 'contar_conversaciones_activas'],
                ],
                [
                    'slug' => 'flavor-chat-interno-config',
                    'titulo' => __('Configuracion', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_configuracion'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_dashboard_widget'],
            'estadisticas' => [$this, 'get_estadisticas_admin'],
        ];
    }

    /**
     * Cuenta conversaciones activas para el badge
     *
     * @return int
     */
    public function contar_conversaciones_activas() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';

        if (!$this->tabla_existe($tabla_conversaciones)) {
            return 0;
        }

        $total_activas = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_conversaciones} WHERE estado = 'activa'"
        );

        return intval($total_activas);
    }

    /**
     * Renderiza el dashboard de administracion
     */
    public function render_admin_dashboard() {
        $this->render_page_header(__('Chat Interno - Dashboard', 'flavor-chat-ia'));
        ?>
        <div class="wrap flavor-chat-interno-admin">
            <div class="flavor-admin-grid">
                <?php $this->render_dashboard_widget(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la lista de conversaciones en admin
     */
    public function render_admin_conversaciones() {
        $this->render_page_header(
            __('Conversaciones', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Exportar', 'flavor-chat-ia'),
                    'url' => admin_url('admin.php?page=flavor-chat-interno-conversaciones&action=exportar'),
                    'class' => 'button-secondary',
                ],
            ]
        );
        ?>
        <div class="wrap flavor-chat-interno-admin">
            <p><?php _e('Listado de todas las conversaciones del sistema.', 'flavor-chat-ia'); ?></p>
            <?php $this->render_tabla_conversaciones(); ?>
        </div>
        <?php
    }

    /**
     * Renderiza la tabla de conversaciones
     */
    private function render_tabla_conversaciones() {
        global $wpdb;
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

        if (!$this->tabla_existe($tabla_conversaciones)) {
            echo '<div class="notice notice-warning"><p>' . __('Las tablas no estan creadas.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $conversaciones = $wpdb->get_results("
            SELECT c.*,
                   COUNT(DISTINCT p.usuario_id) as num_participantes
            FROM {$tabla_conversaciones} c
            LEFT JOIN {$tabla_participantes} p ON c.id = p.conversacion_id
            GROUP BY c.id
            ORDER BY c.fecha_actualizacion DESC
            LIMIT 50
        ");

        if (empty($conversaciones)) {
            echo '<div class="notice notice-info"><p>' . __('No hay conversaciones registradas.', 'flavor-chat-ia') . '</p></div>';
            return;
        }
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Tipo', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Participantes', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Ultima actividad', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($conversaciones as $conversacion): ?>
                    <tr>
                        <td><?php echo esc_html($conversacion->id); ?></td>
                        <td><?php echo esc_html(ucfirst($conversacion->tipo)); ?></td>
                        <td>
                            <span class="flavor-estado flavor-estado-<?php echo esc_attr($conversacion->estado); ?>">
                                <?php echo esc_html(ucfirst($conversacion->estado)); ?>
                            </span>
                        </td>
                        <td><?php echo intval($conversacion->num_participantes); ?></td>
                        <td><?php echo esc_html(human_time_diff(strtotime($conversacion->fecha_actualizacion), current_time('timestamp')) . ' ' . __('atras', 'flavor-chat-ia')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Renderiza la pagina de configuracion en admin
     */
    public function render_admin_configuracion() {
        $this->render_page_header(__('Configuracion del Chat Interno', 'flavor-chat-ia'));
        ?>
        <div class="wrap flavor-chat-interno-admin">
            <form method="post" action="">
                <?php wp_nonce_field('flavor_chat_interno_config', 'config_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Permitir archivos', 'flavor-chat-ia'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="permite_archivos" value="1" <?php checked($this->get_setting('permite_archivos'), true); ?>>
                                <?php _e('Permitir envio de archivos adjuntos', 'flavor-chat-ia'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Notas de voz', 'flavor-chat-ia'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="permite_notas_voz" value="1" <?php checked($this->get_setting('permite_notas_voz'), true); ?>>
                                <?php _e('Permitir envio de notas de voz', 'flavor-chat-ia'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Mensajes por pagina', 'flavor-chat-ia'); ?></th>
                        <td>
                            <input type="number" name="mensajes_por_pagina" value="<?php echo esc_attr($this->get_setting('mensajes_por_pagina')); ?>" min="10" max="100" class="small-text">
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Guardar cambios', 'flavor-chat-ia'); ?></button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Widget para el dashboard del panel unificado
     */
    public function render_dashboard_widget() {
        global $wpdb;
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';

        $total_conversaciones = 0;
        $total_mensajes_hoy = 0;

        if ($this->tabla_existe($tabla_conversaciones)) {
            $total_conversaciones = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_conversaciones}");
        }

        if ($this->tabla_existe($tabla_mensajes)) {
            $total_mensajes_hoy = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_mensajes} WHERE DATE(fecha_envio) = %s",
                current_time('Y-m-d')
            ));
        }
        ?>
        <div class="flavor-widget-stats">
            <div class="stat-item">
                <span class="stat-number"><?php echo intval($total_conversaciones); ?></span>
                <span class="stat-label"><?php _e('Conversaciones', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo intval($total_mensajes_hoy); ?></span>
                <span class="stat-label"><?php _e('Mensajes hoy', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Estadisticas para el panel unificado
     *
     * @return array
     */
    public function get_estadisticas_admin() {
        global $wpdb;
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';

        $total_activas = 0;
        if ($this->tabla_existe($tabla_conversaciones)) {
            $total_activas = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_conversaciones} WHERE estado = 'activa'");
        }

        return [
            [
                'icon' => 'dashicons-testimonial',
                'valor' => intval($total_activas),
                'label' => __('Chats activos', 'flavor-chat-ia'),
                'color' => 'purple',
                'enlace' => admin_url('admin.php?page=flavor-chat-interno-conversaciones'),
            ],
        ];
    }

    /**
     * Obtiene estadísticas para el dashboard del cliente
     *
     * @return array Estadísticas del módulo
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $estadisticas = [];

        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversaciones';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_mensajes';
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_participantes';

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return $estadisticas;
        }

        if ($this->tabla_existe($tabla_participantes)) {
            // Mis conversaciones activas
            $mis_chats = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT p.conversacion_id)
                 FROM {$tabla_participantes} p
                 INNER JOIN {$tabla_conversaciones} c ON p.conversacion_id = c.id
                 WHERE p.usuario_id = %d AND c.estado = 'activa'",
                $usuario_id
            ));

            $estadisticas['mis_chats'] = [
                'icon' => 'dashicons-format-chat',
                'valor' => $mis_chats,
                'label' => __('Conversaciones', 'flavor-chat-ia'),
                'color' => $mis_chats > 0 ? 'purple' : 'gray',
            ];

            // Mensajes sin leer (comparando IDs de mensaje)
            if ($this->tabla_existe($tabla_mensajes)) {
                $sin_leer = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_mensajes} m
                     INNER JOIN {$tabla_participantes} p ON m.conversacion_id = p.conversacion_id
                     WHERE p.usuario_id = %d
                     AND m.id > COALESCE(p.ultimo_mensaje_leido, 0)
                     AND m.remitente_id != %d",
                    $usuario_id,
                    $usuario_id
                ));

                if ($sin_leer > 0) {
                    $estadisticas['sin_leer'] = [
                        'icon' => 'dashicons-email-alt',
                        'valor' => $sin_leer,
                        'label' => __('Sin leer', 'flavor-chat-ia'),
                        'color' => 'orange',
                    ];
                }
            }
        }

        return $estadisticas;
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Chat Interno', 'flavor-chat-ia'),
                'slug' => 'chat-interno',
                'content' => '<h1>' . __('Chat Interno', 'flavor-chat-ia') . '</h1>
<p>' . __('Mensajería privada entre usuarios de la comunidad. Comunícate de forma directa y segura.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="chat_interno" action="listar"]',
                'parent' => 0,
            ],
            [
                'title' => __('Conversaciones', 'flavor-chat-ia'),
                'slug' => 'conversaciones',
                'content' => '<h1>' . __('Mis Conversaciones', 'flavor-chat-ia') . '</h1>
<p>' . __('Accede a todas tus conversaciones activas y archivadas.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="chat_interno" action="conversaciones"]',
                'parent' => 'chat-interno',
            ],
        ];
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'chat-interno',
            'title'    => __('Mensajes', 'flavor-chat-ia'),
            'subtitle' => __('Mensajería privada entre usuarios', 'flavor-chat-ia'),
            'icon'     => '💬',
            'color'    => 'primary', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_chat_mensajes',
                'primary_key' => 'id',
            ],

            'fields' => [
                'contenido'      => ['type' => 'textarea', 'label' => __('Mensaje', 'flavor-chat-ia'), 'required' => true],
                'destinatario_id'=> ['type' => 'user', 'label' => __('Destinatario', 'flavor-chat-ia'), 'required' => true],
                'adjuntos'       => ['type' => 'file', 'label' => __('Adjuntos', 'flavor-chat-ia'), 'multiple' => true],
                'conversacion_id'=> ['type' => 'hidden', 'label' => __('Conversación', 'flavor-chat-ia')],
            ],

            'estados' => [
                'enviado'    => ['label' => __('Enviado', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '✓'],
                'entregado'  => ['label' => __('Entregado', 'flavor-chat-ia'), 'color' => 'blue', 'icon' => '✓✓'],
                'leido'      => ['label' => __('Leído', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '👁️'],
            ],

            'stats' => [
                [
                    'key'   => 'conversaciones_activas',
                    'label' => __('Conversaciones', 'flavor-chat-ia'),
                    'icon'  => '💬',
                    'color' => 'indigo',
                    'query' => "SELECT COUNT(DISTINCT conversacion_id) FROM {prefix}flavor_chat_mensajes WHERE remitente_id = {user_id} OR destinatario_id = {user_id}",
                ],
                [
                    'key'   => 'mensajes_sin_leer',
                    'label' => __('Sin leer', 'flavor-chat-ia'),
                    'icon'  => '🔴',
                    'color' => 'red',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_chat_mensajes WHERE destinatario_id = {user_id} AND leido = 0",
                ],
                [
                    'key'   => 'mensajes_hoy',
                    'label' => __('Hoy', 'flavor-chat-ia'),
                    'icon'  => '📨',
                    'color' => 'green',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_chat_mensajes WHERE (remitente_id = {user_id} OR destinatario_id = {user_id}) AND DATE(created_at) = CURDATE()",
                ],
            ],

            'card' => [
                'layout'         => 'conversation',
                'avatar'         => true,
                'show_preview'   => true,
                'show_timestamp' => true,
                'show_unread'    => true,
            ],

            'tabs' => [
                'conversaciones' => [
                    'label'   => __('Conversaciones', 'flavor-chat-ia'),
                    'icon'    => '💬',
                    'content' => 'shortcode:chat_interno_conversaciones',
                ],
                'nuevo' => [
                    'label'   => __('Nuevo mensaje', 'flavor-chat-ia'),
                    'icon'    => '✏️',
                    'content' => 'shortcode:chat_interno_nuevo',
                ],
                'archivados' => [
                    'label'   => __('Archivados', 'flavor-chat-ia'),
                    'icon'    => '📁',
                    'content' => 'shortcode:chat_interno_archivados',
                ],
            ],

            'archive' => [
                'columns'       => 1,
                'per_page'      => 20,
                'order_by'      => 'updated_at',
                'order'         => 'DESC',
                'conversation_view' => true,
            ],

            'dashboard' => [
                'widgets' => [
                    'conversaciones_recientes' => ['type' => 'list', 'title' => __('Conversaciones recientes', 'flavor-chat-ia')],
                    'mensajes_sin_leer'        => ['type' => 'notification', 'title' => __('Sin leer', 'flavor-chat-ia')],
                ],
                'actions' => [
                    'nuevo_mensaje' => [
                        'label' => __('Nuevo mensaje', 'flavor-chat-ia'),
                        'icon'  => '✏️',
                        'modal' => 'chat-interno-nuevo',
                    ],
                ],
            ],

            'features' => [
                'has_archive'    => false,
                'has_single'     => true,
                'has_dashboard'  => true,
                'has_search'     => true,
                'realtime'       => true,
                'has_typing'     => true,
                'has_read_receipts' => true,
                'has_attachments'=> true,
                'has_reactions'  => true,
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-chat-interno-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Chat_Interno_Dashboard_Tab')) {
                Flavor_Chat_Interno_Dashboard_Tab::get_instance();
            }
        }
    }
}
