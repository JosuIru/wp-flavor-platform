<?php
/**
 * Pasarela WooCommerce para Grupos de Consumo
 *
 * Usa el sistema de checkout de WooCommerce para procesar pagos.
 * Requiere WooCommerce activo.
 *
 * @package FlavorChatIA
 * @subpackage Modules\GruposConsumo\Payments
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pasarela WooCommerce
 *
 * @since 4.1.0
 */
class Flavor_GC_Gateway_WooCommerce extends Flavor_GC_Payment_Gateway {

    /**
     * ID de la pasarela
     *
     * @var string
     */
    protected $id = 'woocommerce';

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = __('Checkout WooCommerce', 'flavor-chat-ia');
        $this->description = __('Usa tu método de pago favorito a través de WooCommerce.', 'flavor-chat-ia');
        $this->icon = 'dashicons-cart';

        parent::__construct();
    }

    /**
     * Obtiene el ID de la pasarela
     *
     * @return string
     */
    public function get_id(): string {
        return $this->id;
    }

    /**
     * Obtiene el nombre de la pasarela
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Verifica si la pasarela puede activarse
     *
     * @return bool
     */
    public function can_activate(): bool {
        // WooCommerce debe estar activo
        if (!class_exists('WooCommerce')) {
            return false;
        }

        // Debe existir el producto virtual configurado
        $product_id = $this->get_setting('product_id', 0);
        if ($product_id) {
            $product = wc_get_product($product_id);
            if (!$product) {
                return false;
            }
        }

        return true;
    }

    /**
     * Inicializa hooks específicos
     *
     * @return void
     */
    protected function init_hooks(): void {
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Agregar datos de GC al pedido WC
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_gc_data_to_item'], 10, 4);

        // Manejar pedido completado
        add_action('woocommerce_order_status_completed', [$this, 'handle_order_completed']);
        add_action('woocommerce_order_status_processing', [$this, 'handle_order_processing']);

        // Manejar reembolsos de WC
        add_action('woocommerce_order_refunded', [$this, 'handle_order_refunded'], 10, 2);
    }

    /**
     * Procesa el pago
     *
     * @param int $entrega_id ID de la entrega
     * @param float $amount Monto del pedido
     * @return array Resultado del proceso
     */
    public function process_payment(int $entrega_id, float $amount): array {
        if (!class_exists('WooCommerce')) {
            return [
                'success' => false,
                'error' => __('WooCommerce no está instalado.', 'flavor-chat-ia'),
            ];
        }

        // Obtener o crear producto virtual para GC
        $product_id = $this->get_or_create_gc_product();

        if (!$product_id) {
            return [
                'success' => false,
                'error' => __('Error al configurar el producto de pago.', 'flavor-chat-ia'),
            ];
        }

        // Vaciar carrito actual
        WC()->cart->empty_cart();

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
                'error' => __('Entrega no encontrada.', 'flavor-chat-ia'),
            ];
        }

        // Agregar al carrito con precio personalizado
        $cart_item_data = [
            'gc_entrega_id' => $entrega_id,
            'gc_amount' => $amount,
            'gc_ciclo_id' => $entrega->ciclo_id,
        ];

        $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);

        if (!$cart_item_key) {
            return [
                'success' => false,
                'error' => __('Error al agregar al carrito.', 'flavor-chat-ia'),
            ];
        }

        // Registrar transacción
        $transaction_id = $this->log_transaction($entrega_id, 'procesando', [
            'amount' => $amount,
            'currency' => 'EUR',
            'cart_item_key' => $cart_item_key,
            'product_id' => $product_id,
        ]);

        // Guardar referencia en sesión
        WC()->session->set('gc_entrega_id', $entrega_id);
        WC()->session->set('gc_transaction_id', $transaction_id);

        return [
            'success' => true,
            'requires_action' => true,
            'redirect_url' => wc_get_checkout_url(),
            'transaction_id' => $transaction_id,
        ];
    }

    /**
     * Obtiene o crea el producto virtual para pagos de GC
     *
     * @return int|null ID del producto
     */
    private function get_or_create_gc_product(): ?int {
        // Buscar producto existente
        $product_id = $this->get_setting('product_id', 0);

        if ($product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                return $product_id;
            }
        }

        // Crear producto virtual
        $product = new WC_Product_Simple();
        $product->set_name(__('Pedido Grupos de Consumo', 'flavor-chat-ia'));
        $product->set_status('private');
        $product->set_catalog_visibility('hidden');
        $product->set_virtual(true);
        $product->set_sold_individually(true);
        $product->set_price(0);
        $product->set_regular_price(0);

        $product_id = $product->save();

        if ($product_id) {
            // Guardar ID del producto
            $settings = get_option('flavor_gc_payment_settings', []);
            $settings[$this->id]['product_id'] = $product_id;
            update_option('flavor_gc_payment_settings', $settings);

            return $product_id;
        }

        return null;
    }

    /**
     * Agrega datos de GC al item del pedido
     *
     * @param WC_Order_Item_Product $item Item del pedido
     * @param string $cart_item_key Clave del item en el carrito
     * @param array $values Valores del carrito
     * @param WC_Order $order Pedido
     * @return void
     */
    public function add_gc_data_to_item($item, $cart_item_key, $values, $order): void {
        if (!empty($values['gc_entrega_id'])) {
            $item->add_meta_data('_gc_entrega_id', $values['gc_entrega_id'], true);
            $item->add_meta_data('_gc_amount', $values['gc_amount'], true);
            $item->add_meta_data('_gc_ciclo_id', $values['gc_ciclo_id'], true);

            // Establecer precio personalizado
            $item->set_subtotal($values['gc_amount']);
            $item->set_total($values['gc_amount']);
        }
    }

    /**
     * Maneja pedido WC procesando
     *
     * @param int $order_id ID del pedido WC
     * @return void
     */
    public function handle_order_processing(int $order_id): void {
        $this->sync_gc_payment_status($order_id, 'procesando');
    }

    /**
     * Maneja pedido WC completado
     *
     * @param int $order_id ID del pedido WC
     * @return void
     */
    public function handle_order_completed(int $order_id): void {
        $this->sync_gc_payment_status($order_id, 'completado');
    }

    /**
     * Sincroniza el estado del pago de GC con WC
     *
     * @param int $order_id ID del pedido WC
     * @param string $status Estado a establecer
     * @return void
     */
    private function sync_gc_payment_status(int $order_id, string $status): void {
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $entrega_id = $item->get_meta('_gc_entrega_id');

            if (!$entrega_id) {
                continue;
            }

            global $wpdb;
            $tabla_pagos = $wpdb->prefix . 'flavor_gc_pagos';

            // Buscar transacción
            $transaccion = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$tabla_pagos}
                 WHERE entrega_id = %d AND pasarela = 'woocommerce' AND estado IN ('procesando', 'pendiente')",
                $entrega_id
            ));

            if ($transaccion) {
                $this->update_transaction($transaccion->id, $status, [
                    'wc_order_id' => $order_id,
                    'transaction_id' => $order->get_transaction_id(),
                ]);
            }

            // Si está completado, marcar entrega como pagada
            if ($status === 'completado') {
                flavor_gc_payments()->mark_entrega_paid($entrega_id, $this->id);
            }
        }
    }

    /**
     * Maneja reembolsos de WC
     *
     * @param int $order_id ID del pedido
     * @param int $refund_id ID del reembolso
     * @return void
     */
    public function handle_order_refunded(int $order_id, int $refund_id): void {
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $entrega_id = $item->get_meta('_gc_entrega_id');

            if (!$entrega_id) {
                continue;
            }

            global $wpdb;
            $tabla_pagos = $wpdb->prefix . 'flavor_gc_pagos';

            $transaccion = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$tabla_pagos}
                 WHERE entrega_id = %d AND pasarela = 'woocommerce' AND estado = 'completado'",
                $entrega_id
            ));

            if ($transaccion) {
                $refund = wc_get_order($refund_id);
                $refund_amount = $refund ? abs($refund->get_total()) : 0;

                $this->update_transaction($transaccion->id, 'reembolsado', [
                    'wc_refund_id' => $refund_id,
                    'refund_amount' => $refund_amount,
                ]);
            }
        }
    }

    /**
     * Renderiza los campos del checkout
     *
     * @return void
     */
    public function render_checkout_fields(): void {
        if (!class_exists('WooCommerce')) {
            echo '<p class="gc-wc-error">' . esc_html__('WooCommerce no está disponible.', 'flavor-chat-ia') . '</p>';
            return;
        }

        // Obtener pasarelas de pago disponibles en WC
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

        if (empty($available_gateways)) {
            echo '<p class="gc-wc-error">' . esc_html__('No hay métodos de pago configurados en WooCommerce.', 'flavor-chat-ia') . '</p>';
            return;
        }

        ?>
        <div class="gc-gateway-wc-wrapper">
            <p class="gc-wc-message">
                <span class="dashicons dashicons-cart" aria-hidden="true"></span>
                <?php esc_html_e('Serás redirigido al checkout de WooCommerce donde podrás elegir tu método de pago preferido.', 'flavor-chat-ia'); ?>
            </p>

            <div class="gc-wc-available-methods">
                <strong><?php esc_html_e('Métodos de pago disponibles:', 'flavor-chat-ia'); ?></strong>
                <ul>
                    <?php foreach ($available_gateways as $gateway) : ?>
                    <li>
                        <?php if ($gateway->icon) : ?>
                        <img src="<?php echo esc_url($gateway->icon); ?>" alt="<?php echo esc_attr($gateway->get_title()); ?>" height="24">
                        <?php endif; ?>
                        <?php echo esc_html($gateway->get_title()); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <style>
        .gc-gateway-wc-wrapper {
            margin: 1rem 0;
        }
        .gc-wc-message {
            padding: 15px;
            background: #f0f6fc;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .gc-wc-available-methods ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .gc-wc-available-methods li {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }
        .gc-wc-error {
            color: #dc3232;
            padding: 10px;
            background: #fcf0f0;
            border-radius: 4px;
        }
        </style>
        <?php
    }

    /**
     * Obtiene los campos de configuración para el admin
     *
     * @return array
     */
    public function get_admin_fields(): array {
        $fields = [
            [
                'id' => 'enabled',
                'type' => 'checkbox',
                'label' => __('Habilitar pago por WooCommerce', 'flavor-chat-ia'),
                'default' => false,
            ],
        ];

        if (!class_exists('WooCommerce')) {
            $fields[] = [
                'id' => 'wc_notice',
                'type' => 'notice',
                'content' => __('WooCommerce no está instalado. Esta pasarela requiere WooCommerce activo.', 'flavor-chat-ia'),
                'level' => 'warning',
            ];
        }

        return $fields;
    }

    /**
     * Procesa un reembolso
     *
     * @param int $transaction_id ID de la transacción
     * @param float $amount Monto a reembolsar
     * @param string $reason Motivo del reembolso
     * @return array
     */
    public function process_refund(int $transaction_id, float $amount, string $reason = ''): array {
        if (!class_exists('WooCommerce')) {
            return [
                'success' => false,
                'error' => __('WooCommerce no está disponible.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_pagos = $wpdb->prefix . 'flavor_gc_pagos';

        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_pagos} WHERE id = %d AND pasarela = 'woocommerce'",
            $transaction_id
        ));

        if (!$transaccion) {
            return [
                'success' => false,
                'error' => __('Transacción no encontrada.', 'flavor-chat-ia'),
            ];
        }

        $datos = json_decode($transaccion->datos_pasarela, true);
        $wc_order_id = $datos['wc_order_id'] ?? 0;

        if (!$wc_order_id) {
            return [
                'success' => false,
                'error' => __('No se encontró el pedido de WooCommerce asociado.', 'flavor-chat-ia'),
            ];
        }

        $order = wc_get_order($wc_order_id);

        if (!$order) {
            return [
                'success' => false,
                'error' => __('Pedido de WooCommerce no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Crear reembolso en WC
        $refund = wc_create_refund([
            'amount' => $amount,
            'reason' => $reason,
            'order_id' => $wc_order_id,
            'refund_payment' => true,
        ]);

        if (is_wp_error($refund)) {
            return [
                'success' => false,
                'error' => $refund->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'refund_id' => $refund->get_id(),
            'message' => __('Reembolso procesado a través de WooCommerce.', 'flavor-chat-ia'),
        ];
    }
}
