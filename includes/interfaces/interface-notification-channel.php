<?php
/**
 * Interface para Canales de Notificacion
 *
 * Define el contrato que deben implementar todos los canales de notificacion
 * en Flavor Platform (push, email, SMS, WhatsApp, Telegram, webhooks, etc.).
 *
 * @package    FlavorPlatform
 * @subpackage Interfaces
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interface Flavor_Notification_Channel_Interface
 *
 * Contrato para implementar canales de notificacion.
 * Cualquier canal de notificacion (push, email, SMS, etc.) debe implementar
 * esta interfaz para integrarse con Flavor_Notification_Manager.
 *
 * @since 1.0.0
 */
interface Flavor_Notification_Channel_Interface {

    /**
     * Obtiene el identificador unico del canal.
     *
     * Este ID se usa para referenciar el canal en preferencias de usuario,
     * configuracion y logs. Debe ser un slug unico (ej: 'push', 'email', 'sms').
     *
     * @since 1.0.0
     * @return string Identificador unico del canal (slug).
     */
    public function get_id(): string;

    /**
     * Obtiene el nombre legible del canal.
     *
     * Nombre para mostrar en interfaces de usuario y administracion.
     *
     * @since 1.0.0
     * @return string Nombre del canal localizado.
     */
    public function get_name(): string;

    /**
     * Obtiene la descripcion del canal.
     *
     * Descripcion breve que explica el proposito y funcionamiento del canal.
     *
     * @since 1.0.0
     * @return string Descripcion del canal localizada.
     */
    public function get_description(): string;

    /**
     * Obtiene el icono del canal.
     *
     * Puede ser una clase de dashicon de WordPress (ej: 'dashicons-email')
     * o una URL a un icono SVG/imagen.
     *
     * @since 1.0.0
     * @return string Clase de icono o URL.
     */
    public function get_icon(): string;

    /**
     * Verifica si el canal esta habilitado y correctamente configurado.
     *
     * Comprueba que todas las dependencias y configuraciones necesarias
     * estan disponibles para que el canal funcione.
     *
     * @since 1.0.0
     * @return bool True si el canal esta listo para enviar, false en caso contrario.
     */
    public function is_enabled(): bool;

    /**
     * Verifica si el canal soporta un tipo de notificacion especifico.
     *
     * Permite filtrar canales segun el tipo de evento o notificacion.
     * Por ejemplo, SMS podria no soportar notificaciones con imagenes.
     *
     * @since 1.0.0
     * @param string $notification_type Tipo de notificacion (ej: 'security_alert', 'order_status').
     * @return bool True si el canal soporta este tipo de notificacion.
     */
    public function supports( string $notification_type ): bool;

    /**
     * Envia una notificacion a un usuario a traves de este canal.
     *
     * Metodo principal para enviar notificaciones individuales.
     *
     * @since 1.0.0
     * @param int    $user_id ID del usuario destinatario.
     * @param string $title   Titulo de la notificacion.
     * @param string $message Cuerpo del mensaje.
     * @param array  $data    Datos adicionales opcionales.
     *                        Puede incluir: 'link', 'icon', 'color', 'priority',
     *                        'notification_id', 'type', y datos especificos del canal.
     * @return array Resultado del envio con claves:
     *               - 'success' (bool): Si el envio fue exitoso.
     *               - 'enviados' (int): Numero de mensajes enviados exitosamente.
     *               - 'fallidos' (int): Numero de mensajes fallidos.
     *               - 'error' (string|null): Mensaje de error si aplica.
     *               - Datos adicionales especificos del canal.
     */
    public function send( int $user_id, string $title, string $message, array $data = [] ): array;

    /**
     * Envia una notificacion a multiples usuarios.
     *
     * Optimizado para envios masivos. Las implementaciones deben
     * considerar rate limits y optimizaciones de batch cuando sea posible.
     *
     * @since 1.0.0
     * @param array  $user_ids Array de IDs de usuarios destinatarios.
     * @param string $title    Titulo de la notificacion.
     * @param string $message  Cuerpo del mensaje.
     * @param array  $data     Datos adicionales opcionales.
     * @return array Resumen del envio con claves:
     *               - 'total_usuarios' (int): Total de usuarios procesados.
     *               - 'enviados' (int): Total de envios exitosos.
     *               - 'fallidos' (int): Total de envios fallidos.
     *               - 'sin_destinatario' (int): Usuarios sin datos de contacto para este canal.
     *               - 'resultados' (array): Resultados individuales por user_id.
     */
    public function send_batch( array $user_ids, string $title, string $message, array $data = [] ): array;

