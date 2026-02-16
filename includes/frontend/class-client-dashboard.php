<?php
/**
 * Dashboard de Cliente Frontend
 *
 * Renderiza un dashboard completo para usuarios con estadisticas,
 * widgets modulares, actividad reciente y accesos rapidos.
 * Los modulos pueden registrar sus propios widgets.
 *
 * @package FlavorChatIA
 * @subpackage Frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del dashboard de cliente
 */
class Flavor_Client_Dashboard {

    /**
     * Instancia singleton
     *
     * @var Flavor_Client_Dashboard|null
     */
    private static $instancia = null;

    /**
     * Indica si el shortcode esta presente en la pagina actual
     *
     * @var bool
     */
    private $shortcode_presente_en_pagina = false;

    /**
     * Widgets registrados por modulos
     *
     * @var array
     */
    private $widgets_registrados = [];

    /**
     * Estadisticas registradas
     *
     * @var array
     */
    private $estadisticas_registradas = [];

    /**
     * Atajos rapidos registrados
     *
     * @var array
     */
    private $atajos_registrados = [];

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Client_Dashboard
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado: registra shortcode, hooks y assets
     */
    private function __construct() {
        add_shortcode('flavor_client_dashboard', [$this, 'render_dashboard']);
        add_action('wp', [$this, 'detectar_shortcode_en_pagina']);
        add_action('wp_enqueue_scripts', [$this, 'encolar_assets_condicional']);

        // Endpoints AJAX
        add_action('wp_ajax_flavor_client_dashboard_stats', [$this, 'ajax_obtener_estadisticas']);
        add_action('wp_ajax_flavor_client_dashboard_widgets', [$this, 'ajax_obtener_widgets']);
        add_action('wp_ajax_flavor_client_dashboard_activity', [$this, 'ajax_obtener_actividad']);
        add_action('wp_ajax_flavor_client_dashboard_notifications', [$this, 'ajax_obtener_notificaciones']);
        add_action('wp_ajax_flavor_client_dashboard_dismiss_notification', [$this, 'ajax_descartar_notificacion']);
        add_action('wp_ajax_flavor_client_dashboard_save_preferences', [$this, 'ajax_guardar_preferencias']);

        // Endpoints AJAX para nuevos widgets
        add_action('wp_ajax_flavor_client_dashboard_network_data', [$this, 'ajax_obtener_datos_red']);
        add_action('wp_ajax_flavor_client_dashboard_shared_resources', [$this, 'ajax_obtener_recursos_compartidos']);
        add_action('wp_ajax_flavor_client_dashboard_map_markers', [$this, 'ajax_obtener_marcadores_mapa']);
        add_action('wp_ajax_flavor_client_dashboard_advanced_stats', [$this, 'ajax_obtener_estadisticas_avanzadas']);

        // Registrar estadisticas y widgets por defecto
        add_action('init', [$this, 'registrar_elementos_por_defecto'], 20);

        // Hook para que modulos registren sus widgets
        add_action('flavor_client_dashboard_init', [$this, 'inicializar_widgets_modulos']);
    }

    /**
     * Detecta si el shortcode esta presente en la pagina actual
     */
    public function detectar_shortcode_en_pagina() {
        global $post;

        if (!$post || !is_singular()) {
            return;
        }

        if (has_shortcode($post->post_content, 'flavor_client_dashboard')) {
            $this->shortcode_presente_en_pagina = true;
        }
    }

