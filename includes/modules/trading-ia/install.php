<?php
/**
 * Instalacion de tablas para Trading IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas necesarias para el modulo Trading IA
 */
function flavor_trading_ia_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Tabla de trades (historial de operaciones)
    $tabla_trades = $wpdb->prefix . 'flavor_trading_ia_trades';
    $sql_trades = "CREATE TABLE $tabla_trades (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        trade_id varchar(50) NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        timestamp datetime DEFAULT NULL,
        tipo varchar(10) NOT NULL,
        token_comprado varchar(20) NOT NULL,
        token_vendido varchar(20) NOT NULL,
        cantidad_comprada decimal(20,8) NOT NULL DEFAULT 0,
        cantidad_vendida decimal(20,8) NOT NULL DEFAULT 0,
        precio decimal(20,8) NOT NULL DEFAULT 0,
        pnl decimal(10,4) DEFAULT 0,
        comision_red_usd decimal(10,6) DEFAULT 0,
        comision_dex_usd decimal(10,6) DEFAULT 0,
        slippage_usd decimal(10,6) DEFAULT 0,
        fees_total_usd decimal(10,6) DEFAULT 0,
        PRIMARY KEY (id),
        KEY trade_id (trade_id),
        KEY usuario_id (usuario_id),
        KEY timestamp (timestamp),
        KEY tipo (tipo)
    ) $charset_collate;";

    // Tabla de portfolio (estado del portfolio por usuario)
    $tabla_portfolio = $wpdb->prefix . 'flavor_trading_ia_portfolio';
    $sql_portfolio = "CREATE TABLE $tabla_portfolio (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        balance_usd decimal(20,8) NOT NULL DEFAULT 1000,
        balance_inicial decimal(20,8) NOT NULL DEFAULT 1000,
        tokens_json longtext DEFAULT NULL,
        precios_entrada_json longtext DEFAULT NULL,
        fees_acumuladas_usd decimal(10,6) DEFAULT 0,
        contador_trades int(11) DEFAULT 0,
        fecha_creacion datetime DEFAULT NULL,
        fecha_actualizacion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY usuario_id (usuario_id)
    ) $charset_collate;";

    // Tabla de decisiones IA
    $tabla_decisiones = $wpdb->prefix . 'flavor_trading_ia_decisiones';
    $sql_decisiones = "CREATE TABLE $tabla_decisiones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        timestamp datetime DEFAULT NULL,
        accion varchar(10) NOT NULL,
        token varchar(20) DEFAULT '',
        cantidad_porcentaje decimal(5,2) DEFAULT 0,
        confianza decimal(5,2) DEFAULT 0,
        razonamiento text DEFAULT NULL,
        proveedor_usado varchar(50) DEFAULT '',
        tiempo_respuesta_ms int(11) DEFAULT 0,
        PRIMARY KEY (id),
        KEY usuario_id (usuario_id),
        KEY timestamp (timestamp)
    ) $charset_collate;";

    // Tabla de reglas dinamicas
    $tabla_reglas = $wpdb->prefix . 'flavor_trading_ia_reglas';
    $sql_reglas = "CREATE TABLE $tabla_reglas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        regla_id varchar(50) NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        nombre varchar(255) NOT NULL,
        token_condicion varchar(20) DEFAULT '*',
        indicador varchar(50) NOT NULL,
        operador varchar(5) NOT NULL,
        valor decimal(20,8) NOT NULL,
        accion_tipo varchar(50) NOT NULL,
        accion_parametros_json text DEFAULT NULL,
        activa tinyint(1) DEFAULT 1,
        creada_por varchar(10) DEFAULT 'ia',
        razon text DEFAULT NULL,
        veces_activada int(11) DEFAULT 0,
        ultima_activacion datetime DEFAULT NULL,
        fecha_creacion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY regla_id (regla_id),
        KEY usuario_id (usuario_id),
        KEY activa (activa)
    ) $charset_collate;";

    // Tabla de historial de auto-ajustes
    $tabla_ajustes = $wpdb->prefix . 'flavor_trading_ia_ajustes';
    $sql_ajustes = "CREATE TABLE $tabla_ajustes (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        timestamp datetime DEFAULT NULL,
        parametro varchar(100) NOT NULL,
        valor_anterior decimal(10,4) NOT NULL,
        valor_nuevo decimal(10,4) NOT NULL,
        razon text DEFAULT NULL,
        PRIMARY KEY (id),
        KEY usuario_id (usuario_id),
        KEY timestamp (timestamp)
    ) $charset_collate;";

    // Tabla de historial de precios para indicadores tecnicos
    $tabla_indicadores = $wpdb->prefix . 'flavor_trading_ia_indicadores';
    $sql_indicadores = "CREATE TABLE $tabla_indicadores (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        token varchar(20) NOT NULL,
        precio decimal(20,8) NOT NULL,
        volumen decimal(20,2) DEFAULT 0,
        timestamp datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY token (token),
        KEY timestamp (timestamp)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_trades);
    dbDelta($sql_portfolio);
    dbDelta($sql_decisiones);
    dbDelta($sql_reglas);
    dbDelta($sql_ajustes);
    dbDelta($sql_indicadores);
}
