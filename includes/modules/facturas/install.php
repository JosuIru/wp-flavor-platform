<?php
/**
 * Instalación de tablas para el módulo de Facturas
 *
 * @package FlavorPlatform
 * @subpackage Modules\Facturas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas de facturas en la base de datos
 *
 * @return void
 */
function flavor_facturas_crear_tablas() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // 1. Tabla principal de facturas
    $tabla_facturas = $wpdb->prefix . 'flavor_facturas';
    $sql_facturas = "CREATE TABLE IF NOT EXISTS $tabla_facturas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        numero_factura varchar(50) NOT NULL,
        serie varchar(10) DEFAULT 'A',
        ejercicio int(4) NOT NULL,
        cliente_id bigint(20) unsigned NOT NULL,
        cliente_nombre varchar(255) NOT NULL,
        cliente_nif varchar(20) DEFAULT NULL,
        cliente_direccion text DEFAULT NULL,
        tipo_factura enum('ordinaria','simplificada','rectificativa','proforma') DEFAULT 'ordinaria',
        factura_rectificada_id bigint(20) unsigned DEFAULT NULL,
        motivo_rectificacion text DEFAULT NULL,
        fecha_emision date NOT NULL,
        fecha_vencimiento date DEFAULT NULL,
        fecha_operacion date DEFAULT NULL,
        base_imponible decimal(10,2) NOT NULL DEFAULT 0.00,
        total_iva decimal(10,2) NOT NULL DEFAULT 0.00,
        total_recargo decimal(10,2) DEFAULT 0.00,
        total_irpf decimal(10,2) DEFAULT 0.00,
        total_factura decimal(10,2) NOT NULL DEFAULT 0.00,
        total_pagado decimal(10,2) DEFAULT 0.00,
        pendiente_pago decimal(10,2) DEFAULT 0.00,
        estado enum('borrador','emitida','enviada','pagada','parcialmente_pagada','vencida','cancelada','anulada') NOT NULL DEFAULT 'borrador',
        metodo_pago varchar(50) DEFAULT NULL,
        metodo_pago_descripcion varchar(255) DEFAULT NULL,
        forma_pago enum('contado','30_dias','60_dias','90_dias','transferencia','domiciliacion','tarjeta','efectivo','personalizado') DEFAULT 'contado',
        iban_cliente varchar(34) DEFAULT NULL,
        observaciones text DEFAULT NULL,
        notas_internas text DEFAULT NULL,
        pie_factura text DEFAULT NULL,
        pdf_url varchar(500) DEFAULT NULL,
        pdf_generado_at datetime DEFAULT NULL,
        xml_url varchar(500) DEFAULT NULL,
        enviada_email tinyint(1) NOT NULL DEFAULT 0,
        fecha_envio_email datetime DEFAULT NULL,
        destinatario_email varchar(255) DEFAULT NULL,
        num_envios_email int(11) DEFAULT 0,
        descuento_global decimal(10,2) DEFAULT 0.00,
        descuento_tipo enum('porcentaje','fijo') DEFAULT 'porcentaje',
        moneda varchar(3) DEFAULT 'EUR',
        tipo_cambio decimal(10,6) DEFAULT 1.000000,
        idioma varchar(5) DEFAULT 'es',
        origen varchar(50) DEFAULT 'manual',
        relacionada_con varchar(255) DEFAULT NULL,
        proyecto_id bigint(20) unsigned DEFAULT NULL,
        usuario_creador bigint(20) unsigned DEFAULT NULL,
        usuario_modificador bigint(20) unsigned DEFAULT NULL,
        ip_creacion varchar(45) DEFAULT NULL,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY numero_ejercicio (numero_factura, serie, ejercicio),
        KEY cliente_id (cliente_id),
        KEY estado (estado),
        KEY fecha_emision (fecha_emision),
        KEY fecha_vencimiento (fecha_vencimiento),
        KEY ejercicio (ejercicio),
        KEY serie (serie),
        KEY tipo_factura (tipo_factura),
        KEY estado_fecha (estado, fecha_emision),
        FULLTEXT KEY busqueda (numero_factura, cliente_nombre, observaciones)
    ) $charset_collate;";

    // 2. Tabla de líneas de factura
    $tabla_lineas = $wpdb->prefix . 'flavor_facturas_lineas';
    $sql_lineas = "CREATE TABLE IF NOT EXISTS $tabla_lineas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        factura_id bigint(20) unsigned NOT NULL,
        producto_servicio_id bigint(20) unsigned DEFAULT NULL,
        tipo enum('producto','servicio','descuento','gasto') DEFAULT 'producto',
        concepto varchar(500) NOT NULL,
        descripcion text DEFAULT NULL,
        cantidad decimal(10,3) NOT NULL DEFAULT 1.000,
        unidad varchar(20) DEFAULT 'ud',
        precio_unitario decimal(10,2) NOT NULL DEFAULT 0.00,
        descuento decimal(10,2) DEFAULT 0.00,
        descuento_tipo enum('porcentaje','fijo') DEFAULT 'porcentaje',
        subtotal_sin_iva decimal(10,2) NOT NULL DEFAULT 0.00,
        iva_porcentaje decimal(5,2) NOT NULL DEFAULT 21.00,
        iva_importe decimal(10,2) NOT NULL DEFAULT 0.00,
        recargo_equivalencia decimal(5,2) DEFAULT 0.00,
        recargo_importe decimal(10,2) DEFAULT 0.00,
        irpf_porcentaje decimal(5,2) DEFAULT 0.00,
        irpf_importe decimal(10,2) DEFAULT 0.00,
        total_linea decimal(10,2) NOT NULL DEFAULT 0.00,
        orden int(11) NOT NULL DEFAULT 0,
        metadata json DEFAULT NULL,
        PRIMARY KEY (id),
        KEY factura_id (factura_id),
        KEY producto_servicio_id (producto_servicio_id),
        KEY tipo (tipo)
    ) $charset_collate;";

    // 3. Tabla de pagos de facturas
    $tabla_pagos = $wpdb->prefix . 'flavor_facturas_pagos';
    $sql_pagos = "CREATE TABLE IF NOT EXISTS $tabla_pagos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        factura_id bigint(20) unsigned NOT NULL,
        importe decimal(10,2) NOT NULL,
        fecha_pago date NOT NULL,
        metodo_pago varchar(50) NOT NULL,
        metodo_pago_descripcion varchar(255) DEFAULT NULL,
        referencia varchar(100) DEFAULT NULL,
        numero_transaccion varchar(100) DEFAULT NULL,
        cuenta_bancaria varchar(34) DEFAULT NULL,
        notas text DEFAULT NULL,
        gateway varchar(50) DEFAULT NULL,
        gateway_transaction_id varchar(100) DEFAULT NULL,
        gateway_status varchar(50) DEFAULT NULL,
        gateway_fee decimal(10,2) DEFAULT 0.00,
        gateway_metadata json DEFAULT NULL,
        documento_pago varchar(500) DEFAULT NULL,
        usuario_registro bigint(20) unsigned DEFAULT NULL,
        estado enum('pendiente','completado','fallido','reembolsado','cancelado') DEFAULT 'completado',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY factura_id (factura_id),
        KEY metodo_pago (metodo_pago),
        KEY fecha_pago (fecha_pago),
        KEY gateway (gateway),
        KEY estado (estado)
    ) $charset_collate;";

    // 4. Tabla de series de facturación
    $tabla_series = $wpdb->prefix . 'flavor_facturas_series';
    $sql_series = "CREATE TABLE IF NOT EXISTS $tabla_series (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        serie varchar(10) NOT NULL,
        nombre varchar(100) NOT NULL,
        descripcion text DEFAULT NULL,
        prefijo varchar(20) DEFAULT NULL,
        sufijo varchar(20) DEFAULT NULL,
        siguiente_numero int(11) NOT NULL DEFAULT 1,
        digitos int(11) NOT NULL DEFAULT 6,
        formato_numero varchar(50) DEFAULT '{PREFIJO}{NUMERO}{SUFIJO}',
        tipo_serie enum('ordinaria','simplificada','rectificativa','proforma') DEFAULT 'ordinaria',
        ejercicio_actual int(4) DEFAULT NULL,
        reiniciar_por_ejercicio tinyint(1) NOT NULL DEFAULT 1,
        activa tinyint(1) NOT NULL DEFAULT 1,
        por_defecto tinyint(1) NOT NULL DEFAULT 0,
        color varchar(7) DEFAULT '#3b82f6',
        orden int(11) NOT NULL DEFAULT 0,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY serie (serie),
        KEY activa (activa),
        KEY tipo_serie (tipo_serie)
    ) $charset_collate;";

    // 5. Tabla de impuestos (IVA, IRPF, etc.)
    $tabla_impuestos = $wpdb->prefix . 'flavor_facturas_impuestos';
    $sql_impuestos = "CREATE TABLE IF NOT EXISTS $tabla_impuestos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(100) NOT NULL,
        tipo enum('iva','irpf','recargo','otro') NOT NULL,
        porcentaje decimal(5,2) NOT NULL,
        descripcion text DEFAULT NULL,
        pais varchar(3) DEFAULT 'ES',
        activo tinyint(1) NOT NULL DEFAULT 1,
        por_defecto tinyint(1) NOT NULL DEFAULT 0,
        fecha_inicio date DEFAULT NULL,
        fecha_fin date DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY tipo (tipo),
        KEY activo (activo),
        KEY pais (pais)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_facturas);
    dbDelta($sql_lineas);
    dbDelta($sql_pagos);
    dbDelta($sql_series);
    dbDelta($sql_impuestos);

    // Insertar datos por defecto
    flavor_facturas_insertar_datos_default();

    update_option('flavor_facturas_db_version', '1.0.0');
}

