<?php
/**
 * Modulo de Documentacion Legal
 *
 * Repositorio de leyes, sentencias, modelos de denuncia y recursos legales.
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del modulo de Documentacion Legal
 */
class Flavor_Chat_Documentacion_Legal_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        // Auto-registered AJAX handlers
        add_action('wp_ajax_documentacion_legal_guardar_favorito', [$this, 'ajax_guardar_favorito']);
        add_action('wp_ajax_nopriv_documentacion_legal_guardar_favorito', [$this, 'ajax_guardar_favorito']);

        $this->id = 'documentacion_legal';
        $this->name = 'Documentacion Legal';
        $this->description = 'Repositorio de documentos legales: leyes, sentencias, modelos de denuncia, recursos administrativos y guias juridicas para la defensa del territorio.';

        parent::__construct();

        // Registrar en el Panel Unificado de Administracion
        $this->registrar_en_panel_unificado();
    }

    /**
     * Verifica si el modulo puede activarse
     */
    public function can_activate() {
        global $wpdb;
        return Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_documentacion_legal');
    }

    /**
     * Mensaje de error si no puede activarse
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return 'Las tablas del modulo Documentacion Legal no estan creadas. Desactiva y reactiva el modulo.';
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
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        if (!Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_documentacion_legal')) {
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

        // Tabla principal de documentos
        $tabla_documentos = $wpdb->prefix . 'flavor_documentacion_legal';
        $sql_documentos = "CREATE TABLE $tabla_documentos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text,
            contenido longtext,
            tipo enum('ley','decreto','ordenanza','sentencia','modelo_denuncia','modelo_recurso','guia','informe','otro') NOT NULL DEFAULT 'otro',
            categoria varchar(100),
            subcategoria varchar(100),
            ambito enum('estatal','autonomico','provincial','municipal','europeo') DEFAULT 'estatal',
            fecha_publicacion date,
            fecha_vigencia date,
            numero_referencia varchar(100),
            boe_bop varchar(255),
            url_oficial varchar(500),
            archivo_adjunto varchar(255),
            archivo_tipo varchar(50),
            archivo_tamano int unsigned,
            palabras_clave text,
            etiquetas text,
            autor_id bigint(20) unsigned NOT NULL,
            verificado tinyint(1) DEFAULT 0,
            verificado_por bigint(20) unsigned,
            destacado tinyint(1) DEFAULT 0,
            descargas int unsigned DEFAULT 0,
            visitas int unsigned DEFAULT 0,
            estado enum('publicado','borrador','revision','archivado') NOT NULL DEFAULT 'borrador',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY categoria (categoria),
            KEY ambito (ambito),
            KEY autor_id (autor_id),
            KEY estado (estado),
            KEY verificado (verificado),
            FULLTEXT KEY busqueda (titulo, descripcion, contenido, palabras_clave)
        ) $charset_collate;";
        dbDelta($sql_documentos);

        // Tabla de categorias
        $tabla_categorias = $wpdb->prefix . 'flavor_documentacion_legal_categorias';
        $sql_categorias = "CREATE TABLE $tabla_categorias (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            icono varchar(50),
            color varchar(7),
            parent_id bigint(20) unsigned DEFAULT 0,
            orden int DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY parent_id (parent_id)
        ) $charset_collate;";
        dbDelta($sql_categorias);

        // Tabla de favoritos/guardados
        $tabla_favoritos = $wpdb->prefix . 'flavor_documentacion_legal_favoritos';
        $sql_favoritos = "CREATE TABLE $tabla_favoritos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            documento_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            notas text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY documento_user (documento_id, user_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_favoritos);

        // Tabla de comentarios/anotaciones
        $tabla_comentarios = $wpdb->prefix . 'flavor_documentacion_legal_comentarios';
        $sql_comentarios = "CREATE TABLE $tabla_comentarios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            documento_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            comentario text NOT NULL,
            tipo enum('nota','pregunta','aclaracion','correccion') DEFAULT 'nota',
            estado enum('visible','oculto','resuelto') DEFAULT 'visible',
            parent_id bigint(20) unsigned DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY documento_id (documento_id),
            KEY user_id (user_id),
            KEY parent_id (parent_id)
        ) $charset_collate;";
        dbDelta($sql_comentarios);

        // Insertar categorias por defecto
        $this->insertar_categorias_defecto();
    }

    /**
     * Inserta categorias por defecto
     */
    private function insertar_categorias_defecto() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal_categorias';

        $categorias_existentes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
        if ($categorias_existentes > 0) return;

        $categorias = [
            ['nombre' => 'Medio Ambiente', 'slug' => 'medio-ambiente', 'icono' => 'dashicons-admin-site-alt3', 'color' => '#16a34a'],
            ['nombre' => 'Urbanismo', 'slug' => 'urbanismo', 'icono' => 'dashicons-building', 'color' => '#2563eb'],
            ['nombre' => 'Aguas', 'slug' => 'aguas', 'icono' => 'dashicons-admin-site', 'color' => '#0891b2'],
            ['nombre' => 'Montes y Forestacion', 'slug' => 'montes', 'icono' => 'dashicons-palmtree', 'color' => '#065f46'],
            ['nombre' => 'Patrimonio', 'slug' => 'patrimonio', 'icono' => 'dashicons-bank', 'color' => '#9333ea'],
            ['nombre' => 'Participacion Ciudadana', 'slug' => 'participacion', 'icono' => 'dashicons-groups', 'color' => '#dc2626'],
            ['nombre' => 'Transparencia', 'slug' => 'transparencia', 'icono' => 'dashicons-visibility', 'color' => '#f59e0b'],
            ['nombre' => 'Derechos Fundamentales', 'slug' => 'derechos', 'icono' => 'dashicons-shield', 'color' => '#7c3aed'],
            ['nombre' => 'Energia', 'slug' => 'energia', 'icono' => 'dashicons-lightbulb', 'color' => '#eab308'],
            ['nombre' => 'Agricultura', 'slug' => 'agricultura', 'icono' => 'dashicons-carrot', 'color' => '#84cc16'],
        ];

        foreach ($categorias as $index => $cat) {
            $wpdb->insert($tabla, array_merge($cat, ['orden' => $index]));
        }
    }

    /**
     * Configuracion por defecto
     */
    public function get_default_settings() {
        return [
            'requiere_verificacion' => true,
            'permitir_comentarios' => true,
            'permitir_descargas' => true,
            'mostrar_visitas' => true,
            'tipos_archivo_permitidos' => ['pdf', 'doc', 'docx', 'odt', 'txt'],
            'tamano_maximo_archivo' => 10, // MB
        ];
    }

    /**
     * Registra los shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('documentacion_legal_listar', [$this, 'shortcode_listar']);
        add_shortcode('documentacion_legal_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('documentacion_legal_buscar', [$this, 'shortcode_buscar']);
        add_shortcode('documentacion_legal_categorias', [$this, 'shortcode_categorias']);
        add_shortcode('documentacion_legal_subir', [$this, 'shortcode_subir']);
        add_shortcode('documentacion_legal_mis_guardados', [$this, 'shortcode_mis_guardados']);
    }

    /**
     * Shortcode para listar documentos
     */
    public function shortcode_listar($atts) {
        $atts = shortcode_atts([
            'tipo' => '',
            'categoria' => '',
            'limite' => 12,
            'orden' => 'recientes',
        ], $atts);

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/documentacion-legal/views/listado.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para detalle de documento
     */
    public function shortcode_detalle($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);

        $documento_id = $atts['id'] ?: (isset($_GET['documento_id']) ? intval($_GET['documento_id']) : 0);

        if (!$documento_id) {
            return '<p class="flavor-error">Documento no encontrado.</p>';
        }

        $documento = $this->obtener_documento($documento_id);
        if (!$documento) {
            return '<p class="flavor-error">Documento no encontrado.</p>';
        }

        // Incrementar visitas
        $this->incrementar_visitas($documento_id);

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/documentacion-legal/views/detalle.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para buscar documentos
     */
    public function shortcode_buscar($atts) {
        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/documentacion-legal/views/buscar.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para categorias
     */
    public function shortcode_categorias($atts) {
        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/documentacion-legal/views/categorias.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para subir documento
     */
    public function shortcode_subir($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-info">Debes iniciar sesion para subir documentos.</p>';
        }

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/documentacion-legal/views/subir.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para documentos guardados
     */
    public function shortcode_mis_guardados($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-info">Debes iniciar sesion para ver tus documentos guardados.</p>';
        }

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/documentacion-legal/views/mis-guardados.php';
        return ob_get_clean();
    }

    /**
     * Registra handlers AJAX
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_documentacion_legal_subir', [$this, 'ajax_subir']);
        add_action('wp_ajax_documentacion_legal_guardar', [$this, 'ajax_guardar_favorito']);
        add_action('wp_ajax_documentacion_legal_quitar_guardado', [$this, 'ajax_quitar_favorito']);
        add_action('wp_ajax_documentacion_legal_comentar', [$this, 'ajax_comentar']);
        add_action('wp_ajax_documentacion_legal_descargar', [$this, 'ajax_descargar']);
        add_action('wp_ajax_nopriv_documentacion_legal_descargar', [$this, 'ajax_descargar']);

        add_action('wp_ajax_documentacion_legal_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_nopriv_documentacion_legal_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_documentacion_legal_listar', [$this, 'ajax_listar']);
        add_action('wp_ajax_nopriv_documentacion_legal_listar', [$this, 'ajax_listar']);
    }

    /**
     * AJAX: Subir documento
     */
    public function ajax_subir() {
        check_ajax_referer('flavor_documentacion_legal_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => 'Debes iniciar sesion.']);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal';

        $datos = [
            'titulo' => sanitize_text_field($_POST['titulo'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'contenido' => wp_kses_post($_POST['contenido'] ?? ''),
            'tipo' => sanitize_text_field($_POST['tipo'] ?? 'otro'),
            'categoria' => sanitize_text_field($_POST['categoria'] ?? ''),
            'ambito' => sanitize_text_field($_POST['ambito'] ?? 'estatal'),
            'fecha_publicacion' => sanitize_text_field($_POST['fecha_publicacion'] ?? ''),
            'numero_referencia' => sanitize_text_field($_POST['numero_referencia'] ?? ''),
            'url_oficial' => esc_url_raw($_POST['url_oficial'] ?? ''),
            'palabras_clave' => sanitize_text_field($_POST['palabras_clave'] ?? ''),
            'etiquetas' => sanitize_text_field($_POST['etiquetas'] ?? ''),
            'autor_id' => get_current_user_id(),
            'estado' => 'borrador',
        ];

        if (empty($datos['titulo'])) {
            wp_send_json_error(['error' => 'El titulo es obligatorio.']);
        }

        // Manejar archivo adjunto
        if (!empty($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $archivo = $this->procesar_archivo($_FILES['archivo']);
            if (is_wp_error($archivo)) {
                wp_send_json_error(['error' => $archivo->get_error_message()]);
            }
            $datos['archivo_adjunto'] = $archivo['url'];
            $datos['archivo_tipo'] = $archivo['tipo'];
            $datos['archivo_tamano'] = $archivo['tamano'];
        }

        $resultado = $wpdb->insert($tabla, $datos);

        if ($resultado === false) {
            wp_send_json_error(['error' => 'Error al guardar el documento.']);
        }

        wp_send_json_success([
            'documento_id' => $wpdb->insert_id,
            'mensaje' => 'Documento guardado. Pendiente de revision.',
        ]);
    }

    /**
     * Procesa archivo subido
     */
    private function procesar_archivo($archivo) {
        $tipos_permitidos = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.oasis.opendocument.text', 'text/plain'];

        if (!in_array($archivo['type'], $tipos_permitidos)) {
            return new WP_Error('tipo_invalido', 'Tipo de archivo no permitido.');
        }

        $max_size = 10 * 1024 * 1024; // 10MB
        if ($archivo['size'] > $max_size) {
            return new WP_Error('tamano_excedido', 'El archivo excede el tamano maximo permitido (10MB).');
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = wp_handle_upload($archivo, ['test_form' => false]);

        if (isset($upload['error'])) {
            return new WP_Error('upload_error', $upload['error']);
        }

        return [
            'url' => $upload['url'],
            'tipo' => $archivo['type'],
            'tamano' => $archivo['size'],
        ];
    }

    /**
     * AJAX: Guardar en favoritos
     */
    public function ajax_guardar_favorito() {
        check_ajax_referer('flavor_documentacion_legal_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => 'Debes iniciar sesion.']);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal_favoritos';

        $documento_id = intval($_POST['documento_id'] ?? 0);
        $user_id = get_current_user_id();

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE documento_id = %d AND user_id = %d",
            $documento_id, $user_id
        ));

        if ($existe) {
            wp_send_json_error(['error' => 'Ya tienes este documento guardado.']);
        }

        $wpdb->insert($tabla, [
            'documento_id' => $documento_id,
            'user_id' => $user_id,
            'notas' => sanitize_textarea_field($_POST['notas'] ?? ''),
        ]);

        wp_send_json_success(['mensaje' => 'Documento guardado en favoritos.']);
    }

    /**
     * AJAX: Buscar documentos
     */
    public function ajax_buscar() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal';

        $busqueda = sanitize_text_field($_POST['q'] ?? $_GET['q'] ?? '');
        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $ambito = sanitize_text_field($_POST['ambito'] ?? '');
        $limite = intval($_POST['limite'] ?? 20);

        $where = "WHERE estado = 'publicado'";
        $params = [];

        if ($busqueda) {
            $where .= " AND (titulo LIKE %s OR descripcion LIKE %s OR palabras_clave LIKE %s)";
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($tipo) {
            $where .= " AND tipo = %s";
            $params[] = $tipo;
        }

        if ($categoria) {
            $where .= " AND categoria = %s";
            $params[] = $categoria;
        }

        if ($ambito) {
            $where .= " AND ambito = %s";
            $params[] = $ambito;
        }

        $params[] = $limite;

        $documentos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, tipo, categoria, ambito, fecha_publicacion, descargas, verificado
             FROM $tabla $where ORDER BY destacado DESC, created_at DESC LIMIT %d",
            $params
        ));

        wp_send_json_success(['documentos' => $documentos]);
    }

    /**
     * AJAX: Listar documentos
     */
    public function ajax_listar() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal';

        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $limite = intval($_POST['limite'] ?? 12);
        $offset = intval($_POST['offset'] ?? 0);

        $where = "WHERE estado = 'publicado'";
        $params = [];

        if ($tipo) {
            $where .= " AND tipo = %s";
            $params[] = $tipo;
        }
        if ($categoria) {
            $where .= " AND categoria = %s";
            $params[] = $categoria;
        }

        $params[] = $limite;
        $params[] = $offset;

        $documentos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla $where ORDER BY destacado DESC, created_at DESC LIMIT %d OFFSET %d",
            $params
        ));

        wp_send_json_success(['documentos' => $documentos]);
    }

    /**
     * Obtiene un documento con datos relacionados
     */
    private function obtener_documento($documento_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal';

        $documento = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $documento_id
        ));

        if (!$documento) return null;

        // Datos del autor
        $documento->autor = get_userdata($documento->autor_id);

        // Comentarios
        $tabla_comentarios = $wpdb->prefix . 'flavor_documentacion_legal_comentarios';
        $documento->comentarios = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name as autor_nombre
             FROM $tabla_comentarios c
             LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
             WHERE c.documento_id = %d AND c.estado = 'visible'
             ORDER BY c.created_at DESC",
            $documento_id
        ));

        // Documentos relacionados
        $documento->relacionados = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, tipo FROM $tabla
             WHERE categoria = %s AND id != %d AND estado = 'publicado'
             ORDER BY RAND() LIMIT 5",
            $documento->categoria, $documento_id
        ));

        return $documento;
    }

    /**
     * Incrementa visitas
     */
    private function incrementar_visitas($documento_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal';
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla SET visitas = visitas + 1 WHERE id = %d",
            $documento_id
        ));
    }

    /**
     * Registra rutas REST API
     */
    private function register_rest_routes() {
        add_action('rest_api_init', function() {
            register_rest_route('flavor/v1', '/documentacion-legal', [
                'methods' => 'GET',
                'callback' => [$this, 'api_listar'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('flavor/v1', '/documentacion-legal/(?P<id>\d+)', [
                'methods' => 'GET',
                'callback' => [$this, 'api_obtener'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('flavor/v1', '/documentacion-legal/buscar', [
                'methods' => 'GET',
                'callback' => [$this, 'api_buscar'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('flavor/v1', '/documentacion-legal/categorias', [
                'methods' => 'GET',
                'callback' => [$this, 'api_categorias'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    /**
     * API: Listar documentos
     */
    public function api_listar($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal';

        $tipo = $request->get_param('tipo');
        $categoria = $request->get_param('categoria');
        $limite = intval($request->get_param('limite')) ?: 12;

        $where = "WHERE estado = 'publicado'";
        $params = [];

        if ($tipo) {
            $where .= " AND tipo = %s";
            $params[] = $tipo;
        }
        if ($categoria) {
            $where .= " AND categoria = %s";
            $params[] = $categoria;
        }

        $params[] = $limite;

        $documentos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, descripcion, tipo, categoria, ambito, fecha_publicacion, descargas, verificado
             FROM $tabla $where ORDER BY destacado DESC, created_at DESC LIMIT %d",
            $params
        ));

        return rest_ensure_response(['documentos' => $documentos]);
    }

    /**
     * API: Obtener documento
     */
    public function api_obtener($request) {
        $documento = $this->obtener_documento($request['id']);

        if (!$documento || $documento->estado !== 'publicado') {
            return new WP_Error('not_found', 'Documento no encontrado', ['status' => 404]);
        }

        return rest_ensure_response(['documento' => $documento]);
    }

    /**
     * API: Buscar documentos
     */
    public function api_buscar($request) {
        $_POST = $request->get_params();
        ob_start();
        $this->ajax_buscar();
        return rest_ensure_response(json_decode(ob_get_clean(), true));
    }

    /**
     * API: Obtener categorias
     */
    public function api_categorias($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal_categorias';

        $categorias = $wpdb->get_results("SELECT * FROM $tabla ORDER BY orden ASC");

        return rest_ensure_response(['categorias' => $categorias]);
    }

    /**
     * Carga assets frontend
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_assets()) return;

        wp_enqueue_style(
            'flavor-documentacion-legal',
            FLAVOR_CHAT_IA_URL . 'includes/modules/documentacion-legal/assets/css/documentacion-legal.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-documentacion-legal',
            FLAVOR_CHAT_IA_URL . 'includes/modules/documentacion-legal/assets/js/documentacion-legal.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-documentacion-legal', 'flavorDocLegalConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_documentacion_legal_nonce'),
        ]);
    }

    /**
     * Determina si cargar assets
     */
    private function should_load_assets() {
        global $post;
        if (!$post) return false;

        $shortcodes = ['documentacion_legal_listar', 'documentacion_legal_detalle', 'documentacion_legal_buscar', 'documentacion_legal_categorias', 'documentacion_legal_subir', 'documentacion_legal_mis_guardados'];

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) return true;
        }

        return strpos($post->post_name, 'documentacion') !== false || strpos($post->post_name, 'legal') !== false;
    }

    /**
     * Obtiene las acciones disponibles para IA
     */
    public function get_actions() {
        return [
            'buscar_documentos' => [
                'descripcion' => 'Busca documentos legales por texto, categoria o tipo',
                'parametros' => [
                    'q' => ['tipo' => 'string', 'descripcion' => 'Texto de busqueda'],
                    'tipo' => ['tipo' => 'string', 'descripcion' => 'Tipo de documento (ley, sentencia, modelo_denuncia, etc.)'],
                    'categoria' => ['tipo' => 'string', 'descripcion' => 'Categoria (medio-ambiente, urbanismo, etc.)'],
                ],
            ],
            'ver_documento' => [
                'descripcion' => 'Muestra el detalle de un documento legal',
                'parametros' => [
                    'documento_id' => ['tipo' => 'integer', 'descripcion' => 'ID del documento', 'requerido' => true],
                ],
            ],
            'listar_categorias' => [
                'descripcion' => 'Lista las categorias de documentacion legal disponibles',
                'parametros' => [],
            ],
        ];
    }

    /**
     * Ejecuta una accion
     */
    public function execute_action($accion, $parametros = []) {
        $aliases = [
            'foro' => 'foro_documento',
            'chat' => 'chat_documento',
            'multimedia' => 'multimedia_documento',
            'red-social' => 'red_social_documento',
            'red_social' => 'red_social_documento',
        ];

        $accion = $aliases[$accion] ?? $accion;

        switch ($accion) {
            case 'buscar_documentos':
                return $this->action_buscar($parametros);
            case 'ver_documento':
                return $this->action_ver_documento($parametros);
            case 'listar_categorias':
                return $this->action_listar_categorias();
            case 'foro_documento':
                return $this->action_foro_documento($parametros);
            case 'chat_documento':
                return $this->action_chat_documento($parametros);
            case 'multimedia_documento':
                return $this->action_multimedia_documento($parametros);
            case 'red_social_documento':
                return $this->action_red_social_documento($parametros);
            default:
                return ['success' => false, 'error' => 'Accion no reconocida'];
        }
    }

    private function resolve_contextual_documento(array $params = []): ?array {
        global $wpdb;

        $documento_id = absint(
            $params['documento_id']
            ?? $params['id']
            ?? $_GET['documento_id']
            ?? $_GET['id']
            ?? 0
        );

        if (!$documento_id) {
            return null;
        }

        $tabla = $wpdb->prefix . 'flavor_documentacion_legal';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return null;
        }

        $documento = $wpdb->get_row($wpdb->prepare(
            "SELECT id, titulo, descripcion FROM {$tabla} WHERE id = %d",
            $documento_id
        ));

        if (!$documento) {
            return null;
        }

        return [
            'id' => (int) $documento->id,
            'titulo' => (string) $documento->titulo,
            'descripcion' => (string) ($documento->descripcion ?? ''),
        ];
    }

    private function action_foro_documento($params) {
        $documento = $this->resolve_contextual_documento((array) $params);
        if (!$documento) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un documento para ver su foro.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-foro">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;">'
            . '<h2>' . esc_html__('Foro del documento', 'flavor-chat-ia') . '</h2>'
            . '<p>' . esc_html($documento['titulo']) . '</p>'
            . '</div>'
            . do_shortcode('[flavor_foros_integrado entidad="documento_legal" entidad_id="' . absint($documento['id']) . '"]')
            . '</div>';
    }

    private function action_chat_documento($params) {
        $documento = $this->resolve_contextual_documento((array) $params);
        if (!$documento) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un documento para ver su chat.', 'flavor-chat-ia') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en el chat de este documento.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-chat">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Chat del documento', 'flavor-chat-ia') . '</h2><p>' . esc_html($documento['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/chat-grupos/mensajes/?documento_id=' . absint($documento['id']))) . '" class="button button-secondary">'
            . esc_html__('Abrir chat completo', 'flavor-chat-ia')
            . '</a></div>'
            . do_shortcode('[flavor_chat_grupo_integrado entidad="documento_legal" entidad_id="' . absint($documento['id']) . '"]')
            . '</div>';
    }

    private function action_multimedia_documento($params) {
        $documento = $this->resolve_contextual_documento((array) $params);
        if (!$documento) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un documento para ver sus archivos relacionados.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-multimedia">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Archivos del documento', 'flavor-chat-ia') . '</h2><p>' . esc_html($documento['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/multimedia/subir/?documento_id=' . absint($documento['id']))) . '" class="button button-primary">'
            . esc_html__('Subir archivo', 'flavor-chat-ia')
            . '</a></div>'
            . do_shortcode('[flavor_multimedia_galeria entidad="documento_legal" entidad_id="' . absint($documento['id']) . '"]')
            . '</div>';
    }

    private function action_red_social_documento($params) {
        $documento = $this->resolve_contextual_documento((array) $params);
        if (!$documento) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona un documento para ver su actividad social.', 'flavor-chat-ia') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en la actividad social de este documento.', 'flavor-chat-ia') . '</p>';
        }

        return '<div class="flavor-contextual-tab flavor-contextual-red-social">'
            . '<div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2>' . esc_html__('Actividad social del documento', 'flavor-chat-ia') . '</h2><p>' . esc_html($documento['titulo']) . '</p></div>'
            . '<a href="' . esc_url(home_url('/mi-portal/red-social/crear/?documento_id=' . absint($documento['id']))) . '" class="button button-primary">'
            . esc_html__('Publicar', 'flavor-chat-ia')
            . '</a></div>'
            . do_shortcode('[flavor_social_feed entidad="documento_legal" entidad_id="' . absint($documento['id']) . '"]')
            . '</div>';
    }

    /**
     * Accion: Buscar documentos
     */
    private function action_buscar($params) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal';

        $busqueda = sanitize_text_field($params['q'] ?? '');
        $tipo = sanitize_text_field($params['tipo'] ?? '');
        $categoria = sanitize_text_field($params['categoria'] ?? '');

        $where = "WHERE estado = 'publicado'";
        $query_params = [];

        if ($busqueda) {
            $where .= " AND (titulo LIKE %s OR descripcion LIKE %s)";
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $query_params[] = $like;
            $query_params[] = $like;
        }

        if ($tipo) {
            $where .= " AND tipo = %s";
            $query_params[] = $tipo;
        }

        if ($categoria) {
            $where .= " AND categoria = %s";
            $query_params[] = $categoria;
        }

        $query_params[] = 10;

        $documentos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, tipo, categoria, fecha_publicacion FROM $tabla $where LIMIT %d",
            $query_params
        ));

        return ['success' => true, 'documentos' => $documentos];
    }

    /**
     * Accion: Ver documento
     */
    private function action_ver_documento($params) {
        $documento = $this->obtener_documento(intval($params['documento_id'] ?? 0));

        if (!$documento) {
            return ['success' => false, 'error' => 'Documento no encontrado'];
        }

        return ['success' => true, 'documento' => $documento];
    }

    /**
     * Accion: Listar categorias
     */
    private function action_listar_categorias() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal_categorias';
        $categorias = $wpdb->get_results("SELECT nombre, slug, descripcion FROM $tabla ORDER BY orden");
        return ['success' => true, 'categorias' => $categorias];
    }

    /**
     * Obtiene tipos de documento
     */
    public function get_tipos_documento() {
        return [
            'ley' => 'Ley',
            'decreto' => 'Decreto',
            'ordenanza' => 'Ordenanza',
            'sentencia' => 'Sentencia',
            'modelo_denuncia' => 'Modelo de Denuncia',
            'modelo_recurso' => 'Modelo de Recurso',
            'guia' => 'Guia',
            'informe' => 'Informe',
            'otro' => 'Otro',
        ];
    }

    /**
     * Renderiza widget dashboard
     */
    public function render_dashboard_widget() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_documentacion_legal';

        $estadisticas = [
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'publicado'"),
            'modelos' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'publicado' AND tipo IN ('modelo_denuncia', 'modelo_recurso')"),
            'descargas' => $wpdb->get_var("SELECT SUM(descargas) FROM $tabla"),
        ];

        include FLAVOR_CHAT_IA_PATH . 'includes/modules/documentacion-legal/views/dashboard.php';
    }

    /**
     * Definiciones para IA
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'documentacion_legal_buscar',
                'description' => 'Busca leyes, sentencias, modelos de denuncia y documentos legales',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'q' => ['type' => 'string', 'description' => 'Texto de busqueda'],
                        'tipo' => ['type' => 'string', 'description' => 'Tipo: ley, sentencia, modelo_denuncia, etc.'],
                        'categoria' => ['type' => 'string', 'description' => 'Categoria tematica'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Base de conocimiento
     */
    public function get_knowledge_base() {
        return "El modulo de Documentacion Legal proporciona un repositorio de documentos juridicos incluyendo leyes, decretos, ordenanzas, sentencias, modelos de denuncia, modelos de recurso y guias legales. Los documentos estan organizados por categorias tematicas (medio ambiente, urbanismo, aguas, etc.) y ambito territorial (estatal, autonomico, provincial, municipal, europeo). Los usuarios pueden buscar, descargar y guardar documentos en favoritos.";
    }

    /**
     * FAQs
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Donde encuentro modelos de denuncia?',
                'respuesta' => 'En la seccion de documentacion legal, filtra por tipo "Modelo de denuncia" para ver todos los modelos disponibles.',
            ],
            [
                'pregunta' => 'Puedo subir documentos?',
                'respuesta' => 'Si, los usuarios registrados pueden subir documentos. Estos pasan por un proceso de verificacion antes de publicarse.',
            ],
        ];
    }

    // =========================================================================
    // PANEL UNIFICADO DE ADMINISTRACION
    // =========================================================================

    /**
     * Configuracion para el Panel Unificado de Administracion
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id'         => 'documentacion_legal',
            'label'      => __('Documentacion Legal', 'flavor-chat-ia'),
            'icon'       => 'dashicons-media-document',
            'capability' => 'manage_options',
            'categoria'  => 'recursos', // personas|economia|operaciones|recursos|comunicacion|actividades|servicios|comunidad|sostenibilidad
            'paginas'    => [
                [
                    'slug'     => 'documentos-dashboard',
                    'titulo'   => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_pagina_dashboard'],
                ],
                [
                    'slug'     => 'documentos-listado',
                    'titulo'   => __('Listado', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_listado'],
                    'badge'    => [$this, 'contar_documentos_pendientes'],
                ],
                [
                    'slug'     => 'documentos-categorias',
                    'titulo'   => __('Categorias', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_categorias'],
                ],
                [
                    'slug'     => 'documentos-config',
                    'titulo'   => __('Configuracion', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_dashboard_widget_admin'],
            'estadisticas'     => [$this, 'get_estadisticas_admin'],
        ];
    }

    /**
     * Cuenta documentos pendientes de revision para badge
     *
     * @return int
     */
    public function contar_documentos_pendientes() {
        global $wpdb;
        $tabla_documentos = $wpdb->prefix . 'flavor_documentacion_legal';

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_documentos} WHERE estado IN ('borrador', 'revision')"
        );
    }

    /**
     * Obtiene estadisticas para el dashboard admin
     *
     * @return array
     */
    public function get_estadisticas_admin() {
        global $wpdb;
        $tabla_documentos = $wpdb->prefix . 'flavor_documentacion_legal';
        $tabla_categorias = $wpdb->prefix . 'flavor_documentacion_legal_categorias';

        // Consolidar 4 queries a tabla_documentos en 1 sola query
        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) AS total_documentos,
                SUM(CASE WHEN estado = 'publicado' THEN 1 ELSE 0 END) AS documentos_publicados,
                SUM(CASE WHEN estado IN ('borrador', 'revision') THEN 1 ELSE 0 END) AS documentos_pendientes,
                COALESCE(SUM(descargas), 0) AS total_descargas
            FROM {$tabla_documentos}"
        );

        $total_categorias = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_categorias}");

        return [
            'total_documentos'      => (int) ($stats->total_documentos ?? 0),
            'documentos_publicados' => (int) ($stats->documentos_publicados ?? 0),
            'documentos_pendientes' => (int) ($stats->documentos_pendientes ?? 0),
            'total_descargas'       => (int) ($stats->total_descargas ?? 0),
            'total_categorias'      => $total_categorias,
        ];
    }

    /**
     * Renderiza el Dashboard de Documentacion Legal
     */
    public function render_admin_dashboard() {
        global $wpdb;
        $tabla_documentos = $wpdb->prefix . 'flavor_documentacion_legal';
        $tabla_categorias = $wpdb->prefix . 'flavor_documentacion_legal_categorias';

        // Estadisticas generales
        $estadisticas = $this->get_estadisticas_admin();

        // Documentos recientes
        $documentos_recientes = $wpdb->get_results(
            "SELECT id, titulo, tipo, estado, descargas, created_at
             FROM {$tabla_documentos}
             ORDER BY created_at DESC
             LIMIT 10"
        );

        // Categorias con conteo
        $categorias_con_conteo = $wpdb->get_results(
            "SELECT c.id, c.nombre, c.slug, c.icono, c.color,
                    COUNT(d.id) as total_documentos
             FROM {$tabla_categorias} c
             LEFT JOIN {$tabla_documentos} d ON d.categoria = c.slug AND d.estado = 'publicado'
             GROUP BY c.id
             ORDER BY total_documentos DESC
             LIMIT 10"
        );

        // Documentos mas descargados
        $documentos_populares = $wpdb->get_results(
            "SELECT id, titulo, tipo, descargas, visitas
             FROM {$tabla_documentos}
             WHERE estado = 'publicado'
             ORDER BY descargas DESC
             LIMIT 5"
        );

        // Tipos de documento disponibles
        $tipos_documento = $this->get_tipos_documento();

        ?>
        <div class="wrap flavor-admin-dashboard">
            <?php $this->render_page_header(__('Dashboard - Documentacion Legal', 'flavor-chat-ia'), [
                ['label' => __('Nuevo Documento', 'flavor-chat-ia'), 'url' => '#nuevo-documento', 'class' => 'button-primary'],
            ]); ?>

            <?php if (method_exists($this, 'render_admin_module_hub')) : ?>
                <?php $this->render_admin_module_hub([
                    'description' => __('Acceso visible al dashboard, listado, categorías, configuración y al bloque principal de métricas.', 'flavor-chat-ia'),
                    'stats_anchor' => '#documentacion-legal-stats',
                    'extra_items' => [
                        [
                            'label' => __('Portal', 'flavor-chat-ia'),
                            'url' => home_url('/mi-portal/documentacion-legal/'),
                            'icon' => 'dashicons-external',
                        ],
                    ],
                ]); ?>
            <?php endif; ?>

            <!-- KPIs -->
            <div id="documentacion-legal-stats" class="flavor-kpis-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span class="dashicons dashicons-media-document" style="font-size: 40px; color: #2271b1;"></span>
                        <div>
                            <div style="font-size: 28px; font-weight: 600; color: #1d2327;"><?php echo esc_html($estadisticas['total_documentos']); ?></div>
                            <div style="color: #646970;"><?php esc_html_e('Total Documentos', 'flavor-chat-ia'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span class="dashicons dashicons-download" style="font-size: 40px; color: #00a32a;"></span>
                        <div>
                            <div style="font-size: 28px; font-weight: 600; color: #1d2327;"><?php echo esc_html(number_format($estadisticas['total_descargas'])); ?></div>
                            <div style="color: #646970;"><?php esc_html_e('Descargas Totales', 'flavor-chat-ia'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span class="dashicons dashicons-yes-alt" style="font-size: 40px; color: #00a32a;"></span>
                        <div>
                            <div style="font-size: 28px; font-weight: 600; color: #1d2327;"><?php echo esc_html($estadisticas['documentos_publicados']); ?></div>
                            <div style="color: #646970;"><?php esc_html_e('Publicados', 'flavor-chat-ia'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span class="dashicons dashicons-clock" style="font-size: 40px; color: #dba617;"></span>
                        <div>
                            <div style="font-size: 28px; font-weight: 600; color: #1d2327;"><?php echo esc_html($estadisticas['documentos_pendientes']); ?></div>
                            <div style="color: #646970;"><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span class="dashicons dashicons-category" style="font-size: 40px; color: #8c5cb6;"></span>
                        <div>
                            <div style="font-size: 28px; font-weight: 600; color: #1d2327;"><?php echo esc_html($estadisticas['total_categorias']); ?></div>
                            <div style="color: #646970;"><?php esc_html_e('Categorias', 'flavor-chat-ia'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <!-- Documentos recientes -->
                <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
                        <span class="dashicons dashicons-clock"></span>
                        <?php esc_html_e('Documentos Recientes', 'flavor-chat-ia'); ?>
                    </h3>
                    <?php if (!empty($documentos_recientes)): ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Titulo', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Descargas', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documentos_recientes as $documento): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=documentos-listado&action=edit&id=' . $documento->id)); ?>">
                                                <?php echo esc_html($documento->titulo); ?>
                                            </a>
                                        </td>
                                        <td><?php echo esc_html($tipos_documento[$documento->tipo] ?? $documento->tipo); ?></td>
                                        <td>
                                            <?php
                                            $estado_class = [
                                                'publicado' => 'background: #d4edda; color: #155724;',
                                                'borrador'  => 'background: #e2e3e5; color: #383d41;',
                                                'revision'  => 'background: #fff3cd; color: #856404;',
                                                'archivado' => 'background: #f8d7da; color: #721c24;',
                                            ];
                                            $estilo_estado = $estado_class[$documento->estado] ?? '';
                                            ?>
                                            <span style="padding: 3px 8px; border-radius: 3px; font-size: 11px; <?php echo esc_attr($estilo_estado); ?>">
                                                <?php echo esc_html(ucfirst($documento->estado)); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($documento->descargas); ?></td>
                                        <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($documento->created_at))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: #646970;"><?php esc_html_e('No hay documentos registrados.', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Panel lateral -->
                <div>
                    <!-- Categorias -->
                    <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                        <h3 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
                            <span class="dashicons dashicons-category"></span>
                            <?php esc_html_e('Categorias', 'flavor-chat-ia'); ?>
                        </h3>
                        <?php if (!empty($categorias_con_conteo)): ?>
                            <ul style="margin: 0; padding: 0; list-style: none;">
                                <?php foreach ($categorias_con_conteo as $categoria): ?>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                                        <span>
                                            <?php if ($categoria->icono): ?>
                                                <span class="<?php echo esc_attr($categoria->icono); ?>" style="color: <?php echo esc_attr($categoria->color ?: '#2271b1'); ?>;"></span>
                                            <?php endif; ?>
                                            <?php echo esc_html($categoria->nombre); ?>
                                        </span>
                                        <span style="background: #f0f0f1; padding: 2px 8px; border-radius: 10px; font-size: 12px;">
                                            <?php echo esc_html($categoria->total_documentos); ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p style="color: #646970;"><?php esc_html_e('No hay categorias.', 'flavor-chat-ia'); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Documentos populares -->
                    <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php esc_html_e('Mas Descargados', 'flavor-chat-ia'); ?>
                        </h3>
                        <?php if (!empty($documentos_populares)): ?>
                            <ul style="margin: 0; padding: 0; list-style: none;">
                                <?php foreach ($documentos_populares as $indice => $documento): ?>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span style="background: #2271b1; color: #fff; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600;">
                                                <?php echo esc_html($indice + 1); ?>
                                            </span>
                                            <div style="flex: 1;">
                                                <div style="font-weight: 500;"><?php echo esc_html($documento->titulo); ?></div>
                                                <div style="font-size: 12px; color: #646970;">
                                                    <?php echo esc_html($documento->descargas); ?> <?php esc_html_e('descargas', 'flavor-chat-ia'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p style="color: #646970;"><?php esc_html_e('No hay descargas registradas.', 'flavor-chat-ia'); ?></p>
                        <?php endif; ?>
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
        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            $this->render_admin_dashboard();
        }
    }

    /**
     * Renderiza el listado de documentos
     */
    public function render_admin_listado() {
        global $wpdb;
        $tabla_documentos = $wpdb->prefix . 'flavor_documentacion_legal';
        $tabla_categorias = $wpdb->prefix . 'flavor_documentacion_legal_categorias';

        // Procesar acciones
        $this->procesar_acciones_listado();

        // Filtros
        $filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
        $filtro_categoria = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
        $filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        $busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        // Paginacion
        $por_pagina = 20;
        $pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($pagina_actual - 1) * $por_pagina;

        // Construir consulta
        $condiciones_where = ["1=1"];
        $parametros_consulta = [];

        if ($filtro_tipo) {
            $condiciones_where[] = "tipo = %s";
            $parametros_consulta[] = $filtro_tipo;
        }
        if ($filtro_categoria) {
            $condiciones_where[] = "categoria = %s";
            $parametros_consulta[] = $filtro_categoria;
        }
        if ($filtro_estado) {
            $condiciones_where[] = "estado = %s";
            $parametros_consulta[] = $filtro_estado;
        }
        if ($busqueda) {
            $condiciones_where[] = "(titulo LIKE %s OR descripcion LIKE %s)";
            $termino_busqueda = '%' . $wpdb->esc_like($busqueda) . '%';
            $parametros_consulta[] = $termino_busqueda;
            $parametros_consulta[] = $termino_busqueda;
        }

        $clausula_where = implode(' AND ', $condiciones_where);

        // Contar total
        $total_documentos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_documentos} WHERE {$clausula_where}",
            $parametros_consulta
        ));

        // Obtener documentos
        $parametros_consulta[] = $por_pagina;
        $parametros_consulta[] = $offset;

        $documentos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla_documentos} WHERE {$clausula_where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $parametros_consulta
        ));

        // Obtener categorias para filtro
        $categorias = $wpdb->get_results("SELECT slug, nombre FROM {$tabla_categorias} ORDER BY nombre");

        // Tipos de documento
        $tipos_documento = $this->get_tipos_documento();

        // Estados
        $estados_disponibles = [
            'publicado' => __('Publicado', 'flavor-chat-ia'),
            'borrador'  => __('Borrador', 'flavor-chat-ia'),
            'revision'  => __('En revision', 'flavor-chat-ia'),
            'archivado' => __('Archivado', 'flavor-chat-ia'),
        ];

        $total_paginas = ceil($total_documentos / $por_pagina);

        ?>
        <div class="wrap">
            <?php $this->render_page_header(__('Listado de Documentos', 'flavor-chat-ia'), [
                ['label' => __('Nuevo Documento', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=documentos-listado&action=new'), 'class' => 'button-primary'],
                ['label' => __('Exportar', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=documentos-listado&action=export'), 'class' => 'button'],
            ]); ?>

            <!-- Filtros -->
            <div class="tablenav top">
                <form method="get" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="hidden" name="page" value="documentos-listado">

                    <input type="search" name="s" value="<?php echo esc_attr($busqueda); ?>" placeholder="<?php esc_attr_e('Buscar...', 'flavor-chat-ia'); ?>" style="width: 200px;">

                    <select name="tipo">
                        <option value=""><?php esc_html_e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($tipos_documento as $slug_tipo => $nombre_tipo): ?>
                            <option value="<?php echo esc_attr($slug_tipo); ?>" <?php selected($filtro_tipo, $slug_tipo); ?>>
                                <?php echo esc_html($nombre_tipo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="categoria">
                        <option value=""><?php esc_html_e('Todas las categorias', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo esc_attr($categoria->slug); ?>" <?php selected($filtro_categoria, $categoria->slug); ?>>
                                <?php echo esc_html($categoria->nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="estado">
                        <option value=""><?php esc_html_e('Todos los estados', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($estados_disponibles as $slug_estado => $nombre_estado): ?>
                            <option value="<?php echo esc_attr($slug_estado); ?>" <?php selected($filtro_estado, $slug_estado); ?>>
                                <?php echo esc_html($nombre_estado); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="button"><?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?></button>

                    <?php if ($filtro_tipo || $filtro_categoria || $filtro_estado || $busqueda): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=documentos-listado')); ?>" class="button">
                            <?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </form>

                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(
                            esc_html(_n('%s documento', '%s documentos', $total_documentos, 'flavor-chat-ia')),
                            number_format_i18n($total_documentos)
                        ); ?>
                    </span>
                </div>
            </div>

            <!-- Tabla de documentos -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </td>
                        <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e('Titulo', 'flavor-chat-ia'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Categoria', 'flavor-chat-ia'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Descargas', 'flavor-chat-ia'); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($documentos)): ?>
                        <?php foreach ($documentos as $documento): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="documento[]" value="<?php echo esc_attr($documento->id); ?>">
                                </th>
                                <td class="column-title column-primary">
                                    <strong>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=documentos-listado&action=edit&id=' . $documento->id)); ?>">
                                            <?php echo esc_html($documento->titulo); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=documentos-listado&action=edit&id=' . $documento->id)); ?>">
                                                <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                                            </a> |
                                        </span>
                                        <span class="view">
                                            <a href="<?php echo esc_url($documento->archivo_adjunto ?: '#'); ?>" target="_blank">
                                                <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                                            </a> |
                                        </span>
                                        <span class="trash">
                                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=documentos-listado&action=delete&id=' . $documento->id), 'delete_documento_' . $documento->id)); ?>" onclick="return confirm('<?php esc_attr_e('Estas seguro de eliminar este documento?', 'flavor-chat-ia'); ?>');">
                                                <?php esc_html_e('Eliminar', 'flavor-chat-ia'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo esc_html($tipos_documento[$documento->tipo] ?? $documento->tipo); ?></td>
                                <td><?php echo esc_html($documento->categoria ?: '-'); ?></td>
                                <td>
                                    <?php
                                    $colores_estado = [
                                        'publicado' => '#00a32a',
                                        'borrador'  => '#646970',
                                        'revision'  => '#dba617',
                                        'archivado' => '#d63638',
                                    ];
                                    $color_estado = $colores_estado[$documento->estado] ?? '#646970';
                                    ?>
                                    <span style="color: <?php echo esc_attr($color_estado); ?>; font-weight: 500;">
                                        <?php echo esc_html(ucfirst($documento->estado)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($documento->descargas); ?></td>
                                <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($documento->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">
                                <?php esc_html_e('No se encontraron documentos.', 'flavor-chat-ia'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Paginacion -->
            <?php if ($total_paginas > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        $enlaces_paginacion = paginate_links([
                            'base'      => add_query_arg('paged', '%#%'),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_paginas,
                            'current'   => $pagina_actual,
                        ]);
                        echo wp_kses_post($enlaces_paginacion);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Procesa acciones del listado (eliminar, etc.)
     */
    private function procesar_acciones_listado() {
        if (!isset($_GET['action'])) {
            return;
        }

        $accion = sanitize_text_field($_GET['action']);

        if ($accion === 'delete' && isset($_GET['id'])) {
            $documento_id = intval($_GET['id']);

            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_documento_' . $documento_id)) {
                wp_die(__('Accion no autorizada.', 'flavor-chat-ia'));
            }

            global $wpdb;
            $tabla_documentos = $wpdb->prefix . 'flavor_documentacion_legal';

            $resultado = $wpdb->delete($tabla_documentos, ['id' => $documento_id], ['%d']);

            if ($resultado) {
                add_settings_error(
                    'flavor_documentacion_legal',
                    'documento_eliminado',
                    __('Documento eliminado correctamente.', 'flavor-chat-ia'),
                    'success'
                );
            }

            wp_safe_redirect(admin_url('admin.php?page=documentos-listado'));
            exit;
        }
    }

    /**
     * Renderiza la gestion de categorias
     */
    public function render_admin_categorias() {
        global $wpdb;
        $tabla_categorias = $wpdb->prefix . 'flavor_documentacion_legal_categorias';

        // Procesar formulario
        $this->procesar_formulario_categorias();

        // Obtener categorias
        $categorias = $wpdb->get_results(
            "SELECT * FROM {$tabla_categorias} ORDER BY orden ASC, nombre ASC"
        );

        // Categoria a editar
        $categoria_editar = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $categoria_editar = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_categorias} WHERE id = %d",
                intval($_GET['id'])
            ));
        }

        ?>
        <div class="wrap">
            <?php $this->render_page_header(__('Categorias de Documentos', 'flavor-chat-ia')); ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <!-- Formulario -->
                <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0;">
                        <?php echo $categoria_editar ? esc_html__('Editar Categoria', 'flavor-chat-ia') : esc_html__('Nueva Categoria', 'flavor-chat-ia'); ?>
                    </h3>

                    <form method="post">
                        <?php wp_nonce_field('flavor_categoria_action', 'flavor_categoria_nonce'); ?>
                        <?php if ($categoria_editar): ?>
                            <input type="hidden" name="categoria_id" value="<?php echo esc_attr($categoria_editar->id); ?>">
                        <?php endif; ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="nombre"><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?> *</label></th>
                                <td>
                                    <input type="text" name="nombre" id="nombre" class="regular-text" required
                                           value="<?php echo esc_attr($categoria_editar->nombre ?? ''); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="slug"><?php esc_html_e('Slug', 'flavor-chat-ia'); ?></label></th>
                                <td>
                                    <input type="text" name="slug" id="slug" class="regular-text"
                                           value="<?php echo esc_attr($categoria_editar->slug ?? ''); ?>">
                                    <p class="description"><?php esc_html_e('Dejar vacio para generar automaticamente.', 'flavor-chat-ia'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="descripcion"><?php esc_html_e('Descripcion', 'flavor-chat-ia'); ?></label></th>
                                <td>
                                    <textarea name="descripcion" id="descripcion" rows="3" class="large-text"><?php echo esc_textarea($categoria_editar->descripcion ?? ''); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="icono"><?php esc_html_e('Icono', 'flavor-chat-ia'); ?></label></th>
                                <td>
                                    <input type="text" name="icono" id="icono" class="regular-text"
                                           value="<?php echo esc_attr($categoria_editar->icono ?? ''); ?>"
                                           placeholder="dashicons-admin-generic">
                                    <p class="description"><?php esc_html_e('Clase de dashicon (ej: dashicons-media-document)', 'flavor-chat-ia'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="color"><?php esc_html_e('Color', 'flavor-chat-ia'); ?></label></th>
                                <td>
                                    <input type="color" name="color" id="color"
                                           value="<?php echo esc_attr($categoria_editar->color ?? '#2271b1'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="orden"><?php esc_html_e('Orden', 'flavor-chat-ia'); ?></label></th>
                                <td>
                                    <input type="number" name="orden" id="orden" class="small-text" min="0"
                                           value="<?php echo esc_attr($categoria_editar->orden ?? 0); ?>">
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" name="guardar_categoria" class="button button-primary">
                                <?php echo $categoria_editar ? esc_html__('Actualizar Categoria', 'flavor-chat-ia') : esc_html__('Crear Categoria', 'flavor-chat-ia'); ?>
                            </button>
                            <?php if ($categoria_editar): ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=documentos-categorias')); ?>" class="button">
                                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                                </a>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>

                <!-- Lista de categorias -->
                <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0;"><?php esc_html_e('Categorias Existentes', 'flavor-chat-ia'); ?></h3>

                    <?php if (!empty($categorias)): ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Slug', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Orden', 'flavor-chat-ia'); ?></th>
                                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categorias as $categoria): ?>
                                    <tr>
                                        <td>
                                            <?php if ($categoria->icono): ?>
                                                <span class="<?php echo esc_attr($categoria->icono); ?>" style="color: <?php echo esc_attr($categoria->color ?: '#2271b1'); ?>; margin-right: 5px;"></span>
                                            <?php endif; ?>
                                            <?php echo esc_html($categoria->nombre); ?>
                                        </td>
                                        <td><code><?php echo esc_html($categoria->slug); ?></code></td>
                                        <td><?php echo esc_html($categoria->orden); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=documentos-categorias&action=edit&id=' . $categoria->id)); ?>" class="button button-small">
                                                <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                                            </a>
                                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=documentos-categorias&action=delete&id=' . $categoria->id), 'delete_categoria_' . $categoria->id)); ?>"
                                               class="button button-small button-link-delete"
                                               onclick="return confirm('<?php esc_attr_e('Eliminar esta categoria?', 'flavor-chat-ia'); ?>');">
                                                <?php esc_html_e('Eliminar', 'flavor-chat-ia'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: #646970;"><?php esc_html_e('No hay categorias creadas.', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Procesa el formulario de categorias
     */
    private function procesar_formulario_categorias() {
        // Guardar categoria
        if (isset($_POST['guardar_categoria']) && wp_verify_nonce($_POST['flavor_categoria_nonce'] ?? '', 'flavor_categoria_action')) {
            global $wpdb;
            $tabla_categorias = $wpdb->prefix . 'flavor_documentacion_legal_categorias';

            $nombre = sanitize_text_field($_POST['nombre'] ?? '');
            $slug = sanitize_title($_POST['slug'] ?? $nombre);
            $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
            $icono = sanitize_text_field($_POST['icono'] ?? '');
            $color = sanitize_hex_color($_POST['color'] ?? '');
            $orden = intval($_POST['orden'] ?? 0);

            $datos_categoria = [
                'nombre'      => $nombre,
                'slug'        => $slug,
                'descripcion' => $descripcion,
                'icono'       => $icono,
                'color'       => $color,
                'orden'       => $orden,
            ];

            if (isset($_POST['categoria_id']) && $_POST['categoria_id']) {
                // Actualizar
                $wpdb->update($tabla_categorias, $datos_categoria, ['id' => intval($_POST['categoria_id'])]);
                add_settings_error('flavor_categorias', 'actualizada', __('Categoria actualizada.', 'flavor-chat-ia'), 'success');
            } else {
                // Crear
                $wpdb->insert($tabla_categorias, $datos_categoria);
                add_settings_error('flavor_categorias', 'creada', __('Categoria creada.', 'flavor-chat-ia'), 'success');
            }

            wp_safe_redirect(admin_url('admin.php?page=documentos-categorias'));
            exit;
        }

        // Eliminar categoria
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_categoria_' . intval($_GET['id']))) {
                wp_die(__('Accion no autorizada.', 'flavor-chat-ia'));
            }

            global $wpdb;
            $tabla_categorias = $wpdb->prefix . 'flavor_documentacion_legal_categorias';

            $wpdb->delete($tabla_categorias, ['id' => intval($_GET['id'])]);

            wp_safe_redirect(admin_url('admin.php?page=documentos-categorias'));
            exit;
        }
    }

    /**
     * Renderiza la pagina de configuracion
     */
    public function render_admin_config() {
        // Guardar configuracion
        if (isset($_POST['guardar_config']) && wp_verify_nonce($_POST['flavor_config_nonce'] ?? '', 'flavor_config_action')) {
            $configuracion = [
                'requiere_verificacion'    => isset($_POST['requiere_verificacion']),
                'permitir_comentarios'     => isset($_POST['permitir_comentarios']),
                'permitir_descargas'       => isset($_POST['permitir_descargas']),
                'mostrar_visitas'          => isset($_POST['mostrar_visitas']),
                'tipos_archivo_permitidos' => array_map('sanitize_text_field', explode(',', $_POST['tipos_archivo_permitidos'] ?? '')),
                'tamano_maximo_archivo'    => intval($_POST['tamano_maximo_archivo'] ?? 10),
                'roles_pueden_subir'       => array_map('sanitize_text_field', $_POST['roles_pueden_subir'] ?? ['administrator']),
            ];

            update_option('flavor_documentacion_legal_config', $configuracion);
            add_settings_error('flavor_config', 'guardada', __('Configuracion guardada.', 'flavor-chat-ia'), 'success');
        }

        // Obtener configuracion actual
        $configuracion_actual = get_option('flavor_documentacion_legal_config', $this->get_default_settings());

        // Roles de WordPress
        $roles_wordpress = wp_roles()->get_names();

        ?>
        <div class="wrap">
            <?php $this->render_page_header(__('Configuracion - Documentacion Legal', 'flavor-chat-ia')); ?>

            <?php settings_errors('flavor_config'); ?>

            <form method="post">
                <?php wp_nonce_field('flavor_config_action', 'flavor_config_nonce'); ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- General -->
                    <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0;">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php esc_html_e('Configuracion General', 'flavor-chat-ia'); ?>
                        </h3>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Verificacion', 'flavor-chat-ia'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="requiere_verificacion" value="1"
                                               <?php checked($configuracion_actual['requiere_verificacion'] ?? true); ?>>
                                        <?php esc_html_e('Los documentos requieren verificacion antes de publicarse', 'flavor-chat-ia'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Comentarios', 'flavor-chat-ia'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="permitir_comentarios" value="1"
                                               <?php checked($configuracion_actual['permitir_comentarios'] ?? true); ?>>
                                        <?php esc_html_e('Permitir comentarios en documentos', 'flavor-chat-ia'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Descargas', 'flavor-chat-ia'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="permitir_descargas" value="1"
                                               <?php checked($configuracion_actual['permitir_descargas'] ?? true); ?>>
                                        <?php esc_html_e('Permitir descargas de documentos', 'flavor-chat-ia'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Estadisticas', 'flavor-chat-ia'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="mostrar_visitas" value="1"
                                               <?php checked($configuracion_actual['mostrar_visitas'] ?? true); ?>>
                                        <?php esc_html_e('Mostrar contador de visitas', 'flavor-chat-ia'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Archivos -->
                    <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0;">
                            <span class="dashicons dashicons-media-document"></span>
                            <?php esc_html_e('Configuracion de Archivos', 'flavor-chat-ia'); ?>
                        </h3>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="tipos_archivo_permitidos"><?php esc_html_e('Tipos permitidos', 'flavor-chat-ia'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="tipos_archivo_permitidos" id="tipos_archivo_permitidos" class="regular-text"
                                           value="<?php echo esc_attr(implode(',', $configuracion_actual['tipos_archivo_permitidos'] ?? ['pdf', 'doc', 'docx'])); ?>">
                                    <p class="description"><?php esc_html_e('Extensiones separadas por coma (ej: pdf,doc,docx,odt)', 'flavor-chat-ia'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="tamano_maximo_archivo"><?php esc_html_e('Tamano maximo (MB)', 'flavor-chat-ia'); ?></label>
                                </th>
                                <td>
                                    <input type="number" name="tamano_maximo_archivo" id="tamano_maximo_archivo" class="small-text" min="1" max="100"
                                           value="<?php echo esc_attr($configuracion_actual['tamano_maximo_archivo'] ?? 10); ?>">
                                    <p class="description"><?php esc_html_e('Tamano maximo de archivo en megabytes', 'flavor-chat-ia'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Permisos -->
                    <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0;">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php esc_html_e('Permisos de Subida', 'flavor-chat-ia'); ?>
                        </h3>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Roles que pueden subir documentos', 'flavor-chat-ia'); ?></th>
                                <td>
                                    <?php
                                    $roles_pueden_subir = $configuracion_actual['roles_pueden_subir'] ?? ['administrator'];
                                    foreach ($roles_wordpress as $slug_rol => $nombre_rol):
                                        ?>
                                        <label style="display: block; margin-bottom: 5px;">
                                            <input type="checkbox" name="roles_pueden_subir[]" value="<?php echo esc_attr($slug_rol); ?>"
                                                   <?php checked(in_array($slug_rol, $roles_pueden_subir)); ?>>
                                            <?php echo esc_html($nombre_rol); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Informacion -->
                    <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0;">
                            <span class="dashicons dashicons-info-outline"></span>
                            <?php esc_html_e('Informacion del Modulo', 'flavor-chat-ia'); ?>
                        </h3>

                        <?php
                        $estadisticas = $this->get_estadisticas_admin();
                        ?>
                        <table class="widefat">
                            <tr>
                                <td><strong><?php esc_html_e('Total documentos', 'flavor-chat-ia'); ?></strong></td>
                                <td><?php echo esc_html($estadisticas['total_documentos']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('Documentos publicados', 'flavor-chat-ia'); ?></strong></td>
                                <td><?php echo esc_html($estadisticas['documentos_publicados']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('Pendientes de revision', 'flavor-chat-ia'); ?></strong></td>
                                <td><?php echo esc_html($estadisticas['documentos_pendientes']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('Total descargas', 'flavor-chat-ia'); ?></strong></td>
                                <td><?php echo esc_html(number_format($estadisticas['total_descargas'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('Categorias', 'flavor-chat-ia'); ?></strong></td>
                                <td><?php echo esc_html($estadisticas['total_categorias']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <p class="submit" style="margin-top: 20px;">
                    <button type="submit" name="guardar_config" class="button button-primary button-large">
                        <?php esc_html_e('Guardar Configuracion', 'flavor-chat-ia'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Widget para el dashboard de administracion
     */
    public function render_dashboard_widget_admin() {
        $estadisticas = $this->get_estadisticas_admin();
        ?>
        <div class="flavor-dashboard-widget">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 15px;">
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #2271b1;"><?php echo esc_html($estadisticas['total_documentos']); ?></div>
                    <div style="font-size: 12px; color: #646970;"><?php esc_html_e('Documentos', 'flavor-chat-ia'); ?></div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #00a32a;"><?php echo esc_html(number_format($estadisticas['total_descargas'])); ?></div>
                    <div style="font-size: 12px; color: #646970;"><?php esc_html_e('Descargas', 'flavor-chat-ia'); ?></div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #dba617;"><?php echo esc_html($estadisticas['documentos_pendientes']); ?></div>
                    <div style="font-size: 12px; color: #646970;"><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></div>
                </div>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=documentos-dashboard')); ?>" class="button button-primary" style="width: 100%; text-align: center;">
                <?php esc_html_e('Ver Dashboard Completo', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'documentacion-legal',
            'title'    => __('Documentación Legal', 'flavor-chat-ia'),
            'subtitle' => __('Repositorio de documentos jurídicos y modelos', 'flavor-chat-ia'),
            'icon'     => '⚖️',
            'color'    => 'secondary', // Usa variable CSS --flavor-secondary del tema

            'database' => [
                'table'       => 'flavor_documentacion_legal',
                'primary_key' => 'id',
            ],

            'fields' => [
                'titulo'      => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'required' => true],
                'tipo'        => ['type' => 'select', 'label' => __('Tipo', 'flavor-chat-ia'), 'required' => true],
                'categoria'   => ['type' => 'select', 'label' => __('Categoría', 'flavor-chat-ia')],
                'ambito'      => ['type' => 'select', 'label' => __('Ámbito', 'flavor-chat-ia')],
                'fecha_publicacion' => ['type' => 'date', 'label' => __('Fecha publicación', 'flavor-chat-ia')],
                'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
                'archivo'     => ['type' => 'file', 'label' => __('Documento', 'flavor-chat-ia')],
                'url_oficial' => ['type' => 'url', 'label' => __('URL oficial', 'flavor-chat-ia')],
            ],

            'estados' => [
                'borrador'   => ['label' => __('Borrador', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '📝'],
                'pendiente'  => ['label' => __('Pendiente', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '⏳'],
                'publicado'  => ['label' => __('Publicado', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '✅'],
                'obsoleto'   => ['label' => __('Obsoleto', 'flavor-chat-ia'), 'color' => 'red', 'icon' => '⚠️'],
            ],

            'stats' => [
                [
                    'key'   => 'total_documentos',
                    'label' => __('Documentos', 'flavor-chat-ia'),
                    'icon'  => '📄',
                    'color' => 'slate',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_documentacion_legal WHERE estado = 'publicado'",
                ],
                [
                    'key'   => 'leyes',
                    'label' => __('Leyes', 'flavor-chat-ia'),
                    'icon'  => '📜',
                    'color' => 'blue',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_documentacion_legal WHERE tipo = 'ley' AND estado = 'publicado'",
                ],
                [
                    'key'   => 'modelos',
                    'label' => __('Modelos', 'flavor-chat-ia'),
                    'icon'  => '📋',
                    'color' => 'green',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_documentacion_legal WHERE tipo LIKE 'modelo%' AND estado = 'publicado'",
                ],
                [
                    'key'   => 'sentencias',
                    'label' => __('Sentencias', 'flavor-chat-ia'),
                    'icon'  => '⚖️',
                    'color' => 'purple',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_documentacion_legal WHERE tipo = 'sentencia' AND estado = 'publicado'",
                ],
            ],

            'card' => [
                'layout'      => 'document',
                'icon_field'  => 'tipo',
                'title_field' => 'titulo',
                'meta_fields' => ['tipo', 'ambito', 'fecha_publicacion'],
                'badge_field' => 'tipo',
                'show_download' => true,
            ],

            'tabs' => [
                'listado' => [
                    'label'   => __('Documentos', 'flavor-chat-ia'),
                    'icon'    => '📄',
                    'content' => 'template:documentacion-legal/_listado.php',
                ],
                'leyes' => [
                    'label'   => __('Leyes', 'flavor-chat-ia'),
                    'icon'    => '📜',
                    'content' => 'shortcode:documentacion_legal_leyes',
                ],
                'modelos' => [
                    'label'   => __('Modelos', 'flavor-chat-ia'),
                    'icon'    => '📋',
                    'content' => 'shortcode:documentacion_legal_modelos',
                ],
                'sentencias' => [
                    'label'   => __('Sentencias', 'flavor-chat-ia'),
                    'icon'    => '⚖️',
                    'content' => 'shortcode:documentacion_legal_sentencias',
                ],
                'favoritos' => [
                    'label'   => __('Favoritos', 'flavor-chat-ia'),
                    'icon'    => '⭐',
                    'content' => 'shortcode:documentacion_legal_favoritos',
                ],
            ],

            'archive' => [
                'columns'       => 2,
                'per_page'      => 20,
                'order_by'      => 'fecha_publicacion',
                'order'         => 'DESC',
                'filterable_by' => ['tipo', 'categoria', 'ambito'],
            ],

            'dashboard' => [
                'widgets' => [
                    'documentos_recientes' => ['type' => 'list', 'title' => __('Documentos recientes', 'flavor-chat-ia')],
                    'mis_favoritos'        => ['type' => 'list', 'title' => __('Mis favoritos', 'flavor-chat-ia')],
                ],
                'actions' => [
                    'subir_documento' => [
                        'label' => __('Subir documento', 'flavor-chat-ia'),
                        'icon'  => '📤',
                        'modal' => 'documentacion-legal-subir',
                    ],
                ],
            ],

            'features' => [
                'has_archive'    => true,
                'has_single'     => true,
                'has_dashboard'  => true,
                'has_search'     => true,
                'has_categories' => true,
                'has_favorites'  => true,
                'has_downloads'  => true,
                'has_versioning' => true,
            ],
        ];
    }

    /**
     * Registra las paginas de administracion del modulo
     */
    public function registrar_paginas_admin() {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;


        $capability = 'manage_options';

        add_submenu_page(
            null,
            __('Categorias de Documentos', 'flavor-chat-ia'),
            __('Categorias', 'flavor-chat-ia'),
            $capability,
            'documentos-categorias',
            [$this, 'render_documentos_categorias']
        );

        add_submenu_page(
            null,
            __('Configuracion Documentacion Legal', 'flavor-chat-ia'),
            __('Configuracion', 'flavor-chat-ia'),
            $capability,
            'documentos-config',
            [$this, 'render_documentos_config']
        );

        add_submenu_page(
            null,
            __('Estadisticas Documentacion Legal', 'flavor-chat-ia'),
            __('Estadisticas', 'flavor-chat-ia'),
            $capability,
            'documentos-estadisticas',
            [$this, 'render_documentos_estadisticas']
        );

        add_submenu_page(
            null,
            __('Listado de Documentos', 'flavor-chat-ia'),
            __('Documentos', 'flavor-chat-ia'),
            $capability,
            'documentos-listado',
            [$this, 'render_documentos_listado']
        );

        add_submenu_page(
            null,
            __('Modelos de Documentos', 'flavor-chat-ia'),
            __('Modelos', 'flavor-chat-ia'),
            $capability,
            'documentos-modelos',
            [$this, 'render_documentos_modelos']
        );

        add_submenu_page(
            null,
            __('Nuevo Documento', 'flavor-chat-ia'),
            __('Nuevo', 'flavor-chat-ia'),
            $capability,
            'documentos-nuevo',
            [$this, 'render_documentos_nuevo']
        );
    }

    /**
     * Render: Categorias de documentos
     */
    public function render_documentos_categorias() {
        $vista = dirname(__FILE__) . '/views/categorias.php';
        if (file_exists($vista)) {
            include $vista;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Categorias de Documentos', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Render: Configuracion
     */
    public function render_documentos_config() {
        $vista = dirname(__FILE__) . '/views/config.php';
        if (file_exists($vista)) {
            include $vista;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Configuracion Documentacion Legal', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Render: Estadisticas
     */
    public function render_documentos_estadisticas() {
        $vista = dirname(__FILE__) . '/views/estadisticas.php';
        if (file_exists($vista)) {
            include $vista;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Estadisticas Documentacion Legal', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Render: Listado de documentos
     */
    public function render_documentos_listado() {
        $vista = dirname(__FILE__) . '/views/listado.php';
        if (file_exists($vista)) {
            include $vista;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Listado de Documentos', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Render: Modelos de documentos
     */
    public function render_documentos_modelos() {
        $vista = dirname(__FILE__) . '/views/modelos.php';
        if (file_exists($vista)) {
            include $vista;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Modelos de Documentos', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Render: Nuevo documento
     */
    public function render_documentos_nuevo() {
        $vista = dirname(__FILE__) . '/views/nuevo.php';
        if (file_exists($vista)) {
            include $vista;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Nuevo Documento', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', 'flavor-chat-ia') . '</p></div>';
        }
    }
}
