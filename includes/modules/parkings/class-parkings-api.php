<?php
/**
 * API REST para Parkings (Móvil)
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Parkings_API {

    const NAMESPACE = FLAVOR_PLATFORM_REST_NAMESPACE;

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
        // GET /parkings
        flavor_register_rest_route(self::NAMESPACE, '/parkings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_parkings'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /parkings/reservar
        flavor_register_rest_route(self::NAMESPACE, '/parkings/reservar', [
            'methods' => 'POST',
            'callback' => [$this, 'reservar_parking'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'parking_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
                'fecha_entrada' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'fecha_salida' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // POST /parkings/reservas/{id}/extender
        flavor_register_rest_route(self::NAMESPACE, '/parkings/reservas/(?P<id>\d+)/extender', [
            'methods' => 'POST',
            'callback' => [$this, 'extender_reserva'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'horas' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        // DELETE /parkings/reservas/{id}
        flavor_register_rest_route(self::NAMESPACE, '/parkings/reservas/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'cancelar_reserva'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
    }

    public function get_parkings($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_parkings = $wpdb->prefix . 'flavor_parkings';
        $tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';

        // Parkings disponibles
        $parkings = $wpdb->get_results(
            "SELECT * FROM $tabla_parkings WHERE activo = 1 ORDER BY nombre",
            ARRAY_A
        );

        $parkings_formateados = array_map([$this, 'formatear_parking'], $parkings);

        // Reservas del usuario
        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, p.nombre as parking_nombre
            FROM $tabla_reservas r
            INNER JOIN $tabla_parkings p ON r.parking_id = p.id
            WHERE r.usuario_id = %d
            ORDER BY r.fecha_entrada DESC
            LIMIT 20",
            $usuario_id
        ), ARRAY_A);

        $reservas_formateadas = array_map([$this, 'formatear_reserva'], $reservas);

        return new WP_REST_Response([
            'success' => true,
            'parkings' => $parkings_formateados,
            'mis_reservas' => $reservas_formateadas,
        ], 200);
    }

    public function reservar_parking($request) {
        $usuario_id = get_current_user_id();
        $parking_id = $request->get_param('parking_id');
        $fecha_entrada = $request->get_param('fecha_entrada');
        $fecha_salida = $request->get_param('fecha_salida');

        global $wpdb;
        $tabla_parkings = $wpdb->prefix . 'flavor_parkings';
        $tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';

        // Verificar parking
        $parking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_parkings WHERE id = %d AND activo = 1",
            $parking_id
        ));

        if (!$parking || $parking->plazas_disponibles <= 0) {
            return new WP_Error('sin_plazas', 'No hay plazas disponibles', ['status' => 400]);
        }

        // Validar fechas
        $entrada = strtotime($fecha_entrada);
        $salida = strtotime($fecha_salida);

        if ($entrada >= $salida) {
            return new WP_Error('fechas_invalidas', 'La fecha de salida debe ser posterior a la entrada', ['status' => 400]);
        }

        // Calcular coste
        $horas = ceil(($salida - $entrada) / 3600);
        $coste = $this->calcular_coste($horas, $parking);

        // Asignar plaza
        $plaza = 'Plaza ' . rand(1, $parking->plazas_totales);

        // Crear reserva
        $resultado = $wpdb->insert(
            $tabla_reservas,
            [
                'usuario_id' => $usuario_id,
                'parking_id' => $parking_id,
                'plaza' => $plaza,
                'fecha_entrada' => date('Y-m-d H:i:s', $entrada),
                'fecha_salida' => date('Y-m-d H:i:s', $salida),
                'coste' => $coste,
                'estado' => 'activa',
                'fecha_reserva' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%f', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('error_reservar', 'Error al crear la reserva', ['status' => 500]);
        }

        // Actualizar disponibilidad
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_parkings SET plazas_disponibles = plazas_disponibles - 1 WHERE id = %d",
            $parking_id
        ));

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => 'Reserva creada correctamente',
            'reserva_id' => $wpdb->insert_id,
            'plaza' => $plaza,
            'coste' => $coste,
        ], 201);
    }

    public function extender_reserva($request) {
        $usuario_id = get_current_user_id();
        $reserva_id = $request->get_param('id');
        $horas = $request->get_param('horas');

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';
        $tabla_parkings = $wpdb->prefix . 'flavor_parkings';

        // Verificar reserva
        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, p.tarifa_hora, p.tarifa_dia
            FROM $tabla_reservas r
            INNER JOIN $tabla_parkings p ON r.parking_id = p.id
            WHERE r.id = %d AND r.usuario_id = %d AND r.estado = 'activa'",
            $reserva_id,
            $usuario_id
        ));

        if (!$reserva) {
            return new WP_Error('reserva_no_encontrada', 'Reserva no encontrada', ['status' => 404]);
        }

        // Extender fecha de salida
        $nueva_salida = date('Y-m-d H:i:s', strtotime($reserva->fecha_salida) + ($horas * 3600));
        $coste_adicional = $horas * floatval($reserva->tarifa_hora);

        $wpdb->update(
            $tabla_reservas,
            [
                'fecha_salida' => $nueva_salida,
                'coste' => floatval($reserva->coste) + $coste_adicional,
            ],
            ['id' => $reserva_id],
            ['%s', '%f'],
            ['%d']
        );

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => 'Reserva extendida correctamente',
            'nueva_fecha_salida' => $nueva_salida,
            'coste_adicional' => $coste_adicional,
        ], 200);
    }

    public function cancelar_reserva($request) {
        $usuario_id = get_current_user_id();
        $reserva_id = $request->get_param('id');

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';
        $tabla_parkings = $wpdb->prefix . 'flavor_parkings';

        // Verificar reserva
        $reserva = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_reservas
            WHERE id = %d AND usuario_id = %d",
            $reserva_id,
            $usuario_id
        ));

        if (!$reserva) {
            return new WP_Error('reserva_no_encontrada', 'Reserva no encontrada', ['status' => 404]);
        }

        // Cancelar reserva
        $wpdb->update(
            $tabla_reservas,
            ['estado' => 'cancelada'],
            ['id' => $reserva_id],
            ['%s'],
            ['%d']
        );

        // Liberar plaza
        if ($reserva->estado === 'activa') {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_parkings SET plazas_disponibles = plazas_disponibles + 1 WHERE id = %d",
                $reserva->parking_id
            ));
        }

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => 'Reserva cancelada correctamente',
        ], 200);
    }

    private function formatear_parking($parking) {
        if (!$parking) return null;

        return [
            'id' => (int) $parking['id'],
            'nombre' => $parking['nombre'],
            'direccion' => $parking['direccion'],
            'tipo' => $parking['tipo'] ?? 'publico',
            'plazas_disponibles' => (int) ($parking['plazas_disponibles'] ?? 0),
            'plazas_totales' => (int) ($parking['plazas_totales'] ?? 0),
            'tarifa_hora' => (float) ($parking['tarifa_hora'] ?? 0),
            'tarifa_dia' => (float) ($parking['tarifa_dia'] ?? 0),
            'descripcion' => $parking['descripcion'] ?? '',
            'horario' => $parking['horario'] ?? '24h',
            'servicios' => $parking['servicios'] ?? '',
            'coordenadas' => [
                'lat' => isset($parking['latitud']) ? (float) $parking['latitud'] : 0,
                'lng' => isset($parking['longitud']) ? (float) $parking['longitud'] : 0,
            ],
        ];
    }

    private function formatear_reserva($reserva) {
        if (!$reserva) return null;

        return [
            'id' => (int) $reserva['id'],
            'parking_nombre' => $reserva['parking_nombre'],
            'plaza' => $reserva['plaza'],
            'fecha_entrada' => mysql2date('c', $reserva['fecha_entrada']),
            'fecha_salida' => mysql2date('c', $reserva['fecha_salida']),
            'coste' => (float) $reserva['coste'],
            'estado' => $reserva['estado'],
        ];
    }

    private function calcular_coste($horas, $parking) {
        $tarifa_hora = floatval($parking->tarifa_hora);
        $tarifa_dia = floatval($parking->tarifa_dia);

        // Si son más de 24 horas, usar tarifa por día
        if ($horas >= 24) {
            $dias = ceil($horas / 24);
            return $dias * $tarifa_dia;
        }

        return $horas * $tarifa_hora;
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
