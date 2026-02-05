<?php
/**
 * Instalacion de tablas para el modulo de Reservas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea la tabla de reservas en la base de datos
 *
 * Usa dbDelta de WordPress para crear o actualizar la tabla.
 *
 * @return void
 */
function flavor_reservas_crear_tabla() {
    global $wpdb;

    $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';
    $charset_collate       = $wpdb->get_charset_collate();

    $sql_reservas = "CREATE TABLE IF NOT EXISTS $nombre_tabla_reservas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        tipo_servicio varchar(100) NOT NULL DEFAULT 'mesa_restaurante',
        nombre_cliente varchar(200) NOT NULL,
        email_cliente varchar(200) NOT NULL,
        telefono_cliente varchar(50) DEFAULT NULL,
        fecha_reserva date NOT NULL,
        hora_inicio time NOT NULL,
        hora_fin time NOT NULL,
        num_personas int(11) NOT NULL DEFAULT 1,
        estado varchar(30) NOT NULL DEFAULT 'pendiente',
        notas text DEFAULT NULL,
        user_id bigint(20) unsigned DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY tipo_servicio (tipo_servicio),
        KEY fecha_reserva (fecha_reserva),
        KEY estado (estado),
        KEY user_id (user_id),
        KEY email_cliente (email_cliente),
        KEY fecha_estado (fecha_reserva, estado)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_reservas);

    update_option('flavor_reservas_db_version', '1.0.0');
}

/**
 * Elimina la tabla de reservas de la base de datos
 *
 * @return void
 */
function flavor_reservas_eliminar_tabla() {
    global $wpdb;

    $nombre_tabla_reservas = $wpdb->prefix . 'flavor_reservas';
    $wpdb->query("DROP TABLE IF EXISTS $nombre_tabla_reservas");
    delete_option('flavor_reservas_db_version');
}
