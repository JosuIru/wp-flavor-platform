<?php
/**
 * Modulo de Mapa de Actores
 *
 * Directorio de actores del territorio: administraciones, empresas, instituciones.
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del modulo de Mapa de Actores
 */
class Flavor_Chat_Mapa_Actores_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'mapa_actores';
        $this->name = 'Mapa de Actores';
        $this->description = 'Directorio de actores del territorio: administraciones, empresas, instituciones, medios de comunicacion. Con relaciones, posiciones y contactos.';

        parent::__construct();
    }

    /**
     * Verifica si el modulo puede activarse
     */
    public function can_activate() {
        return Flavor_Chat_Helpers::tabla_existe('flavor_mapa_actores');
    }

    /**
     * Mensaje de error si no puede activarse
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return 'Las tablas del modulo Mapa de Actores no estan creadas.';
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
        if (!Flavor_Chat_Helpers::tabla_existe('flavor_mapa_actores')) {
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

        // Tabla principal de actores
        $tabla_actores = $wpdb->prefix . 'flavor_mapa_actores';
        $sql_actores = "CREATE TABLE $tabla_actores (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            nombre_corto varchar(100),
            descripcion text,
            tipo enum('administracion_publica','empresa','institucion','medio_comunicacion','partido_politico','sindicato','ong','colectivo','persona','otro') NOT NULL,
            subtipo varchar(100),
            ambito enum('local','comarcal','provincial','autonomico','estatal','internacional') DEFAULT 'local',
            posicion_general enum('aliado','neutro','opositor','desconocido') DEFAULT 'desconocido',
            nivel_influencia enum('bajo','medio','alto','muy_alto') DEFAULT 'medio',
            logo varchar(255),
            direccion text,
            municipio varchar(100),
            codigo_postal varchar(10),
            telefono varchar(50),
            email varchar(200),
            web varchar(255),
            redes_sociales text,
            responsables text,
            competencias text,
            temas_relacionados text,
            etiquetas text,
            notas_internas text,
            fuentes text,
            verificado tinyint(1) DEFAULT 0,
            activo tinyint(1) DEFAULT 1,
            creador_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY ambito (ambito),
            KEY posicion_general (posicion_general),
            KEY municipio (municipio),
            KEY creador_id (creador_id),
            FULLTEXT KEY busqueda (nombre, descripcion, competencias, temas_relacionados)
        ) $charset_collate;";
        dbDelta($sql_actores);

        // Tabla de relaciones entre actores
        $tabla_relaciones = $wpdb->prefix . 'flavor_mapa_actores_relaciones';
        $sql_relaciones = "CREATE TABLE $tabla_relaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            actor_origen_id bigint(20) unsigned NOT NULL,
            actor_destino_id bigint(20) unsigned NOT NULL,
            tipo_relacion enum('pertenece_a','controla','financia','colabora','compite','influye','depende','otro') NOT NULL,
            descripcion text,
            intensidad enum('debil','moderada','fuerte') DEFAULT 'moderada',
            bidireccional tinyint(1) DEFAULT 0,
            fecha_inicio date,
            fecha_fin date,
            fuente varchar(255),
            verificada tinyint(1) DEFAULT 0,
            creador_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY actor_origen_id (actor_origen_id),
            KEY actor_destino_id (actor_destino_id),
            KEY tipo_relacion (tipo_relacion)
        ) $charset_collate;";
        dbDelta($sql_relaciones);

        // Tabla de interacciones/eventos con actores
        $tabla_interacciones = $wpdb->prefix . 'flavor_mapa_actores_interacciones';
        $sql_interacciones = "CREATE TABLE $tabla_interacciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            actor_id bigint(20) unsigned NOT NULL,
            tipo enum('reunion','comunicacion','denuncia','colaboracion','conflicto','declaracion','otro') NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            fecha date NOT NULL,
            resultado enum('positivo','neutro','negativo') DEFAULT 'neutro',
            documentos text,
            participantes text,
            campania_id bigint(20) unsigned,
            denuncia_id bigint(20) unsigned,
            autor_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY actor_id (actor_id),
            KEY tipo (tipo),
            KEY fecha (fecha),
            KEY campania_id (campania_id)
        ) $charset_collate;";
        dbDelta($sql_interacciones);

        // Tabla de personas clave dentro de actores
        $tabla_personas = $wpdb->prefix . 'flavor_mapa_actores_personas';
        $sql_personas = "CREATE TABLE $tabla_personas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            actor_id bigint(20) unsigned NOT NULL,
            nombre varchar(200) NOT NULL,
            cargo varchar(200),
            departamento varchar(200),
            email varchar(200),
            telefono varchar(50),
            notas text,
            fecha_desde date,
            fecha_hasta date,
            activo tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY actor_id (actor_id),
            KEY activo (activo)
        ) $charset_collate;";
        dbDelta($sql_personas);
    }

    /**
     * Configuracion por defecto
     */
    public function get_default_settings() {
        return [
            'mostrar_mapa' => true,
            'mostrar_grafo_relaciones' => true,
            'permitir_edicion_comunidad' => true,
            'requiere_verificacion' => true,
        ];
    }

    /**
     * Registra los shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('actores_listar', [$this, 'shortcode_listar']);
        add_shortcode('actores_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('actores_crear', [$this, 'shortcode_crear']);
        add_shortcode('actores_mapa', [$this, 'shortcode_mapa']);
        add_shortcode('actores_grafo', [$this, 'shortcode_grafo']);
        add_shortcode('actores_buscar', [$this, 'shortcode_buscar']);
    }

    /**
     * Shortcode para listar actores
     */
    public function shortcode_listar($atts) {
        $atts = shortcode_atts([
            'tipo' => '',
            'ambito' => '',
            'posicion' => '',
            'limite' => 20,
        ], $atts);

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/mapa-actores/views/listado.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para detalle de actor
     */
    public function shortcode_detalle($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);

        $actor_id = $atts['id'] ?: (isset($_GET['actor_id']) ? intval($_GET['actor_id']) : 0);

        if (!$actor_id) {
            return '<p class="flavor-error">Actor no encontrado.</p>';
        }

        $actor = $this->obtener_actor($actor_id);
        if (!$actor) {
            return '<p class="flavor-error">Actor no encontrado.</p>';
        }

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/mapa-actores/views/detalle.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para crear actor
     */
    public function shortcode_crear($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-info">Debes iniciar sesion para agregar actores.</p>';
        }

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/mapa-actores/views/crear.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para mapa geografico
     */
    public function shortcode_mapa($atts) {
        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/mapa-actores/views/mapa.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para grafo de relaciones
     */
    public function shortcode_grafo($atts) {
        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/mapa-actores/views/grafo.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para buscar
     */
    public function shortcode_buscar($atts) {
        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'includes/modules/mapa-actores/views/buscar.php';
        return ob_get_clean();
    }

    /**
     * Registra handlers AJAX
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_actores_crear', [$this, 'ajax_crear']);
        add_action('wp_ajax_actores_actualizar', [$this, 'ajax_actualizar']);
        add_action('wp_ajax_actores_agregar_relacion', [$this, 'ajax_agregar_relacion']);
        add_action('wp_ajax_actores_agregar_interaccion', [$this, 'ajax_agregar_interaccion']);
        add_action('wp_ajax_actores_agregar_persona', [$this, 'ajax_agregar_persona']);

        add_action('wp_ajax_actores_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_nopriv_actores_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_actores_listar', [$this, 'ajax_listar']);
        add_action('wp_ajax_nopriv_actores_listar', [$this, 'ajax_listar']);
        add_action('wp_ajax_actores_grafo_datos', [$this, 'ajax_grafo_datos']);
        add_action('wp_ajax_nopriv_actores_grafo_datos', [$this, 'ajax_grafo_datos']);
    }

    /**
     * AJAX: Crear actor
     */
    public function ajax_crear() {
        check_ajax_referer('flavor_actores_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => 'Debes iniciar sesion.']);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_mapa_actores';

        $datos = [
            'nombre' => sanitize_text_field($_POST['nombre'] ?? ''),
            'nombre_corto' => sanitize_text_field($_POST['nombre_corto'] ?? ''),
            'descripcion' => wp_kses_post($_POST['descripcion'] ?? ''),
            'tipo' => sanitize_text_field($_POST['tipo'] ?? 'otro'),
            'subtipo' => sanitize_text_field($_POST['subtipo'] ?? ''),
            'ambito' => sanitize_text_field($_POST['ambito'] ?? 'local'),
            'posicion_general' => sanitize_text_field($_POST['posicion_general'] ?? 'desconocido'),
            'nivel_influencia' => sanitize_text_field($_POST['nivel_influencia'] ?? 'medio'),
            'direccion' => sanitize_textarea_field($_POST['direccion'] ?? ''),
            'municipio' => sanitize_text_field($_POST['municipio'] ?? ''),
            'telefono' => sanitize_text_field($_POST['telefono'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'web' => esc_url_raw($_POST['web'] ?? ''),
            'competencias' => sanitize_textarea_field($_POST['competencias'] ?? ''),
            'temas_relacionados' => sanitize_text_field($_POST['temas_relacionados'] ?? ''),
            'etiquetas' => sanitize_text_field($_POST['etiquetas'] ?? ''),
            'creador_id' => get_current_user_id(),
        ];

        if (empty($datos['nombre'])) {
            wp_send_json_error(['error' => 'El nombre es obligatorio.']);
        }

        $resultado = $wpdb->insert($tabla, $datos);

        if ($resultado === false) {
            wp_send_json_error(['error' => 'Error al crear el actor.']);
        }

        wp_send_json_success([
            'actor_id' => $wpdb->insert_id,
            'mensaje' => 'Actor creado correctamente.',
        ]);
    }

    /**
     * AJAX: Agregar relacion
     */
    public function ajax_agregar_relacion() {
        check_ajax_referer('flavor_actores_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => 'Debes iniciar sesion.']);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_mapa_actores_relaciones';

        $datos = [
            'actor_origen_id' => intval($_POST['actor_origen_id'] ?? 0),
            'actor_destino_id' => intval($_POST['actor_destino_id'] ?? 0),
            'tipo_relacion' => sanitize_text_field($_POST['tipo_relacion'] ?? 'otro'),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'intensidad' => sanitize_text_field($_POST['intensidad'] ?? 'moderada'),
            'bidireccional' => isset($_POST['bidireccional']) ? 1 : 0,
            'fuente' => sanitize_text_field($_POST['fuente'] ?? ''),
            'creador_id' => get_current_user_id(),
        ];

        if (!$datos['actor_origen_id'] || !$datos['actor_destino_id']) {
            wp_send_json_error(['error' => 'Debes seleccionar ambos actores.']);
        }

        $wpdb->insert($tabla, $datos);

        wp_send_json_success(['mensaje' => 'Relacion agregada.']);
    }

    /**
     * AJAX: Agregar interaccion
     */
    public function ajax_agregar_interaccion() {
        check_ajax_referer('flavor_actores_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => 'Debes iniciar sesion.']);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_mapa_actores_interacciones';

        $datos = [
            'actor_id' => intval($_POST['actor_id'] ?? 0),
            'tipo' => sanitize_text_field($_POST['tipo'] ?? 'otro'),
            'titulo' => sanitize_text_field($_POST['titulo'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'fecha' => sanitize_text_field($_POST['fecha'] ?? date('Y-m-d')),
            'resultado' => sanitize_text_field($_POST['resultado'] ?? 'neutro'),
            'campania_id' => intval($_POST['campania_id'] ?? 0) ?: null,
            'autor_id' => get_current_user_id(),
        ];

        if (!$datos['actor_id'] || empty($datos['titulo'])) {
            wp_send_json_error(['error' => 'Actor y titulo son obligatorios.']);
        }

        $wpdb->insert($tabla, $datos);

        wp_send_json_success(['mensaje' => 'Interaccion registrada.']);
    }

    /**
     * AJAX: Buscar actores
     */
    public function ajax_buscar() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_mapa_actores';

        $busqueda = sanitize_text_field($_POST['q'] ?? $_GET['q'] ?? '');
        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $posicion = sanitize_text_field($_POST['posicion'] ?? '');
        $limite = intval($_POST['limite'] ?? 20);

        $where = "WHERE activo = 1";
        $params = [];

        if ($busqueda) {
            $where .= " AND (nombre LIKE %s OR descripcion LIKE %s OR competencias LIKE %s)";
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($tipo) {
            $where .= " AND tipo = %s";
            $params[] = $tipo;
        }

        if ($posicion) {
            $where .= " AND posicion_general = %s";
            $params[] = $posicion;
        }

        $params[] = $limite;

        $actores = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nombre, nombre_corto, tipo, ambito, posicion_general, nivel_influencia, municipio, logo
             FROM $tabla $where ORDER BY nivel_influencia DESC, nombre ASC LIMIT %d",
            $params
        ));

        wp_send_json_success(['actores' => $actores]);
    }

    /**
     * AJAX: Listar actores
     */
    public function ajax_listar() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_mapa_actores';

        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $ambito = sanitize_text_field($_POST['ambito'] ?? '');
        $limite = intval($_POST['limite'] ?? 20);

        $where = "WHERE activo = 1";
        $params = [];

        if ($tipo) {
            $where .= " AND tipo = %s";
            $params[] = $tipo;
        }
        if ($ambito) {
            $where .= " AND ambito = %s";
            $params[] = $ambito;
        }

        $params[] = $limite;

        $actores = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla $where ORDER BY nivel_influencia DESC, nombre ASC LIMIT %d",
            $params
        ));

        wp_send_json_success(['actores' => $actores]);
    }

    /**
     * AJAX: Datos para grafo de relaciones
     */
    public function ajax_grafo_datos() {
        global $wpdb;
        $tabla_actores = $wpdb->prefix . 'flavor_mapa_actores';
        $tabla_relaciones = $wpdb->prefix . 'flavor_mapa_actores_relaciones';

        // Nodos (actores)
        $actores = $wpdb->get_results(
            "SELECT id, nombre, nombre_corto, tipo, posicion_general, nivel_influencia
             FROM $tabla_actores WHERE activo = 1"
        );

        $nodos = array_map(function($actor) {
            $colores = [
                'aliado' => '#10b981',
                'neutro' => '#6b7280',
                'opositor' => '#ef4444',
                'desconocido' => '#9ca3af',
            ];

            $tamanos = [
                'bajo' => 20,
                'medio' => 30,
                'alto' => 40,
                'muy_alto' => 50,
            ];

            return [
                'id' => $actor->id,
                'label' => $actor->nombre_corto ?: $actor->nombre,
                'group' => $actor->tipo,
                'color' => $colores[$actor->posicion_general] ?? '#9ca3af',
                'size' => $tamanos[$actor->nivel_influencia] ?? 30,
            ];
        }, $actores);

        // Aristas (relaciones)
        $relaciones = $wpdb->get_results(
            "SELECT actor_origen_id, actor_destino_id, tipo_relacion, intensidad, bidireccional
             FROM $tabla_relaciones"
        );

        $aristas = array_map(function($rel) {
            return [
                'from' => $rel->actor_origen_id,
                'to' => $rel->actor_destino_id,
                'label' => $rel->tipo_relacion,
                'arrows' => $rel->bidireccional ? 'to, from' : 'to',
                'width' => $rel->intensidad === 'fuerte' ? 3 : ($rel->intensidad === 'moderada' ? 2 : 1),
            ];
        }, $relaciones);

        wp_send_json_success([
            'nodos' => $nodos,
            'aristas' => $aristas,
        ]);
    }

    /**
     * Obtiene un actor con datos relacionados
     */
    private function obtener_actor($actor_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_mapa_actores';

        $actor = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", $actor_id));

        if (!$actor) return null;

        // Relaciones salientes
        $tabla_relaciones = $wpdb->prefix . 'flavor_mapa_actores_relaciones';
        $actor->relaciones_salientes = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, a.nombre as actor_destino_nombre
             FROM $tabla_relaciones r
             LEFT JOIN $tabla a ON r.actor_destino_id = a.id
             WHERE r.actor_origen_id = %d",
            $actor_id
        ));

        // Relaciones entrantes
        $actor->relaciones_entrantes = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, a.nombre as actor_origen_nombre
             FROM $tabla_relaciones r
             LEFT JOIN $tabla a ON r.actor_origen_id = a.id
             WHERE r.actor_destino_id = %d",
            $actor_id
        ));

        // Interacciones
        $tabla_interacciones = $wpdb->prefix . 'flavor_mapa_actores_interacciones';
        $actor->interacciones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_interacciones WHERE actor_id = %d ORDER BY fecha DESC LIMIT 20",
            $actor_id
        ));

        // Personas clave
        $tabla_personas = $wpdb->prefix . 'flavor_mapa_actores_personas';
        $actor->personas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_personas WHERE actor_id = %d AND activo = 1 ORDER BY cargo",
            $actor_id
        ));

        return $actor;
    }

    /**
     * Registra rutas REST API
     */
    private function register_rest_routes() {
        add_action('rest_api_init', function() {
            register_rest_route('flavor/v1', '/actores', [
                'methods' => 'GET',
                'callback' => [$this, 'api_listar'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('flavor/v1', '/actores/(?P<id>\d+)', [
                'methods' => 'GET',
                'callback' => [$this, 'api_obtener'],
                'permission_callback' => '__return_true',
            ]);

            register_rest_route('flavor/v1', '/actores/grafo', [
                'methods' => 'GET',
                'callback' => [$this, 'api_grafo'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    /**
     * API: Listar
     */
    public function api_listar($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_mapa_actores';

        $tipo = $request->get_param('tipo');
        $limite = intval($request->get_param('limite')) ?: 20;

        $where = "WHERE activo = 1";
        $params = [];

        if ($tipo) {
            $where .= " AND tipo = %s";
            $params[] = $tipo;
        }

        $params[] = $limite;

        $actores = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nombre, tipo, ambito, posicion_general, nivel_influencia, municipio
             FROM $tabla $where ORDER BY nivel_influencia DESC LIMIT %d",
            $params
        ));

        return rest_ensure_response(['actores' => $actores]);
    }

    /**
     * API: Obtener
     */
    public function api_obtener($request) {
        $actor = $this->obtener_actor($request['id']);

        if (!$actor) {
            return new WP_Error('not_found', 'Actor no encontrado', ['status' => 404]);
        }

        return rest_ensure_response(['actor' => $actor]);
    }

    /**
     * API: Grafo
     */
    public function api_grafo($request) {
        ob_start();
        $this->ajax_grafo_datos();
        return rest_ensure_response(json_decode(ob_get_clean(), true));
    }

    /**
     * Carga assets frontend
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_assets()) return;

        wp_enqueue_style(
            'flavor-mapa-actores',
            FLAVOR_CHAT_IA_URL . 'includes/modules/mapa-actores/assets/css/mapa-actores.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-mapa-actores',
            FLAVOR_CHAT_IA_URL . 'includes/modules/mapa-actores/assets/js/mapa-actores.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-mapa-actores', 'flavorActoresConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_actores_nonce'),
        ]);
    }

    /**
     * Determina si cargar assets
     */
    private function should_load_assets() {
        global $post;
        if (!$post) return false;

        $shortcodes = ['actores_listar', 'actores_detalle', 'actores_crear', 'actores_mapa', 'actores_grafo', 'actores_buscar'];

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) return true;
        }

        return strpos($post->post_name, 'actor') !== false;
    }

    /**
     * Obtiene las acciones disponibles para IA
     */
    public function get_actions() {
        return [
            'buscar_actores' => [
                'descripcion' => 'Busca actores del territorio por nombre, tipo o posicion',
                'parametros' => [
                    'q' => ['tipo' => 'string', 'descripcion' => 'Texto de busqueda'],
                    'tipo' => ['tipo' => 'string', 'descripcion' => 'Tipo de actor'],
                    'posicion' => ['tipo' => 'string', 'descripcion' => 'Posicion (aliado, neutro, opositor)'],
                ],
            ],
            'ver_actor' => [
                'descripcion' => 'Muestra el detalle de un actor con sus relaciones e interacciones',
                'parametros' => [
                    'actor_id' => ['tipo' => 'integer', 'descripcion' => 'ID del actor', 'requerido' => true],
                ],
            ],
            'listar_tipos_actor' => [
                'descripcion' => 'Lista los tipos de actor disponibles',
                'parametros' => [],
            ],
        ];
    }

    /**
     * Ejecuta una accion
     */
    public function execute_action($accion, $parametros = []) {
        switch ($accion) {
            case 'buscar_actores':
                return $this->action_buscar($parametros);
            case 'ver_actor':
                return $this->action_ver($parametros);
            case 'listar_tipos_actor':
                return ['success' => true, 'tipos' => $this->get_tipos_actor()];
            default:
                return ['success' => false, 'error' => 'Accion no reconocida'];
        }
    }

    /**
     * Accion: Buscar
     */
    private function action_buscar($params) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_mapa_actores';

        $busqueda = sanitize_text_field($params['q'] ?? '');
        $tipo = sanitize_text_field($params['tipo'] ?? '');

        $where = "WHERE activo = 1";
        $query_params = [];

        if ($busqueda) {
            $where .= " AND nombre LIKE %s";
            $query_params[] = '%' . $wpdb->esc_like($busqueda) . '%';
        }

        if ($tipo) {
            $where .= " AND tipo = %s";
            $query_params[] = $tipo;
        }

        $query_params[] = 10;

        $actores = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nombre, tipo, posicion_general FROM $tabla $where LIMIT %d",
            $query_params
        ));

        return ['success' => true, 'actores' => $actores];
    }

    /**
     * Accion: Ver
     */
    private function action_ver($params) {
        $actor = $this->obtener_actor(intval($params['actor_id'] ?? 0));

        if (!$actor) {
            return ['success' => false, 'error' => 'Actor no encontrado'];
        }

        return ['success' => true, 'actor' => $actor];
    }

    /**
     * Obtiene tipos de actor
     */
    public function get_tipos_actor() {
        return [
            'administracion_publica' => 'Administracion Publica',
            'empresa' => 'Empresa',
            'institucion' => 'Institucion',
            'medio_comunicacion' => 'Medio de Comunicacion',
            'partido_politico' => 'Partido Politico',
            'sindicato' => 'Sindicato',
            'ong' => 'ONG',
            'colectivo' => 'Colectivo',
            'persona' => 'Persona',
            'otro' => 'Otro',
        ];
    }

    /**
     * Renderiza widget dashboard
     */
    public function render_dashboard_widget() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_mapa_actores';

        $estadisticas = [
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE activo = 1"),
            'aliados' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE activo = 1 AND posicion_general = 'aliado'"),
            'opositores' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE activo = 1 AND posicion_general = 'opositor'"),
        ];

        include FLAVOR_CHAT_IA_PATH . 'includes/modules/mapa-actores/views/dashboard.php';
    }

    /**
     * Definiciones para IA
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'actores_buscar',
                'description' => 'Busca actores del territorio (administraciones, empresas, instituciones)',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'q' => ['type' => 'string', 'description' => 'Texto de busqueda'],
                        'tipo' => ['type' => 'string', 'description' => 'Tipo de actor'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Base de conocimiento
     */
    public function get_knowledge_base() {
        return "El modulo de Mapa de Actores permite documentar y visualizar los diferentes actores que influyen en el territorio: administraciones publicas, empresas, instituciones, medios de comunicacion, partidos politicos, sindicatos, ONGs y colectivos. Para cada actor se registra su posicion (aliado, neutro, opositor), nivel de influencia, relaciones con otros actores, personas clave y un historial de interacciones. Se puede visualizar un grafo de relaciones entre actores.";
    }

    /**
     * FAQs
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Que tipos de actores puedo registrar?',
                'respuesta' => 'Administraciones publicas, empresas, instituciones, medios de comunicacion, partidos politicos, sindicatos, ONGs, colectivos y personas.',
            ],
            [
                'pregunta' => 'Como registro una relacion entre actores?',
                'respuesta' => 'En la ficha del actor, puedes agregar relaciones indicando el tipo (pertenece a, controla, financia, colabora, etc.) y la intensidad.',
            ],
        ];
    }
}
