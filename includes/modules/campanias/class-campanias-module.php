<?php
/**
 * Modulo de Campanias y Acciones Colectivas
 *
 * Coordina protestas, recogida de firmas, concentraciones y acciones ciudadanas.
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del modulo de Campanias
 */
class Flavor_Chat_Campanias_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'campanias';
        $this->name = 'Campanias y Acciones';
        $this->description = 'Coordina campanias ciudadanas, protestas, recogida de firmas, concentraciones y acciones colectivas para la defensa del territorio.';

        parent::__construct();
    }

    /**
     * Verifica si el modulo puede activarse
     */
    public function can_activate() {
        return Flavor_Chat_Helpers::tabla_existe('flavor_campanias');
    }

    /**
     * Mensaje de error si no puede activarse
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return 'Las tablas del modulo Campanias no estan creadas. Desactiva y reactiva el modulo.';
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
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        if (!Flavor_Chat_Helpers::tabla_existe('flavor_campanias')) {
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

        // Tabla principal de campanias
        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';
        $sql_campanias = "CREATE TABLE $tabla_campanias (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion longtext NOT NULL,
            tipo enum('protesta','recogida_firmas','concentracion','boicot','denuncia_publica','sensibilizacion','accion_legal','otra') NOT NULL DEFAULT 'otra',
            estado enum('planificada','activa','pausada','completada','cancelada') NOT NULL DEFAULT 'planificada',
            objetivo_descripcion text,
            objetivo_firmas int unsigned DEFAULT 0,
            firmas_actuales int unsigned DEFAULT 0,
            fecha_inicio date,
            fecha_fin date,
            ubicacion varchar(255),
            latitud decimal(10,8),
            longitud decimal(11,8),
            imagen varchar(255),
            documentos text,
            enlaces_externos text,
            hashtags varchar(255),
            colectivo_id bigint(20) unsigned,
            comunidad_id bigint(20) unsigned,
            creador_id bigint(20) unsigned NOT NULL,
            visibilidad enum('publica','miembros','privada') NOT NULL DEFAULT 'publica',
            destacada tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY creador_id (creador_id),
            KEY tipo (tipo),
            KEY estado (estado),
            KEY colectivo_id (colectivo_id),
            KEY comunidad_id (comunidad_id),
            KEY fecha_inicio (fecha_inicio)
        ) $charset_collate;";
        dbDelta($sql_campanias);

        // Tabla de participantes en campanias
        $tabla_participantes = $wpdb->prefix . 'flavor_campanias_participantes';
        $sql_participantes = "CREATE TABLE $tabla_participantes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campania_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            rol enum('organizador','coordinador','colaborador','firmante','asistente') NOT NULL DEFAULT 'colaborador',
            estado enum('confirmado','pendiente','cancelado') NOT NULL DEFAULT 'pendiente',
            tareas_asignadas text,
            notas text,
            fecha_union datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY campania_user (campania_id, user_id),
            KEY campania_id (campania_id),
            KEY user_id (user_id),
            KEY rol (rol)
        ) $charset_collate;";
        dbDelta($sql_participantes);

        // Tabla de acciones/eventos de la campania
        $tabla_acciones = $wpdb->prefix . 'flavor_campanias_acciones';
        $sql_acciones = "CREATE TABLE $tabla_acciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campania_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            tipo enum('concentracion','manifestacion','charla','taller','difusion','reunion','entrega_firmas','rueda_prensa','otra') NOT NULL,
            fecha datetime NOT NULL,
            ubicacion varchar(255),
            latitud decimal(10,8),
            longitud decimal(11,8),
            punto_encuentro varchar(255),
            materiales_necesarios text,
            responsable_id bigint(20) unsigned,
            asistentes_esperados int unsigned DEFAULT 0,
            asistentes_confirmados int unsigned DEFAULT 0,
            estado enum('programada','en_curso','completada','cancelada') NOT NULL DEFAULT 'programada',
            resultado text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campania_id (campania_id),
            KEY fecha (fecha),
            KEY estado (estado)
        ) $charset_collate;";
        dbDelta($sql_acciones);

        // Tabla de actualizaciones/noticias de la campania
        $tabla_actualizaciones = $wpdb->prefix . 'flavor_campanias_actualizaciones';
        $sql_actualizaciones = "CREATE TABLE $tabla_actualizaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campania_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            contenido longtext NOT NULL,
            tipo enum('noticia','logro','problema','llamamiento','media') NOT NULL DEFAULT 'noticia',
            imagen varchar(255),
            autor_id bigint(20) unsigned NOT NULL,
            destacada tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campania_id (campania_id),
            KEY autor_id (autor_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_actualizaciones);

        // Tabla de firmas (para recogida de firmas)
        $tabla_firmas = $wpdb->prefix . 'flavor_campanias_firmas';
        $sql_firmas = "CREATE TABLE $tabla_firmas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campania_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned,
            nombre varchar(200),
            email varchar(200),
            dni_hash varchar(64),
            localidad varchar(100),
            comentario text,
            visible tinyint(1) DEFAULT 1,
            verificada tinyint(1) DEFAULT 0,
            ip_hash varchar(64),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campania_id (campania_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_firmas);
    }

    /**
     * Configuracion por defecto
     */
    public function get_default_settings() {
        return [
            'requiere_aprobacion' => true,
            'tipos_permitidos' => ['protesta', 'recogida_firmas', 'concentracion', 'boicot', 'denuncia_publica', 'sensibilizacion', 'accion_legal', 'otra'],
            'permitir_firmas_anonimas' => false,
            'verificar_email_firmas' => true,
            'max_campanias_por_usuario' => 10,
            'notificar_nuevas_campanias' => true,
            'mostrar_mapa_acciones' => true,
        ];
    }

    /**
     * Registra los shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('campanias_listar', [$this, 'shortcode_listar']);
        add_shortcode('campanias_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('campanias_crear', [$this, 'shortcode_crear']);
        add_shortcode('campanias_mis_campanias', [$this, 'shortcode_mis_campanias']);
        add_shortcode('campanias_firmar', [$this, 'shortcode_firmar']);
        add_shortcode('campanias_mapa', [$this, 'shortcode_mapa']);
        add_shortcode('campanias_calendario', [$this, 'shortcode_calendario']);
    }

    /**
     * Shortcode para listar campanias
     */
    public function shortcode_listar($atts) {
        $atts = shortcode_atts([
            'tipo' => '',
            'estado' => 'activa',
            'limite' => 12,
            'columnas' => 3,
            'comunidad' => '',
            'colectivo' => '',
        ], $atts);

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/campanias/views/listado.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para detalle de campania
     */
    public function shortcode_detalle($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $campania_id = $atts['id'] ?: (isset($_GET['campania_id']) ? intval($_GET['campania_id']) : 0);

        if (!$campania_id) {
            return '<p class="flavor-error">Campania no encontrada.</p>';
        }

        $campania = $this->obtener_campania($campania_id);
        if (!$campania) {
            return '<p class="flavor-error">Campania no encontrada.</p>';
        }

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/campanias/views/detalle.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para crear campania
     */
    public function shortcode_crear($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-info">Debes iniciar sesion para crear una campania.</p>';
        }

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/campanias/views/crear.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para mis campanias
     */
    public function shortcode_mis_campanias($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-info">Debes iniciar sesion para ver tus campanias.</p>';
        }

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/campanias/views/mis-campanias.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para firmar campania
     */
    public function shortcode_firmar($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $campania_id = $atts['id'] ?: (isset($_GET['campania_id']) ? intval($_GET['campania_id']) : 0);

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/campanias/views/firmar.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para mapa de acciones
     */
    public function shortcode_mapa($atts) {
        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/campanias/views/mapa.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para calendario de acciones
     */
    public function shortcode_calendario($atts) {
        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/campanias/views/calendario.php';
        return ob_get_clean();
    }

    /**
     * Registra handlers AJAX
     */
    private function register_ajax_handlers() {
        // Con autenticacion
        add_action('wp_ajax_campanias_crear', [$this, 'ajax_crear']);
        add_action('wp_ajax_campanias_actualizar', [$this, 'ajax_actualizar']);
        add_action('wp_ajax_campanias_participar', [$this, 'ajax_participar']);
        add_action('wp_ajax_campanias_abandonar', [$this, 'ajax_abandonar']);
        add_action('wp_ajax_campanias_crear_accion', [$this, 'ajax_crear_accion']);
        add_action('wp_ajax_campanias_publicar_actualizacion', [$this, 'ajax_publicar_actualizacion']);
        add_action('wp_ajax_campanias_confirmar_asistencia', [$this, 'ajax_confirmar_asistencia']);

        // Publicos
        add_action('wp_ajax_campanias_firmar', [$this, 'ajax_firmar']);
        add_action('wp_ajax_nopriv_campanias_firmar', [$this, 'ajax_firmar']);
        add_action('wp_ajax_campanias_listar', [$this, 'ajax_listar']);
        add_action('wp_ajax_nopriv_campanias_listar', [$this, 'ajax_listar']);
        add_action('wp_ajax_campanias_obtener', [$this, 'ajax_obtener']);
        add_action('wp_ajax_nopriv_campanias_obtener', [$this, 'ajax_obtener']);
    }

    /**
     * AJAX: Crear campania
     */
    public function ajax_crear() {
        check_ajax_referer('flavor_campanias_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => 'Debes iniciar sesion.']);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_campanias';

        $datos = [
            'titulo' => sanitize_text_field($_POST['titulo'] ?? ''),
            'descripcion' => wp_kses_post($_POST['descripcion'] ?? ''),
            'tipo' => sanitize_text_field($_POST['tipo'] ?? 'otra'),
            'estado' => 'planificada',
            'objetivo_descripcion' => sanitize_textarea_field($_POST['objetivo_descripcion'] ?? ''),
            'objetivo_firmas' => intval($_POST['objetivo_firmas'] ?? 0),
            'fecha_inicio' => sanitize_text_field($_POST['fecha_inicio'] ?? ''),
            'fecha_fin' => sanitize_text_field($_POST['fecha_fin'] ?? ''),
            'ubicacion' => sanitize_text_field($_POST['ubicacion'] ?? ''),
            'hashtags' => sanitize_text_field($_POST['hashtags'] ?? ''),
            'colectivo_id' => intval($_POST['colectivo_id'] ?? 0) ?: null,
            'comunidad_id' => intval($_POST['comunidad_id'] ?? 0) ?: null,
            'creador_id' => get_current_user_id(),
            'visibilidad' => sanitize_text_field($_POST['visibilidad'] ?? 'publica'),
        ];

        if (empty($datos['titulo'])) {
            wp_send_json_error(['error' => 'El titulo es obligatorio.']);
        }

        $resultado = $wpdb->insert($tabla, $datos);

        if ($resultado === false) {
            wp_send_json_error(['error' => 'Error al crear la campania.']);
        }

        $campania_id = $wpdb->insert_id;

        // Agregar al creador como organizador
        $tabla_participantes = $wpdb->prefix . 'flavor_campanias_participantes';
        $wpdb->insert($tabla_participantes, [
            'campania_id' => $campania_id,
            'user_id' => get_current_user_id(),
            'rol' => 'organizador',
            'estado' => 'confirmado',
        ]);

        wp_send_json_success([
            'campania_id' => $campania_id,
            'mensaje' => 'Campania creada correctamente.',
        ]);
    }

    /**
     * AJAX: Participar en campania
     */
    public function ajax_participar() {
        check_ajax_referer('flavor_campanias_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => 'Debes iniciar sesion.']);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_campanias_participantes';

        $campania_id = intval($_POST['campania_id'] ?? 0);
        $user_id = get_current_user_id();

        // Verificar si ya participa
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE campania_id = %d AND user_id = %d",
            $campania_id, $user_id
        ));

        if ($existe) {
            wp_send_json_error(['error' => 'Ya participas en esta campania.']);
        }

        $resultado = $wpdb->insert($tabla, [
            'campania_id' => $campania_id,
            'user_id' => $user_id,
            'rol' => 'colaborador',
            'estado' => 'confirmado',
        ]);

        if ($resultado === false) {
            wp_send_json_error(['error' => 'Error al unirse a la campania.']);
        }

        wp_send_json_success(['mensaje' => 'Te has unido a la campania.']);
    }

    /**
     * AJAX: Firmar campania
     */
    public function ajax_firmar() {
        check_ajax_referer('flavor_campanias_nonce', 'nonce');

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_campanias_firmas';
        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';

        $campania_id = intval($_POST['campania_id'] ?? 0);
        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $localidad = sanitize_text_field($_POST['localidad'] ?? '');
        $comentario = sanitize_textarea_field($_POST['comentario'] ?? '');

        if (empty($nombre) || empty($email)) {
            wp_send_json_error(['error' => 'Nombre y email son obligatorios.']);
        }

        // Verificar si ya firmo (por email)
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE campania_id = %d AND email = %s",
            $campania_id, $email
        ));

        if ($existe) {
            wp_send_json_error(['error' => 'Ya has firmado esta campania.']);
        }

        $user_id = is_user_logged_in() ? get_current_user_id() : null;

        $resultado = $wpdb->insert($tabla, [
            'campania_id' => $campania_id,
            'user_id' => $user_id,
            'nombre' => $nombre,
            'email' => $email,
            'localidad' => $localidad,
            'comentario' => $comentario,
            'ip_hash' => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? ''),
        ]);

        if ($resultado === false) {
            wp_send_json_error(['error' => 'Error al registrar la firma.']);
        }

        // Actualizar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_campanias SET firmas_actuales = firmas_actuales + 1 WHERE id = %d",
            $campania_id
        ));

        $total_firmas = $wpdb->get_var($wpdb->prepare(
            "SELECT firmas_actuales FROM $tabla_campanias WHERE id = %d",
            $campania_id
        ));

        wp_send_json_success([
            'mensaje' => 'Gracias por firmar.',
            'total_firmas' => $total_firmas,
        ]);
    }

    /**
     * AJAX: Listar campanias
     */
    public function ajax_listar() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_campanias';

        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $estado = sanitize_text_field($_POST['estado'] ?? 'activa');
        $limite = intval($_POST['limite'] ?? 12);
        $offset = intval($_POST['offset'] ?? 0);

        $where = "WHERE visibilidad = 'publica'";
        $params = [];

        if ($tipo) {
            $where .= " AND tipo = %s";
            $params[] = $tipo;
        }
        if ($estado) {
            $where .= " AND estado = %s";
            $params[] = $estado;
        }

        $sql = "SELECT * FROM $tabla $where ORDER BY destacada DESC, created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limite;
        $params[] = $offset;

        $campanias = $wpdb->get_results($wpdb->prepare($sql, $params));

        wp_send_json_success(['campanias' => $campanias]);
    }

    /**
     * AJAX: Obtener campania
     */
    public function ajax_obtener() {
        $campania_id = intval($_POST['campania_id'] ?? $_GET['id'] ?? 0);
        $campania = $this->obtener_campania($campania_id);

        if (!$campania) {
            wp_send_json_error(['error' => 'Campania no encontrada.']);
        }

        wp_send_json_success(['campania' => $campania]);
    }

    /**
     * Obtiene una campania con datos relacionados
     */
    private function obtener_campania($campania_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_campanias';

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $campania_id
        ));

        if (!$campania) {
            return null;
        }

        // Obtener participantes
        $tabla_participantes = $wpdb->prefix . 'flavor_campanias_participantes';
        $campania->participantes = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.display_name, u.user_email
             FROM $tabla_participantes p
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
             WHERE p.campania_id = %d AND p.estado = 'confirmado'",
            $campania_id
        ));

        // Obtener acciones programadas
        $tabla_acciones = $wpdb->prefix . 'flavor_campanias_acciones';
        $campania->acciones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_acciones WHERE campania_id = %d ORDER BY fecha ASC",
            $campania_id
        ));

        // Obtener actualizaciones
        $tabla_actualizaciones = $wpdb->prefix . 'flavor_campanias_actualizaciones';
        $campania->actualizaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name as autor_nombre
             FROM $tabla_actualizaciones a
             LEFT JOIN {$wpdb->users} u ON a.autor_id = u.ID
             WHERE a.campania_id = %d ORDER BY created_at DESC LIMIT 10",
            $campania_id
        ));

        // Datos del creador
        $campania->creador = get_userdata($campania->creador_id);

        return $campania;
    }

    /**
     * Registra rutas REST API
     */
    private function register_rest_routes() {
        add_action('rest_api_init', function() {
            register_rest_route('flavor/v1', '/campanias', [
                'methods' => 'GET',
                'callback' => [$this, 'api_listar'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('flavor/v1', '/campanias/(?P<id>\d+)', [
                'methods' => 'GET',
                'callback' => [$this, 'api_obtener'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('flavor/v1', '/campanias/(?P<id>\d+)/firmar', [
                'methods' => 'POST',
                'callback' => [$this, 'api_firmar'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('flavor/v1', '/campanias/(?P<id>\d+)/participar', [
                'methods' => 'POST',
                'callback' => [$this, 'api_participar'],
                'permission_callback' => [$this, 'api_verificar_autenticacion'],
            ]);
        });
    }

    /**
     * API: Verificar autenticacion
     */
    public function api_verificar_autenticacion() {
        return is_user_logged_in();
    }

    /**
     * API: Listar campanias
     */
    public function api_listar($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_campanias';

        $tipo = $request->get_param('tipo');
        $estado = $request->get_param('estado') ?: 'activa';
        $limite = intval($request->get_param('limite')) ?: 12;

        $where = "WHERE visibilidad = 'publica'";
        $params = [];

        if ($tipo) {
            $where .= " AND tipo = %s";
            $params[] = $tipo;
        }
        if ($estado) {
            $where .= " AND estado = %s";
            $params[] = $estado;
        }

        $sql = "SELECT * FROM $tabla $where ORDER BY destacada DESC, created_at DESC LIMIT %d";
        $params[] = $limite;

        $campanias = $wpdb->get_results($wpdb->prepare($sql, $params));

        return rest_ensure_response(['campanias' => $campanias]);
    }

    /**
     * API: Obtener campania
     */
    public function api_obtener($request) {
        $campania = $this->obtener_campania($request['id']);

        if (!$campania) {
            return new WP_Error('not_found', 'Campania no encontrada', ['status' => 404]);
        }

        return rest_ensure_response(['campania' => $campania]);
    }

    /**
     * API: Firmar campania
     */
    public function api_firmar($request) {
        $_POST = $request->get_params();
        $_POST['campania_id'] = $request['id'];

        // Simular nonce para el handler AJAX
        $_POST['nonce'] = wp_create_nonce('flavor_campanias_nonce');
        $_REQUEST['nonce'] = $_POST['nonce'];

        ob_start();
        $this->ajax_firmar();
        $response = ob_get_clean();

        return rest_ensure_response(json_decode($response, true));
    }

    /**
     * API: Participar en campania
     */
    public function api_participar($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_campanias_participantes';

        $campania_id = $request['id'];
        $user_id = get_current_user_id();

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE campania_id = %d AND user_id = %d",
            $campania_id, $user_id
        ));

        if ($existe) {
            return new WP_Error('already_joined', 'Ya participas en esta campania', ['status' => 400]);
        }

        $wpdb->insert($tabla, [
            'campania_id' => $campania_id,
            'user_id' => $user_id,
            'rol' => 'colaborador',
            'estado' => 'confirmado',
        ]);

        return rest_ensure_response(['success' => true, 'mensaje' => 'Te has unido a la campania.']);
    }

    /**
     * Carga assets frontend
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        wp_enqueue_style(
            'flavor-campanias',
            FLAVOR_CHAT_IA_URL . 'includes/modules/campanias/assets/css/campanias.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-campanias',
            FLAVOR_CHAT_IA_URL . 'includes/modules/campanias/assets/js/campanias.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-campanias', 'flavorCampaniasConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_campanias_nonce'),
            'strings' => [
                'confirmParticipar' => 'Quieres unirte a esta campania?',
                'confirmAbandonar' => 'Seguro que quieres abandonar esta campania?',
                'graciasFirema' => 'Gracias por firmar!',
                'errorConexion' => 'Error de conexion. Intentalo de nuevo.',
            ],
        ]);
    }

    /**
     * Determina si cargar assets
     */
    private function should_load_assets() {
        global $post;
        if (!$post) return false;

        $shortcodes = ['campanias_listar', 'campanias_detalle', 'campanias_crear', 'campanias_mis_campanias', 'campanias_firmar', 'campanias_mapa', 'campanias_calendario'];

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return strpos($post->post_name, 'campania') !== false;
    }

    /**
     * Obtiene las acciones disponibles para IA
     */
    public function get_actions() {
        return [
            'listar_campanias' => [
                'descripcion' => 'Lista las campanias activas',
                'parametros' => [
                    'tipo' => ['tipo' => 'string', 'descripcion' => 'Tipo de campania'],
                    'estado' => ['tipo' => 'string', 'descripcion' => 'Estado de la campania'],
                    'limite' => ['tipo' => 'integer', 'descripcion' => 'Numero maximo de resultados'],
                ],
            ],
            'ver_campania' => [
                'descripcion' => 'Muestra el detalle de una campania',
                'parametros' => [
                    'campania_id' => ['tipo' => 'integer', 'descripcion' => 'ID de la campania', 'requerido' => true],
                ],
            ],
            'crear_campania' => [
                'descripcion' => 'Crea una nueva campania',
                'parametros' => [
                    'titulo' => ['tipo' => 'string', 'descripcion' => 'Titulo de la campania', 'requerido' => true],
                    'descripcion' => ['tipo' => 'string', 'descripcion' => 'Descripcion detallada', 'requerido' => true],
                    'tipo' => ['tipo' => 'string', 'descripcion' => 'Tipo de campania'],
                    'objetivo_firmas' => ['tipo' => 'integer', 'descripcion' => 'Objetivo de firmas'],
                ],
            ],
            'firmar_campania' => [
                'descripcion' => 'Firma una campania de recogida de firmas',
                'parametros' => [
                    'campania_id' => ['tipo' => 'integer', 'descripcion' => 'ID de la campania', 'requerido' => true],
                    'nombre' => ['tipo' => 'string', 'descripcion' => 'Nombre del firmante', 'requerido' => true],
                    'email' => ['tipo' => 'string', 'descripcion' => 'Email del firmante', 'requerido' => true],
                ],
            ],
            'participar_campania' => [
                'descripcion' => 'Unirse como participante de una campania',
                'parametros' => [
                    'campania_id' => ['tipo' => 'integer', 'descripcion' => 'ID de la campania', 'requerido' => true],
                ],
            ],
        ];
    }

    /**
     * Ejecuta una accion
     */
    public function execute_action($accion, $parametros = []) {
        switch ($accion) {
            case 'listar_campanias':
                return $this->action_listar_campanias($parametros);
            case 'ver_campania':
                return $this->action_ver_campania($parametros);
            case 'crear_campania':
                return $this->action_crear_campania($parametros);
            case 'firmar_campania':
                return $this->action_firmar_campania($parametros);
            case 'participar_campania':
                return $this->action_participar_campania($parametros);
            default:
                return ['success' => false, 'error' => 'Accion no reconocida'];
        }
    }

    /**
     * Accion: Listar campanias
     */
    private function action_listar_campanias($params) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_campanias';

        $limite = intval($params['limite'] ?? 10);
        $tipo = sanitize_text_field($params['tipo'] ?? '');
        $estado = sanitize_text_field($params['estado'] ?? 'activa');

        $where = "WHERE visibilidad = 'publica' AND estado = %s";
        $query_params = [$estado];

        if ($tipo) {
            $where .= " AND tipo = %s";
            $query_params[] = $tipo;
        }

        $query_params[] = $limite;

        $campanias = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, tipo, estado, firmas_actuales, objetivo_firmas, fecha_inicio
             FROM $tabla $where ORDER BY created_at DESC LIMIT %d",
            $query_params
        ));

        return [
            'success' => true,
            'campanias' => $campanias,
            'total' => count($campanias),
        ];
    }

    /**
     * Accion: Ver campania
     */
    private function action_ver_campania($params) {
        $campania_id = intval($params['campania_id'] ?? 0);
        $campania = $this->obtener_campania($campania_id);

        if (!$campania) {
            return ['success' => false, 'error' => 'Campania no encontrada'];
        }

        return ['success' => true, 'campania' => $campania];
    }

    /**
     * Accion: Crear campania
     */
    private function action_crear_campania($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesion'];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_campanias';

        $datos = [
            'titulo' => sanitize_text_field($params['titulo'] ?? ''),
            'descripcion' => wp_kses_post($params['descripcion'] ?? ''),
            'tipo' => sanitize_text_field($params['tipo'] ?? 'otra'),
            'estado' => 'planificada',
            'objetivo_firmas' => intval($params['objetivo_firmas'] ?? 0),
            'creador_id' => get_current_user_id(),
            'visibilidad' => 'publica',
        ];

        if (empty($datos['titulo'])) {
            return ['success' => false, 'error' => 'El titulo es obligatorio'];
        }

        $resultado = $wpdb->insert($tabla, $datos);

        if ($resultado === false) {
            return ['success' => false, 'error' => 'Error al crear la campania'];
        }

        return [
            'success' => true,
            'campania_id' => $wpdb->insert_id,
            'mensaje' => 'Campania creada correctamente',
        ];
    }

    /**
     * Accion: Firmar campania
     */
    private function action_firmar_campania($params) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_campanias_firmas';
        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';

        $campania_id = intval($params['campania_id'] ?? 0);
        $nombre = sanitize_text_field($params['nombre'] ?? '');
        $email = sanitize_email($params['email'] ?? '');

        if (empty($nombre) || empty($email)) {
            return ['success' => false, 'error' => 'Nombre y email son obligatorios'];
        }

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE campania_id = %d AND email = %s",
            $campania_id, $email
        ));

        if ($existe) {
            return ['success' => false, 'error' => 'Ya has firmado esta campania'];
        }

        $wpdb->insert($tabla, [
            'campania_id' => $campania_id,
            'nombre' => $nombre,
            'email' => $email,
            'user_id' => is_user_logged_in() ? get_current_user_id() : null,
        ]);

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_campanias SET firmas_actuales = firmas_actuales + 1 WHERE id = %d",
            $campania_id
        ));

        return ['success' => true, 'mensaje' => 'Gracias por firmar'];
    }

    /**
     * Accion: Participar en campania
     */
    private function action_participar_campania($params) {
        if (!is_user_logged_in()) {
            return ['success' => false, 'error' => 'Debes iniciar sesion'];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_campanias_participantes';

        $campania_id = intval($params['campania_id'] ?? 0);
        $user_id = get_current_user_id();

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE campania_id = %d AND user_id = %d",
            $campania_id, $user_id
        ));

        if ($existe) {
            return ['success' => false, 'error' => 'Ya participas en esta campania'];
        }

        $wpdb->insert($tabla, [
            'campania_id' => $campania_id,
            'user_id' => $user_id,
            'rol' => 'colaborador',
            'estado' => 'confirmado',
        ]);

        return ['success' => true, 'mensaje' => 'Te has unido a la campania'];
    }

    /**
     * Renderiza el widget del dashboard
     */
    public function render_dashboard_widget() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_campanias';

        $estadisticas = [
            'activas' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'activa'"),
            'total_firmas' => $wpdb->get_var("SELECT SUM(firmas_actuales) FROM $tabla"),
            'proximas_acciones' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_campanias_acciones WHERE fecha > %s AND estado = 'programada'",
                current_time('mysql')
            )),
        ];

        include FLAVOR_CHAT_IA_PATH . 'includes/modules/campanias/views/dashboard.php';
    }

    /**
     * Obtiene tipos de campania
     */
    public function get_tipos_campania() {
        return [
            'protesta' => 'Protesta / Manifestacion',
            'recogida_firmas' => 'Recogida de Firmas',
            'concentracion' => 'Concentracion',
            'boicot' => 'Boicot',
            'denuncia_publica' => 'Denuncia Publica',
            'sensibilizacion' => 'Sensibilizacion',
            'accion_legal' => 'Accion Legal',
            'otra' => 'Otra',
        ];
    }

    /**
     * Obtiene definiciones de herramientas para IA
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'campanias_listar',
                'description' => 'Lista las campanias ciudadanas activas en el territorio',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo' => ['type' => 'string', 'description' => 'Filtrar por tipo'],
                        'limite' => ['type' => 'integer', 'description' => 'Numero de resultados'],
                    ],
                ],
            ],
            [
                'name' => 'campanias_firmar',
                'description' => 'Firma una campania de recogida de firmas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'campania_id' => ['type' => 'integer', 'description' => 'ID de la campania'],
                        'nombre' => ['type' => 'string', 'description' => 'Nombre del firmante'],
                        'email' => ['type' => 'string', 'description' => 'Email del firmante'],
                    ],
                    'required' => ['campania_id', 'nombre', 'email'],
                ],
            ],
        ];
    }

    /**
     * Base de conocimiento
     */
    public function get_knowledge_base() {
        return "El modulo de Campanias permite coordinar acciones ciudadanas colectivas como protestas, recogidas de firmas, concentraciones y denuncias publicas. Cada campania puede tener multiples acciones programadas, participantes con diferentes roles (organizador, coordinador, colaborador) y un sistema de firmas para apoyos. Las campanias pueden estar vinculadas a colectivos o comunidades especificas.";
    }

    /**
     * FAQs
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Como puedo crear una campania?',
                'respuesta' => 'Ve a la seccion de campanias y usa el formulario de creacion. Necesitas estar registrado.',
            ],
            [
                'pregunta' => 'Como firmo una campania?',
                'respuesta' => 'En la pagina de detalle de la campania encontraras el formulario de firma. Solo necesitas tu nombre y email.',
            ],
            [
                'pregunta' => 'Puedo organizar acciones dentro de una campania?',
                'respuesta' => 'Si, como organizador o coordinador puedes programar concentraciones, charlas, entregas de firmas y otras acciones.',
            ],
        ];
    }
}
