<?php
/**
 * Instalación de tablas para el módulo de Clientes
 *
 * @package FlavorPlatform
 * @subpackage Modules\Clientes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea la tabla de clientes en la base de datos
 *
 * @return void
 */
function flavor_clientes_crear_tablas() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla principal de clientes
    $tabla_clientes = $wpdb->prefix . 'flavor_clientes';
    $sql_clientes = "CREATE TABLE IF NOT EXISTS $tabla_clientes (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        numero_cliente varchar(50) NOT NULL,
        usuario_id bigint(20) unsigned DEFAULT NULL,
        nombre varchar(255) NOT NULL,
        apellidos varchar(255) DEFAULT NULL,
        nombre_comercial varchar(255) DEFAULT NULL,
        email varchar(255) NOT NULL,
        telefono varchar(50) DEFAULT NULL,
        telefono_secundario varchar(50) DEFAULT NULL,
        movil varchar(50) DEFAULT NULL,
        empresa varchar(255) DEFAULT NULL,
        nif_cif varchar(20) DEFAULT NULL,
        tipo_documento enum('nif','cif','nie','pasaporte','otro') DEFAULT 'nif',
        direccion varchar(500) DEFAULT NULL,
        direccion_linea2 varchar(255) DEFAULT NULL,
        ciudad varchar(100) DEFAULT NULL,
        provincia varchar(100) DEFAULT NULL,
        codigo_postal varchar(10) DEFAULT NULL,
        pais varchar(100) DEFAULT 'España',
        direccion_envio varchar(500) DEFAULT NULL,
        ciudad_envio varchar(100) DEFAULT NULL,
        provincia_envio varchar(100) DEFAULT NULL,
        codigo_postal_envio varchar(10) DEFAULT NULL,
        pais_envio varchar(100) DEFAULT NULL,
        usa_direccion_envio tinyint(1) NOT NULL DEFAULT 0,
        tipo_cliente enum('particular','empresa','autonomo','profesional') DEFAULT 'particular',
        categoria varchar(50) DEFAULT 'general',
        estado enum('activo','inactivo','potencial','moroso','bloqueado') DEFAULT 'activo',
        fecha_alta date NOT NULL,
        fecha_baja date DEFAULT NULL,
        motivo_baja text DEFAULT NULL,
        origen varchar(100) DEFAULT NULL,
        web varchar(255) DEFAULT NULL,
        descuento_global decimal(5,2) DEFAULT 0.00,
        limite_credito decimal(10,2) DEFAULT NULL,
        dias_pago int(11) DEFAULT 30,
        forma_pago enum('contado','transferencia','domiciliacion','tarjeta','efectivo','cheque') DEFAULT 'transferencia',
        iban varchar(34) DEFAULT NULL,
        bic_swift varchar(11) DEFAULT NULL,
        mandato_sepa varchar(50) DEFAULT NULL,
        fecha_mandato_sepa date DEFAULT NULL,
        idioma varchar(5) DEFAULT 'es',
        moneda varchar(3) DEFAULT 'EUR',
        observaciones text DEFAULT NULL,
        notas_internas text DEFAULT NULL,
        imagen_perfil varchar(500) DEFAULT NULL,
        logo_empresa varchar(500) DEFAULT NULL,
        preferencias_comunicacion varchar(255) DEFAULT 'email,telefono',
        acepta_marketing tinyint(1) NOT NULL DEFAULT 0,
        acepta_newsletter tinyint(1) NOT NULL DEFAULT 0,
        ultima_compra date DEFAULT NULL,
        total_compras decimal(10,2) DEFAULT 0.00,
        num_facturas int(11) DEFAULT 0,
        puntos_fidelidad int(11) DEFAULT 0,
        nivel_fidelidad varchar(50) DEFAULT 'bronce',
        referido_por bigint(20) unsigned DEFAULT NULL,
        asignado_a bigint(20) unsigned DEFAULT NULL,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY numero_cliente (numero_cliente),
        UNIQUE KEY email (email),
        KEY usuario_id (usuario_id),
        KEY estado (estado),
        KEY tipo_cliente (tipo_cliente),
        KEY categoria (categoria),
        KEY fecha_alta (fecha_alta),
        KEY nif_cif (nif_cif),
        KEY asignado_a (asignado_a),
        FULLTEXT KEY busqueda (nombre, apellidos, email, empresa, nombre_comercial)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_clientes);

    // Insertar categorías por defecto
    flavor_clientes_insertar_datos_default();

    update_option('flavor_clientes_db_version', '1.0.0');
}

/**
 * Inserta datos por defecto para el módulo
 *
 * @return void
 */
function flavor_clientes_insertar_datos_default() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_clientes';

    // Verificar si ya hay clientes
    $existe = $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
    if ($existe > 0) {
        return;
    }

    // Aquí se podrían insertar clientes de ejemplo si fuera necesario
    // Por ahora dejamos la tabla vacía para datos reales
}

/**
 * Elimina la tabla de clientes
 *
 * @return void
 */
function flavor_clientes_eliminar_tablas() {
    global $wpdb;

    $tabla = $wpdb->prefix . 'flavor_clientes';
    $wpdb->query("DROP TABLE IF EXISTS $tabla");

    delete_option('flavor_clientes_db_version');
}
