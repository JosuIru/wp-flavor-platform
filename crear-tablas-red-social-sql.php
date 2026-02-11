<?php
/**
 * Script para crear tablas de red social con SQL directo
 * Acceder vía: http://localhost:10028/wp-content/plugins/flavor-chat-ia/crear-tablas-red-social-sql.php
 */

// Cargar WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para acceder a esta página.');
}

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Crear Tablas Red Social</title></head><body>';
echo '<h1>Crear Tablas Red Social - SQL Directo</h1>';
echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #ccc;">';

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$tablas = [
    'flavor_social_perfiles' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}flavor_social_perfiles (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        nombre_completo varchar(255) DEFAULT NULL,
        bio text DEFAULT NULL,
        ubicacion varchar(255) DEFAULT NULL,
        sitio_web varchar(255) DEFAULT NULL,
        fecha_nacimiento date DEFAULT NULL,
        cover_url varchar(500) DEFAULT NULL,
        es_verificado tinyint(1) DEFAULT 0,
        es_privado tinyint(1) DEFAULT 0,
        total_publicaciones int(11) DEFAULT 0,
        total_seguidores int(11) DEFAULT 0,
        total_siguiendo int(11) DEFAULT 0,
        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY usuario_id (usuario_id)
    ) {$charset_collate};",

    'flavor_social_publicaciones' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}flavor_social_publicaciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        autor_id bigint(20) unsigned NOT NULL,
        contenido text NOT NULL,
        tipo enum('texto','imagen','video','enlace','evento','compartido') DEFAULT 'texto',
        adjuntos longtext DEFAULT NULL,
        visibilidad enum('publica','comunidad','seguidores','privada') DEFAULT 'comunidad',
        ubicacion varchar(255) DEFAULT NULL,
        estado enum('borrador','publicado','moderacion','oculto','eliminado') DEFAULT 'publicado',
        publicacion_original_id bigint(20) unsigned DEFAULT NULL,
        es_fijado tinyint(1) DEFAULT 0,
        me_gusta int(11) DEFAULT 0,
        comentarios int(11) DEFAULT 0,
        compartidos int(11) DEFAULT 0,
        vistas int(11) DEFAULT 0,
        fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY autor_id (autor_id),
        KEY estado (estado),
        KEY fecha_publicacion (fecha_publicacion)
    ) {$charset_collate};",

    'flavor_social_comentarios' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}flavor_social_comentarios (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        publicacion_id bigint(20) unsigned NOT NULL,
        autor_id bigint(20) unsigned NOT NULL,
        comentario_padre_id bigint(20) unsigned DEFAULT NULL,
        contenido text NOT NULL,
        me_gusta int(11) DEFAULT 0,
        estado enum('publicado','moderacion','oculto','eliminado') DEFAULT 'publicado',
        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY publicacion_id (publicacion_id),
        KEY autor_id (autor_id)
    ) {$charset_collate};",

    'flavor_social_reacciones' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}flavor_social_reacciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        publicacion_id bigint(20) unsigned DEFAULT NULL,
        comentario_id bigint(20) unsigned DEFAULT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        tipo enum('me_gusta','me_encanta','me_divierte','me_entristece','me_enfada') DEFAULT 'me_gusta',
        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY publicacion_usuario (publicacion_id, usuario_id),
        KEY usuario_id (usuario_id)
    ) {$charset_collate};",

    'flavor_social_seguimientos' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}flavor_social_seguimientos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        seguidor_id bigint(20) unsigned NOT NULL,
        seguido_id bigint(20) unsigned NOT NULL,
        notificaciones_activas tinyint(1) DEFAULT 1,
        fecha_seguimiento datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY seguidor_seguido (seguidor_id, seguido_id),
        KEY seguido_id (seguido_id)
    ) {$charset_collate};",

    'flavor_social_hashtags' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}flavor_social_hashtags (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        hashtag varchar(100) NOT NULL,
        total_usos int(11) DEFAULT 0,
        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
        fecha_ultimo_uso datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY hashtag (hashtag)
    ) {$charset_collate};",

    'flavor_social_hashtags_posts' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}flavor_social_hashtags_posts (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        hashtag_id bigint(20) unsigned NOT NULL,
        publicacion_id bigint(20) unsigned NOT NULL,
        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY hashtag_publicacion (hashtag_id, publicacion_id)
    ) {$charset_collate};",

    'flavor_social_historias' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}flavor_social_historias (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        autor_id bigint(20) unsigned NOT NULL,
        tipo enum('imagen','video','texto') DEFAULT 'imagen',
        contenido_url varchar(500) DEFAULT NULL,
        texto text DEFAULT NULL,
        color_fondo varchar(20) DEFAULT NULL,
        vistas int(11) DEFAULT 0,
        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
        fecha_expiracion datetime NOT NULL,
        PRIMARY KEY (id),
        KEY autor_id (autor_id)
    ) {$charset_collate};",

    'flavor_social_notificaciones' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}flavor_social_notificaciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        actor_id bigint(20) unsigned NOT NULL,
        tipo enum('like','comentario','seguidor','mencion','compartido','historia') NOT NULL,
        referencia_id bigint(20) unsigned DEFAULT NULL,
        referencia_tipo varchar(50) DEFAULT NULL,
        mensaje text DEFAULT NULL,
        leida tinyint(1) DEFAULT 0,
        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY usuario_id (usuario_id),
        KEY leida (leida)
    ) {$charset_collate};",

    'flavor_social_guardados' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}flavor_social_guardados (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        publicacion_id bigint(20) unsigned NOT NULL,
        fecha_guardado datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY usuario_publicacion (usuario_id, publicacion_id)
    ) {$charset_collate};"
];

$creadas = 0;
$errores = [];

foreach ($tablas as $nombre => $sql) {
    echo "<h3>Creando: {$nombre}</h3>";

    $resultado = $wpdb->query($sql);

    if ($resultado !== false) {
        echo "<p style='color: green;'>✓ Tabla creada: {$nombre}</p>";
        $creadas++;

        // Verificar
        if (Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . $nombre)) {
            echo "<p style='color: green;'>  ✓ Verificada</p>";
        } else {
            echo "<p style='color: red;'>  ✗ No se puede verificar</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Error: " . $wpdb->last_error . "</p>";
        $errores[] = $nombre . ': ' . $wpdb->last_error;
    }
}

echo '</div>';

echo '<h2 style="margin: 20px;">Resumen</h2>';
echo '<div style="background: ' . ($creadas == count($tablas) ? '#d4edda' : '#f8d7da') . '; padding: 20px; margin: 20px; border: 1px solid ' . ($creadas == count($tablas) ? '#c3e6cb' : '#f5c6cb') . '; border-radius: 4px;">';
echo "<p style='font-size: 18px; margin: 0;'><strong>Tablas creadas: {$creadas} de " . count($tablas) . "</strong></p>";

if (!empty($errores)) {
    echo '<h3 style="color: #721c24;">Errores:</h3>';
    echo '<ul>';
    foreach ($errores as $error) {
        echo "<li style='color: #721c24;'>" . esc_html($error) . "</li>";
    }
    echo '</ul>';
}
echo '</div>';

if ($creadas == count($tablas)) {
    echo '<p style="margin: 20px;"><a href="' . plugins_url('popular-red-social-directo.php', __FILE__) . '" class="button button-primary button-large">Siguiente: Popular Datos →</a></p>';
}

echo '<p style="margin: 20px;"><a href="' . admin_url('admin.php?page=flavor-app-composer') . '" class="button">Volver al Compositor</a></p>';

echo '</body></html>';
