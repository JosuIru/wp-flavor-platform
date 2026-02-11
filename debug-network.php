<?php
/**
 * Debug script para Red de Comunidades
 */

require_once __DIR__ . '/../../../wp-load.php';

if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos');
}

global $wpdb;
$prefix = $wpdb->prefix . 'flavor_network_';

echo "<h1>Debug Red de Comunidades</h1>";

// 1. Verificar tablas
echo "<h2>1. Tablas existentes:</h2>";
$tablas = ['nodes', 'connections', 'shared_content', 'events', 'collaborations'];
foreach ($tablas as $tabla) {
    $tabla_completa = $prefix . $tabla;
    $existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_completa'") === $tabla_completa;
    $count = $existe ? $wpdb->get_var("SELECT COUNT(*) FROM $tabla_completa") : 0;
    echo "<p><strong>{$tabla}:</strong> " . ($existe ? "✓ Existe - {$count} registros" : "✗ No existe") . "</p>";
}

// 2. Verificar nodo local
echo "<h2>2. Nodo Local:</h2>";
$nodo_local = $wpdb->get_row("SELECT * FROM {$prefix}nodes WHERE es_nodo_local = 1");
if ($nodo_local) {
    echo "<p>✓ Nodo local configurado: <strong>{$nodo_local->nombre}</strong> (ID: {$nodo_local->id})</p>";
} else {
    echo "<p>✗ <strong style='color:red;'>NO HAY NODO LOCAL CONFIGURADO</strong></p>";
    echo "<p>Este es el problema. Necesitas ir a <a href='" . admin_url('admin.php?page=flavor-network&tab=mi-nodo') . "'>Mi Nodo</a> y guardar la configuración.</p>";
}

// 3. Ver contenidos
echo "<h2>3. Contenidos compartidos:</h2>";
$contenidos = $wpdb->get_results("SELECT id, titulo, tipo_contenido, estado, visible_red, nodo_id FROM {$prefix}shared_content LIMIT 10");
if ($contenidos) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Título</th><th>Tipo</th><th>Estado</th><th>Visible Red</th><th>Nodo ID</th></tr>";
    foreach ($contenidos as $c) {
        echo "<tr><td>{$c->id}</td><td>{$c->titulo}</td><td>{$c->tipo_contenido}</td><td>{$c->estado}</td><td>{$c->visible_red}</td><td>{$c->nodo_id}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay contenidos</p>";
}

// 4. Ver eventos
echo "<h2>4. Eventos:</h2>";
$eventos = $wpdb->get_results("SELECT id, titulo, tipo_evento, estado, visible_red, nodo_id FROM {$prefix}events LIMIT 10");
if ($eventos) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Título</th><th>Tipo</th><th>Estado</th><th>Visible Red</th><th>Nodo ID</th></tr>";
    foreach ($eventos as $e) {
        echo "<tr><td>{$e->id}</td><td>{$e->titulo}</td><td>{$e->tipo_evento}</td><td>{$e->estado}</td><td>{$e->visible_red}</td><td>{$e->nodo_id}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay eventos</p>";
}

// 5. Ver colaboraciones
echo "<h2>5. Colaboraciones:</h2>";
$colaboraciones = $wpdb->get_results("SELECT id, titulo, tipo_colaboracion, estado, visible_red, nodo_id FROM {$prefix}collaborations LIMIT 10");
if ($colaboraciones) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Título</th><th>Tipo</th><th>Estado</th><th>Visible Red</th><th>Nodo ID</th></tr>";
    foreach ($colaboraciones as $col) {
        echo "<tr><td>{$col->id}</td><td>{$col->titulo}</td><td>{$col->tipo_colaboracion}</td><td>{$col->estado}</td><td>{$col->visible_red}</td><td>{$col->nodo_id}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay colaboraciones</p>";
}

// 6. Test API REST
echo "<h2>6. Test API REST:</h2>";
$api_url = rest_url('flavor-network/v1/content');
echo "<p>URL API: <code>{$api_url}</code></p>";
echo "<p><a href='{$api_url}' target='_blank'>Abrir en nueva ventana</a></p>";

echo "<hr>";
echo "<p><a href='" . admin_url('admin.php?page=flavor-network') . "'>Volver a Red de Comunidades</a></p>";
