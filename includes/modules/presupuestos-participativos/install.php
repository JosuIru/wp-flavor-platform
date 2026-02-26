<?php
/**
 * Instalación de tablas para el módulo de Presupuestos Participativos
 *
 * @package FlavorChatIA
 * @subpackage Modules\PresupuestosParticipativos
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas necesarias para el módulo de Presupuestos Participativos
 */
function flavor_presupuestos_participativos_install_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Tabla de procesos de presupuestos participativos
    $tabla_procesos = $wpdb->prefix . 'flavor_pp_procesos';
    $sql_procesos = "CREATE TABLE {$tabla_procesos} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        titulo varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        descripcion longtext,
        imagen_destacada varchar(500),
        presupuesto_total decimal(15,2) NOT NULL,
        anio int(4) NOT NULL,
        estado enum('borrador','propuestas','revision','votacion','resultados','ejecucion','finalizado') DEFAULT 'borrador',
        fecha_inicio date NOT NULL,
        fecha_fin date NOT NULL,
        fecha_inicio_propuestas date,
        fecha_fin_propuestas date,
        fecha_inicio_votacion date,
        fecha_fin_votacion date,
        votos_por_ciudadano int(11) DEFAULT 3,
        presupuesto_max_propuesta decimal(15,2),
        presupuesto_min_propuesta decimal(15,2),
        requisitos_participacion text,
        ambito varchar(100),
        categorias_permitidas json,
        total_propuestas int(11) DEFAULT 0,
        total_votantes int(11) DEFAULT 0,
        metadata json,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY estado (estado),
        KEY anio (anio)
    ) {$charset_collate};";
    dbDelta($sql_procesos);

    // Tabla de categorías de propuestas
    $tabla_categorias = $wpdb->prefix . 'flavor_pp_categorias';
    $sql_categorias = "CREATE TABLE {$tabla_categorias} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        nombre varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        descripcion text,
        icono varchar(100),
        color varchar(20),
        presupuesto_reservado decimal(15,2) DEFAULT 0.00,
        orden int(11) DEFAULT 0,
        activa tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug)
    ) {$charset_collate};";
    dbDelta($sql_categorias);

    // Tabla de propuestas/proyectos
    $tabla_propuestas = $wpdb->prefix . 'flavor_pp_propuestas';
    $sql_propuestas = "CREATE TABLE {$tabla_propuestas} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        proceso_id bigint(20) UNSIGNED NOT NULL,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        titulo varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        descripcion longtext NOT NULL,
        descripcion_corta text,
        justificacion text,
        beneficiarios text,
        ubicacion varchar(500),
        latitud decimal(10,8),
        longitud decimal(11,8),
        categoria_id bigint(20) UNSIGNED,
        presupuesto_estimado decimal(15,2) NOT NULL,
        desglose_presupuesto text,
        imagen_principal varchar(500),
        galeria json,
        documentos json,
        estado enum('borrador','pendiente','en_revision','viable','no_viable','en_votacion','aprobada','rechazada','en_ejecucion','completada') DEFAULT 'borrador',
        viabilidad_tecnica text,
        viabilidad_economica text,
        notas_tecnicas text,
        revisor_id bigint(20) UNSIGNED,
        fecha_revision datetime,
        votos_total int(11) DEFAULT 0,
        posicion_ranking int(11),
        apoyos_count int(11) DEFAULT 0,
        comentarios_count int(11) DEFAULT 0,
        metadata json,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug_proceso (proceso_id, slug),
        KEY usuario_id (usuario_id),
        KEY categoria_id (categoria_id),
        KEY estado (estado),
        KEY votos_total (votos_total)
    ) {$charset_collate};";
    dbDelta($sql_propuestas);

    // Tabla de votos
    $tabla_votos = $wpdb->prefix . 'flavor_pp_votos';
    $sql_votos = "CREATE TABLE {$tabla_votos} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        proceso_id bigint(20) UNSIGNED NOT NULL,
        propuesta_id bigint(20) UNSIGNED NOT NULL,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        peso int(11) DEFAULT 1,
        fecha_voto datetime DEFAULT CURRENT_TIMESTAMP,
        ip_address varchar(45),
        user_agent varchar(500),
        PRIMARY KEY (id),
        UNIQUE KEY voto_unico (proceso_id, propuesta_id, usuario_id),
        KEY propuesta_id (propuesta_id),
        KEY usuario_id (usuario_id)
    ) {$charset_collate};";
    dbDelta($sql_votos);

    // Tabla de apoyos (durante fase de propuestas)
    $tabla_apoyos = $wpdb->prefix . 'flavor_pp_apoyos';
    $sql_apoyos = "CREATE TABLE {$tabla_apoyos} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        propuesta_id bigint(20) UNSIGNED NOT NULL,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY apoyo_unico (propuesta_id, usuario_id),
        KEY usuario_id (usuario_id)
    ) {$charset_collate};";
    dbDelta($sql_apoyos);

    // Tabla de comentarios
    $tabla_comentarios = $wpdb->prefix . 'flavor_pp_comentarios';
    $sql_comentarios = "CREATE TABLE {$tabla_comentarios} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        propuesta_id bigint(20) UNSIGNED NOT NULL,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        parent_id bigint(20) UNSIGNED DEFAULT 0,
        contenido text NOT NULL,
        estado enum('pendiente','aprobado','rechazado','spam') DEFAULT 'aprobado',
        likes_count int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY propuesta_id (propuesta_id),
        KEY usuario_id (usuario_id),
        KEY parent_id (parent_id),
        KEY estado (estado)
    ) {$charset_collate};";
    dbDelta($sql_comentarios);

    // Tabla de ejecución de proyectos aprobados
    $tabla_ejecucion = $wpdb->prefix . 'flavor_pp_ejecucion';
    $sql_ejecucion = "CREATE TABLE {$tabla_ejecucion} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        propuesta_id bigint(20) UNSIGNED NOT NULL,
        presupuesto_asignado decimal(15,2) NOT NULL,
        presupuesto_ejecutado decimal(15,2) DEFAULT 0.00,
        estado enum('planificacion','licitacion','en_obra','completado','cancelado') DEFAULT 'planificacion',
        fecha_inicio_prevista date,
        fecha_fin_prevista date,
        fecha_inicio_real date,
        fecha_fin_real date,
        responsable_id bigint(20) UNSIGNED,
        empresa_adjudicataria varchar(255),
        contrato_referencia varchar(100),
        porcentaje_avance decimal(5,2) DEFAULT 0.00,
        actualizaciones json,
        documentos json,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY propuesta_id (propuesta_id),
        KEY estado (estado)
    ) {$charset_collate};";
    dbDelta($sql_ejecucion);

    // Tabla de actualizaciones de ejecución
    $tabla_actualizaciones = $wpdb->prefix . 'flavor_pp_actualizaciones';
    $sql_actualizaciones = "CREATE TABLE {$tabla_actualizaciones} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        ejecucion_id bigint(20) UNSIGNED NOT NULL,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        titulo varchar(255) NOT NULL,
        descripcion text,
        tipo enum('avance','hito','incidencia','cambio','finalizacion') DEFAULT 'avance',
        porcentaje_avance decimal(5,2),
        imagenes json,
        documentos json,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY ejecucion_id (ejecucion_id),
        KEY tipo (tipo)
    ) {$charset_collate};";
    dbDelta($sql_actualizaciones);

    // Insertar categorías por defecto
    flavor_pp_insertar_categorias_defecto();

    // Guardar versión
    update_option('flavor_presupuestos_participativos_db_version', '1.0.0');
}

