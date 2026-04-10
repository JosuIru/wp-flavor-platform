<?php
/**
 * Instalador de Red de Comunidades
 *
 * Crea y gestiona las tablas necesarias para el sistema de red.
 *
 * @package FlavorPlatform\Network
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Network_Installer {

    /**
     * Prefijo de tablas de red
     */
    const TABLE_PREFIX = 'flavor_network_';

    /**
     * Versión del esquema de BD
     */
    const DB_VERSION = '1.8.0';

    /**
     * Opción que almacena la versión actual de BD
     */
    const DB_VERSION_OPTION = 'flavor_network_db_version';

    /**
     * Crea todas las tablas del sistema de red
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . self::TABLE_PREFIX;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ─── Tabla: Nodos de la red ───
        $tabla_nodos = $prefix . 'nodes';
        $sql_nodos = "CREATE TABLE {$tabla_nodos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            site_url varchar(500) NOT NULL,
            nombre varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            descripcion_corta varchar(500) DEFAULT '',
            logo_url varchar(500) DEFAULT '',
            banner_url varchar(500) DEFAULT '',
            tipo_entidad varchar(100) DEFAULT 'comunidad',
            sector varchar(100) DEFAULT '',
            tags text,
            nivel_consciencia varchar(50) DEFAULT 'basico',
            nivel_sello varchar(50) DEFAULT 'ninguno',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            direccion varchar(500) DEFAULT '',
            ciudad varchar(100) DEFAULT '',
            provincia varchar(100) DEFAULT '',
            pais varchar(100) DEFAULT 'ES',
            codigo_postal varchar(20) DEFAULT '',
            telefono varchar(50) DEFAULT '',
            email varchar(255) DEFAULT '',
            web varchar(500) DEFAULT '',
            redes_sociales text,
            api_key varchar(64) DEFAULT '',
            api_secret varchar(128) DEFAULT '',
            modulos_activos text,
            configuracion longtext,
            es_nodo_local tinyint(1) DEFAULT 0,
            verificado tinyint(1) DEFAULT 0,
            estado varchar(20) DEFAULT 'activo',
            miembros_count int(11) DEFAULT 0,
            ultima_sincronizacion datetime DEFAULT NULL,
            fecha_registro datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY estado (estado),
            KEY tipo_entidad (tipo_entidad),
            KEY nivel_consciencia (nivel_consciencia),
            KEY pais_ciudad (pais, ciudad),
            KEY es_nodo_local (es_nodo_local)
        ) {$charset_collate};";
        dbDelta($sql_nodos);

        // ─── Tabla: Conexiones entre nodos ───
        $tabla_conexiones = $prefix . 'connections';
        $sql_conexiones = "CREATE TABLE {$tabla_conexiones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_origen_id bigint(20) unsigned NOT NULL,
            nodo_destino_id bigint(20) unsigned NOT NULL,
            tipo_conexion varchar(50) DEFAULT 'visible',
            nivel varchar(50) DEFAULT 'visible',
            estado varchar(20) DEFAULT 'pendiente',
            mensaje_solicitud text,
            metadata longtext,
            solicitado_por bigint(20) unsigned DEFAULT NULL,
            aprobado_por bigint(20) unsigned DEFAULT NULL,
            fecha_solicitud datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_aprobacion datetime DEFAULT NULL,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY conexion_unica (nodo_origen_id, nodo_destino_id),
            KEY nodo_origen_id (nodo_origen_id),
            KEY nodo_destino_id (nodo_destino_id),
            KEY estado (estado),
            KEY nivel (nivel)
        ) {$charset_collate};";
        dbDelta($sql_conexiones);

        // ─── Tabla: Mensajes entre nodos ───
        $tabla_mensajes = $prefix . 'messages';
        $sql_mensajes = "CREATE TABLE {$tabla_mensajes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            de_nodo_id bigint(20) unsigned NOT NULL,
            a_nodo_id bigint(20) unsigned NOT NULL,
            tipo varchar(50) DEFAULT 'mensaje',
            asunto varchar(255) DEFAULT '',
            contenido longtext NOT NULL,
            metadata longtext,
            leido tinyint(1) DEFAULT 0,
            respondido tinyint(1) DEFAULT 0,
            mensaje_padre_id bigint(20) unsigned DEFAULT NULL,
            fecha_envio datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_lectura datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY de_nodo_id (de_nodo_id),
            KEY a_nodo_id (a_nodo_id),
            KEY tipo (tipo),
            KEY leido (leido),
            KEY mensaje_padre_id (mensaje_padre_id)
        ) {$charset_collate};";
        dbDelta($sql_mensajes);

        // ─── Tabla: Favoritos ───
        $tabla_favoritos = $prefix . 'favorites';
        $sql_favoritos = "CREATE TABLE {$tabla_favoritos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id bigint(20) unsigned NOT NULL,
            nodo_favorito_id bigint(20) unsigned NOT NULL,
            notas text,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY favorito_unico (nodo_id, nodo_favorito_id),
            KEY nodo_id (nodo_id),
            KEY nodo_favorito_id (nodo_favorito_id)
        ) {$charset_collate};";
        dbDelta($sql_favoritos);

        // ─── Tabla: Recomendaciones ───
        $tabla_recomendaciones = $prefix . 'recommendations';
        $sql_recomendaciones = "CREATE TABLE {$tabla_recomendaciones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            de_nodo_id bigint(20) unsigned NOT NULL,
            a_nodo_id bigint(20) unsigned NOT NULL,
            nodo_recomendado_id bigint(20) unsigned NOT NULL,
            motivo text,
            estado varchar(20) DEFAULT 'pendiente',
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY de_nodo_id (de_nodo_id),
            KEY a_nodo_id (a_nodo_id),
            KEY nodo_recomendado_id (nodo_recomendado_id)
        ) {$charset_collate};";
        dbDelta($sql_recomendaciones);

        // ─── Tabla: Tablón de red (anuncios/publicaciones) ───
        $tabla_tablon = $prefix . 'board';
        $sql_tablon = "CREATE TABLE {$tabla_tablon} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id bigint(20) unsigned NOT NULL,
            tipo varchar(50) DEFAULT 'anuncio',
            titulo varchar(255) NOT NULL,
            contenido longtext NOT NULL,
            imagen_url varchar(500) DEFAULT '',
            categorias text,
            ambito varchar(50) DEFAULT 'red',
            prioridad varchar(20) DEFAULT 'normal',
            activo tinyint(1) DEFAULT 1,
            vistas int(11) DEFAULT 0,
            fecha_inicio datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_fin datetime DEFAULT NULL,
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY nodo_id (nodo_id),
            KEY tipo (tipo),
            KEY ambito (ambito),
            KEY activo (activo),
            KEY fecha_publicacion (fecha_publicacion)
        ) {$charset_collate};";
        dbDelta($sql_tablon);

        // ─── Tabla: Contenido compartido (catálogo, servicios, espacios, recursos) ───
        $tabla_contenido = $prefix . 'shared_content';
        $sql_contenido = "CREATE TABLE {$tabla_contenido} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id bigint(20) unsigned NOT NULL,
            tipo_contenido varchar(50) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion longtext,
            imagen_url varchar(500) DEFAULT '',
            imagenes_extra text,
            precio decimal(10,2) DEFAULT NULL,
            moneda varchar(10) DEFAULT 'EUR',
            unidad varchar(50) DEFAULT '',
            disponibilidad varchar(50) DEFAULT 'disponible',
            ubicacion varchar(500) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            categorias text,
            tags text,
            condiciones text,
            contacto_nombre varchar(255) DEFAULT '',
            contacto_email varchar(255) DEFAULT '',
            contacto_telefono varchar(50) DEFAULT '',
            metadata longtext,
            visible_red tinyint(1) DEFAULT 1,
            destacado tinyint(1) DEFAULT 0,
            vistas int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'activo',
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion datetime DEFAULT NULL,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY nodo_id (nodo_id),
            KEY tipo_contenido (tipo_contenido),
            KEY estado (estado),
            KEY visible_red (visible_red),
            KEY disponibilidad (disponibilidad),
            KEY fecha_publicacion (fecha_publicacion)
        ) {$charset_collate};";
        dbDelta($sql_contenido);

        // ─── Tabla: Eventos de red ───
        $tabla_eventos = $prefix . 'events';
        $sql_eventos = "CREATE TABLE {$tabla_eventos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion longtext,
            imagen_url varchar(500) DEFAULT '',
            tipo_evento varchar(50) DEFAULT 'presencial',
            ubicacion varchar(500) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            url_online varchar(500) DEFAULT '',
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime DEFAULT NULL,
            plazas int(11) DEFAULT NULL,
            inscritos int(11) DEFAULT 0,
            precio decimal(10,2) DEFAULT 0.00,
            categorias text,
            visible_red tinyint(1) DEFAULT 1,
            estado varchar(20) DEFAULT 'activo',
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY nodo_id (nodo_id),
            KEY fecha_inicio (fecha_inicio),
            KEY tipo_evento (tipo_evento),
            KEY estado (estado),
            KEY visible_red (visible_red)
        ) {$charset_collate};";
        dbDelta($sql_eventos);

        // ─── Tabla: Colaboraciones (compras colectivas, proyectos, alianzas, etc.) ───
        $tabla_colaboraciones = $prefix . 'collaborations';
        $sql_colaboraciones = "CREATE TABLE {$tabla_colaboraciones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_creador_id bigint(20) unsigned NOT NULL,
            tipo varchar(50) NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion longtext,
            imagen_url varchar(500) DEFAULT '',
            objetivo text,
            requisitos text,
            beneficios text,
            estado varchar(20) DEFAULT 'abierta',
            max_participantes int(11) DEFAULT NULL,
            fecha_limite datetime DEFAULT NULL,
            ubicacion varchar(500) DEFAULT '',
            ambito varchar(50) DEFAULT 'red',
            categorias text,
            metadata longtext,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY nodo_creador_id (nodo_creador_id),
            KEY tipo (tipo),
            KEY estado (estado),
            KEY ambito (ambito),
            KEY fecha_limite (fecha_limite)
        ) {$charset_collate};";
        dbDelta($sql_colaboraciones);

        // ─── Tabla: Participantes en colaboraciones ───
        $tabla_participantes = $prefix . 'collaboration_participants';
        $sql_participantes = "CREATE TABLE {$tabla_participantes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            colaboracion_id bigint(20) unsigned NOT NULL,
            nodo_id bigint(20) unsigned NOT NULL,
            rol varchar(50) DEFAULT 'participante',
            estado varchar(20) DEFAULT 'pendiente',
            aportacion text,
            notas text,
            fecha_inscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY participacion_unica (colaboracion_id, nodo_id),
            KEY colaboracion_id (colaboracion_id),
            KEY nodo_id (nodo_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql_participantes);

        // ─── Tabla: Sello de calidad / certificaciones ───
        $tabla_sellos = $prefix . 'quality_seals';
        $sql_sellos = "CREATE TABLE {$tabla_sellos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id bigint(20) unsigned NOT NULL,
            tipo_sello varchar(50) NOT NULL DEFAULT 'app_consciente',
            nivel varchar(50) NOT NULL DEFAULT 'basico',
            puntuacion int(11) DEFAULT 0,
            criterios_cumplidos longtext,
            verificado_por bigint(20) unsigned DEFAULT NULL,
            fecha_obtencion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_expiracion datetime DEFAULT NULL,
            fecha_revision datetime DEFAULT NULL,
            estado varchar(20) DEFAULT 'activo',
            PRIMARY KEY (id),
            KEY nodo_id (nodo_id),
            KEY tipo_sello (tipo_sello),
            KEY nivel (nivel),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql_sellos);

        // ─── Tabla: Alertas solidarias ───
        $tabla_alertas = $prefix . 'solidarity_alerts';
        $sql_alertas = "CREATE TABLE {$tabla_alertas} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id bigint(20) unsigned NOT NULL,
            tipo varchar(50) DEFAULT 'necesidad',
            titulo varchar(255) NOT NULL,
            descripcion longtext NOT NULL,
            urgencia varchar(20) DEFAULT 'media',
            ubicacion varchar(500) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            contacto text,
            estado varchar(20) DEFAULT 'activa',
            respuestas int(11) DEFAULT 0,
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_resolucion datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY nodo_id (nodo_id),
            KEY tipo (tipo),
            KEY urgencia (urgencia),
            KEY estado (estado),
            KEY fecha_publicacion (fecha_publicacion)
        ) {$charset_collate};";
        dbDelta($sql_alertas);

        // ─── Tabla: Ofertas de tiempo (banco de tiempo inter-nodos) ───
        $tabla_tiempo = $prefix . 'time_offers';
        $sql_tiempo = "CREATE TABLE {$tabla_tiempo} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id bigint(20) unsigned NOT NULL,
            tipo varchar(20) DEFAULT 'oferta',
            titulo varchar(255) NOT NULL,
            descripcion text,
            categoria varchar(100) DEFAULT '',
            horas_estimadas decimal(5,1) DEFAULT NULL,
            disponibilidad text,
            modalidad varchar(50) DEFAULT 'presencial',
            estado varchar(20) DEFAULT 'activa',
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY nodo_id (nodo_id),
            KEY tipo (tipo),
            KEY categoria (categoria),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql_tiempo);


        // ─── Tabla: Newsletters de red ───
        $tabla_newsletters = $prefix . 'newsletters';
        $sql_newsletters = "CREATE TABLE {$tabla_newsletters} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id bigint(20) unsigned NOT NULL,
            asunto varchar(255) NOT NULL,
            contenido longtext NOT NULL,
            tipo varchar(50) DEFAULT 'resumen',
            estado varchar(20) DEFAULT 'borrador',
            destinatarios_count int(11) DEFAULT 0,
            abiertos_count int(11) DEFAULT 0,
            fecha_programada datetime DEFAULT NULL,
            fecha_envio datetime DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY nodo_id (nodo_id),
            KEY estado (estado),
            KEY fecha_envio (fecha_envio)
        ) {$charset_collate};";
        dbDelta($sql_newsletters);

        // ─── Tabla: Suscripciones a newsletter ───
        $tabla_suscripciones = $prefix . 'newsletter_subscribers';
        $sql_suscripciones = "CREATE TABLE {$tabla_suscripciones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id bigint(20) unsigned NOT NULL,
            email varchar(255) NOT NULL,
            nombre varchar(255) DEFAULT '',
            estado varchar(20) DEFAULT 'activo',
            token_baja varchar(64) DEFAULT '',
            fecha_suscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY suscripcion_unica (nodo_id, email),
            KEY nodo_id (nodo_id),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql_suscripciones);

        // ─── Tabla: Matches entre necesidades y excedentes ───
        $tabla_matches = $prefix . 'matches';
        $sql_matches = "CREATE TABLE {$tabla_matches} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            necesidad_id bigint(20) unsigned NOT NULL,
            excedente_id bigint(20) unsigned NOT NULL,
            nodo_necesidad_id bigint(20) unsigned NOT NULL,
            nodo_excedente_id bigint(20) unsigned NOT NULL,
            puntuacion int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'sugerido',
            mensaje text,
            respuesta text,
            fecha_match datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_respuesta datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY match_unico (necesidad_id, excedente_id),
            KEY nodo_necesidad_id (nodo_necesidad_id),
            KEY nodo_excedente_id (nodo_excedente_id),
            KEY estado (estado),
            KEY puntuacion (puntuacion)
        ) {$charset_collate};";
        dbDelta($sql_matches);

        // ─── Tabla: Preguntas a la red ───
        $tabla_preguntas = $prefix . 'questions';
        $sql_preguntas = "CREATE TABLE {$tabla_preguntas} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion longtext NOT NULL,
            categoria varchar(100) DEFAULT 'general',
            tags text,
            estado varchar(20) DEFAULT 'abierta',
            respuestas_count int(11) DEFAULT 0,
            vistas int(11) DEFAULT 0,
            votacion_positiva int(11) DEFAULT 0,
            votacion_negativa int(11) DEFAULT 0,
            destacada tinyint(1) DEFAULT 0,
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY nodo_id (nodo_id),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY destacada (destacada),
            KEY fecha_publicacion (fecha_publicacion)
        ) {$charset_collate};";
        dbDelta($sql_preguntas);

        // ─── Tabla: Respuestas a preguntas de la red ───
        $tabla_respuestas = $prefix . 'answers';
        $sql_respuestas = "CREATE TABLE {$tabla_respuestas} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            pregunta_id bigint(20) unsigned NOT NULL,
            nodo_id bigint(20) unsigned NOT NULL,
            contenido longtext NOT NULL,
            es_solucion tinyint(1) DEFAULT 0,
            votos_positivos int(11) DEFAULT 0,
            votos_negativos int(11) DEFAULT 0,
            estado varchar(20) DEFAULT 'activa',
            fecha_publicacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY pregunta_id (pregunta_id),
            KEY nodo_id (nodo_id),
            KEY es_solucion (es_solucion),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql_respuestas);

        // ─── Tabla: Productores federados ───
        $tabla_productores = $prefix . 'producers';
        $sql_productores = "CREATE TABLE {$tabla_productores} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id bigint(20) unsigned NOT NULL,
            productor_id bigint(20) unsigned NOT NULL,
            nombre varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            descripcion text,
            logo_url varchar(500) DEFAULT '',
            ubicacion varchar(255) DEFAULT '',
            direccion varchar(500) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            radio_entrega_km int(11) DEFAULT 0,
            telefono varchar(50) DEFAULT '',
            email varchar(255) DEFAULT '',
            web varchar(500) DEFAULT '',
            certificacion_eco tinyint(1) DEFAULT 0,
            numero_certificado varchar(100) DEFAULT '',
            metodos_produccion text,
            categorias text,
            productos_count int(11) DEFAULT 0,
            compartir_en_red tinyint(1) DEFAULT 1,
            visible_en_red tinyint(1) DEFAULT 1,
            estado varchar(20) DEFAULT 'activo',
            fecha_registro datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nodo_productor (nodo_id, productor_id),
            KEY nodo_id (nodo_id),
            KEY slug (slug),
            KEY latitud_longitud (latitud, longitud),
            KEY radio_entrega_km (radio_entrega_km),
            KEY certificacion_eco (certificacion_eco),
            KEY compartir_en_red (compartir_en_red),
            KEY visible_en_red (visible_en_red),
            KEY estado (estado)
        ) {$charset_collate};";
        dbDelta($sql_productores);

        // ─── Tabla: Productos de productores federados ───
        $tabla_productos_red = $prefix . 'producer_products';
        $sql_productos_red = "CREATE TABLE {$tabla_productos_red} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            producer_id bigint(20) unsigned NOT NULL,
            producto_id bigint(20) unsigned NOT NULL,
            nombre varchar(255) NOT NULL,
            descripcion text,
            precio decimal(10,2) DEFAULT NULL,
            unidad varchar(50) DEFAULT 'kg',
            categoria varchar(100) DEFAULT '',
            imagen_url varchar(500) DEFAULT '',
            disponible tinyint(1) DEFAULT 1,
            temporada varchar(100) DEFAULT '',
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY producer_producto (producer_id, producto_id),
            KEY producer_id (producer_id),
            KEY categoria (categoria),
            KEY disponible (disponible)
        ) {$charset_collate};";
        dbDelta($sql_productos_red);

        // ─── Tabla: Eventos federados ───
        $tabla_eventos = $prefix . 'events';
        $sql_eventos = "CREATE TABLE {$tabla_eventos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id varchar(36) NOT NULL,
            evento_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            tipo varchar(50) DEFAULT 'social',
            fecha_inicio datetime NOT NULL,
            fecha_fin datetime DEFAULT NULL,
            ubicacion varchar(255) DEFAULT '',
            direccion varchar(500) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            es_online tinyint(1) DEFAULT 0,
            url_online varchar(500) DEFAULT '',
            organizador_nombre varchar(255) DEFAULT '',
            precio decimal(10,2) DEFAULT 0.00,
            aforo_maximo int(11) DEFAULT 0,
            inscritos_count int(11) DEFAULT 0,
            imagen_url varchar(500) DEFAULT '',
            compartir_en_red tinyint(1) DEFAULT 1,
            visible_en_red tinyint(1) DEFAULT 1,
            creado_en datetime DEFAULT CURRENT_TIMESTAMP,
            actualizado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nodo_evento (nodo_id, evento_id),
            KEY nodo_id (nodo_id),
            KEY fecha_inicio (fecha_inicio),
            KEY latitud_longitud (latitud, longitud),
            KEY visible_en_red (visible_en_red)
        ) {$charset_collate};";
        dbDelta($sql_eventos);

        // ─── Tabla: Viajes carpooling federados ───
        $tabla_viajes = $prefix . 'carpooling';
        $sql_viajes = "CREATE TABLE {$tabla_viajes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id varchar(36) NOT NULL,
            viaje_id bigint(20) unsigned NOT NULL,
            origen varchar(255) NOT NULL,
            origen_lat decimal(10,8) DEFAULT NULL,
            origen_lng decimal(11,8) DEFAULT NULL,
            destino varchar(255) NOT NULL,
            destino_lat decimal(10,8) DEFAULT NULL,
            destino_lng decimal(11,8) DEFAULT NULL,
            fecha_salida datetime NOT NULL,
            hora_salida time DEFAULT NULL,
            conductor_nombre varchar(255) DEFAULT '',
            plazas_totales int(11) DEFAULT 4,
            plazas_disponibles int(11) DEFAULT 4,
            precio_plaza decimal(10,2) DEFAULT 0.00,
            permite_equipaje tinyint(1) DEFAULT 1,
            permite_mascotas tinyint(1) DEFAULT 0,
            notas text,
            estado enum('activo','completo','cancelado','finalizado') DEFAULT 'activo',
            compartir_en_red tinyint(1) DEFAULT 1,
            visible_en_red tinyint(1) DEFAULT 1,
            creado_en datetime DEFAULT CURRENT_TIMESTAMP,
            actualizado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nodo_viaje (nodo_id, viaje_id),
            KEY nodo_id (nodo_id),
            KEY fecha_salida (fecha_salida),
            KEY origen_coords (origen_lat, origen_lng),
            KEY destino_coords (destino_lat, destino_lng),
            KEY estado (estado),
            KEY visible_en_red (visible_en_red)
        ) {$charset_collate};";
        dbDelta($sql_viajes);

        // ─── Tabla: Talleres federados ───
        $tabla_talleres = $prefix . 'workshops';
        $sql_talleres = "CREATE TABLE {$tabla_talleres} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id varchar(36) NOT NULL,
            taller_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            slug varchar(255) DEFAULT '',
            descripcion text,
            categoria varchar(100) DEFAULT '',
            nivel enum('principiante','intermedio','avanzado','todos') DEFAULT 'todos',
            duracion_horas decimal(5,2) DEFAULT 0,
            numero_sesiones int(11) DEFAULT 1,
            max_participantes int(11) DEFAULT 20,
            inscritos_actuales int(11) DEFAULT 0,
            precio decimal(10,2) DEFAULT 0,
            es_gratuito tinyint(1) DEFAULT 1,
            ubicacion varchar(500) DEFAULT '',
            latitud decimal(10,7) DEFAULT NULL,
            longitud decimal(10,7) DEFAULT NULL,
            organizador_nombre varchar(255) DEFAULT '',
            imagen_url varchar(500) DEFAULT '',
            fecha_primera_sesion datetime DEFAULT NULL,
            fecha_limite_inscripcion datetime DEFAULT NULL,
            estado varchar(50) DEFAULT 'publicado',
            compartir_en_red tinyint(1) DEFAULT 1,
            visible_en_red tinyint(1) DEFAULT 1,
            creado_en datetime DEFAULT CURRENT_TIMESTAMP,
            actualizado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nodo_taller (nodo_id, taller_id),
            KEY nodo_id (nodo_id),
            KEY categoria (categoria),
            KEY fecha_primera_sesion (fecha_primera_sesion),
            KEY latitud_longitud (latitud, longitud),
            KEY visible_en_red (visible_en_red)
        ) {$charset_collate};";
        dbDelta($sql_talleres);

        // ─── Tabla: Espacios comunes federados ───
        $tabla_espacios = $prefix . 'spaces';
        $sql_espacios = "CREATE TABLE {$tabla_espacios} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id varchar(36) NOT NULL,
            espacio_id bigint(20) unsigned NOT NULL,
            nombre varchar(255) NOT NULL,
            descripcion text,
            tipo varchar(50) DEFAULT 'salon_eventos',
            ubicacion varchar(500) DEFAULT '',
            latitud decimal(10,7) DEFAULT NULL,
            longitud decimal(10,7) DEFAULT NULL,
            capacidad_personas int(11) DEFAULT 0,
            superficie_m2 decimal(10,2) DEFAULT NULL,
            precio_hora decimal(10,2) DEFAULT 0,
            precio_dia decimal(10,2) DEFAULT 0,
            horario_apertura time DEFAULT '08:00:00',
            horario_cierre time DEFAULT '22:00:00',
            dias_disponibles varchar(50) DEFAULT 'L,M,X,J,V,S,D',
            foto_principal varchar(500) DEFAULT '',
            estado varchar(50) DEFAULT 'disponible',
            compartir_en_red tinyint(1) DEFAULT 1,
            visible_en_red tinyint(1) DEFAULT 1,
            creado_en datetime DEFAULT CURRENT_TIMESTAMP,
            actualizado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nodo_espacio (nodo_id, espacio_id),
            KEY nodo_id (nodo_id),
            KEY tipo (tipo),
            KEY latitud_longitud (latitud, longitud),
            KEY visible_en_red (visible_en_red)
        ) {$charset_collate};";
        dbDelta($sql_espacios);

        // ─── Tabla: Anuncios marketplace federados ───
        $tabla_anuncios = $prefix . 'marketplace';
        $sql_anuncios = "CREATE TABLE {$tabla_anuncios} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id varchar(36) NOT NULL,
            anuncio_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            slug varchar(255) DEFAULT '',
            descripcion text,
            tipo enum('venta','compra','intercambio','regalo','alquiler','servicio') DEFAULT 'venta',
            categoria varchar(100) DEFAULT '',
            precio decimal(10,2) DEFAULT NULL,
            es_gratuito tinyint(1) DEFAULT 0,
            condicion varchar(50) DEFAULT 'buen_estado',
            imagen_principal varchar(500) DEFAULT '',
            ubicacion varchar(500) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            envio_disponible tinyint(1) DEFAULT 0,
            usuario_nombre varchar(200) DEFAULT '',
            estado varchar(50) DEFAULT 'publicado',
            compartir_en_red tinyint(1) DEFAULT 1,
            visible_en_red tinyint(1) DEFAULT 1,
            creado_en datetime DEFAULT CURRENT_TIMESTAMP,
            actualizado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nodo_anuncio (nodo_id, anuncio_id),
            KEY nodo_id (nodo_id),
            KEY tipo (tipo),
            KEY categoria (categoria),
            KEY precio (precio),
            KEY latitud_longitud (latitud, longitud),
            KEY visible_en_red (visible_en_red)
        ) {$charset_collate};";
        dbDelta($sql_anuncios);

        // ─── Tabla: Servicios banco de tiempo federados ───
        $tabla_banco_tiempo = $prefix . 'time_bank';
        $sql_banco_tiempo = "CREATE TABLE {$tabla_banco_tiempo} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id varchar(36) NOT NULL,
            servicio_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text,
            tipo enum('oferta','demanda') DEFAULT 'oferta',
            categoria varchar(100) DEFAULT '',
            horas_estimadas decimal(5,2) DEFAULT 1.00,
            modalidad enum('presencial','online','ambas') DEFAULT 'presencial',
            disponibilidad text,
            ubicacion varchar(500) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            usuario_nombre varchar(200) DEFAULT '',
            valoracion_promedio decimal(3,2) DEFAULT 0.00,
            intercambios_completados int(11) DEFAULT 0,
            estado varchar(50) DEFAULT 'activo',
            compartir_en_red tinyint(1) DEFAULT 1,
            visible_en_red tinyint(1) DEFAULT 1,
            creado_en datetime DEFAULT CURRENT_TIMESTAMP,
            actualizado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nodo_servicio (nodo_id, servicio_id),
            KEY nodo_id (nodo_id),
            KEY tipo (tipo),
            KEY categoria (categoria),
            KEY modalidad (modalidad),
            KEY latitud_longitud (latitud, longitud),
            KEY visible_en_red (visible_en_red)
        ) {$charset_collate};";
        dbDelta($sql_banco_tiempo);

        // ─── Tabla: Cursos federados ───
        $tabla_cursos = $prefix . 'courses';
        $sql_cursos = "CREATE TABLE {$tabla_cursos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id varchar(36) NOT NULL,
            curso_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            slug varchar(255) DEFAULT '',
            descripcion text,
            categoria varchar(100) DEFAULT '',
            nivel enum('principiante','intermedio','avanzado','todos') DEFAULT 'todos',
            modalidad enum('online','presencial','mixto') DEFAULT 'online',
            duracion_horas decimal(6,2) DEFAULT 0,
            numero_lecciones int(11) DEFAULT 0,
            max_alumnos int(11) DEFAULT 30,
            inscritos_actuales int(11) DEFAULT 0,
            precio decimal(10,2) DEFAULT 0,
            es_gratuito tinyint(1) DEFAULT 1,
            ubicacion varchar(500) DEFAULT '',
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            instructor_nombre varchar(200) DEFAULT '',
            valoracion_promedio decimal(3,2) DEFAULT 0.00,
            imagen_url varchar(500) DEFAULT '',
            fecha_inicio datetime DEFAULT NULL,
            fecha_fin datetime DEFAULT NULL,
            estado varchar(50) DEFAULT 'publicado',
            compartir_en_red tinyint(1) DEFAULT 1,
            visible_en_red tinyint(1) DEFAULT 1,
            creado_en datetime DEFAULT CURRENT_TIMESTAMP,
            actualizado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nodo_curso (nodo_id, curso_id),
            KEY nodo_id (nodo_id),
            KEY categoria (categoria),
            KEY nivel (nivel),
            KEY modalidad (modalidad),
            KEY fecha_inicio (fecha_inicio),
            KEY latitud_longitud (latitud, longitud),
            KEY visible_en_red (visible_en_red)
        ) {$charset_collate};";
        dbDelta($sql_cursos);

        // Guardar versión de BD
        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
    }

    /**
     * Comprueba si es necesario actualizar las tablas
     */
    public static function maybe_upgrade() {
        $version_actual = get_option(self::DB_VERSION_OPTION, '0');
        if (version_compare($version_actual, self::DB_VERSION, '<')) {
            self::create_tables();
        }
    }

    /**
     * Obtiene el nombre completo de una tabla de red
     */
    public static function get_table_name($tabla) {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_PREFIX . $tabla;
    }
}
