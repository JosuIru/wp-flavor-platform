<?php
/**
 * Script de prueba para verificar la detección de basabere-campamentos
 *
 * Ejecutar desde la raíz de WordPress:
 * php wp-content/plugins/flavor-chat-ia/test-discovery-basabere.php
 *
 * @package FlavorChatIA
 */

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  TEST: Detección de basabere-campamentos en Discovery       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Verificar que las clases necesarias existan
if (!class_exists('Flavor_Plugin_Detector')) {
    echo "❌ ERROR: Clase Flavor_Plugin_Detector no encontrada\n";
    exit(1);
}

if (!class_exists('Flavor_App_Integration')) {
    echo "❌ ERROR: Clase Flavor_App_Integration no encontrada\n";
    exit(1);
}

echo "✓ Clases necesarias cargadas\n\n";

// Obtener instancias
$detector = new Flavor_Plugin_Detector();
$integration = Flavor_App_Integration::get_instance();

echo "═══════════════════════════════════════════════════════════════\n";
echo "  1. VERIFICACIÓN DE PLUGINS ACTIVOS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Verificar wp-calendario-experiencias
$calendario_active = $detector->is_calendario_active();
echo ($calendario_active ? "✅" : "❌") . " wp-calendario-experiencias: " . ($calendario_active ? "ACTIVO" : "INACTIVO") . "\n";

// Verificar basabere-campamentos
$basabere_active = $detector->is_basabere_active();
echo ($basabere_active ? "✅" : "❌") . " basabere-campamentos: " . ($basabere_active ? "ACTIVO" : "INACTIVO") . "\n";

// Verificar Flavor Chat IA
$flavor_active = $detector->is_flavor_chat_active();
echo ($flavor_active ? "✅" : "✅") . " Flavor Chat IA: " . ($flavor_active ? "ACTIVO" : "ACTIVO (siempre)") . "\n\n";

