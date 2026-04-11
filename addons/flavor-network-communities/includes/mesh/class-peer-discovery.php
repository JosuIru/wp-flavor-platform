<?php
/**
 * Peer Discovery para red mesh descentralizada
 *
 * Implementa descubrimiento de peers sin DHT completo:
 * - Bootstrap Nodes: nodos conocidos para iniciar
 * - Peer Exchange (PEX): intercambiar listas de peers
 * - Anuncio via Gossip: peer_announce message type
 *
 * @package FlavorPlatform\Network\Mesh
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Peer_Discovery {

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Bootstrap nodes oficiales por defecto
     *
     * @var array
     */
    private $default_bootstrap_nodes = [
        // En producción, esto tendría nodos oficiales de la red
        // Por ahora vacío para desarrollo local
    ];

    /**
     * Tabla de peers
     *
     * @var string
     */
    private $peers_table;

    /**
     * Tabla de bootstrap nodes
     *
     * @var string
     */
    private $bootstrap_table;

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        global $wpdb;
        $this->peers_table = $wpdb->prefix . 'flavor_network_peers';
        $this->bootstrap_table = $wpdb->prefix . 'flavor_network_bootstrap_nodes';

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
     * Inicializa hooks
     */
    private function init_hooks() {
        // Cron para descubrimiento periódico
        add_action('flavor_mesh_peer_discovery', [$this, 'run_discovery']);

        if (!wp_next_scheduled('flavor_mesh_peer_discovery')) {
            wp_schedule_event(time(), 'hourly', 'flavor_mesh_peer_discovery');
        }

        // Procesar anuncios de peers via gossip
        add_action('flavor_mesh_process_peer_announce', [$this, 'handle_peer_announce'], 10, 2);
        add_action('flavor_mesh_process_peer_exchange', [$this, 'handle_peer_exchange'], 10, 2);
    }

    /**
     * Ejecuta un ciclo completo de descubrimiento
     */
    public function run_discovery() {
        // 1. Contactar bootstrap nodes
        $this->discover_from_bootstrap();

        // 2. Intercambiar peers con conexiones existentes
        $this->exchange_with_connected_peers();

        // 3. Anunciar nuestra presencia
        $this->announce_self();
    }

    /**
     * Descubre peers desde nodos bootstrap
     *
     * @return int Número de nuevos peers descubiertos
     */
    public function discover_from_bootstrap() {
        $bootstrap_nodes = $this->get_bootstrap_nodes();

        if (empty($bootstrap_nodes)) {
            return 0;
        }

        $new_peers_count = 0;

        foreach ($bootstrap_nodes as $bootstrap) {
            $peers = $this->fetch_peer_list_from($bootstrap->url);

            if ($peers === false) {
                $this->record_bootstrap_failure($bootstrap->id);
                continue;
            }

            $this->record_bootstrap_success($bootstrap->id);

            foreach ($peers as $peer_data) {
                if ($this->register_peer($peer_data)) {
                    $new_peers_count++;
                }
            }
        }

        return $new_peers_count;
    }

    /**
     * Obtiene lista de peers desde un endpoint
     *
     * @param string $url URL base del nodo
     * @return array|false Lista de peers o false si falla
     */
    private function fetch_peer_list_from($url) {
        $endpoint = trailingslashit($url) . 'wp-json/flavor-mesh/v1/peers/list';

        $local_peer = Flavor_Network_Installer::get_local_peer();
        if (!$local_peer) {
            return false;
        }

        $response = wp_remote_get($endpoint, [
            'timeout' => 10,
            'headers' => [
                'X-Mesh-Peer-Id' => $local_peer->peer_id,
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return $body['peers'] ?? [];
    }

    /**
     * Intercambia listas de peers con conexiones existentes (PEX)
     *
     * @return int Número de nuevos peers descubiertos
     */
    public function exchange_with_connected_peers() {
        $topology = Flavor_Mesh_Topology::instance();
        $local_peer = Flavor_Network_Installer::get_local_peer();

        if (!$local_peer) {
            return 0;
        }

        $connected = $topology->get_connected_peers($local_peer->peer_id);
        if (empty($connected)) {
            return 0;
        }

        // Seleccionar algunos peers para intercambio (no todos)
        $targets = array_slice($connected, 0, 5);
        $new_peers_count = 0;

        foreach ($targets as $peer_id) {
            $peer = $this->get_peer($peer_id);
            if (!$peer || empty($peer->site_url)) {
                continue;
            }

            $result = $this->peer_exchange($peer);
            if ($result !== false) {
                $new_peers_count += $result;
            }
        }

        return $new_peers_count;
    }

    /**
     * Realiza intercambio de peers con un peer específico
     *
     * @param object $peer Peer para intercambiar
     * @return int|false Número de nuevos peers o false si falla
     */
    public function peer_exchange($peer) {
        $endpoint = trailingslashit($peer->site_url) . 'wp-json/flavor-mesh/v1/peers/exchange';

        $local_peer = Flavor_Network_Installer::get_local_peer();
        if (!$local_peer) {
            return false;
        }

        // Preparar nuestra lista de peers para compartir
        $our_peers = $this->get_shareable_peers(20);

        $response = wp_remote_post($endpoint, [
            'timeout' => 15,
            'body'    => wp_json_encode(['peers' => $our_peers]),
            'headers' => [
                'Content-Type'   => 'application/json',
                'X-Mesh-Peer-Id' => $local_peer->peer_id,
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $their_peers = $body['peers'] ?? [];

        $new_count = 0;
        foreach ($their_peers as $peer_data) {
            if ($this->register_peer($peer_data)) {
                $new_count++;
            }
        }

        return $new_count;
    }

    /**
     * Anuncia nuestra presencia en la red via gossip
     */
    public function announce_self() {
        $local_peer = Flavor_Network_Installer::get_local_peer();
        if (!$local_peer) {
            return;
        }

        $gossip = Flavor_Gossip_Protocol::instance();

        $gossip->gossip_message('peer_announce', [
            'peer_id'         => $local_peer->peer_id,
            'public_key'      => $local_peer->public_key_ed25519,
            'display_name'    => $local_peer->display_name,
            'site_url'        => $local_peer->site_url,
            'capabilities'    => json_decode($local_peer->capabilities ?? '{}', true),
            'timestamp'       => time(),
        ], 3, 'normal');
    }

    /**
     * Maneja un anuncio de peer recibido via gossip
     *
     * @param array $payload Datos del anuncio
     * @param object $message Mensaje gossip completo
     */
    public function handle_peer_announce($payload, $message) {
        $peer_id = $payload['peer_id'] ?? '';
        if (empty($peer_id)) {
            return;
        }

        // No registrar nuestro propio anuncio
        $local_peer = Flavor_Network_Installer::get_local_peer();
        if ($local_peer && $peer_id === $local_peer->peer_id) {
            return;
        }

        $this->register_peer([
            'peer_id'      => $peer_id,
            'public_key'   => $payload['public_key'] ?? '',
            'display_name' => $payload['display_name'] ?? '',
            'site_url'     => $payload['site_url'] ?? '',
            'capabilities' => $payload['capabilities'] ?? [],
        ]);
    }

    /**
     * Maneja una respuesta de peer exchange
     *
     * @param array $payload
     * @param object $message
     */
    public function handle_peer_exchange($payload, $message) {
        $peers = $payload['peers'] ?? [];

        foreach ($peers as $peer_data) {
            $this->register_peer($peer_data);
        }
    }

    /**
     * Registra un nuevo peer descubierto
     *
     * @param array $peer_data Datos del peer
     * @return bool True si es un nuevo peer
     */
    public function register_peer($peer_data) {
        global $wpdb;

        $peer_id = $peer_data['peer_id'] ?? '';
        if (empty($peer_id)) {
            return false;
        }

        // Verificar si ya existe
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->peers_table} WHERE peer_id = %s",
            $peer_id
        ));

        if ($existing) {
            // Actualizar last_seen si ya existe
            $wpdb->update(
                $this->peers_table,
                ['last_seen' => current_time('mysql')],
                ['peer_id' => $peer_id]
            );
            return false;
        }

        // No registrar nuestro propio peer
        $local_peer = Flavor_Network_Installer::get_local_peer();
        if ($local_peer && $peer_id === $local_peer->peer_id) {
            return false;
        }

        // Insertar nuevo peer
        $result = $wpdb->insert($this->peers_table, [
            'peer_id'            => $peer_id,
            'public_key_ed25519' => $peer_data['public_key'] ?? '',
            'display_name'       => $peer_data['display_name'] ?? '',
            'site_url'           => $peer_data['site_url'] ?? '',
            'capabilities'       => wp_json_encode($peer_data['capabilities'] ?? []),
            'trust_level'        => 'seen',
            'is_online'          => 0,
            'last_seen'          => current_time('mysql'),
            'metadata'           => wp_json_encode(['discovered_at' => time()]),
        ]);

        if ($result) {
            do_action('flavor_mesh_peer_discovered', $peer_id, $peer_data);
            return true;
        }

        return false;
    }

    /**
     * Obtiene lista de peers compartibles
     *
     * @param int $limit
     * @return array
     */
    public function get_shareable_peers($limit = 20) {
        global $wpdb;

        $peers = $wpdb->get_results($wpdb->prepare(
            "SELECT peer_id, public_key_ed25519, display_name, site_url, capabilities
             FROM {$this->peers_table}
             WHERE is_local_peer = 0
               AND site_url != ''
               AND trust_level IN ('seen', 'verified', 'trusted')
             ORDER BY last_seen DESC
             LIMIT %d",
            $limit
        ));

        $result = [];
        foreach ($peers as $peer) {
            $result[] = [
                'peer_id'      => $peer->peer_id,
                'public_key'   => $peer->public_key_ed25519,
                'display_name' => $peer->display_name,
                'site_url'     => $peer->site_url,
                'capabilities' => json_decode($peer->capabilities ?? '[]', true),
            ];
        }

        return $result;
    }

    /**
     * Obtiene un peer por ID
     *
     * @param string $peer_id
     * @return object|null
     */
    public function get_peer($peer_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->peers_table} WHERE peer_id = %s",
            $peer_id
        ));
    }

    /**
     * Obtiene todos los bootstrap nodes habilitados
     *
     * @return array
     */
    public function get_bootstrap_nodes() {
        global $wpdb;

        $table_exists = $wpdb->get_var(
            "SHOW TABLES LIKE '{$this->bootstrap_table}'"
        );

        if (!$table_exists) {
            return [];
        }

        return $wpdb->get_results(
            "SELECT * FROM {$this->bootstrap_table}
             WHERE is_enabled = 1
             ORDER BY priority ASC"
        );
    }

    /**
     * Añade un bootstrap node
     *
     * @param string $url URL del nodo
     * @param string $name Nombre descriptivo
     * @param bool $is_official Si es oficial
     * @return int|false ID del nodo o false
     */
    public function add_bootstrap_node($url, $name = '', $is_official = false) {
        global $wpdb;

        // Verificar que la URL no exista
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->bootstrap_table} WHERE url = %s",
            $url
        ));

        if ($existing) {
            return $existing;
        }

        $result = $wpdb->insert($this->bootstrap_table, [
            'url'         => $url,
            'name'        => $name,
            'is_official' => $is_official ? 1 : 0,
            'is_enabled'  => 1,
            'priority'    => $is_official ? 10 : 100,
        ]);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Registra éxito de conexión con bootstrap
     *
     * @param int $bootstrap_id
     */
    private function record_bootstrap_success($bootstrap_id) {
        global $wpdb;
        $wpdb->update(
            $this->bootstrap_table,
            [
                'last_check'     => current_time('mysql'),
                'last_success'   => current_time('mysql'),
                'failures_count' => 0,
            ],
            ['id' => $bootstrap_id]
        );
    }

    /**
     * Registra fallo de conexión con bootstrap
     *
     * @param int $bootstrap_id
     */
    private function record_bootstrap_failure($bootstrap_id) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->bootstrap_table}
             SET last_check = %s, failures_count = failures_count + 1
             WHERE id = %d",
            current_time('mysql'),
            $bootstrap_id
        ));
    }

    /**
     * Actualiza el trust level de un peer
     *
     * @param string $peer_id
     * @param string $trust_level unknown, seen, verified, trusted
     * @return bool
     */
    public function update_trust_level($peer_id, $trust_level) {
        global $wpdb;

        $valid_levels = ['unknown', 'seen', 'verified', 'trusted'];
        if (!in_array($trust_level, $valid_levels, true)) {
            return false;
        }

        return $wpdb->update(
            $this->peers_table,
            ['trust_level' => $trust_level],
            ['peer_id' => $peer_id]
        ) !== false;
    }

    /**
     * Verifica y actualiza la clave pública de un peer
     *
     * @param string $peer_id
     * @param string $public_key_base64
     * @return bool True si la clave coincide o se actualizó
     */
    public function verify_peer_key($peer_id, $public_key_base64) {
        global $wpdb;

        // Verificar que el peer_id corresponde a la clave
        $public_key = base64_decode($public_key_base64);
        $computed_peer_id = hash('sha256', $public_key);

        if ($computed_peer_id !== $peer_id) {
            return false;
        }

        // Obtener peer existente
        $peer = $this->get_peer($peer_id);
        if (!$peer) {
            return false;
        }

        // Si no tiene clave, actualizarla
        if (empty($peer->public_key_ed25519)) {
            $wpdb->update(
                $this->peers_table,
                [
                    'public_key_ed25519' => $public_key_base64,
                    'trust_level'        => 'verified',
                ],
                ['peer_id' => $peer_id]
            );
            return true;
        }

        // Si tiene clave, verificar que coincide
        if ($peer->public_key_ed25519 === $public_key_base64) {
            // Actualizar a verified si no lo está
            if ($peer->trust_level === 'seen') {
                $this->update_trust_level($peer_id, 'verified');
            }
            return true;
        }

        // Clave no coincide - posible ataque
        error_log("[PeerDiscovery] Key mismatch for peer {$peer_id}");
        return false;
    }

    /**
     * Obtiene estadísticas de descubrimiento
     *
     * @return array
     */
    public function get_stats() {
        global $wpdb;

        return [
            'total_peers'        => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->peers_table}"),
            'by_trust_level'     => $wpdb->get_results(
                "SELECT trust_level, COUNT(*) as count FROM {$this->peers_table} GROUP BY trust_level",
                OBJECT_K
            ),
            'bootstrap_nodes'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->bootstrap_table} WHERE is_enabled = 1"),
            'recently_seen'      => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->peers_table} WHERE last_seen > %s",
                date('Y-m-d H:i:s', strtotime('-1 hour'))
            )),
        ];
    }
}
