<?php
/**
 * Módulo de Ayuda Vecinal para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Ayuda Vecinal - Red de ayuda mutua entre vecinos
 */
class Flavor_Chat_Ayuda_Vecinal_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Consumer;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'ayuda_vecinal';
        $this->name = 'Ayuda Vecinal'; // Translation loaded on init
        $this->description = 'Red de ayuda mutua entre vecinos - ofrece y solicita ayuda en tu comunidad.'; // Translation loaded on init

        parent::__construct();

        // Admin pages
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);

        // Admin styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX handlers para gestión de voluntarios
        add_action('wp_ajax_ayuda_vecinal_listar_voluntarios', [$this, 'ajax_listar_voluntarios']);
        add_action('wp_ajax_ayuda_vecinal_listar_usuarios', [$this, 'ajax_listar_usuarios']);
        add_action('wp_ajax_ayuda_vecinal_guardar_voluntario', [$this, 'ajax_guardar_voluntario']);

        // AJAX handler para dashboard
        add_action('wp_ajax_ayuda_vecinal_get_dashboard_data', [$this, 'ajax_get_dashboard_data']);

        // AJAX handlers para solicitudes
        add_action('wp_ajax_ayuda_vecinal_listar_solicitudes', [$this, 'ajax_listar_solicitudes']);
        add_action('wp_ajax_ayuda_vecinal_guardar_solicitud', [$this, 'ajax_guardar_solicitud']);

        // Registrar shortcodes
        add_shortcode('ayuda_vecinal_solicitudes', [$this, 'render_shortcode_solicitudes']);
        add_shortcode('ayuda_vecinal_ofrecer', [$this, 'render_shortcode_ofrecer']);
        add_shortcode('ayuda_vecinal_solicitar', [$this, 'render_shortcode_solicitar']);
        add_shortcode('ayuda_vecinal_mis_ayudas', [$this, 'render_shortcode_mis_ayudas']);
        add_shortcode('ayuda_vecinal_mapa', [$this, 'render_shortcode_mapa']);
        add_shortcode('ayuda_vecinal_estadisticas', [$this, 'render_shortcode_estadisticas']);

        // AJAX handlers para shortcodes frontend
        add_action('wp_ajax_ayuda_vecinal_crear_solicitud', [$this, 'ajax_crear_solicitud_frontend']);
        add_action('wp_ajax_ayuda_vecinal_crear_oferta', [$this, 'ajax_crear_oferta_frontend']);
        add_action('wp_ajax_ayuda_vecinal_responder_solicitud', [$this, 'ajax_responder_solicitud']);
        add_action('wp_ajax_nopriv_ayuda_vecinal_get_solicitudes_mapa', [$this, 'ajax_get_solicitudes_mapa']);
        add_action('wp_ajax_ayuda_vecinal_get_solicitudes_mapa', [$this, 'ajax_get_solicitudes_mapa']);

        // Encolar assets frontend cuando se usan shortcodes
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_frontend_assets']);
    }

    /**
     * Encolar assets del admin
     *
     * @param string $hook Hook de la página actual
     */
    public function enqueue_admin_assets($hook) {
        // Solo cargar en páginas de ayuda-vecinal
        $paginas_ayuda = ['toplevel_page_ayuda-vecinal', 'ayuda-vecinal_page_ayuda-solicitudes', 'ayuda-vecinal_page_ayuda-voluntarios', 'ayuda-vecinal_page_ayuda-matches', 'ayuda-vecinal_page_ayuda-estadisticas'];

        if (!in_array($hook, $paginas_ayuda)) {
            return;
        }

        $assets_url = FLAVOR_CHAT_IA_URL . 'includes/modules/ayuda-vecinal/assets/';
        $version = FLAVOR_CHAT_IA_VERSION;

        // CSS del admin
        wp_enqueue_style(
            'ayuda-vecinal-admin',
            $assets_url . 'css/ayuda-vecinal.css',
            [],
            $version
        );

        // Estilos comunes del plugin si existen
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'admin/css/admin-common.css')) {
            wp_enqueue_style(
                'flavor-admin-common',
                FLAVOR_CHAT_IA_URL . 'admin/css/admin-common.css',
                [],
                $version
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        return Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Ayuda Vecinal no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
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
            'requiere_verificacion_usuarios' => true,
            'permite_valoraciones' => true,
            'sistema_puntos_solidaridad' => true,
            'puntos_por_ayuda' => 10,
            'categorias_ayuda' => [
                'compras',
                'cuidado_mayores',
                'cuidado_ninos',
                'mascotas',
                'transporte',
                'tecnologia',
                'tramites',
                'reparaciones',
                'companía',
                'otro',
            ],
        ];
    }

    /**
     * Define que tipos de contenido acepta este modulo
     *
     * @return array IDs de providers aceptados
     */
    protected function get_accepted_integrations() {
        return ['multimedia'];
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
                'table'   => $wpdb->prefix . 'flavor_ayuda_vecinal',
                'context' => 'side',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        $this->register_as_integration_consumer();

        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Widget
        $this->inicializar_dashboard_widget();

        // Cargar Frontend Controller
        $this->cargar_frontend_controller();
    }

    /**
     * Carga el controlador frontend
     */
    private function cargar_frontend_controller() {
        $archivo_controller = dirname(__FILE__) . '/frontend/class-ayuda-vecinal-frontend-controller.php';
        if (file_exists($archivo_controller)) {
            require_once $archivo_controller;
            Flavor_Ayuda_Vecinal_Frontend_Controller::get_instance();
        }
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';
        $tabla_ofertas = $wpdb->prefix . 'flavor_ayuda_ofertas';
        $tabla_respuestas = $wpdb->prefix . 'flavor_ayuda_respuestas';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_ayuda_valoraciones';

        $sql_solicitudes = "CREATE TABLE IF NOT EXISTS $tabla_solicitudes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            solicitante_id bigint(20) unsigned NOT NULL,
            categoria varchar(100) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            urgencia enum('baja','media','alta','urgente') DEFAULT 'media',
            ubicacion varchar(500) DEFAULT NULL,
            ubicacion_lat decimal(10,7) DEFAULT NULL,
            ubicacion_lng decimal(10,7) DEFAULT NULL,
            fecha_necesaria datetime DEFAULT NULL,
            duracion_estimada_minutos int(11) DEFAULT NULL,
            necesita_desplazamiento tinyint(1) DEFAULT 0,
            requiere_habilidad_especifica tinyint(1) DEFAULT 0,
            habilidades_requeridas text DEFAULT NULL,
            num_personas_necesarias int(11) DEFAULT 1,
            compensacion text DEFAULT NULL,
            estado enum('abierta','asignada','en_curso','completada','cancelada','expirada') DEFAULT 'abierta',
            ayudante_id bigint(20) unsigned DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_asignacion datetime DEFAULT NULL,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY solicitante_id (solicitante_id),
            KEY ayudante_id (ayudante_id),
            KEY categoria (categoria),
            KEY urgencia (urgencia),
            KEY estado (estado),
            KEY fecha_necesaria (fecha_necesaria),
            KEY ubicacion (ubicacion_lat, ubicacion_lng)
        ) $charset_collate;";

        $sql_ofertas = "CREATE TABLE IF NOT EXISTS $tabla_ofertas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            categoria varchar(100) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            habilidades text DEFAULT NULL COMMENT 'JSON',
            disponibilidad text DEFAULT NULL COMMENT 'JSON dias y horarios',
            radio_km int(11) DEFAULT 5,
            tiene_vehiculo tinyint(1) DEFAULT 0,
            activa tinyint(1) DEFAULT 1,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY categoria (categoria),
            KEY activa (activa)
        ) $charset_collate;";

        $sql_respuestas = "CREATE TABLE IF NOT EXISTS $tabla_respuestas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            solicitud_id bigint(20) unsigned NOT NULL,
            ayudante_id bigint(20) unsigned NOT NULL,
            mensaje text DEFAULT NULL,
            disponibilidad_propuesta datetime DEFAULT NULL,
            estado enum('pendiente','aceptada','rechazada','retirada') DEFAULT 'pendiente',
            fecha_respuesta datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY solicitud_id (solicitud_id),
            KEY ayudante_id (ayudante_id),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_valoraciones = "CREATE TABLE IF NOT EXISTS $tabla_valoraciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            solicitud_id bigint(20) unsigned NOT NULL,
            valorador_id bigint(20) unsigned NOT NULL,
            valorado_id bigint(20) unsigned NOT NULL,
            tipo enum('ayudante','solicitante') NOT NULL,
            puntuacion int(11) NOT NULL,
            aspectos text DEFAULT NULL COMMENT 'JSON: puntualidad, amabilidad, calidad',
            comentario text DEFAULT NULL,
            puntos_solidaridad_otorgados int(11) DEFAULT 0,
            fecha_valoracion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY solicitud_valorador (solicitud_id, valorador_id),
            KEY valorado_id (valorado_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_solicitudes);
        dbDelta($sql_ofertas);
        dbDelta($sql_respuestas);
        dbDelta($sql_valoraciones);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_solicitudes' => [
                'description' => 'Listar todas las solicitudes de ayuda',
                'params' => ['estado', 'categoria', 'limit', 'offset'],
            ],
            'solicitudes_activas' => [
                'description' => 'Ver solicitudes de ayuda activas',
                'params' => ['categoria', 'urgencia'],
            ],
            'solicitudes_cercanas' => [
                'description' => 'Solicitudes cercanas a mi ubicación',
                'params' => ['lat', 'lng', 'radio_km'],
            ],
            'crear_solicitud' => [
                'description' => 'Crear solicitud de ayuda',
                'params' => ['categoria', 'titulo', 'descripcion', 'urgencia'],
            ],
            'mis_solicitudes' => [
                'description' => 'Mis solicitudes de ayuda',
                'params' => [],
            ],
            'ofrecer_ayuda' => [
                'description' => 'Ofrecer ayuda en solicitud',
                'params' => ['solicitud_id', 'mensaje'],
            ],
            'aceptar_ayudante' => [
                'description' => 'Aceptar ayudante para mi solicitud',
                'params' => ['respuesta_id'],
            ],
            'publicar_oferta' => [
                'description' => 'Publicar oferta de ayuda permanente',
                'params' => ['categoria', 'titulo', 'descripcion'],
            ],
            'mis_ayudas_realizadas' => [
                'description' => 'Ayudas que he realizado',
                'params' => [],
            ],
            'marcar_completada' => [
                'description' => 'Marcar ayuda como completada',
                'params' => ['solicitud_id'],
            ],
            'valorar_ayuda' => [
                'description' => 'Valorar ayuda recibida/dada',
                'params' => ['solicitud_id', 'puntuacion', 'comentario'],
            ],
            'mis_puntos_solidaridad' => [
                'description' => 'Ver mis puntos de solidaridad',
                'params' => [],
            ],
            // Admin actions
            'estadisticas_ayuda' => [
                'description' => 'Estadísticas de ayuda mutua (admin)',
                'params' => ['periodo'],
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
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: Listar solicitudes (genérica con filtros)
     */
    private function action_listar_solicitudes($params) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        $where = ['1=1'];
        $prepare_values = [];

        // Filtro por estado (si no se especifica, mostrar activas por defecto)
        $estado = $params['estado'] ?? 'abierta';
        if (!empty($estado) && $estado !== 'todas') {
            $where[] = 'estado = %s';
            $prepare_values[] = sanitize_text_field($estado);
        }

        // Filtro por categoría
        if (!empty($params['categoria'])) {
            $where[] = 'categoria = %s';
            $prepare_values[] = sanitize_text_field($params['categoria']);
        }

        // Paginación
        $limit = isset($params['limit']) ? absint($params['limit']) : 20;
        $offset = isset($params['offset']) ? absint($params['offset']) : 0;

        $sql = "SELECT * FROM $tabla_solicitudes WHERE " . implode(' AND ', $where) . " ORDER BY fecha_solicitud DESC LIMIT %d OFFSET %d";
        $prepare_values[] = $limit;
        $prepare_values[] = $offset;

        $solicitudes = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        // Contar total
        $sql_count = "SELECT COUNT(*) FROM $tabla_solicitudes WHERE " . implode(' AND ', $where);
        if (count($prepare_values) > 2) {
            $count_values = array_slice($prepare_values, 0, -2);
            $total = $wpdb->get_var($wpdb->prepare($sql_count, ...$count_values));
        } else {
            $total = $wpdb->get_var($sql_count);
        }

        return [
            'success' => true,
            'solicitudes' => array_map(function($s) {
                $solicitante = get_userdata($s->solicitante_id);
                return [
                    'id' => $s->id,
                    'titulo' => $s->titulo,
                    'descripcion' => $s->descripcion,
                    'categoria' => $s->categoria,
                    'urgencia' => $s->urgencia,
                    'estado' => $s->estado,
                    'solicitante' => $solicitante ? $solicitante->display_name : 'Vecino',
                    'solicitante_id' => $s->solicitante_id,
                    'fecha_necesaria' => $s->fecha_necesaria ? date('d/m/Y H:i', strtotime($s->fecha_necesaria)) : null,
                    'duracion_estimada' => $s->duracion_estimada_minutos,
                    'ubicacion' => $s->ubicacion,
                    'fecha_solicitud' => date('d/m/Y H:i', strtotime($s->fecha_solicitud)),
                    'tiempo_publicada' => human_time_diff(strtotime($s->fecha_solicitud), current_time('timestamp')) . ' atrás',
                ];
            }, $solicitudes),
            'total' => (int) $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Acción: Solicitudes activas
     */
    private function action_solicitudes_activas($params) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        $where = ["estado = 'abierta'"];
        $prepare_values = [];

        if (!empty($params['categoria'])) {
            $where[] = 'categoria = %s';
            $prepare_values[] = sanitize_text_field($params['categoria']);
        }

        if (!empty($params['urgencia'])) {
            $where[] = 'urgencia = %s';
            $prepare_values[] = sanitize_text_field($params['urgencia']);
        }

        $sql = "SELECT * FROM $tabla_solicitudes WHERE " . implode(' AND ', $where) . " ORDER BY urgencia DESC, fecha_solicitud DESC LIMIT 50";

        if (!empty($prepare_values)) {
            $solicitudes = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $solicitudes = $wpdb->get_results($sql);
        }

        return [
            'success' => true,
            'solicitudes' => array_map(function($s) {
                $solicitante = get_userdata($s->solicitante_id);
                return [
                    'id' => $s->id,
                    'titulo' => $s->titulo,
                    'descripcion' => $s->descripcion,
                    'categoria' => $s->categoria,
                    'urgencia' => $s->urgencia,
                    'solicitante' => $solicitante ? $solicitante->display_name : 'Vecino',
                    'fecha_necesaria' => $s->fecha_necesaria ? date('d/m/Y H:i', strtotime($s->fecha_necesaria)) : null,
                    'duracion_estimada' => $s->duracion_estimada_minutos,
                    'ubicacion' => $s->ubicacion,
                    'tiempo_publicada' => human_time_diff(strtotime($s->fecha_solicitud), current_time('timestamp')) . ' atrás',
                ];
            }, $solicitudes),
        ];
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Matching inteligente entre solicitudes y voluntarios
     * - Sugerencias de ayuda según habilidades del usuario
     * - Detección de necesidades urgentes prioritarias
     * - Sistema de reputación y confianza
     */
    public function get_web_components() {
        return [
            'hero_ayuda' => [
                'label' => __('Hero Ayuda Vecinal', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-heart',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Red de Ayuda Vecinal', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Unidos somos más fuertes. Pide o presta ayuda en tu comunidad', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_estadisticas' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'ayuda-vecinal/hero',
            ],
            'solicitudes_grid' => [
                'label' => __('Grid de Solicitudes', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Solicitudes Activas', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'options' => [2, 3], 'default' => 2],
                    'limite' => ['type' => 'number', 'default' => 6],
                    'categoria' => ['type' => 'text', 'default' => ''],
                    'mostrar_urgencia' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'ayuda-vecinal/solicitudes-grid',
            ],
            'categorias_ayuda' => [
                'label' => __('Categorías de Ayuda', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-category',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('¿Cómo Puedes Ayudar?', 'flavor-chat-ia')],
                    'mostrar_iconos' => ['type' => 'toggle', 'default' => true],
                    'estilo' => ['type' => 'select', 'options' => ['grid', 'lista'], 'default' => 'grid'],
                ],
                'template' => 'ayuda-vecinal/categorias',
            ],
            'cta_voluntario' => [
                'label' => __('CTA Ser Voluntario', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-megaphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Únete como Voluntario', 'flavor-chat-ia')],
                    'descripcion' => ['type' => 'textarea', 'default' => __('Ayuda a tus vecinos y fortalece tu comunidad', 'flavor-chat-ia')],
                    'boton_texto' => ['type' => 'text', 'default' => __('Quiero Ayudar', 'flavor-chat-ia')],
                    'boton_url' => ['type' => 'url', 'default' => '#'],
                    'color_fondo' => ['type' => 'color', 'default' => '#ef4444'],
                ],
                'template' => 'ayuda-vecinal/cta-voluntario',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'ayuda_solicitudes',
                'description' => 'Ver solicitudes de ayuda activas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => ['type' => 'string', 'description' => 'Categoría de ayuda'],
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
**Red de Ayuda Vecinal**

Comunidad de apoyo mutuo donde vecinos se ayudan entre sí.

**Tipos de ayuda:**
- Compras y recados
- Cuidado de mayores
- Cuidado de niños
- Cuidado de mascotas
- Transporte y desplazamientos
- Ayuda con tecnología
- Trámites y gestiones
- Reparaciones menores
- Compañía y conversación

**Cómo funciona:**

**Si necesitas ayuda:**
1. Publica tu solicitud
2. Describe qué necesitas
3. Indica urgencia y disponibilidad
4. Espera ofertas de vecinos
5. Acepta la mejor opción
6. Coordina los detalles
7. Valora la ayuda recibida

**Si puedes ayudar:**
1. Mira solicitudes activas
2. Ofrécete para ayudar
3. Coordina con el solicitante
4. Presta tu ayuda
5. Recibe valoración y puntos

**Sistema de puntos:**
- Ganas puntos por ayudar
- Reconocimiento comunitario
- Ranking de solidaridad
- Sin intercambio monetario

**Principios:**
- Ayuda desinteresada
- Reciprocidad natural
- Sin ánimo de lucro
- Confianza y respeto
- Comunidad más fuerte

**Categorías urgencia:**
- Urgente: Necesidad inmediata
- Alta: En 24 horas
- Media: En pocos días
- Baja: Cuando sea posible
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Tengo que pagar por la ayuda?',
                'respuesta' => 'No, es ayuda gratuita entre vecinos. Puedes agradecer como quieras pero no hay pago obligatorio.',
            ],
            [
                'pregunta' => '¿Y si nadie me ayuda?',
                'respuesta' => 'Intenta reformular tu solicitud o ampliar el radio. También puedes contactar con el coordinador.',
            ],
            [
                'pregunta' => '¿Estoy obligado a ayudar?',
                'respuesta' => 'No, solo si quieres y puedes. La ayuda debe ser siempre voluntaria.',
            ],
        ];
    }

    // ─── Panel Unificado de Gestión ─────────────────────────────

    /**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'ayuda_vecinal',
            'label' => __('Ayuda Vecinal', 'flavor-chat-ia'),
            'icon' => 'dashicons-heart',
            'capability' => 'manage_options',
            'categoria' => 'comunidad',
            'paginas' => [
                [
                    'slug' => 'ayuda-vecinal-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'ayuda-vecinal-solicitudes',
                    'titulo' => __('Solicitudes', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_solicitudes'],
                    'badge' => [$this, 'contar_solicitudes_abiertas'],
                ],
                [
                    'slug' => 'ayuda-vecinal-voluntarios',
                    'titulo' => __('Voluntarios', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_voluntarios'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta solicitudes abiertas
     *
     * @return int
     */
    public function contar_solicitudes_abiertas() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'abierta'"
        );
    }

    /**
     * Estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';
        $tabla_ofertas = $wpdb->prefix . 'flavor_ayuda_ofertas';
        $estadisticas = [];

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            return $estadisticas;
        }

        // Solicitudes abiertas
        $solicitudes_abiertas = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'abierta'"
        );
        $estadisticas[] = [
            'icon' => 'dashicons-sos',
            'valor' => $solicitudes_abiertas,
            'label' => __('Solicitudes abiertas', 'flavor-chat-ia'),
            'color' => $solicitudes_abiertas > 0 ? 'orange' : 'gray',
            'enlace' => admin_url('admin.php?page=ayuda-vecinal-solicitudes'),
        ];

        // Total de voluntarios activos
        if (Flavor_Chat_Helpers::tabla_existe($tabla_ofertas)) {
            $total_voluntarios = (int) $wpdb->get_var(
                "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_ofertas WHERE activa = 1"
            );
            $estadisticas[] = [
                'icon' => 'dashicons-groups',
                'valor' => $total_voluntarios,
                'label' => __('Voluntarios activos', 'flavor-chat-ia'),
                'color' => $total_voluntarios > 0 ? 'green' : 'gray',
                'enlace' => admin_url('admin.php?page=ayuda-vecinal-voluntarios'),
            ];
        }

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard de ayuda vecinal
     */
    public function render_admin_dashboard() {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';
        $tabla_ofertas = $wpdb->prefix . 'flavor_ayuda_ofertas';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_ayuda_valoraciones';

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Dashboard de Ayuda Vecinal', 'flavor-chat-ia'), [
            ['label' => __('Nueva Solicitud', 'flavor-chat-ia'), 'url' => '#', 'class' => 'button-primary'],
        ]);

        // Estadísticas generales
        $solicitudes_abiertas = Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)
            ? (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'abierta'")
            : 0;

        $solicitudes_completadas = Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)
            ? (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'completada'")
            : 0;

        $total_voluntarios = Flavor_Chat_Helpers::tabla_existe($tabla_ofertas)
            ? (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_ofertas WHERE activa = 1")
            : 0;

        $solicitudes_urgentes = Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)
            ? (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado = 'abierta' AND urgencia IN ('alta', 'urgente')")
            : 0;

        echo '<div class="flavor-stats-grid">';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($solicitudes_abiertas) . '</span><span class="stat-label">' . __('Solicitudes Abiertas', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($solicitudes_completadas) . '</span><span class="stat-label">' . __('Ayudas Completadas', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($total_voluntarios) . '</span><span class="stat-label">' . __('Voluntarios Activos', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card' . ($solicitudes_urgentes > 0 ? ' stat-urgent' : '') . '"><span class="stat-number">' . esc_html($solicitudes_urgentes) . '</span><span class="stat-label">' . __('Urgentes', 'flavor-chat-ia') . '</span></div>';
        echo '</div>';

        echo '<p>' . __('La Red de Ayuda Vecinal conecta a vecinos que necesitan ayuda con voluntarios dispuestos a colaborar. Unidos somos más fuertes.', 'flavor-chat-ia') . '</p>';
        echo '</div>';
    }

    /**
     * Renderiza la página de solicitudes
     */
    public function render_admin_solicitudes() {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestión de Solicitudes', 'flavor-chat-ia'));

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            echo '<p>' . __('Las tablas no están creadas.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $solicitudes = $wpdb->get_results(
            "SELECT * FROM $tabla_solicitudes ORDER BY
                CASE urgencia
                    WHEN 'urgente' THEN 1
                    WHEN 'alta' THEN 2
                    WHEN 'media' THEN 3
                    WHEN 'baja' THEN 4
                END,
                fecha_solicitud DESC
             LIMIT 50",
            ARRAY_A
        );

        if (!empty($solicitudes)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Título', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Solicitante', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Categoría', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Urgencia', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Estado', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Fecha', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Acciones', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ($solicitudes as $solicitud) {
                $solicitante = get_userdata($solicitud['solicitante_id']);
                $clase_urgencia = $this->obtener_clase_urgencia($solicitud['urgencia']);
                $clase_estado = $this->obtener_clase_estado_solicitud($solicitud['estado']);

                echo '<tr>';
                echo '<td><strong>' . esc_html($solicitud['titulo']) . '</strong></td>';
                echo '<td>' . esc_html($solicitante ? $solicitante->display_name : __('Vecino', 'flavor-chat-ia')) . '</td>';
                echo '<td>' . esc_html(ucfirst(str_replace('_', ' ', $solicitud['categoria']))) . '</td>';
                echo '<td><span class="' . esc_attr($clase_urgencia) . '">' . esc_html(ucfirst($solicitud['urgencia'])) . '</span></td>';
                echo '<td><span class="' . esc_attr($clase_estado) . '">' . esc_html(ucfirst($solicitud['estado'])) . '</span></td>';
                echo '<td>' . esc_html(date_i18n('d/m/Y H:i', strtotime($solicitud['fecha_solicitud']))) . '</td>';
                echo '<td><a href="#" class="button button-small av-ver-solicitud" data-id="' . esc_attr($solicitud['id']) . '">' . __('Ver', 'flavor-chat-ia') . '</a></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay solicitudes registradas.', 'flavor-chat-ia') . '</p>';
        }

        // Modal ver solicitud
        echo '<div id="modal-ver-solicitud" style="display:none;">
            <div class="modal-overlay" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:100000;">
                <div class="modal-content" style="position:relative;max-width:600px;margin:50px auto;background:#fff;padding:20px;border-radius:4px;">
                    <h2>' . __('Detalle de Solicitud', 'flavor-chat-ia') . '</h2>
                    <div id="contenido-solicitud"></div>
                    <p><button type="button" class="button" id="cerrar-modal-solicitud">' . __('Cerrar', 'flavor-chat-ia') . '</button></p>
                </div>
            </div>
        </div>';

        echo '<script>
        jQuery(document).ready(function($) {
            $(".av-ver-solicitud").on("click", function(e) {
                e.preventDefault();
                var id = $(this).data("id");
                var $row = $(this).closest("tr");
                var titulo = $row.find("td:eq(0)").text();
                var solicitante = $row.find("td:eq(1)").text();
                var categoria = $row.find("td:eq(2)").text();
                var urgencia = $row.find("td:eq(3)").text();
                var estado = $row.find("td:eq(4)").text();
                var fecha = $row.find("td:eq(5)").text();

                var html = "<table class=\"form-table\">";
                html += "<tr><th>Título:</th><td>" + titulo + "</td></tr>";
                html += "<tr><th>Solicitante:</th><td>" + solicitante + "</td></tr>";
                html += "<tr><th>Categoría:</th><td>" + categoria + "</td></tr>";
                html += "<tr><th>Urgencia:</th><td>" + urgencia + "</td></tr>";
                html += "<tr><th>Estado:</th><td>" + estado + "</td></tr>";
                html += "<tr><th>Fecha:</th><td>" + fecha + "</td></tr>";
                html += "</table>";
                $("#contenido-solicitud").html(html);
                $("#modal-ver-solicitud").fadeIn();
            });
            $("#cerrar-modal-solicitud, .modal-overlay").on("click", function(e) {
                if (e.target === this) $("#modal-ver-solicitud").fadeOut();
            });
        });
        </script>';

        echo '</div>';
    }

    /**
     * Renderiza la página de voluntarios
     */
    public function render_admin_voluntarios() {
        global $wpdb;
        $tabla_ofertas = $wpdb->prefix . 'flavor_ayuda_ofertas';
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Voluntarios de la Red', 'flavor-chat-ia'));

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_ofertas)) {
            echo '<p>' . __('Las tablas no están creadas.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        // Obtener voluntarios con ofertas activas
        $voluntarios = $wpdb->get_results(
            "SELECT usuario_id, COUNT(*) as total_ofertas, MAX(fecha_creacion) as ultima_oferta
             FROM $tabla_ofertas
             WHERE activa = 1
             GROUP BY usuario_id
             ORDER BY total_ofertas DESC
             LIMIT 50",
            ARRAY_A
        );

        if (!empty($voluntarios)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Voluntario', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Ofertas Activas', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Ayudas Realizadas', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Puntos Solidaridad', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Última Actividad', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Acciones', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ($voluntarios as $voluntario) {
                $usuario = get_userdata($voluntario['usuario_id']);

                // Contar ayudas realizadas
                $ayudas_realizadas = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_solicitudes
                     WHERE ayudante_id = %d AND estado = 'completada'",
                    $voluntario['usuario_id']
                ));

                // Calcular puntos de solidaridad
                $puntos_por_ayuda = $this->get_setting('puntos_por_ayuda', 10);
                $puntos_solidaridad = $ayudas_realizadas * $puntos_por_ayuda;

                echo '<tr>';
                echo '<td><strong>' . esc_html($usuario ? $usuario->display_name : __('Voluntario', 'flavor-chat-ia')) . '</strong></td>';
                echo '<td>' . esc_html($voluntario['total_ofertas']) . '</td>';
                echo '<td>' . esc_html($ayudas_realizadas) . '</td>';
                echo '<td><span class="puntos-solidaridad">' . esc_html($puntos_solidaridad) . ' pts</span></td>';
                echo '<td>' . esc_html(date_i18n('d/m/Y', strtotime($voluntario['ultima_oferta']))) . '</td>';
                echo '<td><a href="' . esc_url(admin_url('user-edit.php?user_id=' . $voluntario['usuario_id'])) . '" class="button button-small">' . __('Ver Perfil', 'flavor-chat-ia') . '</a></td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay voluntarios registrados en la red de ayuda vecinal.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Obtiene la clase CSS según la urgencia
     *
     * @param string $urgencia Nivel de urgencia
     * @return string Clase CSS
     */
    private function obtener_clase_urgencia($urgencia) {
        $clases = [
            'baja' => 'urgencia-baja',
            'media' => 'urgencia-media',
            'alta' => 'urgencia-alta',
            'urgente' => 'urgencia-urgente',
        ];
        return $clases[$urgencia] ?? 'urgencia-media';
    }

    /**
     * Obtiene la clase CSS según el estado de la solicitud
     *
     * @param string $estado Estado de la solicitud
     * @return string Clase CSS
     */
    private function obtener_clase_estado_solicitud($estado) {
        $clases = [
            'abierta' => 'status-open',
            'asignada' => 'status-assigned',
            'en_curso' => 'status-in-progress',
            'completada' => 'status-completed',
            'cancelada' => 'status-cancelled',
            'expirada' => 'status-expired',
        ];
        return $clases[$estado] ?? 'status-default';
    }

    // ─── REST API ──────────────────────────────────────────────────

    /**
     * Registra las rutas de la REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // GET /flavor/v1/ayuda-vecinal - Listar solicitudes activas
        register_rest_route($namespace, '/ayuda-vecinal', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'api_listar_solicitudes'],
            'permission_callback' => '__return_true',
            'args' => [
                'categoria' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'urgencia' => [
                    'type' => 'string',
                    'enum' => ['baja', 'media', 'alta', 'urgente'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'estado' => [
                    'type' => 'string',
                    'enum' => ['abierta', 'asignada', 'en_curso', 'completada', 'cancelada', 'expirada'],
                    'default' => 'abierta',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'per_page' => [
                    'type' => 'integer',
                    'default' => 20,
                    'minimum' => 1,
                    'maximum' => 100,
                    'sanitize_callback' => 'absint',
                ],
                'page' => [
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // GET /flavor/v1/ayuda-vecinal/{id} - Obtener una solicitud
        register_rest_route($namespace, '/ayuda-vecinal/(?P<id>\d+)', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'api_obtener_solicitud'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // POST /flavor/v1/ayuda-vecinal - Crear solicitud
        register_rest_route($namespace, '/ayuda-vecinal', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'api_crear_solicitud'],
            'permission_callback' => [$this, 'api_verificar_usuario_autenticado'],
            'args' => [
                'categoria' => [
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'titulo' => [
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'descripcion' => [
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'urgencia' => [
                    'type' => 'string',
                    'enum' => ['baja', 'media', 'alta', 'urgente'],
                    'default' => 'media',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'ubicacion' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'ubicacion_lat' => [
                    'type' => 'number',
                    
                ],
                'ubicacion_lng' => [
                    'type' => 'number',
                    
                ],
                'fecha_necesaria' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'duracion_estimada_minutos' => [
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'necesita_desplazamiento' => [
                    'type' => 'boolean',
                    'default' => false,
                ],
                'requiere_habilidad_especifica' => [
                    'type' => 'boolean',
                    'default' => false,
                ],
                'habilidades_requeridas' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'num_personas_necesarias' => [
                    'type' => 'integer',
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ],
                'compensacion' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);

        // POST /flavor/v1/ayuda-vecinal/{id}/responder - Responder a solicitud (ofrecer ayuda)
        register_rest_route($namespace, '/ayuda-vecinal/(?P<id>\d+)/responder', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'api_responder_solicitud'],
            'permission_callback' => [$this, 'api_verificar_usuario_autenticado'],
            'args' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'sanitize_callback' => 'absint',
                ],
                'mensaje' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'disponibilidad_propuesta' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // GET /flavor/v1/ayuda-vecinal/mis-solicitudes - Solicitudes del usuario
        register_rest_route($namespace, '/ayuda-vecinal/mis-solicitudes', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'api_mis_solicitudes'],
            'permission_callback' => [$this, 'api_verificar_usuario_autenticado'],
            'args' => [
                'tipo' => [
                    'type' => 'string',
                    'enum' => ['creadas', 'respondidas', 'todas'],
                    'default' => 'todas',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'estado' => [
                    'type' => 'string',
                    'enum' => ['abierta', 'asignada', 'en_curso', 'completada', 'cancelada', 'expirada', 'todas'],
                    'default' => 'todas',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'per_page' => [
                    'type' => 'integer',
                    'default' => 20,
                    'minimum' => 1,
                    'maximum' => 100,
                    'sanitize_callback' => 'absint',
                ],
                'page' => [
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // PUT /flavor/v1/ayuda-vecinal/{id} - Actualizar solicitud
        register_rest_route($namespace, '/ayuda-vecinal/(?P<id>\d+)', [
            'methods' => \WP_REST_Server::EDITABLE,
            'callback' => [$this, 'api_actualizar_solicitud'],
            'permission_callback' => [$this, 'api_verificar_propietario_solicitud'],
            'args' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'sanitize_callback' => 'absint',
                ],
                'estado' => [
                    'type' => 'string',
                    'enum' => ['abierta', 'asignada', 'en_curso', 'completada', 'cancelada'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'ayudante_id' => [
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // DELETE /flavor/v1/ayuda-vecinal/{id} - Cancelar solicitud
        register_rest_route($namespace, '/ayuda-vecinal/(?P<id>\d+)', [
            'methods' => \WP_REST_Server::DELETABLE,
            'callback' => [$this, 'api_cancelar_solicitud'],
            'permission_callback' => [$this, 'api_verificar_propietario_solicitud'],
            'args' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // GET /flavor/v1/ayuda-vecinal/{id}/respuestas - Obtener respuestas de una solicitud
        register_rest_route($namespace, '/ayuda-vecinal/(?P<id>\d+)/respuestas', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'api_obtener_respuestas'],
            'permission_callback' => [$this, 'api_verificar_propietario_solicitud'],
            'args' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // POST /flavor/v1/ayuda-vecinal/respuestas/{id}/aceptar - Aceptar una respuesta
        register_rest_route($namespace, '/ayuda-vecinal/respuestas/(?P<id>\d+)/aceptar', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'api_aceptar_respuesta'],
            'permission_callback' => [$this, 'api_verificar_usuario_autenticado'],
            'args' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // GET /flavor/v1/ayuda-vecinal/categorias - Listar categorias disponibles
        register_rest_route($namespace, '/ayuda-vecinal/categorias', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'api_listar_categorias'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Verifica si el usuario está autenticado
     *
     * @return bool|\WP_Error
     */
    public function api_verificar_usuario_autenticado() {
        if (!is_user_logged_in()) {
            return new \WP_Error(
                'rest_not_logged_in',
                __('Debes iniciar sesión para realizar esta acción.', 'flavor-chat-ia'),
                ['status' => 401]
            );
        }
        return true;
    }

    /**
     * Verifica si el usuario es propietario de la solicitud
     *
     * @param \WP_REST_Request $request Solicitud REST
     * @return bool|\WP_Error
     */
    public function api_verificar_propietario_solicitud($request) {
        if (!is_user_logged_in()) {
            return new \WP_Error(
                'rest_not_logged_in',
                __('Debes iniciar sesión para realizar esta acción.', 'flavor-chat-ia'),
                ['status' => 401]
            );
        }

        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';
        $solicitud_id = $request->get_param('id');
        $usuario_id = get_current_user_id();

        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT solicitante_id FROM $tabla_solicitudes WHERE id = %d",
            $solicitud_id
        ));

        if (!$solicitud) {
            return new \WP_Error(
                'rest_solicitud_not_found',
                __('Solicitud no encontrada.', 'flavor-chat-ia'),
                ['status' => 404]
            );
        }

        if ((int) $solicitud->solicitante_id !== $usuario_id && !current_user_can('manage_options')) {
            return new \WP_Error(
                'rest_forbidden',
                __('No tienes permiso para modificar esta solicitud.', 'flavor-chat-ia'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * API: Listar solicitudes activas
     *
     * @param \WP_REST_Request $request Solicitud REST
     * @return \WP_REST_Response|\WP_Error
     */
    public function api_listar_solicitudes($request) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            return new \WP_Error(
                'rest_table_not_found',
                __('Las tablas del módulo no están creadas.', 'flavor-chat-ia'),
                ['status' => 500]
            );
        }

        $parametros_consulta = [];
        $condiciones_where = [];

        // Filtrar por estado
        $estado = $request->get_param('estado');
        if ($estado) {
            $condiciones_where[] = 'estado = %s';
            $parametros_consulta[] = $estado;
        }

        // Filtrar por categoría
        $categoria = $request->get_param('categoria');
        if ($categoria) {
            $condiciones_where[] = 'categoria = %s';
            $parametros_consulta[] = $categoria;
        }

        // Filtrar por urgencia
        $urgencia = $request->get_param('urgencia');
        if ($urgencia) {
            $condiciones_where[] = 'urgencia = %s';
            $parametros_consulta[] = $urgencia;
        }

        // Paginación
        $por_pagina = $request->get_param('per_page');
        $pagina = $request->get_param('page');
        $offset = ($pagina - 1) * $por_pagina;

        // Construir consulta
        $clausula_where = !empty($condiciones_where) ? 'WHERE ' . implode(' AND ', $condiciones_where) : '';
        $consulta_sql = "SELECT * FROM $tabla_solicitudes $clausula_where
                         ORDER BY FIELD(urgencia, 'urgente', 'alta', 'media', 'baja'), fecha_solicitud DESC
                         LIMIT %d OFFSET %d";

        $parametros_consulta[] = $por_pagina;
        $parametros_consulta[] = $offset;

        if (!empty($condiciones_where)) {
            $solicitudes = $wpdb->get_results($wpdb->prepare($consulta_sql, ...$parametros_consulta));
        } else {
            $solicitudes = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_solicitudes
                 ORDER BY FIELD(urgencia, 'urgente', 'alta', 'media', 'baja'), fecha_solicitud DESC
                 LIMIT %d OFFSET %d",
                $por_pagina,
                $offset
            ));
        }

        // Obtener total para paginación
        $consulta_total = "SELECT COUNT(*) FROM $tabla_solicitudes $clausula_where";
        if (!empty($condiciones_where)) {
            $total = (int) $wpdb->get_var($wpdb->prepare($consulta_total, ...array_slice($parametros_consulta, 0, -2)));
        } else {
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes");
        }

        $solicitudes_formateadas = array_map([$this, 'formatear_solicitud_api'], $solicitudes);

        $respuesta = new \WP_REST_Response($solicitudes_formateadas, 200);
        $respuesta->header('X-WP-Total', $total);
        $respuesta->header('X-WP-TotalPages', ceil($total / $por_pagina));

        return $respuesta;
    }

    /**
     * API: Obtener una solicitud específica
     *
     * @param \WP_REST_Request $request Solicitud REST
     * @return \WP_REST_Response|\WP_Error
     */
    public function api_obtener_solicitud($request) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        $solicitud_id = $request->get_param('id');

        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_solicitudes WHERE id = %d",
            $solicitud_id
        ));

        if (!$solicitud) {
            return new \WP_Error(
                'rest_solicitud_not_found',
                __('Solicitud no encontrada.', 'flavor-chat-ia'),
                ['status' => 404]
            );
        }

        return new \WP_REST_Response($this->formatear_solicitud_api($solicitud, true), 200);
    }

    /**
     * API: Crear una nueva solicitud
     *
     * @param \WP_REST_Request $request Solicitud REST
     * @return \WP_REST_Response|\WP_Error
     */
    public function api_crear_solicitud($request) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
            return new \WP_Error(
                'rest_table_not_found',
                __('Las tablas del módulo no están creadas.', 'flavor-chat-ia'),
                ['status' => 500]
            );
        }

        $usuario_id = get_current_user_id();

        // Validar categoría
        $categorias_permitidas = $this->get_setting('categorias_ayuda', []);
        $categoria = $request->get_param('categoria');
        if (!empty($categorias_permitidas) && !in_array($categoria, $categorias_permitidas, true)) {
            return new \WP_Error(
                'rest_invalid_categoria',
                __('La categoría seleccionada no es válida.', 'flavor-chat-ia'),
                ['status' => 400]
            );
        }

        $datos_solicitud = [
            'solicitante_id' => $usuario_id,
            'categoria' => $categoria,
            'titulo' => $request->get_param('titulo'),
            'descripcion' => $request->get_param('descripcion'),
            'urgencia' => $request->get_param('urgencia') ?: 'media',
            'estado' => 'abierta',
            'fecha_solicitud' => current_time('mysql'),
        ];

        // Campos opcionales
        $campos_opcionales = [
            'ubicacion',
            'ubicacion_lat',
            'ubicacion_lng',
            'fecha_necesaria',
            'duracion_estimada_minutos',
            'necesita_desplazamiento',
            'requiere_habilidad_especifica',
            'habilidades_requeridas',
            'num_personas_necesarias',
            'compensacion',
        ];

        foreach ($campos_opcionales as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                $datos_solicitud[$campo] = $valor;
            }
        }

        $resultado_insercion = $wpdb->insert($tabla_solicitudes, $datos_solicitud);

        if ($resultado_insercion === false) {
            return new \WP_Error(
                'rest_db_error',
                __('Error al crear la solicitud.', 'flavor-chat-ia'),
                ['status' => 500]
            );
        }

        $nueva_solicitud_id = $wpdb->insert_id;
        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_solicitudes WHERE id = %d",
            $nueva_solicitud_id
        ));

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Solicitud creada correctamente.', 'flavor-chat-ia'),
            'solicitud' => $this->formatear_solicitud_api($solicitud),
        ], 201);
    }

    /**
     * API: Responder a una solicitud (ofrecer ayuda)
     *
     * @param \WP_REST_Request $request Solicitud REST
     * @return \WP_REST_Response|\WP_Error
     */
    public function api_responder_solicitud($request) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';
        $tabla_respuestas = $wpdb->prefix . 'flavor_ayuda_respuestas';

        $solicitud_id = $request->get_param('id');
        $usuario_id = get_current_user_id();

        // Verificar que la solicitud existe y está abierta
        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_solicitudes WHERE id = %d",
            $solicitud_id
        ));

        if (!$solicitud) {
            return new \WP_Error(
                'rest_solicitud_not_found',
                __('Solicitud no encontrada.', 'flavor-chat-ia'),
                ['status' => 404]
            );
        }

        if ($solicitud->estado !== 'abierta') {
            return new \WP_Error(
                'rest_solicitud_not_open',
                __('Esta solicitud ya no está abierta para respuestas.', 'flavor-chat-ia'),
                ['status' => 400]
            );
        }

        // Verificar que no es el propio solicitante
        if ((int) $solicitud->solicitante_id === $usuario_id) {
            return new \WP_Error(
                'rest_cannot_respond_own',
                __('No puedes responder a tu propia solicitud.', 'flavor-chat-ia'),
                ['status' => 400]
            );
        }

        // Verificar que no ha respondido ya
        $respuesta_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_respuestas WHERE solicitud_id = %d AND ayudante_id = %d",
            $solicitud_id,
            $usuario_id
        ));

        if ($respuesta_existente) {
            return new \WP_Error(
                'rest_already_responded',
                __('Ya has ofrecido ayuda para esta solicitud.', 'flavor-chat-ia'),
                ['status' => 400]
            );
        }

        $datos_respuesta = [
            'solicitud_id' => $solicitud_id,
            'ayudante_id' => $usuario_id,
            'mensaje' => $request->get_param('mensaje'),
            'estado' => 'pendiente',
            'fecha_respuesta' => current_time('mysql'),
        ];

        $disponibilidad = $request->get_param('disponibilidad_propuesta');
        if ($disponibilidad) {
            $datos_respuesta['disponibilidad_propuesta'] = $disponibilidad;
        }

        $resultado_insercion = $wpdb->insert($tabla_respuestas, $datos_respuesta);

        if ($resultado_insercion === false) {
            return new \WP_Error(
                'rest_db_error',
                __('Error al registrar la respuesta.', 'flavor-chat-ia'),
                ['status' => 500]
            );
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Tu oferta de ayuda ha sido registrada.', 'flavor-chat-ia'),
            'respuesta_id' => $wpdb->insert_id,
        ], 201);
    }

    /**
     * API: Obtener solicitudes del usuario actual
     *
     * @param \WP_REST_Request $request Solicitud REST
     * @return \WP_REST_Response|\WP_Error
     */
    public function api_mis_solicitudes($request) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';
        $tabla_respuestas = $wpdb->prefix . 'flavor_ayuda_respuestas';

        $usuario_id = get_current_user_id();
        $tipo = $request->get_param('tipo');
        $estado = $request->get_param('estado');
        $por_pagina = $request->get_param('per_page');
        $pagina = $request->get_param('page');
        $offset = ($pagina - 1) * $por_pagina;

        $resultado = [
            'creadas' => [],
            'respondidas' => [],
        ];

        // Solicitudes creadas por el usuario
        if ($tipo === 'creadas' || $tipo === 'todas') {
            $condicion_estado = ($estado !== 'todas') ? $wpdb->prepare(' AND estado = %s', $estado) : '';

            $solicitudes_creadas = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_solicitudes
                 WHERE solicitante_id = %d $condicion_estado
                 ORDER BY fecha_solicitud DESC
                 LIMIT %d OFFSET %d",
                $usuario_id,
                $por_pagina,
                $offset
            ));

            $resultado['creadas'] = array_map([$this, 'formatear_solicitud_api'], $solicitudes_creadas);
        }

        // Solicitudes donde el usuario ha respondido
        if ($tipo === 'respondidas' || $tipo === 'todas') {
            $condicion_estado_resp = ($estado !== 'todas') ? $wpdb->prepare(' AND s.estado = %s', $estado) : '';

            $solicitudes_respondidas = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, r.estado as estado_respuesta, r.mensaje as mi_mensaje
                 FROM $tabla_solicitudes s
                 INNER JOIN $tabla_respuestas r ON s.id = r.solicitud_id
                 WHERE r.ayudante_id = %d $condicion_estado_resp
                 ORDER BY r.fecha_respuesta DESC
                 LIMIT %d OFFSET %d",
                $usuario_id,
                $por_pagina,
                $offset
            ));

            $resultado['respondidas'] = array_map(function($solicitud) {
                $formateada = $this->formatear_solicitud_api($solicitud);
                $formateada['mi_respuesta'] = [
                    'estado' => $solicitud->estado_respuesta,
                    'mensaje' => $solicitud->mi_mensaje,
                ];
                return $formateada;
            }, $solicitudes_respondidas);
        }

        return new \WP_REST_Response($resultado, 200);
    }

    /**
     * API: Actualizar una solicitud
     *
     * @param \WP_REST_Request $request Solicitud REST
     * @return \WP_REST_Response|\WP_Error
     */
    public function api_actualizar_solicitud($request) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        $solicitud_id = $request->get_param('id');
        $datos_actualizar = [];

        // Campos que se pueden actualizar
        $estado = $request->get_param('estado');
        if ($estado) {
            $datos_actualizar['estado'] = $estado;

            // Si se marca como completada, registrar la fecha
            if ($estado === 'completada') {
                $datos_actualizar['fecha_completado'] = current_time('mysql');
            }

            // Si se asigna, registrar la fecha de asignación
            if ($estado === 'asignada') {
                $datos_actualizar['fecha_asignacion'] = current_time('mysql');
            }
        }

        $ayudante_id = $request->get_param('ayudante_id');
        if ($ayudante_id) {
            $datos_actualizar['ayudante_id'] = $ayudante_id;
        }

        if (empty($datos_actualizar)) {
            return new \WP_Error(
                'rest_no_data',
                __('No se proporcionaron datos para actualizar.', 'flavor-chat-ia'),
                ['status' => 400]
            );
        }

        $resultado_actualizacion = $wpdb->update(
            $tabla_solicitudes,
            $datos_actualizar,
            ['id' => $solicitud_id]
        );

        if ($resultado_actualizacion === false) {
            return new \WP_Error(
                'rest_db_error',
                __('Error al actualizar la solicitud.', 'flavor-chat-ia'),
                ['status' => 500]
            );
        }

        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_solicitudes WHERE id = %d",
            $solicitud_id
        ));

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Solicitud actualizada correctamente.', 'flavor-chat-ia'),
            'solicitud' => $this->formatear_solicitud_api($solicitud),
        ], 200);
    }

    /**
     * API: Cancelar una solicitud
     *
     * @param \WP_REST_Request $request Solicitud REST
     * @return \WP_REST_Response|\WP_Error
     */
    public function api_cancelar_solicitud($request) {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        $solicitud_id = $request->get_param('id');

        $resultado_actualizacion = $wpdb->update(
            $tabla_solicitudes,
            ['estado' => 'cancelada'],
            ['id' => $solicitud_id]
        );

        if ($resultado_actualizacion === false) {
            return new \WP_Error(
                'rest_db_error',
                __('Error al cancelar la solicitud.', 'flavor-chat-ia'),
                ['status' => 500]
            );
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Solicitud cancelada correctamente.', 'flavor-chat-ia'),
        ], 200);
    }

    /**
     * API: Obtener respuestas de una solicitud
     *
     * @param \WP_REST_Request $request Solicitud REST
     * @return \WP_REST_Response|\WP_Error
     */
    public function api_obtener_respuestas($request) {
        global $wpdb;
        $tabla_respuestas = $wpdb->prefix . 'flavor_ayuda_respuestas';

        $solicitud_id = $request->get_param('id');

        $respuestas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_respuestas WHERE solicitud_id = %d ORDER BY fecha_respuesta DESC",
            $solicitud_id
        ));

        $respuestas_formateadas = array_map(function($respuesta) {
            $ayudante = get_userdata($respuesta->ayudante_id);
            return [
                'id' => (int) $respuesta->id,
                'ayudante' => [
                    'id' => (int) $respuesta->ayudante_id,
                    'nombre' => $ayudante ? $ayudante->display_name : __('Vecino', 'flavor-chat-ia'),
                    'avatar' => $ayudante ? get_avatar_url($ayudante->ID, ['size' => 96]) : '',
                ],
                'mensaje' => $respuesta->mensaje,
                'disponibilidad_propuesta' => $respuesta->disponibilidad_propuesta,
                'estado' => $respuesta->estado,
                'fecha_respuesta' => $respuesta->fecha_respuesta,
                'fecha_formateada' => human_time_diff(strtotime($respuesta->fecha_respuesta), current_time('timestamp')) . ' ' . __('atrás', 'flavor-chat-ia'),
            ];
        }, $respuestas);

        return new \WP_REST_Response($respuestas_formateadas, 200);
    }

    /**
     * API: Aceptar una respuesta (asignar ayudante)
     *
     * @param \WP_REST_Request $request Solicitud REST
     * @return \WP_REST_Response|\WP_Error
     */
    public function api_aceptar_respuesta($request) {
        global $wpdb;
        $tabla_respuestas = $wpdb->prefix . 'flavor_ayuda_respuestas';
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        $respuesta_id = $request->get_param('id');
        $usuario_id = get_current_user_id();

        // Obtener la respuesta
        $respuesta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_respuestas WHERE id = %d",
            $respuesta_id
        ));

        if (!$respuesta) {
            return new \WP_Error(
                'rest_respuesta_not_found',
                __('Respuesta no encontrada.', 'flavor-chat-ia'),
                ['status' => 404]
            );
        }

        // Verificar que el usuario es el propietario de la solicitud
        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_solicitudes WHERE id = %d",
            $respuesta->solicitud_id
        ));

        if (!$solicitud || ((int) $solicitud->solicitante_id !== $usuario_id && !current_user_can('manage_options'))) {
            return new \WP_Error(
                'rest_forbidden',
                __('No tienes permiso para aceptar esta respuesta.', 'flavor-chat-ia'),
                ['status' => 403]
            );
        }

        // Actualizar la respuesta como aceptada
        $wpdb->update(
            $tabla_respuestas,
            ['estado' => 'aceptada'],
            ['id' => $respuesta_id]
        );

        // Rechazar otras respuestas pendientes
        $wpdb->update(
            $tabla_respuestas,
            ['estado' => 'rechazada'],
            [
                'solicitud_id' => $respuesta->solicitud_id,
                'estado' => 'pendiente',
            ]
        );

        // Actualizar la solicitud con el ayudante asignado
        $wpdb->update(
            $tabla_solicitudes,
            [
                'estado' => 'asignada',
                'ayudante_id' => $respuesta->ayudante_id,
                'fecha_asignacion' => current_time('mysql'),
            ],
            ['id' => $respuesta->solicitud_id]
        );

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Ayudante aceptado correctamente.', 'flavor-chat-ia'),
        ], 200);
    }

    /**
     * API: Listar categorías disponibles
     *
     * @param \WP_REST_Request $request Solicitud REST
     * @return \WP_REST_Response
     */
    public function api_listar_categorias($request) {
        $categorias = $this->get_setting('categorias_ayuda', []);

        $iconos_categorias = [
            'compras' => 'dashicons-cart',
            'cuidado_mayores' => 'dashicons-heart',
            'cuidado_ninos' => 'dashicons-smiley',
            'mascotas' => 'dashicons-pets',
            'transporte' => 'dashicons-car',
            'tecnologia' => 'dashicons-laptop',
            'tramites' => 'dashicons-clipboard',
            'reparaciones' => 'dashicons-admin-tools',
            'companía' => 'dashicons-groups',
            'otro' => 'dashicons-admin-generic',
        ];

        $categorias_formateadas = array_map(function($categoria) use ($iconos_categorias) {
            return [
                'id' => $categoria,
                'nombre' => ucfirst(str_replace('_', ' ', $categoria)),
                'icono' => $iconos_categorias[$categoria] ?? 'dashicons-admin-generic',
            ];
        }, $categorias);

        return new \WP_REST_Response($categorias_formateadas, 200);
    }

    /**
     * Formatea una solicitud para la respuesta de la API
     *
     * @param object $solicitud Objeto de solicitud de la base de datos
     * @param bool   $incluir_detalles Si se deben incluir todos los detalles
     * @return array Solicitud formateada
     */
    private function formatear_solicitud_api($solicitud, $incluir_detalles = false) {
        $solicitante = get_userdata($solicitud->solicitante_id);

        $datos_formateados = [
            'id' => (int) $solicitud->id,
            'titulo' => $solicitud->titulo,
            'descripcion' => $solicitud->descripcion,
            'categoria' => $solicitud->categoria,
            'categoria_nombre' => ucfirst(str_replace('_', ' ', $solicitud->categoria)),
            'urgencia' => $solicitud->urgencia,
            'estado' => $solicitud->estado,
            'solicitante' => [
                'id' => (int) $solicitud->solicitante_id,
                'nombre' => $solicitante ? $solicitante->display_name : __('Vecino', 'flavor-chat-ia'),
                'avatar' => $solicitante ? get_avatar_url($solicitante->ID, ['size' => 96]) : '',
            ],
            'fecha_solicitud' => $solicitud->fecha_solicitud,
            'fecha_formateada' => human_time_diff(strtotime($solicitud->fecha_solicitud), current_time('timestamp')) . ' ' . __('atrás', 'flavor-chat-ia'),
        ];

        if ($incluir_detalles || $solicitud->ubicacion) {
            $datos_formateados['ubicacion'] = $solicitud->ubicacion;
            $datos_formateados['ubicacion_lat'] = $solicitud->ubicacion_lat ? (float) $solicitud->ubicacion_lat : null;
            $datos_formateados['ubicacion_lng'] = $solicitud->ubicacion_lng ? (float) $solicitud->ubicacion_lng : null;
        }

        if ($incluir_detalles) {
            $datos_formateados['fecha_necesaria'] = $solicitud->fecha_necesaria;
            $datos_formateados['duracion_estimada_minutos'] = $solicitud->duracion_estimada_minutos ? (int) $solicitud->duracion_estimada_minutos : null;
            $datos_formateados['necesita_desplazamiento'] = (bool) $solicitud->necesita_desplazamiento;
            $datos_formateados['requiere_habilidad_especifica'] = (bool) $solicitud->requiere_habilidad_especifica;
            $datos_formateados['habilidades_requeridas'] = $solicitud->habilidades_requeridas;
            $datos_formateados['num_personas_necesarias'] = (int) $solicitud->num_personas_necesarias;
            $datos_formateados['compensacion'] = $solicitud->compensacion;

            if ($solicitud->ayudante_id) {
                $ayudante = get_userdata($solicitud->ayudante_id);
                $datos_formateados['ayudante'] = [
                    'id' => (int) $solicitud->ayudante_id,
                    'nombre' => $ayudante ? $ayudante->display_name : __('Vecino', 'flavor-chat-ia'),
                    'avatar' => $ayudante ? get_avatar_url($ayudante->ID, ['size' => 96]) : '',
                ];
            }

            $datos_formateados['fecha_asignacion'] = $solicitud->fecha_asignacion;
            $datos_formateados['fecha_completado'] = $solicitud->fecha_completado;
        }

        return $datos_formateados;
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
            Flavor_Page_Creator::refresh_module_pages('ayuda_vecinal');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('ayuda-vecinal');
        if (!$pagina && !get_option('flavor_ayuda_vecinal_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['ayuda_vecinal']);
            update_option('flavor_ayuda_vecinal_pages_created', 1, false);
        }
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Ayuda Vecinal', 'flavor-chat-ia'),
                'slug' => 'ayuda-vecinal',
                'content' => '<h1>' . __('Ayuda Vecinal', 'flavor-chat-ia') . '</h1>
<p>' . __('Solicita o ofrece ayuda a tus vecinos', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="ayuda_vecinal" action="listar_solicitudes" columnas="2" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Solicitar Ayuda', 'flavor-chat-ia'),
                'slug' => 'solicitar',
                'content' => '<h1>' . __('Solicitar Ayuda', 'flavor-chat-ia') . '</h1>
<p>' . __('Describe qué necesitas', 'flavor-chat-ia') . '</p>

[flavor_module_form module="ayuda_vecinal" action="crear_solicitud"]',
                'parent' => 'ayuda-vecinal',
            ],
            [
                'title' => __('Ofrecer Ayuda', 'flavor-chat-ia'),
                'slug' => 'ofrecer',
                'content' => '<h1>' . __('Ofrecer Ayuda', 'flavor-chat-ia') . '</h1>
<p>' . __('Indica en qué puedes ayudar', 'flavor-chat-ia') . '</p>

[flavor_module_form module="ayuda_vecinal" action="ofrecer_ayuda"]',
                'parent' => 'ayuda-vecinal',
            ],
            [
                'title' => __('Mis Solicitudes', 'flavor-chat-ia'),
                'slug' => 'mis-solicitudes',
                'content' => '<h1>' . __('Mis Solicitudes', 'flavor-chat-ia') . '</h1>

[flavor_module_dashboard module="ayuda_vecinal"]',
                'parent' => 'ayuda-vecinal',
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
        add_submenu_page(null, __('Ayuda Vecinal - Dashboard', 'flavor-chat-ia'), __('Dashboard', 'flavor-chat-ia'), $capability, 'ayuda-vecinal', [$this, 'render_pagina_dashboard']);
        add_submenu_page(null, __('Ayuda Vecinal - Solicitudes', 'flavor-chat-ia'), __('Solicitudes', 'flavor-chat-ia'), $capability, 'ayuda-solicitudes', [$this, 'render_pagina_solicitudes']);
        add_submenu_page(null, __('Ayuda Vecinal - Voluntarios', 'flavor-chat-ia'), __('Voluntarios', 'flavor-chat-ia'), $capability, 'ayuda-voluntarios', [$this, 'render_pagina_voluntarios']);
        add_submenu_page(null, __('Ayuda Vecinal - Matches', 'flavor-chat-ia'), __('Matches', 'flavor-chat-ia'), $capability, 'ayuda-matches', [$this, 'render_pagina_matches']);
        add_submenu_page(null, __('Ayuda Vecinal - Estadísticas', 'flavor-chat-ia'), __('Estadísticas', 'flavor-chat-ia'), $capability, 'ayuda-estadisticas', [$this, 'render_pagina_estadisticas']);
    }

    public function render_pagina_dashboard() {
        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Dashboard Ayuda Vecinal', 'flavor-chat-ia') . '</h1></div>'; }
    }

    public function render_pagina_solicitudes() {
        $views_path = dirname(__FILE__) . '/views/solicitudes.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Gestión de Solicitudes', 'flavor-chat-ia') . '</h1></div>'; }
    }

    public function render_pagina_voluntarios() {
        $views_path = dirname(__FILE__) . '/views/voluntarios.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Gestión de Voluntarios', 'flavor-chat-ia') . '</h1></div>'; }
    }

    public function render_pagina_matches() {
        $views_path = dirname(__FILE__) . '/views/matches.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Matches de Ayuda', 'flavor-chat-ia') . '</h1></div>'; }
    }

    public function render_pagina_estadisticas() {
        $views_path = dirname(__FILE__) . '/views/estadisticas.php';
        if (file_exists($views_path)) { include $views_path; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Estadísticas de Ayuda', 'flavor-chat-ia') . '</h1></div>'; }
    }

    /**
     * AJAX: Obtener datos del dashboard
     */
    public function ajax_get_dashboard_data() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';
        $tabla_ofertas = $wpdb->prefix . 'flavor_ayuda_ofertas';
        $tabla_respuestas = $wpdb->prefix . 'flavor_ayuda_respuestas';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_ayuda_valoraciones';

        // KPIs
        $solicitudes_activas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_solicitudes WHERE estado IN ('pendiente', 'en_proceso')") ?: 0;
        $voluntarios_activos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_ofertas WHERE activa = 1") ?: 0;
        $ayudas_completadas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_respuestas WHERE estado = 'aceptada'") ?: 0;
        $horas_voluntariado = $wpdb->get_var("SELECT COALESCE(SUM(duracion_horas), 0) FROM $tabla_solicitudes WHERE estado = 'completada'") ?: 0;

        // Solicitudes por categoría
        $categorias_raw = $wpdb->get_results("SELECT categoria, COUNT(*) as total FROM $tabla_solicitudes GROUP BY categoria ORDER BY total DESC LIMIT 6");
        $categorias = [
            'labels' => [],
            'values' => [],
        ];
        if ($categorias_raw) {
            foreach ($categorias_raw as $cat) {
                $categorias['labels'][] = ucfirst($cat->categoria ?: __('Sin categoría', 'flavor-chat-ia'));
                $categorias['values'][] = (int) $cat->total;
            }
        } else {
            // Datos de ejemplo
            $categorias = [
                'labels' => [__('Compras', 'flavor-chat-ia'), __('Transporte', 'flavor-chat-ia'), __('Compañía', 'flavor-chat-ia'), __('Tecnología', 'flavor-chat-ia'), __('Trámites', 'flavor-chat-ia')],
                'values' => [15, 12, 10, 8, 5],
            ];
        }

        // Tendencia últimos 6 meses
        $tendencia = [
            'labels' => [],
            'solicitudes' => [],
            'ayudas' => [],
        ];
        for ($i = 5; $i >= 0; $i--) {
            $mes = date('Y-m', strtotime("-$i months"));
            $mes_label = date_i18n('M', strtotime("-$i months"));
            $tendencia['labels'][] = $mes_label;

            $count_sol = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_solicitudes WHERE DATE_FORMAT(fecha_solicitud, '%%Y-%%m') = %s",
                $mes
            )) ?: 0;
            $tendencia['solicitudes'][] = (int) $count_sol;

            $count_ayudas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_respuestas WHERE estado = 'aceptada' AND DATE_FORMAT(fecha_respuesta, '%%Y-%%m') = %s",
                $mes
            )) ?: 0;
            $tendencia['ayudas'][] = (int) $count_ayudas;
        }

        // Si no hay datos, usar ejemplo
        if (array_sum($tendencia['solicitudes']) === 0) {
            $tendencia = [
                'labels' => ['Sep', 'Oct', 'Nov', 'Dic', 'Ene', 'Feb'],
                'solicitudes' => [12, 19, 15, 22, 18, 25],
                'ayudas' => [10, 15, 12, 18, 14, 20],
            ];
        }

        // Solicitudes urgentes
        $urgentes = $wpdb->get_results(
            "SELECT s.*, u.display_name as solicitante_nombre
             FROM $tabla_solicitudes s
             LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
             WHERE s.estado = 'pendiente' AND s.urgencia = 'alta'
             ORDER BY s.fecha_solicitud DESC
             LIMIT 5"
        ) ?: [];

        $urgentes_data = [];
        foreach ($urgentes as $u) {
            $urgentes_data[] = [
                'id' => $u->id,
                'titulo' => $u->titulo,
                'categoria' => $u->categoria,
                'solicitante' => $u->solicitante_nombre ?: __('Anónimo', 'flavor-chat-ia'),
                'fecha' => human_time_diff(strtotime($u->fecha_solicitud), current_time('timestamp')) . ' ' . __('atrás', 'flavor-chat-ia'),
            ];
        }

        // Voluntarios destacados
        $destacados = $wpdb->get_results(
            "SELECT o.*, u.display_name as nombre,
                    (SELECT AVG(puntuacion) FROM $tabla_valoraciones WHERE valorado_id = o.usuario_id) as valoracion,
                    (SELECT COUNT(*) FROM $tabla_respuestas WHERE ayudante_id = o.usuario_id AND estado = 'aceptada') as ayudas
             FROM $tabla_ofertas o
             LEFT JOIN {$wpdb->users} u ON o.usuario_id = u.ID
             WHERE o.activa = 1
             ORDER BY ayudas DESC
             LIMIT 5"
        ) ?: [];

        $destacados_data = [];
        foreach ($destacados as $d) {
            $destacados_data[] = [
                'id' => $d->id,
                'nombre' => $d->nombre ?: __('Voluntario', 'flavor-chat-ia'),
                'categoria' => $d->categoria,
                'valoracion' => $d->valoracion ? round($d->valoracion, 1) : null,
                'ayudas' => (int) $d->ayudas,
            ];
        }

        // Actividad reciente
        $actividad = $wpdb->get_results(
            "SELECT 'solicitud' as tipo, s.titulo, s.fecha_solicitud as fecha, u.display_name as usuario
             FROM $tabla_solicitudes s
             LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
             ORDER BY s.fecha_solicitud DESC
             LIMIT 10"
        ) ?: [];

        $actividad_data = [];
        foreach ($actividad as $a) {
            $actividad_data[] = [
                'tipo' => $a->tipo,
                'titulo' => $a->titulo,
                'usuario' => $a->usuario ?: __('Usuario', 'flavor-chat-ia'),
                'fecha' => human_time_diff(strtotime($a->fecha), current_time('timestamp')) . ' ' . __('atrás', 'flavor-chat-ia'),
            ];
        }

        wp_send_json_success([
            'kpis' => [
                'solicitudes_activas' => (int) $solicitudes_activas,
                'voluntarios_activos' => (int) $voluntarios_activos,
                'ayudas_completadas' => (int) $ayudas_completadas,
                'horas_voluntariado' => (int) $horas_voluntariado,
            ],
            'categorias' => $categorias,
            'tendencia' => $tendencia,
            'urgentes' => $urgentes_data,
            'destacados' => $destacados_data,
            'actividad' => $actividad_data,
        ]);
    }

    /**
     * AJAX: Listar voluntarios (ofertas de ayuda)
     */
    public function ajax_listar_voluntarios() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_ofertas = $wpdb->prefix . 'flavor_ayuda_ofertas';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_ayuda_valoraciones';
        $tabla_respuestas = $wpdb->prefix . 'flavor_ayuda_respuestas';

        // Filtros
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $disponibilidad = isset($_POST['disponibilidad']) ? sanitize_text_field($_POST['disponibilidad']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';

        $where = ['1=1'];
        $prepare_values = [];

        if (!empty($search)) {
            $where[] = "(u.display_name LIKE %s OR o.habilidades LIKE %s)";
            $prepare_values[] = '%' . $wpdb->esc_like($search) . '%';
            $prepare_values[] = '%' . $wpdb->esc_like($search) . '%';
        }

        if (!empty($categoria)) {
            $where[] = "o.categoria = %s";
            $prepare_values[] = $categoria;
        }

        // Estado basado en disponibilidad
        if ($disponibilidad === 'disponible') {
            $where[] = "o.activa = 1";
        } elseif ($disponibilidad === 'inactivo') {
            $where[] = "o.activa = 0";
        }

        $sql = "SELECT o.*, u.display_name as nombre, u.user_email as email,
                       (SELECT AVG(puntuacion) FROM $tabla_valoraciones WHERE valorado_id = o.usuario_id) as valoracion,
                       (SELECT COUNT(*) FROM $tabla_respuestas WHERE ayudante_id = o.usuario_id AND estado = 'aceptada') as ayudas_completadas,
                       (SELECT COUNT(*) FROM $tabla_respuestas WHERE ayudante_id = o.usuario_id AND estado = 'pendiente') as ayudas_activas
                FROM $tabla_ofertas o
                LEFT JOIN {$wpdb->users} u ON o.usuario_id = u.ID
                WHERE " . implode(' AND ', $where) . "
                ORDER BY o.fecha_creacion DESC";

        if (!empty($prepare_values)) {
            $voluntarios = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $voluntarios = $wpdb->get_results($sql);
        }

        // Formatear datos
        $resultado = [];
        foreach ($voluntarios as $vol) {
            $nombre = $vol->nombre ?: __('Usuario', 'flavor-chat-ia');
            $iniciales = '';
            $palabras = explode(' ', $nombre);
            foreach (array_slice($palabras, 0, 2) as $palabra) {
                $iniciales .= mb_substr($palabra, 0, 1);
            }

            $estado = $vol->activa ? 'disponible' : 'inactivo';

            $resultado[] = [
                'id' => $vol->id,
                'usuario_id' => $vol->usuario_id,
                'nombre' => $nombre,
                'iniciales' => strtoupper($iniciales),
                'email' => $vol->email,
                'categorias' => $vol->categoria ? json_encode([$vol->categoria]) : '[]',
                'habilidades' => $vol->habilidades,
                'estado' => $estado,
                'valoracion' => $vol->valoracion ? round($vol->valoracion, 1) : null,
                'ayudas_completadas' => (int) $vol->ayudas_completadas,
                'ayudas_activas' => (int) $vol->ayudas_activas,
            ];
        }

        wp_send_json_success($resultado);
    }

    /**
     * AJAX: Listar usuarios disponibles para ser voluntarios
     */
    public function ajax_listar_usuarios() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $usuarios = get_users([
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => 100,
        ]);

        $resultado = [];
        foreach ($usuarios as $usuario) {
            $resultado[] = [
                'id' => $usuario->ID,
                'nombre' => $usuario->display_name ?: $usuario->user_login,
                'email' => $usuario->user_email,
            ];
        }

        wp_send_json_success($resultado);
    }

    /**
     * AJAX: Guardar voluntario (crear/actualizar oferta)
     */
    public function ajax_guardar_voluntario() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_ofertas = $wpdb->prefix . 'flavor_ayuda_ofertas';

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $usuario_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
        $categorias = isset($_POST['categorias']) ? (array) $_POST['categorias'] : [];
        $habilidades = isset($_POST['habilidades']) ? sanitize_textarea_field($_POST['habilidades']) : '';
        $dias_disponibles = isset($_POST['dias_disponibles']) ? (array) $_POST['dias_disponibles'] : [];
        $max_ayudas = isset($_POST['max_ayudas_simultaneas']) ? intval($_POST['max_ayudas_simultaneas']) : 3;
        $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : 'disponible';

        if (empty($usuario_id)) {
            wp_send_json_error(['message' => __('Usuario requerido', 'flavor-chat-ia')]);
        }

        $categoria = !empty($categorias) ? sanitize_text_field($categorias[0]) : '';
        $disponibilidad = json_encode([
            'dias' => array_map('intval', $dias_disponibles),
            'max_ayudas' => $max_ayudas,
        ]);

        $datos = [
            'usuario_id' => $usuario_id,
            'categoria' => $categoria,
            'titulo' => __('Oferta de ayuda', 'flavor-chat-ia'),
            'descripcion' => $habilidades,
            'habilidades' => $habilidades,
            'disponibilidad' => $disponibilidad,
            'activa' => $estado === 'disponible' ? 1 : 0,
        ];

        if ($id > 0) {
            // Actualizar
            $wpdb->update($tabla_ofertas, $datos, ['id' => $id]);
            $oferta_id = $id;
        } else {
            // Crear
            $wpdb->insert($tabla_ofertas, $datos);
            $oferta_id = $wpdb->insert_id;
        }

        if ($oferta_id) {
            wp_send_json_success([
                'id' => $oferta_id,
                'message' => __('Voluntario guardado correctamente', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al guardar', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Listar solicitudes de ayuda
     */
    public function ajax_listar_solicitudes() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        // Filtros
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';

        $where = ['1=1'];
        $prepare_values = [];

        if (!empty($search)) {
            $where[] = "(s.titulo LIKE %s OR s.descripcion LIKE %s)";
            $prepare_values[] = '%' . $wpdb->esc_like($search) . '%';
            $prepare_values[] = '%' . $wpdb->esc_like($search) . '%';
        }

        if (!empty($estado)) {
            $where[] = "s.estado = %s";
            $prepare_values[] = $estado;
        }

        if (!empty($categoria)) {
            $where[] = "s.categoria = %s";
            $prepare_values[] = $categoria;
        }

        $sql = "SELECT s.*, u.display_name as solicitante_nombre
                FROM $tabla_solicitudes s
                LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
                WHERE " . implode(' AND ', $where) . "
                ORDER BY s.fecha_solicitud DESC
                LIMIT 100";

        if (!empty($prepare_values)) {
            $solicitudes = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));
        } else {
            $solicitudes = $wpdb->get_results($sql);
        }

        $resultado = [];
        foreach ($solicitudes as $sol) {
            $resultado[] = [
                'id' => $sol->id,
                'titulo' => $sol->titulo,
                'descripcion' => $sol->descripcion,
                'categoria' => $sol->categoria,
                'estado' => $sol->estado,
                'urgencia' => $sol->urgencia ?? 'normal',
                'solicitante_id' => $sol->usuario_id,
                'solicitante_nombre' => $sol->solicitante_nombre ?: __('Anónimo', 'flavor-chat-ia'),
                'fecha_solicitud' => $sol->fecha_solicitud,
                'fecha_necesaria' => $sol->fecha_necesaria ?? '',
                'fecha_formateada' => human_time_diff(strtotime($sol->fecha_solicitud), current_time('timestamp')) . ' ' . __('atrás', 'flavor-chat-ia'),
            ];
        }

        wp_send_json_success($resultado);
    }

    /**
     * AJAX: Guardar solicitud de ayuda
     */
    public function ajax_guardar_solicitud() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
        $descripcion = isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $fecha_necesaria = isset($_POST['fecha_necesaria']) ? sanitize_text_field($_POST['fecha_necesaria']) : '';
        $solicitante_id = isset($_POST['solicitante_id']) ? intval($_POST['solicitante_id']) : 0;
        $urgente = isset($_POST['urgente']) && $_POST['urgente'] === '1';
        $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : 'pendiente';
        $voluntario_id = isset($_POST['voluntario_id']) ? intval($_POST['voluntario_id']) : 0;

        if (empty($titulo) || empty($descripcion) || empty($categoria)) {
            wp_send_json_error(['message' => __('Campos requeridos incompletos', 'flavor-chat-ia')]);
        }

        $datos = [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'categoria' => $categoria,
            'fecha_necesaria' => $fecha_necesaria ?: null,
            'usuario_id' => $solicitante_id,
            'urgencia' => $urgente ? 'alta' : 'normal',
            'estado' => $estado,
        ];

        if ($id > 0) {
            // Actualizar
            $wpdb->update($tabla_solicitudes, $datos, ['id' => $id]);
            $solicitud_id = $id;
        } else {
            // Crear
            $datos['fecha_solicitud'] = current_time('mysql');
            $wpdb->insert($tabla_solicitudes, $datos);
            $solicitud_id = $wpdb->insert_id;
        }

        if ($solicitud_id) {
            wp_send_json_success([
                'id' => $solicitud_id,
                'message' => __('Solicitud guardada correctamente', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al guardar', 'flavor-chat-ia')]);
        }
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'ayuda-vecinal',
            'title'    => __('Ayuda Vecinal', 'flavor-chat-ia'),
            'subtitle' => __('Red de ayuda mutua entre vecinos', 'flavor-chat-ia'),
            'icon'     => '🤝',
            'color'    => 'primary', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_ayuda_solicitudes',
                'primary_key' => 'id',
            ],

            'fields' => [
                'titulo'       => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'required' => true],
                'descripcion'  => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'required' => true],
                'categoria'    => ['type' => 'select', 'label' => __('Categoría', 'flavor-chat-ia'), 'required' => true],
                'urgencia'     => ['type' => 'select', 'label' => __('Urgencia', 'flavor-chat-ia'), 'options' => ['normal', 'alta', 'critica']],
                'fecha_necesaria' => ['type' => 'date', 'label' => __('Fecha necesaria', 'flavor-chat-ia')],
                'ubicacion'    => ['type' => 'text', 'label' => __('Ubicación', 'flavor-chat-ia')],
            ],

            'estados' => [
                'pendiente'    => ['label' => __('Pendiente', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '⏳'],
                'asignada'     => ['label' => __('Asignada', 'flavor-chat-ia'), 'color' => 'blue', 'icon' => '👤'],
                'en_progreso'  => ['label' => __('En progreso', 'flavor-chat-ia'), 'color' => 'indigo', 'icon' => '🔄'],
                'completada'   => ['label' => __('Completada', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '✅'],
                'cancelada'    => ['label' => __('Cancelada', 'flavor-chat-ia'), 'color' => 'red', 'icon' => '❌'],
            ],

            'stats' => [
                'solicitudes_activas' => ['label' => __('Solicitudes activas', 'flavor-chat-ia'), 'icon' => '📋', 'color' => 'rose'],
                'voluntarios'         => ['label' => __('Voluntarios', 'flavor-chat-ia'), 'icon' => '🙋', 'color' => 'green'],
                'ayudas_completadas'  => ['label' => __('Ayudas completadas', 'flavor-chat-ia'), 'icon' => '✅', 'color' => 'blue'],
                'horas_donadas'       => ['label' => __('Horas donadas', 'flavor-chat-ia'), 'icon' => '⏱️', 'color' => 'purple'],
            ],

            'card' => [
                'template'     => 'solicitud-card',
                'title_field'  => 'titulo',
                'subtitle_field' => 'categoria',
                'meta_fields'  => ['urgencia', 'fecha_necesaria', 'estado'],
                'show_author'  => true,
                'show_estado'  => true,
            ],

            'tabs' => [
                'solicitudes' => [
                    'label'   => __('Solicitudes', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-sos',
                    'content' => 'shortcode:ayuda_vecinal_solicitudes',
                    'public'  => true,
                ],
                'ofrecer' => [
                    'label'   => __('Ofrecer Ayuda', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-heart',
                    'content' => 'shortcode:ayuda_vecinal_ofrecer',
                    'public'  => true,
                ],
                'solicitar' => [
                    'label'      => __('Pedir Ayuda', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-plus-alt',
                    'content'    => 'shortcode:ayuda_vecinal_solicitar',
                    'requires_login' => true,
                ],
                'mis-ayudas' => [
                    'label'      => __('Mis Ayudas', 'flavor-chat-ia'),
                    'icon'       => 'dashicons-admin-users',
                    'content'    => 'shortcode:ayuda_vecinal_mis_ayudas',
                    'requires_login' => true,
                ],
                'mapa' => [
                    'label'   => __('Mapa', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-location',
                    'content' => 'shortcode:ayuda_vecinal_mapa',
                    'public'  => true,
                ],
                'estadisticas' => [
                    'label'   => __('Estadísticas', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-chart-bar',
                    'content' => 'shortcode:ayuda_vecinal_estadisticas',
                    'public'  => true,
                ],
            ],

            'archive' => [
                'columns'    => 2,
                'per_page'   => 12,
                'order_by'   => 'fecha_solicitud',
                'order'      => 'DESC',
                'filterable' => ['categoria', 'urgencia', 'estado'],
            ],

            'dashboard' => [
                'widgets' => ['stats', 'solicitudes_urgentes', 'mis_ayudas', 'ranking_voluntarios'],
                'actions' => [
                    'pedir'     => ['label' => __('Pedir ayuda', 'flavor-chat-ia'), 'icon' => '🆘', 'color' => 'rose'],
                    'ofrecer'   => ['label' => __('Ofrecer ayuda', 'flavor-chat-ia'), 'icon' => '🤝', 'color' => 'green'],
                ],
            ],

            'features' => [
                'matching'       => true,
                'valoraciones'   => true,
                'chat'           => true,
                'notificaciones' => true,
                'gamificacion'   => true,
            ],
        ];
    }

    // =========================================================================
    // SHORTCODES - Métodos de renderizado frontend
    // =========================================================================

    /**
     * Encola assets frontend solo cuando se usan shortcodes
     */
    public function maybe_enqueue_frontend_assets() {
        global $post;

        if (!is_a($post, 'WP_Post')) {
            return;
        }

        $shortcodes_modulo = [
            'ayuda_vecinal_solicitudes',
            'ayuda_vecinal_ofrecer',
            'ayuda_vecinal_solicitar',
            'ayuda_vecinal_mis_ayudas',
            'ayuda_vecinal_mapa',
            'ayuda_vecinal_estadisticas'
        ];

        $contenido_tiene_shortcode = false;
        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                $contenido_tiene_shortcode = true;
                break;
            }
        }

        if ($contenido_tiene_shortcode) {
            $this->enqueue_frontend_assets();
        }
    }

    /**
     * Encola los assets CSS y JS para el frontend
     */
    private function enqueue_frontend_assets() {
        $assets_url = FLAVOR_CHAT_IA_URL . 'includes/modules/ayuda-vecinal/assets/';
        $version = FLAVOR_CHAT_IA_VERSION;

        // CSS frontend
        wp_enqueue_style(
            'ayuda-vecinal-frontend',
            $assets_url . 'css/ayuda-vecinal-frontend.css',
            [],
            $version
        );

        // JS frontend
        wp_enqueue_script(
            'ayuda-vecinal-frontend',
            $assets_url . 'js/ayuda-vecinal-frontend.js',
            ['jquery'],
            $version,
            true
        );

        // Localizar script con datos necesarios
        wp_localize_script('ayuda-vecinal-frontend', 'ayudaVecinalData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ayuda_vecinal_frontend'),
            'user_logged_in' => is_user_logged_in(),
            'user_id' => get_current_user_id(),
            'categorias' => $this->get_categorias_ayuda(),
            'strings' => [
                'error_general' => __('Ha ocurrido un error. Inténtalo de nuevo.', 'flavor-chat-ia'),
                'confirmar_envio' => __('¿Estás seguro de que deseas enviar esta solicitud?', 'flavor-chat-ia'),
                'solicitud_enviada' => __('Tu solicitud ha sido enviada correctamente.', 'flavor-chat-ia'),
                'oferta_enviada' => __('Tu oferta de ayuda ha sido registrada.', 'flavor-chat-ia'),
                'login_requerido' => __('Debes iniciar sesión para realizar esta acción.', 'flavor-chat-ia'),
            ]
        ]);

        // Leaflet para mapas
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
    }

    /**
     * Obtiene las categorías de ayuda configuradas
     */
    private function get_categorias_ayuda() {
        $settings = $this->get_settings();
        $categorias = $settings['categorias_ayuda'] ?? [
            'compras' => __('Compras', 'flavor-chat-ia'),
            'cuidado_mayores' => __('Cuidado de mayores', 'flavor-chat-ia'),
            'cuidado_ninos' => __('Cuidado de niños', 'flavor-chat-ia'),
            'mascotas' => __('Mascotas', 'flavor-chat-ia'),
            'transporte' => __('Transporte', 'flavor-chat-ia'),
            'tecnologia' => __('Ayuda tecnológica', 'flavor-chat-ia'),
            'tramites' => __('Trámites', 'flavor-chat-ia'),
            'reparaciones' => __('Reparaciones', 'flavor-chat-ia'),
            'compania' => __('Compañía', 'flavor-chat-ia'),
            'otro' => __('Otro', 'flavor-chat-ia'),
        ];

        // Convertir array simple a asociativo si es necesario
        if (isset($categorias[0])) {
            $categorias_asociativas = [];
            foreach ($categorias as $categoria) {
                $categorias_asociativas[$categoria] = ucfirst(str_replace('_', ' ', $categoria));
            }
            return $categorias_asociativas;
        }

        return $categorias;
    }

    /**
     * Shortcode: Lista de solicitudes de ayuda activas
     * [ayuda_vecinal_solicitudes categoria="compras" urgencia="alta" limite="10"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function render_shortcode_solicitudes($atts) {
        global $wpdb;

        $atributos = shortcode_atts([
            'categoria' => '',
            'urgencia' => '',
            'limite' => 10,
            'mostrar_filtros' => 'true',
            'columnas' => 2,
        ], $atts);

        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';
        $limite_solicitudes = absint($atributos['limite']);
        $numero_columnas = absint($atributos['columnas']);
        $mostrar_filtros = $atributos['mostrar_filtros'] === 'true';

        // Construir query
        $condiciones_where = ['estado = %s'];
        $valores_preparados = ['abierta'];

        if (!empty($atributos['categoria'])) {
            $condiciones_where[] = 'categoria = %s';
            $valores_preparados[] = sanitize_text_field($atributos['categoria']);
        }

        if (!empty($atributos['urgencia'])) {
            $condiciones_where[] = 'urgencia = %s';
            $valores_preparados[] = sanitize_text_field($atributos['urgencia']);
        }

        $consulta_sql = $wpdb->prepare(
            "SELECT s.*, u.display_name as nombre_solicitante
             FROM {$tabla_solicitudes} s
             LEFT JOIN {$wpdb->users} u ON s.solicitante_id = u.ID
             WHERE " . implode(' AND ', $condiciones_where) . "
             ORDER BY
                CASE s.urgencia
                    WHEN 'urgente' THEN 1
                    WHEN 'alta' THEN 2
                    WHEN 'media' THEN 3
                    WHEN 'baja' THEN 4
                END,
                s.fecha_solicitud DESC
             LIMIT %d",
            array_merge($valores_preparados, [$limite_solicitudes])
        );

        $solicitudes = $wpdb->get_results($consulta_sql);
        $categorias = $this->get_categorias_ayuda();

        ob_start();
        ?>
        <div class="ayuda-vecinal-solicitudes-container">
            <?php if ($mostrar_filtros): ?>
            <div class="ayuda-vecinal-filtros">
                <form class="filtros-form" method="get">
                    <div class="filtro-grupo">
                        <label for="filtro-categoria"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?></label>
                        <select id="filtro-categoria" name="categoria" class="filtro-select">
                            <option value=""><?php esc_html_e('Todas', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($categorias as $clave_categoria => $etiqueta_categoria): ?>
                                <option value="<?php echo esc_attr($clave_categoria); ?>" <?php selected($atributos['categoria'], $clave_categoria); ?>>
                                    <?php echo esc_html($etiqueta_categoria); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filtro-grupo">
                        <label for="filtro-urgencia"><?php esc_html_e('Urgencia', 'flavor-chat-ia'); ?></label>
                        <select id="filtro-urgencia" name="urgencia" class="filtro-select">
                            <option value=""><?php esc_html_e('Todas', 'flavor-chat-ia'); ?></option>
                            <option value="urgente" <?php selected($atributos['urgencia'], 'urgente'); ?>><?php esc_html_e('Urgente', 'flavor-chat-ia'); ?></option>
                            <option value="alta" <?php selected($atributos['urgencia'], 'alta'); ?>><?php esc_html_e('Alta', 'flavor-chat-ia'); ?></option>
                            <option value="media" <?php selected($atributos['urgencia'], 'media'); ?>><?php esc_html_e('Media', 'flavor-chat-ia'); ?></option>
                            <option value="baja" <?php selected($atributos['urgencia'], 'baja'); ?>><?php esc_html_e('Baja', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                    <button type="submit" class="btn-filtrar"><?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?></button>
                </form>
            </div>
            <?php endif; ?>

            <?php if (empty($solicitudes)): ?>
                <div class="ayuda-vecinal-sin-resultados">
                    <span class="icono">🤝</span>
                    <p><?php esc_html_e('No hay solicitudes de ayuda activas en este momento.', 'flavor-chat-ia'); ?></p>
                    <?php if (is_user_logged_in()): ?>
                        <p><?php esc_html_e('¿Necesitas ayuda? ¡Crea una solicitud!', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="ayuda-vecinal-grid columnas-<?php echo esc_attr($numero_columnas); ?>">
                    <?php foreach ($solicitudes as $solicitud): ?>
                        <?php echo $this->render_tarjeta_solicitud($solicitud, $categorias); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza una tarjeta de solicitud individual
     *
     * @param object $solicitud Datos de la solicitud
     * @param array $categorias Lista de categorías
     * @return string HTML de la tarjeta
     */
    private function render_tarjeta_solicitud($solicitud, $categorias) {
        $clase_urgencia = 'urgencia-' . esc_attr($solicitud->urgencia);
        $etiqueta_categoria = $categorias[$solicitud->categoria] ?? ucfirst($solicitud->categoria);
        $tiempo_transcurrido = human_time_diff(strtotime($solicitud->fecha_solicitud), current_time('timestamp'));

        $iconos_urgencia = [
            'urgente' => '🚨',
            'alta' => '⚠️',
            'media' => '📌',
            'baja' => '📝'
        ];
        $icono_urgencia = $iconos_urgencia[$solicitud->urgencia] ?? '📝';

        ob_start();
        ?>
        <div class="ayuda-vecinal-tarjeta <?php echo $clase_urgencia; ?>" data-solicitud-id="<?php echo esc_attr($solicitud->id); ?>">
            <div class="tarjeta-header">
                <span class="categoria-badge"><?php echo esc_html($etiqueta_categoria); ?></span>
                <span class="urgencia-badge <?php echo $clase_urgencia; ?>">
                    <?php echo $icono_urgencia; ?> <?php echo esc_html(ucfirst($solicitud->urgencia)); ?>
                </span>
            </div>
            <h3 class="tarjeta-titulo"><?php echo esc_html($solicitud->titulo); ?></h3>
            <p class="tarjeta-descripcion"><?php echo esc_html(wp_trim_words($solicitud->descripcion, 25)); ?></p>
            <div class="tarjeta-meta">
                <span class="solicitante">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php echo esc_html($solicitud->nombre_solicitante); ?>
                </span>
                <span class="tiempo">
                    <span class="dashicons dashicons-clock"></span>
                    <?php printf(esc_html__('Hace %s', 'flavor-chat-ia'), $tiempo_transcurrido); ?>
                </span>
                <?php if (!empty($solicitud->ubicacion)): ?>
                <span class="ubicacion">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo esc_html(wp_trim_words($solicitud->ubicacion, 3)); ?>
                </span>
                <?php endif; ?>
            </div>
            <?php if (is_user_logged_in() && get_current_user_id() !== (int)$solicitud->solicitante_id): ?>
            <div class="tarjeta-acciones">
                <button type="button" class="btn-ofrecer-ayuda" data-solicitud-id="<?php echo esc_attr($solicitud->id); ?>">
                    🤝 <?php esc_html_e('Ofrecer ayuda', 'flavor-chat-ia'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario para ofrecer ayuda
     * [ayuda_vecinal_ofrecer]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function render_shortcode_ofrecer($atts) {
        if (!is_user_logged_in()) {
            return $this->render_mensaje_login(__('Inicia sesión para ofrecer tu ayuda a la comunidad.', 'flavor-chat-ia'));
        }

        $usuario_actual = wp_get_current_user();
        $categorias = $this->get_categorias_ayuda();

        // Verificar si ya tiene una oferta activa
        global $wpdb;
        $tabla_ofertas = $wpdb->prefix . 'flavor_ayuda_ofertas';
        $oferta_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_ofertas} WHERE usuario_id = %d AND activa = 1",
            get_current_user_id()
        ));

        ob_start();
        ?>
        <div class="ayuda-vecinal-form-container ayuda-vecinal-ofrecer">
            <div class="form-header">
                <h2>🤝 <?php esc_html_e('Ofrecer mi ayuda', 'flavor-chat-ia'); ?></h2>
                <p><?php esc_html_e('Comparte tus habilidades y disponibilidad para ayudar a tus vecinos.', 'flavor-chat-ia'); ?></p>
            </div>

            <?php if ($oferta_existente): ?>
            <div class="aviso-oferta-existente">
                <p><?php esc_html_e('Ya tienes una oferta de ayuda activa. Puedes actualizarla a continuación.', 'flavor-chat-ia'); ?></p>
            </div>
            <?php endif; ?>

            <form id="form-ofrecer-ayuda" class="ayuda-vecinal-form" data-oferta-id="<?php echo $oferta_existente ? esc_attr($oferta_existente->id) : ''; ?>">
                <?php wp_nonce_field('ayuda_vecinal_frontend', 'ayuda_vecinal_nonce'); ?>

                <div class="form-grupo">
                    <label for="oferta-titulo"><?php esc_html_e('Título de tu oferta', 'flavor-chat-ia'); ?> <span class="requerido">*</span></label>
                    <input type="text" id="oferta-titulo" name="titulo" required maxlength="255"
                           value="<?php echo $oferta_existente ? esc_attr($oferta_existente->titulo) : ''; ?>"
                           placeholder="<?php esc_attr_e('Ej: Ayudo con compras y recados en el barrio', 'flavor-chat-ia'); ?>">
                </div>

                <div class="form-grupo">
                    <label for="oferta-categoria"><?php esc_html_e('Categoría principal', 'flavor-chat-ia'); ?> <span class="requerido">*</span></label>
                    <select id="oferta-categoria" name="categoria" required>
                        <option value=""><?php esc_html_e('Selecciona una categoría', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($categorias as $clave_categoria => $etiqueta_categoria): ?>
                            <option value="<?php echo esc_attr($clave_categoria); ?>" <?php selected($oferta_existente->categoria ?? '', $clave_categoria); ?>>
                                <?php echo esc_html($etiqueta_categoria); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grupo">
                    <label for="oferta-descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?> <span class="requerido">*</span></label>
                    <textarea id="oferta-descripcion" name="descripcion" required rows="4"
                              placeholder="<?php esc_attr_e('Describe qué tipo de ayuda puedes ofrecer, tu experiencia, etc.', 'flavor-chat-ia'); ?>"><?php echo $oferta_existente ? esc_textarea($oferta_existente->descripcion) : ''; ?></textarea>
                </div>

                <div class="form-grupo">
                    <label for="oferta-habilidades"><?php esc_html_e('Habilidades especiales', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="oferta-habilidades" name="habilidades"
                           value="<?php echo $oferta_existente ? esc_attr($oferta_existente->habilidades) : ''; ?>"
                           placeholder="<?php esc_attr_e('Ej: Primeros auxilios, idiomas, bricolaje...', 'flavor-chat-ia'); ?>">
                    <span class="form-ayuda"><?php esc_html_e('Separa las habilidades con comas', 'flavor-chat-ia'); ?></span>
                </div>

                <div class="form-grupo-flex">
                    <div class="form-grupo">
                        <label for="oferta-radio"><?php esc_html_e('Radio de acción (km)', 'flavor-chat-ia'); ?></label>
                        <input type="number" id="oferta-radio" name="radio_km" min="1" max="50"
                               value="<?php echo $oferta_existente ? esc_attr($oferta_existente->radio_km) : '5'; ?>">
                    </div>
                    <div class="form-grupo">
                        <label class="checkbox-label">
                            <input type="checkbox" name="tiene_vehiculo" value="1" <?php checked($oferta_existente->tiene_vehiculo ?? false); ?>>
                            <span><?php esc_html_e('Tengo vehículo disponible', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                </div>

                <div class="form-grupo">
                    <label><?php esc_html_e('Disponibilidad semanal', 'flavor-chat-ia'); ?></label>
                    <div class="disponibilidad-grid">
                        <?php
                        $dias_semana = [
                            'lunes' => __('Lunes', 'flavor-chat-ia'),
                            'martes' => __('Martes', 'flavor-chat-ia'),
                            'miercoles' => __('Miércoles', 'flavor-chat-ia'),
                            'jueves' => __('Jueves', 'flavor-chat-ia'),
                            'viernes' => __('Viernes', 'flavor-chat-ia'),
                            'sabado' => __('Sábado', 'flavor-chat-ia'),
                            'domingo' => __('Domingo', 'flavor-chat-ia'),
                        ];
                        $disponibilidad_guardada = $oferta_existente ? json_decode($oferta_existente->disponibilidad, true) : [];
                        foreach ($dias_semana as $clave_dia => $nombre_dia):
                        ?>
                        <label class="dia-checkbox">
                            <input type="checkbox" name="disponibilidad[<?php echo esc_attr($clave_dia); ?>]" value="1"
                                   <?php checked(!empty($disponibilidad_guardada[$clave_dia])); ?>>
                            <span><?php echo esc_html($nombre_dia); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-acciones">
                    <button type="submit" class="btn-principal">
                        <?php echo $oferta_existente ? esc_html__('Actualizar oferta', 'flavor-chat-ia') : esc_html__('Publicar oferta de ayuda', 'flavor-chat-ia'); ?>
                    </button>
                    <?php if ($oferta_existente): ?>
                    <button type="button" class="btn-secundario btn-desactivar-oferta" data-oferta-id="<?php echo esc_attr($oferta_existente->id); ?>">
                        <?php esc_html_e('Desactivar oferta', 'flavor-chat-ia'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario para solicitar ayuda
     * [ayuda_vecinal_solicitar]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function render_shortcode_solicitar($atts) {
        if (!is_user_logged_in()) {
            return $this->render_mensaje_login(__('Inicia sesión para solicitar ayuda de tus vecinos.', 'flavor-chat-ia'));
        }

        $categorias = $this->get_categorias_ayuda();

        ob_start();
        ?>
        <div class="ayuda-vecinal-form-container ayuda-vecinal-solicitar">
            <div class="form-header">
                <h2>🆘 <?php esc_html_e('Solicitar ayuda', 'flavor-chat-ia'); ?></h2>
                <p><?php esc_html_e('Describe qué necesitas y la comunidad te ayudará.', 'flavor-chat-ia'); ?></p>
            </div>

            <form id="form-solicitar-ayuda" class="ayuda-vecinal-form">
                <?php wp_nonce_field('ayuda_vecinal_frontend', 'ayuda_vecinal_nonce'); ?>

                <div class="form-grupo">
                    <label for="solicitud-titulo"><?php esc_html_e('¿Qué necesitas?', 'flavor-chat-ia'); ?> <span class="requerido">*</span></label>
                    <input type="text" id="solicitud-titulo" name="titulo" required maxlength="255"
                           placeholder="<?php esc_attr_e('Ej: Necesito ayuda para hacer la compra semanal', 'flavor-chat-ia'); ?>">
                </div>

                <div class="form-grupo-flex">
                    <div class="form-grupo">
                        <label for="solicitud-categoria"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?> <span class="requerido">*</span></label>
                        <select id="solicitud-categoria" name="categoria" required>
                            <option value=""><?php esc_html_e('Selecciona una categoría', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($categorias as $clave_categoria => $etiqueta_categoria): ?>
                                <option value="<?php echo esc_attr($clave_categoria); ?>">
                                    <?php echo esc_html($etiqueta_categoria); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-grupo">
                        <label for="solicitud-urgencia"><?php esc_html_e('Urgencia', 'flavor-chat-ia'); ?> <span class="requerido">*</span></label>
                        <select id="solicitud-urgencia" name="urgencia" required>
                            <option value="baja"><?php esc_html_e('Baja - Sin prisa', 'flavor-chat-ia'); ?></option>
                            <option value="media" selected><?php esc_html_e('Media - Normal', 'flavor-chat-ia'); ?></option>
                            <option value="alta"><?php esc_html_e('Alta - Pronto', 'flavor-chat-ia'); ?></option>
                            <option value="urgente"><?php esc_html_e('Urgente - Lo antes posible', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="form-grupo">
                    <label for="solicitud-descripcion"><?php esc_html_e('Descripción detallada', 'flavor-chat-ia'); ?> <span class="requerido">*</span></label>
                    <textarea id="solicitud-descripcion" name="descripcion" required rows="4"
                              placeholder="<?php esc_attr_e('Explica con detalle qué necesitas, cuándo, requisitos especiales...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="form-grupo">
                    <label for="solicitud-ubicacion"><?php esc_html_e('Ubicación', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="solicitud-ubicacion" name="ubicacion"
                           placeholder="<?php esc_attr_e('Barrio, zona o dirección aproximada', 'flavor-chat-ia'); ?>">
                    <input type="hidden" id="solicitud-lat" name="ubicacion_lat">
                    <input type="hidden" id="solicitud-lng" name="ubicacion_lng">
                </div>

                <div class="form-grupo-flex">
                    <div class="form-grupo">
                        <label for="solicitud-fecha"><?php esc_html_e('Fecha necesaria', 'flavor-chat-ia'); ?></label>
                        <input type="datetime-local" id="solicitud-fecha" name="fecha_necesaria">
                    </div>
                    <div class="form-grupo">
                        <label for="solicitud-duracion"><?php esc_html_e('Duración estimada (min)', 'flavor-chat-ia'); ?></label>
                        <input type="number" id="solicitud-duracion" name="duracion_estimada_minutos" min="15" step="15"
                               placeholder="60">
                    </div>
                </div>

                <div class="form-grupo">
                    <label class="checkbox-label">
                        <input type="checkbox" name="necesita_desplazamiento" value="1">
                        <span><?php esc_html_e('Requiere desplazamiento', 'flavor-chat-ia'); ?></span>
                    </label>
                </div>

                <div class="form-grupo">
                    <label for="solicitud-compensacion"><?php esc_html_e('Compensación ofrecida', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="solicitud-compensacion" name="compensacion"
                           placeholder="<?php esc_attr_e('Ej: Invito a café, intercambio de favores, nada (voluntario)...', 'flavor-chat-ia'); ?>">
                </div>

                <div class="form-acciones">
                    <button type="submit" class="btn-principal">
                        <?php esc_html_e('Publicar solicitud de ayuda', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis ayudas (ofrecidas y recibidas)
     * [ayuda_vecinal_mis_ayudas]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function render_shortcode_mis_ayudas($atts) {
        if (!is_user_logged_in()) {
            return $this->render_mensaje_login(__('Inicia sesión para ver tu historial de ayudas.', 'flavor-chat-ia'));
        }

        global $wpdb;
        $id_usuario_actual = get_current_user_id();
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';
        $tabla_respuestas = $wpdb->prefix . 'flavor_ayuda_respuestas';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_ayuda_valoraciones';

        // Solicitudes que he creado
        $mis_solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.display_name as nombre_ayudante,
                    (SELECT COUNT(*) FROM {$tabla_respuestas} r WHERE r.solicitud_id = s.id AND r.estado = 'pendiente') as respuestas_pendientes
             FROM {$tabla_solicitudes} s
             LEFT JOIN {$wpdb->users} u ON s.ayudante_id = u.ID
             WHERE s.solicitante_id = %d
             ORDER BY s.fecha_solicitud DESC
             LIMIT 20",
            $id_usuario_actual
        ));

        // Ayudas que he ofrecido
        $mis_ayudas_ofrecidas = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.display_name as nombre_solicitante, r.estado as estado_respuesta, r.id as respuesta_id
             FROM {$tabla_solicitudes} s
             INNER JOIN {$tabla_respuestas} r ON r.solicitud_id = s.id
             LEFT JOIN {$wpdb->users} u ON s.solicitante_id = u.ID
             WHERE r.ayudante_id = %d
             ORDER BY r.fecha_respuesta DESC
             LIMIT 20",
            $id_usuario_actual
        ));

        // Estadísticas del usuario
        $total_solicitudes_creadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE solicitante_id = %d",
            $id_usuario_actual
        ));

        $total_ayudas_completadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE ayudante_id = %d AND estado = 'completada'",
            $id_usuario_actual
        ));

        $valoracion_media = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(puntuacion) FROM {$tabla_valoraciones} WHERE valorado_id = %d",
            $id_usuario_actual
        ));

        $puntos_solidaridad = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(puntos_solidaridad_otorgados), 0) FROM {$tabla_valoraciones} WHERE valorado_id = %d",
            $id_usuario_actual
        ));

        $categorias = $this->get_categorias_ayuda();

        ob_start();
        ?>
        <div class="ayuda-vecinal-mis-ayudas">
            <div class="mis-ayudas-header">
                <h2><?php esc_html_e('Mi actividad de ayuda vecinal', 'flavor-chat-ia'); ?></h2>
            </div>

            <!-- Resumen estadísticas -->
            <div class="mis-ayudas-stats">
                <div class="stat-card">
                    <span class="stat-numero"><?php echo esc_html($total_solicitudes_creadas); ?></span>
                    <span class="stat-etiqueta"><?php esc_html_e('Solicitudes creadas', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-numero"><?php echo esc_html($total_ayudas_completadas); ?></span>
                    <span class="stat-etiqueta"><?php esc_html_e('Ayudas realizadas', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-numero"><?php echo $valoracion_media ? number_format($valoracion_media, 1) : '-'; ?></span>
                    <span class="stat-etiqueta"><?php esc_html_e('Valoración media', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card destacado">
                    <span class="stat-numero"><?php echo esc_html($puntos_solidaridad); ?></span>
                    <span class="stat-etiqueta"><?php esc_html_e('Puntos solidarios', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <!-- Tabs de navegación -->
            <div class="mis-ayudas-tabs">
                <button class="tab-btn activo" data-tab="solicitudes">
                    <?php esc_html_e('Mis solicitudes', 'flavor-chat-ia'); ?>
                    <span class="contador"><?php echo count($mis_solicitudes); ?></span>
                </button>
                <button class="tab-btn" data-tab="ofrecidas">
                    <?php esc_html_e('Ayudas ofrecidas', 'flavor-chat-ia'); ?>
                    <span class="contador"><?php echo count($mis_ayudas_ofrecidas); ?></span>
                </button>
            </div>

            <!-- Tab: Mis solicitudes -->
            <div class="tab-content activo" id="tab-solicitudes">
                <?php if (empty($mis_solicitudes)): ?>
                    <div class="sin-resultados">
                        <p><?php esc_html_e('No has creado ninguna solicitud de ayuda todavía.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="lista-ayudas">
                        <?php foreach ($mis_solicitudes as $solicitud): ?>
                            <?php echo $this->render_fila_mi_solicitud($solicitud, $categorias); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Ayudas ofrecidas -->
            <div class="tab-content" id="tab-ofrecidas">
                <?php if (empty($mis_ayudas_ofrecidas)): ?>
                    <div class="sin-resultados">
                        <p><?php esc_html_e('No has ofrecido ayuda en ninguna solicitud todavía.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="lista-ayudas">
                        <?php foreach ($mis_ayudas_ofrecidas as $ayuda): ?>
                            <?php echo $this->render_fila_ayuda_ofrecida($ayuda, $categorias); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza una fila de solicitud propia
     */
    private function render_fila_mi_solicitud($solicitud, $categorias) {
        $etiqueta_categoria = $categorias[$solicitud->categoria] ?? ucfirst($solicitud->categoria);
        $clases_estado = [
            'abierta' => 'estado-abierta',
            'asignada' => 'estado-asignada',
            'en_curso' => 'estado-en-curso',
            'completada' => 'estado-completada',
            'cancelada' => 'estado-cancelada',
            'expirada' => 'estado-expirada',
        ];
        $clase_estado = $clases_estado[$solicitud->estado] ?? '';

        ob_start();
        ?>
        <div class="fila-ayuda <?php echo esc_attr($clase_estado); ?>">
            <div class="fila-info">
                <h4><?php echo esc_html($solicitud->titulo); ?></h4>
                <div class="fila-meta">
                    <span class="categoria"><?php echo esc_html($etiqueta_categoria); ?></span>
                    <span class="fecha"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($solicitud->fecha_solicitud))); ?></span>
                    <span class="estado-badge <?php echo esc_attr($clase_estado); ?>"><?php echo esc_html(ucfirst(str_replace('_', ' ', $solicitud->estado))); ?></span>
                </div>
                <?php if ($solicitud->respuestas_pendientes > 0): ?>
                    <div class="aviso-respuestas">
                        <?php printf(esc_html(_n('%d persona quiere ayudarte', '%d personas quieren ayudarte', $solicitud->respuestas_pendientes, 'flavor-chat-ia')), $solicitud->respuestas_pendientes); ?>
                    </div>
                <?php endif; ?>
                <?php if ($solicitud->nombre_ayudante): ?>
                    <div class="ayudante-info">
                        <?php printf(esc_html__('Ayudante: %s', 'flavor-chat-ia'), esc_html($solicitud->nombre_ayudante)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="fila-acciones">
                <?php if ($solicitud->estado === 'abierta'): ?>
                    <button class="btn-ver-respuestas" data-solicitud-id="<?php echo esc_attr($solicitud->id); ?>">
                        <?php esc_html_e('Ver respuestas', 'flavor-chat-ia'); ?>
                    </button>
                    <button class="btn-cancelar-solicitud" data-solicitud-id="<?php echo esc_attr($solicitud->id); ?>">
                        <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                    </button>
                <?php elseif ($solicitud->estado === 'en_curso'): ?>
                    <button class="btn-completar-solicitud" data-solicitud-id="<?php echo esc_attr($solicitud->id); ?>">
                        <?php esc_html_e('Marcar completada', 'flavor-chat-ia'); ?>
                    </button>
                <?php elseif ($solicitud->estado === 'completada'): ?>
                    <button class="btn-valorar" data-solicitud-id="<?php echo esc_attr($solicitud->id); ?>">
                        <?php esc_html_e('Valorar', 'flavor-chat-ia'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza una fila de ayuda ofrecida
     */
    private function render_fila_ayuda_ofrecida($ayuda, $categorias) {
        $etiqueta_categoria = $categorias[$ayuda->categoria] ?? ucfirst($ayuda->categoria);
        $estados_respuesta = [
            'pendiente' => __('Pendiente', 'flavor-chat-ia'),
            'aceptada' => __('Aceptada', 'flavor-chat-ia'),
            'rechazada' => __('Rechazada', 'flavor-chat-ia'),
            'retirada' => __('Retirada', 'flavor-chat-ia'),
        ];
        $estado_texto = $estados_respuesta[$ayuda->estado_respuesta] ?? $ayuda->estado_respuesta;

        ob_start();
        ?>
        <div class="fila-ayuda estado-<?php echo esc_attr($ayuda->estado_respuesta); ?>">
            <div class="fila-info">
                <h4><?php echo esc_html($ayuda->titulo); ?></h4>
                <div class="fila-meta">
                    <span class="categoria"><?php echo esc_html($etiqueta_categoria); ?></span>
                    <span class="solicitante"><?php printf(esc_html__('Para: %s', 'flavor-chat-ia'), esc_html($ayuda->nombre_solicitante)); ?></span>
                    <span class="estado-badge estado-<?php echo esc_attr($ayuda->estado_respuesta); ?>"><?php echo esc_html($estado_texto); ?></span>
                </div>
            </div>
            <div class="fila-acciones">
                <?php if ($ayuda->estado_respuesta === 'pendiente'): ?>
                    <button class="btn-retirar-oferta" data-respuesta-id="<?php echo esc_attr($ayuda->respuesta_id); ?>">
                        <?php esc_html_e('Retirar oferta', 'flavor-chat-ia'); ?>
                    </button>
                <?php elseif ($ayuda->estado_respuesta === 'aceptada' && $ayuda->estado === 'completada'): ?>
                    <button class="btn-valorar" data-solicitud-id="<?php echo esc_attr($ayuda->id); ?>">
                        <?php esc_html_e('Valorar', 'flavor-chat-ia'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mapa de solicitudes cercanas
     * [ayuda_vecinal_mapa altura="400" zoom="14"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function render_shortcode_mapa($atts) {
        $atributos = shortcode_atts([
            'altura' => '400',
            'zoom' => 14,
            'lat_centro' => '',
            'lng_centro' => '',
            'mostrar_controles' => 'true',
        ], $atts);

        $altura_mapa = absint($atributos['altura']);
        $nivel_zoom = absint($atributos['zoom']);
        $mostrar_controles = $atributos['mostrar_controles'] === 'true';

        // Configuración del mapa
        $configuracion_mapa = [
            'zoom' => $nivel_zoom,
            'lat_centro' => $atributos['lat_centro'] ?: 40.4168, // Madrid por defecto
            'lng_centro' => $atributos['lng_centro'] ?: -3.7038,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ayuda_vecinal_mapa'),
        ];

        $categorias = $this->get_categorias_ayuda();

        ob_start();
        ?>
        <div class="ayuda-vecinal-mapa-container">
            <?php if ($mostrar_controles): ?>
            <div class="mapa-controles">
                <div class="control-grupo">
                    <label for="mapa-filtro-categoria"><?php esc_html_e('Categoría', 'flavor-chat-ia'); ?></label>
                    <select id="mapa-filtro-categoria" class="mapa-filtro">
                        <option value=""><?php esc_html_e('Todas', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($categorias as $clave_categoria => $etiqueta_categoria): ?>
                            <option value="<?php echo esc_attr($clave_categoria); ?>"><?php echo esc_html($etiqueta_categoria); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="control-grupo">
                    <label for="mapa-filtro-urgencia"><?php esc_html_e('Urgencia', 'flavor-chat-ia'); ?></label>
                    <select id="mapa-filtro-urgencia" class="mapa-filtro">
                        <option value=""><?php esc_html_e('Todas', 'flavor-chat-ia'); ?></option>
                        <option value="urgente"><?php esc_html_e('Urgente', 'flavor-chat-ia'); ?></option>
                        <option value="alta"><?php esc_html_e('Alta', 'flavor-chat-ia'); ?></option>
                        <option value="media"><?php esc_html_e('Media', 'flavor-chat-ia'); ?></option>
                        <option value="baja"><?php esc_html_e('Baja', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
                <button type="button" id="mapa-btn-mi-ubicacion" class="btn-mi-ubicacion">
                    📍 <?php esc_html_e('Mi ubicación', 'flavor-chat-ia'); ?>
                </button>
            </div>
            <?php endif; ?>

            <div id="ayuda-vecinal-mapa"
                 class="mapa-leaflet"
                 style="height: <?php echo esc_attr($altura_mapa); ?>px;"
                 data-config="<?php echo esc_attr(wp_json_encode($configuracion_mapa)); ?>">
            </div>

            <div class="mapa-leyenda">
                <span class="leyenda-item urgente">🚨 <?php esc_html_e('Urgente', 'flavor-chat-ia'); ?></span>
                <span class="leyenda-item alta">⚠️ <?php esc_html_e('Alta', 'flavor-chat-ia'); ?></span>
                <span class="leyenda-item media">📌 <?php esc_html_e('Media', 'flavor-chat-ia'); ?></span>
                <span class="leyenda-item baja">📝 <?php esc_html_e('Baja', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Estadísticas de ayuda comunitaria
     * [ayuda_vecinal_estadisticas periodo="mes"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function render_shortcode_estadisticas($atts) {
        $atributos = shortcode_atts([
            'periodo' => 'mes',
            'mostrar_ranking' => 'true',
            'limite_ranking' => 10,
        ], $atts);

        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_ayuda_valoraciones';

        // Determinar rango de fechas
        $fecha_inicio = '';
        switch ($atributos['periodo']) {
            case 'semana':
                $fecha_inicio = date('Y-m-d', strtotime('-1 week'));
                $titulo_periodo = __('Esta semana', 'flavor-chat-ia');
                break;
            case 'mes':
                $fecha_inicio = date('Y-m-d', strtotime('-1 month'));
                $titulo_periodo = __('Este mes', 'flavor-chat-ia');
                break;
            case 'ano':
                $fecha_inicio = date('Y-m-d', strtotime('-1 year'));
                $titulo_periodo = __('Este año', 'flavor-chat-ia');
                break;
            default:
                $titulo_periodo = __('Total', 'flavor-chat-ia');
        }

        $condicion_fecha = $fecha_inicio ? $wpdb->prepare("AND fecha_solicitud >= %s", $fecha_inicio) : '';
        $condicion_fecha_completada = $fecha_inicio ? $wpdb->prepare("AND fecha_completado >= %s", $fecha_inicio) : '';

        // Estadísticas generales
        $total_solicitudes = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE 1=1 {$condicion_fecha}"
        );

        $solicitudes_completadas = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado = 'completada' {$condicion_fecha_completada}"
        );

        $solicitudes_activas = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado IN ('abierta', 'asignada', 'en_curso')"
        );

        $voluntarios_activos = $wpdb->get_var(
            "SELECT COUNT(DISTINCT ayudante_id) FROM {$tabla_solicitudes} WHERE ayudante_id IS NOT NULL {$condicion_fecha}"
        );

        $valoracion_media_global = $wpdb->get_var(
            "SELECT AVG(puntuacion) FROM {$tabla_valoraciones}"
        );

        // Estadísticas por categoría
        $estadisticas_categorias = $wpdb->get_results(
            "SELECT categoria, COUNT(*) as total,
                    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas
             FROM {$tabla_solicitudes}
             WHERE 1=1 {$condicion_fecha}
             GROUP BY categoria
             ORDER BY total DESC"
        );

        // Ranking de voluntarios (si está habilitado)
        $ranking_voluntarios = [];
        $mostrar_ranking = $atributos['mostrar_ranking'] === 'true';
        $limite_ranking = absint($atributos['limite_ranking']);

        if ($mostrar_ranking) {
            $ranking_voluntarios = $wpdb->get_results($wpdb->prepare(
                "SELECT u.ID, u.display_name,
                        COUNT(s.id) as ayudas_realizadas,
                        COALESCE(AVG(v.puntuacion), 0) as valoracion_media,
                        COALESCE(SUM(v.puntos_solidaridad_otorgados), 0) as puntos_totales
                 FROM {$wpdb->users} u
                 INNER JOIN {$tabla_solicitudes} s ON s.ayudante_id = u.ID AND s.estado = 'completada'
                 LEFT JOIN {$tabla_valoraciones} v ON v.valorado_id = u.ID
                 GROUP BY u.ID
                 ORDER BY ayudas_realizadas DESC, puntos_totales DESC
                 LIMIT %d",
                $limite_ranking
            ));
        }

        $categorias = $this->get_categorias_ayuda();

        // Calcular tasa de éxito
        $tasa_exito = $total_solicitudes > 0
            ? round(($solicitudes_completadas / $total_solicitudes) * 100, 1)
            : 0;

        ob_start();
        ?>
        <div class="ayuda-vecinal-estadisticas">
            <div class="estadisticas-header">
                <h2><?php esc_html_e('Estadísticas de ayuda comunitaria', 'flavor-chat-ia'); ?></h2>
                <span class="periodo-badge"><?php echo esc_html($titulo_periodo); ?></span>
            </div>

            <!-- KPIs principales -->
            <div class="estadisticas-kpis">
                <div class="kpi-card">
                    <div class="kpi-icono">📝</div>
                    <div class="kpi-valor"><?php echo esc_html($total_solicitudes); ?></div>
                    <div class="kpi-etiqueta"><?php esc_html_e('Solicitudes totales', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="kpi-card destacado">
                    <div class="kpi-icono">✅</div>
                    <div class="kpi-valor"><?php echo esc_html($solicitudes_completadas); ?></div>
                    <div class="kpi-etiqueta"><?php esc_html_e('Ayudas completadas', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icono">🔄</div>
                    <div class="kpi-valor"><?php echo esc_html($solicitudes_activas); ?></div>
                    <div class="kpi-etiqueta"><?php esc_html_e('Solicitudes activas', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icono">🤝</div>
                    <div class="kpi-valor"><?php echo esc_html($voluntarios_activos); ?></div>
                    <div class="kpi-etiqueta"><?php esc_html_e('Voluntarios activos', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icono">📊</div>
                    <div class="kpi-valor"><?php echo esc_html($tasa_exito); ?>%</div>
                    <div class="kpi-etiqueta"><?php esc_html_e('Tasa de éxito', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icono">⭐</div>
                    <div class="kpi-valor"><?php echo $valoracion_media_global ? number_format($valoracion_media_global, 1) : '-'; ?></div>
                    <div class="kpi-etiqueta"><?php esc_html_e('Valoración media', 'flavor-chat-ia'); ?></div>
                </div>
            </div>

            <!-- Estadísticas por categoría -->
            <?php if (!empty($estadisticas_categorias)): ?>
            <div class="estadisticas-seccion">
                <h3><?php esc_html_e('Por categoría', 'flavor-chat-ia'); ?></h3>
                <div class="categorias-stats">
                    <?php foreach ($estadisticas_categorias as $stats_categoria):
                        $nombre_categoria = $categorias[$stats_categoria->categoria] ?? ucfirst($stats_categoria->categoria);
                        $porcentaje_completadas = $stats_categoria->total > 0
                            ? round(($stats_categoria->completadas / $stats_categoria->total) * 100)
                            : 0;
                    ?>
                    <div class="categoria-stat-item">
                        <div class="categoria-info">
                            <span class="categoria-nombre"><?php echo esc_html($nombre_categoria); ?></span>
                            <span class="categoria-numeros"><?php echo esc_html($stats_categoria->completadas); ?>/<?php echo esc_html($stats_categoria->total); ?></span>
                        </div>
                        <div class="barra-progreso">
                            <div class="barra-relleno" style="width: <?php echo esc_attr($porcentaje_completadas); ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ranking de voluntarios -->
            <?php if ($mostrar_ranking && !empty($ranking_voluntarios)): ?>
            <div class="estadisticas-seccion">
                <h3>🏆 <?php esc_html_e('Top voluntarios', 'flavor-chat-ia'); ?></h3>
                <div class="ranking-voluntarios">
                    <table class="ranking-tabla">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php esc_html_e('Voluntario', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Ayudas', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Valoración', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Puntos', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ranking_voluntarios as $posicion => $voluntario): ?>
                            <tr class="<?php echo $posicion < 3 ? 'top-3' : ''; ?>">
                                <td class="posicion">
                                    <?php
                                    $medallas = ['🥇', '🥈', '🥉'];
                                    echo $posicion < 3 ? $medallas[$posicion] : ($posicion + 1);
                                    ?>
                                </td>
                                <td class="nombre">
                                    <?php echo esc_html($voluntario->display_name); ?>
                                </td>
                                <td class="ayudas"><?php echo esc_html($voluntario->ayudas_realizadas); ?></td>
                                <td class="valoracion">
                                    <?php if ($voluntario->valoracion_media > 0): ?>
                                        ⭐ <?php echo number_format($voluntario->valoracion_media, 1); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="puntos"><?php echo esc_html($voluntario->puntos_totales); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza mensaje de login requerido
     *
     * @param string $mensaje Mensaje a mostrar
     * @return string HTML del mensaje
     */
    private function render_mensaje_login($mensaje) {
        ob_start();
        ?>
        <div class="ayuda-vecinal-login-requerido">
            <div class="login-icono">🔐</div>
            <p><?php echo esc_html($mensaje); ?></p>
            <div class="login-acciones">
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn-principal">
                    <?php esc_html_e('Iniciar sesión', 'flavor-chat-ia'); ?>
                </a>
                <?php if (get_option('users_can_register')): ?>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="btn-secundario">
                    <?php esc_html_e('Registrarse', 'flavor-chat-ia'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    // AJAX Handlers para shortcodes frontend
    // =========================================================================

    /**
     * AJAX: Crear solicitud desde frontend
     */
    public function ajax_crear_solicitud_frontend() {
        check_ajax_referer('ayuda_vecinal_frontend', 'ayuda_vecinal_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        $datos_solicitud = [
            'solicitante_id' => get_current_user_id(),
            'titulo' => sanitize_text_field($_POST['titulo'] ?? ''),
            'categoria' => sanitize_text_field($_POST['categoria'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'urgencia' => sanitize_text_field($_POST['urgencia'] ?? 'media'),
            'ubicacion' => sanitize_text_field($_POST['ubicacion'] ?? ''),
            'ubicacion_lat' => floatval($_POST['ubicacion_lat'] ?? 0) ?: null,
            'ubicacion_lng' => floatval($_POST['ubicacion_lng'] ?? 0) ?: null,
            'fecha_necesaria' => sanitize_text_field($_POST['fecha_necesaria'] ?? '') ?: null,
            'duracion_estimada_minutos' => absint($_POST['duracion_estimada_minutos'] ?? 0) ?: null,
            'necesita_desplazamiento' => isset($_POST['necesita_desplazamiento']) ? 1 : 0,
            'compensacion' => sanitize_text_field($_POST['compensacion'] ?? ''),
            'estado' => 'abierta',
            'fecha_solicitud' => current_time('mysql'),
        ];

        // Validaciones
        if (empty($datos_solicitud['titulo']) || empty($datos_solicitud['categoria']) || empty($datos_solicitud['descripcion'])) {
            wp_send_json_error(['message' => __('Por favor, completa todos los campos obligatorios.', 'flavor-chat-ia')]);
        }

        $resultado_insercion = $wpdb->insert($tabla_solicitudes, $datos_solicitud);

        if ($resultado_insercion) {
            $id_solicitud = $wpdb->insert_id;
            wp_send_json_success([
                'message' => __('Tu solicitud de ayuda ha sido publicada.', 'flavor-chat-ia'),
                'solicitud_id' => $id_solicitud,
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al crear la solicitud. Inténtalo de nuevo.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Crear o actualizar oferta de ayuda
     */
    public function ajax_crear_oferta_frontend() {
        check_ajax_referer('ayuda_vecinal_frontend', 'ayuda_vecinal_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_ofertas = $wpdb->prefix . 'flavor_ayuda_ofertas';
        $id_usuario = get_current_user_id();
        $id_oferta_existente = absint($_POST['oferta_id'] ?? 0);

        // Procesar disponibilidad
        $disponibilidad_dias = [];
        if (!empty($_POST['disponibilidad']) && is_array($_POST['disponibilidad'])) {
            foreach ($_POST['disponibilidad'] as $dia => $valor) {
                $disponibilidad_dias[sanitize_key($dia)] = true;
            }
        }

        $datos_oferta = [
            'usuario_id' => $id_usuario,
            'titulo' => sanitize_text_field($_POST['titulo'] ?? ''),
            'categoria' => sanitize_text_field($_POST['categoria'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'habilidades' => sanitize_text_field($_POST['habilidades'] ?? ''),
            'disponibilidad' => wp_json_encode($disponibilidad_dias),
            'radio_km' => absint($_POST['radio_km'] ?? 5),
            'tiene_vehiculo' => isset($_POST['tiene_vehiculo']) ? 1 : 0,
            'activa' => 1,
        ];

        // Validaciones
        if (empty($datos_oferta['titulo']) || empty($datos_oferta['categoria']) || empty($datos_oferta['descripcion'])) {
            wp_send_json_error(['message' => __('Por favor, completa todos los campos obligatorios.', 'flavor-chat-ia')]);
        }

        if ($id_oferta_existente) {
            // Verificar que la oferta pertenece al usuario
            $oferta_usuario = $wpdb->get_var($wpdb->prepare(
                "SELECT usuario_id FROM {$tabla_ofertas} WHERE id = %d",
                $id_oferta_existente
            ));

            if ((int)$oferta_usuario !== $id_usuario) {
                wp_send_json_error(['message' => __('No tienes permisos para editar esta oferta.', 'flavor-chat-ia')]);
            }

            $datos_oferta['fecha_actualizacion'] = current_time('mysql');
            $resultado = $wpdb->update($tabla_ofertas, $datos_oferta, ['id' => $id_oferta_existente]);
            $mensaje_exito = __('Tu oferta ha sido actualizada.', 'flavor-chat-ia');
        } else {
            $datos_oferta['fecha_creacion'] = current_time('mysql');
            $resultado = $wpdb->insert($tabla_ofertas, $datos_oferta);
            $mensaje_exito = __('Tu oferta de ayuda ha sido publicada.', 'flavor-chat-ia');
        }

        if ($resultado !== false) {
            wp_send_json_success(['message' => $mensaje_exito]);
        } else {
            wp_send_json_error(['message' => __('Error al guardar la oferta. Inténtalo de nuevo.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Responder a una solicitud (ofrecer ayuda)
     */
    public function ajax_responder_solicitud() {
        check_ajax_referer('ayuda_vecinal_frontend', 'ayuda_vecinal_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';
        $tabla_respuestas = $wpdb->prefix . 'flavor_ayuda_respuestas';
        $id_usuario = get_current_user_id();
        $id_solicitud = absint($_POST['solicitud_id'] ?? 0);

        if (!$id_solicitud) {
            wp_send_json_error(['message' => __('Solicitud no válida.', 'flavor-chat-ia')]);
        }

        // Verificar que la solicitud existe y está abierta
        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_solicitudes} WHERE id = %d AND estado = 'abierta'",
            $id_solicitud
        ));

        if (!$solicitud) {
            wp_send_json_error(['message' => __('Esta solicitud ya no está disponible.', 'flavor-chat-ia')]);
        }

        // No puede responder a su propia solicitud
        if ((int)$solicitud->solicitante_id === $id_usuario) {
            wp_send_json_error(['message' => __('No puedes responder a tu propia solicitud.', 'flavor-chat-ia')]);
        }

        // Verificar si ya ha respondido
        $respuesta_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_respuestas} WHERE solicitud_id = %d AND ayudante_id = %d AND estado != 'retirada'",
            $id_solicitud,
            $id_usuario
        ));

        if ($respuesta_existente) {
            wp_send_json_error(['message' => __('Ya has ofrecido ayuda para esta solicitud.', 'flavor-chat-ia')]);
        }

        $datos_respuesta = [
            'solicitud_id' => $id_solicitud,
            'ayudante_id' => $id_usuario,
            'mensaje' => sanitize_textarea_field($_POST['mensaje'] ?? ''),
            'disponibilidad_propuesta' => sanitize_text_field($_POST['disponibilidad'] ?? '') ?: null,
            'estado' => 'pendiente',
            'fecha_respuesta' => current_time('mysql'),
        ];

        $resultado = $wpdb->insert($tabla_respuestas, $datos_respuesta);

        if ($resultado) {
            wp_send_json_success(['message' => __('Tu oferta de ayuda ha sido enviada al solicitante.', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(['message' => __('Error al enviar la respuesta. Inténtalo de nuevo.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Obtener solicitudes para el mapa
     */
    public function ajax_get_solicitudes_mapa() {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_solicitudes';

        $condiciones = ['estado = %s', 'ubicacion_lat IS NOT NULL', 'ubicacion_lng IS NOT NULL'];
        $valores = ['abierta'];

        if (!empty($_GET['categoria'])) {
            $condiciones[] = 'categoria = %s';
            $valores[] = sanitize_text_field($_GET['categoria']);
        }

        if (!empty($_GET['urgencia'])) {
            $condiciones[] = 'urgencia = %s';
            $valores[] = sanitize_text_field($_GET['urgencia']);
        }

        $consulta = $wpdb->prepare(
            "SELECT id, titulo, categoria, urgencia, ubicacion, ubicacion_lat, ubicacion_lng
             FROM {$tabla_solicitudes}
             WHERE " . implode(' AND ', $condiciones) . "
             ORDER BY fecha_solicitud DESC
             LIMIT 100",
            $valores
        );

        $solicitudes = $wpdb->get_results($consulta);
        $categorias = $this->get_categorias_ayuda();

        $marcadores = [];
        foreach ($solicitudes as $solicitud) {
            $marcadores[] = [
                'id' => $solicitud->id,
                'lat' => floatval($solicitud->ubicacion_lat),
                'lng' => floatval($solicitud->ubicacion_lng),
                'titulo' => $solicitud->titulo,
                'categoria' => $categorias[$solicitud->categoria] ?? $solicitud->categoria,
                'urgencia' => $solicitud->urgencia,
                'ubicacion' => $solicitud->ubicacion,
            ];
        }

        wp_send_json_success(['marcadores' => $marcadores]);
    }


    /**
     * Inicializa el dashboard widget del módulo
     */
    private function inicializar_dashboard_widget() {
        $archivo = dirname(__FILE__) . '/class-ayuda-vecinal-dashboard-widget.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Ayuda_Vecinal_Dashboard_Widget')) {
                new Flavor_Ayuda_Vecinal_Dashboard_Widget();
            }
        }
    }
}
