<?php
/**
 * Restaurar app_profile a "personalizado"
 */
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('No tienes permisos');
}

$config = get_option('flavor_chat_ia_settings', []);
$config['app_profile'] = 'personalizado';
update_option('flavor_chat_ia_settings', $config);
wp_cache_delete('alloptions', 'options');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Profile</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        .success { background: #4CAF50; color: white; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="success">
        <h3>✓ app_profile restaurado a "personalizado"</h3>
        <p>Ahora puedes probar activar una plantilla.</p>
    </div>
    <p><a href="<?php echo admin_url('admin.php?page=flavor-app-composer'); ?>">Ir al App Composer</a></p>
</body>
</html>
