<?php
/**
 * Notifications System — wrapper deprecated.
 *
 * @deprecated since 3.2.0  Use {@see Flavor_Notification_Center} directly.
 *   Esta clase se mantiene como capa de compatibilidad para los 27 callers
 *   históricos. Delega cada llamada al Notification_Center y NO registra
 *   handlers AJAX ni crea la tabla (Notification_Center es el owner del
 *   schema y de los hooks `wp_ajax_flavor_*`).
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Notifications_System {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Sin hooks: Notification_Center registra los handlers AJAX y crea
        // la tabla `wp_flavor_notifications`. Aquí solo exponemos la API
        // legacy delegando a Notification_Center.
        add_filter('flavor_unread_notifications_count', [$this, 'get_unread_count']);
    }

    /**
     * @deprecated 3.2.0 Use Flavor_Notification_Center::send().
     */
    public function create($user_id, $type, $title, $message, $args = []) {
        $center = Flavor_Notification_Center::get_instance();
        $args['type'] = $type;
        if (isset($args['icon'])) {
            // Notification_Center no almacena icon directamente; pasa por metadata.
            $args['metadata'] = array_merge(
                isset($args['metadata']) && is_array($args['metadata']) ? $args['metadata'] : [],
                ['icon' => $args['icon']]
            );
            unset($args['icon']);
        }
        return $center->send($user_id, $title, $message, $args);
    }

    /**
     * @deprecated 3.2.0 Use Flavor_Notification_Center::get_notifications().
     */
    public function get_user_notifications($user_id, $args = []) {
        return Flavor_Notification_Center::get_instance()->get_notifications($user_id, $args);
    }

    /**
     * @deprecated 3.2.0 Use Flavor_Notification_Center::get_unread_count().
     */
    public function get_unread_count($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return 0;
        }
        return Flavor_Notification_Center::get_instance()->get_unread_count((int) $user_id);
    }

    /**
     * @deprecated 3.2.0 Use Flavor_Notification_Center::mark_read().
     */
    public function mark_as_read($notification_id, $user_id = null) {
        return Flavor_Notification_Center::get_instance()->mark_read($notification_id);
    }

    /**
     * @deprecated 3.2.0 Use Flavor_Notification_Center::mark_all_read().
     */
    public function mark_all_as_read($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        return Flavor_Notification_Center::get_instance()->mark_all_read((int) $user_id);
    }

    /**
     * @deprecated 3.2.0 Use Flavor_Notification_Center::delete().
     */
    public function delete($notification_id, $user_id = null) {
        return Flavor_Notification_Center::get_instance()->delete($notification_id);
    }

    /**
     * Helpers estáticos legacy. Se mantienen por compatibilidad con tests
     * y posibles llamadas externas; delegan al Notification_Center.
     *
     * @deprecated 3.2.0
     */
    public static function notify_event_created($user_id, $event_title) {
        return self::get_instance()->create(
            $user_id,
            'event',
            __('Nuevo Evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            sprintf(__('Se ha creado el evento "%s"', FLAVOR_PLATFORM_TEXT_DOMAIN), $event_title),
            ['link' => home_url('/eventos/'), 'icon' => '📅']
        );
    }

    /**
     * @deprecated 3.2.0
     */
    public static function notify_taller_approved($user_id, $taller_title) {
        return self::get_instance()->create(
            $user_id,
            'taller',
            __('Taller Aprobado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            sprintf(__('Tu taller "%s" ha sido aprobado', FLAVOR_PLATFORM_TEXT_DOMAIN), $taller_title),
            ['link' => home_url('/talleres/'), 'icon' => '🎨']
        );
    }

    /**
     * @deprecated 3.2.0
     */
    public static function notify_incidencia_resuelta($user_id, $incidencia_id) {
        return self::get_instance()->create(
            $user_id,
            'incidencia',
            __('Incidencia Resuelta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Tu incidencia ha sido resuelta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ['link' => home_url('/incidencias/' . $incidencia_id), 'icon' => '🔧']
        );
    }

    /**
     * @deprecated 3.2.0
     */
    public static function notify_reserva_confirmada($user_id, $espacio_name, $fecha) {
        return self::get_instance()->create(
            $user_id,
            'reserva',
            __('Reserva Confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            sprintf(__('Tu reserva de %s para el %s ha sido confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN), $espacio_name, $fecha),
            ['link' => home_url('/espacios-comunes/'), 'icon' => '🏛️']
        );
    }

    /**
     * @deprecated 3.2.0
     */
    public static function notify_pedido_listo($user_id) {
        return self::get_instance()->create(
            $user_id,
            'pedido',
            __('Pedido Listo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Tu pedido está listo para recoger', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ['link' => home_url('/grupos-consumo/'), 'icon' => '🛒']
        );
    }
}
