<?php
/**
 * Instalación de tablas para el módulo Kulturaka
 *
 * @package FlavorPlatform
 * @subpackage Modules\Kulturaka
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas de Kulturaka en la base de datos
 *
 * @return void
 */
function flavor_kulturaka_crear_tablas() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla de nodos geográficos (red de espacios/comunidades)
    $tabla_nodos = $wpdb->prefix . 'flavor_kulturaka_nodos';
    $sql_nodos = "CREATE TABLE IF NOT EXISTS $tabla_nodos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(200) NOT NULL,
        slug varchar(200) NOT NULL,
        descripcion text DEFAULT NULL,

        -- Tipo de nodo
        tipo enum('espacio','comunidad','colectivo','festival','red') NOT NULL DEFAULT 'espacio',

        -- Relación polimórfica
        entidad_tipo varchar(50) DEFAULT NULL,
        entidad_id bigint(20) unsigned DEFAULT NULL,

        -- Ubicación geográfica
        direccion varchar(500) DEFAULT NULL,
        ciudad varchar(100) DEFAULT NULL,
        provincia varchar(100) DEFAULT NULL,
        pais varchar(100) DEFAULT 'España',
        codigo_postal varchar(20) DEFAULT NULL,
        latitud decimal(10,8) DEFAULT NULL,
        longitud decimal(11,8) DEFAULT NULL,
        zona_horaria varchar(50) DEFAULT 'Europe/Madrid',

        -- Capacidad y características
        aforo_maximo int(11) DEFAULT NULL,
        metros_cuadrados decimal(10,2) DEFAULT NULL,
        caracteristicas json DEFAULT NULL,
        equipamiento json DEFAULT NULL,

        -- Servicios ofrecidos
        acepta_propuestas tinyint(1) NOT NULL DEFAULT 1,
        acepta_residencias tinyint(1) NOT NULL DEFAULT 0,
        tiene_backline tinyint(1) NOT NULL DEFAULT 0,
        tiene_camerinos tinyint(1) NOT NULL DEFAULT 0,
        accesible tinyint(1) NOT NULL DEFAULT 0,

        -- Economía
        acepta_eur tinyint(1) NOT NULL DEFAULT 1,
        acepta_semilla tinyint(1) NOT NULL DEFAULT 1,
        acepta_hours tinyint(1) NOT NULL DEFAULT 1,
        porcentaje_taquilla decimal(5,2) DEFAULT 20.00,
        cache_minimo decimal(10,2) DEFAULT NULL,

        -- Distribución por defecto (estilo Kulturaka)
        distribucion_artista decimal(5,2) DEFAULT 70.00,
        distribucion_espacio decimal(5,2) DEFAULT 10.00,
        distribucion_comunidad decimal(5,2) DEFAULT 10.00,
        distribucion_plataforma decimal(5,2) DEFAULT 5.00,
        distribucion_emergencia decimal(5,2) DEFAULT 5.00,

        -- Contacto
        email varchar(200) DEFAULT NULL,
        telefono varchar(50) DEFAULT NULL,
        web varchar(500) DEFAULT NULL,
        redes_sociales json DEFAULT NULL,

        -- Visual
        imagen_principal varchar(500) DEFAULT NULL,
        galeria json DEFAULT NULL,
        color_marca varchar(7) DEFAULT '#ec4899',

        -- Métricas
        eventos_realizados int(11) NOT NULL DEFAULT 0,
        artistas_apoyados int(11) NOT NULL DEFAULT 0,
        fondos_recaudados decimal(12,2) NOT NULL DEFAULT 0.00,
        indice_cooperacion decimal(5,2) NOT NULL DEFAULT 0.00,

        -- Estado
        estado enum('activo','pausado','cerrado','pendiente') NOT NULL DEFAULT 'activo',
        verificado tinyint(1) NOT NULL DEFAULT 0,
        destacado tinyint(1) NOT NULL DEFAULT 0,

        -- Conexiones con otros nodos
        nodos_conectados json DEFAULT NULL,

        -- Admin
        admin_id bigint(20) unsigned DEFAULT NULL,
        admins json DEFAULT NULL,

        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY tipo (tipo),
        KEY estado (estado),
        KEY ciudad (ciudad),
        KEY entidad (entidad_tipo, entidad_id),
        KEY coordenadas (latitud, longitud),
        KEY verificado (verificado),
        KEY destacado (destacado),
        FULLTEXT KEY busqueda (nombre, descripcion, ciudad)
    ) $charset_collate;";

    // Tabla de agradecimientos (muro de gratitud)
    $tabla_agradecimientos = $wpdb->prefix . 'flavor_kulturaka_agradecimientos';
    $sql_agradecimientos = "CREATE TABLE IF NOT EXISTS $tabla_agradecimientos (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

        -- Quien agradece
        usuario_id bigint(20) unsigned NOT NULL,
        usuario_tipo enum('artista','espacio','comunidad','persona') NOT NULL DEFAULT 'persona',

        -- A quién agradece
        destinatario_id bigint(20) unsigned DEFAULT NULL,
        destinatario_tipo enum('artista','espacio','comunidad','persona','evento') NOT NULL DEFAULT 'persona',
        destinatario_nombre varchar(200) DEFAULT NULL,

        -- Contexto del agradecimiento
        evento_id bigint(20) unsigned DEFAULT NULL,
        crowdfunding_id bigint(20) unsigned DEFAULT NULL,
        nodo_id bigint(20) unsigned DEFAULT NULL,

        -- Contenido
        mensaje text NOT NULL,
        tipo enum('gracias','apoyo','colaboracion','inspiracion','mencion','otro') NOT NULL DEFAULT 'gracias',

        -- Valor asociado (opcional)
        importe decimal(10,2) DEFAULT NULL,
        moneda enum('eur','semilla','hours') DEFAULT NULL,

        -- Visual
        emoji varchar(10) DEFAULT '❤️',
        imagen varchar(500) DEFAULT NULL,

        -- Visibilidad
        publico tinyint(1) NOT NULL DEFAULT 1,
        destacado tinyint(1) NOT NULL DEFAULT 0,

        -- Reacciones
        likes_count int(11) NOT NULL DEFAULT 0,

        -- Moderación
        estado enum('activo','oculto','reportado') NOT NULL DEFAULT 'activo',

        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        KEY usuario_id (usuario_id),
        KEY destinatario (destinatario_tipo, destinatario_id),
        KEY evento_id (evento_id),
        KEY nodo_id (nodo_id),
        KEY tipo (tipo),
        KEY publico (publico),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Tabla de propuestas artista → espacio
    $tabla_propuestas = $wpdb->prefix . 'flavor_kulturaka_propuestas';
    $sql_propuestas = "CREATE TABLE IF NOT EXISTS $tabla_propuestas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

        -- Artista que propone
        artista_id bigint(20) unsigned NOT NULL,
        artista_user_id bigint(20) unsigned NOT NULL,

        -- Espacio/nodo al que propone
        nodo_id bigint(20) unsigned NOT NULL,

        -- Detalles de la propuesta
        titulo varchar(255) NOT NULL,
        descripcion text NOT NULL,
        tipo_evento enum('concierto','teatro','danza','exposicion','taller','conferencia','otro') NOT NULL DEFAULT 'concierto',

        -- Fechas propuestas
        fechas_propuestas json DEFAULT NULL,
        duracion_minutos int(11) DEFAULT 90,
        necesidades_tecnicas text DEFAULT NULL,

        -- Económico
        modelo_economico enum('taquilla','cache','mixto','gratuito','a_voluntad') NOT NULL DEFAULT 'taquilla',
        cache_solicitado decimal(10,2) DEFAULT NULL,
        porcentaje_taquilla decimal(5,2) DEFAULT NULL,
        acepta_semilla tinyint(1) NOT NULL DEFAULT 1,
        acepta_hours tinyint(1) NOT NULL DEFAULT 1,

        -- Material promocional
        imagen varchar(500) DEFAULT NULL,
        video varchar(500) DEFAULT NULL,
        enlaces json DEFAULT NULL,
        dossier varchar(500) DEFAULT NULL,

        -- Estado
        estado enum('enviada','vista','negociando','aceptada','rechazada','cancelada','realizada') NOT NULL DEFAULT 'enviada',

        -- Respuesta del espacio
        respuesta text DEFAULT NULL,
        fecha_respuesta datetime DEFAULT NULL,
        respondido_por bigint(20) unsigned DEFAULT NULL,

        -- Si se acepta, referencia al evento creado
        evento_id bigint(20) unsigned DEFAULT NULL,

        -- Historial de negociación
        conversacion json DEFAULT NULL,

        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        KEY artista_id (artista_id),
        KEY nodo_id (nodo_id),
        KEY estado (estado),
        KEY tipo_evento (tipo_evento),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Tabla de métricas de impacto
    $tabla_metricas = $wpdb->prefix . 'flavor_kulturaka_metricas';
    $sql_metricas = "CREATE TABLE IF NOT EXISTS $tabla_metricas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

        -- Entidad medida
        entidad_tipo enum('artista','espacio','comunidad','evento','crowdfunding') NOT NULL,
        entidad_id bigint(20) unsigned NOT NULL,

        -- Período
        periodo enum('dia','semana','mes','trimestre','anual','total') NOT NULL DEFAULT 'mes',
        fecha_inicio date NOT NULL,
        fecha_fin date DEFAULT NULL,

        -- Métricas de eventos
        eventos_realizados int(11) NOT NULL DEFAULT 0,
        asistentes_total int(11) NOT NULL DEFAULT 0,
        asistentes_promedio decimal(10,2) NOT NULL DEFAULT 0.00,

        -- Métricas económicas
        ingresos_eur decimal(12,2) NOT NULL DEFAULT 0.00,
        ingresos_semilla decimal(12,2) NOT NULL DEFAULT 0.00,
        ingresos_hours decimal(10,2) NOT NULL DEFAULT 0.00,

        -- Distribución de ingresos
        distribuido_artistas decimal(12,2) NOT NULL DEFAULT 0.00,
        distribuido_espacios decimal(12,2) NOT NULL DEFAULT 0.00,
        distribuido_comunidades decimal(12,2) NOT NULL DEFAULT 0.00,
        distribuido_emergencia decimal(12,2) NOT NULL DEFAULT 0.00,

        -- Métricas de colaboración
        colaboraciones int(11) NOT NULL DEFAULT 0,
        artistas_nuevos int(11) NOT NULL DEFAULT 0,
        espacios_nuevos int(11) NOT NULL DEFAULT 0,

        -- Métricas sociales
        agradecimientos_recibidos int(11) NOT NULL DEFAULT 0,
        agradecimientos_enviados int(11) NOT NULL DEFAULT 0,

        -- Índices calculados
        indice_impacto decimal(5,2) NOT NULL DEFAULT 0.00,
        indice_cooperacion decimal(5,2) NOT NULL DEFAULT 0.00,
        indice_sostenibilidad decimal(5,2) NOT NULL DEFAULT 0.00,

        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        UNIQUE KEY entidad_periodo (entidad_tipo, entidad_id, periodo, fecha_inicio),
        KEY entidad (entidad_tipo, entidad_id),
        KEY periodo (periodo),
        KEY fecha_inicio (fecha_inicio)
    ) $charset_collate;";

    // Tabla de conexiones entre nodos (red descentralizada)
    $tabla_conexiones = $wpdb->prefix . 'flavor_kulturaka_conexiones';
    $sql_conexiones = "CREATE TABLE IF NOT EXISTS $tabla_conexiones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

        nodo_origen_id bigint(20) unsigned NOT NULL,
        nodo_destino_id bigint(20) unsigned NOT NULL,

        -- Tipo de conexión
        tipo enum('colaboracion','hermandad','intercambio','federacion','mentoría') NOT NULL DEFAULT 'colaboracion',

        -- Estado
        estado enum('pendiente','activa','pausada','finalizada') NOT NULL DEFAULT 'pendiente',

        -- Detalles
        descripcion text DEFAULT NULL,
        acuerdos json DEFAULT NULL,

        -- Métricas de la conexión
        eventos_conjuntos int(11) NOT NULL DEFAULT 0,
        artistas_intercambiados int(11) NOT NULL DEFAULT 0,
        recursos_compartidos decimal(10,2) NOT NULL DEFAULT 0.00,

        -- Fechas
        fecha_inicio date DEFAULT NULL,
        fecha_fin date DEFAULT NULL,

        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        UNIQUE KEY conexion_unica (nodo_origen_id, nodo_destino_id),
        KEY nodo_origen_id (nodo_origen_id),
        KEY nodo_destino_id (nodo_destino_id),
        KEY tipo (tipo),
        KEY estado (estado)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_nodos);
    dbDelta($sql_agradecimientos);
    dbDelta($sql_propuestas);
    dbDelta($sql_metricas);
    dbDelta($sql_conexiones);

    update_option('flavor_kulturaka_db_version', '1.0.0');
}

/**
 * Elimina las tablas de Kulturaka
 *
 * @return void
 */
function flavor_kulturaka_eliminar_tablas() {
    global $wpdb;

    $tablas = [
        $wpdb->prefix . 'flavor_kulturaka_nodos',
        $wpdb->prefix . 'flavor_kulturaka_agradecimientos',
        $wpdb->prefix . 'flavor_kulturaka_propuestas',
        $wpdb->prefix . 'flavor_kulturaka_metricas',
        $wpdb->prefix . 'flavor_kulturaka_conexiones',
    ];

    foreach ($tablas as $tabla) {
        $wpdb->query("DROP TABLE IF EXISTS $tabla");
    }

    delete_option('flavor_kulturaka_db_version');
}
