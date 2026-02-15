<?php
/**
 * Instalación de tablas para Banco de Tiempo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas necesarias para el Banco de Tiempo
 */
function flavor_banco_tiempo_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Tabla de servicios ofrecidos
    $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
    $sql_servicios = "CREATE TABLE $tabla_servicios (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        titulo varchar(255) NOT NULL,
        descripcion text NOT NULL,
        categoria varchar(50) DEFAULT 'otros',
        horas_estimadas decimal(4,2) DEFAULT 1.00,
        estado varchar(20) DEFAULT 'activo',
        fecha_publicacion datetime DEFAULT NULL,
        fecha_actualizacion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY usuario_id (usuario_id),
        KEY categoria (categoria),
        KEY estado (estado)
    ) $charset_collate;";

    // Tabla de transacciones/intercambios
    $tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';
    $sql_transacciones = "CREATE TABLE $tabla_transacciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        servicio_id bigint(20) unsigned NOT NULL,
        usuario_solicitante_id bigint(20) unsigned NOT NULL,
        usuario_receptor_id bigint(20) unsigned NOT NULL,
        horas decimal(4,2) NOT NULL,
        mensaje text DEFAULT NULL,
        fecha_preferida datetime DEFAULT NULL,
        estado varchar(20) DEFAULT 'pendiente',
        motivo_cancelacion text DEFAULT NULL,
        valoracion_solicitante tinyint(1) DEFAULT NULL,
        valoracion_receptor tinyint(1) DEFAULT NULL,
        comentario_solicitante text DEFAULT NULL,
        comentario_receptor text DEFAULT NULL,
        fecha_solicitud datetime DEFAULT NULL,
        fecha_aceptacion datetime DEFAULT NULL,
        fecha_completado datetime DEFAULT NULL,
        fecha_cancelacion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY servicio_id (servicio_id),
        KEY usuario_solicitante_id (usuario_solicitante_id),
        KEY usuario_receptor_id (usuario_receptor_id),
        KEY estado (estado),
        KEY fecha_solicitud (fecha_solicitud)
    ) $charset_collate;";

    // =====================================================
    // TABLAS v4.2.0 - Sello de Conciencia (+3 pts)
    // =====================================================

    // Tabla de reputación y badges de usuarios
    $tabla_reputacion = $wpdb->prefix . 'flavor_banco_tiempo_reputacion';
    $sql_reputacion = "CREATE TABLE $tabla_reputacion (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        total_intercambios_completados int(11) DEFAULT 0,
        total_horas_dadas decimal(8,2) DEFAULT 0.00,
        total_horas_recibidas decimal(8,2) DEFAULT 0.00,
        rating_promedio decimal(3,2) DEFAULT 0.00,
        rating_puntualidad decimal(3,2) DEFAULT 0.00,
        rating_calidad decimal(3,2) DEFAULT 0.00,
        rating_comunicacion decimal(3,2) DEFAULT 0.00,
        fecha_primer_intercambio datetime DEFAULT NULL,
        fecha_ultimo_intercambio datetime DEFAULT NULL,
        estado_verificacion enum('pendiente','verificado','destacado','mentor') DEFAULT 'pendiente',
        fecha_verificacion datetime DEFAULT NULL,
        verificado_por bigint(20) unsigned DEFAULT NULL,
        badges longtext DEFAULT NULL,
        nivel int(3) DEFAULT 1,
        puntos_confianza int(11) DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY usuario_id (usuario_id),
        KEY estado_verificacion (estado_verificacion),
        KEY rating_promedio (rating_promedio),
        KEY nivel (nivel)
    ) $charset_collate;";

    // Tabla de donaciones solidarias (fondo comunitario)
    $tabla_donaciones = $wpdb->prefix . 'flavor_banco_tiempo_donaciones';
    $sql_donaciones = "CREATE TABLE $tabla_donaciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        donante_id bigint(20) unsigned NOT NULL,
        beneficiario_id bigint(20) unsigned DEFAULT NULL,
        tipo enum('fondo_comunitario','regalo_directo','emergencia') DEFAULT 'fondo_comunitario',
        horas decimal(6,2) NOT NULL,
        motivo varchar(255) DEFAULT NULL,
        mensaje text DEFAULT NULL,
        estado enum('pendiente','aceptada','rechazada','utilizada') DEFAULT 'pendiente',
        fecha_donacion datetime DEFAULT NULL,
        fecha_utilizacion datetime DEFAULT NULL,
        utilizada_por bigint(20) unsigned DEFAULT NULL,
        transaccion_origen_id bigint(20) unsigned DEFAULT NULL,
        PRIMARY KEY (id),
        KEY donante_id (donante_id),
        KEY beneficiario_id (beneficiario_id),
        KEY tipo (tipo),
        KEY estado (estado),
        KEY fecha_donacion (fecha_donacion)
    ) $charset_collate;";

    // Tabla de métricas de sostenibilidad del sistema
    $tabla_metricas = $wpdb->prefix . 'flavor_banco_tiempo_metricas';
    $sql_metricas = "CREATE TABLE $tabla_metricas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        periodo_inicio date NOT NULL,
        periodo_fin date NOT NULL,
        tipo_periodo enum('diario','semanal','mensual','trimestral','anual') DEFAULT 'mensual',
        total_usuarios_activos int(11) DEFAULT 0,
        nuevos_usuarios int(11) DEFAULT 0,
        total_intercambios int(11) DEFAULT 0,
        total_horas_intercambiadas decimal(10,2) DEFAULT 0.00,
        horas_donadas_periodo decimal(10,2) DEFAULT 0.00,
        fondo_comunitario_actual decimal(10,2) DEFAULT 0.00,
        indice_equidad decimal(5,4) DEFAULT 0.00,
        categoria_mas_demandada varchar(50) DEFAULT NULL,
        categoria_menos_demandada varchar(50) DEFAULT NULL,
        ratio_oferta_demanda longtext DEFAULT NULL,
        alertas_generadas longtext DEFAULT NULL,
        usuarios_con_deuda_alta int(11) DEFAULT 0,
        usuarios_con_excedente_alto int(11) DEFAULT 0,
        puntuacion_sostenibilidad int(3) DEFAULT 0,
        datos_detalle longtext DEFAULT NULL,
        fecha_calculo datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY periodo_inicio (periodo_inicio),
        KEY tipo_periodo (tipo_periodo),
        KEY puntuacion_sostenibilidad (puntuacion_sostenibilidad)
    ) $charset_collate;";

    // Tabla de valoraciones detalladas
    $tabla_valoraciones = $wpdb->prefix . 'flavor_banco_tiempo_valoraciones';
    $sql_valoraciones = "CREATE TABLE $tabla_valoraciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        transaccion_id bigint(20) unsigned NOT NULL,
        valorador_id bigint(20) unsigned NOT NULL,
        valorado_id bigint(20) unsigned NOT NULL,
        rol_valorador enum('solicitante','receptor') NOT NULL,
        rating_general tinyint(1) NOT NULL,
        rating_puntualidad tinyint(1) DEFAULT NULL,
        rating_calidad tinyint(1) DEFAULT NULL,
        rating_comunicacion tinyint(1) DEFAULT NULL,
        comentario text DEFAULT NULL,
        es_publica tinyint(1) DEFAULT 1,
        fecha_valoracion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY transaccion_valorador (transaccion_id, valorador_id),
        KEY valorado_id (valorado_id),
        KEY rating_general (rating_general),
        KEY fecha_valoracion (fecha_valoracion)
    ) $charset_collate;";

    // Tabla de límites y alertas por usuario
    $tabla_limites = $wpdb->prefix . 'flavor_banco_tiempo_limites';
    $sql_limites = "CREATE TABLE $tabla_limites (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        usuario_id bigint(20) unsigned NOT NULL,
        saldo_actual decimal(8,2) DEFAULT 0.00,
        limite_deuda decimal(8,2) DEFAULT -20.00,
        limite_acumulacion decimal(8,2) DEFAULT 100.00,
        alerta_activa tinyint(1) DEFAULT 0,
        tipo_alerta varchar(50) DEFAULT NULL,
        fecha_ultima_alerta datetime DEFAULT NULL,
        en_plan_equilibrio tinyint(1) DEFAULT 0,
        notas_plan text DEFAULT NULL,
        fecha_actualizacion datetime DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY usuario_id (usuario_id),
        KEY saldo_actual (saldo_actual),
        KEY alerta_activa (alerta_activa)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_servicios);
    dbDelta($sql_transacciones);
    dbDelta($sql_reputacion);
    dbDelta($sql_donaciones);
    dbDelta($sql_metricas);
    dbDelta($sql_valoraciones);
    dbDelta($sql_limites);

    // Agregar datos de ejemplo (opcional)
    if ($wpdb->get_var("SELECT COUNT(*) FROM $tabla_servicios") == 0) {
        flavor_banco_tiempo_insertar_datos_ejemplo();
    }
}

