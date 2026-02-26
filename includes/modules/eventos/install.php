<?php
/**
 * Instalación de tablas para el módulo de Eventos
 *
 * @package FlavorChatIA
 * @subpackage Modules\Eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas de eventos en la base de datos
 *
 * @return void
 */
function flavor_eventos_crear_tablas() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla principal de eventos
    $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
    $sql_eventos = "CREATE TABLE IF NOT EXISTS $tabla_eventos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        titulo varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        descripcion text DEFAULT NULL,
        contenido longtext DEFAULT NULL,
        extracto text DEFAULT NULL,
        imagen_destacada varchar(500) DEFAULT NULL,
        galeria_imagenes text DEFAULT NULL,
        tipo varchar(50) NOT NULL DEFAULT 'evento',
        categoria varchar(100) DEFAULT NULL,
        etiquetas text DEFAULT NULL,
        fecha_inicio datetime NOT NULL,
        fecha_fin datetime DEFAULT NULL,
        hora_inicio time DEFAULT NULL,
        hora_fin time DEFAULT NULL,
        es_todo_el_dia tinyint(1) NOT NULL DEFAULT 0,
        es_recurrente tinyint(1) NOT NULL DEFAULT 0,
        recurrencia_tipo varchar(20) DEFAULT NULL,
        recurrencia_config json DEFAULT NULL,
        ubicacion_tipo enum('presencial','online','hibrido') NOT NULL DEFAULT 'presencial',
        ubicacion_nombre varchar(255) DEFAULT NULL,
        ubicacion_direccion varchar(500) DEFAULT NULL,
        ubicacion_latitud decimal(10,8) DEFAULT NULL,
        ubicacion_longitud decimal(11,8) DEFAULT NULL,
        url_online varchar(500) DEFAULT NULL,
        plataforma_online varchar(50) DEFAULT NULL,
        organizador_id bigint(20) unsigned DEFAULT NULL,
        organizador_nombre varchar(200) DEFAULT NULL,
        organizador_email varchar(200) DEFAULT NULL,
        organizador_telefono varchar(50) DEFAULT NULL,
        aforo_maximo int(11) DEFAULT NULL,
        inscritos_count int(11) NOT NULL DEFAULT 0,
        lista_espera_count int(11) NOT NULL DEFAULT 0,
        requiere_inscripcion tinyint(1) NOT NULL DEFAULT 0,
        inscripcion_abierta tinyint(1) NOT NULL DEFAULT 1,
        fecha_limite_inscripcion datetime DEFAULT NULL,
        precio decimal(10,2) DEFAULT 0.00,
        precio_socios decimal(10,2) DEFAULT NULL,
        moneda varchar(3) DEFAULT 'EUR',
        es_gratuito tinyint(1) NOT NULL DEFAULT 1,
        estado enum('borrador','publicado','cancelado','finalizado','pospuesto') NOT NULL DEFAULT 'borrador',
        visibilidad enum('publico','privado','socios') NOT NULL DEFAULT 'publico',
        es_destacado tinyint(1) NOT NULL DEFAULT 0,
        visualizaciones int(11) NOT NULL DEFAULT 0,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY tipo (tipo),
        KEY categoria (categoria),
        KEY estado (estado),
        KEY fecha_inicio (fecha_inicio),
        KEY fecha_fin (fecha_fin),
        KEY organizador_id (organizador_id),
        KEY es_destacado (es_destacado),
        KEY ubicacion_tipo (ubicacion_tipo),
        KEY estado_fecha (estado, fecha_inicio),
        FULLTEXT KEY busqueda (titulo, descripcion, ubicacion_nombre)
    ) $charset_collate;";

    // Tabla de inscripciones
    $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';
    $sql_inscripciones = "CREATE TABLE IF NOT EXISTS $tabla_inscripciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        evento_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned DEFAULT NULL,
        nombre varchar(200) NOT NULL,
        email varchar(200) NOT NULL,
        telefono varchar(50) DEFAULT NULL,
        num_asistentes int(11) NOT NULL DEFAULT 1,
        estado enum('pendiente','confirmada','cancelada','asistio','no_asistio','lista_espera') NOT NULL DEFAULT 'pendiente',
        tipo_entrada varchar(50) DEFAULT 'general',
        precio_pagado decimal(10,2) DEFAULT 0.00,
        metodo_pago varchar(50) DEFAULT NULL,
        referencia_pago varchar(100) DEFAULT NULL,
        notas text DEFAULT NULL,
        codigo_confirmacion varchar(50) DEFAULT NULL,
        qr_code varchar(255) DEFAULT NULL,
        check_in_at datetime DEFAULT NULL,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY evento_id (evento_id),
        KEY usuario_id (usuario_id),
        KEY email (email),
        KEY estado (estado),
        KEY codigo_confirmacion (codigo_confirmacion),
        UNIQUE KEY inscripcion_unica (evento_id, email)
    ) $charset_collate;";

    // Tabla de categorías de eventos
    $tabla_categorias = $wpdb->prefix . 'flavor_eventos_categorias';
    $sql_categorias = "CREATE TABLE IF NOT EXISTS $tabla_categorias (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        descripcion text DEFAULT NULL,
        icono varchar(50) DEFAULT 'dashicons-calendar',
        color varchar(7) DEFAULT '#3b82f6',
        imagen varchar(500) DEFAULT NULL,
        padre_id bigint(20) unsigned DEFAULT NULL,
        activa tinyint(1) NOT NULL DEFAULT 1,
        orden int(11) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY padre_id (padre_id),
        KEY activa (activa)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_eventos);
    dbDelta($sql_inscripciones);
    dbDelta($sql_categorias);

    // Insertar categorías por defecto
    flavor_eventos_insertar_categorias_default();

    update_option('flavor_eventos_db_version', '1.0.0');
}

