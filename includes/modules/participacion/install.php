<?php
/**
 * Instalación de tablas para el módulo de Participación Ciudadana
 *
 * @package FlavorChatIA
 * @subpackage Modules\Participacion
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas de participación en la base de datos
 *
 * @return void
 */
function flavor_participacion_crear_tablas() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla de propuestas ciudadanas
    $tabla_propuestas = $wpdb->prefix . 'flavor_participacion_propuestas';
    $sql_propuestas = "CREATE TABLE IF NOT EXISTS $tabla_propuestas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        titulo varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        descripcion text NOT NULL,
        contenido longtext DEFAULT NULL,
        categoria varchar(100) DEFAULT NULL,
        ambito varchar(50) DEFAULT 'barrio',
        imagen varchar(500) DEFAULT NULL,
        documentos text DEFAULT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        usuario_nombre varchar(200) DEFAULT NULL,
        estado enum('borrador','pendiente','en_revision','aprobada','rechazada','en_votacion','aceptada','implementada','archivada') NOT NULL DEFAULT 'borrador',
        motivo_rechazo text DEFAULT NULL,
        fase_actual varchar(50) DEFAULT NULL,
        votos_favor int(11) NOT NULL DEFAULT 0,
        votos_contra int(11) NOT NULL DEFAULT 0,
        votos_abstencion int(11) NOT NULL DEFAULT 0,
        comentarios_count int(11) NOT NULL DEFAULT 0,
        visualizaciones int(11) NOT NULL DEFAULT 0,
        presupuesto_estimado decimal(12,2) DEFAULT NULL,
        ubicacion_texto varchar(500) DEFAULT NULL,
        latitud decimal(10,8) DEFAULT NULL,
        longitud decimal(11,8) DEFAULT NULL,
        fecha_inicio_votacion datetime DEFAULT NULL,
        fecha_fin_votacion datetime DEFAULT NULL,
        fecha_implementacion date DEFAULT NULL,
        responsable_id bigint(20) unsigned DEFAULT NULL,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY categoria (categoria),
        KEY ambito (ambito),
        KEY estado (estado),
        KEY usuario_id (usuario_id),
        KEY votos_favor (votos_favor),
        KEY created_at (created_at),
        FULLTEXT KEY busqueda (titulo, descripcion)
    ) $charset_collate;";

    // Tabla de votos
    $tabla_votos = $wpdb->prefix . 'flavor_participacion_votos';
    $sql_votos = "CREATE TABLE IF NOT EXISTS $tabla_votos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        propuesta_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        tipo_voto enum('favor','contra','abstencion') NOT NULL,
        comentario text DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY voto_unico (propuesta_id, usuario_id),
        KEY propuesta_id (propuesta_id),
        KEY usuario_id (usuario_id),
        KEY tipo_voto (tipo_voto)
    ) $charset_collate;";

    // Tabla de comentarios/debates
    $tabla_comentarios = $wpdb->prefix . 'flavor_participacion_comentarios';
    $sql_comentarios = "CREATE TABLE IF NOT EXISTS $tabla_comentarios (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        propuesta_id bigint(20) unsigned NOT NULL,
        padre_id bigint(20) unsigned DEFAULT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        usuario_nombre varchar(200) DEFAULT NULL,
        contenido text NOT NULL,
        tipo enum('comentario','pregunta','respuesta_oficial','moderacion') NOT NULL DEFAULT 'comentario',
        likes_count int(11) NOT NULL DEFAULT 0,
        es_oficial tinyint(1) NOT NULL DEFAULT 0,
        estado enum('publicado','oculto','eliminado') NOT NULL DEFAULT 'publicado',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY propuesta_id (propuesta_id),
        KEY padre_id (padre_id),
        KEY usuario_id (usuario_id),
        KEY tipo (tipo),
        KEY estado (estado)
    ) $charset_collate;";

    // Tabla de encuestas
    $tabla_encuestas = $wpdb->prefix . 'flavor_participacion_encuestas';
    $sql_encuestas = "CREATE TABLE IF NOT EXISTS $tabla_encuestas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        titulo varchar(255) NOT NULL,
        descripcion text DEFAULT NULL,
        tipo enum('simple','multiple','escala','abierta') NOT NULL DEFAULT 'simple',
        opciones json DEFAULT NULL,
        fecha_inicio datetime NOT NULL,
        fecha_fin datetime NOT NULL,
        estado enum('borrador','activa','cerrada','archivada') NOT NULL DEFAULT 'borrador',
        anonima tinyint(1) NOT NULL DEFAULT 0,
        solo_socios tinyint(1) NOT NULL DEFAULT 0,
        respuestas_count int(11) NOT NULL DEFAULT 0,
        creado_por bigint(20) unsigned DEFAULT NULL,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY estado (estado),
        KEY fecha_inicio (fecha_inicio),
        KEY fecha_fin (fecha_fin)
    ) $charset_collate;";

    // Tabla de respuestas a encuestas
    $tabla_respuestas = $wpdb->prefix . 'flavor_participacion_respuestas';
    $sql_respuestas = "CREATE TABLE IF NOT EXISTS $tabla_respuestas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        encuesta_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned DEFAULT NULL,
        respuesta json NOT NULL,
        ip_hash varchar(64) DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY encuesta_id (encuesta_id),
        KEY usuario_id (usuario_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_propuestas);
    dbDelta($sql_votos);
    dbDelta($sql_comentarios);
    dbDelta($sql_encuestas);
    dbDelta($sql_respuestas);

    update_option('flavor_participacion_db_version', '1.0.0');
}

/**
 * Elimina las tablas de participación
 *
 * @return void
 */
function flavor_participacion_eliminar_tablas() {
    global $wpdb;

    $tablas = [
        $wpdb->prefix . 'flavor_participacion_propuestas',
        $wpdb->prefix . 'flavor_participacion_votos',
        $wpdb->prefix . 'flavor_participacion_comentarios',
        $wpdb->prefix . 'flavor_participacion_encuestas',
        $wpdb->prefix . 'flavor_participacion_respuestas',
    ];

    foreach ($tablas as $tabla) {
        $wpdb->query("DROP TABLE IF EXISTS $tabla");
    }

    delete_option('flavor_participacion_db_version');
}
