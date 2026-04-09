<?php
/**
 * Modulo de Participacion Ciudadana para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Participacion Ciudadana - Votaciones, encuestas, propuestas y presupuestos participativos
 */
class Flavor_Chat_Participacion_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Consumer;

    /**
     * Version del modulo
     */
    const VERSION = '2.0.0';

    /**
     * Categorias de propuestas disponibles
     */
    private $categorias_propuesta = [
        'urbanismo' => 'Urbanismo y espacios publicos',
        'movilidad' => 'Movilidad y transporte',
        'medio_ambiente' => 'Medio ambiente',
        'cultura' => 'Cultura y ocio',
        'servicios' => 'Servicios municipales',
        'seguridad' => 'Seguridad ciudadana',
        'educacion' => 'Educacion',
        'deportes' => 'Deportes',
        'social' => 'Bienestar social',
        'economia' => 'Economia local',
        'tecnologia' => 'Tecnologia e innovacion',
        'otros' => 'Otros',
    ];

    /**
     * Estados de propuesta
     */
    private $estados_propuesta = [
        'borrador' => 'Borrador',
        'pendiente_validacion' => 'Pendiente de validacion',
        'activa' => 'Activa',
        'en_estudio' => 'En estudio',
        'aprobada' => 'Aprobada',
        'rechazada' => 'Rechazada',
        'implementada' => 'Implementada',
        'archivada' => 'Archivada',
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'participacion';
        $this->name = 'Participacion Ciudadana'; // Translation loaded on init
        $this->description = 'Votaciones, encuestas, propuestas, presupuestos participativos y consultas ciudadanas.'; // Translation loaded on init
        $this->module_role = 'transversal';
        $this->ecosystem_governs_modules = ['energia_comunitaria', 'comunidades', 'presupuestos_participativos'];
        $this->dashboard_transversal_priority = 10;
        $this->dashboard_client_contexts = ['participacion', 'gobernanza', 'comunidad'];
        $this->dashboard_admin_contexts = ['gobernanza', 'participacion', 'admin'];

        // Principios Gailu que implementa este modulo
        $this->gailu_principios = ['gobernanza'];
        $this->gailu_contribuye_a = ['autonomia', 'cohesion'];

        parent::__construct();

        // Admin pages
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
        return Flavor_Chat_Helpers::tabla_existe($tabla_propuestas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Participacion no estan creadas. Activa el modulo para crearlas automaticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
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
            'requiere_verificacion' => true,
            'votos_necesarios_propuesta' => 10,
            'permite_propuestas_ciudadanas' => true,
            'moderacion_propuestas' => true,
            'duracion_votacion_dias' => 7,
            'quorum_minimo' => 0,
            'max_propuestas_usuario_mes' => 5,
            'permitir_comentarios' => true,
            'permitir_comentarios_anonimos' => false,
            'notificar_nuevas_propuestas' => true,
            'notificar_votos' => true,
            'presupuesto_participativo_activo' => false,
            'presupuesto_total_anual' => 100000,
            'max_presupuesto_propuesta' => 50000,
            'fases_participacion' => [
                'recogida' => ['nombre' => 'Recogida de propuestas', 'duracion_dias' => 30],
                'debate' => ['nombre' => 'Debate ciudadano', 'duracion_dias' => 15],
                'votacion' => ['nombre' => 'Votacion', 'duracion_dias' => 15],
                'evaluacion' => ['nombre' => 'Evaluacion tecnica', 'duracion_dias' => 30],
                'implementacion' => ['nombre' => 'Implementacion', 'duracion_dias' => 90],
            ],
        ];
    }

    /**
     * Define que tipos de contenido acepta este modulo
     *
     * @return array IDs de providers aceptados
     */
    protected function get_accepted_integrations() {
        return ['multimedia', 'articulos_social'];
    }

    /**
     * Define donde se muestran los metaboxes de integracion
     *
     * @return array Configuracion de targets
     */
    protected function get_integration_targets() {
        global $wpdb;
        return [
            [
                'type'    => 'table',
                'table'   => $wpdb->prefix . 'flavor_participacion_propuestas',
                'context' => 'side',
            ],
        ];
    }

    public function get_ecosystem_metadata() {
        $metadata = parent::get_ecosystem_metadata();
        $metadata['module_role'] = 'transversal';
        $metadata['governs_modules'] = ['energia_comunitaria', 'comunidades', 'presupuestos_participativos'];

        return $metadata;
    }

    /**
     * Configuración del renderer para navegación moderna del portal.
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'participacion',
            'title'    => __('Participación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitle' => __('Propuestas, votaciones, debates y peticiones ciudadanas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => '🗳️',
            'color'    => 'blue',
            'database' => [
                'table'         => 'flavor_propuestas',
                'status_field'  => 'estado',
                'order_by'      => 'fecha_creacion DESC',
                'filter_fields' => ['categoria', 'estado'],
            ],
            'fields' => [
                'titulo'      => 'titulo',
                'descripcion' => 'descripcion',
                'estado'      => 'estado',
                'fecha'       => 'fecha_creacion',
                'tipo'        => 'categoria',
            ],
            'tabs' => [
                'propuestas' => [
                    'label'   => __('Iniciativas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-lightbulb',
                    'content' => '[propuestas_activas]',
                    'public'  => true,
                ],
                'crear' => [
                    'label'          => __('Nueva propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'           => 'dashicons-plus-alt2',
                    'content'        => '[crear_propuesta]',
                    'requires_login' => true,
                    'hidden_nav'     => true,
                ],
                'votaciones' => [
                    'label'   => __('Decisiones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-thumbs-up',
                    'content' => '[participacion_encuestas]',
                    'public'  => true,
                ],
                'resultados' => [
                    'label'   => __('Acuerdos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-chart-bar',
                    'content' => '[resultados_participacion]',
                    'public'  => true,
                ],
                'debates' => [
                    'label'   => __('Conversaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-admin-comments',
                    'content' => '[participacion_debates]',
                    'public'  => true,
                ],
                'detalle' => [
                    'label'      => __('Detalle', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-visibility',
                    'content'    => '[detalle_propuesta]',
                    'public'     => true,
                    'hidden_nav' => true,
                ],
                'encuesta' => [
                    'label'      => __('Encuesta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-chart-bar',
                    'content'    => '[participacion_encuesta]',
                    'public'     => true,
                    'hidden_nav' => true,
                ],
                'peticiones' => [
                    'label'      => __('Peticiones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-megaphone',
                    'content'    => '[participacion_peticiones]',
                    'public'     => true,
                    'hidden_nav' => true,
                ],
                'peticion' => [
                    'label'      => __('Petición', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-megaphone',
                    'content'    => '[participacion_peticion]',
                    'public'     => true,
                    'hidden_nav' => true,
                ],
                'crear-peticion' => [
                    'label'          => __('Crear petición', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'           => 'dashicons-plus-alt',
                    'content'        => '[participacion_crear_peticion]',
                    'requires_login' => true,
                    'hidden_nav'     => true,
                ],
                'debate' => [
                    'label'      => __('Debate', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'       => 'dashicons-format-chat',
                    'content'    => '[participacion_debate]',
                    'public'     => true,
                    'hidden_nav' => true,
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        $this->register_as_integration_consumer();
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_migrate_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_participacion_crear_propuesta', [$this, 'ajax_crear_propuesta']);
        add_action('wp_ajax_participacion_apoyar_propuesta', [$this, 'ajax_apoyar_propuesta']);
        add_action('wp_ajax_participacion_votar', [$this, 'ajax_votar']);
        add_action('wp_ajax_participacion_comentar', [$this, 'ajax_comentar']);
        add_action('wp_ajax_participacion_filtrar_propuestas', [$this, 'ajax_filtrar_propuestas']);
        add_action('wp_ajax_nopriv_participacion_filtrar_propuestas', [$this, 'ajax_filtrar_propuestas']);
        add_action('wp_ajax_participacion_cargar_propuestas', [$this, 'ajax_cargar_propuestas']);
        add_action('wp_ajax_nopriv_participacion_cargar_propuestas', [$this, 'ajax_cargar_propuestas']);
        add_action('wp_ajax_participacion_resultados_votacion', [$this, 'ajax_resultados_votacion']);
        add_action('wp_ajax_nopriv_participacion_resultados_votacion', [$this, 'ajax_resultados_votacion']);
        add_action('wp_ajax_participacion_like_comentario', [$this, 'ajax_like_comentario']);
        add_action('wp_ajax_participacion_cargar_comentarios', [$this, 'ajax_cargar_comentarios']);
        add_action('wp_ajax_nopriv_participacion_cargar_comentarios', [$this, 'ajax_cargar_comentarios']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Cron para actualizar estados
        if (!wp_next_scheduled('flavor_participacion_actualizar_estados')) {
            wp_schedule_event(time(), 'hourly', 'flavor_participacion_actualizar_estados');
        }
        add_action('flavor_participacion_actualizar_estados', [$this, 'actualizar_estados_automaticos']);

        // Registrar en Panel Unificado de Gestión
        
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();
        $this->registrar_en_panel_unificado();
        $this->cargar_frontend_controller();
    }

    /**
     * Carga el controlador frontend del módulo.
     */
    private function cargar_frontend_controller() {
        $archivo_controller = dirname(__FILE__) . '/frontend/class-participacion-frontend-controller.php';
        if (file_exists($archivo_controller)) {
            require_once $archivo_controller;
            Flavor_Participacion_Frontend_Controller::get_instance();
        }
    }

    /**
     * Registrar assets
     */
    public function enqueue_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $ruta_modulo = plugin_dir_url(__FILE__);

        wp_enqueue_style(
            'flavor-participacion',
            $ruta_modulo . 'assets/css/participacion.css',
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'flavor-participacion',
            $ruta_modulo . 'assets/js/participacion.js',
            ['jquery'],
            self::VERSION,
            true
        );

        wp_localize_script('flavor-participacion', 'flavorParticipacionConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_participacion_nonce'),
            'strings' => [
                'error_conexion' => __('Error de conexion. Intentalo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmar_voto' => __('Confirmar voto', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Determinar si cargar assets
     */
    private function should_load_assets() {
        global $post;
        if (!$post) {
            return false;
        }

        $shortcodes_modulo = ['propuestas_activas', 'crear_propuesta', 'votacion_activa', 'resultados_participacion', 'fases_participacion', 'presupuesto_participativo'];

        foreach ($shortcodes_modulo as $shortcode) {
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
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_propuestas)) {
            $this->create_tables();
        }
    }

    /**
     * Verifica y aplica migraciones de base de datos
     */
    public function maybe_migrate_tables() {
        $version_migracion = get_option('flavor_participacion_migration_version', '1.0.0');

        if (version_compare($version_migracion, '1.1.0', '<')) {
            $this->migrate_to_1_1_0();
            update_option('flavor_participacion_migration_version', '1.1.0');
        }
    }

    /**
     * Migración a versión 1.1.0 - Agregar columna total_apoyos
     */
    private function migrate_to_1_1_0() {
        global $wpdb;

        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_propuestas)) {
            return;
        }

        // Verificar y añadir total_apoyos
        $existe = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM $tabla_propuestas LIKE %s", 'total_apoyos'));
        if (empty($existe)) {
            $wpdb->query("ALTER TABLE $tabla_propuestas ADD COLUMN total_apoyos int(11) DEFAULT 0 AFTER votos_abstencion");
        }

        // Verificar y añadir total_comentarios
        $existe = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM $tabla_propuestas LIKE %s", 'total_comentarios'));
        if (empty($existe)) {
            $wpdb->query("ALTER TABLE $tabla_propuestas ADD COLUMN total_comentarios int(11) DEFAULT 0 AFTER total_apoyos");
        }

        // Recalcular total_apoyos desde la tabla de apoyos
        $tabla_apoyos = $wpdb->prefix . 'flavor_apoyos';
        if (Flavor_Chat_Helpers::tabla_existe($tabla_apoyos)) {
            $wpdb->query("
                UPDATE $tabla_propuestas p
                SET p.total_apoyos = (
                    SELECT COUNT(*) FROM $tabla_apoyos a WHERE a.propuesta_id = p.id
                )
            ");
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
        $tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';
        $tabla_votos = $wpdb->prefix . 'flavor_votos';
        $tabla_apoyos = $wpdb->prefix . 'flavor_apoyos';
        $tabla_comentarios = $wpdb->prefix . 'flavor_comentarios_propuesta';
        $tabla_fases = $wpdb->prefix . 'flavor_fases_participacion';

        $sql_propuestas = "CREATE TABLE IF NOT EXISTS $tabla_propuestas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion longtext NOT NULL,
            resumen text DEFAULT NULL,
            categoria varchar(50) DEFAULT 'general',
            proponente_id bigint(20) unsigned DEFAULT NULL,
            estado enum('borrador','pendiente_validacion','activa','en_estudio','aprobada','rechazada','implementada','archivada') DEFAULT 'pendiente_validacion',
            tipo enum('propuesta','consulta','iniciativa','presupuesto') DEFAULT 'propuesta',
            votos_favor int(11) DEFAULT 0,
            votos_contra int(11) DEFAULT 0,
            votos_abstencion int(11) DEFAULT 0,
            total_apoyos int(11) DEFAULT 0,
            total_comentarios int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_publicacion datetime DEFAULT NULL,
            fecha_finalizacion datetime DEFAULT NULL,
            fecha_actualizacion datetime DEFAULT NULL,
            ambito varchar(100) DEFAULT NULL,
            ubicacion_lat decimal(10,8) DEFAULT NULL,
            ubicacion_lng decimal(11,8) DEFAULT NULL,
            direccion varchar(255) DEFAULT NULL,
            presupuesto_estimado decimal(12,2) DEFAULT NULL,
            presupuesto_asignado decimal(12,2) DEFAULT NULL,
            documentos longtext DEFAULT NULL,
            imagenes longtext DEFAULT NULL,
            etiquetas varchar(500) DEFAULT NULL,
            respuesta_oficial text DEFAULT NULL,
            fecha_respuesta datetime DEFAULT NULL,
            motivo_rechazo text DEFAULT NULL,
            fase_actual varchar(50) DEFAULT NULL,
            prioridad tinyint(1) DEFAULT 0,
            destacada tinyint(1) DEFAULT 0,
            visitas int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY proponente_id (proponente_id),
            KEY estado (estado),
            KEY categoria (categoria),
            KEY tipo (tipo),
            KEY fecha_creacion (fecha_creacion),
            KEY destacada (destacada),
            FULLTEXT KEY busqueda (titulo, descripcion)
        ) $charset_collate;";

        $sql_votaciones = "CREATE TABLE IF NOT EXISTS $tabla_votaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            tipo enum('referendum','consulta','encuesta','presupuesto') DEFAULT 'consulta',
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime NOT NULL,
            estado enum('programada','activa','finalizada','cancelada') DEFAULT 'programada',
            opciones longtext NOT NULL,
            total_votos int(11) DEFAULT 0,
            es_anonima tinyint(1) DEFAULT 1,
            permite_multiples tinyint(1) DEFAULT 0,
            max_opciones int(11) DEFAULT 1,
            quorum_minimo int(11) DEFAULT 0,
            creado_por bigint(20) unsigned DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            resultados_publicos tinyint(1) DEFAULT 1,
            mostrar_resultados_durante tinyint(1) DEFAULT 0,
            ambito varchar(100) DEFAULT NULL,
            propuesta_id bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio),
            KEY fecha_fin (fecha_fin),
            KEY propuesta_id (propuesta_id)
        ) $charset_collate;";

        $sql_votos = "CREATE TABLE IF NOT EXISTS $tabla_votos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            votacion_id bigint(20) unsigned DEFAULT NULL,
            propuesta_id bigint(20) unsigned DEFAULT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            voto varchar(100) NOT NULL,
            opciones_seleccionadas longtext DEFAULT NULL,
            es_anonimo tinyint(1) DEFAULT 1,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            fecha_voto datetime DEFAULT CURRENT_TIMESTAMP,
            hash_verificacion varchar(64) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY votacion_usuario (votacion_id, usuario_id),
            UNIQUE KEY propuesta_usuario (propuesta_id, usuario_id),
            KEY usuario_id (usuario_id),
            KEY fecha_voto (fecha_voto)
        ) $charset_collate;";

        $sql_apoyos = "CREATE TABLE IF NOT EXISTS $tabla_apoyos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            propuesta_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo enum('apoyo','retiro') DEFAULT 'apoyo',
            comentario text DEFAULT NULL,
            fecha_apoyo datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY propuesta_usuario (propuesta_id, usuario_id),
            KEY propuesta_id (propuesta_id),
            KEY usuario_id (usuario_id),
            KEY fecha_apoyo (fecha_apoyo)
        ) $charset_collate;";

        $sql_comentarios = "CREATE TABLE IF NOT EXISTS $tabla_comentarios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            propuesta_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned DEFAULT NULL,
            comentario_padre_id bigint(20) unsigned DEFAULT NULL,
            contenido text NOT NULL,
            estado enum('pendiente','aprobado','rechazado','spam') DEFAULT 'aprobado',
            likes int(11) DEFAULT 0,
            es_oficial tinyint(1) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY propuesta_id (propuesta_id),
            KEY usuario_id (usuario_id),
            KEY comentario_padre_id (comentario_padre_id),
            KEY estado (estado),
            KEY fecha_creacion (fecha_creacion)
        ) $charset_collate;";

        $sql_fases = "CREATE TABLE IF NOT EXISTS $tabla_fases (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            slug varchar(50) NOT NULL,
            orden int(11) DEFAULT 0,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime NOT NULL,
            estado enum('pendiente','activa','finalizada') DEFAULT 'pendiente',
            proceso_id bigint(20) unsigned DEFAULT NULL,
            anio int(4) DEFAULT NULL,
            color varchar(7) DEFAULT '#2563eb',
            icono varchar(50) DEFAULT 'dashicons-calendar',
            configuracion longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY proceso_id (proceso_id),
            KEY estado (estado),
            KEY anio (anio),
            KEY orden (orden)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_propuestas);
        dbDelta($sql_votaciones);
        dbDelta($sql_votos);
        dbDelta($sql_apoyos);
        dbDelta($sql_comentarios);
        dbDelta($sql_fases);
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('propuestas_activas', [$this, 'shortcode_propuestas_activas']);
        add_shortcode('crear_propuesta', [$this, 'shortcode_crear_propuesta']);
        add_shortcode('votacion_activa', [$this, 'shortcode_votacion_activa']);
        add_shortcode('resultados_participacion', [$this, 'shortcode_resultados']);
        add_shortcode('fases_participacion', [$this, 'shortcode_fases']);
        add_shortcode('presupuesto_participativo', [$this, 'shortcode_presupuesto']);
        add_shortcode('detalle_propuesta', [$this, 'shortcode_detalle_propuesta']);
    }

    /**
     * Shortcode: Propuestas activas
     */
    public function shortcode_propuestas_activas($atts) {
        $atributos = shortcode_atts([
            'limite' => 12,
            'categoria' => '',
            'estado' => 'activa',
            'orden' => 'apoyos',
            'mostrar_filtros' => 'true',
            'columnas' => 3,
        ], $atts);

        $propuestas = $this->obtener_propuestas([
            'estado' => $atributos['estado'],
            'categoria' => $atributos['categoria'],
            'limite' => intval($atributos['limite']),
            'orden' => $atributos['orden'],
        ]);

        ob_start();
        ?>
        <div class="participacion-container">
            <?php if ($atributos['mostrar_filtros'] === 'true'): ?>
            <div class="filtros-participacion">
                <div class="filtro-grupo">
                    <label for="filtro_categoria"><?php esc_html_e('Categoria:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="filtro_categoria" class="filtro-propuestas">
                        <option value=""><?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($this->categorias_propuesta as $clave_categoria => $nombre_categoria): ?>
                        <option value="<?php echo esc_attr($clave_categoria); ?>"><?php echo esc_html($nombre_categoria); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-grupo">
                    <label for="filtro_orden"><?php esc_html_e('Ordenar:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="filtro_orden" class="filtro-propuestas">
                        <option value="<?php echo esc_attr__('apoyos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php esc_html_e('Mas apoyos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php esc_html_e('Mas recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php esc_html_e('Mas comentados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
                <div class="filtro-busqueda filtro-busqueda-propuestas">
                    <input type="text" placeholder="<?php esc_attr_e('Buscar propuestas...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($propuestas)): ?>
            <div class="sin-resultados">
                <div class="sin-resultados-icono">&#128161;</div>
                <h3><?php esc_html_e('No hay propuestas activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php esc_html_e('Se el primero en crear una propuesta para tu comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <?php else: ?>
            <div class="propuestas-grid">
                <?php foreach ($propuestas as $propuesta): ?>
                <?php echo $this->render_propuesta_card($propuesta); ?>
                <?php endforeach; ?>
            </div>

            <?php if (count($propuestas) >= intval($atributos['limite'])): ?>
            <div class="cargar-mas-container" style="text-align: center; margin-top: 2rem;">
                <button class="btn-participacion btn-participacion-outline btn-cargar-mas-propuestas" data-pagina="1">
                    <?php esc_html_e('Cargar mas propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar tarjeta de propuesta
     */
    private function render_propuesta_card($propuesta) {
        $usuario = get_userdata($propuesta->proponente_id);
        $nombre_usuario = $usuario ? $usuario->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $avatar = get_avatar_url($propuesta->proponente_id, ['size' => 32]);
        $ya_apoyo = $this->usuario_ya_apoyo($propuesta->id);
        $categoria_nombre = isset($this->categorias_propuesta[$propuesta->categoria]) ? $this->categorias_propuesta[$propuesta->categoria] : $propuesta->categoria;

        ob_start();
        ?>
        <article class="propuesta-card" data-propuesta-id="<?php echo esc_attr($propuesta->id); ?>">
            <div class="propuesta-header">
                <span class="propuesta-categoria"><?php echo esc_html($categoria_nombre); ?></span>
                <h3 class="propuesta-titulo">
                    <a href="<?php echo esc_url(add_query_arg('propuesta_id', $propuesta->id, home_url('/mi-portal/participacion/detalle/'))); ?>">
                        <?php echo esc_html($propuesta->titulo); ?>
                    </a>
                </h3>
            </div>
            <p class="propuesta-descripcion">
                <?php echo esc_html(wp_trim_words($propuesta->descripcion, 25)); ?>
            </p>
            <div class="propuesta-meta">
                <span class="propuesta-autor">
                    <img src="<?php echo esc_url($avatar); ?>" alt="">
                    <?php echo esc_html($nombre_usuario); ?>
                </span>
                <span class="propuesta-fecha">
                    <?php echo esc_html(date_i18n('d M Y', strtotime($propuesta->fecha_creacion))); ?>
                </span>
            </div>
            <div class="propuesta-stats">
                <div class="propuesta-stat">
                    <span class="propuesta-stat-valor votos-favor-valor"><?php echo esc_html($propuesta->total_apoyos); ?></span>
                    <span class="propuesta-stat-label"><?php esc_html_e('Apoyos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="propuesta-stat">
                    <span class="propuesta-stat-valor"><?php echo esc_html($propuesta->total_comentarios); ?></span>
                    <span class="propuesta-stat-label"><?php esc_html_e('Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <?php if ($propuesta->presupuesto_estimado): ?>
                <div class="propuesta-stat">
                    <span class="propuesta-stat-valor"><?php echo esc_html(number_format($propuesta->presupuesto_estimado, 0, ',', '.')); ?></span>
                    <span class="propuesta-stat-label"><?php esc_html_e('EUR', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="propuesta-acciones">
                <button class="btn-participacion btn-participacion-primary btn-apoyar <?php echo $ya_apoyo ? 'apoyado' : ''; ?>"
                        data-propuesta-id="<?php echo esc_attr($propuesta->id); ?>"
                        <?php echo $ya_apoyo ? 'disabled' : ''; ?>>
                    <?php echo $ya_apoyo ? '&#10003; ' . esc_html__('Apoyada', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Apoyar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <a href="<?php echo esc_url(add_query_arg('propuesta_id', $propuesta->id, home_url('/mi-portal/participacion/detalle/'))); ?>"
                   class="btn-participacion btn-participacion-outline">
                    <?php esc_html_e('Ver mas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Crear propuesta
     */
    public function shortcode_crear_propuesta($atts) {
        if (!is_user_logged_in()) {
            return '<div class="alerta-participacion advertencia">' .
                   esc_html__('Debes iniciar sesion para crear una propuesta.', FLAVOR_PLATFORM_TEXT_DOMAIN) .
                   ' <a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '">' .
                   esc_html__('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a></div>';
        }

        $atributos = shortcode_atts([
            'tipo' => 'propuesta',
            'mostrar_presupuesto' => 'true',
        ], $atts);

        ob_start();
        ?>
        <div class="participacion-container">
            <form class="form-crear-propuesta" method="post">
                <h2><?php esc_html_e('Nueva Propuesta Ciudadana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

                <div class="form-grupo">
                    <label for="titulo"><?php esc_html_e('Titulo de la propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <input type="text" name="titulo" id="titulo" required
                           placeholder="<?php esc_attr_e('Escribe un titulo descriptivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                           minlength="10" maxlength="255">
                    <span class="form-ayuda"><?php esc_html_e('Minimo 10 caracteres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>

                <div class="form-grupo">
                    <label for="descripcion"><?php esc_html_e('Descripcion detallada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <textarea name="descripcion" id="descripcion" required
                              placeholder="<?php esc_attr_e('Describe tu propuesta con el mayor detalle posible...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                              minlength="50"></textarea>
                    <span class="form-ayuda"><?php esc_html_e('Minimo 50 caracteres. Explica que propones, por que es importante y como beneficiaria a la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>

                <div class="form-grupo-row">
                    <div class="form-grupo">
                        <label for="categoria"><?php esc_html_e('Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="categoria" id="categoria">
                            <?php foreach ($this->categorias_propuesta as $clave_categoria => $nombre_categoria): ?>
                            <option value="<?php echo esc_attr($clave_categoria); ?>"><?php echo esc_html($nombre_categoria); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-grupo">
                        <label for="ambito"><?php esc_html_e('Ambito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="ambito" id="ambito">
                            <option value="<?php echo esc_attr__('barrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php esc_html_e('Barrio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('distrito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php esc_html_e('Distrito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="<?php echo esc_attr__('ciudad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php esc_html_e('Ciudad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                </div>

                <?php if ($atributos['mostrar_presupuesto'] === 'true'): ?>
                <div class="form-grupo">
                    <label for="presupuesto_estimado"><?php esc_html_e('Presupuesto estimado (EUR)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="number" name="presupuesto_estimado" id="presupuesto_estimado"
                           min="0" max="<?php echo esc_attr($this->settings['max_presupuesto_propuesta']); ?>"
                           placeholder="0">
                    <span class="form-ayuda"><?php echo esc_html(sprintf(__('Maximo %s EUR', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($this->settings['max_presupuesto_propuesta'], 0, ',', '.'))); ?></span>
                </div>
                <?php endif; ?>

                <div class="form-grupo">
                    <button type="submit" class="btn-participacion btn-participacion-primary btn-participacion-lg btn-participacion-block">
                        <?php esc_html_e('Enviar Propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>

                <p class="form-ayuda" style="text-align: center;">
                    <?php esc_html_e('Tu propuesta sera revisada antes de publicarse.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Votacion activa
     */
    public function shortcode_votacion_activa($atts) {
        $atributos = shortcode_atts([
            'id' => 0,
            'mostrar_resultados' => 'false',
        ], $atts);

        $votacion_id = intval($atributos['id']);

        if ($votacion_id) {
            $votacion = $this->obtener_votacion($votacion_id);
        } else {
            $votacion = $this->obtener_votacion_activa();
        }

        if (!$votacion) {
            return '<div class="sin-resultados"><h3>' . esc_html__('No hay votaciones activas', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3></div>';
        }

        $opciones = json_decode($votacion->opciones, true);
        $ya_voto = $this->usuario_ya_voto_votacion($votacion->id);
        $mostrar_resultados = $votacion->estado === 'finalizada' || $votacion->mostrar_resultados_durante || $ya_voto;

        ob_start();
        ?>
        <div class="participacion-container">
            <div class="votacion-card" data-votacion-id="<?php echo esc_attr($votacion->id); ?>">
                <div class="votacion-header">
                    <span class="votacion-tipo <?php echo esc_attr($votacion->tipo); ?>">
                        <?php echo esc_html(ucfirst($votacion->tipo)); ?>
                    </span>
                    <h2 class="votacion-titulo"><?php echo esc_html($votacion->titulo); ?></h2>
                    <p class="votacion-descripcion"><?php echo esc_html($votacion->descripcion); ?></p>

                    <div class="votacion-tiempo <?php echo $votacion->estado === 'activa' ? 'activa' : 'finalizada'; ?>">
                        <?php if ($votacion->estado === 'activa'): ?>
                        <span class="votacion-contador" data-fecha-fin="<?php echo esc_attr($votacion->fecha_fin); ?>">
                            <?php esc_html_e('Calculando tiempo restante...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </span>
                        <?php else: ?>
                        <span><?php esc_html_e('Votacion finalizada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="votacion-opciones" data-permite-multiples="<?php echo $votacion->permite_multiples ? 'true' : 'false'; ?>">
                    <?php foreach ($opciones as $indice => $opcion): ?>
                    <?php
                        $porcentaje = 0;
                        $votos_opcion = 0;
                        if ($mostrar_resultados && $votacion->total_votos > 0) {
                            $votos_opcion = $this->contar_votos_opcion($votacion->id, $opcion['valor']);
                            $porcentaje = round(($votos_opcion / $votacion->total_votos) * 100, 1);
                        }
                    ?>
                    <div class="votacion-opcion <?php echo $ya_voto ? 'votada' : ''; ?>"
                         data-opcion="<?php echo esc_attr($opcion['valor']); ?>">
                        <div class="votacion-opcion-texto">
                            <?php echo esc_html($opcion['texto']); ?>
                            <?php if ($mostrar_resultados): ?>
                            <span class="votacion-opcion-porcentaje"><?php echo esc_html($porcentaje); ?>%</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($mostrar_resultados): ?>
                        <div class="votacion-opcion-barra">
                            <div class="votacion-opcion-fill" style="width: <?php echo esc_attr($porcentaje); ?>%"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="votacion-footer">
                    <span class="votacion-participantes">
                        <?php echo esc_html(sprintf(__('%d participantes', FLAVOR_PLATFORM_TEXT_DOMAIN), $votacion->total_votos)); ?>
                    </span>
                    <?php if (!$ya_voto && $votacion->estado === 'activa'): ?>
                    <button class="btn-participacion btn-participacion-primary btn-confirmar-voto" disabled>
                        <?php esc_html_e('Confirmar voto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <?php elseif ($ya_voto): ?>
                    <span class="alerta-participacion exito" style="margin: 0; padding: 0.5rem 1rem;">
                        <?php esc_html_e('Ya has votado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Fases de participacion
     */
    public function shortcode_fases($atts) {
        $atributos = shortcode_atts([
            'anio' => date('Y'),
            'proceso_id' => 0,
        ], $atts);

        $fases = $this->obtener_fases($atributos['anio'], intval($atributos['proceso_id']));

        if (empty($fases)) {
            $fases = $this->generar_fases_default($atributos['anio']);
        }

        $progreso = $this->calcular_progreso_participacion($atributos['anio']);

        ob_start();
        ?>
        <div class="participacion-container">
            <div class="progreso-participacion">
                <div class="progreso-header">
                    <span class="progreso-titulo"><?php echo esc_html(sprintf(__('Participacion %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $atributos['anio'])); ?></span>
                    <span class="progreso-porcentaje"><?php echo esc_html($progreso['porcentaje']); ?>%</span>
                </div>
                <div class="progreso-barra">
                    <div class="progreso-fill" data-porcentaje="<?php echo esc_attr($progreso['porcentaje']); ?>" style="width: 0%;"></div>
                </div>
                <div class="progreso-stats">
                    <div class="progreso-stat">
                        <span class="progreso-stat-valor"><?php echo esc_html($progreso['propuestas']); ?></span>
                        <span class="progreso-stat-label"><?php esc_html_e('Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="progreso-stat">
                        <span class="progreso-stat-valor"><?php echo esc_html($progreso['participantes']); ?></span>
                        <span class="progreso-stat-label"><?php esc_html_e('Participantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="progreso-stat">
                        <span class="progreso-stat-valor"><?php echo esc_html($progreso['votos']); ?></span>
                        <span class="progreso-stat-label"><?php esc_html_e('Votos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <div class="fases-participacion">
                <?php foreach ($fases as $fase): ?>
                <div class="fase-item <?php echo $fase->estado === 'activa' ? 'activa' : ($fase->estado === 'finalizada' ? 'completada' : ''); ?>"
                     data-fecha-inicio="<?php echo esc_attr($fase->fecha_inicio); ?>"
                     data-fecha-fin="<?php echo esc_attr($fase->fecha_fin); ?>">
                    <div class="fase-icono">
                        <span class="dashicons <?php echo esc_attr($fase->icono); ?>"></span>
                    </div>
                    <div class="fase-info">
                        <span class="fase-nombre"><?php echo esc_html($fase->nombre); ?></span>
                        <span class="fase-fechas">
                            <?php echo esc_html(date_i18n('d M', strtotime($fase->fecha_inicio))); ?> -
                            <?php echo esc_html(date_i18n('d M', strtotime($fase->fecha_fin))); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Presupuesto participativo
     */
    public function shortcode_presupuesto($atts) {
        $atributos = shortcode_atts([
            'anio' => date('Y'),
        ], $atts);

        $presupuesto_total = floatval($this->settings['presupuesto_total_anual']);
        $presupuesto_asignado = $this->calcular_presupuesto_asignado($atributos['anio']);
        $presupuesto_disponible = $presupuesto_total - $presupuesto_asignado;
        $porcentaje_usado = $presupuesto_total > 0 ? round(($presupuesto_asignado / $presupuesto_total) * 100, 1) : 0;

        $propuestas_presupuesto = $this->obtener_propuestas([
            'tipo' => 'presupuesto',
            'estado' => 'aprobada',
            'limite' => 10,
            'orden' => 'presupuesto',
        ]);

        ob_start();
        ?>
        <div class="participacion-container">
            <div class="presupuesto-info" data-presupuesto-total="<?php echo esc_attr($presupuesto_total); ?>">
                <h3 class="presupuesto-titulo"><?php echo esc_html(sprintf(__('Presupuesto Participativo %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $atributos['anio'])); ?></h3>
                <div class="presupuesto-cantidad"><?php echo esc_html(number_format($presupuesto_total, 0, ',', '.')); ?> EUR</div>
                <div class="presupuesto-progreso">
                    <div class="presupuesto-progreso-fill" style="width: <?php echo esc_attr($porcentaje_usado); ?>%"></div>
                </div>
                <div class="presupuesto-detalles">
                    <span class="presupuesto-asignado"><?php echo esc_html(sprintf(__('Asignado: %s EUR', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($presupuesto_asignado, 0, ',', '.'))); ?></span>
                    <span class="presupuesto-disponible"><?php echo esc_html(sprintf(__('Disponible: %s EUR', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($presupuesto_disponible, 0, ',', '.'))); ?></span>
                </div>
            </div>

            <?php if (!empty($propuestas_presupuesto)): ?>
            <h3><?php esc_html_e('Proyectos Aprobados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="propuestas-grid">
                <?php foreach ($propuestas_presupuesto as $propuesta): ?>
                <?php echo $this->render_propuesta_card($propuesta); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Resultados participacion
     */
    public function shortcode_resultados($atts) {
        $atributos = shortcode_atts([
            'anio' => date('Y'),
            'tipo' => 'all',
        ], $atts);

        $estadisticas = $this->obtener_estadisticas_participacion($atributos['anio']);
        $propuestas_destacadas = $this->obtener_propuestas([
            'estado' => 'implementada',
            'limite' => 6,
            'orden' => 'apoyos',
        ]);

        ob_start();
        ?>
        <div class="participacion-container">
            <div class="participacion-header">
                <h1><?php echo esc_html(sprintf(__('Resultados de Participacion %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $atributos['anio'])); ?></h1>
                <p><?php esc_html_e('Resumen de la participacion ciudadana este periodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div class="progreso-participacion">
                <div class="progreso-stats" style="border: none; padding: 0;">
                    <div class="progreso-stat">
                        <span class="progreso-stat-valor"><?php echo esc_html($estadisticas['total_propuestas']); ?></span>
                        <span class="progreso-stat-label"><?php esc_html_e('Propuestas Totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="progreso-stat">
                        <span class="progreso-stat-valor"><?php echo esc_html($estadisticas['propuestas_aprobadas']); ?></span>
                        <span class="progreso-stat-label"><?php esc_html_e('Aprobadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="progreso-stat">
                        <span class="progreso-stat-valor"><?php echo esc_html($estadisticas['propuestas_implementadas']); ?></span>
                        <span class="progreso-stat-label"><?php esc_html_e('Implementadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="progreso-stat">
                        <span class="progreso-stat-valor"><?php echo esc_html($estadisticas['total_participantes']); ?></span>
                        <span class="progreso-stat-label"><?php esc_html_e('Participantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="progreso-stat">
                        <span class="progreso-stat-valor"><?php echo esc_html(number_format($estadisticas['presupuesto_ejecutado'], 0, ',', '.')); ?></span>
                        <span class="progreso-stat-label"><?php esc_html_e('EUR Ejecutados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($propuestas_destacadas)): ?>
            <h3 style="margin-top: 2rem;"><?php esc_html_e('Propuestas Implementadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <div class="propuestas-grid">
                <?php foreach ($propuestas_destacadas as $propuesta): ?>
                <?php echo $this->render_propuesta_card($propuesta); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle propuesta
     */
    public function shortcode_detalle_propuesta($atts) {
        $atributos = shortcode_atts([
            'id' => 0,
        ], $atts);

        $propuesta_id = intval($atributos['id']) ?: intval($_GET['propuesta_id'] ?? 0);

        if (!$propuesta_id) {
            return '<div class="alerta-participacion error">' . esc_html__('Propuesta no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
        }

        $propuesta = $this->obtener_propuesta($propuesta_id);

        if (!$propuesta) {
            return '<div class="alerta-participacion error">' . esc_html__('Propuesta no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</div>';
        }

        // Incrementar visitas
        $this->incrementar_visitas($propuesta_id);

        $usuario = get_userdata($propuesta->proponente_id);
        $nombre_usuario = $usuario ? $usuario->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $ya_apoyo = $this->usuario_ya_apoyo($propuesta_id);
        $comentarios = $this->obtener_comentarios_propuesta($propuesta_id);
        $categoria_nombre = isset($this->categorias_propuesta[$propuesta->categoria]) ? $this->categorias_propuesta[$propuesta->categoria] : $propuesta->categoria;
        $estado_nombre = isset($this->estados_propuesta[$propuesta->estado]) ? $this->estados_propuesta[$propuesta->estado] : $propuesta->estado;

        ob_start();
        ?>
        <div class="participacion-container">
            <div class="propuesta-detalle">
                <div class="propuesta-detalle-header">
                    <span class="propuesta-detalle-estado <?php echo esc_attr($propuesta->estado); ?>">
                        <?php echo esc_html($estado_nombre); ?>
                    </span>
                    <h1 class="propuesta-detalle-titulo"><?php echo esc_html($propuesta->titulo); ?></h1>

                    <div class="propuesta-detalle-meta">
                        <span class="propuesta-detalle-meta-item">
                            <strong><?php esc_html_e('Autor:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html($nombre_usuario); ?>
                        </span>
                        <span class="propuesta-detalle-meta-item">
                            <strong><?php esc_html_e('Categoria:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html($categoria_nombre); ?>
                        </span>
                        <span class="propuesta-detalle-meta-item">
                            <strong><?php esc_html_e('Fecha:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html(date_i18n('d M Y', strtotime($propuesta->fecha_creacion))); ?>
                        </span>
                        <?php if ($propuesta->presupuesto_estimado): ?>
                        <span class="propuesta-detalle-meta-item">
                            <strong><?php esc_html_e('Presupuesto:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html(number_format($propuesta->presupuesto_estimado, 0, ',', '.')); ?> EUR
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="propuesta-detalle-descripcion">
                        <?php echo wp_kses_post(nl2br($propuesta->descripcion)); ?>
                    </div>

                    <?php if ($propuesta->respuesta_oficial): ?>
                    <div class="alerta-participacion info" style="margin-top: 1.5rem;">
                        <strong><?php esc_html_e('Respuesta oficial:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong><br>
                        <?php echo wp_kses_post($propuesta->respuesta_oficial); ?>
                    </div>
                    <?php endif; ?>

                    <div class="propuesta-detalle-acciones">
                        <button class="btn-participacion btn-participacion-primary btn-participacion-lg btn-apoyar <?php echo $ya_apoyo ? 'apoyado' : ''; ?>"
                                data-propuesta-id="<?php echo esc_attr($propuesta->id); ?>"
                                <?php echo $ya_apoyo ? 'disabled' : ''; ?>>
                            <?php echo $ya_apoyo ? '&#10003; ' . esc_html__('Apoyada', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Apoyar esta propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <span class="propuesta-stat" style="margin-left: 1rem;">
                            <span class="propuesta-stat-valor votos-favor-valor" style="font-size: 1.5rem;"><?php echo esc_html($propuesta->total_apoyos); ?></span>
                            <span class="propuesta-stat-label"><?php esc_html_e('Apoyos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </span>
                    </div>
                </div>

                <!-- Seccion de Comentarios -->
                <?php if ($this->settings['permitir_comentarios']): ?>
                <div class="comentarios-section" data-propuesta-id="<?php echo esc_attr($propuesta->id); ?>">
                    <div class="comentarios-header">
                        <h3 class="comentarios-titulo"><?php esc_html_e('Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <span class="comentarios-count"><?php echo esc_html($propuesta->total_comentarios); ?></span>
                    </div>

                    <?php if (is_user_logged_in()): ?>
                    <form class="form-comentario" data-propuesta-id="<?php echo esc_attr($propuesta->id); ?>">
                        <textarea placeholder="<?php esc_attr_e('Escribe tu comentario...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" rows="3"></textarea>
                        <button type="submit" class="btn-participacion btn-participacion-primary">
                            <?php esc_html_e('Comentar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="alerta-participacion info">
                        <?php esc_html_e('Inicia sesion para comentar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <a href="<?php echo esc_url(wp_login_url(flavor_current_request_url())); ?>"><?php esc_html_e('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                    </div>
                    <?php endif; ?>

                    <div class="comentarios-lista">
                        <?php foreach ($comentarios as $comentario): ?>
                        <?php echo $this->render_comentario($comentario); ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($comentarios) >= 10): ?>
                    <button class="btn-participacion btn-participacion-outline btn-cargar-comentarios"
                            data-propuesta-id="<?php echo esc_attr($propuesta->id); ?>" data-offset="10">
                        <?php esc_html_e('Cargar mas comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar comentario
     */
    private function render_comentario($comentario) {
        $usuario = get_userdata($comentario->usuario_id);
        $nombre_usuario = $usuario ? $usuario->display_name : __('Anonimo', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $avatar = get_avatar_url($comentario->usuario_id, ['size' => 40]);

        ob_start();
        ?>
        <div class="comentario-item" data-comentario-id="<?php echo esc_attr($comentario->id); ?>">
            <img src="<?php echo esc_url($avatar); ?>" alt="" class="comentario-avatar">
            <div class="comentario-contenido">
                <span class="comentario-autor">
                    <?php echo esc_html($nombre_usuario); ?>
                    <?php if ($comentario->es_oficial): ?>
                    <span class="badge-oficial"><?php esc_html_e('Oficial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <?php endif; ?>
                    <span class="comentario-fecha"><?php echo esc_html(human_time_diff(strtotime($comentario->fecha_creacion), current_time('timestamp'))); ?></span>
                </span>
                <p class="comentario-texto"><?php echo wp_kses_post(nl2br($comentario->contenido)); ?></p>
                <div class="comentario-acciones">
                    <button class="comentario-accion comentario-like" data-comentario-id="<?php echo esc_attr($comentario->id); ?>">
                        &#9825; <span class="likes-count"><?php echo esc_html($comentario->likes); ?></span>
                    </button>
                    <?php if (is_user_logged_in()): ?>
                    <button class="comentario-accion comentario-responder">
                        <?php esc_html_e('Responder', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="comentario-respuestas"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // AJAX Handlers
    // =========================================================================

    /**
     * AJAX: Crear propuesta
     */
    public function ajax_crear_propuesta() {
        check_ajax_referer('flavor_participacion_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['error' => __('Debes iniciar sesion.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar limite mensual
        if (!$this->puede_crear_propuesta($usuario_id)) {
            wp_send_json_error(['error' => __('Has alcanzado el limite de propuestas este mes.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');

        if (strlen($titulo) < 10) {
            wp_send_json_error(['error' => __('El titulo debe tener al menos 10 caracteres.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (strlen($descripcion) < 50) {
            wp_send_json_error(['error' => __('La descripcion debe tener al menos 50 caracteres.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        $estado_inicial = $this->settings['moderacion_propuestas'] ? 'pendiente_validacion' : 'activa';
        $presupuesto = floatval($_POST['presupuesto_estimado'] ?? 0);

        if ($presupuesto > $this->settings['max_presupuesto_propuesta']) {
            wp_send_json_error(['error' => sprintf(__('El presupuesto maximo es %s EUR.', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format($this->settings['max_presupuesto_propuesta'], 0, ',', '.'))]);
        }

        $resultado = $wpdb->insert(
            $tabla_propuestas,
            [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'categoria' => sanitize_text_field($_POST['categoria'] ?? 'otros'),
                'ambito' => sanitize_text_field($_POST['ambito'] ?? 'barrio'),
                'proponente_id' => $usuario_id,
                'estado' => $estado_inicial,
                'tipo' => $presupuesto > 0 ? 'presupuesto' : 'propuesta',
                'presupuesto_estimado' => $presupuesto ?: null,
                'fecha_creacion' => current_time('mysql'),
                'fecha_publicacion' => $estado_inicial === 'activa' ? current_time('mysql') : null,
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s', '%s', '%f', '%s', '%s']
        );

        if ($resultado === false) {
            wp_send_json_error(['error' => __('Error al crear la propuesta.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $mensaje = $estado_inicial === 'pendiente_validacion'
            ? __('Propuesta enviada. Esta pendiente de validacion.', FLAVOR_PLATFORM_TEXT_DOMAIN)
            : __('Propuesta publicada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);

        // Notificacion
        $propuesta_id = $wpdb->insert_id;
        if ($this->settings['notificar_nuevas_propuestas']) {
            $this->notificar_nueva_propuesta($propuesta_id, $titulo);
        }

        // Hook para sistema de reputación
        do_action('flavor_propuesta_creada', $usuario_id, $propuesta_id);

        wp_send_json_success([
            'propuesta_id' => $propuesta_id,
            'mensaje' => $mensaje,
        ]);
    }

    /**
     * AJAX: Apoyar propuesta
     */
    public function ajax_apoyar_propuesta() {
        check_ajax_referer('flavor_participacion_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['error' => __('Debes iniciar sesion.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $propuesta_id = absint($_POST['propuesta_id'] ?? 0);
        if (!$propuesta_id) {
            wp_send_json_error(['error' => __('Propuesta invalida.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if ($this->usuario_ya_apoyo($propuesta_id, $usuario_id)) {
            wp_send_json_error(['error' => __('Ya has apoyado esta propuesta.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_apoyos = $wpdb->prefix . 'flavor_apoyos';
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        $wpdb->insert(
            $tabla_apoyos,
            [
                'propuesta_id' => $propuesta_id,
                'usuario_id' => $usuario_id,
                'tipo' => 'apoyo',
                'fecha_apoyo' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_propuestas SET total_apoyos = total_apoyos + 1 WHERE id = %d",
            $propuesta_id
        ));

        $total_apoyos = $wpdb->get_var($wpdb->prepare(
            "SELECT total_apoyos FROM $tabla_propuestas WHERE id = %d",
            $propuesta_id
        ));

        wp_send_json_success([
            'votos_favor' => $total_apoyos,
            'mensaje' => sprintf(__('Gracias por tu apoyo. La propuesta tiene %d apoyos.', FLAVOR_PLATFORM_TEXT_DOMAIN), $total_apoyos),
        ]);
    }

    /**
     * AJAX: Votar
     */
    public function ajax_votar() {
        check_ajax_referer('flavor_participacion_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['error' => __('Debes iniciar sesion.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $votacion_id = absint($_POST['votacion_id'] ?? 0);
        $opciones = $_POST['opciones'] ?? [];

        if (!$votacion_id || empty($opciones)) {
            wp_send_json_error(['error' => __('Datos de votacion invalidos.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $votacion = $this->obtener_votacion($votacion_id);
        if (!$votacion || $votacion->estado !== 'activa') {
            wp_send_json_error(['error' => __('Esta votacion no esta activa.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if ($this->usuario_ya_voto_votacion($votacion_id, $usuario_id)) {
            wp_send_json_error(['error' => __('Ya has votado en esta votacion.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_votos';
        $tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';

        $opciones_array = is_array($opciones) ? $opciones : [$opciones];

        $wpdb->insert(
            $tabla_votos,
            [
                'votacion_id' => $votacion_id,
                'usuario_id' => $usuario_id,
                'voto' => $opciones_array[0],
                'opciones_seleccionadas' => json_encode($opciones_array),
                'es_anonimo' => $votacion->es_anonima,
                'fecha_voto' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'hash_verificacion' => wp_hash($usuario_id . $votacion_id . time()),
            ],
            ['%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_votaciones SET total_votos = total_votos + 1 WHERE id = %d",
            $votacion_id
        ));

        // Hook para sistema de reputación
        do_action('flavor_voto_emitido', $usuario_id, $votacion_id, $opciones_array[0]);

        // Obtener resultados actualizados
        $resultados = $this->obtener_resultados_votacion($votacion_id);

        wp_send_json_success([
            'mensaje' => __('Voto registrado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'resultados' => $resultados,
        ]);
    }

    /**
     * AJAX: Comentar
     */
    public function ajax_comentar() {
        check_ajax_referer('flavor_participacion_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id && !$this->settings['permitir_comentarios_anonimos']) {
            wp_send_json_error(['error' => __('Debes iniciar sesion.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $propuesta_id = absint($_POST['propuesta_id'] ?? 0);
        $contenido = sanitize_textarea_field($_POST['contenido'] ?? '');
        $comentario_padre_id = absint($_POST['comentario_padre_id'] ?? 0);

        if (!$propuesta_id || strlen($contenido) < 5) {
            wp_send_json_error(['error' => __('El comentario debe tener al menos 5 caracteres.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_comentarios = $wpdb->prefix . 'flavor_comentarios_propuesta';
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        $wpdb->insert(
            $tabla_comentarios,
            [
                'propuesta_id' => $propuesta_id,
                'usuario_id' => $usuario_id ?: null,
                'comentario_padre_id' => $comentario_padre_id ?: null,
                'contenido' => $contenido,
                'estado' => 'aprobado',
                'fecha_creacion' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s', '%s']
        );

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_propuestas SET total_comentarios = total_comentarios + 1 WHERE id = %d",
            $propuesta_id
        ));

        $comentario = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_comentarios WHERE id = %d",
            $wpdb->insert_id
        ));

        wp_send_json_success([
            'mensaje' => __('Comentario publicado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'html' => $this->render_comentario($comentario),
        ]);
    }

    /**
     * AJAX: Filtrar propuestas
     */
    public function ajax_filtrar_propuestas() {
        check_ajax_referer('flavor_participacion_nonce', 'nonce');

        $propuestas = $this->obtener_propuestas([
            'categoria' => sanitize_text_field($_POST['categoria'] ?? ''),
            'estado' => sanitize_text_field($_POST['estado'] ?? 'activa'),
            'orden' => sanitize_text_field($_POST['orden'] ?? 'apoyos'),
            'busqueda' => sanitize_text_field($_POST['busqueda'] ?? ''),
            'limite' => 12,
        ]);

        ob_start();
        foreach ($propuestas as $propuesta) {
            echo $this->render_propuesta_card($propuesta);
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * AJAX: Cargar mas propuestas
     */
    public function ajax_cargar_propuestas() {
        check_ajax_referer('flavor_participacion_nonce', 'nonce');

        $pagina = absint($_POST['pagina'] ?? 1);
        $limite = 12;
        $offset = ($pagina - 1) * $limite;

        $propuestas = $this->obtener_propuestas([
            'categoria' => sanitize_text_field($_POST['categoria'] ?? ''),
            'estado' => sanitize_text_field($_POST['estado'] ?? 'activa'),
            'limite' => $limite,
            'offset' => $offset,
        ]);

        ob_start();
        foreach ($propuestas as $propuesta) {
            echo $this->render_propuesta_card($propuesta);
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'tiene_mas' => count($propuestas) >= $limite,
        ]);
    }

    /**
     * AJAX: Resultados votacion
     */
    public function ajax_resultados_votacion() {
        check_ajax_referer('flavor_participacion_nonce', 'nonce');

        $votacion_id = absint($_POST['votacion_id'] ?? 0);
        $votacion = $this->obtener_votacion($votacion_id);

        if (!$votacion) {
            wp_send_json_error(['error' => __('Votacion no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $resultados = $this->obtener_resultados_votacion($votacion_id);
        $opciones_datos = json_decode($votacion->opciones, true);

        $opciones_resultado = [];
        foreach ($opciones_datos as $opcion) {
            $votos = $resultados[$opcion['valor']] ?? 0;
            $porcentaje = $votacion->total_votos > 0 ? round(($votos / $votacion->total_votos) * 100, 1) : 0;
            $opciones_resultado[] = [
                'texto' => $opcion['texto'],
                'votos' => $votos,
                'porcentaje' => $porcentaje,
            ];
        }

        usort($opciones_resultado, function($a, $b) {
            return $b['votos'] - $a['votos'];
        });

        wp_send_json_success([
            'titulo' => $votacion->titulo,
            'total_votos' => $votacion->total_votos,
            'opciones' => $opciones_resultado,
        ]);
    }

    /**
     * AJAX: Like comentario
     */
    public function ajax_like_comentario() {
        check_ajax_referer('flavor_participacion_nonce', 'nonce');

        $comentario_id = absint($_POST['comentario_id'] ?? 0);

        global $wpdb;
        $tabla_comentarios = $wpdb->prefix . 'flavor_comentarios_propuesta';

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_comentarios SET likes = likes + 1 WHERE id = %d",
            $comentario_id
        ));

        $total_likes = $wpdb->get_var($wpdb->prepare(
            "SELECT likes FROM $tabla_comentarios WHERE id = %d",
            $comentario_id
        ));

        wp_send_json_success(['total_likes' => $total_likes]);
    }

    /**
     * AJAX: Cargar comentarios
     */
    public function ajax_cargar_comentarios() {
        check_ajax_referer('flavor_participacion_nonce', 'nonce');

        $propuesta_id = absint($_POST['propuesta_id'] ?? 0);
        $offset = absint($_POST['offset'] ?? 0);

        $comentarios = $this->obtener_comentarios_propuesta($propuesta_id, 10, $offset);

        ob_start();
        foreach ($comentarios as $comentario) {
            echo $this->render_comentario($comentario);
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'tiene_mas' => count($comentarios) >= 10,
        ]);
    }

    // =========================================================================
    // REST API
    // =========================================================================

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor-chat/v1', '/participacion/propuestas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_propuestas'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-chat/v1', '/participacion/propuestas/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_propuesta'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-chat/v1', '/participacion/votaciones', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_votaciones'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor-chat/v1', '/participacion/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_estadisticas'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * REST: Obtener propuestas
     */
    public function rest_get_propuestas($request) {
        $propuestas = $this->obtener_propuestas([
            'estado' => $request->get_param('estado') ?: 'activa',
            'categoria' => $request->get_param('categoria') ?: '',
            'limite' => absint($request->get_param('limite')) ?: 20,
        ]);

        $respuesta = [
            'success' => true,
            'total' => count($propuestas),
            'propuestas' => $propuestas,
        ];

        return rest_ensure_response($this->sanitize_public_participacion_response($respuesta));
    }

    /**
     * REST: Obtener propuesta
     */
    public function rest_get_propuesta($request) {
        $propuesta = $this->obtener_propuesta($request->get_param('id'));

        if (!$propuesta) {
            return new WP_Error('not_found', __('Propuesta no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        $respuesta = [
            'success' => true,
            'propuesta' => $propuesta,
        ];

        return rest_ensure_response($this->sanitize_public_participacion_response($respuesta));
    }

    /**
     * REST: Obtener votaciones
     */
    public function rest_get_votaciones($request) {
        $votaciones = $this->obtener_votaciones([
            'estado' => $request->get_param('estado') ?: 'activa',
            'limite' => absint($request->get_param('limite')) ?: 10,
        ]);

        $respuesta = [
            'success' => true,
            'total' => count($votaciones),
            'votaciones' => $votaciones,
        ];

        return rest_ensure_response($this->sanitize_public_participacion_response($respuesta));
    }

    /**
     * REST: Obtener estadisticas
     */
    public function rest_get_estadisticas($request) {
        $anio = $request->get_param('anio') ?: date('Y');
        $estadisticas = $this->obtener_estadisticas_participacion($anio);

        $respuesta = [
            'success' => true,
            'anio' => $anio,
            'estadisticas' => $estadisticas,
        ];

        return rest_ensure_response($this->sanitize_public_participacion_response($respuesta));
    }

    private function sanitize_public_participacion_response($respuesta) {
        if (is_user_logged_in() || empty($respuesta['success'])) {
            return $respuesta;
        }

        if (!empty($respuesta['propuestas']) && is_array($respuesta['propuestas'])) {
            $respuesta['propuestas'] = array_map([$this, 'sanitize_public_propuesta'], $respuesta['propuestas']);
        }

        if (!empty($respuesta['propuesta'])) {
            $respuesta['propuesta'] = $this->sanitize_public_propuesta($respuesta['propuesta']);
        }

        return $respuesta;
    }

    private function sanitize_public_propuesta($propuesta) {
        if (is_object($propuesta)) {
            unset(
                $propuesta->proponente_id,
                $propuesta->direccion,
                $propuesta->ubicacion_lat,
                $propuesta->ubicacion_lng
            );
            return $propuesta;
        }

        if (!is_array($propuesta)) {
            return $propuesta;
        }

        unset(
            $propuesta['proponente_id'],
            $propuesta['direccion'],
            $propuesta['ubicacion_lat'],
            $propuesta['ubicacion_lng']
        );

        return $propuesta;
    }

    // =========================================================================
    // Metodos de datos
    // =========================================================================

    /**
     * Obtener propuestas
     */
    private function obtener_propuestas($args = []) {
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        $defaults = [
            'estado' => 'activa',
            'categoria' => '',
            'tipo' => '',
            'busqueda' => '',
            'orden' => 'apoyos',
            'limite' => 20,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $valores_preparados = [];

        if (!empty($args['estado'])) {
            if ($args['estado'] === 'todas') {
                $where[] = "estado IN ('activa', 'en_estudio', 'aprobada', 'implementada')";
            } else {
                $where[] = 'estado = %s';
                $valores_preparados[] = $args['estado'];
            }
        }

        if (!empty($args['categoria'])) {
            $where[] = 'categoria = %s';
            $valores_preparados[] = $args['categoria'];
        }

        if (!empty($args['tipo'])) {
            $where[] = 'tipo = %s';
            $valores_preparados[] = $args['tipo'];
        }

        if (!empty($args['busqueda'])) {
            $where[] = '(titulo LIKE %s OR descripcion LIKE %s)';
            $termino_busqueda = '%' . $wpdb->esc_like($args['busqueda']) . '%';
            $valores_preparados[] = $termino_busqueda;
            $valores_preparados[] = $termino_busqueda;
        }

        $order_by = 'total_apoyos DESC, fecha_creacion DESC';
        if ($args['orden'] === 'recientes') {
            $order_by = 'fecha_creacion DESC';
        } elseif ($args['orden'] === 'comentarios') {
            $order_by = 'total_comentarios DESC, fecha_creacion DESC';
        } elseif ($args['orden'] === 'presupuesto') {
            $order_by = 'presupuesto_estimado DESC';
        }

        $sql_where = implode(' AND ', $where);
        $sql = "SELECT * FROM $tabla_propuestas WHERE $sql_where ORDER BY $order_by LIMIT %d OFFSET %d";

        $valores_preparados[] = $args['limite'];
        $valores_preparados[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare($sql, ...$valores_preparados));
    }

    /**
     * Obtener propuesta individual
     */
    private function obtener_propuesta($propuesta_id) {
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_propuestas WHERE id = %d",
            $propuesta_id
        ));
    }

    /**
     * Obtener votacion
     */
    private function obtener_votacion($votacion_id) {
        global $wpdb;
        $tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_votaciones WHERE id = %d",
            $votacion_id
        ));
    }

    /**
     * Obtener votacion activa
     */
    private function obtener_votacion_activa() {
        global $wpdb;
        $tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';

        return $wpdb->get_row(
            "SELECT * FROM $tabla_votaciones WHERE estado = 'activa' ORDER BY fecha_inicio DESC LIMIT 1"
        );
    }

    /**
     * Obtener votaciones
     */
    private function obtener_votaciones($args = []) {
        global $wpdb;
        $tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';

        $defaults = [
            'estado' => 'activa',
            'limite' => 10,
        ];

        $args = wp_parse_args($args, $defaults);

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_votaciones WHERE estado = %s ORDER BY fecha_inicio DESC LIMIT %d",
            $args['estado'],
            $args['limite']
        ));
    }

    /**
     * Obtener comentarios de propuesta
     */
    private function obtener_comentarios_propuesta($propuesta_id, $limite = 10, $offset = 0) {
        global $wpdb;
        $tabla_comentarios = $wpdb->prefix . 'flavor_comentarios_propuesta';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_comentarios
             WHERE propuesta_id = %d AND estado = 'aprobado' AND comentario_padre_id IS NULL
             ORDER BY es_oficial DESC, fecha_creacion DESC
             LIMIT %d OFFSET %d",
            $propuesta_id,
            $limite,
            $offset
        ));
    }

    /**
     * Obtener fases
     */
    private function obtener_fases($anio, $proceso_id = 0) {
        global $wpdb;
        $tabla_fases = $wpdb->prefix . 'flavor_fases_participacion';

        $where = 'anio = %d';
        $valores = [$anio];

        if ($proceso_id) {
            $where .= ' AND proceso_id = %d';
            $valores[] = $proceso_id;
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_fases WHERE $where ORDER BY orden ASC",
            ...$valores
        ));
    }

    /**
     * Generar fases por defecto
     */
    private function generar_fases_default($anio) {
        $fases_config = $this->settings['fases_participacion'];
        $fecha_inicio = new DateTime("$anio-01-01");
        $fases = [];

        $iconos = [
            'recogida' => 'dashicons-edit',
            'debate' => 'dashicons-format-chat',
            'votacion' => 'dashicons-yes-alt',
            'evaluacion' => 'dashicons-search',
            'implementacion' => 'dashicons-hammer',
        ];

        foreach ($fases_config as $slug => $config) {
            $fecha_fin = clone $fecha_inicio;
            $fecha_fin->modify('+' . $config['duracion_dias'] . ' days');

            $ahora = new DateTime();
            $estado = 'pendiente';
            if ($ahora >= $fecha_inicio && $ahora <= $fecha_fin) {
                $estado = 'activa';
            } elseif ($ahora > $fecha_fin) {
                $estado = 'finalizada';
            }

            $fases[] = (object) [
                'nombre' => $config['nombre'],
                'slug' => $slug,
                'fecha_inicio' => $fecha_inicio->format('Y-m-d'),
                'fecha_fin' => $fecha_fin->format('Y-m-d'),
                'estado' => $estado,
                'icono' => $iconos[$slug] ?? 'dashicons-calendar',
            ];

            $fecha_inicio = clone $fecha_fin;
            $fecha_inicio->modify('+1 day');
        }

        return $fases;
    }

    /**
     * Verificar si usuario ya apoyo
     */
    private function usuario_ya_apoyo($propuesta_id, $usuario_id = null) {
        if (!$usuario_id) {
            $usuario_id = get_current_user_id();
        }

        if (!$usuario_id) {
            return false;
        }

        global $wpdb;
        $tabla_apoyos = $wpdb->prefix . 'flavor_apoyos';

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_apoyos WHERE propuesta_id = %d AND usuario_id = %d",
            $propuesta_id,
            $usuario_id
        ));
    }

    /**
     * Verificar si usuario ya voto en votacion
     */
    private function usuario_ya_voto_votacion($votacion_id, $usuario_id = null) {
        if (!$usuario_id) {
            $usuario_id = get_current_user_id();
        }

        if (!$usuario_id) {
            return false;
        }

        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_votos';

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_votos WHERE votacion_id = %d AND usuario_id = %d",
            $votacion_id,
            $usuario_id
        ));
    }

    /**
     * Contar votos por opcion
     */
    private function contar_votos_opcion($votacion_id, $opcion) {
        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_votos';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_votos WHERE votacion_id = %d AND voto = %s",
            $votacion_id,
            $opcion
        ));
    }

    /**
     * Obtener resultados de votacion
     */
    private function obtener_resultados_votacion($votacion_id) {
        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_votos';

        $resultados_raw = $wpdb->get_results($wpdb->prepare(
            "SELECT voto, COUNT(*) as total FROM $tabla_votos WHERE votacion_id = %d GROUP BY voto",
            $votacion_id
        ));

        $resultados = [];
        foreach ($resultados_raw as $resultado) {
            $resultados[$resultado->voto] = (int) $resultado->total;
        }

        return $resultados;
    }

    /**
     * Verificar si puede crear propuesta
     */
    private function puede_crear_propuesta($usuario_id) {
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        $propuestas_mes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_propuestas
             WHERE proponente_id = %d AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 1 MONTH)",
            $usuario_id
        ));

        return $propuestas_mes < $this->settings['max_propuestas_usuario_mes'];
    }

    /**
     * Calcular progreso de participacion
     */
    private function calcular_progreso_participacion($anio) {
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
        $tabla_apoyos = $wpdb->prefix . 'flavor_apoyos';
        $tabla_votos = $wpdb->prefix . 'flavor_votos';

        $propuestas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_propuestas WHERE YEAR(fecha_creacion) = %d",
            $anio
        ));

        $participantes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_apoyos WHERE YEAR(fecha_apoyo) = %d",
            $anio
        ));

        $votos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_votos WHERE YEAR(fecha_voto) = %d",
            $anio
        ));

        // Calcular porcentaje basado en objetivos
        $objetivo_propuestas = 100;
        $objetivo_participantes = 500;
        $porcentaje = min(100, round((($propuestas / $objetivo_propuestas) + ($participantes / $objetivo_participantes)) * 50));

        return [
            'propuestas' => $propuestas,
            'participantes' => $participantes,
            'votos' => $votos,
            'porcentaje' => $porcentaje,
        ];
    }

    /**
     * Calcular presupuesto asignado
     */
    private function calcular_presupuesto_asignado($anio) {
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        return (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(presupuesto_asignado), 0) FROM $tabla_propuestas
             WHERE tipo = 'presupuesto' AND estado IN ('aprobada', 'implementada') AND YEAR(fecha_creacion) = %d",
            $anio
        ));
    }

    /**
     * Obtener estadisticas de participacion
     */
    private function obtener_estadisticas_participacion($anio) {
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
        $tabla_apoyos = $wpdb->prefix . 'flavor_apoyos';

        return [
            'total_propuestas' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_propuestas WHERE YEAR(fecha_creacion) = %d",
                $anio
            )),
            'propuestas_aprobadas' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_propuestas WHERE YEAR(fecha_creacion) = %d AND estado IN ('aprobada', 'implementada')",
                $anio
            )),
            'propuestas_implementadas' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_propuestas WHERE YEAR(fecha_creacion) = %d AND estado = 'implementada'",
                $anio
            )),
            'total_participantes' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_apoyos WHERE YEAR(fecha_apoyo) = %d",
                $anio
            )),
            'presupuesto_ejecutado' => $this->calcular_presupuesto_asignado($anio),
        ];
    }

    /**
     * Incrementar visitas
     */
    private function incrementar_visitas($propuesta_id) {
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_propuestas SET visitas = visitas + 1 WHERE id = %d",
            $propuesta_id
        ));
    }

    /**
     * Notificar nueva propuesta
     */
    private function notificar_nueva_propuesta($propuesta_id, $titulo) {
        $admin_email = get_option('admin_email');
        $asunto = sprintf(__('[Participacion] Nueva propuesta: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $titulo);
        $mensaje = sprintf(
            __("Se ha creado una nueva propuesta ciudadana:\n\nTitulo: %s\n\nRevisa y valida la propuesta en el panel de administracion.", FLAVOR_PLATFORM_TEXT_DOMAIN),
            $titulo
        );

        wp_mail($admin_email, $asunto, $mensaje);
    }

    /**
     * Actualizar estados automaticos
     */
    public function actualizar_estados_automaticos() {
        global $wpdb;
        $tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';

        // Activar votaciones programadas
        $wpdb->query(
            "UPDATE $tabla_votaciones
             SET estado = 'activa'
             WHERE estado = 'programada' AND fecha_inicio <= NOW()"
        );

        // Finalizar votaciones
        $wpdb->query(
            "UPDATE $tabla_votaciones
             SET estado = 'finalizada'
             WHERE estado = 'activa' AND fecha_fin <= NOW()"
        );
    }

    // =========================================================================
    // Metodos heredados
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'crear_propuesta' => [
                'description' => 'Crear una propuesta ciudadana',
                'params' => ['titulo', 'descripcion', 'categoria', 'presupuesto_estimado'],
            ],
            'listar_propuestas' => [
                'description' => 'Listar propuestas activas',
                'params' => ['estado', 'categoria', 'limite'],
            ],
            'ver_propuesta' => [
                'description' => 'Ver detalles de una propuesta',
                'params' => ['propuesta_id'],
            ],
            'apoyar_propuesta' => [
                'description' => 'Apoyar una propuesta con tu voto',
                'params' => ['propuesta_id'],
            ],
            'votar' => [
                'description' => 'Votar en una votacion activa',
                'params' => ['votacion_id', 'opcion'],
            ],
            'listar_votaciones' => [
                'description' => 'Ver votaciones activas',
                'params' => ['estado'],
            ],
            'resultados_votacion' => [
                'description' => 'Ver resultados de una votacion',
                'params' => ['votacion_id'],
            ],
            'mis_propuestas' => [
                'description' => 'Ver propuestas que he creado',
                'params' => [],
            ],
            'comentar_propuesta' => [
                'description' => 'Comentar en una propuesta',
                'params' => ['propuesta_id', 'contenido'],
            ],
            'ver_fases' => [
                'description' => 'Ver fases del proceso participativo',
                'params' => ['anio'],
            ],
            'estadisticas_participacion' => [
                'description' => 'Ver estadisticas de participacion',
                'params' => ['anio'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'listar_propuestas',
            'listado' => 'listar_propuestas',
            'explorar' => 'listar_propuestas',
            'buscar' => 'listar_propuestas',
            'propuestas' => 'listar_propuestas',
            'crear' => 'crear_propuesta',
            'nueva' => 'crear_propuesta',
            'votaciones' => 'votacion_activa',
            'votar' => 'votacion_activa',
            'resultados' => 'resultados',
            'debates' => 'fases',
            'reuniones' => 'fases',
            'presupuesto' => 'presupuesto',
            'foro' => 'foro_propuesta',
            'chat' => 'chat_propuesta',
            'multimedia' => 'multimedia_propuesta',
            'red-social' => 'red_social_propuesta',
            'red_social' => 'red_social_propuesta',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => __('La vista solicitada no esta disponible en Participacion.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Accion: Listar propuestas
     */
    private function action_listar_propuestas($params) {
        $propuestas = $this->obtener_propuestas([
            'estado' => $params['estado'] ?? 'activa',
            'categoria' => $params['categoria'] ?? '',
            'limite' => absint($params['limite'] ?? 20),
        ]);

        return [
            'success' => true,
            'total' => count($propuestas),
            'propuestas' => array_map(function($p) {
                $usuario = get_userdata($p->proponente_id);
                return [
                    'id' => $p->id,
                    'titulo' => $p->titulo,
                    'descripcion' => wp_trim_words($p->descripcion, 30),
                    'categoria' => $p->categoria,
                    'estado' => $p->estado,
                    'apoyos' => $p->total_apoyos,
                    'comentarios' => $p->total_comentarios,
                    'proponente' => $usuario ? $usuario->display_name : 'Usuario',
                    'fecha' => date('d/m/Y', strtotime($p->fecha_creacion)),
                ];
            }, $propuestas),
        ];
    }

    private function action_crear_propuesta($params) {
        return do_shortcode('[crear_propuesta]');
    }

    private function action_votacion_activa($params) {
        $atts = [];

        if (!empty($params['id'])) {
            $atts[] = 'id="' . absint($params['id']) . '"';
        }

        return do_shortcode('[votacion_activa ' . implode(' ', $atts) . ']');
    }

    private function action_resultados($params) {
        $atts = [];

        if (!empty($params['id'])) {
            $atts[] = 'id="' . absint($params['id']) . '"';
        }

        return do_shortcode('[resultados_participacion ' . implode(' ', $atts) . ']');
    }

    private function action_fases($params) {
        return do_shortcode('[fases_participacion]');
    }

    private function action_presupuesto($params) {
        return do_shortcode('[presupuesto_participativo]');
    }

    /**
     * Resolver propuesta contextual para tabs satélite.
     *
     * @param array $params
     * @return object|null
     */
    private function resolve_contextual_propuesta($params = []) {
        $propuesta_id = absint(
            $params['propuesta_id']
            ?? $params['id']
            ?? $_GET['propuesta_id']
            ?? 0
        );

        if (!$propuesta_id) {
            return null;
        }

        return $this->obtener_propuesta($propuesta_id);
    }

    private function action_foro_propuesta($params) {
        $propuesta = $this->resolve_contextual_propuesta($params);
        if (!$propuesta) {
            return [
                'success' => false,
                'error' => __('No se ha encontrado la propuesta contextual.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $html  = '<div class="flavor-context-header">';
        $html .= '<h3>' . esc_html__('Foro de la propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        $html .= '<p>' . esc_html($propuesta->titulo) . '</p>';
        $html .= '</div>';
        $html .= do_shortcode('[flavor_foros_integrado entidad="participacion_propuesta" entidad_id="' . absint($propuesta->id) . '"]');

        return $html;
    }

    private function action_chat_propuesta($params) {
        if (!is_user_logged_in()) {
            return '<div class="notice notice-info"><p>' . esc_html__('Debes iniciar sesión para acceder al chat de la propuesta.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }

        $propuesta = $this->resolve_contextual_propuesta($params);
        if (!$propuesta) {
            return [
                'success' => false,
                'error' => __('No se ha encontrado la propuesta contextual.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $html  = '<div class="flavor-context-header">';
        $html .= '<h3>' . esc_html__('Chat de la propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        $html .= '<p>' . esc_html($propuesta->titulo) . '</p>';
        $html .= '</div>';
        $html .= do_shortcode('[flavor_chat_grupo_integrado entidad="participacion_propuesta" entidad_id="' . absint($propuesta->id) . '"]');

        return $html;
    }

    private function action_multimedia_propuesta($params) {
        $propuesta = $this->resolve_contextual_propuesta($params);
        if (!$propuesta) {
            return [
                'success' => false,
                'error' => __('No se ha encontrado la propuesta contextual.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $html  = '<div class="flavor-context-header">';
        $html .= '<h3>' . esc_html__('Multimedia de la propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        $html .= '<p>' . esc_html($propuesta->titulo) . '</p>';
        $html .= '<p><a class="button button-primary" href="' . esc_url(home_url('/mi-portal/multimedia/subir/?propuesta_id=' . absint($propuesta->id))) . '">' . esc_html__('Subir archivo', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a></p>';
        $html .= '</div>';
        $html .= do_shortcode('[flavor_multimedia_galeria entidad="participacion_propuesta" entidad_id="' . absint($propuesta->id) . '"]');

        return $html;
    }

    private function action_red_social_propuesta($params) {
        if (!is_user_logged_in()) {
            return '<div class="notice notice-info"><p>' . esc_html__('Debes iniciar sesión para ver la actividad social de la propuesta.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }

        $propuesta = $this->resolve_contextual_propuesta($params);
        if (!$propuesta) {
            return [
                'success' => false,
                'error' => __('No se ha encontrado la propuesta contextual.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $html  = '<div class="flavor-context-header">';
        $html .= '<h3>' . esc_html__('Actividad social de la propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        $html .= '<p>' . esc_html($propuesta->titulo) . '</p>';
        $html .= '<p><a class="button button-primary" href="' . esc_url(home_url('/mi-portal/red-social/crear/?propuesta_id=' . absint($propuesta->id))) . '">' . esc_html__('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a></p>';
        $html .= '</div>';
        $html .= do_shortcode('[flavor_social_feed entidad="participacion_propuesta" entidad_id="' . absint($propuesta->id) . '"]');

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'participacion_crear_propuesta',
                'description' => 'Crear una propuesta ciudadana para el barrio',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'titulo' => [
                            'type' => 'string',
                            'description' => 'Titulo de la propuesta',
                        ],
                        'descripcion' => [
                            'type' => 'string',
                            'description' => 'Descripcion detallada',
                        ],
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Categoria de la propuesta',
                        ],
                    ],
                    'required' => ['titulo', 'descripcion'],
                ],
            ],
            [
                'name' => 'participacion_listar',
                'description' => 'Ver propuestas ciudadanas activas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => [
                            'type' => 'string',
                            'description' => 'Filtrar por categoria',
                        ],
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Filtrar por estado',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'participacion_apoyar',
                'description' => 'Apoyar una propuesta ciudadana',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'propuesta_id' => [
                            'type' => 'integer',
                            'description' => 'ID de la propuesta a apoyar',
                        ],
                    ],
                    'required' => ['propuesta_id'],
                ],
            ],
            [
                'name' => 'participacion_votar',
                'description' => 'Votar en una votacion activa',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'votacion_id' => [
                            'type' => 'integer',
                            'description' => 'ID de la votacion',
                        ],
                        'opcion' => [
                            'type' => 'string',
                            'description' => 'Opcion seleccionada',
                        ],
                    ],
                    'required' => ['votacion_id', 'opcion'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Sistema de Participacion Ciudadana**

Permite a los vecinos proponer, votar y decidir sobre asuntos del barrio.

**Tipos de participacion:**
- Propuestas ciudadanas: Ideas para mejorar el barrio
- Consultas: Preguntar opinion sobre decisiones
- Votaciones: Decidir entre opciones
- Presupuestos participativos: Asignar recursos a proyectos

**Proceso de propuestas:**
1. Cualquier vecino puede crear una propuesta
2. Otros vecinos la apoyan
3. Si alcanza los apoyos minimos, pasa a estudio
4. El ayuntamiento evalua viabilidad
5. Se implementa o se explica por que no

**Fases del proceso:**
1. Recogida de propuestas
2. Debate ciudadano
3. Votacion
4. Evaluacion tecnica
5. Implementacion

**Presupuesto participativo:**
- Cada ano se destina un presupuesto para proyectos ciudadanos
- Los vecinos proponen y votan como se gasta
- Limite maximo por propuesta configurable
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Como creo una propuesta?',
                'respuesta' => 'Ve a Participacion, pulsa "Nueva propuesta", describe tu idea y publicala. Otros vecinos podran apoyarla.',
            ],
            [
                'pregunta' => 'Cuantos apoyos necesita una propuesta?',
                'respuesta' => 'Depende de la configuracion del ayuntamiento. Normalmente entre 10-50 apoyos para ser evaluada.',
            ],
            [
                'pregunta' => 'Que es el presupuesto participativo?',
                'respuesta' => 'Es una cantidad de dinero que el ayuntamiento destina a proyectos propuestos y votados por los vecinos.',
            ],
            [
                'pregunta' => 'Puedo retirar mi apoyo?',
                'respuesta' => 'Una vez dado el apoyo, no se puede retirar para garantizar la integridad del proceso.',
            ],
        ];
    }

    /**
     * Componentes web del modulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Participacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Seccion hero con propuestas destacadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'hero',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Participacion Ciudadana', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtitulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Tu voz importa. Propon y decide el futuro de tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'mostrar_estadisticas' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar estadisticas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'participacion/hero',
            ],
            'propuestas_grid' => [
                'label' => __('Grid de Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado de propuestas ciudadanas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'listings',
                'icon' => 'dashicons-lightbulb',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Propuestas Activas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'estado' => [
                        'type' => 'select',
                        'label' => __('Filtrar por estado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => ['todas', 'activas', 'aprobadas', 'en_estudio'],
                        'default' => 'activas',
                    ],
                    'ordenar' => [
                        'type' => 'select',
                        'label' => __('Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => ['recientes', 'apoyos', 'comentarios'],
                        'default' => 'apoyos',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Numero maximo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => 9,
                    ],
                ],
                'template' => 'participacion/propuestas-grid',
            ],
            'fases_timeline' => [
                'label' => __('Timeline de Fases', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Visualizacion de fases del proceso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'display',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'anio' => [
                        'type' => 'number',
                        'label' => __('Ano', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => date('Y'),
                    ],
                    'mostrar_progreso' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar barra de progreso', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'participacion/fases-timeline',
            ],
            'presupuesto_widget' => [
                'label' => __('Widget Presupuesto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Estado del presupuesto participativo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'widgets',
                'icon' => 'dashicons-chart-pie',
                'fields' => [
                    'anio' => [
                        'type' => 'number',
                        'label' => __('Ano', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => date('Y'),
                    ],
                ],
                'template' => 'participacion/presupuesto-widget',
            ],
        ];
    }

    // =========================================================================
    // PANEL UNIFICADO DE GESTIÓN
    // =========================================================================

    /**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'participacion',
            'label' => __('Participación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-groups',
            'capability' => 'manage_options',
            'categoria' => 'comunidad',
            'paginas' => [
                [
                    'slug' => 'participacion-dashboard',
                    'titulo' => __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'participacion-propuestas',
                    'titulo' => __('Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_propuestas'],
                    'badge' => [$this, 'contar_propuestas_activas'],
                ],
                [
                    'slug' => 'participacion-votaciones',
                    'titulo' => __('Votaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_votaciones'],
                    'badge' => [$this, 'contar_votaciones_abiertas'],
                ],
                [
                    'slug' => 'participacion-debates',
                    'titulo' => __('Debates', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_debates'],
                ],
                [
                    'slug' => 'participacion-config',
                    'titulo' => __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta propuestas activas
     *
     * @return int
     */
    public function contar_propuestas_activas() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_propuestas)) {
            return 0;
        }
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_propuestas WHERE estado = 'activa'"
        );
    }

    /**
     * Cuenta votaciones abiertas
     *
     * @return int
     */
    public function contar_votaciones_abiertas() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_votaciones)) {
            return 0;
        }
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_votaciones WHERE estado = 'abierta' AND fecha_fin > %s",
            current_time('mysql')
        ));
    }

    /**
     * Estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
        $tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';
        $estadisticas = [];

        // Propuestas activas
        if (Flavor_Chat_Helpers::tabla_existe($tabla_propuestas)) {
            $propuestas_activas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_propuestas WHERE estado = 'activa'"
            );
            $estadisticas[] = [
                'icon' => 'dashicons-lightbulb',
                'valor' => $propuestas_activas,
                'label' => __('Propuestas activas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => $propuestas_activas > 0 ? 'blue' : 'gray',
                'enlace' => admin_url('admin.php?page=participacion-propuestas'),
            ];
        }

        // Votaciones abiertas
        if (Flavor_Chat_Helpers::tabla_existe($tabla_votaciones)) {
            $votaciones_abiertas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_votaciones WHERE estado = 'abierta' AND fecha_fin > %s",
                current_time('mysql')
            ));
            $estadisticas[] = [
                'icon' => 'dashicons-thumbs-up',
                'valor' => $votaciones_abiertas,
                'label' => __('Votaciones abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => $votaciones_abiertas > 0 ? 'green' : 'gray',
                'enlace' => admin_url('admin.php?page=participacion-votaciones'),
            ];
        }

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard de participación
     */
    public function render_admin_dashboard() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Dashboard de Participación', FLAVOR_PLATFORM_TEXT_DOMAIN), [
            ['label' => __('Nueva Propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=participacion-propuestas&action=nueva'), 'class' => 'button-primary'],
        ]);

        // Resumen de estadísticas
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
        $tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';

        $total_propuestas = 0;
        $propuestas_activas = 0;
        $votaciones_abiertas = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_propuestas)) {
            $total_propuestas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_propuestas");
            $propuestas_activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_propuestas WHERE estado = 'activa'");
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_votaciones)) {
            $votaciones_abiertas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_votaciones WHERE estado = 'abierta' AND fecha_fin > %s",
                current_time('mysql')
            ));
        }

        echo '<div class="flavor-stats-grid">';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($total_propuestas) . '</span><span class="stat-label">' . __('Total Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($propuestas_activas) . '</span><span class="stat-label">' . __('Propuestas Activas', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($votaciones_abiertas) . '</span><span class="stat-label">' . __('Votaciones Abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span></div>';
        echo '</div>';

        echo '<p>' . __('Panel de control del módulo de participación ciudadana con métricas y accesos rápidos.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        echo '</div>';
    }

    /**
     * Renderiza la página de propuestas
     */
    public function render_admin_propuestas() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Propuestas Ciudadanas', FLAVOR_PLATFORM_TEXT_DOMAIN), [
            ['label' => __('Nueva Propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=participacion-propuestas&action=nueva'), 'class' => 'button-primary'],
        ]);

        // Listado de propuestas
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_propuestas)) {
            echo '<div class="notice notice-warning"><p>' . __('Las tablas del módulo no están creadas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            echo '</div>';
            return;
        }

        $propuestas = $wpdb->get_results("SELECT * FROM $tabla_propuestas ORDER BY created_at DESC LIMIT 20");

        if (empty($propuestas)) {
            echo '<div class="notice notice-info"><p>' . __('No hay propuestas registradas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('ID', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Votos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($propuestas as $propuesta) {
                $titulo = isset($propuesta->titulo) ? $propuesta->titulo : (isset($propuesta->title) ? $propuesta->title : '-');
                $categoria = isset($propuesta->categoria) ? $propuesta->categoria : '-';
                $estado = isset($propuesta->estado) ? $propuesta->estado : '-';
                $votos = isset($propuesta->votos) ? $propuesta->votos : (isset($propuesta->apoyos) ? $propuesta->apoyos : 0);
                $fecha = isset($propuesta->created_at) ? $propuesta->created_at : '-';

                echo '<tr>';
                echo '<td>' . esc_html($propuesta->id) . '</td>';
                echo '<td>' . esc_html($titulo) . '</td>';
                echo '<td>' . esc_html($categoria) . '</td>';
                echo '<td><span class="status-badge status-' . esc_attr($estado) . '">' . esc_html(ucfirst($estado)) . '</span></td>';
                echo '<td>' . esc_html($votos) . '</td>';
                echo '<td>' . esc_html($fecha) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la página de votaciones
     */
    public function render_admin_votaciones() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Votaciones', FLAVOR_PLATFORM_TEXT_DOMAIN), [
            ['label' => __('Nueva Votación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=participacion-votaciones&action=nueva'), 'class' => 'button-primary'],
        ]);

        // Listado de votaciones
        global $wpdb;
        $tabla_votaciones = $wpdb->prefix . 'flavor_votaciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_votaciones)) {
            echo '<div class="notice notice-warning"><p>' . __('Las tablas del módulo no están creadas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            echo '</div>';
            return;
        }

        $votaciones = $wpdb->get_results("SELECT * FROM $tabla_votaciones ORDER BY created_at DESC LIMIT 20");

        if (empty($votaciones)) {
            echo '<div class="notice notice-info"><p>' . __('No hay votaciones registradas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('ID', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Título', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Fecha Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Fecha Fin', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($votaciones as $votacion) {
                $titulo = isset($votacion->titulo) ? $votacion->titulo : (isset($votacion->title) ? $votacion->title : '-');
                $estado = isset($votacion->estado) ? $votacion->estado : '-';
                $fecha_inicio = isset($votacion->fecha_inicio) ? $votacion->fecha_inicio : '-';
                $fecha_fin = isset($votacion->fecha_fin) ? $votacion->fecha_fin : '-';

                echo '<tr>';
                echo '<td>' . esc_html($votacion->id) . '</td>';
                echo '<td>' . esc_html($titulo) . '</td>';
                echo '<td><span class="status-badge status-' . esc_attr($estado) . '">' . esc_html(ucfirst($estado)) . '</span></td>';
                echo '<td>' . esc_html($fecha_inicio) . '</td>';
                echo '<td>' . esc_html($fecha_fin) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la página de debates
     */
    public function render_admin_debates() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Debates Ciudadanos', FLAVOR_PLATFORM_TEXT_DOMAIN), [
            ['label' => __('Nuevo Debate', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=participacion-debates&action=nuevo'), 'class' => 'button-primary'],
        ]);

        // Listado de debates (usando comentarios de propuestas activas o tabla dedicada)
        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
        $tabla_comentarios = $wpdb->prefix . 'flavor_participacion_comentarios';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_propuestas)) {
            echo '<div class="notice notice-warning"><p>' . __('Las tablas del módulo no están creadas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
            echo '</div>';
            return;
        }

        // Mostrar propuestas con más actividad de debate
        $propuestas_debate = $wpdb->get_results(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM $tabla_comentarios c WHERE c.propuesta_id = p.id) as num_comentarios
             FROM $tabla_propuestas p
             WHERE p.estado = 'activa'
             ORDER BY num_comentarios DESC
             LIMIT 20"
        );

        if (empty($propuestas_debate)) {
            echo '<div class="notice notice-info"><p>' . __('No hay debates activos en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($propuestas_debate as $propuesta) {
                $titulo = isset($propuesta->titulo) ? $propuesta->titulo : (isset($propuesta->title) ? $propuesta->title : '-');
                $categoria = isset($propuesta->categoria) ? $propuesta->categoria : '-';
                $num_comentarios = isset($propuesta->num_comentarios) ? $propuesta->num_comentarios : 0;

                echo '<tr>';
                echo '<td>' . esc_html($titulo) . '</td>';
                echo '<td>' . esc_html($categoria) . '</td>';
                echo '<td>' . esc_html($num_comentarios) . '</td>';
                echo '<td><span class="status-badge status-activa">' . __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span></td>';
                echo '<td><a href="' . esc_url(admin_url('admin.php?page=participacion-debates&propuesta_id=' . $propuesta->id)) . '" class="button button-small">' . __('Ver Debate', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la página de configuración
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Participación', FLAVOR_PLATFORM_TEXT_DOMAIN));

        // Procesar guardado de configuración
        if (isset($_POST['guardar_config']) && check_admin_referer('participacion_config_nonce')) {
            $nuevos_ajustes = [
                'requiere_verificacion' => isset($_POST['requiere_verificacion']),
                'votos_necesarios_propuesta' => intval($_POST['votos_necesarios_propuesta'] ?? 10),
                'permite_propuestas_ciudadanas' => isset($_POST['permite_propuestas_ciudadanas']),
                'moderacion_propuestas' => isset($_POST['moderacion_propuestas']),
                'duracion_votacion_dias' => intval($_POST['duracion_votacion_dias'] ?? 7),
                'max_propuestas_usuario_mes' => intval($_POST['max_propuestas_usuario_mes'] ?? 5),
                'permitir_comentarios' => isset($_POST['permitir_comentarios']),
                'presupuesto_participativo_activo' => isset($_POST['presupuesto_participativo_activo']),
                'presupuesto_total_anual' => floatval($_POST['presupuesto_total_anual'] ?? 100000),
            ];

            update_option('flavor_participacion_settings', array_merge($this->get_settings(), $nuevos_ajustes));
            echo '<div class="notice notice-success"><p>' . __('Configuración guardada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }

        $settings = $this->get_settings();
        ?>
        <form method="post" class="flavor-config-form">
            <?php wp_nonce_field('participacion_config_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Requiere verificación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="requiere_verificacion" value="1" <?php checked($settings['requiere_verificacion'] ?? true); ?>>
                            <?php _e('Los usuarios deben estar verificados para participar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Votos necesarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="number" name="votos_necesarios_propuesta" value="<?php echo esc_attr($settings['votos_necesarios_propuesta'] ?? 10); ?>" min="1" class="small-text">
                        <p class="description"><?php _e('Votos mínimos para que una propuesta avance', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Propuestas ciudadanas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="permite_propuestas_ciudadanas" value="1" <?php checked($settings['permite_propuestas_ciudadanas'] ?? true); ?>>
                            <?php _e('Permitir que los ciudadanos creen propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Moderación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="moderacion_propuestas" value="1" <?php checked($settings['moderacion_propuestas'] ?? true); ?>>
                            <?php _e('Las propuestas requieren aprobación antes de publicarse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Duración de votaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="number" name="duracion_votacion_dias" value="<?php echo esc_attr($settings['duracion_votacion_dias'] ?? 7); ?>" min="1" class="small-text">
                        <span><?php _e('días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Límite propuestas/usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="number" name="max_propuestas_usuario_mes" value="<?php echo esc_attr($settings['max_propuestas_usuario_mes'] ?? 5); ?>" min="1" class="small-text">
                        <span><?php _e('propuestas por mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="permitir_comentarios" value="1" <?php checked($settings['permitir_comentarios'] ?? true); ?>>
                            <?php _e('Permitir comentarios en propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row" colspan="2"><h2><?php _e('Presupuestos Participativos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2></th>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Activar presupuestos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="presupuesto_participativo_activo" value="1" <?php checked($settings['presupuesto_participativo_activo'] ?? false); ?>>
                            <?php _e('Habilitar módulo de presupuestos participativos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Presupuesto anual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="number" name="presupuesto_total_anual" value="<?php echo esc_attr($settings['presupuesto_total_anual'] ?? 100000); ?>" min="0" step="100" class="regular-text">
                        <span><?php echo esc_html__('&euro;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="guardar_config" class="button button-primary" value="<?php _e('Guardar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </p>
        </form>
        <?php
        echo '</div>';
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Participación Ciudadana', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'participacion',
                'content' => '<h1>' . __('Participación Ciudadana', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Tu voz importa. Participa en las decisiones de tu comunidad, propón ideas y vota por las iniciativas que quieres ver realizadas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="participacion" action="listar" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Crear Propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'participacion/propuesta',
                'content' => '<h1>' . __('Crear Nueva Propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Comparte tu idea con la comunidad. Describe tu propuesta y consigue el apoyo de tus vecinos.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="participacion" action="crear_propuesta"]',
                'parent' => 'participacion',
            ],
            [
                'title' => __('Votar Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'participacion/votar',
                'content' => '<h1>' . __('Votar Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Apoya las propuestas que más te interesan. Tu voto ayuda a priorizar las iniciativas comunitarias.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="participacion" action="votar" columnas="2" limite="20"]',
                'parent' => 'participacion',
            ],
            [
                'title' => __('Mis Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'participacion/mis-propuestas',
                'content' => '<h1>' . __('Mis Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Consulta el estado de las propuestas que has creado y el apoyo que han recibido.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="participacion" action="mis_propuestas" columnas="2" limite="10"]',
                'parent' => 'participacion',
            ],
        ];
    }

    /**
     * Registrar páginas de administración
     */
    public function registrar_paginas_admin() {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;


        $capability = 'manage_options';

        // Páginas ocultas (sin menú visible en el sidebar)
        add_submenu_page(null, __('Configuración Participación', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN), $capability, 'participacion-config', [$this, 'render_pagina_config']);
        add_submenu_page(null, __('Debates', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Debates', FLAVOR_PLATFORM_TEXT_DOMAIN), $capability, 'participacion-debates', [$this, 'render_pagina_debates']);
        add_submenu_page(null, __('Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN), $capability, 'participacion-propuestas', [$this, 'render_pagina_propuestas']);
        add_submenu_page(null, __('Resultados', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Resultados', FLAVOR_PLATFORM_TEXT_DOMAIN), $capability, 'participacion-resultados', [$this, 'render_pagina_resultados']);
        add_submenu_page(null, __('Votaciones', FLAVOR_PLATFORM_TEXT_DOMAIN), __('Votaciones', FLAVOR_PLATFORM_TEXT_DOMAIN), $capability, 'participacion-votaciones', [$this, 'render_pagina_votaciones']);
    }

    public function render_pagina_config() {
        $views_path = dirname(__FILE__) . '/views/config.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Configuración Participación', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1></div>'; }
    }

    public function render_pagina_debates() {
        $views_path = dirname(__FILE__) . '/views/debates.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Gestión de Debates', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1></div>'; }
    }

    public function render_pagina_propuestas() {
        $views_path = dirname(__FILE__) . '/views/propuestas.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Gestión de Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1></div>'; }
    }

    public function render_pagina_resultados() {
        $views_path = dirname(__FILE__) . '/views/resultados.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Resultados', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1></div>'; }
    }

    public function render_pagina_votaciones() {
        $views_path = dirname(__FILE__) . '/views/votaciones.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Gestión de Votaciones', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1></div>'; }
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo_tab = dirname(__FILE__) . '/class-participacion-dashboard-tab.php';
        if (file_exists($archivo_tab)) {
            require_once $archivo_tab;
            Flavor_Participacion_Dashboard_Tab::get_instance();
        }
    }
}
