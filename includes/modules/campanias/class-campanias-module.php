<?php
/**
 * Modulo de Campanias y Acciones Colectivas
 *
 * Coordina protestas, recogida de firmas, concentraciones y acciones ciudadanas.
 *
 * @package FlavorPlatform
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del modulo de Campanias
 */
class Flavor_Platform_Campanias_Module extends Flavor_Platform_Module_Base {

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
        global $wpdb;
        return Flavor_Platform_Helpers::tabla_existe($wpdb->prefix . 'flavor_campanias');
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
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();
        $this->cargar_dashboard_tab();

        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Carga el Dashboard Tab para el panel de usuario
     */
    private function cargar_dashboard_tab() {
        $archivo_dashboard_tab = dirname(__FILE__) . '/class-campanias-dashboard-tab.php';
        if (file_exists($archivo_dashboard_tab)) {
            require_once $archivo_dashboard_tab;
            Flavor_Campanias_Dashboard_Tab::get_instance();
        }
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        if (!Flavor_Platform_Helpers::tabla_existe($wpdb->prefix . 'flavor_campanias')) {
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
     * Renderiza una vista del modulo de forma segura.
     *
     * @param string $view_file Nombre del archivo dentro de views/
     * @return string
     */
    private function render_view($view_file) {
        $path = FLAVOR_PLATFORM_PATH . 'includes/modules/campanias/views/' . ltrim((string) $view_file, '/');
        if (!file_exists($path)) {
            $fallbacks = [
                FLAVOR_PLATFORM_PATH . 'includes/modules/campanias/views/dashboard.php',
                FLAVOR_PLATFORM_PATH . 'includes/modules/campanias/views/listado.php',
            ];

            foreach ($fallbacks as $fallback_path) {
                if (file_exists($fallback_path)) {
                    flavor_platform_log('Vista no encontrada en campanias, usando fallback: ' . $path, 'warning');
                    ob_start();
                    include $fallback_path;
                    return (string) ob_get_clean();
                }
            }

            flavor_platform_log('Vista no encontrada en campanias sin fallback: ' . $path, 'warning');
            return '<p class="flavor-info">' . esc_html__('No se pudo cargar la vista solicitada.', 'flavor-platform') . '</p>';
        }

        ob_start();
        include $path;
        return (string) ob_get_clean();
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

        return $this->render_view('listado.php');
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

        return $this->render_view('detalle.php');
    }

    /**
     * Shortcode para crear campania
     */
    public function shortcode_crear($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-info">Debes iniciar sesion para crear una campania.</p>';
        }

        return $this->render_view('crear.php');
    }

    /**
     * Shortcode para mis campanias
     */
    public function shortcode_mis_campanias($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-info">Debes iniciar sesion para ver tus campanias.</p>';
        }

        return $this->render_view('mis-campanias.php');
    }

    /**
     * Shortcode para firmar campania
     */
    public function shortcode_firmar($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $campania_id = $atts['id'] ?: (isset($_GET['campania_id']) ? intval($_GET['campania_id']) : 0);

        return $this->render_view('firmar.php');
    }

    /**
     * Shortcode para mapa de acciones
     */
    public function shortcode_mapa($atts) {
        return $this->render_view('mapa.php');
    }

