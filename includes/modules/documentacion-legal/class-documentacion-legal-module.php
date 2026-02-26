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
    }

    /**
     * Verifica si el modulo puede activarse
     */
    public function can_activate() {
        return Flavor_Chat_Helpers::tabla_existe('flavor_documentacion_legal');
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
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        if (!Flavor_Chat_Helpers::tabla_existe('flavor_documentacion_legal')) {
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
        switch ($accion) {
            case 'buscar_documentos':
                return $this->action_buscar($parametros);
            case 'ver_documento':
                return $this->action_ver_documento($parametros);
            case 'listar_categorias':
                return $this->action_listar_categorias();
            default:
                return ['success' => false, 'error' => 'Accion no reconocida'];
        }
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
}
