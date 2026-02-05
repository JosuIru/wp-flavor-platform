<?php
/**
 * Módulo de Podcast para Chat IA - Sistema Completo
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de Podcast - Plataforma de podcasting comunitario completa
 */
class Flavor_Chat_Podcast_Module extends Flavor_Chat_Module_Base {

    /** @var string Versión del módulo */
    const VERSION = '2.0.0';

    /** @var array Categorías de podcast disponibles */
    private $categorias_disponibles = [
        'noticias' => 'Noticias Locales',
        'entrevistas' => 'Entrevistas',
        'historias' => 'Historias del Barrio',
        'debates' => 'Debates Comunitarios',
        'cultura' => 'Cultura y Arte',
        'educacion' => 'Educación',
        'tecnologia' => 'Tecnología',
        'deportes' => 'Deportes',
        'musica' => 'Música',
        'comedia' => 'Comedia',
    ];

    /** @var array Nombres de tablas */
    private $tabla_series;
    private $tabla_episodios;
    private $tabla_suscripciones;
    private $tabla_reproducciones;
    private $tabla_transcripciones;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;

        $this->id = 'podcast';
        $this->name = __('Podcast', 'flavor-chat-ia');
        $this->description = __('Plataforma de podcasting comunitario - crea, publica y escucha episodios de audio.', 'flavor-chat-ia');

