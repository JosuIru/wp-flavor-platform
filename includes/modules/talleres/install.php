<?php
/**
 * Instalación de tablas para el módulo de Talleres
 *
 * @package FlavorPlatform
 * @subpackage Modules\Talleres
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas de talleres en la base de datos
 *
 * @return void
 */
function flavor_talleres_crear_tablas() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla principal de talleres
    $tabla_talleres = $wpdb->prefix . 'flavor_talleres';
    $sql_talleres = "CREATE TABLE IF NOT EXISTS $tabla_talleres (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        titulo varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        descripcion text DEFAULT NULL,
        contenido longtext DEFAULT NULL,
        objetivos text DEFAULT NULL,
        requisitos text DEFAULT NULL,
        materiales text DEFAULT NULL,
        categoria varchar(100) DEFAULT NULL,
        nivel enum('principiante','intermedio','avanzado','todos') NOT NULL DEFAULT 'todos',
        imagen_destacada varchar(500) DEFAULT NULL,
        galeria_imagenes text DEFAULT NULL,
        instructor_id bigint(20) unsigned DEFAULT NULL,
        instructor_nombre varchar(200) DEFAULT NULL,
        instructor_bio text DEFAULT NULL,
        instructor_foto varchar(500) DEFAULT NULL,
        modalidad enum('presencial','online','hibrido') NOT NULL DEFAULT 'presencial',
        ubicacion_nombre varchar(255) DEFAULT NULL,
        ubicacion_direccion varchar(500) DEFAULT NULL,
        ubicacion_latitud decimal(10,8) DEFAULT NULL,
        ubicacion_longitud decimal(11,8) DEFAULT NULL,
        url_online varchar(500) DEFAULT NULL,
        fecha_inicio date NOT NULL,
        fecha_fin date DEFAULT NULL,
        horario varchar(255) DEFAULT NULL,
        duracion_horas int(11) DEFAULT NULL,
        num_sesiones int(11) DEFAULT 1,
        plazas_maximas int(11) DEFAULT NULL,
        plazas_minimas int(11) DEFAULT NULL,
        inscritos_count int(11) NOT NULL DEFAULT 0,
        lista_espera_count int(11) NOT NULL DEFAULT 0,
        precio decimal(10,2) DEFAULT 0.00,
        precio_socios decimal(10,2) DEFAULT NULL,
        precio_materiales decimal(10,2) DEFAULT NULL,
        es_gratuito tinyint(1) NOT NULL DEFAULT 0,
        requiere_inscripcion tinyint(1) NOT NULL DEFAULT 1,
        fecha_limite_inscripcion datetime DEFAULT NULL,
        estado enum('borrador','publicado','en_curso','finalizado','cancelado','completo') NOT NULL DEFAULT 'borrador',
        certificado tinyint(1) NOT NULL DEFAULT 0,
        etiquetas text DEFAULT NULL,
        visualizaciones int(11) NOT NULL DEFAULT 0,
        valoracion_media decimal(3,2) DEFAULT NULL,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY categoria (categoria),
        KEY nivel (nivel),
        KEY modalidad (modalidad),
        KEY estado (estado),
        KEY instructor_id (instructor_id),
        KEY fecha_inicio (fecha_inicio),
        KEY precio (precio),
        FULLTEXT KEY busqueda (titulo, descripcion)
    ) $charset_collate;";

    // Tabla de inscripciones
    $tabla_inscripciones = $wpdb->prefix . 'flavor_talleres_inscripciones';
    $sql_inscripciones = "CREATE TABLE IF NOT EXISTS $tabla_inscripciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        taller_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned DEFAULT NULL,
        nombre varchar(200) NOT NULL,
        email varchar(200) NOT NULL,
        telefono varchar(50) DEFAULT NULL,
        estado enum('pendiente','confirmada','cancelada','completada','lista_espera','no_asistio') NOT NULL DEFAULT 'pendiente',
        precio_pagado decimal(10,2) DEFAULT 0.00,
        metodo_pago varchar(50) DEFAULT NULL,
        referencia_pago varchar(100) DEFAULT NULL,
        notas text DEFAULT NULL,
        asistencias json DEFAULT NULL,
        porcentaje_asistencia decimal(5,2) DEFAULT NULL,
        calificacion decimal(5,2) DEFAULT NULL,
        certificado_emitido tinyint(1) NOT NULL DEFAULT 0,
        certificado_url varchar(500) DEFAULT NULL,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY taller_id (taller_id),
        KEY usuario_id (usuario_id),
        KEY email (email),
        KEY estado (estado),
        UNIQUE KEY inscripcion_unica (taller_id, email)
    ) $charset_collate;";

    // Tabla de sesiones
    $tabla_sesiones = $wpdb->prefix . 'flavor_talleres_sesiones';
    $sql_sesiones = "CREATE TABLE IF NOT EXISTS $tabla_sesiones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        taller_id bigint(20) unsigned NOT NULL,
        numero_sesion int(11) NOT NULL,
        titulo varchar(255) DEFAULT NULL,
        descripcion text DEFAULT NULL,
        fecha date NOT NULL,
        hora_inicio time NOT NULL,
        hora_fin time NOT NULL,
        ubicacion varchar(500) DEFAULT NULL,
        url_online varchar(500) DEFAULT NULL,
        materiales text DEFAULT NULL,
        estado enum('programada','en_curso','completada','cancelada') NOT NULL DEFAULT 'programada',
        notas text DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY taller_id (taller_id),
        KEY fecha (fecha),
        KEY estado (estado)
    ) $charset_collate;";

    // Tabla de valoraciones
    $tabla_valoraciones = $wpdb->prefix . 'flavor_talleres_valoraciones';
    $sql_valoraciones = "CREATE TABLE IF NOT EXISTS $tabla_valoraciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        taller_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        puntuacion tinyint(1) NOT NULL,
        comentario text DEFAULT NULL,
        aspectos json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY taller_id (taller_id),
        KEY usuario_id (usuario_id),
        KEY puntuacion (puntuacion),
        UNIQUE KEY valoracion_unica (taller_id, usuario_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_talleres);
    dbDelta($sql_inscripciones);
    dbDelta($sql_sesiones);
    dbDelta($sql_valoraciones);

    update_option('flavor_talleres_db_version', '1.0.0');
}

/**
 * Elimina las tablas de talleres
 *
 * @return void
 */
function flavor_talleres_eliminar_tablas() {
    global $wpdb;

    $tablas = [
        $wpdb->prefix . 'flavor_talleres',
        $wpdb->prefix . 'flavor_talleres_inscripciones',
        $wpdb->prefix . 'flavor_talleres_sesiones',
        $wpdb->prefix . 'flavor_talleres_valoraciones',
    ];

    foreach ($tablas as $tabla) {
        $wpdb->query("DROP TABLE IF EXISTS $tabla");
    }

    delete_option('flavor_talleres_db_version');
}
