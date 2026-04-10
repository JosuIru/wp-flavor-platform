<?php
/**
 * Dashboard de Cliente Frontend
 *
 * Renderiza un dashboard completo para usuarios con estadisticas,
 * widgets modulares, actividad reciente y accesos rapidos.
 * Los modulos pueden registrar sus propios widgets.
 *
 * @package FlavorPlatform
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
     * Obtiene la URL actual para redirects de login en el dashboard.
     */
    private function get_current_request_url(): string {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';
        $request_uri = '/' . ltrim($request_uri, '/');

        return home_url($request_uri);
    }

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
        $version = FLAVOR_PLATFORM_VERSION;
        $plugin_url = FLAVOR_PLATFORM_URL;

        // =====================================================================
        // CSS - Sistema de Diseno Unificado (v4.1.0)
        // =====================================================================

        // 1. Design Tokens (variables CSS base)
        wp_enqueue_style(
            'fl-design-tokens',
            $plugin_url . 'assets/css/core/design-tokens.css',
            [],
            $version
        );

        // 2. Compatibilidad con variables antiguas
        wp_enqueue_style(
            'fl-design-tokens-compat',
            $plugin_url . 'assets/css/core/design-tokens-compat.css',
            ['fl-design-tokens'],
            $version
        );

        // 3. CSS Base del dashboard
        wp_enqueue_style(
            'fl-dashboard-base',
            $plugin_url . 'assets/css/layouts/dashboard-base.css',
            ['fl-design-tokens-compat'],
            $version
        );

        // 4. Widgets y niveles
        wp_enqueue_style(
            'fl-dashboard-widgets',
            $plugin_url . 'assets/css/layouts/dashboard-widgets.css',
            ['fl-dashboard-base'],
            $version
        );

        // 5. Grupos y categorias
        wp_enqueue_style(
            'fl-dashboard-groups',
            $plugin_url . 'assets/css/layouts/dashboard-groups.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 6. Estados visuales
        wp_enqueue_style(
            'fl-dashboard-states',
            $plugin_url . 'assets/css/layouts/dashboard-states.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 7. Accesibilidad
        wp_enqueue_style(
            'fl-dashboard-a11y',
            $plugin_url . 'assets/css/layouts/dashboard-a11y.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 8. Responsive
        wp_enqueue_style(
            'fl-dashboard-responsive',
            $plugin_url . 'assets/css/layouts/dashboard-responsive.css',
            ['fl-dashboard-groups'],
            $version
        );

        // 9. Breadcrumbs
        wp_enqueue_style(
            'fl-breadcrumbs',
            $plugin_url . 'assets/css/components/breadcrumbs.css',
            ['fl-design-tokens'],
            $version
        );

        // 10. Client Dashboard (estilos especificos)
        wp_enqueue_style(
            'flavor-client-dashboard',
            $plugin_url . "assets/css/layouts/client-dashboard{$sufijo_asset}.css",
            ['fl-dashboard-responsive', 'fl-breadcrumbs'],
            $version
        );

        // 11. Estilos unificados UI/UX (mejoras visuales v4.2)
        wp_enqueue_style(
            'flavor-client-dashboard-unified',
            $plugin_url . 'assets/css/layouts/client-dashboard-unified.css',
            ['flavor-client-dashboard'],
            $version
        );

        // Scripts
        wp_enqueue_script(
            'flavor-client-dashboard',
            FLAVOR_PLATFORM_URL . "assets/js/client-dashboard{$sufijo_asset}.js",
            ['jquery'],
            FLAVOR_PLATFORM_VERSION,
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
                'cargando'                => __('Cargando...', 'flavor-platform'),
                'error_conexion'          => __('Error de conexion. Intentalo de nuevo.', 'flavor-platform'),
                'actualizado'             => __('Datos actualizados', 'flavor-platform'),
                'sin_actividad'           => __('No hay actividad reciente', 'flavor-platform'),
                'sin_notificaciones'      => __('No tienes notificaciones pendientes', 'flavor-platform'),
                'notificacion_descartada' => __('Notificacion descartada', 'flavor-platform'),
                'preferencias_guardadas'  => __('Preferencias guardadas', 'flavor-platform'),
                'ver_todo'                => __('Ver todo', 'flavor-platform'),
                'hace_momentos'           => __('Hace unos momentos', 'flavor-platform'),
                'hace_minutos'            => __('Hace %d minutos', 'flavor-platform'),
                'hace_horas'              => __('Hace %d horas', 'flavor-platform'),
                'hace_dias'               => __('Hace %d dias', 'flavor-platform'),
                'atajo_actualizar'        => __('Ctrl+R para actualizar', 'flavor-platform'),
                'atajo_buscar'            => __('Ctrl+K para buscar', 'flavor-platform'),
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
            'label'    => __('Mis Reservas', 'flavor-platform'),
            'icon'     => 'calendar',
            'color'    => 'primary',
            'callback' => [$this, 'obtener_estadistica_reservas'],
            'url'      => home_url('/mi-cuenta/?tab=reservas'),
            'orden'    => 10,
        ]);

        // Estadistica: Participaciones
        $this->registrar_estadistica('participaciones', [
            'label'    => __('Participaciones', 'flavor-platform'),
            'icon'     => 'users',
            'color'    => 'success',
            'callback' => [$this, 'obtener_estadistica_participaciones'],
            'url'      => home_url('/mi-cuenta/?tab=participaciones'),
            'orden'    => 20,
        ]);

        // Estadistica: Puntos
        $this->registrar_estadistica('puntos', [
            'label'    => __('Mis Puntos', 'flavor-platform'),
            'icon'     => 'star',
            'color'    => 'warning',
            'callback' => [$this, 'obtener_estadistica_puntos'],
            'url'      => home_url('/mi-cuenta/?tab=puntos'),
            'orden'    => 30,
        ]);

        // Estadistica: Mensajes sin leer
        $this->registrar_estadistica('mensajes', [
            'label'    => __('Mensajes', 'flavor-platform'),
            'icon'     => 'message',
            'color'    => 'info',
            'callback' => [$this, 'obtener_estadistica_mensajes'],
            'url'      => home_url('/mi-cuenta/?tab=mensajes'),
            'orden'    => 40,
        ]);

        // Atajos rapidos por defecto
        $this->registrar_atajo('nueva-reserva', [
            'label'  => __('Nueva Reserva', 'flavor-platform'),
            'icon'   => 'plus-circle',
            'url'    => home_url('/reservas/nueva/'),
            'color'  => 'primary',
            'orden'  => 10,
        ]);

        $this->registrar_atajo('mi-perfil', [
            'label'  => __('Mi Perfil', 'flavor-platform'),
            'icon'   => 'user',
            'url'    => home_url('/mi-cuenta/?tab=perfil'),
            'color'  => 'secondary',
            'orden'  => 20,
        ]);

        $this->registrar_atajo('soporte', [
            'label'  => __('Soporte', 'flavor-platform'),
            'icon'   => 'help-circle',
            'url'    => home_url('/soporte/'),
            'color'  => 'info',
            'orden'  => 30,
        ]);

        // Widget: Proximas reservas
        $this->registrar_widget('proximas-reservas', [
            'title'    => __('Proximas Reservas', 'flavor-platform'),
            'icon'     => 'calendar',
            'callback' => [$this, 'render_widget_proximas_reservas'],
            'size'     => 'medium',
            'orden'    => 10,
        ]);

        // Widget: Mensajes recientes
        $this->registrar_widget('mensajes-recientes', [
            'title'    => __('Mensajes Recientes', 'flavor-platform'),
            'icon'     => 'message',
            'callback' => [$this, 'render_widget_mensajes_recientes'],
            'size'     => 'medium',
            'orden'    => 20,
        ]);

        // Widget: Red de Comunidades
        $this->registrar_widget('widget-network', [
            'title'    => __('Red de Comunidades', 'flavor-platform'),
            'icon'     => 'globe',
            'callback' => [$this, 'render_widget_red_comunidades'],
            'size'     => 'medium',
            'orden'    => 30,
        ]);

        // Widget: Recursos Compartidos
        $this->registrar_widget('widget-shared', [
            'title'    => __('Recursos Compartidos', 'flavor-platform'),
            'icon'     => 'share',
            'callback' => [$this, 'render_widget_recursos_compartidos'],
            'size'     => 'medium',
            'orden'    => 40,
        ]);

        // Widget: Mapa Interactivo
        $this->registrar_widget('widget-map', [
            'title'    => __('Mapa Interactivo', 'flavor-platform'),
            'icon'     => 'map',
            'callback' => [$this, 'render_widget_mapa_interactivo'],
            'size'     => 'large',
            'orden'    => 50,
        ]);

        // Widget: Panel de Estadisticas Avanzadas
        $this->registrar_widget('widget-stats-panel', [
            'title'    => __('Estadisticas Avanzadas', 'flavor-platform'),
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
        if (!class_exists('Flavor_Platform_Module_Loader')) {
            return;
        }

        $loader = Flavor_Platform_Module_Loader::get_instance();
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
                        'ecosystem' => $this->build_dashboard_ecosystem_metadata($modulo_id, $instancia_modulo),
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
                'modulo' => $modulo_id_normalizado,
                'ecosystem' => $this->build_dashboard_ecosystem_metadata($modulo_id, $instancia_modulo),
            ]);
        }
    }

    private function build_dashboard_ecosystem_metadata($module_id, $module_instance) {
        $metadata = [
            'module_id' => $this->normalize_module_id($module_id),
            'module_role' => 'vertical',
            'depends_on' => [],
            'base_for_modules' => [],
            'parent_module' => $this->normalize_module_id($module_id),
            'satellite_priority' => 50,
            'transversal_priority' => 50,
            'client_contexts' => [],
        ];

        if (is_object($module_instance) && method_exists($module_instance, 'get_ecosystem_metadata')) {
            $ecosystem = $module_instance->get_ecosystem_metadata();
            if (is_array($ecosystem)) {
                $metadata['module_role'] = $ecosystem['module_role'] ?? 'vertical';
                $metadata['depends_on'] = array_values(array_map([$this, 'normalize_module_id'], (array) ($ecosystem['depends_on'] ?? [])));
                $metadata['base_for_modules'] = array_values(array_map([$this, 'normalize_module_id'], (array) ($ecosystem['base_for_modules'] ?? [])));
            }
        }

        if (is_object($module_instance) && method_exists($module_instance, 'get_dashboard_metadata')) {
            $dashboard = $module_instance->get_dashboard_metadata();
            if (is_array($dashboard)) {
                $metadata['satellite_priority'] = isset($dashboard['satellite_priority']) ? absint($dashboard['satellite_priority']) : 50;
                $metadata['transversal_priority'] = isset($dashboard['transversal_priority']) ? absint($dashboard['transversal_priority']) : 50;
                $metadata['client_contexts'] = array_values(array_map([$this, 'sanitize_client_context'], (array) ($dashboard['client_contexts'] ?? [])));

                $dashboard_parent = $this->normalize_module_id($dashboard['parent_module'] ?? '');
                if ($dashboard_parent !== '') {
                    $metadata['parent_module'] = $dashboard_parent;
                }
            }
        }

        if ($metadata['parent_module'] === $metadata['module_id'] && $metadata['module_role'] !== 'base' && !empty($metadata['depends_on'])) {
            $metadata['parent_module'] = $metadata['depends_on'][0];
        }

        if ($metadata['parent_module'] === $metadata['module_id'] && $metadata['module_role'] !== 'base') {
            $inferred_parent = $this->find_dashboard_base_parent_module($metadata['module_id']);
            if ($inferred_parent !== '') {
                $metadata['parent_module'] = $inferred_parent;
            }
        }

        return $metadata;
    }

    /**
     * Resuelve una base declarativa a partir de base_for_modules.
     *
     * @param string $module_id
     * @return string
     */
    private function find_dashboard_base_parent_module($module_id) {
        static $base_parent_map = null;

        $module_id = $this->normalize_module_id($module_id);
        if ($module_id === '') {
            return '';
        }

        if (is_array($base_parent_map)) {
            return $base_parent_map[$module_id] ?? '';
        }

        $base_parent_map = [];

        if (!class_exists('Flavor_Platform_Module_Loader')) {
            return '';
        }

        $loader = Flavor_Platform_Module_Loader::get_instance();
        if (!$loader) {
            return '';
        }

        $registered_modules = $loader->get_registered_modules();
        foreach ($registered_modules as $candidate_id => $candidate_module) {
            $ecosystem = is_array($candidate_module['ecosystem'] ?? null) ? $candidate_module['ecosystem'] : [];
            if (($ecosystem['module_role'] ?? '') !== 'base') {
                continue;
            }

            $base_for_modules = array_values(array_map([$this, 'normalize_module_id'], (array) ($ecosystem['base_for_modules'] ?? [])));
            $candidate_id = $this->normalize_module_id($candidate_id);
            foreach ($base_for_modules as $child_module_id) {
                if ($child_module_id !== '') {
                    $base_parent_map[$child_module_id] = $candidate_id;
                }
            }
        }

        return $base_parent_map[$module_id] ?? '';
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
                    <?php esc_html_e('Ver más', 'flavor-platform'); ?>
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
            'ecosystem' => [],
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
            'modulo' => '',
            'ecosystem' => [],
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

    private function obtener_jerarquia_ecosistema_dashboard($widgets, $atajos, $dashboard_contexts = []) {
        if (!class_exists('Flavor_Platform_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Platform_Module_Loader::get_instance();
        $registered_modules = $loader->get_registered_modules();
        $nodes = [];
        $active_module_ids = [];
        $dashboard_contexts = array_values(array_unique(array_filter(array_map([$this, 'sanitize_client_context'], (array) $dashboard_contexts))));

        foreach ($widgets as $widget_id => $widget) {
            $widget_module_id = $this->normalize_module_id($widget['modulo'] ?? '');
            if ($widget_module_id !== '') {
                $active_module_ids[$widget_module_id] = true;
            }
            $this->adjuntar_item_a_jerarquia_dashboard(
                $nodes,
                $registered_modules,
                $widget['modulo'] ?? '',
                $widget['ecosystem'] ?? [],
                $dashboard_contexts,
                'widget',
                [
                    'id' => $widget_id,
                    'label' => $widget['title'] ?? $widget_id,
                ]
            );
        }

        foreach ($atajos as $shortcut_id => $shortcut) {
            $shortcut_module_id = $this->normalize_module_id($shortcut['modulo'] ?? '');
            if ($shortcut_module_id !== '') {
                $active_module_ids[$shortcut_module_id] = true;
            }
            $this->adjuntar_item_a_jerarquia_dashboard(
                $nodes,
                $registered_modules,
                $shortcut['modulo'] ?? '',
                $shortcut['ecosystem'] ?? [],
                $dashboard_contexts,
                'shortcut',
                [
                    'id' => $shortcut_id,
                    'label' => $shortcut['label'] ?? $shortcut_id,
                    'url' => $shortcut['url'] ?? '',
                ]
            );
        }

        foreach ($nodes as &$node) {
            $satellite_ids = array_keys($node['satellites']);
            $node['transversals'] = $this->get_related_transversals_for_dashboard_node(
                $node['id'],
                $satellite_ids,
                $registered_modules,
                array_keys($active_module_ids),
                $dashboard_contexts
            );

            $node['satellites'] = array_values($node['satellites']);
            usort($node['satellites'], function ($a, $b) {
                if (($a['priority'] ?? 50) !== ($b['priority'] ?? 50)) {
                    return ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50);
                }

                return strcmp($a['name'], $b['name']);
            });

            $node['widgets'] = array_values($node['widgets']);
            usort($node['widgets'], function ($a, $b) {
                return strcmp($a['label'], $b['label']);
            });

            $node['shortcuts'] = array_values($node['shortcuts']);
            usort($node['shortcuts'], function ($a, $b) {
                return strcmp($a['label'], $b['label']);
            });

            $node['satellite_count'] = count($node['satellites']);
            $node['transversal_count'] = count($node['transversals']);
            $node['item_count'] = count($node['widgets']) + count($node['shortcuts']);
        }
        unset($node);

        uasort($nodes, function ($a, $b) {
            if (($a['context_match_score'] ?? 0) !== ($b['context_match_score'] ?? 0)) {
                return ($a['context_match_score'] ?? 0) > ($b['context_match_score'] ?? 0) ? -1 : 1;
            }

            if (($a['priority'] ?? 50) !== ($b['priority'] ?? 50)) {
                return ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50);
            }

            if (($a['satellite_count'] ?? 0) === ($b['satellite_count'] ?? 0)) {
                return strcmp($a['name'], $b['name']);
            }

            return ($a['satellite_count'] ?? 0) > ($b['satellite_count'] ?? 0) ? -1 : 1;
        });

        return array_values($nodes);
    }

    private function adjuntar_item_a_jerarquia_dashboard(&$nodes, $registered_modules, $module_id, $ecosystem, $dashboard_contexts, $item_type, $item_data) {
        $module_id = $this->normalize_module_id($module_id);
        if ($module_id === '') {
            return;
        }

        $parent_module = $this->normalize_module_id($ecosystem['parent_module'] ?? $module_id);
        if ($parent_module === '') {
            $parent_module = $module_id;
        }

        if (!isset($nodes[$parent_module])) {
            $nodes[$parent_module] = $this->build_hierarchy_node($parent_module, $registered_modules, $dashboard_contexts);
        }

        if ($module_id !== $parent_module) {
            $nodes[$parent_module]['satellites'][$module_id] = [
                'id' => $module_id,
                'name' => $this->get_registered_module_name_for_dashboard($module_id, $registered_modules),
                'role' => $this->get_dashboard_role_label($this->get_registered_module_role_for_dashboard($module_id, $registered_modules), $module_id, $registered_modules),
                'priority' => $this->get_registered_module_dashboard_priority($module_id, $registered_modules, 'satellite_priority'),
            ];
        }

        $nodes[$parent_module][$item_type . 's'][$item_data['id']] = $item_data;
    }

    private function build_hierarchy_node($module_id, $registered_modules, $dashboard_contexts = []) {
        $role = $this->get_registered_module_role_for_dashboard($module_id, $registered_modules);
        $client_contexts = $this->get_registered_module_dashboard_contexts($module_id, $registered_modules);

        return [
            'id' => $module_id,
            'name' => $this->get_registered_module_name_for_dashboard($module_id, $registered_modules),
            'role' => $role,
            'role_label' => $this->get_dashboard_role_label($role, $module_id, $registered_modules),
            'url' => home_url('/mi-portal/' . str_replace('_', '-', $module_id) . '/'),
            'priority' => $this->get_registered_module_dashboard_priority($module_id, $registered_modules, 'satellite_priority'),
            'client_contexts' => $client_contexts,
            'context_match_score' => $this->calculate_dashboard_context_match($client_contexts, $dashboard_contexts),
            'satellites' => [],
            'transversals' => [],
            'widgets' => [],
            'shortcuts' => [],
        ];
    }

    private function get_related_transversals_for_dashboard_node($parent_module_id, $satellite_ids, $registered_modules, $active_module_ids, $dashboard_contexts = []) {
        $targets = array_values(array_unique(array_merge([$parent_module_id], $satellite_ids)));
        $relations = ['supports_modules', 'measures_modules', 'governs_modules', 'teaches_modules'];
        $transversals = [];

        foreach ($registered_modules as $module_id => $module_data) {
            $ecosystem = is_array($module_data['ecosystem'] ?? null) ? $module_data['ecosystem'] : [];
            if (($ecosystem['module_role'] ?? 'vertical') !== 'transversal') {
                continue;
            }

            $matched = false;
            foreach ($relations as $relation_key) {
                $related = array_map([$this, 'normalize_module_id'], (array) ($ecosystem[$relation_key] ?? []));
                if (!empty(array_intersect($targets, $related))) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                continue;
            }

            $transversals[] = [
                'id' => $module_id,
                'name' => $this->get_registered_module_name_for_dashboard($module_id, $registered_modules),
                'is_active' => in_array($module_id, $active_module_ids, true),
                'priority' => $this->get_registered_module_dashboard_priority($module_id, $registered_modules, 'transversal_priority'),
                'client_contexts' => $this->get_registered_module_dashboard_contexts($module_id, $registered_modules),
                'context_match_score' => $this->calculate_dashboard_context_match(
                    $this->get_registered_module_dashboard_contexts($module_id, $registered_modules),
                    $dashboard_contexts
                ),
                'url' => in_array($module_id, $active_module_ids, true)
                    ? home_url('/mi-portal/' . str_replace('_', '-', $module_id) . '/')
                    : '',
            ];
        }

        usort($transversals, function ($a, $b) {
            if ($a['is_active'] !== $b['is_active']) {
                return $a['is_active'] ? -1 : 1;
            }

            if (($a['context_match_score'] ?? 0) !== ($b['context_match_score'] ?? 0)) {
                return ($a['context_match_score'] ?? 0) > ($b['context_match_score'] ?? 0) ? -1 : 1;
            }

            if (($a['priority'] ?? 50) !== ($b['priority'] ?? 50)) {
                return ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50);
            }

            return strcmp($a['name'], $b['name']);
        });

        return array_slice($transversals, 0, 5);
    }

    private function get_registered_module_name_for_dashboard($module_id, $registered_modules) {
        $module = $registered_modules[$module_id] ?? null;
        if (!$module) {
            return ucfirst(str_replace(['-', '_'], ' ', $module_id));
        }

        $name = trim((string) ($module['name'] ?? ''));
        return $name !== '' ? $name : ucfirst(str_replace(['-', '_'], ' ', $module_id));
    }

    private function get_registered_module_role_for_dashboard($module_id, $registered_modules) {
        return $registered_modules[$module_id]['ecosystem']['module_role'] ?? 'vertical';
    }

    private function get_registered_module_dashboard_priority($module_id, $registered_modules, $key) {
        return absint($registered_modules[$module_id]['dashboard'][$key] ?? 50);
    }

    private function get_registered_module_dashboard_contexts($module_id, $registered_modules) {
        return array_values(array_map([$this, 'sanitize_client_context'], (array) ($registered_modules[$module_id]['dashboard']['client_contexts'] ?? [])));
    }

    private function calculate_dashboard_context_match($module_contexts, $dashboard_contexts) {
        $module_contexts = array_values(array_unique(array_filter(array_map([$this, 'sanitize_client_context'], (array) $module_contexts))));
        $dashboard_contexts = array_values(array_unique(array_filter(array_map([$this, 'sanitize_client_context'], (array) $dashboard_contexts))));

        if (empty($module_contexts) || empty($dashboard_contexts)) {
            return 0;
        }

        return count(array_intersect($module_contexts, $dashboard_contexts));
    }

    private function sanitize_client_context($context) {
        return sanitize_key(str_replace('-', '_', (string) $context));
    }

    private function build_panel_layers($atajos, $notificaciones, $dashboard_contexts = [], $id_usuario = 0) {
        $signal_modules = ['avisos_municipales', 'anuncios', 'incidencias', 'notificaciones', 'energia_comunitaria'];
        $action_modules = ['eventos', 'reservas', 'participacion', 'grupos_consumo', 'banco_tiempo', 'ayuda_vecinal', 'tramites', 'socios'];
        $context_labels = [
            'energia_comunitaria' => __('Energia', 'flavor-platform'),
            'eventos' => __('Encuentros', 'flavor-platform'),
            'reservas' => __('Agenda', 'flavor-platform'),
            'participacion' => __('Decisiones', 'flavor-platform'),
            'grupos_consumo' => __('Consumo local', 'flavor-platform'),
            'banco_tiempo' => __('Cuidados', 'flavor-platform'),
            'ayuda_vecinal' => __('Cuidados', 'flavor-platform'),
            'incidencias' => __('Atencion', 'flavor-platform'),
            'avisos_municipales' => __('Avisos', 'flavor-platform'),
            'socios' => __('Membresia', 'flavor-platform'),
            'tramites' => __('Gestiones', 'flavor-platform'),
        ];

        $layers = [
            'signals' => [],
            'actions' => [],
            'services' => [],
        ];

        $real_signals = $this->get_panel_native_signals((int) $id_usuario);
        $real_signal_modules = [];

        foreach ($real_signals as $real_signal) {
            $layers['signals'][] = $real_signal;
            if (!empty($real_signal['module_id'])) {
                $real_signal_modules[] = $real_signal['module_id'];
            }
        }

        $real_signal_modules = array_values(array_unique(array_filter($real_signal_modules)));

        $real_actions = $this->get_panel_upcoming_actions((int) $id_usuario);
        $real_action_modules = [];

        foreach ($real_actions as $real_action) {
            $layers['actions'][] = $real_action;
            if (!empty($real_action['module_id'])) {
                $real_action_modules[] = $real_action['module_id'];
            }
        }

        $real_action_modules = array_values(array_unique(array_filter($real_action_modules)));

        foreach ((array) $notificaciones as $notification) {
            $severity = class_exists('Flavor_Dashboard_Severity')
                ? Flavor_Dashboard_Severity::get_payload(Flavor_Dashboard_Severity::from_notification_type($notification['type'] ?? 'info'))
                : ['slug' => 'followup', 'label' => __('Seguimiento', 'flavor-platform')];
            $layers['signals'][] = [
                'label' => $notification['title'] ?? __('Notificacion', 'flavor-platform'),
                'meta' => $notification['message'] ?? '',
                'url' => home_url('/mi-cuenta/?tab=notificaciones'),
                'kind' => __('Notificacion', 'flavor-platform'),
                'context' => __('Nodo', 'flavor-platform'),
                'severity' => $severity,
                'color' => 'secondary',
                'target' => '_self',
            ];
        }

        foreach ((array) $atajos as $shortcut_id => $shortcut) {
            $module_id = $this->normalize_module_id($shortcut['modulo'] ?? '');
            $item = [
                'id' => $shortcut_id,
                'label' => $shortcut['label'] ?? $shortcut_id,
                'url' => $shortcut['url'] ?? '',
                'kind' => __('Herramienta', 'flavor-platform'),
                'context' => $context_labels[$module_id] ?? '',
                'icon' => $shortcut['icon'] ?? 'link',
                'color' => $shortcut['color'] ?? 'secondary',
                'target' => $shortcut['target'] ?? '_self',
            ];

            if ($module_id !== '' && in_array($module_id, $signal_modules, true)) {
                if (in_array($module_id, $real_signal_modules, true)) {
                    continue;
                }

                $item['kind'] = __('Senal', 'flavor-platform');
                $item['severity'] = class_exists('Flavor_Dashboard_Severity')
                    ? Flavor_Dashboard_Severity::get_payload('attention')
                    : ['slug' => 'attention', 'label' => __('Atención', 'flavor-platform')];
                $layers['signals'][] = $item;
                continue;
            }

            if ($module_id !== '' && in_array($module_id, $action_modules, true)) {
                if (in_array($module_id, $real_action_modules, true)) {
                    continue;
                }

                $item['kind'] = __('Accion', 'flavor-platform');
                $item['severity'] = class_exists('Flavor_Dashboard_Severity')
                    ? Flavor_Dashboard_Severity::get_payload(in_array($module_id, ['eventos', 'reservas', 'participacion'], true) ? 'attention' : 'followup')
                    : ['slug' => 'followup', 'label' => __('Seguimiento', 'flavor-platform')];
                $layers['actions'][] = $item;
                continue;
            }

            $layers['services'][] = $item;
        }

        if (!empty($dashboard_contexts)) {
            usort($layers['actions'], function ($a, $b) use ($dashboard_contexts) {
                $a_score = in_array($this->sanitize_client_context($a['context'] ?? ''), $dashboard_contexts, true) ? 1 : 0;
                $b_score = in_array($this->sanitize_client_context($b['context'] ?? ''), $dashboard_contexts, true) ? 1 : 0;
                if ($a_score !== $b_score) {
                    return $a_score > $b_score ? -1 : 1;
                }

                $a_severity = $a['severity']['slug'] ?? 'stable';
                $b_severity = $b['severity']['slug'] ?? 'stable';
                $severity_order = ['attention' => 0, 'followup' => 1, 'stable' => 2];
                if (($severity_order[$a_severity] ?? 2) !== ($severity_order[$b_severity] ?? 2)) {
                    return ($severity_order[$a_severity] ?? 2) <=> ($severity_order[$b_severity] ?? 2);
                }

                $a_date = (int) ($a['date_ts'] ?? 0);
                $b_date = (int) ($b['date_ts'] ?? 0);
                if ($a_date && $b_date && $a_date !== $b_date) {
                    return $a_date <=> $b_date;
                }

                return strcmp($a['label'], $b['label']);
            });
        }

        usort($layers['signals'], function ($a, $b) {
            $a_severity = $a['severity']['slug'] ?? 'stable';
            $b_severity = $b['severity']['slug'] ?? 'stable';
            $severity_order = ['attention' => 0, 'followup' => 1, 'stable' => 2];
            if (($severity_order[$a_severity] ?? 2) !== ($severity_order[$b_severity] ?? 2)) {
                return ($severity_order[$a_severity] ?? 2) <=> ($severity_order[$b_severity] ?? 2);
            }

            return strcmp($a['label'], $b['label']);
        });

        $layers['signals'] = array_slice($layers['signals'], 0, 5);
        $layers['actions'] = array_slice($layers['actions'], 0, 6);

        return $layers;
    }

    private function get_panel_native_signals($id_usuario) {
        if ($id_usuario <= 0) {
            return [];
        }

        $signals = array_merge(
            $this->get_panel_participation_signals($id_usuario),
            $this->get_panel_incidencia_signals($id_usuario),
            $this->get_panel_socios_signals($id_usuario)
        );

        return array_slice($signals, 0, 3);
    }

    private function get_panel_upcoming_actions($id_usuario) {
        if ($id_usuario <= 0) {
            return [];
        }

        $acciones = array_merge(
            $this->get_panel_upcoming_event_actions($id_usuario),
            $this->get_panel_upcoming_reservation_actions($id_usuario),
            $this->get_panel_upcoming_participation_actions($id_usuario),
            $this->get_panel_upcoming_tramite_actions($id_usuario),
            $this->get_panel_upcoming_grupos_consumo_actions($id_usuario)
        );

        usort($acciones, function ($a, $b) {
            $a_date = (int) ($a['date_ts'] ?? 0);
            $b_date = (int) ($b['date_ts'] ?? 0);

            if ($a_date && $b_date && $a_date !== $b_date) {
                return $a_date <=> $b_date;
            }

            return strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        return array_slice($acciones, 0, 4);
    }

    private function get_panel_upcoming_event_actions($id_usuario) {
        global $wpdb;

        $acciones = [];
        $eventos = [];

        $eventos_module_class = flavor_get_runtime_class_name('Flavor_Chat_Eventos_Module');
        if (class_exists($eventos_module_class)) {
            $eventos_module = $eventos_module_class::get_instance();
            if ($eventos_module && method_exists($eventos_module, 'get_proximos_eventos_usuario')) {
                $eventos = (array) $eventos_module->get_proximos_eventos_usuario($id_usuario, 3);
            }
        }

        if (empty($eventos)) {
            $tabla_eventos = $wpdb->prefix . 'flavor_network_events';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") === $tabla_eventos) {
                $eventos = (array) $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, titulo, fecha_inicio, nodo_origen_nombre
                         FROM $tabla_eventos
                         WHERE estado = 'publicado' AND fecha_inicio >= %s
                         ORDER BY fecha_inicio ASC
                         LIMIT 3",
                        current_time('mysql')
                    ),
                    ARRAY_A
                );
            }
        }

        foreach ($eventos as $evento) {
            $fecha = (string) ($evento['fecha_inicio'] ?? $evento['fecha'] ?? '');
            $date_ts = $fecha ? strtotime($fecha) : 0;
            if (!$date_ts) {
                continue;
            }

            $severity_slug = class_exists('Flavor_Dashboard_Severity')
                ? Flavor_Dashboard_Severity::from_date($fecha, 'followup')
                : 'followup';

            $meta_parts = [date_i18n(get_option('date_format') . ' · ' . get_option('time_format'), $date_ts)];
            if (!empty($evento['nodo_origen_nombre'])) {
                $meta_parts[] = (string) $evento['nodo_origen_nombre'];
            }

            $acciones[] = [
                'id' => 'evento-' . sanitize_key((string) ($evento['id'] ?? wp_generate_uuid4())),
                'module_id' => 'eventos',
                'label' => $evento['titulo'] ?? $evento['nombre'] ?? __('Evento cercano', 'flavor-platform'),
                'meta' => implode(' · ', array_filter($meta_parts)),
                'url' => $evento['url'] ?? home_url('/mi-portal/eventos/'),
                'kind' => __('Evento cercano', 'flavor-platform'),
                'context' => __('Encuentros', 'flavor-platform'),
                'severity' => class_exists('Flavor_Dashboard_Severity')
                    ? Flavor_Dashboard_Severity::get_payload($severity_slug)
                    : ['slug' => $severity_slug, 'label' => __('Seguimiento', 'flavor-platform')],
                'date_ts' => $date_ts,
                'target' => '_self',
            ];
        }

        return $acciones;
    }

    private function get_panel_upcoming_reservation_actions($id_usuario) {
        global $wpdb;

        $acciones = [];
        $reservas = [];

        $reservas_module_class = flavor_get_runtime_class_name('Flavor_Chat_Reservas_Module');
        if (class_exists($reservas_module_class)) {
            $reservas_module = $reservas_module_class::get_instance();
            if ($reservas_module && method_exists($reservas_module, 'get_proximas_reservas_usuario')) {
                $reservas = (array) $reservas_module->get_proximas_reservas_usuario($id_usuario, 3);
            }
        }

        if (empty($reservas)) {
            $tabla_reservas = $wpdb->prefix . 'flavor_reservations';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_reservas'") === $tabla_reservas) {
                $reservas = (array) $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT *
                         FROM $tabla_reservas
                         WHERE user_id = %d AND fecha >= CURDATE() AND status IN ('confirmed', 'pending')
                         ORDER BY fecha ASC, hora ASC
                         LIMIT 3",
                        $id_usuario
                    ),
                    ARRAY_A
                );
            }
        }

        foreach ($reservas as $reserva) {
            $fecha = (string) ($reserva['fecha'] ?? '');
            $hora = (string) ($reserva['hora'] ?? '');
            $date_ts = $fecha ? strtotime(trim($fecha . ' ' . $hora)) : 0;
            if (!$date_ts) {
                $date_ts = $fecha ? strtotime($fecha) : 0;
            }
            if (!$date_ts) {
                continue;
            }

            $severity_slug = class_exists('Flavor_Dashboard_Severity')
                ? Flavor_Dashboard_Severity::from_date($fecha, 'followup')
                : 'followup';

            $meta_parts = [date_i18n(get_option('date_format'), strtotime($fecha))];
            if ($hora !== '') {
                $hora_ts = strtotime($hora);
                if ($hora_ts) {
                    $meta_parts[] = date_i18n(get_option('time_format'), $hora_ts);
                }
            }

            $espacio = $reserva['nombre_espacio'] ?? $reserva['servicio'] ?? $reserva['titulo'] ?? __('Reserva', 'flavor-platform');
            $estado = $reserva['status'] ?? '';
            if ($estado !== '') {
                $meta_parts[] = ucfirst((string) $estado);
            }

            $acciones[] = [
                'id' => 'reserva-' . sanitize_key((string) ($reserva['id'] ?? wp_generate_uuid4())),
                'module_id' => 'reservas',
                'label' => (string) $espacio,
                'meta' => implode(' · ', array_filter($meta_parts)),
                'url' => $reserva['url'] ?? home_url('/mi-portal/reservas/'),
                'kind' => __('Reserva cercana', 'flavor-platform'),
                'context' => __('Agenda', 'flavor-platform'),
                'severity' => class_exists('Flavor_Dashboard_Severity')
                    ? Flavor_Dashboard_Severity::get_payload($severity_slug)
                    : ['slug' => $severity_slug, 'label' => __('Seguimiento', 'flavor-platform')],
                'date_ts' => $date_ts,
                'target' => '_self',
            ];
        }

        return $acciones;
    }

    private function get_panel_upcoming_participation_actions($id_usuario) {
        global $wpdb;

        $acciones = [];
        $tabla_votaciones = $wpdb->prefix . 'votaciones';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_votaciones'") !== $tabla_votaciones) {
            return $acciones;
        }

        $votaciones = (array) $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, titulo, fecha_fin, estado
                 FROM $tabla_votaciones
                 WHERE estado = 'activa' AND fecha_fin >= %s
                 ORDER BY fecha_fin ASC
                 LIMIT 3",
                current_time('mysql')
            ),
            ARRAY_A
        );

        foreach ($votaciones as $votacion) {
            $fecha_fin = (string) ($votacion['fecha_fin'] ?? '');
            $date_ts = $fecha_fin ? strtotime($fecha_fin) : 0;
            if (!$date_ts) {
                continue;
            }

            $severity_slug = class_exists('Flavor_Dashboard_Severity')
                ? Flavor_Dashboard_Severity::from_date($fecha_fin, 'followup')
                : 'followup';

            $acciones[] = [
                'id' => 'participacion-' . sanitize_key((string) ($votacion['id'] ?? wp_generate_uuid4())),
                'module_id' => 'participacion',
                'label' => $votacion['titulo'] ?? __('Decision activa', 'flavor-platform'),
                'meta' => sprintf(
                    __('Cierra %s', 'flavor-platform'),
                    date_i18n(get_option('date_format') . ' · ' . get_option('time_format'), $date_ts)
                ),
                'url' => home_url('/mi-portal/participacion/votaciones/'),
                'kind' => __('Decision activa', 'flavor-platform'),
                'context' => __('Decisiones', 'flavor-platform'),
                'severity' => class_exists('Flavor_Dashboard_Severity')
                    ? Flavor_Dashboard_Severity::get_payload($severity_slug)
                    : ['slug' => $severity_slug, 'label' => __('Seguimiento', 'flavor-platform')],
                'date_ts' => $date_ts,
                'target' => '_self',
            ];
        }

        return $acciones;
    }

    private function get_panel_upcoming_tramite_actions($id_usuario) {
        global $wpdb;

        $acciones = [];
        $tabla_tramites = $wpdb->prefix . 'tramites';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_tramites'") !== $tabla_tramites) {
            return $acciones;
        }

        $tramites = (array) $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, titulo, tipo, estado, created_at
                 FROM $tabla_tramites
                 WHERE usuario_id = %d
                 AND estado IN ('pendiente', 'en_revision', 'en_proceso')
                 ORDER BY created_at DESC
                 LIMIT 3",
                $id_usuario
            ),
            ARRAY_A
        );

        foreach ($tramites as $tramite) {
            $fecha = (string) ($tramite['created_at'] ?? '');
            $date_ts = $fecha ? strtotime($fecha) : 0;
            $estado = sanitize_key((string) ($tramite['estado'] ?? 'pendiente'));
            $severity_slug = $estado === 'pendiente' ? 'attention' : 'followup';

            $meta_parts = [];
            if (!empty($tramite['tipo'])) {
                $meta_parts[] = ucwords(str_replace(['-', '_'], ' ', (string) $tramite['tipo']));
            }
            if ($date_ts) {
                $meta_parts[] = sprintf(
                    __('Iniciado %s', 'flavor-platform'),
                    date_i18n(get_option('date_format'), $date_ts)
                );
            }

            $acciones[] = [
                'id' => 'tramite-' . sanitize_key((string) ($tramite['id'] ?? wp_generate_uuid4())),
                'module_id' => 'tramites',
                'label' => $tramite['titulo'] ?? __('Tramite pendiente', 'flavor-platform'),
                'meta' => implode(' · ', array_filter($meta_parts)),
                'url' => home_url('/mi-portal/tramites/mis-tramites/'),
                'kind' => __('Tramite pendiente', 'flavor-platform'),
                'context' => __('Gestiones', 'flavor-platform'),
                'severity' => class_exists('Flavor_Dashboard_Severity')
                    ? Flavor_Dashboard_Severity::get_payload($severity_slug)
                    : ['slug' => $severity_slug, 'label' => __('Seguimiento', 'flavor-platform')],
                'date_ts' => $date_ts,
                'target' => '_self',
            ];
        }

        return $acciones;
    }

    private function get_panel_participation_signals($id_usuario) {
        global $wpdb;

        $signals = [];
        $tabla_votaciones = $wpdb->prefix . 'votaciones';
        $tabla_propuestas = $wpdb->prefix . 'propuestas';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_votaciones'") === $tabla_votaciones) {
            $votacion = $wpdb->get_row(
                "SELECT id, titulo, fecha_fin
                 FROM $tabla_votaciones
                 WHERE estado = 'activa' AND fecha_fin >= NOW()
                 ORDER BY fecha_fin ASC
                 LIMIT 1",
                ARRAY_A
            );

            if (!empty($votacion)) {
                $fecha_fin = (string) ($votacion['fecha_fin'] ?? '');
                $severity_slug = class_exists('Flavor_Dashboard_Severity')
                    ? Flavor_Dashboard_Severity::from_date($fecha_fin, 'followup')
                    : 'followup';

                $signals[] = [
                    'module_id' => 'participacion',
                    'label' => __('Hay decisiones activas en marcha', 'flavor-platform'),
                    'meta' => sprintf(
                        __('%s · cierra %s', 'flavor-platform'),
                        $votacion['titulo'] ?? __('Votacion activa', 'flavor-platform'),
                        date_i18n(get_option('date_format') . ' · ' . get_option('time_format'), strtotime($fecha_fin))
                    ),
                    'url' => home_url('/mi-portal/participacion/votaciones/'),
                    'kind' => __('Senal', 'flavor-platform'),
                    'context' => __('Decisiones', 'flavor-platform'),
                    'severity' => class_exists('Flavor_Dashboard_Severity')
                        ? Flavor_Dashboard_Severity::get_payload($severity_slug)
                        : ['slug' => $severity_slug, 'label' => __('Seguimiento', 'flavor-platform')],
                ];

                return $signals;
            }
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_propuestas'") === $tabla_propuestas) {
            $propuestas_abiertas = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_propuestas
                 WHERE estado IN ('abierta', 'publicada', 'en_debate', 'votacion')"
            );

            if ($propuestas_abiertas > 0) {
                $signals[] = [
                    'module_id' => 'participacion',
                    'label' => sprintf(
                        _n('%d propuesta abierta', '%d propuestas abiertas', $propuestas_abiertas, 'flavor-platform'),
                        $propuestas_abiertas
                    ),
                    'meta' => __('Hay actividad participativa que conviene revisar.', 'flavor-platform'),
                    'url' => home_url('/mi-portal/participacion/propuestas/'),
                    'kind' => __('Senal', 'flavor-platform'),
                    'context' => __('Participacion', 'flavor-platform'),
                    'severity' => class_exists('Flavor_Dashboard_Severity')
                        ? Flavor_Dashboard_Severity::get_payload('followup')
                        : ['slug' => 'followup', 'label' => __('Seguimiento', 'flavor-platform')],
                ];
            }
        }

        return $signals;
    }

    private function get_panel_incidencia_signals($id_usuario) {
        global $wpdb;

        $signals = [];
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        $tabla_seguimiento = $wpdb->prefix . 'flavor_seguimiento';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_incidencias'") !== $tabla_incidencias) {
            return $signals;
        }

        $mis_abiertas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_incidencias}
             WHERE usuario_id = %d
             AND estado NOT IN ('resuelta', 'cerrada', 'rechazada')",
            $id_usuario
        ));

        $actualizaciones_nuevas = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_seguimiento'") === $tabla_seguimiento) {
            $actualizaciones_nuevas = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_seguimiento} s
                 INNER JOIN {$tabla_incidencias} i ON s.incidencia_id = i.id
                 WHERE i.usuario_id = %d
                 AND s.fecha_creacion > DATE_SUB(NOW(), INTERVAL 7 DAY)
                 AND s.autor_id != %d",
                $id_usuario,
                $id_usuario
            ));
        }

        if ($mis_abiertas > 0 || $actualizaciones_nuevas > 0) {
            $meta = [];
            if ($mis_abiertas > 0) {
                $meta[] = sprintf(
                    _n('%d incidencia abierta', '%d incidencias abiertas', $mis_abiertas, 'flavor-platform'),
                    $mis_abiertas
                );
            }
            if ($actualizaciones_nuevas > 0) {
                $meta[] = sprintf(
                    _n('%d actualizacion reciente', '%d actualizaciones recientes', $actualizaciones_nuevas, 'flavor-platform'),
                    $actualizaciones_nuevas
                );
            }

            $signals[] = [
                'module_id' => 'incidencias',
                'label' => __('Hay incidencias que requieren atención', 'flavor-platform'),
                'meta' => implode(' · ', $meta),
                'url' => home_url('/mi-portal/incidencias/mis-incidencias/'),
                'kind' => __('Senal', 'flavor-platform'),
                'context' => __('Atencion', 'flavor-platform'),
                'severity' => class_exists('Flavor_Dashboard_Severity')
                    ? Flavor_Dashboard_Severity::get_payload('attention')
                    : ['slug' => 'attention', 'label' => __('Atención', 'flavor-platform')],
            ];

            return $signals;
        }

        $total_abiertas = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_incidencias}
             WHERE estado NOT IN ('resuelta', 'cerrada', 'rechazada')"
        );

        if ($total_abiertas > 0) {
            $signals[] = [
                'module_id' => 'incidencias',
                'label' => sprintf(
                    _n('%d incidencia en la comunidad', '%d incidencias en la comunidad', $total_abiertas, 'flavor-platform'),
                    $total_abiertas
                ),
                'meta' => __('Hay actividad comunitaria de seguimiento en incidencias.', 'flavor-platform'),
                'url' => home_url('/mi-portal/incidencias/'),
                'kind' => __('Senal', 'flavor-platform'),
                'context' => __('Nodo', 'flavor-platform'),
                'severity' => class_exists('Flavor_Dashboard_Severity')
                    ? Flavor_Dashboard_Severity::get_payload('followup')
                    : ['slug' => 'followup', 'label' => __('Seguimiento', 'flavor-platform')],
            ];
        }

        return $signals;
    }

    private function get_panel_socios_signals($id_usuario) {
        global $wpdb;

        $signals = [];
        $tabla_socios = $wpdb->prefix . 'flavor_socios_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_socios'") !== $tabla_socios) {
            return $signals;
        }

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT id, estado
             FROM {$tabla_socios}
             WHERE usuario_id = %d
             LIMIT 1",
            $id_usuario
        ), ARRAY_A);

        if (empty($socio)) {
            return $signals;
        }

        $estado = sanitize_key((string) ($socio['estado'] ?? ''));
        $cuotas_pendientes = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_cuotas'") === $tabla_cuotas) {
            $cuotas_pendientes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$tabla_cuotas}
                 WHERE socio_id = %d AND estado IN ('pendiente', 'vencida')",
                (int) $socio['id']
            ));
        }

        if ($estado === 'suspendido' || $cuotas_pendientes > 0) {
            $meta_parts = [];
            if ($estado === 'suspendido') {
                $meta_parts[] = __('Tu membresía está suspendida.', 'flavor-platform');
            }
            if ($cuotas_pendientes > 0) {
                $meta_parts[] = sprintf(
                    _n('%d cuota pendiente', '%d cuotas pendientes', $cuotas_pendientes, 'flavor-platform'),
                    $cuotas_pendientes
                );
            }

            $signals[] = [
                'module_id' => 'socios',
                'label' => __('Tu vínculo de miembro requiere atención', 'flavor-platform'),
                'meta' => implode(' · ', $meta_parts),
                'url' => home_url('/mi-portal/socios/cuotas/'),
                'kind' => __('Senal', 'flavor-platform'),
                'context' => __('Membresia', 'flavor-platform'),
                'severity' => class_exists('Flavor_Dashboard_Severity')
                    ? Flavor_Dashboard_Severity::get_payload('attention')
                    : ['slug' => 'attention', 'label' => __('Atención', 'flavor-platform')],
            ];

            return $signals;
        }

        if ($estado === 'pendiente') {
            $signals[] = [
                'module_id' => 'socios',
                'label' => __('Tu membresía sigue pendiente de revisión', 'flavor-platform'),
                'meta' => __('Conviene revisar tu estado y completar lo que falte.', 'flavor-platform'),
                'url' => home_url('/mi-portal/socios/mi-perfil/'),
                'kind' => __('Senal', 'flavor-platform'),
                'context' => __('Membresia', 'flavor-platform'),
                'severity' => class_exists('Flavor_Dashboard_Severity')
                    ? Flavor_Dashboard_Severity::get_payload('followup')
                    : ['slug' => 'followup', 'label' => __('Seguimiento', 'flavor-platform')],
            ];
        }

        return $signals;
    }

    private function get_panel_upcoming_grupos_consumo_actions($id_usuario) {
        global $wpdb;

        $acciones = [];
        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        if (
            $wpdb->get_var("SHOW TABLES LIKE '$tabla_consumidores'") !== $tabla_consumidores ||
            $wpdb->get_var("SHOW TABLES LIKE '$tabla_pedidos'") !== $tabla_pedidos
        ) {
            return $acciones;
        }

        $consumidor_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_consumidores} WHERE usuario_id = %d LIMIT 1",
            $id_usuario
        ));

        if ($consumidor_id <= 0) {
            return $acciones;
        }

        $ciclos = get_posts([
            'post_type' => 'gc_ciclo',
            'post_status' => 'gc_abierto',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
        ]);

        if (empty($ciclos)) {
            return $acciones;
        }

        $ciclo_id = (int) $ciclos[0];
        $fecha_cierre = (string) get_post_meta($ciclo_id, '_gc_fecha_cierre', true);
        $fecha_entrega = (string) get_post_meta($ciclo_id, '_gc_fecha_entrega', true);
        $titulo_ciclo = get_the_title($ciclo_id) ?: __('Ciclo activo', 'flavor-platform');

        $tiene_pedido = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_pedidos} WHERE usuario_id = %d AND ciclo_id = %d",
            $id_usuario,
            $ciclo_id
        )) > 0;

        $candidatas = [];
        if ($fecha_cierre) {
            $candidatas[] = [
                'date' => $fecha_cierre,
                'kind' => __('Cierre de ciclo', 'flavor-platform'),
                'meta_prefix' => __('Cierra', 'flavor-platform'),
            ];
        }
        if ($fecha_entrega && $tiene_pedido) {
            $candidatas[] = [
                'date' => $fecha_entrega,
                'kind' => __('Entrega cercana', 'flavor-platform'),
                'meta_prefix' => __('Entrega', 'flavor-platform'),
            ];
        }

        foreach ($candidatas as $candidata) {
            $date_ts = strtotime((string) $candidata['date']);
            if (!$date_ts) {
                continue;
            }

            $severity_slug = class_exists('Flavor_Dashboard_Severity')
                ? Flavor_Dashboard_Severity::from_date((string) $candidata['date'], 'followup')
                : 'followup';

            if ($severity_slug === 'stable') {
                continue;
            }

            $acciones[] = [
                'id' => 'grupos-consumo-' . $ciclo_id . '-' . sanitize_key((string) $candidata['kind']),
                'module_id' => 'grupos_consumo',
                'label' => $titulo_ciclo,
                'meta' => sprintf(
                    __('%s %s', 'flavor-platform'),
                    $candidata['meta_prefix'],
                    date_i18n(get_option('date_format'), $date_ts)
                ),
                'url' => home_url('/mi-portal/grupos-consumo/'),
                'kind' => $candidata['kind'],
                'context' => __('Consumo local', 'flavor-platform'),
                'severity' => class_exists('Flavor_Dashboard_Severity')
                    ? Flavor_Dashboard_Severity::get_payload($severity_slug)
                    : ['slug' => $severity_slug, 'label' => __('Seguimiento', 'flavor-platform')],
                'date_ts' => $date_ts,
                'target' => '_self',
            ];
        }

        return array_slice($acciones, 0, 1);
    }

    private function resolve_client_dashboard_contexts($atributos = []) {
        $contexts = ['mi_panel', 'portal'];
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
        $request_query = [];

        if ($request_uri) {
            parse_str((string) wp_parse_url($request_uri, PHP_URL_QUERY), $request_query);
        }

        $explicit_context = $this->sanitize_client_context($atributos['contexto'] ?? '');
        if ($explicit_context !== '') {
            $contexts[] = $explicit_context;
        }

        if (strpos($request_path, '/mi-cuenta') !== false) {
            $contexts[] = 'cuenta';
        }

        if (strpos($request_path, '/mi-portal') !== false) {
            $contexts[] = 'portal';
        }

        $tab_context = $this->sanitize_client_context($request_query['tab'] ?? '');
        if ($tab_context !== '') {
            $contexts[] = $tab_context;
        }

        foreach (['comunidad', 'energia', 'consumo', 'cuidados', 'participacion', 'gobernanza', 'transparencia'] as $candidate_context) {
            if (strpos($request_path, $candidate_context) !== false) {
                $contexts[] = $candidate_context;
            }
        }

        return array_values(array_unique(array_filter($contexts)));
    }

    private function get_dashboard_role_label($role, $module_id = '', $registered_modules = []) {
        $module_key = $this->normalize_module_id($module_id);
        if ($module_key !== '' && !empty($registered_modules[$module_key]['ecosystem']['display_role_label'])) {
            $display_role = sanitize_key((string) ($registered_modules[$module_key]['ecosystem']['display_role'] ?? $role));
            if ($display_role === 'base-standalone') {
                return __('Base local', 'flavor-platform');
            }
        }

        switch ($role) {
            case 'base':
                return __('Base', 'flavor-platform');
            case 'transversal':
                return __('Transversal', 'flavor-platform');
            case 'vertical':
            default:
                return __('Vertical', 'flavor-platform');
        }
    }

    private function normalize_module_id($module_id) {
        return str_replace('-', '_', sanitize_key((string) $module_id));
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
            'contexto'             => '',
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
        $dashboard_contexts  = $this->resolve_client_dashboard_contexts($atributos);
        $ecosystem_hierarchy = $this->obtener_jerarquia_ecosistema_dashboard($widgets, $atajos, $dashboard_contexts);
        $panel_layers        = $this->build_panel_layers($atajos, $notificaciones, $dashboard_contexts, $usuario_actual->ID);

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
            'ecosystem_hierarchy'   => $ecosystem_hierarchy,
            'panel_layers'          => $panel_layers,
            'dashboard_contexts'    => $dashboard_contexts,
            'atributos'             => $atributos,
            'dashboard_instance'    => $this,
            'portal_url'            => home_url('/mi-portal/'),
            'legacy_notice'         => [
                'eyebrow' => __('Vista heredada', 'flavor-platform'),
                'title'   => __('Mi Portal es ahora el dashboard principal', 'flavor-platform'),
                'text'    => __('Este panel se mantiene por compatibilidad con paginas que usan el shortcode [flavor_client_dashboard]. Usa Mi Portal para la experiencia principal del nodo.', 'flavor-platform'),
                'cta'     => __('Abrir Mi Portal', 'flavor-platform'),
            ],
        ];

        $ruta_template = FLAVOR_PLATFORM_PATH . 'templates/frontend/dashboard/client-dashboard.php';
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
                <h2><?php esc_html_e('Acceso Requerido', 'flavor-platform'); ?></h2>
                <p><?php esc_html_e('Necesitas iniciar sesion para acceder a tu panel personal.', 'flavor-platform'); ?></p>
                <div class="flavor-client-dashboard__login-actions">
                    <a href="<?php echo esc_url(wp_login_url($this->get_current_request_url())); ?>" class="flavor-btn flavor-btn--primary">
                        <?php esc_html_e('Iniciar Sesion', 'flavor-platform'); ?>
                    </a>
                    <?php if (get_option('users_can_register')) : ?>
                        <a href="<?php echo esc_url(wp_registration_url()); ?>" class="flavor-btn flavor-btn--outline">
                            <?php esc_html_e('Crear Cuenta', 'flavor-platform'); ?>
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
            return __('Buenos dias', 'flavor-platform');
        } elseif ($hora_actual >= 12 && $hora_actual < 20) {
            return __('Buenas tardes', 'flavor-platform');
        } else {
            return __('Buenas noches', 'flavor-platform');
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
            'texto' => sprintf(__('%d proximas', 'flavor-platform'), $proximas_reservas),
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
            'texto' => __('Este mes', 'flavor-platform'),
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
            'texto' => sprintf(__('Nivel %s', 'flavor-platform'), $nivel),
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
            'texto' => __('Sin leer', 'flavor-platform'),
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
            return __('Experto', 'flavor-platform');
        } elseif ($puntos >= 5000) {
            return __('Avanzado', 'flavor-platform');
        } elseif ($puntos >= 1000) {
            return __('Intermedio', 'flavor-platform');
        } elseif ($puntos >= 100) {
            return __('Basico', 'flavor-platform');
        }

        return __('Nuevo', 'flavor-platform');
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
            echo '<p>' . esc_html__('No tienes reservas proximas', 'flavor-platform') . '</p>';
            echo '<a href="' . esc_url(home_url('/reservas/')) . '" class="flavor-btn flavor-btn--sm flavor-btn--outline">' . esc_html__('Hacer una reserva', 'flavor-platform') . '</a>';
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
            echo '<span class="flavor-widget-list__title">' . esc_html($reserva['servicio'] ?? __('Reserva', 'flavor-platform')) . '</span>';
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
            echo '<p>' . esc_html__('No tienes mensajes nuevos', 'flavor-platform') . '</p>';
            echo '</div>';
            return;
        }

        echo '<ul class="flavor-widget-list">';
        foreach ($notificaciones as $notificacion) {
            $titulo = $notificacion['title'] ?? __('Notificacion', 'flavor-platform');
            $fecha = isset($notificacion['created_at']) ? human_time_diff(strtotime($notificacion['created_at'])) : '';

            echo '<li class="flavor-widget-list__item">';
            echo '<div class="flavor-widget-list__content">';
            echo '<span class="flavor-widget-list__title">' . esc_html($titulo) . '</span>';
            if ($fecha) {
                echo '<span class="flavor-widget-list__meta">' . sprintf(esc_html__('Hace %s', 'flavor-platform'), esc_html($fecha)) . '</span>';
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
            return __('Hace unos momentos', 'flavor-platform');
        } elseif ($diferencia < 3600) {
            $minutos = floor($diferencia / 60);
            return sprintf(__('Hace %d minutos', 'flavor-platform'), $minutos);
        } elseif ($diferencia < 86400) {
            $horas = floor($diferencia / 3600);
            return sprintf(__('Hace %d horas', 'flavor-platform'), $horas);
        } elseif ($diferencia < 604800) {
            $dias = floor($diferencia / 86400);
            return sprintf(__('Hace %d dias', 'flavor-platform'), $dias);
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
            wp_send_json_error(['message' => __('No autorizado', 'flavor-platform')]);
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
            wp_send_json_error(['message' => __('No autorizado', 'flavor-platform')]);
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

        wp_send_json_error(['message' => __('Widget no encontrado', 'flavor-platform')]);
    }

    /**
     * AJAX: Obtener actividad reciente
     */
    public function ajax_obtener_actividad() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-platform')]);
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
            wp_send_json_error(['message' => __('No autorizado', 'flavor-platform')]);
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
            wp_send_json_error(['message' => __('No autorizado', 'flavor-platform')]);
        }

        $id_notificacion = isset($_POST['notification_id']) ? absint($_POST['notification_id']) : 0;

        if (!$id_notificacion) {
            wp_send_json_error(['message' => __('ID de notificacion no valido', 'flavor-platform')]);
        }

        if (class_exists('Flavor_Notification_Manager')) {
            $gestor_notificaciones = Flavor_Notification_Manager::get_instance();
            $gestor_notificaciones->mark_as_read($id_notificacion);
        }

        wp_send_json_success([
            'message' => __('Notificacion descartada', 'flavor-platform'),
        ]);
    }

    /**
     * AJAX: Guardar preferencias del usuario
     */
    public function ajax_guardar_preferencias() {
        check_ajax_referer('flavor_client_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('No autorizado', 'flavor-platform')]);
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
                'message'      => __('Preferencias guardadas', 'flavor-platform'),
                'preferencias' => $this->obtener_preferencias_usuario($id_usuario),
            ]);
        }

        wp_send_json_error(['message' => __('Error al guardar preferencias', 'flavor-platform')]);
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
                        <?php esc_html_e('Comunidades', 'flavor-platform'); ?>
                    </span>
                </div>
                <div class="flavor-widget-network__stat">
                    <span class="flavor-widget-network__stat-value">
                        <?php echo esc_html(number_format_i18n($estadisticas_red['total_usuarios'] ?? 0)); ?>
                    </span>
                    <span class="flavor-widget-network__stat-label">
                        <?php esc_html_e('Usuarios', 'flavor-platform'); ?>
                    </span>
                </div>
                <div class="flavor-widget-network__stat">
                    <span class="flavor-widget-network__stat-value">
                        <?php echo esc_html(number_format_i18n($estadisticas_red['contenido_compartido'] ?? 0)); ?>
                    </span>
                    <span class="flavor-widget-network__stat-label">
                        <?php esc_html_e('Contenidos', 'flavor-platform'); ?>
                    </span>
                </div>
            </div>

            <!-- Nodos conectados -->
            <?php if (!empty($nodos_conectados)) : ?>
                <div class="flavor-widget-network__nodes">
                    <h4 class="flavor-widget-network__subtitle">
                        <?php esc_html_e('Comunidades Conectadas', 'flavor-platform'); ?>
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
                                        <?php echo esc_html($nodo['tipo'] ?? __('Comunidad', 'flavor-platform')); ?>
                                    </span>
                                </div>
                                <?php if (!empty($nodo['estado']) && $nodo['estado'] === 'activo') : ?>
                                    <span class="flavor-indicator flavor-indicator--online"
                                          title="<?php esc_attr_e('Activo', 'flavor-platform'); ?>"></span>
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
                        <?php esc_html_e('Ultimas Actualizaciones', 'flavor-platform'); ?>
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
                    <p><?php esc_html_e('Aun no estas conectado a ninguna red', 'flavor-platform'); ?></p>
                </div>
            <?php endif; ?>

            <!-- Link explorar red -->
            <a href="<?php echo esc_url(home_url('/red-comunidades/')); ?>"
               class="flavor-widget-network__explore-link">
                <?php echo $this->obtener_icono_svg('external', 14); ?>
                <?php esc_html_e('Explorar la Red', 'flavor-platform'); ?>
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
            'todos'     => __('Todos', 'flavor-platform'),
            'eventos'   => __('Eventos', 'flavor-platform'),
            'ofertas'   => __('Ofertas', 'flavor-platform'),
            'servicios' => __('Servicios', 'flavor-platform'),
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
                                          title="<?php esc_attr_e('Origen', 'flavor-platform'); ?>">
                                        <?php echo $this->obtener_icono_svg('globe', 12); ?>
                                        <?php echo esc_html($recurso['origen'] ?? __('Local', 'flavor-platform')); ?>
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
                                   aria-label="<?php esc_attr_e('Ver mas', 'flavor-platform'); ?>">
                                    <?php echo $this->obtener_icono_svg('external', 14); ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <a href="<?php echo esc_url(home_url('/recursos-compartidos/')); ?>"
                   class="flavor-widget-shared__view-more">
                    <?php esc_html_e('Ver mas recursos', 'flavor-platform'); ?>
                    <?php echo $this->obtener_icono_svg('external', 14); ?>
                </a>
            <?php else : ?>
                <div class="flavor-widget-empty">
                    <div class="flavor-widget-empty__icon">
                        <?php echo $this->obtener_icono_svg('share', 32); ?>
                    </div>
                    <p><?php esc_html_e('No hay recursos compartidos disponibles', 'flavor-platform'); ?></p>
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
                        'todos'      => ['label' => __('Todos', 'flavor-platform'), 'icon' => 'layers'],
                        'bicicletas' => ['label' => __('Bicicletas', 'flavor-platform'), 'icon' => 'bike'],
                        'parkings'   => ['label' => __('Parkings', 'flavor-platform'), 'icon' => 'parking'],
                        'huertos'    => ['label' => __('Huertos', 'flavor-platform'), 'icon' => 'leaf'],
                        'reciclaje'  => ['label' => __('Reciclaje', 'flavor-platform'), 'icon' => 'recycle'],
                        'espacios'   => ['label' => __('Espacios', 'flavor-platform'), 'icon' => 'home'],
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
                        title="<?php esc_attr_e('Mi ubicacion', 'flavor-platform'); ?>">
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
                    <span><?php esc_html_e('Cargando mapa...', 'flavor-platform'); ?></span>
                </div>
            </div>

            <!-- Leyenda del mapa -->
            <div class="flavor-widget-map__legend">
                <span class="flavor-widget-map__legend-item flavor-widget-map__legend-item--bicicletas">
                    <?php echo $this->obtener_icono_svg('bike', 12); ?>
                    <?php esc_html_e('Bicicletas', 'flavor-platform'); ?>
                </span>
                <span class="flavor-widget-map__legend-item flavor-widget-map__legend-item--parkings">
                    <?php echo $this->obtener_icono_svg('parking', 12); ?>
                    <?php esc_html_e('Parkings', 'flavor-platform'); ?>
                </span>
                <span class="flavor-widget-map__legend-item flavor-widget-map__legend-item--huertos">
                    <?php echo $this->obtener_icono_svg('leaf', 12); ?>
                    <?php esc_html_e('Huertos', 'flavor-platform'); ?>
                </span>
                <span class="flavor-widget-map__legend-item flavor-widget-map__legend-item--reciclaje">
                    <?php echo $this->obtener_icono_svg('recycle', 12); ?>
                    <?php esc_html_e('Reciclaje', 'flavor-platform'); ?>
                </span>
            </div>

            <!-- Link expandir mapa -->
            <a href="<?php echo esc_url(home_url('/mapa/')); ?>"
               class="flavor-widget-map__expand-link">
                <?php echo $this->obtener_icono_svg('external', 14); ?>
                <?php esc_html_e('Ver mapa completo', 'flavor-platform'); ?>
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
                        <?php esc_html_e('7 dias', 'flavor-platform'); ?>
                    </button>
                    <button type="button"
                            class="flavor-widget-stats-panel__period-btn"
                            data-period="30d">
                        <?php esc_html_e('30 dias', 'flavor-platform'); ?>
                    </button>
                    <button type="button"
                            class="flavor-widget-stats-panel__period-btn"
                            data-period="90d">
                        <?php esc_html_e('90 dias', 'flavor-platform'); ?>
                    </button>
                </div>
            </div>

            <!-- Grafico de actividad -->
            <div class="flavor-widget-stats-panel__chart">
                <h4 class="flavor-widget-stats-panel__chart-title">
                    <?php esc_html_e('Tu Actividad', 'flavor-platform'); ?>
                </h4>
                <div class="flavor-widget-stats-panel__chart-container"
                     id="flavor-activity-chart"
                     data-chart-data="<?php echo esc_attr(wp_json_encode($actividad_semanal)); ?>">
                    <!-- Grafico de barras simple en CSS -->
                    <div class="flavor-widget-stats-panel__bars">
                        <?php
                        $dias_semana = [
                            __('Lun', 'flavor-platform'),
                            __('Mar', 'flavor-platform'),
                            __('Mie', 'flavor-platform'),
                            __('Jue', 'flavor-platform'),
                            __('Vie', 'flavor-platform'),
                            __('Sab', 'flavor-platform'),
                            __('Dom', 'flavor-platform'),
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
                                     title="<?php echo esc_attr(sprintf(__('%d acciones', 'flavor-platform'), $valor_dia)); ?>">
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
                    <?php esc_html_e('vs. Semana Anterior', 'flavor-platform'); ?>
                </h4>
                <div class="flavor-widget-stats-panel__comparison-grid">
                    <?php
                    $metricas_comparativa = [
                        'participaciones' => ['label' => __('Participaciones', 'flavor-platform'), 'icon' => 'users'],
                        'reservas'        => ['label' => __('Reservas', 'flavor-platform'), 'icon' => 'calendar'],
                        'interacciones'   => ['label' => __('Interacciones', 'flavor-platform'), 'icon' => 'activity'],
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
                        <?php esc_html_e('Tus Tendencias', 'flavor-platform'); ?>
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
            FLAVOR_PLATFORM_URL . "assets/css/layouts/dashboard-map{$sufijo_asset}.css",
            ['leaflet', 'leaflet-markercluster'],
            FLAVOR_PLATFORM_VERSION
        );

        // JS del mapa del dashboard
        wp_enqueue_script(
            'flavor-dashboard-map',
            FLAVOR_PLATFORM_URL . "assets/js/dashboard-map{$sufijo_asset}.js",
            ['jquery', 'leaflet', 'leaflet-markercluster'],
            FLAVOR_PLATFORM_VERSION,
            true
        );

        wp_localize_script('flavor-dashboard-map', 'flavorDashboardMap', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_client_dashboard'),
            'i18n'    => [
                'cargando'        => __('Cargando...', 'flavor-platform'),
                'error_ubicacion' => __('No se pudo obtener tu ubicacion', 'flavor-platform'),
                'ver_detalle'     => __('Ver detalle', 'flavor-platform'),
                'disponible'      => __('Disponible', 'flavor-platform'),
                'ocupado'         => __('Ocupado', 'flavor-platform'),
                'abierto'         => __('Abierto', 'flavor-platform'),
                'cerrado'         => __('Cerrado', 'flavor-platform'),
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
                    'origen' => $evento['nodo_origen_nombre'] ?? __('Red', 'flavor-platform'),
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
                    'origen' => $contenido['nodo_origen_nombre'] ?? __('Red', 'flavor-platform'),
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
                        __('%d/%d disponibles', 'flavor-platform'),
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
                        __('%d plazas libres', 'flavor-platform'),
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
                        __('%d parcelas', 'flavor-platform'),
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
                        __('Capacidad: %d', 'flavor-platform'),
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
                'texto' => __('Semana muy activa! Sigue asi.', 'flavor-platform'),
                'icono' => 'trending-up',
                'tipo'  => 'positivo',
            ];
        } elseif ($total_actividad_semana > 5) {
            $datos['tendencias'][] = [
                'texto' => __('Buena actividad esta semana.', 'flavor-platform'),
                'icono' => 'activity',
                'tipo'  => 'neutral',
            ];
        } else {
            $datos['tendencias'][] = [
                'texto' => __('Podrias participar mas en la comunidad.', 'flavor-platform'),
                'icono' => 'activity',
                'tipo'  => 'sugerencia',
            ];
        }

        if ($datos['comparativa']['reservas']['actual'] > $datos['comparativa']['reservas']['anterior']) {
            $datos['tendencias'][] = [
                'texto' => __('Mas reservas que la semana pasada.', 'flavor-platform'),
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
            wp_send_json_error(['message' => __('No autorizado', 'flavor-platform')]);
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
            wp_send_json_error(['message' => __('No autorizado', 'flavor-platform')]);
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
            wp_send_json_error(['message' => __('No autorizado', 'flavor-platform')]);
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
            wp_send_json_error(['message' => __('No autorizado', 'flavor-platform')]);
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
                'label'       => __('Operaciones', 'flavor-platform'),
                'icon'        => 'dashicons-admin-tools',
                'color'       => '#f97316',
                'order'       => 10,
                'description' => __('Reservas, fichaje e incidencias', 'flavor-platform'),
            ],
            'recursos' => [
                'label'       => __('Recursos', 'flavor-platform'),
                'icon'        => 'dashicons-archive',
                'color'       => '#14b8a6',
                'order'       => 20,
                'description' => __('Espacios, equipamiento y biblioteca', 'flavor-platform'),
            ],
            'economia' => [
                'label'       => __('Economia', 'flavor-platform'),
                'icon'        => 'dashicons-chart-line',
                'color'       => '#10b981',
                'order'       => 30,
                'description' => __('Finanzas y transacciones', 'flavor-platform'),
            ],
            'comunicacion' => [
                'label'       => __('Comunicacion', 'flavor-platform'),
                'icon'        => 'dashicons-megaphone',
                'color'       => '#8b5cf6',
                'order'       => 40,
                'description' => __('Mensajeria y avisos', 'flavor-platform'),
            ],
            'actividades' => [
                'label'       => __('Actividades', 'flavor-platform'),
                'icon'        => 'dashicons-calendar-alt',
                'color'       => '#a855f7',
                'order'       => 50,
                'description' => __('Eventos y formacion', 'flavor-platform'),
            ],
            'sostenibilidad' => [
                'label'       => __('Sostenibilidad', 'flavor-platform'),
                'icon'        => 'dashicons-palmtree',
                'color'       => '#84cc16',
                'order'       => 60,
                'description' => __('Medio ambiente', 'flavor-platform'),
            ],
            'comunidad' => [
                'label'       => __('Comunidad', 'flavor-platform'),
                'icon'        => 'dashicons-groups',
                'color'       => '#f59e0b',
                'order'       => 70,
                'description' => __('Participacion y vida social', 'flavor-platform'),
            ],
            'servicios' => [
                'label'       => __('Servicios', 'flavor-platform'),
                'icon'        => 'dashicons-admin-site',
                'color'       => '#0ea5e9',
                'order'       => 80,
                'description' => __('Tramites y soporte', 'flavor-platform'),
            ],
            'red' => [
                'label'       => __('Red de Nodos', 'flavor-platform'),
                'icon'        => 'dashicons-networking',
                'color'       => '#06b6d4',
                'order'       => 90,
                'description' => __('Red federada', 'flavor-platform'),
            ],
            'gestion' => [
                'label'       => __('Gestion', 'flavor-platform'),
                'icon'        => 'dashicons-clipboard',
                'color'       => '#3b82f6',
                'order'       => 5,
                'description' => __('Panel general', 'flavor-platform'),
            ],
        ];
    }
}

// Inicializar
Flavor_Client_Dashboard::get_instance();
