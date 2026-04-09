<?php
/**
 * Sistema de Notificaciones - Manager Principal
 *
 * Gestiona todos los tipos de notificaciones: email, push, in-app, SMS
 *
 * @package FlavorChatIA
 * @subpackage Notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Notification_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Canales de notificación disponibles
     */
    private $channels = [];

    /**
     * Tipos de eventos registrados
     */
    private $event_types = [];

    /**
     * Cola de notificaciones pendientes
     */
    private $queue = [];

    /**
     * Tabla de notificaciones
     */
    private $table_notifications;
    private $table_preferences;
    private $table_queue;
    private $table_logs;

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;

        $this->table_notifications = $wpdb->prefix . 'flavor_notifications';
        $this->table_preferences = $wpdb->prefix . 'flavor_notification_preferences';
        $this->table_queue = $wpdb->prefix . 'flavor_notification_queue';
        $this->table_logs = $wpdb->prefix . 'flavor_notification_logs';

        $this->init();
    }

    /**
     * Inicializar el sistema
     */
    private function init() {
        // Crear tablas si no existen
        $this->create_tables();

        // Registrar canales por defecto
        $this->register_default_channels();

        // Registrar eventos por defecto
        $this->register_default_events();

        // Hooks
        add_action('init', [$this, 'process_queue']);
        add_action('wp_ajax_flavor_mark_notification_read', [$this, 'ajax_mark_read']);
        add_action('wp_ajax_flavor_get_notifications', [$this, 'ajax_get_notifications']);
        add_action('wp_ajax_flavor_update_notification_preferences', [$this, 'ajax_update_preferences']);
        add_action('wp_ajax_flavor_dismiss_notification', [$this, 'ajax_dismiss_notification']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Cron para procesar cola
        if (!wp_next_scheduled('flavor_process_notification_queue')) {
            wp_schedule_event(time(), 'every_minute', 'flavor_process_notification_queue');
        }
        add_action('flavor_process_notification_queue', [$this, 'process_queue']);

        // Hook para limpiar notificaciones antiguas
        if (!wp_next_scheduled('flavor_cleanup_old_notifications')) {
            wp_schedule_event(time(), 'daily', 'flavor_cleanup_old_notifications');
        }
        add_action('flavor_cleanup_old_notifications', [$this, 'cleanup_old_notifications']);
    }

    /**
     * Crear tablas de base de datos
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = [];

        // Tabla de notificaciones
        $sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_notifications} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            data longtext,
            icon varchar(100) DEFAULT 'dashicons-bell',
            color varchar(20) DEFAULT '#3b82f6',
            link varchar(500) DEFAULT '',
            is_read tinyint(1) DEFAULT 0,
            is_dismissed tinyint(1) DEFAULT 0,
            priority enum('low','normal','high','urgent') DEFAULT 'normal',
            expires_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            read_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY is_read (is_read),
            KEY created_at (created_at),
            KEY priority (priority)
        ) $charset_collate;";

        // Tabla de preferencias de usuario
        $sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_preferences} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            event_type varchar(100) NOT NULL,
            channel_email tinyint(1) DEFAULT 1,
            channel_push tinyint(1) DEFAULT 1,
            channel_inapp tinyint(1) DEFAULT 1,
            channel_sms tinyint(1) DEFAULT 0,
            frequency enum('instant','hourly','daily','weekly') DEFAULT 'instant',
            quiet_hours_start time DEFAULT NULL,
            quiet_hours_end time DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_event (user_id, event_type),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Tabla de cola de notificaciones
        $sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_queue} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            notification_id bigint(20) UNSIGNED DEFAULT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            channel varchar(50) NOT NULL,
            payload longtext NOT NULL,
            status enum('pending','processing','sent','failed') DEFAULT 'pending',
            attempts int DEFAULT 0,
            max_attempts int DEFAULT 3,
            scheduled_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime DEFAULT NULL,
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY scheduled_at (scheduled_at),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Tabla de logs
        $sql[] = "CREATE TABLE IF NOT EXISTS {$this->table_logs} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            notification_id bigint(20) UNSIGNED DEFAULT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            channel varchar(50) NOT NULL,
            event_type varchar(100) NOT NULL,
            status varchar(20) NOT NULL,
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY notification_id (notification_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    /**
     * Registrar canales por defecto
     */
    private function register_default_channels() {
        $this->channels = [
            'email' => [
                'label' => __('Email', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-email',
                'handler' => [$this, 'send_email'],
                'enabled' => true,
            ],
            'push' => [
                'label' => __('Push Notification', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-smartphone',
                'handler' => class_exists('Flavor_Push_Notification_Channel')
                    ? [new Flavor_Push_Notification_Channel(), 'handle_push_from_queue']
                    : null,
                'enabled' => class_exists('Flavor_Push_Notification_Channel'),
            ],
            'inapp' => [
                'label' => __('In-App', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-bell',
                'handler' => [$this, 'send_inapp'],
                'enabled' => true,
            ],
            'sms' => [
                'label' => __('SMS', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-phone',
                'handler' => [$this, 'send_sms'],
                'enabled' => false, // Requiere configuración
            ],
        ];

        // Permitir añadir canales personalizados
        $this->channels = apply_filters('flavor_notification_channels', $this->channels);
    }

    /**
     * Registrar eventos por defecto
     */
    private function register_default_events() {
        $this->event_types = [
            // Sistema
            'system_update' => [
                'label' => __('Actualizaciones del sistema', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'system',
                'default_channels' => ['email', 'inapp'],
                'icon' => 'dashicons-update',
            ],
            'security_alert' => [
                'label' => __('Alertas de seguridad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'system',
                'default_channels' => ['email', 'push', 'inapp'],
                'icon' => 'dashicons-shield',
                'priority' => 'high',
            ],

            // Usuarios
            'user_welcome' => [
                'label' => __('Bienvenida a nuevos usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'users',
                'default_channels' => ['email', 'inapp'],
                'icon' => 'dashicons-admin-users',
            ],
            'user_mention' => [
                'label' => __('Menciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'users',
                'default_channels' => ['push', 'inapp'],
                'icon' => 'dashicons-format-chat',
            ],

            // Chat IA
            'chat_escalation' => [
                'label' => __('Escalación de chat', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'chat',
                'default_channels' => ['email', 'push'],
                'icon' => 'dashicons-sos',
                'priority' => 'high',
            ],
            'chat_response' => [
                'label' => __('Respuesta del asistente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'chat',
                'default_channels' => ['inapp'],
                'icon' => 'dashicons-format-chat',
            ],

            // Módulos
            'module_event' => [
                'label' => __('Eventos de módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'modules',
                'default_channels' => ['inapp'],
                'icon' => 'dashicons-admin-generic',
            ],
            'new_content' => [
                'label' => __('Nuevo contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'modules',
                'default_channels' => ['email', 'inapp'],
                'icon' => 'dashicons-admin-post',
            ],

            // Marketplace
            'order_status' => [
                'label' => __('Estado de pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'commerce',
                'default_channels' => ['email', 'push', 'inapp'],
                'icon' => 'dashicons-cart',
            ],
            'new_message' => [
                'label' => __('Nuevos mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'commerce',
                'default_channels' => ['push', 'inapp'],
                'icon' => 'dashicons-email-alt',
            ],

            // Eventos
            'event_reminder' => [
                'label' => __('Recordatorios de eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'events',
                'default_channels' => ['email', 'push'],
                'icon' => 'dashicons-calendar-alt',
            ],
            'event_update' => [
                'label' => __('Actualizaciones de eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'events',
                'default_channels' => ['email', 'inapp'],
                'icon' => 'dashicons-calendar',
            ],

            // Comunidad
            'community_activity' => [
                'label' => __('Actividad de comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'community',
                'default_channels' => ['inapp'],
                'icon' => 'dashicons-groups',
            ],
            'group_invite' => [
                'label' => __('Invitaciones a grupos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category' => 'community',
                'default_channels' => ['email', 'push', 'inapp'],
                'icon' => 'dashicons-buddicons-groups',
            ],
        ];

        // Permitir añadir eventos personalizados
        $this->event_types = apply_filters('flavor_notification_events', $this->event_types);
    }

    /**
     * Enviar notificación
     *
     * @param int|array $user_ids ID(s) de usuario(s)
     * @param string $event_type Tipo de evento
     * @param array $data Datos de la notificación
     * @return bool|int
     */
    public function send($user_ids, $event_type, $data = []) {
        if (!is_array($user_ids)) {
            $user_ids = [$user_ids];
        }

        $defaults = [
            'title' => '',
            'message' => '',
            'icon' => 'dashicons-bell',
            'color' => '#3b82f6',
            'link' => '',
            'data' => [],
            'priority' => 'normal',
            'expires_at' => null,
            'channels' => null, // null = usar preferencias del usuario
        ];

        $data = wp_parse_args($data, $defaults);

        // Obtener configuración del evento
        $event_config = $this->event_types[$event_type] ?? [];
        if (!empty($event_config['icon']) && $data['icon'] === 'dashicons-bell') {
            $data['icon'] = $event_config['icon'];
        }
        if (!empty($event_config['priority']) && $data['priority'] === 'normal') {
            $data['priority'] = $event_config['priority'];
        }

        $sent_count = 0;

        foreach ($user_ids as $user_id) {
            // Verificar preferencias del usuario
            $preferences = $this->get_user_preferences($user_id, $event_type);

            // Determinar canales a usar
            $channels = $data['channels'] ?? $this->get_active_channels($preferences, $event_config);

            if (empty($channels)) {
                continue;
            }

            // Crear notificación en base de datos
            $notification_id = $this->create_notification($user_id, $event_type, $data);

            if (!$notification_id) {
                continue;
            }

            // Encolar para cada canal
            foreach ($channels as $channel) {
                if (!$this->is_channel_enabled($channel)) {
                    continue;
                }

                // Verificar horas de silencio
                if ($this->is_quiet_hours($preferences)) {
                    // Programar para después de las horas de silencio
                    $scheduled_at = $this->get_next_available_time($preferences);
                } else {
                    $scheduled_at = current_time('mysql');
                }

                $this->enqueue_notification($notification_id, $user_id, $channel, $data, $scheduled_at);
            }

            $sent_count++;
        }

        return $sent_count;
    }

    /**
     * Crear notificación en BD
     */
    private function create_notification($user_id, $event_type, $data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table_notifications,
            [
                'user_id' => $user_id,
                'type' => $event_type,
                'title' => $data['title'],
                'message' => $data['message'],
                'data' => maybe_serialize($data['data']),
                'icon' => $data['icon'],
                'color' => $data['color'],
                'link' => $data['link'],
                'priority' => $data['priority'],
                'expires_at' => $data['expires_at'],
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Encolar notificación
     */
    private function enqueue_notification($notification_id, $user_id, $channel, $data, $scheduled_at) {
        global $wpdb;

        $payload = [
            'notification_id' => $notification_id,
            'user_id' => $user_id,
            'channel' => $channel,
            'title' => $data['title'],
            'message' => $data['message'],
            'icon' => $data['icon'],
            'color' => $data['color'],
            'link' => $data['link'],
            'data' => $data['data'],
        ];

        return $wpdb->insert(
            $this->table_queue,
            [
                'notification_id' => $notification_id,
                'user_id' => $user_id,
                'channel' => $channel,
                'payload' => maybe_serialize($payload),
                'scheduled_at' => $scheduled_at,
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );
    }

    /**
     * Procesar cola de notificaciones
     */
    public function process_queue() {
        global $wpdb;

        // Obtener notificaciones pendientes
        $pending = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_queue}
            WHERE status = 'pending'
            AND scheduled_at <= %s
            AND attempts < max_attempts
            ORDER BY scheduled_at ASC
            LIMIT 50",
            current_time('mysql')
        ));

        foreach ($pending as $item) {
            $this->process_queue_item($item);
        }
    }

    /**
     * Procesar item de cola
     */
    private function process_queue_item($item) {
        global $wpdb;

        // Marcar como procesando
        $wpdb->update(
            $this->table_queue,
            ['status' => 'processing', 'attempts' => $item->attempts + 1],
            ['id' => $item->id]
        );

        $payload = maybe_unserialize($item->payload);
        $channel = $item->channel;
        $success = false;
        $error_message = '';

        try {
            // Ejecutar handler del canal
            if (isset($this->channels[$channel]['handler'])) {
                $success = call_user_func($this->channels[$channel]['handler'], $payload);
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }

        // Actualizar estado
        $new_status = $success ? 'sent' : ($item->attempts + 1 >= $item->max_attempts ? 'failed' : 'pending');

        $wpdb->update(
            $this->table_queue,
            [
                'status' => $new_status,
                'processed_at' => $success ? current_time('mysql') : null,
                'error_message' => $error_message,
            ],
            ['id' => $item->id]
        );

        // Log
        $this->log_notification($item->notification_id, $item->user_id, $channel, $payload['type'] ?? 'unknown', $new_status, $error_message);
    }

    /**
     * Enviar email
     */
    public function send_email($payload) {
        $user = get_user_by('ID', $payload['user_id']);
        if (!$user) {
            return false;
        }

        $to = $user->user_email;
        $subject = $payload['title'];
        $message = $this->build_email_template($payload);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        ];

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Construir template de email
     */
    private function build_email_template($payload) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $logo_url = get_option('flavor_logo_url', '');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f5f5f5;">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
                <!-- Header -->
                <tr>
                    <td style="background: linear-gradient(135deg, <?php echo esc_attr($payload['color']); ?> 0%, <?php echo esc_attr($this->adjust_color($payload['color'], -20)); ?> 100%); padding: 30px; text-align: center;">
                        <?php if ($logo_url): ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" style="max-height: 50px; margin-bottom: 15px;">
                        <?php endif; ?>
                        <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;">
                            <?php echo esc_html($payload['title']); ?>
                        </h1>
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding: 40px 30px;">
                        <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                            <?php echo nl2br(esc_html($payload['message'])); ?>
                        </p>

                        <?php if (!empty($payload['link'])): ?>
                            <p style="text-align: center; margin: 30px 0;">
                                <a href="<?php echo esc_url($payload['link']); ?>" style="display: inline-block; background-color: <?php echo esc_attr($payload['color']); ?>; color: #ffffff; text-decoration: none; padding: 14px 30px; border-radius: 8px; font-weight: 600; font-size: 16px;">
                                    Ver más detalles
                                </a>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background-color: #f8f9fa; padding: 25px 30px; text-align: center; border-top: 1px solid #e9ecef;">
                        <p style="color: #6c757d; font-size: 14px; margin: 0 0 10px;">
                            <?php echo esc_html($site_name); ?>
                        </p>
                        <p style="color: #adb5bd; font-size: 12px; margin: 0;">
                            <a href="<?php echo esc_url($site_url); ?>/notification-preferences" style="color: #6c757d; text-decoration: underline;">
                                Gestionar preferencias de notificaciones
                            </a>
                        </p>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Enviar push notification via Firebase Cloud Messaging
     *
     * Delega el envio al canal FCM (Flavor_Push_Notification_Channel).
     */
    public function send_push($payload) {
        if (class_exists('Flavor_Push_Notification_Channel')) {
            $canal_push = new Flavor_Push_Notification_Channel();
            return $canal_push->handle_push_from_queue($payload);
        }
        // Fallback: disparar accion para integracion externa
        do_action('flavor_send_push_notification', $payload);
        return true;
    }

    /**
     * Enviar notificación in-app
     */
    public function send_inapp($payload) {
        // Ya está guardada en BD, solo disparar evento para actualización en tiempo real
        do_action('flavor_inapp_notification_sent', $payload);
        return true;
    }

    /**
     * Enviar SMS
     */
    public function send_sms($payload) {
        $sms_config = get_option('flavor_sms_config', []);

        if (empty($sms_config['enabled'])) {
            return false;
        }

        // Disparar acción para integración con Twilio/similar
        do_action('flavor_send_sms_notification', $payload, $sms_config);

        return true;
    }

    /**
     * Obtener notificaciones del usuario
     */
    public function get_user_notifications($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'unread_only' => false,
            'type' => null,
            'include_dismissed' => false,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['user_id = %d'];
        $params = [$user_id];

        if ($args['unread_only']) {
            $where[] = 'is_read = 0';
        }

        if (!$args['include_dismissed']) {
            $where[] = 'is_dismissed = 0';
        }

        if ($args['type']) {
            $where[] = 'type = %s';
            $params[] = $args['type'];
        }

        // Excluir expiradas
        $where[] = '(expires_at IS NULL OR expires_at > %s)';
        $params[] = current_time('mysql');

        $where_clause = implode(' AND ', $where);
        $params[] = $args['limit'];
        $params[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_notifications}
            WHERE {$where_clause}
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d",
            $params
        ));
    }

    /**
     * Contar notificaciones no leídas
     */
    public function get_unread_count($user_id) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_notifications}
            WHERE user_id = %d AND is_read = 0 AND is_dismissed = 0
            AND (expires_at IS NULL OR expires_at > %s)",
            $user_id,
            current_time('mysql')
        ));
    }

    /**
     * Marcar como leída
     */
    public function mark_as_read($notification_id, $user_id = null) {
        global $wpdb;

        $where = ['id' => $notification_id];
        if ($user_id) {
            $where['user_id'] = $user_id;
        }

        return $wpdb->update(
            $this->table_notifications,
            ['is_read' => 1, 'read_at' => current_time('mysql')],
            $where
        );
    }

    /**
     * Marcar todas como leídas
     */
    public function mark_all_as_read($user_id) {
        global $wpdb;

        return $wpdb->update(
            $this->table_notifications,
            ['is_read' => 1, 'read_at' => current_time('mysql')],
            ['user_id' => $user_id, 'is_read' => 0]
        );
    }

    /**
     * Descartar notificación
     */
    public function dismiss($notification_id, $user_id = null) {
        global $wpdb;

        $where = ['id' => $notification_id];
        if ($user_id) {
            $where['user_id'] = $user_id;
        }

        return $wpdb->update(
            $this->table_notifications,
            ['is_dismissed' => 1],
            $where
        );
    }

    /**
     * Obtener preferencias del usuario
     */
    public function get_user_preferences($user_id, $event_type = null) {
        global $wpdb;

        if ($event_type) {
            $pref = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->table_preferences}
                WHERE user_id = %d AND event_type = %s",
                $user_id,
                $event_type
            ));

            if ($pref) {
                return $pref;
            }

            // Devolver defaults del evento
            $event = $this->event_types[$event_type] ?? [];
            return (object) [
                'user_id' => $user_id,
                'event_type' => $event_type,
                'channel_email' => in_array('email', $event['default_channels'] ?? []) ? 1 : 0,
                'channel_push' => in_array('push', $event['default_channels'] ?? []) ? 1 : 0,
                'channel_inapp' => in_array('inapp', $event['default_channels'] ?? []) ? 1 : 0,
                'channel_sms' => 0,
                'frequency' => 'instant',
                'quiet_hours_start' => null,
                'quiet_hours_end' => null,
            ];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_preferences} WHERE user_id = %d",
            $user_id
        ));
    }

    /**
     * Actualizar preferencias del usuario
     */
    public function update_user_preferences($user_id, $event_type, $preferences) {
        global $wpdb;

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_preferences}
            WHERE user_id = %d AND event_type = %s",
            $user_id,
            $event_type
        ));

        $data = [
            'user_id' => $user_id,
            'event_type' => $event_type,
            'channel_email' => isset($preferences['email']) ? (int) $preferences['email'] : 1,
            'channel_push' => isset($preferences['push']) ? (int) $preferences['push'] : 1,
            'channel_inapp' => isset($preferences['inapp']) ? (int) $preferences['inapp'] : 1,
            'channel_sms' => isset($preferences['sms']) ? (int) $preferences['sms'] : 0,
            'frequency' => $preferences['frequency'] ?? 'instant',
            'quiet_hours_start' => $preferences['quiet_hours_start'] ?? null,
            'quiet_hours_end' => $preferences['quiet_hours_end'] ?? null,
        ];

        if ($existing) {
            return $wpdb->update($this->table_preferences, $data, ['id' => $existing]);
        } else {
            return $wpdb->insert($this->table_preferences, $data);
        }
    }

    /**
     * AJAX: Marcar como leída
     */
    public function ajax_mark_read() {
        check_ajax_referer('flavor_notifications', 'nonce');

        $notification_id = intval($_POST['notification_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$user_id) {
            wp_send_json_error(['message' => __('No autorizado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if ($notification_id === 0) {
            $this->mark_all_as_read($user_id);
        } else {
            $this->mark_as_read($notification_id, $user_id);
        }

        wp_send_json_success([
            'unread_count' => $this->get_unread_count($user_id),
        ]);
    }

    /**
     * AJAX: Obtener notificaciones
     */
    public function ajax_get_notifications() {
        check_ajax_referer('flavor_notifications', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('No autorizado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $args = [
            'limit' => intval($_POST['limit'] ?? 20),
            'offset' => intval($_POST['offset'] ?? 0),
            'unread_only' => !empty($_POST['unread_only']),
        ];

        $notifications = $this->get_user_notifications($user_id, $args);
        $unread_count = $this->get_unread_count($user_id);

        wp_send_json_success([
            'notifications' => $notifications,
            'unread_count' => $unread_count,
        ]);
    }

    /**
     * AJAX: Actualizar preferencias
     */
    public function ajax_update_preferences() {
        check_ajax_referer('flavor_notifications', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('No autorizado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $event_type = sanitize_text_field($_POST['event_type'] ?? '');
        $preferences = $_POST['preferences'] ?? [];

        if (empty($event_type)) {
            wp_send_json_error(['message' => __('Tipo de evento requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $this->update_user_preferences($user_id, $event_type, $preferences);

        wp_send_json_success(['message' => __('Preferencias actualizadas', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Descartar notificación
     */
    public function ajax_dismiss_notification() {
        check_ajax_referer('flavor_notifications', 'nonce');

        $notification_id = intval($_POST['notification_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$user_id || !$notification_id) {
            wp_send_json_error(['message' => __('Datos inválidos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $this->dismiss($notification_id, $user_id);

        wp_send_json_success([
            'unread_count' => $this->get_unread_count($user_id),
        ]);
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor-notifications/v1', '/notifications', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'rest_get_notifications'],
            'permission_callback' => [$this, 'rest_permission_check'],
        ]);

        register_rest_route('flavor-notifications/v1', '/notifications/(?P<id>\d+)/read', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'rest_mark_read'],
            'permission_callback' => [$this, 'rest_permission_check'],
        ]);

        register_rest_route('flavor-notifications/v1', '/preferences', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'rest_get_preferences'],
            'permission_callback' => [$this, 'rest_permission_check'],
        ]);

        register_rest_route('flavor-notifications/v1', '/preferences', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'rest_update_preferences'],
            'permission_callback' => [$this, 'rest_permission_check'],
        ]);

        register_rest_route('flavor-notifications/v1', '/unread-count', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'rest_get_unread_count'],
            'permission_callback' => [$this, 'rest_permission_check'],
        ]);
    }

    public function rest_permission_check() {
        return is_user_logged_in();
    }

    public function rest_get_notifications($request) {
        $user_id = get_current_user_id();
        $notifications = $this->get_user_notifications($user_id, [
            'limit' => $request->get_param('limit') ?? 20,
            'offset' => $request->get_param('offset') ?? 0,
            'unread_only' => $request->get_param('unread_only') ?? false,
        ]);

        return rest_ensure_response([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $this->get_unread_count($user_id),
        ]);
    }

    public function rest_mark_read($request) {
        $user_id = get_current_user_id();
        $notification_id = $request->get_param('id');

        $this->mark_as_read($notification_id, $user_id);

        return rest_ensure_response([
            'success' => true,
            'unread_count' => $this->get_unread_count($user_id),
        ]);
    }

    public function rest_get_preferences($request) {
        $user_id = get_current_user_id();
        $preferences = $this->get_user_preferences($user_id);

        return rest_ensure_response([
            'success' => true,
            'preferences' => $preferences,
            'event_types' => $this->event_types,
        ]);
    }

    public function rest_update_preferences($request) {
        $user_id = get_current_user_id();
        $event_type = $request->get_param('event_type');
        $preferences = $request->get_param('preferences');

        $this->update_user_preferences($user_id, $event_type, $preferences);

        return rest_ensure_response(['success' => true]);
    }

    public function rest_get_unread_count($request) {
        return rest_ensure_response([
            'success' => true,
            'count' => $this->get_unread_count(get_current_user_id()),
        ]);
    }

    /**
     * Log de notificación
     */
    private function log_notification($notification_id, $user_id, $channel, $event_type, $status, $details = '') {
        global $wpdb;

        $wpdb->insert(
            $this->table_logs,
            [
                'notification_id' => $notification_id,
                'user_id' => $user_id,
                'channel' => $channel,
                'event_type' => $event_type,
                'status' => $status,
                'details' => $details,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Limpiar notificaciones antiguas
     */
    public function cleanup_old_notifications() {
        global $wpdb;

        // Eliminar notificaciones leídas de más de 30 días
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_notifications}
            WHERE is_read = 1 AND read_at < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));

        // Eliminar notificaciones descartadas de más de 7 días
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_notifications}
            WHERE is_dismissed = 1 AND created_at < %s",
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));

        // Eliminar cola procesada de más de 7 días
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_queue}
            WHERE status IN ('sent', 'failed') AND processed_at < %s",
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));

        // Eliminar logs de más de 90 días
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_logs} WHERE created_at < %s",
            date('Y-m-d H:i:s', strtotime('-90 days'))
        ));
    }

    /**
     * Helpers
     */
    private function get_active_channels($preferences, $event_config) {
        $channels = [];

        if ($preferences->channel_email) $channels[] = 'email';
        if ($preferences->channel_push) $channels[] = 'push';
        if ($preferences->channel_inapp) $channels[] = 'inapp';
        if ($preferences->channel_sms) $channels[] = 'sms';

        return $channels;
    }

    private function is_channel_enabled($channel) {
        return !empty($this->channels[$channel]['enabled']);
    }

    private function is_quiet_hours($preferences) {
        if (empty($preferences->quiet_hours_start) || empty($preferences->quiet_hours_end)) {
            return false;
        }

        $now = current_time('H:i:s');
        return $now >= $preferences->quiet_hours_start && $now <= $preferences->quiet_hours_end;
    }

    private function get_next_available_time($preferences) {
        $end_time = $preferences->quiet_hours_end;
        $today = current_time('Y-m-d');

        return $today . ' ' . $end_time;
    }

    private function adjust_color($hex, $steps) {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2)) + $steps;
        $g = hexdec(substr($hex, 2, 2)) + $steps;
        $b = hexdec(substr($hex, 4, 2)) + $steps;

        $r = max(0, min(255, $r));
        $g = max(0, min(255, $g));
        $b = max(0, min(255, $b));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Getters públicos
     */
    public function get_channels() {
        return $this->channels;
    }

    public function get_event_types() {
        return $this->event_types;
    }

    /**
     * Registrar nuevo tipo de evento
     */
    public function register_event_type($type, $config) {
        $this->event_types[$type] = wp_parse_args($config, [
            'label' => $type,
            'category' => 'custom',
            'default_channels' => ['inapp'],
            'icon' => 'dashicons-bell',
        ]);
    }

    /**
     * Registrar nuevo canal
     */
    public function register_channel($id, $config) {
        $this->channels[$id] = wp_parse_args($config, [
            'label' => $id,
            'icon' => 'dashicons-megaphone',
            'handler' => null,
            'enabled' => true,
        ]);
    }
}

// Registrar cron interval
add_filter('cron_schedules', function($schedules) {
    $schedules['every_minute'] = [
        'interval' => 60,
        'display' => __('Cada minuto', FLAVOR_PLATFORM_TEXT_DOMAIN),
    ];
    return $schedules;
});

// Inicializar
Flavor_Notification_Manager::get_instance();

// Helper function global
function flavor_notify($user_ids, $event_type, $data = []) {
    return Flavor_Notification_Manager::get_instance()->send($user_ids, $event_type, $data);
}
