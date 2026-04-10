<?php
/**
 * Instalación de tablas para el módulo de Socios
 *
 * @package FlavorPlatform
 * @subpackage Modules\Socios
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas de socios en la base de datos
 *
 * @return void
 */
function flavor_socios_crear_tablas() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla principal de socios
    $tabla_socios = $wpdb->prefix . 'flavor_socios';
    $sql_socios = "CREATE TABLE IF NOT EXISTS $tabla_socios (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        numero_socio varchar(50) NOT NULL,
        usuario_id bigint(20) unsigned DEFAULT NULL,
        nombre varchar(100) NOT NULL,
        apellidos varchar(150) DEFAULT NULL,
        email varchar(200) NOT NULL,
        telefono varchar(50) DEFAULT NULL,
        telefono_secundario varchar(50) DEFAULT NULL,
        dni_nif varchar(20) DEFAULT NULL,
        fecha_nacimiento date DEFAULT NULL,
        genero enum('masculino','femenino','otro','no_especificado') DEFAULT 'no_especificado',
        direccion varchar(500) DEFAULT NULL,
        codigo_postal varchar(10) DEFAULT NULL,
        ciudad varchar(100) DEFAULT NULL,
        provincia varchar(100) DEFAULT NULL,
        pais varchar(100) DEFAULT 'Espana',
        imagen_perfil varchar(500) DEFAULT NULL,
        tipo_socio varchar(50) NOT NULL DEFAULT 'ordinario',
        categoria varchar(50) DEFAULT NULL,
        fecha_alta date NOT NULL,
        fecha_baja date DEFAULT NULL,
        motivo_baja text DEFAULT NULL,
        estado enum('pendiente','activo','suspendido','baja','moroso') NOT NULL DEFAULT 'pendiente',
        cuota_tipo varchar(50) DEFAULT 'mensual',
        cuota_importe decimal(10,2) DEFAULT 0.00,
        cuota_reducida tinyint(1) NOT NULL DEFAULT 0,
        motivo_reduccion text DEFAULT NULL,
        forma_pago enum('domiciliacion','transferencia','efectivo','tarjeta') DEFAULT 'transferencia',
        iban varchar(34) DEFAULT NULL,
        mandato_sepa varchar(50) DEFAULT NULL,
        comunicaciones_email tinyint(1) NOT NULL DEFAULT 1,
        comunicaciones_sms tinyint(1) NOT NULL DEFAULT 0,
        comunicaciones_postal tinyint(1) NOT NULL DEFAULT 0,
        intereses text DEFAULT NULL,
        notas text DEFAULT NULL,
        referido_por bigint(20) unsigned DEFAULT NULL,
        carnet_emitido tinyint(1) NOT NULL DEFAULT 0,
        carnet_fecha_emision date DEFAULT NULL,
        ultimo_acceso datetime DEFAULT NULL,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY numero_socio (numero_socio),
        UNIQUE KEY email (email),
        KEY usuario_id (usuario_id),
        KEY tipo_socio (tipo_socio),
        KEY estado (estado),
        KEY fecha_alta (fecha_alta),
        KEY ciudad (ciudad),
        KEY dni_nif (dni_nif),
        FULLTEXT KEY busqueda (nombre, apellidos, email)
    ) $charset_collate;";

    // Tabla de cuotas/pagos
    $tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';
    $sql_cuotas = "CREATE TABLE IF NOT EXISTS $tabla_cuotas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        socio_id bigint(20) unsigned NOT NULL,
        concepto varchar(255) NOT NULL,
        tipo enum('cuota','inscripcion','donacion','evento','otro') NOT NULL DEFAULT 'cuota',
        periodo varchar(50) DEFAULT NULL,
        fecha_emision date NOT NULL,
        fecha_vencimiento date NOT NULL,
        importe decimal(10,2) NOT NULL,
        importe_pagado decimal(10,2) DEFAULT 0.00,
        estado enum('pendiente','pagada','vencida','cancelada','devuelta') NOT NULL DEFAULT 'pendiente',
        fecha_pago datetime DEFAULT NULL,
        metodo_pago varchar(50) DEFAULT NULL,
        referencia_pago varchar(100) DEFAULT NULL,
        factura_id varchar(50) DEFAULT NULL,
        factura_url varchar(500) DEFAULT NULL,
        remesa_id varchar(50) DEFAULT NULL,
        intentos_cobro int(11) NOT NULL DEFAULT 0,
        ultimo_intento datetime DEFAULT NULL,
        notas text DEFAULT NULL,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY socio_id (socio_id),
        KEY tipo (tipo),
        KEY estado (estado),
        KEY fecha_vencimiento (fecha_vencimiento),
        KEY periodo (periodo),
        KEY remesa_id (remesa_id)
    ) $charset_collate;";

    // Tabla de tipos de socio
    $tabla_tipos = $wpdb->prefix . 'flavor_socios_tipos';
    $sql_tipos = "CREATE TABLE IF NOT EXISTS $tabla_tipos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        descripcion text DEFAULT NULL,
        cuota_mensual decimal(10,2) DEFAULT 0.00,
        cuota_anual decimal(10,2) DEFAULT 0.00,
        cuota_inscripcion decimal(10,2) DEFAULT 0.00,
        beneficios text DEFAULT NULL,
        color varchar(7) DEFAULT '#3b82f6',
        icono varchar(50) DEFAULT 'dashicons-id',
        es_gratuito tinyint(1) NOT NULL DEFAULT 0,
        requiere_aprobacion tinyint(1) NOT NULL DEFAULT 0,
        visible tinyint(1) NOT NULL DEFAULT 1,
        orden int(11) NOT NULL DEFAULT 0,
        activo tinyint(1) NOT NULL DEFAULT 1,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY activo (activo),
        KEY orden (orden)
    ) $charset_collate;";

    // Tabla de historial de cambios
    $tabla_historial = $wpdb->prefix . 'flavor_socios_historial';
    $sql_historial = "CREATE TABLE IF NOT EXISTS $tabla_historial (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        socio_id bigint(20) unsigned NOT NULL,
        accion varchar(100) NOT NULL,
        descripcion text DEFAULT NULL,
        datos_anteriores json DEFAULT NULL,
        datos_nuevos json DEFAULT NULL,
        realizado_por bigint(20) unsigned DEFAULT NULL,
        ip_address varchar(45) DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY socio_id (socio_id),
        KEY accion (accion),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_socios);
    dbDelta($sql_cuotas);
    dbDelta($sql_tipos);
    dbDelta($sql_historial);

    // Insertar tipos de socio por defecto
    flavor_socios_insertar_tipos_default();

    update_option('flavor_socios_db_version', '1.0.0');
}