    /**
     * Shortcode para calendario de acciones
     */
    public function shortcode_calendario($atts) {
        return $this->render_view('calendario.php');
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
                'permission_callback' => [$this, 'public_read_permission'],
            ]);

            register_rest_route('flavor/v1', '/campanias/(?P<id>\d+)', [
                'methods' => 'GET',
                'callback' => [$this, 'api_obtener'],
                'permission_callback' => [$this, 'can_read_campaign'],
            ]);

            register_rest_route('flavor/v1', '/campanias/(?P<id>\d+)/firmar', [
                'methods' => 'POST',
                'callback' => [$this, 'api_firmar'],
                'permission_callback' => [$this, 'can_sign_campaign'],
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
     * Permiso de lectura pública para campañas listables.
     *
     * @return bool
     */
    public function public_read_permission() {
        return true;
    }

    /**
     * Verifica que una campaña concreta sea visible.
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function can_read_campaign($request) {
        $campania = $this->obtener_campania($request['id']);

        if (!$campania) {
            return new WP_Error('not_found', 'Campania no encontrada', ['status' => 404]);
        }

        if (($campania->visibilidad ?? '') === 'publica') {
            return true;
        }

        if (is_user_logged_in() && (int) get_current_user_id() === (int) ($campania->creador_id ?? 0)) {
            return true;
        }

        return new WP_Error('rest_forbidden', 'No tienes permiso para ver esta campania', ['status' => 403]);
    }

    /**
     * Verifica que una campaña pública pueda recibir firmas.
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function can_sign_campaign($request) {
        $permission = $this->can_read_campaign($request);
        if (is_wp_error($permission)) {
            return $permission;
        }

        $campania = $this->obtener_campania($request['id']);
        if (!$campania || ($campania->estado ?? '') !== 'activa') {
            return new WP_Error('campaign_inactive', 'La campania no esta activa', ['status' => 403]);
        }

        $rate_limit = Flavor_API_Rate_Limiter::check_rate_limit('post');
        if (is_wp_error($rate_limit)) {
            return $rate_limit;
        }

        return true;
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
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_campanias_firmas';
        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';

        $campania_id = absint($request['id']);
        $nombre = sanitize_text_field($request->get_param('nombre'));
        $email = sanitize_email($request->get_param('email'));
        $localidad = sanitize_text_field($request->get_param('localidad'));
        $comentario = sanitize_textarea_field($request->get_param('comentario'));

        if (empty($campania_id) || empty($nombre) || empty($email)) {
            return new WP_Error('invalid_signature_data', 'Nombre y email son obligatorios.', ['status' => 400]);
        }

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE campania_id = %d AND email = %s",
            $campania_id,
            $email
        ));

        if ($existe) {
            return new WP_Error('already_signed', 'Ya has firmado esta campania.', ['status' => 409]);
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
            return new WP_Error('signature_insert_failed', 'Error al registrar la firma.', ['status' => 500]);
        }

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_campanias SET firmas_actuales = firmas_actuales + 1 WHERE id = %d",
            $campania_id
        ));

        $total_firmas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT firmas_actuales FROM $tabla_campanias WHERE id = %d",
            $campania_id
        ));

        return rest_ensure_response([
            'success' => true,
            'mensaje' => 'Gracias por firmar.',
            'total_firmas' => $total_firmas,
        ]);
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
            FLAVOR_PLATFORM_URL . 'includes/modules/campanias/assets/css/campanias.css',
            [],
            FLAVOR_PLATFORM_VERSION
        );

        wp_enqueue_script(
            'flavor-campanias',
            FLAVOR_PLATFORM_URL . 'includes/modules/campanias/assets/js/campanias.js',
            ['jquery'],
            FLAVOR_PLATFORM_VERSION,
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
        if ($page === '' || strpos($page, 'campanias-') !== 0) {
            return;
        }

        wp_enqueue_style(
            'flavor-campanias-admin',
            FLAVOR_PLATFORM_URL . 'includes/modules/campanias/assets/css/campanias.css',
            [],
            FLAVOR_PLATFORM_VERSION
        );
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
        $this->render_admin_widget();
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

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'campanias',
            'title'    => __('Campañas Ciudadanas', 'flavor-platform'),
            'subtitle' => __('Coordina acciones colectivas y movilización social', 'flavor-platform'),
            'icon'     => '📣',
            'color'    => 'accent', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_campanias',
                'primary_key' => 'id',
            ],

            'fields' => [
                'titulo'      => ['type' => 'text', 'label' => __('Título', 'flavor-platform'), 'required' => true],
                'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-platform')],
                'objetivo'    => ['type' => 'textarea', 'label' => __('Objetivo', 'flavor-platform')],
                'fecha_inicio'=> ['type' => 'date', 'label' => __('Fecha inicio', 'flavor-platform')],
                'fecha_fin'   => ['type' => 'date', 'label' => __('Fecha fin', 'flavor-platform')],
                'meta_firmas' => ['type' => 'number', 'label' => __('Meta de firmas', 'flavor-platform')],
                'estado'      => ['type' => 'select', 'label' => __('Estado', 'flavor-platform')],
            ],

            'estados' => [
                'borrador'    => ['label' => __('Borrador', 'flavor-platform'), 'color' => 'gray', 'icon' => '📝'],
                'activa'      => ['label' => __('Activa', 'flavor-platform'), 'color' => 'green', 'icon' => '🟢'],
                'pausada'     => ['label' => __('Pausada', 'flavor-platform'), 'color' => 'yellow', 'icon' => '⏸️'],
                'finalizada'  => ['label' => __('Finalizada', 'flavor-platform'), 'color' => 'blue', 'icon' => '✅'],
                'cancelada'   => ['label' => __('Cancelada', 'flavor-platform'), 'color' => 'red', 'icon' => '❌'],
            ],

            'stats' => [
                [
                    'key'   => 'total_campanias',
                    'label' => __('Campañas', 'flavor-platform'),
                    'icon'  => '📣',
                    'color' => 'rose',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_campanias",
                ],
                [
                    'key'   => 'campanias_activas',
                    'label' => __('Activas', 'flavor-platform'),
                    'icon'  => '🟢',
                    'color' => 'green',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_campanias WHERE estado = 'activa'",
                ],
                [
                    'key'   => 'total_firmas',
                    'label' => __('Firmas', 'flavor-platform'),
                    'icon'  => '✍️',
                    'color' => 'blue',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_campanias_firmas",
                ],
                [
                    'key'   => 'total_acciones',
                    'label' => __('Acciones', 'flavor-platform'),
                    'icon'  => '📅',
                    'color' => 'purple',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_campanias_acciones",
                ],
            ],

            'card' => [
                'layout'      => 'vertical',
                'image_field' => 'imagen',
                'title_field' => 'titulo',
                'meta_fields' => ['fecha_inicio', 'meta_firmas'],
                'badge_field' => 'estado',
                'show_author' => true,
            ],

            'tabs' => [
                'listado' => [
                    'label'   => __('Campañas', 'flavor-platform'),
                    'icon'    => '📣',
                    'content' => 'template:campanias/_listado.php',
                ],
                'mis-campanias' => [
                    'label'   => __('Mis campañas', 'flavor-platform'),
                    'icon'    => '👤',
                    'content' => 'shortcode:campanias_mis_campanias',
                ],
                'firmar' => [
                    'label'   => __('Firmar', 'flavor-platform'),
                    'icon'    => '✍️',
                    'content' => 'shortcode:campanias_firmar',
                ],
                'acciones' => [
                    'label'   => __('Acciones', 'flavor-platform'),
                    'icon'    => '📅',
                    'content' => 'shortcode:campanias_acciones',
                ],
            ],

            'archive' => [
                'columns'       => 3,
                'per_page'      => 12,
                'order_by'      => 'fecha_inicio',
                'order'         => 'DESC',
                'filterable_by' => ['estado', 'categoria'],
            ],

            'dashboard' => [
                'widgets' => [
                    'mis_campanias'  => ['type' => 'list', 'title' => __('Mis campañas', 'flavor-platform')],
                    'firmas_recientes' => ['type' => 'activity', 'title' => __('Últimas firmas', 'flavor-platform')],
                ],
                'actions' => [
                    'nueva_campania' => [
                        'label' => __('Nueva campaña', 'flavor-platform'),
                        'icon'  => '➕',
                        'modal' => 'campanias-nueva',
                    ],
                ],
            ],

            'features' => [
                'has_archive'    => true,
                'has_single'     => true,
                'has_dashboard'  => true,
                'has_search'     => true,
                'has_comments'   => true,
                'has_categories' => true,
                'has_firmas'     => true,
                'has_acciones'   => true,
            ],
        ];
    }

    /**
     * Configuracion del panel de admin para el Panel Unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'campanias',
            'label' => __('Campanias', 'flavor-platform'),
            'icon' => 'dashicons-megaphone',
            'capability' => 'manage_options',
            'categoria' => 'comunidad',
            'paginas' => [
                [
                    'slug' => 'campanias-dashboard',
                    'titulo' => __('Dashboard', 'flavor-platform'),
                    'callback' => [$this, 'render_pagina_dashboard'],
                ],
                [
                    'slug' => 'campanias-listado',
                    'titulo' => __('Listado', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_listado'],
                    'badge' => [$this, 'contar_campanias_pendientes'],
                ],
                [
                    'slug' => 'campanias-firmas',
                    'titulo' => __('Firmas', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_firmas'],
                ],
                [
                    'slug' => 'campanias-estadisticas',
                    'titulo' => __('Estadísticas', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_estadisticas'],
                ],
                [
                    'slug' => 'campanias-config',
                    'titulo' => __('Configuracion', 'flavor-platform'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_admin_estadisticas'],
            'dashboard_widget' => [$this, 'render_admin_widget'],
        ];
    }

    /**
     * Cuenta campanias pendientes de aprobacion para el badge
     *
     * @return int
     */
    public function contar_campanias_pendientes() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_campanias';
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'planificada'");
    }

    /**
     * Estadisticas para el panel unificado
     *
     * @return array
     */
    public function get_admin_estadisticas() {
        global $wpdb;
        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';
        $tabla_firmas = $wpdb->prefix . 'flavor_campanias_firmas';

        $total_campanias = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_campanias");
        $campanias_activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_campanias WHERE estado = 'activa'");
        $total_firmas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_firmas");
        $campanias_exitosas = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_campanias WHERE estado = 'completada' AND firmas_actuales >= objetivo_firmas AND objetivo_firmas > 0"
        );

        return [
            [
                'icon' => 'dashicons-megaphone',
                'valor' => $total_campanias,
                'label' => __('Total campanias', 'flavor-platform'),
                'color' => 'blue',
                'enlace' => admin_url('admin.php?page=campanias-listado'),
            ],
            [
                'icon' => 'dashicons-yes-alt',
                'valor' => $campanias_activas,
                'label' => __('Campanias activas', 'flavor-platform'),
                'color' => 'green',
                'enlace' => admin_url('admin.php?page=campanias-listado&estado=activa'),
            ],
            [
                'icon' => 'dashicons-edit',
                'valor' => $total_firmas,
                'label' => __('Total firmas', 'flavor-platform'),
                'color' => 'purple',
                'enlace' => admin_url('admin.php?page=campanias-firmas'),
            ],
            [
                'icon' => 'dashicons-awards',
                'valor' => $campanias_exitosas,
                'label' => __('Campanias exitosas', 'flavor-platform'),
                'color' => 'gold',
            ],
        ];
    }

    /**
     * Widget para el dashboard del panel unificado
     */
    public function render_admin_widget() {
        global $wpdb;
        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';

        $campanias_recientes = $wpdb->get_results(
            "SELECT id, titulo, estado, firmas_actuales, objetivo_firmas
             FROM $tabla_campanias
             ORDER BY created_at DESC
             LIMIT 5"
        );
        ?>
        <div class="flavor-admin-widget-campanias">
            <?php if (empty($campanias_recientes)): ?>
                <p class="flavor-empty-state"><?php _e('No hay campanias registradas.', 'flavor-platform'); ?></p>
            <?php else: ?>
                <ul class="flavor-widget-list">
                    <?php foreach ($campanias_recientes as $campania): ?>
                        <li>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=campanias-listado&accion=editar&id=' . $campania->id)); ?>">
                                <?php echo esc_html($campania->titulo); ?>
                            </a>
                            <span class="flavor-badge flavor-badge-<?php echo esc_attr($campania->estado); ?>">
                                <?php echo esc_html(ucfirst($campania->estado)); ?>
                            </span>
                            <?php if ($campania->objetivo_firmas > 0): ?>
                                <small><?php echo intval($campania->firmas_actuales); ?>/<?php echo intval($campania->objetivo_firmas); ?> firmas</small>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el dashboard de administracion de campanias
     */
    public function render_admin_dashboard() {
        $is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');
        global $wpdb;
        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';
        $tabla_firmas = $wpdb->prefix . 'flavor_campanias_firmas';
        $tabla_acciones = $wpdb->prefix . 'flavor_campanias_acciones';

        // Estadisticas generales
        $total_campanias = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_campanias");
        $campanias_activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_campanias WHERE estado = 'activa'");
        $campanias_planificadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_campanias WHERE estado = 'planificada'");
        $campanias_completadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_campanias WHERE estado = 'completada'");
        $total_firmas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_firmas");
        $firmas_hoy = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_firmas WHERE DATE(created_at) = %s",
            current_time('Y-m-d')
        ));
        $firmas_semana = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_firmas WHERE created_at >= %s",
            date('Y-m-d', strtotime('-7 days'))
        ));
        $campanias_exitosas = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_campanias WHERE estado = 'completada' AND firmas_actuales >= objetivo_firmas AND objetivo_firmas > 0"
        );

        // Campanias con mas firmas
        $top_campanias = $wpdb->get_results(
            "SELECT id, titulo, firmas_actuales, objetivo_firmas, estado
             FROM $tabla_campanias
             WHERE objetivo_firmas > 0
             ORDER BY firmas_actuales DESC
             LIMIT 5"
        );

        // Firmas recientes
        $firmas_recientes = $wpdb->get_results(
            "SELECT f.*, c.titulo as campania_titulo
             FROM $tabla_firmas f
             LEFT JOIN $tabla_campanias c ON f.campania_id = c.id
             ORDER BY f.created_at DESC
             LIMIT 10"
        );

        // Tendencia de firmas (ultimos 7 dias)
        $tendencia_firmas = $wpdb->get_results(
            "SELECT DATE(created_at) as fecha, COUNT(*) as total
             FROM $tabla_firmas
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at)
             ORDER BY fecha ASC"
        );

        // Proximas acciones
        $proximas_acciones = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, c.titulo as campania_titulo
             FROM $tabla_acciones a
             LEFT JOIN $tabla_campanias c ON a.campania_id = c.id
             WHERE a.fecha >= %s AND a.estado = 'programada'
             ORDER BY a.fecha ASC
             LIMIT 5",
            current_time('mysql')
        ));

        $acciones = $is_dashboard_viewer
            ? [
                [
                    'label' => __('Ver en portal', 'flavor-platform'),
                    'url' => home_url('/mi-portal/participacion/'),
                    'class' => '',
                ],
            ]
            : [
                [
                    'label' => __('Nueva Campania', 'flavor-platform'),
                    'url' => admin_url('admin.php?page=campanias-listado&accion=nueva'),
                    'class' => 'button-primary',
                ],
            ];
        $this->render_page_header(__('Dashboard de Campanias', 'flavor-platform'), $acciones);
        ?>
        <div class="wrap flavor-admin-dashboard">
            <?php if ($is_dashboard_viewer) : ?>
                <div class="notice notice-info"><p><?php esc_html_e('Vista resumida para gestor de grupos. La edición de campañas, firmas y acciones sigue reservada a administración.', 'flavor-platform'); ?></p></div>
            <?php endif; ?>
            <?php if (method_exists($this, 'render_admin_module_hub')) : ?>
                <?php $this->render_admin_module_hub([
                    'description' => __('Acceso visible al dashboard, listado, firmas, configuración y al bloque principal de métricas.', 'flavor-platform'),
                    'stats_anchor' => '#campanias-stats',
                    'extra_items' => [
                        [
                            'label' => __('Portal', 'flavor-platform'),
                            'url' => home_url('/mi-portal/participacion/'),
                            'icon' => 'dashicons-external',
                        ],
                    ],
                ]); ?>
            <?php endif; ?>
            <!-- Tarjetas de estadisticas -->
            <div id="campanias-stats" class="flavor-stats-grid campanias-admin-dashboard__stats">
                <div class="flavor-stat-card campanias-admin-dashboard__stat-card campanias-admin-dashboard__stat-card--blue">
                    <div class="stat-icon campanias-admin-dashboard__stat-icon">
                        <span class="dashicons dashicons-megaphone"></span>
                    </div>
                    <div class="stat-value campanias-admin-dashboard__stat-value"><?php echo $total_campanias; ?></div>
                    <div class="stat-label campanias-admin-dashboard__stat-label"><?php _e('Total Campanias', 'flavor-platform'); ?></div>
                </div>

                <div class="flavor-stat-card campanias-admin-dashboard__stat-card campanias-admin-dashboard__stat-card--green">
                    <div class="stat-icon campanias-admin-dashboard__stat-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="stat-value campanias-admin-dashboard__stat-value"><?php echo $campanias_activas; ?></div>
                    <div class="stat-label campanias-admin-dashboard__stat-label"><?php _e('Campanias Activas', 'flavor-platform'); ?></div>
                </div>

                <div class="flavor-stat-card campanias-admin-dashboard__stat-card campanias-admin-dashboard__stat-card--purple">
                    <div class="stat-icon campanias-admin-dashboard__stat-icon">
                        <span class="dashicons dashicons-edit"></span>
                    </div>
                    <div class="stat-value campanias-admin-dashboard__stat-value"><?php echo $total_firmas; ?></div>
                    <div class="stat-label campanias-admin-dashboard__stat-label"><?php _e('Total Firmas', 'flavor-platform'); ?></div>
                    <div class="stat-extra campanias-admin-dashboard__stat-extra">
                        +<?php echo $firmas_hoy; ?> <?php _e('hoy', 'flavor-platform'); ?> /
                        +<?php echo $firmas_semana; ?> <?php _e('esta semana', 'flavor-platform'); ?>
                    </div>
                </div>

                <div class="flavor-stat-card campanias-admin-dashboard__stat-card campanias-admin-dashboard__stat-card--gold">
                    <div class="stat-icon campanias-admin-dashboard__stat-icon">
                        <span class="dashicons dashicons-awards"></span>
                    </div>
                    <div class="stat-value campanias-admin-dashboard__stat-value"><?php echo $campanias_exitosas; ?></div>
                    <div class="stat-label campanias-admin-dashboard__stat-label"><?php _e('Campanias Exitosas', 'flavor-platform'); ?></div>
                </div>
            </div>

            <?php if ($is_dashboard_viewer) : ?>
                <p><?php esc_html_e('El resto de accesos administrativos del dashboard se ocultan en modo gestor para evitar flujos de edición o moderación fuera de alcance.', 'flavor-platform'); ?></p>
                </div>
                <?php
                return;
            endif; ?>

            <div class="flavor-dashboard-columns campanias-admin-dashboard__columns">
                <!-- Columna principal -->
                <div class="flavor-main-column">
                    <!-- Top campanias por firmas -->
                    <div class="flavor-card campanias-admin-dashboard__card campanias-admin-dashboard__card--mb">
                        <h3 class="campanias-admin-dashboard__card-title">
                            <span class="dashicons dashicons-chart-bar"></span>
                            <?php _e('Campanias con mas firmas', 'flavor-platform'); ?>
                        </h3>
                        <?php if (empty($top_campanias)): ?>
                            <p class="flavor-empty"><?php _e('No hay campanias con objetivo de firmas.', 'flavor-platform'); ?></p>
                        <?php else: ?>
                            <table class="widefat striped campanias-admin-dashboard__table-clean">
                                <thead>
                                    <tr>
                                        <th><?php _e('Campania', 'flavor-platform'); ?></th>
                                        <th><?php _e('Progreso', 'flavor-platform'); ?></th>
                                        <th><?php _e('Estado', 'flavor-platform'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_campanias as $campania): ?>
                                        <?php
                                        $porcentaje = $campania->objetivo_firmas > 0
                                            ? min(100, round(($campania->firmas_actuales / $campania->objetivo_firmas) * 100))
                                            : 0;
                                        $color_barra = $porcentaje >= 100 ? '#00a32a' : ($porcentaje >= 50 ? '#dba617' : '#2271b1');
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=campanias-listado&accion=editar&id=' . $campania->id)); ?>">
                                                    <?php echo esc_html($campania->titulo); ?>
                                                </a>
                                            </td>
                                            <td class="campanias-admin-dashboard__progress-col">
                                                <div class="campanias-admin-dashboard__progress">
                                                    <div style="background: <?php echo $color_barra; ?>; width: <?php echo $porcentaje; ?>%; height: 100%; border-radius: 4px;"></div>
                                                    <span class="campanias-admin-dashboard__progress-label">
                                                        <?php echo intval($campania->firmas_actuales); ?>/<?php echo intval($campania->objetivo_firmas); ?> (<?php echo $porcentaje; ?>%)
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="flavor-badge flavor-estado-<?php echo esc_attr($campania->estado); ?> campanias-admin-dashboard__badge" style="background: <?php
                                                    echo $campania->estado === 'activa' ? '#00a32a' : ($campania->estado === 'completada' ? '#2271b1' : '#646970');
                                                ?>; color: #fff;">
                                                    <?php echo esc_html(ucfirst($campania->estado)); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <!-- Tendencia de firmas -->
                    <div class="flavor-card campanias-admin-dashboard__card campanias-admin-dashboard__card--mb">
                        <h3 class="campanias-admin-dashboard__card-title">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php _e('Tendencia de firmas (ultimos 7 dias)', 'flavor-platform'); ?>
                        </h3>
                        <?php if (empty($tendencia_firmas)): ?>
                            <p class="flavor-empty"><?php _e('No hay datos de firmas en los ultimos 7 dias.', 'flavor-platform'); ?></p>
                        <?php else: ?>
                            <div class="flavor-chart-simple campanias-admin-dashboard__chart">
                                <?php
                                $max_firmas = max(array_column($tendencia_firmas, 'total'));
                                foreach ($tendencia_firmas as $dia):
                                    $altura = $max_firmas > 0 ? ($dia->total / $max_firmas) * 100 : 0;
                                ?>
                                    <div class="campanias-admin-dashboard__chart-col">
                                        <span class="campanias-admin-dashboard__chart-value"><?php echo intval($dia->total); ?></span>
                                        <div style="background: #2271b1; width: 100%; height: <?php echo max(5, $altura); ?>%; border-radius: 4px 4px 0 0;"></div>
                                        <span class="campanias-admin-dashboard__chart-day">
                                            <?php echo date_i18n('D', strtotime($dia->fecha)); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Proximas acciones -->
                    <div class="flavor-card campanias-admin-dashboard__card">
                        <h3 class="campanias-admin-dashboard__card-title">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php _e('Proximas acciones programadas', 'flavor-platform'); ?>
                        </h3>
                        <?php if (empty($proximas_acciones)): ?>
                            <p class="flavor-empty"><?php _e('No hay acciones programadas.', 'flavor-platform'); ?></p>
                        <?php else: ?>
                            <ul class="flavor-timeline campanias-admin-dashboard__timeline">
                                <?php foreach ($proximas_acciones as $accion): ?>
                                    <li class="campanias-admin-dashboard__timeline-item">
                                        <div class="timeline-date campanias-admin-dashboard__timeline-date">
                                            <div class="campanias-admin-dashboard__timeline-day">
                                                <?php echo date_i18n('d', strtotime($accion->fecha)); ?>
                                            </div>
                                            <div class="campanias-admin-dashboard__timeline-month">
                                                <?php echo date_i18n('M Y', strtotime($accion->fecha)); ?>
                                            </div>
                                        </div>
                                        <div class="timeline-content">
                                            <strong><?php echo esc_html($accion->titulo); ?></strong>
                                            <div class="campanias-admin-dashboard__timeline-meta">
                                                <?php echo esc_html($accion->campania_titulo); ?>
                                                <?php if ($accion->ubicacion): ?>
                                                    &middot; <?php echo esc_html($accion->ubicacion); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Columna lateral -->
                <div class="flavor-sidebar-column">
                    <!-- Resumen de estados -->
                    <div class="flavor-card campanias-admin-dashboard__card campanias-admin-dashboard__card--mb">
                        <h3 class="campanias-admin-dashboard__card-title">
                            <span class="dashicons dashicons-chart-pie"></span>
                            <?php _e('Por estado', 'flavor-platform'); ?>
                        </h3>
                        <ul class="campanias-admin-dashboard__status-list">
                            <li class="campanias-admin-dashboard__status-item">
                                <span><span class="dashicons dashicons-clock campanias-admin-dashboard__icon-muted"></span> <?php _e('Planificadas', 'flavor-platform'); ?></span>
                                <strong><?php echo $campanias_planificadas; ?></strong>
                            </li>
                            <li class="campanias-admin-dashboard__status-item">
                                <span><span class="dashicons dashicons-yes campanias-admin-dashboard__icon-green"></span> <?php _e('Activas', 'flavor-platform'); ?></span>
                                <strong><?php echo $campanias_activas; ?></strong>
                            </li>
                            <li class="campanias-admin-dashboard__status-item campanias-admin-dashboard__status-item--last">
                                <span><span class="dashicons dashicons-flag campanias-admin-dashboard__icon-blue"></span> <?php _e('Completadas', 'flavor-platform'); ?></span>
                                <strong><?php echo $campanias_completadas; ?></strong>
                            </li>
                        </ul>
                    </div>

                    <!-- Firmas recientes -->
                    <div class="flavor-card campanias-admin-dashboard__card">
                        <h3 class="campanias-admin-dashboard__card-title">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Firmas recientes', 'flavor-platform'); ?>
                        </h3>
                        <?php if (empty($firmas_recientes)): ?>
                            <p class="flavor-empty"><?php _e('No hay firmas registradas.', 'flavor-platform'); ?></p>
                        <?php else: ?>
                            <ul class="campanias-admin-dashboard__signatures-list">
                                <?php foreach ($firmas_recientes as $firma): ?>
                                    <li class="campanias-admin-dashboard__signatures-item">
                                        <strong><?php echo esc_html($firma->nombre); ?></strong>
                                        <div class="campanias-admin-dashboard__signatures-campaign">
                                            <?php echo esc_html($firma->campania_titulo); ?>
                                        </div>
                                        <div class="campanias-admin-dashboard__signatures-time">
                                            <?php echo human_time_diff(strtotime($firma->created_at), current_time('timestamp')); ?> <?php _e('ago', 'flavor-platform'); ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <p class="campanias-admin-dashboard__signatures-link">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=campanias-firmas')); ?>" class="button">
                                <?php _e('Ver todas las firmas', 'flavor-platform'); ?>
                            </a>
                        </p>
                    </div>
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
     * Renderiza el listado de campanias en admin
     */
    public function render_admin_listado() {
        global $wpdb;
        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';

        // Procesar acciones
        $accion = isset($_GET['accion']) ? sanitize_text_field($_GET['accion']) : '';
        $campania_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        // Guardar campania
        if (isset($_POST['guardar_campania']) && check_admin_referer('campanias_guardar', 'campanias_nonce')) {
            $datos = [
                'titulo' => sanitize_text_field($_POST['titulo']),
                'descripcion' => wp_kses_post($_POST['descripcion']),
                'tipo' => sanitize_text_field($_POST['tipo']),
                'estado' => sanitize_text_field($_POST['estado']),
                'objetivo_descripcion' => sanitize_textarea_field($_POST['objetivo_descripcion']),
                'objetivo_firmas' => intval($_POST['objetivo_firmas']),
                'fecha_inicio' => sanitize_text_field($_POST['fecha_inicio']),
                'fecha_fin' => sanitize_text_field($_POST['fecha_fin']),
                'ubicacion' => sanitize_text_field($_POST['ubicacion']),
                'hashtags' => sanitize_text_field($_POST['hashtags']),
                'visibilidad' => sanitize_text_field($_POST['visibilidad']),
                'destacada' => isset($_POST['destacada']) ? 1 : 0,
            ];

            if ($campania_id > 0) {
                $wpdb->update($tabla_campanias, $datos, ['id' => $campania_id]);
                $mensaje = __('Campania actualizada correctamente.', 'flavor-platform');
            } else {
                $datos['creador_id'] = get_current_user_id();
                $wpdb->insert($tabla_campanias, $datos);
                $campania_id = $wpdb->insert_id;
                $mensaje = __('Campania creada correctamente.', 'flavor-platform');
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($mensaje) . '</p></div>';
            $accion = '';
        }

        // Eliminar campania
        if ($accion === 'eliminar' && $campania_id > 0 && check_admin_referer('eliminar_campania_' . $campania_id)) {
            $wpdb->delete($tabla_campanias, ['id' => $campania_id]);
            $wpdb->delete($wpdb->prefix . 'flavor_campanias_firmas', ['campania_id' => $campania_id]);
            $wpdb->delete($wpdb->prefix . 'flavor_campanias_participantes', ['campania_id' => $campania_id]);
            $wpdb->delete($wpdb->prefix . 'flavor_campanias_acciones', ['campania_id' => $campania_id]);
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Campania eliminada correctamente.', 'flavor-platform') . '</p></div>';
            $accion = '';
            $campania_id = 0;
        }

        // Mostrar formulario de edicion/creacion
        if ($accion === 'nueva' || $accion === 'editar') {
            $campania = null;
            if ($accion === 'editar' && $campania_id > 0) {
                $campania = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_campanias WHERE id = %d", $campania_id));
            }

            $this->render_page_header(
                $campania ? __('Editar Campania', 'flavor-platform') : __('Nueva Campania', 'flavor-platform'),
                [
                    [
                        'label' => __('Volver al listado', 'flavor-platform'),
                        'url' => admin_url('admin.php?page=campanias-listado'),
                        'class' => '',
                    ],
                ]
            );
            ?>
            <form method="post" class="flavor-admin-form" style="background: #fff; padding: 20px; max-width: 800px;">
                <?php wp_nonce_field('campanias_guardar', 'campanias_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="titulo"><?php _e('Titulo', 'flavor-platform'); ?> *</label></th>
                        <td><input type="text" name="titulo" id="titulo" class="regular-text" required value="<?php echo esc_attr($campania->titulo ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="descripcion"><?php _e('Descripcion', 'flavor-platform'); ?></label></th>
                        <td>
                            <?php
                            wp_editor(
                                $campania->descripcion ?? '',
                                'descripcion',
                                ['textarea_rows' => 8, 'media_buttons' => true]
                            );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="tipo"><?php _e('Tipo', 'flavor-platform'); ?></label></th>
                        <td>
                            <select name="tipo" id="tipo">
                                <?php foreach ($this->get_tipos_campania() as $valor => $etiqueta): ?>
                                    <option value="<?php echo esc_attr($valor); ?>" <?php selected($campania->tipo ?? '', $valor); ?>>
                                        <?php echo esc_html($etiqueta); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="estado"><?php _e('Estado', 'flavor-platform'); ?></label></th>
                        <td>
                            <select name="estado" id="estado">
                                <option value="planificada" <?php selected($campania->estado ?? '', 'planificada'); ?>><?php _e('Planificada', 'flavor-platform'); ?></option>
                                <option value="activa" <?php selected($campania->estado ?? '', 'activa'); ?>><?php _e('Activa', 'flavor-platform'); ?></option>
                                <option value="pausada" <?php selected($campania->estado ?? '', 'pausada'); ?>><?php _e('Pausada', 'flavor-platform'); ?></option>
                                <option value="completada" <?php selected($campania->estado ?? '', 'completada'); ?>><?php _e('Completada', 'flavor-platform'); ?></option>
                                <option value="cancelada" <?php selected($campania->estado ?? '', 'cancelada'); ?>><?php _e('Cancelada', 'flavor-platform'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="objetivo_descripcion"><?php _e('Objetivo', 'flavor-platform'); ?></label></th>
                        <td><textarea name="objetivo_descripcion" id="objetivo_descripcion" rows="3" class="large-text"><?php echo esc_textarea($campania->objetivo_descripcion ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="objetivo_firmas"><?php _e('Meta de firmas', 'flavor-platform'); ?></label></th>
                        <td><input type="number" name="objetivo_firmas" id="objetivo_firmas" min="0" value="<?php echo intval($campania->objetivo_firmas ?? 0); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="fecha_inicio"><?php _e('Fecha inicio', 'flavor-platform'); ?></label></th>
                        <td><input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo esc_attr($campania->fecha_inicio ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="fecha_fin"><?php _e('Fecha fin', 'flavor-platform'); ?></label></th>
                        <td><input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo esc_attr($campania->fecha_fin ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="ubicacion"><?php _e('Ubicacion', 'flavor-platform'); ?></label></th>
                        <td><input type="text" name="ubicacion" id="ubicacion" class="regular-text" value="<?php echo esc_attr($campania->ubicacion ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="hashtags"><?php _e('Hashtags', 'flavor-platform'); ?></label></th>
                        <td><input type="text" name="hashtags" id="hashtags" class="regular-text" placeholder="#ejemplo #campania" value="<?php echo esc_attr($campania->hashtags ?? ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="visibilidad"><?php _e('Visibilidad', 'flavor-platform'); ?></label></th>
                        <td>
                            <select name="visibilidad" id="visibilidad">
                                <option value="publica" <?php selected($campania->visibilidad ?? '', 'publica'); ?>><?php _e('Publica', 'flavor-platform'); ?></option>
                                <option value="miembros" <?php selected($campania->visibilidad ?? '', 'miembros'); ?>><?php _e('Solo miembros', 'flavor-platform'); ?></option>
                                <option value="privada" <?php selected($campania->visibilidad ?? '', 'privada'); ?>><?php _e('Privada', 'flavor-platform'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="destacada"><?php _e('Destacada', 'flavor-platform'); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="destacada" id="destacada" value="1" <?php checked($campania->destacada ?? 0, 1); ?>>
                                <?php _e('Mostrar como destacada', 'flavor-platform'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="guardar_campania" class="button button-primary">
                        <?php echo $campania ? __('Actualizar Campania', 'flavor-platform') : __('Crear Campania', 'flavor-platform'); ?>
                    </button>
                </p>
            </form>
            <?php
            return;
        }

        // Filtros
        $filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        $filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
        $busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        // Construir query
        $where = "WHERE 1=1";
        $params = [];

        if ($filtro_estado) {
            $where .= " AND estado = %s";
            $params[] = $filtro_estado;
        }
        if ($filtro_tipo) {
            $where .= " AND tipo = %s";
            $params[] = $filtro_tipo;
        }
        if ($busqueda) {
            $where .= " AND (titulo LIKE %s OR descripcion LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
        }

        // Paginacion
        $por_pagina = 20;
        $pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($pagina_actual - 1) * $por_pagina;

        $total_items = (int) $wpdb->get_var(
            $params
                ? $wpdb->prepare("SELECT COUNT(*) FROM $tabla_campanias $where", $params)
                : "SELECT COUNT(*) FROM $tabla_campanias $where"
        );
        $total_paginas = ceil($total_items / $por_pagina);

        $query = "SELECT * FROM $tabla_campanias $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $por_pagina;
        $params[] = $offset;

        $campanias = $wpdb->get_results($wpdb->prepare($query, $params));

        $this->render_page_header(
            __('Listado de Campanias', 'flavor-platform'),
            [
                [
                    'label' => __('Nueva Campania', 'flavor-platform'),
                    'url' => admin_url('admin.php?page=campanias-listado&accion=nueva'),
                    'class' => 'button-primary',
                ],
            ]
        );
        ?>
        <div class="wrap">
            <!-- Filtros -->
            <form method="get" class="flavor-filters campanias-admin-list__filters">
                <input type="hidden" name="page" value="campanias-listado">

                <select name="estado">
                    <option value=""><?php _e('Todos los estados', 'flavor-platform'); ?></option>
                    <option value="planificada" <?php selected($filtro_estado, 'planificada'); ?>><?php _e('Planificada', 'flavor-platform'); ?></option>
                    <option value="activa" <?php selected($filtro_estado, 'activa'); ?>><?php _e('Activa', 'flavor-platform'); ?></option>
                    <option value="pausada" <?php selected($filtro_estado, 'pausada'); ?>><?php _e('Pausada', 'flavor-platform'); ?></option>
                    <option value="completada" <?php selected($filtro_estado, 'completada'); ?>><?php _e('Completada', 'flavor-platform'); ?></option>
                    <option value="cancelada" <?php selected($filtro_estado, 'cancelada'); ?>><?php _e('Cancelada', 'flavor-platform'); ?></option>
                </select>

                <select name="tipo">
                    <option value=""><?php _e('Todos los tipos', 'flavor-platform'); ?></option>
                    <?php foreach ($this->get_tipos_campania() as $valor => $etiqueta): ?>
                        <option value="<?php echo esc_attr($valor); ?>" <?php selected($filtro_tipo, $valor); ?>>
                            <?php echo esc_html($etiqueta); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="search" name="s" value="<?php echo esc_attr($busqueda); ?>" placeholder="<?php _e('Buscar...', 'flavor-platform'); ?>">

                <button type="submit" class="button"><?php _e('Filtrar', 'flavor-platform'); ?></button>

                <?php if ($filtro_estado || $filtro_tipo || $busqueda): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=campanias-listado')); ?>" class="button">
                        <?php _e('Limpiar filtros', 'flavor-platform'); ?>
                    </a>
                <?php endif; ?>
            </form>

            <!-- Tabla de campanias -->
            <?php if (empty($campanias)): ?>
                <div class="notice notice-info">
                    <p><?php _e('No se encontraron campanias.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="campanias-admin-list__col-title"><?php _e('Titulo', 'flavor-platform'); ?></th>
                            <th><?php _e('Tipo', 'flavor-platform'); ?></th>
                            <th><?php _e('Estado', 'flavor-platform'); ?></th>
                            <th><?php _e('Firmas', 'flavor-platform'); ?></th>
                            <th><?php _e('Fecha inicio', 'flavor-platform'); ?></th>
                            <th><?php _e('Creador', 'flavor-platform'); ?></th>
                            <th class="campanias-admin-list__col-actions"><?php _e('Acciones', 'flavor-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campanias as $campania): ?>
                            <?php $creador = get_userdata($campania->creador_id); ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=campanias-listado&accion=editar&id=' . $campania->id)); ?>">
                                            <?php echo esc_html($campania->titulo); ?>
                                        </a>
                                    </strong>
                                    <?php if ($campania->destacada): ?>
                                        <span class="dashicons dashicons-star-filled campanias-admin-list__icon-star" title="<?php _e('Destacada', 'flavor-platform'); ?>"></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($this->get_tipos_campania()[$campania->tipo] ?? $campania->tipo); ?></td>
                                <td>
                                    <span class="flavor-badge campanias-admin-list__status-badge" style="background: <?php
                                        switch ($campania->estado) {
                                            case 'activa': echo '#00a32a'; break;
                                            case 'completada': echo '#2271b1'; break;
                                            case 'planificada': echo '#646970'; break;
                                            case 'pausada': echo '#dba617'; break;
                                            case 'cancelada': echo '#d63638'; break;
                                            default: echo '#646970';
                                        }
                                    ?>; color: #fff;">
                                        <?php echo esc_html(ucfirst($campania->estado)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($campania->objetivo_firmas > 0): ?>
                                        <?php echo intval($campania->firmas_actuales); ?>/<?php echo intval($campania->objetivo_firmas); ?>
                                        <div class="campanias-admin-list__progress-track">
                                            <div style="background: #2271b1; width: <?php echo min(100, ($campania->firmas_actuales / $campania->objetivo_firmas) * 100); ?>%; height: 100%; border-radius: 2px;"></div>
                                        </div>
                                    <?php else: ?>
                                        <?php echo intval($campania->firmas_actuales); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $campania->fecha_inicio ? date_i18n(get_option('date_format'), strtotime($campania->fecha_inicio)) : '-'; ?></td>
                                <td><?php echo $creador ? esc_html($creador->display_name) : '-'; ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=campanias-listado&accion=editar&id=' . $campania->id)); ?>" class="button button-small">
                                        <?php _e('Editar', 'flavor-platform'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=campanias-listado&accion=eliminar&id=' . $campania->id), 'eliminar_campania_' . $campania->id)); ?>"
                                       class="button button-small button-link-delete"
                                       onclick="return confirm('<?php _e('Seguro que quieres eliminar esta campania?', 'flavor-platform'); ?>');">
                                        <?php _e('Eliminar', 'flavor-platform'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginacion -->
                <?php if ($total_paginas > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php printf(__('%d elementos', 'flavor-platform'), $total_items); ?></span>
                            <span class="pagination-links">
                                <?php
                                $base_url = admin_url('admin.php?page=campanias-listado');
                                if ($filtro_estado) $base_url = add_query_arg('estado', $filtro_estado, $base_url);
                                if ($filtro_tipo) $base_url = add_query_arg('tipo', $filtro_tipo, $base_url);
                                if ($busqueda) $base_url = add_query_arg('s', $busqueda, $base_url);

                                if ($pagina_actual > 1): ?>
                                    <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', $pagina_actual - 1, $base_url)); ?>">
                                        <span class="screen-reader-text"><?php _e('Anterior', 'flavor-platform'); ?></span>
                                        <span aria-hidden="true">&lsaquo;</span>
                                    </a>
                                <?php endif; ?>

                                <span class="paging-input">
                                    <?php echo $pagina_actual; ?> <?php _e('de', 'flavor-platform'); ?> <?php echo $total_paginas; ?>
                                </span>

                                <?php if ($pagina_actual < $total_paginas): ?>
                                    <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', $pagina_actual + 1, $base_url)); ?>">
                                        <span class="screen-reader-text"><?php _e('Siguiente', 'flavor-platform'); ?></span>
                                        <span aria-hidden="true">&rsaquo;</span>
                                    </a>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza la pagina de firmas en admin
     */
    public function render_admin_firmas() {
        global $wpdb;
        $tabla_firmas = $wpdb->prefix . 'flavor_campanias_firmas';
        $tabla_campanias = $wpdb->prefix . 'flavor_campanias';

        // Filtros
        $filtro_campania = isset($_GET['campania_id']) ? intval($_GET['campania_id']) : 0;
        $filtro_verificada = isset($_GET['verificada']) ? sanitize_text_field($_GET['verificada']) : '';
        $busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        // Eliminar firma
        if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id'])) {
            $firma_id = intval($_GET['id']);
            if (check_admin_referer('eliminar_firma_' . $firma_id)) {
                // Obtener campania_id para actualizar contador
                $campania_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT campania_id FROM $tabla_firmas WHERE id = %d",
                    $firma_id
                ));

                $wpdb->delete($tabla_firmas, ['id' => $firma_id]);

                if ($campania_id) {
                    $wpdb->query($wpdb->prepare(
                        "UPDATE $tabla_campanias SET firmas_actuales = GREATEST(0, firmas_actuales - 1) WHERE id = %d",
                        $campania_id
                    ));
                }

                echo '<div class="notice notice-success is-dismissible"><p>' . __('Firma eliminada correctamente.', 'flavor-platform') . '</p></div>';
            }
        }

        // Verificar firma
        if (isset($_GET['accion']) && $_GET['accion'] === 'verificar' && isset($_GET['id'])) {
            $firma_id = intval($_GET['id']);
            if (check_admin_referer('verificar_firma_' . $firma_id)) {
                $wpdb->update($tabla_firmas, ['verificada' => 1], ['id' => $firma_id]);
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Firma verificada correctamente.', 'flavor-platform') . '</p></div>';
            }
        }

        // Construir query
        $where = "WHERE 1=1";
        $params = [];

        if ($filtro_campania) {
            $where .= " AND f.campania_id = %d";
            $params[] = $filtro_campania;
        }
        if ($filtro_verificada !== '') {
            $where .= " AND f.verificada = %d";
            $params[] = intval($filtro_verificada);
        }
        if ($busqueda) {
            $where .= " AND (f.nombre LIKE %s OR f.email LIKE %s OR f.localidad LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
        }

        // Paginacion
        $por_pagina = 50;
        $pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($pagina_actual - 1) * $por_pagina;

        $count_query = "SELECT COUNT(*) FROM $tabla_firmas f $where";
        $total_items = (int) ($params ? $wpdb->get_var($wpdb->prepare($count_query, $params)) : $wpdb->get_var($count_query));
        $total_paginas = ceil($total_items / $por_pagina);

        $query = "SELECT f.*, c.titulo as campania_titulo
                  FROM $tabla_firmas f
                  LEFT JOIN $tabla_campanias c ON f.campania_id = c.id
                  $where
                  ORDER BY f.created_at DESC
                  LIMIT %d OFFSET %d";
        $params[] = $por_pagina;
        $params[] = $offset;

        $firmas = $wpdb->get_results($wpdb->prepare($query, $params));

        // Obtener campanias para el filtro
        $campanias_disponibles = $wpdb->get_results("SELECT id, titulo FROM $tabla_campanias ORDER BY titulo ASC");

        $this->render_page_header(
            __('Historico de Firmas', 'flavor-platform'),
            [
                [
                    'label' => __('Exportar CSV', 'flavor-platform'),
                    'url' => admin_url('admin.php?page=campanias-firmas&accion=exportar' . ($filtro_campania ? '&campania_id=' . $filtro_campania : '')),
                    'class' => 'button-secondary',
                ],
            ]
        );
        ?>
        <div class="wrap">
            <!-- Estadisticas rapidas -->
            <div class="flavor-stats-inline campanias-admin-firmas__stats-inline">
                <div>
                    <strong><?php echo $total_items; ?></strong>
                    <span class="campanias-admin-firmas__stats-label"><?php _e('Total firmas', 'flavor-platform'); ?></span>
                </div>
                <div>
                    <strong><?php echo (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_firmas WHERE verificada = 1"); ?></strong>
                    <span class="campanias-admin-firmas__stats-label"><?php _e('Verificadas', 'flavor-platform'); ?></span>
                </div>
                <div>
                    <strong><?php echo (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tabla_firmas WHERE DATE(created_at) = %s", current_time('Y-m-d'))); ?></strong>
                    <span class="campanias-admin-firmas__stats-label"><?php _e('Hoy', 'flavor-platform'); ?></span>
                </div>
            </div>

            <!-- Filtros -->
            <form method="get" class="flavor-filters campanias-admin-firmas__filters">
                <input type="hidden" name="page" value="campanias-firmas">

                <select name="campania_id">
                    <option value=""><?php _e('Todas las campanias', 'flavor-platform'); ?></option>
                    <?php foreach ($campanias_disponibles as $campania): ?>
                        <option value="<?php echo esc_attr($campania->id); ?>" <?php selected($filtro_campania, $campania->id); ?>>
                            <?php echo esc_html($campania->titulo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="verificada">
                    <option value=""><?php _e('Todas las firmas', 'flavor-platform'); ?></option>
                    <option value="1" <?php selected($filtro_verificada, '1'); ?>><?php _e('Verificadas', 'flavor-platform'); ?></option>
                    <option value="0" <?php selected($filtro_verificada, '0'); ?>><?php _e('No verificadas', 'flavor-platform'); ?></option>
                </select>

                <input type="search" name="s" value="<?php echo esc_attr($busqueda); ?>" placeholder="<?php _e('Buscar por nombre, email...', 'flavor-platform'); ?>">

                <button type="submit" class="button"><?php _e('Filtrar', 'flavor-platform'); ?></button>

                <?php if ($filtro_campania || $filtro_verificada !== '' || $busqueda): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=campanias-firmas')); ?>" class="button">
                        <?php _e('Limpiar filtros', 'flavor-platform'); ?>
                    </a>
                <?php endif; ?>
            </form>

            <!-- Tabla de firmas -->
            <?php if (empty($firmas)): ?>
                <div class="notice notice-info">
                    <p><?php _e('No se encontraron firmas.', 'flavor-platform'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Nombre', 'flavor-platform'); ?></th>
                            <th><?php _e('Email', 'flavor-platform'); ?></th>
                            <th><?php _e('Localidad', 'flavor-platform'); ?></th>
                            <th><?php _e('Campania', 'flavor-platform'); ?></th>
                            <th><?php _e('Estado', 'flavor-platform'); ?></th>
                            <th><?php _e('Fecha', 'flavor-platform'); ?></th>
                            <th class="campanias-admin-firmas__col-actions"><?php _e('Acciones', 'flavor-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($firmas as $firma): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($firma->nombre); ?></strong>
                                    <?php if ($firma->user_id): ?>
                                        <span class="dashicons dashicons-admin-users campanias-admin-firmas__icon-user" title="<?php _e('Usuario registrado', 'flavor-platform'); ?>"></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($firma->email); ?></td>
                                <td><?php echo esc_html($firma->localidad ?: '-'); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=campanias-listado&accion=editar&id=' . $firma->campania_id)); ?>">
                                        <?php echo esc_html($firma->campania_titulo); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($firma->verificada): ?>
                                        <span class="dashicons dashicons-yes-alt campanias-admin-firmas__icon-verified"></span>
                                        <?php _e('Verificada', 'flavor-platform'); ?>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-marker campanias-admin-firmas__icon-pending"></span>
                                        <?php _e('Pendiente', 'flavor-platform'); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($firma->created_at)); ?>
                                </td>
                                <td>
                                    <?php if (!$firma->verificada): ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=campanias-firmas&accion=verificar&id=' . $firma->id), 'verificar_firma_' . $firma->id)); ?>"
                                           class="button button-small" title="<?php _e('Verificar', 'flavor-platform'); ?>">
                                            <span class="dashicons dashicons-yes campanias-admin-firmas__icon-action"></span>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=campanias-firmas&accion=eliminar&id=' . $firma->id), 'eliminar_firma_' . $firma->id)); ?>"
                                       class="button button-small button-link-delete"
                                       onclick="return confirm('<?php _e('Seguro que quieres eliminar esta firma?', 'flavor-platform'); ?>');"
                                       title="<?php _e('Eliminar', 'flavor-platform'); ?>">
                                        <span class="dashicons dashicons-trash campanias-admin-firmas__icon-action"></span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginacion -->
                <?php if ($total_paginas > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php printf(__('%d firmas', 'flavor-platform'), $total_items); ?></span>
                            <span class="pagination-links">
                                <?php
                                $base_url = admin_url('admin.php?page=campanias-firmas');
                                if ($filtro_campania) $base_url = add_query_arg('campania_id', $filtro_campania, $base_url);
                                if ($filtro_verificada !== '') $base_url = add_query_arg('verificada', $filtro_verificada, $base_url);
                                if ($busqueda) $base_url = add_query_arg('s', $busqueda, $base_url);

                                if ($pagina_actual > 1): ?>
                                    <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', $pagina_actual - 1, $base_url)); ?>">
                                        <span aria-hidden="true">&lsaquo;</span>
                                    </a>
                                <?php endif; ?>

                                <span class="paging-input">
                                    <?php echo $pagina_actual; ?> <?php _e('de', 'flavor-platform'); ?> <?php echo $total_paginas; ?>
                                </span>

                                <?php if ($pagina_actual < $total_paginas): ?>
                                    <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', $pagina_actual + 1, $base_url)); ?>">
                                        <span aria-hidden="true">&rsaquo;</span>
                                    </a>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza la pagina de configuracion del modulo
     */
    public function render_admin_config() {
        // Guardar configuracion
        if (isset($_POST['guardar_config']) && check_admin_referer('campanias_config', 'campanias_config_nonce')) {
            $opciones = [
                'requiere_aprobacion' => isset($_POST['requiere_aprobacion']),
                'permitir_firmas_anonimas' => isset($_POST['permitir_firmas_anonimas']),
                'verificar_email_firmas' => isset($_POST['verificar_email_firmas']),
                'max_campanias_por_usuario' => intval($_POST['max_campanias_por_usuario']),
                'notificar_nuevas_campanias' => isset($_POST['notificar_nuevas_campanias']),
                'mostrar_mapa_acciones' => isset($_POST['mostrar_mapa_acciones']),
                'tipos_permitidos' => isset($_POST['tipos_permitidos']) ? array_map('sanitize_text_field', $_POST['tipos_permitidos']) : [],
                'email_notificaciones' => sanitize_email($_POST['email_notificaciones']),
                'texto_firma_exitosa' => sanitize_textarea_field($_POST['texto_firma_exitosa']),
                'mostrar_firmantes_publicos' => isset($_POST['mostrar_firmantes_publicos']),
                'limite_firmas_por_ip' => intval($_POST['limite_firmas_por_ip']),
            ];

            update_option('flavor_campanias_config', $opciones);
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Configuracion guardada correctamente.', 'flavor-platform') . '</p></div>';
        }

        // Obtener configuracion actual
        $config = get_option('flavor_campanias_config', $this->get_default_settings());
        $config = wp_parse_args($config, $this->get_default_settings());

        $this->render_page_header(__('Configuracion de Campanias', 'flavor-platform'));
        ?>
        <div class="wrap">
            <form method="post" class="flavor-admin-form" style="background: #fff; padding: 20px; max-width: 800px;">
                <?php wp_nonce_field('campanias_config', 'campanias_config_nonce'); ?>

                <h2><?php _e('Configuracion General', 'flavor-platform'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Aprobacion de campanias', 'flavor-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="requiere_aprobacion" value="1" <?php checked($config['requiere_aprobacion'], true); ?>>
                                <?php _e('Las nuevas campanias requieren aprobacion de un administrador', 'flavor-platform'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Campanias por usuario', 'flavor-platform'); ?></th>
                        <td>
                            <input type="number" name="max_campanias_por_usuario" min="1" max="100" value="<?php echo intval($config['max_campanias_por_usuario']); ?>">
                            <p class="description"><?php _e('Numero maximo de campanias que un usuario puede crear', 'flavor-platform'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Tipos de campania permitidos', 'flavor-platform'); ?></th>
                        <td>
                            <?php foreach ($this->get_tipos_campania() as $valor => $etiqueta): ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" name="tipos_permitidos[]" value="<?php echo esc_attr($valor); ?>"
                                        <?php checked(in_array($valor, $config['tipos_permitidos'] ?? [])); ?>>
                                    <?php echo esc_html($etiqueta); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Mostrar mapa de acciones', 'flavor-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mostrar_mapa_acciones" value="1" <?php checked($config['mostrar_mapa_acciones'], true); ?>>
                                <?php _e('Mostrar mapa interactivo con las acciones programadas', 'flavor-platform'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <h2><?php _e('Configuracion de Firmas', 'flavor-platform'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Firmas anonimas', 'flavor-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="permitir_firmas_anonimas" value="1" <?php checked($config['permitir_firmas_anonimas'], true); ?>>
                                <?php _e('Permitir firmar sin estar registrado', 'flavor-platform'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Verificacion de email', 'flavor-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="verificar_email_firmas" value="1" <?php checked($config['verificar_email_firmas'], true); ?>>
                                <?php _e('Requerir verificacion de email para validar firmas', 'flavor-platform'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Mostrar firmantes', 'flavor-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="mostrar_firmantes_publicos" value="1" <?php checked($config['mostrar_firmantes_publicos'] ?? false, true); ?>>
                                <?php _e('Mostrar lista de firmantes publicamente', 'flavor-platform'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Limite por IP', 'flavor-platform'); ?></th>
                        <td>
                            <input type="number" name="limite_firmas_por_ip" min="0" max="100" value="<?php echo intval($config['limite_firmas_por_ip'] ?? 0); ?>">
                            <p class="description"><?php _e('Numero maximo de firmas permitidas por IP (0 = sin limite)', 'flavor-platform'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Mensaje post-firma', 'flavor-platform'); ?></th>
                        <td>
                            <textarea name="texto_firma_exitosa" rows="3" class="large-text"><?php echo esc_textarea($config['texto_firma_exitosa'] ?? __('Gracias por firmar. Tu apoyo es muy importante para esta causa.', 'flavor-platform')); ?></textarea>
                            <p class="description"><?php _e('Mensaje que se muestra tras firmar exitosamente', 'flavor-platform'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php _e('Notificaciones', 'flavor-platform'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Notificar nuevas campanias', 'flavor-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="notificar_nuevas_campanias" value="1" <?php checked($config['notificar_nuevas_campanias'], true); ?>>
                                <?php _e('Enviar notificacion cuando se crea una nueva campania', 'flavor-platform'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Email para notificaciones', 'flavor-platform'); ?></th>
                        <td>
                            <input type="email" name="email_notificaciones" class="regular-text" value="<?php echo esc_attr($config['email_notificaciones'] ?? get_option('admin_email')); ?>">
                            <p class="description"><?php _e('Email donde se enviaran las notificaciones de administracion', 'flavor-platform'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php _e('Shortcodes disponibles', 'flavor-platform'); ?></h2>
                <div style="background: #f6f7f7; padding: 15px; border-radius: 4px;">
                    <p><code>[campanias_listar]</code> - <?php _e('Muestra el listado de campanias', 'flavor-platform'); ?></p>
                    <p><code>[campanias_detalle id="X"]</code> - <?php _e('Muestra el detalle de una campania', 'flavor-platform'); ?></p>
                    <p><code>[campanias_crear]</code> - <?php _e('Formulario para crear campania', 'flavor-platform'); ?></p>
                    <p><code>[campanias_mis_campanias]</code> - <?php _e('Campanias del usuario actual', 'flavor-platform'); ?></p>
                    <p><code>[campanias_firmar id="X"]</code> - <?php _e('Formulario para firmar una campania', 'flavor-platform'); ?></p>
                    <p><code>[campanias_mapa]</code> - <?php _e('Mapa de acciones', 'flavor-platform'); ?></p>
                    <p><code>[campanias_calendario]</code> - <?php _e('Calendario de acciones', 'flavor-platform'); ?></p>
                </div>

                <p class="submit">
                    <button type="submit" name="guardar_config" class="button button-primary">
                        <?php _e('Guardar Configuracion', 'flavor-platform'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-campanias-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Campanias_Dashboard_Tab')) {
                Flavor_Campanias_Dashboard_Tab::get_instance();
            }
        }
    }

    /**
     * Renderiza la página de estadísticas
     */
    public function render_admin_estadisticas() {
        $template_path = dirname(__FILE__) . '/views/estadisticas.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }
}

if (!class_exists('Flavor_Chat_Campanias_Module', false)) {
    class_alias('Flavor_Platform_Campanias_Module', 'Flavor_Chat_Campanias_Module');
}
