<?php
/**
 * Script para limpiar OPcache
 * Acceder vía: /wp-content/plugins/flavor-chat-ia/clear-opcache.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Clear OPcache</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        .success { background: #4CAF50; color: white; padding: 15px; border-radius: 5px; }
        .error { background: #f44336; color: white; padding: 15px; border-radius: 5px; }
        pre { background: white; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>Limpiar OPcache de PHP</h2>

    <?php
    if (function_exists('opcache_reset')) {
        $resultado = opcache_reset();
        if ($resultado) {
            echo '<div class="success">✓ OPcache limpiado exitosamente</div>';
        } else {
            echo '<div class="error">✗ Error al limpiar OPcache</div>';
        }

        echo '<h3>Estado de OPcache:</h3>';
        echo '<pre>';
        $status = opcache_get_status();
        print_r($status);
        echo '</pre>';
    } else {
        echo '<div class="error">✗ OPcache no está habilitado en este servidor</div>';
    }
    ?>

    <p><a href="<?php echo admin_url('admin.php?page=flavor-app-composer'); ?>">← Volver al App Composer</a></p>
</body>
</html>
