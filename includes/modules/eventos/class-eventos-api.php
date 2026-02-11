<?php
/**
 * API REST para el módulo de Eventos
 *
 * @package Flavor_Chat_IA
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
    const API_NAMESPACE = 'flavor-chat-ia/v1';

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
        register_rest_route(self::API_NAMESPACE, '/eventos', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_eventos'],
            'permission_callback' => '__return_true',
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
        register_rest_route(self::API_NAMESPACE, '/eventos/(?P<id>\d+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_evento_detail'],
            'permission_callback' => '__return_true',
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
        register_rest_route(self::API_NAMESPACE, '/eventos/(?P<id>\d+)/inscribir', [
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
        register_rest_route(self::API_NAMESPACE, '/eventos/(?P<id>\d+)/cancelar', [
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
        $where = "WHERE 1=1";
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
        $query = "SELECT * FROM $table_name $where ORDER BY fecha DESC LIMIT %d OFFSET %d";
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
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $evento_id),
            ARRAY_A
        );

        if (!$evento) {
            return new WP_Error(
                'evento_not_found',
                __('Evento no encontrado', 'flavor-chat-ia'),
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
                __('Evento no encontrado', 'flavor-chat-ia'),
                ['status' => 404]
            );
        }

        // Verificar si ya está inscrito
        if ($this->usuario_inscrito($evento_id, $user_id)) {
            return new WP_Error(
                'already_registered',
                __('Ya estás inscrito en este evento', 'flavor-chat-ia'),
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
                    __('No quedan plazas disponibles', 'flavor-chat-ia'),
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
                __('Error al crear la inscripción', 'flavor-chat-ia'),
                ['status' => 500]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Inscripción realizada correctamente', 'flavor-chat-ia'),
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
                __('No se encontró la inscripción', 'flavor-chat-ia'),
                ['status' => 404]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'message' => __('Inscripción cancelada correctamente', 'flavor-chat-ia'),
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
}

// Inicializar API
Flavor_Eventos_API::get_instance();
