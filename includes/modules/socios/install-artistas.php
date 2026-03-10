<?php
/**
 * Instalación de tablas para perfiles de Artista (extensión Kulturaka)
 *
 * @package FlavorChatIA
 * @subpackage Modules\Socios\Artistas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crea las tablas de perfiles de artista en la base de datos
 *
 * @return void
 */
function flavor_socios_artistas_crear_tablas() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tabla de perfiles de artista (extensión de socios)
    $tabla_artistas = $wpdb->prefix . 'flavor_socios_artistas';
    $sql_artistas = "CREATE TABLE IF NOT EXISTS $tabla_artistas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        socio_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,

        -- Identidad artística
        nombre_artistico varchar(200) NOT NULL,
        slug varchar(200) NOT NULL,
        bio_artistica text DEFAULT NULL,
        bio_corta varchar(500) DEFAULT NULL,

        -- Clasificación
        disciplinas json DEFAULT NULL,
        generos json DEFAULT NULL,
        idiomas json DEFAULT NULL,

        -- Portfolio
        portfolio_videos json DEFAULT NULL,
        portfolio_audio json DEFAULT NULL,
        portfolio_imagenes json DEFAULT NULL,
        portfolio_prensa json DEFAULT NULL,
        video_destacado varchar(500) DEFAULT NULL,
        audio_destacado varchar(500) DEFAULT NULL,

        -- Redes sociales
        website varchar(500) DEFAULT NULL,
        instagram varchar(200) DEFAULT NULL,
        bandcamp varchar(200) DEFAULT NULL,
        spotify varchar(200) DEFAULT NULL,
        youtube varchar(200) DEFAULT NULL,
        soundcloud varchar(200) DEFAULT NULL,
        tiktok varchar(200) DEFAULT NULL,

        -- Nivel y reputación
        nivel_artista enum('emergente','establecido','profesional','consagrado') DEFAULT 'emergente',
        eventos_realizados int(11) NOT NULL DEFAULT 0,
        audiencia_total int(11) NOT NULL DEFAULT 0,
        rating decimal(3,2) DEFAULT NULL,
        total_valoraciones int(11) NOT NULL DEFAULT 0,

        -- Verificación y certificación
        verificado tinyint(1) NOT NULL DEFAULT 0,
        verificado_por bigint(20) unsigned DEFAULT NULL,
        verificado_at datetime DEFAULT NULL,
        votos_verificacion int(11) NOT NULL DEFAULT 0,

        -- Económico (integración con sistema)
        ganancias_eur decimal(12,2) NOT NULL DEFAULT 0.00,
        ganancias_semilla decimal(12,2) NOT NULL DEFAULT 0.00,
        ganancias_hours decimal(10,2) NOT NULL DEFAULT 0.00,

        -- Disponibilidad para giras
        disponible_giras tinyint(1) NOT NULL DEFAULT 1,
        radio_actuacion_km int(11) DEFAULT NULL,
        requiere_cachette tinyint(1) NOT NULL DEFAULT 0,
        cache_minimo decimal(10,2) DEFAULT NULL,
        acepta_semilla tinyint(1) NOT NULL DEFAULT 1,
        acepta_hours tinyint(1) NOT NULL DEFAULT 1,

        -- Requisitos técnicos
        rider_tecnico text DEFAULT NULL,
        necesidades_escenario text DEFAULT NULL,
        duracion_tipica_min int(11) DEFAULT 60,

        -- Estadísticas
        seguidores_count int(11) NOT NULL DEFAULT 0,
        siguiendo_count int(11) NOT NULL DEFAULT 0,
        visualizaciones_perfil int(11) NOT NULL DEFAULT 0,

        -- Metadatos
        destacado tinyint(1) NOT NULL DEFAULT 0,
        activo tinyint(1) NOT NULL DEFAULT 1,
        metadata json DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        UNIQUE KEY socio_id (socio_id),
        UNIQUE KEY slug (slug),
        KEY usuario_id (usuario_id),
        KEY nivel_artista (nivel_artista),
        KEY verificado (verificado),
        KEY activo (activo),
        KEY rating (rating),
        FULLTEXT KEY busqueda_artista (nombre_artistico, bio_artistica, bio_corta)
    ) $charset_collate;";

    // Tabla de disciplinas artísticas
    $tabla_disciplinas = $wpdb->prefix . 'flavor_artistas_disciplinas';
    $sql_disciplinas = "CREATE TABLE IF NOT EXISTS $tabla_disciplinas (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        nombre varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        descripcion text DEFAULT NULL,
        icono varchar(50) DEFAULT 'dashicons-art',
        color varchar(7) DEFAULT '#8b5cf6',
        categoria enum('musica','teatro','danza','artes_visuales','audiovisual','literatura','otro') DEFAULT 'otro',
        orden int(11) NOT NULL DEFAULT 0,
        activa tinyint(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY categoria (categoria),
        KEY activa (activa)
    ) $charset_collate;";

    // Tabla de valoraciones de artistas
    $tabla_valoraciones = $wpdb->prefix . 'flavor_artistas_valoraciones';
    $sql_valoraciones = "CREATE TABLE IF NOT EXISTS $tabla_valoraciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        artista_id bigint(20) unsigned NOT NULL,
        usuario_id bigint(20) unsigned NOT NULL,
        evento_id bigint(20) unsigned DEFAULT NULL,

        puntuacion tinyint(1) NOT NULL,
        comentario text DEFAULT NULL,

        -- Aspectos específicos
        puntualidad tinyint(1) DEFAULT NULL,
        calidad_artistica tinyint(1) DEFAULT NULL,
        profesionalidad tinyint(1) DEFAULT NULL,
        trato_publico tinyint(1) DEFAULT NULL,

        visible tinyint(1) NOT NULL DEFAULT 1,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        UNIQUE KEY valoracion_unica (artista_id, usuario_id, evento_id),
        KEY artista_id (artista_id),
        KEY usuario_id (usuario_id),
        KEY evento_id (evento_id),
        KEY puntuacion (puntuacion)
    ) $charset_collate;";

    // Tabla de seguidores de artistas
    $tabla_seguidores = $wpdb->prefix . 'flavor_artistas_seguidores';
    $sql_seguidores = "CREATE TABLE IF NOT EXISTS $tabla_seguidores (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        artista_id bigint(20) unsigned NOT NULL,
        seguidor_id bigint(20) unsigned NOT NULL,
        notificaciones tinyint(1) NOT NULL DEFAULT 1,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        UNIQUE KEY seguimiento_unico (artista_id, seguidor_id),
        KEY artista_id (artista_id),
        KEY seguidor_id (seguidor_id)
    ) $charset_collate;";

    // Tabla de colaboraciones entre artistas
    $tabla_colaboraciones = $wpdb->prefix . 'flavor_artistas_colaboraciones';
    $sql_colaboraciones = "CREATE TABLE IF NOT EXISTS $tabla_colaboraciones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        artista_id bigint(20) unsigned NOT NULL,
        colaborador_id bigint(20) unsigned NOT NULL,
        tipo enum('proyecto','evento','grabacion','gira','otro') DEFAULT 'proyecto',
        descripcion text DEFAULT NULL,
        evento_id bigint(20) unsigned DEFAULT NULL,
        estado enum('propuesta','confirmada','completada','cancelada') DEFAULT 'propuesta',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,

        PRIMARY KEY (id),
        UNIQUE KEY colaboracion_unica (artista_id, colaborador_id, evento_id),
        KEY artista_id (artista_id),
        KEY colaborador_id (colaborador_id),
        KEY estado (estado)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_artistas);
    dbDelta($sql_disciplinas);
    dbDelta($sql_valoraciones);
    dbDelta($sql_seguidores);
    dbDelta($sql_colaboraciones);

    // Insertar disciplinas por defecto
    flavor_artistas_insertar_disciplinas_default();

    // Insertar tipos de socio artista y espacio
    flavor_artistas_insertar_tipos_socio();

    update_option('flavor_socios_artistas_db_version', '1.0.0');
}

