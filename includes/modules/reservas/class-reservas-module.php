<?php
/**
 * Modulo de Reservas Generico para Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Reservas - Gestion generica de reservas para distintos tipos de negocio
 */
class Flavor_Chat_Reservas_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'reservas';
        $this->name = 'Reservas'; // Translation loaded on init
        $this->description = 'Gestion generica de reservas: mesas, espacios, clases y mas. Permite crear, cancelar, modificar y consultar disponibilidad.'; // Translation loaded on init

        parent::__construct();
    }

    /**
     * Verifica si el modulo puede activarse
     */
    public function can_activate() {
        global $wpdb;
        $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';

        return Flavor_Chat_Helpers::tabla_existe($nombre_tabla_reservas);
    }

    /**
     * Mensaje si no puede activarse
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Reservas no estan creadas. Activa el modulo para crearlas automaticamente.', 'flavor-chat-ia');
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
     * Configuracion por defecto
     */
    protected function get_default_settings() {
        return [
            'hora_apertura'        => '09:00',
            'hora_cierre'          => '22:00',
            'duracion_por_defecto' => 60,
            'capacidad_maxima'     => 50,
            'dias_antelacion'      => 30,
            'tipos_servicio'       => [
                'mesa_restaurante'  => __('Mesa de Restaurante', 'flavor-chat-ia'),
                'espacio_coworking' => __('Espacio Coworking', 'flavor-chat-ia'),
                'clase_deportiva'   => __('Clase Deportiva', 'flavor-chat-ia'),
            ],
            'estados_reserva'      => [
                'pendiente'  => __('Pendiente', 'flavor-chat-ia'),
                'confirmada' => __('Confirmada', 'flavor-chat-ia'),
                'cancelada'  => __('Cancelada', 'flavor-chat-ia'),
                'completada' => __('Completada', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * Inicializa el modulo
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();
    }

    /**
     * Registrar rutas REST API para APKs
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // Listar reservas del usuario
        register_rest_route($namespace, '/reservas', [
            'methods' => 'GET',
            'callback' => [$this, 'api_listar_reservas'],
            'permission_callback' => '__return_true',
            'args' => [
                'estado' => [
                    'type' => 'string',
                    'enum' => ['pendiente', 'confirmada', 'cancelada', 'completada'],
                ],
                'fecha' => [
                    'type' => 'string',
                    'format' => 'date',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 20,
                ],
            ],
        ]);

        // Obtener una reserva específica
        register_rest_route($namespace, '/reservas/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_reserva'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        // Crear nueva reserva
        register_rest_route($namespace, '/reservas', [
            'methods' => 'POST',
            'callback' => [$this, 'api_crear_reserva'],
            'permission_callback' => '__return_true',
            'args' => [
                'tipo_servicio' => [
                    'type' => 'string',
                    'default' => 'mesa_restaurante',
                ],
                'nombre_cliente' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'email_cliente' => [
                    'required' => true,
                    'type' => 'string',
                    'format' => 'email',
                ],
                'telefono_cliente' => [
                    'type' => 'string',
                ],
                'fecha_reserva' => [
                    'required' => true,
                    'type' => 'string',
                    'format' => 'date',
                ],
                'hora_inicio' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'hora_fin' => [
                    'type' => 'string',
                ],
                'num_personas' => [
                    'type' => 'integer',
                    'default' => 1,
                ],
                'notas' => [
                    'type' => 'string',
                ],
            ],
        ]);

        // Modificar reserva
        register_rest_route($namespace, '/reservas/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'api_modificar_reserva'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
                'fecha_reserva' => [
                    'type' => 'string',
                    'format' => 'date',
                ],
                'hora_inicio' => [
                    'type' => 'string',
                ],
                'hora_fin' => [
                    'type' => 'string',
                ],
                'num_personas' => [
                    'type' => 'integer',
                ],
            ],
        ]);

        // Cancelar reserva
        register_rest_route($namespace, '/reservas/(?P<id>\d+)/cancelar', [
            'methods' => 'POST',
            'callback' => [$this, 'api_cancelar_reserva'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        // Consultar disponibilidad
        register_rest_route($namespace, '/reservas/disponibilidad', [
            'methods' => 'GET',
            'callback' => [$this, 'api_disponibilidad'],
            'permission_callback' => '__return_true',
            'args' => [
                'fecha_reserva' => [
                    'required' => true,
                    'type' => 'string',
                    'format' => 'date',
                ],
                'hora_inicio' => [
                    'type' => 'string',
                ],
                'hora_fin' => [
                    'type' => 'string',
                ],
                'num_personas' => [
                    'type' => 'integer',
                    'default' => 1,
                ],
            ],
        ]);

        // Obtener configuración (tipos de servicio, horarios, etc.)
        register_rest_route($namespace, '/reservas/config', [
            'methods' => 'GET',
            'callback' => [$this, 'api_obtener_config'],
            'permission_callback' => '__return_true',
        ]);
    }

    // =========================================================================
    // Métodos API REST
    // =========================================================================

    /**
     * API: Listar reservas
     */
    public function api_listar_reservas($request) {
        $resultado = $this->action_mis_reservas([
            'email' => $request->get_param('email'),
            'estado' => $request->get_param('estado'),
            'limite' => $request->get_param('limite') ?: 20,
        ]);

        if (!$resultado['success']) {
            return new WP_REST_Response(['success' => false, 'error' => $resultado['error']], 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Obtener una reserva específica
     */
    public function api_obtener_reserva($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_reservas';
        $id = absint($request->get_param('id'));

        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $id
        ));

        if (!$reserva) {
            return new WP_REST_Response(['success' => false, 'error' => 'Reserva no encontrada'], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'reserva' => [
                'id' => $reserva->id,
                'tipo_servicio' => $reserva->tipo_servicio,
                'nombre_cliente' => $reserva->nombre_cliente,
                'email_cliente' => $reserva->email_cliente,
                'telefono_cliente' => $reserva->telefono_cliente,
                'fecha' => $reserva->fecha_reserva,
                'hora_inicio' => $reserva->hora_inicio,
                'hora_fin' => $reserva->hora_fin,
                'num_personas' => $reserva->num_personas,
                'estado' => $reserva->estado,
                'notas' => $reserva->notas,
                'created_at' => $reserva->created_at,
            ],
        ], 200);
    }

    /**
     * API: Crear reserva
     */
    public function api_crear_reserva($request) {
        $resultado = $this->action_crear_reserva([
            'tipo_servicio' => $request->get_param('tipo_servicio'),
            'nombre_cliente' => $request->get_param('nombre_cliente'),
            'email_cliente' => $request->get_param('email_cliente'),
            'telefono_cliente' => $request->get_param('telefono_cliente'),
            'fecha_reserva' => $request->get_param('fecha_reserva'),
            'hora_inicio' => $request->get_param('hora_inicio'),
            'hora_fin' => $request->get_param('hora_fin'),
            'num_personas' => $request->get_param('num_personas'),
            'notas' => $request->get_param('notas'),
        ]);

        if (!$resultado['success']) {
            return new WP_REST_Response(['success' => false, 'error' => $resultado['error']], 400);
        }

        return new WP_REST_Response($resultado, 201);
    }

    /**
     * API: Modificar reserva
     */
    public function api_modificar_reserva($request) {
        $resultado = $this->action_modificar_reserva([
            'reserva_id' => $request->get_param('id'),
            'fecha_reserva' => $request->get_param('fecha_reserva'),
            'hora_inicio' => $request->get_param('hora_inicio'),
            'hora_fin' => $request->get_param('hora_fin'),
            'num_personas' => $request->get_param('num_personas'),
        ]);

        if (!$resultado['success']) {
            return new WP_REST_Response(['success' => false, 'error' => $resultado['error']], 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Cancelar reserva
     */
    public function api_cancelar_reserva($request) {
        $resultado = $this->action_cancelar_reserva([
            'reserva_id' => $request->get_param('id'),
        ]);

        if (!$resultado['success']) {
            return new WP_REST_Response(['success' => false, 'error' => $resultado['error']], 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Consultar disponibilidad
     */
    public function api_disponibilidad($request) {
        $resultado = $this->action_disponibilidad([
            'fecha_reserva' => $request->get_param('fecha_reserva'),
            'hora_inicio' => $request->get_param('hora_inicio'),
            'hora_fin' => $request->get_param('hora_fin'),
            'num_personas' => $request->get_param('num_personas'),
        ]);

        if (!$resultado['success']) {
            return new WP_REST_Response(['success' => false, 'error' => $resultado['error']], 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Obtener configuración del módulo
     */
    public function api_obtener_config($request) {
        return new WP_REST_Response([
            'success' => true,
            'config' => [
                'hora_apertura' => $this->get_setting('hora_apertura', '09:00'),
                'hora_cierre' => $this->get_setting('hora_cierre', '22:00'),
                'duracion_por_defecto' => $this->get_setting('duracion_por_defecto', 60),
                'capacidad_maxima' => $this->get_setting('capacidad_maxima', 50),
                'dias_antelacion' => $this->get_setting('dias_antelacion', 30),
                'tipos_servicio' => $this->get_setting('tipos_servicio', []),
                'estados_reserva' => $this->get_setting('estados_reserva', []),
            ],
        ], 200);
    }

    /**
     * Configuración para el Panel Unificado de Gestión
     *
     * @return array Configuración del módulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'reservas',
            'label' => __('Reservas', 'flavor-chat-ia'),
            'icon' => 'dashicons-calendar-alt',
            'capability' => 'manage_options',
            'categoria' => 'operaciones',
            'paginas' => [
                [
                    'slug' => 'reservas-calendario',
                    'titulo' => __('Calendario', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_calendario'],
                    'badge' => [$this, 'contar_reservas_hoy'],
                ],
                [
                    'slug' => 'reservas-listado',
                    'titulo' => __('Todas las Reservas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_listado'],
                ],
                [
                    'slug' => 'reservas-nueva',
                    'titulo' => __('Nueva Reserva', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_nueva'],
                ],
                [
                    'slug' => 'reservas-recursos',
                    'titulo' => __('Recursos', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_recursos'],
                ],
                [
                    'slug' => 'reservas-config',
                    'titulo' => __('Configuración', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta reservas para hoy
     *
     * @return int
     */
    public function contar_reservas_hoy() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_reservas';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return 0;
        }
        $hoy = date('Y-m-d');
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE DATE(fecha_reserva) = %s AND estado IN ('pendiente', 'confirmada')",
            $hoy
        ));
    }

    /**
     * Estadísticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_reservas';
        $stats = [];

        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return $stats;
        }

        // Reservas para hoy
        $hoy = date('Y-m-d');
        $reservas_hoy = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE DATE(fecha_reserva) = %s AND estado IN ('pendiente', 'confirmada')",
            $hoy
        ));
        $stats[] = [
            'icon' => 'dashicons-calendar-alt',
            'valor' => $reservas_hoy,
            'label' => __('Reservas hoy', 'flavor-chat-ia'),
            'color' => $reservas_hoy > 0 ? 'blue' : 'green',
            'enlace' => admin_url('admin.php?page=reservas-calendario'),
        ];

        // Pendientes de confirmar
        $pendientes = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla WHERE estado = 'pendiente'"
        );
        if ($pendientes > 0) {
            $stats[] = [
                'icon' => 'dashicons-clock',
                'valor' => $pendientes,
                'label' => __('Pendientes confirmar', 'flavor-chat-ia'),
                'color' => 'orange',
                'enlace' => admin_url('admin.php?page=reservas-listado&estado=pendiente'),
            ];
        }

        return $stats;
    }

    /**
     * Renderiza el calendario de reservas
     */
    public function render_admin_calendario() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Calendario de Reservas', 'flavor-chat-ia'), [
            ['label' => __('Nueva Reserva', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=reservas-nueva'), 'class' => 'button-primary'],
        ]);
        $this->handle_admin_actions();
        echo '<p>' . __('Vista rápida de reservas próximas (7 días).', 'flavor-chat-ia') . '</p>';

        if (!$this->can_activate()) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('El módulo no está activo o no tiene tablas creadas.', 'flavor-chat-ia') . '</p></div>';
            echo '</div>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_reservas';
        $hoy = date('Y-m-d');
        $fin = date('Y-m-d', strtotime('+7 days'));

        $reservas = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tabla WHERE fecha_reserva BETWEEN %s AND %s ORDER BY fecha_reserva ASC, hora_inicio ASC LIMIT 200",
                $hoy,
                $fin
            )
        );

        if (empty($reservas)) {
            echo '<p>' . esc_html__('No hay reservas próximas.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        $por_dia = [];
        foreach ($reservas as $reserva) {
            $por_dia[$reserva->fecha_reserva][] = $reserva;
        }

        foreach ($por_dia as $fecha => $items) {
            echo '<h3>' . esc_html(date_i18n(get_option('date_format'), strtotime($fecha))) . '</h3>';
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>' . esc_html__('Hora', 'flavor-chat-ia') . '</th>';
            echo '<th>' . esc_html__('Cliente', 'flavor-chat-ia') . '</th>';
            echo '<th>' . esc_html__('Personas', 'flavor-chat-ia') . '</th>';
            echo '<th>' . esc_html__('Estado', 'flavor-chat-ia') . '</th>';
            echo '<th>' . esc_html__('Acciones', 'flavor-chat-ia') . '</th>';
            echo '</tr></thead><tbody>';
            foreach ($items as $reserva) {
                echo '<tr>';
                echo '<td>' . esc_html(substr($reserva->hora_inicio, 0, 5)) . ' - ' . esc_html(substr($reserva->hora_fin, 0, 5)) . '</td>';
                echo '<td>' . esc_html($reserva->nombre_cliente) . '</td>';
                echo '<td>' . esc_html($reserva->num_personas) . '</td>';
                echo '<td>' . esc_html(ucfirst($reserva->estado)) . '</td>';
                echo '<td>' . $this->render_estado_actions($reserva->id, $reserva->estado) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    /**
     * Renderiza el listado de reservas
     */
    public function render_admin_listado() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Listado de Reservas', 'flavor-chat-ia'), [
            ['label' => __('Nueva Reserva', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=reservas-nueva'), 'class' => 'button-primary'],
        ]);
        $this->handle_admin_actions();
        echo '<p>' . __('Listado filtrable de todas las reservas.', 'flavor-chat-ia') . '</p>';

        if (!$this->can_activate()) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('El módulo no está activo o no tiene tablas creadas.', 'flavor-chat-ia') . '</p></div>';
            echo '</div>';
            return;
        }

        $estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        $fecha = isset($_GET['fecha']) ? sanitize_text_field($_GET['fecha']) : '';
        $busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        echo '<form method="get" style="margin: 12px 0;">';
        echo '<input type="hidden" name="page" value="reservas-listado">';
        echo '<input type="date" name="fecha" value="' . esc_attr($fecha) . '"> ';
        echo '<select name="estado">';
        echo '<option value="">' . esc_html__('Todos los estados', 'flavor-chat-ia') . '</option>';
        foreach ($this->get_setting('estados_reserva', []) as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($estado, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select> ';
        echo '<input type="search" name="s" placeholder="' . esc_attr__('Buscar cliente', 'flavor-chat-ia') . '" value="' . esc_attr($busqueda) . '"> ';
        echo '<button class="button">' . esc_html__('Filtrar', 'flavor-chat-ia') . '</button>';
        echo '</form>';

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_reservas';
        $where = [];
        $params = [];
        if ($estado) {
            $where[] = 'estado = %s';
            $params[] = $estado;
        }
        if ($fecha) {
            $where[] = 'fecha_reserva = %s';
            $params[] = $fecha;
        }
        if ($busqueda) {
            $where[] = 'nombre_cliente LIKE %s';
            $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
        }
        $sql = "SELECT * FROM $tabla";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY fecha_reserva DESC, hora_inicio DESC LIMIT 200';

        $reservas = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);

        if (empty($reservas)) {
            echo '<p>' . esc_html__('No hay reservas con esos filtros.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>ID</th>';
        echo '<th>' . esc_html__('Fecha', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Hora', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Cliente', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Personas', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Estado', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Acciones', 'flavor-chat-ia') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($reservas as $reserva) {
            echo '<tr>';
            echo '<td>' . esc_html($reserva->id) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($reserva->fecha_reserva))) . '</td>';
            echo '<td>' . esc_html(substr($reserva->hora_inicio, 0, 5)) . ' - ' . esc_html(substr($reserva->hora_fin, 0, 5)) . '</td>';
            echo '<td>' . esc_html($reserva->nombre_cliente) . '</td>';
            echo '<td>' . esc_html($reserva->num_personas) . '</td>';
            echo '<td>' . esc_html(ucfirst($reserva->estado)) . '</td>';
            echo '<td>' . $this->render_estado_actions($reserva->id, $reserva->estado) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    /**
     * Renderiza formulario de nueva reserva
     */
    public function render_admin_nueva() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Nueva Reserva', 'flavor-chat-ia'));
        $this->handle_admin_create_reserva();
        echo '<p>' . __('Formulario para crear nueva reserva manual.', 'flavor-chat-ia') . '</p>';

        $tipos = $this->get_setting('tipos_servicio', []);
        $estados = $this->get_setting('estados_reserva', []);

        echo '<form method="post">';
        wp_nonce_field('crear_reserva', 'reservas_nonce');
        echo '<table class="form-table"><tbody>';
        echo '<tr><th>' . esc_html__('Tipo de servicio', 'flavor-chat-ia') . '</th><td><select name="tipo_servicio">';
        foreach ($tipos as $key => $label) {
            echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
        }
        echo '</select></td></tr>';
        echo '<tr><th>' . esc_html__('Nombre cliente', 'flavor-chat-ia') . '</th><td><input type="text" name="nombre_cliente" class="regular-text" required></td></tr>';
        echo '<tr><th>' . esc_html__('Email', 'flavor-chat-ia') . '</th><td><input type="email" name="email_cliente" class="regular-text" required></td></tr>';
        echo '<tr><th>' . esc_html__('Teléfono', 'flavor-chat-ia') . '</th><td><input type="text" name="telefono_cliente" class="regular-text"></td></tr>';
        echo '<tr><th>' . esc_html__('Fecha', 'flavor-chat-ia') . '</th><td><input type="date" name="fecha_reserva" required></td></tr>';
        echo '<tr><th>' . esc_html__('Hora inicio', 'flavor-chat-ia') . '</th><td><input type="time" name="hora_inicio" required></td></tr>';
        echo '<tr><th>' . esc_html__('Hora fin', 'flavor-chat-ia') . '</th><td><input type="time" name="hora_fin" required></td></tr>';
        echo '<tr><th>' . esc_html__('Personas', 'flavor-chat-ia') . '</th><td><input type="number" name="num_personas" min="1" value="1"></td></tr>';
        echo '<tr><th>' . esc_html__('Estado', 'flavor-chat-ia') . '</th><td><select name="estado">';
        foreach ($estados as $key => $label) {
            echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
        }
        echo '</select></td></tr>';
        echo '<tr><th>' . esc_html__('Notas', 'flavor-chat-ia') . '</th><td><textarea name="notas" rows="4" class="large-text"></textarea></td></tr>';
        echo '</tbody></table>';
        submit_button(__('Guardar Reserva', 'flavor-chat-ia'));
        echo '</form>';
        echo '</div>';
    }

    /**
     * Renderiza gestión de recursos
     */
    public function render_admin_recursos() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Recursos Reservables', 'flavor-chat-ia'), [
            ['label' => __('Nuevo Recurso', 'flavor-chat-ia'), 'url' => '#', 'class' => 'button-primary'],
        ]);
        echo '<p>' . __('Gestiona tipos de servicio desde la configuración del módulo.', 'flavor-chat-ia') . '</p>';
        echo '<p><a class="button" href="' . esc_url(admin_url('admin.php?page=reservas-config')) . '">' . esc_html__('Ir a configuración', 'flavor-chat-ia') . '</a></p>';
        echo '</div>';
    }

    /**
     * Renderiza configuración del módulo
     */
    public function render_admin_config() {
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Configuración de Reservas', 'flavor-chat-ia'));
        $this->handle_admin_save_config();
        echo '<p>' . __('Configuración del sistema de reservas.', 'flavor-chat-ia') . '</p>';

        $tipos = $this->get_setting('tipos_servicio', []);
        $tipos_lineas = [];
        foreach ($tipos as $key => $label) {
            $tipos_lineas[] = $key . '|' . $label;
        }

        echo '<form method="post">';
        wp_nonce_field('reservas_config', 'reservas_config_nonce');
        echo '<table class="form-table"><tbody>';
        echo '<tr><th>' . esc_html__('Hora apertura', 'flavor-chat-ia') . '</th><td><input type="time" name="hora_apertura" value="' . esc_attr($this->get_setting('hora_apertura')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Hora cierre', 'flavor-chat-ia') . '</th><td><input type="time" name="hora_cierre" value="' . esc_attr($this->get_setting('hora_cierre')) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Duración por defecto (min)', 'flavor-chat-ia') . '</th><td><input type="number" name="duracion_por_defecto" value="' . esc_attr($this->get_setting('duracion_por_defecto')) . '" min="15"></td></tr>';
        echo '<tr><th>' . esc_html__('Capacidad máxima', 'flavor-chat-ia') . '</th><td><input type="number" name="capacidad_maxima" value="' . esc_attr($this->get_setting('capacidad_maxima')) . '" min="1"></td></tr>';
        echo '<tr><th>' . esc_html__('Días de antelación', 'flavor-chat-ia') . '</th><td><input type="number" name="dias_antelacion" value="' . esc_attr($this->get_setting('dias_antelacion')) . '" min="1"></td></tr>';
        echo '<tr><th>' . esc_html__('Tipos de servicio', 'flavor-chat-ia') . '</th><td>';
        echo '<textarea name="tipos_servicio" rows="5" class="large-text" placeholder="mesa_restaurante|Mesa de Restaurante">' . esc_textarea(implode("\n", $tipos_lineas)) . '</textarea>';
        echo '<p class="description">' . esc_html__('Un tipo por línea en formato clave|Etiqueta.', 'flavor-chat-ia') . '</p>';
        echo '</td></tr>';
        echo '</tbody></table>';
        submit_button(__('Guardar configuración', 'flavor-chat-ia'));
        echo '</form>';
        echo '</div>';
    }

    private function handle_admin_actions() {
        if (empty($_GET['reserva_action']) || empty($_GET['reserva_id'])) {
            return;
        }

        $action = sanitize_text_field($_GET['reserva_action']);
        $reserva_id = absint($_GET['reserva_id']);
        $nonce = $_GET['_wpnonce'] ?? '';

        if (!wp_verify_nonce($nonce, 'reservas_estado_' . $reserva_id)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $estados = array_keys($this->get_setting('estados_reserva', []));
        if (!in_array($action, $estados, true)) {
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_reservas';
        $wpdb->update($tabla, ['estado' => $action], ['id' => $reserva_id]);
        echo '<div class="notice notice-success"><p>' . esc_html__('Estado actualizado.', 'flavor-chat-ia') . '</p></div>';
    }

    private function render_estado_actions($reserva_id, $estado_actual) {
        $acciones = [];
        foreach ($this->get_setting('estados_reserva', []) as $key => $label) {
            if ($key === $estado_actual) {
                continue;
            }
            $url = wp_nonce_url(
                add_query_arg([
                    'reserva_action' => $key,
                    'reserva_id' => $reserva_id,
                ]),
                'reservas_estado_' . $reserva_id
            );
            $acciones[] = '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }

        return implode(' | ', $acciones);
    }

    private function handle_admin_create_reserva() {
        if (empty($_POST['reservas_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['reservas_nonce'], 'crear_reserva')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $data = [
            'tipo_servicio' => sanitize_text_field($_POST['tipo_servicio'] ?? 'mesa_restaurante'),
            'nombre_cliente' => sanitize_text_field($_POST['nombre_cliente'] ?? ''),
            'email_cliente' => sanitize_email($_POST['email_cliente'] ?? ''),
            'telefono_cliente' => sanitize_text_field($_POST['telefono_cliente'] ?? ''),
            'fecha_reserva' => sanitize_text_field($_POST['fecha_reserva'] ?? ''),
            'hora_inicio' => sanitize_text_field($_POST['hora_inicio'] ?? ''),
            'hora_fin' => sanitize_text_field($_POST['hora_fin'] ?? ''),
            'num_personas' => max(1, intval($_POST['num_personas'] ?? 1)),
            'estado' => sanitize_text_field($_POST['estado'] ?? 'pendiente'),
            'notas' => sanitize_textarea_field($_POST['notas'] ?? ''),
        ];

        if (empty($data['nombre_cliente']) || !is_email($data['email_cliente'])) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nombre y email son obligatorios.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_reservas';
        $wpdb->insert($tabla, $data);
        echo '<div class="notice notice-success"><p>' . esc_html__('Reserva creada correctamente.', 'flavor-chat-ia') . '</p></div>';
    }

    private function handle_admin_save_config() {
        if (empty($_POST['reservas_config_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['reservas_config_nonce'], 'reservas_config')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Nonce inválido.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        $this->update_setting('hora_apertura', sanitize_text_field($_POST['hora_apertura'] ?? '09:00'));
        $this->update_setting('hora_cierre', sanitize_text_field($_POST['hora_cierre'] ?? '22:00'));
        $this->update_setting('duracion_por_defecto', absint($_POST['duracion_por_defecto'] ?? 60));
        $this->update_setting('capacidad_maxima', absint($_POST['capacidad_maxima'] ?? 50));
        $this->update_setting('dias_antelacion', absint($_POST['dias_antelacion'] ?? 30));

        $tipos_raw = sanitize_textarea_field($_POST['tipos_servicio'] ?? '');
        $tipos = [];
        foreach (array_filter(array_map('trim', explode("\n", $tipos_raw))) as $linea) {
            $parts = array_map('trim', explode('|', $linea, 2));
            if (!empty($parts[0])) {
                $tipos[$parts[0]] = $parts[1] ?? $parts[0];
            }
        }
        if ($tipos) {
            $this->update_setting('tipos_servicio', $tipos);
        }

        echo '<div class="notice notice-success"><p>' . esc_html__('Configuración guardada.', 'flavor-chat-ia') . '</p></div>';
    }

    /**
     * Crea la tabla si no existe
     */
    public function maybe_create_tables() {
        global $wpdb;
        $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';

        if (!Flavor_Chat_Helpers::tabla_existe($nombre_tabla_reservas)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        $ruta_instalador = FLAVOR_CHAT_IA_PATH . 'includes/modules/reservas/install.php';

        if (file_exists($ruta_instalador)) {
            require_once $ruta_instalador;
            flavor_reservas_crear_tabla();
        }
    }

    /**
     * Obtiene las acciones disponibles del modulo
     */
    public function get_actions() {
        return [
            'crear_reserva' => [
                'description' => 'Crear una nueva reserva',
                'params'      => ['tipo_servicio', 'nombre_cliente', 'email_cliente', 'telefono_cliente', 'fecha_reserva', 'hora_inicio', 'hora_fin', 'num_personas', 'notas'],
            ],
            'cancelar_reserva' => [
                'description' => 'Cancelar una reserva existente por su ID',
                'params'      => ['reserva_id'],
            ],
            'mis_reservas' => [
                'description' => 'Listar las reservas del usuario actual (por email o user_id)',
                'params'      => ['email', 'estado', 'limite'],
            ],
            'disponibilidad' => [
                'description' => 'Comprobar disponibilidad en una fecha y hora concretas',
                'params'      => ['tipo_servicio', 'fecha_reserva', 'hora_inicio', 'hora_fin', 'num_personas'],
            ],
            'modificar_reserva' => [
                'description' => 'Modificar fecha u hora de una reserva existente',
                'params'      => ['reserva_id', 'fecha_reserva', 'hora_inicio', 'hora_fin', 'num_personas'],
            ],
        ];
    }

    /**
     * Ejecuta una accion del modulo
     */
    public function execute_action($action_name, $params) {
        $metodo_accion = 'action_' . $action_name;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }

        return [
            'success' => false,
            'error'   => "Accion no implementada: {$action_name}",
        ];
    }

    // =========================================================================
    // Acciones del modulo
    // =========================================================================

    /**
     * Accion: Crear una nueva reserva
     */
    private function action_crear_reserva($params) {
        $campos_obligatorios = ['nombre_cliente', 'email_cliente', 'fecha_reserva', 'hora_inicio'];
        foreach ($campos_obligatorios as $campo_requerido) {
            if (empty($params[$campo_requerido])) {
                return [
                    'success' => false,
                    'error'   => sprintf(__('El campo %s es obligatorio.', 'flavor-chat-ia'), $campo_requerido),
                ];
            }
        }

        $email_sanitizado = sanitize_email($params['email_cliente']);
        if (!is_email($email_sanitizado)) {
            return ['success' => false, 'error' => __('El email proporcionado no es valido.', 'flavor-chat-ia')];
        }

        $fecha_reserva = sanitize_text_field($params['fecha_reserva']);
        $fecha_hoy     = current_time('Y-m-d');
        if ($fecha_reserva < $fecha_hoy) {
            return ['success' => false, 'error' => __('No se puede reservar en una fecha pasada.', 'flavor-chat-ia')];
        }

        $dias_antelacion_maxima = $this->get_setting('dias_antelacion', 30);
        $fecha_limite = date('Y-m-d', strtotime("+{$dias_antelacion_maxima} days"));
        if ($fecha_reserva > $fecha_limite) {
            return ['success' => false, 'error' => sprintf(__('No se puede reservar con mas de %d dias de antelacion.', 'flavor-chat-ia'), $dias_antelacion_maxima)];
        }

        $hora_inicio = sanitize_text_field($params['hora_inicio']);
        $hora_fin    = !empty($params['hora_fin'])
            ? sanitize_text_field($params['hora_fin'])
            : date('H:i:s', strtotime($hora_inicio) + ($this->get_setting('duracion_por_defecto', 60) * 60));

        $hora_apertura = $this->get_setting('hora_apertura', '09:00');
        $hora_cierre   = $this->get_setting('hora_cierre', '22:00');

        if ($hora_inicio < $hora_apertura || $hora_fin > $hora_cierre) {
            return ['success' => false, 'error' => sprintf(__('El horario de reservas es de %s a %s.', 'flavor-chat-ia'), $hora_apertura, $hora_cierre)];
        }

        $numero_personas = absint($params['num_personas'] ?? 1);
        if ($numero_personas < 1) { $numero_personas = 1; }

        $capacidad_maxima = $this->get_setting('capacidad_maxima', 50);
        $ocupacion_actual = $this->obtener_ocupacion_en_franja($fecha_reserva, $hora_inicio, $hora_fin);

        if (($ocupacion_actual + $numero_personas) > $capacidad_maxima) {
            return ['success' => false, 'error' => __('No hay disponibilidad suficiente para esa franja horaria.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $tipo_servicio         = sanitize_text_field($params['tipo_servicio'] ?? 'mesa_restaurante');
        $nombre_cliente        = sanitize_text_field($params['nombre_cliente']);
        $telefono_cliente      = sanitize_text_field($params['telefono_cliente'] ?? '');
        $notas_reserva         = sanitize_textarea_field($params['notas'] ?? '');
        $identificador_usuario = get_current_user_id() ?: null;

        $resultado_insercion = $wpdb->insert($nombre_tabla_reservas, [
            'tipo_servicio'    => $tipo_servicio,
            'nombre_cliente'   => $nombre_cliente,
            'email_cliente'    => $email_sanitizado,
            'telefono_cliente' => $telefono_cliente,
            'fecha_reserva'    => $fecha_reserva,
            'hora_inicio'      => $hora_inicio,
            'hora_fin'         => $hora_fin,
            'num_personas'     => $numero_personas,
            'estado'           => 'pendiente',
            'notas'            => $notas_reserva,
            'user_id'          => $identificador_usuario,
            'created_at'       => current_time('mysql'),
            'updated_at'       => current_time('mysql'),
        ], ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s']);

        if ($resultado_insercion === false) {
            return ['success' => false, 'error' => __('Error al crear la reserva. Intentalo de nuevo.', 'flavor-chat-ia')];
        }

        $identificador_reserva = $wpdb->insert_id;

        return [
            'success' => true,
            'mensaje' => sprintf(__('Reserva #%d creada correctamente para el %s a las %s.', 'flavor-chat-ia'), $identificador_reserva, date('d/m/Y', strtotime($fecha_reserva)), $hora_inicio),
            'reserva' => [
                'id'             => $identificador_reserva,
                'tipo_servicio'  => $tipo_servicio,
                'fecha'          => $fecha_reserva,
                'hora_inicio'    => $hora_inicio,
                'hora_fin'       => $hora_fin,
                'num_personas'   => $numero_personas,
                'estado'         => 'pendiente',
                'nombre_cliente' => $nombre_cliente,
            ],
        ];
    }

    /**
     * Accion: Cancelar una reserva existente
     */
    private function action_cancelar_reserva($params) {
        if (empty($params['reserva_id'])) {
            return ['success' => false, 'error' => __('Se requiere el ID de la reserva.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $identificador_reserva = absint($params['reserva_id']);

        $reserva_encontrada = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $nombre_tabla_reservas WHERE id = %d",
            $identificador_reserva
        ));

        if (!$reserva_encontrada) {
            return ['success' => false, 'error' => __('Reserva no encontrada.', 'flavor-chat-ia')];
        }

        $identificador_usuario_actual = get_current_user_id();
        $es_propietario   = ($reserva_encontrada->user_id && $reserva_encontrada->user_id == $identificador_usuario_actual);
        $es_administrador = current_user_can('manage_options');

        if (!$es_propietario && !$es_administrador && $identificador_usuario_actual) {
            return ['success' => false, 'error' => __('No tienes permisos para cancelar esta reserva.', 'flavor-chat-ia')];
        }

        if ($reserva_encontrada->estado === 'cancelada') {
            return ['success' => false, 'error' => __('Esta reserva ya esta cancelada.', 'flavor-chat-ia')];
        }

        if ($reserva_encontrada->estado === 'completada') {
            return ['success' => false, 'error' => __('No se puede cancelar una reserva ya completada.', 'flavor-chat-ia')];
        }

        $resultado_actualizacion = $wpdb->update($nombre_tabla_reservas, ['estado' => 'cancelada', 'updated_at' => current_time('mysql')], ['id' => $identificador_reserva], ['%s', '%s'], ['%d']);

        if ($resultado_actualizacion === false) {
            return ['success' => false, 'error' => __('Error al cancelar la reserva.', 'flavor-chat-ia')];
        }

        return ['success' => true, 'mensaje' => sprintf(__('Reserva #%d cancelada correctamente.', 'flavor-chat-ia'), $identificador_reserva)];
    }

    /**
     * Accion: Listar reservas del usuario
     */
    private function action_mis_reservas($params) {
        global $wpdb;
        $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $condiciones_where  = [];
        $valores_preparados = [];

        $identificador_usuario_actual = get_current_user_id();
        if ($identificador_usuario_actual) {
            $condiciones_where[]  = 'user_id = %d';
            $valores_preparados[] = $identificador_usuario_actual;
        } elseif (!empty($params['email'])) {
            $condiciones_where[]  = 'email_cliente = %s';
            $valores_preparados[] = sanitize_email($params['email']);
        } else {
            return ['success' => false, 'error' => __('Debes iniciar sesion o proporcionar un email.', 'flavor-chat-ia')];
        }

        if (!empty($params['estado'])) {
            $condiciones_where[]  = 'estado = %s';
            $valores_preparados[] = sanitize_text_field($params['estado']);
        }

        $limite_resultados    = absint($params['limite'] ?? 20);
        $clausula_where       = implode(' AND ', $condiciones_where);
        $valores_preparados[] = $limite_resultados;

        $consulta_sql = "SELECT * FROM $nombre_tabla_reservas WHERE $clausula_where ORDER BY fecha_reserva DESC, hora_inicio DESC LIMIT %d";
        $listado_reservas = $wpdb->get_results($wpdb->prepare($consulta_sql, ...$valores_preparados));

        if (empty($listado_reservas)) {
            return ['success' => true, 'mensaje' => __('No se encontraron reservas.', 'flavor-chat-ia'), 'reservas' => []];
        }

        $reservas_formateadas = array_map(function ($reserva) {
            return [
                'id'             => $reserva->id,
                'tipo_servicio'  => $reserva->tipo_servicio,
                'fecha'          => date('d/m/Y', strtotime($reserva->fecha_reserva)),
                'hora_inicio'    => $reserva->hora_inicio,
                'hora_fin'       => $reserva->hora_fin,
                'num_personas'   => $reserva->num_personas,
                'estado'         => $reserva->estado,
                'nombre_cliente' => $reserva->nombre_cliente,
                'notas'          => $reserva->notas,
            ];
        }, $listado_reservas);

        return ['success' => true, 'total' => count($reservas_formateadas), 'reservas' => $reservas_formateadas];
    }

    /**
     * Accion: Comprobar disponibilidad
     */
    private function action_disponibilidad($params) {
        if (empty($params['fecha_reserva'])) {
            return ['success' => false, 'error' => __('Se requiere la fecha para consultar disponibilidad.', 'flavor-chat-ia')];
        }

        $fecha_reserva    = sanitize_text_field($params['fecha_reserva']);
        $hora_inicio      = sanitize_text_field($params['hora_inicio'] ?? $this->get_setting('hora_apertura', '09:00'));
        $hora_fin         = sanitize_text_field($params['hora_fin'] ?? $this->get_setting('hora_cierre', '22:00'));
        $numero_personas  = absint($params['num_personas'] ?? 1);
        $capacidad_maxima = $this->get_setting('capacidad_maxima', 50);

        $fecha_hoy = current_time('Y-m-d');
        if ($fecha_reserva < $fecha_hoy) {
            return ['success' => false, 'error' => __('No se puede consultar disponibilidad para fechas pasadas.', 'flavor-chat-ia')];
        }

        $ocupacion_en_franja = $this->obtener_ocupacion_en_franja($fecha_reserva, $hora_inicio, $hora_fin);
        $plazas_disponibles  = $capacidad_maxima - $ocupacion_en_franja;
        $hay_disponibilidad  = $plazas_disponibles >= $numero_personas;
        $franjas_disponibles = $this->obtener_franjas_disponibles($fecha_reserva);

        return [
            'success'       => true,
            'disponible'    => $hay_disponibilidad,
            'fecha'         => date('d/m/Y', strtotime($fecha_reserva)),
            'hora_inicio'   => $hora_inicio,
            'hora_fin'      => $hora_fin,
            'ocupacion'     => $ocupacion_en_franja,
            'capacidad'     => $capacidad_maxima,
            'plazas_libres' => max(0, $plazas_disponibles),
            'franjas'       => $franjas_disponibles,
            'mensaje'       => $hay_disponibilidad
                ? sprintf(__('Hay %d plazas disponibles.', 'flavor-chat-ia'), $plazas_disponibles)
                : __('No hay disponibilidad para esa franja. Consulta otras franjas horarias.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Accion: Modificar fecha/hora de una reserva
     */
    private function action_modificar_reserva($params) {
        if (empty($params['reserva_id'])) {
            return ['success' => false, 'error' => __('Se requiere el ID de la reserva.', 'flavor-chat-ia')];
        }

        global $wpdb;
        $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $identificador_reserva = absint($params['reserva_id']);

        $reserva_encontrada = $wpdb->get_row($wpdb->prepare("SELECT * FROM $nombre_tabla_reservas WHERE id = %d", $identificador_reserva));

        if (!$reserva_encontrada) {
            return ['success' => false, 'error' => __('Reserva no encontrada.', 'flavor-chat-ia')];
        }

        $identificador_usuario_actual = get_current_user_id();
        $es_propietario   = ($reserva_encontrada->user_id && $reserva_encontrada->user_id == $identificador_usuario_actual);
        $es_administrador = current_user_can('manage_options');

        if (!$es_propietario && !$es_administrador && $identificador_usuario_actual) {
            return ['success' => false, 'error' => __('No tienes permisos para modificar esta reserva.', 'flavor-chat-ia')];
        }

        if (in_array($reserva_encontrada->estado, ['cancelada', 'completada'], true)) {
            return ['success' => false, 'error' => __('No se puede modificar una reserva cancelada o completada.', 'flavor-chat-ia')];
        }

        $datos_actualizados = [];
        $formatos_datos     = [];

        if (!empty($params['fecha_reserva'])) {
            $nueva_fecha = sanitize_text_field($params['fecha_reserva']);
            if ($nueva_fecha < current_time('Y-m-d')) {
                return ['success' => false, 'error' => __('No se puede cambiar a una fecha pasada.', 'flavor-chat-ia')];
            }
            $datos_actualizados['fecha_reserva'] = $nueva_fecha;
            $formatos_datos[] = '%s';
        }

        if (!empty($params['hora_inicio'])) {
            $datos_actualizados['hora_inicio'] = sanitize_text_field($params['hora_inicio']);
            $formatos_datos[] = '%s';
        }

        if (!empty($params['hora_fin'])) {
            $datos_actualizados['hora_fin'] = sanitize_text_field($params['hora_fin']);
            $formatos_datos[] = '%s';
        }

        if (!empty($params['num_personas'])) {
            $datos_actualizados['num_personas'] = absint($params['num_personas']);
            $formatos_datos[] = '%d';
        }

        if (empty($datos_actualizados)) {
            return ['success' => false, 'error' => __('No se proporcionaron datos para modificar.', 'flavor-chat-ia')];
        }

        $fecha_verificacion       = $datos_actualizados['fecha_reserva'] ?? $reserva_encontrada->fecha_reserva;
        $hora_inicio_verificacion = $datos_actualizados['hora_inicio'] ?? $reserva_encontrada->hora_inicio;
        $hora_fin_verificacion    = $datos_actualizados['hora_fin'] ?? $reserva_encontrada->hora_fin;
        $personas_verificacion    = $datos_actualizados['num_personas'] ?? $reserva_encontrada->num_personas;

        $ocupacion_franja = $this->obtener_ocupacion_en_franja($fecha_verificacion, $hora_inicio_verificacion, $hora_fin_verificacion, $identificador_reserva);
        $capacidad_maxima = $this->get_setting('capacidad_maxima', 50);

        if (($ocupacion_franja + $personas_verificacion) > $capacidad_maxima) {
            return ['success' => false, 'error' => __('No hay disponibilidad para los nuevos datos solicitados.', 'flavor-chat-ia')];
        }

        $datos_actualizados['updated_at'] = current_time('mysql');
        $formatos_datos[] = '%s';

        $resultado_actualizacion = $wpdb->update($nombre_tabla_reservas, $datos_actualizados, ['id' => $identificador_reserva], $formatos_datos, ['%d']);

        if ($resultado_actualizacion === false) {
            return ['success' => false, 'error' => __('Error al modificar la reserva.', 'flavor-chat-ia')];
        }

        return ['success' => true, 'mensaje' => sprintf(__('Reserva #%d modificada correctamente.', 'flavor-chat-ia'), $identificador_reserva)];
    }

    // =========================================================================
    // Metodos auxiliares
    // =========================================================================

    /**
     * Obtiene la ocupacion total en una franja horaria
     */
    private function obtener_ocupacion_en_franja($fecha_consulta, $hora_inicio_consulta, $hora_fin_consulta, $excluir_reserva_id = null) {
        global $wpdb;
        $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';

        $consulta_sql = "SELECT IFNULL(SUM(num_personas), 0)
            FROM $nombre_tabla_reservas
            WHERE fecha_reserva = %s
              AND estado IN ('pendiente', 'confirmada')
              AND hora_inicio < %s
              AND hora_fin > %s";

        $valores_preparados = [$fecha_consulta, $hora_fin_consulta, $hora_inicio_consulta];

        if ($excluir_reserva_id) {
            $consulta_sql .= " AND id != %d";
            $valores_preparados[] = $excluir_reserva_id;
        }

        $ocupacion_total = $wpdb->get_var($wpdb->prepare($consulta_sql, ...$valores_preparados));
        return absint($ocupacion_total);
    }

    /**
     * Obtiene las franjas horarias disponibles para un dia
     */
    private function obtener_franjas_disponibles($fecha_consulta) {
        $hora_apertura    = $this->get_setting('hora_apertura', '09:00');
        $hora_cierre      = $this->get_setting('hora_cierre', '22:00');
        $duracion_franja  = $this->get_setting('duracion_por_defecto', 60);
        $capacidad_maxima = $this->get_setting('capacidad_maxima', 50);

        $franjas_resultado   = [];
        $marca_tiempo_inicio = strtotime($fecha_consulta . ' ' . $hora_apertura);
        $marca_tiempo_cierre = strtotime($fecha_consulta . ' ' . $hora_cierre);

        while ($marca_tiempo_inicio < $marca_tiempo_cierre) {
            $marca_tiempo_fin_franja = $marca_tiempo_inicio + ($duracion_franja * 60);
            if ($marca_tiempo_fin_franja > $marca_tiempo_cierre) { break; }

            $hora_inicio_franja = date('H:i', $marca_tiempo_inicio);
            $hora_fin_franja    = date('H:i', $marca_tiempo_fin_franja);
            $ocupacion_franja   = $this->obtener_ocupacion_en_franja($fecha_consulta, $hora_inicio_franja, $hora_fin_franja);
            $plazas_libres      = $capacidad_maxima - $ocupacion_franja;

            $franjas_resultado[] = [
                'hora_inicio'   => $hora_inicio_franja,
                'hora_fin'      => $hora_fin_franja,
                'ocupacion'     => $ocupacion_franja,
                'plazas_libres' => max(0, $plazas_libres),
                'disponible'    => $plazas_libres > 0,
            ];

            $marca_tiempo_inicio = $marca_tiempo_fin_franja;
        }

        return $franjas_resultado;
    }

    // =========================================================================
    // Definiciones de herramientas para IA
    // =========================================================================

    /**
     * Obtiene las definiciones de tools para Claude
     */
    public function get_tool_definitions() {
        return [
            [
                'name'         => 'reservas_crear_reserva',
                'description'  => 'Crear una nueva reserva (mesa, espacio, clase, etc.)',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'tipo_servicio'   => ['type' => 'string', 'description' => 'Tipo de servicio a reservar', 'enum' => ['mesa_restaurante', 'espacio_coworking', 'clase_deportiva']],
                        'nombre_cliente'  => ['type' => 'string', 'description' => 'Nombre completo del cliente'],
                        'email_cliente'   => ['type' => 'string', 'description' => 'Email de contacto del cliente'],
                        'telefono_cliente' => ['type' => 'string', 'description' => 'Telefono de contacto'],
                        'fecha_reserva'   => ['type' => 'string', 'description' => 'Fecha de la reserva en formato YYYY-MM-DD'],
                        'hora_inicio'     => ['type' => 'string', 'description' => 'Hora de inicio en formato HH:MM'],
                        'hora_fin'        => ['type' => 'string', 'description' => 'Hora de fin en formato HH:MM (opcional)'],
                        'num_personas'    => ['type' => 'integer', 'description' => 'Numero de personas'],
                        'notas'           => ['type' => 'string', 'description' => 'Notas adicionales sobre la reserva'],
                    ],
                    'required' => ['nombre_cliente', 'email_cliente', 'fecha_reserva', 'hora_inicio'],
                ],
            ],
            [
                'name'         => 'reservas_cancelar_reserva',
                'description'  => 'Cancelar una reserva existente por su ID',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => ['reserva_id' => ['type' => 'integer', 'description' => 'ID de la reserva a cancelar']],
                    'required'   => ['reserva_id'],
                ],
            ],
            [
                'name'         => 'reservas_mis_reservas',
                'description'  => 'Listar las reservas del usuario actual o buscar por email',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'email'  => ['type' => 'string', 'description' => 'Email para buscar reservas (si no hay sesion)'],
                        'estado' => ['type' => 'string', 'description' => 'Filtrar por estado', 'enum' => ['pendiente', 'confirmada', 'cancelada', 'completada']],
                        'limite' => ['type' => 'integer', 'description' => 'Numero maximo de resultados'],
                    ],
                ],
            ],
            [
                'name'         => 'reservas_disponibilidad',
                'description'  => 'Comprobar disponibilidad de reservas en una fecha y franja horaria',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'tipo_servicio' => ['type' => 'string', 'description' => 'Tipo de servicio'],
                        'fecha_reserva' => ['type' => 'string', 'description' => 'Fecha a consultar en formato YYYY-MM-DD'],
                        'hora_inicio'   => ['type' => 'string', 'description' => 'Hora de inicio de la franja (HH:MM)'],
                        'hora_fin'      => ['type' => 'string', 'description' => 'Hora de fin de la franja (HH:MM)'],
                        'num_personas'  => ['type' => 'integer', 'description' => 'Numero de personas para la reserva'],
                    ],
                    'required' => ['fecha_reserva'],
                ],
            ],
            [
                'name'         => 'reservas_modificar_reserva',
                'description'  => 'Modificar la fecha, hora o numero de personas de una reserva existente',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'reserva_id'    => ['type' => 'integer', 'description' => 'ID de la reserva a modificar'],
                        'fecha_reserva' => ['type' => 'string', 'description' => 'Nueva fecha en formato YYYY-MM-DD'],
                        'hora_inicio'   => ['type' => 'string', 'description' => 'Nueva hora de inicio (HH:MM)'],
                        'hora_fin'      => ['type' => 'string', 'description' => 'Nueva hora de fin (HH:MM)'],
                        'num_personas'  => ['type' => 'integer', 'description' => 'Nuevo numero de personas'],
                    ],
                    'required' => ['reserva_id'],
                ],
            ],
        ];
    }

    // =========================================================================
    // Knowledge base y FAQs
    // =========================================================================

    /**
     * Obtiene el conocimiento base del modulo
     */
    public function get_knowledge_base() {
        $hora_apertura = $this->get_setting('hora_apertura', '09:00');
        $hora_cierre   = $this->get_setting('hora_cierre', '22:00');
        $capacidad     = $this->get_setting('capacidad_maxima', 50);
        $antelacion    = $this->get_setting('dias_antelacion', 30);

        return "**Sistema de Reservas**\n\n" .
            "Gestion de reservas para distintos tipos de servicio.\n\n" .
            "**Funcionalidades:**\n" .
            "- Crear nuevas reservas con fecha, hora y numero de personas\n" .
            "- Cancelar reservas existentes\n" .
            "- Modificar fecha/hora de reservas\n" .
            "- Consultar disponibilidad por fecha y franja horaria\n" .
            "- Ver historial de reservas del usuario\n\n" .
            "**Configuracion actual:**\n" .
            "- Horario: de $hora_apertura a $hora_cierre\n" .
            "- Capacidad maxima simultanea: $capacidad personas\n" .
            "- Reservas con hasta $antelacion dias de antelacion\n\n" .
            "**Estados de reserva:**\n" .
            "- Pendiente: Reserva recien creada\n" .
            "- Confirmada: Reserva confirmada por el establecimiento\n" .
            "- Cancelada: Reserva cancelada\n" .
            "- Completada: Reserva que ya se ha realizado\n\n" .
            "**Tipos de servicio:** mesa_restaurante, espacio_coworking, clase_deportiva";
    }

    /**
     * Obtiene las FAQs del modulo
     */
    public function get_faqs() {
        return [
            ['pregunta' => 'Como puedo hacer una reserva?', 'respuesta' => 'Puedes hacer una reserva indicandome la fecha, hora y numero de personas. Yo me encargo de comprobar la disponibilidad y crear la reserva.'],
            ['pregunta' => 'Puedo cancelar mi reserva?', 'respuesta' => 'Si, puedes cancelar tu reserva en cualquier momento siempre que no se haya completado ya. Solo necesito el numero de reserva.'],
            ['pregunta' => 'Como cambio la fecha u hora de mi reserva?', 'respuesta' => 'Indicame el numero de reserva y los nuevos datos. Compruebo disponibilidad y la modifico al momento.'],
            ['pregunta' => 'Como puedo ver mis reservas?', 'respuesta' => 'Si tienes cuenta, veo tus reservas automaticamente. Si no, indicame tu email y busco las reservas asociadas.'],
        ];
    }
}
