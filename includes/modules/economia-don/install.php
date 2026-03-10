<?php
/**
 * Instalador de tablas para Economía del Don
 *
 * @package FlavorChatIA
 * @subpackage Modules\EconomiaDon
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Instala las tablas necesarias para el módulo Economía del Don
 */
function flavor_economia_don_install_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $prefix = $wpdb->prefix . 'flavor_';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Tabla de dones ofrecidos
    $sql_dones = "CREATE TABLE {$prefix}economia_dones (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        titulo varchar(255) NOT NULL,
        descripcion text NOT NULL,
        categoria varchar(100) NOT NULL,
        condiciones text DEFAULT NULL,
        ubicacion varchar(255) DEFAULT NULL,
        imagen varchar(500) DEFAULT NULL,
        estado enum('disponible','reservado','entregado','recibido') DEFAULT 'disponible',
        fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY usuario_id (usuario_id),
        KEY estado (estado),
        KEY categoria (categoria),
        KEY fecha_creacion (fecha_creacion)
    ) $charset_collate;";

    // Tabla de solicitudes de dones
    $sql_solicitudes = "CREATE TABLE {$prefix}economia_solicitudes (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        don_id bigint(20) UNSIGNED NOT NULL,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        mensaje text DEFAULT NULL,
        estado enum('pendiente','aceptada','rechazada') DEFAULT 'pendiente',
        fecha datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY don_id (don_id),
        KEY usuario_id (usuario_id),
        KEY estado (estado)
    ) $charset_collate;";

    // Tabla de entregas de dones
    $sql_entregas = "CREATE TABLE {$prefix}economia_entregas (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        don_id bigint(20) UNSIGNED NOT NULL,
        donante_id bigint(20) UNSIGNED NOT NULL,
        receptor_id bigint(20) UNSIGNED NOT NULL,
        fecha_entrega datetime DEFAULT CURRENT_TIMESTAMP,
        notas text DEFAULT NULL,
        gratitud_enviada tinyint(1) DEFAULT 0,
        PRIMARY KEY (id),
        KEY don_id (don_id),
        KEY donante_id (donante_id),
        KEY receptor_id (receptor_id)
    ) $charset_collate;";

    // Tabla de gratitudes (muro de agradecimientos)
    $sql_gratitudes = "CREATE TABLE {$prefix}economia_gratitudes (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        don_id bigint(20) UNSIGNED NOT NULL,
        usuario_id bigint(20) UNSIGNED NOT NULL,
        mensaje text NOT NULL,
        publico tinyint(1) DEFAULT 1,
        fecha datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY don_id (don_id),
        KEY usuario_id (usuario_id),
        KEY publico (publico),
        KEY fecha (fecha)
    ) $charset_collate;";

    dbDelta($sql_dones);
    dbDelta($sql_solicitudes);
    dbDelta($sql_entregas);
    dbDelta($sql_gratitudes);

    update_option('flavor_economia_don_db_version', '1.0.0');

    return true;
}

/**
 * Verifica si las tablas están instaladas
 */
function flavor_economia_don_tables_installed() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_economia_dones';
    return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla)) === $tabla;
}

/**
 * Hook para instalar tablas cuando se activa el módulo
 */
add_action('admin_init', function() {
    // Solo ejecutar si no están instaladas las tablas
    if (!flavor_economia_don_tables_installed()) {
        // Verificar que el módulo está activo usando función centralizada
        $modulo_activo = false;

        if (class_exists('Flavor_Chat_Module_Loader')) {
            $modulo_activo = Flavor_Chat_Module_Loader::is_module_active('economia-don')
                          || Flavor_Chat_Module_Loader::is_module_active('economia_don');
        } else {
            // Fallback: verificar en ambas opciones
            $settings = get_option('flavor_chat_ia_settings', []);
            $modulos_activos = $settings['active_modules'] ?? [];
            $modulos_legacy = get_option('flavor_active_modules', []);
            $modulos_activos = array_unique(array_merge($modulos_activos, $modulos_legacy));

            $modulo_activo = in_array('economia_don', $modulos_activos, true)
                          || in_array('economia-don', $modulos_activos, true);
        }

        if ($modulo_activo) {
            flavor_economia_don_install_tables();
        }
    }
});
