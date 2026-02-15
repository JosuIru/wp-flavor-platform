<?php
/**
 * API REST para Reciclaje (Móvil)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Reciclaje_API {

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
        // GET /reciclaje/dashboard
        register_rest_route(self::NAMESPACE, '/reciclaje/dashboard', [
            'methods' => 'GET',
            'callback' => [$this, 'get_dashboard'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
    }

    public function get_dashboard($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
        $tabla_puntos_reciclaje = $wpdb->prefix . 'flavor_puntos_reciclaje';

        // Estadísticas personales del usuario
        $stats_usuario = $wpdb->get_row($wpdb->prepare(
            "SELECT
                IFNULL(SUM(cantidad_kg), 0) as kg_reciclados,
                IFNULL(SUM(cantidad_kg * 0.5), 0) as co2_ahorrado,
                IFNULL(SUM(puntos_ganados), 0) as puntos_totales
            FROM $tabla_depositos
            WHERE usuario_id = %d",
            $usuario_id
        ), ARRAY_A);

        // Ranking del usuario
        $ranking = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) + 1 FROM (
                SELECT usuario_id, SUM(puntos_ganados) as total_puntos
                FROM $tabla_depositos
                GROUP BY usuario_id
                HAVING total_puntos > (
                    SELECT IFNULL(SUM(puntos_ganados), 0)
                    FROM $tabla_depositos
                    WHERE usuario_id = %d
                )
            ) as ranking",
            $usuario_id
        ));

        // Estadísticas globales de la comunidad
        $stats_comunidad = $wpdb->get_row(
            "SELECT
                IFNULL(SUM(cantidad_kg), 0) as kg_reciclados,
                IFNULL(SUM(cantidad_kg * 0.5), 0) as co2_ahorrado,
                COUNT(DISTINCT usuario_id) as participantes
            FROM $tabla_depositos",
            ARRAY_A
        );

        // Meta mensual de la comunidad (ejemplo: 1000 kg)
        $meta_mensual = 1000;
        $kg_mes_actual = $wpdb->get_var(
            "SELECT IFNULL(SUM(cantidad_kg), 0)
            FROM $tabla_depositos
            WHERE MONTH(fecha_deposito) = MONTH(NOW()) AND YEAR(fecha_deposito) = YEAR(NOW())"
        );

        // Calendario de recogidas (próximas 5 fechas)
        $calendario = $this->obtener_calendario_recogidas();

        // Puntos de reciclaje cercanos
        $puntos = $wpdb->get_results(
            "SELECT * FROM $tabla_puntos_reciclaje WHERE estado = 'activo' ORDER BY nombre LIMIT 10",
            ARRAY_A
        );

        $puntos_formateados = array_map([$this, 'formatear_punto'], $puntos);

        // Tipos de residuos (datos estáticos)
        $tipos_residuos = $this->obtener_tipos_residuos();

        return new WP_REST_Response([
            'success' => true,
            'mi_estadistica' => [
                'kg_reciclados' => (float) ($stats_usuario['kg_reciclados'] ?? 0),
                'co2_ahorrado' => (float) ($stats_usuario['co2_ahorrado'] ?? 0),
                'puntos' => (int) ($stats_usuario['puntos_totales'] ?? 0),
                'ranking' => (int) ($ranking ?? 0),
            ],
            'estadisticas_comunidad' => [
                'kg_reciclados' => (float) ($stats_comunidad['kg_reciclados'] ?? 0),
                'co2_ahorrado' => (float) ($stats_comunidad['co2_ahorrado'] ?? 0),
                'participantes' => (int) ($stats_comunidad['participantes'] ?? 0),
                'meta_mensual' => (int) $meta_mensual,
                'progreso_mensual' => (float) $kg_mes_actual,
                'porcentaje_meta' => $meta_mensual > 0 ? round(($kg_mes_actual / $meta_mensual) * 100, 1) : 0,
            ],
            'calendario_recogidas' => $calendario,
            'puntos_reciclaje' => $puntos_formateados,
            'tipos_residuos' => $tipos_residuos,
        ], 200);
    }

    private function obtener_calendario_recogidas() {
        $fecha_actual = new DateTime();
        $recogidas = [];

        // Generar próximas 5 fechas de recogida
        for ($i = 0; $i < 5; $i++) {
            $fecha = clone $fecha_actual;
            $fecha->modify("+{$i} days");

            $dia_semana = (int) $fecha->format('N');
            $tipo = '';

            // Lunes: Orgánico
            if ($dia_semana === 1) $tipo = 'organico';
            // Martes: Papel
            if ($dia_semana === 2) $tipo = 'papel';
            // Miércoles: Plástico
            if ($dia_semana === 3) $tipo = 'plastico';
            // Jueves: Vidrio
            if ($dia_semana === 4) $tipo = 'vidrio';
            // Viernes: Metal
            if ($dia_semana === 5) $tipo = 'metal';

            if ($tipo) {
                $recogidas[] = [
                    'fecha' => $fecha->format('Y-m-d'),
                    'tipo' => $tipo,
                    'horario' => '08:00 - 12:00',
                ];
            }
        }

        return $recogidas;
    }

    private function obtener_tipos_residuos() {
        return [
            [
                'tipo' => 'organico',
                'nombre' => 'Orgánico',
                'icono' => 'compost',
                'color' => '#795548',
                'ejemplos' => ['Restos de comida', 'Cáscaras', 'Posos de café'],
            ],
            [
                'tipo' => 'papel',
                'nombre' => 'Papel y Cartón',
                'icono' => 'description',
                'color' => '#2196F3',
                'ejemplos' => ['Periódicos', 'Cajas de cartón', 'Revistas'],
            ],
            [
                'tipo' => 'plastico',
                'nombre' => 'Plástico',
                'icono' => 'recycling',
                'color' => '#FFEB3B',
                'ejemplos' => ['Botellas PET', 'Envases', 'Bolsas'],
            ],
            [
                'tipo' => 'vidrio',
                'nombre' => 'Vidrio',
                'icono' => 'wine_bar',
                'color' => '#4CAF50',
                'ejemplos' => ['Botellas', 'Frascos', 'Tarros'],
            ],
            [
                'tipo' => 'metal',
                'nombre' => 'Metal',
                'icono' => 'handyman',
                'color' => '#9E9E9E',
                'ejemplos' => ['Latas', 'Aluminio', 'Tapas metálicas'],
            ],
            [
                'tipo' => 'electronico',
                'nombre' => 'Electrónico',
                'icono' => 'devices',
                'color' => '#FF5722',
                'ejemplos' => ['Móviles', 'Pilas', 'Cables'],
            ],
        ];
    }

    private function formatear_punto($punto) {
        if (!$punto) return null;

        return [
            'id' => (int) $punto['id'],
            'nombre' => $punto['nombre'],
            'direccion' => $punto['direccion'],
            'tipo' => $punto['tipo'] ?: 'general',
            'horario' => $punto['horario'] ?: 'L-V: 9:00-20:00',
            'distancia' => isset($punto['distancia']) ? (float) $punto['distancia'] : 0,
            'coordenadas' => [
                'lat' => isset($punto['latitud']) ? (float) $punto['latitud'] : 0,
                'lng' => isset($punto['longitud']) ? (float) $punto['longitud'] : 0,
            ],
        ];
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
