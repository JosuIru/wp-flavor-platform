<?php
/**
 * Modulo de Seguimiento de Denuncias Formales
 *
 * Sistema de tracking de denuncias ante administraciones publicas.
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del modulo de Seguimiento de Denuncias
 */
class Flavor_Chat_Seguimiento_Denuncias_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'seguimiento_denuncias';
        $this->name = 'Seguimiento de Denuncias';
        $this->description = 'Sistema de tracking de denuncias formales ante administraciones. Seguimiento de estados, plazos, respuestas y documentacion asociada.';

        parent::__construct();
    }

    /**
     * Verifica si el modulo puede activarse
     */
    public function can_activate() {
        return Flavor_Chat_Helpers::tabla_existe('flavor_seguimiento_denuncias');
    }

    /**
     * Mensaje de error si no puede activarse
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return 'Las tablas del modulo Seguimiento de Denuncias no estan creadas.';
        }
        return '';
    }

    /**
     * Inicializacion del modulo
     */
    public function init() {
        $this->maybe_create_tables();
        $this->register_shortcodes();
        $this->register_ajax_handlers();
        $this->register_rest_routes();

        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Cron para verificar plazos
        add_action('flavor_verificar_plazos_denuncias', [$this, 'verificar_plazos']);
        if (!wp_next_scheduled('flavor_verificar_plazos_denuncias')) {
            wp_schedule_event(time(), 'daily', 'flavor_verificar_plazos_denuncias');
        }
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        if (!Flavor_Chat_Helpers::tabla_existe('flavor_seguimiento_denuncias')) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas del modulo
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Tabla principal de denuncias
        $tabla_denuncias = $wpdb->prefix . 'flavor_seguimiento_denuncias';
        $sql_denuncias = "CREATE TABLE $tabla_denuncias (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion longtext NOT NULL,
            tipo enum('denuncia','queja','recurso','solicitud','peticion') NOT NULL DEFAULT 'denuncia',
            categoria varchar(100),
            ambito enum('municipal','provincial','autonomico','estatal','europeo') DEFAULT 'municipal',
            organismo_destino varchar(255) NOT NULL,
            organismo_email varchar(200),
            organismo_direccion text,
            numero_registro varchar(100),
            fecha_presentacion date NOT NULL,
            fecha_limite_respuesta date,
            estado enum('presentada','en_tramite','requerimiento','silencio','resuelta_favorable','resuelta_desfavorable','archivada','recurrida') NOT NULL DEFAULT 'presentada',
            prioridad enum('baja','media','alta','urgente') DEFAULT 'media',
            denunciante_id bigint(20) unsigned NOT NULL,
            denunciante_nombre varchar(200),
            denunciante_tipo enum('individual','colectivo','anonimo') DEFAULT 'individual',
            colectivo_id bigint(20) unsigned,
            campania_id bigint(20) unsigned,
            incidencia_id bigint(20) unsigned,
            documentos_adjuntos text,
            etiquetas text,
            visibilidad enum('publica','miembros','privada') DEFAULT 'miembros',
            notificar_cambios tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY denunciante_id (denunciante_id),
            KEY estado (estado),
            KEY tipo (tipo),
            KEY organismo_destino (organismo_destino),
            KEY fecha_presentacion (fecha_presentacion),
            KEY fecha_limite_respuesta (fecha_limite_respuesta),
            KEY colectivo_id (colectivo_id),
            KEY campania_id (campania_id)
        ) $charset_collate;";
        dbDelta($sql_denuncias);

        // Tabla de eventos/actualizaciones (timeline)
        $tabla_eventos = $wpdb->prefix . 'flavor_seguimiento_denuncias_eventos';
        $sql_eventos = "CREATE TABLE $tabla_eventos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            denuncia_id bigint(20) unsigned NOT NULL,
            tipo enum('creacion','cambio_estado','documento_recibido','documento_enviado','respuesta','requerimiento','recurso','nota','plazo_vencido','otro') NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            estado_anterior varchar(50),
            estado_nuevo varchar(50),
            documento_adjunto varchar(255),
            autor_id bigint(20) unsigned NOT NULL,
            automatico tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY denuncia_id (denuncia_id),
            KEY tipo (tipo),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_eventos);

        // Tabla de participantes/seguidores
        $tabla_participantes = $wpdb->prefix . 'flavor_seguimiento_denuncias_participantes';
        $sql_participantes = "CREATE TABLE $tabla_participantes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            denuncia_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            rol enum('denunciante','colaborador','seguidor','afectado') NOT NULL DEFAULT 'seguidor',
            notificaciones tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY denuncia_user (denuncia_id, user_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_participantes);

        // Tabla de plantillas de denuncia
        $tabla_plantillas = $wpdb->prefix . 'flavor_seguimiento_denuncias_plantillas';
        $sql_plantillas = "CREATE TABLE $tabla_plantillas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text,
            tipo varchar(50),
            categoria varchar(100),
            contenido_plantilla longtext,
            campos_requeridos text,
            organismo_sugerido varchar(255),
            plazo_respuesta_dias int DEFAULT 30,
            activa tinyint(1) DEFAULT 1,
            usos int unsigned DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY categoria (categoria)
        ) $charset_collate;";
        dbDelta($sql_plantillas);
    }

    /**
     * Configuracion por defecto
     */
    public function get_default_settings() {
        return [
            'plazo_respuesta_defecto' => 30,
            'notificar_plazos' => true,
            'dias_aviso_plazo' => 5,
            'permitir_denuncias_anonimas' => false,
            'requiere_aprobacion' => false,
        ];
    }

    /**
     * Registra los shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('denuncias_listar', [$this, 'shortcode_listar']);
        add_shortcode('denuncias_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('denuncias_crear', [$this, 'shortcode_crear']);
        add_shortcode('denuncias_mis_denuncias', [$this, 'shortcode_mis_denuncias']);
        add_shortcode('denuncias_timeline', [$this, 'shortcode_timeline']);
        add_shortcode('denuncias_estadisticas', [$this, 'shortcode_estadisticas']);
    }

    /**
     * Shortcode para listar denuncias
     */
    public function shortcode_listar($atts) {
        $atts = shortcode_atts([
            'estado' => '',
            'tipo' => '',
            'limite' => 12,
        ], $atts);

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/seguimiento-denuncias/views/listado.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para detalle de denuncia
     */
    public function shortcode_detalle($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);

        $denuncia_id = $atts['id'] ?: (isset($_GET['denuncia_id']) ? intval($_GET['denuncia_id']) : 0);

        if (!$denuncia_id) {
            return '<p class="flavor-error">Denuncia no encontrada.</p>';
        }

        $denuncia = $this->obtener_denuncia($denuncia_id);
        if (!$denuncia) {
            return '<p class="flavor-error">Denuncia no encontrada.</p>';
        }

        // Verificar permisos
        if ($denuncia->visibilidad === 'privada' && $denuncia->denunciante_id !== get_current_user_id()) {
            return '<p class="flavor-error">No tienes permiso para ver esta denuncia.</p>';
        }

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/seguimiento-denuncias/views/detalle.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para crear denuncia
     */
    public function shortcode_crear($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-info">Debes iniciar sesion para registrar una denuncia.</p>';
        }

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/seguimiento-denuncias/views/crear.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para mis denuncias
     */
    public function shortcode_mis_denuncias($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-info">Debes iniciar sesion.</p>';
        }

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/seguimiento-denuncias/views/mis-denuncias.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para timeline
     */
    public function shortcode_timeline($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);

        $denuncia_id = $atts['id'] ?: (isset($_GET['denuncia_id']) ? intval($_GET['denuncia_id']) : 0);

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/seguimiento-denuncias/views/timeline.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para estadisticas
     */
    public function shortcode_estadisticas($atts) {
        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/seguimiento-denuncias/views/estadisticas.php';
        return ob_get_clean();
    }

    /**
     * Registra handlers AJAX
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_denuncias_crear', [$this, 'ajax_crear']);
        add_action('wp_ajax_denuncias_actualizar_estado', [$this, 'ajax_actualizar_estado']);
        add_action('wp_ajax_denuncias_agregar_evento', [$this, 'ajax_agregar_evento']);
        add_action('wp_ajax_denuncias_seguir', [$this, 'ajax_seguir']);
        add_action('wp_ajax_denuncias_dejar_seguir', [$this, 'ajax_dejar_seguir']);

        add_action('wp_ajax_denuncias_listar', [$this, 'ajax_listar']);
        add_action('wp_ajax_nopriv_denuncias_listar', [$this, 'ajax_listar']);
    }

    /**
     * AJAX: Crear denuncia
     */
    public function ajax_crear() {
        check_ajax_referer('flavor_denuncias_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => 'Debes iniciar sesion.']);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        $fecha_presentacion = sanitize_text_field($_POST['fecha_presentacion'] ?? date('Y-m-d'));
        $plazo_dias = intval($_POST['plazo_respuesta'] ?? 30);
        $fecha_limite = date('Y-m-d', strtotime($fecha_presentacion . ' + ' . $plazo_dias . ' days'));

        $datos = [
            'titulo' => sanitize_text_field($_POST['titulo'] ?? ''),
            'descripcion' => wp_kses_post($_POST['descripcion'] ?? ''),
            'tipo' => sanitize_text_field($_POST['tipo'] ?? 'denuncia'),
            'categoria' => sanitize_text_field($_POST['categoria'] ?? ''),
            'ambito' => sanitize_text_field($_POST['ambito'] ?? 'municipal'),
            'organismo_destino' => sanitize_text_field($_POST['organismo_destino'] ?? ''),
            'numero_registro' => sanitize_text_field($_POST['numero_registro'] ?? ''),
            'fecha_presentacion' => $fecha_presentacion,
            'fecha_limite_respuesta' => $fecha_limite,
            'estado' => 'presentada',
            'prioridad' => sanitize_text_field($_POST['prioridad'] ?? 'media'),
            'denunciante_id' => get_current_user_id(),
            'denunciante_nombre' => sanitize_text_field($_POST['denunciante_nombre'] ?? wp_get_current_user()->display_name),
            'denunciante_tipo' => sanitize_text_field($_POST['denunciante_tipo'] ?? 'individual'),
            'colectivo_id' => intval($_POST['colectivo_id'] ?? 0) ?: null,
            'campania_id' => intval($_POST['campania_id'] ?? 0) ?: null,
            'visibilidad' => sanitize_text_field($_POST['visibilidad'] ?? 'miembros'),
        ];

        if (empty($datos['titulo']) || empty($datos['organismo_destino'])) {
            wp_send_json_error(['error' => 'Titulo y organismo son obligatorios.']);
        }

        $resultado = $wpdb->insert($tabla, $datos);

        if ($resultado === false) {
            wp_send_json_error(['error' => 'Error al registrar la denuncia.']);
        }

        $denuncia_id = $wpdb->insert_id;

        // Crear evento de creacion
        $this->crear_evento($denuncia_id, 'creacion', 'Denuncia registrada', 'Se ha registrado la denuncia en el sistema.');

        // Agregar como participante
        $tabla_participantes = $wpdb->prefix . 'flavor_seguimiento_denuncias_participantes';
        $wpdb->insert($tabla_participantes, [
            'denuncia_id' => $denuncia_id,
            'user_id' => get_current_user_id(),
            'rol' => 'denunciante',
        ]);

        wp_send_json_success([
            'denuncia_id' => $denuncia_id,
            'mensaje' => 'Denuncia registrada correctamente.',
        ]);
    }

    /**
     * AJAX: Actualizar estado
     */
    public function ajax_actualizar_estado() {
        check_ajax_referer('flavor_denuncias_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => 'Debes iniciar sesion.']);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        $denuncia_id = intval($_POST['denuncia_id'] ?? 0);
        $nuevo_estado = sanitize_text_field($_POST['estado'] ?? '');
        $nota = sanitize_textarea_field($_POST['nota'] ?? '');

        // Verificar permiso
        $denuncia = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", $denuncia_id));

        if (!$denuncia || ($denuncia->denunciante_id !== get_current_user_id() && !current_user_can('manage_options'))) {
            wp_send_json_error(['error' => 'No tienes permiso para modificar esta denuncia.']);
        }

        $estado_anterior = $denuncia->estado;

        $wpdb->update($tabla, ['estado' => $nuevo_estado], ['id' => $denuncia_id]);

        // Crear evento
        $this->crear_evento($denuncia_id, 'cambio_estado', 'Estado actualizado: ' . $nuevo_estado, $nota, $estado_anterior, $nuevo_estado);

        wp_send_json_success(['mensaje' => 'Estado actualizado.']);
    }

    /**
     * AJAX: Agregar evento
     */
    public function ajax_agregar_evento() {
        check_ajax_referer('flavor_denuncias_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => 'Debes iniciar sesion.']);
        }

        $denuncia_id = intval($_POST['denuncia_id'] ?? 0);
        $tipo = sanitize_text_field($_POST['tipo'] ?? 'nota');
        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');

        $this->crear_evento($denuncia_id, $tipo, $titulo, $descripcion);

        wp_send_json_success(['mensaje' => 'Evento agregado.']);
    }

    /**
     * Crea un evento en el timeline
     */
    private function crear_evento($denuncia_id, $tipo, $titulo, $descripcion = '', $estado_anterior = null, $estado_nuevo = null, $automatico = false) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias_eventos';

        $wpdb->insert($tabla, [
            'denuncia_id' => $denuncia_id,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'estado_anterior' => $estado_anterior,
            'estado_nuevo' => $estado_nuevo,
            'autor_id' => get_current_user_id() ?: 0,
            'automatico' => $automatico ? 1 : 0,
        ]);

        return $wpdb->insert_id;
    }

    /**
     * AJAX: Seguir denuncia
     */
    public function ajax_seguir() {
        check_ajax_referer('flavor_denuncias_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => 'Debes iniciar sesion.']);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias_participantes';

        $denuncia_id = intval($_POST['denuncia_id'] ?? 0);
        $user_id = get_current_user_id();

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE denuncia_id = %d AND user_id = %d",
            $denuncia_id, $user_id
        ));

        if ($existe) {
            wp_send_json_error(['error' => 'Ya sigues esta denuncia.']);
        }

        $wpdb->insert($tabla, [
            'denuncia_id' => $denuncia_id,
            'user_id' => $user_id,
            'rol' => 'seguidor',
        ]);

        wp_send_json_success(['mensaje' => 'Ahora sigues esta denuncia.']);
    }

    /**
     * AJAX: Listar denuncias
     */
    public function ajax_listar() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        $estado = sanitize_text_field($_POST['estado'] ?? '');
        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $limite = intval($_POST['limite'] ?? 12);

        $where = "WHERE visibilidad IN ('publica', 'miembros')";
        $params = [];

        if ($estado) {
            $where .= " AND estado = %s";
            $params[] = $estado;
        }
        if ($tipo) {
            $where .= " AND tipo = %s";
            $params[] = $tipo;
        }

        $params[] = $limite;

        $denuncias = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, tipo, estado, organismo_destino, fecha_presentacion, fecha_limite_respuesta, prioridad
             FROM $tabla $where ORDER BY created_at DESC LIMIT %d",
            $params
        ));

        wp_send_json_success(['denuncias' => $denuncias]);
    }

    /**
     * Obtiene una denuncia con datos relacionados
     */
    private function obtener_denuncia($denuncia_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        $denuncia = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", $denuncia_id));

        if (!$denuncia) return null;

        // Timeline de eventos
        $tabla_eventos = $wpdb->prefix . 'flavor_seguimiento_denuncias_eventos';
        $denuncia->eventos = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, u.display_name as autor_nombre
             FROM $tabla_eventos e
             LEFT JOIN {$wpdb->users} u ON e.autor_id = u.ID
             WHERE e.denuncia_id = %d ORDER BY e.created_at DESC",
            $denuncia_id
        ));

        // Participantes
        $tabla_participantes = $wpdb->prefix . 'flavor_seguimiento_denuncias_participantes';
        $denuncia->participantes = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.display_name, u.user_email
             FROM $tabla_participantes p
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
             WHERE p.denuncia_id = %d",
            $denuncia_id
        ));

        // Dias restantes
        if ($denuncia->fecha_limite_respuesta) {
            $hoy = new DateTime();
            $limite = new DateTime($denuncia->fecha_limite_respuesta);
            $denuncia->dias_restantes = $hoy->diff($limite)->days * ($limite < $hoy ? -1 : 1);
        }

        return $denuncia;
    }

    /**
     * Verifica plazos de denuncias (cron)
     */
    public function verificar_plazos() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        $dias_aviso = $this->get_setting('dias_aviso_plazo', 5);

        // Buscar denuncias proximas a vencer
        $proximas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla
             WHERE estado IN ('presentada', 'en_tramite')
             AND fecha_limite_respuesta IS NOT NULL
             AND fecha_limite_respuesta <= DATE_ADD(CURDATE(), INTERVAL %d DAY)
             AND fecha_limite_respuesta >= CURDATE()",
            $dias_aviso
        ));

        foreach ($proximas as $denuncia) {
            $this->crear_evento($denuncia->id, 'plazo_vencido', 'Aviso: plazo proximo a vencer', 'El plazo de respuesta vence el ' . $denuncia->fecha_limite_respuesta, null, null, true);
        }

        // Marcar como silencio administrativo
        $vencidas = $wpdb->get_results(
            "SELECT * FROM $tabla
             WHERE estado IN ('presentada', 'en_tramite')
             AND fecha_limite_respuesta < CURDATE()"
        );

        foreach ($vencidas as $denuncia) {
            $wpdb->update($tabla, ['estado' => 'silencio'], ['id' => $denuncia->id]);
            $this->crear_evento($denuncia->id, 'cambio_estado', 'Silencio administrativo', 'El plazo de respuesta ha vencido sin respuesta de la administracion.', $denuncia->estado, 'silencio', true);
        }
    }

    /**
     * Registra rutas REST API
     */
    private function register_rest_routes() {
        add_action('rest_api_init', function() {
            register_rest_route('flavor/v1', '/denuncias', [
                'methods' => 'GET',
                'callback' => [$this, 'api_listar'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('flavor/v1', '/denuncias/(?P<id>\d+)', [
                'methods' => 'GET',
                'callback' => [$this, 'api_obtener'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('flavor/v1', '/denuncias/(?P<id>\d+)/timeline', [
                'methods' => 'GET',
                'callback' => [$this, 'api_timeline'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    /**
     * API: Listar
     */
    public function api_listar($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        $limite = intval($request->get_param('limite')) ?: 12;

        $denuncias = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, tipo, estado, organismo_destino, fecha_presentacion, prioridad
             FROM $tabla WHERE visibilidad = 'publica' ORDER BY created_at DESC LIMIT %d",
            $limite
        ));

        return rest_ensure_response(['denuncias' => $denuncias]);
    }

    /**
     * API: Obtener
     */
    public function api_obtener($request) {
        $denuncia = $this->obtener_denuncia($request['id']);

        if (!$denuncia || ($denuncia->visibilidad === 'privada' && !is_user_logged_in())) {
            return new WP_Error('not_found', 'Denuncia no encontrada', ['status' => 404]);
        }

        return rest_ensure_response(['denuncia' => $denuncia]);
    }

    /**
     * API: Timeline
     */
    public function api_timeline($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias_eventos';

        $eventos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE denuncia_id = %d ORDER BY created_at DESC",
            $request['id']
        ));

        return rest_ensure_response(['eventos' => $eventos]);
    }

    /**
     * Carga assets frontend
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_assets()) return;

        wp_enqueue_style(
            'flavor-seguimiento-denuncias',
            FLAVOR_CHAT_IA_URL . 'includes/modules/seguimiento-denuncias/assets/css/seguimiento-denuncias.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-seguimiento-denuncias',
            FLAVOR_CHAT_IA_URL . 'includes/modules/seguimiento-denuncias/assets/js/seguimiento-denuncias.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-seguimiento-denuncias', 'flavorDenunciasConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_denuncias_nonce'),
        ]);
    }

    /**
     * Determina si cargar assets
     */
    private function should_load_assets() {
        global $post;
        if (!$post) return false;

        $shortcodes = ['denuncias_listar', 'denuncias_detalle', 'denuncias_crear', 'denuncias_mis_denuncias', 'denuncias_timeline', 'denuncias_estadisticas'];

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) return true;
        }

        return strpos($post->post_name, 'denuncia') !== false;
    }

    /**
     * Obtiene las acciones disponibles para IA
     */
    public function get_actions() {
        return [
            'listar_denuncias' => [
                'descripcion' => 'Lista las denuncias registradas',
                'parametros' => [
                    'estado' => ['tipo' => 'string', 'descripcion' => 'Filtrar por estado'],
                    'tipo' => ['tipo' => 'string', 'descripcion' => 'Filtrar por tipo'],
                ],
            ],
            'ver_denuncia' => [
                'descripcion' => 'Muestra el detalle y timeline de una denuncia',
                'parametros' => [
                    'denuncia_id' => ['tipo' => 'integer', 'descripcion' => 'ID de la denuncia', 'requerido' => true],
                ],
            ],
            'estadisticas_denuncias' => [
                'descripcion' => 'Muestra estadisticas de denuncias',
                'parametros' => [],
            ],
        ];
    }

    /**
     * Ejecuta una accion
     */
    public function execute_action($accion, $parametros = []) {
        switch ($accion) {
            case 'listar_denuncias':
                return $this->action_listar($parametros);
            case 'ver_denuncia':
                return $this->action_ver($parametros);
            case 'estadisticas_denuncias':
                return $this->action_estadisticas();
            default:
                return ['success' => false, 'error' => 'Accion no reconocida'];
        }
    }

    /**
     * Accion: Listar
     */
    private function action_listar($params) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        $denuncias = $wpdb->get_results(
            "SELECT id, titulo, tipo, estado, organismo_destino, fecha_presentacion
             FROM $tabla WHERE visibilidad IN ('publica', 'miembros') ORDER BY created_at DESC LIMIT 10"
        );

        return ['success' => true, 'denuncias' => $denuncias];
    }

    /**
     * Accion: Ver
     */
    private function action_ver($params) {
        $denuncia = $this->obtener_denuncia(intval($params['denuncia_id'] ?? 0));

        if (!$denuncia) {
            return ['success' => false, 'error' => 'Denuncia no encontrada'];
        }

        return ['success' => true, 'denuncia' => $denuncia];
    }

    /**
     * Accion: Estadisticas
     */
    private function action_estadisticas() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        $estadisticas = [
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla"),
            'en_tramite' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado IN ('presentada', 'en_tramite')"),
            'resueltas_favorable' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'resuelta_favorable'"),
            'silencio' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'silencio'"),
        ];

        return ['success' => true, 'estadisticas' => $estadisticas];
    }

    /**
     * Obtiene estados de denuncia
     */
    public function get_estados() {
        return [
            'presentada' => ['label' => 'Presentada', 'color' => '#3b82f6'],
            'en_tramite' => ['label' => 'En tramite', 'color' => '#f59e0b'],
            'requerimiento' => ['label' => 'Requerimiento', 'color' => '#ef4444'],
            'silencio' => ['label' => 'Silencio administrativo', 'color' => '#6b7280'],
            'resuelta_favorable' => ['label' => 'Resuelta favorable', 'color' => '#10b981'],
            'resuelta_desfavorable' => ['label' => 'Resuelta desfavorable', 'color' => '#ef4444'],
            'archivada' => ['label' => 'Archivada', 'color' => '#9ca3af'],
            'recurrida' => ['label' => 'Recurrida', 'color' => '#8b5cf6'],
        ];
    }

    /**
     * Renderiza widget dashboard
     */
    public function render_dashboard_widget() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        $estadisticas = [
            'activas' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado IN ('presentada', 'en_tramite', 'requerimiento')"),
            'silencio' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'silencio'"),
            'resueltas' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado IN ('resuelta_favorable', 'resuelta_desfavorable')"),
        ];

        include FLAVOR_CHAT_IA_PATH . 'includes/modules/seguimiento-denuncias/views/dashboard.php';
    }

    /**
     * Definiciones para IA
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'denuncias_listar',
                'description' => 'Lista las denuncias formales registradas y su estado',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => ['type' => 'string', 'description' => 'Filtrar por estado'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Base de conocimiento
     */
    public function get_knowledge_base() {
        return "El modulo de Seguimiento de Denuncias permite registrar y hacer seguimiento de denuncias formales presentadas ante administraciones publicas. Cada denuncia tiene un timeline de eventos, control de plazos de respuesta, y puede detectar automaticamente el silencio administrativo. Los estados incluyen: presentada, en tramite, requerimiento, silencio administrativo, resuelta favorable, resuelta desfavorable, archivada y recurrida.";
    }

    /**
     * FAQs
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Que es el silencio administrativo?',
                'respuesta' => 'Cuando la administracion no responde en el plazo establecido, se considera silencio administrativo. El sistema lo detecta automaticamente.',
            ],
            [
                'pregunta' => 'Como registro una denuncia?',
                'respuesta' => 'En la seccion de denuncias, usa el formulario para registrar los datos. El sistema calculara automaticamente el plazo de respuesta.',
            ],
        ];
    }
}
