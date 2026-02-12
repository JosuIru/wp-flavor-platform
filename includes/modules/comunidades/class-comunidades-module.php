<?php
/**
 * Modulo Comunidades para Chat IA
 *
 * Crea y gestiona comunidades tematicas con miembros, actividades y contenido compartido.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Comunidades - Gestion de comunidades tematicas
 */
class Flavor_Chat_Comunidades_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'comunidades';
        $this->name = 'Comunidades'; // Translation loaded on init
        $this->description = 'Crea y gestiona comunidades tematicas con miembros, actividades y contenido compartido'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        return Flavor_Chat_Helpers::tabla_existe($tabla_comunidades);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Comunidades no estan creadas. Se crearan automaticamente al activar.', 'flavor-chat-ia');
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
            'maximo_comunidades_por_usuario' => 10,
            'requiere_aprobacion_creacion'   => false,
            'permitir_comunidades_secretas'  => true,
            'categorias_predeterminadas'     => [
                'tecnologia'    => __('Tecnologia', 'flavor-chat-ia'),
                'deportes'      => __('Deportes', 'flavor-chat-ia'),
                'cultura'       => __('Cultura', 'flavor-chat-ia'),
                'educacion'     => __('Educacion', 'flavor-chat-ia'),
                'medioambiente' => __('Medio Ambiente', 'flavor-chat-ia'),
                'salud'         => __('Salud', 'flavor-chat-ia'),
                'ocio'          => __('Ocio', 'flavor-chat-ia'),
                'vecinal'       => __('Vecinal', 'flavor-chat-ia'),
                'otros'         => __('Otros', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Registrar en Panel Unificado de Gestion
        $this->registrar_en_panel_unificado();
    }

    // =========================================================================
    // REST API
    // =========================================================================

    /**
     * Registra las rutas de la REST API para el modulo de comunidades
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // GET /flavor/v1/comunidades - Listar comunidades
        register_rest_route($namespace, '/comunidades', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_listar_comunidades'],
            'permission_callback' => '__return_true',
            'args'                => [
                'tipo' => [
                    'type'              => 'string',
                    'enum'              => ['abierta', 'cerrada'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'categoria' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'busqueda' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'limite' => [
                    'type'              => 'integer',
                    'default'           => 20,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // GET /flavor/v1/comunidades/{id} - Obtener una comunidad
        register_rest_route($namespace, '/comunidades/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_obtener_comunidad'],
            'permission_callback' => '__return_true',
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function ($valor) {
                        return is_numeric($valor) && $valor > 0;
                    },
                ],
            ],
        ]);

        // POST /flavor/v1/comunidades/{id}/unirse - Unirse a comunidad
        register_rest_route($namespace, '/comunidades/(?P<id>\d+)/unirse', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'api_unirse_comunidad'],
            'permission_callback' => [$this, 'api_verificar_usuario_autenticado'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function ($valor) {
                        return is_numeric($valor) && $valor > 0;
                    },
                ],
            ],
        ]);

        // POST /flavor/v1/comunidades/{id}/salir - Salir de comunidad
        register_rest_route($namespace, '/comunidades/(?P<id>\d+)/salir', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'api_salir_comunidad'],
            'permission_callback' => [$this, 'api_verificar_usuario_autenticado'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function ($valor) {
                        return is_numeric($valor) && $valor > 0;
                    },
                ],
            ],
        ]);

        // GET /flavor/v1/comunidades/mis-comunidades - Comunidades del usuario
        register_rest_route($namespace, '/comunidades/mis-comunidades', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_mis_comunidades'],
            'permission_callback' => [$this, 'api_verificar_usuario_autenticado'],
            'args'                => [
                'rol' => [
                    'type'              => 'string',
                    'enum'              => ['admin', 'moderador', 'miembro'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'estado' => [
                    'type'              => 'string',
                    'enum'              => ['activo', 'pendiente'],
                    'default'           => 'activo',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    /**
     * Verifica si el usuario esta autenticado para la REST API
     *
     * @return bool|WP_Error
     */
    public function api_verificar_usuario_autenticado() {
        if (!is_user_logged_in()) {
            return new \WP_Error(
                'rest_not_logged_in',
                __('Debes iniciar sesion para realizar esta accion.', 'flavor-chat-ia'),
                ['status' => 401]
            );
        }
        return true;
    }

    /**
     * API: Listar comunidades
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_listar_comunidades($request) {
        $parametros = [
            'tipo'     => $request->get_param('tipo'),
            'categoria' => $request->get_param('categoria'),
            'busqueda' => $request->get_param('busqueda'),
            'limite'   => $request->get_param('limite'),
        ];

        $resultado = $this->action_listar_comunidades($parametros);

        if (!$resultado['success']) {
            return new \WP_Error(
                'comunidades_error',
                $resultado['error'],
                ['status' => 400]
            );
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Obtener una comunidad especifica
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_obtener_comunidad($request) {
        $comunidad_id = $request->get_param('id');

        $parametros = [
            'comunidad_id' => $comunidad_id,
        ];

        $resultado = $this->action_ver_comunidad($parametros);

        if (!$resultado['success']) {
            return new \WP_Error(
                'comunidad_no_encontrada',
                $resultado['error'],
                ['status' => 404]
            );
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Unirse a una comunidad
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_unirse_comunidad($request) {
        $comunidad_id = $request->get_param('id');

        $parametros = [
            'comunidad_id' => $comunidad_id,
        ];

        $resultado = $this->action_unirse($parametros);

        if (!$resultado['success']) {
            $codigo_estado = 400;

            // Determinar codigo de estado apropiado
            if (strpos($resultado['error'], 'baneado') !== false) {
                $codigo_estado = 403;
            } elseif (strpos($resultado['error'], 'no encontrada') !== false) {
                $codigo_estado = 404;
            }

            return new \WP_Error(
                'unirse_error',
                $resultado['error'],
                ['status' => $codigo_estado]
            );
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Salir de una comunidad
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_salir_comunidad($request) {
        $comunidad_id = $request->get_param('id');

        $parametros = [
            'comunidad_id' => $comunidad_id,
        ];

        $resultado = $this->action_salir($parametros);

        if (!$resultado['success']) {
            $codigo_estado = 400;

            // No es miembro activo
            if (strpos($resultado['error'], 'no eres miembro') !== false) {
                $codigo_estado = 403;
            } elseif (strpos($resultado['error'], 'unico administrador') !== false) {
                $codigo_estado = 409; // Conflict
            }

            return new \WP_Error(
                'salir_error',
                $resultado['error'],
                ['status' => $codigo_estado]
            );
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Obtener comunidades del usuario autenticado
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_mis_comunidades($request) {
        $parametros = [
            'rol'    => $request->get_param('rol'),
            'estado' => $request->get_param('estado'),
        ];

        $resultado = $this->action_mis_comunidades($parametros);

        if (!$resultado['success']) {
            return new \WP_Error(
                'mis_comunidades_error',
                $resultado['error'],
                ['status' => 400]
            );
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
            'id' => 'comunidades',
            'label' => __('Comunidades', 'flavor-chat-ia'),
            'icon' => 'dashicons-groups',
            'capability' => 'manage_options',
            'categoria' => 'comunidad',
            'paginas' => [
                [
                    'slug' => 'comunidades-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug' => 'comunidades-listado',
                    'titulo' => __('Comunidades', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_listado'],
                    'badge' => [$this, 'contar_comunidades_activas'],
                ],
                [
                    'slug' => 'comunidades-miembros',
                    'titulo' => __('Miembros', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_miembros'],
                    'badge' => [$this, 'contar_solicitudes_pendientes'],
                ],
                [
                    'slug' => 'comunidades-config',
                    'titulo' => __('Configuracion', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta comunidades activas
     *
     * @return int
     */
    public function contar_comunidades_activas() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_comunidades)) {
            return 0;
        }
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_comunidades WHERE estado = 'activa'"
        );
    }

    /**
     * Cuenta solicitudes de miembros pendientes
     *
     * @return int
     */
    public function contar_solicitudes_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_miembros)) {
            return 0;
        }
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_miembros WHERE estado = 'pendiente'"
        );
    }

    /**
     * Estadisticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $estadisticas = [];

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_comunidades)) {
            return $estadisticas;
        }

        // Total comunidades activas
        $comunidades_activas = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_comunidades WHERE estado = 'activa'"
        );
        $estadisticas[] = [
            'icon' => 'dashicons-groups',
            'valor' => $comunidades_activas,
            'label' => __('Comunidades activas', 'flavor-chat-ia'),
            'color' => $comunidades_activas > 0 ? 'blue' : 'gray',
            'enlace' => admin_url('admin.php?page=comunidades-listado'),
        ];

        // Total miembros activos
        if (Flavor_Chat_Helpers::tabla_existe($tabla_miembros)) {
            $miembros_activos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_miembros WHERE estado = 'activo'"
            );
            $estadisticas[] = [
                'icon' => 'dashicons-admin-users',
                'valor' => $miembros_activos,
                'label' => __('Miembros activos', 'flavor-chat-ia'),
                'color' => $miembros_activos > 0 ? 'green' : 'gray',
                'enlace' => admin_url('admin.php?page=comunidades-miembros'),
            ];

            // Solicitudes pendientes
            $solicitudes_pendientes = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_miembros WHERE estado = 'pendiente'"
            );
            if ($solicitudes_pendientes > 0) {
                $estadisticas[] = [
                    'icon' => 'dashicons-clock',
                    'valor' => $solicitudes_pendientes,
                    'label' => __('Solicitudes pendientes', 'flavor-chat-ia'),
                    'color' => 'orange',
                    'enlace' => admin_url('admin.php?page=comunidades-miembros&estado=pendiente'),
                ];
            }
        }

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard de comunidades
     */
    public function render_admin_dashboard() {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Dashboard de Comunidades', 'flavor-chat-ia'), [
            ['label' => __('Nueva Comunidad', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=comunidades-listado&accion=nueva'), 'class' => 'button-primary'],
        ]);

        // Estadisticas rapidas
        $total_comunidades = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_comunidades WHERE estado = 'activa'");
        $total_miembros = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_miembros WHERE estado = 'activo'");
        $total_actividad = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_actividad");
        $pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_miembros WHERE estado = 'pendiente'");

        echo '<div class="flavor-stats-grid">';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($total_comunidades) . '</span><span class="stat-label">' . __('Comunidades Activas', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($total_miembros) . '</span><span class="stat-label">' . __('Miembros Totales', 'flavor-chat-ia') . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($total_actividad) . '</span><span class="stat-label">' . __('Publicaciones', 'flavor-chat-ia') . '</span></div>';
        if ($pendientes > 0) {
            echo '<div class="flavor-stat-card flavor-stat-warning"><span class="stat-number">' . esc_html($pendientes) . '</span><span class="stat-label">' . __('Solicitudes Pendientes', 'flavor-chat-ia') . '</span></div>';
        }
        echo '</div>';

        // Comunidades mas activas
        echo '<h2>' . __('Comunidades mas activas', 'flavor-chat-ia') . '</h2>';
        $comunidades_top = $wpdb->get_results(
            "SELECT * FROM $tabla_comunidades WHERE estado = 'activa' ORDER BY miembros_count DESC LIMIT 5"
        );

        if ($comunidades_top) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . __('Comunidad', 'flavor-chat-ia') . '</th><th>' . __('Tipo', 'flavor-chat-ia') . '</th><th>' . __('Categoria', 'flavor-chat-ia') . '</th><th>' . __('Miembros', 'flavor-chat-ia') . '</th></tr></thead>';
            echo '<tbody>';
            foreach ($comunidades_top as $comunidad) {
                echo '<tr>';
                echo '<td><strong>' . esc_html($comunidad->nombre) . '</strong></td>';
                echo '<td>' . esc_html(ucfirst($comunidad->tipo)) . '</td>';
                echo '<td>' . esc_html(ucfirst($comunidad->categoria)) . '</td>';
                echo '<td>' . esc_html($comunidad->miembros_count) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay comunidades activas todavia.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la pagina de listado de comunidades
     */
    public function render_admin_listado() {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestion de Comunidades', 'flavor-chat-ia'), [
            ['label' => __('Nueva Comunidad', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=comunidades-listado&accion=nueva'), 'class' => 'button-primary'],
        ]);

        // Tabs de filtro
        $estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'activa';
        $this->render_page_tabs([
            ['slug' => 'activa', 'label' => __('Activas', 'flavor-chat-ia')],
            ['slug' => 'pausada', 'label' => __('Pausadas', 'flavor-chat-ia')],
            ['slug' => 'archivada', 'label' => __('Archivadas', 'flavor-chat-ia')],
            ['slug' => 'todas', 'label' => __('Todas', 'flavor-chat-ia')],
        ], $estado_filtro);

        // Consulta de comunidades
        $condicion_estado = ($estado_filtro !== 'todas') ? $wpdb->prepare("WHERE estado = %s", $estado_filtro) : "";
        $comunidades = $wpdb->get_results(
            "SELECT * FROM $tabla_comunidades $condicion_estado ORDER BY created_at DESC"
        );

        if ($comunidades) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Nombre', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Tipo', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Categoria', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Miembros', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Estado', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Creada', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Acciones', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            foreach ($comunidades as $comunidad) {
                $creador = get_userdata($comunidad->creador_id);
                $nombre_creador = $creador ? $creador->display_name : __('Desconocido', 'flavor-chat-ia');
                echo '<tr>';
                echo '<td><strong>' . esc_html($comunidad->nombre) . '</strong><br><small>' . esc_html($nombre_creador) . '</small></td>';
                echo '<td>' . esc_html(ucfirst($comunidad->tipo)) . '</td>';
                echo '<td>' . esc_html(ucfirst($comunidad->categoria)) . '</td>';
                echo '<td>' . esc_html($comunidad->miembros_count) . '</td>';
                echo '<td><span class="status-' . esc_attr($comunidad->estado) . '">' . esc_html(ucfirst($comunidad->estado)) . '</span></td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($comunidad->created_at))) . '</td>';
                echo '<td>';
                echo '<a href="' . esc_url(admin_url('admin.php?page=comunidades-listado&accion=ver&id=' . $comunidad->id)) . '" class="button button-small">' . __('Ver', 'flavor-chat-ia') . '</a> ';
                echo '<a href="' . esc_url(admin_url('admin.php?page=comunidades-listado&accion=editar&id=' . $comunidad->id)) . '" class="button button-small">' . __('Editar', 'flavor-chat-ia') . '</a>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay comunidades en este estado.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la pagina de gestion de miembros
     */
    public function render_admin_miembros() {
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestion de Miembros', 'flavor-chat-ia'));

        // Tabs de filtro
        $estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'todos';
        $pendientes_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_miembros WHERE estado = 'pendiente'");

        $this->render_page_tabs([
            ['slug' => 'todos', 'label' => __('Todos', 'flavor-chat-ia')],
            ['slug' => 'activo', 'label' => __('Activos', 'flavor-chat-ia')],
            ['slug' => 'pendiente', 'label' => __('Pendientes', 'flavor-chat-ia'), 'badge' => $pendientes_count],
            ['slug' => 'suspendido', 'label' => __('Suspendidos', 'flavor-chat-ia')],
            ['slug' => 'baneado', 'label' => __('Baneados', 'flavor-chat-ia')],
        ], $estado_filtro);

        // Consulta de miembros
        $condicion_estado = ($estado_filtro !== 'todos') ? $wpdb->prepare("WHERE m.estado = %s", $estado_filtro) : "";
        $miembros = $wpdb->get_results(
            "SELECT m.*, c.nombre as comunidad_nombre, u.display_name, u.user_email
             FROM $tabla_miembros m
             LEFT JOIN $tabla_comunidades c ON m.comunidad_id = c.id
             LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
             $condicion_estado
             ORDER BY m.joined_at DESC
             LIMIT 100"
        );

        if ($miembros) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Usuario', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Comunidad', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Rol', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Estado', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Fecha Union', 'flavor-chat-ia') . '</th>';
            echo '<th>' . __('Acciones', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            foreach ($miembros as $miembro) {
                echo '<tr>';
                echo '<td><strong>' . esc_html($miembro->display_name ?: __('Usuario', 'flavor-chat-ia')) . '</strong><br><small>' . esc_html($miembro->user_email) . '</small></td>';
                echo '<td>' . esc_html($miembro->comunidad_nombre) . '</td>';
                echo '<td>' . esc_html(ucfirst($miembro->rol)) . '</td>';
                echo '<td><span class="status-' . esc_attr($miembro->estado) . '">' . esc_html(ucfirst($miembro->estado)) . '</span></td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($miembro->joined_at))) . '</td>';
                echo '<td>';
                if ($miembro->estado === 'pendiente') {
                    echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=comunidades-miembros&accion=aprobar&id=' . $miembro->id), 'aprobar_miembro')) . '" class="button button-small button-primary">' . __('Aprobar', 'flavor-chat-ia') . '</a> ';
                }
                echo '<a href="' . esc_url(admin_url('admin.php?page=comunidades-miembros&accion=gestionar&id=' . $miembro->id)) . '" class="button button-small">' . __('Gestionar', 'flavor-chat-ia') . '</a>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay miembros con este filtro.', 'flavor-chat-ia') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la pagina de configuracion
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuracion de Comunidades', 'flavor-chat-ia'));

        // Obtener configuracion actual
        $configuracion = $this->get_settings();

        echo '<form method="post" action="">';
        wp_nonce_field('guardar_config_comunidades', 'comunidades_config_nonce');

        echo '<table class="form-table">';

        // Maximo comunidades por usuario
        echo '<tr>';
        echo '<th scope="row"><label for="maximo_comunidades">' . __('Max. comunidades por usuario', 'flavor-chat-ia') . '</label></th>';
        echo '<td><input type="number" id="maximo_comunidades" name="maximo_comunidades_por_usuario" value="' . esc_attr($configuracion['maximo_comunidades_por_usuario']) . '" min="1" max="100" class="small-text"></td>';
        echo '</tr>';

        // Requiere aprobacion para crear
        echo '<tr>';
        echo '<th scope="row">' . __('Aprobacion para crear', 'flavor-chat-ia') . '</th>';
        echo '<td><label><input type="checkbox" name="requiere_aprobacion_creacion" value="1" ' . checked($configuracion['requiere_aprobacion_creacion'], true, false) . '> ' . __('Requiere aprobacion de admin para crear comunidades', 'flavor-chat-ia') . '</label></td>';
        echo '</tr>';

        // Permitir comunidades secretas
        echo '<tr>';
        echo '<th scope="row">' . __('Comunidades secretas', 'flavor-chat-ia') . '</th>';
        echo '<td><label><input type="checkbox" name="permitir_comunidades_secretas" value="1" ' . checked($configuracion['permitir_comunidades_secretas'], true, false) . '> ' . __('Permitir crear comunidades secretas (solo por invitacion)', 'flavor-chat-ia') . '</label></td>';
        echo '</tr>';

        echo '</table>';

        echo '<p class="submit"><input type="submit" name="guardar_config" class="button button-primary" value="' . __('Guardar Configuracion', 'flavor-chat-ia') . '"></p>';
        echo '</form>';

        echo '</div>';
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_comunidades)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias para el modulo de comunidades
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_actividad   = $wpdb->prefix . 'flavor_comunidades_actividad';

        $sql_comunidades = "CREATE TABLE IF NOT EXISTS $tabla_comunidades (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(200) NOT NULL,
            descripcion text DEFAULT NULL,
            imagen varchar(255) DEFAULT NULL,
            tipo enum('abierta','cerrada','secreta') DEFAULT 'abierta',
            categoria varchar(100) DEFAULT 'otros',
            ubicacion varchar(200) DEFAULT NULL,
            reglas text DEFAULT NULL,
            miembros_count int(11) DEFAULT 0,
            creador_id bigint(20) unsigned NOT NULL,
            estado enum('activa','pausada','archivada') DEFAULT 'activa',
            configuracion text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY creador_id (creador_id),
            KEY tipo (tipo),
            KEY categoria (categoria),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_miembros = "CREATE TABLE IF NOT EXISTS $tabla_miembros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            rol enum('admin','moderador','miembro') DEFAULT 'miembro',
            estado enum('activo','pendiente','suspendido','baneado') DEFAULT 'activo',
            joined_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY comunidad_usuario (comunidad_id, user_id),
            KEY comunidad_id (comunidad_id),
            KEY user_id (user_id),
            KEY rol (rol),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_actividad = "CREATE TABLE IF NOT EXISTS $tabla_actividad (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            tipo enum('publicacion','evento','anuncio','encuesta') DEFAULT 'publicacion',
            titulo varchar(255) DEFAULT NULL,
            contenido longtext DEFAULT NULL,
            adjuntos text DEFAULT NULL,
            reacciones_count int(11) DEFAULT 0,
            comentarios_count int(11) DEFAULT 0,
            es_fijado tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY comunidad_id (comunidad_id),
            KEY user_id (user_id),
            KEY tipo (tipo),
            KEY es_fijado (es_fijado),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_comunidades);
        dbDelta($sql_miembros);
        dbDelta($sql_actividad);

        // Insertar datos de ejemplo si las tablas estan vacias
        if ((int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_comunidades") === 0) {
            $this->insertar_datos_ejemplo();
        }
    }

    /**
     * Inserta datos de ejemplo para el modulo de comunidades
     */
    private function insertar_datos_ejemplo() {
        global $wpdb;

        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_actividad   = $wpdb->prefix . 'flavor_comunidades_actividad';

        $usuarios_admin = get_users(['role' => 'administrator', 'number' => 1]);
        $creador_id     = !empty($usuarios_admin) ? $usuarios_admin[0]->ID : 1;
        $fecha_actual   = current_time('mysql');

        $comunidades_ejemplo = [
            [
                'nombre'      => 'Huertos Urbanos del Barrio',
                'descripcion' => 'Comunidad para compartir experiencias, consejos y semillas entre los hortelanos del barrio. Organizamos jornadas de plantacion y talleres.',
                'tipo'        => 'abierta',
                'categoria'   => 'medioambiente',
                'ubicacion'   => 'Huerto comunitario - Plaza Central',
                'reglas'       => 'Respeto mutuo. Compartir es clave. Cuidamos la tierra juntos.',
                'miembros_count' => 1,
            ],
            [
                'nombre'      => 'Club de Lectura Local',
                'descripcion' => 'Nos reunimos mensualmente para comentar libros. Cada mes un miembro elige la lectura. Todos los generos son bienvenidos.',
                'tipo'        => 'abierta',
                'categoria'   => 'cultura',
                'ubicacion'   => 'Biblioteca Municipal',
                'reglas'       => 'Lectura obligatoria antes del encuentro. Respetamos todas las opiniones.',
                'miembros_count' => 1,
            ],
            [
                'nombre'      => 'Runners del Parque',
                'descripcion' => 'Grupo de corredores de todos los niveles. Quedamos 3 veces por semana para entrenar juntos. Principiantes bienvenidos.',
                'tipo'        => 'abierta',
                'categoria'   => 'deportes',
                'ubicacion'   => 'Parque Municipal',
                'reglas'       => 'Respeta tu ritmo y el de los demas. Puntualidad en las quedadas.',
                'miembros_count' => 1,
            ],
            [
                'nombre'      => 'Desarrolladores Web Local',
                'descripcion' => 'Comunidad para desarrolladores web del barrio. Compartimos recursos, hacemos pair programming y organizamos hackathons.',
                'tipo'        => 'cerrada',
                'categoria'   => 'tecnologia',
                'ubicacion'   => 'Coworking Central',
                'reglas'       => 'Codigo de conducta: inclusividad, respeto y colaboracion.',
                'miembros_count' => 1,
            ],
            [
                'nombre'      => 'Padres y Madres Activos',
                'descripcion' => 'Espacio para familias del barrio. Organizamos actividades para los ninos, compartimos recursos y nos apoyamos mutuamente.',
                'tipo'        => 'abierta',
                'categoria'   => 'vecinal',
                'ubicacion'   => 'Centro Civico',
                'reglas'       => 'Entorno seguro para familias. No se comparte informacion de menores en redes.',
                'miembros_count' => 1,
            ],
            [
                'nombre'      => 'Meditacion y Mindfulness',
                'descripcion' => 'Grupo de practica de meditacion y mindfulness. Sesiones guiadas para principiantes y avanzados.',
                'tipo'        => 'abierta',
                'categoria'   => 'salud',
                'ubicacion'   => 'Sala Polivalente - Centro Civico',
                'reglas'       => 'Silencio durante las sesiones. Respeto al espacio compartido.',
                'miembros_count' => 1,
            ],
        ];

        foreach ($comunidades_ejemplo as $comunidad_datos) {
            $configuracion_predeterminada = wp_json_encode([
                'allow_posts'       => true,
                'require_approval'  => false,
                'allow_events'      => true,
                'allow_polls'       => true,
            ]);

            $wpdb->insert(
                $tabla_comunidades,
                array_merge($comunidad_datos, [
                    'creador_id'    => $creador_id,
                    'estado'        => 'activa',
                    'configuracion' => $configuracion_predeterminada,
                    'created_at'    => $fecha_actual,
                    'updated_at'    => $fecha_actual,
                ]),
                ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s']
            );

            $comunidad_id = $wpdb->insert_id;

            // Registrar al creador como admin de la comunidad
            $wpdb->insert(
                $tabla_miembros,
                [
                    'comunidad_id' => $comunidad_id,
                    'user_id'      => $creador_id,
                    'rol'          => 'admin',
                    'estado'       => 'activo',
                    'joined_at'    => $fecha_actual,
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );

            // Crear una publicacion de bienvenida
            $wpdb->insert(
                $tabla_actividad,
                [
                    'comunidad_id' => $comunidad_id,
                    'user_id'      => $creador_id,
                    'tipo'         => 'anuncio',
                    'titulo'       => 'Bienvenidos a ' . $comunidad_datos['nombre'],
                    'contenido'    => 'Esta comunidad acaba de crearse. Invita a tus amigos y comienza a participar.',
                    'created_at'   => $fecha_actual,
                ],
                ['%d', '%d', '%s', '%s', '%s', '%s']
            );
        }
    }

    // =========================================================================
    // ACCIONES DEL MODULO
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_comunidades' => [
                'description' => 'Listar comunidades disponibles con filtros opcionales',
                'params'      => ['tipo', 'categoria', 'busqueda', 'limite'],
            ],
            'ver_comunidad' => [
                'description' => 'Ver detalle de una comunidad y su actividad reciente',
                'params'      => ['comunidad_id'],
            ],
            'crear_comunidad' => [
                'description' => 'Crear una nueva comunidad (requiere login)',
                'params'      => ['nombre', 'descripcion', 'tipo', 'categoria', 'ubicacion', 'reglas'],
            ],
            'unirse' => [
                'description' => 'Unirse a una comunidad',
                'params'      => ['comunidad_id'],
            ],
            'salir' => [
                'description' => 'Salir de una comunidad',
                'params'      => ['comunidad_id'],
            ],
            'mis_comunidades' => [
                'description' => 'Ver las comunidades del usuario actual',
                'params'      => ['rol', 'estado'],
            ],
            'publicar' => [
                'description' => 'Publicar contenido en una comunidad (requiere membresia)',
                'params'      => ['comunidad_id', 'tipo', 'titulo', 'contenido'],
            ],
            'miembros' => [
                'description' => 'Listar miembros de una comunidad',
                'params'      => ['comunidad_id', 'rol', 'limite'],
            ],
            'gestionar_miembro' => [
                'description' => 'Cambiar rol o estado de un miembro (requiere ser admin)',
                'params'      => ['comunidad_id', 'user_id', 'accion', 'nuevo_rol'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($nombre_accion, $parametros) {
        $metodo_accion = 'action_' . $nombre_accion;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($parametros);
        }

        return [
            'success' => false,
            'error'   => sprintf(__('Accion no implementada: %s', 'flavor-chat-ia'), $nombre_accion),
        ];
    }

    // =========================================================================
    // IMPLEMENTACION DE ACCIONES
    // =========================================================================

    /**
     * Accion: Listar comunidades con filtros opcionales
     */
    private function action_listar_comunidades($parametros) {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        $tipo_filtro      = sanitize_text_field($parametros['tipo'] ?? '');
        $categoria_filtro = sanitize_text_field($parametros['categoria'] ?? '');
        $busqueda_filtro  = sanitize_text_field($parametros['busqueda'] ?? '');
        $limite           = absint($parametros['limite'] ?? 20);

        $condiciones_where   = ["estado = 'activa'", "tipo != 'secreta'"];
        $valores_preparacion = [];

        if (!empty($tipo_filtro)) {
            $condiciones_where[]   = "tipo = %s";
            $valores_preparacion[] = $tipo_filtro;
        }

        if (!empty($categoria_filtro)) {
            $condiciones_where[]   = "categoria = %s";
            $valores_preparacion[] = $categoria_filtro;
        }

        if (!empty($busqueda_filtro)) {
            $condiciones_where[]   = "(nombre LIKE %s OR descripcion LIKE %s)";
            $termino_busqueda      = '%' . $wpdb->esc_like($busqueda_filtro) . '%';
            $valores_preparacion[] = $termino_busqueda;
            $valores_preparacion[] = $termino_busqueda;
        }

        $sql_condiciones     = implode(' AND ', $condiciones_where);
        $sql_consulta        = "SELECT * FROM $tabla_comunidades WHERE $sql_condiciones ORDER BY miembros_count DESC, created_at DESC LIMIT %d";
        $valores_preparacion[] = $limite;

        $comunidades_encontradas = $wpdb->get_results($wpdb->prepare($sql_consulta, ...$valores_preparacion));

        $comunidades_formateadas = array_map(function ($comunidad) {
            $creador_datos = get_userdata($comunidad->creador_id);
            return [
                'id'             => (int) $comunidad->id,
                'nombre'         => $comunidad->nombre,
                'descripcion'    => $comunidad->descripcion,
                'imagen'         => $comunidad->imagen,
                'tipo'           => $comunidad->tipo,
                'categoria'      => $comunidad->categoria,
                'ubicacion'      => $comunidad->ubicacion,
                'miembros_count' => (int) $comunidad->miembros_count,
                'creador'        => [
                    'id'     => (int) $comunidad->creador_id,
                    'nombre' => $creador_datos ? $creador_datos->display_name : __('Usuario', 'flavor-chat-ia'),
                ],
                'estado'     => $comunidad->estado,
                'created_at' => $comunidad->created_at,
            ];
        }, $comunidades_encontradas);

        return [
            'success'      => true,
            'total'        => count($comunidades_formateadas),
            'comunidades'  => $comunidades_formateadas,
            'mensaje'      => sprintf(
                __('Se encontraron %d comunidades%s.', 'flavor-chat-ia'),
                count($comunidades_formateadas),
                !empty($busqueda_filtro) ? " para '$busqueda_filtro'" : ''
            ),
        ];
    }

    /**
     * Accion: Ver detalle de una comunidad y su actividad reciente
     */
    private function action_ver_comunidad($parametros) {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_actividad   = $wpdb->prefix . 'flavor_comunidades_actividad';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';

        $comunidad_id = absint($parametros['comunidad_id'] ?? 0);

        if (!$comunidad_id) {
            return [
                'success' => false,
                'error'   => __('ID de comunidad no valido.', 'flavor-chat-ia'),
            ];
        }

        $comunidad = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_comunidades WHERE id = %d",
            $comunidad_id
        ));

        if (!$comunidad) {
            return [
                'success' => false,
                'error'   => __('Comunidad no encontrada.', 'flavor-chat-ia'),
            ];
        }

        // Obtener actividad reciente
        $actividad_reciente = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name as autor_nombre
             FROM $tabla_actividad a
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.comunidad_id = %d
             ORDER BY a.es_fijado DESC, a.created_at DESC
             LIMIT 10",
            $comunidad_id
        ));

        $actividad_formateada = array_map(function ($entrada) {
            return [
                'id'               => (int) $entrada->id,
                'tipo'             => $entrada->tipo,
                'titulo'           => $entrada->titulo,
                'contenido'        => $entrada->contenido,
                'autor'            => $entrada->autor_nombre,
                'reacciones_count' => (int) $entrada->reacciones_count,
                'comentarios_count' => (int) $entrada->comentarios_count,
                'es_fijado'        => (bool) $entrada->es_fijado,
                'created_at'       => $entrada->created_at,
            ];
        }, $actividad_reciente);

        // Verificar si el usuario actual es miembro
        $usuario_actual_id   = get_current_user_id();
        $membresia_usuario   = null;
        if ($usuario_actual_id) {
            $membresia_usuario = $wpdb->get_row($wpdb->prepare(
                "SELECT rol, estado FROM $tabla_miembros WHERE comunidad_id = %d AND user_id = %d",
                $comunidad_id,
                $usuario_actual_id
            ));
        }

        $creador_datos = get_userdata($comunidad->creador_id);
        $configuracion_comunidad = json_decode($comunidad->configuracion, true) ?: [];

        return [
            'success'   => true,
            'comunidad' => [
                'id'             => (int) $comunidad->id,
                'nombre'         => $comunidad->nombre,
                'descripcion'    => $comunidad->descripcion,
                'imagen'         => $comunidad->imagen,
                'tipo'           => $comunidad->tipo,
                'categoria'      => $comunidad->categoria,
                'ubicacion'      => $comunidad->ubicacion,
                'reglas'         => $comunidad->reglas,
                'miembros_count' => (int) $comunidad->miembros_count,
                'creador'        => [
                    'id'     => (int) $comunidad->creador_id,
                    'nombre' => $creador_datos ? $creador_datos->display_name : __('Usuario', 'flavor-chat-ia'),
                ],
                'estado'        => $comunidad->estado,
                'configuracion' => $configuracion_comunidad,
                'created_at'    => $comunidad->created_at,
            ],
            'actividad_reciente' => $actividad_formateada,
            'membresia_usuario'  => $membresia_usuario ? [
                'rol'    => $membresia_usuario->rol,
                'estado' => $membresia_usuario->estado,
            ] : null,
        ];
    }

    /**
     * Accion: Crear una nueva comunidad
     */
    private function action_crear_comunidad($parametros) {
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion para crear una comunidad.', 'flavor-chat-ia'),
            ];
        }

        $nombre_comunidad      = sanitize_text_field($parametros['nombre'] ?? '');
        $descripcion_comunidad = sanitize_textarea_field($parametros['descripcion'] ?? '');
        $tipo_comunidad        = sanitize_text_field($parametros['tipo'] ?? 'abierta');
        $categoria_comunidad   = sanitize_text_field($parametros['categoria'] ?? 'otros');
        $ubicacion_comunidad   = sanitize_text_field($parametros['ubicacion'] ?? '');
        $reglas_comunidad      = sanitize_textarea_field($parametros['reglas'] ?? '');

        if (empty($nombre_comunidad)) {
            return [
                'success' => false,
                'error'   => __('El nombre de la comunidad es obligatorio.', 'flavor-chat-ia'),
            ];
        }

        // Validar tipo
        $tipos_permitidos = ['abierta', 'cerrada', 'secreta'];
        if (!in_array($tipo_comunidad, $tipos_permitidos, true)) {
            $tipo_comunidad = 'abierta';
        }

        // Verificar limite de comunidades creadas por el usuario
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_actividad   = $wpdb->prefix . 'flavor_comunidades_actividad';

        $maximo_comunidades = $this->get_setting('maximo_comunidades_por_usuario', 10);
        $comunidades_del_usuario = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_comunidades WHERE creador_id = %d AND estado != 'archivada'",
            $usuario_actual_id
        ));

        if ($comunidades_del_usuario >= $maximo_comunidades) {
            return [
                'success' => false,
                'error'   => sprintf(
                    __('Has alcanzado el limite de %d comunidades creadas.', 'flavor-chat-ia'),
                    $maximo_comunidades
                ),
            ];
        }

        $configuracion_predeterminada = wp_json_encode([
            'allow_posts'       => true,
            'require_approval'  => ($tipo_comunidad === 'cerrada'),
            'allow_events'      => true,
            'allow_polls'       => true,
        ]);

        $fecha_actual = current_time('mysql');

        $resultado_insercion = $wpdb->insert(
            $tabla_comunidades,
            [
                'nombre'         => $nombre_comunidad,
                'descripcion'    => $descripcion_comunidad,
                'tipo'           => $tipo_comunidad,
                'categoria'      => $categoria_comunidad,
                'ubicacion'      => $ubicacion_comunidad,
                'reglas'         => $reglas_comunidad,
                'miembros_count' => 1,
                'creador_id'     => $usuario_actual_id,
                'estado'         => 'activa',
                'configuracion'  => $configuracion_predeterminada,
                'created_at'     => $fecha_actual,
                'updated_at'     => $fecha_actual,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s']
        );

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error'   => __('Error al crear la comunidad.', 'flavor-chat-ia'),
            ];
        }

        $nueva_comunidad_id = $wpdb->insert_id;

        // Registrar al creador como admin
        $wpdb->insert(
            $tabla_miembros,
            [
                'comunidad_id' => $nueva_comunidad_id,
                'user_id'      => $usuario_actual_id,
                'rol'          => 'admin',
                'estado'       => 'activo',
                'joined_at'    => $fecha_actual,
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        // Crear publicacion de bienvenida
        $wpdb->insert(
            $tabla_actividad,
            [
                'comunidad_id' => $nueva_comunidad_id,
                'user_id'      => $usuario_actual_id,
                'tipo'         => 'anuncio',
                'titulo'       => sprintf(__('Bienvenidos a %s', 'flavor-chat-ia'), $nombre_comunidad),
                'contenido'    => __('La comunidad acaba de crearse. Invita a tus amigos y comienza a participar.', 'flavor-chat-ia'),
                'created_at'   => $fecha_actual,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );

        return [
            'success'      => true,
            'comunidad_id' => $nueva_comunidad_id,
            'mensaje'      => sprintf(
                __('Comunidad "%s" creada con exito. Ya eres administrador.', 'flavor-chat-ia'),
                $nombre_comunidad
            ),
        ];
    }

    /**
     * Accion: Unirse a una comunidad
     */
    private function action_unirse($parametros) {
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion para unirte a una comunidad.', 'flavor-chat-ia'),
            ];
        }

        $comunidad_id = absint($parametros['comunidad_id'] ?? 0);

        if (!$comunidad_id) {
            return [
                'success' => false,
                'error'   => __('ID de comunidad no valido.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';

        // Verificar que la comunidad existe y esta activa
        $comunidad = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_comunidades WHERE id = %d AND estado = 'activa'",
            $comunidad_id
        ));

        if (!$comunidad) {
            return [
                'success' => false,
                'error'   => __('Comunidad no encontrada o no esta activa.', 'flavor-chat-ia'),
            ];
        }

        // Verificar si ya es miembro
        $membresia_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_miembros WHERE comunidad_id = %d AND user_id = %d",
            $comunidad_id,
            $usuario_actual_id
        ));

        if ($membresia_existente) {
            if ($membresia_existente->estado === 'activo') {
                return [
                    'success' => false,
                    'error'   => __('Ya eres miembro de esta comunidad.', 'flavor-chat-ia'),
                ];
            }
            if ($membresia_existente->estado === 'baneado') {
                return [
                    'success' => false,
                    'error'   => __('Has sido baneado de esta comunidad.', 'flavor-chat-ia'),
                ];
            }
            if ($membresia_existente->estado === 'pendiente') {
                return [
                    'success' => false,
                    'error'   => __('Tu solicitud ya esta pendiente de aprobacion.', 'flavor-chat-ia'),
                ];
            }
        }

        // Determinar estado segun tipo de comunidad
        $estado_inicial = 'activo';
        if ($comunidad->tipo === 'cerrada') {
            $estado_inicial = 'pendiente';
        }

        $fecha_actual = current_time('mysql');

        if ($membresia_existente) {
            // Reactivar membresia suspendida
            $wpdb->update(
                $tabla_miembros,
                [
                    'estado'    => $estado_inicial,
                    'joined_at' => $fecha_actual,
                ],
                [
                    'comunidad_id' => $comunidad_id,
                    'user_id'      => $usuario_actual_id,
                ],
                ['%s', '%s'],
                ['%d', '%d']
            );
        } else {
            $wpdb->insert(
                $tabla_miembros,
                [
                    'comunidad_id' => $comunidad_id,
                    'user_id'      => $usuario_actual_id,
                    'rol'          => 'miembro',
                    'estado'       => $estado_inicial,
                    'joined_at'    => $fecha_actual,
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );
        }

        // Actualizar contador de miembros si es activo directamente
        if ($estado_inicial === 'activo') {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_comunidades SET miembros_count = miembros_count + 1, updated_at = %s WHERE id = %d",
                $fecha_actual,
                $comunidad_id
            ));
        }

        $mensaje_respuesta = ($estado_inicial === 'pendiente')
            ? sprintf(__('Tu solicitud para unirte a "%s" esta pendiente de aprobacion.', 'flavor-chat-ia'), $comunidad->nombre)
            : sprintf(__('Te has unido a "%s" correctamente.', 'flavor-chat-ia'), $comunidad->nombre);

        return [
            'success' => true,
            'estado'  => $estado_inicial,
            'mensaje' => $mensaje_respuesta,
        ];
    }

    /**
     * Accion: Salir de una comunidad
     */
    private function action_salir($parametros) {
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion.', 'flavor-chat-ia'),
            ];
        }

        $comunidad_id = absint($parametros['comunidad_id'] ?? 0);

        if (!$comunidad_id) {
            return [
                'success' => false,
                'error'   => __('ID de comunidad no valido.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';

        // Verificar que es miembro activo
        $membresia = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_miembros WHERE comunidad_id = %d AND user_id = %d AND estado = 'activo'",
            $comunidad_id,
            $usuario_actual_id
        ));

        if (!$membresia) {
            return [
                'success' => false,
                'error'   => __('No eres miembro activo de esta comunidad.', 'flavor-chat-ia'),
            ];
        }

        // No permitir que el unico admin se salga
        if ($membresia->rol === 'admin') {
            $total_admins = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_miembros WHERE comunidad_id = %d AND rol = 'admin' AND estado = 'activo'",
                $comunidad_id
            ));

            if ($total_admins <= 1) {
                return [
                    'success' => false,
                    'error'   => __('No puedes salir siendo el unico administrador. Asigna otro admin antes de irte.', 'flavor-chat-ia'),
                ];
            }
        }

        // Eliminar membresia
        $wpdb->delete(
            $tabla_miembros,
            [
                'comunidad_id' => $comunidad_id,
                'user_id'      => $usuario_actual_id,
            ],
            ['%d', '%d']
        );

        // Actualizar contador
        $fecha_actual = current_time('mysql');
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_comunidades SET miembros_count = GREATEST(miembros_count - 1, 0), updated_at = %s WHERE id = %d",
            $fecha_actual,
            $comunidad_id
        ));

        $comunidad = $wpdb->get_row($wpdb->prepare(
            "SELECT nombre FROM $tabla_comunidades WHERE id = %d",
            $comunidad_id
        ));

        return [
            'success' => true,
            'mensaje' => sprintf(
                __('Has salido de la comunidad "%s".', 'flavor-chat-ia'),
                $comunidad ? $comunidad->nombre : ''
            ),
        ];
    }

    /**
     * Accion: Mis comunidades
     */
    private function action_mis_comunidades($parametros) {
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion para ver tus comunidades.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';

        $rol_filtro    = sanitize_text_field($parametros['rol'] ?? '');
        $estado_filtro = sanitize_text_field($parametros['estado'] ?? 'activo');

        $condiciones_where   = ["m.user_id = %d", "m.estado = %s"];
        $valores_preparacion = [$usuario_actual_id, $estado_filtro];

        if (!empty($rol_filtro)) {
            $condiciones_where[]   = "m.rol = %s";
            $valores_preparacion[] = $rol_filtro;
        }

        $sql_condiciones = implode(' AND ', $condiciones_where);

        $comunidades_del_usuario = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, m.rol, m.estado as membresia_estado, m.joined_at
             FROM $tabla_comunidades c
             INNER JOIN $tabla_miembros m ON c.id = m.comunidad_id
             WHERE $sql_condiciones
             ORDER BY m.joined_at DESC",
            ...$valores_preparacion
        ));

        $comunidades_formateadas = array_map(function ($fila) {
            return [
                'id'              => (int) $fila->id,
                'nombre'          => $fila->nombre,
                'descripcion'     => $fila->descripcion,
                'tipo'            => $fila->tipo,
                'categoria'       => $fila->categoria,
                'miembros_count'  => (int) $fila->miembros_count,
                'mi_rol'          => $fila->rol,
                'estado_comunidad' => $fila->estado,
                'joined_at'       => $fila->joined_at,
            ];
        }, $comunidades_del_usuario);

        return [
            'success'     => true,
            'total'       => count($comunidades_formateadas),
            'comunidades' => $comunidades_formateadas,
            'mensaje'     => sprintf(
                __('Perteneces a %d comunidades.', 'flavor-chat-ia'),
                count($comunidades_formateadas)
            ),
        ];
    }

    /**
     * Accion: Publicar contenido en una comunidad
     */
    private function action_publicar($parametros) {
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion para publicar.', 'flavor-chat-ia'),
            ];
        }

        $comunidad_id        = absint($parametros['comunidad_id'] ?? 0);
        $tipo_publicacion    = sanitize_text_field($parametros['tipo'] ?? 'publicacion');
        $titulo_publicacion  = sanitize_text_field($parametros['titulo'] ?? '');
        $contenido_publicacion = sanitize_textarea_field($parametros['contenido'] ?? '');

        if (!$comunidad_id) {
            return [
                'success' => false,
                'error'   => __('ID de comunidad no valido.', 'flavor-chat-ia'),
            ];
        }

        if (empty($contenido_publicacion) && empty($titulo_publicacion)) {
            return [
                'success' => false,
                'error'   => __('El contenido o titulo son obligatorios.', 'flavor-chat-ia'),
            ];
        }

        // Validar tipo de publicacion
        $tipos_publicacion_permitidos = ['publicacion', 'evento', 'anuncio', 'encuesta'];
        if (!in_array($tipo_publicacion, $tipos_publicacion_permitidos, true)) {
            $tipo_publicacion = 'publicacion';
        }

        global $wpdb;
        $tabla_miembros  = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';

        // Verificar membresia activa
        $membresia = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_miembros WHERE comunidad_id = %d AND user_id = %d AND estado = 'activo'",
            $comunidad_id,
            $usuario_actual_id
        ));

        if (!$membresia) {
            return [
                'success' => false,
                'error'   => __('Debes ser miembro activo para publicar en esta comunidad.', 'flavor-chat-ia'),
            ];
        }

        // Solo admins y moderadores pueden publicar anuncios
        if ($tipo_publicacion === 'anuncio' && !in_array($membresia->rol, ['admin', 'moderador'], true)) {
            return [
                'success' => false,
                'error'   => __('Solo administradores y moderadores pueden publicar anuncios.', 'flavor-chat-ia'),
            ];
        }

        $fecha_actual = current_time('mysql');

        $resultado_insercion = $wpdb->insert(
            $tabla_actividad,
            [
                'comunidad_id' => $comunidad_id,
                'user_id'      => $usuario_actual_id,
                'tipo'         => $tipo_publicacion,
                'titulo'       => $titulo_publicacion,
                'contenido'    => $contenido_publicacion,
                'created_at'   => $fecha_actual,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error'   => __('Error al crear la publicacion.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success'        => true,
            'publicacion_id' => $wpdb->insert_id,
            'mensaje'        => __('Publicacion creada correctamente.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Accion: Listar miembros de una comunidad
     */
    private function action_miembros($parametros) {
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        $comunidad_id = absint($parametros['comunidad_id'] ?? 0);
        $rol_filtro   = sanitize_text_field($parametros['rol'] ?? '');
        $limite       = absint($parametros['limite'] ?? 50);

        if (!$comunidad_id) {
            return [
                'success' => false,
                'error'   => __('ID de comunidad no valido.', 'flavor-chat-ia'),
            ];
        }

        $condiciones_where   = ["m.comunidad_id = %d", "m.estado = 'activo'"];
        $valores_preparacion = [$comunidad_id];

        if (!empty($rol_filtro)) {
            $condiciones_where[]   = "m.rol = %s";
            $valores_preparacion[] = $rol_filtro;
        }

        $sql_condiciones       = implode(' AND ', $condiciones_where);
        $valores_preparacion[] = $limite;

        $miembros_encontrados = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_email
             FROM $tabla_miembros m
             LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
             WHERE $sql_condiciones
             ORDER BY FIELD(m.rol, 'admin', 'moderador', 'miembro'), m.joined_at ASC
             LIMIT %d",
            ...$valores_preparacion
        ));

        $miembros_formateados = array_map(function ($miembro) {
            return [
                'user_id'  => (int) $miembro->user_id,
                'nombre'   => $miembro->display_name ?: __('Usuario', 'flavor-chat-ia'),
                'email'    => $miembro->user_email,
                'rol'      => $miembro->rol,
                'estado'   => $miembro->estado,
                'joined_at' => $miembro->joined_at,
            ];
        }, $miembros_encontrados);

        return [
            'success'  => true,
            'total'    => count($miembros_formateados),
            'miembros' => $miembros_formateados,
            'mensaje'  => sprintf(
                __('La comunidad tiene %d miembros activos.', 'flavor-chat-ia'),
                count($miembros_formateados)
            ),
        ];
    }

    /**
     * Accion: Gestionar un miembro (cambiar rol, suspender, banear)
     */
    private function action_gestionar_miembro($parametros) {
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion.', 'flavor-chat-ia'),
            ];
        }

        $comunidad_id    = absint($parametros['comunidad_id'] ?? 0);
        $usuario_objetivo_id = absint($parametros['user_id'] ?? 0);
        $accion_gestionar    = sanitize_text_field($parametros['accion'] ?? '');
        $nuevo_rol           = sanitize_text_field($parametros['nuevo_rol'] ?? '');

        if (!$comunidad_id || !$usuario_objetivo_id) {
            return [
                'success' => false,
                'error'   => __('Comunidad y usuario son obligatorios.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';

        // Verificar que el usuario actual es admin de la comunidad
        $membresia_admin = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_miembros WHERE comunidad_id = %d AND user_id = %d AND rol = 'admin' AND estado = 'activo'",
            $comunidad_id,
            $usuario_actual_id
        ));

        if (!$membresia_admin) {
            return [
                'success' => false,
                'error'   => __('Solo los administradores pueden gestionar miembros.', 'flavor-chat-ia'),
            ];
        }

        // No gestionarse a si mismo
        if ($usuario_actual_id === $usuario_objetivo_id) {
            return [
                'success' => false,
                'error'   => __('No puedes gestionarte a ti mismo.', 'flavor-chat-ia'),
            ];
        }

        // Verificar que el usuario objetivo es miembro
        $membresia_objetivo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_miembros WHERE comunidad_id = %d AND user_id = %d",
            $comunidad_id,
            $usuario_objetivo_id
        ));

        if (!$membresia_objetivo) {
            return [
                'success' => false,
                'error'   => __('El usuario no es miembro de esta comunidad.', 'flavor-chat-ia'),
            ];
        }

        $fecha_actual       = current_time('mysql');
        $mensaje_resultado  = '';

        switch ($accion_gestionar) {
            case 'cambiar_rol':
                $roles_permitidos = ['admin', 'moderador', 'miembro'];
                if (!in_array($nuevo_rol, $roles_permitidos, true)) {
                    return [
                        'success' => false,
                        'error'   => __('Rol no valido.', 'flavor-chat-ia'),
                    ];
                }
                $wpdb->update(
                    $tabla_miembros,
                    ['rol' => $nuevo_rol],
                    ['comunidad_id' => $comunidad_id, 'user_id' => $usuario_objetivo_id],
                    ['%s'],
                    ['%d', '%d']
                );
                $mensaje_resultado = sprintf(__('Rol actualizado a "%s".', 'flavor-chat-ia'), $nuevo_rol);
                break;

            case 'suspender':
                $wpdb->update(
                    $tabla_miembros,
                    ['estado' => 'suspendido'],
                    ['comunidad_id' => $comunidad_id, 'user_id' => $usuario_objetivo_id],
                    ['%s'],
                    ['%d', '%d']
                );
                $wpdb->query($wpdb->prepare(
                    "UPDATE $tabla_comunidades SET miembros_count = GREATEST(miembros_count - 1, 0), updated_at = %s WHERE id = %d",
                    $fecha_actual,
                    $comunidad_id
                ));
                $mensaje_resultado = __('Miembro suspendido.', 'flavor-chat-ia');
                break;

            case 'banear':
                $wpdb->update(
                    $tabla_miembros,
                    ['estado' => 'baneado'],
                    ['comunidad_id' => $comunidad_id, 'user_id' => $usuario_objetivo_id],
                    ['%s'],
                    ['%d', '%d']
                );
                $wpdb->query($wpdb->prepare(
                    "UPDATE $tabla_comunidades SET miembros_count = GREATEST(miembros_count - 1, 0), updated_at = %s WHERE id = %d",
                    $fecha_actual,
                    $comunidad_id
                ));
                $mensaje_resultado = __('Miembro baneado de la comunidad.', 'flavor-chat-ia');
                break;

            case 'aprobar':
                if ($membresia_objetivo->estado !== 'pendiente') {
                    return [
                        'success' => false,
                        'error'   => __('Este miembro no esta pendiente de aprobacion.', 'flavor-chat-ia'),
                    ];
                }
                $wpdb->update(
                    $tabla_miembros,
                    ['estado' => 'activo'],
                    ['comunidad_id' => $comunidad_id, 'user_id' => $usuario_objetivo_id],
                    ['%s'],
                    ['%d', '%d']
                );
                $wpdb->query($wpdb->prepare(
                    "UPDATE $tabla_comunidades SET miembros_count = miembros_count + 1, updated_at = %s WHERE id = %d",
                    $fecha_actual,
                    $comunidad_id
                ));
                $mensaje_resultado = __('Miembro aprobado y activado.', 'flavor-chat-ia');
                break;

            case 'reactivar':
                $wpdb->update(
                    $tabla_miembros,
                    ['estado' => 'activo'],
                    ['comunidad_id' => $comunidad_id, 'user_id' => $usuario_objetivo_id],
                    ['%s'],
                    ['%d', '%d']
                );
                $wpdb->query($wpdb->prepare(
                    "UPDATE $tabla_comunidades SET miembros_count = miembros_count + 1, updated_at = %s WHERE id = %d",
                    $fecha_actual,
                    $comunidad_id
                ));
                $mensaje_resultado = __('Miembro reactivado.', 'flavor-chat-ia');
                break;

            default:
                return [
                    'success' => false,
                    'error'   => __('Accion de gestion no valida. Usa: cambiar_rol, suspender, banear, aprobar o reactivar.', 'flavor-chat-ia'),
                ];
        }

        return [
            'success' => true,
            'mensaje' => $mensaje_resultado,
        ];
    }

    // =========================================================================
    // TOOL DEFINITIONS PARA CLAUDE
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name'         => 'comunidades_listar',
                'description'  => 'Lista las comunidades disponibles con filtros opcionales de tipo, categoria y busqueda',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'tipo' => [
                            'type'        => 'string',
                            'description' => 'Filtrar por tipo de comunidad',
                            'enum'        => ['abierta', 'cerrada'],
                        ],
                        'categoria' => [
                            'type'        => 'string',
                            'description' => 'Filtrar por categoria',
                        ],
                        'busqueda' => [
                            'type'        => 'string',
                            'description' => 'Termino de busqueda en nombre o descripcion',
                        ],
                        'limite' => [
                            'type'        => 'integer',
                            'description' => 'Numero maximo de resultados',
                            'default'     => 20,
                        ],
                    ],
                ],
            ],
            [
                'name'         => 'comunidades_buscar',
                'description'  => 'Busca comunidades por nombre, descripcion o categoria',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'busqueda' => [
                            'type'        => 'string',
                            'description' => 'Termino de busqueda',
                        ],
                        'categoria' => [
                            'type'        => 'string',
                            'description' => 'Filtrar por categoria',
                        ],
                    ],
                    'required' => ['busqueda'],
                ],
            ],
            [
                'name'         => 'comunidades_crear',
                'description'  => 'Crea una nueva comunidad tematica. El usuario debe estar autenticado.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'nombre' => [
                            'type'        => 'string',
                            'description' => 'Nombre de la comunidad',
                        ],
                        'descripcion' => [
                            'type'        => 'string',
                            'description' => 'Descripcion detallada de la comunidad',
                        ],
                        'tipo' => [
                            'type'        => 'string',
                            'description' => 'Tipo de comunidad',
                            'enum'        => ['abierta', 'cerrada', 'secreta'],
                        ],
                        'categoria' => [
                            'type'        => 'string',
                            'description' => 'Categoria de la comunidad',
                        ],
                        'ubicacion' => [
                            'type'        => 'string',
                            'description' => 'Ubicacion fisica de la comunidad',
                        ],
                        'reglas' => [
                            'type'        => 'string',
                            'description' => 'Reglas de la comunidad',
                        ],
                    ],
                    'required' => ['nombre', 'descripcion'],
                ],
            ],
            [
                'name'         => 'comunidades_unirse',
                'description'  => 'Permite al usuario unirse a una comunidad existente',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'comunidad_id' => [
                            'type'        => 'integer',
                            'description' => 'ID de la comunidad a la que unirse',
                        ],
                    ],
                    'required' => ['comunidad_id'],
                ],
            ],
        ];
    }

    // =========================================================================
    // KNOWLEDGE BASE Y FAQS
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Comunidades - Guia de Uso**

