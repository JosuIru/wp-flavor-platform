<?php
/**
 * Modelo de Peer para red mesh P2P
 *
 * Representa un peer en la red mesh con identidad criptográfica.
 * Complementa/reemplaza Flavor_Network_Node para el nuevo modelo.
 *
 * @package FlavorPlatform\Network\Mesh
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Network_Peer {

    /**
     * Nombre de la tabla
     *
     * @var string
     */
    private static $table_name;

    /**
     * Datos del peer
     *
     * @var array
     */
    private $data = [];

    /**
     * Niveles de confianza
     */
    const TRUST_LEVELS = ['unknown', 'seen', 'verified', 'trusted'];

    /**
     * Capacidades disponibles
     */
    const CAPABILITIES = [
        'gossip'    => 'Puede participar en gossip protocol',
        'crdt'      => 'Soporta sincronización CRDT',
        'relay'     => 'Puede actuar como relay para otros peers',
        'bootstrap' => 'Es un nodo bootstrap de la red',
    ];

    /**
     * Constructor
     *
     * @param array|object $data Datos del peer
     */
    public function __construct($data = []) {
        if (is_object($data)) {
            $data = (array) $data;
        }
        $this->data = $data;
        self::init_table_name();
    }

    /**
     * Inicializa el nombre de la tabla
     */
    private static function init_table_name() {
        if (empty(self::$table_name)) {
            global $wpdb;
            self::$table_name = $wpdb->prefix . 'flavor_network_peers';
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // STATIC FACTORY METHODS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Busca un peer por ID
     *
     * @param int $id ID de BD
     * @return self|null
     */
    public static function find($id) {
        global $wpdb;
        self::init_table_name();

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$table_name . " WHERE id = %d",
            $id
        ));

        return $row ? new self($row) : null;
    }

    /**
     * Busca un peer por peer_id (hash criptográfico)
     *
     * @param string $peer_id
     * @return self|null
     */
    public static function find_by_peer_id($peer_id) {
        global $wpdb;
        self::init_table_name();

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$table_name . " WHERE peer_id = %s",
            $peer_id
        ));

        return $row ? new self($row) : null;
    }

    /**
     * Obtiene el peer local
     *
     * @return self|null
     */
    public static function get_local() {
        global $wpdb;
        self::init_table_name();

        // Cache
        static $local_peer = null;
        if ($local_peer !== null) {
            return $local_peer;
        }

        $row = $wpdb->get_row(
            "SELECT * FROM " . self::$table_name . " WHERE is_local_peer = 1 LIMIT 1"
        );

        if ($row) {
            $local_peer = new self($row);
        }

        return $local_peer;
    }

    /**
     * Crea un nuevo peer
     *
     * @param array $data Datos del peer
     * @return self|false
     */
    public static function create(array $data) {
        global $wpdb;
        self::init_table_name();

        // Validar peer_id requerido
        if (empty($data['peer_id'])) {
            return false;
        }

        // Verificar que no exista
        $existing = self::find_by_peer_id($data['peer_id']);
        if ($existing) {
            return false;
        }

        $defaults = [
            'public_key_ed25519'  => '',
            'display_name'        => '',
            'site_url'            => '',
            'capabilities'        => '{}',
            'reputacion_score'    => 0,
            'trust_level'         => 'unknown',
            'vector_clock_version' => 0,
            'is_local_peer'       => 0,
            'is_bootstrap_node'   => 0,
            'is_online'           => 0,
            'connection_failures' => 0,
            'metadata'            => '{}',
        ];

        $data = wp_parse_args($data, $defaults);

        // Serializar arrays/objects
        if (is_array($data['capabilities'])) {
            $data['capabilities'] = wp_json_encode($data['capabilities']);
        }
        if (is_array($data['metadata'])) {
            $data['metadata'] = wp_json_encode($data['metadata']);
        }

        $result = $wpdb->insert(self::$table_name, $data);

        if ($result === false) {
            return false;
        }

        $data['id'] = $wpdb->insert_id;
        return new self($data);
    }

    /**
     * Busca peers con filtros
     *
     * @param array $filters Filtros
     * @param int $limit Límite
     * @param int $offset Offset
     * @return array
     */
    public static function query(array $filters = [], $limit = 50, $offset = 0) {
        global $wpdb;
        self::init_table_name();

        $where = ['1=1'];
        $values = [];

        if (isset($filters['is_online'])) {
            $where[] = 'is_online = %d';
            $values[] = (int) $filters['is_online'];
        }

        if (isset($filters['trust_level'])) {
            if (is_array($filters['trust_level'])) {
                $placeholders = implode(',', array_fill(0, count($filters['trust_level']), '%s'));
                $where[] = "trust_level IN ({$placeholders})";
                $values = array_merge($values, $filters['trust_level']);
            } else {
                $where[] = 'trust_level = %s';
                $values[] = $filters['trust_level'];
            }
        }

        if (isset($filters['is_local_peer'])) {
            $where[] = 'is_local_peer = %d';
            $values[] = (int) $filters['is_local_peer'];
        }

        if (isset($filters['min_reputation'])) {
            $where[] = 'reputacion_score >= %f';
            $values[] = (float) $filters['min_reputation'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(display_name LIKE %s OR site_url LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where);
        $order_by = $filters['order_by'] ?? 'last_seen';
        $order_dir = strtoupper($filters['order_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM " . self::$table_name . "
                WHERE {$where_clause}
                ORDER BY {$order_by} {$order_dir}
                LIMIT %d OFFSET %d";

        $values[] = $limit;
        $values[] = $offset;

        $rows = $wpdb->get_results($wpdb->prepare($sql, $values));

        return array_map(function ($row) {
            return new self($row);
        }, $rows);
    }

    /**
     * Cuenta peers con filtros
     *
     * @param array $filters
     * @return int
     */
    public static function count(array $filters = []) {
        global $wpdb;
        self::init_table_name();

        $where = ['1=1'];
        $values = [];

        if (isset($filters['is_online'])) {
            $where[] = 'is_online = %d';
            $values[] = (int) $filters['is_online'];
        }

        if (isset($filters['trust_level'])) {
            $where[] = 'trust_level = %s';
            $values[] = $filters['trust_level'];
        }

        $where_clause = implode(' AND ', $where);

        if (empty($values)) {
            return (int) $wpdb->get_var("SELECT COUNT(*) FROM " . self::$table_name . " WHERE {$where_clause}");
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::$table_name . " WHERE {$where_clause}",
            $values
        ));
    }

    // ═══════════════════════════════════════════════════════════════════
    // INSTANCE METHODS - GETTERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Obtiene un campo
     *
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        return $this->data[$key] ?? null;
    }

    /**
     * Magic getter
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->get($name);
    }

    /**
     * Obtiene el ID de BD
     *
     * @return int|null
     */
    public function get_id() {
        return isset($this->data['id']) ? (int) $this->data['id'] : null;
    }

    /**
     * Obtiene el peer_id (hash criptográfico)
     *
     * @return string
     */
    public function get_peer_id() {
        return $this->data['peer_id'] ?? '';
    }

    /**
     * Obtiene la clave pública Ed25519
     *
     * @return string Base64
     */
    public function get_public_key() {
        return $this->data['public_key_ed25519'] ?? '';
    }

    /**
     * Obtiene el nombre para mostrar
     *
     * @return string
     */
    public function get_display_name() {
        return $this->data['display_name'] ?? substr($this->get_peer_id(), 0, 8);
    }

    /**
     * Obtiene la URL del sitio
     *
     * @return string
     */
    public function get_site_url() {
        return $this->data['site_url'] ?? '';
    }

    /**
     * Obtiene las capacidades
     *
     * @return array
     */
    public function get_capabilities() {
        $caps = $this->data['capabilities'] ?? '{}';
        if (is_string($caps)) {
            $caps = json_decode($caps, true) ?: [];
        }
        return $caps;
    }

    /**
     * Verifica si tiene una capacidad
     *
     * @param string $capability
     * @return bool
     */
    public function has_capability($capability) {
        $caps = $this->get_capabilities();
        return !empty($caps[$capability]);
    }

    /**
     * Obtiene el nivel de confianza
     *
     * @return string
     */
    public function get_trust_level() {
        return $this->data['trust_level'] ?? 'unknown';
    }

    /**
     * Obtiene el score de reputación
     *
     * @return float
     */
    public function get_reputation() {
        return (float) ($this->data['reputacion_score'] ?? 0);
    }

    /**
     * Verifica si es el peer local
     *
     * @return bool
     */
    public function is_local() {
        return (bool) ($this->data['is_local_peer'] ?? false);
    }

    /**
     * Verifica si es un nodo bootstrap
     *
     * @return bool
     */
    public function is_bootstrap() {
        return (bool) ($this->data['is_bootstrap_node'] ?? false);
    }

    /**
     * Verifica si está online
     *
     * @return bool
     */
    public function is_online() {
        return (bool) ($this->data['is_online'] ?? false);
    }

    /**
     * Obtiene la fecha de última vez visto
     *
     * @return string|null
     */
    public function get_last_seen() {
        return $this->data['last_seen'] ?? null;
    }

    /**
     * Obtiene metadata adicional
     *
     * @param string|null $key Clave específica o null para todo
     * @return mixed
     */
    public function get_metadata($key = null) {
        $metadata = $this->data['metadata'] ?? '{}';
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        if ($key !== null) {
            return $metadata[$key] ?? null;
        }

        return $metadata;
    }

    // ═══════════════════════════════════════════════════════════════════
    // INSTANCE METHODS - SETTERS/UPDATE
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Actualiza campos del peer
     *
     * @param array $data Campos a actualizar
     * @return bool
     */
    public function update(array $data) {
        global $wpdb;

        if (!$this->get_id()) {
            return false;
        }

        // Serializar arrays
        if (isset($data['capabilities']) && is_array($data['capabilities'])) {
            $data['capabilities'] = wp_json_encode($data['capabilities']);
        }
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $data['metadata'] = wp_json_encode($data['metadata']);
        }

        $result = $wpdb->update(
            self::$table_name,
            $data,
            ['id' => $this->get_id()]
        );

        if ($result !== false) {
            // Actualizar datos locales
            $this->data = array_merge($this->data, $data);
            return true;
        }

        return false;
    }

    /**
     * Actualiza el nivel de confianza
     *
     * @param string $level
     * @return bool
     */
    public function set_trust_level($level) {
        if (!in_array($level, self::TRUST_LEVELS, true)) {
            return false;
        }
        return $this->update(['trust_level' => $level]);
    }

    /**
     * Incrementa la reputación
     *
     * @param float $amount
     * @return bool
     */
    public function increment_reputation($amount = 1.0) {
        $new_score = min(100, $this->get_reputation() + $amount);
        return $this->update(['reputacion_score' => $new_score]);
    }

    /**
     * Decrementa la reputación
     *
     * @param float $amount
     * @return bool
     */
    public function decrement_reputation($amount = 1.0) {
        $new_score = max(0, $this->get_reputation() - $amount);
        return $this->update(['reputacion_score' => $new_score]);
    }

    /**
     * Marca como online
     *
     * @return bool
     */
    public function mark_online() {
        return $this->update([
            'is_online'           => 1,
            'last_seen'           => current_time('mysql'),
            'connection_failures' => 0,
        ]);
    }

    /**
     * Marca como offline
     *
     * @return bool
     */
    public function mark_offline() {
        return $this->update(['is_online' => 0]);
    }

    /**
     * Registra un fallo de conexión
     *
     * @return bool
     */
    public function record_failure() {
        global $wpdb;

        $failures = (int) ($this->data['connection_failures'] ?? 0) + 1;

        $data = ['connection_failures' => $failures];

        // Marcar offline si hay muchos fallos
        if ($failures >= 5) {
            $data['is_online'] = 0;
        }

        return $this->update($data);
    }

    /**
     * Añade metadata
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function set_metadata($key, $value) {
        $metadata = $this->get_metadata();
        $metadata[$key] = $value;
        return $this->update(['metadata' => $metadata]);
    }

    /**
     * Elimina el peer
     *
     * @return bool
     */
    public function delete() {
        global $wpdb;

        if (!$this->get_id() || $this->is_local()) {
            return false; // No eliminar peer local
        }

        return $wpdb->delete(
            self::$table_name,
            ['id' => $this->get_id()]
        ) !== false;
    }

    // ═══════════════════════════════════════════════════════════════════
    // CRYPTO METHODS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Verifica una firma de este peer
     *
     * @param string $message Mensaje firmado
     * @param string $signature Firma en base64
     * @return bool
     */
    public function verify_signature($message, $signature) {
        $public_key_base64 = $this->get_public_key();
        if (empty($public_key_base64)) {
            return false;
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
     * Firma un mensaje (solo para peer local)
     *
     * @param string $message
     * @return string|false Firma en base64 o false
     */
    public function sign($message) {
        if (!$this->is_local()) {
            return false;
        }

        $private_key_encrypted = $this->data['private_key_encrypted'] ?? '';
        if (empty($private_key_encrypted)) {
            return false;
        }

        $private_key = Flavor_Network_Installer::decrypt_private_key($private_key_encrypted);
        if (!$private_key) {
            return false;
        }

        try {
            $signature = sodium_crypto_sign_detached($message, $private_key);
            sodium_memzero($private_key);
            return base64_encode($signature);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Genera un fingerprint legible de la clave pública
     *
     * @return string
     */
    public function get_fingerprint() {
        $public_key = base64_decode($this->get_public_key());
        if (empty($public_key)) {
            return '';
        }

        $hash = hash('sha256', $public_key, true);
        $numbers = [];

        for ($i = 0; $i < 12; $i++) {
            $value = unpack('n', substr($hash, $i * 2, 2))[1];
            $numbers[] = str_pad($value % 100000, 5, '0', STR_PAD_LEFT);
        }

        return implode(' ', $numbers);
    }

    // ═══════════════════════════════════════════════════════════════════
    // SERIALIZATION
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Convierte a array público (sin datos sensibles)
     *
     * @return array
     */
    public function to_public_array() {
        return [
            'peer_id'       => $this->get_peer_id(),
            'display_name'  => $this->get_display_name(),
            'site_url'      => $this->get_site_url(),
            'public_key'    => $this->get_public_key(),
            'trust_level'   => $this->get_trust_level(),
            'reputation'    => $this->get_reputation(),
            'is_online'     => $this->is_online(),
            'is_bootstrap'  => $this->is_bootstrap(),
            'capabilities'  => $this->get_capabilities(),
            'last_seen'     => $this->get_last_seen(),
        ];
    }

    /**
     * Convierte a array completo (solo admin)
     *
     * @return array
     */
    public function to_array() {
        return $this->data;
    }

    /**
     * Convierte a array mínimo (para listas)
     *
     * @return array
     */
    public function to_card_array() {
        return [
            'peer_id'      => $this->get_peer_id(),
            'display_name' => $this->get_display_name(),
            'is_online'    => $this->is_online(),
            'trust_level'  => $this->get_trust_level(),
        ];
    }

    /**
     * Representación como string
     *
     * @return string
     */
    public function __toString() {
        $name = $this->get_display_name();
        $status = $this->is_online() ? 'online' : 'offline';
        return "Peer[{$name}]({$status})";
    }
}
