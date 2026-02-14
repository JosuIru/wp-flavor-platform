<?php
/**
 * Gateway WooCommerce para módulo Socios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gateway que usa WooCommerce para procesar pagos de cuotas
 */
class Flavor_Socios_Gateway_WooCommerce {

    /**
     * ID del gateway
     *
     * @var string
     */
    public $id = 'woocommerce';

    /**
     * ID del producto WooCommerce para cuotas
     *
     * @var int
     */
    private $producto_cuota_id;

    /**
     * Constructor
     */
    public function __construct() {
        $opciones = get_option('flavor_socios_woo_settings', []);
        $this->producto_cuota_id = absint($opciones['producto_cuota_id'] ?? 0);

        // Hooks de WooCommerce
        add_action('woocommerce_thankyou', [$this, 'on_order_complete'], 10, 1);
        add_action('woocommerce_order_status_completed', [$this, 'on_order_status_completed'], 10, 1);
    }

    /**
     * Verifica si el gateway está configurado
     *
     * @return bool
     */
    public function is_configured() {
        return class_exists('WooCommerce') && $this->producto_cuota_id > 0;
    }

    /**
     * Procesa un pago creando una orden de WooCommerce
     *
     * @param object $cuota          Datos de la cuota
     * @param int    $transaccion_id ID de transacción interna
     * @param array  $datos          Datos adicionales
     * @return array
     */
    public function procesar($cuota, $transaccion_id, $datos = []) {
        if (!$this->is_configured()) {
            return [
                'success' => false,
                'error'   => __('WooCommerce no está configurado.', 'flavor-chat-ia'),
            ];
        }

        try {
            // Obtener datos del socio
            global $wpdb;
            $tabla_socios = $wpdb->prefix . 'flavor_socios';
            $socio = $wpdb->get_row($wpdb->prepare(
                "SELECT s.*, u.user_email, u.display_name
                 FROM $tabla_socios s
                 LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
                 WHERE s.id = %d",
                $cuota->socio_id
            ));

            if (!$socio) {
                return [
                    'success' => false,
                    'error'   => __('Socio no encontrado.', 'flavor-chat-ia'),
                ];
            }

            // Vaciar carrito actual
            WC()->cart->empty_cart();

            // Crear producto variable o usar el existente
            $producto_id = $this->obtener_o_crear_producto_cuota();

            if (!$producto_id) {
                return [
                    'success' => false,
                    'error'   => __('No se pudo crear el producto de cuota.', 'flavor-chat-ia'),
                ];
            }

            // Añadir al carrito con precio dinámico
            $clave_carrito = WC()->cart->add_to_cart($producto_id, 1, 0, [], [
                'flavor_cuota_id'       => $cuota->id,
                'flavor_transaccion_id' => $transaccion_id,
                'flavor_socio_id'       => $cuota->socio_id,
                'flavor_importe'        => $cuota->importe,
                'flavor_periodo'        => $cuota->periodo,
            ]);

            if (!$clave_carrito) {
                return [
                    'success' => false,
                    'error'   => __('Error al añadir al carrito.', 'flavor-chat-ia'),
                ];
            }

            // Hook para modificar precio del item
            add_filter('woocommerce_cart_item_price', [$this, 'modificar_precio_item'], 10, 3);

            return [
                'success'        => true,
                'tipo'           => 'redirect',
                'checkout_url'   => wc_get_checkout_url(),
                'transaccion_id' => $transaccion_id,
            ];

        } catch (Exception $excepcion) {
            return [
                'success' => false,
                'error'   => $excepcion->getMessage(),
            ];
        }
    }

    /**
     * Obtiene o crea el producto de cuota de WooCommerce
     *
     * @return int ID del producto
     */
    private function obtener_o_crear_producto_cuota() {
        if ($this->producto_cuota_id && get_post($this->producto_cuota_id)) {
            return $this->producto_cuota_id;
        }

        // Crear producto
        $producto = new WC_Product_Simple();
        $producto->set_name(__('Cuota de Socio', 'flavor-chat-ia'));
        $producto->set_status('private');
        $producto->set_catalog_visibility('hidden');
        $producto->set_price(0);
        $producto->set_regular_price(0);
        $producto->set_sold_individually(true);
        $producto->set_virtual(true);
        $producto->save();

        $producto_id = $producto->get_id();

        // Guardar ID
        $opciones = get_option('flavor_socios_woo_settings', []);
        $opciones['producto_cuota_id'] = $producto_id;
        update_option('flavor_socios_woo_settings', $opciones);
        $this->producto_cuota_id = $producto_id;

        return $producto_id;
    }

