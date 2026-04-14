<?php
/**
 * Interface para Pasarelas de Pago
 *
 * Define el contrato que deben implementar todas las pasarelas de pago
 * en Flavor Platform (Stripe, PayPal, Redsys, WooCommerce, etc.).
 *
 * @package    FlavorPlatform
 * @subpackage Interfaces
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interface Flavor_Payment_Gateway_Interface
 *
 * Contrato para implementar pasarelas de pago.
 * Cualquier pasarela de pago (Stripe, PayPal, Redsys, WooCommerce, etc.)
 * debe implementar esta interfaz para integrarse con los modulos de pago
 * de Flavor Platform (facturas, socios, grupos-consumo).
 *
 * @since 1.0.0
 */
interface Flavor_Payment_Gateway_Interface {

    /*
    |--------------------------------------------------------------------------
    | Identificacion de la Pasarela
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene el identificador unico de la pasarela.
     *
     * Este ID se usa para referenciar la pasarela en configuracion,
     * transacciones y logs. Debe ser un slug unico (ej: 'stripe', 'paypal', 'redsys').
     *
     * @since 1.0.0
     * @return string Identificador unico de la pasarela (slug).
     */
    public function get_id(): string;

    /**
     * Obtiene el nombre legible de la pasarela.
     *
     * Nombre para mostrar en interfaces de usuario y administracion.
     *
     * @since 1.0.0
     * @return string Nombre de la pasarela localizado.
     */
    public function get_name(): string;

    /**
     * Obtiene la descripcion de la pasarela.
     *
     * Descripcion breve que explica el metodo de pago al usuario.
     *
     * @since 1.0.0
     * @return string Descripcion de la pasarela localizada.
     */
    public function get_description(): string;

    /**
     * Obtiene el icono de la pasarela.
     *
     * Puede ser una clase de dashicon de WordPress (ej: 'dashicons-credit-card'),
     * una URL a un icono SVG/imagen, o una clase de iconos personalizados.
     *
     * @since 1.0.0
     * @return string Clase de icono o URL.
     */
    public function get_icon(): string;

    /*
    |--------------------------------------------------------------------------
    | Estado y Disponibilidad
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si la pasarela esta habilitada en la configuracion.
     *
     * Comprueba si el administrador ha activado esta pasarela
     * en los ajustes del modulo correspondiente.
     *
     * @since 1.0.0
     * @return bool True si la pasarela esta habilitada.
     */
    public function is_enabled(): bool;

    /**
     * Verifica si la pasarela esta correctamente configurada.
     *
     * Comprueba que las credenciales y configuracion minima
     * necesaria estan presentes (claves API, tokens, etc.).
     *
     * @since 1.0.0
     * @return bool True si la pasarela esta configurada.
     */
    public function is_configured(): bool;

    /**
     * Verifica si la pasarela esta disponible para procesar pagos.
     *
     * Combina las verificaciones de habilitacion, configuracion
     * y cualquier otra condicion necesaria (dependencias, estado del servicio).
     *
     * @since 1.0.0
     * @return bool True si la pasarela esta lista para procesar pagos.
     */
    public function is_available(): bool;

    /**
     * Verifica si la pasarela esta en modo de pruebas (sandbox).
     *
     * En modo sandbox se usan credenciales de prueba y el proveedor
     * de pago no procesa transacciones reales.
     *
     * @since 1.0.0
     * @return bool True si esta en modo sandbox/test.
     */
    public function is_test_mode(): bool;

    /**
     * Obtiene los metodos de pago soportados por esta pasarela.
     *
     * Define que tipos de pago acepta (tarjeta, transferencia, wallet, etc.).
     *
     * @since 1.0.0
     * @return array Lista de metodos soportados.
     *               Valores posibles: 'card', 'bank_transfer', 'wallet',
     *               'cash_on_delivery', 'direct_debit', 'invoice'.
     */
    public function get_supported_payment_methods(): array;

    /**
     * Obtiene las monedas soportadas por la pasarela.
     *
     * @since 1.0.0
     * @return array Lista de codigos de moneda ISO 4217 (ej: ['EUR', 'USD', 'GBP']).
     */
    public function get_supported_currencies(): array;

    /*
    |--------------------------------------------------------------------------
    | Procesamiento de Pagos
    |--------------------------------------------------------------------------
    */

