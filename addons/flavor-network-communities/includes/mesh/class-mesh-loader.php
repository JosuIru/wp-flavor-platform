<?php
/**
 * Loader del sistema P2P/Mesh
 *
 * Inicializa y coordina todos los componentes del sistema mesh:
 * - Vector Clocks y CRDTs
 * - Gossip Protocol
 * - Mesh Topology
 * - Peer Discovery
 * - REST API
 *
 * @package FlavorPlatform\Network\Mesh
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Mesh_Loader {

    /**
     * Instancia singleton
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Directorio base de los archivos mesh
     *
     * @var string
     */
    private $base_dir;

    /**
     * Indica si el sistema está inicializado
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        $this->base_dir = dirname(__FILE__);
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
     * Inicializa el sistema mesh
     */
    public function init() {
        if ($this->initialized) {
            return;
        }

        // Verificar requisitos
        if (!$this->check_requirements()) {
            error_log('[Flavor Mesh] Requisitos no cumplidos. Sistema mesh desactivado.');
            return;
        }

        // Cargar clases CRDT
        $this->load_crdt_classes();

        // Cargar clases mesh
        $this->load_mesh_classes();

        // Asegurar que existe el peer local
        $this->ensure_local_peer();

        // Inicializar singletons
        $this->init_singletons();

        // Registrar hooks de procesamiento de mensajes
        $this->register_message_handlers();

        // Registrar cron jobs para procesamiento asíncrono
        $this->register_cron_jobs();

        // Registrar hooks para propagación automática de contenido
        $this->register_content_hooks();

        $this->initialized = true;

        do_action('flavor_mesh_initialized');

        error_log('[Flavor Mesh] Sistema P2P/Mesh inicializado correctamente.');
    }

    /**
     * Verifica requisitos del sistema
     *
     * @return bool
     */
    private function check_requirements() {
        // Verificar PHP 7.2+ con libsodium
        if (version_compare(PHP_VERSION, '7.2.0', '<')) {
            return false;
        }

        if (!extension_loaded('sodium')) {
            return false;
        }

        // Verificar funciones necesarias
        $required_functions = [
            'sodium_crypto_sign_keypair',
            'sodium_crypto_sign_detached',
            'sodium_crypto_sign_verify_detached',
            'sodium_crypto_secretbox',
        ];

        foreach ($required_functions as $func) {
            if (!function_exists($func)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Carga clases CRDT
     */
    private function load_crdt_classes() {
        $crdt_dir = dirname($this->base_dir) . '/crdt';

        $crdt_files = [
            'class-vector-clock.php',
            'class-lww-register.php',
            'class-or-set.php',
            'class-g-counter.php',
            'class-pn-counter.php',
            'class-crdt-manager.php',
        ];

        foreach ($crdt_files as $file) {
            $path = $crdt_dir . '/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    /**
     * Carga clases mesh
     */
    private function load_mesh_classes() {
        $mesh_files = [
            'class-mesh-cache.php',        // Sistema de caché (cargar primero)
            'class-mesh-async.php',        // Requests asíncronas
            'class-network-peer.php',      // Modelo de Peer
            'class-gossip-protocol.php',   // Protocolo Gossip
            'class-mesh-topology.php',     // Topología Mesh
            'class-peer-discovery.php',    // Descubrimiento de Peers
            'class-mesh-api.php',          // REST API
            'class-mesh-node-bridge.php',  // Bridge Node <-> Peer
            'class-mesh-migration.php',    // Migración de nodos legacy
        ];

        // Cargar admin solo en backend
        if (is_admin()) {
            $mesh_files[] = 'class-mesh-admin.php';
        }

        foreach ($mesh_files as $file) {
            $path = $this->base_dir . '/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    /**
     * Asegura que existe el peer local
     */
    private function ensure_local_peer() {
        require_once dirname($this->base_dir) . '/class-network-installer.php';
        Flavor_Network_Installer::ensure_local_peer_exists();
    }

    /**
     * Inicializa los singletons de cada componente
     */
    private function init_singletons() {
        // CRDT Manager
        if (class_exists('Flavor_CRDT_Manager')) {
            Flavor_CRDT_Manager::instance();
        }

        // Gossip Protocol
        if (class_exists('Flavor_Gossip_Protocol')) {
            Flavor_Gossip_Protocol::instance();
        }

        // Mesh Topology
        if (class_exists('Flavor_Mesh_Topology')) {
            Flavor_Mesh_Topology::instance();
        }

        // Peer Discovery
        if (class_exists('Flavor_Peer_Discovery')) {
            Flavor_Peer_Discovery::instance();
        }

        // REST API
        if (class_exists('Flavor_Mesh_API')) {
            Flavor_Mesh_API::instance();
        }

        // Bridge Node <-> Peer (para compatibilidad con sistema legacy)
        if (class_exists('Flavor_Mesh_Node_Bridge')) {
            Flavor_Mesh_Node_Bridge::instance();
        }
    }

    /**
     * Registra handlers para procesamiento de mensajes gossip
     */
    private function register_message_handlers() {
        // Handler para sincronización CRDT via gossip
        add_action('flavor_mesh_process_crdt_sync', [$this, 'handle_crdt_sync'], 10, 2);

        // Handler para compartir contenido
        add_action('flavor_mesh_process_content_share', [$this, 'handle_content_share'], 10, 2);

        // Handler para alertas
        add_action('flavor_mesh_process_alert', [$this, 'handle_alert'], 10, 2);

        // Handler para heartbeats
        add_action('flavor_mesh_process_heartbeat', [$this, 'handle_heartbeat'], 10, 2);
    }

    /**
     * Handler para sincronización CRDT
     *
     * @param array $payload
     * @param object $message
     */
    public function handle_crdt_sync($payload, $message) {
        $doc_type = $payload['doc_type'] ?? '';
        $doc_id = $payload['doc_id'] ?? '';
        $fields = $payload['fields'] ?? [];
        $origin_peer_id = $message->origin_peer_id;

        if (empty($doc_type) || empty($doc_id)) {
            return;
        }

        $crdt_manager = Flavor_CRDT_Manager::instance();

        foreach ($fields as $field_name => $field_data) {
            $crdt_manager->merge_remote(
                $doc_type,
                $doc_id,
                $field_name,
                $field_data['state_data'] ?? '',
                $origin_peer_id
            );
        }
    }

    /**
     * Handler para contenido compartido
     *
     * @param array $payload
     * @param object $message
     */
    public function handle_content_share($payload, $message) {
        $content_type = $payload['content_type'] ?? '';
        $content_data = $payload['data'] ?? [];
        $origin_peer_id = $message->origin_peer_id;

        if (empty($content_type) || empty($content_data)) {
            return;
        }

        // Disparar acción específica por tipo de contenido
        do_action("flavor_mesh_content_received_{$content_type}", $content_data, $origin_peer_id, $message);
        do_action('flavor_mesh_content_received', $content_type, $content_data, $origin_peer_id, $message);
    }

    /**
     * Handler para alertas
     *
     * @param array $payload
     * @param object $message
     */
    public function handle_alert($payload, $message) {
        $alert_type = $payload['type'] ?? 'general';
        $urgency = $payload['urgency'] ?? 'normal';
        $origin_peer_id = $message->origin_peer_id;

        do_action('flavor_mesh_alert_received', $alert_type, $payload, $origin_peer_id, $message);

        // Log de alertas urgentes
        if (in_array($urgency, ['high', 'urgent'], true)) {
            error_log("[Flavor Mesh] Alerta urgente recibida: {$alert_type} desde {$origin_peer_id}");
        }
    }

    /**
     * Handler para heartbeats
     *
     * @param array $payload
     * @param object $message
     */
    public function handle_heartbeat($payload, $message) {
        $peer_id = $payload['peer_id'] ?? '';

        if (empty($peer_id)) {
            return;
        }

        // Actualizar estado del peer
        global $wpdb;
        $peers_table = $wpdb->prefix . 'flavor_network_peers';

        $wpdb->update(
            $peers_table,
            [
                'is_online'  => 1,
                'last_seen'  => current_time('mysql'),
            ],
            ['peer_id' => $peer_id]
        );
    }

    /**
     * Propaga contenido local a la red
     *
     * Método de conveniencia para enviar contenido via gossip
     *
     * @param string $content_type Tipo de contenido
     * @param array $content_data Datos del contenido
     * @param string $priority Prioridad: low, normal, high, urgent
     * @return array|false
     */
    public function propagate_content($content_type, array $content_data, $priority = 'normal') {
        if (!class_exists('Flavor_Gossip_Protocol')) {
            return false;
        }

        $gossip = Flavor_Gossip_Protocol::instance();

        return $gossip->gossip_message('content_share', [
            'content_type' => $content_type,
            'data'         => $content_data,
            'timestamp'    => time(),
        ], 5, $priority);
    }

    /**
     * Propaga una alerta a la red
     *
     * @param string $alert_type Tipo de alerta
     * @param array $alert_data Datos de la alerta
     * @param string $urgency Urgencia: low, normal, high, urgent
     * @return array|false
     */
    public function propagate_alert($alert_type, array $alert_data, $urgency = 'normal') {
        if (!class_exists('Flavor_Gossip_Protocol')) {
            return false;
        }

        $gossip = Flavor_Gossip_Protocol::instance();

        $priority = in_array($urgency, ['high', 'urgent'], true) ? 'urgent' : 'normal';

        return $gossip->gossip_message('alert', array_merge($alert_data, [
            'type'      => $alert_type,
            'urgency'   => $urgency,
            'timestamp' => time(),
        ]), 7, $priority);
    }

    /**
     * Sincroniza un documento CRDT con la red
     *
     * @param string $doc_type
     * @param string $doc_id
     * @return array|false
     */
    public function sync_document($doc_type, $doc_id) {
        if (!class_exists('Flavor_CRDT_Manager') || !class_exists('Flavor_Gossip_Protocol')) {
            return false;
        }

        $crdt_manager = Flavor_CRDT_Manager::instance();
        $export = $crdt_manager->export_document($doc_type, $doc_id);

        $gossip = Flavor_Gossip_Protocol::instance();

        return $gossip->gossip_message('crdt_sync', $export, 3, 'normal');
    }

    /**
     * Obtiene el estado del sistema mesh
     *
     * @return array
     */
    public function get_status() {
        $local_peer = Flavor_Network_Installer::get_local_peer();

        $status = [
            'initialized'   => $this->initialized,
            'local_peer_id' => $local_peer ? $local_peer->peer_id : null,
            'version'       => '1.5.0',
            'components'    => [
                'crdt'      => class_exists('Flavor_CRDT_Manager'),
                'gossip'    => class_exists('Flavor_Gossip_Protocol'),
                'topology'  => class_exists('Flavor_Mesh_Topology'),
                'discovery' => class_exists('Flavor_Peer_Discovery'),
                'api'       => class_exists('Flavor_Mesh_API'),
            ],
        ];

        if ($this->initialized) {
            try {
                $status['stats'] = [
                    'topology' => Flavor_Mesh_Topology::instance()->get_topology_stats(),
                    'gossip'   => Flavor_Gossip_Protocol::instance()->get_stats(),
                ];
            } catch (Exception $e) {
                $status['stats_error'] = $e->getMessage();
            }
        }

        return $status;
    }

    /**
     * Verifica si el sistema está inicializado
     *
     * @return bool
     */
    public function is_initialized() {
        return $this->initialized;
    }

    // ═══════════════════════════════════════════════════════════════════
    // CRON JOBS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Registra los cron jobs necesarios para el sistema mesh
     *
     * @since 1.5.0
     */
    private function register_cron_jobs() {
        // Registrar intervalos personalizados
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);

        // Registrar handlers de cron
        add_action('flavor_mesh_gossip_batch', [$this, 'cron_process_gossip_batch']);
        add_action('flavor_mesh_heartbeat', [$this, 'cron_send_heartbeats']);
        add_action('flavor_mesh_peer_discovery', [$this, 'cron_discover_peers']);
        add_action('flavor_mesh_cleanup_expired', [$this, 'cron_cleanup_expired']);

        // Programar cron jobs si no están programados
        if (!wp_next_scheduled('flavor_mesh_gossip_batch')) {
            wp_schedule_event(time(), 'every_minute', 'flavor_mesh_gossip_batch');
        }

        if (!wp_next_scheduled('flavor_mesh_heartbeat')) {
            wp_schedule_event(time(), 'every_five_minutes', 'flavor_mesh_heartbeat');
        }

        if (!wp_next_scheduled('flavor_mesh_peer_discovery')) {
            wp_schedule_event(time(), 'hourly', 'flavor_mesh_peer_discovery');
        }

        if (!wp_next_scheduled('flavor_mesh_cleanup_expired')) {
            wp_schedule_event(time(), 'twicedaily', 'flavor_mesh_cleanup_expired');
        }
    }

    /**
     * Añade intervalos de cron personalizados
     *
     * @param array $schedules Intervalos existentes
     * @return array
     */
    public function add_cron_schedules($schedules) {
        $schedules['every_minute'] = [
            'interval' => 60,
            'display'  => __('Cada minuto', 'flavor-network-communities'),
        ];

        $schedules['every_five_minutes'] = [
            'interval' => 300,
            'display'  => __('Cada 5 minutos', 'flavor-network-communities'),
        ];

        return $schedules;
    }

    /**
     * Cron: Procesa batch de mensajes gossip pendientes
     *
     * @since 1.5.0
     */
    public function cron_process_gossip_batch() {
        if (!class_exists('Flavor_Gossip_Protocol')) {
            return;
        }

        $gossip = Flavor_Gossip_Protocol::instance();
        $gossip->process_pending_batch();
    }

    /**
     * Cron: Envía heartbeats a peers conectados
     *
     * @since 1.5.0
     */
    public function cron_send_heartbeats() {
        if (!class_exists('Flavor_Gossip_Protocol') || !class_exists('Flavor_Network_Peer')) {
            return;
        }

        $local_peer = Flavor_Network_Peer::get_local();
        if (!$local_peer) {
            return;
        }

        $gossip = Flavor_Gossip_Protocol::instance();
        $gossip->gossip_message('heartbeat', [
            'peer_id'   => $local_peer->peer_id,
            'timestamp' => time(),
            'status'    => 'online',
        ], 2, 'low');
    }

    /**
     * Cron: Descubre nuevos peers via bootstrap y PEX
     *
     * @since 1.5.0
     */
    public function cron_discover_peers() {
        if (!class_exists('Flavor_Peer_Discovery')) {
            return;
        }

        $discovery = Flavor_Peer_Discovery::instance();

        // Intentar descubrir desde bootstrap nodes
        $discovery->discover_from_bootstrap();

        // Anunciarse a la red
        $discovery->announce_self();
    }

    /**
     * Cron: Limpia mensajes expirados y datos obsoletos
     *
     * @since 1.5.0
     */
    public function cron_cleanup_expired() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_network_';

        // Eliminar mensajes gossip expirados (más de 24h)
        $wpdb->query(
            "DELETE FROM {$prefix}gossip_messages
             WHERE (expires_at IS NOT NULL AND expires_at < NOW())
                OR (expires_at IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR))"
        );

        // Marcar peers como offline si no se han visto en 30 minutos
        $wpdb->query(
            "UPDATE {$prefix}peers
             SET is_online = 0
             WHERE is_local_peer = 0
               AND is_online = 1
               AND last_seen < DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
        );

        // Eliminar logs de sync antiguos (más de 7 días)
        $wpdb->query(
            "DELETE FROM {$prefix}sync_log
             WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        // Log de limpieza
        error_log('[Flavor Mesh] Limpieza de datos expirados completada.');
    }

    // ═══════════════════════════════════════════════════════════════════
    // HOOKS DE CONTENIDO
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Registra hooks para propagar contenido automáticamente
     *
     * @since 1.5.0
     */
    private function register_content_hooks() {
        // Hook cuando se crea/actualiza contenido compartido
        add_action('flavor_network_content_created', [$this, 'on_content_created'], 10, 2);
        add_action('flavor_network_content_updated', [$this, 'on_content_updated'], 10, 2);

        // Hook cuando se crea/actualiza un evento
        add_action('flavor_network_event_created', [$this, 'on_event_created'], 10, 2);
        add_action('flavor_network_event_updated', [$this, 'on_event_updated'], 10, 2);

        // Hook cuando se crea una colaboración
        add_action('flavor_network_collaboration_created', [$this, 'on_collaboration_created'], 10, 2);

        // Hook para alertas solidarias
        add_action('flavor_network_alert_created', [$this, 'on_alert_created'], 10, 2);
    }

    /**
     * Handler: Contenido compartido creado
     *
     * @param int   $content_id ID del contenido
     * @param array $data       Datos del contenido
     */
    public function on_content_created($content_id, $data) {
        $this->propagate_content('shared_content', array_merge($data, [
            'content_id' => $content_id,
            'action'     => 'create',
        ]), 'normal');
    }

    /**
     * Handler: Contenido compartido actualizado
     *
     * @param int   $content_id ID del contenido
     * @param array $data       Datos actualizados
     */
    public function on_content_updated($content_id, $data) {
        $this->propagate_content('shared_content', array_merge($data, [
            'content_id' => $content_id,
            'action'     => 'update',
        ]), 'normal');
    }

    /**
     * Handler: Evento creado
     *
     * @param int   $event_id ID del evento
     * @param array $data     Datos del evento
     */
    public function on_event_created($event_id, $data) {
        $this->propagate_content('event', array_merge($data, [
            'event_id' => $event_id,
            'action'   => 'create',
        ]), 'normal');
    }

    /**
     * Handler: Evento actualizado
     *
     * @param int   $event_id ID del evento
     * @param array $data     Datos actualizados
     */
    public function on_event_updated($event_id, $data) {
        $this->propagate_content('event', array_merge($data, [
            'event_id' => $event_id,
            'action'   => 'update',
        ]), 'normal');
    }

    /**
     * Handler: Colaboración creada
     *
     * @param int   $collab_id ID de la colaboración
     * @param array $data      Datos de la colaboración
     */
    public function on_collaboration_created($collab_id, $data) {
        $this->propagate_content('collaboration', array_merge($data, [
            'collaboration_id' => $collab_id,
            'action'           => 'create',
        ]), 'normal');
    }

    /**
     * Handler: Alerta solidaria creada
     *
     * @param int   $alert_id ID de la alerta
     * @param array $data     Datos de la alerta
     */
    public function on_alert_created($alert_id, $data) {
        $urgency = $data['urgencia'] ?? 'normal';
        $this->propagate_alert('solidarity', array_merge($data, [
            'alert_id' => $alert_id,
        ]), $urgency);
    }
}

/**
 * Función helper para obtener el loader del sistema mesh
 *
 * @return Flavor_Mesh_Loader
 */
function flavor_mesh() {
    return Flavor_Mesh_Loader::instance();
}
