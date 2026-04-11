<?php
/**
 * Sistema de Caché para el Mesh P2P
 *
 * Implementa caché en memoria y transients para reducir
 * consultas a la base de datos.
 *
 * @package FlavorPlatform\Network\Mesh
 * @since 1.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Mesh_Cache {

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Caché en memoria (por request)
     *
     * @var array
     */
    private $memory_cache = [];

    /**
     * Prefijo para transients
     */
    const TRANSIENT_PREFIX = 'fmesh_';

    /**
     * TTL por defecto para transients (5 minutos)
     */
    const DEFAULT_TTL = 300;

    /**
     * TTL corto para datos volátiles (1 minuto)
     */
    const SHORT_TTL = 60;

    /**
     * TTL largo para datos estables (1 hora)
     */
    const LONG_TTL = 3600;

    /**
     * Constructor privado
     */
    private function __construct() {
        // Limpiar caché al actualizar datos críticos
        add_action('flavor_mesh_peer_updated', [$this, 'invalidate_peer_cache']);
        add_action('flavor_mesh_connection_changed', [$this, 'invalidate_topology_cache']);
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

    // ═══════════════════════════════════════════════════════════════════
    // MÉTODOS DE CACHÉ GENERAL
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Obtiene un valor del caché
     *
     * @param string $key     Clave del caché
     * @param mixed  $default Valor por defecto si no existe
     * @return mixed
     */
    public function get($key, $default = null) {
        // Primero buscar en memoria (más rápido)
        if (isset($this->memory_cache[$key])) {
            return $this->memory_cache[$key];
        }

        // Luego buscar en transients
        $value = get_transient(self::TRANSIENT_PREFIX . $key);

        if ($value !== false) {
            // Guardar en memoria para próximas consultas en este request
            $this->memory_cache[$key] = $value;
            return $value;
        }

        return $default;
    }

    /**
     * Guarda un valor en el caché
     *
     * @param string $key   Clave del caché
     * @param mixed  $value Valor a guardar
     * @param int    $ttl   Tiempo de vida en segundos
     * @return bool
     */
    public function set($key, $value, $ttl = self::DEFAULT_TTL) {
        // Guardar en memoria
        $this->memory_cache[$key] = $value;

        // Guardar en transient
        return set_transient(self::TRANSIENT_PREFIX . $key, $value, $ttl);
    }

    /**
     * Elimina un valor del caché
     *
     * @param string $key Clave del caché
     * @return bool
     */
    public function delete($key) {
        unset($this->memory_cache[$key]);
        return delete_transient(self::TRANSIENT_PREFIX . $key);
    }

    /**
     * Obtiene o calcula un valor (cache-aside pattern)
     *
     * @param string   $key      Clave del caché
     * @param callable $callback Función que calcula el valor
     * @param int      $ttl      Tiempo de vida
     * @return mixed
     */
    public function remember($key, callable $callback, $ttl = self::DEFAULT_TTL) {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    // ═══════════════════════════════════════════════════════════════════
    // CACHÉ ESPECÍFICO DE PEERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Obtiene el peer local (caché largo)
     *
     * @return object|null
     */
    public function get_local_peer() {
        return $this->remember('local_peer', function() {
            global $wpdb;
            $table = $wpdb->prefix . 'flavor_network_peers';

            return $wpdb->get_row(
                "SELECT peer_id, public_key_ed25519, private_key_encrypted,
                        display_name, site_url, capabilities
                 FROM {$table}
                 WHERE is_local_peer = 1
                 LIMIT 1"
            );
        }, self::LONG_TTL);
    }

    /**
     * Obtiene peers online (caché corto)
     *
     * @param int $limit Máximo de peers
     * @return array
     */
    public function get_online_peers($limit = 50) {
        $cache_key = "online_peers_{$limit}";

        return $this->remember($cache_key, function() use ($limit) {
            global $wpdb;
            $table = $wpdb->prefix . 'flavor_network_peers';

            return $wpdb->get_results($wpdb->prepare(
                "SELECT peer_id, site_url, display_name, reputacion_score, trust_level
                 FROM {$table}
                 WHERE is_online = 1
                   AND is_local_peer = 0
                 ORDER BY reputacion_score DESC, last_seen DESC
                 LIMIT %d",
                $limit
            ));
        }, self::SHORT_TTL);
    }

    /**
     * Obtiene un peer por ID (caché medio)
     *
     * @param string $peer_id ID del peer
     * @return object|null
     */
    public function get_peer($peer_id) {
        $cache_key = "peer_{$peer_id}";

        return $this->remember($cache_key, function() use ($peer_id) {
            global $wpdb;
            $table = $wpdb->prefix . 'flavor_network_peers';

            return $wpdb->get_row($wpdb->prepare(
                "SELECT peer_id, public_key_ed25519, site_url, display_name,
                        reputacion_score, trust_level, is_online, last_seen
                 FROM {$table}
                 WHERE peer_id = %s",
                $peer_id
            ));
        }, self::DEFAULT_TTL);
    }

    /**
     * Obtiene la clave pública de un peer (caché largo)
     *
     * @param string $peer_id ID del peer
     * @return string|null
     */
    public function get_peer_public_key($peer_id) {
        $cache_key = "peer_pubkey_{$peer_id}";

        return $this->remember($cache_key, function() use ($peer_id) {
            global $wpdb;
            $table = $wpdb->prefix . 'flavor_network_peers';

            return $wpdb->get_var($wpdb->prepare(
                "SELECT public_key_ed25519 FROM {$table} WHERE peer_id = %s",
                $peer_id
            ));
        }, self::LONG_TTL);
    }

    /**
     * Invalida caché de un peer específico
     *
     * @param string $peer_id ID del peer
     */
    public function invalidate_peer_cache($peer_id = null) {
        if ($peer_id) {
            $this->delete("peer_{$peer_id}");
            $this->delete("peer_pubkey_{$peer_id}");
        }

        // Siempre invalidar listas
        $this->delete('local_peer');
        $this->delete('online_peers_50');
        $this->delete('online_peers_20');
        $this->delete('online_peers_10');
    }

    // ═══════════════════════════════════════════════════════════════════
    // CACHÉ DE TOPOLOGÍA
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Obtiene estadísticas de topología (caché medio)
     *
     * @return array
     */
    public function get_topology_stats() {
        return $this->remember('topology_stats', function() {
            global $wpdb;
            $peers_table = $wpdb->prefix . 'flavor_network_peers';
            $conn_table = $wpdb->prefix . 'flavor_network_mesh_connections';

            return [
                'total_peers'       => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$peers_table}"),
                'online_peers'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$peers_table} WHERE is_online = 1"),
                'total_connections' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$conn_table} WHERE state = 'active'"),
            ];
        }, self::DEFAULT_TTL);
    }

    /**
     * Obtiene conexiones de un peer (caché corto)
     *
     * @param string $peer_id ID del peer
     * @return array
     */
    public function get_peer_connections($peer_id) {
        $cache_key = "peer_conn_{$peer_id}";

        return $this->remember($cache_key, function() use ($peer_id) {
            global $wpdb;
            $table = $wpdb->prefix . 'flavor_network_mesh_connections';

            return $wpdb->get_results($wpdb->prepare(
                "SELECT peer_a_id, peer_b_id, connection_type, state
                 FROM {$table}
                 WHERE (peer_a_id = %s OR peer_b_id = %s)
                   AND state = 'active'",
                $peer_id,
                $peer_id
            ));
        }, self::SHORT_TTL);
    }

    /**
     * Invalida caché de topología
     */
    public function invalidate_topology_cache() {
        $this->delete('topology_stats');

        // Invalidar conexiones de todos los peers en memoria
        foreach (array_keys($this->memory_cache) as $key) {
            if (strpos($key, 'peer_conn_') === 0) {
                unset($this->memory_cache[$key]);
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // CACHÉ DE GOSSIP
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Verifica si un mensaje ya existe (muy frecuente, usar memoria)
     *
     * @param string $message_id ID del mensaje
     * @return bool
     */
    public function message_exists($message_id) {
        $cache_key = "msg_exists_{$message_id}";

        // Solo memoria para esta consulta (muy frecuente)
        if (isset($this->memory_cache[$cache_key])) {
            return $this->memory_cache[$cache_key];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'flavor_network_gossip_messages';

        $exists = (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT 1 FROM {$table} WHERE message_id = %s LIMIT 1",
            $message_id
        ));

        $this->memory_cache[$cache_key] = $exists;

        return $exists;
    }

    /**
     * Marca un mensaje como existente en caché
     *
     * @param string $message_id ID del mensaje
     */
    public function mark_message_exists($message_id) {
        $this->memory_cache["msg_exists_{$message_id}"] = true;
    }

    /**
     * Obtiene estadísticas de gossip (caché corto)
     *
     * @return array
     */
    public function get_gossip_stats() {
        return $this->remember('gossip_stats', function() {
            global $wpdb;
            $table = $wpdb->prefix . 'flavor_network_gossip_messages';

            return [
                'total_messages'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"),
                'pending_forward' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE forwarded = 0 AND ttl > 0"),
                'processed'       => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE processed = 1"),
            ];
        }, self::SHORT_TTL);
    }

    // ═══════════════════════════════════════════════════════════════════
    // UTILIDADES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Limpia toda la caché del mesh
     */
    public function flush_all() {
        global $wpdb;

        // Limpiar memoria
        $this->memory_cache = [];

        // Limpiar transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_" . self::TRANSIENT_PREFIX . "%'
                OR option_name LIKE '_transient_timeout_" . self::TRANSIENT_PREFIX . "%'"
        );
    }

    /**
     * Obtiene estadísticas de caché (para debug)
     *
     * @return array
     */
    public function get_cache_stats() {
        global $wpdb;

        $transient_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_" . self::TRANSIENT_PREFIX . "%'
               AND option_name NOT LIKE '_transient_timeout_%'"
        );

        return [
            'memory_items'    => count($this->memory_cache),
            'transient_items' => (int) $transient_count,
            'memory_keys'     => array_keys($this->memory_cache),
        ];
    }
}

/**
 * Helper function para acceder al caché
 *
 * @return Flavor_Mesh_Cache
 */
function flavor_mesh_cache() {
    return Flavor_Mesh_Cache::instance();
}
