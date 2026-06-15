<?php
/**
 * API REST para Ahorro Rotativo (móvil).
 *
 * Las lecturas se resuelven aquí; las escrituras se delegan al módulo
 * (Flavor_Platform_Ahorro_Rotativo_Module) que es la fuente de verdad.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Ahorro_Rotativo_API {

    const NAMESPACE = FLAVOR_PLATFORM_REST_NAMESPACE;

    private static $instance = null;
    private $module = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /** Instancia perezosa del módulo (fuente de verdad de las escrituras). */
    private function module() {
        if ($this->module === null) {
            $this->module = new Flavor_Platform_Ahorro_Rotativo_Module();
        }
        return $this->module;
    }

    public function register_routes() {
        $base = '/ahorro-rotativo';

        // Lectura: mis círculos.
        flavor_register_rest_route(self::NAMESPACE, $base . '/circulos', [
            'methods' => 'GET',
            'callback' => [$this, 'get_mis_circulos'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // Escritura: crear círculo.
        flavor_register_rest_route(self::NAMESPACE, $base . '/circulos', [
            'methods' => 'POST',
            'callback' => [$this, 'crear_circulo'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'nombre' => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'importe_aportacion' => ['required' => true, 'type' => 'number'],
                'num_plazas' => ['required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint'],
                'periodo_dias' => ['type' => 'integer', 'default' => 30, 'sanitize_callback' => 'absint'],
                'modo_orden' => ['type' => 'string', 'default' => 'fijo', 'sanitize_callback' => 'sanitize_text_field'],
            ],
        ]);

        // Lectura: detalle de un círculo (turnos, ronda actual, aportaciones, reputación).
        flavor_register_rest_route(self::NAMESPACE, $base . '/circulos/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_detalle'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // Escritura: activar círculo.
        flavor_register_rest_route(self::NAMESPACE, $base . '/circulos/(?P<id>\d+)/activar', [
            'methods' => 'POST',
            'callback' => [$this, 'activar_circulo'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // Escritura: invitar/añadir miembro.
        flavor_register_rest_route(self::NAMESPACE, $base . '/circulos/(?P<id>\d+)/miembros', [
            'methods' => 'POST',
            'callback' => [$this, 'invitar_miembro'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'nombre_visible' => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'usuario_id' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        // Escritura: unirse con código de invitación.
        flavor_register_rest_route(self::NAMESPACE, $base . '/unirse', [
            'methods' => 'POST',
            'callback' => [$this, 'unirse'],
            'permission_callback' => [$this, 'check_authentication'],
            'args' => [
                'codigo_invitacion' => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            ],
        ]);

        // Escritura: marcar aportación pagada.
        flavor_register_rest_route(self::NAMESPACE, $base . '/aportaciones/(?P<id>\d+)/pagar', [
            'methods' => 'POST',
            'callback' => [$this, 'pagar'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // Escritura: confirmar recepción.
        flavor_register_rest_route(self::NAMESPACE, $base . '/aportaciones/(?P<id>\d+)/confirmar', [
            'methods' => 'POST',
            'callback' => [$this, 'confirmar'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
    }

    // ---------------------- Escrituras (delegadas al módulo) ----------------------

    public function crear_circulo($request) {
        return rest_ensure_response($this->module()->execute_action('crear_circulo', [
            'nombre' => $request->get_param('nombre'),
            'importe_aportacion' => $request->get_param('importe_aportacion'),
            'num_plazas' => $request->get_param('num_plazas'),
            'periodo_dias' => $request->get_param('periodo_dias'),
            'modo_orden' => $request->get_param('modo_orden'),
        ]));
    }

    public function activar_circulo($request) {
        return rest_ensure_response($this->module()->execute_action('activar_circulo', [
            'circulo_id' => absint($request['id']),
        ]));
    }

    public function invitar_miembro($request) {
        return rest_ensure_response($this->module()->execute_action('invitar_miembro', [
            'circulo_id' => absint($request['id']),
            'nombre_visible' => $request->get_param('nombre_visible'),
            'usuario_id' => $request->get_param('usuario_id'),
        ]));
    }

    public function unirse($request) {
        return rest_ensure_response($this->module()->execute_action('unirse', [
            'codigo_invitacion' => $request->get_param('codigo_invitacion'),
        ]));
    }

    public function pagar($request) {
        return rest_ensure_response($this->module()->execute_action('marcar_pagada', [
            'aportacion_id' => absint($request['id']),
        ]));
    }

    public function confirmar($request) {
        return rest_ensure_response($this->module()->execute_action('confirmar_recepcion', [
            'aportacion_id' => absint($request['id']),
        ]));
    }

    // ---------------------- Lecturas ----------------------

    public function get_mis_circulos($request) {
        global $wpdb;
        $usuario_id = get_current_user_id();
        $t_circulos = $wpdb->prefix . 'flavor_ahorro_rotativo_circulos';
        $t_miembros = $wpdb->prefix . 'flavor_ahorro_rotativo_miembros';

        $filas = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT c.* FROM $t_circulos c
             LEFT JOIN $t_miembros m ON m.circulo_id = c.id AND m.usuario_id = %d
             WHERE c.creado_por = %d OR m.id IS NOT NULL
             ORDER BY c.fecha_creacion DESC",
            $usuario_id, $usuario_id
        ));

        return rest_ensure_response([
            'success' => true,
            'circulos' => array_map([$this, 'formatear_circulo'], $filas ?: []),
        ]);
    }

    public function get_detalle($request) {
        global $wpdb;
        $circulo_id = absint($request['id']);
        $usuario_id = get_current_user_id();
        $t_circulos = $wpdb->prefix . 'flavor_ahorro_rotativo_circulos';
        $t_miembros = $wpdb->prefix . 'flavor_ahorro_rotativo_miembros';
        $t_rondas = $wpdb->prefix . 'flavor_ahorro_rotativo_rondas';
        $t_aport = $wpdb->prefix . 'flavor_ahorro_rotativo_aportaciones';

        $circulo = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t_circulos WHERE id = %d", $circulo_id));
        if (!$circulo) {
            return new WP_Error('no_encontrado', __('Círculo no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        $es_creador = (int) $circulo->creado_por === $usuario_id;
        $es_miembro = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $t_miembros WHERE circulo_id = %d AND usuario_id = %d",
            $circulo_id, $usuario_id
        )) > 0;
        if (!$es_creador && !$es_miembro) {
            return new WP_Error('sin_permiso', __('No perteneces a este círculo.', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 403]);
        }

        // Reputación derivada por miembro: % de aportaciones confirmadas a tiempo.
        $reputaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT m.id AS miembro_id,
                    COUNT(a.id) AS total,
                    SUM(a.estado = 'confirmada'
                        AND a.fecha_pago IS NOT NULL
                        AND a.fecha_pago <= DATE_ADD(r.periodo_fin, INTERVAL c.dias_gracia DAY)) AS a_tiempo
             FROM $t_miembros m
             JOIN $t_circulos c ON c.id = m.circulo_id
             LEFT JOIN $t_aport a ON a.miembro_id = m.id
             LEFT JOIN $t_rondas r ON r.id = a.ronda_id
             WHERE m.circulo_id = %d
             GROUP BY m.id",
            $circulo_id
        ), OBJECT_K);

        $miembros = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $t_miembros WHERE circulo_id = %d ORDER BY posicion_turno IS NULL, posicion_turno",
            $circulo_id
        ));
        $miembros_salida = array_map(function ($m) use ($reputaciones) {
            $rep = $reputaciones[$m->id] ?? null;
            $total = $rep ? (int) $rep->total : 0;
            $a_tiempo = $rep ? (int) $rep->a_tiempo : 0;
            return [
                'id' => (int) $m->id,
                'usuario_id' => $m->usuario_id ? (int) $m->usuario_id : null,
                'nombre_visible' => $m->nombre_visible,
                'posicion_turno' => $m->posicion_turno ? (int) $m->posicion_turno : null,
                'estado_invitacion' => $m->estado_invitacion,
                'reputacion_porcentaje' => $total > 0 ? (int) round(100 * $a_tiempo / $total) : null,
            ];
        }, $miembros ?: []);

        // Ronda actual = primera abierta (o la última si todas cerradas).
        $ronda = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $t_rondas WHERE circulo_id = %d ORDER BY (estado = 'abierta') DESC, numero_ronda ASC LIMIT 1",
            $circulo_id
        ));
        $ronda_salida = null;
        if ($ronda) {
            $aportaciones = $wpdb->get_results($wpdb->prepare(
                "SELECT a.*, m.nombre_visible FROM $t_aport a
                 JOIN $t_miembros m ON m.id = a.miembro_id
                 WHERE a.ronda_id = %d",
                $ronda->id
            ));
            $ronda_salida = [
                'id' => (int) $ronda->id,
                'numero_ronda' => (int) $ronda->numero_ronda,
                'periodo_inicio' => $ronda->periodo_inicio,
                'periodo_fin' => $ronda->periodo_fin,
                'miembro_beneficiario_id' => (int) $ronda->miembro_beneficiario_id,
                'estado' => $ronda->estado,
                'aportaciones' => array_map(function ($a) {
                    return [
                        'id' => (int) $a->id,
                        'miembro_id' => (int) $a->miembro_id,
                        'nombre_visible' => $a->nombre_visible,
                        'importe' => (float) $a->importe,
                        'estado' => $a->estado,
                        'fecha_pago' => $a->fecha_pago,
                    ];
                }, $aportaciones ?: []),
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'circulo' => $this->formatear_circulo($circulo),
            'es_creador' => $es_creador,
            'miembros' => $miembros_salida,
            'ronda_actual' => $ronda_salida,
        ]);
    }

    private function formatear_circulo($c) {
        return [
            'id' => (int) $c->id,
            'colectivo_id' => $c->colectivo_id ? (int) $c->colectivo_id : null,
            'nombre' => $c->nombre,
            'importe_aportacion' => (float) $c->importe_aportacion,
            'moneda' => $c->moneda,
            'periodo_dias' => (int) $c->periodo_dias,
            'num_plazas' => (int) $c->num_plazas,
            'bote' => (float) $c->importe_aportacion * (int) $c->num_plazas,
            'modo_orden' => $c->modo_orden,
            'dias_gracia' => (int) $c->dias_gracia,
            'estado' => $c->estado,
            'creado_por' => (int) $c->creado_por,
        ];
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
