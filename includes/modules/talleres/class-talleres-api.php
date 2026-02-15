<?php
/**
 * API REST para Talleres (Móvil)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Talleres_API {

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
        // GET /talleres/dashboard
        register_rest_route(self::NAMESPACE, '/talleres/dashboard', [
            'methods' => 'GET',
            'callback' => [$this, 'get_dashboard'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /talleres
        register_rest_route(self::NAMESPACE, '/talleres', [
            'methods' => 'GET',
            'callback' => [$this, 'get_talleres'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /talleres/mis-talleres
        register_rest_route(self::NAMESPACE, '/talleres/mis-talleres', [
            'methods' => 'GET',
            'callback' => [$this, 'get_mis_talleres'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /talleres/{id}
        register_rest_route(self::NAMESPACE, '/talleres/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_taller'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /talleres/{id}/inscribir
        register_rest_route(self::NAMESPACE, '/talleres/(?P<id>\d+)/inscribir', [
            'methods' => 'POST',
            'callback' => [$this, 'inscribirse'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // DELETE /talleres/{id}/cancelar
        register_rest_route(self::NAMESPACE, '/talleres/(?P<id>\d+)/cancelar', [
            'methods' => 'DELETE',
            'callback' => [$this, 'cancelar_inscripcion'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /talleres/{id}/valorar
        register_rest_route(self::NAMESPACE, '/talleres/(?P<id>\d+)/valorar', [
            'methods' => 'POST',
            'callback' => [$this, 'valorar_taller'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'puntuacion' => [
                    'required' => true,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 5,
                ],
                'comentario' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);

        // GET /talleres/categorias
        register_rest_route(self::NAMESPACE, '/talleres/categorias', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categorias'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
    }

    public function get_dashboard($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';

        // Mis inscripciones activas
        $mis_inscripciones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_inscripciones WHERE usuario_id = %d AND estado = 'confirmada'",
            $usuario_id
        ));

        // Talleres completados
        $completados = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_inscripciones WHERE usuario_id = %d AND estado = 'completada'",
            $usuario_id
        ));

        // Talleres disponibles
        $talleres_disponibles = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_talleres WHERE estado IN ('publicado', 'inscripciones_abiertas')"
        );

        // Próximas sesiones del usuario
        $proximas_sesiones = $wpdb->get_results($wpdb->prepare(
            "SELECT t.titulo, t.imagen_url, s.fecha, s.hora_inicio, s.hora_fin, s.ubicacion
            FROM $tabla_talleres t
            INNER JOIN $tabla_inscripciones i ON t.id = i.taller_id
            INNER JOIN $tabla_sesiones s ON t.id = s.taller_id
            WHERE i.usuario_id = %d AND i.estado = 'confirmada'
            AND s.fecha >= CURDATE()
            ORDER BY s.fecha ASC, s.hora_inicio ASC
            LIMIT 5",
            $usuario_id
        ), ARRAY_A);

        // Talleres destacados
        $destacados = $wpdb->get_results(
            "SELECT t.*,
                (SELECT COUNT(*) FROM $tabla_inscripciones i WHERE i.taller_id = t.id AND i.estado IN ('confirmada', 'completada')) as inscritos
            FROM $tabla_talleres t
            WHERE t.estado IN ('publicado', 'inscripciones_abiertas')
            AND t.destacado = 1
            ORDER BY t.fecha_creacion DESC
            LIMIT 5",
            ARRAY_A
        );

        return new WP_REST_Response([
            'success' => true,
            'mi_estadistica' => [
                'inscripciones_activas' => (int) $mis_inscripciones,
                'talleres_completados' => (int) $completados,
            ],
            'estadisticas_generales' => [
                'talleres_disponibles' => (int) $talleres_disponibles,
            ],
            'proximas_sesiones' => $proximas_sesiones,
            'talleres_destacados' => array_map([$this, 'formatear_taller'], $destacados),
        ], 200);
    }

    public function get_talleres($request) {
        global $wpdb;

        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';

        $categoria = $request->get_param('categoria');
        $modalidad = $request->get_param('modalidad');
        $busqueda = $request->get_param('busqueda');
        $limite = $request->get_param('limite') ?: 50;

        $where = ["t.estado IN ('publicado', 'inscripciones_abiertas', 'en_curso')"];
        $params = [];

        if ($categoria) {
            $where[] = "t.categoria = %s";
            $params[] = $categoria;
        }

        if ($modalidad) {
            $where[] = "t.modalidad = %s";
            $params[] = $modalidad;
        }

        if ($busqueda) {
            $where[] = "(t.titulo LIKE %s OR t.descripcion LIKE %s)";
            $busqueda_like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $busqueda_like;
            $params[] = $busqueda_like;
        }

        $where_sql = implode(' AND ', $where);
        $query = "SELECT t.*,
                    (SELECT COUNT(*) FROM $tabla_inscripciones i WHERE i.taller_id = t.id AND i.estado IN ('confirmada', 'completada')) as inscritos
                FROM $tabla_talleres t
                WHERE $where_sql
                ORDER BY t.fecha ASC, t.titulo ASC
                LIMIT %d";
        $params[] = $limite;

        $talleres = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'talleres' => array_map([$this, 'formatear_taller'], $talleres),
        ], 200);
    }

    public function get_mis_talleres($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';

        $talleres = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, i.estado as estado_inscripcion, i.fecha_inscripcion
            FROM $tabla_talleres t
            INNER JOIN $tabla_inscripciones i ON t.id = i.taller_id
            WHERE i.usuario_id = %d
            ORDER BY i.estado = 'confirmada' DESC, t.fecha DESC",
            $usuario_id
        ), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'talleres' => array_map([$this, 'formatear_taller'], $talleres),
        ], 200);
    }

    public function get_taller($request) {
        $taller_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
        $tabla_materiales = $wpdb->prefix . 'flavor_talleres_materiales';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_talleres_valoraciones';

        $taller = $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, u.display_name as instructor_nombre
            FROM $tabla_talleres t
            LEFT JOIN {$wpdb->users} u ON t.instructor_id = u.ID
            WHERE t.id = %d",
            $taller_id
        ), ARRAY_A);

        if (!$taller) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Taller no encontrado',
            ], 404);
        }

        // Sesiones
        $sesiones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_sesiones WHERE taller_id = %d ORDER BY fecha ASC, hora_inicio ASC",
            $taller_id
        ), ARRAY_A);

        // Inscripción del usuario
        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_inscripciones WHERE taller_id = %d AND usuario_id = %d",
            $taller_id,
            $usuario_id
        ), ARRAY_A);

        // Materiales (si está inscrito)
        $materiales = [];
        if ($inscripcion && $inscripcion['estado'] === 'confirmada') {
            $materiales = $wpdb->get_results($wpdb->prepare(
                "SELECT nombre, descripcion, url_archivo, tipo FROM $tabla_materiales WHERE taller_id = %d ORDER BY orden",
                $taller_id
            ), ARRAY_A);
        }

        // Valoración promedio
        $valoracion_promedio = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(puntuacion) FROM $tabla_valoraciones WHERE taller_id = %d",
            $taller_id
        ));

        // Inscritos
        $inscritos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_inscripciones WHERE taller_id = %d AND estado IN ('confirmada', 'completada')",
            $taller_id
        ));

        $taller_formateado = $this->formatear_taller($taller);
        $taller_formateado['sesiones'] = array_map(function($s) {
            return [
                'id' => (int) $s['id'],
                'fecha' => $s['fecha'],
                'hora_inicio' => $s['hora_inicio'],
                'hora_fin' => $s['hora_fin'],
                'ubicacion' => $s['ubicacion'] ?? '',
                'contenido' => $s['contenido'] ?? '',
            ];
        }, $sesiones);
        $taller_formateado['inscrito'] = !empty($inscripcion);
        $taller_formateado['estado_inscripcion'] = $inscripcion ? $inscripcion['estado'] : null;
        $taller_formateado['materiales'] = $materiales;
        $taller_formateado['valoracion_promedio'] = $valoracion_promedio ? round((float) $valoracion_promedio, 1) : null;
        $taller_formateado['inscritos'] = (int) $inscritos;

        return new WP_REST_Response([
            'success' => true,
            'taller' => $taller_formateado,
        ], 200);
    }

    public function inscribirse($request) {
        $taller_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';

        // Verificar que el taller existe y acepta inscripciones
        $taller = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_talleres WHERE id = %d",
            $taller_id
        ));

        if (!$taller) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Taller no encontrado',
            ], 404);
        }

        if (!in_array($taller->estado, ['publicado', 'inscripciones_abiertas'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'El taller no acepta inscripciones en este momento',
            ], 400);
        }

        // Verificar si ya está inscrito
        $inscripcion_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_inscripciones WHERE taller_id = %d AND usuario_id = %d AND estado IN ('pendiente', 'confirmada')",
            $taller_id,
            $usuario_id
        ));

        if ($inscripcion_existente) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Ya estás inscrito en este taller',
            ], 400);
        }

        // Verificar plazas disponibles
        if ($taller->plazas_maximas > 0) {
            $inscritos = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_inscripciones WHERE taller_id = %d AND estado IN ('confirmada', 'completada')",
                $taller_id
            ));

            if ($inscritos >= $taller->plazas_maximas) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'No hay plazas disponibles',
                ], 400);
            }
        }

        // Crear inscripción
        $estado_inicial = ($taller->precio > 0) ? 'pendiente' : 'confirmada';

        $wpdb->insert($tabla_inscripciones, [
            'taller_id' => $taller_id,
            'usuario_id' => $usuario_id,
            'estado' => $estado_inicial,
            'fecha_inscripcion' => current_time('mysql'),
        ]);

        $mensaje = ($estado_inicial === 'pendiente')
            ? 'Inscripción pendiente de pago'
            : 'Inscripción realizada correctamente';

        return new WP_REST_Response([
            'success' => true,
            'message' => $mensaje,
            'estado' => $estado_inicial,
        ], 201);
    }

    public function cancelar_inscripcion($request) {
        $taller_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';

        $resultado = $wpdb->update(
            $tabla_inscripciones,
            ['estado' => 'cancelada'],
            [
                'taller_id' => $taller_id,
                'usuario_id' => $usuario_id,
            ]
        );

        if ($resultado === false || $resultado === 0) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'No se encontró la inscripción',
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Inscripción cancelada',
        ], 200);
    }

    public function valorar_taller($request) {
        $taller_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_valoraciones = $wpdb->prefix . 'flavor_talleres_valoraciones';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';

        // Verificar que completó el taller
        $inscripcion = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_inscripciones WHERE taller_id = %d AND usuario_id = %d AND estado = 'completada'",
            $taller_id,
            $usuario_id
        ));

        if (!$inscripcion) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Solo puedes valorar talleres que hayas completado',
            ], 400);
        }

        // Insertar o actualizar valoración
        $valoracion_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_valoraciones WHERE taller_id = %d AND usuario_id = %d",
            $taller_id,
            $usuario_id
        ));

        $datos = [
            'taller_id' => $taller_id,
            'usuario_id' => $usuario_id,
            'puntuacion' => $request->get_param('puntuacion'),
            'comentario' => $request->get_param('comentario') ?: '',
            'fecha_valoracion' => current_time('mysql'),
        ];

        if ($valoracion_existente) {
            $wpdb->update($tabla_valoraciones, $datos, ['id' => $valoracion_existente]);
        } else {
            $wpdb->insert($tabla_valoraciones, $datos);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Valoración registrada',
        ], 201);
    }

    public function get_categorias($request) {
        global $wpdb;

        $tabla_talleres = $wpdb->prefix . 'flavor_talleres';

        $categorias = $wpdb->get_col(
            "SELECT DISTINCT categoria FROM $tabla_talleres
            WHERE categoria IS NOT NULL AND categoria != ''
            ORDER BY categoria"
        );

        return new WP_REST_Response([
            'success' => true,
            'categorias' => $categorias,
        ], 200);
    }

    private function formatear_taller($taller) {
        if (!$taller) return null;

        return [
            'id' => (int) $taller['id'],
            'titulo' => $taller['titulo'],
            'descripcion' => $taller['descripcion'] ?? '',
            'descripcion_corta' => $taller['descripcion_corta'] ?? '',
            'imagen' => $taller['imagen_url'] ?? '',
            'categoria' => $taller['categoria'] ?? '',
            'modalidad' => $taller['modalidad'] ?? 'presencial',
            'duracion_horas' => (int) ($taller['duracion_horas'] ?? 0),
            'precio' => (float) ($taller['precio'] ?? 0),
            'plazas_maximas' => (int) ($taller['plazas_maximas'] ?? 0),
            'inscritos' => (int) ($taller['inscritos'] ?? 0),
            'instructor_id' => (int) ($taller['instructor_id'] ?? 0),
            'instructor_nombre' => $taller['instructor_nombre'] ?? '',
            'fecha' => $taller['fecha'] ?? null,
            'hora_inicio' => $taller['hora_inicio'] ?? '',
            'hora_fin' => $taller['hora_fin'] ?? '',
            'ubicacion' => $taller['ubicacion'] ?? '',
            'estado' => $taller['estado'],
            'destacado' => !empty($taller['destacado']),
            'requisitos' => $taller['requisitos'] ?? '',
            'materiales_incluidos' => $taller['materiales_incluidos'] ?? '',
        ];
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
