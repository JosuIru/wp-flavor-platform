<?php
/**
 * Addon Name: Flavor Network Communities
 * Description: Sistema de red multi-sitio para conectar comunidades, compartir contenido, eventos, colaboraciones y catálogo global. Ideal para redes de municipios, franquicias o federaciones.
 * Version: 1.0.0
 * Author: Gailu Labs
 * Author URI: https://gailu.net
 * Requires: Flavor Chat IA 3.0.0+
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Constantes del addon
if (!defined('FLAVOR_NETWORK_VERSION')) {
    define('FLAVOR_NETWORK_VERSION', '1.5.0');
}
if (!defined('FLAVOR_NETWORK_PATH')) {
    define('FLAVOR_NETWORK_PATH', plugin_dir_path(__FILE__));
}
if (!defined('FLAVOR_NETWORK_URL')) {
    define('FLAVOR_NETWORK_URL', plugin_dir_url(__FILE__));
}

/**
 * Registrar el addon con Flavor Platform
 */
add_action('flavor_register_addons', 'flavor_network_communities_register');

function flavor_network_communities_register() {
    if (!class_exists('Flavor_Addon_Manager')) {
        return;
    }

    Flavor_Addon_Manager::register_addon('network-communities', [
        'name' => __('Network Communities', 'flavor-network-communities'),
        'version' => FLAVOR_NETWORK_VERSION,
        'description' => __('Sistema de red multi-sitio para conectar comunidades y compartir recursos globales.', 'flavor-network-communities'),
        'author' => 'Gailu Labs',
        'author_uri' => 'https://gailu.net',
        'icon' => 'dashicons-networking',
        'file' => __FILE__,
        'requires_core' => '3.0.0',
        'requires' => [
            'required' => [
                'php' => '7.2',
                'wordpress' => '5.8',
                'php_extension:curl' => true,
                'php_extension:json' => true,
                'php_extension:sodium' => true, // Requerido para P2P/Mesh (Ed25519, CRDT)
            ],
            'optional' => [
                'module:eventos' => [
                    'name' => 'Módulo de Eventos',
                    'feature' => 'Eventos compartidos en la red'
                ],
                'module:marketplace' => [
                    'name' => 'Módulo de Marketplace',
                    'feature' => 'Catálogo global de productos'
                ],
            ]
        ],
        'init_callback' => 'flavor_network_communities_init',
        'settings_page' => 'admin.php?page=flavor-network',
        'documentation_url' => 'https://gailu.net/docs/network-communities',
        'is_premium' => false,
    ]);
}

/**
 * Inicialización del addon
 *
 * NOTA: Las clases de Network ahora están integradas en el core de Flavor Platform.
 * Este addon ahora solo añade funcionalidades extra o extensiones específicas.
 * Las clases base (Flavor_Network_Manager, Flavor_Network_Admin, etc.) se cargan desde includes/network/
 */
function flavor_network_communities_init() {
    // Las clases de Network ahora se cargan desde el core en includes/network/
    // Solo verificamos que estén disponibles y añadimos extensiones si es necesario

    // Si las clases del core no están disponibles (instalación antigua), cargar las del addon
    if (!class_exists('Flavor_Network_Installer')) {
        require_once FLAVOR_NETWORK_PATH . 'includes/class-network-installer.php';
    }
    if (!class_exists('Flavor_Network_Node')) {
        require_once FLAVOR_NETWORK_PATH . 'includes/class-network-node.php';
    }
    if (!class_exists('Flavor_Network_API')) {
        require_once FLAVOR_NETWORK_PATH . 'includes/class-network-api.php';
    }
    if (!class_exists('Flavor_Network_Manager')) {
        require_once FLAVOR_NETWORK_PATH . 'includes/class-network-manager.php';
    }

    // Cargar admin solo en backend si no existe
    if (is_admin() && !class_exists('Flavor_Network_Admin')) {
        require_once FLAVOR_NETWORK_PATH . 'includes/class-network-admin.php';
    }

    // Cargar Rate Limiter
    if (!class_exists('Flavor_Network_Rate_Limiter')) {
        require_once FLAVOR_NETWORK_PATH . 'includes/class-network-rate-limiter.php';
    }

    // Cargar Webhooks Manager
    if (!class_exists('Flavor_Network_Webhooks')) {
        $webhooks_file = FLAVOR_NETWORK_PATH . 'includes/class-network-webhooks.php';
        if (file_exists($webhooks_file)) {
            require_once $webhooks_file;
        }
    }

    // Cargar Network Cleanup (gestión del ciclo de vida de nodos)
    if (!class_exists('Flavor_Network_Cleanup')) {
        $cleanup_file = FLAVOR_NETWORK_PATH . 'includes/class-network-cleanup.php';
        if (file_exists($cleanup_file)) {
            require_once $cleanup_file;
        }
    }

    // Crear tablas si no existen
    if (class_exists('Flavor_Network_Installer')) {
        Flavor_Network_Installer::create_tables();
    }

    // Inicializar componentes principales (el singleton evita duplicados)
    if (class_exists('Flavor_Network_Manager')) {
        Flavor_Network_Manager::get_instance();
    }

    // Inicializar REST API (el singleton evita duplicados)
    if (class_exists('Flavor_Network_API')) {
        Flavor_Network_API::get_instance();
    }

    if (is_admin() && class_exists('Flavor_Network_Admin')) {
        Flavor_Network_Admin::get_instance();
    }

    // Inicializar Network Cleanup (cron de limpieza)
    if (class_exists('Flavor_Network_Cleanup')) {
        Flavor_Network_Cleanup::get_instance();
    }

    // ═══════════════════════════════════════════════════════════════════
    // SISTEMA P2P/MESH (v1.5.0)
    // Gossip Protocol, CRDTs, Peer Discovery, Mesh Topology
    // ═══════════════════════════════════════════════════════════════════
    $mesh_loader_file = FLAVOR_NETWORK_PATH . 'includes/mesh/class-mesh-loader.php';
    if (file_exists($mesh_loader_file)) {
        require_once $mesh_loader_file;

        // Inicializar el sistema mesh
        if (class_exists('Flavor_Mesh_Loader')) {
            add_action('init', function() {
                Flavor_Mesh_Loader::instance()->init();
            }, 20); // Prioridad 20 para cargar después de otros componentes
        }
    }

    // Log de inicialización en modo debug
    if (defined('FLAVOR_PLATFORM_DEBUG') && FLAVOR_PLATFORM_DEBUG) {
        flavor_log_debug( 'Addon Network Communities inicializado v' . FLAVOR_NETWORK_VERSION, 'NetworkCommunities' );
    }
}

/**
 * Carga de traducciones
 */
add_action('init', function() {
    load_plugin_textdomain(
        'flavor-network-communities',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});

/**
 * Limpieza al desactivar el addon
 */
register_deactivation_hook(__FILE__, 'flavor_network_communities_deactivate');

function flavor_network_communities_deactivate() {
    // Limpiar cron jobs de limpieza
    if (class_exists('Flavor_Network_Cleanup')) {
        Flavor_Network_Cleanup::deactivate();
    }

    // Limpiar cron jobs del sistema P2P/Mesh
    wp_clear_scheduled_hook('flavor_mesh_gossip_batch');
    wp_clear_scheduled_hook('flavor_mesh_heartbeat');
    wp_clear_scheduled_hook('flavor_mesh_cleanup_expired');
    wp_clear_scheduled_hook('flavor_mesh_peer_discovery');
}
