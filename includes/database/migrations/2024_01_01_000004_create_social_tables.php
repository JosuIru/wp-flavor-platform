<?php
/**
 * Migration: Crear tablas sociales
 *
 * Tablas para: comunidades, colectivos, foros, red social
 *
 * @package FlavorPlatform
 * @subpackage Database\Migrations
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Migration_2024_01_01_000004_Create_Social_Tables extends Flavor_Migration_Base {

    protected $migration_name = 'create_social_tables';
    protected $description = 'Crear tablas sociales (comunidades, colectivos, foros, red social)';

    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'flavor_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ─── Comunidades ───
        $sql = "CREATE TABLE {$prefix}comunidades (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            logo_url varchar(500) DEFAULT '',
            banner_url varchar(500) DEFAULT '',
            tipo varchar(50) DEFAULT 'publica',
            categoria varchar(100) DEFAULT '',
            ubicacion varchar(255) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            configuracion longtext,
            miembros_count int(11) DEFAULT 0,
            creador_id bigint(20) unsigned NOT NULL,
            estado varchar(20) DEFAULT 'activa',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY creador_id (creador_id),
            KEY estado (estado),
            KEY tipo (tipo)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Miembros de comunidades ───
        $sql = "CREATE TABLE {$prefix}comunidad_miembros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            rol varchar(50) DEFAULT 'miembro',
            estado varchar(20) DEFAULT 'activo',
            fecha_ingreso datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY comunidad_user (comunidad_id, user_id),
            KEY user_id (user_id)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Colectivos ───
        $sql = "CREATE TABLE {$prefix}colectivos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            imagen_url varchar(500) DEFAULT '',
            tipo varchar(50) DEFAULT 'abierto',
            categoria varchar(100) DEFAULT '',
            tags text,
            configuracion longtext,
            miembros_count int(11) DEFAULT 0,
            creador_id bigint(20) unsigned NOT NULL,
            estado varchar(20) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY creador_id (creador_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Miembros de colectivos ───
        $sql = "CREATE TABLE {$prefix}colectivo_miembros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            colectivo_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            rol varchar(50) DEFAULT 'miembro',
            estado varchar(20) DEFAULT 'activo',
            fecha_ingreso datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY colectivo_user (colectivo_id, user_id),
            KEY user_id (user_id)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Foros ───
        $sql = "CREATE TABLE {$prefix}foros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            icono varchar(50) DEFAULT 'comments',
            parent_id bigint(20) unsigned DEFAULT NULL,
            orden int(11) DEFAULT 0,
            es_privado tinyint(1) DEFAULT 0,
            estado varchar(20) DEFAULT 'activo',
            hilos_count int(11) DEFAULT 0,
            mensajes_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY parent_id (parent_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Hilos de foro ───
        $sql = "CREATE TABLE {$prefix}foro_hilos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            foro_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            contenido longtext,
            autor_id bigint(20) unsigned NOT NULL,
            es_fijado tinyint(1) DEFAULT 0,
            es_cerrado tinyint(1) DEFAULT 0,
            vistas int(11) DEFAULT 0,
            respuestas_count int(11) DEFAULT 0,
            ultimo_mensaje_id bigint(20) unsigned DEFAULT NULL,
            ultimo_mensaje_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY foro_id (foro_id),
            KEY autor_id (autor_id),
            KEY es_fijado (es_fijado),
            KEY ultimo_mensaje_at (ultimo_mensaje_at)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Respuestas de foro ───
        $sql = "CREATE TABLE {$prefix}foro_respuestas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            hilo_id bigint(20) unsigned NOT NULL,
            contenido longtext NOT NULL,
            autor_id bigint(20) unsigned NOT NULL,
            parent_id bigint(20) unsigned DEFAULT NULL,
            es_solucion tinyint(1) DEFAULT 0,
            votos int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY hilo_id (hilo_id),
            KEY autor_id (autor_id),
            KEY parent_id (parent_id)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Publicaciones red social ───
        $sql = "CREATE TABLE {$prefix}publicaciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            autor_id bigint(20) unsigned NOT NULL,
            contenido longtext,
            tipo varchar(50) DEFAULT 'texto',
            media_urls longtext,
            visibilidad varchar(20) DEFAULT 'publica',
            comunidad_id bigint(20) unsigned DEFAULT NULL,
            colectivo_id bigint(20) unsigned DEFAULT NULL,
            likes_count int(11) DEFAULT 0,
            comentarios_count int(11) DEFAULT 0,
            compartidos_count int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'publicado',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY autor_id (autor_id),
            KEY comunidad_id (comunidad_id),
            KEY colectivo_id (colectivo_id),
            KEY estado (estado),
            KEY created_at (created_at)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Comentarios ───
        $sql = "CREATE TABLE {$prefix}comentarios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            publicacion_id bigint(20) unsigned NOT NULL,
            autor_id bigint(20) unsigned NOT NULL,
            contenido text NOT NULL,
            parent_id bigint(20) unsigned DEFAULT NULL,
            likes_count int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'aprobado',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY publicacion_id (publicacion_id),
            KEY autor_id (autor_id),
            KEY parent_id (parent_id)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Likes ───
        $sql = "CREATE TABLE {$prefix}likes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            objeto_tipo varchar(50) NOT NULL,
            objeto_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_objeto (user_id, objeto_tipo, objeto_id),
            KEY objeto (objeto_tipo, objeto_id)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Seguidores ───
        $sql = "CREATE TABLE {$prefix}seguidores (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            seguidor_id bigint(20) unsigned NOT NULL,
            seguido_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY seguidor_seguido (seguidor_id, seguido_id),
            KEY seguido_id (seguido_id)
        ) {$charset_collate};";
        dbDelta($sql);

        return true;
    }

    public function down() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tables = [
            'seguidores',
            'likes',
            'comentarios',
            'publicaciones',
            'foro_respuestas',
            'foro_hilos',
            'foros',
            'colectivo_miembros',
            'colectivos',
            'comunidad_miembros',
            'comunidades',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table}");
        }

        return true;
    }
}