Las comunidades son espacios tematicos donde los usuarios se reunen en torno a intereses comunes.
Cada comunidad tiene miembros, actividad compartida y su propia configuracion.

**Tipos de comunidad:**
- Abierta: cualquiera puede unirse libremente
- Cerrada: requiere aprobacion de un administrador
- Secreta: no aparece en listados publicos, solo por invitacion

**Roles de miembros:**
- Admin: control total, puede gestionar miembros y configuracion
- Moderador: puede moderar contenido y aprobar miembros
- Miembro: puede publicar y participar

**Categorias disponibles:**
- Tecnologia, Deportes, Cultura, Educacion, Medio Ambiente, Salud, Ocio, Vecinal, Otros

**Tipos de publicaciones:**
- Publicacion: contenido general
- Evento: actividades programadas
- Anuncio: comunicados oficiales (solo admin/moderador)
- Encuesta: votaciones de la comunidad

**Comandos disponibles:**
- "ver comunidades": lista comunidades disponibles
- "buscar comunidad [tema]": busca comunidades por tema
- "crear comunidad": crea una nueva comunidad
- "unirme a [comunidad]": unirse a una comunidad
- "mis comunidades": ver comunidades donde participo
- "publicar en [comunidad]": crear contenido en una comunidad

**Importante:**
- Los usuarios deben estar autenticados para crear o unirse a comunidades
- Los administradores son responsables de moderar su comunidad
- Las comunidades secretas no aparecen en busquedas publicas
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta'  => 'Como puedo crear una comunidad?',
                'respuesta' => 'Necesitas iniciar sesion y luego usar la opcion de crear comunidad. Elige un nombre, descripcion, tipo y categoria.',
            ],
            [
                'pregunta'  => 'Cual es la diferencia entre comunidad abierta, cerrada y secreta?',
                'respuesta' => 'Las abiertas permiten unirse libremente. Las cerradas requieren aprobacion. Las secretas no aparecen en listados y solo se accede por invitacion.',
            ],
            [
                'pregunta'  => 'Como me uno a una comunidad?',
                'respuesta' => 'Busca la comunidad que te interesa y haz clic en unirte. Si es cerrada, tu solicitud sera revisada por un admin.',
            ],
            [
                'pregunta'  => 'Puedo salir de una comunidad?',
                'respuesta' => 'Si, puedes salir en cualquier momento. Si eres el unico admin, deberas designar otro admin antes de irte.',
            ],
            [
                'pregunta'  => 'Cuantas comunidades puedo crear?',
                'respuesta' => 'Por defecto puedes crear hasta 10 comunidades. Este limite puede variar segun la configuracion del sitio.',
            ],
        ];
    }

    // =========================================================================
    // WEB COMPONENTS
    // =========================================================================

    /**
     * Componentes web del modulo para el constructor de paginas
     */
    public function get_web_components() {
        return [
            'comunidades_hero' => [
                'label'       => __('Hero Comunidades', 'flavor-chat-ia'),
                'description' => __('Seccion hero principal para la pagina de comunidades', 'flavor-chat-ia'),
                'category'    => 'hero',
                'icon'        => 'dashicons-groups',
                'fields'      => [
                    'titulo' => [
                        'type'    => 'text',
                        'label'   => __('Titulo', 'flavor-chat-ia'),
                        'default' => __('Comunidades', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type'    => 'textarea',
                        'label'   => __('Subtitulo', 'flavor-chat-ia'),
                        'default' => __('Encuentra tu tribu y conecta con personas que comparten tus intereses', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type'    => 'image',
                        'label'   => __('Imagen de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                ],
                'template' => 'comunidades/hero',
            ],
            'comunidades_grid' => [
                'label'       => __('Grid de Comunidades', 'flavor-chat-ia'),
                'description' => __('Listado visual de comunidades disponibles', 'flavor-chat-ia'),
                'category'    => 'listings',
                'icon'        => 'dashicons-grid-view',
                'fields'      => [
                    'titulo_seccion' => [
                        'type'    => 'text',
                        'label'   => __('Titulo de la seccion', 'flavor-chat-ia'),
                        'default' => __('Explora Comunidades', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type'    => 'select',
                        'label'   => __('Columnas', 'flavor-chat-ia'),
                        'options' => ['2', '3', '4'],
                        'default' => '3',
                    ],
                    'tipo_filtro' => [
                        'type'    => 'select',
                        'label'   => __('Filtrar por tipo', 'flavor-chat-ia'),
                        'options' => ['todos', 'abierta', 'cerrada'],
                        'default' => 'todos',
                    ],
                    'mostrar_miembros' => [
                        'type'    => 'toggle',
                        'label'   => __('Mostrar contador de miembros', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'comunidades/comunidades-grid',
            ],
            'comunidades_como_unirse' => [
                'label'       => __('Como Unirse', 'flavor-chat-ia'),
                'description' => __('Seccion explicativa de como unirse a comunidades', 'flavor-chat-ia'),
                'category'    => 'features',
                'icon'        => 'dashicons-info',
                'fields'      => [
                    'titulo_seccion' => [
                        'type'    => 'text',
                        'label'   => __('Titulo de la seccion', 'flavor-chat-ia'),
                        'default' => __('Como Unirse', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'comunidades/como-unirse',
            ],
        ];
    }

    /**
     * Crea páginas frontend automáticamente
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('comunidades');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('comunidades');
        if (!$pagina && !get_option('flavor_comunidades_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['comunidades']);
            update_option('flavor_comunidades_pages_created', 1, false);
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
                'title' => __('Comunidades', 'flavor-chat-ia'),
                'slug' => 'comunidades',
                'content' => '<h1>' . __('Comunidades', 'flavor-chat-ia') . '</h1>
<p>' . __('Descubre y únete a comunidades de tu interés, comparte experiencias y conecta con personas afines.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="comunidades" action="listar" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Crear Comunidad', 'flavor-chat-ia'),
                'slug' => 'crear',
                'content' => '<h1>' . __('Crear Comunidad', 'flavor-chat-ia') . '</h1>
<p>' . __('Crea tu propia comunidad y reúne a personas con intereses comunes.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="comunidades" action="crear"]',
                'parent' => 'comunidades',
            ],
            [
                'title' => __('Mis Comunidades', 'flavor-chat-ia'),
                'slug' => 'mis-comunidades',
                'content' => '<h1>' . __('Mis Comunidades', 'flavor-chat-ia') . '</h1>
<p>' . __('Gestiona las comunidades a las que perteneces y las que has creado.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="comunidades" action="mis_comunidades" columnas="3" limite="12"]',
                'parent' => 'comunidades',
            ],
        ];
    }
}
