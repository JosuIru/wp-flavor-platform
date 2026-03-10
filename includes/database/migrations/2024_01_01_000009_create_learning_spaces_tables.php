<?php
/**
 * Migration: Crear tablas de formación y espacios
 *
 * Tablas para: cursos, talleres, espacios comunes, biblioteca
 *
 * @package FlavorPlatform
 * @subpackage Database\Migrations
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Migration_2024_01_01_000009_Create_Learning_Spaces_Tables extends Flavor_Migration_Base {

    protected $migration_name = 'create_learning_spaces_tables';
    protected $description = 'Crear tablas de formación y espacios (cursos, talleres, espacios, biblioteca)';

    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'flavor_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ─── Cursos ───
        $sql = "CREATE TABLE {$prefix}cursos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion longtext,
            imagen_url varchar(500) DEFAULT '',
            categoria varchar(100) DEFAULT '',
            nivel varchar(50) DEFAULT 'basico',
            duracion_horas int(11) DEFAULT NULL,
            modalidad varchar(50) DEFAULT 'presencial',
            precio decimal(10,2) DEFAULT 0.00,
            plazas_max int(11) DEFAULT NULL,
            inscritos_count int(11) DEFAULT 0,
            fecha_inicio date DEFAULT NULL,
            fecha_fin date DEFAULT NULL,
            horario text,
            ubicacion varchar(255) DEFAULT '',
            instructor_id bigint(20) unsigned NOT NULL,
            comunidad_id bigint(20) unsigned DEFAULT NULL,
            requisitos text,
            programa longtext,
            estado varchar(20) DEFAULT 'borrador',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY instructor_id (instructor_id),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Inscripciones a cursos ───
        $sql = "CREATE TABLE {$prefix}curso_inscripciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            curso_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            estado varchar(20) DEFAULT 'inscrito',
            progreso decimal(5,2) DEFAULT 0.00,
            nota_final decimal(4,2) DEFAULT NULL,
            certificado_url varchar(500) DEFAULT '',
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY curso_usuario (curso_id, usuario_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Talleres ───
        $sql = "CREATE TABLE {$prefix}talleres (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            imagen_url varchar(500) DEFAULT '',
            categoria varchar(100) DEFAULT '',
            fecha datetime NOT NULL,
            duracion_minutos int(11) DEFAULT 120,
            ubicacion varchar(255) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            precio decimal(10,2) DEFAULT 0.00,
            plazas_max int(11) DEFAULT NULL,
            inscritos_count int(11) DEFAULT 0,
            materiales_necesarios text,
            facilitador_id bigint(20) unsigned NOT NULL,
            comunidad_id bigint(20) unsigned DEFAULT NULL,
            estado varchar(20) DEFAULT 'publicado',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY facilitador_id (facilitador_id),
            KEY fecha (fecha),
            KEY categoria (categoria),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Inscripciones a talleres ───
        $sql = "CREATE TABLE {$prefix}taller_inscripciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            taller_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            estado varchar(20) DEFAULT 'confirmada',
            asistio tinyint(1) DEFAULT NULL,
            valoracion tinyint(1) DEFAULT NULL,
            comentario text,
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY taller_usuario (taller_id, usuario_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Espacios comunes ───
        $sql = "CREATE TABLE {$prefix}espacios_comunes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            imagen_url varchar(500) DEFAULT '',
            tipo varchar(50) DEFAULT 'sala',
            ubicacion varchar(255) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            capacidad int(11) DEFAULT NULL,
            superficie_m2 decimal(8,2) DEFAULT NULL,
            equipamiento text,
            servicios text,
            horario_disponible longtext,
            precio_hora decimal(10,2) DEFAULT 0.00,
            requiere_aprobacion tinyint(1) DEFAULT 0,
            normas_uso longtext,
            responsable_id bigint(20) unsigned DEFAULT NULL,
            comunidad_id bigint(20) unsigned DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY tipo (tipo),
            KEY responsable_id (responsable_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Cesiones de espacios ───
        $sql = "CREATE TABLE {$prefix}espacio_cesiones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            espacio_id bigint(20) unsigned NOT NULL,
            solicitante_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime NOT NULL,
            num_asistentes int(11) DEFAULT NULL,
            estado varchar(20) DEFAULT 'pendiente',
            aprobado_por bigint(20) unsigned DEFAULT NULL,
            motivo_rechazo text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY espacio_id (espacio_id),
            KEY solicitante_id (solicitante_id),
            KEY fecha_inicio (fecha_inicio),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Biblioteca: Recursos ───
        $sql = "CREATE TABLE {$prefix}biblioteca_recursos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            autor varchar(255) DEFAULT '',
            descripcion text,
            imagen_url varchar(500) DEFAULT '',
            tipo varchar(50) DEFAULT 'libro',
            categoria varchar(100) DEFAULT '',
            isbn varchar(20) DEFAULT '',
            editorial varchar(100) DEFAULT '',
            ano_publicacion int(4) DEFAULT NULL,
            idioma varchar(10) DEFAULT 'es',
            num_paginas int(11) DEFAULT NULL,
            ubicacion_fisica varchar(100) DEFAULT '',
            archivo_digital_url varchar(500) DEFAULT '',
            disponibles int(11) DEFAULT 1,
            prestados int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'disponible',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY tipo (tipo),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY isbn (isbn)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Biblioteca: Préstamos ───
        $sql = "CREATE TABLE {$prefix}biblioteca_prestamos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            recurso_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            fecha_prestamo datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_devolucion_prevista datetime NOT NULL,
            fecha_devolucion_real datetime DEFAULT NULL,
            renovaciones int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'activo',
            notas text,
            PRIMARY KEY (id),
            KEY recurso_id (recurso_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY fecha_devolucion_prevista (fecha_devolucion_prevista)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Biblioteca: Reservas ───
        $sql = "CREATE TABLE {$prefix}biblioteca_reservas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            recurso_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            fecha_reserva datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion datetime NOT NULL,
            estado varchar(20) DEFAULT 'activa',
            PRIMARY KEY (id),
            KEY recurso_id (recurso_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        return true;
    }

    public function down() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tables = [
            'biblioteca_reservas',
            'biblioteca_prestamos',
            'biblioteca_recursos',
            'espacio_cesiones',
            'espacios_comunes',
            'taller_inscripciones',
            'talleres',
            'curso_inscripciones',
            'cursos',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table}");
        }

        return true;
    }
}
