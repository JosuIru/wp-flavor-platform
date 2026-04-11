<?php
/**
 * Script de verificación del sistema P2P/Mesh
 *
 * Ejecutar desde WP-CLI (prueba completa):
 * wp eval-file wp-content/plugins/flavor-platform/addons/flavor-network-communities/tools/test-mesh-system.php
 *
 * O directamente con PHP (modo standalone - solo CRDTs y crypto):
 * php tools/test-mesh-system.php --standalone
 *
 * @package FlavorPlatform\Network\Mesh
 * @since 1.5.0
 */

// Detectar modo de ejecución
$standalone_mode = in_array('--standalone', $argv ?? []);
$wp_loaded = false;

if (!defined('ABSPATH')) {
    // Intentar cargar WordPress
    $wp_load = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/wp-load.php';
    if (file_exists($wp_load) && !$standalone_mode) {
        require_once $wp_load;
        $wp_loaded = true;
    } else {
        // Modo completamente standalone
        define('ABSPATH', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/');
        define('FLAVOR_NETWORK_PATH', dirname(__DIR__) . '/');
        $standalone_mode = true;
    }
} else {
    $wp_loaded = true;
}

echo "═══════════════════════════════════════════════════════════════════\n";
echo "  VERIFICACIÓN DEL SISTEMA P2P/MESH - Flavor Network Communities   \n";
echo "═══════════════════════════════════════════════════════════════════\n";

if ($standalone_mode) {
    echo "  ⚠️  MODO STANDALONE (solo CRDTs y criptografía)                  \n";
}
echo "\n";

$tests_passed = 0;
$tests_failed = 0;

function test_result($name, $passed, $details = '') {
    global $tests_passed, $tests_failed;
    if ($passed) {
        echo "✅ {$name}\n";
        if ($details) echo "   {$details}\n";
        $tests_passed++;
    } else {
        echo "❌ {$name}\n";
        if ($details) echo "   {$details}\n";
        $tests_failed++;
    }
}

// ═══════════════════════════════════════════════════════════════════
// 1. VERIFICAR REQUISITOS
// ═══════════════════════════════════════════════════════════════════
echo "▶ VERIFICANDO REQUISITOS...\n";
echo "-------------------------------------------------------------------\n";

test_result(
    'PHP 7.2+',
    version_compare(PHP_VERSION, '7.2.0', '>='),
    'Versión: ' . PHP_VERSION
);

test_result(
    'Extension sodium',
    extension_loaded('sodium'),
    extension_loaded('sodium') ? 'Cargada correctamente' : 'NO DISPONIBLE'
);

test_result(
    'Función sodium_crypto_sign_keypair',
    function_exists('sodium_crypto_sign_keypair'),
    ''
);

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// 2. VERIFICAR TABLAS DE BD (solo si WordPress está cargado)
// ═══════════════════════════════════════════════════════════════════
if ($wp_loaded) {
    echo "▶ VERIFICANDO TABLAS DE BASE DE DATOS...\n";
    echo "-------------------------------------------------------------------\n";

    global $wpdb;
    $prefix = $wpdb->prefix . 'flavor_network_';

    $required_tables = [
        'peers'            => 'Peers (identidades criptográficas)',
        'mesh_connections' => 'Conexiones mesh bidireccionales',
        'gossip_messages'  => 'Cola de mensajes gossip',
        'vector_clocks'    => 'Vector clocks',
        'crdt_state'       => 'Estados CRDT',
        'bootstrap_nodes'  => 'Nodos bootstrap',
        'sync_log'         => 'Log de sincronización',
    ];

    foreach ($required_tables as $table => $description) {
        $full_name = $prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_name}'") === $full_name;
        test_result("Tabla {$table}", $exists, $description);
    }

    echo "\n";
} else {
    echo "▶ TABLAS DE BD: Omitido (modo standalone)\n\n";
}

// ═══════════════════════════════════════════════════════════════════
// 3. VERIFICAR CLASES
// ═══════════════════════════════════════════════════════════════════
echo "▶ VERIFICANDO CLASES...\n";
echo "-------------------------------------------------------------------\n";

// En modo standalone, cargar clases manualmente
if ($standalone_mode) {
    $crdt_dir = FLAVOR_NETWORK_PATH . 'includes/crdt/';
    $mesh_dir = FLAVOR_NETWORK_PATH . 'includes/mesh/';

    $crdt_files = [
        'class-vector-clock.php',
        'class-lww-register.php',
        'class-or-set.php',
        'class-g-counter.php',
        'class-pn-counter.php',
        'class-crdt-manager.php',
    ];

    foreach ($crdt_files as $file) {
        $path = $crdt_dir . $file;
        if (file_exists($path)) {
            require_once $path;
        }
    }
} else {
    // Cargar el sistema mesh si no está cargado
    $mesh_loader = FLAVOR_NETWORK_PATH . 'includes/mesh/class-mesh-loader.php';
    if (file_exists($mesh_loader) && !class_exists('Flavor_Mesh_Loader')) {
        require_once $mesh_loader;
        Flavor_Mesh_Loader::instance()->init();
    }
}

