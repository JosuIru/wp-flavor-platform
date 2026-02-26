<?php
/**
 * Script de migración para añadir columna de cross-posting
 * Ejecutar una vez y luego eliminar
 *
 * Acceder vía: /wp-content/plugins/flavor-chat-ia/actualizar-comunidades-crosspost.php
 */

// Cargar WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Solo admins
if (!current_user_can('manage_options')) {
    wp_die('Acceso denegado');
}

global $wpdb;
$resultados = [];

// Verificar y añadir columna referencia_original a tabla de actividad
$tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';

if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_actividad'") === $tabla_actividad) {
    // Verificar si la columna existe
    $columnas = $wpdb->get_col("SHOW COLUMNS FROM $tabla_actividad");

    if (!in_array('referencia_original', $columnas)) {
        $wpdb->query("ALTER TABLE $tabla_actividad ADD COLUMN referencia_original bigint(20) unsigned DEFAULT NULL AFTER imagen");
        $wpdb->query("ALTER TABLE $tabla_actividad ADD INDEX idx_referencia (referencia_original)");
        $resultados[] = '✅ Columna referencia_original añadida para cross-posting';
    } else {
        $resultados[] = '⏭️ Columna referencia_original ya existe';
    }
} else {
    $resultados[] = '❌ Tabla de actividad no encontrada';
}

// Verificar que Network Content Bridge está activo
$tabla_shared = $wpdb->prefix . 'flavor_network_shared_content';
if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_shared'") !== $tabla_shared) {
    // Crear tabla de contenido compartido si no existe
    $charset_collate = $wpdb->get_charset_collate();
    $wpdb->query("CREATE TABLE IF NOT EXISTS $tabla_shared (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nodo_id bigint(20) unsigned NOT NULL,
        tipo_contenido varchar(50) NOT NULL,
        titulo varchar(255) NOT NULL,
        descripcion text,
        url_externa varchar(500),
        imagen_url varchar(500),
        visible_red tinyint(1) DEFAULT 1,
        nivel_visibilidad enum('privado','conectado','federado','publico') DEFAULT 'federado',
        referencia_local bigint(20) unsigned DEFAULT NULL,
        metadata longtext,
        estado enum('activo','oculto','eliminado') DEFAULT 'activo',
        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY nodo_id (nodo_id),
        KEY tipo_contenido (tipo_contenido),
        KEY visible_red (visible_red),
        KEY estado (estado)
    ) $charset_collate");
    $resultados[] = '✅ Tabla de contenido compartido creada';
} else {
    $resultados[] = '⏭️ Tabla de contenido compartido ya existe';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Migración Cross-Posting</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 40px; background: #f0f0f0; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1e3a5f; margin-bottom: 20px; }
        .resultado { padding: 10px 15px; margin: 8px 0; background: #f8f9fa; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .warning { background: #fff3cd; color: #856404; }
        .error { background: #f8d7da; color: #721c24; }
        .btn { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        .btn:hover { background: #2563eb; }
        .note { margin-top: 20px; padding: 15px; background: #fef3c7; border-radius: 4px; color: #92400e; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📤 Migración Cross-Posting Completada</h1>

        <?php foreach ($resultados as $resultado): ?>
        <div class="resultado <?php
            if (strpos($resultado, '✅') !== false) echo 'success';
            elseif (strpos($resultado, '⏭️') !== false) echo 'warning';
            else echo 'error';
        ?>">
            <?php echo $resultado; ?>
        </div>
        <?php endforeach; ?>

        <div class="note">
            <strong>⚠️ Importante:</strong> Elimina este archivo después de ejecutarlo por seguridad.
        </div>

        <a href="<?php echo admin_url('admin.php?page=flavor-modules'); ?>" class="btn">Ir al Panel de Módulos</a>
    </div>
</body>
</html>
<?php
// Auto-eliminarse (opcional, comentado por seguridad)
// unlink(__FILE__);
