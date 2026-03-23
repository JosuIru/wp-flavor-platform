<?php
/**
 * Instalación de tablas para el módulo Bug Tracker
 *
 * @package Flavor_Chat_IA
 * @subpackage Bug_Tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas necesarias para el módulo Bug Tracker
 *
 * @return void
 */
function flavor_bug_tracker_crear_tablas() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla principal de reportes de bugs
    $tabla_bug_reports = $wpdb->prefix . 'flavor_bug_reports';
    $sql_bug_reports = "CREATE TABLE IF NOT EXISTS $tabla_bug_reports (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        codigo varchar(20) NOT NULL,
        tipo enum('error_php','exception','warning','notice','manual','crash','deprecation') NOT NULL DEFAULT 'error_php',
        severidad enum('critical','high','medium','low','info') NOT NULL DEFAULT 'medium',
        titulo varchar(500) NOT NULL,
        mensaje text DEFAULT NULL,
        stack_trace longtext DEFAULT NULL,
        archivo varchar(500) DEFAULT NULL,
        linea int(11) DEFAULT NULL,
        modulo_id varchar(50) DEFAULT NULL,
        hash_fingerprint varchar(64) NOT NULL,
        ocurrencias int(11) NOT NULL DEFAULT 1,
        primera_ocurrencia datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ultima_ocurrencia datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        estado enum('nuevo','abierto','resuelto','ignorado') NOT NULL DEFAULT 'nuevo',
        asignado_a bigint(20) unsigned DEFAULT NULL,
        resuelto_por bigint(20) unsigned DEFAULT NULL,
        resuelto_at datetime DEFAULT NULL,
        contexto_request json DEFAULT NULL,
        contexto_servidor json DEFAULT NULL,
        contexto_usuario json DEFAULT NULL,
        contexto_extra json DEFAULT NULL,
        notas text DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY codigo (codigo),
        UNIQUE KEY hash_fingerprint (hash_fingerprint),
        KEY tipo (tipo),
        KEY severidad (severidad),
        KEY estado (estado),
        KEY modulo_id (modulo_id),
        KEY ultima_ocurrencia (ultima_ocurrencia),
        KEY asignado_a (asignado_a)
    ) $charset_collate;";

    // Tabla de configuración de canales de notificación
    $tabla_bug_channels = $wpdb->prefix . 'flavor_bug_channels';
    $sql_bug_channels = "CREATE TABLE IF NOT EXISTS $tabla_bug_channels (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(100) NOT NULL,
        tipo enum('slack','discord','email','webhook') NOT NULL,
        webhook_url varchar(500) DEFAULT NULL,
        email_destinatarios text DEFAULT NULL,
        severidad_minima enum('critical','high','medium','low','info') NOT NULL DEFAULT 'high',
        tipos_incluidos json DEFAULT NULL,
        modulos_incluidos json DEFAULT NULL,
        activo tinyint(1) NOT NULL DEFAULT 1,
        ultimo_envio datetime DEFAULT NULL,
        envios_exitosos int(11) NOT NULL DEFAULT 0,
        envios_fallidos int(11) NOT NULL DEFAULT 0,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY tipo (tipo),
        KEY activo (activo),
        KEY severidad_minima (severidad_minima)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_bug_reports);
    dbDelta($sql_bug_channels);

    // Insertar canales por defecto si no existen
    $canales_existentes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_bug_channels");
    if ($canales_existentes == 0) {
        // Canal de email para administradores
        $wpdb->insert($tabla_bug_channels, [
            'nombre' => 'Email Administradores',
            'tipo' => 'email',
            'email_destinatarios' => get_option('admin_email'),
            'severidad_minima' => 'high',
            'activo' => 1,
        ]);
    }

    update_option('flavor_bug_tracker_db_version', '1.0.0');
}

/**
 * Elimina las tablas del módulo Bug Tracker
 *
 * @return void
 */
function flavor_bug_tracker_eliminar_tablas() {
    global $wpdb;

    $tablas = [
        $wpdb->prefix . 'flavor_bug_reports',
        $wpdb->prefix . 'flavor_bug_channels',
    ];

    foreach ($tablas as $tabla) {
        $wpdb->query("DROP TABLE IF EXISTS $tabla");
    }

    delete_option('flavor_bug_tracker_db_version');
}

/**
 * Actualiza las tablas si es necesario
 *
 * @return void
 */
function flavor_bug_tracker_actualizar_tablas() {
    $version_instalada = get_option('flavor_bug_tracker_db_version', '0.0.0');
    $version_actual = '1.0.0';

    if (version_compare($version_instalada, $version_actual, '<')) {
        flavor_bug_tracker_crear_tablas();
    }
}
