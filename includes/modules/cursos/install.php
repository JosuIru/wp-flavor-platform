<?php
/**
 * Instalación de tablas para el módulo de Cursos
 *
 * @package FlavorPlatform
 * @subpackage Modules\Cursos
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas necesarias para el módulo de Cursos
 */
function flavor_cursos_install_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Tabla principal de cursos
    $tabla_cursos = $wpdb->prefix . 'flavor_cursos';
    $sql_cursos = "CREATE TABLE {$tabla_cursos} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        titulo varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        descripcion longtext,
        descripcion_corta text,
        imagen_destacada varchar(500),
        instructor_id bigint(20) UNSIGNED,
        instructor_nombre varchar(255),
        categoria_id bigint(20) UNSIGNED,
        nivel enum('basico','intermedio','avanzado') DEFAULT 'basico',
        duracion_horas int(11) DEFAULT 0,
        total_lecciones int(11) DEFAULT 0,
        estado enum('borrador','pendiente','publicado','archivado') DEFAULT 'borrador',
        es_gratuito tinyint(1) DEFAULT 0,
        precio decimal(10,2) DEFAULT 0.00,
        precio_oferta decimal(10,2) DEFAULT NULL,
        fecha_inicio date DEFAULT NULL,
        fecha_fin date DEFAULT NULL,
        plazas_maximas int(11) DEFAULT NULL,
        inscritos_count int(11) DEFAULT 0,
        valoracion_media decimal(3,2) DEFAULT 0.00,
        total_valoraciones int(11) DEFAULT 0,
        requisitos text,
        objetivos text,
        certificado tinyint(1) DEFAULT 0,
        certificado_plantilla varchar(255),
        modalidad enum('presencial','online','hibrido') DEFAULT 'online',
        ubicacion varchar(255),
        url_streaming varchar(500),
        metadata json,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY instructor_id (instructor_id),
        KEY categoria_id (categoria_id),
        KEY estado (estado),
        KEY fecha_inicio (fecha_inicio)
    ) {$charset_collate};";
    dbDelta($sql_cursos);

    // Tabla de categorías de cursos
    $tabla_categorias = $wpdb->prefix . 'flavor_cursos_categorias';
    $sql_categorias = "CREATE TABLE {$tabla_categorias} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        nombre varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        descripcion text,
        imagen varchar(500),
        parent_id bigint(20) UNSIGNED DEFAULT 0,
        orden int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY parent_id (parent_id)
    ) {$charset_collate};";
    dbDelta($sql_categorias);

    // Tabla de lecciones/contenido del curso
    $tabla_lecciones = $wpdb->prefix . 'flavor_cursos_lecciones';
    $sql_lecciones = "CREATE TABLE {$tabla_lecciones} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        curso_id bigint(20) UNSIGNED NOT NULL,
        modulo_id bigint(20) UNSIGNED DEFAULT 0,
        titulo varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        contenido longtext,
        tipo enum('video','texto','quiz','tarea','recurso') DEFAULT 'texto',
        video_url varchar(500),
        duracion_minutos int(11) DEFAULT 0,
        orden int(11) DEFAULT 0,
        es_gratuita tinyint(1) DEFAULT 0,
        recursos json,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY curso_id (curso_id),
        KEY modulo_id (modulo_id),
        KEY orden (orden)
    ) {$charset_collate};";
    dbDelta($sql_lecciones);

    // Tabla de módulos/secciones del curso
    $tabla_modulos = $wpdb->prefix . 'flavor_cursos_modulos';
    $sql_modulos = "CREATE TABLE {$tabla_modulos} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        curso_id bigint(20) UNSIGNED NOT NULL,
        titulo varchar(255) NOT NULL,
        descripcion text,
        orden int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY curso_id (curso_id),
        KEY orden (orden)
    ) {$charset_collate};";
    dbDelta($sql_modulos);

    // Tabla de matrículas
    $tabla_matriculas = $wpdb->prefix . 'flavor_cursos_matriculas';
    $sql_matriculas = "CREATE TABLE {$tabla_matriculas} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        curso_id bigint(20) UNSIGNED NOT NULL,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        fecha_matricula datetime DEFAULT CURRENT_TIMESTAMP,
        fecha_inicio datetime,
        fecha_completado datetime,
        estado enum('activa','pausada','completada','cancelada') DEFAULT 'activa',
        progreso decimal(5,2) DEFAULT 0.00,
        ultima_leccion_id bigint(20) UNSIGNED,
        tiempo_total_minutos int(11) DEFAULT 0,
        certificado_emitido tinyint(1) DEFAULT 0,
        certificado_url varchar(500),
        certificado_fecha datetime,
        pago_id varchar(100),
        monto_pagado decimal(10,2) DEFAULT 0.00,
        metadata json,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY curso_usuario (curso_id, usuario_id),
        KEY usuario_id (usuario_id),
        KEY estado (estado)
    ) {$charset_collate};";
    dbDelta($sql_matriculas);

    // Tabla de progreso de lecciones
    $tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';
    $sql_progreso = "CREATE TABLE {$tabla_progreso} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        curso_id bigint(20) UNSIGNED NOT NULL,
        leccion_id bigint(20) UNSIGNED NOT NULL,
        completada tinyint(1) DEFAULT 0,
        fecha_completada datetime,
        tiempo_visualizado int(11) DEFAULT 0,
        puntuacion decimal(5,2) DEFAULT NULL,
        intentos int(11) DEFAULT 0,
        notas text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY usuario_leccion (usuario_id, leccion_id),
        KEY curso_id (curso_id)
    ) {$charset_collate};";
    dbDelta($sql_progreso);

    // Tabla de valoraciones
    $tabla_valoraciones = $wpdb->prefix . 'flavor_cursos_valoraciones';
    $sql_valoraciones = "CREATE TABLE {$tabla_valoraciones} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        curso_id bigint(20) UNSIGNED NOT NULL,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        puntuacion tinyint(1) NOT NULL,
        comentario text,
        estado enum('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY curso_usuario (curso_id, usuario_id),
        KEY puntuacion (puntuacion)
    ) {$charset_collate};";
    dbDelta($sql_valoraciones);

    // Guardar versión
    update_option('flavor_cursos_db_version', '1.0.0');
}

// Ejecutar instalación
flavor_cursos_install_tables();
