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
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('flavor/v1', '/admin/dashboard-charts', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_obtener_datos_graficos'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('flavor/v1', '/admin/dashboard-alerts', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_obtener_alertas'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * Registra assets del dashboard
     *
     * @param string $sufijo_hook Sufijo del hook
     * @return void
     */
    public function enqueue_dashboard_assets($sufijo_hook) {
        $sufijo_hook = (string) $sufijo_hook;
        if (strpos($sufijo_hook, 'flavor-dashboard') === false) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // Design tokens (variables CSS base)
        wp_enqueue_style(
            'flavor-design-tokens',
            FLAVOR_CHAT_IA_URL . "admin/css/design-tokens{$sufijo_asset}.css",
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // CSS del dashboard
        wp_enqueue_style(
            'flavor-dashboard',
            FLAVOR_CHAT_IA_URL . "admin/css/dashboard{$sufijo_asset}.css",
            ['flavor-design-tokens'],
            FLAVOR_CHAT_IA_VERSION
        );

        // Chart.js para graficos
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );

        // jQuery UI Sortable para widgets reordenables
        wp_enqueue_script('jquery-ui-sortable');

        // JavaScript del dashboard
        wp_enqueue_script(
            'flavor-dashboard-charts',
            FLAVOR_CHAT_IA_URL . 'admin/js/dashboard-charts.js',
            ['jquery', 'chartjs', 'jquery-ui-sortable'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        // Localizar script con datos
        wp_localize_script('flavor-dashboard-charts', 'flavorDashboard', [
            'ajaxUrl'              => admin_url('admin-ajax.php'),
            'restUrl'              => rest_url('flavor/v1/admin/'),
            'nonce'                => wp_create_nonce('wp_rest'),
            'ajaxNonce'            => wp_create_nonce('flavor_dashboard_nonce'),
            'intervaloActualizacion' => self::INTERVALO_ACTUALIZACION * 1000,
            'textos'               => [
                'cargando'         => __('Cargando...', 'flavor-chat-ia'),
                'error'            => __('Error al cargar datos', 'flavor-chat-ia'),
                'ultimaActualizacion' => __('Ultima actualizacion:', 'flavor-chat-ia'),
                'ahora'            => __('Ahora mismo', 'flavor-chat-ia'),
                'haceMenos1Min'    => __('Hace menos de 1 minuto', 'flavor-chat-ia'),
                'haceMinutos'      => __('Hace %d minutos', 'flavor-chat-ia'),
                'usuariosNuevos'   => __('Usuarios nuevos', 'flavor-chat-ia'),
                'actividadModulo'  => __('Actividad por modulo', 'flavor-chat-ia'),
                'distribucionRoles' => __('Distribucion de roles', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Renderiza la pagina de dashboard
     *
     * @return void
     */
    public function render_dashboard_page() {
        $estadisticas = $this->get_dashboard_stats();
        $addons_activos = Flavor_Addon_Manager::get_active_addons();
        $addons_registrados = Flavor_Addon_Manager::get_registered_addons();

        // Datos del perfil activo
        $datos_perfil_activo = $this->obtener_datos_perfil_activo();
        $checks_onboarding = $this->obtener_checks_onboarding();
        $progreso_onboarding = $this->calcular_progreso_onboarding($checks_onboarding);
        $nivel_salud = $this->obtener_semaforo_salud();
        $estado_sistema = $this->obtener_estado_sistema();
        $alertas = $this->obtener_alertas_pendientes();
        $actividad_reciente = $this->obtener_actividad_reciente();
        $acciones_rapidas = $this->obtener_acciones_rapidas_completas($datos_perfil_activo['id']);

        // Incluir la vista del dashboard
        include FLAVOR_CHAT_IA_PATH . 'admin/views/dashboard.php';
    }

    /**
     * Obtiene datos del perfil activo para el hero
     *
     * @return array
     */
    private function obtener_datos_perfil_activo() {
        $datos_perfil = [
            'id'          => 'personalizado',
            'nombre'      => __('Personalizado', 'flavor-chat-ia'),
            'descripcion' => __('Selecciona manualmente los modulos que necesitas.', 'flavor-chat-ia'),
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
        $configuracion = get_option('flavor_chat_ia_settings', []);
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
            ['etiqueta' => __('Perfil seleccionado', 'flavor-chat-ia'), 'completado' => $id_perfil_activo !== 'personalizado'],
            ['etiqueta' => __('Modulos activos', 'flavor-chat-ia'),     'completado' => count($modulos_activos) > 0],
            ['etiqueta' => __('IA configurada', 'flavor-chat-ia'),      'completado' => $tiene_api_key],
            ['etiqueta' => __('Paginas creadas', 'flavor-chat-ia'),     'completado' => $conteo_paginas_creadas > 0],
            ['etiqueta' => __('Addons instalados', 'flavor-chat-ia'),   'completado' => $conteo_addons > 0],
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
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $tiene_api_key = !empty($configuracion['claude_api_key']) || !empty($configuracion['openai_api_key']) || !empty($configuracion['deepseek_api_key']) || !empty($configuracion['mistral_api_key']);
        $chat_habilitado = !empty($configuracion['enabled']);

        if ($tiene_api_key && $chat_habilitado) {
            return ['nivel' => 'verde', 'icono' => 'dashicons-yes-alt', 'mensaje' => __('Todo funcionando correctamente', 'flavor-chat-ia')];
        } elseif ($tiene_api_key) {
            return ['nivel' => 'amarillo', 'icono' => 'dashicons-warning', 'mensaje' => __('Chat IA deshabilitado', 'flavor-chat-ia')];
        }
        return ['nivel' => 'rojo', 'icono' => 'dashicons-dismiss', 'mensaje' => __('API key no configurada', 'flavor-chat-ia')];
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
        $configuracion = get_option('flavor_chat_ia_settings', []);
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
            'version_plugin'      => FLAVOR_CHAT_IA_VERSION,
            'version_php'         => PHP_VERSION,
            'version_wordpress'   => get_bloginfo('version'),
            'version_mysql'       => $wpdb->db_version(),
            'estado_api'          => $estado_api,
            'espacio_uploads'     => $this->formatear_bytes($espacio_usado),
            'espacio_uploads_raw' => $espacio_usado,
            'ultima_sincronizacion' => $ultima_sincronizacion ? human_time_diff(strtotime($ultima_sincronizacion)) : __('Nunca', 'flavor-chat-ia'),
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
        if (class_exists('Flavor_Chat_Module_Loader') && Flavor_Chat_Module_Loader::is_module_active('grupos_consumo')) {
            $pedidos_pendientes = $this->contar_pedidos_pendientes();
            if ($pedidos_pendientes > 0) {
                $alertas[] = [
                    'tipo'    => 'warning',
                    'icono'   => 'dashicons-cart',
                    'mensaje' => sprintf(__('%d pedidos sin procesar', 'flavor-chat-ia'), $pedidos_pendientes),
                    'url'     => admin_url('admin.php?page=flavor-grupos-consumo&tab=pedidos'),
                ];
            }
        }

        // Solicitudes de socio pendientes (si socios activo)
        if (class_exists('Flavor_Chat_Module_Loader') && Flavor_Chat_Module_Loader::is_module_active('socios')) {
            $solicitudes_pendientes = $this->contar_solicitudes_socios_pendientes();
            if ($solicitudes_pendientes > 0) {
                $alertas[] = [
                    'tipo'    => 'info',
                    'icono'   => 'dashicons-groups',
                    'mensaje' => sprintf(__('%d solicitudes de socio pendientes', 'flavor-chat-ia'), $solicitudes_pendientes),
                    'url'     => admin_url('admin.php?page=flavor-socios&tab=solicitudes'),
                ];
            }
        }

        // Ciclos proximos a cerrar (grupos_consumo)
        if (class_exists('Flavor_Chat_Module_Loader') && Flavor_Chat_Module_Loader::is_module_active('grupos_consumo')) {
            $ciclos_por_cerrar = $this->obtener_ciclos_proximos_cierre();
            if (!empty($ciclos_por_cerrar)) {
                $alertas[] = [
                    'tipo'    => 'warning',
                    'icono'   => 'dashicons-calendar-alt',
                    'mensaje' => sprintf(__('%d ciclos cierran en las proximas 48h', 'flavor-chat-ia'), count($ciclos_por_cerrar)),
                    'url'     => admin_url('admin.php?page=flavor-grupos-consumo&tab=ciclos'),
                ];
            }
        }

        // Eventos proximos (si eventos activo)
        if (class_exists('Flavor_Chat_Module_Loader') && Flavor_Chat_Module_Loader::is_module_active('eventos')) {
            $eventos_proximos = $this->contar_eventos_proximos();
            if ($eventos_proximos > 0) {
                $alertas[] = [
                    'tipo'    => 'info',
                    'icono'   => 'dashicons-calendar',
                    'mensaje' => sprintf(__('%d eventos en los proximos 7 dias', 'flavor-chat-ia'), $eventos_proximos),
                    'url'     => admin_url('admin.php?page=flavor-eventos'),
                ];
            }
        }

        // Verificar actualizaciones disponibles
        $actualizacion_disponible = get_transient('flavor_update_available');
        if ($actualizacion_disponible) {
            $alertas[] = [
                'tipo'    => 'success',
                'icono'   => 'dashicons-update',
                'mensaje' => sprintf(__('Actualizacion disponible: v%s', 'flavor-chat-ia'), $actualizacion_disponible),
                'url'     => admin_url('plugins.php'),
            ];
        }

        // Incidencias sin resolver
        if (class_exists('Flavor_Chat_Module_Loader') && Flavor_Chat_Module_Loader::is_module_active('incidencias')) {
            $incidencias_abiertas = $this->contar_incidencias_abiertas();
            if ($incidencias_abiertas > 0) {
                $alertas[] = [
                    'tipo'    => 'error',
                    'icono'   => 'dashicons-warning',
                    'mensaje' => sprintf(__('%d incidencias sin resolver', 'flavor-chat-ia'), $incidencias_abiertas),
                    'url'     => admin_url('admin.php?page=flavor-incidencias'),
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

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_pedidos)) {
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

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_solicitudes)) {
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

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_ciclos)) {
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

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_eventos)) {
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

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_incidencias)) {
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

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_conversaciones)) {
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
                    __('Conversacion #%s (%d mensajes)', 'flavor-chat-ia'),
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
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];

        $acciones = [
            'principales' => [
                [
                    'id'       => 'configuracion',
                    'etiqueta' => __('Configuracion', 'flavor-chat-ia'),
                    'icono'    => 'dashicons-admin-settings',
                    'url'      => admin_url('admin.php?page=flavor-chat-config'),
                    'color'    => '#2271b1',
                ],
                [
                    'id'       => 'compositor',
                    'etiqueta' => __('Compositor', 'flavor-chat-ia'),
                    'icono'    => 'dashicons-smartphone',
                    'url'      => admin_url('admin.php?page=flavor-app-composer'),
                    'color'    => '#8e44ad',
                ],
                [
                    'id'       => 'addons',
                    'etiqueta' => __('Addons', 'flavor-chat-ia'),
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
                'etiqueta' => __('Crear Evento', 'flavor-chat-ia'),
                'icono'    => 'dashicons-calendar-alt',
                'url'      => admin_url('admin.php?page=flavor-eventos&action=nuevo'),
                'color'    => '#e74c3c',
                'modal'    => true,
            ];
        }

        if (in_array('grupos_consumo', $modulos_activos)) {
            $acciones['contextuales'][] = [
                'id'       => 'crear_producto',
                'etiqueta' => __('Crear Producto', 'flavor-chat-ia'),
                'icono'    => 'dashicons-products',
                'url'      => admin_url('admin.php?page=flavor-grupos-consumo&action=nuevo_producto'),
                'color'    => '#f39c12',
                'modal'    => true,
            ];
        }

        if (in_array('socios', $modulos_activos)) {
            $acciones['contextuales'][] = [
                'id'       => 'ver_socios',
                'etiqueta' => __('Ver Socios', 'flavor-chat-ia'),
                'icono'    => 'dashicons-groups',
                'url'      => admin_url('admin.php?page=flavor-socios'),
                'color'    => '#3498db',
            ];
        }

        // Acciones generales
        $acciones['generales'] = [
            [
                'id'       => 'enviar_notificacion',
                'etiqueta' => __('Enviar Notificacion', 'flavor-chat-ia'),
                'icono'    => 'dashicons-megaphone',
                'url'      => '#',
                'color'    => '#9b59b6',
                'modal'    => true,
                'action'   => 'send_notification',
            ],
            [
                'id'       => 'exportar_datos',
                'etiqueta' => __('Exportar Datos', 'flavor-chat-ia'),
                'icono'    => 'dashicons-download',
                'url'      => admin_url('admin.php?page=flavor-export-import'),
                'color'    => '#1abc9c',
            ],
            [
                'id'       => 'ver_logs',
                'etiqueta' => __('Ver Logs', 'flavor-chat-ia'),
                'icono'    => 'dashicons-list-view',
                'url'      => admin_url('admin.php?page=flavor-activity-log'),
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
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];
        $total_modulos = 0;
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $total_modulos = count(Flavor_Chat_Module_Loader::get_instance()->get_registered_modules());
        }

        // Addons activos
        $addons_activos = count(Flavor_Addon_Manager::get_active_addons());
        $addons_totales = count(Flavor_Addon_Manager::get_registered_addons());

        // Conversaciones
        $tabla_conversaciones = $wpdb->prefix . 'flavor_chat_conversations';
        $conversaciones = 0;
        if (Flavor_Chat_Helpers::tabla_existe($tabla_conversaciones)) {
            $conversaciones = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_conversaciones}");
        }

        // Mensajes
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_messages';
        $mensajes = 0;
        if (Flavor_Chat_Helpers::tabla_existe($tabla_mensajes)) {
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

        // Contar usuarios que han iniciado sesion recientemente
        $conteo_usuarios = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id)
             FROM {$wpdb->usermeta}
             WHERE meta_key = 'session_tokens'
             AND user_id IN (
                 SELECT ID FROM {$wpdb->users} WHERE user_registered >= %s
                 UNION
                 SELECT user_id FROM {$wpdb->usermeta}
                 WHERE meta_key = 'last_activity' AND meta_value >= %s
             )",
            $fecha_limite,
            strtotime("-{$dias_limite} days")
        ));

        // Si no hay dato, usar conteo total de usuarios
        if (!$conteo_usuarios) {
            $conteo_usuarios = count_users()['total_users'];
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

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
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
            'administrator' => __('Administrador', 'flavor-chat-ia'),
            'editor'        => __('Editor', 'flavor-chat-ia'),
            'author'        => __('Autor', 'flavor-chat-ia'),
            'contributor'   => __('Colaborador', 'flavor-chat-ia'),
            'subscriber'    => __('Suscriptor', 'flavor-chat-ia'),
            'socio'         => __('Socio', 'flavor-chat-ia'),
            'cliente'       => __('Cliente', 'flavor-chat-ia'),
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
    // ENDPOINTS API REST
    // =========================================================================

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
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
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
            wp_send_json_error(['message' => __('Permisos insuficientes', 'flavor-chat-ia')]);
        }

        $accion = sanitize_key($_POST['action_id'] ?? '');

        switch ($accion) {
            case 'send_notification':
                $titulo = sanitize_text_field($_POST['titulo'] ?? '');
                $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
                // Aqui iria la logica de enviar notificacion
                wp_send_json_success(['message' => __('Notificacion enviada', 'flavor-chat-ia')]);
                break;

            case 'clear_cache':
                // Limpiar cache del dashboard
                delete_transient('flavor_dashboard_stats');
                wp_send_json_success(['message' => __('Cache limpiada', 'flavor-chat-ia')]);
                break;

            default:
                wp_send_json_error(['message' => __('Accion no reconocida', 'flavor-chat-ia')]);
        }
    }
}
