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
    const DB_VERSION = '1.5.0';

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
            api_key_expires_at datetime DEFAULT NULL,
            api_key_rotated_at datetime DEFAULT NULL,
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

        // ─── Tabla: Permisos ACL por nodo/tipo-contenido ───
        $tabla_permisos = $prefix . 'permissions';
        $sql_permisos = "CREATE TABLE IF NOT EXISTS {$tabla_permisos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nodo_id bigint(20) unsigned NOT NULL,
            tipo_contenido varchar(50) NOT NULL,
            permiso ENUM('leer', 'escribir', 'admin') DEFAULT 'leer',
            otorgado_por bigint(20) unsigned DEFAULT NULL,
            notas text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_nodo_tipo (nodo_id, tipo_contenido),
            KEY idx_nodo (nodo_id),
            KEY idx_tipo_contenido (tipo_contenido),
            KEY idx_permiso (permiso)
        ) {$charset_collate};";
        dbDelta($sql_permisos);

        // ════════════════════════════════════════════════════════════════════
        // ═══ TABLAS P2P/MESH (v1.5.0) ═══════════════════════════════════════
        // ════════════════════════════════════════════════════════════════════

        // ─── Tabla: Peers de la red mesh (reemplaza concepto jerárquico) ───
        $tabla_peers = $prefix . 'peers';
        $sql_peers = "CREATE TABLE {$tabla_peers} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            peer_id varchar(64) NOT NULL COMMENT 'Hash SHA-256 de clave pública Ed25519',
            node_id bigint(20) unsigned DEFAULT NULL COMMENT 'FK a tabla nodes para compatibilidad',
            public_key_ed25519 text NOT NULL COMMENT 'Clave pública Ed25519 base64',
            private_key_encrypted text COMMENT 'Clave privada cifrada (solo para peer local)',
            display_name varchar(255) DEFAULT '',
            site_url varchar(500) DEFAULT '',
            capabilities text COMMENT 'JSON: capacidades del peer',
            reputacion_score decimal(5,2) DEFAULT 0.00 COMMENT 'Score de reputación 0-100',
            trust_level ENUM('unknown', 'seen', 'verified', 'trusted') DEFAULT 'unknown',
            vector_clock_version bigint(20) unsigned DEFAULT 0,
            last_seen datetime DEFAULT NULL,
            last_successful_sync datetime DEFAULT NULL,
            is_local_peer tinyint(1) DEFAULT 0 COMMENT 'Si es el peer de esta instalación',
            is_bootstrap_node tinyint(1) DEFAULT 0 COMMENT 'Si es nodo bootstrap conocido',
            is_online tinyint(1) DEFAULT 0,
            connection_failures int(11) DEFAULT 0 COMMENT 'Contador de fallos consecutivos',
            metadata longtext COMMENT 'JSON: datos adicionales del peer',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY peer_id (peer_id),
            KEY node_id (node_id),
            KEY trust_level (trust_level),
            KEY is_local_peer (is_local_peer),
            KEY is_bootstrap_node (is_bootstrap_node),
            KEY is_online (is_online),
            KEY last_seen (last_seen),
            KEY reputacion_score (reputacion_score)
        ) {$charset_collate};";
        dbDelta($sql_peers);

        // ─── Tabla: Conexiones mesh bidireccionales (sin restricción de ciclos) ───
        $tabla_mesh_connections = $prefix . 'mesh_connections';
        $sql_mesh_connections = "CREATE TABLE {$tabla_mesh_connections} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            peer_a_id varchar(64) NOT NULL COMMENT 'peer_id del primer peer (alfabéticamente menor)',
            peer_b_id varchar(64) NOT NULL COMMENT 'peer_id del segundo peer',
            connection_type ENUM('direct', 'relay', 'bootstrap') DEFAULT 'direct',
            established_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_activity datetime DEFAULT NULL,
            latency_ms int(11) DEFAULT NULL COMMENT 'Latencia promedio en ms',
            bandwidth_score int(11) DEFAULT 0 COMMENT 'Score de calidad de conexión',
            messages_exchanged bigint(20) unsigned DEFAULT 0,
            state ENUM('pending', 'active', 'suspended', 'closed') DEFAULT 'pending',
            handshake_completed tinyint(1) DEFAULT 0,
            shared_secret_hash varchar(64) DEFAULT NULL COMMENT 'Hash del secreto compartido para verificación',
            metadata longtext COMMENT 'JSON: datos de la conexión',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY connection_unique (peer_a_id, peer_b_id),
            KEY peer_a_id (peer_a_id),
            KEY peer_b_id (peer_b_id),
            KEY connection_type (connection_type),
            KEY state (state),
            KEY last_activity (last_activity)
        ) {$charset_collate};";
        dbDelta($sql_mesh_connections);

        // ─── Tabla: Cola de mensajes gossip ───
        $tabla_gossip = $prefix . 'gossip_messages';
        $sql_gossip = "CREATE TABLE {$tabla_gossip} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            message_id varchar(64) NOT NULL COMMENT 'Hash SHA-256 del contenido',
            origin_peer_id varchar(64) NOT NULL COMMENT 'peer_id del creador original',
            message_type ENUM('peer_announce', 'data_update', 'heartbeat', 'crdt_sync', 'peer_exchange', 'content_share', 'alert') DEFAULT 'data_update',
            payload longtext NOT NULL COMMENT 'JSON: contenido del mensaje',
            signature text NOT NULL COMMENT 'Firma Ed25519 del origin_peer',
            ttl int(11) DEFAULT 5 COMMENT 'Time-to-live: saltos restantes',
            hop_count int(11) DEFAULT 0 COMMENT 'Número de saltos realizados',
            propagation_path text COMMENT 'JSON array: peer_ids por los que ha pasado',
            vector_clock text COMMENT 'JSON: vector clock del mensaje',
            priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
            expires_at datetime DEFAULT NULL COMMENT 'Fecha de expiración',
            processed tinyint(1) DEFAULT 0 COMMENT 'Si ya fue procesado localmente',
            forwarded tinyint(1) DEFAULT 0 COMMENT 'Si ya fue reenviado',
            forwarded_to text COMMENT 'JSON array: peer_ids a los que se reenvió',
            received_from_peer_id varchar(64) DEFAULT NULL COMMENT 'peer_id del que lo recibimos',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY message_id (message_id),
            KEY origin_peer_id (origin_peer_id),
            KEY message_type (message_type),
            KEY ttl (ttl),
            KEY priority (priority),
            KEY processed (processed),
            KEY forwarded (forwarded),
            KEY expires_at (expires_at),
            KEY created_at (created_at)
        ) {$charset_collate};";
        dbDelta($sql_gossip);

        // ─── Tabla: Vector clocks para ordenamiento causal ───
        $tabla_vector_clocks = $prefix . 'vector_clocks';
        $sql_vector_clocks = "CREATE TABLE {$tabla_vector_clocks} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            entity_type varchar(50) NOT NULL COMMENT 'Tipo: content, event, collaboration, etc.',
            entity_id varchar(100) NOT NULL COMMENT 'ID de la entidad (puede ser global)',
            clock_data longtext NOT NULL COMMENT 'JSON: {peer_id: version, ...}',
            last_update_peer_id varchar(64) DEFAULT NULL COMMENT 'Último peer que actualizó',
            merged_count int(11) DEFAULT 0 COMMENT 'Veces que se ha hecho merge',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY entity_unique (entity_type, entity_id),
            KEY entity_type (entity_type),
            KEY last_update_peer_id (last_update_peer_id)
        ) {$charset_collate};";
        dbDelta($sql_vector_clocks);

        // ─── Tabla: Estados CRDT para resolución de conflictos ───
        $tabla_crdt = $prefix . 'crdt_state';
        $sql_crdt = "CREATE TABLE {$tabla_crdt} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            doc_id varchar(100) NOT NULL COMMENT 'ID único del documento/entidad',
            doc_type varchar(50) NOT NULL COMMENT 'Tipo: shared_content, event, collaboration, etc.',
            field_name varchar(100) NOT NULL COMMENT 'Campo específico (para granularidad fina)',
            crdt_type ENUM('lww_register', 'or_set', 'g_counter', 'pn_counter', 'mv_register') NOT NULL,
            state_data longtext NOT NULL COMMENT 'JSON: estado serializado del CRDT',
            vector_clock text COMMENT 'JSON: vector clock asociado',
            last_merge_peer_id varchar(64) DEFAULT NULL,
            merge_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY crdt_unique (doc_type, doc_id, field_name),
            KEY doc_type (doc_type),
            KEY doc_id (doc_id),
            KEY crdt_type (crdt_type),
            KEY last_merge_peer_id (last_merge_peer_id)
        ) {$charset_collate};";
        dbDelta($sql_crdt);

        // ─── Tabla: Configuración de peers bootstrap conocidos ───
        $tabla_bootstrap = $prefix . 'bootstrap_nodes';
        $sql_bootstrap = "CREATE TABLE {$tabla_bootstrap} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            peer_id varchar(64) DEFAULT NULL COMMENT 'peer_id si es conocido',
            url varchar(500) NOT NULL COMMENT 'URL del endpoint de la red',
            name varchar(255) DEFAULT '' COMMENT 'Nombre descriptivo',
            priority int(11) DEFAULT 100 COMMENT 'Orden de preferencia (menor = más prioritario)',
            is_official tinyint(1) DEFAULT 0 COMMENT 'Si es nodo oficial de la red',
            is_enabled tinyint(1) DEFAULT 1,
            last_check datetime DEFAULT NULL,
            last_success datetime DEFAULT NULL,
            failures_count int(11) DEFAULT 0,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY url (url),
            KEY peer_id (peer_id),
            KEY is_enabled (is_enabled),
            KEY priority (priority)
        ) {$charset_collate};";
        dbDelta($sql_bootstrap);

        // ─── Tabla: Log de sincronización entre peers ───
        $tabla_sync_log = $prefix . 'sync_log';
        $sql_sync_log = "CREATE TABLE {$tabla_sync_log} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            peer_id varchar(64) NOT NULL,
            sync_type ENUM('full', 'incremental', 'crdt_merge', 'gossip') NOT NULL,
            direction ENUM('push', 'pull', 'bidirectional') DEFAULT 'bidirectional',
            entities_synced int(11) DEFAULT 0,
            conflicts_resolved int(11) DEFAULT 0,
            bytes_transferred bigint(20) unsigned DEFAULT 0,
            duration_ms int(11) DEFAULT 0,
            status ENUM('started', 'completed', 'failed', 'partial') DEFAULT 'started',
            error_message text,
            vector_clock_before text,
            vector_clock_after text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY peer_id (peer_id),
            KEY sync_type (sync_type),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";
        dbDelta($sql_sync_log);

        // Guardar versión de BD
        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);

        // Añadir índices compuestos para optimización
        self::add_composite_indexes();
    }

    /**
     * Añade índices compuestos para optimizar búsquedas geográficas y filtradas
     *
     * @since 1.2.0
     */
    private static function add_composite_indexes() {
        global $wpdb;
        $prefix = $wpdb->prefix . self::TABLE_PREFIX;

        // Definir índices compuestos para optimizar queries frecuentes
        $composite_indexes = [
            // Índices para búsquedas geográficas en nodos
            [
                'table'   => 'nodes',
                'name'    => 'idx_coords_estado',
                'columns' => 'latitud, longitud, estado',
            ],
            // Índices para eventos con filtros de fecha y ubicación
            [
                'table'   => 'events',
                'name'    => 'idx_fecha_geo',
                'columns' => 'fecha_inicio, latitud, longitud',
            ],
            [
                'table'   => 'events',
                'name'    => 'idx_visible_fecha',
                'columns' => 'visible_red, estado, fecha_inicio',
            ],
            // Índices para contenido compartido
            [
                'table'   => 'shared_content',
                'name'    => 'idx_visible_tipo_fecha',
                'columns' => 'visible_red, tipo_contenido, fecha_publicacion',
            ],
            [
                'table'   => 'shared_content',
                'name'    => 'idx_estado_visible',
                'columns' => 'estado, visible_red',
            ],
            // Índices para colaboraciones
            [
                'table'   => 'collaborations',
                'name'    => 'idx_estado_fecha',
                'columns' => 'estado, fecha_limite',
            ],
            [
                'table'   => 'collaborations',
                'name'    => 'idx_tipo_estado',
                'columns' => 'tipo, estado',
            ],
            // Índices para conexiones
            [
                'table'   => 'connections',
                'name'    => 'idx_estado_nivel',
                'columns' => 'estado, nivel',
            ],
            // Índices para mensajes
            [
                'table'   => 'messages',
                'name'    => 'idx_destino_leido',
                'columns' => 'a_nodo_id, leido',
            ],
            // Índices para alertas solidarias
            [
                'table'   => 'solidarity_alerts',
                'name'    => 'idx_estado_urgencia',
                'columns' => 'estado, urgencia',
            ],
            // Índices para banco de tiempo
            [
                'table'   => 'time_offers',
                'name'    => 'idx_tipo_estado_cat',
                'columns' => 'tipo, estado, categoria',
            ],
            // Índice para rotación de API keys expiradas
            [
                'table'   => 'nodes',
                'name'    => 'idx_api_key_expiry',
                'columns' => 'es_nodo_local, estado, api_key_expires_at',
            ],
            // Índices para matches
            [
                'table'   => 'matches',
                'name'    => 'idx_estado_puntuacion',
                'columns' => 'estado, puntuacion',
            ],
            // ═══ Índices P2P/Mesh (v1.5.0) ═══
            // Índices para peers
            [
                'table'   => 'peers',
                'name'    => 'idx_trust_online',
                'columns' => 'trust_level, is_online',
            ],
            [
                'table'   => 'peers',
                'name'    => 'idx_last_seen_online',
                'columns' => 'last_seen, is_online',
            ],
            // Índices para mesh_connections
            [
                'table'   => 'mesh_connections',
                'name'    => 'idx_state_type',
                'columns' => 'state, connection_type',
            ],
            [
                'table'   => 'mesh_connections',
                'name'    => 'idx_activity_state',
                'columns' => 'last_activity, state',
            ],
            // Índices para gossip_messages
            [
                'table'   => 'gossip_messages',
                'name'    => 'idx_pending_forward',
                'columns' => 'processed, forwarded, ttl',
            ],
            [
                'table'   => 'gossip_messages',
                'name'    => 'idx_type_priority',
                'columns' => 'message_type, priority, created_at',
            ],
            [
                'table'   => 'gossip_messages',
                'name'    => 'idx_expiry_processed',
                'columns' => 'expires_at, processed',
            ],
            // Índices para vector_clocks
            [
                'table'   => 'vector_clocks',
                'name'    => 'idx_entity_updated',
                'columns' => 'entity_type, updated_at',
            ],
            // Índices para crdt_state
            [
                'table'   => 'crdt_state',
                'name'    => 'idx_doc_updated',
                'columns' => 'doc_type, doc_id, updated_at',
            ],
            [
                'table'   => 'crdt_state',
                'name'    => 'idx_crdt_type_merge',
                'columns' => 'crdt_type, merge_count',
            ],
            // Índices para sync_log
            [
                'table'   => 'sync_log',
                'name'    => 'idx_peer_status',
                'columns' => 'peer_id, status, created_at',
            ],
        ];

        foreach ($composite_indexes as $index_definition) {
            $table_name = $prefix . $index_definition['table'];
            $index_name = $index_definition['name'];
            $columns_definition = $index_definition['columns'];

            // Verificar si el índice ya existe
            $index_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(1) FROM information_schema.statistics
                 WHERE table_schema = DATABASE()
                   AND table_name = %s
                   AND index_name = %s",
                $table_name,
                $index_name
            ));

            if (!$index_exists) {
                // Crear el índice (silenciar errores si la tabla no existe aún)
                $sql_create_index = "ALTER TABLE {$table_name} ADD INDEX {$index_name} ({$columns_definition})";
                $wpdb->query($sql_create_index);
            }
        }
    }

    /**
     * Comprueba si es necesario actualizar las tablas
     */
    public static function maybe_upgrade() {
        $version_actual = get_option(self::DB_VERSION_OPTION, '0');
        if (version_compare($version_actual, self::DB_VERSION, '<')) {
            self::create_tables();

            // Ejecutar migraciones específicas por versión
            if (version_compare($version_actual, '1.5.0', '<')) {
                self::migrate_to_1_5_0();
            }
        }
    }

    /**
     * Migración a v1.5.0: Crear peers a partir de nodos existentes
     *
     * @since 1.5.0
     */
    private static function migrate_to_1_5_0() {
        global $wpdb;
        $prefix = $wpdb->prefix . self::TABLE_PREFIX;

        // Verificar que existen las tablas necesarias
        $tabla_peers = $prefix . 'peers';
        $tabla_nodes = $prefix . 'nodes';

        $peers_exists = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_peers}'") === $tabla_peers;
        $nodes_exists = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_nodes}'") === $tabla_nodes;

        if (!$peers_exists || !$nodes_exists) {
            return;
        }

        // Crear peer local si no existe
        self::ensure_local_peer_exists();

        // Migrar nodos existentes a peers (sin clave privada, son remotos)
        $nodos_remotos = $wpdb->get_results(
            "SELECT id, site_url, nombre, api_key
             FROM {$tabla_nodes}
             WHERE es_nodo_local = 0 AND estado = 'activo'"
        );

        foreach ($nodos_remotos as $nodo) {
            // Generar peer_id basado en URL si no hay api_key
            $peer_identifier = !empty($nodo->api_key) ? $nodo->api_key : $nodo->site_url;
            $peer_id = hash('sha256', 'legacy_node_' . $peer_identifier);

            // Verificar si ya existe
            $existing_peer = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$tabla_peers} WHERE peer_id = %s OR node_id = %d",
                $peer_id,
                $nodo->id
            ));

            if (!$existing_peer) {
                $wpdb->insert($tabla_peers, [
                    'peer_id'           => $peer_id,
                    'node_id'           => $nodo->id,
                    'public_key_ed25519' => '', // Sin clave pública, es nodo legacy
                    'display_name'      => $nodo->nombre,
                    'site_url'          => $nodo->site_url,
                    'trust_level'       => 'seen',
                    'is_local_peer'     => 0,
                    'is_online'         => 0,
                    'metadata'          => wp_json_encode(['migrated_from_node' => $nodo->id]),
                ]);
            }
        }

        // Log de migración
        error_log('[Flavor Network] Migración a v1.5.0 completada. Peers creados: ' . count($nodos_remotos));
    }

    /**
     * Asegura que existe un peer local con claves criptográficas
     *
     * @since 1.5.0
     * @return array|false Datos del peer local o false en caso de error
     */
    public static function ensure_local_peer_exists() {
        global $wpdb;
        $prefix = $wpdb->prefix . self::TABLE_PREFIX;
        $tabla_peers = $prefix . 'peers';
        $tabla_nodes = $prefix . 'nodes';

        // Buscar peer local existente
        $local_peer = $wpdb->get_row(
            "SELECT * FROM {$tabla_peers} WHERE is_local_peer = 1 LIMIT 1"
        );

        if ($local_peer) {
            return (array) $local_peer;
        }

        // Buscar nodo local existente
        $local_node = $wpdb->get_row(
            "SELECT id, nombre, site_url FROM {$tabla_nodes} WHERE es_nodo_local = 1 LIMIT 1"
        );

        // Generar par de claves Ed25519
        $claves = self::generate_peer_keypair();
        if (!$claves) {
            error_log('[Flavor Network] Error: No se pudieron generar claves Ed25519');
            return false;
        }

        $peer_id = $claves['peer_id'];
        $display_name = $local_node ? $local_node->nombre : get_bloginfo('name');
        $site_url = $local_node ? $local_node->site_url : home_url();

        // Insertar peer local
        $result = $wpdb->insert($tabla_peers, [
            'peer_id'                => $peer_id,
            'node_id'                => $local_node ? $local_node->id : null,
            'public_key_ed25519'     => $claves['public_key_base64'],
            'private_key_encrypted'  => $claves['private_key_encrypted'],
            'display_name'           => $display_name,
            'site_url'               => $site_url,
            'capabilities'           => wp_json_encode([
                'gossip'     => true,
                'crdt'       => true,
                'relay'      => false,
                'bootstrap'  => false,
            ]),
            'trust_level'            => 'trusted',
            'is_local_peer'          => 1,
            'is_online'              => 1,
            'vector_clock_version'   => 1,
            'last_seen'              => current_time('mysql'),
            'metadata'               => wp_json_encode([
                'created_version' => self::DB_VERSION,
                'wp_version'      => get_bloginfo('version'),
            ]),
        ]);

        if ($result === false) {
            error_log('[Flavor Network] Error creando peer local: ' . $wpdb->last_error);
            return false;
        }

        error_log('[Flavor Network] Peer local creado con ID: ' . $peer_id);

        return [
            'id'                 => $wpdb->insert_id,
            'peer_id'            => $peer_id,
            'public_key_base64'  => $claves['public_key_base64'],
            'display_name'       => $display_name,
            'site_url'           => $site_url,
        ];
    }

    /**
     * Genera un par de claves Ed25519 para un peer
     *
     * @since 1.5.0
     * @return array|false Claves generadas o false si falla
     */
    private static function generate_peer_keypair() {
        if (!function_exists('sodium_crypto_sign_keypair')) {
            error_log('[Flavor Network] Error: libsodium no disponible');
            return false;
        }

        try {
            // Generar par de claves Ed25519
            $keypair = sodium_crypto_sign_keypair();
            $public_key = sodium_crypto_sign_publickey($keypair);
            $private_key = sodium_crypto_sign_secretkey($keypair);

            // peer_id es el hash SHA-256 de la clave pública
            $peer_id = hash('sha256', $public_key);

            // Cifrar clave privada para almacenamiento
            $private_key_encrypted = self::encrypt_private_key($private_key);

            // Limpiar datos sensibles de memoria
            sodium_memzero($private_key);
            sodium_memzero($keypair);

            return [
                'peer_id'                 => $peer_id,
                'public_key_base64'       => base64_encode($public_key),
                'private_key_encrypted'   => $private_key_encrypted,
            ];
        } catch (Exception $e) {
            error_log('[Flavor Network] Error generando keypair: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cifra una clave privada para almacenamiento seguro
     *
     * @since 1.5.0
     * @param string $private_key Clave privada en bytes
     * @return string Clave cifrada en base64
     */
    private static function encrypt_private_key($private_key) {
        // Obtener o crear clave maestra del servidor
        $master_key = get_option('flavor_mesh_master_key');
        if (!$master_key) {
            $master_key = base64_encode(random_bytes(32));
            update_option('flavor_mesh_master_key', $master_key, false);
        }
        $master_key_bytes = base64_decode($master_key);

        // Generar nonce
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        // Cifrar con XSalsa20-Poly1305
        $ciphertext = sodium_crypto_secretbox($private_key, $nonce, $master_key_bytes);

        // Limpiar clave de memoria
        sodium_memzero($master_key_bytes);

        return base64_encode($nonce . $ciphertext);
    }

    /**
     * Descifra una clave privada almacenada
     *
     * @since 1.5.0
     * @param string $encrypted_key Clave cifrada en base64
     * @return string|false Clave privada en bytes o false si falla
     */
    public static function decrypt_private_key($encrypted_key) {
        $master_key = get_option('flavor_mesh_master_key');
        if (!$master_key) {
            return false;
        }

        try {
            $master_key_bytes = base64_decode($master_key);
            $data = base64_decode($encrypted_key);

            $nonce = substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $ciphertext = substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

            $private_key = sodium_crypto_secretbox_open($ciphertext, $nonce, $master_key_bytes);

            sodium_memzero($master_key_bytes);

            return $private_key;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene el peer local actual
     *
     * @since 1.5.0
     * @return object|null Objeto peer o null si no existe
     */
    public static function get_local_peer() {
        global $wpdb;
        $prefix = $wpdb->prefix . self::TABLE_PREFIX;

        $cached = wp_cache_get('local_peer', 'flavor_mesh');
        if ($cached !== false) {
            return $cached;
        }

        $peer = $wpdb->get_row(
            "SELECT * FROM {$prefix}peers WHERE is_local_peer = 1 LIMIT 1"
        );

        if ($peer) {
            wp_cache_set('local_peer', $peer, 'flavor_mesh', 300);
        }

        return $peer;
    }

    /**
     * Invalida la caché del peer local
     *
     * @since 1.5.0
     */
    public static function invalidate_local_peer_cache() {
        wp_cache_delete('local_peer', 'flavor_mesh');
    }

    /**
     * Obtiene el nombre completo de una tabla de red
     */
    public static function get_table_name($tabla) {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_PREFIX . $tabla;
    }
}
