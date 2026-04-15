<?php
/**
 * API REST para el módulo de Eventos
 *
 * @package Flavor_Platform
 * @subpackage Eventos
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Eventos_API {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Namespace de la API
     */
    const API_NAMESPACE = FLAVOR_PLATFORM_REST_NAMESPACE;

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registrar rutas de la API
     */
    public function register_routes() {
        // GET /eventos - Listar eventos
        flavor_register_rest_route(self::API_NAMESPACE, '/eventos', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_eventos'],
            'permission_callback' => [$this, 'public_read_permission'],
            'args' => [
                'page' => [
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ],
                'per_page' => [
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ],
                'search' => [
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // GET /eventos/{id} - Detalle de evento
        flavor_register_rest_route(self::API_NAMESPACE, '/eventos/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_evento_detail'],
            'permission_callback' => [$this, 'public_read_permission'],
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
            ],
        ]);

        // POST /eventos/{id}/inscribir - Inscribirse en evento
        flavor_register_rest_route(self::API_NAMESPACE, '/eventos/(?P<id>\d+)/inscribir', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'inscribir_en_evento'],
            'permission_callback' => 'is_user_logged_in',
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
            ],
        ]);

        // DELETE /eventos/{id}/cancelar - Cancelar inscripción
        flavor_register_rest_route(self::API_NAMESPACE, '/eventos/(?P<id>\d+)/cancelar', [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => [$this, 'cancelar_inscripcion'],
            'permission_callback' => 'is_user_logged_in',
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
            ],
        ]);

        // POST /eventos - Crear evento
        flavor_register_rest_route(self::API_NAMESPACE, '/eventos', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'crear_evento'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => [
                'titulo' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'descripcion' => [
                    'required' => true,
                    'sanitize_callback' => 'wp_kses_post',
                ],
                'tipo' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_key',
                ],
                'fecha_inicio' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // Registrar AJAX handler para crear eventos (compatibilidad con formularios)
        add_action('wp_ajax_eventos_crear_evento_ajax', [$this, 'ajax_crear_evento']);

        // Handlers AJAX para panel de administración de eventos
        add_action('wp_ajax_eventos_guardar_evento', [$this, 'ajax_guardar_evento']);
        add_action('wp_ajax_eventos_listar_eventos', [$this, 'ajax_listar_eventos']);
        add_action('wp_ajax_eventos_obtener_evento', [$this, 'ajax_obtener_evento']);
        add_action('wp_ajax_eventos_eliminar_evento', [$this, 'ajax_eliminar_evento']);

        // Handlers AJAX para tipos de entrada
        add_action('wp_ajax_eventos_listar_tipos_entrada', [$this, 'ajax_listar_tipos_entrada']);

        // Handlers AJAX para gestión de asistentes
        add_action('wp_ajax_eventos_listar_asistentes', [$this, 'ajax_listar_asistentes']);
        add_action('wp_ajax_eventos_hacer_checkin', [$this, 'ajax_hacer_checkin']);
        add_action('wp_ajax_eventos_exportar_asistentes', [$this, 'ajax_exportar_asistentes']);
        add_action('wp_ajax_eventos_estadisticas_entradas', [$this, 'ajax_estadisticas_entradas']);
    }

    /**
     * Permisos públicos de lectura del catálogo publicado.
     *
     * @return bool
     */
    public function public_read_permission() {
        return true;
    }

    /**
     * POST /eventos - Crear un nuevo evento
     */
    public function crear_evento($request) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        // Preparar datos del evento
        $datos_evento = [
            'titulo'         => $request->get_param('titulo'),
            'descripcion'    => $request->get_param('descripcion'),
            'tipo'           => $request->get_param('tipo'),
            'fecha_inicio'   => $request->get_param('fecha_inicio'),
            'fecha_fin'      => $request->get_param('fecha_fin') ?: null,
            'ubicacion'      => $request->get_param('ubicacion') ?: '',
            'direccion'      => $request->get_param('direccion') ?: '',
            'precio'         => floatval($request->get_param('precio') ?: 0),
            'aforo_maximo'   => intval($request->get_param('aforo_maximo') ?: 0),
            'es_online'      => $request->get_param('es_online') ? 1 : 0,
            'url_online'     => $request->get_param('url_online') ?: '',
            'imagen'         => $request->get_param('imagen') ?: '',
            'estado'         => 'publicado',
            'organizador_id' => $usuario_id,
            'comunidad_id'   => absint($request->get_param('comunidad_id') ?: 0) ?: null,
            'created_at'     => current_time('mysql'),
            'updated_at'     => current_time('mysql'),
        ];

        // Insertar en BD
        $resultado = $wpdb->insert($tabla_eventos, $datos_evento);

        if ($resultado === false) {
            return new WP_Error(
                'evento_create_error',
                __('Error al crear el evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 500]
            );
        }

        $evento_id = $wpdb->insert_id;

        return rest_ensure_response([
            'success' => true,
            'data' => [
                'id' => $evento_id,
                'message' => __('Evento creado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'redirect' => add_query_arg('evento_id', $evento_id, Flavor_Platform_Helpers::get_action_url('eventos', 'detalle')),
            ],
        ]);
    }

    /**
     * AJAX: Crear evento (para formularios tradicionales)
     */
    public function ajax_crear_evento() {
        // Verificar nonce
        if (!isset($_POST['eventos_nonce']) || !wp_verify_nonce($_POST['eventos_nonce'], 'eventos_crear')) {
            wp_send_json_error(['message' => __('Sesion expirada. Recarga la pagina.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar permisos
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos para crear eventos.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $usuario_id = get_current_user_id();

        // Validar campos requeridos
        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
        $descripcion = isset($_POST['descripcion']) ? wp_kses_post($_POST['descripcion']) : '';
        $tipo = isset($_POST['tipo']) ? sanitize_key($_POST['tipo']) : '';
        $fecha_inicio = isset($_POST['fecha_inicio']) ? sanitize_text_field($_POST['fecha_inicio']) : '';

        if (empty($titulo) || empty($descripcion) || empty($tipo) || empty($fecha_inicio)) {
            wp_send_json_error(['message' => __('Completa todos los campos requeridos.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Preparar datos
        $datos_evento = [
            'titulo'         => $titulo,
            'descripcion'    => $descripcion,
            'tipo'           => $tipo,
            'fecha_inicio'   => $fecha_inicio,
            'fecha_fin'      => isset($_POST['fecha_fin']) ? sanitize_text_field($_POST['fecha_fin']) : null,
            'ubicacion'      => isset($_POST['ubicacion']) ? sanitize_text_field($_POST['ubicacion']) : '',
            'direccion'      => isset($_POST['direccion']) ? sanitize_text_field($_POST['direccion']) : '',
            'precio'         => isset($_POST['precio']) ? floatval($_POST['precio']) : 0,
            'aforo_maximo'   => isset($_POST['aforo_maximo']) ? intval($_POST['aforo_maximo']) : 0,
            'es_online'      => isset($_POST['es_online']) && $_POST['es_online'] ? 1 : 0,
            'url_online'     => isset($_POST['url_online']) ? esc_url_raw($_POST['url_online']) : '',
            'imagen'         => isset($_POST['imagen']) ? esc_url_raw($_POST['imagen']) : '',
            'estado'         => 'publicado',
            'organizador_id' => $usuario_id,
            'comunidad_id'   => absint($_POST['comunidad_id'] ?? 0) ?: null,
            'created_at'     => current_time('mysql'),
            'updated_at'     => current_time('mysql'),
        ];

        // Insertar
        $resultado = $wpdb->insert($tabla_eventos, $datos_evento);

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al guardar el evento. Intentalo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $evento_id = $wpdb->insert_id;

        wp_send_json_success([
            'id' => $evento_id,
            'message' => __('Evento creado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'redirect' => add_query_arg('evento_id', $evento_id, Flavor_Platform_Helpers::get_action_url('eventos', 'detalle')),
        ]);
    }

    /**
     * GET /eventos - Listar eventos
     */
    public function get_eventos($request) {
        global $wpdb;

        $page = $request->get_param('page');
        $per_page = min($request->get_param('per_page'), 100);
        $search = $request->get_param('search');
        $offset = ($page - 1) * $per_page;

        $table_name = $wpdb->prefix . 'flavor_eventos';

        // Query base
        $where = "WHERE estado = 'publicado'";
        $params = [];

        // Búsqueda
        if (!empty($search)) {
            $where .= " AND (titulo LIKE %s OR descripcion LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Contar total
        $count_query = "SELECT COUNT(*) FROM $table_name $where";
        $total = $wpdb->get_var($wpdb->prepare($count_query, $params));

        // Obtener eventos
        $query = "SELECT * FROM $table_name $where ORDER BY fecha_inicio DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $eventos = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

        // Añadir información de inscripción si hay usuario logueado
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            foreach ($eventos as &$evento) {
                $evento['inscrito'] = $this->usuario_inscrito($evento['id'], $user_id);
            }
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $eventos,
            'pagination' => [
                'total' => (int) $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page),
            ],
        ]);
    }

    /**
     * GET /eventos/{id} - Detalle de evento
     */
    public function get_evento_detail($request) {
        global $wpdb;

        $evento_id = $request->get_param('id');
        $table_name = $wpdb->prefix . 'flavor_eventos';

        $evento = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND estado = 'publicado'", $evento_id),
            ARRAY_A
        );

        if (!$evento) {
            return new WP_Error(
                'evento_not_found',
                __('Evento no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 404]
            );
        }

        // Contar plazas ocupadas
        $inscripciones_table = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $plazas_ocupadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $inscripciones_table WHERE evento_id = %d AND estado = 'confirmada'",
            $evento_id
        ));

        $evento['plazas_ocupadas'] = (int) $plazas_ocupadas;

        // Verificar si el usuario está inscrito
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $evento['inscrito'] = $this->usuario_inscrito($evento_id, $user_id);
        } else {
            $evento['inscrito'] = false;
        }

        return rest_ensure_response([
            'success' => true,
            'evento' => $evento,
        ]);
    }

    /**
     * POST /eventos/{id}/inscribir - Inscribirse en evento
     */
    public function inscribir_en_evento($request) {
        global $wpdb;

        $evento_id = $request->get_param('id');
        $user_id = get_current_user_id();

        // Verificar que el evento existe
        $table_name = $wpdb->prefix . 'flavor_eventos';
        $evento = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $evento_id),
            ARRAY_A
        );

        if (!$evento) {
            return new WP_Error(
                'evento_not_found',
                __('Evento no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 404]
            );
        }

        // Verificar si ya está inscrito
        if ($this->usuario_inscrito($evento_id, $user_id)) {
            return new WP_Error(
                'already_registered',
                __('Ya estás inscrito en este evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 400]
            );
        }

        // Verificar plazas disponibles
        if ($evento['plazas_totales'] > 0) {
            $inscripciones_table = $wpdb->prefix . 'flavor_eventos_inscripciones';
            $plazas_ocupadas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $inscripciones_table WHERE evento_id = %d AND estado = 'confirmada'",
                $evento_id
            ));

            if ($plazas_ocupadas >= $evento['plazas_totales']) {
                return new WP_Error(
                    'no_places',
                    __('No quedan plazas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ['status' => 400]
                );
            }
        }

        // Crear inscripción
        $inscripciones_table = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $result = $wpdb->insert(
            $inscripciones_table,
            [
                'evento_id' => $evento_id,
                'usuario_id' => $user_id,
                'estado' => 'confirmada',
                'fecha_inscripcion' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s']
        );

        if ($result === false) {
            return new WP_Error(
                'insert_failed',
                __('Error al crear la inscripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 500]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Inscripción realizada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'inscripcion_id' => $wpdb->insert_id,
        ]);
    }

    /**
     * DELETE /eventos/{id}/cancelar - Cancelar inscripción
     */
    public function cancelar_inscripcion($request) {
        global $wpdb;

        $evento_id = $request->get_param('id');
        $user_id = get_current_user_id();

        $inscripciones_table = $wpdb->prefix . 'flavor_eventos_inscripciones';

        $result = $wpdb->delete(
            $inscripciones_table,
            [
                'evento_id' => $evento_id,
                'usuario_id' => $user_id,
            ],
            ['%d', '%d']
        );

        if ($result === false || $result === 0) {
            return new WP_Error(
                'not_found',
                __('No se encontró la inscripción', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 404]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Inscripción cancelada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * Verificar si un usuario está inscrito en un evento
     */
    private function usuario_inscrito($evento_id, $user_id) {
        global $wpdb;

        $inscripciones_table = $wpdb->prefix . 'flavor_eventos_inscripciones';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $inscripciones_table WHERE evento_id = %d AND usuario_id = %d",
            $evento_id,
            $user_id
        ));

        return $count > 0;
    }

    /**
     * AJAX: Guardar evento (crear o actualizar)
     */
    public function ajax_guardar_evento() {
        // Verificar permisos
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos para gestionar eventos.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $usuario_id = get_current_user_id();

        // Obtener y sanitizar datos
        $evento_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
        $descripcion = isset($_POST['descripcion']) ? wp_kses_post($_POST['descripcion']) : '';
        $fecha = isset($_POST['fecha']) ? sanitize_text_field($_POST['fecha']) : '';
        $hora = isset($_POST['hora']) ? sanitize_text_field($_POST['hora']) : '';
        $ubicacion = isset($_POST['ubicacion']) ? sanitize_text_field($_POST['ubicacion']) : '';
        $capacidad = isset($_POST['capacidad']) ? intval($_POST['capacidad']) : 0;
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : 'otro';

        // Validar campos requeridos
        if (empty($titulo) || empty($fecha) || empty($hora)) {
            wp_send_json_error(['message' => __('Completa los campos obligatorios: título, fecha y hora.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Combinar fecha y hora
        $fecha_inicio = $fecha . ' ' . $hora . ':00';

        // Preparar datos
        $datos_evento = [
            'titulo'         => $titulo,
            'descripcion'    => $descripcion,
            'tipo'           => $categoria,
            'fecha_inicio'   => $fecha_inicio,
            'ubicacion'      => $ubicacion,
            'aforo_maximo'   => $capacidad,
            'estado'         => 'publicado',
            'updated_at'     => current_time('mysql'),
        ];

        if ($evento_id > 0) {
            // Actualizar evento existente
            $resultado = $wpdb->update(
                $tabla_eventos,
                $datos_evento,
                ['id' => $evento_id],
                null,
                ['%d']
            );

            if ($resultado === false) {
                wp_send_json_error(['message' => __('Error al actualizar el evento.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }

            wp_send_json_success([
                'id' => $evento_id,
                'message' => __('Evento actualizado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        } else {
            // Crear nuevo evento
            $datos_evento['organizador_id'] = $usuario_id;
            $datos_evento['created_at'] = current_time('mysql');

            $resultado = $wpdb->insert($tabla_eventos, $datos_evento);

            if ($resultado === false) {
                wp_send_json_error(['message' => __('Error al crear el evento.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }

            wp_send_json_success([
                'id' => $wpdb->insert_id,
                'message' => __('Evento creado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }
    }

    /**
     * AJAX: Listar eventos para panel de administración
     */
    public function ajax_listar_eventos() {
        // Verificar permisos
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';

        // Filtros
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '';

        // Construir query
        $where_clauses = ['1=1'];
        $where_values = [];

        if (!empty($search)) {
            $where_clauses[] = "(titulo LIKE %s OR descripcion LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        if (!empty($categoria)) {
            $where_clauses[] = "tipo = %s";
            $where_values[] = $categoria;
        }

        if (!empty($estado)) {
            $where_clauses[] = "estado = %s";
            $where_values[] = $estado;
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Obtener eventos con conteo de asistentes
        $query = "SELECT e.*,
                  (SELECT COUNT(*) FROM {$tabla_inscripciones} WHERE evento_id = e.id AND estado = 'confirmado') as asistentes_confirmados
                  FROM {$tabla_eventos} e
                  WHERE {$where_sql}
                  ORDER BY e.fecha_inicio DESC
                  LIMIT 100";

        if (!empty($where_values)) {
            $eventos = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No hay valores de usuario
            $eventos = $wpdb->get_results($query, ARRAY_A);
        }

        // Formatear datos para la vista
        foreach ($eventos as &$evento) {
            $evento['fecha'] = date_i18n('d/m/Y H:i', strtotime($evento['fecha_inicio']));
            $evento['capacidad'] = $evento['aforo_maximo'] ?: null;
        }

        wp_send_json_success($eventos ?: []);
    }

    /**
     * AJAX: Obtener datos de un evento específico
     */
    public function ajax_obtener_evento() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $evento_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($evento_id <= 0) {
            wp_send_json_error(['message' => __('ID de evento inválido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        $evento = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$tabla_eventos} WHERE id = %d", $evento_id),
            ARRAY_A
        );

        if (!$evento) {
            wp_send_json_error(['message' => __('Evento no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Separar fecha y hora para el formulario
        if (!empty($evento['fecha_inicio'])) {
            $fecha_datetime = strtotime($evento['fecha_inicio']);
            $evento['fecha'] = date('Y-m-d', $fecha_datetime);
            $evento['hora'] = date('H:i', $fecha_datetime);
        }

        $evento['categoria'] = $evento['tipo'];
        $evento['capacidad'] = $evento['aforo_maximo'];

        wp_send_json_success($evento);
    }

    /**
     * AJAX: Eliminar evento
     */
    public function ajax_eliminar_evento() {
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos para eliminar eventos.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $evento_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($evento_id <= 0) {
            wp_send_json_error(['message' => __('ID de evento inválido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        $resultado = $wpdb->delete($tabla_eventos, ['id' => $evento_id], ['%d']);

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al eliminar el evento.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        wp_send_json_success(['message' => __('Evento eliminado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Listar tipos de entrada para un evento
     */
    public function ajax_listar_tipos_entrada() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $evento_id = isset($_POST['evento_id']) ? intval($_POST['evento_id']) : 0;

        if ($evento_id <= 0) {
            wp_send_json_error(['message' => __('ID de evento inválido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_tipos_entrada = $wpdb->prefix . 'flavor_eventos_tipos_entrada';

        // Verificar si la tabla existe
        $tabla_existe = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $tabla_tipos_entrada
            )
        );

        if (!$tabla_existe) {
            // Devolver tipos por defecto si la tabla no existe
            wp_send_json_success([
                [
                    'id' => 0,
                    'nombre' => __('Entrada General', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'precio' => 0,
                    'cantidad_disponible' => null,
                    'descripcion' => '',
                ],
            ]);
            return;
        }

        $tipos_entrada = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, nombre, precio, cantidad_disponible, descripcion, activo
                FROM {$tabla_tipos_entrada}
                WHERE evento_id = %d
                ORDER BY precio ASC",
                $evento_id
            ),
            ARRAY_A
        );

        if (empty($tipos_entrada)) {
            // Devolver tipo por defecto si no hay tipos configurados
            $tipos_entrada = [
                [
                    'id' => 0,
                    'nombre' => __('Entrada General', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'precio' => 0,
                    'cantidad_disponible' => null,
                    'descripcion' => '',
                ],
            ];
        }

        wp_send_json_success($tipos_entrada);
    }

    /**
     * AJAX: Listar asistentes de un evento
     */
    public function ajax_listar_asistentes() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $evento_id = isset($_POST['evento_id']) ? intval($_POST['evento_id']) : 0;
        $buscar = isset($_POST['buscar']) ? sanitize_text_field($_POST['buscar']) : '';
        $estado_filtro = isset($_POST['estado']) ? sanitize_key($_POST['estado']) : '';
        $pagina = isset($_POST['pagina']) ? max(1, intval($_POST['pagina'])) : 1;
        $por_pagina = isset($_POST['por_pagina']) ? min(100, max(1, intval($_POST['por_pagina']))) : 20;

        if ($evento_id <= 0) {
            wp_send_json_error(['message' => __('ID de evento inválido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';
        $offset = ($pagina - 1) * $por_pagina;

        // Construir condiciones WHERE
        $condiciones_where = ['i.evento_id = %d'];
        $valores_where = [$evento_id];

        if (!empty($buscar)) {
            $condiciones_where[] = "(u.display_name LIKE %s OR u.user_email LIKE %s)";
            $termino_busqueda = '%' . $wpdb->esc_like($buscar) . '%';
            $valores_where[] = $termino_busqueda;
            $valores_where[] = $termino_busqueda;
        }

        if (!empty($estado_filtro)) {
            $condiciones_where[] = "i.estado = %s";
            $valores_where[] = $estado_filtro;
        }

        $where_sql = implode(' AND ', $condiciones_where);

        // Contar total
        $query_total = "SELECT COUNT(*)
            FROM {$tabla_inscripciones} i
            LEFT JOIN {$wpdb->users} u ON i.usuario_id = u.ID
            WHERE {$where_sql}";

        $total = $wpdb->get_var($wpdb->prepare($query_total, $valores_where));

        // Obtener asistentes con paginación
        $valores_paginados = array_merge($valores_where, [$por_pagina, $offset]);

        $query_asistentes = "SELECT
                i.id,
                i.usuario_id,
                i.estado,
                i.fecha_inscripcion,
                i.checkin_at,
                i.tipo_entrada_id,
                u.display_name as nombre,
                u.user_email as email
            FROM {$tabla_inscripciones} i
            LEFT JOIN {$wpdb->users} u ON i.usuario_id = u.ID
            WHERE {$where_sql}
            ORDER BY i.fecha_inscripcion DESC
            LIMIT %d OFFSET %d";

        $asistentes = $wpdb->get_results(
            $wpdb->prepare($query_asistentes, $valores_paginados),
            ARRAY_A
        );

        // Formatear fechas
        foreach ($asistentes as &$asistente) {
            $asistente['fecha_inscripcion_formateada'] = date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                strtotime($asistente['fecha_inscripcion'])
            );
            $asistente['checkin_formateado'] = !empty($asistente['checkin_at'])
                ? date_i18n(get_option('time_format'), strtotime($asistente['checkin_at']))
                : null;
            $asistente['tiene_checkin'] = !empty($asistente['checkin_at']);
        }

        wp_send_json_success([
            'asistentes' => $asistentes,
            'total' => (int) $total,
            'pagina' => $pagina,
            'por_pagina' => $por_pagina,
            'total_paginas' => ceil($total / $por_pagina),
        ]);
    }

    /**
     * AJAX: Hacer check-in de un asistente
     */
    public function ajax_hacer_checkin() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $inscripcion_id = isset($_POST['inscripcion_id']) ? intval($_POST['inscripcion_id']) : 0;
        $accion = isset($_POST['accion']) ? sanitize_key($_POST['accion']) : 'checkin';

        if ($inscripcion_id <= 0) {
            wp_send_json_error(['message' => __('ID de inscripción inválido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';

        // Verificar que la inscripción existe
        $inscripcion = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, estado, checkin_at FROM {$tabla_inscripciones} WHERE id = %d",
                $inscripcion_id
            ),
            ARRAY_A
        );

        if (!$inscripcion) {
            wp_send_json_error(['message' => __('Inscripción no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if ($accion === 'undo') {
            // Deshacer check-in
            $resultado = $wpdb->update(
                $tabla_inscripciones,
                [
                    'checkin_at' => null,
                    'checkin_by' => null,
                ],
                ['id' => $inscripcion_id],
                ['%s', '%s'],
                ['%d']
            );

            if ($resultado === false) {
                wp_send_json_error(['message' => __('Error al deshacer el check-in.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }

            wp_send_json_success([
                'message' => __('Check-in deshecho correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'tiene_checkin' => false,
            ]);
        } else {
            // Hacer check-in
            if (!empty($inscripcion['checkin_at'])) {
                wp_send_json_error(['message' => __('Este asistente ya tiene check-in.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }

            $resultado = $wpdb->update(
                $tabla_inscripciones,
                [
                    'checkin_at' => current_time('mysql'),
                    'checkin_by' => get_current_user_id(),
                ],
                ['id' => $inscripcion_id],
                ['%s', '%d'],
                ['%d']
            );

            if ($resultado === false) {
                wp_send_json_error(['message' => __('Error al registrar el check-in.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }

            wp_send_json_success([
                'message' => __('Check-in registrado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'tiene_checkin' => true,
                'checkin_formateado' => date_i18n(get_option('time_format'), current_time('timestamp')),
            ]);
        }
    }

    /**
     * AJAX: Exportar lista de asistentes a CSV
     */
    public function ajax_exportar_asistentes() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $evento_id = isset($_POST['evento_id']) ? intval($_POST['evento_id']) : 0;
        $formato = isset($_POST['formato']) ? sanitize_key($_POST['formato']) : 'csv';

        if ($evento_id <= 0) {
            wp_send_json_error(['message' => __('ID de evento inválido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';

        // Obtener datos del evento
        $evento = $wpdb->get_row(
            $wpdb->prepare("SELECT titulo FROM {$tabla_eventos} WHERE id = %d", $evento_id),
            ARRAY_A
        );

        if (!$evento) {
            wp_send_json_error(['message' => __('Evento no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Obtener todos los asistentes
        $asistentes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    u.display_name as nombre,
                    u.user_email as email,
                    i.estado,
                    i.fecha_inscripcion,
                    i.checkin_at,
                    i.notas
                FROM {$tabla_inscripciones} i
                LEFT JOIN {$wpdb->users} u ON i.usuario_id = u.ID
                WHERE i.evento_id = %d
                ORDER BY i.fecha_inscripcion ASC",
                $evento_id
            ),
            ARRAY_A
        );

        if (empty($asistentes)) {
            wp_send_json_error(['message' => __('No hay asistentes para exportar.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Generar CSV
        $csv_lineas = [];

        // Cabecera
        $csv_lineas[] = implode(',', [
            '"' . __('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN) . '"',
            '"' . __('Email', FLAVOR_PLATFORM_TEXT_DOMAIN) . '"',
            '"' . __('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '"',
            '"' . __('Fecha Inscripción', FLAVOR_PLATFORM_TEXT_DOMAIN) . '"',
            '"' . __('Check-in', FLAVOR_PLATFORM_TEXT_DOMAIN) . '"',
            '"' . __('Notas', FLAVOR_PLATFORM_TEXT_DOMAIN) . '"',
        ]);

        // Datos
        foreach ($asistentes as $asistente) {
            $csv_lineas[] = implode(',', [
                '"' . str_replace('"', '""', $asistente['nombre'] ?: '') . '"',
                '"' . str_replace('"', '""', $asistente['email'] ?: '') . '"',
                '"' . str_replace('"', '""', $asistente['estado'] ?: '') . '"',
                '"' . ($asistente['fecha_inscripcion'] ? date_i18n('Y-m-d H:i', strtotime($asistente['fecha_inscripcion'])) : '') . '"',
                '"' . ($asistente['checkin_at'] ? date_i18n('Y-m-d H:i', strtotime($asistente['checkin_at'])) : '') . '"',
                '"' . str_replace('"', '""', $asistente['notas'] ?: '') . '"',
            ]);
        }

        $contenido_csv = implode("\n", $csv_lineas);
        $nombre_archivo = sanitize_file_name('asistentes-' . $evento['titulo'] . '-' . date('Y-m-d')) . '.csv';

        wp_send_json_success([
            'contenido' => $contenido_csv,
            'nombre_archivo' => $nombre_archivo,
            'tipo_mime' => 'text/csv',
            'total_registros' => count($asistentes),
        ]);
    }

    /**
     * AJAX: Obtener estadísticas de entradas para un evento
     */
    public function ajax_estadisticas_entradas() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No tienes permisos.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $evento_id = isset($_POST['evento_id']) ? intval($_POST['evento_id']) : 0;

        if ($evento_id <= 0) {
            wp_send_json_error(['message' => __('ID de evento inválido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';

        // Obtener datos del evento
        $evento = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, titulo, aforo_maximo, precio FROM {$tabla_eventos} WHERE id = %d",
                $evento_id
            ),
            ARRAY_A
        );

        if (!$evento) {
            wp_send_json_error(['message' => __('Evento no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Estadísticas generales de inscripciones
        $estadisticas_estado = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT estado, COUNT(*) as cantidad
                FROM {$tabla_inscripciones}
                WHERE evento_id = %d
                GROUP BY estado",
                $evento_id
            ),
            ARRAY_A
        );

        $por_estado = [];
        $total_inscripciones = 0;
        foreach ($estadisticas_estado as $fila) {
            $por_estado[$fila['estado']] = (int) $fila['cantidad'];
            $total_inscripciones += (int) $fila['cantidad'];
        }

        // Check-ins realizados
        $total_checkins = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_inscripciones}
                WHERE evento_id = %d AND checkin_at IS NOT NULL",
                $evento_id
            )
        );

        // Inscripciones por día (últimos 30 días)
        $inscripciones_por_dia = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(fecha_inscripcion) as fecha, COUNT(*) as cantidad
                FROM {$tabla_inscripciones}
                WHERE evento_id = %d
                AND fecha_inscripcion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(fecha_inscripcion)
                ORDER BY fecha ASC",
                $evento_id
            ),
            ARRAY_A
        );

        // Calcular ocupación
        $aforo_maximo = (int) ($evento['aforo_maximo'] ?: 0);
        $confirmados = $por_estado['confirmada'] ?? $por_estado['confirmado'] ?? 0;
        $porcentaje_ocupacion = $aforo_maximo > 0
            ? round(($confirmados / $aforo_maximo) * 100, 1)
            : 0;

        // Calcular tasa de check-in
        $tasa_checkin = $confirmados > 0
            ? round(((int) $total_checkins / $confirmados) * 100, 1)
            : 0;

        wp_send_json_success([
            'evento_id' => $evento_id,
            'evento_titulo' => $evento['titulo'],
            'aforo_maximo' => $aforo_maximo,
            'total_inscripciones' => $total_inscripciones,
            'por_estado' => $por_estado,
            'confirmados' => $confirmados,
            'total_checkins' => (int) $total_checkins,
            'porcentaje_ocupacion' => $porcentaje_ocupacion,
            'tasa_checkin' => $tasa_checkin,
            'plazas_disponibles' => $aforo_maximo > 0 ? max(0, $aforo_maximo - $confirmados) : null,
            'inscripciones_por_dia' => $inscripciones_por_dia,
        ]);
    }
}

// Inicializar API
Flavor_Eventos_API::get_instance();
