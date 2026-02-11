<?php
/**
 * Puente de Notificaciones para Módulos
 *
 * Conecta los eventos de los módulos con el sistema de notificaciones.
 *
 * @package FlavorPlatform
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar notificaciones de módulos
 */
class Flavor_Module_Notifications {

    /**
     * Instancia singleton
     *
     * @var Flavor_Module_Notifications|null
     */
    private static $instance = null;

    /**
     * Nombre del cron hook
     */
    const CRON_HOOK = 'flavor_module_notifications_cron';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Module_Notifications
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        // Hook del cron para procesar notificaciones pendientes
        add_action(self::CRON_HOOK, [$this, 'procesar_notificaciones_pendientes']);

        // Programar cron si no existe
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'hourly', self::CRON_HOOK);
        }

        // Hooks para eventos de módulos
        add_action('flavor_module_event', [$this, 'manejar_evento_modulo'], 10, 3);

        // Hooks específicos por tipo de evento
        add_action('flavor_nuevo_evento', [$this, 'notificar_nuevo_evento'], 10, 2);
        add_action('flavor_nuevo_taller', [$this, 'notificar_nuevo_taller'], 10, 2);
        add_action('flavor_nuevo_pedido', [$this, 'notificar_nuevo_pedido'], 10, 2);
        add_action('flavor_nuevo_socio', [$this, 'notificar_nuevo_socio'], 10, 2);
        add_action('flavor_ciclo_abierto', [$this, 'notificar_ciclo_abierto'], 10, 2);
        add_action('flavor_ciclo_cerrado', [$this, 'notificar_ciclo_cerrado'], 10, 2);
    }

    /**
     * Desactiva el cron (llamado en desactivación del plugin)
     */
    public static function desactivar_cron() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }

    /**
     * Procesa notificaciones pendientes
     */
    public function procesar_notificaciones_pendientes() {
        if (!class_exists('Flavor_Notification_Manager')) {
            return;
        }

        $notification_manager = Flavor_Notification_Manager::get_instance();

        // Verificar que el método existe antes de llamarlo
        if (method_exists($notification_manager, 'process_queue')) {
            $notification_manager->process_queue();
        }
    }

    /**
     * Maneja un evento genérico de módulo
     *
     * @param string $tipo_evento Tipo de evento
     * @param array  $datos       Datos del evento
     * @param string $modulo      Nombre del módulo
     */
    public function manejar_evento_modulo($tipo_evento, $datos, $modulo) {
        if (!class_exists('Flavor_Notification_Manager')) {
            return;
        }

        $notification_manager = Flavor_Notification_Manager::get_instance();

        // Construir datos de notificación
        $notificacion = [
            'tipo' => $tipo_evento,
            'modulo' => $modulo,
            'datos' => $datos,
            'fecha' => current_time('mysql'),
        ];

        // Obtener usuarios a notificar según el tipo de evento
        $usuarios = $this->obtener_usuarios_para_evento($tipo_evento, $datos, $modulo);

        foreach ($usuarios as $usuario_id) {
            $notification_manager->enviar($usuario_id, $notificacion);
        }
    }

    /**
     * Obtiene los usuarios que deben ser notificados para un evento
     *
     * @param string $tipo_evento Tipo de evento
     * @param array  $datos       Datos del evento
     * @param string $modulo      Nombre del módulo
     * @return array IDs de usuarios
     */
    private function obtener_usuarios_para_evento($tipo_evento, $datos, $modulo) {
        $usuarios = [];

        // Filtro para que módulos puedan definir sus propios destinatarios
        $usuarios = apply_filters(
            'flavor_notificacion_destinatarios',
            $usuarios,
            $tipo_evento,
            $datos,
            $modulo
        );

        return array_unique(array_filter($usuarios));
    }

    /**
     * Notifica nuevo evento
     *
     * @param int   $evento_id ID del evento
     * @param array $datos     Datos adicionales
     */
    public function notificar_nuevo_evento($evento_id, $datos = []) {
        do_action('flavor_module_event', 'nuevo_evento', array_merge(['evento_id' => $evento_id], $datos), 'eventos');
    }

    /**
     * Notifica nuevo taller
     *
     * @param int   $taller_id ID del taller
     * @param array $datos     Datos adicionales
     */
    public function notificar_nuevo_taller($taller_id, $datos = []) {
        do_action('flavor_module_event', 'nuevo_taller', array_merge(['taller_id' => $taller_id], $datos), 'talleres');
    }

    /**
     * Notifica nuevo pedido
     *
     * @param int   $pedido_id ID del pedido
     * @param array $datos     Datos adicionales
     */
    public function notificar_nuevo_pedido($pedido_id, $datos = []) {
        do_action('flavor_module_event', 'nuevo_pedido', array_merge(['pedido_id' => $pedido_id], $datos), 'grupos-consumo');
    }

    /**
     * Notifica nuevo socio
     *
     * @param int   $socio_id ID del socio
     * @param array $datos    Datos adicionales
     */
    public function notificar_nuevo_socio($socio_id, $datos = []) {
        do_action('flavor_module_event', 'nuevo_socio', array_merge(['socio_id' => $socio_id], $datos), 'socios');
    }

    /**
     * Notifica ciclo de pedidos abierto
     *
     * @param int   $ciclo_id ID del ciclo
     * @param array $datos    Datos adicionales
     */
    public function notificar_ciclo_abierto($ciclo_id, $datos = []) {
        do_action('flavor_module_event', 'ciclo_abierto', array_merge(['ciclo_id' => $ciclo_id], $datos), 'grupos-consumo');
    }

    /**
     * Notifica ciclo de pedidos cerrado
     *
     * @param int   $ciclo_id ID del ciclo
     * @param array $datos    Datos adicionales
     */
    public function notificar_ciclo_cerrado($ciclo_id, $datos = []) {
        do_action('flavor_module_event', 'ciclo_cerrado', array_merge(['ciclo_id' => $ciclo_id], $datos), 'grupos-consumo');
    }
}