/**
 * Inserta disciplinas artísticas por defecto
 *
 * @return void
 */
function flavor_artistas_insertar_disciplinas_default() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_artistas_disciplinas';

    $existe = $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
    if ($existe > 0) {
        return;
    }

    $disciplinas = [
        // Música
        ['nombre' => 'Concierto', 'slug' => 'concierto', 'categoria' => 'musica', 'icono' => 'dashicons-format-audio', 'color' => '#ec4899'],
        ['nombre' => 'DJ Set', 'slug' => 'dj-set', 'categoria' => 'musica', 'icono' => 'dashicons-controls-volumeon', 'color' => '#8b5cf6'],
        ['nombre' => 'Sesión Acústica', 'slug' => 'acustico', 'categoria' => 'musica', 'icono' => 'dashicons-playlist-audio', 'color' => '#f59e0b'],

        // Teatro
        ['nombre' => 'Teatro', 'slug' => 'teatro', 'categoria' => 'teatro', 'icono' => 'dashicons-tickets-alt', 'color' => '#ef4444'],
        ['nombre' => 'Monólogo', 'slug' => 'monologo', 'categoria' => 'teatro', 'icono' => 'dashicons-microphone', 'color' => '#10b981'],
        ['nombre' => 'Improvisación', 'slug' => 'improvisacion', 'categoria' => 'teatro', 'icono' => 'dashicons-randomize', 'color' => '#06b6d4'],

        // Danza
        ['nombre' => 'Danza Contemporánea', 'slug' => 'danza-contemporanea', 'categoria' => 'danza', 'icono' => 'dashicons-superhero', 'color' => '#d946ef'],
        ['nombre' => 'Danza Tradicional', 'slug' => 'danza-tradicional', 'categoria' => 'danza', 'icono' => 'dashicons-universal-access', 'color' => '#84cc16'],

        // Artes visuales
        ['nombre' => 'Exposición', 'slug' => 'exposicion', 'categoria' => 'artes_visuales', 'icono' => 'dashicons-art', 'color' => '#3b82f6'],
        ['nombre' => 'Performance', 'slug' => 'performance', 'categoria' => 'artes_visuales', 'icono' => 'dashicons-visibility', 'color' => '#f97316'],
        ['nombre' => 'Arte Urbano', 'slug' => 'arte-urbano', 'categoria' => 'artes_visuales', 'icono' => 'dashicons-format-image', 'color' => '#14b8a6'],

        // Audiovisual
        ['nombre' => 'Cine', 'slug' => 'cine', 'categoria' => 'audiovisual', 'icono' => 'dashicons-video-alt3', 'color' => '#6366f1'],
        ['nombre' => 'VJ / Visuales', 'slug' => 'vj-visuales', 'categoria' => 'audiovisual', 'icono' => 'dashicons-desktop', 'color' => '#a855f7'],

        // Literatura
        ['nombre' => 'Poesía', 'slug' => 'poesia', 'categoria' => 'literatura', 'icono' => 'dashicons-book', 'color' => '#0ea5e9'],
        ['nombre' => 'Narración Oral', 'slug' => 'narracion-oral', 'categoria' => 'literatura', 'icono' => 'dashicons-format-quote', 'color' => '#22c55e'],

        // Talleres
        ['nombre' => 'Taller', 'slug' => 'taller', 'categoria' => 'otro', 'icono' => 'dashicons-hammer', 'color' => '#64748b'],
        ['nombre' => 'Masterclass', 'slug' => 'masterclass', 'categoria' => 'otro', 'icono' => 'dashicons-welcome-learn-more', 'color' => '#0891b2'],
    ];

    foreach ($disciplinas as $index => $disciplina) {
        $wpdb->insert($tabla, array_merge($disciplina, [
            'orden' => $index,
            'activa' => 1,
        ]));
    }
}

