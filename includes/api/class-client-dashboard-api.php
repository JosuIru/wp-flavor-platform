<?php
/**
 * API REST para el Dashboard de Cliente
 *
 * Endpoints para que los usuarios finales (clientes) accedan a su
 * dashboard personal: estadisticas, actividad, notificaciones y widgets.
 *
 * @package FlavorChatIA
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
                    'description' => __('Incluir datos de widgets', 'flavor-chat-ia'),
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
                    'description' => __('Periodo de estadisticas: week, month, year, all', 'flavor-chat-ia'),
                    'type'        => 'string',
                    'default'     => 'month',
                    'enum'        => ['week', 'month', 'year', 'all'],
                ],
            ],
        ]);

        // GET /wp-json/flavor/v1/client/activity
        // Timeline de actividad reciente
        register_rest_route(self::API_NAMESPACE, '/client/activity', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_activity'],
            'permission_callback' => [$this, 'check_user_authenticated'],
            'args'                => [
                'page' => [
                    'description' => __('Numero de pagina', 'flavor-chat-ia'),
                    'type'        => 'integer',
                    'default'     => 1,
                    'minimum'     => 1,
                ],
                'per_page' => [
                    'description' => __('Elementos por pagina', 'flavor-chat-ia'),
                    'type'        => 'integer',
                    'default'     => 20,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ],
                'type' => [
                    'description' => __('Filtrar por tipo de actividad', 'flavor-chat-ia'),
                    'type'        => 'string',
                    'default'     => '',
                ],
                'module' => [
                    'description' => __('Filtrar por modulo', 'flavor-chat-ia'),
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
                    'description' => __('Numero de pagina', 'flavor-chat-ia'),
                    'type'        => 'integer',
                    'default'     => 1,
                    'minimum'     => 1,
                ],
                'per_page' => [
                    'description' => __('Elementos por pagina', 'flavor-chat-ia'),
                    'type'        => 'integer',
                    'default'     => 20,
                    'minimum'     => 1,
                    'maximum'     => 100,
                ],
                'unread_only' => [
                    'description' => __('Solo notificaciones sin leer', 'flavor-chat-ia'),
                    'type'        => 'boolean',
                    'default'     => false,
                ],
                'type' => [
                    'description' => __('Filtrar por tipo de notificacion', 'flavor-chat-ia'),
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
                    'description' => __('IDs de notificaciones a marcar como leidas', 'flavor-chat-ia'),
                    'type'        => 'array',
                    'items'       => ['type' => 'integer'],
                    'default'     => [],
                ],
                'mark_all' => [
                    'description' => __('Marcar todas las notificaciones como leidas', 'flavor-chat-ia'),
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
                    'description' => __('IDs especificos de widgets a obtener', 'flavor-chat-ia'),
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
                    'description' => __('Lista de widgets visibles', 'flavor-chat-ia'),
                    'type'        => 'array',
                    'items'       => ['type' => 'string'],
                ],
                'widget_order' => [
                    'description' => __('Orden de los widgets', 'flavor-chat-ia'),
                    'type'        => 'array',
                    'items'       => ['type' => 'string'],
                ],
                'theme' => [
                    'description' => __('Tema del dashboard: light, dark, auto', 'flavor-chat-ia'),
                    'type'        => 'string',
                    'enum'        => ['light', 'dark', 'auto'],
                ],
                'notifications_enabled' => [
                    'description' => __('Notificaciones habilitadas', 'flavor-chat-ia'),
                    'type'        => 'boolean',
                ],
                'compact_mode' => [
                    'description' => __('Modo compacto', 'flavor-chat-ia'),
                    'type'        => 'boolean',
                ],
            ],
        ]);
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
                __('Debes iniciar sesion para acceder a este recurso.', 'flavor-chat-ia'),
                ['status' => 401]
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

        if ($datos_cacheados !== false && !defined('WP_DEBUG') || !WP_DEBUG) {
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
                __('El sistema de notificaciones no esta disponible.', 'flavor-chat-ia'),
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
            'marked_count' => $cantidad_marcadas === -1 ? __('todas', 'flavor-chat-ia') : $cantidad_marcadas,
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
                __('Preferencias del dashboard actualizadas', 'flavor-chat-ia'),
                [
                    'usuario_id' => $id_usuario_actual,
                    'datos_extra' => $preferencias_nuevas,
                ]
            );
        }

        return rest_ensure_response([
            'success'     => true,
            'preferences' => $preferencias_actualizadas,
            'message'     => __('Preferencias guardadas correctamente.', 'flavor-chat-ia'),
        ]);
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
            'member_since' => sprintf(__('Miembro desde hace %s', 'flavor-chat-ia'), $tiempo_miembro),
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
        $configuracion_settings = get_option('flavor_chat_ia_settings', []);
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
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_actividad)) {
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
                'date_human'  => human_time_diff(strtotime($registro->fecha), current_time('timestamp')) . ' ' . __('ago', 'flavor-chat-ia'),
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

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_actividad)) {
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
            'info'        => __('Informacion', 'flavor-chat-ia'),
            'exito'       => __('Exito', 'flavor-chat-ia'),
            'advertencia' => __('Advertencia', 'flavor-chat-ia'),
            'error'       => __('Error', 'flavor-chat-ia'),
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
                'date_human' => human_time_diff(strtotime($notificacion->created_at), current_time('timestamp')) . ' ' . __('ago', 'flavor-chat-ia'),
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

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_notificaciones)) {
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
            'title'       => __('Resumen Rapido', 'flavor-chat-ia'),
            'description' => __('Tus estadisticas principales de un vistazo', 'flavor-chat-ia'),
            'icon'        => 'dashicons-chart-bar',
            'data'        => $this->calculate_user_stats($id_usuario, 'month'),
            'size'        => 'medium',
        ];

        // Widget de actividad reciente
        $widgets[] = [
            'id'          => 'recent_activity',
            'type'        => 'activity',
            'title'       => __('Actividad Reciente', 'flavor-chat-ia'),
            'description' => __('Tus ultimas acciones en la plataforma', 'flavor-chat-ia'),
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
            'title'       => __('Notificaciones', 'flavor-chat-ia'),
            'description' => __('Tus notificaciones pendientes', 'flavor-chat-ia'),
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
            'title'       => __('Tu Progreso', 'flavor-chat-ia'),
            'description' => __('Nivel, puntos y logros', 'flavor-chat-ia'),
            'icon'        => 'dashicons-awards',
            'data'        => $datos_gamificacion,
            'size'        => 'small',
        ];

        // Obtener widgets de modulos activos
        $configuracion_settings = get_option('flavor_chat_ia_settings', []);
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

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_reservas)) {
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
}

// No inicializar aqui - se hace desde el archivo principal del plugin
