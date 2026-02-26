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

        // Insertar badges predeterminados
        self::insert_default_badges($prefix);

        // Añadir campos WhatsApp a tabla de mensajes
        self::upgrade_whatsapp_fields();

        update_option('flavor_db_version', '1.8.0');
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
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY foro_id (foro_id),
            KEY autor_id (autor_id),
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
            'api_url' => rest_url('flavor-integration/v1/'),
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
        $version_nueva = '1.7.0'; // Versión con tablas de foros extendidas y social_seguimientos

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
     * Crea el nodo local si no existe
     */
    private static function maybe_create_local_node() {
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

        // Crear nodo local
        $site_name = get_bloginfo('name') ?: 'Mi Comunidad';
        $wpdb->insert("{$prefix}network_nodes", [
            'nombre' => $site_name,
            'slug' => sanitize_title($site_name),
            'descripcion' => get_bloginfo('description'),
            'site_url' => home_url(),
            'api_url' => rest_url('flavor-integration/v1/'),
            'logo_url' => get_site_icon_url(),
            'es_nodo_local' => 1,
            'activo' => 1
        ]);
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
}
