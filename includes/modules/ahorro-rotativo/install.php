<?php
/**
 * Instalación de tablas para Ahorro Rotativo (panderos / tandas / círculos).
 *
 * La plataforma REGISTRA y COORDINA el ahorro rotativo; no custodia ni
 * transfiere dinero (eso evita licencias/KYC). La confianza se autorregula
 * con transparencia y la reputación derivada de las aportaciones.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas necesarias para el módulo Ahorro Rotativo.
 */
function flavor_ahorro_rotativo_crear_tablas() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Círculos de ahorro.
    $tabla_circulos = $wpdb->prefix . 'flavor_ahorro_rotativo_circulos';
    $sql_circulos = "CREATE TABLE $tabla_circulos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        colectivo_id bigint(20) unsigned DEFAULT NULL,
        nombre varchar(255) NOT NULL,
        importe_aportacion decimal(12,2) NOT NULL,
        moneda varchar(8) DEFAULT 'EUR',
        periodo_dias int(11) DEFAULT 30,
        num_plazas int(11) NOT NULL,
        fecha_inicio date DEFAULT NULL,
        modo_orden enum('fijo','sorteo') DEFAULT 'fijo',
        semilla_sorteo varchar(64) DEFAULT NULL,
        dias_gracia int(11) DEFAULT 3,
        estado enum('borrador','activo','completado','cancelado') DEFAULT 'borrador',
        creado_por bigint(20) unsigned NOT NULL,
        fecha_creacion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY colectivo_id (colectivo_id),
        KEY creado_por (creado_por),
        KEY estado (estado)
    ) $charset_collate;";

    // Miembros de cada círculo.
    $tabla_miembros = $wpdb->prefix . 'flavor_ahorro_rotativo_miembros';
    $sql_miembros = "CREATE TABLE $tabla_miembros (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        circulo_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned DEFAULT NULL,
        nombre_visible varchar(255) NOT NULL,
        posicion_turno int(11) DEFAULT NULL,
        estado_invitacion enum('invitado','aceptado','rechazado') DEFAULT 'invitado',
        codigo_invitacion varchar(64) DEFAULT NULL,
        fecha_alta datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY circulo_usuario (circulo_id, usuario_id),
        UNIQUE KEY codigo_invitacion (codigo_invitacion),
        KEY circulo_id (circulo_id),
        KEY usuario_id (usuario_id)
    ) $charset_collate;";

    // Rondas: una por periodo. En la ronda N cobra quien tiene posicion_turno = N.
    $tabla_rondas = $wpdb->prefix . 'flavor_ahorro_rotativo_rondas';
    $sql_rondas = "CREATE TABLE $tabla_rondas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        circulo_id bigint(20) unsigned NOT NULL,
        numero_ronda int(11) NOT NULL,
        periodo_inicio date NOT NULL,
        periodo_fin date NOT NULL,
        miembro_beneficiario_id bigint(20) unsigned NOT NULL,
        estado enum('abierta','cerrada') DEFAULT 'abierta',
        PRIMARY KEY (id),
        UNIQUE KEY circulo_ronda (circulo_id, numero_ronda),
        KEY miembro_beneficiario_id (miembro_beneficiario_id)
    ) $charset_collate;";

    // Aportaciones: una por miembro y ronda. La reputación se DERIVA de aquí.
    $tabla_aportaciones = $wpdb->prefix . 'flavor_ahorro_rotativo_aportaciones';
    $sql_aportaciones = "CREATE TABLE $tabla_aportaciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ronda_id bigint(20) unsigned NOT NULL,
        miembro_id bigint(20) unsigned NOT NULL,
        importe decimal(12,2) NOT NULL,
        fecha_pago datetime DEFAULT NULL,
        confirmada_por bigint(20) unsigned DEFAULT NULL,
        fecha_confirmacion datetime DEFAULT NULL,
        estado enum('pendiente','pagada','confirmada') DEFAULT 'pendiente',
        PRIMARY KEY (id),
        UNIQUE KEY ronda_miembro (ronda_id, miembro_id),
        KEY miembro_id (miembro_id),
        KEY estado (estado)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_circulos);
    dbDelta($sql_miembros);
    dbDelta($sql_rondas);
    dbDelta($sql_aportaciones);
}
