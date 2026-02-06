<?php
/**
 * Test directo para verificar guardado de app_profile
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar permisos
if (!current_user_can('manage_options')) {
    die('No tienes permisos');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Direct Save</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        .box { background: white; padding: 15px; border: 1px solid #ccc; border-radius: 5px; margin: 10px 0; }
        .success { background: #4CAF50; color: white; }
        .error { background: #f44336; color: white; }
        pre { overflow-x: auto; }
    </style>
</head>
<body>
    <h2>Test: Guardar app_profile directamente</h2>

    <?php
    // Leer configuración actual
    $config_antes = get_option('flavor_chat_ia_settings', []);
    echo '<div class="box">';
    echo '<h3>Configuración ANTES:</h3>';
    echo '<pre>app_profile: ' . ($config_antes['app_profile'] ?? 'NO EXISTE') . '</pre>';
    echo '</div>';

    // Intentar guardar app_profile = "test_direct"
    $config_antes['app_profile'] = 'test_direct_' . time();
    $resultado = update_option('flavor_chat_ia_settings', $config_antes);

    echo '<div class="box ' . ($resultado ? 'success' : 'error') . '">';
    echo '<h3>Resultado de update_option:</h3>';
    echo '<p>' . ($resultado ? '✓ TRUE - Guardado exitoso' : '✗ FALSE - No se guardó (valor idéntico)') . '</p>';
    echo '</div>';

    // Limpiar cache
    wp_cache_delete('alloptions', 'options');

    // Re-leer
    $config_despues = get_option('flavor_chat_ia_settings', []);
    echo '<div class="box">';
    echo '<h3>Configuración DESPUÉS:</h3>';
    echo '<pre>app_profile: ' . ($config_despues['app_profile'] ?? 'NO EXISTE') . '</pre>';
    echo '</div>';

    // Verificar si se guardó
    if (($config_despues['app_profile'] ?? '') === $config_antes['app_profile']) {
        echo '<div class="box success">';
        echo '<h3>✓ ÉXITO</h3>';
        echo '<p>El valor se guardó correctamente en la base de datos.</p>';
        echo '</div>';
    } else {
        echo '<div class="box error">';
        echo '<h3>✗ PROBLEMA</h3>';
        echo '<p>El valor NO se guardó o fue sobrescrito.</p>';
        echo '</div>';
    }
    ?>

    <p><a href="debug-profile.php">Ver configuración completa</a></p>
</body>
</html>
