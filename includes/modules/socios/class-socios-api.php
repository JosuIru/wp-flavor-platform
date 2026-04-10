<?php
/**
 * API REST para Socios (Móvil)
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Socios_API {

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
        // GET /socios/perfil
        flavor_register_rest_route(self::NAMESPACE, '/socios/perfil', [
            'methods' => 'GET',
            'callback' => [$this, 'get_perfil'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // PUT /socios/perfil
        flavor_register_rest_route(self::NAMESPACE, '/socios/perfil', [
            'methods' => 'PUT',
            'callback' => [$this, 'actualizar_perfil'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /socios/cuotas
        flavor_register_rest_route(self::NAMESPACE, '/socios/cuotas', [
            'methods' => 'GET',
            'callback' => [$this, 'get_cuotas'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /socios/carnet
        flavor_register_rest_route(self::NAMESPACE, '/socios/carnet', [
            'methods' => 'GET',
            'callback' => [$this, 'get_carnet'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /socios/beneficios
        flavor_register_rest_route(self::NAMESPACE, '/socios/beneficios', [
            'methods' => 'GET',
            'callback' => [$this, 'get_beneficios'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /socios/actividad
        flavor_register_rest_route(self::NAMESPACE, '/socios/actividad', [
            'methods' => 'GET',
            'callback' => [$this, 'get_actividad'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
    }

    public function get_perfil($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_socios WHERE user_id = %d",
            $usuario_id
        ), ARRAY_A);

        if (!$socio) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'No eres socio registrado',
                'es_socio' => false,
            ], 200);
        }

        // Última cuota pagada
        $ultima_cuota = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_cuotas WHERE socio_id = %d AND estado = 'pagada' ORDER BY fecha_pago DESC LIMIT 1",
            $socio['id']
        ), ARRAY_A);

        // Cuotas pendientes
        $cuotas_pendientes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_cuotas WHERE socio_id = %d AND estado = 'pendiente'",
            $socio['id']
        ));

        $user = get_userdata($usuario_id);

        return new WP_REST_Response([
            'success' => true,
            'es_socio' => true,
            'perfil' => [
                'id' => (int) $socio['id'],
                'numero_socio' => $socio['numero_socio'],
                'nombre' => $user->display_name,
                'email' => $user->user_email,
                'telefono' => $socio['telefono'] ?? '',
                'direccion' => $socio['direccion'] ?? '',
                'fecha_alta' => $socio['fecha_alta'],
                'tipo_socio' => $socio['tipo_socio'] ?? 'estandar',
                'estado' => $socio['estado'],
                'foto_url' => $socio['foto_url'] ?? get_avatar_url($usuario_id),
            ],
            'membresia' => [
                'vigente' => $socio['estado'] === 'activo',
                'fecha_vencimiento' => $socio['fecha_vencimiento'] ?? null,
                'ultima_cuota_pagada' => $ultima_cuota ? $ultima_cuota['periodo'] : null,
                'cuotas_pendientes' => (int) $cuotas_pendientes,
            ],
        ], 200);
    }

    public function actualizar_perfil($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $socio_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_socios WHERE user_id = %d",
            $usuario_id
        ));

        if (!$socio_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'No eres socio registrado',
            ], 404);
        }

        $campos_actualizables = ['telefono', 'direccion'];
        $datos = [];

        foreach ($campos_actualizables as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                $datos[$campo] = sanitize_text_field($valor);
            }
        }

        if (empty($datos)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'No hay datos para actualizar',
            ], 400);
        }

        $wpdb->update($tabla_socios, $datos, ['id' => $socio_id]);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Perfil actualizado',
        ], 200);
    }

    public function get_cuotas($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $tabla_socios WHERE user_id = %d",
            $usuario_id
        ));

        if (!$socio) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'No eres socio registrado',
            ], 404);
        }

        $cuotas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_cuotas WHERE socio_id = %d ORDER BY periodo DESC",
            $socio->id
        ), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'cuotas' => array_map(function($cuota) {
                return [
                    'id' => (int) $cuota['id'],
                    'periodo' => $cuota['periodo'],
                    'importe' => (float) $cuota['importe'],
                    'estado' => $cuota['estado'],
                    'fecha_vencimiento' => $cuota['fecha_vencimiento'] ?? null,
                    'fecha_pago' => $cuota['fecha_pago'] ?? null,
                    'metodo_pago' => $cuota['metodo_pago'] ?? '',
                    'recibo_url' => $cuota['recibo_url'] ?? null,
                ];
            }, $cuotas),
        ], 200);
    }

    public function get_carnet($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_socios WHERE user_id = %d",
            $usuario_id
        ), ARRAY_A);

        if (!$socio) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'No eres socio registrado',
            ], 404);
        }

        $user = get_userdata($usuario_id);
        $organizacion = get_option('blogname', 'Organización');

        // Generar código QR data
        $qr_data = json_encode([
            'tipo' => 'carnet_socio',
            'numero' => $socio['numero_socio'],
            'usuario' => $usuario_id,
            'verificacion' => md5($socio['numero_socio'] . $usuario_id . wp_salt()),
        ]);

        return new WP_REST_Response([
            'success' => true,
            'carnet' => [
                'numero_socio' => $socio['numero_socio'],
                'nombre' => $user->display_name,
                'tipo_socio' => $socio['tipo_socio'] ?? 'Estándar',
                'fecha_alta' => $socio['fecha_alta'],
                'estado' => $socio['estado'],
                'vigente' => $socio['estado'] === 'activo',
                'foto_url' => $socio['foto_url'] ?? get_avatar_url($usuario_id, ['size' => 200]),
                'organizacion' => $organizacion,
                'qr_data' => base64_encode($qr_data),
            ],
        ], 200);
    }

    public function get_beneficios($request) {
        global $wpdb;

        $tabla_beneficios = $wpdb->prefix . 'flavor_socios_beneficios';

        // Verificar si existe la tabla
        if (!Flavor_Platform_Helpers::tabla_existe($tabla_beneficios)) {
            // Retornar beneficios por defecto
            return new WP_REST_Response([
                'success' => true,
                'beneficios' => [
                    [
                        'id' => 1,
                        'titulo' => 'Descuentos en actividades',
                        'descripcion' => '10% de descuento en cursos y talleres',
                        'icono' => 'school',
                        'activo' => true,
                    ],
                    [
                        'id' => 2,
                        'titulo' => 'Acceso prioritario',
                        'descripcion' => 'Reserva anticipada de espacios comunes',
                        'icono' => 'event_seat',
                        'activo' => true,
                    ],
                    [
                        'id' => 3,
                        'titulo' => 'Biblioteca gratuita',
                        'descripcion' => 'Préstamos de libros sin coste adicional',
                        'icono' => 'menu_book',
                        'activo' => true,
                    ],
                ],
            ], 200);
        }

        $beneficios = $wpdb->get_results(
            "SELECT * FROM $tabla_beneficios WHERE activo = 1 ORDER BY orden ASC",
            ARRAY_A
        );

        return new WP_REST_Response([
            'success' => true,
            'beneficios' => array_map(function($b) {
                return [
                    'id' => (int) $b['id'],
                    'titulo' => $b['titulo'],
                    'descripcion' => $b['descripcion'] ?? '',
                    'icono' => $b['icono'] ?? 'card_giftcard',
                    'tipo' => $b['tipo'] ?? 'general',
                    'valor_descuento' => (float) ($b['valor_descuento'] ?? 0),
                    'condiciones' => $b['condiciones'] ?? '',
                    'activo' => true,
                ];
            }, $beneficios),
        ], 200);
    }

    public function get_actividad($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $tabla_socios WHERE user_id = %d",
            $usuario_id
        ));

        if (!$socio) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'No eres socio registrado',
            ], 404);
        }

        $actividades = [];

        // Eventos inscritos
        $tabla_eventos_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';
        if (Flavor_Platform_Helpers::tabla_existe($tabla_eventos_inscripciones)) {
            $eventos = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_eventos_inscripciones WHERE usuario_id = %d",
                $usuario_id
            ));
            $actividades['eventos_inscritos'] = (int) $eventos;
        }

        // Cursos completados
        $tabla_cursos_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
        if (Flavor_Platform_Helpers::tabla_existe($tabla_cursos_inscripciones)) {
            $cursos = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_cursos_inscripciones WHERE usuario_id = %d AND estado IN ('completado', 'activo')",
                $usuario_id
            ));
            $actividades['cursos_completados'] = (int) $cursos;
        }

        // Reservas de espacios
        $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
        if (Flavor_Platform_Helpers::tabla_existe($tabla_reservas)) {
            $reservas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_reservas WHERE usuario_id = %d",
                $usuario_id
            ));
            $actividades['reservas_realizadas'] = (int) $reservas;
        }

        // Préstamos biblioteca
        $tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
        if (Flavor_Platform_Helpers::tabla_existe($tabla_prestamos)) {
            $prestamos = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_prestamos WHERE usuario_id = %d",
                $usuario_id
            ));
            $actividades['prestamos_biblioteca'] = (int) $prestamos;
        }

        return new WP_REST_Response([
            'success' => true,
            'actividad' => $actividades,
        ], 200);
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