/**
 * Inserta categorías por defecto
 *
 * @return void
 */
function flavor_eventos_insertar_categorias_default() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_eventos_categorias';

    $existe = $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
    if ($existe > 0) {
        return;
    }

    $categorias = [
        ['nombre' => 'Culturales', 'slug' => 'culturales', 'icono' => 'dashicons-art', 'color' => '#8b5cf6'],
        ['nombre' => 'Deportivos', 'slug' => 'deportivos', 'icono' => 'dashicons-superhero', 'color' => '#10b981'],
        ['nombre' => 'Educativos', 'slug' => 'educativos', 'icono' => 'dashicons-welcome-learn-more', 'color' => '#3b82f6'],
        ['nombre' => 'Sociales', 'slug' => 'sociales', 'icono' => 'dashicons-groups', 'color' => '#f59e0b'],
        ['nombre' => 'Gastronomía', 'slug' => 'gastronomia', 'icono' => 'dashicons-carrot', 'color' => '#ef4444'],
        ['nombre' => 'Música', 'slug' => 'musica', 'icono' => 'dashicons-format-audio', 'color' => '#ec4899'],
        ['nombre' => 'Fiestas', 'slug' => 'fiestas', 'icono' => 'dashicons-buddicons-groups', 'color' => '#f97316'],
        ['nombre' => 'Talleres', 'slug' => 'talleres', 'icono' => 'dashicons-hammer', 'color' => '#06b6d4'],
        ['nombre' => 'Conferencias', 'slug' => 'conferencias', 'icono' => 'dashicons-microphone', 'color' => '#6366f1'],
        ['nombre' => 'Otros', 'slug' => 'otros', 'icono' => 'dashicons-calendar-alt', 'color' => '#6b7280'],
    ];

    foreach ($categorias as $index => $cat) {
        $wpdb->insert($tabla, [
            'nombre' => $cat['nombre'],
            'slug' => $cat['slug'],
            'icono' => $cat['icono'],
            'color' => $cat['color'],
            'orden' => $index,
            'activa' => 1,
        ]);
    }
}

/**
 * Elimina las tablas de eventos
 *
 * @return void
 */
function flavor_eventos_eliminar_tablas() {
    global $wpdb;

    $tablas = [
        $wpdb->prefix . 'flavor_eventos',
        $wpdb->prefix . 'flavor_eventos_inscripciones',
        $wpdb->prefix . 'flavor_eventos_categorias',
    ];

    foreach ($tablas as $tabla) {
        $wpdb->query("DROP TABLE IF EXISTS $tabla");
    }

    delete_option('flavor_eventos_db_version');
}
