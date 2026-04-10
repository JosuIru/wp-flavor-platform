<?php
/**
 * Dashboard Principal de Flavor Platform
 *
 * Vista general con widgets, estadisticas, graficos y acciones rapidas.
 * Incluye actualizacion en tiempo real via AJAX.
 *
 * @package FlavorPlatform
 * @subpackage Admin
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para el dashboard principal
 *
 * @since 3.0.0
 */
class Flavor_Dashboard {

    /**
     * Instancia singleton
     *
     * @var Flavor_Dashboard
     */
    private static $instancia = null;

    /**
     * Intervalo de actualizacion en segundos
     *
     * @var int
     */
    const INTERVALO_ACTUALIZACION = 60;

    /**
     * Rate limit: máximo de requests por minuto para endpoints costosos
     *
     * @var int
     */
    const RATE_LIMIT_MAX_REQUESTS = 10;

    /**
     * Rate limit: ventana de tiempo en segundos
     *
     * @var int
     */
    const RATE_LIMIT_WINDOW = 60;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Dashboard
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
        $this->init_hooks();
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Registrar assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);

        // Registrar endpoint REST API
        add_action('rest_api_init', [$this, 'registrar_endpoints_api']);

        // AJAX para usuarios no autenticados con REST API
        add_action('wp_ajax_flavor_dashboard_stats', [$this, 'ajax_obtener_estadisticas']);
        add_action('wp_ajax_flavor_dashboard_quick_action', [$this, 'ajax_ejecutar_accion_rapida']);
        add_action('wp_ajax_flavor_save_panel_state', [$this, 'ajax_guardar_estado_panel']);
    }

    /**
     * Registra endpoints de la API REST
     *
     * @return void
     */
    public function registrar_endpoints_api() {
        register_rest_route('flavor/v1', '/admin/dashboard-stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_obtener_estadisticas'],
            'permission_callback' => [$this, 'usuario_puede_ver_dashboard'],
        ]);

        register_rest_route('flavor/v1', '/admin/dashboard-charts', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_obtener_datos_graficos'],
            'permission_callback' => [$this, 'usuario_puede_ver_dashboard'],
        ]);

        register_rest_route('flavor/v1', '/admin/dashboard-alerts', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_obtener_alertas'],
            'permission_callback' => [$this, 'usuario_puede_ver_dashboard'],
        ]);

        // Endpoints para Red de Comunidades
        register_rest_route('flavor/v1', '/admin/network-stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_obtener_estadisticas_red'],
            'permission_callback' => [$this, 'usuario_puede_ver_dashboard'],
        ]);

        register_rest_route('flavor/v1', '/admin/network-sync', [
            'methods'             => 'POST',
            'callback'            => [$this, 'api_sincronizar_red'],
            'permission_callback' => [$this, 'usuario_puede_ver_dashboard'],
        ]);

        register_rest_route('flavor/v1', '/admin/activity-map', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_obtener_mapa_actividad'],
            'permission_callback' => [$this, 'usuario_puede_ver_dashboard'],
            'args'                => [
                'tipo' => [
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function( $value ) {
                        $tipos_permitidos = ['cooperativa', 'asociacion', 'comunidad', 'colectivo', 'grupo', 'red', ''];
                        return empty( $value ) || in_array( $value, $tipos_permitidos, true );
                    },
                ],
                'pais' => [
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function( $value ) {
                        // Validar código de país ISO 3166-1 alpha-2 o nombre
                        return empty( $value ) || ( is_string( $value ) && strlen( $value ) <= 100 );
                    },
                ],
                'modulo' => [
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => function( $value ) {
                        return empty( $value ) || preg_match( '/^[a-z0-9_-]+$/', $value );
                    },
                ],
            ],
        ]);

        register_rest_route('flavor/v1', '/admin/export-stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_exportar_estadisticas_csv'],
            'permission_callback' => [$this, 'usuario_puede_ver_dashboard'],
            'args'                => [
                'tipo' => [
                    'required'          => false,
                    'type'              => 'string',
                    'default'           => 'general',
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => function( $value ) {
                        $tipos_permitidos = ['general', 'usuarios', 'red', 'conversaciones'];
                        return in_array( $value, $tipos_permitidos, true );
                    },
                ],
            ],
        ]);
    }

    /**
     * Determina si el usuario puede acceder al dashboard principal.
     *
     * @return bool
     */
    public function usuario_puede_ver_dashboard() {
        return current_user_can('manage_options')
            || current_user_can('flavor_ver_dashboard')
            || current_user_can('flavor_gestor_grupos');
    }

    /**
     * Verifica rate limiting para endpoints costosos.
     *
     * FIX: Añadido para prevenir abuso de endpoints de exportación y sincronización.
     *
     * @param string $endpoint_key Identificador único del endpoint.
     * @return bool|WP_REST_Response True si está permitido, WP_REST_Response con error si excede límite.
     */
    private function check_rate_limit( $endpoint_key ) {
        $user_id = get_current_user_id();
        $transient_key = 'flavor_rate_limit_' . $endpoint_key . '_' . $user_id;

        $requests = get_transient( $transient_key );
        if ( false === $requests ) {
            $requests = 0;
        }

        if ( $requests >= self::RATE_LIMIT_MAX_REQUESTS ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => __( 'Demasiadas solicitudes. Intenta de nuevo en un minuto.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'code'    => 'rate_limit_exceeded',
                ],
                429
            );
        }

        set_transient( $transient_key, $requests + 1, self::RATE_LIMIT_WINDOW );

        return true;
    }

    /**
     * Registra assets del dashboard
     *
     * Sistema de Diseño Unificado v4.1.0
     *
     * @param string $sufijo_hook Sufijo del hook
     * @return void
     */
    public function enqueue_dashboard_assets($sufijo_hook) {
        $sufijo_hook = (string) $sufijo_hook;
        $dashboard_hooks = [
            'toplevel_page_flavor-chat-ia',
            'flavor-platform_page_flavor-dashboard',
            'toplevel_page_flavor-dashboard',
        ];

        $is_dashboard_hook = strpos($sufijo_hook, 'flavor-dashboard') !== false
            || in_array($sufijo_hook, $dashboard_hooks, true);

        if (!$is_dashboard_hook) {
            return;
        }

        $version = FLAVOR_PLATFORM_VERSION;
        $plugin_url = FLAVOR_PLATFORM_URL;

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

        // 5. Grupos y categorías
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

        // 10. CSS Componentes
        wp_enqueue_style(
            'fud-dashboard-components',
            $plugin_url . 'assets/css/layouts/dashboard-components.css',
            ['fl-dashboard-responsive'],
            $version
        );

        // 11. Dashboard específico (legacy, si existe)
        if (file_exists(FLAVOR_PLATFORM_PATH . 'admin/css/dashboard.css')) {
            wp_enqueue_style(
                'flavor-dashboard-legacy',
                $plugin_url . 'admin/css/dashboard.css',
                ['fud-dashboard-components'],
                $version
            );
        }

        // =====================================================================
        // JavaScript
        // =====================================================================

        // Chart.js para graficos
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );

        // SortableJS
        wp_enqueue_script(
            'sortablejs',
            'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
            [],
            '1.15.2',
            true
        );

        // Dashboard Sortable (nuevo sistema)
        if (file_exists(FLAVOR_PLATFORM_PATH . 'assets/js/dashboard-sortable.js')) {
            wp_enqueue_script(
                'fl-dashboard-sortable',
                $plugin_url . 'assets/js/dashboard-sortable.js',
                ['sortablejs'],
                $version,
                true
            );
        }

        // jQuery UI Sortable como fallback
        wp_enqueue_script('jquery-ui-sortable');

        // JavaScript del dashboard
        wp_enqueue_script(
            'flavor-dashboard-charts',
            $plugin_url . 'admin/js/dashboard-charts.js',
            ['jquery', 'chartjs', 'jquery-ui-sortable'],
            $version,
            true
        );

        // Localizar script con datos
        wp_localize_script('flavor-dashboard-charts', 'flavorDashboard', [
            'ajaxUrl'              => admin_url('admin-ajax.php'),
            'restUrl'              => rest_url('flavor/v1/admin/'),
            'nonce'                => wp_create_nonce('wp_rest'),
            'ajaxNonce'            => wp_create_nonce('flavor_dashboard_nonce'),
            'panelStateNonce'      => wp_create_nonce('flavor_panel_state'), // FIX: Nonce para guardar estado de paneles
            'intervaloActualizacion' => self::INTERVALO_ACTUALIZACION * 1000,
            'features'             => [
                'sortable'      => true,
                'groups'        => true,
                'levels'        => true,
                'accessibility' => true,
            ],
            'textos'               => [
                'cargando'         => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error'            => __('Error al cargar datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'ultimaActualizacion' => __('Ultima actualizacion:', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'ahora'            => __('Ahora mismo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'haceMenos1Min'    => __('Hace menos de 1 minuto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'haceMinutos'      => __('Hace %d minutos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'usuariosNuevos'   => __('Usuarios nuevos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'actividadModulo'  => __('Actividad por modulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'distribucionRoles' => __('Distribucion de roles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'dragStart'        => __('Arrastrando widget', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'dragEnd'          => __('Widget soltado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'layoutSaved'      => __('Disposición guardada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Renderiza la pagina de dashboard
     *
     * @return void
     */
    public function render_dashboard_page() {
        // Determinar vista activa
        $vista_dashboard = Flavor_Admin_Menu_Manager::VISTA_ADMIN;
        if (class_exists('Flavor_Admin_Menu_Manager')) {
            $vista_dashboard = Flavor_Admin_Menu_Manager::get_instance()->obtener_vista_activa();
        }
        $es_vista_gestor_grupos = ($vista_dashboard === Flavor_Admin_Menu_Manager::VISTA_GESTOR_GRUPOS);

        // =====================================================
        // DATOS COMUNES
        // =====================================================
        $resumen_stats = $this->obtener_resumen_stats();
        $actividad_reciente = $this->obtener_actividad_reciente();

        // =====================================================
        // DATOS PARA ADMINISTRADOR
        // =====================================================
        $tareas_pendientes = [];
        $modulos_usados = [];
        $alertas_config = [];
        $accesos_rapidos = [];

        // Datos avanzados (paneles colapsables)
        $estadisticas_red = [];
        $datos_mapa_actividad = [];
        $kpis_principales = [];
        $comparativas_temporales = [];
        $datos_graficos = [];
        $gailu_metricas = [];
        $addons_activos = [];
        $addons_registrados = [];

        if (!$es_vista_gestor_grupos) {
            $tareas_pendientes = $this->obtener_tareas_pendientes();
            $modulos_usados = $this->obtener_modulos_mas_usados(6);
            $alertas_config = $this->obtener_alertas_configuracion();
            $accesos_rapidos = $this->obtener_accesos_rapidos_contextuales();

            // Datos para paneles avanzados
            $estadisticas_red = $this->obtener_estadisticas_red();
            $datos_mapa_actividad = $this->obtener_datos_mapa_actividad();
            $kpis_principales = $this->obtener_kpis_principales();
            $comparativas_temporales = $this->obtener_comparativas_temporales();
            $datos_graficos = $this->obtener_datos_graficos();

            // Datos Gailu
            $configuracion_gailu = flavor_get_main_settings();
            $modulos_activos_ids = $configuracion_gailu['active_modules'] ?? [];
            if (class_exists('Flavor_Platform_Module_Loader')) {
                $gailu_metricas = Flavor_Platform_Module_Loader::get_gailu_metricas($modulos_activos_ids);
            }

            // Addons
            if (class_exists('Flavor_Addon_Manager')) {
                $addons_activos = Flavor_Addon_Manager::get_active_addons();
                $addons_registrados = Flavor_Addon_Manager::get_registered_addons();
            }
        }

        // =====================================================
        // DATOS PARA GESTOR DE GRUPOS
        // =====================================================
        $datos_gestor = [];
        if ($es_vista_gestor_grupos) {
            $datos_gestor = $this->obtener_datos_gestor_grupos();
        }

        // Preferencias de paneles colapsables del usuario
        $paneles_estado = get_user_meta(get_current_user_id(), 'flavor_dashboard_panels', true);
        if (!is_array($paneles_estado)) {
            $paneles_estado = [
                'graficos' => false,
                'red' => false,
                'gailu' => false,
            ];
        }

        // Incluir la vista del dashboard mejorado
        include FLAVOR_PLATFORM_PATH . 'admin/views/dashboard.php';
    }

    /**
     * Ajusta las acciones rápidas al contexto de vista activa.
     *
     * @param array  $acciones
     * @param string $vista_dashboard
     * @return array
     */
    private function filtrar_acciones_rapidas_por_vista($acciones, $vista_dashboard) {
        if ($vista_dashboard !== Flavor_Admin_Menu_Manager::VISTA_GESTOR_GRUPOS) {
            return $acciones;
        }

        $principales_permitidas = ['configuracion'];
        $generales_permitidas = ['ver_logs'];

        $acciones['principales'] = array_values(array_filter(
            $acciones['principales'],
            function ($accion) use ($principales_permitidas) {
                return in_array($accion['id'], $principales_permitidas, true);
            }
        ));

        $acciones['generales'] = array_values(array_filter(
            $acciones['generales'],
            function ($accion) use ($generales_permitidas) {
                return in_array($accion['id'], $generales_permitidas, true);
            }
        ));

        array_unshift($acciones['generales'], [
            'id'       => 'crear_paginas',
            'etiqueta' => __('Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono'    => 'dashicons-admin-page',
            'url'      => admin_url('admin.php?page=flavor-create-pages'),
            'color'    => '#2271b1',
        ]);

        return $acciones;
    }

    /**
     * Obtiene datos del perfil activo para el hero
     *
     * @return array
     */
    private function obtener_datos_perfil_activo() {
        $datos_perfil = [
            'id'          => 'personalizado',
            'nombre'      => __('Personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Selecciona manualmente los modulos que necesitas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono'       => 'dashicons-admin-generic',
            'color'       => '#2271b1',
        ];

        if (class_exists('Flavor_App_Profiles')) {
            $gestor_perfiles = Flavor_App_Profiles::get_instance();
            $id_perfil_activo = $gestor_perfiles->obtener_perfil_activo();
            $perfil = $gestor_perfiles->obtener_perfil($id_perfil_activo);
            if ($perfil) {
                $datos_perfil = [
                    'id'          => $id_perfil_activo,
                    'nombre'      => $perfil['nombre'],
                    'descripcion' => $perfil['descripcion'],
                    'icono'       => $perfil['icono'],
                    'color'       => $perfil['color'],
                ];
            }
        }

        return $datos_perfil;
    }

    /**
     * Obtiene checks del onboarding
     *
     * @return array
     */
    private function obtener_checks_onboarding() {
        $configuracion = flavor_get_main_settings();
        $id_perfil_activo = $configuracion['app_profile'] ?? 'personalizado';
        $modulos_activos = $configuracion['active_modules'] ?? [];
        $tiene_api_key = !empty($configuracion['claude_api_key']) || !empty($configuracion['openai_api_key']) || !empty($configuracion['deepseek_api_key']) || !empty($configuracion['mistral_api_key']);

        $conteo_paginas_creadas = 0;
        if (class_exists('Flavor_Page_Creator')) {
            $estado_paginas = Flavor_Page_Creator::get_pages_status();
            $conteo_paginas_creadas = count($estado_paginas['exists'] ?? []);
        }

        $conteo_addons = count(Flavor_Addon_Manager::get_active_addons());

        return [
            ['etiqueta' => __('Perfil seleccionado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'completado' => $id_perfil_activo !== 'personalizado'],
            ['etiqueta' => __('Modulos activos', FLAVOR_PLATFORM_TEXT_DOMAIN),     'completado' => count($modulos_activos) > 0],
            ['etiqueta' => __('IA configurada', FLAVOR_PLATFORM_TEXT_DOMAIN),      'completado' => $tiene_api_key],
            ['etiqueta' => __('Paginas creadas', FLAVOR_PLATFORM_TEXT_DOMAIN),     'completado' => $conteo_paginas_creadas > 0],
            ['etiqueta' => __('Addons instalados', FLAVOR_PLATFORM_TEXT_DOMAIN),   'completado' => $conteo_addons > 0],
        ];
    }

    /**
     * Calcula el progreso del onboarding
     *
     * @param array $checks_onboarding
     * @return int Porcentaje 0-100
     */
    private function calcular_progreso_onboarding($checks_onboarding) {
        $completados = count(array_filter($checks_onboarding, function ($check) {
            return $check['completado'];
        }));
        return (int) round(($completados / count($checks_onboarding)) * 100);
    }

    /**
     * Obtiene el nivel del semaforo de salud
     *
     * @return array
     */
    private function obtener_semaforo_salud() {
        $configuracion = flavor_get_main_settings();
        $tiene_api_key = !empty($configuracion['claude_api_key']) || !empty($configuracion['openai_api_key']) || !empty($configuracion['deepseek_api_key']) || !empty($configuracion['mistral_api_key']);
        $chat_habilitado = !empty($configuracion['enabled']);

        if ($tiene_api_key && $chat_habilitado) {
            return ['nivel' => 'verde', 'icono' => 'dashicons-yes-alt', 'mensaje' => __('Todo funcionando correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        } elseif ($tiene_api_key) {
            return ['nivel' => 'amarillo', 'icono' => 'dashicons-warning', 'mensaje' => __('Asistente IA deshabilitado', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }
        return ['nivel' => 'rojo', 'icono' => 'dashicons-dismiss', 'mensaje' => __('API key no configurada', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }

    /**
     * Obtiene estado detallado del sistema
     *
     * @return array
     */
    private function obtener_estado_sistema() {
        global $wpdb;

        // Espacio en disco usado por uploads
        $directorio_uploads = wp_upload_dir();
        $espacio_usado = $this->calcular_tamano_directorio($directorio_uploads['basedir']);

        // Estado de la API
        $configuracion = flavor_get_main_settings();
        $estado_api = 'sin_configurar';
        if (!empty($configuracion['claude_api_key']) || !empty($configuracion['openai_api_key'])) {
            $estado_api = 'configurada';
            // Verificar ultima llamada exitosa
            $ultimo_uso_api = get_transient('flavor_last_api_call');
            if ($ultimo_uso_api) {
                $estado_api = 'activa';
            }
        }

        // Ultima sincronizacion con red de nodos
        $ultima_sincronizacion = get_option('flavor_last_node_sync', null);

        // Conteo de tablas del plugin
        $tablas_flavor = $wpdb->get_var(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE()
             AND table_name LIKE '{$wpdb->prefix}flavor_%'"
        );

        return [
            'version_plugin'      => FLAVOR_PLATFORM_VERSION,
            'version_php'         => PHP_VERSION,
            'version_wordpress'   => get_bloginfo('version'),
            'version_mysql'       => $wpdb->db_version(),
            'estado_api'          => $estado_api,
            'espacio_uploads'     => $this->formatear_bytes($espacio_usado),
            'espacio_uploads_raw' => $espacio_usado,
            'ultima_sincronizacion' => $ultima_sincronizacion ? human_time_diff(strtotime($ultima_sincronizacion)) : __('Nunca', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tablas_plugin'       => intval($tablas_flavor),
            'memoria_limite'      => ini_get('memory_limit'),
            'max_upload'          => ini_get('upload_max_filesize'),
        ];
    }

    /**
     * Calcula el tamano de un directorio recursivamente
     *
     * @param string $ruta_directorio
     * @return int Tamano en bytes
     */
    private function calcular_tamano_directorio($ruta_directorio) {
        $tamano_total = 0;

        if (!is_dir($ruta_directorio)) {
            return 0;
        }

        $iterador = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($ruta_directorio, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterador as $archivo) {
            if ($archivo->isFile()) {
                $tamano_total += $archivo->getSize();
            }
        }

        return $tamano_total;
    }

    /**
     * Formatea bytes a unidad legible
     *
     * @param int $bytes
     * @return string
     */
    private function formatear_bytes($bytes) {
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $potencia = floor(($bytes ? log($bytes) : 0) / log(1024));
        $potencia = min($potencia, count($unidades) - 1);
        $bytes /= pow(1024, $potencia);

        return round($bytes, 2) . ' ' . $unidades[$potencia];
    }

    /**
     * Obtiene alertas y notificaciones pendientes
     *
     * @return array
     */
    private function obtener_alertas_pendientes() {
        $alertas = [];

        // Pedidos sin procesar (si grupos_consumo activo)
        if (class_exists('Flavor_Platform_Module_Loader') && Flavor_Platform_Module_Loader::is_module_active('grupos_consumo')) {
            $pedidos_pendientes = $this->contar_pedidos_pendientes();
            if ($pedidos_pendientes > 0) {
                $alertas[] = [
                    'tipo'    => 'warning',
                    'icono'   => 'dashicons-cart',
                    'mensaje' => sprintf(__('%d pedidos sin procesar', FLAVOR_PLATFORM_TEXT_DOMAIN), $pedidos_pendientes),
                    'url'     => admin_url('admin.php?page=grupos-consumo-dashboard&tab=pedidos'),
                ];
            }
        }

        // Solicitudes de socio pendientes (si socios activo)
        if (class_exists('Flavor_Platform_Module_Loader') && Flavor_Platform_Module_Loader::is_module_active('socios')) {
            $solicitudes_pendientes = $this->contar_solicitudes_socios_pendientes();
            if ($solicitudes_pendientes > 0) {
                $alertas[] = [
                    'tipo'    => 'info',
                    'icono'   => 'dashicons-groups',
                    'mensaje' => sprintf(__('%d solicitudes de miembro pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN), $solicitudes_pendientes),
                    'url'     => admin_url('admin.php?page=socios-dashboard&tab=solicitudes'),
                ];
            }
        }

        // Ciclos proximos a cerrar (grupos_consumo)
        if (class_exists('Flavor_Platform_Module_Loader') && Flavor_Platform_Module_Loader::is_module_active('grupos_consumo')) {
            $ciclos_por_cerrar = $this->obtener_ciclos_proximos_cierre();
            if (!empty($ciclos_por_cerrar)) {
                $alertas[] = [
                    'tipo'    => 'warning',
                    'icono'   => 'dashicons-calendar-alt',
                    'mensaje' => sprintf(__('%d ciclos cierran en las proximas 48h', FLAVOR_PLATFORM_TEXT_DOMAIN), count($ciclos_por_cerrar)),
                    'url'     => admin_url('admin.php?page=grupos-consumo-dashboard&tab=ciclos'),
                ];
            }
        }

        // Eventos proximos (si eventos activo)
        if (class_exists('Flavor_Platform_Module_Loader') && Flavor_Platform_Module_Loader::is_module_active('eventos')) {
            $eventos_proximos = $this->contar_eventos_proximos();
            if ($eventos_proximos > 0) {
                $alertas[] = [
                    'tipo'    => 'info',
                    'icono'   => 'dashicons-calendar',
                    'mensaje' => sprintf(__('%d eventos en los proximos 7 dias', FLAVOR_PLATFORM_TEXT_DOMAIN), $eventos_proximos),
                    'url'     => admin_url('admin.php?page=eventos-dashboard'),
                ];
            }
        }

        // Verificar actualizaciones disponibles
        $actualizacion_disponible = get_transient('flavor_update_available');
        if ($actualizacion_disponible) {
            $alertas[] = [
                'tipo'    => 'success',
                'icono'   => 'dashicons-update',
                'mensaje' => sprintf(__('Actualizacion disponible: v%s', FLAVOR_PLATFORM_TEXT_DOMAIN), $actualizacion_disponible),
                'url'     => admin_url('plugins.php'),
            ];
        }

        // Incidencias sin resolver
        if (class_exists('Flavor_Platform_Module_Loader') && Flavor_Platform_Module_Loader::is_module_active('incidencias')) {
            $incidencias_abiertas = $this->contar_incidencias_abiertas();
            if ($incidencias_abiertas > 0) {
                $alertas[] = [
                    'tipo'    => 'error',
                    'icono'   => 'dashicons-warning',
                    'mensaje' => sprintf(__('%d incidencias sin resolver', FLAVOR_PLATFORM_TEXT_DOMAIN), $incidencias_abiertas),
                    'url'     => admin_url('admin.php?page=incidencias-dashboard'),
                ];
            }
        }

        return $alertas;
    }

    /**
     * Cuenta pedidos pendientes de grupos de consumo
     *
     * @return int
     */
    private function contar_pedidos_pendientes() {
        global $wpdb;
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        if (!$this->tabla_existe($tabla_pedidos)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_pedidos} WHERE estado = 'pendiente'"
        );
    }

    /**
     * Cuenta solicitudes de socios pendientes
     *
     * @return int
     */
    private function contar_solicitudes_socios_pendientes() {
        global $wpdb;
        $tabla_solicitudes = $wpdb->prefix . 'flavor_socios_solicitudes';

        if (!$this->tabla_existe($tabla_solicitudes)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_solicitudes} WHERE estado = 'pendiente'"
        );
    }

    /**
     * Obtiene ciclos que cierran en las proximas 48 horas
     *
     * @return array
     */
    private function obtener_ciclos_proximos_cierre() {
        global $wpdb;
        $tabla_ciclos = $wpdb->prefix . 'flavor_gc_ciclos';

        if (!$this->tabla_existe($tabla_ciclos)) {
            return [];
        }

        $fecha_limite = gmdate('Y-m-d H:i:s', strtotime('+48 hours'));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla_ciclos}
             WHERE estado = 'abierto'
             AND fecha_cierre <= %s
             ORDER BY fecha_cierre ASC",
            $fecha_limite
        ));
    }

    /**
     * Cuenta eventos en los proximos 7 dias
     *
     * @return int
     */
    private function contar_eventos_proximos() {
        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        if (!$this->tabla_existe($tabla_eventos)) {
            return 0;
        }

        $fecha_inicio = current_time('Y-m-d');
        $fecha_fin = gmdate('Y-m-d', strtotime('+7 days'));

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_eventos}
             WHERE fecha_inicio >= %s AND fecha_inicio <= %s
             AND estado = 'publicado'",
            $fecha_inicio,
            $fecha_fin
        ));
    }

    /**
     * Cuenta incidencias abiertas
     *
     * @return int
     */
    private function contar_incidencias_abiertas() {
        global $wpdb;
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        if (!$this->tabla_existe($tabla_incidencias)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_incidencias} WHERE estado IN ('abierta', 'en_progreso')"
        );
    }

    /**
     * Obtiene las ultimas conversaciones y actividad
     *
     * @return array
     */
    private function obtener_actividad_reciente() {
        // Usar el sistema de Activity Log si esta disponible
        if (class_exists('Flavor_Activity_Log')) {
            $activity_log = Flavor_Activity_Log::get_instance();
            $registros = $activity_log->obtener_actividad_reciente(10);

            $actividad_formateada = [];
            foreach ($registros as $registro) {
                $actividad_formateada[] = [
                    'tipo'     => $registro->tipo,
                    'icono'    => $this->obtener_icono_actividad($registro->tipo, $registro->modulo_id),
                    'titulo'   => $registro->titulo,
                    'modulo'   => $registro->modulo_id,
                    'usuario'  => $registro->nombre_usuario,
                    'tiempo'   => human_time_diff(strtotime($registro->fecha), current_time('timestamp')),
                    'fecha'    => $registro->fecha,
                ];
            }

            return $actividad_formateada;
        }

        // Fallback: conversaciones del chat
        global $wpdb;
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversations';

        if (!$this->tabla_existe($tabla_conversaciones)) {
            return [];
        }

        $conversaciones_recientes = $wpdb->get_results(
            "SELECT id, session_id, message_count, started_at FROM {$tabla_conversaciones} ORDER BY started_at DESC LIMIT 10",
            ARRAY_A
        );

        $actividad_formateada = [];
        foreach ($conversaciones_recientes as $conversacion) {
            $actividad_formateada[] = [
                'tipo'    => 'info',
                'icono'   => 'dashicons-format-chat',
                'titulo'  => sprintf(
                    __('Conversacion #%s (%d mensajes)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    substr($conversacion['session_id'], 0, 8),
                    $conversacion['message_count']
                ),
                'modulo'  => 'chat',
                'usuario' => '',
                'tiempo'  => human_time_diff(strtotime($conversacion['started_at']), current_time('timestamp')),
                'fecha'   => $conversacion['started_at'],
            ];
        }

        return $actividad_formateada;
    }

    /**
     * Obtiene icono segun tipo de actividad
     *
     * @param string $tipo_actividad
     * @param string $modulo_id
     * @return string
     */
    private function obtener_icono_actividad($tipo_actividad, $modulo_id) {
        $iconos_modulo = [
            'chat'           => 'dashicons-format-chat',
            'eventos'        => 'dashicons-calendar',
            'socios'         => 'dashicons-groups',
            'grupos_consumo' => 'dashicons-cart',
            'incidencias'    => 'dashicons-warning',
            'sistema'        => 'dashicons-admin-generic',
        ];

        $iconos_tipo = [
            'error'       => 'dashicons-dismiss',
            'advertencia' => 'dashicons-warning',
            'exito'       => 'dashicons-yes-alt',
            'info'        => 'dashicons-info',
        ];

        if (isset($iconos_tipo[$tipo_actividad])) {
            return $iconos_tipo[$tipo_actividad];
        }

        return $iconos_modulo[$modulo_id] ?? 'dashicons-marker';
    }

    /**
     * Obtiene acciones rapidas completas
     *
     * @param string $id_perfil
     * @return array
     */
    private function obtener_acciones_rapidas_completas($id_perfil) {
        $configuracion = flavor_get_main_settings();
        $modulos_activos = $configuracion['active_modules'] ?? [];

        $acciones = [
            'principales' => [
                [
                    'id'       => 'configuracion',
                    'etiqueta' => __('Configuracion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icono'    => 'dashicons-admin-settings',
                    'url'      => admin_url('admin.php?page=flavor-platform-settings'),
                    'color'    => '#2271b1',
                ],
                [
                    'id'       => 'modulos',
                    'etiqueta' => __('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icono'    => 'dashicons-screenoptions',
                    'url'      => admin_url('admin.php?page=flavor-module-dashboards'),
                    'color'    => '#8e44ad',
                ],
                [
                    'id'       => 'addons',
                    'etiqueta' => __('Addons', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icono'    => 'dashicons-admin-plugins',
                    'url'      => admin_url('admin.php?page=flavor-addons'),
                    'color'    => '#27ae60',
                ],
            ],
            'contextuales' => [],
        ];

        // Acciones segun modulos activos
        if (in_array('eventos', $modulos_activos)) {
            $acciones['contextuales'][] = [
                'id'       => 'crear_evento',
                'etiqueta' => __('Crear Evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'    => 'dashicons-calendar-alt',
                'url'      => admin_url('admin.php?page=eventos-dashboard&action=nuevo'),
                'color'    => '#e74c3c',
                'modal'    => true,
            ];
        }

        if (in_array('grupos_consumo', $modulos_activos)) {
            $acciones['contextuales'][] = [
                'id'       => 'crear_producto',
                'etiqueta' => __('Crear Producto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'    => 'dashicons-products',
                'url'      => admin_url('admin.php?page=grupos-consumo-dashboard&action=nuevo_producto'),
                'color'    => '#f39c12',
                'modal'    => true,
            ];
        }

        if (in_array('socios', $modulos_activos)) {
            $acciones['contextuales'][] = [
                'id'       => 'ver_socios',
                'etiqueta' => __('Ver Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'    => 'dashicons-groups',
                'url'      => admin_url('admin.php?page=socios-dashboard'),
                'color'    => '#3498db',
            ];
        }

        // Acciones generales
        $acciones['generales'] = [
            [
                'id'       => 'enviar_notificacion',
                'etiqueta' => __('Enviar Notificacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'    => 'dashicons-megaphone',
                'url'      => '#',
                'color'    => '#9b59b6',
                'modal'    => true,
                'action'   => 'send_notification',
            ],
            [
                'id'       => 'exportar_datos',
                'etiqueta' => __('Exportar Datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'    => 'dashicons-download',
                'url'      => admin_url('admin.php?page=flavor-platform-export-import'),
                'color'    => '#1abc9c',
            ],
            [
                'id'       => 'ver_logs',
                'etiqueta' => __('Ver Logs', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono'    => 'dashicons-list-view',
                'url'      => admin_url('admin.php?page=flavor-platform-activity-log'),
                'color'    => '#7f8c8d',
            ],
        ];

        return $acciones;
    }

    /**
     * Obtiene estadisticas para el dashboard
     *
     * @return array
     */
    public function get_dashboard_stats() {
        global $wpdb;

        // Cache de estadisticas
        $cache_key = 'flavor_dashboard_stats';
        $estadisticas = get_transient($cache_key);

        if ($estadisticas !== false) {
            return $estadisticas;
        }

        // Usuarios activos (ultimos 30 dias)
        $usuarios_activos_30d = $this->contar_usuarios_activos(30);

        // Modulos
        $configuracion = flavor_get_main_settings();
        $modulos_activos = $configuracion['active_modules'] ?? [];
        $total_modulos = 0;
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $total_modulos = count(Flavor_Platform_Module_Loader::get_instance()->get_registered_modules());
        }

        // Addons activos
        $addons_activos = count(Flavor_Addon_Manager::get_active_addons());
        $addons_totales = count(Flavor_Addon_Manager::get_registered_addons());

        // Conversaciones
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversations';
        $conversaciones = 0;
        if ($this->tabla_existe($tabla_conversaciones)) {
            $conversaciones = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_conversaciones}");
        }

        // Mensajes
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_messages';
        $mensajes = 0;
        if ($this->tabla_existe($tabla_mensajes)) {
            $mensajes = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_mensajes}");
        }

        // Eventos proximos (si modulo activo)
        $eventos_proximos = 0;
        if (in_array('eventos', $modulos_activos)) {
            $eventos_proximos = $this->contar_eventos_proximos();
        }

        // Pedidos pendientes (si grupos_consumo activo)
        $pedidos_pendientes = 0;
        if (in_array('grupos_consumo', $modulos_activos)) {
            $pedidos_pendientes = $this->contar_pedidos_pendientes();
        }

        // Socios activos (si socios activo)
        $socios_activos = 0;
        if (in_array('socios', $modulos_activos)) {
            $socios_activos = $this->contar_socios_activos();
        }

        $estadisticas = [
            'usuarios_activos_30d' => $usuarios_activos_30d,
            'modulos_activos'      => count($modulos_activos),
            'modulos_totales'      => $total_modulos,
            'addons_activos'       => $addons_activos,
            'addons_totales'       => $addons_totales,
            'conversaciones'       => number_format_i18n($conversaciones),
            'conversaciones_raw'   => intval($conversaciones),
            'mensajes'             => number_format_i18n($mensajes),
            'mensajes_raw'         => intval($mensajes),
            'eventos_proximos'     => $eventos_proximos,
            'pedidos_pendientes'   => $pedidos_pendientes,
            'socios_activos'       => $socios_activos,
            'timestamp'            => current_time('timestamp'),
        ];

        // Cachear por 5 minutos
        set_transient($cache_key, $estadisticas, 5 * MINUTE_IN_SECONDS);

        return $estadisticas;
    }

    /**
     * Cuenta usuarios activos en los ultimos N dias
     *
     * @param int $dias_limite
     * @return int
     */
    private function contar_usuarios_activos($dias_limite = 30) {
        global $wpdb;

        $fecha_limite = gmdate('Y-m-d H:i:s', strtotime("-{$dias_limite} days"));
        $timestamp_limite = strtotime("-{$dias_limite} days");

        // Aproximación consistente:
        // - usuarios con last_activity reciente
        // - o usuarios registrados dentro del periodo
        // Evita el falso fallback al total de usuarios.
        $conteo_usuarios = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id)
             FROM (
                 SELECT ID AS user_id
                 FROM {$wpdb->users}
                 WHERE user_registered >= %s

                 UNION

                 SELECT user_id
                 FROM {$wpdb->usermeta}
                 WHERE meta_key = 'last_activity'
                 AND CAST(meta_value AS UNSIGNED) >= %d
             ) AS usuarios_activos",
            $fecha_limite,
            $timestamp_limite
        ));

        // Fallback razonable: si no hay señales de actividad, contar solo usuarios recientes.
        if (!$conteo_usuarios) {
            $conteo_usuarios = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered >= %s",
                $fecha_limite
            ));
        }

        return intval($conteo_usuarios);
    }

    /**
     * Cuenta socios activos
     *
     * @return int
     */
    private function contar_socios_activos() {
        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        if (!$this->tabla_existe($tabla_socios)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_socios} WHERE estado = 'activo'"
        );
    }

    /**
     * Obtiene datos para graficos
     *
     * @return array
     */
    public function obtener_datos_graficos() {
        return [
            'usuarios_por_semana'   => $this->obtener_usuarios_nuevos_por_semana(),
            'actividad_por_modulo'  => $this->obtener_actividad_por_modulo(),
            'distribucion_roles'    => $this->obtener_distribucion_roles(),
        ];
    }

    /**
     * Obtiene usuarios nuevos por semana (ultimas 8 semanas)
     *
     * @return array
     */
    private function obtener_usuarios_nuevos_por_semana() {
        global $wpdb;

        $datos_semanas = [];
        $etiquetas = [];

        for ($semana = 7; $semana >= 0; $semana--) {
            $fecha_inicio = gmdate('Y-m-d', strtotime("-{$semana} weeks monday"));
            $fecha_fin = gmdate('Y-m-d', strtotime("-{$semana} weeks sunday"));

            $conteo_usuarios = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->users}
                 WHERE user_registered >= %s AND user_registered <= %s",
                $fecha_inicio . ' 00:00:00',
                $fecha_fin . ' 23:59:59'
            ));

            $datos_semanas[] = intval($conteo_usuarios);
            $etiquetas[] = gmdate('d M', strtotime($fecha_inicio));
        }

        return [
            'etiquetas' => $etiquetas,
            'datos'     => $datos_semanas,
        ];
    }

    /**
     * Obtiene actividad por modulo
     *
     * @return array
     */
    private function obtener_actividad_por_modulo() {
        if (!class_exists('Flavor_Activity_Log')) {
            return ['etiquetas' => [], 'datos' => []];
        }

        $activity_log = Flavor_Activity_Log::get_instance();
        $resumen = $activity_log->obtener_resumen(30);

        $etiquetas = [];
        $datos = [];
        $colores = [];

        $colores_modulo = [
            'chat'           => '#3498db',
            'eventos'        => '#e74c3c',
            'socios'         => '#27ae60',
            'grupos_consumo' => '#f39c12',
            'incidencias'    => '#9b59b6',
            'sistema'        => '#95a5a6',
        ];

        foreach ($resumen['por_modulo'] as $registro) {
            $etiquetas[] = ucfirst(str_replace('_', ' ', $registro->modulo_id));
            $datos[] = intval($registro->total);
            $colores[] = $colores_modulo[$registro->modulo_id] ?? '#7f8c8d';
        }

        return [
            'etiquetas' => $etiquetas,
            'datos'     => $datos,
            'colores'   => $colores,
        ];
    }

    /**
     * Obtiene distribucion de roles de usuario
     *
     * @return array
     */
    private function obtener_distribucion_roles() {
        $conteo_usuarios = count_users();
        $datos_roles = $conteo_usuarios['avail_roles'];

        $etiquetas = [];
        $datos = [];
        $colores = [
            'administrator' => '#e74c3c',
            'editor'        => '#3498db',
            'author'        => '#27ae60',
            'contributor'   => '#f39c12',
            'subscriber'    => '#9b59b6',
            'socio'         => '#1abc9c',
            'cliente'       => '#e67e22',
        ];

        $nombres_roles = [
            'administrator' => __('Administrador', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'editor'        => __('Editor', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'author'        => __('Autor', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'contributor'   => __('Colaborador', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subscriber'    => __('Suscriptor', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'socio'         => __('Miembro', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cliente'       => __('Cliente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        $colores_grafico = [];

        foreach ($datos_roles as $rol => $conteo) {
            if ($conteo > 0) {
                $etiquetas[] = $nombres_roles[$rol] ?? ucfirst($rol);
                $datos[] = $conteo;
                $colores_grafico[] = $colores[$rol] ?? '#7f8c8d';
            }
        }

        return [
            'etiquetas' => $etiquetas,
            'datos'     => $datos,
            'colores'   => $colores_grafico,
        ];
    }

    // =========================================================================
    // RED DE COMUNIDADES - ESTADISTICAS Y DATOS
    // =========================================================================

    /**
     * Verifica si una tabla existe en la base de datos
     *
     * @param string $nombre_tabla Nombre completo de la tabla
     * @return bool
     */
    private function tabla_existe($nombre_tabla) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $resultado = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $nombre_tabla));
        return $resultado === $nombre_tabla;
    }

    /**
     * Obtiene estadisticas de la red de comunidades
     *
     * @return array
     */
    public function obtener_estadisticas_red() {
        global $wpdb;

        $cache_key = 'flavor_network_stats';
        $estadisticas_cacheadas = get_transient($cache_key);

        if ($estadisticas_cacheadas !== false) {
            return $estadisticas_cacheadas;
        }

        // Verificar si existe la tabla de nodos
        $tabla_nodos = $wpdb->prefix . 'flavor_network_nodes';
        $tabla_conexiones = $wpdb->prefix . 'flavor_network_connections';
        $tabla_mensajes = $wpdb->prefix . 'flavor_network_messages';
        $tabla_contenido = $wpdb->prefix . 'flavor_network_shared_content';

        // Valores por defecto
        $estadisticas = [
            'nodos_conectados'      => 0,
            'nodos_activos'         => 0,
            'nodos_inactivos'       => 0,
            'nodos_pendientes'      => 0,
            'conexiones_totales'    => 0,
            'conexiones_federadas'  => 0,
            'mensajes_enviados'     => 0,
            'mensajes_recibidos'    => 0,
            'mensajes_sin_leer'     => 0,
            'contenido_compartido'  => 0,
            'ultima_sincronizacion' => null,
            'nodo_local'            => null,
            'alertas_nodos'         => [],
            'nodos_recientes'       => [],
        ];

        // Verificar existencia de tablas
        if (!$this->tabla_existe($tabla_nodos)) {
            return $estadisticas;
        }

        // Obtener nodo local
        $nodo_local = $wpdb->get_row(
            "SELECT * FROM {$tabla_nodos} WHERE es_nodo_local = 1 LIMIT 1"
        );

        if ($nodo_local) {
            $estadisticas['nodo_local'] = [
                'id'                    => (int) $nodo_local->id,
                'nombre'                => $nodo_local->nombre,
                'slug'                  => $nodo_local->slug,
                'estado'                => $nodo_local->estado,
                'ultima_sincronizacion' => $nodo_local->ultima_sincronizacion,
                'verificado'            => (bool) $nodo_local->verificado,
            ];
        }

        // Contar nodos por estado
        $conteos_nodos = $wpdb->get_results(
            "SELECT estado, COUNT(*) as total FROM {$tabla_nodos} WHERE es_nodo_local = 0 GROUP BY estado"
        );

        foreach ($conteos_nodos as $conteo) {
            switch ($conteo->estado) {
                case 'activo':
                    $estadisticas['nodos_activos'] = (int) $conteo->total;
                    break;
                case 'inactivo':
                    $estadisticas['nodos_inactivos'] = (int) $conteo->total;
                    break;
                case 'pendiente':
                    $estadisticas['nodos_pendientes'] = (int) $conteo->total;
                    break;
            }
        }

        $estadisticas['nodos_conectados'] = $estadisticas['nodos_activos'] + $estadisticas['nodos_inactivos'] + $estadisticas['nodos_pendientes'];

        // Contar conexiones
        if ($this->tabla_existe($tabla_conexiones)) {
            $estadisticas['conexiones_totales'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_conexiones} WHERE estado = 'aprobada'"
            );
            $estadisticas['conexiones_federadas'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_conexiones} WHERE estado = 'aprobada' AND nivel = 'federado'"
            );
        }

        // Contar mensajes
        if ($this->tabla_existe($tabla_mensajes) && $nodo_local) {
            $estadisticas['mensajes_enviados'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_mensajes} WHERE de_nodo_id = %d",
                $nodo_local->id
            ));
            $estadisticas['mensajes_recibidos'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_mensajes} WHERE a_nodo_id = %d",
                $nodo_local->id
            ));
            $estadisticas['mensajes_sin_leer'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_mensajes} WHERE a_nodo_id = %d AND leido = 0",
                $nodo_local->id
            ));
        }

        // Contar contenido compartido
        if ($this->tabla_existe($tabla_contenido)) {
            $estadisticas['contenido_compartido'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_contenido} WHERE estado = 'activo'"
            );
        }

        // Ultima sincronizacion
        $ultima_sincronizacion = get_option('flavor_last_node_sync', null);
        $estadisticas['ultima_sincronizacion'] = $ultima_sincronizacion;

        // Alertas de nodos desconectados (sin sincronizar en ultimos 7 dias)
        $fecha_limite_alerta = gmdate('Y-m-d H:i:s', strtotime('-7 days'));
        $nodos_desconectados = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nombre, slug, ultima_sincronizacion
             FROM {$tabla_nodos}
             WHERE es_nodo_local = 0
             AND estado = 'activo'
             AND (ultima_sincronizacion IS NULL OR ultima_sincronizacion < %s)
             ORDER BY ultima_sincronizacion ASC
             LIMIT 10",
            $fecha_limite_alerta
        ));

        foreach ($nodos_desconectados as $nodo_desconectado) {
            $estadisticas['alertas_nodos'][] = [
                'id'                    => (int) $nodo_desconectado->id,
                'nombre'                => $nodo_desconectado->nombre,
                'slug'                  => $nodo_desconectado->slug,
                'ultima_sincronizacion' => $nodo_desconectado->ultima_sincronizacion,
                'dias_sin_sync'         => $nodo_desconectado->ultima_sincronizacion
                    ? round((time() - strtotime($nodo_desconectado->ultima_sincronizacion)) / DAY_IN_SECONDS)
                    : null,
            ];
        }

        // Nodos conectados recientemente
        $estadisticas['nodos_recientes'] = $wpdb->get_results(
            "SELECT id, nombre, slug, tipo_entidad, ciudad, pais, logo_url, fecha_registro, ultima_sincronizacion
             FROM {$tabla_nodos}
             WHERE es_nodo_local = 0 AND estado = 'activo'
             ORDER BY fecha_registro DESC
             LIMIT 5"
        );

        // Cachear por 5 minutos
        set_transient($cache_key, $estadisticas, 5 * MINUTE_IN_SECONDS);

        return $estadisticas;
    }

    /**
     * Obtiene modulos que comparten datos en la red
     *
     * @return array
     */
    public function obtener_modulos_compartidos() {
        global $wpdb;

        $modulos_compartidos = [];

        $tabla_contenido = $wpdb->prefix . 'flavor_network_shared_content';

        if (!$this->tabla_existe($tabla_contenido)) {
            return $modulos_compartidos;
        }

        // Verificar que las columnas necesarias existan
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $columnas = $wpdb->get_col("DESCRIBE {$tabla_contenido}", 0);
        if (!in_array('tipo', $columnas, true) || !in_array('estado', $columnas, true)) {
            return $modulos_compartidos;
        }

        // Obtener estadisticas por tipo de contenido
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $resultados = $wpdb->get_results(
            "SELECT tipo, COUNT(*) as total,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                    MAX(fecha_actualizacion) as ultima_actualizacion
             FROM {$tabla_contenido}
             GROUP BY tipo
             ORDER BY total DESC"
        );

        $iconos_tipo = [
            'producto'     => 'dashicons-cart',
            'servicio'     => 'dashicons-businessman',
            'espacio'      => 'dashicons-building',
            'recurso'      => 'dashicons-hammer',
            'evento'       => 'dashicons-calendar-alt',
            'banco_tiempo' => 'dashicons-clock',
            'saber'        => 'dashicons-book',
            'excedente'    => 'dashicons-update',
            'necesidad'    => 'dashicons-sos',
        ];

        $etiquetas_tipo = [
            'producto'     => __('Productos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'servicio'     => __('Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'espacio'      => __('Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'recurso'      => __('Recursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'evento'       => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'banco_tiempo' => __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'saber'        => __('Saberes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'excedente'    => __('Excedentes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'necesidad'    => __('Necesidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        foreach ($resultados as $fila) {
            $modulos_compartidos[] = [
                'tipo'                 => $fila->tipo,
                'etiqueta'             => $etiquetas_tipo[$fila->tipo] ?? ucfirst($fila->tipo),
                'icono'                => $iconos_tipo[$fila->tipo] ?? 'dashicons-admin-generic',
                'total'                => (int) $fila->total,
                'activos'              => (int) $fila->activos,
                'ultima_actualizacion' => $fila->ultima_actualizacion,
            ];
        }

        return $modulos_compartidos;
    }

    /**
     * Obtiene datos para el mapa de actividad geolocalizado
     *
     * @param array $filtros Filtros opcionales
     * @return array
     */
    public function obtener_datos_mapa_actividad($filtros = []) {
        global $wpdb;

        $tabla_nodos = $wpdb->prefix . 'flavor_network_nodes';
        $datos_mapa = [
            'nodos'       => [],
            'heatmap'     => [],
            'centro'      => ['lat' => 40.4168, 'lng' => -3.7038], // Default: Madrid
            'zoom'        => 6,
            'estadisticas_zona' => [],
        ];

        if (!$this->tabla_existe($tabla_nodos)) {
            return $datos_mapa;
        }

        // Obtener nodos con coordenadas
        // FIX: Siempre usar prepare() añadiendo placeholder dummy para consistencia
        $where_clauses = ["estado = 'activo'", "latitud IS NOT NULL", "longitud IS NOT NULL", "1 = %d"];
        $where_values = [1]; // Placeholder dummy para forzar uso de prepare()

        if (!empty($filtros['tipo_entidad'])) {
            $where_clauses[] = 'tipo_entidad = %s';
            $where_values[] = sanitize_text_field($filtros['tipo_entidad']);
        }

        if (!empty($filtros['pais'])) {
            $where_clauses[] = 'pais = %s';
            $where_values[] = sanitize_text_field($filtros['pais']);
        }

        $where_sql = implode(' AND ', $where_clauses);
        $sql_query = "SELECT id, nombre, slug, tipo_entidad, sector, latitud, longitud, ciudad, pais, logo_url, miembros_count
                FROM {$tabla_nodos}
                WHERE {$where_sql}";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query construida con valores literales y placeholders
        $nodos = $wpdb->get_results($wpdb->prepare($sql_query, $where_values));

        // Preparar datos de nodos para el mapa
        foreach ($nodos as $nodo) {
            $datos_mapa['nodos'][] = [
                'id'             => (int) $nodo->id,
                'nombre'         => $nodo->nombre,
                'slug'           => $nodo->slug,
                'tipo_entidad'   => $nodo->tipo_entidad,
                'sector'         => $nodo->sector,
                'lat'            => (float) $nodo->latitud,
                'lng'            => (float) $nodo->longitud,
                'ciudad'         => $nodo->ciudad,
                'pais'           => $nodo->pais,
                'logo_url'       => $nodo->logo_url,
                'miembros_count' => (int) $nodo->miembros_count,
            ];

            // Datos para heatmap (intensidad basada en miembros)
            $datos_mapa['heatmap'][] = [
                'lat'       => (float) $nodo->latitud,
                'lng'       => (float) $nodo->longitud,
                'intensity' => max(1, (int) $nodo->miembros_count),
            ];
        }

        // Calcular centro automatico si hay nodos
        if (!empty($datos_mapa['nodos'])) {
            $suma_lat = 0;
            $suma_lng = 0;
            foreach ($datos_mapa['nodos'] as $nodo_item) {
                $suma_lat += $nodo_item['lat'];
                $suma_lng += $nodo_item['lng'];
            }
            $cantidad_nodos = count($datos_mapa['nodos']);
            $datos_mapa['centro'] = [
                'lat' => $suma_lat / $cantidad_nodos,
                'lng' => $suma_lng / $cantidad_nodos,
            ];
        }

        // Estadisticas por zona (pais/ciudad)
        $estadisticas_zona = $wpdb->get_results(
            "SELECT pais, ciudad, COUNT(*) as total_nodos, SUM(miembros_count) as total_miembros
             FROM {$tabla_nodos}
             WHERE estado = 'activo'
             GROUP BY pais, ciudad
             ORDER BY total_nodos DESC
             LIMIT 10"
        );

        foreach ($estadisticas_zona as $zona) {
            $datos_mapa['estadisticas_zona'][] = [
                'pais'           => $zona->pais,
                'ciudad'         => $zona->ciudad,
                'total_nodos'    => (int) $zona->total_nodos,
                'total_miembros' => (int) $zona->total_miembros,
            ];
        }

        return $datos_mapa;
    }

    /**
     * Obtiene KPIs principales con tendencias
     *
     * @return array
     */
    public function obtener_kpis_principales() {
        global $wpdb;

        $kpis = [];

        // Usuario activos con tendencia
        $usuarios_actuales = $this->contar_usuarios_activos(30);
        $usuarios_periodo_anterior = $this->contar_usuarios_activos_periodo(60, 30);
        $tendencia_usuarios = $usuarios_periodo_anterior > 0
            ? round((($usuarios_actuales - $usuarios_periodo_anterior) / $usuarios_periodo_anterior) * 100, 1)
            : 0;

        $kpis['usuarios'] = [
            'valor'           => $usuarios_actuales,
            'etiqueta'        => __('Usuarios activos (30d)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono'           => 'dashicons-admin-users',
            'tendencia'       => $tendencia_usuarios,
            'tendencia_tipo'  => $tendencia_usuarios >= 0 ? 'positiva' : 'negativa',
            'periodo'         => __('vs. mes anterior', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        // Conversaciones con tendencia
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversations';
        $conversaciones_mes = 0;
        $conversaciones_anterior = 0;

        if ($this->tabla_existe($tabla_conversaciones)) {
            $fecha_inicio_mes = gmdate('Y-m-01');
            $fecha_inicio_anterior = gmdate('Y-m-01', strtotime('-1 month'));

            $conversaciones_mes = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_conversaciones} WHERE started_at >= %s",
                $fecha_inicio_mes . ' 00:00:00'
            ));

            $conversaciones_anterior = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_conversaciones}
                 WHERE started_at >= %s AND started_at < %s",
                $fecha_inicio_anterior . ' 00:00:00',
                $fecha_inicio_mes . ' 00:00:00'
            ));
        }

        $tendencia_conversaciones = $conversaciones_anterior > 0
            ? round((($conversaciones_mes - $conversaciones_anterior) / $conversaciones_anterior) * 100, 1)
            : 0;

        $kpis['conversaciones'] = [
            'valor'           => $conversaciones_mes,
            'etiqueta'        => __('Conversaciones este mes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono'           => 'dashicons-format-chat',
            'tendencia'       => $tendencia_conversaciones,
            'tendencia_tipo'  => $tendencia_conversaciones >= 0 ? 'positiva' : 'negativa',
            'periodo'         => __('vs. mes anterior', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        // Nodos de red
        $estadisticas_red = $this->obtener_estadisticas_red();
        $kpis['nodos_red'] = [
            'valor'           => $estadisticas_red['nodos_activos'],
            'etiqueta'        => __('Nodos activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono'           => 'dashicons-networking',
            'tendencia'       => 0,
            'tendencia_tipo'  => 'neutral',
            'subtitulo'       => sprintf(__('%d conexiones federadas', FLAVOR_PLATFORM_TEXT_DOMAIN), $estadisticas_red['conexiones_federadas']),
        ];

        // Contenido compartido
        $kpis['contenido_compartido'] = [
            'valor'           => $estadisticas_red['contenido_compartido'],
            'etiqueta'        => __('Items compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono'           => 'dashicons-share',
            'tendencia'       => 0,
            'tendencia_tipo'  => 'neutral',
            'subtitulo'       => __('En la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $kpis;
    }

    /**
     * Cuenta usuarios activos en un periodo especifico
     *
     * @param int $dias_inicio Dias desde hoy
     * @param int $dias_fin Dias desde hoy
     * @return int
     */
    private function contar_usuarios_activos_periodo($dias_inicio, $dias_fin) {
        global $wpdb;

        $fecha_inicio = gmdate('Y-m-d H:i:s', strtotime("-{$dias_inicio} days"));
        $fecha_fin = gmdate('Y-m-d H:i:s', strtotime("-{$dias_fin} days"));

        $conteo_usuarios = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->users}
             WHERE user_registered >= %s AND user_registered < %s",
            $fecha_inicio,
            $fecha_fin
        ));

        return intval($conteo_usuarios ?: 0);
    }

    /**
     * Obtiene comparativas temporales para graficos
     *
     * @return array
     */
    public function obtener_comparativas_temporales() {
        global $wpdb;

        $comparativas = [
            'usuarios_mensual'       => $this->obtener_usuarios_mensual(),
            'conversaciones_semanal' => $this->obtener_conversaciones_semanal(),
            'actividad_por_hora'     => $this->obtener_actividad_por_hora(),
        ];

        return $comparativas;
    }

    /**
     * Obtiene usuarios registrados por mes (ultimos 6 meses)
     *
     * @return array
     */
    private function obtener_usuarios_mensual() {
        global $wpdb;

        $datos = ['etiquetas' => [], 'actual' => [], 'anterior' => []];

        for ($mes = 5; $mes >= 0; $mes--) {
            $fecha_mes = strtotime("-{$mes} months");
            $fecha_inicio = gmdate('Y-m-01', $fecha_mes);
            $fecha_fin = gmdate('Y-m-t', $fecha_mes);

            $conteo = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->users}
                 WHERE user_registered >= %s AND user_registered <= %s",
                $fecha_inicio . ' 00:00:00',
                $fecha_fin . ' 23:59:59'
            ));

            $datos['etiquetas'][] = date_i18n('M', $fecha_mes);
            $datos['actual'][] = (int) $conteo;
        }

        return $datos;
    }

    /**
     * Obtiene conversaciones por semana (ultimas 8 semanas)
     *
     * @return array
     */
    private function obtener_conversaciones_semanal() {
        global $wpdb;

        $datos = ['etiquetas' => [], 'valores' => []];
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversations';

        if (!$this->tabla_existe($tabla_conversaciones)) {
            return $datos;
        }

        for ($semana = 7; $semana >= 0; $semana--) {
            $fecha_inicio = gmdate('Y-m-d', strtotime("-{$semana} weeks monday"));
            $fecha_fin = gmdate('Y-m-d', strtotime("-{$semana} weeks sunday"));

            $conteo = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_conversaciones}
                 WHERE started_at >= %s AND started_at <= %s",
                $fecha_inicio . ' 00:00:00',
                $fecha_fin . ' 23:59:59'
            ));

            $datos['etiquetas'][] = date_i18n('d M', strtotime($fecha_inicio));
            $datos['valores'][] = (int) $conteo;
        }

        return $datos;
    }

    /**
     * Obtiene actividad por hora del dia (ultimos 7 dias)
     *
     * @return array
     */
    private function obtener_actividad_por_hora() {
        global $wpdb;

        $datos = ['etiquetas' => [], 'valores' => []];
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversations';

        if (!$this->tabla_existe($tabla_conversaciones)) {
            // Generar datos por defecto
            for ($hora = 0; $hora < 24; $hora++) {
                $datos['etiquetas'][] = sprintf('%02d:00', $hora);
                $datos['valores'][] = 0;
            }
            return $datos;
        }

        $fecha_limite = gmdate('Y-m-d H:i:s', strtotime('-7 days'));

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT HOUR(started_at) as hora, COUNT(*) as total
             FROM {$tabla_conversaciones}
             WHERE started_at >= %s
             GROUP BY HOUR(started_at)
             ORDER BY hora ASC",
            $fecha_limite
        ));

        $actividad_por_hora = array_fill(0, 24, 0);
        foreach ($resultados as $fila) {
            $actividad_por_hora[(int) $fila->hora] = (int) $fila->total;
        }

        for ($hora = 0; $hora < 24; $hora++) {
            $datos['etiquetas'][] = sprintf('%02d:00', $hora);
            $datos['valores'][] = $actividad_por_hora[$hora];
        }

        return $datos;
    }

    /**
     * Sincroniza manualmente con la red de nodos
     *
     * @return array
     */
    public function sincronizar_red_manual() {
        if (class_exists('Flavor_Network_Manager')) {
            $gestor_red = Flavor_Network_Manager::get_instance();
            $gestor_red->sync_with_peers();

            update_option('flavor_last_node_sync', current_time('mysql'));

            // Limpiar cache de estadisticas
            delete_transient('flavor_network_stats');
            delete_transient('flavor_dashboard_stats');

            return [
                'success'    => true,
                'message'    => __('Sincronizacion completada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'timestamp'  => current_time('c'),
            ];
        }

        return [
            'success' => false,
            'message' => __('El sistema de red no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    // =========================================================================
    // ENDPOINTS API REST
    // =========================================================================

    /**
     * Endpoint API para estadisticas de red
     *
     * @param WP_REST_Request $peticion
     * @return WP_REST_Response
     */
    public function api_obtener_estadisticas_red($peticion) {
        $estadisticas_red = $this->obtener_estadisticas_red();
        $modulos_compartidos = $this->obtener_modulos_compartidos();
        $kpis = $this->obtener_kpis_principales();

        return new WP_REST_Response([
            'success' => true,
            'data'    => [
                'estadisticas'         => $estadisticas_red,
                'modulos_compartidos'  => $modulos_compartidos,
                'kpis'                 => $kpis,
                'timestamp'            => current_time('c'),
            ],
        ], 200);
    }

    /**
     * Endpoint API para sincronizacion manual de red
     *
     * @param WP_REST_Request $peticion
     * @return WP_REST_Response
     */
    public function api_sincronizar_red($peticion) {
        // FIX: Rate limiting para prevenir abuso
        $rate_check = $this->check_rate_limit( 'network_sync' );
        if ( $rate_check instanceof WP_REST_Response ) {
            return $rate_check;
        }

        $resultado = $this->sincronizar_red_manual();

        return new WP_REST_Response($resultado, $resultado['success'] ? 200 : 500);
    }

    /**
     * Endpoint API para mapa de actividad
     *
     * @param WP_REST_Request $peticion
     * @return WP_REST_Response
     */
    public function api_obtener_mapa_actividad($peticion) {
        $filtros = [
            'tipo_entidad' => $peticion->get_param('tipo'),
            'pais'         => $peticion->get_param('pais'),
            'modulo'       => $peticion->get_param('modulo'),
        ];

        $filtros = array_filter($filtros);

        $datos_mapa = $this->obtener_datos_mapa_actividad($filtros);

        return new WP_REST_Response([
            'success' => true,
            'data'    => $datos_mapa,
        ], 200);
    }

    /**
     * Endpoint API para exportar estadisticas a CSV
     *
     * @param WP_REST_Request $peticion
     * @return WP_REST_Response
     */
    public function api_exportar_estadisticas_csv($peticion) {
        // FIX: Rate limiting para prevenir abuso de exportaciones
        $rate_check = $this->check_rate_limit( 'export_stats' );
        if ( $rate_check instanceof WP_REST_Response ) {
            return $rate_check;
        }

        $tipo = sanitize_text_field($peticion->get_param('tipo') ?? 'general');

        $datos_exportar = [];
        $nombre_archivo = 'flavor_stats_' . gmdate('Y-m-d') . '.csv';

        switch ($tipo) {
            case 'usuarios':
                $datos_exportar = $this->exportar_estadisticas_usuarios();
                $nombre_archivo = 'flavor_usuarios_' . gmdate('Y-m-d') . '.csv';
                break;
            case 'red':
                $datos_exportar = $this->exportar_estadisticas_red();
                $nombre_archivo = 'flavor_red_' . gmdate('Y-m-d') . '.csv';
                break;
            case 'conversaciones':
                $datos_exportar = $this->exportar_estadisticas_conversaciones();
                $nombre_archivo = 'flavor_conversaciones_' . gmdate('Y-m-d') . '.csv';
                break;
            default:
                $datos_exportar = $this->exportar_estadisticas_generales();
        }

        return new WP_REST_Response([
            'success'  => true,
            'data'     => $datos_exportar,
            'filename' => $nombre_archivo,
        ], 200);
    }

    /**
     * Exporta estadisticas generales para CSV
     *
     * @return array
     */
    private function exportar_estadisticas_generales() {
        $estadisticas = $this->get_dashboard_stats();
        $estadisticas_red = $this->obtener_estadisticas_red();

        return [
            ['Metrica', 'Valor', 'Fecha'],
            ['Usuarios activos (30d)', $estadisticas['usuarios_activos_30d'], current_time('Y-m-d')],
            ['Modulos activos', $estadisticas['modulos_activos'], current_time('Y-m-d')],
            ['Conversaciones totales', $estadisticas['conversaciones_raw'], current_time('Y-m-d')],
            ['Mensajes totales', $estadisticas['mensajes_raw'], current_time('Y-m-d')],
            ['Nodos en red', $estadisticas_red['nodos_conectados'], current_time('Y-m-d')],
            ['Nodos activos', $estadisticas_red['nodos_activos'], current_time('Y-m-d')],
            ['Conexiones federadas', $estadisticas_red['conexiones_federadas'], current_time('Y-m-d')],
        ];
    }

    /**
     * Exporta estadisticas de usuarios para CSV
     *
     * @return array
     */
    private function exportar_estadisticas_usuarios() {
        $datos_mensuales = $this->obtener_usuarios_mensual();
        $filas_csv = [['Mes', 'Usuarios Nuevos']];

        foreach ($datos_mensuales['etiquetas'] as $indice => $mes) {
            $filas_csv[] = [$mes, $datos_mensuales['actual'][$indice]];
        }

        return $filas_csv;
    }

    /**
     * Exporta estadisticas de red para CSV
     *
     * @return array
     */
    private function exportar_estadisticas_red() {
        global $wpdb;

        $tabla_nodos = $wpdb->prefix . 'flavor_network_nodes';

        if (!$this->tabla_existe($tabla_nodos)) {
            return [['Sin datos de red disponibles']];
        }

        $nodos = $wpdb->get_results(
            "SELECT nombre, tipo_entidad, ciudad, pais, estado, miembros_count, fecha_registro
             FROM {$tabla_nodos}
             WHERE es_nodo_local = 0
             ORDER BY nombre ASC"
        );

        $filas_csv = [['Nombre', 'Tipo', 'Ciudad', 'Pais', 'Estado', 'Miembros', 'Fecha Registro']];

        foreach ($nodos as $nodo) {
            $filas_csv[] = [
                $nodo->nombre,
                $nodo->tipo_entidad,
                $nodo->ciudad,
                $nodo->pais,
                $nodo->estado,
                $nodo->miembros_count,
                $nodo->fecha_registro,
            ];
        }

        return $filas_csv;
    }

    /**
     * Exporta estadisticas de conversaciones para CSV
     *
     * @return array
     */
    private function exportar_estadisticas_conversaciones() {
        $datos_semanales = $this->obtener_conversaciones_semanal();
        $filas_csv = [['Semana', 'Conversaciones']];

        foreach ($datos_semanales['etiquetas'] as $indice => $semana) {
            $filas_csv[] = [$semana, $datos_semanales['valores'][$indice]];
        }

        return $filas_csv;
    }

    /**
     * Endpoint API para estadisticas
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function api_obtener_estadisticas($request) {
        $estadisticas = $this->get_dashboard_stats();
        $alertas = $this->obtener_alertas_pendientes();
        $actividad = $this->obtener_actividad_reciente();

        return new WP_REST_Response([
            'success'    => true,
            'data'       => [
                'estadisticas' => $estadisticas,
                'alertas'      => $alertas,
                'actividad'    => $actividad,
                'timestamp'    => current_time('c'),
            ],
        ], 200);
    }

    /**
     * Endpoint API para datos de graficos
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function api_obtener_datos_graficos($request) {
        $datos_graficos = $this->obtener_datos_graficos();

        // Agregar datos comparativos
        $datos_graficos['comparativas'] = $this->obtener_comparativas_temporales();

        return new WP_REST_Response([
            'success' => true,
            'data'    => $datos_graficos,
        ], 200);
    }

    /**
     * Endpoint API para alertas
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function api_obtener_alertas($request) {
        $alertas = $this->obtener_alertas_pendientes();

        return new WP_REST_Response([
            'success' => true,
            'data'    => $alertas,
        ], 200);
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX handler para estadisticas
     *
     * @return void
     */
    public function ajax_obtener_estadisticas() {
        check_ajax_referer('flavor_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $estadisticas = $this->get_dashboard_stats();
        $alertas = $this->obtener_alertas_pendientes();
        $actividad = $this->obtener_actividad_reciente();

        wp_send_json_success([
            'estadisticas' => $estadisticas,
            'alertas'      => $alertas,
            'actividad'    => $actividad,
            'timestamp'    => current_time('c'),
        ]);
    }

    /**
     * AJAX handler para acciones rapidas
     *
     * @return void
     */
    public function ajax_ejecutar_accion_rapida() {
        check_ajax_referer('flavor_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $accion = sanitize_key($_POST['action_id'] ?? '');

        switch ($accion) {
            case 'send_notification':
                $titulo = sanitize_text_field($_POST['titulo'] ?? '');
                $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
                // Aqui iria la logica de enviar notificacion
                wp_send_json_success(['message' => __('Notificacion enviada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
                break;

            case 'clear_cache':
                // Limpiar cache del dashboard
                delete_transient('flavor_dashboard_stats');
                wp_send_json_success(['message' => __('Cache limpiada', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
                break;

            default:
                wp_send_json_error(['message' => __('Accion no reconocida', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX handler para guardar el estado de los paneles colapsables
     *
     * @return void
     */
    public function ajax_guardar_estado_panel() {
        check_ajax_referer('flavor_panel_state', '_wpnonce');

        if (!$this->usuario_puede_ver_dashboard()) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $panel_id = sanitize_key($_POST['panel'] ?? '');
        $state = !empty($_POST['state']) && $_POST['state'] === '1';

        $paneles_validos = ['graficos', 'red', 'gailu', 'addons'];

        if (!in_array($panel_id, $paneles_validos, true)) {
            wp_send_json_error(['message' => __('Panel no válido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $user_id = get_current_user_id();
        $paneles_estado = get_user_meta($user_id, 'flavor_dashboard_panels', true);

        if (!is_array($paneles_estado)) {
            $paneles_estado = [
                'graficos' => false,
                'red' => false,
                'gailu' => false,
                'addons' => false,
            ];
        }

        $paneles_estado[$panel_id] = $state;
        update_user_meta($user_id, 'flavor_dashboard_panels', $paneles_estado);

        wp_send_json_success(['panel' => $panel_id, 'state' => $state]);
    }

    // =========================================================================
    // MÉTODOS PARA DASHBOARD MEJORADO V2
    // =========================================================================

    /**
     * Obtiene resumen de tareas pendientes para el administrador
     *
     * @return array
     */
    public function obtener_tareas_pendientes() {
        global $wpdb;
        $tareas = [];

        // 1. Contenido pendiente de moderación (posts en pending)
        $pendientes_moderacion = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_status = 'pending'
             AND post_type NOT IN ('revision', 'nav_menu_item', 'attachment')"
        );
        if ($pendientes_moderacion > 0) {
            $tareas[] = [
                'tipo' => 'moderacion',
                'icono' => 'dashicons-visibility',
                'titulo' => sprintf(__('%d publicaciones pendientes de revisión', FLAVOR_PLATFORM_TEXT_DOMAIN), $pendientes_moderacion),
                'url' => admin_url('edit.php?post_status=pending'),
                'cantidad' => (int) $pendientes_moderacion,
                'prioridad' => 'media',
            ];
        }

        // 2. Comentarios pendientes
        $comentarios_pendientes = wp_count_comments()->moderated;
        if ($comentarios_pendientes > 0) {
            $tareas[] = [
                'tipo' => 'comentarios',
                'icono' => 'dashicons-admin-comments',
                'titulo' => sprintf(__('%d comentarios por moderar', FLAVOR_PLATFORM_TEXT_DOMAIN), $comentarios_pendientes),
                'url' => admin_url('edit-comments.php?comment_status=moderated'),
                'cantidad' => (int) $comentarios_pendientes,
                'prioridad' => 'media',
            ];
        }

        // 3. Solicitudes de socios pendientes
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        if ($this->tabla_existe($tabla_socios)) {
            $socios_pendientes = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_socios} WHERE estado = 'pendiente'"
            );
            if ($socios_pendientes > 0) {
                $tareas[] = [
                    'tipo' => 'socios',
                    'icono' => 'dashicons-groups',
                    'titulo' => sprintf(__('%d solicitudes de socios', FLAVOR_PLATFORM_TEXT_DOMAIN), $socios_pendientes),
                    'url' => admin_url('admin.php?page=socios-dashboard&estado=pendiente'),
                    'cantidad' => (int) $socios_pendientes,
                    'prioridad' => 'alta',
                ];
            }
        }

        // 4. Incidencias abiertas
        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        if ($this->tabla_existe($tabla_incidencias)) {
            $incidencias_abiertas = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_incidencias} WHERE estado IN ('abierta', 'nueva')"
            );
            if ($incidencias_abiertas > 0) {
                $tareas[] = [
                    'tipo' => 'incidencias',
                    'icono' => 'dashicons-warning',
                    'titulo' => sprintf(__('%d incidencias abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN), $incidencias_abiertas),
                    'url' => admin_url('admin.php?page=incidencias-dashboard'),
                    'cantidad' => (int) $incidencias_abiertas,
                    'prioridad' => 'alta',
                ];
            }
        }

        // 5. Reservas pendientes de confirmar
        $tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        if ($this->tabla_existe($tabla_reservas)) {
            $reservas_pendientes = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_reservas} WHERE estado = 'pendiente'"
            );
            if ($reservas_pendientes > 0) {
                $tareas[] = [
                    'tipo' => 'reservas',
                    'icono' => 'dashicons-calendar-alt',
                    'titulo' => sprintf(__('%d reservas por confirmar', FLAVOR_PLATFORM_TEXT_DOMAIN), $reservas_pendientes),
                    'url' => admin_url('admin.php?page=reservas-dashboard&estado=pendiente'),
                    'cantidad' => (int) $reservas_pendientes,
                    'prioridad' => 'media',
                ];
            }
        }

        // 6. Propuestas pendientes de votación
        $tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
        if ($this->tabla_existe($tabla_propuestas)) {
            $propuestas_pendientes = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_propuestas} WHERE estado = 'pendiente'"
            );
            if ($propuestas_pendientes > 0) {
                $tareas[] = [
                    'tipo' => 'propuestas',
                    'icono' => 'dashicons-megaphone',
                    'titulo' => sprintf(__('%d propuestas pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN), $propuestas_pendientes),
                    'url' => admin_url('admin.php?page=participacion-dashboard'),
                    'cantidad' => (int) $propuestas_pendientes,
                    'prioridad' => 'baja',
                ];
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // ALERTAS PROACTIVAS ADICIONALES (basadas en datos en tiempo real)
        // ═══════════════════════════════════════════════════════════════════

        // 7. Incidencias URGENTES (prioridad alta sin resolver)
        if ($this->tabla_existe($tabla_incidencias)) {
            $incidencias_urgentes = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_incidencias}
                 WHERE prioridad = 'alta'
                 AND estado NOT IN ('cerrada', 'resuelta')"
            );
            if ($incidencias_urgentes > 0) {
                $tareas[] = [
                    'tipo' => 'incidencias_urgentes',
                    'icono' => 'dashicons-sos',
                    'titulo' => sprintf(__('⚠️ %d incidencias URGENTES', FLAVOR_PLATFORM_TEXT_DOMAIN), $incidencias_urgentes),
                    'url' => admin_url('admin.php?page=incidencias-dashboard&prioridad=alta'),
                    'cantidad' => (int) $incidencias_urgentes,
                    'prioridad' => 'critica',
                ];
            }
        }

        // 8. Cuotas de socios pendientes de cobro
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';
        if ($this->tabla_existe($tabla_cuotas)) {
            $cuotas_vencidas = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_cuotas}
                 WHERE estado = 'pendiente'
                 AND fecha_vencimiento < CURDATE()"
            );
            if ($cuotas_vencidas > 0) {
                $tareas[] = [
                    'tipo' => 'cuotas',
                    'icono' => 'dashicons-money-alt',
                    'titulo' => sprintf(__('%d cuotas vencidas sin cobrar', FLAVOR_PLATFORM_TEXT_DOMAIN), $cuotas_vencidas),
                    'url' => admin_url('admin.php?page=socios-dashboard&tab=cuotas&estado=vencida'),
                    'cantidad' => (int) $cuotas_vencidas,
                    'prioridad' => 'alta',
                ];
            }
        }

        // 9. Inscripciones de eventos sin confirmar
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';
        if ($this->tabla_existe($tabla_inscripciones)) {
            $inscripciones_pendientes = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_inscripciones}
                 WHERE estado = 'pendiente'"
            );
            if ($inscripciones_pendientes > 0) {
                $tareas[] = [
                    'tipo' => 'inscripciones',
                    'icono' => 'dashicons-tickets-alt',
                    'titulo' => sprintf(__('%d inscripciones por confirmar', FLAVOR_PLATFORM_TEXT_DOMAIN), $inscripciones_pendientes),
                    'url' => admin_url('admin.php?page=eventos-dashboard&tab=inscripciones'),
                    'cantidad' => (int) $inscripciones_pendientes,
                    'prioridad' => 'media',
                ];
            }
        }

        // 10. Ciclos de grupos de consumo próximos a cerrar (48h)
        $tabla_ciclos = $wpdb->prefix . 'flavor_gc_ciclos';
        if ($this->tabla_existe($tabla_ciclos)) {
            $ciclos_por_cerrar = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_ciclos}
                 WHERE estado = 'abierto'
                 AND fecha_cierre BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)"
            );
            if ($ciclos_por_cerrar > 0) {
                $tareas[] = [
                    'tipo' => 'ciclos_gc',
                    'icono' => 'dashicons-clock',
                    'titulo' => sprintf(__('%d ciclo(s) cierran en 48h', FLAVOR_PLATFORM_TEXT_DOMAIN), $ciclos_por_cerrar),
                    'url' => admin_url('admin.php?page=grupos-consumo-dashboard&tab=ciclos'),
                    'cantidad' => (int) $ciclos_por_cerrar,
                    'prioridad' => 'alta',
                ];
            }
        }

        // 11. Pedidos de GC pendientes de procesar
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
        if ($this->tabla_existe($tabla_pedidos)) {
            $pedidos_pendientes = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_pedidos}
                 WHERE estado IN ('pendiente', 'confirmado')"
            );
            if ($pedidos_pendientes > 0) {
                $tareas[] = [
                    'tipo' => 'pedidos_gc',
                    'icono' => 'dashicons-cart',
                    'titulo' => sprintf(__('%d pedidos por procesar', FLAVOR_PLATFORM_TEXT_DOMAIN), $pedidos_pendientes),
                    'url' => admin_url('admin.php?page=grupos-consumo-dashboard&tab=pedidos'),
                    'cantidad' => (int) $pedidos_pendientes,
                    'prioridad' => 'media',
                ];
            }
        }

        // 12. Préstamos de biblioteca vencidos
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        if ($this->tabla_existe($tabla_prestamos)) {
            $prestamos_vencidos = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_prestamos}
                 WHERE estado = 'activo'
                 AND fecha_devolucion < CURDATE()"
            );
            if ($prestamos_vencidos > 0) {
                $tareas[] = [
                    'tipo' => 'prestamos',
                    'icono' => 'dashicons-book',
                    'titulo' => sprintf(__('%d préstamos vencidos', FLAVOR_PLATFORM_TEXT_DOMAIN), $prestamos_vencidos),
                    'url' => admin_url('admin.php?page=biblioteca-dashboard&tab=prestamos&estado=vencido'),
                    'cantidad' => (int) $prestamos_vencidos,
                    'prioridad' => 'media',
                ];
            }
        }

        // 13. Reservas para HOY sin confirmar
        if ($this->tabla_existe($tabla_reservas)) {
            $reservas_hoy_pendientes = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_reservas}
                 WHERE estado = 'pendiente'
                 AND DATE(fecha_inicio) = CURDATE()"
            );
            if ($reservas_hoy_pendientes > 0) {
                $tareas[] = [
                    'tipo' => 'reservas_hoy',
                    'icono' => 'dashicons-calendar',
                    'titulo' => sprintf(__('⏰ %d reservas de HOY sin confirmar', FLAVOR_PLATFORM_TEXT_DOMAIN), $reservas_hoy_pendientes),
                    'url' => admin_url('admin.php?page=reservas-dashboard&fecha=hoy&estado=pendiente'),
                    'cantidad' => (int) $reservas_hoy_pendientes,
                    'prioridad' => 'critica',
                ];
            }
        }

        // 14. Matrículas de cursos pendientes
        $tabla_matriculas = $wpdb->prefix . 'flavor_cursos_matriculas';
        if ($this->tabla_existe($tabla_matriculas)) {
            $matriculas_pendientes = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_matriculas}
                 WHERE estado = 'pendiente'"
            );
            if ($matriculas_pendientes > 0) {
                $tareas[] = [
                    'tipo' => 'matriculas',
                    'icono' => 'dashicons-welcome-learn-more',
                    'titulo' => sprintf(__('%d matrículas por aprobar', FLAVOR_PLATFORM_TEXT_DOMAIN), $matriculas_pendientes),
                    'url' => admin_url('admin.php?page=cursos-dashboard&tab=matriculas'),
                    'cantidad' => (int) $matriculas_pendientes,
                    'prioridad' => 'media',
                ];
            }
        }

        // 15. Productos de marketplace pendientes de aprobación
        $tabla_marketplace = $wpdb->prefix . 'flavor_marketplace_productos';
        if ($this->tabla_existe($tabla_marketplace)) {
            $productos_pendientes = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_marketplace}
                 WHERE estado = 'pendiente'"
            );
            if ($productos_pendientes > 0) {
                $tareas[] = [
                    'tipo' => 'marketplace',
                    'icono' => 'dashicons-store',
                    'titulo' => sprintf(__('%d anuncios por aprobar', FLAVOR_PLATFORM_TEXT_DOMAIN), $productos_pendientes),
                    'url' => admin_url('admin.php?page=marketplace-dashboard&estado=pendiente'),
                    'cantidad' => (int) $productos_pendientes,
                    'prioridad' => 'baja',
                ];
            }
        }

        // Ordenar por prioridad (critica > alta > media > baja)
        usort($tareas, function($a, $b) {
            $prioridades = ['critica' => 0, 'alta' => 1, 'media' => 2, 'baja' => 3];
            return ($prioridades[$a['prioridad']] ?? 3) <=> ($prioridades[$b['prioridad']] ?? 3);
        });

        // Limitar a las 10 más importantes para no saturar
        return array_slice($tareas, 0, 10);
    }

    /**
     * Obtiene los módulos más usados basado en actividad
     *
     * @param int $limite Número máximo de módulos a devolver
     * @return array
     */
    public function obtener_modulos_mas_usados($limite = 6) {
        $configuracion = flavor_get_main_settings();
        $modulos_activos = $configuracion['active_modules'] ?? [];

        if (empty($modulos_activos)) {
            return [];
        }

        // Obtener metadatos de módulos
        $modulos_info = [];
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $loader = Flavor_Platform_Module_Loader::get_instance();
            $todos_modulos = $loader->get_registered_modules();
            foreach ($modulos_activos as $modulo_id) {
                if (isset($todos_modulos[$modulo_id])) {
                    $meta = $todos_modulos[$modulo_id];
                    $modulos_info[] = [
                        'id' => $modulo_id,
                        'nombre' => $meta['name'] ?? ucfirst(str_replace(['_', '-'], ' ', $modulo_id)),
                        'icono' => $meta['icon'] ?? 'dashicons-admin-generic',
                        'color' => $meta['color'] ?? '#2271b1',
                        'url' => admin_url('admin.php?page=' . str_replace('_', '-', $modulo_id) . '-dashboard'),
                        'descripcion' => $meta['description'] ?? '',
                    ];
                }
            }
        }

        // Si no hay info de módulos, crear desde los IDs
        if (empty($modulos_info)) {
            foreach (array_slice($modulos_activos, 0, $limite) as $modulo_id) {
                $modulos_info[] = [
                    'id' => $modulo_id,
                    'nombre' => ucfirst(str_replace(['_', '-'], ' ', $modulo_id)),
                    'icono' => 'dashicons-admin-generic',
                    'color' => '#2271b1',
                    'url' => admin_url('admin.php?page=' . str_replace('_', '-', $modulo_id) . '-dashboard'),
                    'descripcion' => '',
                ];
            }
        }

        return array_slice($modulos_info, 0, $limite);
    }

    /**
     * Obtiene datos para el dashboard del gestor de grupos
     *
     * @return array
     */
    public function obtener_datos_gestor_grupos() {
        global $wpdb;
        $user_id = get_current_user_id();
        $datos = [
            'mis_grupos' => [],
            'miembros_recientes' => [],
            'contenido_pendiente' => [],
            'solicitudes_pendientes' => [],
            'estadisticas' => [
                'total_grupos' => 0,
                'total_miembros' => 0,
                'publicaciones_semana' => 0,
            ],
        ];

        // Obtener grupos/comunidades donde el usuario es gestor
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidad_miembros';

        if ($this->tabla_existe($tabla_comunidades)) {
            // Grupos donde es admin/gestor
            $mis_grupos = $wpdb->get_results($wpdb->prepare(
                "SELECT c.*,
                        (SELECT COUNT(*) FROM {$tabla_miembros} WHERE comunidad_id = c.id AND estado = 'activo') as total_miembros,
                        (SELECT COUNT(*) FROM {$tabla_miembros} WHERE comunidad_id = c.id AND estado = 'pendiente') as pendientes
                 FROM {$tabla_comunidades} c
                 WHERE c.creador_id = %d OR c.id IN (
                     SELECT comunidad_id FROM {$tabla_miembros}
                     WHERE user_id = %d AND rol IN ('admin', 'moderador')
                 )
                 ORDER BY c.created_at DESC
                 LIMIT 10",
                $user_id,
                $user_id
            ));

            foreach ($mis_grupos as $grupo) {
                $datos['mis_grupos'][] = [
                    'id' => $grupo->id,
                    'nombre' => $grupo->nombre,
                    'miembros' => (int) $grupo->total_miembros,
                    'pendientes' => (int) $grupo->pendientes,
                    'url' => admin_url('admin.php?page=comunidades-dashboard&comunidad=' . $grupo->id),
                ];
                $datos['estadisticas']['total_grupos']++;
                $datos['estadisticas']['total_miembros'] += (int) $grupo->total_miembros;

                // Acumular solicitudes pendientes
                if ($grupo->pendientes > 0) {
                    $datos['solicitudes_pendientes'][] = [
                        'grupo_id' => $grupo->id,
                        'grupo_nombre' => $grupo->nombre,
                        'cantidad' => (int) $grupo->pendientes,
                        'url' => admin_url('admin.php?page=comunidades-dashboard&comunidad=' . $grupo->id . '&tab=solicitudes'),
                    ];
                }
            }

            // Miembros recientes (últimos 7 días)
            if (!empty($mis_grupos)) {
                $grupo_ids = array_column($mis_grupos, 'id');
                $placeholders = implode(',', array_fill(0, count($grupo_ids), '%d'));

                $miembros_recientes = $wpdb->get_results($wpdb->prepare(
                    "SELECT m.*, u.display_name, u.user_email, c.nombre as grupo_nombre
                     FROM {$tabla_miembros} m
                     JOIN {$wpdb->users} u ON m.user_id = u.ID
                     JOIN {$tabla_comunidades} c ON m.comunidad_id = c.id
                     WHERE m.comunidad_id IN ({$placeholders})
                     AND m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                     AND m.estado = 'activo'
                     ORDER BY m.created_at DESC
                     LIMIT 10",
                    ...$grupo_ids
                ));

                foreach ($miembros_recientes as $miembro) {
                    $datos['miembros_recientes'][] = [
                        'user_id' => $miembro->user_id,
                        'nombre' => $miembro->display_name,
                        'email' => $miembro->user_email,
                        'grupo' => $miembro->grupo_nombre,
                        'fecha' => human_time_diff(strtotime($miembro->created_at), current_time('timestamp')) . ' ' . __('atrás', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ];
                }
            }
        }

        // Contenido pendiente de moderación en colectivos
        $tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
        $tabla_publicaciones = $wpdb->prefix . 'flavor_publicaciones';

        if ($this->tabla_existe($tabla_publicaciones)) {
            $contenido_pendiente = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, u.display_name as autor_nombre
                 FROM {$tabla_publicaciones} p
                 JOIN {$wpdb->users} u ON p.user_id = u.ID
                 WHERE p.estado = 'pendiente'
                 AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 ORDER BY p.created_at DESC
                 LIMIT 10"
            ));

            foreach ($contenido_pendiente as $pub) {
                $datos['contenido_pendiente'][] = [
                    'id' => $pub->id,
                    'titulo' => wp_trim_words($pub->contenido, 10, '...'),
                    'autor' => $pub->autor_nombre,
                    'fecha' => human_time_diff(strtotime($pub->created_at), current_time('timestamp')) . ' ' . __('atrás', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'url' => admin_url('admin.php?page=moderacion-dashboard&id=' . $pub->id),
                ];
            }
        }

        return $datos;
    }

    /**
     * Obtiene accesos rápidos contextuales basados en módulos activos
     *
     * @return array
     */
    public function obtener_accesos_rapidos_contextuales() {
        $configuracion = flavor_get_main_settings();
        $modulos_activos = $configuracion['active_modules'] ?? [];
        $accesos = [];

        // Mapeo de módulos a accesos rápidos
        $accesos_por_modulo = [
            'marketplace' => [
                'etiqueta' => __('Nuevo producto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-plus-alt',
                'url' => admin_url('post-new.php?post_type=marketplace_item'),
                'color' => '#10b981',
            ],
            'eventos' => [
                'etiqueta' => __('Crear evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-calendar-alt',
                'url' => admin_url('post-new.php?post_type=evento'),
                'color' => '#8b5cf6',
            ],
            'talleres' => [
                'etiqueta' => __('Nuevo taller', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-welcome-learn-more',
                'url' => admin_url('post-new.php?post_type=taller'),
                'color' => '#f59e0b',
            ],
            'reservas' => [
                'etiqueta' => __('Ver reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-calendar',
                'url' => admin_url('admin.php?page=reservas-dashboard'),
                'color' => '#3b82f6',
            ],
            'socios' => [
                'etiqueta' => __('Gestionar miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-groups',
                'url' => admin_url('admin.php?page=socios-dashboard'),
                'color' => '#ec4899',
            ],
            'banco_tiempo' => [
                'etiqueta' => __('Banco de tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-clock',
                'url' => admin_url('admin.php?page=banco-tiempo-dashboard'),
                'color' => '#06b6d4',
            ],
            'incidencias' => [
                'etiqueta' => __('Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-warning',
                'url' => admin_url('admin.php?page=incidencias-dashboard'),
                'color' => '#ef4444',
            ],
            'comunidades' => [
                'etiqueta' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-networking',
                'url' => admin_url('admin.php?page=comunidades-dashboard'),
                'color' => '#a855f7',
            ],
        ];

        foreach ($modulos_activos as $modulo) {
            $modulo_key = str_replace('-', '_', $modulo);
            if (isset($accesos_por_modulo[$modulo_key])) {
                $accesos[] = $accesos_por_modulo[$modulo_key];
            }
        }

        // Limitar a 8 accesos rápidos
        return array_slice($accesos, 0, 8);
    }

    /**
     * Obtiene alertas de configuración incompleta
     *
     * @return array
     */
    public function obtener_alertas_configuracion() {
        $alertas = [];
        $configuracion = flavor_get_main_settings();

        // Verificar API key
        if (empty($configuracion['openai_api_key'])) {
            $alertas[] = [
                'tipo' => 'warning',
                'icono' => 'dashicons-admin-network',
                'mensaje' => __('API de IA no configurada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => admin_url('admin.php?page=flavor-platform-settings'),
                'accion' => __('Configurar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Verificar módulos activos
        $modulos_activos = $configuracion['active_modules'] ?? [];
        if (empty($modulos_activos)) {
            $alertas[] = [
                'tipo' => 'info',
                'icono' => 'dashicons-screenoptions',
                'mensaje' => __('No hay módulos activados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => admin_url('admin.php?page=flavor-app-composer'),
                'accion' => __('Activar módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Verificar páginas creadas
        $paginas_creadas = get_option('flavor_pages_created', []);
        if (empty($paginas_creadas)) {
            $alertas[] = [
                'tipo' => 'info',
                'icono' => 'dashicons-admin-page',
                'mensaje' => __('Páginas del portal no creadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url' => admin_url('admin.php?page=flavor-create-pages'),
                'accion' => __('Crear páginas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Verificar errores de PHP recientes
        $errores_recientes = get_option('flavor_recent_errors', []);
        if (!empty($errores_recientes)) {
            $alertas[] = [
                'tipo' => 'error',
                'icono' => 'dashicons-warning',
                'mensaje' => sprintf(__('%d errores registrados', FLAVOR_PLATFORM_TEXT_DOMAIN), count($errores_recientes)),
                'url' => admin_url('admin.php?page=flavor-platform-health-check'),
                'accion' => __('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        return $alertas;
    }

    /**
     * Obtiene resumen compacto de estadísticas principales
     *
     * @return array
     */
    public function obtener_resumen_stats() {
        $stats = $this->get_dashboard_stats();

        return [
            [
                'id' => 'usuarios',
                'valor' => $stats['usuarios_activos_30d'] ?? 0,
                'etiqueta' => __('Usuarios activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-admin-users',
                'color' => '#3b82f6',
            ],
            [
                'id' => 'modulos',
                'valor' => ($stats['modulos_activos'] ?? 0) . '/' . ($stats['modulos_totales'] ?? 0),
                'etiqueta' => __('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-screenoptions',
                'color' => '#10b981',
            ],
            [
                'id' => 'socios',
                'valor' => $stats['socios_activos'] ?? 0,
                'etiqueta' => __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-groups',
                'color' => '#8b5cf6',
            ],
            [
                'id' => 'conversaciones',
                'valor' => $stats['conversaciones'] ?? 0,
                'etiqueta' => __('Conversaciones IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-format-chat',
                'color' => '#f59e0b',
            ],
        ];
    }
}
