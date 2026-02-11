<?php
/**
 * Script para limpiar OPcache
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/limpiar-cache.php
 */

// Limpiar OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo '<p style="color: green;">✓ OPcache limpiado</p>';
} else {
    echo '<p style="color: orange;">⚠ OPcache no está disponible</p>';
}

// Limpiar cache de WordPress Object Cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo '<p style="color: green;">✓ WordPress Object Cache limpiado</p>';
}

// Verificar que el método is_active existe en reciclaje
$archivo = __DIR__ . '/includes/modules/reciclaje/class-reciclaje-module.php';
if (file_exists($archivo)) {
    $contenido = file_get_contents($archivo);
    if (strpos($contenido, 'function is_active()') !== false) {
        echo '<p style="color: green;">✓ Método is_active() existe en class-reciclaje-module.php</p>';
    } else {
        echo '<p style="color: red;">✗ Método is_active() NO encontrado en class-reciclaje-module.php</p>';
    }
}

echo '<hr>';
echo '<p><a href="' . $_SERVER['HTTP_REFERER'] . '">← Volver</a></p>';
echo '<p><a href="/">Ir al home</a></p>';
