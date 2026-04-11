<?php
/**
 * Sistema de Webhooks para Red de Comunidades
 *
 * Permite notificaciones automáticas entre nodos cuando ocurren
 * eventos importantes: conexiones, contenido nuevo, colaboraciones.
 *
 * @package FlavorPlatform\Network
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
     * Tipos de eventos soportados
     */
    const EVENT_TYPES = [
        // Conexiones
        'connection.requested'  => 'Solicitud de conexión recibida',
        'connection.approved'   => 'Conexión aprobada',
        'connection.rejected'   => 'Conexión rechazada',
        'connection.removed'    => 'Conexión eliminada',

        // Contenido
        'content.created'       => 'Contenido nuevo publicado',
        'content.updated'       => 'Contenido actualizado',
        'content.removed'       => 'Contenido eliminado',

        // Eventos
        'event.created'         => 'Evento creado',
        'event.updated'         => 'Evento actualizado',
        'event.cancelled'       => 'Evento cancelado',

        // Colaboraciones
        'collaboration.created' => 'Colaboración creada',
        'collaboration.joined'  => 'Nodo unido a colaboración',
        'collaboration.left'    => 'Nodo abandonó colaboración',
        'collaboration.closed'  => 'Colaboración cerrada',

        // Alertas
        'alert.created'         => 'Alerta solidaria publicada',
        'alert.resolved'        => 'Alerta resuelta',

        // Tablón
        'board.posted'          => 'Publicación en tablón',

        // Mensajes
        'message.received'      => 'Mensaje recibido',

        // Nodo
        'node.updated'          => 'Perfil de nodo actualizado',
        'node.verified'         => 'Nodo verificado',
    ];

    /**
     * Configuración de reintentos
     */
    const RETRY_CONFIG = [
        'max_attempts'   => 4,        // 4 intentos total (1 inicial + 3 reintentos)
        'timeout'        => 15,       // Timeout de request en segundos
    ];

    /**
     * Delays exponenciales para reintentos (en segundos)
     * Intento 1 falla -> espera 5 min (300s)
     * Intento 2 falla -> espera 10 min (600s)
     * Intento 3 falla -> espera 30 min (1800s)
     */
    const RETRY_DELAYS = [300, 600, 1800];

    /**
     * Opción para almacenar webhooks suscritos
     */
    const WEBHOOKS_OPTION = 'flavor_network_webhooks';

    /**
     * Opción para cola de webhooks pendientes
     */
    const QUEUE_OPTION = 'flavor_network_webhook_queue';

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
        $this->init_hooks();
    }

    /**
     * Inicializa los hooks de WordPress
     */
    private function init_hooks() {
        // Hooks para eventos del sistema
        add_action('flavor_network_connection_requested', [$this, 'on_connection_requested'], 10, 2);
        add_action('flavor_network_connection_approved', [$this, 'on_connection_approved'], 10, 2);
        add_action('flavor_network_connection_rejected', [$this, 'on_connection_rejected'], 10, 2);

        add_action('flavor_network_content_created', [$this, 'on_content_created'], 10, 2);
        add_action('flavor_network_content_updated', [$this, 'on_content_updated'], 10, 2);

        add_action('flavor_network_event_created', [$this, 'on_event_created'], 10, 2);
        add_action('flavor_network_collaboration_created', [$this, 'on_collaboration_created'], 10, 2);
        add_action('flavor_network_collaboration_joined', [$this, 'on_collaboration_joined'], 10, 3);

        add_action('flavor_network_alert_created', [$this, 'on_alert_created'], 10, 2);
        add_action('flavor_network_message_sent', [$this, 'on_message_sent'], 10, 3);

        // Cron para procesar cola de webhooks
        add_action('flavor_network_process_webhook_queue', [$this, 'process_queue']);

        // Registrar cron si no existe
        if (!wp_next_scheduled('flavor_network_process_webhook_queue')) {
            wp_schedule_event(time(), 'five_minutes', 'flavor_network_process_webhook_queue');
        }

        // Registrar intervalo de 5 minutos si no existe
        add_filter('cron_schedules', [$this, 'add_cron_interval']);
    }

    /**
     * Añade intervalo de cron de 5 minutos
     */
    public function add_cron_interval($schedules) {
        if (!isset($schedules['five_minutes'])) {
            $schedules['five_minutes'] = [
                'interval' => 300,
                'display'  => __('Cada 5 minutos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }
        return $schedules;
    }

    // ─── MANEJADORES DE EVENTOS ───

    public function on_connection_requested($connection_id, $data) {
        $this->dispatch_webhook('connection.requested', [
            'connection_id' => $connection_id,
            'from_node'     => $data['from_node'] ?? null,
            'to_node'       => $data['to_node'] ?? null,
            'message'       => $data['message'] ?? '',
        ]);
    }

    public function on_connection_approved($connection_id, $data) {
        $this->dispatch_webhook('connection.approved', [
            'connection_id' => $connection_id,
            'nodes'         => $data['nodes'] ?? [],
            'level'         => $data['level'] ?? 'visible',
        ]);
    }

    public function on_connection_rejected($connection_id, $data) {
        $this->dispatch_webhook('connection.rejected', [
            'connection_id' => $connection_id,
            'reason'        => $data['reason'] ?? '',
        ]);
    }

    public function on_content_created($content_id, $data) {
        $this->dispatch_webhook('content.created', [
            'content_id'   => $content_id,
            'type'         => $data['type'] ?? 'producto',
            'title'        => $data['title'] ?? '',
            'node_id'      => $data['node_id'] ?? null,
            'visible_red'  => $data['visible_red'] ?? true,
        ]);
    }

    public function on_content_updated($content_id, $data) {
        $this->dispatch_webhook('content.updated', [
            'content_id' => $content_id,
            'changes'    => $data['changes'] ?? [],
        ]);
    }

    public function on_event_created($event_id, $data) {
        $this->dispatch_webhook('event.created', [
            'event_id'    => $event_id,
            'title'       => $data['title'] ?? '',
            'date_start'  => $data['date_start'] ?? '',
            'location'    => $data['location'] ?? '',
            'node_id'     => $data['node_id'] ?? null,
        ]);
    }

    public function on_collaboration_created($collaboration_id, $data) {
        $this->dispatch_webhook('collaboration.created', [
            'collaboration_id' => $collaboration_id,
            'type'             => $data['type'] ?? '',
            'title'            => $data['title'] ?? '',
            'creator_node_id'  => $data['creator_node_id'] ?? null,
        ]);
    }

    public function on_collaboration_joined($collaboration_id, $node_id, $data) {
        $this->dispatch_webhook('collaboration.joined', [
            'collaboration_id' => $collaboration_id,
            'node_id'          => $node_id,
            'role'             => $data['role'] ?? 'participante',
        ]);
    }

    public function on_alert_created($alert_id, $data) {
        $this->dispatch_webhook('alert.created', [
            'alert_id'  => $alert_id,
            'type'      => $data['type'] ?? 'necesidad',
            'title'     => $data['title'] ?? '',
            'urgency'   => $data['urgency'] ?? 'media',
            'node_id'   => $data['node_id'] ?? null,
        ]);
    }

    public function on_message_sent($message_id, $from_node_id, $to_node_id) {
        // Solo notificar al nodo destino
        $this->dispatch_webhook_to_node('message.received', [
            'message_id'   => $message_id,
            'from_node_id' => $from_node_id,
        ], $to_node_id);
    }

    // ─── DISPATCH DE WEBHOOKS ───

    /**
     * Despacha un webhook a todos los nodos suscritos al evento
     */
    public function dispatch_webhook($event_type, $payload) {
        $webhooks = $this->get_webhooks_for_event($event_type);

        if (empty($webhooks)) {
            return;
        }

        $local_node = Flavor_Network_Node::get_local_node();
        $source_node = $local_node ? [
            'id'   => $local_node->id,
            'name' => $local_node->nombre,
            'url'  => $local_node->site_url,
        ] : null;

        $webhook_payload = [
            'event'     => $event_type,
            'timestamp' => current_time('c'),
            'source'    => $source_node,
            'data'      => $payload,
        ];

        foreach ($webhooks as $webhook) {
            $this->queue_webhook($webhook['url'], $webhook_payload, $webhook['secret'] ?? '');
        }
    }

    /**
     * Despacha webhook a un nodo específico
     */
    public function dispatch_webhook_to_node($event_type, $payload, $node_id) {
        $node = Flavor_Network_Node::find($node_id);
        if (!$node || empty($node->site_url)) {
            return;
        }

        $webhook_url = trailingslashit($node->site_url) . 'wp-json/' . Flavor_Network_API::API_NAMESPACE . '/webhook/receive';

        $local_node = Flavor_Network_Node::get_local_node();
        $source_node = $local_node ? [
            'id'   => $local_node->id,
            'name' => $local_node->nombre,
            'url'  => $local_node->site_url,
        ] : null;

        $webhook_payload = [
            'event'     => $event_type,
            'timestamp' => current_time('c'),
            'source'    => $source_node,
            'data'      => $payload,
        ];

        $this->queue_webhook($webhook_url, $webhook_payload, $node->api_secret ?? '');
    }

    // ─── COLA DE WEBHOOKS ───

    /**
     * Añade un webhook a la cola para procesamiento asíncrono
     */
    private function queue_webhook($url, $payload, $secret = '') {
        $queue = get_option(self::QUEUE_OPTION, []);

        $queue[] = [
            'url'       => $url,
            'payload'   => $payload,
            'secret'    => $secret,
            'attempts'  => 0,
            'queued_at' => time(),
        ];

        update_option(self::QUEUE_OPTION, $queue);

        // Intentar enviar inmediatamente si es posible
        if (!wp_doing_cron()) {
            wp_schedule_single_event(time(), 'flavor_network_process_webhook_queue');
        }
    }

    /**
     * Procesa la cola de webhooks pendientes
     *
     * Usa backoff exponencial para reintentos:
     * - Intento 1 falla -> espera 5 min
     * - Intento 2 falla -> espera 10 min
     * - Intento 3 falla -> espera 30 min
     * - Intento 4 falla -> descartado y logeado como fallo
     */
    public function process_queue() {
        $queue = get_option(self::QUEUE_OPTION, []);

        if (empty($queue)) {
            return;
        }

        $new_queue = [];
        $processed = 0;
        $skipped = 0;
        $max_per_batch = 10;
        $current_time = time();

        foreach ($queue as $item) {
            // Limite de procesamiento por batch
            if ($processed >= $max_per_batch) {
                $new_queue[] = $item;
                continue;
            }

            // Verificar si hay que esperar antes de reintentar (backoff)
            if (!empty($item['retry_after']) && $current_time < $item['retry_after']) {
                $new_queue[] = $item;
                $skipped++;
                continue;
            }

            $result = $this->send_webhook($item['url'], $item['payload'], $item['secret']);

            if ($result['success']) {
                $processed++;
                // Log exitoso
                $this->log_webhook_result($item, $result, 'success');
            } else {
                $item['attempts']++;
                $item['last_error'] = $result['error'];
                $item['last_attempt'] = $current_time;

                if ($item['attempts'] < self::RETRY_CONFIG['max_attempts']) {
                    // Calcular delay exponencial para el proximo reintento
                    $delay_index = min($item['attempts'] - 1, count(self::RETRY_DELAYS) - 1);
                    $delay_seconds = self::RETRY_DELAYS[$delay_index];
                    $item['retry_after'] = $current_time + $delay_seconds;

                    // Log del reintento programado
                    $this->log_webhook_result($item, array_merge($result, [
                        'next_retry' => date('c', $item['retry_after']),
                        'delay_minutes' => round($delay_seconds / 60),
                    ]), 'retry_scheduled');

                    $new_queue[] = $item;
                } else {
                    // Todos los reintentos fallaron - log de fallo final
                    $this->log_webhook_result($item, array_merge($result, [
                        'final_failure' => true,
                        'total_attempts' => $item['attempts'],
                    ]), 'failed');
                }
            }
        }

        update_option(self::QUEUE_OPTION, $new_queue);

        // Log de resumen si hay actividad significativa
        if ($processed > 0 || count($new_queue) > 0) {
            $this->log_queue_summary($processed, $skipped, count($new_queue));
        }
    }

    /**
     * Registra un resumen del procesamiento de la cola
     */
    private function log_queue_summary($processed, $skipped, $remaining) {
        if (!apply_filters('flavor_network_webhook_logging', true)) {
            return;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Network Webhooks] Cola procesada: %d enviados, %d en espera por backoff, %d pendientes',
                $processed,
                $skipped,
                $remaining
            ));
        }
    }

    /**
     * Envía un webhook HTTP
     */
    private function send_webhook($url, $payload, $secret = '') {
        $body = wp_json_encode($payload);

        $headers = [
            'Content-Type'     => 'application/json',
            'X-Webhook-Event'  => $payload['event'] ?? 'unknown',
            'X-Webhook-Source' => home_url(),
        ];

        // Firmar el payload si hay secreto
        if (!empty($secret)) {
            $signature = hash_hmac('sha256', $body, $secret);
            $headers['X-Webhook-Signature'] = 'sha256=' . $signature;
        }

        $response = wp_remote_post($url, [
            'timeout'     => self::RETRY_CONFIG['timeout'],
            'headers'     => $headers,
            'body'        => $body,
            'sslverify'   => !defined('WP_DEBUG') || !WP_DEBUG,
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error'   => $response->get_error_message(),
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code >= 200 && $status_code < 300) {
            return [
                'success'     => true,
                'status_code' => $status_code,
            ];
        }

        return [
            'success'     => false,
            'status_code' => $status_code,
            'error'       => 'HTTP ' . $status_code,
        ];
    }

    // ─── GESTIÓN DE SUSCRIPCIONES ───

    /**
     * Registra un webhook para eventos
     */
    public function register_webhook($url, $events, $secret = '', $node_id = null) {
        $webhooks = get_option(self::WEBHOOKS_OPTION, []);

        $webhook_id = md5($url . implode(',', (array) $events));

        $webhooks[$webhook_id] = [
            'id'         => $webhook_id,
            'url'        => esc_url_raw($url),
            'events'     => (array) $events,
            'secret'     => $secret,
            'node_id'    => $node_id,
            'created_at' => current_time('c'),
            'active'     => true,
        ];

        update_option(self::WEBHOOKS_OPTION, $webhooks);

        return $webhook_id;
    }

    /**
     * Elimina un webhook
     */
    public function unregister_webhook($webhook_id) {
        $webhooks = get_option(self::WEBHOOKS_OPTION, []);

        if (isset($webhooks[$webhook_id])) {
            unset($webhooks[$webhook_id]);
            update_option(self::WEBHOOKS_OPTION, $webhooks);
            return true;
        }

        return false;
    }

    /**
     * Obtiene webhooks suscritos a un evento
     */
    private function get_webhooks_for_event($event_type) {
        $webhooks = get_option(self::WEBHOOKS_OPTION, []);
        $matching = [];

        foreach ($webhooks as $webhook) {
            if (!$webhook['active']) {
                continue;
            }

            // Verificar si está suscrito al evento específico o a wildcard
            if (in_array($event_type, $webhook['events']) ||
                in_array('*', $webhook['events']) ||
                in_array(explode('.', $event_type)[0] . '.*', $webhook['events'])) {
                $matching[] = $webhook;
            }
        }

        return $matching;
    }

    /**
     * Lista todos los webhooks registrados
     */
    public function list_webhooks() {
        return get_option(self::WEBHOOKS_OPTION, []);
    }

    // ─── LOGGING ───

    /**
     * Registra el resultado de un webhook
     */
    private function log_webhook_result($item, $result, $status) {
        if (!apply_filters('flavor_network_webhook_logging', true)) {
            return;
        }

        $log_entry = [
            'url'       => $item['url'],
            'event'     => $item['payload']['event'] ?? 'unknown',
            'status'    => $status,
            'attempts'  => $item['attempts'],
            'result'    => $result,
            'timestamp' => current_time('c'),
        ];

        // Almacenar últimos 100 logs
        $logs = get_option('flavor_network_webhook_logs', []);
        array_unshift($logs, $log_entry);
        $logs = array_slice($logs, 0, 100);
        update_option('flavor_network_webhook_logs', $logs);
    }

    /**
     * Obtiene los logs de webhooks
     */
    public function get_logs($limit = 50) {
        $logs = get_option('flavor_network_webhook_logs', []);
        return array_slice($logs, 0, $limit);
    }

    /**
     * Limpia los logs
     */
    public function clear_logs() {
        delete_option('flavor_network_webhook_logs');
    }

    // ─── UTILIDADES ───

    /**
     * Obtiene estadísticas de webhooks
     */
    public function get_stats() {
        $webhooks = get_option(self::WEBHOOKS_OPTION, []);
        $queue = get_option(self::QUEUE_OPTION, []);
        $logs = get_option('flavor_network_webhook_logs', []);

        $successful = 0;
        $failed = 0;
        foreach ($logs as $log) {
            if ($log['status'] === 'success') {
                $successful++;
            } else {
                $failed++;
            }
        }

        return [
            'registered_webhooks' => count($webhooks),
            'active_webhooks'     => count(array_filter($webhooks, fn($w) => $w['active'])),
            'pending_queue'       => count($queue),
            'recent_successful'   => $successful,
            'recent_failed'       => $failed,
            'event_types'         => array_keys(self::EVENT_TYPES),
        ];
    }

    /**
     * Prueba un webhook enviando un evento de test
     */
    public function test_webhook($webhook_id) {
        $webhooks = get_option(self::WEBHOOKS_OPTION, []);

        if (!isset($webhooks[$webhook_id])) {
            return new WP_Error('not_found', 'Webhook no encontrado');
        }

        $webhook = $webhooks[$webhook_id];

        $test_payload = [
            'event'     => 'test.ping',
            'timestamp' => current_time('c'),
            'source'    => [
                'url' => home_url(),
            ],
            'data'      => [
                'message' => 'Test webhook from ' . get_bloginfo('name'),
            ],
        ];

        return $this->send_webhook($webhook['url'], $test_payload, $webhook['secret']);
    }
}
