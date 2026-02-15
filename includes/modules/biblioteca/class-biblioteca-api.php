<?php
/**
 * API REST para Biblioteca (Móvil)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Biblioteca_API {

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
        // GET /biblioteca/dashboard
        register_rest_route(self::NAMESPACE, '/biblioteca/dashboard', [
            'methods' => 'GET',
            'callback' => [$this, 'get_dashboard'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /biblioteca/libros
        register_rest_route(self::NAMESPACE, '/biblioteca/libros', [
            'methods' => 'GET',
            'callback' => [$this, 'get_libros'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /biblioteca/libros/{id}
        register_rest_route(self::NAMESPACE, '/biblioteca/libros/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_libro'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /biblioteca/mis-prestamos
        register_rest_route(self::NAMESPACE, '/biblioteca/mis-prestamos', [
            'methods' => 'GET',
            'callback' => [$this, 'get_mis_prestamos'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /biblioteca/libros/{id}/reservar
        register_rest_route(self::NAMESPACE, '/biblioteca/libros/(?P<id>\d+)/reservar', [
            'methods' => 'POST',
            'callback' => [$this, 'reservar_libro'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // DELETE /biblioteca/reservas/{id}
        register_rest_route(self::NAMESPACE, '/biblioteca/reservas/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'cancelar_reserva'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /biblioteca/libros/{id}/resena
        register_rest_route(self::NAMESPACE, '/biblioteca/libros/(?P<id>\d+)/resena', [
            'methods' => 'POST',
            'callback' => [$this, 'agregar_resena'],
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

        // GET /biblioteca/categorias
        register_rest_route(self::NAMESPACE, '/biblioteca/categorias', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categorias'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
    }

    public function get_dashboard($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';

        // Mis préstamos activos
        $mis_prestamos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_prestamos WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        // Mis reservas pendientes
        $mis_reservas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_reservas WHERE usuario_id = %d AND estado = 'pendiente'",
            $usuario_id
        ));

        // Total libros
        $total_libros = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_libros WHERE estado = 'disponible' OR estado = 'prestado'");

        // Libros disponibles
        $libros_disponibles = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_libros WHERE estado = 'disponible'");

        // Novedades (últimos 10 libros agregados)
        $novedades = $wpdb->get_results(
            "SELECT * FROM $tabla_libros
            WHERE estado IN ('disponible', 'prestado')
            ORDER BY fecha_agregado DESC
            LIMIT 10",
            ARRAY_A
        );

        // Préstamos próximos a vencer
        $proximos_vencer = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, l.titulo, l.autor, l.portada_url
            FROM $tabla_prestamos p
            INNER JOIN $tabla_libros l ON p.libro_id = l.id
            WHERE p.usuario_id = %d AND p.estado = 'activo'
            AND p.fecha_devolucion_prevista <= DATE_ADD(NOW(), INTERVAL 7 DAY)
            ORDER BY p.fecha_devolucion_prevista ASC",
            $usuario_id
        ), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'mi_estadistica' => [
                'prestamos_activos' => (int) $mis_prestamos,
                'reservas_pendientes' => (int) $mis_reservas,
            ],
            'estadisticas_biblioteca' => [
                'total_libros' => (int) $total_libros,
                'disponibles' => (int) $libros_disponibles,
            ],
            'novedades' => array_map([$this, 'formatear_libro'], $novedades),
            'proximos_a_vencer' => array_map(function($p) {
                return [
                    'id' => (int) $p['id'],
                    'libro_id' => (int) $p['libro_id'],
                    'titulo' => $p['titulo'],
                    'autor' => $p['autor'],
                    'portada' => $p['portada_url'] ?? '',
                    'fecha_devolucion' => $p['fecha_devolucion_prevista'],
                ];
            }, $proximos_vencer),
        ], 200);
    }

    public function get_libros($request) {
        global $wpdb;

        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        $categoria = $request->get_param('categoria');
        $busqueda = $request->get_param('busqueda');
        $disponible = $request->get_param('disponible');
        $limite = $request->get_param('limite') ?: 50;

        $where = ["estado IN ('disponible', 'prestado')"];
        $params = [];

        if ($categoria) {
            $where[] = "categoria = %s";
            $params[] = $categoria;
        }

        if ($busqueda) {
            $where[] = "(titulo LIKE %s OR autor LIKE %s OR isbn LIKE %s)";
            $busqueda_like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $busqueda_like;
            $params[] = $busqueda_like;
            $params[] = $busqueda_like;
        }

        if ($disponible === 'true' || $disponible === '1') {
            $where[] = "estado = 'disponible'";
        }

        $where_sql = implode(' AND ', $where);
        $query = "SELECT * FROM $tabla_libros WHERE $where_sql ORDER BY titulo ASC LIMIT %d";
        $params[] = $limite;

        $libros = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'libros' => array_map([$this, 'formatear_libro'], $libros),
        ], 200);
    }

    public function get_libro($request) {
        $libro_id = $request->get_param('id');
        global $wpdb;

        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_resenas = $wpdb->prefix . 'flavor_biblioteca_resenas';

        $libro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_libros WHERE id = %d",
            $libro_id
        ), ARRAY_A);

        if (!$libro) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Libro no encontrado',
            ], 404);
        }

        // Reseñas
        $resenas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, u.display_name as usuario_nombre
            FROM $tabla_resenas r
            INNER JOIN {$wpdb->users} u ON r.usuario_id = u.ID
            WHERE r.libro_id = %d
            ORDER BY r.fecha_resena DESC
            LIMIT 10",
            $libro_id
        ), ARRAY_A);

        // Promedio de puntuación
        $promedio = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(puntuacion) FROM $tabla_resenas WHERE libro_id = %d",
            $libro_id
        ));

        $libro_formateado = $this->formatear_libro($libro);
        $libro_formateado['puntuacion_promedio'] = $promedio ? round((float) $promedio, 1) : null;
        $libro_formateado['resenas'] = array_map(function($r) {
            return [
                'id' => (int) $r['id'],
                'usuario_nombre' => $r['usuario_nombre'],
                'puntuacion' => (int) $r['puntuacion'],
                'comentario' => $r['comentario'] ?? '',
                'fecha' => $r['fecha_resena'],
            ];
        }, $resenas);

        return new WP_REST_Response([
            'success' => true,
            'libro' => $libro_formateado,
        ], 200);
    }

    public function get_mis_prestamos($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';

        // Préstamos activos
        $prestamos = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, l.titulo, l.autor, l.portada_url, l.isbn
            FROM $tabla_prestamos p
            INNER JOIN $tabla_libros l ON p.libro_id = l.id
            WHERE p.usuario_id = %d
            ORDER BY p.estado = 'activo' DESC, p.fecha_prestamo DESC
            LIMIT 50",
            $usuario_id
        ), ARRAY_A);

        // Reservas
        $reservas = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, l.titulo, l.autor, l.portada_url
            FROM $tabla_reservas r
            INNER JOIN $tabla_libros l ON r.libro_id = l.id
            WHERE r.usuario_id = %d AND r.estado = 'pendiente'
            ORDER BY r.fecha_reserva DESC",
            $usuario_id
        ), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'prestamos' => array_map(function($p) {
                return [
                    'id' => (int) $p['id'],
                    'libro_id' => (int) $p['libro_id'],
                    'titulo' => $p['titulo'],
                    'autor' => $p['autor'],
                    'portada' => $p['portada_url'] ?? '',
                    'estado' => $p['estado'],
                    'fecha_prestamo' => $p['fecha_prestamo'],
                    'fecha_devolucion_prevista' => $p['fecha_devolucion_prevista'],
                    'fecha_devolucion_real' => $p['fecha_devolucion_real'] ?? null,
                ];
            }, $prestamos),
            'reservas' => array_map(function($r) {
                return [
                    'id' => (int) $r['id'],
                    'libro_id' => (int) $r['libro_id'],
                    'titulo' => $r['titulo'],
                    'autor' => $r['autor'],
                    'portada' => $r['portada_url'] ?? '',
                    'fecha_reserva' => $r['fecha_reserva'],
                    'posicion_cola' => (int) ($r['posicion_cola'] ?? 1),
                ];
            }, $reservas),
        ], 200);
    }

    public function reservar_libro($request) {
        $libro_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
        $tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';

        // Verificar que el libro existe
        $libro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_libros WHERE id = %d",
            $libro_id
        ));

        if (!$libro) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Libro no encontrado',
            ], 404);
        }

        // Verificar si ya tiene este libro prestado
        $prestamo_activo = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_prestamos WHERE libro_id = %d AND usuario_id = %d AND estado = 'activo'",
            $libro_id,
            $usuario_id
        ));

        if ($prestamo_activo) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Ya tienes este libro en préstamo',
            ], 400);
        }

        // Verificar si ya tiene una reserva pendiente
        $reserva_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_reservas WHERE libro_id = %d AND usuario_id = %d AND estado = 'pendiente'",
            $libro_id,
            $usuario_id
        ));

        if ($reserva_existente) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Ya tienes una reserva pendiente para este libro',
            ], 400);
        }

        // Calcular posición en cola
        $posicion = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) + 1 FROM $tabla_reservas WHERE libro_id = %d AND estado = 'pendiente'",
            $libro_id
        ));

        // Crear reserva
        $wpdb->insert($tabla_reservas, [
            'libro_id' => $libro_id,
            'usuario_id' => $usuario_id,
            'estado' => 'pendiente',
            'posicion_cola' => $posicion,
            'fecha_reserva' => current_time('mysql'),
        ]);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Reserva realizada correctamente',
            'posicion_cola' => (int) $posicion,
        ], 201);
    }

    public function cancelar_reserva($request) {
        $reserva_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';

        $resultado = $wpdb->update(
            $tabla_reservas,
            ['estado' => 'cancelada'],
            [
                'id' => $reserva_id,
                'usuario_id' => $usuario_id,
                'estado' => 'pendiente',
            ]
        );

        if ($resultado === false || $resultado === 0) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'No se encontró la reserva o ya fue cancelada',
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Reserva cancelada',
        ], 200);
    }

    public function agregar_resena($request) {
        $libro_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_resenas = $wpdb->prefix . 'flavor_biblioteca_resenas';

        // Verificar si ya tiene una reseña
        $resena_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_resenas WHERE libro_id = %d AND usuario_id = %d",
            $libro_id,
            $usuario_id
        ));

        $datos = [
            'libro_id' => $libro_id,
            'usuario_id' => $usuario_id,
            'puntuacion' => $request->get_param('puntuacion'),
            'comentario' => $request->get_param('comentario') ?: '',
            'fecha_resena' => current_time('mysql'),
        ];

        if ($resena_existente) {
            $wpdb->update($tabla_resenas, $datos, ['id' => $resena_existente]);
            $mensaje = 'Reseña actualizada';
        } else {
            $wpdb->insert($tabla_resenas, $datos);
            $mensaje = 'Reseña agregada';
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => $mensaje,
        ], 201);
    }

    public function get_categorias($request) {
        global $wpdb;

        $tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

        $categorias = $wpdb->get_col(
            "SELECT DISTINCT categoria FROM $tabla_libros
            WHERE categoria IS NOT NULL AND categoria != ''
            ORDER BY categoria"
        );

        return new WP_REST_Response([
            'success' => true,
            'categorias' => $categorias,
        ], 200);
    }

    private function formatear_libro($libro) {
        if (!$libro) return null;

        return [
            'id' => (int) $libro['id'],
            'titulo' => $libro['titulo'],
            'autor' => $libro['autor'] ?? '',
            'isbn' => $libro['isbn'] ?? '',
            'editorial' => $libro['editorial'] ?? '',
            'anio_publicacion' => $libro['anio_publicacion'] ?? '',
            'categoria' => $libro['categoria'] ?? '',
            'descripcion' => $libro['descripcion'] ?? '',
            'portada' => $libro['portada_url'] ?? '',
            'estado' => $libro['estado'],
            'ubicacion' => $libro['ubicacion'] ?? '',
            'num_paginas' => (int) ($libro['num_paginas'] ?? 0),
            'idioma' => $libro['idioma'] ?? 'es',
        ];
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