    /**
     * Procesa un payload de notificacion desde la cola.
     *
     * Metodo utilizado por el sistema de cola para procesar notificaciones
     * pendientes. El payload contiene todos los datos necesarios.
     *
     * @since 1.0.0
     * @param array $payload Datos de la notificacion desde la cola. Incluye:
     *                       - 'user_id' (int): ID del usuario.
     *                       - 'title' (string): Titulo.
     *                       - 'message' (string): Mensaje.
     *                       - 'notification_id' (int): ID de notificacion.
     *                       - 'type' (string): Tipo de evento.
     *                       - 'link' (string): URL de accion.
     *                       - 'icon' (string): Icono.
     *                       - 'data' (array): Datos adicionales.
     * @return bool True si el procesamiento fue exitoso, false en caso contrario.
     */
    public function handle_from_queue( array $payload ): bool;

    /**
     * Obtiene los requisitos de configuracion del canal.
     *
     * Devuelve la lista de campos de configuracion necesarios
     * para que el canal funcione correctamente.
     *
     * @since 1.0.0
     * @return array Array de campos de configuracion con estructura:
     *               [
     *                   'field_id' => [
     *                       'label' => 'Etiqueta del campo',
     *                       'type' => 'text|password|textarea|checkbox|select',
     *                       'description' => 'Descripcion de ayuda',
     *                       'required' => true|false,
     *                       'default' => 'valor por defecto',
     *                       'options' => [] // Solo para type=select
     *                   ],
     *               ]
     */
    public function get_configuration_fields(): array;

    /**
     * Configura el canal con los ajustes proporcionados.
     *
     * Valida y guarda la configuracion del canal.
     *
     * @since 1.0.0
     * @param array $settings Array asociativo con los ajustes a aplicar.
     * @return bool True si la configuracion fue guardada exitosamente.
     */
    public function configure( array $settings ): bool;

    /**
     * Obtiene la configuracion actual del canal.
     *
     * @since 1.0.0
     * @return array Configuracion actual del canal.
     */
    public function get_configuration(): array;

    /**
     * Valida que la configuracion actual sea correcta y funcional.
     *
     * Realiza validaciones de conectividad, credenciales, etc.
     * Util para mostrar estado en paneles de administracion.
     *
     * @since 1.0.0
     * @return array Resultado de la validacion:
     *               - 'valid' (bool): Si la configuracion es valida.
     *               - 'errors' (array): Lista de errores encontrados.
     *               - 'warnings' (array): Lista de advertencias.
     */
    public function validate_configuration(): array;

    /**
     * Verifica si el canal esta disponible para un usuario especifico.
     *
     * Comprueba que el usuario tenga los datos necesarios para recibir
     * notificaciones por este canal (ej: token push, telefono, email).
     *
     * @since 1.0.0
     * @param int $user_id ID del usuario a verificar.
     * @return bool True si el usuario puede recibir notificaciones por este canal.
     */
    public function is_available_for_user( int $user_id ): bool;

    /**
     * Obtiene la prioridad del canal.
     *
     * Usado para ordenar canales en listas y determinar
     * el orden de procesamiento en la cola.
     *
     * @since 1.0.0
     * @return int Prioridad (menor = mayor prioridad). Default: 10.
     */
    public function get_priority(): int;

    /**
     * Obtiene el rate limit del canal.
     *
     * Define cuantas notificaciones por segundo/minuto puede manejar
     * este canal para evitar exceder limites de proveedores externos.
     *
     * @since 1.0.0
     * @return array Configuracion de rate limit:
     *               - 'requests_per_second' (int|null): Limite por segundo.
     *               - 'requests_per_minute' (int|null): Limite por minuto.
     *               - 'batch_size' (int): Tamano maximo de lote.
     */
    public function get_rate_limit(): array;
}
