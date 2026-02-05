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

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_servicios);
    dbDelta($sql_transacciones);

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