    /**
     * Procesa un pago.
     *
     * Inicia el flujo de pago con la pasarela. Dependiendo del gateway,
     * puede devolver una URL de redireccion, datos para un formulario,
     * o confirmar el pago directamente.
     *
     * @since 1.0.0
     * @param array $payment_data Datos del pago. Claves esperadas:
     *                            - 'amount' (float): Cantidad a cobrar.
     *                            - 'currency' (string): Codigo de moneda (default: EUR).
     *                            - 'order_id' (int|string): ID del pedido/factura/cuota.
     *                            - 'description' (string): Descripcion del pago.
     *                            - 'customer_email' (string): Email del cliente.
     *                            - 'customer_name' (string): Nombre del cliente.
     *                            - 'metadata' (array): Datos adicionales para tracking.
     *                            - 'return_url' (string): URL de retorno tras pago exitoso.
     *                            - 'cancel_url' (string): URL si el usuario cancela.
     * @return array Resultado del proceso:
     *               - 'success' (bool): Si el inicio fue exitoso.
     *               - 'type' (string): Tipo de respuesta: 'redirect', 'form', 'direct', 'iframe'.
     *               - 'redirect_url' (string): URL para redireccionar (si type=redirect).
     *               - 'form_data' (array): Datos para formulario POST (si type=form).
     *               - 'iframe_url' (string): URL para iframe (si type=iframe).
     *               - 'transaction_id' (string): ID de transaccion del gateway.
     *               - 'client_secret' (string): Token para el frontend (Stripe/PayPal).
     *               - 'error' (string): Mensaje de error si success=false.
     *               - 'error_code' (string): Codigo de error para debugging.
     */
    public function process_payment( array $payment_data ): array;

    /**
     * Verifica el estado de un pago.
     *
     * Consulta el estado actual de una transaccion en el gateway.
     *
     * @since 1.0.0
     * @param string $transaction_id ID de la transaccion en el gateway.
     * @return array Estado del pago:
     *               - 'status' (string): Estado: 'pending', 'processing', 'completed',
     *                                    'failed', 'cancelled', 'refunded'.
     *               - 'amount' (float): Cantidad pagada/pendiente.
     *               - 'currency' (string): Moneda.
     *               - 'paid_at' (string|null): Fecha de pago (ISO 8601).
     *               - 'metadata' (array): Datos adicionales del gateway.
     *               - 'error' (string|null): Error si aplica.
     */
    public function verify_payment( string $transaction_id ): array;

    /**
     * Captura un pago previamente autorizado.
     *
     * Algunas pasarelas permiten autorizar primero y capturar despues.
     * Si la pasarela no soporta autorizacion separada, este metodo
     * puede simplemente verificar y confirmar el pago.
     *
     * @since 1.0.0
     * @param string     $transaction_id ID de la transaccion autorizada.
     * @param float|null $amount         Cantidad a capturar (null = total autorizado).
     * @return array Resultado de la captura:
     *               - 'success' (bool): Si la captura fue exitosa.
     *               - 'capture_id' (string): ID de la captura.
     *               - 'amount_captured' (float): Cantidad capturada.
     *               - 'error' (string): Mensaje de error si aplica.
     */
    public function capture_payment( string $transaction_id, ?float $amount = null ): array;

    /**
     * Cancela un pago pendiente o autorizado.
     *
     * Cancela una transaccion antes de que sea capturada/completada.
     *
     * @since 1.0.0
     * @param string $transaction_id ID de la transaccion a cancelar.
     * @param string $reason         Motivo de la cancelacion (opcional).
     * @return array Resultado de la cancelacion:
     *               - 'success' (bool): Si la cancelacion fue exitosa.
     *               - 'error' (string): Mensaje de error si aplica.
     */
    public function cancel_payment( string $transaction_id, string $reason = '' ): array;

    /*
    |--------------------------------------------------------------------------
    | Reembolsos
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si la pasarela soporta reembolsos.
     *
     * @since 1.0.0
     * @return bool True si la pasarela puede procesar reembolsos.
     */
    public function supports_refunds(): bool;

    /**
     * Procesa un reembolso total o parcial.
     *
     * @since 1.0.0
     * @param string $transaction_id ID de la transaccion original.
     * @param float  $amount         Cantidad a reembolsar.
     * @param string $reason         Motivo del reembolso (opcional).
     * @return array Resultado del reembolso:
     *               - 'success' (bool): Si el reembolso fue exitoso.
     *               - 'refund_id' (string): ID del reembolso en el gateway.
     *               - 'amount_refunded' (float): Cantidad reembolsada.
     *               - 'status' (string): Estado: 'pending', 'completed', 'failed'.
     *               - 'error' (string): Mensaje de error si aplica.
     */
    public function process_refund( string $transaction_id, float $amount, string $reason = '' ): array;

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si la pasarela utiliza webhooks.
     *
     * @since 1.0.0
     * @return bool True si la pasarela recibe notificaciones via webhook.
     */
    public function uses_webhooks(): bool;

    /**
     * Obtiene la URL del webhook para esta pasarela.
     *
     * Esta URL debe configurarse en el panel del proveedor de pago
     * para recibir notificaciones de eventos (pagos, reembolsos, etc.).
     *
     * @since 1.0.0
     * @return string URL completa del endpoint de webhook.
     */
    public function get_webhook_url(): string;

    /**
     * Procesa una notificacion de webhook.
     *
     * Recibe, valida y procesa las notificaciones enviadas por el gateway.
     * Debe verificar la firma/autenticidad del webhook antes de procesar.
     *
     * @since 1.0.0
     * @param array $payload Datos del webhook (parseados del request).
     * @param array $headers Headers del request HTTP para verificacion.
     * @return array Resultado del procesamiento:
     *               - 'success' (bool): Si el webhook fue procesado correctamente.
     *               - 'event_type' (string): Tipo de evento procesado.
     *               - 'transaction_id' (string): ID de transaccion afectada.
     *               - 'action_taken' (string): Accion realizada en el sistema.
     *               - 'error' (string): Mensaje de error si aplica.
     */
    public function handle_webhook( array $payload, array $headers = [] ): array;

