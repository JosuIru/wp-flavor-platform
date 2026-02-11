<?php
/**
 * Script para verificar datos reales en las tablas
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/verificar-datos-reales.php
 */

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Verificación Datos</title>';
echo '<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f0f1; padding: 20px; }
.container { max-width: 1400px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; }
h1 { color: #1d2327; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 10px; text-align: left; border: 1px solid #ddd; font-size: 13px; }
th { background: #f6f7f7; font-weight: 600; }
.ok { background: #d4edda; }
.error { background: #f8d7da; }
.warning { background: #fff3cd; }
pre { background: #f6f7f7; padding: 10px; overflow-x: auto; font-size: 11px; }
</style></head><body>';

echo '<div class="container">';
echo '<h1>🔍 Verificación de Datos Reales</h1>';

global $wpdb;

// Verificaciones detalladas
$tablas = [
    'Socios (CPT)' => [
        'query' => "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'socio' AND post_status = 'publish'",
        'query_demo' => "SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'socio' AND pm.meta_key = '_flavor_demo_data' AND pm.meta_value = '1'",
        'sample' => "SELECT p.ID, p.post_title, pm.meta_value as es_demo FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_flavor_demo_data' WHERE p.post_type = 'socio' LIMIT 5"
    ],
    'Empleados (tabla)' => [
        'query' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_empleados",
        'query_demo' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_empleados WHERE es_demo = 1",
        'sample' => "SELECT * FROM {$wpdb->prefix}flavor_empleados LIMIT 5"
    ],
    'Eventos (CPT)' => [
        'query' => "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'evento' AND post_status = 'publish'",
        'query_demo' => "SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'evento' AND pm.meta_key = '_flavor_demo_data' AND pm.meta_value = '1'",
        'sample' => "SELECT p.ID, p.post_title FROM {$wpdb->posts} p WHERE p.post_type = 'evento' LIMIT 5"
    ],
    'Facturas (CPT)' => [
        'query' => "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'factura' AND post_status = 'publish'",
        'query_demo' => "SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'factura' AND pm.meta_key = '_flavor_demo_data' AND pm.meta_value = '1'",
        'sample' => "SELECT p.ID, p.post_title FROM {$wpdb->posts} p WHERE p.post_type = 'factura' LIMIT 5"
    ],
    'Red Social Perfiles' => [
        'query' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_social_perfiles",
        'query_demo' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_social_perfiles",
        'sample' => "SELECT * FROM {$wpdb->prefix}flavor_social_perfiles LIMIT 5"
    ],
    'Red Social Publicaciones' => [
        'query' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_social_publicaciones",
        'query_demo' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_social_publicaciones",
        'sample' => "SELECT id, autor_id, LEFT(contenido, 50) as contenido FROM {$wpdb->prefix}flavor_social_publicaciones LIMIT 5"
    ],
    'Marketplace Productos' => [
        'query' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_marketplace_productos",
        'query_demo' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_marketplace_productos WHERE es_demo = 1",
        'sample' => "SELECT * FROM {$wpdb->prefix}flavor_marketplace_productos LIMIT 5"
    ],
    'Reservas (CPT)' => [
        'query' => "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'reserva' AND post_status = 'publish'",
        'query_demo' => "SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'reserva' AND pm.meta_key = '_flavor_demo_data' AND pm.meta_value = '1'",
        'sample' => "SELECT p.ID, p.post_title FROM {$wpdb->posts} p WHERE p.post_type = 'reserva' LIMIT 5"
    ],
    'Incidencias (CPT)' => [
        'query' => "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'incidencia' AND post_status = 'publish'",
        'query_demo' => "SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'incidencia' AND pm.meta_key = '_flavor_demo_data' AND pm.meta_value = '1'",
        'sample' => "SELECT p.ID, p.post_title FROM {$wpdb->posts} p WHERE p.post_type = 'incidencia' LIMIT 5"
    ],
    'Talleres (CPT)' => [
        'query' => "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'taller' AND post_status = 'publish'",
        'query_demo' => "SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'taller' AND pm.meta_key = '_flavor_demo_data' AND pm.meta_value = '1'",
        'sample' => "SELECT p.ID, p.post_title FROM {$wpdb->posts} p WHERE p.post_type = 'taller' LIMIT 5"
    ],
    'Banco Tiempo Servicios' => [
        'query' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_banco_tiempo_servicios",
        'query_demo' => "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_banco_tiempo_servicios WHERE es_demo = 1",
        'sample' => "SELECT * FROM {$wpdb->prefix}flavor_banco_tiempo_servicios LIMIT 5"
    ],
];

echo '<h2>📊 Conteo de Registros</h2>';
echo '<table>';
echo '<tr><th>Tabla/Módulo</th><th>Total</th><th>Demo</th><th>Estado</th></tr>';

foreach ($tablas as $nombre => $queries) {
    echo '<tr>';
    echo "<td><strong>{$nombre}</strong></td>";

    // Total
    $total = $wpdb->get_var($queries['query']);
    $total = $total !== null ? $total : '❌ Error';
    echo "<td>{$total}</td>";

    // Demo
    $demo = $wpdb->get_var($queries['query_demo']);
    $demo = $demo !== null ? $demo : '❌ Error';
    echo "<td>{$demo}</td>";

    // Estado
    $clase = '';
    if ($total === '❌ Error') {
        $clase = 'error';
        echo "<td class='{$clase}'>Tabla no existe</td>";
    } elseif ($total > 0) {
        $clase = 'ok';
        echo "<td class='{$clase}'>✓ Con datos</td>";
    } else {
        $clase = 'warning';
        echo "<td class='{$clase}'>⚠ Vacía</td>";
    }

    echo '</tr>';
}

echo '</table>';

// Mostrar ejemplos de registros
echo '<h2>📝 Ejemplos de Registros</h2>';

foreach ($tablas as $nombre => $queries) {
    $resultados = $wpdb->get_results($queries['sample'], ARRAY_A);

    if (!empty($resultados)) {
        echo "<h3>{$nombre}</h3>";
        echo '<pre>';
        print_r($resultados);
        echo '</pre>';
    }
}

// Verificar usuarios demo
echo '<h2>👥 Usuarios Demo</h2>';
$usuarios_demo = get_users([
    'meta_key' => '_flavor_demo_data',
    'meta_value' => '1',
]);

echo '<p>Total: ' . count($usuarios_demo) . '</p>';
echo '<table>';
echo '<tr><th>ID</th><th>Nombre</th><th>Email</th></tr>';
foreach ($usuarios_demo as $user) {
    echo "<tr>";
    echo "<td>{$user->ID}</td>";
    echo "<td>{$user->display_name}</td>";
    echo "<td>{$user->user_email}</td>";
    echo "</tr>";
}
echo '</table>';

// Verificar IDs demo marcados
echo '<h2>🏷️ IDs Demo Marcados en Options</h2>';
$demo_ids = get_option('flavor_demo_data_ids', []);
echo '<pre>';
print_r($demo_ids);
echo '</pre>';

echo '</div>';
echo '</body></html>';
