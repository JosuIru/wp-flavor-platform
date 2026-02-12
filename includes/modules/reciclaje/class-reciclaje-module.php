<?php
/**
 * Módulo de Reciclaje para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Reciclaje - Gestión de reciclaje comunitario
 */
class Flavor_Chat_Reciclaje_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'reciclaje';
        $this->name = 'Reciclaje Comunitario'; // Translation loaded on init
        $this->description = 'Sistema de gestión de reciclaje, puntos limpios y economía circular en la comunidad.'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_puntos_reciclaje = $wpdb->prefix . 'flavor_reciclaje_puntos';

        return Flavor_Chat_Helpers::tabla_existe($tabla_puntos_reciclaje);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Reciclaje no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
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
            'puntos_por_kg' => 10,
            'permite_canje_puntos' => true,
            'notificar_recogidas' => true,
            'permite_reportar_contenedores' => true,
            'categorias_reciclaje' => ['papel', 'plastico', 'vidrio', 'organico', 'electronico', 'ropa', 'aceite', 'pilas'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);
        add_action('init', [$this, 'register_shortcodes']);

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();

        // AJAX handlers
        add_action('wp_ajax_reciclaje_registrar_deposito', [$this, 'ajax_registrar_deposito']);
        add_action('wp_ajax_reciclaje_obtener_puntos', [$this, 'ajax_obtener_puntos']);
        add_action('wp_ajax_reciclaje_reportar_contenedor', [$this, 'ajax_reportar_contenedor']);
        add_action('wp_ajax_reciclaje_calendario', [$this, 'ajax_calendario_recogidas']);
        add_action('wp_ajax_reciclaje_mis_puntos', [$this, 'ajax_mis_puntos']);
        add_action('wp_ajax_reciclaje_canjear_puntos', [$this, 'ajax_canjear_puntos']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // WP Cron
        add_action('reciclaje_notificar_recogidas', [$this, 'notificar_recogidas_proximas']);
        add_action('reciclaje_verificar_contenedores', [$this, 'verificar_estado_contenedores']);

        if (!wp_next_scheduled('reciclaje_notificar_recogidas')) {
            wp_schedule_event(time(), 'daily', 'reciclaje_notificar_recogidas');
        }
        if (!wp_next_scheduled('reciclaje_verificar_contenedores')) {
            wp_schedule_event(time(), 'twicedaily', 'reciclaje_verificar_contenedores');
        }

        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Admin menu
        add_action('admin_menu', [$this, 'register_admin_menu']);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_puntos_reciclaje = $wpdb->prefix . 'flavor_reciclaje_puntos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_puntos_reciclaje)) {
            $this->create_tables();
        }
    }

    /**
     * Registra los post types
     */
    public function register_post_types() {
        // Post type para recompensas canjeables
        register_post_type('recompensa_reciclaje', [
            'labels' => [
                'name' => __('Recompensas', 'flavor-chat-ia'),
                'singular_name' => __('Recompensa', 'flavor-chat-ia'),
                'add_new' => __('Añadir Recompensa', 'flavor-chat-ia'),
                'add_new_item' => __('Añadir Nueva Recompensa', 'flavor-chat-ia'),
                'edit_item' => __('Editar Recompensa', 'flavor-chat-ia'),
                'new_item' => __('Nueva Recompensa', 'flavor-chat-ia'),
                'view_item' => __('Ver Recompensa', 'flavor-chat-ia'),
                'search_items' => __('Buscar Recompensas', 'flavor-chat-ia'),
                'not_found' => __('No se encontraron recompensas', 'flavor-chat-ia'),
            ],
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-awards',
            'supports' => ['title', 'editor', 'thumbnail'],
            'rewrite' => ['slug' => 'recompensas-reciclaje'],
            'show_in_rest' => true,
        ]);

        // Post type para guías de reciclaje
        register_post_type('guia_reciclaje', [
            'labels' => [
                'name' => __('Guías', 'flavor-chat-ia'),
                'singular_name' => __('Guía', 'flavor-chat-ia'),
                'add_new' => __('Añadir Guía', 'flavor-chat-ia'),
            ],
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-book-alt',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'rewrite' => ['slug' => 'guias-reciclaje'],
            'show_in_rest' => true,
        ]);
    }

    /**
     * Registra las taxonomías
     */
    public function register_taxonomies() {
        register_taxonomy('tipo_material', ['guia_reciclaje'], [
            'labels' => [
                'name' => __('Tipos de Material', 'flavor-chat-ia'),
                'singular_name' => __('Tipo de Material', 'flavor-chat-ia'),
            ],
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'tipo-material'],
        ]);

        register_taxonomy('categoria_recompensa', ['recompensa_reciclaje'], [
            'labels' => [
                'name' => __('Categorías de Recompensa', 'flavor-chat-ia'),
                'singular_name' => __('Categoría de Recompensa', 'flavor-chat-ia'),
            ],
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'categoria-recompensa'],
        ]);
    }

    /**
     * Registra los shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('reciclaje_puntos_cercanos', [$this, 'shortcode_puntos_cercanos']);
        add_shortcode('reciclaje_calendario', [$this, 'shortcode_calendario']);
        add_shortcode('reciclaje_mis_puntos', [$this, 'shortcode_mis_puntos']);
        add_shortcode('reciclaje_ranking', [$this, 'shortcode_ranking']);
        add_shortcode('reciclaje_guia', [$this, 'shortcode_guia']);
        add_shortcode('reciclaje_recompensas', [$this, 'shortcode_recompensas']);
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_puntos_reciclaje = $wpdb->prefix . 'flavor_reciclaje_puntos';
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $tabla_recogidas = $wpdb->prefix . 'flavor_reciclaje_recogidas';
        $tabla_contenedores = $wpdb->prefix . 'flavor_reciclaje_contenedores';

        $sql_puntos = "CREATE TABLE IF NOT EXISTS $tabla_puntos_reciclaje (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            tipo enum('punto_limpio','contenedor_comunitario','centro_acopio','movil') DEFAULT 'contenedor_comunitario',
            direccion varchar(500) NOT NULL,
            latitud decimal(10,7) NOT NULL,
            longitud decimal(10,7) NOT NULL,
            materiales_aceptados text NOT NULL COMMENT 'JSON array',
            horario text DEFAULT NULL,
            contacto varchar(255) DEFAULT NULL,
            instrucciones text DEFAULT NULL,
            foto_url varchar(500) DEFAULT NULL,
            estado enum('activo','lleno','mantenimiento','inactivo') DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ubicacion (latitud, longitud),
            KEY tipo (tipo),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_depositos = "CREATE TABLE IF NOT EXISTS $tabla_depositos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            punto_reciclaje_id bigint(20) unsigned NOT NULL,
            tipo_material varchar(50) NOT NULL,
            cantidad_kg decimal(10,2) NOT NULL,
            puntos_ganados int(11) DEFAULT 0,
            foto_url varchar(500) DEFAULT NULL,
            verificado tinyint(1) DEFAULT 0,
            verificado_por bigint(20) unsigned DEFAULT NULL,
            fecha_deposito datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY punto_reciclaje_id (punto_reciclaje_id),
            KEY tipo_material (tipo_material),
            KEY fecha_deposito (fecha_deposito)
        ) $charset_collate;";

        $sql_recogidas = "CREATE TABLE IF NOT EXISTS $tabla_recogidas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tipo_recogida enum('programada','a_demanda','urgente') DEFAULT 'programada',
            zona varchar(255) NOT NULL,
            tipos_residuos text NOT NULL COMMENT 'JSON',
            fecha_programada datetime NOT NULL,
            hora_inicio time DEFAULT NULL,
            hora_fin time DEFAULT NULL,
            ruta text DEFAULT NULL COMMENT 'JSON de coordenadas',
            notas text DEFAULT NULL,
            estado enum('programada','en_curso','completada','cancelada') DEFAULT 'programada',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY fecha_programada (fecha_programada),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_contenedores = "CREATE TABLE IF NOT EXISTS $tabla_contenedores (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            punto_reciclaje_id bigint(20) unsigned NOT NULL,
            tipo_residuo varchar(50) NOT NULL,
            capacidad_litros int(11) DEFAULT NULL,
            nivel_llenado int(11) DEFAULT 0 COMMENT 'Porcentaje 0-100',
            necesita_vaciado tinyint(1) DEFAULT 0,
            ultima_recogida datetime DEFAULT NULL,
            reportes_problema int(11) DEFAULT 0,
            estado enum('operativo','lleno','danado','fuera_servicio') DEFAULT 'operativo',
            fecha_instalacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY punto_reciclaje_id (punto_reciclaje_id),
            KEY tipo_residuo (tipo_residuo),
            KEY necesita_vaciado (necesita_vaciado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_puntos);
        dbDelta($sql_depositos);
        dbDelta($sql_recogidas);
        dbDelta($sql_contenedores);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'puntos_cercanos' => [
                'description' => 'Encontrar puntos de reciclaje cercanos',
                'params' => ['lat', 'lng', 'tipo_material'],
            ],
            'calendario_recogidas' => [
                'description' => 'Ver calendario de recogidas',
                'params' => ['zona', 'tipo_residuo'],
            ],
            'registrar_deposito' => [
                'description' => 'Registrar depósito de material',
                'params' => ['punto_id', 'tipo_material', 'cantidad_kg'],
            ],
            'mis_puntos_reciclaje' => [
                'description' => 'Ver mis puntos acumulados',
                'params' => [],
            ],
            'canje_puntos' => [
                'description' => 'Canjear puntos por recompensas',
                'params' => ['recompensa_id'],
            ],
            'reportar_contenedor' => [
                'description' => 'Reportar problema con contenedor',
                'params' => ['contenedor_id', 'problema'],
            ],
            'guia_reciclaje' => [
                'description' => 'Guía de qué reciclar y cómo',
                'params' => ['tipo_material'],
            ],
            // Admin actions
            'estadisticas_reciclaje' => [
                'description' => 'Estadísticas de reciclaje (admin)',
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
     * Acción: Puntos cercanos
     */
    private function action_puntos_cercanos($params) {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';

        $lat = floatval($params['lat'] ?? 0);
        $lng = floatval($params['lng'] ?? 0);
        $tipo_material = sanitize_text_field($params['tipo_material'] ?? '');

        $where = "estado = 'activo'";
        if (!empty($tipo_material)) {
            $where .= $wpdb->prepare(" AND materiales_aceptados LIKE %s", '%' . $wpdb->esc_like($tipo_material) . '%');
        }

        if ($lat != 0 && $lng != 0) {
            $sql = "SELECT *,
                    (6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) AS distancia
                    FROM $tabla_puntos
                    WHERE $where
                    ORDER BY distancia ASC
                    LIMIT 20";

            $puntos = $wpdb->get_results($wpdb->prepare($sql, $lat, $lng, $lat));
        } else {
            $puntos = $wpdb->get_results("SELECT * FROM $tabla_puntos WHERE $where ORDER BY nombre LIMIT 20");
        }

        return [
            'success' => true,
            'puntos' => array_map(function($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'tipo' => $p->tipo,
                    'direccion' => $p->direccion,
                    'lat' => floatval($p->latitud),
                    'lng' => floatval($p->longitud),
                    'materiales' => json_decode($p->materiales_aceptados, true),
                    'horario' => $p->horario,
                    'distancia_km' => isset($p->distancia) ? round($p->distancia, 2) : null,
                ];
            }, $puntos),
        ];
    }

    /**
     * Registra el menú de administración
     */
    public function register_admin_menu() {
        add_submenu_page(
            'flavor-chat-ia',
            __('Reciclaje', 'flavor-chat-ia'),
            __('Reciclaje', 'flavor-chat-ia'),
            'manage_options',
            'flavor-reciclaje',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'reciclaje',
            'label' => __('Reciclaje', 'flavor-chat-ia'),
            'icon' => 'dashicons-image-rotate',
            'capability' => 'manage_options',
            'categoria' => 'sostenibilidad',
            'paginas' => [
                [
                    'slug' => 'reciclaje-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'reciclaje-puntos',
                    'titulo' => __('Puntos de Reciclaje', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_puntos_reciclaje'],
                    'badge' => [$this, 'contar_puntos_reciclaje'],
                ],
                [
                    'slug' => 'reciclaje-estadisticas',
                    'titulo' => __('Estadísticas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_estadisticas'],
                ],
                [
                    'slug' => 'reciclaje-campanas',
                    'titulo' => __('Campañas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_campanas'],
                ],
                [
                    'slug' => 'reciclaje-config',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_configuracion'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta los puntos de reciclaje activos
     *
     * @return int
     */
    public function contar_puntos_reciclaje() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_puntos)) {
            return 0;
        }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_puntos WHERE estado = 'activo'");
    }

    /**
     * Estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $estadisticas = [];

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_depositos)) {
            return $estadisticas;
        }

        // Kg reciclados este mes
        $primer_dia_mes = date('Y-m-01 00:00:00');
        $kg_este_mes = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(cantidad_kg), 0) FROM $tabla_depositos WHERE verificado = 1 AND fecha_deposito >= %s",
            $primer_dia_mes
        ));

        $estadisticas[] = [
            'icon' => 'dashicons-image-rotate',
            'valor' => number_format_i18n($kg_este_mes, 1) . ' kg',
            'label' => __('Kg reciclados este mes', 'flavor-chat-ia'),
            'color' => $kg_este_mes > 0 ? 'green' : 'gray',
            'enlace' => admin_url('admin.php?page=reciclaje-estadisticas'),
        ];

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard del módulo de reciclaje
     */
    public function render_admin_dashboard() {
        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Dashboard de Reciclaje', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Punto', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=reciclaje-puntos&action=nuevo'), 'class' => 'button-primary'],
        ]);

        // Estadísticas resumen
        $estadisticas = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_depositos)) {
            $primer_dia_mes = date('Y-m-01 00:00:00');
            $estadisticas['kg_mes'] = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(cantidad_kg), 0) FROM $tabla_depositos WHERE verificado = 1 AND fecha_deposito >= %s",
                $primer_dia_mes
            ));
            $estadisticas['total_depositos'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_depositos WHERE verificado = 1");
            $estadisticas['usuarios_activos'] = (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_depositos WHERE verificado = 1");
        }
        if (Flavor_Chat_Helpers::tabla_existe($tabla_puntos)) {
            $estadisticas['puntos_activos'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_puntos WHERE estado = 'activo'");
        }

        echo '<div class="flavor-stats-grid">';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html(number_format_i18n($estadisticas['kg_mes'] ?? 0, 1)) . ' kg</span><span class="stat-label">' . __('Reciclado este mes', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($estadisticas['total_depositos'] ?? 0) . '</span><span class="stat-label">' . __('Depósitos totales', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($estadisticas['usuarios_activos'] ?? 0) . '</span><span class="stat-label">' . __('Usuarios activos', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($estadisticas['puntos_activos'] ?? 0) . '</span><span class="stat-label">' . __('Puntos de reciclaje', 'flavor-chat-ia') . '</span></div>';
        echo '</div>';

        echo '<p>' . __('Panel de control del módulo de reciclaje con métricas ambientales y accesos rápidos.', 'flavor-chat-ia') . '</p>';
        echo '</div>';
    }

    /**
     * Renderiza la página de puntos de reciclaje
     */
    public function render_admin_puntos_reciclaje() {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Puntos de Reciclaje', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Punto', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=reciclaje-puntos&action=nuevo'), 'class' => 'button-primary'],
        ]);

        $puntos = $wpdb->get_results("SELECT * FROM $tabla_puntos ORDER BY nombre");

        if (!empty($puntos)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . __('Nombre', 'flavor-chat-ia') . '</th><th>' . __('Tipo', 'flavor-chat-ia') . '</th><th>' . __('Dirección', 'flavor-chat-ia') . '</th><th>' . __('Estado', 'flavor-chat-ia') . '</th><th>' . __('Acciones', 'flavor-chat-ia') . '</th></tr></thead>';
            echo '<tbody>';
            foreach ($puntos as $punto) {
                $clase_estado = $punto->estado === 'activo' ? 'status-active' : 'status-inactive';
                echo '<tr>';
                echo '<td><strong>' . esc_html($punto->nombre) . '</strong></td>';
                echo '<td>' . esc_html(ucfirst(str_replace('_', ' ', $punto->tipo))) . '</td>';
                echo '<td>' . esc_html($punto->direccion) . '</td>';
                echo '<td><span class="' . esc_attr($clase_estado) . '">' . esc_html(ucfirst($punto->estado)) . '</span></td>';
                echo '<td><a href="#" class="button button-small">' . __('Editar', 'flavor-chat-ia') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay puntos de reciclaje registrados.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la página de estadísticas
     */
    public function render_admin_estadisticas() {
        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Estadísticas de Reciclaje', 'flavor-chat-ia'));

        // Estadísticas generales
        $estadisticas = $wpdb->get_row("
            SELECT
                COUNT(*) as total_depositos,
                COALESCE(SUM(cantidad_kg), 0) as total_kg,
                COALESCE(SUM(puntos_ganados), 0) as total_puntos,
                COUNT(DISTINCT usuario_id) as usuarios_activos
            FROM $tabla_depositos
            WHERE verificado = 1
        ");

        echo '<div class="flavor-stats-grid">';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html(number_format_i18n($estadisticas->total_depositos)) . '</span><span class="stat-label">' . __('Depósitos Totales', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html(number_format_i18n($estadisticas->total_kg, 2)) . ' kg</span><span class="stat-label">' . __('Material Reciclado', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html(number_format_i18n($estadisticas->total_puntos)) . '</span><span class="stat-label">' . __('Puntos Otorgados', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html(number_format_i18n($estadisticas->usuarios_activos)) . '</span><span class="stat-label">' . __('Usuarios Activos', 'flavor-chat-ia') . '</span></div>';
        echo '</div>';

        // Estadísticas por tipo de material
        $por_material = $wpdb->get_results("
            SELECT tipo_material,
                   SUM(cantidad_kg) as total_kg,
                   COUNT(*) as num_depositos
            FROM $tabla_depositos
            WHERE verificado = 1
            GROUP BY tipo_material
            ORDER BY total_kg DESC
        ");

        if (!empty($por_material)) {
            echo '<h3>' . __('Por Tipo de Material', 'flavor-chat-ia') . '</h3>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . __('Material', 'flavor-chat-ia') . '</th><th>' . __('Total (kg)', 'flavor-chat-ia') . '</th><th>' . __('Depósitos', 'flavor-chat-ia') . '</th></tr></thead>';
            echo '<tbody>';
            foreach ($por_material as $material) {
                echo '<tr>';
                echo '<td>' . esc_html(ucfirst($material->tipo_material)) . '</td>';
                echo '<td>' . esc_html(number_format_i18n($material->total_kg, 2)) . ' kg</td>';
                echo '<td>' . esc_html(number_format_i18n($material->num_depositos)) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la página de campañas
     */
    public function render_admin_campanas() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Campañas de Reciclaje', 'flavor-chat-ia'), [
            ['label' => __('Nueva Campaña', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=reciclaje-campanas&action=nueva'), 'class' => 'button-primary'],
        ]);

        echo '<p>' . __('Gestiona campañas de concienciación y eventos de reciclaje especiales.', 'flavor-chat-ia') . '</p>';
        echo '<div class="notice notice-info"><p>' . __('Próximamente: Funcionalidad de campañas de reciclaje.', 'flavor-chat-ia') . '</p></div>';
        echo '</div>';
    }

    /**
     * Renderiza la página de configuración
     */
    public function render_admin_configuracion() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Reciclaje', 'flavor-chat-ia'));

        $configuracion_actual = $this->get_settings();

        echo '<form method="post" action="">';
        echo '<table class="form-table">';

        echo '<tr><th scope="row"><label for="puntos_por_kg">' . __('Puntos por Kg', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="puntos_por_kg" id="puntos_por_kg" value="' . esc_attr($configuracion_actual['puntos_por_kg'] ?? 10) . '" min="1" class="small-text" />';
        echo '<p class="description">' . __('Puntos otorgados por cada kilogramo reciclado.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="permite_canje_puntos">' . __('Permitir canje de puntos', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="permite_canje_puntos" id="permite_canje_puntos" ' . checked($configuracion_actual['permite_canje_puntos'] ?? true, true, false) . ' /></td></tr>';

        echo '<tr><th scope="row"><label for="notificar_recogidas">' . __('Notificar recogidas', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="notificar_recogidas" id="notificar_recogidas" ' . checked($configuracion_actual['notificar_recogidas'] ?? true, true, false) . ' />';
        echo '<p class="description">' . __('Enviar recordatorios de recogidas programadas.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="permite_reportar_contenedores">' . __('Permitir reportes de contenedores', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="checkbox" name="permite_reportar_contenedores" id="permite_reportar_contenedores" ' . checked($configuracion_actual['permite_reportar_contenedores'] ?? true, true, false) . ' /></td></tr>';

        echo '</table>';
        echo '<p class="submit"><input type="submit" name="guardar_config" class="button-primary" value="' . __('Guardar Configuración', 'flavor-chat-ia') . '" /></p>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Renderiza la página de administración
     */
    public function render_admin_page() {
        $tab = $_GET['tab'] ?? 'puntos';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Gestión de Reciclaje', 'flavor-chat-ia'); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=flavor-reciclaje&tab=puntos" class="nav-tab <?php echo $tab === 'puntos' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Puntos de Reciclaje', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=flavor-reciclaje&tab=recogidas" class="nav-tab <?php echo $tab === 'recogidas' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Recogidas', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=flavor-reciclaje&tab=depositos" class="nav-tab <?php echo $tab === 'depositos' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Depósitos', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=flavor-reciclaje&tab=estadisticas" class="nav-tab <?php echo $tab === 'estadisticas' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Estadísticas', 'flavor-chat-ia'); ?>
                </a>
            </nav>

            <div class="tab-content">
                <?php
                switch ($tab) {
                    case 'recogidas':
                        $this->render_recogidas_tab();
                        break;
                    case 'depositos':
                        $this->render_depositos_tab();
                        break;
                    case 'estadisticas':
                        $this->render_estadisticas_tab();
                        break;
                    case 'puntos':
                    default:
                        $this->render_puntos_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Tab de puntos de reciclaje
     */
    private function render_puntos_tab() {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';
        $puntos = $wpdb->get_results("SELECT * FROM $tabla_puntos ORDER BY nombre");
        ?>
        <div class="reciclaje-puntos-admin">
            <h2><?php esc_html_e('Puntos de Reciclaje', 'flavor-chat-ia'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Dirección', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($puntos as $punto): ?>
                    <tr>
                        <td><?php echo esc_html($punto->nombre); ?></td>
                        <td><?php echo esc_html($punto->tipo); ?></td>
                        <td><?php echo esc_html($punto->direccion); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($punto->estado); ?>">
                                <?php echo esc_html($punto->estado); ?>
                            </span>
                        </td>
                        <td>
                            <a href="#" class="button button-small"><?php esc_html_e('Editar', 'flavor-chat-ia'); ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Tab de recogidas
     */
    private function render_recogidas_tab() {
        global $wpdb;
        $tabla_recogidas = $wpdb->prefix . 'flavor_reciclaje_recogidas';
        $recogidas = $wpdb->get_results("SELECT * FROM $tabla_recogidas ORDER BY fecha_programada DESC LIMIT 50");
        ?>
        <div class="reciclaje-recogidas-admin">
            <h2><?php esc_html_e('Calendario de Recogidas', 'flavor-chat-ia'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Zona', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recogidas as $recogida): ?>
                    <tr>
                        <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($recogida->fecha_programada))); ?></td>
                        <td><?php echo esc_html($recogida->zona); ?></td>
                        <td><?php echo esc_html($recogida->tipo_recogida); ?></td>
                        <td><?php echo esc_html($recogida->estado); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Tab de depósitos
     */
    private function render_depositos_tab() {
        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $depositos = $wpdb->get_results("
            SELECT d.*, u.display_name, p.nombre as punto_nombre
            FROM $tabla_depositos d
            LEFT JOIN {$wpdb->users} u ON d.usuario_id = u.ID
            LEFT JOIN {$wpdb->prefix}flavor_reciclaje_puntos p ON d.punto_reciclaje_id = p.id
            ORDER BY d.fecha_deposito DESC
            LIMIT 100
        ");
        ?>
        <div class="reciclaje-depositos-admin">
            <h2><?php esc_html_e('Depósitos Recientes', 'flavor-chat-ia'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Usuario', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Punto', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Material', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Cantidad (kg)', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Puntos', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Verificado', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($depositos as $deposito): ?>
                    <tr>
                        <td><?php echo esc_html($deposito->display_name); ?></td>
                        <td><?php echo esc_html($deposito->punto_nombre); ?></td>
                        <td><?php echo esc_html($deposito->tipo_material); ?></td>
                        <td><?php echo esc_html($deposito->cantidad_kg); ?></td>
                        <td><?php echo esc_html($deposito->puntos_ganados); ?></td>
                        <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($deposito->fecha_deposito))); ?></td>
                        <td><?php echo $deposito->verificado ? '✓' : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Tab de estadísticas
     */
    private function render_estadisticas_tab() {
        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';

        // Estadísticas generales
        $stats = $wpdb->get_row("
            SELECT
                COUNT(*) as total_depositos,
                SUM(cantidad_kg) as total_kg,
                SUM(puntos_ganados) as total_puntos,
                COUNT(DISTINCT usuario_id) as usuarios_activos
            FROM $tabla_depositos
            WHERE verificado = 1
        ");

        // Por tipo de material
        $por_material = $wpdb->get_results("
            SELECT tipo_material,
                   SUM(cantidad_kg) as total_kg,
                   COUNT(*) as num_depositos
            FROM $tabla_depositos
            WHERE verificado = 1
            GROUP BY tipo_material
            ORDER BY total_kg DESC
        ");
        ?>
        <div class="reciclaje-stats-admin">
            <h2><?php esc_html_e('Estadísticas de Reciclaje', 'flavor-chat-ia'); ?></h2>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo number_format_i18n($stats->total_depositos); ?></h3>
                    <p><?php esc_html_e('Depósitos Totales', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="stat-card">
                    <h3><?php echo number_format_i18n($stats->total_kg, 2); ?> kg</h3>
                    <p><?php esc_html_e('Material Reciclado', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="stat-card">
                    <h3><?php echo number_format_i18n($stats->total_puntos); ?></h3>
                    <p><?php esc_html_e('Puntos Otorgados', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="stat-card">
                    <h3><?php echo number_format_i18n($stats->usuarios_activos); ?></h3>
                    <p><?php esc_html_e('Usuarios Activos', 'flavor-chat-ia'); ?></p>
                </div>
            </div>

            <h3><?php esc_html_e('Por Tipo de Material', 'flavor-chat-ia'); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Material', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Total (kg)', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Depósitos', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($por_material as $material): ?>
                    <tr>
                        <td><?php echo esc_html($material->tipo_material); ?></td>
                        <td><?php echo number_format_i18n($material->total_kg, 2); ?> kg</td>
                        <td><?php echo number_format_i18n($material->num_depositos); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->can_activate()) {
            return;
        }

        $base_url = plugins_url('assets/css/', __FILE__);
        $version = defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '1.0.0';

        wp_enqueue_style('flavor-reciclaje', $base_url . 'reciclaje.css', [], $version);
        wp_enqueue_script('flavor-reciclaje', plugins_url('assets/js/reciclaje.js', __FILE__), ['jquery'], $version, true);

        wp_localize_script('flavor-reciclaje', 'flavorReciclaje', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reciclaje_nonce'),
            'i18n' => [
                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                'success' => __('Operación realizada correctamente', 'flavor-chat-ia'),
                'loading' => __('Cargando...', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'flavor-chat-ia_page_flavor-reciclaje') {
            return;
        }

        $base_url = plugins_url('assets/', __FILE__);
        $version = defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '1.0.0';

        wp_enqueue_style('flavor-reciclaje-admin', $base_url . 'reciclaje-admin.css', [], $version);
        wp_enqueue_script('flavor-reciclaje-admin', $base_url . 'reciclaje-admin.js', ['jquery'], $version, true);
    }

    /**
     * AJAX: Registrar depósito
     */
    public function ajax_registrar_deposito() {
        check_ajax_referer('reciclaje_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';

        $usuario_id = get_current_user_id();
        $punto_id = intval($_POST['punto_id'] ?? 0);
        $tipo_material = sanitize_text_field($_POST['tipo_material'] ?? '');
        $cantidad_kg = floatval($_POST['cantidad_kg'] ?? 0);

        if (!$punto_id || !$tipo_material || $cantidad_kg <= 0) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-chat-ia')]);
        }

        $settings = $this->get_settings();
        $puntos_ganados = ceil($cantidad_kg * ($settings['puntos_por_kg'] ?? 10));

        $result = $wpdb->insert(
            $tabla_depositos,
            [
                'usuario_id' => $usuario_id,
                'punto_reciclaje_id' => $punto_id,
                'tipo_material' => $tipo_material,
                'cantidad_kg' => $cantidad_kg,
                'puntos_ganados' => $puntos_ganados,
                'verificado' => 0,
                'fecha_deposito' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%f', '%d', '%d', '%s']
        );

        if ($result) {
            // Notificar
            if (class_exists('Flavor_Notification_Manager')) {
                Flavor_Notification_Manager::get_instance()->send(
                    $usuario_id,
                    'reciclaje_deposito_registrado',
                    [
                        'title' => __('Depósito Registrado', 'flavor-chat-ia'),
                        'message' => sprintf(__('Has registrado %s kg de %s. Ganarás %d puntos al verificar.', 'flavor-chat-ia'), $cantidad_kg, $tipo_material, $puntos_ganados),
                        'icon' => 'dashicons-yes-alt',
                    ]
                );
            }

            // Gamificación
            if (class_exists('Flavor_Gamification')) {
                do_action('flavor_gamification_award_points', $usuario_id, $puntos_ganados, 'reciclaje_deposito', [
                    'deposito_id' => $wpdb->insert_id,
                ]);
            }

            wp_send_json_success([
                'message' => __('Depósito registrado correctamente', 'flavor-chat-ia'),
                'puntos_ganados' => $puntos_ganados,
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al registrar el depósito', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Obtener puntos cercanos
     */
    public function ajax_obtener_puntos() {
        check_ajax_referer('reciclaje_nonce', 'nonce');

        $result = $this->action_puntos_cercanos($_POST);
        wp_send_json($result);
    }

    /**
     * AJAX: Reportar contenedor
     */
    public function ajax_reportar_contenedor() {
        check_ajax_referer('reciclaje_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_contenedores = $wpdb->prefix . 'flavor_reciclaje_contenedores';

        $contenedor_id = intval($_POST['contenedor_id'] ?? 0);
        $problema = sanitize_text_field($_POST['problema'] ?? '');

        if (!$contenedor_id || !$problema) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-chat-ia')]);
        }

        // Incrementar reportes
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_contenedores SET reportes_problema = reportes_problema + 1 WHERE id = %d",
            $contenedor_id
        ));

        // Si hay muchos reportes, cambiar estado
        $reportes = $wpdb->get_var($wpdb->prepare(
            "SELECT reportes_problema FROM $tabla_contenedores WHERE id = %d",
            $contenedor_id
        ));

        if ($reportes >= 3) {
            $wpdb->update(
                $tabla_contenedores,
                ['necesita_vaciado' => 1],
                ['id' => $contenedor_id],
                ['%d'],
                ['%d']
            );
        }

        wp_send_json_success(['message' => __('Reporte enviado correctamente', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Calendario de recogidas
     */
    public function ajax_calendario_recogidas() {
        check_ajax_referer('reciclaje_nonce', 'nonce');

        global $wpdb;
        $tabla_recogidas = $wpdb->prefix . 'flavor_reciclaje_recogidas';

        $fecha_desde = sanitize_text_field($_POST['fecha_desde'] ?? date('Y-m-01'));
        $fecha_hasta = sanitize_text_field($_POST['fecha_hasta'] ?? date('Y-m-t'));

        $recogidas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_recogidas
             WHERE fecha_programada BETWEEN %s AND %s
             AND estado != 'cancelada'
             ORDER BY fecha_programada ASC",
            $fecha_desde,
            $fecha_hasta
        ));

        $eventos = array_map(function($r) {
            return [
                'id' => $r->id,
                'title' => $r->zona . ' - ' . $r->tipo_recogida,
                'start' => $r->fecha_programada,
                'tipo' => $r->tipo_recogida,
                'zona' => $r->zona,
                'estado' => $r->estado,
            ];
        }, $recogidas);

        wp_send_json_success(['eventos' => $eventos]);
    }

    /**
     * AJAX: Mis puntos
     */
    public function ajax_mis_puntos() {
        check_ajax_referer('reciclaje_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $usuario_id = get_current_user_id();

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(puntos_ganados) as total_puntos,
                SUM(cantidad_kg) as total_kg,
                COUNT(*) as total_depositos
             FROM $tabla_depositos
             WHERE usuario_id = %d AND verificado = 1",
            $usuario_id
        ));

        wp_send_json_success([
            'total_puntos' => intval($stats->total_puntos ?? 0),
            'total_kg' => floatval($stats->total_kg ?? 0),
            'total_depositos' => intval($stats->total_depositos ?? 0),
        ]);
    }

    /**
     * AJAX: Canjear puntos
     */
    public function ajax_canjear_puntos() {
        check_ajax_referer('reciclaje_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $recompensa_id = intval($_POST['recompensa_id'] ?? 0);
        $usuario_id = get_current_user_id();

        if (!$recompensa_id) {
            wp_send_json_error(['message' => __('Recompensa no válida', 'flavor-chat-ia')]);
        }

        $puntos_necesarios = intval(get_post_meta($recompensa_id, '_puntos_necesarios', true));

        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';

        $puntos_usuario = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(puntos_ganados) FROM $tabla_depositos WHERE usuario_id = %d AND verificado = 1",
            $usuario_id
        ));

        if ($puntos_usuario < $puntos_necesarios) {
            wp_send_json_error(['message' => __('No tienes suficientes puntos', 'flavor-chat-ia')]);
        }

        // Registrar canje
        update_user_meta($usuario_id, '_reciclaje_puntos_canjeados', intval(get_user_meta($usuario_id, '_reciclaje_puntos_canjeados', true)) + $puntos_necesarios);
        update_user_meta($usuario_id, '_reciclaje_ultimo_canje', current_time('mysql'));

        wp_send_json_success(['message' => __('Puntos canjeados correctamente', 'flavor-chat-ia')]);
    }

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/reciclaje/puntos', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_puntos'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/reciclaje/deposito', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_registrar_deposito'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);

        register_rest_route('flavor/v1', '/reciclaje/calendario', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_calendario'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/reciclaje/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_stats'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);
    }

    /**
     * REST: Get puntos
     */
    public function rest_get_puntos($request) {
        $params = [
            'lat' => $request->get_param('lat'),
            'lng' => $request->get_param('lng'),
            'tipo_material' => $request->get_param('tipo_material'),
        ];

        return rest_ensure_response($this->action_puntos_cercanos($params));
    }

    /**
     * REST: Registrar depósito
     */
    public function rest_registrar_deposito($request) {
        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';

        $usuario_id = get_current_user_id();
        $punto_id = intval($request->get_param('punto_id'));
        $tipo_material = sanitize_text_field($request->get_param('tipo_material'));
        $cantidad_kg = floatval($request->get_param('cantidad_kg'));

        if (!$punto_id || !$tipo_material || $cantidad_kg <= 0) {
            return new WP_Error('datos_invalidos', __('Datos incompletos', 'flavor-chat-ia'), ['status' => 400]);
        }

        $settings = $this->get_settings();
        $puntos_ganados = ceil($cantidad_kg * ($settings['puntos_por_kg'] ?? 10));

        $result = $wpdb->insert(
            $tabla_depositos,
            [
                'usuario_id' => $usuario_id,
                'punto_reciclaje_id' => $punto_id,
                'tipo_material' => $tipo_material,
                'cantidad_kg' => $cantidad_kg,
                'puntos_ganados' => $puntos_ganados,
                'verificado' => 0,
                'fecha_deposito' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%f', '%d', '%d', '%s']
        );

        if ($result) {
            return rest_ensure_response([
                'success' => true,
                'deposito_id' => $wpdb->insert_id,
                'puntos_ganados' => $puntos_ganados,
            ]);
        }

        return new WP_Error('error_bd', __('Error al registrar', 'flavor-chat-ia'), ['status' => 500]);
    }

    /**
     * REST: Get calendario
     */
    public function rest_get_calendario($request) {
        global $wpdb;
        $tabla_recogidas = $wpdb->prefix . 'flavor_reciclaje_recogidas';

        $zona = sanitize_text_field($request->get_param('zona'));
        $where = "estado != 'cancelada'";

        if ($zona) {
            $where .= $wpdb->prepare(" AND zona = %s", $zona);
        }

        $recogidas = $wpdb->get_results("
            SELECT * FROM $tabla_recogidas
            WHERE $where
            ORDER BY fecha_programada ASC
            LIMIT 100
        ");

        return rest_ensure_response(['recogidas' => $recogidas]);
    }

    /**
     * REST: Get stats
     */
    public function rest_get_stats($request) {
        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $usuario_id = get_current_user_id();

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(puntos_ganados) as total_puntos,
                SUM(cantidad_kg) as total_kg,
                COUNT(*) as total_depositos
             FROM $tabla_depositos
             WHERE usuario_id = %d AND verificado = 1",
            $usuario_id
        ));

        return rest_ensure_response([
            'total_puntos' => intval($stats->total_puntos ?? 0),
            'total_kg' => floatval($stats->total_kg ?? 0),
            'total_depositos' => intval($stats->total_depositos ?? 0),
        ]);
    }

    /**
     * WP Cron: Notificar recogidas próximas
     */
    public function notificar_recogidas_proximas() {
        global $wpdb;
        $tabla_recogidas = $wpdb->prefix . 'flavor_reciclaje_recogidas';

        $manana = date('Y-m-d', strtotime('+1 day'));

        $recogidas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_recogidas
             WHERE DATE(fecha_programada) = %s
             AND estado = 'programada'",
            $manana
        ));

        if (!class_exists('Flavor_Notification_Manager')) {
            return;
        }

        $manager = Flavor_Notification_Manager::get_instance();

        foreach ($recogidas as $recogida) {
            // Notificar a usuarios de la zona
            $usuarios = get_users(['meta_key' => '_zona_residencia', 'meta_value' => $recogida->zona]);

            foreach ($usuarios as $usuario) {
                $manager->send(
                    $usuario->ID,
                    'reciclaje_recordatorio_recogida',
                    [
                        'title' => __('Recogida de Reciclaje Mañana', 'flavor-chat-ia'),
                        'message' => sprintf(__('Recogida de %s en %s', 'flavor-chat-ia'), $recogida->tipos_residuos, $recogida->zona),
                        'icon' => 'dashicons-calendar-alt',
                    ]
                );
            }
        }
    }

    /**
     * WP Cron: Verificar estado de contenedores
     */
    public function verificar_estado_contenedores() {
        global $wpdb;
        $tabla_contenedores = $wpdb->prefix . 'flavor_reciclaje_contenedores';

        // Marcar contenedores con alto nivel de llenado
        $wpdb->query("
            UPDATE $tabla_contenedores
            SET necesita_vaciado = 1, estado = 'lleno'
            WHERE nivel_llenado >= 80 AND estado = 'operativo'
        ");

        // Notificar a administradores
        $contenedores_llenos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_contenedores WHERE necesita_vaciado = 1");

        if ($contenedores_llenos > 0 && class_exists('Flavor_Notification_Manager')) {
            $admins = get_users(['role' => 'administrator']);
            $manager = Flavor_Notification_Manager::get_instance();

            foreach ($admins as $admin) {
                $manager->send(
                    $admin->ID,
                    'reciclaje_contenedores_llenos',
                    [
                        'title' => __('Contenedores que Necesitan Vaciado', 'flavor-chat-ia'),
                        'message' => sprintf(__('Hay %d contenedores que necesitan ser vaciados', 'flavor-chat-ia'), $contenedores_llenos),
                        'icon' => 'dashicons-warning',
                        'priority' => 'high',
                    ]
                );
            }
        }
    }

    /**
     * Shortcode: Puntos cercanos
     */
    public function shortcode_puntos_cercanos($atts) {
        $atts = shortcode_atts([
            'altura' => 500,
            'zoom' => 14,
        ], $atts);

        ob_start();
        ?>
        <div class="flavor-reciclaje-mapa" data-altura="<?php echo esc_attr($atts['altura']); ?>" data-zoom="<?php echo esc_attr($atts['zoom']); ?>">
            <div id="mapa-reciclaje" style="height: <?php echo esc_attr($atts['altura']); ?>px;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Calendario
     */
    public function shortcode_calendario($atts) {
        $atts = shortcode_atts([
            'zona' => '',
        ], $atts);

        global $wpdb;
        $tabla_recogidas = $wpdb->prefix . 'flavor_reciclaje_recogidas';

        $where = "estado != 'cancelada'";
        if ($atts['zona']) {
            $where .= $wpdb->prepare(" AND zona = %s", $atts['zona']);
        }

        $recogidas = $wpdb->get_results("SELECT * FROM $tabla_recogidas WHERE $where ORDER BY fecha_programada ASC LIMIT 10");

        ob_start();
        ?>
        <div class="flavor-reciclaje-calendario">
            <h3><?php esc_html_e('Próximas Recogidas', 'flavor-chat-ia'); ?></h3>
            <ul class="recogidas-lista">
                <?php foreach ($recogidas as $recogida): ?>
                <li>
                    <span class="fecha"><?php echo esc_html(date_i18n('d/m/Y', strtotime($recogida->fecha_programada))); ?></span>
                    <span class="zona"><?php echo esc_html($recogida->zona); ?></span>
                    <span class="tipo"><?php echo esc_html($recogida->tipos_residuos); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis puntos
     */
    public function shortcode_mis_puntos($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para ver tus puntos', 'flavor-chat-ia') . '</p>';
        }

        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $usuario_id = get_current_user_id();

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(puntos_ganados) as total_puntos,
                SUM(cantidad_kg) as total_kg,
                COUNT(*) as total_depositos
             FROM $tabla_depositos
             WHERE usuario_id = %d AND verificado = 1",
            $usuario_id
        ));

        ob_start();
        ?>
        <div class="flavor-reciclaje-mis-puntos">
            <div class="puntos-grid">
                <div class="punto-stat">
                    <span class="numero"><?php echo number_format_i18n($stats->total_puntos ?? 0); ?></span>
                    <span class="label"><?php esc_html_e('Puntos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="punto-stat">
                    <span class="numero"><?php echo number_format_i18n($stats->total_kg ?? 0, 2); ?> kg</span>
                    <span class="label"><?php esc_html_e('Reciclado', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="punto-stat">
                    <span class="numero"><?php echo number_format_i18n($stats->total_depositos ?? 0); ?></span>
                    <span class="label"><?php esc_html_e('Depósitos', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Ranking
     */
    public function shortcode_ranking($atts) {
        $atts = shortcode_atts([
            'limite' => 10,
        ], $atts);

        global $wpdb;
        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';

        $ranking = $wpdb->get_results($wpdb->prepare(
            "SELECT u.display_name, SUM(d.puntos_ganados) as total_puntos, SUM(d.cantidad_kg) as total_kg
             FROM $tabla_depositos d
             JOIN {$wpdb->users} u ON d.usuario_id = u.ID
             WHERE d.verificado = 1
             GROUP BY d.usuario_id
             ORDER BY total_puntos DESC
             LIMIT %d",
            intval($atts['limite'])
        ));

        ob_start();
        ?>
        <div class="flavor-reciclaje-ranking">
            <h3><?php esc_html_e('Top Recicladores', 'flavor-chat-ia'); ?></h3>
            <ol class="ranking-lista">
                <?php foreach ($ranking as $index => $usuario): ?>
                <li class="ranking-item <?php echo $index < 3 ? 'top-' . ($index + 1) : ''; ?>">
                    <span class="posicion">#<?php echo $index + 1; ?></span>
                    <span class="usuario"><?php echo esc_html($usuario->display_name); ?></span>
                    <span class="stats">
                        <?php echo number_format_i18n($usuario->total_puntos); ?> pts
                        <small>(<?php echo number_format_i18n($usuario->total_kg, 2); ?> kg)</small>
                    </span>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Guía
     */
    public function shortcode_guia($atts) {
        $guias = get_posts([
            'post_type' => 'guia_reciclaje',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ]);

        ob_start();
        ?>
        <div class="flavor-reciclaje-guia">
            <?php foreach ($guias as $guia): ?>
            <div class="guia-item">
                <h4><?php echo esc_html($guia->post_title); ?></h4>
                <div class="guia-contenido">
                    <?php echo wpautop($guia->post_content); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Recompensas
     */
    public function shortcode_recompensas($atts) {
        $recompensas = get_posts([
            'post_type' => 'recompensa_reciclaje',
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => '_puntos_necesarios',
            'order' => 'ASC',
        ]);

        ob_start();
        ?>
        <div class="flavor-reciclaje-recompensas">
            <div class="recompensas-grid">
                <?php foreach ($recompensas as $recompensa): ?>
                <div class="recompensa-card">
                    <?php if (has_post_thumbnail($recompensa->ID)): ?>
                        <?php echo get_the_post_thumbnail($recompensa->ID, 'medium'); ?>
                    <?php endif; ?>
                    <h4><?php echo esc_html($recompensa->post_title); ?></h4>
                    <p><?php echo esc_html($recompensa->post_excerpt); ?></p>
                    <div class="recompensa-precio">
                        <?php echo number_format_i18n(get_post_meta($recompensa->ID, '_puntos_necesarios', true)); ?> puntos
                    </div>
                    <button class="btn-canjear" data-recompensa="<?php echo esc_attr($recompensa->ID); ?>">
                        <?php esc_html_e('Canjear', 'flavor-chat-ia'); ?>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Componentes web del módulo
     *
     * IA Features futuras:
     * - Reconocimiento de materiales por foto (clasificación automática)
     * - Rutas optimizadas a puntos de reciclaje cercanos
     * - Sugerencias de reciclaje según historial del usuario
     * - Chatbot para dudas de reciclaje
     */
    public function get_web_components() {
        return [
            'hero_reciclaje' => [
                'label' => __('Hero Reciclaje', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-admin-site',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Reciclaje Comunitario', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'default' => __('Recicla, gana puntos y cuida el planeta', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'default' => ''],
                    'mostrar_puntos' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'reciclaje/hero',
            ],
            'puntos_reciclaje' => [
                'label' => __('Puntos de Reciclaje', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-location',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Encuentra tu Punto de Reciclaje', 'flavor-chat-ia')],
                    'altura_mapa' => ['type' => 'number', 'default' => 500],
                    'mostrar_materiales' => ['type' => 'toggle', 'default' => true],
                    'filtrar_por_tipo' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'reciclaje/puntos',
            ],
            'calendario_recogidas' => [
                'label' => __('Calendario de Recogidas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-calendar-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Calendario de Recogidas', 'flavor-chat-ia')],
                    'vista' => ['type' => 'select', 'options' => ['mensual', 'semanal'], 'default' => 'mensual'],
                    'mostrar_zona' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'reciclaje/calendario',
            ],
            'guia_reciclaje' => [
                'label' => __('Guía de Reciclaje', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-book-alt',
                'fields' => [
                    'titulo' => ['type' => 'text', 'default' => __('Qué va en cada Contenedor', 'flavor-chat-ia')],
                    'estilo' => ['type' => 'select', 'options' => ['tarjetas', 'acordeon'], 'default' => 'tarjetas'],
                    'mostrar_colores' => ['type' => 'toggle', 'default' => true],
                ],
                'template' => 'reciclaje/guia',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'reciclaje_puntos_cercanos',
                'description' => 'Encontrar puntos de reciclaje cercanos',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'lat' => ['type' => 'number', 'description' => 'Latitud'],
                        'lng' => ['type' => 'number', 'description' => 'Longitud'],
                        'tipo_material' => ['type' => 'string', 'description' => 'Tipo de material a reciclar'],
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
**Reciclaje Comunitario**

Sistema integral de gestión de reciclaje con recompensas por participar.

**Tipos de reciclaje:**
- Papel y cartón
- Plástico y envases
- Vidrio
- Orgánico
- Electrónico (RAEE)
- Ropa y textil
- Aceite usado
- Pilas y baterías

**Puntos de reciclaje:**
- Puntos limpios municipales
- Contenedores comunitarios
- Centros de acopio especializados
- Recogida móvil

**Sistema de puntos:**
- Gana puntos por reciclar
- Canjea por descuentos locales
- Premios comunitarios
- Rankings de reciclaje

**Calendario de recogidas:**
- Recogidas programadas por zona
- Alertas personalizadas
- Recogida de voluminosos
- Residuos especiales

**Guías de reciclaje:**
- Qué va en cada contenedor
- Cómo preparar los residuos
- Qué NO reciclar
- Alternativas de reutilización
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Dónde reciclo aparatos electrónicos?',
                'respuesta' => 'En los puntos limpios municipales o en recogidas especiales de RAEE.',
            ],
            [
                'pregunta' => '¿Cómo funcionan los puntos?',
                'respuesta' => 'Ganas puntos por cada kg de material reciclado. Pueden canjearse por descuentos en comercios locales.',
            ],
            [
                'pregunta' => '¿Qué hago con el aceite usado?',
                'respuesta' => 'Nunca por el fregadero. Guárdalo en botellas y llévalo a puntos de recogida de aceite.',
            ],
        ];
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
            Flavor_Page_Creator::refresh_module_pages('reciclaje');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('reciclaje');
        if (!$pagina && !get_option('flavor_reciclaje_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['reciclaje']);
            update_option('flavor_reciclaje_pages_created', 1, false);
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
                'title' => __('Reciclaje', 'flavor-chat-ia'),
                'slug' => 'reciclaje',
                'content' => '<h1>' . __('Reciclaje en la Comunidad', 'flavor-chat-ia') . '</h1>
<p>' . __('Encuentra puntos de reciclaje y registra tu contribución', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="reciclaje" action="listar_puntos" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Mapa de Puntos', 'flavor-chat-ia'),
                'slug' => 'mapa',
                'content' => '<h1>' . __('Mapa de Puntos de Reciclaje', 'flavor-chat-ia') . '</h1>

[flavor_reciclaje_mapa]',
                'parent' => 'reciclaje',
            ],
            [
                'title' => __('Registrar Reciclaje', 'flavor-chat-ia'),
                'slug' => 'registrar',
                'content' => '<h1>' . __('Registrar Reciclaje', 'flavor-chat-ia') . '</h1>
<p>' . __('Registra tu aportación y gana puntos', 'flavor-chat-ia') . '</p>

[flavor_module_form module="reciclaje" action="registrar_aportacion"]',
                'parent' => 'reciclaje',
            ],
            [
                'title' => __('Mis Estadísticas', 'flavor-chat-ia'),
                'slug' => 'mis-estadisticas',
                'content' => '<h1>' . __('Mis Estadísticas de Reciclaje', 'flavor-chat-ia') . '</h1>

[flavor_module_dashboard module="reciclaje"]',
                'parent' => 'reciclaje',
            ],
        ];
    }
}
