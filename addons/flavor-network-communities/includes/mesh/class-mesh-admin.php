<?php
/**
 * Panel de Administración del Sistema Mesh
 *
 * Proporciona una interfaz visual para monitorear y gestionar
 * el sistema P2P/Mesh de Flavor Network Communities.
 *
 * @package FlavorPlatform\Network\Mesh
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Mesh_Admin {

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
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_flavor_mesh_get_status', [$this, 'ajax_get_status']);
        add_action('wp_ajax_flavor_mesh_trigger_discovery', [$this, 'ajax_trigger_discovery']);
        add_action('wp_ajax_flavor_mesh_add_bootstrap', [$this, 'ajax_add_bootstrap']);
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
     * Añade el menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-network',
            __('Sistema Mesh P2P', 'flavor-network-communities'),
            __('Mesh P2P', 'flavor-network-communities'),
            'manage_options',
            'flavor-mesh-admin',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Encola scripts y estilos para el admin
     *
     * @param string $hook_suffix Sufijo del hook de página
     */
    public function enqueue_scripts($hook_suffix) {
        if (strpos($hook_suffix, 'flavor-mesh-admin') === false) {
            return;
        }

        wp_enqueue_style(
            'flavor-mesh-admin',
            FLAVOR_NETWORK_URL . 'assets/css/mesh-admin.css',
            [],
            FLAVOR_NETWORK_VERSION
        );

        wp_enqueue_script(
            'flavor-mesh-admin',
            FLAVOR_NETWORK_URL . 'assets/js/mesh-admin.js',
            ['jquery'],
            FLAVOR_NETWORK_VERSION,
            true
        );

        wp_localize_script('flavor-mesh-admin', 'flavorMeshAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_mesh_admin'),
            'i18n'    => [
                'loading'       => __('Cargando...', 'flavor-network-communities'),
                'error'         => __('Error al cargar datos', 'flavor-network-communities'),
                'online'        => __('Online', 'flavor-network-communities'),
                'offline'       => __('Offline', 'flavor-network-communities'),
                'discovering'   => __('Descubriendo peers...', 'flavor-network-communities'),
                'discovered'    => __('Descubrimiento completado', 'flavor-network-communities'),
            ],
        ]);
    }

    /**
     * Renderiza la página de administración
     */
    public function render_admin_page() {
        $mesh_loader = class_exists('Flavor_Mesh_Loader') ? Flavor_Mesh_Loader::instance() : null;
        $status = $mesh_loader ? $mesh_loader->get_status() : ['initialized' => false];

        $local_peer = class_exists('Flavor_Network_Installer')
            ? Flavor_Network_Installer::get_local_peer()
            : null;

        ?>
        <div class="wrap flavor-mesh-admin">
            <h1>
                <span class="dashicons dashicons-networking"></span>
                <?php esc_html_e('Sistema Mesh P2P', 'flavor-network-communities'); ?>
            </h1>

            <?php if (!$status['initialized']): ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php esc_html_e('Sistema no inicializado', 'flavor-network-communities'); ?></strong>
                        <?php esc_html_e('El sistema mesh no está activo. Verifica que la extensión sodium esté instalada.', 'flavor-network-communities'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Estado General -->
            <div class="mesh-dashboard">
                <div class="mesh-card mesh-status-card">
                    <h2><?php esc_html_e('Estado del Sistema', 'flavor-network-communities'); ?></h2>
                    <div class="mesh-status-grid">
                        <div class="mesh-status-item">
                            <span class="status-label"><?php esc_html_e('Estado', 'flavor-network-communities'); ?></span>
                            <span class="status-value <?php echo $status['initialized'] ? 'status-ok' : 'status-error'; ?>">
                                <?php echo $status['initialized'] ? '✅ Activo' : '❌ Inactivo'; ?>
                            </span>
                        </div>
                        <div class="mesh-status-item">
                            <span class="status-label"><?php esc_html_e('Versión', 'flavor-network-communities'); ?></span>
                            <span class="status-value"><?php echo esc_html($status['version'] ?? '1.5.0'); ?></span>
                        </div>
                        <div class="mesh-status-item">
                            <span class="status-label"><?php esc_html_e('Peer Local', 'flavor-network-communities'); ?></span>
                            <span class="status-value" title="<?php echo esc_attr($local_peer->peer_id ?? ''); ?>">
                                <?php echo $local_peer ? substr($local_peer->peer_id, 0, 12) . '...' : 'No configurado'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Componentes -->
                <div class="mesh-card">
                    <h2><?php esc_html_e('Componentes', 'flavor-network-communities'); ?></h2>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Componente', 'flavor-network-communities'); ?></th>
                                <th><?php esc_html_e('Estado', 'flavor-network-communities'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $components = $status['components'] ?? [];
                            $component_names = [
                                'crdt'      => 'CRDT Manager',
                                'gossip'    => 'Gossip Protocol',
                                'topology'  => 'Mesh Topology',
                                'discovery' => 'Peer Discovery',
                                'api'       => 'REST API',
                            ];
                            foreach ($component_names as $key => $name):
                                $active = $components[$key] ?? false;
                            ?>
                            <tr>
                                <td><?php echo esc_html($name); ?></td>
                                <td>
                                    <?php if ($active): ?>
                                        <span class="status-badge status-ok">✅ Cargado</span>
                                    <?php else: ?>
                                        <span class="status-badge status-error">❌ No disponible</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Estadísticas -->
                <?php if (!empty($status['stats'])): ?>
                <div class="mesh-card">
                    <h2><?php esc_html_e('Estadísticas', 'flavor-network-communities'); ?></h2>
                    <div class="mesh-stats-grid">
                        <?php
                        $topology_stats = $status['stats']['topology'] ?? [];
                        $gossip_stats = $status['stats']['gossip'] ?? [];
                        ?>
                        <div class="mesh-stat">
                            <span class="stat-number"><?php echo esc_html($topology_stats['total_peers'] ?? 0); ?></span>
                            <span class="stat-label"><?php esc_html_e('Peers Totales', 'flavor-network-communities'); ?></span>
                        </div>
                        <div class="mesh-stat">
                            <span class="stat-number"><?php echo esc_html($topology_stats['online_peers'] ?? 0); ?></span>
                            <span class="stat-label"><?php esc_html_e('Peers Online', 'flavor-network-communities'); ?></span>
                        </div>
                        <div class="mesh-stat">
                            <span class="stat-number"><?php echo esc_html($topology_stats['total_connections'] ?? 0); ?></span>
                            <span class="stat-label"><?php esc_html_e('Conexiones', 'flavor-network-communities'); ?></span>
                        </div>
                        <div class="mesh-stat">
                            <span class="stat-number"><?php echo esc_html($gossip_stats['total_messages'] ?? 0); ?></span>
                            <span class="stat-label"><?php esc_html_e('Mensajes Gossip', 'flavor-network-communities'); ?></span>
                        </div>
                        <div class="mesh-stat">
                            <span class="stat-number"><?php echo esc_html($gossip_stats['pending_forward'] ?? 0); ?></span>
                            <span class="stat-label"><?php esc_html_e('Pendientes', 'flavor-network-communities'); ?></span>
                        </div>
                        <div class="mesh-stat">
                            <span class="stat-number"><?php echo esc_html($gossip_stats['active_peers'] ?? 0); ?></span>
                            <span class="stat-label"><?php esc_html_e('Peers Activos', 'flavor-network-communities'); ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Peers Conocidos -->
                <div class="mesh-card">
                    <h2>
                        <?php esc_html_e('Peers Conocidos', 'flavor-network-communities'); ?>
                        <button type="button" class="button button-secondary" id="btn-discover-peers">
                            <span class="dashicons dashicons-search"></span>
                            <?php esc_html_e('Descubrir Peers', 'flavor-network-communities'); ?>
                        </button>
                    </h2>
                    <div id="peers-list">
                        <?php $this->render_peers_list(); ?>
                    </div>
                </div>

                <!-- Bootstrap Nodes -->
                <div class="mesh-card">
                    <h2><?php esc_html_e('Nodos Bootstrap', 'flavor-network-communities'); ?></h2>
                    <?php $this->render_bootstrap_nodes(); ?>

                    <form id="form-add-bootstrap" class="form-add-bootstrap">
                        <input type="text" name="bootstrap_url" placeholder="https://ejemplo.com" required>
                        <input type="text" name="bootstrap_name" placeholder="Nombre (opcional)">
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Añadir Bootstrap', 'flavor-network-communities'); ?>
                        </button>
                    </form>
                </div>

                <!-- Cron Jobs -->
                <div class="mesh-card">
                    <h2><?php esc_html_e('Tareas Programadas', 'flavor-network-communities'); ?></h2>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Tarea', 'flavor-network-communities'); ?></th>
                                <th><?php esc_html_e('Intervalo', 'flavor-network-communities'); ?></th>
                                <th><?php esc_html_e('Próxima Ejecución', 'flavor-network-communities'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cron_jobs = [
                                'flavor_mesh_gossip_batch'   => ['Gossip Batch', 'Cada minuto'],
                                'flavor_mesh_heartbeat'      => ['Heartbeat', 'Cada 5 min'],
                                'flavor_mesh_peer_discovery' => ['Peer Discovery', 'Cada hora'],
                                'flavor_mesh_cleanup_expired'=> ['Limpieza', 'Dos veces al día'],
                            ];
                            foreach ($cron_jobs as $hook => $info):
                                $next = wp_next_scheduled($hook);
                            ?>
                            <tr>
                                <td><?php echo esc_html($info[0]); ?></td>
                                <td><?php echo esc_html($info[1]); ?></td>
                                <td>
                                    <?php if ($next): ?>
                                        <?php echo esc_html(human_time_diff($next) . ' ' . __('desde ahora', 'flavor-network-communities')); ?>
                                    <?php else: ?>
                                        <span class="status-badge status-warning">No programada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <style>
        .flavor-mesh-admin { max-width: 1200px; }
        .mesh-dashboard { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 20px; }
        .mesh-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; }
        .mesh-card h2 { margin-top: 0; display: flex; justify-content: space-between; align-items: center; }
        .mesh-status-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .mesh-status-item { text-align: center; padding: 15px; background: #f9f9f9; border-radius: 4px; }
        .status-label { display: block; font-size: 12px; color: #666; margin-bottom: 5px; }
        .status-value { display: block; font-size: 16px; font-weight: 600; }
        .status-ok { color: #46b450; }
        .status-error { color: #dc3232; }
        .status-warning { color: #ffb900; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; }
        .mesh-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .mesh-stat { text-align: center; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 8px; }
        .stat-number { display: block; font-size: 32px; font-weight: 700; }
        .stat-label { display: block; font-size: 12px; opacity: 0.9; margin-top: 5px; }
        .form-add-bootstrap { display: flex; gap: 10px; margin-top: 15px; }
        .form-add-bootstrap input[type="text"] { flex: 1; }
        #peers-list { max-height: 300px; overflow-y: auto; }
        .peer-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee; }
        .peer-item:last-child { border-bottom: none; }
        .peer-info { flex: 1; }
        .peer-name { font-weight: 600; }
        .peer-id { font-size: 11px; color: #666; font-family: monospace; }
        .peer-status { padding: 3px 10px; border-radius: 12px; font-size: 11px; }
        .peer-online { background: #d4edda; color: #155724; }
        .peer-offline { background: #f8d7da; color: #721c24; }
        </style>
        <?php
    }

    /**
     * Renderiza la lista de peers
     */
    private function render_peers_list() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_network_';

        $peers = $wpdb->get_results(
            "SELECT peer_id, display_name, site_url, trust_level, is_online, is_local_peer, last_seen
             FROM {$prefix}peers
             ORDER BY is_local_peer DESC, is_online DESC, last_seen DESC
             LIMIT 50"
        );

        if (empty($peers)) {
            echo '<p>' . esc_html__('No hay peers registrados.', 'flavor-network-communities') . '</p>';
            return;
        }

        foreach ($peers as $peer):
            $status_class = $peer->is_online ? 'peer-online' : 'peer-offline';
            $status_text = $peer->is_online ? __('Online', 'flavor-network-communities') : __('Offline', 'flavor-network-communities');
            if ($peer->is_local_peer) {
                $status_text = __('Local', 'flavor-network-communities');
                $status_class = 'peer-online';
            }
        ?>
        <div class="peer-item">
            <div class="peer-info">
                <div class="peer-name">
                    <?php echo esc_html($peer->display_name ?: __('Sin nombre', 'flavor-network-communities')); ?>
                    <?php if ($peer->is_local_peer): ?>
                        <span class="dashicons dashicons-admin-home" title="<?php esc_attr_e('Peer local', 'flavor-network-communities'); ?>"></span>
                    <?php endif; ?>
                </div>
                <div class="peer-id"><?php echo esc_html(substr($peer->peer_id, 0, 24) . '...'); ?></div>
                <?php if ($peer->site_url): ?>
                    <div class="peer-url" style="font-size: 11px; color: #0073aa;"><?php echo esc_html($peer->site_url); ?></div>
                <?php endif; ?>
            </div>
            <span class="peer-status <?php echo esc_attr($status_class); ?>">
                <?php echo esc_html($status_text); ?>
            </span>
        </div>
        <?php
        endforeach;
    }

    /**
     * Renderiza los nodos bootstrap
     */
    private function render_bootstrap_nodes() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_network_';

        $nodes = $wpdb->get_results(
            "SELECT id, url, name, is_enabled, last_success, failures_count
             FROM {$prefix}bootstrap_nodes
             ORDER BY priority ASC"
        );

        if (empty($nodes)) {
            echo '<p>' . esc_html__('No hay nodos bootstrap configurados.', 'flavor-network-communities') . '</p>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('URL', 'flavor-network-communities') . '</th>';
        echo '<th>' . esc_html__('Nombre', 'flavor-network-communities') . '</th>';
        echo '<th>' . esc_html__('Estado', 'flavor-network-communities') . '</th>';
        echo '<th>' . esc_html__('Último éxito', 'flavor-network-communities') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($nodes as $node) {
            echo '<tr>';
            echo '<td><code>' . esc_html($node->url) . '</code></td>';
            echo '<td>' . esc_html($node->name ?: '-') . '</td>';
            echo '<td>';
            if ($node->is_enabled) {
                echo '<span class="status-badge status-ok">Activo</span>';
            } else {
                echo '<span class="status-badge status-error">Inactivo</span>';
            }
            if ($node->failures_count > 0) {
                echo ' <span class="status-badge status-warning">' . esc_html($node->failures_count) . ' fallos</span>';
            }
            echo '</td>';
            echo '<td>';
            if ($node->last_success) {
                echo esc_html(human_time_diff(strtotime($node->last_success)) . ' ago');
            } else {
                echo '-';
            }
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * AJAX: Obtener estado actualizado
     */
    public function ajax_get_status() {
        check_ajax_referer('flavor_mesh_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $mesh_loader = class_exists('Flavor_Mesh_Loader') ? Flavor_Mesh_Loader::instance() : null;
        $status = $mesh_loader ? $mesh_loader->get_status() : ['initialized' => false];

        wp_send_json_success($status);
    }

    /**
     * AJAX: Disparar descubrimiento de peers
     */
    public function ajax_trigger_discovery() {
        check_ajax_referer('flavor_mesh_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        if (!class_exists('Flavor_Peer_Discovery')) {
            wp_send_json_error(['message' => 'Peer Discovery no disponible']);
        }

        $discovery = Flavor_Peer_Discovery::instance();
        $result = $discovery->discover_from_bootstrap();

        wp_send_json_success([
            'message'        => 'Descubrimiento completado',
            'peers_found'    => $result['peers_found'] ?? 0,
            'peers_new'      => $result['peers_new'] ?? 0,
        ]);
    }

    /**
     * AJAX: Añadir nodo bootstrap
     */
    public function ajax_add_bootstrap() {
        check_ajax_referer('flavor_mesh_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';

        if (empty($url)) {
            wp_send_json_error(['message' => 'URL requerida']);
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_network_';

        $result = $wpdb->insert(
            $prefix . 'bootstrap_nodes',
            [
                'url'        => $url,
                'name'       => $name,
                'is_enabled' => 1,
                'priority'   => 100,
            ],
            ['%s', '%s', '%d', '%d']
        );

        if ($result === false) {
            wp_send_json_error(['message' => 'Error al guardar']);
        }

        wp_send_json_success(['message' => 'Bootstrap añadido correctamente']);
    }
}

// Inicializar en admin
if (is_admin()) {
    add_action('plugins_loaded', function() {
        Flavor_Mesh_Admin::instance();
    });
}
