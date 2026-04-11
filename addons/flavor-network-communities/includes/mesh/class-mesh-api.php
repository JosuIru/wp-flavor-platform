<?php
/**
 * REST API para la red mesh P2P
 *
 * Endpoints para gossip, peer discovery, sincronización y CRDT merge.
 *
 * @package FlavorPlatform\Network\Mesh
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Mesh_API {

    /**
     * Namespace de la API
     */
    const NAMESPACE = 'flavor-mesh/v1';

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
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
     * Registra las rutas REST
     */
    public function register_routes() {
        // ═══════════════════════════════════════════════════════════════
        // GOSSIP ENDPOINTS
        // ═══════════════════════════════════════════════════════════════

        // Recibir mensaje gossip
        register_rest_route(self::NAMESPACE, '/gossip/receive', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'receive_gossip'],
            'permission_callback' => [$this, 'verify_peer_signature'],
        ]);

        // ═══════════════════════════════════════════════════════════════
        // PEER ENDPOINTS
        // ═══════════════════════════════════════════════════════════════

        // Listar peers (público para bootstrap)
        register_rest_route(self::NAMESPACE, '/peers/list', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'list_peers'],
            'permission_callback' => '__return_true',
        ]);

        // Intercambiar listas de peers (PEX)
        register_rest_route(self::NAMESPACE, '/peers/exchange', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'peer_exchange'],
            'permission_callback' => [$this, 'verify_peer_signature'],
        ]);

        // Obtener info de un peer específico
        register_rest_route(self::NAMESPACE, '/peers/(?P<peer_id>[a-f0-9]{64})', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_peer'],
            'permission_callback' => '__return_true',
        ]);

        // ═══════════════════════════════════════════════════════════════
        // SYNC ENDPOINTS
        // ═══════════════════════════════════════════════════════════════

        // Enviar datos sincronizados
        register_rest_route(self::NAMESPACE, '/sync/push', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'sync_push'],
            'permission_callback' => [$this, 'verify_peer_signature'],
        ]);

        // Solicitar datos sincronizados
        register_rest_route(self::NAMESPACE, '/sync/pull', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'sync_pull'],
            'permission_callback' => [$this, 'verify_peer_signature'],
        ]);

        // ═══════════════════════════════════════════════════════════════
        // CRDT ENDPOINTS
        // ═══════════════════════════════════════════════════════════════

        // Merge de estado CRDT
        register_rest_route(self::NAMESPACE, '/crdt/merge', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'crdt_merge'],
            'permission_callback' => [$this, 'verify_peer_signature'],
        ]);

        // Obtener estado CRDT de un documento
        register_rest_route(self::NAMESPACE, '/crdt/(?P<doc_type>[a-z_]+)/(?P<doc_id>[a-zA-Z0-9_-]+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_crdt_state'],
            'permission_callback' => [$this, 'verify_peer_signature'],
        ]);

        // ═══════════════════════════════════════════════════════════════
        // MESH CONNECTION ENDPOINTS
        // ═══════════════════════════════════════════════════════════════

        // Solicitar conexión mesh
        register_rest_route(self::NAMESPACE, '/mesh/connect', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'mesh_connect'],
            'permission_callback' => '__return_true', // Cualquiera puede solicitar
        ]);

        // Completar handshake
        register_rest_route(self::NAMESPACE, '/mesh/handshake', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'mesh_handshake'],
            'permission_callback' => [$this, 'verify_peer_signature'],
        ]);

        // ═══════════════════════════════════════════════════════════════
        // HEALTH & STATUS
        // ═══════════════════════════════════════════════════════════════

        // Health check
        register_rest_route(self::NAMESPACE, '/health', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'health_check'],
            'permission_callback' => '__return_true',
        ]);

        // Heartbeat
        register_rest_route(self::NAMESPACE, '/heartbeat', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'heartbeat'],
            'permission_callback' => [$this, 'verify_peer_signature'],
        ]);

        // Estadísticas de la red
        register_rest_route(self::NAMESPACE, '/stats', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_stats'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // Topología de la red (para visualización)
        register_rest_route(self::NAMESPACE, '/topology', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_topology'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // PERMISSION CALLBACKS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Verifica la firma del peer remoto
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function verify_peer_signature(WP_REST_Request $request) {
        $peer_id = $request->get_header('X-Mesh-Peer-Id');
        $timestamp = $request->get_header('X-Mesh-Timestamp');
        $signature = $request->get_header('X-Mesh-Signature');

        if (empty($peer_id) || empty($timestamp) || empty($signature)) {
            return new WP_Error(
                'missing_auth_headers',
                'Missing authentication headers',
                ['status' => 401]
            );
        }

        // Verificar que el timestamp no sea muy antiguo (5 minutos)
        if (abs(time() - (int) $timestamp) > 300) {
            return new WP_Error(
                'expired_timestamp',
                'Request timestamp is too old',
                ['status' => 401]
            );
        }

        // Obtener clave pública del peer
        global $wpdb;
        $peers_table = $wpdb->prefix . 'flavor_network_peers';

        $public_key_base64 = $wpdb->get_var($wpdb->prepare(
            "SELECT public_key_ed25519 FROM {$peers_table} WHERE peer_id = %s",
            $peer_id
        ));

        // Si no conocemos al peer, aceptar pero marcarlo como desconocido
        if (empty($public_key_base64)) {
            // Registrar peer desconocido
            $request->set_param('_unknown_peer', true);
            return true;
        }

        // Verificar firma
        $body = $request->get_body();
        $message = $body . '|' . $timestamp;

        try {
            $public_key = base64_decode($public_key_base64);
            $signature_bytes = base64_decode($signature);

            $valid = sodium_crypto_sign_verify_detached($signature_bytes, $message, $public_key);

            if (!$valid) {
                return new WP_Error(
                    'invalid_signature',
                    'Signature verification failed',
                    ['status' => 401]
                );
            }

            $request->set_param('_verified_peer_id', $peer_id);
            return true;
        } catch (Exception $e) {
            return new WP_Error(
                'signature_error',
                'Error verifying signature: ' . $e->getMessage(),
                ['status' => 401]
            );
        }
    }

    /**
     * Verifica permiso de administrador
     *
     * @return bool
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }

    // ═══════════════════════════════════════════════════════════════════
    // GOSSIP HANDLERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Recibe un mensaje gossip
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function receive_gossip(WP_REST_Request $request) {
        $data = $request->get_json_params();

        $gossip = Flavor_Gossip_Protocol::instance();
        $result = $gossip->receive_message($data);

        return new WP_REST_Response($result, $result['status'] === 'accepted' ? 200 : 400);
    }

    // ═══════════════════════════════════════════════════════════════════
    // PEER HANDLERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Lista peers conocidos (público para bootstrap)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function list_peers(WP_REST_Request $request) {
        $limit = min(50, (int) $request->get_param('limit') ?: 20);

        $discovery = Flavor_Peer_Discovery::instance();
        $peers = $discovery->get_shareable_peers($limit);

        $local_peer = Flavor_Network_Installer::get_local_peer();

        return new WP_REST_Response([
            'peers'    => $peers,
            'local'    => $local_peer ? [
                'peer_id'      => $local_peer->peer_id,
                'display_name' => $local_peer->display_name,
            ] : null,
            'count'    => count($peers),
            'version'  => '1.5.0',
        ]);
    }

    /**
     * Intercambio de peers (PEX)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function peer_exchange(WP_REST_Request $request) {
        $their_peers = $request->get_param('peers') ?: [];

        // Registrar sus peers
        $discovery = Flavor_Peer_Discovery::instance();
        $new_count = 0;

        foreach ($their_peers as $peer_data) {
            if ($discovery->register_peer($peer_data)) {
                $new_count++;
            }
        }

        // Devolver nuestros peers
        $our_peers = $discovery->get_shareable_peers(20);

        return new WP_REST_Response([
            'peers'          => $our_peers,
            'received_count' => count($their_peers),
            'new_count'      => $new_count,
        ]);
    }

    /**
     * Obtiene información de un peer
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_peer(WP_REST_Request $request) {
        $peer_id = $request->get_param('peer_id');

        $discovery = Flavor_Peer_Discovery::instance();
        $peer = $discovery->get_peer($peer_id);

        if (!$peer) {
            return new WP_Error(
                'peer_not_found',
                'Peer not found',
                ['status' => 404]
            );
        }

        return new WP_REST_Response([
            'peer_id'      => $peer->peer_id,
            'display_name' => $peer->display_name,
            'site_url'     => $peer->site_url,
            'trust_level'  => $peer->trust_level,
            'is_online'    => (bool) $peer->is_online,
            'last_seen'    => $peer->last_seen,
            'capabilities' => json_decode($peer->capabilities ?? '{}', true),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // SYNC HANDLERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Recibe datos sincronizados
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function sync_push(WP_REST_Request $request) {
        $documents = $request->get_param('documents') ?: [];
        $sender_peer_id = $request->get_param('_verified_peer_id') ?: '';

        $crdt_manager = Flavor_CRDT_Manager::instance();
        $results = [];

        foreach ($documents as $doc) {
            $success = $crdt_manager->import_document($doc, $sender_peer_id);
            $results[$doc['doc_type'] . ':' . $doc['doc_id']] = $success;
        }

        // Log de sincronización
        $this->log_sync($sender_peer_id, 'push', count($documents));

        return new WP_REST_Response([
            'status'    => 'accepted',
            'processed' => count($documents),
            'results'   => $results,
        ]);
    }

    /**
     * Solicita datos sincronizados
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function sync_pull(WP_REST_Request $request) {
        $doc_types = $request->get_param('doc_types') ?: [];
        $since_timestamp = $request->get_param('since');
        $vector_clock_json = $request->get_param('vector_clock');

        global $wpdb;
        $crdt_table = $wpdb->prefix . 'flavor_network_crdt_state';

        $documents = [];

        // Obtener documentos actualizados
        $sql = "SELECT DISTINCT doc_type, doc_id FROM {$crdt_table} WHERE 1=1";

        if (!empty($doc_types)) {
            $placeholders = implode(',', array_fill(0, count($doc_types), '%s'));
            $sql .= $wpdb->prepare(" AND doc_type IN ({$placeholders})", $doc_types);
        }

        if ($since_timestamp) {
            $sql .= $wpdb->prepare(" AND updated_at > %s", date('Y-m-d H:i:s', $since_timestamp));
        }

        $sql .= " LIMIT 100";

        $rows = $wpdb->get_results($sql);
        $crdt_manager = Flavor_CRDT_Manager::instance();

        foreach ($rows as $row) {
            $documents[] = $crdt_manager->export_document($row->doc_type, $row->doc_id);
        }

        return new WP_REST_Response([
            'documents' => $documents,
            'count'     => count($documents),
            'timestamp' => time(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // CRDT HANDLERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Merge de estado CRDT
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function crdt_merge(WP_REST_Request $request) {
        $doc_type = $request->get_param('doc_type');
        $doc_id = $request->get_param('doc_id');
        $field_name = $request->get_param('field_name');
        $state_data = $request->get_param('state_data');
        $sender_peer_id = $request->get_param('_verified_peer_id') ?: '';

        if (empty($doc_type) || empty($doc_id) || empty($field_name)) {
            return new WP_Error(
                'missing_params',
                'Missing required parameters',
                ['status' => 400]
            );
        }

        $crdt_manager = Flavor_CRDT_Manager::instance();
        $success = $crdt_manager->merge_remote($doc_type, $doc_id, $field_name, $state_data, $sender_peer_id);

        return new WP_REST_Response([
            'status'  => $success ? 'merged' : 'failed',
            'doc_id'  => $doc_id,
        ]);
    }

    /**
     * Obtiene estado CRDT de un documento
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_crdt_state(WP_REST_Request $request) {
        $doc_type = $request->get_param('doc_type');
        $doc_id = $request->get_param('doc_id');

        $crdt_manager = Flavor_CRDT_Manager::instance();
        $export = $crdt_manager->export_document($doc_type, $doc_id);

        return new WP_REST_Response($export);
    }

    // ═══════════════════════════════════════════════════════════════════
    // MESH CONNECTION HANDLERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Solicitud de conexión mesh
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function mesh_connect(WP_REST_Request $request) {
        $remote_peer_id = $request->get_param('peer_id');
        $remote_public_key = $request->get_param('public_key');
        $remote_site_url = $request->get_param('site_url');
        $remote_display_name = $request->get_param('display_name');

        if (empty($remote_peer_id) || empty($remote_public_key)) {
            return new WP_Error(
                'missing_params',
                'Missing peer_id or public_key',
                ['status' => 400]
            );
        }

        // Verificar que el peer_id corresponde a la clave pública
        $discovery = Flavor_Peer_Discovery::instance();
        if (!$discovery->verify_peer_key($remote_peer_id, $remote_public_key)) {
            // Intentar registrar como nuevo peer
            $discovery->register_peer([
                'peer_id'      => $remote_peer_id,
                'public_key'   => $remote_public_key,
                'site_url'     => $remote_site_url,
                'display_name' => $remote_display_name,
            ]);
        }

        // Obtener peer local
        $local_peer = Flavor_Network_Installer::get_local_peer();
        if (!$local_peer) {
            return new WP_Error(
                'no_local_peer',
                'Local peer not configured',
                ['status' => 500]
            );
        }

        // Verificar si podemos conectar
        $topology = Flavor_Mesh_Topology::instance();
        $check = $topology->can_connect($local_peer->peer_id, $remote_peer_id);

        if (!$check['can_connect']) {
            return new WP_Error(
                'cannot_connect',
                $check['reason'],
                ['status' => 409]
            );
        }

        // Crear conexión pendiente
        $connection_id = $topology->create_connection($local_peer->peer_id, $remote_peer_id);

        if (!$connection_id) {
            return new WP_Error(
                'connection_failed',
                'Failed to create connection',
                ['status' => 500]
            );
        }

        // Devolver nuestros datos para el handshake
        return new WP_REST_Response([
            'status'        => 'pending',
            'connection_id' => $connection_id,
            'peer_id'       => $local_peer->peer_id,
            'public_key'    => $local_peer->public_key_ed25519,
            'display_name'  => $local_peer->display_name,
            'site_url'      => $local_peer->site_url,
        ]);
    }

    /**
     * Completar handshake de conexión
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function mesh_handshake(WP_REST_Request $request) {
        $connection_id = $request->get_param('connection_id');
        $remote_peer_id = $request->get_param('_verified_peer_id');

        if (empty($connection_id)) {
            return new WP_Error(
                'missing_connection_id',
                'Connection ID required',
                ['status' => 400]
            );
        }

        $topology = Flavor_Mesh_Topology::instance();

        // Generar hash del secreto compartido (simplificado)
        $shared_secret_hash = hash('sha256', $connection_id . $remote_peer_id . time());

        $success = $topology->activate_connection($connection_id, $shared_secret_hash);

        if (!$success) {
            return new WP_Error(
                'handshake_failed',
                'Failed to complete handshake',
                ['status' => 500]
            );
        }

        return new WP_REST_Response([
            'status'        => 'connected',
            'connection_id' => $connection_id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // HEALTH & STATUS HANDLERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Health check
     *
     * @return WP_REST_Response
     */
    public function health_check() {
        $local_peer = Flavor_Network_Installer::get_local_peer();

        return new WP_REST_Response([
            'status'    => 'ok',
            'peer_id'   => $local_peer ? $local_peer->peer_id : null,
            'version'   => '1.5.0',
            'timestamp' => time(),
        ]);
    }

    /**
     * Heartbeat
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function heartbeat(WP_REST_Request $request) {
        $remote_peer_id = $request->get_param('_verified_peer_id');
        $remote_timestamp = $request->get_param('timestamp');

        if ($remote_peer_id) {
            // Actualizar last_seen del peer
            global $wpdb;
            $peers_table = $wpdb->prefix . 'flavor_network_peers';

            $wpdb->update(
                $peers_table,
                [
                    'is_online'  => 1,
                    'last_seen'  => current_time('mysql'),
                ],
                ['peer_id' => $remote_peer_id]
            );
        }

        $local_peer = Flavor_Network_Installer::get_local_peer();

        return new WP_REST_Response([
            'status'    => 'pong',
            'peer_id'   => $local_peer ? $local_peer->peer_id : null,
            'timestamp' => time(),
        ]);
    }

    /**
     * Estadísticas de la red (solo admin)
     *
     * @return WP_REST_Response
     */
    public function get_stats() {
        $gossip_stats = Flavor_Gossip_Protocol::instance()->get_stats();
        $topology_stats = Flavor_Mesh_Topology::instance()->get_topology_stats();
        $discovery_stats = Flavor_Peer_Discovery::instance()->get_stats();
        $crdt_stats = Flavor_CRDT_Manager::instance()->get_stats();

        return new WP_REST_Response([
            'gossip'    => $gossip_stats,
            'topology'  => $topology_stats,
            'discovery' => $discovery_stats,
            'crdt'      => $crdt_stats,
            'version'   => '1.5.0',
            'timestamp' => time(),
        ]);
    }

    /**
     * Topología para visualización (solo admin)
     *
     * @return WP_REST_Response
     */
    public function get_topology() {
        $topology = Flavor_Mesh_Topology::instance();
        $graph = $topology->export_graph();

        return new WP_REST_Response([
            'graph'     => $graph,
            'stats'     => $topology->get_topology_stats(),
            'timestamp' => time(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Log de sincronización
     *
     * @param string $peer_id
     * @param string $type
     * @param int $count
     */
    private function log_sync($peer_id, $type, $count) {
        global $wpdb;
        $log_table = $wpdb->prefix . 'flavor_network_sync_log';

        $wpdb->insert($log_table, [
            'peer_id'          => $peer_id,
            'sync_type'        => 'crdt_merge',
            'direction'        => $type,
            'entities_synced'  => $count,
            'status'           => 'completed',
            'completed_at'     => current_time('mysql'),
        ]);
    }
}