/**
 * Inserta datos por defecto para el módulo
 *
 * @return void
 */
function flavor_facturas_insertar_datos_default() {
    global $wpdb;

    // 1. Serie por defecto
    $tabla_series = $wpdb->prefix . 'flavor_facturas_series';
    $existe_series = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_series");

    if ($existe_series == 0) {
        $ejercicio_actual = date('Y');

        $series_default = [
            [
                'serie' => 'A',
                'nombre' => 'Serie General',
                'descripcion' => 'Serie principal para facturas ordinarias',
                'prefijo' => 'FAC',
                'siguiente_numero' => 1,
                'digitos' => 6,
                'tipo_serie' => 'ordinaria',
                'ejercicio_actual' => $ejercicio_actual,
                'reiniciar_por_ejercicio' => 1,
                'activa' => 1,
                'por_defecto' => 1,
                'color' => '#3b82f6',
                'orden' => 1,
            ],
            [
                'serie' => 'R',
                'nombre' => 'Serie Rectificativas',
                'descripcion' => 'Serie para facturas rectificativas',
                'prefijo' => 'RECT',
                'siguiente_numero' => 1,
                'digitos' => 6,
                'tipo_serie' => 'rectificativa',
                'ejercicio_actual' => $ejercicio_actual,
                'reiniciar_por_ejercicio' => 1,
                'activa' => 1,
                'por_defecto' => 0,
                'color' => '#ef4444',
                'orden' => 2,
            ],
            [
                'serie' => 'P',
                'nombre' => 'Serie Proforma',
                'descripcion' => 'Serie para presupuestos y proformas',
                'prefijo' => 'PRO',
                'siguiente_numero' => 1,
                'digitos' => 6,
                'tipo_serie' => 'proforma',
                'ejercicio_actual' => $ejercicio_actual,
                'reiniciar_por_ejercicio' => 1,
                'activa' => 1,
                'por_defecto' => 0,
                'color' => '#f59e0b',
                'orden' => 3,
            ],
        ];

        foreach ($series_default as $serie) {
            $wpdb->insert($tabla_series, $serie);
        }
    }

    // 2. Tipos de IVA por defecto (España)
    $tabla_impuestos = $wpdb->prefix . 'flavor_facturas_impuestos';
    $existe_impuestos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_impuestos");

    if ($existe_impuestos == 0) {
        $impuestos_default = [
            // IVA
            [
                'nombre' => 'IVA General',
                'tipo' => 'iva',
                'porcentaje' => 21.00,
                'descripcion' => 'IVA General 21%',
                'pais' => 'ES',
                'activo' => 1,
                'por_defecto' => 1,
            ],
            [
                'nombre' => 'IVA Reducido',
                'tipo' => 'iva',
                'porcentaje' => 10.00,
                'descripcion' => 'IVA Reducido 10%',
                'pais' => 'ES',
                'activo' => 1,
                'por_defecto' => 0,
            ],
            [
                'nombre' => 'IVA Superreducido',
                'tipo' => 'iva',
                'porcentaje' => 4.00,
                'descripcion' => 'IVA Superreducido 4%',
                'pais' => 'ES',
                'activo' => 1,
                'por_defecto' => 0,
            ],
            [
                'nombre' => 'IVA Exento',
                'tipo' => 'iva',
                'porcentaje' => 0.00,
                'descripcion' => 'Exento de IVA',
                'pais' => 'ES',
                'activo' => 1,
                'por_defecto' => 0,
            ],
            // Recargo de Equivalencia
            [
                'nombre' => 'Recargo 5.2%',
                'tipo' => 'recargo',
                'porcentaje' => 5.20,
                'descripcion' => 'Recargo de Equivalencia 5.2% (sobre IVA 21%)',
                'pais' => 'ES',
                'activo' => 1,
                'por_defecto' => 0,
            ],
            [
                'nombre' => 'Recargo 1.4%',
                'tipo' => 'recargo',
                'porcentaje' => 1.40,
                'descripcion' => 'Recargo de Equivalencia 1.4% (sobre IVA 10%)',
                'pais' => 'ES',
                'activo' => 1,
                'por_defecto' => 0,
            ],
            [
                'nombre' => 'Recargo 0.5%',
                'tipo' => 'recargo',
                'porcentaje' => 0.50,
                'descripcion' => 'Recargo de Equivalencia 0.5% (sobre IVA 4%)',
                'pais' => 'ES',
                'activo' => 1,
                'por_defecto' => 0,
            ],
            // IRPF
            [
                'nombre' => 'IRPF 15%',
                'tipo' => 'irpf',
                'porcentaje' => 15.00,
                'descripcion' => 'Retención IRPF 15% (profesionales)',
                'pais' => 'ES',
                'activo' => 1,
                'por_defecto' => 0,
            ],
            [
                'nombre' => 'IRPF 7%',
                'tipo' => 'irpf',
                'porcentaje' => 7.00,
                'descripcion' => 'Retención IRPF 7% (actividades profesionales)',
                'pais' => 'ES',
                'activo' => 1,
                'por_defecto' => 0,
            ],
        ];

        foreach ($impuestos_default as $impuesto) {
            $wpdb->insert($tabla_impuestos, $impuesto);
        }
    }
}

/**
 * Elimina las tablas de facturas
 *
 * @return void
 */
function flavor_facturas_eliminar_tablas() {
    global $wpdb;

    $tablas = [
        $wpdb->prefix . 'flavor_facturas',
        $wpdb->prefix . 'flavor_facturas_lineas',
        $wpdb->prefix . 'flavor_facturas_pagos',
        $wpdb->prefix . 'flavor_facturas_series',
        $wpdb->prefix . 'flavor_facturas_impuestos',
    ];

    foreach ($tablas as $tabla) {
        $wpdb->query("DROP TABLE IF EXISTS $tabla");
    }

    delete_option('flavor_facturas_db_version');
}
