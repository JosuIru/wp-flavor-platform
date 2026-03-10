<?php
/**
 * Migration: Crear tablas de medios y administración
 *
 * Tablas para: radio, podcast, multimedia, trámites, incidencias, avisos
 *
 * @package FlavorPlatform
 * @subpackage Database\Migrations
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Migration_2024_01_01_000008_Create_Media_Admin_Tables extends Flavor_Migration_Base {

    protected $migration_name = 'create_media_admin_tables';
    protected $description = 'Crear tablas de medios y administración (radio, podcast, trámites, incidencias)';

    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'flavor_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ─── Programas de radio ───
        $sql = "CREATE TABLE {$prefix}radio_programas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            imagen_url varchar(500) DEFAULT '',
            categoria varchar(100) DEFAULT '',
            conductor_id bigint(20) unsigned DEFAULT NULL,
            dia_emision varchar(20) DEFAULT '',
            hora_inicio time DEFAULT NULL,
            hora_fin time DEFAULT NULL,
            es_activo tinyint(1) DEFAULT 1,
            podcast_feed_url varchar(500) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY conductor_id (conductor_id),
            KEY es_activo (es_activo)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Episodios de radio/podcast ───
        $sql = "CREATE TABLE {$prefix}radio_episodios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            programa_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            imagen_url varchar(500) DEFAULT '',
            audio_url varchar(500) NOT NULL,
            duracion_segundos int(11) DEFAULT 0,
            fecha_emision datetime NOT NULL,
            reproducciones int(11) DEFAULT 0,
            es_destacado tinyint(1) DEFAULT 0,
            estado varchar(20) DEFAULT 'publicado',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY programa_id (programa_id),
            KEY fecha_emision (fecha_emision),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Podcasts ───
        $sql = "CREATE TABLE {$prefix}podcasts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            imagen_url varchar(500) DEFAULT '',
            categoria varchar(100) DEFAULT '',
            autor_id bigint(20) unsigned NOT NULL,
            feed_url varchar(500) DEFAULT '',
            itunes_url varchar(500) DEFAULT '',
            spotify_url varchar(500) DEFAULT '',
            suscriptores_count int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY autor_id (autor_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Episodios de podcast ───
        $sql = "CREATE TABLE {$prefix}podcast_episodios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            podcast_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion longtext,
            imagen_url varchar(500) DEFAULT '',
            audio_url varchar(500) NOT NULL,
            duracion_segundos int(11) DEFAULT 0,
            temporada int(11) DEFAULT 1,
            numero_episodio int(11) DEFAULT 1,
            fecha_publicacion datetime NOT NULL,
            reproducciones int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'publicado',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY podcast_id (podcast_id),
            KEY fecha_publicacion (fecha_publicacion),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Multimedia: Galerías ───
        $sql = "CREATE TABLE {$prefix}galerias (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            imagen_portada_url varchar(500) DEFAULT '',
            tipo varchar(50) DEFAULT 'imagenes',
            autor_id bigint(20) unsigned NOT NULL,
            comunidad_id bigint(20) unsigned DEFAULT NULL,
            items_count int(11) DEFAULT 0,
            visibilidad varchar(20) DEFAULT 'publica',
            estado varchar(20) DEFAULT 'activa',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY autor_id (autor_id),
            KEY tipo (tipo),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Multimedia: Items ───
        $sql = "CREATE TABLE {$prefix}galeria_items (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            galeria_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) DEFAULT '',
            descripcion text,
            archivo_url varchar(500) NOT NULL,
            thumbnail_url varchar(500) DEFAULT '',
            tipo varchar(50) DEFAULT 'imagen',
            mime_type varchar(100) DEFAULT '',
            tamano_bytes bigint(20) DEFAULT 0,
            orden int(11) DEFAULT 0,
            metadata longtext,
            vistas int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY galeria_id (galeria_id),
            KEY tipo (tipo),
            KEY orden (orden)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Trámites: Catálogo ───
        $sql = "CREATE TABLE {$prefix}tramites_catalogo (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            categoria varchar(100) DEFAULT '',
            requisitos longtext,
            documentacion_requerida longtext,
            formulario_config longtext,
            plazo_dias int(11) DEFAULT NULL,
            coste decimal(10,2) DEFAULT 0.00,
            responsable_id bigint(20) unsigned DEFAULT NULL,
            es_online tinyint(1) DEFAULT 1,
            estado varchar(20) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY categoria (categoria),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Trámites: Expedientes ───
        $sql = "CREATE TABLE {$prefix}tramites_expedientes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tramite_id bigint(20) unsigned NOT NULL,
            numero_expediente varchar(50) NOT NULL,
            solicitante_id bigint(20) unsigned NOT NULL,
            datos_formulario longtext,
            documentos longtext,
            estado varchar(50) DEFAULT 'iniciado',
            asignado_a bigint(20) unsigned DEFAULT NULL,
            fecha_limite datetime DEFAULT NULL,
            resolucion text,
            fecha_resolucion datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY numero_expediente (numero_expediente),
            KEY tramite_id (tramite_id),
            KEY solicitante_id (solicitante_id),
            KEY estado (estado),
            KEY asignado_a (asignado_a)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Trámites: Historial ───
        $sql = "CREATE TABLE {$prefix}tramites_historial (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            expediente_id bigint(20) unsigned NOT NULL,
            accion varchar(100) NOT NULL,
            estado_anterior varchar(50) DEFAULT NULL,
            estado_nuevo varchar(50) DEFAULT NULL,
            comentario text,
            usuario_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY expediente_id (expediente_id),
            KEY usuario_id (usuario_id)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Incidencias ───
        $sql = "CREATE TABLE {$prefix}incidencias (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(100) DEFAULT '',
            ubicacion varchar(255) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            imagenes longtext,
            prioridad varchar(20) DEFAULT 'media',
            reportador_id bigint(20) unsigned NOT NULL,
            asignado_a bigint(20) unsigned DEFAULT NULL,
            estado varchar(20) DEFAULT 'abierta',
            fecha_resolucion datetime DEFAULT NULL,
            resolucion text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY reportador_id (reportador_id),
            KEY asignado_a (asignado_a),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY prioridad (prioridad)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Avisos municipales ───
        $sql = "CREATE TABLE {$prefix}avisos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            contenido longtext,
            tipo varchar(50) DEFAULT 'informativo',
            categoria varchar(100) DEFAULT '',
            imagen_url varchar(500) DEFAULT '',
            fecha_inicio datetime DEFAULT NULL,
            fecha_fin datetime DEFAULT NULL,
            es_destacado tinyint(1) DEFAULT 0,
            es_urgente tinyint(1) DEFAULT 0,
            autor_id bigint(20) unsigned NOT NULL,
            comunidad_id bigint(20) unsigned DEFAULT NULL,
            vistas int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'publicado',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY autor_id (autor_id),
            KEY tipo (tipo),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio),
            KEY es_destacado (es_destacado)
        ) {$charset_collate};";
        dbDelta($sql);

        return true;
    }

    public function down() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tables = [
            'avisos',
            'incidencias',
            'tramites_historial',
            'tramites_expedientes',
            'tramites_catalogo',
            'galeria_items',
            'galerias',
            'podcast_episodios',
            'podcasts',
            'radio_episodios',
            'radio_programas',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table}");
        }

        return true;
    }
}
