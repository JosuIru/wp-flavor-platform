<?php
/**
 * Centro de Notificaciones Unificado
 *
 * Sistema centralizado para gestionar notificaciones de todos los módulos
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Notification_Center {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Nombre de la tabla de notificaciones
     */
    const TABLE_NAME = 'flavor_notifications';

    /**
     * Tipos de notificación disponibles
     */
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';

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
        // Crear tabla en activación
        add_action('init', [$this, 'maybe_create_table']);

        // Registrar API REST
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX handlers
        add_action('wp_ajax_flavor_mark_notification_read', [$this, 'ajax_mark_read']);
        add_action('wp_ajax_flavor_mark_all_read', [$this, 'ajax_mark_all_read']);
        add_action('wp_ajax_flavor_delete_notification', [$this, 'ajax_delete_notification']);
        add_action('wp_ajax_flavor_get_notifications', [$this, 'ajax_get_notifications']);

        // Shortcode para widget de notificaciones
        add_shortcode('flavor_notifications_widget', [$this, 'render_widget']);

        // Encolar assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Crea la tabla de notificaciones si no existe
     */
    public function maybe_create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        // Verificar si la tabla ya existe
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name) {
            // Si existe, verificar y actualizar estructura
            $this->verify_and_update_structure();
            return;
        }

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            module_id varchar(100) NOT NULL DEFAULT '',
            type varchar(20) NOT NULL DEFAULT 'info',
            title varchar(255) NOT NULL,
            message text NOT NULL,
            link varchar(255) DEFAULT '',
            metadata longtext DEFAULT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            read_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY module_id (module_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        error_log("[Flavor Notifications] Tabla creada: {$table_name}");
    }

    /**
     * Verificar y actualizar la estructura de la tabla
     */
    private function verify_and_update_structure() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Obtener columnas actuales
        $columns = $wpdb->get_results("DESCRIBE {$table_name}");
        $column_names = array_column($columns, 'Field');

        // Verificar y agregar module_id si no existe
        if (!in_array('module_id', $column_names)) {
            $wpdb->query(
                "ALTER TABLE {$table_name}
                ADD COLUMN module_id varchar(100) NOT NULL DEFAULT '' AFTER user_id,
                ADD KEY module_id (module_id)"
            );
            error_log("[Flavor Notifications] Columna 'module_id' agregada");
        }

        // Verificar y agregar type si no existe
        if (!in_array('type', $column_names)) {
            $wpdb->query(
                "ALTER TABLE {$table_name}
                ADD COLUMN type varchar(20) NOT NULL DEFAULT 'info' AFTER module_id"
            );
            error_log("[Flavor Notifications] Columna 'type' agregada");
        }

        // Verificar y agregar link si no existe
        if (!in_array('link', $column_names)) {
            $wpdb->query(
                "ALTER TABLE {$table_name}
                ADD COLUMN link varchar(255) DEFAULT '' AFTER message"
            );
            error_log("[Flavor Notifications] Columna 'link' agregada");
        }

        // Verificar y agregar metadata si no existe
        if (!in_array('metadata', $column_names)) {
            $wpdb->query(
                "ALTER TABLE {$table_name}
                ADD COLUMN metadata longtext DEFAULT NULL AFTER link"
            );
            error_log("[Flavor Notifications] Columna 'metadata' agregada");
        }

        // Verificar y agregar read_at si no existe
        if (!in_array('read_at', $column_names)) {
            $wpdb->query(
                "ALTER TABLE {$table_name}
                ADD COLUMN read_at datetime DEFAULT NULL AFTER created_at"
            );
            error_log("[Flavor Notifications] Columna 'read_at' agregada");
        }
    }

    /**
     * Envía una notificación a un usuario
     *
     * @param int $user_id ID del usuario
     * @param string $title Título de la notificación
     * @param string $message Mensaje
     * @param array $args Argumentos adicionales
     * @return int|false ID de la notificación o false si falló
     */
    public function send($user_id, $title, $message, $args = []) {
        global $wpdb;

        $defaults = [
            'module_id' => '',
            'type' => self::TYPE_INFO,
            'link' => '',
            'metadata' => [],
        ];

        $args = wp_parse_args($args, $defaults);

        // Validar tipo
        if (!in_array($args['type'], [self::TYPE_INFO, self::TYPE_SUCCESS, self::TYPE_WARNING, self::TYPE_ERROR])) {
            $args['type'] = self::TYPE_INFO;
        }

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $data = [
            'user_id' => absint($user_id),
            'module_id' => sanitize_key($args['module_id']),
            'type' => sanitize_key($args['type']),
            'title' => sanitize_text_field($title),
            'message' => wp_kses_post($message),
            'link' => esc_url_raw($args['link']),
            'metadata' => maybe_serialize($args['metadata']),
            'created_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert($table_name, $data);

        if (!$result) {
            error_log("[Flavor Notifications] Error al insertar notificación: " . $wpdb->last_error);
            return false;
        }

        $notification_id = $wpdb->insert_id;

        // Trigger action para hooks externos
        do_action('flavor_notification_sent', $notification_id, $user_id, $args['module_id']);

        return $notification_id;
    }

    /**
     * Envía notificación a múltiples usuarios
     *
     * @param array $user_ids Array de IDs de usuarios
     * @param string $title Título
     * @param string $message Mensaje
     * @param array $args Argumentos adicionales
     * @return array Array de IDs de notificaciones creadas
     */
    public function send_bulk($user_ids, $title, $message, $args = []) {
        $notification_ids = [];

        foreach ($user_ids as $user_id) {
            $id = $this->send($user_id, $title, $message, $args);
            if ($id) {
                $notification_ids[] = $id;
            }
        }

        return $notification_ids;
    }

    /**
     * Marca una notificación como leída
     *
     * @param int $notification_id
     * @return bool
     */
    public function mark_read($notification_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $result = $wpdb->update(
            $table_name,
            [
                'is_read' => 1,
                'read_at' => current_time('mysql'),
            ],
            ['id' => absint($notification_id)],
            ['%d', '%s'],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Marca todas las notificaciones de un usuario como leídas
     *
     * @param int $user_id
     * @return bool
     */
    public function mark_all_read($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $result = $wpdb->update(
            $table_name,
            [
                'is_read' => 1,
                'read_at' => current_time('mysql'),
            ],
            [
                'user_id' => absint($user_id),
                'is_read' => 0,
            ],
            ['%d', '%s'],
            ['%d', '%d']
        );

        return $result !== false;
    }

    /**
     * Elimina una notificación
     *
     * @param int $notification_id
     * @return bool
     */
    public function delete($notification_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $result = $wpdb->delete(
            $table_name,
            ['id' => absint($notification_id)],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Obtiene notificaciones de un usuario
     *
     * @param int $user_id
     * @param array $args Argumentos de consulta
     * @return array
     */
    public function get_notifications($user_id, $args = []) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $defaults = [
            'module_id' => '',
            'type' => '',
            'unread_only' => false,
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);

        $where = $wpdb->prepare('user_id = %d', absint($user_id));

        if (!empty($args['module_id'])) {
            $where .= $wpdb->prepare(' AND module_id = %s', sanitize_key($args['module_id']));
        }

        if (!empty($args['type'])) {
            $where .= $wpdb->prepare(' AND type = %s', sanitize_key($args['type']));
        }

        if ($args['unread_only']) {
            $where .= ' AND is_read = 0';
        }

        $orderby = sanitize_key($args['orderby']);
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $limit = absint($args['limit']);
        $offset = absint($args['offset']);

        $query = "SELECT * FROM {$table_name} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT {$limit} OFFSET {$offset}";

        $results = $wpdb->get_results($query, ARRAY_A);

        // Deserializar metadata
        foreach ($results as &$notification) {
            $notification['metadata'] = maybe_unserialize($notification['metadata']);
        }

        return $results;
    }

    /**
     * Obtiene el contador de notificaciones no leídas
     *
     * @param int $user_id
     * @param string $module_id Opcional: filtrar por módulo
     * @return int
     */
    public function get_unread_count($user_id, $module_id = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $where = $wpdb->prepare('user_id = %d AND is_read = 0', absint($user_id));

        if (!empty($module_id)) {
            $where .= $wpdb->prepare(' AND module_id = %s', sanitize_key($module_id));
        }

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE {$where}");

        return absint($count);
    }

    /**
     * Limpia notificaciones antiguas
     *
     * @param int $days Días de antigüedad (por defecto 30)
     * @return int Número de notificaciones eliminadas
     */
    public function clean_old_notifications($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE is_read = 1 AND created_at < %s",
            $date
        ));

        return $result !== false ? $result : 0;
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // GET /notifications - Obtener notificaciones
        register_rest_route($namespace, '/notifications', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_notifications'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);

        // GET /notifications/unread-count - Contador de no leídas
        register_rest_route($namespace, '/notifications/unread-count', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_unread_count'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);

        // POST /notifications/(?P<id>\d+)/read - Marcar como leída
        register_rest_route($namespace, '/notifications/(?P<id>\d+)/read', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_mark_read'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);

        // POST /notifications/mark-all-read - Marcar todas como leídas
        register_rest_route($namespace, '/notifications/mark-all-read', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_mark_all_read'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);

        // DELETE /notifications/(?P<id>\d+) - Eliminar notificación
        register_rest_route($namespace, '/notifications/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'rest_delete_notification'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);
    }

    /**
     * REST: Obtener notificaciones
     */
    public function rest_get_notifications($request) {
        $user_id = get_current_user_id();

        $args = [
            'module_id' => $request->get_param('module_id') ?? '',
            'type' => $request->get_param('type') ?? '',
            'unread_only' => filter_var($request->get_param('unread_only'), FILTER_VALIDATE_BOOLEAN),
            'limit' => absint($request->get_param('limit') ?? 20),
            'offset' => absint($request->get_param('offset') ?? 0),
        ];

        $notifications = $this->get_notifications($user_id, $args);

        return rest_ensure_response([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $this->get_unread_count($user_id),
        ]);
    }

    /**
     * REST: Obtener contador de no leídas
     */
    public function rest_get_unread_count($request) {
        $user_id = get_current_user_id();
        $module_id = $request->get_param('module_id') ?? '';

        $count = $this->get_unread_count($user_id, $module_id);

        return rest_ensure_response([
            'success' => true,
            'unread_count' => $count,
        ]);
    }

    /**
     * REST: Marcar como leída
     */
    public function rest_mark_read($request) {
        $notification_id = absint($request['id']);
        $result = $this->mark_read($notification_id);

        return rest_ensure_response([
            'success' => $result,
        ]);
    }

    /**
     * REST: Marcar todas como leídas
     */
    public function rest_mark_all_read($request) {
        $user_id = get_current_user_id();
        $result = $this->mark_all_read($user_id);

        return rest_ensure_response([
            'success' => $result,
        ]);
    }

    /**
     * REST: Eliminar notificación
     */
    public function rest_delete_notification($request) {
        $notification_id = absint($request['id']);
        $result = $this->delete($notification_id);

        return rest_ensure_response([
            'success' => $result,
        ]);
    }

    /**
     * AJAX: Marcar como leída
     */
    public function ajax_mark_read() {
        check_ajax_referer('flavor_notifications', 'nonce');

        $notification_id = absint($_POST['id'] ?? 0);
        $result = $this->mark_read($notification_id);

        wp_send_json_success(['marked' => $result]);
    }

    /**
     * AJAX: Marcar todas como leídas
     */
    public function ajax_mark_all_read() {
        check_ajax_referer('flavor_notifications', 'nonce');

        $user_id = get_current_user_id();
        $result = $this->mark_all_read($user_id);

        wp_send_json_success(['marked' => $result]);
    }

    /**
     * AJAX: Eliminar notificación
     */
    public function ajax_delete_notification() {
        check_ajax_referer('flavor_notifications', 'nonce');

        $notification_id = absint($_POST['id'] ?? 0);
        $result = $this->delete($notification_id);

        wp_send_json_success(['deleted' => $result]);
    }

    /**
     * AJAX: Obtener notificaciones
     */
    public function ajax_get_notifications() {
        check_ajax_referer('flavor_notifications', 'nonce');

        $user_id = get_current_user_id();

        $args = [
            'unread_only' => filter_var($_POST['unread_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'limit' => absint($_POST['limit'] ?? 10),
        ];

        $notifications = $this->get_notifications($user_id, $args);

        wp_send_json_success([
            'notifications' => $notifications,
            'unread_count' => $this->get_unread_count($user_id),
        ]);
    }

    /**
     * Renderiza widget de notificaciones
     */
    public function render_widget($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $atts = shortcode_atts([
            'limit' => 5,
            'show_all_link' => 'yes',
        ], $atts);

        $user_id = get_current_user_id();
        $notifications = $this->get_notifications($user_id, ['limit' => absint($atts['limit'])]);
        $unread_count = $this->get_unread_count($user_id);

        ob_start();
        include FLAVOR_CHAT_IA_PATH . 'templates/notifications/widget.php';
        return ob_get_clean();
    }

    /**
     * Encola assets frontend
     */
    public function enqueue_assets() {
        if (!is_user_logged_in()) {
            return;
        }

        wp_enqueue_style(
            'flavor-notifications',
            FLAVOR_CHAT_IA_URL . 'assets/css/notifications.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-notifications',
            FLAVOR_CHAT_IA_URL . 'assets/js/notifications.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-notifications', 'flavorNotifications', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url(),
            'nonce' => wp_create_nonce('flavor_notifications'),
            'userId' => get_current_user_id(),
            'preferencesUrl' => admin_url('admin.php?page=flavor-settings&tab=notifications'),
            'allNotificationsUrl' => home_url('/mi-portal/#notifications'),
            'enablePolling' => true,
            'soundEnabled' => get_user_meta(get_current_user_id(), 'flavor_notification_sound', true) !== 'off',
            'iconUrl' => FLAVOR_CHAT_IA_URL . 'assets/images/icon-notification.png',
            'i18n' => [
                'confirmDelete' => __('¿Eliminar esta notificación?', 'flavor-chat-ia'),
                'noNotifications' => __('No tienes notificaciones', 'flavor-chat-ia'),
                'markAllRead' => __('Marcar todas como leídas', 'flavor-chat-ia'),
                'loadMore' => __('Cargar más', 'flavor-chat-ia'),
                'loading' => __('Cargando...', 'flavor-chat-ia'),
            ],
        ]);
    }
}
