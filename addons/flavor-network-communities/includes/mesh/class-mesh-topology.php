<?php
/**
 * Mesh Topology Manager
 *
 * Gestiona la topología de red mesh sin restricción de ciclos.
 * Permite conexiones bidireccionales entre cualquier par de peers.
 *
 * @package FlavorPlatform\Network\Mesh
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Mesh_Topology {

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Máximo de conexiones por peer
     */
    const MAX_CONNECTIONS_PER_PEER = 20;

    /**
     * Tabla de conexiones mesh
     *
     * @var string
     */
    private $connections_table;

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
        $this->connections_table = $wpdb->prefix . 'flavor_network_mesh_connections';
        $this->peers_table = $wpdb->prefix . 'flavor_network_peers';
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
     * Verifica si dos peers pueden conectarse
     *
     * A diferencia del modelo DAG anterior, NO hay restricción de ciclos.
     * Solo verificamos límites de conexiones.
     *
     * @param string $peer_a_id
     * @param string $peer_b_id
     * @return array ['can_connect' => bool, 'reason' => string]
     */
    public function can_connect($peer_a_id, $peer_b_id) {
        // No conectar consigo mismo
        if ($peer_a_id === $peer_b_id) {
            return [
                'can_connect' => false,
                'reason'      => 'Cannot connect to self',
            ];
        }

        // Verificar que no exista ya la conexión
        if ($this->connection_exists($peer_a_id, $peer_b_id)) {
            return [
                'can_connect' => false,
                'reason'      => 'Connection already exists',
            ];
        }

        // Verificar límite de conexiones de peer A
        $count_a = $this->get_connection_count($peer_a_id);
        if ($count_a >= self::MAX_CONNECTIONS_PER_PEER) {
            return [
                'can_connect' => false,
                'reason'      => "Peer A has reached max connections ({$count_a}/" . self::MAX_CONNECTIONS_PER_PEER . ")",
            ];
        }

        // Verificar límite de conexiones de peer B
        $count_b = $this->get_connection_count($peer_b_id);
        if ($count_b >= self::MAX_CONNECTIONS_PER_PEER) {
            return [
                'can_connect' => false,
                'reason'      => "Peer B has reached max connections ({$count_b}/" . self::MAX_CONNECTIONS_PER_PEER . ")",
            ];
        }

        // Verificar que ambos peers existen
        if (!$this->peer_exists($peer_a_id)) {
            return [
                'can_connect' => false,
                'reason'      => 'Peer A does not exist',
            ];
        }

        if (!$this->peer_exists($peer_b_id)) {
            return [
                'can_connect' => false,
                'reason'      => 'Peer B does not exist',
            ];
        }

        return [
            'can_connect' => true,
            'reason'      => 'OK',
        ];
    }

    /**
     * Crea una conexión entre dos peers
     *
     * @param string $peer_a_id
     * @param string $peer_b_id
     * @param string $type direct, relay, bootstrap
     * @return int|false ID de la conexión o false si falla
     */
    public function create_connection($peer_a_id, $peer_b_id, $type = 'direct') {
        // Normalizar orden (peer_a siempre es el menor alfabéticamente)
        if ($peer_a_id > $peer_b_id) {
            $temp = $peer_a_id;
            $peer_a_id = $peer_b_id;
            $peer_b_id = $temp;
        }

        $check = $this->can_connect($peer_a_id, $peer_b_id);
        if (!$check['can_connect']) {
            error_log("[MeshTopology] Cannot connect: {$check['reason']}");
            return false;
        }

        global $wpdb;
        $result = $wpdb->insert($this->connections_table, [
            'peer_a_id'       => $peer_a_id,
            'peer_b_id'       => $peer_b_id,
            'connection_type' => $type,
            'state'           => 'pending',
            'established_at'  => current_time('mysql'),
        ]);

        if ($result === false) {
            error_log("[MeshTopology] Error creating connection: " . $wpdb->last_error);
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Activa una conexión (completa el handshake)
     *
     * @param int $connection_id
     * @param string|null $shared_secret_hash Hash del secreto compartido
     * @return bool
     */
    public function activate_connection($connection_id, $shared_secret_hash = null) {
        global $wpdb;

        $data = [
            'state'               => 'active',
            'handshake_completed' => 1,
            'last_activity'       => current_time('mysql'),
        ];

        if ($shared_secret_hash) {
            $data['shared_secret_hash'] = $shared_secret_hash;
        }

        return $wpdb->update(
            $this->connections_table,
            $data,
            ['id' => $connection_id]
        ) !== false;
    }

    /**
     * Cierra una conexión
     *
     * @param string $peer_a_id
     * @param string $peer_b_id
     * @return bool
     */
    public function close_connection($peer_a_id, $peer_b_id) {
        global $wpdb;

        // Normalizar orden
        if ($peer_a_id > $peer_b_id) {
            $temp = $peer_a_id;
            $peer_a_id = $peer_b_id;
            $peer_b_id = $temp;
        }

        return $wpdb->update(
            $this->connections_table,
            ['state' => 'closed'],
            ['peer_a_id' => $peer_a_id, 'peer_b_id' => $peer_b_id]
        ) !== false;
    }

    /**
     * Verifica si existe una conexión entre dos peers
     *
     * @param string $peer_a_id
     * @param string $peer_b_id
     * @return bool
     */
    public function connection_exists($peer_a_id, $peer_b_id) {
        global $wpdb;

        // Normalizar orden
        if ($peer_a_id > $peer_b_id) {
            $temp = $peer_a_id;
            $peer_a_id = $peer_b_id;
            $peer_b_id = $temp;
        }

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->connections_table}
             WHERE peer_a_id = %s AND peer_b_id = %s AND state != 'closed'",
            $peer_a_id,
            $peer_b_id
        ));

        return $exists > 0;
    }

    /**
     * Obtiene el número de conexiones activas de un peer
     *
     * @param string $peer_id
     * @return int
     */
    public function get_connection_count($peer_id) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->connections_table}
             WHERE (peer_a_id = %s OR peer_b_id = %s) AND state = 'active'",
            $peer_id,
            $peer_id
        ));
    }

    /**
     * Obtiene todas las conexiones de un peer
     *
     * @param string $peer_id
     * @param string $state Filtrar por estado (null = todos)
     * @return array
     */
    public function get_peer_connections($peer_id, $state = 'active') {
        global $wpdb;

        $sql = "SELECT * FROM {$this->connections_table}
                WHERE (peer_a_id = %s OR peer_b_id = %s)";

        if ($state) {
            $sql .= $wpdb->prepare(" AND state = %s", $state);
        }

        return $wpdb->get_results($wpdb->prepare($sql, $peer_id, $peer_id));
    }

    /**
     * Obtiene los peers conectados a un peer dado
     *
     * @param string $peer_id
     * @return array Lista de peer_ids
     */
    public function get_connected_peers($peer_id) {
        global $wpdb;

        $connections = $wpdb->get_results($wpdb->prepare(
            "SELECT peer_a_id, peer_b_id FROM {$this->connections_table}
             WHERE (peer_a_id = %s OR peer_b_id = %s) AND state = 'active'",
            $peer_id,
            $peer_id
        ));

        $connected = [];
        foreach ($connections as $conn) {
            if ($conn->peer_a_id === $peer_id) {
                $connected[] = $conn->peer_b_id;
            } else {
                $connected[] = $conn->peer_a_id;
            }
        }

        return $connected;
    }

    /**
     * Encuentra el camino más corto entre dos peers (BFS)
     *
     * @param string $from_peer_id
     * @param string $to_peer_id
     * @return array|null Array de peer_ids o null si no hay camino
     */
    public function find_shortest_path($from_peer_id, $to_peer_id) {
        if ($from_peer_id === $to_peer_id) {
            return [$from_peer_id];
        }

        // BFS
        $queue = [[$from_peer_id]];
        $visited = [$from_peer_id => true];

        while (!empty($queue)) {
            $path = array_shift($queue);
            $current = end($path);

            $neighbors = $this->get_connected_peers($current);

            foreach ($neighbors as $neighbor) {
                if (isset($visited[$neighbor])) {
                    continue;
                }

                $new_path = array_merge($path, [$neighbor]);

                if ($neighbor === $to_peer_id) {
                    return $new_path;
                }

                $visited[$neighbor] = true;
                $queue[] = $new_path;
            }
        }

        return null; // No hay camino
    }

    /**
     * Obtiene los peers más cercanos (por número de saltos)
     *
     * @param string $peer_id
     * @param int $max_hops Máximo número de saltos
     * @param int $limit Máximo de resultados
     * @return array peer_id => hops
     */
    public function get_nearest_peers($peer_id, $max_hops = 3, $limit = 20) {
        $nearest = [];
        $queue = [[$peer_id, 0]]; // [peer_id, hops]
        $visited = [$peer_id => true];

        while (!empty($queue) && count($nearest) < $limit) {
            list($current, $hops) = array_shift($queue);

            if ($hops > 0) {
                $nearest[$current] = $hops;
            }

            if ($hops >= $max_hops) {
                continue;
            }

            $neighbors = $this->get_connected_peers($current);
            foreach ($neighbors as $neighbor) {
                if (isset($visited[$neighbor])) {
                    continue;
                }
                $visited[$neighbor] = true;
                $queue[] = [$neighbor, $hops + 1];
            }
        }

        return $nearest;
    }

    /**
     * Verifica si un peer existe
     *
     * @param string $peer_id
     * @return bool
     */
    private function peer_exists($peer_id) {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->peers_table} WHERE peer_id = %s",
            $peer_id
        ));
    }

    /**
     * Actualiza la actividad de una conexión
     *
     * @param string $peer_a_id
     * @param string $peer_b_id
     */
    public function update_connection_activity($peer_a_id, $peer_b_id) {
        global $wpdb;

        if ($peer_a_id > $peer_b_id) {
            $temp = $peer_a_id;
            $peer_a_id = $peer_b_id;
            $peer_b_id = $temp;
        }

        $wpdb->update(
            $this->connections_table,
            [
                'last_activity'       => current_time('mysql'),
                'messages_exchanged'  => $wpdb->prepare('messages_exchanged + 1'),
            ],
            ['peer_a_id' => $peer_a_id, 'peer_b_id' => $peer_b_id]
        );
    }

    /**
     * Obtiene estadísticas de la topología
     *
     * @return array
     */
    public function get_topology_stats() {
        global $wpdb;

        $total_connections = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->connections_table} WHERE state = 'active'"
        );

        $total_peers = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->peers_table}"
        );

        $online_peers = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->peers_table} WHERE is_online = 1"
        );

        // Grado promedio (conexiones por peer)
        $avg_degree = $total_peers > 0 ? ($total_connections * 2) / $total_peers : 0;

        // Peers aislados (sin conexiones)
        $isolated = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->peers_table} p
             WHERE NOT EXISTS (
                 SELECT 1 FROM {$this->connections_table} c
                 WHERE (c.peer_a_id = p.peer_id OR c.peer_b_id = p.peer_id)
                   AND c.state = 'active'
             )"
        );

        return [
            'total_peers'       => $total_peers,
            'online_peers'      => $online_peers,
            'total_connections' => $total_connections,
            'average_degree'    => round($avg_degree, 2),
            'isolated_peers'    => $isolated,
            'connectivity'      => $total_peers > 0 ? round(($total_peers - $isolated) / $total_peers * 100, 1) : 0,
        ];
    }

    /**
     * Exporta la topología como grafo (para visualización)
     *
     * @return array ['nodes' => [...], 'edges' => [...]]
     */
    public function export_graph() {
        global $wpdb;

        // Nodos
        $peers = $wpdb->get_results(
            "SELECT peer_id, display_name, is_online, is_local_peer, trust_level, reputacion_score
             FROM {$this->peers_table}"
        );

        $nodes = [];
        foreach ($peers as $peer) {
            $nodes[] = [
                'id'       => $peer->peer_id,
                'label'    => $peer->display_name ?: substr($peer->peer_id, 0, 8),
                'online'   => (bool) $peer->is_online,
                'local'    => (bool) $peer->is_local_peer,
                'trust'    => $peer->trust_level,
                'score'    => (float) $peer->reputacion_score,
            ];
        }

        // Aristas
        $connections = $wpdb->get_results(
            "SELECT peer_a_id, peer_b_id, connection_type, state
             FROM {$this->connections_table}
             WHERE state = 'active'"
        );

        $edges = [];
        foreach ($connections as $conn) {
            $edges[] = [
                'from' => $conn->peer_a_id,
                'to'   => $conn->peer_b_id,
                'type' => $conn->connection_type,
            ];
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }
}