        $this->tabla_series = $wpdb->prefix . 'flavor_podcast_series';
        $this->tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
        $this->tabla_suscripciones = $wpdb->prefix . 'flavor_podcast_suscripciones';
        $this->tabla_reproducciones = $wpdb->prefix . 'flavor_podcast_reproducciones';
        $this->tabla_transcripciones = $wpdb->prefix . 'flavor_podcast_transcripciones';

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return Flavor_Chat_Helpers::tabla_existe($this->tabla_series);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Podcast no están creadas. Se crearán automáticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'permite_subir_episodios' => true,
            'requiere_moderacion' => false,
            'duracion_maxima_minutos' => 120,
            'tamano_maximo_mb' => 100,
            'formatos_permitidos' => ['mp3', 'mp4', 'ogg', 'm4a', 'wav'],
            'permite_comentarios' => true,
            'genera_rss' => true,
            'transcripcion_automatica' => false,
            'autoplay_siguiente' => true,
            'mostrar_estadisticas' => true,
            'limite_series_por_usuario' => 5,
            'episodios_por_pagina' => 10,
            'calidad_audio_defecto' => 'alta',
            'rss_items_limite' => 50,
            'imagen_serie_defecto' => '',
            'color_player_primario' => '#6366f1',
            'color_player_secundario' => '#818cf8',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('init', [$this, 'register_rss_feed']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_flavor_podcast_action', [$this, 'handle_ajax_request']);
        add_action('wp_ajax_nopriv_flavor_podcast_action', [$this, 'handle_ajax_request']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_filter('query_vars', [$this, 'add_query_vars']);
    }

    /**
     * Añade variables de consulta para RSS
     */
    public function add_query_vars($vars) {
        $vars[] = 'podcast_feed';
        $vars[] = 'serie_id';
        return $vars;
    }

    /**
     * Registra el feed RSS personalizado
     */
    public function register_rss_feed() {
        add_feed('podcast', [$this, 'render_rss_feed']);
        add_rewrite_rule(
            'podcast/feed/([0-9]+)/?$',
            'index.php?podcast_feed=1&serie_id=$matches[1]',
            'top'
        );
    }

    /**
     * Encola assets CSS y JS
     */
    public function enqueue_assets() {
        $modulo_url = plugin_dir_url(__FILE__);
        $modulo_path = plugin_dir_path(__FILE__);
        $version = self::VERSION;

        if (file_exists($modulo_path . 'assets/css/podcast.css')) {
            wp_enqueue_style(
                'flavor-podcast',
                $modulo_url . 'assets/css/podcast.css',
                [],
                $version
            );
        }

        if (file_exists($modulo_path . 'assets/js/podcast.js')) {
            wp_enqueue_script(
                'flavor-podcast',
                $modulo_url . 'assets/js/podcast.js',
                ['jquery'],
                $version,
                true
            );

            wp_localize_script('flavor-podcast', 'FlavorPodcast', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => rest_url('flavor-podcast/v1/'),
                'nonce' => wp_create_nonce('flavor_podcast_nonce'),
                'strings' => [
                    'play' => __('Reproducir', 'flavor-chat-ia'),
                    'pause' => __('Pausar', 'flavor-chat-ia'),
                    'loading' => __('Cargando...', 'flavor-chat-ia'),
                    'error' => __('Error al cargar el audio', 'flavor-chat-ia'),
                    'subscribe' => __('Suscribirse', 'flavor-chat-ia'),
                    'unsubscribe' => __('Cancelar suscripción', 'flavor-chat-ia'),
                    'subscribed' => __('Suscrito', 'flavor-chat-ia'),
                    'share' => __('Compartir', 'flavor-chat-ia'),
                    'copied' => __('Enlace copiado', 'flavor-chat-ia'),
                    'speed' => __('Velocidad', 'flavor-chat-ia'),
                    'volume' => __('Volumen', 'flavor-chat-ia'),
                ],
                'settings' => [
                    'autoplay' => $this->get_setting('autoplay_siguiente'),
                    'colorPrimario' => $this->get_setting('color_player_primario'),
                    'colorSecundario' => $this->get_setting('color_player_secundario'),
                ],
                'userId' => get_current_user_id(),
            ]);
        }
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_series)) {
            $this->create_tables();
        }
    }

    /**
     * Crea todas las tablas necesarias
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_series = "CREATE TABLE IF NOT EXISTS {$this->tabla_series} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text NOT NULL,
            descripcion_corta varchar(500) DEFAULT NULL,
            autor_id bigint(20) unsigned NOT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            imagen_banner_url varchar(500) DEFAULT NULL,
            categoria varchar(100) DEFAULT NULL,
            subcategoria varchar(100) DEFAULT NULL,
            idioma varchar(10) DEFAULT 'es',
            pais varchar(50) DEFAULT 'ES',
            sitio_web varchar(500) DEFAULT NULL,
            email_contacto varchar(255) DEFAULT NULL,
            estado enum('borrador','publicado','pausado','archivado') DEFAULT 'borrador',
            tipo enum('episodico','serial') DEFAULT 'episodico',
            explicito tinyint(1) DEFAULT 0,
            copyright varchar(255) DEFAULT NULL,
            suscriptores int(11) DEFAULT 0,
            total_episodios int(11) DEFAULT 0,
            total_reproducciones bigint(20) DEFAULT 0,
            duracion_total_segundos bigint(20) DEFAULT 0,
            valoracion_promedio decimal(3,2) DEFAULT 0.00,
            total_valoraciones int(11) DEFAULT 0,
            fecha_ultimo_episodio datetime DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            meta_datos json DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY autor_id (autor_id),
            KEY estado (estado),
            KEY categoria (categoria),
            KEY fecha_ultimo_episodio (fecha_ultimo_episodio),
            FULLTEXT KEY busqueda (titulo, descripcion)
        ) $charset_collate;";

        $sql_episodios = "CREATE TABLE IF NOT EXISTS {$this->tabla_episodios} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            serie_id bigint(20) unsigned NOT NULL,
            temporada int(11) DEFAULT 1,
            numero_episodio int(11) NOT NULL,
            guid varchar(255) NOT NULL,
            titulo varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            descripcion text NOT NULL,
            descripcion_corta varchar(500) DEFAULT NULL,
            notas_episodio text DEFAULT NULL,
            archivo_url varchar(500) NOT NULL,
            archivo_url_alternativo varchar(500) DEFAULT NULL,
            tipo_archivo varchar(50) DEFAULT 'audio/mpeg',
            duracion_segundos int(11) DEFAULT NULL,
            tamano_bytes bigint(20) DEFAULT NULL,
            bitrate int(11) DEFAULT NULL,
            imagen_url varchar(500) DEFAULT NULL,
            estado enum('borrador','publicado','programado','archivado') DEFAULT 'borrador',
            explicito tinyint(1) DEFAULT 0,
            tipo_episodio enum('completo','trailer','bonus') DEFAULT 'completo',
            reproducciones int(11) DEFAULT 0,
            reproducciones_unicas int(11) DEFAULT 0,
            descargas int(11) DEFAULT 0,
            me_gusta int(11) DEFAULT 0,
            comentarios_count int(11) DEFAULT 0,
            tiempo_escucha_total bigint(20) DEFAULT 0,
            porcentaje_completado_promedio decimal(5,2) DEFAULT 0.00,
            fecha_publicacion datetime DEFAULT NULL,
            fecha_programacion datetime DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            meta_datos json DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY guid (guid),
            KEY serie_id (serie_id),
            KEY estado (estado),
            KEY fecha_publicacion (fecha_publicacion),
            KEY temporada_episodio (serie_id, temporada, numero_episodio),
            FULLTEXT KEY busqueda (titulo, descripcion)
        ) $charset_collate;";

        $sql_suscripciones = "CREATE TABLE IF NOT EXISTS {$this->tabla_suscripciones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            serie_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned NOT NULL,
            notificaciones_email tinyint(1) DEFAULT 1,
            notificaciones_push tinyint(1) DEFAULT 1,
            ultimo_episodio_visto bigint(20) unsigned DEFAULT NULL,
            episodios_pendientes int(11) DEFAULT 0,
            fecha_suscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_ultima_actividad datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY serie_usuario (serie_id, usuario_id),
            KEY usuario_id (usuario_id),
            KEY fecha_suscripcion (fecha_suscripcion)
        ) $charset_collate;";

        $sql_reproducciones = "CREATE TABLE IF NOT EXISTS {$this->tabla_reproducciones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            episodio_id bigint(20) unsigned NOT NULL,
            usuario_id bigint(20) unsigned DEFAULT NULL,
            sesion_id varchar(100) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(500) DEFAULT NULL,
            dispositivo varchar(50) DEFAULT NULL,
            plataforma varchar(50) DEFAULT NULL,
            navegador varchar(50) DEFAULT NULL,
            posicion_actual int(11) DEFAULT 0,
            duracion_escuchada int(11) DEFAULT 0,
            porcentaje_completado decimal(5,2) DEFAULT 0.00,
            completado tinyint(1) DEFAULT 0,
            velocidad_reproduccion decimal(3,2) DEFAULT 1.00,
            fuente varchar(50) DEFAULT 'web',
            pais varchar(50) DEFAULT NULL,
            ciudad varchar(100) DEFAULT NULL,
            fecha_inicio datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_ultima_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            fecha_fin datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY episodio_id (episodio_id),
            KEY usuario_id (usuario_id),
            KEY sesion_id (sesion_id),
            KEY fecha_inicio (fecha_inicio),
            KEY episodio_usuario (episodio_id, usuario_id)
        ) $charset_collate;";

        $sql_transcripciones = "CREATE TABLE IF NOT EXISTS {$this->tabla_transcripciones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            episodio_id bigint(20) unsigned NOT NULL,
            idioma varchar(10) DEFAULT 'es',
            tipo enum('auto','manual','ia') DEFAULT 'manual',
            estado enum('pendiente','procesando','completado','error') DEFAULT 'pendiente',
            contenido_texto longtext DEFAULT NULL,
            contenido_srt longtext DEFAULT NULL,
            contenido_vtt longtext DEFAULT NULL,
            contenido_json json DEFAULT NULL,
            palabras_count int(11) DEFAULT 0,
            duracion_procesamiento int(11) DEFAULT NULL,
            confianza_promedio decimal(5,4) DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY episodio_idioma (episodio_id, idioma),
            KEY estado (estado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_series);
        dbDelta($sql_episodios);
        dbDelta($sql_suscripciones);
        dbDelta($sql_reproducciones);
        dbDelta($sql_transcripciones);
    }

    /**
     * Registra los shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('podcast_player', [$this, 'shortcode_player']);
        add_shortcode('podcast_lista_episodios', [$this, 'shortcode_lista_episodios']);
        add_shortcode('podcast_series', [$this, 'shortcode_series']);
        add_shortcode('podcast_suscribirse', [$this, 'shortcode_suscribirse']);
        add_shortcode('podcast_estadisticas', [$this, 'shortcode_estadisticas']);
        add_shortcode('podcast_buscar', [$this, 'shortcode_buscar']);
    }

    /**
     * Shortcode: Player de podcast
     */
    public function shortcode_player($atts) {
        $atributos = shortcode_atts([
            'episodio_id' => 0,
            'serie_id' => 0,
            'autoplay' => false,
            'mostrar_lista' => true,
            'mostrar_descripcion' => true,
            'mostrar_transcripcion' => false,
            'estilo' => 'completo',
            'color' => '',
        ], $atts);

        $episodio_id = absint($atributos['episodio_id']);
        $serie_id = absint($atributos['serie_id']);

        if ($episodio_id > 0) {
            $episodio = $this->obtener_episodio($episodio_id);
            if (!$episodio) {
                return '<p class="flavor-podcast-error">' . __('Episodio no encontrado', 'flavor-chat-ia') . '</p>';
            }
            $serie = $this->obtener_serie($episodio->serie_id);
        } elseif ($serie_id > 0) {
            $serie = $this->obtener_serie($serie_id);
            if (!$serie) {
                return '<p class="flavor-podcast-error">' . __('Serie no encontrada', 'flavor-chat-ia') . '</p>';
            }
            $episodio = $this->obtener_ultimo_episodio($serie_id);
        } else {
            return '<p class="flavor-podcast-error">' . __('Especifica episodio_id o serie_id', 'flavor-chat-ia') . '</p>';
        }

        $color_personalizado = !empty($atributos['color']) ? esc_attr($atributos['color']) : $this->get_setting('color_player_primario');
        $episodios_lista = $atributos['mostrar_lista'] ? $this->obtener_episodios_serie($serie->id, 10) : [];
        $transcripcion = $atributos['mostrar_transcripcion'] && $episodio ? $this->obtener_transcripcion($episodio->id) : null;

        ob_start();
        ?>
        <div class="flavor-podcast-player"
             data-episodio-id="<?php echo $episodio ? esc_attr($episodio->id) : ''; ?>"
             data-serie-id="<?php echo esc_attr($serie->id); ?>"
             data-autoplay="<?php echo $atributos['autoplay'] ? 'true' : 'false'; ?>"
             style="--podcast-color-primary: <?php echo $color_personalizado; ?>">

            <div class="podcast-player-header">
                <div class="podcast-artwork">
                    <?php if ($episodio && $episodio->imagen_url): ?>
                        <img src="<?php echo esc_url($episodio->imagen_url); ?>" alt="<?php echo esc_attr($episodio->titulo); ?>">
                    <?php elseif ($serie->imagen_url): ?>
                        <img src="<?php echo esc_url($serie->imagen_url); ?>" alt="<?php echo esc_attr($serie->titulo); ?>">
                    <?php else: ?>
                        <div class="podcast-artwork-placeholder">
                            <span class="dashicons dashicons-microphone"></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="podcast-info">
                    <h3 class="podcast-serie-titulo"><?php echo esc_html($serie->titulo); ?></h3>
                    <?php if ($episodio): ?>
                        <h4 class="podcast-episodio-titulo">
                            <?php if ($episodio->temporada > 1 || $episodio->numero_episodio > 0): ?>
                                <span class="episodio-numero">S<?php echo $episodio->temporada; ?>E<?php echo $episodio->numero_episodio; ?></span>
                            <?php endif; ?>
                            <?php echo esc_html($episodio->titulo); ?>
                        </h4>
                        <div class="podcast-meta">
                            <span class="podcast-fecha">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo date_i18n(get_option('date_format'), strtotime($episodio->fecha_publicacion)); ?>
                            </span>
                            <span class="podcast-duracion">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo $this->formatear_duracion($episodio->duracion_segundos); ?>
                            </span>
                            <span class="podcast-reproducciones">
                                <span class="dashicons dashicons-controls-play"></span>
                                <?php echo number_format_i18n($episodio->reproducciones); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($episodio): ?>
            <div class="podcast-player-controls">
                <div class="player-progress-container">
                    <div class="player-time-current">0:00</div>
                    <div class="player-progress-bar">
                        <div class="player-progress-loaded"></div>
                        <div class="player-progress-played"></div>
                        <div class="player-progress-handle"></div>
                    </div>
                    <div class="player-time-total"><?php echo $this->formatear_duracion($episodio->duracion_segundos); ?></div>
                </div>

                <div class="player-buttons">
                    <button class="player-btn player-btn-rewind" data-seconds="-15" title="<?php esc_attr_e('Retroceder 15s', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-controls-skipback"></span>
                        <span class="btn-label">15</span>
                    </button>

                    <button class="player-btn player-btn-play" title="<?php esc_attr_e('Reproducir', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-controls-play play-icon"></span>
                        <span class="dashicons dashicons-controls-pause pause-icon" style="display:none;"></span>
                    </button>

                    <button class="player-btn player-btn-forward" data-seconds="30" title="<?php esc_attr_e('Adelantar 30s', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-controls-skipforward"></span>
                        <span class="btn-label">30</span>
                    </button>
                </div>

                <div class="player-secondary-controls">
                    <div class="player-speed-control">
                        <button class="player-btn player-btn-speed" title="<?php esc_attr_e('Velocidad', 'flavor-chat-ia'); ?>">
                            <span class="speed-value">1x</span>
                        </button>
                        <div class="speed-menu" style="display:none;">
                            <button data-speed="0.5">0.5x</button>
                            <button data-speed="0.75">0.75x</button>
                            <button data-speed="1" class="active">1x</button>
                            <button data-speed="1.25">1.25x</button>
                            <button data-speed="1.5">1.5x</button>
                            <button data-speed="2">2x</button>
                        </div>
                    </div>

                    <div class="player-volume-control">
                        <button class="player-btn player-btn-volume" title="<?php esc_attr_e('Volumen', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-controls-volumeon volume-on"></span>
                            <span class="dashicons dashicons-controls-volumeoff volume-off" style="display:none;"></span>
                        </button>
                        <div class="volume-slider-container" style="display:none;">
                            <input type="range" class="volume-slider" min="0" max="100" value="100">
                        </div>
                    </div>

                    <button class="player-btn player-btn-share" title="<?php esc_attr_e('Compartir', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-share"></span>
                    </button>

                    <button class="player-btn player-btn-download" title="<?php esc_attr_e('Descargar', 'flavor-chat-ia'); ?>">
                        <a href="<?php echo esc_url($episodio->archivo_url); ?>" download>
                            <span class="dashicons dashicons-download"></span>
                        </a>
                    </button>
                </div>

                <audio class="podcast-audio" preload="metadata">
                    <source src="<?php echo esc_url($episodio->archivo_url); ?>" type="<?php echo esc_attr($episodio->tipo_archivo); ?>">
                </audio>
            </div>

            <?php if ($atributos['mostrar_descripcion'] && !empty($episodio->descripcion)): ?>
            <div class="podcast-descripcion">
                <h5><?php _e('Descripción', 'flavor-chat-ia'); ?></h5>
                <div class="descripcion-contenido">
                    <?php echo wp_kses_post($episodio->descripcion); ?>
                </div>
                <?php if (!empty($episodio->notas_episodio)): ?>
                <div class="notas-episodio">
                    <h6><?php _e('Notas del episodio', 'flavor-chat-ia'); ?></h6>
                    <?php echo wp_kses_post($episodio->notas_episodio); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($transcripcion && $transcripcion->estado === 'completado'): ?>
            <div class="podcast-transcripcion">
                <h5>
                    <span class="dashicons dashicons-text"></span>
                    <?php _e('Transcripción', 'flavor-chat-ia'); ?>
                </h5>
                <div class="transcripcion-contenido" data-episodio-id="<?php echo $episodio->id; ?>">
                    <?php echo wp_kses_post(nl2br($transcripcion->contenido_texto)); ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($atributos['mostrar_lista'] && !empty($episodios_lista)): ?>
            <div class="podcast-lista-episodios">
                <h5><?php _e('Más episodios', 'flavor-chat-ia'); ?></h5>
                <ul class="episodios-lista">
                    <?php foreach ($episodios_lista as $ep): ?>
                    <li class="episodio-item <?php echo ($episodio && $ep->id === $episodio->id) ? 'activo' : ''; ?>"
                        data-episodio-id="<?php echo esc_attr($ep->id); ?>"
                        data-archivo-url="<?php echo esc_url($ep->archivo_url); ?>">
                        <div class="episodio-play-indicator">
                            <span class="dashicons dashicons-controls-play"></span>
                        </div>
                        <div class="episodio-info">
                            <span class="episodio-titulo"><?php echo esc_html($ep->titulo); ?></span>
                            <span class="episodio-meta">
                                <?php echo $this->formatear_duracion($ep->duracion_segundos); ?> &middot;
                                <?php echo date_i18n('j M Y', strtotime($ep->fecha_publicacion)); ?>
                            </span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Lista de episodios
     */
    public function shortcode_lista_episodios($atts) {
        $atributos = shortcode_atts([
            'serie_id' => 0,
            'limite' => 10,
            'orden' => 'recientes',
            'temporada' => 0,
            'mostrar_player_mini' => true,
            'paginacion' => true,
            'columnas' => 1,
        ], $atts);

        $serie_id = absint($atributos['serie_id']);
        if ($serie_id === 0) {
            return '<p class="flavor-podcast-error">' . __('Especifica serie_id', 'flavor-chat-ia') . '</p>';
        }

        $serie = $this->obtener_serie($serie_id);
        if (!$serie) {
            return '<p class="flavor-podcast-error">' . __('Serie no encontrada', 'flavor-chat-ia') . '</p>';
        }

        $pagina_actual = max(1, absint($_GET['podcast_pagina'] ?? 1));
        $limite = absint($atributos['limite']);
        $offset = ($pagina_actual - 1) * $limite;
        $temporada = absint($atributos['temporada']);

        $episodios = $this->obtener_episodios_serie($serie_id, $limite, $offset, $atributos['orden'], $temporada);
        $total_episodios = $this->contar_episodios_serie($serie_id, $temporada);
        $total_paginas = ceil($total_episodios / $limite);

        ob_start();
        ?>
        <div class="flavor-podcast-lista" data-serie-id="<?php echo esc_attr($serie_id); ?>">
            <div class="lista-header">
                <h3><?php echo esc_html($serie->titulo); ?></h3>
                <p class="lista-meta">
                    <?php printf(__('%d episodios', 'flavor-chat-ia'), $total_episodios); ?>
                    <?php if ($temporada > 0): ?>
                        &middot; <?php printf(__('Temporada %d', 'flavor-chat-ia'), $temporada); ?>
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($this->obtener_temporadas_serie($serie_id) > 1): ?>
            <div class="lista-filtros">
                <select class="filtro-temporada">
                    <option value="0"><?php _e('Todas las temporadas', 'flavor-chat-ia'); ?></option>
                    <?php for ($temporada_num = 1; $temporada_num <= $this->obtener_temporadas_serie($serie_id); $temporada_num++): ?>
                        <option value="<?php echo $temporada_num; ?>" <?php selected($temporada, $temporada_num); ?>>
                            <?php printf(__('Temporada %d', 'flavor-chat-ia'), $temporada_num); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="episodios-grid columnas-<?php echo absint($atributos['columnas']); ?>">
                <?php foreach ($episodios as $episodio): ?>
                <article class="episodio-card" data-episodio-id="<?php echo esc_attr($episodio->id); ?>">
                    <div class="episodio-imagen">
                        <?php if ($episodio->imagen_url): ?>
                            <img src="<?php echo esc_url($episodio->imagen_url); ?>" alt="<?php echo esc_attr($episodio->titulo); ?>">
                        <?php elseif ($serie->imagen_url): ?>
                            <img src="<?php echo esc_url($serie->imagen_url); ?>" alt="<?php echo esc_attr($episodio->titulo); ?>">
                        <?php endif; ?>
                        <?php if ($atributos['mostrar_player_mini']): ?>
                        <button class="episodio-play-btn" data-archivo="<?php echo esc_url($episodio->archivo_url); ?>">
                            <span class="dashicons dashicons-controls-play"></span>
                        </button>
                        <?php endif; ?>
                        <span class="episodio-duracion-badge"><?php echo $this->formatear_duracion($episodio->duracion_segundos); ?></span>
                    </div>
                    <div class="episodio-contenido">
                        <span class="episodio-numero-fecha">
                            <?php if ($episodio->temporada > 0): ?>
                                S<?php echo $episodio->temporada; ?>E<?php echo $episodio->numero_episodio; ?> &middot;
                            <?php endif; ?>
                            <?php echo date_i18n('j M Y', strtotime($episodio->fecha_publicacion)); ?>
                        </span>
                        <h4 class="episodio-titulo">
                            <a href="<?php echo $this->obtener_url_episodio($episodio); ?>">
                                <?php echo esc_html($episodio->titulo); ?>
                            </a>
                        </h4>
                        <p class="episodio-descripcion">
                            <?php echo esc_html(wp_trim_words($episodio->descripcion_corta ?: $episodio->descripcion, 25)); ?>
                        </p>
                        <div class="episodio-stats">
                            <span><span class="dashicons dashicons-controls-play"></span> <?php echo number_format_i18n($episodio->reproducciones); ?></span>
                            <span><span class="dashicons dashicons-heart"></span> <?php echo number_format_i18n($episodio->me_gusta); ?></span>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <?php if ($atributos['paginacion'] && $total_paginas > 1): ?>
            <nav class="podcast-paginacion">
                <?php if ($pagina_actual > 1): ?>
                    <a href="<?php echo add_query_arg('podcast_pagina', $pagina_actual - 1); ?>" class="pagina-anterior">
                        &laquo; <?php _e('Anterior', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>

                <span class="pagina-info">
                    <?php printf(__('Página %d de %d', 'flavor-chat-ia'), $pagina_actual, $total_paginas); ?>
                </span>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="<?php echo add_query_arg('podcast_pagina', $pagina_actual + 1); ?>" class="pagina-siguiente">
                        <?php _e('Siguiente', 'flavor-chat-ia'); ?> &raquo;
                    </a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Grid de series
     */
    public function shortcode_series($atts) {
        $atributos = shortcode_atts([
            'limite' => 12,
            'categoria' => '',
            'orden' => 'recientes',
            'columnas' => 3,
            'mostrar_suscriptores' => true,
            'mostrar_episodios' => true,
        ], $atts);

        $series = $this->obtener_series([
            'limite' => absint($atributos['limite']),
            'categoria' => sanitize_text_field($atributos['categoria']),
            'orden' => sanitize_text_field($atributos['orden']),
        ]);

        ob_start();
        ?>
        <div class="flavor-podcast-series-grid columnas-<?php echo absint($atributos['columnas']); ?>">
            <?php foreach ($series as $serie): ?>
            <article class="serie-card">
                <div class="serie-imagen">
                    <?php if ($serie->imagen_url): ?>
                        <img src="<?php echo esc_url($serie->imagen_url); ?>" alt="<?php echo esc_attr($serie->titulo); ?>">
                    <?php else: ?>
                        <div class="serie-imagen-placeholder">
                            <span class="dashicons dashicons-microphone"></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($serie->categoria): ?>
                        <span class="serie-categoria-badge"><?php echo esc_html($this->categorias_disponibles[$serie->categoria] ?? $serie->categoria); ?></span>
                    <?php endif; ?>
                </div>
                <div class="serie-contenido">
                    <h3 class="serie-titulo">
                        <a href="<?php echo $this->obtener_url_serie($serie); ?>">
                            <?php echo esc_html($serie->titulo); ?>
                        </a>
                    </h3>
                    <p class="serie-descripcion"><?php echo esc_html(wp_trim_words($serie->descripcion_corta ?: $serie->descripcion, 20)); ?></p>
                    <div class="serie-meta">
                        <?php if ($atributos['mostrar_episodios']): ?>
                            <span class="meta-episodios">
                                <span class="dashicons dashicons-playlist-audio"></span>
                                <?php printf(_n('%d episodio', '%d episodios', $serie->total_episodios, 'flavor-chat-ia'), $serie->total_episodios); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($atributos['mostrar_suscriptores']): ?>
                            <span class="meta-suscriptores">
                                <span class="dashicons dashicons-groups"></span>
                                <?php echo number_format_i18n($serie->suscriptores); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Botón de suscripción
     */
    public function shortcode_suscribirse($atts) {
        $atributos = shortcode_atts([
            'serie_id' => 0,
            'estilo' => 'boton',
            'mostrar_contador' => true,
        ], $atts);

        $serie_id = absint($atributos['serie_id']);
        if ($serie_id === 0) {
            return '';
        }

        $serie = $this->obtener_serie($serie_id);
        if (!$serie) {
            return '';
        }

        $usuario_id = get_current_user_id();
        $esta_suscrito = $usuario_id > 0 ? $this->esta_suscrito($usuario_id, $serie_id) : false;

        ob_start();
        ?>
        <div class="flavor-podcast-suscripcion <?php echo esc_attr($atributos['estilo']); ?>" data-serie-id="<?php echo esc_attr($serie_id); ?>">
            <button class="btn-suscribirse <?php echo $esta_suscrito ? 'suscrito' : ''; ?>"
                    <?php echo $usuario_id === 0 ? 'data-requiere-login="true"' : ''; ?>>
                <span class="dashicons <?php echo $esta_suscrito ? 'dashicons-yes' : 'dashicons-plus-alt'; ?>"></span>
                <span class="btn-texto">
                    <?php echo $esta_suscrito ? __('Suscrito', 'flavor-chat-ia') : __('Suscribirse', 'flavor-chat-ia'); ?>
                </span>
            </button>
            <?php if ($atributos['mostrar_contador']): ?>
                <span class="contador-suscriptores"><?php echo number_format_i18n($serie->suscriptores); ?></span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Estadísticas de podcast
     */
    public function shortcode_estadisticas($atts) {
        $atributos = shortcode_atts([
            'serie_id' => 0,
            'periodo' => '30',
        ], $atts);

        $serie_id = absint($atributos['serie_id']);
        $usuario_id = get_current_user_id();

        if ($serie_id === 0 || $usuario_id === 0) {
            return '';
        }

        $serie = $this->obtener_serie($serie_id);
        if (!$serie || $serie->autor_id != $usuario_id) {
            return '<p class="flavor-podcast-error">' . __('No tienes permiso para ver estas estadísticas', 'flavor-chat-ia') . '</p>';
        }

        $estadisticas = $this->obtener_estadisticas_serie($serie_id, absint($atributos['periodo']));

        ob_start();
        ?>
        <div class="flavor-podcast-estadisticas">
            <h3><?php _e('Estadísticas', 'flavor-chat-ia'); ?></h3>
            <div class="estadisticas-grid">
                <div class="stat-card">
                    <span class="stat-valor"><?php echo number_format_i18n($estadisticas['reproducciones_totales']); ?></span>
                    <span class="stat-label"><?php _e('Reproducciones', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-valor"><?php echo number_format_i18n($estadisticas['suscriptores']); ?></span>
                    <span class="stat-label"><?php _e('Suscriptores', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-valor"><?php echo $this->formatear_duracion($estadisticas['tiempo_escucha_total']); ?></span>
                    <span class="stat-label"><?php _e('Tiempo de escucha', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-valor"><?php echo number_format($estadisticas['porcentaje_completado_promedio'], 1); ?>%</span>
                    <span class="stat-label"><?php _e('Tasa de completado', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Buscador de podcasts
     */
    public function shortcode_buscar($atts) {
        $atributos = shortcode_atts([
            'placeholder' => __('Buscar podcasts y episodios...', 'flavor-chat-ia'),
            'mostrar_filtros' => true,
        ], $atts);

        ob_start();
        ?>
        <div class="flavor-podcast-buscador">
            <form class="buscador-form" method="get">
                <div class="buscador-input-wrapper">
                    <span class="dashicons dashicons-search"></span>
                    <input type="search" name="podcast_buscar" class="buscador-input"
                           placeholder="<?php echo esc_attr($atributos['placeholder']); ?>"
                           value="<?php echo esc_attr($_GET['podcast_buscar'] ?? ''); ?>">
                </div>
                <?php if ($atributos['mostrar_filtros']): ?>
                <div class="buscador-filtros">
                    <select name="podcast_categoria">
                        <option value=""><?php _e('Todas las categorías', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($this->categorias_disponibles as $slug => $nombre): ?>
                            <option value="<?php echo esc_attr($slug); ?>" <?php selected($_GET['podcast_categoria'] ?? '', $slug); ?>>
                                <?php echo esc_html($nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="podcast_tipo">
                        <option value=""><?php _e('Series y episodios', 'flavor-chat-ia'); ?></option>
                        <option value="series" <?php selected($_GET['podcast_tipo'] ?? '', 'series'); ?>><?php _e('Solo series', 'flavor-chat-ia'); ?></option>
                        <option value="episodios" <?php selected($_GET['podcast_tipo'] ?? '', 'episodios'); ?>><?php _e('Solo episodios', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
                <?php endif; ?>
                <button type="submit" class="buscador-btn"><?php _e('Buscar', 'flavor-chat-ia'); ?></button>
            </form>
            <div class="buscador-resultados"></div>
        </div>
        <?php
        return ob_get_clean();
    }
