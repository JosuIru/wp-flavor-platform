<?php
/**
 * API REST para Ayuda Vecinal (Móvil)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Ayuda_Vecinal_API {

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
        // GET /ayuda-vecinal
        register_rest_route(self::NAMESPACE, '/ayuda-vecinal', [
            'methods' => 'GET',
            'callback' => [$this, 'get_sistema'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /ayuda-vecinal/solicitudes
        register_rest_route(self::NAMESPACE, '/ayuda-vecinal/solicitudes', [
            'methods' => 'POST',
            'callback' => [$this, 'crear_solicitud'],
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
                'categoria' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'urgencia' => [
                    'type' => 'string',
                    'default' => 'normal',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // POST /ayuda-vecinal/solicitudes/{id}/ofrecer
        register_rest_route(self::NAMESPACE, '/ayuda-vecinal/solicitudes/(?P<id>\d+)/ofrecer', [
            'methods' => 'POST',
            'callback' => [$this, 'ofrecer_ayuda'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // DELETE /ayuda-vecinal/solicitudes/{id}
        register_rest_route(self::NAMESPACE, '/ayuda-vecinal/solicitudes/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'cancelar_solicitud'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
    }

    public function get_sistema($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_solicitudes = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';
        $tabla_voluntarios = $wpdb->prefix . 'flavor_ayuda_vecinal_voluntarios';

        // Solicitudes activas de otros usuarios
        $solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.display_name as usuario_nombre
            FROM $tabla_solicitudes s
            INNER JOIN {$wpdb->users} u ON s.usuario_id = u.ID
            WHERE s.estado = 'pendiente' AND s.usuario_id != %d
            ORDER BY s.urgencia DESC, s.fecha_solicitud DESC
            LIMIT 50",
            $usuario_id
        ), ARRAY_A);

        $solicitudes_formateadas = array_map([$this, 'formatear_solicitud'], $solicitudes);

        // Voluntarios disponibles
        $voluntarios = $wpdb->get_results(
            "SELECT v.*, u.display_name as nombre, u.user_email as email,
                    (SELECT COUNT(*) FROM $tabla_solicitudes WHERE voluntario_id = v.usuario_id AND estado = 'completada') as ayudas_completadas
            FROM $tabla_voluntarios v
            INNER JOIN {$wpdb->users} u ON v.usuario_id = u.ID
            WHERE v.disponible = 1
            ORDER BY ayudas_completadas DESC
            LIMIT 30",
            ARRAY_A
        );

        $voluntarios_formateados = array_map([$this, 'formatear_voluntario'], $voluntarios);

        // Mis solicitudes
        $mis_solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.display_name as voluntario_nombre
            FROM $tabla_solicitudes s
            LEFT JOIN {$wpdb->users} u ON s.voluntario_id = u.ID
            WHERE s.usuario_id = %d
            ORDER BY s.fecha_solicitud DESC
            LIMIT 20",
            $usuario_id
        ), ARRAY_A);

        $mis_solicitudes_formateadas = array_map([$this, 'formatear_solicitud_propia'], $mis_solicitudes);

        // Categorías disponibles
        $categorias = $this->obtener_categorias();

        return new WP_REST_Response([
            'success' => true,
            'solicitudes' => $solicitudes_formateadas,
            'voluntarios' => $voluntarios_formateados,
            'mis_solicitudes' => $mis_solicitudes_formateadas,
            'categorias' => $categorias,
        ], 200);
    }

    public function crear_solicitud($request) {
        $usuario_id = get_current_user_id();
        $titulo = $request->get_param('titulo');
        $descripcion = $request->get_param('descripcion');
        $categoria = $request->get_param('categoria');
        $urgencia = $request->get_param('urgencia') ?: 'normal';

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';

        $resultado = $wpdb->insert(
            $tabla,
            [
                'usuario_id' => $usuario_id,
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'categoria' => $categoria,
                'urgencia' => $urgencia,
                'estado' => 'pendiente',
                'fecha_solicitud' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('error_crear', 'Error al crear la solicitud', ['status' => 500]);
        }

        // Notificar a voluntarios suscritos a esta categoría
        $this->notificar_voluntarios($categoria, $titulo);

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => 'Solicitud creada correctamente',
            'solicitud_id' => $wpdb->insert_id,
        ], 201);
    }

    public function ofrecer_ayuda($request) {
        $usuario_id = get_current_user_id();
        $solicitud_id = $request->get_param('id');

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';

        // Verificar que la solicitud existe y está pendiente
        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d AND estado = 'pendiente'",
            $solicitud_id
        ));

        if (!$solicitud) {
            return new WP_Error('solicitud_no_disponible', 'La solicitud no está disponible', ['status' => 400]);
        }

        // No puedes ayudarte a ti mismo
        if ($solicitud->usuario_id == $usuario_id) {
            return new WP_Error('solicitud_propia', 'No puedes ayudarte a ti mismo', ['status' => 400]);
        }

        // Asignar voluntario
        $wpdb->update(
            $tabla,
            [
                'voluntario_id' => $usuario_id,
                'estado' => 'en_curso',
                'fecha_asignacion' => current_time('mysql'),
            ],
            ['id' => $solicitud_id],
            ['%d', '%s', '%s'],
            ['%d']
        );

        // Notificar al solicitante
        $solicitante = get_user_by('id', $solicitud->usuario_id);
        $voluntario = wp_get_current_user();

        if ($solicitante && !empty($solicitante->user_email)) {
            $asunto = 'Alguien va a ayudarte - Ayuda Vecinal';
            $contenido = sprintf(
                "Hola %s,\n\n%s ha aceptado ayudarte con: %s\n\n",
                $solicitante->display_name,
                $voluntario->display_name,
                $solicitud->titulo
            );
            wp_mail($solicitante->user_email, $asunto, $contenido);
        }

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => 'Te has ofrecido como voluntario',
        ], 200);
    }

    public function cancelar_solicitud($request) {
        $usuario_id = get_current_user_id();
        $solicitud_id = $request->get_param('id');

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_ayuda_vecinal_solicitudes';

        // Verificar permisos
        $solicitud = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d AND usuario_id = %d",
            $solicitud_id,
            $usuario_id
        ));

        if (!$solicitud) {
            return new WP_Error('sin_permiso', 'No tienes permiso para cancelar esta solicitud', ['status' => 403]);
        }

        // Cancelar solicitud
        $wpdb->update(
            $tabla,
            ['estado' => 'cancelada'],
            ['id' => $solicitud_id],
            ['%s'],
            ['%d']
        );

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => 'Solicitud cancelada correctamente',
        ], 200);
    }

    private function formatear_solicitud($solicitud) {
        if (!$solicitud) return null;

        return [
            'id' => (int) $solicitud['id'],
            'titulo' => $solicitud['titulo'],
            'descripcion' => $solicitud['descripcion'],
            'categoria' => $solicitud['categoria'],
            'urgencia' => $solicitud['urgencia'],
            'estado' => $solicitud['estado'],
            'usuario_nombre' => $solicitud['usuario_nombre'],
            'fecha_solicitud' => mysql2date('c', $solicitud['fecha_solicitud']),
        ];
    }

    private function formatear_solicitud_propia($solicitud) {
        if (!$solicitud) return null;

        return [
            'id' => (int) $solicitud['id'],
            'titulo' => $solicitud['titulo'],
            'descripcion' => $solicitud['descripcion'],
            'categoria' => $solicitud['categoria'],
            'urgencia' => $solicitud['urgencia'],
            'estado' => $solicitud['estado'],
            'voluntario_nombre' => $solicitud['voluntario_nombre'] ?? '',
            'fecha_solicitud' => mysql2date('c', $solicitud['fecha_solicitud']),
        ];
    }

    private function formatear_voluntario($voluntario) {
        if (!$voluntario) return null;

        $especialidades = isset($voluntario['especialidades']) ? explode(',', $voluntario['especialidades']) : [];

        return [
            'id' => (int) $voluntario['usuario_id'],
            'nombre' => $voluntario['nombre'],
            'especialidades' => array_map('trim', $especialidades),
            'ayudas_completadas' => (int) ($voluntario['ayudas_completadas'] ?? 0),
        ];
    }

    private function obtener_categorias() {
        return [
            ['id' => 'compras', 'nombre' => 'Compras', 'icono' => 'shopping_cart'],
            ['id' => 'transporte', 'nombre' => 'Transporte', 'icono' => 'directions_car'],
            ['id' => 'cuidados', 'nombre' => 'Cuidados', 'icono' => 'favorite'],
            ['id' => 'tecnologia', 'nombre' => 'Tecnología', 'icono' => 'computer'],
            ['id' => 'reparaciones', 'nombre' => 'Reparaciones', 'icono' => 'build'],
            ['id' => 'tareas_domesticas', 'nombre' => 'Tareas Domésticas', 'icono' => 'cleaning_services'],
            ['id' => 'acompanamiento', 'nombre' => 'Acompañamiento', 'icono' => 'accessibility'],
        ];
    }

    private function notificar_voluntarios($categoria, $titulo) {
        global $wpdb;
        $tabla_voluntarios = $wpdb->prefix . 'flavor_ayuda_vecinal_voluntarios';

        $voluntarios = $wpdb->get_results($wpdb->prepare(
            "SELECT v.usuario_id, u.user_email, u.display_name
            FROM $tabla_voluntarios v
            INNER JOIN {$wpdb->users} u ON v.usuario_id = u.ID
            WHERE v.disponible = 1 AND (v.especialidades LIKE %s OR v.especialidades = '')",
            '%' . $wpdb->esc_like($categoria) . '%'
        ));

        foreach ($voluntarios as $voluntario) {
            if (!empty($voluntario->user_email)) {
                $asunto = 'Nueva solicitud de ayuda';
                $contenido = sprintf(
                    "Hola %s,\n\nHay una nueva solicitud de ayuda en la categoría %s:\n\n%s\n\n",
                    $voluntario->display_name,
                    $categoria,
                    $titulo
                );
                wp_mail($voluntario->user_email, $asunto, $contenido);
            }
        }
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
