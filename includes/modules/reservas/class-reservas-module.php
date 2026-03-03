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
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Consumer;

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
        $nombre_tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

        return Flavor_Chat_Helpers::tabla_existe($nombre_tabla_reservas)
            && Flavor_Chat_Helpers::tabla_existe($nombre_tabla_recursos);
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
     * Define que tipos de contenido acepta este modulo
     *
     * @return array IDs de providers aceptados
     */
    protected function get_accepted_integrations() {
        return ['multimedia'];
    }

    /**
     * Define donde se muestran los metaboxes de integracion
     *
     * @return array Configuracion de targets
     */
    protected function get_integration_targets() {
        global $wpdb;
        return [
            [
                'type'    => 'table',
                'table'   => $wpdb->prefix . 'flavor_reservas_recursos',
                'context' => 'side',
            ],
        ];
    }

    /**
     * Inicializa el modulo
     */
    public function init() {
        $this->register_as_integration_consumer();

        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'registrar_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Registrar en Panel Unificado de Gestión
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();

        // Admin pages
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);

        // Cargar Frontend Controller
        $this->cargar_frontend_controller();

        // Cargar Dashboard Tab
        $this->cargar_dashboard_tab();

        // AJAX handlers para shortcodes
        add_action('wp_ajax_reservas_crear', [$this, 'ajax_crear_reserva']);
        add_action('wp_ajax_nopriv_reservas_crear', [$this, 'ajax_crear_reserva']);
        add_action('wp_ajax_reservas_cancelar', [$this, 'ajax_cancelar_reserva']);
        add_action('wp_ajax_reservas_disponibilidad', [$this, 'ajax_consultar_disponibilidad']);
        add_action('wp_ajax_nopriv_reservas_disponibilidad', [$this, 'ajax_consultar_disponibilidad']);
    }

    /**
     * Carga el controlador frontend
     */
    private function cargar_frontend_controller() {
        $archivo_controller = dirname(__FILE__) . '/frontend/class-reservas-frontend-controller.php';
        if (file_exists($archivo_controller)) {
            require_once $archivo_controller;
            Flavor_Reservas_Frontend_Controller::get_instance();
        }
    }

    /**
     * Carga el Dashboard Tab para el panel del usuario
     */
    private function cargar_dashboard_tab() {
        $archivo_dashboard_tab = dirname(__FILE__) . '/class-reservas-dashboard-tab.php';
        if (file_exists($archivo_dashboard_tab)) {
            require_once $archivo_dashboard_tab;
            Flavor_Reservas_Dashboard_Tab::get_instance();
        }
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

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Registra todos los shortcodes del módulo de reservas
     */
    public function registrar_shortcodes() {
        add_shortcode('reservas_recursos', [$this, 'shortcode_recursos']);
        add_shortcode('reservas_calendario', [$this, 'shortcode_calendario']);
        add_shortcode('reservas_formulario', [$this, 'shortcode_formulario']);
        add_shortcode('reservas_mis_reservas', [$this, 'shortcode_mis_reservas']);
        add_shortcode('reservas_cancelar', [$this, 'shortcode_cancelar']);
        add_shortcode('reservas_disponibilidad', [$this, 'shortcode_disponibilidad']);
    }

    /**
     * Encola los estilos y scripts necesarios para los shortcodes
     */
    private function encolar_assets_shortcodes() {
        static $assets_encolados = false;
        if ($assets_encolados) {
            return;
        }
        $assets_encolados = true;

        // Estilos CSS inline para los shortcodes
        wp_add_inline_style('flavor-frontend', $this->obtener_estilos_shortcodes());

        // Localizar script para AJAX
        wp_localize_script('jquery', 'reservasAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('reservas_shortcode_nonce'),
        ]);
    }

    /**
     * Obtiene los estilos CSS para los shortcodes
     */
    private function obtener_estilos_shortcodes() {
        return '
        .reservas-container {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 100%;
            margin: 0 auto;
        }
        .reservas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .reservas-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e5e7eb;
        }
        .reservas-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        .reservas-card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .reservas-card-icon {
            font-size: 2rem;
        }
        .reservas-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        .reservas-card-meta {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }
        .reservas-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .reservas-badge-disponible { background: #d1fae5; color: #065f46; }
        .reservas-badge-pendiente { background: #fef3c7; color: #92400e; }
        .reservas-badge-confirmada { background: #dbeafe; color: #1e40af; }
        .reservas-badge-cancelada { background: #fee2e2; color: #991b1b; }
        .reservas-badge-completada { background: #e5e7eb; color: #374151; }
        .reservas-btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: background 0.2s;
        }
        .reservas-btn-primary {
            background: #2563eb;
            color: #fff;
        }
        .reservas-btn-primary:hover {
            background: #1d4ed8;
            color: #fff;
        }
        .reservas-btn-danger {
            background: #dc2626;
            color: #fff;
        }
        .reservas-btn-danger:hover {
            background: #b91c1c;
        }
        .reservas-btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .reservas-btn-secondary:hover {
            background: #e5e7eb;
        }
        .reservas-form {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }
        .reservas-form-group {
            margin-bottom: 1.25rem;
        }
        .reservas-form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.375rem;
        }
        .reservas-form-input,
        .reservas-form-select,
        .reservas-form-textarea {
            width: 100%;
            padding: 0.625rem 0.875rem;
            font-size: 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }
        .reservas-form-input:focus,
        .reservas-form-select:focus,
        .reservas-form-textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .reservas-form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        @media (max-width: 640px) {
            .reservas-form-row {
                grid-template-columns: 1fr;
            }
        }
        .reservas-calendario {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }
        .reservas-calendario-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .reservas-calendario-titulo {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        .reservas-calendario-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }
        .reservas-calendario-dia-header {
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            padding: 0.5rem;
        }
        .reservas-calendario-dia {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background 0.2s;
            border: 1px solid transparent;
        }
        .reservas-calendario-dia:hover {
            background: #f3f4f6;
        }
        .reservas-calendario-dia.disponible {
            background: #d1fae5;
            color: #065f46;
        }
        .reservas-calendario-dia.parcial {
            background: #fef3c7;
            color: #92400e;
        }
        .reservas-calendario-dia.lleno {
            background: #fee2e2;
            color: #991b1b;
            cursor: not-allowed;
        }
        .reservas-calendario-dia.hoy {
            border: 2px solid #2563eb;
            font-weight: 600;
        }
        .reservas-calendario-dia.pasado {
            color: #d1d5db;
            cursor: not-allowed;
        }
        .reservas-franjas {
            margin-top: 1.5rem;
        }
        .reservas-franja {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        .reservas-franja-hora {
            font-weight: 500;
            color: #1f2937;
        }
        .reservas-franja-plazas {
            font-size: 0.875rem;
            color: #6b7280;
        }
        .reservas-tabla {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .reservas-tabla th {
            background: #f3f4f6;
            padding: 0.875rem 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .reservas-tabla td {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.875rem;
            color: #374151;
        }
        .reservas-tabla tr:hover td {
            background: #f9fafb;
        }
        .reservas-empty {
            text-align: center;
            padding: 3rem 2rem;
            color: #6b7280;
        }
        .reservas-empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .reservas-mensaje {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .reservas-mensaje-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .reservas-mensaje-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .reservas-mensaje-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .reservas-disponibilidad-resultado {
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }
        .reservas-disponibilidad-resultado.disponible {
            background: #d1fae5;
            border: 2px solid #10b981;
        }
        .reservas-disponibilidad-resultado.no-disponible {
            background: #fee2e2;
            border: 2px solid #ef4444;
        }
        .reservas-disponibilidad-icono {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        ';
    }

    /**
     * Shortcode [reservas_recursos] - Lista de recursos/servicios reservables
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function shortcode_recursos($atts) {
        $this->encolar_assets_shortcodes();

        $atributos = shortcode_atts([
            'columnas' => '3',
            'mostrar_disponibilidad' => 'si',
        ], $atts);

        $tipos_servicio = $this->get_setting('tipos_servicio', []);

        if (empty($tipos_servicio)) {
            return $this->renderizar_mensaje_vacio(__('No hay recursos configurados.', 'flavor-chat-ia'));
        }

        $iconos_servicio = [
            'mesa_restaurante'  => '🍽️',
            'espacio_coworking' => '💼',
            'clase_deportiva'   => '🏃',
            'sala_reunion'      => '🏢',
            'equipo'            => '🔧',
            'vehiculo'          => '🚗',
        ];

        $columnas_grid = absint($atributos['columnas']);
        $fecha_hoy = current_time('Y-m-d');

        ob_start();
        ?>
        <div class="reservas-container">
            <div class="reservas-grid" style="grid-template-columns: repeat(<?php echo esc_attr($columnas_grid); ?>, 1fr);">
                <?php foreach ($tipos_servicio as $clave_servicio => $etiqueta_servicio):
                    $icono_recurso = $iconos_servicio[$clave_servicio] ?? '📋';
                    $plazas_disponibles = 0;

                    if ($atributos['mostrar_disponibilidad'] === 'si') {
                        $hora_apertura = $this->get_setting('hora_apertura', '09:00');
                        $hora_cierre = $this->get_setting('hora_cierre', '22:00');
                        $ocupacion_hoy = $this->obtener_ocupacion_en_franja($fecha_hoy, $hora_apertura, $hora_cierre);
                        $capacidad_maxima = $this->get_setting('capacidad_maxima', 50);
                        $plazas_disponibles = max(0, $capacidad_maxima - $ocupacion_hoy);
                    }
                ?>
                <div class="reservas-card">
                    <div class="reservas-card-header">
                        <span class="reservas-card-icon"><?php echo esc_html($icono_recurso); ?></span>
                        <h3 class="reservas-card-title"><?php echo esc_html($etiqueta_servicio); ?></h3>
                    </div>
                    <?php if ($atributos['mostrar_disponibilidad'] === 'si'): ?>
                    <div class="reservas-card-meta">
                        <span class="reservas-badge <?php echo $plazas_disponibles > 0 ? 'reservas-badge-disponible' : 'reservas-badge-cancelada'; ?>">
                            <?php echo $plazas_disponibles > 0
                                ? sprintf(__('%d plazas disponibles hoy', 'flavor-chat-ia'), $plazas_disponibles)
                                : __('Sin disponibilidad hoy', 'flavor-chat-ia'); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <a href="?reservar=<?php echo esc_attr($clave_servicio); ?>" class="reservas-btn reservas-btn-primary">
                        <?php esc_html_e('Reservar', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [reservas_calendario id="X"] - Calendario de disponibilidad
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function shortcode_calendario($atts) {
        $this->encolar_assets_shortcodes();

        $atributos = shortcode_atts([
            'id'               => '',
            'tipo_servicio'    => '',
            'mes'              => '',
            'anio'             => '',
            'mostrar_franjas'  => 'si',
        ], $atts);

        $mes_actual = $atributos['mes'] ? absint($atributos['mes']) : (int) date('n');
        $anio_actual = $atributos['anio'] ? absint($atributos['anio']) : (int) date('Y');

        $primer_dia_mes = mktime(0, 0, 0, $mes_actual, 1, $anio_actual);
        $nombre_mes = date_i18n('F Y', $primer_dia_mes);
        $dias_en_mes = (int) date('t', $primer_dia_mes);
        $dia_semana_inicio = (int) date('N', $primer_dia_mes);
        $fecha_hoy = current_time('Y-m-d');

        $dias_semana = [
            __('Lun', 'flavor-chat-ia'),
            __('Mar', 'flavor-chat-ia'),
            __('Mié', 'flavor-chat-ia'),
            __('Jue', 'flavor-chat-ia'),
            __('Vie', 'flavor-chat-ia'),
            __('Sáb', 'flavor-chat-ia'),
            __('Dom', 'flavor-chat-ia'),
        ];

        $capacidad_maxima = $this->get_setting('capacidad_maxima', 50);

        // Calcular mes anterior y siguiente
        $mes_anterior = $mes_actual - 1;
        $anio_anterior = $anio_actual;
        if ($mes_anterior < 1) {
            $mes_anterior = 12;
            $anio_anterior--;
        }

        $mes_siguiente = $mes_actual + 1;
        $anio_siguiente = $anio_actual;
        if ($mes_siguiente > 12) {
            $mes_siguiente = 1;
            $anio_siguiente++;
        }

        ob_start();
        ?>
        <div class="reservas-container">
            <div class="reservas-calendario">
                <div class="reservas-calendario-header">
                    <a href="?mes=<?php echo esc_attr($mes_anterior); ?>&anio=<?php echo esc_attr($anio_anterior); ?>" class="reservas-btn reservas-btn-secondary">
                        &larr; <?php esc_html_e('Anterior', 'flavor-chat-ia'); ?>
                    </a>
                    <h2 class="reservas-calendario-titulo"><?php echo esc_html(ucfirst($nombre_mes)); ?></h2>
                    <a href="?mes=<?php echo esc_attr($mes_siguiente); ?>&anio=<?php echo esc_attr($anio_siguiente); ?>" class="reservas-btn reservas-btn-secondary">
                        <?php esc_html_e('Siguiente', 'flavor-chat-ia'); ?> &rarr;
                    </a>
                </div>

                <div class="reservas-calendario-grid">
                    <?php foreach ($dias_semana as $nombre_dia): ?>
                        <div class="reservas-calendario-dia-header"><?php echo esc_html($nombre_dia); ?></div>
                    <?php endforeach; ?>

                    <?php
                    // Espacios vacíos antes del primer día
                    for ($espacio = 1; $espacio < $dia_semana_inicio; $espacio++): ?>
                        <div class="reservas-calendario-dia"></div>
                    <?php endfor; ?>

                    <?php for ($numero_dia = 1; $numero_dia <= $dias_en_mes; $numero_dia++):
                        $fecha_dia = sprintf('%04d-%02d-%02d', $anio_actual, $mes_actual, $numero_dia);
                        $es_hoy = ($fecha_dia === $fecha_hoy);
                        $es_pasado = ($fecha_dia < $fecha_hoy);

                        $hora_apertura = $this->get_setting('hora_apertura', '09:00');
                        $hora_cierre = $this->get_setting('hora_cierre', '22:00');
                        $ocupacion_dia = $this->obtener_ocupacion_en_franja($fecha_dia, $hora_apertura, $hora_cierre);
                        $porcentaje_ocupacion = ($capacidad_maxima > 0) ? ($ocupacion_dia / $capacidad_maxima) * 100 : 0;

                        $clase_estado = 'disponible';
                        if ($es_pasado) {
                            $clase_estado = 'pasado';
                        } elseif ($porcentaje_ocupacion >= 100) {
                            $clase_estado = 'lleno';
                        } elseif ($porcentaje_ocupacion >= 50) {
                            $clase_estado = 'parcial';
                        }
                    ?>
                        <div class="reservas-calendario-dia <?php echo esc_attr($clase_estado); ?> <?php echo $es_hoy ? 'hoy' : ''; ?>"
                             data-fecha="<?php echo esc_attr($fecha_dia); ?>"
                             title="<?php echo esc_attr(sprintf(__('Ocupación: %d%%', 'flavor-chat-ia'), $porcentaje_ocupacion)); ?>">
                            <span><?php echo esc_html($numero_dia); ?></span>
                        </div>
                    <?php endfor; ?>
                </div>

                <?php if ($atributos['mostrar_franjas'] === 'si'): ?>
                <div class="reservas-franjas" id="reservas-franjas-detalle">
                    <h4><?php esc_html_e('Selecciona un día para ver las franjas disponibles', 'flavor-chat-ia'); ?></h4>
                </div>
                <?php endif; ?>

                <div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <span><span style="display:inline-block;width:12px;height:12px;background:#d1fae5;border-radius:2px;margin-right:4px;"></span><?php esc_html_e('Disponible', 'flavor-chat-ia'); ?></span>
                    <span><span style="display:inline-block;width:12px;height:12px;background:#fef3c7;border-radius:2px;margin-right:4px;"></span><?php esc_html_e('Parcialmente ocupado', 'flavor-chat-ia'); ?></span>
                    <span><span style="display:inline-block;width:12px;height:12px;background:#fee2e2;border-radius:2px;margin-right:4px;"></span><?php esc_html_e('Completo', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var diasCalendario = document.querySelectorAll('.reservas-calendario-dia[data-fecha]');
            diasCalendario.forEach(function(elementoDia) {
                if (!elementoDia.classList.contains('pasado') && !elementoDia.classList.contains('lleno')) {
                    elementoDia.addEventListener('click', function() {
                        var fechaSeleccionada = this.getAttribute('data-fecha');
                        if (typeof reservasAjax !== 'undefined') {
                            var contenedorFranjas = document.getElementById('reservas-franjas-detalle');
                            contenedorFranjas.innerHTML = '<p><?php esc_html_e('Cargando franjas...', 'flavor-chat-ia'); ?></p>';

                            var formData = new FormData();
                            formData.append('action', 'reservas_disponibilidad');
                            formData.append('fecha', fechaSeleccionada);
                            formData.append('nonce', reservasAjax.nonce);

                            fetch(reservasAjax.ajaxurl, {
                                method: 'POST',
                                body: formData
                            })
                            .then(function(respuesta) { return respuesta.json(); })
                            .then(function(datos) {
                                if (datos.success && datos.data.franjas) {
                                    var htmlFranjas = '<h4><?php esc_html_e('Franjas para', 'flavor-chat-ia'); ?> ' + fechaSeleccionada + '</h4>';
                                    datos.data.franjas.forEach(function(franja) {
                                        var claseDisponible = franja.disponible ? 'disponible' : 'no-disponible';
                                        htmlFranjas += '<div class="reservas-franja">';
                                        htmlFranjas += '<span class="reservas-franja-hora">' + franja.hora_inicio + ' - ' + franja.hora_fin + '</span>';
                                        htmlFranjas += '<span class="reservas-franja-plazas">' + franja.plazas_libres + ' <?php esc_html_e('plazas', 'flavor-chat-ia'); ?></span>';
                                        htmlFranjas += '</div>';
                                    });
                                    contenedorFranjas.innerHTML = htmlFranjas;
                                }
                            });
                        }
                    });
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [reservas_formulario id="X"] - Formulario de reserva
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function shortcode_formulario($atts) {
        $this->encolar_assets_shortcodes();

        $atributos = shortcode_atts([
            'id'             => '',
            'tipo_servicio'  => '',
            'redirect'       => '',
        ], $atts);

        $tipos_servicio = $this->get_setting('tipos_servicio', []);
        $hora_apertura = $this->get_setting('hora_apertura', '09:00');
        $hora_cierre = $this->get_setting('hora_cierre', '22:00');
        $dias_antelacion = $this->get_setting('dias_antelacion', 30);

        $fecha_minima = current_time('Y-m-d');
        $fecha_maxima = date('Y-m-d', strtotime("+{$dias_antelacion} days"));

        $usuario_actual = wp_get_current_user();
        $nombre_predeterminado = $usuario_actual->ID ? $usuario_actual->display_name : '';
        $email_predeterminado = $usuario_actual->ID ? $usuario_actual->user_email : '';

        $tipo_preseleccionado = sanitize_text_field($atributos['tipo_servicio']);
        if (empty($tipo_preseleccionado) && isset($_GET['reservar'])) {
            $tipo_preseleccionado = sanitize_text_field($_GET['reservar']);
        }

        ob_start();
        ?>
        <div class="reservas-container">
            <div class="reservas-form" id="reservas-formulario">
                <div id="reservas-mensajes"></div>

                <form id="form-nueva-reserva" method="post">
                    <?php wp_nonce_field('reservas_crear_nonce', 'reservas_nonce'); ?>

                    <div class="reservas-form-group">
                        <label class="reservas-form-label" for="tipo_servicio"><?php esc_html_e('Tipo de servicio', 'flavor-chat-ia'); ?> *</label>
                        <select name="tipo_servicio" id="tipo_servicio" class="reservas-form-select" required>
                            <option value=""><?php esc_html_e('Selecciona un servicio', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($tipos_servicio as $clave => $etiqueta): ?>
                                <option value="<?php echo esc_attr($clave); ?>" <?php selected($tipo_preseleccionado, $clave); ?>>
                                    <?php echo esc_html($etiqueta); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="reservas-form-row">
                        <div class="reservas-form-group">
                            <label class="reservas-form-label" for="nombre_cliente"><?php esc_html_e('Nombre completo', 'flavor-chat-ia'); ?> *</label>
                            <input type="text" name="nombre_cliente" id="nombre_cliente" class="reservas-form-input"
                                   value="<?php echo esc_attr($nombre_predeterminado); ?>" required>
                        </div>
                        <div class="reservas-form-group">
                            <label class="reservas-form-label" for="email_cliente"><?php esc_html_e('Email', 'flavor-chat-ia'); ?> *</label>
                            <input type="email" name="email_cliente" id="email_cliente" class="reservas-form-input"
                                   value="<?php echo esc_attr($email_predeterminado); ?>" required>
                        </div>
                    </div>

                    <div class="reservas-form-group">
                        <label class="reservas-form-label" for="telefono_cliente"><?php esc_html_e('Teléfono', 'flavor-chat-ia'); ?></label>
                        <input type="tel" name="telefono_cliente" id="telefono_cliente" class="reservas-form-input">
                    </div>

                    <div class="reservas-form-row">
                        <div class="reservas-form-group">
                            <label class="reservas-form-label" for="fecha_reserva"><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?> *</label>
                            <input type="date" name="fecha_reserva" id="fecha_reserva" class="reservas-form-input"
                                   min="<?php echo esc_attr($fecha_minima); ?>"
                                   max="<?php echo esc_attr($fecha_maxima); ?>" required>
                        </div>
                        <div class="reservas-form-group">
                            <label class="reservas-form-label" for="num_personas"><?php esc_html_e('Número de personas', 'flavor-chat-ia'); ?> *</label>
                            <input type="number" name="num_personas" id="num_personas" class="reservas-form-input"
                                   min="1" max="<?php echo esc_attr($this->get_setting('capacidad_maxima', 50)); ?>" value="1" required>
                        </div>
                    </div>

                    <div class="reservas-form-row">
                        <div class="reservas-form-group">
                            <label class="reservas-form-label" for="hora_inicio"><?php esc_html_e('Hora de inicio', 'flavor-chat-ia'); ?> *</label>
                            <input type="time" name="hora_inicio" id="hora_inicio" class="reservas-form-input"
                                   min="<?php echo esc_attr($hora_apertura); ?>"
                                   max="<?php echo esc_attr($hora_cierre); ?>" required>
                        </div>
                        <div class="reservas-form-group">
                            <label class="reservas-form-label" for="hora_fin"><?php esc_html_e('Hora de fin', 'flavor-chat-ia'); ?></label>
                            <input type="time" name="hora_fin" id="hora_fin" class="reservas-form-input"
                                   min="<?php echo esc_attr($hora_apertura); ?>"
                                   max="<?php echo esc_attr($hora_cierre); ?>">
                            <small style="color:#6b7280;"><?php esc_html_e('Opcional. Se calculará automáticamente.', 'flavor-chat-ia'); ?></small>
                        </div>
                    </div>

                    <div class="reservas-form-group">
                        <label class="reservas-form-label" for="notas"><?php esc_html_e('Notas adicionales', 'flavor-chat-ia'); ?></label>
                        <textarea name="notas" id="notas" class="reservas-form-textarea" rows="3"
                                  placeholder="<?php esc_attr_e('Alergias, preferencias, etc.', 'flavor-chat-ia'); ?>"></textarea>
                    </div>

                    <input type="hidden" name="redirect" value="<?php echo esc_attr($atributos['redirect']); ?>">

                    <button type="submit" class="reservas-btn reservas-btn-primary" style="width:100%; padding: 0.875rem;">
                        <?php esc_html_e('Confirmar Reserva', 'flavor-chat-ia'); ?>
                    </button>
                </form>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var formularioReserva = document.getElementById('form-nueva-reserva');
            if (formularioReserva) {
                formularioReserva.addEventListener('submit', function(evento) {
                    evento.preventDefault();

                    var contenedorMensajes = document.getElementById('reservas-mensajes');
                    var botonEnviar = formularioReserva.querySelector('button[type="submit"]');
                    var textoOriginalBoton = botonEnviar.textContent;

                    botonEnviar.disabled = true;
                    botonEnviar.textContent = '<?php esc_html_e('Procesando...', 'flavor-chat-ia'); ?>';
                    contenedorMensajes.innerHTML = '';

                    var formData = new FormData(formularioReserva);
                    formData.append('action', 'reservas_crear');

                    fetch(reservasAjax.ajaxurl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(respuesta) { return respuesta.json(); })
                    .then(function(datos) {
                        if (datos.success) {
                            contenedorMensajes.innerHTML = '<div class="reservas-mensaje reservas-mensaje-success">' + datos.data.mensaje + '</div>';
                            formularioReserva.reset();
                            if (datos.data.redirect) {
                                setTimeout(function() {
                                    window.location.href = datos.data.redirect;
                                }, 2000);
                            }
                        } else {
                            contenedorMensajes.innerHTML = '<div class="reservas-mensaje reservas-mensaje-error">' + datos.data.error + '</div>';
                        }
                    })
                    .catch(function(error) {
                        contenedorMensajes.innerHTML = '<div class="reservas-mensaje reservas-mensaje-error"><?php esc_html_e('Error de conexión. Inténtalo de nuevo.', 'flavor-chat-ia'); ?></div>';
                    })
                    .finally(function() {
                        botonEnviar.disabled = false;
                        botonEnviar.textContent = textoOriginalBoton;
                    });
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [reservas_mis_reservas] - Reservas del usuario actual
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function shortcode_mis_reservas($atts) {
        $this->encolar_assets_shortcodes();

        $atributos = shortcode_atts([
            'limite'       => '20',
            'estado'       => '',
            'mostrar_pasadas' => 'no',
        ], $atts);

        $identificador_usuario = get_current_user_id();

        if (!$identificador_usuario) {
            return '<div class="reservas-container">
                <div class="reservas-mensaje reservas-mensaje-info">
                    ' . sprintf(
                        __('Debes <a href="%s">iniciar sesión</a> para ver tus reservas.', 'flavor-chat-ia'),
                        wp_login_url(get_permalink())
                    ) . '
                </div>
            </div>';
        }

        global $wpdb;
        $nombre_tabla = $wpdb->prefix . 'flavor_reservas';

        $condiciones_where = ['user_id = %d'];
        $valores_preparados = [$identificador_usuario];

        if (!empty($atributos['estado'])) {
            $condiciones_where[] = 'estado = %s';
            $valores_preparados[] = sanitize_text_field($atributos['estado']);
        }

        if ($atributos['mostrar_pasadas'] === 'no') {
            $condiciones_where[] = 'fecha_reserva >= %s';
            $valores_preparados[] = current_time('Y-m-d');
        }

        $limite = absint($atributos['limite']);
        $valores_preparados[] = $limite;

        $consulta_sql = "SELECT * FROM $nombre_tabla WHERE " . implode(' AND ', $condiciones_where) .
                       " ORDER BY fecha_reserva ASC, hora_inicio ASC LIMIT %d";

        $reservas_usuario = $wpdb->get_results($wpdb->prepare($consulta_sql, ...$valores_preparados));

        if (empty($reservas_usuario)) {
            return $this->renderizar_mensaje_vacio(__('No tienes reservas activas.', 'flavor-chat-ia'));
        }

        $tipos_servicio = $this->get_setting('tipos_servicio', []);
        $estados_reserva = $this->get_setting('estados_reserva', []);

        ob_start();
        ?>
        <div class="reservas-container">
            <div class="reservas-grid">
                <?php foreach ($reservas_usuario as $reserva):
                    $etiqueta_tipo = $tipos_servicio[$reserva->tipo_servicio] ?? $reserva->tipo_servicio;
                    $etiqueta_estado = $estados_reserva[$reserva->estado] ?? ucfirst($reserva->estado);
                    $fecha_formateada = date_i18n(get_option('date_format'), strtotime($reserva->fecha_reserva));
                ?>
                <div class="reservas-card">
                    <div class="reservas-card-header">
                        <span class="reservas-card-icon">📅</span>
                        <div>
                            <h3 class="reservas-card-title"><?php echo esc_html($etiqueta_tipo); ?></h3>
                            <span class="reservas-badge reservas-badge-<?php echo esc_attr($reserva->estado); ?>">
                                <?php echo esc_html($etiqueta_estado); ?>
                            </span>
                        </div>
                    </div>
                    <div class="reservas-card-meta">
                        <p><strong><?php esc_html_e('Fecha:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($fecha_formateada); ?></p>
                        <p><strong><?php esc_html_e('Hora:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html(substr($reserva->hora_inicio, 0, 5)); ?> - <?php echo esc_html(substr($reserva->hora_fin, 0, 5)); ?></p>
                        <p><strong><?php esc_html_e('Personas:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($reserva->num_personas); ?></p>
                        <?php if (!empty($reserva->notas)): ?>
                        <p><strong><?php esc_html_e('Notas:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($reserva->notas); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (in_array($reserva->estado, ['pendiente', 'confirmada'], true)): ?>
                    <a href="?cancelar_reserva=<?php echo esc_attr($reserva->id); ?>&_wpnonce=<?php echo wp_create_nonce('cancelar_reserva_' . $reserva->id); ?>"
                       class="reservas-btn reservas-btn-danger"
                       onclick="return confirm('<?php esc_attr_e('¿Estás seguro de que deseas cancelar esta reserva?', 'flavor-chat-ia'); ?>');">
                        <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [reservas_cancelar id="X"] - Cancelar una reserva específica
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function shortcode_cancelar($atts) {
        $this->encolar_assets_shortcodes();

        $atributos = shortcode_atts([
            'id' => '',
        ], $atts);

        // Procesar cancelación desde URL
        if (isset($_GET['cancelar_reserva']) && isset($_GET['_wpnonce'])) {
            $identificador_reserva = absint($_GET['cancelar_reserva']);

            if (wp_verify_nonce($_GET['_wpnonce'], 'cancelar_reserva_' . $identificador_reserva)) {
                $resultado_cancelacion = $this->action_cancelar_reserva(['reserva_id' => $identificador_reserva]);

                if ($resultado_cancelacion['success']) {
                    return '<div class="reservas-container">
                        <div class="reservas-mensaje reservas-mensaje-success">' . esc_html($resultado_cancelacion['mensaje']) . '</div>
                    </div>';
                } else {
                    return '<div class="reservas-container">
                        <div class="reservas-mensaje reservas-mensaje-error">' . esc_html($resultado_cancelacion['error']) . '</div>
                    </div>';
                }
            }
        }

        // Si se proporciona un ID específico
        $identificador_reserva = absint($atributos['id']);
        if (!$identificador_reserva) {
            return '<div class="reservas-container">
                <div class="reservas-mensaje reservas-mensaje-error">' .
                esc_html__('No se especificó ninguna reserva para cancelar.', 'flavor-chat-ia') .
                '</div>
            </div>';
        }

        global $wpdb;
        $nombre_tabla = $wpdb->prefix . 'flavor_reservas';
        $reserva = $wpdb->get_row($wpdb->prepare("SELECT * FROM $nombre_tabla WHERE id = %d", $identificador_reserva));

        if (!$reserva) {
            return '<div class="reservas-container">
                <div class="reservas-mensaje reservas-mensaje-error">' .
                esc_html__('Reserva no encontrada.', 'flavor-chat-ia') .
                '</div>
            </div>';
        }

        $tipos_servicio = $this->get_setting('tipos_servicio', []);
        $etiqueta_tipo = $tipos_servicio[$reserva->tipo_servicio] ?? $reserva->tipo_servicio;
        $fecha_formateada = date_i18n(get_option('date_format'), strtotime($reserva->fecha_reserva));

        ob_start();
        ?>
        <div class="reservas-container">
            <div class="reservas-form">
                <h3 style="margin-top:0;"><?php esc_html_e('Cancelar Reserva', 'flavor-chat-ia'); ?></h3>

                <div class="reservas-card" style="box-shadow:none;border:2px solid #e5e7eb;">
                    <p><strong><?php esc_html_e('Servicio:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($etiqueta_tipo); ?></p>
                    <p><strong><?php esc_html_e('Fecha:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($fecha_formateada); ?></p>
                    <p><strong><?php esc_html_e('Hora:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html(substr($reserva->hora_inicio, 0, 5)); ?> - <?php echo esc_html(substr($reserva->hora_fin, 0, 5)); ?></p>
                    <p><strong><?php esc_html_e('Personas:', 'flavor-chat-ia'); ?></strong> <?php echo esc_html($reserva->num_personas); ?></p>
                    <p><strong><?php esc_html_e('Estado actual:', 'flavor-chat-ia'); ?></strong>
                        <span class="reservas-badge reservas-badge-<?php echo esc_attr($reserva->estado); ?>">
                            <?php echo esc_html(ucfirst($reserva->estado)); ?>
                        </span>
                    </p>
                </div>

                <?php if (in_array($reserva->estado, ['pendiente', 'confirmada'], true)): ?>
                <form method="get" style="margin-top:1.5rem;">
                    <input type="hidden" name="cancelar_reserva" value="<?php echo esc_attr($identificador_reserva); ?>">
                    <?php wp_nonce_field('cancelar_reserva_' . $identificador_reserva, '_wpnonce', false); ?>

                    <p style="color:#6b7280;margin-bottom:1rem;">
                        <?php esc_html_e('¿Estás seguro de que deseas cancelar esta reserva? Esta acción no se puede deshacer.', 'flavor-chat-ia'); ?>
                    </p>

                    <button type="submit" class="reservas-btn reservas-btn-danger" style="width:100%;">
                        <?php esc_html_e('Confirmar Cancelación', 'flavor-chat-ia'); ?>
                    </button>
                </form>
                <?php else: ?>
                <div class="reservas-mensaje reservas-mensaje-info" style="margin-top:1rem;">
                    <?php esc_html_e('Esta reserva no puede ser cancelada porque ya está cancelada o completada.', 'flavor-chat-ia'); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [reservas_disponibilidad] - Verificar disponibilidad
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    public function shortcode_disponibilidad($atts) {
        $this->encolar_assets_shortcodes();

        $atributos = shortcode_atts([
            'tipo_servicio' => '',
            'fecha'         => '',
            'mostrar_formulario' => 'si',
        ], $atts);

        $hora_apertura = $this->get_setting('hora_apertura', '09:00');
        $hora_cierre = $this->get_setting('hora_cierre', '22:00');
        $dias_antelacion = $this->get_setting('dias_antelacion', 30);
        $fecha_minima = current_time('Y-m-d');
        $fecha_maxima = date('Y-m-d', strtotime("+{$dias_antelacion} days"));

        ob_start();
        ?>
        <div class="reservas-container">
            <?php if ($atributos['mostrar_formulario'] === 'si'): ?>
            <div class="reservas-form">
                <h3 style="margin-top:0;"><?php esc_html_e('Consultar Disponibilidad', 'flavor-chat-ia'); ?></h3>

                <form id="form-disponibilidad">
                    <div class="reservas-form-row">
                        <div class="reservas-form-group">
                            <label class="reservas-form-label" for="disp_fecha"><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?> *</label>
                            <input type="date" name="fecha" id="disp_fecha" class="reservas-form-input"
                                   min="<?php echo esc_attr($fecha_minima); ?>"
                                   max="<?php echo esc_attr($fecha_maxima); ?>"
                                   value="<?php echo esc_attr($atributos['fecha'] ?: $fecha_minima); ?>" required>
                        </div>
                        <div class="reservas-form-group">
                            <label class="reservas-form-label" for="disp_personas"><?php esc_html_e('Personas', 'flavor-chat-ia'); ?></label>
                            <input type="number" name="num_personas" id="disp_personas" class="reservas-form-input"
                                   min="1" value="1">
                        </div>
                    </div>

                    <div class="reservas-form-row">
                        <div class="reservas-form-group">
                            <label class="reservas-form-label" for="disp_hora_inicio"><?php esc_html_e('Hora inicio', 'flavor-chat-ia'); ?></label>
                            <input type="time" name="hora_inicio" id="disp_hora_inicio" class="reservas-form-input"
                                   min="<?php echo esc_attr($hora_apertura); ?>"
                                   max="<?php echo esc_attr($hora_cierre); ?>"
                                   value="<?php echo esc_attr($hora_apertura); ?>">
                        </div>
                        <div class="reservas-form-group">
                            <label class="reservas-form-label" for="disp_hora_fin"><?php esc_html_e('Hora fin', 'flavor-chat-ia'); ?></label>
                            <input type="time" name="hora_fin" id="disp_hora_fin" class="reservas-form-input"
                                   min="<?php echo esc_attr($hora_apertura); ?>"
                                   max="<?php echo esc_attr($hora_cierre); ?>"
                                   value="<?php echo esc_attr($hora_cierre); ?>">
                        </div>
                    </div>

                    <button type="submit" class="reservas-btn reservas-btn-primary" style="width:100%;">
                        <?php esc_html_e('Consultar Disponibilidad', 'flavor-chat-ia'); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <div id="resultado-disponibilidad" style="margin-top:1.5rem;"></div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var formularioDisponibilidad = document.getElementById('form-disponibilidad');
            if (formularioDisponibilidad) {
                formularioDisponibilidad.addEventListener('submit', function(evento) {
                    evento.preventDefault();

                    var contenedorResultado = document.getElementById('resultado-disponibilidad');
                    var botonConsultar = formularioDisponibilidad.querySelector('button[type="submit"]');
                    var textoOriginalBoton = botonConsultar.textContent;

                    botonConsultar.disabled = true;
                    botonConsultar.textContent = '<?php esc_html_e('Consultando...', 'flavor-chat-ia'); ?>';

                    var formData = new FormData(formularioDisponibilidad);
                    formData.append('action', 'reservas_disponibilidad');
                    formData.append('nonce', reservasAjax.nonce);

                    fetch(reservasAjax.ajaxurl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(respuesta) { return respuesta.json(); })
                    .then(function(datos) {
                        if (datos.success) {
                            var resultado = datos.data;
                            var claseResultado = resultado.disponible ? 'disponible' : 'no-disponible';
                            var iconoResultado = resultado.disponible ? '✅' : '❌';

                            var htmlResultado = '<div class="reservas-disponibilidad-resultado ' + claseResultado + '">';
                            htmlResultado += '<div class="reservas-disponibilidad-icono">' + iconoResultado + '</div>';
                            htmlResultado += '<h3>' + (resultado.disponible ? '<?php esc_html_e('¡Disponible!', 'flavor-chat-ia'); ?>' : '<?php esc_html_e('No disponible', 'flavor-chat-ia'); ?>') + '</h3>';
                            htmlResultado += '<p>' + resultado.mensaje + '</p>';

                            if (resultado.franjas && resultado.franjas.length > 0) {
                                htmlResultado += '<div class="reservas-franjas" style="text-align:left;margin-top:1rem;">';
                                htmlResultado += '<h4><?php esc_html_e('Franjas horarias:', 'flavor-chat-ia'); ?></h4>';
                                resultado.franjas.forEach(function(franja) {
                                    var claseFranja = franja.disponible ? 'disponible' : '';
                                    htmlResultado += '<div class="reservas-franja ' + claseFranja + '">';
                                    htmlResultado += '<span class="reservas-franja-hora">' + franja.hora_inicio + ' - ' + franja.hora_fin + '</span>';
                                    htmlResultado += '<span class="reservas-franja-plazas">' + franja.plazas_libres + ' <?php esc_html_e('plazas libres', 'flavor-chat-ia'); ?></span>';
                                    htmlResultado += '</div>';
                                });
                                htmlResultado += '</div>';
                            }

                            htmlResultado += '</div>';
                            contenedorResultado.innerHTML = htmlResultado;
                        } else {
                            contenedorResultado.innerHTML = '<div class="reservas-mensaje reservas-mensaje-error">' + datos.data.error + '</div>';
                        }
                    })
                    .catch(function(error) {
                        contenedorResultado.innerHTML = '<div class="reservas-mensaje reservas-mensaje-error"><?php esc_html_e('Error de conexión.', 'flavor-chat-ia'); ?></div>';
                    })
                    .finally(function() {
                        botonConsultar.disabled = false;
                        botonConsultar.textContent = textoOriginalBoton;
                    });
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza un mensaje vacío con estilo
     */
    private function renderizar_mensaje_vacio($mensaje) {
        return '<div class="reservas-container">
            <div class="reservas-empty">
                <div class="reservas-empty-icon">📭</div>
                <p>' . esc_html($mensaje) . '</p>
            </div>
        </div>';
    }

    // =========================================================================
    // AJAX Handlers para Shortcodes
    // =========================================================================

    /**
     * AJAX: Crear reserva desde shortcode
     */
    public function ajax_crear_reserva() {
        if ((isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'reservas_nonce')) || isset($_POST['recurso_id'])) {
            if (class_exists('Flavor_Reservas_Frontend_Controller')) {
                Flavor_Reservas_Frontend_Controller::get_instance()->ajax_crear_reserva();
                return;
            }
        }

        // Verificar nonce
        if (!wp_verify_nonce($_POST['reservas_nonce'] ?? '', 'reservas_crear_nonce')) {
            wp_send_json_error(['error' => __('Error de seguridad. Recarga la página.', 'flavor-chat-ia')]);
        }

        $parametros_reserva = [
            'tipo_servicio'    => sanitize_text_field($_POST['tipo_servicio'] ?? ''),
            'nombre_cliente'   => sanitize_text_field($_POST['nombre_cliente'] ?? ''),
            'email_cliente'    => sanitize_email($_POST['email_cliente'] ?? ''),
            'telefono_cliente' => sanitize_text_field($_POST['telefono_cliente'] ?? ''),
            'fecha_reserva'    => sanitize_text_field($_POST['fecha_reserva'] ?? ''),
            'hora_inicio'      => sanitize_text_field($_POST['hora_inicio'] ?? ''),
            'hora_fin'         => sanitize_text_field($_POST['hora_fin'] ?? ''),
            'num_personas'     => absint($_POST['num_personas'] ?? 1),
            'notas'            => sanitize_textarea_field($_POST['notas'] ?? ''),
        ];

        $resultado = $this->action_crear_reserva($parametros_reserva);

        if ($resultado['success']) {
            $datos_respuesta = [
                'mensaje' => $resultado['mensaje'],
                'reserva' => $resultado['reserva'],
            ];

            if (!empty($_POST['redirect'])) {
                $datos_respuesta['redirect'] = esc_url($_POST['redirect']);
            }

            wp_send_json_success($datos_respuesta);
        } else {
            wp_send_json_error(['error' => $resultado['error']]);
        }
    }

    /**
     * AJAX: Cancelar reserva desde shortcode
     */
    public function ajax_cancelar_reserva() {
        if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'reservas_nonce')) {
            if (class_exists('Flavor_Reservas_Frontend_Controller')) {
                Flavor_Reservas_Frontend_Controller::get_instance()->ajax_cancelar_reserva();
                return;
            }
        }

        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'reservas_shortcode_nonce')) {
            wp_send_json_error(['error' => __('Error de seguridad.', 'flavor-chat-ia')]);
        }

        $identificador_reserva = absint($_POST['reserva_id'] ?? 0);

        if (!$identificador_reserva) {
            wp_send_json_error(['error' => __('ID de reserva no válido.', 'flavor-chat-ia')]);
        }

        $resultado = $this->action_cancelar_reserva(['reserva_id' => $identificador_reserva]);

        if ($resultado['success']) {
            wp_send_json_success(['mensaje' => $resultado['mensaje']]);
        } else {
            wp_send_json_error(['error' => $resultado['error']]);
        }
    }

    /**
     * AJAX: Consultar disponibilidad desde shortcode
     */
    public function ajax_consultar_disponibilidad() {
        if ((isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'reservas_nonce')) || isset($_POST['recurso_id'])) {
            if (class_exists('Flavor_Reservas_Frontend_Controller')) {
                Flavor_Reservas_Frontend_Controller::get_instance()->ajax_verificar_disponibilidad();
                return;
            }
        }

        // Verificar nonce (permite tanto el nonce del shortcode como el general)
        $nonce_valido = wp_verify_nonce($_POST['nonce'] ?? '', 'reservas_shortcode_nonce');

        if (!$nonce_valido) {
            wp_send_json_error(['error' => __('Error de seguridad.', 'flavor-chat-ia')]);
        }

        $parametros_consulta = [
            'fecha_reserva' => sanitize_text_field($_POST['fecha'] ?? ''),
            'hora_inicio'   => sanitize_text_field($_POST['hora_inicio'] ?? ''),
            'hora_fin'      => sanitize_text_field($_POST['hora_fin'] ?? ''),
            'num_personas'  => absint($_POST['num_personas'] ?? 1),
        ];

        $resultado = $this->action_disponibilidad($parametros_consulta);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error(['error' => $resultado['error']]);
        }
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

        // Migas de pan
        ?>
        <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
            <a href="<?php echo admin_url('admin.php?page=reservas-calendario'); ?>" style="color: #2271b1; text-decoration: none;">
                <span class="dashicons dashicons-calendar" style="font-size: 14px; vertical-align: middle;"></span>
                <?php _e('Reservas', 'flavor-chat-ia'); ?>
            </a>
            <span style="color: #646970; margin: 0 5px;">›</span>
            <span style="color: #1d2327;"><?php _e('Configuración', 'flavor-chat-ia'); ?></span>
        </nav>
        <?php

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
        $nombre_tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

        if (!Flavor_Chat_Helpers::tabla_existe($nombre_tabla_reservas)
            || !Flavor_Chat_Helpers::tabla_existe($nombre_tabla_recursos)) {
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
        $es_propietario   = ($reserva_encontrada->user_id && (int) $reserva_encontrada->user_id === $identificador_usuario_actual);
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
        $es_propietario   = ($reserva_encontrada->user_id && (int) $reserva_encontrada->user_id === $identificador_usuario_actual);
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

    /**
     * Define las páginas del módulo para V3
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Reservas', 'flavor-chat-ia'),
                'slug' => 'reservas',
                'content' => '<h1>' . __('Reservas', 'flavor-chat-ia') . '</h1>
<p>' . __('Gestiona tus reservas de forma fácil y rápida', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="reservas" action="listar_reservas" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Nueva Reserva', 'flavor-chat-ia'),
                'slug' => 'nueva-reserva',
                'content' => '<h1>' . __('Nueva Reserva', 'flavor-chat-ia') . '</h1>
<p>' . __('Crea una nueva reserva', 'flavor-chat-ia') . '</p>

[flavor_module_form module="reservas" action="crear_reserva"]',
                'parent' => 'reservas',
            ],
            [
                'title' => __('Mis Reservas', 'flavor-chat-ia'),
                'slug' => 'mis-reservas',
                'content' => '<h1>' . __('Mis Reservas', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta el estado de tus reservas', 'flavor-chat-ia') . '</p>

[flavor_module_dashboard module="reservas" action="mis_reservas"]',
                'parent' => 'reservas',
            ],
            [
                'title' => __('Calendario', 'flavor-chat-ia'),
                'slug' => 'calendario-reservas',
                'content' => '<h1>' . __('Calendario de Reservas', 'flavor-chat-ia') . '</h1>
<p>' . __('Consulta la disponibilidad', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="reservas" action="calendario"]',
                'parent' => 'reservas',
            ],
        ];
    }

    /**
     * Registrar páginas de administración (ocultas del sidebar)
     */
    public function registrar_paginas_admin() {
        $capability = 'manage_options';

        // Dashboard - página oculta
        add_submenu_page(
            null,
            __('Dashboard Reservas', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            $capability,
            'reservas',
            [$this, 'render_pagina_dashboard']
        );

        // Listado de reservas - página oculta
        add_submenu_page(
            null,
            __('Listado de Reservas', 'flavor-chat-ia'),
            __('Reservas', 'flavor-chat-ia'),
            $capability,
            'reservas-listado',
            [$this, 'render_pagina_reservas']
        );

        // Recursos - página oculta
        add_submenu_page(
            null,
            __('Recursos Reservas', 'flavor-chat-ia'),
            __('Recursos', 'flavor-chat-ia'),
            $capability,
            'reservas-recursos',
            [$this, 'render_pagina_recursos']
        );

        // Calendario - página oculta
        add_submenu_page(
            null,
            __('Calendario Reservas', 'flavor-chat-ia'),
            __('Calendario', 'flavor-chat-ia'),
            $capability,
            'reservas-calendario',
            [$this, 'render_pagina_calendario']
        );
    }

    /**
     * Renderizar página dashboard
     */
    public function render_pagina_dashboard() {
        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Dashboard Reservas', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Panel de administración del módulo de reservas.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Renderizar página de reservas
     */
    public function render_pagina_reservas() {
        $views_path = dirname(__FILE__) . '/views/reservas.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Reservas', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de recursos
     */
    public function render_pagina_recursos() {
        $views_path = dirname(__FILE__) . '/views/recursos.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Recursos', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de calendario
     */
    public function render_pagina_calendario() {
        $views_path = dirname(__FILE__) . '/views/calendario.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Calendario de Reservas', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    // =========================================================================
    // MÉTODOS RENDER_TAB_* PARA DYNAMIC PAGES
    // =========================================================================

    /**
     * Renderiza el tab de recursos disponibles
     *
     * @param int $usuario_id ID del usuario actual
     */
    public function render_tab_recursos($usuario_id = 0) {
        global $wpdb;
        $tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

        // Verificar si existe la tabla
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_recursos)) !== $tabla_recursos) {
            echo '<div class="fmd-empty-state">';
            echo '<span class="dashicons dashicons-admin-home" style="font-size: 48px; color: #9ca3af;"></span>';
            echo '<h3>' . esc_html__('Módulo en configuración', 'flavor-chat-ia') . '</h3>';
            echo '<p>' . esc_html__('Los recursos reservables se están configurando.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        // Obtener recursos activos
        $recursos = $wpdb->get_results(
            "SELECT * FROM $tabla_recursos WHERE activo = 1 ORDER BY nombre ASC"
        );

        if (empty($recursos)) {
            echo '<div class="fmd-empty-state">';
            echo '<span class="dashicons dashicons-admin-home" style="font-size: 48px; color: #9ca3af;"></span>';
            echo '<h3>' . esc_html__('No hay recursos disponibles', 'flavor-chat-ia') . '</h3>';
            echo '<p>' . esc_html__('Actualmente no hay espacios o recursos configurados para reservar.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        ?>
        <div class="reservas-recursos-grid">
            <?php foreach ($recursos as $recurso): ?>
                <div class="reservas-recurso-card">
                    <?php if (!empty($recurso->imagen)): ?>
                        <div class="recurso-imagen">
                            <img src="<?php echo esc_url($recurso->imagen); ?>" alt="<?php echo esc_attr($recurso->nombre); ?>">
                        </div>
                    <?php else: ?>
                        <div class="recurso-imagen recurso-imagen-placeholder">
                            <span class="dashicons dashicons-admin-home"></span>
                        </div>
                    <?php endif; ?>

                    <div class="recurso-info">
                        <h4 class="recurso-nombre"><?php echo esc_html($recurso->nombre); ?></h4>

                        <?php if (!empty($recurso->tipo)): ?>
                            <span class="recurso-tipo"><?php echo esc_html(ucfirst($recurso->tipo)); ?></span>
                        <?php endif; ?>

                        <?php if (!empty($recurso->descripcion)): ?>
                            <p class="recurso-descripcion"><?php echo esc_html(wp_trim_words($recurso->descripcion, 20)); ?></p>
                        <?php endif; ?>

                        <div class="recurso-meta">
                            <?php if (!empty($recurso->capacidad)): ?>
                                <span class="meta-item">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php printf(esc_html__('%d personas', 'flavor-chat-ia'), $recurso->capacidad); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($recurso->ubicacion)): ?>
                                <span class="meta-item">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($recurso->ubicacion); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="recurso-acciones">
                            <a href="<?php echo esc_url(add_query_arg(['tab' => 'nueva-reserva', 'recurso_id' => $recurso->id], home_url('/mi-portal/reservas/'))); ?>" class="fmd-btn fmd-btn-primary fmd-btn-sm">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php esc_html_e('Reservar', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <style>
        .reservas-recursos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .reservas-recurso-card {
            background: var(--fmd-bg-card, #fff);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid var(--fmd-border, #e5e7eb);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .reservas-recurso-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .recurso-imagen {
            height: 160px;
            overflow: hidden;
            background: var(--fmd-bg-secondary, #f3f4f6);
        }
        .recurso-imagen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .recurso-imagen-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .recurso-imagen-placeholder .dashicons {
            font-size: 48px;
            color: var(--fmd-text-muted, #9ca3af);
        }
        .recurso-info {
            padding: 1.25rem;
        }
        .recurso-nombre {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--fmd-text-primary, #1f2937);
            margin: 0 0 0.5rem 0;
        }
        .recurso-tipo {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--fmd-primary, #2563eb);
            background: var(--fmd-primary-light, #eff6ff);
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            margin-bottom: 0.75rem;
        }
        .recurso-descripcion {
            font-size: 0.875rem;
            color: var(--fmd-text-secondary, #6b7280);
            margin: 0 0 1rem 0;
            line-height: 1.5;
        }
        .recurso-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.8125rem;
            color: var(--fmd-text-muted, #9ca3af);
        }
        .meta-item .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        .recurso-acciones {
            display: flex;
            gap: 0.5rem;
        }
        </style>
        <?php
    }

    /**
     * Renderiza el tab de mis reservas
     *
     * @param int $usuario_id ID del usuario actual
     */
    public function render_tab_mis_reservas($usuario_id = 0) {
        if (!is_user_logged_in()) {
            echo '<div class="fmd-login-required">';
            echo '<span class="dashicons dashicons-lock"></span>';
            echo '<p>' . esc_html__('Inicia sesión para ver tus reservas.', 'flavor-chat-ia') . '</p>';
            echo '<a href="' . esc_url(wp_login_url(home_url('/mi-portal/reservas/'))) . '" class="fmd-btn fmd-btn-primary">' . esc_html__('Iniciar sesión', 'flavor-chat-ia') . '</a>';
            echo '</div>';
            return;
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';
        $usuario_id = $usuario_id ?: get_current_user_id();

        // Verificar si existe la tabla
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_reservas)) !== $tabla_reservas) {
            echo '<div class="fmd-empty-state">';
            echo '<span class="dashicons dashicons-calendar-alt" style="font-size: 48px; color: #9ca3af;"></span>';
            echo '<h3>' . esc_html__('No hay reservas', 'flavor-chat-ia') . '</h3>';
            echo '</div>';
            return;
        }

        // Obtener reservas del usuario
        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, rec.nombre as recurso_nombre, rec.tipo as recurso_tipo, rec.imagen as recurso_imagen
             FROM $tabla_reservas r
             LEFT JOIN $tabla_recursos rec ON r.recurso_id = rec.id
             WHERE r.usuario_id = %d
             ORDER BY r.fecha_inicio DESC
             LIMIT 50",
            $usuario_id
        ));

        if (empty($reservas)) {
            echo '<div class="fmd-empty-state">';
            echo '<span class="dashicons dashicons-calendar-alt" style="font-size: 48px; color: #9ca3af;"></span>';
            echo '<h3>' . esc_html__('No tienes reservas', 'flavor-chat-ia') . '</h3>';
            echo '<p>' . esc_html__('Aún no has realizado ninguna reserva.', 'flavor-chat-ia') . '</p>';
            echo '<a href="' . esc_url(add_query_arg('tab', 'recursos', home_url('/mi-portal/reservas/'))) . '" class="fmd-btn fmd-btn-primary">';
            echo '<span class="dashicons dashicons-plus-alt"></span> ' . esc_html__('Hacer una reserva', 'flavor-chat-ia');
            echo '</a>';
            echo '</div>';
            return;
        }

        $estados_config = [
            'pendiente'  => ['label' => __('Pendiente', 'flavor-chat-ia'), 'class' => 'warning'],
            'confirmada' => ['label' => __('Confirmada', 'flavor-chat-ia'), 'class' => 'success'],
            'completada' => ['label' => __('Completada', 'flavor-chat-ia'), 'class' => 'neutral'],
            'cancelada'  => ['label' => __('Cancelada', 'flavor-chat-ia'), 'class' => 'error'],
        ];

        ?>
        <div class="reservas-mis-reservas">
            <?php foreach ($reservas as $reserva):
                $estado_info = $estados_config[$reserva->estado] ?? ['label' => $reserva->estado, 'class' => 'neutral'];
                $es_futura = strtotime($reserva->fecha_inicio) > time();
                $puede_cancelar = $es_futura && in_array($reserva->estado, ['pendiente', 'confirmada']);
            ?>
                <div class="reserva-item reserva-estado-<?php echo esc_attr($reserva->estado); ?>">
                    <div class="reserva-recurso">
                        <?php if (!empty($reserva->recurso_imagen)): ?>
                            <img src="<?php echo esc_url($reserva->recurso_imagen); ?>" alt="" class="recurso-thumb">
                        <?php else: ?>
                            <div class="recurso-thumb recurso-thumb-placeholder">
                                <span class="dashicons dashicons-admin-home"></span>
                            </div>
                        <?php endif; ?>
                        <div class="reserva-recurso-info">
                            <h4><?php echo esc_html($reserva->recurso_nombre ?: __('Recurso', 'flavor-chat-ia')); ?></h4>
                            <?php if (!empty($reserva->recurso_tipo)): ?>
                                <span class="tipo"><?php echo esc_html(ucfirst($reserva->recurso_tipo)); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="reserva-fechas">
                        <div class="fecha-item">
                            <span class="fecha-label"><?php esc_html_e('Inicio', 'flavor-chat-ia'); ?></span>
                            <span class="fecha-valor"><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($reserva->fecha_inicio))); ?></span>
                        </div>
                        <div class="fecha-item">
                            <span class="fecha-label"><?php esc_html_e('Fin', 'flavor-chat-ia'); ?></span>
                            <span class="fecha-valor"><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($reserva->fecha_fin))); ?></span>
                        </div>
                    </div>

                    <div class="reserva-estado-acciones">
                        <span class="fmd-badge fmd-badge-<?php echo esc_attr($estado_info['class']); ?>">
                            <?php echo esc_html($estado_info['label']); ?>
                        </span>

                        <?php if ($puede_cancelar): ?>
                            <button type="button" class="fmd-btn fmd-btn-sm fmd-btn-outline-danger btn-cancelar-reserva"
                                    data-id="<?php echo esc_attr($reserva->id); ?>"
                                    data-nonce="<?php echo wp_create_nonce('reservas_cancelar_' . $reserva->id); ?>">
                                <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <style>
        .reservas-mis-reservas {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .reserva-item {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1.5rem;
            align-items: center;
            background: var(--fmd-bg-card, #fff);
            padding: 1.25rem;
            border-radius: 12px;
            border: 1px solid var(--fmd-border, #e5e7eb);
        }
        .reserva-item.reserva-estado-cancelada {
            opacity: 0.6;
        }
        .reserva-recurso {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .recurso-thumb {
            width: 64px;
            height: 64px;
            border-radius: 8px;
            object-fit: cover;
        }
        .recurso-thumb-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--fmd-bg-secondary, #f3f4f6);
        }
        .recurso-thumb-placeholder .dashicons {
            font-size: 24px;
            color: var(--fmd-text-muted, #9ca3af);
        }
        .reserva-recurso-info h4 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 0.25rem 0;
            color: var(--fmd-text-primary, #1f2937);
        }
        .reserva-recurso-info .tipo {
            font-size: 0.8125rem;
            color: var(--fmd-text-muted, #9ca3af);
        }
        .reserva-fechas {
            display: flex;
            gap: 1.5rem;
        }
        .fecha-item {
            display: flex;
            flex-direction: column;
        }
        .fecha-label {
            font-size: 0.75rem;
            color: var(--fmd-text-muted, #9ca3af);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .fecha-valor {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--fmd-text-primary, #1f2937);
        }
        .reserva-estado-acciones {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        @media (max-width: 768px) {
            .reserva-item {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .reserva-fechas {
                justify-content: space-between;
            }
            .reserva-estado-acciones {
                justify-content: space-between;
            }
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-cancelar-reserva').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (!confirm('<?php echo esc_js(__('¿Estás seguro de que deseas cancelar esta reserva?', 'flavor-chat-ia')); ?>')) {
                        return;
                    }

                    var reservaId = this.dataset.id;
                    var nonce = this.dataset.nonce;
                    var boton = this;

                    boton.disabled = true;
                    boton.textContent = '<?php echo esc_js(__('Cancelando...', 'flavor-chat-ia')); ?>';

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=reservas_cancelar&reserva_id=' + reservaId + '&nonce=' + nonce
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.data || '<?php echo esc_js(__('Error al cancelar', 'flavor-chat-ia')); ?>');
                            boton.disabled = false;
                            boton.textContent = '<?php echo esc_js(__('Cancelar', 'flavor-chat-ia')); ?>';
                        }
                    })
                    .catch(function() {
                        alert('<?php echo esc_js(__('Error de conexión', 'flavor-chat-ia')); ?>');
                        boton.disabled = false;
                        boton.textContent = '<?php echo esc_js(__('Cancelar', 'flavor-chat-ia')); ?>';
                    });
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Renderiza el tab de calendario
     *
     * @param int $usuario_id ID del usuario actual
     */
    public function render_tab_calendario($usuario_id = 0) {
        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_reservas';
        $tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

        // Obtener mes y año actuales o de los parámetros
        $mes_actual = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('m'));
        $anio_actual = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date('Y'));

        // Validar rango
        if ($mes_actual < 1) { $mes_actual = 12; $anio_actual--; }
        if ($mes_actual > 12) { $mes_actual = 1; $anio_actual++; }

        $primer_dia_mes = mktime(0, 0, 0, $mes_actual, 1, $anio_actual);
        $dias_en_mes = intval(date('t', $primer_dia_mes));
        $dia_semana_inicio = intval(date('N', $primer_dia_mes)); // 1=Lunes, 7=Domingo

        // Obtener reservas del mes
        $fecha_inicio_mes = date('Y-m-01', $primer_dia_mes);
        $fecha_fin_mes = date('Y-m-t', $primer_dia_mes);

        $reservas_mes = [];
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_reservas)) === $tabla_reservas) {
            $reservas_raw = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, rec.nombre as recurso_nombre
                 FROM $tabla_reservas r
                 LEFT JOIN $tabla_recursos rec ON r.recurso_id = rec.id
                 WHERE r.estado IN ('confirmada', 'pendiente')
                   AND DATE(r.fecha_inicio) BETWEEN %s AND %s
                 ORDER BY r.fecha_inicio ASC",
                $fecha_inicio_mes,
                $fecha_fin_mes
            ));

            foreach ($reservas_raw as $reserva) {
                $dia = intval(date('j', strtotime($reserva->fecha_inicio)));
                if (!isset($reservas_mes[$dia])) {
                    $reservas_mes[$dia] = [];
                }
                $reservas_mes[$dia][] = $reserva;
            }
        }

        // Navegación
        $mes_anterior = $mes_actual - 1;
        $anio_anterior = $anio_actual;
        if ($mes_anterior < 1) { $mes_anterior = 12; $anio_anterior--; }

        $mes_siguiente = $mes_actual + 1;
        $anio_siguiente = $anio_actual;
        if ($mes_siguiente > 12) { $mes_siguiente = 1; $anio_siguiente++; }

        $base_url = home_url('/mi-portal/reservas/?tab=calendario');

        $dias_semana = [
            __('Lun', 'flavor-chat-ia'),
            __('Mar', 'flavor-chat-ia'),
            __('Mié', 'flavor-chat-ia'),
            __('Jue', 'flavor-chat-ia'),
            __('Vie', 'flavor-chat-ia'),
            __('Sáb', 'flavor-chat-ia'),
            __('Dom', 'flavor-chat-ia'),
        ];

        ?>
        <div class="reservas-calendario-container">
            <div class="calendario-header">
                <a href="<?php echo esc_url($base_url . '&mes=' . $mes_anterior . '&anio=' . $anio_anterior); ?>" class="nav-btn">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </a>
                <h3 class="mes-titulo"><?php echo esc_html(ucfirst(date_i18n('F Y', $primer_dia_mes))); ?></h3>
                <a href="<?php echo esc_url($base_url . '&mes=' . $mes_siguiente . '&anio=' . $anio_siguiente); ?>" class="nav-btn">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </div>

            <div class="calendario-grid">
                <?php foreach ($dias_semana as $dia_nombre): ?>
                    <div class="calendario-dia-header"><?php echo esc_html($dia_nombre); ?></div>
                <?php endforeach; ?>

                <?php
                // Celdas vacías antes del primer día
                for ($celda_vacia = 1; $celda_vacia < $dia_semana_inicio; $celda_vacia++) {
                    echo '<div class="calendario-dia calendario-dia-vacio"></div>';
                }

                // Días del mes
                $hoy = date('Y-m-d');
                for ($dia = 1; $dia <= $dias_en_mes; $dia++):
                    $fecha_dia = date('Y-m-d', mktime(0, 0, 0, $mes_actual, $dia, $anio_actual));
                    $es_hoy = ($fecha_dia === $hoy);
                    $es_pasado = ($fecha_dia < $hoy);
                    $tiene_reservas = isset($reservas_mes[$dia]) && !empty($reservas_mes[$dia]);
                    $num_reservas = $tiene_reservas ? count($reservas_mes[$dia]) : 0;

                    $clases = ['calendario-dia'];
                    if ($es_hoy) $clases[] = 'es-hoy';
                    if ($es_pasado) $clases[] = 'es-pasado';
                    if ($tiene_reservas) $clases[] = 'tiene-reservas';
                ?>
                    <div class="<?php echo esc_attr(implode(' ', $clases)); ?>" data-fecha="<?php echo esc_attr($fecha_dia); ?>">
                        <span class="dia-numero"><?php echo $dia; ?></span>
                        <?php if ($tiene_reservas): ?>
                            <span class="dia-indicador" title="<?php echo esc_attr(sprintf(_n('%d reserva', '%d reservas', $num_reservas, 'flavor-chat-ia'), $num_reservas)); ?>">
                                <?php echo $num_reservas; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="calendario-leyenda">
                <span class="leyenda-item"><span class="indicador disponible"></span> <?php esc_html_e('Disponible', 'flavor-chat-ia'); ?></span>
                <span class="leyenda-item"><span class="indicador reservado"></span> <?php esc_html_e('Con reservas', 'flavor-chat-ia'); ?></span>
                <span class="leyenda-item"><span class="indicador hoy"></span> <?php esc_html_e('Hoy', 'flavor-chat-ia'); ?></span>
            </div>

            <?php if (!empty($reservas_mes)): ?>
            <div class="calendario-proximas">
                <h4><?php esc_html_e('Reservas del mes', 'flavor-chat-ia'); ?></h4>
                <div class="proximas-lista">
                    <?php
                    $todas_reservas = [];
                    foreach ($reservas_mes as $reservas_dia) {
                        $todas_reservas = array_merge($todas_reservas, $reservas_dia);
                    }
                    usort($todas_reservas, function($a, $b) {
                        return strtotime($a->fecha_inicio) - strtotime($b->fecha_inicio);
                    });
                    $reservas_mostrar = array_slice($todas_reservas, 0, 10);

                    foreach ($reservas_mostrar as $reserva):
                    ?>
                        <div class="proxima-item">
                            <div class="proxima-fecha">
                                <span class="dia"><?php echo esc_html(date_i18n('d', strtotime($reserva->fecha_inicio))); ?></span>
                                <span class="mes"><?php echo esc_html(date_i18n('M', strtotime($reserva->fecha_inicio))); ?></span>
                            </div>
                            <div class="proxima-info">
                                <span class="recurso"><?php echo esc_html($reserva->recurso_nombre); ?></span>
                                <span class="hora"><?php echo esc_html(date_i18n('H:i', strtotime($reserva->fecha_inicio))); ?> - <?php echo esc_html(date_i18n('H:i', strtotime($reserva->fecha_fin))); ?></span>
                            </div>
                            <span class="fmd-badge fmd-badge-<?php echo $reserva->estado === 'confirmada' ? 'success' : 'warning'; ?>">
                                <?php echo esc_html(ucfirst($reserva->estado)); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <style>
        .reservas-calendario-container {
            background: var(--fmd-bg-card, #fff);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--fmd-border, #e5e7eb);
        }
        .calendario-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .mes-titulo {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: var(--fmd-text-primary, #1f2937);
        }
        .nav-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--fmd-bg-secondary, #f3f4f6);
            color: var(--fmd-text-primary, #1f2937);
            text-decoration: none;
            transition: background 0.2s;
        }
        .nav-btn:hover {
            background: var(--fmd-border, #e5e7eb);
        }
        .calendario-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }
        .calendario-dia-header {
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--fmd-text-muted, #9ca3af);
            padding: 0.5rem;
            text-transform: uppercase;
        }
        .calendario-dia {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            position: relative;
            background: var(--fmd-bg-secondary, #f9fafb);
        }
        .calendario-dia:hover:not(.es-pasado) {
            background: var(--fmd-primary-light, #eff6ff);
        }
        .calendario-dia-vacio {
            background: transparent;
            cursor: default;
        }
        .dia-numero {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--fmd-text-primary, #1f2937);
        }
        .calendario-dia.es-hoy {
            background: var(--fmd-primary, #2563eb);
        }
        .calendario-dia.es-hoy .dia-numero {
            color: #fff;
        }
        .calendario-dia.es-pasado {
            opacity: 0.4;
            cursor: default;
        }
        .calendario-dia.tiene-reservas {
            background: var(--fmd-success-light, #dcfce7);
        }
        .calendario-dia.tiene-reservas.es-hoy {
            background: var(--fmd-primary, #2563eb);
        }
        .dia-indicador {
            position: absolute;
            bottom: 4px;
            font-size: 0.625rem;
            font-weight: 600;
            background: var(--fmd-success, #22c55e);
            color: #fff;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .calendario-leyenda {
            display: flex;
            gap: 1.5rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--fmd-border, #e5e7eb);
        }
        .leyenda-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            color: var(--fmd-text-muted, #6b7280);
        }
        .leyenda-item .indicador {
            width: 12px;
            height: 12px;
            border-radius: 4px;
        }
        .indicador.disponible { background: var(--fmd-bg-secondary, #f3f4f6); }
        .indicador.reservado { background: var(--fmd-success-light, #dcfce7); }
        .indicador.hoy { background: var(--fmd-primary, #2563eb); }
        .calendario-proximas {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--fmd-border, #e5e7eb);
        }
        .calendario-proximas h4 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
            color: var(--fmd-text-primary, #1f2937);
        }
        .proximas-lista {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .proxima-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: var(--fmd-bg-secondary, #f9fafb);
            border-radius: 8px;
        }
        .proxima-fecha {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 40px;
        }
        .proxima-fecha .dia {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--fmd-primary, #2563eb);
            line-height: 1;
        }
        .proxima-fecha .mes {
            font-size: 0.625rem;
            text-transform: uppercase;
            color: var(--fmd-text-muted, #9ca3af);
        }
        .proxima-info {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .proxima-info .recurso {
            font-weight: 500;
            color: var(--fmd-text-primary, #1f2937);
        }
        .proxima-info .hora {
            font-size: 0.8125rem;
            color: var(--fmd-text-muted, #9ca3af);
        }
        </style>
        <?php
    }

    /**
     * Renderiza el tab de nueva reserva (formulario)
     *
     * @param int $usuario_id ID del usuario actual
     */
    public function render_tab_nueva_reserva($usuario_id = 0) {
        if (!is_user_logged_in()) {
            echo '<div class="fmd-login-required">';
            echo '<span class="dashicons dashicons-lock"></span>';
            echo '<p>' . esc_html__('Inicia sesión para hacer una reserva.', 'flavor-chat-ia') . '</p>';
            echo '<a href="' . esc_url(wp_login_url(home_url('/mi-portal/reservas/'))) . '" class="fmd-btn fmd-btn-primary">' . esc_html__('Iniciar sesión', 'flavor-chat-ia') . '</a>';
            echo '</div>';
            return;
        }

        global $wpdb;
        $tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';

        // Verificar si existe la tabla de recursos
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_recursos)) !== $tabla_recursos) {
            echo '<div class="fmd-empty-state">';
            echo '<span class="dashicons dashicons-admin-home" style="font-size: 48px; color: #9ca3af;"></span>';
            echo '<h3>' . esc_html__('Sistema de reservas en configuración', 'flavor-chat-ia') . '</h3>';
            echo '</div>';
            return;
        }

        // Obtener recursos activos
        $recursos = $wpdb->get_results(
            "SELECT * FROM $tabla_recursos WHERE activo = 1 ORDER BY nombre ASC"
        );

        if (empty($recursos)) {
            echo '<div class="fmd-empty-state">';
            echo '<span class="dashicons dashicons-admin-home" style="font-size: 48px; color: #9ca3af;"></span>';
            echo '<h3>' . esc_html__('No hay recursos disponibles', 'flavor-chat-ia') . '</h3>';
            echo '<p>' . esc_html__('Actualmente no hay espacios configurados para reservar.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }

        // Recurso preseleccionado
        $recurso_seleccionado = isset($_GET['recurso_id']) ? intval($_GET['recurso_id']) : 0;

        $usuario_actual = wp_get_current_user();
        ?>
        <div class="reservas-formulario-container">
            <form id="form-nueva-reserva" class="reservas-form">
                <?php wp_nonce_field('reservas_crear', 'reservas_nonce'); ?>

                <div class="form-section">
                    <h4><?php esc_html_e('Selecciona el recurso', 'flavor-chat-ia'); ?></h4>

                    <div class="form-group">
                        <label for="recurso_id"><?php esc_html_e('Recurso a reservar', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                        <select id="recurso_id" name="recurso_id" required class="fmd-input">
                            <option value=""><?php esc_html_e('-- Selecciona un recurso --', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($recursos as $recurso): ?>
                                <option value="<?php echo esc_attr($recurso->id); ?>" <?php selected($recurso_seleccionado, $recurso->id); ?>>
                                    <?php echo esc_html($recurso->nombre); ?>
                                    <?php if (!empty($recurso->tipo)): ?> - <?php echo esc_html(ucfirst($recurso->tipo)); ?><?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h4><?php esc_html_e('Fecha y hora', 'flavor-chat-ia'); ?></h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_inicio"><?php esc_html_e('Fecha de inicio', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" required class="fmd-input"
                                   min="<?php echo esc_attr(date('Y-m-d')); ?>">
                        </div>
                        <div class="form-group">
                            <label for="hora_inicio"><?php esc_html_e('Hora de inicio', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                            <input type="time" id="hora_inicio" name="hora_inicio" required class="fmd-input">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_fin"><?php esc_html_e('Fecha de fin', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                            <input type="date" id="fecha_fin" name="fecha_fin" required class="fmd-input"
                                   min="<?php echo esc_attr(date('Y-m-d')); ?>">
                        </div>
                        <div class="form-group">
                            <label for="hora_fin"><?php esc_html_e('Hora de fin', 'flavor-chat-ia'); ?> <span class="required">*</span></label>
                            <input type="time" id="hora_fin" name="hora_fin" required class="fmd-input">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4><?php esc_html_e('Información adicional', 'flavor-chat-ia'); ?></h4>

                    <div class="form-group">
                        <label for="motivo"><?php esc_html_e('Motivo de la reserva', 'flavor-chat-ia'); ?></label>
                        <textarea id="motivo" name="motivo" rows="3" class="fmd-input"
                                  placeholder="<?php esc_attr_e('Describe brevemente para qué necesitas este recurso...', 'flavor-chat-ia'); ?>"></textarea>
                    </div>
                </div>

                <div id="verificacion-disponibilidad" class="verificacion-box" style="display: none;">
                    <span class="verificacion-icono"></span>
                    <span class="verificacion-mensaje"></span>
                </div>

                <div class="form-actions">
                    <button type="button" id="btn-verificar" class="fmd-btn fmd-btn-outline">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('Verificar Disponibilidad', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="submit" id="btn-reservar" class="fmd-btn fmd-btn-primary" disabled>
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('Confirmar Reserva', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>

        <style>
        .reservas-formulario-container {
            max-width: 600px;
        }
        .reservas-form {
            background: var(--fmd-bg-card, #fff);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--fmd-border, #e5e7eb);
        }
        .form-section {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--fmd-border, #e5e7eb);
        }
        .form-section:last-of-type {
            border-bottom: none;
            padding-bottom: 0;
        }
        .form-section h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--fmd-text-primary, #1f2937);
            margin: 0 0 1rem 0;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group:last-child {
            margin-bottom: 0;
        }
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--fmd-text-primary, #374151);
            margin-bottom: 0.375rem;
        }
        .form-group .required {
            color: var(--fmd-error, #ef4444);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .fmd-input {
            width: 100%;
            padding: 0.625rem 0.875rem;
            font-size: 1rem;
            border: 1px solid var(--fmd-border, #d1d5db);
            border-radius: 8px;
            background: var(--fmd-bg, #fff);
            color: var(--fmd-text-primary, #1f2937);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .fmd-input:focus {
            outline: none;
            border-color: var(--fmd-primary, #2563eb);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        textarea.fmd-input {
            resize: vertical;
            min-height: 80px;
        }
        .verificacion-box {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .verificacion-box.disponible {
            background: var(--fmd-success-light, #dcfce7);
            border: 1px solid var(--fmd-success, #22c55e);
        }
        .verificacion-box.no-disponible {
            background: var(--fmd-error-light, #fee2e2);
            border: 1px solid var(--fmd-error, #ef4444);
        }
        .verificacion-box.cargando {
            background: var(--fmd-bg-secondary, #f3f4f6);
            border: 1px solid var(--fmd-border, #e5e7eb);
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .form-actions {
                flex-direction: column;
            }
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.getElementById('form-nueva-reserva');
            var btnVerificar = document.getElementById('btn-verificar');
            var btnReservar = document.getElementById('btn-reservar');
            var verificacionBox = document.getElementById('verificacion-disponibilidad');

            // Auto-rellenar fecha_fin cuando cambia fecha_inicio
            document.getElementById('fecha_inicio').addEventListener('change', function() {
                var fechaFin = document.getElementById('fecha_fin');
                if (!fechaFin.value) {
                    fechaFin.value = this.value;
                }
            });

            // Verificar disponibilidad
            btnVerificar.addEventListener('click', function() {
                var recursoId = document.getElementById('recurso_id').value;
                var fechaInicio = document.getElementById('fecha_inicio').value;
                var horaInicio = document.getElementById('hora_inicio').value;
                var fechaFin = document.getElementById('fecha_fin').value;
                var horaFin = document.getElementById('hora_fin').value;

                if (!recursoId || !fechaInicio || !horaInicio || !fechaFin || !horaFin) {
                    alert('<?php echo esc_js(__('Por favor, completa todos los campos de fecha y hora.', 'flavor-chat-ia')); ?>');
                    return;
                }

                verificacionBox.style.display = 'flex';
                verificacionBox.className = 'verificacion-box cargando';
                verificacionBox.querySelector('.verificacion-mensaje').textContent = '<?php echo esc_js(__('Verificando disponibilidad...', 'flavor-chat-ia')); ?>';

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=reservas_disponibilidad&recurso_id=' + recursoId +
                          '&fecha_inicio=' + fechaInicio + '&hora_inicio=' + horaInicio +
                          '&fecha_fin=' + fechaFin + '&hora_fin=' + horaFin
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success && data.data.disponible) {
                        verificacionBox.className = 'verificacion-box disponible';
                        verificacionBox.querySelector('.verificacion-mensaje').textContent = '<?php echo esc_js(__('Horario disponible', 'flavor-chat-ia')); ?>';
                        btnReservar.disabled = false;
                    } else {
                        verificacionBox.className = 'verificacion-box no-disponible';
                        verificacionBox.querySelector('.verificacion-mensaje').textContent = data.data?.mensaje || '<?php echo esc_js(__('Horario no disponible', 'flavor-chat-ia')); ?>';
                        btnReservar.disabled = true;
                    }
                })
                .catch(function() {
                    verificacionBox.className = 'verificacion-box no-disponible';
                    verificacionBox.querySelector('.verificacion-mensaje').textContent = '<?php echo esc_js(__('Error al verificar', 'flavor-chat-ia')); ?>';
                });
            });

            // Enviar formulario
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (btnReservar.disabled) {
                    alert('<?php echo esc_js(__('Verifica la disponibilidad antes de reservar.', 'flavor-chat-ia')); ?>');
                    return;
                }

                btnReservar.disabled = true;
                btnReservar.innerHTML = '<span class="dashicons dashicons-update spin"></span> <?php echo esc_js(__('Procesando...', 'flavor-chat-ia')); ?>';

                var formData = new FormData(form);
                formData.append('action', 'reservas_crear');

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        alert('<?php echo esc_js(__('Reserva creada correctamente', 'flavor-chat-ia')); ?>');
                        window.location.href = '<?php echo esc_url(home_url('/mi-portal/reservas/?tab=mis-reservas')); ?>';
                    } else {
                        alert(data.data || '<?php echo esc_js(__('Error al crear la reserva', 'flavor-chat-ia')); ?>');
                        btnReservar.disabled = false;
                        btnReservar.innerHTML = '<span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_js(__('Confirmar Reserva', 'flavor-chat-ia')); ?>';
                    }
                })
                .catch(function() {
                    alert('<?php echo esc_js(__('Error de conexión', 'flavor-chat-ia')); ?>');
                    btnReservar.disabled = false;
                    btnReservar.innerHTML = '<span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_js(__('Confirmar Reserva', 'flavor-chat-ia')); ?>';
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Configuración para el Module Renderer
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'reservas',
            'title'    => __('Reservas', 'flavor-chat-ia'),
            'subtitle' => __('Reserva espacios y recursos comunitarios', 'flavor-chat-ia'),
            'icon'     => '📋',
            'color'    => 'primary', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'          => 'flavor_reservas',
                'status_field'   => 'estado',
                'exclude_status' => 'cancelada',
                'order_by'       => 'fecha_inicio ASC',
                'filter_fields'  => ['estado', 'recurso_id'],
            ],

            'fields' => [
                'titulo'       => 'titulo',
                'descripcion'  => 'descripcion',
                'estado'       => 'estado',
                'fecha_inicio' => 'fecha_inicio',
                'fecha_fin'    => 'fecha_fin',
                'recurso'      => 'recurso_id',
                'user_id'      => 'user_id',
            ],

            'estados' => [
                'pendiente'  => ['label' => __('Pendiente', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '🟡'],
                'confirmada' => ['label' => __('Confirmada', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '🟢'],
                'completada' => ['label' => __('Completada', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '⚫'],
                'cancelada'  => ['label' => __('Cancelada', 'flavor-chat-ia'), 'color' => 'red', 'icon' => '🔴'],
            ],

            'stats' => [
                ['label' => __('Pendientes', 'flavor-chat-ia'), 'icon' => '🟡', 'color' => 'yellow', 'count_where' => "estado = 'pendiente'"],
                ['label' => __('Hoy', 'flavor-chat-ia'), 'icon' => '📅', 'color' => 'teal', 'count_where' => "DATE(fecha_inicio) = CURDATE() AND estado = 'confirmada'"],
                ['label' => __('Recursos', 'flavor-chat-ia'), 'icon' => '🏠', 'color' => 'blue', 'query' => "SELECT COUNT(*) FROM {table}_recursos WHERE activo = 1"],
            ],

            'tabs' => [
                'recursos' => ['label' => __('Recursos Disponibles', 'flavor-chat-ia'), 'icon' => 'dashicons-admin-home'],
                'mis-reservas' => ['label' => __('Mis Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt'],
                'calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar'],
                'nueva-reserva' => ['label' => __('Hacer Reserva', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt'],
            ],

            'dashboard' => [
                'show_header' => true,
                'quick_actions' => [
                    ['title' => __('Reservar', 'flavor-chat-ia'), 'icon' => '➕', 'color' => 'teal', 'url' => home_url('/mi-portal/reservas/')],
                    ['title' => __('Mis reservas', 'flavor-chat-ia'), 'icon' => '📋', 'color' => 'blue', 'url' => home_url('/mi-portal/reservas/?tab=mis-reservas')],
                    ['title' => __('Calendario', 'flavor-chat-ia'), 'icon' => '📅', 'color' => 'green', 'url' => home_url('/mi-portal/reservas/?tab=calendario')],
                ],
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-reservas-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Reservas_Dashboard_Tab')) {
                Flavor_Reservas_Dashboard_Tab::get_instance();
            }
        }
    }
}
