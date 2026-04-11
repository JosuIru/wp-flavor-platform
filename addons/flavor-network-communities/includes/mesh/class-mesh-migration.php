<?php
/**
 * Migración de Nodos Legacy a Sistema Mesh P2P
 *
 * Migra los datos del sistema de nodos jerárquico (DAG) al nuevo
 * sistema de peers descentralizado (Mesh).
 *
 * @package FlavorPlatform\Network\Mesh
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Mesh_Migration {

    /**
     * Versión de migración
     */
    const MIGRATION_VERSION = '1.5.0';

    /**
     * Opción que almacena el estado de migración
     */
    const MIGRATION_STATUS_OPTION = 'flavor_mesh_migration_status';

    /**
     * Ejecuta la migración completa
     *
     * @param bool $dry_run Si true, solo simula sin hacer cambios
     * @return array Resultado de la migración
     */
    public static function migrate_all($dry_run = false) {
        $results = [
            'started_at'       => current_time('mysql'),
            'dry_run'          => $dry_run,
            'nodes_migrated'   => 0,
            'peers_created'    => 0,
            'connections_migrated' => 0,
            'errors'           => [],
            'warnings'         => [],
        ];

        // Verificar que no se haya migrado ya
        $status = get_option(self::MIGRATION_STATUS_OPTION, []);
        if (!empty($status['completed']) && !$dry_run) {
            $results['warnings'][] = 'La migración ya fue completada anteriormente';
            return $results;
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_network_';

        // ═══════════════════════════════════════════════════════════════════
        // PASO 1: Migrar nodos a peers
        // ═══════════════════════════════════════════════════════════════════

        $nodes = $wpdb->get_results("SELECT * FROM {$prefix}nodes WHERE estado = 'activo'");

        if (empty($nodes)) {
            $results['warnings'][] = 'No hay nodos para migrar';
        } else {
            foreach ($nodes as $node) {
                $peer_result = self::migrate_node_to_peer($node, $dry_run);

                if ($peer_result['success']) {
                    $results['nodes_migrated']++;
                    $results['peers_created']++;
                } else {
                    $results['errors'][] = "Nodo {$node->id} ({$node->nombre}): " . $peer_result['error'];
                }
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // PASO 2: Migrar conexiones a mesh_connections
        // ═══════════════════════════════════════════════════════════════════

        $connections = $wpdb->get_results(
            "SELECT c.*,
                    no.slug as origen_slug,
                    nd.slug as destino_slug
             FROM {$prefix}connections c
             LEFT JOIN {$prefix}nodes no ON c.nodo_origen_id = no.id
             LEFT JOIN {$prefix}nodes nd ON c.nodo_destino_id = nd.id
             WHERE c.estado = 'aprobada'"
        );

        if (!empty($connections)) {
            foreach ($connections as $conn) {
                $conn_result = self::migrate_connection($conn, $dry_run);

                if ($conn_result['success']) {
                    $results['connections_migrated']++;
                } else {
                    $results['warnings'][] = "Conexión {$conn->id}: " . $conn_result['error'];
                }
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // PASO 3: Crear peer local si no existe
        // ═══════════════════════════════════════════════════════════════════

        if (!$dry_run) {
            $local_peer = self::ensure_local_peer();
            if (!$local_peer) {
                $results['errors'][] = 'No se pudo crear el peer local';
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // PASO 4: Guardar estado de migración
        // ═══════════════════════════════════════════════════════════════════

        if (!$dry_run && empty($results['errors'])) {
            update_option(self::MIGRATION_STATUS_OPTION, [
                'completed'           => true,
                'version'             => self::MIGRATION_VERSION,
                'completed_at'        => current_time('mysql'),
                'nodes_migrated'      => $results['nodes_migrated'],
                'connections_migrated' => $results['connections_migrated'],
            ]);
        }

        $results['completed_at'] = current_time('mysql');

        return $results;
    }

    /**
     * Migra un nodo a peer
     *
     * @param object $node Datos del nodo
     * @param bool $dry_run Simulación
     * @return array
     */
    private static function migrate_node_to_peer($node, $dry_run = false) {
        global $wpdb;
        $peers_table = $wpdb->prefix . 'flavor_network_peers';

        // Verificar si ya existe un peer para este nodo
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$peers_table} WHERE node_id = %d",
            $node->id
        ));

        if ($existing) {
            return [
                'success' => true,
                'message' => 'Peer ya existe',
                'peer_id' => $existing,
            ];
        }

        // Generar identidad Ed25519 para el peer
        if (!function_exists('sodium_crypto_sign_keypair')) {
            return [
                'success' => false,
                'error'   => 'Extension sodium no disponible',
            ];
        }

        $keypair = sodium_crypto_sign_keypair();
        $public_key = sodium_crypto_sign_publickey($keypair);
        $private_key = sodium_crypto_sign_secretkey($keypair);

        // Generar peer_id (hash de clave pública)
        $peer_id = hash('sha256', $public_key);

        // Cifrar clave privada (solo para nodo local)
        $is_local = (bool) $node->es_nodo_local;
        $private_key_encrypted = null;

        if ($is_local) {
            $encryption_key = self::get_encryption_key();
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $encrypted = sodium_crypto_secretbox($private_key, $nonce, $encryption_key);
            $private_key_encrypted = base64_encode($nonce . $encrypted);
        }

        // Limpiar clave privada de memoria
        sodium_memzero($private_key);

        // Preparar datos del peer
        $peer_data = [
            'peer_id'              => $peer_id,
            'node_id'              => $node->id,
            'public_key_ed25519'   => base64_encode($public_key),
            'private_key_encrypted' => $private_key_encrypted,
            'display_name'         => $node->nombre,
            'site_url'             => $node->site_url,
            'capabilities'         => wp_json_encode([
                'tipo_entidad' => $node->tipo_entidad,
                'modulos'      => maybe_unserialize($node->modulos_activos),
            ]),
            'reputacion_score'     => 50.00, // Score inicial neutro
            'trust_level'          => $is_local ? 'trusted' : 'seen',
            'is_local_peer'        => $is_local ? 1 : 0,
            'is_online'            => $is_local ? 1 : 0,
            'metadata'             => wp_json_encode([
                'migrated_from_node' => $node->id,
                'migration_date'     => current_time('mysql'),
                'original_slug'      => $node->slug,
            ]),
        ];

        if ($dry_run) {
            return [
                'success' => true,
                'message' => 'DRY RUN: Se crearía peer',
                'peer_id' => $peer_id,
                'data'    => $peer_data,
            ];
        }

        // Insertar peer
        $result = $wpdb->insert($peers_table, $peer_data);

        if ($result === false) {
            return [
                'success' => false,
                'error'   => $wpdb->last_error,
            ];
        }

        return [
            'success' => true,
            'peer_id' => $peer_id,
            'db_id'   => $wpdb->insert_id,
        ];
    }

    /**
     * Migra una conexión a mesh_connection
     *
     * @param object $conn Datos de la conexión
     * @param bool $dry_run Simulación
     * @return array
     */
    private static function migrate_connection($conn, $dry_run = false) {
        global $wpdb;
        $peers_table = $wpdb->prefix . 'flavor_network_peers';
        $mesh_table = $wpdb->prefix . 'flavor_network_mesh_connections';

        // Obtener peer_ids de los nodos
        $peer_a = $wpdb->get_var($wpdb->prepare(
            "SELECT peer_id FROM {$peers_table} WHERE node_id = %d",
            $conn->nodo_origen_id
        ));

        $peer_b = $wpdb->get_var($wpdb->prepare(
            "SELECT peer_id FROM {$peers_table} WHERE node_id = %d",
            $conn->nodo_destino_id
        ));

        if (!$peer_a || !$peer_b) {
            return [
                'success' => false,
                'error'   => 'Peers no encontrados para los nodos',
            ];
        }

        // Ordenar alfabéticamente (convención mesh)
        if ($peer_a > $peer_b) {
            list($peer_a, $peer_b) = [$peer_b, $peer_a];
        }

        // Verificar si ya existe
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$mesh_table} WHERE peer_a_id = %s AND peer_b_id = %s",
            $peer_a,
            $peer_b
        ));

        if ($existing) {
            return [
                'success' => true,
                'message' => 'Conexión mesh ya existe',
            ];
        }

        $mesh_data = [
            'peer_a_id'       => $peer_a,
            'peer_b_id'       => $peer_b,
            'connection_type' => 'direct',
            'state'           => 'active',
            'handshake_completed' => 1,
            'established_at'  => $conn->fecha_aprobacion ?: current_time('mysql'),
            'metadata'        => wp_json_encode([
                'migrated_from_connection' => $conn->id,
                'original_nivel'           => $conn->nivel,
                'migration_date'           => current_time('mysql'),
            ]),
        ];

        if ($dry_run) {
            return [
                'success' => true,
                'message' => 'DRY RUN: Se crearía conexión mesh',
                'data'    => $mesh_data,
            ];
        }

        $result = $wpdb->insert($mesh_table, $mesh_data);

        if ($result === false) {
            return [
                'success' => false,
                'error'   => $wpdb->last_error,
            ];
        }

        return [
            'success' => true,
            'mesh_id' => $wpdb->insert_id,
        ];
    }

    /**
     * Asegura que existe el peer local
     *
     * @return object|null
     */
    private static function ensure_local_peer() {
        global $wpdb;
        $peers_table = $wpdb->prefix . 'flavor_network_peers';

        // Verificar si ya existe
        $local = $wpdb->get_row(
            "SELECT * FROM {$peers_table} WHERE is_local_peer = 1 LIMIT 1"
        );

        if ($local) {
            return $local;
        }

        // Crear nuevo peer local
        if (!function_exists('sodium_crypto_sign_keypair')) {
            return null;
        }

        $keypair = sodium_crypto_sign_keypair();
        $public_key = sodium_crypto_sign_publickey($keypair);
        $private_key = sodium_crypto_sign_secretkey($keypair);

        $peer_id = hash('sha256', $public_key);

        // Cifrar clave privada
        $encryption_key = self::get_encryption_key();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $encrypted = sodium_crypto_secretbox($private_key, $nonce, $encryption_key);
        $private_key_encrypted = base64_encode($nonce . $encrypted);

        sodium_memzero($private_key);

        $wpdb->insert($peers_table, [
            'peer_id'              => $peer_id,
            'public_key_ed25519'   => base64_encode($public_key),
            'private_key_encrypted' => $private_key_encrypted,
            'display_name'         => get_bloginfo('name'),
            'site_url'             => home_url(),
            'capabilities'         => wp_json_encode(['type' => 'wordpress']),
            'reputacion_score'     => 100.00,
            'trust_level'          => 'trusted',
            'is_local_peer'        => 1,
            'is_online'            => 1,
        ]);

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$peers_table} WHERE peer_id = %s",
            $peer_id
        ));
    }

    /**
     * Obtiene la clave de cifrado para claves privadas
     *
     * @return string
     */
    private static function get_encryption_key() {
        $key = get_option('flavor_mesh_encryption_key');

        if (!$key) {
            $key = sodium_crypto_secretbox_keygen();
            update_option('flavor_mesh_encryption_key', base64_encode($key), false);
            return $key;
        }

        return base64_decode($key);
    }

    /**
     * Revierte la migración (para desarrollo/testing)
     *
     * @return array
     */
    public static function rollback() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_network_';

        $results = [
            'peers_deleted'      => 0,
            'connections_deleted' => 0,
        ];

        // Eliminar peers migrados (tienen node_id)
        $results['peers_deleted'] = $wpdb->query(
            "DELETE FROM {$prefix}peers WHERE node_id IS NOT NULL"
        );

        // Eliminar conexiones mesh migradas
        $results['connections_deleted'] = $wpdb->query(
            "DELETE FROM {$prefix}mesh_connections
             WHERE JSON_EXTRACT(metadata, '$.migrated_from_connection') IS NOT NULL"
        );

        // Limpiar estado de migración
        delete_option(self::MIGRATION_STATUS_OPTION);

        return $results;
    }

    /**
     * Obtiene el estado de la migración
     *
     * @return array
     */
    public static function get_status() {
        return get_option(self::MIGRATION_STATUS_OPTION, [
            'completed' => false,
        ]);
    }

    /**
     * Obtiene estadísticas de lo que se migraría
     *
     * @return array
     */
    public static function get_migration_preview() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_network_';

        return [
            'nodes_to_migrate' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$prefix}nodes
                 WHERE estado = 'activo'
                   AND id NOT IN (SELECT node_id FROM {$prefix}peers WHERE node_id IS NOT NULL)"
            ),
            'connections_to_migrate' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$prefix}connections WHERE estado = 'aprobada'"
            ),
            'existing_peers' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$prefix}peers"
            ),
            'existing_mesh_connections' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$prefix}mesh_connections"
            ),
        ];
    }
}

