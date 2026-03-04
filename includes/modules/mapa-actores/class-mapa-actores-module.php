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

        // Registrar en el Panel Unificado de Administracion
        $this->registrar_en_panel_unificado();
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

        // Registrar páginas de administración
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);
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

    // =========================================================================
    // PANEL DE ADMINISTRACION UNIFICADO
    // =========================================================================

    /**
     * Configuracion del modulo para el Panel Unificado de Administracion
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'mapa_actores',
            'label' => __('Mapa de Actores', 'flavor-chat-ia'),
            'icon' => 'dashicons-networking',
            'capability' => 'manage_options',
            'categoria' => 'comunidad',
            'paginas' => [
                [
                    'slug' => 'actores-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_pagina_dashboard'],
                ],
                [
                    'slug' => 'actores-listado',
                    'titulo' => __('Listado', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_listado'],
                ],
                [
                    'slug' => 'actores-relaciones',
                    'titulo' => __('Relaciones', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_relaciones'],
                    'badge' => [$this, 'contar_relaciones_pendientes'],
                ],
                [
                    'slug' => 'actores-config',
                    'titulo' => __('Configuracion', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_dashboard_widget'],
            'estadisticas' => [$this, 'get_estadisticas_admin'],
        ];
    }

    /**
     * Obtiene estadisticas para el panel unificado
     *
     * @return array
     */
    public function get_estadisticas_admin() {
        global $wpdb;
        $tabla_actores = $wpdb->prefix . 'flavor_mapa_actores';
        $tabla_relaciones = $wpdb->prefix . 'flavor_mapa_actores_relaciones';
        $is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');

        $total_actores = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_actores WHERE activo = 1");
        $total_relaciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_relaciones");

        return [
            [
                'icon' => 'dashicons-networking',
                'valor' => $total_actores,
                'label' => __('Actores', 'flavor-chat-ia'),
                'color' => 'blue',
                'enlace' => $is_dashboard_viewer ? home_url('/mi-portal/participacion/') : admin_url('admin.php?page=actores-listado'),
            ],
            [
                'icon' => 'dashicons-admin-links',
                'valor' => $total_relaciones,
                'label' => __('Relaciones', 'flavor-chat-ia'),
                'color' => 'purple',
                'enlace' => $is_dashboard_viewer ? home_url('/mi-portal/participacion/') : admin_url('admin.php?page=actores-relaciones'),
            ],
        ];
    }

    /**
     * Contador de relaciones pendientes de verificacion
     *
     * @return int
     */
    public function contar_relaciones_pendientes() {
        global $wpdb;
        $tabla_relaciones = $wpdb->prefix . 'flavor_mapa_actores_relaciones';

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_relaciones WHERE verificada = 0");
    }

    /**
     * Renderiza el dashboard de administracion del modulo
     */
    public function render_admin_dashboard() {
        $is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');
        global $wpdb;
        $tabla_actores = $wpdb->prefix . 'flavor_mapa_actores';
        $tabla_relaciones = $wpdb->prefix . 'flavor_mapa_actores_relaciones';

        // Estadisticas generales
        $estadisticas = [
            'total_actores' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_actores WHERE activo = 1"),
            'aliados' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_actores WHERE activo = 1 AND posicion_general = 'aliado'"),
            'neutros' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_actores WHERE activo = 1 AND posicion_general = 'neutro'"),
            'opositores' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_actores WHERE activo = 1 AND posicion_general = 'opositor'"),
            'total_relaciones' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_relaciones"),
            'relaciones_sin_verificar' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_relaciones WHERE verificada = 0"),
        ];

        // Tipos de actores
        $tipos_actores = $wpdb->get_results(
            "SELECT tipo, COUNT(*) as cantidad
             FROM $tabla_actores
             WHERE activo = 1
             GROUP BY tipo
             ORDER BY cantidad DESC"
        );

        // Actores recientes
        $actores_recientes = $wpdb->get_results(
            "SELECT id, nombre, tipo, posicion_general, created_at
             FROM $tabla_actores
             WHERE activo = 1
             ORDER BY created_at DESC
             LIMIT 10"
        );

        // Actores con mas influencia
        $actores_influyentes = $wpdb->get_results(
            "SELECT id, nombre, tipo, posicion_general, nivel_influencia
             FROM $tabla_actores
             WHERE activo = 1
             ORDER BY FIELD(nivel_influencia, 'muy_alto', 'alto', 'medio', 'bajo')
             LIMIT 5"
        );

        $acciones = $is_dashboard_viewer
            ? [
                [
                    'label' => __('Ver en portal', 'flavor-chat-ia'),
                    'url' => home_url('/mi-portal/participacion/'),
                    'class' => '',
                ],
            ]
            : [
                [
                    'label' => __('Nuevo Actor', 'flavor-chat-ia'),
                    'url' => admin_url('admin.php?page=actores-listado&action=nuevo'),
                    'class' => 'button-primary',
                ],
            ];
        $this->render_page_header(__('Mapa de Actores - Dashboard', 'flavor-chat-ia'), $acciones);
        ?>
        <div class="wrap flavor-admin-dashboard">
            <?php if ($is_dashboard_viewer) : ?>
                <div class="notice notice-info"><p><?php esc_html_e('Vista resumida para gestor de grupos. El alta de actores, relaciones y configuración estratégica sigue reservada a administración.', 'flavor-chat-ia'); ?></p></div>
            <?php endif; ?>
            <?php if (method_exists($this, 'render_admin_module_hub')) : ?>
                <?php $this->render_admin_module_hub([
                    'description' => __('Acceso visible a listado, relaciones, configuración y al bloque principal de análisis del mapa.', 'flavor-chat-ia'),
                    'stats_anchor' => '#mapa-actores-stats',
                    'extra_items' => [
                        [
                            'label' => __('Portal', 'flavor-chat-ia'),
                            'url' => home_url('/mi-portal/participacion/'),
                            'icon' => 'dashicons-external',
                        ],
                    ],
                ]); ?>
            <?php endif; ?>
            <!-- KPIs principales -->
            <div id="mapa-actores-stats" class="flavor-admin-kpis" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-networking" style="font-size: 32px; color: #2271b1; margin-bottom: 10px;"></span>
                    <div style="font-size: 28px; font-weight: 600;"><?php echo esc_html($estadisticas['total_actores']); ?></div>
                    <div style="color: #646970;"><?php _e('Total Actores', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-thumbs-up" style="font-size: 32px; color: #00a32a; margin-bottom: 10px;"></span>
                    <div style="font-size: 28px; font-weight: 600;"><?php echo esc_html($estadisticas['aliados']); ?></div>
                    <div style="color: #646970;"><?php _e('Aliados', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-thumbs-down" style="font-size: 32px; color: #d63638; margin-bottom: 10px;"></span>
                    <div style="font-size: 28px; font-weight: 600;"><?php echo esc_html($estadisticas['opositores']); ?></div>
                    <div style="color: #646970;"><?php _e('Opositores', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-kpi-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span class="dashicons dashicons-admin-links" style="font-size: 32px; color: #8b5cf6; margin-bottom: 10px;"></span>
                    <div style="font-size: 28px; font-weight: 600;"><?php echo esc_html($estadisticas['total_relaciones']); ?></div>
                    <div style="color: #646970;"><?php _e('Relaciones', 'flavor-chat-ia'); ?></div>
                </div>
            </div>

            <div class="flavor-admin-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <!-- Columna principal -->
                <div class="flavor-admin-main">
                    <!-- Tipos de actores -->
                    <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                        <h3 style="margin-top: 0;"><?php _e('Distribucion por Tipo', 'flavor-chat-ia'); ?></h3>
                        <?php if (!empty($tipos_actores)): ?>
                            <table class="widefat striped" style="border: none;">
                                <tbody>
                                    <?php
                                    $tipos_labels = $this->get_tipos_actor();
                                    foreach ($tipos_actores as $tipo_actor):
                                    ?>
                                        <tr>
                                            <td><?php echo esc_html($tipos_labels[$tipo_actor->tipo] ?? $tipo_actor->tipo); ?></td>
                                            <td style="text-align: right; font-weight: 600;"><?php echo esc_html($tipo_actor->cantidad); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color: #646970;"><?php _e('No hay actores registrados.', 'flavor-chat-ia'); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Actores recientes -->
                    <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0;"><?php _e('Actores Recientes', 'flavor-chat-ia'); ?></h3>
                        <?php if (!empty($actores_recientes)): ?>
                            <table class="widefat striped" style="border: none;">
                                <thead>
                                    <tr>
                                        <th><?php _e('Nombre', 'flavor-chat-ia'); ?></th>
                                        <th><?php _e('Tipo', 'flavor-chat-ia'); ?></th>
                                        <th><?php _e('Posicion', 'flavor-chat-ia'); ?></th>
                                        <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $tipos_labels = $this->get_tipos_actor();
                                    $posiciones_labels = [
                                        'aliado' => __('Aliado', 'flavor-chat-ia'),
                                        'neutro' => __('Neutro', 'flavor-chat-ia'),
                                        'opositor' => __('Opositor', 'flavor-chat-ia'),
                                        'desconocido' => __('Desconocido', 'flavor-chat-ia'),
                                    ];
                                    foreach ($actores_recientes as $actor_reciente):
                                    ?>
                                        <tr>
                                            <td>
                                                <?php if ($is_dashboard_viewer) : ?>
                                                    <?php echo esc_html($actor_reciente->nombre); ?>
                                                <?php else : ?>
                                                    <a href="<?php echo esc_url(admin_url('admin.php?page=actores-listado&action=ver&id=' . $actor_reciente->id)); ?>">
                                                        <?php echo esc_html($actor_reciente->nombre); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html($tipos_labels[$actor_reciente->tipo] ?? $actor_reciente->tipo); ?></td>
                                            <td>
                                                <?php
                                                $posicion_color = [
                                                    'aliado' => '#00a32a',
                                                    'neutro' => '#646970',
                                                    'opositor' => '#d63638',
                                                    'desconocido' => '#9ca3af',
                                                ];
                                                $color_actual = $posicion_color[$actor_reciente->posicion_general] ?? '#646970';
                                                ?>
                                                <span style="color: <?php echo esc_attr($color_actual); ?>; font-weight: 500;">
                                                    <?php echo esc_html($posiciones_labels[$actor_reciente->posicion_general] ?? $actor_reciente->posicion_general); ?>
                                                </span>
                                            </td>
                                            <td><?php echo esc_html(date_i18n('d M Y', strtotime($actor_reciente->created_at))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if (!$is_dashboard_viewer) : ?>
                                <p style="margin-bottom: 0; margin-top: 15px;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=actores-listado')); ?>" class="button">
                                        <?php _e('Ver todos los actores', 'flavor-chat-ia'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p style="color: #646970;"><?php _e('No hay actores registrados.', 'flavor-chat-ia'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="flavor-admin-sidebar">
                    <!-- Actores influyentes -->
                    <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                        <h3 style="margin-top: 0;"><?php _e('Actores Clave', 'flavor-chat-ia'); ?></h3>
                        <?php if (!empty($actores_influyentes)): ?>
                            <ul style="margin: 0; padding: 0; list-style: none;">
                                <?php
                                $influencia_icons = [
                                    'muy_alto' => '🔥',
                                    'alto' => '⭐',
                                    'medio' => '●',
                                    'bajo' => '○',
                                ];
                                foreach ($actores_influyentes as $actor_influyente):
                                ?>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                        <span title="<?php echo esc_attr(ucfirst(str_replace('_', ' ', $actor_influyente->nivel_influencia))); ?>">
                                            <?php echo esc_html($influencia_icons[$actor_influyente->nivel_influencia] ?? '●'); ?>
                                        </span>
                                        <?php if ($is_dashboard_viewer) : ?>
                                            <?php echo esc_html($actor_influyente->nombre); ?>
                                        <?php else : ?>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=actores-listado&action=ver&id=' . $actor_influyente->id)); ?>">
                                                <?php echo esc_html($actor_influyente->nombre); ?>
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p style="color: #646970; margin-bottom: 0;"><?php _e('Sin datos.', 'flavor-chat-ia'); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Acciones rapidas -->
                    <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0;"><?php _e('Acciones Rapidas', 'flavor-chat-ia'); ?></h3>
                        <?php if (!$is_dashboard_viewer) : ?>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=actores-listado&action=nuevo')); ?>" class="button button-primary" style="text-align: center;">
                                    <?php _e('Agregar Actor', 'flavor-chat-ia'); ?>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=actores-relaciones')); ?>" class="button" style="text-align: center;">
                                    <?php _e('Gestionar Relaciones', 'flavor-chat-ia'); ?>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=actores-config')); ?>" class="button" style="text-align: center;">
                                    <?php _e('Configuracion', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($estadisticas['relaciones_sin_verificar'] > 0): ?>
                            <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 4px; border-left: 4px solid #ffc107;">
                                <strong><?php _e('Pendiente:', 'flavor-chat-ia'); ?></strong>
                                <?php printf(
                                    _n('%d relacion sin verificar', '%d relaciones sin verificar', $estadisticas['relaciones_sin_verificar'], 'flavor-chat-ia'),
                                    $estadisticas['relaciones_sin_verificar']
                                ); ?>
                            </div>
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
     * Renderiza el listado de actores en administracion
     */
    public function render_admin_listado() {
        global $wpdb;
        $tabla_actores = $wpdb->prefix . 'flavor_mapa_actores';
        $is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');

        // Parametros de filtrado y paginacion
        $pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $por_pagina = 20;
        $offset = ($pagina_actual - 1) * $por_pagina;

        $filtro_tipo = isset($_GET['filtro_tipo']) ? sanitize_text_field($_GET['filtro_tipo']) : '';
        $filtro_posicion = isset($_GET['filtro_posicion']) ? sanitize_text_field($_GET['filtro_posicion']) : '';
        $busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        // Construir consulta
        $where_condiciones = ["activo = 1"];
        $where_parametros = [];

        if (!empty($filtro_tipo)) {
            $where_condiciones[] = "tipo = %s";
            $where_parametros[] = $filtro_tipo;
        }

        if (!empty($filtro_posicion)) {
            $where_condiciones[] = "posicion_general = %s";
            $where_parametros[] = $filtro_posicion;
        }

        if (!empty($busqueda)) {
            $where_condiciones[] = "(nombre LIKE %s OR descripcion LIKE %s)";
            $like_busqueda = '%' . $wpdb->esc_like($busqueda) . '%';
            $where_parametros[] = $like_busqueda;
            $where_parametros[] = $like_busqueda;
        }

        $where_sql = implode(' AND ', $where_condiciones);

        // Contar total
        $total_consulta = "SELECT COUNT(*) FROM $tabla_actores WHERE $where_sql";
        if (!empty($where_parametros)) {
            $total_items = (int) $wpdb->get_var($wpdb->prepare($total_consulta, $where_parametros));
        } else {
            $total_items = (int) $wpdb->get_var($total_consulta);
        }

        $total_paginas = ceil($total_items / $por_pagina);

        // Obtener actores
        $consulta_actores = "SELECT * FROM $tabla_actores WHERE $where_sql ORDER BY nombre ASC LIMIT %d OFFSET %d";
        $parametros_consulta = array_merge($where_parametros, [$por_pagina, $offset]);
        $actores = $wpdb->get_results($wpdb->prepare($consulta_actores, $parametros_consulta));

        $this->render_page_header(
            __('Listado de Actores', 'flavor-chat-ia'),
            $is_dashboard_viewer
                ? [
                    [
                        'label' => __('Ver en portal', 'flavor-chat-ia'),
                        'url' => home_url('/mi-portal/participacion/'),
                        'class' => '',
                    ],
                ]
                : [
                    [
                        'label' => __('Nuevo Actor', 'flavor-chat-ia'),
                        'url' => admin_url('admin.php?page=actores-listado&action=nuevo'),
                        'class' => 'button-primary',
                    ],
                ]
        );
        ?>
        <div class="wrap">
            <?php if ($is_dashboard_viewer) : ?>
                <div class="notice notice-info"><p><?php esc_html_e('Vista de consulta para gestor de grupos. El alta, edición y configuración del mapa de actores siguen reservadas a administración.', 'flavor-chat-ia'); ?></p></div>
            <?php endif; ?>
            <!-- Filtros -->
            <form method="get" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <input type="hidden" name="page" value="actores-listado">

                <input type="search" name="s" value="<?php echo esc_attr($busqueda); ?>"
                       placeholder="<?php esc_attr_e('Buscar actores...', 'flavor-chat-ia'); ?>"
                       style="min-width: 200px;">

                <select name="filtro_tipo">
                    <option value=""><?php _e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                    <?php
                    $tipos_labels = $this->get_tipos_actor();
                    foreach ($tipos_labels as $tipo_valor => $tipo_etiqueta):
                    ?>
                        <option value="<?php echo esc_attr($tipo_valor); ?>" <?php selected($filtro_tipo, $tipo_valor); ?>>
                            <?php echo esc_html($tipo_etiqueta); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="filtro_posicion">
                    <option value=""><?php _e('Todas las posiciones', 'flavor-chat-ia'); ?></option>
                    <option value="aliado" <?php selected($filtro_posicion, 'aliado'); ?>><?php _e('Aliado', 'flavor-chat-ia'); ?></option>
                    <option value="neutro" <?php selected($filtro_posicion, 'neutro'); ?>><?php _e('Neutro', 'flavor-chat-ia'); ?></option>
                    <option value="opositor" <?php selected($filtro_posicion, 'opositor'); ?>><?php _e('Opositor', 'flavor-chat-ia'); ?></option>
                    <option value="desconocido" <?php selected($filtro_posicion, 'desconocido'); ?>><?php _e('Desconocido', 'flavor-chat-ia'); ?></option>
                </select>

                <button type="submit" class="button"><?php _e('Filtrar', 'flavor-chat-ia'); ?></button>

                <?php if ($filtro_tipo || $filtro_posicion || $busqueda): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=actores-listado')); ?>" class="button">
                        <?php _e('Limpiar', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </form>

            <!-- Tabla de actores -->
            <?php if (!empty($actores)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 30%;"><?php _e('Nombre', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Tipo', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Posicion', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Influencia', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Ubicacion', 'flavor-chat-ia'); ?></th>
                            <th style="width: 120px;"><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tipos_labels = $this->get_tipos_actor();
                        $posiciones_colores = [
                            'aliado' => '#00a32a',
                            'neutro' => '#646970',
                            'opositor' => '#d63638',
                            'desconocido' => '#9ca3af',
                        ];
                        $influencia_labels = [
                            'bajo' => __('Bajo', 'flavor-chat-ia'),
                            'medio' => __('Medio', 'flavor-chat-ia'),
                            'alto' => __('Alto', 'flavor-chat-ia'),
                            'muy_alto' => __('Muy Alto', 'flavor-chat-ia'),
                        ];
                        foreach ($actores as $actor):
                        ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?php if ($is_dashboard_viewer) : ?>
                                            <?php echo esc_html($actor->nombre); ?>
                                        <?php else : ?>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=actores-listado&action=ver&id=' . $actor->id)); ?>">
                                                <?php echo esc_html($actor->nombre); ?>
                                            </a>
                                        <?php endif; ?>
                                    </strong>
                                    <?php if ($actor->verificado): ?>
                                        <span title="<?php esc_attr_e('Verificado', 'flavor-chat-ia'); ?>">✓</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($tipos_labels[$actor->tipo] ?? $actor->tipo); ?></td>
                                <td>
                                    <span style="color: <?php echo esc_attr($posiciones_colores[$actor->posicion_general] ?? '#646970'); ?>; font-weight: 500;">
                                        <?php echo esc_html(ucfirst($actor->posicion_general)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($influencia_labels[$actor->nivel_influencia] ?? $actor->nivel_influencia); ?></td>
                                <td><?php echo esc_html($actor->municipio ?: '-'); ?></td>
                                <td>
                                    <?php if ($is_dashboard_viewer) : ?>
                                        <span class="description"><?php esc_html_e('Solo lectura', 'flavor-chat-ia'); ?></span>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=actores-listado&action=editar&id=' . $actor->id)); ?>"
                                           class="button button-small" title="<?php esc_attr_e('Editar', 'flavor-chat-ia'); ?>">
                                            <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                                        </a>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=actores-relaciones&actor_id=' . $actor->id)); ?>"
                                           class="button button-small" title="<?php esc_attr_e('Ver Relaciones', 'flavor-chat-ia'); ?>">
                                            <span class="dashicons dashicons-admin-links" style="vertical-align: middle;"></span>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginacion -->
                <?php if ($total_paginas > 1): ?>
                    <div class="tablenav bottom" style="margin-top: 20px;">
                        <div class="tablenav-pages">
                            <span class="displaying-num">
                                <?php printf(_n('%s elemento', '%s elementos', $total_items, 'flavor-chat-ia'), number_format_i18n($total_items)); ?>
                            </span>
                            <?php
                            $url_paginacion = add_query_arg([
                                'page' => 'actores-listado',
                                'filtro_tipo' => $filtro_tipo,
                                'filtro_posicion' => $filtro_posicion,
                                's' => $busqueda,
                            ], admin_url('admin.php'));

                            echo paginate_links([
                                'base' => $url_paginacion . '&paged=%#%',
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_paginas,
                                'current' => $pagina_actual,
                            ]);
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="notice notice-info" style="margin-top: 20px;">
                    <p>
                        <?php if ($busqueda || $filtro_tipo || $filtro_posicion): ?>
                            <?php _e('No se encontraron actores con los filtros seleccionados.', 'flavor-chat-ia'); ?>
                        <?php else: ?>
                            <?php _e('No hay actores registrados. Comienza agregando el primero.', 'flavor-chat-ia'); ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza la pagina de relaciones
     */
    public function render_admin_relaciones() {
        global $wpdb;
        $tabla_actores = $wpdb->prefix . 'flavor_mapa_actores';
        $tabla_relaciones = $wpdb->prefix . 'flavor_mapa_actores_relaciones';
        $is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');

        // Filtro por actor especifico
        $actor_id_filtro = isset($_GET['actor_id']) ? intval($_GET['actor_id']) : 0;

        // Obtener relaciones
        $consulta_base = "
            SELECT r.*,
                   ao.nombre as actor_origen_nombre,
                   ad.nombre as actor_destino_nombre,
                   ao.tipo as actor_origen_tipo,
                   ad.tipo as actor_destino_tipo
            FROM $tabla_relaciones r
            LEFT JOIN $tabla_actores ao ON r.actor_origen_id = ao.id
            LEFT JOIN $tabla_actores ad ON r.actor_destino_id = ad.id
        ";

        if ($actor_id_filtro) {
            $consulta_base .= " WHERE r.actor_origen_id = %d OR r.actor_destino_id = %d";
            $relaciones = $wpdb->get_results($wpdb->prepare($consulta_base . " ORDER BY r.created_at DESC LIMIT 100", $actor_id_filtro, $actor_id_filtro));
        } else {
            $relaciones = $wpdb->get_results($consulta_base . " ORDER BY r.created_at DESC LIMIT 100");
        }

        // Obtener lista de actores para el selector
        $lista_actores = $wpdb->get_results("SELECT id, nombre FROM $tabla_actores WHERE activo = 1 ORDER BY nombre ASC");

        // Tipos de relacion
        $tipos_relacion = [
            'pertenece_a' => __('Pertenece a', 'flavor-chat-ia'),
            'controla' => __('Controla', 'flavor-chat-ia'),
            'financia' => __('Financia', 'flavor-chat-ia'),
            'colabora' => __('Colabora con', 'flavor-chat-ia'),
            'compite' => __('Compite con', 'flavor-chat-ia'),
            'influye' => __('Influye en', 'flavor-chat-ia'),
            'depende' => __('Depende de', 'flavor-chat-ia'),
            'otro' => __('Otro', 'flavor-chat-ia'),
        ];

        $this->render_page_header(
            __('Relaciones entre Actores', 'flavor-chat-ia'),
            $is_dashboard_viewer
                ? [
                    [
                        'label' => __('Ver en portal', 'flavor-chat-ia'),
                        'url' => home_url('/mi-portal/participacion/'),
                        'class' => '',
                    ],
                ]
                : [
                    [
                        'label' => __('Nueva Relacion', 'flavor-chat-ia'),
                        'url' => '#',
                        'class' => 'button-primary',
                    ],
                ]
        );
        ?>
        <div class="wrap">
            <?php if ($is_dashboard_viewer) : ?>
                <div class="notice notice-info"><p><?php esc_html_e('Vista de consulta para gestor de grupos. Las relaciones pueden revisarse, pero su creación y mantenimiento siguen reservados a administración.', 'flavor-chat-ia'); ?></p></div>
            <?php endif; ?>
            <!-- Filtro por actor -->
            <form method="get" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
                <input type="hidden" name="page" value="actores-relaciones">

                <select name="actor_id" style="min-width: 250px;">
                    <option value=""><?php _e('Todos los actores', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($lista_actores as $actor_opcion): ?>
                        <option value="<?php echo esc_attr($actor_opcion->id); ?>" <?php selected($actor_id_filtro, $actor_opcion->id); ?>>
                            <?php echo esc_html($actor_opcion->nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="button"><?php _e('Filtrar', 'flavor-chat-ia'); ?></button>

                <?php if ($actor_id_filtro): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=actores-relaciones')); ?>" class="button">
                        <?php _e('Ver todas', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </form>

            <!-- Tabla de relaciones -->
            <?php if (!empty($relaciones)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 25%;"><?php _e('Actor Origen', 'flavor-chat-ia'); ?></th>
                            <th style="width: 15%;"><?php _e('Tipo Relacion', 'flavor-chat-ia'); ?></th>
                            <th style="width: 25%;"><?php _e('Actor Destino', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Intensidad', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                            <th style="width: 100px;"><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($relaciones as $relacion): ?>
                            <tr>
                                <td>
                                    <?php if ($is_dashboard_viewer) : ?>
                                        <?php echo esc_html($relacion->actor_origen_nombre); ?>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=actores-listado&action=ver&id=' . $relacion->actor_origen_id)); ?>">
                                            <?php echo esc_html($relacion->actor_origen_nombre); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($tipos_relacion[$relacion->tipo_relacion] ?? $relacion->tipo_relacion); ?>
                                    <?php if ($relacion->bidireccional): ?>
                                        <span title="<?php esc_attr_e('Bidireccional', 'flavor-chat-ia'); ?>">↔</span>
                                    <?php else: ?>
                                        <span>→</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_dashboard_viewer) : ?>
                                        <?php echo esc_html($relacion->actor_destino_nombre); ?>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=actores-listado&action=ver&id=' . $relacion->actor_destino_id)); ?>">
                                            <?php echo esc_html($relacion->actor_destino_nombre); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $intensidad_label = [
                                        'debil' => __('Debil', 'flavor-chat-ia'),
                                        'moderada' => __('Moderada', 'flavor-chat-ia'),
                                        'fuerte' => __('Fuerte', 'flavor-chat-ia'),
                                    ];
                                    echo esc_html($intensidad_label[$relacion->intensidad] ?? $relacion->intensidad);
                                    ?>
                                </td>
                                <td>
                                    <?php if ($relacion->verificada): ?>
                                        <span style="color: #00a32a;">✓ <?php _e('Verificada', 'flavor-chat-ia'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #dba617;">⏳ <?php _e('Pendiente', 'flavor-chat-ia'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-small" title="<?php esc_attr_e('Editar', 'flavor-chat-ia'); ?>">
                                        <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="notice notice-info">
                    <p>
                        <?php if ($actor_id_filtro): ?>
                            <?php _e('Este actor no tiene relaciones registradas.', 'flavor-chat-ia'); ?>
                        <?php else: ?>
                            <?php _e('No hay relaciones registradas entre actores.', 'flavor-chat-ia'); ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Grafo de relaciones (placeholder) -->
            <div style="margin-top: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0;"><?php _e('Visualizacion del Grafo', 'flavor-chat-ia'); ?></h3>
                <div id="grafo-relaciones" style="height: 400px; background: #f6f7f7; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #646970;">
                    <?php _e('El grafo de relaciones se mostrara aqui. Requiere la libreria de visualizacion.', 'flavor-chat-ia'); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la pagina de configuracion
     */
    public function render_admin_config() {
        // Guardar configuracion si se envio el formulario
        if (isset($_POST['guardar_config_actores']) && check_admin_referer('actores_config_nonce')) {
            $configuracion = [
                'tipos_personalizados' => sanitize_textarea_field($_POST['tipos_personalizados'] ?? ''),
                'relaciones_personalizadas' => sanitize_textarea_field($_POST['relaciones_personalizadas'] ?? ''),
                'mostrar_mapa' => isset($_POST['mostrar_mapa']) ? 1 : 0,
                'mostrar_grafo_relaciones' => isset($_POST['mostrar_grafo_relaciones']) ? 1 : 0,
                'permitir_edicion_comunidad' => isset($_POST['permitir_edicion_comunidad']) ? 1 : 0,
                'requiere_verificacion' => isset($_POST['requiere_verificacion']) ? 1 : 0,
                'ambitos_disponibles' => array_map('sanitize_text_field', $_POST['ambitos_disponibles'] ?? []),
            ];

            update_option('flavor_mapa_actores_config', $configuracion);

            echo '<div class="notice notice-success is-dismissible"><p>' . __('Configuracion guardada correctamente.', 'flavor-chat-ia') . '</p></div>';
        }

        // Cargar configuracion actual
        $configuracion = get_option('flavor_mapa_actores_config', []);
        $configuracion = wp_parse_args($configuracion, [
            'tipos_personalizados' => '',
            'relaciones_personalizadas' => '',
            'mostrar_mapa' => 1,
            'mostrar_grafo_relaciones' => 1,
            'permitir_edicion_comunidad' => 1,
            'requiere_verificacion' => 1,
            'ambitos_disponibles' => ['local', 'comarcal', 'provincial', 'autonomico', 'estatal', 'internacional'],
        ]);

        $this->render_page_header(__('Configuracion del Mapa de Actores', 'flavor-chat-ia'));
        ?>
        <div class="wrap">
            <form method="post" action="">
                <?php wp_nonce_field('actores_config_nonce'); ?>

                <!-- Tipos de actores -->
                <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h3 style="margin-top: 0;"><?php _e('Tipos de Actores', 'flavor-chat-ia'); ?></h3>
                    <p class="description"><?php _e('Los tipos predefinidos son: Administracion Publica, Empresa, Institucion, Medio de Comunicacion, Partido Politico, Sindicato, ONG, Colectivo, Persona, Otro.', 'flavor-chat-ia'); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="tipos_personalizados"><?php _e('Tipos Personalizados', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <textarea name="tipos_personalizados" id="tipos_personalizados" rows="4" class="large-text"
                                          placeholder="<?php esc_attr_e('Un tipo por linea: slug|Nombre visible', 'flavor-chat-ia'); ?>"><?php echo esc_textarea($configuracion['tipos_personalizados']); ?></textarea>
                                <p class="description"><?php _e('Formato: slug|Nombre visible (ej: cooperativa|Cooperativa)', 'flavor-chat-ia'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Tipos de relaciones -->
                <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h3 style="margin-top: 0;"><?php _e('Tipos de Relaciones', 'flavor-chat-ia'); ?></h3>
                    <p class="description"><?php _e('Los tipos predefinidos son: Pertenece a, Controla, Financia, Colabora, Compite, Influye, Depende, Otro.', 'flavor-chat-ia'); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="relaciones_personalizadas"><?php _e('Relaciones Personalizadas', 'flavor-chat-ia'); ?></label>
                            </th>
                            <td>
                                <textarea name="relaciones_personalizadas" id="relaciones_personalizadas" rows="4" class="large-text"
                                          placeholder="<?php esc_attr_e('Un tipo por linea: slug|Nombre visible', 'flavor-chat-ia'); ?>"><?php echo esc_textarea($configuracion['relaciones_personalizadas']); ?></textarea>
                                <p class="description"><?php _e('Formato: slug|Nombre visible (ej: subcontrata|Subcontrata)', 'flavor-chat-ia'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Opciones del mapa -->
                <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <h3 style="margin-top: 0;"><?php _e('Opciones de Visualizacion', 'flavor-chat-ia'); ?></h3>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Caracteristicas', 'flavor-chat-ia'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="mostrar_mapa" value="1" <?php checked($configuracion['mostrar_mapa'], 1); ?>>
                                        <?php _e('Mostrar mapa geografico de actores', 'flavor-chat-ia'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox" name="mostrar_grafo_relaciones" value="1" <?php checked($configuracion['mostrar_grafo_relaciones'], 1); ?>>
                                        <?php _e('Mostrar grafo de relaciones', 'flavor-chat-ia'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox" name="permitir_edicion_comunidad" value="1" <?php checked($configuracion['permitir_edicion_comunidad'], 1); ?>>
                                        <?php _e('Permitir que la comunidad sugiera actores', 'flavor-chat-ia'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox" name="requiere_verificacion" value="1" <?php checked($configuracion['requiere_verificacion'], 1); ?>>
                                        <?php _e('Los actores nuevos requieren verificacion', 'flavor-chat-ia'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Ambitos Geograficos', 'flavor-chat-ia'); ?></th>
                            <td>
                                <fieldset>
                                    <?php
                                    $ambitos_todos = [
                                        'local' => __('Local', 'flavor-chat-ia'),
                                        'comarcal' => __('Comarcal', 'flavor-chat-ia'),
                                        'provincial' => __('Provincial', 'flavor-chat-ia'),
                                        'autonomico' => __('Autonomico', 'flavor-chat-ia'),
                                        'estatal' => __('Estatal', 'flavor-chat-ia'),
                                        'internacional' => __('Internacional', 'flavor-chat-ia'),
                                    ];
                                    foreach ($ambitos_todos as $ambito_valor => $ambito_etiqueta):
                                    ?>
                                        <label style="display: inline-block; margin-right: 15px;">
                                            <input type="checkbox" name="ambitos_disponibles[]" value="<?php echo esc_attr($ambito_valor); ?>"
                                                   <?php checked(in_array($ambito_valor, $configuracion['ambitos_disponibles'])); ?>>
                                            <?php echo esc_html($ambito_etiqueta); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit">
                    <button type="submit" name="guardar_config_actores" class="button button-primary">
                        <?php _e('Guardar Configuracion', 'flavor-chat-ia'); ?>
                    </button>
                </p>
            </form>
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
            'module'   => 'mapa-actores',
            'title'    => __('Mapa de Actores', 'flavor-chat-ia'),
            'subtitle' => __('Documenta y visualiza actores del territorio', 'flavor-chat-ia'),
            'icon'     => '🗺️',
            'color'    => 'info', // Usa variable CSS --flavor-info del tema

            'database' => [
                'table'       => 'flavor_mapa_actores',
                'primary_key' => 'id',
            ],

            'fields' => [
                'nombre'      => ['type' => 'text', 'label' => __('Nombre', 'flavor-chat-ia'), 'required' => true],
                'tipo'        => ['type' => 'select', 'label' => __('Tipo', 'flavor-chat-ia'), 'required' => true],
                'posicion'    => ['type' => 'select', 'label' => __('Posición', 'flavor-chat-ia')],
                'influencia'  => ['type' => 'range', 'label' => __('Nivel de influencia', 'flavor-chat-ia'), 'min' => 1, 'max' => 5],
                'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
                'logo'        => ['type' => 'file', 'label' => __('Logo', 'flavor-chat-ia')],
                'web'         => ['type' => 'url', 'label' => __('Sitio web', 'flavor-chat-ia')],
            ],

            'estados' => [
                'aliado'    => ['label' => __('Aliado', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '🤝'],
                'neutro'    => ['label' => __('Neutro', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '⚪'],
                'opositor'  => ['label' => __('Opositor', 'flavor-chat-ia'), 'color' => 'red', 'icon' => '❌'],
                'variable'  => ['label' => __('Variable', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '⚡'],
            ],

            'stats' => [
                [
                    'key'   => 'total_actores',
                    'label' => __('Actores', 'flavor-chat-ia'),
                    'icon'  => '🏢',
                    'color' => 'cyan',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_mapa_actores",
                ],
                [
                    'key'   => 'aliados',
                    'label' => __('Aliados', 'flavor-chat-ia'),
                    'icon'  => '🤝',
                    'color' => 'green',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_mapa_actores WHERE posicion = 'aliado'",
                ],
                [
                    'key'   => 'opositores',
                    'label' => __('Opositores', 'flavor-chat-ia'),
                    'icon'  => '❌',
                    'color' => 'red',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_mapa_actores WHERE posicion = 'opositor'",
                ],
                [
                    'key'   => 'relaciones',
                    'label' => __('Relaciones', 'flavor-chat-ia'),
                    'icon'  => '🔗',
                    'color' => 'purple',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_mapa_actores_relaciones",
                ],
            ],

            'card' => [
                'layout'      => 'entity',
                'image_field' => 'logo',
                'title_field' => 'nombre',
                'meta_fields' => ['tipo', 'posicion', 'influencia'],
                'badge_field' => 'posicion',
            ],

            'tabs' => [
                'listado' => [
                    'label'   => __('Actores', 'flavor-chat-ia'),
                    'icon'    => '🏢',
                    'content' => 'template:mapa-actores/_listado.php',
                ],
                'grafo' => [
                    'label'   => __('Grafo', 'flavor-chat-ia'),
                    'icon'    => '🕸️',
                    'content' => 'shortcode:mapa_actores_grafo',
                ],
                'por-tipo' => [
                    'label'   => __('Por tipo', 'flavor-chat-ia'),
                    'icon'    => '📊',
                    'content' => 'shortcode:mapa_actores_tipos',
                ],
                'relaciones' => [
                    'label'   => __('Relaciones', 'flavor-chat-ia'),
                    'icon'    => '🔗',
                    'content' => 'shortcode:mapa_actores_relaciones',
                ],
            ],

            'archive' => [
                'columns'       => 3,
                'per_page'      => 18,
                'order_by'      => 'influencia',
                'order'         => 'DESC',
                'filterable_by' => ['tipo', 'posicion', 'influencia'],
            ],

            'dashboard' => [
                'widgets' => [
                    'actores_clave'   => ['type' => 'list', 'title' => __('Actores clave', 'flavor-chat-ia')],
                    'grafo_mini'      => ['type' => 'graph', 'title' => __('Grafo de relaciones', 'flavor-chat-ia')],
                ],
                'actions' => [
                    'nuevo_actor' => [
                        'label' => __('Añadir actor', 'flavor-chat-ia'),
                        'icon'  => '➕',
                        'modal' => 'mapa-actores-nuevo',
                    ],
                ],
            ],

            'features' => [
                'has_archive'    => true,
                'has_single'     => true,
                'has_dashboard'  => true,
                'has_search'     => true,
                'has_graph'      => true,
                'has_relations'  => true,
                'has_timeline'   => true,
            ],
        ];
    }

    /**
     * Registrar páginas de administración (ocultas del sidebar)
     * Las páginas son accesibles vía URL directa pero no aparecen en el menú
     * Se acceden desde el Dashboard Unificado
     */
    public function registrar_paginas_admin() {
        $capability = 'manage_options';

        // Páginas ocultas (null como parent = no aparecen en menú)
        add_submenu_page(null, __('Mapa Actores - Configuración', 'flavor-chat-ia'), __('Configuración', 'flavor-chat-ia'), $capability, 'actores-config', [$this, 'render_pagina_config']);
        add_submenu_page(null, __('Mapa Actores - Interacciones', 'flavor-chat-ia'), __('Interacciones', 'flavor-chat-ia'), $capability, 'actores-interacciones', [$this, 'render_pagina_interacciones']);
        add_submenu_page(null, __('Mapa Actores - Listado', 'flavor-chat-ia'), __('Listado', 'flavor-chat-ia'), $capability, 'actores-listado', [$this, 'render_pagina_listado']);
        add_submenu_page(null, __('Mapa Actores - Nuevo', 'flavor-chat-ia'), __('Nuevo Actor', 'flavor-chat-ia'), $capability, 'actores-nuevo', [$this, 'render_pagina_nuevo']);
        add_submenu_page(null, __('Mapa Actores - Personas', 'flavor-chat-ia'), __('Personas', 'flavor-chat-ia'), $capability, 'actores-personas', [$this, 'render_pagina_personas']);
        add_submenu_page(null, __('Mapa Actores - Relaciones', 'flavor-chat-ia'), __('Relaciones', 'flavor-chat-ia'), $capability, 'actores-relaciones', [$this, 'render_pagina_relaciones']);
    }

    public function render_pagina_config() {
        $views_path = dirname(__FILE__) . '/views/config.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Configuración Mapa de Actores', 'flavor-chat-ia') . '</h1></div>'; }
    }

    public function render_pagina_interacciones() {
        $views_path = dirname(__FILE__) . '/views/interacciones.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Historial de Interacciones', 'flavor-chat-ia') . '</h1></div>'; }
    }

    public function render_pagina_listado() {
        $views_path = dirname(__FILE__) . '/views/listado.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Listado de Actores', 'flavor-chat-ia') . '</h1></div>'; }
    }

    public function render_pagina_nuevo() {
        $views_path = dirname(__FILE__) . '/views/nuevo.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Nuevo Actor', 'flavor-chat-ia') . '</h1></div>'; }
    }

    public function render_pagina_personas() {
        $views_path = dirname(__FILE__) . '/views/personas.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Personas Clave', 'flavor-chat-ia') . '</h1></div>'; }
    }

    public function render_pagina_relaciones() {
        $views_path = dirname(__FILE__) . '/views/relaciones.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Relaciones entre Actores', 'flavor-chat-ia') . '</h1></div>'; }
    }
}