/**
 * Inserta tipos de socio por defecto
 *
 * @return void
 */
function flavor_socios_insertar_tipos_default() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_socios_tipos';

    $existe = $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
    if ($existe > 0) {
        return;
    }

    $tipos = [
        [
            'nombre' => 'Socio Ordinario',
            'slug' => 'ordinario',
            'descripcion' => 'Membresía estándar con todos los derechos y servicios.',
            'cuota_mensual' => 10.00,
            'cuota_anual' => 100.00,
            'cuota_inscripcion' => 15.00,
            'beneficios' => 'Acceso a todos los servicios, Descuentos en actividades, Voto en asambleas',
            'color' => '#3b82f6',
        ],
        [
            'nombre' => 'Socio Juvenil',
            'slug' => 'juvenil',
            'descripcion' => 'Para jóvenes menores de 30 años.',
            'cuota_mensual' => 5.00,
            'cuota_anual' => 50.00,
            'cuota_inscripcion' => 0.00,
            'beneficios' => 'Acceso a todos los servicios, Descuentos especiales, Actividades juveniles',
            'color' => '#10b981',
        ],
        [
            'nombre' => 'Socio Familiar',
            'slug' => 'familiar',
            'descripcion' => 'Membresía para toda la unidad familiar.',
            'cuota_mensual' => 20.00,
            'cuota_anual' => 200.00,
            'cuota_inscripcion' => 25.00,
            'beneficios' => 'Hasta 4 miembros, Acceso completo, Descuentos familiares',
            'color' => '#f59e0b',
        ],
        [
            'nombre' => 'Socio Colaborador',
            'slug' => 'colaborador',
            'descripcion' => 'Apoya la organización sin participación activa.',
            'cuota_mensual' => 5.00,
            'cuota_anual' => 50.00,
            'cuota_inscripcion' => 0.00,
            'beneficios' => 'Boletín informativo, Acceso a eventos abiertos',
            'color' => '#8b5cf6',
        ],
        [
            'nombre' => 'Socio Honorífico',
            'slug' => 'honorifico',
            'descripcion' => 'Membresía gratuita por méritos especiales.',
            'cuota_mensual' => 0.00,
            'cuota_anual' => 0.00,
            'cuota_inscripcion' => 0.00,
            'es_gratuito' => 1,
            'beneficios' => 'Todos los beneficios, Reconocimiento especial',
            'color' => '#ec4899',
        ],
    ];

    foreach ($tipos as $index => $tipo) {
        $wpdb->insert($tabla, array_merge([
            'orden' => $index,
            'activo' => 1,
            'visible' => 1,
            'icono' => 'dashicons-id',
        ], $tipo));
    }
}

/**
 * Elimina las tablas de socios
 *
 * @return void
 */
function flavor_socios_eliminar_tablas() {
    global $wpdb;

    $tablas = [
        $wpdb->prefix . 'flavor_socios',
        $wpdb->prefix . 'flavor_socios_cuotas',
        $wpdb->prefix . 'flavor_socios_tipos',
        $wpdb->prefix . 'flavor_socios_historial',
    ];

    foreach ($tablas as $tabla) {
        $wpdb->query("DROP TABLE IF EXISTS $tabla");
    }

    delete_option('flavor_socios_db_version');
}