/**
 * Comandos WP-CLI para migración
 */
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('flavor-mesh migrate', function($args, $assoc_args) {
        $dry_run = isset($assoc_args['dry-run']);

        if ($dry_run) {
            WP_CLI::log('Ejecutando en modo DRY RUN (sin cambios reales)...');
        }

        $result = Flavor_Mesh_Migration::migrate_all($dry_run);

        WP_CLI::log('');
        WP_CLI::log('=== Resultado de Migración ===');
        WP_CLI::log("Nodos migrados: {$result['nodes_migrated']}");
        WP_CLI::log("Peers creados: {$result['peers_created']}");
        WP_CLI::log("Conexiones migradas: {$result['connections_migrated']}");

        if (!empty($result['errors'])) {
            WP_CLI::log('');
            WP_CLI::warning('Errores:');
            foreach ($result['errors'] as $error) {
                WP_CLI::log("  - {$error}");
            }
        }

        if (!empty($result['warnings'])) {
            WP_CLI::log('');
            WP_CLI::warning('Advertencias:');
            foreach ($result['warnings'] as $warning) {
                WP_CLI::log("  - {$warning}");
            }
        }

        if (empty($result['errors'])) {
            WP_CLI::success('Migración completada');
        } else {
            WP_CLI::error('Migración completada con errores');
        }
    });

    WP_CLI::add_command('flavor-mesh migrate-status', function() {
        $status = Flavor_Mesh_Migration::get_status();
        $preview = Flavor_Mesh_Migration::get_migration_preview();

        WP_CLI::log('=== Estado de Migración ===');
        WP_CLI::log('Completada: ' . ($status['completed'] ? 'Sí' : 'No'));

        if ($status['completed']) {
            WP_CLI::log("Versión: {$status['version']}");
            WP_CLI::log("Fecha: {$status['completed_at']}");
        }

        WP_CLI::log('');
        WP_CLI::log('=== Datos Pendientes ===');
        WP_CLI::log("Nodos por migrar: {$preview['nodes_to_migrate']}");
        WP_CLI::log("Conexiones por migrar: {$preview['connections_to_migrate']}");
        WP_CLI::log('');
        WP_CLI::log('=== Datos Actuales Mesh ===');
        WP_CLI::log("Peers existentes: {$preview['existing_peers']}");
        WP_CLI::log("Conexiones mesh: {$preview['existing_mesh_connections']}");
    });

    WP_CLI::add_command('flavor-mesh migrate-rollback', function($args, $assoc_args) {
        if (!isset($assoc_args['confirm'])) {
            WP_CLI::error('Usa --confirm para confirmar el rollback. ESTO ELIMINARÁ DATOS.');
            return;
        }

        $result = Flavor_Mesh_Migration::rollback();

        WP_CLI::log("Peers eliminados: {$result['peers_deleted']}");
        WP_CLI::log("Conexiones mesh eliminadas: {$result['connections_deleted']}");
        WP_CLI::success('Rollback completado');
    });
}
