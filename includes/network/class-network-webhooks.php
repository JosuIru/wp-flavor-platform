<?php
/**
 * Sistema de Webhooks para Federación
 *
 * Permite sincronización en tiempo real entre nodos
 * mediante notificaciones push.
 *
 * @package FlavorChatIA\Network
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Network_Webhooks {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Eventos soportados
     */
    const EVENTS = [
        'content.created',
        'content.updated',
        'content.deleted',
        'node.connected',
        'node.disconnected',
        'sync.requested',
    ];

    /**
     * Tipos de contenido
     */
    const CONTENT_TYPES = [
        'event',
        'course',
        'workshop',
        'space',
        'marketplace',
        'timebank',
        'carpooling',
        'producer',
    ];

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
     * Constructor
     */
    private function __construct() {
        // Registrar endpoint para recibir webhooks
        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);

        // Hooks para disparar webhooks cuando hay cambios
        add_action('flavor_federation_content_created', [$this, 'on_content_created'], 10, 3);
        add_action('flavor_federation_content_updated', [$this, 'on_content_updated'], 10, 3);
        add_action('flavor_federation_content_deleted', [$this, 'on_content_deleted'], 10, 2);

        // Admin para gestionar suscripciones
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // AJAX handlers
        add_action('wp_ajax_webhook_test_send', [$this, 'ajax_test_send']);
        add_action('wp_ajax_webhook_subscribe', [$this, 'ajax_subscribe']);
        add_action('wp_ajax_webhook_unsubscribe', [$this, 'ajax_unsubscribe']);

        // Procesar cola de webhooks pendientes
        add_action('flavor_process_webhook_queue', [$this, 'process_queue']);
        if (!wp_next_scheduled('flavor_process_webhook_queue')) {
            wp_schedule_event(time(), 'every_minute', 'flavor_process_webhook_queue');
        }

        // Registrar intervalo personalizado
        add_filter('cron_schedules', [$this, 'add_cron_interval']);
    }

    /**
     * Añade intervalo de cron personalizado
     */
    public function add_cron_interval($schedules) {
        $schedules['every_minute'] = [
            'interval' => 60,
            'display'  => __('Cada minuto', 'flavor-chat-ia'),
        ];
        return $schedules;
    }

    /**
     * Registra el endpoint para recibir webhooks
     */
    public function register_webhook_endpoint() {
        register_rest_route('flavor-network/v1', '/webhook', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_incoming_webhook'],
            'permission_callback' => [$this, 'verify_webhook_signature'],
        ]);

        register_rest_route('flavor-network/v1', '/webhook/subscribe', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_subscription_request'],
            'permission_callback' => [$this, 'verify_node_token'],
        ]);
    }

    /**
     * Verifica la firma del webhook
     */
    public function verify_webhook_signature($request) {
        $signature = $request->get_header('X-Webhook-Signature');
        $timestamp = $request->get_header('X-Webhook-Timestamp');
        $body = $request->get_body();

        if (!$signature || !$timestamp) {
            return false;
        }

        // Verificar que el timestamp no sea muy antiguo (5 minutos)
        if (abs(time() - intval($timestamp)) > 300) {
            return false;
        }

        // Obtener el secreto del nodo que envía
        $node_id = $request->get_header('X-Node-ID');
        $secret = $this->get_node_webhook_secret($node_id);

        if (!$secret) {
            return false;
        }

        // Calcular firma esperada
        $payload = $timestamp . '.' . $body;
        $expected_signature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected_signature, $signature);
    }

    /**
     * Verifica el token del nodo
     */
    public function verify_node_token($request) {
        $token = $request->get_header('X-Node-Token');
        $node_id = $request->get_header('X-Node-ID');

        if (!$token || !$node_id) {
            return false;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_network_nodes';

        $node = $wpdb->get_row($wpdb->prepare(
            "SELECT api_key FROM {$tabla} WHERE slug = %s OR id = %d",
            $node_id,
            intval($node_id)
        ));

        return $node && hash_equals($node->api_key, $token);
    }

    /**
     * Obtiene el secreto de webhook de un nodo
     */
    private function get_node_webhook_secret($node_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_network_nodes';

        return $wpdb->get_var($wpdb->prepare(
            "SELECT api_secret FROM {$tabla} WHERE slug = %s OR id = %d",
            $node_id,
            intval($node_id)
        ));
    }

    /**
     * Maneja un webhook entrante
     */
    public function handle_incoming_webhook($request) {
        $body = json_decode($request->get_body(), true);

        if (!is_array($body) || empty($body['event'])) {
            return new WP_Error('invalid_payload', 'Payload inválido', ['status' => 400]);
        }

        $event = sanitize_text_field($body['event']);
        $data = $body['data'] ?? [];
        $node_id = $request->get_header('X-Node-ID');

        // Log del webhook recibido
        $this->log_webhook('incoming', $event, $node_id, $data);

        // Procesar según el tipo de evento
        switch ($event) {
            case 'content.created':
            case 'content.updated':
                $this->handle_content_change($data, $node_id);
                break;

            case 'content.deleted':
                $this->handle_content_deletion($data, $node_id);
                break;

            case 'sync.requested':
                // Ejecutar sincronización
                do_action('flavor_network_sync_peers');
                break;

            default:
                // Evento desconocido - permitir extensiones
                do_action('flavor_webhook_received_' . $event, $data, $node_id);
        }

        return [
            'success' => true,
            'message' => 'Webhook procesado',
        ];
    }

    /**
     * Maneja cambios de contenido
     */
    private function handle_content_change($data, $node_id) {
        if (empty($data['type']) || empty($data['content'])) {
            return;
        }

        $type = sanitize_key($data['type']);
        $content = $data['content'];

        // Mapear tipo a tabla
        $table_map = [
            'event'       => 'events',
            'course'      => 'courses',
            'workshop'    => 'workshops',
            'space'       => 'spaces',
            'marketplace' => 'marketplace',
            'timebank'    => 'time_bank',
            'carpooling'  => 'carpooling',
            'producer'    => 'producers',
        ];

        if (!isset($table_map[$type])) {
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_network_' . $table_map[$type];

        // Verificar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            return;
        }

        // Buscar si ya existe
        $content_id_field = $type . '_id';
        if ($type === 'marketplace') {
            $content_id_field = 'anuncio_id';
        } elseif ($type === 'timebank') {
            $content_id_field = 'servicio_id';
        }

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla} WHERE nodo_id = %s AND {$content_id_field} = %d",
            $node_id,
            $content['id']
        ));

        // Preparar datos
        $datos = $this->sanitize_content_data($type, $content, $node_id);

        if ($existe) {
            $wpdb->update($tabla, $datos, ['id' => $existe]);
        } else {
            $datos['creado_en'] = current_time('mysql');
            $wpdb->insert($tabla, $datos);
        }
    }

    /**
     * Maneja eliminación de contenido
     */
    private function handle_content_deletion($data, $node_id) {
        if (empty($data['type']) || empty($data['id'])) {
            return;
        }

        $type = sanitize_key($data['type']);
        $content_id = absint($data['id']);

        $table_map = [
            'event'       => 'events',
            'course'      => 'courses',
            'workshop'    => 'workshops',
            'space'       => 'spaces',
            'marketplace' => 'marketplace',
            'timebank'    => 'time_bank',
            'carpooling'  => 'carpooling',
            'producer'    => 'producers',
        ];

        if (!isset($table_map[$type])) {
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_network_' . $table_map[$type];

        $content_id_field = $type . '_id';
        if ($type === 'marketplace') {
            $content_id_field = 'anuncio_id';
        } elseif ($type === 'timebank') {
            $content_id_field = 'servicio_id';
        }

        $wpdb->delete($tabla, [
            'nodo_id' => $node_id,
            $content_id_field => $content_id,
        ]);
    }

    /**
     * Sanitiza datos de contenido según el tipo
     */
    private function sanitize_content_data($type, $content, $node_id) {
        $datos = [
            'nodo_id' => $node_id,
            'actualizado_en' => current_time('mysql'),
            'visible_en_red' => 1,
        ];

        // Campos comunes
        $campos_comunes = ['titulo', 'descripcion', 'categoria', 'ubicacion', 'estado'];
        foreach ($campos_comunes as $campo) {
            if (isset($content[$campo])) {
                $datos[$campo] = sanitize_text_field($content[$campo]);
            }
        }

        // ID específico según tipo
        switch ($type) {
            case 'event':
                $datos['evento_id'] = absint($content['id']);
                if (isset($content['fecha_inicio'])) {
                    $datos['fecha_inicio'] = sanitize_text_field($content['fecha_inicio']);
                }
                break;
            case 'course':
                $datos['curso_id'] = absint($content['id']);
                break;
            case 'workshop':
                $datos['taller_id'] = absint($content['id']);
                break;
            case 'space':
                $datos['espacio_id'] = absint($content['id']);
                if (isset($content['nombre'])) {
                    $datos['nombre'] = sanitize_text_field($content['nombre']);
                }
                break;
            case 'marketplace':
                $datos['anuncio_id'] = absint($content['id']);
                if (isset($content['tipo'])) {
                    $datos['tipo'] = sanitize_text_field($content['tipo']);
                }
                break;
            case 'timebank':
                $datos['servicio_id'] = absint($content['id']);
                break;
            case 'carpooling':
                $datos['viaje_id'] = absint($content['id']);
                break;
            case 'producer':
                $datos['productor_id'] = absint($content['id']);
                if (isset($content['nombre'])) {
                    $datos['nombre'] = sanitize_text_field($content['nombre']);
                }
                break;
        }

        return $datos;
    }

    /**
     * Maneja solicitud de suscripción
     */
    public function handle_subscription_request($request) {
        $body = json_decode($request->get_body(), true);

        $callback_url = esc_url_raw($body['callback_url'] ?? '');
        $events = array_map('sanitize_text_field', $body['events'] ?? ['content.created', 'content.updated', 'content.deleted']);
        $node_id = $request->get_header('X-Node-ID');

        if (empty($callback_url)) {
            return new WP_Error('invalid_url', 'URL de callback requerida', ['status' => 400]);
        }

        // Guardar suscripción
        $this->save_subscription($node_id, $callback_url, $events);

        // Generar secreto compartido
        $secret = wp_generate_password(32, false);
        $this->save_webhook_secret($node_id, $secret);

        return [
            'success' => true,
            'secret' => $secret,
            'message' => 'Suscripción registrada',
        ];
    }

    /**
     * Guarda una suscripción de webhook
     */
    private function save_subscription($node_id, $callback_url, $events) {
        $subscriptions = get_option('flavor_webhook_subscriptions', []);

        $subscriptions[$node_id] = [
            'callback_url' => $callback_url,
            'events' => $events,
            'created_at' => time(),
            'active' => true,
        ];

        update_option('flavor_webhook_subscriptions', $subscriptions);
    }

    /**
     * Guarda el secreto de webhook para un nodo
     */
    private function save_webhook_secret($node_id, $secret) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_network_nodes';

        $wpdb->update(
            $tabla,
            ['api_secret' => $secret],
            ['slug' => $node_id]
        );
    }

    /**
     * Evento: Contenido creado
     */
    public function on_content_created($type, $content_id, $data) {
        $this->queue_webhook('content.created', [
            'type' => $type,
            'content' => array_merge(['id' => $content_id], $data),
        ]);
    }

    /**
     * Evento: Contenido actualizado
     */
    public function on_content_updated($type, $content_id, $data) {
        $this->queue_webhook('content.updated', [
            'type' => $type,
            'content' => array_merge(['id' => $content_id], $data),
        ]);
    }

    /**
     * Evento: Contenido eliminado
     */
    public function on_content_deleted($type, $content_id) {
        $this->queue_webhook('content.deleted', [
            'type' => $type,
            'id' => $content_id,
        ]);
    }

    /**
     * Encola un webhook para envío
     */
    private function queue_webhook($event, $data) {
        $queue = get_option('flavor_webhook_queue', []);

        $queue[] = [
            'event' => $event,
            'data' => $data,
            'timestamp' => time(),
            'attempts' => 0,
        ];

        update_option('flavor_webhook_queue', $queue);
    }

    /**
     * Procesa la cola de webhooks pendientes
     */
    public function process_queue() {
        $queue = get_option('flavor_webhook_queue', []);

        if (empty($queue)) {
            return;
        }

        $subscriptions = get_option('flavor_webhook_subscriptions', []);
        $processed = [];
        $failed = [];

        foreach ($queue as $index => $item) {
            if ($item['attempts'] >= 3) {
                // Demasiados intentos, descartar
                $this->log_webhook('failed', $item['event'], 'all', $item['data']);
                continue;
            }

            $success = true;
            foreach ($subscriptions as $node_id => $subscription) {
                if (!$subscription['active']) {
                    continue;
                }

                if (!in_array($item['event'], $subscription['events'])) {
                    continue;
                }

                $result = $this->send_webhook(
                    $subscription['callback_url'],
                    $item['event'],
                    $item['data'],
                    $node_id
                );

                if (!$result) {
                    $success = false;
                }
            }

            if ($success) {
                $processed[] = $index;
            } else {
                $queue[$index]['attempts']++;
                $failed[] = $index;
            }
        }

        // Eliminar procesados exitosamente
        foreach ($processed as $index) {
            unset($queue[$index]);
        }

        // Reindexar y guardar
        update_option('flavor_webhook_queue', array_values($queue));
    }

    /**
     * Envía un webhook a un nodo
     */
    private function send_webhook($url, $event, $data, $target_node_id) {
        $node_id = get_option('flavor_network_node_id', '');
        $secret = $this->get_local_webhook_secret($target_node_id);

        $timestamp = time();
        $body = wp_json_encode([
            'event' => $event,
            'data' => $data,
            'timestamp' => $timestamp,
        ]);

        // Calcular firma
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        $response = wp_remote_post($url, [
            'timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Timestamp' => $timestamp,
                'X-Node-ID' => $node_id,
            ],
            'body' => $body,
        ]);

        $success = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;

        $this->log_webhook('outgoing', $event, $target_node_id, [
            'success' => $success,
            'url' => $url,
        ]);

        return $success;
    }

    /**
     * Obtiene el secreto local para un nodo destino
     */
    private function get_local_webhook_secret($node_id) {
        $secrets = get_option('flavor_webhook_secrets', []);
        return $secrets[$node_id] ?? wp_generate_password(32, false);
    }

    /**
     * Log de webhooks
     */
    private function log_webhook($direction, $event, $node_id, $data) {
        $logs = get_option('flavor_webhook_logs', []);

        // Mantener solo los últimos 100 logs
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }

        $logs[] = [
            'direction' => $direction,
            'event' => $event,
            'node_id' => $node_id,
            'timestamp' => time(),
            'data' => $data,
        ];

        update_option('flavor_webhook_logs', $logs);
    }

    /**
     * Añade menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-network',
            __('Webhooks', 'flavor-chat-ia'),
            __('Webhooks', 'flavor-chat-ia'),
            'manage_options',
            'flavor-webhooks',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Renderiza página de administración
     */
    public function render_admin_page() {
        $subscriptions = get_option('flavor_webhook_subscriptions', []);
        $logs = array_reverse(get_option('flavor_webhook_logs', []));
        $queue = get_option('flavor_webhook_queue', []);
        ?>
        <div class="wrap">
            <h1>🔔 <?php echo esc_html__('Webhooks de Federación', 'flavor-chat-ia'); ?></h1>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <!-- Suscripciones -->
                <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h2 style="margin-top: 0;">📥 <?php echo esc_html__('Suscripciones Activas', 'flavor-chat-ia'); ?></h2>

                    <?php if (empty($subscriptions)): ?>
                        <p style="color: #666;"><?php echo esc_html__('No hay suscripciones de webhooks', 'flavor-chat-ia'); ?></p>
                    <?php else: ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Nodo', 'flavor-chat-ia'); ?></th>
                                    <th><?php echo esc_html__('URL', 'flavor-chat-ia'); ?></th>
                                    <th><?php echo esc_html__('Eventos', 'flavor-chat-ia'); ?></th>
                                    <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subscriptions as $node_id => $sub): ?>
                                    <tr>
                                        <td><code><?php echo esc_html(substr($node_id, 0, 12)); ?>...</code></td>
                                        <td><small><?php echo esc_html($sub['callback_url']); ?></small></td>
                                        <td><?php echo count($sub['events']); ?> eventos</td>
                                        <td>
                                            <?php if ($sub['active']): ?>
                                                <span style="color: green;">✅ Activo</span>
                                            <?php else: ?>
                                                <span style="color: red;">❌ Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <h3 style="margin-top: 30px;">📤 <?php echo esc_html__('Cola de Envío', 'flavor-chat-ia'); ?></h3>
                    <p>
                        <strong><?php echo count($queue); ?></strong> <?php echo esc_html__('webhooks pendientes', 'flavor-chat-ia'); ?>
                    </p>
                </div>

                <!-- Logs -->
                <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h2 style="margin-top: 0;">📋 <?php echo esc_html__('Últimos Webhooks', 'flavor-chat-ia'); ?></h2>

                    <?php if (empty($logs)): ?>
                        <p style="color: #666;"><?php echo esc_html__('No hay logs de webhooks', 'flavor-chat-ia'); ?></p>
                    <?php else: ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="widefat striped" style="font-size: 12px;">
                                <thead>
                                    <tr>
                                        <th><?php echo esc_html__('Hora', 'flavor-chat-ia'); ?></th>
                                        <th><?php echo esc_html__('Dir', 'flavor-chat-ia'); ?></th>
                                        <th><?php echo esc_html__('Evento', 'flavor-chat-ia'); ?></th>
                                        <th><?php echo esc_html__('Nodo', 'flavor-chat-ia'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($logs, 0, 50) as $log): ?>
                                        <tr>
                                            <td><?php echo esc_html(date_i18n('H:i:s', $log['timestamp'])); ?></td>
                                            <td>
                                                <?php echo $log['direction'] === 'incoming' ? '📥' : ($log['direction'] === 'outgoing' ? '📤' : '❌'); ?>
                                            </td>
                                            <td><code><?php echo esc_html($log['event']); ?></code></td>
                                            <td><code><?php echo esc_html(substr($log['node_id'], 0, 8)); ?>...</code></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Configuración -->
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px;">
                <h2 style="margin-top: 0;">⚙️ <?php echo esc_html__('Configuración', 'flavor-chat-ia'); ?></h2>

                <p>
                    <strong><?php echo esc_html__('URL de Webhook de este nodo:', 'flavor-chat-ia'); ?></strong><br>
                    <code><?php echo esc_url(rest_url('flavor-network/v1/webhook')); ?></code>
                </p>

                <p>
                    <strong><?php echo esc_html__('ID de este nodo:', 'flavor-chat-ia'); ?></strong><br>
                    <code><?php echo esc_html(get_option('flavor_network_node_id', 'No configurado')); ?></code>
                </p>

                <p style="margin-top: 20px;">
                    <button type="button" class="button" id="btn-test-webhook">
                        🧪 <?php echo esc_html__('Enviar Webhook de Prueba', 'flavor-chat-ia'); ?>
                    </button>
                    <span id="test-result" style="margin-left: 10px;"></span>
                </p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#btn-test-webhook').on('click', function() {
                var $btn = $(this);
                var $result = $('#test-result');

                $btn.prop('disabled', true);
                $result.text('Enviando...');

                $.post(ajaxurl, {
                    action: 'webhook_test_send',
                    _wpnonce: '<?php echo wp_create_nonce('webhook_test'); ?>'
                }, function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        $result.html('<span style="color: green;">✅ ' + response.data.message + '</span>');
                    } else {
                        $result.html('<span style="color: red;">❌ ' + response.data + '</span>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Enviar webhook de prueba
     */
    public function ajax_test_send() {
        check_ajax_referer('webhook_test', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        // Encolar webhook de prueba
        $this->queue_webhook('sync.requested', [
            'test' => true,
            'timestamp' => time(),
        ]);

        // Procesar inmediatamente
        $this->process_queue();

        wp_send_json_success([
            'message' => __('Webhook de prueba enviado', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Suscribirse a webhooks de un nodo
     */
    public function ajax_subscribe() {
        check_ajax_referer('webhook_subscribe', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $node_url = esc_url_raw($_POST['node_url']);
        $events = isset($_POST['events']) ? array_map('sanitize_text_field', $_POST['events']) : self::EVENTS;

        if (empty($node_url)) {
            wp_send_json_error('URL requerida');
        }

        // Enviar solicitud de suscripción al nodo
        $response = wp_remote_post($node_url . '/wp-json/flavor-network/v1/webhook/subscribe', [
            'timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Node-ID' => get_option('flavor_network_node_id', ''),
                'X-Node-Token' => get_option('flavor_network_token', ''),
            ],
            'body' => wp_json_encode([
                'callback_url' => rest_url('flavor-network/v1/webhook'),
                'events' => $events,
            ]),
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['success']) && !empty($body['secret'])) {
            // Guardar secreto para verificar webhooks entrantes de este nodo
            $secrets = get_option('flavor_webhook_secrets', []);
            $secrets[$_POST['node_id'] ?? 'unknown'] = $body['secret'];
            update_option('flavor_webhook_secrets', $secrets);

            wp_send_json_success([
                'message' => __('Suscripción completada', 'flavor-chat-ia'),
            ]);
        } else {
            wp_send_json_error($body['message'] ?? 'Error desconocido');
        }
    }

    /**
     * AJAX: Cancelar suscripción
     */
    public function ajax_unsubscribe() {
        check_ajax_referer('webhook_unsubscribe', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $node_id = sanitize_text_field($_POST['node_id']);

        $subscriptions = get_option('flavor_webhook_subscriptions', []);
        unset($subscriptions[$node_id]);
        update_option('flavor_webhook_subscriptions', $subscriptions);

        wp_send_json_success([
            'message' => __('Suscripción cancelada', 'flavor-chat-ia'),
        ]);
    }

    /**
     * Dispara un webhook cuando se crea/actualiza contenido
     * Método helper para usar desde los módulos
     */
    public static function trigger($event, $type, $content_id, $data = []) {
        $instance = self::get_instance();

        switch ($event) {
            case 'created':
                do_action('flavor_federation_content_created', $type, $content_id, $data);
                break;
            case 'updated':
                do_action('flavor_federation_content_updated', $type, $content_id, $data);
                break;
            case 'deleted':
                do_action('flavor_federation_content_deleted', $type, $content_id);
                break;
        }
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_Network_Webhooks::get_instance();
});