$required_classes = [
    'Flavor_Vector_Clock'       => 'CRDT - Vector Clock',
    'Flavor_LWW_Register'       => 'CRDT - LWW Register',
    'Flavor_OR_Set'             => 'CRDT - OR Set',
    'Flavor_G_Counter'          => 'CRDT - G Counter',
    'Flavor_PN_Counter'         => 'CRDT - PN Counter',
    'Flavor_CRDT_Manager'       => 'CRDT Manager',
];

// Clases mesh solo se verifican si WordPress está cargado
if (!$standalone_mode) {
    $required_classes = array_merge($required_classes, [
        'Flavor_Gossip_Protocol'    => 'Gossip Protocol',
        'Flavor_Mesh_Topology'      => 'Mesh Topology',
        'Flavor_Peer_Discovery'     => 'Peer Discovery',
        'Flavor_Mesh_API'           => 'Mesh REST API',
        'Flavor_Network_Peer'       => 'Modelo Peer',
        'Flavor_Mesh_Node_Bridge'   => 'Bridge Node <-> Peer',
        'Flavor_Mesh_Loader'        => 'Mesh Loader',
    ]);
}

foreach ($required_classes as $class => $description) {
    test_result("Clase {$class}", class_exists($class), $description);
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// 4. VERIFICAR PEER LOCAL (solo con WordPress)
// ═══════════════════════════════════════════════════════════════════
if ($wp_loaded) {
    echo "▶ VERIFICANDO PEER LOCAL...\n";
    echo "-------------------------------------------------------------------\n";

    $local_peer = null;
    if (class_exists('Flavor_Network_Installer')) {
        $local_peer = Flavor_Network_Installer::get_local_peer();
    }

    test_result(
        'Peer local existe',
        $local_peer !== null,
        $local_peer ? "peer_id: " . substr($local_peer->peer_id, 0, 16) . '...' : 'No encontrado'
    );

    if ($local_peer) {
        test_result(
            'Peer local tiene clave pública',
            !empty($local_peer->public_key_ed25519),
            ''
        );

        test_result(
            'Peer local tiene clave privada cifrada',
            !empty($local_peer->private_key_encrypted),
            ''
        );
    }

    echo "\n";
} else {
    echo "▶ PEER LOCAL: Omitido (modo standalone)\n\n";
}

// ═══════════════════════════════════════════════════════════════════
// 5. TEST DE CRDT
// ═══════════════════════════════════════════════════════════════════
echo "▶ PROBANDO CRDTs...\n";
echo "-------------------------------------------------------------------\n";

// Test Vector Clock
if (class_exists('Flavor_Vector_Clock')) {
    $clock1 = new Flavor_Vector_Clock(['peer_a' => 1, 'peer_b' => 2]);
    $clock2 = new Flavor_Vector_Clock(['peer_a' => 2, 'peer_b' => 1]);
    $merged = $clock1->merge($clock2);

    test_result(
        'Vector Clock merge',
        $merged->get('peer_a') === 2 && $merged->get('peer_b') === 2,
        "Merged: {$merged}"
    );

    test_result(
        'Vector Clock concurrent detection',
        $clock1->is_concurrent($clock2),
        'Detecta correctamente eventos concurrentes'
    );
}

// Test LWW Register
if (class_exists('Flavor_LWW_Register')) {
    $reg1 = new Flavor_LWW_Register('valor1', 1000, 'peer_a');
    $reg2 = new Flavor_LWW_Register('valor2', 2000, 'peer_b');
    $merged_reg = $reg1->merge($reg2);

    test_result(
        'LWW Register merge',
        $merged_reg->get() === 'valor2',
        'El valor más reciente gana'
    );
}

// Test OR-Set
if (class_exists('Flavor_OR_Set')) {
    $set1 = new Flavor_OR_Set();
    $set1->add('item1', 'peer_a');
    $set1->add('item2', 'peer_a');

    $set2 = new Flavor_OR_Set();
    $set2->add('item2', 'peer_b');
    $set2->add('item3', 'peer_b');

    $merged_set = $set1->merge($set2);

    test_result(
        'OR-Set merge',
        count($merged_set->values()) === 3,
        "Elementos: " . implode(', ', $merged_set->values())
    );
}

// Test G-Counter
if (class_exists('Flavor_G_Counter')) {
    $counter1 = new Flavor_G_Counter(['peer_a' => 5]);
    $counter2 = new Flavor_G_Counter(['peer_a' => 3, 'peer_b' => 7]);
    $merged_counter = $counter1->merge($counter2);

    test_result(
        'G-Counter merge',
        $merged_counter->value() === 12, // max(5,3) + 7
        "Valor: {$merged_counter->value()}"
    );
}

// Test PN-Counter
if (class_exists('Flavor_PN_Counter')) {
    $pn = new Flavor_PN_Counter();
    $pn->increment('peer_a', 10);
    $pn->decrement('peer_a', 3);

    test_result(
        'PN-Counter operaciones',
        $pn->value() === 7,
        "Valor: {$pn->value()}"
    );
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// 6. TEST DE FIRMA Ed25519
// ═══════════════════════════════════════════════════════════════════
echo "▶ PROBANDO FIRMAS Ed25519...\n";
echo "-------------------------------------------------------------------\n";

if (extension_loaded('sodium')) {
    // Generar keypair de prueba
    $keypair = sodium_crypto_sign_keypair();
    $public_key = sodium_crypto_sign_publickey($keypair);
    $private_key = sodium_crypto_sign_secretkey($keypair);

    // Firmar mensaje
    $message = 'Test message ' . time();
    $signature = sodium_crypto_sign_detached($message, $private_key);

    test_result(
        'Generación de keypair Ed25519',
        strlen($public_key) === 32 && strlen($private_key) === 64,
        'Public: 32 bytes, Private: 64 bytes'
    );

    test_result(
        'Firma de mensaje',
        strlen($signature) === 64,
        'Signature: 64 bytes'
    );

    // Verificar firma
    $verified = sodium_crypto_sign_verify_detached($signature, $message, $public_key);
    test_result(
        'Verificación de firma válida',
        $verified === true,
        'Firma verificada correctamente'
    );

    // Verificar que firma inválida se rechaza
    $tampered_message = 'Tampered message';
    $verify_tampered = sodium_crypto_sign_verify_detached($signature, $tampered_message, $public_key);
    test_result(
        'Rechazo de firma para mensaje alterado',
        $verify_tampered === false,
        'Firma inválida rechazada correctamente'
    );
} else {
    echo "   ⚠️ Extension sodium no disponible - omitiendo tests de firma\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// 7. VERIFICAR REST API (solo con WordPress)
// ═══════════════════════════════════════════════════════════════════
if ($wp_loaded && function_exists('rest_get_server')) {
    echo "▶ VERIFICANDO REST API...\n";
    echo "-------------------------------------------------------------------\n";

    $rest_server = rest_get_server();
    $routes = $rest_server->get_routes();

    $mesh_routes = [
        '/flavor-mesh/v1/health',
        '/flavor-mesh/v1/peers/list',
        '/flavor-mesh/v1/gossip/receive',
        '/flavor-mesh/v1/mesh/connect',
    ];

    foreach ($mesh_routes as $route) {
        $exists = isset($routes[$route]);
        test_result("Ruta {$route}", $exists, '');
    }

    echo "\n";
} else {
    echo "▶ REST API: Omitido (modo standalone)\n\n";
}

// ═══════════════════════════════════════════════════════════════════
// 8. ESTADÍSTICAS (solo con WordPress)
// ═══════════════════════════════════════════════════════════════════
if ($wp_loaded) {
    echo "▶ ESTADÍSTICAS DEL SISTEMA...\n";
    echo "-------------------------------------------------------------------\n";

    if (class_exists('Flavor_Mesh_Topology')) {
        $topology = Flavor_Mesh_Topology::instance();
        $stats = $topology->get_topology_stats();

        echo "   Total peers: {$stats['total_peers']}\n";
        echo "   Peers online: {$stats['online_peers']}\n";
        echo "   Conexiones activas: {$stats['total_connections']}\n";
        echo "   Grado promedio: {$stats['average_degree']}\n";
        echo "   Conectividad: {$stats['connectivity']}%\n";
    }

    if (class_exists('Flavor_Gossip_Protocol')) {
        $gossip = Flavor_Gossip_Protocol::instance();
        $gossip_stats = $gossip->get_stats();

        echo "   Mensajes gossip: {$gossip_stats['total_messages']}\n";
        echo "   Pendientes de forward: {$gossip_stats['pending_forward']}\n";
    }

    echo "\n";
} else {
    echo "▶ ESTADÍSTICAS: Omitido (modo standalone)\n\n";
}

// ═══════════════════════════════════════════════════════════════════
// RESUMEN
// ═══════════════════════════════════════════════════════════════════
echo "═══════════════════════════════════════════════════════════════════\n";
echo "                         RESUMEN                                    \n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   Tests pasados: {$tests_passed}\n";
echo "   Tests fallidos: {$tests_failed}\n";
echo "   Total: " . ($tests_passed + $tests_failed) . "\n";

if ($standalone_mode) {
    echo "   Modo: STANDALONE (sin WordPress/BD)\n";
}

echo "═══════════════════════════════════════════════════════════════════\n";

if ($tests_failed === 0) {
    echo "\n✅ TODOS LOS TESTS PASARON - Sistema P2P/Mesh operativo\n\n";
    exit(0);
} else {
    echo "\n⚠️ HAY TESTS FALLIDOS - Revisar configuración\n\n";
    exit(1);
}
