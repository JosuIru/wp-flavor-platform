<?php
/**
 * API REST para Avisos Municipales (Móvil)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Avisos_Municipales_API {

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
        // GET /avisos-municipales
        register_rest_route(self::NAMESPACE, '/avisos-municipales', [
            'methods' => 'GET',
            'callback' => [$this, 'get_avisos'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'categoria' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // POST /avisos-municipales/{id}/leer
        register_rest_route(self::NAMESPACE, '/avisos-municipales/(?P<id>\d+)/leer', [
            'methods' => 'POST',
            'callback' => [$this, 'marcar_leido'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // POST /avisos-municipales/suscripciones
        register_rest_route(self::NAMESPACE, '/avisos-municipales/suscripciones', [
            'methods' => 'POST',
            'callback' => [$this, 'actualizar_suscripciones'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'categorias' => [
                    'required' => true,
                    'type' => 'array',
                ],
            ],
        ]);
    }

    public function get_avisos($request) {
        $usuario_id = get_current_user_id();
        $categoria = $request->get_param('categoria');

        global $wpdb;
        $tabla_avisos = $wpdb->prefix . 'flavor_avisos_municipales';
        $tabla_lecturas = $wpdb->prefix . 'flavor_avisos_lecturas';
        $tabla_suscripciones = $wpdb->prefix . 'flavor_avisos_suscripciones';

        // Construir query
        $where = ["a.activo = 1"];
        $valores = [];

        if (!empty($categoria) && $categoria !== 'todas') {
            $where[] = "a.categoria = %s";
            $valores[] = $categoria;
        }

        $sql_where = implode(' AND ', $where);

        // Obtener avisos con estado de lectura
        $sql = "SELECT a.*, l.fecha_lectura, (l.id IS NOT NULL) as leido
                FROM $tabla_avisos a
                LEFT JOIN $tabla_lecturas l ON a.id = l.aviso_id AND l.usuario_id = %d
                WHERE $sql_where
                ORDER BY a.prioridad DESC, a.fecha_publicacion DESC
                LIMIT 50";

        array_unshift($valores, $usuario_id);

        $avisos = $wpdb->get_results(
            !empty($valores) ? $wpdb->prepare($sql, ...$valores) : $sql,
            ARRAY_A
        );

        $avisos_formateados = array_map([$this, 'formatear_aviso'], $avisos);

        // Categorías disponibles
        $categorias = $wpdb->get_col("SELECT DISTINCT categoria FROM $tabla_avisos WHERE activo = 1");

        // Suscripciones del usuario
        $suscripciones = $wpdb->get_col($wpdb->prepare(
            "SELECT categoria FROM $tabla_suscripciones WHERE usuario_id = %d",
            $usuario_id
        ));

        return new WP_REST_Response([
            'success' => true,
            'avisos' => $avisos_formateados,
            'categorias' => $categorias,
            'mis_suscripciones' => $suscripciones,
        ], 200);
    }

    public function marcar_leido($request) {
        $usuario_id = get_current_user_id();
        $aviso_id = $request->get_param('id');

        global $wpdb;
        $tabla_avisos = $wpdb->prefix . 'flavor_avisos_municipales';
        $tabla_lecturas = $wpdb->prefix . 'flavor_avisos_lecturas';

        // Verificar que el aviso existe
        $aviso = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_avisos WHERE id = %d",
            $aviso_id
        ));

        if (!$aviso) {
            return new WP_Error('aviso_no_encontrado', 'Aviso no encontrado', ['status' => 404]);
        }

        // Verificar si ya está marcado como leído
        $leido = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_lecturas WHERE aviso_id = %d AND usuario_id = %d",
            $aviso_id,
            $usuario_id
        ));

        if ($leido > 0) {
            return new WP_REST_Response([
                'success' => true,
                'mensaje' => 'Ya estaba marcado como leído',
            ], 200);
        }

        // Marcar como leído
        $wpdb->insert(
            $tabla_lecturas,
            [
                'aviso_id' => $aviso_id,
                'usuario_id' => $usuario_id,
                'fecha_lectura' => current_time('mysql'),
            ],
            ['%d', '%d', '%s']
        );

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => 'Aviso marcado como leído',
        ], 200);
    }

    public function actualizar_suscripciones($request) {
        $usuario_id = get_current_user_id();
        $categorias = $request->get_param('categorias');

        if (!is_array($categorias)) {
            return new WP_Error('datos_invalidos', 'El parámetro categorías debe ser un array', ['status' => 400]);
        }

        global $wpdb;
        $tabla_suscripciones = $wpdb->prefix . 'flavor_avisos_suscripciones';

        // Eliminar suscripciones existentes
        $wpdb->delete(
            $tabla_suscripciones,
            ['usuario_id' => $usuario_id],
            ['%d']
        );

        // Insertar nuevas suscripciones
        foreach ($categorias as $categoria) {
            $wpdb->insert(
                $tabla_suscripciones,
                [
                    'usuario_id' => $usuario_id,
                    'categoria' => sanitize_text_field($categoria),
                    'fecha_suscripcion' => current_time('mysql'),
                ],
                ['%d', '%s', '%s']
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'mensaje' => 'Suscripciones actualizadas correctamente',
        ], 200);
    }

    private function formatear_aviso($aviso) {
        if (!$aviso) return null;

        return [
            'id' => (int) $aviso['id'],
            'titulo' => $aviso['titulo'],
            'contenido' => $aviso['contenido'],
            'categoria' => $aviso['categoria'],
            'prioridad' => $aviso['prioridad'] ?? 'normal',
            'enlace' => $aviso['enlace'] ?? '',
            'fecha' => mysql2date('d/m/Y', $aviso['fecha_publicacion']),
            'leido' => isset($aviso['leido']) && $aviso['leido'],
        ];
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
