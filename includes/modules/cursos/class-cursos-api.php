<?php
/**
 * API REST para Cursos (Móvil)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Cursos_API {

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
        // GET /cursos/dashboard
        register_rest_route(self::NAMESPACE, '/cursos/dashboard', [
            'methods' => 'GET',
            'callback' => [$this, 'get_dashboard'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /cursos
        register_rest_route(self::NAMESPACE, '/cursos', [
            'methods' => 'GET',
            'callback' => [$this, 'get_cursos'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /cursos/mis-cursos
        register_rest_route(self::NAMESPACE, '/cursos/mis-cursos', [
            'methods' => 'GET',
            'callback' => [$this, 'get_mis_cursos'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /cursos/{id}
        register_rest_route(self::NAMESPACE, '/cursos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_curso'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /cursos/{id}/inscribir
        register_rest_route(self::NAMESPACE, '/cursos/(?P<id>\d+)/inscribir', [
            'methods' => 'POST',
            'callback' => [$this, 'inscribirse'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // DELETE /cursos/{id}/cancelar
        register_rest_route(self::NAMESPACE, '/cursos/(?P<id>\d+)/cancelar', [
            'methods' => 'DELETE',
            'callback' => [$this, 'cancelar_inscripcion'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /cursos/{id}/lecciones
        register_rest_route(self::NAMESPACE, '/cursos/(?P<id>\d+)/lecciones', [
            'methods' => 'GET',
            'callback' => [$this, 'get_lecciones'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /cursos/lecciones/{id}/completar
        register_rest_route(self::NAMESPACE, '/cursos/lecciones/(?P<id>\d+)/completar', [
            'methods' => 'POST',
            'callback' => [$this, 'completar_leccion'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /cursos/categorias
        register_rest_route(self::NAMESPACE, '/cursos/categorias', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categorias'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
    }

    public function get_dashboard($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';

        // Mis inscripciones
        $mis_cursos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_inscripciones WHERE alumno_id = %d AND estado = 'activa'",
            $usuario_id
        ));

        $cursos_completados = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_inscripciones WHERE alumno_id = %d AND estado = 'completada'",
            $usuario_id
        ));

        // Cursos disponibles
        $cursos_disponibles = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_cursos WHERE estado IN ('publicado', 'inscripciones_abiertas')"
        );

        // Próximos cursos
        $proximos = $wpdb->get_results(
            "SELECT c.*,
                (SELECT COUNT(*) FROM $tabla_inscripciones i WHERE i.curso_id = c.id AND i.estado = 'activa') as inscritos
            FROM $tabla_cursos c
            WHERE c.estado IN ('publicado', 'inscripciones_abiertas')
            AND (c.fecha_inicio IS NULL OR c.fecha_inicio >= CURDATE())
            ORDER BY c.fecha_inicio ASC
            LIMIT 5",
            ARRAY_A
        );

        // Mis cursos en progreso
        $en_progreso = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, i.progreso_porcentaje, i.fecha_inscripcion
            FROM $tabla_cursos c
            INNER JOIN $tabla_inscripciones i ON c.id = i.curso_id
            WHERE i.alumno_id = %d AND i.estado = 'activa'
            ORDER BY i.fecha_inscripcion DESC
            LIMIT 5",
            $usuario_id
        ), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'mi_estadistica' => [
                'cursos_activos' => (int) $mis_cursos,
                'cursos_completados' => (int) $cursos_completados,
            ],
            'estadisticas_generales' => [
                'cursos_disponibles' => (int) $cursos_disponibles,
            ],
            'proximos_cursos' => array_map([$this, 'formatear_curso'], $proximos),
            'mis_cursos_en_progreso' => array_map([$this, 'formatear_curso'], $en_progreso),
        ], 200);
    }

    public function get_cursos($request) {
        global $wpdb;

        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';

        $categoria = $request->get_param('categoria');
        $modalidad = $request->get_param('modalidad');
        $busqueda = $request->get_param('busqueda');
        $limite = $request->get_param('limite') ?: 50;

        $where = "WHERE c.estado IN ('publicado', 'inscripciones_abiertas', 'en_curso')";
        $params = [];

        if ($categoria) {
            $where .= " AND c.categoria = %s";
            $params[] = $categoria;
        }

        if ($modalidad) {
            $where .= " AND c.modalidad = %s";
            $params[] = $modalidad;
        }

        if ($busqueda) {
            $where .= " AND (c.titulo LIKE %s OR c.descripcion LIKE %s)";
            $busqueda_like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $busqueda_like;
            $params[] = $busqueda_like;
        }

        $query = "SELECT c.*,
                    (SELECT COUNT(*) FROM $tabla_inscripciones i WHERE i.curso_id = c.id AND i.estado = 'activa') as inscritos
                FROM $tabla_cursos c
                $where
                ORDER BY c.fecha_inicio ASC, c.titulo ASC
                LIMIT %d";
        $params[] = $limite;

        $cursos = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'cursos' => array_map([$this, 'formatear_curso'], $cursos),
        ], 200);
    }

    public function get_mis_cursos($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';

        $estado = $request->get_param('estado') ?: 'activa';

        $cursos = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, i.progreso_porcentaje, i.fecha_inscripcion, i.estado as estado_inscripcion
            FROM $tabla_cursos c
            INNER JOIN $tabla_inscripciones i ON c.id = i.curso_id
            WHERE i.alumno_id = %d AND i.estado = %s
            ORDER BY i.fecha_inscripcion DESC",
            $usuario_id,
            $estado
        ), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'cursos' => array_map([$this, 'formatear_curso'], $cursos),
        ], 200);
    }

    public function get_curso($request) {
        $curso_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';

        $curso = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, u.display_name as instructor_nombre
            FROM $tabla_cursos c
            LEFT JOIN {$wpdb->users} u ON c.instructor_id = u.ID
            WHERE c.id = %d",
            $curso_id
        ), ARRAY_A);

        if (!$curso) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Curso no encontrado',
            ], 404);
        }

        // Verificar inscripción del usuario
        $inscripcion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_inscripciones WHERE curso_id = %d AND alumno_id = %d",
            $curso_id,
            $usuario_id
        ), ARRAY_A);

        // Contar inscritos
        $inscritos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_inscripciones WHERE curso_id = %d AND estado = 'activa'",
            $curso_id
        ));

        // Contar lecciones
        $total_lecciones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_lecciones WHERE curso_id = %d",
            $curso_id
        ));

        $curso_formateado = $this->formatear_curso($curso);
        $curso_formateado['inscritos'] = (int) $inscritos;
        $curso_formateado['total_lecciones'] = (int) $total_lecciones;
        $curso_formateado['inscrito'] = !empty($inscripcion);
        $curso_formateado['progreso'] = $inscripcion ? (int) $inscripcion['progreso_porcentaje'] : 0;
        $curso_formateado['estado_inscripcion'] = $inscripcion ? $inscripcion['estado'] : null;

        return new WP_REST_Response([
            'success' => true,
            'curso' => $curso_formateado,
        ], 200);
    }

    public function inscribirse($request) {
        $curso_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';

        // Verificar que el curso existe y acepta inscripciones
        $curso = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_cursos WHERE id = %d",
            $curso_id
        ));

        if (!$curso) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Curso no encontrado',
            ], 404);
        }

        if (!in_array($curso->estado, ['publicado', 'inscripciones_abiertas'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'El curso no acepta inscripciones en este momento',
            ], 400);
        }

        // Verificar si ya está inscrito
        $inscripcion_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_inscripciones WHERE curso_id = %d AND alumno_id = %d",
            $curso_id,
            $usuario_id
        ));

        if ($inscripcion_existente) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Ya estás inscrito en este curso',
            ], 400);
        }

        // Verificar plazas disponibles
        if ($curso->plazas_maximas > 0) {
            $inscritos = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_inscripciones WHERE curso_id = %d AND estado = 'activa'",
                $curso_id
            ));

            if ($inscritos >= $curso->plazas_maximas) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'No hay plazas disponibles',
                ], 400);
            }
        }

        // Crear inscripción
        $wpdb->insert($tabla_inscripciones, [
            'curso_id' => $curso_id,
            'alumno_id' => $usuario_id,
            'estado' => 'activa',
            'progreso_porcentaje' => 0,
            'fecha_inscripcion' => current_time('mysql'),
        ]);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Inscripción realizada correctamente',
        ], 201);
    }

    public function cancelar_inscripcion($request) {
        $curso_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';

        $resultado = $wpdb->update(
            $tabla_inscripciones,
            ['estado' => 'cancelada'],
            [
                'curso_id' => $curso_id,
                'alumno_id' => $usuario_id,
                'estado' => 'activa',
            ]
        );

        if ($resultado === false || $resultado === 0) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'No se encontró la inscripción o ya fue cancelada',
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Inscripción cancelada',
        ], 200);
    }

    public function get_lecciones($request) {
        $curso_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';
        $tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';

        $lecciones = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*,
                (SELECT completada FROM $tabla_progreso p
                 WHERE p.leccion_id = l.id AND p.alumno_id = %d) as completada
            FROM $tabla_lecciones l
            WHERE l.curso_id = %d
            ORDER BY l.orden ASC",
            $usuario_id,
            $curso_id
        ), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'lecciones' => array_map(function($leccion) {
                return [
                    'id' => (int) $leccion['id'],
                    'titulo' => $leccion['titulo'],
                    'descripcion' => $leccion['descripcion'] ?? '',
                    'duracion_minutos' => (int) ($leccion['duracion_minutos'] ?? 0),
                    'tipo' => $leccion['tipo'] ?? 'video',
                    'orden' => (int) $leccion['orden'],
                    'completada' => !empty($leccion['completada']),
                ];
            }, $lecciones),
        ], 200);
    }

    public function completar_leccion($request) {
        $leccion_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';
        $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';

        // Obtener curso de la lección
        $leccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_lecciones WHERE id = %d",
            $leccion_id
        ));

        if (!$leccion) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Lección no encontrada',
            ], 404);
        }

        // Marcar lección como completada
        $wpdb->replace($tabla_progreso, [
            'leccion_id' => $leccion_id,
            'alumno_id' => $usuario_id,
            'curso_id' => $leccion->curso_id,
            'completada' => 1,
            'fecha_completado' => current_time('mysql'),
        ]);

        // Actualizar progreso del curso
        $total_lecciones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_lecciones WHERE curso_id = %d",
            $leccion->curso_id
        ));

        $lecciones_completadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_progreso WHERE curso_id = %d AND alumno_id = %d AND completada = 1",
            $leccion->curso_id,
            $usuario_id
        ));

        $progreso = $total_lecciones > 0 ? round(($lecciones_completadas / $total_lecciones) * 100) : 0;

        $wpdb->update(
            $tabla_inscripciones,
            ['progreso_porcentaje' => $progreso],
            [
                'curso_id' => $leccion->curso_id,
                'alumno_id' => $usuario_id,
            ]
        );

        // Si completó el 100%, marcar inscripción como completada
        if ($progreso >= 100) {
            $wpdb->update(
                $tabla_inscripciones,
                [
                    'estado' => 'completada',
                    'fecha_completado' => current_time('mysql'),
                ],
                [
                    'curso_id' => $leccion->curso_id,
                    'alumno_id' => $usuario_id,
                ]
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Lección completada',
            'progreso' => $progreso,
        ], 200);
    }

    public function get_categorias($request) {
        global $wpdb;

        $tabla_cursos = $wpdb->prefix . 'flavor_cursos';

        $categorias = $wpdb->get_col(
            "SELECT DISTINCT categoria FROM $tabla_cursos WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria"
        );

        return new WP_REST_Response([
            'success' => true,
            'categorias' => $categorias,
        ], 200);
    }

    private function formatear_curso($curso) {
        if (!$curso) return null;

        return [
            'id' => (int) $curso['id'],
            'titulo' => $curso['titulo'],
            'descripcion' => $curso['descripcion'] ?? '',
            'descripcion_corta' => $curso['descripcion_corta'] ?? '',
            'imagen' => $curso['imagen_url'] ?? '',
            'categoria' => $curso['categoria'] ?? '',
            'modalidad' => $curso['modalidad'] ?? 'presencial',
            'duracion_horas' => (int) ($curso['duracion_horas'] ?? 0),
            'precio' => (float) ($curso['precio'] ?? 0),
            'plazas_maximas' => (int) ($curso['plazas_maximas'] ?? 0),
            'inscritos' => (int) ($curso['inscritos'] ?? 0),
            'instructor_id' => (int) ($curso['instructor_id'] ?? 0),
            'instructor_nombre' => $curso['instructor_nombre'] ?? '',
            'fecha_inicio' => $curso['fecha_inicio'] ?? null,
            'fecha_fin' => $curso['fecha_fin'] ?? null,
            'horario' => $curso['horario'] ?? '',
            'ubicacion' => $curso['ubicacion'] ?? '',
            'estado' => $curso['estado'],
            'progreso' => (int) ($curso['progreso_porcentaje'] ?? 0),
        ];
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
