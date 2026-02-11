<?php
/**
 * Módulo de Presupuestos Participativos para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Presupuestos Participativos - Democracia económica directa
 */
class Flavor_Chat_Presupuestos_Participativos_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'presupuestos_participativos';
        $this->name = 'Presupuestos Participativos'; // Translation loaded on init
        $this->description = 'Democracia participativa: los vecinos deciden en qué invertir el presupuesto del barrio.'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        return Flavor_Chat_Helpers::tabla_existe($tabla_proyectos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Presupuestos Participativos no están creadas. Activa el módulo para crearlas automáticamente.', 'flavor-chat-ia');
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
            'presupuesto_anual' => 100000.00,
            'presupuesto_minimo_proyecto' => 1000.00,
            'presupuesto_maximo_proyecto' => 50000.00,
            'votos_maximos_por_persona' => 3,
            'requiere_verificacion' => true,
            'fase_actual' => 'cerrada', // propuestas, votacion, implementacion, cerrada
            'fecha_inicio_propuestas' => null,
            'fecha_fin_propuestas' => null,
            'fecha_inicio_votacion' => null,
            'fecha_fin_votacion' => null,
            'categorias' => [
                'infraestructura' => __('Infraestructura', 'flavor-chat-ia'),
                'medio_ambiente' => __('Medio Ambiente', 'flavor-chat-ia'),
                'cultura' => __('Cultura y Ocio', 'flavor-chat-ia'),
                'deporte' => __('Deporte', 'flavor-chat-ia'),
                'social' => __('Social', 'flavor-chat-ia'),
                'educacion' => __('Educación', 'flavor-chat-ia'),
                'accesibilidad' => __('Accesibilidad', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        $this->registrar_en_panel_unificado();
    }

    /**
     * Registrar rutas REST API para APKs
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // Listar proyectos/propuestas
        register_rest_route($namespace, '/presupuestos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_proyectos'],
            'permission_callback' => '__return_true',
            'args' => [
                'categoria' => [
                    'type' => 'string',
                    'description' => 'Filtrar por categoría del proyecto',
                ],
                'estado' => [
                    'type' => 'string',
                    'enum' => ['borrador', 'pendiente_validacion', 'validado', 'en_votacion', 'seleccionado', 'en_ejecucion', 'ejecutado', 'rechazado'],
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                ],
            ],
        ]);

        // Obtener un proyecto específico
        register_rest_route($namespace, '/presupuestos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_proyecto'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        // Crear nueva propuesta
        register_rest_route($namespace, '/presupuestos', [
            'methods' => 'POST',
            'callback' => [$this, 'api_crear_propuesta'],
            'permission_callback' => [$this, 'verificar_usuario_autenticado'],
            'args' => [
                'titulo' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'descripcion' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'categoria' => [
                    'type' => 'string',
                    'default' => 'social',
                ],
                'presupuesto' => [
                    'required' => true,
                    'type' => 'number',
                ],
                'ubicacion' => [
                    'type' => 'string',
                ],
            ],
        ]);

        // Votar proyecto
        register_rest_route($namespace, '/presupuestos/(?P<id>\d+)/votar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_votar_proyecto'],
            'permission_callback' => [$this, 'verificar_usuario_autenticado'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
                'prioridad' => [
                    'type' => 'integer',
                    'default' => 1,
                ],
            ],
        ]);

        // Ver resultados de votación
        register_rest_route($namespace, '/presupuestos/resultados', [
            'methods' => 'GET',
            'callback' => [$this, 'api_resultados'],
            'permission_callback' => '__return_true',
            'args' => [
                'edicion_id' => [
                    'type' => 'integer',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                ],
            ],
        ]);

        // Ver propuestas del usuario
        register_rest_route($namespace, '/presupuestos/mis-propuestas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_propuestas'],
            'permission_callback' => [$this, 'verificar_usuario_autenticado'],
            'args' => [
                'estado' => [
                    'type' => 'string',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                ],
            ],
        ]);

        // Obtener información de la edición actual
        register_rest_route($namespace, '/presupuestos/edicion', [
            'methods' => 'GET',
            'callback' => [$this, 'api_info_edicion'],
            'permission_callback' => '__return_true',
        ]);

        // Ver mis votos
        register_rest_route($namespace, '/presupuestos/mis-votos', [
            'methods' => 'GET',
            'callback' => [$this, 'api_mis_votos'],
            'permission_callback' => [$this, 'verificar_usuario_autenticado'],
        ]);

        // Obtener configuración (categorías, límites, etc.)
        register_rest_route($namespace, '/presupuestos/config', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_config'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Verificar que el usuario está autenticado
     */
    public function verificar_usuario_autenticado() {
        return is_user_logged_in();
    }

    // =========================================================================
    // Métodos API REST
    // =========================================================================

    /**
     * API: Listar proyectos
     */
    public function api_listar_proyectos($request) {
        $resultado = $this->action_listar_proyectos([
            'categoria' => $request->get_param('categoria'),
            'estado' => $request->get_param('estado'),
            'limite' => $request->get_param('limite') ?: 20,
        ]);

        if (!$resultado['success']) {
            return new WP_REST_Response(['success' => false, 'error' => $resultado['error'] ?? 'Error al obtener proyectos'], 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Obtener un proyecto específico
     */
    public function api_obtener_proyecto($request) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $identificador_proyecto = absint($request->get_param('id'));

        $proyecto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_proyectos WHERE id = %d",
            $identificador_proyecto
        ));

        if (!$proyecto) {
            return new WP_REST_Response(['success' => false, 'error' => 'Proyecto no encontrado'], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'proyecto' => [
                'id' => $proyecto->id,
                'titulo' => $proyecto->titulo,
                'descripcion' => $proyecto->descripcion,
                'categoria' => $proyecto->categoria,
                'ambito' => $proyecto->ambito,
                'ubicacion' => $proyecto->ubicacion,
                'presupuesto_solicitado' => floatval($proyecto->presupuesto_solicitado),
                'presupuesto_aprobado' => $proyecto->presupuesto_aprobado ? floatval($proyecto->presupuesto_aprobado) : null,
                'estado' => $proyecto->estado,
                'votos_recibidos' => intval($proyecto->votos_recibidos),
                'ranking' => intval($proyecto->ranking),
                'es_viable' => $proyecto->es_viable,
                'porcentaje_ejecucion' => intval($proyecto->porcentaje_ejecucion),
                'fecha_creacion' => $proyecto->fecha_creacion,
            ],
        ], 200);
    }

    /**
     * API: Crear propuesta
     */
    public function api_crear_propuesta($request) {
        $resultado = $this->action_proponer_proyecto([
            'titulo' => $request->get_param('titulo'),
            'descripcion' => $request->get_param('descripcion'),
            'categoria' => $request->get_param('categoria'),
            'presupuesto' => $request->get_param('presupuesto'),
            'ubicacion' => $request->get_param('ubicacion'),
        ]);

        if (!$resultado['success']) {
            return new WP_REST_Response(['success' => false, 'error' => $resultado['error']], 400);
        }

        return new WP_REST_Response($resultado, 201);
    }

    /**
     * API: Votar proyecto
     */
    public function api_votar_proyecto($request) {
        $identificador_proyecto = absint($request->get_param('id'));
        $prioridad_voto = absint($request->get_param('prioridad') ?: 1);

        $resultado = $this->action_votar_proyecto_individual([
            'proyecto_id' => $identificador_proyecto,
            'prioridad' => $prioridad_voto,
        ]);

        if (!$resultado['success']) {
            return new WP_REST_Response(['success' => false, 'error' => $resultado['error']], 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Ver resultados de votación
     */
    public function api_resultados($request) {
        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';

        $identificador_edicion = $request->get_param('edicion_id');
        $limite_resultados = absint($request->get_param('limite') ?: 20);

        // Obtener edición
        if ($identificador_edicion) {
            $edicion = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_ediciones WHERE id = %d",
                $identificador_edicion
            ));
        } else {
            $edicion = $wpdb->get_row(
                "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
            );
        }

        if (!$edicion) {
            return new WP_REST_Response([
                'success' => true,
                'mensaje' => 'No hay edición activa',
                'proyectos' => [],
            ], 200);
        }

        // Obtener proyectos ordenados por votos
        $proyectos_ranking = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, COUNT(v.id) as total_votos
             FROM $tabla_proyectos p
             LEFT JOIN $tabla_votos v ON p.id = v.proyecto_id
             WHERE p.edicion_id = %d AND p.estado IN ('validado', 'en_votacion', 'seleccionado', 'en_ejecucion', 'ejecutado')
             GROUP BY p.id
             ORDER BY total_votos DESC
             LIMIT %d",
            $edicion->id,
            $limite_resultados
        ));

        $total_votantes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_votos WHERE edicion_id = %d",
            $edicion->id
        ));

        $proyectos_formateados = array_map(function($proyecto) {
            return [
                'id' => $proyecto->id,
                'titulo' => $proyecto->titulo,
                'descripcion' => wp_trim_words($proyecto->descripcion, 30),
                'categoria' => $proyecto->categoria,
                'presupuesto' => floatval($proyecto->presupuesto_solicitado),
                'votos' => intval($proyecto->total_votos),
                'estado' => $proyecto->estado,
                'porcentaje_ejecucion' => intval($proyecto->porcentaje_ejecucion),
            ];
        }, $proyectos_ranking);

        return new WP_REST_Response([
            'success' => true,
            'edicion' => [
                'id' => $edicion->id,
                'anio' => $edicion->anio,
                'fase' => $edicion->fase,
                'presupuesto_total' => floatval($edicion->presupuesto_total),
            ],
            'total_votantes' => $total_votantes,
            'total_proyectos' => count($proyectos_formateados),
            'proyectos' => $proyectos_formateados,
        ], 200);
    }

    /**
     * API: Ver propuestas del usuario
     */
    public function api_mis_propuestas($request) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        $identificador_usuario = get_current_user_id();
        $estado_filtro = $request->get_param('estado');
        $limite_resultados = absint($request->get_param('limite') ?: 20);

        $condiciones_where = ['proponente_id = %d'];
        $valores_preparados = [$identificador_usuario];

        if ($estado_filtro) {
            $condiciones_where[] = 'estado = %s';
            $valores_preparados[] = sanitize_text_field($estado_filtro);
        }

        $clausula_where = implode(' AND ', $condiciones_where);
        $valores_preparados[] = $limite_resultados;

        $propuestas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_proyectos WHERE $clausula_where ORDER BY fecha_creacion DESC LIMIT %d",
            ...$valores_preparados
        ));

        $propuestas_formateadas = array_map(function($proyecto) {
            return [
                'id' => $proyecto->id,
                'titulo' => $proyecto->titulo,
                'descripcion' => wp_trim_words($proyecto->descripcion, 30),
                'categoria' => $proyecto->categoria,
                'presupuesto' => floatval($proyecto->presupuesto_solicitado),
                'estado' => $proyecto->estado,
                'votos' => intval($proyecto->votos_recibidos),
                'fecha_creacion' => $proyecto->fecha_creacion,
            ];
        }, $propuestas);

        return new WP_REST_Response([
            'success' => true,
            'total' => count($propuestas_formateadas),
            'propuestas' => $propuestas_formateadas,
        ], 200);
    }

    /**
     * API: Información de la edición actual
     */
    public function api_info_edicion($request) {
        $resultado = $this->action_info_edicion_actual([]);

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Ver mis votos
     */
    public function api_mis_votos($request) {
        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        $identificador_usuario = get_current_user_id();

        // Obtener edición actual
        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return new WP_REST_Response([
                'success' => true,
                'mensaje' => 'No hay edición activa',
                'votos' => [],
            ], 200);
        }

        $votos_usuario = $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, p.titulo, p.categoria, p.presupuesto_solicitado
             FROM $tabla_votos v
             INNER JOIN $tabla_proyectos p ON v.proyecto_id = p.id
             WHERE v.usuario_id = %d AND v.edicion_id = %d
             ORDER BY v.prioridad ASC",
            $identificador_usuario,
            $edicion->id
        ));

        $votos_formateados = array_map(function($voto) {
            return [
                'proyecto_id' => $voto->proyecto_id,
                'titulo' => $voto->titulo,
                'categoria' => $voto->categoria,
                'presupuesto' => floatval($voto->presupuesto_solicitado),
                'prioridad' => intval($voto->prioridad),
                'fecha_voto' => $voto->fecha_voto,
            ];
        }, $votos_usuario);

        $configuracion = $this->settings;
        $votos_maximos = $configuracion['votos_maximos_por_persona'] ?? 3;

        return new WP_REST_Response([
            'success' => true,
            'edicion' => $edicion->anio,
            'votos_emitidos' => count($votos_formateados),
            'votos_disponibles' => max(0, $votos_maximos - count($votos_formateados)),
            'votos' => $votos_formateados,
        ], 200);
    }

    /**
     * API: Obtener configuración del módulo
     */
    public function api_obtener_config($request) {
        $configuracion = $this->settings;

        return new WP_REST_Response([
            'success' => true,
            'config' => [
                'presupuesto_minimo_proyecto' => floatval($configuracion['presupuesto_minimo_proyecto'] ?? 1000),
                'presupuesto_maximo_proyecto' => floatval($configuracion['presupuesto_maximo_proyecto'] ?? 50000),
                'votos_maximos_por_persona' => intval($configuracion['votos_maximos_por_persona'] ?? 3),
                'requiere_verificacion' => $configuracion['requiere_verificacion'] ?? true,
                'categorias' => $configuracion['categorias'] ?? [],
            ],
        ], 200);
    }

    /**
     * Acción: Votar un proyecto individual
     */
    private function action_votar_proyecto_individual($params) {
        $identificador_usuario = get_current_user_id();

        if (!$identificador_usuario) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesión para votar.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        // Verificar fase de votación
        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase = 'votacion' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return [
                'success' => false,
                'error' => __('No estamos en fase de votación.', 'flavor-chat-ia'),
            ];
        }

        $identificador_proyecto = absint($params['proyecto_id']);
        $prioridad_voto = absint($params['prioridad'] ?? 1);

        // Verificar que el proyecto existe y está en votación
        $proyecto = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_proyectos WHERE id = %d AND edicion_id = %d AND estado IN ('validado', 'en_votacion')",
            $identificador_proyecto,
            $edicion->id
        ));

        if (!$proyecto) {
            return [
                'success' => false,
                'error' => __('Proyecto no encontrado o no disponible para votar.', 'flavor-chat-ia'),
            ];
        }

        // Verificar límite de votos
        $configuracion = $this->settings;
        $votos_maximos = $configuracion['votos_maximos_por_persona'] ?? 3;

        $votos_actuales = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_votos WHERE usuario_id = %d AND edicion_id = %d",
            $identificador_usuario,
            $edicion->id
        ));

        // Verificar si ya votó este proyecto
        $voto_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_votos WHERE usuario_id = %d AND proyecto_id = %d",
            $identificador_usuario,
            $identificador_proyecto
        ));

        if ($voto_existente) {
            return [
                'success' => false,
                'error' => __('Ya has votado este proyecto.', 'flavor-chat-ia'),
            ];
        }

        if ($votos_actuales >= $votos_maximos) {
            return [
                'success' => false,
                'error' => sprintf(__('Has alcanzado el límite de %d votos.', 'flavor-chat-ia'), $votos_maximos),
            ];
        }

        // Registrar voto
        $resultado_insercion = $wpdb->insert(
            $tabla_votos,
            [
                'proyecto_id' => $identificador_proyecto,
                'edicion_id' => $edicion->id,
                'usuario_id' => $identificador_usuario,
                'prioridad' => $prioridad_voto,
            ],
            ['%d', '%d', '%d', '%d']
        );

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error' => __('Error al registrar el voto.', 'flavor-chat-ia'),
            ];
        }

        // Actualizar contador de votos del proyecto
        $this->actualizar_contadores_votos($edicion->id);

        return [
            'success' => true,
            'mensaje' => sprintf(__('¡Voto registrado para "%s"!', 'flavor-chat-ia'), $proyecto->titulo),
            'votos_restantes' => $votos_maximos - $votos_actuales - 1,
        ];
    }

    /**
     * Configuración para el panel de administración unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => $this->id,
            'label' => $this->name,
            'icon' => 'dashicons-money-alt',
            'capability' => 'manage_options',
            'categoria' => 'economia',
            'paginas' => [
                [
                    'slug' => 'pp-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'pp-propuestas',
                    'titulo' => __('Propuestas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_propuestas'],
                    'badge' => [$this, 'contar_propuestas_pendientes'],
                ],
                [
                    'slug' => 'pp-votaciones',
                    'titulo' => __('Votaciones', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_votaciones'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_widget_resumen'],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta propuestas pendientes de validación
     *
     * @return int
     */
    public function contar_propuestas_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_proyectos)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_proyectos WHERE estado = 'pendiente_validacion'"
        );
    }

    /**
     * Renderiza el dashboard de administración
     */
    public function render_admin_dashboard() {
        $this->render_page_header(
            __('Presupuestos Participativos - Dashboard', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Nueva Edición', 'flavor-chat-ia'),
                    'url' => admin_url('admin.php?page=pp-propuestas&action=nueva-edicion'),
                    'class' => 'button-primary',
                ],
            ]
        );

        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';

        $edicion_actual = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        $total_proyectos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos");
        $total_votos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_votos");
        $proyectos_pendientes = $this->contar_propuestas_pendientes();

        ?>
        <div class="wrap flavor-pp-dashboard">
            <div class="flavor-pp-stats-grid">
                <div class="flavor-pp-stat-card">
                    <span class="dashicons dashicons-portfolio"></span>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo esc_html($total_proyectos); ?></span>
                        <span class="stat-label"><?php _e('Total Proyectos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-pp-stat-card warning">
                    <span class="dashicons dashicons-clock"></span>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo esc_html($proyectos_pendientes); ?></span>
                        <span class="stat-label"><?php _e('Pendientes Validación', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-pp-stat-card">
                    <span class="dashicons dashicons-thumbs-up"></span>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo esc_html($total_votos); ?></span>
                        <span class="stat-label"><?php _e('Votos Emitidos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-pp-stat-card <?php echo $edicion_actual ? 'success' : ''; ?>">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo $edicion_actual ? esc_html($edicion_actual->fase) : __('N/A', 'flavor-chat-ia'); ?></span>
                        <span class="stat-label"><?php _e('Fase Actual', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($edicion_actual): ?>
                <div class="flavor-pp-edicion-info">
                    <h2><?php printf(__('Edición %d', 'flavor-chat-ia'), $edicion_actual->anio); ?></h2>
                    <p>
                        <strong><?php _e('Presupuesto Total:', 'flavor-chat-ia'); ?></strong>
                        <?php echo number_format($edicion_actual->presupuesto_total, 2, ',', '.'); ?> €
                    </p>
                    <p>
                        <strong><?php _e('Fase:', 'flavor-chat-ia'); ?></strong>
                        <?php echo esc_html(ucfirst($edicion_actual->fase)); ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="notice notice-info">
                    <p><?php _e('No hay una edición activa. Crea una nueva edición para comenzar.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza la página de administración de propuestas
     */
    public function render_admin_propuestas() {
        $this->render_page_header(
            __('Gestión de Propuestas', 'flavor-chat-ia'),
            [
                [
                    'label' => __('Exportar', 'flavor-chat-ia'),
                    'url' => admin_url('admin.php?page=pp-propuestas&action=exportar'),
                    'class' => '',
                ],
            ]
        );

        $tab_actual = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'pendientes';

        $this->render_page_tabs([
            ['slug' => 'pendientes', 'label' => __('Pendientes', 'flavor-chat-ia'), 'badge' => $this->contar_propuestas_pendientes()],
            ['slug' => 'validados', 'label' => __('Validados', 'flavor-chat-ia')],
            ['slug' => 'rechazados', 'label' => __('Rechazados', 'flavor-chat-ia')],
            ['slug' => 'todos', 'label' => __('Todos', 'flavor-chat-ia')],
        ], $tab_actual);

        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        $where_estado = '';
        switch ($tab_actual) {
            case 'pendientes':
                $where_estado = "WHERE estado = 'pendiente_validacion'";
                break;
            case 'validados':
                $where_estado = "WHERE estado IN ('validado', 'en_votacion', 'seleccionado')";
                break;
            case 'rechazados':
                $where_estado = "WHERE estado = 'rechazado'";
                break;
        }

        $proyectos = $wpdb->get_results("SELECT * FROM $tabla_proyectos $where_estado ORDER BY fecha_creacion DESC LIMIT 50");

        ?>
        <div class="wrap">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Título', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Categoría', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Presupuesto', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Votos', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proyectos)): ?>
                        <tr>
                            <td colspan="7"><?php _e('No hay propuestas en esta categoría.', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($proyectos as $proyecto): ?>
                            <tr>
                                <td><?php echo esc_html($proyecto->id); ?></td>
                                <td><strong><?php echo esc_html($proyecto->titulo); ?></strong></td>
                                <td><?php echo esc_html(ucfirst($proyecto->categoria)); ?></td>
                                <td><?php echo number_format($proyecto->presupuesto_solicitado, 2, ',', '.'); ?> €</td>
                                <td>
                                    <span class="flavor-pp-estado flavor-pp-estado-<?php echo esc_attr($proyecto->estado); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $proyecto->estado))); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($proyecto->votos_recibidos); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=pp-propuestas&action=ver&id=' . $proyecto->id); ?>" class="button button-small">
                                        <?php _e('Ver', 'flavor-chat-ia'); ?>
                                    </a>
                                    <?php if ($proyecto->estado === 'pendiente_validacion'): ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=pp-propuestas&action=validar&id=' . $proyecto->id), 'validar_proyecto_' . $proyecto->id); ?>" class="button button-small button-primary">
                                            <?php _e('Validar', 'flavor-chat-ia'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renderiza la página de administración de votaciones
     */
    public function render_admin_votaciones() {
        $this->render_page_header(
            __('Gestión de Votaciones', 'flavor-chat-ia')
        );

        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        $edicion_actual = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion_actual) {
            echo '<div class="notice notice-warning"><p>' . __('No hay edición activa.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $total_votantes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id) FROM $tabla_votos WHERE edicion_id = %d",
            $edicion_actual->id
        ));

        $proyectos_ranking = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, COUNT(v.id) as total_votos
             FROM $tabla_proyectos p
             LEFT JOIN $tabla_votos v ON p.id = v.proyecto_id
             WHERE p.edicion_id = %d AND p.estado IN ('validado', 'en_votacion', 'seleccionado')
             GROUP BY p.id
             ORDER BY total_votos DESC
             LIMIT 20",
            $edicion_actual->id
        ));

        ?>
        <div class="wrap flavor-pp-votaciones">
            <div class="flavor-pp-votacion-info">
                <h2><?php printf(__('Votación - Edición %d', 'flavor-chat-ia'), $edicion_actual->anio); ?></h2>
                <p>
                    <strong><?php _e('Fase actual:', 'flavor-chat-ia'); ?></strong>
                    <?php echo esc_html(ucfirst($edicion_actual->fase)); ?>
                </p>
                <p>
                    <strong><?php _e('Total votantes:', 'flavor-chat-ia'); ?></strong>
                    <?php echo esc_html($total_votantes); ?>
                </p>
                <?php if ($edicion_actual->fecha_inicio_votacion && $edicion_actual->fecha_fin_votacion): ?>
                    <p>
                        <strong><?php _e('Período:', 'flavor-chat-ia'); ?></strong>
                        <?php echo esc_html($edicion_actual->fecha_inicio_votacion); ?> - <?php echo esc_html($edicion_actual->fecha_fin_votacion); ?>
                    </p>
                <?php endif; ?>
            </div>

            <h3><?php _e('Ranking de Proyectos', 'flavor-chat-ia'); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php _e('#', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Proyecto', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Categoría', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Presupuesto', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Votos', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proyectos_ranking)): ?>
                        <tr>
                            <td colspan="5"><?php _e('No hay proyectos en votación.', 'flavor-chat-ia'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $posicion = 1;
                        foreach ($proyectos_ranking as $proyecto):
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($posicion++); ?></strong></td>
                                <td><?php echo esc_html($proyecto->titulo); ?></td>
                                <td><?php echo esc_html(ucfirst($proyecto->categoria)); ?></td>
                                <td><?php echo number_format($proyecto->presupuesto_solicitado, 2, ',', '.'); ?> €</td>
                                <td><strong><?php echo esc_html($proyecto->total_votos); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renderiza widget de resumen para el dashboard unificado
     */
    public function render_widget_resumen() {
        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        if ($edicion) {
            $fases_texto = [
                'propuestas' => __('Recibiendo propuestas', 'flavor-chat-ia'),
                'evaluacion' => __('Evaluando proyectos', 'flavor-chat-ia'),
                'votacion' => __('Votación abierta', 'flavor-chat-ia'),
                'implementacion' => __('En implementación', 'flavor-chat-ia'),
            ];
            echo '<p><strong>' . sprintf(__('Edición %d:', 'flavor-chat-ia'), $edicion->anio) . '</strong> ';
            echo esc_html($fases_texto[$edicion->fase] ?? $edicion->fase) . '</p>';
        } else {
            echo '<p>' . __('Sin edición activa', 'flavor-chat-ia') . '</p>';
        }
    }

    /**
     * Obtiene estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        $propuestas_pendientes = $this->contar_propuestas_pendientes();

        $estadisticas = [];

        if ($propuestas_pendientes > 0) {
            $estadisticas[] = [
                'icon' => 'dashicons-portfolio',
                'valor' => $propuestas_pendientes,
                'label' => __('Propuestas pendientes', 'flavor-chat-ia'),
                'color' => 'orange',
                'enlace' => admin_url('admin.php?page=pp-propuestas&tab=pendientes'),
            ];
        }

        return $estadisticas;
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_proyectos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        $sql_ediciones = "CREATE TABLE IF NOT EXISTS $tabla_ediciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            anio int(11) NOT NULL,
            presupuesto_total decimal(12,2) NOT NULL,
            presupuesto_gastado decimal(12,2) DEFAULT 0.00,
            fase enum('propuestas','evaluacion','votacion','implementacion','cerrada') DEFAULT 'propuestas',
            fecha_inicio_propuestas date DEFAULT NULL,
            fecha_fin_propuestas date DEFAULT NULL,
            fecha_inicio_votacion date DEFAULT NULL,
            fecha_fin_votacion date DEFAULT NULL,
            total_proyectos int(11) DEFAULT 0,
            total_votantes int(11) DEFAULT 0,
            participacion_porcentaje decimal(5,2) DEFAULT 0.00,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY anio (anio)
        ) $charset_collate;";

        $sql_proyectos = "CREATE TABLE IF NOT EXISTS $tabla_proyectos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            edicion_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(50) NOT NULL,
            ambito varchar(100) DEFAULT NULL,
            ubicacion varchar(500) DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            presupuesto_solicitado decimal(12,2) NOT NULL,
            presupuesto_aprobado decimal(12,2) DEFAULT NULL,
            proponente_id bigint(20) unsigned DEFAULT NULL,
            proponente_grupo varchar(255) DEFAULT NULL,
            estado enum('borrador','pendiente_validacion','validado','en_votacion','seleccionado','en_ejecucion','ejecutado','rechazado') DEFAULT 'pendiente_validacion',
            votos_recibidos int(11) DEFAULT 0,
            ranking int(11) DEFAULT 0,
            es_viable tinyint(1) DEFAULT NULL,
            motivo_no_viable text DEFAULT NULL,
            fecha_validacion datetime DEFAULT NULL,
            fecha_inicio_ejecucion datetime DEFAULT NULL,
            fecha_fin_ejecucion datetime DEFAULT NULL,
            porcentaje_ejecucion int(11) DEFAULT 0,
            imagenes text DEFAULT NULL,
            documentos text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY edicion_id (edicion_id),
            KEY proponente_id (proponente_id),
            KEY estado (estado),
            KEY categoria (categoria)
        ) $charset_collate;";

        $sql_votos = "CREATE TABLE IF NOT EXISTS $tabla_votos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            proyecto_id bigint(20) unsigned NOT NULL,
            edicion_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            prioridad int(11) DEFAULT 1,
            fecha_voto datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_proyecto (usuario_id, proyecto_id),
            KEY proyecto_id (proyecto_id),
            KEY edicion_id (edicion_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_ediciones);
        dbDelta($sql_proyectos);
        dbDelta($sql_votos);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'info_edicion_actual' => [
                'description' => 'Ver información de la edición actual de presupuestos participativos',
                'params' => [],
            ],
            'proponer_proyecto' => [
                'description' => 'Proponer un proyecto para presupuestos participativos',
                'params' => ['titulo', 'descripcion', 'categoria', 'presupuesto', 'ubicacion'],
            ],
            'listar_proyectos' => [
                'description' => 'Ver proyectos propuestos',
                'params' => ['categoria', 'estado', 'limite'],
            ],
            'ver_proyecto' => [
                'description' => 'Ver detalles de un proyecto',
                'params' => ['proyecto_id'],
            ],
            'votar_proyectos' => [
                'description' => 'Votar tus proyectos favoritos (hasta 3)',
                'params' => ['proyecto_ids'],
            ],
            'mis_votos' => [
                'description' => 'Ver qué proyectos he votado',
                'params' => [],
            ],
            'resultados' => [
                'description' => 'Ver resultados de la votación',
                'params' => ['edicion_id'],
            ],
            'seguimiento_proyecto' => [
                'description' => 'Ver estado de ejecución de un proyecto aprobado',
                'params' => ['proyecto_id'],
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
     * Acción: Info edición actual
     */
    private function action_info_edicion_actual($params) {
        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return [
                'success' => true,
                'activa' => false,
                'mensaje' => __('default', 'flavor-chat-ia'),
            ];
        }

        $fases_texto = [
            'propuestas' => 'Fase de propuestas ciudadanas',
            'evaluacion' => 'Fase de evaluación técnica',
            'votacion' => 'Fase de votación',
            'implementacion' => 'Fase de implementación',
        ];

        return [
            'success' => true,
            'activa' => true,
            'edicion' => [
                'anio' => $edicion->anio,
                'presupuesto_total' => floatval($edicion->presupuesto_total),
                'presupuesto_disponible' => floatval($edicion->presupuesto_total - $edicion->presupuesto_gastado),
                'fase' => $edicion->fase,
                'fase_texto' => $fases_texto[$edicion->fase] ?? $edicion->fase,
                'fechas' => [
                    'propuestas' => [
                        'inicio' => $edicion->fecha_inicio_propuestas,
                        'fin' => $edicion->fecha_fin_propuestas,
                    ],
                    'votacion' => [
                        'inicio' => $edicion->fecha_inicio_votacion,
                        'fin' => $edicion->fecha_fin_votacion,
                    ],
                ],
                'total_proyectos' => $edicion->total_proyectos,
                'total_votantes' => $edicion->total_votantes,
                'participacion' => floatval($edicion->participacion_porcentaje) . '%',
            ],
        ];
    }

    /**
     * Acción: Proponer proyecto
     */
    private function action_proponer_proyecto($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => __('fields', 'flavor-chat-ia'),
            ];
        }

        // Verificar que estamos en fase de propuestas
        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase = 'propuestas' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return [
                'success' => false,
                'error' => __('text', 'flavor-chat-ia'),
            ];
        }

        $titulo = sanitize_text_field($params['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($params['descripcion'] ?? '');
        $presupuesto = floatval($params['presupuesto'] ?? 0);

        if (empty($titulo) || empty($descripcion)) {
            return [
                'success' => false,
                'error' => __('presupuestos-participativos/resultados', 'flavor-chat-ia'),
            ];
        }

        $settings = $this->settings;
        if ($presupuesto < $settings['presupuesto_minimo_proyecto'] || $presupuesto > $settings['presupuesto_maximo_proyecto']) {
            return [
                'success' => false,
                'error' => sprintf(
                    'El presupuesto debe estar entre %s€ y %s€.',
                    number_format($settings['presupuesto_minimo_proyecto'], 0, ',', '.'),
                    number_format($settings['presupuesto_maximo_proyecto'], 0, ',', '.')
                ),
            ];
        }

        $resultado = $wpdb->insert(
            $tabla_proyectos,
            [
                'edicion_id' => $edicion->id,
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'categoria' => sanitize_text_field($params['categoria'] ?? 'social'),
                'ubicacion' => sanitize_text_field($params['ubicacion'] ?? ''),
                'presupuesto_solicitado' => $presupuesto,
                'proponente_id' => $usuario_id,
                'estado' => 'pendiente_validacion',
            ],
            ['%d', '%s', '%s', '%s', '%s', '%f', '%d', '%s']
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Acción no implementada: {$action_name}', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'proyecto_id' => $wpdb->insert_id,
            'mensaje' => __('¡Proyecto propuesto! Será evaluado técnicamente antes de pasar a votación.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: Listar proyectos
     */
    private function action_listar_proyectos($params) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        // Obtener edición actual
        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase != 'cerrada' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return [
                'success' => true,
                'total' => 0,
                'proyectos' => [],
            ];
        }

        $where = ['edicion_id = %d'];
        $prepare_values = [$edicion->id];

        if (!empty($params['categoria'])) {
            $where[] = 'categoria = %s';
            $prepare_values[] = $params['categoria'];
        }

        if (!empty($params['estado'])) {
            $where[] = 'estado = %s';
            $prepare_values[] = $params['estado'];
        } else {
            // Por defecto, mostrar proyectos validados o en votación
            $where[] = "estado IN ('validado', 'en_votacion', 'seleccionado')";
        }

        $limite = absint($params['limite'] ?? 20);
        $sql_where = implode(' AND ', $where);

        $sql = "SELECT * FROM $tabla_proyectos WHERE $sql_where ORDER BY votos_recibidos DESC, fecha_creacion DESC LIMIT %d";
        $prepare_values[] = $limite;

        $proyectos = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_values));

        return [
            'success' => true,
            'edicion' => $edicion->anio,
            'fase' => $edicion->fase,
            'total' => count($proyectos),
            'proyectos' => array_map(function($p) {
                return [
                    'id' => $p->id,
                    'titulo' => $p->titulo,
                    'descripcion' => wp_trim_words($p->descripcion, 30),
                    'categoria' => $p->categoria,
                    'presupuesto' => floatval($p->presupuesto_solicitado),
                    'ubicacion' => $p->ubicacion,
                    'votos' => $p->votos_recibidos,
                    'ranking' => $p->ranking,
                    'estado' => $p->estado,
                    'porcentaje_ejecucion' => $p->porcentaje_ejecucion,
                ];
            }, $proyectos),
        ];
    }

    /**
     * Acción: Votar proyectos
     */
    private function action_votar_proyectos($params) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => __('Acción no implementada: {$action_name}', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_ediciones = $wpdb->prefix . 'flavor_pp_ediciones';

        // Verificar fase de votación
        $edicion = $wpdb->get_row(
            "SELECT * FROM $tabla_ediciones WHERE fase = 'votacion' ORDER BY anio DESC LIMIT 1"
        );

        if (!$edicion) {
            return [
                'success' => false,
                'error' => __('Acción no implementada: {$action_name}', 'flavor-chat-ia'),
            ];
        }

        $proyecto_ids = $params['proyecto_ids'] ?? [];
        if (!is_array($proyecto_ids)) {
            $proyecto_ids = [$proyecto_ids];
        }

        $settings = $this->settings;
        $max_votos = $settings['votos_maximos_por_persona'];

        if (count($proyecto_ids) > $max_votos) {
            return [
                'success' => false,
                'error' => "Solo puedes votar hasta {$max_votos} proyectos.",
            ];
        }

        // Limpiar votos anteriores
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $wpdb->delete(
            $tabla_votos,
            ['usuario_id' => $usuario_id, 'edicion_id' => $edicion->id],
            ['%d', '%d']
        );

        // Registrar nuevos votos
        $votos_registrados = 0;
        foreach ($proyecto_ids as $prioridad => $proyecto_id) {
            $resultado = $wpdb->insert(
                $tabla_votos,
                [
                    'proyecto_id' => absint($proyecto_id),
                    'edicion_id' => $edicion->id,
                    'usuario_id' => $usuario_id,
                    'prioridad' => $prioridad + 1,
                ],
                ['%d', '%d', '%d', '%d']
            );

            if ($resultado !== false) {
                $votos_registrados++;
            }
        }

        // Actualizar contadores
        $this->actualizar_contadores_votos($edicion->id);

        return [
            'success' => true,
            'votos_registrados' => $votos_registrados,
            'mensaje' => "¡Voto registrado! Has votado {$votos_registrados} proyectos.",
        ];
    }

    /**
     * Actualiza contadores de votos
     */
    private function actualizar_contadores_votos($edicion_id) {
        global $wpdb;
        $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_pp_proyectos';

        // Actualizar votos por proyecto
        $wpdb->query("
            UPDATE $tabla_proyectos p
            SET votos_recibidos = (
                SELECT COUNT(*) FROM $tabla_votos v
                WHERE v.proyecto_id = p.id AND v.edicion_id = $edicion_id
            )
            WHERE p.edicion_id = $edicion_id
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'pp_info',
                'description' => 'Ver información de los presupuestos participativos actuales',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
            [
                'name' => 'pp_proponer',
                'description' => 'Proponer un proyecto para presupuestos participativos',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'titulo' => ['type' => 'string', 'description' => 'Título del proyecto'],
                        'descripcion' => ['type' => 'string', 'description' => 'Descripción detallada'],
                        'categoria' => ['type' => 'string', 'description' => 'Categoría'],
                        'presupuesto' => ['type' => 'number', 'description' => 'Presupuesto estimado en euros'],
                    ],
                    'required' => ['titulo', 'descripcion', 'presupuesto'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Presupuestos Participativos**

Democracia económica directa: los vecinos deciden en qué se invierte parte del presupuesto municipal.

**Fases del proceso:**
1. Propuestas: Cualquier vecino puede proponer proyectos
2. Evaluación: Técnicos evalúan viabilidad y coste
3. Votación: Los vecinos votan sus proyectos favoritos
4. Implementación: Se ejecutan los proyectos ganadores

**Tipos de proyectos:**
- Infraestructura: Arreglos, mejoras urbanas
- Medio ambiente: Zonas verdes, reciclaje
- Cultura: Actividades, espacios culturales
- Deporte: Instalaciones deportivas
- Social: Servicios sociales, inclusión
- Educación: Formación, bibliotecas
- Accesibilidad: Rampas, adaptaciones

**Reglas:**
- Cada vecino puede votar hasta 3 proyectos
- Los proyectos se ordenan por votos recibidos
- Se aprueban en orden hasta agotar presupuesto
- Cada proyecto tiene un presupuesto mínimo y máximo
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Qué son los presupuestos participativos?',
                'respuesta' => 'Es un proceso donde los vecinos proponemos y votamos proyectos para el barrio, y el ayuntamiento ejecuta los más votados.',
            ],
            [
                'pregunta' => '¿Cuántos proyectos puedo votar?',
                'respuesta' => 'Normalmente puedes votar hasta 3 proyectos, eligiendo tus favoritos.',
            ],
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero' => [
                'label' => __('Hero Presupuestos', 'flavor-chat-ia'),
                'description' => __('Sección hero con fase actual del proceso', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-money-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Presupuestos Participativos', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => __('Decide en qué se invierte el presupuesto de tu barrio', 'flavor-chat-ia'),
                    ],
                    'mostrar_fase' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar fase actual', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_presupuesto' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar presupuesto total', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'presupuestos-participativos/hero',
            ],
            'proyectos_grid' => [
                'label' => __('Grid de Proyectos', 'flavor-chat-ia'),
                'description' => __('Listado de proyectos propuestos', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-portfolio',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Proyectos Propuestos', 'flavor-chat-ia'),
                    ],
                    'categoria' => [
                        'type' => 'select',
                        'label' => __('Filtrar por categoría', 'flavor-chat-ia'),
                        'options' => ['todas', 'infraestructura', 'cultura', 'medio_ambiente', 'social'],
                        'default' => 'todas',
                    ],
                    'ordenar' => [
                        'type' => 'select',
                        'label' => __('Ordenar por', 'flavor-chat-ia'),
                        'options' => ['votos', 'coste', 'recientes'],
                        'default' => 'votos',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Número máximo', 'flavor-chat-ia'),
                        'default' => 9,
                    ],
                ],
                'template' => 'presupuestos-participativos/proyectos-grid',
            ],
            'fases_proceso' => [
                'label' => __('Fases del Proceso', 'flavor-chat-ia'),
                'description' => __('Timeline del proceso participativo', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-backup',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Cómo funciona?', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'presupuestos-participativos/fases',
            ],
            'resultados' => [
                'label' => __('Resultados Votación', 'flavor-chat-ia'),
                'description' => __('Proyectos ganadores y estadísticas', 'flavor-chat-ia'),
                'category' => 'content',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('Resultados', 'flavor-chat-ia'),
                    ],
                    'edicion' => [
                        'type' => 'select',
                        'label' => __('Edición', 'flavor-chat-ia'),
                        'options' => ['actual', 'anterior'],
                        'default' => 'actual',
                    ],
                ],
                'template' => 'presupuestos-participativos/resultados',
            ],
            'cta_proponer' => [
                'label' => __('CTA Proponer Proyecto', 'flavor-chat-ia'),
                'description' => __('Llamada a acción para proponer proyecto', 'flavor-chat-ia'),
                'category' => 'cta',
                'icon' => 'dashicons-plus-alt',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => __('¿Tienes un proyecto para el barrio?', 'flavor-chat-ia'),
                    ],
                    'boton_texto' => [
                        'type' => 'text',
                        'label' => __('Texto del botón', 'flavor-chat-ia'),
                        'default' => __('Proponer Proyecto', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'presupuestos-participativos/cta-proponer',
            ],
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
            Flavor_Page_Creator::refresh_module_pages('presupuestos_participativos');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('presupuestos-participativos');
        if (!$pagina && !get_option('flavor_presupuestos_participativos_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['presupuestos_participativos']);
            update_option('flavor_presupuestos_participativos_pages_created', 1, false);
        }
    }

}
