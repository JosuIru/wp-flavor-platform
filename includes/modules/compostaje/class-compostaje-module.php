<?php
/**
 * Modulo de Compostaje Comunitario para Chat IA
 * Sistema completo de gestion de compostaje comunitario con gamificacion
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Compostaje - Gestion de compostaje comunitario
 */
class Flavor_Chat_Compostaje_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;

    /**
     * Version del modulo
     */
    const VERSION = '2.0.0';

    /**
     * Niveles de gamificacion
     */
    private $niveles_gamificacion = [
        1 => ['nombre' => 'Semilla', 'kg_minimo' => 0, 'puntos_bonus' => 0, 'icono' => 'seedling'],
        2 => ['nombre' => 'Brote', 'kg_minimo' => 10, 'puntos_bonus' => 5, 'icono' => 'leaf'],
        3 => ['nombre' => 'Planta', 'kg_minimo' => 50, 'puntos_bonus' => 10, 'icono' => 'plant'],
        4 => ['nombre' => 'Arbol', 'kg_minimo' => 150, 'puntos_bonus' => 15, 'icono' => 'tree'],
        5 => ['nombre' => 'Bosque', 'kg_minimo' => 500, 'puntos_bonus' => 25, 'icono' => 'forest'],
        6 => ['nombre' => 'Ecosistema', 'kg_minimo' => 1000, 'puntos_bonus' => 50, 'icono' => 'globe'],
    ];

    /**
     * Materiales compostables predefinidos
     */
    private $materiales_compostables = [
        'verdes' => [
            'frutas_verduras' => ['nombre' => 'Frutas y verduras', 'ratio_cn' => 25, 'puntos_kg' => 5],
            'posos_cafe' => ['nombre' => 'Posos de cafe', 'ratio_cn' => 20, 'puntos_kg' => 6],
            'cesped_fresco' => ['nombre' => 'Cesped fresco', 'ratio_cn' => 20, 'puntos_kg' => 4],
            'restos_cocina' => ['nombre' => 'Restos de cocina', 'ratio_cn' => 25, 'puntos_kg' => 5],
            'plantas_verdes' => ['nombre' => 'Plantas verdes', 'ratio_cn' => 25, 'puntos_kg' => 4],
        ],
        'marrones' => [
            'hojas_secas' => ['nombre' => 'Hojas secas', 'ratio_cn' => 60, 'puntos_kg' => 6],
            'papel_carton' => ['nombre' => 'Papel y carton', 'ratio_cn' => 170, 'puntos_kg' => 7],
            'ramas_poda' => ['nombre' => 'Ramas y poda', 'ratio_cn' => 100, 'puntos_kg' => 5],
            'serrín' => ['nombre' => 'Serrin', 'ratio_cn' => 400, 'puntos_kg' => 4],
            'paja' => ['nombre' => 'Paja', 'ratio_cn' => 75, 'puntos_kg' => 5],
        ],
        'especiales' => [
            'cascaras_huevo' => ['nombre' => 'Cascaras de huevo', 'ratio_cn' => 0, 'puntos_kg' => 8],
            'bolsas_te' => ['nombre' => 'Bolsas de te', 'ratio_cn' => 25, 'puntos_kg' => 6],
        ],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'compostaje';
        $this->name = 'Compostaje Comunitario'; // Translation loaded on init
        $this->description = 'Sistema completo de compostaje comunitario con gamificacion, turnos y estadisticas.'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';
        return Flavor_Chat_Helpers::tabla_existe($tabla_puntos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Compostaje no estan creadas. Se crearan automaticamente al activar.', 'flavor-chat-ia');
        }
        
    return '';
    }

