<?php
/**
 * Instalador de tablas de la base de datos
 *
 * @package Flavor_Platform
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

        // Insertar badges predeterminados
        self::insert_default_badges($prefix);

        // Añadir campos WhatsApp a tabla de mensajes
        self::upgrade_whatsapp_fields();

        // Añadir campos E2E (cifrado extremo a extremo) a tablas de mensajes
        self::upgrade_e2e_fields();

        update_option('flavor_db_version', '2.1.0');
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
            ubicacion varchar(255) DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
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

        // Foros - Categorías/Subforos
        $tables[] = "CREATE TABLE {$prefix}foros (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text,
            slug varchar(255) NOT NULL,
            padre_id bigint(20) UNSIGNED DEFAULT NULL,
            orden int(11) DEFAULT 0,
            icono varchar(100) DEFAULT NULL,
            color varchar(7) DEFAULT '#3b82f6',
            solo_admins tinyint(1) DEFAULT 0,
            estado varchar(50) DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY padre_id (padre_id),
            KEY estado (estado),
            KEY orden (orden)
        ) $charset_collate;";

        // Foros - Temas (legacy)
        $tables[] = "CREATE TABLE {$prefix}foros_temas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            foro_id bigint(20) UNSIGNED DEFAULT NULL,
            titulo varchar(255) NOT NULL,
            contenido text,
            autor_id bigint(20) UNSIGNED NOT NULL,
            respuestas int(11) DEFAULT 0,
            respuestas_count int(11) DEFAULT 0,
            vistas int(11) DEFAULT 0,
            estado varchar(50) DEFAULT 'abierto',
            ultima_actividad datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY foro_id (foro_id),
            KEY autor_id (autor_id),
            KEY estado (estado),
            KEY ultima_actividad (ultima_actividad),
            KEY respuestas_count (respuestas_count)
        ) $charset_collate;";

        // Foros - Hilos (nueva estructura)
        $tables[] = "CREATE TABLE {$prefix}foros_hilos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            foro_id bigint(20) UNSIGNED NOT NULL,
            autor_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            contenido text NOT NULL,
            slug varchar(255) NOT NULL,
            es_fijado tinyint(1) DEFAULT 0,
            es_cerrado tinyint(1) DEFAULT 0,
            vistas int(11) DEFAULT 0,
            respuestas int(11) DEFAULT 0,
            ultima_respuesta_id bigint(20) UNSIGNED DEFAULT NULL,
            ultima_respuesta_fecha datetime DEFAULT NULL,
            estado varchar(50) DEFAULT 'abierto',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY foro_id (foro_id),
            KEY autor_id (autor_id),
            KEY created_at (created_at),
            KEY estado (estado),
            KEY fecha_creacion (fecha_creacion),
            KEY es_fijado (es_fijado)
        ) $charset_collate;";

        // Foros - Respuestas
        $tables[] = "CREATE TABLE {$prefix}foros_respuestas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            hilo_id bigint(20) UNSIGNED NOT NULL,
            autor_id bigint(20) UNSIGNED NOT NULL,
            respuesta_padre_id bigint(20) UNSIGNED DEFAULT NULL,
            contenido text NOT NULL,
            es_solucion tinyint(1) DEFAULT 0,
            votos_positivos int(11) DEFAULT 0,
            votos_negativos int(11) DEFAULT 0,
            estado varchar(50) DEFAULT 'publicado',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY hilo_id (hilo_id),
            KEY autor_id (autor_id),
            KEY respuesta_padre_id (respuesta_padre_id),
            KEY es_solucion (es_solucion),
            KEY fecha_creacion (fecha_creacion)
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
            estado varchar(50) DEFAULT 'activo',
            fecha_union datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY colectivo_usuario (colectivo_id, usuario_id),
            KEY estado (estado)
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
            reportado_por bigint(20) UNSIGNED DEFAULT NULL,
            numero_incidencia varchar(50) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            ubicacion varchar(255),
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            categoria varchar(100),
            prioridad varchar(20) DEFAULT 'normal',
            asignado_a bigint(20) UNSIGNED DEFAULT NULL,
            estado varchar(50) DEFAULT 'pendiente',
            fecha_resolucion datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY numero_incidencia (numero_incidencia),
            KEY usuario_id (usuario_id),
            KEY reportado_por (reportado_por),
            KEY estado (estado),
            KEY categoria (categoria),
            KEY prioridad (prioridad)
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

        // Expedientes (sistema de trámites avanzado)
        $tables[] = "CREATE TABLE {$prefix}expedientes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            numero_expediente varchar(50) DEFAULT NULL,
            tipo_tramite_id bigint(20) UNSIGNED DEFAULT NULL,
            solicitante_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED DEFAULT NULL,
            estado varchar(50) DEFAULT 'pendiente',
            estado_actual varchar(50) DEFAULT 'pendiente',
            prioridad varchar(20) DEFAULT 'normal',
            observaciones text DEFAULT NULL,
            datos longtext DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_inicio datetime DEFAULT NULL,
            fecha_limite datetime DEFAULT NULL,
            fecha_resolucion datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY numero_expediente (numero_expediente),
            KEY tipo_tramite_id (tipo_tramite_id),
            KEY solicitante_id (solicitante_id),
            KEY estado (estado),
            KEY estado_actual (estado_actual),
            KEY prioridad (prioridad)
        ) $charset_collate;";

        // Tipos de trámite
        $tables[] = "CREATE TABLE {$prefix}tipos_tramite (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(100) DEFAULT NULL,
            descripcion text DEFAULT NULL,
            icono varchar(50) DEFAULT 'dashicons-clipboard',
            color varchar(20) DEFAULT '#2271b1',
            requisitos text DEFAULT NULL,
            plazo_dias int(11) DEFAULT 30,
            activo tinyint(1) DEFAULT 1,
            orden int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY activo (activo),
            KEY orden (orden)
        ) $charset_collate;";

        // Historial de expedientes
        $tables[] = "CREATE TABLE {$prefix}expedientes_historial (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            expediente_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED DEFAULT NULL,
            accion varchar(100) NOT NULL,
            estado_anterior varchar(50) DEFAULT NULL,
            estado_nuevo varchar(50) DEFAULT NULL,
            observaciones text DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY expediente_id (expediente_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Documentos de expedientes
        $tables[] = "CREATE TABLE {$prefix}expedientes_documentos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            expediente_id bigint(20) UNSIGNED NOT NULL,
            nombre varchar(255) NOT NULL,
            archivo_url varchar(500) DEFAULT NULL,
            tipo varchar(50) DEFAULT NULL,
            tamaño bigint(20) DEFAULT NULL,
            subido_por bigint(20) UNSIGNED DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY expediente_id (expediente_id)
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
            libro_id bigint(20) UNSIGNED DEFAULT NULL,
            libro_titulo varchar(255) NOT NULL,
            fecha_prestamo date NOT NULL,
            fecha_devolucion_prevista date DEFAULT NULL,
            fecha_devolucion date DEFAULT NULL,
            estado varchar(50) DEFAULT 'activo',
            notas text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY libro_id (libro_id),
            KEY estado (estado),
            KEY fecha_devolucion_prevista (fecha_devolucion_prevista)
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

        // Podcast series
        $tables[] = "CREATE TABLE {$prefix}podcast_series (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(255) DEFAULT NULL,
            descripcion text,
            imagen_url varchar(500) DEFAULT NULL,
            autor_id bigint(20) UNSIGNED DEFAULT NULL,
            categoria varchar(100) DEFAULT NULL,
            idioma varchar(10) DEFAULT 'es',
            episodios_count int(11) DEFAULT 0,
            suscriptores_count int(11) DEFAULT 0,
            estado varchar(50) DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY autor_id (autor_id),
            KEY categoria (categoria),
            KEY estado (estado)
        ) $charset_collate;";

        // Podcast episodios
        $tables[] = "CREATE TABLE {$prefix}podcast_episodios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            serie_id bigint(20) UNSIGNED DEFAULT NULL,
            programa_id bigint(20) UNSIGNED DEFAULT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            duracion_segundos int(11) DEFAULT 0,
            url_audio varchar(500),
            imagen_url varchar(500) DEFAULT NULL,
            numero_episodio int(11) DEFAULT NULL,
            temporada int(11) DEFAULT 1,
            reproducciones int(11) DEFAULT 0,
            descargas int(11) DEFAULT 0,
            estado varchar(50) DEFAULT 'publicado',
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY serie_id (serie_id),
            KEY programa_id (programa_id),
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
            numero_factura varchar(50) NOT NULL,
            serie varchar(10) DEFAULT 'F',
            numero_serie int(11) DEFAULT NULL,
            año int(4) DEFAULT NULL,
            usuario_id bigint(20) UNSIGNED DEFAULT NULL,
            cliente_id bigint(20) UNSIGNED DEFAULT NULL,
            cliente_nombre varchar(255) DEFAULT NULL,
            cliente_nif varchar(50) DEFAULT NULL,
            cliente_direccion text DEFAULT NULL,
            cliente_email varchar(255) DEFAULT NULL,
            concepto varchar(255) DEFAULT NULL,
            base_imponible decimal(12,2) DEFAULT 0.00,
            total_iva decimal(12,2) DEFAULT 0.00,
            total decimal(12,2) NOT NULL DEFAULT 0.00,
            estado enum('borrador','emitida','parcial','pagada','vencida','cancelada','pendiente') DEFAULT 'pendiente',
            fecha_emision date DEFAULT NULL,
            fecha_vencimiento date DEFAULT NULL,
            metodo_pago varchar(50) DEFAULT NULL,
            observaciones text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY cliente_id (cliente_id),
            UNIQUE KEY numero_factura (numero_factura),
            KEY estado (estado),
            KEY fecha_emision (fecha_emision)
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

        // =====================================================
        // TABLAS PARA RED DE NODOS FEDERADA
        // =====================================================

        // Tabla de nodos en la red federada
        $tables[] = "CREATE TABLE {$prefix}network_nodes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text,
            site_url varchar(500) NOT NULL,
            api_url varchar(500) NOT NULL,
            logo_url varchar(500) DEFAULT NULL,
            es_nodo_local tinyint(1) DEFAULT 0,
            activo tinyint(1) DEFAULT 1,
            ultimo_sync datetime DEFAULT NULL,
            metadata longtext,
            fecha_registro datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY activo (activo),
            KEY es_nodo_local (es_nodo_local)
        ) $charset_collate;";

        // Tabla de contenido compartido en la red
        $tables[] = "CREATE TABLE {$prefix}network_shared_content (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nodo_id bigint(20) UNSIGNED NOT NULL,
            tipo_contenido varchar(50) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            url_externa varchar(500) DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            visible_red tinyint(1) DEFAULT 1,
            nivel_visibilidad varchar(50) DEFAULT 'privado',
            referencia_local bigint(20) UNSIGNED DEFAULT NULL,
            metadata longtext,
            estado varchar(50) DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY nodo_id (nodo_id),
            KEY tipo_contenido (tipo_contenido),
            KEY visible_red (visible_red),
            KEY nivel_visibilidad (nivel_visibilidad),
            KEY estado (estado),
            UNIQUE KEY nodo_referencia (nodo_id, tipo_contenido, referencia_local)
        ) $charset_collate;";

        // Tabla de relaciones de integraciones entre módulos
        $tables[] = "CREATE TABLE {$prefix}module_integrations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            consumer_type varchar(50) NOT NULL,
            consumer_id bigint(20) UNSIGNED NOT NULL,
            provider_type varchar(50) NOT NULL,
            provider_id bigint(20) UNSIGNED NOT NULL,
            orden int(11) DEFAULT 0,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY consumer (consumer_type, consumer_id),
            KEY provider (provider_type, provider_id),
            UNIQUE KEY unique_relation (consumer_type, consumer_id, provider_type, provider_id)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA SISTEMA DE PRIVACIDAD Y RGPD
        // =====================================================

        // Tabla de consentimientos de privacidad
        $tables[] = "CREATE TABLE {$prefix}privacy_consents (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo_consentimiento varchar(100) NOT NULL,
            consentido tinyint(1) NOT NULL DEFAULT 0,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_usuario (usuario_id),
            KEY idx_tipo (tipo_consentimiento),
            KEY idx_fecha (fecha)
        ) $charset_collate;";

        // Tabla de solicitudes de privacidad (exportación, eliminación)
        $tables[] = "CREATE TABLE {$prefix}privacy_requests (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo enum('exportar', 'eliminar', 'rectificar') NOT NULL,
            estado enum('pendiente', 'procesando', 'completado', 'rechazado') DEFAULT 'pendiente',
            motivo_rechazo text DEFAULT NULL,
            datos longtext DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_procesado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_usuario (usuario_id),
            KEY idx_estado (estado),
            KEY idx_tipo (tipo),
            KEY idx_fecha (fecha_solicitud)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA SISTEMA DE MODERACIÓN
        // =====================================================

        // Tabla de reportes de contenido
        $tables[] = "CREATE TABLE {$prefix}moderation_reports (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tipo_contenido varchar(50) NOT NULL,
            contenido_id bigint(20) UNSIGNED NOT NULL,
            autor_contenido_id bigint(20) UNSIGNED NOT NULL,
            reportador_id bigint(20) UNSIGNED NOT NULL,
            razon varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            contenido_snapshot text DEFAULT NULL,
            estado enum('pendiente', 'en_revision', 'resuelto', 'rechazado', 'escalado') DEFAULT 'pendiente',
            prioridad enum('baja', 'media', 'alta', 'critica') DEFAULT 'media',
            moderador_id bigint(20) UNSIGNED DEFAULT NULL,
            fecha_reporte datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_resolucion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_tipo_contenido (tipo_contenido, contenido_id),
            KEY idx_autor (autor_contenido_id),
            KEY idx_reportador (reportador_id),
            KEY idx_estado (estado),
            KEY idx_prioridad (prioridad),
            KEY idx_moderador (moderador_id),
            KEY idx_fecha (fecha_reporte)
        ) $charset_collate;";

        // Tabla de acciones de moderación
        $tables[] = "CREATE TABLE {$prefix}moderation_actions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            reporte_id bigint(20) UNSIGNED DEFAULT NULL,
            moderador_id bigint(20) UNSIGNED NOT NULL,
            usuario_afectado_id bigint(20) UNSIGNED DEFAULT NULL,
            tipo_contenido varchar(50) DEFAULT NULL,
            contenido_id bigint(20) UNSIGNED DEFAULT NULL,
            accion varchar(50) NOT NULL,
            notas text DEFAULT NULL,
            notificado tinyint(1) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_reporte (reporte_id),
            KEY idx_moderador (moderador_id),
            KEY idx_usuario (usuario_afectado_id),
            KEY idx_accion (accion),
            KEY idx_fecha (fecha)
        ) $charset_collate;";

        // Tabla de sanciones a usuarios
        $tables[] = "CREATE TABLE {$prefix}moderation_sanctions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo enum('warning', 'silenciado', 'ban_temporal', 'ban_permanente') NOT NULL,
            motivo text NOT NULL,
            moderador_id bigint(20) UNSIGNED NOT NULL,
            reporte_id bigint(20) UNSIGNED DEFAULT NULL,
            fecha_inicio datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_fin datetime DEFAULT NULL,
            activa tinyint(1) DEFAULT 1,
            levantada_por bigint(20) UNSIGNED DEFAULT NULL,
            fecha_levantamiento datetime DEFAULT NULL,
            motivo_levantamiento text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_usuario (usuario_id),
            KEY idx_tipo (tipo),
            KEY idx_activa (activa),
            KEY idx_fecha_inicio (fecha_inicio),
            KEY idx_fecha_fin (fecha_fin)
        ) $charset_collate;";

        // Tabla de advertencias (warnings)
        $tables[] = "CREATE TABLE {$prefix}moderation_warnings (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            moderador_id bigint(20) UNSIGNED NOT NULL,
            reporte_id bigint(20) UNSIGNED DEFAULT NULL,
            mensaje text NOT NULL,
            tipo_contenido varchar(50) DEFAULT NULL,
            contenido_id bigint(20) UNSIGNED DEFAULT NULL,
            leida tinyint(1) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_usuario (usuario_id),
            KEY idx_moderador (moderador_id),
            KEY idx_leida (leida),
            KEY idx_fecha (fecha)
        ) $charset_collate;";

        // Tabla de contadores de reportes por contenido (para auto-ocultar)
        $tables[] = "CREATE TABLE {$prefix}moderation_report_counts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tipo_contenido varchar(50) NOT NULL,
            contenido_id bigint(20) UNSIGNED NOT NULL,
            total_reportes int(11) DEFAULT 0,
            auto_ocultado tinyint(1) DEFAULT 0,
            fecha_primer_reporte datetime DEFAULT NULL,
            fecha_ultimo_reporte datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_contenido (tipo_contenido, contenido_id),
            KEY idx_total (total_reportes),
            KEY idx_auto_ocultado (auto_ocultado)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA CHAT ESTADOS (STORIES TIPO WHATSAPP)
        // =====================================================

        // Tabla principal de estados
        $tables[] = "CREATE TABLE {$prefix}chat_estados (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo enum('texto', 'imagen', 'video') DEFAULT 'texto',
            contenido text DEFAULT NULL,
            media_url varchar(500) DEFAULT NULL,
            media_thumbnail varchar(500) DEFAULT NULL,
            color_fondo varchar(7) DEFAULT '#128C7E',
            font_size int(11) DEFAULT 24,
            duracion int(11) DEFAULT 5 COMMENT 'Segundos de visualización',
            privacidad enum('todos', 'contactos', 'contactos_excepto', 'solo_compartir') DEFAULT 'contactos',
            excepciones text DEFAULT NULL COMMENT 'JSON de user_ids',
            compartir_con text DEFAULT NULL COMMENT 'JSON de user_ids',
            visualizaciones_count int(11) DEFAULT 0,
            respuestas_count int(11) DEFAULT 0,
            activo tinyint(1) DEFAULT 1,
            expira_en datetime NOT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_usuario (usuario_id),
            KEY idx_activo (activo),
            KEY idx_expira (expira_en),
            KEY idx_fecha (fecha_creacion)
        ) $charset_collate;";

        // Tabla de visualizaciones de estados
        $tables[] = "CREATE TABLE {$prefix}chat_estados_vistas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            estado_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_vista (estado_id, usuario_id),
            KEY idx_estado (estado_id),
            KEY idx_usuario (usuario_id)
        ) $charset_collate;";

        // Tabla de reacciones a estados
        $tables[] = "CREATE TABLE {$prefix}chat_estados_reacciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            estado_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            emoji varchar(10) NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_reaccion (estado_id, usuario_id),
            KEY idx_estado (estado_id)
        ) $charset_collate;";

        // Tabla de respuestas a estados
        $tables[] = "CREATE TABLE {$prefix}chat_estados_respuestas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            estado_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            mensaje text NOT NULL,
            leido tinyint(1) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_estado (estado_id),
            KEY idx_usuario (usuario_id),
            KEY idx_leido (leido)
        ) $charset_collate;";

        // Tabla de usuarios silenciados en estados
        $tables[] = "CREATE TABLE {$prefix}chat_estados_silenciados (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            silenciado_id bigint(20) UNSIGNED NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_silenciado (usuario_id, silenciado_id)
        ) $charset_collate;";

        // =====================================================
        // CAMPOS ADICIONALES PARA WHATSAPP FEATURES EN MENSAJES
        // =====================================================
        // Nota: Estos campos se añaden a la tabla existente de mensajes
        // via ALTER TABLE al actualizar. Ver método upgrade_whatsapp_fields()

        // =====================================================
        // TABLAS PARA RED SOCIAL (PUBLICACIONES, PERFILES, INTERACCIONES)
        // =====================================================

        // Perfiles de usuarios en la red social
        $tables[] = "CREATE TABLE {$prefix}social_perfiles (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            bio text DEFAULT NULL,
            ubicacion varchar(255) DEFAULT NULL,
            sitio_web varchar(500) DEFAULT NULL,
            avatar_url varchar(500) DEFAULT NULL,
            portada_url varchar(500) DEFAULT NULL,
            seguidores_count int(11) DEFAULT 0,
            siguiendo_count int(11) DEFAULT 0,
            publicaciones_count int(11) DEFAULT 0,
            verificado tinyint(1) DEFAULT 0,
            privado tinyint(1) DEFAULT 0,
            ultima_actividad datetime DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_id (usuario_id),
            KEY idx_verificado (verificado),
            KEY idx_ultima_actividad (ultima_actividad)
        ) $charset_collate;";

        // Publicaciones de la red social
        $tables[] = "CREATE TABLE {$prefix}social_publicaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            contenido text NOT NULL,
            tipo enum('texto', 'imagen', 'video', 'enlace', 'encuesta') DEFAULT 'texto',
            media_urls text DEFAULT NULL COMMENT 'JSON array de URLs',
            enlace_url varchar(500) DEFAULT NULL,
            enlace_titulo varchar(255) DEFAULT NULL,
            enlace_descripcion text DEFAULT NULL,
            enlace_imagen varchar(500) DEFAULT NULL,
            ubicacion varchar(255) DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            privacidad enum('publico', 'seguidores', 'privado') DEFAULT 'publico',
            permite_comentarios tinyint(1) DEFAULT 1,
            es_fijada tinyint(1) DEFAULT 0,
            es_compartido tinyint(1) DEFAULT 0,
            publicacion_original_id bigint(20) UNSIGNED DEFAULT NULL,
            likes_count int(11) DEFAULT 0,
            comentarios_count int(11) DEFAULT 0,
            compartidos_count int(11) DEFAULT 0,
            vistas_count int(11) DEFAULT 0,
            estado enum('publicado', 'borrador', 'oculto', 'eliminado') DEFAULT 'publicado',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_usuario (usuario_id),
            KEY idx_estado (estado),
            KEY idx_privacidad (privacidad),
            KEY idx_fecha (fecha_creacion),
            KEY idx_tipo (tipo),
            KEY idx_ubicacion (ubicacion),
            KEY idx_compartido (publicacion_original_id),
            FULLTEXT KEY ft_contenido (contenido)
        ) $charset_collate;";

        // Comentarios en publicaciones
        $tables[] = "CREATE TABLE {$prefix}social_comentarios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            publicacion_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            padre_id bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Para respuestas anidadas',
            contenido text NOT NULL,
            likes_count int(11) DEFAULT 0,
            respuestas_count int(11) DEFAULT 0,
            estado enum('activo', 'oculto', 'eliminado') DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_publicacion (publicacion_id),
            KEY idx_usuario (usuario_id),
            KEY idx_padre (padre_id),
            KEY idx_fecha (fecha_creacion),
            KEY idx_estado (estado)
        ) $charset_collate;";

        // Likes en publicaciones y comentarios
        $tables[] = "CREATE TABLE {$prefix}social_likes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo_contenido enum('publicacion', 'comentario') NOT NULL,
            contenido_id bigint(20) UNSIGNED NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_like (usuario_id, tipo_contenido, contenido_id),
            KEY idx_contenido (tipo_contenido, contenido_id),
            KEY idx_fecha (fecha)
        ) $charset_collate;";

        // Hashtags
        $tables[] = "CREATE TABLE {$prefix}social_hashtags (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            usos_count int(11) DEFAULT 0,
            trending tinyint(1) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nombre (nombre),
            KEY idx_usos (usos_count),
            KEY idx_trending (trending)
        ) $charset_collate;";

        // Relación publicaciones-hashtags
        $tables[] = "CREATE TABLE {$prefix}social_publicaciones_hashtags (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            publicacion_id bigint(20) UNSIGNED NOT NULL,
            hashtag_id bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_pub_hash (publicacion_id, hashtag_id),
            KEY idx_hashtag (hashtag_id)
        ) $charset_collate;";

        // Seguidores (relaciones de seguimiento) - estructura original
        $tables[] = "CREATE TABLE {$prefix}social_seguidores (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            seguidor_id bigint(20) UNSIGNED NOT NULL,
            seguido_id bigint(20) UNSIGNED NOT NULL,
            estado enum('activo', 'pendiente', 'bloqueado') DEFAULT 'activo',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_follow (seguidor_id, seguido_id),
            KEY idx_seguidor (seguidor_id),
            KEY idx_seguido (seguido_id),
            KEY idx_estado (estado)
        ) $charset_collate;";

        // Seguimientos (usada por módulo red_social)
        $tables[] = "CREATE TABLE {$prefix}social_seguimientos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            seguidor_id bigint(20) UNSIGNED NOT NULL,
            seguido_id bigint(20) UNSIGNED NOT NULL,
            estado enum('activo', 'pendiente', 'bloqueado') DEFAULT 'activo',
            notificaciones_activas tinyint(1) DEFAULT 1,
            fecha_seguimiento datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY seguidor_seguido (seguidor_id, seguido_id),
            KEY seguido_id (seguido_id),
            KEY estado (estado),
            KEY fecha_seguimiento (fecha_seguimiento)
        ) $charset_collate;";

        // Menciones en publicaciones y comentarios
        $tables[] = "CREATE TABLE {$prefix}social_menciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tipo_contenido enum('publicacion', 'comentario') NOT NULL,
            contenido_id bigint(20) UNSIGNED NOT NULL,
            usuario_mencionado_id bigint(20) UNSIGNED NOT NULL,
            notificado tinyint(1) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_contenido (tipo_contenido, contenido_id),
            KEY idx_mencionado (usuario_mencionado_id),
            KEY idx_notificado (notificado)
        ) $charset_collate;";

        // Guardados/Bookmarks
        $tables[] = "CREATE TABLE {$prefix}social_guardados (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            publicacion_id bigint(20) UNSIGNED NOT NULL,
            coleccion varchar(100) DEFAULT 'general',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_guardado (usuario_id, publicacion_id),
            KEY idx_usuario (usuario_id),
            KEY idx_coleccion (coleccion)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA SISTEMA DE REPUTACIÓN Y GAMIFICACIÓN
        // =====================================================

        // Reputación de usuarios
        $tables[] = "CREATE TABLE {$prefix}social_reputacion (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            puntos_totales int(11) DEFAULT 0,
            nivel varchar(50) DEFAULT 'nuevo',
            puntos_semana int(11) DEFAULT 0,
            puntos_mes int(11) DEFAULT 0,
            racha_dias int(11) DEFAULT 0,
            ultima_actividad datetime DEFAULT NULL,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_id (usuario_id),
            KEY nivel (nivel),
            KEY puntos_totales (puntos_totales)
        ) $charset_collate;";

        // Badges/Insignias disponibles
        $tables[] = "CREATE TABLE {$prefix}social_badges (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            icono varchar(255) DEFAULT NULL,
            color varchar(20) DEFAULT '#3b82f6',
            categoria enum('participacion','creacion','comunidad','especial','temporal') DEFAULT 'participacion',
            puntos_requeridos int(11) DEFAULT 0,
            condicion_especial text DEFAULT NULL,
            es_unico tinyint(1) DEFAULT 0,
            activo tinyint(1) DEFAULT 1,
            orden int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY categoria (categoria),
            KEY activo (activo)
        ) $charset_collate;";

        // Badges obtenidos por usuarios
        $tables[] = "CREATE TABLE {$prefix}social_usuario_badges (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            badge_id bigint(20) UNSIGNED NOT NULL,
            fecha_obtenido datetime DEFAULT CURRENT_TIMESTAMP,
            destacado tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_badge (usuario_id, badge_id),
            KEY badge_id (badge_id),
            KEY fecha_obtenido (fecha_obtenido)
        ) $charset_collate;";

        // Historial de puntos (para auditoría y desglose)
        $tables[] = "CREATE TABLE {$prefix}social_historial_puntos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            puntos int(11) NOT NULL,
            tipo_accion varchar(50) NOT NULL,
            descripcion varchar(255) DEFAULT NULL,
            referencia_id bigint(20) UNSIGNED DEFAULT NULL,
            referencia_tipo varchar(50) DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY tipo_accion (tipo_accion),
            KEY fecha_creacion (fecha_creacion)
        ) $charset_collate;";

        // Engagement entre usuarios (para algoritmo de feed)
        $tables[] = "CREATE TABLE {$prefix}social_engagement (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            autor_id bigint(20) UNSIGNED NOT NULL,
            tipo_interaccion enum('like','comentario','compartido','guardado','clic','tiempo_lectura') NOT NULL,
            contenido_id bigint(20) UNSIGNED DEFAULT NULL,
            peso decimal(5,2) DEFAULT 1.00,
            fecha_interaccion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_autor (usuario_id, autor_id),
            KEY autor_id (autor_id),
            KEY fecha_interaccion (fecha_interaccion),
            KEY tipo_interaccion (tipo_interaccion)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA SISTEMA DE ENCUESTAS/FORMULARIOS
        // =====================================================

        // Encuestas principales
        $tables[] = "CREATE TABLE {$prefix}encuestas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(500) NOT NULL,
            descripcion text DEFAULT NULL,
            autor_id bigint(20) UNSIGNED NOT NULL,
            estado enum('borrador', 'activa', 'cerrada', 'archivada') DEFAULT 'borrador',
            tipo enum('encuesta', 'formulario', 'quiz') DEFAULT 'encuesta',
            contexto_tipo varchar(50) DEFAULT NULL COMMENT 'chat_grupo, foro, red_social, comunidad, etc.',
            contexto_id bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID de la entidad asociada',
            es_anonima tinyint(1) DEFAULT 0,
            permite_multiples tinyint(1) DEFAULT 0 COMMENT 'Múltiples opciones por pregunta',
            mostrar_resultados enum('siempre', 'al_votar', 'al_cerrar', 'nunca') DEFAULT 'al_votar',
            fecha_cierre datetime DEFAULT NULL,
            total_respuestas int(11) DEFAULT 0 COMMENT 'Cache de respuestas totales',
            total_participantes int(11) DEFAULT 0 COMMENT 'Cache de participantes únicos',
            configuracion longtext DEFAULT NULL COMMENT 'JSON con config adicional',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_autor (autor_id),
            KEY idx_estado (estado),
            KEY idx_tipo (tipo),
            KEY idx_contexto (contexto_tipo, contexto_id),
            KEY idx_fecha_cierre (fecha_cierre),
            KEY idx_fecha_creacion (fecha_creacion)
        ) $charset_collate;";

        // Campos/Preguntas de encuestas
        $tables[] = "CREATE TABLE {$prefix}encuestas_campos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            encuesta_id bigint(20) UNSIGNED NOT NULL,
            tipo enum('texto', 'textarea', 'seleccion_unica', 'seleccion_multiple', 'fecha', 'numero', 'escala', 'si_no', 'estrellas') DEFAULT 'seleccion_unica',
            etiqueta varchar(500) NOT NULL COMMENT 'Texto de la pregunta',
            descripcion text DEFAULT NULL COMMENT 'Descripción o ayuda',
            opciones longtext DEFAULT NULL COMMENT 'JSON array de opciones para selección',
            es_requerido tinyint(1) DEFAULT 1,
            orden int(11) DEFAULT 0,
            configuracion longtext DEFAULT NULL COMMENT 'JSON: min, max, step, etc.',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_encuesta (encuesta_id),
            KEY idx_orden (orden),
            KEY idx_tipo (tipo)
        ) $charset_collate;";

        // Respuestas individuales a campos
        $tables[] = "CREATE TABLE {$prefix}encuestas_respuestas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            encuesta_id bigint(20) UNSIGNED NOT NULL,
            campo_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED DEFAULT NULL COMMENT 'NULL si anónima',
            sesion_id varchar(64) DEFAULT NULL COMMENT 'Tracking para respuestas anónimas',
            valor text DEFAULT NULL COMMENT 'Respuesta de texto/número',
            opcion_index int(11) DEFAULT NULL COMMENT 'Índice de opción seleccionada (para optimizar conteo)',
            fecha_respuesta datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_encuesta (encuesta_id),
            KEY idx_campo (campo_id),
            KEY idx_usuario (usuario_id),
            KEY idx_sesion (sesion_id),
            KEY idx_opcion (campo_id, opcion_index),
            KEY idx_fecha (fecha_respuesta)
        ) $charset_collate;";

        // Participantes en encuestas (tracking de quién ha completado)
        $tables[] = "CREATE TABLE {$prefix}encuestas_participantes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            encuesta_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED DEFAULT NULL,
            sesion_id varchar(64) DEFAULT NULL,
            completada tinyint(1) DEFAULT 0,
            fecha_inicio datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_completada datetime DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_participante (encuesta_id, usuario_id),
            KEY idx_encuesta (encuesta_id),
            KEY idx_usuario (usuario_id),
            KEY idx_sesion (sesion_id),
            KEY idx_completada (completada)
        ) $charset_collate;";

        // ==========================================
        // SISTEMA DE REPUTACIÓN
        // ==========================================

        // Reputación de usuarios
        $tables[] = "CREATE TABLE {$prefix}social_reputacion (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            puntos_totales int(11) DEFAULT 0,
            nivel varchar(50) DEFAULT 'nuevo',
            puntos_semana int(11) DEFAULT 0,
            puntos_mes int(11) DEFAULT 0,
            racha_dias int(11) DEFAULT 0,
            ultima_actividad datetime DEFAULT NULL,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_id (usuario_id),
            KEY nivel (nivel),
            KEY puntos_totales (puntos_totales)
        ) $charset_collate;";

        // Badges/Insignias disponibles
        $tables[] = "CREATE TABLE {$prefix}social_badges (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            icono varchar(255) DEFAULT NULL,
            color varchar(20) DEFAULT '#3b82f6',
            categoria enum('participacion','creacion','comunidad','especial','temporal') DEFAULT 'participacion',
            puntos_requeridos int(11) DEFAULT 0,
            condicion_especial text DEFAULT NULL,
            es_unico tinyint(1) DEFAULT 0,
            activo tinyint(1) DEFAULT 1,
            orden int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY categoria (categoria),
            KEY activo (activo)
        ) $charset_collate;";

        // Badges obtenidos por usuarios
        $tables[] = "CREATE TABLE {$prefix}social_usuario_badges (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            badge_id bigint(20) UNSIGNED NOT NULL,
            fecha_obtenido datetime DEFAULT CURRENT_TIMESTAMP,
            destacado tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_badge (usuario_id, badge_id),
            KEY badge_id (badge_id),
            KEY fecha_obtenido (fecha_obtenido)
        ) $charset_collate;";

        // Historial de puntos ganados
        $tables[] = "CREATE TABLE {$prefix}social_historial_puntos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            puntos int(11) NOT NULL,
            tipo_accion varchar(50) NOT NULL,
            descripcion varchar(255) DEFAULT NULL,
            referencia_id bigint(20) UNSIGNED DEFAULT NULL,
            referencia_tipo varchar(50) DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY tipo_accion (tipo_accion),
            KEY fecha_creacion (fecha_creacion)
        ) $charset_collate;";

        // Engagement entre usuarios (para feed inteligente)
        $tables[] = "CREATE TABLE {$prefix}social_engagement (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            autor_id bigint(20) UNSIGNED NOT NULL,
            tipo_interaccion enum('like','comentario','compartido','guardado','clic','tiempo_lectura') NOT NULL,
            contenido_id bigint(20) UNSIGNED DEFAULT NULL,
            peso decimal(5,2) DEFAULT 1.00,
            fecha_interaccion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_autor (usuario_id, autor_id),
            KEY autor_id (autor_id),
            KEY fecha_interaccion (fecha_interaccion),
            KEY tipo_interaccion (tipo_interaccion)
        ) $charset_collate;";

        // Registro de publicaciones federadas recibidas
        $tables[] = "CREATE TABLE {$prefix}social_federacion (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            publicacion_id bigint(20) UNSIGNED NOT NULL,
            nodo_origen varchar(500) NOT NULL,
            nodo_id bigint(20) UNSIGNED DEFAULT NULL,
            publicacion_origen_id bigint(20) UNSIGNED DEFAULT NULL,
            fecha_recibido datetime DEFAULT CURRENT_TIMESTAMP,
            estado enum('recibido','procesado','rechazado') DEFAULT 'recibido',
            PRIMARY KEY (id),
            KEY publicacion_id (publicacion_id),
            KEY nodo_origen (nodo_origen(191)),
            KEY nodo_id (nodo_id),
            KEY fecha_recibido (fecha_recibido)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA INTEGRACIONES POST-MÓDULOS
        // =====================================================

        // Relaciones posts WP con eventos
        $tables[] = "CREATE TABLE {$prefix}eventos_posts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            evento_id bigint(20) UNSIGNED NOT NULL,
            post_id bigint(20) UNSIGNED NOT NULL,
            tipo enum('noticia','recurso','relacionado') DEFAULT 'relacionado',
            orden int(11) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY evento_post (evento_id, post_id),
            KEY evento_id (evento_id),
            KEY post_id (post_id)
        ) $charset_collate;";

        // Relaciones posts WP con cursos
        $tables[] = "CREATE TABLE {$prefix}cursos_posts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            curso_id bigint(20) UNSIGNED NOT NULL,
            post_id bigint(20) UNSIGNED NOT NULL,
            leccion_id bigint(20) UNSIGNED DEFAULT NULL,
            tipo enum('material','lectura','recurso') DEFAULT 'recurso',
            orden int(11) DEFAULT 0,
            obligatorio tinyint(1) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY curso_post (curso_id, post_id),
            KEY curso_id (curso_id),
            KEY post_id (post_id),
            KEY leccion_id (leccion_id)
        ) $charset_collate;";

        // Relaciones posts WP con talleres
        $tables[] = "CREATE TABLE {$prefix}talleres_posts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            taller_id bigint(20) UNSIGNED NOT NULL,
            post_id bigint(20) UNSIGNED NOT NULL,
            tipo enum('material','recurso','resultado') DEFAULT 'recurso',
            orden int(11) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY taller_post (taller_id, post_id),
            KEY taller_id (taller_id),
            KEY post_id (post_id)
        ) $charset_collate;";

        // Relaciones posts WP con campañas
        $tables[] = "CREATE TABLE {$prefix}campanias_posts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            campania_id bigint(20) UNSIGNED NOT NULL,
            post_id bigint(20) UNSIGNED NOT NULL,
            tipo enum('noticia','apoyo','resultado') DEFAULT 'apoyo',
            destacado tinyint(1) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY campania_post (campania_id, post_id),
            KEY campania_id (campania_id),
            KEY post_id (post_id)
        ) $charset_collate;";

        // Relaciones posts WP con comunidades
        $tables[] = "CREATE TABLE {$prefix}comunidades_posts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) UNSIGNED NOT NULL,
            post_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            mensaje text DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY comunidad_post (comunidad_id, post_id),
            KEY comunidad_id (comunidad_id),
            KEY post_id (post_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Relaciones posts WP con colectivos
        $tables[] = "CREATE TABLE {$prefix}colectivos_posts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            colectivo_id bigint(20) UNSIGNED NOT NULL,
            post_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo enum('compartido','creado','referencia') DEFAULT 'compartido',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY colectivo_post (colectivo_id, post_id),
            KEY colectivo_id (colectivo_id),
            KEY post_id (post_id)
        ) $charset_collate;";

        // Contenidos de newsletters
        $tables[] = "CREATE TABLE {$prefix}email_newsletter_posts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            newsletter_id bigint(20) UNSIGNED NOT NULL,
            post_id bigint(20) UNSIGNED NOT NULL,
            titulo_personalizado varchar(255) DEFAULT NULL,
            extracto_personalizado text DEFAULT NULL,
            orden int(11) DEFAULT 0,
            incluido tinyint(1) DEFAULT 1,
            fecha_agregado datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY newsletter_post (newsletter_id, post_id),
            KEY newsletter_id (newsletter_id),
            KEY post_id (post_id)
        ) $charset_collate;";

        // Recursos de biblioteca desde posts
        $tables[] = "CREATE TABLE {$prefix}biblioteca_posts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            categoria_id bigint(20) UNSIGNED DEFAULT NULL,
            post_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) DEFAULT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('articulo','guia','tutorial','referencia') DEFAULT 'articulo',
            destacado tinyint(1) DEFAULT 0,
            vistas int(11) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id),
            KEY categoria_id (categoria_id),
            KEY usuario_id (usuario_id),
            KEY tipo (tipo)
        ) $charset_collate;";

        // Historial de integraciones realizadas
        $tables[] = "CREATE TABLE {$prefix}posts_integraciones_log (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            modulo varchar(50) NOT NULL,
            elemento_id bigint(20) UNSIGNED DEFAULT NULL,
            elemento_tipo varchar(50) DEFAULT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            resultado_id bigint(20) UNSIGNED DEFAULT NULL,
            estado enum('exito','error','pendiente') DEFAULT 'exito',
            mensaje text DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY modulo (modulo),
            KEY usuario_id (usuario_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA ECONOMÍA DEL DON
        // =====================================================

        // Dones ofrecidos
        $tables[] = "CREATE TABLE {$prefix}economia_dones (
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

        // Solicitudes de dones
        $tables[] = "CREATE TABLE {$prefix}economia_solicitudes (
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

        // Entregas de dones
        $tables[] = "CREATE TABLE {$prefix}economia_entregas (
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

        // Gratitudes (muro de agradecimientos)
        $tables[] = "CREATE TABLE {$prefix}economia_gratitudes (
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

        // =====================================================
        // TABLAS PARA BIBLIOTECA COMUNITARIA
        // =====================================================

        // Libros de la biblioteca
        $tables[] = "CREATE TABLE {$prefix}biblioteca_libros (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            propietario_id bigint(20) UNSIGNED NOT NULL,
            isbn varchar(20) DEFAULT NULL,
            titulo varchar(500) NOT NULL,
            autor varchar(255) NOT NULL,
            editorial varchar(255) DEFAULT NULL,
            ano_publicacion int(11) DEFAULT NULL,
            idioma varchar(50) DEFAULT 'Español',
            genero varchar(100) DEFAULT NULL,
            num_paginas int(11) DEFAULT NULL,
            descripcion text DEFAULT NULL,
            portada_url varchar(500) DEFAULT NULL,
            estado_fisico enum('excelente','bueno','aceptable','desgastado') DEFAULT 'bueno',
            disponibilidad enum('disponible','prestado','reservado','no_disponible') DEFAULT 'disponible',
            tipo enum('donado','prestamo','intercambio') DEFAULT 'prestamo',
            ubicacion varchar(255) DEFAULT NULL,
            valoracion_media decimal(3,2) DEFAULT 0,
            veces_prestado int(11) DEFAULT 0,
            fecha_agregado datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY propietario_id (propietario_id),
            KEY isbn (isbn),
            KEY disponibilidad (disponibilidad),
            KEY genero (genero),
            FULLTEXT KEY ft_busqueda (titulo, autor, descripcion)
        ) $charset_collate;";

        // Reservas de libros
        $tables[] = "CREATE TABLE {$prefix}biblioteca_reservas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            libro_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion datetime DEFAULT NULL,
            estado enum('pendiente','confirmada','cancelada','expirada','convertida') DEFAULT 'pendiente',
            notificado tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY libro_id (libro_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Reseñas de libros
        $tables[] = "CREATE TABLE {$prefix}biblioteca_resenas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            libro_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            valoracion int(11) DEFAULT NULL,
            resena text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY libro_usuario (libro_id, usuario_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA BANCO DE TIEMPO
        // =====================================================

        // Servicios ofrecidos
        $tables[] = "CREATE TABLE {$prefix}banco_tiempo_servicios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            categoria varchar(50) DEFAULT 'otros',
            horas_estimadas decimal(4,2) DEFAULT 1.00,
            estado varchar(20) DEFAULT 'activo',
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY categoria (categoria),
            KEY estado (estado)
        ) $charset_collate;";

        // Transacciones/Intercambios
        $tables[] = "CREATE TABLE {$prefix}banco_tiempo_transacciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            servicio_id bigint(20) UNSIGNED DEFAULT NULL,
            usuario_solicitante_id bigint(20) UNSIGNED NOT NULL,
            usuario_receptor_id bigint(20) UNSIGNED NOT NULL,
            horas decimal(4,2) NOT NULL,
            mensaje text DEFAULT NULL,
            fecha_preferida datetime DEFAULT NULL,
            estado varchar(20) DEFAULT 'pendiente',
            motivo_cancelacion text DEFAULT NULL,
            valoracion_solicitante tinyint(1) DEFAULT NULL,
            valoracion_receptor tinyint(1) DEFAULT NULL,
            comentario_solicitante text DEFAULT NULL,
            comentario_receptor text DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
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

        // Reputación de usuarios
        $tables[] = "CREATE TABLE {$prefix}banco_tiempo_reputacion (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            total_intercambios_completados int(11) DEFAULT 0,
            total_horas_dadas decimal(8,2) DEFAULT 0,
            total_horas_recibidas decimal(8,2) DEFAULT 0,
            rating_promedio decimal(3,2) DEFAULT 0,
            rating_puntualidad decimal(3,2) DEFAULT 0,
            rating_calidad decimal(3,2) DEFAULT 0,
            rating_comunicacion decimal(3,2) DEFAULT 0,
            fecha_primer_intercambio datetime DEFAULT NULL,
            fecha_ultimo_intercambio datetime DEFAULT NULL,
            estado_verificacion enum('pendiente','verificado','destacado','mentor') DEFAULT 'pendiente',
            fecha_verificacion datetime DEFAULT NULL,
            verificado_por bigint(20) UNSIGNED DEFAULT NULL,
            badges longtext DEFAULT NULL,
            nivel int(3) DEFAULT 1,
            puntos_confianza int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_id (usuario_id),
            KEY estado_verificacion (estado_verificacion),
            KEY rating_promedio (rating_promedio),
            KEY nivel (nivel)
        ) $charset_collate;";

        // Donaciones solidarias
        $tables[] = "CREATE TABLE {$prefix}banco_tiempo_donaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            donante_id bigint(20) UNSIGNED NOT NULL,
            beneficiario_id bigint(20) UNSIGNED DEFAULT NULL,
            tipo enum('fondo_comunitario','regalo_directo','emergencia') DEFAULT 'fondo_comunitario',
            horas decimal(6,2) NOT NULL,
            motivo varchar(255) DEFAULT NULL,
            mensaje text DEFAULT NULL,
            estado enum('pendiente','aceptada','rechazada','utilizada') DEFAULT 'pendiente',
            fecha_donacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_utilizacion datetime DEFAULT NULL,
            utilizada_por bigint(20) UNSIGNED DEFAULT NULL,
            transaccion_origen_id bigint(20) UNSIGNED DEFAULT NULL,
            PRIMARY KEY (id),
            KEY donante_id (donante_id),
            KEY beneficiario_id (beneficiario_id),
            KEY tipo (tipo),
            KEY estado (estado),
            KEY fecha_donacion (fecha_donacion)
        ) $charset_collate;";

        // Métricas del sistema
        $tables[] = "CREATE TABLE {$prefix}banco_tiempo_metricas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            periodo_inicio date NOT NULL,
            periodo_fin date NOT NULL,
            tipo_periodo enum('diario','semanal','mensual','trimestral','anual') DEFAULT 'mensual',
            total_usuarios_activos int(11) DEFAULT 0,
            nuevos_usuarios int(11) DEFAULT 0,
            total_intercambios int(11) DEFAULT 0,
            total_horas_intercambiadas decimal(10,2) DEFAULT 0,
            horas_donadas_periodo decimal(10,2) DEFAULT 0,
            fondo_comunitario_actual decimal(10,2) DEFAULT 0,
            indice_equidad decimal(5,4) DEFAULT 0,
            categoria_mas_demandada varchar(50) DEFAULT NULL,
            categoria_menos_demandada varchar(50) DEFAULT NULL,
            ratio_oferta_demanda longtext DEFAULT NULL,
            alertas_generadas longtext DEFAULT NULL,
            usuarios_con_deuda_alta int(11) DEFAULT 0,
            usuarios_con_excedente_alto int(11) DEFAULT 0,
            puntuacion_sostenibilidad int(3) DEFAULT 0,
            datos_detalle longtext DEFAULT NULL,
            fecha_calculo datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY periodo_inicio (periodo_inicio),
            KEY tipo_periodo (tipo_periodo),
            KEY puntuacion_sostenibilidad (puntuacion_sostenibilidad)
        ) $charset_collate;";

        // Valoraciones detalladas
        $tables[] = "CREATE TABLE {$prefix}banco_tiempo_valoraciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            transaccion_id bigint(20) UNSIGNED NOT NULL,
            valorador_id bigint(20) UNSIGNED NOT NULL,
            valorado_id bigint(20) UNSIGNED NOT NULL,
            rol_valorador enum('solicitante','receptor') NOT NULL,
            rating_general tinyint(1) DEFAULT NULL,
            rating_puntualidad tinyint(1) DEFAULT NULL,
            rating_calidad tinyint(1) DEFAULT NULL,
            rating_comunicacion tinyint(1) DEFAULT NULL,
            comentario text DEFAULT NULL,
            es_publica tinyint(1) DEFAULT 1,
            fecha_valoracion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY transaccion_valorador (transaccion_id, valorador_id),
            KEY valorado_id (valorado_id),
            KEY rating_general (rating_general),
            KEY fecha_valoracion (fecha_valoracion)
        ) $charset_collate;";

        // Límites y alertas por usuario
        $tables[] = "CREATE TABLE {$prefix}banco_tiempo_limites (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            saldo_actual decimal(8,2) DEFAULT 0,
            limite_deuda decimal(8,2) DEFAULT -20.00,
            limite_acumulacion decimal(8,2) DEFAULT 100.00,
            alerta_activa tinyint(1) DEFAULT 0,
            tipo_alerta varchar(50) DEFAULT NULL,
            fecha_ultima_alerta datetime DEFAULT NULL,
            en_plan_equilibrio tinyint(1) DEFAULT 0,
            notas_plan text DEFAULT NULL,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_id (usuario_id),
            KEY saldo_actual (saldo_actual),
            KEY alerta_activa (alerta_activa)
        ) $charset_collate;";

        // Intercambios programados
        $tables[] = "CREATE TABLE {$prefix}banco_tiempo_intercambios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            transaccion_id bigint(20) UNSIGNED NOT NULL,
            ofertante_id bigint(20) UNSIGNED DEFAULT NULL,
            solicitante_id bigint(20) UNSIGNED DEFAULT NULL,
            servicio_id bigint(20) UNSIGNED DEFAULT NULL,
            fecha_programada datetime NOT NULL,
            lugar varchar(255) DEFAULT NULL,
            notas text DEFAULT NULL,
            estado varchar(50) DEFAULT 'pendiente',
            recordatorio_enviado tinyint(1) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY transaccion_id (transaccion_id),
            KEY ofertante_id (ofertante_id),
            KEY solicitante_id (solicitante_id),
            KEY estado (estado),
            KEY fecha_programada (fecha_programada)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA GRUPOS DE CONSUMO
        // =====================================================

        // Pedidos individuales
        $tables[] = "CREATE TABLE {$prefix}gc_pedidos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ciclo_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            producto_id bigint(20) UNSIGNED NOT NULL,
            cantidad decimal(10,2) NOT NULL,
            precio_unitario decimal(10,2) NOT NULL,
            estado varchar(20) DEFAULT 'pendiente',
            notas text DEFAULT NULL,
            fecha_pedido datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ciclo_id (ciclo_id),
            KEY usuario_id (usuario_id),
            KEY producto_id (producto_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Líneas de pedido
        $tables[] = "CREATE TABLE {$prefix}gc_pedidos_lineas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            pedido_id bigint(20) UNSIGNED NOT NULL,
            producto_id bigint(20) UNSIGNED NOT NULL,
            cantidad decimal(10,2) NOT NULL,
            precio_unitario decimal(10,2) NOT NULL,
            subtotal decimal(10,2) NOT NULL,
            notas text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY pedido_id (pedido_id),
            KEY producto_id (producto_id)
        ) $charset_collate;";

        // Entregas
        $tables[] = "CREATE TABLE {$prefix}gc_entregas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ciclo_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            total_pedido decimal(10,2) DEFAULT 0,
            gastos_gestion decimal(10,2) DEFAULT 0,
            total_final decimal(10,2) DEFAULT 0,
            estado_pago varchar(20) DEFAULT 'pendiente',
            fecha_pago datetime DEFAULT NULL,
            metodo_pago varchar(50) DEFAULT NULL,
            estado_recogida varchar(20) DEFAULT 'pendiente',
            fecha_recogida datetime DEFAULT NULL,
            recogido_por varchar(255) DEFAULT NULL,
            notas text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ciclo_id (ciclo_id),
            KEY usuario_id (usuario_id),
            KEY estado_pago (estado_pago),
            KEY estado_recogida (estado_recogida)
        ) $charset_collate;";

        // Consolidado por productor
        $tables[] = "CREATE TABLE {$prefix}gc_consolidado (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ciclo_id bigint(20) UNSIGNED NOT NULL,
            productor_id bigint(20) UNSIGNED DEFAULT NULL,
            producto_id bigint(20) UNSIGNED NOT NULL,
            cantidad_total decimal(10,2) DEFAULT 0,
            numero_pedidos int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'pendiente',
            fecha_solicitud datetime DEFAULT NULL,
            fecha_confirmacion datetime DEFAULT NULL,
            fecha_entrega datetime DEFAULT NULL,
            notas text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY ciclo_producto (ciclo_id, producto_id),
            KEY productor_id (productor_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Consumidores/Membresía
        $tables[] = "CREATE TABLE {$prefix}gc_consumidores (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            grupo_id bigint(20) UNSIGNED NOT NULL,
            rol enum('consumidor','coordinador','productor') DEFAULT 'consumidor',
            estado enum('pendiente','activo','suspendido','baja') DEFAULT 'pendiente',
            preferencias_alimentarias text DEFAULT NULL,
            alergias text DEFAULT NULL,
            saldo_pendiente decimal(10,2) DEFAULT 0,
            notas_internas text DEFAULT NULL,
            fecha_alta datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_baja datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_grupo (usuario_id, grupo_id),
            KEY grupo_id (grupo_id),
            KEY estado (estado),
            KEY rol (rol)
        ) $charset_collate;";

        // Suscripciones a cestas
        $tables[] = "CREATE TABLE {$prefix}gc_suscripciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            consumidor_id bigint(20) UNSIGNED NOT NULL,
            tipo_cesta_id bigint(20) UNSIGNED DEFAULT NULL,
            frecuencia enum('semanal','quincenal','mensual') DEFAULT 'semanal',
            importe decimal(10,2) DEFAULT 0,
            estado enum('activa','pausada','cancelada') DEFAULT 'activa',
            fecha_inicio date NOT NULL,
            fecha_proximo_cargo date DEFAULT NULL,
            fecha_pausa date DEFAULT NULL,
            fecha_cancelacion date DEFAULT NULL,
            notas text DEFAULT NULL,
            metodo_pago varchar(50) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY consumidor_id (consumidor_id),
            KEY tipo_cesta_id (tipo_cesta_id),
            KEY estado (estado),
            KEY fecha_proximo_cargo (fecha_proximo_cargo)
        ) $charset_collate;";

        // Historial de suscripciones
        $tables[] = "CREATE TABLE {$prefix}gc_suscripciones_historial (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            suscripcion_id bigint(20) UNSIGNED NOT NULL,
            ciclo_id bigint(20) UNSIGNED DEFAULT NULL,
            importe decimal(10,2) NOT NULL,
            estado enum('pendiente','procesado','fallido') DEFAULT 'pendiente',
            fecha_cargo datetime DEFAULT CURRENT_TIMESTAMP,
            notas text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY suscripcion_id (suscripcion_id),
            KEY ciclo_id (ciclo_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Tipos de cestas
        $tables[] = "CREATE TABLE {$prefix}gc_cestas_tipo (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            precio_base decimal(10,2) DEFAULT 0,
            productos_incluidos longtext DEFAULT NULL,
            imagen_id bigint(20) UNSIGNED DEFAULT NULL,
            orden int(11) DEFAULT 0,
            activa tinyint(1) DEFAULT 1,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY activa (activa),
            KEY orden (orden)
        ) $charset_collate;";

        // Lista de compra
        $tables[] = "CREATE TABLE {$prefix}gc_lista_compra (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            producto_id bigint(20) UNSIGNED NOT NULL,
            cantidad decimal(10,2) DEFAULT 1.00,
            fecha_agregado datetime DEFAULT CURRENT_TIMESTAMP,
            notas varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY producto_id (producto_id),
            KEY fecha_agregado (fecha_agregado)
        ) $charset_collate;";

        // Pagos
        $tables[] = "CREATE TABLE {$prefix}gc_pagos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            entrega_id bigint(20) UNSIGNED DEFAULT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            ciclo_id bigint(20) UNSIGNED DEFAULT NULL,
            pasarela varchar(50) DEFAULT NULL,
            transaction_id varchar(255) DEFAULT NULL,
            importe decimal(10,2) NOT NULL,
            moneda varchar(3) DEFAULT 'EUR',
            estado enum('pendiente','procesando','completado','fallido','reembolsado','cancelado') DEFAULT 'pendiente',
            datos_pasarela longtext DEFAULT NULL,
            ip_cliente varchar(45) DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY entrega_id (entrega_id),
            KEY usuario_id (usuario_id),
            KEY ciclo_id (ciclo_id),
            KEY pasarela (pasarela),
            KEY estado (estado),
            KEY fecha_creacion (fecha_creacion)
        ) $charset_collate;";

        // Productos
        $tables[] = "CREATE TABLE {$prefix}gc_productos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            productor_id bigint(20) UNSIGNED DEFAULT NULL,
            nombre varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            categoria varchar(100) DEFAULT NULL,
            precio decimal(10,2) NOT NULL,
            unidad varchar(50) DEFAULT 'unidad',
            stock_disponible decimal(10,2) DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            es_ecologico tinyint(1) DEFAULT 0,
            certificaciones varchar(255) DEFAULT NULL,
            origen_km int(11) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY productor_id (productor_id),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY slug (slug)
        ) $charset_collate;";

        // Grupos de consumo
        $tables[] = "CREATE TABLE {$prefix}gc_grupos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            ubicacion varchar(255) DEFAULT NULL,
            coordinador_id bigint(20) UNSIGNED DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            configuracion longtext DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY coordinador_id (coordinador_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Notificaciones
        $tables[] = "CREATE TABLE {$prefix}gc_notificaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo varchar(50) NOT NULL,
            titulo varchar(255) NOT NULL,
            mensaje text DEFAULT NULL,
            relacionado_tipo varchar(50) DEFAULT NULL,
            relacionado_id bigint(20) UNSIGNED DEFAULT NULL,
            leida tinyint(1) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_lectura datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY leida (leida),
            KEY tipo (tipo),
            KEY fecha_creacion (fecha_creacion)
        ) $charset_collate;";

        // Solicitudes de membresía
        $tables[] = "CREATE TABLE {$prefix}gc_solicitudes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            grupo_id bigint(20) UNSIGNED NOT NULL,
            mensaje text DEFAULT NULL,
            estado enum('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
            procesado_por bigint(20) UNSIGNED DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_procesado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY grupo_id (grupo_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Excedentes
        $tables[] = "CREATE TABLE {$prefix}gc_excedentes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ciclo_id bigint(20) UNSIGNED NOT NULL,
            producto_id bigint(20) UNSIGNED NOT NULL,
            cantidad_sobrante decimal(10,2) NOT NULL,
            cantidad_reclamada decimal(10,2) DEFAULT 0,
            cantidad_donada decimal(10,2) DEFAULT 0,
            estado enum('disponible','parcial','agotado','donado','descartado') DEFAULT 'disponible',
            destino_donacion varchar(255) DEFAULT NULL,
            precio_solidario decimal(10,2) DEFAULT NULL,
            motivo_excedente varchar(100) DEFAULT NULL,
            notas text DEFAULT NULL,
            fecha_registro datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_cierre datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY ciclo_id (ciclo_id),
            KEY producto_id (producto_id),
            KEY estado (estado),
            KEY fecha_registro (fecha_registro)
        ) $charset_collate;";

        // Reclamaciones de excedentes
        $tables[] = "CREATE TABLE {$prefix}gc_excedentes_reclamaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            excedente_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            cantidad decimal(10,2) NOT NULL,
            precio_pagado decimal(10,2) DEFAULT 0,
            estado enum('pendiente','confirmada','recogida','cancelada') DEFAULT 'pendiente',
            fecha_reclamacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_recogida datetime DEFAULT NULL,
            notas varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY excedente_id (excedente_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Huella ecológica por ciclo
        $tables[] = "CREATE TABLE {$prefix}gc_huella_ciclo (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ciclo_id bigint(20) UNSIGNED NOT NULL,
            km_evitados decimal(10,2) DEFAULT 0,
            co2_evitado_kg decimal(10,2) DEFAULT 0,
            plastico_evitado_kg decimal(10,4) DEFAULT 0,
            agua_ahorrada_litros decimal(12,2) DEFAULT 0,
            productores_locales int(11) DEFAULT 0,
            productos_eco_porcentaje decimal(5,2) DEFAULT 0,
            km_medio_producto decimal(8,2) DEFAULT 0,
            num_participantes int(11) DEFAULT 0,
            total_kg_productos decimal(10,2) DEFAULT 0,
            puntuacion_sostenibilidad int(3) DEFAULT 0,
            datos_detalle longtext DEFAULT NULL,
            fecha_calculo datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY ciclo_id (ciclo_id),
            KEY puntuacion_sostenibilidad (puntuacion_sostenibilidad)
        ) $charset_collate;";

        // Desglose de precios
        $tables[] = "CREATE TABLE {$prefix}gc_precio_desglose (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            producto_id bigint(20) UNSIGNED NOT NULL,
            ciclo_id bigint(20) UNSIGNED DEFAULT NULL,
            precio_productor decimal(10,2) NOT NULL,
            coste_transporte decimal(10,2) DEFAULT 0,
            coste_gestion decimal(10,2) DEFAULT 0,
            coste_mermas decimal(10,2) DEFAULT 0,
            aportacion_fondo_social decimal(10,2) DEFAULT 0,
            iva decimal(10,2) DEFAULT 0,
            precio_final decimal(10,2) NOT NULL,
            margen_productor_porcentaje decimal(5,2) DEFAULT 0,
            origen_km int(11) DEFAULT NULL,
            certificaciones varchar(255) DEFAULT NULL,
            visible_publico tinyint(1) DEFAULT 1,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY producto_id (producto_id),
            KEY ciclo_id (ciclo_id),
            KEY visible_publico (visible_publico)
        ) $charset_collate;";

        // Sistema de trueque
        $tables[] = "CREATE TABLE {$prefix}gc_trueque (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_ofrece_id bigint(20) UNSIGNED NOT NULL,
            usuario_recibe_id bigint(20) UNSIGNED DEFAULT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            productos_ofrecidos longtext DEFAULT NULL,
            productos_deseados longtext DEFAULT NULL,
            valor_estimado decimal(10,2) DEFAULT NULL,
            tipo enum('trueque','regalo','prestamo') DEFAULT 'trueque',
            estado enum('abierto','en_negociacion','acordado','completado','cancelado','expirado') DEFAULT 'abierto',
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion datetime DEFAULT NULL,
            fecha_acuerdo datetime DEFAULT NULL,
            fecha_completado datetime DEFAULT NULL,
            ubicacion_intercambio varchar(255) DEFAULT NULL,
            notas_intercambio text DEFAULT NULL,
            valoracion_ofrece tinyint(1) DEFAULT NULL,
            valoracion_recibe tinyint(1) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_ofrece_id (usuario_ofrece_id),
            KEY usuario_recibe_id (usuario_recibe_id),
            KEY estado (estado),
            KEY tipo (tipo),
            KEY fecha_publicacion (fecha_publicacion),
            KEY fecha_expiracion (fecha_expiracion)
        ) $charset_collate;";

        // Mensajes de trueque
        $tables[] = "CREATE TABLE {$prefix}gc_trueque_mensajes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            trueque_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            mensaje text NOT NULL,
            propuesta_modificada longtext DEFAULT NULL,
            fecha_mensaje datetime DEFAULT CURRENT_TIMESTAMP,
            leido tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY trueque_id (trueque_id),
            KEY usuario_id (usuario_id),
            KEY fecha_mensaje (fecha_mensaje),
            KEY leido (leido)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA ESPACIOS COMUNES
        // =====================================================

        // Espacios comunes
        $tables[] = "CREATE TABLE {$prefix}espacios_comunes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo varchar(50) DEFAULT 'sala',
            categoria varchar(100) DEFAULT NULL,
            imagen_principal varchar(500) DEFAULT NULL,
            galeria_imagenes text DEFAULT NULL,
            direccion varchar(500) DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            planta varchar(50) DEFAULT NULL,
            capacidad_maxima int(11) DEFAULT 0,
            superficie_m2 decimal(10,2) DEFAULT NULL,
            equipamiento text DEFAULT NULL,
            normas_uso text DEFAULT NULL,
            horario_apertura time DEFAULT '08:00:00',
            horario_cierre time DEFAULT '22:00:00',
            dias_disponibles varchar(50) DEFAULT '1,2,3,4,5',
            tiempo_minimo_reserva int(11) DEFAULT 30,
            tiempo_maximo_reserva int(11) DEFAULT 240,
            antelacion_minima_horas int(11) DEFAULT 24,
            antelacion_maxima_dias int(11) DEFAULT 30,
            precio_hora decimal(10,2) DEFAULT 0,
            precio_socios decimal(10,2) DEFAULT NULL,
            requiere_deposito tinyint(1) DEFAULT 0,
            deposito_cantidad decimal(10,2) DEFAULT NULL,
            requiere_aprobacion tinyint(1) DEFAULT 0,
            responsable_id bigint(20) UNSIGNED DEFAULT NULL,
            estado enum('activo','inactivo','mantenimiento','reservado') DEFAULT 'activo',
            accesibilidad tinyint(1) DEFAULT 0,
            wifi tinyint(1) DEFAULT 0,
            parking tinyint(1) DEFAULT 0,
            visualizaciones int(11) DEFAULT 0,
            reservas_count int(11) DEFAULT 0,
            metadata longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY tipo (tipo),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY capacidad_maxima (capacidad_maxima),
            KEY precio_hora (precio_hora)
        ) $charset_collate;";

        // Reservas de espacios
        $tables[] = "CREATE TABLE {$prefix}espacios_reservas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            espacio_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) DEFAULT NULL,
            descripcion text DEFAULT NULL,
            fecha date NOT NULL,
            hora_inicio time NOT NULL,
            hora_fin time NOT NULL,
            num_asistentes int(11) DEFAULT NULL,
            estado enum('pendiente','aprobada','rechazada','cancelada','completada') DEFAULT 'pendiente',
            motivo_rechazo text DEFAULT NULL,
            precio_total decimal(10,2) DEFAULT 0,
            deposito_pagado tinyint(1) DEFAULT 0,
            pago_completado tinyint(1) DEFAULT 0,
            referencia_pago varchar(100) DEFAULT NULL,
            codigo_acceso varchar(50) DEFAULT NULL,
            check_in_at datetime DEFAULT NULL,
            check_out_at datetime DEFAULT NULL,
            notas_admin text DEFAULT NULL,
            aprobado_por bigint(20) UNSIGNED DEFAULT NULL,
            aprobado_at datetime DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY espacio_id (espacio_id),
            KEY usuario_id (usuario_id),
            KEY fecha (fecha),
            KEY estado (estado),
            KEY espacio_fecha (espacio_id, fecha),
            KEY codigo_acceso (codigo_acceso)
        ) $charset_collate;";

        // Bloqueos de espacios
        $tables[] = "CREATE TABLE {$prefix}espacios_bloqueos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            espacio_id bigint(20) UNSIGNED NOT NULL,
            motivo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime NOT NULL,
            tipo enum('mantenimiento','evento_privado','festivo','otro') DEFAULT 'mantenimiento',
            recurrente tinyint(1) DEFAULT 0,
            creado_por bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY espacio_id (espacio_id),
            KEY fecha_inicio (fecha_inicio),
            KEY fecha_fin (fecha_fin),
            KEY tipo (tipo)
        ) $charset_collate;";

        // Equipamiento de espacios
        $tables[] = "CREATE TABLE {$prefix}espacios_equipamiento (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            espacio_id bigint(20) UNSIGNED NOT NULL,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            cantidad int(11) DEFAULT 1,
            estado varchar(50) DEFAULT 'disponible',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY espacio_id (espacio_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Incidencias de espacios
        $tables[] = "CREATE TABLE {$prefix}espacios_incidencias (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            espacio_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            reserva_id bigint(20) UNSIGNED DEFAULT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            prioridad enum('baja','media','alta','urgente') DEFAULT 'media',
            estado enum('abierta','en_progreso','resuelta','cerrada') DEFAULT 'abierta',
            fecha_reporte datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_resolucion datetime DEFAULT NULL,
            resuelto_por bigint(20) UNSIGNED DEFAULT NULL,
            notas_resolucion text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY espacio_id (espacio_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY prioridad (prioridad)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA COMUNIDADES
        // =====================================================

        // Comunidades
        $tables[] = "CREATE TABLE {$prefix}comunidades (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(200) NOT NULL,
            slug varchar(200) NOT NULL,
            descripcion text DEFAULT NULL,
            imagen varchar(255) DEFAULT NULL,
            tipo enum('abierta','cerrada','secreta') DEFAULT 'abierta',
            categoria varchar(100) DEFAULT 'otros',
            ubicacion varchar(200) DEFAULT NULL,
            reglas text DEFAULT NULL,
            miembros_count int(11) DEFAULT 0,
            creador_id bigint(20) UNSIGNED NOT NULL,
            estado enum('activa','pausada','archivada') DEFAULT 'activa',
            configuracion text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY creador_id (creador_id),
            KEY tipo (tipo),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY slug (slug)
        ) $charset_collate;";

        // Miembros de comunidades
        $tables[] = "CREATE TABLE {$prefix}comunidades_miembros (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            rol enum('admin','moderador','miembro') DEFAULT 'miembro',
            estado enum('activo','pendiente','suspendido','baneado') DEFAULT 'activo',
            joined_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY comunidad_usuario (comunidad_id, user_id),
            KEY comunidad_id (comunidad_id),
            KEY user_id (user_id),
            KEY rol (rol),
            KEY estado (estado)
        ) $charset_collate;";

        // Actividad/Feed de comunidades
        $tables[] = "CREATE TABLE {$prefix}comunidades_actividad (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            tipo enum('publicacion','evento','anuncio','encuesta') DEFAULT 'publicacion',
            titulo varchar(255) DEFAULT NULL,
            contenido longtext DEFAULT NULL,
            adjuntos text DEFAULT NULL,
            imagen varchar(500) DEFAULT NULL,
            referencia_original bigint(20) UNSIGNED DEFAULT NULL,
            reacciones_count int(11) DEFAULT 0,
            comentarios_count int(11) DEFAULT 0,
            es_fijado tinyint(1) DEFAULT 0,
            metadata longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY comunidad_id (comunidad_id),
            KEY user_id (user_id),
            KEY tipo (tipo),
            KEY es_fijado (es_fijado),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Reacciones a actividades
        $tables[] = "CREATE TABLE {$prefix}comunidades_actividad_reacciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            actividad_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            tipo enum('like') DEFAULT 'like',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY actividad_usuario (actividad_id, user_id),
            KEY actividad_id (actividad_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Comentarios en comunidades
        $tables[] = "CREATE TABLE {$prefix}comunidades_comentarios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            actividad_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            padre_id bigint(20) UNSIGNED DEFAULT NULL,
            contenido text NOT NULL,
            estado enum('activo','oculto','eliminado') DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY actividad_id (actividad_id),
            KEY user_id (user_id),
            KEY padre_id (padre_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Publicaciones de comunidades
        $tables[] = "CREATE TABLE {$prefix}comunidades_publicaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) UNSIGNED NOT NULL,
            autor_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) DEFAULT NULL,
            contenido longtext NOT NULL,
            tipo varchar(50) DEFAULT 'post',
            adjuntos longtext DEFAULT NULL,
            likes_count int(11) DEFAULT 0,
            comentarios_count int(11) DEFAULT 0,
            es_fijado tinyint(1) DEFAULT 0,
            estado varchar(20) DEFAULT 'publicado',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY comunidad_id (comunidad_id),
            KEY autor_id (autor_id),
            KEY estado (estado),
            KEY es_fijado (es_fijado)
        ) $charset_collate;";

        // Anuncios de comunidades
        $tables[] = "CREATE TABLE {$prefix}comunidades_anuncios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(200) NOT NULL,
            contenido text DEFAULT NULL,
            categoria varchar(50) DEFAULT 'general',
            destacado tinyint(1) DEFAULT 0,
            compartir_red tinyint(1) DEFAULT 0,
            fecha_expiracion date DEFAULT NULL,
            estado enum('borrador','publicado','archivado') DEFAULT 'publicado',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY comunidad_id (comunidad_id),
            KEY user_id (user_id),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY destacado (destacado),
            KEY fecha_expiracion (fecha_expiracion)
        ) $charset_collate;";

        // =====================================================
        // TABLAS ADICIONALES PARA TALLERES
        // =====================================================

        // Inscripciones a talleres
        $tables[] = "CREATE TABLE {$prefix}talleres_inscripciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            taller_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            estado enum('pendiente','confirmada','cancelada','asistio','no_asistio') DEFAULT 'pendiente',
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            notas text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY taller_usuario (taller_id, usuario_id),
            KEY taller_id (taller_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Sesiones de talleres
        $tables[] = "CREATE TABLE {$prefix}talleres_sesiones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            taller_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) DEFAULT NULL,
            fecha datetime NOT NULL,
            duracion_minutos int(11) DEFAULT 60,
            ubicacion varchar(255) DEFAULT NULL,
            notas text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY taller_id (taller_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Asistencias a talleres
        $tables[] = "CREATE TABLE {$prefix}talleres_asistencias (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            sesion_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            asistio tinyint(1) DEFAULT 0,
            hora_llegada time DEFAULT NULL,
            notas text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY sesion_usuario (sesion_id, usuario_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Materiales de talleres
        $tables[] = "CREATE TABLE {$prefix}talleres_materiales (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            taller_id bigint(20) UNSIGNED NOT NULL,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            cantidad int(11) DEFAULT 1,
            incluido tinyint(1) DEFAULT 1,
            coste decimal(10,2) DEFAULT 0,
            PRIMARY KEY (id),
            KEY taller_id (taller_id)
        ) $charset_collate;";

        // Valoraciones de talleres
        $tables[] = "CREATE TABLE {$prefix}talleres_valoraciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            taller_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            valoracion int(11) NOT NULL,
            comentario text DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY taller_usuario (taller_id, usuario_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // =====================================================
        // TABLAS ADICIONALES PARA CURSOS
        // =====================================================

        // Categorías de cursos
        $tables[] = "CREATE TABLE {$prefix}cursos_categorias (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            icono varchar(50) DEFAULT NULL,
            orden int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        // Módulos de cursos
        $tables[] = "CREATE TABLE {$prefix}cursos_modulos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            curso_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            orden int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY curso_id (curso_id),
            KEY orden (orden)
        ) $charset_collate;";

        // Lecciones de cursos
        $tables[] = "CREATE TABLE {$prefix}cursos_lecciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            modulo_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            contenido longtext DEFAULT NULL,
            tipo enum('video','texto','quiz','tarea') DEFAULT 'texto',
            duracion_minutos int(11) DEFAULT 0,
            recursos longtext DEFAULT NULL,
            orden int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY modulo_id (modulo_id),
            KEY orden (orden)
        ) $charset_collate;";

        // Matrículas
        $tables[] = "CREATE TABLE {$prefix}cursos_matriculas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            curso_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            estado enum('activa','completada','cancelada','pausada') DEFAULT 'activa',
            progreso int(11) DEFAULT 0,
            fecha_inicio datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY curso_usuario (curso_id, usuario_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Progreso de lecciones
        $tables[] = "CREATE TABLE {$prefix}cursos_progreso (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            leccion_id bigint(20) UNSIGNED NOT NULL,
            completada tinyint(1) DEFAULT 0,
            puntuacion int(11) DEFAULT NULL,
            fecha_inicio datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_leccion (usuario_id, leccion_id),
            KEY leccion_id (leccion_id)
        ) $charset_collate;";

        // Certificados
        $tables[] = "CREATE TABLE {$prefix}cursos_certificados (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            curso_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            codigo varchar(50) NOT NULL,
            fecha_emision datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY codigo (codigo),
            KEY curso_id (curso_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Valoraciones de cursos
        $tables[] = "CREATE TABLE {$prefix}cursos_valoraciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            curso_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            valoracion int(11) NOT NULL,
            comentario text DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY curso_usuario (curso_id, usuario_id)
        ) $charset_collate;";

        // =====================================================
        // TABLAS ADICIONALES PARA PARKINGS
        // =====================================================

        // Plazas de parking
        $tables[] = "CREATE TABLE {$prefix}parkings_plazas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            parking_id bigint(20) UNSIGNED NOT NULL,
            numero varchar(20) NOT NULL,
            tipo enum('normal','discapacitado','moto','electrico') DEFAULT 'normal',
            planta varchar(20) DEFAULT NULL,
            estado enum('libre','ocupada','reservada','mantenimiento') DEFAULT 'libre',
            PRIMARY KEY (id),
            UNIQUE KEY parking_numero (parking_id, numero),
            KEY parking_id (parking_id),
            KEY estado (estado),
            KEY tipo (tipo)
        ) $charset_collate;";

        // Reservas de parking
        $tables[] = "CREATE TABLE {$prefix}parkings_reservas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            plaza_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime NOT NULL,
            matricula varchar(20) DEFAULT NULL,
            estado enum('pendiente','activa','completada','cancelada') DEFAULT 'pendiente',
            precio decimal(10,2) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY plaza_id (plaza_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio)
        ) $charset_collate;";

        // Asignaciones permanentes
        $tables[] = "CREATE TABLE {$prefix}parkings_asignaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            plaza_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha_inicio date NOT NULL,
            fecha_fin date DEFAULT NULL,
            estado enum('activa','finalizada','cancelada') DEFAULT 'activa',
            PRIMARY KEY (id),
            KEY plaza_id (plaza_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Lista de espera
        $tables[] = "CREATE TABLE {$prefix}parkings_lista_espera (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            parking_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo_plaza varchar(20) DEFAULT 'normal',
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            posicion int(11) DEFAULT NULL,
            estado enum('esperando','notificado','asignado','cancelado') DEFAULT 'esperando',
            PRIMARY KEY (id),
            KEY parking_id (parking_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Propietarios
        $tables[] = "CREATE TABLE {$prefix}parkings_propietarios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            plaza_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha_adquisicion date NOT NULL,
            PRIMARY KEY (id),
            KEY plaza_id (plaza_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // =====================================================
        // TABLAS ADICIONALES PARA PODCAST
        // =====================================================

        // Series de podcast
        $tables[] = "CREATE TABLE {$prefix}podcast_series (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            autor_id bigint(20) UNSIGNED DEFAULT NULL,
            categoria varchar(100) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY autor_id (autor_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Podcasts (alias de series)
        $tables[] = "CREATE TABLE {$prefix}podcasts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            rss_url varchar(500) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY estado (estado)
        ) $charset_collate;";

        // Suscripciones a podcast
        $tables[] = "CREATE TABLE {$prefix}podcast_suscripciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            serie_id bigint(20) UNSIGNED NOT NULL,
            notificaciones tinyint(1) DEFAULT 1,
            fecha_suscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_serie (usuario_id, serie_id),
            KEY serie_id (serie_id)
        ) $charset_collate;";

        // Reproducciones
        $tables[] = "CREATE TABLE {$prefix}podcast_reproducciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            episodio_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED DEFAULT NULL,
            ip varchar(45) DEFAULT NULL,
            posicion_segundos int(11) DEFAULT 0,
            completado tinyint(1) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY episodio_id (episodio_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Likes de podcast
        $tables[] = "CREATE TABLE {$prefix}podcast_likes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            episodio_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY episodio_usuario (episodio_id, usuario_id)
        ) $charset_collate;";

        // Transcripciones
        $tables[] = "CREATE TABLE {$prefix}podcast_transcripciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            episodio_id bigint(20) UNSIGNED NOT NULL,
            contenido longtext NOT NULL,
            idioma varchar(10) DEFAULT 'es',
            generada_por varchar(50) DEFAULT 'manual',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY episodio_id (episodio_id)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA BICICLETAS COMPARTIDAS
        // =====================================================

        // Estaciones de bicicletas
        $tables[] = "CREATE TABLE {$prefix}bicicletas_estaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            direccion varchar(500) DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            capacidad int(11) DEFAULT 20,
            bicis_disponibles int(11) DEFAULT 0,
            huecos_libres int(11) DEFAULT 0,
            horario varchar(100) DEFAULT '24h',
            estado enum('operativa','mantenimiento','cerrada') DEFAULT 'operativa',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY estado (estado)
        ) $charset_collate;";

        // Préstamos de bicicletas
        $tables[] = "CREATE TABLE {$prefix}bicicletas_prestamos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            bicicleta_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            estacion_origen_id bigint(20) UNSIGNED NOT NULL,
            estacion_destino_id bigint(20) UNSIGNED DEFAULT NULL,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime DEFAULT NULL,
            duracion_minutos int(11) DEFAULT NULL,
            km_recorridos decimal(10,2) DEFAULT NULL,
            estado enum('activo','completado','incidencia') DEFAULT 'activo',
            PRIMARY KEY (id),
            KEY bicicleta_id (bicicleta_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio)
        ) $charset_collate;";

        // Incidencias de bicicletas
        $tables[] = "CREATE TABLE {$prefix}bicicletas_incidencias (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            bicicleta_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo varchar(50) NOT NULL,
            descripcion text DEFAULT NULL,
            estado enum('reportada','revision','reparando','resuelta') DEFAULT 'reportada',
            fecha_reporte datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_resolucion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY bicicleta_id (bicicleta_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Mantenimiento
        $tables[] = "CREATE TABLE {$prefix}bicicletas_mantenimiento (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            bicicleta_id bigint(20) UNSIGNED NOT NULL,
            tipo varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            tecnico varchar(100) DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            coste decimal(10,2) DEFAULT 0,
            PRIMARY KEY (id),
            KEY bicicleta_id (bicicleta_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA HUERTOS URBANOS (ADICIONALES)
        // =====================================================

        // Huertos
        $tables[] = "CREATE TABLE {$prefix}huertos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            direccion varchar(500) DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            superficie_m2 decimal(10,2) DEFAULT NULL,
            num_parcelas int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY estado (estado)
        ) $charset_collate;";

        // Huertos urbanos (alias)
        $tables[] = "CREATE TABLE {$prefix}huertos_urbanos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            ubicacion varchar(500) DEFAULT NULL,
            descripcion text DEFAULT NULL,
            num_parcelas int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado)
        ) $charset_collate;";

        // Cultivos
        $tables[] = "CREATE TABLE {$prefix}huertos_cultivos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            parcela_id bigint(20) UNSIGNED NOT NULL,
            nombre varchar(100) NOT NULL,
            variedad varchar(100) DEFAULT NULL,
            fecha_siembra date DEFAULT NULL,
            fecha_cosecha_estimada date DEFAULT NULL,
            fecha_cosecha_real date DEFAULT NULL,
            cantidad_cosechada decimal(10,2) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            notas text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY parcela_id (parcela_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Tareas de huerto
        $tables[] = "CREATE TABLE {$prefix}huertos_tareas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            parcela_id bigint(20) UNSIGNED DEFAULT NULL,
            huerto_id bigint(20) UNSIGNED DEFAULT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo varchar(50) DEFAULT 'general',
            prioridad enum('baja','media','alta') DEFAULT 'media',
            fecha_limite date DEFAULT NULL,
            estado enum('pendiente','en_progreso','completada') DEFAULT 'pendiente',
            asignado_a bigint(20) UNSIGNED DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parcela_id (parcela_id),
            KEY huerto_id (huerto_id),
            KEY estado (estado),
            KEY asignado_a (asignado_a)
        ) $charset_collate;";

        // Participantes en tareas
        $tables[] = "CREATE TABLE {$prefix}huertos_participantes_tareas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tarea_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha_asignacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY tarea_usuario (tarea_id, usuario_id)
        ) $charset_collate;";

        // Solicitudes de parcela
        $tables[] = "CREATE TABLE {$prefix}huertos_solicitudes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            huerto_id bigint(20) UNSIGNED DEFAULT NULL,
            mensaje text DEFAULT NULL,
            estado enum('pendiente','aprobada','rechazada','lista_espera') DEFAULT 'pendiente',
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_procesado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY huerto_id (huerto_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Turnos de riego
        $tables[] = "CREATE TABLE {$prefix}huertos_turnos_riego (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            parcela_id bigint(20) UNSIGNED NOT NULL,
            dia_semana tinyint(1) NOT NULL,
            hora_inicio time NOT NULL,
            hora_fin time NOT NULL,
            PRIMARY KEY (id),
            KEY parcela_id (parcela_id)
        ) $charset_collate;";

        // Herramientas compartidas
        $tables[] = "CREATE TABLE {$prefix}huertos_herramientas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            huerto_id bigint(20) UNSIGNED NOT NULL,
            nombre varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            cantidad int(11) DEFAULT 1,
            estado varchar(20) DEFAULT 'disponible',
            PRIMARY KEY (id),
            KEY huerto_id (huerto_id)
        ) $charset_collate;";

        // Intercambios
        $tables[] = "CREATE TABLE {$prefix}huertos_intercambios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_ofrece_id bigint(20) UNSIGNED NOT NULL,
            usuario_recibe_id bigint(20) UNSIGNED DEFAULT NULL,
            tipo enum('semillas','plantas','cosecha','herramientas') NOT NULL,
            descripcion text NOT NULL,
            estado enum('disponible','reservado','completado') DEFAULT 'disponible',
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_ofrece_id (usuario_ofrece_id),
            KEY tipo (tipo),
            KEY estado (estado)
        ) $charset_collate;";

        // Actividades de huerto
        $tables[] = "CREATE TABLE {$prefix}huertos_actividades (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            huerto_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            fecha datetime NOT NULL,
            tipo varchar(50) DEFAULT 'taller',
            plazas int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY huerto_id (huerto_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Pagos de parcelas
        $tables[] = "CREATE TABLE {$prefix}huertos_pagos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            asignacion_id bigint(20) UNSIGNED NOT NULL,
            importe decimal(10,2) NOT NULL,
            concepto varchar(255) DEFAULT NULL,
            fecha_pago datetime DEFAULT CURRENT_TIMESTAMP,
            estado varchar(20) DEFAULT 'completado',
            PRIMARY KEY (id),
            KEY asignacion_id (asignacion_id)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA RECICLAJE (ADICIONALES)
        // =====================================================

        // Contenedores de reciclaje
        $tables[] = "CREATE TABLE {$prefix}reciclaje_contenedores (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            tipo varchar(50) NOT NULL,
            direccion varchar(500) DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            capacidad_litros int(11) DEFAULT NULL,
            nivel_llenado int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'activo',
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY estado (estado)
        ) $charset_collate;";

        // Depósitos de reciclaje
        $tables[] = "CREATE TABLE {$prefix}reciclaje_depositos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            contenedor_id bigint(20) UNSIGNED DEFAULT NULL,
            tipo_residuo varchar(50) NOT NULL,
            cantidad_kg decimal(10,3) DEFAULT NULL,
            puntos_ganados int(11) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY contenedor_id (contenedor_id),
            KEY tipo_residuo (tipo_residuo)
        ) $charset_collate;";

        // Puntos por usuario
        $tables[] = "CREATE TABLE {$prefix}reciclaje_puntos_usuario (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            puntos_totales int(11) DEFAULT 0,
            puntos_canjeados int(11) DEFAULT 0,
            nivel varchar(20) DEFAULT 'principiante',
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Recompensas
        $tables[] = "CREATE TABLE {$prefix}reciclaje_recompensas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            puntos_requeridos int(11) NOT NULL,
            stock int(11) DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY puntos_requeridos (puntos_requeridos)
        ) $charset_collate;";

        // Canjes
        $tables[] = "CREATE TABLE {$prefix}reciclaje_canjes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            recompensa_id bigint(20) UNSIGNED NOT NULL,
            puntos_usados int(11) NOT NULL,
            estado enum('pendiente','entregado','cancelado') DEFAULT 'pendiente',
            fecha_canje datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_entrega datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY recompensa_id (recompensa_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Registros de reciclaje
        $tables[] = "CREATE TABLE {$prefix}reciclaje_registros (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo_material varchar(50) NOT NULL,
            cantidad decimal(10,2) NOT NULL,
            unidad varchar(20) DEFAULT 'kg',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            verificado tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY tipo_material (tipo_material),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Recogidas programadas
        $tables[] = "CREATE TABLE {$prefix}reciclaje_recogidas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            direccion varchar(500) NOT NULL,
            tipo_residuos varchar(255) NOT NULL,
            fecha_solicitada date NOT NULL,
            horario_preferido varchar(50) DEFAULT NULL,
            estado enum('pendiente','programada','completada','cancelada') DEFAULT 'pendiente',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY fecha_solicitada (fecha_solicitada)
        ) $charset_collate;";

        // Reportes de contenedores
        $tables[] = "CREATE TABLE {$prefix}reciclaje_reportes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            contenedor_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo_reporte varchar(50) NOT NULL,
            descripcion text DEFAULT NULL,
            estado enum('pendiente','revisado','resuelto') DEFAULT 'pendiente',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY contenedor_id (contenedor_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Premios de reciclaje
        $tables[] = "CREATE TABLE {$prefix}reciclaje_premios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            puntos_necesarios int(11) NOT NULL,
            cantidad_disponible int(11) DEFAULT NULL,
            imagen varchar(500) DEFAULT NULL,
            activo tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY activo (activo)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA COMPOSTAJE (ADICIONALES)
        // =====================================================

        // Composteras
        $tables[] = "CREATE TABLE {$prefix}composteras (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            ubicacion varchar(500) DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            capacidad_litros int(11) DEFAULT NULL,
            nivel_llenado int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'activa',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado)
        ) $charset_collate;";

        // Aportaciones de compost
        $tables[] = "CREATE TABLE {$prefix}compostaje_aportaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            compostera_id bigint(20) UNSIGNED NOT NULL,
            tipo_residuo varchar(50) NOT NULL,
            cantidad_kg decimal(10,3) NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY compostera_id (compostera_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Aportaciones (alias)
        $tables[] = "CREATE TABLE {$prefix}aportaciones_compost (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            compostera_id bigint(20) UNSIGNED DEFAULT NULL,
            tipo varchar(50) NOT NULL,
            cantidad decimal(10,3) NOT NULL,
            puntos int(11) DEFAULT 0,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Puntos de compostaje
        $tables[] = "CREATE TABLE {$prefix}puntos_compostaje (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            direccion varchar(500) DEFAULT NULL,
            horario varchar(255) DEFAULT NULL,
            tipo varchar(50) DEFAULT 'comunitario',
            estado varchar(20) DEFAULT 'activo',
            PRIMARY KEY (id),
            KEY estado (estado)
        ) $charset_collate;";

        // Turnos de compostaje
        $tables[] = "CREATE TABLE {$prefix}turnos_compostaje (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            compostera_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha date NOT NULL,
            turno varchar(20) NOT NULL,
            estado varchar(20) DEFAULT 'asignado',
            PRIMARY KEY (id),
            KEY compostera_id (compostera_id),
            KEY usuario_id (usuario_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Inscripciones a turnos
        $tables[] = "CREATE TABLE {$prefix}inscripciones_turno (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            turno_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            asistio tinyint(1) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY turno_usuario (turno_id, usuario_id)
        ) $charset_collate;";

        // Materiales compostables
        $tables[] = "CREATE TABLE {$prefix}materiales_compostables (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            categoria varchar(50) NOT NULL,
            compostable tinyint(1) DEFAULT 1,
            instrucciones text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY categoria (categoria)
        ) $charset_collate;";

        // Depósitos de compostaje
        $tables[] = "CREATE TABLE {$prefix}compostaje_depositos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            punto_id bigint(20) UNSIGNED DEFAULT NULL,
            peso_kg decimal(10,3) NOT NULL,
            tipo_residuo varchar(100) DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY punto_id (punto_id)
        ) $charset_collate;";

        // Mantenimiento de composteras
        $tables[] = "CREATE TABLE {$prefix}compostaje_mantenimiento (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            compostera_id bigint(20) UNSIGNED NOT NULL,
            tipo varchar(50) NOT NULL,
            descripcion text DEFAULT NULL,
            realizado_por bigint(20) UNSIGNED DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY compostera_id (compostera_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Recogidas de compost
        $tables[] = "CREATE TABLE {$prefix}compostaje_recogidas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            compostera_id bigint(20) UNSIGNED NOT NULL,
            cantidad_kg decimal(10,2) NOT NULL,
            calidad varchar(20) DEFAULT 'buena',
            destino varchar(255) DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY compostera_id (compostera_id)
        ) $charset_collate;";

        // Estadísticas de compost
        $tables[] = "CREATE TABLE {$prefix}estadisticas_compost (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            periodo varchar(20) NOT NULL,
            fecha_inicio date NOT NULL,
            fecha_fin date NOT NULL,
            total_kg decimal(12,2) DEFAULT 0,
            participantes int(11) DEFAULT 0,
            co2_evitado_kg decimal(10,2) DEFAULT 0,
            PRIMARY KEY (id),
            KEY periodo (periodo),
            KEY fecha_inicio (fecha_inicio)
        ) $charset_collate;";

        // Logros de compostaje
        $tables[] = "CREATE TABLE {$prefix}logros_compostaje (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            logro varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            fecha_obtenido datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA AYUDA VECINAL (ADICIONALES)
        // =====================================================

        // Solicitudes de ayuda
        $tables[] = "CREATE TABLE {$prefix}ayuda_solicitudes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(50) DEFAULT 'general',
            urgencia enum('baja','media','alta','urgente') DEFAULT 'media',
            estado enum('abierta','en_progreso','resuelta','cerrada') DEFAULT 'abierta',
            ubicacion varchar(500) DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_resolucion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY categoria (categoria),
            KEY urgencia (urgencia),
            KEY estado (estado)
        ) $charset_collate;";

        // Ofertas de ayuda
        $tables[] = "CREATE TABLE {$prefix}ayuda_ofertas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(50) DEFAULT 'general',
            disponibilidad varchar(255) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activa',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY categoria (categoria),
            KEY estado (estado)
        ) $charset_collate;";

        // Respuestas a solicitudes
        $tables[] = "CREATE TABLE {$prefix}ayuda_respuestas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            solicitud_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            mensaje text NOT NULL,
            estado enum('pendiente','aceptada','rechazada') DEFAULT 'pendiente',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY solicitud_id (solicitud_id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Valoraciones de ayuda
        $tables[] = "CREATE TABLE {$prefix}ayuda_valoraciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            solicitud_id bigint(20) UNSIGNED NOT NULL,
            valorador_id bigint(20) UNSIGNED NOT NULL,
            valorado_id bigint(20) UNSIGNED NOT NULL,
            puntuacion int(11) NOT NULL,
            comentario text DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY solicitud_valorador (solicitud_id, valorador_id),
            KEY valorado_id (valorado_id)
        ) $charset_collate;";

        // Voluntarios de ayuda vecinal
        $tables[] = "CREATE TABLE {$prefix}ayuda_vecinal_voluntarios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            habilidades text DEFAULT NULL,
            disponibilidad varchar(255) DEFAULT NULL,
            zona varchar(255) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            fecha_registro datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Asignaciones de ayuda
        $tables[] = "CREATE TABLE {$prefix}ayuda_vecinal_asignaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            solicitud_id bigint(20) UNSIGNED NOT NULL,
            voluntario_id bigint(20) UNSIGNED NOT NULL,
            estado enum('asignada','en_progreso','completada','cancelada') DEFAULT 'asignada',
            fecha_asignacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_completado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY solicitud_id (solicitud_id),
            KEY voluntario_id (voluntario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Solicitudes de ayuda vecinal (alias)
        $tables[] = "CREATE TABLE {$prefix}ayuda_vecinal_solicitudes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            tipo varchar(50) DEFAULT 'general',
            urgente tinyint(1) DEFAULT 0,
            estado varchar(20) DEFAULT 'pendiente',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Valoraciones ayuda vecinal (alias)
        $tables[] = "CREATE TABLE {$prefix}ayuda_vecinal_valoraciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            solicitud_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            puntuacion int(11) NOT NULL,
            comentario text DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY solicitud_id (solicitud_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA EMAIL MARKETING
        // =====================================================

        // Listas de suscriptores
        $tables[] = "CREATE TABLE {$prefix}em_listas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            estado varchar(20) DEFAULT 'activa',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY estado (estado)
        ) $charset_collate;";

        // Suscriptores
        $tables[] = "CREATE TABLE {$prefix}em_suscriptores (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            nombre varchar(255) DEFAULT NULL,
            usuario_id bigint(20) UNSIGNED DEFAULT NULL,
            estado enum('activo','pendiente','dado_baja','rebotado') DEFAULT 'pendiente',
            token_confirmacion varchar(64) DEFAULT NULL,
            fecha_suscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_confirmacion datetime DEFAULT NULL,
            fecha_baja datetime DEFAULT NULL,
            ip_suscripcion varchar(45) DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Relación suscriptor-lista
        $tables[] = "CREATE TABLE {$prefix}em_suscriptor_lista (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            suscriptor_id bigint(20) UNSIGNED NOT NULL,
            lista_id bigint(20) UNSIGNED NOT NULL,
            fecha_union datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY suscriptor_lista (suscriptor_id, lista_id),
            KEY lista_id (lista_id)
        ) $charset_collate;";

        // Campañas
        $tables[] = "CREATE TABLE {$prefix}em_campanias (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            asunto varchar(255) NOT NULL,
            contenido_html longtext NOT NULL,
            contenido_texto longtext DEFAULT NULL,
            lista_id bigint(20) UNSIGNED DEFAULT NULL,
            estado enum('borrador','programada','enviando','enviada','pausada') DEFAULT 'borrador',
            fecha_programada datetime DEFAULT NULL,
            fecha_envio datetime DEFAULT NULL,
            total_enviados int(11) DEFAULT 0,
            total_abiertos int(11) DEFAULT 0,
            total_clics int(11) DEFAULT 0,
            total_rebotes int(11) DEFAULT 0,
            total_bajas int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY lista_id (lista_id),
            KEY estado (estado),
            KEY fecha_programada (fecha_programada)
        ) $charset_collate;";

        // Cola de envío
        $tables[] = "CREATE TABLE {$prefix}em_cola (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            campania_id bigint(20) UNSIGNED NOT NULL,
            suscriptor_id bigint(20) UNSIGNED NOT NULL,
            estado enum('pendiente','enviado','fallido') DEFAULT 'pendiente',
            intentos int(11) DEFAULT 0,
            fecha_programada datetime DEFAULT NULL,
            fecha_envio datetime DEFAULT NULL,
            error_mensaje text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY campania_id (campania_id),
            KEY suscriptor_id (suscriptor_id),
            KEY estado (estado),
            KEY fecha_programada (fecha_programada)
        ) $charset_collate;";

        // Tracking de emails
        $tables[] = "CREATE TABLE {$prefix}em_tracking (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            campania_id bigint(20) UNSIGNED NOT NULL,
            suscriptor_id bigint(20) UNSIGNED NOT NULL,
            tipo enum('apertura','clic','rebote','baja') NOT NULL,
            url_clic varchar(500) DEFAULT NULL,
            ip varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campania_id (campania_id),
            KEY suscriptor_id (suscriptor_id),
            KEY tipo (tipo),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Plantillas
        $tables[] = "CREATE TABLE {$prefix}em_plantillas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            contenido_html longtext NOT NULL,
            categoria varchar(50) DEFAULT 'general',
            es_predeterminada tinyint(1) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY categoria (categoria)
        ) $charset_collate;";

        // Automatizaciones
        $tables[] = "CREATE TABLE {$prefix}em_automatizaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            trigger_evento varchar(100) NOT NULL,
            campania_id bigint(20) UNSIGNED DEFAULT NULL,
            retraso_minutos int(11) DEFAULT 0,
            condiciones longtext DEFAULT NULL,
            activa tinyint(1) DEFAULT 1,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY trigger_evento (trigger_evento),
            KEY activa (activa)
        ) $charset_collate;";

        // Auto-suscriptores
        $tables[] = "CREATE TABLE {$prefix}em_auto_suscriptores (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            lista_id bigint(20) UNSIGNED NOT NULL,
            evento varchar(100) NOT NULL,
            activo tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY lista_id (lista_id),
            KEY evento (evento)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA PRESUPUESTOS PARTICIPATIVOS
        // =====================================================

        // Procesos de presupuestos participativos
        $tables[] = "CREATE TABLE {$prefix}pp_procesos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            presupuesto_total decimal(12,2) NOT NULL,
            fase enum('propuestas','evaluacion','votacion','ejecucion','cerrado') DEFAULT 'propuestas',
            fecha_inicio_propuestas date DEFAULT NULL,
            fecha_fin_propuestas date DEFAULT NULL,
            fecha_inicio_votacion date DEFAULT NULL,
            fecha_fin_votacion date DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY fase (fase),
            KEY estado (estado)
        ) $charset_collate;";

        // Propuestas
        $tables[] = "CREATE TABLE {$prefix}pp_propuestas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            proceso_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            justificacion text DEFAULT NULL,
            presupuesto_estimado decimal(12,2) NOT NULL,
            categoria_id bigint(20) UNSIGNED DEFAULT NULL,
            ubicacion varchar(500) DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            imagenes longtext DEFAULT NULL,
            documentos longtext DEFAULT NULL,
            estado enum('borrador','enviada','evaluacion','aprobada','rechazada','votacion','ganadora','descartada') DEFAULT 'borrador',
            votos_count int(11) DEFAULT 0,
            apoyos_count int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_envio datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY proceso_id (proceso_id),
            KEY usuario_id (usuario_id),
            KEY categoria_id (categoria_id),
            KEY estado (estado),
            KEY votos_count (votos_count)
        ) $charset_collate;";

        // Proyectos (propuestas ganadoras)
        $tables[] = "CREATE TABLE {$prefix}pp_proyectos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            propuesta_id bigint(20) UNSIGNED NOT NULL,
            proceso_id bigint(20) UNSIGNED NOT NULL,
            presupuesto_asignado decimal(12,2) NOT NULL,
            estado enum('pendiente','planificacion','en_ejecucion','completado','cancelado') DEFAULT 'pendiente',
            porcentaje_avance int(11) DEFAULT 0,
            fecha_inicio date DEFAULT NULL,
            fecha_fin_estimada date DEFAULT NULL,
            fecha_fin_real date DEFAULT NULL,
            responsable_id bigint(20) UNSIGNED DEFAULT NULL,
            notas text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY propuesta_id (propuesta_id),
            KEY proceso_id (proceso_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Votos
        $tables[] = "CREATE TABLE {$prefix}pp_votos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            propuesta_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            proceso_id bigint(20) UNSIGNED NOT NULL,
            puntos int(11) DEFAULT 1,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY propuesta_usuario (propuesta_id, usuario_id),
            KEY proceso_id (proceso_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Apoyos (fase propuestas)
        $tables[] = "CREATE TABLE {$prefix}pp_apoyos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            propuesta_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY propuesta_usuario (propuesta_id, usuario_id)
        ) $charset_collate;";

        // Categorías
        $tables[] = "CREATE TABLE {$prefix}pp_categorias (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            icono varchar(50) DEFAULT NULL,
            color varchar(20) DEFAULT NULL,
            orden int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        // Comentarios
        $tables[] = "CREATE TABLE {$prefix}pp_comentarios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            propuesta_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            padre_id bigint(20) UNSIGNED DEFAULT NULL,
            contenido text NOT NULL,
            estado varchar(20) DEFAULT 'publicado',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY propuesta_id (propuesta_id),
            KEY usuario_id (usuario_id),
            KEY padre_id (padre_id)
        ) $charset_collate;";

        // Ediciones de propuestas
        $tables[] = "CREATE TABLE {$prefix}pp_ediciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            propuesta_id bigint(20) UNSIGNED NOT NULL,
            campo varchar(50) NOT NULL,
            valor_anterior longtext DEFAULT NULL,
            valor_nuevo longtext DEFAULT NULL,
            editado_por bigint(20) UNSIGNED NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY propuesta_id (propuesta_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Actualizaciones de proyectos
        $tables[] = "CREATE TABLE {$prefix}pp_actualizaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            proyecto_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            contenido text NOT NULL,
            imagenes longtext DEFAULT NULL,
            porcentaje_avance int(11) DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY proyecto_id (proyecto_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Ejecución de proyectos
        $tables[] = "CREATE TABLE {$prefix}pp_ejecucion (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            proyecto_id bigint(20) UNSIGNED NOT NULL,
            concepto varchar(255) NOT NULL,
            importe decimal(12,2) NOT NULL,
            tipo enum('gasto','ingreso') DEFAULT 'gasto',
            fecha date NOT NULL,
            documento_url varchar(500) DEFAULT NULL,
            notas text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY proyecto_id (proyecto_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Ciclos de presupuestos
        $tables[] = "CREATE TABLE {$prefix}pp_ciclos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            proceso_id bigint(20) UNSIGNED NOT NULL,
            nombre varchar(100) NOT NULL,
            fecha_inicio date NOT NULL,
            fecha_fin date NOT NULL,
            presupuesto decimal(12,2) DEFAULT 0,
            PRIMARY KEY (id),
            KEY proceso_id (proceso_id)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA OTROS MÓDULOS
        // =====================================================

        // Campañas
        $tables[] = "CREATE TABLE {$prefix}campanias (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text NOT NULL,
            objetivo text DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            meta_firmas int(11) DEFAULT NULL,
            firmas_count int(11) DEFAULT 0,
            estado enum('borrador','activa','completada','archivada') DEFAULT 'borrador',
            creador_id bigint(20) UNSIGNED NOT NULL,
            fecha_inicio date DEFAULT NULL,
            fecha_fin date DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY creador_id (creador_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Firmas de campañas
        $tables[] = "CREATE TABLE {$prefix}campanias_firmas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            campania_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED DEFAULT NULL,
            nombre varchar(255) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            comentario text DEFAULT NULL,
            es_publica tinyint(1) DEFAULT 1,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campania_id (campania_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Actualizaciones de campañas
        $tables[] = "CREATE TABLE {$prefix}campanias_actualizaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            campania_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            contenido text NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campania_id (campania_id)
        ) $charset_collate;";

        // Participantes de campañas
        $tables[] = "CREATE TABLE {$prefix}campanias_participantes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            campania_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            rol varchar(50) DEFAULT 'seguidor',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY campania_usuario (campania_id, usuario_id)
        ) $charset_collate;";

        // Acciones de campañas
        $tables[] = "CREATE TABLE {$prefix}campanias_acciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            campania_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo varchar(50) NOT NULL,
            fecha date DEFAULT NULL,
            ubicacion varchar(500) DEFAULT NULL,
            estado varchar(20) DEFAULT 'programada',
            PRIMARY KEY (id),
            KEY campania_id (campania_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Mapa de actores
        $tables[] = "CREATE TABLE {$prefix}mapa_actores (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            tipo varchar(50) NOT NULL,
            descripcion text DEFAULT NULL,
            ubicacion varchar(500) DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            contacto varchar(255) DEFAULT NULL,
            web varchar(500) DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            creador_id bigint(20) UNSIGNED DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY estado (estado)
        ) $charset_collate;";

        // Personas en mapa de actores
        $tables[] = "CREATE TABLE {$prefix}mapa_actores_personas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            actor_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED DEFAULT NULL,
            nombre varchar(255) NOT NULL,
            rol varchar(100) DEFAULT NULL,
            contacto varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY actor_id (actor_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Relaciones entre actores
        $tables[] = "CREATE TABLE {$prefix}mapa_actores_relaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            actor_origen_id bigint(20) UNSIGNED NOT NULL,
            actor_destino_id bigint(20) UNSIGNED NOT NULL,
            tipo_relacion varchar(50) NOT NULL,
            descripcion text DEFAULT NULL,
            intensidad int(11) DEFAULT 3,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY actor_origen_id (actor_origen_id),
            KEY actor_destino_id (actor_destino_id)
        ) $charset_collate;";

        // Interacciones de actores
        $tables[] = "CREATE TABLE {$prefix}mapa_actores_interacciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            actor_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo varchar(50) NOT NULL,
            descripcion text DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY actor_id (actor_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Saberes ancestrales
        $tables[] = "CREATE TABLE {$prefix}saberes_ancestrales (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            tipo enum('tecnica','receta','tradicion','artesania','otro') DEFAULT 'otro',
            origen varchar(255) DEFAULT NULL,
            transmisor_id bigint(20) UNSIGNED DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            recursos longtext DEFAULT NULL,
            estado varchar(20) DEFAULT 'publicado',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY categoria (categoria),
            KEY tipo (tipo),
            KEY transmisor_id (transmisor_id)
        ) $charset_collate;";

        // Saberes (alias)
        $tables[] = "CREATE TABLE {$prefix}saberes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo varchar(50) DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            creador_id bigint(20) UNSIGNED DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY creador_id (creador_id)
        ) $charset_collate;";

        // Aprendices de saberes
        $tables[] = "CREATE TABLE {$prefix}saberes_aprendices (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            saber_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            nivel varchar(20) DEFAULT 'principiante',
            fecha_inicio datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY saber_usuario (saber_id, usuario_id)
        ) $charset_collate;";

        // Transmisiones de saberes
        $tables[] = "CREATE TABLE {$prefix}saberes_transmisiones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            saber_id bigint(20) UNSIGNED NOT NULL,
            maestro_id bigint(20) UNSIGNED NOT NULL,
            aprendiz_id bigint(20) UNSIGNED NOT NULL,
            fecha date NOT NULL,
            duracion_horas decimal(4,2) DEFAULT NULL,
            notas text DEFAULT NULL,
            valoracion int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY saber_id (saber_id),
            KEY maestro_id (maestro_id),
            KEY aprendiz_id (aprendiz_id)
        ) $charset_collate;";

        // Documentación legal
        $tables[] = "CREATE TABLE {$prefix}documentacion_legal (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            contenido longtext NOT NULL,
            categoria_id bigint(20) UNSIGNED DEFAULT NULL,
            tipo varchar(50) DEFAULT 'documento',
            archivo_url varchar(500) DEFAULT NULL,
            version varchar(20) DEFAULT '1.0',
            estado varchar(20) DEFAULT 'publicado',
            autor_id bigint(20) UNSIGNED DEFAULT NULL,
            fecha_vigencia date DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY categoria_id (categoria_id),
            KEY tipo (tipo),
            KEY estado (estado)
        ) $charset_collate;";

        // Categorías de documentación legal
        $tables[] = "CREATE TABLE {$prefix}documentacion_legal_categorias (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            padre_id bigint(20) UNSIGNED DEFAULT NULL,
            orden int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY padre_id (padre_id)
        ) $charset_collate;";

        // Comentarios en documentación legal
        $tables[] = "CREATE TABLE {$prefix}documentacion_legal_comentarios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            documento_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            contenido text NOT NULL,
            estado varchar(20) DEFAULT 'pendiente',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY documento_id (documento_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Favoritos/Guardados de documentación
        $tables[] = "CREATE TABLE {$prefix}documentacion_legal_favoritos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            documento_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY documento_usuario (documento_id, usuario_id)
        ) $charset_collate;";

        // Alias guardados
        $tables[] = "CREATE TABLE {$prefix}documentacion_legal_guardados (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            documento_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY documento_usuario (documento_id, usuario_id)
        ) $charset_collate;";

        // Transparencia datos
        $tables[] = "CREATE TABLE {$prefix}transparencia_datos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            categoria varchar(100) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            valor varchar(255) DEFAULT NULL,
            unidad varchar(50) DEFAULT NULL,
            periodo varchar(50) DEFAULT NULL,
            archivo_url varchar(500) DEFAULT NULL,
            orden int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'publicado',
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY categoria (categoria),
            KEY estado (estado)
        ) $charset_collate;";

        // Documentos de transparencia
        $tables[] = "CREATE TABLE {$prefix}transparencia_documentos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            categoria varchar(100) DEFAULT NULL,
            archivo_url varchar(500) NOT NULL,
            tipo_archivo varchar(50) DEFAULT NULL,
            tamano_bytes bigint(20) DEFAULT NULL,
            descargas int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'publicado',
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY categoria (categoria),
            KEY estado (estado)
        ) $charset_collate;";

        // Solicitudes de transparencia
        $tables[] = "CREATE TABLE {$prefix}transparencia_solicitudes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            estado enum('pendiente','en_proceso','respondida','rechazada') DEFAULT 'pendiente',
            respuesta text DEFAULT NULL,
            documentos_respuesta longtext DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_respuesta datetime DEFAULT NULL,
            respondido_por bigint(20) UNSIGNED DEFAULT NULL,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Seguimiento de denuncias
        $tables[] = "CREATE TABLE {$prefix}seguimiento_denuncias (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo varchar(50) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            denunciante_id bigint(20) UNSIGNED DEFAULT NULL,
            anonima tinyint(1) DEFAULT 0,
            estado enum('recibida','en_investigacion','resuelta','archivada') DEFAULT 'recibida',
            prioridad enum('baja','media','alta','urgente') DEFAULT 'media',
            asignado_a bigint(20) UNSIGNED DEFAULT NULL,
            fecha_denuncia datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_resolucion datetime DEFAULT NULL,
            resolucion text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY codigo (codigo),
            KEY denunciante_id (denunciante_id),
            KEY estado (estado),
            KEY asignado_a (asignado_a)
        ) $charset_collate;";

        // Eventos de seguimiento
        $tables[] = "CREATE TABLE {$prefix}seguimiento_denuncias_eventos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            denuncia_id bigint(20) UNSIGNED NOT NULL,
            tipo varchar(50) NOT NULL,
            descripcion text NOT NULL,
            usuario_id bigint(20) UNSIGNED DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY denuncia_id (denuncia_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Participantes en denuncias
        $tables[] = "CREATE TABLE {$prefix}seguimiento_denuncias_participantes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            denuncia_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            rol varchar(50) NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY denuncia_id (denuncia_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Plantillas de denuncias
        $tables[] = "CREATE TABLE {$prefix}seguimiento_denuncias_plantillas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            campos longtext NOT NULL,
            activa tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY categoria (categoria)
        ) $charset_collate;";

        // Trading IA
        $tables[] = "CREATE TABLE {$prefix}trading_ia_trades (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo enum('compra','venta') NOT NULL,
            activo varchar(50) NOT NULL,
            cantidad decimal(20,8) NOT NULL,
            precio decimal(20,8) NOT NULL,
            total decimal(20,8) NOT NULL,
            estado varchar(20) DEFAULT 'ejecutado',
            estrategia varchar(100) DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY activo (activo),
            KEY estado (estado),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Portfolio de trading
        $tables[] = "CREATE TABLE {$prefix}trading_ia_portfolio (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            activo varchar(50) NOT NULL,
            cantidad decimal(20,8) NOT NULL,
            precio_medio decimal(20,8) DEFAULT 0,
            valor_actual decimal(20,8) DEFAULT 0,
            ganancia_perdida decimal(20,8) DEFAULT 0,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_activo (usuario_id, activo)
        ) $charset_collate;";

        // Decisiones de IA
        $tables[] = "CREATE TABLE {$prefix}trading_ia_decisiones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            activo varchar(50) NOT NULL,
            accion_recomendada enum('comprar','vender','mantener') NOT NULL,
            confianza decimal(5,2) NOT NULL,
            razonamiento text DEFAULT NULL,
            indicadores_usados longtext DEFAULT NULL,
            ejecutada tinyint(1) DEFAULT 0,
            trade_id bigint(20) UNSIGNED DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY activo (activo),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Alertas de trading
        $tables[] = "CREATE TABLE {$prefix}trading_ia_alertas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            activo varchar(50) NOT NULL,
            tipo varchar(50) NOT NULL,
            condicion varchar(255) NOT NULL,
            valor_objetivo decimal(20,8) DEFAULT NULL,
            activa tinyint(1) DEFAULT 1,
            disparada tinyint(1) DEFAULT 0,
            fecha_disparo datetime DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY activo (activo),
            KEY activa (activa)
        ) $charset_collate;";

        // Indicadores de trading
        $tables[] = "CREATE TABLE {$prefix}trading_ia_indicadores (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            activo varchar(50) NOT NULL,
            indicador varchar(50) NOT NULL,
            valor decimal(20,8) NOT NULL,
            periodo varchar(20) DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY activo (activo),
            KEY indicador (indicador),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Reglas de trading
        $tables[] = "CREATE TABLE {$prefix}trading_ia_reglas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            nombre varchar(255) NOT NULL,
            condiciones longtext NOT NULL,
            acciones longtext NOT NULL,
            activa tinyint(1) DEFAULT 1,
            ejecuciones int(11) DEFAULT 0,
            ultima_ejecucion datetime DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY activa (activa)
        ) $charset_collate;";

        // Ajustes de trading
        $tables[] = "CREATE TABLE {$prefix}trading_ia_ajustes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            clave varchar(100) NOT NULL,
            valor longtext DEFAULT NULL,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_clave (usuario_id, clave)
        ) $charset_collate;";

        // Empresarial proyectos
        $tables[] = "CREATE TABLE {$prefix}empresarial_proyectos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            cliente_id bigint(20) UNSIGNED DEFAULT NULL,
            responsable_id bigint(20) UNSIGNED DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            fecha_inicio date DEFAULT NULL,
            fecha_fin date DEFAULT NULL,
            presupuesto decimal(12,2) DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY cliente_id (cliente_id),
            KEY responsable_id (responsable_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Contactos empresariales
        $tables[] = "CREATE TABLE {$prefix}empresarial_contactos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            email varchar(255) DEFAULT NULL,
            telefono varchar(50) DEFAULT NULL,
            empresa varchar(255) DEFAULT NULL,
            cargo varchar(100) DEFAULT NULL,
            notas text DEFAULT NULL,
            propietario_id bigint(20) UNSIGNED DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY propietario_id (propietario_id),
            KEY email (email)
        ) $charset_collate;";

        // Multimedia
        $tables[] = "CREATE TABLE {$prefix}multimedia (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('imagen','video','audio','documento') NOT NULL,
            url varchar(500) NOT NULL,
            thumbnail_url varchar(500) DEFAULT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            album_id bigint(20) UNSIGNED DEFAULT NULL,
            tamano_bytes bigint(20) DEFAULT NULL,
            mime_type varchar(100) DEFAULT NULL,
            vistas int(11) DEFAULT 0,
            likes int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'publicado',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY album_id (album_id),
            KEY tipo (tipo),
            KEY estado (estado)
        ) $charset_collate;";

        // Álbumes multimedia
        $tables[] = "CREATE TABLE {$prefix}multimedia_albumes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            portada_id bigint(20) UNSIGNED DEFAULT NULL,
            privacidad enum('publico','privado','amigos') DEFAULT 'publico',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Categorías multimedia
        $tables[] = "CREATE TABLE {$prefix}multimedia_categorias (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        // Tags multimedia
        $tables[] = "CREATE TABLE {$prefix}multimedia_tags (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            multimedia_id bigint(20) UNSIGNED NOT NULL,
            tag varchar(100) NOT NULL,
            PRIMARY KEY (id),
            KEY multimedia_id (multimedia_id),
            KEY tag (tag)
        ) $charset_collate;";

        // Likes multimedia
        $tables[] = "CREATE TABLE {$prefix}multimedia_likes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            multimedia_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY multimedia_usuario (multimedia_id, usuario_id)
        ) $charset_collate;";

        // Comentarios multimedia
        $tables[] = "CREATE TABLE {$prefix}multimedia_comentarios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            multimedia_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            contenido text NOT NULL,
            padre_id bigint(20) UNSIGNED DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY multimedia_id (multimedia_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Reportes multimedia
        $tables[] = "CREATE TABLE {$prefix}multimedia_reportes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            multimedia_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            razon varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            estado varchar(20) DEFAULT 'pendiente',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY multimedia_id (multimedia_id),
            KEY estado (estado)
        ) $charset_collate;";

        // Recetas
        $tables[] = "CREATE TABLE {$prefix}recetas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            ingredientes longtext NOT NULL,
            instrucciones longtext NOT NULL,
            tiempo_preparacion int(11) DEFAULT NULL,
            tiempo_coccion int(11) DEFAULT NULL,
            porciones int(11) DEFAULT NULL,
            dificultad enum('facil','media','dificil') DEFAULT 'media',
            categoria varchar(100) DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            autor_id bigint(20) UNSIGNED NOT NULL,
            valoracion_media decimal(3,2) DEFAULT 0,
            estado varchar(20) DEFAULT 'publicada',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY autor_id (autor_id),
            KEY categoria (categoria),
            KEY estado (estado)
        ) $charset_collate;";

        // Valoraciones de recetas
        $tables[] = "CREATE TABLE {$prefix}recetas_valoraciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            receta_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            valoracion int(11) NOT NULL,
            comentario text DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY receta_usuario (receta_id, usuario_id)
        ) $charset_collate;";

        // Socios cuotas
        $tables[] = "CREATE TABLE {$prefix}socios_cuotas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            socio_id bigint(20) UNSIGNED NOT NULL,
            concepto varchar(255) NOT NULL,
            importe decimal(10,2) NOT NULL,
            periodo_inicio date NOT NULL,
            periodo_fin date NOT NULL,
            estado enum('pendiente','pagada','vencida','cancelada') DEFAULT 'pendiente',
            fecha_vencimiento date NOT NULL,
            fecha_pago datetime DEFAULT NULL,
            metodo_pago varchar(50) DEFAULT NULL,
            referencia_pago varchar(100) DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY socio_id (socio_id),
            KEY estado (estado),
            KEY fecha_vencimiento (fecha_vencimiento)
        ) $charset_collate;";

        // Pagos de socios
        $tables[] = "CREATE TABLE {$prefix}socios_pagos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            socio_id bigint(20) UNSIGNED NOT NULL,
            cuota_id bigint(20) UNSIGNED DEFAULT NULL,
            importe decimal(10,2) NOT NULL,
            metodo varchar(50) NOT NULL,
            referencia varchar(100) DEFAULT NULL,
            estado varchar(20) DEFAULT 'completado',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY socio_id (socio_id),
            KEY cuota_id (cuota_id)
        ) $charset_collate;";

        // Historial de socios
        $tables[] = "CREATE TABLE {$prefix}socios_historial (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            socio_id bigint(20) UNSIGNED NOT NULL,
            accion varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            datos_anteriores longtext DEFAULT NULL,
            datos_nuevos longtext DEFAULT NULL,
            realizado_por bigint(20) UNSIGNED DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY socio_id (socio_id),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Beneficios de socios
        $tables[] = "CREATE TABLE {$prefix}socios_beneficios (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo_socio varchar(50) DEFAULT 'todos',
            descuento_porcentaje decimal(5,2) DEFAULT NULL,
            activo tinyint(1) DEFAULT 1,
            fecha_inicio date DEFAULT NULL,
            fecha_fin date DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tipo_socio (tipo_socio),
            KEY activo (activo)
        ) $charset_collate;";

        // Tipos de socios
        $tables[] = "CREATE TABLE {$prefix}socios_tipos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text DEFAULT NULL,
            cuota_anual decimal(10,2) DEFAULT 0,
            beneficios longtext DEFAULT NULL,
            activo tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        // Transacciones de socios
        $tables[] = "CREATE TABLE {$prefix}socios_transacciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            socio_id bigint(20) UNSIGNED NOT NULL,
            tipo varchar(50) NOT NULL,
            concepto varchar(255) NOT NULL,
            importe decimal(10,2) NOT NULL,
            saldo_anterior decimal(10,2) DEFAULT 0,
            saldo_posterior decimal(10,2) DEFAULT 0,
            referencia varchar(100) DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY socio_id (socio_id),
            KEY tipo (tipo),
            KEY fecha (fecha)
        ) $charset_collate;";

        // Votaciones
        $tables[] = "CREATE TABLE {$prefix}votaciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('simple','multiple','ranking') DEFAULT 'simple',
            opciones longtext NOT NULL,
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime NOT NULL,
            estado enum('borrador','activa','cerrada','cancelada') DEFAULT 'borrador',
            creador_id bigint(20) UNSIGNED NOT NULL,
            requiere_autenticacion tinyint(1) DEFAULT 1,
            permite_comentarios tinyint(1) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY creador_id (creador_id),
            KEY estado (estado),
            KEY fecha_inicio (fecha_inicio),
            KEY fecha_fin (fecha_fin)
        ) $charset_collate;";

        // Votos
        $tables[] = "CREATE TABLE {$prefix}votos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            votacion_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED DEFAULT NULL,
            opcion_index int(11) NOT NULL,
            valor int(11) DEFAULT 1,
            ip varchar(45) DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY votacion_id (votacion_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Propuestas (participación)
        $tables[] = "CREATE TABLE {$prefix}propuestas (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            descripcion text NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            estado enum('borrador','publicada','en_debate','votacion','aprobada','rechazada','archivada') DEFAULT 'borrador',
            apoyos_count int(11) DEFAULT 0,
            comentarios_count int(11) DEFAULT 0,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY categoria (categoria),
            KEY estado (estado)
        ) $charset_collate;";

        // Apoyos a propuestas
        $tables[] = "CREATE TABLE {$prefix}apoyos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            propuesta_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY propuesta_usuario (propuesta_id, usuario_id)
        ) $charset_collate;";

        // Red social posts (para módulo genérico)
        $tables[] = "CREATE TABLE {$prefix}red_social_posts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            contenido text NOT NULL,
            tipo varchar(50) DEFAULT 'texto',
            adjuntos longtext DEFAULT NULL,
            likes_count int(11) DEFAULT 0,
            comentarios_count int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'publicado',
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY estado (estado),
            KEY fecha_creacion (fecha_creacion)
        ) $charset_collate;";

        // Interacciones de red social
        $tables[] = "CREATE TABLE {$prefix}red_social_interacciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            tipo enum('like','comentario','compartir') NOT NULL,
            contenido text DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY usuario_id (usuario_id),
            KEY tipo (tipo)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA PRESUPUESTOS PARTICIPATIVOS
        // =====================================================

        // Ciclos de presupuestos participativos
        $tables[] = "CREATE TABLE {$prefix}pp_ciclos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            presupuesto_total decimal(12,2) DEFAULT 0,
            fecha_inicio date NOT NULL,
            fecha_fin date NOT NULL,
            fase_actual varchar(50) DEFAULT 'propuestas',
            estado varchar(50) DEFAULT 'activo',
            configuracion longtext DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY fase_actual (fase_actual),
            KEY fecha_inicio (fecha_inicio)
        ) $charset_collate;";

        // Proyectos de presupuestos participativos
        $tables[] = "CREATE TABLE {$prefix}pp_proyectos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ciclo_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            presupuesto decimal(12,2) DEFAULT 0,
            categoria varchar(100) DEFAULT 'general',
            ubicacion varchar(255) DEFAULT NULL,
            imagen varchar(500) DEFAULT NULL,
            votos_count int(11) DEFAULT 0,
            estado varchar(50) DEFAULT 'borrador',
            motivo_rechazo text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ciclo_id (ciclo_id),
            KEY usuario_id (usuario_id),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY votos_count (votos_count)
        ) $charset_collate;";

        // Votos de presupuestos participativos
        $tables[] = "CREATE TABLE {$prefix}pp_votos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ciclo_id bigint(20) UNSIGNED NOT NULL,
            proyecto_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            puntos int(11) DEFAULT 1,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY voto_unico (ciclo_id, proyecto_id, usuario_id),
            KEY ciclo_id (ciclo_id),
            KEY proyecto_id (proyecto_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // =========================================================================
        // TABLAS E2E (Cifrado extremo a extremo - Signal Protocol)
        // =========================================================================

        // Claves de identidad por usuario/dispositivo
        $tables[] = "CREATE TABLE {$prefix}e2e_identity_keys (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            dispositivo_id varchar(64) NOT NULL,
            public_key text NOT NULL,
            private_key_encrypted text NOT NULL,
            key_fingerprint varchar(128) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_dispositivo (usuario_id, dispositivo_id),
            KEY usuario_id (usuario_id),
            KEY key_fingerprint (key_fingerprint)
        ) $charset_collate;";

        // Signed PreKeys (rotan cada 30 días)
        $tables[] = "CREATE TABLE {$prefix}e2e_signed_prekeys (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            dispositivo_id varchar(64) NOT NULL,
            prekey_id int(11) UNSIGNED NOT NULL,
            public_key text NOT NULL,
            signature text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_dispositivo_prekey (usuario_id, dispositivo_id, prekey_id),
            KEY usuario_id (usuario_id),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        // One-time PreKeys (se consumen al usarse)
        $tables[] = "CREATE TABLE {$prefix}e2e_one_time_prekeys (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            dispositivo_id varchar(64) NOT NULL,
            prekey_id int(11) UNSIGNED NOT NULL,
            public_key text NOT NULL,
            used tinyint(1) DEFAULT 0,
            used_at datetime DEFAULT NULL,
            used_by_usuario_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_dispositivo_prekey (usuario_id, dispositivo_id, prekey_id),
            KEY usuario_id (usuario_id),
            KEY used (used)
        ) $charset_collate;";

        // Estado de sesiones Double Ratchet
        $tables[] = "CREATE TABLE {$prefix}e2e_sessions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id varchar(128) NOT NULL,
            usuario_local_id bigint(20) UNSIGNED NOT NULL,
            dispositivo_local_id varchar(64) NOT NULL,
            usuario_remoto_id bigint(20) UNSIGNED NOT NULL,
            dispositivo_remoto_id varchar(64) NOT NULL,
            state_encrypted text NOT NULL,
            root_key_hash varchar(64) DEFAULT NULL,
            message_number int(11) UNSIGNED DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY usuario_local_id (usuario_local_id),
            KEY usuario_remoto_id (usuario_remoto_id),
            KEY local_remoto (usuario_local_id, dispositivo_local_id, usuario_remoto_id, dispositivo_remoto_id)
        ) $charset_collate;";

        // Dispositivos registrados
        $tables[] = "CREATE TABLE {$prefix}e2e_devices (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            dispositivo_id varchar(64) NOT NULL,
            nombre varchar(255) DEFAULT NULL,
            tipo enum('web','android','ios','desktop') DEFAULT 'web',
            user_agent text DEFAULT NULL,
            is_primary tinyint(1) DEFAULT 0,
            revoked tinyint(1) DEFAULT 0,
            revoked_at datetime DEFAULT NULL,
            last_seen datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_dispositivo (usuario_id, dispositivo_id),
            KEY usuario_id (usuario_id),
            KEY revoked (revoked),
            KEY last_seen (last_seen)
        ) $charset_collate;";

        // Sender Keys para grupos
        $tables[] = "CREATE TABLE {$prefix}e2e_sender_keys (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            grupo_id bigint(20) UNSIGNED NOT NULL,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            dispositivo_id varchar(64) NOT NULL,
            chain_key_encrypted text NOT NULL,
            iteration int(11) UNSIGNED DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY grupo_usuario_dispositivo (grupo_id, usuario_id, dispositivo_id),
            KEY grupo_id (grupo_id),
            KEY usuario_id (usuario_id)
        ) $charset_collate;";

        // Distribución de Sender Keys
        $tables[] = "CREATE TABLE {$prefix}e2e_sender_key_distribution (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            grupo_id bigint(20) UNSIGNED NOT NULL,
            propietario_id bigint(20) UNSIGNED NOT NULL,
            propietario_dispositivo_id varchar(64) NOT NULL,
            receptor_id bigint(20) UNSIGNED NOT NULL,
            receptor_dispositivo_id varchar(64) NOT NULL,
            distribuido tinyint(1) DEFAULT 0,
            distribuido_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY distribucion_unica (grupo_id, propietario_id, propietario_dispositivo_id, receptor_id, receptor_dispositivo_id),
            KEY grupo_id (grupo_id),
            KEY receptor_id (receptor_id)
        ) $charset_collate;";

        // Identidades verificadas
        $tables[] = "CREATE TABLE {$prefix}e2e_verified_identities (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_verificador_id bigint(20) UNSIGNED NOT NULL,
            usuario_verificado_id bigint(20) UNSIGNED NOT NULL,
            dispositivo_verificado_id varchar(64) NOT NULL,
            fingerprint varchar(128) NOT NULL,
            verified_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY verificacion_unica (usuario_verificador_id, usuario_verificado_id, dispositivo_verificado_id),
            KEY usuario_verificador_id (usuario_verificador_id),
            KEY usuario_verificado_id (usuario_verificado_id)
        ) $charset_collate;";

        // Backups cifrados de claves
        $tables[] = "CREATE TABLE {$prefix}e2e_key_backups (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) UNSIGNED NOT NULL,
            backup_key_hash varchar(128) NOT NULL,
            encrypted_bundle longtext NOT NULL,
            version int(11) UNSIGNED DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usuario_id (usuario_id),
            KEY backup_key_hash (backup_key_hash)
        ) $charset_collate;";

        // =====================================================
        // TABLAS PARA CHAT IA (Conversaciones con asistente)
        // =====================================================

        // Conversaciones de Chat IA
        $tables[] = "CREATE TABLE {$prefix}chat_conversations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            session_id varchar(64) NOT NULL,
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            ended_at datetime DEFAULT NULL,
            message_count int(11) DEFAULT 0,
            total_tokens int(11) DEFAULT 0,
            escalated tinyint(1) DEFAULT 0,
            escalated_to bigint(20) UNSIGNED DEFAULT NULL,
            escalated_at datetime DEFAULT NULL,
            context_module varchar(100) DEFAULT NULL,
            satisfaction_rating tinyint(1) DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY started_at (started_at),
            KEY escalated (escalated),
            KEY context_module (context_module),
            KEY user_started (user_id, started_at)
        ) $charset_collate;";

        // Mensajes de Chat IA
        $tables[] = "CREATE TABLE {$prefix}chat_messages (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) UNSIGNED NOT NULL,
            role enum('user','assistant','system') NOT NULL,
            content longtext NOT NULL,
            tokens_used int(11) DEFAULT 0,
            tools_used longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            metadata longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY role (role),
            KEY created_at (created_at),
            KEY conv_created (conversation_id, created_at)
        ) $charset_collate;";

        // Uso de API por día (para métricas del dashboard)
        $tables[] = "CREATE TABLE {$prefix}chat_api_usage (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            fecha date NOT NULL,
            provider varchar(50) NOT NULL DEFAULT 'claude',
            total_requests int(11) DEFAULT 0,
            total_tokens_input int(11) DEFAULT 0,
            total_tokens_output int(11) DEFAULT 0,
            total_cost decimal(10,4) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY fecha_provider (fecha, provider),
            KEY fecha (fecha)
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

        // Nodo local para la red federada
        $site_name = get_bloginfo('name') ?: 'Mi Comunidad';
        $site_url = home_url();
        $wpdb->insert("{$prefix}network_nodes", [
            'nombre' => $site_name,
            'slug' => sanitize_title($site_name),
            'descripcion' => get_bloginfo('description'),
            'site_url' => $site_url,
            'api_url' => self::build_flavor_integration_api_url(),
            'logo_url' => get_site_icon_url(),
            'es_nodo_local' => 1,
            'activo' => 1
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
            'facturas', 'bicicletas', 'bicicletas_alquileres', 'parkings',
            // Tablas de foros extendidas
            'foros', 'foros_hilos', 'foros_respuestas',
            // Tablas de red social
            'social_seguimientos',
            // Tablas de reputación y gamificación
            'social_reputacion', 'social_badges', 'social_usuario_badges', 'social_historial_puntos', 'social_engagement',
            // Tablas de integraciones y red
            'interactions', 'interaction_counts', 'network_nodes', 'network_shared_content', 'module_integrations',
            // Tablas de privacidad RGPD
            'privacy_consents', 'privacy_requests',
            // Tablas de moderación
            'moderation_reports', 'moderation_actions', 'moderation_sanctions', 'moderation_warnings', 'moderation_report_counts',
            // Tablas de chat estados (Stories)
            'chat_estados', 'chat_estados_vistas', 'chat_estados_reacciones', 'chat_estados_respuestas', 'chat_estados_silenciados'
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

            // Chat IA (conversaciones con asistente)
            ['tabla' => 'chat_conversations', 'nombre' => 'dashboard_stats', 'columnas' => 'started_at, escalated'],
            ['tabla' => 'chat_conversations', 'nombre' => 'user_recent', 'columnas' => 'user_id, started_at'],
            ['tabla' => 'chat_messages', 'nombre' => 'api_usage', 'columnas' => 'role, created_at'],
            ['tabla' => 'chat_messages', 'nombre' => 'conv_role', 'columnas' => 'conversation_id, role'],
            ['tabla' => 'chat_api_usage', 'nombre' => 'reporting', 'columnas' => 'fecha, provider'],

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

            // =====================================================
            // Índices para optimización del Feed Unificado (Mi Red)
            // =====================================================

            // Red Social - publicaciones
            ['tabla' => 'social_publicaciones', 'nombre' => 'feed_estado_fecha', 'columnas' => 'estado, fecha_publicacion'],
            ['tabla' => 'social_publicaciones', 'nombre' => 'feed_visibilidad', 'columnas' => 'visibilidad'],
            ['tabla' => 'social_publicaciones', 'nombre' => 'feed_autor_fecha', 'columnas' => 'usuario_id, fecha_publicacion'],
            ['tabla' => 'social_publicaciones', 'nombre' => 'feed_entidad', 'columnas' => 'entidad_tipo, entidad_id'],

            // Multimedia
            ['tabla' => 'multimedia', 'nombre' => 'feed_estado_fecha', 'columnas' => 'estado, fecha_creacion'],
            ['tabla' => 'multimedia', 'nombre' => 'feed_tipo_estado', 'columnas' => 'tipo, estado'],
            ['tabla' => 'multimedia', 'nombre' => 'feed_usuario', 'columnas' => 'usuario_id, fecha_creacion'],

            // Podcast
            ['tabla' => 'podcast_episodios', 'nombre' => 'feed_estado_fecha', 'columnas' => 'estado, fecha_publicacion'],
            ['tabla' => 'podcast_series', 'nombre' => 'feed_entidad', 'columnas' => 'entidad_tipo, entidad_id'],

            // Radio
            ['tabla' => 'radio_programas', 'nombre' => 'feed_estado', 'columnas' => 'estado'],
            ['tabla' => 'radio_emisiones', 'nombre' => 'feed_fecha', 'columnas' => 'fecha_emision'],

            // Comunidades
            ['tabla' => 'comunidades', 'nombre' => 'feed_estado', 'columnas' => 'estado'],
            ['tabla' => 'comunidades_publicaciones', 'nombre' => 'feed_estado_fecha', 'columnas' => 'estado, fecha_publicacion'],
            ['tabla' => 'comunidades_miembros', 'nombre' => 'feed_usuario_estado', 'columnas' => 'usuario_id, estado'],

            // Foros
            ['tabla' => 'foros_temas', 'nombre' => 'feed_estado_fecha', 'columnas' => 'estado, fecha_creacion'],
            ['tabla' => 'foros_temas', 'nombre' => 'feed_entidad', 'columnas' => 'entidad_tipo, entidad_id'],

            // Colectivos
            ['tabla' => 'colectivos_miembros', 'nombre' => 'feed_usuario_estado', 'columnas' => 'usuario_id, estado'],
            ['tabla' => 'colectivos_proyectos', 'nombre' => 'feed_colectivo_estado', 'columnas' => 'colectivo_id, estado'],
            ['tabla' => 'colectivos_asambleas', 'nombre' => 'feed_colectivo_fecha', 'columnas' => 'colectivo_id, fecha_hora'],

            // Círculos de Cuidados
            ['tabla' => 'circulos_cuidados', 'nombre' => 'feed_privacidad_estado', 'columnas' => 'privacidad, estado'],
            ['tabla' => 'circulos_miembros', 'nombre' => 'feed_usuario_estado', 'columnas' => 'usuario_id, estado'],
            ['tabla' => 'circulos_actividades', 'nombre' => 'feed_circulo_fecha', 'columnas' => 'circulo_id, fecha_inicio'],

            // Notificaciones sociales
            ['tabla' => 'social_notificaciones', 'nombre' => 'feed_receptor_leida', 'columnas' => 'receptor_id, leida'],
            ['tabla' => 'social_notificaciones', 'nombre' => 'feed_receptor_fecha', 'columnas' => 'receptor_id, fecha_creacion'],

            // Reacciones y comentarios
            ['tabla' => 'social_reacciones', 'nombre' => 'feed_post_tipo', 'columnas' => 'post_id, tipo'],
            ['tabla' => 'social_comentarios', 'nombre' => 'feed_post_fecha', 'columnas' => 'post_id, fecha_creacion'],
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

            // Verificar que todas las columnas del índice existen
            $columnas_array = array_map('trim', explode(',', $indice['columnas']));
            $todas_columnas_existen = true;

            foreach ($columnas_array as $columna) {
                $columna_existe = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE table_schema = DATABASE()
                     AND table_name = %s
                     AND column_name = %s",
                    $tabla_completa,
                    $columna
                ));
                if (!$columna_existe) {
                    $todas_columnas_existen = false;
                    break;
                }
            }

            if (!$todas_columnas_existen) {
                // Las columnas no existen, saltamos este índice silenciosamente
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
        $version_nueva = '1.8.0'; // Versión con tablas de Chat IA para dashboard

        if (version_compare($version_actual, $version_nueva, '<')) {
            // Crear tablas faltantes
            self::create_missing_tables();
            // Añadir índices faltantes
            self::add_missing_indexes();
            // Crear nodo local si no existe
            self::maybe_create_local_node();
            // Añadir campos WhatsApp a tabla de mensajes
            self::upgrade_whatsapp_fields();
            update_option('flavor_db_version', $version_nueva);
        }
    }

    /**
     * Programa la creación del nodo local para un hook más tardío
     * donde todas las funciones de WordPress están disponibles
     */
    private static function maybe_create_local_node() {
        // Diferir a 'init' porque durante 'plugins_loaded' las funciones REST
        // y get_bloginfo() pueden no estar completamente disponibles
        if (!did_action('init')) {
            add_action('init', [__CLASS__, 'create_local_node_deferred'], 20);
            return;
        }
        self::create_local_node_deferred();
    }

    /**
     * Crea el nodo local si no existe (ejecución diferida)
     */
    public static function create_local_node_deferred() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'flavor_';

        // Verificar si la tabla existe
        $tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '{$prefix}network_nodes'");
        if (!$tabla_existe) {
            return;
        }

        // Verificar si ya existe el nodo local
        $nodo_local_existe = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$prefix}network_nodes WHERE es_nodo_local = 1"
        );

        if ($nodo_local_existe > 0) {
            return;
        }

        $site_name = get_bloginfo('name') ?: 'Mi Comunidad';
        $api_url = self::build_flavor_integration_api_url();

        $wpdb->insert("{$prefix}network_nodes", [
            'nombre' => $site_name,
            'slug' => sanitize_title($site_name),
            'descripcion' => get_bloginfo('description'),
            'site_url' => home_url(),
            'api_url' => $api_url,
            'logo_url' => get_site_icon_url(),
            'es_nodo_local' => 1,
            'activo' => 1
        ]);
    }

    /**
     * Construye la URL del endpoint de integración evitando fatales cuando
     * WordPress aún no ha inicializado completamente rewrite/rest.
     *
     * @return string
     */
    private static function build_flavor_integration_api_url() {
        $route = 'flavor-integration/v1/';

        // rest_url puede fallar en contexto temprano (plugins_loaded) si $wp_rewrite es null.
        if (function_exists('rest_url')) {
            global $wp_rewrite;
            if (did_action('init') || ($wp_rewrite instanceof WP_Rewrite)) {
                return rest_url($route);
            }
        }

        // Fallback manual seguro.
        $rest_prefix = function_exists('rest_get_url_prefix') ? rest_get_url_prefix() : 'wp-json';
        return trailingslashit(home_url()) . trailingslashit($rest_prefix) . $route;
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
                'title'   => __('Política de Cookies', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'content' => self::get_cookies_policy_content(),
            ],
            'terminos-de-uso' => [
                'title'   => __('Términos de Uso', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
<h2>' . __('¿Qué son las cookies?', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . __('Las cookies son pequeños archivos de texto que los sitios web almacenan en su dispositivo cuando los visita. Se utilizan ampliamente para hacer que los sitios web funcionen de manera más eficiente y proporcionar información a los propietarios del sitio.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>' . __('Cookies que utilizamos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . __('Utilizamos cookies técnicas necesarias para el funcionamiento del sitio, cookies de análisis para entender cómo se usa el sitio, y cookies de preferencias para recordar su configuración.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>' . __('Gestión de cookies', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . __('Puede configurar su navegador para rechazar cookies o alertarle cuando se envían cookies. Sin embargo, algunas partes del sitio pueden no funcionar correctamente sin cookies.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
<!-- /wp:paragraph -->';
    }

    /**
     * Contenido predeterminado de términos de uso
     */
    private static function get_terms_content() {
        return '<!-- wp:heading -->
<h2>' . __('Aceptación de los términos', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . __('Al acceder y utilizar este sitio web, usted acepta cumplir con estos términos de uso. Si no está de acuerdo con alguna parte de estos términos, no debe utilizar nuestro sitio.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>' . __('Uso del sitio', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . __('Este sitio está destinado a usuarios mayores de edad. El contenido es solo para fines informativos y no constituye asesoramiento profesional.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>' . __('Propiedad intelectual', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . __('Todo el contenido de este sitio, incluyendo textos, gráficos, logos e imágenes, es propiedad del sitio o sus licenciantes y está protegido por las leyes de propiedad intelectual.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
<!-- /wp:paragraph -->';
    }

    /**
     * Inserta badges predeterminados del sistema de reputación
     *
     * @param string $prefix Prefijo de tablas
     */
    private static function insert_default_badges($prefix) {
        global $wpdb;

        $tabla_badges = $prefix . 'social_badges';

        // Verificar si la tabla existe
        $tabla_existe = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $tabla_badges)
        );

        if (!$tabla_existe) {
            return;
        }

        // Verificar si ya hay badges
        $badges_existentes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_badges");
        if ($badges_existentes > 0) {
            return;
        }

        $badges_predeterminados = [
            [
                'nombre' => 'Primeros Pasos',
                'slug' => 'primeros-pasos',
                'descripcion' => 'Completaste tu perfil y publicaste tu primer contenido',
                'icono' => '🎯',
                'color' => '#3b82f6',
                'categoria' => 'participacion',
                'puntos_requeridos' => 25,
                'condicion_especial' => null,
                'es_unico' => 1,
                'orden' => 1
            ],
            [
                'nombre' => 'Comunicador',
                'slug' => 'comunicador',
                'descripcion' => 'Has comentado en más de 50 publicaciones',
                'icono' => '💬',
                'color' => '#8b5cf6',
                'categoria' => 'participacion',
                'puntos_requeridos' => 0,
                'condicion_especial' => json_encode(['tipo' => 'comentarios_count', 'valor' => 50]),
                'es_unico' => 1,
                'orden' => 2
            ],
            [
                'nombre' => 'Creador Activo',
                'slug' => 'creador-activo',
                'descripcion' => 'Has creado más de 20 publicaciones',
                'icono' => '✍️',
                'color' => '#f59e0b',
                'categoria' => 'creacion',
                'puntos_requeridos' => 0,
                'condicion_especial' => json_encode(['tipo' => 'publicaciones_count', 'valor' => 20]),
                'es_unico' => 1,
                'orden' => 3
            ],
            [
                'nombre' => 'Influencer',
                'slug' => 'influencer',
                'descripcion' => 'Has conseguido más de 100 seguidores',
                'icono' => '⭐',
                'color' => '#ec4899',
                'categoria' => 'comunidad',
                'puntos_requeridos' => 0,
                'condicion_especial' => json_encode(['tipo' => 'seguidores_count', 'valor' => 100]),
                'es_unico' => 1,
                'orden' => 4
            ],
            [
                'nombre' => 'Constante',
                'slug' => 'constante',
                'descripcion' => 'Has mantenido una racha de 7 días de actividad',
                'icono' => '🔥',
                'color' => '#ef4444',
                'categoria' => 'participacion',
                'puntos_requeridos' => 0,
                'condicion_especial' => json_encode(['tipo' => 'racha_dias', 'valor' => 7]),
                'es_unico' => 1,
                'orden' => 5
            ],
            [
                'nombre' => 'Maratonista',
                'slug' => 'maratonista',
                'descripcion' => 'Has mantenido una racha de 30 días de actividad',
                'icono' => '🏃',
                'color' => '#10b981',
                'categoria' => 'participacion',
                'puntos_requeridos' => 0,
                'condicion_especial' => json_encode(['tipo' => 'racha_dias', 'valor' => 30]),
                'es_unico' => 1,
                'orden' => 6
            ],
            [
                'nombre' => 'Centenario',
                'slug' => 'centenario',
                'descripcion' => 'Has alcanzado 100 puntos de reputación',
                'icono' => '💯',
                'color' => '#6366f1',
                'categoria' => 'participacion',
                'puntos_requeridos' => 100,
                'condicion_especial' => null,
                'es_unico' => 1,
                'orden' => 7
            ],
            [
                'nombre' => 'Veterano',
                'slug' => 'veterano',
                'descripcion' => 'Has alcanzado 1000 puntos de reputación',
                'icono' => '🏆',
                'color' => '#f97316',
                'categoria' => 'especial',
                'puntos_requeridos' => 1000,
                'condicion_especial' => null,
                'es_unico' => 1,
                'orden' => 8
            ],
            [
                'nombre' => 'Colaborador',
                'slug' => 'colaborador',
                'descripcion' => 'Has ayudado a otros usuarios con más de 10 respuestas valoradas',
                'icono' => '🤝',
                'color' => '#14b8a6',
                'categoria' => 'comunidad',
                'puntos_requeridos' => 0,
                'condicion_especial' => json_encode(['tipo' => 'respuestas_valoradas', 'valor' => 10]),
                'es_unico' => 1,
                'orden' => 9
            ],
            [
                'nombre' => 'Embajador',
                'slug' => 'embajador',
                'descripcion' => 'Has invitado a más de 5 usuarios que se registraron',
                'icono' => '🌟',
                'color' => '#a855f7',
                'categoria' => 'especial',
                'puntos_requeridos' => 0,
                'condicion_especial' => json_encode(['tipo' => 'invitaciones_exitosas', 'valor' => 5]),
                'es_unico' => 1,
                'orden' => 10
            ]
        ];

        foreach ($badges_predeterminados as $badge) {
            $wpdb->insert($tabla_badges, array_merge($badge, [
                'activo' => 1,
                'fecha_creacion' => current_time('mysql')
            ]));
        }
    }

    /**
     * Añade campos WhatsApp a las tablas de mensajes existentes
     *
     * Campos añadidos:
     * - delivery_status: Estado de entrega (pending, sent, delivered, read)
     * - delivery_timestamp: Fecha del último cambio de estado
     * - es_temporal: Si el mensaje es temporal/efímero
     * - fecha_expiracion: Cuándo expira el mensaje temporal
     * - has_links: Si el mensaje contiene enlaces
     * - link_previews: JSON con URLs detectadas para preview
     */
    public static function upgrade_whatsapp_fields() {
        global $wpdb;

        // Tablas de mensajes a actualizar (chat interno y grupos)
        $tablas_mensajes = [
            $wpdb->prefix . 'flavor_chat_mensajes',
            $wpdb->prefix . 'flavor_chat_grupos_mensajes',
        ];

        foreach ($tablas_mensajes as $tabla_mensajes) {
            self::add_whatsapp_fields_to_table($tabla_mensajes);
        }
    }

    /**
     * Añade campos WhatsApp a una tabla específica de mensajes
     *
     * @param string $tabla_mensajes Nombre completo de la tabla
     */
    private static function add_whatsapp_fields_to_table($tabla_mensajes) {
        global $wpdb;

        // Verificar si la tabla existe
        $tabla_existe = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $tabla_mensajes)
        );

        if (!$tabla_existe) {
            return;
        }

        // Obtener columnas existentes
        $columnas_existentes = $wpdb->get_col("DESCRIBE {$tabla_mensajes}", 0);

        // Determinar columna de referencia para AFTER (puede variar entre tablas)
        $columna_referencia = in_array('eliminado_para', $columnas_existentes) ? 'eliminado_para' : 'eliminado';

        // Campos a añadir con sus definiciones SQL
        $campos_nuevos = [
            'delivery_status' => "ALTER TABLE {$tabla_mensajes} ADD COLUMN delivery_status enum('pending','sent','delivered','read') DEFAULT 'sent' AFTER {$columna_referencia}",
            'delivery_timestamp' => "ALTER TABLE {$tabla_mensajes} ADD COLUMN delivery_timestamp datetime DEFAULT NULL AFTER delivery_status",
            'es_temporal' => "ALTER TABLE {$tabla_mensajes} ADD COLUMN es_temporal tinyint(1) DEFAULT 0 AFTER delivery_timestamp",
            'fecha_expiracion' => "ALTER TABLE {$tabla_mensajes} ADD COLUMN fecha_expiracion datetime DEFAULT NULL AFTER es_temporal",
            'has_links' => "ALTER TABLE {$tabla_mensajes} ADD COLUMN has_links tinyint(1) DEFAULT 0 AFTER fecha_expiracion",
            'link_previews' => "ALTER TABLE {$tabla_mensajes} ADD COLUMN link_previews text DEFAULT NULL AFTER has_links",
        ];

        foreach ($campos_nuevos as $campo => $sql) {
            if (!in_array($campo, $columnas_existentes)) {
                $wpdb->query($sql);
            }
        }

        // Añadir índices para los nuevos campos
        $indices_nuevos = [
            'idx_delivery_status' => "ALTER TABLE {$tabla_mensajes} ADD INDEX idx_delivery_status (delivery_status)",
            'idx_es_temporal' => "ALTER TABLE {$tabla_mensajes} ADD INDEX idx_es_temporal (es_temporal)",
            'idx_fecha_expiracion' => "ALTER TABLE {$tabla_mensajes} ADD INDEX idx_fecha_expiracion (fecha_expiracion)",
        ];

        foreach ($indices_nuevos as $nombre_indice => $sql) {
            // Verificar si el índice ya existe
            $indice_existe = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
                 WHERE table_schema = DATABASE()
                 AND table_name = %s
                 AND index_name = %s",
                $tabla_mensajes,
                $nombre_indice
            ));

            if (!$indice_existe) {
                $wpdb->query($sql);
            }
        }
    }

    /**
     * Añade campos E2E (cifrado extremo a extremo) a las tablas de mensajes existentes
     *
     * Campos añadidos:
     * - cifrado: 1 si el mensaje está cifrado E2E
     * - ciphertext: Texto cifrado del mensaje
     * - e2e_version: Versión del protocolo E2E usado
     * - ratchet_header: Header del ratchet para descifrado (JSON)
     * - sender_key_id: ID del Sender Key usado (para grupos)
     * - sender_key_iteration: Iteración del Sender Key (para grupos)
     * - legacy_plaintext: 1 si es un mensaje anterior a E2E (sin cifrar)
     */
    public static function upgrade_e2e_fields() {
        global $wpdb;

        // Tablas de mensajes a actualizar
        $tablas_mensajes = [
            $wpdb->prefix . 'flavor_chat_mensajes',
            $wpdb->prefix . 'flavor_chat_grupos_mensajes',
        ];

        foreach ($tablas_mensajes as $tabla_mensajes) {
            self::add_e2e_fields_to_table($tabla_mensajes);
        }
    }

    /**
     * Añade campos E2E a una tabla específica de mensajes
     *
     * @param string $tabla_mensajes Nombre completo de la tabla
     */
    private static function add_e2e_fields_to_table($tabla_mensajes) {
        global $wpdb;

        // Verificar si la tabla existe
        $tabla_existe = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $tabla_mensajes)
        );

        if (!$tabla_existe) {
            return;
        }

        // Obtener columnas existentes
        $columnas_existentes = $wpdb->get_col("DESCRIBE {$tabla_mensajes}", 0);

        // Determinar columna de referencia para AFTER
        $columna_referencia = 'fecha_creacion';
        if (in_array('link_previews', $columnas_existentes)) {
            $columna_referencia = 'link_previews';
        }

        // Campos E2E a añadir con sus definiciones SQL
        $campos_e2e = [
            'cifrado' => "ALTER TABLE {$tabla_mensajes} ADD COLUMN cifrado tinyint(1) DEFAULT 0 AFTER {$columna_referencia}",
            'ciphertext' => "ALTER TABLE {$tabla_mensajes} ADD COLUMN ciphertext text DEFAULT NULL AFTER cifrado",
            'e2e_version' => "ALTER TABLE {$tabla_mensajes} ADD COLUMN e2e_version smallint UNSIGNED DEFAULT NULL AFTER ciphertext",
            'ratchet_header' => "ALTER TABLE {$tabla_mensajes} ADD COLUMN ratchet_header text DEFAULT NULL AFTER e2e_version",
            'sender_key_id' => "ALTER TABLE {$tabla_mensajes} ADD COLUMN sender_key_id bigint(20) UNSIGNED DEFAULT NULL AFTER ratchet_header",
            'sender_key_iteration' => "ALTER TABLE {$tabla_mensajes} ADD COLUMN sender_key_iteration int(11) UNSIGNED DEFAULT NULL AFTER sender_key_id",
            'legacy_plaintext' => "ALTER TABLE {$tabla_mensajes} ADD COLUMN legacy_plaintext tinyint(1) DEFAULT 0 AFTER sender_key_iteration",
        ];

        foreach ($campos_e2e as $campo => $sql) {
            if (!in_array($campo, $columnas_existentes)) {
                $wpdb->query($sql);
            }
        }

        // Añadir índices para campos E2E
        $indices_e2e = [
            'idx_cifrado' => "ALTER TABLE {$tabla_mensajes} ADD INDEX idx_cifrado (cifrado)",
            'idx_legacy_plaintext' => "ALTER TABLE {$tabla_mensajes} ADD INDEX idx_legacy_plaintext (legacy_plaintext)",
        ];

        foreach ($indices_e2e as $nombre_indice => $sql) {
            $indice_existe = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
                 WHERE table_schema = DATABASE()
                 AND table_name = %s
                 AND index_name = %s",
                $tabla_mensajes,
                $nombre_indice
            ));

            if (!$indice_existe) {
                $wpdb->query($sql);
            }
        }
    }

    /**
     * Marca los mensajes existentes como legacy_plaintext
     * Ejecutar una sola vez durante la migración a E2E
     */
    public static function mark_existing_messages_as_legacy() {
        global $wpdb;

        $tablas_mensajes = [
            $wpdb->prefix . 'flavor_chat_mensajes',
            $wpdb->prefix . 'flavor_chat_grupos_mensajes',
        ];

        foreach ($tablas_mensajes as $tabla) {
            $tabla_existe = $wpdb->get_var(
                $wpdb->prepare("SHOW TABLES LIKE %s", $tabla)
            );

            if ($tabla_existe) {
                // Marcar como legacy todos los mensajes que no están cifrados
                $wpdb->query(
                    "UPDATE {$tabla} SET legacy_plaintext = 1 WHERE cifrado = 0 AND legacy_plaintext = 0"
                );
            }
        }
    }
}
