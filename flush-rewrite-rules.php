<?php
/**
 * Script temporal para forzar flush de rewrite rules
 * Ejecutar una sola vez y luego eliminar
 */

// Cargar WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Verificar que sea admin
if (!current_user_can('manage_options')) {
    die('No tienes permisos para ejecutar este script');
}

echo '<h1>Flush Rewrite Rules</h1>';

// Establecer flag para que se ejecute en el próximo load
update_option('flavor_landing_flush_rewrite_rules', true);

echo '<p>✅ Flag establecido. Ahora recarga cualquier página del admin...</p>';
echo '<p><a href="' . admin_url('edit.php?post_type=flavor_landing') . '">Ir a Landing Pages</a></p>';

// O forzar flush directo aquí
flush_rewrite_rules();

echo '<p>✅ Rewrite rules actualizadas directamente.</p>';
echo '<p><strong>Ya puedes acceder a tus landing pages.</strong></p>';
echo '<p><a href="' . admin_url('edit.php?post_type=flavor_landing') . '">Ver Landing Pages</a></p>';
