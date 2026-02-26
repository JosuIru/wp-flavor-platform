<?php
/**
 * Instalación de tablas para el módulo de Incidencias
 *
 * @package FlavorChatIA
 * @subpackage Modules\Incidencias
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas de incidencias en la base de datos
 *
 * @return void
 */
function flavor_incidencias_crear_tablas() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla principal de incidencias
    $tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
    $sql_incidencias = "CREATE TABLE IF NOT EXISTS $tabla_incidencias (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        titulo varchar(255) NOT NULL,
        descripcion text NOT NULL,
        categoria varchar(100) NOT NULL DEFAULT 'general',
        subcategoria varchar(100) DEFAULT NULL,
        prioridad enum('baja','media','alta','urgente') NOT NULL DEFAULT 'media',
        estado enum('pendiente','en_revision','en_progreso','resuelta','cerrada','rechazada') NOT NULL DEFAULT 'pendiente',
        ubicacion_texto varchar(500) DEFAULT NULL,
        latitud decimal(10,8) DEFAULT NULL,
        longitud decimal(11,8) DEFAULT NULL,
        direccion varchar(500) DEFAULT NULL,
        codigo_postal varchar(10) DEFAULT NULL,
        barrio varchar(100) DEFAULT NULL,
        imagen_url varchar(500) DEFAULT NULL,
        imagenes_adicionales text DEFAULT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        usuario_nombre varchar(200) DEFAULT NULL,
        usuario_email varchar(200) DEFAULT NULL,
        usuario_telefono varchar(50) DEFAULT NULL,
        es_anonimo tinyint(1) NOT NULL DEFAULT 0,
        asignado_a bigint(20) unsigned DEFAULT NULL,
        departamento varchar(100) DEFAULT NULL,
        fecha_limite date DEFAULT NULL,
        fecha_resolucion datetime DEFAULT NULL,
        resolucion_descripcion text DEFAULT NULL,
        votos_apoyo int(11) NOT NULL DEFAULT 0,
        visualizaciones int(11) NOT NULL DEFAULT 0,
        es_publica tinyint(1) NOT NULL DEFAULT 1,
        origen varchar(50) DEFAULT 'web',
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY categoria (categoria),
        KEY estado (estado),
        KEY prioridad (prioridad),
        KEY usuario_id (usuario_id),
        KEY asignado_a (asignado_a),
        KEY ubicacion (latitud, longitud),
        KEY barrio (barrio),
        KEY created_at (created_at),
        KEY estado_prioridad (estado, prioridad),
        FULLTEXT KEY busqueda (titulo, descripcion, ubicacion_texto)
    ) $charset_collate;";

    // Tabla de comentarios/seguimiento
    $tabla_comentarios = $wpdb->prefix . 'flavor_incidencias_comentarios';
    $sql_comentarios = "CREATE TABLE IF NOT EXISTS $tabla_comentarios (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        incidencia_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        usuario_nombre varchar(200) DEFAULT NULL,
        tipo enum('comentario','estado','asignacion','interno','resolucion') NOT NULL DEFAULT 'comentario',
        contenido text NOT NULL,
        es_interno tinyint(1) NOT NULL DEFAULT 0,
        adjuntos text DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY incidencia_id (incidencia_id),
        KEY usuario_id (usuario_id),
        KEY tipo (tipo),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Tabla de votos de apoyo
    $tabla_votos = $wpdb->prefix . 'flavor_incidencias_votos';
    $sql_votos = "CREATE TABLE IF NOT EXISTS $tabla_votos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        incidencia_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY voto_unico (incidencia_id, usuario_id),
        KEY incidencia_id (incidencia_id),
        KEY usuario_id (usuario_id)
    ) $charset_collate;";

    // Tabla de categorías
    $tabla_categorias = $wpdb->prefix . 'flavor_incidencias_categorias';
    $sql_categorias = "CREATE TABLE IF NOT EXISTS $tabla_categorias (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        descripcion text DEFAULT NULL,
        icono varchar(50) DEFAULT 'dashicons-warning',
        color varchar(7) DEFAULT '#666666',
        padre_id bigint(20) unsigned DEFAULT NULL,
        departamento_default varchar(100) DEFAULT NULL,
        prioridad_default enum('baja','media','alta','urgente') DEFAULT 'media',
        activa tinyint(1) NOT NULL DEFAULT 1,
        orden int(11) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY padre_id (padre_id),
        KEY activa (activa),
        KEY orden (orden)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_incidencias);
    dbDelta($sql_comentarios);
    dbDelta($sql_votos);
    dbDelta($sql_categorias);

    // Insertar categorías por defecto si no existen
    flavor_incidencias_insertar_categorias_default();

    update_option('flavor_incidencias_db_version', '1.0.0');
}

/**
 * Inserta las categorías por defecto
 *
 * @return void
 */
function flavor_incidencias_insertar_categorias_default() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_incidencias_categorias';

    $existe = $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
    if ($existe > 0) {
        return;
    }

    $categorias = [
        ['nombre' => 'Alumbrado', 'slug' => 'alumbrado', 'icono' => 'dashicons-lightbulb', 'color' => '#f59e0b'],
        ['nombre' => 'Limpieza', 'slug' => 'limpieza', 'icono' => 'dashicons-trash', 'color' => '#10b981'],
        ['nombre' => 'Vía Pública', 'slug' => 'via-publica', 'icono' => 'dashicons-location', 'color' => '#3b82f6'],
        ['nombre' => 'Parques y Jardines', 'slug' => 'parques-jardines', 'icono' => 'dashicons-palmtree', 'color' => '#22c55e'],
        ['nombre' => 'Mobiliario Urbano', 'slug' => 'mobiliario-urbano', 'icono' => 'dashicons-admin-home', 'color' => '#8b5cf6'],
        ['nombre' => 'Tráfico', 'slug' => 'trafico', 'icono' => 'dashicons-car', 'color' => '#ef4444'],
        ['nombre' => 'Ruidos', 'slug' => 'ruidos', 'icono' => 'dashicons-megaphone', 'color' => '#f97316'],
        ['nombre' => 'Agua', 'slug' => 'agua', 'icono' => 'dashicons-admin-site', 'color' => '#06b6d4'],
        ['nombre' => 'Seguridad', 'slug' => 'seguridad', 'icono' => 'dashicons-shield', 'color' => '#dc2626'],
        ['nombre' => 'Otros', 'slug' => 'otros', 'icono' => 'dashicons-admin-generic', 'color' => '#6b7280'],
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
 * Elimina las tablas de incidencias
 *
 * @return void
 */
function flavor_incidencias_eliminar_tablas() {
    global $wpdb;

    $tablas = [
        $wpdb->prefix . 'flavor_incidencias',
        $wpdb->prefix . 'flavor_incidencias_comentarios',
        $wpdb->prefix . 'flavor_incidencias_votos',
        $wpdb->prefix . 'flavor_incidencias_categorias',
    ];

    foreach ($tablas as $tabla) {
        $wpdb->query("DROP TABLE IF EXISTS $tabla");
    }

    delete_option('flavor_incidencias_db_version');
}
