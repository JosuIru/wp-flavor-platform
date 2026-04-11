<?php
/**
 * Bridge entre sistema legacy (Nodes) y nuevo sistema (Mesh/Peers)
 *
 * Proporciona compatibilidad hacia atrás y sincronización automática
 * entre los dos modelos de datos durante la transición.
 *
 * @package FlavorPlatform\Network\Mesh
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Mesh_Node_Bridge {

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
     * Inicializa hooks de sincronización
     */
    private function init_hooks() {
        // Sincronizar cuando se crea/actualiza un nodo
        add_action('flavor_network_node_created', [$this, 'sync_node_to_peer'], 10, 2);
        add_action('flavor_network_node_updated', [$this, 'sync_node_to_peer'], 10, 2);

        // Sincronizar cuando se crea/actualiza un peer
        add_action('flavor_mesh_peer_created', [$this, 'sync_peer_to_node'], 10, 2);

        // Propagar contenido del sistema legacy via mesh
        add_action('flavor_network_content_created', [$this, 'propagate_content'], 10, 3);
        add_action('flavor_network_content_updated', [$this, 'propagate_content'], 10, 3);

        // Recibir contenido propagado por mesh
        add_action('flavor_mesh_content_received_shared_content', [$this, 'receive_propagated_content'], 10, 3);
        add_action('flavor_mesh_content_received_event', [$this, 'receive_propagated_event'], 10, 3);
        add_action('flavor_mesh_content_received_board', [$this, 'receive_propagated_board'], 10, 3);

        // Propagar alertas solidarias
        add_action('flavor_network_alert_created', [$this, 'propagate_alert'], 10, 2);

        // Recibir alertas
        add_action('flavor_mesh_alert_received', [$this, 'receive_alert'], 10, 4);

        // Sincronizar conexiones
        add_action('flavor_network_connection_approved', [$this, 'create_mesh_connection'], 10, 2);
    }

    // ═══════════════════════════════════════════════════════════════════
    // NODE <-> PEER SYNC
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Sincroniza un nodo al sistema de peers
     *
     * @param int $node_id ID del nodo
     * @param array $node_data Datos del nodo
     */
    public function sync_node_to_peer($node_id, $node_data = []) {
        global $wpdb;

        $nodes_table = $wpdb->prefix . 'flavor_network_nodes';
        $peers_table = $wpdb->prefix . 'flavor_network_peers';

        // Obtener datos del nodo si no se proporcionaron
        if (empty($node_data)) {
            $node_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$nodes_table} WHERE id = %d",
                $node_id
            ), ARRAY_A);
        }

        if (!$node_data) {
            return;
        }

        // Buscar peer existente vinculado a este nodo
        $existing_peer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$peers_table} WHERE node_id = %d",
            $node_id
        ));

        if ($existing_peer) {
            // Actualizar peer existente
            $wpdb->update($peers_table, [
                'display_name' => $node_data['nombre'],
                'site_url'     => $node_data['site_url'],
            ], ['node_id' => $node_id]);
        } else {
            // Crear nuevo peer para este nodo
            $peer_id = $this->generate_peer_id_for_node($node_data);

            $wpdb->insert($peers_table, [
                'peer_id'            => $peer_id,
                'node_id'            => $node_id,
                'public_key_ed25519' => '', // Sin clave, es nodo legacy
                'display_name'       => $node_data['nombre'],
                'site_url'           => $node_data['site_url'],
                'trust_level'        => 'seen',
                'is_local_peer'      => $node_data['es_nodo_local'] ?? 0,
                'metadata'           => wp_json_encode([
                    'synced_from_node' => true,
                    'node_id'          => $node_id,
                ]),
            ]);
        }
    }

    /**
     * Sincroniza un peer al sistema de nodos
     *
     * @param string $peer_id
     * @param array $peer_data
     */
    public function sync_peer_to_node($peer_id, $peer_data = []) {
        global $wpdb;

        $nodes_table = $wpdb->prefix . 'flavor_network_nodes';
        $peers_table = $wpdb->prefix . 'flavor_network_peers';

        // Obtener datos del peer si no se proporcionaron
        if (empty($peer_data)) {
            $peer_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$peers_table} WHERE peer_id = %s",
                $peer_id
            ), ARRAY_A);
        }

        if (!$peer_data) {
            return;
        }

        // Si ya tiene node_id, no crear nuevo nodo
        if (!empty($peer_data['node_id'])) {
            return;
        }

        // Verificar si existe nodo con misma URL
        $existing_node = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$nodes_table} WHERE site_url = %s",
            $peer_data['site_url']
        ));

        if ($existing_node) {
            // Vincular peer al nodo existente
            $wpdb->update($peers_table, [
                'node_id' => $existing_node,
            ], ['peer_id' => $peer_id]);
            return;
        }

        // Crear nuevo nodo para este peer
        $slug = sanitize_title($peer_data['display_name'] ?: substr($peer_id, 0, 16));

        // Asegurar slug único
        $base_slug = $slug;
        $counter = 1;
        while ($wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$nodes_table} WHERE slug = %s",
            $slug
        ))) {
            $slug = $base_slug . '-' . $counter++;
        }

        $result = $wpdb->insert($nodes_table, [
            'site_url'       => $peer_data['site_url'],
            'nombre'         => $peer_data['display_name'] ?: 'Peer ' . substr($peer_id, 0, 8),
            'slug'           => $slug,
            'es_nodo_local'  => 0,
            'estado'         => 'activo',
        ]);

        if ($result) {
            $node_id = $wpdb->insert_id;

            // Vincular peer al nodo
            $wpdb->update($peers_table, [
                'node_id' => $node_id,
            ], ['peer_id' => $peer_id]);
        }
    }

    /**
     * Genera un peer_id para un nodo existente
     *
     * @param array $node_data
     * @return string
     */
    private function generate_peer_id_for_node($node_data) {
        // Usar API key si existe, sino URL
        $identifier = !empty($node_data['api_key'])
            ? $node_data['api_key']
            : $node_data['site_url'];

        return hash('sha256', 'legacy_node_' . $identifier);
    }

    // ═══════════════════════════════════════════════════════════════════
    // CONTENT PROPAGATION
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Propaga contenido creado/actualizado via mesh
     *
     * @param int $content_id
     * @param string $content_type
     * @param array $content_data
     */
    public function propagate_content($content_id, $content_type, $content_data = []) {
        if (!class_exists('Flavor_Mesh_Loader')) {
            return;
        }

        $mesh = Flavor_Mesh_Loader::instance();
        if (!$mesh->is_initialized()) {
            return;
        }

        // Obtener datos si no se proporcionaron
        if (empty($content_data)) {
            $content_data = $this->get_content_data($content_id, $content_type);
        }

        if (empty($content_data)) {
            return;
        }

        // Preparar datos para propagación
        $propagation_data = [
            'id'           => $content_id,
            'type'         => $content_type,
            'data'         => $content_data,
            'origin_node'  => $this->get_local_node_id(),
            'updated_at'   => current_time('mysql'),
        ];

        $mesh->propagate_content($content_type, $propagation_data, 'normal');

        // También sincronizar via CRDT
        $this->sync_content_crdt($content_id, $content_type, $content_data);
    }

    /**
     * Sincroniza contenido con CRDTs
     *
     * @param int $content_id
     * @param string $content_type
     * @param array $content_data
     */
    private function sync_content_crdt($content_id, $content_type, $content_data) {
        if (!class_exists('Flavor_CRDT_Manager')) {
            return;
        }

        $crdt = Flavor_CRDT_Manager::instance();
        $local_peer = Flavor_Network_Installer::get_local_peer();

        if (!$local_peer) {
            return;
        }

        $peer_id = $local_peer->peer_id;
        $doc_id = $content_type . '_' . $content_id;

        // Sincronizar campos mapeados a CRDTs
        $field_map = [
            'titulo'      => ['value' => $content_data['titulo'] ?? '', 'op' => 'set'],
            'descripcion' => ['value' => $content_data['descripcion'] ?? '', 'op' => 'set'],
        ];

        if (isset($content_data['categorias'])) {
            $field_map['categorias'] = ['value' => $content_data['categorias'], 'op' => 'set'];
        }

        if (isset($content_data['tags'])) {
            $field_map['tags'] = ['value' => $content_data['tags'], 'op' => 'set'];
        }

        foreach ($field_map as $field => $config) {
            $crdt->update_field(
                $content_type,
                $doc_id,
                $field,
                $config['value'],
                $peer_id,
                $config['op']
            );
        }
    }

    /**
     * Recibe contenido propagado por mesh
     *
     * @param array $content_data
     * @param string $origin_peer_id
     * @param object $message
     */
    public function receive_propagated_content($content_data, $origin_peer_id, $message) {
        $type = $content_data['type'] ?? 'shared_content';
        $data = $content_data['data'] ?? [];
        $origin_node = $content_data['origin_node'] ?? null;

        // No procesar nuestro propio contenido
        $local_node_id = $this->get_local_node_id();
        if ($origin_node && $origin_node == $local_node_id) {
            return;
        }

        // Obtener nodo de origen
        $node_id = $this->get_node_id_for_peer($origin_peer_id);
        if (!$node_id) {
            // Crear nodo para este peer
            $this->sync_peer_to_node($origin_peer_id);
            $node_id = $this->get_node_id_for_peer($origin_peer_id);
        }

        if (!$node_id) {
            return;
        }

        // Insertar o actualizar contenido
        $this->upsert_content($type, $data, $node_id);
    }

    /**
     * Recibe evento propagado
     *
     * @param array $event_data
     * @param string $origin_peer_id
     * @param object $message
     */
    public function receive_propagated_event($event_data, $origin_peer_id, $message) {
        $this->receive_propagated_content([
            'type' => 'events',
            'data' => $event_data,
        ], $origin_peer_id, $message);
    }

    /**
     * Recibe anuncio de tablón propagado
     *
     * @param array $board_data
     * @param string $origin_peer_id
     * @param object $message
     */
    public function receive_propagated_board($board_data, $origin_peer_id, $message) {
        $this->receive_propagated_content([
            'type' => 'board',
            'data' => $board_data,
        ], $origin_peer_id, $message);
    }

    // ═══════════════════════════════════════════════════════════════════
    // ALERTS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Propaga una alerta solidaria
     *
     * @param int $alert_id
     * @param array $alert_data
     */
    public function propagate_alert($alert_id, $alert_data = []) {
        if (!class_exists('Flavor_Mesh_Loader')) {
            return;
        }

        $mesh = Flavor_Mesh_Loader::instance();
        if (!$mesh->is_initialized()) {
            return;
        }

        // Obtener datos si no se proporcionaron
        if (empty($alert_data)) {
            global $wpdb;
            $table = $wpdb->prefix . 'flavor_network_solidarity_alerts';
            $alert_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
                $alert_id
            ), ARRAY_A);
        }

        if (empty($alert_data)) {
            return;
        }

        $urgency = $alert_data['urgencia'] ?? 'normal';

        $mesh->propagate_alert(
            $alert_data['tipo'] ?? 'general',
            [
                'id'          => $alert_id,
                'titulo'      => $alert_data['titulo'],
                'descripcion' => $alert_data['descripcion'],
                'ubicacion'   => $alert_data['ubicacion'] ?? '',
                'contacto'    => $alert_data['contacto'] ?? '',
                'origin_node' => $this->get_local_node_id(),
            ],
            $urgency
        );
    }

    /**
     * Recibe una alerta de la red
     *
     * @param string $alert_type
     * @param array $payload
     * @param string $origin_peer_id
     * @param object $message
     */
    public function receive_alert($alert_type, $payload, $origin_peer_id, $message) {
        global $wpdb;

        // No procesar nuestras propias alertas
        $origin_node = $payload['origin_node'] ?? null;
        $local_node_id = $this->get_local_node_id();

        if ($origin_node && $origin_node == $local_node_id) {
            return;
        }

        // Obtener nodo de origen
        $node_id = $this->get_node_id_for_peer($origin_peer_id);
        if (!$node_id) {
            $this->sync_peer_to_node($origin_peer_id);
            $node_id = $this->get_node_id_for_peer($origin_peer_id);
        }

        if (!$node_id) {
            return;
        }

        // Insertar alerta si no existe
        $table = $wpdb->prefix . 'flavor_network_solidarity_alerts';

        // Verificar duplicado por título + nodo
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE nodo_id = %d AND titulo = %s",
            $node_id,
            $payload['titulo']
        ));

        if ($existing) {
            return; // Ya existe
        }

        $wpdb->insert($table, [
            'nodo_id'     => $node_id,
            'tipo'        => $alert_type,
            'titulo'      => $payload['titulo'],
            'descripcion' => $payload['descripcion'],
            'ubicacion'   => $payload['ubicacion'] ?? '',
            'contacto'    => $payload['contacto'] ?? '',
            'urgencia'    => $payload['urgency'] ?? 'media',
            'estado'      => 'activa',
        ]);

        do_action('flavor_mesh_alert_stored', $wpdb->insert_id, $payload, $origin_peer_id);
    }

    // ═══════════════════════════════════════════════════════════════════
    // CONNECTIONS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Crea conexión mesh cuando se aprueba conexión de nodos
     *
     * @param int $connection_id
     * @param array $connection_data
     */
    public function create_mesh_connection($connection_id, $connection_data = []) {
        if (!class_exists('Flavor_Mesh_Topology')) {
            return;
        }

        global $wpdb;
        $connections_table = $wpdb->prefix . 'flavor_network_connections';
        $peers_table = $wpdb->prefix . 'flavor_network_peers';

        // Obtener datos de conexión
        if (empty($connection_data)) {
            $connection_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$connections_table} WHERE id = %d",
                $connection_id
            ), ARRAY_A);
        }

        if (!$connection_data || $connection_data['estado'] !== 'aprobada') {
            return;
        }

        // Obtener peer_ids de los nodos
        $peer_a = $wpdb->get_var($wpdb->prepare(
            "SELECT peer_id FROM {$peers_table} WHERE node_id = %d",
            $connection_data['nodo_origen_id']
        ));

        $peer_b = $wpdb->get_var($wpdb->prepare(
            "SELECT peer_id FROM {$peers_table} WHERE node_id = %d",
            $connection_data['nodo_destino_id']
        ));

        if (!$peer_a || !$peer_b) {
            return;
        }

        // Crear conexión mesh
        $topology = Flavor_Mesh_Topology::instance();
        $mesh_connection_id = $topology->create_connection($peer_a, $peer_b, 'direct');

        if ($mesh_connection_id) {
            // Activar inmediatamente ya que la conexión de nodos ya está aprobada
            $topology->activate_connection($mesh_connection_id);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Obtiene ID del nodo local
     *
     * @return int|null
     */
    private function get_local_node_id() {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_network_nodes';
        return $wpdb->get_var("SELECT id FROM {$table} WHERE es_nodo_local = 1 LIMIT 1");
    }

    /**
     * Obtiene node_id asociado a un peer_id
     *
     * @param string $peer_id
     * @return int|null
     */
    private function get_node_id_for_peer($peer_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_network_peers';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT node_id FROM {$table} WHERE peer_id = %s",
            $peer_id
        ));
    }

    /**
     * Obtiene datos de contenido
     *
     * @param int $content_id
     * @param string $content_type
     * @return array|null
     */
    private function get_content_data($content_id, $content_type) {
        global $wpdb;

        $table_map = [
            'shared_content' => $wpdb->prefix . 'flavor_network_shared_content',
            'events'         => $wpdb->prefix . 'flavor_network_events',
            'board'          => $wpdb->prefix . 'flavor_network_board',
        ];

        $table = $table_map[$content_type] ?? null;
        if (!$table) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $content_id
        ), ARRAY_A);
    }

    /**
     * Inserta o actualiza contenido
     *
     * @param string $type
     * @param array $data
     * @param int $node_id
     */
    private function upsert_content($type, $data, $node_id) {
        global $wpdb;

        $table_map = [
            'shared_content' => $wpdb->prefix . 'flavor_network_shared_content',
            'events'         => $wpdb->prefix . 'flavor_network_events',
            'board'          => $wpdb->prefix . 'flavor_network_board',
        ];

        $table = $table_map[$type] ?? null;
        if (!$table) {
            return;
        }

        // Verificar si ya existe
        $existing = null;
        if (!empty($data['id'])) {
            // Buscar por ID externo si tenemos metadata
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE nodo_id = %d AND titulo = %s",
                $node_id,
                $data['titulo'] ?? ''
            ));
        }

        // Preparar datos
        $insert_data = [
            'nodo_id' => $node_id,
            'titulo'  => $data['titulo'] ?? '',
        ];

        if (isset($data['descripcion'])) {
            $insert_data['descripcion'] = $data['descripcion'];
        }

        if ($type === 'shared_content') {
            $insert_data['tipo_contenido'] = $data['tipo_contenido'] ?? 'producto';
            $insert_data['visible_red'] = 1;
        }

        if ($type === 'events') {
            $insert_data['fecha_inicio'] = $data['fecha_inicio'] ?? current_time('mysql');
            $insert_data['visible_red'] = 1;
        }

        if ($type === 'board') {
            $insert_data['contenido'] = $data['contenido'] ?? $data['descripcion'] ?? '';
            $insert_data['activo'] = 1;
        }

        if ($existing) {
            $wpdb->update($table, $insert_data, ['id' => $existing]);
        } else {
            $wpdb->insert($table, $insert_data);
        }
    }
}
