<?php
/**
 * API REST para Trámites (Móvil)
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Tramites_API {

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
        // GET /tramites/dashboard
        flavor_register_rest_route(self::NAMESPACE, '/tramites/dashboard', [
            'methods' => 'GET',
            'callback' => [$this, 'get_dashboard'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /tramites/tipos
        flavor_register_rest_route(self::NAMESPACE, '/tramites/tipos', [
            'methods' => 'GET',
            'callback' => [$this, 'get_tipos_tramite'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /tramites/mis-expedientes
        flavor_register_rest_route(self::NAMESPACE, '/tramites/mis-expedientes', [
            'methods' => 'GET',
            'callback' => [$this, 'get_mis_expedientes'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /tramites/expedientes/{id}
        flavor_register_rest_route(self::NAMESPACE, '/tramites/expedientes/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_expediente'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /tramites/iniciar
        flavor_register_rest_route(self::NAMESPACE, '/tramites/iniciar', [
            'methods' => 'POST',
            'callback' => [$this, 'iniciar_tramite'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'tipo_tramite_id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
                'datos' => [
                    'type' => 'object',
                    'default' => [],
                ],
            ],
        ]);

        // POST /tramites/expedientes/{id}/documentos
        flavor_register_rest_route(self::NAMESPACE, '/tramites/expedientes/(?P<id>\d+)/documentos', [
            'methods' => 'POST',
            'callback' => [$this, 'subir_documento'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // DELETE /tramites/expedientes/{id}
        flavor_register_rest_route(self::NAMESPACE, '/tramites/expedientes/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'cancelar_expediente'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
    }

    public function get_dashboard($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_expedientes = $wpdb->prefix . 'flavor_expedientes';
        $tabla_tipos = $wpdb->prefix . 'flavor_tipos_tramite';

        // Mis expedientes por estado
        $en_tramite = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_expedientes WHERE usuario_id = %d AND estado IN ('iniciado', 'en_revision', 'pendiente_documentacion')",
            $usuario_id
        ));

        $resueltos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_expedientes WHERE usuario_id = %d AND estado IN ('aprobado', 'completado')",
            $usuario_id
        ));

        $rechazados = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_expedientes WHERE usuario_id = %d AND estado = 'rechazado'",
            $usuario_id
        ));

        // Últimos expedientes
        $ultimos = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, t.nombre as tipo_nombre, t.icono
            FROM $tabla_expedientes e
            INNER JOIN $tabla_tipos t ON e.tipo_tramite_id = t.id
            WHERE e.usuario_id = %d
            ORDER BY e.fecha_inicio DESC
            LIMIT 5",
            $usuario_id
        ), ARRAY_A);

        // Tipos de trámite populares
        $tipos_populares = $wpdb->get_results(
            "SELECT t.*, COUNT(e.id) as total_solicitudes
            FROM $tabla_tipos t
            LEFT JOIN $tabla_expedientes e ON t.id = e.tipo_tramite_id
            WHERE t.estado = 'activo'
            GROUP BY t.id
            ORDER BY total_solicitudes DESC
            LIMIT 6",
            ARRAY_A
        );

        return new WP_REST_Response([
            'success' => true,
            'mi_estadistica' => [
                'en_tramite' => (int) $en_tramite,
                'resueltos' => (int) $resueltos,
                'rechazados' => (int) $rechazados,
            ],
            'ultimos_expedientes' => array_map([$this, 'formatear_expediente'], $ultimos),
            'tipos_populares' => array_map([$this, 'formatear_tipo'], $tipos_populares),
        ], 200);
    }

    public function get_tipos_tramite($request) {
        global $wpdb;

        $tabla_tipos = $wpdb->prefix . 'flavor_tipos_tramite';

        $categoria = $request->get_param('categoria');

        $where = "estado = 'activo'";
        $params = [];

        if ($categoria) {
            $where .= " AND categoria = %s";
            $params[] = $categoria;
        }

        $query = "SELECT * FROM $tabla_tipos WHERE $where ORDER BY categoria, nombre";

        if (!empty($params)) {
            $tipos = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
        } else {
            $tipos = $wpdb->get_results($query, ARRAY_A);
        }

        return new WP_REST_Response([
            'success' => true,
            'tipos' => array_map([$this, 'formatear_tipo'], $tipos),
        ], 200);
    }

    public function get_mis_expedientes($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_expedientes = $wpdb->prefix . 'flavor_expedientes';
        $tabla_tipos = $wpdb->prefix . 'flavor_tipos_tramite';

        $estado = $request->get_param('estado');

        $where = "e.usuario_id = %d";
        $params = [$usuario_id];

        if ($estado) {
            $where .= " AND e.estado = %s";
            $params[] = $estado;
        }

        $expedientes = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, t.nombre as tipo_nombre, t.icono
            FROM $tabla_expedientes e
            INNER JOIN $tabla_tipos t ON e.tipo_tramite_id = t.id
            WHERE $where
            ORDER BY e.fecha_inicio DESC",
            $params
        ), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'expedientes' => array_map([$this, 'formatear_expediente'], $expedientes),
        ], 200);
    }

    public function get_expediente($request) {
        $expediente_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_expedientes = $wpdb->prefix . 'flavor_expedientes';
        $tabla_tipos = $wpdb->prefix . 'flavor_tipos_tramite';
        $tabla_documentos = $wpdb->prefix . 'flavor_documentos_expediente';
        $tabla_historial = $wpdb->prefix . 'flavor_historial_expediente';

        $expediente = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, t.nombre as tipo_nombre, t.descripcion as tipo_descripcion, t.icono
            FROM $tabla_expedientes e
            INNER JOIN $tabla_tipos t ON e.tipo_tramite_id = t.id
            WHERE e.id = %d AND e.usuario_id = %d",
            $expediente_id,
            $usuario_id
        ), ARRAY_A);

        if (!$expediente) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Expediente no encontrado',
            ], 404);
        }

        // Documentos
        $documentos = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nombre, tipo_documento, url_archivo, fecha_subida, estado
            FROM $tabla_documentos
            WHERE expediente_id = %d
            ORDER BY fecha_subida DESC",
            $expediente_id
        ), ARRAY_A);

        // Historial
        $historial = $wpdb->get_results($wpdb->prepare(
            "SELECT h.*, u.display_name as usuario_nombre
            FROM $tabla_historial h
            LEFT JOIN {$wpdb->users} u ON h.usuario_id = u.ID
            WHERE h.expediente_id = %d
            ORDER BY h.fecha DESC",
            $expediente_id
        ), ARRAY_A);

        $expediente_formateado = $this->formatear_expediente($expediente);
        $expediente_formateado['documentos'] = $documentos;
        $expediente_formateado['historial'] = array_map(function($h) {
            return [
                'id' => (int) $h['id'],
                'accion' => $h['accion'],
                'descripcion' => $h['descripcion'] ?? '',
                'estado_anterior' => $h['estado_anterior'] ?? '',
                'estado_nuevo' => $h['estado_nuevo'] ?? '',
                'fecha' => $h['fecha'],
                'usuario' => $h['usuario_nombre'] ?? 'Sistema',
            ];
        }, $historial);

        return new WP_REST_Response([
            'success' => true,
            'expediente' => $expediente_formateado,
        ], 200);
    }

    public function iniciar_tramite($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_tipos = $wpdb->prefix . 'flavor_tipos_tramite';
        $tabla_expedientes = $wpdb->prefix . 'flavor_expedientes';
        $tabla_historial = $wpdb->prefix . 'flavor_historial_expediente';

        $tipo_tramite_id = $request->get_param('tipo_tramite_id');
        $datos = $request->get_param('datos') ?: [];

        // Verificar tipo de trámite
        $tipo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_tipos WHERE id = %d AND estado = 'activo'",
            $tipo_tramite_id
        ));

        if (!$tipo) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Tipo de trámite no encontrado o no disponible',
            ], 404);
        }

        // Generar número de expediente
        $anio = date('Y');
        $ultimo = $wpdb->get_var($wpdb->prepare(
            "SELECT numero_expediente FROM $tabla_expedientes
            WHERE numero_expediente LIKE %s ORDER BY id DESC LIMIT 1",
            "EXP-$anio-%"
        ));

        if ($ultimo) {
            $numero = (int) substr($ultimo, -5) + 1;
        } else {
            $numero = 1;
        }
        $numero_expediente = sprintf("EXP-%s-%05d", $anio, $numero);

        // Crear expediente
        $wpdb->insert($tabla_expedientes, [
            'numero_expediente' => $numero_expediente,
            'tipo_tramite_id' => $tipo_tramite_id,
            'usuario_id' => $usuario_id,
            'estado' => 'iniciado',
            'datos_formulario' => json_encode($datos),
            'fecha_inicio' => current_time('mysql'),
        ]);

        $expediente_id = $wpdb->insert_id;

        // Registrar en historial
        $wpdb->insert($tabla_historial, [
            'expediente_id' => $expediente_id,
            'usuario_id' => $usuario_id,
            'accion' => 'inicio',
            'descripcion' => 'Expediente iniciado',
            'estado_nuevo' => 'iniciado',
            'fecha' => current_time('mysql'),
        ]);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Trámite iniciado correctamente',
            'expediente_id' => $expediente_id,
            'numero_expediente' => $numero_expediente,
        ], 201);
    }

    public function subir_documento($request) {
        $expediente_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_expedientes = $wpdb->prefix . 'flavor_expedientes';
        $tabla_documentos = $wpdb->prefix . 'flavor_documentos_expediente';

        // Verificar que el expediente pertenece al usuario
        $expediente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_expedientes WHERE id = %d AND usuario_id = %d",
            $expediente_id,
            $usuario_id
        ));

        if (!$expediente) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Expediente no encontrado',
            ], 404);
        }

        // Verificar que hay archivos
        if (empty($_FILES['documento'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'No se ha enviado ningún documento',
            ], 400);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $uploaded = wp_handle_upload($_FILES['documento'], ['test_form' => false]);

        if (isset($uploaded['error'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $uploaded['error'],
            ], 400);
        }

        $nombre = sanitize_text_field($request->get_param('nombre') ?: $_FILES['documento']['name']);
        $tipo_documento = sanitize_text_field($request->get_param('tipo_documento') ?: 'general');

        $wpdb->insert($tabla_documentos, [
            'expediente_id' => $expediente_id,
            'nombre' => $nombre,
            'tipo_documento' => $tipo_documento,
            'url_archivo' => $uploaded['url'],
            'ruta_archivo' => $uploaded['file'],
            'mime_type' => $uploaded['type'],
            'tamano' => filesize($uploaded['file']),
            'estado' => 'pendiente_revision',
            'fecha_subida' => current_time('mysql'),
        ]);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Documento subido correctamente',
            'documento_id' => $wpdb->insert_id,
        ], 201);
    }

    public function cancelar_expediente($request) {
        $expediente_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_expedientes = $wpdb->prefix . 'flavor_expedientes';
        $tabla_historial = $wpdb->prefix . 'flavor_historial_expediente';

        // Verificar que el expediente pertenece al usuario y puede cancelarse
        $expediente = $wpdb->get_row($wpdb->prepare(
            "SELECT estado FROM $tabla_expedientes WHERE id = %d AND usuario_id = %d",
            $expediente_id,
            $usuario_id
        ));

        if (!$expediente) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Expediente no encontrado',
            ], 404);
        }

        if (in_array($expediente->estado, ['aprobado', 'completado', 'cancelado'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Este expediente no puede ser cancelado',
            ], 400);
        }

        $wpdb->update(
            $tabla_expedientes,
            ['estado' => 'cancelado'],
            ['id' => $expediente_id]
        );

        $wpdb->insert($tabla_historial, [
            'expediente_id' => $expediente_id,
            'usuario_id' => $usuario_id,
            'accion' => 'cancelacion',
            'descripcion' => 'Expediente cancelado por el usuario',
            'estado_anterior' => $expediente->estado,
            'estado_nuevo' => 'cancelado',
            'fecha' => current_time('mysql'),
        ]);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Expediente cancelado',
        ], 200);
    }

    private function formatear_expediente($expediente) {
        if (!$expediente) return null;

        return [
            'id' => (int) $expediente['id'],
            'numero' => $expediente['numero_expediente'],
            'tipo_tramite_id' => (int) $expediente['tipo_tramite_id'],
            'tipo_nombre' => $expediente['tipo_nombre'] ?? '',
            'icono' => $expediente['icono'] ?? 'description',
            'estado' => $expediente['estado'],
            'fecha_inicio' => $expediente['fecha_inicio'],
            'fecha_resolucion' => $expediente['fecha_resolucion'] ?? null,
            'observaciones' => $expediente['observaciones'] ?? '',
        ];
    }

    private function formatear_tipo($tipo) {
        if (!$tipo) return null;

        return [
            'id' => (int) $tipo['id'],
            'nombre' => $tipo['nombre'],
            'descripcion' => $tipo['descripcion'] ?? '',
            'categoria' => $tipo['categoria'] ?? '',
            'icono' => $tipo['icono'] ?? 'description',
            'tiempo_estimado' => $tipo['tiempo_estimado'] ?? '',
            'requisitos' => $tipo['requisitos'] ?? '',
            'documentos_requeridos' => $tipo['documentos_requeridos'] ?? '',
            'precio' => (float) ($tipo['precio'] ?? 0),
        ];
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
