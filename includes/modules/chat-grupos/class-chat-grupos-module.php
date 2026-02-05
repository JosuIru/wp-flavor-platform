<?php
/**
 * Módulo de Chat de Grupos para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Chat de Grupos - Grupos de conversación temáticos
 */
class Flavor_Chat_Chat_Grupos_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'chat_grupos';
        $this->name = __('Chat de Grupos', 'flavor-chat-ia');
        $this->description = __('Grupos de conversación temáticos para la comunidad con canales y temas organizados.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        return Flavor_Chat_Helpers::tabla_existe($tabla_grupos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Chat de Grupos no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'ambas',
            'max_miembros_por_grupo' => 500,
            'permite_crear_grupos' => true,
            'requiere_aprobacion_grupos' => false,
            'permite_grupos_privados' => true,
            'permite_archivos' => true,
            'max_archivo_mb' => 10,
            'permite_videollamadas' => false,
            'permite_encuestas' => true,
            'historial_mensajes_dias' => 365,
            'notificaciones_push' => true,
            'mensajes_por_pagina' => 50,
            'permite_reacciones' => true,
            'permite_hilos' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_ajax_flavor_chat_grupos_send', [$this, 'ajax_enviar_mensaje']);
        add_action('wp_ajax_flavor_chat_grupos_messages', [$this, 'ajax_obtener_mensajes']);
        add_action('wp_ajax_flavor_chat_grupos_mark_read', [$this, 'ajax_marcar_leido']);
        add_action('wp_ajax_flavor_chat_grupos_typing', [$this, 'ajax_typing']);
        add_action('wp_ajax_flavor_chat_grupos_react', [$this, 'ajax_reaccionar']);
        add_action('wp_ajax_flavor_chat_grupos_search', [$this, 'ajax_buscar_mensajes']);
        add_action('wp_ajax_flavor_chat_grupos_upload', [$this, 'ajax_subir_archivo']);
        add_action('wp_ajax_flavor_chat_grupos_create', [$this, 'ajax_crear_grupo']);
        add_action('wp_ajax_flavor_chat_grupos_join', [$this, 'ajax_unirse_grupo']);
        add_action('wp_ajax_flavor_chat_grupos_leave', [$this, 'ajax_salir_grupo']);
        add_action('wp_ajax_flavor_chat_grupos_invite', [$this, 'ajax_invitar']);
        add_action('wp_ajax_flavor_chat_grupos_kick', [$this, 'ajax_expulsar']);
        add_action('wp_ajax_flavor_chat_grupos_role', [$this, 'ajax_cambiar_rol']);
        add_action('wp_ajax_flavor_chat_grupos_settings', [$this, 'ajax_actualizar_config']);
        add_action('wp_ajax_flavor_chat_grupos_poll_create', [$this, 'ajax_crear_encuesta']);
        add_action('wp_ajax_flavor_chat_grupos_poll_vote', [$this, 'ajax_votar_encuesta']);
        add_action('wp_ajax_flavor_chat_grupos_delete_msg', [$this, 'ajax_eliminar_mensaje']);
        add_action('wp_ajax_flavor_chat_grupos_edit_msg', [$this, 'ajax_editar_mensaje']);
        add_action('wp_ajax_flavor_chat_grupos_pin', [$this, 'ajax_fijar_mensaje']);

        // Dashboard integration
        add_filter('flavor_user_dashboard_tabs', [$this, 'add_dashboard_tab']);

        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_grupos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_invitaciones = $wpdb->prefix . 'flavor_chat_grupos_invitaciones';
        $tabla_reacciones = $wpdb->prefix . 'flavor_chat_grupos_reacciones';
        $tabla_encuestas = $wpdb->prefix . 'flavor_chat_grupos_encuestas';
        $tabla_votos = $wpdb->prefix . 'flavor_chat_grupos_votos';
        $tabla_fijados = $wpdb->prefix . 'flavor_chat_grupos_fijados';

        $sql_grupos = "CREATE TABLE IF NOT EXISTS $tabla_grupos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            color varchar(7) DEFAULT '#2271b1',
            creador_id bigint(20) unsigned NOT NULL,
            tipo enum('publico','privado','secreto') DEFAULT 'publico',
            categoria varchar(100) DEFAULT NULL,
            max_miembros int(11) DEFAULT 500,
            miembros_count int(11) DEFAULT 0,
            mensajes_count int(11) DEFAULT 0,
            permite_archivos tinyint(1) DEFAULT 1,
            permite_encuestas tinyint(1) DEFAULT 1,
            solo_admins_publican tinyint(1) DEFAULT 0,
            estado enum('activo','archivado','bloqueado') DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ultimo_mensaje_id bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY creador_id (creador_id),
            KEY tipo (tipo),
            KEY categoria (categoria),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_miembros = "CREATE TABLE IF NOT EXISTS $tabla_miembros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            grupo_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            rol enum('miembro','moderador','admin') DEFAULT 'miembro',
            notificaciones enum('todas','menciones','ninguna') DEFAULT 'todas',
            silenciado_hasta datetime DEFAULT NULL,
            ultimo_mensaje_leido bigint(20) DEFAULT 0,
            escribiendo tinyint(1) DEFAULT 0,
            escribiendo_timestamp datetime DEFAULT NULL,
            fecha_ingreso datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY grupo_usuario (grupo_id, usuario_id),
            KEY usuario_id (usuario_id),
            KEY rol (rol)
        ) $charset_collate;";

        $sql_mensajes = "CREATE TABLE IF NOT EXISTS $tabla_mensajes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            grupo_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            mensaje text NOT NULL,
            mensaje_html text DEFAULT NULL,
            tipo enum('texto','imagen','archivo','sistema','encuesta','hilo') DEFAULT 'texto',
            adjuntos text DEFAULT NULL COMMENT 'JSON',
            responde_a bigint(20) unsigned DEFAULT NULL,
            hilo_padre bigint(20) unsigned DEFAULT NULL,
            menciones text DEFAULT NULL COMMENT 'JSON de user_ids',
            reacciones_count int(11) DEFAULT 0,
            respuestas_count int(11) DEFAULT 0,
            editado tinyint(1) DEFAULT 0,
            eliminado tinyint(1) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_edicion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY grupo_id (grupo_id),
            KEY usuario_id (usuario_id),
            KEY fecha_creacion (fecha_creacion),
            KEY responde_a (responde_a),
            KEY hilo_padre (hilo_padre),
            FULLTEXT KEY mensaje (mensaje)
        ) $charset_collate;";

        $sql_invitaciones = "CREATE TABLE IF NOT EXISTS $tabla_invitaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            grupo_id bigint(20) unsigned NOT NULL,
            invitado_id bigint(20) unsigned DEFAULT NULL,
            invitado_email varchar(255) DEFAULT NULL,
            invitador_id bigint(20) unsigned NOT NULL,
            codigo varchar(64) DEFAULT NULL,
            estado enum('pendiente','aceptada','rechazada','expirada') DEFAULT 'pendiente',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY codigo (codigo),
            KEY grupo_id (grupo_id),
            KEY invitado_id (invitado_id)
        ) $charset_collate;";

        $sql_reacciones = "CREATE TABLE IF NOT EXISTS $tabla_reacciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            mensaje_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            emoji varchar(32) NOT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY mensaje_usuario_emoji (mensaje_id, usuario_id, emoji),
            KEY mensaje_id (mensaje_id)
        ) $charset_collate;";

        $sql_encuestas = "CREATE TABLE IF NOT EXISTS $tabla_encuestas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            mensaje_id bigint(20) unsigned NOT NULL,
            grupo_id bigint(20) unsigned NOT NULL,
            pregunta varchar(500) NOT NULL,
            opciones text NOT NULL COMMENT 'JSON',
            multiple tinyint(1) DEFAULT 0,
            anonima tinyint(1) DEFAULT 0,
            fecha_cierre datetime DEFAULT NULL,
            cerrada tinyint(1) DEFAULT 0,
            votos_totales int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY mensaje_id (mensaje_id),
            KEY grupo_id (grupo_id)
        ) $charset_collate;";

        $sql_votos = "CREATE TABLE IF NOT EXISTS $tabla_votos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            encuesta_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            opcion_index int(11) NOT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY encuesta_id (encuesta_id),
            UNIQUE KEY encuesta_usuario_opcion (encuesta_id, usuario_id, opcion_index)
        ) $charset_collate;";

        $sql_fijados = "CREATE TABLE IF NOT EXISTS $tabla_fijados (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            grupo_id bigint(20) unsigned NOT NULL,
            mensaje_id bigint(20) unsigned NOT NULL,
            fijado_por bigint(20) unsigned NOT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY grupo_mensaje (grupo_id, mensaje_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_grupos);
        dbDelta($sql_miembros);
        dbDelta($sql_mensajes);
        dbDelta($sql_invitaciones);
        dbDelta($sql_reacciones);
        dbDelta($sql_encuestas);
        dbDelta($sql_votos);
        dbDelta($sql_fijados);
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('flavor_chat_grupos', [$this, 'shortcode_chat_grupos']);
        add_shortcode('flavor_chat_grupo', [$this, 'shortcode_chat_grupo']);
        add_shortcode('flavor_grupos_lista', [$this, 'shortcode_grupos_lista']);
        add_shortcode('flavor_grupos_explorar', [$this, 'shortcode_grupos_explorar']);
        add_shortcode('flavor_grupos_crear', [$this, 'shortcode_crear_grupo']);
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        wp_enqueue_style(
            'flavor-chat-grupos',
            FLAVOR_CHAT_IA_URL . 'includes/modules/chat-grupos/assets/css/chat-grupos.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-chat-grupos',
            FLAVOR_CHAT_IA_URL . 'includes/modules/chat-grupos/assets/js/chat-grupos.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-chat-grupos', 'flavorChatGrupos', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'resturl' => rest_url('flavor/v1/chat-grupos/'),
            'nonce' => wp_create_nonce('flavor_chat_grupos'),
            'user_id' => get_current_user_id(),
            'user_name' => wp_get_current_user()->display_name,
            'user_avatar' => get_avatar_url(get_current_user_id(), ['size' => 48]),
            'polling_interval' => 3000,
            'typing_timeout' => 3000,
            'strings' => [
                'escribiendo' => __('escribiendo...', 'flavor-chat-ia'),
                'tu' => __('Tú', 'flavor-chat-ia'),
                'ahora' => __('ahora', 'flavor-chat-ia'),
                'ayer' => __('ayer', 'flavor-chat-ia'),
                'mensaje_eliminado' => __('Mensaje eliminado', 'flavor-chat-ia'),
                'mensaje_editado' => __('editado', 'flavor-chat-ia'),
                'sin_mensajes' => __('No hay mensajes aún. ¡Sé el primero en escribir!', 'flavor-chat-ia'),
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Verifica si debe cargar assets
     */
    private function should_load_assets() {
        global $post;
        if (!$post) return false;

        $shortcodes = ['flavor_chat_grupos', 'flavor_chat_grupo', 'flavor_grupos_lista', 'flavor_grupos_explorar', 'flavor_grupos_crear'];
        foreach ($shortcodes as $sc) {
            if (has_shortcode($post->post_content, $sc)) {
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
            'mis_grupos' => [
                'description' => 'Listar mis grupos de chat',
                'params' => [],
            ],
            'grupos_publicos' => [
                'description' => 'Buscar grupos públicos',
                'params' => ['categoria', 'busqueda', 'pagina'],
            ],
            'crear_grupo' => [
                'description' => 'Crear nuevo grupo',
                'params' => ['nombre', 'descripcion', 'tipo', 'categoria', 'color'],
            ],
            'editar_grupo' => [
                'description' => 'Editar grupo existente',
                'params' => ['grupo_id', 'nombre', 'descripcion', 'tipo'],
            ],
            'eliminar_grupo' => [
                'description' => 'Eliminar/archivar grupo',
                'params' => ['grupo_id'],
            ],
            'unirse_grupo' => [
                'description' => 'Unirse a un grupo',
                'params' => ['grupo_id', 'codigo_invitacion'],
            ],
            'salir_grupo' => [
                'description' => 'Salir de un grupo',
                'params' => ['grupo_id'],
            ],
            'mensajes' => [
                'description' => 'Ver mensajes de un grupo',
                'params' => ['grupo_id', 'desde', 'limite', 'antes_de'],
            ],
            'enviar_mensaje' => [
                'description' => 'Enviar mensaje al grupo',
                'params' => ['grupo_id', 'mensaje', 'responde_a', 'adjuntos'],
            ],
            'editar_mensaje' => [
                'description' => 'Editar mensaje propio',
                'params' => ['mensaje_id', 'mensaje'],
            ],
            'eliminar_mensaje' => [
                'description' => 'Eliminar mensaje',
                'params' => ['mensaje_id'],
            ],
            'info_grupo' => [
                'description' => 'Ver información del grupo',
                'params' => ['grupo_id'],
            ],
            'miembros_grupo' => [
                'description' => 'Ver miembros del grupo',
                'params' => ['grupo_id'],
            ],
            'invitar_miembro' => [
                'description' => 'Invitar usuario al grupo',
                'params' => ['grupo_id', 'usuario_id', 'email'],
            ],
            'expulsar_miembro' => [
                'description' => 'Expulsar miembro del grupo',
                'params' => ['grupo_id', 'usuario_id'],
            ],
            'cambiar_rol' => [
                'description' => 'Cambiar rol de miembro',
                'params' => ['grupo_id', 'usuario_id', 'rol'],
            ],
            'silenciar_grupo' => [
                'description' => 'Silenciar notificaciones',
                'params' => ['grupo_id', 'duracion_horas'],
            ],
            'buscar_mensajes' => [
                'description' => 'Buscar en mensajes del grupo',
                'params' => ['grupo_id', 'query'],
            ],
            'crear_encuesta' => [
                'description' => 'Crear encuesta en grupo',
                'params' => ['grupo_id', 'pregunta', 'opciones', 'multiple'],
            ],
            'votar_encuesta' => [
                'description' => 'Votar en encuesta',
                'params' => ['encuesta_id', 'opcion'],
            ],
            'reaccionar' => [
                'description' => 'Añadir reacción a mensaje',
                'params' => ['mensaje_id', 'emoji'],
            ],
            'fijar_mensaje' => [
                'description' => 'Fijar/desfijar mensaje',
                'params' => ['mensaje_id'],
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
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: Mis grupos
     */
    private function action_mis_grupos($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        $grupos = $wpdb->get_results($wpdb->prepare(
            "SELECT g.*, m.rol, m.notificaciones, m.ultimo_mensaje_leido, m.silenciado_hasta
             FROM $tabla_grupos g
             INNER JOIN $tabla_miembros m ON g.id = m.grupo_id
             WHERE m.usuario_id = %d AND g.estado = 'activo'
             ORDER BY g.fecha_actualizacion DESC",
            $usuario_id
        ));

        $resultado = [];
        foreach ($grupos as $g) {
            $no_leidos = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_mensajes
                 WHERE grupo_id = %d AND id > %d AND eliminado = 0",
                $g->id, $g->ultimo_mensaje_leido
            ));

            $ultimo_msg = $wpdb->get_row($wpdb->prepare(
                "SELECT m.*, u.display_name as autor_nombre
                 FROM $tabla_mensajes m
                 LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
                 WHERE m.grupo_id = %d AND m.eliminado = 0
                 ORDER BY m.id DESC LIMIT 1",
                $g->id
            ));

            $resultado[] = [
                'id' => (int) $g->id,
                'nombre' => $g->nombre,
                'slug' => $g->slug,
                'descripcion' => wp_trim_words($g->descripcion, 20),
                'imagen_url' => $g->imagen_url,
                'color' => $g->color,
                'tipo' => $g->tipo,
                'miembros' => (int) $g->miembros_count,
                'mensajes_no_leidos' => (int) $no_leidos,
                'mi_rol' => $g->rol,
                'silenciado' => $g->silenciado_hasta && strtotime($g->silenciado_hasta) > time(),
                'ultimo_mensaje' => $ultimo_msg ? [
                    'texto' => wp_trim_words($ultimo_msg->mensaje, 10),
                    'autor' => $ultimo_msg->autor_nombre,
                    'fecha' => $this->tiempo_relativo($ultimo_msg->fecha_creacion),
                ] : null,
            ];
        }

        return ['success' => true, 'grupos' => $resultado];
    }

    /**
     * Acción: Grupos públicos
     */
    private function action_grupos_publicos($params) {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        $where = ["g.tipo = 'publico'", "g.estado = 'activo'"];
        $where_values = [];

        if (!empty($params['categoria'])) {
            $where[] = "g.categoria = %s";
            $where_values[] = sanitize_text_field($params['categoria']);
        }

        if (!empty($params['busqueda'])) {
            $where[] = "(g.nombre LIKE %s OR g.descripcion LIKE %s)";
            $busqueda = '%' . $wpdb->esc_like(sanitize_text_field($params['busqueda'])) . '%';
            $where_values[] = $busqueda;
            $where_values[] = $busqueda;
        }

        $pagina = max(1, intval($params['pagina'] ?? 1));
        $limite = 20;
        $offset = ($pagina - 1) * $limite;

        $where_sql = implode(' AND ', $where);

        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_grupos g WHERE $where_sql",
                ...$where_values
            )
        );

        $grupos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT g.* FROM $tabla_grupos g
                 WHERE $where_sql
                 ORDER BY g.miembros_count DESC, g.mensajes_count DESC
                 LIMIT %d OFFSET %d",
                array_merge($where_values, [$limite, $offset])
            )
        );

        $usuario_id = get_current_user_id();
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $resultado = [];
        foreach ($grupos as $g) {
            $es_miembro = false;
            if ($usuario_id) {
                $es_miembro = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
                    $g->id, $usuario_id
                )) > 0;
            }

            $resultado[] = [
                'id' => (int) $g->id,
                'nombre' => $g->nombre,
                'slug' => $g->slug,
                'descripcion' => wp_trim_words($g->descripcion, 30),
                'imagen_url' => $g->imagen_url,
                'color' => $g->color,
                'categoria' => $g->categoria,
                'miembros' => (int) $g->miembros_count,
                'mensajes' => (int) $g->mensajes_count,
                'es_miembro' => $es_miembro,
            ];
        }

        return [
            'success' => true,
            'grupos' => $resultado,
            'total' => (int) $total,
            'paginas' => ceil($total / $limite),
            'pagina_actual' => $pagina,
        ];
    }

    /**
     * Acción: Crear grupo
     */
    private function action_crear_grupo($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        $settings = $this->get_settings();
        if (!$settings['permite_crear_grupos']) {
            return ['success' => false, 'error' => 'La creación de grupos está deshabilitada.'];
        }

        $nombre = sanitize_text_field($params['nombre'] ?? '');
        if (strlen($nombre) < 3) {
            return ['success' => false, 'error' => 'El nombre debe tener al menos 3 caracteres.'];
        }

        $tipo = in_array($params['tipo'] ?? '', ['publico', 'privado', 'secreto']) ? $params['tipo'] : 'publico';
        if ($tipo !== 'publico' && !$settings['permite_grupos_privados']) {
            $tipo = 'publico';
        }

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $slug = sanitize_title($nombre);
        $slug_base = $slug;
        $contador = 1;
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $tabla_grupos WHERE slug = %s", $slug))) {
            $slug = $slug_base . '-' . $contador++;
        }

        $insertado = $wpdb->insert($tabla_grupos, [
            'nombre' => $nombre,
            'slug' => $slug,
            'descripcion' => sanitize_textarea_field($params['descripcion'] ?? ''),
            'color' => sanitize_hex_color($params['color'] ?? '#2271b1') ?: '#2271b1',
            'creador_id' => $usuario_id,
            'tipo' => $tipo,
            'categoria' => sanitize_text_field($params['categoria'] ?? ''),
            'max_miembros' => $settings['max_miembros_por_grupo'],
            'miembros_count' => 1,
        ]);

        if (!$insertado) {
            return ['success' => false, 'error' => 'Error al crear el grupo.'];
        }

        $grupo_id = $wpdb->insert_id;

        // Añadir creador como admin
        $wpdb->insert($tabla_miembros, [
            'grupo_id' => $grupo_id,
            'usuario_id' => $usuario_id,
            'rol' => 'admin',
        ]);

        // Mensaje de sistema
        $this->crear_mensaje_sistema($grupo_id, 'grupo_creado', [
            'usuario_id' => $usuario_id,
            'usuario_nombre' => wp_get_current_user()->display_name,
        ]);

        do_action('flavor_gamificacion_agregar_puntos', $usuario_id, 10, 'crear_grupo_chat');

        return [
            'success' => true,
            'grupo_id' => $grupo_id,
            'slug' => $slug,
            'mensaje' => 'Grupo creado correctamente.',
        ];
    }

    /**
     * Acción: Unirse a grupo
     */
    private function action_unirse_grupo($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);
        if (!$grupo_id) {
            return ['success' => false, 'error' => 'ID de grupo inválido.'];
        }

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_invitaciones = $wpdb->prefix . 'flavor_chat_grupos_invitaciones';

        $grupo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_grupos WHERE id = %d AND estado = 'activo'",
            $grupo_id
        ));

        if (!$grupo) {
            return ['success' => false, 'error' => 'Grupo no encontrado.'];
        }

        // Verificar si ya es miembro
        $es_miembro = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        ));

        if ($es_miembro) {
            return ['success' => false, 'error' => 'Ya eres miembro de este grupo.'];
        }

        // Verificar límite de miembros
        if ($grupo->miembros_count >= $grupo->max_miembros) {
            return ['success' => false, 'error' => 'El grupo ha alcanzado el límite de miembros.'];
        }

        // Para grupos privados/secretos, verificar invitación
        if ($grupo->tipo !== 'publico') {
            $codigo = sanitize_text_field($params['codigo_invitacion'] ?? '');
            $invitacion = null;

            if ($codigo) {
                $invitacion = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $tabla_invitaciones
                     WHERE grupo_id = %d AND codigo = %s AND estado = 'pendiente'
                     AND (fecha_expiracion IS NULL OR fecha_expiracion > NOW())",
                    $grupo_id, $codigo
                ));
            } else {
                $invitacion = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $tabla_invitaciones
                     WHERE grupo_id = %d AND invitado_id = %d AND estado = 'pendiente'
                     AND (fecha_expiracion IS NULL OR fecha_expiracion > NOW())",
                    $grupo_id, $usuario_id
                ));
            }

            if (!$invitacion) {
                return ['success' => false, 'error' => 'Necesitas una invitación para unirte a este grupo.'];
            }

            // Marcar invitación como aceptada
            $wpdb->update($tabla_invitaciones, ['estado' => 'aceptada'], ['id' => $invitacion->id]);
        }

        // Añadir como miembro
        $wpdb->insert($tabla_miembros, [
            'grupo_id' => $grupo_id,
            'usuario_id' => $usuario_id,
            'rol' => 'miembro',
        ]);

        // Actualizar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_grupos SET miembros_count = miembros_count + 1 WHERE id = %d",
            $grupo_id
        ));

        // Mensaje de sistema
        $this->crear_mensaje_sistema($grupo_id, 'usuario_unido', [
            'usuario_id' => $usuario_id,
            'usuario_nombre' => wp_get_current_user()->display_name,
        ]);

        return ['success' => true, 'mensaje' => 'Te has unido al grupo.'];
    }

    /**
     * Acción: Salir del grupo
     */
    private function action_salir_grupo($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $miembro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        ));

        if (!$miembro) {
            return ['success' => false, 'error' => 'No eres miembro de este grupo.'];
        }

        // Si es el único admin, no puede salir
        if ($miembro->rol === 'admin') {
            $otros_admins = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_miembros
                 WHERE grupo_id = %d AND rol = 'admin' AND usuario_id != %d",
                $grupo_id, $usuario_id
            ));

            if (!$otros_admins) {
                return ['success' => false, 'error' => 'Debes asignar otro administrador antes de salir.'];
            }
        }

        $wpdb->delete($tabla_miembros, ['grupo_id' => $grupo_id, 'usuario_id' => $usuario_id]);

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_grupos SET miembros_count = GREATEST(0, miembros_count - 1) WHERE id = %d",
            $grupo_id
        ));

        $this->crear_mensaje_sistema($grupo_id, 'usuario_salio', [
            'usuario_id' => $usuario_id,
            'usuario_nombre' => wp_get_current_user()->display_name,
        ]);

        return ['success' => true, 'mensaje' => 'Has salido del grupo.'];
    }

    /**
     * Acción: Ver mensajes
     */
    private function action_mensajes($params) {
        $usuario_id = get_current_user_id();
        $grupo_id = intval($params['grupo_id'] ?? 0);

        if (!$this->usuario_puede_ver_grupo($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => 'No tienes acceso a este grupo.'];
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_reacciones = $wpdb->prefix . 'flavor_chat_grupos_reacciones';

        $limite = min(100, max(10, intval($params['limite'] ?? 50)));
        $antes_de = intval($params['antes_de'] ?? 0);

        $where = "m.grupo_id = %d AND m.hilo_padre IS NULL";
        $where_values = [$grupo_id];

        if ($antes_de) {
            $where .= " AND m.id < %d";
            $where_values[] = $antes_de;
        }

        $mensajes = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as autor_nombre
             FROM $tabla_mensajes m
             LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE $where
             ORDER BY m.id DESC
             LIMIT %d",
            array_merge($where_values, [$limite])
        ));

        $mensajes = array_reverse($mensajes);
        $resultado = [];

        foreach ($mensajes as $msg) {
            // Obtener reacciones agrupadas
            $reacciones = $wpdb->get_results($wpdb->prepare(
                "SELECT emoji, COUNT(*) as count,
                 GROUP_CONCAT(usuario_id) as usuarios
                 FROM $tabla_reacciones
                 WHERE mensaje_id = %d
                 GROUP BY emoji",
                $msg->id
            ));

            $reacciones_formato = [];
            foreach ($reacciones as $r) {
                $usuarios_ids = explode(',', $r->usuarios);
                $reacciones_formato[] = [
                    'emoji' => $r->emoji,
                    'count' => (int) $r->count,
                    'yo_reaccione' => in_array($usuario_id, $usuarios_ids),
                ];
            }

            $resultado[] = [
                'id' => (int) $msg->id,
                'usuario_id' => (int) $msg->usuario_id,
                'autor_nombre' => $msg->autor_nombre ?: 'Usuario',
                'autor_avatar' => get_avatar_url($msg->usuario_id, ['size' => 48]),
                'mensaje' => $msg->eliminado ? '' : $msg->mensaje,
                'mensaje_html' => $msg->eliminado ? '' : $this->formatear_mensaje($msg->mensaje),
                'tipo' => $msg->tipo,
                'adjuntos' => $msg->adjuntos ? json_decode($msg->adjuntos, true) : [],
                'responde_a' => $msg->responde_a ? (int) $msg->responde_a : null,
                'menciones' => $msg->menciones ? json_decode($msg->menciones, true) : [],
                'reacciones' => $reacciones_formato,
                'respuestas_count' => (int) $msg->respuestas_count,
                'editado' => (bool) $msg->editado,
                'eliminado' => (bool) $msg->eliminado,
                'fecha' => $msg->fecha_creacion,
                'fecha_humana' => $this->tiempo_relativo($msg->fecha_creacion),
                'es_mio' => $msg->usuario_id == $usuario_id,
            ];
        }

        // Marcar como leídos
        if ($usuario_id && !empty($resultado)) {
            $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
            $ultimo_id = end($resultado)['id'];
            $wpdb->update(
                $tabla_miembros,
                ['ultimo_mensaje_leido' => $ultimo_id],
                ['grupo_id' => $grupo_id, 'usuario_id' => $usuario_id]
            );
        }

        return [
            'success' => true,
            'mensajes' => $resultado,
            'hay_mas' => count($mensajes) === $limite,
        ];
    }

    /**
     * Acción: Enviar mensaje
     */
    private function action_enviar_mensaje($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);

        if (!$this->usuario_puede_escribir($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => 'No puedes escribir en este grupo.'];
        }

        $mensaje = trim($params['mensaje'] ?? '');
        if (empty($mensaje) && empty($params['adjuntos'])) {
            return ['success' => false, 'error' => 'El mensaje no puede estar vacío.'];
        }

        if (strlen($mensaje) > 5000) {
            return ['success' => false, 'error' => 'Mensaje demasiado largo (máximo 5000 caracteres).'];
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        // Detectar menciones
        $menciones = [];
        if (preg_match_all('/@(\w+)/', $mensaje, $matches)) {
            foreach ($matches[1] as $username) {
                $user = get_user_by('login', $username);
                if ($user) {
                    $menciones[] = $user->ID;
                }
            }
        }

        $tipo = 'texto';
        $adjuntos = null;
        if (!empty($params['adjuntos'])) {
            $adjuntos = json_encode($params['adjuntos']);
            $tipo = 'archivo';
        }

        $responde_a = !empty($params['responde_a']) ? intval($params['responde_a']) : null;

        $wpdb->insert($tabla_mensajes, [
            'grupo_id' => $grupo_id,
            'usuario_id' => $usuario_id,
            'mensaje' => sanitize_textarea_field($mensaje),
            'mensaje_html' => $this->formatear_mensaje($mensaje),
            'tipo' => $tipo,
            'adjuntos' => $adjuntos,
            'responde_a' => $responde_a,
            'menciones' => !empty($menciones) ? json_encode($menciones) : null,
        ]);

        $mensaje_id = $wpdb->insert_id;

        // Actualizar grupo
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_grupos SET
             mensajes_count = mensajes_count + 1,
             ultimo_mensaje_id = %d,
             fecha_actualizacion = NOW()
             WHERE id = %d",
            $mensaje_id, $grupo_id
        ));

        // Actualizar último leído del autor
        $wpdb->update(
            $tabla_miembros,
            ['ultimo_mensaje_leido' => $mensaje_id],
            ['grupo_id' => $grupo_id, 'usuario_id' => $usuario_id]
        );

        // Si responde a otro mensaje, incrementar contador de respuestas
        if ($responde_a) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_mensajes SET respuestas_count = respuestas_count + 1 WHERE id = %d",
                $responde_a
            ));
        }

        // Notificar menciones
        foreach ($menciones as $mencionado_id) {
            if ($mencionado_id != $usuario_id) {
                do_action('flavor_notificacion_enviar', $mencionado_id, 'chat_mencion', [
                    'grupo_id' => $grupo_id,
                    'mensaje_id' => $mensaje_id,
                    'autor_id' => $usuario_id,
                    'autor_nombre' => wp_get_current_user()->display_name,
                ]);
            }
        }

        // Puntos por participar
        do_action('flavor_gamificacion_agregar_puntos', $usuario_id, 1, 'mensaje_chat_grupo');

        return [
            'success' => true,
            'mensaje_id' => $mensaje_id,
            'mensaje' => [
                'id' => $mensaje_id,
                'usuario_id' => $usuario_id,
                'autor_nombre' => wp_get_current_user()->display_name,
                'autor_avatar' => get_avatar_url($usuario_id, ['size' => 48]),
                'mensaje' => $mensaje,
                'mensaje_html' => $this->formatear_mensaje($mensaje),
                'tipo' => $tipo,
                'adjuntos' => $params['adjuntos'] ?? [],
                'responde_a' => $responde_a,
                'menciones' => $menciones,
                'reacciones' => [],
                'editado' => false,
                'eliminado' => false,
                'fecha' => current_time('mysql'),
                'fecha_humana' => __('ahora', 'flavor-chat-ia'),
                'es_mio' => true,
            ],
        ];
    }

    /**
     * Acción: Info del grupo
     */
    private function action_info_grupo($params) {
        $usuario_id = get_current_user_id();
        $grupo_id = intval($params['grupo_id'] ?? 0);

        if (!$this->usuario_puede_ver_grupo($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => 'No tienes acceso a este grupo.'];
        }

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_fijados = $wpdb->prefix . 'flavor_chat_grupos_fijados';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        $grupo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_grupos WHERE id = %d",
            $grupo_id
        ));

        if (!$grupo) {
            return ['success' => false, 'error' => 'Grupo no encontrado.'];
        }

        // Obtener mi membresía
        $mi_membresia = null;
        if ($usuario_id) {
            $mi_membresia = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
                $grupo_id, $usuario_id
            ));
        }

        // Obtener admins y moderadores
        $admins = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_email
             FROM $tabla_miembros m
             JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE m.grupo_id = %d AND m.rol IN ('admin', 'moderador')
             ORDER BY m.rol DESC, m.fecha_ingreso ASC",
            $grupo_id
        ));

        // Mensajes fijados
        $fijados = $wpdb->get_results($wpdb->prepare(
            "SELECT f.*, m.mensaje, m.usuario_id, u.display_name as autor_nombre
             FROM $tabla_fijados f
             JOIN $tabla_mensajes m ON f.mensaje_id = m.id
             JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE f.grupo_id = %d
             ORDER BY f.fecha_creacion DESC",
            $grupo_id
        ));

        $creador = get_userdata($grupo->creador_id);

        return [
            'success' => true,
            'grupo' => [
                'id' => (int) $grupo->id,
                'nombre' => $grupo->nombre,
                'slug' => $grupo->slug,
                'descripcion' => $grupo->descripcion,
                'imagen_url' => $grupo->imagen_url,
                'color' => $grupo->color,
                'tipo' => $grupo->tipo,
                'categoria' => $grupo->categoria,
                'miembros_count' => (int) $grupo->miembros_count,
                'mensajes_count' => (int) $grupo->mensajes_count,
                'max_miembros' => (int) $grupo->max_miembros,
                'creador' => [
                    'id' => (int) $grupo->creador_id,
                    'nombre' => $creador ? $creador->display_name : 'Usuario',
                    'avatar' => get_avatar_url($grupo->creador_id, ['size' => 48]),
                ],
                'fecha_creacion' => $grupo->fecha_creacion,
                'permite_archivos' => (bool) $grupo->permite_archivos,
                'permite_encuestas' => (bool) $grupo->permite_encuestas,
                'solo_admins_publican' => (bool) $grupo->solo_admins_publican,
            ],
            'mi_membresia' => $mi_membresia ? [
                'rol' => $mi_membresia->rol,
                'notificaciones' => $mi_membresia->notificaciones,
                'silenciado_hasta' => $mi_membresia->silenciado_hasta,
                'fecha_ingreso' => $mi_membresia->fecha_ingreso,
            ] : null,
            'admins' => array_map(function($a) {
                return [
                    'id' => (int) $a->usuario_id,
                    'nombre' => $a->display_name,
                    'avatar' => get_avatar_url($a->usuario_id, ['size' => 48]),
                    'rol' => $a->rol,
                ];
            }, $admins),
            'mensajes_fijados' => array_map(function($f) {
                return [
                    'mensaje_id' => (int) $f->mensaje_id,
                    'mensaje' => wp_trim_words($f->mensaje, 20),
                    'autor_nombre' => $f->autor_nombre,
                ];
            }, $fijados),
        ];
    }

    /**
     * Acción: Miembros del grupo
     */
    private function action_miembros_grupo($params) {
        $usuario_id = get_current_user_id();
        $grupo_id = intval($params['grupo_id'] ?? 0);

        if (!$this->usuario_puede_ver_grupo($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => 'No tienes acceso a este grupo.'];
        }

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $miembros = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_email
             FROM $tabla_miembros m
             JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE m.grupo_id = %d
             ORDER BY FIELD(m.rol, 'admin', 'moderador', 'miembro'), m.fecha_ingreso ASC",
            $grupo_id
        ));

        return [
            'success' => true,
            'miembros' => array_map(function($m) {
                return [
                    'id' => (int) $m->usuario_id,
                    'nombre' => $m->display_name,
                    'avatar' => get_avatar_url($m->usuario_id, ['size' => 48]),
                    'rol' => $m->rol,
                    'fecha_ingreso' => $m->fecha_ingreso,
                    'online' => $this->usuario_online($m->usuario_id),
                ];
            }, $miembros),
            'total' => count($miembros),
        ];
    }

    /**
     * Acción: Invitar miembro
     */
    private function action_invitar_miembro($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);

        if (!$this->usuario_es_admin_o_mod($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => 'No tienes permisos para invitar.'];
        }

        global $wpdb;
        $tabla_invitaciones = $wpdb->prefix . 'flavor_chat_grupos_invitaciones';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $invitado_id = intval($params['usuario_id'] ?? 0);
        $email = sanitize_email($params['email'] ?? '');

        if (!$invitado_id && !$email) {
            return ['success' => false, 'error' => 'Debes especificar un usuario o email.'];
        }

        // Verificar si ya es miembro
        if ($invitado_id) {
            $ya_miembro = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
                $grupo_id, $invitado_id
            ));
            if ($ya_miembro) {
                return ['success' => false, 'error' => 'El usuario ya es miembro del grupo.'];
            }
        }

        $codigo = wp_generate_password(32, false);

        $wpdb->insert($tabla_invitaciones, [
            'grupo_id' => $grupo_id,
            'invitado_id' => $invitado_id ?: null,
            'invitado_email' => $email ?: null,
            'invitador_id' => $usuario_id,
            'codigo' => $codigo,
            'fecha_expiracion' => date('Y-m-d H:i:s', strtotime('+7 days')),
        ]);

        // Notificar al invitado
        if ($invitado_id) {
            do_action('flavor_notificacion_enviar', $invitado_id, 'chat_invitacion', [
                'grupo_id' => $grupo_id,
                'invitador_id' => $usuario_id,
                'codigo' => $codigo,
            ]);
        }

        return [
            'success' => true,
            'codigo' => $codigo,
            'mensaje' => 'Invitación enviada.',
        ];
    }

    /**
     * Acción: Expulsar miembro
     */
    private function action_expulsar_miembro($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);
        $expulsado_id = intval($params['usuario_id'] ?? 0);

        if (!$this->usuario_es_admin_o_mod($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => 'No tienes permisos para expulsar.'];
        }

        if ($expulsado_id === $usuario_id) {
            return ['success' => false, 'error' => 'No puedes expulsarte a ti mismo.'];
        }

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        // No permitir expulsar admins si no eres admin
        $mi_rol = $this->obtener_rol_usuario($usuario_id, $grupo_id);
        $su_rol = $this->obtener_rol_usuario($expulsado_id, $grupo_id);

        if ($su_rol === 'admin' && $mi_rol !== 'admin') {
            return ['success' => false, 'error' => 'No puedes expulsar a un administrador.'];
        }

        $wpdb->delete($tabla_miembros, ['grupo_id' => $grupo_id, 'usuario_id' => $expulsado_id]);

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_grupos SET miembros_count = GREATEST(0, miembros_count - 1) WHERE id = %d",
            $grupo_id
        ));

        $expulsado = get_userdata($expulsado_id);
        $this->crear_mensaje_sistema($grupo_id, 'usuario_expulsado', [
            'usuario_id' => $expulsado_id,
            'usuario_nombre' => $expulsado ? $expulsado->display_name : 'Usuario',
            'por_id' => $usuario_id,
            'por_nombre' => wp_get_current_user()->display_name,
        ]);

        return ['success' => true, 'mensaje' => 'Miembro expulsado.'];
    }

    /**
     * Acción: Cambiar rol
     */
    private function action_cambiar_rol($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);
        $miembro_id = intval($params['usuario_id'] ?? 0);
        $nuevo_rol = $params['rol'] ?? '';

        if (!in_array($nuevo_rol, ['miembro', 'moderador', 'admin'])) {
            return ['success' => false, 'error' => 'Rol inválido.'];
        }

        if (!$this->usuario_es_admin($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => 'Solo los administradores pueden cambiar roles.'];
        }

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $wpdb->update(
            $tabla_miembros,
            ['rol' => $nuevo_rol],
            ['grupo_id' => $grupo_id, 'usuario_id' => $miembro_id]
        );

        return ['success' => true, 'mensaje' => 'Rol actualizado.'];
    }

    /**
     * Acción: Silenciar grupo
     */
    private function action_silenciar_grupo($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);
        $horas = intval($params['duracion_horas'] ?? 8);

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $silenciado_hasta = $horas > 0 ? date('Y-m-d H:i:s', strtotime("+{$horas} hours")) : null;

        $wpdb->update(
            $tabla_miembros,
            ['silenciado_hasta' => $silenciado_hasta],
            ['grupo_id' => $grupo_id, 'usuario_id' => $usuario_id]
        );

        return [
            'success' => true,
            'silenciado_hasta' => $silenciado_hasta,
            'mensaje' => $horas > 0 ? "Notificaciones silenciadas por {$horas} horas." : 'Notificaciones activadas.',
        ];
    }

    /**
     * Acción: Buscar mensajes
     */
    private function action_buscar_mensajes($params) {
        $usuario_id = get_current_user_id();
        $grupo_id = intval($params['grupo_id'] ?? 0);

        if (!$this->usuario_puede_ver_grupo($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => 'No tienes acceso a este grupo.'];
        }

        $query = sanitize_text_field($params['query'] ?? '');
        if (strlen($query) < 3) {
            return ['success' => false, 'error' => 'La búsqueda debe tener al menos 3 caracteres.'];
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as autor_nombre
             FROM $tabla_mensajes m
             LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE m.grupo_id = %d
             AND m.eliminado = 0
             AND MATCH(m.mensaje) AGAINST(%s IN NATURAL LANGUAGE MODE)
             ORDER BY m.fecha_creacion DESC
             LIMIT 50",
            $grupo_id, $query
        ));

        return [
            'success' => true,
            'resultados' => array_map(function($m) {
                return [
                    'id' => (int) $m->id,
                    'mensaje' => wp_trim_words($m->mensaje, 30),
                    'autor_nombre' => $m->autor_nombre,
                    'fecha' => $m->fecha_creacion,
                    'fecha_humana' => $this->tiempo_relativo($m->fecha_creacion),
                ];
            }, $resultados),
            'total' => count($resultados),
        ];
    }

    /**
     * Acción: Crear encuesta
     */
    private function action_crear_encuesta($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        $grupo_id = intval($params['grupo_id'] ?? 0);

        if (!$this->usuario_puede_escribir($usuario_id, $grupo_id)) {
            return ['success' => false, 'error' => 'No puedes crear encuestas en este grupo.'];
        }

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        $grupo = $wpdb->get_row($wpdb->prepare(
            "SELECT permite_encuestas FROM $tabla_grupos WHERE id = %d",
            $grupo_id
        ));

        if (!$grupo || !$grupo->permite_encuestas) {
            return ['success' => false, 'error' => 'Las encuestas no están permitidas en este grupo.'];
        }

        $pregunta = sanitize_text_field($params['pregunta'] ?? '');
        $opciones = $params['opciones'] ?? [];

        if (strlen($pregunta) < 5) {
            return ['success' => false, 'error' => 'La pregunta debe tener al menos 5 caracteres.'];
        }

        if (!is_array($opciones) || count($opciones) < 2) {
            return ['success' => false, 'error' => 'La encuesta debe tener al menos 2 opciones.'];
        }

        $opciones = array_map('sanitize_text_field', array_slice($opciones, 0, 10));

        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_encuestas = $wpdb->prefix . 'flavor_chat_grupos_encuestas';

        // Crear mensaje de encuesta
        $wpdb->insert($tabla_mensajes, [
            'grupo_id' => $grupo_id,
            'usuario_id' => $usuario_id,
            'mensaje' => $pregunta,
            'tipo' => 'encuesta',
        ]);
        $mensaje_id = $wpdb->insert_id;

        // Crear encuesta
        $wpdb->insert($tabla_encuestas, [
            'mensaje_id' => $mensaje_id,
            'grupo_id' => $grupo_id,
            'pregunta' => $pregunta,
            'opciones' => json_encode($opciones),
            'multiple' => !empty($params['multiple']) ? 1 : 0,
            'anonima' => !empty($params['anonima']) ? 1 : 0,
        ]);

        $encuesta_id = $wpdb->insert_id;

        // Actualizar grupo
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_grupos SET mensajes_count = mensajes_count + 1, fecha_actualizacion = NOW() WHERE id = %d",
            $grupo_id
        ));

        return [
            'success' => true,
            'encuesta_id' => $encuesta_id,
            'mensaje_id' => $mensaje_id,
            'mensaje' => 'Encuesta creada.',
        ];
    }

    /**
     * Acción: Votar encuesta
     */
    private function action_votar_encuesta($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        $encuesta_id = intval($params['encuesta_id'] ?? 0);
        $opcion = intval($params['opcion'] ?? -1);

        global $wpdb;
        $tabla_encuestas = $wpdb->prefix . 'flavor_chat_grupos_encuestas';
        $tabla_votos = $wpdb->prefix . 'flavor_chat_grupos_votos';

        $encuesta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_encuestas WHERE id = %d",
            $encuesta_id
        ));

        if (!$encuesta) {
            return ['success' => false, 'error' => 'Encuesta no encontrada.'];
        }

        if ($encuesta->cerrada) {
            return ['success' => false, 'error' => 'La encuesta está cerrada.'];
        }

        $opciones = json_decode($encuesta->opciones, true);
        if ($opcion < 0 || $opcion >= count($opciones)) {
            return ['success' => false, 'error' => 'Opción inválida.'];
        }

        // Si no es múltiple, eliminar voto anterior
        if (!$encuesta->multiple) {
            $wpdb->delete($tabla_votos, [
                'encuesta_id' => $encuesta_id,
                'usuario_id' => $usuario_id,
            ]);
        }

        // Verificar si ya votó esta opción
        $ya_voto = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_votos WHERE encuesta_id = %d AND usuario_id = %d AND opcion_index = %d",
            $encuesta_id, $usuario_id, $opcion
        ));

        if ($ya_voto) {
            // Quitar voto
            $wpdb->delete($tabla_votos, ['id' => $ya_voto]);
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_encuestas SET votos_totales = GREATEST(0, votos_totales - 1) WHERE id = %d",
                $encuesta_id
            ));
        } else {
            // Añadir voto
            $wpdb->insert($tabla_votos, [
                'encuesta_id' => $encuesta_id,
                'usuario_id' => $usuario_id,
                'opcion_index' => $opcion,
            ]);
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_encuestas SET votos_totales = votos_totales + 1 WHERE id = %d",
                $encuesta_id
            ));
        }

        // Obtener resultados actualizados
        $votos = $wpdb->get_results($wpdb->prepare(
            "SELECT opcion_index, COUNT(*) as count FROM $tabla_votos WHERE encuesta_id = %d GROUP BY opcion_index",
            $encuesta_id
        ));

        $resultados = array_fill(0, count($opciones), 0);
        foreach ($votos as $v) {
            $resultados[$v->opcion_index] = (int) $v->count;
        }

        return [
            'success' => true,
            'resultados' => $resultados,
            'mi_voto' => $ya_voto ? null : $opcion,
        ];
    }

    /**
     * Acción: Reaccionar a mensaje
     */
    private function action_reaccionar($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        $mensaje_id = intval($params['mensaje_id'] ?? 0);
        $emoji = sanitize_text_field($params['emoji'] ?? '');

        if (!$emoji) {
            return ['success' => false, 'error' => 'Emoji inválido.'];
        }

        global $wpdb;
        $tabla_reacciones = $wpdb->prefix . 'flavor_chat_grupos_reacciones';
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        // Verificar que existe el mensaje
        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT grupo_id FROM $tabla_mensajes WHERE id = %d AND eliminado = 0",
            $mensaje_id
        ));

        if (!$mensaje) {
            return ['success' => false, 'error' => 'Mensaje no encontrado.'];
        }

        // Verificar acceso al grupo
        if (!$this->usuario_puede_ver_grupo($usuario_id, $mensaje->grupo_id)) {
            return ['success' => false, 'error' => 'No tienes acceso a este grupo.'];
        }

        // Toggle reacción
        $existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_reacciones WHERE mensaje_id = %d AND usuario_id = %d AND emoji = %s",
            $mensaje_id, $usuario_id, $emoji
        ));

        if ($existente) {
            $wpdb->delete($tabla_reacciones, ['id' => $existente]);
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_mensajes SET reacciones_count = GREATEST(0, reacciones_count - 1) WHERE id = %d",
                $mensaje_id
            ));
            $agregada = false;
        } else {
            $wpdb->insert($tabla_reacciones, [
                'mensaje_id' => $mensaje_id,
                'usuario_id' => $usuario_id,
                'emoji' => $emoji,
            ]);
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_mensajes SET reacciones_count = reacciones_count + 1 WHERE id = %d",
                $mensaje_id
            ));
            $agregada = true;
        }

        return [
            'success' => true,
            'agregada' => $agregada,
            'emoji' => $emoji,
        ];
    }

    /**
     * Acción: Fijar mensaje
     */
    private function action_fijar_mensaje($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => 'Debes iniciar sesión.'];
        }

        $mensaje_id = intval($params['mensaje_id'] ?? 0);

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';
        $tabla_fijados = $wpdb->prefix . 'flavor_chat_grupos_fijados';

        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT grupo_id FROM $tabla_mensajes WHERE id = %d",
            $mensaje_id
        ));

        if (!$mensaje) {
            return ['success' => false, 'error' => 'Mensaje no encontrado.'];
        }

        if (!$this->usuario_es_admin_o_mod($usuario_id, $mensaje->grupo_id)) {
            return ['success' => false, 'error' => 'No tienes permisos para fijar mensajes.'];
        }

        // Toggle fijado
        $fijado = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_fijados WHERE grupo_id = %d AND mensaje_id = %d",
            $mensaje->grupo_id, $mensaje_id
        ));

        if ($fijado) {
            $wpdb->delete($tabla_fijados, ['id' => $fijado]);
            $esta_fijado = false;
        } else {
            $wpdb->insert($tabla_fijados, [
                'grupo_id' => $mensaje->grupo_id,
                'mensaje_id' => $mensaje_id,
                'fijado_por' => $usuario_id,
            ]);
            $esta_fijado = true;
        }

        return [
            'success' => true,
            'fijado' => $esta_fijado,
        ];
    }

    // ==================== REST API ====================

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/chat-grupos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mis_grupos'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/explorar', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_explorar_grupos'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_info_grupo'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/(?P<id>\d+)/mensajes', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_mensajes'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/(?P<id>\d+)/mensajes', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_enviar_mensaje'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/(?P<id>\d+)/miembros', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_miembros'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/crear', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_crear_grupo'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/(?P<id>\d+)/unirse', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_unirse'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/(?P<id>\d+)/salir', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_salir'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/(?P<id>\d+)/typing', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_typing'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor/v1', '/chat-grupos/mensaje/(?P<id>\d+)/reaccion', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_reaccionar'],
            'permission_callback' => 'is_user_logged_in',
        ]);
    }

    public function rest_mis_grupos($request) {
        return rest_ensure_response($this->action_mis_grupos([]));
    }

    public function rest_explorar_grupos($request) {
        return rest_ensure_response($this->action_grupos_publicos([
            'categoria' => $request->get_param('categoria'),
            'busqueda' => $request->get_param('busqueda'),
            'pagina' => $request->get_param('pagina'),
        ]));
    }

    public function rest_info_grupo($request) {
        return rest_ensure_response($this->action_info_grupo([
            'grupo_id' => $request->get_param('id'),
        ]));
    }

    public function rest_mensajes($request) {
        return rest_ensure_response($this->action_mensajes([
            'grupo_id' => $request->get_param('id'),
            'antes_de' => $request->get_param('antes_de'),
            'limite' => $request->get_param('limite'),
        ]));
    }

    public function rest_enviar_mensaje($request) {
        return rest_ensure_response($this->action_enviar_mensaje([
            'grupo_id' => $request->get_param('id'),
            'mensaje' => $request->get_param('mensaje'),
            'responde_a' => $request->get_param('responde_a'),
            'adjuntos' => $request->get_param('adjuntos'),
        ]));
    }

    public function rest_miembros($request) {
        return rest_ensure_response($this->action_miembros_grupo([
            'grupo_id' => $request->get_param('id'),
        ]));
    }

    public function rest_crear_grupo($request) {
        return rest_ensure_response($this->action_crear_grupo([
            'nombre' => $request->get_param('nombre'),
            'descripcion' => $request->get_param('descripcion'),
            'tipo' => $request->get_param('tipo'),
            'categoria' => $request->get_param('categoria'),
            'color' => $request->get_param('color'),
        ]));
    }

    public function rest_unirse($request) {
        return rest_ensure_response($this->action_unirse_grupo([
            'grupo_id' => $request->get_param('id'),
            'codigo_invitacion' => $request->get_param('codigo'),
        ]));
    }

    public function rest_salir($request) {
        return rest_ensure_response($this->action_salir_grupo([
            'grupo_id' => $request->get_param('id'),
        ]));
    }

    public function rest_typing($request) {
        $usuario_id = get_current_user_id();
        $grupo_id = $request->get_param('id');

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $wpdb->update($tabla_miembros, [
            'escribiendo' => 1,
            'escribiendo_timestamp' => current_time('mysql'),
        ], [
            'grupo_id' => $grupo_id,
            'usuario_id' => $usuario_id,
        ]);

        return rest_ensure_response(['success' => true]);
    }

    public function rest_reaccionar($request) {
        return rest_ensure_response($this->action_reaccionar([
            'mensaje_id' => $request->get_param('id'),
            'emoji' => $request->get_param('emoji'),
        ]));
    }

    // ==================== AJAX Handlers ====================

    public function ajax_enviar_mensaje() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');

        $resultado = $this->action_enviar_mensaje([
            'grupo_id' => intval($_POST['grupo_id'] ?? 0),
            'mensaje' => $_POST['mensaje'] ?? '',
            'responde_a' => $_POST['responde_a'] ?? null,
        ]);

        wp_send_json($resultado);
    }

    public function ajax_obtener_mensajes() {
        $resultado = $this->action_mensajes([
            'grupo_id' => intval($_GET['grupo_id'] ?? 0),
            'antes_de' => intval($_GET['antes_de'] ?? 0),
            'limite' => intval($_GET['limite'] ?? 50),
        ]);

        wp_send_json($resultado);
    }

    public function ajax_marcar_leido() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');

        $usuario_id = get_current_user_id();
        $grupo_id = intval($_POST['grupo_id'] ?? 0);
        $mensaje_id = intval($_POST['mensaje_id'] ?? 0);

        if (!$usuario_id || !$grupo_id || !$mensaje_id) {
            wp_send_json_error('Parámetros inválidos');
        }

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $wpdb->update(
            $tabla_miembros,
            ['ultimo_mensaje_leido' => $mensaje_id],
            ['grupo_id' => $grupo_id, 'usuario_id' => $usuario_id]
        );

        wp_send_json_success();
    }

    public function ajax_typing() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');

        $usuario_id = get_current_user_id();
        $grupo_id = intval($_POST['grupo_id'] ?? 0);

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $wpdb->update($tabla_miembros, [
            'escribiendo' => 1,
            'escribiendo_timestamp' => current_time('mysql'),
        ], [
            'grupo_id' => $grupo_id,
            'usuario_id' => $usuario_id,
        ]);

        // Obtener quién está escribiendo
        $escribiendo = $wpdb->get_results($wpdb->prepare(
            "SELECT m.usuario_id, u.display_name
             FROM $tabla_miembros m
             JOIN {$wpdb->users} u ON m.usuario_id = u.ID
             WHERE m.grupo_id = %d
             AND m.escribiendo = 1
             AND m.escribiendo_timestamp > DATE_SUB(NOW(), INTERVAL 5 SECOND)
             AND m.usuario_id != %d",
            $grupo_id, $usuario_id
        ));

        wp_send_json_success([
            'escribiendo' => array_map(function($e) {
                return ['id' => $e->usuario_id, 'nombre' => $e->display_name];
            }, $escribiendo),
        ]);
    }

    public function ajax_reaccionar() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');

        $resultado = $this->action_reaccionar([
            'mensaje_id' => intval($_POST['mensaje_id'] ?? 0),
            'emoji' => $_POST['emoji'] ?? '',
        ]);

        wp_send_json($resultado);
    }

    public function ajax_buscar_mensajes() {
        $resultado = $this->action_buscar_mensajes([
            'grupo_id' => intval($_GET['grupo_id'] ?? 0),
            'query' => $_GET['query'] ?? '',
        ]);

        wp_send_json($resultado);
    }

    public function ajax_subir_archivo() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Debes iniciar sesión');
        }

        $grupo_id = intval($_POST['grupo_id'] ?? 0);

        if (!$this->usuario_puede_escribir(get_current_user_id(), $grupo_id)) {
            wp_send_json_error('No puedes subir archivos a este grupo');
        }

        if (empty($_FILES['archivo'])) {
            wp_send_json_error('No se recibió ningún archivo');
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $settings = $this->get_settings();
        $max_size = $settings['max_archivo_mb'] * 1024 * 1024;

        if ($_FILES['archivo']['size'] > $max_size) {
            wp_send_json_error("El archivo excede el tamaño máximo de {$settings['max_archivo_mb']} MB");
        }

        $attachment_id = media_handle_upload('archivo', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error($attachment_id->get_error_message());
        }

        $url = wp_get_attachment_url($attachment_id);
        $tipo = wp_check_filetype($url)['ext'];

        wp_send_json_success([
            'id' => $attachment_id,
            'url' => $url,
            'nombre' => basename($url),
            'tipo' => $tipo,
            'es_imagen' => wp_attachment_is_image($attachment_id),
        ]);
    }

    public function ajax_crear_grupo() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');
        $resultado = $this->action_crear_grupo($_POST);
        wp_send_json($resultado);
    }

    public function ajax_unirse_grupo() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');
        $resultado = $this->action_unirse_grupo($_POST);
        wp_send_json($resultado);
    }

    public function ajax_salir_grupo() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');
        $resultado = $this->action_salir_grupo($_POST);
        wp_send_json($resultado);
    }

    public function ajax_invitar() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');
        $resultado = $this->action_invitar_miembro($_POST);
        wp_send_json($resultado);
    }

    public function ajax_expulsar() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');
        $resultado = $this->action_expulsar_miembro($_POST);
        wp_send_json($resultado);
    }

    public function ajax_cambiar_rol() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');
        $resultado = $this->action_cambiar_rol($_POST);
        wp_send_json($resultado);
    }

    public function ajax_actualizar_config() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');

        $usuario_id = get_current_user_id();
        $grupo_id = intval($_POST['grupo_id'] ?? 0);

        if (!$this->usuario_es_admin($usuario_id, $grupo_id)) {
            wp_send_json_error('No tienes permisos');
        }

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        $datos = [];
        if (isset($_POST['nombre'])) $datos['nombre'] = sanitize_text_field($_POST['nombre']);
        if (isset($_POST['descripcion'])) $datos['descripcion'] = sanitize_textarea_field($_POST['descripcion']);
        if (isset($_POST['tipo'])) $datos['tipo'] = in_array($_POST['tipo'], ['publico', 'privado', 'secreto']) ? $_POST['tipo'] : 'publico';
        if (isset($_POST['solo_admins_publican'])) $datos['solo_admins_publican'] = intval($_POST['solo_admins_publican']);
        if (isset($_POST['permite_archivos'])) $datos['permite_archivos'] = intval($_POST['permite_archivos']);
        if (isset($_POST['permite_encuestas'])) $datos['permite_encuestas'] = intval($_POST['permite_encuestas']);

        if (!empty($datos)) {
            $wpdb->update($tabla_grupos, $datos, ['id' => $grupo_id]);
        }

        wp_send_json_success();
    }

    public function ajax_crear_encuesta() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');
        $resultado = $this->action_crear_encuesta([
            'grupo_id' => intval($_POST['grupo_id'] ?? 0),
            'pregunta' => $_POST['pregunta'] ?? '',
            'opciones' => $_POST['opciones'] ?? [],
            'multiple' => !empty($_POST['multiple']),
        ]);
        wp_send_json($resultado);
    }

    public function ajax_votar_encuesta() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');
        $resultado = $this->action_votar_encuesta([
            'encuesta_id' => intval($_POST['encuesta_id'] ?? 0),
            'opcion' => intval($_POST['opcion'] ?? -1),
        ]);
        wp_send_json($resultado);
    }

    public function ajax_eliminar_mensaje() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');

        $usuario_id = get_current_user_id();
        $mensaje_id = intval($_POST['mensaje_id'] ?? 0);

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_mensajes WHERE id = %d",
            $mensaje_id
        ));

        if (!$mensaje) {
            wp_send_json_error('Mensaje no encontrado');
        }

        // Solo el autor o admin/mod puede eliminar
        if ($mensaje->usuario_id != $usuario_id && !$this->usuario_es_admin_o_mod($usuario_id, $mensaje->grupo_id)) {
            wp_send_json_error('No tienes permisos');
        }

        $wpdb->update($tabla_mensajes, ['eliminado' => 1], ['id' => $mensaje_id]);

        wp_send_json_success();
    }

    public function ajax_editar_mensaje() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');

        $usuario_id = get_current_user_id();
        $mensaje_id = intval($_POST['mensaje_id'] ?? 0);
        $nuevo_mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');

        if (empty($nuevo_mensaje)) {
            wp_send_json_error('El mensaje no puede estar vacío');
        }

        global $wpdb;
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_mensajes WHERE id = %d",
            $mensaje_id
        ));

        if (!$mensaje || $mensaje->usuario_id != $usuario_id) {
            wp_send_json_error('No puedes editar este mensaje');
        }

        $wpdb->update($tabla_mensajes, [
            'mensaje' => $nuevo_mensaje,
            'mensaje_html' => $this->formatear_mensaje($nuevo_mensaje),
            'editado' => 1,
            'fecha_edicion' => current_time('mysql'),
        ], ['id' => $mensaje_id]);

        wp_send_json_success([
            'mensaje' => $nuevo_mensaje,
            'mensaje_html' => $this->formatear_mensaje($nuevo_mensaje),
        ]);
    }

    public function ajax_fijar_mensaje() {
        check_ajax_referer('flavor_chat_grupos', 'nonce');
        $resultado = $this->action_fijar_mensaje([
            'mensaje_id' => intval($_POST['mensaje_id'] ?? 0),
        ]);
        wp_send_json($resultado);
    }

    // ==================== Shortcodes ====================

    public function shortcode_chat_grupos($atts) {
        if (!is_user_logged_in()) {
            return '<div class="cg-login-required"><p>' . __('Inicia sesión para acceder al chat de grupos.', 'flavor-chat-ia') . '</p></div>';
        }

        ob_start();
        ?>
        <div id="flavor-chat-grupos-app" class="cg-app" data-user-id="<?php echo get_current_user_id(); ?>">
            <div class="cg-sidebar">
                <div class="cg-sidebar-header">
                    <h3><?php _e('Mis Grupos', 'flavor-chat-ia'); ?></h3>
                    <button class="cg-btn-crear" title="<?php esc_attr_e('Crear grupo', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-plus-alt2"></span>
                    </button>
                </div>
                <div class="cg-grupos-lista" id="cg-mis-grupos">
                    <div class="cg-loading"><?php _e('Cargando...', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="cg-sidebar-footer">
                    <a href="#explorar" class="cg-link-explorar">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Explorar grupos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
            <div class="cg-main">
                <div class="cg-no-grupo-seleccionado">
                    <span class="dashicons dashicons-format-chat"></span>
                    <h3><?php _e('Selecciona un grupo', 'flavor-chat-ia'); ?></h3>
                    <p><?php _e('Elige un grupo de la lista para ver los mensajes', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="cg-chat-container" style="display:none;">
                    <div class="cg-chat-header">
                        <div class="cg-grupo-info">
                            <div class="cg-grupo-avatar"></div>
                            <div class="cg-grupo-datos">
                                <h4 class="cg-grupo-nombre"></h4>
                                <span class="cg-grupo-miembros"></span>
                            </div>
                        </div>
                        <div class="cg-chat-acciones">
                            <button class="cg-btn-buscar" title="<?php esc_attr_e('Buscar', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-search"></span>
                            </button>
                            <button class="cg-btn-info" title="<?php esc_attr_e('Info del grupo', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-info"></span>
                            </button>
                        </div>
                    </div>
                    <div class="cg-mensajes-container" id="cg-mensajes">
                        <div class="cg-loading"><?php _e('Cargando mensajes...', 'flavor-chat-ia'); ?></div>
                    </div>
                    <div class="cg-escribiendo" style="display:none;"></div>
                    <div class="cg-input-container">
                        <button class="cg-btn-adjuntar" title="<?php esc_attr_e('Adjuntar archivo', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-paperclip"></span>
                        </button>
                        <div class="cg-input-wrapper">
                            <textarea id="cg-mensaje-input" placeholder="<?php esc_attr_e('Escribe un mensaje...', 'flavor-chat-ia'); ?>" rows="1"></textarea>
                        </div>
                        <button class="cg-btn-enviar" title="<?php esc_attr_e('Enviar', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-arrow-right-alt"></span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="cg-panel-info" style="display:none;">
                <!-- Panel lateral de info del grupo -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_chat_grupo($atts) {
        $atts = shortcode_atts(['id' => 0, 'slug' => ''], $atts);

        $grupo_id = intval($atts['id']);
        if (!$grupo_id && $atts['slug']) {
            global $wpdb;
            $grupo_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}flavor_chat_grupos WHERE slug = %s",
                sanitize_title($atts['slug'])
            ));
        }

        if (!$grupo_id) {
            return '<p>' . __('Grupo no encontrado.', 'flavor-chat-ia') . '</p>';
        }

        return $this->shortcode_chat_grupos([]) . "<script>document.addEventListener('DOMContentLoaded', function() { if(window.FlavorChatGrupos) FlavorChatGrupos.abrirGrupo({$grupo_id}); });</script>";
    }

    public function shortcode_grupos_lista($atts) {
        $atts = shortcode_atts([
            'limite' => 6,
            'categoria' => '',
            'columnas' => 3,
        ], $atts);

        $resultado = $this->action_grupos_publicos([
            'categoria' => $atts['categoria'],
            'pagina' => 1,
        ]);

        if (!$resultado['success'] || empty($resultado['grupos'])) {
            return '<p>' . __('No hay grupos disponibles.', 'flavor-chat-ia') . '</p>';
        }

        $grupos = array_slice($resultado['grupos'], 0, intval($atts['limite']));

        ob_start();
        ?>
        <div class="cg-grupos-grid columnas-<?php echo intval($atts['columnas']); ?>">
            <?php foreach ($grupos as $grupo): ?>
            <div class="cg-grupo-card">
                <div class="cg-grupo-card-header" style="background-color: <?php echo esc_attr($grupo['color']); ?>">
                    <?php if ($grupo['imagen_url']): ?>
                        <img src="<?php echo esc_url($grupo['imagen_url']); ?>" alt="">
                    <?php endif; ?>
                </div>
                <div class="cg-grupo-card-body">
                    <h4><?php echo esc_html($grupo['nombre']); ?></h4>
                    <p><?php echo esc_html($grupo['descripcion']); ?></p>
                    <div class="cg-grupo-card-meta">
                        <span><span class="dashicons dashicons-groups"></span> <?php echo $grupo['miembros']; ?></span>
                        <span><span class="dashicons dashicons-admin-comments"></span> <?php echo $grupo['mensajes']; ?></span>
                    </div>
                </div>
                <div class="cg-grupo-card-footer">
                    <?php if ($grupo['es_miembro']): ?>
                        <a href="<?php echo esc_url(add_query_arg('grupo', $grupo['slug'], get_permalink())); ?>" class="cg-btn cg-btn-primary"><?php _e('Abrir', 'flavor-chat-ia'); ?></a>
                    <?php else: ?>
                        <button class="cg-btn cg-btn-outline cg-btn-unirse" data-id="<?php echo $grupo['id']; ?>"><?php _e('Unirse', 'flavor-chat-ia'); ?></button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_grupos_explorar($atts) {
        ob_start();
        ?>
        <div id="cg-explorar" class="cg-explorar">
            <div class="cg-explorar-header">
                <h2><?php _e('Explorar Grupos', 'flavor-chat-ia'); ?></h2>
                <div class="cg-explorar-busqueda">
                    <input type="text" id="cg-buscar-grupos" placeholder="<?php esc_attr_e('Buscar grupos...', 'flavor-chat-ia'); ?>">
                    <select id="cg-filtro-categoria">
                        <option value=""><?php _e('Todas las categorías', 'flavor-chat-ia'); ?></option>
                        <option value="general"><?php _e('General', 'flavor-chat-ia'); ?></option>
                        <option value="deportes"><?php _e('Deportes', 'flavor-chat-ia'); ?></option>
                        <option value="cultura"><?php _e('Cultura', 'flavor-chat-ia'); ?></option>
                        <option value="vecinal"><?php _e('Vecinal', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
            </div>
            <div id="cg-explorar-resultados" class="cg-grupos-grid columnas-3">
                <div class="cg-loading"><?php _e('Cargando grupos...', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="cg-explorar-paginacion" id="cg-paginacion"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_crear_grupo($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Inicia sesión para crear un grupo.', 'flavor-chat-ia') . '</p>';
        }

        $settings = $this->get_settings();
        if (!$settings['permite_crear_grupos']) {
            return '<p>' . __('La creación de grupos está deshabilitada.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="cg-crear-grupo-form">
            <h3><?php _e('Crear Nuevo Grupo', 'flavor-chat-ia'); ?></h3>
            <form id="cg-form-crear">
                <div class="cg-form-field">
                    <label for="cg-nombre"><?php _e('Nombre del grupo', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" id="cg-nombre" name="nombre" required minlength="3" maxlength="100">
                </div>
                <div class="cg-form-field">
                    <label for="cg-descripcion"><?php _e('Descripción', 'flavor-chat-ia'); ?></label>
                    <textarea id="cg-descripcion" name="descripcion" rows="3" maxlength="500"></textarea>
                </div>
                <div class="cg-form-row">
                    <div class="cg-form-field">
                        <label for="cg-tipo"><?php _e('Tipo', 'flavor-chat-ia'); ?></label>
                        <select id="cg-tipo" name="tipo">
                            <option value="publico"><?php _e('Público', 'flavor-chat-ia'); ?></option>
                            <?php if ($settings['permite_grupos_privados']): ?>
                            <option value="privado"><?php _e('Privado', 'flavor-chat-ia'); ?></option>
                            <option value="secreto"><?php _e('Secreto', 'flavor-chat-ia'); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="cg-form-field">
                        <label for="cg-categoria"><?php _e('Categoría', 'flavor-chat-ia'); ?></label>
                        <select id="cg-categoria" name="categoria">
                            <option value=""><?php _e('Sin categoría', 'flavor-chat-ia'); ?></option>
                            <option value="general"><?php _e('General', 'flavor-chat-ia'); ?></option>
                            <option value="deportes"><?php _e('Deportes', 'flavor-chat-ia'); ?></option>
                            <option value="cultura"><?php _e('Cultura', 'flavor-chat-ia'); ?></option>
                            <option value="vecinal"><?php _e('Vecinal', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="cg-form-field">
                    <label for="cg-color"><?php _e('Color', 'flavor-chat-ia'); ?></label>
                    <input type="color" id="cg-color" name="color" value="#2271b1">
                </div>
                <div class="cg-form-actions">
                    <button type="submit" class="cg-btn cg-btn-primary"><?php _e('Crear Grupo', 'flavor-chat-ia'); ?></button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    // ==================== Dashboard Integration ====================

    public function add_dashboard_tab($tabs) {
        $tabs['chat-grupos'] = [
            'label' => __('Mis Grupos', 'flavor-chat-ia'),
            'icon' => 'format-chat',
            'callback' => [$this, 'render_dashboard_tab'],
            'orden' => 30,
        ];
        return $tabs;
    }

    public function render_dashboard_tab() {
        echo do_shortcode('[flavor_chat_grupos]');
    }

    // ==================== Helper Methods ====================

    private function usuario_puede_ver_grupo($usuario_id, $grupo_id) {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $grupo = $wpdb->get_row($wpdb->prepare(
            "SELECT tipo FROM $tabla_grupos WHERE id = %d AND estado = 'activo'",
            $grupo_id
        ));

        if (!$grupo) return false;

        // Grupos públicos pueden ser vistos por todos
        if ($grupo->tipo === 'publico') return true;

        // Para privados/secretos, debe ser miembro
        if (!$usuario_id) return false;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        )) > 0;
    }

    private function usuario_puede_escribir($usuario_id, $grupo_id) {
        if (!$usuario_id) return false;

        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';

        $miembro = $wpdb->get_row($wpdb->prepare(
            "SELECT m.rol, g.solo_admins_publican
             FROM $tabla_miembros m
             JOIN $tabla_grupos g ON m.grupo_id = g.id
             WHERE m.grupo_id = %d AND m.usuario_id = %d AND g.estado = 'activo'",
            $grupo_id, $usuario_id
        ));

        if (!$miembro) return false;

        // Si solo admins pueden publicar
        if ($miembro->solo_admins_publican && !in_array($miembro->rol, ['admin', 'moderador'])) {
            return false;
        }

        return true;
    }

    private function usuario_es_admin($usuario_id, $grupo_id) {
        return $this->obtener_rol_usuario($usuario_id, $grupo_id) === 'admin';
    }

    private function usuario_es_admin_o_mod($usuario_id, $grupo_id) {
        $rol = $this->obtener_rol_usuario($usuario_id, $grupo_id);
        return in_array($rol, ['admin', 'moderador']);
    }

    private function obtener_rol_usuario($usuario_id, $grupo_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT rol FROM {$wpdb->prefix}flavor_chat_grupos_miembros WHERE grupo_id = %d AND usuario_id = %d",
            $grupo_id, $usuario_id
        ));
    }

    private function crear_mensaje_sistema($grupo_id, $tipo, $datos) {
        global $wpdb;

        $mensajes = [
            'grupo_creado' => sprintf(__('%s ha creado el grupo', 'flavor-chat-ia'), $datos['usuario_nombre']),
            'usuario_unido' => sprintf(__('%s se ha unido al grupo', 'flavor-chat-ia'), $datos['usuario_nombre']),
            'usuario_salio' => sprintf(__('%s ha salido del grupo', 'flavor-chat-ia'), $datos['usuario_nombre']),
            'usuario_expulsado' => sprintf(__('%s ha sido expulsado por %s', 'flavor-chat-ia'), $datos['usuario_nombre'], $datos['por_nombre'] ?? ''),
        ];

        $mensaje = $mensajes[$tipo] ?? $tipo;

        $wpdb->insert($wpdb->prefix . 'flavor_chat_grupos_mensajes', [
            'grupo_id' => $grupo_id,
            'usuario_id' => $datos['usuario_id'] ?? 0,
            'mensaje' => $mensaje,
            'tipo' => 'sistema',
        ]);
    }

    private function formatear_mensaje($texto) {
        $texto = esc_html($texto);

        // URLs a links
        $texto = preg_replace(
            '/(https?:\/\/[^\s<]+)/i',
            '<a href="$1" target="_blank" rel="noopener">$1</a>',
            $texto
        );

        // Menciones
        $texto = preg_replace(
            '/@(\w+)/',
            '<span class="cg-mencion">@$1</span>',
            $texto
        );

        // Emojis básicos
        $emojis = [':)' => '😊', ':(' => '😢', ':D' => '😄', ';)' => '😉', '<3' => '❤️'];
        $texto = str_replace(array_keys($emojis), array_values($emojis), $texto);

        // Negrita **texto**
        $texto = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $texto);

        // Cursiva _texto_
        $texto = preg_replace('/\_(.+?)\_/', '<em>$1</em>', $texto);

        // Saltos de línea
        $texto = nl2br($texto);

        return $texto;
    }

    private function tiempo_relativo($fecha) {
        $timestamp = strtotime($fecha);
        $diff = time() - $timestamp;

        if ($diff < 60) return __('ahora', 'flavor-chat-ia');
        if ($diff < 3600) return sprintf(__('hace %d min', 'flavor-chat-ia'), floor($diff / 60));
        if ($diff < 86400) return sprintf(__('hace %d h', 'flavor-chat-ia'), floor($diff / 3600));
        if ($diff < 172800) return __('ayer', 'flavor-chat-ia');
        if ($diff < 604800) return sprintf(__('hace %d días', 'flavor-chat-ia'), floor($diff / 86400));

        return date_i18n('j M', $timestamp);
    }

    private function usuario_online($usuario_id) {
        $last_activity = get_user_meta($usuario_id, 'last_activity', true);
        if (!$last_activity) return false;
        return (time() - strtotime($last_activity)) < 300; // 5 minutos
    }

    // ==================== Web Components ====================

    public function get_web_components() {
        return [
            'hero_chat_grupos' => [
                'label' => __('Hero Chat Grupos', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-format-chat',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Chat de Grupos', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Comunícate en tiempo real', 'flavor-chat-ia')],
                ],
                'template' => 'chat-grupos/hero',
            ],
            'grupos_destacados' => [
                'label' => __('Grupos Destacados', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Grupos Populares', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'default' => 6],
                ],
                'template' => 'chat-grupos/grupos-destacados',
            ],
        ];
    }

    public function get_tool_definitions() {
        return [
            [
                'name' => 'chat_grupos_mis_grupos',
                'description' => 'Ver mis grupos de chat',
                'input_schema' => ['type' => 'object', 'properties' => []],
            ],
        ];
    }

    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Chat de Grupos Comunitarios**

Sistema de mensajería grupal organizado por temas e intereses.

**Tipos de grupos:**
- Públicos: Cualquiera puede unirse
- Privados: Requieren invitación
- Secretos: Solo por invitación, no aparecen en búsqueda

**Funcionalidades:**
- Chat en tiempo real
- Menciones (@usuario)
- Respuestas a mensajes
- Reacciones con emojis
- Compartir archivos
- Encuestas
- Mensajes fijados
- Búsqueda de mensajes
KNOWLEDGE;
    }

    public function get_faqs() {
        return [
            ['pregunta' => '¿Cuántos grupos puedo crear?', 'respuesta' => 'No hay límite.'],
            ['pregunta' => '¿Los mensajes se guardan?', 'respuesta' => 'Sí, el historial se mantiene.'],
        ];
    }
}
