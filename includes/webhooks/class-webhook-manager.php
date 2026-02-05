<?php
/**
 * Webhook Manager - Sistema de Webhooks
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Webhook_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Tabla de webhooks
     */
    private $table_webhooks;

    /**
     * Tabla de logs
     */
    private $table_logs;

    /**
     * Eventos disponibles
     */
    private $events = [];

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->table_webhooks = $wpdb->prefix . 'flavor_webhooks';
        $this->table_logs = $wpdb->prefix . 'flavor_webhook_logs';

        $this->init_events();
        $this->init_hooks();
    }

    /**
     * Inicializar eventos disponibles
     */
    private function init_events() {
        $this->events = [
            // Chat events
            'chat.conversation.started' => [
                'name' => 'Conversación iniciada',
                'category' => 'chat',
                'description' => 'Se dispara cuando un usuario inicia una nueva conversación',
            ],
            'chat.conversation.ended' => [
                'name' => 'Conversación finalizada',
                'category' => 'chat',
                'description' => 'Se dispara cuando una conversación se cierra',
            ],
            'chat.message.sent' => [
                'name' => 'Mensaje enviado',
                'category' => 'chat',
                'description' => 'Se dispara cuando se envía un mensaje',
            ],
            'chat.escalation.created' => [
                'name' => 'Escalación creada',
                'category' => 'chat',
                'description' => 'Se dispara cuando una conversación se escala a humano',
            ],

            // Page Builder events
            'pagebuilder.page.created' => [
                'name' => 'Página creada',
                'category' => 'pagebuilder',
                'description' => 'Se dispara cuando se crea una página con el builder',
            ],
            'pagebuilder.page.published' => [
                'name' => 'Página publicada',
                'category' => 'pagebuilder',
                'description' => 'Se dispara cuando se publica una página',
            ],
            'pagebuilder.template.applied' => [
                'name' => 'Template aplicado',
                'category' => 'pagebuilder',
                'description' => 'Se dispara cuando se aplica un template',
            ],

            // Module events
            'module.activated' => [
                'name' => 'Módulo activado',
                'category' => 'modules',
                'description' => 'Se dispara cuando se activa un módulo',
            ],
            'module.deactivated' => [
                'name' => 'Módulo desactivado',
                'category' => 'modules',
                'description' => 'Se dispara cuando se desactiva un módulo',
            ],

            // User events
            'user.registered' => [
                'name' => 'Usuario registrado',
                'category' => 'users',
                'description' => 'Se dispara cuando se registra un nuevo usuario',
            ],
            'user.profile.updated' => [
                'name' => 'Perfil actualizado',
                'category' => 'users',
                'description' => 'Se dispara cuando un usuario actualiza su perfil',
            ],

            // Form events
            'form.submitted' => [
                'name' => 'Formulario enviado',
                'category' => 'forms',
                'description' => 'Se dispara cuando se envía un formulario',
            ],

            // Notification events
            'notification.sent' => [
                'name' => 'Notificación enviada',
                'category' => 'notifications',
                'description' => 'Se dispara cuando se envía una notificación',
            ],
        ];

        $this->events = apply_filters('flavor_webhook_events', $this->events);
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_flavor_webhook_create', [$this, 'ajax_create_webhook']);
        add_action('wp_ajax_flavor_webhook_update', [$this, 'ajax_update_webhook']);
        add_action('wp_ajax_flavor_webhook_delete', [$this, 'ajax_delete_webhook']);
        add_action('wp_ajax_flavor_webhook_test', [$this, 'ajax_test_webhook']);
        add_action('wp_ajax_flavor_webhook_list', [$this, 'ajax_list_webhooks']);
        add_action('wp_ajax_flavor_webhook_logs', [$this, 'ajax_get_logs']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // WordPress hooks para eventos
        $this->register_event_hooks();
    }

    /**
     * Registrar hooks para eventos
     */
    private function register_event_hooks() {
        // Registrar action hooks personalizados
        foreach (array_keys($this->events) as $event_key) {
            $hook_name = 'flavor_' . str_replace('.', '_', $event_key);
            add_action($hook_name, function($data) use ($event_key) {
                $this->trigger($event_key, $data);
            }, 10, 1);
        }
    }

    /**
     * Crear tablas
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_webhooks = "CREATE TABLE IF NOT EXISTS {$this->table_webhooks} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            url text NOT NULL,
            secret varchar(255) DEFAULT NULL,
            events text NOT NULL,
            headers text DEFAULT NULL,
            status enum('active','inactive') DEFAULT 'active',
            retry_count int(11) DEFAULT 3,
            timeout int(11) DEFAULT 30,
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_by (created_by)
        ) $charset_collate;";

        $sql_logs = "CREATE TABLE IF NOT EXISTS {$this->table_logs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            webhook_id bigint(20) unsigned NOT NULL,
            event varchar(100) NOT NULL,
            request_url text NOT NULL,
            request_headers text DEFAULT NULL,
            request_body text DEFAULT NULL,
            response_code int(11) DEFAULT NULL,
            response_body text DEFAULT NULL,
            response_time float DEFAULT NULL,
            status enum('success','failed','pending') DEFAULT 'pending',
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY webhook_id (webhook_id),
            KEY event (event),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_webhooks);
        dbDelta($sql_logs);
    }

    /**
     * Disparar webhook
     *
     * @param string $event
     * @param array $data
     */
    public function trigger($event, $data = []) {
        global $wpdb;

        // Obtener webhooks activos para este evento
        $webhooks = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_webhooks}
            WHERE status = 'active'
            AND events LIKE %s",
            '%"' . $event . '"%'
        ));

        if (empty($webhooks)) {
            return;
        }

        foreach ($webhooks as $webhook) {
            $this->send_webhook($webhook, $event, $data);
        }
    }

    /**
     * Enviar webhook
     *
     * @param object $webhook
     * @param string $event
     * @param array $data
     */
    private function send_webhook($webhook, $event, $data) {
        global $wpdb;

        $payload = [
            'event' => $event,
            'timestamp' => current_time('c'),
            'data' => $data,
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'X-Flavor-Event' => $event,
            'X-Flavor-Delivery' => wp_generate_uuid4(),
        ];

        // Añadir firma si hay secreto
        if (!empty($webhook->secret)) {
            $signature = hash_hmac('sha256', wp_json_encode($payload), $webhook->secret);
            $headers['X-Flavor-Signature'] = 'sha256=' . $signature;
        }

        // Headers personalizados
        $custom_headers = maybe_unserialize($webhook->headers);
        if (is_array($custom_headers)) {
            $headers = array_merge($headers, $custom_headers);
        }

        // Crear log inicial
        $log_id = $wpdb->insert($this->table_logs, [
            'webhook_id' => $webhook->id,
            'event' => $event,
            'request_url' => $webhook->url,
            'request_headers' => wp_json_encode($headers),
            'request_body' => wp_json_encode($payload),
            'status' => 'pending',
        ]);
        $log_id = $wpdb->insert_id;

        // Enviar request
        $start_time = microtime(true);

        $response = wp_remote_post($webhook->url, [
            'headers' => $headers,
            'body' => wp_json_encode($payload),
            'timeout' => $webhook->timeout ?: 30,
            'sslverify' => true,
        ]);

        $response_time = microtime(true) - $start_time;

        // Actualizar log
        $update_data = [
            'response_time' => round($response_time, 4),
        ];

        if (is_wp_error($response)) {
            $update_data['status'] = 'failed';
            $update_data['error_message'] = $response->get_error_message();
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            $update_data['response_code'] = $response_code;
            $update_data['response_body'] = substr($response_body, 0, 65535);
            $update_data['status'] = ($response_code >= 200 && $response_code < 300) ? 'success' : 'failed';

            if ($update_data['status'] === 'failed') {
                $update_data['error_message'] = "HTTP {$response_code}";
            }
        }

        $wpdb->update($this->table_logs, $update_data, ['id' => $log_id]);

        // Retry si falla
        if ($update_data['status'] === 'failed' && $webhook->retry_count > 0) {
            $this->schedule_retry($webhook, $event, $data, $webhook->retry_count - 1);
        }
    }

    /**
     * Programar retry
     */
    private function schedule_retry($webhook, $event, $data, $remaining_retries) {
        // Delay exponencial: 1min, 5min, 30min
        $delays = [60, 300, 1800];
        $delay_index = $webhook->retry_count - $remaining_retries - 1;
        $delay = $delays[$delay_index] ?? 1800;

        wp_schedule_single_event(
            time() + $delay,
            'flavor_webhook_retry',
            [$webhook->id, $event, $data, $remaining_retries]
        );
    }

    /**
     * Crear webhook via AJAX
     */
    public function ajax_create_webhook() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        global $wpdb;

        $name = sanitize_text_field($_POST['name'] ?? '');
        $url = esc_url_raw($_POST['url'] ?? '');
        $events = array_map('sanitize_text_field', $_POST['events'] ?? []);
        $secret = sanitize_text_field($_POST['secret'] ?? '');

        if (empty($name) || empty($url) || empty($events)) {
            wp_send_json_error(['message' => 'Campos requeridos incompletos']);
        }

        // Validar URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(['message' => 'URL inválida']);
        }

        // Validar eventos
        foreach ($events as $event) {
            if (!isset($this->events[$event])) {
                wp_send_json_error(['message' => 'Evento inválido: ' . $event]);
            }
        }

        $result = $wpdb->insert($this->table_webhooks, [
            'name' => $name,
            'url' => $url,
            'secret' => $secret ?: null,
            'events' => wp_json_encode($events),
            'status' => 'active',
            'created_by' => get_current_user_id(),
        ]);

        if ($result === false) {
            wp_send_json_error(['message' => 'Error al crear webhook']);
        }

        wp_send_json_success([
            'id' => $wpdb->insert_id,
            'message' => 'Webhook creado correctamente',
        ]);
    }

    /**
     * Actualizar webhook via AJAX
     */
    public function ajax_update_webhook() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        global $wpdb;

        $webhook_id = intval($_POST['id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $url = esc_url_raw($_POST['url'] ?? '');
        $events = array_map('sanitize_text_field', $_POST['events'] ?? []);
        $status = sanitize_text_field($_POST['status'] ?? 'active');

        if (!$webhook_id) {
            wp_send_json_error(['message' => 'ID de webhook requerido']);
        }

        $update_data = [];

        if (!empty($name)) {
            $update_data['name'] = $name;
        }

        if (!empty($url)) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                wp_send_json_error(['message' => 'URL inválida']);
            }
            $update_data['url'] = $url;
        }

        if (!empty($events)) {
            $update_data['events'] = wp_json_encode($events);
        }

        if (in_array($status, ['active', 'inactive'])) {
            $update_data['status'] = $status;
        }

        if (isset($_POST['secret'])) {
            $update_data['secret'] = sanitize_text_field($_POST['secret']) ?: null;
        }

        if (empty($update_data)) {
            wp_send_json_error(['message' => 'Nada que actualizar']);
        }

        $result = $wpdb->update($this->table_webhooks, $update_data, ['id' => $webhook_id]);

        if ($result === false) {
            wp_send_json_error(['message' => 'Error al actualizar webhook']);
        }

        wp_send_json_success(['message' => 'Webhook actualizado']);
    }

    /**
     * Eliminar webhook via AJAX
     */
    public function ajax_delete_webhook() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        global $wpdb;

        $webhook_id = intval($_POST['id'] ?? 0);

        if (!$webhook_id) {
            wp_send_json_error(['message' => 'ID de webhook requerido']);
        }

        // Eliminar logs asociados
        $wpdb->delete($this->table_logs, ['webhook_id' => $webhook_id]);

        // Eliminar webhook
        $result = $wpdb->delete($this->table_webhooks, ['id' => $webhook_id]);

        if ($result === false) {
            wp_send_json_error(['message' => 'Error al eliminar webhook']);
        }

        wp_send_json_success(['message' => 'Webhook eliminado']);
    }

    /**
     * Probar webhook via AJAX
     */
    public function ajax_test_webhook() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        global $wpdb;

        $webhook_id = intval($_POST['id'] ?? 0);

        if (!$webhook_id) {
            wp_send_json_error(['message' => 'ID de webhook requerido']);
        }

        $webhook = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_webhooks} WHERE id = %d",
            $webhook_id
        ));

        if (!$webhook) {
            wp_send_json_error(['message' => 'Webhook no encontrado']);
        }

        // Enviar evento de prueba
        $test_data = [
            'test' => true,
            'message' => 'Este es un webhook de prueba',
            'webhook_id' => $webhook_id,
            'timestamp' => current_time('c'),
        ];

        $this->send_webhook($webhook, 'webhook.test', $test_data);

        // Obtener último log
        $last_log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_logs}
            WHERE webhook_id = %d
            ORDER BY id DESC
            LIMIT 1",
            $webhook_id
        ));

        wp_send_json_success([
            'message' => 'Webhook de prueba enviado',
            'log' => $last_log,
        ]);
    }

    /**
     * Listar webhooks via AJAX
     */
    public function ajax_list_webhooks() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        global $wpdb;

        $webhooks = $wpdb->get_results(
            "SELECT w.*,
                (SELECT COUNT(*) FROM {$this->table_logs} WHERE webhook_id = w.id) as total_calls,
                (SELECT COUNT(*) FROM {$this->table_logs} WHERE webhook_id = w.id AND status = 'success') as successful_calls
            FROM {$this->table_webhooks} w
            ORDER BY w.created_at DESC"
        );

        foreach ($webhooks as &$webhook) {
            $webhook->events = json_decode($webhook->events, true);
        }

        wp_send_json_success([
            'webhooks' => $webhooks,
            'available_events' => $this->events,
        ]);
    }

    /**
     * Obtener logs via AJAX
     */
    public function ajax_get_logs() {
        check_ajax_referer('flavor_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        global $wpdb;

        $webhook_id = intval($_POST['webhook_id'] ?? 0);
        $page = intval($_POST['page'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $where = '';
        if ($webhook_id) {
            $where = $wpdb->prepare(' WHERE webhook_id = %d', $webhook_id);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_logs}" . $where);

        $logs = $wpdb->get_results(
            "SELECT l.*, w.name as webhook_name
            FROM {$this->table_logs} l
            LEFT JOIN {$this->table_webhooks} w ON l.webhook_id = w.id
            {$where}
            ORDER BY l.created_at DESC
            LIMIT {$per_page} OFFSET {$offset}"
        );

        wp_send_json_success([
            'logs' => $logs,
            'total' => intval($total),
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
        ]);
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/webhooks', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'rest_list_webhooks'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'rest_create_webhook'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
        ]);

        register_rest_route('flavor/v1', '/webhooks/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'rest_get_webhook'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'rest_update_webhook'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'rest_delete_webhook'],
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ],
        ]);

        register_rest_route('flavor/v1', '/webhook-events', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_list_events'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    /**
     * REST: Listar webhooks
     */
    public function rest_list_webhooks($request) {
        global $wpdb;

        $webhooks = $wpdb->get_results(
            "SELECT * FROM {$this->table_webhooks} ORDER BY created_at DESC"
        );

        foreach ($webhooks as &$webhook) {
            $webhook->events = json_decode($webhook->events, true);
            unset($webhook->secret); // No exponer secreto
        }

        return rest_ensure_response($webhooks);
    }

    /**
     * REST: Listar eventos disponibles
     */
    public function rest_list_events($request) {
        return rest_ensure_response($this->events);
    }

    /**
     * Obtener eventos disponibles
     */
    public function get_events() {
        return $this->events;
    }
}

/**
 * Helper global para disparar webhooks
 *
 * @param string $event
 * @param array $data
 */
function flavor_webhook_trigger($event, $data = []) {
    do_action('flavor_' . str_replace('.', '_', $event), $data);
}