    /**
     * Encola CSS y JS solo cuando el shortcode esta presente
     *
     * Sistema de Diseno Unificado v4.1.0
     */
    public function encolar_assets_condicional() {
        if (!$this->shortcode_presente_en_pagina) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';
        $version = FLAVOR_CHAT_IA_VERSION;
        $plugin_url = FLAVOR_CHAT_IA_URL;

        // =====================================================================
        // CSS - Sistema de Diseno Unificado (v4.1.0)
        // =====================================================================

        // 1. Design Tokens (variables CSS base)
        wp_enqueue_style(
            'fl-design-tokens',
            $plugin_url . 'assets/css/design-tokens.css',
            [],
            $version
        );

        // 2. Compatibilidad con variables antiguas
        wp_enqueue_style(
            'fl-design-tokens-compat',
            $plugin_url . 'assets/css/design-tokens-compat.css',
            ['fl-design-tokens'],
            $version
        );

        // 3. CSS Base del dashboard
        wp_enqueue_style(
            'fl-dashboard-base',
            $plugin_url . 'assets/css/dashboard-base.css',
            ['fl-design-tokens-compat'],
            $version
        );

        // 4. Widgets y niveles
        wp_enqueue_style(
            'fl-dashboard-widgets',
            $plugin_url . 'assets/css/dashboard-widgets.css',
            ['fl-dashboard-base'],
            $version
        );

        // 5. Grupos y categorias
        wp_enqueue_style(
            'fl-dashboard-groups',
            $plugin_url . 'assets/css/dashboard-groups.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 6. Estados visuales
        wp_enqueue_style(
            'fl-dashboard-states',
            $plugin_url . 'assets/css/dashboard-states.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 7. Accesibilidad
        wp_enqueue_style(
            'fl-dashboard-a11y',
            $plugin_url . 'assets/css/dashboard-a11y.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 8. Responsive
        wp_enqueue_style(
            'fl-dashboard-responsive',
            $plugin_url . 'assets/css/dashboard-responsive.css',
            ['fl-dashboard-groups'],
            $version
        );

        // 9. Breadcrumbs
        wp_enqueue_style(
            'fl-breadcrumbs',
            $plugin_url . 'assets/css/breadcrumbs.css',
            ['fl-design-tokens'],
            $version
        );

        // 10. Client Dashboard (estilos especificos)
        wp_enqueue_style(
            'flavor-client-dashboard',
            $plugin_url . "assets/css/client-dashboard{$sufijo_asset}.css",
            ['fl-dashboard-responsive', 'fl-breadcrumbs'],
            $version
        );

        // Scripts
        wp_enqueue_script(
            'flavor-client-dashboard',
            FLAVOR_CHAT_IA_URL . "assets/js/client-dashboard{$sufijo_asset}.js",
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        $usuario_actual = wp_get_current_user();
        $preferencias_usuario = $this->obtener_preferencias_usuario($usuario_actual->ID);

        $datos_localizados = [
            'ajaxUrl'           => admin_url('admin-ajax.php'),
            'nonce'             => wp_create_nonce('flavor_client_dashboard'),
            'userId'            => get_current_user_id(),
            'userName'          => $usuario_actual->exists() ? $usuario_actual->display_name : '',
            'refreshInterval'   => 120000,
            'preferences'       => $preferencias_usuario,
            'i18n'              => [
                'cargando'                => __('Cargando...', 'flavor-chat-ia'),
                'error_conexion'          => __('Error de conexion. Intentalo de nuevo.', 'flavor-chat-ia'),
                'actualizado'             => __('Datos actualizados', 'flavor-chat-ia'),
                'sin_actividad'           => __('No hay actividad reciente', 'flavor-chat-ia'),
                'sin_notificaciones'      => __('No tienes notificaciones pendientes', 'flavor-chat-ia'),
                'notificacion_descartada' => __('Notificacion descartada', 'flavor-chat-ia'),
                'preferencias_guardadas'  => __('Preferencias guardadas', 'flavor-chat-ia'),
                'ver_todo'                => __('Ver todo', 'flavor-chat-ia'),
                'hace_momentos'           => __('Hace unos momentos', 'flavor-chat-ia'),
                'hace_minutos'            => __('Hace %d minutos', 'flavor-chat-ia'),
                'hace_horas'              => __('Hace %d horas', 'flavor-chat-ia'),
                'hace_dias'               => __('Hace %d dias', 'flavor-chat-ia'),
                'atajo_actualizar'        => __('Ctrl+R para actualizar', 'flavor-chat-ia'),
                'atajo_buscar'            => __('Ctrl+K para buscar', 'flavor-chat-ia'),
            ],
        ];

        wp_localize_script('flavor-client-dashboard', 'flavorClientDashboard', $datos_localizados);
    }

    /**
     * Registra elementos por defecto del dashboard
     */
    public function registrar_elementos_por_defecto() {
        // Estadistica: Reservas del usuario
        $this->registrar_estadistica('reservas', [
            'label'    => __('Mis Reservas', 'flavor-chat-ia'),
            'icon'     => 'calendar',
            'color'    => 'primary',
            'callback' => [$this, 'obtener_estadistica_reservas'],
            'url'      => home_url('/mi-cuenta/?tab=reservas'),
            'orden'    => 10,
        ]);

        // Estadistica: Participaciones
        $this->registrar_estadistica('participaciones', [
            'label'    => __('Participaciones', 'flavor-chat-ia'),
            'icon'     => 'users',
            'color'    => 'success',
            'callback' => [$this, 'obtener_estadistica_participaciones'],
            'url'      => home_url('/mi-cuenta/?tab=participaciones'),
            'orden'    => 20,
        ]);

        // Estadistica: Puntos
        $this->registrar_estadistica('puntos', [
            'label'    => __('Mis Puntos', 'flavor-chat-ia'),
            'icon'     => 'star',
            'color'    => 'warning',
            'callback' => [$this, 'obtener_estadistica_puntos'],
            'url'      => home_url('/mi-cuenta/?tab=puntos'),
            'orden'    => 30,
        ]);

        // Estadistica: Mensajes sin leer
        $this->registrar_estadistica('mensajes', [
            'label'    => __('Mensajes', 'flavor-chat-ia'),
            'icon'     => 'message',
            'color'    => 'info',
            'callback' => [$this, 'obtener_estadistica_mensajes'],
            'url'      => home_url('/mi-cuenta/?tab=mensajes'),
            'orden'    => 40,
        ]);

        // Atajos rapidos por defecto
        $this->registrar_atajo('nueva-reserva', [
            'label'  => __('Nueva Reserva', 'flavor-chat-ia'),
            'icon'   => 'plus-circle',
            'url'    => home_url('/reservas/nueva/'),
            'color'  => 'primary',
            'orden'  => 10,
        ]);

        $this->registrar_atajo('mi-perfil', [
            'label'  => __('Mi Perfil', 'flavor-chat-ia'),
            'icon'   => 'user',
            'url'    => home_url('/mi-cuenta/?tab=perfil'),
            'color'  => 'secondary',
            'orden'  => 20,
        ]);

        $this->registrar_atajo('soporte', [
            'label'  => __('Soporte', 'flavor-chat-ia'),
            'icon'   => 'help-circle',
            'url'    => home_url('/soporte/'),
            'color'  => 'info',
            'orden'  => 30,
        ]);

        // Widget: Proximas reservas
        $this->registrar_widget('proximas-reservas', [
            'title'    => __('Proximas Reservas', 'flavor-chat-ia'),
            'icon'     => 'calendar',
            'callback' => [$this, 'render_widget_proximas_reservas'],
            'size'     => 'medium',
            'orden'    => 10,
        ]);

        // Widget: Mensajes recientes
        $this->registrar_widget('mensajes-recientes', [
            'title'    => __('Mensajes Recientes', 'flavor-chat-ia'),
            'icon'     => 'message',
            'callback' => [$this, 'render_widget_mensajes_recientes'],
            'size'     => 'medium',
            'orden'    => 20,
        ]);

        // Widget: Red de Comunidades
        $this->registrar_widget('widget-network', [
            'title'    => __('Red de Comunidades', 'flavor-chat-ia'),
            'icon'     => 'globe',
            'callback' => [$this, 'render_widget_red_comunidades'],
            'size'     => 'medium',
            'orden'    => 30,
        ]);

        // Widget: Recursos Compartidos
        $this->registrar_widget('widget-shared', [
            'title'    => __('Recursos Compartidos', 'flavor-chat-ia'),
            'icon'     => 'share',
            'callback' => [$this, 'render_widget_recursos_compartidos'],
            'size'     => 'medium',
            'orden'    => 40,
        ]);

        // Widget: Mapa Interactivo
        $this->registrar_widget('widget-map', [
            'title'    => __('Mapa Interactivo', 'flavor-chat-ia'),
            'icon'     => 'map',
            'callback' => [$this, 'render_widget_mapa_interactivo'],
            'size'     => 'large',
            'orden'    => 50,
        ]);

        // Widget: Panel de Estadisticas Avanzadas
        $this->registrar_widget('widget-stats-panel', [
            'title'    => __('Estadisticas Avanzadas', 'flavor-chat-ia'),
            'icon'     => 'trending-up',
            'callback' => [$this, 'render_widget_estadisticas_avanzadas'],
            'size'     => 'large',
            'orden'    => 60,
        ]);

        /**
         * Hook para que modulos registren sus propios widgets
         *
         * Ejemplo de uso:
         *   add_action('flavor_client_dashboard_init', function($dashboard) {
         *       $dashboard->registrar_widget('mi-widget', [
         *           'title'    => 'Mi Widget',
         *           'icon'     => 'star',
         *           'callback' => [$this, 'render_mi_widget'],
         *           'size'     => 'small',
         *           'orden'    => 50,
         *       ]);
         *   });
         */
        do_action('flavor_client_dashboard_init', $this);
    }

    /**
     * Inicializa widgets de modulos activos
     *
     * @param Flavor_Client_Dashboard $dashboard Instancia del dashboard
     */
    public function inicializar_widgets_modulos($dashboard) {
        // Obtener el Module Loader
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulos_cargados = $loader->get_loaded_modules();

        // Iconos por modulo
        $iconos_modulos = [
            'eventos'                   => 'calendar',
            'reservas'                  => 'calendar',
            'espacios-comunes'          => 'home',
            'grupos-consumo'            => 'shopping-bag',
            'huertos-urbanos'           => 'sun',
            'biblioteca'                => 'book',
            'marketplace'               => 'tag',
            'incidencias'               => 'alert-triangle',
            'banco-tiempo'              => 'clock',
            'bicicletas-compartidas'    => 'navigation',
            'parkings'                  => 'map-pin',
            'carpooling'                => 'truck',
            'reciclaje'                 => 'refresh-cw',
            'compostaje'                => 'leaf',
            'cursos'                    => 'book-open',
            'talleres'                  => 'tool',
            'comunidades'               => 'globe',
            'podcast'                   => 'mic',
            'radio'                     => 'radio',
            'red-social'                => 'users',
            'participacion'             => 'message-circle',
            'transparencia'             => 'eye',
            'tramites'                  => 'file-text',
            'avisos-municipales'        => 'bell',
            'trading-ia'                => 'trending-up',
            'advertising'               => 'bar-chart-2',
            'clientes'                  => 'users',
            'empresarial'               => 'briefcase',
            'dex-solana'                => 'zap',
            'facturas'                  => 'file-text',
            'multimedia'                => 'image',
            'woocommerce'               => 'shopping-cart',
        ];

        $orden_base = 100;

        foreach ($modulos_cargados as $modulo_id => $instancia_modulo) {
            // Normalizar ID del modulo
            $modulo_id_normalizado = str_replace('_', '-', $modulo_id);

            // Obtener nombre del modulo usando metodos publicos
            $nombre_modulo = '';
            if (method_exists($instancia_modulo, 'get_module_name')) {
                $nombre_modulo = $instancia_modulo->get_module_name();
            } elseif (method_exists($instancia_modulo, 'get_name')) {
                $nombre_modulo = $instancia_modulo->get_name();
            } else {
                // Fallback: usar el ID del modulo formateado
                $nombre_modulo = ucfirst(str_replace(['-', '_'], ' ', $modulo_id));
            }

            // Obtener icono
            $icono = $iconos_modulos[$modulo_id_normalizado] ?? 'box';

            // Verificar si el modulo tiene estadisticas para dashboard
            if (method_exists($instancia_modulo, 'get_estadisticas_dashboard')) {
                $estadisticas = $instancia_modulo->get_estadisticas_dashboard();

                if (!empty($estadisticas) && is_array($estadisticas)) {
                    // Registrar widget con estadisticas del modulo
                    $dashboard->registrar_widget('modulo-' . $modulo_id_normalizado, [
                        'title'    => $nombre_modulo,
                        'icon'     => $icono,
                        'callback' => function($id_usuario) use ($instancia_modulo, $modulo_id_normalizado, $estadisticas) {
                            $this->render_widget_modulo_generico($id_usuario, $modulo_id_normalizado, $estadisticas);
                        },
                        'size'     => 'medium',
                        'orden'    => $orden_base,
                        'modulo'   => $modulo_id_normalizado,
                    ]);

                    $orden_base += 10;
                }
            }

            // Registrar atajo rapido al modulo
            $dashboard->registrar_atajo('modulo-' . $modulo_id_normalizado, [
                'label'  => $nombre_modulo,
                'icon'   => $icono,
                'url'    => home_url('/mi-portal/' . $modulo_id_normalizado . '/'),
                'color'  => 'secondary',
                'orden'  => $orden_base,
            ]);
        }
    }

    /**
     * Renderiza un widget generico de modulo
     *
     * @param int    $id_usuario          ID del usuario
     * @param string $modulo_id           ID del modulo
     * @param array  $estadisticas        Estadisticas del modulo
     */
    private function render_widget_modulo_generico($id_usuario, $modulo_id, $estadisticas) {
        $url_modulo = home_url('/mi-portal/' . $modulo_id . '/');
        ?>
        <div class="fcd-widget-modulo" data-modulo="<?php echo esc_attr($modulo_id); ?>">
            <div class="fcd-modulo-stats">
                <?php foreach (array_slice($estadisticas, 0, 4) as $clave => $valor): ?>
                    <?php
                    $etiqueta = is_string($clave) ? ucfirst(str_replace('_', ' ', $clave)) : '';
                    $valor_mostrar = is_array($valor) ? ($valor['valor'] ?? $valor['value'] ?? 0) : $valor;
                    $icono = is_array($valor) ? ($valor['icon'] ?? 'activity') : 'activity';
                    ?>
                    <div class="fcd-modulo-stat">
                        <span class="fcd-stat-icon" data-feather="<?php echo esc_attr($icono); ?>"></span>
                        <span class="fcd-stat-value"><?php echo esc_html($valor_mostrar); ?></span>
                        <?php if ($etiqueta): ?>
                            <span class="fcd-stat-label"><?php echo esc_html($etiqueta); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="fcd-modulo-actions">
                <a href="<?php echo esc_url($url_modulo); ?>" class="fcd-btn fcd-btn-sm fcd-btn-outline">
                    <?php esc_html_e('Ver más', 'flavor-chat-ia'); ?>
                    <span data-feather="arrow-right"></span>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Registra una estadistica
     *
     * @param string $identificador Identificador unico
     * @param array  $configuracion Configuracion de la estadistica
     */
    public function registrar_estadistica($identificador, $configuracion) {
        $this->estadisticas_registradas[$identificador] = wp_parse_args($configuracion, [
            'label'    => '',
            'icon'     => 'chart',
            'color'    => 'primary',
            'callback' => null,
            'url'      => '',
            'orden'    => 50,
        ]);
    }

    /**
     * Registra un widget
     *
     * @param string $identificador Identificador unico
     * @param array  $configuracion Configuracion del widget
     */
    public function registrar_widget($identificador, $configuracion) {
        $this->widgets_registrados[$identificador] = wp_parse_args($configuracion, [
            'title'    => '',
            'icon'     => 'box',
            'callback' => null,
            'size'     => 'medium',
            'orden'    => 50,
            'modulo'   => '',
        ]);
    }

    /**
     * Registra un atajo rapido
     *
     * @param string $identificador Identificador unico
     * @param array  $configuracion Configuracion del atajo
     */
    public function registrar_atajo($identificador, $configuracion) {
        $this->atajos_registrados[$identificador] = wp_parse_args($configuracion, [
            'label'  => '',
            'icon'   => 'link',
            'url'    => '',
            'color'  => 'secondary',
            'orden'  => 50,
            'target' => '_self',
        ]);
    }

    /**
     * Obtiene todas las estadisticas ordenadas
     *
     * @return array
     */
    public function obtener_estadisticas() {
        $estadisticas = apply_filters('flavor_client_dashboard_estadisticas', $this->estadisticas_registradas);

        uasort($estadisticas, function ($estadistica_a, $estadistica_b) {
            return ($estadistica_a['orden'] ?? 50) - ($estadistica_b['orden'] ?? 50);
        });

        return $estadisticas;
    }

    /**
     * Obtiene todos los widgets ordenados
     *
     * @return array
     */
    public function obtener_widgets() {
        $widgets = apply_filters('flavor_client_dashboard_widgets', $this->widgets_registrados);

        uasort($widgets, function ($widget_a, $widget_b) {
            return ($widget_a['orden'] ?? 50) - ($widget_b['orden'] ?? 50);
        });

        return $widgets;
    }

    /**
     * Obtiene todos los atajos ordenados
     *
     * @return array
     */
    public function obtener_atajos() {
        $atajos = apply_filters('flavor_client_dashboard_atajos', $this->atajos_registrados);

        uasort($atajos, function ($atajo_a, $atajo_b) {
            return ($atajo_a['orden'] ?? 50) - ($atajo_b['orden'] ?? 50);
        });

        return $atajos;
    }

    /**
     * Renderiza el dashboard completo
     *
     * @param array $atributos_shortcode Atributos del shortcode
     * @return string HTML del dashboard
     */
    public function render_dashboard($atributos_shortcode = []) {
        $this->shortcode_presente_en_pagina = true;

        if (!is_user_logged_in()) {
            return $this->render_acceso_requerido();
        }

        $atributos = shortcode_atts([
            'mostrar_estadisticas' => 'true',
            'mostrar_atajos'       => 'true',
            'mostrar_actividad'    => 'true',
            'mostrar_widgets'      => 'true',
            'mostrar_notificaciones' => 'true',
            'columnas_widgets'     => 2,
        ], $atributos_shortcode);

        $usuario_actual = wp_get_current_user();
        $avatar_url     = get_avatar_url($usuario_actual->ID, ['size' => 128]);
        $nombre_usuario = $this->obtener_nombre_usuario($usuario_actual);
        $saludo         = $this->obtener_saludo_personalizado();

        // Preparar datos
        $estadisticas        = $this->obtener_estadisticas_con_valores($usuario_actual->ID);
        $atajos              = $this->obtener_atajos();
        $widgets             = $this->obtener_widgets();
        $actividad_reciente  = $this->obtener_actividad_reciente($usuario_actual->ID, 10);
        $notificaciones      = $this->obtener_notificaciones_usuario($usuario_actual->ID, 5);
        $preferencias        = $this->obtener_preferencias_usuario($usuario_actual->ID);

        // Obtener widgets agrupados por categoría
        $widgets_agrupados = [];
        $categorias_disponibles = [];
        $total_widgets = 0;
        $usar_registry = false;

        // Intentar usar Widget Registry si existe y tiene widgets
        if (class_exists('Flavor_Widget_Registry')) {
            $widget_registry = Flavor_Widget_Registry::get_instance();
            $widgets_registry = $widget_registry->get_all();

            if (!empty($widgets_registry)) {
                $widgets_agrupados = $widget_registry->get_grouped_by_category();
                $categorias_disponibles = $widget_registry->get_categories_with_count();
                $usar_registry = true;

                // Contar total de widgets
                foreach ($widgets_agrupados as $grupo) {
                    $total_widgets += $grupo['count'] ?? 0;
                }
            }
        }

        // Fallback: Agrupar los widgets locales si no hay widgets del registry
        if (!$usar_registry && !empty($widgets)) {
            $widgets_agrupados = $this->agrupar_widgets_por_categoria($widgets);
            $categorias_disponibles = $this->obtener_categorias_de_widgets($widgets);
            $total_widgets = count($widgets);
        }

        // Debug: log si no hay widgets (solo en WP_DEBUG)
        if (empty($widgets_agrupados) && defined('WP_DEBUG') && WP_DEBUG) {
            flavor_log_debug( 'No hay widgets agrupados. Registry: ' . ($usar_registry ? 'sí' : 'no') . ', Widgets locales: ' . count($widgets), 'ClientDashboard' );
        }

        ob_start();

        $datos_template = [
            'usuario'               => $usuario_actual,
            'avatar_url'            => $avatar_url,
            'nombre_usuario'        => $nombre_usuario,
            'saludo'                => $saludo,
            'estadisticas'          => $estadisticas,
            'atajos'                => $atajos,
            'widgets'               => $widgets,
            'widgets_agrupados'     => $widgets_agrupados,
            'categorias'            => $categorias_disponibles,
            'total_widgets'         => $total_widgets,
            'actividad_reciente'    => $actividad_reciente,
            'notificaciones'        => $notificaciones,
            'preferencias'          => $preferencias,
            'atributos'             => $atributos,
            'dashboard_instance'    => $this,
        ];

        $ruta_template = FLAVOR_CHAT_IA_PATH . 'templates/frontend/dashboard/client-dashboard.php';
        if (file_exists($ruta_template)) {
            extract($datos_template);
            include $ruta_template;
        }

        return ob_get_clean();
    }

    /**
     * Renderiza mensaje de acceso requerido
     *
     * @return string HTML
     */
    private function render_acceso_requerido() {
        ob_start();
        ?>
        <div class="flavor-client-dashboard flavor-client-dashboard--login-required">
            <div class="flavor-client-dashboard__login-box">
                <div class="flavor-client-dashboard__login-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                </div>
                <h2><?php esc_html_e('Acceso Requerido', 'flavor-chat-ia'); ?></h2>
                <p><?php esc_html_e('Necesitas iniciar sesion para acceder a tu panel personal.', 'flavor-chat-ia'); ?></p>
                <div class="flavor-client-dashboard__login-actions">
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="flavor-btn flavor-btn--primary">
                        <?php esc_html_e('Iniciar Sesion', 'flavor-chat-ia'); ?>
                    </a>
                    <?php if (get_option('users_can_register')) : ?>
                        <a href="<?php echo esc_url(wp_registration_url()); ?>" class="flavor-btn flavor-btn--outline">
                            <?php esc_html_e('Crear Cuenta', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene el nombre del usuario para mostrar
     *
     * @param WP_User $usuario Usuario
     * @return string
     */
    private function obtener_nombre_usuario($usuario) {
        $nombre_completo = trim($usuario->first_name . ' ' . $usuario->last_name);

        if (!empty($nombre_completo)) {
            return $nombre_completo;
        }

        return $usuario->display_name;
    }

    /**
     * Genera un saludo personalizado segun la hora
     *
     * @return string
     */
    private function obtener_saludo_personalizado() {
        $hora_actual = (int) current_time('G');

        if ($hora_actual >= 5 && $hora_actual < 12) {
            return __('Buenos dias', 'flavor-chat-ia');
        } elseif ($hora_actual >= 12 && $hora_actual < 20) {
            return __('Buenas tardes', 'flavor-chat-ia');
        } else {
            return __('Buenas noches', 'flavor-chat-ia');
        }
    }

    /**
     * Obtiene las estadisticas con sus valores calculados
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    public function obtener_estadisticas_con_valores($id_usuario) {
        $estadisticas = $this->obtener_estadisticas();
        $resultados = [];

        foreach ($estadisticas as $identificador => $configuracion) {
            $valor = 0;
            $texto_secundario = '';

            if (is_callable($configuracion['callback'])) {
                $resultado = call_user_func($configuracion['callback'], $id_usuario);

                if (is_array($resultado)) {
                    $valor = $resultado['valor'] ?? 0;
                    $texto_secundario = $resultado['texto'] ?? '';
                } else {
                    $valor = $resultado;
                }
            }

            $resultados[$identificador] = [
                'label'     => $configuracion['label'],
                'valor'     => $valor,
                'texto'     => $texto_secundario,
                'icon'      => $configuracion['icon'],
                'color'     => $configuracion['color'],
                'url'       => $configuracion['url'],
            ];
        }

        return $resultados;
    }

    /**
     * Obtiene la actividad reciente del usuario
     *
     * @param int $id_usuario ID del usuario
     * @param int $limite     Cantidad maxima de items
     * @return array
     */
    public function obtener_actividad_reciente($id_usuario, $limite = 10) {
        $actividad = [];

        /**
         * Filtro para que modulos agreguen actividad
         *
         * @param array $actividad Actividad actual
         * @param int   $id_usuario ID del usuario
         * @param int   $limite Limite de items
         */
        $actividad = apply_filters('flavor_client_dashboard_actividad', $actividad, $id_usuario, $limite);

        // Ordenar por fecha descendente
        usort($actividad, function ($actividad_a, $actividad_b) {
            $fecha_a = strtotime($actividad_a['fecha'] ?? '');
            $fecha_b = strtotime($actividad_b['fecha'] ?? '');
            return $fecha_b - $fecha_a;
        });

        return array_slice($actividad, 0, $limite);
    }

    /**
     * Obtiene las notificaciones del usuario
     *
     * @param int $id_usuario ID del usuario
     * @param int $limite     Cantidad maxima
     * @return array
     */
    public function obtener_notificaciones_usuario($id_usuario, $limite = 5) {
        $notificaciones = [];

        if (class_exists('Flavor_Notification_Manager')) {
            $gestor_notificaciones = Flavor_Notification_Manager::get_instance();
            $notificaciones = $gestor_notificaciones->get_user_notifications($id_usuario, [
                'limit'  => $limite,
                'unread' => true,
            ]);
        }

        return apply_filters('flavor_client_dashboard_notificaciones', $notificaciones, $id_usuario);
    }

    /**
     * Obtiene preferencias del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    public function obtener_preferencias_usuario($id_usuario) {
        $preferencias_por_defecto = [
            'tema'                  => 'auto',
            'widgets_colapsados'    => [],
            'orden_widgets'         => [],
            'mostrar_actividad'     => true,
            'mostrar_notificaciones' => true,
        ];

        $preferencias_guardadas = get_user_meta($id_usuario, 'flavor_client_dashboard_preferences', true);

        if (!is_array($preferencias_guardadas)) {
            $preferencias_guardadas = [];
        }

        return wp_parse_args($preferencias_guardadas, $preferencias_por_defecto);
    }

    /**
     * Guarda preferencias del usuario
     *
     * @param int   $id_usuario   ID del usuario
     * @param array $preferencias Preferencias a guardar
     * @return bool
     */
    public function guardar_preferencias_usuario($id_usuario, $preferencias) {
        $preferencias_actuales = $this->obtener_preferencias_usuario($id_usuario);
        $preferencias_nuevas = wp_parse_args($preferencias, $preferencias_actuales);

        return update_user_meta($id_usuario, 'flavor_client_dashboard_preferences', $preferencias_nuevas);
    }

    // =========================================================================
    // Callbacks de estadisticas por defecto
    // =========================================================================

    /**
     * Obtiene estadistica de reservas del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    public function obtener_estadistica_reservas($id_usuario) {
        global $wpdb;

        $total_reservas = 0;
        $proximas_reservas = 0;

        // Intentar obtener de diferentes fuentes
        $tabla_reservas = $wpdb->prefix . 'flavor_reservations';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_reservas'") === $tabla_reservas) {
            $total_reservas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_reservas WHERE user_id = %d",
                $id_usuario
            ));

            $proximas_reservas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_reservas WHERE user_id = %d AND fecha >= CURDATE() AND status IN ('confirmed', 'pending')",
                $id_usuario
            ));
        }

        return [
            'valor' => $total_reservas,
            'texto' => sprintf(__('%d proximas', 'flavor-chat-ia'), $proximas_reservas),
        ];
    }

    /**
     * Obtiene estadistica de participaciones del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    public function obtener_estadistica_participaciones($id_usuario) {
        $participaciones = 0;

        // Contar inscripciones en eventos, cursos, talleres, etc.
        $participaciones = apply_filters('flavor_client_dashboard_participaciones_count', $participaciones, $id_usuario);

        return [
            'valor' => $participaciones,
            'texto' => __('Este mes', 'flavor-chat-ia'),
        ];
    }

    /**
     * Obtiene estadistica de puntos del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    public function obtener_estadistica_puntos($id_usuario) {
        $puntos = (int) get_user_meta($id_usuario, 'flavor_user_points', true);
        $nivel = $this->calcular_nivel_usuario($puntos);

        return [
            'valor' => $puntos,
            'texto' => sprintf(__('Nivel %s', 'flavor-chat-ia'), $nivel),
        ];
    }

    /**
     * Obtiene estadistica de mensajes del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    public function obtener_estadistica_mensajes($id_usuario) {
        $mensajes_sin_leer = 0;

        if (class_exists('Flavor_Notification_Manager')) {
            $gestor_notificaciones = Flavor_Notification_Manager::get_instance();
            $mensajes_sin_leer = $gestor_notificaciones->get_unread_count($id_usuario);
        }

        return [
            'valor' => $mensajes_sin_leer,
            'texto' => __('Sin leer', 'flavor-chat-ia'),
        ];
    }

    /**
     * Calcula el nivel del usuario segun sus puntos
     *
     * @param int $puntos Puntos del usuario
     * @return string
     */
    private function calcular_nivel_usuario($puntos) {
        if ($puntos >= 10000) {
            return __('Experto', 'flavor-chat-ia');
        } elseif ($puntos >= 5000) {
            return __('Avanzado', 'flavor-chat-ia');
        } elseif ($puntos >= 1000) {
            return __('Intermedio', 'flavor-chat-ia');
        } elseif ($puntos >= 100) {
            return __('Basico', 'flavor-chat-ia');
        }

        return __('Nuevo', 'flavor-chat-ia');
    }

    // =========================================================================
    // Widgets por defecto
    // =========================================================================

    /**
     * Renderiza widget de proximas reservas
     *
     * @param int $id_usuario ID del usuario
     */
    public function render_widget_proximas_reservas($id_usuario) {
        global $wpdb;

        $reservas = [];
        $tabla_reservas = $wpdb->prefix . 'flavor_reservations';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_reservas'") === $tabla_reservas) {
            $reservas = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_reservas
                WHERE user_id = %d AND fecha >= CURDATE() AND status IN ('confirmed', 'pending')
                ORDER BY fecha ASC, hora ASC
                LIMIT 5",
                $id_usuario
            ), ARRAY_A);
        }

        if (empty($reservas)) {
            echo '<div class="flavor-widget-empty">';
            echo '<p>' . esc_html__('No tienes reservas proximas', 'flavor-chat-ia') . '</p>';
            echo '<a href="' . esc_url(home_url('/reservas/')) . '" class="flavor-btn flavor-btn--sm flavor-btn--outline">' . esc_html__('Hacer una reserva', 'flavor-chat-ia') . '</a>';
            echo '</div>';
            return;
        }

        echo '<ul class="flavor-widget-list">';
        foreach ($reservas as $reserva) {
            $fecha_formateada = date_i18n(get_option('date_format'), strtotime($reserva['fecha']));
            $hora_formateada = isset($reserva['hora']) ? date_i18n(get_option('time_format'), strtotime($reserva['hora'])) : '';
            $estado_clase = $reserva['status'] === 'confirmed' ? 'success' : 'warning';

            echo '<li class="flavor-widget-list__item">';
            echo '<div class="flavor-widget-list__content">';
            echo '<span class="flavor-widget-list__title">' . esc_html($reserva['servicio'] ?? __('Reserva', 'flavor-chat-ia')) . '</span>';
            echo '<span class="flavor-widget-list__meta">' . esc_html($fecha_formateada);
            if ($hora_formateada) {
                echo ' - ' . esc_html($hora_formateada);
            }
            echo '</span>';
            echo '</div>';
            echo '<span class="flavor-badge flavor-badge--' . esc_attr($estado_clase) . '">' . esc_html(ucfirst($reserva['status'])) . '</span>';
            echo '</li>';
        }
        echo '</ul>';
    }

    /**
     * Renderiza widget de mensajes recientes
     *
     * @param int $id_usuario ID del usuario
     */
    public function render_widget_mensajes_recientes($id_usuario) {
        $notificaciones = $this->obtener_notificaciones_usuario($id_usuario, 5);

        if (empty($notificaciones)) {
            echo '<div class="flavor-widget-empty">';
            echo '<p>' . esc_html__('No tienes mensajes nuevos', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        echo '<ul class="flavor-widget-list">';
        foreach ($notificaciones as $notificacion) {
            $titulo = $notificacion['title'] ?? __('Notificacion', 'flavor-chat-ia');
            $fecha = isset($notificacion['created_at']) ? human_time_diff(strtotime($notificacion['created_at'])) : '';

            echo '<li class="flavor-widget-list__item">';
            echo '<div class="flavor-widget-list__content">';
            echo '<span class="flavor-widget-list__title">' . esc_html($titulo) . '</span>';
            if ($fecha) {
                echo '<span class="flavor-widget-list__meta">' . sprintf(esc_html__('Hace %s', 'flavor-chat-ia'), esc_html($fecha)) . '</span>';
            }
            echo '</div>';
            if (!empty($notificacion['is_read']) && !$notificacion['is_read']) {
                echo '<span class="flavor-indicator flavor-indicator--unread"></span>';
            }
            echo '</li>';
        }
        echo '</ul>';
    }

    // =========================================================================
    // Helpers para templates
    // =========================================================================

    /**
     * Genera el icono SVG
     *
     * @param string $nombre_icono Nombre del icono
     * @param int    $tamano       Tamano en pixels
     * @return string
     */
    public function obtener_icono_svg($nombre_icono, $tamano = 24) {
        $iconos_disponibles = [
            'calendar'     => '<path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
            'users'        => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"/>',
            'star'         => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
            'message'      => '<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>',
            'user'         => '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'bell'         => '<path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/>',
            'plus-circle'  => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>',
            'help-circle'  => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            'settings'     => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9c.26.604.852.997 1.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>',
            'activity'     => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
            'clock'        => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
            'check-circle' => '<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'alert-circle' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
            'x'            => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
            'refresh'      => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>',
            'sun'          => '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>',
            'moon'         => '<path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>',
            'box'          => '<path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
            'link'         => '<path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>',
            'chart'        => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
            'home'         => '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
            'shopping-bag' => '<path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/>',
            // Iconos para nuevos widgets
            'globe'        => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>',
            'share'        => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>',
            'map'          => '<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>',
            'map-pin'      => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
            'trending-up'  => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
            'bar-chart'    => '<line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>',
            'layers'       => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
            'truck'        => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
            'coffee'       => '<path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>',
            'parking'      => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><path d="M9 17V7h4a3 3 0 010 6H9"/>',
            'bike'         => '<circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h3"/>',
            'leaf'         => '<path d="M11 20A7 7 0 019.84 6.34L12 3l2.16 3.34A7 7 0 0111 20z"/><path d="M6.87 14.13l5.17-4.13"/>',
            'recycle'      => '<path d="M7 19H4.815a1.83 1.83 0 01-1.57-.881 1.785 1.785 0 01-.004-1.784L7.196 9.5"/><path d="M11 19h5.816a1.83 1.83 0 001.57-.881 1.785 1.785 0 00.004-1.784L14.433 9.5"/><path d="M14 16l3 3-3 3"/><path d="M8.5 9.5L12 2l3.5 7.5"/><path d="M14 10l-2 2-2-2"/>',
            'external'     => '<path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>',
            'filter'       => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
            'database'     => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>',
        ];

        $path_icono = $iconos_disponibles[$nombre_icono] ?? $iconos_disponibles['box'];

        return sprintf(
            '<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">%2$s</svg>',
            (int) $tamano,
            $path_icono
        );
    }

    /**
     * Formatea una fecha relativa
     *
     * @param string $fecha Fecha en formato timestamp o string
     * @return string
     */
    public function formatear_fecha_relativa($fecha) {
        $timestamp = is_numeric($fecha) ? $fecha : strtotime($fecha);
        $diferencia = time() - $timestamp;

        if ($diferencia < 60) {
            return __('Hace unos momentos', 'flavor-chat-ia');
        } elseif ($diferencia < 3600) {
            $minutos = floor($diferencia / 60);
            return sprintf(__('Hace %d minutos', 'flavor-chat-ia'), $minutos);
        } elseif ($diferencia < 86400) {
            $horas = floor($diferencia / 3600);
            return sprintf(__('Hace %d horas', 'flavor-chat-ia'), $horas);
        } elseif ($diferencia < 604800) {
            $dias = floor($diferencia / 86400);
            return sprintf(__('Hace %d dias', 'flavor-chat-ia'), $dias);
        }

        return date_i18n(get_option('date_format'), $timestamp);
    }

    // =========================================================================
    // Endpoints AJAX
    // =========================================================================

    /**
     * AJAX: Obtener estadisticas actualizadas
     */
    public function ajax_obtener_estadisticas() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_usuario = get_current_user_id();
        $estadisticas = $this->obtener_estadisticas_con_valores($id_usuario);

        wp_send_json_success([
            'estadisticas' => $estadisticas,
        ]);
    }

    /**
     * AJAX: Obtener contenido de widgets
     */
    public function ajax_obtener_widgets() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_usuario = get_current_user_id();
        $id_widget = isset($_POST['widget_id']) ? sanitize_key($_POST['widget_id']) : '';
        $widgets = $this->obtener_widgets();

        if (!empty($id_widget) && isset($widgets[$id_widget])) {
            ob_start();
            if (is_callable($widgets[$id_widget]['callback'])) {
                call_user_func($widgets[$id_widget]['callback'], $id_usuario);
            }
            $contenido_html = ob_get_clean();

            wp_send_json_success([
                'widget_id' => $id_widget,
                'html'      => $contenido_html,
            ]);
        }

        wp_send_json_error(['message' => __('Widget no encontrado', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Obtener actividad reciente
     */
    public function ajax_obtener_actividad() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_usuario = get_current_user_id();
        $limite = isset($_POST['limit']) ? absint($_POST['limit']) : 10;
        $actividad = $this->obtener_actividad_reciente($id_usuario, $limite);

        wp_send_json_success([
            'actividad' => $actividad,
        ]);
    }

    /**
     * AJAX: Obtener notificaciones
     */
    public function ajax_obtener_notificaciones() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_usuario = get_current_user_id();
        $limite = isset($_POST['limit']) ? absint($_POST['limit']) : 5;
        $notificaciones = $this->obtener_notificaciones_usuario($id_usuario, $limite);

        wp_send_json_success([
            'notificaciones' => $notificaciones,
            'total'          => count($notificaciones),
        ]);
    }

    /**
     * AJAX: Descartar notificacion
     */
    public function ajax_descartar_notificacion() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_notificacion = isset($_POST['notification_id']) ? absint($_POST['notification_id']) : 0;

        if (!$id_notificacion) {
            wp_send_json_error(['message' => __('ID de notificacion no valido', 'flavor-chat-ia')]);
        }

        if (class_exists('Flavor_Notification_Manager')) {
            $gestor_notificaciones = Flavor_Notification_Manager::get_instance();
            $gestor_notificaciones->mark_as_read($id_notificacion);
        }

        wp_send_json_success([
            'message' => __('Notificacion descartada', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Guardar preferencias del usuario
     */
    public function ajax_guardar_preferencias() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_usuario = get_current_user_id();
        $preferencias = [];

        if (isset($_POST['tema'])) {
            $preferencias['tema'] = sanitize_key($_POST['tema']);
        }

        if (isset($_POST['widgets_colapsados'])) {
            $preferencias['widgets_colapsados'] = array_map('sanitize_key', (array) $_POST['widgets_colapsados']);
        }

        if (isset($_POST['orden_widgets'])) {
            $preferencias['orden_widgets'] = array_map('sanitize_key', (array) $_POST['orden_widgets']);
        }

        $guardado_exitoso = $this->guardar_preferencias_usuario($id_usuario, $preferencias);

        if ($guardado_exitoso) {
            wp_send_json_success([
                'message'      => __('Preferencias guardadas', 'flavor-chat-ia'),
                'preferencias' => $this->obtener_preferencias_usuario($id_usuario),
            ]);
        }

        wp_send_json_error(['message' => __('Error al guardar preferencias', 'flavor-chat-ia')]);
    }

    // =========================================================================
    // Widgets Avanzados: Red, Recursos Compartidos, Mapa y Estadisticas
    // =========================================================================

    /**
     * Renderiza widget de Red de Comunidades
     *
     * @param int $id_usuario ID del usuario
     */
    public function render_widget_red_comunidades($id_usuario) {
        $datos_red = $this->obtener_datos_red();
        $nodos_conectados = $datos_red['nodos'] ?? [];
        $estadisticas_red = $datos_red['estadisticas'] ?? [];
        $actualizaciones_recientes = $datos_red['actualizaciones'] ?? [];

        ?>
        <div class="flavor-widget-network" data-widget="network">
            <!-- Estadisticas de la red -->
            <div class="flavor-widget-network__stats">
                <div class="flavor-widget-network__stat">
                    <span class="flavor-widget-network__stat-value">
                        <?php echo esc_html(number_format_i18n($estadisticas_red['total_nodos'] ?? 0)); ?>
                    </span>
                    <span class="flavor-widget-network__stat-label">
                        <?php esc_html_e('Comunidades', 'flavor-chat-ia'); ?>
                    </span>
                </div>
                <div class="flavor-widget-network__stat">
                    <span class="flavor-widget-network__stat-value">
                        <?php echo esc_html(number_format_i18n($estadisticas_red['total_usuarios'] ?? 0)); ?>
                    </span>
                    <span class="flavor-widget-network__stat-label">
                        <?php esc_html_e('Usuarios', 'flavor-chat-ia'); ?>
                    </span>
                </div>
                <div class="flavor-widget-network__stat">
                    <span class="flavor-widget-network__stat-value">
                        <?php echo esc_html(number_format_i18n($estadisticas_red['contenido_compartido'] ?? 0)); ?>
                    </span>
                    <span class="flavor-widget-network__stat-label">
                        <?php esc_html_e('Contenidos', 'flavor-chat-ia'); ?>
                    </span>
                </div>
            </div>

            <!-- Nodos conectados -->
            <?php if (!empty($nodos_conectados)) : ?>
                <div class="flavor-widget-network__nodes">
                    <h4 class="flavor-widget-network__subtitle">
                        <?php esc_html_e('Comunidades Conectadas', 'flavor-chat-ia'); ?>
                    </h4>
                    <ul class="flavor-widget-network__nodes-list">
                        <?php foreach (array_slice($nodos_conectados, 0, 5) as $nodo) : ?>
                            <li class="flavor-widget-network__node">
                                <div class="flavor-widget-network__node-avatar">
                                    <?php if (!empty($nodo['logo'])) : ?>
                                        <img src="<?php echo esc_url($nodo['logo']); ?>"
                                             alt="<?php echo esc_attr($nodo['nombre']); ?>"
                                             width="32" height="32" loading="lazy" />
                                    <?php else : ?>
                                        <?php echo $this->obtener_icono_svg('globe', 20); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="flavor-widget-network__node-info">
                                    <span class="flavor-widget-network__node-name">
                                        <?php echo esc_html($nodo['nombre']); ?>
                                    </span>
                                    <span class="flavor-widget-network__node-type">
                                        <?php echo esc_html($nodo['tipo'] ?? __('Comunidad', 'flavor-chat-ia')); ?>
                                    </span>
                                </div>
                                <?php if (!empty($nodo['estado']) && $nodo['estado'] === 'activo') : ?>
                                    <span class="flavor-indicator flavor-indicator--online"
                                          title="<?php esc_attr_e('Activo', 'flavor-chat-ia'); ?>"></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Actualizaciones recientes -->
            <?php if (!empty($actualizaciones_recientes)) : ?>
                <div class="flavor-widget-network__updates">
                    <h4 class="flavor-widget-network__subtitle">
                        <?php esc_html_e('Ultimas Actualizaciones', 'flavor-chat-ia'); ?>
                    </h4>
                    <ul class="flavor-widget-network__updates-list">
                        <?php foreach (array_slice($actualizaciones_recientes, 0, 3) as $actualizacion) : ?>
                            <li class="flavor-widget-network__update">
                                <span class="flavor-widget-network__update-icon">
                                    <?php echo $this->obtener_icono_svg($actualizacion['icono'] ?? 'activity', 14); ?>
                                </span>
                                <span class="flavor-widget-network__update-text">
                                    <?php echo esc_html($actualizacion['texto']); ?>
                                </span>
                                <time class="flavor-widget-network__update-time">
                                    <?php echo esc_html($this->formatear_fecha_relativa($actualizacion['fecha'])); ?>
                                </time>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (empty($nodos_conectados) && empty($actualizaciones_recientes)) : ?>
                <div class="flavor-widget-empty">
                    <p><?php esc_html_e('Aun no estas conectado a ninguna red', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>

            <!-- Link explorar red -->
            <a href="<?php echo esc_url(home_url('/red-comunidades/')); ?>"
               class="flavor-widget-network__explore-link">
                <?php echo $this->obtener_icono_svg('external', 14); ?>
                <?php esc_html_e('Explorar la Red', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Renderiza widget de Recursos Compartidos
     *
     * @param int $id_usuario ID del usuario
     */
    public function render_widget_recursos_compartidos($id_usuario) {
        $recursos = $this->obtener_recursos_compartidos_usuario($id_usuario);
        $filtro_activo = isset($_GET['tipo_recurso']) ? sanitize_key($_GET['tipo_recurso']) : 'todos';

        $tipos_recursos = [
            'todos'     => __('Todos', 'flavor-chat-ia'),
            'eventos'   => __('Eventos', 'flavor-chat-ia'),
            'ofertas'   => __('Ofertas', 'flavor-chat-ia'),
            'servicios' => __('Servicios', 'flavor-chat-ia'),
        ];

        ?>
        <div class="flavor-widget-shared" data-widget="shared">
            <!-- Filtros -->
            <div class="flavor-widget-shared__filters">
                <?php foreach ($tipos_recursos as $tipo_clave => $tipo_etiqueta) : ?>
                    <button type="button"
                            class="flavor-widget-shared__filter <?php echo $filtro_activo === $tipo_clave ? 'flavor-widget-shared__filter--active' : ''; ?>"
                            data-filter="<?php echo esc_attr($tipo_clave); ?>">
                        <?php echo esc_html($tipo_etiqueta); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Lista de recursos -->
            <?php if (!empty($recursos)) : ?>
                <ul class="flavor-widget-shared__list">
                    <?php foreach (array_slice($recursos, 0, 6) as $recurso) : ?>
                        <li class="flavor-widget-shared__item"
                            data-type="<?php echo esc_attr($recurso['tipo'] ?? 'general'); ?>">
                            <div class="flavor-widget-shared__item-icon flavor-widget-shared__item-icon--<?php echo esc_attr($recurso['tipo'] ?? 'general'); ?>">
                                <?php
                                $icono_tipo = [
                                    'eventos'   => 'calendar',
                                    'ofertas'   => 'shopping-bag',
                                    'servicios' => 'coffee',
                                ];
                                $icono_recurso = $icono_tipo[$recurso['tipo']] ?? 'layers';
                                echo $this->obtener_icono_svg($icono_recurso, 18);
                                ?>
                            </div>
                            <div class="flavor-widget-shared__item-content">
                                <span class="flavor-widget-shared__item-title">
                                    <?php echo esc_html($recurso['titulo']); ?>
                                </span>
                                <div class="flavor-widget-shared__item-meta">
                                    <span class="flavor-widget-shared__item-origin"
                                          title="<?php esc_attr_e('Origen', 'flavor-chat-ia'); ?>">
                                        <?php echo $this->obtener_icono_svg('globe', 12); ?>
                                        <?php echo esc_html($recurso['origen'] ?? __('Local', 'flavor-chat-ia')); ?>
                                    </span>
                                    <?php if (!empty($recurso['fecha'])) : ?>
                                        <time class="flavor-widget-shared__item-date">
                                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($recurso['fecha']))); ?>
                                        </time>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($recurso['url'])) : ?>
                                <a href="<?php echo esc_url($recurso['url']); ?>"
                                   class="flavor-widget-shared__item-link"
                                   aria-label="<?php esc_attr_e('Ver mas', 'flavor-chat-ia'); ?>">
                                    <?php echo $this->obtener_icono_svg('external', 14); ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <a href="<?php echo esc_url(home_url('/recursos-compartidos/')); ?>"
                   class="flavor-widget-shared__view-more">
                    <?php esc_html_e('Ver mas recursos', 'flavor-chat-ia'); ?>
                    <?php echo $this->obtener_icono_svg('external', 14); ?>
                </a>
            <?php else : ?>
                <div class="flavor-widget-empty">
                    <div class="flavor-widget-empty__icon">
                        <?php echo $this->obtener_icono_svg('share', 32); ?>
                    </div>
                    <p><?php esc_html_e('No hay recursos compartidos disponibles', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza widget de Mapa Interactivo
     *
     * @param int $id_usuario ID del usuario
     */
    public function render_widget_mapa_interactivo($id_usuario) {
        // Encolar assets del mapa
        $this->encolar_assets_mapa();

        $marcadores = $this->obtener_marcadores_mapa($id_usuario);
        $configuracion_mapa = $this->obtener_configuracion_mapa();

        ?>
        <div class="flavor-widget-map" data-widget="map">
            <!-- Filtros del mapa -->
            <div class="flavor-widget-map__controls">
                <div class="flavor-widget-map__filters">
                    <?php
                    $categorias_mapa = [
                        'todos'      => ['label' => __('Todos', 'flavor-chat-ia'), 'icon' => 'layers'],
                        'bicicletas' => ['label' => __('Bicicletas', 'flavor-chat-ia'), 'icon' => 'bike'],
                        'parkings'   => ['label' => __('Parkings', 'flavor-chat-ia'), 'icon' => 'parking'],
                        'huertos'    => ['label' => __('Huertos', 'flavor-chat-ia'), 'icon' => 'leaf'],
                        'reciclaje'  => ['label' => __('Reciclaje', 'flavor-chat-ia'), 'icon' => 'recycle'],
                        'espacios'   => ['label' => __('Espacios', 'flavor-chat-ia'), 'icon' => 'home'],
                    ];

                    foreach ($categorias_mapa as $cat_clave => $cat_config) :
                    ?>
                        <button type="button"
                                class="flavor-widget-map__filter-btn <?php echo $cat_clave === 'todos' ? 'flavor-widget-map__filter-btn--active' : ''; ?>"
                                data-category="<?php echo esc_attr($cat_clave); ?>"
                                title="<?php echo esc_attr($cat_config['label']); ?>">
                            <?php echo $this->obtener_icono_svg($cat_config['icon'], 16); ?>
                            <span class="flavor-widget-map__filter-label"><?php echo esc_html($cat_config['label']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <button type="button"
                        class="flavor-widget-map__locate-btn"
                        id="flavor-map-locate"
                        title="<?php esc_attr_e('Mi ubicacion', 'flavor-chat-ia'); ?>">
                    <?php echo $this->obtener_icono_svg('map-pin', 18); ?>
                </button>
            </div>

            <!-- Contenedor del mapa -->
            <div class="flavor-widget-map__container"
                 id="flavor-dashboard-map"
                 data-lat="<?php echo esc_attr($configuracion_mapa['lat']); ?>"
                 data-lng="<?php echo esc_attr($configuracion_mapa['lng']); ?>"
                 data-zoom="<?php echo esc_attr($configuracion_mapa['zoom']); ?>"
                 data-markers="<?php echo esc_attr(wp_json_encode($marcadores)); ?>">
                <div class="flavor-widget-map__loading">
                    <div class="flavor-widget-map__loading-spinner"></div>
                    <span><?php esc_html_e('Cargando mapa...', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <!-- Leyenda del mapa -->
            <div class="flavor-widget-map__legend">
                <span class="flavor-widget-map__legend-item flavor-widget-map__legend-item--bicicletas">
                    <?php echo $this->obtener_icono_svg('bike', 12); ?>
                    <?php esc_html_e('Bicicletas', 'flavor-chat-ia'); ?>
                </span>
                <span class="flavor-widget-map__legend-item flavor-widget-map__legend-item--parkings">
                    <?php echo $this->obtener_icono_svg('parking', 12); ?>
                    <?php esc_html_e('Parkings', 'flavor-chat-ia'); ?>
                </span>
                <span class="flavor-widget-map__legend-item flavor-widget-map__legend-item--huertos">
                    <?php echo $this->obtener_icono_svg('leaf', 12); ?>
                    <?php esc_html_e('Huertos', 'flavor-chat-ia'); ?>
                </span>
                <span class="flavor-widget-map__legend-item flavor-widget-map__legend-item--reciclaje">
                    <?php echo $this->obtener_icono_svg('recycle', 12); ?>
                    <?php esc_html_e('Reciclaje', 'flavor-chat-ia'); ?>
                </span>
            </div>

            <!-- Link expandir mapa -->
            <a href="<?php echo esc_url(home_url('/mapa/')); ?>"
               class="flavor-widget-map__expand-link">
                <?php echo $this->obtener_icono_svg('external', 14); ?>
                <?php esc_html_e('Ver mapa completo', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Renderiza widget de Estadisticas Avanzadas
     *
     * @param int $id_usuario ID del usuario
     */
    public function render_widget_estadisticas_avanzadas($id_usuario) {
        $datos_estadisticas = $this->obtener_estadisticas_avanzadas($id_usuario);
        $actividad_semanal = $datos_estadisticas['actividad_semanal'] ?? [];
        $comparativa = $datos_estadisticas['comparativa'] ?? [];
        $tendencias = $datos_estadisticas['tendencias'] ?? [];

        ?>
        <div class="flavor-widget-stats-panel" data-widget="stats-panel">
            <!-- Selector de periodo -->
            <div class="flavor-widget-stats-panel__header">
                <div class="flavor-widget-stats-panel__period-selector">
                    <button type="button"
                            class="flavor-widget-stats-panel__period-btn flavor-widget-stats-panel__period-btn--active"
                            data-period="7d">
                        <?php esc_html_e('7 dias', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="button"
                            class="flavor-widget-stats-panel__period-btn"
                            data-period="30d">
                        <?php esc_html_e('30 dias', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="button"
                            class="flavor-widget-stats-panel__period-btn"
                            data-period="90d">
                        <?php esc_html_e('90 dias', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </div>

            <!-- Grafico de actividad -->
            <div class="flavor-widget-stats-panel__chart">
                <h4 class="flavor-widget-stats-panel__chart-title">
                    <?php esc_html_e('Tu Actividad', 'flavor-chat-ia'); ?>
                </h4>
                <div class="flavor-widget-stats-panel__chart-container"
                     id="flavor-activity-chart"
                     data-chart-data="<?php echo esc_attr(wp_json_encode($actividad_semanal)); ?>">
                    <!-- Grafico de barras simple en CSS -->
                    <div class="flavor-widget-stats-panel__bars">
                        <?php
                        $dias_semana = [
                            __('Lun', 'flavor-chat-ia'),
                            __('Mar', 'flavor-chat-ia'),
                            __('Mie', 'flavor-chat-ia'),
                            __('Jue', 'flavor-chat-ia'),
                            __('Vie', 'flavor-chat-ia'),
                            __('Sab', 'flavor-chat-ia'),
                            __('Dom', 'flavor-chat-ia'),
                        ];

                        $valor_maximo = !empty($actividad_semanal) ? max(array_column($actividad_semanal, 'valor')) : 1;
                        if ($valor_maximo < 1) {
                            $valor_maximo = 1;
                        }

                        foreach ($dias_semana as $indice => $dia_nombre) :
                            $valor_dia = $actividad_semanal[$indice]['valor'] ?? 0;
                            $porcentaje_altura = ($valor_dia / $valor_maximo) * 100;
                            $es_hoy = $actividad_semanal[$indice]['es_hoy'] ?? false;
                        ?>
                            <div class="flavor-widget-stats-panel__bar-wrapper">
                                <div class="flavor-widget-stats-panel__bar <?php echo $es_hoy ? 'flavor-widget-stats-panel__bar--today' : ''; ?>"
                                     style="height: <?php echo esc_attr(max(5, $porcentaje_altura)); ?>%;"
                                     data-value="<?php echo esc_attr($valor_dia); ?>"
                                     title="<?php echo esc_attr(sprintf(__('%d acciones', 'flavor-chat-ia'), $valor_dia)); ?>">
                                </div>
                                <span class="flavor-widget-stats-panel__bar-label"><?php echo esc_html($dia_nombre); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Comparativa con periodo anterior -->
            <div class="flavor-widget-stats-panel__comparison">
                <h4 class="flavor-widget-stats-panel__section-title">
                    <?php esc_html_e('vs. Semana Anterior', 'flavor-chat-ia'); ?>
                </h4>
                <div class="flavor-widget-stats-panel__comparison-grid">
                    <?php
                    $metricas_comparativa = [
                        'participaciones' => ['label' => __('Participaciones', 'flavor-chat-ia'), 'icon' => 'users'],
                        'reservas'        => ['label' => __('Reservas', 'flavor-chat-ia'), 'icon' => 'calendar'],
                        'interacciones'   => ['label' => __('Interacciones', 'flavor-chat-ia'), 'icon' => 'activity'],
                    ];

                    foreach ($metricas_comparativa as $metrica_clave => $metrica_config) :
                        $valor_actual = $comparativa[$metrica_clave]['actual'] ?? 0;
                        $valor_anterior = $comparativa[$metrica_clave]['anterior'] ?? 0;
                        $diferencia = $valor_actual - $valor_anterior;
                        $porcentaje_cambio = $valor_anterior > 0 ? round(($diferencia / $valor_anterior) * 100) : ($valor_actual > 0 ? 100 : 0);
                        $tendencia = $diferencia > 0 ? 'up' : ($diferencia < 0 ? 'down' : 'neutral');
                    ?>
                        <div class="flavor-widget-stats-panel__comparison-item">
                            <div class="flavor-widget-stats-panel__comparison-icon">
                                <?php echo $this->obtener_icono_svg($metrica_config['icon'], 20); ?>
                            </div>
                            <div class="flavor-widget-stats-panel__comparison-data">
                                <span class="flavor-widget-stats-panel__comparison-value">
                                    <?php echo esc_html(number_format_i18n($valor_actual)); ?>
                                </span>
                                <span class="flavor-widget-stats-panel__comparison-label">
                                    <?php echo esc_html($metrica_config['label']); ?>
                                </span>
                            </div>
                            <div class="flavor-widget-stats-panel__comparison-trend flavor-widget-stats-panel__comparison-trend--<?php echo esc_attr($tendencia); ?>">
                                <?php if ($tendencia === 'up') : ?>
                                    <?php echo $this->obtener_icono_svg('trending-up', 14); ?>
                                    <span>+<?php echo esc_html($porcentaje_cambio); ?>%</span>
                                <?php elseif ($tendencia === 'down') : ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/>
                                        <polyline points="17 18 23 18 23 12"/>
                                    </svg>
                                    <span><?php echo esc_html($porcentaje_cambio); ?>%</span>
                                <?php else : ?>
                                    <span>=</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tendencias del usuario -->
            <?php if (!empty($tendencias)) : ?>
                <div class="flavor-widget-stats-panel__trends">
                    <h4 class="flavor-widget-stats-panel__section-title">
                        <?php esc_html_e('Tus Tendencias', 'flavor-chat-ia'); ?>
                    </h4>
                    <ul class="flavor-widget-stats-panel__trends-list">
                        <?php foreach (array_slice($tendencias, 0, 3) as $tendencia) : ?>
                            <li class="flavor-widget-stats-panel__trend-item flavor-widget-stats-panel__trend-item--<?php echo esc_attr($tendencia['tipo'] ?? 'neutral'); ?>">
                                <span class="flavor-widget-stats-panel__trend-icon">
                                    <?php echo $this->obtener_icono_svg($tendencia['icono'] ?? 'activity', 16); ?>
                                </span>
                                <span class="flavor-widget-stats-panel__trend-text">
                                    <?php echo esc_html($tendencia['texto']); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // =========================================================================
    // Metodos auxiliares para los nuevos widgets
    // =========================================================================

    /**
     * Encola assets del mapa (Leaflet)
     */
    private function encolar_assets_mapa() {
        // Solo encolar si no esta ya cargado
        if (wp_script_is('leaflet', 'enqueued')) {
            return;
        }

        // Leaflet CSS
        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );

        // Leaflet MarkerCluster CSS
        wp_enqueue_style(
            'leaflet-markercluster',
            'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css',
            ['leaflet'],
            '1.4.1'
        );

        wp_enqueue_style(
            'leaflet-markercluster-default',
            'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css',
            ['leaflet-markercluster'],
            '1.4.1'
        );

        // Leaflet JS
        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );

        // Leaflet MarkerCluster JS
        wp_enqueue_script(
            'leaflet-markercluster',
            'https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js',
            ['leaflet'],
            '1.4.1',
            true
        );

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // CSS del mapa del dashboard
        wp_enqueue_style(
            'flavor-dashboard-map',
            FLAVOR_CHAT_IA_URL . "assets/css/dashboard-map{$sufijo_asset}.css",
            ['leaflet', 'leaflet-markercluster'],
            FLAVOR_CHAT_IA_VERSION
        );

        // JS del mapa del dashboard
        wp_enqueue_script(
            'flavor-dashboard-map',
            FLAVOR_CHAT_IA_URL . "assets/js/dashboard-map{$sufijo_asset}.js",
            ['jquery', 'leaflet', 'leaflet-markercluster'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-dashboard-map', 'flavorDashboardMap', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_client_dashboard'),
            'i18n'    => [
                'cargando'        => __('Cargando...', 'flavor-chat-ia'),
                'error_ubicacion' => __('No se pudo obtener tu ubicacion', 'flavor-chat-ia'),
                'ver_detalle'     => __('Ver detalle', 'flavor-chat-ia'),
                'disponible'      => __('Disponible', 'flavor-chat-ia'),
                'ocupado'         => __('Ocupado', 'flavor-chat-ia'),
                'abierto'         => __('Abierto', 'flavor-chat-ia'),
                'cerrado'         => __('Cerrado', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Obtiene datos de la red de comunidades
     *
     * @return array
     */
    private function obtener_datos_red() {
        $datos = [
            'nodos'          => [],
            'estadisticas'   => [
                'total_nodos'        => 0,
                'total_usuarios'     => 0,
                'contenido_compartido' => 0,
            ],
            'actualizaciones' => [],
        ];

        // Intentar obtener datos del Network Manager
        if (class_exists('Flavor_Network_Manager')) {
            global $wpdb;

            $tabla_nodos = $wpdb->prefix . 'flavor_network_nodes';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_nodos'") === $tabla_nodos) {
                // Obtener nodos activos
                $nodos_db = $wpdb->get_results(
                    "SELECT id, nombre, tipo_entidad, logo_url, estado, site_url
                     FROM $tabla_nodos
                     WHERE estado = 'activo'
                     ORDER BY ultima_sincronizacion DESC
                     LIMIT 10",
                    ARRAY_A
                );

                foreach ($nodos_db as $nodo) {
                    $datos['nodos'][] = [
                        'id'     => $nodo['id'],
                        'nombre' => $nodo['nombre'],
                        'tipo'   => $nodo['tipo_entidad'],
                        'logo'   => $nodo['logo_url'],
                        'estado' => $nodo['estado'],
                        'url'    => $nodo['site_url'],
                    ];
                }

                // Estadisticas
                $datos['estadisticas']['total_nodos'] = (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM $tabla_nodos WHERE estado = 'activo'"
                );
            }

            // Contenido compartido
            $tabla_contenido = $wpdb->prefix . 'flavor_network_shared_content';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_contenido'") === $tabla_contenido) {
                $datos['estadisticas']['contenido_compartido'] = (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM $tabla_contenido WHERE estado = 'publicado'"
                );

                // Actualizaciones recientes
                $actualizaciones_db = $wpdb->get_results(
                    "SELECT titulo, tipo, created_at
                     FROM $tabla_contenido
                     WHERE estado = 'publicado'
                     ORDER BY created_at DESC
                     LIMIT 5",
                    ARRAY_A
                );

                foreach ($actualizaciones_db as $act) {
                    $datos['actualizaciones'][] = [
                        'texto' => $act['titulo'],
                        'icono' => $this->obtener_icono_por_tipo($act['tipo']),
                        'fecha' => $act['created_at'],
                    ];
                }
            }

            // Usuarios totales (estimacion basada en conteo local)
            $datos['estadisticas']['total_usuarios'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->users}"
            ) * max(1, $datos['estadisticas']['total_nodos']);
        }

        return apply_filters('flavor_client_dashboard_network_data', $datos);
    }

    /**
     * Obtiene recursos compartidos para el usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    private function obtener_recursos_compartidos_usuario($id_usuario) {
        $recursos = [];

        global $wpdb;

        // Eventos compartidos
        $tabla_eventos = $wpdb->prefix . 'flavor_network_events';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") === $tabla_eventos) {
            $eventos = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, titulo, fecha_inicio, nodo_origen_nombre
                     FROM $tabla_eventos
                     WHERE estado = 'publicado' AND fecha_inicio >= %s
                     ORDER BY fecha_inicio ASC
                     LIMIT 5",
                    current_time('mysql')
                ),
                ARRAY_A
            );

            foreach ($eventos as $evento) {
                $recursos[] = [
                    'id'     => $evento['id'],
                    'titulo' => $evento['titulo'],
                    'tipo'   => 'eventos',
                    'origen' => $evento['nodo_origen_nombre'] ?? __('Red', 'flavor-chat-ia'),
                    'fecha'  => $evento['fecha_inicio'],
                    'url'    => home_url('/eventos/' . $evento['id']),
                ];
            }
        }

        // Contenido compartido general
        $tabla_contenido = $wpdb->prefix . 'flavor_network_shared_content';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_contenido'") === $tabla_contenido) {
            $contenidos = $wpdb->get_results(
                "SELECT id, titulo, tipo, nodo_origen_nombre, created_at
                 FROM $tabla_contenido
                 WHERE estado = 'publicado'
                 ORDER BY created_at DESC
                 LIMIT 10",
                ARRAY_A
            );

            foreach ($contenidos as $contenido) {
                $tipo_mapeado = 'servicios';
                if (in_array($contenido['tipo'], ['oferta', 'promocion', 'descuento'])) {
                    $tipo_mapeado = 'ofertas';
                } elseif (in_array($contenido['tipo'], ['servicio', 'profesional'])) {
                    $tipo_mapeado = 'servicios';
                }

                $recursos[] = [
                    'id'     => $contenido['id'],
                    'titulo' => $contenido['titulo'],
                    'tipo'   => $tipo_mapeado,
                    'origen' => $contenido['nodo_origen_nombre'] ?? __('Red', 'flavor-chat-ia'),
                    'fecha'  => $contenido['created_at'],
                    'url'    => home_url('/recursos/' . $contenido['id']),
                ];
            }
        }

        // Ordenar por fecha
        usort($recursos, function ($recurso_a, $recurso_b) {
            return strtotime($recurso_b['fecha'] ?? '0') - strtotime($recurso_a['fecha'] ?? '0');
        });

        return apply_filters('flavor_client_dashboard_shared_resources', $recursos, $id_usuario);
    }

    /**
     * Obtiene marcadores para el mapa
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    private function obtener_marcadores_mapa($id_usuario) {
        $marcadores = [];

        global $wpdb;

        // Bicicletas compartidas
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas_estaciones';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_bicicletas'") === $tabla_bicicletas) {
            $estaciones = $wpdb->get_results(
                "SELECT id, nombre, latitud, longitud, bicicletas_disponibles, capacidad
                 FROM $tabla_bicicletas
                 WHERE estado = 'activo' AND latitud IS NOT NULL AND longitud IS NOT NULL
                 LIMIT 50",
                ARRAY_A
            );

            foreach ($estaciones as $estacion) {
                $marcadores[] = [
                    'id'        => 'bike-' . $estacion['id'],
                    'lat'       => (float) $estacion['latitud'],
                    'lng'       => (float) $estacion['longitud'],
                    'titulo'    => $estacion['nombre'],
                    'categoria' => 'bicicletas',
                    'icono'     => 'bike',
                    'info'      => sprintf(
                        __('%d/%d disponibles', 'flavor-chat-ia'),
                        $estacion['bicicletas_disponibles'],
                        $estacion['capacidad']
                    ),
                    'url'       => home_url('/bicicletas/estacion/' . $estacion['id']),
                ];
            }
        }

        // Parkings
        $tabla_parkings = $wpdb->prefix . 'flavor_parkings';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_parkings'") === $tabla_parkings) {
            $parkings = $wpdb->get_results(
                "SELECT id, nombre, latitud, longitud, plazas_libres, plazas_totales
                 FROM $tabla_parkings
                 WHERE estado = 'activo' AND latitud IS NOT NULL AND longitud IS NOT NULL
                 LIMIT 50",
                ARRAY_A
            );

            foreach ($parkings as $parking) {
                $marcadores[] = [
                    'id'        => 'parking-' . $parking['id'],
                    'lat'       => (float) $parking['latitud'],
                    'lng'       => (float) $parking['longitud'],
                    'titulo'    => $parking['nombre'],
                    'categoria' => 'parkings',
                    'icono'     => 'parking',
                    'info'      => sprintf(
                        __('%d plazas libres', 'flavor-chat-ia'),
                        $parking['plazas_libres'] ?? 0
                    ),
                    'url'       => home_url('/parkings/' . $parking['id']),
                ];
            }
        }

        // Huertos urbanos
        $tabla_huertos = $wpdb->prefix . 'flavor_huertos';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_huertos'") === $tabla_huertos) {
            $huertos = $wpdb->get_results(
                "SELECT id, nombre, latitud, longitud, parcelas_disponibles
                 FROM $tabla_huertos
                 WHERE estado = 'activo' AND latitud IS NOT NULL AND longitud IS NOT NULL
                 LIMIT 50",
                ARRAY_A
            );

            foreach ($huertos as $huerto) {
                $marcadores[] = [
                    'id'        => 'huerto-' . $huerto['id'],
                    'lat'       => (float) $huerto['latitud'],
                    'lng'       => (float) $huerto['longitud'],
                    'titulo'    => $huerto['nombre'],
                    'categoria' => 'huertos',
                    'icono'     => 'leaf',
                    'info'      => sprintf(
                        __('%d parcelas', 'flavor-chat-ia'),
                        $huerto['parcelas_disponibles'] ?? 0
                    ),
                    'url'       => home_url('/huertos/' . $huerto['id']),
                ];
            }
        }

        // Puntos de reciclaje
        $tabla_reciclaje = $wpdb->prefix . 'flavor_puntos_reciclaje';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_reciclaje'") === $tabla_reciclaje) {
            $puntos = $wpdb->get_results(
                "SELECT id, nombre, latitud, longitud, tipos_residuos
                 FROM $tabla_reciclaje
                 WHERE estado = 'activo' AND latitud IS NOT NULL AND longitud IS NOT NULL
                 LIMIT 50",
                ARRAY_A
            );

            foreach ($puntos as $punto) {
                $marcadores[] = [
                    'id'        => 'reciclaje-' . $punto['id'],
                    'lat'       => (float) $punto['latitud'],
                    'lng'       => (float) $punto['longitud'],
                    'titulo'    => $punto['nombre'],
                    'categoria' => 'reciclaje',
                    'icono'     => 'recycle',
                    'info'      => $punto['tipos_residuos'] ?? '',
                    'url'       => home_url('/reciclaje/punto/' . $punto['id']),
                ];
            }
        }

        // Espacios comunes
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_espacios'") === $tabla_espacios) {
            $espacios = $wpdb->get_results(
                "SELECT id, nombre, latitud, longitud, capacidad
                 FROM $tabla_espacios
                 WHERE estado = 'activo' AND latitud IS NOT NULL AND longitud IS NOT NULL
                 LIMIT 50",
                ARRAY_A
            );

            foreach ($espacios as $espacio) {
                $marcadores[] = [
                    'id'        => 'espacio-' . $espacio['id'],
                    'lat'       => (float) $espacio['latitud'],
                    'lng'       => (float) $espacio['longitud'],
                    'titulo'    => $espacio['nombre'],
                    'categoria' => 'espacios',
                    'icono'     => 'home',
                    'info'      => sprintf(
                        __('Capacidad: %d', 'flavor-chat-ia'),
                        $espacio['capacidad'] ?? 0
                    ),
                    'url'       => home_url('/espacios/' . $espacio['id']),
                ];
            }
        }

        return apply_filters('flavor_client_dashboard_map_markers', $marcadores, $id_usuario);
    }

    /**
     * Obtiene configuracion del mapa
     *
     * @return array
     */
    private function obtener_configuracion_mapa() {
        $configuracion_defecto = [
            'lat'  => 40.4168,  // Madrid por defecto
            'lng'  => -3.7038,
            'zoom' => 13,
        ];

        // Intentar obtener ubicacion de opciones del sitio
        $lat_guardada = get_option('flavor_default_lat');
        $lng_guardada = get_option('flavor_default_lng');
        $zoom_guardado = get_option('flavor_default_zoom');

        if ($lat_guardada && $lng_guardada) {
            $configuracion_defecto['lat'] = (float) $lat_guardada;
            $configuracion_defecto['lng'] = (float) $lng_guardada;
        }

        if ($zoom_guardado) {
            $configuracion_defecto['zoom'] = (int) $zoom_guardado;
        }

        return apply_filters('flavor_client_dashboard_map_config', $configuracion_defecto);
    }

    /**
     * Obtiene estadisticas avanzadas del usuario
     *
     * @param int $id_usuario ID del usuario
     * @return array
     */
    private function obtener_estadisticas_avanzadas($id_usuario) {
        global $wpdb;

        $datos = [
            'actividad_semanal' => [],
            'comparativa'       => [
                'participaciones' => ['actual' => 0, 'anterior' => 0],
                'reservas'        => ['actual' => 0, 'anterior' => 0],
                'interacciones'   => ['actual' => 0, 'anterior' => 0],
            ],
            'tendencias'        => [],
        ];

        // Calcular actividad de los ultimos 7 dias
        $hoy = current_time('Y-m-d');
        $hace_7_dias = date('Y-m-d', strtotime('-6 days', strtotime($hoy)));
        $hace_14_dias = date('Y-m-d', strtotime('-13 days', strtotime($hoy)));
        $dia_semana_actual = (int) date('N', strtotime($hoy));

        // Generar datos para cada dia de la semana
        for ($indice_dia = 0; $indice_dia < 7; $indice_dia++) {
            $fecha_dia = date('Y-m-d', strtotime("-" . (6 - $indice_dia) . " days", strtotime($hoy)));
            $es_hoy = $fecha_dia === $hoy;

            // Contar actividades del usuario en ese dia
            $total_actividad = 0;

            // Reservas
            $tabla_reservas = $wpdb->prefix . 'flavor_reservations';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_reservas'") === $tabla_reservas) {
                $reservas_dia = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_reservas
                     WHERE user_id = %d AND DATE(created_at) = %s",
                    $id_usuario,
                    $fecha_dia
                ));
                $total_actividad += $reservas_dia;
            }

            // Comentarios/participaciones
            $comentarios_dia = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->comments}
                 WHERE user_id = %d AND DATE(comment_date) = %s",
                $id_usuario,
                $fecha_dia
            ));
            $total_actividad += $comentarios_dia;

            $datos['actividad_semanal'][] = [
                'fecha' => $fecha_dia,
                'valor' => $total_actividad,
                'es_hoy' => $es_hoy,
            ];
        }

        // Comparativa semana actual vs anterior
        // Participaciones (comentarios)
        $datos['comparativa']['participaciones']['actual'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments}
             WHERE user_id = %d AND comment_date >= %s",
            $id_usuario,
            $hace_7_dias . ' 00:00:00'
        ));

        $datos['comparativa']['participaciones']['anterior'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments}
             WHERE user_id = %d AND comment_date >= %s AND comment_date < %s",
            $id_usuario,
            $hace_14_dias . ' 00:00:00',
            $hace_7_dias . ' 00:00:00'
        ));

        // Reservas
        $tabla_reservas = $wpdb->prefix . 'flavor_reservations';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_reservas'") === $tabla_reservas) {
            $datos['comparativa']['reservas']['actual'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_reservas
                 WHERE user_id = %d AND created_at >= %s",
                $id_usuario,
                $hace_7_dias . ' 00:00:00'
            ));

            $datos['comparativa']['reservas']['anterior'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_reservas
                 WHERE user_id = %d AND created_at >= %s AND created_at < %s",
                $id_usuario,
                $hace_14_dias . ' 00:00:00',
                $hace_7_dias . ' 00:00:00'
            ));
        }

        // Interacciones generales
        $datos['comparativa']['interacciones']['actual'] =
            $datos['comparativa']['participaciones']['actual'] +
            $datos['comparativa']['reservas']['actual'];

        $datos['comparativa']['interacciones']['anterior'] =
            $datos['comparativa']['participaciones']['anterior'] +
            $datos['comparativa']['reservas']['anterior'];

        // Generar tendencias
        $total_actividad_semana = array_sum(array_column($datos['actividad_semanal'], 'valor'));

        if ($total_actividad_semana > 10) {
            $datos['tendencias'][] = [
                'texto' => __('Semana muy activa! Sigue asi.', 'flavor-chat-ia'),
                'icono' => 'trending-up',
                'tipo'  => 'positivo',
            ];
        } elseif ($total_actividad_semana > 5) {
            $datos['tendencias'][] = [
                'texto' => __('Buena actividad esta semana.', 'flavor-chat-ia'),
                'icono' => 'activity',
                'tipo'  => 'neutral',
            ];
        } else {
            $datos['tendencias'][] = [
                'texto' => __('Podrias participar mas en la comunidad.', 'flavor-chat-ia'),
                'icono' => 'activity',
                'tipo'  => 'sugerencia',
            ];
        }

        if ($datos['comparativa']['reservas']['actual'] > $datos['comparativa']['reservas']['anterior']) {
            $datos['tendencias'][] = [
                'texto' => __('Mas reservas que la semana pasada.', 'flavor-chat-ia'),
                'icono' => 'calendar',
                'tipo'  => 'positivo',
            ];
        }

        return apply_filters('flavor_client_dashboard_advanced_stats', $datos, $id_usuario);
    }

    /**
     * Obtiene icono segun tipo de contenido
     *
     * @param string $tipo Tipo de contenido
     * @return string
     */
    private function obtener_icono_por_tipo($tipo) {
        $iconos = [
            'evento'     => 'calendar',
            'oferta'     => 'shopping-bag',
            'servicio'   => 'coffee',
            'recurso'    => 'layers',
            'producto'   => 'box',
            'espacio'    => 'home',
            'proyecto'   => 'activity',
        ];

        return $iconos[$tipo] ?? 'activity';
    }

    // =========================================================================
    // Endpoints AJAX para nuevos widgets
    // =========================================================================

    /**
     * AJAX: Obtener datos de la red
     */
    public function ajax_obtener_datos_red() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $datos_red = $this->obtener_datos_red();

        wp_send_json_success($datos_red);
    }

    /**
     * AJAX: Obtener recursos compartidos
     */
    public function ajax_obtener_recursos_compartidos() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_usuario = get_current_user_id();
        $tipo_filtro = isset($_POST['tipo']) ? sanitize_key($_POST['tipo']) : 'todos';

        $recursos = $this->obtener_recursos_compartidos_usuario($id_usuario);

        // Filtrar por tipo si es necesario
        if ($tipo_filtro !== 'todos') {
            $recursos = array_filter($recursos, function ($recurso) use ($tipo_filtro) {
                return $recurso['tipo'] === $tipo_filtro;
            });
        }

        wp_send_json_success([
            'recursos' => array_values($recursos),
            'total'    => count($recursos),
        ]);
    }

    /**
     * AJAX: Obtener marcadores del mapa
     */
    public function ajax_obtener_marcadores_mapa() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_usuario = get_current_user_id();
        $categoria = isset($_POST['categoria']) ? sanitize_key($_POST['categoria']) : 'todos';

        $marcadores = $this->obtener_marcadores_mapa($id_usuario);

        // Filtrar por categoria si es necesario
        if ($categoria !== 'todos') {
            $marcadores = array_filter($marcadores, function ($marcador) use ($categoria) {
                return $marcador['categoria'] === $categoria;
            });
        }

        wp_send_json_success([
            'marcadores'    => array_values($marcadores),
            'total'         => count($marcadores),
            'configuracion' => $this->obtener_configuracion_mapa(),
        ]);
    }

    /**
     * AJAX: Obtener estadisticas avanzadas
     */
    public function ajax_obtener_estadisticas_avanzadas() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-chat-ia')]);
        }

