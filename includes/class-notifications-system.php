<?php
/**
 * Notifications System Backend
 *
 * Sistema completo de notificaciones para usuarios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

class Flavor_Notifications_System {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Nombre de la tabla
     */
    private $table_name;

    /**
     * Obtiene la instancia singleton
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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'flavor_notifications';

        // Hooks
        add_action('init', [$this, 'maybe_create_table']);
        add_action('wp_ajax_flavor_mark_notification_read', [$this, 'ajax_mark_read']);
        add_action('wp_ajax_flavor_mark_all_read', [$this, 'ajax_mark_all_read']);
        add_action('wp_ajax_flavor_delete_notification', [$this, 'ajax_delete']);

        // Filtro para contador de notificaciones
        add_filter('flavor_unread_notifications_count', [$this, 'get_unread_count']);
    }

    /**
     * Crea la tabla si no existe
     */
    public function maybe_create_table() {
        if (get_option('flavor_notifications_table_version') === '1.0') {
            return;
        }

        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            link varchar(500) DEFAULT NULL,
            icon varchar(50) DEFAULT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            read_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('flavor_notifications_table_version', '1.0');
    }

    /**
     * Crea una notificación
     */
    public function create($user_id, $type, $title, $message, $args = []) {
        global $wpdb;

        $defaults = [
            'link' => null,
            'icon' => $this->get_default_icon($type),
        ];

        $args = wp_parse_args($args, $defaults);

        $result = $wpdb->insert(
            $this->table_name,
            [
                'user_id' => $user_id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $args['link'],
                'icon' => $args['icon'],
                'is_read' => 0,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
        );

        if ($result) {
            // Trigger acción para otros sistemas (email, push, etc.)
            do_action('flavor_notification_created', $wpdb->insert_id, $user_id, $type);
        }

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Obtiene icono por defecto según tipo
     */
    private function get_default_icon($type) {
        $icons = [
            'info' => 'ℹ️',
            'success' => '✅',
            'warning' => '⚠️',
            'error' => '❌',
            'message' => '💬',
            'event' => '📅',
            'taller' => '🎨',
            'incidencia' => '🔧',
            'reserva' => '🏛️',
            'pedido' => '🛒',
            'pago' => '💰',
            // Comunidades cross-comunidad
            'nueva_publicacion' => '📝',
            'nuevo_evento' => '📅',
            'nuevo_miembro' => '👋',
            'recurso_compartido' => '📦',
            'mencion' => '💬',
            'contenido_federado' => '🌐',
            'crosspost' => '🔄',
            'comunidad_relacionada' => '🏘️',
            'evento_red' => '🗓️',
            'comunidades_sugeridas' => '🎯',
        ];

        return $icons[$type] ?? 'ℹ️';
    }

    /**
     * Obtiene notificaciones de un usuario
     */
    public function get_user_notifications($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'unread_only' => false,
            'type' => null,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = $wpdb->prepare('user_id = %d', $user_id);

        if ($args['unread_only']) {
            $where .= ' AND is_read = 0';
        }

        if ($args['type']) {
            $where .= $wpdb->prepare(' AND type = %s', $args['type']);
        }

        $query = "SELECT * FROM {$this->table_name}
                  WHERE {$where}
                  ORDER BY created_at DESC
                  LIMIT {$args['limit']} OFFSET {$args['offset']}";

        return $wpdb->get_results($query);
    }

    /**
     * Obtiene contador de no leídas
     */
    public function get_unread_count($user_id = null) {
        global $wpdb;

        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return 0;
        }

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND is_read = 0",
            $user_id
        ));

        return (int) $count;
    }

    /**
     * Marca notificación como leída
     */
    public function mark_as_read($notification_id, $user_id = null) {
        global $wpdb;

        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        $result = $wpdb->update(
            $this->table_name,
            [
                'is_read' => 1,
                'read_at' => current_time('mysql'),
            ],
            [
                'id' => $notification_id,
                'user_id' => $user_id,
            ],
            ['%d', '%s'],
            ['%d', '%d']
        );

        return $result !== false;
    }

    /**
     * Marca todas como leídas
     */
    public function mark_all_as_read($user_id = null) {
        global $wpdb;

        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        $result = $wpdb->update(
            $this->table_name,
            [
                'is_read' => 1,
                'read_at' => current_time('mysql'),
            ],
            [
                'user_id' => $user_id,
                'is_read' => 0,
            ],
            ['%d', '%s'],
            ['%d', '%d']
        );

        return $result !== false;
    }

    /**
     * Elimina una notificación
     */
    public function delete($notification_id, $user_id = null) {
        global $wpdb;

        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        $result = $wpdb->delete(
            $this->table_name,
            [
                'id' => $notification_id,
                'user_id' => $user_id,
            ],
            ['%d', '%d']
        );

        return $result !== false;
    }

    /**
     * AJAX: Marcar como leída
     */
    public function ajax_mark_read() {
        check_ajax_referer('flavor_notifications', 'nonce');

        $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

        if (!$notification_id) {
            wp_send_json_error(['message' => 'Invalid notification ID']);
        }

        $result = $this->mark_as_read($notification_id);

        if ($result) {
            wp_send_json_success([
                'message' => __('Notificación marcada como leída', 'flavor-chat-ia'),
                'unread_count' => $this->get_unread_count(),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al marcar notificación', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Marcar todas como leídas
     */
    public function ajax_mark_all_read() {
        check_ajax_referer('flavor_notifications', 'nonce');

        $result = $this->mark_all_as_read();

        if ($result !== false) {
            wp_send_json_success([
                'message' => __('Todas las notificaciones marcadas como leídas', 'flavor-chat-ia'),
                'unread_count' => 0,
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al marcar notificaciones', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Eliminar notificación
     */
    public function ajax_delete() {
        check_ajax_referer('flavor_notifications', 'nonce');

        $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

        if (!$notification_id) {
            wp_send_json_error(['message' => 'Invalid notification ID']);
        }

        $result = $this->delete($notification_id);

        if ($result) {
            wp_send_json_success([
                'message' => __('Notificación eliminada', 'flavor-chat-ia'),
                'unread_count' => $this->get_unread_count(),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al eliminar notificación', 'flavor-chat-ia')]);
        }
    }

    /**
     * Helpers: Crear notificaciones comunes
     */
    public static function notify_event_created($user_id, $event_title) {
        $system = self::get_instance();
        return $system->create(
            $user_id,
            'event',
            __('Nuevo Evento', 'flavor-chat-ia'),
            sprintf(__('Se ha creado el evento "%s"', 'flavor-chat-ia'), $event_title),
            ['link' => home_url('/eventos/'), 'icon' => '📅']
        );
    }

    public static function notify_taller_approved($user_id, $taller_title) {
        $system = self::get_instance();
        return $system->create(
            $user_id,
            'success',
            __('Taller Aprobado', 'flavor-chat-ia'),
            sprintf(__('Tu taller "%s" ha sido aprobado', 'flavor-chat-ia'), $taller_title),
            ['link' => home_url('/talleres/mis-talleres/'), 'icon' => '✅']
        );
    }

    public static function notify_incidencia_resuelta($user_id, $incidencia_id) {
        $system = self::get_instance();
        return $system->create(
            $user_id,
            'success',
            __('Incidencia Resuelta', 'flavor-chat-ia'),
            __('Tu incidencia ha sido marcada como resuelta', 'flavor-chat-ia'),
            ['link' => home_url("/incidencias/{$incidencia_id}/"), 'icon' => '✅']
        );
    }

    public static function notify_reserva_confirmada($user_id, $espacio_name, $fecha) {
        $system = self::get_instance();
        return $system->create(
            $user_id,
            'success',
            __('Reserva Confirmada', 'flavor-chat-ia'),
            sprintf(__('Tu reserva de "%s" para %s ha sido confirmada', 'flavor-chat-ia'), $espacio_name, $fecha),
            ['link' => home_url('/espacios-comunes/mis-reservas/'), 'icon' => '🏛️']
        );
    }

    public static function notify_pedido_listo($user_id) {
        $system = self::get_instance();
        return $system->create(
            $user_id,
            'info',
            __('Pedido Listo', 'flavor-chat-ia'),
            __('Tu pedido está listo para recoger', 'flavor-chat-ia'),
            ['link' => Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mi-grupo'), 'icon' => '🛒']
        );
    }
}

// Inicializar
Flavor_Notifications_System::get_instance();
