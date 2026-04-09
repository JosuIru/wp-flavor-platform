<?php
/**
 * Pasarela de Pago en Recogida para Grupos de Consumo
 *
 * Permite confirmar el pago al momento de recoger el pedido.
 *
 * @package FlavorChatIA
 * @subpackage Modules\GruposConsumo\Payments
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pasarela de pago en recogida
 *
 * @since 4.1.0
 */
class Flavor_GC_Gateway_Pickup extends Flavor_GC_Payment_Gateway {

    /**
     * ID de la pasarela
     *
     * @var string
     */
    protected $id = 'pickup';

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = __('Pago en recogida', 'flavor-platform');
        $this->description = __('Paga cuando recojas tu pedido.', 'flavor-platform');
        $this->icon = 'dashicons-store';

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
     * @return bool Siempre true, no requiere configuración externa
     */
    public function can_activate(): bool {
        return true;
    }

    /**
     * Procesa el pago
     *
     * @param int $entrega_id ID de la entrega
     * @param float $amount Monto del pedido
     * @return array Resultado del proceso
     */
    public function process_payment(int $entrega_id, float $amount): array {
        // Registrar transacción como pendiente de pago en persona
        $transaction_id = $this->log_transaction($entrega_id, 'pendiente', [
            'amount' => $amount,
            'currency' => 'EUR',
            'tipo' => 'pago_recogida',
            'fecha_registro' => current_time('mysql'),
        ]);

        if (!$transaction_id) {
            return [
                'success' => false,
                'error' => __('Error al registrar el pedido.', 'flavor-platform'),
            ];
        }

        // Actualizar estado de la entrega a confirmada pero pendiente de pago
        global $wpdb;
        $tabla_entregas = $wpdb->prefix . 'flavor_gc_entregas';

        $wpdb->update(
            $tabla_entregas,
            [
                'estado' => 'confirmada',
                'estado_pago' => 'pendiente_recogida',
                'pasarela_pago' => $this->id,
            ],
            ['id' => $entrega_id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        /**
         * Acción cuando se confirma un pedido con pago en recogida
         *
         * @param int $entrega_id ID de la entrega
         * @param int $transaction_id ID de la transacción
         */
        do_action('gc_pickup_order_confirmed', $entrega_id, $transaction_id);

        return [
            'success' => true,
            'message' => __('Pedido confirmado. Recuerda llevar el pago exacto cuando vayas a recogerlo.', 'flavor-platform'),
            'transaction_id' => $transaction_id,
            'redirect_url' => Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mis-pedidos'),
        ];
    }

    /**
     * Renderiza los campos del checkout
     *
     * @return void
     */
    public function render_checkout_fields(): void {
        $instrucciones = $this->get_setting('instrucciones', '');

        if (empty($instrucciones)) {
            $instrucciones = __('El pago se realizará al momento de recoger tu pedido. Por favor, lleva el importe exacto.', 'flavor-platform');
        }
        ?>
        <div class="gc-gateway-pickup-info">
            <p class="gc-pickup-message">
                <span class="dashicons dashicons-store" aria-hidden="true"></span>
                <?php echo esc_html($instrucciones); ?>
            </p>

            <?php
            $punto_recogida = $this->get_setting('punto_recogida', '');
            if ($punto_recogida) :
            ?>
            <div class="gc-pickup-location">
                <strong><?php esc_html_e('Punto de recogida:', 'flavor-platform'); ?></strong>
                <p><?php echo esc_html($punto_recogida); ?></p>
            </div>
            <?php endif; ?>

            <div class="gc-pickup-accepted-methods">
                <strong><?php esc_html_e('Formas de pago aceptadas:', 'flavor-platform'); ?></strong>
                <ul>
                    <?php if ($this->get_setting('acepta_efectivo', true)) : ?>
                    <li><span class="dashicons dashicons-money-alt"></span> <?php esc_html_e('Efectivo', 'flavor-platform'); ?></li>
                    <?php endif; ?>
                    <?php if ($this->get_setting('acepta_bizum', false)) : ?>
                    <li><span class="dashicons dashicons-smartphone"></span> <?php esc_html_e('Bizum', 'flavor-platform'); ?></li>
                    <?php endif; ?>
                    <?php if ($this->get_setting('acepta_transferencia', false)) : ?>
                    <li><span class="dashicons dashicons-bank"></span> <?php esc_html_e('Transferencia bancaria', 'flavor-platform'); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene los campos de configuración para el admin
     *
     * @return array
     */
    public function get_admin_fields(): array {
        return [
            [
                'id' => 'enabled',
                'type' => 'checkbox',
                'label' => __('Habilitar pago en recogida', 'flavor-platform'),
                'default' => true,
            ],
            [
                'id' => 'instrucciones',
                'type' => 'textarea',
                'label' => __('Instrucciones', 'flavor-platform'),
                'description' => __('Mensaje que verán los usuarios al seleccionar este método de pago.', 'flavor-platform'),
                'default' => __('El pago se realizará al momento de recoger tu pedido. Por favor, lleva el importe exacto.', 'flavor-platform'),
            ],
            [
                'id' => 'punto_recogida',
                'type' => 'text',
                'label' => __('Punto de recogida', 'flavor-platform'),
                'description' => __('Dirección o descripción del punto de recogida.', 'flavor-platform'),
            ],
            [
                'id' => 'acepta_efectivo',
                'type' => 'checkbox',
                'label' => __('Aceptar efectivo', 'flavor-platform'),
                'default' => true,
            ],
            [
                'id' => 'acepta_bizum',
                'type' => 'checkbox',
                'label' => __('Aceptar Bizum', 'flavor-platform'),
                'default' => false,
            ],
            [
                'id' => 'acepta_transferencia',
                'type' => 'checkbox',
                'label' => __('Aceptar transferencia bancaria', 'flavor-platform'),
                'default' => false,
            ],
        ];
    }

    /**
     * Confirma el pago de una entrega (llamado por admin)
     *
     * @param int $entrega_id ID de la entrega
     * @param string $metodo_pago Método usado (efectivo, bizum, transferencia)
     * @return bool
     */
    public function confirm_payment(int $entrega_id, string $metodo_pago = 'efectivo'): bool {
        global $wpdb;

        // Buscar la transacción
        $tabla_pagos = $wpdb->prefix . 'flavor_gc_pagos';
        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$tabla_pagos}
             WHERE entrega_id = %d AND pasarela = %s AND estado = 'pendiente'
             ORDER BY fecha_creacion DESC LIMIT 1",
            $entrega_id,
            $this->id
        ));

        if (!$transaccion) {
            return false;
        }

        // Actualizar transacción
        $this->update_transaction($transaccion->id, 'completado', [
            'metodo_pago' => $metodo_pago,
            'confirmado_por' => get_current_user_id(),
            'fecha_confirmacion' => current_time('mysql'),
        ]);

        // Marcar entrega como pagada
        flavor_gc_payments()->mark_entrega_paid($entrega_id, $this->id);

        return true;
    }
}
