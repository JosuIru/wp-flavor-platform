<?php
/**
 * Instalador de tablas de la base de datos
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Database_Installer {

    /**
     * Instala todas las tablas del plugin
     */
    public static function install_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'flavor_';

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Array de todas las tablas a crear
        $tables = self::get_tables_sql($prefix, $charset_collate);

        foreach ($tables as $sql) {
            dbDelta($sql);
        }

        // Insertar datos de ejemplo
        self::insert_sample_data($prefix);

        update_option('flavor_db_version', '1.0.0');
    }

    /**
     * Obtiene el SQL de todas las tablas
     */
    private static function get_tables_sql($prefix, $charset_collate) {
        $tables = [];

        // Eventos
        $tables[] = "CREATE TABLE {$prefix}eventos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime,
            lugar varchar(255),
            capacidad int(11) DEFAULT 0,
            precio decimal(10,2) DEFAULT 0,
            imagen varchar(500),
            estado varchar(50) DEFAULT 'publicado',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio)
        ) $charset_collate;";

        // Inscripciones a eventos
        $tables[] = "CREATE TABLE {$prefix}eventos_inscripciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            estado varchar(50) DEFAULT 'confirmada',
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY evento_id (evento_id),
            KEY usuario_id (usuario_id),
            KEY evento_estado (evento_id, estado)
        ) $charset_collate;";

        // Espacios comunes
        $tables[] = "CREATE TABLE {$prefix}espacios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text,
            capacidad int(11) DEFAULT 0,
            ubicacion varchar(255),
            imagen varchar(500),
            estado varchar(50) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado)
        ) $charset_collate;";

        // Reservas
        $tables[] = "CREATE TABLE {$prefix}reservas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            espacio_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime NOT NULL,
            estado varchar(50) DEFAULT 'pendiente',
            notas text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY espacio_id (espacio_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY espacio_fechas (espacio_id, fecha_inicio, fecha_fin)
        ) $charset_collate;";

        // Cursos
        $tables[] = "CREATE TABLE {$prefix}cursos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text,
            duracion_horas int(11) DEFAULT 0,
            precio decimal(10,2) DEFAULT 0,
            imagen varchar(500),
            estado varchar(50) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado)
        ) $charset_collate;";

        // Inscripciones a cursos
        $tables[] = "CREATE TABLE {$prefix}cursos_inscripciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            curso_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            progreso int(11) DEFAULT 0,
            estado varchar(50) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY curso_id (curso_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Talleres
        $tables[] = "CREATE TABLE {$prefix}talleres (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text,
            fecha datetime NOT NULL,
            duracion_minutos int(11) DEFAULT 60,
            plazas_disponibles int(11) DEFAULT 10,
            precio decimal(10,2) DEFAULT 0,
            lugar varchar(255),
            estado varchar(50) DEFAULT 'publicado',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY fecha (fecha),
            KEY estado (estado)
        ) $charset_collate;";

        // Avisos municipales
        $tables[] = "CREATE TABLE {$prefix}avisos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            contenido text,
            urgente tinyint(1) DEFAULT 0,
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion datetime,
            estado varchar(50) DEFAULT 'publicado',
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY fecha_expiracion (fecha_expiracion)
        ) $charset_collate;";

        // Chat grupos
        $tables[] = "CREATE TABLE {$prefix}chat_grupos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text,
            imagen varchar(500),
            tipo varchar(50) DEFAULT 'publico',
            estado varchar(50) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY tipo (tipo)
        ) $charset_collate;";

        // Miembros de grupos de chat
        $tables[] = "CREATE TABLE {$prefix}chat_grupos_miembros (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            grupo_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            rol varchar(50) DEFAULT 'miembro',
            fecha_union datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY grupo_usuario (grupo_id, usuario_id)
        ) $charset_collate;";

        // Foros
        $tables[] = "CREATE TABLE {$prefix}foros_temas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            contenido text,
            autor_id bigint(20) UNSIGNED NOT NULL,
            respuestas int(11) DEFAULT 0,
            vistas int(11) DEFAULT 0,
            estado varchar(50) DEFAULT 'abierto',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY autor_id (autor_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Mensajes
        $tables[] = "CREATE TABLE {$prefix}mensajes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            remitente_id bigint(20) UNSIGNED NOT NULL,
            destinatario_id bigint(20) UNSIGNED NOT NULL,
            asunto varchar(255),
            contenido text NOT NULL,
            leido tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY destinatario_id (destinatario_id),
            KEY remitente_id (remitente_id),
            KEY leido (leido)
        ) $charset_collate;";

        // Notificaciones
        $tables[] = "CREATE TABLE {$prefix}notificaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            mensaje text,
            tipo varchar(50) DEFAULT 'info',
            leida tinyint(1) DEFAULT 0,
            url varchar(500),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY leida (leida),
            KEY tipo (tipo)
        ) $charset_collate;";

        // Huertos parcelas
        $tables[] = "CREATE TABLE {$prefix}huertos_parcelas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            huerto_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            numero varchar(50) NOT NULL DEFAULT '',
            nombre varchar(255) NOT NULL,
            superficie decimal(10,2) DEFAULT 0,
            ubicacion varchar(255),
            estado varchar(50) DEFAULT 'disponible',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY huerto_numero (huerto_id, numero)
        ) $charset_collate;";

        // Huertos asignaciones
        $tables[] = "CREATE TABLE {$prefix}huertos_asignaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            parcela_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha_inicio date NOT NULL,
            fecha_fin date,
            estado varchar(50) DEFAULT 'activo',
            PRIMARY KEY (id),
            KEY parcela_id (parcela_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Reciclaje puntos
        $tables[] = "CREATE TABLE {$prefix}reciclaje_puntos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            puntos int(11) NOT NULL,
            concepto varchar(255),
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Marketplace
        $tables[] = "CREATE TABLE {$prefix}marketplace (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            precio decimal(10,2) DEFAULT 0,
            imagen varchar(500),
            categoria varchar(100),
            estado varchar(50) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY categoria (categoria),
            KEY estado (estado)
        ) $charset_collate;";

        // Tienda productos
        $tables[] = "CREATE TABLE {$prefix}tienda_productos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text,
            precio decimal(10,2) NOT NULL,
            stock int(11) DEFAULT 0,
            imagen varchar(500),
            categoria varchar(100),
            estado varchar(50) DEFAULT 'disponible',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY categoria (categoria),
            KEY estado (estado)
        ) $charset_collate;";

        // Tienda pedidos
        $tables[] = "CREATE TABLE {$prefix}tienda_pedidos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            numero_pedido varchar(50) NOT NULL,
            total decimal(10,2) NOT NULL,
            estado varchar(50) DEFAULT 'pendiente',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            UNIQUE KEY numero_pedido (numero_pedido),
            KEY estado (estado)
        ) $charset_collate;";

        // Banco del tiempo
        $tables[] = "CREATE TABLE {$prefix}banco_tiempo_saldo (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            horas decimal(10,2) NOT NULL,
            concepto varchar(255),
            tipo varchar(50) DEFAULT 'ingreso',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY tipo (tipo),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Colectivos
        $tables[] = "CREATE TABLE {$prefix}colectivos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text,
            tipo varchar(100),
            imagen varchar(500),
            estado varchar(50) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY tipo (tipo)
        ) $charset_collate;";

        // Colectivos miembros
        $tables[] = "CREATE TABLE {$prefix}colectivos_miembros (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            colectivo_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            rol varchar(50) DEFAULT 'miembro',
            fecha_union datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY colectivo_usuario (colectivo_id, usuario_id)
        ) $charset_collate;";

        // Socios
        $tables[] = "CREATE TABLE {$prefix}socios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            numero_socio varchar(50) NOT NULL,
            tipo varchar(50) DEFAULT 'standard',
            estado varchar(50) DEFAULT 'activo',
            fecha_alta date NOT NULL,
            fecha_renovacion date,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            UNIQUE KEY numero_socio (numero_socio),
            KEY estado (estado)
        ) $charset_collate;";

        // Incidencias
        $tables[] = "CREATE TABLE {$prefix}incidencias (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            numero_incidencia varchar(50) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            ubicacion varchar(255),
            categoria varchar(100),
            estado varchar(50) DEFAULT 'pendiente',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY numero_incidencia (numero_incidencia),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY categoria (categoria)
        ) $charset_collate;";

        // Trámites
        $tables[] = "CREATE TABLE {$prefix}tramites (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo varchar(100) NOT NULL,
            titulo varchar(255),
            datos text,
            estado varchar(50) DEFAULT 'pendiente',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY tipo (tipo),
            KEY estado (estado)
        ) $charset_collate;";

        // Participación procesos
        $tables[] = "CREATE TABLE {$prefix}participacion_procesos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text,
            fecha_inicio date,
            fecha_fin date,
            estado varchar(50) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY fecha_fin (fecha_fin)
        ) $charset_collate;";

        // Presupuestos propuestas
        $tables[] = "CREATE TABLE {$prefix}presupuestos_propuestas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            presupuesto decimal(12,2) DEFAULT 0,
            votos int(11) DEFAULT 0,
            estado varchar(50) DEFAULT 'pendiente',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Fichajes
        $tables[] = "CREATE TABLE {$prefix}fichajes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha date NOT NULL,
            hora_entrada time,
            hora_salida time,
            notas text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY fecha (fecha),
            KEY usuario_fecha (usuario_id, fecha)
        ) $charset_collate;";

        // Biblioteca préstamos
        $tables[] = "CREATE TABLE {$prefix}biblioteca_prestamos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            libro_titulo varchar(255) NOT NULL,
            fecha_prestamo date NOT NULL,
            fecha_devolucion date,
            estado varchar(50) DEFAULT 'activo',
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Bares y restaurantes
        $tables[] = "CREATE TABLE {$prefix}bares (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            tipo varchar(100),
            direccion varchar(255),
            telefono varchar(50),
            horario varchar(255),
            estado varchar(50) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY estado (estado)
        ) $charset_collate;";

        // Podcast episodios
        $tables[] = "CREATE TABLE {$prefix}podcast_episodios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text,
            duracion_segundos int(11) DEFAULT 0,
            url_audio varchar(500),
            estado varchar(50) DEFAULT 'publicado',
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY fecha_publicacion (fecha_publicacion)
        ) $charset_collate;";

        // Carpooling viajes
        $tables[] = "CREATE TABLE {$prefix}carpooling_viajes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conductor_id bigint(20) UNSIGNED NOT NULL,
            origen varchar(255) NOT NULL,
            destino varchar(255) NOT NULL,
            fecha_salida datetime NOT NULL,
            plazas_disponibles int(11) DEFAULT 4,
            precio decimal(10,2) DEFAULT 0,
            estado varchar(50) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conductor_id (conductor_id),
            KEY estado (estado),
            KEY fecha_salida (fecha_salida)
        ) $charset_collate;";

        // Grupos de consumo
        $tables[] = "CREATE TABLE {$prefix}grupos_consumo (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text,
            proximo_reparto date,
            estado varchar(50) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado)
        ) $charset_collate;";

        // Grupos consumo miembros
        $tables[] = "CREATE TABLE {$prefix}grupos_consumo_miembros (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            grupo_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha_union datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY grupo_usuario (grupo_id, usuario_id)
        ) $charset_collate;";

        // Compostaje aportes
        $tables[] = "CREATE TABLE {$prefix}compostaje_aportes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            cantidad_kg decimal(10,2) NOT NULL,
            tipo varchar(100),
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY tipo (tipo),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Ayuda vecinal
        $tables[] = "CREATE TABLE {$prefix}ayuda_vecinal (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            tipo varchar(100),
            urgente tinyint(1) DEFAULT 0,
            estado varchar(50) DEFAULT 'activa',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY tipo (tipo)
        ) $charset_collate;";

        // Recursos compartidos
        $tables[] = "CREATE TABLE {$prefix}recursos_compartidos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            tipo varchar(100),
            estado varchar(50) DEFAULT 'disponible',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY tipo (tipo),
            KEY estado (estado)
        ) $charset_collate;";

        // Facturas
        $tables[] = "CREATE TABLE {$prefix}facturas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            numero_factura varchar(50) NOT NULL,
            concepto varchar(255),
            total decimal(10,2) NOT NULL,
            estado varchar(50) DEFAULT 'pendiente',
            fecha date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            UNIQUE KEY numero_factura (numero_factura),
            KEY estado (estado)
        ) $charset_collate;";

        // Bicicletas
        $tables[] = "CREATE TABLE {$prefix}bicicletas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo varchar(50) NOT NULL,
            tipo varchar(100),
            ubicacion varchar(255),
            estado varchar(50) DEFAULT 'disponible',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY codigo (codigo)
        ) $charset_collate;";

        // Bicicletas alquileres
        $tables[] = "CREATE TABLE {$prefix}bicicletas_alquileres (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            bicicleta_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime,
            estado varchar(50) DEFAULT 'activo',
            PRIMARY KEY (id),
            KEY bicicleta_id (bicicleta_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Parkings
        $tables[] = "CREATE TABLE {$prefix}parkings (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            direccion varchar(255),
            plazas_totales int(11) DEFAULT 0,
            plazas_libres int(11) DEFAULT 0,
            estado varchar(50) DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado)
        ) $charset_collate;";

        // Radio - Programas
        $tables[] = "CREATE TABLE {$prefix}radio_programas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text NOT NULL,
            locutor_id bigint(20) UNSIGNED NOT NULL,
            co_locutores JSON DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            categoria varchar(100) DEFAULT NULL,
            genero_musical varchar(100) DEFAULT NULL,
            frecuencia varchar(50) DEFAULT 'semanal',
            dias_semana JSON DEFAULT NULL,
            hora_inicio time DEFAULT NULL,
            duracion_minutos int(11) DEFAULT 60,
            estado varchar(50) DEFAULT 'pendiente',
            oyentes_promedio int(11) DEFAULT 0,
            total_episodios int(11) DEFAULT 0,
            redes_sociales JSON DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY locutor_id (locutor_id),
            KEY estado (estado),
            KEY slug (slug)
        ) $charset_collate;";

        // Radio - Programación/Emisiones
        $tables[] = "CREATE TABLE {$prefix}radio_programacion (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            programa_id bigint(20) UNSIGNED DEFAULT NULL,
            tipo varchar(50) DEFAULT 'programa',
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            fecha_hora_inicio datetime NOT NULL,
            fecha_hora_fin datetime NOT NULL,
            archivo_url varchar(500) DEFAULT NULL,
            en_vivo tinyint(1) DEFAULT 0,
            oyentes_pico int(11) DEFAULT 0,
            oyentes_total int(11) DEFAULT 0,
            chat_activo tinyint(1) DEFAULT 1,
            estado varchar(50) DEFAULT 'programado',
            notas_locutor text DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY programa_id (programa_id),
            KEY fecha_hora_inicio (fecha_hora_inicio),
            KEY estado (estado)
        ) $charset_collate;";

        // Radio - Dedicatorias
        $tables[] = "CREATE TABLE {$prefix}radio_dedicatorias (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            de_nombre varchar(100) NOT NULL,
            para_nombre varchar(100) NOT NULL,
            mensaje text NOT NULL,
            cancion_titulo varchar(255) DEFAULT NULL,
            cancion_artista varchar(255) DEFAULT NULL,
            cancion_url varchar(500) DEFAULT NULL,
            estado varchar(50) DEFAULT 'pendiente',
            emision_id bigint(20) UNSIGNED DEFAULT NULL,
            motivo_rechazo varchar(255) DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_emision datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY emision_id (emision_id)
        ) $charset_collate;";

        // Radio - Chat
        $tables[] = "CREATE TABLE {$prefix}radio_chat (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            emision_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            mensaje text NOT NULL,
            tipo varchar(50) DEFAULT 'mensaje',
            destacado tinyint(1) DEFAULT 0,
            eliminado tinyint(1) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY emision_id (emision_id),
            KEY usuario_id (usuario_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Radio - Oyentes
        $tables[] = "CREATE TABLE {$prefix}radio_oyentes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            usuario_id bigint(20) UNSIGNED DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            emision_id bigint(20) UNSIGNED DEFAULT NULL,
            dispositivo varchar(50) DEFAULT NULL,
            inicio datetime DEFAULT CURRENT_TIMESTAMP,
            ultima_actividad datetime DEFAULT CURRENT_TIMESTAMP,
            duracion_segundos int(11) DEFAULT 0,
            activo tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY usuario_id (usuario_id),
            KEY emision_id (emision_id),
            KEY activo (activo)
        ) $charset_collate;";

        // Radio - Propuestas de programas
        $tables[] = "CREATE TABLE {$prefix}radio_propuestas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            nombre_programa varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            frecuencia_deseada varchar(50) DEFAULT NULL,
            horario_preferido varchar(100) DEFAULT NULL,
            experiencia text DEFAULT NULL,
            demo_url varchar(500) DEFAULT NULL,
            estado varchar(50) DEFAULT 'pendiente',
            notas_admin text DEFAULT NULL,
            programa_id bigint(20) UNSIGNED DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_respuesta datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Radio - Podcasts
        $tables[] = "CREATE TABLE {$prefix}radio_podcasts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            programa_id bigint(20) UNSIGNED NOT NULL,
            emision_id bigint(20) UNSIGNED DEFAULT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            archivo_url varchar(500) NOT NULL,
            duracion_segundos int(11) DEFAULT 0,
            tamano_bytes bigint(20) DEFAULT 0,
            imagen_url varchar(500) DEFAULT NULL,
            reproducciones int(11) DEFAULT 0,
            descargas int(11) DEFAULT 0,
            publicado tinyint(1) DEFAULT 1,
            fecha_emision datetime DEFAULT NULL,
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY programa_id (programa_id),
            KEY emision_id (emision_id),
            KEY publicado (publicado)
        ) $charset_collate;";

        return $tables;
    }

    /**
     * Inserta datos de ejemplo
     */
    private static function insert_sample_data($prefix) {
        global $wpdb;
        $user_id = get_current_user_id() ?: 1;

        // Verificar si ya hay datos
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}eventos");
        if ($count > 0) {
            return;
        }

        // Eventos de ejemplo
        $wpdb->insert("{$prefix}eventos", [
            'titulo' => 'Festival de Verano 2024',
            'descripcion' => 'Gran festival de música y cultura al aire libre',
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'fecha_fin' => date('Y-m-d H:i:s', strtotime('+7 days +6 hours')),
            'lugar' => 'Plaza Mayor',
            'capacidad' => 500,
            'estado' => 'publicado'
        ]);
        $wpdb->insert("{$prefix}eventos", [
            'titulo' => 'Taller de Huerto Urbano',
            'descripcion' => 'Aprende a cultivar tus propias verduras',
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'lugar' => 'Centro Cívico',
            'capacidad' => 20,
            'estado' => 'publicado'
        ]);
        $wpdb->insert("{$prefix}eventos", [
            'titulo' => 'Charla sobre Reciclaje',
            'descripcion' => 'Cómo reducir nuestra huella ecológica',
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+10 days')),
            'lugar' => 'Biblioteca Municipal',
            'capacidad' => 50,
            'estado' => 'publicado'
        ]);

        // Espacios de ejemplo
        $wpdb->insert("{$prefix}espacios", ['nombre' => 'Sala de Reuniones A', 'capacidad' => 15, 'estado' => 'activo']);
        $wpdb->insert("{$prefix}espacios", ['nombre' => 'Salón de Actos', 'capacidad' => 100, 'estado' => 'activo']);
        $wpdb->insert("{$prefix}espacios", ['nombre' => 'Aula Multiusos', 'capacidad' => 30, 'estado' => 'activo']);

        // Cursos de ejemplo
        $wpdb->insert("{$prefix}cursos", ['titulo' => 'Iniciación a la Fotografía', 'duracion_horas' => 20, 'estado' => 'activo']);
        $wpdb->insert("{$prefix}cursos", ['titulo' => 'Cocina Saludable', 'duracion_horas' => 15, 'estado' => 'activo']);

        // Talleres de ejemplo
        $wpdb->insert("{$prefix}talleres", [
            'titulo' => 'Taller de Cerámica',
            'fecha' => date('Y-m-d H:i:s', strtotime('+5 days')),
            'plazas_disponibles' => 12,
            'estado' => 'publicado'
        ]);

        // Avisos de ejemplo
        $wpdb->insert("{$prefix}avisos", [
            'titulo' => 'Corte de agua programado',
            'contenido' => 'El próximo lunes habrá corte de agua de 10:00 a 14:00',
            'urgente' => 1,
            'estado' => 'publicado'
        ]);
        $wpdb->insert("{$prefix}avisos", [
            'titulo' => 'Nueva zona peatonal',
            'contenido' => 'A partir del día 15 se habilitará la nueva zona peatonal',
            'estado' => 'publicado'
        ]);

        // Grupos de chat de ejemplo
        $grupo_id = $wpdb->insert("{$prefix}chat_grupos", ['nombre' => 'Vecinos del Barrio', 'tipo' => 'publico', 'estado' => 'activo']);
        $wpdb->insert("{$prefix}chat_grupos_miembros", ['grupo_id' => $wpdb->insert_id, 'usuario_id' => $user_id]);

        // Foros de ejemplo
        $wpdb->insert("{$prefix}foros_temas", [
            'titulo' => '¿Cómo mejorar el transporte público?',
            'contenido' => 'Propuestas para mejorar el servicio de autobuses',
            'autor_id' => $user_id,
            'respuestas' => 12
        ]);

        // Puntos de reciclaje
        $wpdb->insert("{$prefix}reciclaje_puntos", ['usuario_id' => $user_id, 'puntos' => 150, 'concepto' => 'Reciclaje de papel']);
        $wpdb->insert("{$prefix}reciclaje_puntos", ['usuario_id' => $user_id, 'puntos' => 75, 'concepto' => 'Reciclaje de vidrio']);

        // Marketplace
        $wpdb->insert("{$prefix}marketplace", [
            'usuario_id' => $user_id,
            'titulo' => 'Bicicleta de montaña',
            'descripcion' => 'En buen estado, poco uso',
            'precio' => 150,
            'categoria' => 'Deportes',
            'estado' => 'activo'
        ]);

        // Tienda productos
        $wpdb->insert("{$prefix}tienda_productos", ['nombre' => 'Miel Local', 'precio' => 8.50, 'stock' => 20, 'estado' => 'disponible']);
        $wpdb->insert("{$prefix}tienda_productos", ['nombre' => 'Aceite de Oliva', 'precio' => 12.00, 'stock' => 15, 'estado' => 'disponible']);

        // Banco del tiempo
        $wpdb->insert("{$prefix}banco_tiempo_saldo", ['usuario_id' => $user_id, 'horas' => 5, 'concepto' => 'Clases de idiomas']);

        // Colectivos
        $colectivo_id = $wpdb->insert("{$prefix}colectivos", ['nombre' => 'Asociación de Vecinos', 'tipo' => 'vecinal', 'estado' => 'activo']);
        $wpdb->insert("{$prefix}colectivos_miembros", ['colectivo_id' => $wpdb->insert_id, 'usuario_id' => $user_id]);

        // Socio
        $wpdb->insert("{$prefix}socios", [
            'usuario_id' => $user_id,
            'numero_socio' => 'SOC-' . str_pad($user_id, 4, '0', STR_PAD_LEFT),
            'tipo' => 'standard',
            'estado' => 'activo',
            'fecha_alta' => date('Y-m-d'),
            'fecha_renovacion' => date('Y-m-d', strtotime('+1 year'))
        ]);

        // Participación
        $wpdb->insert("{$prefix}participacion_procesos", [
            'titulo' => 'Consulta sobre movilidad urbana',
            'descripcion' => 'Tu opinión sobre el plan de movilidad',
            'fecha_fin' => date('Y-m-d', strtotime('+30 days')),
            'estado' => 'activo'
        ]);

        // Presupuestos
        $wpdb->insert("{$prefix}presupuestos_propuestas", [
            'usuario_id' => $user_id,
            'titulo' => 'Parque infantil en zona norte',
            'descripcion' => 'Instalación de nuevo parque infantil',
            'presupuesto' => 25000,
            'votos' => 45,
            'estado' => 'votacion'
        ]);

        // Podcast
        $wpdb->insert("{$prefix}podcast_episodios", [
            'titulo' => 'Episodio 1: Bienvenidos',
            'descripcion' => 'Primer episodio del podcast municipal',
            'duracion_segundos' => 1800,
            'estado' => 'publicado'
        ]);

        // Carpooling
        $wpdb->insert("{$prefix}carpooling_viajes", [
            'conductor_id' => $user_id,
            'origen' => 'Centro',
            'destino' => 'Polígono Industrial',
            'fecha_salida' => date('Y-m-d H:i:s', strtotime('+2 days 08:00')),
            'plazas_disponibles' => 3,
            'estado' => 'activo'
        ]);

        // Grupos de consumo
        $grupo_consumo_id = $wpdb->insert("{$prefix}grupos_consumo", [
            'nombre' => 'Grupo Ecológico',
            'proximo_reparto' => date('Y-m-d', strtotime('next saturday')),
            'estado' => 'activo'
        ]);
        $wpdb->insert("{$prefix}grupos_consumo_miembros", ['grupo_id' => $wpdb->insert_id, 'usuario_id' => $user_id]);

        // Bicicletas
        $wpdb->insert("{$prefix}bicicletas", ['codigo' => 'BIC-001', 'tipo' => 'urbana', 'ubicacion' => 'Estación Centro', 'estado' => 'disponible']);
        $wpdb->insert("{$prefix}bicicletas", ['codigo' => 'BIC-002', 'tipo' => 'urbana', 'ubicacion' => 'Estación Centro', 'estado' => 'disponible']);
        $wpdb->insert("{$prefix}bicicletas", ['codigo' => 'BIC-003', 'tipo' => 'eléctrica', 'ubicacion' => 'Estación Norte', 'estado' => 'disponible']);

        // Parkings
        $wpdb->insert("{$prefix}parkings", ['nombre' => 'Parking Centro', 'plazas_totales' => 100, 'plazas_libres' => 45, 'estado' => 'activo']);
        $wpdb->insert("{$prefix}parkings", ['nombre' => 'Parking Estación', 'plazas_totales' => 200, 'plazas_libres' => 120, 'estado' => 'activo']);

        // Bares
        $wpdb->insert("{$prefix}bares", ['nombre' => 'Bar La Plaza', 'tipo' => 'Bar', 'direccion' => 'Plaza Mayor, 5', 'estado' => 'activo']);
        $wpdb->insert("{$prefix}bares", ['nombre' => 'Restaurante El Molino', 'tipo' => 'Restaurante', 'direccion' => 'Calle Real, 12', 'estado' => 'activo']);

        // Notificación de bienvenida
        $wpdb->insert("{$prefix}notificaciones", [
            'usuario_id' => $user_id,
            'titulo' => '¡Bienvenido al portal!',
            'mensaje' => 'Gracias por unirte a nuestra comunidad',
            'tipo' => 'info'
        ]);
    }

    /**
     * Elimina todas las tablas del plugin
     */
    public static function uninstall_tables() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        $tables = [
            'eventos', 'eventos_inscripciones', 'espacios', 'reservas',
            'cursos', 'cursos_inscripciones', 'talleres', 'avisos',
            'chat_grupos', 'chat_grupos_miembros', 'foros_temas', 'mensajes',
            'notificaciones', 'huertos_parcelas', 'huertos_asignaciones',
            'reciclaje_puntos', 'marketplace', 'tienda_productos', 'tienda_pedidos',
            'banco_tiempo_saldo', 'colectivos', 'colectivos_miembros', 'socios',
            'incidencias', 'tramites', 'participacion_procesos', 'presupuestos_propuestas',
            'fichajes', 'biblioteca_prestamos', 'bares', 'podcast_episodios',
            'carpooling_viajes', 'grupos_consumo', 'grupos_consumo_miembros',
            'compostaje_aportes', 'ayuda_vecinal', 'recursos_compartidos',
            'facturas', 'bicicletas', 'bicicletas_alquileres', 'parkings'
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table}");
        }

        delete_option('flavor_db_version');
    }

    /**
     * Añade índices faltantes a instalaciones existentes
     *
     * Este método se puede ejecutar en actualizaciones para mejorar
     * el rendimiento de bases de datos existentes sin recrear tablas.
     *
     * @return array Resultado de las operaciones
     */
    public static function add_missing_indexes() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';
        $resultados = ['añadidos' => [], 'existentes' => [], 'errores' => []];

        // Definición de índices a verificar/crear
        $indices = [
            // Eventos e inscripciones
            ['tabla' => 'eventos', 'nombre' => 'estado', 'columnas' => 'estado'],
            ['tabla' => 'eventos', 'nombre' => 'fecha_inicio', 'columnas' => 'fecha_inicio'],
            ['tabla' => 'eventos_inscripciones', 'nombre' => 'evento_estado', 'columnas' => 'evento_id, estado'],

            // Espacios y reservas
            ['tabla' => 'espacios', 'nombre' => 'estado', 'columnas' => 'estado'],
            ['tabla' => 'reservas', 'nombre' => 'estado', 'columnas' => 'estado'],
            ['tabla' => 'reservas', 'nombre' => 'espacio_fechas', 'columnas' => 'espacio_id, fecha_inicio, fecha_fin'],

            // Cursos y talleres
            ['tabla' => 'cursos', 'nombre' => 'estado', 'columnas' => 'estado'],
            ['tabla' => 'talleres', 'nombre' => 'fecha', 'columnas' => 'fecha'],
            ['tabla' => 'talleres', 'nombre' => 'estado', 'columnas' => 'estado'],

            // Avisos
            ['tabla' => 'avisos', 'nombre' => 'estado', 'columnas' => 'estado'],
            ['tabla' => 'avisos', 'nombre' => 'fecha_expiracion', 'columnas' => 'fecha_expiracion'],

            // Chat y foros
            ['tabla' => 'chat_grupos', 'nombre' => 'estado', 'columnas' => 'estado'],
            ['tabla' => 'chat_grupos', 'nombre' => 'tipo', 'columnas' => 'tipo'],
            ['tabla' => 'foros_temas', 'nombre' => 'autor_id', 'columnas' => 'autor_id'],
            ['tabla' => 'foros_temas', 'nombre' => 'estado', 'columnas' => 'estado'],

            // Mensajes y notificaciones
            ['tabla' => 'mensajes', 'nombre' => 'leido', 'columnas' => 'leido'],
            ['tabla' => 'notificaciones', 'nombre' => 'leida', 'columnas' => 'leida'],
            ['tabla' => 'notificaciones', 'nombre' => 'tipo', 'columnas' => 'tipo'],

            // Huertos
            ['tabla' => 'huertos_asignaciones', 'nombre' => 'parcela_id', 'columnas' => 'parcela_id'],
            ['tabla' => 'huertos_asignaciones', 'nombre' => 'usuario_id', 'columnas' => 'usuario_id'],
            ['tabla' => 'huertos_asignaciones', 'nombre' => 'estado', 'columnas' => 'estado'],

            // Marketplace
            ['tabla' => 'marketplace', 'nombre' => 'usuario_id', 'columnas' => 'usuario_id'],
            ['tabla' => 'marketplace', 'nombre' => 'categoria', 'columnas' => 'categoria'],
            ['tabla' => 'marketplace', 'nombre' => 'estado', 'columnas' => 'estado'],

            // Tienda
            ['tabla' => 'tienda_productos', 'nombre' => 'categoria', 'columnas' => 'categoria'],
            ['tabla' => 'tienda_productos', 'nombre' => 'estado', 'columnas' => 'estado'],
            ['tabla' => 'tienda_pedidos', 'nombre' => 'estado', 'columnas' => 'estado'],

            // Banco del tiempo
            ['tabla' => 'banco_tiempo_saldo', 'nombre' => 'tipo', 'columnas' => 'tipo'],
            ['tabla' => 'banco_tiempo_saldo', 'nombre' => 'fecha', 'columnas' => 'fecha'],

            // Colectivos y socios
            ['tabla' => 'colectivos', 'nombre' => 'estado', 'columnas' => 'estado'],
            ['tabla' => 'colectivos', 'nombre' => 'tipo', 'columnas' => 'tipo'],
            ['tabla' => 'socios', 'nombre' => 'estado', 'columnas' => 'estado'],

            // Incidencias
            ['tabla' => 'incidencias', 'nombre' => 'usuario_id', 'columnas' => 'usuario_id'],
            ['tabla' => 'incidencias', 'nombre' => 'estado', 'columnas' => 'estado'],
            ['tabla' => 'incidencias', 'nombre' => 'categoria', 'columnas' => 'categoria'],

            // Trámites
            ['tabla' => 'tramites', 'nombre' => 'usuario_id', 'columnas' => 'usuario_id'],
            ['tabla' => 'tramites', 'nombre' => 'tipo', 'columnas' => 'tipo'],
            ['tabla' => 'tramites', 'nombre' => 'estado', 'columnas' => 'estado'],

            // Participación
            ['tabla' => 'participacion_procesos', 'nombre' => 'estado', 'columnas' => 'estado'],
            ['tabla' => 'participacion_procesos', 'nombre' => 'fecha_fin', 'columnas' => 'fecha_fin'],
            ['tabla' => 'presupuestos_propuestas', 'nombre' => 'usuario_id', 'columnas' => 'usuario_id'],
            ['tabla' => 'presupuestos_propuestas', 'nombre' => 'estado', 'columnas' => 'estado'],

            // Fichajes
            ['tabla' => 'fichajes', 'nombre' => 'usuario_fecha', 'columnas' => 'usuario_id, fecha'],

            // Biblioteca
            ['tabla' => 'biblioteca_prestamos', 'nombre' => 'usuario_id', 'columnas' => 'usuario_id'],
            ['tabla' => 'biblioteca_prestamos', 'nombre' => 'estado', 'columnas' => 'estado'],

            // Bares
            ['tabla' => 'bares', 'nombre' => 'tipo', 'columnas' => 'tipo'],
            ['tabla' => 'bares', 'nombre' => 'estado', 'columnas' => 'estado'],

            // Podcast
            ['tabla' => 'podcast_episodios', 'nombre' => 'estado', 'columnas' => 'estado'],
            ['tabla' => 'podcast_episodios', 'nombre' => 'fecha_publicacion', 'columnas' => 'fecha_publicacion'],

            // Carpooling
            ['tabla' => 'carpooling_viajes', 'nombre' => 'conductor_id', 'columnas' => 'conductor_id'],
            ['tabla' => 'carpooling_viajes', 'nombre' => 'estado', 'columnas' => 'estado'],
            ['tabla' => 'carpooling_viajes', 'nombre' => 'fecha_salida', 'columnas' => 'fecha_salida'],

            // Grupos de consumo
            ['tabla' => 'grupos_consumo', 'nombre' => 'estado', 'columnas' => 'estado'],

            // Compostaje
            ['tabla' => 'compostaje_aportes', 'nombre' => 'usuario_id', 'columnas' => 'usuario_id'],
            ['tabla' => 'compostaje_aportes', 'nombre' => 'tipo', 'columnas' => 'tipo'],
            ['tabla' => 'compostaje_aportes', 'nombre' => 'fecha', 'columnas' => 'fecha'],

            // Ayuda vecinal
            ['tabla' => 'ayuda_vecinal', 'nombre' => 'usuario_id', 'columnas' => 'usuario_id'],
            ['tabla' => 'ayuda_vecinal', 'nombre' => 'estado', 'columnas' => 'estado'],
            ['tabla' => 'ayuda_vecinal', 'nombre' => 'tipo', 'columnas' => 'tipo'],

            // Recursos compartidos
            ['tabla' => 'recursos_compartidos', 'nombre' => 'usuario_id', 'columnas' => 'usuario_id'],
            ['tabla' => 'recursos_compartidos', 'nombre' => 'tipo', 'columnas' => 'tipo'],
            ['tabla' => 'recursos_compartidos', 'nombre' => 'estado', 'columnas' => 'estado'],

            // Facturas
            ['tabla' => 'facturas', 'nombre' => 'usuario_id', 'columnas' => 'usuario_id'],
            ['tabla' => 'facturas', 'nombre' => 'estado', 'columnas' => 'estado'],

            // Bicicletas
            ['tabla' => 'bicicletas_alquileres', 'nombre' => 'bicicleta_id', 'columnas' => 'bicicleta_id'],
            ['tabla' => 'bicicletas_alquileres', 'nombre' => 'usuario_id', 'columnas' => 'usuario_id'],
            ['tabla' => 'bicicletas_alquileres', 'nombre' => 'estado', 'columnas' => 'estado'],

            // Parkings
            ['tabla' => 'parkings', 'nombre' => 'estado', 'columnas' => 'estado'],
        ];

        foreach ($indices as $indice) {
            $tabla_completa = $prefix . $indice['tabla'];

            // Verificar si la tabla existe
            $tabla_existe = $wpdb->get_var(
                $wpdb->prepare("SHOW TABLES LIKE %s", $tabla_completa)
            );

            if (!$tabla_existe) {
                continue;
            }

            // Verificar si el índice ya existe
            $indice_existe = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
                 WHERE table_schema = DATABASE()
                 AND table_name = %s
                 AND index_name = %s",
                $tabla_completa,
                $indice['nombre']
            ));

            if ($indice_existe) {
                $resultados['existentes'][] = "{$indice['tabla']}.{$indice['nombre']}";
                continue;
            }

            // Crear el índice
            $sql = "ALTER TABLE `{$tabla_completa}` ADD INDEX `{$indice['nombre']}` ({$indice['columnas']})";
            $resultado = $wpdb->query($sql);

            if ($resultado !== false) {
                $resultados['añadidos'][] = "{$indice['tabla']}.{$indice['nombre']}";
            } else {
                $resultados['errores'][] = "{$indice['tabla']}.{$indice['nombre']}: " . $wpdb->last_error;
            }
        }

        return $resultados;
    }

    /**
     * Verifica la versión de la BD y ejecuta migraciones necesarias
     */
    public static function maybe_upgrade() {
        $version_actual = get_option('flavor_db_version', '1.0.0');
        $version_nueva = '1.2.0'; // Versión con tablas de radio

        if (version_compare($version_actual, $version_nueva, '<')) {
            // Crear tablas faltantes
            self::create_missing_tables();
            // Añadir índices faltantes
            self::add_missing_indexes();
            update_option('flavor_db_version', $version_nueva);
        }
    }

    /**
     * Crea tablas que faltan sin afectar las existentes
     */
    public static function create_missing_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'flavor_';

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $tables = self::get_tables_sql($prefix, $charset_collate);

        foreach ($tables as $sql) {
            // dbDelta solo crea si no existe
            dbDelta($sql);
        }
    }

    /**
     * Crea páginas legales requeridas si no existen
     */
    public static function install_legal_pages() {
        $legal_pages = [
            'politica-de-cookies' => [
                'title'   => __('Política de Cookies', 'flavor-chat-ia'),
                'content' => self::get_cookies_policy_content(),
            ],
            'terminos-de-uso' => [
                'title'   => __('Términos de Uso', 'flavor-chat-ia'),
                'content' => self::get_terms_content(),
            ],
        ];

        foreach ($legal_pages as $slug => $page_data) {
            // Verificar si la página existe
            $existing = get_page_by_path($slug);
            if (!$existing) {
                wp_insert_post([
                    'post_title'   => $page_data['title'],
                    'post_name'    => $slug,
                    'post_content' => $page_data['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_author'  => 1,
                ]);
            }
        }
    }

    /**
     * Contenido predeterminado de política de cookies
     */
    private static function get_cookies_policy_content() {
        return '<!-- wp:heading -->
<h2>' . __('¿Qué son las cookies?', 'flavor-chat-ia') . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . __('Las cookies son pequeños archivos de texto que los sitios web almacenan en su dispositivo cuando los visita. Se utilizan ampliamente para hacer que los sitios web funcionen de manera más eficiente y proporcionar información a los propietarios del sitio.', 'flavor-chat-ia') . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>' . __('Cookies que utilizamos', 'flavor-chat-ia') . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . __('Utilizamos cookies técnicas necesarias para el funcionamiento del sitio, cookies de análisis para entender cómo se usa el sitio, y cookies de preferencias para recordar su configuración.', 'flavor-chat-ia') . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>' . __('Gestión de cookies', 'flavor-chat-ia') . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . __('Puede configurar su navegador para rechazar cookies o alertarle cuando se envían cookies. Sin embargo, algunas partes del sitio pueden no funcionar correctamente sin cookies.', 'flavor-chat-ia') . '</p>
<!-- /wp:paragraph -->';
    }

    /**
     * Contenido predeterminado de términos de uso
     */
    private static function get_terms_content() {
        return '<!-- wp:heading -->
<h2>' . __('Aceptación de los términos', 'flavor-chat-ia') . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . __('Al acceder y utilizar este sitio web, usted acepta cumplir con estos términos de uso. Si no está de acuerdo con alguna parte de estos términos, no debe utilizar nuestro sitio.', 'flavor-chat-ia') . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>' . __('Uso del sitio', 'flavor-chat-ia') . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . __('Este sitio está destinado a usuarios mayores de edad. El contenido es solo para fines informativos y no constituye asesoramiento profesional.', 'flavor-chat-ia') . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>' . __('Propiedad intelectual', 'flavor-chat-ia') . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . __('Todo el contenido de este sitio, incluyendo textos, gráficos, logos e imágenes, es propiedad del sitio o sus licenciantes y está protegido por las leyes de propiedad intelectual.', 'flavor-chat-ia') . '</p>
<!-- /wp:paragraph -->';
    }
}
