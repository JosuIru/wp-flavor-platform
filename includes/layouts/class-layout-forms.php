<?php
/**
 * Layout Forms - Handlers para formularios de layouts
 *
 * Procesa formularios de newsletter, contacto y otros
 * integrados en los layouts del plugin.
 *
 * @package FlavorChatIA
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Layout_Forms {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Tabla de suscriptores
     */
    private $table_subscribers;

    /**
     * Tabla de mensajes de contacto
     */
    private $table_contact;

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
        $this->table_subscribers = $wpdb->prefix . 'flavor_newsletter_subscribers';
        $this->table_contact = $wpdb->prefix . 'flavor_contact_messages';

        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_flavor_newsletter_subscribe', [$this, 'ajax_newsletter_subscribe']);
        add_action('wp_ajax_nopriv_flavor_newsletter_subscribe', [$this, 'ajax_newsletter_subscribe']);

        add_action('wp_ajax_flavor_contact_submit', [$this, 'ajax_contact_submit']);
        add_action('wp_ajax_nopriv_flavor_contact_submit', [$this, 'ajax_contact_submit']);

        // Encolar scripts con datos localizados
        add_action('wp_enqueue_scripts', [$this, 'localize_scripts'], 20);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Crear tablas
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de suscriptores newsletter
        $sql_subscribers = "CREATE TABLE IF NOT EXISTS {$this->table_subscribers} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            name varchar(255) DEFAULT NULL,
            status enum('active','unsubscribed','pending') DEFAULT 'active',
            source varchar(100) DEFAULT 'footer',
            ip_address varchar(45) DEFAULT NULL,
            subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
            unsubscribed_at datetime DEFAULT NULL,
            confirmation_token varchar(64) DEFAULT NULL,
            confirmed_at datetime DEFAULT NULL,
            metadata text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY status (status),
            KEY subscribed_at (subscribed_at)
        ) $charset_collate;";

        // Tabla de mensajes de contacto
        $sql_contact = "CREATE TABLE IF NOT EXISTS {$this->table_contact} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) DEFAULT NULL,
            subject varchar(255) DEFAULT NULL,
            message text NOT NULL,
            status enum('new','read','replied','archived') DEFAULT 'new',
            source varchar(100) DEFAULT 'footer',
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            read_at datetime DEFAULT NULL,
            replied_at datetime DEFAULT NULL,
            metadata text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY email (email),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_subscribers);
        dbDelta($sql_contact);
    }

    /**
     * Localizar scripts
     */
    public function localize_scripts() {
        wp_localize_script('flavor-layouts', 'flavorLayouts', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_forms_nonce'),
            'i18n' => [
                'subscribing' => __('Suscribiendo...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subscribed' => __('¡Suscrito correctamente!', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sending' => __('Enviando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sent' => __('¡Mensaje enviado!', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'invalid_email' => __('Por favor, introduce un email válido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'required_fields' => __('Por favor, completa todos los campos requeridos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * AJAX: Suscribir a newsletter
     */
    public function ajax_newsletter_subscribe() {
        // Verificar nonce
        if (!check_ajax_referer('flavor_forms_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Verificación de seguridad fallida. Recarga la página e inténtalo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }

        // Obtener y sanitizar email
        $email = sanitize_email($_POST['email'] ?? '');

        if (!is_email($email)) {
            wp_send_json_error([
                'message' => __('Por favor, introduce un email válido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }

        // Verificar si ya está suscrito
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_subscribers} WHERE email = %s",
            $email
        ));

        if ($existing) {
            // Reactivar si estaba desuscrito
            $status = $wpdb->get_var($wpdb->prepare(
                "SELECT status FROM {$this->table_subscribers} WHERE id = %d",
                $existing
            ));

            if ($status === 'unsubscribed') {
                $wpdb->update(
                    $this->table_subscribers,
                    [
                        'status' => 'active',
                        'unsubscribed_at' => null,
                        'subscribed_at' => current_time('mysql'),
                    ],
                    ['id' => $existing]
                );

                wp_send_json_success([
                    'message' => __('¡Bienvenido de nuevo! Has sido resuscrito a nuestra newsletter.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ]);
            }

            wp_send_json_success([
                'message' => __('Este email ya está suscrito a nuestra newsletter.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }

        // Nombre opcional
        $name = sanitize_text_field($_POST['name'] ?? '');

        // Generar token de confirmación (para double opt-in futuro)
        $token = wp_generate_password(32, false);

        // Insertar suscriptor
        $result = $wpdb->insert($this->table_subscribers, [
            'email' => $email,
            'name' => $name ?: null,
            'status' => 'active',
            'source' => sanitize_text_field($_POST['source'] ?? 'footer'),
            'ip_address' => $this->get_client_ip(),
            'confirmation_token' => $token,
            'metadata' => wp_json_encode([
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'page_url' => esc_url_raw($_POST['page_url'] ?? ''),
            ]),
        ]);

        if ($result === false) {
            wp_send_json_error([
                'message' => __('Error al guardar la suscripción. Por favor, inténtalo más tarde.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }

        // Disparar acción para integraciones (Mailchimp, etc.)
        do_action('flavor_newsletter_subscribed', $email, $name, $wpdb->insert_id);

        // Enviar email de bienvenida
        $this->send_welcome_email($email, $name);

        wp_send_json_success([
            'message' => __('¡Gracias por suscribirte! Recibirás nuestras novedades en tu email.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * AJAX: Enviar formulario de contacto
     */
    public function ajax_contact_submit() {
        // Verificar nonce
        if (!check_ajax_referer('flavor_forms_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Verificación de seguridad fallida. Recarga la página e inténtalo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }

        // Obtener y sanitizar datos
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        // Validar campos requeridos
        if (empty($name) || empty($email) || empty($message)) {
            wp_send_json_error([
                'message' => __('Por favor, completa todos los campos requeridos (nombre, email y mensaje).', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }

        if (!is_email($email)) {
            wp_send_json_error([
                'message' => __('Por favor, introduce un email válido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }

        // Rate limiting simple
        $rate_key = 'flavor_contact_' . md5($this->get_client_ip());
        $rate_count = get_transient($rate_key);

        if ($rate_count && $rate_count >= 5) {
            wp_send_json_error([
                'message' => __('Has enviado demasiados mensajes. Por favor, espera unos minutos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }

        set_transient($rate_key, ($rate_count ?: 0) + 1, 5 * MINUTE_IN_SECONDS);

        // Guardar en base de datos
        global $wpdb;
        $result = $wpdb->insert($this->table_contact, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone ?: null,
            'subject' => $subject ?: null,
            'message' => $message,
            'status' => 'new',
            'source' => sanitize_text_field($_POST['source'] ?? 'footer'),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'metadata' => wp_json_encode([
                'page_url' => esc_url_raw($_POST['page_url'] ?? ''),
            ]),
        ]);

        if ($result === false) {
            wp_send_json_error([
                'message' => __('Error al enviar el mensaje. Por favor, inténtalo más tarde.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }

        $message_id = $wpdb->insert_id;

        // Disparar acción
        do_action('flavor_contact_submitted', $message_id, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message,
        ]);

        // Enviar notificación al admin
        $this->send_contact_notification($message_id, $name, $email, $subject, $message);

        // Enviar confirmación al usuario
        $this->send_contact_confirmation($email, $name);

        wp_send_json_success([
            'message' => __('¡Gracias por tu mensaje! Te responderemos lo antes posible.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'message_id' => $message_id,
        ]);
    }

    /**
     * Enviar email de bienvenida
     */
    private function send_welcome_email($email, $name) {
        $settings = get_option('flavor_layout_settings', []);
        $site_name = get_bloginfo('name');

        $subject = sprintf(
            __('¡Bienvenido a %s!', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $site_name
        );

        $greeting = $name ? sprintf(__('Hola %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $name) : __('Hola', FLAVOR_PLATFORM_TEXT_DOMAIN);

        $message = sprintf(
            "%s,\n\n" .
            __('Gracias por suscribirte a nuestra newsletter. A partir de ahora recibirás nuestras últimas novedades y ofertas exclusivas.', FLAVOR_PLATFORM_TEXT_DOMAIN) .
            "\n\n" .
            __('Si no te suscribiste, puedes ignorar este mensaje.', FLAVOR_PLATFORM_TEXT_DOMAIN) .
            "\n\n" .
            __('Saludos,', FLAVOR_PLATFORM_TEXT_DOMAIN) .
            "\n" .
            $site_name,
            $greeting
        );

        wp_mail($email, $subject, $message);
    }

    /**
     * Enviar notificación de contacto al admin
     */
    private function send_contact_notification($message_id, $name, $email, $subject, $message) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');

        $email_subject = sprintf(
            __('[%s] Nuevo mensaje de contacto de %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $site_name,
            $name
        );

        $body = sprintf(
            __('Has recibido un nuevo mensaje de contacto:', FLAVOR_PLATFORM_TEXT_DOMAIN) . "\n\n" .
            __('Nombre: %s', FLAVOR_PLATFORM_TEXT_DOMAIN) . "\n" .
            __('Email: %s', FLAVOR_PLATFORM_TEXT_DOMAIN) . "\n" .
            __('Asunto: %s', FLAVOR_PLATFORM_TEXT_DOMAIN) . "\n\n" .
            __('Mensaje:', FLAVOR_PLATFORM_TEXT_DOMAIN) . "\n%s\n\n" .
            __('Responder a este mensaje: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $name,
            $email,
            $subject ?: __('(Sin asunto)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $message,
            admin_url('admin.php?page=flavor-contact-messages&id=' . $message_id)
        );

        wp_mail($admin_email, $email_subject, $body, [
            'Reply-To: ' . $name . ' <' . $email . '>',
        ]);
    }

    /**
     * Enviar confirmación al usuario
     */
    private function send_contact_confirmation($email, $name) {
        $site_name = get_bloginfo('name');

        $subject = sprintf(
            __('Hemos recibido tu mensaje - %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $site_name
        );

        $greeting = $name ? sprintf(__('Hola %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $name) : __('Hola', FLAVOR_PLATFORM_TEXT_DOMAIN);

        $message = sprintf(
            "%s,\n\n" .
            __('Gracias por ponerte en contacto con nosotros. Hemos recibido tu mensaje y te responderemos lo antes posible.', FLAVOR_PLATFORM_TEXT_DOMAIN) .
            "\n\n" .
            __('Saludos,', FLAVOR_PLATFORM_TEXT_DOMAIN) .
            "\n" .
            __('El equipo de %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $greeting,
            $site_name
        );

        wp_mail($email, $subject, $message);
    }

    /**
     * Obtener IP del cliente
     */
    private function get_client_ip() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/newsletter/subscribe', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_newsletter_subscribe'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/contact', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_contact_submit'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // Admin endpoints
        register_rest_route('flavor/v1', '/newsletter/subscribers', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_subscribers'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('flavor/v1', '/contact/messages', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_messages'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }

    /**
     * REST: Suscribir newsletter
     */
    public function rest_newsletter_subscribe($request) {
        $email = sanitize_email($request->get_param('email'));
        $name = sanitize_text_field($request->get_param('name') ?? '');

        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Email inválido', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 400]);
        }

        // Simular POST para reutilizar lógica
        $_POST['email'] = $email;
        $_POST['name'] = $name;
        $_POST['nonce'] = wp_create_nonce('flavor_forms_nonce');

        ob_start();
        $this->ajax_newsletter_subscribe();
        $response = ob_get_clean();

        $data = json_decode($response, true);
        return rest_ensure_response($data);
    }

    /**
     * REST: Obtener suscriptores
     */
    public function rest_get_subscribers($request) {
        global $wpdb;

        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;
        $status = $request->get_param('status');
        $offset = ($page - 1) * $per_page;

        $where = '';
        if ($status) {
            $where = $wpdb->prepare(' WHERE status = %s', $status);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subscribers}" . $where);

        $subscribers = $wpdb->get_results(
            "SELECT id, email, name, status, source, subscribed_at, unsubscribed_at
            FROM {$this->table_subscribers}
            {$where}
            ORDER BY subscribed_at DESC
            LIMIT {$per_page} OFFSET {$offset}"
        );

        return rest_ensure_response([
            'subscribers' => $subscribers,
            'total' => intval($total),
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
        ]);
    }

    /**
     * REST: Obtener mensajes de contacto
     */
    public function rest_get_messages($request) {
        global $wpdb;

        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;
        $status = $request->get_param('status');
        $offset = ($page - 1) * $per_page;

        $where = '';
        if ($status) {
            $where = $wpdb->prepare(' WHERE status = %s', $status);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_contact}" . $where);

        $messages = $wpdb->get_results(
            "SELECT id, name, email, phone, subject, message, status, created_at
            FROM {$this->table_contact}
            {$where}
            ORDER BY created_at DESC
            LIMIT {$per_page} OFFSET {$offset}"
        );

        return rest_ensure_response([
            'messages' => $messages,
            'total' => intval($total),
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
        ]);
    }

    /**
     * Exportar suscriptores a CSV
     */
    public function export_subscribers_csv() {
        if (!current_user_can('manage_options')) {
            return false;
        }

        global $wpdb;
        $subscribers = $wpdb->get_results(
            "SELECT email, name, status, source, subscribed_at
            FROM {$this->table_subscribers}
            WHERE status = 'active'
            ORDER BY subscribed_at DESC"
        );

        $csv = "Email,Nombre,Estado,Fuente,Fecha\n";

        foreach ($subscribers as $sub) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s"' . "\n",
                $sub->email,
                $sub->name ?: '',
                $sub->status,
                $sub->source,
                $sub->subscribed_at
            );
        }

        return $csv;
    }

    /**
     * Obtener estadísticas
     */
    public function get_stats() {
        global $wpdb;

        return [
            'newsletter' => [
                'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subscribers}"),
                'active' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subscribers} WHERE status = 'active'"),
                'unsubscribed' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subscribers} WHERE status = 'unsubscribed'"),
                'this_month' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_subscribers}
                    WHERE subscribed_at >= %s",
                    date('Y-m-01')
                )),
            ],
            'contact' => [
                'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_contact}"),
                'new' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_contact} WHERE status = 'new'"),
                'read' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_contact} WHERE status = 'read'"),
                'replied' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_contact} WHERE status = 'replied'"),
            ],
        ];
    }
}

/**
 * Función helper
 */
function flavor_layout_forms() {
    return Flavor_Layout_Forms::get_instance();
}
