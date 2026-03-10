<?php
/**
 * Migration: Crear tablas de participación
 *
 * Tablas para: eventos, reservas, participación ciudadana, presupuestos participativos
 *
 * @package FlavorPlatform
 * @subpackage Database\Migrations
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Migration_2024_01_01_000006_Create_Participation_Tables extends Flavor_Migration_Base {

    protected $migration_name = 'create_participation_tables';
    protected $description = 'Crear tablas de participación (eventos, reservas, participación, presupuestos)';

    public function up() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'flavor_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ─── Eventos ───
        $sql = "CREATE TABLE {$prefix}eventos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion longtext,
            imagen_url varchar(500) DEFAULT '',
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime DEFAULT NULL,
            todo_el_dia tinyint(1) DEFAULT 0,
            ubicacion varchar(255) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            ubicacion_online varchar(500) DEFAULT '',
            tipo varchar(50) DEFAULT 'presencial',
            categoria varchar(100) DEFAULT '',
            precio decimal(10,2) DEFAULT 0.00,
            aforo_maximo int(11) DEFAULT NULL,
            inscritos_count int(11) DEFAULT 0,
            organizador_id bigint(20) unsigned NOT NULL,
            comunidad_id bigint(20) unsigned DEFAULT NULL,
            colectivo_id bigint(20) unsigned DEFAULT NULL,
            estado varchar(20) DEFAULT 'publicado',
            destacado tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY organizador_id (organizador_id),
            KEY fecha_inicio (fecha_inicio),
            KEY categoria (categoria),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Inscripciones a eventos ───
        $sql = "CREATE TABLE {$prefix}evento_inscripciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            estado varchar(20) DEFAULT 'confirmada',
            num_acompanantes int(11) DEFAULT 0,
            datos_adicionales longtext,
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_asistencia datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY evento_user (evento_id, user_id),
            KEY user_id (user_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Recursos reservables ───
        $sql = "CREATE TABLE {$prefix}recursos_reservables (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            imagen_url varchar(500) DEFAULT '',
            tipo varchar(50) DEFAULT 'espacio',
            categoria varchar(100) DEFAULT '',
            ubicacion varchar(255) DEFAULT '',
            capacidad int(11) DEFAULT NULL,
            equipamiento text,
            horario_disponible longtext,
            precio_hora decimal(10,2) DEFAULT 0.00,
            requiere_aprobacion tinyint(1) DEFAULT 0,
            responsable_id bigint(20) unsigned DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY tipo (tipo),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Reservas ───
        $sql = "CREATE TABLE {$prefix}reservas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            recurso_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) DEFAULT '',
            descripcion text,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime NOT NULL,
            num_asistentes int(11) DEFAULT 1,
            estado varchar(20) DEFAULT 'pendiente',
            motivo_rechazo text,
            aprobado_por bigint(20) unsigned DEFAULT NULL,
            fecha_aprobacion datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY recurso_id (recurso_id),
            KEY user_id (user_id),
            KEY fecha_inicio (fecha_inicio),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Propuestas ciudadanas ───
        $sql = "CREATE TABLE {$prefix}propuestas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion longtext,
            categoria varchar(100) DEFAULT '',
            ambito varchar(50) DEFAULT 'local',
            presupuesto_estimado decimal(12,2) DEFAULT NULL,
            imagen_url varchar(500) DEFAULT '',
            autor_id bigint(20) unsigned NOT NULL,
            comunidad_id bigint(20) unsigned DEFAULT NULL,
            votos_a_favor int(11) DEFAULT 0,
            votos_en_contra int(11) DEFAULT 0,
            apoyos_count int(11) DEFAULT 0,
            comentarios_count int(11) DEFAULT 0,
            fase varchar(50) DEFAULT 'recogida_apoyos',
            estado varchar(20) DEFAULT 'activa',
            fecha_limite datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY autor_id (autor_id),
            KEY categoria (categoria),
            KEY fase (fase),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Apoyos a propuestas ───
        $sql = "CREATE TABLE {$prefix}propuesta_apoyos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            propuesta_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            tipo varchar(20) DEFAULT 'apoyo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY propuesta_user (propuesta_id, user_id),
            KEY user_id (user_id)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Presupuestos participativos ───
        $sql = "CREATE TABLE {$prefix}presupuestos_participativos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion longtext,
            presupuesto_total decimal(12,2) NOT NULL,
            presupuesto_disponible decimal(12,2) NOT NULL,
            ano int(4) NOT NULL,
            fecha_inicio_propuestas date NOT NULL,
            fecha_fin_propuestas date NOT NULL,
            fecha_inicio_votacion date NOT NULL,
            fecha_fin_votacion date NOT NULL,
            ambito varchar(50) DEFAULT 'municipal',
            comunidad_id bigint(20) unsigned DEFAULT NULL,
            estado varchar(20) DEFAULT 'borrador',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY ano (ano),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Proyectos de presupuestos participativos ───
        $sql = "CREATE TABLE {$prefix}presupuesto_proyectos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            presupuesto_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion longtext,
            presupuesto_solicitado decimal(12,2) NOT NULL,
            categoria varchar(100) DEFAULT '',
            ubicacion varchar(255) DEFAULT '',
            autor_id bigint(20) unsigned NOT NULL,
            votos int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'propuesto',
            seleccionado tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY presupuesto_id (presupuesto_id),
            KEY autor_id (autor_id),
            KEY estado (estado),
            KEY votos (votos)
        ) {$charset_collate};";
        dbDelta($sql);

        // ─── Votos de presupuestos participativos ───
        $sql = "CREATE TABLE {$prefix}presupuesto_votos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            presupuesto_id bigint(20) unsigned NOT NULL,
            proyecto_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            puntos int(11) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY proyecto_user (proyecto_id, user_id),
            KEY presupuesto_id (presupuesto_id),
            KEY user_id (user_id)
        ) {$charset_collate};";
        dbDelta($sql);

        return true;
    }

    public function down() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tables = [
            'presupuesto_votos',
            'presupuesto_proyectos',
            'presupuestos_participativos',
            'propuesta_apoyos',
            'propuestas',
            'reservas',
            'recursos_reservables',
            'evento_inscripciones',
            'eventos',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table}");
        }

        return true;
    }
}
