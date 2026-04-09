<?php
/**
 * Trait para notificaciones en módulos
 *
 * Proporciona métodos helper para que los módulos emitan notificaciones fácilmente.
 * Los módulos solo necesitan usar este trait y llamar a los métodos notify_*
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Flavor_Module_Notifications_Trait {

    /**
     * Emite una notificación para el módulo actual
     *
     * @param string $tipo_evento Tipo de evento (ej: 'nueva_reserva', 'pedido_listo')
     * @param array  $datos       Datos del evento
     * @param array  $opciones    Opciones adicionales:
     *                            - destinatarios: array de user_ids específicos
     *                            - canal: 'push', 'email', 'in_app', 'all'
     *                            - prioridad: 'low', 'normal', 'high'
     * @return bool True si se envió correctamente
     */
    protected function emitir_notificacion($tipo_evento, $datos = [], $opciones = []) {
        $modulo_id = $this->get_id();

        // Opciones por defecto
        $opciones = wp_parse_args($opciones, [
            'destinatarios' => [],
            'canal' => 'all',
            'prioridad' => 'normal',
        ]);

        // Añadir información del módulo
        $datos['modulo_id'] = $modulo_id;
        $datos['modulo_nombre'] = $this->get_name();

        // Disparar el evento genérico
        do_action('flavor_module_event', $tipo_evento, $datos, $modulo_id);

        // Si hay destinatarios específicos, enviar directamente
        if (!empty($opciones['destinatarios']) && class_exists('Flavor_Notification_Manager')) {
            $manager = Flavor_Notification_Manager::get_instance();

            $notificacion = $this->construir_notificacion($tipo_evento, $datos, $opciones);

            foreach ($opciones['destinatarios'] as $user_id) {
                if (method_exists($manager, 'enviar')) {
                    $manager->enviar($user_id, $notificacion);
                }
            }
        }

        return true;
    }

    /**
     * Construye el objeto de notificación
     *
     * @param string $tipo_evento
     * @param array  $datos
     * @param array  $opciones
     * @return array
     */
    private function construir_notificacion($tipo_evento, $datos, $opciones) {
        return [
            'tipo' => $tipo_evento,
            'modulo' => $datos['modulo_id'],
            'titulo' => $this->obtener_titulo_notificacion($tipo_evento, $datos),
            'mensaje' => $this->obtener_mensaje_notificacion($tipo_evento, $datos),
            'datos' => $datos,
            'canal' => $opciones['canal'],
            'prioridad' => $opciones['prioridad'],
            'fecha' => current_time('mysql'),
            'url' => $this->obtener_url_notificacion($tipo_evento, $datos),
        ];
    }

    /**
     * Obtiene el título de la notificación según el tipo de evento
     * Los módulos pueden sobrescribir este método para personalizar títulos
     *
     * @param string $tipo_evento
     * @param array  $datos
     * @return string
     */
    protected function obtener_titulo_notificacion($tipo_evento, $datos) {
        $modulo_nombre = $datos['modulo_nombre'] ?? $this->get_name();

        // Títulos genéricos por tipo de evento
        $titulos = [
            'nuevo_registro' => sprintf(__('Nuevo registro en %s', 'flavor-platform'), $modulo_nombre),
            'nueva_reserva' => __('Nueva reserva confirmada', 'flavor-platform'),
            'reserva_cancelada' => __('Reserva cancelada', 'flavor-platform'),
            'nuevo_pedido' => __('Nuevo pedido recibido', 'flavor-platform'),
            'pedido_listo' => __('Tu pedido está listo', 'flavor-platform'),
            'nuevo_mensaje' => __('Nuevo mensaje', 'flavor-platform'),
            'nueva_incidencia' => __('Nueva incidencia reportada', 'flavor-platform'),
            'incidencia_resuelta' => __('Incidencia resuelta', 'flavor-platform'),
            'recordatorio' => __('Recordatorio', 'flavor-platform'),
            'actualizacion' => sprintf(__('Actualización en %s', 'flavor-platform'), $modulo_nombre),
        ];

        return $titulos[$tipo_evento] ?? sprintf(__('Notificación de %s', 'flavor-platform'), $modulo_nombre);
    }

    /**
     * Obtiene el mensaje de la notificación según el tipo de evento
     * Los módulos pueden sobrescribir este método para personalizar mensajes
     *
     * @param string $tipo_evento
     * @param array  $datos
     * @return string
     */
    protected function obtener_mensaje_notificacion($tipo_evento, $datos) {
        // Los módulos deben sobrescribir este método para mensajes específicos
        return apply_filters(
            'flavor_notification_mensaje_' . $this->get_id(),
            $datos['mensaje'] ?? '',
            $tipo_evento,
            $datos
        );
    }

    /**
     * Obtiene la URL a la que redirigir al hacer clic en la notificación
     * Los módulos pueden sobrescribir este método
     *
     * @param string $tipo_evento
     * @param array  $datos
     * @return string
     */
    protected function obtener_url_notificacion($tipo_evento, $datos) {
        // Por defecto, ir a la página del módulo
        return apply_filters(
            'flavor_notification_url_' . $this->get_id(),
            $datos['url'] ?? '',
            $tipo_evento,
            $datos
        );
    }

    // ==========================================
    // MÉTODOS HELPER PARA EVENTOS COMUNES
    // ==========================================

    /**
     * Notifica a un usuario específico
     *
     * @param int    $user_id      ID del usuario
     * @param string $titulo       Título de la notificación
     * @param string $mensaje      Mensaje
     * @param string $url          URL de acción (opcional)
     * @param string $tipo_evento  Tipo de evento (opcional)
     */
    protected function notificar_usuario($user_id, $titulo, $mensaje, $url = '', $tipo_evento = 'notificacion') {
        $this->emitir_notificacion($tipo_evento, [
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'url' => $url,
        ], [
            'destinatarios' => [$user_id],
        ]);
    }

    /**
     * Notifica a todos los administradores del módulo
     *
     * @param string $tipo_evento
     * @param array  $datos
     */
    protected function notificar_admins($tipo_evento, $datos = []) {
        // Obtener administradores con capacidad para el módulo
        $modulo_id = $this->get_id();
        $capability = 'manage_' . str_replace('-', '_', $modulo_id);

        $admins = get_users([
            'capability' => $capability,
            'fields' => 'ID',
        ]);

        // Fallback: administradores generales
        if (empty($admins)) {
            $admins = get_users([
                'role' => 'administrator',
                'fields' => 'ID',
            ]);
        }

        if (!empty($admins)) {
            $this->emitir_notificacion($tipo_evento, $datos, [
                'destinatarios' => $admins,
                'prioridad' => 'high',
            ]);
        }
    }

    /**
     * Notifica a todos los suscriptores del módulo
     *
     * @param string $tipo_evento
     * @param array  $datos
     */
    protected function notificar_suscriptores($tipo_evento, $datos = []) {
        $modulo_id = $this->get_id();

        // Obtener suscriptores desde meta de usuario
        $suscriptores = get_users([
            'meta_key' => 'flavor_suscripcion_' . $modulo_id,
            'meta_value' => '1',
            'fields' => 'ID',
        ]);

        if (!empty($suscriptores)) {
            $this->emitir_notificacion($tipo_evento, $datos, [
                'destinatarios' => $suscriptores,
            ]);
        }
    }

    /**
     * Programa una notificación para el futuro
     *
     * @param string $tipo_evento
     * @param array  $datos
     * @param int    $timestamp   Timestamp Unix para enviar
     * @param array  $opciones
     * @return int|false ID del evento programado o false
     */
    protected function programar_notificacion($tipo_evento, $datos, $timestamp, $opciones = []) {
        $modulo_id = $this->get_id();

        $evento_data = [
            'modulo' => $modulo_id,
            'tipo' => $tipo_evento,
            'datos' => $datos,
            'opciones' => $opciones,
        ];

        return wp_schedule_single_event(
            $timestamp,
            'flavor_enviar_notificacion_programada',
            [$evento_data]
        );
    }
}

// Hook para procesar notificaciones programadas
add_action('flavor_enviar_notificacion_programada', function($evento_data) {
    if (!class_exists('Flavor_Chat_Module_Loader')) {
        return;
    }

    $loader = Flavor_Chat_Module_Loader::get_instance();
    $modulo = $loader->get_module($evento_data['modulo']);

    if ($modulo && method_exists($modulo, 'emitir_notificacion')) {
        $modulo->emitir_notificacion(
            $evento_data['tipo'],
            $evento_data['datos'],
            $evento_data['opciones']
        );
    }
});
