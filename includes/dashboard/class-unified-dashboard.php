<?php
/**
 * Dashboard Unificado - Controlador Principal
 *
 * Centraliza la visualizacion de widgets de todos los modulos activos
 * en una unica vista personalizable por el usuario.
 *
 * @package FlavorChatIA
 * @subpackage Dashboard
 * @since 4.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Unified Dashboard
 *
 * @since 4.0.0
 */
class Flavor_Unified_Dashboard {

    /**
     * Instancia singleton
     *
     * @var Flavor_Unified_Dashboard|null
     */
    private static $instance = null;

    /**
     * Registro de widgets
     *
     * @var Flavor_Widget_Registry
     */
    private $registry;

    /**
     * Renderizador de widgets
     *
     * @var Flavor_Widget_Renderer
     */
    private $renderer;

    /**
     * Intervalo de actualizacion automatica en segundos
     *
     * @var int
     */
    const AUTO_REFRESH_INTERVAL = 120;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Unified_Dashboard
     */
    public static function get_instance(): Flavor_Unified_Dashboard {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializa los hooks de WordPress
     *
     * @return void
     */
    private function init_hooks(): void {
        // Cargar dependencias
        add_action('init', [$this, 'load_dependencies'], 5);

        // Shortcode para frontend
        add_shortcode('flavor_unified_dashboard', [$this, 'render_shortcode']);

        // Assets del dashboard (admin y frontend)
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // AJAX handlers
        add_action('wp_ajax_fud_get_dashboard_data', [$this, 'ajax_get_dashboard_data']);
        add_action('wp_ajax_fud_save_layout', [$this, 'ajax_save_layout']);
        add_action('wp_ajax_fud_refresh_all', [$this, 'ajax_refresh_all']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Carga las dependencias del dashboard
     *
     * @return void
     */
    public function load_dependencies(): void {
        // Cargar archivos necesarios
        $dashboard_path = FLAVOR_CHAT_IA_PATH . 'includes/dashboard/';
        $frontend_path = FLAVOR_CHAT_IA_PATH . 'includes/frontend/';

        // Verificar que los archivos existan
        $archivos_requeridos = [
            'interface-dashboard-widget.php',
            'class-widget-registry.php',
            'class-widget-renderer.php',
        ];

        foreach ($archivos_requeridos as $archivo) {
            $ruta_completa = $dashboard_path . $archivo;
            if (!file_exists($ruta_completa)) {
                error_log("Flavor Dashboard: Archivo no encontrado: {$ruta_completa}");
                return;
            }
            require_once $ruta_completa;
        }

        // Cargar sistema de breadcrumbs (v4.1.0)
        $breadcrumbs_path = $frontend_path . 'class-breadcrumbs.php';
        if (file_exists($breadcrumbs_path)) {
            require_once $breadcrumbs_path;
        }

        // Inicializar instancias solo si las clases existen
        if (class_exists('Flavor_Widget_Registry')) {
            $this->registry = Flavor_Widget_Registry::get_instance();
        }

        if (class_exists('Flavor_Widget_Renderer')) {
            $this->renderer = Flavor_Widget_Renderer::get_instance();
        }

        // Registrar widgets del sistema (solo si el registry esta listo)
        if ($this->registry) {
            add_action('flavor_register_dashboard_widgets', [$this, 'register_system_widgets'], 5);
        }
    }

    /**
     * Encola los assets del dashboard unificado
     *
     * @param string $hook Hook de la pagina actual
     * @return void
     */
    public function enqueue_assets(string $hook): void {
        // Solo cargar en la pagina del dashboard unificado
        // El hook puede ser: toplevel_page_X, admin_page_X, flavor-platform_page_X
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $is_dashboard_page = strpos($hook, 'flavor-unified-dashboard') !== false ||
                             $current_page === 'flavor-unified-dashboard';

        if (!$is_dashboard_page) {
            return;
        }

        $plugin_url = FLAVOR_CHAT_IA_URL;
        $version = defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '4.1.0';

        // =====================================================================
        // CSS - Sistema de Diseño Unificado (v4.1.0)
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
            'fud-dashboard-base',
            $plugin_url . 'assets/css/dashboard-base.css',
            ['fl-design-tokens-compat'],
            $version
        );

        // 4. Widgets y niveles
        wp_enqueue_style(
            'fl-dashboard-widgets',
            $plugin_url . 'assets/css/dashboard-widgets.css',
            ['fud-dashboard-base'],
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

        // CSS Componentes (legacy)
        wp_enqueue_style(
            'fud-dashboard-components',
            $plugin_url . 'assets/css/dashboard-components.css',
            ['fl-dashboard-responsive'],
            $version
        );

        // CSS Unificado (admin)
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'admin/css/unified-dashboard.css')) {
            wp_enqueue_style(
                'fud-unified-dashboard',
                $plugin_url . 'admin/css/unified-dashboard.css',
                ['fud-dashboard-components'],
                $version
            );
        }

        // =====================================================================
        // JavaScript - Sistema de Drag & Drop (v4.1.0)
        // =====================================================================

        // SortableJS desde CDN (fallback a jQuery UI si falla)
        wp_enqueue_script(
            'sortablejs',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
            [],
            '1.15.2',
            true
        );

        // Dashboard Sortable (nuevo sistema)
        wp_enqueue_script(
            'fl-dashboard-sortable',
            $plugin_url . 'assets/js/dashboard-sortable.js',
            ['sortablejs'],
            $version,
            true
        );

        // jQuery UI como fallback
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');

        // JS del dashboard (legacy)
        $js_path = $plugin_url . 'admin/js/unified-dashboard.js';
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'admin/js/unified-dashboard.js')) {
            wp_enqueue_script(
                'fud-unified-dashboard',
                $js_path,
                ['jquery', 'jquery-ui-sortable', 'fl-dashboard-sortable'],
                $version,
                true
            );
        }

        // =====================================================================
        // Localizacion de scripts
        // =====================================================================

        // Configuracion compartida para todos los scripts
        $dashboard_config = [
            'ajaxUrl'         => admin_url('admin-ajax.php'),
            'restUrl'         => rest_url('flavor/v1/dashboard/'),
            'nonce'           => wp_create_nonce('fud_nonce'),
            'refreshInterval' => self::AUTO_REFRESH_INTERVAL * 1000,
            'features'        => [
                'sortable'     => true,
                'groups'       => true,
                'levels'       => true,
                'accessibility' => true,
            ],
            'i18n'            => $this->get_i18n_strings(),
        ];

