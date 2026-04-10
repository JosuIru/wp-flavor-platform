<?php
/**
 * API REST para Bicicletas Compartidas (Móvil)
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Bicicletas_Compartidas_API {

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
        // GET /bicicletas-compartidas
        flavor_register_rest_route(self::NAMESPACE, '/bicicletas-compartidas', [
            'methods' => 'GET',
            'callback' => [$this, 'get_sistema'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /bicicletas-compartidas/alquilar
        flavor_register_rest_route(self::NAMESPACE, '/bicicletas-compartidas/alquilar', [
            'methods' => 'POST',
            'callback' => [$this, 'alquilar_bicicleta'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'estacion_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        // POST /bicicletas-compartidas/finalizar
        flavor_register_rest_route(self::NAMESPACE, '/bicicletas-compartidas/finalizar', [
            'methods' => 'POST',
            'callback' => [$this, 'finalizar_alquiler'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'estacion_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);
    }

    public function get_sistema($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';
        $tabla_alquileres = $wpdb->prefix . 'flavor_bicicletas_alquileres';

        // Estaciones con disponibilidad
        $estaciones = $wpdb->get_results(
            "SELECT * FROM $tabla_estaciones WHERE activa = 1 ORDER BY nombre",
            ARRAY_A
        );

        $estaciones_formateadas = array_map([$this, 'formatear_estacion'], $estaciones);

        // Alquiler activo del usuario
        $alquiler_activo = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, e.nombre as estacion_origen_nombre
            FROM $tabla_alquileres a
            INNER JOIN $tabla_estaciones e ON a.estacion_origen_id = e.id
            WHERE a.usuario_id = %d AND a.estado = 'activo'
            ORDER BY a.fecha_inicio DESC
            LIMIT 1",
            $usuario_id
        ), ARRAY_A);

        $alquiler_formateado = null;
        if ($alquiler_activo) {
            $inicio = new DateTime($alquiler_activo['fecha_inicio']);
            $ahora = new DateTime();
            $diferencia = $ahora->diff($inicio);
            $minutos = ($diferencia->days * 24 * 60) + ($diferencia->h * 60) + $diferencia->i;

            $alquiler_formateado = [
                'id' => (int) $alquiler_activo['id'],
                'bicicleta_id' => (int) $alquiler_activo['bicicleta_id'],
                'estacion_origen' => $alquiler_activo['estacion_origen_nombre'],
                'fecha_inicio' => mysql2date('c', $alquiler_activo['fecha_inicio']),
                'duracion_minutos' => $minutos,
                'coste_estimado' => $this->calcular_coste($minutos),
            ];
        }

        // Historial de alquileres
        $historial = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, e1.nombre as estacion_origen_nombre, e2.nombre as estacion_destino_nombre
            FROM $tabla_alquileres a
            INNER JOIN $tabla_estaciones e1 ON a.estacion_origen_id = e1.id
            LEFT JOIN $tabla_estaciones e2 ON a.estacion_destino_id = e2.id
            WHERE a.usuario_id = %d AND a.estado = 'finalizado'
            ORDER BY a.fecha_inicio DESC
            LIMIT 20",
            $usuario_id
        ), ARRAY_A);

        $historial_formateado = array_map([$this, 'formatear_alquiler_historial'], $historial);

        return new WP_REST_Response([
            'success' => true,
            'estaciones' => $estaciones_formateadas,
            'alquiler_activo' => $alquiler_formateado,
            'historial' => $historial_formateado,
        ], 200);
    }

    public function alquilar_bicicleta($request) {
        $usuario_id = get_current_user_id();
        $estacion_id = $request->get_param('estacion_id');

        global $wpdb;
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_alquileres = $wpdb->prefix . 'flavor_bicicletas_alquileres';

        // Verificar si ya tiene un alquiler activo
        $alquiler_activo = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_alquileres
            WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        if ($alquiler_activo > 0) {
            return new WP_Error('alquiler_activo', 'Ya tienes un alquiler activo', ['status' => 400]);
        }

        // Verificar disponibilidad en la estación
        $estacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_estaciones WHERE id = %d AND activa = 1",
            $estacion_id
        ));

        if (!$estacion || $estacion->bicicletas_disponibles <= 0) {
            return new WP_Error('sin_bicicletas', 'No hay bicicletas disponibles en esta estación', ['status' => 400]);
        }

        // Buscar una bicicleta disponible
        $bicicleta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_bicicletas
            WHERE estacion_actual_id = %d AND estado = 'disponible'
            LIMIT 1",
            $estacion_id
        ));

        if (!$bicicleta) {
            return new WP_Error('sin_bicicletas', 'No hay bicicletas disponibles', ['status' => 400]);
        }

        // Crear alquiler
        $resultado = $wpdb->insert(
            $tabla_alquileres,
            [
                'usuario_id' => $usuario_id,
                'bicicleta_id' => $bicicleta->id,
                'estacion_origen_id' => $estacion_id,
                'fecha_inicio' => current_time('mysql'),
                'estado' => 'activo',
            ],
            ['%d', '%d', '%d', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('error_alquilar', 'Error al crear el alquiler', ['status' => 500]);
        }

        // Actualizar bicicleta
        $wpdb->update(
            $tabla_bicicletas,
            ['estado' => 'en_uso'],
            ['id' => $bicicleta->id],
            ['%s'],
            ['%d']
        );

        // Actualizar disponibilidad de la estación
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_estaciones SET bicicletas_disponibles = bicicletas_disponibles - 1 WHERE id = %d",
            $estacion_id
        ));

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => 'Bicicleta alquilada correctamente',
            'alquiler_id' => $wpdb->insert_id,
        ], 201);
    }

    public function finalizar_alquiler($request) {
        $usuario_id = get_current_user_id();
        $estacion_destino_id = $request->get_param('estacion_id');

        global $wpdb;
        $tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_estaciones';
        $tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas';
        $tabla_alquileres = $wpdb->prefix . 'flavor_bicicletas_alquileres';

        // Buscar alquiler activo
        $alquiler = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_alquileres
            WHERE usuario_id = %d AND estado = 'activo'
            ORDER BY fecha_inicio DESC LIMIT 1",
            $usuario_id
        ));

        if (!$alquiler) {
            return new WP_Error('sin_alquiler', 'No tienes ningún alquiler activo', ['status' => 400]);
        }

        // Verificar que la estación tiene espacio
        $estacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_estaciones WHERE id = %d AND activa = 1",
            $estacion_destino_id
        ));

        if (!$estacion || $estacion->espacios_disponibles <= 0) {
            return new WP_Error('sin_espacio', 'No hay espacio disponible en esta estación', ['status' => 400]);
        }

        // Calcular duración y coste
        $inicio = new DateTime($alquiler->fecha_inicio);
        $fin = new DateTime();
        $diferencia = $fin->diff($inicio);
        $minutos = ($diferencia->days * 24 * 60) + ($diferencia->h * 60) + $diferencia->i;
        $coste = $this->calcular_coste($minutos);

        // Finalizar alquiler
        $wpdb->update(
            $tabla_alquileres,
            [
                'estacion_destino_id' => $estacion_destino_id,
                'fecha_fin' => current_time('mysql'),
                'duracion_minutos' => $minutos,
                'coste' => $coste,
                'estado' => 'finalizado',
            ],
            ['id' => $alquiler->id],
            ['%d', '%s', '%d', '%f', '%s'],
            ['%d']
        );

        // Actualizar bicicleta
        $wpdb->update(
            $tabla_bicicletas,
            [
                'estado' => 'disponible',
                'estacion_actual_id' => $estacion_destino_id,
            ],
            ['id' => $alquiler->bicicleta_id],
            ['%s', '%d'],
            ['%d']
        );

        // Actualizar disponibilidad de estaciones
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_estaciones SET espacios_disponibles = espacios_disponibles - 1 WHERE id = %d",
            $estacion_destino_id
        ));
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_estaciones SET bicicletas_disponibles = bicicletas_disponibles + 1 WHERE id = %d",
            $estacion_destino_id
        ));

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => 'Alquiler finalizado correctamente',
            'duracion_minutos' => $minutos,
            'coste' => $coste,
        ], 200);
    }

    private function formatear_estacion($estacion) {
        if (!$estacion) return null;

        return [
            'id' => (int) $estacion['id'],
            'nombre' => $estacion['nombre'],
            'direccion' => $estacion['direccion'],
            'bicicletas_disponibles' => (int) ($estacion['bicicletas_disponibles'] ?? 0),
            'espacios_disponibles' => (int) ($estacion['espacios_disponibles'] ?? 0),
            'coordenadas' => [
                'lat' => isset($estacion['latitud']) ? (float) $estacion['latitud'] : 0,
                'lng' => isset($estacion['longitud']) ? (float) $estacion['longitud'] : 0,
            ],
        ];
    }

    private function formatear_alquiler_historial($alquiler) {
        if (!$alquiler) return null;

        return [
            'id' => (int) $alquiler['id'],
            'estacion_origen' => $alquiler['estacion_origen_nombre'],
            'estacion_destino' => $alquiler['estacion_destino_nombre'] ?? 'N/A',
            'fecha_inicio' => mysql2date('c', $alquiler['fecha_inicio']),
            'fecha_fin' => isset($alquiler['fecha_fin']) ? mysql2date('c', $alquiler['fecha_fin']) : null,
            'duracion_minutos' => (int) ($alquiler['duracion_minutos'] ?? 0),
            'coste' => (float) ($alquiler['coste'] ?? 0),
        ];
    }

    private function calcular_coste($minutos) {
        // Primeros 30 minutos gratis
        if ($minutos <= 30) return 0.0;

        // 0.5€ cada 30 minutos adicionales
        $bloques = ceil(($minutos - 30) / 30);
        return $bloques * 0.5;
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
