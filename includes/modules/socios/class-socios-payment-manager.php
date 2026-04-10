<?php
/**
 * Gestor de pagos del módulo Socios
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar pagos de cuotas de socios
 */
class Flavor_Socios_Payment_Manager {

    /**
     * Instancia singleton
     *
     * @var Flavor_Socios_Payment_Manager
     */
    private static $instancia = null;

    /**
     * Gateways de pago registrados
     *
     * @var array
     */
    private $gateways = [];

    /**
     * Constructor privado para singleton
     */
    private function __construct() {
        $this->cargar_gateways();
        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Socios_Payment_Manager
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'maybe_create_transactions_table']);
        add_action('wp_ajax_socios_iniciar_pago', [$this, 'ajax_iniciar_pago']);
        add_action('wp_ajax_socios_confirmar_pago', [$this, 'ajax_confirmar_pago']);
        add_action('wp_ajax_socios_verificar_pago', [$this, 'ajax_verificar_pago']);

        // Webhook endpoints para gateways
        add_action('rest_api_init', [$this, 'register_webhook_routes']);
    }

    /**
     * Carga los gateways de pago disponibles
     */
    private function cargar_gateways() {
        $directorio_base = dirname(__FILE__) . '/gateways/';

        // Gateway manual (siempre disponible)
        $this->gateways['manual'] = [
            'id'          => 'manual',
            'nombre'      => __('Transferencia/Efectivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'descripcion' => __('Pago manual por transferencia o efectivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'activo'      => true,
            'clase'       => null,
        ];

        // Cargar gateway Stripe
        $archivo_stripe = $directorio_base . 'class-socios-gateway-stripe.php';
        if (file_exists($archivo_stripe)) {
            require_once $archivo_stripe;
            if (class_exists('Flavor_Socios_Gateway_Stripe')) {
                $stripe = new Flavor_Socios_Gateway_Stripe();
                $this->gateways['stripe'] = [
                    'id'          => 'stripe',
                    'nombre'      => __('Tarjeta de crédito/débito', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'descripcion' => __('Pago seguro con tarjeta vía Stripe', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'activo'      => $stripe->is_configured(),
                    'clase'       => $stripe,
                ];
            }
        }

        // Cargar gateway WooCommerce
        $archivo_woo = $directorio_base . 'class-socios-gateway-woocommerce.php';
        if (file_exists($archivo_woo) && class_exists('WooCommerce')) {
            require_once $archivo_woo;
            if (class_exists('Flavor_Socios_Gateway_WooCommerce')) {
                $woo = new Flavor_Socios_Gateway_WooCommerce();
                $this->gateways['woocommerce'] = [
                    'id'          => 'woocommerce',
                    'nombre'      => __('WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'descripcion' => __('Pagar usando métodos de WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'activo'      => $woo->is_configured(),
                    'clase'       => $woo,
                ];
            }
        }

        // Permitir agregar gateways externos
        $this->gateways = apply_filters('flavor_socios_payment_gateways', $this->gateways);
    }

    /**
     * Obtiene los gateways disponibles
     *
     * @param bool $solo_activos Solo retornar gateways activos
     * @return array
     */
    public function get_gateways($solo_activos = true) {
        if (!$solo_activos) {
            return $this->gateways;
        }

        return array_filter($this->gateways, function($gateway) {
            return $gateway['activo'];
        });
    }

    /**
     * Obtiene un gateway específico
     *
     * @param string $gateway_id ID del gateway
     * @return array|null
     */
    public function get_gateway($gateway_id) {
        return $this->gateways[$gateway_id] ?? null;
    }

    /**
     * Procesa un pago de cuota
     *
     * @param int    $cuota_id   ID de la cuota
     * @param string $gateway_id ID del gateway a usar
     * @param array  $datos      Datos adicionales del pago
     * @return array Resultado del procesamiento
     */
    public function procesar_pago($cuota_id, $gateway_id, $datos = []) {
        global $wpdb;
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';
        $tabla_transacciones = $wpdb->prefix . 'flavor_socios_transacciones';

        // Verificar cuota
        $cuota = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_cuotas WHERE id = %d",
            $cuota_id
        ));

        if (!$cuota) {
            return [
                'success' => false,
                'error'   => __('Cuota no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        if ($cuota->estado === 'pagada') {
            return [
                'success' => false,
                'error'   => __('Esta cuota ya está pagada.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Verificar gateway
        $gateway = $this->get_gateway($gateway_id);
        if (!$gateway || !$gateway['activo']) {
            return [
                'success' => false,
                'error'   => __('Método de pago no disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Crear transacción pendiente
        $transaccion_id = $this->crear_transaccion([
            'cuota_id'    => $cuota_id,
            'socio_id'    => $cuota->socio_id,
            'importe'     => $cuota->importe,
            'gateway_id'  => $gateway_id,
            'estado'      => 'pendiente',
        ]);

        if (!$transaccion_id) {
            return [
                'success' => false,
                'error'   => __('Error al crear la transacción.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Procesar según el gateway
        if ($gateway_id === 'manual') {
            // Pago manual: solo registrar la intención
            return [
                'success'        => true,
                'tipo'           => 'manual',
                'transaccion_id' => $transaccion_id,
                'mensaje'        => __('Registra tu pago con la referencia proporcionada.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'referencia'     => 'CUOTA-' . $cuota_id . '-' . $transaccion_id,
                'datos_pago'     => $this->get_datos_pago_manual(),
            ];
        }

        // Procesar con gateway externo
        if ($gateway['clase']) {
            $resultado_gateway = $gateway['clase']->procesar($cuota, $transaccion_id, $datos);

            // Actualizar transacción con datos del gateway
            $this->actualizar_transaccion($transaccion_id, [
                'gateway_response' => wp_json_encode($resultado_gateway),
            ]);

            return $resultado_gateway;
        }

        return [
            'success' => false,
            'error'   => __('Gateway no implementado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Confirma un pago
     *
     * @param int    $transaccion_id ID de la transacción
     * @param string $referencia     Referencia de pago
     * @param array  $datos          Datos adicionales
     * @return array
     */
    public function confirmar_pago($transaccion_id, $referencia = '', $datos = []) {
        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_socios_transacciones';
        $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';

        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_transacciones WHERE id = %d",
            $transaccion_id
        ));

        if (!$transaccion) {
            return [
                'success' => false,
                'error'   => __('Transacción no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        if ($transaccion->estado === 'completada') {
            return [
                'success' => false,
                'error'   => __('Esta transacción ya está completada.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Actualizar transacción
        $wpdb->update(
            $tabla_transacciones,
            [
                'estado'            => 'completada',
                'referencia_pago'   => $referencia,
                'fecha_completado'  => current_time('mysql'),
                'datos_adicionales' => wp_json_encode($datos),
            ],
            ['id' => $transaccion_id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );

        // Actualizar cuota
        $wpdb->update(
            $tabla_cuotas,
            [
                'estado'          => 'pagada',
                'fecha_pago'      => current_time('mysql'),
                'metodo_pago'     => $transaccion->gateway_id,
                'referencia_pago' => $referencia,
            ],
            ['id' => $transaccion->cuota_id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );

        // Disparar acción para notificaciones
        do_action('flavor_socios_cuota_pagada', $transaccion->cuota_id, $transaccion->socio_id, $transaccion_id);

        return [
            'success' => true,
            'mensaje' => __('Pago confirmado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Crea una transacción
     *
     * @param array $datos Datos de la transacción
     * @return int|false ID de la transacción o false
     */
    private function crear_transaccion($datos) {
        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_socios_transacciones';

        $resultado = $wpdb->insert(
            $tabla_transacciones,
            [
                'cuota_id'       => $datos['cuota_id'],
                'socio_id'       => $datos['socio_id'],
                'importe'        => $datos['importe'],
                'gateway_id'     => $datos['gateway_id'],
                'estado'         => $datos['estado'] ?? 'pendiente',
                'fecha_creacion' => current_time('mysql'),
            ],
            ['%d', '%d', '%f', '%s', '%s', '%s']
        );

        return $resultado ? $wpdb->insert_id : false;
    }

    /**
     * Actualiza una transacción
     *
     * @param int   $transaccion_id ID de la transacción
     * @param array $datos          Datos a actualizar
     * @return bool
     */
    private function actualizar_transaccion($transaccion_id, $datos) {
        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_socios_transacciones';

        return $wpdb->update(
            $tabla_transacciones,
            $datos,
            ['id' => $transaccion_id]
        ) !== false;
    }

    /**
     * Obtiene datos para pago manual
     *
     * @return array
     */
    private function get_datos_pago_manual() {
        $opciones = get_option('flavor_socios_settings', []);

        return [
            'banco'      => $opciones['banco'] ?? '',
            'iban'       => $opciones['iban_cooperativa'] ?? '',
            'titular'    => $opciones['titular_cuenta'] ?? get_bloginfo('name'),
            'concepto'   => __('Cuota de socio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'bizum'      => $opciones['telefono_bizum'] ?? '',
        ];
    }

    /**
     * AJAX: Iniciar pago
     */
    public function ajax_iniciar_pago() {
        check_ajax_referer('flavor_socios_nonce', 'nonce');

        $cuota_id = absint($_POST['cuota_id'] ?? 0);
        $gateway_id = sanitize_text_field($_POST['gateway_id'] ?? 'manual');
        $datos = [];

        if (isset($_POST['datos'])) {
            $datos = array_map('sanitize_text_field', (array) $_POST['datos']);
        }

        $resultado = $this->procesar_pago($cuota_id, $gateway_id, $datos);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Confirmar pago
     */
    public function ajax_confirmar_pago() {
        check_ajax_referer('flavor_socios_nonce', 'nonce');

        $transaccion_id = absint($_POST['transaccion_id'] ?? 0);
        $referencia = sanitize_text_field($_POST['referencia'] ?? '');

        $resultado = $this->confirmar_pago($transaccion_id, $referencia);

        wp_send_json($resultado);
    }

    /**
     * AJAX: Verificar estado de pago
     */
    public function ajax_verificar_pago() {
        check_ajax_referer('flavor_socios_nonce', 'nonce');

        $transaccion_id = absint($_POST['transaccion_id'] ?? 0);

        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_socios_transacciones';

        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_transacciones WHERE id = %d",
            $transaccion_id
        ));

        if (!$transaccion) {
            wp_send_json(['success' => false, 'error' => __('Transacción no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        wp_send_json([
            'success' => true,
            'estado'  => $transaccion->estado,
            'pagada'  => $transaccion->estado === 'completada',
        ]);
    }

    /**
     * Registra rutas webhook para gateways
     */
    public function register_webhook_routes() {
        register_rest_route('flavor/v1', '/socios/webhook/(?P<gateway>[a-z]+)', [
            'methods'             => ['POST', 'GET'],
            'callback'            => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Maneja webhooks de gateways externos
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function handle_webhook($request) {
        $gateway_id = $request->get_param('gateway');
        $gateway = $this->get_gateway($gateway_id);

        if (!$gateway || !$gateway['clase']) {
            return new WP_REST_Response(['error' => 'Gateway not found'], 404);
        }

        return $gateway['clase']->handle_webhook($request);
    }

    /**
     * Crea la tabla de transacciones si no existe
     */
    public function maybe_create_transactions_table() {
        global $wpdb;
        $tabla_transacciones = $wpdb->prefix . 'flavor_socios_transacciones';

        if (Flavor_Platform_Helpers::tabla_existe($tabla_transacciones)) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $tabla_transacciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cuota_id bigint(20) unsigned NOT NULL,
            socio_id bigint(20) unsigned NOT NULL,
            importe decimal(10,2) NOT NULL,
            gateway_id varchar(50) NOT NULL,
            estado enum('pendiente','procesando','completada','fallida','cancelada') DEFAULT 'pendiente',
            referencia_pago varchar(255) DEFAULT NULL,
            gateway_response text DEFAULT NULL,
            datos_adicionales text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY cuota_id (cuota_id),
            KEY socio_id (socio_id),
            KEY estado (estado),
            KEY gateway_id (gateway_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