/**
 * Inserta datos de ejemplo para el Banco de Tiempo
 */
function flavor_banco_tiempo_insertar_datos_ejemplo() {
    global $wpdb;
    $tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

    // Obtener un usuario admin para los ejemplos
    $admin_user = get_users(['role' => 'administrator', 'number' => 1]);
    $usuario_id = !empty($admin_user) ? $admin_user[0]->ID : 1;

    $servicios_ejemplo = [
        [
            'titulo' => 'Clases de guitarra para principiantes',
            'descripcion' => 'Ofrezco clases de guitarra para principiantes. Aprende los acordes básicos y tus primeras canciones.',
            'categoria' => 'educacion',
            'horas_estimadas' => 1.5,
        ],
        [
            'titulo' => 'Ayuda con reparaciones del hogar',
            'descripcion' => 'Puedo ayudarte con pequeñas reparaciones: colgar cuadros, arreglar grifos, pintura, etc.',
            'categoria' => 'bricolaje',
            'horas_estimadas' => 2.0,
        ],
        [
            'titulo' => 'Cuidado de mascotas',
            'descripcion' => 'Cuidado tu mascota mientras estás fuera. Tengo experiencia con perros y gatos.',
            'categoria' => 'cuidados',
            'horas_estimadas' => 2.0,
        ],
        [
            'titulo' => 'Ayuda con ordenador y móvil',
            'descripcion' => 'Te ayudo a resolver problemas técnicos con tu ordenador, móvil o tablet.',
            'categoria' => 'tecnologia',
            'horas_estimadas' => 1.0,
        ],
    ];

    foreach ($servicios_ejemplo as $servicio) {
        $wpdb->insert(
            $tabla_servicios,
            array_merge($servicio, [
                'usuario_id' => $usuario_id,
                'estado' => 'activo',
                'fecha_publicacion' => current_time('mysql'),
            ])
        );
    }
}
