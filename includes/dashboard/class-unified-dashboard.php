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
     * Obtiene la URL actual para redirects de login en el dashboard unificado.
     */
    private function get_current_request_url(): string {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';
        $request_uri = '/' . ltrim($request_uri, '/');

        return home_url($request_uri);
    }

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
        add_action('wp_ajax_fud_load_widget', [$this, 'ajax_load_widget']);

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
            'class-widgets-loader.php',
        ];

        foreach ($archivos_requeridos as $archivo) {
            $ruta_completa = $dashboard_path . $archivo;
            if (!file_exists($ruta_completa)) {
                flavor_log_error( "Archivo no encontrado: {$ruta_completa}", 'Dashboard' );
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
            'fud-dashboard-base',
            $plugin_url . 'assets/css/layouts/dashboard-base.css',
            ['fl-design-tokens-compat'],
            $version
        );

        // 4. Widgets y niveles
        wp_enqueue_style(
            'fl-dashboard-widgets',
            $plugin_url . 'assets/css/layouts/dashboard-widgets.css',
            ['fud-dashboard-base'],
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

        // CSS Componentes (legacy)
        wp_enqueue_style(
            'fud-dashboard-components',
            $plugin_url . 'assets/css/layouts/dashboard-components.css',
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

        // Lazy Loading de Widgets (v4.2.0)
        wp_enqueue_script(
            'fud-lazy-load',
            $plugin_url . 'assets/js/dashboard-lazy-load.js',
            [],
            $version,
            true
        );

        // =====================================================================
        // Localizacion de scripts
        // =====================================================================

        // Configuracion compartida para todos los scripts
        $dashboard_config = [
            'ajaxUrl'         => admin_url('admin-ajax.php'),
            'restUrl'         => rest_url('flavor/v1/dashboard/'),
            'nonce'           => wp_create_nonce('fud_dashboard_nonce'),
            'refreshInterval' => self::AUTO_REFRESH_INTERVAL * 1000,
            'features'        => [
                'sortable'     => true,
                'groups'       => true,
                'levels'       => true,
                'accessibility' => true,
                'lazyLoad'     => true,
            ],
            'i18n'            => $this->get_i18n_strings(),
        ];

        wp_localize_script('fud-lazy-load', 'fudDashboard', $dashboard_config);
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
            'title'    => __('Estado del Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
                'title'    => __('Red de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
            'title'    => __('Acciones Rapidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
            'title'    => __('Actividad Reciente', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
            'loading'           => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'error'             => __('Error al cargar datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'refreshing'        => __('Actualizando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'refreshed'         => __('Datos actualizados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'savingLayout'      => __('Guardando disposicion...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'layoutSaved'       => __('Disposicion guardada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'confirmHide'       => __('¿Ocultar este widget?', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'noWidgets'         => __('No hay widgets disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'customize'         => __('Personalizar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'refresh'           => __('Actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'collapse'          => __('Colapsar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'expand'            => __('Expandir', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'lastUpdate'        => __('Ultima actualizacion:', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'justNow'           => __('Ahora mismo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'minutesAgo'        => __('Hace %d minutos', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
            wp_die(__('No tienes permisos para acceder a esta pagina.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Asegurar que las dependencias esten cargadas
        if (!$this->registry || !$this->renderer) {
            $this->load_dependencies();
        }

        // Verificar que las dependencias se hayan cargado correctamente
        if (!$this->registry || !$this->renderer) {
            echo '<div class="wrap"><div class="notice notice-error"><p>';
            esc_html_e('Error: No se pudieron cargar las dependencias del dashboard.', FLAVOR_PLATFORM_TEXT_DOMAIN);
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

        // Obtener módulos activos desde ambas ubicaciones
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];

        // También leer de flavor_active_modules (legacy/compatibilidad)
        $modulos_activos_legacy = get_option('flavor_active_modules', []);
        if (!empty($modulos_activos_legacy)) {
            $modulos_activos = array_unique(array_merge($modulos_activos, $modulos_activos_legacy));
        }

        // Si no hay módulos configurados, usar default
        if (empty($modulos_activos)) {
            $modulos_activos = ['woocommerce'];
        }

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
        $es_contexto_admin = is_admin() && !wp_doing_ajax();

        if (is_array($estadisticas)) {
            foreach ($estadisticas as $stat) {
                // Usar 'url' o 'enlace' (compatibilidad con módulos legacy)
                $url_original = $stat['url'] ?? $stat['enlace'] ?? '';

                // En contexto frontend, no usar URLs de admin hardcodeadas
                // ya que no serían accesibles para el usuario
                $url_stat = '';
                if ($es_contexto_admin && !empty($url_original)) {
                    $url_stat = $url_original;
                }

                $stats[] = [
                    'icon'  => $stat['icon'] ?? 'dashicons-chart-bar',
                    'valor' => $stat['valor'] ?? 0,
                    'label' => $stat['label'] ?? '',
                    'color' => $stat['color'] ?? 'primary',
                    'url'   => $url_stat,
                ];
            }
        }

        // Obtener la URL correcta del modulo
        $module_url = $this->get_module_admin_url($modulo, $modulo_id);

        return [
            'stats'       => $stats,
            'items'       => [],
            'empty_state' => sprintf(__('No hay datos de %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $modulo->get_name()),
            'footer'      => [
                [
                    'label' => __('Administrar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url'   => $module_url,
                    'icon'  => 'dashicons-admin-generic',
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

        // Si estamos en admin, usar el mapeo de paginas de admin
        if (is_admin()) {
            $mapped_url = class_exists('Flavor_Module_Admin_Pages_Trait')
                ? Flavor_Module_Admin_Pages_Helper::get_module_dashboard_url($modulo_id)
                : null;

            if (!empty($mapped_url)) {
                return $mapped_url;
            }

            // Fallback: ir al indice de dashboards de modulos
            return admin_url('admin.php?page=flavor-module-dashboards');
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
                    <?php echo esc_html($link['label'] ?? __('Ver mas', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
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
        // Obtener módulos activos desde ambas ubicaciones
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];

        $modulos_activos_legacy = get_option('flavor_active_modules', []);
        if (!empty($modulos_activos_legacy)) {
            $modulos_activos = array_unique(array_merge($modulos_activos, $modulos_activos_legacy));
        }

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
                    'label' => __('Modulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'color' => 'primary',
                    'url'   => admin_url('admin.php?page=flavor-module-dashboards'),
                ],
                [
                    'icon'  => 'dashicons-cloud',
                    'valor' => $tiene_api_key ? __('OK', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('--', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'label' => __('API IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'color' => $tiene_api_key ? 'success' : 'warning',
                    'url'   => admin_url('admin.php?page=flavor-platform-settings'),
                ],
            ],
            'footer' => [
                [
                    'label' => __('Health Check', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url'   => admin_url('admin.php?page=flavor-platform-health-check'),
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
                __('Red no configurada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'dashicons-networking',
                [
                    'label' => __('Configurar Red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url'   => admin_url('admin.php?page=flavor-platform-network'),
                ]
            );
            return;
        }
        ?>
        <div class="fud-network-overview">
            <div class="fud-network-stats-grid">
                <div class="fud-network-stat">
                    <span class="fud-network-stat__value"><?php echo esc_html($network['nodos_activos'] ?? 0); ?></span>
                    <span class="fud-network-stat__label"><?php esc_html_e('Nodos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="fud-network-stat">
                    <span class="fud-network-stat__value"><?php echo esc_html($network['conexiones_federadas'] ?? 0); ?></span>
                    <span class="fud-network-stat__label"><?php esc_html_e('Federados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <div class="fud-network-stat">
                    <span class="fud-network-stat__value"><?php echo esc_html($network['contenido_compartido'] ?? 0); ?></span>
                    <span class="fud-network-stat__label"><?php esc_html_e('Compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <?php if (!empty($network['alertas_nodos'])): ?>
            <div class="fud-network-alerts">
                <span class="dashicons dashicons-warning"></span>
                <?php printf(
                    esc_html__('%d nodos sin conexion reciente', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
        // Obtener módulos activos desde ambas ubicaciones
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];

        $modulos_activos_legacy = get_option('flavor_active_modules', []);
        if (!empty($modulos_activos_legacy)) {
            $modulos_activos = array_unique(array_merge($modulos_activos, $modulos_activos_legacy));
        }

        $acciones = [
            [
                'id'     => 'configuracion',
                'label'  => __('Configuracion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'   => 'dashicons-admin-settings',
                'url'    => admin_url('admin.php?page=flavor-platform-settings'),
                'color'  => '#2271b1',
            ],
            [
                'id'     => 'modulos',
                'label'  => __('Modulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'   => 'dashicons-screenoptions',
                'url'    => admin_url('admin.php?page=flavor-module-dashboards'),
                'color'  => '#8e44ad',
            ],
        ];

        // Acciones contextuales segun modulos activos
        if (in_array('eventos', $modulos_activos, true)) {
            $acciones[] = [
                'id'    => 'nuevo-evento',
                'label' => __('Nuevo Evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'  => 'dashicons-calendar-alt',
                'url'   => admin_url('admin.php?page=eventos-nuevo'),
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
            'empty_state' => __('Sin actividad reciente', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'footer'      => [
                [
                    'label' => __('Ver toda la actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url'   => admin_url('admin.php?page=flavor-platform-activity-log'),
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
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
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
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
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
            wp_send_json_success(['message' => __('Disposicion guardada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(['message' => __('Error al guardar', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
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
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
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

    /**
     * AJAX: Cargar un widget individual (para lazy loading)
     *
     * @return void
     */
    public function ajax_load_widget(): void {
        check_ajax_referer('fud_dashboard_nonce', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $widget_id = isset($_POST['widget_id']) ? sanitize_key($_POST['widget_id']) : '';

        if (empty($widget_id)) {
            wp_send_json_error(['message' => __('Widget ID requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $widget = $this->registry->get($widget_id);

        if (!$widget) {
            wp_send_json_error(['message' => __('Widget no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        ob_start();
        $widget->render_widget();
        $html = ob_get_clean();

        wp_send_json_success([
            'widget_id' => $widget_id,
            'html'      => $html,
            'data'      => $widget->get_widget_data(),
            'timestamp' => current_time('c'),
        ]);
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
                ? __('Layout guardado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN)
                : __('Error al guardar el layout', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
                <p>' . esc_html__('Debes iniciar sesión para acceder a tu portal.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
                <a href="' . esc_url(wp_login_url($this->get_current_request_url())) . '" class="fud-btn fud-btn-primary">' . esc_html__('Iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>
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
            $plugin_url . 'assets/css/core/design-tokens.css',
            [],
            $version
        );

        // 2. Compatibilidad
        wp_enqueue_style(
            'fl-design-tokens-compat',
            $plugin_url . 'assets/css/core/design-tokens-compat.css',
            ['fl-design-tokens'],
            $version
        );

        // 3. Widgets
        wp_enqueue_style(
            'fl-dashboard-widgets',
            $plugin_url . 'assets/css/layouts/dashboard-widgets.css',
            ['fl-design-tokens-compat'],
            $version
        );

        // 4. Grupos
        wp_enqueue_style(
            'fl-dashboard-groups',
            $plugin_url . 'assets/css/layouts/dashboard-groups.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 5. Estados
        wp_enqueue_style(
            'fl-dashboard-states',
            $plugin_url . 'assets/css/layouts/dashboard-states.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 6. Accesibilidad
        wp_enqueue_style(
            'fl-dashboard-a11y',
            $plugin_url . 'assets/css/layouts/dashboard-a11y.css',
            ['fl-dashboard-widgets'],
            $version
        );

        // 7. Responsive
        wp_enqueue_style(
            'fl-dashboard-responsive',
            $plugin_url . 'assets/css/layouts/dashboard-responsive.css',
            ['fl-dashboard-groups'],
            $version
        );

        // 8. Breadcrumbs
        wp_enqueue_style(
            'fl-breadcrumbs',
            $plugin_url . 'assets/css/components/breadcrumbs.css',
            ['fl-design-tokens'],
            $version
        );

        // CSS Unificado principal
        wp_enqueue_style(
            'flavor-unified-dashboard',
            $plugin_url . 'assets/css/layouts/unified-dashboard.css',
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
                'loading'        => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error'          => __('Error al cargar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'saved'          => __('Guardado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'dragStart'      => __('Arrastrando widget', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'dragEnd'        => __('Widget soltado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'orderSaved'     => __('Orden guardado', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
        $ecosystem_nodes = $this->build_frontend_ecosystem_nodes($widgets);
        $featured_ecosystem_nodes = array_values(array_filter($ecosystem_nodes, static function ($node) {
            return (int) ($node['satellite_count'] ?? 0) >= 2;
        }));
        if (empty($featured_ecosystem_nodes)) {
            $featured_ecosystem_nodes = $ecosystem_nodes;
        }
        $ecosystem_widget_ids = [];
        foreach ($ecosystem_nodes as $ecosystem_node) {
            foreach ((array) ($ecosystem_node['widgets'] ?? []) as $node_widget) {
                $ecosystem_widget_ids[sanitize_key(str_replace('-', '_', (string) ($node_widget['id'] ?? '')))] = true;
            }
        }
        $remaining_widgets = array_values(array_filter($widgets, function ($widget) use ($ecosystem_widget_ids) {
            $widget_key = sanitize_key(str_replace('-', '_', (string) ($widget['id'] ?? '')));
            return !isset($ecosystem_widget_ids[$widget_key]);
        }));
        $social_panel = $this->get_frontend_social_panel_data($user_id);
        $widgets_agrupados = $this->agrupar_widgets_por_categoria($widgets);
        $categorias_disponibles = $this->obtener_categorias_con_conteo($widgets);
        $remaining_widgets_agrupados = $this->agrupar_widgets_por_categoria($remaining_widgets);
        $remaining_categorias_disponibles = $this->obtener_categorias_con_conteo($remaining_widgets);
        $portal_notifications_markup = '';
        $portal_actions_markup = '';

        if (class_exists('Flavor_Portal_Shortcodes')) {
            $portal_shortcodes = Flavor_Portal_Shortcodes::get_instance();
            if (method_exists($portal_shortcodes, 'render_shared_notifications_bar')) {
                $portal_notifications_markup = (string) $portal_shortcodes->render_shared_notifications_bar();
            }
            if (method_exists($portal_shortcodes, 'render_shared_upcoming_actions')) {
                $portal_actions_markup = (string) $portal_shortcodes->render_shared_upcoming_actions();
            }
        }
        ?>
        <div class="fud-frontend-dashboard fl-dashboard-container" data-fl-dashboard>
            <!-- Header del Dashboard -->
            <div class="fud-header fl-dashboard-header">
                <div class="fl-dashboard-header__info">
                    <h2 class="fl-dashboard-header__title"><?php esc_html_e('Mi Portal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p class="fud-welcome fl-dashboard-header__welcome">
                        <?php printf(esc_html__('Hola, %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html(wp_get_current_user()->display_name)); ?>
                    </p>
                </div>
                <div class="fl-dashboard-header__actions">
                    <button type="button" class="fl-btn fl-btn--ghost fl-btn--icon" id="fl-refresh-dashboard" aria-label="<?php esc_attr_e('Actualizar dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            </div>

            <?php if ($portal_notifications_markup !== '' || $portal_actions_markup !== '') : ?>
            <section class="fud-priority-panels" aria-labelledby="fud-priority-panels-title">
                <div class="fud-priority-panels__header">
                    <h2 id="fud-priority-panels-title" class="fud-priority-panels__title"><?php esc_html_e('Atención y próximos pasos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p class="fud-priority-panels__description"><?php esc_html_e('Avisos, notificaciones y acciones cercanas que conviene revisar antes de entrar al detalle del nodo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
                <div class="fud-priority-panels__grid">
                    <?php if ($portal_notifications_markup !== '') : ?>
                    <article class="fud-priority-panel">
                        <div class="fud-priority-panel__head">
                            <h3 class="fud-priority-panel__title"><?php esc_html_e('Señales del nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <p class="fud-priority-panel__subtitle"><?php esc_html_e('Avisos, anuncios, notificaciones y alertas relevantes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                        <?php echo $portal_notifications_markup; ?>
                    </article>
                    <?php endif; ?>
                    <?php if ($portal_actions_markup !== '') : ?>
                    <article class="fud-priority-panel">
                        <div class="fud-priority-panel__head">
                            <h3 class="fud-priority-panel__title"><?php esc_html_e('Qué hacer ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <p class="fud-priority-panel__subtitle"><?php esc_html_e('Eventos, reservas, decisiones y tareas cercanas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                        <?php echo $portal_actions_markup; ?>
                    </article>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php
            // Panel de Impacto Regenerativo (filosofia Gailu)
            $this->render_gailu_impact_panel();
            ?>

            <?php if (!empty($featured_ecosystem_nodes)) : ?>
            <section class="fud-ecosystem-hierarchy" aria-labelledby="fud-frontend-ecosystem-title">
                <div class="fud-ecosystem-hierarchy__header">
                    <h2 id="fud-frontend-ecosystem-title" class="fud-ecosystem-hierarchy__title">
                        <?php esc_html_e('Ecosistemas principales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h2>
                    <p class="fud-ecosystem-hierarchy__description">
                        <?php esc_html_e('Aquí se resumen los ecosistemas con más estructura activa en tu portal.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>
                <div class="fud-ecosystem-hierarchy__grid">
                    <?php foreach ($featured_ecosystem_nodes as $ecosystem_node) : ?>
                    <?php
                    $base_widget = null;
                    $satellite_widgets = [];
                    foreach ($ecosystem_node['widgets'] as $node_widget) {
                        if (($node_widget['id'] ?? '') === ($ecosystem_node['id'] ?? '')) {
                            $base_widget = $node_widget;
                        } else {
                            $satellite_widgets[] = $node_widget;
                        }
                    }
                    $satellite_count = count($satellite_widgets);
                    if ($satellite_count < 1) {
                        continue;
                    }
                    ?>
                    <article class="fud-ecosystem-card">
                        <div class="fud-ecosystem-card__head">
                            <div>
                                <h3 class="fud-ecosystem-card__name"><?php echo esc_html($ecosystem_node['name']); ?></h3>
                                <span class="fud-ecosystem-card__role"><?php echo esc_html($ecosystem_node['role_label']); ?></span>
                            </div>
                            <span class="fud-ecosystem-card__count"><?php echo esc_html($satellite_count); ?></span>
                        </div>
                        <p class="fud-ecosystem-card__summary"><?php echo esc_html($this->get_frontend_ecosystem_summary($ecosystem_node)); ?></p>
                        <?php if ($base_widget) : ?>
                        <div class="fud-ecosystem-card__block">
                            <div class="fud-ecosystem-card__label"><?php esc_html_e('Base activa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                            <div class="fud-ecosystem-card__tags">
                                <span class="fud-ecosystem-card__tag fud-ecosystem-card__tag--base"><?php echo esc_html($base_widget['title']); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($satellite_widgets)) : ?>
                        <div class="fud-ecosystem-card__block">
                            <div class="fud-ecosystem-card__label"><?php esc_html_e('Satélites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                            <div class="fud-ecosystem-card__tags">
                                <?php foreach ($satellite_widgets as $satellite_widget) : ?>
                                <span class="fud-ecosystem-card__tag"><?php echo esc_html($satellite_widget['title'] ?? ''); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($ecosystem_node['transversals'])) : ?>
                        <div class="fud-ecosystem-card__block">
                            <div class="fud-ecosystem-card__label"><?php esc_html_e('Capas transversales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                            <div class="fud-ecosystem-card__tags">
                                <?php foreach ($ecosystem_node['transversals'] as $transversal) : ?>
                                <span class="fud-ecosystem-card__tag <?php echo !empty($transversal['is_active']) ? 'is-active' : 'is-suggested'; ?>">
                                    <?php echo esc_html($transversal['name']); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if (!empty($social_panel['feed']) || !empty($social_panel['community_nodes']) || !empty($social_panel['groups'])) : ?>
            <section class="fud-social-panel" aria-labelledby="fud-social-panel-title">
                <div class="fud-social-panel__header">
                    <h2 id="fud-social-panel-title" class="fud-social-panel__title">
                        <?php esc_html_e('Pulso social del nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h2>
                    <p class="fud-social-panel__description">
                        <?php esc_html_e('Últimos posts, nodos activos y grupos de conversación enlazados a tu red.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>

                <div class="fud-social-panel__grid">
                    <article class="fud-social-panel__card">
                        <div class="fud-social-panel__card-head">
                            <h3 class="fud-social-panel__card-title"><?php esc_html_e('Últimos posts', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <a href="<?php echo esc_url(home_url('/mi-portal/mi-red/')); ?>" class="fud-social-panel__link"><?php esc_html_e('Abrir red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                        </div>
                        <?php if (!empty($social_panel['feed'])) : ?>
                            <div class="fl-item-list">
                                <?php foreach ($social_panel['feed'] as $item) : ?>
                                    <a href="<?php echo esc_url($item['url'] ?? '#'); ?>" class="fl-item-list__link">
                                        <span class="fl-item-list__icon"><?php echo esc_html($item['tipo_info']['icon'] ?? '📝'); ?></span>
                                        <span class="fl-item-list__content">
                                            <span class="fl-item-list__title"><?php echo esc_html($item['title']); ?></span>
                                            <span class="fl-item-list__meta"><?php echo esc_html($item['meta']); ?></span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <p class="fud-social-panel__empty"><?php esc_html_e('Todavía no hay publicaciones recientes en tu red.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <?php endif; ?>
                    </article>

                    <article class="fud-social-panel__card fud-social-panel__card--wide">
                        <div class="fud-social-panel__card-head">
                            <h3 class="fud-social-panel__card-title"><?php esc_html_e('Nodos y grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/')); ?>" class="fud-social-panel__link"><?php esc_html_e('Ver espacios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                        </div>
                        <?php if (!empty($social_panel['community_nodes'])) : ?>
                            <?php
                            $social_node_counts = [
                                'all' => count($social_panel['community_nodes']),
                                'comunidad' => 0,
                                'colectivo' => 0,
                                'energia_comunidad' => 0,
                                'grupo_consumo' => 0,
                                'evento' => 0,
                                'unread' => 0,
                            ];

                            foreach ($social_panel['community_nodes'] as $social_node) {
                                $social_type = sanitize_key((string) ($social_node['entity_type'] ?? ''));
                                if (isset($social_node_counts[$social_type])) {
                                    $social_node_counts[$social_type]++;
                                }
                                if (absint($social_node['unread_count'] ?? 0) > 0) {
                                    $social_node_counts['unread']++;
                                }
                            }
                            ?>
                            <div class="fud-social-panel__filters" role="toolbar" aria-label="<?php esc_attr_e('Filtrar nodos sociales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <button type="button" class="fud-social-panel__filter is-active" data-node-filter="all" aria-pressed="true"><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?><span class="fud-social-panel__filter-count"><?php echo esc_html($social_node_counts['all']); ?></span></button>
                                <button type="button" class="fud-social-panel__filter" data-node-filter="unread" aria-pressed="false"><?php esc_html_e('Con no leídos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?><span class="fud-social-panel__filter-count"><?php echo esc_html($social_node_counts['unread']); ?></span></button>
                                <button type="button" class="fud-social-panel__filter" data-node-filter="comunidad" aria-pressed="false"><?php esc_html_e('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?><span class="fud-social-panel__filter-count"><?php echo esc_html($social_node_counts['comunidad']); ?></span></button>
                                <button type="button" class="fud-social-panel__filter" data-node-filter="colectivo" aria-pressed="false"><?php esc_html_e('Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?><span class="fud-social-panel__filter-count"><?php echo esc_html($social_node_counts['colectivo']); ?></span></button>
                                <button type="button" class="fud-social-panel__filter" data-node-filter="energia_comunidad" aria-pressed="false"><?php esc_html_e('Energía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?><span class="fud-social-panel__filter-count"><?php echo esc_html($social_node_counts['energia_comunidad']); ?></span></button>
                                <button type="button" class="fud-social-panel__filter" data-node-filter="grupo_consumo" aria-pressed="false"><?php esc_html_e('Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?><span class="fud-social-panel__filter-count"><?php echo esc_html($social_node_counts['grupo_consumo']); ?></span></button>
                                <button type="button" class="fud-social-panel__filter" data-node-filter="evento" aria-pressed="false"><?php esc_html_e('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?><span class="fud-social-panel__filter-count"><?php echo esc_html($social_node_counts['evento']); ?></span></button>
                            </div>
                            <div class="fud-social-panel__sort" role="toolbar" aria-label="<?php esc_attr_e('Ordenar nodos sociales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <span class="fud-social-panel__sort-label"><?php esc_html_e('Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <button type="button" class="fud-social-panel__sort-btn is-active" data-node-sort="recent" aria-pressed="true"><?php esc_html_e('Más recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                                <button type="button" class="fud-social-panel__sort-btn" data-node-sort="unread" aria-pressed="false"><?php esc_html_e('Más no leídos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                                <button type="button" class="fud-social-panel__sort-btn" data-node-sort="active" aria-pressed="false"><?php esc_html_e('Más activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                            </div>
                            <div class="fud-social-tree" data-social-tree>
                                <?php foreach ($social_panel['community_nodes'] as $community) : ?>
                                    <?php $community_type = sanitize_key((string) ($community['entity_type'] ?? 'comunidad')); ?>
                                    <div class="fud-social-tree__node fud-social-tree__node--<?php echo esc_attr($community_type); ?>" data-node-type="<?php echo esc_attr($community_type); ?>" data-node-unread="<?php echo esc_attr(absint($community['unread_count'] ?? 0)); ?>" data-node-activity="<?php echo esc_attr(absint($community['last_activity_ts'] ?? 0)); ?>" data-node-groups="<?php echo esc_attr(absint($community['group_count'] ?? 0)); ?>">
                                        <a href="<?php echo esc_url($community['url']); ?>" class="fud-social-tree__node-link">
                                            <span class="fud-social-tree__node-icon fud-social-tree__node-icon--<?php echo esc_attr($community_type); ?>">
                                                <?php echo esc_html($community['icon'] ?? '👥'); ?>
                                            </span>
                                            <span class="fud-social-tree__node-content">
                                                <span class="fud-social-tree__node-type fud-social-tree__node-type--<?php echo esc_attr($community_type); ?>">
                                                    <?php echo esc_html($community['meta']); ?>
                                                </span>
                                                <span class="fud-social-tree__node-title"><?php echo esc_html($community['title']); ?></span>
                                                <?php if (!empty($community['summary'])) : ?>
                                                    <span class="fud-social-tree__node-summary"><?php echo esc_html($community['summary']); ?></span>
                                                <?php endif; ?>
                                            </span>
                                            <?php if (!empty($community['unread_badge'])) : ?>
                                                <span class="fud-social-tree__node-badge"><?php echo esc_html($community['unread_badge']); ?></span>
                                            <?php endif; ?>
                                            <span class="fud-social-tree__node-cta"><?php echo esc_html($community['cta_label'] ?? __('Abrir nodo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                                        </a>
                                        <?php if (!empty($community['latest_post'])) : ?>
                                            <a href="<?php echo esc_url($community['latest_post']['url'] ?? '#'); ?>" class="fud-social-tree__node-post-link">
                                                <span class="fud-social-tree__node-post-label"><?php esc_html_e('Último post', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                                <span class="fud-social-tree__node-post-title"><?php echo esc_html($community['latest_post']['title'] ?? ''); ?></span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($community['groups'])) : ?>
                                            <div class="fud-social-tree__branches">
                                                <?php foreach ($community['groups'] as $group) : ?>
                                                    <a href="<?php echo esc_url($group['url']); ?>" class="fud-social-tree__branch">
                                                        <span class="fud-social-tree__branch-icon">💬</span>
                                                        <span class="fud-social-tree__branch-content">
                                                            <span class="fud-social-tree__branch-title"><?php echo esc_html($group['title']); ?></span>
                                                            <span class="fud-social-tree__branch-meta"><?php echo esc_html($group['meta']); ?></span>
                                                            <?php if (!empty($group['activity_preview'])) : ?>
                                                                <span class="fud-social-tree__branch-preview"><?php echo esc_html($group['activity_preview']); ?></span>
                                                            <?php endif; ?>
                                                        </span>
                                                        <?php if (!empty($group['badge'])) : ?>
                                                            <span class="fud-social-tree__branch-badge"><?php echo esc_html($group['badge']); ?></span>
                                                        <?php endif; ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <p class="fud-social-panel__empty"><?php esc_html_e('No tienes comunidades activas en este nodo todavía.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <?php endif; ?>
                    </article>

                    <article class="fud-social-panel__card">
                        <div class="fud-social-panel__card-head">
                            <h3 class="fud-social-panel__card-title"><?php esc_html_e('Conversaciones abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <a href="<?php echo esc_url(home_url('/mi-portal/chat-grupos/')); ?>" class="fud-social-panel__link"><?php esc_html_e('Abrir grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                        </div>
                        <?php if (!empty($social_panel['groups'])) : ?>
                            <div class="fl-item-list">
                                <?php foreach ($social_panel['groups'] as $group) : ?>
                                    <a href="<?php echo esc_url($group['url']); ?>" class="fl-item-list__link">
                                        <span class="fl-item-list__icon">💬</span>
                                        <span class="fl-item-list__content">
                                            <span class="fl-item-list__title"><?php echo esc_html($group['title']); ?></span>
                                            <span class="fl-item-list__meta"><?php echo esc_html($group['meta']); ?></span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <p class="fud-social-panel__empty"><?php esc_html_e('Todavía no participas en grupos de conversación activos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <?php endif; ?>
                    </article>
                </div>
            </section>
            <?php endif; ?>

            <!-- Filtros por Categoría -->
            <?php if (empty($ecosystem_nodes) && !empty($categorias_disponibles) && count($categorias_disponibles) > 1): ?>
            <nav class="fl-category-filters" role="navigation" aria-label="<?php esc_attr_e('Filtrar por categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <button type="button" class="fl-category-filter fl-category-filter--active" data-category="all" aria-pressed="true">
                    <span class="fl-category-filter__icon dashicons dashicons-screenoptions"></span>
                    <span class="fl-category-filter__label"><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
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

            <!-- Widgets Agrupados -->
            <?php if (empty($widgets)): ?>
                <div class="fl-empty-state">
                    <span class="fl-empty-state__icon dashicons dashicons-screenoptions"></span>
                    <p class="fl-empty-state__message"><?php esc_html_e('No hay módulos activos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p class="fl-empty-state__hint"><?php esc_html_e('Contacta con el administrador para activar módulos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php elseif (!empty($ecosystem_nodes)): ?>
                <section class="fud-coordinated-ecosystems" aria-labelledby="fud-coordinated-ecosystems-title">
                    <div class="fud-coordinated-ecosystems__header">
                        <h2 id="fud-coordinated-ecosystems-title" class="fud-coordinated-ecosystems__title"><?php esc_html_e('Ecosistemas coordinados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        <p class="fud-coordinated-ecosystems__description"><?php esc_html_e('Aquí ves el detalle operativo de cada ecosistema: base activa, satélites y capas transversales.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                <div class="fl-widget-groups" data-fl-widget-groups>
                    <?php foreach ($ecosystem_nodes as $ecosystem_node): ?>
                    <?php
                    $base_widget = null;
                    $satellite_widgets = [];
                    foreach ($ecosystem_node['widgets'] as $node_widget) {
                        if (($node_widget['id'] ?? '') === ($ecosystem_node['id'] ?? '')) {
                            $base_widget = $node_widget;
                        } else {
                            $satellite_widgets[] = $node_widget;
                        }
                    }
                    $satellite_count = count($satellite_widgets);
                    if ($satellite_count < 1) {
                        continue;
                    }
                    $base_widget_url = $this->get_frontend_widget_url((array) $base_widget, (string) ($ecosystem_node['id'] ?? ''));
                    ?>
                    <section class="fl-widget-group fud-widget-group fud-widget-group--ecosystem" data-ecosystem="<?php echo esc_attr($ecosystem_node['id']); ?>" aria-labelledby="fl-group-<?php echo esc_attr($ecosystem_node['id']); ?>">
                        <header class="fl-widget-group__header">
                            <div class="fud-ecosystem-group__intro">
                                <div class="fud-ecosystem-group__title-row">
                                    <h3 id="fl-group-<?php echo esc_attr($ecosystem_node['id']); ?>" class="fl-widget-group__title">
                                        <?php echo esc_html($ecosystem_node['name']); ?>
                                    </h3>
                                    <span class="fud-ecosystem-card__role"><?php echo esc_html($ecosystem_node['role_label']); ?></span>
                                </div>
                                <p class="fud-ecosystem-group__summary">
                                    <?php echo esc_html($this->get_frontend_ecosystem_summary($ecosystem_node)); ?>
                                </p>
                                <?php if ($base_widget) : ?>
                                <a href="<?php echo esc_url($base_widget_url); ?>" class="fud-ecosystem-group__base-inline">
                                    <span class="fud-ecosystem-group__base-inline-label"><?php esc_html_e('Base activa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                    <span class="fud-ecosystem-group__base-inline-title"><?php echo esc_html($base_widget['title'] ?? ''); ?></span>
                                    <span class="fud-ecosystem-group__base-inline-cta"><?php esc_html_e('Abrir ecosistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($satellite_widgets)) : ?>
                                <div class="fud-ecosystem-group__label"><?php esc_html_e('Satélites operativos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                <div class="fud-ecosystem-group__tags">
                                    <?php foreach ($satellite_widgets as $satellite_widget) : ?>
                                    <span class="fud-ecosystem-card__tag"><?php echo esc_html($satellite_widget['title'] ?? ''); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($ecosystem_node['transversals'])) : ?>
                                <div class="fud-ecosystem-group__label"><?php esc_html_e('Capas transversales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                <div class="fud-ecosystem-group__tags">
                                    <?php foreach ($ecosystem_node['transversals'] as $transversal) : ?>
                                    <span class="fud-ecosystem-card__tag <?php echo !empty($transversal['is_active']) ? 'is-active' : 'is-suggested'; ?>">
                                        <?php echo esc_html($transversal['name']); ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="fl-widget-group__toggle" aria-expanded="true" aria-controls="fl-group-content-<?php echo esc_attr($ecosystem_node['id']); ?>">
                                <span class="fl-widget-group__count"><?php echo esc_html($satellite_count); ?></span>
                                <span class="fl-widget-group__chevron dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                        </header>
                        <div id="fl-group-content-<?php echo esc_attr($ecosystem_node['id']); ?>" class="fl-widget-group__content fud-ecosystem-group__content">
                            <?php if (!empty($satellite_widgets)) : ?>
                            <div class="fud-widgets-grid">
                                <?php foreach ($satellite_widgets as $widget): ?>
                                    <?php $this->render_frontend_widget_card($widget, 'ecosystem'); ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endforeach; ?>
                </div>
                </section>
                <?php if (!empty($remaining_widgets_agrupados)) : ?>
                <section class="fud-secondary-widget-groups" aria-labelledby="fud-secondary-widget-groups-title">
                    <div class="fud-secondary-widget-groups__header">
                        <h2 id="fud-secondary-widget-groups-title" class="fud-secondary-widget-groups__title"><?php esc_html_e('Otros espacios activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                        <p class="fud-secondary-widget-groups__description"><?php esc_html_e('Módulos activos que no forman todavía un ecosistema jerárquico completo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                    <?php if (count($remaining_categorias_disponibles) > 1) : ?>
                    <nav class="fl-category-filters" role="navigation" aria-label="<?php esc_attr_e('Filtrar otros espacios por categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <button type="button" class="fl-category-filter fl-category-filter--active" data-category="all" aria-pressed="true">
                            <span class="fl-category-filter__icon dashicons dashicons-screenoptions"></span>
                            <span class="fl-category-filter__label"><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span class="fl-category-filter__count"><?php echo count($remaining_widgets); ?></span>
                        </button>
                        <?php foreach ($remaining_categorias_disponibles as $categoria_id => $categoria_info): ?>
                        <button type="button" class="fl-category-filter" data-category="<?php echo esc_attr($categoria_id); ?>" aria-pressed="false">
                            <span class="fl-category-filter__icon dashicons <?php echo esc_attr($categoria_info['icono']); ?>"></span>
                            <span class="fl-category-filter__label"><?php echo esc_html($categoria_info['nombre']); ?></span>
                            <span class="fl-category-filter__count"><?php echo intval($categoria_info['cantidad']); ?></span>
                        </button>
                        <?php endforeach; ?>
                    </nav>
                    <?php endif; ?>
                    <div class="fl-widget-groups" data-fl-widget-groups>
                        <?php foreach ($remaining_widgets_agrupados as $categoria_id => $grupo): ?>
                        <section class="fl-widget-group" data-category="<?php echo esc_attr($categoria_id); ?>" aria-labelledby="fl-group-remaining-<?php echo esc_attr($categoria_id); ?>">
                            <header class="fl-widget-group__header">
                                <button type="button" class="fl-widget-group__toggle" aria-expanded="true" aria-controls="fl-group-content-remaining-<?php echo esc_attr($categoria_id); ?>">
                                    <span class="fl-widget-group__icon dashicons <?php echo esc_attr($grupo['icono']); ?>"></span>
                                    <h3 id="fl-group-remaining-<?php echo esc_attr($categoria_id); ?>" class="fl-widget-group__title">
                                        <?php echo esc_html($grupo['nombre']); ?>
                                    </h3>
                                    <span class="fl-widget-group__count"><?php echo count($grupo['widgets']); ?></span>
                                    <span class="fl-widget-group__chevron dashicons dashicons-arrow-down-alt2"></span>
                                </button>
                            </header>
                            <div id="fl-group-content-remaining-<?php echo esc_attr($categoria_id); ?>" class="fl-widget-group__content fud-widgets-grid">
                                <?php foreach ($grupo['widgets'] as $widget): ?>
                                    <?php $this->render_frontend_widget_card($widget, $categoria_id); ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
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
                                <?php $this->render_frontend_widget_card($widget, $categoria_id); ?>
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

            // Filtros del panel social
            const socialFilters = document.querySelectorAll('.fud-social-panel__filter');
            const socialNodes = document.querySelectorAll('.fud-social-tree__node[data-node-type]');
            const socialTree = document.querySelector('[data-social-tree]');
            const socialSortButtons = document.querySelectorAll('.fud-social-panel__sort-btn');

            socialFilters.forEach(function(filter) {
                filter.addEventListener('click', function() {
                    const nodeType = this.dataset.nodeFilter || 'all';

                    socialFilters.forEach(function(item) {
                        item.classList.remove('is-active');
                        item.setAttribute('aria-pressed', 'false');
                    });

                    this.classList.add('is-active');
                    this.setAttribute('aria-pressed', 'true');

                    socialNodes.forEach(function(node) {
                        const hasUnread = parseInt(node.dataset.nodeUnread || '0', 10) > 0;
                        if (nodeType === 'all' || (nodeType === 'unread' && hasUnread) || node.dataset.nodeType === nodeType) {
                            node.style.display = '';
                        } else {
                            node.style.display = 'none';
                        }
                    });
                });
            });

            socialSortButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const sortMode = this.dataset.nodeSort || 'recent';
                    if (!socialTree || !socialNodes.length) {
                        return;
                    }

                    socialSortButtons.forEach(function(item) {
                        item.classList.remove('is-active');
                        item.setAttribute('aria-pressed', 'false');
                    });

                    this.classList.add('is-active');
                    this.setAttribute('aria-pressed', 'true');

                    const nodes = Array.from(socialTree.querySelectorAll('.fud-social-tree__node[data-node-type]'));
                    nodes.sort(function(a, b) {
                        const aActivity = parseInt(a.dataset.nodeActivity || '0', 10);
                        const bActivity = parseInt(b.dataset.nodeActivity || '0', 10);
                        const aUnread = parseInt(a.dataset.nodeUnread || '0', 10);
                        const bUnread = parseInt(b.dataset.nodeUnread || '0', 10);
                        const aGroups = parseInt(a.dataset.nodeGroups || '0', 10);
                        const bGroups = parseInt(b.dataset.nodeGroups || '0', 10);

                        if (sortMode === 'unread') {
                            if (bUnread !== aUnread) {
                                return bUnread - aUnread;
                            }
                            return bActivity - aActivity;
                        }

                        if (sortMode === 'active') {
                            if (bGroups !== aGroups) {
                                return bGroups - aGroups;
                            }
                            return bActivity - aActivity;
                        }

                        if (bActivity !== aActivity) {
                            return bActivity - aActivity;
                        }
                        return bUnread - aUnread;
                    });

                    nodes.forEach(function(node) {
                        socialTree.appendChild(node);
                    });
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
                'nombre' => __('Gestión', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-clipboard',
            ],
            'comunidad' => [
                'nombre' => __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-groups',
            ],
            'economia' => [
                'nombre' => __('Economía', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-cart',
            ],
            'sostenibilidad' => [
                'nombre' => __('Sostenibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-palmtree',
            ],
            'movilidad' => [
                'nombre' => __('Movilidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-car',
            ],
            'recursos' => [
                'nombre' => __('Recursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-archive',
            ],
            'otros' => [
                'nombre' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
                'semantics' => $this->get_widget_semantics_frontend($modulo['id']),
                'severity' => $this->get_widget_native_severity_frontend($modulo['id']),
            ];
        }

        return $widgets;
    }

    /**
     * Renderiza la tarjeta simplificada de widget para el dashboard frontend.
     *
     * @param array $widget
     * @param string $category_id
     * @return void
     */
    private function render_frontend_widget_card(array $widget, string $category_id = ''): void {
        $widget_semantics = (array) ($widget['semantics'] ?? []);
        $cta_label = __('Ver más', FLAVOR_PLATFORM_TEXT_DOMAIN);

        if ($category_id !== 'ecosystem' && ($widget_semantics['kind_slug'] ?? '') === 'base') {
            $widget_semantics['kind'] = __('Gestionar', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $widget_semantics['kind_slug'] = 'standalone';
            $cta_label = __('Abrir espacio', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }

        ?>
        <article class="fud-widget fl-widget fl-widget--standard" data-module="<?php echo esc_attr($widget['id']); ?>" data-category="<?php echo esc_attr($category_id); ?>" data-severity="<?php echo esc_attr($widget['severity']['slug'] ?? ''); ?>" role="region" aria-labelledby="widget-title-<?php echo esc_attr($widget['id']); ?>">
            <header class="fud-widget-header fl-widget__header">
                <div class="fud-widget__title-wrap fl-widget__title-wrap">
                    <span class="fud-widget__icon fl-widget__icon" aria-hidden="true">
                        <span class="dashicons <?php echo esc_attr($widget['icon'] ?? 'dashicons-admin-generic'); ?>"></span>
                    </span>
                    <div class="fud-widget__title-block fl-widget__title-block">
                        <?php if (!empty($widget_semantics['kind']) || !empty($widget_semantics['context']) || !empty($widget['severity']['label'])) : ?>
                            <div class="fud-widget__meta fl-widget__meta">
                                <?php if (!empty($widget_semantics['kind'])) : ?>
                                    <span class="fud-widget__kind fud-widget__kind--<?php echo esc_attr($widget_semantics['kind_slug'] ?? 'vertical'); ?>">
                                        <?php echo esc_html($widget_semantics['kind']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($widget_semantics['context'])) : ?>
                                    <span class="fud-widget__context"><?php echo esc_html($widget_semantics['context']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($widget['severity']['label'])) : ?>
                                    <span class="fud-widget__severity fud-widget__severity--<?php echo esc_attr($widget['severity']['slug'] ?? ''); ?>" <?php if (!empty($widget['severity']['reason'])) : ?>title="<?php echo esc_attr($widget['severity']['reason']); ?>"<?php endif; ?>>
                                        <?php echo esc_html($widget['severity']['label']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <h4 id="widget-title-<?php echo esc_attr($widget['id']); ?>" class="fl-widget__title"><?php echo esc_html($widget['title']); ?></h4>
                        <?php if (!empty($widget_semantics['summary'])) : ?>
                            <p class="fud-widget__summary"><?php echo esc_html($widget_semantics['summary']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            <div class="fud-widget-stats fl-widget__body">
                <?php if (!empty($widget['stats'])): ?>
                    <div class="fl-widget-stats">
                    <?php foreach ($widget['stats'] as $stat): ?>
                        <?php if (is_array($stat) && isset($stat['value'], $stat['label'])): ?>
                        <div class="fl-stat-item">
                            <span class="fl-stat-value"><?php echo esc_html($stat['value']); ?></span>
                            <span class="fl-stat-label"><?php echo esc_html($stat['label']); ?></span>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <footer class="fud-widget-actions fl-widget__footer">
                <a href="<?php echo esc_url($this->get_frontend_widget_url($widget)); ?>" class="fl-btn fl-btn--primary fl-btn--sm">
                    <?php echo esc_html($cta_label); ?>
                    <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                </a>
            </footer>
        </article>
        <?php
    }

    /**
     * Obtiene semántica corta del widget para frontend.
     *
     * @param string $module_id
     * @return array<string,string>
     */
    private function get_widget_semantics_frontend(string $module_id): array {
        $module_key = sanitize_key(str_replace('-', '_', $module_id));

        if ($module_key === '' || !class_exists('Flavor_Chat_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $registered_modules = $loader ? $loader->get_registered_modules() : [];
        $module_data = $registered_modules[$module_key] ?? null;

        if (!is_array($module_data)) {
            return [];
        }

        $ecosystem = is_array($module_data['ecosystem'] ?? null) ? $module_data['ecosystem'] : [];
        $dashboard = is_array($module_data['dashboard'] ?? null) ? $module_data['dashboard'] : [];
        $role = (string) ($ecosystem['module_role'] ?? 'vertical');
        $display_role = (string) ($ecosystem['display_role'] ?? $role);

        $kind_map = [
            'base' => __('Coordinar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'vertical' => __('Operar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'transversal' => __('Entender', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'standalone' => __('Gestionar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'base-standalone' => __('Gestionar', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        $context_labels = [
            'comunidad' => __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'gobernanza' => __('Gobernanza', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'participacion' => __('Participación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'transparencia' => __('Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'energia' => __('Energía', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'consumo' => __('Consumo local', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cuidados' => __('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'sostenibilidad' => __('Sostenibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'impacto' => __('Impacto', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'aprendizaje' => __('Aprendizaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'saberes' => __('Saberes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'agenda' => __('Agenda', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eventos' => __('Encuentros', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'socios' => __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'membresia' => __('Membresía', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cuenta' => __('Cuenta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'colectivos' => __('Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'asociacion' => __('Asociación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'coordinacion' => __('Coordinación', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        $contexts = (array) ($dashboard['client_contexts'] ?? []);
        $primary_context = (string) reset($contexts);
        $kind_slug = $display_role !== '' ? $display_role : 'vertical';
        $summary_map = [
            'comunidad' => __('Espacio comunitario con actividad y coordinación compartida.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'gobernanza' => __('Espacio de decisiones, acuerdos y seguimiento colectivo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'participacion' => __('Espacio para propuestas, votaciones y conversación pública.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'transparencia' => __('Espacio para memoria abierta, recursos e información compartida.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'energia' => __('Espacio para seguimiento operativo y balance energético.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'consumo' => __('Espacio para ciclos, pedidos y relación con productores.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cuidados' => __('Espacio para ayuda mutua, apoyo vecinal y cuidados.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'sostenibilidad' => __('Espacio para prácticas regenerativas e impacto local.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'impacto' => __('Espacio para métricas, huella e indicadores compartidos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'aprendizaje' => __('Espacio para cursos, talleres y aprendizaje compartido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'saberes' => __('Espacio para saberes, cultura y transmisión comunitaria.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'agenda' => __('Espacio para agenda, citas y actividad próxima.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eventos' => __('Espacio para encuentros, asistencia y calendario vivo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'socios' => __('Espacio para membresía, vínculo y gestión de personas asociadas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'membresia' => __('Espacio para membresía, vínculo y gestión de personas asociadas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cuenta' => __('Espacio para estado personal, acceso y seguimiento propio.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'colectivos' => __('Espacio para organización, coordinación y trabajo colectivo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'asociacion' => __('Espacio para organización, coordinación y trabajo colectivo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'coordinacion' => __('Espacio para coordinación operativa y seguimiento común.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return [
            'kind' => $kind_map[$kind_slug] ?? __('Operar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'kind_slug' => sanitize_html_class($kind_slug),
            'context' => $context_labels[$primary_context] ?? (
                $primary_context !== ''
                    ? ucwords(str_replace('_', ' ', $primary_context))
                    : ''
            ),
            'summary' => $summary_map[$primary_context] ?? '',
        ];
    }

    /**
     * Obtiene severidad nativa del widget si existe.
     *
     * @param string $module_id
     * @return array<string,string>
     */
    private function get_widget_native_severity_frontend(string $module_id): array {
        static $cache = [];

        $module_key = sanitize_key(str_replace('-', '_', $module_id));

        if ($module_key === '') {
            return [];
        }

        if (array_key_exists($module_key, $cache)) {
            return $cache[$module_key];
        }

        if (!class_exists('Flavor_Widget_Registry')) {
            $cache[$module_key] = [];
            return $cache[$module_key];
        }

        $registry = Flavor_Widget_Registry::get_instance();
        if (!$registry || !method_exists($registry, 'initialize_widgets')) {
            $cache[$module_key] = [];
            return $cache[$module_key];
        }

        $registry->initialize_widgets();

        $widget_candidates = array_values(array_unique([
            $module_key,
            str_replace('_', '-', $module_key),
        ]));

        foreach ($widget_candidates as $widget_candidate) {
            $widget = $registry->get_widget($widget_candidate);
            if (!$widget || !method_exists($widget, 'get_widget_config')) {
                continue;
            }

            $config = (array) $widget->get_widget_config();
            $severity_slug = sanitize_key((string) ($config['severity_slug'] ?? ''));

            if ($severity_slug === '') {
                continue;
            }

            $cache[$module_key] = [
                'slug' => $severity_slug,
                'label' => (string) ($config['severity_label'] ?? ''),
                'reason' => (string) ($config['severity_reason'] ?? ''),
            ];

            return $cache[$module_key];
        }

        $cache[$module_key] = [];
        return $cache[$module_key];
    }

    /**
     * Construye nodos ecosistémicos para el dashboard frontend.
     *
     * @param array $widgets
     * @return array
     */
    private function build_frontend_ecosystem_nodes(array $widgets): array {
        if (empty($widgets) || !class_exists('Flavor_Chat_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $registered_modules = $loader ? $loader->get_registered_modules() : [];

        if (empty($registered_modules)) {
            return [];
        }

        $widget_map = [];
        foreach ($widgets as $widget) {
            $widget_map[sanitize_key(str_replace('-', '_', (string) ($widget['id'] ?? '')))] = $widget;
        }

        $nodes = [];

        foreach ($widget_map as $module_key => $widget) {
            $module_data = $registered_modules[$module_key] ?? null;
            $ecosystem = is_array($module_data['ecosystem'] ?? null) ? $module_data['ecosystem'] : [];
            $dashboard = is_array($module_data['dashboard'] ?? null) ? $module_data['dashboard'] : [];
            $role = (string) ($ecosystem['module_role'] ?? 'vertical');
            $depends_on = array_values(array_filter(array_map('sanitize_key', (array) ($ecosystem['depends_on'] ?? []))));
            $parent_module = sanitize_key((string) ($dashboard['parent_module'] ?? ($depends_on[0] ?? '')));

            if ($role === 'base') {
                continue;
            }

            if ($parent_module === '') {
                $parent_module = $this->find_frontend_base_parent_for_module($module_key, $registered_modules);
            }

            if (
                $parent_module === ''
                || !isset($registered_modules[$parent_module])
                || !isset($widget_map[$parent_module])
            ) {
                continue;
            }

            if (!isset($nodes[$parent_module])) {
                $nodes[$parent_module] = $this->build_frontend_ecosystem_node(
                    $parent_module,
                    $registered_modules,
                    $widget_map[$parent_module]
                );
                $nodes[$parent_module]['widgets'][$parent_module] = $widget_map[$parent_module];
            }

            $nodes[$parent_module]['widgets'][$module_key] = $widget;

            if ($role === 'vertical') {
                $nodes[$parent_module]['satellites'][$module_key] = [
                    'id' => str_replace('_', '-', $module_key),
                    'name' => (string) ($widget['title'] ?? $module_data['name'] ?? ucfirst($module_key)),
                ];
            }
        }

        foreach ($nodes as $parent_key => &$node) {
            $active_targets = array_keys($node['widgets']);

            foreach ($registered_modules as $module_key => $module_data) {
                $ecosystem = is_array($module_data['ecosystem'] ?? null) ? $module_data['ecosystem'] : [];

                if (($ecosystem['module_role'] ?? 'vertical') !== 'transversal') {
                    continue;
                }

                $related_targets = array_merge(
                    (array) ($ecosystem['ecosystem_supports_modules'] ?? []),
                    (array) ($ecosystem['ecosystem_measures_modules'] ?? []),
                    (array) ($ecosystem['ecosystem_governs_modules'] ?? []),
                    (array) ($ecosystem['ecosystem_teaches_modules'] ?? [])
                );

                $related_targets = array_values(array_filter(array_map('sanitize_key', $related_targets)));

                if (empty(array_intersect($active_targets, $related_targets)) && !in_array($parent_key, $related_targets, true)) {
                    continue;
                }

                $node['transversals'][$module_key] = [
                    'id' => str_replace('_', '-', $module_key),
                    'name' => (string) ($widget_map[$module_key]['title'] ?? $module_data['name'] ?? ucfirst($module_key)),
                    'is_active' => isset($widget_map[$module_key]),
                ];
            }

            $node['widgets'] = array_values($node['widgets']);
            $node['satellites'] = array_values($node['satellites']);
            $node['transversals'] = array_values($node['transversals']);
            $node['widget_count'] = count($node['widgets']);
            $node['satellite_count'] = count($node['satellites']);
            $node['active_transversal_count'] = count(array_filter($node['transversals'], function ($transversal) {
                return !empty($transversal['is_active']);
            }));
        }
        unset($node);

        $nodes = array_values(array_filter($nodes, function ($node) {
            if (empty($node['widgets']) || empty($node['widgets'][$node['base_widget_key'] ?? ''])) {
                return false;
            }

            return !empty($node['satellite_count']);
        }));

        usort($nodes, function ($a, $b) {
            return ($b['widget_count'] ?? 0) <=> ($a['widget_count'] ?? 0);
        });

        return $nodes;
    }

    /**
     * Construye un nodo base de ecosistema para frontend.
     *
     * @param string $module_key
     * @param array $registered_modules
     * @return array
     */
    private function build_frontend_ecosystem_node(string $module_key, array $registered_modules, array $base_widget = []): array {
        $module_data = $registered_modules[$module_key] ?? [];
        $ecosystem = is_array($module_data['ecosystem'] ?? null) ? $module_data['ecosystem'] : [];
        $dashboard = is_array($module_data['dashboard'] ?? null) ? $module_data['dashboard'] : [];
        $role = (string) ($ecosystem['display_role'] ?? $ecosystem['module_role'] ?? 'base');

        $role_labels = [
            'base' => __('Base', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'base-standalone' => __('Base local', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'vertical' => __('Operativo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'transversal' => __('Transversal', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return [
            'id' => str_replace('_', '-', $module_key),
            'name' => (string) ($base_widget['title'] ?? $module_data['name'] ?? ucfirst($module_key)),
            'role' => $role,
            'role_label' => $role_labels[$role] ?? __('Base', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'contexts' => array_values(array_filter(array_map('sanitize_key', (array) ($dashboard['client_contexts'] ?? [])))),
            'widgets' => [],
            'satellites' => [],
            'transversals' => [],
            'widget_count' => 0,
            'base_widget_key' => $module_key,
        ];
    }

    /**
     * Resuelve la URL de un widget en el dashboard frontend sin caer a anclas vacías.
     *
     * @param array  $widget
     * @param string $fallback_module_id
     * @return string
     */
    private function get_frontend_widget_url(array $widget, string $fallback_module_id = ''): string {
        $more_url = trim((string) ($widget['more_url'] ?? ''));
        if ($more_url !== '' && $more_url !== '#') {
            return $more_url;
        }

        $widget_id = sanitize_title((string) ($widget['id'] ?? ''));
        if ($widget_id === '') {
            $widget_id = sanitize_title($fallback_module_id);
        }

        return home_url('/mi-portal/' . $widget_id . '/');
    }

    /**
     * Devuelve una lectura corta del ecosistema coordinado.
     *
     * @param array $ecosystem_node
     * @return string
     */
    private function get_frontend_ecosystem_summary(array $ecosystem_node): string {
        $context_labels = [
            'comunidad' => __('Coordina vida comunitaria y servicios compartidos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'socios' => __('Organiza membresía, acceso y relación con personas vinculadas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'membresia' => __('Organiza membresía, acceso y relación con personas vinculadas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'colectivos' => __('Da soporte a colectivos, coordinación y espacios de organización.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'asociacion' => __('Da soporte a colectivos, coordinación y espacios de organización.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'energia' => __('Conecta infraestructura, comunidad energética y seguimiento operativo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'consumo' => __('Articula consumo local, ciclos y relación con productores.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cuidados' => __('Sostiene redes de ayuda, cuidados y acompañamiento mutuo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eventos' => __('Conecta agenda, encuentros y participación activa.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'agenda' => __('Conecta agenda, encuentros y participación activa.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        foreach ((array) ($ecosystem_node['contexts'] ?? []) as $context) {
            if (isset($context_labels[$context])) {
                return $context_labels[$context];
            }
        }

        return __('Base activa con servicios operativos y capas de soporte relacionadas.', FLAVOR_PLATFORM_TEXT_DOMAIN);
    }

    /**
     * Resuelve una base ecosistémica declarativa a partir de base_for_modules.
     *
     * @param string $module_key
     * @param array  $registered_modules
     * @return string
     */
    private function find_frontend_base_parent_for_module(string $module_key, array $registered_modules): string {
        foreach ($registered_modules as $candidate_key => $candidate_module) {
            $candidate_ecosystem = is_array($candidate_module['ecosystem'] ?? null) ? $candidate_module['ecosystem'] : [];
            if (($candidate_ecosystem['module_role'] ?? '') !== 'base') {
                continue;
            }

            $base_for_modules = array_values(array_filter(array_map('sanitize_key', (array) ($candidate_ecosystem['base_for_modules'] ?? []))));
            if (in_array($module_key, $base_for_modules, true)) {
                return sanitize_key((string) $candidate_key);
            }
        }

        return '';
    }

    /**
     * Obtiene un panel social compacto para la home del dashboard frontend.
     *
     * @param int $user_id
     * @return array
     */
    private function get_frontend_social_panel_data(int $user_id): array {
        if ($user_id <= 0 || !class_exists('Flavor_Mi_Red_Social')) {
            return [
                'feed' => [],
                'communities' => [],
                'groups' => [],
            ];
        }

        $mi_red = Flavor_Mi_Red_Social::get_instance();

        if (!$mi_red) {
            return [
                'feed' => [],
                'communities' => [],
                'groups' => [],
            ];
        }

        $feed_items = method_exists($mi_red, 'obtener_feed_unificado')
            ? (array) $mi_red->obtener_feed_unificado($user_id, 4, 0, 'todos')
            : [];
        $communities = method_exists($mi_red, 'obtener_comunidades_usuario')
            ? (array) $mi_red->obtener_comunidades_usuario($user_id)
            : [];
        $groups = method_exists($mi_red, 'obtener_grupos_chat')
            ? (array) $mi_red->obtener_grupos_chat($user_id)
            : [];

        $normalized_feed = $this->normalize_frontend_social_feed_items($feed_items);

        return [
            'feed' => $normalized_feed,
            'community_nodes' => $this->normalize_frontend_social_community_nodes($communities, $groups, $normalized_feed),
            'groups' => $this->normalize_frontend_social_groups($groups),
        ];
    }

    /**
     * Normaliza items del feed social para panel compacto.
     *
     * @param array $items
     * @return array
     */
    private function normalize_frontend_social_feed_items(array $items): array {
        $result = [];

        foreach (array_slice($items, 0, 4) as $item) {
            $title = trim((string) ($item['contenido']['titulo'] ?? ''));
            if ($title === '') {
                $title = wp_trim_words(wp_strip_all_tags((string) ($item['contenido']['texto'] ?? '')), 10);
            }

            $author = (string) ($item['autor']['nombre'] ?? __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN));
            $time = (string) ($item['fecha_humana'] ?? '');
            $meta = trim($author . ($time !== '' ? ' · ' . $time : ''));

            $result[] = [
                'title' => $title !== '' ? $title : __('Publicación reciente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'meta' => $meta,
                'url' => (string) ($item['url'] ?? '#'),
                'tipo_info' => (array) ($item['tipo_info'] ?? []),
                'entity_type' => $this->infer_social_feed_entity_type((array) ($item['contexto'] ?? []), (string) ($item['url'] ?? '')),
                'entity_id' => $this->infer_social_feed_entity_id((array) ($item['contexto'] ?? []), (string) ($item['url'] ?? '')),
            ];
        }

        return $result;
    }

    /**
     * Normaliza nodos sociales para panel compacto.
     *
     * @param array $communities
     * @param array $feed_items
     * @return array
     */
    private function normalize_frontend_social_community_nodes(array $communities, array $groups, array $feed_items = []): array {
        $nodes = [];

        foreach (array_slice($communities, 0, 8) as $community) {
            $community = (array) $community;
            $id = absint($community['id'] ?? 0);
            $members = absint($community['miembros_count'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $nodes['comunidad:' . $id] = [
                'id' => $id,
                'entity_key' => 'comunidad:' . $id,
                'entity_type' => 'comunidad',
                'id' => $id,
                'title' => (string) ($community['nombre'] ?? __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN)),
                'meta' => $members > 0
                    ? sprintf(_n('%d miembro', '%d miembros', $members, FLAVOR_PLATFORM_TEXT_DOMAIN), $members)
                    : __('Nodo activo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => home_url('/mi-portal/comunidades/?comunidad_id=' . $id),
                'icon' => $this->get_social_node_icon_by_entity_type('comunidad'),
                'cta_label' => $this->get_social_node_cta_by_entity_type('comunidad'),
                'last_activity_ts' => 0,
                'group_count' => 0,
                'unread_count' => 0,
                'latest_post' => null,
                'groups' => [],
            ];
        }

        foreach ($groups as $group) {
            $group = (array) $group;
            $entity_type = sanitize_key((string) ($group['entidad_tipo'] ?? ''));
            $entity_id = absint($group['entidad_id'] ?? 0);

            if ($entity_type === '' || $entity_id <= 0) {
                $related_community_id = $this->infer_social_group_community_id($group);
                if ($related_community_id > 0) {
                    $entity_type = 'comunidad';
                    $entity_id = $related_community_id;
                }
            }

            if ($entity_type === '' || $entity_id <= 0) {
                continue;
            }

            if (!$this->is_supported_social_node_entity_type($entity_type)) {
                continue;
            }

            $node_key = $entity_type . ':' . $entity_id;

            if (!isset($nodes[$node_key])) {
                $nodes[$node_key] = [
                    'id' => $entity_id,
                    'entity_key' => $node_key,
                    'entity_type' => $entity_type,
                    'title' => $this->get_social_node_title_from_group($group, $entity_type),
                    'meta' => $this->get_social_node_label_by_entity_type($entity_type),
                    'url' => $this->get_social_node_url_by_entity_type($entity_type, $entity_id),
                    'icon' => $this->get_social_node_icon_by_entity_type($entity_type),
                    'cta_label' => $this->get_social_node_cta_by_entity_type($entity_type),
                    'last_activity_ts' => 0,
                    'group_count' => 0,
                    'unread_count' => 0,
                    'latest_post' => null,
                    'groups' => [],
                ];
            }

            $group_activity_ts = absint($group['activity_timestamp'] ?? 0);
            $group_unread_count = absint($group['badge'] ?? 0);
            if ($group_activity_ts > (int) ($nodes[$node_key]['last_activity_ts'] ?? 0)) {
                $nodes[$node_key]['last_activity_ts'] = $group_activity_ts;
            }
            $nodes[$node_key]['group_count'] = (int) ($nodes[$node_key]['group_count'] ?? 0) + 1;
            $nodes[$node_key]['unread_count'] = (int) ($nodes[$node_key]['unread_count'] ?? 0) + $group_unread_count;

            $nodes[$node_key]['groups'][] = [
                'title' => $this->strip_social_group_prefix((string) ($group['title'] ?? __('Grupo', FLAVOR_PLATFORM_TEXT_DOMAIN))),
                'meta' => (string) ($group['meta'] ?? ''),
                'url' => (string) ($group['url'] ?? home_url('/mi-portal/chat-grupos/')),
                'badge' => (string) ($group['badge'] ?? ''),
                'activity_preview' => (string) ($group['activity_preview'] ?? ''),
            ];
        }

        foreach ($feed_items as $feed_item) {
            $feed_item = (array) $feed_item;
            $entity_type = sanitize_key((string) ($feed_item['entity_type'] ?? ''));
            $entity_id = absint($feed_item['entity_id'] ?? 0);

            if ($entity_type === '' || $entity_id <= 0) {
                continue;
            }

            $node_key = $entity_type . ':' . $entity_id;
            if (!isset($nodes[$node_key]) || !empty($nodes[$node_key]['latest_post'])) {
                continue;
            }

            $nodes[$node_key]['latest_post'] = [
                'title' => (string) ($feed_item['title'] ?? __('Publicación reciente', FLAVOR_PLATFORM_TEXT_DOMAIN)),
                'meta' => (string) ($feed_item['meta'] ?? ''),
                'url' => (string) ($feed_item['url'] ?? '#'),
            ];
        }

        $nodes = array_values($nodes);

        foreach ($nodes as &$node) {
            $summary_parts = [];
            $group_count = absint($node['group_count'] ?? 0);
            $unread_count = absint($node['unread_count'] ?? 0);

            if ($group_count > 0) {
                $summary_parts[] = sprintf(_n('%d grupo', '%d grupos', $group_count, FLAVOR_PLATFORM_TEXT_DOMAIN), $group_count);
            }

            if ($unread_count > 0) {
                $summary_parts[] = sprintf(_n('%d no leído', '%d no leídos', $unread_count, FLAVOR_PLATFORM_TEXT_DOMAIN), $unread_count);
            }

            $node['summary'] = !empty($summary_parts)
                ? implode(' · ', $summary_parts)
                : __('Sin grupos activos', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $node['unread_badge'] = $unread_count > 0 ? (string) $unread_count : '';
        }
        unset($node);

        usort($nodes, function ($a, $b) {
            $activity_compare = ((int) ($b['last_activity_ts'] ?? 0)) <=> ((int) ($a['last_activity_ts'] ?? 0));
            if ($activity_compare !== 0) {
                return $activity_compare;
            }

            return count($b['groups'] ?? []) <=> count($a['groups'] ?? []);
        });

        return array_slice($nodes, 0, 8);
    }

    /**
     * Normaliza grupos de chat para panel compacto.
     *
     * @param array $groups
     * @return array
     */
    private function normalize_frontend_social_groups(array $groups): array {
        $result = [];

        foreach (array_slice($groups, 0, 4) as $group) {
            $group = (array) $group;
            $id = absint($group['id'] ?? 0);
            $members = absint($group['miembros'] ?? $group['miembros_count'] ?? 0);
            $last_message = (array) ($group['ultimo_mensaje'] ?? []);
            $last_text = trim((string) ($last_message['texto'] ?? $group['descripcion'] ?? ''));
            $last_author = trim((string) ($last_message['autor'] ?? ''));
            $last_time = trim((string) ($last_message['fecha'] ?? ''));
            $last_timestamp = absint($last_message['timestamp'] ?? 0);

            $meta_parts = [];
            if ($members > 0) {
                $meta_parts[] = sprintf(_n('%d miembro', '%d miembros', $members, FLAVOR_PLATFORM_TEXT_DOMAIN), $members);
            }
            if ($last_text !== '') {
                $meta_parts[] = wp_trim_words($last_text, 8);
            }

            $result[] = [
                'title' => $this->strip_social_group_prefix((string) ($group['nombre'] ?? $group['grupo_nombre'] ?? __('Grupo', FLAVOR_PLATFORM_TEXT_DOMAIN))),
                'meta' => !empty($meta_parts) ? implode(' · ', $meta_parts) : __('Conversación activa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => $id > 0 ? home_url('/mi-portal/chat-grupos/mensajes/?grupo_id=' . $id) : home_url('/mi-portal/chat-grupos/'),
                'badge' => !empty($group['mensajes_no_leidos']) ? (string) absint($group['mensajes_no_leidos']) : '',
                'activity_preview' => $this->build_social_group_activity_preview($last_author, $last_text, $last_time),
                'activity_timestamp' => $last_timestamp ? (int) $last_timestamp : 0,
                'slug' => (string) ($group['slug'] ?? ''),
                'tipo' => (string) ($group['tipo'] ?? ''),
                'entidad_tipo' => sanitize_key((string) ($group['entidad_tipo'] ?? '')),
                'entidad_id' => absint($group['entidad_id'] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Devuelve si un tipo de entidad es soportado como nodo social.
     *
     * @param string $entity_type
     * @return bool
     */
    private function is_supported_social_node_entity_type(string $entity_type): bool {
        return in_array($entity_type, ['comunidad', 'colectivo', 'energia_comunidad', 'grupo_consumo', 'evento'], true);
    }

    /**
     * Etiqueta humana para un nodo social.
     *
     * @param string $entity_type
     * @return string
     */
    private function get_social_node_label_by_entity_type(string $entity_type): string {
        $labels = [
            'comunidad' => __('Comunidad activa', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'colectivo' => __('Colectivo activo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'energia_comunidad' => __('Comunidad energética', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'grupo_consumo' => __('Grupo de consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'evento' => __('Encuentro activo', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $labels[$entity_type] ?? __('Nodo activo', FLAVOR_PLATFORM_TEXT_DOMAIN);
    }

    /**
     * Icono para un nodo social según su entidad.
     *
     * @param string $entity_type
     * @return string
     */
    private function get_social_node_icon_by_entity_type(string $entity_type): string {
        $icons = [
            'comunidad' => '👥',
            'colectivo' => '🕸',
            'energia_comunidad' => '⚡',
            'grupo_consumo' => '🧺',
            'evento' => '📅',
        ];

        return $icons[$entity_type] ?? '👥';
    }

    /**
     * CTA humano por tipo de nodo social.
     *
     * @param string $entity_type
     * @return string
     */
    private function get_social_node_cta_by_entity_type(string $entity_type): string {
        $labels = [
            'comunidad' => __('Abrir comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'colectivo' => __('Abrir colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'energia_comunidad' => __('Abrir energía', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'grupo_consumo' => __('Abrir consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'evento' => __('Abrir evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $labels[$entity_type] ?? __('Abrir nodo', FLAVOR_PLATFORM_TEXT_DOMAIN);
    }

    /**
     * URL principal para un nodo social.
     *
     * @param string $entity_type
     * @param int $entity_id
     * @return string
     */
    private function get_social_node_url_by_entity_type(string $entity_type, int $entity_id): string {
        switch ($entity_type) {
            case 'comunidad':
                return home_url('/mi-portal/comunidades/?comunidad_id=' . $entity_id);
            case 'colectivo':
                return home_url('/mi-portal/colectivos/?colectivo_id=' . $entity_id);
            case 'energia_comunidad':
                return home_url('/mi-portal/energia-comunitaria/?comunidad_id=' . $entity_id);
            case 'grupo_consumo':
                return home_url('/mi-portal/grupos-consumo/');
            case 'evento':
                return home_url('/mi-portal/eventos/');
            default:
                return home_url('/mi-portal/chat-grupos/');
        }
    }

    /**
     * Construye un título de nodo social a partir del grupo cuando no hay comunidad cargada.
     *
     * @param array $group
     * @param string $entity_type
     * @return string
     */
    private function get_social_node_title_from_group(array $group, string $entity_type): string {
        $title = $this->strip_social_group_prefix((string) ($group['title'] ?? $group['nombre'] ?? ''));

        if ($title !== '') {
            return $title;
        }

        $fallbacks = [
            'comunidad' => __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'colectivo' => __('Colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'energia_comunidad' => __('Comunidad energética', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'grupo_consumo' => __('Grupo de consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'evento' => __('Evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $fallbacks[$entity_type] ?? __('Nodo', FLAVOR_PLATFORM_TEXT_DOMAIN);
    }

    /**
     * Limpia prefijos redundantes del nombre de grupo.
     *
     * @param string $title
     * @return string
     */
    private function strip_social_group_prefix(string $title): string {
        $title = preg_replace('/^\s*chat\s*:\s*/i', '', $title);
        return trim((string) $title);
    }

    /**
     * Resumen corto de actividad reciente de un grupo.
     *
     * @param string $author
     * @param string $text
     * @param string $time
     * @return string
     */
    private function build_social_group_activity_preview(string $author, string $text, string $time): string {
        $parts = [];

        if ($author !== '') {
            $parts[] = $author;
        }

        if ($text !== '') {
            $parts[] = wp_trim_words($text, 7);
        }

        $preview = implode(': ', array_filter([
            !empty($parts) ? implode(' · ', array_slice($parts, 0, 1)) : '',
            !empty($parts[1]) ? $parts[1] : '',
        ]));

        if ($preview === '') {
            return $time !== '' ? sprintf(__('Actividad reciente · %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $time) : '';
        }

        return $time !== '' ? $preview . ' · ' . $time : $preview;
    }

    /**
     * Intenta inferir el tipo de nodo asociado a un item del feed.
     *
     * @param array $context
     * @param string $url
     * @return string
     */
    private function infer_social_feed_entity_type(array $context, string $url): string {
        if (!empty($context['comunidad_id'])) {
            return 'comunidad';
        }

        if (!empty($context['colectivo_id'])) {
            return 'colectivo';
        }

        if (!empty($context['evento_id'])) {
            return 'evento';
        }

        if (!empty($context['grupo_consumo_id'])) {
            return 'grupo_consumo';
        }

        if (!empty($context['energia_comunidad_id'])) {
            return 'energia_comunidad';
        }

        if (preg_match('#/comunidades/(\d+)/#', $url)) {
            return 'comunidad';
        }

        if (preg_match('#/colectivos/(\d+)/#', $url)) {
            return 'colectivo';
        }

        if (preg_match('#/eventos/(\d+)/#', $url)) {
            return 'evento';
        }

        if (preg_match('#/grupos-consumo/(\d+)/#', $url)) {
            return 'grupo_consumo';
        }

        if (preg_match('#/energia-comunitaria/(\d+)/#', $url)) {
            return 'energia_comunidad';
        }

        return '';
    }

    /**
     * Intenta inferir el ID de nodo asociado a un item del feed.
     *
     * @param array $context
     * @param string $url
     * @return int
     */
    private function infer_social_feed_entity_id(array $context, string $url): int {
        if (!empty($context['comunidad_id'])) {
            return absint($context['comunidad_id']);
        }

        if (!empty($context['colectivo_id'])) {
            return absint($context['colectivo_id']);
        }

        if (!empty($context['evento_id'])) {
            return absint($context['evento_id']);
        }

        if (!empty($context['grupo_consumo_id'])) {
            return absint($context['grupo_consumo_id']);
        }

        if (!empty($context['energia_comunidad_id'])) {
            return absint($context['energia_comunidad_id']);
        }

        if (preg_match('#/comunidades/(\d+)/#', $url, $matches)) {
            return absint($matches[1]);
        }

        if (preg_match('#/colectivos/(\d+)/#', $url, $matches)) {
            return absint($matches[1]);
        }

        if (preg_match('#/eventos/(\d+)/#', $url, $matches)) {
            return absint($matches[1]);
        }

        if (preg_match('#/grupos-consumo/(\d+)/#', $url, $matches)) {
            return absint($matches[1]);
        }

        if (preg_match('#/energia-comunitaria/(\d+)/#', $url, $matches)) {
            return absint($matches[1]);
        }

        return 0;
    }

    /**
     * Intenta inferir la comunidad relacionada a un grupo social.
     *
     * @param array $group
     * @return int
     */
    private function infer_social_group_community_id(array $group): int {
        $slug = (string) ($group['slug'] ?? '');

        if ($slug !== '' && preg_match('/^comunidad-(\d+)$/', $slug, $matches)) {
            return absint($matches[1]);
        }

        return 0;
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
                    $stats[] = ['value' => $total_pedidos, 'label' => __('Mis pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-cart'];
                }

                // Items en cesta
                $tabla_lista = $wpdb->prefix . 'flavor_gc_lista_compra';
                if ($this->table_exists($tabla_lista)) {
                    $items_cesta = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_lista} WHERE usuario_id = %d",
                        $user_id
                    ));
                    $stats[] = ['value' => $items_cesta, 'label' => __('En cesta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-products'];
                }
                break;

            case 'banco-tiempo':
                $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
                if ($this->table_exists($tabla_servicios)) {
                    $servicios_activos = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_servicios} WHERE usuario_id = %d AND estado = 'activo'",
                        $user_id
                    ));
                    $stats[] = ['value' => $servicios_activos, 'label' => __('Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clock'];
                }

                // Primero intentar tabla transacciones (nueva estructura)
                $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';
                $tabla_intercambios = $wpdb->prefix . 'flavor_banco_tiempo_intercambios';

                if ($this->table_exists($tabla_transacciones)) {
                    $intercambios = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_transacciones}
                         WHERE (usuario_solicitante_id = %d OR usuario_receptor_id = %d) AND estado = 'completado'",
                        $user_id, $user_id
                    ));
                    $stats[] = ['value' => $intercambios, 'label' => __('Intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-randomize'];
                } elseif ($this->table_exists($tabla_intercambios)) {
                    // Fallback a tabla intercambios (estructura antigua)
                    $intercambios = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_intercambios}
                         WHERE (solicitante_id = %d OR oferente_id = %d) AND estado = 'completado'",
                        $user_id, $user_id
                    ));
                    $stats[] = ['value' => $intercambios, 'label' => __('Intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-randomize'];
                }
                break;

            case 'espacios-comunes':
                $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
                if ($this->table_exists($tabla_reservas)) {
                    $reservas_activas = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_reservas}
                         WHERE usuario_id = %d AND estado IN ('pendiente', 'aprobada') AND fecha >= CURDATE()",
                        $user_id
                    ));
                    $stats[] = ['value' => $reservas_activas, 'label' => __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-calendar'];
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
                    $stats[] = ['value' => $incidencias_abiertas, 'label' => __('Abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-warning'];
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
                $stats[] = ['value' => $eventos_inscritos, 'label' => __('Inscritos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-tickets-alt'];
                break;

            case 'bicicletas-compartidas':
                $tabla_prestamos = $wpdb->prefix . 'flavor_bicicletas_prestamos';
                if ($this->table_exists($tabla_prestamos)) {
                    $prestamos_activos = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_prestamos}
                         WHERE usuario_id = %d AND estado = 'activo'",
                        $user_id
                    ));
                    $stats[] = ['value' => $prestamos_activos, 'label' => __('En uso', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-site'];
                }
                break;

            case 'reciclaje':
                $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
                if ($this->table_exists($tabla_depositos)) {
                    $total_kg = (float) $wpdb->get_var($wpdb->prepare(
                        "SELECT COALESCE(SUM(cantidad_kg), 0) FROM {$tabla_depositos}
                         WHERE usuario_id = %d AND MONTH(fecha) = MONTH(NOW())",
                        $user_id
                    ));
                    $stats[] = ['value' => number_format($total_kg, 1) . ' kg', 'label' => __('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-trash'];
                }
                break;

            case 'compostaje':
                $tabla_aportes = $wpdb->prefix . 'flavor_aportaciones_compost';
                if ($this->table_exists($tabla_aportes)) {
                    $aportes_mes = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_aportes}
                         WHERE usuario_id = %d AND MONTH(fecha) = MONTH(NOW())",
                        $user_id
                    ));
                    $stats[] = ['value' => $aportes_mes, 'label' => __('Aportes/mes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-carrot'];
                }
                break;

            case 'huertos-urbanos':
                $tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
                if ($this->table_exists($tabla_parcelas) && $this->column_exists($tabla_parcelas, 'usuario_id')) {
                    $parcelas = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_parcelas}
                         WHERE usuario_id = %d AND estado = 'activa'",
                        $user_id
                    ));
                    $stats[] = ['value' => $parcelas, 'label' => __('Parcelas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-admin-site-alt3'];
                }
                break;

            case 'marketplace':
                $productos = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts}
                     WHERE post_author = %d AND post_type = 'flavor_producto' AND post_status = 'publish'",
                    $user_id
                ));
                $stats[] = ['value' => $productos, 'label' => __('Productos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-store'];
                break;

            case 'fichaje-empleados':
                $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';
                if ($this->table_exists($tabla_fichajes)) {
                    // Detectar estructura de la tabla
                    $col_usuario = $this->column_exists($tabla_fichajes, 'user_id') ? 'user_id' :
                                  ($this->column_exists($tabla_fichajes, 'usuario_id') ? 'usuario_id' : null);

                    if ($col_usuario && $this->column_exists($tabla_fichajes, 'hora_entrada')) {
                        $col_fecha = $this->column_exists($tabla_fichajes, 'fecha') ? 'fecha' : 'created_at';
                        $fichaje_hoy = $wpdb->get_row($wpdb->prepare(
                            "SELECT hora_entrada, hora_salida FROM {$tabla_fichajes}
                             WHERE {$col_usuario} = %d AND DATE({$col_fecha}) = CURDATE()
                             ORDER BY hora_entrada DESC LIMIT 1",
                            $user_id
                        ));
                        if ($fichaje_hoy) {
                            $estado = $fichaje_hoy->hora_salida ? __('Salida', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Entrada', FLAVOR_PLATFORM_TEXT_DOMAIN);
                            $stats[] = ['value' => $estado, 'label' => __('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clock'];
                        } else {
                            $stats[] = ['value' => '-', 'label' => __('Sin fichar', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clock'];
                        }
                    } elseif ($col_usuario && $this->column_exists($tabla_fichajes, 'tipo')) {
                        // Estructura alternativa con tipo entrada/salida
                        $entrada = $wpdb->get_var($wpdb->prepare(
                            "SELECT fecha_hora FROM {$tabla_fichajes}
                             WHERE {$col_usuario} = %d AND tipo = 'entrada' AND DATE(fecha_hora) = CURDATE()
                             ORDER BY fecha_hora DESC LIMIT 1",
                            $user_id
                        ));
                        if ($entrada) {
                            $stats[] = ['value' => date('H:i', strtotime($entrada)), 'label' => __('Entrada', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clock'];
                        } else {
                            $stats[] = ['value' => '-', 'label' => __('Sin fichar', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-clock'];
                        }
                    }
                }
                break;

            case 'carpooling':
                // Viajes ofrecidos por el usuario
                $viajes_ofrecidos = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts}
                     WHERE post_author = %d AND post_type = 'flavor_viaje' AND post_status = 'publish'",
                    $user_id
                ));
                $stats[] = ['value' => $viajes_ofrecidos, 'label' => __('Viajes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-car'];
                break;

            case 'parkings':
                $tabla_reservas_parking = $wpdb->prefix . 'flavor_parking_reservas';
                if ($this->table_exists($tabla_reservas_parking)) {
                    $reservas_parking = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_reservas_parking}
                         WHERE usuario_id = %d AND estado = 'activa' AND fecha_inicio >= NOW()",
                        $user_id
                    ));
                    $stats[] = ['value' => $reservas_parking, 'label' => __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-location-alt'];
                }
                break;

            case 'cursos':
                // Inscripciones a cursos
                $cursos_inscritos = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
                     INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                     WHERE pm.meta_key = '_flavor_curso_inscritos'
                       AND pm.meta_value LIKE %s
                       AND p.post_type = 'flavor_curso'
                       AND p.post_status = 'publish'",
                    '%"' . $user_id . '"%'
                ));
                $stats[] = ['value' => $cursos_inscritos, 'label' => __('Inscritos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-welcome-learn-more'];
                break;

            case 'talleres':
                // Inscripciones a talleres
                $talleres_inscritos = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
                     INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                     WHERE pm.meta_key = '_flavor_taller_inscritos'
                       AND pm.meta_value LIKE %s
                       AND p.post_type = 'flavor_taller'
                       AND p.post_status = 'publish'",
                    '%"' . $user_id . '"%'
                ));
                $stats[] = ['value' => $talleres_inscritos, 'label' => __('Inscritos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-hammer'];
                break;

            case 'biblioteca':
                $tabla_prestamos_biblioteca = $wpdb->prefix . 'flavor_biblioteca_prestamos';
                if ($this->table_exists($tabla_prestamos_biblioteca)) {
                    $prestamos_activos = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_prestamos_biblioteca}
                         WHERE usuario_id = %d AND estado = 'prestado'",
                        $user_id
                    ));
                    $stats[] = ['value' => $prestamos_activos, 'label' => __('Préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-book'];
                } else {
                    // Fallback: contar libros favoritos o reservados via postmeta
                    $libros_reservados = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
                         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                         WHERE pm.meta_key = '_flavor_libro_reservado_por'
                           AND pm.meta_value = %d
                           AND p.post_type = 'flavor_libro'",
                        $user_id
                    ));
                    $stats[] = ['value' => $libros_reservados, 'label' => __('Reservados', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-book'];
                }
                break;

            case 'reservas':
                $tabla_reservas_general = $wpdb->prefix . 'flavor_reservas';
                if ($this->table_exists($tabla_reservas_general) && $this->column_exists($tabla_reservas_general, 'usuario_id')) {
                    // Detectar columna de fecha
                    $col_fecha = $this->column_exists($tabla_reservas_general, 'fecha') ? 'fecha' :
                                ($this->column_exists($tabla_reservas_general, 'fecha_inicio') ? 'fecha_inicio' : null);

                    if ($col_fecha) {
                        $mis_reservas = (int) $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$tabla_reservas_general}
                             WHERE usuario_id = %d AND estado IN ('confirmada', 'pendiente') AND {$col_fecha} >= CURDATE()",
                            $user_id
                        ));
                        $stats[] = ['value' => $mis_reservas, 'label' => __('Activas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-calendar'];
                    }
                }
                break;

            case 'ayuda-vecinal':
                $tabla_ayuda = $wpdb->prefix . 'flavor_ayuda_vecinal';
                // Verificar que existen las columnas necesarias antes de consultar
                if ($this->table_exists($tabla_ayuda) && $this->column_exists($tabla_ayuda, 'ayudante_id')) {
                    $ayudas_dadas = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_ayuda}
                         WHERE ayudante_id = %d AND estado = 'completada'",
                        $user_id
                    ));
                    $stats[] = ['value' => $ayudas_dadas, 'label' => __('Ayudas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-heart'];
                }
                break;

            case 'participacion':
                // Propuestas creadas por el usuario
                $propuestas = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts}
                     WHERE post_author = %d AND post_type = 'flavor_propuesta' AND post_status = 'publish'",
                    $user_id
                ));
                $stats[] = ['value' => $propuestas, 'label' => __('Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-lightbulb'];
                break;

            case 'presupuestos-participativos':
                // Votos emitidos
                $tabla_votos = $wpdb->prefix . 'flavor_presupuestos_votos';
                if ($this->table_exists($tabla_votos)) {
                    $votos_emitidos = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tabla_votos} WHERE usuario_id = %d",
                        $user_id
                    ));
                    $stats[] = ['value' => $votos_emitidos, 'label' => __('Votos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-chart-pie'];
                }
                break;

            case 'socios':
                // Estado de membresía
                $es_socio = get_user_meta($user_id, '_flavor_socio_activo', true);
                $estado_socio = $es_socio ? __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('No socio', FLAVOR_PLATFORM_TEXT_DOMAIN);
                $stats[] = ['value' => $estado_socio, 'label' => __('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-id'];
                break;

            case 'avisos-municipales':
                // Avisos no leídos
                $avisos_no_leidos = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} p
                     WHERE p.post_type = 'flavor_aviso'
                       AND p.post_status = 'publish'
                       AND NOT EXISTS (
                           SELECT 1 FROM {$wpdb->postmeta} pm
                           WHERE pm.post_id = p.ID
                             AND pm.meta_key = '_flavor_aviso_leido_por'
                             AND pm.meta_value LIKE %s
                       )",
                    '%"' . $user_id . '"%'
                ));
                $stats[] = ['value' => $avisos_no_leidos, 'label' => __('Sin leer', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => 'dashicons-bell'];
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
                return [['value' => '-', 'label' => __('Total', FLAVOR_PLATFORM_TEXT_DOMAIN)]];
        }

        // Si no hay estadísticas, devolver valor por defecto
        if (empty($stats)) {
            return [['value' => '-', 'label' => __('Total', FLAVOR_PLATFORM_TEXT_DOMAIN)]];
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
     * Verifica si una columna existe en una tabla
     *
     * @param string $table_name Nombre completo de la tabla
     * @param string $column_name Nombre de la columna
     * @return bool
     */
    private function column_exists(string $table_name, string $column_name): bool {
        global $wpdb;
        static $cache = [];

        $cache_key = $table_name . '.' . $column_name;
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        $wpdb->suppress_errors(true);
        $result = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM {$table_name} LIKE %s",
            $column_name
        ));
        $wpdb->suppress_errors(false);

        $cache[$cache_key] = !empty($result);
        return $cache[$cache_key];
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
            'reservas'              => ['nombre' => __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-calendar', 'categoria' => 'gestion'],
            'espacios_comunes'      => ['nombre' => __('Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-admin-home', 'categoria' => 'gestion'],
            'parkings'              => ['nombre' => __('Parkings', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-location-alt', 'categoria' => 'gestion'],
            'incidencias'           => ['nombre' => __('Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-warning', 'categoria' => 'gestion'],
            'tramites'              => ['nombre' => __('Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-clipboard', 'categoria' => 'gestion'],
            'fichaje_empleados'     => ['nombre' => __('Fichaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-clock', 'categoria' => 'gestion'],
            'clientes'              => ['nombre' => __('Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-id-alt', 'categoria' => 'gestion'],
            'facturas'              => ['nombre' => __('Facturas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-media-text', 'categoria' => 'gestion'],

            // === COMUNIDAD ===
            'eventos'               => ['nombre' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-calendar-alt', 'categoria' => 'comunidad'],
            'cursos'                => ['nombre' => __('Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-welcome-learn-more', 'categoria' => 'comunidad'],
            'talleres'              => ['nombre' => __('Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-hammer', 'categoria' => 'comunidad'],
            'comunidades'           => ['nombre' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-groups', 'categoria' => 'comunidad'],
            'colectivos'            => ['nombre' => __('Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-networking', 'categoria' => 'comunidad'],
            'socios'                => ['nombre' => __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-id', 'categoria' => 'comunidad'],
            'participacion'         => ['nombre' => __('Participación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-megaphone', 'categoria' => 'comunidad'],
            'presupuestos_participativos' => ['nombre' => __('Presupuestos Participativos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-chart-pie', 'categoria' => 'comunidad'],
            'ayuda_vecinal'         => ['nombre' => __('Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-heart', 'categoria' => 'comunidad'],
            'avisos_municipales'    => ['nombre' => __('Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-bell', 'categoria' => 'comunidad'],

            // === ECONOMÍA ===
            'marketplace'           => ['nombre' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-cart', 'categoria' => 'economia'],
            'banco_tiempo'          => ['nombre' => __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-backup', 'categoria' => 'economia'],
            'grupos_consumo'        => ['nombre' => __('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-store', 'categoria' => 'economia'],
            'advertising'           => ['nombre' => __('Publicidad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-megaphone', 'categoria' => 'economia'],
            'empresarial'           => ['nombre' => __('Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-building', 'categoria' => 'economia'],
            'trading_ia'            => ['nombre' => __('Trading IA', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-chart-line', 'categoria' => 'economia'],
            'dex_solana'            => ['nombre' => __('DEX Solana', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-superhero-alt', 'categoria' => 'economia'],

            // === SOSTENIBILIDAD ===
            'huertos_urbanos'       => ['nombre' => __('Huertos Urbanos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-carrot', 'categoria' => 'sostenibilidad'],
            'reciclaje'             => ['nombre' => __('Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-update-alt', 'categoria' => 'sostenibilidad'],
            'compostaje'            => ['nombre' => __('Compostaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-admin-site-alt', 'categoria' => 'sostenibilidad'],

            // === MOVILIDAD ===
            'carpooling'            => ['nombre' => __('Carpooling', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-car', 'categoria' => 'movilidad'],
            'bicicletas_compartidas' => ['nombre' => __('Bicicletas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-dashboard', 'categoria' => 'movilidad'],

            // === RECURSOS ===
            'biblioteca'            => ['nombre' => __('Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-book', 'categoria' => 'recursos'],
            'podcast'               => ['nombre' => __('Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-microphone', 'categoria' => 'recursos'],
            'radio'                 => ['nombre' => __('Radio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-format-audio', 'categoria' => 'recursos'],
            'multimedia'            => ['nombre' => __('Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-format-video', 'categoria' => 'recursos'],

            // === COMUNICACIÓN ===
            'foros'                 => ['nombre' => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-format-chat', 'categoria' => 'comunicacion'],
            'chat_interno'          => ['nombre' => __('Chat Interno', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-email-alt', 'categoria' => 'comunicacion'],
            'chat_grupos'           => ['nombre' => __('Grupos de Chat', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-groups', 'categoria' => 'comunicacion'],
            'red_social'            => ['nombre' => __('Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-share', 'categoria' => 'comunicacion'],
            'email_marketing'       => ['nombre' => __('Email Marketing', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-email', 'categoria' => 'comunicacion'],

            // === OTROS ===
            'bares'                 => ['nombre' => __('Bares', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-food', 'categoria' => 'otros'],
        ];

        // Obtener módulos activos desde configuración (ambas ubicaciones)
        $settings = get_option('flavor_chat_ia_settings', []);
        $activos = $settings['active_modules'] ?? [];

        // También leer de flavor_active_modules (legacy/compatibilidad)
        $modulos_activos_legacy = get_option('flavor_active_modules', []);
        if (!empty($modulos_activos_legacy)) {
            $activos = array_unique(array_merge($activos, $modulos_activos_legacy));
        }

        // Si no hay módulos configurados, usar default (NO mostrar todos)
        if (empty($activos)) {
            $activos = ['woocommerce'];
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

    /**
     * Renderiza el panel de impacto regenerativo (filosofía Gailu)
     *
     * Muestra los principios transformadores y las capacidades regenerativas
     * activas en el nodo, basándose en los módulos activos.
     *
     * @since 3.1.0
     * @return void
     */
    /**
     * Renderiza el panel de impacto regenerativo (Gailu)
     * Puede ser llamado desde otros contextos (ej: class-dynamic-pages.php)
     *
     * @param bool $compact Si es true, muestra versión compacta para vistas de módulo
     * @return void
     */
    public function render_gailu_impact_panel(bool $compact = false): void {
        // Obtener métricas Gailu desde el Module Loader
        // Leer de ambas ubicaciones de configuración
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos_ids = $configuracion['active_modules'] ?? [];

        $modulos_activos_legacy = get_option('flavor_active_modules', []);
        if (!empty($modulos_activos_legacy)) {
            $modulos_activos_ids = array_unique(array_merge($modulos_activos_ids, $modulos_activos_legacy));
        }

        if (empty($modulos_activos_ids)) {
            return; // No mostrar si no hay módulos activos
        }

        $gailu_metricas = [];
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $gailu_metricas = Flavor_Chat_Module_Loader::get_gailu_metricas($modulos_activos_ids);
        }

        if (empty($gailu_metricas)) {
            return;
        }

        $principios = $gailu_metricas['principios'] ?? [];
        $contribuciones = $gailu_metricas['contribuciones'] ?? [];
        $principios_cubiertos = $gailu_metricas['cubiertos']['principios'] ?? 0;
        $total_principios = $gailu_metricas['totales']['principios'] ?? 5;

        $etiquetas_principios = [
            'economia_local' => ['nombre' => __('Economía Local', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => '🏪', 'color' => '#10b981'],
            'cuidados' => ['nombre' => __('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => '💚', 'color' => '#ec4899'],
            'gobernanza' => ['nombre' => __('Gobernanza', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => '🤝', 'color' => '#8b5cf6'],
            'regeneracion' => ['nombre' => __('Regeneración', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => '🌱', 'color' => '#22c55e'],
            'aprendizaje' => ['nombre' => __('Aprendizaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => '📚', 'color' => '#f59e0b'],
        ];

        $etiquetas_contribuciones = [
            'autonomia' => ['nombre' => __('Autonomía', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => '🚀', 'color' => '#3b82f6'],
            'resiliencia' => ['nombre' => __('Resiliencia', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => '🛡️', 'color' => '#06b6d4'],
            'cohesion' => ['nombre' => __('Cohesión', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => '🔗', 'color' => '#a855f7'],
            'impacto' => ['nombre' => __('Impacto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => '⚡', 'color' => '#ef4444'],
        ];

        $porcentaje_cobertura = $total_principios > 0 ? round(($principios_cubiertos / $total_principios) * 100) : 0;
        ?>
        <section class="fud-gailu-panel" aria-labelledby="fud-gailu-title">
            <div class="fud-gailu-panel__header">
                <div class="fud-gailu-panel__title-wrapper">
                    <h2 id="fud-gailu-title" class="fud-gailu-panel__title">
                        <span class="fud-gailu-panel__icon">🌍</span>
                        <?php esc_html_e('Impacto Regenerativo del Nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h2>
                    <p class="fud-gailu-panel__description">
                        <?php esc_html_e('Tu participación activa impulsa la transición hacia una comunidad más sostenible y solidaria.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>
                <div class="fud-gailu-panel__score">
                    <span class="fud-gailu-panel__score-value"><?php echo esc_html($porcentaje_cobertura); ?>%</span>
                    <span class="fud-gailu-panel__score-label"><?php esc_html_e('cobertura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>

            <div class="fud-gailu-panel__grid">
                <!-- Principios transformadores -->
                <div class="fud-gailu-card">
                    <h3 class="fud-gailu-card__title">
                        <span>⭐</span> <?php esc_html_e('Principios Transformadores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="fud-gailu-principios">
                        <?php foreach ($etiquetas_principios as $clave => $datos) :
                            $modulos_principio = $principios[$clave] ?? [];
                            $tiene_modulos = !empty($modulos_principio);
                            $count = count($modulos_principio);
                        ?>
                        <div class="fud-gailu-principio <?php echo $tiene_modulos ? 'is-active' : 'is-inactive'; ?>"
                             style="--principio-color: <?php echo esc_attr($datos['color']); ?>"
                             title="<?php echo $tiene_modulos ? esc_attr(implode(', ', $modulos_principio)) : esc_attr__('Aún no activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <span class="fud-gailu-principio__icon"><?php echo esc_html($datos['icono']); ?></span>
                            <span class="fud-gailu-principio__name"><?php echo esc_html($datos['nombre']); ?></span>
                            <?php if ($tiene_modulos) : ?>
                            <span class="fud-gailu-principio__count"><?php echo esc_html($count); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Capacidades regenerativas -->
                <div class="fud-gailu-card">
                    <h3 class="fud-gailu-card__title">
                        <span>🏆</span> <?php esc_html_e('Capacidades Regenerativas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="fud-gailu-capacidades">
                        <?php
                        $total_modulos = max(1, count($modulos_activos_ids));
                        foreach ($etiquetas_contribuciones as $clave => $datos) :
                            $modulos_contribucion = $contribuciones[$clave] ?? [];
                            $tiene_contribucion = !empty($modulos_contribucion);
                            $count = count($modulos_contribucion);
                            $porcentaje = round(($count / $total_modulos) * 100);
                        ?>
                        <div class="fud-gailu-capacidad <?php echo $tiene_contribucion ? 'is-active' : 'is-inactive'; ?>">
                            <div class="fud-gailu-capacidad__info">
                                <span class="fud-gailu-capacidad__icon"><?php echo esc_html($datos['icono']); ?></span>
                                <span class="fud-gailu-capacidad__name"><?php echo esc_html($datos['nombre']); ?></span>
                            </div>
                            <div class="fud-gailu-capacidad__bar">
                                <div class="fud-gailu-capacidad__fill" style="width: <?php echo esc_attr($porcentaje); ?>%; background: <?php echo esc_attr($datos['color']); ?>"></div>
                            </div>
                            <span class="fud-gailu-capacidad__value"><?php echo esc_html($count); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }

    /**
     * Renderiza el panel de prioridades (señales del nodo y próximas acciones)
     * Puede ser llamado desde otros contextos (ej: class-dynamic-pages.php)
     *
     * @param string|null $module_id Si se pasa, filtra alertas relevantes para el módulo
     * @return void
     */
    public function render_priority_panels(?string $module_id = null): void {
        $portal_notifications_markup = '';
        $portal_actions_markup = '';

        if (class_exists('Flavor_Portal_Shortcodes')) {
            $portal_shortcodes = Flavor_Portal_Shortcodes::get_instance();
            if (method_exists($portal_shortcodes, 'render_shared_notifications_bar')) {
                $portal_notifications_markup = (string) $portal_shortcodes->render_shared_notifications_bar($module_id);
            }
            if (method_exists($portal_shortcodes, 'render_shared_upcoming_actions')) {
                $portal_actions_markup = (string) $portal_shortcodes->render_shared_upcoming_actions($module_id);
            }
        }

        if ($portal_notifications_markup === '' && $portal_actions_markup === '') {
            return;
        }
        ?>
        <section class="fud-priority-panels fud-priority-panels--module" aria-labelledby="fud-priority-panels-title">
            <div class="fud-priority-panels__header">
                <h2 id="fud-priority-panels-title" class="fud-priority-panels__title"><?php esc_html_e('Atención y próximos pasos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            </div>
            <div class="fud-priority-panels__grid">
                <?php if ($portal_notifications_markup !== '') : ?>
                <article class="fud-priority-panel">
                    <div class="fud-priority-panel__head">
                        <h3 class="fud-priority-panel__title"><?php esc_html_e('Señales del nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    </div>
                    <?php echo $portal_notifications_markup; ?>
                </article>
                <?php endif; ?>
                <?php if ($portal_actions_markup !== '') : ?>
                <article class="fud-priority-panel">
                    <div class="fud-priority-panel__head">
                        <h3 class="fud-priority-panel__title"><?php esc_html_e('Qué hacer ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    </div>
                    <?php echo $portal_actions_markup; ?>
                </article>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Renderiza el panel social compacto
     * Puede ser llamado desde otros contextos (ej: class-dynamic-pages.php)
     *
     * @param int $user_id ID del usuario
     * @return void
     */
    public function render_social_panel_compact(int $user_id): void {
        $social_panel = $this->get_frontend_social_panel_data($user_id);

        if (empty($social_panel['feed']) && empty($social_panel['community_nodes']) && empty($social_panel['groups'])) {
            return;
        }
        ?>
        <section class="fud-social-panel fud-social-panel--compact" aria-labelledby="fud-social-panel-compact-title">
            <div class="fud-social-panel__header">
                <h2 id="fud-social-panel-compact-title" class="fud-social-panel__title">
                    <?php esc_html_e('Pulso social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <a href="<?php echo esc_url(home_url('/mi-portal/')); ?>" class="fud-social-panel__link">
                    <?php esc_html_e('Ver todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
            <div class="fud-social-panel__grid fud-social-panel__grid--compact">
                <?php if (!empty($social_panel['feed'])) : ?>
                <div class="fl-item-list fl-item-list--horizontal">
                    <?php foreach (array_slice($social_panel['feed'], 0, 3) as $item) : ?>
                    <a href="<?php echo esc_url($item['url'] ?? '#'); ?>" class="fl-item-list__link">
                        <span class="fl-item-list__icon"><?php echo esc_html($item['tipo_info']['icon'] ?? '📝'); ?></span>
                        <span class="fl-item-list__content">
                            <span class="fl-item-list__title"><?php echo esc_html($item['title']); ?></span>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
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