        wp_localize_script('fl-dashboard-sortable', 'flDashboard', $dashboard_config);
        wp_localize_script('fud-unified-dashboard', 'fudConfig', $dashboard_config);
    }

    /**
     * Registra widgets del sistema (no de modulos)
     *
     * @param Flavor_Widget_Registry $registry Registro de widgets
     * @return void
     */
    public function register_system_widgets(Flavor_Widget_Registry $registry): void {
        // Registrar widgets de modulos activos automaticamente
        $this->register_module_widgets($registry);
        // Widget: Resumen del Sistema
        $registry->register(new Flavor_Module_Widget([
            'id'       => 'system-overview',
            'title'    => __('Estado del Sistema', 'flavor-chat-ia'),
            'icon'     => 'dashicons-dashboard',
            'size'     => 'small',
            'category' => 'sistema',
            'priority' => 10,
            'data_callback' => [$this, 'get_system_overview_data'],
            'render_callback' => [$this, 'render_system_overview'],
        ]));

        // Widget: Red de Nodos
        if ($this->is_network_enabled()) {
            $registry->register(new Flavor_Module_Widget([
                'id'       => 'network-status',
                'title'    => __('Red de Comunidades', 'flavor-chat-ia'),
                'icon'     => 'dashicons-networking',
                'size'     => 'medium',
                'category' => 'red',
                'priority' => 10,
                'data_callback' => [$this, 'get_network_status_data'],
                'render_callback' => [$this, 'render_network_status'],
            ]));
        }

        // Widget: Acciones Rapidas
        $registry->register(new Flavor_Module_Widget([
            'id'       => 'quick-actions',
            'title'    => __('Acciones Rapidas', 'flavor-chat-ia'),
            'icon'     => 'dashicons-admin-tools',
            'size'     => 'small',
            'category' => 'sistema',
            'priority' => 20,
            'data_callback' => [$this, 'get_quick_actions_data'],
            'render_callback' => [$this, 'render_quick_actions'],
        ]));

        // Widget: Actividad Reciente
        $registry->register(new Flavor_Module_Widget([
            'id'       => 'recent-activity',
            'title'    => __('Actividad Reciente', 'flavor-chat-ia'),
            'icon'     => 'dashicons-backup',
            'size'     => 'medium',
            'category' => 'sistema',
            'priority' => 30,
            'data_callback' => [$this, 'get_recent_activity_data'],
            'render_callback' => [$this, 'render_recent_activity'],
        ]));
    }

    /**
     * Obtiene las cadenas de traduccion para JS
     *
     * @return array
     */
    private function get_i18n_strings(): array {
        return [
            'loading'           => __('Cargando...', 'flavor-chat-ia'),
            'error'             => __('Error al cargar datos', 'flavor-chat-ia'),
            'refreshing'        => __('Actualizando...', 'flavor-chat-ia'),
            'refreshed'         => __('Datos actualizados', 'flavor-chat-ia'),
            'savingLayout'      => __('Guardando disposicion...', 'flavor-chat-ia'),
            'layoutSaved'       => __('Disposicion guardada', 'flavor-chat-ia'),
            'confirmHide'       => __('¿Ocultar este widget?', 'flavor-chat-ia'),
            'noWidgets'         => __('No hay widgets disponibles', 'flavor-chat-ia'),
            'customize'         => __('Personalizar', 'flavor-chat-ia'),
            'refresh'           => __('Actualizar', 'flavor-chat-ia'),
            'collapse'          => __('Colapsar', 'flavor-chat-ia'),
            'expand'            => __('Expandir', 'flavor-chat-ia'),
            'lastUpdate'        => __('Ultima actualizacion:', 'flavor-chat-ia'),
            'justNow'           => __('Ahora mismo', 'flavor-chat-ia'),
            'minutesAgo'        => __('Hace %d minutos', 'flavor-chat-ia'),
        ];
    }

    /**
     * Obtiene las preferencias del usuario
     *
     * @return array
     */
    private function get_user_preferences(): array {
        $usuario_id = get_current_user_id();

        return [
            'widgetOrder'      => get_user_meta($usuario_id, 'fud_widget_order', true) ?: [],
            'widgetVisibility' => get_user_meta($usuario_id, 'fud_widget_visibility', true) ?: [],
            'collapsedWidgets' => get_user_meta($usuario_id, 'fud_collapsed_widgets', true) ?: [],
            'viewMode'         => get_user_meta($usuario_id, 'fud_view_mode', true) ?: 'grid',
            'darkMode'         => get_user_meta($usuario_id, 'fud_dark_mode', true) ?: false,
        ];
    }

    /**
     * Renderiza la pagina del dashboard
     *
     * @return void
     */
    public function render(): void {
        // Verificar permisos
        if (!current_user_can('read')) {
            wp_die(__('No tienes permisos para acceder a esta pagina.', 'flavor-chat-ia'));
        }

        // Asegurar que las dependencias esten cargadas
        if (!$this->registry || !$this->renderer) {
            $this->load_dependencies();
        }

        // Verificar que las dependencias se hayan cargado correctamente
        if (!$this->registry || !$this->renderer) {
            echo '<div class="wrap"><div class="notice notice-error"><p>';
            esc_html_e('Error: No se pudieron cargar las dependencias del dashboard.', 'flavor-chat-ia');
            echo '</p></div></div>';
            return;
        }

        // Asegurar que el registro este inicializado
        $this->registry->initialize_widgets();

        // Obtener datos para la vista
        $widgets = $this->registry->get_sorted();
        $categories = $this->registry->get_categories_with_count();
        $user_order = $this->registry->get_user_widget_order();
        $visible_ids = $this->registry->get_visible_widget_ids();

        // Ordenar widgets segun preferencia del usuario
        $widgets_ordenados = $this->order_widgets_by_user_preference($widgets, $user_order);

        // Filtrar solo visibles
        $widgets_visibles = array_filter($widgets_ordenados, function ($widget) use ($visible_ids) {
            return in_array($widget->get_widget_id(), $visible_ids, true);
        });

        // Variables para la vista
        $widgets       = $widgets_visibles;
        $all_widgets   = $widgets_ordenados;
        $renderer      = $this->renderer;
        $total_widgets = count($widgets_ordenados);
        $visible_count = count($widgets_visibles);
        $last_refresh  = current_time('c');
        $user_prefs    = $this->get_user_preferences();

        // Cargar la vista (las variables locales estan disponibles en el scope del include)
        include FLAVOR_CHAT_IA_PATH . 'admin/views/unified-dashboard.php';
    }

    /**
     * Ordena widgets segun preferencia del usuario
     *
     * @param array $widgets Widgets a ordenar
     * @param array $user_order Orden del usuario
     * @return array
     */
    private function order_widgets_by_user_preference(array $widgets, array $user_order): array {
        if (empty($user_order)) {
            return $widgets;
        }

        $widgets_indexados = [];
        foreach ($widgets as $widget) {
            $widgets_indexados[$widget->get_widget_id()] = $widget;
        }

        $widgets_ordenados = [];

        // Primero los que estan en el orden del usuario
        foreach ($user_order as $widget_id) {
            if (isset($widgets_indexados[$widget_id])) {
                $widgets_ordenados[] = $widgets_indexados[$widget_id];
                unset($widgets_indexados[$widget_id]);
            }
        }

        // Luego los que no estaban en el orden (nuevos)
        foreach ($widgets_indexados as $widget) {
            $widgets_ordenados[] = $widget;
        }

        return $widgets_ordenados;
    }

    /**
     * Registra widgets automaticamente de los modulos activos
     *
     * Detecta modulos que tienen get_estadisticas_dashboard() y crea widgets
     *
     * @param Flavor_Widget_Registry $registry Registro de widgets
     * @return void
     */
    private function register_module_widgets(Flavor_Widget_Registry $registry): void {
        // Verificar que el Module Loader existe
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return;
        }

        $module_loader = Flavor_Chat_Module_Loader::get_instance();
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];

        // Obtener todos los modulos cargados
        $modulos_cargados = [];
        if (method_exists($module_loader, 'get_loaded_modules')) {
            $modulos_cargados = $module_loader->get_loaded_modules();
        }

        // Mapeo de IDs de modulo a categorias
        $categorias_modulos = [
            // Gestion
            'reservas'                  => 'gestion',
            'espacios_comunes'          => 'gestion',
            'fichaje_empleados'         => 'gestion',
            'incidencias'               => 'gestion',
            'tramites'                  => 'gestion',
            'clientes'                  => 'gestion',
            'parkings'                  => 'gestion',

            // Comunidad
            'eventos'                   => 'comunidad',
            'cursos'                    => 'comunidad',
            'talleres'                  => 'comunidad',
            'red_social'                => 'comunidad',
            'socios'                    => 'comunidad',
            'colectivos'                => 'comunidad',
            'huertos_urbanos'           => 'comunidad',
            'reciclaje'                 => 'comunidad',
            'compostaje'                => 'comunidad',
            'ayuda_vecinal'             => 'comunidad',
            'comunidades'               => 'comunidad',
            'participacion'             => 'comunidad',
            'presupuestos_participativos' => 'comunidad',

            // Economia
            'grupos_consumo'            => 'economia',
            'banco_tiempo'              => 'economia',
            'marketplace'               => 'economia',
            'facturas'                  => 'economia',
            'presupuestos'              => 'economia',
            'transparencia'             => 'economia',
            'woocommerce'               => 'economia',
            'trading_ia'                => 'economia',
            'dex_solana'                => 'economia',
            'empresarial'               => 'economia',
            'advertising'               => 'economia',

            // Comunicacion
            'podcast'                   => 'comunicacion',
            'radio'                     => 'comunicacion',
            'foros'                     => 'comunicacion',
            'chat_grupos'               => 'comunicacion',
            'chat_interno'              => 'comunicacion',
            'avisos_municipales'        => 'comunicacion',
            'email_marketing'           => 'comunicacion',
            'multimedia'                => 'comunicacion',

            // Movilidad
            'carpooling'                => 'comunidad',
            'bicicletas_compartidas'    => 'comunidad',
            'biblioteca'                => 'comunidad',
            'bares'                     => 'comunidad',
        ];

        // Mapeo de iconos por modulo
        $iconos_modulos = [
            // Eventos y formacion
            'eventos'                   => 'dashicons-calendar',
            'cursos'                    => 'dashicons-welcome-learn-more',
            'talleres'                  => 'dashicons-hammer',

            // Reservas y espacios
            'reservas'                  => 'dashicons-calendar-alt',
            'espacios_comunes'          => 'dashicons-admin-home',
            'parkings'                  => 'dashicons-location-alt',

            // Empleados y gestion
            'fichaje_empleados'         => 'dashicons-clock',
            'incidencias'               => 'dashicons-warning',
            'tramites'                  => 'dashicons-clipboard',
            'clientes'                  => 'dashicons-groups',

            // Economia y comercio
            'grupos_consumo'            => 'dashicons-cart',
            'banco_tiempo'              => 'dashicons-backup',
            'marketplace'               => 'dashicons-store',
            'facturas'                  => 'dashicons-media-text',
            'presupuestos'              => 'dashicons-portfolio',
            'transparencia'             => 'dashicons-visibility',
            'woocommerce'               => 'dashicons-cart',
            'trading_ia'                => 'dashicons-chart-line',
            'dex_solana'                => 'dashicons-superhero-alt',
            'empresarial'               => 'dashicons-building',
            'advertising'               => 'dashicons-megaphone',

            // Comunicacion
            'podcast'                   => 'dashicons-microphone',
            'radio'                     => 'dashicons-format-audio',
            'foros'                     => 'dashicons-format-chat',
            'chat_grupos'               => 'dashicons-groups',
            'chat_interno'              => 'dashicons-email-alt',
            'avisos_municipales'        => 'dashicons-bell',
            'email_marketing'           => 'dashicons-email',
            'multimedia'                => 'dashicons-images-alt2',

            // Comunidad
            'red_social'                => 'dashicons-share',
            'socios'                    => 'dashicons-id-alt',
            'colectivos'                => 'dashicons-networking',
            'comunidades'               => 'dashicons-admin-multisite',
            'participacion'             => 'dashicons-megaphone',
            'presupuestos_participativos' => 'dashicons-chart-pie',
            'ayuda_vecinal'             => 'dashicons-sos',

            // Sostenibilidad
            'huertos_urbanos'           => 'dashicons-carrot',
            'reciclaje'                 => 'dashicons-update-alt',
            'compostaje'                => 'dashicons-admin-site-alt',

            // Movilidad
            'carpooling'                => 'dashicons-car',
            'bicicletas_compartidas'    => 'dashicons-admin-site',

            // Otros
            'biblioteca'                => 'dashicons-book',
            'bares'                     => 'dashicons-food',
        ];

        foreach ($modulos_cargados as $modulo_id => $modulo) {
            // Solo modulos activos
            if (!in_array($modulo_id, $modulos_activos, true)) {
                continue;
            }

            // Solo modulos con get_estadisticas_dashboard
            if (!method_exists($modulo, 'get_estadisticas_dashboard')) {
                continue;
            }

            // Solo modulos que puedan activarse
            if (method_exists($modulo, 'can_activate') && !$modulo->can_activate()) {
                continue;
            }

            // Crear widget para este modulo
            $widget_id = 'module-' . $modulo_id;
            $widget_title = method_exists($modulo, 'get_name') ? $modulo->get_name() : ucfirst(str_replace('_', ' ', $modulo_id));
            $widget_icon = $iconos_modulos[$modulo_id] ?? 'dashicons-admin-generic';
            $widget_category = $categorias_modulos[$modulo_id] ?? 'gestion';

            $registry->register(new Flavor_Module_Widget([
                'id'              => $widget_id,
                'title'           => $widget_title,
                'icon'            => $widget_icon,
                'size'            => 'small',
                'category'        => $widget_category,
                'priority'        => 50,
                'refreshable'     => true,
                'cache_time'      => 0, // Desactivado temporalmente para debug
                'module'          => $modulo,
                'data_callback'   => function() use ($modulo, $modulo_id) {
                    return $this->get_module_widget_data($modulo, $modulo_id);
                },
                'render_callback' => function($data) use ($modulo_id) {
                    $this->render_module_widget($data, $modulo_id);
                },
            ]));
        }
    }

    /**
     * Obtiene datos de widget de un modulo
     *
     * @param object $modulo Instancia del modulo
     * @param string $modulo_id ID del modulo
     * @return array
     */
    private function get_module_widget_data($modulo, string $modulo_id): array {
        $estadisticas = [];

        if (method_exists($modulo, 'get_estadisticas_dashboard')) {
            $estadisticas = $modulo->get_estadisticas_dashboard();
        }

        // Transformar al formato de stats del widget
        $stats = [];
        if (is_array($estadisticas)) {
            foreach ($estadisticas as $stat) {
                $stats[] = [
                    'icon'  => $stat['icon'] ?? 'dashicons-chart-bar',
                    'valor' => $stat['valor'] ?? 0,
                    'label' => $stat['label'] ?? '',
                    'color' => $stat['color'] ?? 'primary',
                    'url'   => $stat['url'] ?? '',
                ];
            }
        }

        // Obtener la URL correcta del modulo
        $module_url = $this->get_module_admin_url($modulo, $modulo_id);

        return [
            'stats'       => $stats,
            'items'       => [],
            'empty_state' => sprintf(__('No hay datos de %s', 'flavor-chat-ia'), $modulo->get_name()),
            'footer'      => [
                [
                    'label' => __('Ver mas', 'flavor-chat-ia'),
                    'url'   => $module_url,
                    'icon'  => 'dashicons-arrow-right-alt2',
                ],
            ],
        ];
    }

    /**
     * Obtiene la URL de un modulo (admin o frontend segun contexto)
     *
     * @param object $modulo Instancia del modulo
     * @param string $modulo_id ID del modulo
     * @return string URL del modulo
     */
    private function get_module_admin_url($modulo, string $modulo_id): string {
        // Convertir ID a slug de URL (guiones en lugar de guiones bajos)
        $url_slug = str_replace('_', '-', $modulo_id);

        // Si estamos en admin, intentar usar el compositor con el modulo
        if (is_admin()) {
            // Verificar si el modulo tiene dashboard propio registrado
            global $submenu;
            $tiene_dashboard_propio = false;

            if (!empty($submenu['flavor-chat-ia'])) {
                foreach ($submenu['flavor-chat-ia'] as $item) {
                    if (isset($item[2]) && strpos($item[2], $modulo_id) !== false) {
                        $tiene_dashboard_propio = true;
                        return admin_url('admin.php?page=' . $item[2]);
                    }
                }
            }

            // Fallback: ir al compositor con el modulo seleccionado
            return admin_url('admin.php?page=flavor-app-composer&module=' . $modulo_id);
        }

        // En frontend: usar el sistema de paginas dinamicas /mi-portal/{modulo}/
        return home_url('/mi-portal/' . $url_slug . '/');
    }

    /**
     * Renderiza widget de un modulo
     *
     * @param array $data Datos del widget
     * @param string $modulo_id ID del modulo
     * @return void
     */
    private function render_module_widget(array $data, string $modulo_id): void {
        $stats = $data['stats'] ?? [];

        if (empty($stats)) {
            echo $this->renderer->render_empty_state($data['empty_state'] ?? '');
            return;
        }
        ?>
        <div class="fud-widget-stats fud-widget-stats--compact">
            <?php foreach ($stats as $stat): ?>
                <?php
                $icon  = esc_attr($stat['icon'] ?? 'dashicons-chart-bar');
                $valor = esc_html($stat['valor'] ?? '0');
                $label = esc_html($stat['label'] ?? '');
                $color = esc_attr($stat['color'] ?? 'primary');
                $url   = !empty($stat['url']) ? esc_url($stat['url']) : '';
                ?>
                <div class="fud-stat-item fud-stat--<?php echo $color; ?>">
                    <span class="fud-stat-icon dashicons <?php echo $icon; ?>"></span>
                    <span class="fud-stat-value"><?php echo $valor; ?></span>
                    <span class="fud-stat-label"><?php echo $label; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php

        // Footer con enlace
        if (!empty($data['footer'])): ?>
        <div class="fud-widget-footer">
            <?php foreach ($data['footer'] as $link): ?>
                <a href="<?php echo esc_url($link['url'] ?? '#'); ?>" class="fud-footer-link">
                    <?php echo esc_html($link['label'] ?? __('Ver mas', 'flavor-chat-ia')); ?>
                    <span class="dashicons <?php echo esc_attr($link['icon'] ?? 'dashicons-arrow-right-alt2'); ?>"></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif;
    }

    // =========================================================================
    // DATOS DE WIDGETS DEL SISTEMA
    // =========================================================================

    /**
     * Obtiene datos del widget de resumen del sistema
     *
     * @return array
     */
    public function get_system_overview_data(): array {
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];
        $total_modulos = 0;

        if (class_exists('Flavor_Chat_Module_Loader')) {
            $total_modulos = count(Flavor_Chat_Module_Loader::get_instance()->get_registered_modules());
        }

        $tiene_api_key = !empty($configuracion['claude_api_key']) ||
                         !empty($configuracion['openai_api_key']) ||
                         !empty($configuracion['deepseek_api_key']);

        return [
            'stats' => [
                [
                    'icon'  => 'dashicons-screenoptions',
                    'valor' => count($modulos_activos) . '/' . $total_modulos,
                    'label' => __('Modulos', 'flavor-chat-ia'),
                    'color' => 'primary',
                    'url'   => admin_url('admin.php?page=flavor-app-composer'),
                ],
                [
                    'icon'  => 'dashicons-cloud',
                    'valor' => $tiene_api_key ? __('OK', 'flavor-chat-ia') : __('--', 'flavor-chat-ia'),
                    'label' => __('API IA', 'flavor-chat-ia'),
                    'color' => $tiene_api_key ? 'success' : 'warning',
                    'url'   => admin_url('admin.php?page=flavor-chat-config'),
                ],
            ],
            'footer' => [
                [
                    'label' => __('Health Check', 'flavor-chat-ia'),
                    'url'   => admin_url('admin.php?page=flavor-health-check'),
                    'icon'  => 'dashicons-heart',
                ],
            ],
        ];
    }

    /**
     * Renderiza el widget de resumen del sistema
     *
     * @param array $data Datos del widget
     * @return void
     */
    public function render_system_overview(array $data): void {
        echo $this->renderer->render_stat_card($data['stats'][0] ?? []);
        echo $this->renderer->render_stat_card($data['stats'][1] ?? []);
    }

    /**
     * Verifica si la red esta habilitada
     *
     * @return bool
     */
    private function is_network_enabled(): bool {
        global $wpdb;
        $tabla_nodos = $wpdb->prefix . 'flavor_network_nodes';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $resultado = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_nodos));
        return $resultado === $tabla_nodos;
    }

    /**
     * Obtiene datos del widget de estado de red
     *
     * @return array
     */
    public function get_network_status_data(): array {
        if (class_exists('Flavor_Dashboard') && method_exists(Flavor_Dashboard::class, 'get_instance')) {
            $dashboard = Flavor_Dashboard::get_instance();
            return [
                'network' => $dashboard->obtener_estadisticas_red(),
                'shared'  => $dashboard->obtener_modulos_compartidos(),
            ];
        }

        return [
            'network' => [],
            'shared'  => [],
        ];
    }

    /**
     * Renderiza el widget de estado de red
     *
     * @param array $data Datos del widget
     * @return void
     */
    public function render_network_status(array $data): void {
        $network = $data['network'] ?? [];
        $shared = $data['shared'] ?? [];

        if (empty($network) || !isset($network['nodo_local'])) {
            echo $this->renderer->render_empty_state(
                __('Red no configurada', 'flavor-chat-ia'),
                'dashicons-networking',
                [
                    'label' => __('Configurar Red', 'flavor-chat-ia'),
                    'url'   => admin_url('admin.php?page=flavor-network-settings'),
                ]
            );
            return;
        }
        ?>
        <div class="fud-network-overview">
            <div class="fud-network-stats-grid">
                <div class="fud-network-stat">
                    <span class="fud-network-stat__value"><?php echo esc_html($network['nodos_activos'] ?? 0); ?></span>
                    <span class="fud-network-stat__label"><?php esc_html_e('Nodos activos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="fud-network-stat">
                    <span class="fud-network-stat__value"><?php echo esc_html($network['conexiones_federadas'] ?? 0); ?></span>
                    <span class="fud-network-stat__label"><?php esc_html_e('Federados', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="fud-network-stat">
                    <span class="fud-network-stat__value"><?php echo esc_html($network['contenido_compartido'] ?? 0); ?></span>
                    <span class="fud-network-stat__label"><?php esc_html_e('Compartidos', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <?php if (!empty($network['alertas_nodos'])): ?>
            <div class="fud-network-alerts">
                <span class="dashicons dashicons-warning"></span>
                <?php printf(
                    esc_html__('%d nodos sin conexion reciente', 'flavor-chat-ia'),
                    count($network['alertas_nodos'])
                ); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Obtiene datos del widget de acciones rapidas
     *
     * @return array
     */
    public function get_quick_actions_data(): array {
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];

        $acciones = [
            [
                'id'     => 'configuracion',
                'label'  => __('Configuracion', 'flavor-chat-ia'),
                'icon'   => 'dashicons-admin-settings',
                'url'    => admin_url('admin.php?page=flavor-chat-config'),
                'color'  => '#2271b1',
            ],
            [
                'id'     => 'compositor',
                'label'  => __('Compositor', 'flavor-chat-ia'),
                'icon'   => 'dashicons-smartphone',
                'url'    => admin_url('admin.php?page=flavor-app-composer'),
                'color'  => '#8e44ad',
            ],
        ];

        // Acciones contextuales segun modulos activos
        if (in_array('eventos', $modulos_activos, true)) {
            $acciones[] = [
                'id'    => 'nuevo-evento',
                'label' => __('Nuevo Evento', 'flavor-chat-ia'),
                'icon'  => 'dashicons-calendar-alt',
                'url'   => admin_url('admin.php?page=flavor-eventos&action=nuevo'),
                'color' => '#e74c3c',
            ];
        }

        return ['actions' => $acciones];
    }

    /**
     * Renderiza el widget de acciones rapidas
     *
     * @param array $data Datos del widget
     * @return void
     */
    public function render_quick_actions(array $data): void {
        $acciones = $data['actions'] ?? [];

        if (empty($acciones)) {
            return;
        }
        ?>
        <div class="fud-quick-actions">
            <?php foreach ($acciones as $accion): ?>
                <a href="<?php echo esc_url($accion['url']); ?>"
                   class="fud-quick-action"
                   style="--action-color: <?php echo esc_attr($accion['color'] ?? '#2271b1'); ?>">
                    <span class="dashicons <?php echo esc_attr($accion['icon']); ?>"></span>
                    <span class="fud-quick-action__label"><?php echo esc_html($accion['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Obtiene datos del widget de actividad reciente
     *
     * @return array
     */
    public function get_recent_activity_data(): array {
        $items = [];

        if (class_exists('Flavor_Activity_Log')) {
            $activity_log = Flavor_Activity_Log::get_instance();
            $registros = $activity_log->obtener_actividad_reciente(10);

            foreach ($registros as $registro) {
                $items[] = [
                    'icon'  => $this->get_activity_icon($registro->tipo, $registro->modulo_id ?? ''),
                    'title' => $registro->titulo,
                    'meta'  => human_time_diff(strtotime($registro->fecha), current_time('timestamp')),
                ];
            }
        }

        return [
            'items'       => $items,
            'empty_state' => __('Sin actividad reciente', 'flavor-chat-ia'),
            'footer'      => [
                [
                    'label' => __('Ver toda la actividad', 'flavor-chat-ia'),
                    'url'   => admin_url('admin.php?page=flavor-activity-log'),
                ],
            ],
        ];
    }

    /**
     * Renderiza el widget de actividad reciente
     *
     * @param array $data Datos del widget
     * @return void
     */
    public function render_recent_activity(array $data): void {
        echo $this->renderer->render_item_list($data['items'] ?? [], 5);
    }

    /**
     * Obtiene el icono para un tipo de actividad
     *
     * @param string $tipo Tipo de actividad
     * @param string $modulo_id ID del modulo
     * @return string
     */
    private function get_activity_icon(string $tipo, string $modulo_id): string {
        $iconos = [
            'error'   => 'dashicons-dismiss',
            'warning' => 'dashicons-warning',
            'success' => 'dashicons-yes-alt',
            'info'    => 'dashicons-info',
        ];

        return $iconos[$tipo] ?? 'dashicons-marker';
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Obtener todos los datos del dashboard
     *
     * @return void
     */
    public function ajax_get_dashboard_data(): void {
        check_ajax_referer('fud_dashboard_nonce', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
        }

        $widgets = $this->registry->get_sorted();
        $widgets_data = [];

        foreach ($widgets as $widget) {
            $widget_id = $widget->get_widget_id();
            $config = $widget->get_widget_config();

            ob_start();
            $widget->render_widget();
            $html = ob_get_clean();

            $widgets_data[$widget_id] = [
                'config' => $config,
                'data'   => $widget->get_widget_data(),
                'html'   => $html,
            ];
        }

        wp_send_json_success([
            'widgets'    => $widgets_data,
            'categories' => $this->registry->get_categories_with_count(),
            'timestamp'  => current_time('c'),
        ]);
    }

    /**
     * AJAX: Guardar disposicion de widgets
     *
     * @return void
     */
    public function ajax_save_layout(): void {
        check_ajax_referer('fud_dashboard_nonce', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
        }

        $order = isset($_POST['order']) ? array_map('sanitize_key', (array) $_POST['order']) : [];
        $visible = isset($_POST['visible']) ? array_map('sanitize_key', (array) $_POST['visible']) : [];
        $collapsed = isset($_POST['collapsed']) ? array_map('sanitize_key', (array) $_POST['collapsed']) : [];

        $usuario_id = get_current_user_id();

        $saved = true;
        $saved = $saved && update_user_meta($usuario_id, 'fud_widget_order', $order);
        $saved = $saved && update_user_meta($usuario_id, 'fud_widget_visibility', $visible);
        $saved = $saved && update_user_meta($usuario_id, 'fud_collapsed_widgets', $collapsed);

        if ($saved) {
            wp_send_json_success(['message' => __('Disposicion guardada', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(['message' => __('Error al guardar', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Refrescar todos los widgets
     *
     * @return void
     */
    public function ajax_refresh_all(): void {
        check_ajax_referer('fud_dashboard_nonce', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
        }

        // Limpiar caches
        $widgets = $this->registry->get_all();
        foreach ($widgets as $widget) {
            if (method_exists($widget, 'clear_cache')) {
                $widget->clear_cache();
            }
        }

        // Devolver datos frescos
        $this->ajax_get_dashboard_data();
    }

    // =========================================================================
    // REST API
    // =========================================================================

    /**
     * Registra rutas REST
     *
     * @return void
     */
    public function register_rest_routes(): void {
        register_rest_route('flavor/v1', '/unified-dashboard', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_dashboard'],
            'permission_callback' => function () {
                return current_user_can('read');
            },
        ]);

        register_rest_route('flavor/v1', '/unified-dashboard/layout', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_save_layout'],
            'permission_callback' => function () {
                return current_user_can('read');
            },
        ]);
    }

    /**
     * REST: Obtener dashboard completo
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function rest_get_dashboard(WP_REST_Request $request): WP_REST_Response {
        $include_html = $request->get_param('include_html') === 'true';

        $widgets = $this->registry->get_sorted();
        $widgets_data = [];

        foreach ($widgets as $widget) {
            $item = [
                'id'     => $widget->get_widget_id(),
                'config' => $widget->get_widget_config(),
                'data'   => $widget->get_widget_data(),
            ];

            if ($include_html) {
                ob_start();
                $widget->render_widget();
                $item['html'] = ob_get_clean();
            }

            $widgets_data[] = $item;
        }

        return new WP_REST_Response([
            'success'         => true,
            'widgets'         => $widgets_data,
            'categories'      => $this->registry->get_categories_with_count(),
            'user_order'      => $this->registry->get_user_widget_order(),
            'user_visibility' => $this->registry->get_visible_widget_ids(),
            'timestamp'       => current_time('c'),
        ], 200);
    }

    /**
     * REST: Guardar layout
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function rest_save_layout(WP_REST_Request $request): WP_REST_Response {
        $order = $request->get_param('order') ?? [];
        $visible = $request->get_param('visible') ?? [];

        $saved_order = $this->registry->save_user_widget_order($order);
        $saved_visible = $this->registry->save_user_widget_visibility($visible);

        return new WP_REST_Response([
            'success' => $saved_order && $saved_visible,
            'message' => ($saved_order && $saved_visible)
                ? __('Layout guardado correctamente', 'flavor-chat-ia')
                : __('Error al guardar el layout', 'flavor-chat-ia'),
        ], $saved_order && $saved_visible ? 200 : 500);
    }

    /**
     * Renderiza el shortcode del dashboard unificado
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del dashboard
     */
    public function render_shortcode($atts = []): string {
        // Verificar login
        if (!is_user_logged_in()) {
            return '<div class="fud-login-required">
                <p>' . esc_html__('Debes iniciar sesión para acceder a tu portal.', 'flavor-chat-ia') . '</p>
                <a href="' . esc_url(wp_login_url(get_permalink())) . '" class="fud-btn fud-btn-primary">' . esc_html__('Iniciar sesión', 'flavor-chat-ia') . '</a>
            </div>';
        }

        // Encolar assets
        $this->enqueue_frontend_assets();

        // Renderizar dashboard
        ob_start();
        $this->render_frontend_dashboard();
        return ob_get_clean();
    }

    /**
     * Encola assets para el frontend
     *
     * @return void
     */
    public function enqueue_frontend_assets(): void {
        // Solo en páginas con el shortcode o en el portal
        global $post;
        $es_pagina_dashboard = $post && has_shortcode($post->post_content, 'flavor_unified_dashboard');
        $es_portal = strpos($_SERVER['REQUEST_URI'] ?? '', '/mi-portal') !== false;

        if (!$es_pagina_dashboard && !$es_portal) {
            return;
        }

        $plugin_url = FLAVOR_CHAT_IA_URL;
        $version = defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '4.1.0';

        // Dashicons
        wp_enqueue_style('dashicons');

        // =====================================================================
        // CSS - Sistema de Diseño Unificado (v4.1.0)
        // =====================================================================

        // 1. Design Tokens
        wp_enqueue_style(
            'fl-design-tokens',
            $plugin_url . 'assets/css/design-tokens.css',
            [],
            $version
        );

        // 2. Compatibilidad
        wp_enqueue_style(
            'fl-design-tokens-compat',
            $plugin_url . 'assets/css/design-tokens-compat.css',
            ['fl-design-tokens'],
            $version
        );

        // 3. Widgets
        wp_enqueue_style(
            'fl-dashboard-widgets',
            $plugin_url . 'assets/css/dashboard-widgets.css',
            ['fl-design-tokens-compat'],
            $version
        );

        // 4. Grupos
        wp_enqueue_style(
            'fl-dashboard-groups',
            $plugin_url . 'assets/css/dashboard-groups.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 5. Estados
        wp_enqueue_style(
            'fl-dashboard-states',
            $plugin_url . 'assets/css/dashboard-states.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 6. Accesibilidad
        wp_enqueue_style(
            'fl-dashboard-a11y',
            $plugin_url . 'assets/css/dashboard-a11y.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 7. Responsive
        wp_enqueue_style(
            'fl-dashboard-responsive',
            $plugin_url . 'assets/css/dashboard-responsive.css',
            ['fl-dashboard-groups'],
            $version
        );

        // 8. Breadcrumbs
        wp_enqueue_style(
            'fl-breadcrumbs',
            $plugin_url . 'assets/css/breadcrumbs.css',
            ['fl-design-tokens'],
            $version
        );

        // CSS Unificado principal
        wp_enqueue_style(
            'flavor-unified-dashboard',
            $plugin_url . 'assets/css/unified-dashboard.css',
            ['fl-dashboard-responsive'],
            $version
        );

        // =====================================================================
        // JavaScript
        // =====================================================================

        // SortableJS
        wp_enqueue_script(
            'sortablejs',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
            [],
            '1.15.2',
            true
        );

        // Dashboard Sortable
        wp_enqueue_script(
            'fl-dashboard-sortable',
            $plugin_url . 'assets/js/dashboard-sortable.js',
            ['sortablejs'],
            $version,
            true
        );

        // Dashboard principal (si existe)
        if (file_exists(FLAVOR_CHAT_IA_PATH . 'assets/js/unified-dashboard.js')) {
            wp_enqueue_script(
                'flavor-unified-dashboard',
                $plugin_url . 'assets/js/unified-dashboard.js',
                ['jquery', 'fl-dashboard-sortable'],
                $version,
                true
            );
        }

        // Localizacion
        $dashboard_config = [
            'ajax_url'        => admin_url('admin-ajax.php'),
            'restUrl'         => rest_url('flavor/v1/dashboard/'),
            'nonce'           => wp_create_nonce('fud_nonce'),
            'refreshInterval' => self::AUTO_REFRESH_INTERVAL * 1000,
            'i18n'            => [
                'loading'        => __('Cargando...', 'flavor-chat-ia'),
                'error'          => __('Error al cargar', 'flavor-chat-ia'),
                'saved'          => __('Guardado', 'flavor-chat-ia'),
                'dragStart'      => __('Arrastrando widget', 'flavor-chat-ia'),
                'dragEnd'        => __('Widget soltado', 'flavor-chat-ia'),
                'orderSaved'     => __('Orden guardado', 'flavor-chat-ia'),
            ],
        ];

        wp_localize_script('fl-dashboard-sortable', 'flavorDashboard', $dashboard_config);
    }

    /**
     * Renderiza el dashboard para frontend
     *
     * @return void
     */
    private function render_frontend_dashboard(): void {
        $user_id = get_current_user_id();
        $widgets = $this->get_user_widgets($user_id);
        $widgets_agrupados = $this->agrupar_widgets_por_categoria($widgets);
        $categorias_disponibles = $this->obtener_categorias_con_conteo($widgets);
        ?>
        <div class="fud-frontend-dashboard fl-dashboard-container" data-fl-dashboard>
            <!-- Header del Dashboard -->
            <div class="fud-header fl-dashboard-header">
                <div class="fl-dashboard-header__info">
                    <h2 class="fl-dashboard-header__title"><?php esc_html_e('Mi Portal', 'flavor-chat-ia'); ?></h2>
                    <p class="fud-welcome fl-dashboard-header__welcome">
                        <?php printf(esc_html__('Hola, %s', 'flavor-chat-ia'), esc_html(wp_get_current_user()->display_name)); ?>
                    </p>
                </div>
                <div class="fl-dashboard-header__actions">
                    <button type="button" class="fl-btn fl-btn--ghost fl-btn--icon" id="fl-refresh-dashboard" aria-label="<?php esc_attr_e('Actualizar dashboard', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            </div>

            <!-- Filtros por Categoría -->
            <?php if (!empty($categorias_disponibles) && count($categorias_disponibles) > 1): ?>
            <nav class="fl-category-filters" role="navigation" aria-label="<?php esc_attr_e('Filtrar por categoría', 'flavor-chat-ia'); ?>">
                <button type="button" class="fl-category-filter fl-category-filter--active" data-category="all" aria-pressed="true">
                    <span class="fl-category-filter__icon dashicons dashicons-screenoptions"></span>
                    <span class="fl-category-filter__label"><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></span>
                    <span class="fl-category-filter__count"><?php echo count($widgets); ?></span>
                </button>
                <?php foreach ($categorias_disponibles as $categoria_id => $categoria_info): ?>
                <button type="button" class="fl-category-filter" data-category="<?php echo esc_attr($categoria_id); ?>" aria-pressed="false">
                    <span class="fl-category-filter__icon dashicons <?php echo esc_attr($categoria_info['icono']); ?>"></span>
                    <span class="fl-category-filter__label"><?php echo esc_html($categoria_info['nombre']); ?></span>
                    <span class="fl-category-filter__count"><?php echo intval($categoria_info['cantidad']); ?></span>
                </button>
                <?php endforeach; ?>
            </nav>
            <?php endif; ?>

            <!-- Widgets Agrupados por Categoría -->
            <?php if (empty($widgets)): ?>
                <div class="fl-empty-state">
                    <span class="fl-empty-state__icon dashicons dashicons-screenoptions"></span>
                    <p class="fl-empty-state__message"><?php esc_html_e('No hay módulos activos.', 'flavor-chat-ia'); ?></p>
                    <p class="fl-empty-state__hint"><?php esc_html_e('Contacta con el administrador para activar módulos.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="fl-widget-groups" data-fl-widget-groups>
                    <?php foreach ($widgets_agrupados as $categoria_id => $grupo): ?>
                    <section class="fl-widget-group" data-category="<?php echo esc_attr($categoria_id); ?>" aria-labelledby="fl-group-<?php echo esc_attr($categoria_id); ?>">
                        <header class="fl-widget-group__header">
                            <button type="button" class="fl-widget-group__toggle" aria-expanded="true" aria-controls="fl-group-content-<?php echo esc_attr($categoria_id); ?>">
                                <span class="fl-widget-group__icon dashicons <?php echo esc_attr($grupo['icono']); ?>"></span>
                                <h3 id="fl-group-<?php echo esc_attr($categoria_id); ?>" class="fl-widget-group__title">
                                    <?php echo esc_html($grupo['nombre']); ?>
                                </h3>
                                <span class="fl-widget-group__count"><?php echo count($grupo['widgets']); ?></span>
                                <span class="fl-widget-group__chevron dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                        </header>
                        <div id="fl-group-content-<?php echo esc_attr($categoria_id); ?>" class="fl-widget-group__content fud-widgets-grid">
                            <?php foreach ($grupo['widgets'] as $widget): ?>
                            <article class="fud-widget fl-widget fl-widget--standard" data-module="<?php echo esc_attr($widget['id']); ?>" data-category="<?php echo esc_attr($categoria_id); ?>" role="region" aria-labelledby="widget-title-<?php echo esc_attr($widget['id']); ?>">
                                <header class="fud-widget-header fl-widget__header">
                                    <span class="dashicons <?php echo esc_attr($widget['icon'] ?? 'dashicons-admin-generic'); ?>" aria-hidden="true"></span>
                                    <h4 id="widget-title-<?php echo esc_attr($widget['id']); ?>" class="fl-widget__title"><?php echo esc_html($widget['title']); ?></h4>
                                </header>
                                <div class="fud-widget-stats fl-widget__body">
                                    <?php if (!empty($widget['stats'])): ?>
                                        <div class="fl-widget-stats">
                                        <?php foreach ($widget['stats'] as $stat): ?>
                                            <div class="fl-stat-item">
                                                <span class="fl-stat-value"><?php echo esc_html($stat['value']); ?></span>
                                                <span class="fl-stat-label"><?php echo esc_html($stat['label']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <footer class="fud-widget-actions fl-widget__footer">
                                    <a href="<?php echo esc_url(home_url('/mi-portal/' . $widget['id'] . '/')); ?>" class="fl-btn fl-btn--primary fl-btn--sm">
                                        <?php esc_html_e('Ver más', 'flavor-chat-ia'); ?>
                                        <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                                    </a>
                                </footer>
                            </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Anuncios para lectores de pantalla -->
            <div class="fl-sr-only" role="status" aria-live="polite" aria-atomic="true" id="fl-dashboard-announcer"></div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filtros de categoría
            const filtros = document.querySelectorAll('.fl-category-filter');
            const grupos = document.querySelectorAll('.fl-widget-group');
            const announcer = document.getElementById('fl-dashboard-announcer');

            filtros.forEach(function(filtro) {
                filtro.addEventListener('click', function() {
                    const categoria = this.dataset.category;

                    // Actualizar estado activo
                    filtros.forEach(function(f) {
                        f.classList.remove('fl-category-filter--active');
                        f.setAttribute('aria-pressed', 'false');
                    });
                    this.classList.add('fl-category-filter--active');
                    this.setAttribute('aria-pressed', 'true');

                    // Mostrar/ocultar grupos
                    grupos.forEach(function(grupo) {
                        if (categoria === 'all' || grupo.dataset.category === categoria) {
                            grupo.style.display = '';
                            grupo.removeAttribute('hidden');
                        } else {
                            grupo.style.display = 'none';
                            grupo.setAttribute('hidden', '');
                        }
                    });

                    // Anunciar cambio para lectores de pantalla
                    if (announcer) {
                        const label = this.querySelector('.fl-category-filter__label');
                        announcer.textContent = label ? 'Mostrando: ' + label.textContent : '';
                    }
                });
            });

            // Toggle de grupos colapsables
            const toggles = document.querySelectorAll('.fl-widget-group__toggle');
            toggles.forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    const expanded = this.getAttribute('aria-expanded') === 'true';
                    const contentId = this.getAttribute('aria-controls');
                    const content = document.getElementById(contentId);
                    const grupo = this.closest('.fl-widget-group');

                    this.setAttribute('aria-expanded', !expanded);

                    if (content) {
                        if (expanded) {
                            content.style.display = 'none';
                            grupo.classList.add('fl-widget-group--collapsed');
                        } else {
                            content.style.display = '';
                            grupo.classList.remove('fl-widget-group--collapsed');
                        }
                    }
                });
            });

            // Botón refrescar
            const refreshBtn = document.getElementById('fl-refresh-dashboard');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function() {
                    this.classList.add('fl-spinning');
                    location.reload();
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Agrupa los widgets por categoría
     *
     * @param array $widgets Lista de widgets
     * @return array Widgets agrupados por categoría
     */
    private function agrupar_widgets_por_categoria(array $widgets): array {
        $categorias_definidas = $this->obtener_definicion_categorias();
        $agrupados = [];

        foreach ($widgets as $widget) {
            $categoria_id = $this->mapear_modulo_a_categoria($widget['id']);

            if (!isset($agrupados[$categoria_id])) {
                $categoria_info = $categorias_definidas[$categoria_id] ?? [
                    'nombre' => ucfirst($categoria_id),
                    'icono' => 'dashicons-category',
                ];
                $agrupados[$categoria_id] = [
                    'nombre' => $categoria_info['nombre'],
                    'icono' => $categoria_info['icono'],
                    'widgets' => [],
                ];
            }

            $agrupados[$categoria_id]['widgets'][] = $widget;
        }

        return $agrupados;
    }

    /**
     * Obtiene las categorías con conteo de widgets
     *
     * @param array $widgets Lista de widgets
     * @return array Categorías con cantidad
     */
    private function obtener_categorias_con_conteo(array $widgets): array {
        $categorias_definidas = $this->obtener_definicion_categorias();
        $conteo = [];

        foreach ($widgets as $widget) {
            $categoria_id = $this->mapear_modulo_a_categoria($widget['id']);
            if (!isset($conteo[$categoria_id])) {
                $conteo[$categoria_id] = 0;
            }
            $conteo[$categoria_id]++;
        }

        $resultado = [];
        foreach ($conteo as $categoria_id => $cantidad) {
            if (isset($categorias_definidas[$categoria_id])) {
                $resultado[$categoria_id] = [
                    'nombre' => $categorias_definidas[$categoria_id]['nombre'],
                    'icono' => $categorias_definidas[$categoria_id]['icono'],
                    'cantidad' => $cantidad,
                ];
            }
        }

        return $resultado;
    }

    /**
     * Mapea un módulo a su categoría correspondiente
     *
     * @param string $modulo_id ID del módulo
     * @return string ID de la categoría
     */
    private function mapear_modulo_a_categoria(string $modulo_id): string {
        $mapeo = [
            // Gestión
            'reservas' => 'gestion',
            'espacios-comunes' => 'gestion',
            'fichaje-empleados' => 'gestion',
            'incidencias' => 'gestion',
            'tramites' => 'gestion',

            // Comunidad
            'eventos' => 'comunidad',
            'cursos' => 'comunidad',
            'talleres' => 'comunidad',
            'comunidades' => 'comunidad',
            'participacion' => 'comunidad',

            // Economía
            'marketplace' => 'economia',
            'banco-tiempo' => 'economia',
            'grupos-consumo' => 'economia',

            // Sostenibilidad
            'huertos-urbanos' => 'sostenibilidad',
            'reciclaje' => 'sostenibilidad',
            'compostaje' => 'sostenibilidad',

            // Movilidad
            'carpooling' => 'movilidad',
            'bicicletas-compartidas' => 'movilidad',

            // Recursos
            'biblioteca' => 'recursos',
            'podcast' => 'recursos',
        ];

        return $mapeo[$modulo_id] ?? 'otros';
    }

    /**
     * Obtiene la definición de todas las categorías disponibles
     *
     * @return array Definición de categorías
     */
    private function obtener_definicion_categorias(): array {
        return [
            'gestion' => [
                'nombre' => __('Gestión', 'flavor-chat-ia'),
                'icono' => 'dashicons-clipboard',
            ],
            'comunidad' => [
                'nombre' => __('Comunidad', 'flavor-chat-ia'),
                'icono' => 'dashicons-groups',
            ],
            'economia' => [
                'nombre' => __('Economía', 'flavor-chat-ia'),
                'icono' => 'dashicons-cart',
            ],
            'sostenibilidad' => [
                'nombre' => __('Sostenibilidad', 'flavor-chat-ia'),
                'icono' => 'dashicons-palmtree',
            ],
            'movilidad' => [
                'nombre' => __('Movilidad', 'flavor-chat-ia'),
                'icono' => 'dashicons-car',
            ],
            'recursos' => [
                'nombre' => __('Recursos', 'flavor-chat-ia'),
                'icono' => 'dashicons-archive',
            ],
            'otros' => [
                'nombre' => __('Otros', 'flavor-chat-ia'),
                'icono' => 'dashicons-category',
            ],
        ];
    }

    /**
     * Obtiene los widgets del usuario
     *
     * @param int $user_id ID del usuario
     * @return array Lista de widgets
     */
    private function get_user_widgets(int $user_id): array {
        // Obtener módulos activos
        $modulos_activos = $this->get_active_modules();
        $widgets = [];

        foreach ($modulos_activos as $modulo) {
            $widgets[] = [
                'id' => $modulo['id'],
                'title' => $modulo['nombre'],
                'icon' => $modulo['icono'] ?? 'dashicons-admin-generic',
                'stats' => $this->get_module_stats($modulo['id'], $user_id),
            ];
        }

        return $widgets;
    }

    /**
     * Obtiene estadísticas de un módulo para el usuario
     *
     * @param string $module_id ID del módulo
     * @param int $user_id ID del usuario
     * @return array Estadísticas
     */
    private function get_module_stats(string $module_id, int $user_id): array {
        global $wpdb;
        $stats = [];

        // Normalizar ID (guiones a guiones bajos para BD)
        $module_id_normalizado = str_replace('_', '-', $module_id);

        switch ($module_id_normalizado) {
            case 'grupos-consumo':
                $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
                if ($this->table_exists($tabla_pedidos)) {
                    $total_pedidos = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_pedidos} WHERE usuario_id = %d",
                        $user_id
                    ));
                    $stats[] = ['value' => $total_pedidos, 'label' => __('Mis pedidos', 'flavor-chat-ia'), 'icon' => 'dashicons-cart'];
                }

                // Items en cesta
                $tabla_lista = $wpdb->prefix . 'flavor_gc_lista_compra';
                if ($this->table_exists($tabla_lista)) {
                    $items_cesta = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_lista} WHERE usuario_id = %d",
                        $user_id
                    ));
                    $stats[] = ['value' => $items_cesta, 'label' => __('En cesta', 'flavor-chat-ia'), 'icon' => 'dashicons-products'];
                }
                break;

            case 'banco-tiempo':
                $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
                if ($this->table_exists($tabla_servicios)) {
                    $servicios_activos = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_servicios} WHERE usuario_id = %d AND estado = 'activo'",
                        $user_id
                    ));
                    $stats[] = ['value' => $servicios_activos, 'label' => __('Servicios', 'flavor-chat-ia'), 'icon' => 'dashicons-clock'];
                }

                $tabla_intercambios = $wpdb->prefix . 'flavor_banco_tiempo_intercambios';
                if ($this->table_exists($tabla_intercambios)) {
                    $intercambios = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_intercambios}
                         WHERE (solicitante_id = %d OR proveedor_id = %d) AND estado = 'completado'",
                        $user_id, $user_id
                    ));
                    $stats[] = ['value' => $intercambios, 'label' => __('Intercambios', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize'];
                }
                break;

            case 'espacios-comunes':
                $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
                if ($this->table_exists($tabla_reservas)) {
                    $reservas_activas = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_reservas}
                         WHERE usuario_id = %d AND estado = 'confirmada' AND fecha_inicio >= NOW()",
                        $user_id
                    ));
                    $stats[] = ['value' => $reservas_activas, 'label' => __('Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'];
                }
                break;

            case 'incidencias':
                $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
                if ($this->table_exists($tabla_incidencias)) {
                    $incidencias_abiertas = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_incidencias}
                         WHERE usuario_id = %d AND estado IN ('abierta', 'en_proceso')",
                        $user_id
                    ));
                    $stats[] = ['value' => $incidencias_abiertas, 'label' => __('Abiertas', 'flavor-chat-ia'), 'icon' => 'dashicons-warning'];
                }
                break;

            case 'eventos':
                $eventos_inscritos = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
                     INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                     WHERE pm.meta_key = '_flavor_evento_asistentes'
                       AND pm.meta_value LIKE %s
                       AND p.post_type = 'flavor_evento'
                       AND p.post_status = 'publish'",
                    '%"' . $user_id . '"%'
                ));
                $stats[] = ['value' => $eventos_inscritos, 'label' => __('Inscritos', 'flavor-chat-ia'), 'icon' => 'dashicons-tickets-alt'];
                break;

            case 'bicicletas-compartidas':
                $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
                if ($this->table_exists($tabla_prestamos)) {
                    $prestamos_activos = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_prestamos}
                         WHERE usuario_id = %d AND estado = 'activo'",
                        $user_id
                    ));
                    $stats[] = ['value' => $prestamos_activos, 'label' => __('En uso', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-site'];
                }
                break;

            case 'reciclaje':
                $tabla_registros = $wpdb->prefix . 'flavor_reciclaje_registros';
                if ($this->table_exists($tabla_registros)) {
                    $total_kg = (float) $wpdb->get_var($wpdb->prepare(
                        "SELECT COALESCE(SUM(cantidad_kg), 0) FROM {$tabla_registros}
                         WHERE usuario_id = %d AND MONTH(fecha) = MONTH(NOW())",
                        $user_id
                    ));
                    $stats[] = ['value' => number_format($total_kg, 1) . ' kg', 'label' => __('Este mes', 'flavor-chat-ia'), 'icon' => 'dashicons-trash'];
                }
                break;

            case 'compostaje':
                $tabla_aportes = $wpdb->prefix . 'flavor_compostaje_aportes';
                if ($this->table_exists($tabla_aportes)) {
                    $aportes_mes = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_aportes}
                         WHERE usuario_id = %d AND MONTH(fecha) = MONTH(NOW())",
                        $user_id
                    ));
                    $stats[] = ['value' => $aportes_mes, 'label' => __('Aportes/mes', 'flavor-chat-ia'), 'icon' => 'dashicons-carrot'];
                }
                break;

            case 'huertos-urbanos':
                $tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
                if ($this->table_exists($tabla_parcelas)) {
                    $parcelas = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_parcelas}
                         WHERE usuario_id = %d AND estado = 'activa'",
                        $user_id
                    ));
                    $stats[] = ['value' => $parcelas, 'label' => __('Parcelas', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-site-alt3'];
                }
                break;

            case 'marketplace':
                $productos = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts}
                     WHERE post_author = %d AND post_type = 'flavor_producto' AND post_status = 'publish'",
                    $user_id
                ));
                $stats[] = ['value' => $productos, 'label' => __('Productos', 'flavor-chat-ia'), 'icon' => 'dashicons-store'];
                break;

            case 'fichaje-empleados':
                $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';
                if ($this->table_exists($tabla_fichajes)) {
                    $fichaje_hoy = $wpdb->get_row($wpdb->prepare(
                        "SELECT entrada, salida FROM {$tabla_fichajes}
                         WHERE usuario_id = %d AND DATE(fecha) = CURDATE()
                         ORDER BY entrada DESC LIMIT 1",
                        $user_id
                    ));
                    if ($fichaje_hoy) {
                        $estado = $fichaje_hoy->salida ? __('Salida', 'flavor-chat-ia') : __('Entrada', 'flavor-chat-ia');
                        $stats[] = ['value' => $estado, 'label' => __('Hoy', 'flavor-chat-ia'), 'icon' => 'dashicons-clock'];
                    } else {
                        $stats[] = ['value' => '-', 'label' => __('Sin fichar', 'flavor-chat-ia'), 'icon' => 'dashicons-clock'];
                    }
                }
                break;

            default:
                // Intentar obtener del módulo directamente
                $modulo = $this->get_module_instance($module_id_normalizado);
                if ($modulo && method_exists($modulo, 'get_estadisticas_dashboard')) {
                    $estadisticas_modulo = $modulo->get_estadisticas_dashboard($user_id);
                    if (!empty($estadisticas_modulo)) {
                        return $estadisticas_modulo;
                    }
                }

                // Fallback: valor por defecto
                return [['value' => '-', 'label' => __('Total', 'flavor-chat-ia')]];
        }

        // Si no hay estadísticas, devolver valor por defecto
        if (empty($stats)) {
            return [['value' => '-', 'label' => __('Total', 'flavor-chat-ia')]];
        }

        return $stats;
    }

    /**
     * Verifica si una tabla existe en la base de datos
     *
     * @param string $table_name Nombre completo de la tabla
     * @return bool
     */
    private function table_exists(string $table_name): bool {
        global $wpdb;
        static $cache = [];

        if (isset($cache[$table_name])) {
            return $cache[$table_name];
        }

        $result = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));

        $cache[$table_name] = ($result === $table_name);
        return $cache[$table_name];
    }

    /**
     * Obtiene la instancia de un módulo
     *
     * @param string $module_id ID del módulo (con guiones)
     * @return object|null Instancia del módulo o null
     */
    private function get_module_instance(string $module_id) {
        // Normalizar a guiones bajos para el loader
        $module_id_normalizado = str_replace('-', '_', $module_id);

        if (class_exists('Flavor_Chat_Module_Loader')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            if (method_exists($loader, 'get_module')) {
                return $loader->get_module($module_id_normalizado);
            }
        }

        return null;
    }

    /**
     * Obtiene los módulos activos del sistema
     *
     * @return array Lista de módulos activos
     */
    private function get_active_modules(): array {
        // Definición completa de todos los módulos disponibles
        $modulos_disponibles = [
            // === GESTIÓN ===
            'reservas'              => ['nombre' => __('Reservas', 'flavor-chat-ia'), 'icono' => 'dashicons-calendar', 'categoria' => 'gestion'],
            'espacios_comunes'      => ['nombre' => __('Espacios Comunes', 'flavor-chat-ia'), 'icono' => 'dashicons-admin-home', 'categoria' => 'gestion'],
            'parkings'              => ['nombre' => __('Parkings', 'flavor-chat-ia'), 'icono' => 'dashicons-location-alt', 'categoria' => 'gestion'],
            'incidencias'           => ['nombre' => __('Incidencias', 'flavor-chat-ia'), 'icono' => 'dashicons-warning', 'categoria' => 'gestion'],
            'tramites'              => ['nombre' => __('Trámites', 'flavor-chat-ia'), 'icono' => 'dashicons-clipboard', 'categoria' => 'gestion'],
            'fichaje_empleados'     => ['nombre' => __('Fichaje', 'flavor-chat-ia'), 'icono' => 'dashicons-clock', 'categoria' => 'gestion'],
            'clientes'              => ['nombre' => __('Clientes', 'flavor-chat-ia'), 'icono' => 'dashicons-id-alt', 'categoria' => 'gestion'],
            'facturas'              => ['nombre' => __('Facturas', 'flavor-chat-ia'), 'icono' => 'dashicons-media-text', 'categoria' => 'gestion'],

            // === COMUNIDAD ===
            'eventos'               => ['nombre' => __('Eventos', 'flavor-chat-ia'), 'icono' => 'dashicons-calendar-alt', 'categoria' => 'comunidad'],
            'cursos'                => ['nombre' => __('Cursos', 'flavor-chat-ia'), 'icono' => 'dashicons-welcome-learn-more', 'categoria' => 'comunidad'],
            'talleres'              => ['nombre' => __('Talleres', 'flavor-chat-ia'), 'icono' => 'dashicons-hammer', 'categoria' => 'comunidad'],
            'comunidades'           => ['nombre' => __('Comunidades', 'flavor-chat-ia'), 'icono' => 'dashicons-groups', 'categoria' => 'comunidad'],
            'colectivos'            => ['nombre' => __('Colectivos', 'flavor-chat-ia'), 'icono' => 'dashicons-networking', 'categoria' => 'comunidad'],
            'socios'                => ['nombre' => __('Socios', 'flavor-chat-ia'), 'icono' => 'dashicons-id', 'categoria' => 'comunidad'],
            'participacion'         => ['nombre' => __('Participación', 'flavor-chat-ia'), 'icono' => 'dashicons-megaphone', 'categoria' => 'comunidad'],
            'presupuestos_participativos' => ['nombre' => __('Presupuestos Participativos', 'flavor-chat-ia'), 'icono' => 'dashicons-chart-pie', 'categoria' => 'comunidad'],
            'ayuda_vecinal'         => ['nombre' => __('Ayuda Vecinal', 'flavor-chat-ia'), 'icono' => 'dashicons-heart', 'categoria' => 'comunidad'],
            'avisos_municipales'    => ['nombre' => __('Avisos Municipales', 'flavor-chat-ia'), 'icono' => 'dashicons-bell', 'categoria' => 'comunidad'],

            // === ECONOMÍA ===
            'marketplace'           => ['nombre' => __('Marketplace', 'flavor-chat-ia'), 'icono' => 'dashicons-cart', 'categoria' => 'economia'],
            'banco_tiempo'          => ['nombre' => __('Banco de Tiempo', 'flavor-chat-ia'), 'icono' => 'dashicons-backup', 'categoria' => 'economia'],
            'grupos_consumo'        => ['nombre' => __('Grupos de Consumo', 'flavor-chat-ia'), 'icono' => 'dashicons-store', 'categoria' => 'economia'],
            'advertising'           => ['nombre' => __('Publicidad', 'flavor-chat-ia'), 'icono' => 'dashicons-megaphone', 'categoria' => 'economia'],
            'empresarial'           => ['nombre' => __('Empresarial', 'flavor-chat-ia'), 'icono' => 'dashicons-building', 'categoria' => 'economia'],
            'trading_ia'            => ['nombre' => __('Trading IA', 'flavor-chat-ia'), 'icono' => 'dashicons-chart-line', 'categoria' => 'economia'],
            'dex_solana'            => ['nombre' => __('DEX Solana', 'flavor-chat-ia'), 'icono' => 'dashicons-superhero-alt', 'categoria' => 'economia'],

            // === SOSTENIBILIDAD ===
            'huertos_urbanos'       => ['nombre' => __('Huertos Urbanos', 'flavor-chat-ia'), 'icono' => 'dashicons-carrot', 'categoria' => 'sostenibilidad'],
            'reciclaje'             => ['nombre' => __('Reciclaje', 'flavor-chat-ia'), 'icono' => 'dashicons-update-alt', 'categoria' => 'sostenibilidad'],
            'compostaje'            => ['nombre' => __('Compostaje', 'flavor-chat-ia'), 'icono' => 'dashicons-admin-site-alt', 'categoria' => 'sostenibilidad'],

            // === MOVILIDAD ===
            'carpooling'            => ['nombre' => __('Carpooling', 'flavor-chat-ia'), 'icono' => 'dashicons-car', 'categoria' => 'movilidad'],
            'bicicletas_compartidas' => ['nombre' => __('Bicicletas', 'flavor-chat-ia'), 'icono' => 'dashicons-dashboard', 'categoria' => 'movilidad'],

            // === RECURSOS ===
            'biblioteca'            => ['nombre' => __('Biblioteca', 'flavor-chat-ia'), 'icono' => 'dashicons-book', 'categoria' => 'recursos'],
            'podcast'               => ['nombre' => __('Podcast', 'flavor-chat-ia'), 'icono' => 'dashicons-microphone', 'categoria' => 'recursos'],
            'radio'                 => ['nombre' => __('Radio', 'flavor-chat-ia'), 'icono' => 'dashicons-format-audio', 'categoria' => 'recursos'],
            'multimedia'            => ['nombre' => __('Multimedia', 'flavor-chat-ia'), 'icono' => 'dashicons-format-video', 'categoria' => 'recursos'],

            // === COMUNICACIÓN ===
            'foros'                 => ['nombre' => __('Foros', 'flavor-chat-ia'), 'icono' => 'dashicons-format-chat', 'categoria' => 'comunicacion'],
            'chat_interno'          => ['nombre' => __('Chat Interno', 'flavor-chat-ia'), 'icono' => 'dashicons-email-alt', 'categoria' => 'comunicacion'],
            'chat_grupos'           => ['nombre' => __('Grupos de Chat', 'flavor-chat-ia'), 'icono' => 'dashicons-groups', 'categoria' => 'comunicacion'],
            'red_social'            => ['nombre' => __('Red Social', 'flavor-chat-ia'), 'icono' => 'dashicons-share', 'categoria' => 'comunicacion'],
            'email_marketing'       => ['nombre' => __('Email Marketing', 'flavor-chat-ia'), 'icono' => 'dashicons-email', 'categoria' => 'comunicacion'],

            // === OTROS ===
            'bares'                 => ['nombre' => __('Bares', 'flavor-chat-ia'), 'icono' => 'dashicons-food', 'categoria' => 'otros'],
        ];

        // Obtener módulos activos desde configuración
        $settings = get_option('flavor_chat_ia_settings', []);
        $activos = $settings['active_modules'] ?? [];

        // Si no hay módulos configurados, mostrar todos
        if (empty($activos)) {
            $activos = array_keys($modulos_disponibles);
        }

        $resultado = [];
        foreach ($activos as $modulo_id) {
            // Normalizar ID (guiones a guiones bajos)
            $modulo_id_normalizado = str_replace('-', '_', $modulo_id);

            if (isset($modulos_disponibles[$modulo_id_normalizado])) {
                $resultado[] = [
                    'id' => str_replace('_', '-', $modulo_id_normalizado),
                    'nombre' => $modulos_disponibles[$modulo_id_normalizado]['nombre'],
                    'icono' => $modulos_disponibles[$modulo_id_normalizado]['icono'],
                    'categoria' => $modulos_disponibles[$modulo_id_normalizado]['categoria'],
                ];
            }
        }

        return $resultado;
    }
}

/**
 * Funcion helper para obtener la instancia del dashboard unificado
 *
 * @return Flavor_Unified_Dashboard
 */
function flavor_unified_dashboard(): Flavor_Unified_Dashboard {
    return Flavor_Unified_Dashboard::get_instance();
}
