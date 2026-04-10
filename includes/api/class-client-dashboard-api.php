<?php
/**
 * API REST para el Dashboard de Cliente
 *
 * Endpoints para que los usuarios finales (clientes) accedan a su
 * dashboard personal: estadisticas, actividad, notificaciones y widgets.
 *
 * @package FlavorPlatform
 * @subpackage API
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para los endpoints REST del dashboard de cliente
 *
 * Proporciona endpoints para:
 * - Resumen completo del dashboard
 * - Estadisticas detalladas del usuario
 * - Timeline de actividad reciente
 * - Notificaciones pendientes
 * - Widgets segun modulos activos
 * - Preferencias del dashboard
 *
 * @since 3.2.0
 */
class Flavor_Client_Dashboard_API {

    /**
     * Version de la API
     *
     * @var string
     */
    const API_VERSION = '1.0.0';

    /**
     * Namespace de la API REST
     *
     * @var string
     */
    const API_NAMESPACE = 'flavor/v1';

    /**
     * Prefijo para cache
     *
     * @var string
     */
    const CACHE_PREFIX = 'flavor_client_dashboard_';

    /**
     * Duracion del cache en segundos (5 minutos)
     *
     * @var int
     */
    const CACHE_DURATION = 300;

    /**
     * Instancia singleton
     *
     * @var Flavor_Client_Dashboard_API|null
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Client_Dashboard_API
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
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registra las rutas REST de la API
     *
     * @return void
     */
    public function register_routes() {
        // GET /wp-json/flavor/v1/client/dashboard
        // Resumen completo del dashboard
        register_rest_route(self::API_NAMESPACE, '/client/dashboard', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_dashboard'],
            'permission_callback' => [$this, 'check_user_authenticated'],
            'args'                => [
                'include_widgets' => [
                    'description' => __('Incluir datos de widgets', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'boolean',
                    'default'     => true,
                ],
            ],
        ]);

        // GET /wp-json/flavor/v1/client/stats
        // Estadisticas detalladas del usuario
        register_rest_route(self::API_NAMESPACE, '/client/stats', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_stats'],
            'permission_callback' => [$this, 'check_user_authenticated'],
            'args'                => [
                'period' => [
                    'description' => __('Periodo de estadisticas: week, month, year, all', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'string',
                    'default'     => 'month',
                    'enum'        => ['week', 'month', 'year', 'all'],
                ],
            ],
        ]);

        // GET /wp-json/flavor/v1/client/statistics
        // Estadisticas simplificadas para widgets del dashboard movil
        register_rest_route(self::API_NAMESPACE, '/client/statistics', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_statistics_for_mobile'],
            'permission_callback' => [$this, 'check_user_authenticated'],
        ]);

