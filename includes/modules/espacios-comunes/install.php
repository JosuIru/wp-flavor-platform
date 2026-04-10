<?php
/**
 * Instalación de tablas para el módulo de Espacios Comunes
 *
 * @package FlavorPlatform
 * @subpackage Modules\EspaciosComunes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas de espacios comunes en la base de datos
 *
 * @return void
 */
function flavor_espacios_comunes_crear_tablas() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla principal de espacios
    $tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
    $sql_espacios = "CREATE TABLE IF NOT EXISTS $tabla_espacios (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        descripcion text DEFAULT NULL,
        tipo varchar(50) NOT NULL DEFAULT 'sala',
        categoria varchar(100) DEFAULT NULL,
        imagen_principal varchar(500) DEFAULT NULL,
        galeria_imagenes text DEFAULT NULL,
        direccion varchar(500) DEFAULT NULL,
        latitud decimal(10,8) DEFAULT NULL,
        longitud decimal(11,8) DEFAULT NULL,
        planta varchar(50) DEFAULT NULL,
        capacidad_maxima int(11) DEFAULT NULL,
        superficie_m2 decimal(10,2) DEFAULT NULL,
        equipamiento text DEFAULT NULL,
        normas_uso text DEFAULT NULL,
        horario_apertura time DEFAULT '08:00:00',
        horario_cierre time DEFAULT '22:00:00',
        dias_disponibles varchar(50) DEFAULT '1,2,3,4,5',
        tiempo_minimo_reserva int(11) DEFAULT 30,
        tiempo_maximo_reserva int(11) DEFAULT 240,
        antelacion_minima_horas int(11) DEFAULT 24,
        antelacion_maxima_dias int(11) DEFAULT 30,
        precio_hora decimal(10,2) DEFAULT 0.00,
        precio_socios decimal(10,2) DEFAULT NULL,
        requiere_deposito tinyint(1) NOT NULL DEFAULT 0,
        deposito_cantidad decimal(10,2) DEFAULT NULL,
        requiere_aprobacion tinyint(1) NOT NULL DEFAULT 0,
        responsable_id bigint(20) unsigned DEFAULT NULL,
        estado enum('activo','inactivo','mantenimiento','reservado') NOT NULL DEFAULT 'activo',
        accesibilidad tinyint(1) NOT NULL DEFAULT 0,
        wifi tinyint(1) NOT NULL DEFAULT 0,
        parking tinyint(1) NOT NULL DEFAULT 0,
        visualizaciones int(11) NOT NULL DEFAULT 0,
        reservas_count int(11) NOT NULL DEFAULT 0,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY tipo (tipo),
        KEY categoria (categoria),
        KEY estado (estado),
        KEY capacidad_maxima (capacidad_maxima),
        KEY precio_hora (precio_hora)
    ) $charset_collate;";

    // Tabla de reservas
    $tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';
    $sql_reservas = "CREATE TABLE IF NOT EXISTS $tabla_reservas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        espacio_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        titulo varchar(255) DEFAULT NULL,
        descripcion text DEFAULT NULL,
        fecha date NOT NULL,
        hora_inicio time NOT NULL,
        hora_fin time NOT NULL,
        num_asistentes int(11) DEFAULT NULL,
        estado enum('pendiente','aprobada','rechazada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
        motivo_rechazo text DEFAULT NULL,
        precio_total decimal(10,2) DEFAULT 0.00,
        deposito_pagado tinyint(1) NOT NULL DEFAULT 0,
        pago_completado tinyint(1) NOT NULL DEFAULT 0,
        referencia_pago varchar(100) DEFAULT NULL,
        codigo_acceso varchar(50) DEFAULT NULL,
        check_in_at datetime DEFAULT NULL,
        check_out_at datetime DEFAULT NULL,
        notas_admin text DEFAULT NULL,
        aprobado_por bigint(20) unsigned DEFAULT NULL,
        aprobado_at datetime DEFAULT NULL,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY espacio_id (espacio_id),
        KEY usuario_id (usuario_id),
        KEY fecha (fecha),
        KEY estado (estado),
        KEY espacio_fecha (espacio_id, fecha),
        KEY codigo_acceso (codigo_acceso)
    ) $charset_collate;";

    // Tabla de bloqueos (mantenimiento, eventos especiales)
    $tabla_bloqueos = $wpdb->prefix . 'flavor_espacios_bloqueos';
    $sql_bloqueos = "CREATE TABLE IF NOT EXISTS $tabla_bloqueos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        espacio_id bigint(20) unsigned NOT NULL,
        motivo varchar(255) NOT NULL,
        descripcion text DEFAULT NULL,
        fecha_inicio datetime NOT NULL,
        fecha_fin datetime NOT NULL,
        tipo enum('mantenimiento','evento_privado','festivo','otro') NOT NULL DEFAULT 'mantenimiento',
        recurrente tinyint(1) NOT NULL DEFAULT 0,
        creado_por bigint(20) unsigned DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY espacio_id (espacio_id),
        KEY fecha_inicio (fecha_inicio),
        KEY fecha_fin (fecha_fin),
        KEY tipo (tipo)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_espacios);
    dbDelta($sql_reservas);
    dbDelta($sql_bloqueos);

    // Insertar espacios de ejemplo
    flavor_espacios_insertar_ejemplos();

    update_option('flavor_espacios_comunes_db_version', '1.0.0');
}

