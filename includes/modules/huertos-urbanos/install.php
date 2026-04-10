<?php
/**
 * Instalación de tablas para el módulo de Huertos Urbanos
 *
 * @package FlavorPlatform
 * @subpackage Modules\HuertosUrbanos
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas necesarias para el módulo de Huertos Urbanos
 */
function flavor_huertos_urbanos_install_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Tabla principal de huertos
    $tabla_huertos = $wpdb->prefix . 'flavor_huertos_urbanos';
    $sql_huertos = "CREATE TABLE {$tabla_huertos} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        nombre varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        descripcion longtext,
        imagen_destacada varchar(500),
        galeria json,
        direccion varchar(500),
        latitud decimal(10,8),
        longitud decimal(11,8),
        superficie_total decimal(10,2),
        total_parcelas int(11) DEFAULT 0,
        parcelas_disponibles int(11) DEFAULT 0,
        tipo_riego varchar(100),
        acceso_agua tinyint(1) DEFAULT 1,
        herramientas_comunes tinyint(1) DEFAULT 0,
        compostaje_comunitario tinyint(1) DEFAULT 0,
        horario_apertura time,
        horario_cierre time,
        dias_apertura varchar(100),
        normas text,
        contacto_nombre varchar(255),
        contacto_email varchar(255),
        contacto_telefono varchar(50),
        cuota_mensual decimal(10,2) DEFAULT 0.00,
        deposito decimal(10,2) DEFAULT 0.00,
        estado enum('activo','inactivo','en_construccion') DEFAULT 'activo',
        metadata json,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY estado (estado)
    ) {$charset_collate};";
    dbDelta($sql_huertos);

    // Tabla de parcelas
    $tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
    $sql_parcelas = "CREATE TABLE {$tabla_parcelas} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        huerto_id bigint(20) UNSIGNED NOT NULL,
        numero varchar(50) NOT NULL,
        superficie decimal(8,2),
        ubicacion_descripcion varchar(255),
        posicion_x int(11),
        posicion_y int(11),
        tipo enum('individual','compartida','comunitaria') DEFAULT 'individual',
        estado enum('disponible','ocupada','reservada','mantenimiento') DEFAULT 'disponible',
        acceso_agua_directo tinyint(1) DEFAULT 0,
        sombra varchar(100),
        observaciones text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY huerto_id (huerto_id),
        KEY estado (estado),
        KEY numero (numero)
    ) {$charset_collate};";
    dbDelta($sql_parcelas);

    // Tabla de asignaciones de parcelas
    $tabla_asignaciones = $wpdb->prefix . 'flavor_huertos_asignaciones';
    $sql_asignaciones = "CREATE TABLE {$tabla_asignaciones} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        parcela_id bigint(20) UNSIGNED NOT NULL,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        fecha_asignacion date NOT NULL,
        fecha_fin date,
        estado enum('activa','finalizada','cancelada','suspendida') DEFAULT 'activa',
        motivo_fin text,
        deposito_pagado decimal(10,2) DEFAULT 0.00,
        deposito_devuelto tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY parcela_id (parcela_id),
        KEY usuario_id (usuario_id),
        KEY estado (estado)
    ) {$charset_collate};";
    dbDelta($sql_asignaciones);

    // Tabla de solicitudes de parcelas
    $tabla_solicitudes = $wpdb->prefix . 'flavor_huertos_solicitudes';
    $sql_solicitudes = "CREATE TABLE {$tabla_solicitudes} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        huerto_id bigint(20) UNSIGNED NOT NULL,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        parcela_preferida_id bigint(20) UNSIGNED,
        tipo_parcela_preferido varchar(50),
        experiencia_previa text,
        motivacion text,
        disponibilidad_horaria text,
        acepta_normas tinyint(1) DEFAULT 0,
        estado enum('pendiente','aprobada','rechazada','lista_espera') DEFAULT 'pendiente',
        posicion_lista_espera int(11),
        notas_admin text,
        fecha_respuesta datetime,
        admin_id bigint(20) UNSIGNED,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY huerto_id (huerto_id),
        KEY usuario_id (usuario_id),
        KEY estado (estado)
    ) {$charset_collate};";
    dbDelta($sql_solicitudes);

    // Tabla de actividades/diario
    $tabla_actividades = $wpdb->prefix . 'flavor_huertos_actividades';
    $sql_actividades = "CREATE TABLE {$tabla_actividades} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        parcela_id bigint(20) UNSIGNED NOT NULL,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        tipo enum('siembra','riego','cosecha','tratamiento','mantenimiento','otro') DEFAULT 'otro',
        titulo varchar(255),
        descripcion text,
        fecha_actividad date NOT NULL,
        cultivo varchar(255),
        cantidad varchar(100),
        fotos json,
        clima varchar(100),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY parcela_id (parcela_id),
        KEY usuario_id (usuario_id),
        KEY fecha_actividad (fecha_actividad),
        KEY tipo (tipo)
    ) {$charset_collate};";
    dbDelta($sql_actividades);

    // Tabla de cultivos registrados
    $tabla_cultivos = $wpdb->prefix . 'flavor_huertos_cultivos';
    $sql_cultivos = "CREATE TABLE {$tabla_cultivos} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        parcela_id bigint(20) UNSIGNED NOT NULL,
        nombre varchar(255) NOT NULL,
        variedad varchar(255),
        fecha_siembra date,
        fecha_cosecha_estimada date,
        fecha_cosecha_real date,
        estado enum('sembrado','creciendo','listo','cosechado','fallido') DEFAULT 'sembrado',
        cantidad_sembrada varchar(100),
        cantidad_cosechada varchar(100),
        notas text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY parcela_id (parcela_id),
        KEY estado (estado)
    ) {$charset_collate};";
    dbDelta($sql_cultivos);

    // Tabla de pagos de cuotas
    $tabla_pagos = $wpdb->prefix . 'flavor_huertos_pagos';
    $sql_pagos = "CREATE TABLE {$tabla_pagos} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        asignacion_id bigint(20) UNSIGNED NOT NULL,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        concepto varchar(255) NOT NULL,
        importe decimal(10,2) NOT NULL,
        periodo varchar(50),
        fecha_vencimiento date,
        fecha_pago datetime,
        estado enum('pendiente','pagado','vencido','cancelado') DEFAULT 'pendiente',
        metodo_pago varchar(100),
        referencia_pago varchar(255),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY asignacion_id (asignacion_id),
        KEY usuario_id (usuario_id),
        KEY estado (estado)
    ) {$charset_collate};";
    dbDelta($sql_pagos);

    // Guardar versión
    update_option('flavor_huertos_urbanos_db_version', '1.0.0');
}

// Ejecutar instalación
flavor_huertos_urbanos_install_tables();
