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
        global $wpdb;
        return Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_seguimiento_denuncias');
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

        // Registrar en panel de administracion unificado
        $this->registrar_en_panel_unificado();

        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

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
        global $wpdb;
        if (!Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_seguimiento_denuncias')) {
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
                'permission_callback' => [$this, 'public_read_permission'],
            ]);

            register_rest_route('flavor/v1', '/denuncias/(?P<id>\d+)', [
                'methods' => 'GET',
                'callback' => [$this, 'api_obtener'],
                'permission_callback' => [$this, 'can_read_denuncia'],
            ]);

            register_rest_route('flavor/v1', '/denuncias/(?P<id>\d+)/timeline', [
                'methods' => 'GET',
                'callback' => [$this, 'api_timeline'],
                'permission_callback' => [$this, 'can_read_denuncia'],
            ]);
        });
    }

    /**
     * Lectura pública explícita.
     */
    public function public_read_permission() {
        return true;
    }

    /**
     * Permite leer una denuncia pública o privada solo a usuarios autenticados.
     */
    public function can_read_denuncia($request) {
        $denuncia = $this->obtener_denuncia($request['id']);

        if (!$denuncia) {
            return false;
        }

        if ($denuncia->visibilidad === 'publica') {
            return true;
        }

        return is_user_logged_in();
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
        $denuncia = $this->obtener_denuncia($request['id']);
        if (!$denuncia || ($denuncia->visibilidad !== 'publica' && !is_user_logged_in())) {
            return new WP_Error('not_found', 'Denuncia no encontrada', ['status' => 404]);
        }

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
     * Carga assets en admin del modulo.
     *
     * @param string $hook Hook actual de admin.
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        if (!is_admin()) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        if ($page === '' || strpos($page, 'denuncias-') !== 0) {
            return;
        }

        wp_enqueue_style(
            'flavor-seguimiento-denuncias-admin',
            FLAVOR_CHAT_IA_URL . 'includes/modules/seguimiento-denuncias/assets/css/seguimiento-denuncias.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );
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

    // =========================================================================
    // PANEL DE ADMINISTRACION UNIFICADO
    // =========================================================================

    /**
     * Configuracion para el Panel de Administracion Unificado
     *
     * @return array
     */
    public function get_admin_config() {
        return [
            'id'         => 'seguimiento_denuncias',
            'label'      => __('Seguimiento de Denuncias', 'flavor-platform'),
            'icon'       => 'dashicons-clipboard',
            'capability' => 'manage_options',
            'categoria'  => 'servicios',
            'paginas'    => [
                [
                    'slug'     => 'denuncias-dashboard',
                    'titulo'   => __('Dashboard', 'flavor-platform'),
                    'callback' => [$this, 'render_pagina_dashboard'],
                    'badge'    => [$this, 'contar_denuncias_pendientes'],
                ],
                [
                    'slug'     => 'denuncias-listado',
                    'titulo'   => __('Listado', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_listado'],
                ],
                [
                    'slug'     => 'denuncias-asignar',
                    'titulo'   => __('Asignar', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_asignar'],
                    'badge'    => [$this, 'contar_sin_asignar'],
                ],
                [
                    'slug'     => 'denuncias-estadisticas',
                    'titulo'   => __('Estadisticas', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_estadisticas'],
                ],
                [
                    'slug'     => 'denuncias-config',
                    'titulo'   => __('Configuracion', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_dashboard_widget'],
            'estadisticas'     => [$this, 'get_estadisticas_admin'],
        ];
    }

    /**
     * Cuenta denuncias pendientes de asignacion
     *
     * @return int
     */
    public function contar_denuncias_pendientes() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla WHERE estado IN ('presentada', 'en_tramite', 'requerimiento')"
        );
    }

    /**
     * Cuenta denuncias sin asignar responsable
     *
     * @return int
     */
    public function contar_sin_asignar() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        // Asumiendo que hay un campo responsable_id o similar
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla
             WHERE estado IN ('presentada', 'en_tramite')
             AND (denunciante_tipo = 'individual' OR colectivo_id IS NULL)"
        );
    }

    /**
     * Estadisticas para el panel de administracion
     *
     * @return array
     */
    public function get_estadisticas_admin() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        $total_abiertas = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla WHERE estado IN ('presentada', 'en_tramite', 'requerimiento')"
        );

        $resueltas_mes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla
             WHERE estado IN ('resuelta_favorable', 'resuelta_desfavorable')
             AND updated_at >= %s",
            date('Y-m-01')
        ));

        $en_silencio = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla WHERE estado = 'silencio'"
        );

        return [
            [
                'icon'   => 'dashicons-clipboard',
                'valor'  => $total_abiertas,
                'label'  => __('Denuncias abiertas', 'flavor-platform'),
                'color'  => 'blue',
                'enlace' => admin_url('admin.php?page=denuncias-listado&estado=abiertas'),
            ],
            [
                'icon'   => 'dashicons-yes-alt',
                'valor'  => $resueltas_mes,
                'label'  => __('Resueltas este mes', 'flavor-platform'),
                'color'  => 'green',
                'enlace' => admin_url('admin.php?page=denuncias-listado&estado=resueltas'),
            ],
            [
                'icon'   => 'dashicons-warning',
                'valor'  => $en_silencio,
                'label'  => __('En silencio adm.', 'flavor-platform'),
                'color'  => 'red',
                'enlace' => admin_url('admin.php?page=denuncias-listado&estado=silencio'),
            ],
        ];
    }

    /**
     * Renderiza el dashboard de administracion
     */
    public function render_admin_dashboard() {
        global $wpdb;
        $tabla_denuncias = $wpdb->prefix . 'flavor_seguimiento_denuncias';
        $tabla_seguimiento = $wpdb->prefix . 'flavor_seguimiento_denuncias_eventos';

        // KPIs principales
        $total_abiertas = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_denuncias WHERE estado IN ('presentada', 'en_tramite', 'requerimiento')"
        );

        $pendientes_asignacion = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_denuncias WHERE estado = 'presentada'"
        );

        $resueltas_mes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_denuncias
             WHERE estado IN ('resuelta_favorable', 'resuelta_desfavorable')
             AND updated_at >= %s",
            date('Y-m-01')
        ));

        // Tiempo medio de resolucion (en dias)
        $tiempo_medio_resolucion = $wpdb->get_var(
            "SELECT AVG(DATEDIFF(updated_at, fecha_presentacion))
             FROM $tabla_denuncias
             WHERE estado IN ('resuelta_favorable', 'resuelta_desfavorable')"
        );
        $tiempo_medio_resolucion = $tiempo_medio_resolucion ? round($tiempo_medio_resolucion, 1) : 0;

        // Denuncias recientes
        $denuncias_recientes = $wpdb->get_results(
            "SELECT d.*, u.display_name as denunciante_nombre_usuario
             FROM $tabla_denuncias d
             LEFT JOIN {$wpdb->users} u ON d.denunciante_id = u.ID
             ORDER BY d.created_at DESC LIMIT 10"
        );

        // Proximos vencimientos
        $proximos_vencimientos = $wpdb->get_results(
            "SELECT * FROM $tabla_denuncias
             WHERE estado IN ('presentada', 'en_tramite')
             AND fecha_limite_respuesta IS NOT NULL
             AND fecha_limite_respuesta >= CURDATE()
             ORDER BY fecha_limite_respuesta ASC LIMIT 5"
        );

        $estados = $this->get_estados();
        ?>
        <div class="wrap flavor-admin-dashboard">
            <?php $this->render_page_header(__('Dashboard de Denuncias', 'flavor-platform'), [
                ['label' => __('Nueva denuncia', 'flavor-platform'), 'url' => admin_url('admin.php?page=denuncias-listado&action=nueva'), 'class' => 'button-primary'],
                ['label' => __('Exportar', 'flavor-platform'), 'url' => admin_url('admin.php?page=denuncias-listado&action=exportar'), 'class' => ''],
            ]); ?>

            <?php if (method_exists($this, 'render_admin_module_hub')) : ?>
                <?php $this->render_admin_module_hub([
                    'description' => __('Acceso visible a listado, asignación, estadísticas, configuración y al bloque principal de KPIs.', 'flavor-platform'),
                    'stats_anchor' => '#denuncias-stats',
                    'extra_items' => [
                        [
                            'label' => __('Portal', 'flavor-platform'),
                            'url' => home_url('/mi-portal/seguimiento-denuncias/'),
                            'icon' => 'dashicons-external',
                        ],
                    ],
                ]); ?>
            <?php endif; ?>

            <!-- KPIs -->
            <div id="denuncias-stats" class="flavor-kpi-grid denuncias-admin-dashboard__kpi-grid">
                <div class="flavor-kpi-card denuncias-admin-dashboard__kpi-card denuncias-admin-dashboard__kpi-card--blue">
                    <div class="kpi-icon denuncias-admin-dashboard__kpi-icon">
                        <span class="dashicons dashicons-clipboard"></span>
                    </div>
                    <div class="kpi-value denuncias-admin-dashboard__kpi-value"><?php echo esc_html($total_abiertas); ?></div>
                    <div class="kpi-label denuncias-admin-dashboard__kpi-label"><?php _e('Denuncias abiertas', 'flavor-platform'); ?></div>
                </div>

                <div class="flavor-kpi-card denuncias-admin-dashboard__kpi-card denuncias-admin-dashboard__kpi-card--amber">
                    <div class="kpi-icon denuncias-admin-dashboard__kpi-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="kpi-value denuncias-admin-dashboard__kpi-value"><?php echo esc_html($pendientes_asignacion); ?></div>
                    <div class="kpi-label denuncias-admin-dashboard__kpi-label"><?php _e('Pendientes asignacion', 'flavor-platform'); ?></div>
                </div>

                <div class="flavor-kpi-card denuncias-admin-dashboard__kpi-card denuncias-admin-dashboard__kpi-card--green">
                    <div class="kpi-icon denuncias-admin-dashboard__kpi-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="kpi-value denuncias-admin-dashboard__kpi-value"><?php echo esc_html($resueltas_mes); ?></div>
                    <div class="kpi-label denuncias-admin-dashboard__kpi-label"><?php _e('Resueltas este mes', 'flavor-platform'); ?></div>
                </div>

                <div class="flavor-kpi-card denuncias-admin-dashboard__kpi-card denuncias-admin-dashboard__kpi-card--purple">
                    <div class="kpi-icon denuncias-admin-dashboard__kpi-icon">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="kpi-value denuncias-admin-dashboard__kpi-value"><?php echo esc_html($tiempo_medio_resolucion); ?> <small class="denuncias-admin-dashboard__kpi-unit"><?php _e('dias', 'flavor-platform'); ?></small></div>
                    <div class="kpi-label denuncias-admin-dashboard__kpi-label"><?php _e('Tiempo medio resolucion', 'flavor-platform'); ?></div>
                </div>
            </div>

            <div class="flavor-admin-columns denuncias-admin-dashboard__columns">
                <!-- Denuncias recientes -->
                <div class="flavor-admin-card denuncias-admin-dashboard__card">
                    <h2 class="denuncias-admin-dashboard__card-title">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('Denuncias recientes', 'flavor-platform'); ?>
                    </h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'flavor-platform'); ?></th>
                                <th><?php _e('Titulo', 'flavor-platform'); ?></th>
                                <th><?php _e('Estado', 'flavor-platform'); ?></th>
                                <th><?php _e('Organismo', 'flavor-platform'); ?></th>
                                <th><?php _e('Fecha', 'flavor-platform'); ?></th>
                                <th><?php _e('Acciones', 'flavor-platform'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($denuncias_recientes): ?>
                                <?php foreach ($denuncias_recientes as $denuncia): ?>
                                    <?php $estado_class = 'denuncias-estado-' . sanitize_html_class((string) $denuncia->estado); ?>
                                    <tr>
                                        <td>#<?php echo esc_html($denuncia->id); ?></td>
                                        <td>
                                            <strong><?php echo esc_html($denuncia->titulo); ?></strong>
                                            <br><small><?php echo esc_html($denuncia->denunciante_nombre_usuario ?: $denuncia->denunciante_nombre); ?></small>
                                        </td>
                                        <td>
                                            <span class="flavor-badge denuncias-admin-dashboard__badge <?php echo esc_attr($estado_class); ?>">
                                                <?php echo esc_html($estados[$denuncia->estado]['label'] ?? $denuncia->estado); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($denuncia->organismo_destino); ?></td>
                                        <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($denuncia->fecha_presentacion))); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=denuncias-listado&action=ver&id=' . $denuncia->id)); ?>" class="button button-small">
                                                <?php _e('Ver', 'flavor-platform'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6"><?php _e('No hay denuncias registradas', 'flavor-platform'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <p class="denuncias-admin-dashboard__card-footer-link">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=denuncias-listado')); ?>"><?php _e('Ver todas', 'flavor-platform'); ?> &rarr;</a>
                    </p>
                </div>

                <!-- Proximos vencimientos -->
                <div class="flavor-admin-card denuncias-admin-dashboard__card">
                    <h2 class="denuncias-admin-dashboard__card-title">
                        <span class="dashicons dashicons-warning"></span>
                        <?php _e('Proximos vencimientos', 'flavor-platform'); ?>
                    </h2>
                    <?php if ($proximos_vencimientos): ?>
                        <ul class="flavor-timeline denuncias-admin-dashboard__timeline">
                            <?php foreach ($proximos_vencimientos as $denuncia):
                                $dias_restantes = floor((strtotime($denuncia->fecha_limite_respuesta) - time()) / 86400);
                                $urgente = $dias_restantes <= 5;
                            ?>
                                <li class="denuncias-admin-dashboard__timeline-item<?php echo $urgente ? ' denuncias-admin-dashboard__timeline-item--urgent' : ''; ?>">
                                    <div class="denuncias-admin-dashboard__timeline-head">
                                        <div>
                                            <strong>#<?php echo esc_html($denuncia->id); ?></strong>
                                            <span><?php echo esc_html(wp_trim_words($denuncia->titulo, 5)); ?></span>
                                        </div>
                                        <span class="flavor-badge denuncias-admin-dashboard__badge <?php echo $urgente ? 'denuncias-vencimiento-urgente' : 'denuncias-vencimiento-proximo'; ?>">
                                            <?php echo sprintf(_n('%d dia', '%d dias', $dias_restantes, 'flavor-platform'), $dias_restantes); ?>
                                        </span>
                                    </div>
                                    <small class="denuncias-admin-dashboard__timeline-meta">
                                        <?php echo esc_html($denuncia->organismo_destino); ?> -
                                        <?php _e('Vence:', 'flavor-platform'); ?> <?php echo esc_html(date_i18n('d/m/Y', strtotime($denuncia->fecha_limite_respuesta))); ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="denuncias-admin-dashboard__empty">
                            <span class="dashicons dashicons-yes-alt denuncias-admin-dashboard__empty-icon"></span><br>
                            <?php _e('No hay vencimientos proximos', 'flavor-platform'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar página dashboard con vista completa
     */
    public function render_pagina_dashboard() {
        $this->render_admin_dashboard();
    }

    /**
     * Renderiza el listado de denuncias en admin
     */
    public function render_admin_listado() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        // Filtros
        $estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        $prioridad_filtro = isset($_GET['prioridad']) ? sanitize_text_field($_GET['prioridad']) : '';
        $busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        // Paginacion
        $por_pagina = 20;
        $pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($pagina_actual - 1) * $por_pagina;

        // Construir query
        $where_clauses = ['1=1'];
        $params = [];

        if ($estado_filtro) {
            if ($estado_filtro === 'abiertas') {
                $where_clauses[] = "estado IN ('presentada', 'en_tramite', 'requerimiento')";
            } elseif ($estado_filtro === 'resueltas') {
                $where_clauses[] = "estado IN ('resuelta_favorable', 'resuelta_desfavorable')";
            } else {
                $where_clauses[] = "estado = %s";
                $params[] = $estado_filtro;
            }
        }

        if ($prioridad_filtro) {
            $where_clauses[] = "prioridad = %s";
            $params[] = $prioridad_filtro;
        }

        if ($busqueda) {
            $where_clauses[] = "(titulo LIKE %s OR descripcion LIKE %s OR organismo_destino LIKE %s)";
            $busqueda_like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $busqueda_like;
            $params[] = $busqueda_like;
            $params[] = $busqueda_like;
        }

        $where = implode(' AND ', $where_clauses);

        // Total
        $total_query = "SELECT COUNT(*) FROM $tabla WHERE $where";
        $total = $params ? $wpdb->get_var($wpdb->prepare($total_query, $params)) : $wpdb->get_var($total_query);

        // Resultados
        $params[] = $por_pagina;
        $params[] = $offset;
        $query = "SELECT d.*, u.display_name as denunciante_nombre_usuario
                  FROM $tabla d
                  LEFT JOIN {$wpdb->users} u ON d.denunciante_id = u.ID
                  WHERE $where
                  ORDER BY d.created_at DESC
                  LIMIT %d OFFSET %d";
        $denuncias = $wpdb->get_results($wpdb->prepare($query, $params));

        $estados = $this->get_estados();
        $total_paginas = ceil($total / $por_pagina);
        ?>
        <div class="wrap">
            <?php $this->render_page_header(__('Listado de Denuncias', 'flavor-platform'), [
                ['label' => __('Nueva denuncia', 'flavor-platform'), 'url' => admin_url('admin.php?page=denuncias-listado&action=nueva'), 'class' => 'button-primary'],
            ]); ?>

            <!-- Filtros -->
            <div class="tablenav top">
                <form method="get" class="denuncias-admin-list__filters">
                    <input type="hidden" name="page" value="denuncias-listado">

                    <select name="estado">
                        <option value=""><?php _e('Todos los estados', 'flavor-platform'); ?></option>
                        <option value="abiertas" <?php selected($estado_filtro, 'abiertas'); ?>><?php _e('Abiertas', 'flavor-platform'); ?></option>
                        <option value="resueltas" <?php selected($estado_filtro, 'resueltas'); ?>><?php _e('Resueltas', 'flavor-platform'); ?></option>
                        <?php foreach ($estados as $estado_key => $estado_data): ?>
                            <option value="<?php echo esc_attr($estado_key); ?>" <?php selected($estado_filtro, $estado_key); ?>>
                                <?php echo esc_html($estado_data['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="prioridad">
                        <option value=""><?php _e('Todas las prioridades', 'flavor-platform'); ?></option>
                        <option value="urgente" <?php selected($prioridad_filtro, 'urgente'); ?>><?php _e('Urgente', 'flavor-platform'); ?></option>
                        <option value="alta" <?php selected($prioridad_filtro, 'alta'); ?>><?php _e('Alta', 'flavor-platform'); ?></option>
                        <option value="media" <?php selected($prioridad_filtro, 'media'); ?>><?php _e('Media', 'flavor-platform'); ?></option>
                        <option value="baja" <?php selected($prioridad_filtro, 'baja'); ?>><?php _e('Baja', 'flavor-platform'); ?></option>
                    </select>

                    <input type="search" name="s" value="<?php echo esc_attr($busqueda); ?>" placeholder="<?php _e('Buscar...', 'flavor-platform'); ?>">

                    <button type="submit" class="button"><?php _e('Filtrar', 'flavor-platform'); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=denuncias-listado')); ?>" class="button"><?php _e('Limpiar', 'flavor-platform'); ?></a>
                </form>
            </div>

            <!-- Tabla -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="denuncias-admin-list__col-id"><?php _e('ID', 'flavor-platform'); ?></th>
                        <th><?php _e('Titulo', 'flavor-platform'); ?></th>
                        <th><?php _e('Estado', 'flavor-platform'); ?></th>
                        <th><?php _e('Prioridad', 'flavor-platform'); ?></th>
                        <th><?php _e('Organismo', 'flavor-platform'); ?></th>
                        <th><?php _e('Denunciante', 'flavor-platform'); ?></th>
                        <th><?php _e('Fecha', 'flavor-platform'); ?></th>
                        <th><?php _e('Vencimiento', 'flavor-platform'); ?></th>
                        <th class="denuncias-admin-list__col-actions"><?php _e('Acciones', 'flavor-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($denuncias): ?>
                        <?php foreach ($denuncias as $denuncia): ?>
                            <?php
                            $estado_class = 'denuncias-estado-' . sanitize_html_class((string) $denuncia->estado);
                            $prioridad_class = 'denuncias-prioridad-' . sanitize_html_class((string) $denuncia->prioridad);
                            ?>
                            <tr>
                                <td>#<?php echo esc_html($denuncia->id); ?></td>
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=denuncias-listado&action=ver&id=' . $denuncia->id)); ?>">
                                            <?php echo esc_html($denuncia->titulo); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td>
                                    <span class="flavor-badge denuncias-admin-list__badge <?php echo esc_attr($estado_class); ?>">
                                        <?php echo esc_html($estados[$denuncia->estado]['label'] ?? $denuncia->estado); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="denuncias-admin-list__priority <?php echo esc_attr($prioridad_class); ?>">
                                        <?php echo esc_html(ucfirst($denuncia->prioridad)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($denuncia->organismo_destino); ?></td>
                                <td><?php echo esc_html($denuncia->denunciante_nombre_usuario ?: $denuncia->denunciante_nombre); ?></td>
                                <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($denuncia->fecha_presentacion))); ?></td>
                                <td>
                                    <?php if ($denuncia->fecha_limite_respuesta): ?>
                                        <?php echo esc_html(date_i18n('d/m/Y', strtotime($denuncia->fecha_limite_respuesta))); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=denuncias-listado&action=ver&id=' . $denuncia->id)); ?>" class="button button-small" title="<?php _e('Ver', 'flavor-platform'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=denuncias-listado&action=editar&id=' . $denuncia->id)); ?>" class="button button-small" title="<?php _e('Editar', 'flavor-platform'); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="denuncias-admin-list__empty-cell">
                                <?php _e('No se encontraron denuncias', 'flavor-platform'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Paginacion -->
            <?php if ($total_paginas > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo sprintf(_n('%s elemento', '%s elementos', $total, 'flavor-platform'), number_format_i18n($total)); ?></span>
                        <span class="pagination-links">
                            <?php
                            echo paginate_links([
                                'base'      => add_query_arg('paged', '%#%'),
                                'format'    => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total'     => $total_paginas,
                                'current'   => $pagina_actual,
                            ]);
                            ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza la pagina de asignacion de denuncias
     */
    public function render_admin_asignar() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        // Obtener denuncias pendientes de asignacion
        $denuncias_pendientes = $wpdb->get_results(
            "SELECT d.*, u.display_name as denunciante_nombre_usuario
             FROM $tabla d
             LEFT JOIN {$wpdb->users} u ON d.denunciante_id = u.ID
             WHERE d.estado = 'presentada'
             ORDER BY d.prioridad DESC, d.created_at ASC"
        );

        // Obtener usuarios que pueden ser responsables
        $responsables = get_users([
            'role__in' => ['administrator', 'editor'],
            'orderby'  => 'display_name',
        ]);

        $estados = $this->get_estados();
        ?>
        <div class="wrap">
            <?php $this->render_page_header(__('Asignar Denuncias', 'flavor-platform')); ?>

            <?php if ($denuncias_pendientes): ?>
                <div class="flavor-asignar-grid denuncias-admin-asignar__grid">
                    <?php foreach ($denuncias_pendientes as $denuncia): ?>
                        <?php $estado_class = 'denuncias-estado-' . sanitize_html_class((string) $denuncia->estado); ?>
                        <div class="flavor-asignar-card denuncias-admin-asignar__card">
                            <div class="denuncia-info">
                                <h3 class="denuncias-admin-asignar__title">
                                    #<?php echo esc_html($denuncia->id); ?> - <?php echo esc_html($denuncia->titulo); ?>
                                    <span class="flavor-badge denuncias-admin-asignar__badge <?php echo esc_attr($estado_class); ?>">
                                        <?php echo esc_html($estados[$denuncia->estado]['label'] ?? $denuncia->estado); ?>
                                    </span>
                                </h3>
                                <p class="denuncias-admin-asignar__meta">
                                    <strong><?php _e('Organismo:', 'flavor-platform'); ?></strong> <?php echo esc_html($denuncia->organismo_destino); ?><br>
                                    <strong><?php _e('Denunciante:', 'flavor-platform'); ?></strong> <?php echo esc_html($denuncia->denunciante_nombre_usuario ?: $denuncia->denunciante_nombre); ?><br>
                                    <strong><?php _e('Fecha:', 'flavor-platform'); ?></strong> <?php echo esc_html(date_i18n('d/m/Y', strtotime($denuncia->fecha_presentacion))); ?>
                                    <?php if ($denuncia->prioridad === 'urgente' || $denuncia->prioridad === 'alta'): ?>
                                        <span class="denuncias-admin-asignar__priority">
                                            <?php _e('Prioridad:', 'flavor-platform'); ?> <?php echo esc_html(ucfirst($denuncia->prioridad)); ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="denuncia-asignacion">
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="denuncias-admin-asignar__form">
                                    <input type="hidden" name="action" value="denuncias_asignar">
                                    <input type="hidden" name="denuncia_id" value="<?php echo esc_attr($denuncia->id); ?>">
                                    <?php wp_nonce_field('denuncias_asignar_' . $denuncia->id); ?>

                                    <select name="responsable_id" required class="denuncias-admin-asignar__select">
                                        <option value=""><?php _e('Seleccionar responsable...', 'flavor-platform'); ?></option>
                                        <?php foreach ($responsables as $usuario): ?>
                                            <option value="<?php echo esc_attr($usuario->ID); ?>">
                                                <?php echo esc_html($usuario->display_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit" class="button button-primary"><?php _e('Asignar', 'flavor-platform'); ?></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="notice notice-success">
                    <p>
                        <span class="dashicons dashicons-yes-alt denuncias-admin-asignar__icon-ok"></span>
                        <?php _e('No hay denuncias pendientes de asignacion.', 'flavor-platform'); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza la pagina de estadisticas
     */
    public function render_admin_estadisticas() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

        // Estadisticas por estado
        $por_estado = $wpdb->get_results(
            "SELECT estado, COUNT(*) as total FROM $tabla GROUP BY estado ORDER BY total DESC"
        );

        // Estadisticas por tipo
        $por_tipo = $wpdb->get_results(
            "SELECT tipo, COUNT(*) as total FROM $tabla GROUP BY tipo ORDER BY total DESC"
        );

        // Estadisticas por ambito
        $por_ambito = $wpdb->get_results(
            "SELECT ambito, COUNT(*) as total FROM $tabla GROUP BY ambito ORDER BY total DESC"
        );

        // Evolucion mensual (ultimos 12 meses)
        $evolucion_mensual = $wpdb->get_results(
            "SELECT
                DATE_FORMAT(fecha_presentacion, '%Y-%m') as mes,
                COUNT(*) as total,
                SUM(CASE WHEN estado IN ('resuelta_favorable', 'resuelta_desfavorable') THEN 1 ELSE 0 END) as resueltas
             FROM $tabla
             WHERE fecha_presentacion >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(fecha_presentacion, '%Y-%m')
             ORDER BY mes ASC"
        );

        // Tiempos de resolucion por organismo
        $tiempos_organismo = $wpdb->get_results(
            "SELECT
                organismo_destino,
                COUNT(*) as total,
                AVG(DATEDIFF(updated_at, fecha_presentacion)) as tiempo_medio
             FROM $tabla
             WHERE estado IN ('resuelta_favorable', 'resuelta_desfavorable')
             GROUP BY organismo_destino
             HAVING total >= 2
             ORDER BY tiempo_medio ASC
             LIMIT 10"
        );

        $estados = $this->get_estados();
        ?>
        <div class="wrap">
            <?php $this->render_page_header(__('Estadisticas de Denuncias', 'flavor-platform'), [
                ['label' => __('Exportar PDF', 'flavor-platform'), 'url' => '#', 'class' => ''],
            ]); ?>

            <div class="flavor-stats-grid denuncias-admin-estadisticas__grid">

                <!-- Por estado -->
                <div class="flavor-stats-card denuncias-admin-estadisticas__card">
                    <h3 class="denuncias-admin-estadisticas__title">
                        <span class="dashicons dashicons-chart-pie"></span>
                        <?php _e('Por estado', 'flavor-platform'); ?>
                    </h3>
                    <table class="widefat">
                        <tbody>
                            <?php foreach ($por_estado as $item): ?>
                                <?php $estado_class = 'denuncias-estado-' . sanitize_html_class((string) $item->estado); ?>
                                <tr>
                                    <td>
                                        <span class="flavor-badge denuncias-admin-estadisticas__badge <?php echo esc_attr($estado_class); ?>">
                                            <?php echo esc_html($estados[$item->estado]['label'] ?? $item->estado); ?>
                                        </span>
                                    </td>
                                    <td class="denuncias-admin-estadisticas__td-right-strong"><?php echo esc_html($item->total); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Por tipo -->
                <div class="flavor-stats-card denuncias-admin-estadisticas__card">
                    <h3 class="denuncias-admin-estadisticas__title">
                        <span class="dashicons dashicons-category"></span>
                        <?php _e('Por tipo', 'flavor-platform'); ?>
                    </h3>
                    <table class="widefat">
                        <tbody>
                            <?php foreach ($por_tipo as $item): ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst($item->tipo)); ?></td>
                                    <td class="denuncias-admin-estadisticas__td-right-strong"><?php echo esc_html($item->total); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Evolucion mensual -->
                <div class="flavor-stats-card denuncias-admin-estadisticas__card denuncias-admin-estadisticas__card--span2">
                    <h3 class="denuncias-admin-estadisticas__title">
                        <span class="dashicons dashicons-chart-line"></span>
                        <?php _e('Evolucion mensual (ultimos 12 meses)', 'flavor-platform'); ?>
                    </h3>
                    <div class="flavor-chart-container denuncias-admin-estadisticas__chart">
                        <?php
                        $max_valor = 1;
                        foreach ($evolucion_mensual as $mes) {
                            if ($mes->total > $max_valor) $max_valor = $mes->total;
                        }
                        foreach ($evolucion_mensual as $mes):
                            $altura_total = ($mes->total / $max_valor) * 200;
                            $altura_resueltas = ($mes->resueltas / $max_valor) * 200;
                        ?>
                            <div class="denuncias-admin-estadisticas__chart-col">
                                <div class="denuncias-admin-estadisticas__chart-col-inner">
                                    <div class="denuncias-admin-estadisticas__bar-total" style="height: <?php echo esc_attr($altura_total); ?>px;">
                                        <div class="denuncias-admin-estadisticas__bar-resueltas" style="height: <?php echo esc_attr($altura_resueltas); ?>px;"></div>
                                    </div>
                                </div>
                                <small class="denuncias-admin-estadisticas__chart-month"><?php echo esc_html(date_i18n('M', strtotime($mes->mes . '-01'))); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="denuncias-admin-estadisticas__legend">
                        <span><span class="denuncias-admin-estadisticas__legend-swatch denuncias-admin-estadisticas__legend-swatch--blue"></span><?php _e('Total', 'flavor-platform'); ?></span>
                        <span><span class="denuncias-admin-estadisticas__legend-swatch denuncias-admin-estadisticas__legend-swatch--green"></span><?php _e('Resueltas', 'flavor-platform'); ?></span>
                    </div>
                </div>

                <!-- Tiempos por organismo -->
                <div class="flavor-stats-card denuncias-admin-estadisticas__card denuncias-admin-estadisticas__card--span2">
                    <h3 class="denuncias-admin-estadisticas__title">
                        <span class="dashicons dashicons-clock"></span>
                        <?php _e('Tiempos medios de resolucion por organismo', 'flavor-platform'); ?>
                    </h3>
                    <?php if ($tiempos_organismo): ?>
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('Organismo', 'flavor-platform'); ?></th>
                                    <th><?php _e('Denuncias resueltas', 'flavor-platform'); ?></th>
                                    <th><?php _e('Tiempo medio (dias)', 'flavor-platform'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tiempos_organismo as $organismo): ?>
                                    <tr>
                                        <td><?php echo esc_html($organismo->organismo_destino); ?></td>
                                        <td><?php echo esc_html($organismo->total); ?></td>
                                        <td>
                                            <strong><?php echo esc_html(round($organismo->tiempo_medio, 1)); ?></strong>
                                            <?php _e('dias', 'flavor-platform'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="denuncias-admin-estadisticas__empty"><?php _e('No hay suficientes datos para mostrar', 'flavor-platform'); ?></p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la pagina de configuracion
     */
    public function render_admin_config() {
        // Guardar configuracion si se envia el formulario
        if (isset($_POST['denuncias_config_submit']) && check_admin_referer('denuncias_config_nonce')) {
            $this->guardar_configuracion();
            echo '<div class="notice notice-success"><p>' . __('Configuracion guardada correctamente.', 'flavor-platform') . '</p></div>';
        }

        $configuracion = $this->get_default_settings();
        $configuracion = wp_parse_args(get_option('flavor_denuncias_config', []), $configuracion);

        // Categorias guardadas
        $categorias = get_option('flavor_denuncias_categorias', [
            'urbanismo' => __('Urbanismo', 'flavor-platform'),
            'medioambiente' => __('Medio Ambiente', 'flavor-platform'),
            'consumo' => __('Consumo', 'flavor-platform'),
            'administracion' => __('Administracion Publica', 'flavor-platform'),
            'transporte' => __('Transporte', 'flavor-platform'),
            'sanidad' => __('Sanidad', 'flavor-platform'),
            'educacion' => __('Educacion', 'flavor-platform'),
            'otro' => __('Otro', 'flavor-platform'),
        ]);
        ?>
        <div class="wrap">
            <?php $this->render_page_header(__('Configuracion de Denuncias', 'flavor-platform')); ?>

            <form method="post">
                <?php wp_nonce_field('denuncias_config_nonce'); ?>

                <div class="flavor-config-grid denuncias-admin-config__grid">

                    <!-- Configuracion general -->
                    <div class="flavor-config-card denuncias-admin-config__card">
                        <h3 class="denuncias-admin-config__title">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php _e('Configuracion general', 'flavor-platform'); ?>
                        </h3>

                        <table class="form-table">
                            <tr>
                                <th><?php _e('Plazo de respuesta por defecto', 'flavor-platform'); ?></th>
                                <td>
                                    <input type="number" name="plazo_respuesta_defecto" value="<?php echo esc_attr($configuracion['plazo_respuesta_defecto']); ?>" min="1" max="365" class="small-text">
                                    <?php _e('dias', 'flavor-platform'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Dias de aviso antes del vencimiento', 'flavor-platform'); ?></th>
                                <td>
                                    <input type="number" name="dias_aviso_plazo" value="<?php echo esc_attr($configuracion['dias_aviso_plazo']); ?>" min="1" max="30" class="small-text">
                                    <?php _e('dias', 'flavor-platform'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Notificar plazos', 'flavor-platform'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="notificar_plazos" value="1" <?php checked($configuracion['notificar_plazos']); ?>>
                                        <?php _e('Enviar notificaciones cuando un plazo este proximo a vencer', 'flavor-platform'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Permitir denuncias anonimas', 'flavor-platform'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="permitir_denuncias_anonimas" value="1" <?php checked($configuracion['permitir_denuncias_anonimas']); ?>>
                                        <?php _e('Permitir registrar denuncias sin identificacion', 'flavor-platform'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Requiere aprobacion', 'flavor-platform'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="requiere_aprobacion" value="1" <?php checked($configuracion['requiere_aprobacion']); ?>>
                                        <?php _e('Las denuncias requieren aprobacion antes de publicarse', 'flavor-platform'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Categorias -->
                    <div class="flavor-config-card denuncias-admin-config__card">
                        <h3 class="denuncias-admin-config__title">
                            <span class="dashicons dashicons-category"></span>
                            <?php _e('Categorias de denuncias', 'flavor-platform'); ?>
                        </h3>

                        <div id="categorias-list">
                            <?php foreach ($categorias as $slug => $nombre): ?>
                                <div class="categoria-item denuncias-admin-config__categoria-item">
                                    <input type="text" name="categorias_slug[]" value="<?php echo esc_attr($slug); ?>" placeholder="<?php _e('Slug', 'flavor-platform'); ?>" class="denuncias-admin-config__categoria-slug">
                                    <input type="text" name="categorias_nombre[]" value="<?php echo esc_attr($nombre); ?>" placeholder="<?php _e('Nombre', 'flavor-platform'); ?>" class="denuncias-admin-config__categoria-nombre">
                                    <button type="button" class="button eliminar-categoria" title="<?php _e('Eliminar', 'flavor-platform'); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="agregar-categoria" class="button">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php _e('Agregar categoria', 'flavor-platform'); ?>
                        </button>
                    </div>

                    <!-- Flujo de estados -->
                    <div class="flavor-config-card denuncias-admin-config__card denuncias-admin-config__card--span2">
                        <h3 class="denuncias-admin-config__title">
                            <span class="dashicons dashicons-randomize"></span>
                            <?php _e('Flujo de estados', 'flavor-platform'); ?>
                        </h3>

                        <p><?php _e('Los estados disponibles y sus transiciones:', 'flavor-platform'); ?></p>

                        <div class="flujo-estados denuncias-admin-config__flujo-estados">
                            <?php
                            $estados = $this->get_estados();
                            foreach ($estados as $estado_key => $estado_data): ?>
                                <div class="estado-chip denuncias-admin-config__estado-chip denuncias-estado-<?php echo esc_attr(sanitize_html_class((string) $estado_key)); ?>">
                                    <?php echo esc_html($estado_data['label']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <p class="denuncias-admin-config__help">
                            <?php _e('El flujo de estados esta predefinido: Presentada -> En tramite -> (Requerimiento) -> Resuelta/Silencio -> Archivada. Las denuncias pueden ser recurridas en cualquier momento.', 'flavor-platform'); ?>
                        </p>
                    </div>

                    <!-- Notificaciones -->
                    <div class="flavor-config-card denuncias-admin-config__card denuncias-admin-config__card--span2">
                        <h3 class="denuncias-admin-config__title">
                            <span class="dashicons dashicons-email"></span>
                            <?php _e('Configuracion de notificaciones', 'flavor-platform'); ?>
                        </h3>

                        <table class="form-table">
                            <tr>
                                <th><?php _e('Notificar al denunciante cuando:', 'flavor-platform'); ?></th>
                                <td>
                                    <label class="denuncias-admin-config__label-checkbox">
                                        <input type="checkbox" name="notif_cambio_estado" value="1" checked>
                                        <?php _e('Cambia el estado de su denuncia', 'flavor-platform'); ?>
                                    </label>
                                    <label class="denuncias-admin-config__label-checkbox">
                                        <input type="checkbox" name="notif_nuevo_evento" value="1" checked>
                                        <?php _e('Se agrega un nuevo evento al timeline', 'flavor-platform'); ?>
                                    </label>
                                    <label class="denuncias-admin-config__label-checkbox">
                                        <input type="checkbox" name="notif_plazo_proximo" value="1" checked>
                                        <?php _e('El plazo de respuesta esta proximo a vencer', 'flavor-platform'); ?>
                                    </label>
                                    <label class="denuncias-admin-config__label-checkbox">
                                        <input type="checkbox" name="notif_silencio" value="1" checked>
                                        <?php _e('Se detecta silencio administrativo', 'flavor-platform'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Email de administracion', 'flavor-platform'); ?></th>
                                <td>
                                    <input type="email" name="email_admin" value="<?php echo esc_attr(get_option('admin_email')); ?>" class="regular-text">
                                    <p class="description"><?php _e('Recibira notificaciones de nuevas denuncias', 'flavor-platform'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>

                <p class="submit">
                    <input type="submit" name="denuncias_config_submit" class="button-primary" value="<?php _e('Guardar configuracion', 'flavor-platform'); ?>">
                </p>
            </form>

            <script>
            jQuery(document).ready(function($) {
                // Agregar categoria
                $('#agregar-categoria').on('click', function() {
                    var html = '<div class="categoria-item denuncias-admin-config__categoria-item">' +
                        '<input type="text" name="categorias_slug[]" placeholder="<?php _e('Slug', 'flavor-platform'); ?>" class="denuncias-admin-config__categoria-slug">' +
                        '<input type="text" name="categorias_nombre[]" placeholder="<?php _e('Nombre', 'flavor-platform'); ?>" class="denuncias-admin-config__categoria-nombre">' +
                        '<button type="button" class="button eliminar-categoria" title="<?php _e('Eliminar', 'flavor-platform'); ?>">' +
                        '<span class="dashicons dashicons-trash"></span></button></div>';
                    $('#categorias-list').append(html);
                });

                // Eliminar categoria
                $(document).on('click', '.eliminar-categoria', function() {
                    $(this).closest('.categoria-item').remove();
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * Guarda la configuracion del modulo
     */
    private function guardar_configuracion() {
        $configuracion = [
            'plazo_respuesta_defecto'    => intval($_POST['plazo_respuesta_defecto'] ?? 30),
            'dias_aviso_plazo'           => intval($_POST['dias_aviso_plazo'] ?? 5),
            'notificar_plazos'           => isset($_POST['notificar_plazos']),
            'permitir_denuncias_anonimas'=> isset($_POST['permitir_denuncias_anonimas']),
            'requiere_aprobacion'        => isset($_POST['requiere_aprobacion']),
        ];

        update_option('flavor_denuncias_config', $configuracion);

        // Guardar categorias
        $categorias_slug = isset($_POST['categorias_slug']) ? array_map('sanitize_key', $_POST['categorias_slug']) : [];
        $categorias_nombre = isset($_POST['categorias_nombre']) ? array_map('sanitize_text_field', $_POST['categorias_nombre']) : [];

        $categorias = [];
        foreach ($categorias_slug as $indice => $slug) {
            if (!empty($slug) && isset($categorias_nombre[$indice])) {
                $categorias[$slug] = $categorias_nombre[$indice];
            }
        }

        if (!empty($categorias)) {
            update_option('flavor_denuncias_categorias', $categorias);
        }
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'seguimiento-denuncias',
            'title'    => __('Seguimiento de Denuncias', 'flavor-platform'),
            'subtitle' => __('Registra y sigue denuncias formales', 'flavor-platform'),
            'icon'     => '📝',
            'color'    => 'error', // Usa variable CSS --flavor-error del tema

            'database' => [
                'table'       => 'flavor_seguimiento_denuncias',
                'primary_key' => 'id',
            ],

            'fields' => [
                'titulo'           => ['type' => 'text', 'label' => __('Título', 'flavor-platform'), 'required' => true],
                'descripcion'      => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-platform')],
                'administracion'   => ['type' => 'text', 'label' => __('Administración', 'flavor-platform'), 'required' => true],
                'fecha_presentacion'=> ['type' => 'date', 'label' => __('Fecha presentación', 'flavor-platform'), 'required' => true],
                'numero_registro'  => ['type' => 'text', 'label' => __('Nº registro', 'flavor-platform')],
                'plazo_dias'       => ['type' => 'number', 'label' => __('Plazo (días)', 'flavor-platform'), 'default' => 30],
                'documentos'       => ['type' => 'file', 'label' => __('Documentos', 'flavor-platform'), 'multiple' => true],
                'estado'           => ['type' => 'select', 'label' => __('Estado', 'flavor-platform')],
            ],

            'estados' => [
                'presentada'       => ['label' => __('Presentada', 'flavor-platform'), 'color' => 'blue', 'icon' => '📤'],
                'en_tramite'       => ['label' => __('En trámite', 'flavor-platform'), 'color' => 'yellow', 'icon' => '⏳'],
                'requerimiento'    => ['label' => __('Requerimiento', 'flavor-platform'), 'color' => 'orange', 'icon' => '📋'],
                'silencio'         => ['label' => __('Silencio administrativo', 'flavor-platform'), 'color' => 'red', 'icon' => '🔇'],
                'favorable'        => ['label' => __('Resuelta favorable', 'flavor-platform'), 'color' => 'green', 'icon' => '✅'],
                'desfavorable'     => ['label' => __('Resuelta desfavorable', 'flavor-platform'), 'color' => 'red', 'icon' => '❌'],
                'archivada'        => ['label' => __('Archivada', 'flavor-platform'), 'color' => 'gray', 'icon' => '📁'],
                'recurrida'        => ['label' => __('Recurrida', 'flavor-platform'), 'color' => 'purple', 'icon' => '🔄'],
            ],

            'stats' => [
                [
                    'key'   => 'total_denuncias',
                    'label' => __('Denuncias', 'flavor-platform'),
                    'icon'  => '📝',
                    'color' => 'red',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_seguimiento_denuncias WHERE user_id = {user_id}",
                ],
                [
                    'key'   => 'en_tramite',
                    'label' => __('En trámite', 'flavor-platform'),
                    'icon'  => '⏳',
                    'color' => 'yellow',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_seguimiento_denuncias WHERE user_id = {user_id} AND estado IN ('presentada', 'en_tramite', 'requerimiento')",
                ],
                [
                    'key'   => 'silencio',
                    'label' => __('Silencio adm.', 'flavor-platform'),
                    'icon'  => '🔇',
                    'color' => 'red',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_seguimiento_denuncias WHERE user_id = {user_id} AND estado = 'silencio'",
                ],
                [
                    'key'   => 'resueltas',
                    'label' => __('Resueltas', 'flavor-platform'),
                    'icon'  => '✅',
                    'color' => 'green',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_seguimiento_denuncias WHERE user_id = {user_id} AND estado IN ('favorable', 'desfavorable')",
                ],
            ],

            'card' => [
                'layout'      => 'timeline',
                'title_field' => 'titulo',
                'meta_fields' => ['administracion', 'fecha_presentacion', 'plazo_dias'],
                'badge_field' => 'estado',
                'show_progress' => true,
            ],

            'tabs' => [
                'listado' => [
                    'label'   => __('Mis denuncias', 'flavor-platform'),
                    'icon'    => '📝',
                    'content' => 'template:seguimiento-denuncias/_listado.php',
                ],
                'nueva' => [
                    'label'   => __('Nueva', 'flavor-platform'),
                    'icon'    => '➕',
                    'content' => 'shortcode:denuncias_formulario',
                ],
                'alertas' => [
                    'label'   => __('Alertas', 'flavor-platform'),
                    'icon'    => '🔔',
                    'content' => 'shortcode:denuncias_alertas',
                ],
                'archivadas' => [
                    'label'   => __('Archivadas', 'flavor-platform'),
                    'icon'    => '📁',
                    'content' => 'shortcode:denuncias_archivadas',
                ],
            ],

            'archive' => [
                'columns'       => 1,
                'per_page'      => 10,
                'order_by'      => 'fecha_presentacion',
                'order'         => 'DESC',
                'filterable_by' => ['estado', 'administracion'],
                'timeline_view' => true,
            ],

            'dashboard' => [
                'widgets' => [
                    'proximos_plazos'  => ['type' => 'alert', 'title' => __('Próximos vencimientos', 'flavor-platform')],
                    'denuncias_activas'=> ['type' => 'list', 'title' => __('Denuncias activas', 'flavor-platform')],
                ],
                'actions' => [
                    'nueva_denuncia' => [
                        'label' => __('Registrar denuncia', 'flavor-platform'),
                        'icon'  => '➕',
                        'modal' => 'denuncias-nueva',
                    ],
                ],
            ],

            'features' => [
                'has_archive'      => true,
                'has_single'       => true,
                'has_dashboard'    => true,
                'has_timeline'     => true,
                'has_reminders'    => true,
                'auto_silencio'    => true,
                'has_documents'    => true,
            ],
        ];
    }
}