/**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'disponible_app' => 'cliente',
            'permite_recoger_compost' => true,
            'kg_minimos_recogida' => 5,
            'puntos_por_kg_depositado' => 5,
            'puntos_por_turno_mantenimiento' => 50,
            'sistema_turnos_volteo' => true,
            'notificar_compost_listo' => true,
            'notificar_turno_asignado' => true,
            'dias_aviso_turno' => 2,
            'max_kg_por_deposito' => 10,
            'permitir_fotos_deposito' => true,
            'validacion_admin_requerida' => false,
            'mostrar_ranking' => true,
            'co2_por_kg_organico' => 0.5,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_pages']);
        // Registrar en panel de administracion unificado
        $this->registrar_en_panel_unificado();

        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_compostaje_registrar_aportacion', [$this, 'ajax_registrar_aportacion']);
        add_action('wp_ajax_compostaje_apuntarse_turno', [$this, 'ajax_apuntarse_turno']);
        add_action('wp_ajax_compostaje_consultar_estado', [$this, 'ajax_consultar_estado']);
        add_action('wp_ajax_compostaje_obtener_puntos', [$this, 'ajax_obtener_puntos_compostaje']);
        add_action('wp_ajax_compostaje_mis_aportaciones', [$this, 'ajax_mis_aportaciones']);
        add_action('wp_ajax_compostaje_cancelar_turno', [$this, 'ajax_cancelar_turno']);
        add_action('wp_ajax_compostaje_completar_turno', [$this, 'ajax_completar_turno']);
        add_action('wp_ajax_nopriv_compostaje_consultar_estado', [$this, 'ajax_consultar_estado']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Cron para notificaciones
        add_action('flavor_compostaje_notificaciones_diarias', [$this, 'enviar_notificaciones_turnos']);
        if (!wp_next_scheduled('flavor_compostaje_notificaciones_diarias')) {
            wp_schedule_event(time(), 'daily', 'flavor_compostaje_notificaciones_diarias');
        }
    }

    /**
     * Registra los shortcodes del modulo
     */
    public function register_shortcodes() {
        add_shortcode('mapa_composteras', [$this, 'shortcode_mapa_composteras']);
        add_shortcode('registrar_aportacion', [$this, 'shortcode_registrar_aportacion']);
        add_shortcode('mis_aportaciones', [$this, 'shortcode_mis_aportaciones']);
        add_shortcode('guia_compostaje', [$this, 'shortcode_guia_compostaje']);
        add_shortcode('ranking_compostaje', [$this, 'shortcode_ranking_compostaje']);
        add_shortcode('estadisticas_compostaje', [$this, 'shortcode_estadisticas_compostaje']);
        add_shortcode('turnos_compostaje', [$this, 'shortcode_turnos_compostaje']);
    }

    /**
     * Encola los assets del modulo
     */
    public function enqueue_assets() {
        if ($this->should_load_assets()) {
            $modulo_url = plugin_dir_url(__FILE__);

            wp_enqueue_style(
                'flavor-compostaje',
                $modulo_url . 'assets/css/compostaje.css',
                [],
                self::VERSION
            );

            wp_enqueue_script(
                'flavor-compostaje',
                $modulo_url . 'assets/js/compostaje.js',
                ['jquery'],
                self::VERSION,
                true
            );

            wp_localize_script('flavor-compostaje', 'flavorCompostaje', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => rest_url('flavor-compostaje/v1/'),
                'nonce' => wp_create_nonce('compostaje_nonce'),
                'restNonce' => wp_create_nonce('wp_rest'),
                'usuario_id' => get_current_user_id(),
                'strings' => [
                    'cargando' => __('Cargando...', 'flavor-chat-ia'),
                    'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                    'exito' => __('Operacion realizada con exito', 'flavor-chat-ia'),
                    'confirmar_turno' => __('¿Confirmas que quieres apuntarte a este turno?', 'flavor-chat-ia'),
                    'confirmar_cancelar' => __('¿Seguro que quieres cancelar tu turno?', 'flavor-chat-ia'),
                ],
            ]);
        }
    }

    /**
     * Determina si debe cargar assets
     */
    private function should_load_assets() {
        global $post;
        if (!$post) return false;

        $shortcodes = ['mapa_composteras', 'registrar_aportacion', 'mis_aportaciones',
                       'guia_compostaje', 'ranking_compostaje', 'estadisticas_compostaje', 'turnos_compostaje'];

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_puntos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de puntos de compostaje (ubicaciones fisicas)
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';
        $sql_puntos = "CREATE TABLE IF NOT EXISTS $tabla_puntos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            direccion varchar(500) NOT NULL,
            latitud decimal(10,7) NOT NULL,
            longitud decimal(10,7) NOT NULL,
            tipo enum('comunitario','vecinal','escolar','municipal','privado') DEFAULT 'comunitario',
            capacidad_litros int(11) NOT NULL DEFAULT 1000,
            num_composteras int(11) DEFAULT 1,
            nivel_llenado_pct int(11) DEFAULT 0,
            temperatura_actual decimal(5,2) DEFAULT NULL,
            humedad_actual int(11) DEFAULT NULL,
            fase_actual enum('recepcion','activo','maduracion','listo','mantenimiento') DEFAULT 'recepcion',
            fecha_ultima_medicion datetime DEFAULT NULL,
            fecha_inicio_ciclo datetime DEFAULT NULL,
            fecha_estimada_listo datetime DEFAULT NULL,
            responsable_id bigint(20) unsigned DEFAULT NULL,
            telefono_contacto varchar(20) DEFAULT NULL,
            email_contacto varchar(100) DEFAULT NULL,
            horario_apertura varchar(255) DEFAULT NULL,
            instrucciones_acceso text DEFAULT NULL,
            materiales_permitidos text DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            estado enum('activo','inactivo','mantenimiento','cerrado') DEFAULT 'activo',
            verificado tinyint(1) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ubicacion (latitud, longitud),
            KEY idx_estado (estado),
            KEY idx_tipo (tipo),
            KEY idx_fase (fase_actual),
            KEY idx_responsable (responsable_id)
        ) $charset_collate;";

        // Tabla de aportaciones de usuarios
        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';
        $sql_aportaciones = "CREATE TABLE IF NOT EXISTS $tabla_aportaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            punto_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo_material enum('frutas_verduras','posos_cafe','cesped_fresco','restos_cocina','plantas_verdes','hojas_secas','papel_carton','ramas_poda','serrin','paja','cascaras_huevo','bolsas_te','otro') DEFAULT 'frutas_verduras',
            categoria_material enum('verde','marron','especial') DEFAULT 'verde',
            cantidad_kg decimal(10,3) NOT NULL,
            puntos_obtenidos int(11) DEFAULT 0,
            bonus_nivel int(11) DEFAULT 0,
            foto_url varchar(500) DEFAULT NULL,
            notas text DEFAULT NULL,
            validado tinyint(1) DEFAULT 1,
            validado_por bigint(20) unsigned DEFAULT NULL,
            fecha_validacion datetime DEFAULT NULL,
            co2_evitado_kg decimal(10,3) DEFAULT 0,
            fecha_aportacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_punto (punto_id),
            KEY idx_usuario (usuario_id),
            KEY idx_fecha (fecha_aportacion),
            KEY idx_tipo (tipo_material),
            KEY idx_validado (validado)
        ) $charset_collate;";

        // Tabla de turnos de mantenimiento
        $tabla_turnos = $wpdb->prefix . 'flavor_turnos_compostaje';
        $sql_turnos = "CREATE TABLE IF NOT EXISTS $tabla_turnos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            punto_id bigint(20) unsigned NOT NULL,
            tipo_tarea enum('volteo','riego','medicion','tamizado','limpieza','revision','otro') DEFAULT 'volteo',
            descripcion text DEFAULT NULL,
            fecha_turno date NOT NULL,
            hora_inicio time DEFAULT '09:00:00',
            hora_fin time DEFAULT '11:00:00',
            plazas_disponibles int(11) DEFAULT 2,
            plazas_ocupadas int(11) DEFAULT 0,
            puntos_recompensa int(11) DEFAULT 50,
            estado enum('abierto','completo','en_curso','completado','cancelado') DEFAULT 'abierto',
            notas_organizador text DEFAULT NULL,
            creado_por bigint(20) unsigned DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_punto (punto_id),
            KEY idx_fecha (fecha_turno),
            KEY idx_estado (estado),
            KEY idx_tipo (tipo_tarea)
        ) $charset_collate;";

        // Tabla de inscripciones a turnos
        $tabla_inscripciones = $wpdb->prefix . 'flavor_inscripciones_turno';
        $sql_inscripciones = "CREATE TABLE IF NOT EXISTS $tabla_inscripciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            turno_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            estado enum('inscrito','confirmado','asistio','no_asistio','cancelado') DEFAULT 'inscrito',
            puntos_obtenidos int(11) DEFAULT 0,
            notas_usuario text DEFAULT NULL,
            notas_admin text DEFAULT NULL,
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_confirmacion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_turno_usuario (turno_id, usuario_id),
            KEY idx_usuario (usuario_id),
            KEY idx_estado (estado)
        ) $charset_collate;";

        // Tabla de materiales compostables (configuracion)
        $tabla_materiales = $wpdb->prefix . 'flavor_materiales_compostables';
        $sql_materiales = "CREATE TABLE IF NOT EXISTS $tabla_materiales (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            codigo varchar(50) NOT NULL,
            nombre varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            categoria enum('verde','marron','especial','no_compostable') NOT NULL,
            ratio_carbono_nitrogeno int(11) DEFAULT NULL,
            puntos_por_kg int(11) DEFAULT 5,
            icono varchar(50) DEFAULT NULL,
            consejos text DEFAULT NULL,
            activo tinyint(1) DEFAULT 1,
            orden int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY idx_codigo (codigo),
            KEY idx_categoria (categoria),
            KEY idx_activo (activo)
        ) $charset_collate;";

        // Tabla de estadisticas agregadas
        $tabla_estadisticas = $wpdb->prefix . 'flavor_estadisticas_compost';
        $sql_estadisticas = "CREATE TABLE IF NOT EXISTS $tabla_estadisticas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            punto_id bigint(20) unsigned DEFAULT NULL,
            periodo enum('diario','semanal','mensual','anual') DEFAULT 'mensual',
            fecha_periodo date NOT NULL,
            total_kg_aportados decimal(10,2) DEFAULT 0,
            total_aportaciones int(11) DEFAULT 0,
            usuarios_activos int(11) DEFAULT 0,
            kg_verdes decimal(10,2) DEFAULT 0,
            kg_marrones decimal(10,2) DEFAULT 0,
            turnos_completados int(11) DEFAULT 0,
            co2_evitado_kg decimal(10,2) DEFAULT 0,
            puntos_otorgados int(11) DEFAULT 0,
            compost_producido_kg decimal(10,2) DEFAULT 0,
            fecha_calculo datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_punto_periodo_fecha (punto_id, periodo, fecha_periodo),
            KEY idx_fecha (fecha_periodo),
            KEY idx_periodo (periodo)
        ) $charset_collate;";

        // Tabla de logros/insignias
        $tabla_logros = $wpdb->prefix . 'flavor_logros_compostaje';
        $sql_logros = "CREATE TABLE IF NOT EXISTS $tabla_logros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo_logro varchar(50) NOT NULL,
            nivel int(11) DEFAULT 1,
            descripcion varchar(255) DEFAULT NULL,
            fecha_obtencion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_usuario_logro (usuario_id, tipo_logro, nivel),
            KEY idx_usuario (usuario_id),
            KEY idx_tipo (tipo_logro)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_puntos);
        dbDelta($sql_aportaciones);
        dbDelta($sql_turnos);
        dbDelta($sql_inscripciones);
        dbDelta($sql_materiales);
        dbDelta($sql_estadisticas);
        dbDelta($sql_logros);

        $this->insertar_materiales_iniciales();
    }

    /**
     * Inserta materiales compostables iniciales
     */
    private function insertar_materiales_iniciales() {
        global $wpdb;
        $tabla_materiales = $wpdb->prefix . 'flavor_materiales_compostables';

        $conteo_existente = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_materiales");
        if ($conteo_existente > 0) return;

        $materiales_iniciales = [
            ['frutas_verduras', 'Frutas y verduras', 'Restos de frutas y verduras crudas', 'verde', 25, 5, 'apple', 'Cortar en trozos pequenos acelera la descomposicion', 1],
            ['posos_cafe', 'Posos de cafe', 'Posos de cafe y filtros de papel', 'verde', 20, 6, 'coffee', 'Excelente aporte de nitrogeno', 2],
            ['cesped_fresco', 'Cesped fresco', 'Cesped recien cortado', 'verde', 20, 4, 'grass', 'Mezclar con material seco para evitar compactacion', 3],
            ['restos_cocina', 'Restos de cocina', 'Restos vegetales de cocina', 'verde', 25, 5, 'cooking', 'Evitar restos cocinados con aceite', 4],
            ['hojas_secas', 'Hojas secas', 'Hojas secas de arboles', 'marron', 60, 6, 'leaf', 'Material ideal para equilibrar humedad', 5],
            ['papel_carton', 'Papel y carton', 'Papel y carton sin tintas toxicas', 'marron', 170, 7, 'paper', 'Romper en trozos pequenos', 6],
            ['ramas_poda', 'Ramas y poda', 'Ramas pequenas y restos de poda', 'marron', 100, 5, 'tree', 'Triturar para acelerar descomposicion', 7],
            ['cascaras_huevo', 'Cascaras de huevo', 'Cascaras de huevo trituradas', 'especial', 0, 8, 'egg', 'Aportan calcio al compost', 8],
        ];

        foreach ($materiales_iniciales as $material) {
            $wpdb->insert($tabla_materiales, [
                'codigo' => $material[0],
                'nombre' => $material[1],
                'descripcion' => $material[2],
                'categoria' => $material[3],
                'ratio_carbono_nitrogeno' => $material[4],
                'puntos_por_kg' => $material[5],
                'icono' => $material[6],
                'consejos' => $material[7],
                'orden' => $material[8],
                'activo' => 1,
            ]);
        }
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes() {
        register_rest_route('flavor-compostaje/v1', '/puntos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_puntos'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-compostaje/v1', '/puntos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_punto'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-compostaje/v1', '/aportacion', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_registrar_aportacion'],
            'permission_callback' => [$this, 'rest_usuario_autenticado'],
        ]);

        register_rest_route('flavor-compostaje/v1', '/usuario/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_estadisticas_usuario'],
            'permission_callback' => [$this, 'rest_usuario_autenticado'],
        ]);

        register_rest_route('flavor-compostaje/v1', '/turnos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_turnos'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-compostaje/v1', '/turno/inscribir', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_inscribir_turno'],
            'permission_callback' => [$this, 'rest_usuario_autenticado'],
        ]);

        register_rest_route('flavor-compostaje/v1', '/estadisticas/globales', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_estadisticas_globales'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-compostaje/v1', '/ranking', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_ranking'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-compostaje/v1', '/materiales', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_materiales'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * Verifica usuario autenticado para REST
     */
    public function rest_usuario_autenticado() {
        return is_user_logged_in();
    }

    /**
     * REST: Obtener puntos de compostaje
     */
    public function rest_obtener_puntos($request) {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

        $latitud = $request->get_param('lat');
        $longitud = $request->get_param('lng');
        $radio = $request->get_param('radio') ?: 10;
        $tipo = $request->get_param('tipo');
        $estado = $request->get_param('estado') ?: 'activo';

        $where_condiciones = ["estado = %s"];
        $where_valores = [$estado];

        if ($tipo) {
            $where_condiciones[] = "tipo = %s";
            $where_valores[] = $tipo;
        }

        $where_clausula = implode(' AND ', $where_condiciones);

        if ($latitud && $longitud) {
            $sql = $wpdb->prepare(
                "SELECT *,
                (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia_km
                FROM $tabla_puntos
                WHERE $where_clausula
                HAVING distancia_km <= %f
                ORDER BY distancia_km ASC
                LIMIT 50",
                array_merge([$latitud, $longitud, $latitud], $where_valores, [$radio])
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT * FROM $tabla_puntos WHERE $where_clausula ORDER BY nombre LIMIT 50",
                $where_valores
            );
        }

        $puntos = $wpdb->get_results($sql);

        return rest_ensure_response([
            'success' => true,
            'puntos' => array_map([$this, 'formatear_punto_respuesta'], $puntos),
            'total' => count($puntos),
        ]);
    }

    /**
     * REST: Obtener punto especifico
     */
    public function rest_obtener_punto($request) {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';
        $punto_id = intval($request['id']);

        $punto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_puntos WHERE id = %d",
            $punto_id
        ));

        if (!$punto) {
            return new WP_Error('punto_no_encontrado', 'Punto de compostaje no encontrado', ['status' => 404]);
        }

        $estadisticas_punto = $this->obtener_estadisticas_punto($punto_id);
        $turnos_proximos = $this->obtener_turnos_punto($punto_id, 5);

        return rest_ensure_response([
            'success' => true,
            'punto' => $this->formatear_punto_respuesta($punto),
            'estadisticas' => $estadisticas_punto,
            'turnos_proximos' => $turnos_proximos,
        ]);
    }

    /**
     * REST: Registrar aportacion
     */
    public function rest_registrar_aportacion($request) {
        $punto_id = intval($request->get_param('punto_id'));
        $tipo_material = sanitize_text_field($request->get_param('tipo_material'));
        $cantidad_kg = floatval($request->get_param('cantidad_kg'));
        $notas = sanitize_textarea_field($request->get_param('notas'));

        $resultado = $this->registrar_aportacion($punto_id, $tipo_material, $cantidad_kg, $notas);

        if ($resultado['success']) {
            return rest_ensure_response($resultado);
        } else {
            return new WP_Error('error_aportacion', $resultado['error'], ['status' => 400]);
        }
    }

    /**
     * REST: Estadisticas del usuario
     */
    public function rest_estadisticas_usuario($request) {
        $usuario_id = get_current_user_id();
        $estadisticas = $this->obtener_estadisticas_usuario($usuario_id);

        return rest_ensure_response([
            'success' => true,
            'estadisticas' => $estadisticas,
        ]);
    }

    /**
     * REST: Obtener turnos disponibles
     */
    public function rest_obtener_turnos($request) {
        global $wpdb;
        $tabla_turnos = $wpdb->prefix . 'flavor_turnos_compostaje';
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

        $punto_id = $request->get_param('punto_id');
        $desde = $request->get_param('desde') ?: date('Y-m-d');
        $hasta = $request->get_param('hasta') ?: date('Y-m-d', strtotime('+30 days'));

        $where_condiciones = ["t.fecha_turno >= %s", "t.fecha_turno <= %s", "t.estado IN ('abierto', 'completo')"];
        $where_valores = [$desde, $hasta];

        if ($punto_id) {
            $where_condiciones[] = "t.punto_id = %d";
            $where_valores[] = intval($punto_id);
        }

        $where_clausula = implode(' AND ', $where_condiciones);

        $turnos = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, p.nombre as nombre_punto, p.direccion
            FROM $tabla_turnos t
            LEFT JOIN $tabla_puntos p ON t.punto_id = p.id
            WHERE $where_clausula
            ORDER BY t.fecha_turno ASC, t.hora_inicio ASC",
            $where_valores
        ));

        return rest_ensure_response([
            'success' => true,
            'turnos' => $turnos,
        ]);
    }

    /**
     * REST: Inscribirse a turno
     */
    public function rest_inscribir_turno($request) {
        $turno_id = intval($request->get_param('turno_id'));
        $resultado = $this->inscribir_usuario_turno($turno_id);

        if ($resultado['success']) {
            return rest_ensure_response($resultado);
        } else {
            return new WP_Error('error_inscripcion', $resultado['error'], ['status' => 400]);
        }
    }

    /**
     * REST: Estadisticas globales
     */
    public function rest_estadisticas_globales($request) {
        $estadisticas = $this->obtener_estadisticas_globales();

        return rest_ensure_response([
            'success' => true,
            'estadisticas' => $estadisticas,
        ]);
    }

    /**
     * REST: Ranking de usuarios
     */
    public function rest_obtener_ranking($request) {
        $limite = $request->get_param('limite') ?: 10;
        $periodo = $request->get_param('periodo') ?: 'total';

        $ranking = $this->obtener_ranking_usuarios($limite, $periodo);

        $respuesta = [
            'success' => true,
            'ranking' => $ranking,
        ];

        return rest_ensure_response($this->sanitize_public_compostaje_response($respuesta));
    }

    private function sanitize_public_compostaje_response($respuesta) {
        if (is_user_logged_in() || empty($respuesta['success'])) {
            return $respuesta;
        }

        if (!empty($respuesta['ranking']) && is_array($respuesta['ranking'])) {
            $respuesta['ranking'] = array_map(function($entrada) {
                if (!is_array($entrada)) {
                    return $entrada;
                }

                unset($entrada['usuario_id']);
                $entrada['avatar'] = '';
                return $entrada;
            }, $respuesta['ranking']);
        }

        return $respuesta;
    }

    /**
     * REST: Obtener materiales compostables
     */
    public function rest_obtener_materiales($request) {
        global $wpdb;
        $tabla_materiales = $wpdb->prefix . 'flavor_materiales_compostables';

        $materiales = $wpdb->get_results(
            "SELECT * FROM $tabla_materiales WHERE activo = 1 ORDER BY categoria, orden"
        );

        $materiales_agrupados = [
            'verde' => [],
            'marron' => [],
            'especial' => [],
        ];

        foreach ($materiales as $material) {
            $materiales_agrupados[$material->categoria][] = $material;
        }

        return rest_ensure_response([
            'success' => true,
            'materiales' => $materiales_agrupados,
        ]);
    }

    /**
     * Registra una aportacion de material organico
     */
    public function registrar_aportacion($punto_id, $tipo_material, $cantidad_kg, $notas = '', $foto_url = '') {
        global $wpdb;

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion para registrar aportaciones', 'flavor-chat-ia')];
        }

        $configuracion = $this->get_settings();
        $cantidad_maxima = $configuracion['max_kg_por_deposito'] ?? 10;

        if ($cantidad_kg <= 0 || $cantidad_kg > $cantidad_maxima) {
            return ['success' => false, 'error' => sprintf(__('La cantidad debe estar entre 0.1 y %d kg', 'flavor-chat-ia'), $cantidad_maxima)];
        }

        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';
        $punto = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_puntos WHERE id = %d AND estado = 'activo'", $punto_id));

        if (!$punto) {
            return ['success' => false, 'error' => __('Punto de compostaje no encontrado o inactivo', 'flavor-chat-ia')];
        }

        if (!in_array($punto->fase_actual, ['recepcion', 'activo'])) {
            return ['success' => false, 'error' => __('Este punto no acepta aportaciones en su fase actual', 'flavor-chat-ia')];
        }

        $tabla_materiales = $wpdb->prefix . 'flavor_materiales_compostables';
        $material = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_materiales WHERE codigo = %s AND activo = 1",
            $tipo_material
        ));

        if (!$material) {
            return ['success' => false, 'error' => __('Tipo de material no valido', 'flavor-chat-ia')];
        }

        $puntos_base = $material->puntos_por_kg * $cantidad_kg;
        $nivel_usuario = $this->obtener_nivel_usuario($usuario_id);
        $bonus_nivel = $this->niveles_gamificacion[$nivel_usuario]['puntos_bonus'] ?? 0;
        $puntos_totales = $puntos_base + ($bonus_nivel * $cantidad_kg);

        $co2_evitado = $cantidad_kg * ($configuracion['co2_por_kg_organico'] ?? 0.5);

        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';
        $insertado = $wpdb->insert($tabla_aportaciones, [
            'punto_id' => $punto_id,
            'usuario_id' => $usuario_id,
            'tipo_material' => $tipo_material,
            'categoria_material' => $material->categoria,
            'cantidad_kg' => $cantidad_kg,
            'puntos_obtenidos' => $puntos_totales,
            'bonus_nivel' => $bonus_nivel * $cantidad_kg,
            'foto_url' => $foto_url,
            'notas' => $notas,
            'validado' => ($configuracion['validacion_admin_requerida'] ?? false) ? 0 : 1,
            'co2_evitado_kg' => $co2_evitado,
            'fecha_aportacion' => current_time('mysql'),
        ]);

        if (!$insertado) {
            return ['success' => false, 'error' => __('Error al registrar la aportacion', 'flavor-chat-ia')];
        }

        $aportacion_id = $wpdb->insert_id;

        $this->actualizar_nivel_punto($punto_id);
        $this->verificar_logros_usuario($usuario_id);
        $this->actualizar_estadisticas_diarias($punto_id);

        return [
            'success' => true,
            'mensaje' => __('Aportacion registrada correctamente', 'flavor-chat-ia'),
            'aportacion_id' => $aportacion_id,
            'puntos_obtenidos' => $puntos_totales,
            'bonus_nivel' => $bonus_nivel * $cantidad_kg,
            'co2_evitado' => $co2_evitado,
            'nivel_actual' => $nivel_usuario,
        ];
    }

    /**
     * Obtiene el nivel del usuario basado en kg aportados
     */
    public function obtener_nivel_usuario($usuario_id) {
        global $wpdb;
        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';

        $kg_totales = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(cantidad_kg), 0) FROM $tabla_aportaciones WHERE usuario_id = %d AND validado = 1",
            $usuario_id
        ));

        $nivel_calculado = 1;
        foreach ($this->niveles_gamificacion as $nivel => $datos) {
            if ($kg_totales >= $datos['kg_minimo']) {
                $nivel_calculado = $nivel;
            }
        }

        return $nivel_calculado;
    }

    /**
     * Obtiene estadisticas del usuario
     */
    public function obtener_estadisticas_usuario($usuario_id) {
        global $wpdb;
        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_inscripciones_turno';
        $tabla_logros = $wpdb->prefix . 'flavor_logros_compostaje';

        $estadisticas_basicas = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_aportaciones,
                COALESCE(SUM(cantidad_kg), 0) as total_kg,
                COALESCE(SUM(puntos_obtenidos), 0) as total_puntos,
                COALESCE(SUM(co2_evitado_kg), 0) as total_co2_evitado
            FROM $tabla_aportaciones
            WHERE usuario_id = %d AND validado = 1",
            $usuario_id
        ));

        $turnos_completados = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_inscripciones WHERE usuario_id = %d AND estado = 'asistio'",
            $usuario_id
        ));

        $logros_obtenidos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_logros WHERE usuario_id = %d ORDER BY fecha_obtencion DESC",
            $usuario_id
        ));

        $nivel_actual = $this->obtener_nivel_usuario($usuario_id);
        $datos_nivel = $this->niveles_gamificacion[$nivel_actual];

        $proximo_nivel = isset($this->niveles_gamificacion[$nivel_actual + 1]) ? $nivel_actual + 1 : null;
        $kg_para_proximo = null;
        $progreso_porcentaje = 100;

        if ($proximo_nivel) {
            $kg_proximo = $this->niveles_gamificacion[$proximo_nivel]['kg_minimo'];
            $kg_actual_nivel = $datos_nivel['kg_minimo'];
            $kg_para_proximo = $kg_proximo - floatval($estadisticas_basicas->total_kg);
            $progreso_porcentaje = (($estadisticas_basicas->total_kg - $kg_actual_nivel) / ($kg_proximo - $kg_actual_nivel)) * 100;
        }

        $historial_mensual = $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE_FORMAT(fecha_aportacion, '%%Y-%%m') as mes,
                SUM(cantidad_kg) as kg_mes,
                COUNT(*) as aportaciones_mes
            FROM $tabla_aportaciones
            WHERE usuario_id = %d AND validado = 1 AND fecha_aportacion >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(fecha_aportacion, '%%Y-%%m')
            ORDER BY mes DESC",
            $usuario_id
        ));

        return [
            'total_aportaciones' => intval($estadisticas_basicas->total_aportaciones),
            'total_kg' => floatval($estadisticas_basicas->total_kg),
            'total_puntos' => intval($estadisticas_basicas->total_puntos),
            'total_co2_evitado' => floatval($estadisticas_basicas->total_co2_evitado),
            'turnos_completados' => intval($turnos_completados),
            'nivel' => [
                'numero' => $nivel_actual,
                'nombre' => $datos_nivel['nombre'],
                'icono' => $datos_nivel['icono'],
                'bonus_actual' => $datos_nivel['puntos_bonus'],
            ],
            'proximo_nivel' => $proximo_nivel ? [
                'numero' => $proximo_nivel,
                'nombre' => $this->niveles_gamificacion[$proximo_nivel]['nombre'],
                'kg_necesarios' => $kg_para_proximo,
                'progreso_porcentaje' => min(100, max(0, $progreso_porcentaje)),
            ] : null,
            'logros' => $logros_obtenidos,
            'historial_mensual' => $historial_mensual,
        ];
    }

    /**
     * Inscribe usuario a un turno
     */
    public function inscribir_usuario_turno($turno_id) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Debes iniciar sesion', 'flavor-chat-ia')];
        }

        $tabla_turnos = $wpdb->prefix . 'flavor_turnos_compostaje';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_inscripciones_turno';

        $turno = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_turnos WHERE id = %d", $turno_id));

        if (!$turno) {
            return ['success' => false, 'error' => __('Turno no encontrado', 'flavor-chat-ia')];
        }

        if ($turno->estado !== 'abierto') {
            return ['success' => false, 'error' => __('Este turno no esta disponible', 'flavor-chat-ia')];
        }

        if ($turno->plazas_ocupadas >= $turno->plazas_disponibles) {
            return ['success' => false, 'error' => __('No hay plazas disponibles', 'flavor-chat-ia')];
        }

        $ya_inscrito = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_inscripciones WHERE turno_id = %d AND usuario_id = %d AND estado NOT IN ('cancelado')",
            $turno_id, $usuario_id
        ));

        if ($ya_inscrito) {
            return ['success' => false, 'error' => __('Ya estas inscrito en este turno', 'flavor-chat-ia')];
        }

        $insertado = $wpdb->insert($tabla_inscripciones, [
            'turno_id' => $turno_id,
            'usuario_id' => $usuario_id,
            'estado' => 'inscrito',
            'fecha_inscripcion' => current_time('mysql'),
        ]);

        if (!$insertado) {
            return ['success' => false, 'error' => __('Error al realizar la inscripcion', 'flavor-chat-ia')];
        }

        $wpdb->update($tabla_turnos,
            ['plazas_ocupadas' => $turno->plazas_ocupadas + 1],
            ['id' => $turno_id]
        );

        if ($turno->plazas_ocupadas + 1 >= $turno->plazas_disponibles) {
            $wpdb->update($tabla_turnos, ['estado' => 'completo'], ['id' => $turno_id]);
        }

        return [
            'success' => true,
            'mensaje' => __('Inscripcion realizada correctamente', 'flavor-chat-ia'),
            'turno' => $turno,
        ];
    }

    /**
     * Obtiene estadisticas globales
     */
    public function obtener_estadisticas_globales() {
        global $wpdb;
        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_inscripciones_turno';

        $estadisticas = $wpdb->get_row(
            "SELECT
                COUNT(DISTINCT usuario_id) as usuarios_activos,
                COUNT(*) as total_aportaciones,
                COALESCE(SUM(cantidad_kg), 0) as total_kg,
                COALESCE(SUM(co2_evitado_kg), 0) as total_co2_evitado
            FROM $tabla_aportaciones WHERE validado = 1"
        );

        $puntos_activos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_puntos WHERE estado = 'activo'");

        $turnos_completados = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_inscripciones WHERE estado = 'asistio'"
        );

        $estadisticas_mes = $wpdb->get_row(
            "SELECT
                COUNT(*) as aportaciones_mes,
                COALESCE(SUM(cantidad_kg), 0) as kg_mes
            FROM $tabla_aportaciones
            WHERE validado = 1 AND fecha_aportacion >= DATE_SUB(NOW(), INTERVAL 1 MONTH)"
        );

        return [
            'usuarios_activos' => intval($estadisticas->usuarios_activos),
            'total_aportaciones' => intval($estadisticas->total_aportaciones),
            'total_kg' => floatval($estadisticas->total_kg),
            'total_co2_evitado' => floatval($estadisticas->total_co2_evitado),
            'puntos_activos' => intval($puntos_activos),
            'turnos_completados' => intval($turnos_completados),
            'estadisticas_mes' => [
                'aportaciones' => intval($estadisticas_mes->aportaciones_mes),
                'kg' => floatval($estadisticas_mes->kg_mes),
            ],
        ];
    }

    /**
     * Obtiene ranking de usuarios
     */
    public function obtener_ranking_usuarios($limite = 10, $periodo = 'total') {
        global $wpdb;
        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';

        $where_fecha = '';
        if ($periodo === 'mes') {
            $where_fecha = "AND fecha_aportacion >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        } elseif ($periodo === 'semana') {
            $where_fecha = "AND fecha_aportacion >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        } elseif ($periodo === 'ano') {
            $where_fecha = "AND fecha_aportacion >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        }

        $ranking = $wpdb->get_results($wpdb->prepare(
            "SELECT
                usuario_id,
                SUM(cantidad_kg) as total_kg,
                SUM(puntos_obtenidos) as total_puntos,
                COUNT(*) as total_aportaciones
            FROM $tabla_aportaciones
            WHERE validado = 1 $where_fecha
            GROUP BY usuario_id
            ORDER BY total_kg DESC
            LIMIT %d",
            $limite
        ));

        $ranking_formateado = [];
        $posicion = 1;
        foreach ($ranking as $entrada) {
            $usuario = get_userdata($entrada->usuario_id);
            $nivel = $this->obtener_nivel_usuario($entrada->usuario_id);

            $ranking_formateado[] = [
                'posicion' => $posicion,
                'usuario_id' => $entrada->usuario_id,
                'nombre' => $usuario ? $usuario->display_name : __('Usuario anonimo', 'flavor-chat-ia'),
                'avatar' => get_avatar_url($entrada->usuario_id, ['size' => 50]),
                'total_kg' => floatval($entrada->total_kg),
                'total_puntos' => intval($entrada->total_puntos),
                'total_aportaciones' => intval($entrada->total_aportaciones),
                'nivel' => $nivel,
                'nivel_nombre' => $this->niveles_gamificacion[$nivel]['nombre'],
            ];
            $posicion++;
        }

        return $ranking_formateado;
    }

    /**
     * Verifica y otorga logros al usuario
     */
    private function verificar_logros_usuario($usuario_id) {
        global $wpdb;
        $tabla_logros = $wpdb->prefix . 'flavor_logros_compostaje';
        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';

        $estadisticas = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_aportaciones,
                SUM(cantidad_kg) as total_kg
            FROM $tabla_aportaciones WHERE usuario_id = %d AND validado = 1",
            $usuario_id
        ));

        $logros_posibles = [
            ['tipo' => 'primera_aportacion', 'nivel' => 1, 'condicion' => $estadisticas->total_aportaciones >= 1, 'descripcion' => 'Primera aportacion realizada'],
            ['tipo' => 'aportaciones_10', 'nivel' => 1, 'condicion' => $estadisticas->total_aportaciones >= 10, 'descripcion' => '10 aportaciones realizadas'],
            ['tipo' => 'aportaciones_50', 'nivel' => 2, 'condicion' => $estadisticas->total_aportaciones >= 50, 'descripcion' => '50 aportaciones realizadas'],
            ['tipo' => 'aportaciones_100', 'nivel' => 3, 'condicion' => $estadisticas->total_aportaciones >= 100, 'descripcion' => '100 aportaciones realizadas'],
            ['tipo' => 'kg_10', 'nivel' => 1, 'condicion' => $estadisticas->total_kg >= 10, 'descripcion' => '10 kg aportados'],
            ['tipo' => 'kg_50', 'nivel' => 2, 'condicion' => $estadisticas->total_kg >= 50, 'descripcion' => '50 kg aportados'],
            ['tipo' => 'kg_100', 'nivel' => 3, 'condicion' => $estadisticas->total_kg >= 100, 'descripcion' => '100 kg aportados'],
            ['tipo' => 'kg_500', 'nivel' => 4, 'condicion' => $estadisticas->total_kg >= 500, 'descripcion' => '500 kg aportados'],
        ];

        foreach ($logros_posibles as $logro) {
            if ($logro['condicion']) {
                $existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_logros WHERE usuario_id = %d AND tipo_logro = %s AND nivel = %d",
                    $usuario_id, $logro['tipo'], $logro['nivel']
                ));

                if (!$existe) {
                    $wpdb->insert($tabla_logros, [
                        'usuario_id' => $usuario_id,
                        'tipo_logro' => $logro['tipo'],
                        'nivel' => $logro['nivel'],
                        'descripcion' => $logro['descripcion'],
                        'fecha_obtencion' => current_time('mysql'),
                    ]);
                }
            }
        }
    }

    /**
     * Actualiza el nivel de llenado del punto
     */
    private function actualizar_nivel_punto($punto_id) {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';
        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';

        $punto = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_puntos WHERE id = %d", $punto_id));
        if (!$punto) return;

        $kg_ciclo_actual = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(cantidad_kg), 0) FROM $tabla_aportaciones
            WHERE punto_id = %d AND fecha_aportacion >= %s",
            $punto_id, $punto->fecha_inicio_ciclo ?: '1970-01-01'
        ));

        $densidad_compost = 0.4;
        $litros_ocupados = $kg_ciclo_actual / $densidad_compost;
        $porcentaje_llenado = min(100, ($litros_ocupados / $punto->capacidad_litros) * 100);

        $wpdb->update($tabla_puntos,
            ['nivel_llenado_pct' => $porcentaje_llenado],
            ['id' => $punto_id]
        );

        if ($porcentaje_llenado >= 80 && $punto->fase_actual === 'recepcion') {
            $wpdb->update($tabla_puntos, ['fase_actual' => 'activo'], ['id' => $punto_id]);
        }
    }

    /**
     * Actualiza estadisticas diarias
     */
    private function actualizar_estadisticas_diarias($punto_id) {
        global $wpdb;
        $tabla_estadisticas = $wpdb->prefix . 'flavor_estadisticas_compost';
        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';

        $fecha_hoy = date('Y-m-d');

        $estadisticas_hoy = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_aportaciones,
                COALESCE(SUM(cantidad_kg), 0) as total_kg,
                COUNT(DISTINCT usuario_id) as usuarios_activos,
                COALESCE(SUM(CASE WHEN categoria_material = 'verde' THEN cantidad_kg ELSE 0 END), 0) as kg_verdes,
                COALESCE(SUM(CASE WHEN categoria_material = 'marron' THEN cantidad_kg ELSE 0 END), 0) as kg_marrones,
                COALESCE(SUM(co2_evitado_kg), 0) as co2_evitado,
                COALESCE(SUM(puntos_obtenidos), 0) as puntos_otorgados
            FROM $tabla_aportaciones
            WHERE punto_id = %d AND DATE(fecha_aportacion) = %s AND validado = 1",
            $punto_id, $fecha_hoy
        ));

        $wpdb->replace($tabla_estadisticas, [
            'punto_id' => $punto_id,
            'periodo' => 'diario',
            'fecha_periodo' => $fecha_hoy,
            'total_kg_aportados' => $estadisticas_hoy->total_kg,
            'total_aportaciones' => $estadisticas_hoy->total_aportaciones,
            'usuarios_activos' => $estadisticas_hoy->usuarios_activos,
            'kg_verdes' => $estadisticas_hoy->kg_verdes,
            'kg_marrones' => $estadisticas_hoy->kg_marrones,
            'co2_evitado_kg' => $estadisticas_hoy->co2_evitado,
            'puntos_otorgados' => $estadisticas_hoy->puntos_otorgados,
            'fecha_calculo' => current_time('mysql'),
        ]);
    }

    /**
     * Formatea punto para respuesta
     */
    private function formatear_punto_respuesta($punto) {
        return [
            'id' => $punto->id,
            'nombre' => $punto->nombre,
            'descripcion' => $punto->descripcion,
            'direccion' => $punto->direccion,
            'latitud' => floatval($punto->latitud),
            'longitud' => floatval($punto->longitud),
            'tipo' => $punto->tipo,
            'capacidad_litros' => intval($punto->capacidad_litros),
            'nivel_llenado_pct' => intval($punto->nivel_llenado_pct),
            'fase_actual' => $punto->fase_actual,
            'horario_apertura' => $punto->horario_apertura,
            'telefono_contacto' => $punto->telefono_contacto,
            'foto_url' => $punto->foto_url,
            'acepta_aportaciones' => in_array($punto->fase_actual, ['recepcion', 'activo']),
            'distancia_km' => isset($punto->distancia_km) ? round($punto->distancia_km, 2) : null,
        ];
    }

    /**
     * Obtiene estadisticas de un punto
     */
    private function obtener_estadisticas_punto($punto_id) {
        global $wpdb;
        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_aportaciones,
                COALESCE(SUM(cantidad_kg), 0) as total_kg,
                COUNT(DISTINCT usuario_id) as usuarios_participantes
            FROM $tabla_aportaciones WHERE punto_id = %d AND validado = 1",
            $punto_id
        ));
    }

    /**
     * Obtiene turnos de un punto
     */
    private function obtener_turnos_punto($punto_id, $limite = 5) {
        global $wpdb;
        $tabla_turnos = $wpdb->prefix . 'flavor_turnos_compostaje';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_turnos
            WHERE punto_id = %d AND fecha_turno >= CURDATE() AND estado IN ('abierto', 'completo')
            ORDER BY fecha_turno ASC LIMIT %d",
            $punto_id, $limite
        ));
    }

    // ========== AJAX HANDLERS ==========

    /**
     * AJAX: Registrar aportacion
     */
    public function ajax_registrar_aportacion() {
        check_ajax_referer('compostaje_nonce', 'nonce');

        $punto_id = intval($_POST['punto_id'] ?? 0);
        $tipo_material = sanitize_text_field($_POST['tipo_material'] ?? '');
        $cantidad_kg = floatval($_POST['cantidad_kg'] ?? 0);
        $notas = sanitize_textarea_field($_POST['notas'] ?? '');

        $resultado = $this->registrar_aportacion($punto_id, $tipo_material, $cantidad_kg, $notas);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Apuntarse a turno
     */
    public function ajax_apuntarse_turno() {
        check_ajax_referer('compostaje_nonce', 'nonce');

        $turno_id = intval($_POST['turno_id'] ?? 0);
        $resultado = $this->inscribir_usuario_turno($turno_id);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Consultar estado
     */
    public function ajax_consultar_estado() {
        $punto_id = intval($_GET['punto_id'] ?? $_POST['punto_id'] ?? 0);

        if (!$punto_id) {
            wp_send_json(['success' => false, 'error' => __('ID de punto requerido', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

        $punto = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_puntos WHERE id = %d", $punto_id));

        if (!$punto) {
            wp_send_json(['success' => false, 'error' => __('ID de punto requerido', 'flavor-chat-ia')]);
        }

        wp_send_json([
            'success' => true,
            'punto' => $this->formatear_punto_respuesta($punto),
            'estadisticas' => $this->obtener_estadisticas_punto($punto_id),
        ]);
    }

    /**
     * AJAX: Obtener puntos del usuario
     */
    public function ajax_obtener_puntos_compostaje() {
        check_ajax_referer('compostaje_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json(['success' => false, 'error' => __('ID de punto requerido', 'flavor-chat-ia')]);
        }

        $estadisticas = $this->obtener_estadisticas_usuario($usuario_id);
        wp_send_json(['success' => true, 'estadisticas' => $estadisticas]);
    }

    /**
     * AJAX: Mis aportaciones
     */
    public function ajax_mis_aportaciones() {
        check_ajax_referer('compostaje_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json(['success' => false, 'error' => __('ID de punto requerido', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';
        $tabla_materiales = $wpdb->prefix . 'flavor_materiales_compostables';

        $pagina = intval($_POST['pagina'] ?? 1);
        $limite = 10;
        $offset = ($pagina - 1) * $limite;

        $aportaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, p.nombre as nombre_punto, m.nombre as nombre_material
            FROM $tabla_aportaciones a
            LEFT JOIN $tabla_puntos p ON a.punto_id = p.id
            LEFT JOIN $tabla_materiales m ON a.tipo_material = m.codigo
            WHERE a.usuario_id = %d
            ORDER BY a.fecha_aportacion DESC
            LIMIT %d OFFSET %d",
            $usuario_id, $limite, $offset
        ));

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_aportaciones WHERE usuario_id = %d",
            $usuario_id
        ));

        wp_send_json([
            'success' => true,
            'aportaciones' => $aportaciones,
            'total' => intval($total),
            'paginas' => ceil($total / $limite),
            'pagina_actual' => $pagina,
        ]);
    }

    /**
     * AJAX: Cancelar turno
     */
    public function ajax_cancelar_turno() {
        check_ajax_referer('compostaje_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        $turno_id = intval($_POST['turno_id'] ?? 0);

        if (!$usuario_id || !$turno_id) {
            wp_send_json(['success' => false, 'error' => __('No estas inscrito en este turno', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_inscripciones_turno';
        $tabla_turnos = $wpdb->prefix . 'flavor_turnos_compostaje';

        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_inscripciones WHERE turno_id = %d AND usuario_id = %d AND estado = 'inscrito'",
            $turno_id, $usuario_id
        ));

        if (!$inscripcion) {
            wp_send_json(['success' => false, 'error' => __('ID de punto requerido', 'flavor-chat-ia')]);
        }

        $wpdb->update($tabla_inscripciones, ['estado' => 'cancelado'], ['id' => $inscripcion->id]);
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_turnos SET plazas_ocupadas = plazas_ocupadas - 1, estado = 'abierto' WHERE id = %d",
            $turno_id
        ));

        wp_send_json(['success' => true, 'mensaje' => __('turno_id', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Completar turno (admin)
     */
    public function ajax_completar_turno() {
        check_ajax_referer('compostaje_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json(['success' => false, 'error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $turno_id = intval($_POST['turno_id'] ?? 0);
        $asistentes = isset($_POST['asistentes']) ? array_map('intval', $_POST['asistentes']) : [];

        global $wpdb;
        $tabla_turnos = $wpdb->prefix . 'flavor_turnos_compostaje';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_inscripciones_turno';

        $turno = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_turnos WHERE id = %d", $turno_id));

        if (!$turno) {
            wp_send_json(['success' => false, 'error' => __('ID de punto requerido', 'flavor-chat-ia')]);
        }

        foreach ($asistentes as $usuario_id) {
            $wpdb->update($tabla_inscripciones,
                ['estado' => 'asistio', 'puntos_obtenidos' => $turno->puntos_recompensa],
                ['turno_id' => $turno_id, 'usuario_id' => $usuario_id]
            );
        }

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_inscripciones SET estado = 'no_asistio'
            WHERE turno_id = %d AND estado = 'inscrito'",
            $turno_id
        ));

        $wpdb->update($tabla_turnos, ['estado' => 'completado'], ['id' => $turno_id]);

        wp_send_json(['success' => true, 'mensaje' => __('dias_aviso_turno', 'flavor-chat-ia')]);
    }

    /**
     * Envia notificaciones de turnos proximos
     */
    public function enviar_notificaciones_turnos() {
        global $wpdb;
        $tabla_turnos = $wpdb->prefix . 'flavor_turnos_compostaje';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_inscripciones_turno';
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

        $configuracion = $this->get_settings();
        $dias_aviso = $configuracion['dias_aviso_turno'] ?? 2;

        $fecha_objetivo = date('Y-m-d', strtotime("+{$dias_aviso} days"));

        $turnos_proximos = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, p.nombre as nombre_punto
            FROM $tabla_turnos t
            LEFT JOIN $tabla_puntos p ON t.punto_id = p.id
            WHERE t.fecha_turno = %s AND t.estado IN ('abierto', 'completo')",
            $fecha_objetivo
        ));

        foreach ($turnos_proximos as $turno) {
            $inscritos = $wpdb->get_results($wpdb->prepare(
                "SELECT usuario_id FROM $tabla_inscripciones WHERE turno_id = %d AND estado = 'inscrito'",
                $turno->id
            ));

            foreach ($inscritos as $inscrito) {
                $usuario = get_userdata($inscrito->usuario_id);
                if ($usuario && $usuario->user_email) {
                    $asunto = sprintf(__('Recordatorio: Turno de compostaje en %s', 'flavor-chat-ia'), $turno->nombre_punto);
                    $mensaje = sprintf(
                        __("Hola %s,\n\nTe recordamos que tienes un turno de %s programado para el %s de %s a %s en %s.\n\nGracias por tu participacion!", 'flavor-chat-ia'),
                        $usuario->display_name,
                        $turno->tipo_tarea,
                        date_i18n('l j F', strtotime($turno->fecha_turno)),
                        $turno->hora_inicio,
                        $turno->hora_fin,
                        $turno->nombre_punto
                    );
                    wp_mail($usuario->user_email, $asunto, $mensaje);
                }
            }
        }
    }

    // ========== SHORTCODES ==========

    /**
     * Shortcode: Mapa de composteras
     */
    public function shortcode_mapa_composteras($atts) {
        $atributos = shortcode_atts([
            'altura' => 500,
            'tipo' => '',
            'mostrar_filtros' => 'true',
        ], $atts);

        ob_start();
        ?>
        <div class="flavor-compostaje-mapa-container" data-tipo="<?php echo esc_attr($atributos['tipo']); ?>">
            <?php if ($atributos['mostrar_filtros'] === 'true'): ?>
            <div class="flavor-compostaje-filtros">
                <select id="filtro-tipo-punto" class="flavor-select">
                    <option value=""><?php _e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('comunitario', 'flavor-chat-ia'); ?>"><?php _e('Comunitario', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('vecinal', 'flavor-chat-ia'); ?>"><?php _e('Vecinal', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('escolar', 'flavor-chat-ia'); ?>"><?php _e('Escolar', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('municipal', 'flavor-chat-ia'); ?>"><?php _e('Municipal', 'flavor-chat-ia'); ?></option>
                </select>
                <button type="button" id="btn-mi-ubicacion" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-location"></span>
                    <?php _e('Mi ubicacion', 'flavor-chat-ia'); ?>
                </button>
            </div>
            <?php endif; ?>

            <div id="mapa-composteras" style="height: <?php echo intval($atributos['altura']); ?>px;"></div>

            <div id="lista-puntos-compostaje" class="flavor-compostaje-lista"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Registrar aportacion
     */
    public function shortcode_registrar_aportacion($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-aviso flavor-aviso-info">' .
                   __('Debes iniciar sesion para registrar aportaciones.', 'flavor-chat-ia') .
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Iniciar sesion', 'flavor-chat-ia') . '</a></div>';
        }

        $atributos = shortcode_atts([
            'punto_id' => 0,
        ], $atts);

        global $wpdb;
        $tabla_materiales = $wpdb->prefix . 'flavor_materiales_compostables';
        $materiales = $wpdb->get_results("SELECT * FROM $tabla_materiales WHERE activo = 1 ORDER BY categoria, orden");

        ob_start();
        ?>
        <div class="flavor-compostaje-form-container">
            <h3><?php _e('Registrar Aportacion', 'flavor-chat-ia'); ?></h3>

            <form id="form-aportacion-compost" class="flavor-form">
                <?php wp_nonce_field('compostaje_nonce', 'compostaje_nonce_field'); ?>

                <div class="flavor-form-group">
                    <label for="punto-compostaje"><?php _e('Punto de compostaje', 'flavor-chat-ia'); ?></label>
                    <select id="punto-compostaje" name="punto_id" required class="flavor-select">
                        <option value=""><?php _e('Selecciona un punto...', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="tipo-material"><?php _e('Tipo de material', 'flavor-chat-ia'); ?></label>
                    <select id="tipo-material" name="tipo_material" required class="flavor-select">
                        <option value=""><?php _e('Selecciona el tipo...', 'flavor-chat-ia'); ?></option>
                        <optgroup label="<?php _e('Materiales verdes (ricos en nitrogeno)', 'flavor-chat-ia'); ?>">
                            <?php foreach ($materiales as $material): ?>
                                <?php if ($material->categoria === 'verde'): ?>
                                <option value="<?php echo esc_attr($material->codigo); ?>" data-puntos="<?php echo esc_attr($material->puntos_por_kg); ?>">
                                    <?php echo esc_html($material->nombre); ?> (<?php echo $material->puntos_por_kg; ?> pts/kg)
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="<?php _e('Materiales marrones (ricos en carbono)', 'flavor-chat-ia'); ?>">
                            <?php foreach ($materiales as $material): ?>
                                <?php if ($material->categoria === 'marron'): ?>
                                <option value="<?php echo esc_attr($material->codigo); ?>" data-puntos="<?php echo esc_attr($material->puntos_por_kg); ?>">
                                    <?php echo esc_html($material->nombre); ?> (<?php echo $material->puntos_por_kg; ?> pts/kg)
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="<?php _e('Materiales especiales', 'flavor-chat-ia'); ?>">
                            <?php foreach ($materiales as $material): ?>
                                <?php if ($material->categoria === 'especial'): ?>
                                <option value="<?php echo esc_attr($material->codigo); ?>" data-puntos="<?php echo esc_attr($material->puntos_por_kg); ?>">
                                    <?php echo esc_html($material->nombre); ?> (<?php echo $material->puntos_por_kg; ?> pts/kg)
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="cantidad-kg"><?php _e('Cantidad (kg)', 'flavor-chat-ia'); ?></label>
                    <input type="number" id="cantidad-kg" name="cantidad_kg" min="0.1" max="10" step="0.1" required class="flavor-input">
                    <small class="flavor-form-help"><?php _e('Maximo 10 kg por aportacion', 'flavor-chat-ia'); ?></small>
                </div>

                <div class="flavor-form-group">
                    <label for="notas-aportacion"><?php _e('Notas (opcional)', 'flavor-chat-ia'); ?></label>
                    <textarea id="notas-aportacion" name="notas" rows="2" class="flavor-textarea"></textarea>
                </div>

                <div id="preview-puntos" class="flavor-compostaje-preview" style="display:none;">
                    <div class="preview-item">
                        <span class="preview-label"><?php _e('Puntos estimados:', 'flavor-chat-ia'); ?></span>
                        <span id="puntos-estimados" class="preview-value">0</span>
                    </div>
                    <div class="preview-item">
                        <span class="preview-label"><?php _e('CO2 evitado:', 'flavor-chat-ia'); ?></span>
                        <span id="co2-estimado" class="preview-value"><?php echo esc_html__('0 kg', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>

                <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-block">
                    <?php _e('Registrar Aportacion', 'flavor-chat-ia'); ?>
                </button>
            </form>

            <div id="resultado-aportacion" class="flavor-resultado" style="display:none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis aportaciones
     */
    public function shortcode_mis_aportaciones($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-aviso flavor-aviso-info">' .
                   __('Debes iniciar sesion para ver tus aportaciones.', 'flavor-chat-ia') . '</div>';
        }

        $usuario_id = get_current_user_id();
        $estadisticas = $this->obtener_estadisticas_usuario($usuario_id);

        ob_start();
        ?>
        <div class="flavor-compostaje-perfil">
            <div class="flavor-compostaje-nivel-card">
                <div class="nivel-icono nivel-<?php echo esc_attr($estadisticas['nivel']['icono']); ?>"></div>
                <div class="nivel-info">
                    <h3><?php echo esc_html($estadisticas['nivel']['nombre']); ?></h3>
                    <span class="nivel-numero"><?php printf(__('Nivel %d', 'flavor-chat-ia'), $estadisticas['nivel']['numero']); ?></span>
                </div>
                <?php if ($estadisticas['proximo_nivel']): ?>
                <div class="nivel-progreso">
                    <div class="progreso-barra">
                        <div class="progreso-llenado" style="width: <?php echo esc_attr($estadisticas['proximo_nivel']['progreso_porcentaje']); ?>%"></div>
                    </div>
                    <small><?php printf(__('%.1f kg para nivel %s', 'flavor-chat-ia'), $estadisticas['proximo_nivel']['kg_necesarios'], $estadisticas['proximo_nivel']['nombre']); ?></small>
                </div>
                <?php endif; ?>
            </div>

            <div class="flavor-compostaje-stats-grid">
                <div class="stat-card">
                    <span class="stat-valor"><?php echo number_format($estadisticas['total_kg'], 1); ?></span>
                    <span class="stat-label"><?php _e('kg aportados', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-valor"><?php echo number_format($estadisticas['total_puntos']); ?></span>
                    <span class="stat-label"><?php _e('puntos ganados', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-valor"><?php echo number_format($estadisticas['total_co2_evitado'], 1); ?></span>
                    <span class="stat-label"><?php _e('kg CO2 evitado', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-valor"><?php echo $estadisticas['total_aportaciones']; ?></span>
                    <span class="stat-label"><?php _e('aportaciones', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <div class="flavor-compostaje-historial">
                <h4><?php _e('Historial de aportaciones', 'flavor-chat-ia'); ?></h4>
                <div id="lista-aportaciones" class="flavor-lista-aportaciones">
                    <div class="flavor-cargando"><?php _e('Cargando...', 'flavor-chat-ia'); ?></div>
                </div>
                <div id="paginacion-aportaciones" class="flavor-paginacion"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Guia de compostaje
     */
    public function shortcode_guia_compostaje($atts) {
        $atributos = shortcode_atts([
            'estilo' => 'tarjetas',
        ], $atts);

        ob_start();
        ?>
        <div class="flavor-compostaje-guia flavor-guia-<?php echo esc_attr($atributos['estilo']); ?>">
            <div class="guia-seccion guia-si">
                <h3 class="guia-titulo guia-titulo-si">
                    <span class="guia-icono">&#10004;</span>
                    <?php _e('Si se puede compostar', 'flavor-chat-ia'); ?>
                </h3>
                <div class="guia-items">
                    <div class="guia-categoria">
                        <h4><?php _e('Materiales verdes (nitrogeno)', 'flavor-chat-ia'); ?></h4>
                        <ul>
                            <li><?php _e('Restos de frutas y verduras', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Posos de cafe y filtros de papel', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Bolsas de te (sin grapas)', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Cesped recien cortado', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Restos de plantas verdes', 'flavor-chat-ia'); ?></li>
                        </ul>
                    </div>
                    <div class="guia-categoria">
                        <h4><?php _e('Materiales marrones (carbono)', 'flavor-chat-ia'); ?></h4>
                        <ul>
                            <li><?php _e('Hojas secas', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Papel y carton sin tintas', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Ramas pequenas trituradas', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Serrin de madera no tratada', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Paja', 'flavor-chat-ia'); ?></li>
                        </ul>
                    </div>
                    <div class="guia-categoria">
                        <h4><?php _e('Otros', 'flavor-chat-ia'); ?></h4>
                        <ul>
                            <li><?php _e('Cascaras de huevo trituradas', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Pelo y plumas', 'flavor-chat-ia'); ?></li>
                            <li><?php _e('Ceniza de madera (poca cantidad)', 'flavor-chat-ia'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="guia-seccion guia-no">
                <h3 class="guia-titulo guia-titulo-no">
                    <span class="guia-icono">&#10008;</span>
                    <?php _e('No se puede compostar', 'flavor-chat-ia'); ?>
                </h3>
                <div class="guia-items">
                    <ul>
                        <li><?php _e('Carne, pescado y huesos', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Lacteos y grasas', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Plantas enfermas o con plagas', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Excrementos de mascotas', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Ceniza de carbon o briquetas', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Madera tratada o pintada', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Plasticos y sinteticos', 'flavor-chat-ia'); ?></li>
                        <li><?php _e('Citricos en grandes cantidades', 'flavor-chat-ia'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="guia-seccion guia-consejos">
                <h3 class="guia-titulo"><?php _e('Consejos para un buen compost', 'flavor-chat-ia'); ?></h3>
                <div class="guia-consejos-grid">
                    <div class="consejo-card">
                        <span class="consejo-numero">1</span>
                        <p><?php _e('Equilibra verdes y marrones (proporcion 1:2)', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="consejo-card">
                        <span class="consejo-numero">2</span>
                        <p><?php _e('Corta los materiales en trozos pequenos', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="consejo-card">
                        <span class="consejo-numero">3</span>
                        <p><?php _e('Mantener humedad como esponja escurrida', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="consejo-card">
                        <span class="consejo-numero">4</span>
                        <p><?php _e('Voltear regularmente para airear', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Ranking de compostaje
     */
    public function shortcode_ranking_compostaje($atts) {
        $atributos = shortcode_atts([
            'limite' => 10,
            'periodo' => 'total',
        ], $atts);

        $ranking = $this->obtener_ranking_usuarios($atributos['limite'], $atributos['periodo']);

        ob_start();
        ?>
        <div class="flavor-compostaje-ranking">
            <div class="ranking-filtros">
                <button type="button" class="ranking-filtro <?php echo $atributos['periodo'] === 'total' ? 'activo' : ''; ?>" data-periodo="total">
                    <?php _e('Total', 'flavor-chat-ia'); ?>
                </button>
                <button type="button" class="ranking-filtro <?php echo $atributos['periodo'] === 'mes' ? 'activo' : ''; ?>" data-periodo="mes">
                    <?php _e('Este mes', 'flavor-chat-ia'); ?>
                </button>
                <button type="button" class="ranking-filtro <?php echo $atributos['periodo'] === 'semana' ? 'activo' : ''; ?>" data-periodo="semana">
                    <?php _e('Esta semana', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <div id="lista-ranking" class="ranking-lista">
                <?php foreach ($ranking as $entrada): ?>
                <div class="ranking-item ranking-posicion-<?php echo $entrada['posicion']; ?>">
                    <span class="ranking-pos"><?php echo $entrada['posicion']; ?></span>
                    <img src="<?php echo esc_url($entrada['avatar']); ?>" alt="" class="ranking-avatar">
                    <div class="ranking-info">
                        <span class="ranking-nombre"><?php echo esc_html($entrada['nombre']); ?></span>
                        <span class="ranking-nivel"><?php echo esc_html($entrada['nivel_nombre']); ?></span>
                    </div>
                    <div class="ranking-stats">
                        <span class="ranking-kg"><?php echo number_format($entrada['total_kg'], 1); ?> kg</span>
                        <span class="ranking-puntos"><?php echo number_format($entrada['total_puntos']); ?> pts</span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($ranking)): ?>
                <div class="ranking-vacio"><?php _e('Aun no hay datos de ranking', 'flavor-chat-ia'); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Estadisticas globales
     */
    public function shortcode_estadisticas_compostaje($atts) {
        $estadisticas = $this->obtener_estadisticas_globales();

        ob_start();
        ?>
        <div class="flavor-compostaje-estadisticas-globales">
            <div class="stats-hero">
                <h2><?php _e('Impacto de nuestra comunidad', 'flavor-chat-ia'); ?></h2>
            </div>

            <div class="stats-grid">
                <div class="stat-card stat-card-destacado">
                    <div class="stat-icono icono-kg"></div>
                    <span class="stat-valor contador" data-valor="<?php echo $estadisticas['total_kg']; ?>">0</span>
                    <span class="stat-label"><?php _e('kg de organicos compostados', 'flavor-chat-ia'); ?></span>
                </div>

                <div class="stat-card stat-card-destacado">
                    <div class="stat-icono icono-co2"></div>
                    <span class="stat-valor contador" data-valor="<?php echo $estadisticas['total_co2_evitado']; ?>">0</span>
                    <span class="stat-label"><?php _e('kg de CO2 evitados', 'flavor-chat-ia'); ?></span>
                </div>

                <div class="stat-card">
                    <span class="stat-valor"><?php echo number_format($estadisticas['usuarios_activos']); ?></span>
                    <span class="stat-label"><?php _e('composteros activos', 'flavor-chat-ia'); ?></span>
                </div>

                <div class="stat-card">
                    <span class="stat-valor"><?php echo number_format($estadisticas['puntos_activos']); ?></span>
                    <span class="stat-label"><?php _e('puntos de compostaje', 'flavor-chat-ia'); ?></span>
                </div>

                <div class="stat-card">
                    <span class="stat-valor"><?php echo number_format($estadisticas['total_aportaciones']); ?></span>
                    <span class="stat-label"><?php _e('aportaciones realizadas', 'flavor-chat-ia'); ?></span>
                </div>

                <div class="stat-card">
                    <span class="stat-valor"><?php echo number_format($estadisticas['turnos_completados']); ?></span>
                    <span class="stat-label"><?php _e('turnos de mantenimiento', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <div class="stats-mes">
                <h4><?php _e('Este mes', 'flavor-chat-ia'); ?></h4>
                <div class="stats-mes-grid">
                    <div class="stat-mes-item">
                        <span class="stat-mes-valor"><?php echo number_format($estadisticas['estadisticas_mes']['kg'], 1); ?></span>
                        <span class="stat-mes-label"><?php _e('kg aportados', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="stat-mes-item">
                        <span class="stat-mes-valor"><?php echo number_format($estadisticas['estadisticas_mes']['aportaciones']); ?></span>
                        <span class="stat-mes-label"><?php _e('aportaciones', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Turnos de compostaje
     */
    public function shortcode_turnos_compostaje($atts) {
        $atributos = shortcode_atts([
            'punto_id' => 0,
            'dias' => 30,
        ], $atts);

        ob_start();
        ?>
        <div class="flavor-compostaje-turnos" data-punto="<?php echo intval($atributos['punto_id']); ?>" data-dias="<?php echo intval($atributos['dias']); ?>">
            <div class="turnos-filtros">
                <select id="filtro-punto-turno" class="flavor-select">
                    <option value=""><?php _e('Todos los puntos', 'flavor-chat-ia'); ?></option>
                </select>
                <select id="filtro-tipo-turno" class="flavor-select">
                    <option value=""><?php _e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('volteo', 'flavor-chat-ia'); ?>"><?php _e('Volteo', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('riego', 'flavor-chat-ia'); ?>"><?php _e('Riego', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('medicion', 'flavor-chat-ia'); ?>"><?php _e('Medicion', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('tamizado', 'flavor-chat-ia'); ?>"><?php _e('Tamizado', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('limpieza', 'flavor-chat-ia'); ?>"><?php _e('Limpieza', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div id="calendario-turnos" class="turnos-calendario">
                <div class="flavor-cargando"><?php _e('Cargando turnos...', 'flavor-chat-ia'); ?></div>
            </div>

            <div id="lista-turnos" class="turnos-lista"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    // ========== ACCIONES DEL MODULO (CHAT) ==========

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'puntos_compostaje_cercanos' => [
                'description' => 'Encontrar puntos de compostaje cercanos',
                'params' => ['lat', 'lng', 'radio'],
            ],
            'estado_punto_compostaje' => [
                'description' => 'Ver estado de un punto de compostaje',
                'params' => ['punto_id'],
            ],
            'registrar_aportacion_compost' => [
                'description' => 'Registrar aportacion de material organico',
                'params' => ['punto_id', 'tipo_material', 'cantidad_kg'],
            ],
            'mis_estadisticas_compostaje' => [
                'description' => 'Ver mis estadisticas de compostaje',
                'params' => [],
            ],
            'turnos_disponibles' => [
                'description' => 'Ver turnos de mantenimiento disponibles',
                'params' => ['punto_id'],
            ],
            'apuntarse_turno' => [
                'description' => 'Apuntarse a un turno de mantenimiento',
                'params' => ['turno_id'],
            ],
            'guia_materiales' => [
                'description' => 'Guia de materiales compostables',
                'params' => [],
            ],
            'ranking_composteros' => [
                'description' => 'Ver ranking de composteros',
                'params' => ['periodo'],
            ],
            'impacto_ambiental' => [
                'description' => 'Ver impacto ambiental del compostaje',
                'params' => [],
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
            'error' => "Accion no implementada: {$action_name}",
        ];
    }

    /**
     * Accion: Puntos cercanos
     */
    private function action_puntos_compostaje_cercanos($params) {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

        $latitud = floatval($params['lat'] ?? 0);
        $longitud = floatval($params['lng'] ?? 0);
        $radio = floatval($params['radio'] ?? 10);

        if ($latitud && $longitud) {
            $puntos = $wpdb->get_results($wpdb->prepare(
                "SELECT *,
                (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia_km
                FROM $tabla_puntos
                WHERE estado = 'activo'
                HAVING distancia_km <= %f
                ORDER BY distancia_km ASC
                LIMIT 20",
                $latitud, $longitud, $latitud, $radio
            ));
        } else {
            $puntos = $wpdb->get_results("SELECT * FROM $tabla_puntos WHERE estado = 'activo' ORDER BY nombre LIMIT 20");
        }

        return [
            'success' => true,
            'puntos' => array_map([$this, 'formatear_punto_respuesta'], $puntos),
            'total' => count($puntos),
        ];
    }

    /**
     * Accion: Mis estadisticas
     */
    private function action_mis_estadisticas_compostaje($params) {
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return ['success' => false, 'error' => __('Usuario no autenticado', 'flavor-chat-ia')];
        }

        $estadisticas = $this->obtener_estadisticas_usuario($usuario_id);

        return [
            'success' => true,
            'estadisticas' => $estadisticas,
        ];
    }

    /**
     * Accion: Guia de materiales
     */
    private function action_guia_materiales($params) {
        global $wpdb;
        $tabla_materiales = $wpdb->prefix . 'flavor_materiales_compostables';

        $materiales = $wpdb->get_results("SELECT * FROM $tabla_materiales WHERE activo = 1 ORDER BY categoria, orden");

        $guia = [
            'compostables' => [
                'verdes' => [],
                'marrones' => [],
                'especiales' => [],
            ],
            'no_compostables' => [
                'Carne, pescado y huesos',
                'Lacteos y grasas',
                'Plantas enfermas',
                'Excrementos de mascotas',
                'Ceniza de carbon',
                'Madera tratada',
                'Plasticos',
            ],
            'consejos' => [
                'Equilibra materiales verdes y marrones (1:2)',
                'Corta en trozos pequenos',
                'Mantener humedad adecuada',
                'Voltear regularmente',
            ],
        ];

        foreach ($materiales as $material) {
            $categoria = $material->categoria === 'verde' ? 'verdes' :
                        ($material->categoria === 'marron' ? 'marrones' : 'especiales');
            $guia['compostables'][$categoria][] = [
                'nombre' => $material->nombre,
                'descripcion' => $material->descripcion,
                'puntos_kg' => $material->puntos_por_kg,
                'consejos' => $material->consejos,
            ];
        }

        return [
            'success' => true,
            'guia' => $guia,
        ];
    }

    /**
     * Accion: Impacto ambiental
     */
    private function action_impacto_ambiental($params) {
        $estadisticas = $this->obtener_estadisticas_globales();

        $equivalencias_co2 = [
            'km_coche' => $estadisticas['total_co2_evitado'] * 6,
            'arboles_ano' => $estadisticas['total_co2_evitado'] / 22,
            'vuelos_madrid_barcelona' => $estadisticas['total_co2_evitado'] / 140,
        ];

        return [
            'success' => true,
            'impacto' => [
                'total_kg_compostados' => $estadisticas['total_kg'],
                'co2_evitado_kg' => $estadisticas['total_co2_evitado'],
                'equivalencias' => $equivalencias_co2,
                'usuarios_participantes' => $estadisticas['usuarios_activos'],
            ],
        ];
    }

    // ========== COMPONENTES WEB ==========

    /**
     * {@inheritdoc}
     */
    public function get_web_components() {
        return [
            'hero_compostaje' => [
                'label' => __('Hero Compostaje', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-carrot',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Compostaje Comunitario', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Convierte residuos organicos en abono natural', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_impacto' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'compostaje/hero',
            ],
            'mapa_composteras' => [
                'label' => __('Mapa de Puntos', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Encuentra tu Punto de Compostaje', 'flavor-chat-ia')],
                    'altura_mapa' => ['type' => 'number', 'default' => 500],
                    'mostrar_filtros' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'compostaje/mapa',
            ],
            'formulario_aportacion' => [
                'label' => __('Formulario Aportacion', 'flavor-chat-ia'),
                'category' => 'forms',
                'icon' => 'dashicons-plus-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Registra tu Aportacion', 'flavor-chat-ia')],
                    'punto_fijo' => ['type' => 'number', 'default' => 0],
                ],
                'template' => 'compostaje/formulario',
            ],
            'estadisticas_usuario' => [
                'label' => __('Mis Estadisticas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'mostrar_nivel' => ['type' => 'toggle', 'default' => true],
                    'mostrar_historial' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'compostaje/estadisticas-usuario',
            ],
            'ranking_compostaje' => [
                'label' => __('Ranking Composteros', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-awards',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Top Composteros', 'flavor-chat-ia')],
                    'limite' => ['type' => 'number', 'default' => 10],
                    'periodo' => ['type' => 'select', 'options' => ['total', 'mes', 'semana'], 'default' => 'total'],
                ],
                'template' => 'compostaje/ranking',
            ],
            'guia_compostaje' => [
                'label' => __('Guia de Compostaje', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-book',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Que Compostar', 'flavor-chat-ia')],
                    'estilo' => ['type' => 'select', 'options' => ['lista', 'tarjetas'], 'default' => 'tarjetas'],
                ],
                'template' => 'compostaje/guia',
            ],
            'calendario_turnos' => [
                'label' => __('Calendario de Turnos', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Turnos de Mantenimiento', 'flavor-chat-ia')],
                    'dias_adelante' => ['type' => 'number', 'default' => 30],
                ],
                'template' => 'compostaje/turnos',
            ],
            'impacto_global' => [
                'label' => __('Impacto Global', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-chart-area',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Nuestro Impacto', 'flavor-chat-ia')],
                    'mostrar_equivalencias' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'compostaje/impacto',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'compostaje_puntos',
                'description' => 'Encontrar puntos de compostaje comunitario cercanos',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lat' => ['type' => 'number', 'description' => 'Latitud'],
                        'lng' => ['type' => 'number', 'description' => 'Longitud'],
                        'radio' => ['type' => 'number', 'description' => 'Radio en km'],
                    ],
                ],
            ],
            [
                'name' => 'compostaje_registrar',
                'description' => 'Registrar una aportacion de material organico',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'punto_id' => ['type' => 'integer', 'description' => 'ID del punto'],
                        'tipo_material' => ['type' => 'string', 'description' => 'Tipo de material'],
                        'cantidad_kg' => ['type' => 'number', 'description' => 'Cantidad en kg'],
                    ],
                    'required' => ['punto_id', 'tipo_material', 'cantidad_kg'],
                ],
            ],
            [
                'name' => 'compostaje_estadisticas',
                'description' => 'Obtener estadisticas de compostaje del usuario',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
            [
                'name' => 'compostaje_turnos',
                'description' => 'Ver turnos de mantenimiento disponibles',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'punto_id' => ['type' => 'integer', 'description' => 'ID del punto (opcional)'],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Compostaje Comunitario - Sistema Completo**

El sistema de compostaje comunitario permite a los usuarios participar en la transformacion de residuos organicos en abono natural de calidad.

**Funcionalidades principales:**

1. **Puntos de compostaje**: Ubicaciones fisicas donde depositar material organico
   - Tipos: comunitario, vecinal, escolar, municipal, privado
   - Fases: recepcion, activo, maduracion, listo, mantenimiento
   - Informacion de capacidad, nivel de llenado, horarios

2. **Sistema de aportaciones**:
   - Registro de material organico por tipo y cantidad
   - Materiales verdes (nitrogeno): frutas, verduras, posos cafe, cesped
   - Materiales marrones (carbono): hojas secas, papel, carton, ramas
   - Calculo automatico de puntos y CO2 evitado

3. **Gamificacion por niveles**:
   - Nivel 1 Semilla: 0 kg
   - Nivel 2 Brote: 10 kg
   - Nivel 3 Planta: 50 kg
   - Nivel 4 Arbol: 150 kg
   - Nivel 5 Bosque: 500 kg
   - Nivel 6 Ecosistema: 1000 kg
   - Bonus de puntos segun nivel

4. **Turnos de mantenimiento**:
   - Volteo, riego, medicion, tamizado, limpieza
   - Sistema de inscripcion
   - Puntos de recompensa por participacion

5. **Estadisticas e impacto**:
   - kg totales compostados
   - CO2 evitado
   - Ranking de usuarios
   - Historial personal

**Que SI compostar:**
- Restos frutas y verduras
- Posos cafe y te
- Cesped y plantas
- Hojas secas
- Papel y carton sin tintas
- Cascaras huevo

**Que NO compostar:**
- Carne, pescado, huesos
- Lacteos y grasas
- Plantas enfermas
- Excrementos mascotas
- Madera tratada
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Como registro mi aportacion de compost?',
                'respuesta' => 'Accede al formulario de aportacion, selecciona el punto de compostaje, el tipo de material y la cantidad en kg. Obtendras puntos automaticamente.',
            ],
            [
                'pregunta' => '¿Como funcionan los niveles de compostaje?',
                'respuesta' => 'Subes de nivel segun los kg aportados. Cada nivel desbloquea bonus de puntos: desde Semilla (0kg) hasta Ecosistema (1000kg).',
            ],
            [
                'pregunta' => '¿Que son los turnos de mantenimiento?',
                'respuesta' => 'Son tareas colectivas para cuidar las composteras: volteo, riego, medicion. Apuntate y gana puntos extra por participar.',
            ],
            [
                'pregunta' => '¿Cuanto CO2 evito al compostar?',
                'respuesta' => 'Aproximadamente 0.5 kg de CO2 por cada kg de material organico compostado en lugar de ir al vertedero.',
            ],
            [
                'pregunta' => '¿Puedo compostar citricos?',
                'respuesta' => 'Si, pero en pequenas cantidades. Su acidez puede ralentizar el proceso si hay exceso.',
            ],
        ];
    }

    /**
     * Configuracion de paginas de administracion para el Panel Unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'compostaje',
            'label' => __('Compostaje', 'flavor-chat-ia'),
            'icon' => 'dashicons-carrot',
            'capability' => 'manage_options',
            'categoria' => 'sostenibilidad',
            'paginas' => [
                [
                    'slug' => 'flavor-compostaje-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'flavor-compostaje-composteras',
                    'titulo' => __('Composteras', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_composteras'],
                ],
                [
                    'slug' => 'flavor-compostaje-participantes',
                    'titulo' => __('Participantes', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_participantes'],
                ],
            ],
        ];
    }

    /**
     * Renderiza el dashboard de administracion de compostaje
     */
    public function render_admin_dashboard() {
        $this->render_page_header(__('Dashboard de Compostaje', 'flavor-chat-ia'));

        $estadisticas_generales = $this->obtener_estadisticas_generales();

        include dirname(__FILE__) . '/views/admin-dashboard.php';
    }

    /**
     * Renderiza la pagina de gestion de composteras
     */
    public function render_admin_composteras() {
        $acciones = [
            [
                'label' => __('Nueva Compostera', 'flavor-chat-ia'),
                'url' => admin_url('admin.php?page=flavor-compostaje-composteras&action=nueva'),
                'class' => 'button-primary',
            ],
        ];

        $this->render_page_header(__('Gestion de Composteras', 'flavor-chat-ia'), $acciones);

        $composteras = $this->obtener_composteras();

        include dirname(__FILE__) . '/views/admin-composteras.php';
    }

    /**
     * Renderiza la pagina de gestion de participantes
     */
    public function render_admin_participantes() {
        $this->render_page_header(__('Participantes del Compostaje', 'flavor-chat-ia'));

        $participantes = $this->obtener_ranking_participantes();

        include dirname(__FILE__) . '/views/admin-participantes.php';
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }

    /**
     * Crea páginas frontend automáticamente
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('compostaje');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('compostaje');
        if (!$pagina && !get_option('flavor_compostaje_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['compostaje']);
            update_option('flavor_compostaje_pages_created', 1, false);
        }
    }
}
