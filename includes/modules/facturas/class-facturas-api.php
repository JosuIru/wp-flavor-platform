<?php
/**
 * API REST para Facturas (Móvil)
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Facturas_API {

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
        // GET /facturas
        flavor_register_rest_route(self::NAMESPACE, '/facturas', [
            'methods' => 'GET',
            'callback' => [$this, 'get_facturas'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /facturas/{id}
        flavor_register_rest_route(self::NAMESPACE, '/facturas/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_factura'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /facturas/{id}/pdf
        flavor_register_rest_route(self::NAMESPACE, '/facturas/(?P<id>\d+)/pdf', [
            'methods' => 'GET',
            'callback' => [$this, 'get_pdf'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);

        // GET /facturas/resumen
        flavor_register_rest_route(self::NAMESPACE, '/facturas/resumen', [
            'methods' => 'GET',
            'callback' => [$this, 'get_resumen'],
            'permission_callback' => [$this, 'check_authentication'],
        ]);
    }

    public function get_facturas($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_facturas = $wpdb->prefix . 'flavor_facturas';

        $estado = $request->get_param('estado');
        $anio = $request->get_param('anio');
        $limite = $request->get_param('limite') ?: 50;

        $where = "usuario_id = %d";
        $params = [$usuario_id];

        if ($estado) {
            $where .= " AND estado = %s";
            $params[] = $estado;
        }

        if ($anio) {
            $where .= " AND YEAR(fecha_emision) = %d";
            $params[] = $anio;
        }

        $params[] = $limite;

        $facturas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_facturas WHERE $where ORDER BY fecha_emision DESC LIMIT %d",
            $params
        ), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'facturas' => array_map([$this, 'formatear_factura'], $facturas),
        ], 200);
    }

    public function get_factura($request) {
        $factura_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_facturas = $wpdb->prefix . 'flavor_facturas';
        $tabla_lineas = $wpdb->prefix . 'flavor_facturas_lineas';
        $tabla_pagos = $wpdb->prefix . 'flavor_facturas_pagos';

        $factura = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_facturas WHERE id = %d AND usuario_id = %d",
            $factura_id,
            $usuario_id
        ), ARRAY_A);

        if (!$factura) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Factura no encontrada',
            ], 404);
        }

        // Líneas de factura
        $lineas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_lineas WHERE factura_id = %d ORDER BY id ASC",
            $factura_id
        ), ARRAY_A);

        // Pagos
        $pagos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_pagos WHERE factura_id = %d ORDER BY fecha_pago DESC",
            $factura_id
        ), ARRAY_A);

        $factura_formateada = $this->formatear_factura($factura);
        $factura_formateada['lineas'] = array_map(function($linea) {
            return [
                'id' => (int) $linea['id'],
                'concepto' => $linea['concepto'],
                'descripcion' => $linea['descripcion'] ?? '',
                'cantidad' => (float) $linea['cantidad'],
                'precio_unitario' => (float) $linea['precio_unitario'],
                'descuento' => (float) ($linea['descuento'] ?? 0),
                'iva' => (float) ($linea['iva'] ?? 21),
                'subtotal' => (float) $linea['subtotal'],
            ];
        }, $lineas);
        $factura_formateada['pagos'] = array_map(function($pago) {
            return [
                'id' => (int) $pago['id'],
                'importe' => (float) $pago['importe'],
                'metodo' => $pago['metodo_pago'],
                'referencia' => $pago['referencia'] ?? '',
                'fecha' => $pago['fecha_pago'],
            ];
        }, $pagos);

        return new WP_REST_Response([
            'success' => true,
            'factura' => $factura_formateada,
        ], 200);
    }

    public function get_pdf($request) {
        $factura_id = $request->get_param('id');
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_facturas = $wpdb->prefix . 'flavor_facturas';

        $factura = $wpdb->get_row($wpdb->prepare(
            "SELECT pdf_url FROM $tabla_facturas WHERE id = %d AND usuario_id = %d",
            $factura_id,
            $usuario_id
        ));

        if (!$factura) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Factura no encontrada',
            ], 404);
        }

        if (empty($factura->pdf_url)) {
            // Intentar generar PDF si existe el método
            $facturas_module_class = function_exists('flavor_get_runtime_class_name')
                ? flavor_get_runtime_class_name('Flavor_Chat_Facturas_Module')
                : 'Flavor_Chat_Facturas_Module';
            if (class_exists($facturas_module_class)) {
                $module = $facturas_module_class::get_instance();
                if (method_exists($module, 'generar_pdf')) {
                    $pdf_url = $module->generar_pdf($factura_id);
                    if ($pdf_url) {
                        return new WP_REST_Response([
                            'success' => true,
                            'pdf_url' => $pdf_url,
                        ], 200);
                    }
                }
            }

            return new WP_REST_Response([
                'success' => false,
                'message' => 'PDF no disponible',
            ], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'pdf_url' => $factura->pdf_url,
        ], 200);
    }

    public function get_resumen($request) {
        $usuario_id = get_current_user_id();
        global $wpdb;

        $tabla_facturas = $wpdb->prefix . 'flavor_facturas';

        $anio_actual = date('Y');

        // Total facturado este año
        $total_anio = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM $tabla_facturas
            WHERE usuario_id = %d AND YEAR(fecha_emision) = %d AND estado != 'anulada'",
            $usuario_id,
            $anio_actual
        ));

        // Facturas pendientes
        $pendientes = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as cantidad, COALESCE(SUM(total - COALESCE(pagado, 0)), 0) as importe
            FROM $tabla_facturas
            WHERE usuario_id = %d AND estado = 'pendiente'",
            $usuario_id
        ));

        // Facturas por año
        $por_anio = $wpdb->get_results($wpdb->prepare(
            "SELECT YEAR(fecha_emision) as anio, COUNT(*) as cantidad, SUM(total) as total
            FROM $tabla_facturas
            WHERE usuario_id = %d AND estado != 'anulada'
            GROUP BY YEAR(fecha_emision)
            ORDER BY anio DESC
            LIMIT 5",
            $usuario_id
        ), ARRAY_A);

        // Última factura
        $ultima = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_facturas WHERE usuario_id = %d ORDER BY fecha_emision DESC LIMIT 1",
            $usuario_id
        ), ARRAY_A);

        return new WP_REST_Response([
            'success' => true,
            'resumen' => [
                'total_anio_actual' => (float) $total_anio,
                'anio' => (int) $anio_actual,
                'facturas_pendientes' => [
                    'cantidad' => (int) $pendientes->cantidad,
                    'importe' => (float) $pendientes->importe,
                ],
                'historico' => array_map(function($item) {
                    return [
                        'anio' => (int) $item['anio'],
                        'cantidad' => (int) $item['cantidad'],
                        'total' => (float) $item['total'],
                    ];
                }, $por_anio),
                'ultima_factura' => $ultima ? $this->formatear_factura($ultima) : null,
            ],
        ], 200);
    }

    private function formatear_factura($factura) {
        if (!$factura) return null;

        return [
            'id' => (int) $factura['id'],
            'numero' => $factura['numero_factura'],
            'serie' => $factura['serie'] ?? '',
            'fecha_emision' => $factura['fecha_emision'],
            'fecha_vencimiento' => $factura['fecha_vencimiento'] ?? null,
            'concepto' => $factura['concepto'] ?? '',
            'base_imponible' => (float) ($factura['base_imponible'] ?? 0),
            'iva' => (float) ($factura['iva'] ?? 0),
            'total' => (float) $factura['total'],
            'pagado' => (float) ($factura['pagado'] ?? 0),
            'pendiente' => (float) ($factura['total'] - ($factura['pagado'] ?? 0)),
            'estado' => $factura['estado'],
            'pdf_url' => $factura['pdf_url'] ?? null,
            'notas' => $factura['notas'] ?? '',
        ];
    }

    public function check_authentication($request) {
        return is_user_logged_in();
    }
}
