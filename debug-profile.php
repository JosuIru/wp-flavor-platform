<?php
/**
 * Script temporal de debug para verificar app_profile
 *
 * Acceder vía: /wp-content/plugins/flavor-chat-ia/debug-profile.php
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Obtener la configuración
$configuracion = get_option('flavor_chat_ia_settings', []);

// Mostrar resultados
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug App Profile</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        pre { background: white; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <h2>Debug: Configuración flavor_chat_ia_settings</h2>

    <h3>app_profile:</h3>
    <pre><?php echo esc_html($configuracion['app_profile'] ?? 'NO EXISTE'); ?></pre>

    <h3>active_modules:</h3>
    <pre><?php print_r($configuracion['active_modules'] ?? []); ?></pre>

    <h3>Configuración completa:</h3>
    <pre><?php print_r($configuracion); ?></pre>
</body>
</html>
