<?php
/**
 * Modulo de Gestion de Clientes / CRM para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo: Gestion de Clientes
 * CRM basico para gestionar clientes, notas e interacciones
 */
class Flavor_Chat_Clientes_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'clientes';
        $this->name = 'Gestion de Clientes'; // Translation loaded on init
        $this->description = 'CRM basico para gestionar clientes, notas e interacciones'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        return Flavor_Chat_Helpers::tabla_existe($tabla_clientes);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas del modulo de Clientes no estan creadas. Se crearan automaticamente al activar.', 'flavor-chat-ia');
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
            'limite_resultados_por_defecto' => 20,
            'tipos_cliente' => ['particular', 'empresa', 'autonomo', 'administracion'],
            'estados_cliente' => ['activo', 'inactivo', 'potencial', 'perdido'],
            'origenes_cliente' => ['web', 'referido', 'redes', 'directo', 'otro'],
            'tipos_nota' => ['nota', 'llamada', 'email', 'reunion', 'tarea', 'seguimiento'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Registrar en Panel Unificado de Gestion
        
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();
        $this->registrar_en_panel_unificado();
    }

    // =====================================================================
    // REST API ENDPOINTS
    // =====================================================================

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes() {
        // GET /flavor/v1/clientes - Listar clientes
        register_rest_route('flavor/v1', '/clientes', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_clientes'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
            'args' => [
                'estado' => [
                    'type' => 'string',
                    'enum' => ['activo', 'inactivo', 'potencial', 'perdido'],
                    'description' => 'Filtrar por estado del cliente',
                ],
                'tipo' => [
                    'type' => 'string',
                    'enum' => ['particular', 'empresa', 'autonomo', 'administracion'],
                    'description' => 'Filtrar por tipo de cliente',
                ],
                'etiquetas' => [
                    'type' => 'string',
                    'description' => 'Filtrar por etiquetas',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                    'description' => 'Numero maximo de resultados',
                ],
                'pagina' => [
                    'type' => 'integer',
                    'default' => 1,
                    'description' => 'Numero de pagina',
                ],
            ],
        ]);

        // GET /flavor/v1/clientes/{id} - Obtener un cliente
        register_rest_route('flavor/v1', '/clientes/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_cliente'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
            'args' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID del cliente',
                ],
            ],
        ]);

        // POST /flavor/v1/clientes - Crear cliente
        register_rest_route('flavor/v1', '/clientes', [
            'methods' => 'POST',
            'callback' => [$this, 'api_crear_cliente'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
            'args' => [
                'nombre' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Nombre del cliente',
                ],
                'email' => [
                    'type' => 'string',
                    'format' => 'email',
                    'description' => 'Email del cliente',
                ],
                'telefono' => [
                    'type' => 'string',
                    'description' => 'Telefono del cliente',
                ],
                'empresa' => [
                    'type' => 'string',
                    'description' => 'Empresa del cliente',
                ],
                'cargo' => [
                    'type' => 'string',
                    'description' => 'Cargo del cliente',
                ],
                'direccion' => [
                    'type' => 'string',
                    'description' => 'Direccion del cliente',
                ],
                'tipo' => [
                    'type' => 'string',
                    'enum' => ['particular', 'empresa', 'autonomo', 'administracion'],
                    'default' => 'particular',
                    'description' => 'Tipo de cliente',
                ],
                'estado' => [
                    'type' => 'string',
                    'enum' => ['activo', 'inactivo', 'potencial', 'perdido'],
                    'default' => 'potencial',
                    'description' => 'Estado del cliente',
                ],
                'etiquetas' => [
                    'type' => 'string',
                    'description' => 'Etiquetas separadas por comas',
                ],
                'valor_estimado' => [
                    'type' => 'number',
                    'default' => 0,
                    'description' => 'Valor estimado del cliente',
                ],
                'origen' => [
                    'type' => 'string',
                    'enum' => ['web', 'referido', 'redes', 'directo', 'otro'],
                    'default' => 'directo',
                    'description' => 'Origen del cliente',
                ],
                'asignado_a' => [
                    'type' => 'integer',
                    'description' => 'ID del usuario asignado',
                ],
            ],
        ]);

        // PUT /flavor/v1/clientes/{id} - Actualizar cliente
        register_rest_route('flavor/v1', '/clientes/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'api_actualizar_cliente'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
            'args' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID del cliente',
                ],
                'nombre' => [
                    'type' => 'string',
                    'description' => 'Nombre del cliente',
                ],
                'email' => [
                    'type' => 'string',
                    'format' => 'email',
                    'description' => 'Email del cliente',
                ],
                'telefono' => [
                    'type' => 'string',
                    'description' => 'Telefono del cliente',
                ],
                'empresa' => [
                    'type' => 'string',
                    'description' => 'Empresa del cliente',
                ],
                'cargo' => [
                    'type' => 'string',
                    'description' => 'Cargo del cliente',
                ],
                'direccion' => [
                    'type' => 'string',
                    'description' => 'Direccion del cliente',
                ],
                'tipo' => [
                    'type' => 'string',
                    'enum' => ['particular', 'empresa', 'autonomo', 'administracion'],
                    'description' => 'Tipo de cliente',
                ],
                'estado' => [
                    'type' => 'string',
                    'enum' => ['activo', 'inactivo', 'potencial', 'perdido'],
                    'description' => 'Estado del cliente',
                ],
                'etiquetas' => [
                    'type' => 'string',
                    'description' => 'Etiquetas separadas por comas',
                ],
                'valor_estimado' => [
                    'type' => 'number',
                    'description' => 'Valor estimado del cliente',
                ],
                'origen' => [
                    'type' => 'string',
                    'enum' => ['web', 'referido', 'redes', 'directo', 'otro'],
                    'description' => 'Origen del cliente',
                ],
                'asignado_a' => [
                    'type' => 'integer',
                    'description' => 'ID del usuario asignado',
                ],
            ],
        ]);

        // GET /flavor/v1/clientes/buscar - Buscar clientes
        register_rest_route('flavor/v1', '/clientes/buscar', [
            'methods' => 'GET',
            'callback' => [$this, 'api_buscar_clientes'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
            'args' => [
                'busqueda' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Termino de busqueda',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 10,
                    'description' => 'Numero maximo de resultados',
                ],
            ],
        ]);

        // POST /flavor/v1/clientes/{id}/notas - Agregar nota a cliente
        register_rest_route('flavor/v1', '/clientes/(?P<id>\d+)/notas', [
            'methods' => 'POST',
            'callback' => [$this, 'api_agregar_nota'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
            'args' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID del cliente',
                ],
                'contenido' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Contenido de la nota',
                ],
                'tipo' => [
                    'type' => 'string',
                    'enum' => ['nota', 'llamada', 'email', 'reunion', 'tarea', 'seguimiento'],
                    'default' => 'nota',
                    'description' => 'Tipo de nota/interaccion',
                ],
                'estado' => [
                    'type' => 'string',
                    'enum' => ['pendiente', 'completada', 'cancelada'],
                    'default' => 'completada',
                    'description' => 'Estado de la nota',
                ],
                'fecha_seguimiento' => [
                    'type' => 'string',
                    'description' => 'Fecha de seguimiento (YYYY-MM-DD HH:MM:SS)',
                ],
            ],
        ]);

        // GET /flavor/v1/clientes/estadisticas - Estadisticas del CRM
        register_rest_route('flavor/v1', '/clientes/estadisticas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_estadisticas'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * API: Listar clientes
     *
     * @param WP_REST_Request $request Peticion REST
     * @return WP_REST_Response
     */
    public function api_listar_clientes($request) {
        $parametros = [
            'estado' => $request->get_param('estado'),
            'tipo' => $request->get_param('tipo'),
            'etiquetas' => $request->get_param('etiquetas'),
            'limite' => $request->get_param('limite'),
            'pagina' => $request->get_param('pagina'),
        ];

        $resultado = $this->action_listar_clientes($parametros);

        if (!$resultado['success']) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $resultado['error'] ?? __('Error al listar clientes', 'flavor-chat-ia'),
            ], 400);
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Obtener un cliente
     *
     * @param WP_REST_Request $request Peticion REST
     * @return WP_REST_Response
     */
    public function api_obtener_cliente($request) {
        $cliente_id = $request->get_param('id');

        $resultado = $this->action_ver_cliente(['cliente_id' => $cliente_id]);

        if (!$resultado['success']) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $resultado['error'] ?? __('Cliente no encontrado', 'flavor-chat-ia'),
            ], 404);
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Crear cliente
     *
     * @param WP_REST_Request $request Peticion REST
     * @return WP_REST_Response
     */
    public function api_crear_cliente($request) {
        $parametros = [
            'nombre' => $request->get_param('nombre'),
            'email' => $request->get_param('email'),
            'telefono' => $request->get_param('telefono'),
            'empresa' => $request->get_param('empresa'),
            'cargo' => $request->get_param('cargo'),
            'direccion' => $request->get_param('direccion'),
            'tipo' => $request->get_param('tipo'),
            'estado' => $request->get_param('estado'),
            'etiquetas' => $request->get_param('etiquetas'),
            'valor_estimado' => $request->get_param('valor_estimado'),
            'origen' => $request->get_param('origen'),
            'asignado_a' => $request->get_param('asignado_a'),
        ];

        $resultado = $this->action_crear_cliente($parametros);

        if (!$resultado['success']) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $resultado['error'] ?? __('Error al crear cliente', 'flavor-chat-ia'),
            ], 400);
        }

        return new WP_REST_Response($resultado, 201);
    }

    /**
     * API: Actualizar cliente
     *
     * @param WP_REST_Request $request Peticion REST
     * @return WP_REST_Response
     */
    public function api_actualizar_cliente($request) {
        $parametros = [
            'cliente_id' => $request->get_param('id'),
            'nombre' => $request->get_param('nombre'),
            'email' => $request->get_param('email'),
            'telefono' => $request->get_param('telefono'),
            'empresa' => $request->get_param('empresa'),
            'cargo' => $request->get_param('cargo'),
            'direccion' => $request->get_param('direccion'),
            'tipo' => $request->get_param('tipo'),
            'estado' => $request->get_param('estado'),
            'etiquetas' => $request->get_param('etiquetas'),
            'valor_estimado' => $request->get_param('valor_estimado'),
            'origen' => $request->get_param('origen'),
            'asignado_a' => $request->get_param('asignado_a'),
        ];

        // Filtrar parametros nulos para no sobrescribir con valores vacios
        $parametros = array_filter($parametros, function($valor) {
            return $valor !== null;
        });

        $resultado = $this->action_actualizar_cliente($parametros);

        if (!$resultado['success']) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $resultado['error'] ?? __('Error al actualizar cliente', 'flavor-chat-ia'),
            ], 400);
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Buscar clientes
     *
     * @param WP_REST_Request $request Peticion REST
     * @return WP_REST_Response
     */
    public function api_buscar_clientes($request) {
        $parametros = [
            'busqueda' => $request->get_param('busqueda'),
            'limite' => $request->get_param('limite'),
        ];

        $resultado = $this->action_buscar_clientes($parametros);

        if (!$resultado['success']) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $resultado['error'] ?? __('Error en la busqueda', 'flavor-chat-ia'),
            ], 400);
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Agregar nota a cliente
     *
     * @param WP_REST_Request $request Peticion REST
     * @return WP_REST_Response
     */
    public function api_agregar_nota($request) {
        $parametros = [
            'cliente_id' => $request->get_param('id'),
            'contenido' => $request->get_param('contenido'),
            'tipo' => $request->get_param('tipo'),
            'estado' => $request->get_param('estado'),
            'fecha_seguimiento' => $request->get_param('fecha_seguimiento'),
        ];

        $resultado = $this->action_agregar_nota($parametros);

        if (!$resultado['success']) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $resultado['error'] ?? __('Error al agregar nota', 'flavor-chat-ia'),
            ], 400);
        }

        return new WP_REST_Response($resultado, 201);
    }

    /**
     * API: Estadisticas del CRM
     *
     * @param WP_REST_Request $request Peticion REST
     * @return WP_REST_Response
     */
    public function api_estadisticas($request) {
        $resultado = $this->action_estadisticas([]);

        if (!$resultado['success']) {
            return new WP_REST_Response([
                'success' => false,
                'error' => $resultado['error'] ?? __('Error al obtener estadisticas', 'flavor-chat-ia'),
            ], 400);
        }

        return rest_ensure_response($resultado);
    }

    /**
     * Configuracion para el Panel Unificado de Gestion
     *
     * @return array Configuracion del modulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'clientes',
            'label' => __('Clientes', 'flavor-chat-ia'),
            'icon' => 'dashicons-businessman',
            'capability' => 'manage_options',
            'categoria' => 'personas',
            'paginas' => [
                [
                    'slug' => 'clientes-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'clientes-listado',
                    'titulo' => __('Clientes', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_listado'],
                    'badge' => [$this, 'contar_clientes_activos'],
                ],
                [
                    'slug' => 'clientes-fichas',
                    'titulo' => __('Fichas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_fichas'],
                ],
                [
                    'slug' => 'clientes-config',
                    'titulo' => __('Configuracion', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
                [
                    'slug' => 'clientes-nuevo',
                    'titulo' => __('Nuevo Cliente', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_nuevo_alias'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta clientes activos para el badge
     *
     * @return int
     */
    public function contar_clientes_activos() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_clientes)) {
            return 0;
        }
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_clientes WHERE estado = 'activo'"
        );
    }

    /**
     * Estadisticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
        $estadisticas = [];

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_clientes)) {
            return $estadisticas;
        }

        // Total de clientes
        $total_clientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_clientes");
        $estadisticas[] = [
            'icon' => 'dashicons-businessman',
            'valor' => $total_clientes,
            'label' => __('Total Clientes', 'flavor-chat-ia'),
            'color' => $total_clientes > 0 ? 'blue' : 'gray',
            'enlace' => admin_url('admin.php?page=clientes-listado'),
        ];

        // Clientes activos
        $clientes_activos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_clientes WHERE estado = 'activo'"
        );
        $estadisticas[] = [
            'icon' => 'dashicons-yes-alt',
            'valor' => $clientes_activos,
            'label' => __('Clientes Activos', 'flavor-chat-ia'),
            'color' => $clientes_activos > 0 ? 'green' : 'gray',
            'enlace' => admin_url('admin.php?page=clientes-listado&estado=activo'),
        ];

        // Clientes potenciales
        $clientes_potenciales = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_clientes WHERE estado = 'potencial'"
        );
        $estadisticas[] = [
            'icon' => 'dashicons-star-empty',
            'valor' => $clientes_potenciales,
            'label' => __('Potenciales', 'flavor-chat-ia'),
            'color' => $clientes_potenciales > 0 ? 'orange' : 'gray',
            'enlace' => admin_url('admin.php?page=clientes-listado&estado=potencial'),
        ];

        // Valor total del pipeline
        $valor_pipeline = (float) $wpdb->get_var(
            "SELECT IFNULL(SUM(valor_estimado), 0) FROM $tabla_clientes WHERE valor_estimado > 0"
        );
        if ($valor_pipeline > 0) {
            $estadisticas[] = [
                'icon' => 'dashicons-chart-line',
                'valor' => $this->format_price($valor_pipeline),
                'label' => __('Valor Pipeline', 'flavor-chat-ia'),
                'color' => 'purple',
                'enlace' => admin_url('admin.php?page=clientes-dashboard'),
            ];
        }

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard de clientes
     */
    public function render_admin_dashboard() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Dashboard de Clientes', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Cliente', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=clientes-listado&action=nuevo'), 'class' => 'button-primary'],
        ]);

        // Resumen de estadisticas
        $estadisticas = $this->action_estadisticas([]);
        if ($estadisticas['success'] && !empty($estadisticas['estadisticas'])) {
            $datos = $estadisticas['estadisticas'];
            echo '<div class="flavor-stats-grid">';
            echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($datos['total_clientes']) . '</span><span class="stat-label">' . __('Total Clientes', 'flavor-chat-ia') . '</span></div>';
            echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($datos['nuevos_este_mes']) . '</span><span class="stat-label">' . __('Nuevos este mes', 'flavor-chat-ia') . '</span></div>';
            echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($this->format_price($datos['pipeline']['valor_total'])) . '</span><span class="stat-label">' . __('Valor Pipeline', 'flavor-chat-ia') . '</span></div>';
            echo '</div>';

            // Desglose por estado
            if (!empty($datos['por_estado'])) {
                echo '<h3>' . __('Clientes por Estado', 'flavor-chat-ia') . '</h3>';
                echo '<ul class="flavor-list-inline">';
                foreach ($datos['por_estado'] as $estado_nombre => $cantidad_estado) {
                    echo '<li><strong>' . esc_html(ucfirst($estado_nombre)) . ':</strong> ' . esc_html($cantidad_estado) . '</li>';
                }
                echo '</ul>';
            }

            // Desglose por tipo
            if (!empty($datos['por_tipo'])) {
                echo '<h3>' . __('Clientes por Tipo', 'flavor-chat-ia') . '</h3>';
                echo '<ul class="flavor-list-inline">';
                foreach ($datos['por_tipo'] as $tipo_nombre => $cantidad_tipo) {
                    echo '<li><strong>' . esc_html(ucfirst($tipo_nombre)) . ':</strong> ' . esc_html($cantidad_tipo) . '</li>';
                }
                echo '</ul>';
            }
        }

        echo '<p>' . __('Panel de control del CRM con metricas y accesos rapidos.', 'flavor-chat-ia') . '</p>';
        echo '</div>';
    }

    /**
     * Renderiza el listado de clientes
     */
    public function render_admin_listado() {
        $admin_action = isset($_GET['action']) ? sanitize_key(wp_unslash((string) $_GET['action'])) : '';

        if ($admin_action === 'exportar') {
            $this->exportar_clientes_csv_admin();
            return;
        }

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Clientes', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Cliente', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=clientes-listado&action=nuevo'), 'class' => 'button-primary'],
            ['label' => __('Exportar', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=clientes-listado&action=exportar'), 'class' => 'button'],
        ]);

        if ($admin_action === 'nuevo') {
            $this->render_admin_form_cliente();
            echo '</div>';
            return;
        }

        // Filtros
        $estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        $tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';

        // Listado de clientes
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        $condiciones_where = ['1=1'];
        $valores_preparar = [];

        if (!empty($estado_filtro)) {
            $condiciones_where[] = 'estado = %s';
            $valores_preparar[] = $estado_filtro;
        }

        if (!empty($tipo_filtro)) {
            $condiciones_where[] = 'tipo = %s';
            $valores_preparar[] = $tipo_filtro;
        }

        $sql_where = implode(' AND ', $condiciones_where);
        $sql_query = "SELECT * FROM $tabla_clientes WHERE $sql_where ORDER BY updated_at DESC LIMIT 50";

        if (!empty($valores_preparar)) {
            $clientes = $wpdb->get_results($wpdb->prepare($sql_query, ...$valores_preparar), ARRAY_A);
        } else {
            $clientes = $wpdb->get_results($sql_query, ARRAY_A);
        }

        if (!empty($clientes)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Nombre', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Email', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Telefono', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Tipo', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Estado', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Valor', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Acciones', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            foreach ($clientes as $cliente) {
                $clase_estado = 'status-' . esc_attr($cliente['estado']);
                echo '<tr>';
                echo '<td><strong>' . esc_html($cliente['nombre']) . '</strong>';
                if (!empty($cliente['empresa'])) {
                    echo '<br><small>' . esc_html($cliente['empresa']) . '</small>';
                }
                echo '</td>';
                echo '<td>' . esc_html($cliente['email']) . '</td>';
                echo '<td>' . esc_html($cliente['telefono']) . '</td>';
                echo '<td>' . esc_html(ucfirst($cliente['tipo'])) . '</td>';
                echo '<td><span class="' . esc_attr($clase_estado) . '">' . esc_html(ucfirst($cliente['estado'])) . '</span></td>';
                echo '<td>' . esc_html($this->format_price((float) $cliente['valor_estimado'])) . '</td>';
                echo '<td>';
                echo '<a href="' . esc_url(admin_url('admin.php?page=clientes-fichas&cliente_id=' . $cliente['id'])) . '" class="button button-small">' . __('Ver', 'flavor-chat-ia') . '</a> ';
                echo '<a href="' . esc_url(admin_url('admin.php?page=clientes-listado&action=nuevo&editar=' . $cliente['id'])) . '" class="button button-small">' . __('Editar', 'flavor-chat-ia') . '</a>';
                echo ' <a href="' . esc_url(add_query_arg([
                    'page' => 'facturas-nueva',
                    'cliente_id' => absint($cliente['id']),
                    'cliente_tipo' => 'crm_cliente',
                    'cliente_nombre' => (string) $cliente['nombre'],
                    'cliente_email' => (string) $cliente['email'],
                ], admin_url('admin.php'))) . '" class="button button-small">' . __('Facturar', 'flavor-chat-ia') . '</a>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No se encontraron clientes.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza formulario admin de cliente (crear/editar) y procesa guardado.
     *
     * @return void
     */
    private function render_admin_form_cliente() {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
        $settings = $this->get_default_settings();

        $cliente_id = isset($_GET['editar']) ? absint($_GET['editar']) : 0;
        $modo_edicion = $cliente_id > 0;

        $form_data = [
            'nombre' => '',
            'email' => '',
            'telefono' => '',
            'empresa' => '',
            'cargo' => '',
            'direccion' => '',
            'tipo' => 'particular',
            'estado' => 'potencial',
            'etiquetas' => '',
            'valor_estimado' => '0',
            'origen' => 'directo',
            'asignado_a' => 0,
        ];

        if ($modo_edicion) {
            $cliente = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_clientes WHERE id = %d", $cliente_id));
            if ($cliente) {
                $etiquetas = json_decode((string) $cliente->etiquetas, true);
                if (!is_array($etiquetas)) {
                    $etiquetas = [];
                }

                $form_data = [
                    'nombre' => (string) $cliente->nombre,
                    'email' => (string) $cliente->email,
                    'telefono' => (string) $cliente->telefono,
                    'empresa' => (string) $cliente->empresa,
                    'cargo' => (string) $cliente->cargo,
                    'direccion' => (string) $cliente->direccion,
                    'tipo' => (string) $cliente->tipo,
                    'estado' => (string) $cliente->estado,
                    'etiquetas' => implode(', ', $etiquetas),
                    'valor_estimado' => (string) $cliente->valor_estimado,
                    'origen' => (string) $cliente->origen,
                    'asignado_a' => (int) $cliente->asignado_a,
                ];
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Cliente no encontrado para editar.', 'flavor-chat-ia') . '</p></div>';
                $modo_edicion = false;
                $cliente_id = 0;
            }
        }

        if (isset($_POST['flavor_cliente_guardar'])) {
            $nonce = isset($_POST['flavor_cliente_nonce']) ? sanitize_text_field(wp_unslash((string) $_POST['flavor_cliente_nonce'])) : '';

            if (!wp_verify_nonce($nonce, 'flavor_cliente_admin_guardar')) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error de seguridad. Recarga la página e inténtalo de nuevo.', 'flavor-chat-ia') . '</p></div>';
            } else {
                $valor_estimado_raw = isset($_POST['valor_estimado']) ? sanitize_text_field(wp_unslash((string) $_POST['valor_estimado'])) : '0';
                $valor_estimado = str_replace(',', '.', $valor_estimado_raw);

                $params = [
                    'nombre' => isset($_POST['nombre']) ? sanitize_text_field(wp_unslash((string) $_POST['nombre'])) : '',
                    'email' => isset($_POST['email']) ? sanitize_email(wp_unslash((string) $_POST['email'])) : '',
                    'telefono' => isset($_POST['telefono']) ? sanitize_text_field(wp_unslash((string) $_POST['telefono'])) : '',
                    'empresa' => isset($_POST['empresa']) ? sanitize_text_field(wp_unslash((string) $_POST['empresa'])) : '',
                    'cargo' => isset($_POST['cargo']) ? sanitize_text_field(wp_unslash((string) $_POST['cargo'])) : '',
                    'direccion' => isset($_POST['direccion']) ? sanitize_textarea_field(wp_unslash((string) $_POST['direccion'])) : '',
                    'tipo' => isset($_POST['tipo']) ? sanitize_text_field(wp_unslash((string) $_POST['tipo'])) : 'particular',
                    'estado' => isset($_POST['estado']) ? sanitize_text_field(wp_unslash((string) $_POST['estado'])) : 'potencial',
                    'etiquetas' => isset($_POST['etiquetas']) ? sanitize_text_field(wp_unslash((string) $_POST['etiquetas'])) : '',
                    'valor_estimado' => (float) $valor_estimado,
                    'origen' => isset($_POST['origen']) ? sanitize_text_field(wp_unslash((string) $_POST['origen'])) : 'directo',
                    'asignado_a' => isset($_POST['asignado_a']) ? absint($_POST['asignado_a']) : 0,
                ];

                if ($modo_edicion) {
                    $params['cliente_id'] = $cliente_id;
                }

                $resultado = $this->execute_action($modo_edicion ? 'actualizar_cliente' : 'crear_cliente', $params);

                if (!empty($resultado['success'])) {
                    $redirect_url = $modo_edicion
                        ? admin_url('admin.php?page=clientes-listado&action=nuevo&editar=' . $cliente_id . '&updated=1')
                        : admin_url('admin.php?page=clientes-listado&action=nuevo&created=1&cliente_id=' . absint($resultado['cliente_id'] ?? 0));
                    wp_safe_redirect($redirect_url);
                    exit;
                }

                echo '<div class="notice notice-error"><p>' . esc_html($resultado['error'] ?? __('No se pudo guardar el cliente.', 'flavor-chat-ia')) . '</p></div>';
                $form_data = array_merge($form_data, $params);
            }
        }

        if (isset($_GET['created']) && absint($_GET['created']) === 1) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Cliente creado correctamente.', 'flavor-chat-ia') . '</p></div>';
        }
        if (isset($_GET['updated']) && absint($_GET['updated']) === 1) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Cliente actualizado correctamente.', 'flavor-chat-ia') . '</p></div>';
        }

        $usuarios = get_users([
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => 200,
        ]);

        echo '<h2 style="margin-top:16px;">' . esc_html($modo_edicion ? __('Editar Cliente', 'flavor-chat-ia') : __('Nuevo Cliente', 'flavor-chat-ia')) . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin.php?page=clientes-listado&action=nuevo' . ($modo_edicion ? '&editar=' . $cliente_id : ''))) . '">';
        wp_nonce_field('flavor_cliente_admin_guardar', 'flavor_cliente_nonce');
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th scope="row"><label for="cliente_nombre">' . esc_html__('Nombre', 'flavor-chat-ia') . ' *</label></th>';
        echo '<td><input type="text" id="cliente_nombre" name="nombre" class="regular-text" required value="' . esc_attr((string) $form_data['nombre']) . '" /></td></tr>';

        echo '<tr><th scope="row"><label for="cliente_email">' . esc_html__('Email', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="email" id="cliente_email" name="email" class="regular-text" value="' . esc_attr((string) $form_data['email']) . '" /></td></tr>';

        echo '<tr><th scope="row"><label for="cliente_telefono">' . esc_html__('Teléfono', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="text" id="cliente_telefono" name="telefono" class="regular-text" value="' . esc_attr((string) $form_data['telefono']) . '" /></td></tr>';

        echo '<tr><th scope="row"><label for="cliente_empresa">' . esc_html__('Empresa', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="text" id="cliente_empresa" name="empresa" class="regular-text" value="' . esc_attr((string) $form_data['empresa']) . '" /></td></tr>';

        echo '<tr><th scope="row"><label for="cliente_cargo">' . esc_html__('Cargo', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="text" id="cliente_cargo" name="cargo" class="regular-text" value="' . esc_attr((string) $form_data['cargo']) . '" /></td></tr>';

        echo '<tr><th scope="row"><label for="cliente_direccion">' . esc_html__('Dirección', 'flavor-chat-ia') . '</label></th>';
        echo '<td><textarea id="cliente_direccion" name="direccion" class="large-text" rows="3">' . esc_textarea((string) $form_data['direccion']) . '</textarea></td></tr>';

        echo '<tr><th scope="row"><label for="cliente_tipo">' . esc_html__('Tipo', 'flavor-chat-ia') . '</label></th>';
        echo '<td><select id="cliente_tipo" name="tipo">';
        foreach ((array) $settings['tipos_cliente'] as $tipo) {
            echo '<option value="' . esc_attr((string) $tipo) . '"' . selected($form_data['tipo'], $tipo, false) . '>' . esc_html(ucfirst((string) $tipo)) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row"><label for="cliente_estado">' . esc_html__('Estado', 'flavor-chat-ia') . '</label></th>';
        echo '<td><select id="cliente_estado" name="estado">';
        foreach ((array) $settings['estados_cliente'] as $estado) {
            echo '<option value="' . esc_attr((string) $estado) . '"' . selected($form_data['estado'], $estado, false) . '>' . esc_html(ucfirst((string) $estado)) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row"><label for="cliente_origen">' . esc_html__('Origen', 'flavor-chat-ia') . '</label></th>';
        echo '<td><select id="cliente_origen" name="origen">';
        foreach ((array) $settings['origenes_cliente'] as $origen) {
            echo '<option value="' . esc_attr((string) $origen) . '"' . selected($form_data['origen'], $origen, false) . '>' . esc_html(ucfirst((string) $origen)) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row"><label for="cliente_valor_estimado">' . esc_html__('Valor estimado', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" id="cliente_valor_estimado" name="valor_estimado" min="0" step="0.01" class="regular-text" value="' . esc_attr((string) $form_data['valor_estimado']) . '" /></td></tr>';

        echo '<tr><th scope="row"><label for="cliente_etiquetas">' . esc_html__('Etiquetas', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="text" id="cliente_etiquetas" name="etiquetas" class="regular-text" value="' . esc_attr((string) $form_data['etiquetas']) . '" />';
        echo '<p class="description">' . esc_html__('Separadas por comas. Ejemplo: vip, lead, ecommerce', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label for="cliente_asignado_a">' . esc_html__('Asignado a', 'flavor-chat-ia') . '</label></th>';
        echo '<td><select id="cliente_asignado_a" name="asignado_a">';
        echo '<option value="0">' . esc_html__('Sin asignar', 'flavor-chat-ia') . '</option>';
        foreach ($usuarios as $usuario) {
            echo '<option value="' . esc_attr((string) $usuario->ID) . '"' . selected((int) $form_data['asignado_a'], (int) $usuario->ID, false) . '>' . esc_html($usuario->display_name) . '</option>';
        }
        echo '</select></td></tr>';

        echo '</tbody></table>';

        echo '<p class="submit">';
        echo '<button type="submit" name="flavor_cliente_guardar" value="1" class="button button-primary">' . esc_html($modo_edicion ? __('Guardar cambios', 'flavor-chat-ia') : __('Crear cliente', 'flavor-chat-ia')) . '</button> ';
        echo '<a href="' . esc_url(admin_url('admin.php?page=clientes-listado')) . '" class="button">' . esc_html__('Volver al listado', 'flavor-chat-ia') . '</a>';
        echo '</p>';
        echo '</form>';
    }

    /**
     * Exporta clientes en CSV desde la vista admin.
     *
     * @return void
     */
    private function exportar_clientes_csv_admin() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para exportar clientes.', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        $estado_filtro = isset($_GET['estado']) ? sanitize_text_field(wp_unslash((string) $_GET['estado'])) : '';
        $tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field(wp_unslash((string) $_GET['tipo'])) : '';

        $where = ['1=1'];
        $params = [];

        if ($estado_filtro !== '') {
            $where[] = 'estado = %s';
            $params[] = $estado_filtro;
        }
        if ($tipo_filtro !== '') {
            $where[] = 'tipo = %s';
            $params[] = $tipo_filtro;
        }

        $sql = "SELECT id, nombre, email, telefono, empresa, cargo, tipo, estado, valor_estimado, origen, created_at, updated_at
                FROM {$tabla_clientes}
                WHERE " . implode(' AND ', $where) . '
                ORDER BY updated_at DESC';

        $rows = !empty($params)
            ? $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A)
            : $wpdb->get_results($sql, ARRAY_A);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=clientes-' . gmdate('Y-m-d-His') . '.csv');

        $output = fopen('php://output', 'w');
        if (!$output) {
            wp_die(esc_html__('No se pudo generar el archivo CSV.', 'flavor-chat-ia'));
        }

        fputcsv($output, ['id', 'nombre', 'email', 'telefono', 'empresa', 'cargo', 'tipo', 'estado', 'valor_estimado', 'origen', 'created_at', 'updated_at']);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    /**
     * Renderiza la pagina de fichas de clientes
     */
    public function render_admin_fichas() {
        echo '<div class="wrap flavor-modulo-page">';

        $cliente_id = isset($_GET['cliente_id']) ? absint($_GET['cliente_id']) : 0;

        if ($cliente_id > 0) {
            // Mostrar ficha individual
            $resultado = $this->action_ver_cliente(['cliente_id' => $cliente_id]);

            if ($resultado['success'] && !empty($resultado['cliente'])) {
                $cliente = $resultado['cliente'];

                $this->render_page_header(
                    sprintf(__('Ficha: %s', 'flavor-chat-ia'), $cliente['nombre']),
                    [
                        ['label' => __('Editar', 'flavor-chat-ia'), 'url' => '#', 'class' => 'button-primary'],
                        ['label' => __('Nueva Factura', 'flavor-chat-ia'), 'url' => add_query_arg([
                            'page' => 'facturas-nueva',
                            'cliente_id' => absint($cliente['id']),
                            'cliente_tipo' => 'crm_cliente',
                            'cliente_nombre' => (string) $cliente['nombre'],
                            'cliente_email' => (string) $cliente['email'],
                        ], admin_url('admin.php')), 'class' => 'button'],
                        ['label' => __('Volver al listado', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=clientes-listado'), 'class' => 'button'],
                    ]
                );

                echo '<div class="flavor-ficha-cliente">';

                // Informacion principal
                echo '<div class="flavor-ficha-section">';
                echo '<h3>' . __('Informacion de Contacto', 'flavor-chat-ia') . '</h3>';
                echo '<table class="form-table">';
                echo '<tr><th>' . __('Email', 'flavor-chat-ia') . '</th><td>' . esc_html($cliente['email']) . '</td></tr>';
                echo '<tr><th>' . __('Telefono', 'flavor-chat-ia') . '</th><td>' . esc_html($cliente['telefono']) . '</td></tr>';
                echo '<tr><th>' . __('Empresa', 'flavor-chat-ia') . '</th><td>' . esc_html($cliente['empresa']) . '</td></tr>';
                echo '<tr><th>' . __('Cargo', 'flavor-chat-ia') . '</th><td>' . esc_html($cliente['cargo']) . '</td></tr>';
                echo '<tr><th>' . __('Direccion', 'flavor-chat-ia') . '</th><td>' . esc_html($cliente['direccion']) . '</td></tr>';
                echo '</table>';
                echo '</div>';

                // Estado y clasificacion
                echo '<div class="flavor-ficha-section">';
                echo '<h3>' . __('Clasificacion', 'flavor-chat-ia') . '</h3>';
                echo '<table class="form-table">';
                echo '<tr><th>' . __('Tipo', 'flavor-chat-ia') . '</th><td>' . esc_html(ucfirst($cliente['tipo'])) . '</td></tr>';
                echo '<tr><th>' . __('Estado', 'flavor-chat-ia') . '</th><td>' . esc_html(ucfirst($cliente['estado'])) . '</td></tr>';
                echo '<tr><th>' . __('Origen', 'flavor-chat-ia') . '</th><td>' . esc_html(ucfirst($cliente['origen'])) . '</td></tr>';
                echo '<tr><th>' . __('Valor Estimado', 'flavor-chat-ia') . '</th><td>' . esc_html($this->format_price($cliente['valor_estimado'])) . '</td></tr>';
                echo '</table>';
                echo '</div>';

                // Notas recientes
                if (!empty($cliente['notas'])) {
                    echo '<div class="flavor-ficha-section">';
                    echo '<h3>' . __('Notas e Interacciones', 'flavor-chat-ia') . '</h3>';
                    echo '<ul class="flavor-notas-list">';
                    foreach ($cliente['notas'] as $nota) {
                        echo '<li>';
                        echo '<strong>' . esc_html(ucfirst($nota['tipo'])) . '</strong> - ';
                        echo '<span class="fecha">' . esc_html($nota['created_at']) . '</span>';
                        echo '<p>' . esc_html($nota['contenido']) . '</p>';
                        echo '<small>' . __('Por:', 'flavor-chat-ia') . ' ' . esc_html($nota['autor']) . '</small>';
                        echo '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }

                echo '</div>';
            } else {
                echo '<p>' . __('Cliente no encontrado.', 'flavor-chat-ia') . '</p>';
            }
        } else {
            // Listado de fichas
            $this->render_page_header(__('Fichas de Clientes', 'flavor-chat-ia'));
            echo '<p>' . __('Selecciona un cliente del listado para ver su ficha completa.', 'flavor-chat-ia') . '</p>';
            echo '<p><a href="' . esc_url(admin_url('admin.php?page=clientes-listado')) . '" class="button">' . __('Ir al Listado de Clientes', 'flavor-chat-ia') . '</a></p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la pagina de configuracion del modulo
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuracion de Clientes', 'flavor-chat-ia'));

        $configuracion_actual = $this->get_default_settings();

        echo '<form method="post" action="">';
        echo '<table class="form-table">';

        echo '<tr><th scope="row"><label for="limite_resultados_por_defecto">' . __('Limite de resultados por defecto', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" name="limite_resultados_por_defecto" id="limite_resultados_por_defecto" value="' . esc_attr($configuracion_actual['limite_resultados_por_defecto']) . '" min="5" max="100" class="small-text" />';
        echo '<p class="description">' . __('Numero de clientes a mostrar por pagina.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label>' . __('Tipos de Cliente', 'flavor-chat-ia') . '</label></th>';
        echo '<td><code>' . esc_html(implode(', ', $configuracion_actual['tipos_cliente'])) . '</code>';
        echo '<p class="description">' . __('Tipos disponibles para clasificar clientes.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label>' . __('Estados de Cliente', 'flavor-chat-ia') . '</label></th>';
        echo '<td><code>' . esc_html(implode(', ', $configuracion_actual['estados_cliente'])) . '</code>';
        echo '<p class="description">' . __('Estados del pipeline de ventas.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label>' . __('Origenes de Cliente', 'flavor-chat-ia') . '</label></th>';
        echo '<td><code>' . esc_html(implode(', ', $configuracion_actual['origenes_cliente'])) . '</code>';
        echo '<p class="description">' . __('Como llegaron los clientes.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '<tr><th scope="row"><label>' . __('Tipos de Nota', 'flavor-chat-ia') . '</label></th>';
        echo '<td><code>' . esc_html(implode(', ', $configuracion_actual['tipos_nota'])) . '</code>';
        echo '<p class="description">' . __('Tipos de interacciones registrables.', 'flavor-chat-ia') . '</p></td></tr>';

        echo '</table>';
        echo '<p class="submit"><input type="submit" name="guardar_config" class="button-primary" value="' . __('Guardar Configuracion', 'flavor-chat-ia') . '" /></p>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Alias legacy para "clientes-nuevo".
     *
     * @return void
     */
    public function render_admin_nuevo_alias() {
        wp_safe_redirect(admin_url('admin.php?page=clientes-listado&action=nuevo'));
        exit;
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_clientes)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias para el modulo
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
        $tabla_notas = $wpdb->prefix . 'flavor_clientes_notas';

        $sql_clientes = "CREATE TABLE IF NOT EXISTS $tabla_clientes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(200) NOT NULL,
            email varchar(200) DEFAULT NULL,
            telefono varchar(50) DEFAULT NULL,
            empresa varchar(200) DEFAULT NULL,
            cargo varchar(200) DEFAULT NULL,
            direccion text DEFAULT NULL,
            tipo enum('particular','empresa','autonomo','administracion') DEFAULT 'particular',
            estado enum('activo','inactivo','potencial','perdido') DEFAULT 'potencial',
            etiquetas text DEFAULT NULL,
            valor_estimado decimal(10,2) DEFAULT 0.00,
            origen varchar(100) DEFAULT 'directo',
            asignado_a bigint(20) unsigned DEFAULT NULL,
            notas_count int(11) DEFAULT 0,
            ultima_interaccion datetime DEFAULT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_email (email),
            KEY idx_tipo (tipo),
            KEY idx_estado (estado),
            KEY idx_asignado_a (asignado_a),
            KEY idx_created_by (created_by),
            KEY idx_origen (origen)
        ) $charset_collate;";

        $sql_notas = "CREATE TABLE IF NOT EXISTS $tabla_notas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cliente_id bigint(20) unsigned NOT NULL,
            autor_id bigint(20) unsigned DEFAULT NULL,
            tipo enum('nota','llamada','email','reunion','tarea','seguimiento') DEFAULT 'nota',
            contenido text NOT NULL,
            estado enum('pendiente','completada','cancelada') DEFAULT 'completada',
            fecha_seguimiento datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_cliente_id (cliente_id),
            KEY idx_autor_id (autor_id),
            KEY idx_tipo (tipo),
            KEY idx_estado (estado),
            KEY idx_fecha_seguimiento (fecha_seguimiento)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_clientes);
        dbDelta($sql_notas);
    }

    // =====================================================================
    // ACCIONES (get_actions / execute_action)
    // =====================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_clientes' => [
                'description' => 'Listar clientes con filtros opcionales por estado, tipo y etiquetas',
                'params' => ['estado', 'tipo', 'etiquetas', 'limite', 'pagina'],
            ],
            'ver_cliente' => [
                'description' => 'Ver detalle de un cliente con sus notas recientes',
                'params' => ['cliente_id'],
            ],
            'crear_cliente' => [
                'description' => 'Crear un nuevo cliente en el CRM',
                'params' => ['nombre', 'email', 'telefono', 'empresa', 'cargo', 'direccion', 'tipo', 'estado', 'etiquetas', 'valor_estimado', 'origen', 'asignado_a'],
            ],
            'actualizar_cliente' => [
                'description' => 'Actualizar informacion de un cliente existente',
                'params' => ['cliente_id', 'nombre', 'email', 'telefono', 'empresa', 'cargo', 'direccion', 'tipo', 'estado', 'etiquetas', 'valor_estimado', 'origen', 'asignado_a'],
            ],
            'agregar_nota' => [
                'description' => 'Agregar una nota o interaccion a un cliente',
                'params' => ['cliente_id', 'tipo', 'contenido', 'estado', 'fecha_seguimiento'],
            ],
            'buscar_clientes' => [
                'description' => 'Buscar clientes por nombre, email o empresa',
                'params' => ['busqueda', 'limite'],
            ],
            'estadisticas' => [
                'description' => 'Obtener estadisticas del CRM: totales, por tipo, por estado, pipeline de valor',
                'params' => [],
            ],
            'clientes_por_estado' => [
                'description' => 'Agrupar clientes por estado con conteos',
                'params' => [],
            ],
            'mis_clientes' => [
                'description' => 'Obtener los clientes asignados al usuario actual',
                'params' => ['estado', 'limite'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($nombre_accion, $parametros) {
        $aliases = [
            'listar' => 'listar_clientes',
            'listado' => 'listar_clientes',
            'buscar' => 'buscar_clientes',
            'crear' => 'crear_cliente',
            'nuevo' => 'crear_cliente',
            'detalle' => 'ver_cliente',
            'ver' => 'ver_cliente',
            'editar' => 'actualizar_cliente',
            'actualizar' => 'actualizar_cliente',
            'nota' => 'agregar_nota',
            'agregar_nota' => 'agregar_nota',
            'mis_items' => 'mis_clientes',
            'mis-clientes' => 'mis_clientes',
            'stats' => 'estadisticas',
        ];

        $nombre_accion = $aliases[$nombre_accion] ?? $nombre_accion;
        $metodo_accion = 'action_' . $nombre_accion;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($parametros);
        }

        return [
            'success' => false,
            'error' => sprintf(__('Accion no implementada: %s', 'flavor-chat-ia'), $nombre_accion),
        ];
    }

    // =====================================================================
    // IMPLEMENTACION DE ACCIONES
    // =====================================================================

    /**
     * Accion: Listar clientes con filtros
     */
    private function action_listar_clientes($parametros) {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        $estado_filtro = sanitize_text_field($parametros['estado'] ?? '');
        $tipo_filtro = sanitize_text_field($parametros['tipo'] ?? '');
        $etiquetas_filtro = $parametros['etiquetas'] ?? '';
        $limite = absint($parametros['limite'] ?? $this->get_setting('limite_resultados_por_defecto', 20));
        $pagina = absint($parametros['pagina'] ?? 1);
        $offset_valor = ($pagina - 1) * $limite;

        $condiciones_where = ['1=1'];
        $valores_preparar = [];

        if (!empty($estado_filtro)) {
            $condiciones_where[] = 'estado = %s';
            $valores_preparar[] = $estado_filtro;
        }

        if (!empty($tipo_filtro)) {
            $condiciones_where[] = 'tipo = %s';
            $valores_preparar[] = $tipo_filtro;
        }

        if (!empty($etiquetas_filtro)) {
            $etiqueta_buscar = sanitize_text_field($etiquetas_filtro);
            $condiciones_where[] = 'etiquetas LIKE %s';
            $valores_preparar[] = '%' . $wpdb->esc_like($etiqueta_buscar) . '%';
        }

        $sql_where = implode(' AND ', $condiciones_where);

        // Contar total
        $sql_count = "SELECT COUNT(*) FROM $tabla_clientes WHERE $sql_where";
        if (!empty($valores_preparar)) {
            $total_clientes = (int) $wpdb->get_var($wpdb->prepare($sql_count, ...$valores_preparar));
        } else {
            $total_clientes = (int) $wpdb->get_var($sql_count);
        }

        // Obtener resultados
        $sql_select = "SELECT * FROM $tabla_clientes WHERE $sql_where ORDER BY updated_at DESC LIMIT %d OFFSET %d";
        $valores_preparar[] = $limite;
        $valores_preparar[] = $offset_valor;

        $clientes_encontrados = $wpdb->get_results($wpdb->prepare($sql_select, ...$valores_preparar));

        $clientes_formateados = array_map(function ($cliente) {
            return $this->formatear_cliente($cliente);
        }, $clientes_encontrados);

        return [
            'success' => true,
            'total' => $total_clientes,
            'pagina' => $pagina,
            'limite' => $limite,
            'total_paginas' => ceil($total_clientes / $limite),
            'clientes' => $clientes_formateados,
            'mensaje' => sprintf(
                __('Se encontraron %d clientes.', 'flavor-chat-ia'),
                $total_clientes
            ),
        ];
    }

    /**
     * Accion: Ver detalle de un cliente
     */
    private function action_ver_cliente($parametros) {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
        $tabla_notas = $wpdb->prefix . 'flavor_clientes_notas';

        $cliente_id = absint($parametros['cliente_id'] ?? 0);

        if (!$cliente_id) {
            return [
                'success' => false,
                'error' => __('ID de cliente no valido.', 'flavor-chat-ia'),
            ];
        }

        $cliente_registro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_clientes WHERE id = %d",
            $cliente_id
        ));

        if (!$cliente_registro) {
            return [
                'success' => false,
                'error' => __('Cliente no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Obtener notas recientes
        $notas_recientes = $wpdb->get_results($wpdb->prepare(
            "SELECT n.*, u.display_name as autor_nombre
             FROM $tabla_notas n
             LEFT JOIN {$wpdb->users} u ON n.autor_id = u.ID
             WHERE n.cliente_id = %d
             ORDER BY n.created_at DESC
             LIMIT 10",
            $cliente_id
        ));

        $notas_formateadas = array_map(function ($nota) {
            return [
                'id' => (int) $nota->id,
                'tipo' => $nota->tipo,
                'contenido' => $nota->contenido,
                'estado' => $nota->estado,
                'fecha_seguimiento' => $nota->fecha_seguimiento,
                'autor' => $nota->autor_nombre ?? __('Sistema', 'flavor-chat-ia'),
                'created_at' => $nota->created_at,
            ];
        }, $notas_recientes);

        $cliente_detalle = $this->formatear_cliente($cliente_registro);
        $cliente_detalle['notas'] = $notas_formateadas;

        return [
            'success' => true,
            'cliente' => $cliente_detalle,
        ];
    }

    /**
     * Accion: Crear un nuevo cliente
     */
    private function action_crear_cliente($parametros) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesion para crear clientes.', 'flavor-chat-ia'),
            ];
        }

        $nombre_cliente = sanitize_text_field($parametros['nombre'] ?? '');

        if (empty($nombre_cliente)) {
            return [
                'success' => false,
                'error' => __('El nombre del cliente es obligatorio.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        $etiquetas_json = null;
        if (!empty($parametros['etiquetas'])) {
            $etiquetas_valor = $parametros['etiquetas'];
            if (is_array($etiquetas_valor)) {
                $etiquetas_json = wp_json_encode(array_map('sanitize_text_field', $etiquetas_valor));
            } else {
                $etiquetas_json = wp_json_encode(array_map('trim', explode(',', sanitize_text_field($etiquetas_valor))));
            }
        }

        $datos_insertar = [
            'nombre' => $nombre_cliente,
            'email' => sanitize_email($parametros['email'] ?? ''),
            'telefono' => sanitize_text_field($parametros['telefono'] ?? ''),
            'empresa' => sanitize_text_field($parametros['empresa'] ?? ''),
            'cargo' => sanitize_text_field($parametros['cargo'] ?? ''),
            'direccion' => sanitize_textarea_field($parametros['direccion'] ?? ''),
            'tipo' => sanitize_text_field($parametros['tipo'] ?? 'particular'),
            'estado' => sanitize_text_field($parametros['estado'] ?? 'potencial'),
            'etiquetas' => $etiquetas_json,
            'valor_estimado' => floatval($parametros['valor_estimado'] ?? 0),
            'origen' => sanitize_text_field($parametros['origen'] ?? 'directo'),
            'asignado_a' => absint($parametros['asignado_a'] ?? 0) ?: null,
            'notas_count' => 0,
            'ultima_interaccion' => current_time('mysql'),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $formatos_columnas = [
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%d', '%s', '%d', '%s', '%s',
        ];

        // Si asignado_a es null, ajustar
        if ($datos_insertar['asignado_a'] === null) {
            $datos_insertar['asignado_a'] = 0;
        }

        $resultado_insercion = $wpdb->insert($tabla_clientes, $datos_insertar, $formatos_columnas);

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error' => __('Error al crear el cliente.', 'flavor-chat-ia'),
            ];
        }

        $nuevo_cliente_id = $wpdb->insert_id;

        do_action('flavor_cliente_creado', $nuevo_cliente_id, [
            'nombre' => $nombre_cliente,
            'email' => sanitize_email($parametros['email'] ?? ''),
            'telefono' => sanitize_text_field($parametros['telefono'] ?? ''),
            'empresa' => sanitize_text_field($parametros['empresa'] ?? ''),
            'origen' => sanitize_text_field($parametros['origen'] ?? 'directo'),
            'created_by' => get_current_user_id(),
        ]);

        return [
            'success' => true,
            'cliente_id' => $nuevo_cliente_id,
            'mensaje' => sprintf(
                __('Cliente "%s" creado correctamente con ID %d.', 'flavor-chat-ia'),
                $nombre_cliente,
                $nuevo_cliente_id
            ),
        ];
    }

    /**
     * Accion: Actualizar un cliente existente
     */
    private function action_actualizar_cliente($parametros) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesion para actualizar clientes.', 'flavor-chat-ia'),
            ];
        }

        $cliente_id = absint($parametros['cliente_id'] ?? 0);

        if (!$cliente_id) {
            return [
                'success' => false,
                'error' => __('ID de cliente no valido.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        // Verificar que el cliente existe
        $cliente_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_clientes WHERE id = %d",
            $cliente_id
        ));

        if (!$cliente_existente) {
            return [
                'success' => false,
                'error' => __('Cliente no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $campos_actualizables = [
            'nombre', 'email', 'telefono', 'empresa', 'cargo',
            'direccion', 'tipo', 'estado', 'valor_estimado', 'origen', 'asignado_a',
        ];

        $datos_actualizar = [];
        $formatos_actualizar = [];

        foreach ($campos_actualizables as $campo_nombre) {
            if (isset($parametros[$campo_nombre])) {
                if ($campo_nombre === 'email') {
                    $datos_actualizar[$campo_nombre] = sanitize_email($parametros[$campo_nombre]);
                    $formatos_actualizar[] = '%s';
                } elseif ($campo_nombre === 'valor_estimado') {
                    $datos_actualizar[$campo_nombre] = floatval($parametros[$campo_nombre]);
                    $formatos_actualizar[] = '%f';
                } elseif ($campo_nombre === 'asignado_a') {
                    $datos_actualizar[$campo_nombre] = absint($parametros[$campo_nombre]);
                    $formatos_actualizar[] = '%d';
                } elseif ($campo_nombre === 'direccion') {
                    $datos_actualizar[$campo_nombre] = sanitize_textarea_field($parametros[$campo_nombre]);
                    $formatos_actualizar[] = '%s';
                } else {
                    $datos_actualizar[$campo_nombre] = sanitize_text_field($parametros[$campo_nombre]);
                    $formatos_actualizar[] = '%s';
                }
            }
        }

        // Manejar etiquetas por separado
        if (isset($parametros['etiquetas'])) {
            $etiquetas_valor = $parametros['etiquetas'];
            if (is_array($etiquetas_valor)) {
                $datos_actualizar['etiquetas'] = wp_json_encode(array_map('sanitize_text_field', $etiquetas_valor));
            } else {
                $datos_actualizar['etiquetas'] = wp_json_encode(array_map('trim', explode(',', sanitize_text_field($etiquetas_valor))));
            }
            $formatos_actualizar[] = '%s';
        }

        if (empty($datos_actualizar)) {
            return [
                'success' => false,
                'error' => __('No se proporcionaron datos para actualizar.', 'flavor-chat-ia'),
            ];
        }

        $datos_actualizar['updated_at'] = current_time('mysql');
        $formatos_actualizar[] = '%s';

        $resultado_actualizacion = $wpdb->update(
            $tabla_clientes,
            $datos_actualizar,
            ['id' => $cliente_id],
            $formatos_actualizar,
            ['%d']
        );

        if ($resultado_actualizacion === false) {
            return [
                'success' => false,
                'error' => __('Error al actualizar el cliente.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'cliente_id' => $cliente_id,
            'campos_actualizados' => array_keys($datos_actualizar),
            'mensaje' => sprintf(
                __('Cliente #%d actualizado correctamente.', 'flavor-chat-ia'),
                $cliente_id
            ),
        ];
    }

    /**
     * Accion: Agregar nota/interaccion a un cliente
     */
    private function action_agregar_nota($parametros) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesion para agregar notas.', 'flavor-chat-ia'),
            ];
        }

        $cliente_id = absint($parametros['cliente_id'] ?? 0);
        $contenido_nota = sanitize_textarea_field($parametros['contenido'] ?? '');

        if (!$cliente_id) {
            return [
                'success' => false,
                'error' => __('ID de cliente no valido.', 'flavor-chat-ia'),
            ];
        }

        if (empty($contenido_nota)) {
            return [
                'success' => false,
                'error' => __('El contenido de la nota es obligatorio.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
        $tabla_notas = $wpdb->prefix . 'flavor_clientes_notas';

        // Verificar que el cliente existe
        $cliente_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_clientes WHERE id = %d",
            $cliente_id
        ));

        if (!$cliente_existente) {
            return [
                'success' => false,
                'error' => __('Cliente no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $tipo_nota = sanitize_text_field($parametros['tipo'] ?? 'nota');
        $estado_nota = sanitize_text_field($parametros['estado'] ?? 'completada');
        $fecha_seguimiento_nota = null;

        if (!empty($parametros['fecha_seguimiento'])) {
            $fecha_seguimiento_nota = sanitize_text_field($parametros['fecha_seguimiento']);
        }

        $datos_nota = [
            'cliente_id' => $cliente_id,
            'autor_id' => get_current_user_id(),
            'tipo' => $tipo_nota,
            'contenido' => $contenido_nota,
            'estado' => $estado_nota,
            'fecha_seguimiento' => $fecha_seguimiento_nota,
            'created_at' => current_time('mysql'),
        ];

        $formatos_nota = ['%d', '%d', '%s', '%s', '%s', '%s', '%s'];

        $resultado_nota = $wpdb->insert($tabla_notas, $datos_nota, $formatos_nota);

        if ($resultado_nota === false) {
            return [
                'success' => false,
                'error' => __('Error al agregar la nota.', 'flavor-chat-ia'),
            ];
        }

        // Actualizar conteo de notas y ultima interaccion en el cliente
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_clientes
             SET notas_count = notas_count + 1,
                 ultima_interaccion = %s,
                 updated_at = %s
             WHERE id = %d",
            current_time('mysql'),
            current_time('mysql'),
            $cliente_id
        ));

        $etiquetas_tipo = [
            'nota' => __('Nota', 'flavor-chat-ia'),
            'llamada' => __('Llamada', 'flavor-chat-ia'),
            'email' => __('Email', 'flavor-chat-ia'),
            'reunion' => __('Reunion', 'flavor-chat-ia'),
            'tarea' => __('Tarea', 'flavor-chat-ia'),
            'seguimiento' => __('Seguimiento', 'flavor-chat-ia'),
        ];

        $tipo_legible = $etiquetas_tipo[$tipo_nota] ?? $tipo_nota;

        return [
            'success' => true,
            'nota_id' => $wpdb->insert_id,
            'cliente_id' => $cliente_id,
            'mensaje' => sprintf(
                __('%s agregada al cliente #%d correctamente.', 'flavor-chat-ia'),
                $tipo_legible,
                $cliente_id
            ),
        ];
    }

    /**
     * Accion: Buscar clientes por nombre, email o empresa
     */
    private function action_buscar_clientes($parametros) {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        $termino_busqueda = sanitize_text_field($parametros['busqueda'] ?? '');
        $limite = absint($parametros['limite'] ?? 10);

        if (empty($termino_busqueda)) {
            return [
                'success' => false,
                'error' => __('El termino de busqueda es obligatorio.', 'flavor-chat-ia'),
            ];
        }

        $termino_like = '%' . $wpdb->esc_like($termino_busqueda) . '%';

        $clientes_encontrados = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_clientes
             WHERE nombre LIKE %s
                OR email LIKE %s
                OR empresa LIKE %s
                OR telefono LIKE %s
             ORDER BY nombre ASC
             LIMIT %d",
            $termino_like,
            $termino_like,
            $termino_like,
            $termino_like,
            $limite
        ));

        $clientes_formateados = array_map(function ($cliente) {
            return $this->formatear_cliente($cliente);
        }, $clientes_encontrados);

        return [
            'success' => true,
            'total' => count($clientes_formateados),
            'busqueda' => $termino_busqueda,
            'clientes' => $clientes_formateados,
            'mensaje' => sprintf(
                __('Se encontraron %d clientes para "%s".', 'flavor-chat-ia'),
                count($clientes_formateados),
                $termino_busqueda
            ),
        ];
    }

    /**
     * Accion: Estadisticas del CRM
     */
    private function action_estadisticas($parametros) {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        // Total de clientes
        $total_clientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_clientes");

        // Por tipo
        $clientes_por_tipo = $wpdb->get_results(
            "SELECT tipo, COUNT(*) as cantidad FROM $tabla_clientes GROUP BY tipo ORDER BY cantidad DESC"
        );

        $desglose_por_tipo = [];
        foreach ($clientes_por_tipo as $tipo_registro) {
            $desglose_por_tipo[$tipo_registro->tipo] = (int) $tipo_registro->cantidad;
        }

        // Por estado
        $clientes_por_estado = $wpdb->get_results(
            "SELECT estado, COUNT(*) as cantidad FROM $tabla_clientes GROUP BY estado ORDER BY cantidad DESC"
        );

        $desglose_por_estado = [];
        foreach ($clientes_por_estado as $estado_registro) {
            $desglose_por_estado[$estado_registro->estado] = (int) $estado_registro->cantidad;
        }

        // Pipeline de valor (valor estimado por estado)
        $pipeline_valor = $wpdb->get_results(
            "SELECT estado, SUM(valor_estimado) as valor_total, COUNT(*) as cantidad
             FROM $tabla_clientes
             WHERE valor_estimado > 0
             GROUP BY estado
             ORDER BY valor_total DESC"
        );

        $desglose_pipeline = [];
        foreach ($pipeline_valor as $pipeline_registro) {
            $desglose_pipeline[$pipeline_registro->estado] = [
                'valor_total' => floatval($pipeline_registro->valor_total),
                'cantidad' => (int) $pipeline_registro->cantidad,
            ];
        }

        // Valor total del pipeline
        $valor_total_pipeline = (float) $wpdb->get_var(
            "SELECT IFNULL(SUM(valor_estimado), 0) FROM $tabla_clientes WHERE valor_estimado > 0"
        );

        // Por origen
        $clientes_por_origen = $wpdb->get_results(
            "SELECT origen, COUNT(*) as cantidad FROM $tabla_clientes GROUP BY origen ORDER BY cantidad DESC"
        );

        $desglose_por_origen = [];
        foreach ($clientes_por_origen as $origen_registro) {
            $desglose_por_origen[$origen_registro->origen] = (int) $origen_registro->cantidad;
        }

        // Nuevos este mes
        $nuevos_este_mes = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_clientes
             WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );

        return [
            'success' => true,
            'estadisticas' => [
                'total_clientes' => $total_clientes,
                'nuevos_este_mes' => $nuevos_este_mes,
                'por_tipo' => $desglose_por_tipo,
                'por_estado' => $desglose_por_estado,
                'por_origen' => $desglose_por_origen,
                'pipeline' => [
                    'valor_total' => $valor_total_pipeline,
                    'por_estado' => $desglose_pipeline,
                ],
            ],
            'mensaje' => sprintf(
                __('CRM: %d clientes totales, %d nuevos este mes, pipeline de %s.', 'flavor-chat-ia'),
                $total_clientes,
                $nuevos_este_mes,
                $this->format_price($valor_total_pipeline)
            ),
        ];
    }

    /**
     * Accion: Agrupar clientes por estado
     */
    private function action_clientes_por_estado($parametros) {
        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        $resultados_agrupados = $wpdb->get_results(
            "SELECT estado, COUNT(*) as cantidad, SUM(valor_estimado) as valor_total
             FROM $tabla_clientes
             GROUP BY estado
             ORDER BY FIELD(estado, 'activo', 'potencial', 'inactivo', 'perdido')"
        );

        $estados_desglose = [];
        foreach ($resultados_agrupados as $estado_registro) {
            $estados_desglose[] = [
                'estado' => $estado_registro->estado,
                'cantidad' => (int) $estado_registro->cantidad,
                'valor_total' => floatval($estado_registro->valor_total),
            ];
        }

        return [
            'success' => true,
            'estados' => $estados_desglose,
            'mensaje' => __('Desglose de clientes por estado obtenido correctamente.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Accion: Obtener clientes asignados al usuario actual
     */
    private function action_mis_clientes($parametros) {
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesion para ver tus clientes.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

        $estado_filtro = sanitize_text_field($parametros['estado'] ?? '');
        $limite = absint($parametros['limite'] ?? 20);

        $condiciones_where = ['asignado_a = %d'];
        $valores_preparar = [$usuario_actual_id];

        if (!empty($estado_filtro)) {
            $condiciones_where[] = 'estado = %s';
            $valores_preparar[] = $estado_filtro;
        }

        $sql_where = implode(' AND ', $condiciones_where);
        $valores_preparar[] = $limite;

        $mis_clientes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_clientes WHERE $sql_where ORDER BY ultima_interaccion DESC LIMIT %d",
            ...$valores_preparar
        ));

        $clientes_formateados = array_map(function ($cliente) {
            return $this->formatear_cliente($cliente);
        }, $mis_clientes);

        return [
            'success' => true,
            'total' => count($clientes_formateados),
            'clientes' => $clientes_formateados,
            'mensaje' => sprintf(
                __('Tienes %d clientes asignados.', 'flavor-chat-ia'),
                count($clientes_formateados)
            ),
        ];
    }

    // =====================================================================
    // HELPERS
    // =====================================================================

    /**
     * Formatea un registro de cliente para la respuesta
     *
     * @param object $cliente_registro Registro de la base de datos
     * @return array
     */
    private function formatear_cliente($cliente_registro) {
        $usuario_asignado = null;
        if (!empty($cliente_registro->asignado_a)) {
            $datos_usuario = get_userdata($cliente_registro->asignado_a);
            $usuario_asignado = $datos_usuario ? $datos_usuario->display_name : null;
        }

        $usuario_creador = null;
        if (!empty($cliente_registro->created_by)) {
            $datos_creador = get_userdata($cliente_registro->created_by);
            $usuario_creador = $datos_creador ? $datos_creador->display_name : null;
        }

        $etiquetas_decodificadas = [];
        if (!empty($cliente_registro->etiquetas)) {
            $etiquetas_decodificadas = json_decode($cliente_registro->etiquetas, true);
            if (!is_array($etiquetas_decodificadas)) {
                $etiquetas_decodificadas = [];
            }
        }

        return [
            'id' => (int) $cliente_registro->id,
            'nombre' => $cliente_registro->nombre,
            'email' => $cliente_registro->email,
            'telefono' => $cliente_registro->telefono,
            'empresa' => $cliente_registro->empresa,
            'cargo' => $cliente_registro->cargo,
            'direccion' => $cliente_registro->direccion,
            'tipo' => $cliente_registro->tipo,
            'estado' => $cliente_registro->estado,
            'etiquetas' => $etiquetas_decodificadas,
            'valor_estimado' => floatval($cliente_registro->valor_estimado),
            'origen' => $cliente_registro->origen,
            'asignado_a' => [
                'id' => (int) $cliente_registro->asignado_a,
                'nombre' => $usuario_asignado,
            ],
            'notas_count' => (int) $cliente_registro->notas_count,
            'ultima_interaccion' => $cliente_registro->ultima_interaccion,
            'created_by' => [
                'id' => (int) $cliente_registro->created_by,
                'nombre' => $usuario_creador,
            ],
            'created_at' => $cliente_registro->created_at,
            'updated_at' => $cliente_registro->updated_at,
        ];
    }

    // =====================================================================
    // AI TOOL DEFINITIONS
    // =====================================================================

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'clientes_listar',
                'description' => 'Lista clientes del CRM con filtros opcionales por estado, tipo o etiquetas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Filtrar por estado del cliente',
                            'enum' => ['activo', 'inactivo', 'potencial', 'perdido'],
                        ],
                        'tipo' => [
                            'type' => 'string',
                            'description' => 'Filtrar por tipo de cliente',
                            'enum' => ['particular', 'empresa', 'autonomo', 'administracion'],
                        ],
                        'etiquetas' => [
                            'type' => 'string',
                            'description' => 'Filtrar por etiqueta (busqueda parcial)',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Numero maximo de resultados (por defecto 20)',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'clientes_buscar',
                'description' => 'Busca clientes por nombre, email, empresa o telefono',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'busqueda' => [
                            'type' => 'string',
                            'description' => 'Termino de busqueda (nombre, email, empresa o telefono)',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Numero maximo de resultados',
                            'default' => 10,
                        ],
                    ],
                    'required' => ['busqueda'],
                ],
            ],
            [
                'name' => 'clientes_crear',
                'description' => 'Crea un nuevo cliente en el CRM',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'nombre' => [
                            'type' => 'string',
                            'description' => 'Nombre completo del cliente',
                        ],
                        'email' => [
                            'type' => 'string',
                            'description' => 'Email del cliente',
                        ],
                        'telefono' => [
                            'type' => 'string',
                            'description' => 'Telefono del cliente',
                        ],
                        'empresa' => [
                            'type' => 'string',
                            'description' => 'Empresa del cliente',
                        ],
                        'cargo' => [
                            'type' => 'string',
                            'description' => 'Cargo del cliente en su empresa',
                        ],
                        'tipo' => [
                            'type' => 'string',
                            'description' => 'Tipo de cliente',
                            'enum' => ['particular', 'empresa', 'autonomo', 'administracion'],
                        ],
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Estado inicial del cliente',
                            'enum' => ['activo', 'inactivo', 'potencial', 'perdido'],
                        ],
                        'origen' => [
                            'type' => 'string',
                            'description' => 'Origen del cliente',
                            'enum' => ['web', 'referido', 'redes', 'directo', 'otro'],
                        ],
                        'valor_estimado' => [
                            'type' => 'number',
                            'description' => 'Valor estimado del cliente en euros',
                        ],
                        'etiquetas' => [
                            'type' => 'string',
                            'description' => 'Etiquetas separadas por comas',
                        ],
                    ],
                    'required' => ['nombre'],
                ],
            ],
            [
                'name' => 'clientes_agregar_nota',
                'description' => 'Agrega una nota o interaccion a un cliente del CRM',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'cliente_id' => [
                            'type' => 'integer',
                            'description' => 'ID del cliente',
                        ],
                        'contenido' => [
                            'type' => 'string',
                            'description' => 'Contenido de la nota o interaccion',
                        ],
                        'tipo' => [
                            'type' => 'string',
                            'description' => 'Tipo de interaccion',
                            'enum' => ['nota', 'llamada', 'email', 'reunion', 'tarea', 'seguimiento'],
                        ],
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Estado de la nota/tarea',
                            'enum' => ['pendiente', 'completada', 'cancelada'],
                        ],
                        'fecha_seguimiento' => [
                            'type' => 'string',
                            'description' => 'Fecha de seguimiento en formato YYYY-MM-DD HH:MM:SS (opcional)',
                        ],
                    ],
                    'required' => ['cliente_id', 'contenido'],
                ],
            ],
        ];
    }

    // =====================================================================
    // KNOWLEDGE BASE & FAQs
    // =====================================================================

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Gestion de Clientes / CRM**

El modulo de Clientes permite gestionar un CRM basico integrado en el sistema.
Incluye la gestion de contactos, notas de seguimiento e interacciones.

**Tipos de cliente:**
- Particular: persona fisica
- Empresa: persona juridica / empresa
- Autonomo: trabajador autonomo
- Administracion: entidad publica

**Estados del cliente:**
- Potencial: lead o contacto inicial
- Activo: cliente activo con relacion comercial
- Inactivo: cliente que ya no tiene actividad
- Perdido: oportunidad perdida

**Tipos de interaccion/nota:**
- Nota: nota general sobre el cliente
- Llamada: registro de llamada telefonica
- Email: registro de comunicacion por email
- Reunion: registro de reunion presencial o virtual
- Tarea: tarea pendiente relacionada con el cliente
- Seguimiento: recordatorio de seguimiento futuro

**Origenes de cliente:**
- Web: llego a traves de la web
- Referido: recomendado por otro cliente
- Redes: captado en redes sociales
- Directo: contacto directo
- Otro: otro origen

**Acciones disponibles:**
- "buscar clientes [termino]": busca por nombre, email o empresa
- "listar clientes": muestra todos los clientes con filtros
- "crear cliente [nombre]": crea un nuevo contacto
- "ver cliente [ID]": muestra detalle y notas de un cliente
- "agregar nota a cliente [ID]": registra una interaccion
- "mis clientes": muestra los clientes asignados al usuario
- "estadisticas clientes": muestra metricas del CRM
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Como puedo agregar un nuevo cliente al CRM?',
                'respuesta' => 'Puedes decirme "crear cliente" seguido del nombre. Tambien puedes proporcionar email, telefono, empresa y tipo de cliente.',
            ],
            [
                'pregunta' => 'Como registro una llamada con un cliente?',
                'respuesta' => 'Dime "agregar llamada al cliente [ID o nombre]" y el contenido de la nota. Quedara registrada como interaccion tipo llamada.',
            ],
            [
                'pregunta' => 'Como veo las estadisticas del CRM?',
                'respuesta' => 'Dime "estadisticas de clientes" y te mostrare el total de clientes, desglose por tipo y estado, y el pipeline de valor.',
            ],
            [
                'pregunta' => 'Puedo filtrar clientes por estado?',
                'respuesta' => 'Si, puedes pedirme "listar clientes activos", "listar clientes potenciales", etc. Tambien puedes filtrar por tipo o etiquetas.',
            ],
            [
                'pregunta' => 'Como se asigna un cliente a un comercial?',
                'respuesta' => 'Al crear o actualizar un cliente, puedes indicar el campo "asignado_a" con el ID del usuario de WordPress al que quieres asignarlo.',
            ],
        ];
    }

    // =====================================================================
    // WEB COMPONENTS
    // =====================================================================

    /**
     * Componentes web del modulo
     */
    public function get_web_components() {
        return [
            'clientes_hero' => [
                'label' => __('Hero Clientes / CRM', 'flavor-chat-ia'),
                'description' => __('Seccion hero para la pagina de gestion de clientes', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Titulo', 'flavor-chat-ia'),
                        'default' => __('Gestion de Clientes', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtitulo', 'flavor-chat-ia'),
                        'default' => __('CRM integrado para gestionar tus contactos, notas e interacciones', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de Fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                ],
                'template' => 'clientes/hero',
            ],
            'clientes_grid' => [
                'label' => __('Grid de Clientes', 'flavor-chat-ia'),
                'description' => __('Listado de clientes del CRM en formato grid', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-id-alt',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Titulo de Seccion', 'flavor-chat-ia'),
                        'default' => __('Nuestros Clientes', 'flavor-chat-ia'),
                    ],
                    'estado_filtro' => [
                        'type' => 'select',
                        'label' => __('Filtrar por Estado', 'flavor-chat-ia'),
                        'options' => ['todos', 'activo', 'potencial', 'inactivo', 'perdido'],
                        'default' => 'todos',
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Numero maximo de clientes', 'flavor-chat-ia'),
                        'default' => 12,
                    ],
                ],
                'template' => 'clientes/clientes-grid',
            ],
            'clientes_estadisticas' => [
                'label' => __('Estadisticas CRM', 'flavor-chat-ia'),
                'description' => __('Dashboard de estadisticas del CRM', 'flavor-chat-ia'),
                'category' => 'features',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Titulo de Seccion', 'flavor-chat-ia'),
                        'default' => __('Dashboard CRM', 'flavor-chat-ia'),
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['cards', 'minimal'],
                        'default' => 'cards',
                    ],
                ],
                'template' => 'clientes/estadisticas',
            ],
        ];
    }

    /**
     * Define las páginas del módulo para V3
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Clientes', 'flavor-chat-ia'),
                'slug' => 'clientes',
                'content' => '<h1>' . __('Gestión de Clientes', 'flavor-chat-ia') . '</h1>
<p>' . __('Administra tu cartera de clientes', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="clientes" action="listar_clientes" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Nuevo Cliente', 'flavor-chat-ia'),
                'slug' => 'nuevo-cliente',
                'content' => '<h1>' . __('Nuevo Cliente', 'flavor-chat-ia') . '</h1>
<p>' . __('Registra un nuevo cliente', 'flavor-chat-ia') . '</p>

[flavor_module_form module="clientes" action="crear_cliente"]',
                'parent' => 'clientes',
            ],
            [
                'title' => __('Segmentos', 'flavor-chat-ia'),
                'slug' => 'segmentos-clientes',
                'content' => '<h1>' . __('Segmentos', 'flavor-chat-ia') . '</h1>
<p>' . __('Organiza clientes por segmentos', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="clientes" action="segmentos"]',
                'parent' => 'clientes',
            ],
            [
                'title' => __('Historial', 'flavor-chat-ia'),
                'slug' => 'historial-clientes',
                'content' => '<h1>' . __('Historial', 'flavor-chat-ia') . '</h1>
<p>' . __('Revisa el historial de interacciones', 'flavor-chat-ia') . '</p>

[flavor_module_dashboard module="clientes" action="historial"]',
                'parent' => 'clientes',
            ],
        ];
    }


    /**
     * Crea las tablas del módulo si no existen
     *
     * @return void
     */
    public function maybe_create_tables() {
        $db_version_key = 'flavor_clientes_db_version';
        $db_version = get_option($db_version_key, '');

        if ($db_version === '1.0.0') {
            return; // Ya instaladas
        }

        $install_path = dirname(__FILE__) . '/install.php';
        if (file_exists($install_path)) {
            require_once $install_path;

            if (function_exists('flavor_clientes_crear_tablas')) {
                flavor_clientes_crear_tablas();
            }
        }
    }

    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo_tab = dirname(__FILE__) . '/class-clientes-dashboard-tab.php';
        if (file_exists($archivo_tab)) {
            require_once $archivo_tab;
            Flavor_Clientes_Dashboard_Tab::get_instance();
        }
    }
}
