<?php
/**
 * Instalación de tablas para el módulo de Marketplace
 *
 * @package FlavorPlatform
 * @subpackage Modules\Marketplace
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas del marketplace en la base de datos
 *
 * @return void
 */
function flavor_marketplace_crear_tablas() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla principal de anuncios
    $tabla_anuncios = $wpdb->prefix . 'flavor_marketplace_anuncios';
    $sql_anuncios = "CREATE TABLE IF NOT EXISTS $tabla_anuncios (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        titulo varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        descripcion text NOT NULL,
        tipo enum('venta','compra','intercambio','regalo','alquiler','servicio') NOT NULL DEFAULT 'venta',
        categoria_id bigint(20) unsigned DEFAULT NULL,
        subcategoria varchar(100) DEFAULT NULL,
        precio decimal(10,2) DEFAULT NULL,
        precio_negociable tinyint(1) NOT NULL DEFAULT 0,
        moneda varchar(3) DEFAULT 'EUR',
        es_gratuito tinyint(1) NOT NULL DEFAULT 0,
        condicion enum('nuevo','como_nuevo','buen_estado','usado','para_piezas') DEFAULT 'buen_estado',
        imagen_principal varchar(500) DEFAULT NULL,
        galeria_imagenes text DEFAULT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        usuario_nombre varchar(200) DEFAULT NULL,
        usuario_email varchar(200) DEFAULT NULL,
        usuario_telefono varchar(50) DEFAULT NULL,
        mostrar_telefono tinyint(1) NOT NULL DEFAULT 0,
        mostrar_email tinyint(1) NOT NULL DEFAULT 1,
        ubicacion_texto varchar(500) DEFAULT NULL,
        ubicacion_latitud decimal(10,8) DEFAULT NULL,
        ubicacion_longitud decimal(11,8) DEFAULT NULL,
        codigo_postal varchar(10) DEFAULT NULL,
        barrio varchar(100) DEFAULT NULL,
        envio_disponible tinyint(1) NOT NULL DEFAULT 0,
        coste_envio decimal(10,2) DEFAULT NULL,
        estado enum('borrador','pendiente','publicado','pausado','vendido','expirado','rechazado') NOT NULL DEFAULT 'pendiente',
        motivo_rechazo text DEFAULT NULL,
        fecha_publicacion datetime DEFAULT NULL,
        fecha_expiracion datetime DEFAULT NULL,
        es_destacado tinyint(1) NOT NULL DEFAULT 0,
        fecha_destacado datetime DEFAULT NULL,
        visualizaciones int(11) NOT NULL DEFAULT 0,
        favoritos_count int(11) NOT NULL DEFAULT 0,
        contactos_count int(11) NOT NULL DEFAULT 0,
        etiquetas text DEFAULT NULL,
        metadata json DEFAULT NULL,
        comunidad_id bigint(20) unsigned DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY tipo (tipo),
        KEY categoria_id (categoria_id),
        KEY estado (estado),
        KEY usuario_id (usuario_id),
        KEY precio (precio),
        KEY ubicacion (ubicacion_latitud, ubicacion_longitud),
        KEY barrio (barrio),
        KEY es_destacado (es_destacado),
        KEY fecha_publicacion (fecha_publicacion),
        KEY estado_tipo (estado, tipo),
        KEY comunidad_id (comunidad_id),
        FULLTEXT KEY busqueda (titulo, descripcion)
    ) $charset_collate;";

    // Tabla de categorías
    $tabla_categorias = $wpdb->prefix . 'flavor_marketplace_categorias';
    $sql_categorias = "CREATE TABLE IF NOT EXISTS $tabla_categorias (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        descripcion text DEFAULT NULL,
        icono varchar(50) DEFAULT 'dashicons-cart',
        color varchar(7) DEFAULT '#3b82f6',
        imagen varchar(500) DEFAULT NULL,
        padre_id bigint(20) unsigned DEFAULT NULL,
        activa tinyint(1) NOT NULL DEFAULT 1,
        orden int(11) NOT NULL DEFAULT 0,
        anuncios_count int(11) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY padre_id (padre_id),
        KEY activa (activa),
        KEY orden (orden)
    ) $charset_collate;";

    // Tabla de favoritos
    $tabla_favoritos = $wpdb->prefix . 'flavor_marketplace_favoritos';
    $sql_favoritos = "CREATE TABLE IF NOT EXISTS $tabla_favoritos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        anuncio_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY favorito_unico (anuncio_id, usuario_id),
        KEY anuncio_id (anuncio_id),
        KEY usuario_id (usuario_id)
    ) $charset_collate;";

    // Tabla de mensajes/contactos
    $tabla_mensajes = $wpdb->prefix . 'flavor_marketplace_mensajes';
    $sql_mensajes = "CREATE TABLE IF NOT EXISTS $tabla_mensajes (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        anuncio_id bigint(20) unsigned NOT NULL,
        conversacion_id varchar(50) NOT NULL,
        remitente_id bigint(20) unsigned NOT NULL,
        destinatario_id bigint(20) unsigned NOT NULL,
        mensaje text NOT NULL,
        leido tinyint(1) NOT NULL DEFAULT 0,
        leido_at datetime DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY anuncio_id (anuncio_id),
        KEY conversacion_id (conversacion_id),
        KEY remitente_id (remitente_id),
        KEY destinatario_id (destinatario_id),
        KEY leido (leido),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Tabla de valoraciones
    $tabla_valoraciones = $wpdb->prefix . 'flavor_marketplace_valoraciones';
    $sql_valoraciones = "CREATE TABLE IF NOT EXISTS $tabla_valoraciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        vendedor_id bigint(20) unsigned NOT NULL,
        comprador_id bigint(20) unsigned NOT NULL,
        anuncio_id bigint(20) unsigned DEFAULT NULL,
        puntuacion tinyint(1) NOT NULL,
        comentario text DEFAULT NULL,
        respuesta text DEFAULT NULL,
        respuesta_at datetime DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY vendedor_id (vendedor_id),
        KEY comprador_id (comprador_id),
        KEY anuncio_id (anuncio_id),
        KEY puntuacion (puntuacion)
    ) $charset_collate;";

    // Tabla de reportes de fraude/abuso
    $tabla_reportes = $wpdb->prefix . 'flavor_marketplace_reportes';
    $sql_reportes = "CREATE TABLE IF NOT EXISTS $tabla_reportes (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        anuncio_id bigint(20) unsigned DEFAULT NULL,
        usuario_reportado_id bigint(20) unsigned DEFAULT NULL,
        usuario_reportador_id bigint(20) unsigned DEFAULT NULL,
        tipo varchar(50) NOT NULL DEFAULT 'otro',
        descripcion text,
        estado varchar(20) NOT NULL DEFAULT 'pendiente',
        fecha_reporte datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        fecha_resolucion datetime DEFAULT NULL,
        resuelto_por bigint(20) unsigned DEFAULT NULL,
        notas_moderador text,
        PRIMARY KEY (id),
        KEY anuncio_id (anuncio_id),
        KEY usuario_reportado_id (usuario_reportado_id),
        KEY estado (estado),
        KEY fecha_reporte (fecha_reporte)
    ) $charset_collate;";

    // Tabla de transacciones/ventas completadas
    $tabla_transacciones = $wpdb->prefix . 'flavor_marketplace_transacciones';
    $sql_transacciones = "CREATE TABLE IF NOT EXISTS $tabla_transacciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        anuncio_id bigint(20) unsigned NOT NULL,
        vendedor_id bigint(20) unsigned NOT NULL,
        comprador_id bigint(20) unsigned NOT NULL,
        tipo_transaccion varchar(20) NOT NULL DEFAULT 'venta',
        precio decimal(10,2) DEFAULT NULL,
        estado varchar(20) NOT NULL DEFAULT 'pendiente',
        fecha_inicio datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        fecha_completada datetime DEFAULT NULL,
        valoracion_vendedor tinyint(1) DEFAULT NULL,
        valoracion_comprador tinyint(1) DEFAULT NULL,
        comentario_vendedor text,
        comentario_comprador text,
        PRIMARY KEY (id),
        KEY anuncio_id (anuncio_id),
        KEY vendedor_id (vendedor_id),
        KEY comprador_id (comprador_id),
        KEY estado (estado),
        KEY fecha_inicio (fecha_inicio)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_anuncios);
    dbDelta($sql_categorias);
    dbDelta($sql_favoritos);
    dbDelta($sql_mensajes);
    dbDelta($sql_valoraciones);
    dbDelta($sql_reportes);
    dbDelta($sql_transacciones);

    // Insertar categorías por defecto
    flavor_marketplace_insertar_categorias_default();

    // Migración: añadir campo comunidad_id si no existe
    flavor_marketplace_migrar_comunidad_id();

    update_option('flavor_marketplace_db_version', '1.1.0');
}

