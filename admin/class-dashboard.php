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

        // Endpoints para Red de Comunidades
        register_rest_route('flavor/v1', '/admin/network-stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_obtener_estadisticas_red'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('flavor/v1', '/admin/network-sync', [
            'methods'             => 'POST',
            'callback'            => [$this, 'api_sincronizar_red'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('flavor/v1', '/admin/activity-map', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_obtener_mapa_actividad'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('flavor/v1', '/admin/export-stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_exportar_estadisticas_csv'],
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

        // Datos de Red de Comunidades
        $estadisticas_red = $this->obtener_estadisticas_red();
        $modulos_compartidos = $this->obtener_modulos_compartidos();
        $datos_mapa_actividad = $this->obtener_datos_mapa_actividad();
        $kpis_principales = $this->obtener_kpis_principales();
        $comparativas_temporales = $this->obtener_comparativas_temporales();

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

        // Obtener estadisticas por tipo de contenido
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
            'producto'     => __('Productos', 'flavor-chat-ia'),
            'servicio'     => __('Servicios', 'flavor-chat-ia'),
            'espacio'      => __('Espacios', 'flavor-chat-ia'),
            'recurso'      => __('Recursos', 'flavor-chat-ia'),
            'evento'       => __('Eventos', 'flavor-chat-ia'),
            'banco_tiempo' => __('Banco de Tiempo', 'flavor-chat-ia'),
            'saber'        => __('Saberes', 'flavor-chat-ia'),
            'excedente'    => __('Excedentes', 'flavor-chat-ia'),
            'necesidad'    => __('Necesidades', 'flavor-chat-ia'),
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
        $where_clauses = ["estado = 'activo'", "latitud IS NOT NULL", "longitud IS NOT NULL"];
        $where_values = [];

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

        if (!empty($where_values)) {
            $nodos = $wpdb->get_results($wpdb->prepare($sql_query, $where_values));
        } else {
            $nodos = $wpdb->get_results($sql_query);
        }

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
            'etiqueta'        => __('Usuarios activos (30d)', 'flavor-chat-ia'),
            'icono'           => 'dashicons-admin-users',
            'tendencia'       => $tendencia_usuarios,
            'tendencia_tipo'  => $tendencia_usuarios >= 0 ? 'positiva' : 'negativa',
            'periodo'         => __('vs. mes anterior', 'flavor-chat-ia'),
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
            'etiqueta'        => __('Conversaciones este mes', 'flavor-chat-ia'),
            'icono'           => 'dashicons-format-chat',
            'tendencia'       => $tendencia_conversaciones,
            'tendencia_tipo'  => $tendencia_conversaciones >= 0 ? 'positiva' : 'negativa',
            'periodo'         => __('vs. mes anterior', 'flavor-chat-ia'),
        ];

        // Nodos de red
        $estadisticas_red = $this->obtener_estadisticas_red();
        $kpis['nodos_red'] = [
            'valor'           => $estadisticas_red['nodos_activos'],
            'etiqueta'        => __('Nodos activos', 'flavor-chat-ia'),
            'icono'           => 'dashicons-networking',
            'tendencia'       => 0,
            'tendencia_tipo'  => 'neutral',
            'subtitulo'       => sprintf(__('%d conexiones federadas', 'flavor-chat-ia'), $estadisticas_red['conexiones_federadas']),
        ];

        // Contenido compartido
        $kpis['contenido_compartido'] = [
            'valor'           => $estadisticas_red['contenido_compartido'],
            'etiqueta'        => __('Items compartidos', 'flavor-chat-ia'),
            'icono'           => 'dashicons-share',
            'tendencia'       => 0,
            'tendencia_tipo'  => 'neutral',
            'subtitulo'       => __('En la red', 'flavor-chat-ia'),
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

            $datos['etiquetas'][] = gmdate_i18n('M', $fecha_mes);
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

            $datos['etiquetas'][] = gmdate_i18n('d M', strtotime($fecha_inicio));
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
                'message'    => __('Sincronizacion completada', 'flavor-chat-ia'),
                'timestamp'  => current_time('c'),
            ];
        }

        return [
            'success' => false,
            'message' => __('El sistema de red no esta disponible', 'flavor-chat-ia'),
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
