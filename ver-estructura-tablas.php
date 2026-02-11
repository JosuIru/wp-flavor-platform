<?php
/**
 * Script para ver estructura real de tablas problemáticas
 */

require_once __DIR__ . '/../../../wp-load.php';

if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos');
}

global $wpdb;

$tablas_verificar = [
    'wp_flavor_gc_consumidores',
    'wp_flavor_propuestas',
    'wp_flavor_eventos',
    'wp_flavor_biblioteca_libros',
    'wp_flavor_bicicletas_estaciones',
];

echo "<pre>";
echo "ESTRUCTURA DE TABLAS PROBLEMÁTICAS\n";
echo "==================================\n\n";

foreach ($tablas_verificar as $tabla) {
    echo "\n--- $tabla ---\n";

    $columns = $wpdb->get_results("DESCRIBE $tabla");

    if ($columns) {
        foreach ($columns as $col) {
            echo sprintf("  %-25s %s\n", $col->Field, $col->Type);
        }
    } else {
        echo "  [Tabla no existe o error: {$wpdb->last_error}]\n";
    }
}

echo "</pre>";