        $id_usuario = get_current_user_id();
        $periodo = isset($_POST['periodo']) ? sanitize_key($_POST['periodo']) : '7d';

        $datos = $this->obtener_estadisticas_avanzadas($id_usuario);

        wp_send_json_success($datos);
    }

    /**
     * Agrupa widgets locales por categoria
     *
     * Usado como fallback cuando no hay widgets del Widget Registry.
     *
     * @param array $widgets Widgets locales
     * @return array Widgets agrupados
     * @since 4.1.0
     */
    private function agrupar_widgets_por_categoria($widgets) {
        $agrupados = [];
        $categorias_info = $this->obtener_definicion_categorias();

        foreach ($widgets as $widget_id => $widget_config) {
            // Determinar categoria del widget
            $categoria = $widget_config['modulo'] ?? 'general';
            $categoria_mapeada = $this->mapear_modulo_a_categoria($categoria);

            if (!isset($agrupados[$categoria_mapeada])) {
                $agrupados[$categoria_mapeada] = [
                    'info'      => $categorias_info[$categoria_mapeada] ?? [
                        'label' => ucfirst($categoria_mapeada),
                        'icon'  => 'dashicons-admin-generic',
                        'color' => '#6b7280',
                        'order' => 50,
                    ],
                    'widgets'   => [],
                    'collapsed' => false,
                    'count'     => 0,
                ];
            }

            // Añadir widget al grupo
            $agrupados[$categoria_mapeada]['widgets'][$widget_id] = $widget_config;
            $agrupados[$categoria_mapeada]['count']++;
        }

        // Ordenar grupos por orden
        uasort($agrupados, function ($grupo_a, $grupo_b) {
            return ($grupo_a['info']['order'] ?? 50) - ($grupo_b['info']['order'] ?? 50);
        });

        return $agrupados;
    }

    /**
     * Obtiene las categorias de los widgets
     *
     * @param array $widgets Widgets
     * @return array Categorias con conteo
     * @since 4.1.0
     */
    private function obtener_categorias_de_widgets($widgets) {
        $categorias_info = $this->obtener_definicion_categorias();
        $categorias_con_conteo = [];

        foreach ($widgets as $widget_id => $widget_config) {
            $categoria = $widget_config['modulo'] ?? 'general';
            $categoria_mapeada = $this->mapear_modulo_a_categoria($categoria);

            if (!isset($categorias_con_conteo[$categoria_mapeada])) {
                $info_base = $categorias_info[$categoria_mapeada] ?? [
                    'label' => ucfirst($categoria_mapeada),
                    'icon'  => 'dashicons-admin-generic',
                    'color' => '#6b7280',
                    'order' => 50,
                ];
                $categorias_con_conteo[$categoria_mapeada] = array_merge($info_base, ['count' => 0]);
            }
            $categorias_con_conteo[$categoria_mapeada]['count']++;
        }

        // Ordenar por orden
        uasort($categorias_con_conteo, function ($categoria_a, $categoria_b) {
            return ($categoria_a['order'] ?? 50) - ($categoria_b['order'] ?? 50);
        });

        return $categorias_con_conteo;
    }

    /**
     * Mapea un modulo a su categoria correspondiente
     *
     * @param string $modulo ID del modulo
     * @return string ID de categoria
     * @since 4.1.0
     */
    private function mapear_modulo_a_categoria($modulo) {
        $mapeo = [
            // Operaciones
            'reservas'              => 'operaciones',
            'espacios-comunes'      => 'operaciones',
            'incidencias'           => 'operaciones',
            'fichaje'               => 'operaciones',

            // Recursos
            'biblioteca'            => 'recursos',
            'bicicletas-compartidas' => 'recursos',
            'parkings'              => 'recursos',
            'huertos-urbanos'       => 'recursos',

            // Economia
            'grupos-consumo'        => 'economia',
            'marketplace'           => 'economia',
            'banco-tiempo'          => 'economia',
            'facturas'              => 'economia',
            'trading-ia'            => 'economia',

            // Comunicacion
            'foros'                 => 'comunicacion',
            'avisos-municipales'    => 'comunicacion',
            'podcast'               => 'comunicacion',
            'radio'                 => 'comunicacion',

            // Actividades
            'eventos'               => 'actividades',
            'cursos'                => 'actividades',
            'talleres'              => 'actividades',

            // Sostenibilidad
            'reciclaje'             => 'sostenibilidad',
            'compostaje'            => 'sostenibilidad',
            'carpooling'            => 'sostenibilidad',

            // Comunidad
            'red-social'            => 'comunidad',
            'participacion'         => 'comunidad',
            'comunidades'           => 'comunidad',

            // Servicios
            'tramites'              => 'servicios',
            'transparencia'         => 'servicios',

            // Red
            'network'               => 'red',
            'shared'                => 'red',
            'map'                   => 'red',

            // General / Sistema
            'general'               => 'gestion',
            'activity'              => 'gestion',
            'quick-actions'         => 'gestion',
            'stats-panel'           => 'gestion',
        ];

        return $mapeo[$modulo] ?? 'gestion';
    }

    /**
     * Obtiene la definicion de categorias
     *
     * @return array
     * @since 4.1.0
     */
    private function obtener_definicion_categorias() {
        return [
            'operaciones' => [
                'label'       => __('Operaciones', 'flavor-chat-ia'),
                'icon'        => 'dashicons-admin-tools',
                'color'       => '#f97316',
                'order'       => 10,
                'description' => __('Reservas, fichaje e incidencias', 'flavor-chat-ia'),
            ],
            'recursos' => [
                'label'       => __('Recursos', 'flavor-chat-ia'),
                'icon'        => 'dashicons-archive',
                'color'       => '#14b8a6',
                'order'       => 20,
                'description' => __('Espacios, equipamiento y biblioteca', 'flavor-chat-ia'),
            ],
            'economia' => [
                'label'       => __('Economia', 'flavor-chat-ia'),
                'icon'        => 'dashicons-chart-line',
                'color'       => '#10b981',
                'order'       => 30,
                'description' => __('Finanzas y transacciones', 'flavor-chat-ia'),
            ],
            'comunicacion' => [
                'label'       => __('Comunicacion', 'flavor-chat-ia'),
                'icon'        => 'dashicons-megaphone',
                'color'       => '#8b5cf6',
                'order'       => 40,
                'description' => __('Mensajeria y avisos', 'flavor-chat-ia'),
            ],
            'actividades' => [
                'label'       => __('Actividades', 'flavor-chat-ia'),
                'icon'        => 'dashicons-calendar-alt',
                'color'       => '#a855f7',
                'order'       => 50,
                'description' => __('Eventos y formacion', 'flavor-chat-ia'),
            ],
            'sostenibilidad' => [
                'label'       => __('Sostenibilidad', 'flavor-chat-ia'),
                'icon'        => 'dashicons-palmtree',
                'color'       => '#84cc16',
                'order'       => 60,
                'description' => __('Medio ambiente', 'flavor-chat-ia'),
            ],
            'comunidad' => [
                'label'       => __('Comunidad', 'flavor-chat-ia'),
                'icon'        => 'dashicons-groups',
                'color'       => '#f59e0b',
                'order'       => 70,
                'description' => __('Participacion y vida social', 'flavor-chat-ia'),
            ],
            'servicios' => [
                'label'       => __('Servicios', 'flavor-chat-ia'),
                'icon'        => 'dashicons-admin-site',
                'color'       => '#0ea5e9',
                'order'       => 80,
                'description' => __('Tramites y soporte', 'flavor-chat-ia'),
            ],
            'red' => [
                'label'       => __('Red de Nodos', 'flavor-chat-ia'),
                'icon'        => 'dashicons-networking',
                'color'       => '#06b6d4',
                'order'       => 90,
                'description' => __('Red federada', 'flavor-chat-ia'),
            ],
            'gestion' => [
                'label'       => __('Gestion', 'flavor-chat-ia'),
                'icon'        => 'dashicons-clipboard',
                'color'       => '#3b82f6',
                'order'       => 5,
                'description' => __('Panel general', 'flavor-chat-ia'),
            ],
        ];
    }
}

// Inicializar
Flavor_Client_Dashboard::get_instance();