    /**
     * Modifica el precio del item en carrito
     *
     * @param string $precio      Precio formateado
     * @param array  $item        Item del carrito
     * @param string $clave_item  Clave del item
     * @return string
     */
    public function modificar_precio_item($precio, $item, $clave_item) {
        if (isset($item['flavor_importe'])) {
            return wc_price($item['flavor_importe']);
        }
        return $precio;
    }

    /**
     * Hook para modificar el total del carrito
     */
    public function modificar_total_carrito() {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach (WC()->cart->get_cart() as $clave => $item) {
            if (isset($item['flavor_importe'])) {
                $item['data']->set_price($item['flavor_importe']);
            }
        }
    }

    /**
     * Hook cuando se completa un pedido
     *
     * @param int $order_id ID del pedido
     */
    public function on_order_complete($order_id) {
        $this->procesar_orden_completada($order_id);
    }

    /**
     * Hook cuando el estado cambia a completado
     *
     * @param int $order_id ID del pedido
     */
    public function on_order_status_completed($order_id) {
        $this->procesar_orden_completada($order_id);
    }

    /**
     * Procesa una orden completada
     *
     * @param int $order_id ID del pedido
     */
    private function procesar_orden_completada($order_id) {
        $orden = wc_get_order($order_id);

        if (!$orden) {
            return;
        }

        // Ya procesado?
        if ($orden->get_meta('_flavor_cuota_procesada')) {
            return;
        }

        foreach ($orden->get_items() as $item) {
            $transaccion_id = $item->get_meta('_flavor_transaccion_id');
            $cuota_id = $item->get_meta('_flavor_cuota_id');

            if ($transaccion_id && $cuota_id) {
                // Confirmar pago
                $payment_manager = Flavor_Socios_Payment_Manager::get_instance();
                $payment_manager->confirmar_pago(
                    $transaccion_id,
                    'WOO-' . $order_id,
                    [
                        'woo_order_id'     => $order_id,
                        'woo_payment_method' => $orden->get_payment_method(),
                    ]
                );

                // Marcar como procesado
                $orden->update_meta_data('_flavor_cuota_procesada', 1);
                $orden->save();
            }
        }
    }

    /**
     * Maneja webhooks (no usado directamente con WooCommerce)
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function handle_webhook($request) {
        return new WP_REST_Response(['message' => 'WooCommerce uses its own hooks'], 200);
    }

    /**
     * Guarda metadatos de cuota en items del pedido
     */
    public function guardar_meta_item_pedido($item, $cart_item_key, $values, $order) {
        if (isset($values['flavor_cuota_id'])) {
            $item->add_meta_data('_flavor_cuota_id', $values['flavor_cuota_id']);
        }
        if (isset($values['flavor_transaccion_id'])) {
            $item->add_meta_data('_flavor_transaccion_id', $values['flavor_transaccion_id']);
        }
        if (isset($values['flavor_socio_id'])) {
            $item->add_meta_data('_flavor_socio_id', $values['flavor_socio_id']);
        }
        if (isset($values['flavor_periodo'])) {
            $item->add_meta_data('_flavor_periodo', $values['flavor_periodo']);
        }
    }
}

// Hooks de WooCommerce
add_action('woocommerce_checkout_create_order_line_item', function($item, $cart_item_key, $values, $order) {
    if (isset($values['flavor_cuota_id'])) {
        $item->add_meta_data('_flavor_cuota_id', $values['flavor_cuota_id']);
    }
    if (isset($values['flavor_transaccion_id'])) {
        $item->add_meta_data('_flavor_transaccion_id', $values['flavor_transaccion_id']);
    }
    if (isset($values['flavor_socio_id'])) {
        $item->add_meta_data('_flavor_socio_id', $values['flavor_socio_id']);
    }
    if (isset($values['flavor_periodo'])) {
        $item->add_meta_data('_flavor_periodo', $values['flavor_periodo']);
    }
}, 10, 4);

// Modificar precio en carrito
add_action('woocommerce_before_calculate_totals', function($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    foreach ($cart->get_cart() as $item) {
        if (isset($item['flavor_importe'])) {
            $item['data']->set_price($item['flavor_importe']);
        }
    }
}, 10, 1);
