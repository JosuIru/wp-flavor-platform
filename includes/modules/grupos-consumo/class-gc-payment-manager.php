<?php
/**
 * Gestor Central de Pagos para Grupos de Consumo
 *
 * Singleton que orquesta todas las pasarelas de pago,
 * procesa checkout y gestiona transacciones.
 *
 * @package FlavorChatIA
 * @subpackage Modules\GruposConsumo\Payments
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gestor de pagos
 *
 * @since 4.1.0
 */
class Flavor_GC_Payment_Manager {

    /**
     * Instancia singleton
     *
     * @var Flavor_GC_Payment_Manager|null
     */
    private static $instance = null;

    /**
     * Pasarelas registradas
     *
     * @var array<string, Flavor_GC_Payment_Gateway>
     */
    private $gateways = [];

    /**
     * Configuración general de pagos
     *
     * @var array
     */
    private $settings = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_GC_Payment_Manager
     */
    public static function get_instance(): Flavor_GC_Payment_Manager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->load_settings();
        $this->init_hooks();
    }

    /**
     * Carga la configuración
     *
     * @return void
     */
    private function load_settings(): void {
        $this->settings = get_option('flavor_gc_payment_settings', []);
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks(): void {
        // AJAX handlers
        add_action('wp_ajax_gc_process_checkout', [$this, 'ajax_process_checkout']);
        add_action('wp_ajax_gc_get_checkout_form', [$this, 'ajax_get_checkout_form']);

        // Webhooks de pasarelas
        add_action('wp_ajax_nopriv_gc_payment_webhook', [$this, 'handle_webhook']);
        add_action('wp_ajax_gc_payment_webhook', [$this, 'handle_webhook']);

        // Admin hooks
        if (is_admin()) {
            add_action('admin_init', [$this, 'register_settings']);
        }

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Registra una pasarela de pago
     *
     * @param Flavor_GC_Payment_Gateway $gateway Pasarela a registrar
     * @return bool
     */
    public function register_gateway(Flavor_GC_Payment_Gateway $gateway): bool {
        $gateway_id = $gateway->get_id();

        if (empty($gateway_id)) {
            return false;
        }

        $this->gateways[$gateway_id] = $gateway;
        return true;
    }

    /**
     * Obtiene una pasarela por ID
     *
     * @param string $gateway_id ID de la pasarela
     * @return Flavor_GC_Payment_Gateway|null
     */
    public function get_gateway(string $gateway_id): ?Flavor_GC_Payment_Gateway {
        return $this->gateways[$gateway_id] ?? null;
    }

    /**
     * Obtiene todas las pasarelas registradas
     *
     * @return array<string, Flavor_GC_Payment_Gateway>
     */
    public function get_all_gateways(): array {
        return $this->gateways;
    }

    /**
     * Obtiene pasarelas activas (habilitadas y que pueden activarse)
     *
     * @return array<string, Flavor_GC_Payment_Gateway>
     */
    public function get_active_gateways(): array {
        $active = [];

        foreach ($this->gateways as $id => $gateway) {
            if ($gateway->is_enabled()) {
                $active[$id] = $gateway;
            }
        }

        return $active;
    }

    /**
     * Procesa el checkout
     *
     * @param int $entrega_id ID de la entrega
     * @param string $gateway_id ID de la pasarela seleccionada
     * @param array $additional_data Datos adicionales del formulario
     * @return array Resultado del proceso
     */
    public function process_checkout(int $entrega_id, string $gateway_id, array $additional_data = []): array {
        // Validar pasarela
        $gateway = $this->get_gateway($gateway_id);

        if (!$gateway) {
            return [
                'success' => false,
                'error' => __('Pasarela de pago no encontrada.', 'flavor-platform'),
            ];
        }

        if (!$gateway->is_enabled()) {
            return [
                'success' => false,
                'error' => __('La pasarela de pago seleccionada no está disponible.', 'flavor-platform'),
            ];
        }

        // Obtener datos de la entrega
        global $wpdb;
        $tabla_entregas = $wpdb->prefix . 'flavor_gc_entregas';

        $entrega = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_entregas} WHERE id = %d",
            $entrega_id
        ));

        if (!$entrega) {
            return [
                'success' => false,
                'error' => __('Entrega no encontrada.', 'flavor-platform'),
            ];
        }

        // Verificar que la entrega pertenece al usuario actual
        if ((int) $entrega->usuario_id !== get_current_user_id()) {
            return [
                'success' => false,
                'error' => __('No tienes permisos para procesar esta entrega.', 'flavor-platform'),
            ];
        }

        // Verificar que la entrega está pendiente de pago
        if ($entrega->estado_pago === 'completado') {
            return [
                'success' => false,
                'error' => __('Esta entrega ya ha sido pagada.', 'flavor-platform'),
            ];
        }

        // Procesar pago con la pasarela
        $amount = (float) $entrega->total_final;

        try {
            $resultado = $gateway->process_payment($entrega_id, $amount);

            // Si el pago fue exitoso o requiere acción del cliente
            if (!empty($resultado['success']) || !empty($resultado['requires_action'])) {
                // Actualizar estado de la entrega si el pago fue completado
                if (!empty($resultado['success']) && empty($resultado['requires_action'])) {
                    $this->mark_entrega_paid($entrega_id, $gateway_id);
                }

                /**
                 * Acción después de procesar un checkout
                 *
                 * @param int $entrega_id ID de la entrega
                 * @param string $gateway_id ID de la pasarela
                 * @param array $resultado Resultado del proceso
                 */
                do_action('gc_checkout_processed', $entrega_id, $gateway_id, $resultado);
            }

            return $resultado;

        } catch (Exception $e) {
            flavor_log_error( 'Payment Error: ' . $e->getMessage(), 'GruposConsumo' );

            return [
                'success' => false,
                'error' => __('Error al procesar el pago. Por favor, inténtalo de nuevo.', 'flavor-platform'),
                'debug' => WP_DEBUG ? $e->getMessage() : null,
            ];
        }
    }

    /**
     * Marca una entrega como pagada
     *
     * @param int $entrega_id ID de la entrega
     * @param string $gateway_id ID de la pasarela usada
     * @return bool
     */
    public function mark_entrega_paid(int $entrega_id, string $gateway_id): bool {
        global $wpdb;

        $tabla_entregas = $wpdb->prefix . 'flavor_gc_entregas';

        $resultado = $wpdb->update(
            $tabla_entregas,
            [
                'estado_pago' => 'completado',
                'pasarela_pago' => $gateway_id,
                'fecha_pago' => current_time('mysql'),
            ],
            ['id' => $entrega_id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        if ($resultado !== false) {
            /**
             * Acción cuando una entrega es marcada como pagada
             *
             * @param int $entrega_id ID de la entrega
             * @param string $gateway_id ID de la pasarela
             */
            do_action('gc_entrega_paid', $entrega_id, $gateway_id);

            return true;
        }

        return false;
    }

    /**
     * Obtiene el resumen de una entrega para checkout
     *
     * @param int $entrega_id ID de la entrega
     * @return array|null
     */
    public function get_checkout_summary(int $entrega_id): ?array {
        global $wpdb;

        $tabla_entregas = $wpdb->prefix . 'flavor_gc_entregas';
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        $entrega = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, c.post_title as ciclo_nombre
             FROM {$tabla_entregas} e
             LEFT JOIN {$wpdb->posts} c ON e.ciclo_id = c.ID
             WHERE e.id = %d",
            $entrega_id
        ));

        if (!$entrega) {
            return null;
        }

        // Obtener items del pedido
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, prod.post_title as producto_nombre
             FROM {$tabla_pedidos} p
             LEFT JOIN {$wpdb->posts} prod ON p.producto_id = prod.ID
             WHERE p.entrega_id = %d",
            $entrega_id
        ));

        return [
            'entrega_id' => $entrega_id,
            'ciclo' => $entrega->ciclo_nombre,
            'subtotal' => (float) $entrega->subtotal,
            'descuento' => (float) ($entrega->descuento ?? 0),
            'total' => (float) $entrega->total_final,
            'moneda' => 'EUR',
            'estado' => $entrega->estado,
            'estado_pago' => $entrega->estado_pago ?? 'pendiente',
            'items' => array_map(function($item) {
                return [
                    'producto_id' => $item->producto_id,
                    'nombre' => $item->producto_nombre,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => (float) $item->precio_unitario,
                    'subtotal' => $item->cantidad * (float) $item->precio_unitario,
                ];
            }, $items),
            'pasarelas' => $this->get_active_gateways_for_display(),
        ];
    }

    /**
     * Obtiene las pasarelas activas formateadas para mostrar
     *
     * @return array
     */
    public function get_active_gateways_for_display(): array {
        $gateways = $this->get_active_gateways();
        $display = [];

        foreach ($gateways as $id => $gateway) {
            $display[] = [
                'id' => $id,
                'name' => $gateway->get_name(),
                'description' => $gateway->get_description(),
                'icon' => $gateway->get_icon(),
            ];
        }

        return $display;
    }

    /**
     * AJAX: Procesa el checkout
     *
     * @return void
     */
    public function ajax_process_checkout(): void {
        check_ajax_referer('gc_checkout_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-platform')]);
        }

        $entrega_id = absint($_POST['entrega_id'] ?? 0);
        $gateway_id = sanitize_key($_POST['gateway_id'] ?? '');

        if (!$entrega_id || !$gateway_id) {
            wp_send_json_error(['message' => __('Datos incompletos.', 'flavor-platform')]);
        }

        $resultado = $this->process_checkout($entrega_id, $gateway_id, $_POST);

        if (!empty($resultado['success'])) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado);
        }
    }

    /**
     * AJAX: Obtiene el formulario de checkout
     *
     * @return void
     */
    public function ajax_get_checkout_form(): void {
        check_ajax_referer('gc_checkout_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-platform')]);
        }

        $gateway_id = sanitize_key($_POST['gateway_id'] ?? '');
        $gateway = $this->get_gateway($gateway_id);

        if (!$gateway || !$gateway->is_enabled()) {
            wp_send_json_error(['message' => __('Pasarela no disponible.', 'flavor-platform')]);
        }

        ob_start();
        $gateway->render_checkout_fields();
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * Maneja webhooks de pasarelas externas
     *
     * @return void
     */
    public function handle_webhook(): void {
        $gateway_id = sanitize_key($_GET['gateway'] ?? $_POST['gateway'] ?? '');

        if (empty($gateway_id)) {
            wp_send_json_error(['message' => 'Gateway not specified'], 400);
        }

        $gateway = $this->get_gateway($gateway_id);

        if (!$gateway) {
            wp_send_json_error(['message' => 'Gateway not found'], 404);
        }

        if (method_exists($gateway, 'handle_webhook')) {
            $gateway->handle_webhook();
        } else {
            wp_send_json_error(['message' => 'Webhook not supported'], 501);
        }
    }

    /**
     * Registra configuración en el admin
     *
     * @return void
     */
    public function register_settings(): void {
        register_setting('flavor_gc_payments', 'flavor_gc_payment_settings', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);
    }

    /**
     * Sanitiza la configuración
     *
     * @param array $input Datos de entrada
     * @return array Datos sanitizados
     */
    public function sanitize_settings(array $input): array {
        $sanitized = [];

        foreach ($this->gateways as $id => $gateway) {
            if (isset($input[$id])) {
                $gateway_data = $gateway->validate_settings($input[$id]);

                if (!is_wp_error($gateway_data)) {
                    $sanitized[$id] = $gateway_data;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Registra rutas REST
     *
     * @return void
     */
    public function register_rest_routes(): void {
        register_rest_route('flavor-gc/v1', '/checkout/(?P<entrega_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_checkout'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
            'args' => [
                'entrega_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        register_rest_route('flavor-gc/v1', '/checkout/process', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_process_checkout'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);
    }

    /**
     * REST: Obtiene datos de checkout
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function rest_get_checkout(WP_REST_Request $request): WP_REST_Response {
        $entrega_id = $request->get_param('entrega_id');
        $summary = $this->get_checkout_summary($entrega_id);

        if (!$summary) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Entrega no encontrada.', 'flavor-platform'),
            ], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $summary,
        ], 200);
    }

    /**
     * REST: Procesa checkout
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function rest_process_checkout(WP_REST_Request $request): WP_REST_Response {
        $entrega_id = absint($request->get_param('entrega_id'));
        $gateway_id = sanitize_key($request->get_param('gateway_id'));

        if (!$entrega_id || !$gateway_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Datos incompletos.', 'flavor-platform'),
            ], 400);
        }

        $resultado = $this->process_checkout($entrega_id, $gateway_id, $request->get_params());

        $status_code = !empty($resultado['success']) ? 200 : 400;

        return new WP_REST_Response($resultado, $status_code);
    }

    /**
     * Obtiene el total de transacciones por estado
     *
     * @param string|null $gateway_id Filtrar por pasarela
     * @return array
     */
    public function get_transaction_stats(?string $gateway_id = null): array {
        global $wpdb;

        $tabla_pagos = $wpdb->prefix . 'flavor_gc_pagos';

        $where = '';
        if ($gateway_id) {
            $where = $wpdb->prepare(" AND pasarela = %s", $gateway_id);
        }

        $stats = $wpdb->get_results(
            "SELECT estado, COUNT(*) as cantidad, SUM(importe) as total
             FROM {$tabla_pagos}
             WHERE 1=1 {$where}
             GROUP BY estado"
        );

        $resultado = [
            'pendiente' => ['cantidad' => 0, 'total' => 0],
            'procesando' => ['cantidad' => 0, 'total' => 0],
            'completado' => ['cantidad' => 0, 'total' => 0],
            'fallido' => ['cantidad' => 0, 'total' => 0],
            'reembolsado' => ['cantidad' => 0, 'total' => 0],
        ];

        foreach ($stats as $row) {
            $resultado[$row->estado] = [
                'cantidad' => (int) $row->cantidad,
                'total' => (float) $row->total,
            ];
        }

        return $resultado;
    }
}

/**
 * Helper para obtener el gestor de pagos
 *
 * @return Flavor_GC_Payment_Manager
 */
function flavor_gc_payments(): Flavor_GC_Payment_Manager {
    return Flavor_GC_Payment_Manager::get_instance();
}