    /**
     * Verifica la firma/autenticidad de un webhook.
     *
     * @since 1.0.0
     * @param string $payload    Cuerpo raw del request.
     * @param string $signature  Firma recibida en headers.
     * @param string $secret     Secreto de webhook configurado.
     * @return bool True si la firma es valida.
     */
    public function verify_webhook_signature( string $payload, string $signature, string $secret ): bool;

    /*
    |--------------------------------------------------------------------------
    | URLs de Retorno
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene la URL de retorno para pagos exitosos.
     *
     * @since 1.0.0
     * @param int|string $order_id ID del pedido/factura/cuota.
     * @return string URL de retorno exitoso.
     */
    public function get_return_url( $order_id ): string;

    /**
     * Obtiene la URL para pagos cancelados.
     *
     * @since 1.0.0
     * @param int|string $order_id ID del pedido/factura/cuota.
     * @return string URL de cancelacion.
     */
    public function get_cancel_url( $order_id ): string;

    /*
    |--------------------------------------------------------------------------
    | Configuracion y Administracion
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene los campos de configuracion para el panel de administracion.
     *
     * Define los campos que se mostraran en la pagina de ajustes
     * de la pasarela en el admin de WordPress.
     *
     * @since 1.0.0
     * @return array Array de definiciones de campos:
     *               [
     *                   [
     *                       'id' => 'field_id',
     *                       'type' => 'text|password|checkbox|select|textarea',
     *                       'label' => 'Etiqueta del campo',
     *                       'description' => 'Texto de ayuda',
     *                       'default' => 'valor por defecto',
     *                       'required' => true|false,
     *                       'options' => [], // Solo para type=select
     *                   ],
     *               ]
     */
    public function get_admin_fields(): array;

    /**
     * Obtiene la configuracion actual de la pasarela.
     *
     * @since 1.0.0
     * @return array Configuracion actual.
     */
    public function get_settings(): array;

    /**
     * Guarda la configuracion de la pasarela.
     *
     * @since 1.0.0
     * @param array $settings Configuracion a guardar.
     * @return bool True si se guardo correctamente.
     */
    public function save_settings( array $settings ): bool;

    /**
     * Valida las credenciales/configuracion de la pasarela.
     *
     * Realiza una verificacion activa contra la API del gateway
     * para confirmar que las credenciales son correctas.
     *
     * @since 1.0.0
     * @return array Resultado de la validacion:
     *               - 'valid' (bool): Si las credenciales son validas.
     *               - 'message' (string): Mensaje descriptivo.
     *               - 'details' (array): Detalles adicionales del gateway.
     */
    public function validate_credentials(): array;

    /*
    |--------------------------------------------------------------------------
    | Frontend / Checkout
    |--------------------------------------------------------------------------
    */

    /**
     * Renderiza los campos de la pasarela en el checkout.
     *
     * Muestra el formulario, botones o elementos necesarios
     * para que el usuario realice el pago.
     *
     * @since 1.0.0
     * @param array $context Contexto del checkout:
     *                       - 'amount' (float): Cantidad a pagar.
     *                       - 'currency' (string): Moneda.
     *                       - 'order_id' (int|string): ID del pedido.
     *                       - 'customer' (array): Datos del cliente.
     * @return void Imprime HTML directamente.
     */
    public function render_checkout_fields( array $context = [] ): void;

    /**
     * Encola los scripts y estilos necesarios para el checkout.
     *
     * Registra y encola los assets del frontend (JS del SDK del gateway,
     * estilos personalizados, etc.).
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_checkout_assets(): void;

    /**
     * Obtiene los datos necesarios para el frontend JavaScript.
     *
     * Devuelve datos que se pasaran al JavaScript del checkout
     * como claves publicas, configuracion del SDK, etc.
     *
     * @since 1.0.0
     * @return array Datos para el frontend:
     *               - 'publishable_key' (string): Clave publica del gateway.
     *               - 'locale' (string): Idioma para el SDK.
     *               - 'currency' (string): Moneda por defecto.
     *               - Otros datos especificos del gateway.
     */
    public function get_frontend_data(): array;

    /*
    |--------------------------------------------------------------------------
    | Logging y Debug
    |--------------------------------------------------------------------------
    */

    /**
     * Registra un mensaje en el log de la pasarela.
     *
     * @since 1.0.0
     * @param string $message Mensaje a registrar.
     * @param string $level   Nivel: 'debug', 'info', 'warning', 'error'.
     * @param array  $context Datos adicionales de contexto.
     * @return void
     */
    public function log( string $message, string $level = 'info', array $context = [] ): void;

    /**
     * Obtiene el historial de transacciones recientes de la pasarela.
     *
     * @since 1.0.0
     * @param int $limit Numero maximo de transacciones a devolver.
     * @return array Lista de transacciones con sus datos.
     */
    public function get_transaction_history( int $limit = 50 ): array;
}
