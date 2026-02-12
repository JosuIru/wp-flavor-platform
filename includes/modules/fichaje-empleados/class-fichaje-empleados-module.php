<?php
/**
 * Módulo de Fichaje de Empleados para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Fichaje de Empleados - Control de horarios y asistencia
 */
class Flavor_Chat_Fichaje_Empleados_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'fichaje_empleados';
        $this->name = 'Fichaje de Empleados'; // Translation loaded on init
        $this->description = 'Control de horarios, asistencia y fichaje de empleados desde la app movil.'; // Translation loaded on init

        // Configurar visibilidad por defecto: privado (solo empleados con permiso especifico)
        $this->default_visibility = 'private';
        $this->required_capability = 'flavor_fichaje_acceso';

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        return Flavor_Chat_Helpers::tabla_existe($tabla_fichajes);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Fichajes no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-chat-ia');
        }
        
    return '';
    }

/**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'horario_entrada' => '09:00',
            'horario_salida' => '18:00',
            'tiempo_gracia' => 15, // minutos
            'requiere_geolocalizacion' => false,
            'radio_maximo' => 100, // metros
            'dias_laborables' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'permite_fichaje_remoto' => true,
            'notificar_retrasos' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();
    }

    /**
     * Registrar rutas REST API para APKs
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // Registrar entrada
        register_rest_route($namespace, '/fichaje/entrada', [
            'methods' => 'POST',
            'callback' => [$this, 'api_registrar_entrada'],
            'permission_callback' => [$this, 'verificar_autenticacion_api'],
            'args' => [
                'notas' => [
                    'type' => 'string',
                    'description' => 'Notas opcionales sobre el fichaje',
                ],
                'latitud' => [
                    'type' => 'number',
                    'description' => 'Latitud de la ubicacion',
                ],
                'longitud' => [
                    'type' => 'number',
                    'description' => 'Longitud de la ubicacion',
                ],
                'dispositivo' => [
                    'type' => 'string',
                    'description' => 'Identificador del dispositivo',
                ],
            ],
        ]);

        // Registrar salida
        register_rest_route($namespace, '/fichaje/salida', [
            'methods' => 'POST',
            'callback' => [$this, 'api_registrar_salida'],
            'permission_callback' => [$this, 'verificar_autenticacion_api'],
            'args' => [
                'notas' => [
                    'type' => 'string',
                    'description' => 'Notas opcionales sobre el fichaje',
                ],
                'latitud' => [
                    'type' => 'number',
                    'description' => 'Latitud de la ubicacion',
                ],
                'longitud' => [
                    'type' => 'number',
                    'description' => 'Longitud de la ubicacion',
                ],
                'dispositivo' => [
                    'type' => 'string',
                    'description' => 'Identificador del dispositivo',
                ],
            ],
        ]);

        // Estado actual del usuario
        register_rest_route($namespace, '/fichaje/estado', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_estado'],
            'permission_callback' => [$this, 'verificar_autenticacion_api'],
        ]);

        // Historial de fichajes
        register_rest_route($namespace, '/fichaje/historial', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_historial'],
            'permission_callback' => [$this, 'verificar_autenticacion_api'],
            'args' => [
                'desde' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Fecha de inicio del periodo (YYYY-MM-DD)',
                ],
                'hasta' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Fecha de fin del periodo (YYYY-MM-DD)',
                ],
                'tipo' => [
                    'type' => 'string',
                    'enum' => ['entrada', 'salida', 'pausa_inicio', 'pausa_fin'],
                    'description' => 'Filtrar por tipo de fichaje',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 50,
                    'description' => 'Numero maximo de resultados',
                ],
            ],
        ]);

        // Resumen mensual
        register_rest_route($namespace, '/fichaje/resumen', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_resumen'],
            'permission_callback' => [$this, 'verificar_autenticacion_api'],
            'args' => [
                'mes' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 12,
                    'description' => 'Mes del resumen (1-12)',
                ],
                'anio' => [
                    'type' => 'integer',
                    'description' => 'Anio del resumen (YYYY)',
                ],
            ],
        ]);

        // Iniciar pausa
        register_rest_route($namespace, '/fichaje/pausa/iniciar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_iniciar_pausa'],
            'permission_callback' => [$this, 'verificar_autenticacion_api'],
            'args' => [
                'tipo_pausa' => [
                    'type' => 'string',
                    'enum' => ['comida', 'descanso', 'reunion', 'otros'],
                    'description' => 'Tipo de pausa',
                ],
            ],
        ]);

        // Finalizar pausa
        register_rest_route($namespace, '/fichaje/pausa/finalizar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_finalizar_pausa'],
            'permission_callback' => [$this, 'verificar_autenticacion_api'],
        ]);

        // Fichajes del dia actual
        register_rest_route($namespace, '/fichaje/hoy', [
            'methods' => 'GET',
            'callback' => [$this, 'api_fichajes_hoy'],
            'permission_callback' => [$this, 'verificar_autenticacion_api'],
        ]);
    }

    /**
     * Verifica que el usuario este autenticado para la API
     *
     * @return bool|WP_Error
     */
    public function verificar_autenticacion_api() {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return new WP_Error(
                'rest_not_logged_in',
                __('Debes iniciar sesion para acceder a esta funcion.', 'flavor-chat-ia'),
                ['status' => 401]
            );
        }

        return true;
    }

    // =========================================================================
    // Metodos API REST
    // =========================================================================

    /**
     * API: Registrar entrada
     */
    public function api_registrar_entrada($request) {
        $parametros = [
            'tipo' => 'entrada',
            'notas' => $request->get_param('notas'),
            'latitud' => $request->get_param('latitud'),
            'longitud' => $request->get_param('longitud'),
            'dispositivo' => $request->get_param('dispositivo') ?: 'app_movil',
        ];

        $resultado = $this->action_fichar($parametros);

        if (!$resultado['success']) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $resultado['error'],
            ], 400);
        }

        return new WP_REST_Response($resultado, 201);
    }

    /**
     * API: Registrar salida
     */
    public function api_registrar_salida($request) {
        $parametros = [
            'tipo' => 'salida',
            'notas' => $request->get_param('notas'),
            'latitud' => $request->get_param('latitud'),
            'longitud' => $request->get_param('longitud'),
            'dispositivo' => $request->get_param('dispositivo') ?: 'app_movil',
        ];

        $resultado = $this->action_fichar($parametros);

        if (!$resultado['success']) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $resultado['error'],
            ], 400);
        }

        return new WP_REST_Response($resultado, 201);
    }

    /**
     * API: Obtener estado actual del usuario
     */
    public function api_obtener_estado($request) {
        $resultado = $this->action_estado_actual([]);

        if (!$resultado['success']) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $resultado['error'],
            ], 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Obtener historial de fichajes
     */
    public function api_obtener_historial($request) {
        $usuario_id = get_current_user_id();
        $desde = $request->get_param('desde');
        $hasta = $request->get_param('hasta');
        $tipo = $request->get_param('tipo');
        $limite = $request->get_param('limite') ?: 50;

        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        $condiciones_where = ['usuario_id = %d'];
        $valores_preparados = [$usuario_id];

        if ($desde) {
            $condiciones_where[] = 'DATE(fecha_hora) >= %s';
            $valores_preparados[] = sanitize_text_field($desde);
        }

        if ($hasta) {
            $condiciones_where[] = 'DATE(fecha_hora) <= %s';
            $valores_preparados[] = sanitize_text_field($hasta);
        }

        if ($tipo) {
            $condiciones_where[] = 'tipo = %s';
            $valores_preparados[] = sanitize_text_field($tipo);
        }

        $valores_preparados[] = absint($limite);

        $clausula_where = implode(' AND ', $condiciones_where);
        $consulta_sql = "SELECT * FROM $tabla_fichajes WHERE $clausula_where ORDER BY fecha_hora DESC LIMIT %d";

        $fichajes = $wpdb->get_results($wpdb->prepare($consulta_sql, ...$valores_preparados));

        $fichajes_formateados = array_map(function($fichaje) {
            return [
                'id' => $fichaje->id,
                'tipo' => $fichaje->tipo,
                'fecha' => date('Y-m-d', strtotime($fichaje->fecha_hora)),
                'hora' => date('H:i', strtotime($fichaje->fecha_hora)),
                'fecha_hora' => $fichaje->fecha_hora,
                'notas' => $fichaje->notas,
                'validado' => (bool) $fichaje->validado,
                'latitud' => $fichaje->latitud,
                'longitud' => $fichaje->longitud,
                'dispositivo' => $fichaje->dispositivo,
            ];
        }, $fichajes);

        return new WP_REST_Response([
            'success' => true,
            'total' => count($fichajes_formateados),
            'fichajes' => $fichajes_formateados,
        ], 200);
    }

    /**
     * API: Obtener resumen mensual
     */
    public function api_obtener_resumen($request) {
        $usuario_id = get_current_user_id();
        $mes = $request->get_param('mes') ?: (int) date('m');
        $anio = $request->get_param('anio') ?: (int) date('Y');

        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        // Obtener todos los fichajes del mes
        $fichajes_del_mes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_fichajes
            WHERE usuario_id = %d
            AND MONTH(fecha_hora) = %d
            AND YEAR(fecha_hora) = %d
            ORDER BY fecha_hora ASC",
            $usuario_id,
            $mes,
            $anio
        ));

        // Agrupar fichajes por dia
        $fichajes_por_dia = [];
        foreach ($fichajes_del_mes as $fichaje) {
            $fecha_dia = date('Y-m-d', strtotime($fichaje->fecha_hora));
            if (!isset($fichajes_por_dia[$fecha_dia])) {
                $fichajes_por_dia[$fecha_dia] = [];
            }
            $fichajes_por_dia[$fecha_dia][] = $fichaje;
        }

        // Calcular horas por dia
        $dias_trabajados = 0;
        $total_horas = 0;
        $total_pausas = 0;
        $detalle_dias = [];

        foreach ($fichajes_por_dia as $fecha => $fichajes_dia) {
            $horas_dia = $this->calcular_horas_trabajadas($fichajes_dia);
            $pausas_dia = $this->calcular_tiempo_pausas($fichajes_dia);

            if ($horas_dia > 0) {
                $dias_trabajados++;
                $total_horas += $horas_dia;
                $total_pausas += $pausas_dia;

                $detalle_dias[] = [
                    'fecha' => $fecha,
                    'horas_trabajadas' => round($horas_dia, 2),
                    'tiempo_pausas' => round($pausas_dia, 2),
                    'fichajes' => count($fichajes_dia),
                ];
            }
        }

        // Calcular promedios
        $promedio_horas_diarias = $dias_trabajados > 0 ? $total_horas / $dias_trabajados : 0;

        return new WP_REST_Response([
            'success' => true,
            'resumen' => [
                'mes' => $mes,
                'anio' => $anio,
                'dias_trabajados' => $dias_trabajados,
                'total_horas' => round($total_horas, 2),
                'total_pausas' => round($total_pausas, 2),
                'promedio_horas_diarias' => round($promedio_horas_diarias, 2),
            ],
            'detalle_dias' => $detalle_dias,
            'mensaje' => sprintf(
                __('En %s/%d has trabajado %d dias con un total de %.2f horas.', 'flavor-chat-ia'),
                str_pad($mes, 2, '0', STR_PAD_LEFT),
                $anio,
                $dias_trabajados,
                $total_horas
            ),
        ], 200);
    }

    /**
     * API: Iniciar pausa
     */
    public function api_iniciar_pausa($request) {
        $tipo_pausa = $request->get_param('tipo_pausa') ?: 'descanso';

        $parametros = [
            'tipo' => 'pausa_inicio',
            'notas' => sprintf(__('Pausa: %s', 'flavor-chat-ia'), $tipo_pausa),
        ];

        $resultado = $this->action_fichar($parametros);

        if (!$resultado['success']) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $resultado['error'],
            ], 400);
        }

        return new WP_REST_Response($resultado, 201);
    }

    /**
     * API: Finalizar pausa
     */
    public function api_finalizar_pausa($request) {
        $parametros = [
            'tipo' => 'pausa_fin',
        ];

        $resultado = $this->action_fichar($parametros);

        if (!$resultado['success']) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $resultado['error'],
            ], 400);
        }

        return new WP_REST_Response($resultado, 201);
    }

    /**
     * API: Obtener fichajes del dia actual
     */
    public function api_fichajes_hoy($request) {
        $resultado = $this->action_ver_fichajes_hoy([]);

        if (!$resultado['success']) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $resultado['error'],
            ], 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * Calcula el tiempo de pausas del dia
     *
     * @param array $fichajes Lista de fichajes del dia
     * @return float Tiempo de pausas en horas
     */
    private function calcular_tiempo_pausas($fichajes) {
        $tiempo_pausas = 0;
        $inicio_pausa = null;

        foreach ($fichajes as $fichaje) {
            if ($fichaje->tipo === 'pausa_inicio') {
                $inicio_pausa = strtotime($fichaje->fecha_hora);
            } elseif ($fichaje->tipo === 'pausa_fin' && $inicio_pausa) {
                $fin_pausa = strtotime($fichaje->fecha_hora);
                $tiempo_pausas += ($fin_pausa - $inicio_pausa) / 3600;
                $inicio_pausa = null;
            }
        }

        return round($tiempo_pausas, 2);
    }

    /**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'fichaje_empleados',
            'label' => __('Fichaje', 'flavor-chat-ia'),
            'icon' => 'dashicons-clock',
            'capability' => 'manage_options',
            'categoria' => 'personas',
            'paginas' => [
                [
                    'slug' => 'fichaje-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                    'badge' => [$this, 'contar_fichajes_pendientes'],
                ],
                [
                    'slug' => 'fichaje-registros-hoy',
                    'titulo' => __('Registros de hoy', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_registros_hoy'],
                ],
                [
                    'slug' => 'fichaje-historial',
                    'titulo' => __('Historial', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_historial'],
                ],
                [
                    'slug' => 'fichaje-empleados',
                    'titulo' => __('Empleados', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_empleados'],
                ],
                [
                    'slug' => 'fichaje-config',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta fichajes pendientes de validar
     *
     * @return int
     */
    public function contar_fichajes_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_fichajes)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_fichajes WHERE validado = 0"
        );
    }

    /**
     * Estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';
        $estadisticas = [];

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_fichajes)) {
            return $estadisticas;
        }

        $hoy = date('Y-m-d');

        // Fichajes de hoy
        $fichajes_hoy = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_fichajes WHERE DATE(fecha_hora) = %s",
            $hoy
        ));
        $estadisticas[] = [
            'icon' => 'dashicons-clock',
            'valor' => $fichajes_hoy,
            'label' => __('Fichajes hoy', 'flavor-chat-ia'),
            'color' => $fichajes_hoy > 0 ? 'blue' : 'gray',
            'enlace' => admin_url('admin.php?page=fichaje-registros-hoy'),
        ];

        // Empleados activos (los que han fichado entrada pero no salida hoy)
        $empleados_activos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_fichajes
            WHERE DATE(fecha_hora) = %s
            AND tipo = 'entrada'
            AND usuario_id NOT IN (
                SELECT usuario_id FROM $tabla_fichajes
                WHERE DATE(fecha_hora) = %s
                AND tipo = 'salida'
            )",
            $hoy,
            $hoy
        ));
        $estadisticas[] = [
            'icon' => 'dashicons-groups',
            'valor' => $empleados_activos,
            'label' => __('Empleados activos', 'flavor-chat-ia'),
            'color' => $empleados_activos > 0 ? 'green' : 'gray',
            'enlace' => admin_url('admin.php?page=fichaje-dashboard'),
        ];

        // Fichajes pendientes de validar
        $pendientes_validar = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_fichajes WHERE validado = 0"
        );
        if ($pendientes_validar > 0) {
            $estadisticas[] = [
                'icon' => 'dashicons-warning',
                'valor' => $pendientes_validar,
                'label' => __('Pendientes validar', 'flavor-chat-ia'),
                'color' => 'orange',
                'enlace' => admin_url('admin.php?page=fichaje-historial&validado=0'),
            ];
        }

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard de fichaje
     */
    public function render_admin_dashboard() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Dashboard de Fichaje', 'flavor-chat-ia'));

        // Resumen del día
        $estadisticas = $this->get_estadisticas_dashboard();
        echo '<div class="flavor-stats-grid">';
        foreach ($estadisticas as $estadistica) {
            $color_class = isset($estadistica['color']) ? 'flavor-stat-' . $estadistica['color'] : '';
            echo '<div class="flavor-stat-card ' . esc_attr($color_class) . '">';
            echo '<span class="dashicons ' . esc_attr($estadistica['icon']) . '"></span>';
            echo '<span class="flavor-stat-valor">' . esc_html($estadistica['valor']) . '</span>';
            echo '<span class="flavor-stat-label">' . esc_html($estadistica['label']) . '</span>';
            echo '</div>';
        }
        echo '</div>';

        echo '<p>' . __('Panel de control con resumen de fichajes y empleados.', 'flavor-chat-ia') . '</p>';
        echo '</div>';
    }

    /**
     * Renderiza los registros de fichaje de hoy
     */
    public function render_admin_registros_hoy() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Registros de Hoy', 'flavor-chat-ia'));

        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';
        $hoy = date('Y-m-d');

        $fichajes_hoy = $wpdb->get_results($wpdb->prepare(
            "SELECT f.*, u.display_name
            FROM $tabla_fichajes f
            LEFT JOIN {$wpdb->users} u ON f.usuario_id = u.ID
            WHERE DATE(f.fecha_hora) = %s
            ORDER BY f.fecha_hora DESC",
            $hoy
        ));

        if (empty($fichajes_hoy)) {
            echo '<p>' . __('No hay fichajes registrados hoy.', 'flavor-chat-ia') . '</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Empleado', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Tipo', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Hora', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Notas', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Validado', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($fichajes_hoy as $fichaje) {
                $tipo_labels = [
                    'entrada' => __('Entrada', 'flavor-chat-ia'),
                    'salida' => __('Salida', 'flavor-chat-ia'),
                    'pausa_inicio' => __('Inicio pausa', 'flavor-chat-ia'),
                    'pausa_fin' => __('Fin pausa', 'flavor-chat-ia'),
                ];
                echo '<tr>';
                echo '<td>' . esc_html($fichaje->display_name ?: __('Usuario #', 'flavor-chat-ia') . $fichaje->usuario_id) . '</td>';
                echo '<td>' . esc_html($tipo_labels[$fichaje->tipo] ?? $fichaje->tipo) . '</td>';
                echo '<td>' . esc_html(date('H:i', strtotime($fichaje->fecha_hora))) . '</td>';
                echo '<td>' . esc_html($fichaje->notas) . '</td>';
                echo '<td>' . ($fichaje->validado ? '<span class="dashicons dashicons-yes" style="color:green;"></span>' : '<span class="dashicons dashicons-no" style="color:orange;"></span>') . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '</div>';
    }

    /**
     * Renderiza el historial de fichajes
     */
    public function render_admin_historial() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Historial de Fichajes', 'flavor-chat-ia'));
        echo '<p>' . __('Historial completo de fichajes con filtros por fecha y empleado.', 'flavor-chat-ia') . '</p>';
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_fichajes';

        $usuario_id = isset($_GET['usuario_id']) ? absint($_GET['usuario_id']) : 0;
        $tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
        $desde = isset($_GET['desde']) ? sanitize_text_field($_GET['desde']) : '';
        $hasta = isset($_GET['hasta']) ? sanitize_text_field($_GET['hasta']) : '';

        echo '<form method="get" style="margin: 12px 0;">';
        echo '<input type="hidden" name="page" value="fichaje-historial">';
        echo '<input type="number" name="usuario_id" placeholder="' . esc_attr__('ID usuario', 'flavor-chat-ia') . '" value="' . esc_attr($usuario_id ?: '') . '"> ';
        echo '<select name="tipo">';
        echo '<option value="">' . esc_html__('Todos los tipos', 'flavor-chat-ia') . '</option>';
        foreach (['entrada','salida','pausa_inicio','pausa_fin'] as $tipo_key) {
            echo '<option value="' . esc_attr($tipo_key) . '" ' . selected($tipo, $tipo_key, false) . '>' . esc_html($tipo_key) . '</option>';
        }
        echo '</select> ';
        echo '<input type="date" name="desde" value="' . esc_attr($desde) . '"> ';
        echo '<input type="date" name="hasta" value="' . esc_attr($hasta) . '"> ';
        echo '<button class="button">' . esc_html__('Filtrar', 'flavor-chat-ia') . '</button>';
        echo '</form>';

        $where = [];
        $params = [];
        if ($usuario_id) {
            $where[] = 'f.usuario_id = %d';
            $params[] = $usuario_id;
        }
        if ($tipo) {
            $where[] = 'f.tipo = %s';
            $params[] = $tipo;
        }
        if ($desde) {
            $where[] = 'DATE(f.fecha_hora) >= %s';
            $params[] = $desde;
        }
        if ($hasta) {
            $where[] = 'DATE(f.fecha_hora) <= %s';
            $params[] = $hasta;
        }

        $sql = "SELECT f.*, u.display_name
                FROM $tabla f
                LEFT JOIN {$wpdb->users} u ON f.usuario_id = u.ID";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY f.fecha_hora DESC LIMIT 200';

        $fichajes = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);

        if (empty($fichajes)) {
            echo '<p>' . esc_html__('No hay fichajes con esos filtros.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $tipo_labels = [
            'entrada' => __('Entrada', 'flavor-chat-ia'),
            'salida' => __('Salida', 'flavor-chat-ia'),
            'pausa_inicio' => __('Inicio pausa', 'flavor-chat-ia'),
            'pausa_fin' => __('Fin pausa', 'flavor-chat-ia'),
        ];

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Empleado', 'flavor-chat-ia') . '</th>';
        echo '<th>' . __('Tipo', 'flavor-chat-ia') . '</th>';
        echo '<th>' . __('Fecha', 'flavor-chat-ia') . '</th>';
        echo '<th>' . __('Notas', 'flavor-chat-ia') . '</th>';
        echo '<th>' . __('Validado', 'flavor-chat-ia') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($fichajes as $fichaje) {
            echo '<tr>';
            echo '<td>' . esc_html($fichaje->display_name ?: __('Usuario #', 'flavor-chat-ia') . $fichaje->usuario_id) . '</td>';
            echo '<td>' . esc_html($tipo_labels[$fichaje->tipo] ?? $fichaje->tipo) . '</td>';
            echo '<td>' . esc_html(date_i18n('d/m/Y H:i', strtotime($fichaje->fecha_hora))) . '</td>';
            echo '<td>' . esc_html($fichaje->notas) . '</td>';
            echo '<td>' . ($fichaje->validado ? '<span class="dashicons dashicons-yes" style="color:green;"></span>' : '<span class="dashicons dashicons-no" style="color:orange;"></span>') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * Renderiza la gestión de empleados
     */
    public function render_admin_empleados() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestión de Empleados', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Empleado', 'flavor-chat-ia'), 'url' => '#nuevo-empleado', 'class' => 'button-primary'],
        ]);
        echo '<p>' . __('Gestión de empleados y sus horarios de trabajo.', 'flavor-chat-ia') . '</p>';

        $this->handle_admin_save_horario();

        global $wpdb;
        $tabla_horarios = $wpdb->prefix . 'flavor_empleados_horarios';

        $horarios = $wpdb->get_results(
            "SELECT h.*, u.display_name
             FROM $tabla_horarios h
             LEFT JOIN {$wpdb->users} u ON h.usuario_id = u.ID
             ORDER BY u.display_name ASC, h.dia_semana ASC"
        );

        if (empty($horarios)) {
            echo '<p>' . esc_html__('No hay horarios registrados aún.', 'flavor-chat-ia') . '</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Empleado', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Día', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Entrada', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Salida', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Activo', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead><tbody>';
            foreach ($horarios as $horario) {
                echo '<tr>';
                echo '<td>' . esc_html($horario->display_name ?: __('Usuario #', 'flavor-chat-ia') . $horario->usuario_id) . '</td>';
                echo '<td>' . esc_html($horario->dia_semana) . '</td>';
                echo '<td>' . esc_html($horario->hora_entrada) . '</td>';
                echo '<td>' . esc_html($horario->hora_salida) . '</td>';
                echo '<td>' . ($horario->activo ? esc_html__('Sí', 'flavor-chat-ia') : esc_html__('No', 'flavor-chat-ia')) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '<hr id="nuevo-empleado">';
        echo '<h3>' . esc_html__('Asignar horario', 'flavor-chat-ia') . '</h3>';
        echo '<form method="post">';
        wp_nonce_field('fichaje_horario', 'fichaje_horario_nonce');
        echo '<table class="form-table"><tbody>';
        echo '<tr><th>' . esc_html__('Usuario ID', 'flavor-chat-ia') . '</th><td><input type="number" name="usuario_id" min="1" required></td></tr>';
        echo '<tr><th>' . esc_html__('Día de la semana', 'flavor-chat-ia') . '</th><td><select name="dia_semana">';
        foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $dia) {
            echo '<option value="' . esc_attr($dia) . '">' . esc_html($dia) . '</option>';
        }
        echo '</select></td></tr>';
        echo '<tr><th>' . esc_html__('Hora entrada', 'flavor-chat-ia') . '</th><td><input type="time" name="hora_entrada" required></td></tr>';
        echo '<tr><th>' . esc_html__('Hora salida', 'flavor-chat-ia') . '</th><td><input type="time" name="hora_salida" required></td></tr>';
        echo '<tr><th>' . esc_html__('Es laboral', 'flavor-chat-ia') . '</th><td><label><input type="checkbox" name="es_laboral" value="1" checked> ' . esc_html__('Sí', 'flavor-chat-ia') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Activo', 'flavor-chat-ia') . '</th><td><label><input type="checkbox" name="activo" value="1" checked> ' . esc_html__('Sí', 'flavor-chat-ia') . '</label></td></tr>';
        echo '</tbody></table>';
        submit_button(__('Guardar horario', 'flavor-chat-ia'));
        echo '</form>';
        echo '</div>';
    }

    /**
     * Renderiza la configuración del módulo
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Fichaje', 'flavor-chat-ia'));
        echo '<p>' . __('Configuración del sistema de fichaje de empleados.', 'flavor-chat-ia') . '</p>';
        $this->handle_admin_save_config();
        $configuracion = $this->get_settings();

        echo '<form method="post">';
        wp_nonce_field('fichaje_config', 'fichaje_config_nonce');
        echo '<table class="form-table"><tbody>';
        echo '<tr><th>' . esc_html__('Horario entrada', 'flavor-chat-ia') . '</th><td><input type="time" name="horario_entrada" value="' . esc_attr($configuracion['horario_entrada']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Horario salida', 'flavor-chat-ia') . '</th><td><input type="time" name="horario_salida" value="' . esc_attr($configuracion['horario_salida']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Tiempo de gracia (min)', 'flavor-chat-ia') . '</th><td><input type="number" name="tiempo_gracia" value="' . esc_attr($configuracion['tiempo_gracia']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Requiere geolocalización', 'flavor-chat-ia') . '</th><td><label><input type="checkbox" name="requiere_geolocalizacion" value="1" ' . checked($configuracion['requiere_geolocalizacion'], true, false) . '> ' . esc_html__('Sí', 'flavor-chat-ia') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Radio máximo (m)', 'flavor-chat-ia') . '</th><td><input type="number" name="radio_maximo" value="' . esc_attr($configuracion['radio_maximo']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Permite fichaje remoto', 'flavor-chat-ia') . '</th><td><label><input type="checkbox" name="permite_fichaje_remoto" value="1" ' . checked($configuracion['permite_fichaje_remoto'], true, false) . '> ' . esc_html__('Sí', 'flavor-chat-ia') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Notificar retrasos', 'flavor-chat-ia') . '</th><td><label><input type="checkbox" name="notificar_retrasos" value="1" ' . checked($configuracion['notificar_retrasos'], true, false) . '> ' . esc_html__('Sí', 'flavor-chat-ia') . '</label></td></tr>';
        echo '</tbody></table>';
        submit_button(__('Guardar configuración', 'flavor-chat-ia'));
        echo '</form>';
        echo '</div>';
    }

    private function handle_admin_save_horario() {
        if (empty($_POST['fichaje_horario_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['fichaje_horario_nonce'], 'fichaje_horario')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $usuario_id = absint($_POST['usuario_id'] ?? 0);
        $dia_semana = sanitize_text_field($_POST['dia_semana'] ?? '');
        if (!$usuario_id || !$dia_semana) {
            return;
        }

        global $wpdb;
        $tabla_horarios = $wpdb->prefix . 'flavor_empleados_horarios';
        $wpdb->delete($tabla_horarios, [
            'usuario_id' => $usuario_id,
            'dia_semana' => $dia_semana,
        ]);
        $wpdb->insert($tabla_horarios, [
            'usuario_id' => $usuario_id,
            'dia_semana' => $dia_semana,
            'hora_entrada' => sanitize_text_field($_POST['hora_entrada'] ?? '09:00'),
            'hora_salida' => sanitize_text_field($_POST['hora_salida'] ?? '18:00'),
            'es_laboral' => !empty($_POST['es_laboral']) ? 1 : 0,
            'activo' => !empty($_POST['activo']) ? 1 : 0,
        ]);

        echo '<div class="notice notice-success"><p>' . esc_html__('Horario guardado.', 'flavor-chat-ia') . '</p></div>';
    }

    private function handle_admin_save_config() {
        if (empty($_POST['fichaje_config_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['fichaje_config_nonce'], 'fichaje_config')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $this->update_setting('horario_entrada', sanitize_text_field($_POST['horario_entrada'] ?? '09:00'));
        $this->update_setting('horario_salida', sanitize_text_field($_POST['horario_salida'] ?? '18:00'));
        $this->update_setting('tiempo_gracia', absint($_POST['tiempo_gracia'] ?? 15));
        $this->update_setting('requiere_geolocalizacion', !empty($_POST['requiere_geolocalizacion']));
        $this->update_setting('radio_maximo', absint($_POST['radio_maximo'] ?? 100));
        $this->update_setting('permite_fichaje_remoto', !empty($_POST['permite_fichaje_remoto']));
        $this->update_setting('notificar_retrasos', !empty($_POST['notificar_retrasos']));

        echo '<div class="notice notice-success"><p>' . esc_html__('Configuración guardada.', 'flavor-chat-ia') . '</p></div>';
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_fichajes)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';
        $tabla_horarios = $wpdb->prefix . 'flavor_empleados_horarios';

        $sql_fichajes = "CREATE TABLE IF NOT EXISTS $tabla_fichajes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            tipo enum('entrada','salida','pausa_inicio','pausa_fin') NOT NULL,
            fecha_hora datetime NOT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            dispositivo varchar(100) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            notas text DEFAULT NULL,
            validado tinyint(1) DEFAULT 1,
            validado_por bigint(20) unsigned DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY fecha_hora (fecha_hora),
            KEY tipo (tipo)
        ) $charset_collate;";

        $sql_horarios = "CREATE TABLE IF NOT EXISTS $tabla_horarios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            dia_semana enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
            hora_entrada time NOT NULL,
            hora_salida time NOT NULL,
            es_laboral tinyint(1) DEFAULT 1,
            activo tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_dia (usuario_id, dia_semana)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_fichajes);
        dbDelta($sql_horarios);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'fichar' => [
                'description' => 'Registrar entrada, salida o pausa',
                'params' => ['tipo', 'latitud', 'longitud', 'notas'],
            ],
            'ver_fichajes_hoy' => [
                'description' => 'Ver fichajes del día actual',
                'params' => ['usuario_id'],
            ],
            'historial_fichajes' => [
                'description' => 'Ver historial de fichajes por periodo',
                'params' => ['usuario_id', 'desde', 'hasta'],
            ],
            'resumen_mensual' => [
                'description' => 'Obtener resumen de horas trabajadas del mes',
                'params' => ['usuario_id', 'mes', 'anio'],
            ],
            'estado_actual' => [
                'description' => 'Ver si el empleado está fichado o no',
                'params' => ['usuario_id'],
            ],
            'configurar_horario' => [
                'description' => 'Configurar horario semanal del empleado',
                'params' => ['usuario_id', 'horarios'],
            ],
            'empleados_presentes' => [
                'description' => 'Listar empleados actualmente en el lugar de trabajo',
                'params' => [],
            ],
            'fichar_entrada' => [
                'description' => 'Registrar fichaje de entrada',
                'params' => ['notas', 'latitud', 'longitud'],
            ],
            'fichar_salida' => [
                'description' => 'Registrar fichaje de salida',
                'params' => ['notas', 'latitud', 'longitud'],
            ],
            'pausar_jornada' => [
                'description' => 'Iniciar una pausa en la jornada',
                'params' => ['tipo_pausa'],
            ],
            'reanudar_jornada' => [
                'description' => 'Reanudar la jornada tras una pausa',
                'params' => [],
            ],
            'solicitar_cambio' => [
                'description' => 'Solicitar corrección de un fichaje',
                'params' => ['fecha', 'tipo', 'hora', 'motivo'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error' => "Acción no implementada: {$action_name}",
        ];
    }

    /**
     * Acción: Fichar (entrada/salida/pausa)
     */
    private function action_fichar($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => __('Resumen de Horas', 'flavor-chat-ia'),
            ];
        }

        $tipo = $params['tipo'] ?? 'entrada';
        $tipos_validos = ['entrada', 'salida', 'pausa_inicio', 'pausa_fin'];

        if (!in_array($tipo, $tipos_validos)) {
            return [
                'success' => false,
                'error' => __('mostrar_grafico', 'flavor-chat-ia'),
            ];
        }

        // Validar ubicación si está configurado
        $settings = $this->settings;
        if ($settings['requiere_geolocalizacion']) {
            $latitud = floatval($params['latitud'] ?? 0);
            $longitud = floatval($params['longitud'] ?? 0);

            if (!$latitud || !$longitud) {
                return [
                    'success' => false,
                    'error' => __('Acción no implementada: {$action_name}', 'flavor-chat-ia'),
                ];
            }
        }

        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        $resultado = $wpdb->insert(
            $tabla_fichajes,
            [
                'usuario_id' => $usuario_id,
                'tipo' => $tipo,
                'fecha_hora' => current_time('mysql'),
                'latitud' => $params['latitud'] ?? null,
                'longitud' => $params['longitud'] ?? null,
                'dispositivo' => $params['dispositivo'] ?? 'app_movil',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'notas' => sanitize_textarea_field($params['notas'] ?? ''),
                'validado' => 1,
            ],
            ['%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%d']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Acción no implementada: {$action_name}', 'flavor-chat-ia'),
            ];
        }

        $usuario = get_userdata($usuario_id);
        $tipo_texto = [
            'entrada' => 'entrada',
            'salida' => 'salida',
            'pausa_inicio' => 'inicio de pausa',
            'pausa_fin' => 'fin de pausa',
        ];

        return [
            'success' => true,
            'fichaje_id' => $wpdb->insert_id,
            'mensaje' => sprintf(
                '%s ha fichado %s correctamente a las %s',
                $usuario->display_name,
                $tipo_texto[$tipo],
                current_time('H:i')
            ),
        ];
    }

    /**
     * Acción: Ver fichajes de hoy
     */
    private function action_ver_fichajes_hoy($params) {
        $usuario_id = $params['usuario_id'] ?? get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => __('Acción no implementada: {$action_name}', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        $hoy = current_time('Y-m-d');

        $fichajes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_fichajes
            WHERE usuario_id = %d
            AND DATE(fecha_hora) = %s
            ORDER BY fecha_hora ASC",
            $usuario_id,
            $hoy
        ));

        $fichajes_formateados = array_map(function($f) {
            return [
                'tipo' => $f->tipo,
                'hora' => date('H:i', strtotime($f->fecha_hora)),
                'notas' => $f->notas,
            ];
        }, $fichajes);

        // Calcular horas trabajadas
        $horas_trabajadas = $this->calcular_horas_trabajadas($fichajes);

        return [
            'success' => true,
            'fecha' => $hoy,
            'fichajes' => $fichajes_formateados,
            'total_fichajes' => count($fichajes),
            'horas_trabajadas' => $horas_trabajadas,
            'mensaje' => sprintf(
                'Hoy has trabajado %.2f horas con %d fichajes registrados.',
                $horas_trabajadas,
                count($fichajes)
            ),
        ];
    }

    /**
     * Acción: Estado actual del empleado
     */
    private function action_estado_actual($params) {
        $usuario_id = $params['usuario_id'] ?? get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => __('Acción no implementada: {$action_name}', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_fichajes = $wpdb->prefix . 'flavor_fichajes';

        $ultimo_fichaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_fichajes
            WHERE usuario_id = %d
            ORDER BY fecha_hora DESC
            LIMIT 1",
            $usuario_id
        ));

        if (!$ultimo_fichaje) {
            return [
                'success' => true,
                'estado' => 'sin_fichar',
                'mensaje' => __('No has fichado hoy todavía.', 'flavor-chat-ia'),
            ];
        }

        $estados = [
            'entrada' => 'trabajando',
            'salida' => 'fuera',
            'pausa_inicio' => 'en_pausa',
            'pausa_fin' => 'trabajando',
        ];

        $estado = $estados[$ultimo_fichaje->tipo] ?? 'desconocido';

        return [
            'success' => true,
            'estado' => $estado,
            'ultimo_fichaje' => [
                'tipo' => $ultimo_fichaje->tipo,
                'hora' => date('H:i', strtotime($ultimo_fichaje->fecha_hora)),
            ],
            'mensaje' => sprintf(
                'Tu último fichaje fue de %s a las %s. Estado actual: %s.',
                $ultimo_fichaje->tipo,
                date('H:i', strtotime($ultimo_fichaje->fecha_hora)),
                $estado
            ),
        ];
    }

    /**
     * Calcula horas trabajadas del día
     */
    private function calcular_horas_trabajadas($fichajes) {
        $horas = 0;
        $ultima_entrada = null;

        foreach ($fichajes as $fichaje) {
            if ($fichaje->tipo === 'entrada' || $fichaje->tipo === 'pausa_fin') {
                $ultima_entrada = strtotime($fichaje->fecha_hora);
            } elseif (($fichaje->tipo === 'salida' || $fichaje->tipo === 'pausa_inicio') && $ultima_entrada) {
                $salida = strtotime($fichaje->fecha_hora);
                $horas += ($salida - $ultima_entrada) / 3600;
                $ultima_entrada = null;
            }
        }

        return round($horas, 2);
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'fichaje_registrar',
                'description' => 'Registra un fichaje (entrada, salida o pausa)',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo' => [
                            'type' => 'string',
                            'description' => 'Tipo de fichaje',
                            'enum' => ['entrada', 'salida', 'pausa_inicio', 'pausa_fin'],
                        ],
                        'notas' => [
                            'type' => 'string',
                            'description' => 'Notas opcionales sobre el fichaje',
                        ],
                    ],
                    'required' => ['tipo'],
                ],
            ],
            [
                'name' => 'fichaje_ver_hoy',
                'description' => 'Ver fichajes del día actual y horas trabajadas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Sistema de Fichaje de Empleados**

Control completo de horarios y asistencia de empleados desde la app móvil.

**Funcionalidades:**
- Fichaje de entrada y salida
- Control de pausas
- Registro de ubicación (opcional)
- Historial de fichajes
- Cálculo automático de horas trabajadas
- Resumen mensual de asistencia
- Alertas de retrasos
- Configuración de horarios personalizados

**Tipos de fichaje:**
- Entrada: Inicio de jornada laboral
- Salida: Fin de jornada laboral
- Pausa inicio: Comienzo de descanso
- Pausa fin: Fin del descanso

**Validación:**
- Se puede configurar verificación por geolocalización
- Radio máximo permitido desde el centro de trabajo
- Fichaje remoto (opcional)
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo fichar desde la app?',
                'respuesta' => 'Abre la app, ve a la sección Fichaje y pulsa el botón correspondiente (Entrada, Salida, Pausa).',
            ],
            [
                'pregunta' => '¿Puedo ver mis horas trabajadas?',
                'respuesta' => 'Sí, puedes ver tus fichajes del día, semana o mes con el total de horas trabajadas.',
            ],
            [
                'pregunta' => '¿Qué pasa si olvido fichar?',
                'respuesta' => 'Los administradores pueden validar y corregir fichajes manualmente desde el panel de control.',
            ],
        ];
    }

    /**
     * Configuración de formularios del módulo
     *
     * @param string $action_name Nombre de la acción
     * @return array Configuración del formulario
     */
    public function get_form_config($action_name) {
        $configs = [
            'fichar_entrada' => [
                'title' => __('Fichar Entrada', 'flavor-chat-ia'),
                'description' => __('Registra tu entrada al trabajo', 'flavor-chat-ia'),
                'fields' => [
                    'notas' => [
                        'type' => 'textarea',
                        'label' => __('Notas (opcional)', 'flavor-chat-ia'),
                        'rows' => 2,
                        'placeholder' => __('Proyecto, tarea, ubicación...', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Fichar Entrada', 'flavor-chat-ia'),
                'success_message' => __('Entrada registrada correctamente', 'flavor-chat-ia'),
            ],
            'fichar_salida' => [
                'title' => __('Fichar Salida', 'flavor-chat-ia'),
                'description' => __('Registra tu salida del trabajo', 'flavor-chat-ia'),
                'fields' => [
                    'notas' => [
                        'type' => 'textarea',
                        'label' => __('Resumen del día (opcional)', 'flavor-chat-ia'),
                        'rows' => 3,
                        'placeholder' => __('¿Qué has hecho hoy?', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Fichar Salida', 'flavor-chat-ia'),
                'success_message' => __('Salida registrada correctamente. ¡Buen trabajo!', 'flavor-chat-ia'),
            ],
            'pausar_jornada' => [
                'title' => __('Iniciar Pausa', 'flavor-chat-ia'),
                'description' => __('Pausa tu jornada temporalmente', 'flavor-chat-ia'),
                'fields' => [
                    'tipo_pausa' => [
                        'type' => 'select',
                        'label' => __('Tipo de pausa', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'comida' => __('Comida', 'flavor-chat-ia'),
                            'descanso' => __('Descanso', 'flavor-chat-ia'),
                            'reunion' => __('Reunión externa', 'flavor-chat-ia'),
                            'otros' => __('Otros', 'flavor-chat-ia'),
                        ],
                    ],
                ],
                'submit_text' => __('Iniciar Pausa', 'flavor-chat-ia'),
                'success_message' => __('Pausa iniciada', 'flavor-chat-ia'),
            ],
            'reanudar_jornada' => [
                'title' => __('Reanudar Jornada', 'flavor-chat-ia'),
                'description' => __('Vuelve al trabajo tras la pausa', 'flavor-chat-ia'),
                'fields' => [],
                'submit_text' => __('Reanudar Jornada', 'flavor-chat-ia'),
                'success_message' => __('Jornada reanudada', 'flavor-chat-ia'),
            ],
            'solicitar_cambio' => [
                'title' => __('Solicitar Corrección de Fichaje', 'flavor-chat-ia'),
                'description' => __('¿Olvidaste fichar? Solicita una corrección', 'flavor-chat-ia'),
                'fields' => [
                    'fecha' => [
                        'type' => 'date',
                        'label' => __('Fecha del fichaje', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'tipo' => [
                        'type' => 'select',
                        'label' => __('Tipo de fichaje', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'entrada' => __('Entrada', 'flavor-chat-ia'),
                            'salida' => __('Salida', 'flavor-chat-ia'),
                        ],
                    ],
                    'hora' => [
                        'type' => 'time',
                        'label' => __('Hora correcta', 'flavor-chat-ia'),
                        'required' => true,
                    ],
                    'motivo' => [
                        'type' => 'textarea',
                        'label' => __('Motivo de la corrección', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 3,
                        'placeholder' => __('Explica por qué necesitas esta corrección...', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Solicitar Corrección', 'flavor-chat-ia'),
                'success_message' => __('Solicitud enviada. Pendiente de validación.', 'flavor-chat-ia'),
            ],
        ];

        return $configs[$action_name] ?? [];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Fichaje', 'flavor-chat-ia'),
                'description' => __('Sección hero con botón de fichaje rápido', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-clock',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Control de Presencia', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Registra tu entrada y salida de forma sencilla', 'flavor-chat-ia'),
                    ],
                    'mostrar_reloj' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar reloj', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'fichaje-empleados/hero',
            ],
            'boton_fichaje' => [
                'label' => __('Botón de Fichaje', 'flavor-chat-ia'),
                'description' => __('Botón grande para fichar entrada/salida', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-yes-alt',
                'fields' => [
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['grande', 'compacto'],
                        'default' => 'grande',
                    ],
                    'mostrar_estado' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar estado actual', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'fichaje-empleados/boton-fichaje',
            ],
            'historial' => [
                'label' => __('Historial de Fichajes', 'flavor-chat-ia'),
                'description' => __('Tabla con registro de fichajes', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-list-view',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Mis Fichajes', 'flavor-chat-ia'),
                    ],
                    'periodo' => [
                        'type' => 'select',
                        'label' => __('Periodo', 'flavor-chat-ia'),
                        'options' => ['hoy', 'semana', 'mes'],
                        'default' => 'semana',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 30,
                    ],
                ],
                'template' => 'fichaje-empleados/historial',
            ],
            'resumen_horas' => [
                'label' => __('Resumen de Horas', 'flavor-chat-ia'),
                'description' => __('Estadísticas de horas trabajadas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Resumen de Horas', 'flavor-chat-ia'),
                    ],
                    'mostrar_grafico' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar gráfico', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'fichaje-empleados/resumen-horas',
            ],
        ];
    }

    // =========================================================
    // Acciones delegadas para formularios frontend
    // =========================================================

    private function action_fichar_entrada($params) {
        $params['tipo'] = 'entrada';
        return $this->action_fichar($params);
    }

    private function action_fichar_salida($params) {
        $params['tipo'] = 'salida';
        return $this->action_fichar($params);
    }

    private function action_pausar_jornada($params) {
        $params['tipo'] = 'pausa_inicio';
        return $this->action_fichar($params);
    }

    private function action_reanudar_jornada($params) {
        $params['tipo'] = 'pausa_fin';
        return $this->action_fichar($params);
    }

    private function action_solicitar_cambio($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => __('Acción no implementada: {$action_name}', 'flavor-chat-ia'),
            ];
        }

        $fecha = sanitize_text_field($params['fecha'] ?? '');
        $tipo = sanitize_text_field($params['tipo'] ?? '');
        $hora = sanitize_text_field($params['hora'] ?? '');
        $motivo = sanitize_textarea_field($params['motivo'] ?? '');

        if (empty($fecha) || empty($tipo) || empty($hora) || empty($motivo)) {
            return [
                'success' => false,
                'error' => __('Acción no implementada: {$action_name}', 'flavor-chat-ia'),
            ];
        }

        // Registrar la solicitud de correccion
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_fichajes';

        $wpdb->insert($tabla, [
            'usuario_id' => $usuario_id,
            'tipo' => sanitize_text_field($tipo),
            'fecha_hora' => $fecha . ' ' . $hora . ':00',
            'notas' => '[CORRECCION SOLICITADA] ' . $motivo,
            'estado' => 'pendiente_revision',
        ]);

        return [
            'success' => true,
            'mensaje' => __('Solicitud de correccion enviada. Un administrador la revisara.', 'flavor-chat-ia'),
        ];
    }
    /**
     * Crea/actualiza páginas del módulo si es necesario
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('fichaje_empleados');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('fichaje-empleados');
        if (!$pagina && !get_option('flavor_fichaje_empleados_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['fichaje_empleados']);
            update_option('flavor_fichaje_empleados_pages_created', 1, false);
        }
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Fichaje de Empleados', 'flavor-chat-ia'),
                'slug' => 'fichaje-empleados',
                'content' => '<h1>' . __('Control de Presencia', 'flavor-chat-ia') . '</h1>
<p>' . __('Sistema de fichaje para el control de horarios y asistencia de empleados.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="fichaje_empleados" action="estado_actual" columnas="1" limite="1"]',
                'parent' => 0,
            ],
            [
                'title' => __('Fichar Entrada', 'flavor-chat-ia'),
                'slug' => 'entrada',
                'content' => '<h1>' . __('Fichar Entrada', 'flavor-chat-ia') . '</h1>
<p>' . __('Registra tu entrada al trabajo.', 'flavor-chat-ia') . '</p>

[flavor_module_form module="fichaje_empleados" action="fichar_entrada"]',
                'parent' => 'fichaje-empleados',
            ],
            [
                'title' => __('Fichar Salida', 'flavor-chat-ia'),
                'slug' => 'salida',
                'content' => '<h1>' . __('Fichar Salida', 'flavor-chat-ia') . '</h1>
<p>' . __('Registra tu salida del trabajo.', 'flavor-chat-ia') . '</p>

[flavor_module_form module="fichaje_empleados" action="fichar_salida"]',
                'parent' => 'fichaje-empleados',
            ],
            [
                'title' => __('Mis Fichajes', 'flavor-chat-ia'),
                'slug' => 'mis-fichajes',
                'content' => '<h1>' . __('Mis Fichajes', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta tu historial de fichajes y horas trabajadas.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="fichaje_empleados" action="ver_fichajes_hoy" columnas="1" limite="30"]',
                'parent' => 'fichaje-empleados',
            ],
        ];
    }

}
