<?php
/**
 * Dashboard Widgets para Flavor Visual Builder
 *
 * Registra componentes y secciones de dashboard para el Visual Builder.
 * Los widgets solo están disponibles si sus módulos/addons están activos.
 *
 * @package FlavorChatIA
 * @subpackage VisualBuilder
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal de widgets de dashboard para Visual Builder
 */
class Flavor_Dashboard_VB_Widgets {

    /**
     * Instancia singleton
     *
     * @var Flavor_Dashboard_VB_Widgets|null
     */
    private static $instancia = null;

    /**
     * Categorías de widgets
     *
     * @var array
     */
    private $categorias = [];

    /**
     * Widgets registrados
     *
     * @var array
     */
    private $widgets_registrados = [];

    /**
     * Instancia del módulo Themacle
     *
     * @var Flavor_Chat_Themacle_Module|null
     */
    private $themacle_module = null;

    /**
     * Componentes Themacle disponibles
     *
     * @var array
     */
    private $themacle_components = [];

    /**
     * Mapeo de widgets a componentes Themacle
     *
     * @var array
     */
    private $widget_themacle_map = [];

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Dashboard_VB_Widgets
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->definir_categorias();
        $this->init_themacle();
        $this->definir_mapeo_themacle();
        // Inicializar widgets registrados para que estén disponibles en shortcodes
        $this->widgets_registrados = $this->obtener_definicion_widgets();
        $this->init_hooks();
    }

    /**
     * Inicializar integración con Themacle
     */
    private function init_themacle() {
        // Obtener módulo Themacle si está activo
        if (class_exists('Flavor_Chat_Themacle_Module')) {
            $this->themacle_module = new Flavor_Chat_Themacle_Module();
            if ($this->themacle_module->is_active()) {
                $this->themacle_components = $this->themacle_module->get_web_components();
            }
        }
    }

    /**
     * Definir mapeo de widgets a componentes Themacle
     */
    private function definir_mapeo_themacle() {
        $this->widget_themacle_map = [
            // Widgets que usan card_grid
            'proximos-eventos'      => ['component' => 'card_grid', 'columnas' => 3],
            'cursos-activos'        => ['component' => 'card_grid', 'columnas' => 2],
            'talleres'              => ['component' => 'card_grid', 'columnas' => 3],
            'marketplace'           => ['component' => 'card_grid', 'columnas' => 4],
            'tienda-local'          => ['component' => 'card_grid', 'columnas' => 4],
            'recursos-compartidos'  => ['component' => 'card_grid', 'columnas' => 3],
            'biblioteca'            => ['component' => 'card_grid', 'columnas' => 4],
            'bares'                 => ['component' => 'card_grid', 'columnas' => 3],

            // Widgets que usan feature_grid
            'estadisticas-personales' => ['component' => 'feature_grid', 'columnas' => 4],
            'espacios-disponibles'    => ['component' => 'feature_grid', 'columnas' => 3],
            'chat-grupos'             => ['component' => 'feature_grid', 'columnas' => 2],
            'colectivos'              => ['component' => 'feature_grid', 'columnas' => 3],

            // Widgets que usan highlights
            'puntos-nivel'            => ['component' => 'highlights', 'estilo' => 'icons'],
            'reciclaje'               => ['component' => 'highlights', 'estilo' => 'cards'],
            'huertos-urbanos'         => ['component' => 'highlights', 'estilo' => 'cards'],
            'banco-tiempo'            => ['component' => 'highlights', 'estilo' => 'minimal'],

            // Widgets que usan accordion
            'avisos-municipales'      => ['component' => 'accordion'],
            'foros'                   => ['component' => 'accordion'],

            // Widgets que usan text_media
            'perfil-usuario'          => ['component' => 'text_media', 'invertir' => false],
            'socios'                  => ['component' => 'text_media', 'invertir' => true],

            // Widgets que usan map_section
            'mapa-interactivo'        => ['component' => 'map_section'],

            // Widgets que usan related_items
            'red-comunidades'         => ['component' => 'related_items', 'columnas' => 3],
            'red-social'              => ['component' => 'related_items', 'columnas' => 2],
        ];
    }

    /**
     * Verificar si un widget tiene componente Themacle disponible
     *
     * @param string $widget_id ID del widget
     * @return bool
     */
    public function tiene_themacle($widget_id) {
        return !empty($this->themacle_module)
            && !empty($this->widget_themacle_map[$widget_id])
            && !empty($this->themacle_components[$this->widget_themacle_map[$widget_id]['component']]);
    }

    /**
     * Obtener configuración de Themacle para un widget
     *
     * @param string $widget_id ID del widget
     * @return array|null
     */
    public function get_themacle_config($widget_id) {
        if (!$this->tiene_themacle($widget_id)) {
            return null;
        }

        $mapeo = $this->widget_themacle_map[$widget_id];
        $componente = $this->themacle_components[$mapeo['component']];

        return [
            'componente'  => $mapeo['component'],
            'config'      => $mapeo,
            'template'    => $componente['template'] ?? '',
            'fields'      => $componente['fields'] ?? [],
        ];
    }

    /**
     * Definir categorías de widgets
     */
    private function definir_categorias() {
        $this->categorias = [
            'usuario' => [
                'label' => __('Mi Cuenta', 'flavor-chat-ia'),
                'icon'  => 'dashicons-admin-users',
                'orden' => 10,
            ],
            'reservas' => [
                'label' => __('Reservas y Espacios', 'flavor-chat-ia'),
                'icon'  => 'dashicons-calendar-alt',
                'orden' => 20,
            ],
            'eventos' => [
                'label' => __('Eventos y Actividades', 'flavor-chat-ia'),
                'icon'  => 'dashicons-tickets-alt',
                'orden' => 30,
            ],
            'comunicacion' => [
                'label' => __('Comunicación', 'flavor-chat-ia'),
                'icon'  => 'dashicons-megaphone',
                'orden' => 40,
            ],
            'sostenibilidad' => [
                'label' => __('Sostenibilidad', 'flavor-chat-ia'),
                'icon'  => 'dashicons-admin-site-alt3',
                'orden' => 50,
            ],
            'economia' => [
                'label' => __('Economía Local', 'flavor-chat-ia'),
                'icon'  => 'dashicons-cart',
                'orden' => 60,
            ],
            'participacion' => [
                'label' => __('Participación', 'flavor-chat-ia'),
                'icon'  => 'dashicons-groups',
                'orden' => 70,
            ],
            'mapas' => [
                'label' => __('Mapas y Ubicación', 'flavor-chat-ia'),
                'icon'  => 'dashicons-location',
                'orden' => 80,
            ],
            'estadisticas' => [
                'label' => __('Estadísticas', 'flavor-chat-ia'),
                'icon'  => 'dashicons-chart-area',
                'orden' => 90,
            ],
            'red' => [
                'label' => __('Red y Comunidad', 'flavor-chat-ia'),
                'icon'  => 'dashicons-networking',
                'orden' => 100,
            ],
        ];
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Registrar secciones y componentes en el Visual Builder
        add_action('flavor_vb_register_sections', [$this, 'registrar_secciones_dashboard']);
        add_action('flavor_vb_register_components', [$this, 'registrar_componentes_dashboard']);

        // Assets específicos para widgets de dashboard
        add_action('wp_enqueue_scripts', [$this, 'encolar_assets_frontend']);
        add_action('admin_enqueue_scripts', [$this, 'encolar_assets_admin']);

        // AJAX handlers para widgets dinámicos
        add_action('wp_ajax_fvb_dashboard_widget_data', [$this, 'ajax_obtener_datos_widget']);
        add_action('wp_ajax_nopriv_fvb_dashboard_widget_data', [$this, 'ajax_obtener_datos_widget']);

        // Shortcodes
        add_shortcode('flavor_dashboard_unificado', [$this, 'shortcode_dashboard_unificado']);
        add_shortcode('flavor_dashboard_widget', [$this, 'shortcode_dashboard_widget']);
    }

    /**
     * Shortcode: Dashboard Unificado
     *
     * Uso: [flavor_dashboard_unificado columnas="3" categorias="usuario,eventos,mapas"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function shortcode_dashboard_unificado($atts) {
        $atts = shortcode_atts([
            'columnas'      => 3,
            'gap'           => 20,
            'categorias'    => '', // Todas si vacio
            'widgets'       => '', // Todos si vacio
            'estilo'        => 'default',
            'mostrar_stats' => 'true',
            'usar_themacle' => 'true',
            'debug'         => 'false',
        ], $atts);

        // Verificar usuario logueado
        if (!is_user_logged_in()) {
            return $this->renderizar_dashboard_no_logueado();
        }

        // Encolar assets
        $this->encolar_assets_dashboard();

        // Filtrar widgets por categoria o ID
        $widgets_a_mostrar = $this->filtrar_widgets_shortcode($atts);

        // Debug mode
        if ($atts['debug'] === 'true') {
            $debug_info = '<div style="background:#f0f0f0;padding:15px;margin:10px 0;border:1px solid #ccc;">';
            $debug_info .= '<strong>Debug Dashboard:</strong><br>';
            $debug_info .= 'Usuario: ' . get_current_user_id() . '<br>';
            $debug_info .= 'Widgets registrados: ' . count($this->widgets_registrados) . '<br>';
            $debug_info .= 'Widgets a mostrar: ' . count($widgets_a_mostrar) . '<br>';
            $debug_info .= 'IDs: ' . implode(', ', array_keys($widgets_a_mostrar)) . '<br>';
            $debug_info .= '</div>';
            return $debug_info;
        }

        // Datos para renderizado
        $data = [
            'columnas'         => intval($atts['columnas']),
            'gap'              => intval($atts['gap']),
            'widgets_visibles' => array_keys($widgets_a_mostrar),
            'estilo'           => sanitize_key($atts['estilo']),
            'usar_themacle'    => $atts['usar_themacle'] === 'true',
        ];

        ob_start();
        ?>
        <div class="flavor-dashboard-unificado" data-columnas="<?php echo esc_attr($atts['columnas']); ?>">
            <?php if ($atts['mostrar_stats'] === 'true') : ?>
                <?php echo $this->renderizar_seccion_stats(['stats_visibles' => ['reservas', 'eventos', 'puntos', 'mensajes', 'participacion']], 'horizontal'); ?>
            <?php endif; ?>

            <?php echo $this->renderizar_seccion_dashboard_completo($data, 'grid'); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Widget Individual
     *
     * Uso: [flavor_dashboard_widget id="mis-reservas" estilo="card"]
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function shortcode_dashboard_widget($atts) {
        $atts = shortcode_atts([
            'id'           => '',
            'titulo'       => '',
            'estilo'       => 'default',
            'usar_themacle' => 'true',
        ], $atts);

        if (empty($atts['id'])) {
            return '<!-- Widget ID requerido -->';
        }

        $widget_id = sanitize_key($atts['id']);
        $widgets = $this->obtener_definicion_widgets();

        if (!isset($widgets[$widget_id])) {
            return '<!-- Widget no encontrado: ' . esc_html($widget_id) . ' -->';
        }

        $config = $widgets[$widget_id];

        // Verificar modulo activo
        if (!empty($config['modulo']) && !$this->modulo_activo($config['modulo'])) {
            return '<!-- Modulo no activo: ' . esc_html($config['modulo']) . ' -->';
        }

        // Encolar assets
        $this->encolar_assets_dashboard();

        $data = [
            'titulo_personalizado' => $atts['titulo'],
            'estilo'               => $atts['estilo'],
            'mostrar_cabecera'     => true,
            'mostrar_ver_mas'      => true,
            'usar_themacle'        => $atts['usar_themacle'] === 'true',
        ];

        return $this->renderizar_widget($widget_id, $config, $data);
    }

    /**
     * Filtrar widgets para shortcode
     *
     * @param array $atts Atributos del shortcode
     * @return array Widgets filtrados
     */
    private function filtrar_widgets_shortcode($atts) {
        $todos_widgets = $this->obtener_widgets_activos();

        // Filtrar por IDs especificos
        if (!empty($atts['widgets'])) {
            $ids = array_map('trim', explode(',', $atts['widgets']));
            $filtrados = [];
            foreach ($ids as $id) {
                if (isset($todos_widgets[$id])) {
                    $filtrados[$id] = $todos_widgets[$id];
                }
            }
            return $filtrados;
        }

        // Filtrar por categorias
        if (!empty($atts['categorias'])) {
            $categorias = array_map('trim', explode(',', $atts['categorias']));
            $filtrados = [];
            foreach ($todos_widgets as $id => $widget) {
                if (in_array($widget['categoria'], $categorias)) {
                    $filtrados[$id] = $widget;
                }
            }
            return $filtrados;
        }

        return $todos_widgets;
    }

    /**
     * Renderizar dashboard para usuarios no logueados
     *
     * @return string HTML
     */
    private function renderizar_dashboard_no_logueado() {
        ob_start();
        ?>
        <div class="flavor-dashboard-unificado flavor-dashboard--no-login">
            <div class="fvb-widget fvb-widget--login-required">
                <div class="fvb-widget__body">
                    <div class="fvb-widget__login-message">
                        <span class="dashicons dashicons-lock"></span>
                        <h2><?php esc_html_e('Dashboard Personal', 'flavor-chat-ia'); ?></h2>
                        <p><?php esc_html_e('Inicia sesion para acceder a tu dashboard personalizado con todas tus actividades y widgets.', 'flavor-chat-ia'); ?></p>
                        <div class="fvb-widget__login-actions">
                            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="fvb-btn fvb-btn--primary">
                                <?php esc_html_e('Iniciar Sesion', 'flavor-chat-ia'); ?>
                            </a>
                            <?php if (get_option('users_can_register')) : ?>
                                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="fvb-btn fvb-btn--secondary">
                                    <?php esc_html_e('Registrarse', 'flavor-chat-ia'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Encolar assets del dashboard
     */
    private function encolar_assets_dashboard() {
        wp_enqueue_style(
            'fvb-dashboard-widgets',
            FLAVOR_CHAT_IA_URL . 'assets/css/dashboard-vb-widgets.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'fvb-dashboard-widgets',
            FLAVOR_CHAT_IA_URL . 'assets/js/dashboard-vb-widgets.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('fvb-dashboard-widgets', 'fvbDashboardWidgets', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('fvb_dashboard_widgets'),
            'userId'  => get_current_user_id(),
            'i18n'    => [
                'cargando'    => __('Cargando...', 'flavor-chat-ia'),
                'error'       => __('Error al cargar', 'flavor-chat-ia'),
                'actualizado' => __('Actualizado', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Verificar si un módulo está activo
     *
     * @param string $modulo_id ID del módulo
     * @return bool
     */
    public function modulo_activo($modulo_id) {
        // Verificar por addon manager
        if (class_exists('Flavor_Addon_Manager')) {
            if (Flavor_Addon_Manager::is_addon_active($modulo_id)) {
                return true;
            }
        }

        // Verificar por opción directa
        $modulos_activos = get_option('flavor_active_modules', []);
        if (in_array($modulo_id, $modulos_activos)) {
            return true;
        }

        // Verificar si existe la tabla del módulo (indicador de que está instalado)
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_' . str_replace('-', '_', $modulo_id);
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") === $tabla) {
            return true;
        }

        // Por defecto, considerar activo si existe su template
        $template_path = FLAVOR_CHAT_IA_PATH . 'templates/frontend/' . $modulo_id;
        return is_dir($template_path);
    }

    /**
     * Registrar secciones de dashboard
     *
     * @param Flavor_Visual_Builder $builder Instancia del builder
     */
    public function registrar_secciones_dashboard($builder) {
        // Sección principal de Dashboard
        $builder->register_section('dashboard-completo', [
            'label'       => __('Dashboard Completo', 'flavor-chat-ia'),
            'description' => __('Dashboard con todos los widgets disponibles', 'flavor-chat-ia'),
            'icon'        => 'dashicons-dashboard',
            'category'    => 'dashboard',
            'variants'    => ['grid', 'masonry', 'list'],
            'fields'      => $this->get_campos_seccion_dashboard(),
            'render_callback' => [$this, 'renderizar_seccion_dashboard_completo'],
        ]);

        // Sección de estadísticas rápidas
        $builder->register_section('dashboard-stats', [
            'label'       => __('Estadísticas Rápidas', 'flavor-chat-ia'),
            'description' => __('Barra de estadísticas del usuario', 'flavor-chat-ia'),
            'icon'        => 'dashicons-chart-bar',
            'category'    => 'dashboard',
            'variants'    => ['horizontal', 'cards', 'minimal'],
            'fields'      => $this->get_campos_stats(),
            'render_callback' => [$this, 'renderizar_seccion_stats'],
        ]);

        // Sección de actividad reciente
        $builder->register_section('dashboard-actividad', [
            'label'       => __('Actividad Reciente', 'flavor-chat-ia'),
            'description' => __('Timeline de actividad del usuario', 'flavor-chat-ia'),
            'icon'        => 'dashicons-backup',
            'category'    => 'dashboard',
            'variants'    => ['timeline', 'compact', 'detailed'],
            'fields'      => $this->get_campos_actividad(),
            'render_callback' => [$this, 'renderizar_seccion_actividad'],
        ]);

        // Sección de accesos rápidos
        $builder->register_section('dashboard-shortcuts', [
            'label'       => __('Accesos Rápidos', 'flavor-chat-ia'),
            'description' => __('Botones de acceso rápido a funciones', 'flavor-chat-ia'),
            'icon'        => 'dashicons-admin-links',
            'category'    => 'dashboard',
            'variants'    => ['icons', 'buttons', 'cards'],
            'fields'      => $this->get_campos_shortcuts(),
            'render_callback' => [$this, 'renderizar_seccion_shortcuts'],
        ]);
    }

    /**
     * Registrar componentes de dashboard
     *
     * @param Flavor_Visual_Builder $builder Instancia del builder
     */
    public function registrar_componentes_dashboard($builder) {
        $this->widgets_registrados = $this->obtener_definicion_widgets();

        foreach ($this->widgets_registrados as $widget_id => $config) {
            // Verificar si el módulo está activo
            if (!empty($config['modulo']) && !$this->modulo_activo($config['modulo'])) {
                continue; // No registrar si el módulo no está activo
            }

            $builder->register_component('dw-' . $widget_id, [
                'label'       => $config['label'],
                'description' => $config['description'],
                'icon'        => $config['icon'],
                'category'    => 'dashboard-' . $config['categoria'],
                'fields'      => $config['fields'] ?? $this->get_campos_widget_base(),
                'render_callback' => function($data) use ($widget_id, $config) {
                    return $this->renderizar_widget($widget_id, $config, $data);
                },
            ]);
        }
    }

    /**
     * Obtener definición de todos los widgets disponibles
     *
     * @return array
     */
    private function obtener_definicion_widgets() {
        return [
            // =====================================================================
            // CATEGORÍA: USUARIO
            // =====================================================================
            'perfil-usuario' => [
                'label'       => __('Mi Perfil', 'flavor-chat-ia'),
                'description' => __('Avatar e información del usuario', 'flavor-chat-ia'),
                'icon'        => 'dashicons-admin-users',
                'categoria'   => 'usuario',
                'modulo'      => null, // Siempre disponible
                'tamano'      => 'small',
                'fields'      => [
                    'mostrar_avatar'   => ['type' => 'toggle', 'label' => __('Mostrar avatar', 'flavor-chat-ia'), 'default' => true],
                    'mostrar_rol'      => ['type' => 'toggle', 'label' => __('Mostrar rol', 'flavor-chat-ia'), 'default' => true],
                    'mostrar_registro' => ['type' => 'toggle', 'label' => __('Mostrar fecha de registro', 'flavor-chat-ia'), 'default' => false],
                ],
                'render'      => 'renderizar_widget_perfil',
            ],
            'notificaciones' => [
                'label'       => __('Notificaciones', 'flavor-chat-ia'),
                'description' => __('Notificaciones pendientes del usuario', 'flavor-chat-ia'),
                'icon'        => 'dashicons-bell',
                'categoria'   => 'usuario',
                'modulo'      => null,
                'tamano'      => 'medium',
                'fields'      => [
                    'cantidad'      => ['type' => 'number', 'label' => __('Cantidad a mostrar', 'flavor-chat-ia'), 'default' => 5],
                    'solo_sin_leer' => ['type' => 'toggle', 'label' => __('Solo sin leer', 'flavor-chat-ia'), 'default' => true],
                ],
                'render'      => 'renderizar_widget_notificaciones',
            ],
            'puntos-nivel' => [
                'label'       => __('Puntos y Nivel', 'flavor-chat-ia'),
                'description' => __('Puntos acumulados y nivel del usuario', 'flavor-chat-ia'),
                'icon'        => 'dashicons-star-filled',
                'categoria'   => 'usuario',
                'modulo'      => 'gamificacion',
                'tamano'      => 'small',
                'render'      => 'renderizar_widget_puntos',
            ],

            // =====================================================================
            // CATEGORÍA: RESERVAS Y ESPACIOS
            // =====================================================================
            'mis-reservas' => [
                'label'       => __('Mis Reservas', 'flavor-chat-ia'),
                'description' => __('Listado de reservas del usuario', 'flavor-chat-ia'),
                'icon'        => 'dashicons-calendar-alt',
                'categoria'   => 'reservas',
                'modulo'      => 'espacios-comunes',
                'tamano'      => 'medium',
                'fields'      => [
                    'cantidad'        => ['type' => 'number', 'label' => __('Cantidad', 'flavor-chat-ia'), 'default' => 5],
                    'solo_proximas'   => ['type' => 'toggle', 'label' => __('Solo próximas', 'flavor-chat-ia'), 'default' => true],
                    'mostrar_estado'  => ['type' => 'toggle', 'label' => __('Mostrar estado', 'flavor-chat-ia'), 'default' => true],
                ],
                'render'      => 'renderizar_widget_reservas',
            ],
            'espacios-disponibles' => [
                'label'       => __('Espacios Disponibles', 'flavor-chat-ia'),
                'description' => __('Espacios comunes disponibles hoy', 'flavor-chat-ia'),
                'icon'        => 'dashicons-building',
                'categoria'   => 'reservas',
                'modulo'      => 'espacios-comunes',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_espacios',
            ],
            'bicicletas-disponibles' => [
                'label'       => __('Bicicletas', 'flavor-chat-ia'),
                'description' => __('Bicicletas compartidas disponibles', 'flavor-chat-ia'),
                'icon'        => 'dashicons-image-rotate',
                'categoria'   => 'reservas',
                'modulo'      => 'bicicletas-compartidas',
                'tamano'      => 'small',
                'render'      => 'renderizar_widget_bicicletas',
            ],
            'parkings' => [
                'label'       => __('Parkings', 'flavor-chat-ia'),
                'description' => __('Disponibilidad de parkings', 'flavor-chat-ia'),
                'icon'        => 'dashicons-car',
                'categoria'   => 'reservas',
                'modulo'      => 'parkings',
                'tamano'      => 'small',
                'render'      => 'renderizar_widget_parkings',
            ],
            'carpooling' => [
                'label'       => __('Carpooling', 'flavor-chat-ia'),
                'description' => __('Viajes compartidos disponibles', 'flavor-chat-ia'),
                'icon'        => 'dashicons-car',
                'categoria'   => 'reservas',
                'modulo'      => 'carpooling',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_carpooling',
            ],

            // =====================================================================
            // CATEGORÍA: EVENTOS Y ACTIVIDADES
            // =====================================================================
            'calendario-eventos' => [
                'label'       => __('Calendario de Eventos', 'flavor-chat-ia'),
                'description' => __('Calendario con eventos próximos', 'flavor-chat-ia'),
                'icon'        => 'dashicons-calendar',
                'categoria'   => 'eventos',
                'modulo'      => 'eventos',
                'tamano'      => 'large',
                'fields'      => [
                    'vista'           => ['type' => 'select', 'label' => __('Vista', 'flavor-chat-ia'), 'options' => ['mes' => 'Mes', 'semana' => 'Semana', 'agenda' => 'Agenda'], 'default' => 'mes'],
                    'categorias'      => ['type' => 'multiselect', 'label' => __('Categorías', 'flavor-chat-ia')],
                    'mostrar_inscritos' => ['type' => 'toggle', 'label' => __('Destacar inscritos', 'flavor-chat-ia'), 'default' => true],
                ],
                'render'      => 'renderizar_widget_calendario',
            ],
            'proximos-eventos' => [
                'label'       => __('Próximos Eventos', 'flavor-chat-ia'),
                'description' => __('Lista de próximos eventos', 'flavor-chat-ia'),
                'icon'        => 'dashicons-tickets-alt',
                'categoria'   => 'eventos',
                'modulo'      => 'eventos',
                'tamano'      => 'medium',
                'fields'      => [
                    'cantidad'   => ['type' => 'number', 'label' => __('Cantidad', 'flavor-chat-ia'), 'default' => 5],
                    'dias'       => ['type' => 'number', 'label' => __('Próximos días', 'flavor-chat-ia'), 'default' => 30],
                ],
                'render'      => 'renderizar_widget_proximos_eventos',
            ],
            'mis-inscripciones' => [
                'label'       => __('Mis Inscripciones', 'flavor-chat-ia'),
                'description' => __('Eventos donde estoy inscrito', 'flavor-chat-ia'),
                'icon'        => 'dashicons-yes-alt',
                'categoria'   => 'eventos',
                'modulo'      => 'eventos',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_inscripciones',
            ],
            'cursos-activos' => [
                'label'       => __('Mis Cursos', 'flavor-chat-ia'),
                'description' => __('Cursos en los que participo', 'flavor-chat-ia'),
                'icon'        => 'dashicons-welcome-learn-more',
                'categoria'   => 'eventos',
                'modulo'      => 'cursos',
                'tamano'      => 'medium',
                'fields'      => [
                    'mostrar_progreso' => ['type' => 'toggle', 'label' => __('Mostrar progreso', 'flavor-chat-ia'), 'default' => true],
                ],
                'render'      => 'renderizar_widget_cursos',
            ],
            'talleres' => [
                'label'       => __('Talleres', 'flavor-chat-ia'),
                'description' => __('Talleres disponibles', 'flavor-chat-ia'),
                'icon'        => 'dashicons-hammer',
                'categoria'   => 'eventos',
                'modulo'      => 'talleres',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_talleres',
            ],

            // =====================================================================
            // CATEGORÍA: COMUNICACIÓN
            // =====================================================================
            'avisos-municipales' => [
                'label'       => __('Avisos Municipales', 'flavor-chat-ia'),
                'description' => __('Últimos avisos del ayuntamiento', 'flavor-chat-ia'),
                'icon'        => 'dashicons-megaphone',
                'categoria'   => 'comunicacion',
                'modulo'      => 'avisos-municipales',
                'tamano'      => 'medium',
                'fields'      => [
                    'cantidad'       => ['type' => 'number', 'label' => __('Cantidad', 'flavor-chat-ia'), 'default' => 5],
                    'solo_urgentes'  => ['type' => 'toggle', 'label' => __('Solo urgentes', 'flavor-chat-ia'), 'default' => false],
                ],
                'render'      => 'renderizar_widget_avisos',
            ],
            'chat-grupos' => [
                'label'       => __('Mis Grupos de Chat', 'flavor-chat-ia'),
                'description' => __('Grupos de chat activos', 'flavor-chat-ia'),
                'icon'        => 'dashicons-format-chat',
                'categoria'   => 'comunicacion',
                'modulo'      => 'chat-grupos',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_chat_grupos',
            ],
            'mensajes-internos' => [
                'label'       => __('Mensajes', 'flavor-chat-ia'),
                'description' => __('Mensajes privados', 'flavor-chat-ia'),
                'icon'        => 'dashicons-email-alt',
                'categoria'   => 'comunicacion',
                'modulo'      => 'chat-interno',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_mensajes',
            ],
            'foros' => [
                'label'       => __('Foros', 'flavor-chat-ia'),
                'description' => __('Últimos temas en foros', 'flavor-chat-ia'),
                'icon'        => 'dashicons-format-status',
                'categoria'   => 'comunicacion',
                'modulo'      => 'foros',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_foros',
            ],
            'podcast' => [
                'label'       => __('Podcast', 'flavor-chat-ia'),
                'description' => __('Últimos episodios de podcast', 'flavor-chat-ia'),
                'icon'        => 'dashicons-microphone',
                'categoria'   => 'comunicacion',
                'modulo'      => 'podcast',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_podcast',
            ],
            'radio' => [
                'label'       => __('Radio', 'flavor-chat-ia'),
                'description' => __('Reproductor de radio en vivo', 'flavor-chat-ia'),
                'icon'        => 'dashicons-controls-volumeon',
                'categoria'   => 'comunicacion',
                'modulo'      => 'radio',
                'tamano'      => 'small',
                'render'      => 'renderizar_widget_radio',
            ],
            'multimedia' => [
                'label'       => __('Multimedia', 'flavor-chat-ia'),
                'description' => __('Galería de fotos y videos', 'flavor-chat-ia'),
                'icon'        => 'dashicons-format-gallery',
                'categoria'   => 'comunicacion',
                'modulo'      => 'multimedia',
                'tamano'      => 'large',
                'render'      => 'renderizar_widget_multimedia',
            ],

            // =====================================================================
            // CATEGORÍA: SOSTENIBILIDAD
            // =====================================================================
            'huertos-urbanos' => [
                'label'       => __('Mi Huerto', 'flavor-chat-ia'),
                'description' => __('Estado de mi parcela de huerto', 'flavor-chat-ia'),
                'icon'        => 'dashicons-carrot',
                'categoria'   => 'sostenibilidad',
                'modulo'      => 'huertos-urbanos',
                'tamano'      => 'medium',
                'fields'      => [
                    'mostrar_ciclo'   => ['type' => 'toggle', 'label' => __('Mostrar ciclo actual', 'flavor-chat-ia'), 'default' => true],
                    'mostrar_tareas'  => ['type' => 'toggle', 'label' => __('Mostrar tareas pendientes', 'flavor-chat-ia'), 'default' => true],
                ],
                'render'      => 'renderizar_widget_huertos',
            ],
            'reciclaje' => [
                'label'       => __('Reciclaje', 'flavor-chat-ia'),
                'description' => __('Mis estadísticas de reciclaje', 'flavor-chat-ia'),
                'icon'        => 'dashicons-image-rotate-right',
                'categoria'   => 'sostenibilidad',
                'modulo'      => 'reciclaje',
                'tamano'      => 'small',
                'render'      => 'renderizar_widget_reciclaje',
            ],
            'compostaje' => [
                'label'       => __('Compostaje', 'flavor-chat-ia'),
                'description' => __('Mi compostaje comunitario', 'flavor-chat-ia'),
                'icon'        => 'dashicons-admin-site-alt3',
                'categoria'   => 'sostenibilidad',
                'modulo'      => 'compostaje',
                'tamano'      => 'small',
                'render'      => 'renderizar_widget_compostaje',
            ],

            // =====================================================================
            // CATEGORÍA: ECONOMÍA LOCAL
            // =====================================================================
            'marketplace' => [
                'label'       => __('Marketplace', 'flavor-chat-ia'),
                'description' => __('Mis anuncios y compras', 'flavor-chat-ia'),
                'icon'        => 'dashicons-store',
                'categoria'   => 'economia',
                'modulo'      => 'marketplace',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_marketplace',
            ],
            'tienda-local' => [
                'label'       => __('Tienda Local', 'flavor-chat-ia'),
                'description' => __('Productos de tiendas locales', 'flavor-chat-ia'),
                'icon'        => 'dashicons-cart',
                'categoria'   => 'economia',
                'modulo'      => 'tienda-local',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_tienda',
            ],
            'mis-pedidos' => [
                'label'       => __('Mis Pedidos', 'flavor-chat-ia'),
                'description' => __('Estado de mis pedidos', 'flavor-chat-ia'),
                'icon'        => 'dashicons-products',
                'categoria'   => 'economia',
                'modulo'      => 'tienda-local',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_pedidos',
            ],
            'grupos-consumo' => [
                'label'       => __('Grupos de Consumo', 'flavor-chat-ia'),
                'description' => __('Mis grupos de consumo', 'flavor-chat-ia'),
                'icon'        => 'dashicons-groups',
                'categoria'   => 'economia',
                'modulo'      => 'grupos-consumo',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_grupos_consumo',
            ],
            'banco-tiempo' => [
                'label'       => __('Banco del Tiempo', 'flavor-chat-ia'),
                'description' => __('Mi saldo en el banco del tiempo', 'flavor-chat-ia'),
                'icon'        => 'dashicons-clock',
                'categoria'   => 'economia',
                'modulo'      => 'banco-tiempo',
                'tamano'      => 'small',
                'render'      => 'renderizar_widget_banco_tiempo',
            ],
            'facturas' => [
                'label'       => __('Mis Facturas', 'flavor-chat-ia'),
                'description' => __('Historial de facturas', 'flavor-chat-ia'),
                'icon'        => 'dashicons-media-text',
                'categoria'   => 'economia',
                'modulo'      => 'facturas',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_facturas',
            ],

            // =====================================================================
            // CATEGORÍA: PARTICIPACIÓN
            // =====================================================================
            'participacion' => [
                'label'       => __('Participación Ciudadana', 'flavor-chat-ia'),
                'description' => __('Procesos participativos activos', 'flavor-chat-ia'),
                'icon'        => 'dashicons-megaphone',
                'categoria'   => 'participacion',
                'modulo'      => 'participacion',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_participacion',
            ],
            'presupuestos-participativos' => [
                'label'       => __('Presupuestos Participativos', 'flavor-chat-ia'),
                'description' => __('Votaciones de presupuestos', 'flavor-chat-ia'),
                'icon'        => 'dashicons-money-alt',
                'categoria'   => 'participacion',
                'modulo'      => 'presupuestos-participativos',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_presupuestos',
            ],
            'incidencias' => [
                'label'       => __('Mis Incidencias', 'flavor-chat-ia'),
                'description' => __('Incidencias reportadas', 'flavor-chat-ia'),
                'icon'        => 'dashicons-warning',
                'categoria'   => 'participacion',
                'modulo'      => 'incidencias',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_incidencias',
            ],
            'tramites' => [
                'label'       => __('Mis Trámites', 'flavor-chat-ia'),
                'description' => __('Estado de trámites', 'flavor-chat-ia'),
                'icon'        => 'dashicons-clipboard',
                'categoria'   => 'participacion',
                'modulo'      => 'tramites',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_tramites',
            ],
            'ayuda-vecinal' => [
                'label'       => __('Ayuda Vecinal', 'flavor-chat-ia'),
                'description' => __('Solicitudes de ayuda entre vecinos', 'flavor-chat-ia'),
                'icon'        => 'dashicons-heart',
                'categoria'   => 'participacion',
                'modulo'      => 'ayuda-vecinal',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_ayuda_vecinal',
            ],
            'colectivos' => [
                'label'       => __('Mis Colectivos', 'flavor-chat-ia'),
                'description' => __('Colectivos a los que pertenezco', 'flavor-chat-ia'),
                'icon'        => 'dashicons-groups',
                'categoria'   => 'participacion',
                'modulo'      => 'colectivos',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_colectivos',
            ],
            'socios' => [
                'label'       => __('Estado de Socio', 'flavor-chat-ia'),
                'description' => __('Mi membresía como socio', 'flavor-chat-ia'),
                'icon'        => 'dashicons-id-alt',
                'categoria'   => 'participacion',
                'modulo'      => 'socios',
                'tamano'      => 'small',
                'render'      => 'renderizar_widget_socios',
            ],

            // =====================================================================
            // CATEGORÍA: MAPAS
            // =====================================================================
            'mapa-interactivo' => [
                'label'       => __('Mapa Interactivo', 'flavor-chat-ia'),
                'description' => __('Mapa con puntos de interés', 'flavor-chat-ia'),
                'icon'        => 'dashicons-location-alt',
                'categoria'   => 'mapas',
                'modulo'      => null,
                'tamano'      => 'large',
                'fields'      => [
                    'altura'     => ['type' => 'number', 'label' => __('Altura (px)', 'flavor-chat-ia'), 'default' => 400],
                    'capas'      => ['type' => 'multiselect', 'label' => __('Capas visibles', 'flavor-chat-ia'), 'options' => [
                        'bicicletas' => __('Bicicletas', 'flavor-chat-ia'),
                        'parkings'   => __('Parkings', 'flavor-chat-ia'),
                        'huertos'    => __('Huertos', 'flavor-chat-ia'),
                        'reciclaje'  => __('Puntos de reciclaje', 'flavor-chat-ia'),
                        'espacios'   => __('Espacios comunes', 'flavor-chat-ia'),
                        'eventos'    => __('Eventos', 'flavor-chat-ia'),
                    ]],
                    'centrar_usuario' => ['type' => 'toggle', 'label' => __('Centrar en usuario', 'flavor-chat-ia'), 'default' => true],
                ],
                'render'      => 'renderizar_widget_mapa',
            ],

            // =====================================================================
            // CATEGORÍA: ESTADÍSTICAS
            // =====================================================================
            'estadisticas-personales' => [
                'label'       => __('Mis Estadísticas', 'flavor-chat-ia'),
                'description' => __('Resumen de mi actividad', 'flavor-chat-ia'),
                'icon'        => 'dashicons-chart-area',
                'categoria'   => 'estadisticas',
                'modulo'      => null,
                'tamano'      => 'large',
                'fields'      => [
                    'periodo' => ['type' => 'select', 'label' => __('Período', 'flavor-chat-ia'), 'options' => [
                        '7d'  => __('Últimos 7 días', 'flavor-chat-ia'),
                        '30d' => __('Últimos 30 días', 'flavor-chat-ia'),
                        '90d' => __('Últimos 90 días', 'flavor-chat-ia'),
                    ], 'default' => '30d'],
                ],
                'render'      => 'renderizar_widget_estadisticas',
            ],
            'grafico-actividad' => [
                'label'       => __('Gráfico de Actividad', 'flavor-chat-ia'),
                'description' => __('Gráfico de mi actividad semanal', 'flavor-chat-ia'),
                'icon'        => 'dashicons-chart-bar',
                'categoria'   => 'estadisticas',
                'modulo'      => null,
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_grafico_actividad',
            ],

            // =====================================================================
            // CATEGORÍA: RED Y COMUNIDAD
            // =====================================================================
            'red-comunidades' => [
                'label'       => __('Red de Comunidades', 'flavor-chat-ia'),
                'description' => __('Comunidades conectadas a la red', 'flavor-chat-ia'),
                'icon'        => 'dashicons-networking',
                'categoria'   => 'red',
                'modulo'      => 'comunidades',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_red',
            ],
            'recursos-compartidos' => [
                'label'       => __('Recursos Compartidos', 'flavor-chat-ia'),
                'description' => __('Recursos de otras comunidades', 'flavor-chat-ia'),
                'icon'        => 'dashicons-share-alt2',
                'categoria'   => 'red',
                'modulo'      => 'comunidades',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_recursos_compartidos',
            ],
            'red-social' => [
                'label'       => __('Red Social', 'flavor-chat-ia'),
                'description' => __('Actividad de mis contactos', 'flavor-chat-ia'),
                'icon'        => 'dashicons-share',
                'categoria'   => 'red',
                'modulo'      => 'red-social',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_red_social',
            ],

            // =====================================================================
            // WIDGETS ESPECIALES
            // =====================================================================
            'fichaje' => [
                'label'       => __('Fichaje', 'flavor-chat-ia'),
                'description' => __('Control de fichaje de empleados', 'flavor-chat-ia'),
                'icon'        => 'dashicons-clock',
                'categoria'   => 'usuario',
                'modulo'      => 'fichaje-empleados',
                'tamano'      => 'small',
                'render'      => 'renderizar_widget_fichaje',
            ],
            'biblioteca' => [
                'label'       => __('Biblioteca', 'flavor-chat-ia'),
                'description' => __('Mis préstamos de biblioteca', 'flavor-chat-ia'),
                'icon'        => 'dashicons-book',
                'categoria'   => 'comunicacion',
                'modulo'      => 'biblioteca',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_biblioteca',
            ],
            'bares' => [
                'label'       => __('Bares y Restaurantes', 'flavor-chat-ia'),
                'description' => __('Bares cercanos y reservas', 'flavor-chat-ia'),
                'icon'        => 'dashicons-food',
                'categoria'   => 'economia',
                'modulo'      => 'bares',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_bares',
            ],
            'transparencia' => [
                'label'       => __('Transparencia', 'flavor-chat-ia'),
                'description' => __('Datos de transparencia municipal', 'flavor-chat-ia'),
                'icon'        => 'dashicons-visibility',
                'categoria'   => 'participacion',
                'modulo'      => 'transparencia',
                'tamano'      => 'medium',
                'render'      => 'renderizar_widget_transparencia',
            ],
        ];
    }

    /**
     * Obtener campos base para widgets
     *
     * @return array
     */
    private function get_campos_widget_base() {
        return [
            'titulo_personalizado' => [
                'type'    => 'text',
                'label'   => __('Título personalizado', 'flavor-chat-ia'),
                'default' => '',
            ],
            'mostrar_cabecera' => [
                'type'    => 'toggle',
                'label'   => __('Mostrar cabecera', 'flavor-chat-ia'),
                'default' => true,
            ],
            'mostrar_ver_mas' => [
                'type'    => 'toggle',
                'label'   => __('Mostrar enlace "Ver más"', 'flavor-chat-ia'),
                'default' => true,
            ],
            'estilo' => [
                'type'    => 'select',
                'label'   => __('Estilo', 'flavor-chat-ia'),
                'options' => [
                    'default' => __('Por defecto', 'flavor-chat-ia'),
                    'card'    => __('Tarjeta', 'flavor-chat-ia'),
                    'minimal' => __('Minimal', 'flavor-chat-ia'),
                    'glass'   => __('Glassmorphism', 'flavor-chat-ia'),
                ],
                'default' => 'default',
            ],
        ];
    }

    /**
     * Obtener campos para sección dashboard completo
     *
     * @return array
     */
    private function get_campos_seccion_dashboard() {
        return [
            'columnas' => [
                'type'    => 'select',
                'label'   => __('Columnas', 'flavor-chat-ia'),
                'options' => [
                    '1' => '1 columna',
                    '2' => '2 columnas',
                    '3' => '3 columnas',
                    '4' => '4 columnas',
                ],
                'default' => '3',
            ],
            'gap' => [
                'type'    => 'number',
                'label'   => __('Espaciado (px)', 'flavor-chat-ia'),
                'default' => 20,
            ],
            'widgets_visibles' => [
                'type'    => 'multiselect',
                'label'   => __('Widgets a mostrar', 'flavor-chat-ia'),
                'options' => [], // Se llena dinámicamente
            ],
            'ordenar_por' => [
                'type'    => 'select',
                'label'   => __('Ordenar por', 'flavor-chat-ia'),
                'options' => [
                    'default'  => __('Por defecto', 'flavor-chat-ia'),
                    'nombre'   => __('Nombre', 'flavor-chat-ia'),
                    'categoria'=> __('Categoría', 'flavor-chat-ia'),
                ],
                'default' => 'default',
            ],
        ];
    }

    /**
     * Obtener campos para sección de estadísticas
     *
     * @return array
     */
    private function get_campos_stats() {
        return [
            'stats_visibles' => [
                'type'    => 'multiselect',
                'label'   => __('Estadísticas a mostrar', 'flavor-chat-ia'),
                'options' => [
                    'reservas'       => __('Reservas', 'flavor-chat-ia'),
                    'eventos'        => __('Eventos', 'flavor-chat-ia'),
                    'puntos'         => __('Puntos', 'flavor-chat-ia'),
                    'mensajes'       => __('Mensajes', 'flavor-chat-ia'),
                    'participacion'  => __('Participación', 'flavor-chat-ia'),
                ],
            ],
            'mostrar_iconos' => [
                'type'    => 'toggle',
                'label'   => __('Mostrar iconos', 'flavor-chat-ia'),
                'default' => true,
            ],
            'mostrar_tendencia' => [
                'type'    => 'toggle',
                'label'   => __('Mostrar tendencia', 'flavor-chat-ia'),
                'default' => true,
            ],
        ];
    }

    /**
     * Obtener campos para sección de actividad
     *
     * @return array
     */
    private function get_campos_actividad() {
        return [
            'cantidad' => [
                'type'    => 'number',
                'label'   => __('Cantidad de items', 'flavor-chat-ia'),
                'default' => 10,
            ],
            'mostrar_iconos' => [
                'type'    => 'toggle',
                'label'   => __('Mostrar iconos', 'flavor-chat-ia'),
                'default' => true,
            ],
            'agrupar_por_dia' => [
                'type'    => 'toggle',
                'label'   => __('Agrupar por día', 'flavor-chat-ia'),
                'default' => false,
            ],
        ];
    }

    /**
     * Obtener campos para sección de shortcuts
     *
     * @return array
     */
    private function get_campos_shortcuts() {
        return [
            'shortcuts_visibles' => [
                'type'    => 'multiselect',
                'label'   => __('Accesos a mostrar', 'flavor-chat-ia'),
                'options' => [
                    'nueva-reserva'    => __('Nueva Reserva', 'flavor-chat-ia'),
                    'nueva-incidencia' => __('Nueva Incidencia', 'flavor-chat-ia'),
                    'nuevo-mensaje'    => __('Nuevo Mensaje', 'flavor-chat-ia'),
                    'mi-perfil'        => __('Mi Perfil', 'flavor-chat-ia'),
                    'configuracion'    => __('Configuración', 'flavor-chat-ia'),
                ],
            ],
            'mostrar_texto' => [
                'type'    => 'toggle',
                'label'   => __('Mostrar texto', 'flavor-chat-ia'),
                'default' => true,
            ],
        ];
    }

    /**
     * Renderizar widget genérico
     *
     * @param string $widget_id ID del widget
     * @param array  $config    Configuración del widget
     * @param array  $data      Datos del usuario
     * @return string HTML
     */
    public function renderizar_widget($widget_id, $config, $data) {
        // Verificar que el usuario está logueado para widgets que lo requieren
        if (!is_user_logged_in() && empty($config['publico'])) {
            return $this->renderizar_widget_login_requerido($config['label']);
        }

        $id_usuario = get_current_user_id();

        // Intentar usar componente Themacle si está disponible
        $usar_themacle = $data['usar_themacle'] ?? true;
        if ($usar_themacle && $this->tiene_themacle($widget_id)) {
            return $this->renderizar_widget_themacle($widget_id, $config, $data, $id_usuario);
        }

        // Llamar al método de render específico si existe
        if (!empty($config['render']) && method_exists($this, $config['render'])) {
            ob_start();
            $this->{$config['render']}($widget_id, $config, $data, $id_usuario);
            return ob_get_clean();
        }

        // Render por defecto
        return $this->renderizar_widget_default($widget_id, $config, $data, $id_usuario);
    }

    /**
     * Renderizar widget usando componente Themacle
     *
     * @param string $widget_id   ID del widget
     * @param array  $config      Configuración
     * @param array  $data        Datos del builder
     * @param int    $id_usuario  ID del usuario
     * @return string HTML
     */
    private function renderizar_widget_themacle($widget_id, $config, $data, $id_usuario) {
        $themacle_config = $this->get_themacle_config($widget_id);
        if (!$themacle_config) {
            return $this->renderizar_widget_default($widget_id, $config, $data, $id_usuario);
        }

        $componente = $themacle_config['componente'];
        $template = $themacle_config['template'];

        // Obtener datos del widget para pasar al template
        $datos_widget = $this->obtener_datos_widget_para_themacle($widget_id, $config, $id_usuario);

        // Preparar variables para el template
        $template_vars = $this->preparar_vars_themacle($widget_id, $config, $data, $themacle_config, $datos_widget);

        // Buscar y cargar el template
        $template_path = $this->buscar_template_themacle($template);

        if ($template_path && file_exists($template_path)) {
            return $this->cargar_template_themacle($template_path, $template_vars, $widget_id, $config);
        }

        // Fallback a renderizado por defecto
        return $this->renderizar_widget_default($widget_id, $config, $data, $id_usuario);
    }

    /**
     * Obtener datos del widget formateados para Themacle
     *
     * @param string $widget_id   ID del widget
     * @param array  $config      Configuración
     * @param int    $id_usuario  ID del usuario
     * @return array
     */
    private function obtener_datos_widget_para_themacle($widget_id, $config, $id_usuario) {
        // Aplicar filtro para que los módulos puedan proporcionar sus datos
        $datos = apply_filters("fvb_widget_themacle_data_{$widget_id}", [], $id_usuario, $config);

        if (empty($datos)) {
            // Datos de ejemplo / placeholder
            $datos = [
                'items' => [],
                'titulo' => $config['label'] ?? '',
                'descripcion' => $config['description'] ?? '',
            ];
        }

        return $datos;
    }

    /**
     * Preparar variables para el template Themacle
     *
     * @param string $widget_id       ID del widget
     * @param array  $config          Configuración del widget
     * @param array  $data            Datos del builder
     * @param array  $themacle_config Configuración de Themacle
     * @param array  $datos_widget    Datos del widget
     * @return array
     */
    private function preparar_vars_themacle($widget_id, $config, $data, $themacle_config, $datos_widget) {
        $componente = $themacle_config['componente'];
        $mapeo = $themacle_config['config'];

        // Variables base
        $vars = [
            'titulo'      => ($data['titulo_personalizado'] ?? '') ?: ($datos_widget['titulo'] ?? $config['label']),
            'descripcion' => $datos_widget['descripcion'] ?? '',
            'items'       => $datos_widget['items'] ?? [],
            'widget_id'   => $widget_id,
            'clase_widget' => 'fvb-widget fvb-widget--' . esc_attr($widget_id) . ' fvb-widget--themacle',
        ];

        // Añadir variables específicas según el componente
        switch ($componente) {
            case 'card_grid':
            case 'feature_grid':
            case 'related_items':
                $vars['columnas'] = $mapeo['columnas'] ?? 3;
                $vars['estilo_card'] = $data['estilo'] ?? 'shadow';
                break;

            case 'highlights':
                $vars['estilo'] = $mapeo['estilo'] ?? 'cards';
                break;

            case 'text_media':
                $vars['invertir'] = $mapeo['invertir'] ?? false;
                $vars['imagen'] = $datos_widget['imagen'] ?? '';
                break;

            case 'accordion':
                // items ya está configurado
                break;

            case 'map_section':
                $vars['altura'] = $data['altura'] ?? 400;
                $vars['capas'] = $data['capas'] ?? [];
                break;
        }

        return $vars;
    }

    /**
     * Buscar template de Themacle
     *
     * @param string $template Nombre del template
     * @return string|null Ruta del template
     */
    private function buscar_template_themacle($template) {
        if (empty($template)) {
            return null;
        }

        // Rutas donde buscar el template
        $rutas = [
            get_stylesheet_directory() . '/flavor-templates/components/' . $template . '.php',
            get_template_directory() . '/flavor-templates/components/' . $template . '.php',
            FLAVOR_CHAT_IA_PATH . 'templates/components/' . $template . '.php',
        ];

        foreach ($rutas as $ruta) {
            if (file_exists($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    /**
     * Cargar template de Themacle
     *
     * @param string $template_path Ruta del template
     * @param array  $vars          Variables para el template
     * @param string $widget_id     ID del widget
     * @param array  $config        Configuración del widget
     * @return string HTML
     */
    private function cargar_template_themacle($template_path, $vars, $widget_id, $config) {
        // Extraer variables para que estén disponibles en el template
        extract($vars);

        ob_start();
        ?>
        <div class="<?php echo esc_attr($clase_widget); ?>" data-widget-id="<?php echo esc_attr($widget_id); ?>">
            <?php
            // Cargar template de Themacle
            include $template_path;
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar widget cuando se requiere login
     *
     * @param string $titulo Título del widget
     * @return string HTML
     */
    private function renderizar_widget_login_requerido($titulo) {
        ob_start();
        ?>
        <div class="fvb-widget fvb-widget--login-required">
            <div class="fvb-widget__header">
                <h3 class="fvb-widget__title"><?php echo esc_html($titulo); ?></h3>
            </div>
            <div class="fvb-widget__body">
                <div class="fvb-widget__login-message">
                    <span class="dashicons dashicons-lock"></span>
                    <p><?php esc_html_e('Inicia sesión para ver este contenido', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="fvb-btn fvb-btn--primary">
                        <?php esc_html_e('Iniciar Sesión', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar widget por defecto
     *
     * @param string $widget_id   ID del widget
     * @param array  $config      Configuración
     * @param array  $data        Datos del builder
     * @param int    $id_usuario  ID del usuario
     * @return string HTML
     */
    private function renderizar_widget_default($widget_id, $config, $data, $id_usuario) {
        $mostrar_cabecera = $data['mostrar_cabecera'] ?? true;
        $titulo = ($data['titulo_personalizado'] ?? '') ?: $config['label'];
        $estilo = $data['estilo'] ?? 'default';
        $tamano = $config['tamano'] ?? 'medium';
        $module_id = $config['modulo'] ?? '';

        // Obtener acciones del módulo
        $acciones = $this->obtener_acciones_modulo($module_id);

        ob_start();
        ?>
        <div class="fvb-widget fvb-widget--<?php echo esc_attr($widget_id); ?> fvb-widget--<?php echo esc_attr($tamano); ?> fvb-widget--style-<?php echo esc_attr($estilo); ?>"
             data-widget-id="<?php echo esc_attr($widget_id); ?>"
             data-module="<?php echo esc_attr($module_id); ?>">
            <?php if ($mostrar_cabecera) : ?>
                <div class="fvb-widget__header">
                    <span class="fvb-widget__icon dashicons <?php echo esc_attr($config['icon']); ?>"></span>
                    <h3 class="fvb-widget__title"><?php echo esc_html($titulo); ?></h3>
                    <button type="button" class="fvb-widget__refresh" title="<?php esc_attr_e('Actualizar', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="fvb-widget__body">
                <div class="fvb-widget__loading">
                    <span class="fvb-spinner"></span>
                    <span><?php esc_html_e('Cargando...', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="fvb-widget__content" data-ajax-load="true" data-widget="<?php echo esc_attr($widget_id); ?>">
                    <!-- Contenido cargado via AJAX -->
                </div>
            </div>

            <?php if (!empty($acciones)) : ?>
                <div class="fvb-widget__actions">
                    <?php foreach (array_slice($acciones, 0, 3) as $action_id => $action) : ?>
                        <?php $this->renderizar_boton_accion($action_id, $action, $module_id); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="fvb-widget__footer">
                <a href="<?php echo esc_url($this->obtener_url_widget($widget_id)); ?>" class="fvb-widget__link">
                    <?php esc_html_e('Ver más', 'flavor-chat-ia'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtener acciones de un módulo
     *
     * @param string $module_id ID del módulo
     * @return array Acciones disponibles
     */
    private function obtener_acciones_modulo($module_id) {
        if (empty($module_id)) {
            return [];
        }

        // Obtener instancia del módulo
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $module = $loader->get_module($module_id);

        if (!$module) {
            return [];
        }

        // Verificar si el módulo tiene el trait de acciones frontend
        if (method_exists($module, 'get_frontend_actions')) {
            return $module->get_frontend_actions();
        }

        // Fallback: acciones predefinidas
        return $this->get_acciones_predefinidas($module_id);
    }

    /**
     * Obtener acciones predefinidas para módulos sin trait
     *
     * @param string $module_id
     * @return array
     */
    private function get_acciones_predefinidas($module_id) {
        $acciones = [
            // Movilidad
            'bicicletas-compartidas' => [
                'alquilar' => ['label' => __('Alquilar', 'flavor-chat-ia'), 'icon' => 'dashicons-unlock', 'type' => 'page', 'page' => 'bicicletas'],
                'mapa' => ['label' => __('Ver Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt', 'type' => 'page', 'page' => 'bicicletas/mapa'],
            ],
            'parkings' => [
                'reservar' => ['label' => __('Reservar', 'flavor-chat-ia'), 'icon' => 'dashicons-car', 'type' => 'page', 'page' => 'parkings'],
                'mapa' => ['label' => __('Ver Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt', 'type' => 'page', 'page' => 'parkings/mapa'],
            ],
            'carpooling' => [
                'buscar' => ['label' => __('Buscar Viaje', 'flavor-chat-ia'), 'icon' => 'dashicons-search', 'type' => 'page', 'page' => 'carpooling'],
                'ofrecer' => ['label' => __('Ofrecer Viaje', 'flavor-chat-ia'), 'icon' => 'dashicons-plus', 'type' => 'page', 'page' => 'carpooling/nuevo'],
            ],

            // Eventos y Actividades
            'eventos' => [
                'ver' => ['label' => __('Ver Eventos', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'type' => 'page', 'page' => 'eventos'],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'type' => 'page', 'page' => 'eventos/calendario'],
            ],
            'cursos' => [
                'ver' => ['label' => __('Ver Cursos', 'flavor-chat-ia'), 'icon' => 'dashicons-welcome-learn-more', 'type' => 'page', 'page' => 'cursos'],
                'mis-cursos' => ['label' => __('Mis Cursos', 'flavor-chat-ia'), 'icon' => 'dashicons-portfolio', 'type' => 'page', 'page' => 'cursos/mis-cursos'],
            ],
            'talleres' => [
                'ver' => ['label' => __('Ver Talleres', 'flavor-chat-ia'), 'icon' => 'dashicons-hammer', 'type' => 'page', 'page' => 'talleres'],
                'inscribirse' => ['label' => __('Inscribirse', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'type' => 'page', 'page' => 'talleres'],
            ],

            // Reservas y Espacios
            'espacios-comunes' => [
                'reservar' => ['label' => __('Reservar', 'flavor-chat-ia'), 'icon' => 'dashicons-building', 'type' => 'page', 'page' => 'espacios'],
                'mis-reservas' => ['label' => __('Mis Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'type' => 'page', 'page' => 'espacios/mis-reservas'],
            ],

            // Comunicación
            'avisos-municipales' => [
                'ver' => ['label' => __('Ver Avisos', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone', 'type' => 'page', 'page' => 'avisos'],
                'urgentes' => ['label' => __('Urgentes', 'flavor-chat-ia'), 'icon' => 'dashicons-warning', 'type' => 'page', 'page' => 'avisos?urgente=1'],
            ],
            'foros' => [
                'ver' => ['label' => __('Ver Foros', 'flavor-chat-ia'), 'icon' => 'dashicons-format-status', 'type' => 'page', 'page' => 'foros'],
                'nuevo' => ['label' => __('Nuevo Tema', 'flavor-chat-ia'), 'icon' => 'dashicons-edit', 'type' => 'page', 'page' => 'foros/nuevo'],
            ],
            'chat-grupos' => [
                'ver' => ['label' => __('Mis Grupos', 'flavor-chat-ia'), 'icon' => 'dashicons-format-chat', 'type' => 'page', 'page' => 'chat-grupos'],
                'crear' => ['label' => __('Crear Grupo', 'flavor-chat-ia'), 'icon' => 'dashicons-plus', 'type' => 'page', 'page' => 'chat-grupos/nuevo'],
            ],
            'chat-interno' => [
                'bandeja' => ['label' => __('Bandeja', 'flavor-chat-ia'), 'icon' => 'dashicons-email-alt', 'type' => 'page', 'page' => 'mensajes'],
                'nuevo' => ['label' => __('Nuevo Mensaje', 'flavor-chat-ia'), 'icon' => 'dashicons-plus', 'type' => 'page', 'page' => 'mensajes/nuevo'],
            ],
            'podcast' => [
                'escuchar' => ['label' => __('Escuchar', 'flavor-chat-ia'), 'icon' => 'dashicons-controls-play', 'type' => 'page', 'page' => 'podcast'],
            ],
            'radio' => [
                'escuchar' => ['label' => __('Escuchar', 'flavor-chat-ia'), 'icon' => 'dashicons-controls-volumeon', 'type' => 'page', 'page' => 'radio'],
            ],
            'multimedia' => [
                'galeria' => ['label' => __('Ver Galería', 'flavor-chat-ia'), 'icon' => 'dashicons-format-gallery', 'type' => 'page', 'page' => 'multimedia'],
                'subir' => ['label' => __('Subir', 'flavor-chat-ia'), 'icon' => 'dashicons-upload', 'type' => 'page', 'page' => 'multimedia/subir'],
            ],

            // Sostenibilidad
            'huertos-urbanos' => [
                'ver' => ['label' => __('Mi Huerto', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot', 'type' => 'page', 'page' => 'huertos'],
                'solicitar' => ['label' => __('Solicitar Parcela', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-site', 'type' => 'page', 'page' => 'huertos/solicitar'],
            ],
            'reciclaje' => [
                'puntos' => ['label' => __('Mis Puntos', 'flavor-chat-ia'), 'icon' => 'dashicons-star-filled', 'type' => 'page', 'page' => 'reciclaje/mis-puntos'],
                'mapa' => ['label' => __('Puntos Cercanos', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt', 'type' => 'page', 'page' => 'reciclaje/puntos-cercanos'],
            ],
            'compostaje' => [
                'registrar' => ['label' => __('Registrar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus', 'type' => 'page', 'page' => 'compostaje/registrar'],
                'mapa' => ['label' => __('Puntos', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt', 'type' => 'page', 'page' => 'compostaje/mapa'],
            ],

            // Economía Local
            'marketplace' => [
                'publicar' => ['label' => __('Publicar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus', 'type' => 'page', 'page' => 'marketplace/nuevo'],
                'buscar' => ['label' => __('Buscar', 'flavor-chat-ia'), 'icon' => 'dashicons-search', 'type' => 'page', 'page' => 'marketplace'],
            ],
            'tienda-local' => [
                'ver' => ['label' => __('Ver Tiendas', 'flavor-chat-ia'), 'icon' => 'dashicons-store', 'type' => 'page', 'page' => 'tienda'],
                'pedidos' => ['label' => __('Mis Pedidos', 'flavor-chat-ia'), 'icon' => 'dashicons-cart', 'type' => 'page', 'page' => 'tienda/mis-pedidos'],
            ],
            'banco-tiempo' => [
                'ofrecer' => ['label' => __('Ofrecer', 'flavor-chat-ia'), 'icon' => 'dashicons-heart', 'type' => 'page', 'page' => 'banco-tiempo/ofrecer'],
                'buscar' => ['label' => __('Buscar', 'flavor-chat-ia'), 'icon' => 'dashicons-search', 'type' => 'page', 'page' => 'banco-tiempo'],
            ],
            'grupos-consumo' => [
                'ver' => ['label' => __('Ver Grupos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'type' => 'page', 'page' => 'grupos-consumo'],
                'unirse' => ['label' => __('Unirse', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'type' => 'page', 'page' => 'grupos-consumo'],
            ],
            'biblioteca' => [
                'buscar' => ['label' => __('Buscar', 'flavor-chat-ia'), 'icon' => 'dashicons-search', 'type' => 'page', 'page' => 'biblioteca'],
                'prestamos' => ['label' => __('Mis Préstamos', 'flavor-chat-ia'), 'icon' => 'dashicons-book', 'type' => 'page', 'page' => 'biblioteca/mis-prestamos'],
            ],
            'bares' => [
                'ver' => ['label' => __('Ver Locales', 'flavor-chat-ia'), 'icon' => 'dashicons-food', 'type' => 'page', 'page' => 'bares'],
                'mapa' => ['label' => __('Ver Mapa', 'flavor-chat-ia'), 'icon' => 'dashicons-location-alt', 'type' => 'page', 'page' => 'bares/mapa'],
            ],

            // Participación
            'incidencias' => [
                'reportar' => ['label' => __('Reportar', 'flavor-chat-ia'), 'icon' => 'dashicons-warning', 'type' => 'page', 'page' => 'incidencias/reportar'],
                'mis' => ['label' => __('Mis Incidencias', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'type' => 'page', 'page' => 'incidencias/mis-incidencias'],
            ],
            'colectivos' => [
                'ver' => ['label' => __('Mis Colectivos', 'flavor-chat-ia'), 'icon' => 'dashicons-groups', 'type' => 'page', 'page' => 'colectivos'],
                'buscar' => ['label' => __('Buscar', 'flavor-chat-ia'), 'icon' => 'dashicons-search', 'type' => 'page', 'page' => 'colectivos'],
            ],
            'socios' => [
                'estado' => ['label' => __('Mi Estado', 'flavor-chat-ia'), 'icon' => 'dashicons-id-alt', 'type' => 'page', 'page' => 'socios'],
                'renovar' => ['label' => __('Renovar', 'flavor-chat-ia'), 'icon' => 'dashicons-update', 'type' => 'page', 'page' => 'socios/renovar'],
            ],
            'participacion' => [
                'ver' => ['label' => __('Procesos', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone', 'type' => 'page', 'page' => 'participacion'],
                'votar' => ['label' => __('Votar', 'flavor-chat-ia'), 'icon' => 'dashicons-yes', 'type' => 'page', 'page' => 'participacion'],
            ],
            'presupuestos-participativos' => [
                'ver' => ['label' => __('Ver Propuestas', 'flavor-chat-ia'), 'icon' => 'dashicons-money-alt', 'type' => 'page', 'page' => 'presupuestos'],
                'proponer' => ['label' => __('Proponer', 'flavor-chat-ia'), 'icon' => 'dashicons-plus', 'type' => 'page', 'page' => 'presupuestos/nuevo'],
            ],
            'tramites' => [
                'ver' => ['label' => __('Mis Trámites', 'flavor-chat-ia'), 'icon' => 'dashicons-clipboard', 'type' => 'page', 'page' => 'tramites'],
                'nuevo' => ['label' => __('Nuevo', 'flavor-chat-ia'), 'icon' => 'dashicons-plus', 'type' => 'page', 'page' => 'tramites/nuevo'],
            ],
            'ayuda-vecinal' => [
                'solicitar' => ['label' => __('Solicitar', 'flavor-chat-ia'), 'icon' => 'dashicons-heart', 'type' => 'page', 'page' => 'ayuda-vecinal/solicitar'],
                'ofrecer' => ['label' => __('Ofrecer', 'flavor-chat-ia'), 'icon' => 'dashicons-smiley', 'type' => 'page', 'page' => 'ayuda-vecinal/ofrecer'],
            ],
            'transparencia' => [
                'ver' => ['label' => __('Ver Datos', 'flavor-chat-ia'), 'icon' => 'dashicons-visibility', 'type' => 'page', 'page' => 'transparencia'],
            ],

            // Red Social
            'red-social' => [
                'feed' => ['label' => __('Mi Feed', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-users', 'type' => 'page', 'page' => 'red-social'],
                'publicar' => ['label' => __('Publicar', 'flavor-chat-ia'), 'icon' => 'dashicons-plus', 'type' => 'page', 'page' => 'red-social/publicar'],
            ],
            'comunidades' => [
                'explorar' => ['label' => __('Explorar', 'flavor-chat-ia'), 'icon' => 'dashicons-networking', 'type' => 'page', 'page' => 'comunidades'],
            ],

            // Recursos
            'recursos-compartidos' => [
                'ver' => ['label' => __('Ver Recursos', 'flavor-chat-ia'), 'icon' => 'dashicons-share', 'type' => 'page', 'page' => 'recursos'],
                'compartir' => ['label' => __('Compartir', 'flavor-chat-ia'), 'icon' => 'dashicons-plus', 'type' => 'page', 'page' => 'recursos/nuevo'],
            ],

            // Empleados
            'fichaje-empleados' => [
                'fichar' => ['label' => __('Fichar', 'flavor-chat-ia'), 'icon' => 'dashicons-clock', 'type' => 'action', 'action' => 'fichar'],
                'historial' => ['label' => __('Historial', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'type' => 'page', 'page' => 'fichaje/historial'],
            ],
            'facturas' => [
                'ver' => ['label' => __('Mis Facturas', 'flavor-chat-ia'), 'icon' => 'dashicons-media-text', 'type' => 'page', 'page' => 'facturas'],
            ],
        ];

        return $acciones[$module_id] ?? [];
    }

    /**
     * Renderizar botón de acción
     *
     * @param string $action_id
     * @param array $action
     * @param string $module_id
     */
    private function renderizar_boton_accion($action_id, $action, $module_id) {
        $type = $action['type'] ?? 'page';
        $icon = $action['icon'] ?? 'dashicons-yes';
        $label = $action['label'] ?? ucfirst($action_id);

        if ($type === 'page') {
            $page = $action['page'] ?? $module_id;
            $url = home_url('/' . $page . '/');
            ?>
            <a href="<?php echo esc_url($url); ?>" class="fvb-action-btn" title="<?php echo esc_attr($label); ?>">
                <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                <span class="fvb-action-btn__label"><?php echo esc_html($label); ?></span>
            </a>
            <?php
        } else {
            ?>
            <button type="button"
                    class="fvb-action-btn"
                    data-action="<?php echo esc_attr($action_id); ?>"
                    data-module="<?php echo esc_attr($module_id); ?>"
                    title="<?php echo esc_attr($label); ?>">
                <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                <span class="fvb-action-btn__label"><?php echo esc_html($label); ?></span>
            </button>
            <?php
        }
    }

    /**
     * Obtener URL para "Ver más" de un widget
     *
     * Usa el sistema de páginas dinámicas /mi-portal/{modulo}/
     * para widgets de módulos activos.
     *
     * @param string $widget_id ID del widget
     * @return string URL
     */
    private function obtener_url_widget($widget_id) {
        // Mapeo de widget_id a module_id para páginas dinámicas
        $mapeo_modulos = [
            'mis-reservas'          => 'reservas',
            'proximos-eventos'      => 'eventos',
            'cursos-activos'        => 'cursos',
            'talleres'              => 'talleres',
            'avisos-municipales'    => 'avisos-municipales',
            'notificaciones'        => 'notificaciones',
            'mis-pedidos'           => 'pedidos',
            'marketplace'           => 'marketplace',
            'tienda-local'          => 'tienda',
            'incidencias'           => 'incidencias',
            'participacion'         => 'participacion',
            'perfil-usuario'        => 'perfil',
            'estadisticas-personales' => 'estadisticas',
            'puntos-nivel'          => 'puntos',
            'espacios-disponibles'  => 'espacios-comunes',
            'biblioteca'            => 'biblioteca',
            'recursos-compartidos'  => 'recursos',
            'huertos-urbanos'       => 'huertos-urbanos',
            'banco-tiempo'          => 'banco-tiempo',
            'reciclaje'             => 'reciclaje',
            'bicicletas'            => 'bicicletas-compartidas',
            'parkings'              => 'parkings',
            'colectivos'            => 'colectivos',
            'foros'                 => 'foros',
            'chat-grupos'           => 'chat-grupos',
            'red-social'            => 'red-social',
            'red-comunidades'       => 'red-comunidades',
            'socios'                => 'socios',
            'fichaje'               => 'fichaje',
            'tramites'              => 'tramites',
            'presupuestos'          => 'presupuestos-participativos',
            'podcast'               => 'podcast',
            'radio'                 => 'radio',
            'multimedia'            => 'multimedia',
            'mapa-interactivo'      => 'mapa',
            'bares'                 => 'bares',
            'carpooling'            => 'carpooling',
            'grupos-consumo'        => 'grupos-consumo',
            'compostaje'            => 'compostaje',
            'transparencia'         => 'transparencia',
            'ayuda-vecinal'         => 'ayuda-vecinal',
        ];

        // Si existe mapeo, usar página dinámica /mi-portal/
        if (isset($mapeo_modulos[$widget_id])) {
            $modulo_id = $mapeo_modulos[$widget_id];
            return home_url('/mi-portal/' . $modulo_id . '/');
        }

        // Fallback a /mi-portal/ principal
        return home_url('/mi-portal/');
    }

    /**
     * Renderizar sección dashboard completo
     *
     * @param array  $data    Datos de configuración
     * @param string $variant Variante de la sección
     */
    public function renderizar_seccion_dashboard_completo($data, $variant = 'grid') {
        $columnas = $data['columnas'] ?? 3;
        $gap = $data['gap'] ?? 20;
        $widgets_visibles = $data['widgets_visibles'] ?? [];

        // Si no hay widgets seleccionados, mostrar todos los disponibles
        if (empty($widgets_visibles)) {
            $widgets_visibles = array_keys($this->obtener_widgets_activos());
        }

        ob_start();
        ?>
        <section class="fvb-section fvb-section--dashboard fvb-section--<?php echo esc_attr($variant); ?>">
            <div class="fvb-dashboard-grid" style="--fvb-columns: <?php echo esc_attr($columnas); ?>; --fvb-gap: <?php echo esc_attr($gap); ?>px;">
                <?php
                foreach ($widgets_visibles as $widget_id) {
                    if (isset($this->widgets_registrados[$widget_id])) {
                        $config = $this->widgets_registrados[$widget_id];
                        // Verificar módulo activo
                        if (!empty($config['modulo']) && !$this->modulo_activo($config['modulo'])) {
                            continue;
                        }
                        echo $this->renderizar_widget($widget_id, $config, $data);
                    }
                }
                ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar sección de estadísticas
     *
     * @param array  $data    Datos de configuración
     * @param string $variant Variante de la sección
     */
    public function renderizar_seccion_stats($data, $variant = 'horizontal') {
        if (!is_user_logged_in()) {
            return '';
        }

        $id_usuario = get_current_user_id();
        $stats_visibles = $data['stats_visibles'] ?? ['reservas', 'eventos', 'puntos', 'mensajes'];
        $mostrar_iconos = $data['mostrar_iconos'] ?? true;
        $mostrar_tendencia = $data['mostrar_tendencia'] ?? true;

        $estadisticas = $this->obtener_estadisticas_usuario($id_usuario, $stats_visibles);

        ob_start();
        ?>
        <section class="fvb-section fvb-section--stats fvb-section--<?php echo esc_attr($variant); ?>">
            <div class="fvb-stats-grid">
                <?php foreach ($estadisticas as $key => $stat) : ?>
                    <div class="fvb-stat-card fvb-stat-card--<?php echo esc_attr($key); ?>">
                        <?php if ($mostrar_iconos && !empty($stat['icon'])) : ?>
                            <div class="fvb-stat-card__icon">
                                <span class="dashicons <?php echo esc_attr($stat['icon']); ?>"></span>
                            </div>
                        <?php endif; ?>
                        <div class="fvb-stat-card__content">
                            <span class="fvb-stat-card__value"><?php echo esc_html($stat['valor']); ?></span>
                            <span class="fvb-stat-card__label"><?php echo esc_html($stat['label']); ?></span>
                            <?php if ($mostrar_tendencia && isset($stat['tendencia'])) : ?>
                                <span class="fvb-stat-card__trend fvb-stat-card__trend--<?php echo $stat['tendencia'] > 0 ? 'up' : ($stat['tendencia'] < 0 ? 'down' : 'neutral'); ?>">
                                    <?php echo $stat['tendencia'] > 0 ? '+' : ''; ?><?php echo esc_html($stat['tendencia']); ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtener estadísticas del usuario
     *
     * @param int   $id_usuario     ID del usuario
     * @param array $stats_visibles Estadísticas a mostrar
     * @return array
     */
    private function obtener_estadisticas_usuario($id_usuario, $stats_visibles) {
        $estadisticas = [];

        $definiciones = [
            'reservas' => [
                'label'    => __('Reservas', 'flavor-chat-ia'),
                'icon'     => 'dashicons-calendar-alt',
                'callback' => 'obtener_stat_reservas',
            ],
            'eventos' => [
                'label'    => __('Eventos', 'flavor-chat-ia'),
                'icon'     => 'dashicons-tickets-alt',
                'callback' => 'obtener_stat_eventos',
            ],
            'puntos' => [
                'label'    => __('Puntos', 'flavor-chat-ia'),
                'icon'     => 'dashicons-star-filled',
                'callback' => 'obtener_stat_puntos',
            ],
            'mensajes' => [
                'label'    => __('Mensajes', 'flavor-chat-ia'),
                'icon'     => 'dashicons-email-alt',
                'callback' => 'obtener_stat_mensajes',
            ],
            'participacion' => [
                'label'    => __('Participación', 'flavor-chat-ia'),
                'icon'     => 'dashicons-groups',
                'callback' => 'obtener_stat_participacion',
            ],
        ];

        foreach ($stats_visibles as $key) {
            if (isset($definiciones[$key])) {
                $def = $definiciones[$key];
                $valor = 0;
                $tendencia = 0;

                if (method_exists($this, $def['callback'])) {
                    $resultado = $this->{$def['callback']}($id_usuario);
                    $valor = $resultado['valor'] ?? 0;
                    $tendencia = $resultado['tendencia'] ?? 0;
                }

                $estadisticas[$key] = [
                    'label'     => $def['label'],
                    'icon'      => $def['icon'],
                    'valor'     => $valor,
                    'tendencia' => $tendencia,
                ];
            }
        }

        return $estadisticas;
    }

    /**
     * Obtener widgets activos (con módulos activos)
     *
     * @return array
     */
    public function obtener_widgets_activos() {
        $widgets_activos = [];

        foreach ($this->widgets_registrados as $widget_id => $config) {
            if (empty($config['modulo']) || $this->modulo_activo($config['modulo'])) {
                $widgets_activos[$widget_id] = $config;
            }
        }

        return $widgets_activos;
    }

    /**
     * Encolar assets frontend
     */
    public function encolar_assets_frontend() {
        if (!is_singular()) {
            return;
        }

        global $post;
        if (!$post) {
            return;
        }

        // Verificar si la página usa el visual builder con widgets de dashboard
        $builder_data = get_post_meta($post->ID, '_flavor_vb_data', true);
        if (empty($builder_data)) {
            return;
        }

        $tiene_widgets_dashboard = false;
        if (is_array($builder_data) && !empty($builder_data['content'])) {
            foreach ($builder_data['content'] as $item) {
                if (strpos($item['component'] ?? '', 'dw-') === 0 || strpos($item['component'] ?? '', 'dashboard') !== false) {
                    $tiene_widgets_dashboard = true;
                    break;
                }
            }
        }

        if (!$tiene_widgets_dashboard) {
            return;
        }

        // CSS de widgets de dashboard
        wp_enqueue_style(
            'fvb-dashboard-widgets',
            FLAVOR_CHAT_IA_URL . 'assets/css/dashboard-vb-widgets.css',
            ['flavor-vb-frontend'],
            FLAVOR_CHAT_IA_VERSION
        );

        // JS de widgets de dashboard
        wp_enqueue_script(
            'fvb-dashboard-widgets',
            FLAVOR_CHAT_IA_URL . 'assets/js/dashboard-vb-widgets.js',
            ['jquery', 'flavor-vb-frontend'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('fvb-dashboard-widgets', 'fvbDashboardWidgets', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('fvb_dashboard_widgets'),
            'userId'  => get_current_user_id(),
            'i18n'    => [
                'cargando'    => __('Cargando...', 'flavor-chat-ia'),
                'error'       => __('Error al cargar', 'flavor-chat-ia'),
                'actualizado' => __('Actualizado', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encolar assets admin
     */
    public function encolar_assets_admin($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        // CSS para el panel del builder
        wp_enqueue_style(
            'fvb-dashboard-widgets-admin',
            FLAVOR_CHAT_IA_URL . 'assets/css/dashboard-vb-widgets-admin.css',
            ['flavor-visual-builder'],
            FLAVOR_CHAT_IA_VERSION
        );
    }

    /**
     * AJAX: Obtener datos de widget
     */
    public function ajax_obtener_datos_widget() {
        check_ajax_referer('fvb_dashboard_widgets', 'nonce');

        $widget_id = sanitize_key($_POST['widget_id'] ?? '');
        $id_usuario = get_current_user_id();

        if (empty($widget_id)) {
            wp_send_json_error(['message' => __('Widget no especificado', 'flavor-chat-ia')]);
        }

        $widgets = $this->obtener_definicion_widgets();

        if (!isset($widgets[$widget_id])) {
            wp_send_json_error(['message' => __('Widget no encontrado', 'flavor-chat-ia')]);
        }

        $config = $widgets[$widget_id];

        // Verificar módulo activo
        if (!empty($config['modulo']) && !$this->modulo_activo($config['modulo'])) {
            wp_send_json_error(['message' => __('Módulo no activo', 'flavor-chat-ia')]);
        }

        // Obtener datos del widget
        $datos = $this->obtener_datos_widget($widget_id, $config, $id_usuario);

        wp_send_json_success($datos);
    }

    /**
     * Obtener datos de un widget específico
     *
     * @param string $widget_id   ID del widget
     * @param array  $config      Configuración
     * @param int    $id_usuario  ID del usuario
     * @return array
     */
    private function obtener_datos_widget($widget_id, $config, $id_usuario) {
        // Aplicar filtro para que los módulos puedan proporcionar sus datos
        $datos = apply_filters("fvb_widget_data_{$widget_id}", [], $id_usuario, $config);

        if (empty($datos)) {
            // Datos por defecto o placeholder
            $datos = [
                'html'    => '<p class="fvb-widget__empty">' . __('Sin datos disponibles', 'flavor-chat-ia') . '</p>',
                'count'   => 0,
                'updated' => current_time('c'),
            ];
        }

        return $datos;
    }

    // =========================================================================
    // Métodos de renderizado específicos para cada widget
    // =========================================================================

    /**
     * Renderizar widget de perfil
     */
    public function renderizar_widget_perfil($widget_id, $config, $data, $id_usuario) {
        $usuario = get_userdata($id_usuario);
        if (!$usuario) {
            return;
        }

        $avatar_url = get_avatar_url($id_usuario, ['size' => 96]);
        $nombre = trim($usuario->first_name . ' ' . $usuario->last_name) ?: $usuario->display_name;
        ?>
        <div class="fvb-widget fvb-widget--perfil fvb-widget--small">
            <div class="fvb-widget__body">
                <div class="fvb-perfil">
                    <?php if (!empty($data['mostrar_avatar'])) : ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="fvb-perfil__avatar">
                    <?php endif; ?>
                    <div class="fvb-perfil__info">
                        <strong class="fvb-perfil__nombre"><?php echo esc_html($nombre); ?></strong>
                        <?php if (!empty($data['mostrar_rol'])) : ?>
                            <span class="fvb-perfil__rol"><?php echo esc_html(implode(', ', $usuario->roles)); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($data['mostrar_registro'])) : ?>
                            <span class="fvb-perfil__registro">
                                <?php printf(__('Desde %s', 'flavor-chat-ia'), date_i18n(get_option('date_format'), strtotime($usuario->user_registered))); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar widget de Espacios Disponibles
     */
    public function renderizar_widget_espacios($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_espacios';
        $espacios = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            if (in_array('nombre', $columnas)) $select_cols[] = 'nombre';
            elseif (in_array('titulo', $columnas)) $select_cols[] = 'titulo as nombre';
            else $select_cols[] = "'Espacio' as nombre";

            if (in_array('capacidad', $columnas)) $select_cols[] = 'capacidad';
            elseif (in_array('aforo', $columnas)) $select_cols[] = 'aforo as capacidad';
            else $select_cols[] = '0 as capacidad';

            $select = implode(', ', $select_cols);
            $where = in_array('estado', $columnas) ? "WHERE estado = 'activo'" : "WHERE 1=1";

            $espacios = $wpdb->get_results(
                "SELECT $select FROM $tabla $where ORDER BY nombre ASC LIMIT 5"
            );
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($espacios) {
            if (empty($espacios)) {
                $this->render_empty_state(__('No hay espacios disponibles', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($espacios as $espacio): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-building"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($espacio->nombre); ?></strong>
                            <span class="fvb-list__meta"><?php printf(__('Capacidad: %d', 'flavor-chat-ia'), $espacio->capacidad); ?></span>
                        </div>
                        <a href="<?php echo esc_url(home_url('/espacios/?id=' . $espacio->id)); ?>" class="fvb-btn-sm">
                            <?php esc_html_e('Reservar', 'flavor-chat-ia'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'espacios');
    }

    /**
     * Renderizar widget de Próximos Eventos
     */
    public function renderizar_widget_proximos_eventos($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos';
        $eventos = [];
        $cantidad = $data['cantidad'] ?? 5;

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            if (in_array('titulo', $columnas)) $select_cols[] = 'titulo';
            elseif (in_array('nombre', $columnas)) $select_cols[] = 'nombre as titulo';
            else $select_cols[] = "'Evento' as titulo";

            if (in_array('fecha_inicio', $columnas)) $select_cols[] = 'fecha_inicio';
            elseif (in_array('fecha', $columnas)) $select_cols[] = 'fecha as fecha_inicio';
            else $select_cols[] = 'NOW() as fecha_inicio';

            if (in_array('lugar', $columnas)) $select_cols[] = 'lugar';
            elseif (in_array('ubicacion', $columnas)) $select_cols[] = 'ubicacion as lugar';
            else $select_cols[] = 'NULL as lugar';

            $select = implode(', ', $select_cols);
            $col_fecha = in_array('fecha_inicio', $columnas) ? 'fecha_inicio' : (in_array('fecha', $columnas) ? 'fecha' : 'id');

            $where_parts = [];
            if (in_array('estado', $columnas)) $where_parts[] = "estado = 'publicado'";
            $where_parts[] = "$col_fecha >= NOW()";
            $where = implode(' AND ', $where_parts);

            $eventos = $wpdb->get_results($wpdb->prepare(
                "SELECT $select FROM $tabla WHERE $where ORDER BY $col_fecha ASC LIMIT %d",
                $cantidad
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($eventos) {
            if (empty($eventos)) {
                $this->render_empty_state(__('No hay eventos próximos', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($eventos as $evento): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-calendar-alt"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($evento->titulo); ?></strong>
                            <span class="fvb-list__meta">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($evento->fecha_inicio))); ?>
                                <?php if ($evento->lugar): ?> - <?php echo esc_html($evento->lugar); ?><?php endif; ?>
                            </span>
                        </div>
                        <a href="<?php echo esc_url(home_url('/eventos/' . $evento->id . '/')); ?>" class="fvb-btn-sm">
                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'eventos');
    }

    /**
     * Renderizar widget de Mis Cursos
     */
    public function renderizar_widget_cursos($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $cursos = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_cursos) && Flavor_Chat_Helpers::tabla_existe($tabla_inscripciones)) {
            // Obtener columnas disponibles
            $columnas_cursos = $wpdb->get_col("SHOW COLUMNS FROM $tabla_cursos");
            $columnas_insc = $wpdb->get_col("SHOW COLUMNS FROM $tabla_inscripciones");

            $select_cols = ['c.id'];
            if (in_array('titulo', $columnas_cursos)) $select_cols[] = 'c.titulo';
            elseif (in_array('nombre', $columnas_cursos)) $select_cols[] = 'c.nombre as titulo';
            else $select_cols[] = "'Curso' as titulo";

            if (in_array('progreso', $columnas_insc)) $select_cols[] = 'i.progreso';
            else $select_cols[] = '0 as progreso';

            $select = implode(', ', $select_cols);
            $col_usuario_insc = in_array('user_id', $columnas_insc) ? 'user_id' : 'usuario_id';

            $where = "i.$col_usuario_insc = %d";
            if (in_array('estado', $columnas_insc)) $where .= " AND i.estado = 'activo'";

            $order = in_array('updated_at', $columnas_insc) ? 'i.updated_at' :
                    (in_array('created_at', $columnas_insc) ? 'i.created_at' : 'c.id');

            $cursos = $wpdb->get_results($wpdb->prepare(
                "SELECT $select
                 FROM $tabla_cursos c
                 INNER JOIN $tabla_inscripciones i ON c.id = i.curso_id
                 WHERE $where
                 ORDER BY $order DESC LIMIT 5",
                $id_usuario
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($cursos, $data) {
            if (empty($cursos)) {
                $this->render_empty_state(__('No estás inscrito en ningún curso', 'flavor-chat-ia'), home_url('/cursos/'), __('Explorar cursos', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($cursos as $curso): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-welcome-learn-more"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($curso->titulo); ?></strong>
                            <?php if (!empty($data['mostrar_progreso'])): ?>
                                <div class="fvb-progress">
                                    <div class="fvb-progress__bar" style="width: <?php echo esc_attr($curso->progreso ?? 0); ?>%"></div>
                                </div>
                                <span class="fvb-list__meta"><?php printf(__('%d%% completado', 'flavor-chat-ia'), $curso->progreso ?? 0); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo esc_url(home_url('/cursos/' . $curso->id . '/')); ?>" class="fvb-btn-sm">
                            <?php esc_html_e('Continuar', 'flavor-chat-ia'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'cursos');
    }

    /**
     * Renderizar widget de Talleres
     */
    public function renderizar_widget_talleres($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_talleres';
        $talleres = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            if (in_array('titulo', $columnas)) $select_cols[] = 'titulo';
            elseif (in_array('nombre', $columnas)) $select_cols[] = 'nombre as titulo';
            else $select_cols[] = "'Taller' as titulo";

            if (in_array('fecha', $columnas)) $select_cols[] = 'fecha';
            elseif (in_array('fecha_inicio', $columnas)) $select_cols[] = 'fecha_inicio as fecha';
            else $select_cols[] = 'NOW() as fecha';

            if (in_array('plazas_disponibles', $columnas)) $select_cols[] = 'plazas_disponibles';
            elseif (in_array('plazas', $columnas)) $select_cols[] = 'plazas as plazas_disponibles';
            else $select_cols[] = '0 as plazas_disponibles';

            $select = implode(', ', $select_cols);
            $col_fecha = in_array('fecha', $columnas) ? 'fecha' : (in_array('fecha_inicio', $columnas) ? 'fecha_inicio' : 'id');

            $where_parts = [];
            if (in_array('estado', $columnas)) $where_parts[] = "estado = 'publicado'";
            $where_parts[] = "$col_fecha >= NOW()";
            $where = implode(' AND ', $where_parts);

            $talleres = $wpdb->get_results(
                "SELECT $select FROM $tabla WHERE $where ORDER BY $col_fecha ASC LIMIT 5"
            );
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($talleres) {
            if (empty($talleres)) {
                $this->render_empty_state(__('No hay talleres próximos', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($talleres as $taller): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-hammer"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($taller->titulo); ?></strong>
                            <span class="fvb-list__meta">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($taller->fecha))); ?>
                                <?php if ($taller->plazas_disponibles > 0): ?>
                                    - <?php printf(__('%d plazas', 'flavor-chat-ia'), $taller->plazas_disponibles); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <a href="<?php echo esc_url(home_url('/talleres/' . $taller->id . '/')); ?>" class="fvb-btn-sm">
                            <?php esc_html_e('Inscribirse', 'flavor-chat-ia'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'talleres');
    }

    /**
     * Renderizar widget de Avisos Municipales
     */
    public function renderizar_widget_avisos($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_avisos';
        $avisos = [];
        $cantidad = $data['cantidad'] ?? 5;

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            if (in_array('titulo', $columnas)) $select_cols[] = 'titulo';
            elseif (in_array('nombre', $columnas)) $select_cols[] = 'nombre as titulo';
            else $select_cols[] = "'Aviso' as titulo";

            if (in_array('urgente', $columnas)) $select_cols[] = 'urgente';
            else $select_cols[] = '0 as urgente';

            if (in_array('fecha_publicacion', $columnas)) $select_cols[] = 'fecha_publicacion';
            elseif (in_array('created_at', $columnas)) $select_cols[] = 'created_at as fecha_publicacion';
            elseif (in_array('fecha', $columnas)) $select_cols[] = 'fecha as fecha_publicacion';
            else $select_cols[] = 'NOW() as fecha_publicacion';

            $select = implode(', ', $select_cols);

            $where_parts = [];
            if (in_array('estado', $columnas)) $where_parts[] = "estado = 'publicado'";
            if (!empty($data['solo_urgentes']) && in_array('urgente', $columnas)) $where_parts[] = "urgente = 1";
            $where = !empty($where_parts) ? implode(' AND ', $where_parts) : "1=1";

            $order_urgente = in_array('urgente', $columnas) ? 'urgente DESC, ' : '';
            $order_fecha = in_array('fecha_publicacion', $columnas) ? 'fecha_publicacion' :
                          (in_array('created_at', $columnas) ? 'created_at' :
                          (in_array('fecha', $columnas) ? 'fecha' : 'id'));

            $avisos = $wpdb->get_results($wpdb->prepare(
                "SELECT $select FROM $tabla WHERE $where ORDER BY $order_urgente$order_fecha DESC LIMIT %d",
                $cantidad
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($avisos) {
            if (empty($avisos)) {
                $this->render_empty_state(__('No hay avisos recientes', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list fvb-list--avisos">
                <?php foreach ($avisos as $aviso): ?>
                    <li class="fvb-list__item <?php echo $aviso->urgente ? 'fvb-list__item--urgente' : ''; ?>">
                        <span class="fvb-list__icon dashicons <?php echo $aviso->urgente ? 'dashicons-warning' : 'dashicons-megaphone'; ?>"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($aviso->titulo); ?></strong>
                            <span class="fvb-list__meta">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($aviso->fecha_publicacion))); ?>
                            </span>
                        </div>
                        <a href="<?php echo esc_url(home_url('/avisos/' . $aviso->id . '/')); ?>" class="fvb-btn-sm">
                            <?php esc_html_e('Leer', 'flavor-chat-ia'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'avisos-municipales');
    }

    /**
     * Renderizar widget de Mis Grupos de Chat
     */
    public function renderizar_widget_chat_grupos($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        $tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        $grupos = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_grupos) && Flavor_Chat_Helpers::tabla_existe($tabla_miembros)) {
            // Obtener columnas disponibles
            $columnas_grupos = $wpdb->get_col("SHOW COLUMNS FROM $tabla_grupos");
            $columnas_miembros = $wpdb->get_col("SHOW COLUMNS FROM $tabla_miembros");

            $select_cols = ['g.id'];
            if (in_array('nombre', $columnas_grupos)) $select_cols[] = 'g.nombre';
            else $select_cols[] = "'Grupo' as nombre";

            $select_cols[] = "(SELECT COUNT(*) FROM $tabla_miembros WHERE grupo_id = g.id) as miembros";
            $select = implode(', ', $select_cols);

            $col_usuario_miembro = in_array('user_id', $columnas_miembros) ? 'user_id' : 'usuario_id';
            $where = "m.$col_usuario_miembro = %d";
            if (in_array('estado', $columnas_grupos)) $where .= " AND g.estado = 'activo'";

            $grupos = $wpdb->get_results($wpdb->prepare(
                "SELECT $select
                 FROM $tabla_grupos g
                 INNER JOIN $tabla_miembros m ON g.id = m.grupo_id
                 WHERE $where
                 ORDER BY g.nombre ASC LIMIT 5",
                $id_usuario
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($grupos) {
            if (empty($grupos)) {
                $this->render_empty_state(__('No perteneces a ningún grupo', 'flavor-chat-ia'), home_url('/chat-grupos/'), __('Explorar grupos', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($grupos as $grupo): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-format-chat"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($grupo->nombre); ?></strong>
                            <span class="fvb-list__meta"><?php printf(__('%d miembros', 'flavor-chat-ia'), $grupo->miembros); ?></span>
                        </div>
                        <a href="<?php echo esc_url(home_url('/chat-grupos/' . $grupo->id . '/')); ?>" class="fvb-btn-sm">
                            <?php esc_html_e('Abrir', 'flavor-chat-ia'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'chat-grupos');
    }

    /**
     * Renderizar widget de Foros
     */
    public function renderizar_widget_foros($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_foros_temas';
        $temas = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            if (in_array('titulo', $columnas)) $select_cols[] = 'titulo';
            elseif (in_array('nombre', $columnas)) $select_cols[] = 'nombre as titulo';
            else $select_cols[] = "'Tema' as titulo";

            if (in_array('respuestas', $columnas)) $select_cols[] = 'respuestas';
            elseif (in_array('num_respuestas', $columnas)) $select_cols[] = 'num_respuestas as respuestas';
            else $select_cols[] = '0 as respuestas';

            $select = implode(', ', $select_cols);
            $where = in_array('estado', $columnas) ? "WHERE estado = 'abierto'" : "WHERE 1=1";
            $order = in_array('updated_at', $columnas) ? 'updated_at' :
                    (in_array('fecha_actualizacion', $columnas) ? 'fecha_actualizacion' :
                    (in_array('created_at', $columnas) ? 'created_at' : 'id'));

            $temas = $wpdb->get_results(
                "SELECT $select FROM $tabla $where ORDER BY $order DESC LIMIT 5"
            );
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($temas) {
            if (empty($temas)) {
                $this->render_empty_state(__('No hay temas recientes', 'flavor-chat-ia'), home_url('/foros/nuevo/'), __('Crear tema', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($temas as $tema): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-format-status"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($tema->titulo); ?></strong>
                            <span class="fvb-list__meta">
                                <?php printf(__('%d respuestas', 'flavor-chat-ia'), $tema->respuestas ?? 0); ?>
                            </span>
                        </div>
                        <a href="<?php echo esc_url(home_url('/foros/' . $tema->id . '/')); ?>" class="fvb-btn-sm">
                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'foros');
    }

    /**
     * Renderizar widget de Mi Huerto
     */
    public function renderizar_widget_huertos($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
        $tabla_asignaciones = $wpdb->prefix . 'flavor_huertos_asignaciones';
        $parcela = null;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_parcelas) && Flavor_Chat_Helpers::tabla_existe($tabla_asignaciones)) {
            // Obtener columnas disponibles
            $columnas_parcelas = $wpdb->get_col("SHOW COLUMNS FROM $tabla_parcelas");
            $columnas_asig = $wpdb->get_col("SHOW COLUMNS FROM $tabla_asignaciones");

            $select_cols = ['p.id'];
            if (in_array('nombre', $columnas_parcelas)) $select_cols[] = 'p.nombre';
            elseif (in_array('codigo', $columnas_parcelas)) $select_cols[] = 'p.codigo as nombre';
            else $select_cols[] = "'Parcela' as nombre";

            if (in_array('superficie', $columnas_parcelas)) $select_cols[] = 'p.superficie';
            elseif (in_array('metros', $columnas_parcelas)) $select_cols[] = 'p.metros as superficie';
            else $select_cols[] = '0 as superficie';

            if (in_array('fecha_inicio', $columnas_asig)) $select_cols[] = 'a.fecha_inicio';
            else $select_cols[] = 'NULL as fecha_inicio';

            $select = implode(', ', $select_cols);
            $col_usuario = in_array('user_id', $columnas_asig) ? 'user_id' : (in_array('usuario_id', $columnas_asig) ? 'usuario_id' : null);
            if (!$col_usuario) { return; } // Sin columna de usuario, salir

            $where = "a.$col_usuario = %d";
            if (in_array('estado', $columnas_asig)) $where .= " AND a.estado = 'activo'";

            $parcela = $wpdb->get_row($wpdb->prepare(
                "SELECT $select
                 FROM $tabla_parcelas p
                 INNER JOIN $tabla_asignaciones a ON p.id = a.parcela_id
                 WHERE $where LIMIT 1",
                $id_usuario
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($parcela, $data) {
            if (!$parcela) {
                $this->render_empty_state(__('No tienes parcela asignada', 'flavor-chat-ia'), home_url('/huertos/solicitar/'), __('Solicitar parcela', 'flavor-chat-ia'));
                return;
            }
            ?>
            <div class="fvb-huerto-card">
                <div class="fvb-huerto-card__header">
                    <span class="dashicons dashicons-carrot"></span>
                    <h4><?php echo esc_html($parcela->nombre); ?></h4>
                </div>
                <div class="fvb-huerto-card__info">
                    <div class="fvb-huerto-stat">
                        <span class="fvb-huerto-stat__value"><?php echo esc_html($parcela->superficie); ?> m²</span>
                        <span class="fvb-huerto-stat__label"><?php esc_html_e('Superficie', 'flavor-chat-ia'); ?></span>
                    </div>
                    <?php if (!empty($data['mostrar_ciclo'])): ?>
                        <div class="fvb-huerto-stat">
                            <span class="fvb-huerto-stat__value">
                                <?php echo esc_html(date_i18n('M Y', strtotime($parcela->fecha_inicio))); ?>
                            </span>
                            <span class="fvb-huerto-stat__label"><?php esc_html_e('Desde', 'flavor-chat-ia'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="<?php echo esc_url(home_url('/huertos/' . $parcela->id . '/')); ?>" class="fvb-btn fvb-btn--primary">
                    <?php esc_html_e('Ver mi huerto', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php
        }, 'huertos-urbanos');
    }

    /**
     * Renderizar widget de Reciclaje
     */
    public function renderizar_widget_reciclaje($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';
        $puntos_totales = 0;
        $kg_reciclados = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_puntos)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla_puntos");
            $col_puntos = in_array('puntos', $columnas) ? 'puntos' :
                         (in_array('cantidad', $columnas) ? 'cantidad' : null);
            $col_usuario = in_array('user_id', $columnas) ? 'user_id' : (in_array('usuario_id', $columnas) ? 'usuario_id' : null);
            if (!$col_usuario) { return; } // Sin columna de usuario, salir

            if ($col_puntos) {
                $stats = $wpdb->get_row($wpdb->prepare(
                    "SELECT COALESCE(SUM($col_puntos), 0) as total_puntos FROM $tabla_puntos WHERE $col_usuario = %d",
                    $id_usuario
                ));
                $puntos_totales = $stats->total_puntos ?? 0;
            }
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($puntos_totales, $kg_reciclados) {
            ?>
            <div class="fvb-reciclaje-stats">
                <div class="fvb-stat-box fvb-stat-box--success">
                    <span class="fvb-stat-box__icon">♻️</span>
                    <span class="fvb-stat-box__value"><?php echo esc_html(number_format($puntos_totales)); ?></span>
                    <span class="fvb-stat-box__label"><?php esc_html_e('Puntos', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <div class="fvb-widget__quick-actions">
                <a href="<?php echo esc_url(home_url('/reciclaje/mis-puntos/')); ?>" class="fvb-quick-action">
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('Mis puntos', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/reciclaje/puntos-cercanos/')); ?>" class="fvb-quick-action">
                    <span class="dashicons dashicons-location-alt"></span>
                    <?php esc_html_e('Puntos cercanos', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php
        }, 'reciclaje');
    }

    /**
     * Renderizar widget de Marketplace
     */
    public function renderizar_widget_marketplace($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_marketplace';
        $anuncios = [];
        $mis_anuncios = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            if (in_array('titulo', $columnas)) $select_cols[] = 'titulo';
            elseif (in_array('nombre', $columnas)) $select_cols[] = 'nombre as titulo';
            else $select_cols[] = "'Anuncio' as titulo";

            if (in_array('precio', $columnas)) $select_cols[] = 'precio';
            else $select_cols[] = '0 as precio';

            if (in_array('imagen', $columnas)) $select_cols[] = 'imagen';
            elseif (in_array('foto', $columnas)) $select_cols[] = 'foto as imagen';
            else $select_cols[] = 'NULL as imagen';

            $select = implode(', ', $select_cols);
            $col_fecha = in_array('created_at', $columnas) ? 'created_at' : (in_array('fecha', $columnas) ? 'fecha' : 'id');
            $col_usuario = in_array('user_id', $columnas) ? 'user_id' : (in_array('usuario_id', $columnas) ? 'usuario_id' : null);
            if (!$col_usuario) { return; } // Sin columna de usuario, salir
            $where = in_array('estado', $columnas) ? "WHERE estado = 'activo'" : "WHERE 1=1";

            $anuncios = $wpdb->get_results(
                "SELECT $select FROM $tabla $where ORDER BY $col_fecha DESC LIMIT 4"
            );

            $where_usuario = in_array('estado', $columnas) ? "$col_usuario = %d AND estado = 'activo'" : "$col_usuario = %d";
            $mis_anuncios = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE $where_usuario",
                $id_usuario
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($anuncios, $mis_anuncios) {
            ?>
            <div class="fvb-marketplace-header">
                <span class="fvb-badge"><?php printf(__('%d anuncios tuyos', 'flavor-chat-ia'), $mis_anuncios); ?></span>
            </div>
            <?php if (empty($anuncios)): ?>
                <?php $this->render_empty_state(__('No hay anuncios', 'flavor-chat-ia'), home_url('/marketplace/nuevo/'), __('Publicar', 'flavor-chat-ia')); ?>
            <?php else: ?>
                <div class="fvb-marketplace-grid">
                    <?php foreach ($anuncios as $anuncio): ?>
                        <a href="<?php echo esc_url(home_url('/marketplace/' . $anuncio->id . '/')); ?>" class="fvb-marketplace-item">
                            <?php if ($anuncio->imagen): ?>
                                <img src="<?php echo esc_url($anuncio->imagen); ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <div class="fvb-marketplace-item__placeholder"><span class="dashicons dashicons-format-image"></span></div>
                            <?php endif; ?>
                            <div class="fvb-marketplace-item__info">
                                <span class="fvb-marketplace-item__title"><?php echo esc_html(wp_trim_words($anuncio->titulo, 5)); ?></span>
                                <?php if ($anuncio->precio > 0): ?>
                                    <span class="fvb-marketplace-item__price"><?php echo esc_html(number_format($anuncio->precio, 2)); ?>€</span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php
        }, 'marketplace');
    }

    /**
     * Renderizar widget de Banco del Tiempo
     */
    public function renderizar_widget_banco_tiempo($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla_saldo = $wpdb->prefix . 'flavor_banco_tiempo_saldo';
        $saldo = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_saldo)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla_saldo");
            $col_horas = in_array('horas', $columnas) ? 'horas' :
                        (in_array('saldo', $columnas) ? 'saldo' :
                        (in_array('cantidad', $columnas) ? 'cantidad' : null));
            $col_usuario = in_array('user_id', $columnas) ? 'user_id' : (in_array('usuario_id', $columnas) ? 'usuario_id' : null);
            if (!$col_usuario) { return; } // Sin columna de usuario, salir

            if ($col_horas) {
                $stats = $wpdb->get_row($wpdb->prepare(
                    "SELECT COALESCE(SUM($col_horas), 0) as saldo FROM $tabla_saldo WHERE $col_usuario = %d",
                    $id_usuario
                ));
                $saldo = $stats->saldo ?? 0;
            }
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($saldo) {
            ?>
            <div class="fvb-banco-tiempo">
                <div class="fvb-banco-tiempo__saldo">
                    <span class="fvb-banco-tiempo__value"><?php echo esc_html(number_format(abs($saldo), 1)); ?></span>
                    <span class="fvb-banco-tiempo__label"><?php esc_html_e('Horas disponibles', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="fvb-widget__quick-actions">
                    <a href="<?php echo esc_url(home_url('/banco-tiempo/ofrecer/')); ?>" class="fvb-quick-action">
                        <span class="dashicons dashicons-heart"></span>
                        <?php esc_html_e('Ofrecer', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url(home_url('/banco-tiempo/')); ?>" class="fvb-quick-action">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('Buscar', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
            <?php
        }, 'banco-tiempo');
    }

    /**
     * Renderizar widget de Colectivos
     */
    public function renderizar_widget_colectivos($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
        $tabla_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';
        $colectivos = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_colectivos) && Flavor_Chat_Helpers::tabla_existe($tabla_miembros)) {
            // Obtener columnas disponibles
            $columnas_colectivos = $wpdb->get_col("SHOW COLUMNS FROM $tabla_colectivos");
            $columnas_miembros = $wpdb->get_col("SHOW COLUMNS FROM $tabla_miembros");

            $col_usuario_miembro = in_array('user_id', $columnas_miembros) ? 'user_id' : 'usuario_id';

            $select_cols = ['c.id'];
            if (in_array('nombre', $columnas_colectivos)) $select_cols[] = 'c.nombre';
            else $select_cols[] = "'Colectivo' as nombre";

            $select_cols[] = "(SELECT COUNT(*) FROM $tabla_miembros WHERE colectivo_id = c.id) as miembros";
            $select = implode(', ', $select_cols);

            $where = "m.$col_usuario_miembro = %d";
            if (in_array('estado', $columnas_colectivos)) $where .= " AND c.estado = 'activo'";

            $colectivos = $wpdb->get_results($wpdb->prepare(
                "SELECT $select
                 FROM $tabla_colectivos c
                 INNER JOIN $tabla_miembros m ON c.id = m.colectivo_id
                 WHERE $where
                 ORDER BY c.nombre ASC LIMIT 5",
                $id_usuario
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($colectivos) {
            if (empty($colectivos)) {
                $this->render_empty_state(__('No perteneces a ningún colectivo', 'flavor-chat-ia'), home_url('/colectivos/'), __('Explorar', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($colectivos as $colectivo): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-groups"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($colectivo->nombre); ?></strong>
                            <span class="fvb-list__meta"><?php printf(__('%d miembros', 'flavor-chat-ia'), $colectivo->miembros); ?></span>
                        </div>
                        <a href="<?php echo esc_url(home_url('/colectivos/' . $colectivo->id . '/')); ?>" class="fvb-btn-sm">
                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'colectivos');
    }

    /**
     * Renderizar widget de Estado de Socio
     */
    public function renderizar_widget_socios($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_socios';
        $socio = null;

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = [];

            if (in_array('numero_socio', $columnas)) $select_cols[] = 'numero_socio';
            elseif (in_array('numero', $columnas)) $select_cols[] = 'numero as numero_socio';
            else $select_cols[] = 'id as numero_socio';

            if (in_array('tipo', $columnas)) $select_cols[] = 'tipo';
            else $select_cols[] = "'standard' as tipo";

            if (in_array('estado', $columnas)) $select_cols[] = 'estado';
            else $select_cols[] = "'activo' as estado";

            if (in_array('fecha_alta', $columnas)) $select_cols[] = 'fecha_alta';
            elseif (in_array('created_at', $columnas)) $select_cols[] = 'created_at as fecha_alta';
            else $select_cols[] = 'NULL as fecha_alta';

            if (in_array('fecha_renovacion', $columnas)) $select_cols[] = 'fecha_renovacion';
            elseif (in_array('fecha_vencimiento', $columnas)) $select_cols[] = 'fecha_vencimiento as fecha_renovacion';
            else $select_cols[] = 'NULL as fecha_renovacion';

            $select = implode(', ', $select_cols);
            $col_usuario = in_array('user_id', $columnas) ? 'user_id' : (in_array('usuario_id', $columnas) ? 'usuario_id' : null);
            if (!$col_usuario) { return; } // Sin columna de usuario, salir

            $socio = $wpdb->get_row($wpdb->prepare(
                "SELECT $select FROM $tabla WHERE $col_usuario = %d",
                $id_usuario
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($socio) {
            if (!$socio) {
                $this->render_empty_state(__('No eres socio', 'flavor-chat-ia'), home_url('/socios/alta/'), __('Hacerse socio', 'flavor-chat-ia'));
                return;
            }
            ?>
            <div class="fvb-socio-card">
                <div class="fvb-socio-card__badge fvb-socio-card__badge--<?php echo esc_attr($socio->estado); ?>">
                    <span class="dashicons dashicons-id-alt"></span>
                    <span><?php echo esc_html(ucfirst($socio->estado)); ?></span>
                </div>
                <div class="fvb-socio-card__info">
                    <div class="fvb-socio-stat">
                        <span class="fvb-socio-stat__label"><?php esc_html_e('Nº Socio', 'flavor-chat-ia'); ?></span>
                        <span class="fvb-socio-stat__value"><?php echo esc_html($socio->numero_socio); ?></span>
                    </div>
                    <div class="fvb-socio-stat">
                        <span class="fvb-socio-stat__label"><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></span>
                        <span class="fvb-socio-stat__value"><?php echo esc_html(ucfirst($socio->tipo)); ?></span>
                    </div>
                    <?php if ($socio->fecha_renovacion): ?>
                        <div class="fvb-socio-stat">
                            <span class="fvb-socio-stat__label"><?php esc_html_e('Renovación', 'flavor-chat-ia'); ?></span>
                            <span class="fvb-socio-stat__value"><?php echo esc_html(date_i18n('d/m/Y', strtotime($socio->fecha_renovacion))); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="<?php echo esc_url(home_url('/socios/')); ?>" class="fvb-btn">
                    <?php esc_html_e('Ver detalles', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php
        }, 'socios');
    }

    /**
     * Renderizar widget de Estadísticas Personales
     */
    public function renderizar_widget_estadisticas($widget_id, $config, $data, $id_usuario) {
        $puntos = (int) get_user_meta($id_usuario, 'flavor_user_points', true);
        $participaciones = apply_filters('flavor_user_participations_count', 0, $id_usuario);
        $reservas = apply_filters('flavor_user_reservations_count', 0, $id_usuario);

        $this->render_widget_wrapper($widget_id, $config, function() use ($puntos, $participaciones, $reservas) {
            ?>
            <div class="fvb-stats-grid">
                <div class="fvb-stat-card">
                    <span class="fvb-stat-card__icon dashicons dashicons-star-filled"></span>
                    <span class="fvb-stat-card__value"><?php echo esc_html(number_format($puntos)); ?></span>
                    <span class="fvb-stat-card__label"><?php esc_html_e('Puntos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="fvb-stat-card">
                    <span class="fvb-stat-card__icon dashicons dashicons-groups"></span>
                    <span class="fvb-stat-card__value"><?php echo esc_html($participaciones); ?></span>
                    <span class="fvb-stat-card__label"><?php esc_html_e('Participaciones', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="fvb-stat-card">
                    <span class="fvb-stat-card__icon dashicons dashicons-calendar-alt"></span>
                    <span class="fvb-stat-card__value"><?php echo esc_html($reservas); ?></span>
                    <span class="fvb-stat-card__label"><?php esc_html_e('Reservas', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <a href="<?php echo esc_url(home_url('/mi-portal/estadisticas/')); ?>" class="fvb-btn fvb-btn--outline">
                <?php esc_html_e('Ver estadísticas completas', 'flavor-chat-ia'); ?>
            </a>
            <?php
        }, 'estadisticas');
    }

    /**
     * Renderizar widget de Biblioteca
     */
    public function renderizar_widget_biblioteca($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $prestamos_activos = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_prestamos)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla_prestamos");
            $col_usuario = in_array('user_id', $columnas) ? 'user_id' : (in_array('usuario_id', $columnas) ? 'usuario_id' : null);
            if (!$col_usuario) { return; } // Sin columna de usuario, salir

            $where = "$col_usuario = %d";
            if (in_array('estado', $columnas)) {
                $where .= " AND estado = 'activo'";
            } elseif (in_array('devuelto', $columnas)) {
                $where .= " AND devuelto = 0";
            }

            $prestamos_activos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_prestamos WHERE $where",
                $id_usuario
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($prestamos_activos) {
            ?>
            <div class="fvb-biblioteca-stats">
                <div class="fvb-stat-box">
                    <span class="fvb-stat-box__icon dashicons dashicons-book"></span>
                    <span class="fvb-stat-box__value"><?php echo esc_html($prestamos_activos); ?></span>
                    <span class="fvb-stat-box__label"><?php esc_html_e('Préstamos activos', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <div class="fvb-widget__quick-actions">
                <a href="<?php echo esc_url(home_url('/biblioteca/')); ?>" class="fvb-quick-action">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Buscar', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/biblioteca/mis-prestamos/')); ?>" class="fvb-quick-action">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Mis préstamos', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php
        }, 'biblioteca');
    }

    /**
     * Renderizar widget de Bares y Restaurantes
     */
    public function renderizar_widget_bares($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_bares';
        $bares = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            if (in_array('nombre', $columnas)) $select_cols[] = 'nombre';
            elseif (in_array('titulo', $columnas)) $select_cols[] = 'titulo as nombre';
            else $select_cols[] = "'Local' as nombre";

            if (in_array('tipo', $columnas)) $select_cols[] = 'tipo';
            elseif (in_array('categoria', $columnas)) $select_cols[] = 'categoria as tipo';
            else $select_cols[] = "'Restaurante' as tipo";

            if (in_array('direccion', $columnas)) $select_cols[] = 'direccion';
            else $select_cols[] = "'' as direccion";

            $select = implode(', ', $select_cols);
            $where = in_array('estado', $columnas) ? "WHERE estado = 'activo'" : "WHERE 1=1";

            $bares = $wpdb->get_results(
                "SELECT $select FROM $tabla $where ORDER BY RAND() LIMIT 4"
            );
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($bares) {
            if (empty($bares)) {
                $this->render_empty_state(__('No hay locales registrados', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($bares as $bar): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-food"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($bar->nombre); ?></strong>
                            <span class="fvb-list__meta"><?php echo esc_html($bar->tipo); ?> - <?php echo esc_html($bar->direccion); ?></span>
                        </div>
                        <a href="<?php echo esc_url(home_url('/bares/' . $bar->id . '/')); ?>" class="fvb-btn-sm">
                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'bares');
    }

    /**
     * Renderizar widget de Red Social
     */
    public function renderizar_widget_red_social($widget_id, $config, $data, $id_usuario) {
        $this->render_widget_wrapper($widget_id, $config, function() use ($id_usuario) {
            ?>
            <div class="fvb-red-social">
                <div class="fvb-widget__quick-actions">
                    <a href="<?php echo esc_url(home_url('/red-social/')); ?>" class="fvb-quick-action">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php esc_html_e('Mi feed', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url(home_url('/red-social/publicar/')); ?>" class="fvb-quick-action">
                        <span class="dashicons dashicons-plus"></span>
                        <?php esc_html_e('Publicar', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
            <?php
        }, 'red-social');
    }

    /**
     * Renderizar widget de Recursos Compartidos
     */
    public function renderizar_widget_recursos_compartidos($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_recursos_compartidos';
        $recursos = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            if (in_array('titulo', $columnas)) $select_cols[] = 'titulo';
            elseif (in_array('nombre', $columnas)) $select_cols[] = 'nombre as titulo';
            else $select_cols[] = "'Recurso' as titulo";

            if (in_array('tipo', $columnas)) $select_cols[] = 'tipo';
            else $select_cols[] = "'general' as tipo";

            $select = implode(', ', $select_cols);
            $order_date = in_array('created_at', $columnas) ? 'created_at' : (in_array('fecha', $columnas) ? 'fecha' : 'id');
            $where = in_array('estado', $columnas) ? "WHERE estado = 'disponible'" : "WHERE 1=1";

            $recursos = $wpdb->get_results(
                "SELECT $select FROM $tabla $where ORDER BY $order_date DESC LIMIT 5"
            );
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($recursos) {
            if (empty($recursos)) {
                $this->render_empty_state(__('No hay recursos disponibles', 'flavor-chat-ia'), home_url('/recursos/nuevo/'), __('Compartir', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($recursos as $recurso): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-share"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($recurso->titulo); ?></strong>
                            <span class="fvb-list__meta"><?php echo esc_html($recurso->tipo); ?></span>
                        </div>
                        <a href="<?php echo esc_url(home_url('/recursos/' . $recurso->id . '/')); ?>" class="fvb-btn-sm">
                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'recursos');
    }

    /**
     * Renderizar widget de Notificaciones
     */
    public function renderizar_widget_notificaciones($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_notificaciones';
        $notificaciones = [];
        $cantidad = $data['cantidad'] ?? 5;
        $solo_sin_leer = $data['solo_sin_leer'] ?? true;

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $col_usuario = in_array('user_id', $columnas) ? 'user_id' : (in_array('usuario_id', $columnas) ? 'usuario_id' : null);
            if (!$col_usuario) { return; } // Sin columna de usuario, salir
            $col_fecha = in_array('created_at', $columnas) ? 'created_at' : (in_array('fecha', $columnas) ? 'fecha' : 'id');

            $select_cols = ['id'];
            if (in_array('titulo', $columnas)) $select_cols[] = 'titulo';
            elseif (in_array('mensaje', $columnas)) $select_cols[] = 'SUBSTRING(mensaje, 1, 50) as titulo';
            else $select_cols[] = "'Notificación' as titulo";

            $select_cols[] = $col_fecha . ' as created_at';
            $select = implode(', ', $select_cols);

            $where = "$col_usuario = $id_usuario";
            if ($solo_sin_leer && in_array('leida', $columnas)) {
                $where .= " AND leida = 0";
            }

            $notificaciones = $wpdb->get_results(
                "SELECT $select FROM $tabla WHERE $where ORDER BY $col_fecha DESC LIMIT $cantidad"
            );
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($notificaciones) {
            if (empty($notificaciones)) {
                $this->render_empty_state(__('No hay notificaciones', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($notificaciones as $notif): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-bell"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($notif->titulo); ?></strong>
                            <span class="fvb-list__meta"><?php echo esc_html(human_time_diff(strtotime($notif->created_at))); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, '');
    }

    /**
     * Renderizar widget de Puntos y Nivel
     */
    public function renderizar_widget_puntos($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla_puntos = $wpdb->prefix . 'flavor_puntos_usuario';
        $puntos = 0;
        $nivel = 1;

        if (Flavor_Chat_Helpers::tabla_existe($tabla_puntos)) {
            $puntos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(puntos), 0) FROM $tabla_puntos WHERE usuario_id = %d",
                $id_usuario
            ));
        }

        // Calcular nivel
        $niveles = [0 => 1, 100 => 2, 500 => 3, 1500 => 4, 5000 => 5];
        foreach ($niveles as $min_puntos => $niv) {
            if ($puntos >= $min_puntos) {
                $nivel = $niv;
            }
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($puntos, $nivel) {
            ?>
            <div class="fvb-puntos-nivel">
                <div class="fvb-stat-box fvb-stat-box--success">
                    <span class="fvb-stat-box__icon dashicons dashicons-star-filled"></span>
                    <div class="fvb-stat-box__info">
                        <span class="fvb-stat-box__value"><?php echo number_format($puntos); ?></span>
                        <span class="fvb-stat-box__label"><?php esc_html_e('Puntos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="fvb-stat-box fvb-stat-box--primary">
                    <span class="fvb-stat-box__icon dashicons dashicons-awards"></span>
                    <div class="fvb-stat-box__info">
                        <span class="fvb-stat-box__value"><?php echo esc_html($nivel); ?></span>
                        <span class="fvb-stat-box__label"><?php esc_html_e('Nivel', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>
            <?php
        }, 'gamificacion');
    }

    /**
     * Renderizar widget de Mis Reservas
     */
    public function renderizar_widget_reservas($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios';
        $reservas = [];
        $cantidad = $data['cantidad'] ?? 5;

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Verificar si la tabla de espacios existe para hacer JOIN
            if (Flavor_Chat_Helpers::tabla_existe($tabla_espacios)) {
                $reservas = $wpdb->get_results($wpdb->prepare(
                    "SELECT r.id, r.fecha_inicio, r.estado, e.nombre as espacio_nombre
                     FROM $tabla r
                     LEFT JOIN $tabla_espacios e ON r.espacio_id = e.id
                     WHERE r.usuario_id = %d AND r.fecha_inicio >= NOW()
                     ORDER BY r.fecha_inicio ASC LIMIT %d",
                    $id_usuario, $cantidad
                ));
            } else {
                // Sin JOIN, obtener solo datos de reservas
                $reservas = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, fecha_inicio, estado, espacio_id
                     FROM $tabla
                     WHERE usuario_id = %d AND fecha_inicio >= NOW()
                     ORDER BY fecha_inicio ASC LIMIT %d",
                    $id_usuario, $cantidad
                ));
            }
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($reservas) {
            if (empty($reservas)) {
                $this->render_empty_state(__('No tienes reservas próximas', 'flavor-chat-ia'), home_url('/espacios/'), __('Reservar', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($reservas as $reserva): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-calendar-alt"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($reserva->espacio_nombre ?? __('Espacio', 'flavor-chat-ia')); ?></strong>
                            <span class="fvb-list__meta">
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($reserva->fecha_inicio))); ?>
                            </span>
                        </div>
                        <span class="fvb-badge fvb-badge--<?php echo $reserva->estado === 'confirmada' ? 'success' : 'warning'; ?>">
                            <?php echo esc_html(ucfirst($reserva->estado)); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'espacios-comunes');
    }

    /**
     * Renderizar widget de Bicicletas
     */
    public function renderizar_widget_bicicletas($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_alquileres = $wpdb->prefix . 'flavor_bicicletas_alquileres';
        $disponibles = 0;
        $mis_alquileres = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $disponibles = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'disponible'");

            // Verificar si existe la tabla de alquileres
            if (Flavor_Chat_Helpers::tabla_existe($tabla_alquileres)) {
                $mis_alquileres = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_alquileres
                     WHERE usuario_id = %d AND estado = 'activo'",
                    $id_usuario
                ));
            }
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($disponibles, $mis_alquileres) {
            ?>
            <div class="fvb-grid fvb-grid--2">
                <div class="fvb-stat-box fvb-stat-box--success">
                    <span class="fvb-stat-box__icon dashicons dashicons-image-rotate"></span>
                    <div class="fvb-stat-box__info">
                        <span class="fvb-stat-box__value"><?php echo esc_html($disponibles); ?></span>
                        <span class="fvb-stat-box__label"><?php esc_html_e('Disponibles', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="fvb-stat-box fvb-stat-box--info">
                    <span class="fvb-stat-box__icon dashicons dashicons-unlock"></span>
                    <div class="fvb-stat-box__info">
                        <span class="fvb-stat-box__value"><?php echo esc_html($mis_alquileres); ?></span>
                        <span class="fvb-stat-box__label"><?php esc_html_e('Mis alquileres', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>
            <?php
        }, 'bicicletas-compartidas');
    }

    /**
     * Renderizar widget de Parkings
     */
    public function renderizar_widget_parkings($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_parkings';
        $plazas_libres = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Intentar obtener plazas libres - usar columnas que existan
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            if (in_array('plazas_disponibles', $columnas)) {
                $plazas_libres = (int) $wpdb->get_var("SELECT COALESCE(SUM(plazas_disponibles), 0) FROM $tabla WHERE estado = 'activo'");
            } elseif (in_array('plazas_libres', $columnas)) {
                $plazas_libres = (int) $wpdb->get_var("SELECT COALESCE(SUM(plazas_libres), 0) FROM $tabla WHERE estado = 'activo'");
            } else {
                // Contar parkings activos como fallback
                $plazas_libres = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'activo'");
            }
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($plazas_libres) {
            ?>
            <div class="fvb-stat-box fvb-stat-box--<?php echo $plazas_libres > 0 ? 'success' : 'warning'; ?>">
                <span class="fvb-stat-box__icon dashicons dashicons-car"></span>
                <div class="fvb-stat-box__info">
                    <span class="fvb-stat-box__value"><?php echo esc_html($plazas_libres); ?></span>
                    <span class="fvb-stat-box__label"><?php esc_html_e('Plazas disponibles', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <?php
        }, 'parkings');
    }

    /**
     * Renderizar widget de Carpooling
     */
    public function renderizar_widget_carpooling($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_carpooling_viajes';
        $viajes = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            if (in_array('origen', $columnas)) $select_cols[] = 'origen';
            else $select_cols[] = "'Origen' as origen";

            if (in_array('destino', $columnas)) $select_cols[] = 'destino';
            else $select_cols[] = "'Destino' as destino";

            if (in_array('fecha_salida', $columnas)) $select_cols[] = 'fecha_salida';
            elseif (in_array('fecha', $columnas)) $select_cols[] = 'fecha as fecha_salida';
            else $select_cols[] = 'NOW() as fecha_salida';

            if (in_array('plazas_disponibles', $columnas)) $select_cols[] = 'plazas_disponibles';
            elseif (in_array('plazas', $columnas)) $select_cols[] = 'plazas as plazas_disponibles';
            else $select_cols[] = '1 as plazas_disponibles';

            $select = implode(', ', $select_cols);
            $col_fecha = in_array('fecha_salida', $columnas) ? 'fecha_salida' : (in_array('fecha', $columnas) ? 'fecha' : 'id');
            $col_plazas = in_array('plazas_disponibles', $columnas) ? 'plazas_disponibles' : (in_array('plazas', $columnas) ? 'plazas' : '1');

            $where = "$col_fecha >= NOW()";
            if (in_array('estado', $columnas)) $where .= " AND estado = 'activo'";
            $where .= " AND $col_plazas > 0";

            $viajes = $wpdb->get_results(
                "SELECT $select FROM $tabla WHERE $where ORDER BY $col_fecha ASC LIMIT 5"
            );
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($viajes) {
            if (empty($viajes)) {
                $this->render_empty_state(__('No hay viajes disponibles', 'flavor-chat-ia'), home_url('/carpooling/nuevo/'), __('Ofrecer viaje', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($viajes as $viaje): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-car"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($viaje->origen . ' → ' . $viaje->destino); ?></strong>
                            <span class="fvb-list__meta">
                                <?php echo esc_html(date_i18n('j M H:i', strtotime($viaje->fecha_salida))); ?>
                                · <?php printf(esc_html__('%d plazas', 'flavor-chat-ia'), $viaje->plazas_disponibles); ?>
                            </span>
                        </div>
                        <a href="<?php echo esc_url(home_url('/carpooling/' . $viaje->id . '/')); ?>" class="fvb-btn-sm">
                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'carpooling');
    }

    /**
     * Renderizar widget de Calendario de Eventos
     */
    public function renderizar_widget_calendario($widget_id, $config, $data, $id_usuario) {
        $this->render_widget_wrapper($widget_id, $config, function() {
            ?>
            <div class="fvb-calendario-mini">
                <p class="fvb-empty-state"><?php esc_html_e('Calendario disponible en la página completa', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url(home_url('/eventos/calendario/')); ?>" class="fvb-btn fvb-btn--primary">
                    <?php esc_html_e('Ver calendario', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php
        }, 'eventos');
    }

    /**
     * Renderizar widget de Mis Inscripciones
     */
    public function renderizar_widget_inscripciones($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $inscripciones = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla) && Flavor_Chat_Helpers::tabla_existe($tabla_eventos)) {
            // Verificar columnas de la tabla de inscripciones
            $columnas_insc = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $col_usuario = in_array('user_id', $columnas_insc) ? 'user_id' : (in_array('usuario_id', $columnas_insc) ? 'usuario_id' : null);
            if (!$col_usuario) { return; } // Sin columna de usuario, salir

            $inscripciones = $wpdb->get_results($wpdb->prepare(
                "SELECT i.id, i.estado, e.titulo, e.fecha_inicio
                 FROM $tabla i
                 LEFT JOIN $tabla_eventos e ON i.evento_id = e.id
                 WHERE i.$col_usuario = %d AND e.fecha_inicio >= NOW()
                 ORDER BY e.fecha_inicio ASC LIMIT 5",
                $id_usuario
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($inscripciones) {
            if (empty($inscripciones)) {
                $this->render_empty_state(__('No tienes inscripciones', 'flavor-chat-ia'), home_url('/eventos/'), __('Ver eventos', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($inscripciones as $insc): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-yes-alt"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($insc->titulo); ?></strong>
                            <span class="fvb-list__meta">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($insc->fecha_inicio))); ?>
                            </span>
                        </div>
                        <span class="fvb-badge fvb-badge--success"><?php echo esc_html(ucfirst($insc->estado)); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'eventos');
    }

    /**
     * Renderizar widget de Mensajes
     */
    public function renderizar_widget_mensajes($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_mensajes';
        $mensajes = [];
        $sin_leer = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $col_destino = in_array('destinatario_id', $columnas) ? 'destinatario_id' :
                          (in_array('receptor_id', $columnas) ? 'receptor_id' : 'user_id');
            $col_remitente = in_array('remitente_id', $columnas) ? 'remitente_id' :
                            (in_array('emisor_id', $columnas) ? 'emisor_id' : 'from_user_id');
            $col_leido = in_array('leido', $columnas) ? 'leido' : 'read_at';
            $col_fecha = in_array('created_at', $columnas) ? 'created_at' : (in_array('fecha', $columnas) ? 'fecha' : 'id');

            if (in_array('leido', $columnas)) {
                $sin_leer = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE $col_destino = %d AND leido = 0",
                    $id_usuario
                ));
            }

            $select_cols = ['m.id'];
            if (in_array('asunto', $columnas)) $select_cols[] = 'm.asunto';
            elseif (in_array('titulo', $columnas)) $select_cols[] = 'm.titulo as asunto';
            else $select_cols[] = "'Mensaje' as asunto";

            $select_cols[] = "m.$col_fecha as created_at";
            if (in_array('leido', $columnas)) $select_cols[] = 'm.leido';
            else $select_cols[] = '1 as leido';
            $select_cols[] = 'u.display_name as remitente';

            $select = implode(', ', $select_cols);

            $mensajes = $wpdb->get_results($wpdb->prepare(
                "SELECT $select
                 FROM $tabla m
                 LEFT JOIN {$wpdb->users} u ON m.$col_remitente = u.ID
                 WHERE m.$col_destino = %d
                 ORDER BY m.$col_fecha DESC LIMIT 5",
                $id_usuario
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($mensajes, $sin_leer) {
            if ($sin_leer > 0) {
                ?>
                <div class="fvb-alert fvb-alert--info">
                    <span class="dashicons dashicons-email"></span>
                    <?php printf(esc_html__('Tienes %d mensajes sin leer', 'flavor-chat-ia'), $sin_leer); ?>
                </div>
                <?php
            }

            if (empty($mensajes)) {
                $this->render_empty_state(__('No hay mensajes', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($mensajes as $msg): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-email-alt<?php echo !$msg->leido ? ' fvb-list__icon--primary' : ''; ?>"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($msg->asunto ?: __('(Sin asunto)', 'flavor-chat-ia')); ?></strong>
                            <span class="fvb-list__meta"><?php echo esc_html($msg->remitente); ?> · <?php echo esc_html(human_time_diff(strtotime($msg->created_at))); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'chat-interno');
    }

    /**
     * Renderizar widget de Podcast
     */
    public function renderizar_widget_podcast($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_podcast_episodios';
        $episodios = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            // Agregar columnas que existan
            if (in_array('titulo', $columnas)) $select_cols[] = 'titulo';
            if (in_array('nombre', $columnas)) $select_cols[] = 'nombre as titulo';
            if (in_array('duracion', $columnas)) $select_cols[] = 'duracion';
            if (in_array('duracion_segundos', $columnas)) $select_cols[] = 'duracion_segundos as duracion';
            if (in_array('fecha_publicacion', $columnas)) $select_cols[] = 'fecha_publicacion';
            if (in_array('created_at', $columnas)) $select_cols[] = 'created_at as fecha_publicacion';
            if (in_array('fecha', $columnas)) $select_cols[] = 'fecha as fecha_publicacion';

            $select = implode(', ', array_unique($select_cols));
            $where = in_array('estado', $columnas) ? "WHERE estado = 'publicado'" : "WHERE 1=1";
            $order = in_array('fecha_publicacion', $columnas) ? 'fecha_publicacion' : (in_array('created_at', $columnas) ? 'created_at' : 'id');

            $episodios = $wpdb->get_results(
                "SELECT $select FROM $tabla $where ORDER BY $order DESC LIMIT 3"
            );
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($episodios) {
            if (empty($episodios)) {
                $this->render_empty_state(__('No hay episodios', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($episodios as $ep): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-microphone"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($ep->titulo); ?></strong>
                            <span class="fvb-list__meta">
                                <?php if ($ep->duracion): ?><?php echo esc_html(gmdate('i:s', $ep->duracion)); ?> · <?php endif; ?>
                                <?php echo esc_html(date_i18n('j M', strtotime($ep->fecha_publicacion))); ?>
                            </span>
                        </div>
                        <a href="<?php echo esc_url(home_url('/podcast/' . $ep->id . '/')); ?>" class="fvb-btn-sm">
                            <span class="dashicons dashicons-controls-play"></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'podcast');
    }

    /**
     * Renderizar widget de Radio
     */
    public function renderizar_widget_radio($widget_id, $config, $data, $id_usuario) {
        $this->render_widget_wrapper($widget_id, $config, function() {
            ?>
            <div class="fvb-radio-player">
                <div class="fvb-radio-status">
                    <span class="fvb-badge fvb-badge--success"><?php esc_html_e('EN VIVO', 'flavor-chat-ia'); ?></span>
                </div>
                <a href="<?php echo esc_url(home_url('/radio/')); ?>" class="fvb-btn fvb-btn--primary">
                    <span class="dashicons dashicons-controls-volumeon"></span>
                    <?php esc_html_e('Escuchar', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php
        }, 'radio');
    }

    /**
     * Renderizar widget de Multimedia
     */
    public function renderizar_widget_multimedia($widget_id, $config, $data, $id_usuario) {
        $this->render_widget_wrapper($widget_id, $config, function() {
            ?>
            <div class="fvb-multimedia-quick">
                <a href="<?php echo esc_url(home_url('/multimedia/')); ?>" class="fvb-quick-action">
                    <span class="dashicons dashicons-format-gallery"></span>
                    <?php esc_html_e('Ver galería', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/multimedia/subir/')); ?>" class="fvb-quick-action">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e('Subir', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php
        }, 'multimedia');
    }

    /**
     * Renderizar widget de Compostaje
     */
    public function renderizar_widget_compostaje($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_compostaje_aportes';
        $total_kg = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $col_cantidad = in_array('cantidad_kg', $columnas) ? 'cantidad_kg' :
                           (in_array('cantidad', $columnas) ? 'cantidad' :
                           (in_array('peso', $columnas) ? 'peso' : null));
            $col_usuario = in_array('user_id', $columnas) ? 'user_id' : (in_array('usuario_id', $columnas) ? 'usuario_id' : null);
            if (!$col_usuario) { return; } // Sin columna de usuario, salir

            if ($col_cantidad) {
                $total_kg = (float) $wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM($col_cantidad), 0) FROM $tabla WHERE $col_usuario = %d",
                    $id_usuario
                ));
            }
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($total_kg) {
            ?>
            <div class="fvb-stat-box fvb-stat-box--success">
                <span class="fvb-stat-box__icon dashicons dashicons-admin-site-alt3"></span>
                <div class="fvb-stat-box__info">
                    <span class="fvb-stat-box__value"><?php echo number_format($total_kg, 1); ?> kg</span>
                    <span class="fvb-stat-box__label"><?php esc_html_e('Aportado', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <?php
        }, 'compostaje');
    }

    /**
     * Renderizar widget de Tienda Local
     */
    public function renderizar_widget_tienda($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tienda_productos';
        $productos = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            if (in_array('nombre', $columnas)) $select_cols[] = 'nombre';
            elseif (in_array('titulo', $columnas)) $select_cols[] = 'titulo as nombre';
            else $select_cols[] = "'Producto' as nombre";

            if (in_array('precio', $columnas)) $select_cols[] = 'precio';
            elseif (in_array('precio_venta', $columnas)) $select_cols[] = 'precio_venta as precio';
            else $select_cols[] = '0 as precio';

            $select = implode(', ', $select_cols);
            $where = in_array('estado', $columnas) ? "WHERE estado = 'disponible'" : "WHERE 1=1";

            $productos = $wpdb->get_results(
                "SELECT $select FROM $tabla $where ORDER BY RAND() LIMIT 4"
            );
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($productos) {
            if (empty($productos)) {
                $this->render_empty_state(__('No hay productos', 'flavor-chat-ia'));
                return;
            }
            ?>
            <div class="fvb-product-grid">
                <?php foreach ($productos as $prod): ?>
                    <a href="<?php echo esc_url(home_url('/tienda/producto/' . $prod->id . '/')); ?>" class="fvb-product">
                        <div class="fvb-product__img"></div>
                        <span class="fvb-product__name"><?php echo esc_html($prod->nombre); ?></span>
                        <span class="fvb-product__price"><?php echo esc_html(number_format($prod->precio, 2)); ?>€</span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php
        }, 'tienda-local');
    }

    /**
     * Renderizar widget de Mis Pedidos
     */
    public function renderizar_widget_pedidos($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tienda_pedidos';
        $pedidos = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            if (in_array('numero_pedido', $columnas)) $select_cols[] = 'numero_pedido';
            elseif (in_array('numero', $columnas)) $select_cols[] = 'numero as numero_pedido';
            else $select_cols[] = 'id as numero_pedido';

            if (in_array('estado', $columnas)) $select_cols[] = 'estado';
            else $select_cols[] = "'pendiente' as estado";

            if (in_array('total', $columnas)) $select_cols[] = 'total';
            elseif (in_array('importe', $columnas)) $select_cols[] = 'importe as total';
            else $select_cols[] = '0 as total';

            $select = implode(', ', $select_cols);
            $col_usuario = in_array('user_id', $columnas) ? 'user_id' : (in_array('usuario_id', $columnas) ? 'usuario_id' : null);
            if (!$col_usuario) { return; } // Sin columna de usuario, salir
            $col_fecha = in_array('created_at', $columnas) ? 'created_at' : (in_array('fecha', $columnas) ? 'fecha' : 'id');

            $pedidos = $wpdb->get_results($wpdb->prepare(
                "SELECT $select FROM $tabla WHERE $col_usuario = %d ORDER BY $col_fecha DESC LIMIT 5",
                $id_usuario
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($pedidos) {
            if (empty($pedidos)) {
                $this->render_empty_state(__('No tienes pedidos', 'flavor-chat-ia'), home_url('/tienda/'), __('Ver tienda', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($pedidos as $pedido): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-products"></span>
                        <div class="fvb-list__content">
                            <strong>#<?php echo esc_html($pedido->numero_pedido); ?></strong>
                            <span class="fvb-list__meta"><?php echo esc_html(number_format($pedido->total, 2)); ?>€</span>
                        </div>
                        <span class="fvb-badge fvb-badge--<?php echo $pedido->estado === 'entregado' ? 'success' : 'info'; ?>">
                            <?php echo esc_html(ucfirst($pedido->estado)); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'tienda-local');
    }

    /**
     * Renderizar widget de Grupos de Consumo
     */
    public function renderizar_widget_grupos_consumo($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_grupos_consumo';
        $tabla_miembros = $wpdb->prefix . 'flavor_grupos_consumo_miembros';
        $grupos = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla) && Flavor_Chat_Helpers::tabla_existe($tabla_miembros)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $columnas_miembros = $wpdb->get_col("SHOW COLUMNS FROM $tabla_miembros");

            $select_cols = ['g.id'];
            if (in_array('nombre', $columnas)) $select_cols[] = 'g.nombre';
            else $select_cols[] = "'Grupo' as nombre";

            if (in_array('proximo_reparto', $columnas)) $select_cols[] = 'g.proximo_reparto';
            elseif (in_array('fecha_reparto', $columnas)) $select_cols[] = 'g.fecha_reparto as proximo_reparto';
            else $select_cols[] = 'NULL as proximo_reparto';

            $select = implode(', ', $select_cols);
            $col_usuario_miembro = in_array('user_id', $columnas_miembros) ? 'user_id' : 'usuario_id';
            $order = in_array('proximo_reparto', $columnas) ? 'g.proximo_reparto' : (in_array('fecha_reparto', $columnas) ? 'g.fecha_reparto' : 'g.id');

            $where = "m.$col_usuario_miembro = %d";
            if (in_array('estado', $columnas)) $where .= " AND g.estado = 'activo'";

            $grupos = $wpdb->get_results($wpdb->prepare(
                "SELECT $select
                 FROM $tabla g
                 INNER JOIN $tabla_miembros m ON g.id = m.grupo_id
                 WHERE $where
                 ORDER BY $order ASC LIMIT 3",
                $id_usuario
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($grupos) {
            if (empty($grupos)) {
                $this->render_empty_state(__('No perteneces a ningún grupo', 'flavor-chat-ia'), home_url('/grupos-consumo/'), __('Ver grupos', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($grupos as $grupo): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-groups"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($grupo->nombre); ?></strong>
                            <?php if ($grupo->proximo_reparto): ?>
                                <span class="fvb-list__meta">
                                    <?php esc_html_e('Reparto:', 'flavor-chat-ia'); ?> <?php echo esc_html(date_i18n('j M', strtotime($grupo->proximo_reparto))); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'grupos-consumo');
    }

    /**
     * Renderizar widget de Facturas
     */
    public function renderizar_widget_facturas($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_facturas';
        $facturas = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            // Agregar columnas que existan
            if (in_array('numero', $columnas)) $select_cols[] = 'numero';
            elseif (in_array('numero_factura', $columnas)) $select_cols[] = 'numero_factura as numero';
            elseif (in_array('referencia', $columnas)) $select_cols[] = 'referencia as numero';
            else $select_cols[] = 'id as numero';

            if (in_array('total', $columnas)) $select_cols[] = 'total';
            elseif (in_array('importe', $columnas)) $select_cols[] = 'importe as total';
            else $select_cols[] = '0 as total';

            if (in_array('estado', $columnas)) $select_cols[] = 'estado';
            else $select_cols[] = "'pendiente' as estado";

            if (in_array('fecha', $columnas)) $select_cols[] = 'fecha';
            elseif (in_array('created_at', $columnas)) $select_cols[] = 'created_at as fecha';
            elseif (in_array('fecha_emision', $columnas)) $select_cols[] = 'fecha_emision as fecha';
            else $select_cols[] = 'NOW() as fecha';

            $select = implode(', ', $select_cols);
            $col_usuario = in_array('user_id', $columnas) ? 'user_id' : (in_array('usuario_id', $columnas) ? 'usuario_id' : null);
            if (!$col_usuario) { return; } // Sin columna de usuario, salir
            $order = in_array('fecha', $columnas) ? 'fecha' : (in_array('created_at', $columnas) ? 'created_at' : 'id');

            $facturas = $wpdb->get_results($wpdb->prepare(
                "SELECT $select FROM $tabla WHERE $col_usuario = %d ORDER BY $order DESC LIMIT 5",
                $id_usuario
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($facturas) {
            if (empty($facturas)) {
                $this->render_empty_state(__('No tienes facturas', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($facturas as $factura): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-media-text"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($factura->numero); ?></strong>
                            <span class="fvb-list__meta"><?php echo esc_html(number_format($factura->total, 2)); ?>€ · <?php echo esc_html(date_i18n('j M Y', strtotime($factura->fecha))); ?></span>
                        </div>
                        <span class="fvb-badge fvb-badge--<?php echo $factura->estado === 'pagada' ? 'success' : 'warning'; ?>">
                            <?php echo esc_html(ucfirst($factura->estado)); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'facturas');
    }

    /**
     * Renderizar widget de Participación Ciudadana
     */
    public function renderizar_widget_participacion($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_participacion_procesos';
        $procesos = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            if (in_array('titulo', $columnas)) $select_cols[] = 'titulo';
            elseif (in_array('nombre', $columnas)) $select_cols[] = 'nombre as titulo';
            else $select_cols[] = "'Proceso' as titulo";

            if (in_array('estado', $columnas)) $select_cols[] = 'estado';
            else $select_cols[] = "'activo' as estado";

            if (in_array('fecha_fin', $columnas)) $select_cols[] = 'fecha_fin';
            elseif (in_array('fecha_cierre', $columnas)) $select_cols[] = 'fecha_cierre as fecha_fin';
            else $select_cols[] = 'NULL as fecha_fin';

            $select = implode(', ', $select_cols);
            $where = in_array('estado', $columnas) ? "WHERE estado = 'activo'" : "WHERE 1=1";
            $order = in_array('fecha_fin', $columnas) ? 'fecha_fin' : (in_array('fecha_cierre', $columnas) ? 'fecha_cierre' : 'id');

            $procesos = $wpdb->get_results(
                "SELECT $select FROM $tabla $where ORDER BY $order ASC LIMIT 3"
            );
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($procesos) {
            if (empty($procesos)) {
                $this->render_empty_state(__('No hay procesos activos', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($procesos as $proceso): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-megaphone"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($proceso->titulo); ?></strong>
                            <span class="fvb-list__meta">
                                <?php esc_html_e('Hasta:', 'flavor-chat-ia'); ?> <?php echo esc_html(date_i18n('j M', strtotime($proceso->fecha_fin))); ?>
                            </span>
                        </div>
                        <a href="<?php echo esc_url(home_url('/participacion/' . $proceso->id . '/')); ?>" class="fvb-btn-sm">
                            <?php esc_html_e('Participar', 'flavor-chat-ia'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'participacion');
    }

    /**
     * Renderizar widget de Presupuestos Participativos
     */
    public function renderizar_widget_presupuestos($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_presupuestos_propuestas';
        $propuestas = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            if (in_array('titulo', $columnas)) $select_cols[] = 'titulo';
            elseif (in_array('nombre', $columnas)) $select_cols[] = 'nombre as titulo';
            else $select_cols[] = "'Propuesta' as titulo";

            if (in_array('votos', $columnas)) $select_cols[] = 'votos';
            elseif (in_array('num_votos', $columnas)) $select_cols[] = 'num_votos as votos';
            else $select_cols[] = '0 as votos';

            if (in_array('presupuesto', $columnas)) $select_cols[] = 'presupuesto';
            elseif (in_array('importe', $columnas)) $select_cols[] = 'importe as presupuesto';
            elseif (in_array('coste', $columnas)) $select_cols[] = 'coste as presupuesto';
            else $select_cols[] = '0 as presupuesto';

            $select = implode(', ', $select_cols);
            $where = in_array('estado', $columnas) ? "WHERE estado = 'votacion'" : "WHERE 1=1";
            $order = in_array('votos', $columnas) ? 'votos' : (in_array('num_votos', $columnas) ? 'num_votos' : 'id');

            $propuestas = $wpdb->get_results(
                "SELECT $select FROM $tabla $where ORDER BY $order DESC LIMIT 3"
            );
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($propuestas) {
            if (empty($propuestas)) {
                $this->render_empty_state(__('No hay propuestas en votación', 'flavor-chat-ia'), home_url('/presupuestos/nuevo/'), __('Proponer', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($propuestas as $prop): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-money-alt"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($prop->titulo); ?></strong>
                            <span class="fvb-list__meta">
                                <?php echo esc_html(number_format($prop->presupuesto)); ?>€ · <?php echo esc_html($prop->votos); ?> <?php esc_html_e('votos', 'flavor-chat-ia'); ?>
                            </span>
                        </div>
                        <a href="<?php echo esc_url(home_url('/presupuestos/' . $prop->id . '/')); ?>" class="fvb-btn-sm">
                            <?php esc_html_e('Votar', 'flavor-chat-ia'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'presupuestos-participativos');
    }

    /**
     * Renderizar widget de Incidencias
     */
    public function renderizar_widget_incidencias($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_incidencias';
        $incidencias = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            // Agregar columnas que existan
            if (in_array('titulo', $columnas)) $select_cols[] = 'titulo';
            elseif (in_array('nombre', $columnas)) $select_cols[] = 'nombre as titulo';
            elseif (in_array('descripcion', $columnas)) $select_cols[] = 'SUBSTRING(descripcion, 1, 50) as titulo';
            else $select_cols[] = "'Incidencia' as titulo";

            if (in_array('estado', $columnas)) $select_cols[] = 'estado';
            else $select_cols[] = "'pendiente' as estado";

            if (in_array('created_at', $columnas)) $select_cols[] = 'created_at';
            elseif (in_array('fecha', $columnas)) $select_cols[] = 'fecha as created_at';
            elseif (in_array('fecha_creacion', $columnas)) $select_cols[] = 'fecha_creacion as created_at';
            else $select_cols[] = 'NOW() as created_at';

            $select = implode(', ', $select_cols);
            $col_usuario = in_array('user_id', $columnas) ? 'user_id' : (in_array('usuario_id', $columnas) ? 'usuario_id' : null);
            if (!$col_usuario) { return; } // Sin columna de usuario, salir
            $order = in_array('created_at', $columnas) ? 'created_at' : (in_array('fecha', $columnas) ? 'fecha' : 'id');

            $incidencias = $wpdb->get_results($wpdb->prepare(
                "SELECT $select FROM $tabla WHERE $col_usuario = %d ORDER BY $order DESC LIMIT 5",
                $id_usuario
            ));
        }

        $estados_colores = [
            'pendiente' => 'warning',
            'en_proceso' => 'info',
            'resuelta' => 'success',
        ];

        $this->render_widget_wrapper($widget_id, $config, function() use ($incidencias, $estados_colores) {
            if (empty($incidencias)) {
                $this->render_empty_state(__('No has reportado incidencias', 'flavor-chat-ia'), home_url('/incidencias/reportar/'), __('Reportar', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($incidencias as $inc): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-warning"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($inc->titulo); ?></strong>
                            <span class="fvb-list__meta"><?php echo esc_html(human_time_diff(strtotime($inc->created_at))); ?></span>
                        </div>
                        <span class="fvb-badge fvb-badge--<?php echo esc_attr($estados_colores[$inc->estado] ?? 'neutral'); ?>">
                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $inc->estado))); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'incidencias');
    }

    /**
     * Renderizar widget de Trámites
     */
    public function renderizar_widget_tramites($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_tramites';
        $tramites = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            // Agregar columnas que existan
            if (in_array('titulo', $columnas)) $select_cols[] = 'titulo';
            elseif (in_array('nombre', $columnas)) $select_cols[] = 'nombre as titulo';
            elseif (in_array('tipo', $columnas)) $select_cols[] = 'tipo as titulo';
            else $select_cols[] = "'Trámite' as titulo";

            if (in_array('estado', $columnas)) $select_cols[] = 'estado';
            else $select_cols[] = "'pendiente' as estado";

            if (in_array('created_at', $columnas)) $select_cols[] = 'created_at';
            elseif (in_array('fecha', $columnas)) $select_cols[] = 'fecha as created_at';
            elseif (in_array('fecha_inicio', $columnas)) $select_cols[] = 'fecha_inicio as created_at';
            else $select_cols[] = 'NOW() as created_at';

            $select = implode(', ', $select_cols);
            $col_usuario = in_array('user_id', $columnas) ? 'user_id' : (in_array('usuario_id', $columnas) ? 'usuario_id' : null);
            if (!$col_usuario) { return; } // Sin columna de usuario, salir
            $order = in_array('created_at', $columnas) ? 'created_at' : (in_array('fecha', $columnas) ? 'fecha' : 'id');

            $tramites = $wpdb->get_results($wpdb->prepare(
                "SELECT $select FROM $tabla WHERE $col_usuario = %d ORDER BY $order DESC LIMIT 5",
                $id_usuario
            ));
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($tramites) {
            if (empty($tramites)) {
                $this->render_empty_state(__('No tienes trámites', 'flavor-chat-ia'), home_url('/tramites/nuevo/'), __('Iniciar trámite', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($tramites as $tramite): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-clipboard"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($tramite->titulo); ?></strong>
                            <span class="fvb-list__meta"><?php echo esc_html(date_i18n('j M Y', strtotime($tramite->created_at))); ?></span>
                        </div>
                        <span class="fvb-badge fvb-badge--info"><?php echo esc_html(ucfirst($tramite->estado)); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'tramites');
    }

    /**
     * Renderizar widget de Ayuda Vecinal
     */
    public function renderizar_widget_ayuda_vecinal($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_ayuda_vecinal';
        $solicitudes = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");
            $select_cols = ['id'];

            if (in_array('titulo', $columnas)) $select_cols[] = 'titulo';
            elseif (in_array('nombre', $columnas)) $select_cols[] = 'nombre as titulo';
            else $select_cols[] = "'Solicitud' as titulo";

            if (in_array('tipo', $columnas)) $select_cols[] = 'tipo';
            else $select_cols[] = "'general' as tipo";

            if (in_array('urgente', $columnas)) $select_cols[] = 'urgente';
            else $select_cols[] = '0 as urgente';

            $select = implode(', ', $select_cols);
            $order_date = in_array('created_at', $columnas) ? 'created_at' : (in_array('fecha', $columnas) ? 'fecha' : 'id');
            $where = in_array('estado', $columnas) ? "WHERE estado = 'activa'" : "WHERE 1=1";

            $solicitudes = $wpdb->get_results(
                "SELECT $select FROM $tabla $where ORDER BY urgente DESC, $order_date DESC LIMIT 5"
            );
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($solicitudes) {
            if (empty($solicitudes)) {
                $this->render_empty_state(__('No hay solicitudes de ayuda', 'flavor-chat-ia'), home_url('/ayuda-vecinal/ofrecer/'), __('Ofrecer ayuda', 'flavor-chat-ia'));
                return;
            }
            ?>
            <ul class="fvb-list">
                <?php foreach ($solicitudes as $sol): ?>
                    <li class="fvb-list__item">
                        <span class="fvb-list__icon dashicons dashicons-heart<?php echo $sol->urgente ? ' fvb-list__icon--danger' : ''; ?>"></span>
                        <div class="fvb-list__content">
                            <strong><?php echo esc_html($sol->titulo); ?></strong>
                            <span class="fvb-list__meta"><?php echo esc_html(ucfirst($sol->tipo)); ?></span>
                        </div>
                        <?php if ($sol->urgente): ?>
                            <span class="fvb-badge fvb-badge--danger"><?php esc_html_e('Urgente', 'flavor-chat-ia'); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }, 'ayuda-vecinal');
    }

    /**
     * Renderizar widget de Mapa Interactivo
     */
    public function renderizar_widget_mapa($widget_id, $config, $data, $id_usuario) {
        $this->render_widget_wrapper($widget_id, $config, function() {
            ?>
            <div class="fvb-mapa-placeholder">
                <span class="dashicons dashicons-location-alt"></span>
                <p><?php esc_html_e('Mapa disponible en página completa', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url(home_url('/mapa/')); ?>" class="fvb-btn fvb-btn--primary">
                    <?php esc_html_e('Ver mapa', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php
        }, '');
    }

    /**
     * Renderizar widget de Gráfico de Actividad
     */
    public function renderizar_widget_grafico_actividad($widget_id, $config, $data, $id_usuario) {
        $this->render_widget_wrapper($widget_id, $config, function() {
            ?>
            <div class="fvb-grafico-placeholder">
                <p><?php esc_html_e('Gráfico de actividad semanal', 'flavor-chat-ia'); ?></p>
                <div class="fvb-progress">
                    <div class="fvb-progress__bar" style="width: 70%;"></div>
                </div>
                <span class="fvb-list__meta"><?php esc_html_e('70% más activo que la semana pasada', 'flavor-chat-ia'); ?></span>
            </div>
            <?php
        }, '');
    }

    /**
     * Renderizar widget de Red de Comunidades
     */
    public function renderizar_widget_red($widget_id, $config, $data, $id_usuario) {
        $this->render_widget_wrapper($widget_id, $config, function() {
            ?>
            <div class="fvb-red-comunidades">
                <a href="<?php echo esc_url(home_url('/comunidades/')); ?>" class="fvb-quick-action">
                    <span class="dashicons dashicons-networking"></span>
                    <?php esc_html_e('Explorar red', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php
        }, 'comunidades');
    }

    /**
     * Renderizar widget de Fichaje
     */
    public function renderizar_widget_fichaje($widget_id, $config, $data, $id_usuario) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_fichajes';
        $fichaje_hoy = null;

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            // Obtener columnas disponibles
            $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla");

            // Verificar qué estructura tiene la tabla
            $tiene_hora_entrada = in_array('hora_entrada', $columnas);
            $tiene_fecha_hora = in_array('fecha_hora', $columnas);
            $tiene_tipo = in_array('tipo', $columnas);

            $col_usuario = in_array('user_id', $columnas) ? 'user_id' : (in_array('usuario_id', $columnas) ? 'usuario_id' : null);
            if (!$col_usuario) { return; } // Sin columna de usuario, salir

            if ($tiene_fecha_hora && $tiene_tipo) {
                // Estructura del módulo fichaje-empleados (tipo + fecha_hora)
                $entrada = $wpdb->get_row($wpdb->prepare(
                    "SELECT fecha_hora as entrada FROM $tabla
                     WHERE $col_usuario = %d AND tipo = 'entrada' AND DATE(fecha_hora) = CURDATE()
                     ORDER BY fecha_hora DESC LIMIT 1",
                    $id_usuario
                ));
                $salida = $wpdb->get_row($wpdb->prepare(
                    "SELECT fecha_hora as salida FROM $tabla
                     WHERE $col_usuario = %d AND tipo = 'salida' AND DATE(fecha_hora) = CURDATE()
                     ORDER BY fecha_hora DESC LIMIT 1",
                    $id_usuario
                ));
                if ($entrada) {
                    $fichaje_hoy = (object) [
                        'entrada' => $entrada->entrada,
                        'salida' => $salida ? $salida->salida : null
                    ];
                }
            } elseif ($tiene_hora_entrada) {
                // Estructura con hora_entrada/hora_salida
                $col_fecha = in_array('fecha', $columnas) ? 'fecha' : 'created_at';
                $fichaje_hoy = $wpdb->get_row($wpdb->prepare(
                    "SELECT hora_entrada as entrada, hora_salida as salida
                     FROM $tabla
                     WHERE $col_usuario = %d AND DATE($col_fecha) = CURDATE()
                     ORDER BY hora_entrada DESC LIMIT 1",
                    $id_usuario
                ));
            }
        }

        $this->render_widget_wrapper($widget_id, $config, function() use ($fichaje_hoy) {
            if ($fichaje_hoy && $fichaje_hoy->entrada && !$fichaje_hoy->salida) {
                ?>
                <div class="fvb-fichaje-activo">
                    <span class="fvb-badge fvb-badge--success"><?php esc_html_e('Fichado', 'flavor-chat-ia'); ?></span>
                    <p><?php esc_html_e('Entrada:', 'flavor-chat-ia'); ?> <?php echo esc_html(date_i18n('H:i', strtotime($fichaje_hoy->entrada))); ?></p>
                    <button class="fvb-btn fvb-btn--primary" data-action="fichar-salida">
                        <?php esc_html_e('Fichar salida', 'flavor-chat-ia'); ?>
                    </button>
                </div>
                <?php
            } else {
                ?>
                <div class="fvb-fichaje">
                    <button class="fvb-btn fvb-btn--primary" data-action="fichar-entrada">
                        <span class="dashicons dashicons-clock"></span>
                        <?php esc_html_e('Fichar entrada', 'flavor-chat-ia'); ?>
                    </button>
                </div>
                <?php
            }
        }, 'fichaje-empleados');
    }

    /**
     * Renderizar widget de Transparencia
     */
    public function renderizar_widget_transparencia($widget_id, $config, $data, $id_usuario) {
        $this->render_widget_wrapper($widget_id, $config, function() {
            ?>
            <div class="fvb-transparencia">
                <a href="<?php echo esc_url(home_url('/transparencia/')); ?>" class="fvb-quick-action">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php esc_html_e('Portal de transparencia', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php
        }, 'transparencia');
    }

    // =========================================================================
    // Helpers para renderizado de widgets
    // =========================================================================

    /**
     * Envolver contenido en estructura de widget estándar
     */
    private function render_widget_wrapper($widget_id, $config, $callback, $module_id = '') {
        $acciones = $this->obtener_acciones_modulo($module_id);
        ?>
        <div class="fvb-widget fvb-widget--<?php echo esc_attr($widget_id); ?> fvb-widget--<?php echo esc_attr($config['tamano'] ?? 'medium'); ?>">
            <div class="fvb-widget__header">
                <span class="fvb-widget__icon dashicons <?php echo esc_attr($config['icon']); ?>"></span>
                <h3 class="fvb-widget__title"><?php echo esc_html($config['label']); ?></h3>
            </div>
            <div class="fvb-widget__body">
                <?php $callback(); ?>
            </div>
            <?php if (!empty($acciones)): ?>
                <div class="fvb-widget__actions">
                    <?php foreach (array_slice($acciones, 0, 3) as $action_id => $action): ?>
                        <?php $this->renderizar_boton_accion($action_id, $action, $module_id); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="fvb-widget__footer">
                <a href="<?php echo esc_url($this->obtener_url_widget($widget_id)); ?>" class="fvb-widget__link">
                    <?php esc_html_e('Ver más', 'flavor-chat-ia'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar estado vacío con opción de acción
     */
    private function render_empty_state($mensaje, $url = '', $texto_boton = '') {
        ?>
        <div class="fvb-empty-state">
            <p><?php echo esc_html($mensaje); ?></p>
            <?php if ($url && $texto_boton): ?>
                <a href="<?php echo esc_url($url); ?>" class="fvb-btn fvb-btn--sm">
                    <?php echo esc_html($texto_boton); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_Dashboard_VB_Widgets::get_instance();
}, 20);