if (!$basabere_active) {
    echo "⚠️  ADVERTENCIA: basabere-campamentos no está activo\n";
    echo "    Verifica que el plugin esté instalado y activado\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "  2. SISTEMAS DETECTADOS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$systems = $detector->detect_active_systems();
echo "Total de sistemas detectados: " . count($systems) . "\n\n";

foreach ($systems as $system) {
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ Sistema: " . str_pad($system['name'], 48) . "│\n";
    echo "├─────────────────────────────────────────────────────────────┤\n";
    echo "│ ID: " . str_pad($system['id'], 52) . "│\n";
    echo "│ Versión: " . str_pad($system['version'], 48) . "│\n";
    echo "│ API Namespace: " . str_pad($system['api_namespace'], 41) . "│\n";
    echo "│ Features: " . str_pad(count($system['features']), 46) . "│\n";
    echo "│ Endpoints: " . str_pad(count($system['endpoints']), 45) . "│\n";
    echo "└─────────────────────────────────────────────────────────────┘\n\n";
}

// Buscar específicamente basabere-campamentos
$basabere_found = false;
$basabere_data = null;

foreach ($systems as $system) {
    if ($system['id'] === 'basabere-campamentos') {
        $basabere_found = true;
        $basabere_data = $system;
        break;
    }
}

if ($basabere_found) {
    echo "✅ basabere-campamentos DETECTADO en active_systems\n\n";

    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  3. DETALLES DE BASABERE-CAMPAMENTOS\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    echo "📦 Información básica:\n";
    echo "   - ID: " . $basabere_data['id'] . "\n";
    echo "   - Nombre: " . $basabere_data['name'] . "\n";
    echo "   - Versión: " . $basabere_data['version'] . "\n";
    echo "   - API Namespace: " . $basabere_data['api_namespace'] . "\n\n";

    echo "🔧 Features detectadas (" . count($basabere_data['features']) . "):\n";
    foreach ($basabere_data['features'] as $feature) {
        echo "   ✓ " . $feature . "\n";
    }
    echo "\n";

    echo "🌐 Endpoints disponibles (" . count($basabere_data['endpoints']) . "):\n";
    $public_endpoints = 0;
    $admin_endpoints = 0;

    foreach ($basabere_data['endpoints'] as $key => $endpoint) {
        if (strpos($key, 'admin_') === 0) {
            $admin_endpoints++;
        } else {
            $public_endpoints++;
        }
        echo "   • " . str_pad($key, 30) . " → " . $endpoint . "\n";
    }
    echo "\n";
    echo "   📊 Públicos: $public_endpoints | Admin: $admin_endpoints\n\n";

} else {
    echo "❌ basabere-campamentos NO DETECTADO en active_systems\n\n";

    if ($basabere_active) {
        echo "⚠️  El plugin está activo pero no fue detectado por detect_active_systems()\n";
        echo "   Esto indica un problema en la integración.\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "  4. CAPACIDADES COMBINADAS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$capabilities = $detector->get_combined_capabilities();
echo "Total de capacidades disponibles: " . count($capabilities) . "\n\n";

// Verificar capacidades de campamentos
$campamentos_capabilities = array_filter($capabilities, function($cap) {
    return strpos($cap, 'campamento') !== false || $cap === 'inscripciones';
});

if (count($campamentos_capabilities) > 0) {
    echo "✅ Capacidades de campamentos encontradas:\n";
    foreach ($campamentos_capabilities as $cap) {
        echo "   ✓ " . $cap . "\n";
    }
} else {
    echo "❌ No se encontraron capacidades de campamentos\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  5. SIMULACIÓN DE DISCOVERY API\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Simular petición al discovery endpoint
$request = new WP_REST_Request('GET', '/app-discovery/v1/info');
$request->set_param('refresh', '1'); // Forzar refresh de caché

$response = $integration->get_system_info($request);
$response_data = $response->get_data();

echo "📡 Respuesta del endpoint /app-discovery/v1/info:\n\n";
echo "   - WordPress URL: " . $response_data['wordpress_url'] . "\n";
echo "   - Site Name: " . $response_data['site_name'] . "\n";
echo "   - Sistemas activos: " . count($response_data['active_systems']) . "\n";
echo "   - Unified API: " . ($response_data['unified_api'] ? 'Sí' : 'No') . "\n";
echo "   - API Version: " . $response_data['api_version'] . "\n\n";

// Verificar si basabere está en la respuesta API
$basabere_in_response = false;
foreach ($response_data['active_systems'] as $sys) {
    if ($sys['id'] === 'basabere-campamentos') {
        $basabere_in_response = true;
        break;
    }
}

if ($basabere_in_response) {
    echo "✅ basabere-campamentos presente en la respuesta API\n";
} else {
    echo "❌ basabere-campamentos NO presente en la respuesta API\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  6. VERIFICACIÓN DE ENDPOINTS REST\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Verificar si el endpoint camps/v1/camps está registrado
$server = rest_get_server();
$routes = $server->get_routes();

$camps_endpoint_exists = isset($routes['/camps/v1/camps']);
$camps_admin_exists = isset($routes['/camps/v1/admin/camps']);

echo ($camps_endpoint_exists ? "✅" : "❌") . " Endpoint público: /camps/v1/camps\n";
echo ($camps_admin_exists ? "✅" : "❌") . " Endpoint admin: /camps/v1/admin/camps\n\n";

if ($camps_endpoint_exists) {
    echo "🎯 Puedes probar el endpoint con:\n";
    echo "   curl -X GET \"" . get_site_url() . "/wp-json/camps/v1/camps\"\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "  RESUMEN FINAL\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$tests_passed = 0;
$tests_total = 5;

// Test 1: Plugin activo
if ($basabere_active) {
    echo "✅ Test 1: Plugin basabere-campamentos está activo\n";
    $tests_passed++;
} else {
    echo "❌ Test 1: Plugin basabere-campamentos NO está activo\n";
}

// Test 2: Detectado en systems
if ($basabere_found) {
    echo "✅ Test 2: Detectado en active_systems\n";
    $tests_passed++;
} else {
    echo "❌ Test 2: NO detectado en active_systems\n";
}

// Test 3: Presente en API response
if ($basabere_in_response) {
    echo "✅ Test 3: Presente en respuesta API\n";
    $tests_passed++;
} else {
    echo "❌ Test 3: NO presente en respuesta API\n";
}

// Test 4: Endpoints REST registrados
if ($camps_endpoint_exists) {
    echo "✅ Test 4: Endpoints REST registrados\n";
    $tests_passed++;
} else {
    echo "❌ Test 4: Endpoints REST NO registrados\n";
}

// Test 5: Capacidades disponibles
if (count($campamentos_capabilities) > 0) {
    echo "✅ Test 5: Capacidades de campamentos disponibles\n";
    $tests_passed++;
} else {
    echo "❌ Test 5: Capacidades de campamentos NO disponibles\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  RESULTADO: $tests_passed/$tests_total tests pasados\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if ($tests_passed === $tests_total) {
    echo "🎉 ¡ÉXITO! La integración funciona correctamente.\n";
    echo "   Las apps móviles pueden detectar y usar basabere-campamentos.\n\n";
    exit(0);
} elseif ($tests_passed >= 3) {
    echo "⚠️  INTEGRACIÓN PARCIAL. Algunos tests fallaron.\n";
    echo "   Revisa los errores arriba para más detalles.\n\n";
    exit(1);
} else {
    echo "❌ INTEGRACIÓN FALLIDA. La mayoría de tests fallaron.\n";
    echo "   Verifica que basabere-campamentos esté instalado y activado.\n\n";
    exit(1);
}