/**
 * Inserta tipos de socio para artistas y espacios
 *
 * @return void
 */
function flavor_artistas_insertar_tipos_socio() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_socios_tipos';

    // Verificar si ya existe el tipo artista
    $existe_artista = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla WHERE slug = %s",
        'artista'
    ));

    if (!$existe_artista) {
        $wpdb->insert($tabla, [
            'nombre' => 'Artista / Creador',
            'slug' => 'artista',
            'descripcion' => 'Perfil para artistas, músicos, performers y creadores culturales.',
            'cuota_mensual' => 0.00,
            'cuota_anual' => 0.00,
            'cuota_inscripcion' => 0.00,
            'es_gratuito' => 1,
            'beneficios' => 'Perfil público, Portfolio, Propuestas a espacios, Red de artistas, Crowdfunding',
            'color' => '#ec4899',
            'icono' => 'dashicons-art',
            'requiere_aprobacion' => 1,
            'visible' => 1,
            'activo' => 1,
            'orden' => 10,
        ]);
    }

    // Verificar si ya existe el tipo espacio
    $existe_espacio = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla WHERE slug = %s",
        'espacio-cultural'
    ));

    if (!$existe_espacio) {
        $wpdb->insert($tabla, [
            'nombre' => 'Espacio Cultural',
            'slug' => 'espacio-cultural',
            'descripcion' => 'Perfil para gaztetxes, salas de conciertos, teatros y espacios culturales.',
            'cuota_mensual' => 0.00,
            'cuota_anual' => 0.00,
            'cuota_inscripcion' => 0.00,
            'es_gratuito' => 1,
            'beneficios' => 'Calendario público, Recibir propuestas, Red de espacios, Gestión de eventos',
            'color' => '#3b82f6',
            'icono' => 'dashicons-building',
            'requiere_aprobacion' => 1,
            'visible' => 1,
            'activo' => 1,
            'orden' => 11,
        ]);
    }
}

/**
 * Elimina las tablas de artistas
 *
 * @return void
 */
function flavor_socios_artistas_eliminar_tablas() {
    global $wpdb;

    $tablas = [
        $wpdb->prefix . 'flavor_socios_artistas',
        $wpdb->prefix . 'flavor_artistas_disciplinas',
        $wpdb->prefix . 'flavor_artistas_valoraciones',
        $wpdb->prefix . 'flavor_artistas_seguidores',
        $wpdb->prefix . 'flavor_artistas_colaboraciones',
    ];

    foreach ($tablas as $tabla) {
        $wpdb->query("DROP TABLE IF EXISTS $tabla");
    }

    delete_option('flavor_socios_artistas_db_version');
}
