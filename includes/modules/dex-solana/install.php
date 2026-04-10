<?php
/**
 * Instalacion de tablas para DEX Solana
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las 7 tablas necesarias para el modulo DEX Solana
 */
function flavor_dex_solana_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // 1. Tabla de historial de swaps
    $tabla_swaps = $wpdb->prefix . 'flavor_dex_swaps';
    $sql_swaps = "CREATE TABLE $tabla_swaps (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        swap_id varchar(50) NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        timestamp datetime DEFAULT NULL,
        modo varchar(10) NOT NULL DEFAULT 'paper',
        token_entrada_mint varchar(60) NOT NULL,
        token_entrada_simbolo varchar(20) NOT NULL,
        token_salida_mint varchar(60) NOT NULL,
        token_salida_simbolo varchar(20) NOT NULL,
        cantidad_entrada decimal(30,10) NOT NULL DEFAULT 0,
        cantidad_salida decimal(30,10) NOT NULL DEFAULT 0,
        precio_ejecucion decimal(30,10) NOT NULL DEFAULT 0,
        slippage_configurado decimal(5,2) DEFAULT 0.50,
        slippage_real decimal(5,4) DEFAULT 0,
        impacto_precio decimal(5,4) DEFAULT 0,
        ruta_json longtext DEFAULT NULL,
        comision_red_usd decimal(10,6) DEFAULT 0,
        comision_plataforma_usd decimal(10,6) DEFAULT 0,
        fees_total_usd decimal(10,6) DEFAULT 0,
        tx_hash varchar(100) DEFAULT NULL,
        estado varchar(20) DEFAULT 'completado',
        PRIMARY KEY (id),
        KEY swap_id (swap_id),
        KEY usuario_id (usuario_id),
        KEY timestamp (timestamp),
        KEY modo (modo),
        KEY estado (estado)
    ) $charset_collate;";

    // 2. Tabla de portfolio por usuario
    $tabla_portfolio = $wpdb->prefix . 'flavor_dex_portfolio';
    $sql_portfolio = "CREATE TABLE $tabla_portfolio (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        balance_usdc decimal(30,10) NOT NULL DEFAULT 1000,
        balance_sol decimal(30,10) NOT NULL DEFAULT 0,
        balance_inicial_usdc decimal(30,10) NOT NULL DEFAULT 1000,
        tokens_json longtext DEFAULT NULL,
        lp_posiciones_json longtext DEFAULT NULL,
        farming_posiciones_json longtext DEFAULT NULL,
        fees_acumuladas_usd decimal(10,6) DEFAULT 0,
        contador_swaps int(11) DEFAULT 0,
        modo_activo varchar(10) NOT NULL DEFAULT 'paper',
        wallet_address varchar(60) DEFAULT NULL,
        fecha_creacion datetime DEFAULT NULL,
        fecha_actualizacion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY usuario_id (usuario_id)
    ) $charset_collate;";

    // 3. Tabla de pools AMM simulados
    $tabla_pools = $wpdb->prefix . 'flavor_dex_pools';
    $sql_pools = "CREATE TABLE $tabla_pools (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        pool_id varchar(50) NOT NULL,
        token_a_mint varchar(60) NOT NULL,
        token_a_simbolo varchar(20) NOT NULL,
        token_b_mint varchar(60) NOT NULL,
        token_b_simbolo varchar(20) NOT NULL,
        reserva_a decimal(30,10) NOT NULL DEFAULT 0,
        reserva_b decimal(30,10) NOT NULL DEFAULT 0,
        constante_k decimal(60,10) NOT NULL DEFAULT 0,
        total_lp_tokens decimal(30,10) NOT NULL DEFAULT 0,
        fee_porcentaje decimal(5,4) NOT NULL DEFAULT 0.0030,
        volumen_24h_usd decimal(20,4) DEFAULT 0,
        fees_acumuladas_usd decimal(20,6) DEFAULT 0,
        apy_estimado decimal(10,4) DEFAULT 0,
        activo tinyint(1) DEFAULT 1,
        fecha_creacion datetime DEFAULT NULL,
        fecha_actualizacion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY pool_id (pool_id),
        KEY token_a_mint (token_a_mint),
        KEY token_b_mint (token_b_mint),
        KEY activo (activo)
    ) $charset_collate;";

    // 4. Tabla de posiciones LP por usuario
    $tabla_lp_posiciones = $wpdb->prefix . 'flavor_dex_lp_posiciones';
    $sql_lp_posiciones = "CREATE TABLE $tabla_lp_posiciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        pool_id varchar(50) NOT NULL,
        lp_tokens decimal(30,10) NOT NULL DEFAULT 0,
        token_a_depositado decimal(30,10) NOT NULL DEFAULT 0,
        token_b_depositado decimal(30,10) NOT NULL DEFAULT 0,
        valor_entrada_usd decimal(20,4) NOT NULL DEFAULT 0,
        rewards_acumulados_json longtext DEFAULT NULL,
        staked tinyint(1) DEFAULT 0,
        fecha_creacion datetime DEFAULT NULL,
        fecha_actualizacion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY usuario_id (usuario_id),
        KEY pool_id (pool_id),
        KEY staked (staked)
    ) $charset_collate;";

    // 5. Tabla de programas de farming
    $tabla_farming = $wpdb->prefix . 'flavor_dex_farming';
    $sql_farming = "CREATE TABLE $tabla_farming (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        programa_id varchar(50) NOT NULL,
        pool_id varchar(50) NOT NULL,
        reward_token_mint varchar(60) NOT NULL,
        reward_token_simbolo varchar(20) NOT NULL,
        reward_por_segundo decimal(20,10) NOT NULL DEFAULT 0,
        total_staked_lp decimal(30,10) NOT NULL DEFAULT 0,
        duracion_dias int(11) NOT NULL DEFAULT 90,
        fecha_inicio datetime DEFAULT NULL,
        fecha_fin datetime DEFAULT NULL,
        activo tinyint(1) DEFAULT 1,
        PRIMARY KEY (id),
        UNIQUE KEY programa_id (programa_id),
        KEY pool_id (pool_id),
        KEY activo (activo)
    ) $charset_collate;";

    // 6. Tabla de cache de metadatos de tokens
    $tabla_tokens = $wpdb->prefix . 'flavor_dex_tokens';
    $sql_tokens = "CREATE TABLE $tabla_tokens (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        mint_address varchar(60) NOT NULL,
        simbolo varchar(20) NOT NULL,
        nombre varchar(100) NOT NULL,
        decimales int(3) NOT NULL DEFAULT 9,
        logo_url varchar(500) DEFAULT NULL,
        coingecko_id varchar(100) DEFAULT NULL,
        verificado tinyint(1) DEFAULT 0,
        precio_usd decimal(30,10) DEFAULT 0,
        precio_actualizado datetime DEFAULT NULL,
        fecha_registro datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY mint_address (mint_address),
        KEY simbolo (simbolo),
        KEY verificado (verificado)
    ) $charset_collate;";

    // 7. Tabla de historial de decisiones IA
    $tabla_decisiones = $wpdb->prefix . 'flavor_dex_decisiones_ia';
    $sql_decisiones = "CREATE TABLE $tabla_decisiones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        timestamp datetime DEFAULT NULL,
        accion varchar(50) NOT NULL,
        detalles_json longtext DEFAULT NULL,
        confianza decimal(5,2) DEFAULT 0,
        razonamiento text DEFAULT NULL,
        proveedor_usado varchar(50) DEFAULT '',
        tiempo_respuesta_ms int(11) DEFAULT 0,
        PRIMARY KEY (id),
        KEY usuario_id (usuario_id),
        KEY timestamp (timestamp)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_swaps);
    dbDelta($sql_portfolio);
    dbDelta($sql_pools);
    dbDelta($sql_lp_posiciones);
    dbDelta($sql_farming);
    dbDelta($sql_tokens);
    dbDelta($sql_decisiones);
}
