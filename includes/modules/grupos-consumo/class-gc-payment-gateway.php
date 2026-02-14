<?php
/**
 * Clase abstracta base para pasarelas de pago de Grupos de Consumo
 *
 * Todas las pasarelas de pago deben extender esta clase.
 *
 * @package FlavorChatIA
 * @subpackage Modules\GruposConsumo\Payments
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase abstracta para pasarelas de pago
 *
 * @since 4.1.0
 */
abstract class Flavor_GC_Payment_Gateway {

    /**
     * ID único de la pasarela
     *
     * @var string
     */
    protected $id;

    /**
     * Nombre visible de la pasarela
     *
     * @var string
     */
    protected $name;

    /**
     * Descripción de la pasarela
     *
     * @var string
     */
    protected $description;

    /**
     * Icono de la pasarela
     *
     * @var string
     */
    protected $icon = 'dashicons-money-alt';

    /**
     * Si la pasarela está habilitada
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * Configuración de la pasarela
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->load_settings();
        $this->init_hooks();
    }

    /**
     * Carga la configuración de la pasarela
     *
     * @return void
     */
    protected function load_settings(): void {
        $all_settings = get_option('flavor_gc_payment_settings', []);
        $gateway_id = $this->get_id();

        $this->settings = $all_settings[$gateway_id] ?? [];
        $this->enabled = !empty($this->settings['enabled']);
    }

    /**
     * Inicializa hooks específicos de la pasarela
     *
     * @return void
     */
    protected function init_hooks(): void {
        // Las clases hijas pueden sobrescribir esto
    }

    /**
     * Obtiene el ID único de la pasarela
     *
     * @return string
     */
    abstract public function get_id(): string;

    /**
     * Obtiene el nombre de la pasarela
     *
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * Verifica si la pasarela puede activarse
     *
     * @return bool
     */
    abstract public function can_activate(): bool;

    /**
     * Procesa un pago
     *
     * @param int $entrega_id ID de la entrega
     * @param float $amount Monto a cobrar
     * @return array Resultado del proceso
     */
    abstract public function process_payment(int $entrega_id, float $amount): array;

    /**
     * Renderiza los campos del checkout
     *
     * @return void
     */
    abstract public function render_checkout_fields(): void;

    /**
     * Obtiene la descripción de la pasarela
     *
     * @return string
     */
    public function get_description(): string {
        return $this->description ?? '';
    }

    /**
     * Obtiene el icono de la pasarela
     *
     * @return string
     */
    public function get_icon(): string {
        return $this->icon;
    }

    /**
     * Verifica si la pasarela está habilitada
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return $this->enabled && $this->can_activate();
    }

    /**
     * Obtiene un valor de configuración
     *
     * @param string $key Clave de configuración
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    public function get_setting(string $key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Verifica si está en modo sandbox/test
     *
     * @return bool
     */
    public function is_sandbox(): bool {
        return (bool) $this->get_setting('sandbox', true);
    }

    /**
     * Registra una transacción en la base de datos
     *
     * @param int $entrega_id ID de la entrega
     * @param string $status Estado del pago
     * @param array $data Datos adicionales
     * @return int|false ID de la transacción o false en error
     */
    public function log_transaction(int $entrega_id, string $status, array $data = []) {
        global $wpdb;

        $tabla_pagos = $wpdb->prefix . 'flavor_gc_pagos';

        // Verificar que la tabla existe
        $tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_pagos)) === $tabla_pagos;

        if (!$tabla_existe) {
            return false;
        }

        // Obtener datos de la entrega
        $tabla_entregas = $wpdb->prefix . 'flavor_gc_entregas';
        $entrega = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_entregas} WHERE id = %d",
            $entrega_id
        ));

        $resultado = $wpdb->insert(
            $tabla_pagos,
            [
                'entrega_id' => $entrega_id,
                'usuario_id' => get_current_user_id(),
                'ciclo_id' => $entrega ? $entrega->ciclo_id : 0,
                'pasarela' => $this->get_id(),
                'transaction_id' => $data['transaction_id'] ?? null,
                'importe' => $data['amount'] ?? 0,
                'moneda' => $data['currency'] ?? 'EUR',
                'estado' => $status,
                'datos_pasarela' => wp_json_encode($data),
                'ip_cliente' => $this->get_client_ip(),
                'fecha_creacion' => current_time('mysql'),
                'fecha_actualizacion' => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($resultado) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Actualiza el estado de una transacción
     *
     * @param int $transaction_id ID de la transacción
     * @param string $status Nuevo estado
     * @param array $data Datos adicionales
     * @return bool
     */
    public function update_transaction(int $transaction_id, string $status, array $data = []): bool {
        global $wpdb;

        $tabla_pagos = $wpdb->prefix . 'flavor_gc_pagos';

        $update_data = [
            'estado' => $status,
            'fecha_actualizacion' => current_time('mysql'),
        ];

        if (!empty($data['transaction_id'])) {
            $update_data['transaction_id'] = $data['transaction_id'];
        }

        if (!empty($data)) {
            // Obtener datos existentes
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT datos_pasarela FROM {$tabla_pagos} WHERE id = %d",
                $transaction_id
            ));

            $existing_data = json_decode($existing, true) ?: [];
            $merged_data = array_merge($existing_data, $data);
            $update_data['datos_pasarela'] = wp_json_encode($merged_data);
        }

        $resultado = $wpdb->update(
            $tabla_pagos,
            $update_data,
            ['id' => $transaction_id],
            null,
            ['%d']
        );

        return $resultado !== false;
    }

    /**
     * Obtiene la IP del cliente
     *
     * @return string
     */
    protected function get_client_ip(): string {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Si hay múltiples IPs, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
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
                'label' => __('Habilitar pasarela', 'flavor-chat-ia'),
                'default' => false,
            ],
        ];
    }

    /**
     * Valida la configuración de la pasarela
     *
     * @param array $data Datos a validar
     * @return array|WP_Error Datos validados o error
     */
    public function validate_settings(array $data) {
        // Validación básica, las clases hijas pueden extender
        return array_map('sanitize_text_field', $data);
    }

    /**
     * Procesa un reembolso
     *
     * @param int $transaction_id ID de la transacción
     * @param float $amount Monto a reembolsar
     * @param string $reason Motivo del reembolso
     * @return array Resultado del reembolso
     */
    public function process_refund(int $transaction_id, float $amount, string $reason = ''): array {
        // Por defecto, indicar que no soporta reembolsos automáticos
        return [
            'success' => false,
            'error' => __('Esta pasarela no soporta reembolsos automáticos.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Obtiene el estado legible de una transacción
     *
     * @param string $status Estado interno
     * @return string Estado legible
     */
    public static function get_status_label(string $status): string {
        $labels = [
            'pendiente' => __('Pendiente', 'flavor-chat-ia'),
            'procesando' => __('Procesando', 'flavor-chat-ia'),
            'completado' => __('Completado', 'flavor-chat-ia'),
            'fallido' => __('Fallido', 'flavor-chat-ia'),
            'reembolsado' => __('Reembolsado', 'flavor-chat-ia'),
            'cancelado' => __('Cancelado', 'flavor-chat-ia'),
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Obtiene el color CSS del estado
     *
     * @param string $status Estado interno
     * @return string Color CSS
     */
    public static function get_status_color(string $status): string {
        $colors = [
            'pendiente' => 'warning',
            'procesando' => 'info',
            'completado' => 'success',
            'fallido' => 'danger',
            'reembolsado' => 'secondary',
            'cancelado' => 'secondary',
        ];

        return $colors[$status] ?? 'secondary';
    }
}