/**
 * Inserta espacios de ejemplo
 *
 * @return void
 */
function flavor_espacios_insertar_ejemplos() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_espacios_comunes';

    $existe = $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
    if ($existe > 0) {
        return;
    }

    $espacios = [
        [
            'nombre' => 'Sala de Reuniones Principal',
            'slug' => 'sala-reuniones-principal',
            'descripcion' => 'Sala de reuniones equipada con proyector, pizarra y videoconferencia.',
            'tipo' => 'sala_reuniones',
            'capacidad_maxima' => 20,
            'equipamiento' => 'Proyector, Pizarra, Sistema de videoconferencia, WiFi',
            'precio_hora' => 0,
            'wifi' => 1,
            'accesibilidad' => 1,
        ],
        [
            'nombre' => 'Salón de Actos',
            'slug' => 'salon-actos',
            'descripcion' => 'Salón multiusos para eventos, charlas y presentaciones.',
            'tipo' => 'salon',
            'capacidad_maxima' => 100,
            'equipamiento' => 'Sistema de sonido, Proyector HD, Micrófono inalámbrico',
            'precio_hora' => 25,
            'wifi' => 1,
            'accesibilidad' => 1,
        ],
        [
            'nombre' => 'Aula de Formación',
            'slug' => 'aula-formacion',
            'descripcion' => 'Aula preparada para talleres y cursos formativos.',
            'tipo' => 'aula',
            'capacidad_maxima' => 30,
            'equipamiento' => 'Mesas individuales, Pizarra, Proyector',
            'precio_hora' => 10,
            'wifi' => 1,
            'accesibilidad' => 1,
        ],
    ];

    foreach ($espacios as $espacio) {
        $wpdb->insert($tabla, array_merge($espacio, [
            'estado' => 'activo',
            'horario_apertura' => '08:00:00',
            'horario_cierre' => '22:00:00',
        ]));
    }
}

/**
 * Elimina las tablas de espacios comunes
 *
 * @return void
 */
function flavor_espacios_comunes_eliminar_tablas() {
    global $wpdb;

    $tablas = [
        $wpdb->prefix . 'flavor_espacios_comunes',
        $wpdb->prefix . 'flavor_espacios_reservas',
        $wpdb->prefix . 'flavor_espacios_bloqueos',
    ];

    foreach ($tablas as $tabla) {
        $wpdb->query("DROP TABLE IF EXISTS $tabla");
    }

    delete_option('flavor_espacios_comunes_db_version');
}
