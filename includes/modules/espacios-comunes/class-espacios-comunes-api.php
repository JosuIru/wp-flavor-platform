<?php
/**
 * API REST para Espacios Comunes (Móvil)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Espacios_Comunes_API {

    const NAMESPACE = 'flavor-chat-ia/v1';

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        // GET /espacios-comunes/dashboard
        register_rest_route(self::NAMESPACE, '/espacios-comunes/dashboard', [
            'methods' => 'GET',
            'callback' => [$this, 'get_dashboard'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /espacios-comunes
        register_rest_route(self::NAMESPACE, '/espacios-comunes', [
            'methods' => 'GET',
            'callback' => [$this, 'get_espacios'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /espacios-comunes/{id}
        register_rest_route(self::NAMESPACE, '/espacios-comunes/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_espacio'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /espacios-comunes/{id}/disponibilidad
        register_rest_route(self::NAMESPACE, '/espacios-comunes/(?P<id>\d+)/disponibilidad', [
            'methods' => 'GET',
            'callback' => [$this, 'get_disponibilidad'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /espacios-comunes/mis-reservas
        register_rest_route(self::NAMESPACE, '/espacios-comunes/mis-reservas', [
            'methods' => 'GET',
            'callback' => [$this, 'get_mis_reservas'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /espacios-comunes/{id}/reservar
        register_rest_route(self::NAMESPACE, '/espacios-comunes/(?P<id>\d+)/reservar', [
            'methods' => 'POST',
            'callback' => [$this, 'reservar'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'fecha' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'hora_inicio' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'hora_fin' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'motivo' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'num_asistentes' => [
                    'type' => 'integer',
                    'default' => 1,
                ],
            ],
        ]);

        // DELETE /espacios-comunes/reservas/{id}
        register_rest_route(self::NAMESPACE, '/espacios-comunes/reservas/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'cancelar_reserva'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /espacios-comunes/tipos
        register_rest_route(self::NAMESPACE, '/espacios-comunes/tipos', [
            'methods' => 'GET',
            'callback' => [$this, 'get_tipos'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
    }

    public function get_dashboard($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        // Mis reservas activas
        $mis_reservas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reservas
            WHERE usuario_id = %d AND estado IN ('pendiente', 'confirmada')
            AND fecha >= CURDATE()",
            $usuario_id
        ));

        // Espacios disponibles
        $espacios_disponibles = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_espacios WHERE estado = 'activo'"
        );

        // Próximas reservas del usuario
        $proximas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, e.nombre as espacio_nombre, e.imagen_url, e.ubicacion
            FROM $tabla_reservas r
            INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
            WHERE r.usuario_id = %d AND r.estado IN ('pendiente', 'confirmada')
            AND r.fecha >= CURDATE()
            ORDER BY r.fecha ASC, r.hora_inicio ASC
            LIMIT 5",
            $usuario_id
        ), ARRAY_A);

        // Espacios populares (más reservados)
        $populares = $wpdb->get_results(
            "SELECT e.*,
                (SELECT COUNT(*) FROM $tabla_reservas r WHERE r.espacio_id = e.id AND r.estado = 'confirmada') as total_reservas
            FROM $tabla_espacios e
            WHERE e.estado = 'activo'
            ORDER BY total_reservas DESC
            LIMIT 5",
            ARRAY_A
        );

        return new WP_REST_Response([
            'success' => true,
            'mi_estadistica' => [
                'reservas_activas' => (int) $mis_reservas,
            ],
            'estadisticas_generales' => [
                'espacios_disponibles' => (int) $espacios_disponibles,
            ],
            'proximas_reservas' => array_map(function($r) {
                return [
                    'id' => (int) $r['id'],
                    'espacio_id' => (int) $r['espacio_id'],
                    'espacio_nombre' => $r['espacio_nombre'],
                    'imagen' => $r['imagen_url'] ?? '',
                    'ubicacion' => $r['ubicacion'] ?? '',
                    'fecha' => $r['fecha'],
                    'hora_inicio' => $r['hora_inicio'],
                    'hora_fin' => $r['hora_fin'],
                    'estado' => $r['estado'],
                ];
            }, $proximas),
            'espacios_populares' => array_map([$this, 'formatear_espacio'], $populares),
        ], 200);
    }

    public function get_espacios($request) {
        global $wpdb;

        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $tipo = $request->get_param('tipo');
        $busqueda = $request->get_param('busqueda');

        $where = ["estado = 'activo'"];
        $params = [];

        if ($tipo) {
            $where[] = "tipo = %s";
            $params[] = $tipo;
        }

        if ($busqueda) {
            $where[] = "(nombre LIKE %s OR descripcion LIKE %s)";
            $busqueda_like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $busqueda_like;
            $params[] = $busqueda_like;
        }

        $where_sql = implode(' AND ', $where);
        $query = "SELECT * FROM $tabla_espacios WHERE $where_sql ORDER BY nombre ASC";

        if (!empty($params)) {
            $espacios = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
        } else {
            $espacios = $wpdb->get_results($query, ARRAY_A);
        }

        return new WP_REST_Response([
            'success' => true,
            'espacios' => array_map([$this, 'formatear_espacio'], $espacios),
        ], 200);
    }

    public function get_espacio($request) {
        $espacio_id = $request->get_param('id');
        global $wpdb;

        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $tabla_equipamiento = $wpdb->prefix . 'flavor_espacios_equipamiento';

        $espacio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_espacios WHERE id = %d",
            $espacio_id
        ), ARRAY_A);

        if (!$espacio) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Espacio no encontrado',
            ], 404);
        }

        // Equipamiento
        $equipamiento = $wpdb->get_results($wpdb->prepare(
            "SELECT nombre, cantidad, descripcion FROM $tabla_equipamiento WHERE espacio_id = %d",
            $espacio_id
        ), ARRAY_A);

        $espacio_formateado = $this->formatear_espacio($espacio);
        $espacio_formateado['equipamiento'] = $equipamiento;

        return new WP_REST_Response([
            'success' => true,
            'espacio' => $espacio_formateado,
        ], 200);
    }

    public function get_disponibilidad($request) {
        $espacio_id = $request->get_param('id');
        $fecha = $request->get_param('fecha') ?: date('Y-m-d');
        global $wpdb;

        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        // Obtener horarios del espacio
        $espacio = $wpdb->get_row($wpdb->prepare(
            "SELECT horario_apertura, horario_cierre, dias_disponibles FROM $tabla_espacios WHERE id = %d",
            $espacio_id
        ));

        if (!$espacio) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Espacio no encontrado',
            ], 404);
        }

        // Reservas del día
        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT hora_inicio, hora_fin, estado
            FROM $tabla_reservas
            WHERE espacio_id = %d AND fecha = %s AND estado IN ('pendiente', 'confirmada')
            ORDER BY hora_inicio ASC",
            $espacio_id,
            $fecha
        ), ARRAY_A);

        // Generar slots disponibles
        $hora_apertura = $espacio->horario_apertura ?: '08:00:00';
        $hora_cierre = $espacio->horario_cierre ?: '22:00:00';

        $slots = [];
        $hora_actual = strtotime($hora_apertura);
        $hora_fin = strtotime($hora_cierre);

        while ($hora_actual < $hora_fin) {
            $slot_inicio = date('H:i', $hora_actual);
            $slot_fin = date('H:i', $hora_actual + 3600); // slots de 1 hora

            $disponible = true;
            foreach ($reservas as $reserva) {
                $reserva_inicio = strtotime($reserva['hora_inicio']);
                $reserva_fin = strtotime($reserva['hora_fin']);

                if ($hora_actual >= $reserva_inicio && $hora_actual < $reserva_fin) {
                    $disponible = false;
                    break;
                }
            }

            $slots[] = [
                'hora_inicio' => $slot_inicio,
                'hora_fin' => $slot_fin,
                'disponible' => $disponible,
            ];

            $hora_actual += 3600;
        }

        return new WP_REST_Response([
            'success' => true,
            'fecha' => $fecha,
            'horario' => [
                'apertura' => substr($hora_apertura, 0, 5),
                'cierre' => substr($hora_cierre, 0, 5),
            ],
            'slots' => $slots,
            'reservas' => $reservas,
        ], 200);
    }

    public function get_mis_reservas($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, e.nombre as espacio_nombre, e.imagen_url, e.ubicacion, e.tipo
            FROM $tabla_reservas r
            INNER JOIN $tabla_espacios e ON r.espacio_id = e.id
            WHERE r.usuario_id = %d
            ORDER BY r.fecha DESC, r.hora_inicio DESC
            LIMIT 50",
            $usuario_id
        ), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'reservas' => array_map(function($r) {
                return [
                    'id' => (int) $r['id'],
                    'espacio_id' => (int) $r['espacio_id'],
                    'espacio_nombre' => $r['espacio_nombre'],
                    'imagen' => $r['imagen_url'] ?? '',
                    'ubicacion' => $r['ubicacion'] ?? '',
                    'tipo' => $r['tipo'] ?? '',
                    'fecha' => $r['fecha'],
                    'hora_inicio' => $r['hora_inicio'],
                    'hora_fin' => $r['hora_fin'],
                    'motivo' => $r['motivo'] ?? '',
                    'num_asistentes' => (int) ($r['num_asistentes'] ?? 1),
                    'estado' => $r['estado'],
                    'fecha_solicitud' => $r['fecha_solicitud'] ?? '',
                ];
            }, $reservas),
        ], 200);
    }

    public function reservar($request) {
        $espacio_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        // Verificar que el espacio existe
        $espacio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_espacios WHERE id = %d AND estado = 'activo'",
            $espacio_id
        ));

        if (!$espacio) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Espacio no encontrado o no disponible',
            ], 404);
        }

        $fecha = $request->get_param('fecha');
        $hora_inicio = $request->get_param('hora_inicio');
        $hora_fin = $request->get_param('hora_fin');

        // Verificar que no hay conflicto con otras reservas
        $conflicto = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_reservas
            WHERE espacio_id = %d AND fecha = %s AND estado IN ('pendiente', 'confirmada')
            AND (
                (hora_inicio <= %s AND hora_fin > %s)
                OR (hora_inicio < %s AND hora_fin >= %s)
                OR (hora_inicio >= %s AND hora_fin <= %s)
            )",
            $espacio_id,
            $fecha,
            $hora_inicio, $hora_inicio,
            $hora_fin, $hora_fin,
            $hora_inicio, $hora_fin
        ));

        if ($conflicto) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'El horario seleccionado no está disponible',
            ], 400);
        }

        // Crear reserva
        $estado_inicial = $espacio->requiere_aprobacion ? 'pendiente' : 'confirmada';

        $wpdb->insert($tabla_reservas, [
            'espacio_id' => $espacio_id,
            'usuario_id' => $usuario_id,
            'fecha' => $fecha,
            'hora_inicio' => $hora_inicio,
            'hora_fin' => $hora_fin,
            'motivo' => $request->get_param('motivo') ?: '',
            'num_asistentes' => $request->get_param('num_asistentes') ?: 1,
            'estado' => $estado_inicial,
            'fecha_solicitud' => current_time('mysql'),
        ]);

        $mensaje = ($estado_inicial === 'pendiente')
            ? 'Reserva pendiente de aprobación'
            : 'Reserva confirmada';

        return new WP_REST_Response([
            'success' => true,
            'message' => $mensaje,
            'reserva_id' => $wpdb->insert_id,
            'estado' => $estado_inicial,
        ], 201);
    }

    public function cancelar_reserva($request) {
        $reserva_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

        $resultado = $wpdb->update(
            $tabla_reservas,
            ['estado' => 'cancelada'],
            [
                'id' => $reserva_id,
                'usuario_id' => $usuario_id,
            ]
        );

        if ($resultado === false || $resultado === 0) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'No se encontró la reserva o no tienes permiso para cancelarla',
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Reserva cancelada',
        ], 200);
    }

    public function get_tipos($request) {
        global $wpdb;

        $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

        $tipos = $wpdb->get_col(
            "SELECT DISTINCT tipo FROM $tabla_espacios
            WHERE tipo IS NOT NULL AND tipo != '' AND estado = 'activo'
            ORDER BY tipo"
        );

        return new WP_REST_Response([
            'success' => true,
            'tipos' => $tipos,
        ], 200);
    }

    private function formatear_espacio($espacio) {
        if (!$espacio) return null;

        return [
            'id' => (int) $espacio['id'],
            'nombre' => $espacio['nombre'],
            'descripcion' => $espacio['descripcion'] ?? '',
            'tipo' => $espacio['tipo'] ?? '',
            'imagen' => $espacio['imagen_url'] ?? '',
            'capacidad' => (int) ($espacio['capacidad'] ?? 0),
            'ubicacion' => $espacio['ubicacion'] ?? '',
            'piso' => $espacio['piso'] ?? '',
            'horario_apertura' => $espacio['horario_apertura'] ?? '08:00',
            'horario_cierre' => $espacio['horario_cierre'] ?? '22:00',
            'dias_disponibles' => $espacio['dias_disponibles'] ?? 'L-V',
            'precio_hora' => (float) ($espacio['precio_hora'] ?? 0),
            'requiere_aprobacion' => !empty($espacio['requiere_aprobacion']),
            'normas' => $espacio['normas'] ?? '',
            'estado' => $espacio['estado'],
        ];
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
