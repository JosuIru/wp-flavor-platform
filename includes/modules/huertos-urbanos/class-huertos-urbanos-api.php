<?php
/**
 * API REST para Huertos Urbanos (Móvil)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Huertos_Urbanos_API {

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
        // GET /huertos-urbanos/dashboard
        register_rest_route(self::NAMESPACE, '/huertos-urbanos/dashboard', [
            'methods' => 'GET',
            'callback' => [$this, 'get_dashboard'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /huertos-urbanos/solicitar-parcela
        register_rest_route(self::NAMESPACE, '/huertos-urbanos/solicitar-parcela', [
            'methods' => 'POST',
            'callback' => [$this, 'solicitar_parcela'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'tamanio' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'observaciones' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);

        // POST /huertos-urbanos/tareas/{id}/completar
        register_rest_route(self::NAMESPACE, '/huertos-urbanos/tareas/(?P<id>\d+)/completar', [
            'methods' => 'POST',
            'callback' => [$this, 'completar_tarea'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /huertos-urbanos/intercambios/{id}/contactar
        register_rest_route(self::NAMESPACE, '/huertos-urbanos/intercambios/(?P<id>\d+)/contactar', [
            'methods' => 'POST',
            'callback' => [$this, 'contactar_intercambio'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'mensaje' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);
    }

    public function get_dashboard($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
        $tabla_tareas = $wpdb->prefix . 'flavor_huertos_tareas';
        $tabla_intercambios = $wpdb->prefix . 'flavor_huertos_intercambios';

        // Estadísticas globales
        $total_parcelas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_parcelas");
        $parcelas_ocupadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_parcelas WHERE estado = 'asignada'");
        $participantes = (int) $wpdb->get_var("SELECT COUNT(DISTINCT usuario_id) FROM $tabla_parcelas WHERE estado = 'asignada'");

        // Parcelas del usuario
        $mis_parcelas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_parcelas WHERE usuario_id = %d ORDER BY fecha_asignacion DESC",
            $usuario_id
        ));

        $parcelas_formateadas = array_map([$this, 'formatear_parcela'], $mis_parcelas);

        // Tareas pendientes
        $tareas_pendientes = $wpdb->get_results($wpdb->prepare(
            "SELECT t.* FROM $tabla_tareas t
            INNER JOIN $tabla_parcelas p ON t.parcela_id = p.id
            WHERE p.usuario_id = %d AND t.estado = 'pendiente'
            ORDER BY t.fecha_limite ASC
            LIMIT 10",
            $usuario_id
        ));

        $tareas_formateadas = array_map([$this, 'formatear_tarea'], $tareas_pendientes);

        // Calendario de cultivos (datos estáticos por estación)
        $calendario = $this->obtener_calendario_cultivos();

        // Intercambios disponibles
        $intercambios = $wpdb->get_results(
            "SELECT i.*, u.display_name as usuario_nombre
            FROM $tabla_intercambios i
            INNER JOIN {$wpdb->users} u ON i.usuario_id = u.ID
            WHERE i.estado = 'disponible' AND i.usuario_id != $usuario_id
            ORDER BY i.fecha_publicacion DESC
            LIMIT 20",
            ARRAY_A
        );

        $intercambios_formateados = array_map([$this, 'formatear_intercambio'], $intercambios);

        return new WP_REST_Response([
            'success' => true,
            'estadisticas' => [
                'total_parcelas' => $total_parcelas,
                'parcelas_ocupadas' => $parcelas_ocupadas,
                'participantes' => $participantes,
                'ocupacion_porcentaje' => $total_parcelas > 0 ? round(($parcelas_ocupadas / $total_parcelas) * 100, 1) : 0,
            ],
            'mis_parcelas' => $parcelas_formateadas,
            'tareas_pendientes' => $tareas_formateadas,
            'calendario_cultivos' => $calendario,
            'intercambios' => $intercambios_formateados,
        ], 200);
    }

    public function solicitar_parcela($request) {
        $usuario_id = get_current_user_id();
        $tamanio = $request->get_param('tamanio');
        $observaciones = $request->get_param('observaciones');

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_huertos_solicitudes';

        // Verificar si ya tiene una solicitud pendiente
        $solicitud_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla WHERE usuario_id = %d AND estado = 'pendiente'",
            $usuario_id
        ));

        if ($solicitud_existente > 0) {
            return new WP_Error('solicitud_existente', 'Ya tienes una solicitud pendiente', ['status' => 400]);
        }

        $resultado = $wpdb->insert(
            $tabla,
            [
                'usuario_id' => $usuario_id,
                'tamanio' => $tamanio,
                'observaciones' => $observaciones,
                'estado' => 'pendiente',
                'fecha_solicitud' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('error_solicitud', 'Error al crear la solicitud', ['status' => 500]);
        }

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => 'Solicitud enviada correctamente',
        ], 201);
    }

    public function completar_tarea($request) {
        $usuario_id = get_current_user_id();
        $tarea_id = $request->get_param('id');

        global $wpdb;
        $tabla_tareas = $wpdb->prefix . 'flavor_huertos_tareas';
        $tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';

        // Verificar permisos
        $tarea = $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, p.usuario_id FROM $tabla_tareas t
            INNER JOIN $tabla_parcelas p ON t.parcela_id = p.id
            WHERE t.id = %d",
            $tarea_id
        ));

        if (!$tarea || $tarea->usuario_id != $usuario_id) {
            return new WP_Error('sin_permiso', 'No tienes permiso para completar esta tarea', ['status' => 403]);
        }

        $wpdb->update(
            $tabla_tareas,
            ['estado' => 'completada', 'fecha_completado' => current_time('mysql')],
            ['id' => $tarea_id],
            ['%s', '%s'],
            ['%d']
        );

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => 'Tarea completada',
        ], 200);
    }

    public function contactar_intercambio($request) {
        $usuario_id = get_current_user_id();
        $intercambio_id = $request->get_param('id');
        $mensaje = $request->get_param('mensaje');

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_huertos_intercambios';

        $intercambio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d AND estado = 'disponible'",
            $intercambio_id
        ));

        if (!$intercambio) {
            return new WP_Error('no_disponible', 'Este intercambio ya no está disponible', ['status' => 404]);
        }

        // Enviar notificación al propietario
        $propietario = get_user_by('id', $intercambio->usuario_id);
        if ($propietario && !empty($propietario->user_email)) {
            $usuario = wp_get_current_user();
            $asunto = 'Interés en tu producto - Huertos Urbanos';
            $contenido = sprintf(
                "Hola %s,\n\n%s está interesado en tu producto: %s\n\nMensaje:\n%s\n\n",
                $propietario->display_name,
                $usuario->display_name,
                $intercambio->producto,
                $mensaje
            );
            wp_mail($propietario->user_email, $asunto, $contenido);
        }

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => 'Mensaje enviado correctamente',
        ], 200);
    }

    private function formatear_parcela($parcela) {
        if (!$parcela) return null;

        return [
            'id' => (int) $parcela->id,
            'numero' => $parcela->numero,
            'tamanio' => $parcela->tamanio,
            'estado' => $parcela->estado,
            'cultivo_actual' => $parcela->cultivo_actual ?: '',
            'fecha_asignacion' => $parcela->fecha_asignacion ? mysql2date('c', $parcela->fecha_asignacion) : null,
        ];
    }

    private function formatear_tarea($tarea) {
        if (!$tarea) return null;

        return [
            'id' => (int) $tarea->id,
            'tipo' => $tarea->tipo,
            'descripcion' => $tarea->descripcion,
            'estado' => $tarea->estado,
            'prioridad' => $tarea->prioridad ?: 'normal',
            'fecha_limite' => $tarea->fecha_limite ? mysql2date('c', $tarea->fecha_limite) : null,
        ];
    }

    private function formatear_intercambio($intercambio) {
        if (!$intercambio) return null;

        return [
            'id' => (int) $intercambio['id'],
            'producto' => $intercambio['producto'],
            'cantidad' => $intercambio['cantidad'],
            'unidad' => $intercambio['unidad'] ?: 'kg',
            'descripcion' => $intercambio['descripcion'] ?: '',
            'usuario_nombre' => $intercambio['usuario_nombre'],
            'fecha_publicacion' => mysql2date('c', $intercambio['fecha_publicacion']),
        ];
    }

    private function obtener_calendario_cultivos() {
        $mes_actual = (int) date('n');
        $estacion = $this->obtener_estacion($mes_actual);

        $cultivos_por_estacion = [
            'primavera' => [
                ['nombre' => 'Tomates', 'icono' => '🍅', 'mes_inicio' => 3, 'mes_fin' => 5],
                ['nombre' => 'Pimientos', 'icono' => '🫑', 'mes_inicio' => 3, 'mes_fin' => 5],
                ['nombre' => 'Lechugas', 'icono' => '🥬', 'mes_inicio' => 3, 'mes_fin' => 5],
            ],
            'verano' => [
                ['nombre' => 'Calabacines', 'icono' => '🥒', 'mes_inicio' => 6, 'mes_fin' => 8],
                ['nombre' => 'Berenjenas', 'icono' => '🍆', 'mes_inicio' => 6, 'mes_fin' => 8],
                ['nombre' => 'Melones', 'icono' => '🍈', 'mes_inicio' => 6, 'mes_fin' => 8],
            ],
            'otonio' => [
                ['nombre' => 'Zanahorias', 'icono' => '🥕', 'mes_inicio' => 9, 'mes_fin' => 11],
                ['nombre' => 'Cebollas', 'icono' => '🧅', 'mes_inicio' => 9, 'mes_fin' => 11],
                ['nombre' => 'Espinacas', 'icono' => '🥬', 'mes_inicio' => 9, 'mes_fin' => 11],
            ],
            'invierno' => [
                ['nombre' => 'Habas', 'icono' => '🫘', 'mes_inicio' => 12, 'mes_fin' => 2],
                ['nombre' => 'Guisantes', 'icono' => '🫛', 'mes_inicio' => 12, 'mes_fin' => 2],
                ['nombre' => 'Ajos', 'icono' => '🧄', 'mes_inicio' => 12, 'mes_fin' => 2],
            ],
        ];

        return [
            'estacion_actual' => $estacion,
            'cultivos' => $cultivos_por_estacion[$estacion] ?? [],
        ];
    }

    private function obtener_estacion($mes) {
        if ($mes >= 3 && $mes <= 5) return 'primavera';
        if ($mes >= 6 && $mes <= 8) return 'verano';
        if ($mes >= 9 && $mes <= 11) return 'otonio';
        return 'invierno';
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