        // GET /wp-json/flavor/v1/client/activity
        // Timeline de actividad reciente
        register_rest_route(self::API_NAMESPACE, '/client/activity', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_activity'],
            'permission_callback' => [$this, 'check_user_authenticated'],
            'args'                => [
                'page' => [
                    'description' => __('Numero de pagina', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'integer',
                    'default'     => 1,
                    'minimum'     => 1,
                ],
                'per_page' => [
                    'description' => __('Elementos por pagina', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'integer',
                    'default'     => 20,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ],
                'type' => [
                    'description' => __('Filtrar por tipo de actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'string',
                    'default'     => '',
                ],
                'module' => [
                    'description' => __('Filtrar por modulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'string',
                    'default'     => '',
                ],
            ],
        ]);

        // GET /wp-json/flavor/v1/client/notifications
        // Notificaciones del usuario
        register_rest_route(self::API_NAMESPACE, '/client/notifications', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_notifications'],
            'permission_callback' => [$this, 'check_user_authenticated'],
            'args'                => [
                'page' => [
                    'description' => __('Numero de pagina', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'integer',
                    'default'     => 1,
                    'minimum'     => 1,
                ],
                'per_page' => [
                    'description' => __('Elementos por pagina', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'integer',
                    'default'     => 20,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ],
                'unread_only' => [
                    'description' => __('Solo notificaciones sin leer', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'boolean',
                    'default'     => false,
                ],
                'type' => [
                    'description' => __('Filtrar por tipo de notificacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'string',
                    'default'     => '',
                ],
            ],
        ]);

        // POST /wp-json/flavor/v1/client/notifications/mark-read
        // Marcar notificaciones como leidas
        register_rest_route(self::API_NAMESPACE, '/client/notifications/mark-read', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'mark_notifications_read'],
            'permission_callback' => [$this, 'check_user_authenticated'],
            'args'                => [
                'notification_ids' => [
                    'description' => __('IDs de notificaciones a marcar como leidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'array',
                    'items'       => ['type' => 'integer'],
                    'default'     => [],
                ],
                'mark_all' => [
                    'description' => __('Marcar todas las notificaciones como leidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'boolean',
                    'default'     => false,
                ],
            ],
        ]);

        // GET /wp-json/flavor/v1/client/widgets
        // Widgets disponibles segun modulos activos
        register_rest_route(self::API_NAMESPACE, '/client/widgets', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_widgets'],
            'permission_callback' => [$this, 'check_user_authenticated'],
            'args'                => [
                'widget_ids' => [
                    'description' => __('IDs especificos de widgets a obtener', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'array',
                    'items'       => ['type' => 'string'],
                    'default'     => [],
                ],
            ],
        ]);

        // GET /wp-json/flavor/v1/client/preferences
        // Obtener preferencias del dashboard
        register_rest_route(self::API_NAMESPACE, '/client/preferences', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_preferences'],
            'permission_callback' => [$this, 'check_user_authenticated'],
        ]);

        // POST /wp-json/flavor/v1/client/preferences
        // Guardar preferencias del dashboard
        register_rest_route(self::API_NAMESPACE, '/client/preferences', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'save_preferences'],
            'permission_callback' => [$this, 'check_user_authenticated'],
            'args'                => [
                'visible_widgets' => [
                    'description' => __('Lista de widgets visibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'array',
                    'items'       => ['type' => 'string'],
                ],
                'widget_order' => [
                    'description' => __('Orden de los widgets', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'array',
                    'items'       => ['type' => 'string'],
                ],
                'theme' => [
                    'description' => __('Tema del dashboard: light, dark, auto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'string',
                    'enum'        => ['light', 'dark', 'auto'],
                ],
                'notifications_enabled' => [
                    'description' => __('Notificaciones habilitadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'boolean',
                ],
                'compact_mode' => [
                    'description' => __('Modo compacto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'boolean',
                ],
            ],
        ]);

        // GET /wp-json/flavor/v1/client/network-stats
        // Estadisticas globales de la red/plataforma
        register_rest_route(self::API_NAMESPACE, '/client/network-stats', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_network_stats'],
            'permission_callback' => [$this, 'check_user_authenticated'],
            'args'                => [
                'period' => [
                    'description' => __('Periodo de estadisticas: week, month, year, all', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'string',
                    'default'     => 'month',
                    'enum'        => ['week', 'month', 'year', 'all'],
                ],
            ],
        ]);

        // GET /wp-json/flavor/v1/client/shared-resources
        // Recursos compartidos accesibles desde el dashboard movil
        register_rest_route(self::API_NAMESPACE, '/client/shared-resources', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_shared_resources'],
            'permission_callback' => [$this, 'check_user_authenticated'],
            'args'                => [
                'type' => [
                    'description' => __('Filtrar por tipo de recurso compartido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'string',
                    'default'     => 'all',
                ],
                'limit' => [
                    'description' => __('Numero maximo de recursos a devolver', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'integer',
                    'default'     => 30,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ],
            ],
        ]);

        // GET /wp-json/flavor/v1/client/activity-map
        // Mapa de actividad de la plataforma
        register_rest_route(self::API_NAMESPACE, '/client/activity-map', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_activity_map'],
            'permission_callback' => [$this, 'check_user_authenticated'],
            'args'                => [
                'period' => [
                    'description' => __('Periodo de actividad: week, month, year', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'string',
                    'default'     => 'month',
                    'enum'        => ['week', 'month', 'year'],
                ],
                'module' => [
                    'description' => __('Filtrar por modulo especifico', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'type'        => 'string',
                    'default'     => '',
                ],
            ],
        ]);

        // =====================================================================
        // NOTA: Los endpoints /admin/* están definidos en admin/class-dashboard.php
        // No duplicar aquí para evitar conflictos de formato de datos.
        // =====================================================================
    }

    // =========================================================================
    // CALLBACKS DE PERMISOS
    // =========================================================================

    /**
     * Verifica que el usuario esta autenticado (JWT o cookie)
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return bool|WP_Error True si autenticado, WP_Error si no.
     */
    public function check_user_authenticated($request) {
        // JWT Auth ya establece el usuario via determine_current_user filter
        $id_usuario_actual = get_current_user_id();

        if (!$id_usuario_actual) {
            return new WP_Error(
                'rest_not_logged_in',
                __('Debes iniciar sesion para acceder a este recurso.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 401]
            );
        }

        return true;
    }

    /**
     * Verifica que el usuario tiene permisos de administracion
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return bool|WP_Error True si es admin, WP_Error si no.
     */
    public function check_admin_permissions($request) {
        $id_usuario_actual = get_current_user_id();

        if (!$id_usuario_actual) {
            return new WP_Error(
                'rest_not_logged_in',
                __('Debes iniciar sesion para acceder a este recurso.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 401]
            );
        }

        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('No tienes permisos para acceder a este recurso.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 403]
            );
        }

        return true;
    }

    // =========================================================================
    // ENDPOINT: GET /client/dashboard
    // =========================================================================

    /**
     * Obtiene el resumen completo del dashboard
     *
     * Incluye estadisticas, actividad reciente, notificaciones y widgets.
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response|WP_Error Respuesta con datos del dashboard.
     */
    public function get_dashboard(WP_REST_Request $request) {
        $id_usuario_actual = get_current_user_id();
        $incluir_widgets   = $request->get_param('include_widgets');

        // Intentar obtener de cache
        $clave_cache     = self::CACHE_PREFIX . 'dashboard_' . $id_usuario_actual;
        $datos_cacheados = $this->get_cached_data($clave_cache);

        if ($datos_cacheados !== false && (!defined('WP_DEBUG') || !WP_DEBUG)) {
            return rest_ensure_response($datos_cacheados);
        }

        // Obtener datos del usuario
        $usuario_actual = get_userdata($id_usuario_actual);
        $datos_perfil   = $this->get_user_profile_data($id_usuario_actual);

        // Obtener estadisticas resumidas
        $estadisticas_resumen = $this->calculate_user_stats($id_usuario_actual, 'month');

        // Obtener actividad reciente (ultimos 5 items)
        $actividad_reciente = $this->get_user_activity($id_usuario_actual, [
            'limit'  => 5,
            'offset' => 0,
        ]);

        // Obtener notificaciones sin leer
        $cantidad_notificaciones_sin_leer = $this->get_unread_notifications_count($id_usuario_actual);
        $notificaciones_recientes         = $this->get_user_notifications_data($id_usuario_actual, [
            'limit'       => 5,
            'unread_only' => true,
        ]);

        // Obtener preferencias
        $preferencias_dashboard = $this->get_user_preferences($id_usuario_actual);

        // Construir respuesta
        $datos_dashboard = [
            'user'          => $datos_perfil,
            'stats_summary' => $estadisticas_resumen,
            'recent_activity' => [
                'items' => $actividad_reciente,
                'total' => $this->count_user_activity($id_usuario_actual),
            ],
            'notifications' => [
                'unread_count' => $cantidad_notificaciones_sin_leer,
                'recent'       => $notificaciones_recientes,
            ],
            'preferences'   => $preferencias_dashboard,
            'meta'          => [
                'generated_at' => current_time('c'),
                'cache_ttl'    => self::CACHE_DURATION,
            ],
        ];

        // Incluir widgets si se solicita
        if ($incluir_widgets) {
            $datos_dashboard['widgets'] = $this->get_available_widgets($id_usuario_actual);
        }

        // Permitir que otros plugins modifiquen la respuesta
        $datos_dashboard = apply_filters('flavor_client_dashboard_data', $datos_dashboard, $id_usuario_actual);

        // Guardar en cache
        $this->set_cached_data($clave_cache, $datos_dashboard);

        return rest_ensure_response($datos_dashboard);
    }

    // =========================================================================
    // ENDPOINT: GET /client/stats
    // =========================================================================

    /**
     * Obtiene estadisticas detalladas del usuario
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response|WP_Error Respuesta con estadisticas.
     */
    public function get_stats(WP_REST_Request $request) {
        $id_usuario_actual = get_current_user_id();
        $periodo           = $request->get_param('period');

        // Cache key incluye periodo
        $clave_cache     = self::CACHE_PREFIX . 'stats_' . $id_usuario_actual . '_' . $periodo;
        $datos_cacheados = $this->get_cached_data($clave_cache);

        if ($datos_cacheados !== false) {
            return rest_ensure_response($datos_cacheados);
        }

        $estadisticas_usuario = $this->calculate_user_stats($id_usuario_actual, $periodo);

        // Estadisticas por modulo
        $estadisticas_por_modulo = $this->get_stats_by_module($id_usuario_actual, $periodo);

        // Tendencias (comparacion con periodo anterior)
        $tendencias = $this->calculate_trends($id_usuario_actual, $periodo);

        // Logros y puntos
        $datos_gamificacion = $this->get_gamification_data($id_usuario_actual);

        $datos_estadisticas = [
            'period'     => $periodo,
            'date_range' => $this->get_period_date_range($periodo),
            'summary'    => $estadisticas_usuario,
            'by_module'  => $estadisticas_por_modulo,
            'trends'     => $tendencias,
            'gamification' => $datos_gamificacion,
            'meta'       => [
                'generated_at' => current_time('c'),
            ],
        ];

        // Permitir que modulos agreguen sus propias estadisticas
        $datos_estadisticas = apply_filters('flavor_client_stats_data', $datos_estadisticas, $id_usuario_actual, $periodo);

        $this->set_cached_data($clave_cache, $datos_estadisticas);

        return rest_ensure_response($datos_estadisticas);
    }

    // =========================================================================
    // ENDPOINT: GET /client/statistics
    // =========================================================================

    /**
     * Obtiene estadisticas simplificadas para los widgets del dashboard movil
     * Formato optimizado para Flutter: array de objetos con id, title, value, icon, color
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response Respuesta con estadisticas.
     */
    public function get_statistics_for_mobile(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $statistics = [];

        // Obtener modulos activos
        $active_modules = [];
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $active_modules = \Flavor_Platform_Module_Loader::get_active_modules_cached();
        }

        global $wpdb;

        // Eventos proximos
        if (in_array('eventos', $active_modules)) {
            $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") === $tabla_eventos) {
                $eventos_proximos = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_eventos WHERE fecha_inicio >= %s AND estado = 'publicado'",
                    current_time('mysql')
                ));
                $statistics[] = [
                    'id' => 'eventos_proximos',
                    'title' => __('Eventos Próximos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'value' => (string) intval($eventos_proximos),
                    'numeric_value' => floatval($eventos_proximos),
                    'icon_name' => 'event',
                    'color_hex' => '#E91E63',
                ];
            }
        }

        // Grupos de Consumo - Pedidos activos del usuario
        if (in_array('grupos_consumo', $active_modules) || in_array('grupos-consumo', $active_modules)) {
            $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_pedidos'") === $tabla_pedidos) {
                $pedidos_gc = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_pedidos WHERE usuario_id = %d AND estado IN ('abierto', 'pendiente')",
                    $user_id
                ));
                $statistics[] = [
                    'id' => 'gc_pedidos',
                    'title' => __('Mis Pedidos GC', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'value' => (string) intval($pedidos_gc),
                    'numeric_value' => floatval($pedidos_gc),
                    'icon_name' => 'shopping_basket',
                    'color_hex' => '#4CAF50',
                ];
            }
        }

        // Banco de Tiempo - Horas disponibles
        if (in_array('banco_tiempo', $active_modules) || in_array('banco-tiempo', $active_modules)) {
            $tabla_saldos = $wpdb->prefix . 'flavor_bt_saldos';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_saldos'") === $tabla_saldos) {
                $saldo_horas = $wpdb->get_var($wpdb->prepare(
                    "SELECT saldo FROM $tabla_saldos WHERE usuario_id = %d",
                    $user_id
                ));
                $statistics[] = [
                    'id' => 'bt_horas',
                    'title' => __('Mis Horas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'value' => number_format(floatval($saldo_horas), 1) . 'h',
                    'numeric_value' => floatval($saldo_horas),
                    'icon_name' => 'schedule',
                    'color_hex' => '#009688',
                ];
            }

            // Servicios disponibles
            $tabla_servicios = $wpdb->prefix . 'flavor_bt_servicios';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_servicios'") === $tabla_servicios) {
                $servicios = $wpdb->get_var(
                    "SELECT COUNT(*) FROM $tabla_servicios WHERE estado = 'activo'"
                );
                $statistics[] = [
                    'id' => 'bt_servicios',
                    'title' => __('Servicios Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'value' => (string) intval($servicios),
                    'numeric_value' => floatval($servicios),
                    'icon_name' => 'volunteer_activism',
                    'color_hex' => '#00BCD4',
                ];
            }
        }

        // Marketplace - Anuncios activos
        if (in_array('marketplace', $active_modules)) {
            $tabla_anuncios = $wpdb->prefix . 'flavor_marketplace';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_anuncios'") === $tabla_anuncios) {
                $mis_anuncios = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_anuncios WHERE usuario_id = %d AND estado = 'activo'",
                    $user_id
                ));
                $statistics[] = [
                    'id' => 'marketplace_mis_anuncios',
                    'title' => __('Mis Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'value' => (string) intval($mis_anuncios),
                    'numeric_value' => floatval($mis_anuncios),
                    'icon_name' => 'storefront',
                    'color_hex' => '#FF9800',
                ];
            }
        }

        // Socios - Estado de membresia
        if (in_array('socios', $active_modules)) {
            $tabla_socios = $wpdb->prefix . 'flavor_socios';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_socios'") === $tabla_socios) {
                $es_socio = $wpdb->get_var($wpdb->prepare(
                    "SELECT estado FROM $tabla_socios WHERE usuario_id = %d",
                    $user_id
                ));
                $statistics[] = [
                    'id' => 'socios_estado',
                    'title' => __('Membresía', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'value' => $es_socio === 'activo' ? __('Activa', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Inactiva', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'numeric_value' => $es_socio === 'activo' ? 1.0 : 0.0,
                    'icon_name' => 'card_membership',
                    'color_hex' => $es_socio === 'activo' ? '#4CAF50' : '#9E9E9E',
                ];
            }
        }

        // Incidencias - Mis reportes
        if (in_array('incidencias', $active_modules)) {
            $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_incidencias'") === $tabla_incidencias) {
                $mis_incidencias = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_incidencias WHERE usuario_id = %d AND estado NOT IN ('cerrada', 'resuelta')",
                    $user_id
                ));
                $statistics[] = [
                    'id' => 'incidencias_abiertas',
                    'title' => __('Incidencias Abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'value' => (string) intval($mis_incidencias),
                    'numeric_value' => floatval($mis_incidencias),
                    'icon_name' => 'report_problem',
                    'color_hex' => '#F44336',
                ];
            }
        }

        // Tramites - Mis expedientes
        if (in_array('tramites', $active_modules)) {
            $tabla_tramites = $wpdb->prefix . 'flavor_tramites';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_tramites'") === $tabla_tramites) {
                $mis_tramites = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_tramites WHERE usuario_id = %d AND estado NOT IN ('completado', 'cancelado')",
                    $user_id
                ));
                $statistics[] = [
                    'id' => 'tramites_pendientes',
                    'title' => __('Trámites Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'value' => (string) intval($mis_tramites),
                    'numeric_value' => floatval($mis_tramites),
                    'icon_name' => 'description',
                    'color_hex' => '#3F51B5',
                ];
            }
        }

        // Red Social - Publicaciones
        if (in_array('red_social', $active_modules) || in_array('red-social', $active_modules)) {
            $tabla_posts = $wpdb->prefix . 'flavor_rs_posts';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_posts'") === $tabla_posts) {
                $mis_posts = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_posts WHERE autor_id = %d",
                    $user_id
                ));
                $statistics[] = [
                    'id' => 'red_social_posts',
                    'title' => __('Mis Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'value' => (string) intval($mis_posts),
                    'numeric_value' => floatval($mis_posts),
                    'icon_name' => 'dynamic_feed',
                    'color_hex' => '#9C27B0',
                ];
            }
        }

        // Comunidades - Mis comunidades
        if (in_array('comunidades', $active_modules)) {
            $tabla_miembros = $wpdb->prefix . 'flavor_comunidad_miembros';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_miembros'") === $tabla_miembros) {
                $mis_comunidades = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_miembros WHERE usuario_id = %d AND estado = 'activo'",
                    $user_id
                ));
                $statistics[] = [
                    'id' => 'comunidades_miembro',
                    'title' => __('Mis Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'value' => (string) intval($mis_comunidades),
                    'numeric_value' => floatval($mis_comunidades),
                    'icon_name' => 'groups',
                    'color_hex' => '#607D8B',
                ];
            }
        }

        // Carpooling - Mis viajes
        if (in_array('carpooling', $active_modules)) {
            $tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_viajes'") === $tabla_viajes) {
                $mis_viajes = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_viajes WHERE conductor_id = %d AND fecha_salida >= %s",
                    $user_id,
                    current_time('mysql')
                ));
                $statistics[] = [
                    'id' => 'carpooling_viajes',
                    'title' => __('Mis Viajes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'value' => (string) intval($mis_viajes),
                    'numeric_value' => floatval($mis_viajes),
                    'icon_name' => 'directions_car',
                    'color_hex' => '#795548',
                ];
            }
        }

        // Reservas - Proximas reservas
        if (in_array('reservas', $active_modules)) {
            $tabla_reservas = $wpdb->prefix . 'flavor_reservas';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_reservas'") === $tabla_reservas) {
                $mis_reservas = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla_reservas WHERE usuario_id = %d AND fecha_inicio >= %s AND estado = 'confirmada'",
                    $user_id,
                    current_time('mysql')
                ));
                $statistics[] = [
                    'id' => 'reservas_proximas',
                    'title' => __('Próximas Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'value' => (string) intval($mis_reservas),
                    'numeric_value' => floatval($mis_reservas),
                    'icon_name' => 'event_available',
                    'color_hex' => '#2196F3',
                ];
            }
        }

        // Permitir que otros modulos agreguen estadisticas
        $statistics = apply_filters('flavor_client_statistics_mobile', $statistics, $user_id);

        return rest_ensure_response([
            'statistics' => $statistics,
            'total' => count($statistics),
            'generated_at' => current_time('c'),
        ]);
    }

    // =========================================================================
    // ENDPOINT: GET /client/activity
    // =========================================================================

    /**
     * Obtiene el timeline de actividad del usuario
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response|WP_Error Respuesta con actividad.
     */
    public function get_activity(WP_REST_Request $request) {
        $id_usuario_actual = get_current_user_id();
        $pagina            = $request->get_param('page');
        $por_pagina        = $request->get_param('per_page');
        $tipo_actividad    = $request->get_param('type');
        $modulo_filtro     = $request->get_param('module');

        $desplazamiento = ($pagina - 1) * $por_pagina;

        $argumentos_actividad = [
            'limit'  => $por_pagina,
            'offset' => $desplazamiento,
            'type'   => $tipo_actividad,
            'module' => $modulo_filtro,
        ];

        $items_actividad = $this->get_user_activity($id_usuario_actual, $argumentos_actividad);
        $total_actividad = $this->count_user_activity($id_usuario_actual, $argumentos_actividad);
        $total_paginas   = ceil($total_actividad / $por_pagina);

        $datos_actividad = [
            'items'      => $items_actividad,
            'pagination' => [
                'current_page' => $pagina,
                'per_page'     => $por_pagina,
                'total_items'  => $total_actividad,
                'total_pages'  => $total_paginas,
                'has_more'     => $pagina < $total_paginas,
            ],
            'filters'    => [
                'type'   => $tipo_actividad,
                'module' => $modulo_filtro,
            ],
            'available_types' => $this->get_available_activity_types(),
        ];

        return rest_ensure_response($datos_actividad);
    }

    // =========================================================================
    // ENDPOINT: GET /client/notifications
    // =========================================================================

    /**
     * Obtiene las notificaciones del usuario
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response|WP_Error Respuesta con notificaciones.
     */
    public function get_notifications(WP_REST_Request $request) {
        $id_usuario_actual = get_current_user_id();
        $pagina            = $request->get_param('page');
        $por_pagina        = $request->get_param('per_page');
        $solo_sin_leer     = $request->get_param('unread_only');
        $tipo_notificacion = $request->get_param('type');

        $desplazamiento = ($pagina - 1) * $por_pagina;

        $argumentos_notificaciones = [
            'limit'       => $por_pagina,
            'offset'      => $desplazamiento,
            'unread_only' => $solo_sin_leer,
            'type'        => $tipo_notificacion,
        ];

        $items_notificaciones  = $this->get_user_notifications_data($id_usuario_actual, $argumentos_notificaciones);
        $total_notificaciones  = $this->count_user_notifications($id_usuario_actual, $argumentos_notificaciones);
        $cantidad_sin_leer     = $this->get_unread_notifications_count($id_usuario_actual);
        $total_paginas         = ceil($total_notificaciones / $por_pagina);

        $datos_notificaciones = [
            'items'        => $items_notificaciones,
            'unread_count' => $cantidad_sin_leer,
            'pagination'   => [
                'current_page' => $pagina,
                'per_page'     => $por_pagina,
                'total_items'  => $total_notificaciones,
                'total_pages'  => $total_paginas,
                'has_more'     => $pagina < $total_paginas,
            ],
            'filters'      => [
                'unread_only' => $solo_sin_leer,
                'type'        => $tipo_notificacion,
            ],
        ];

        return rest_ensure_response($datos_notificaciones);
    }

    // =========================================================================
    // ENDPOINT: POST /client/notifications/mark-read
    // =========================================================================

    /**
     * Marca notificaciones como leidas
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response|WP_Error Respuesta con resultado.
     */
    public function mark_notifications_read(WP_REST_Request $request) {
        $id_usuario_actual   = get_current_user_id();
        $ids_notificaciones  = $request->get_param('notification_ids');
        $marcar_todas        = $request->get_param('mark_all');

        if (!class_exists('Flavor_Notification_Manager')) {
            return new WP_Error(
                'notifications_unavailable',
                __('El sistema de notificaciones no esta disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 503]
            );
        }

        $gestor_notificaciones = Flavor_Notification_Manager::get_instance();
        $cantidad_marcadas     = 0;

        if ($marcar_todas) {
            $gestor_notificaciones->mark_all_as_read($id_usuario_actual);
            $cantidad_marcadas = -1; // Indica todas
        } elseif (!empty($ids_notificaciones)) {
            foreach ($ids_notificaciones as $id_notificacion) {
                $resultado = $gestor_notificaciones->mark_as_read($id_notificacion, $id_usuario_actual);
                if ($resultado) {
                    $cantidad_marcadas++;
                }
            }
        }

        // Invalidar cache de dashboard
        $this->invalidate_user_cache($id_usuario_actual);

        $cantidad_sin_leer = $gestor_notificaciones->get_unread_count($id_usuario_actual);

        return rest_ensure_response([
            'success'      => true,
            'marked_count' => $cantidad_marcadas === -1 ? __('todas', FLAVOR_PLATFORM_TEXT_DOMAIN) : $cantidad_marcadas,
            'unread_count' => $cantidad_sin_leer,
        ]);
    }

    // =========================================================================
    // ENDPOINT: GET /client/widgets
    // =========================================================================

    /**
     * Obtiene los widgets disponibles con sus datos
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response|WP_Error Respuesta con widgets.
     */
    public function get_widgets(WP_REST_Request $request) {
        $id_usuario_actual = get_current_user_id();
        $ids_widgets       = $request->get_param('widget_ids');

        $widgets_disponibles = $this->get_available_widgets($id_usuario_actual);

        // Filtrar si se especifican IDs
        if (!empty($ids_widgets)) {
            $widgets_filtrados = [];
            foreach ($widgets_disponibles as $widget) {
                if (in_array($widget['id'], $ids_widgets, true)) {
                    $widgets_filtrados[] = $widget;
                }
            }
            $widgets_disponibles = $widgets_filtrados;
        }

        // Obtener preferencias para orden
        $preferencias_dashboard = $this->get_user_preferences($id_usuario_actual);
        $orden_widgets          = $preferencias_dashboard['widget_order'] ?? [];
        $widgets_visibles       = $preferencias_dashboard['visible_widgets'] ?? [];

        // Ordenar segun preferencias
        if (!empty($orden_widgets)) {
            usort($widgets_disponibles, function ($widget_a, $widget_b) use ($orden_widgets) {
                $posicion_a = array_search($widget_a['id'], $orden_widgets, true);
                $posicion_b = array_search($widget_b['id'], $orden_widgets, true);
                $posicion_a = $posicion_a === false ? 999 : $posicion_a;
                $posicion_b = $posicion_b === false ? 999 : $posicion_b;
                return $posicion_a - $posicion_b;
            });
        }

        // Marcar visibilidad
        foreach ($widgets_disponibles as &$widget) {
            $widget['is_visible'] = empty($widgets_visibles) || in_array($widget['id'], $widgets_visibles, true);
        }

        return rest_ensure_response([
            'widgets' => $widgets_disponibles,
            'meta'    => [
                'total_available' => count($widgets_disponibles),
                'visible_count'   => count(array_filter($widgets_disponibles, fn($widget) => $widget['is_visible'])),
            ],
        ]);
    }

    // =========================================================================
    // ENDPOINT: GET/POST /client/preferences
    // =========================================================================

    /**
     * Obtiene las preferencias del dashboard del usuario
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response Respuesta con preferencias.
     */
    public function get_preferences(WP_REST_Request $request) {
        $id_usuario_actual     = get_current_user_id();
        $preferencias_dashboard = $this->get_user_preferences($id_usuario_actual);

        return rest_ensure_response([
            'preferences' => $preferencias_dashboard,
            'defaults'    => $this->get_default_preferences(),
        ]);
    }

    /**
     * Guarda las preferencias del dashboard del usuario
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response|WP_Error Respuesta con resultado.
     */
    public function save_preferences(WP_REST_Request $request) {
        $id_usuario_actual = get_current_user_id();

        // Obtener preferencias actuales
        $preferencias_actuales = $this->get_user_preferences($id_usuario_actual);

        // Campos a actualizar
        $campos_permitidos = [
            'visible_widgets',
            'widget_order',
            'theme',
            'notifications_enabled',
            'compact_mode',
        ];

        $preferencias_nuevas = [];
        foreach ($campos_permitidos as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                $preferencias_nuevas[$campo] = $valor;
            }
        }

        // Merge con preferencias actuales
        $preferencias_actualizadas = array_merge($preferencias_actuales, $preferencias_nuevas);

        // Guardar
        update_user_meta($id_usuario_actual, 'flavor_dashboard_preferences', $preferencias_actualizadas);

        // Invalidar cache
        $this->invalidate_user_cache($id_usuario_actual);

        // Registrar actividad
        if (class_exists('Flavor_Activity_Log')) {
            Flavor_Activity_Log::registrar(
                'sistema',
                'preferences_updated',
                __('Preferencias del dashboard actualizadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                [
                    'usuario_id' => $id_usuario_actual,
                    'datos_extra' => $preferencias_nuevas,
                ]
            );
        }

        return rest_ensure_response([
            'success'     => true,
            'preferences' => $preferencias_actualizadas,
            'message'     => __('Preferencias guardadas correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    // =========================================================================
    // ENDPOINT: GET /client/network-stats
    // =========================================================================

    /**
     * Obtiene estadisticas globales de la red/plataforma
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response Respuesta con estadisticas de red.
     */
    public function get_network_stats(WP_REST_Request $request) {
        $periodo = $request->get_param('period');

        // Cache key
        $clave_cache     = self::CACHE_PREFIX . 'network_stats_' . $periodo;
        $datos_cacheados = $this->get_cached_data($clave_cache);

        if ($datos_cacheados !== false && (!defined('WP_DEBUG') || !WP_DEBUG)) {
            return rest_ensure_response($datos_cacheados);
        }

        $rango_fechas = $this->get_period_date_range($periodo);

        // Estadisticas de usuarios
        $estadisticas_usuarios = $this->get_network_user_stats($rango_fechas);

        // Estadisticas de modulos activos
        $estadisticas_modulos = $this->get_network_module_stats($rango_fechas);

        // Actividad general de la red
        $estadisticas_actividad = $this->get_network_activity_stats($rango_fechas);

        // Top contribuyentes
        $top_contribuyentes = $this->get_top_contributors($rango_fechas, 5);

        // Modulos mas activos
        $modulos_mas_activos = $this->get_most_active_modules($rango_fechas, 5);

        $datos_red = [
            'period'      => $periodo,
            'date_range'  => $rango_fechas,
            'users'       => $estadisticas_usuarios,
            'modules'     => $estadisticas_modulos,
            'activity'    => $estadisticas_actividad,
            'top_contributors' => $top_contribuyentes,
            'most_active_modules' => $modulos_mas_activos,
            'meta'        => [
                'generated_at' => current_time('c'),
                'cache_ttl'    => self::CACHE_DURATION,
            ],
        ];

        // Permitir filtrado por plugins externos
        $datos_red = apply_filters('flavor_network_stats_data', $datos_red, $periodo);

        $this->set_cached_data($clave_cache, $datos_red);

        return rest_ensure_response($datos_red);
    }

    // =========================================================================
    // ENDPOINT: GET /client/shared-resources
    // =========================================================================

    /**
     * Obtiene recursos compartidos relevantes para el usuario autenticado.
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response Respuesta con recursos compartidos.
     */
    public function get_shared_resources(WP_REST_Request $request) {
        $id_usuario_actual = get_current_user_id();
        $tipo_filtro       = sanitize_key((string) $request->get_param('type'));
        $tipo_filtro       = $tipo_filtro ?: 'all';
        $limite            = max(1, min(100, (int) $request->get_param('limit')));

        $clave_cache = self::CACHE_PREFIX . 'shared_resources_' . $id_usuario_actual . '_' . $tipo_filtro . '_' . $limite;
        $datos_cacheados = $this->get_cached_data($clave_cache);

        if ($datos_cacheados !== false && (!defined('WP_DEBUG') || !WP_DEBUG)) {
            return rest_ensure_response($datos_cacheados);
        }

        $recursos = $this->get_shared_resources_data($id_usuario_actual, $tipo_filtro, $limite);

        $respuesta = [
            'items' => $recursos,
            'total' => count($recursos),
            'filters' => [
                'type' => $tipo_filtro,
            ],
            'available_types' => $this->get_shared_resource_available_types($id_usuario_actual),
            'meta' => [
                'generated_at' => current_time('c'),
                'cache_ttl'    => self::CACHE_DURATION,
            ],
        ];

        $respuesta = apply_filters('flavor_client_dashboard_shared_resources_rest', $respuesta, $id_usuario_actual, $tipo_filtro, $limite);

        $this->set_cached_data($clave_cache, $respuesta);

        return rest_ensure_response($respuesta);
    }

    // =========================================================================
    // ENDPOINT: GET /client/activity-map
    // =========================================================================

    /**
     * Obtiene datos para el mapa de actividad de la plataforma
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response Respuesta con datos del mapa.
     */
    public function get_activity_map(WP_REST_Request $request) {
        $periodo = $request->get_param('period');
        $modulo_filtro = $request->get_param('module');

        // Cache key
        $clave_cache     = self::CACHE_PREFIX . 'activity_map_' . $periodo . '_' . $modulo_filtro;
        $datos_cacheados = $this->get_cached_data($clave_cache);

        if ($datos_cacheados !== false && (!defined('WP_DEBUG') || !WP_DEBUG)) {
            return rest_ensure_response($datos_cacheados);
        }

        $rango_fechas = $this->get_period_date_range($periodo);

        // Actividad por dia (heatmap temporal)
        $actividad_por_dia = $this->get_activity_by_day($rango_fechas, $modulo_filtro);

        // Actividad por hora del dia
        $actividad_por_hora = $this->get_activity_by_hour($rango_fechas, $modulo_filtro);

        // Actividad por tipo de accion
        $actividad_por_tipo = $this->get_activity_by_type($rango_fechas, $modulo_filtro);

        // Distribucion por modulo
        $distribucion_modulos = $this->get_activity_distribution_by_module($rango_fechas);

        // Tendencia de actividad
        $tendencia_actividad = $this->get_activity_trend($rango_fechas, $modulo_filtro);

        $datos_mapa = [
            'period'       => $periodo,
            'date_range'   => $rango_fechas,
            'module_filter' => $modulo_filtro,
            'by_day'       => $actividad_por_dia,
            'by_hour'      => $actividad_por_hora,
            'by_type'      => $actividad_por_tipo,
            'by_module'    => $distribucion_modulos,
            'trend'        => $tendencia_actividad,
            'meta'         => [
                'generated_at' => current_time('c'),
                'cache_ttl'    => self::CACHE_DURATION,
            ],
        ];

        // Permitir filtrado por plugins externos
        $datos_mapa = apply_filters('flavor_activity_map_data', $datos_mapa, $periodo, $modulo_filtro);

        $this->set_cached_data($clave_cache, $datos_mapa);

        return rest_ensure_response($datos_mapa);
    }

    // =========================================================================
    // METODOS AUXILIARES: ESTADISTICAS DE RED
    // =========================================================================

    /**
     * Obtiene estadisticas de usuarios de la red
     *
     * @param array $rango_fechas Rango de fechas.
     * @return array Estadisticas de usuarios.
     */
    private function get_network_user_stats($rango_fechas) {
        global $wpdb;

        // Total de usuarios
        $total_usuarios = (int) $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->users}");

        // Usuarios nuevos en el periodo
        $usuarios_nuevos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(ID) FROM {$wpdb->users}
            WHERE user_registered BETWEEN %s AND %s",
            $rango_fechas['start'],
            $rango_fechas['end']
        ));

        // Usuarios activos (con actividad en el periodo)
        $tabla_actividad = $wpdb->prefix . 'flavor_activity_log';
        $usuarios_activos = 0;

        if (Flavor_Platform_Helpers::tabla_existe($tabla_actividad)) {
            $usuarios_activos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_actividad}
                WHERE fecha BETWEEN %s AND %s",
                $rango_fechas['start'],
                $rango_fechas['end']
            ));
        }

        return [
            'total'       => $total_usuarios,
            'new'         => $usuarios_nuevos,
            'active'      => $usuarios_activos,
            'active_rate' => $total_usuarios > 0 ? round(($usuarios_activos / $total_usuarios) * 100, 1) : 0,
        ];
    }

    /**
     * Obtiene estadisticas de modulos de la red
     *
     * @param array $rango_fechas Rango de fechas.
     * @return array Estadisticas de modulos.
     */
    private function get_network_module_stats($rango_fechas) {
        $configuracion = flavor_get_main_settings();
        $modulos_activos = $configuracion['active_modules'] ?? [];

        $total_modulos_disponibles = 0;
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $cargador_modulos = Flavor_Platform_Module_Loader::get_instance();
            $total_modulos_disponibles = count($cargador_modulos->get_available_modules());
        }

        return [
            'total_available' => $total_modulos_disponibles,
            'active'          => count($modulos_activos),
            'active_list'     => $modulos_activos,
        ];
    }

    /**
     * Obtiene estadisticas de actividad general de la red
     *
     * @param array $rango_fechas Rango de fechas.
     * @return array Estadisticas de actividad.
     */
    private function get_network_activity_stats($rango_fechas) {
        global $wpdb;

        $tabla_actividad = $wpdb->prefix . 'flavor_activity_log';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_actividad)) {
            return [
                'total_actions' => 0,
                'avg_per_day'   => 0,
                'peak_day'      => null,
            ];
        }

        // Total de acciones
        $total_acciones = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_actividad}
            WHERE fecha BETWEEN %s AND %s",
            $rango_fechas['start'],
            $rango_fechas['end']
        ));

        // Dias en el periodo
        $dias_en_periodo = max(1, ceil((strtotime($rango_fechas['end']) - strtotime($rango_fechas['start'])) / 86400));

        // Promedio por dia
        $promedio_por_dia = round($total_acciones / $dias_en_periodo, 1);

        // Dia pico
        $dia_pico_resultado = $wpdb->get_row($wpdb->prepare(
            "SELECT DATE(fecha) as dia, COUNT(*) as total
            FROM {$tabla_actividad}
            WHERE fecha BETWEEN %s AND %s
            GROUP BY DATE(fecha)
            ORDER BY total DESC
            LIMIT 1",
            $rango_fechas['start'],
            $rango_fechas['end']
        ));

        $dia_pico = null;
        if ($dia_pico_resultado) {
            $dia_pico = [
                'date'  => $dia_pico_resultado->dia,
                'count' => (int) $dia_pico_resultado->total,
            ];
        }

        return [
            'total_actions' => $total_acciones,
            'avg_per_day'   => $promedio_por_dia,
            'peak_day'      => $dia_pico,
        ];
    }

    /**
     * Obtiene los top contribuyentes de la red
     *
     * @param array $rango_fechas Rango de fechas.
     * @param int   $limite       Limite de resultados.
     * @return array Top contribuyentes.
     */
    private function get_top_contributors($rango_fechas, $limite = 5) {
        global $wpdb;

        $tabla_actividad = $wpdb->prefix . 'flavor_activity_log';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_actividad)) {
            return [];
        }

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT usuario_id, COUNT(*) as total_acciones
            FROM {$tabla_actividad}
            WHERE fecha BETWEEN %s AND %s
            AND usuario_id > 0
            GROUP BY usuario_id
            ORDER BY total_acciones DESC
            LIMIT %d",
            $rango_fechas['start'],
            $rango_fechas['end'],
            $limite
        ));

        $contribuyentes = [];
        foreach ($resultados as $resultado) {
            $usuario = get_userdata($resultado->usuario_id);
            if ($usuario) {
                $contribuyentes[] = [
                    'user_id'      => $resultado->usuario_id,
                    'display_name' => $usuario->display_name,
                    'avatar_url'   => get_avatar_url($resultado->usuario_id, ['size' => 48]),
                    'total_actions' => (int) $resultado->total_acciones,
                ];
            }
        }

        return $contribuyentes;
    }

    /**
     * Obtiene los modulos mas activos
     *
     * @param array $rango_fechas Rango de fechas.
     * @param int   $limite       Limite de resultados.
     * @return array Modulos mas activos.
     */
    private function get_most_active_modules($rango_fechas, $limite = 5) {
        global $wpdb;

        $tabla_actividad = $wpdb->prefix . 'flavor_activity_log';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_actividad)) {
            return [];
        }

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT modulo_id, COUNT(*) as total_acciones
            FROM {$tabla_actividad}
            WHERE fecha BETWEEN %s AND %s
            AND modulo_id IS NOT NULL
            AND modulo_id != ''
            GROUP BY modulo_id
            ORDER BY total_acciones DESC
            LIMIT %d",
            $rango_fechas['start'],
            $rango_fechas['end'],
            $limite
        ));

        $modulos_activos = [];
        foreach ($resultados as $resultado) {
            $modulos_activos[] = [
                'module_id'     => $resultado->modulo_id,
                'module_name'   => $this->get_module_display_name($resultado->modulo_id),
                'total_actions' => (int) $resultado->total_acciones,
            ];
        }

        return $modulos_activos;
    }

    /**
     * Obtiene el nombre para mostrar de un modulo
     *
     * @param string $id_modulo ID del modulo.
     * @return string Nombre del modulo.
     */
    private function get_module_display_name($id_modulo) {
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $cargador_modulos = Flavor_Platform_Module_Loader::get_instance();
            $modulos_disponibles = $cargador_modulos->get_available_modules();

            if (isset($modulos_disponibles[$id_modulo]['name'])) {
                return $modulos_disponibles[$id_modulo]['name'];
            }
        }

        // Fallback: formatear el ID como nombre
        return ucwords(str_replace(['-', '_'], ' ', $id_modulo));
    }

    /**
     * Obtiene recursos compartidos listos para la API movil.
     *
     * @param int    $id_usuario  ID del usuario autenticado.
     * @param string $tipo_filtro Tipo solicitado.
     * @param int    $limite      Limite de resultados.
     * @return array
     */
    private function get_shared_resources_data($id_usuario, $tipo_filtro = 'all', $limite = 30) {
        global $wpdb;

        $recursos = [];
        $limite_eventos = max(3, min(10, (int) ceil($limite / 3)));
        $limite_contenido = max(5, $limite * 2);

        $tabla_eventos = $wpdb->prefix . 'flavor_network_events';
        if (Flavor_Platform_Helpers::tabla_existe($tabla_eventos)) {
            $eventos = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, titulo, fecha_inicio, nodo_origen_nombre
                     FROM {$tabla_eventos}
                     WHERE estado = 'publicado' AND fecha_inicio >= %s
                     ORDER BY fecha_inicio ASC
                     LIMIT %d",
                    current_time('mysql'),
                    $limite_eventos
                ),
                ARRAY_A
            );

            foreach ($eventos as $evento) {
                $recursos[] = [
                    'id' => 'event-' . (int) $evento['id'],
                    'resource_id' => (int) $evento['id'],
                    'title' => $evento['titulo'],
                    'type' => 'eventos',
                    'raw_type' => 'evento',
                    'source' => 'network_events',
                    'origin' => $evento['nodo_origen_nombre'] ?: __('Red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'date' => $evento['fecha_inicio'],
                    'url' => home_url('/eventos/' . (int) $evento['id']),
                    'icon' => 'event',
                    'accent' => '#7C3AED',
                    'summary' => __('Evento compartido desde la red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
        }

        $tabla_contenido = $wpdb->prefix . 'flavor_network_shared_content';
        if (Flavor_Platform_Helpers::tabla_existe($tabla_contenido)) {
            $contenidos = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, titulo, tipo, nodo_origen_nombre, created_at
                     FROM {$tabla_contenido}
                     WHERE estado = 'publicado'
                     ORDER BY created_at DESC
                     LIMIT %d",
                    $limite_contenido
                ),
                ARRAY_A
            );

            foreach ($contenidos as $contenido) {
                [$tipo_mapeado, $icono, $color, $resumen] = $this->map_shared_resource_type($contenido['tipo'] ?? '');

                $recursos[] = [
                    'id' => 'content-' . (int) $contenido['id'],
                    'resource_id' => (int) $contenido['id'],
                    'title' => $contenido['titulo'],
                    'type' => $tipo_mapeado,
                    'raw_type' => $contenido['tipo'],
                    'source' => 'shared_content',
                    'origin' => $contenido['nodo_origen_nombre'] ?: __('Red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'date' => $contenido['created_at'],
                    'url' => home_url('/recursos/' . (int) $contenido['id']),
                    'icon' => $icono,
                    'accent' => $color,
                    'summary' => $resumen,
                ];
            }
        }

        usort($recursos, static function ($recurso_a, $recurso_b) {
            return strtotime($recurso_b['date'] ?? '0') <=> strtotime($recurso_a['date'] ?? '0');
        });

        if ($tipo_filtro !== 'all') {
            $recursos = array_values(array_filter($recursos, static function ($recurso) use ($tipo_filtro) {
                return ($recurso['type'] ?? '') === $tipo_filtro;
            }));
        }

        $recursos = array_slice($recursos, 0, $limite);

        return apply_filters('flavor_client_dashboard_shared_resources_data', $recursos, $id_usuario, $tipo_filtro, $limite);
    }

    /**
     * Devuelve tipos disponibles para recursos compartidos.
     *
     * @param int $id_usuario ID del usuario.
     * @return array
     */
    private function get_shared_resource_available_types($id_usuario) {
        $recursos = $this->get_shared_resources_data($id_usuario, 'all', 100);
        $conteos = [];

        foreach ($recursos as $recurso) {
            $tipo = $recurso['type'] ?? 'general';
            if (!isset($conteos[$tipo])) {
                $conteos[$tipo] = 0;
            }
            $conteos[$tipo]++;
        }

        $tipos = [[
            'id' => 'all',
            'label' => __('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'count' => count($recursos),
        ]];

        foreach ($conteos as $tipo => $cantidad) {
            $tipos[] = [
                'id' => $tipo,
                'label' => $this->get_shared_resource_type_label($tipo),
                'count' => $cantidad,
            ];
        }

        return $tipos;
    }

    /**
     * Mapea tipos internos de recursos a datos visuales para mobile.
     *
     * @param string $tipo Tipo original.
     * @return array{0:string,1:string,2:string,3:string}
     */
    private function map_shared_resource_type($tipo) {
        $tipo = sanitize_key((string) $tipo);

        if (in_array($tipo, ['oferta', 'promocion', 'descuento'], true)) {
            return ['ofertas', 'local_offer', '#F59E0B', __('Oferta o promocion compartida en la red', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        if (in_array($tipo, ['servicio', 'profesional'], true)) {
            return ['servicios', 'handyman', '#0EA5E9', __('Servicio compartido por otra comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        if (in_array($tipo, ['recurso', 'documento', 'material'], true)) {
            return ['recursos', 'inventory_2', '#10B981', __('Recurso compartido disponible en la red', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        return ['general', 'hub', '#6366F1', __('Contenido compartido desde la red', FLAVOR_PLATFORM_TEXT_DOMAIN)];
    }

    /**
     * Etiqueta legible para un tipo de recurso compartido.
     *
     * @param string $tipo Tipo.
     * @return string
     */
    private function get_shared_resource_type_label($tipo) {
        $labels = [
            'all' => __('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eventos' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'ofertas' => __('Ofertas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'servicios' => __('Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'recursos' => __('Recursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'general' => __('General', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $labels[$tipo] ?? ucwords(str_replace(['-', '_'], ' ', $tipo));
    }

    // =========================================================================
    // METODOS AUXILIARES: MAPA DE ACTIVIDAD
    // =========================================================================

    /**
     * Obtiene actividad agrupada por dia
     *
     * @param array  $rango_fechas  Rango de fechas.
     * @param string $modulo_filtro Filtro de modulo.
     * @return array Actividad por dia.
     */
    private function get_activity_by_day($rango_fechas, $modulo_filtro = '') {
        global $wpdb;

        $tabla_actividad = $wpdb->prefix . 'flavor_activity_log';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_actividad)) {
            return [];
        }

        $clausula_where = 'fecha BETWEEN %s AND %s';
        $parametros = [$rango_fechas['start'], $rango_fechas['end']];

        if (!empty($modulo_filtro)) {
            $clausula_where .= ' AND modulo_id = %s';
            $parametros[] = $modulo_filtro;
        }

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(fecha) as dia, COUNT(*) as total
            FROM {$tabla_actividad}
            WHERE {$clausula_where}
            GROUP BY DATE(fecha)
            ORDER BY dia ASC",
            $parametros
        ));

        $actividad_por_dia = [];
        foreach ($resultados as $resultado) {
            $actividad_por_dia[] = [
                'date'  => $resultado->dia,
                'count' => (int) $resultado->total,
            ];
        }

        return $actividad_por_dia;
    }

    /**
     * Obtiene actividad agrupada por hora del dia
     *
     * @param array  $rango_fechas  Rango de fechas.
     * @param string $modulo_filtro Filtro de modulo.
     * @return array Actividad por hora.
     */
    private function get_activity_by_hour($rango_fechas, $modulo_filtro = '') {
        global $wpdb;

        $tabla_actividad = $wpdb->prefix . 'flavor_activity_log';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_actividad)) {
            return [];
        }

        $clausula_where = 'fecha BETWEEN %s AND %s';
        $parametros = [$rango_fechas['start'], $rango_fechas['end']];

        if (!empty($modulo_filtro)) {
            $clausula_where .= ' AND modulo_id = %s';
            $parametros[] = $modulo_filtro;
        }

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT HOUR(fecha) as hora, COUNT(*) as total
            FROM {$tabla_actividad}
            WHERE {$clausula_where}
            GROUP BY HOUR(fecha)
            ORDER BY hora ASC",
            $parametros
        ));

        // Inicializar todas las horas con 0
        $actividad_por_hora = [];
        for ($hora = 0; $hora < 24; $hora++) {
            $actividad_por_hora[$hora] = 0;
        }

        foreach ($resultados as $resultado) {
            $actividad_por_hora[(int) $resultado->hora] = (int) $resultado->total;
        }

        return $actividad_por_hora;
    }

    /**
     * Obtiene actividad agrupada por tipo de accion
     *
     * @param array  $rango_fechas  Rango de fechas.
     * @param string $modulo_filtro Filtro de modulo.
     * @return array Actividad por tipo.
     */
    private function get_activity_by_type($rango_fechas, $modulo_filtro = '') {
        global $wpdb;

        $tabla_actividad = $wpdb->prefix . 'flavor_activity_log';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_actividad)) {
            return [];
        }

        $clausula_where = 'fecha BETWEEN %s AND %s';
        $parametros = [$rango_fechas['start'], $rango_fechas['end']];

        if (!empty($modulo_filtro)) {
            $clausula_where .= ' AND modulo_id = %s';
            $parametros[] = $modulo_filtro;
        }

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo, COUNT(*) as total
            FROM {$tabla_actividad}
            WHERE {$clausula_where}
            GROUP BY tipo
            ORDER BY total DESC",
            $parametros
        ));

        $actividad_por_tipo = [];
        foreach ($resultados as $resultado) {
            $actividad_por_tipo[] = [
                'type'  => $resultado->tipo,
                'label' => $this->get_activity_type_label($resultado->tipo),
                'count' => (int) $resultado->total,
            ];
        }

        return $actividad_por_tipo;
    }

    /**
     * Obtiene la etiqueta de un tipo de actividad
     *
     * @param string $tipo Tipo de actividad.
     * @return string Etiqueta.
     */
    private function get_activity_type_label($tipo) {
        $etiquetas = [
            'info'        => __('Informacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'exito'       => __('Exito', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'advertencia' => __('Advertencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'error'       => __('Error', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'crear'       => __('Creacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'editar'      => __('Edicion', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eliminar'    => __('Eliminacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'login'       => __('Inicio de sesion', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'registro'    => __('Registro', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $etiquetas[$tipo] ?? ucfirst($tipo);
    }

    /**
     * Obtiene distribucion de actividad por modulo
     *
     * @param array $rango_fechas Rango de fechas.
     * @return array Distribucion por modulo.
     */
    private function get_activity_distribution_by_module($rango_fechas) {
        global $wpdb;

        $tabla_actividad = $wpdb->prefix . 'flavor_activity_log';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_actividad)) {
            return [];
        }

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT modulo_id, COUNT(*) as total
            FROM {$tabla_actividad}
            WHERE fecha BETWEEN %s AND %s
            AND modulo_id IS NOT NULL
            AND modulo_id != ''
            GROUP BY modulo_id
            ORDER BY total DESC",
            $rango_fechas['start'],
            $rango_fechas['end']
        ));

        $total_general = array_sum(array_column($resultados, 'total'));

        $distribucion = [];
        foreach ($resultados as $resultado) {
            $porcentaje = $total_general > 0 ? round(($resultado->total / $total_general) * 100, 1) : 0;
            $distribucion[] = [
                'module_id'   => $resultado->modulo_id,
                'module_name' => $this->get_module_display_name($resultado->modulo_id),
                'count'       => (int) $resultado->total,
                'percentage'  => $porcentaje,
            ];
        }

        return $distribucion;
    }

    /**
     * Obtiene la tendencia de actividad
     *
     * @param array  $rango_fechas  Rango de fechas.
     * @param string $modulo_filtro Filtro de modulo.
     * @return array Datos de tendencia.
     */
    private function get_activity_trend($rango_fechas, $modulo_filtro = '') {
        global $wpdb;

        $tabla_actividad = $wpdb->prefix . 'flavor_activity_log';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_actividad)) {
            return [
                'current'  => 0,
                'previous' => 0,
                'change'   => 0,
                'trend'    => 'stable',
            ];
        }

        // Periodo actual
        $clausula_where_actual = 'fecha BETWEEN %s AND %s';
        $parametros_actual = [$rango_fechas['start'], $rango_fechas['end']];

        if (!empty($modulo_filtro)) {
            $clausula_where_actual .= ' AND modulo_id = %s';
            $parametros_actual[] = $modulo_filtro;
        }

        $total_actual = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_actividad} WHERE {$clausula_where_actual}",
            $parametros_actual
        ));

        // Periodo anterior
        $duracion = strtotime($rango_fechas['end']) - strtotime($rango_fechas['start']);
        $fecha_fin_anterior = date('Y-m-d 23:59:59', strtotime($rango_fechas['start']) - 1);
        $fecha_inicio_anterior = date('Y-m-d 00:00:00', strtotime($fecha_fin_anterior) - $duracion);

        $clausula_where_anterior = 'fecha BETWEEN %s AND %s';
        $parametros_anterior = [$fecha_inicio_anterior, $fecha_fin_anterior];

        if (!empty($modulo_filtro)) {
            $clausula_where_anterior .= ' AND modulo_id = %s';
            $parametros_anterior[] = $modulo_filtro;
        }

        $total_anterior = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_actividad} WHERE {$clausula_where_anterior}",
            $parametros_anterior
        ));

        // Calcular cambio porcentual
        $cambio_porcentual = 0;
        if ($total_anterior > 0) {
            $cambio_porcentual = round((($total_actual - $total_anterior) / $total_anterior) * 100, 1);
        } elseif ($total_actual > 0) {
            $cambio_porcentual = 100;
        }

        // Determinar tendencia
        $tendencia = 'stable';
        if ($cambio_porcentual > 5) {
            $tendencia = 'up';
        } elseif ($cambio_porcentual < -5) {
            $tendencia = 'down';
        }

        return [
            'current'  => $total_actual,
            'previous' => $total_anterior,
            'change'   => $cambio_porcentual,
            'trend'    => $tendencia,
        ];
    }

    // =========================================================================
    // METODOS AUXILIARES: DATOS DE USUARIO
    // =========================================================================

    /**
     * Obtiene los datos del perfil del usuario
     *
     * @param int $id_usuario ID del usuario.
     * @return array Datos del perfil.
     */
    private function get_user_profile_data($id_usuario) {
        $usuario = get_userdata($id_usuario);

        if (!$usuario) {
            return [];
        }

        $nombre_para_mostrar = $usuario->display_name;
        if (empty($nombre_para_mostrar)) {
            $nombre_para_mostrar = $usuario->first_name . ' ' . $usuario->last_name;
        }
        if (empty(trim($nombre_para_mostrar))) {
            $nombre_para_mostrar = $usuario->user_login;
        }

        $avatar_url = get_avatar_url($id_usuario, ['size' => 128]);

        // Fecha de registro formateada
        $fecha_registro = strtotime($usuario->user_registered);
        $tiempo_miembro = human_time_diff($fecha_registro, current_time('timestamp'));

        return [
            'id'           => $id_usuario,
            'display_name' => $nombre_para_mostrar,
            'first_name'   => $usuario->first_name,
            'last_name'    => $usuario->last_name,
            'email'        => $usuario->user_email,
            'avatar_url'   => $avatar_url,
            'registered'   => $usuario->user_registered,
            'member_since' => sprintf(__('Miembro desde hace %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $tiempo_miembro),
            'roles'        => $usuario->roles,
        ];
    }

    /**
     * Obtiene las preferencias del dashboard del usuario
     *
     * @param int $id_usuario ID del usuario.
     * @return array Preferencias.
     */
    private function get_user_preferences($id_usuario) {
        $preferencias = get_user_meta($id_usuario, 'flavor_dashboard_preferences', true);

        if (!is_array($preferencias)) {
            $preferencias = [];
        }

        return array_merge($this->get_default_preferences(), $preferencias);
    }

    /**
     * Obtiene las preferencias por defecto
     *
     * @return array Preferencias por defecto.
     */
    private function get_default_preferences() {
        return [
            'visible_widgets'       => [],
            'widget_order'          => [],
            'theme'                 => 'auto',
            'notifications_enabled' => true,
            'compact_mode'          => false,
        ];
    }

    // =========================================================================
    // METODOS AUXILIARES: ESTADISTICAS
    // =========================================================================

    /**
     * Calcula las estadisticas del usuario
     *
     * @param int    $id_usuario ID del usuario.
     * @param string $periodo    Periodo: week, month, year, all.
     * @return array Estadisticas.
     */
    private function calculate_user_stats($id_usuario, $periodo) {
        $rango_fechas   = $this->get_period_date_range($periodo);
        $fecha_inicio   = $rango_fechas['start'];
        $fecha_fin      = $rango_fechas['end'];

        $estadisticas = [
            'participations' => 0,
            'contributions'  => 0,
            'points'         => 0,
            'level'          => 1,
            'reservations'   => 0,
            'orders'         => 0,
        ];

        // Participaciones (actividad del usuario)
        $estadisticas['participations'] = $this->count_user_activity($id_usuario, [
            'date_from' => $fecha_inicio,
            'date_to'   => $fecha_fin,
        ]);

        // Puntos y nivel (gamificacion)
        $datos_gamificacion = $this->get_gamification_data($id_usuario);
        $estadisticas['points'] = $datos_gamificacion['points'];
        $estadisticas['level']  = $datos_gamificacion['level'];

        // Reservas (si existe el modulo)
        $estadisticas['reservations'] = $this->count_user_reservations($id_usuario, $fecha_inicio, $fecha_fin);

        // Pedidos WooCommerce (si existe)
        $estadisticas['orders'] = $this->count_user_orders($id_usuario, $fecha_inicio, $fecha_fin);

        // Permitir que modulos agreguen estadisticas
        $estadisticas = apply_filters('flavor_user_stats_summary', $estadisticas, $id_usuario, $periodo);

        return $estadisticas;
    }

    /**
     * Obtiene estadisticas por modulo
     *
     * @param int    $id_usuario ID del usuario.
     * @param string $periodo    Periodo.
     * @return array Estadisticas por modulo.
     */
    private function get_stats_by_module($id_usuario, $periodo) {
        $estadisticas_por_modulo = [];

        // Obtener modulos activos
        $configuracion_settings = flavor_get_main_settings();
        $modulos_activos        = $configuracion_settings['active_modules'] ?? [];

        foreach ($modulos_activos as $slug_modulo) {
            $estadisticas_modulo = apply_filters(
                "flavor_module_{$slug_modulo}_user_stats",
                [],
                $id_usuario,
                $periodo
            );

            if (!empty($estadisticas_modulo)) {
                $estadisticas_por_modulo[$slug_modulo] = $estadisticas_modulo;
            }
        }

        return $estadisticas_por_modulo;
    }

    /**
     * Calcula tendencias comparando con periodo anterior
     *
     * @param int    $id_usuario ID del usuario.
     * @param string $periodo    Periodo actual.
     * @return array Tendencias.
     */
    private function calculate_trends($id_usuario, $periodo) {
        $rango_actual   = $this->get_period_date_range($periodo);
        $rango_anterior = $this->get_previous_period_range($periodo);

        $actividad_actual   = $this->count_user_activity($id_usuario, [
            'date_from' => $rango_actual['start'],
            'date_to'   => $rango_actual['end'],
        ]);

        $actividad_anterior = $this->count_user_activity($id_usuario, [
            'date_from' => $rango_anterior['start'],
            'date_to'   => $rango_anterior['end'],
        ]);

        $cambio_porcentual = 0;
        if ($actividad_anterior > 0) {
            $cambio_porcentual = round((($actividad_actual - $actividad_anterior) / $actividad_anterior) * 100, 1);
        } elseif ($actividad_actual > 0) {
            $cambio_porcentual = 100;
        }

        return [
            'activity' => [
                'current'  => $actividad_actual,
                'previous' => $actividad_anterior,
                'change'   => $cambio_porcentual,
                'trend'    => $cambio_porcentual >= 0 ? 'up' : 'down',
            ],
        ];
    }

    /**
     * Obtiene datos de gamificacion del usuario
     *
     * @param int $id_usuario ID del usuario.
     * @return array Datos de gamificacion.
     */
    private function get_gamification_data($id_usuario) {
        $puntos_totales = (int) get_user_meta($id_usuario, 'flavor_points', true);
        $nivel_actual   = (int) get_user_meta($id_usuario, 'flavor_level', true);

        if ($nivel_actual < 1) {
            $nivel_actual = 1;
        }

        // Calcular nivel basado en puntos si no hay nivel guardado
        if ($puntos_totales > 0 && $nivel_actual === 1) {
            $nivel_actual = $this->calculate_level_from_points($puntos_totales);
        }

        $puntos_siguiente_nivel = $this->get_points_for_next_level($nivel_actual);
        $puntos_nivel_actual    = $this->get_points_for_level($nivel_actual);
        $progreso_nivel         = 0;

        if ($puntos_siguiente_nivel > $puntos_nivel_actual) {
            $progreso_nivel = round(
                (($puntos_totales - $puntos_nivel_actual) / ($puntos_siguiente_nivel - $puntos_nivel_actual)) * 100,
                1
            );
            $progreso_nivel = max(0, min(100, $progreso_nivel));
        }

        // Logros desbloqueados
        $logros_desbloqueados = get_user_meta($id_usuario, 'flavor_achievements', true);
        if (!is_array($logros_desbloqueados)) {
            $logros_desbloqueados = [];
        }

        return [
            'points'              => $puntos_totales,
            'level'               => $nivel_actual,
            'level_progress'      => $progreso_nivel,
            'points_to_next'      => max(0, $puntos_siguiente_nivel - $puntos_totales),
            'achievements_count'  => count($logros_desbloqueados),
            'recent_achievements' => array_slice($logros_desbloqueados, -3),
        ];
    }

    /**
     * Calcula el nivel basado en puntos
     *
     * @param int $puntos Puntos totales.
     * @return int Nivel.
     */
    private function calculate_level_from_points($puntos) {
        $niveles = [1 => 0, 2 => 100, 3 => 300, 4 => 600, 5 => 1000, 6 => 1500, 7 => 2500, 8 => 4000, 9 => 6000, 10 => 10000];

        $nivel = 1;
        foreach ($niveles as $nivel_num => $puntos_requeridos) {
            if ($puntos >= $puntos_requeridos) {
                $nivel = $nivel_num;
            }
        }

        return $nivel;
    }

    /**
     * Obtiene puntos necesarios para un nivel
     *
     * @param int $nivel Nivel.
     * @return int Puntos.
     */
    private function get_points_for_level($nivel) {
        $niveles = [1 => 0, 2 => 100, 3 => 300, 4 => 600, 5 => 1000, 6 => 1500, 7 => 2500, 8 => 4000, 9 => 6000, 10 => 10000];
        return $niveles[$nivel] ?? 0;
    }

    /**
     * Obtiene puntos necesarios para siguiente nivel
     *
     * @param int $nivel_actual Nivel actual.
     * @return int Puntos.
     */
    private function get_points_for_next_level($nivel_actual) {
        return $this->get_points_for_level($nivel_actual + 1);
    }

    // =========================================================================
    // METODOS AUXILIARES: ACTIVIDAD
    // =========================================================================

    /**
     * Obtiene la actividad del usuario
     *
     * @param int   $id_usuario ID del usuario.
     * @param array $argumentos Argumentos de consulta.
     * @return array Items de actividad.
     */
    private function get_user_activity($id_usuario, $argumentos = []) {
        global $wpdb;

        $argumentos_default = [
            'limit'     => 20,
            'offset'    => 0,
            'type'      => '',
            'module'    => '',
            'date_from' => '',
            'date_to'   => '',
        ];

        $argumentos = wp_parse_args($argumentos, $argumentos_default);

        $tabla_actividad = $wpdb->prefix . 'flavor_activity_log';

        // Verificar si la tabla existe
        if (!Flavor_Platform_Helpers::tabla_existe($tabla_actividad)) {
            return [];
        }

        $clausula_where = ['usuario_id = %d'];
        $parametros     = [$id_usuario];

        if (!empty($argumentos['type'])) {
            $clausula_where[] = 'tipo = %s';
            $parametros[]     = $argumentos['type'];
        }

        if (!empty($argumentos['module'])) {
            $clausula_where[] = 'modulo_id = %s';
            $parametros[]     = $argumentos['module'];
        }

        if (!empty($argumentos['date_from'])) {
            $clausula_where[] = 'fecha >= %s';
            $parametros[]     = $argumentos['date_from'];
        }

        if (!empty($argumentos['date_to'])) {
            $clausula_where[] = 'fecha <= %s';
            $parametros[]     = $argumentos['date_to'];
        }

        $clausula_where_sql = implode(' AND ', $clausula_where);
        $parametros[]       = $argumentos['limit'];
        $parametros[]       = $argumentos['offset'];

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla_actividad}
            WHERE {$clausula_where_sql}
            ORDER BY fecha DESC
            LIMIT %d OFFSET %d",
            $parametros
        ));

        // Formatear resultados
        $items_actividad = [];
        foreach ($resultados as $registro) {
            $items_actividad[] = [
                'id'          => $registro->id,
                'type'        => $registro->tipo,
                'action'      => $registro->accion,
                'title'       => $registro->titulo,
                'description' => $registro->descripcion,
                'module'      => $registro->modulo_id,
                'date'        => $registro->fecha,
                'date_human'  => human_time_diff(strtotime($registro->fecha), current_time('timestamp')) . ' ' . __('ago', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'data'        => maybe_unserialize($registro->datos_extra),
            ];
        }

        return $items_actividad;
    }

    /**
     * Cuenta la actividad del usuario
     *
     * @param int   $id_usuario ID del usuario.
     * @param array $argumentos Argumentos de consulta.
     * @return int Total de registros.
     */
    private function count_user_activity($id_usuario, $argumentos = []) {
        global $wpdb;

        $argumentos_default = [
            'type'      => '',
            'module'    => '',
            'date_from' => '',
            'date_to'   => '',
        ];

        $argumentos = wp_parse_args($argumentos, $argumentos_default);

        $tabla_actividad = $wpdb->prefix . 'flavor_activity_log';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_actividad)) {
            return 0;
        }

        $clausula_where = ['usuario_id = %d'];
        $parametros     = [$id_usuario];

        if (!empty($argumentos['type'])) {
            $clausula_where[] = 'tipo = %s';
            $parametros[]     = $argumentos['type'];
        }

        if (!empty($argumentos['module'])) {
            $clausula_where[] = 'modulo_id = %s';
            $parametros[]     = $argumentos['module'];
        }

        if (!empty($argumentos['date_from'])) {
            $clausula_where[] = 'fecha >= %s';
            $parametros[]     = $argumentos['date_from'];
        }

        if (!empty($argumentos['date_to'])) {
            $clausula_where[] = 'fecha <= %s';
            $parametros[]     = $argumentos['date_to'];
        }

        $clausula_where_sql = implode(' AND ', $clausula_where);

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_actividad} WHERE {$clausula_where_sql}",
            $parametros
        ));
    }

    /**
     * Obtiene tipos de actividad disponibles
     *
     * @return array Tipos de actividad.
     */
    private function get_available_activity_types() {
        return [
            'info'        => __('Informacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'exito'       => __('Exito', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'advertencia' => __('Advertencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'error'       => __('Error', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    // =========================================================================
    // METODOS AUXILIARES: NOTIFICACIONES
    // =========================================================================

    /**
     * Obtiene las notificaciones del usuario
     *
     * @param int   $id_usuario ID del usuario.
     * @param array $argumentos Argumentos de consulta.
     * @return array Notificaciones.
     */
    private function get_user_notifications_data($id_usuario, $argumentos = []) {
        if (!class_exists('Flavor_Notification_Manager')) {
            return [];
        }

        $gestor_notificaciones = Flavor_Notification_Manager::get_instance();

        $argumentos_consulta = [
            'limit'       => $argumentos['limit'] ?? 20,
            'offset'      => $argumentos['offset'] ?? 0,
            'unread_only' => $argumentos['unread_only'] ?? false,
            'type'        => $argumentos['type'] ?? null,
        ];

        $notificaciones = $gestor_notificaciones->get_user_notifications($id_usuario, $argumentos_consulta);

        // Formatear para API
        $items_formateados = [];
        foreach ($notificaciones as $notificacion) {
            $items_formateados[] = [
                'id'         => $notificacion->id,
                'type'       => $notificacion->type,
                'title'      => $notificacion->title,
                'message'    => $notificacion->message,
                'icon'       => $notificacion->icon,
                'color'      => $notificacion->color,
                'link'       => $notificacion->link,
                'is_read'    => (bool) $notificacion->is_read,
                'priority'   => $notificacion->priority,
                'created_at' => $notificacion->created_at,
                'date_human' => human_time_diff(strtotime($notificacion->created_at), current_time('timestamp')) . ' ' . __('ago', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'data'       => maybe_unserialize($notificacion->data),
            ];
        }

        return $items_formateados;
    }

    /**
     * Cuenta las notificaciones del usuario
     *
     * @param int   $id_usuario ID del usuario.
     * @param array $argumentos Argumentos de consulta.
     * @return int Total de notificaciones.
     */
    private function count_user_notifications($id_usuario, $argumentos = []) {
        global $wpdb;

        $tabla_notificaciones = $wpdb->prefix . 'flavor_notifications';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_notificaciones)) {
            return 0;
        }

        $clausula_where = ['user_id = %d', 'is_dismissed = 0'];
        $parametros     = [$id_usuario];

        if (!empty($argumentos['unread_only'])) {
            $clausula_where[] = 'is_read = 0';
        }

        if (!empty($argumentos['type'])) {
            $clausula_where[] = 'type = %s';
            $parametros[]     = $argumentos['type'];
        }

        $clausula_where_sql = implode(' AND ', $clausula_where);

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_notificaciones} WHERE {$clausula_where_sql}",
            $parametros
        ));
    }

    /**
     * Obtiene el conteo de notificaciones sin leer
     *
     * @param int $id_usuario ID del usuario.
     * @return int Cantidad sin leer.
     */
    private function get_unread_notifications_count($id_usuario) {
        if (!class_exists('Flavor_Notification_Manager')) {
            return 0;
        }

        $gestor_notificaciones = Flavor_Notification_Manager::get_instance();
        return $gestor_notificaciones->get_unread_count($id_usuario);
    }

    // =========================================================================
    // METODOS AUXILIARES: WIDGETS
    // =========================================================================

    /**
     * Obtiene los widgets disponibles para el usuario
     *
     * @param int $id_usuario ID del usuario.
     * @return array Widgets disponibles.
     */
    private function get_available_widgets($id_usuario) {
        $widgets = [];

        // Widget de estadisticas rapidas
        $widgets[] = [
            'id'          => 'quick_stats',
            'type'        => 'stats',
            'title'       => __('Resumen Rapido', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Tus estadisticas principales de un vistazo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'        => 'dashicons-chart-bar',
            'data'        => $this->calculate_user_stats($id_usuario, 'month'),
            'size'        => 'medium',
        ];

        // Widget de actividad reciente
        $widgets[] = [
            'id'          => 'recent_activity',
            'type'        => 'activity',
            'title'       => __('Actividad Reciente', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Tus ultimas acciones en la plataforma', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'        => 'dashicons-clock',
            'data'        => [
                'items' => $this->get_user_activity($id_usuario, ['limit' => 5]),
            ],
            'size'        => 'large',
        ];

        // Widget de notificaciones
        $cantidad_sin_leer = $this->get_unread_notifications_count($id_usuario);
        $widgets[] = [
            'id'          => 'notifications',
            'type'        => 'notifications',
            'title'       => __('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Tus notificaciones pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'        => 'dashicons-bell',
            'data'        => [
                'unread_count' => $cantidad_sin_leer,
                'items'        => $this->get_user_notifications_data($id_usuario, ['limit' => 5, 'unread_only' => true]),
            ],
            'size'        => 'medium',
            'badge'       => $cantidad_sin_leer > 0 ? $cantidad_sin_leer : null,
        ];

        // Widget de progreso/gamificacion
        $datos_gamificacion = $this->get_gamification_data($id_usuario);
        $widgets[] = [
            'id'          => 'progress',
            'type'        => 'progress',
            'title'       => __('Tu Progreso', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Nivel, puntos y logros', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'        => 'dashicons-awards',
            'data'        => $datos_gamificacion,
            'size'        => 'small',
        ];

        // Obtener widgets de modulos activos
        $configuracion_settings = flavor_get_main_settings();
        $modulos_activos        = $configuracion_settings['active_modules'] ?? [];

        foreach ($modulos_activos as $slug_modulo) {
            $widgets_modulo = apply_filters(
                "flavor_module_{$slug_modulo}_dashboard_widgets",
                [],
                $id_usuario
            );

            if (!empty($widgets_modulo)) {
                $widgets = array_merge($widgets, $widgets_modulo);
            }
        }

        // Permitir filtrado general
        $widgets = apply_filters('flavor_client_dashboard_widgets', $widgets, $id_usuario);

        return $widgets;
    }

    // =========================================================================
    // METODOS AUXILIARES: UTILIDADES
    // =========================================================================

    /**
     * Obtiene el rango de fechas para un periodo
     *
     * @param string $periodo Periodo: week, month, year, all.
     * @return array Rango de fechas [start, end].
     */
    private function get_period_date_range($periodo) {
        $fecha_fin    = current_time('Y-m-d 23:59:59');
        $fecha_inicio = '';

        switch ($periodo) {
            case 'week':
                $fecha_inicio = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
            case 'month':
                $fecha_inicio = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
            case 'year':
                $fecha_inicio = date('Y-m-d 00:00:00', strtotime('-1 year'));
                break;
            case 'all':
            default:
                $fecha_inicio = '2000-01-01 00:00:00';
                break;
        }

        return [
            'start' => $fecha_inicio,
            'end'   => $fecha_fin,
        ];
    }

    /**
     * Obtiene el rango de fechas del periodo anterior
     *
     * @param string $periodo Periodo actual.
     * @return array Rango de fechas [start, end].
     */
    private function get_previous_period_range($periodo) {
        $rango_actual = $this->get_period_date_range($periodo);
        $duracion     = strtotime($rango_actual['end']) - strtotime($rango_actual['start']);

        $fecha_fin    = date('Y-m-d 23:59:59', strtotime($rango_actual['start']) - 1);
        $fecha_inicio = date('Y-m-d 00:00:00', strtotime($fecha_fin) - $duracion);

        return [
            'start' => $fecha_inicio,
            'end'   => $fecha_fin,
        ];
    }

    /**
     * Cuenta reservas del usuario
     *
     * @param int    $id_usuario   ID del usuario.
     * @param string $fecha_inicio Fecha inicio.
     * @param string $fecha_fin    Fecha fin.
     * @return int Cantidad de reservas.
     */
    private function count_user_reservations($id_usuario, $fecha_inicio, $fecha_fin) {
        global $wpdb;

        $tabla_reservas = $wpdb->prefix . 'flavor_reservas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_reservas)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_reservas}
            WHERE usuario_id = %d
            AND fecha_creacion BETWEEN %s AND %s",
            $id_usuario,
            $fecha_inicio,
            $fecha_fin
        ));
    }

    /**
     * Cuenta pedidos WooCommerce del usuario
     *
     * @param int    $id_usuario   ID del usuario.
     * @param string $fecha_inicio Fecha inicio.
     * @param string $fecha_fin    Fecha fin.
     * @return int Cantidad de pedidos.
     */
    private function count_user_orders($id_usuario, $fecha_inicio, $fecha_fin) {
        if (!class_exists('WooCommerce')) {
            return 0;
        }

        $argumentos_pedidos = [
            'customer_id' => $id_usuario,
            'date_created' => $fecha_inicio . '...' . $fecha_fin,
            'limit'        => -1,
            'return'       => 'ids',
        ];

        $pedidos = wc_get_orders($argumentos_pedidos);

        return count($pedidos);
    }

    // =========================================================================
    // METODOS AUXILIARES: CACHE
    // =========================================================================

    /**
     * Obtiene datos del cache
     *
     * @param string $clave Clave del cache.
     * @return mixed|false Datos o false si no existe.
     */
    private function get_cached_data($clave) {
        if (class_exists('Flavor_Performance_Cache')) {
            $cache = Flavor_Performance_Cache::get_instance();
            return $cache->get($clave, 'client_dashboard');
        }

        return get_transient($clave);
    }

    /**
     * Guarda datos en cache
     *
     * @param string $clave Clave del cache.
     * @param mixed  $datos Datos a guardar.
     * @return bool Exito.
     */
    private function set_cached_data($clave, $datos) {
        if (class_exists('Flavor_Performance_Cache')) {
            $cache = Flavor_Performance_Cache::get_instance();
            return $cache->set($clave, $datos, 'client_dashboard', self::CACHE_DURATION);
        }

        return set_transient($clave, $datos, self::CACHE_DURATION);
    }

    /**
     * Invalida el cache del usuario
     *
     * @param int $id_usuario ID del usuario.
     * @return void
     */
    private function invalidate_user_cache($id_usuario) {
        $claves_cache = [
            self::CACHE_PREFIX . 'dashboard_' . $id_usuario,
            self::CACHE_PREFIX . 'stats_' . $id_usuario . '_week',
            self::CACHE_PREFIX . 'stats_' . $id_usuario . '_month',
            self::CACHE_PREFIX . 'stats_' . $id_usuario . '_year',
            self::CACHE_PREFIX . 'stats_' . $id_usuario . '_all',
        ];

        foreach ($claves_cache as $clave) {
            delete_transient($clave);

            if (class_exists('Flavor_Performance_Cache')) {
                $cache = Flavor_Performance_Cache::get_instance();
                $cache->delete($clave, 'client_dashboard');
            }
        }
    }

    // =========================================================================
    // ENDPOINTS DE ADMIN
    // =========================================================================

    /**
     * Obtiene estadisticas generales para el dashboard de admin
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response Respuesta con estadisticas.
     */
    public function get_admin_dashboard_stats(WP_REST_Request $request) {
        global $wpdb;

        // Estadisticas de usuarios
        $total_usuarios = count_users();
        $usuarios_hoy = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->users}
            WHERE DATE(user_registered) = CURDATE()"
        );
        $usuarios_semana = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->users}
            WHERE user_registered >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        // Estadisticas de contenido
        $total_posts = wp_count_posts('post');
        $total_paginas = wp_count_posts('page');

        // Modulos activos
        $configuracion = flavor_get_main_settings();
        $modulos_activos = $configuracion['active_modules'] ?? [];

        // Actividad reciente (ultimas 24 horas)
        $actividad_24h = 0;
        $tabla_actividad = $wpdb->prefix . 'flavor_activity_log';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla_actividad}'") === $tabla_actividad) {
            $actividad_24h = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_actividad}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
        }

        $datos_respuesta = [
            'success' => true,
            'data'    => [
                'usuarios' => [
                    'total'   => $total_usuarios['total_users'] ?? 0,
                    'hoy'     => (int) $usuarios_hoy,
                    'semana'  => (int) $usuarios_semana,
                    'roles'   => $total_usuarios['avail_roles'] ?? [],
                ],
                'contenido' => [
                    'posts'   => $total_posts->publish ?? 0,
                    'paginas' => $total_paginas->publish ?? 0,
                ],
                'modulos' => [
                    'activos' => count($modulos_activos),
                    'lista'   => $modulos_activos,
                ],
                'actividad' => [
                    'ultimas_24h' => $actividad_24h,
                ],
                'timestamp' => current_time('mysql'),
            ],
        ];

        return rest_ensure_response($datos_respuesta);
    }

    /**
     * Obtiene datos para los graficos del dashboard de admin
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response Respuesta con datos de graficos.
     */
    public function get_admin_dashboard_charts(WP_REST_Request $request) {
        global $wpdb;

        $periodo = $request->get_param('period') ?? 'month';

        // Normalizar periodo (soportar ambos formatos: week/weekly, month/monthly, etc.)
        $periodos_normalizados = [
            'weekly'  => 'week',
            'monthly' => 'month',
            'hourly'  => 'hour',
        ];
        $periodo_normalizado = $periodos_normalizados[$periodo] ?? $periodo;

        // Determinar rango de fechas
        switch ($periodo_normalizado) {
            case 'week':
                $dias = 7;
                $formato_fecha = '%Y-%m-%d';
                break;
            case 'year':
                $dias = 365;
                $formato_fecha = '%Y-%m';
                break;
            case 'hour':
                $dias = 1;
                $formato_fecha = '%H:00';
                break;
            default: // month
                $dias = 30;
                $formato_fecha = '%Y-%m-%d';
                break;
        }

        $fecha_inicio = date('Y-m-d', strtotime("-{$dias} days"));

        // Usuarios nuevos por periodo
        $usuarios_por_fecha = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE_FORMAT(user_registered, %s) as fecha, COUNT(*) as total
            FROM {$wpdb->users}
            WHERE user_registered >= %s
            GROUP BY fecha
            ORDER BY fecha ASC",
            $formato_fecha,
            $fecha_inicio
        ), ARRAY_A);

        // Actividad por modulo (si existe la tabla)
        $actividad_por_modulo = [];
        $tabla_actividad = $wpdb->prefix . 'flavor_activity_log';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla_actividad}'") === $tabla_actividad) {
            $actividad_por_modulo = $wpdb->get_results($wpdb->prepare(
                "SELECT module as modulo, COUNT(*) as total
                FROM {$tabla_actividad}
                WHERE created_at >= %s
                GROUP BY module
                ORDER BY total DESC
                LIMIT 10",
                $fecha_inicio
            ), ARRAY_A);
        }

        // Distribucion de roles
        $usuarios = count_users();
        $roles = [];
        if (!empty($usuarios['avail_roles'])) {
            foreach ($usuarios['avail_roles'] as $rol => $cantidad) {
                if ($cantidad > 0) {
                    $roles[] = [
                        'rol'    => $rol,
                        'nombre' => translate_user_role(ucfirst($rol)),
                        'total'  => $cantidad,
                    ];
                }
            }
        }

        $datos_respuesta = [
            'success' => true,
            'data'    => [
                'usuarios_nuevos' => [
                    'labels' => array_column($usuarios_por_fecha, 'fecha'),
                    'datos'  => array_map('intval', array_column($usuarios_por_fecha, 'total')),
                ],
                'actividad_modulos' => [
                    'labels' => array_column($actividad_por_modulo, 'modulo'),
                    'datos'  => array_map('intval', array_column($actividad_por_modulo, 'total')),
                ],
                'roles' => [
                    'labels' => array_column($roles, 'nombre'),
                    'datos'  => array_map('intval', array_column($roles, 'total')),
                ],
                'periodo'   => $periodo,
                'timestamp' => current_time('mysql'),
            ],
        ];

        return rest_ensure_response($datos_respuesta);
    }

    /**
     * Sincroniza con la red de nodos
     *
     * @param WP_REST_Request $request Peticion REST.
     * @return WP_REST_Response Respuesta de sincronizacion.
     */
    public function sync_network(WP_REST_Request $request) {
        // Verificar si existe el Network Manager
        if (!class_exists('Flavor_Network_Manager')) {
            return rest_ensure_response([
                'success' => false,
                'message' => __('El gestor de red no esta disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }

        $network_manager = Flavor_Network_Manager::get_instance();

        // Intentar sincronizar
        $resultado = null;
        if (method_exists($network_manager, 'sync_with_network')) {
            $resultado = $network_manager->sync_with_network();
        } elseif (method_exists($network_manager, 'sincronizar_red')) {
            $resultado = $network_manager->sincronizar_red();
        }

        if ($resultado === null) {
            return rest_ensure_response([
                'success' => true,
                'message' => __('Red sincronizada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'timestamp' => current_time('mysql'),
            ]);
        }

        return rest_ensure_response([
            'success' => $resultado ? true : false,
            'message' => $resultado
                ? __('Red sincronizada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN)
                : __('Error al sincronizar la red.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'timestamp' => current_time('mysql'),
        ]);
    }
}

// No inicializar aqui - se hace desde el archivo principal del plugin
