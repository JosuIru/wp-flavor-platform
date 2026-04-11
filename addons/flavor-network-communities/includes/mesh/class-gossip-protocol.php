<?php
/**
 * Gossip Protocol para propagación de mensajes en red mesh
 *
 * Implementa un protocolo gossip adaptado a las limitaciones de WordPress:
 * - Sin websockets (PHP síncrono)
 * - Envío oportunista + cron batching
 * - TTL y hop count para evitar loops
 * - Selección de targets por reputación
 *
 * @package FlavorPlatform\Network\Mesh
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Gossip_Protocol {

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * TTL por defecto para mensajes
     */
    const DEFAULT_TTL = 5;

    /**
     * Número de peers a seleccionar para gossip
     */
    const GOSSIP_FANOUT = 3;

    /**
     * Timeout para envío inmediato (ms)
     */
    const IMMEDIATE_TIMEOUT_MS = 500;

    /**
     * Tabla de mensajes gossip
     *
     * @var string
     */
    private $table_name;

    /**
     * Tabla de peers
     *
     * @var string
     */
    private $peers_table;

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'flavor_network_gossip_messages';
        $this->peers_table = $wpdb->prefix . 'flavor_network_peers';

        $this->init_hooks();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return self
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializa hooks de WordPress
     */
    private function init_hooks() {
        // Cron jobs para gossip
        add_action('flavor_mesh_gossip_batch', [$this, 'process_gossip_batch']);
        add_action('flavor_mesh_heartbeat', [$this, 'send_heartbeats']);
        add_action('flavor_mesh_cleanup_expired', [$this, 'cleanup_expired_messages']);

        // Programar crons si no existen
        if (!wp_next_scheduled('flavor_mesh_gossip_batch')) {
            wp_schedule_event(time(), 'every_minute', 'flavor_mesh_gossip_batch');
        }
        if (!wp_next_scheduled('flavor_mesh_heartbeat')) {
            wp_schedule_event(time(), 'every_5_minutes', 'flavor_mesh_heartbeat');
        }
        if (!wp_next_scheduled('flavor_mesh_cleanup_expired')) {
            wp_schedule_event(time(), 'hourly', 'flavor_mesh_cleanup_expired');
        }
    }

    /**
     * Crea y envía un mensaje gossip
     *
     * @param string $type Tipo: peer_announce, data_update, heartbeat, crdt_sync, etc.
     * @param array $payload Contenido del mensaje
     * @param int $ttl TTL inicial (default 5)
     * @param string $priority low, normal, high, urgent
     * @return array|false Datos del mensaje creado o false si falla
     */
    public function gossip_message($type, array $payload, $ttl = null, $priority = 'normal') {
        $local_peer = $this->get_local_peer();
        if (!$local_peer) {
            error_log('[Gossip] Error: No hay peer local configurado');
            return false;
        }

        $ttl = $ttl ?? self::DEFAULT_TTL;

        // Crear mensaje
        $message = [
            'type'      => $type,
            'payload'   => $payload,
            'timestamp' => time(),
            'ttl'       => $ttl,
        ];

        // Generar ID único (hash del contenido)
        $message_json = wp_json_encode($message);
        $message_id = hash('sha256', $local_peer->peer_id . $message_json . microtime(true));

        // Firmar mensaje
        $signature = $this->sign_message($message_json, $local_peer);

        // Vector clock local
        $vector_clock = $this->get_local_vector_clock();
        $vector_clock->increment($local_peer->peer_id);

        // Calcular expiración
        $expires_at = date('Y-m-d H:i:s', time() + ($ttl * 3600)); // TTL en horas

        // Guardar en BD
        global $wpdb;
        $result = $wpdb->insert($this->table_name, [
            'message_id'           => $message_id,
            'origin_peer_id'       => $local_peer->peer_id,
            'message_type'         => $type,
            'payload'              => $message_json,
            'signature'            => $signature,
            'ttl'                  => $ttl,
            'hop_count'            => 0,
            'propagation_path'     => wp_json_encode([$local_peer->peer_id]),
            'vector_clock'         => $vector_clock->to_json(),
            'priority'             => $priority,
            'expires_at'           => $expires_at,
            'processed'            => 1, // Ya procesado localmente
            'forwarded'            => 0,
        ]);

        if ($result === false) {
            error_log('[Gossip] Error guardando mensaje: ' . $wpdb->last_error);
            return false;
        }

        // Intentar envío inmediato a 1 peer (fire-and-forget)
        $this->send_immediate($message_id);

        return [
            'message_id'     => $message_id,
            'origin_peer_id' => $local_peer->peer_id,
            'type'           => $type,
            'ttl'            => $ttl,
        ];
    }

    /**
     * Envía inmediatamente a un peer seleccionado (no bloqueante)
     *
     * @param string $message_id
     */
    private function send_immediate($message_id) {
        $targets = $this->select_gossip_targets(1);
        if (empty($targets)) {
            return;
        }

        $target = $targets[0];
        $message = $this->get_message($message_id);
        if (!$message) {
            return;
        }

        // Envío no bloqueante con timeout corto
        $this->send_to_peer($target, $message, self::IMMEDIATE_TIMEOUT_MS);
    }

    /**
     * Procesa el batch de gossip pendiente (ejecutado por cron)
     *
     * Wrapper que usa el método optimizado con requests paralelas.
     */
    public function process_gossip_batch() {
        return $this->process_pending_batch(50);
    }

    /**
     * Reenvía un mensaje a múltiples peers
     *
     * @param object $message Mensaje de BD
     */
    private function forward_message($message) {
        $targets = $this->select_gossip_targets(self::GOSSIP_FANOUT, $message);

        if (empty($targets)) {
            // Marcar como forwarded aunque no haya targets
            $this->mark_forwarded($message->id, []);
            return;
        }

        $forwarded_to = [];
        foreach ($targets as $target) {
            $success = $this->send_to_peer($target, $message);
            if ($success) {
                $forwarded_to[] = $target->peer_id;
            }
        }

        $this->mark_forwarded($message->id, $forwarded_to);
    }

    /**
     * Selecciona peers para gossip
     *
     * Criterios:
     * - No está en el path de propagación del mensaje
     * - Está online o fue visto recientemente
     * - Selección ponderada por reputación
     *
     * OPTIMIZADO: Usa caché y selecciona solo columnas necesarias
     *
     * @param int $count Número de peers a seleccionar
     * @param object|null $context Contexto con exclude_peers o propagation_path
     * @return array
     */
    public function select_gossip_targets($count, $context = null) {
        $local_peer = $this->get_local_peer();
        if (!$local_peer) {
            return [];
        }

        // Usar caché para peers elegibles (60s TTL)
        $cache = function_exists('flavor_mesh_cache') ? flavor_mesh_cache() : null;
        $candidates = $cache ? $cache->get('gossip_candidates') : null;

        if ($candidates === null) {
            global $wpdb;
            // OPTIMIZACIÓN: Solo columnas necesarias en lugar de SELECT *
            $candidates = $wpdb->get_results($wpdb->prepare(
                "SELECT peer_id, site_url, display_name, reputacion_score
                 FROM {$this->peers_table}
                 WHERE is_local_peer = 0
                   AND (is_online = 1 OR last_seen > %s)
                   AND connection_failures < 5
                   AND site_url != ''
                 ORDER BY reputacion_score DESC, last_seen DESC
                 LIMIT 20",
                date('Y-m-d H:i:s', strtotime('-1 hour'))
            ));

            if ($cache && !empty($candidates)) {
                $cache->set('gossip_candidates', $candidates, 60);
            }
        }

        if (empty($candidates)) {
            return [];
        }

        // Filtrar peers excluidos
        if ($context) {
            $exclude_peers = [];

            // Soportar ambos formatos: exclude_peers directo o propagation_path
            if (isset($context->exclude_peers)) {
                $exclude_peers = (array) $context->exclude_peers;
            } elseif (isset($context->propagation_path)) {
                $exclude_peers = json_decode($context->propagation_path, true) ?? [];
            }

            if (!empty($exclude_peers)) {
                $candidates = array_filter($candidates, function ($peer) use ($exclude_peers) {
                    return !in_array($peer->peer_id, $exclude_peers, true);
                });
            }
        }

        // Selección ponderada por reputación
        $selected = [];
        $candidates = array_values($candidates);
        $total_candidates = count($candidates);

        for ($i = 0; $i < $count && $i < $total_candidates; $i++) {
            // Ponderación simple: más probable elegir los de mayor reputación
            // pero con algo de aleatoriedad
            $index = $this->weighted_random_select($candidates);
            $selected[] = $candidates[$index];
            array_splice($candidates, $index, 1);

            if (empty($candidates)) {
                break;
            }
        }

        return $selected;
    }

    /**
     * Selección aleatoria ponderada por reputación
     *
     * @param array $candidates
     * @return int Índice seleccionado
     */
    private function weighted_random_select($candidates) {
        $total_weight = 0;
        foreach ($candidates as $candidate) {
            $total_weight += max(1, $candidate->reputacion_score);
        }

        $random = mt_rand(0, (int) ($total_weight * 100)) / 100;
        $cumulative = 0;

        foreach ($candidates as $index => $candidate) {
            $cumulative += max(1, $candidate->reputacion_score);
            if ($random <= $cumulative) {
                return $index;
            }
        }

        return 0;
    }

    /**
     * Envía un mensaje a un peer específico
     *
     * @param object $target Peer destino
     * @param object $message Mensaje
     * @param int $timeout_ms Timeout en ms
     * @return bool Success
     */
    private function send_to_peer($target, $message, $timeout_ms = 5000) {
        if (empty($target->site_url)) {
            return false;
        }

        $endpoint = trailingslashit($target->site_url) . 'wp-json/flavor-mesh/v1/gossip/receive';

        // Preparar payload
        $local_peer = $this->get_local_peer();
        $payload = [
            'message_id'       => $message->message_id,
            'origin_peer_id'   => $message->origin_peer_id,
            'message_type'     => $message->message_type,
            'payload'          => $message->payload,
            'signature'        => $message->signature,
            'ttl'              => max(0, $message->ttl - 1), // Decrementar TTL
            'hop_count'        => $message->hop_count + 1,
            'propagation_path' => $message->propagation_path,
            'vector_clock'     => $message->vector_clock,
            'sender_peer_id'   => $local_peer->peer_id,
        ];

        // Headers de autenticación
        $timestamp = time();
        $auth_signature = $this->sign_request($payload, $timestamp, $local_peer);

        $args = [
            'body'      => wp_json_encode($payload),
            'headers'   => [
                'Content-Type'       => 'application/json',
                'X-Mesh-Peer-Id'     => $local_peer->peer_id,
                'X-Mesh-Timestamp'   => $timestamp,
                'X-Mesh-Signature'   => $auth_signature,
            ],
            'timeout'   => $timeout_ms / 1000,
            'blocking'  => $timeout_ms > self::IMMEDIATE_TIMEOUT_MS,
            'sslverify' => apply_filters('flavor_mesh_ssl_verify', true),
        ];

        $response = wp_remote_post($endpoint, $args);

        if (is_wp_error($response)) {
            $this->record_peer_failure($target->peer_id);
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 300) {
            $this->record_peer_success($target->peer_id);
            return true;
        }

        $this->record_peer_failure($target->peer_id);
        return false;
    }

    /**
     * Recibe un mensaje gossip de otro peer
     *
     * OPTIMIZADO: Usa caché para verificación rápida de duplicados
     *
     * @param array $data Datos del mensaje
     * @return array Resultado del procesamiento
     */
    public function receive_message(array $data) {
        global $wpdb;

        $message_id = $data['message_id'] ?? '';
        $origin_peer_id = $data['origin_peer_id'] ?? '';
        $sender_peer_id = $data['sender_peer_id'] ?? '';

        // OPTIMIZADO: Verificación rápida en caché primero
        $cache = function_exists('flavor_mesh_cache') ? flavor_mesh_cache() : null;

        if ($cache && $cache->message_exists($message_id)) {
            return [
                'status'  => 'duplicate',
                'message' => 'Message already received',
            ];
        }

        // Verificar en BD solo si no está en caché
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT 1 FROM {$this->table_name} WHERE message_id = %s LIMIT 1",
            $message_id
        ));

        if ($existing) {
            // Marcar en caché para próximas consultas
            if ($cache) {
                $cache->mark_message_exists($message_id);
            }
            return [
                'status'  => 'duplicate',
                'message' => 'Message already received',
            ];
        }

        // Verificar firma del origen
        $payload_to_verify = $data['payload'] ?? '';
        $signature = $data['signature'] ?? '';

        if (!$this->verify_signature($payload_to_verify, $signature, $origin_peer_id)) {
            return [
                'status'  => 'invalid_signature',
                'message' => 'Signature verification failed',
            ];
        }

        // Actualizar propagation path
        $local_peer = $this->get_local_peer();
        $propagation_path = json_decode($data['propagation_path'] ?? '[]', true);
        $propagation_path[] = $local_peer->peer_id;

        // Guardar mensaje
        $ttl = (int) ($data['ttl'] ?? 0);
        $hop_count = (int) ($data['hop_count'] ?? 0);

        $insert_result = $wpdb->insert($this->table_name, [
            'message_id'           => $message_id,
            'origin_peer_id'       => $origin_peer_id,
            'message_type'         => $data['message_type'] ?? 'data_update',
            'payload'              => $payload_to_verify,
            'signature'            => $signature,
            'ttl'                  => $ttl,
            'hop_count'            => $hop_count,
            'propagation_path'     => wp_json_encode($propagation_path),
            'vector_clock'         => $data['vector_clock'] ?? '{}',
            'priority'             => 'normal',
            'processed'            => 0,
            'forwarded'            => $ttl <= 0 ? 1 : 0, // No forward si TTL agotado
            'received_from_peer_id' => $sender_peer_id,
        ]);

        // Marcar en caché para evitar duplicados futuros
        if ($insert_result && $cache) {
            $cache->mark_message_exists($message_id);
        }

        // Procesar mensaje
        $this->process_message($message_id);

        // Registrar éxito del sender
        if ($sender_peer_id) {
            $this->record_peer_success($sender_peer_id);
        }

        return [
            'status'     => 'accepted',
            'message_id' => $message_id,
            'will_forward' => $ttl > 0,
        ];
    }

    /**
     * Procesa un mensaje recibido según su tipo
     *
     * @param string $message_id
     */
    private function process_message($message_id) {
        $message = $this->get_message($message_id);
        if (!$message || $message->processed) {
            return;
        }

        $payload = json_decode($message->payload, true);
        $inner_payload = $payload['payload'] ?? [];

        // Disparar hook para procesamiento específico por tipo
        do_action('flavor_mesh_process_message', $message->message_type, $inner_payload, $message);
        do_action("flavor_mesh_process_{$message->message_type}", $inner_payload, $message);

        // Marcar como procesado
        global $wpdb;
        $wpdb->update(
            $this->table_name,
            ['processed' => 1, 'processed_at' => current_time('mysql')],
            ['id' => $message->id]
        );
    }

    /**
     * Envía heartbeats a peers conectados
     */
    public function send_heartbeats() {
        $local_peer = $this->get_local_peer();
        if (!$local_peer) {
            return;
        }

        $this->gossip_message('heartbeat', [
            'peer_id'    => $local_peer->peer_id,
            'timestamp'  => time(),
            'uptime'     => $this->get_uptime(),
            'version'    => '1.5.0',
        ], 2, 'low');
    }

    /**
     * Limpia mensajes expirados
     */
    public function cleanup_expired_messages() {
        global $wpdb;

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name}
             WHERE expires_at IS NOT NULL AND expires_at < %s",
            current_time('mysql')
        ));

        if ($deleted > 0) {
            error_log("[Gossip] Limpiados {$deleted} mensajes expirados");
        }
    }

    /**
     * Obtiene el peer local
     *
     * OPTIMIZADO: Usa caché para evitar consultas repetidas
     *
     * @return object|null
     */
    private function get_local_peer() {
        $cache = function_exists('flavor_mesh_cache') ? flavor_mesh_cache() : null;

        if ($cache) {
            return $cache->get_local_peer();
        }

        return Flavor_Network_Installer::get_local_peer();
    }

    /**
     * Obtiene un mensaje por ID
     *
     * OPTIMIZADO: Usa caché de memoria y selecciona solo columnas necesarias
     *
     * @param string $message_id
     * @return object|null
     */
    private function get_message($message_id) {
        $cache = function_exists('flavor_mesh_cache') ? flavor_mesh_cache() : null;

        if ($cache) {
            $cached = $cache->get("msg_{$message_id}");
            if ($cached !== null) {
                return $cached;
            }
        }

        global $wpdb;
        $message = $wpdb->get_row($wpdb->prepare(
            "SELECT id, message_id, origin_peer_id, message_type, payload,
                    signature, ttl, hop_count, propagation_path, vector_clock,
                    priority, processed, forwarded, forwarded_to
             FROM {$this->table_name}
             WHERE message_id = %s",
            $message_id
        ));

        if ($cache && $message) {
            $cache->set("msg_{$message_id}", $message, 60);
        }

        return $message;
    }

    /**
     * Marca un mensaje como reenviado
     *
     * @param int $id ID de BD
     * @param array $forwarded_to Lista de peer_ids
     */
    private function mark_forwarded($id, array $forwarded_to) {
        global $wpdb;
        $wpdb->update(
            $this->table_name,
            [
                'forwarded'    => 1,
                'forwarded_to' => wp_json_encode($forwarded_to),
            ],
            ['id' => $id]
        );
    }

    /**
     * Firma un mensaje con la clave privada del peer local
     *
     * @param string $message
     * @param object $local_peer
     * @return string Firma en base64
     */
    private function sign_message($message, $local_peer) {
        $private_key = Flavor_Network_Installer::decrypt_private_key(
            $local_peer->private_key_encrypted
        );

        if (!$private_key) {
            return '';
        }

        $signature = sodium_crypto_sign_detached($message, $private_key);
        sodium_memzero($private_key);

        return base64_encode($signature);
    }

    /**
     * Firma una request para autenticación
     *
     * @param array $payload
     * @param int $timestamp
     * @param object $local_peer
     * @return string
     */
    private function sign_request($payload, $timestamp, $local_peer) {
        $message = wp_json_encode($payload) . '|' . $timestamp;
        return $this->sign_message($message, $local_peer);
    }

    /**
     * Verifica la firma de un mensaje
     *
     * OPTIMIZADO: Usa caché para claves públicas
     *
     * @param string $message
     * @param string $signature Base64
     * @param string $peer_id
     * @return bool
     */
    private function verify_signature($message, $signature, $peer_id) {
        // Usar caché para claves públicas (TTL largo, cambian raramente)
        $cache = function_exists('flavor_mesh_cache') ? flavor_mesh_cache() : null;
        $public_key_base64 = null;

        if ($cache) {
            $public_key_base64 = $cache->get_peer_public_key($peer_id);
        }

        if ($public_key_base64 === null) {
            global $wpdb;
            $public_key_base64 = $wpdb->get_var($wpdb->prepare(
                "SELECT public_key_ed25519 FROM {$this->peers_table} WHERE peer_id = %s",
                $peer_id
            ));
        }

        if (!$public_key_base64) {
            // Peer desconocido - aceptar pero con precaución
            return true; // TODO: Implementar registro de peers desconocidos
        }

        try {
            $public_key = base64_decode($public_key_base64);
            $signature_bytes = base64_decode($signature);
            return sodium_crypto_sign_verify_detached($signature_bytes, $message, $public_key);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Registra éxito de comunicación con un peer
     *
     * @param string $peer_id
     */
    private function record_peer_success($peer_id) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->peers_table}
             SET is_online = 1,
                 last_seen = %s,
                 connection_failures = 0,
                 last_successful_sync = %s
             WHERE peer_id = %s",
            current_time('mysql'),
            current_time('mysql'),
            $peer_id
        ));
    }

    /**
     * Registra fallo de comunicación con un peer
     *
     * @param string $peer_id
     */
    private function record_peer_failure($peer_id) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->peers_table}
             SET connection_failures = connection_failures + 1,
                 is_online = CASE WHEN connection_failures >= 4 THEN 0 ELSE is_online END
             WHERE peer_id = %s",
            $peer_id
        ));
    }

    /**
     * Obtiene el vector clock local
     *
     * @return Flavor_Vector_Clock
     */
    private function get_local_vector_clock() {
        require_once dirname(__DIR__) . '/crdt/class-vector-clock.php';

        $clock_json = get_option('flavor_mesh_local_vector_clock', '{}');
        return Flavor_Vector_Clock::from_json($clock_json);
    }

    /**
     * Obtiene el uptime del sistema
     *
     * @return int Segundos desde activación
     */
    private function get_uptime() {
        $activated = get_option('flavor_mesh_activated_at', time());
        return time() - $activated;
    }

    /**
     * Procesa batch de mensajes gossip pendientes de reenvío
     *
     * Este método es llamado por el cron job cada minuto para
     * procesar mensajes que no fueron reenviados inmediatamente.
     *
     * OPTIMIZADO: Usa requests paralelas con curl_multi en lugar
     * de envíos secuenciales para mejor rendimiento.
     *
     * @since 1.5.0
     * @param int $limit Máximo de mensajes a procesar por batch
     * @return int Número de mensajes procesados
     */
    public function process_pending_batch($limit = 50) {
        global $wpdb;

        // OPTIMIZADO: Solo seleccionar columnas necesarias
        $pending_messages = $wpdb->get_results($wpdb->prepare(
            "SELECT id, message_id, origin_peer_id, message_type, payload,
                    signature, ttl, hop_count, propagation_path, vector_clock,
                    priority, forwarded_to
             FROM {$this->table_name}
             WHERE forwarded = 0
               AND ttl > 0
               AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY
               CASE priority
                 WHEN 'urgent' THEN 1
                 WHEN 'high' THEN 2
                 WHEN 'normal' THEN 3
                 WHEN 'low' THEN 4
               END,
               created_at ASC
             LIMIT %d",
            $limit
        ));

        if (empty($pending_messages)) {
            return 0;
        }

        // Preparar datos del peer local una sola vez
        $local_peer = class_exists('Flavor_Network_Peer')
            ? Flavor_Network_Peer::get_local()
            : null;

        $timestamp = time();
        $all_requests = [];           // Todas las requests a enviar
        $message_targets = [];        // Mapeo mensaje -> targets
        $messages_without_targets = [];

        // FASE 1: Recopilar todos los targets y preparar requests
        foreach ($pending_messages as $message) {
            $propagation_path = json_decode($message->propagation_path, true) ?: [];
            $exclude_peers = array_merge(
                [$message->origin_peer_id],
                $propagation_path,
                json_decode($message->forwarded_to ?: '[]', true)
            );

            $targets = $this->select_gossip_targets(3, (object) [
                'exclude_peers' => $exclude_peers,
            ]);

            if (empty($targets)) {
                $messages_without_targets[] = $message->id;
                continue;
            }

            $message_targets[$message->id] = [];

            // Preparar datos del mensaje forward
            $forward_data = $this->prepare_forward_data($message, $local_peer);
            $headers = $this->prepare_forward_headers($local_peer, $message->message_id, $timestamp);

            foreach ($targets as $target_peer) {
                if (empty($target_peer->site_url)) {
                    continue;
                }

                $request_key = $message->id . '_' . $target_peer->peer_id;
                $all_requests[$request_key] = [
                    'url'     => rtrim($target_peer->site_url, '/') . '/wp-json/flavor-mesh/v1/gossip/receive',
                    'data'    => $forward_data,
                    'headers' => $headers,
                ];
                $message_targets[$message->id][$request_key] = $target_peer->peer_id;
            }
        }

        // Marcar mensajes sin targets como completados
        if (!empty($messages_without_targets)) {
            $ids_placeholder = implode(',', array_fill(0, count($messages_without_targets), '%d'));
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->table_name} SET forwarded = 1 WHERE id IN ($ids_placeholder)",
                ...$messages_without_targets
            ));
        }

        if (empty($all_requests)) {
            return count($messages_without_targets);
        }

        // FASE 2: Enviar todas las requests en paralelo
        $async = function_exists('flavor_mesh_async') ? flavor_mesh_async() : null;

        if ($async) {
            // Usar sistema async optimizado
            $results = $async->send_parallel(array_values($all_requests), 5);
            $request_keys = array_keys($all_requests);
        } else {
            // Fallback a envío secuencial
            $results = [];
            $request_keys = array_keys($all_requests);
            foreach ($all_requests as $key => $request) {
                $response = wp_remote_post($request['url'], [
                    'body'      => wp_json_encode($request['data']),
                    'headers'   => $request['headers'],
                    'timeout'   => 5,
                    'sslverify' => apply_filters('flavor_mesh_ssl_verify', true),
                ]);
                $code = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
                $results[] = [
                    'success' => ($code >= 200 && $code < 300),
                ];
            }
        }

        // FASE 3: Procesar resultados y actualizar DB
        $peer_successes = [];
        $peer_failures = [];
        $message_forwards = [];

        foreach ($results as $index => $result) {
            $request_key = $request_keys[$index];
            list($message_id, $peer_id) = explode('_', $request_key, 2);

            if (!isset($message_forwards[$message_id])) {
                $message_forwards[$message_id] = [];
            }

            if ($result['success']) {
                $message_forwards[$message_id][] = $peer_id;
                $peer_successes[$peer_id] = ($peer_successes[$peer_id] ?? 0) + 1;
            } else {
                $peer_failures[$peer_id] = ($peer_failures[$peer_id] ?? 0) + 1;
            }
        }

        // Batch update mensajes
        foreach ($message_forwards as $message_id => $forwarded_peers) {
            $original_message = null;
            foreach ($pending_messages as $msg) {
                if ($msg->id == $message_id) {
                    $original_message = $msg;
                    break;
                }
            }

            $existing_forwards = $original_message
                ? json_decode($original_message->forwarded_to ?: '[]', true)
                : [];

            $wpdb->update(
                $this->table_name,
                [
                    'forwarded'    => 1,
                    'forwarded_to' => wp_json_encode(array_merge($existing_forwards, $forwarded_peers)),
                ],
                ['id' => $message_id]
            );
        }

        // Batch update peer stats (optimizado)
        $this->batch_update_peer_stats($peer_successes, $peer_failures);

        $processed_count = count($message_forwards) + count($messages_without_targets);

        if ($processed_count > 0) {
            $request_count = count($all_requests);
            $success_count = array_sum($peer_successes);
            error_log("[Flavor Mesh] Gossip batch: {$processed_count} msgs, {$request_count} requests paralelas, {$success_count} exitosas.");
        }

        return $processed_count;
    }

    /**
     * Prepara datos para forward de mensaje
     *
     * @param object $message Mensaje original
     * @param object|null $local_peer Peer local
     * @return array Datos preparados
     */
    private function prepare_forward_data($message, $local_peer = null) {
        $local_peer = $local_peer ?: $this->get_local_peer();
        $local_peer_id = $local_peer ? $local_peer->peer_id : '';

        return [
            'message_id'       => $message->message_id,
            'origin_peer_id'   => $message->origin_peer_id,
            'message_type'     => $message->message_type,
            'payload'          => json_decode($message->payload, true),
            'signature'        => $message->signature,
            'ttl'              => max(0, $message->ttl - 1),
            'hop_count'        => $message->hop_count + 1,
            'propagation_path' => array_merge(
                json_decode($message->propagation_path, true) ?: [],
                [$local_peer_id]
            ),
            'vector_clock'     => json_decode($message->vector_clock, true),
            'priority'         => $message->priority,
            'sender_peer_id'   => $local_peer_id,
        ];
    }

    /**
     * Prepara headers para forward
     *
     * @param object|null $local_peer Peer local
     * @param string $message_id ID del mensaje
     * @param int $timestamp Timestamp
     * @return array Headers
     */
    private function prepare_forward_headers($local_peer, $message_id, $timestamp) {
        $local_peer_id = $local_peer ? $local_peer->peer_id : '';

        $headers = [
            'Content-Type'      => 'application/json',
            'X-Mesh-Peer-Id'    => $local_peer_id,
            'X-Mesh-Timestamp'  => $timestamp,
        ];

        if ($local_peer) {
            $sign_data = $message_id . ':' . $timestamp;
            $signature = $this->sign_message($sign_data, $local_peer);
            if ($signature) {
                $headers['X-Mesh-Signature'] = $signature;
            }
        }

        return $headers;
    }

    /**
     * Actualiza estadísticas de peers en batch
     *
     * @param array $successes Peer ID => count
     * @param array $failures Peer ID => count
     */
    private function batch_update_peer_stats(array $successes, array $failures) {
        global $wpdb;

        // Éxitos: reset failures, update last_seen
        if (!empty($successes)) {
            $peer_ids = array_keys($successes);
            $placeholders = implode(',', array_fill(0, count($peer_ids), '%s'));
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->peers_table}
                 SET connection_failures = 0, last_seen = NOW(), is_online = 1
                 WHERE peer_id IN ($placeholders)",
                ...$peer_ids
            ));
        }

        // Fallos: increment failures, mark offline if >= 4
        if (!empty($failures)) {
            foreach ($failures as $peer_id => $count) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$this->peers_table}
                     SET connection_failures = connection_failures + %d,
                         is_online = CASE WHEN connection_failures + %d >= 4 THEN 0 ELSE is_online END
                     WHERE peer_id = %s",
                    $count,
                    $count,
                    $peer_id
                ));
            }
        }
    }

    /**
     * Reenvía un mensaje a un peer específico
     *
     * @param object $message Mensaje a reenviar
     * @param object $target_peer Peer destino
     * @return bool Éxito del envío
     */
    private function forward_to_peer($message, $target_peer) {
        if (empty($target_peer->site_url)) {
            return false;
        }

        $endpoint = rtrim($target_peer->site_url, '/') . '/wp-json/flavor-mesh/v1/gossip/receive';

        // Preparar datos del mensaje con TTL decrementado
        $forward_data = [
            'message_id'       => $message->message_id,
            'origin_peer_id'   => $message->origin_peer_id,
            'message_type'     => $message->message_type,
            'payload'          => json_decode($message->payload, true),
            'signature'        => $message->signature,
            'ttl'              => max(0, $message->ttl - 1),
            'hop_count'        => $message->hop_count + 1,
            'propagation_path' => array_merge(
                json_decode($message->propagation_path, true) ?: [],
                [$this->local_peer_id]
            ),
            'vector_clock'     => json_decode($message->vector_clock, true),
            'priority'         => $message->priority,
        ];

        // Agregar firma del forwarder
        $local_peer = class_exists('Flavor_Network_Peer')
            ? Flavor_Network_Peer::get_local()
            : null;

        $headers = [
            'Content-Type'      => 'application/json',
            'X-Mesh-Peer-Id'    => $this->local_peer_id,
            'X-Mesh-Timestamp'  => time(),
        ];

        if ($local_peer) {
            $sign_data = $message->message_id . ':' . $headers['X-Mesh-Timestamp'];
            $signature = $local_peer->sign($sign_data);
            if ($signature) {
                $headers['X-Mesh-Signature'] = $signature;
            }
        }

        $response = wp_remote_post($endpoint, [
            'body'      => wp_json_encode($forward_data),
            'headers'   => $headers,
            'timeout'   => 5,
            'blocking'  => true,
            'sslverify' => apply_filters('flavor_mesh_ssl_verify', true),
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        return $code >= 200 && $code < 300;
    }

    /**
     * Obtiene estadísticas del protocolo gossip
     *
     * OPTIMIZADO: Usa caché para reducir consultas frecuentes.
     *
     * @return array
     */
    public function get_stats() {
        $cache = function_exists('flavor_mesh_cache') ? flavor_mesh_cache() : null;

        if ($cache) {
            return $cache->remember('gossip_protocol_stats', function() {
                return $this->calculate_stats();
            }, 60); // Cache 1 minuto
        }

        return $this->calculate_stats();
    }

    /**
     * Calcula estadísticas (interno)
     *
     * @return array
     */
    private function calculate_stats() {
        global $wpdb;

        // Una sola query optimizada en lugar de 5 queries separadas
        $stats_query = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN forwarded = 0 AND ttl > 0 THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN processed = 1 THEN 1 ELSE 0 END) as processed
            FROM {$this->table_name}
        ";

        $basic_stats = $wpdb->get_row($stats_query);

        return [
            'total_messages'  => (int) ($basic_stats->total ?? 0),
            'pending_forward' => (int) ($basic_stats->pending ?? 0),
            'processed'       => (int) ($basic_stats->processed ?? 0),
            'by_type'         => $wpdb->get_results(
                "SELECT message_type, COUNT(*) as count FROM {$this->table_name} GROUP BY message_type",
                OBJECT_K
            ),
            'active_peers'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->peers_table} WHERE is_online = 1"),
        ];
    }
}

// Registrar intervalos de cron personalizados
add_filter('cron_schedules', function ($schedules) {
    if (!isset($schedules['every_minute'])) {
        $schedules['every_minute'] = [
            'interval' => 60,
            'display'  => __('Every Minute'),
        ];
    }
    if (!isset($schedules['every_5_minutes'])) {
        $schedules['every_5_minutes'] = [
            'interval' => 300,
            'display'  => __('Every 5 Minutes'),
        ];
    }
    return $schedules;
});