/**
 * Migración: añade el campo comunidad_id a la tabla de anuncios si no existe
 *
 * @return void
 */
function flavor_marketplace_migrar_comunidad_id() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_marketplace_anuncios';

    // Verificar si la columna ya existe
    $columna_existe = $wpdb->get_results(
        $wpdb->prepare(
            "SHOW COLUMNS FROM {$tabla} LIKE %s",
            'comunidad_id'
        )
    );

    if (empty($columna_existe)) {
        $wpdb->query("ALTER TABLE {$tabla} ADD COLUMN comunidad_id bigint(20) unsigned DEFAULT NULL AFTER metadata");
        $wpdb->query("ALTER TABLE {$tabla} ADD INDEX idx_comunidad_id (comunidad_id)");
    }
}

/**
 * Inserta categorías por defecto
 *
 * @return void
 */
function flavor_marketplace_insertar_categorias_default() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_marketplace_categorias';

    $existe = $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
    if ($existe > 0) {
        return;
    }

    $categorias = [
        ['nombre' => 'Hogar y Jardín', 'slug' => 'hogar-jardin', 'icono' => 'dashicons-admin-home', 'color' => '#10b981'],
        ['nombre' => 'Tecnología', 'slug' => 'tecnologia', 'icono' => 'dashicons-laptop', 'color' => '#3b82f6'],
        ['nombre' => 'Moda y Accesorios', 'slug' => 'moda-accesorios', 'icono' => 'dashicons-admin-appearance', 'color' => '#ec4899'],
        ['nombre' => 'Vehículos', 'slug' => 'vehiculos', 'icono' => 'dashicons-car', 'color' => '#6366f1'],
        ['nombre' => 'Deportes', 'slug' => 'deportes', 'icono' => 'dashicons-superhero', 'color' => '#f59e0b'],
        ['nombre' => 'Libros y Música', 'slug' => 'libros-musica', 'icono' => 'dashicons-book', 'color' => '#8b5cf6'],
        ['nombre' => 'Niños y Bebés', 'slug' => 'ninos-bebes', 'icono' => 'dashicons-heart', 'color' => '#f43f5e'],
        ['nombre' => 'Mascotas', 'slug' => 'mascotas', 'icono' => 'dashicons-pets', 'color' => '#84cc16'],
        ['nombre' => 'Servicios', 'slug' => 'servicios', 'icono' => 'dashicons-businessman', 'color' => '#06b6d4'],
        ['nombre' => 'Otros', 'slug' => 'otros', 'icono' => 'dashicons-tag', 'color' => '#6b7280'],
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
 * Elimina las tablas del marketplace
 *
 * @return void
 */
function flavor_marketplace_eliminar_tablas() {
    global $wpdb;

    $tablas = [
        $wpdb->prefix . 'flavor_marketplace_anuncios',
        $wpdb->prefix . 'flavor_marketplace_categorias',
        $wpdb->prefix . 'flavor_marketplace_favoritos',
        $wpdb->prefix . 'flavor_marketplace_mensajes',
        $wpdb->prefix . 'flavor_marketplace_valoraciones',
        $wpdb->prefix . 'flavor_marketplace_reportes',
        $wpdb->prefix . 'flavor_marketplace_transacciones',
    ];

    foreach ($tablas as $tabla) {
        $wpdb->query("DROP TABLE IF EXISTS $tabla");
    }

    delete_option('flavor_marketplace_db_version');
}
