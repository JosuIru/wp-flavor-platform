<?php
/**
 * Instalación de tablas para el módulo de Crowdfunding
 *
 * @package FlavorChatIA
 * @subpackage Modules\Crowdfunding
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas de crowdfunding en la base de datos
 *
 * @return void
 */
function flavor_crowdfunding_crear_tablas() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla principal de proyectos/campañas de crowdfunding
    $tabla_proyectos = $wpdb->prefix . 'flavor_crowdfunding_proyectos';
    $sql_proyectos = "CREATE TABLE IF NOT EXISTS $tabla_proyectos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        titulo varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        descripcion text NOT NULL,
        contenido longtext DEFAULT NULL,
        extracto varchar(500) DEFAULT NULL,

        -- Relación con otros módulos (polimórfica)
        modulo_origen varchar(50) DEFAULT NULL,
        entidad_tipo varchar(50) DEFAULT NULL,
        entidad_id bigint(20) unsigned DEFAULT NULL,

        -- Creador
        creador_id bigint(20) unsigned NOT NULL,
        colectivo_id bigint(20) unsigned DEFAULT NULL,
        comunidad_id bigint(20) unsigned DEFAULT NULL,

        -- Tipo de proyecto
        tipo enum('album','tour','produccion','equipamiento','espacio','evento','social','emergencia','otro') NOT NULL DEFAULT 'otro',
        categoria varchar(100) DEFAULT NULL,

        -- Objetivos de financiación
        objetivo_eur decimal(12,2) DEFAULT 0.00,
        objetivo_semilla decimal(12,2) DEFAULT 0.00,
        objetivo_hours decimal(10,2) DEFAULT 0.00,

        -- Recaudación actual
        recaudado_eur decimal(12,2) NOT NULL DEFAULT 0.00,
        recaudado_semilla decimal(12,2) NOT NULL DEFAULT 0.00,
        recaudado_hours decimal(10,2) NOT NULL DEFAULT 0.00,

        -- Configuración
        moneda_principal enum('eur','semilla','hours','mixta') DEFAULT 'eur',
        acepta_eur tinyint(1) NOT NULL DEFAULT 1,
        acepta_semilla tinyint(1) NOT NULL DEFAULT 1,
        acepta_hours tinyint(1) NOT NULL DEFAULT 1,
        minimo_aportacion decimal(10,2) DEFAULT 1.00,
        permite_aportacion_libre tinyint(1) NOT NULL DEFAULT 1,

        -- Modalidad
        modalidad enum('todo_o_nada','flexible','donacion') NOT NULL DEFAULT 'flexible',

        -- Fechas
        fecha_inicio datetime NOT NULL,
        fecha_fin datetime DEFAULT NULL,
        dias_duracion int(11) DEFAULT 30,

        -- Distribución de ingresos (estilo Kulturaka)
        porcentaje_creador decimal(5,2) DEFAULT 70.00,
        porcentaje_espacio decimal(5,2) DEFAULT 10.00,
        porcentaje_comunidad decimal(5,2) DEFAULT 10.00,
        porcentaje_plataforma decimal(5,2) DEFAULT 5.00,
        porcentaje_emergencia decimal(5,2) DEFAULT 5.00,

        -- Transparencia
        desglose_presupuesto json DEFAULT NULL,
        actualizaciones_count int(11) NOT NULL DEFAULT 0,

        -- Multimedia
        imagen_principal varchar(500) DEFAULT NULL,
        video_principal varchar(500) DEFAULT NULL,
        galeria json DEFAULT NULL,
        documentos json DEFAULT NULL,

        -- Estadísticas
        aportantes_count int(11) NOT NULL DEFAULT 0,
        visualizaciones int(11) NOT NULL DEFAULT 0,
        compartidos int(11) NOT NULL DEFAULT 0,

        -- Estado
        estado enum('borrador','revision','activo','pausado','exitoso','fallido','cancelado') NOT NULL DEFAULT 'borrador',
        destacado tinyint(1) NOT NULL DEFAULT 0,
        verificado tinyint(1) NOT NULL DEFAULT 0,
        verificado_por bigint(20) unsigned DEFAULT NULL,
        verificado_at datetime DEFAULT NULL,

        -- SEO y visibilidad
        visibilidad enum('publico','comunidad','privado') NOT NULL DEFAULT 'publico',
        etiquetas text DEFAULT NULL,

        -- Metadatos
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY creador_id (creador_id),
        KEY colectivo_id (colectivo_id),
        KEY comunidad_id (comunidad_id),
        KEY tipo (tipo),
        KEY estado (estado),
        KEY modalidad (modalidad),
        KEY fecha_inicio (fecha_inicio),
        KEY fecha_fin (fecha_fin),
        KEY entidad (modulo_origen, entidad_tipo, entidad_id),
        KEY destacado (destacado),
        FULLTEXT KEY busqueda (titulo, descripcion, extracto)
    ) $charset_collate;";

    // Tabla de tiers/niveles de recompensa
    $tabla_tiers = $wpdb->prefix . 'flavor_crowdfunding_tiers';
    $sql_tiers = "CREATE TABLE IF NOT EXISTS $tabla_tiers (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        proyecto_id bigint(20) unsigned NOT NULL,

        nombre varchar(150) NOT NULL,
        descripcion text DEFAULT NULL,

        -- Importes
        importe_eur decimal(10,2) DEFAULT NULL,
        importe_semilla decimal(10,2) DEFAULT NULL,
        importe_hours decimal(10,2) DEFAULT NULL,

        -- Recompensas
        recompensas json DEFAULT NULL,
        incluye_tiers_anteriores tinyint(1) NOT NULL DEFAULT 0,

        -- Límites
        cantidad_limitada int(11) DEFAULT NULL,
        cantidad_vendida int(11) NOT NULL DEFAULT 0,
        disponible tinyint(1) NOT NULL DEFAULT 1,

        -- Entrega
        fecha_entrega_estimada date DEFAULT NULL,
        requiere_envio tinyint(1) NOT NULL DEFAULT 0,
        coste_envio decimal(10,2) DEFAULT 0.00,

        -- Visual
        imagen varchar(500) DEFAULT NULL,
        destacado tinyint(1) NOT NULL DEFAULT 0,
        orden int(11) NOT NULL DEFAULT 0,
        activo tinyint(1) NOT NULL DEFAULT 1,

        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        KEY proyecto_id (proyecto_id),
        KEY orden (orden),
        KEY activo (activo),
        KEY disponible (disponible)
    ) $charset_collate;";

    // Tabla de aportaciones/contribuciones
    $tabla_aportaciones = $wpdb->prefix . 'flavor_crowdfunding_aportaciones';
    $sql_aportaciones = "CREATE TABLE IF NOT EXISTS $tabla_aportaciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        proyecto_id bigint(20) unsigned NOT NULL,
        tier_id bigint(20) unsigned DEFAULT NULL,
        usuario_id bigint(20) unsigned DEFAULT NULL,

        -- Datos del aportante (para anónimos o sin cuenta)
        nombre varchar(200) DEFAULT NULL,
        email varchar(200) DEFAULT NULL,
        telefono varchar(50) DEFAULT NULL,

        -- Aportación
        importe decimal(12,2) NOT NULL,
        moneda enum('eur','semilla','hours') NOT NULL DEFAULT 'eur',
        importe_eur_equivalente decimal(12,2) DEFAULT NULL,

        -- Pago
        estado enum('pendiente','procesando','completada','fallida','reembolsada','cancelada') NOT NULL DEFAULT 'pendiente',
        metodo_pago varchar(50) DEFAULT NULL,
        referencia_pago varchar(100) DEFAULT NULL,
        transaccion_id varchar(100) DEFAULT NULL,
        fecha_pago datetime DEFAULT NULL,

        -- Recompensas
        recompensas_seleccionadas json DEFAULT NULL,
        recompensas_entregadas tinyint(1) NOT NULL DEFAULT 0,
        fecha_entrega datetime DEFAULT NULL,
        direccion_envio text DEFAULT NULL,

        -- Opciones
        anonimo tinyint(1) NOT NULL DEFAULT 0,
        mensaje_publico text DEFAULT NULL,
        mensaje_privado text DEFAULT NULL,

        -- Recurrente
        es_recurrente tinyint(1) NOT NULL DEFAULT 0,
        frecuencia_recurrencia enum('mensual','trimestral','anual') DEFAULT NULL,
        subscription_id varchar(100) DEFAULT NULL,

        -- Comisiones aplicadas
        comision_plataforma decimal(10,2) DEFAULT 0.00,
        comision_pasarela decimal(10,2) DEFAULT 0.00,
        importe_neto decimal(12,2) DEFAULT NULL,

        -- Metadatos
        ip_address varchar(45) DEFAULT NULL,
        user_agent text DEFAULT NULL,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        KEY proyecto_id (proyecto_id),
        KEY tier_id (tier_id),
        KEY usuario_id (usuario_id),
        KEY email (email),
        KEY estado (estado),
        KEY moneda (moneda),
        KEY fecha_pago (fecha_pago),
        KEY referencia_pago (referencia_pago)
    ) $charset_collate;";

    // Tabla de actualizaciones del proyecto
    $tabla_actualizaciones = $wpdb->prefix . 'flavor_crowdfunding_actualizaciones';
    $sql_actualizaciones = "CREATE TABLE IF NOT EXISTS $tabla_actualizaciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        proyecto_id bigint(20) unsigned NOT NULL,
        autor_id bigint(20) unsigned NOT NULL,

        titulo varchar(255) NOT NULL,
        contenido longtext NOT NULL,
        tipo enum('novedad','hito','agradecimiento','problema','entrega','otro') NOT NULL DEFAULT 'novedad',

        -- Multimedia
        imagen varchar(500) DEFAULT NULL,
        video varchar(500) DEFAULT NULL,
        archivos json DEFAULT NULL,

        -- Visibilidad
        solo_aportantes tinyint(1) NOT NULL DEFAULT 0,
        destacada tinyint(1) NOT NULL DEFAULT 0,

        -- Notificaciones
        notificacion_enviada tinyint(1) NOT NULL DEFAULT 0,
        notificacion_fecha datetime DEFAULT NULL,

        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        KEY proyecto_id (proyecto_id),
        KEY autor_id (autor_id),
        KEY tipo (tipo),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Tabla de comentarios/agradecimientos
    $tabla_comentarios = $wpdb->prefix . 'flavor_crowdfunding_comentarios';
    $sql_comentarios = "CREATE TABLE IF NOT EXISTS $tabla_comentarios (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        proyecto_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned DEFAULT NULL,
        aportacion_id bigint(20) unsigned DEFAULT NULL,
        padre_id bigint(20) unsigned DEFAULT NULL,

        nombre varchar(200) DEFAULT NULL,
        contenido text NOT NULL,
        tipo enum('comentario','pregunta','agradecimiento','respuesta') DEFAULT 'comentario',

        -- Moderación
        estado enum('pendiente','aprobado','rechazado','spam') NOT NULL DEFAULT 'aprobado',
        es_del_creador tinyint(1) NOT NULL DEFAULT 0,
        destacado tinyint(1) NOT NULL DEFAULT 0,

        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        KEY proyecto_id (proyecto_id),
        KEY usuario_id (usuario_id),
        KEY padre_id (padre_id),
        KEY estado (estado),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Tabla de categorías de crowdfunding
    $tabla_categorias = $wpdb->prefix . 'flavor_crowdfunding_categorias';
    $sql_categorias = "CREATE TABLE IF NOT EXISTS $tabla_categorias (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        descripcion text DEFAULT NULL,
        icono varchar(50) DEFAULT 'dashicons-heart',
        color varchar(7) DEFAULT '#ec4899',
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
    dbDelta($sql_proyectos);
    dbDelta($sql_tiers);
    dbDelta($sql_aportaciones);
    dbDelta($sql_actualizaciones);
    dbDelta($sql_comentarios);
    dbDelta($sql_categorias);

    // Insertar categorías por defecto
    flavor_crowdfunding_insertar_categorias_default();

    update_option('flavor_crowdfunding_db_version', '1.0.0');
}

