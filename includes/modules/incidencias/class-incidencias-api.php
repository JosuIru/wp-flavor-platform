<?php
/**
 * API REST para Incidencias (Móvil)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Incidencias_API {

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
        // GET /incidencias/dashboard
        register_rest_route(self::NAMESPACE, '/incidencias/dashboard', [
            'methods' => 'GET',
            'callback' => [$this, 'get_dashboard'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /incidencias
        register_rest_route(self::NAMESPACE, '/incidencias', [
            'methods' => 'GET',
            'callback' => [$this, 'get_incidencias'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /incidencias/mis-incidencias
        register_rest_route(self::NAMESPACE, '/incidencias/mis-incidencias', [
            'methods' => 'GET',
            'callback' => [$this, 'get_mis_incidencias'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /incidencias/{id}
        register_rest_route(self::NAMESPACE, '/incidencias/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_incidencia'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /incidencias
        register_rest_route(self::NAMESPACE, '/incidencias', [
            'methods' => 'POST',
            'callback' => [$this, 'crear_incidencia'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'titulo' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'descripcion' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'categoria_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
                'direccion' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'latitud' => [
                    'type' => 'number',
                ],
                'longitud' => [
                    'type' => 'number',
                ],
                'prioridad' => [
                    'type' => 'string',
                    'default' => 'media',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // POST /incidencias/{id}/votar
        register_rest_route(self::NAMESPACE, '/incidencias/(?P<id>\d+)/votar', [
            'methods' => 'POST',
            'callback' => [$this, 'votar_incidencia'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /incidencias/{id}/comentario
        register_rest_route(self::NAMESPACE, '/incidencias/(?P<id>\d+)/comentario', [
            'methods' => 'POST',
            'callback' => [$this, 'agregar_comentario'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'comentario' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);

        // GET /incidencias/categorias
        register_rest_route(self::NAMESPACE, '/incidencias/categorias', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categorias'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /incidencias/mapa
        register_rest_route(self::NAMESPACE, '/incidencias/mapa', [
            'methods' => 'GET',
            'callback' => [$this, 'get_mapa'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
    }

    public function get_dashboard($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        $tabla_votos = $wpdb->prefix . 'flavor_incidencias_votos';

        // Estadísticas del usuario
        $mis_incidencias = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_incidencias WHERE usuario_id = %d",
            $usuario_id
        ));

        $mis_resueltas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_incidencias WHERE usuario_id = %d AND estado = 'resuelta'",
            $usuario_id
        ));

        $mis_votos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_votos WHERE usuario_id = %d",
            $usuario_id
        ));

        // Estadísticas globales
        $total_incidencias = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias");
        $incidencias_pendientes = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_incidencias WHERE estado IN ('pendiente', 'en_proceso')"
        );
        $incidencias_resueltas = $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_incidencias WHERE estado = 'resuelta'"
        );

        // Incidencias recientes
        $recientes = $wpdb->get_results(
            "SELECT i.*, u.display_name as usuario_nombre,
                (SELECT COUNT(*) FROM $tabla_votos v WHERE v.incidencia_id = i.id) as votos
            FROM $tabla_incidencias i
            INNER JOIN {$wpdb->users} u ON i.usuario_id = u.ID
            WHERE i.estado != 'cerrada'
            ORDER BY i.fecha_creacion DESC
            LIMIT 10",
            ARRAY_A
        );

        return new WP_REST_Response([
            'success' => true,
            'mi_estadistica' => [
                'reportadas' => (int) $mis_incidencias,
                'resueltas' => (int) $mis_resueltas,
                'votos_dados' => (int) $mis_votos,
            ],
            'estadisticas_comunidad' => [
                'total' => (int) $total_incidencias,
                'pendientes' => (int) $incidencias_pendientes,
                'resueltas' => (int) $incidencias_resueltas,
                'tasa_resolucion' => $total_incidencias > 0
                    ? round(($incidencias_resueltas / $total_incidencias) * 100, 1)
                    : 0,
            ],
            'incidencias_recientes' => array_map([$this, 'formatear_incidencia'], $recientes),
        ], 200);
    }

    public function get_incidencias($request) {
        global $wpdb;

        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        $tabla_votos = $wpdb->prefix . 'flavor_incidencias_votos';
        $tabla_categorias = $wpdb->prefix . 'flavor_incidencias_categorias';

        $estado = $request->get_param('estado');
        $categoria_id = $request->get_param('categoria_id');
        $limite = $request->get_param('limite') ?: 50;

        $where = "WHERE 1=1";
        $params = [];

        if ($estado) {
            $where .= " AND i.estado = %s";
            $params[] = $estado;
        }

        if ($categoria_id) {
            $where .= " AND i.categoria_id = %d";
            $params[] = $categoria_id;
        }

        $query = "SELECT i.*, u.display_name as usuario_nombre, c.nombre as categoria_nombre,
                    (SELECT COUNT(*) FROM $tabla_votos v WHERE v.incidencia_id = i.id) as votos
                FROM $tabla_incidencias i
                INNER JOIN {$wpdb->users} u ON i.usuario_id = u.ID
                LEFT JOIN $tabla_categorias c ON i.categoria_id = c.id
                $where
                ORDER BY i.fecha_creacion DESC
                LIMIT %d";
        $params[] = $limite;

        if (count($params) > 1) {
            $incidencias = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
        } else {
            $incidencias = $wpdb->get_results($wpdb->prepare($query, $limite), ARRAY_A);
        }

        return new WP_REST_Response([
            'success' => true,
            'incidencias' => array_map([$this, 'formatear_incidencia'], $incidencias),
        ], 200);
    }

    public function get_mis_incidencias($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        $tabla_votos = $wpdb->prefix . 'flavor_incidencias_votos';
        $tabla_categorias = $wpdb->prefix . 'flavor_incidencias_categorias';

        $incidencias = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, c.nombre as categoria_nombre,
                (SELECT COUNT(*) FROM $tabla_votos v WHERE v.incidencia_id = i.id) as votos
            FROM $tabla_incidencias i
            LEFT JOIN $tabla_categorias c ON i.categoria_id = c.id
            WHERE i.usuario_id = %d
            ORDER BY i.fecha_creacion DESC",
            $usuario_id
        ), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'incidencias' => array_map([$this, 'formatear_incidencia'], $incidencias),
        ], 200);
    }

    public function get_incidencia($request) {
        $incidencia_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
        $tabla_seguimiento = $wpdb->prefix . 'flavor_incidencias_seguimiento';
        $tabla_fotos = $wpdb->prefix . 'flavor_incidencias_fotos';
        $tabla_votos = $wpdb->prefix . 'flavor_incidencias_votos';
        $tabla_categorias = $wpdb->prefix . 'flavor_incidencias_categorias';

        $incidencia = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, u.display_name as usuario_nombre, c.nombre as categoria_nombre
            FROM $tabla_incidencias i
            INNER JOIN {$wpdb->users} u ON i.usuario_id = u.ID
            LEFT JOIN $tabla_categorias c ON i.categoria_id = c.id
            WHERE i.id = %d",
            $incidencia_id
        ), ARRAY_A);

        if (!$incidencia) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Incidencia no encontrada',
            ], 404);
        }

        // Seguimiento/comentarios
        $seguimiento = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.display_name as usuario_nombre
            FROM $tabla_seguimiento s
            INNER JOIN {$wpdb->users} u ON s.usuario_id = u.ID
            WHERE s.incidencia_id = %d
            ORDER BY s.fecha_creacion ASC",
            $incidencia_id
        ), ARRAY_A);

        // Fotos
        $fotos = $wpdb->get_col($wpdb->prepare(
            "SELECT url_foto FROM $tabla_fotos WHERE incidencia_id = %d",
            $incidencia_id
        ));

        // Votos
        $votos_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_votos WHERE incidencia_id = %d",
            $incidencia_id
        ));

        $mi_voto = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_votos WHERE incidencia_id = %d AND usuario_id = %d",
            $incidencia_id,
            $usuario_id
        ));

        $incidencia_formateada = $this->formatear_incidencia($incidencia);
        $incidencia_formateada['seguimiento'] = $seguimiento;
        $incidencia_formateada['fotos'] = $fotos;
        $incidencia_formateada['votos'] = (int) $votos_count;
        $incidencia_formateada['mi_voto'] = !empty($mi_voto);

        return new WP_REST_Response([
            'success' => true,
            'incidencia' => $incidencia_formateada,
        ], 200);
    }

    public function crear_incidencia($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        // Generar número de incidencia
        $anio = date('Y');
        $ultimo = $wpdb->get_var($wpdb->prepare(
            "SELECT numero_incidencia FROM $tabla_incidencias
            WHERE numero_incidencia LIKE %s ORDER BY id DESC LIMIT 1",
            "INC-$anio-%"
        ));

        if ($ultimo) {
            $numero = (int) substr($ultimo, -5) + 1;
        } else {
            $numero = 1;
        }
        $numero_incidencia = sprintf("INC-%s-%05d", $anio, $numero);

        $datos = [
            'numero_incidencia' => $numero_incidencia,
            'usuario_id' => $usuario_id,
            'titulo' => $request->get_param('titulo'),
            'descripcion' => $request->get_param('descripcion'),
            'categoria_id' => $request->get_param('categoria_id'),
            'direccion' => $request->get_param('direccion') ?: '',
            'latitud' => $request->get_param('latitud') ?: null,
            'longitud' => $request->get_param('longitud') ?: null,
            'prioridad' => $request->get_param('prioridad') ?: 'media',
            'estado' => 'pendiente',
            'fecha_creacion' => current_time('mysql'),
        ];

        $resultado = $wpdb->insert($tabla_incidencias, $datos);

        if ($resultado === false) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Error al crear la incidencia',
            ], 500);
        }

        $incidencia_id = $wpdb->insert_id;

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Incidencia creada correctamente',
            'incidencia_id' => $incidencia_id,
            'numero_incidencia' => $numero_incidencia,
        ], 201);
    }

    public function votar_incidencia($request) {
        $incidencia_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_votos = $wpdb->prefix . 'flavor_incidencias_votos';

        // Verificar si ya votó
        $voto_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_votos WHERE incidencia_id = %d AND usuario_id = %d",
            $incidencia_id,
            $usuario_id
        ));

        if ($voto_existente) {
            // Quitar voto
            $wpdb->delete($tabla_votos, [
                'incidencia_id' => $incidencia_id,
                'usuario_id' => $usuario_id,
            ]);

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Voto eliminado',
                'votado' => false,
            ], 200);
        }

        // Agregar voto
        $wpdb->insert($tabla_votos, [
            'incidencia_id' => $incidencia_id,
            'usuario_id' => $usuario_id,
            'fecha_voto' => current_time('mysql'),
        ]);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Voto registrado',
            'votado' => true,
        ], 200);
    }

    public function agregar_comentario($request) {
        $incidencia_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_seguimiento = $wpdb->prefix . 'flavor_incidencias_seguimiento';

        $wpdb->insert($tabla_seguimiento, [
            'incidencia_id' => $incidencia_id,
            'usuario_id' => $usuario_id,
            'tipo' => 'comentario',
            'descripcion' => $request->get_param('comentario'),
            'fecha_creacion' => current_time('mysql'),
        ]);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Comentario agregado',
        ], 201);
    }

    public function get_categorias($request) {
        global $wpdb;

        $tabla_categorias = $wpdb->prefix . 'flavor_incidencias_categorias';

        $categorias = $wpdb->get_results(
            "SELECT id, nombre, slug, icono, color, descripcion
            FROM $tabla_categorias
            WHERE activa = 1
            ORDER BY orden ASC, nombre ASC",
            ARRAY_A
        );

        return new WP_REST_Response([
            'success' => true,
            'categorias' => $categorias,
        ], 200);
    }

    public function get_mapa($request) {
        global $wpdb;

        $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

        $incidencias = $wpdb->get_results(
            "SELECT id, titulo, estado, prioridad, latitud, longitud, categoria_id
            FROM $tabla_incidencias
            WHERE latitud IS NOT NULL AND longitud IS NOT NULL
            AND estado IN ('pendiente', 'en_proceso')
            ORDER BY fecha_creacion DESC
            LIMIT 200",
            ARRAY_A
        );

        $marcadores = array_map(function($inc) {
            return [
                'id' => (int) $inc['id'],
                'titulo' => $inc['titulo'],
                'estado' => $inc['estado'],
                'prioridad' => $inc['prioridad'],
                'lat' => (float) $inc['latitud'],
                'lng' => (float) $inc['longitud'],
                'categoria_id' => (int) $inc['categoria_id'],
            ];
        }, $incidencias);

        return new WP_REST_Response([
            'success' => true,
            'marcadores' => $marcadores,
        ], 200);
    }

    private function formatear_incidencia($incidencia) {
        if (!$incidencia) return null;

        return [
            'id' => (int) $incidencia['id'],
            'numero' => $incidencia['numero_incidencia'] ?? '',
            'titulo' => $incidencia['titulo'],
            'descripcion' => $incidencia['descripcion'] ?? '',
            'estado' => $incidencia['estado'],
            'prioridad' => $incidencia['prioridad'] ?? 'media',
            'categoria_id' => (int) ($incidencia['categoria_id'] ?? 0),
            'categoria_nombre' => $incidencia['categoria_nombre'] ?? '',
            'usuario_id' => (int) $incidencia['usuario_id'],
            'usuario_nombre' => $incidencia['usuario_nombre'] ?? '',
            'direccion' => $incidencia['direccion'] ?? '',
            'coordenadas' => [
                'lat' => isset($incidencia['latitud']) ? (float) $incidencia['latitud'] : null,
                'lng' => isset($incidencia['longitud']) ? (float) $incidencia['longitud'] : null,
            ],
            'votos' => (int) ($incidencia['votos'] ?? 0),
            'fecha_creacion' => $incidencia['fecha_creacion'] ?? '',
            'fecha_resolucion' => $incidencia['fecha_resolucion'] ?? null,
        ];
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