/**
 * Inserta categorías por defecto
 */
function flavor_pp_insertar_categorias_defecto() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_pp_categorias';

    $existe = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}");
    if ($existe > 0) {
        return;
    }

    $categorias = [
        ['nombre' => 'Movilidad y Transporte', 'slug' => 'movilidad', 'icono' => 'dashicons-car', 'color' => '#3b82f6'],
        ['nombre' => 'Medio Ambiente', 'slug' => 'medio-ambiente', 'icono' => 'dashicons-carrot', 'color' => '#22c55e'],
        ['nombre' => 'Espacios Públicos', 'slug' => 'espacios-publicos', 'icono' => 'dashicons-building', 'color' => '#a855f7'],
        ['nombre' => 'Cultura y Deporte', 'slug' => 'cultura-deporte', 'icono' => 'dashicons-awards', 'color' => '#f59e0b'],
        ['nombre' => 'Servicios Sociales', 'slug' => 'servicios-sociales', 'icono' => 'dashicons-groups', 'color' => '#ec4899'],
        ['nombre' => 'Educación', 'slug' => 'educacion', 'icono' => 'dashicons-welcome-learn-more', 'color' => '#06b6d4'],
        ['nombre' => 'Seguridad', 'slug' => 'seguridad', 'icono' => 'dashicons-shield', 'color' => '#ef4444'],
        ['nombre' => 'Otros', 'slug' => 'otros', 'icono' => 'dashicons-admin-generic', 'color' => '#6b7280'],
    ];

    foreach ($categorias as $indice => $categoria) {
        $wpdb->insert($tabla, [
            'nombre' => $categoria['nombre'],
            'slug' => $categoria['slug'],
            'icono' => $categoria['icono'],
            'color' => $categoria['color'],
            'orden' => $indice,
            'activa' => 1,
        ]);
    }
}

// Ejecutar instalación
flavor_presupuestos_participativos_install_tables();