/**
 * Inserta categorías por defecto
 *
 * @return void
 */
function flavor_crowdfunding_insertar_categorias_default() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_crowdfunding_categorias';

    $existe = $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
    if ($existe > 0) {
        return;
    }

    $categorias = [
        ['nombre' => 'Música', 'slug' => 'musica', 'icono' => 'dashicons-format-audio', 'color' => '#ec4899'],
        ['nombre' => 'Teatro y Artes Escénicas', 'slug' => 'teatro', 'icono' => 'dashicons-tickets-alt', 'color' => '#ef4444'],
        ['nombre' => 'Audiovisual', 'slug' => 'audiovisual', 'icono' => 'dashicons-video-alt3', 'color' => '#8b5cf6'],
        ['nombre' => 'Artes Visuales', 'slug' => 'artes-visuales', 'icono' => 'dashicons-art', 'color' => '#3b82f6'],
        ['nombre' => 'Literatura', 'slug' => 'literatura', 'icono' => 'dashicons-book', 'color' => '#10b981'],
        ['nombre' => 'Espacios Culturales', 'slug' => 'espacios', 'icono' => 'dashicons-building', 'color' => '#f59e0b'],
        ['nombre' => 'Eventos y Festivales', 'slug' => 'eventos', 'icono' => 'dashicons-calendar-alt', 'color' => '#06b6d4'],
        ['nombre' => 'Proyectos Sociales', 'slug' => 'social', 'icono' => 'dashicons-groups', 'color' => '#84cc16'],
        ['nombre' => 'Educación', 'slug' => 'educacion', 'icono' => 'dashicons-welcome-learn-more', 'color' => '#6366f1'],
        ['nombre' => 'Medio Ambiente', 'slug' => 'medioambiente', 'icono' => 'dashicons-palmtree', 'color' => '#22c55e'],
        ['nombre' => 'Emergencias', 'slug' => 'emergencias', 'icono' => 'dashicons-sos', 'color' => '#dc2626'],
        ['nombre' => 'Otros', 'slug' => 'otros', 'icono' => 'dashicons-plus-alt', 'color' => '#6b7280'],
    ];

    foreach ($categorias as $index => $cat) {
        $wpdb->insert($tabla, array_merge($cat, [
            'orden' => $index,
            'activa' => 1,
        ]));
    }
}

/**
 * Elimina las tablas de crowdfunding
 *
 * @return void
 */
function flavor_crowdfunding_eliminar_tablas() {
    global $wpdb;

    $tablas = [
        $wpdb->prefix . 'flavor_crowdfunding_proyectos',
        $wpdb->prefix . 'flavor_crowdfunding_tiers',
        $wpdb->prefix . 'flavor_crowdfunding_aportaciones',
        $wpdb->prefix . 'flavor_crowdfunding_actualizaciones',
        $wpdb->prefix . 'flavor_crowdfunding_comentarios',
        $wpdb->prefix . 'flavor_crowdfunding_categorias',
    ];

    foreach ($tablas as $tabla) {
        $wpdb->query("DROP TABLE IF EXISTS $tabla");
    }

    delete_option('flavor_crowdfunding_db_version');
}
