<?php
/**
 * Módulo de Estados/Stories - Sistema de publicaciones efímeras
 *
 * Permite a los usuarios publicar estados (texto, imagen, video)
 * que desaparecen automáticamente después de 24 horas.
 * Similar a WhatsApp Status / Instagram Stories.
 *
 * @package Flavor_Chat_IA
 * @subpackage Modules/Chat_Estados
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Estados_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * ID del módulo
     */
    protected $id = 'chat_estados';

    /**
     * Nombre del módulo
     */
    protected $name = 'Estados';

    /**
     * Descripción del módulo
     */
    protected $description = 'Sistema de estados/stories efímeros tipo WhatsApp. Publica fotos, videos o texto que desaparecen tras 24 horas.';

    /**
     * Prefijo de tablas
     */
    private $prefix;

    /**
     * Duración de estados en segundos (24 horas)
     */
    const DURACION_ESTADO = 86400;

    /**
     * Tipos de estado permitidos
     */
    const TIPOS_ESTADO = ['texto', 'imagen', 'video', 'audio', 'ubicacion'];

    /**
     * Privacidades disponibles
     */
    const PRIVACIDADES = [
        'todos' => 'Todos mis contactos',
        'contactos_excepto' => 'Mis contactos excepto...',
        'solo_compartir' => 'Solo compartir con...'
    ];

    /**
     * Constructor
     */
    public function __construct() {
        // Auto-registered AJAX handlers
        add_action('wp_ajax_chat_estados_crear_estado', [$this, 'ajax_crear_estado']);
        add_action('wp_ajax_nopriv_chat_estados_crear_estado', [$this, 'ajax_crear_estado']);
        add_action('wp_ajax_chat_estados_obtener_estados', [$this, 'ajax_obtener_estados']);
        add_action('wp_ajax_nopriv_chat_estados_obtener_estados', [$this, 'ajax_obtener_estados']);
        add_action('wp_ajax_chat_estados_marcar_visto', [$this, 'ajax_marcar_visto']);
        add_action('wp_ajax_nopriv_chat_estados_marcar_visto', [$this, 'ajax_marcar_visto']);
        add_action('wp_ajax_chat_estados_eliminar_estado', [$this, 'ajax_eliminar_estado']);
        add_action('wp_ajax_nopriv_chat_estados_eliminar_estado', [$this, 'ajax_eliminar_estado']);
        add_action('wp_ajax_chat_estados_silenciar_usuario', [$this, 'ajax_silenciar_usuario']);
        add_action('wp_ajax_nopriv_chat_estados_silenciar_usuario', [$this, 'ajax_silenciar_usuario']);
        add_action('wp_ajax_chat_estados_upload_media', [$this, 'ajax_upload_media']);
        add_action('wp_ajax_nopriv_chat_estados_upload_media', [$this, 'ajax_upload_media']);
        add_action('wp_ajax_chat_estados_obtener_visualizaciones', [$this, 'ajax_obtener_visualizaciones']);
        add_action('wp_ajax_nopriv_chat_estados_obtener_visualizaciones', [$this, 'ajax_obtener_visualizaciones']);
        add_action('wp_ajax_chat_estados_reportar_estado', [$this, 'ajax_reportar_estado']);
        add_action('wp_ajax_nopriv_chat_estados_reportar_estado', [$this, 'ajax_reportar_estado']);

        global $wpdb;
        $this->prefix = $wpdb->prefix . 'flavor_';

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_chat_estados';
        return Flavor_Chat_Helpers::tabla_existe($tabla);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Estados no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function is_active() {
        return $this->can_activate();
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'ambas',
            'max_estados_dia' => 30,
            'max_duracion_video' => 30,
            'max_tamano_imagen' => 5242880,
            'max_tamano_video' => 16777216,
            'permitir_respuestas' => true,
            'permitir_reacciones' => true,
            'mostrar_visualizaciones' => true,
            'duracion_horas' => 24,
            'colores_fondo' => ['#128C7E', '#25D366', '#075E54', '#34B7F1', '#E91E63', '#9C27B0', '#673AB7', '#3F51B5'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        // Crear tablas
        add_action('init', [$this, 'maybe_create_tables']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX handlers
        add_action('wp_ajax_flavor_estados_crear', [$this, 'ajax_crear_estado']);
        add_action('wp_ajax_flavor_estados_obtener', [$this, 'ajax_obtener_estados']);
        add_action('wp_ajax_flavor_estados_ver', [$this, 'ajax_marcar_visto']);
        add_action('wp_ajax_flavor_estados_eliminar', [$this, 'ajax_eliminar_estado']);
        add_action('wp_ajax_flavor_estados_reaccionar', [$this, 'ajax_reaccionar']);
        add_action('wp_ajax_flavor_estados_responder', [$this, 'ajax_responder']);
        add_action('wp_ajax_flavor_estados_silenciar', [$this, 'ajax_silenciar_usuario']);
        add_action('wp_ajax_flavor_estados_upload', [$this, 'ajax_upload_media']);
        add_action('wp_ajax_flavor_estados_mis_estados', [$this, 'ajax_mis_estados']);
        add_action('wp_ajax_flavor_estados_visualizaciones', [$this, 'ajax_obtener_visualizaciones']);
        add_action('wp_ajax_flavor_estados_reportar', [$this, 'ajax_reportar_estado']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Shortcodes
        add_shortcode('flavor_estados', [$this, 'shortcode_estados']);
        add_shortcode('flavor_estados_crear', [$this, 'shortcode_crear_estado']);
        add_shortcode('flavor_estados_mis_estados', [$this, 'shortcode_mis_estados']);

        // Cron para limpieza
        add_action('flavor_limpiar_estados_expirados', [$this, 'limpiar_estados_expirados']);
        if (!wp_next_scheduled('flavor_limpiar_estados_expirados')) {
            wp_schedule_event(time(), 'hourly', 'flavor_limpiar_estados_expirados');
        }

        // Dashboard widget: compatibilidad con registro moderno y dashboard legacy.
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);
        add_filter('flavor_dashboard_widgets', [$this, 'register_legacy_dashboard_widget']);

        // Integración con moderación
        add_filter('flavor_moderation_content_types', [$this, 'registrar_tipo_moderacion']);

        // Panel de administración unificado
        $this->registrar_en_panel_unificado();

        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();
    }

    /**
     * Configuración del módulo para el panel admin unificado.
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'chat_estados',
            'label' => __('Chat Estados', 'flavor-chat-ia'),
            'icon' => 'dashicons-format-status',
            'capability' => 'manage_options',
            'categoria' => 'comunicacion',
            'paginas' => [
                [
                    'slug' => 'chat-estados-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
            ],
        ];
    }

    /**
     * Renderiza el dashboard admin del módulo.
     */
    public function render_admin_dashboard() {
        $is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');
        $estados = $this->obtener_estados_contactos(get_current_user_id());
        $mis_estados = $estados['mis_estados'] ?? null;
        $contactos = $estados['contactos'] ?? [];
        $total_mis_estados = is_array($mis_estados['estados'] ?? null) ? count($mis_estados['estados']) : 0;
        $total_contactos = count($contactos);
        $total_sin_ver = 0;

        foreach ($contactos as $contacto) {
            $total_sin_ver += (int) ($contacto['sin_ver'] ?? 0);
        }

        $this->render_page_header(__('Dashboard de Estados', 'flavor-chat-ia'), [
            [
                'label' => __('Ver en portal', 'flavor-chat-ia'),
                'url' => home_url('/mi-portal/chat-estados/'),
                'class' => '',
            ],
        ]);
        ?>
        <div class="wrap flavor-chat-estados-dashboard">
            <?php if ($is_dashboard_viewer) : ?>
                <div class="notice notice-info"><p><?php esc_html_e('Vista resumida para gestor de grupos. Este dashboard permite consulta rápida, no administración avanzada de estados.', 'flavor-chat-ia'); ?></p></div>
            <?php endif; ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin:20px 0;">
                <div class="card" style="margin:0;padding:16px;">
                    <div style="font-size:28px;font-weight:700;line-height:1.1;"><?php echo esc_html(number_format_i18n($total_mis_estados)); ?></div>
                    <div style="color:#646970;"><?php esc_html_e('Estados propios activos', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="card" style="margin:0;padding:16px;">
                    <div style="font-size:28px;font-weight:700;line-height:1.1;"><?php echo esc_html(number_format_i18n($total_contactos)); ?></div>
                    <div style="color:#646970;"><?php esc_html_e('Contactos con estados', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="card" style="margin:0;padding:16px;">
                    <div style="font-size:28px;font-weight:700;line-height:1.1;"><?php echo esc_html(number_format_i18n($total_sin_ver)); ?></div>
                    <div style="color:#646970;"><?php esc_html_e('Estados pendientes de ver', 'flavor-chat-ia'); ?></div>
                </div>
            </div>

            <div class="card" style="max-width:980px;padding:20px;">
                <h2 style="margin-top:0;"><?php esc_html_e('Resumen operativo', 'flavor-chat-ia'); ?></h2>
                <p style="color:#646970;">
                    <?php esc_html_e('Este dashboard reutiliza la misma base funcional del widget y el tab cliente para mantener una entrada admin coherente sin duplicar lógica.', 'flavor-chat-ia'); ?>
                </p>
                <?php $this->render_dashboard_widget(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo_tab = dirname(__FILE__) . '/class-chat-estados-dashboard-tab.php';
        if (file_exists($archivo_tab)) {
            require_once $archivo_tab;
            if (class_exists('Flavor_Chat_Estados_Dashboard_Tab')) {
                Flavor_Chat_Estados_Dashboard_Tab::get_instance();
            }
        }
    }

    /**
     * Crear tablas si no existen
     */
    public function maybe_create_tables() {
        if (get_option('flavor_estados_db_version') === '1.0.0') {
            return;
        }

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Tabla de estados
        $sql_estados = "CREATE TABLE {$this->prefix}chat_estados (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo enum('texto', 'imagen', 'video', 'audio', 'ubicacion') DEFAULT 'texto',
            contenido text DEFAULT NULL,
            media_url varchar(500) DEFAULT NULL,
            media_thumbnail varchar(500) DEFAULT NULL,
            duracion_media int(11) DEFAULT 0,
            color_fondo varchar(20) DEFAULT '#128C7E',
            color_texto varchar(20) DEFAULT '#FFFFFF',
            fuente varchar(50) DEFAULT 'default',
            ubicacion_lat decimal(10,8) DEFAULT NULL,
            ubicacion_lng decimal(11,8) DEFAULT NULL,
            ubicacion_nombre varchar(255) DEFAULT NULL,
            privacidad enum('todos', 'contactos_excepto', 'solo_compartir') DEFAULT 'todos',
            usuarios_excluidos longtext DEFAULT NULL,
            usuarios_incluidos longtext DEFAULT NULL,
            visualizaciones_count int(11) DEFAULT 0,
            reacciones_count int(11) DEFAULT 0,
            respuestas_count int(11) DEFAULT 0,
            activo tinyint(1) DEFAULT 1,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_usuario (usuario_id),
            KEY idx_activo_expiracion (activo, fecha_expiracion),
            KEY idx_fecha_creacion (fecha_creacion)
        ) $charset_collate;";

        dbDelta($sql_estados);

        // Tabla de visualizaciones
        $sql_vistas = "CREATE TABLE {$this->prefix}chat_estados_vistas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            estado_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha_vista datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_vista (estado_id, usuario_id),
            KEY idx_estado (estado_id),
            KEY idx_usuario (usuario_id)
        ) $charset_collate;";

        dbDelta($sql_vistas);

        // Tabla de reacciones a estados
        $sql_reacciones = "CREATE TABLE {$this->prefix}chat_estados_reacciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            estado_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            emoji varchar(50) NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_reaccion (estado_id, usuario_id),
            KEY idx_estado (estado_id)
        ) $charset_collate;";

        dbDelta($sql_reacciones);

        // Tabla de respuestas a estados (mensajes directos como respuesta)
        $sql_respuestas = "CREATE TABLE {$this->prefix}chat_estados_respuestas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            estado_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            mensaje text NOT NULL,
            leido tinyint(1) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_estado (estado_id),
            KEY idx_usuario (usuario_id)
        ) $charset_collate;";

        dbDelta($sql_respuestas);

        // Tabla de usuarios silenciados (para estados)
        $sql_silenciados = "CREATE TABLE {$this->prefix}chat_estados_silenciados (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            silenciado_id bigint(20) UNSIGNED NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_silencio (usuario_id, silenciado_id)
        ) $charset_collate;";

        dbDelta($sql_silenciados);

        update_option('flavor_estados_db_version', '1.0.0');
    }

    /**
     * Enqueue assets frontend
     */
    public function enqueue_assets() {
        if (!is_user_logged_in()) {
            return;
        }

        wp_enqueue_style(
            'flavor-chat-estados',
            FLAVOR_CHAT_IA_URL . 'includes/modules/chat-estados/assets/css/chat-estados.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-chat-estados',
            FLAVOR_CHAT_IA_URL . 'includes/modules/chat-estados/assets/js/chat-estados.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        $settings = method_exists($this, 'get_settings') ? (array) $this->get_settings() : [];
        $max_estados_dia = isset($settings['max_estados_dia']) ? (int) $settings['max_estados_dia'] : 30;
        if ($max_estados_dia < 1) {
            $max_estados_dia = 30;
        }

        wp_localize_script('flavor-chat-estados', 'flavorEstados', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('flavor/v1/estados/'),
            'nonce' => wp_create_nonce('flavor_estados_nonce'),
            'userId' => get_current_user_id(),
            'duracion' => self::DURACION_ESTADO,
            'maxEstadosDia' => $max_estados_dia,
            'strings' => [
                'tuEstado' => __('Tu estado', 'flavor-chat-ia'),
                'agregarEstado' => __('Añadir estado', 'flavor-chat-ia'),
                'sinEstados' => __('Sin estados recientes', 'flavor-chat-ia'),
                'haceMomento' => __('Hace un momento', 'flavor-chat-ia'),
                'haceMinutos' => __('Hace %d min', 'flavor-chat-ia'),
                'haceHoras' => __('Hace %d h', 'flavor-chat-ia'),
                'responder' => __('Responder...', 'flavor-chat-ia'),
                'eliminar' => __('Eliminar estado', 'flavor-chat-ia'),
                'confirmarEliminar' => __('¿Eliminar este estado?', 'flavor-chat-ia'),
                'estadoPublicado' => __('Estado publicado', 'flavor-chat-ia'),
                'errorPublicar' => __('Error al publicar estado', 'flavor-chat-ia')
            ]
        ]);
    }

    /**
     * Enqueue assets admin
     */
    public function enqueue_admin_assets($hook) {
        // Solo en páginas relevantes
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/estados', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_estados'],
            'permission_callback' => [$this, 'rest_check_auth']
        ]);

        register_rest_route('flavor/v1', '/estados', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_crear_estado'],
            'permission_callback' => [$this, 'rest_check_auth']
        ]);

        register_rest_route('flavor/v1', '/estados/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_estado'],
            'permission_callback' => [$this, 'rest_check_auth']
        ]);

        register_rest_route('flavor/v1', '/estados/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'rest_eliminar_estado'],
            'permission_callback' => [$this, 'rest_check_auth']
        ]);

        register_rest_route('flavor/v1', '/estados/(?P<id>\d+)/ver', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_marcar_visto'],
            'permission_callback' => [$this, 'rest_check_auth']
        ]);

        register_rest_route('flavor/v1', '/estados/(?P<id>\d+)/reaccion', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_reaccionar'],
            'permission_callback' => [$this, 'rest_check_auth']
        ]);

        register_rest_route('flavor/v1', '/estados/(?P<id>\d+)/responder', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_responder'],
            'permission_callback' => [$this, 'rest_check_auth']
        ]);

        register_rest_route('flavor/v1', '/estados/usuario/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_estados_usuario'],
            'permission_callback' => [$this, 'rest_check_auth']
        ]);
    }

    /**
     * Verificar autenticación REST
     */
    public function rest_check_auth() {
        return is_user_logged_in();
    }

    // =========================================================================
    // MÉTODOS PRINCIPALES
    // =========================================================================

    /**
     * Crear un nuevo estado
     */
    public function crear_estado($datos) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return new WP_Error('no_auth', 'Usuario no autenticado');
        }

        // Verificar límite diario
        $estados_hoy = $this->contar_estados_hoy($usuario_id);
        $settings = method_exists($this, 'get_settings') ? (array) $this->get_settings() : [];
        $max_estados_dia = isset($settings['max_estados_dia']) ? (int) $settings['max_estados_dia'] : 30;
        if ($max_estados_dia < 1) {
            $max_estados_dia = 30;
        }

        if ($estados_hoy >= $max_estados_dia) {
            return new WP_Error('limite_alcanzado', 'Has alcanzado el límite de estados diarios');
        }

        // Validar tipo
        $tipo = sanitize_text_field($datos['tipo'] ?? 'texto');
        if (!in_array($tipo, self::TIPOS_ESTADO)) {
            return new WP_Error('tipo_invalido', 'Tipo de estado no válido');
        }

        // Preparar datos
        $insert_data = [
            'usuario_id' => $usuario_id,
            'tipo' => $tipo,
            'contenido' => wp_kses_post($datos['contenido'] ?? ''),
            'media_url' => esc_url_raw($datos['media_url'] ?? ''),
            'media_thumbnail' => esc_url_raw($datos['media_thumbnail'] ?? ''),
            'duracion_media' => absint($datos['duracion_media'] ?? 0),
            'color_fondo' => sanitize_hex_color($datos['color_fondo'] ?? '#128C7E'),
            'color_texto' => sanitize_hex_color($datos['color_texto'] ?? '#FFFFFF'),
            'fuente' => sanitize_text_field($datos['fuente'] ?? 'default'),
            'privacidad' => in_array($datos['privacidad'] ?? '', array_keys(self::PRIVACIDADES))
                ? $datos['privacidad'] : 'todos',
            'fecha_expiracion' => date('Y-m-d H:i:s', time() + self::DURACION_ESTADO)
        ];

        // Ubicación
        if ($tipo === 'ubicacion' && isset($datos['ubicacion'])) {
            $insert_data['ubicacion_lat'] = floatval($datos['ubicacion']['lat'] ?? 0);
            $insert_data['ubicacion_lng'] = floatval($datos['ubicacion']['lng'] ?? 0);
            $insert_data['ubicacion_nombre'] = sanitize_text_field($datos['ubicacion']['nombre'] ?? '');
        }

        // Usuarios excluidos/incluidos
        if ($insert_data['privacidad'] === 'contactos_excepto' && !empty($datos['usuarios_excluidos'])) {
            $insert_data['usuarios_excluidos'] = wp_json_encode(array_map('absint', $datos['usuarios_excluidos']));
        }
        if ($insert_data['privacidad'] === 'solo_compartir' && !empty($datos['usuarios_incluidos'])) {
            $insert_data['usuarios_incluidos'] = wp_json_encode(array_map('absint', $datos['usuarios_incluidos']));
        }

        $result = $wpdb->insert($this->prefix . 'chat_estados', $insert_data);

        if ($result === false) {
            return new WP_Error('db_error', 'Error al crear estado');
        }

        $estado_id = $wpdb->insert_id;

        // Disparar hook
        do_action('flavor_estado_creado', $estado_id, $usuario_id, $datos);

        return $this->obtener_estado($estado_id);
    }

    /**
     * Obtener un estado por ID
     */
    public function obtener_estado($estado_id) {
        global $wpdb;

        $estado = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, u.display_name as autor_nombre
             FROM {$this->prefix}chat_estados e
             LEFT JOIN {$wpdb->users} u ON e.usuario_id = u.ID
             WHERE e.id = %d AND e.activo = 1",
            $estado_id
        ));

        if (!$estado) {
            return null;
        }

        // Añadir avatar
        $estado->autor_avatar = get_avatar_url($estado->usuario_id, ['size' => 100]);

        // Añadir tiempo relativo
        $estado->tiempo_relativo = $this->tiempo_relativo($estado->fecha_creacion);

        // Añadir progreso (cuánto falta para expirar)
        $expira = strtotime($estado->fecha_expiracion);
        $creado = strtotime($estado->fecha_creacion);
        $ahora = time();
        $estado->progreso = max(0, min(100, (($ahora - $creado) / ($expira - $creado)) * 100));

        return $estado;
    }

    /**
     * Obtener estados de contactos del usuario actual
     */
    public function obtener_estados_contactos($usuario_id = null, $args = []) {
        global $wpdb;

        $usuario_id = $usuario_id ?: get_current_user_id();
        if (!$usuario_id) {
            return [];
        }

        $defaults = [
            'incluir_propios' => true,
            'limit' => 50,
            'offset' => 0
        ];
        $args = wp_parse_args($args, $defaults);

        // Obtener IDs de usuarios silenciados
        $silenciados = $this->obtener_usuarios_silenciados($usuario_id);
        $silenciados_sql = !empty($silenciados)
            ? "AND e.usuario_id NOT IN (" . implode(',', array_map('absint', $silenciados)) . ")"
            : "";

        // Obtener estados activos
        $sql = $wpdb->prepare(
            "SELECT e.*, u.display_name as autor_nombre,
                    (SELECT COUNT(*) FROM {$this->prefix}chat_estados_vistas v
                     WHERE v.estado_id = e.id AND v.usuario_id = %d) as visto
             FROM {$this->prefix}chat_estados e
             LEFT JOIN {$wpdb->users} u ON e.usuario_id = u.ID
             WHERE e.activo = 1
               AND e.fecha_expiracion > NOW()
               {$silenciados_sql}
             ORDER BY e.fecha_creacion DESC
             LIMIT %d OFFSET %d",
            $usuario_id,
            $args['limit'],
            $args['offset']
        );

        $estados = $wpdb->get_results($sql);

        // Filtrar por privacidad y agrupar por usuario
        $estados_filtrados = [];
        $usuarios_con_estados = [];

        foreach ($estados as $estado) {
            // Verificar privacidad
            if (!$this->puede_ver_estado($usuario_id, $estado)) {
                continue;
            }

            // Añadir metadata
            $estado->autor_avatar = get_avatar_url($estado->usuario_id, ['size' => 100]);
            $estado->tiempo_relativo = $this->tiempo_relativo($estado->fecha_creacion);

            // Agrupar por usuario
            if (!isset($usuarios_con_estados[$estado->usuario_id])) {
                $usuarios_con_estados[$estado->usuario_id] = [
                    'usuario_id' => $estado->usuario_id,
                    'autor_nombre' => $estado->autor_nombre,
                    'autor_avatar' => $estado->autor_avatar,
                    'estados' => [],
                    'sin_ver' => 0,
                    'ultimo_estado' => null
                ];
            }

            $usuarios_con_estados[$estado->usuario_id]['estados'][] = $estado;

            if (!$estado->visto) {
                $usuarios_con_estados[$estado->usuario_id]['sin_ver']++;
            }

            if (!$usuarios_con_estados[$estado->usuario_id]['ultimo_estado'] ||
                strtotime($estado->fecha_creacion) > strtotime($usuarios_con_estados[$estado->usuario_id]['ultimo_estado'])) {
                $usuarios_con_estados[$estado->usuario_id]['ultimo_estado'] = $estado->fecha_creacion;
            }
        }

        // Separar estados propios
        $mis_estados = isset($usuarios_con_estados[$usuario_id])
            ? $usuarios_con_estados[$usuario_id]
            : null;

        unset($usuarios_con_estados[$usuario_id]);

        // Ordenar: primero los que tienen estados sin ver
        uasort($usuarios_con_estados, function($a, $b) {
            if ($a['sin_ver'] > 0 && $b['sin_ver'] == 0) return -1;
            if ($a['sin_ver'] == 0 && $b['sin_ver'] > 0) return 1;
            return strtotime($b['ultimo_estado']) - strtotime($a['ultimo_estado']);
        });

        return [
            'mis_estados' => $mis_estados,
            'contactos' => array_values($usuarios_con_estados)
        ];
    }

    /**
     * Verificar si un usuario puede ver un estado
     */
    private function puede_ver_estado($usuario_id, $estado) {
        // El autor siempre puede ver sus estados
        if ((int) $estado->usuario_id === $usuario_id) {
            return true;
        }

        switch ($estado->privacidad) {
            case 'todos':
                return true;

            case 'contactos_excepto':
                $excluidos = json_decode($estado->usuarios_excluidos ?: '[]', true);
                return !in_array($usuario_id, $excluidos);

            case 'solo_compartir':
                $incluidos = json_decode($estado->usuarios_incluidos ?: '[]', true);
                return in_array($usuario_id, $incluidos);

            default:
                return true;
        }
    }

    /**
     * Marcar estado como visto
     */
    public function marcar_visto($estado_id, $usuario_id = null) {
        global $wpdb;

        $usuario_id = $usuario_id ?: get_current_user_id();

        // No registrar vista propia
        $estado = $this->obtener_estado($estado_id);
        if (!$estado || (int) $estado->usuario_id === $usuario_id) {
            return false;
        }

        // Insertar o ignorar si ya existe
        $result = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$this->prefix}chat_estados_vistas
             (estado_id, usuario_id) VALUES (%d, %d)",
            $estado_id,
            $usuario_id
        ));

        if ($result) {
            // Actualizar contador
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->prefix}chat_estados
                 SET visualizaciones_count = visualizaciones_count + 1
                 WHERE id = %d",
                $estado_id
            ));

            do_action('flavor_estado_visto', $estado_id, $usuario_id);
        }

        return true;
    }

    /**
     * Añadir reacción a estado
     */
    public function reaccionar($estado_id, $emoji, $usuario_id = null) {
        global $wpdb;

        $usuario_id = $usuario_id ?: get_current_user_id();
        $emoji = sanitize_text_field($emoji);

        // Verificar que existe y puede verlo
        $estado = $this->obtener_estado($estado_id);
        if (!$estado || !$this->puede_ver_estado($usuario_id, $estado)) {
            return new WP_Error('acceso_denegado', 'No puedes reaccionar a este estado');
        }

        // Insertar o actualizar reacción
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->prefix}chat_estados_reacciones
             WHERE estado_id = %d AND usuario_id = %d",
            $estado_id,
            $usuario_id
        ));

        if ($existe) {
            $wpdb->update(
                $this->prefix . 'chat_estados_reacciones',
                ['emoji' => $emoji, 'fecha' => current_time('mysql')],
                ['id' => $existe]
            );
        } else {
            $wpdb->insert($this->prefix . 'chat_estados_reacciones', [
                'estado_id' => $estado_id,
                'usuario_id' => $usuario_id,
                'emoji' => $emoji
            ]);

            // Actualizar contador
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->prefix}chat_estados
                 SET reacciones_count = reacciones_count + 1
                 WHERE id = %d",
                $estado_id
            ));
        }

        // Notificar al autor
        if ($estado->usuario_id != $usuario_id) {
            do_action('flavor_notificar_usuario', $estado->usuario_id, [
                'tipo' => 'reaccion_estado',
                'titulo' => sprintf(__('%s reaccionó a tu estado', 'flavor-chat-ia'),
                    get_userdata($usuario_id)->display_name),
                'mensaje' => $emoji,
                'url' => '#estado-' . $estado_id
            ]);
        }

        return true;
    }

    /**
     * Responder a un estado (inicia conversación privada)
     */
    public function responder($estado_id, $mensaje, $usuario_id = null) {
        global $wpdb;

        $usuario_id = $usuario_id ?: get_current_user_id();
        $mensaje = sanitize_textarea_field($mensaje);

        if (empty($mensaje)) {
            return new WP_Error('mensaje_vacio', 'El mensaje no puede estar vacío');
        }

        $estado = $this->obtener_estado($estado_id);
        if (!$estado || !$this->puede_ver_estado($usuario_id, $estado)) {
            return new WP_Error('acceso_denegado', 'No puedes responder a este estado');
        }

        // Guardar respuesta
        $wpdb->insert($this->prefix . 'chat_estados_respuestas', [
            'estado_id' => $estado_id,
            'usuario_id' => $usuario_id,
            'mensaje' => $mensaje
        ]);

        $respuesta_id = $wpdb->insert_id;

        // Actualizar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->prefix}chat_estados
             SET respuestas_count = respuestas_count + 1
             WHERE id = %d",
            $estado_id
        ));

        // Iniciar conversación privada si existe el módulo de chat interno
        $chat_interno = Flavor_Chat_Module_Loader::get_instance()->get_module('chat_interno');
        if ($chat_interno && method_exists($chat_interno, 'iniciar_conversacion')) {
            $contexto = sprintf(
                __('Respondiendo a tu estado: "%s"', 'flavor-chat-ia'),
                wp_trim_words($estado->contenido ?: '[Media]', 10)
            );
            $chat_interno->iniciar_conversacion($estado->usuario_id, $mensaje, [
                'contexto' => $contexto,
                'referencia_estado' => $estado_id,
                'remitente_id' => $usuario_id
            ]);
        }

        // Notificar
        do_action('flavor_notificar_usuario', $estado->usuario_id, [
            'tipo' => 'respuesta_estado',
            'titulo' => sprintf(__('%s respondió a tu estado', 'flavor-chat-ia'),
                get_userdata($usuario_id)->display_name),
            'mensaje' => wp_trim_words($mensaje, 15),
            'url' => '#estado-' . $estado_id
        ]);

        return $respuesta_id;
    }

    /**
     * Eliminar estado
     */
    public function eliminar_estado($estado_id, $usuario_id = null) {
        global $wpdb;

        $usuario_id = $usuario_id ?: get_current_user_id();

        $estado = $this->obtener_estado($estado_id);
        if (!$estado) {
            return new WP_Error('no_encontrado', 'Estado no encontrado');
        }

        // Solo el autor o admin puede eliminar
        if ($estado->usuario_id != $usuario_id && !current_user_can('manage_options')) {
            return new WP_Error('sin_permiso', 'No tienes permiso para eliminar este estado');
        }

        // Soft delete
        $wpdb->update(
            $this->prefix . 'chat_estados',
            ['activo' => 0],
            ['id' => $estado_id]
        );

        do_action('flavor_estado_eliminado', $estado_id, $usuario_id);

        return true;
    }

    /**
     * Silenciar estados de un usuario
     */
    public function silenciar_usuario($silenciado_id) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        $silenciado_id = absint($silenciado_id);

        if ($usuario_id === $silenciado_id) {
            return new WP_Error('error', 'No puedes silenciarte a ti mismo');
        }

        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$this->prefix}chat_estados_silenciados
             (usuario_id, silenciado_id) VALUES (%d, %d)",
            $usuario_id,
            $silenciado_id
        ));

        return true;
    }

    /**
     * Quitar silencio
     */
    public function quitar_silencio($silenciado_id) {
        global $wpdb;

        $wpdb->delete(
            $this->prefix . 'chat_estados_silenciados',
            [
                'usuario_id' => get_current_user_id(),
                'silenciado_id' => absint($silenciado_id)
            ]
        );

        return true;
    }

    /**
     * Reportar un estado
     */
    public function reportar_estado($estado_id, $usuario_id = null) {
        $usuario_id = $usuario_id ?: get_current_user_id();
        $estado = $this->obtener_estado($estado_id);

        if (!$estado) {
            return new WP_Error('no_encontrado', 'Estado no encontrado');
        }

        // No puedes reportar tu propio estado
        if ((int) $estado->usuario_id === $usuario_id) {
            return new WP_Error('error', 'No puedes reportar tu propio estado');
        }

        // Usar el sistema de moderación si está disponible
        if (function_exists('flavor_reportar_contenido')) {
            return flavor_reportar_contenido([
                'tipo' => 'estado',
                'contenido_id' => $estado_id,
                'reportado_por' => $usuario_id,
                'autor_id' => $estado->usuario_id,
                'razon' => 'reportado_usuario',
                'descripcion' => sprintf('Estado reportado: %s', wp_trim_words($estado->contenido ?: '[Media]', 20))
            ]);
        }

        // Fallback: notificar a admins
        do_action('flavor_notificar_admins', [
            'tipo' => 'reporte_estado',
            'titulo' => __('Estado reportado', 'flavor-chat-ia'),
            'mensaje' => sprintf(
                __('El usuario %s ha reportado un estado de %s', 'flavor-chat-ia'),
                get_userdata($usuario_id)->display_name,
                get_userdata($estado->usuario_id)->display_name
            ),
            'url' => admin_url('admin.php?page=flavor-moderacion&estado=' . $estado_id)
        ]);

        do_action('flavor_estado_reportado', $estado_id, $usuario_id);

        return true;
    }

    /**
     * Obtener usuarios silenciados
     */
    public function obtener_usuarios_silenciados($usuario_id) {
        global $wpdb;

        return $wpdb->get_col($wpdb->prepare(
            "SELECT silenciado_id FROM {$this->prefix}chat_estados_silenciados
             WHERE usuario_id = %d",
            $usuario_id
        ));
    }

    /**
     * Obtener visualizaciones de un estado
     */
    public function obtener_visualizaciones($estado_id) {
        global $wpdb;

        $estado = $this->obtener_estado($estado_id);
        if (!$estado || $estado->usuario_id != get_current_user_id()) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, u.display_name as nombre
             FROM {$this->prefix}chat_estados_vistas v
             LEFT JOIN {$wpdb->users} u ON v.usuario_id = u.ID
             WHERE v.estado_id = %d
             ORDER BY v.fecha_vista DESC",
            $estado_id
        ));
    }

    /**
     * Contar estados publicados hoy
     */
    private function contar_estados_hoy($usuario_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->prefix}chat_estados
             WHERE usuario_id = %d AND DATE(fecha_creacion) = CURDATE()",
            $usuario_id
        ));
    }

    /**
     * Limpiar estados expirados (cron)
     */
    public function limpiar_estados_expirados() {
        global $wpdb;

        // Marcar como inactivos los expirados
        $wpdb->query(
            "UPDATE {$this->prefix}chat_estados
             SET activo = 0
             WHERE fecha_expiracion < NOW() AND activo = 1"
        );

        // Eliminar datos antiguos (más de 7 días)
        $fecha_limite = date('Y-m-d H:i:s', strtotime('-7 days'));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->prefix}chat_estados WHERE fecha_expiracion < %s",
            $fecha_limite
        ));

        // Limpiar vistas y reacciones huérfanas
        $wpdb->query(
            "DELETE v FROM {$this->prefix}chat_estados_vistas v
             LEFT JOIN {$this->prefix}chat_estados e ON v.estado_id = e.id
             WHERE e.id IS NULL"
        );

        $wpdb->query(
            "DELETE r FROM {$this->prefix}chat_estados_reacciones r
             LEFT JOIN {$this->prefix}chat_estados e ON r.estado_id = e.id
             WHERE e.id IS NULL"
        );
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Crear estado
     */
    public function ajax_crear_estado() {
        check_ajax_referer('flavor_estados_nonce', 'nonce');

        $resultado = $this->crear_estado($_POST);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success($resultado);
    }

    /**
     * AJAX: Obtener estados
     */
    public function ajax_obtener_estados() {
        check_ajax_referer('flavor_estados_nonce', 'nonce');

        $estados = $this->obtener_estados_contactos();

        wp_send_json_success($estados);
    }

    /**
     * AJAX: Marcar visto
     */
    public function ajax_marcar_visto() {
        check_ajax_referer('flavor_estados_nonce', 'nonce');

        $estado_id = absint($_POST['estado_id'] ?? 0);
        $this->marcar_visto($estado_id);

        wp_send_json_success();
    }

    /**
     * AJAX: Eliminar estado
     */
    public function ajax_eliminar_estado() {
        check_ajax_referer('flavor_estados_nonce', 'nonce');

        $estado_id = absint($_POST['estado_id'] ?? 0);
        $resultado = $this->eliminar_estado($estado_id);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Reaccionar
     */
    public function ajax_reaccionar() {
        check_ajax_referer('flavor_estados_nonce', 'nonce');

        $estado_id = absint($_POST['estado_id'] ?? 0);
        $emoji = sanitize_text_field($_POST['emoji'] ?? '');

        $resultado = $this->reaccionar($estado_id, $emoji);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Responder
     */
    public function ajax_responder() {
        check_ajax_referer('flavor_estados_nonce', 'nonce');

        $estado_id = absint($_POST['estado_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');

        $resultado = $this->responder($estado_id, $mensaje);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success(['respuesta_id' => $resultado]);
    }

    /**
     * AJAX: Silenciar usuario
     */
    public function ajax_silenciar_usuario() {
        check_ajax_referer('flavor_estados_nonce', 'nonce');

        $silenciado_id = absint($_POST['usuario_id'] ?? 0);
        $accion = sanitize_text_field($_POST['accion'] ?? 'silenciar');

        if ($accion === 'quitar') {
            $this->quitar_silencio($silenciado_id);
        } else {
            $this->silenciar_usuario($silenciado_id);
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Upload media
     */
    public function ajax_upload_media() {
        check_ajax_referer('flavor_estados_nonce', 'nonce');

        if (empty($_FILES['media'])) {
            wp_send_json_error(['message' => 'No se recibió archivo']);
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('media', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()]);
        }

        $url = wp_get_attachment_url($attachment_id);
        $thumbnail = wp_get_attachment_image_url($attachment_id, 'medium');
        $type = wp_check_filetype($url)['type'];

        wp_send_json_success([
            'id' => $attachment_id,
            'url' => $url,
            'thumbnail' => $thumbnail ?: $url,
            'type' => strpos($type, 'video') !== false ? 'video' : 'imagen'
        ]);
    }

    /**
     * AJAX: Mis estados
     */
    public function ajax_mis_estados() {
        check_ajax_referer('flavor_estados_nonce', 'nonce');

        global $wpdb;

        $estados = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->prefix}chat_estados
             WHERE usuario_id = %d AND activo = 1 AND fecha_expiracion > NOW()
             ORDER BY fecha_creacion DESC",
            get_current_user_id()
        ));

        foreach ($estados as &$estado) {
            $estado->tiempo_relativo = $this->tiempo_relativo($estado->fecha_creacion);
            $estado->visualizaciones = $this->obtener_visualizaciones($estado->id);
        }

        wp_send_json_success($estados);
    }

    /**
     * AJAX: Obtener visualizaciones
     */
    public function ajax_obtener_visualizaciones() {
        check_ajax_referer('flavor_estados_nonce', 'nonce');

        $estado_id = absint($_POST['estado_id'] ?? 0);
        $visualizaciones = $this->obtener_visualizaciones($estado_id);

        wp_send_json_success($visualizaciones);
    }

    /**
     * AJAX: Reportar estado
     */
    public function ajax_reportar_estado() {
        check_ajax_referer('flavor_estados_nonce', 'nonce');

        $estado_id = absint($_POST['estado_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$estado_id) {
            wp_send_json_error(['message' => 'Estado no válido']);
        }

        $estado = $this->obtener_estado($estado_id);
        if (!$estado) {
            wp_send_json_error(['message' => 'Estado no encontrado']);
        }

        // Registrar el reporte
        $resultado = $this->reportar_estado($estado_id, $usuario_id);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success();
    }

    // =========================================================================
    // REST API HANDLERS
    // =========================================================================

    public function rest_obtener_estados($request) {
        $estados = $this->obtener_estados_contactos();
        return rest_ensure_response($estados);
    }

    public function rest_crear_estado($request) {
        $datos = $request->get_json_params();
        $resultado = $this->crear_estado($datos);

        if (is_wp_error($resultado)) {
            return new WP_REST_Response(['error' => $resultado->get_error_message()], 400);
        }

        return rest_ensure_response($resultado);
    }

    public function rest_obtener_estado($request) {
        $estado_id = $request->get_param('id');
        $estado = $this->obtener_estado($estado_id);

        if (!$estado) {
            return new WP_REST_Response(['error' => 'Estado no encontrado'], 404);
        }

        return rest_ensure_response($estado);
    }

    public function rest_eliminar_estado($request) {
        $estado_id = $request->get_param('id');
        $resultado = $this->eliminar_estado($estado_id);

        if (is_wp_error($resultado)) {
            return new WP_REST_Response(['error' => $resultado->get_error_message()], 400);
        }

        return rest_ensure_response(['deleted' => true]);
    }

    public function rest_marcar_visto($request) {
        $estado_id = $request->get_param('id');
        $this->marcar_visto($estado_id);
        return rest_ensure_response(['success' => true]);
    }

    public function rest_reaccionar($request) {
        $estado_id = $request->get_param('id');
        $emoji = $request->get_param('emoji');
        $resultado = $this->reaccionar($estado_id, $emoji);

        if (is_wp_error($resultado)) {
            return new WP_REST_Response(['error' => $resultado->get_error_message()], 400);
        }

        return rest_ensure_response(['success' => true]);
    }

    public function rest_responder($request) {
        $estado_id = $request->get_param('id');
        $mensaje = $request->get_param('mensaje');
        $resultado = $this->responder($estado_id, $mensaje);

        if (is_wp_error($resultado)) {
            return new WP_REST_Response(['error' => $resultado->get_error_message()], 400);
        }

        return rest_ensure_response(['respuesta_id' => $resultado]);
    }

    public function rest_estados_usuario($request) {
        global $wpdb;

        $target_user = $request->get_param('id');
        $current_user = get_current_user_id();

        $estados = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->prefix}chat_estados
             WHERE usuario_id = %d AND activo = 1 AND fecha_expiracion > NOW()
             ORDER BY fecha_creacion DESC",
            $target_user
        ));

        // Filtrar por privacidad
        $estados_visibles = array_filter($estados, function($e) use ($current_user) {
            return $this->puede_ver_estado($current_user, $e);
        });

        return rest_ensure_response(array_values($estados_visibles));
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode principal de estados
     */
    public function shortcode_estados($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' .
                   __('Inicia sesión para ver estados', 'flavor-chat-ia') . '</p>';
        }

        $atts = shortcode_atts([
            'mostrar_crear' => true
        ], $atts);

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/chat-estados/frontend/estados-viewer.php';
        // Incluir fullscreen viewer (siempre oculto por defecto)
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/chat-estados/frontend/estados-fullscreen.php';
        // Incluir modal de crear estado
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/chat-estados/frontend/crear-estado.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para crear estado
     */
    public function shortcode_crear_estado($atts = []) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required"><p>' . esc_html__('Inicia sesión para continuar', 'flavor-chat-ia') . '</p><a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="flavor-btn">' . esc_html__('Iniciar sesión', 'flavor-chat-ia') . '</a></div>';
        }

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/chat-estados/frontend/crear-estado.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para listar mis estados activos.
     */
    public function shortcode_mis_estados($atts = []) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required"><p>' . esc_html__('Inicia sesión para ver tus estados', 'flavor-chat-ia') . '</p><a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="flavor-btn">' . esc_html__('Iniciar sesión', 'flavor-chat-ia') . '</a></div>';
        }

        global $wpdb;

        $estados = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->prefix}chat_estados
             WHERE usuario_id = %d AND activo = 1 AND fecha_expiracion > NOW()
             ORDER BY fecha_creacion DESC",
            get_current_user_id()
        ));

        ob_start();
        ?>
        <div class="flavor-estados-mios">
            <?php if (empty($estados)) : ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-format-status"></span>
                    <p><?php esc_html_e('Todavía no has publicado estados activos.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else : ?>
                <div class="flavor-estados-grid">
                    <?php foreach ($estados as $estado) : ?>
                        <article class="flavor-estado-card">
                            <div class="flavor-estado-card__media">
                                <?php if ($estado->tipo === 'imagen' && !empty($estado->media_url)) : ?>
                                    <img src="<?php echo esc_url($estado->media_url); ?>" alt="">
                                <?php elseif ($estado->tipo === 'video' && !empty($estado->media_thumbnail)) : ?>
                                    <img src="<?php echo esc_url($estado->media_thumbnail); ?>" alt="">
                                <?php else : ?>
                                    <div class="flavor-estado-card__texto" style="background: <?php echo esc_attr($estado->color_fondo ?: '#128C7E'); ?>; color: <?php echo esc_attr($estado->color_texto ?: '#FFFFFF'); ?>;">
                                        <?php echo esc_html(wp_trim_words($estado->contenido ?: __('Estado sin texto', 'flavor-chat-ia'), 20)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-estado-card__meta">
                                <strong><?php echo esc_html(ucfirst($estado->tipo)); ?></strong>
                                <span><?php echo esc_html($this->tiempo_relativo($estado->fecha_creacion)); ?></span>
                                <span><?php echo esc_html(sprintf(__('Expira %s', 'flavor-chat-ia'), human_time_diff(time(), strtotime($estado->fecha_expiracion)))); ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <style>
            .flavor-estados-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:16px; }
            .flavor-estado-card { border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; background:#fff; }
            .flavor-estado-card__media { aspect-ratio:9/16; background:#f5f5f5; display:flex; align-items:center; justify-content:center; }
            .flavor-estado-card__media img { width:100%; height:100%; object-fit:cover; display:block; }
            .flavor-estado-card__texto { width:100%; height:100%; display:flex; align-items:center; justify-content:center; padding:18px; text-align:center; font-weight:600; }
            .flavor-estado-card__meta { display:flex; flex-direction:column; gap:4px; padding:12px; font-size:13px; }
        </style>
        <?php

        return ob_get_clean();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Tiempo relativo humanizado
     */
    private function tiempo_relativo($fecha) {
        $timestamp = strtotime($fecha);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return __('Hace un momento', 'flavor-chat-ia');
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return sprintf(__('Hace %d min', 'flavor-chat-ia'), $mins);
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return sprintf(__('Hace %d h', 'flavor-chat-ia'), $hours);
        } else {
            return date_i18n(get_option('date_format'), $timestamp);
        }
    }

    /**
     * Registrar tipo de contenido para moderación
     */
    public function registrar_tipo_moderacion($tipos) {
        $tipos['estado'] = [
            'label' => 'Estado/Story',
            'icon' => 'format-status',
            'table' => 'chat_estados',
            'id_field' => 'id',
            'content_field' => 'contenido',
            'author_field' => 'usuario_id',
            'status_field' => 'activo'
        ];
        return $tipos;
    }

    /**
     * Registrar widget de dashboard
     */
    public function register_dashboard_widget($registry) {
        if (!$registry instanceof Flavor_Widget_Registry) {
            return;
        }

        if (!class_exists('Flavor_Module_Widget')) {
            require_once FLAVOR_CHAT_IA_PATH . 'includes/dashboard/interface-dashboard-widget.php';
        }

        $registry->register(new Flavor_Module_Widget([
            'id' => 'chat-estados',
            'title' => __('Mis Estados', 'flavor-chat-ia'),
            'icon' => 'dashicons-format-status',
            'size' => 'medium',
            'category' => 'comunicacion',
            'priority' => 15,
            'refreshable' => true,
            'cache_time' => 120,
            'module' => $this,
            'data_callback' => [$this, 'get_dashboard_widget_data'],
            'render_callback' => function() {
                $this->render_dashboard_widget();
            },
        ]));
    }

    /**
     * Compatibilidad con el dashboard legacy basado en filtros.
     */
    public function register_legacy_dashboard_widget($widgets) {
        $widgets['estados'] = [
            'titulo' => __('Mis Estados', 'flavor-chat-ia'),
            'icono' => 'dashicons-format-status',
            'callback' => [$this, 'render_dashboard_widget'],
            'orden' => 15
        ];
        return $widgets;
    }

    /**
     * Render widget de dashboard
     */
    public function render_dashboard_widget() {
        $estados = $this->obtener_estados_contactos();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/chat-estados/views/dashboard-widget.php';
    }

    /**
     * Datos del widget en formato del dashboard unificado.
     */
    public function get_dashboard_widget_data(): array {
        $estados = $this->obtener_estados_contactos();
        $items = [];

        foreach (array_slice($estados ?: [], 0, 5) as $estado) {
            $items[] = [
                'icon' => 'dashicons-format-status',
                'title' => wp_trim_words($estado->contenido ?? '', 8),
                'meta' => !empty($estado->usuario_nombre)
                    ? $estado->usuario_nombre
                    : __('Contacto', 'flavor-chat-ia'),
                'badge' => !empty($estado->tipo)
                    ? ucfirst((string) $estado->tipo)
                    : __('Estado', 'flavor-chat-ia'),
                'badge_color' => 'info',
            ];
        }

        return [
            'stats' => [
                [
                    'icon' => 'dashicons-format-status',
                    'valor' => count($estados ?: []),
                    'label' => __('Estados visibles', 'flavor-chat-ia'),
                    'color' => 'info',
                ],
            ],
            'items' => $items,
            'empty_state' => __('No hay estados recientes de tus contactos.', 'flavor-chat-ia'),
            'footer' => [
                [
                    'label' => __('Ver estados', 'flavor-chat-ia'),
                    'url' => home_url('/mi-portal/chat-estados/'),
                    'icon' => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'ver_estados' => [
                'description' => 'Ver estados de mis contactos',
                'params' => [],
            ],
            'crear_estado' => [
                'description' => 'Crear un nuevo estado efímero',
                'params' => ['contenido', 'tipo'],
            ],
            'mis_estados' => [
                'description' => 'Ver mis estados publicados',
                'params' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'ver_estados',
            'listado' => 'ver_estados',
            'crear' => 'crear_estado',
            'nuevo' => 'crear_estado',
            'mis_items' => 'mis_estados',
            'mis-estados' => 'mis_estados',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $method = 'action_' . $action_name;

        if (method_exists($this, $method)) {
            return $this->$method($params);
        }

        return [
            'success' => false,
            'error' => __('Acción no implementada', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: ver estados de contactos.
     */
    private function action_ver_estados($params) {
        return [
            'success' => true,
            'data' => $this->obtener_estados_contactos(),
        ];
    }

    /**
     * Acción: crear estado o devolver el formulario.
     */
    private function action_crear_estado($params) {
        if (empty($params['contenido']) && empty($params['media_url'])) {
            return [
                'success' => true,
                'html' => $this->shortcode_crear_estado(),
            ];
        }

        $estado = $this->crear_estado($params);

        if (is_wp_error($estado)) {
            return [
                'success' => false,
                'error' => $estado->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'estado' => $estado,
        ];
    }

    /**
     * Acción: estados propios activos.
     */
    private function action_mis_estados($params) {
        global $wpdb;

        $estados = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->prefix}chat_estados
             WHERE usuario_id = %d AND activo = 1 AND fecha_expiracion > NOW()
             ORDER BY fecha_creacion DESC",
            get_current_user_id()
        ));

        return [
            'success' => true,
            'estados' => $estados ?: [],
            'html' => $this->shortcode_mis_estados(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return __('Chat Estados permite compartir contenido efímero (stories) con tus contactos, visible durante 24 horas.', 'flavor-chat-ia');
    }

    /**
     * Configuración para el renderer del portal.
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'chat-estados',
            'title'    => __('Estados', 'flavor-chat-ia'),
            'subtitle' => __('Publicaciones efímeras visibles durante 24 horas.', 'flavor-chat-ia'),
            'icon'     => '🟢',
            'color'    => 'primary',
            'database' => [
                'table' => 'flavor_chat_estados',
                'primary_key' => 'id',
            ],
            'tabs' => [
                'estados' => [
                    'label' => __('Estados', 'flavor-chat-ia'),
                    'icon' => '🟢',
                    'content' => 'shortcode:flavor_estados',
                ],
                'crear' => [
                    'label' => __('Crear', 'flavor-chat-ia'),
                    'icon' => '➕',
                    'content' => 'shortcode:flavor_estados_crear',
                ],
                'mis-estados' => [
                    'label' => __('Mis estados', 'flavor-chat-ia'),
                    'icon' => '👤',
                    'content' => 'shortcode:flavor_estados_mis_estados',
                    'requires_login' => true,
                ],
            ],
            'features' => [
                'has_archive' => false,
                'has_single' => false,
                'has_dashboard' => true,
                'has_search' => false,
                'realtime' => true,
            ],
        ];
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    if (class_exists('Flavor_Chat_Module_Loader') && method_exists('Flavor_Chat_Module_Loader', 'get_instance')) {
        $loader = Flavor_Chat_Module_Loader::get_instance();
        if ($loader && method_exists($loader, 'get_module') && $loader->get_module('chat_estados')) {
            return;
        }
    }

    if (class_exists('Flavor_Chat_Estados_Module')) {
        new Flavor_Chat_Estados_Module();
    }
}, 20);
