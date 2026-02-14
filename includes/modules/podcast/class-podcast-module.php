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

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;

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
        $this->name = 'Podcast'; // Translation loaded on init
        $this->description = 'Plataforma de podcasting comunitario - crea, publica y escucha episodios de audio.'; // Translation loaded on init

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
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
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
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('init', [$this, 'register_rss_feed']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_flavor_podcast_action', [$this, 'handle_ajax_request']);
        add_action('wp_ajax_nopriv_flavor_podcast_action', [$this, 'handle_ajax_request']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_filter('query_vars', [$this, 'add_query_vars']);

        // Registrar en panel de administración unificado
        $this->registrar_en_panel_unificado();
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
                            <span class="speed-value"><?php echo esc_html__('1x', 'flavor-chat-ia'); ?></span>
                        </button>
                        <div class="speed-menu" style="display:none;">
                            <button data-speed="0.5"><?php echo esc_html__('0.5x', 'flavor-chat-ia'); ?></button>
                            <button data-speed="0.75"><?php echo esc_html__('0.75x', 'flavor-chat-ia'); ?></button>
                            <button data-speed="1" class="active"><?php echo esc_html__('1x', 'flavor-chat-ia'); ?></button>
                            <button data-speed="1.25"><?php echo esc_html__('1.25x', 'flavor-chat-ia'); ?></button>
                            <button data-speed="1.5"><?php echo esc_html__('1.5x', 'flavor-chat-ia'); ?></button>
                            <button data-speed="2"><?php echo esc_html__('2x', 'flavor-chat-ia'); ?></button>
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
                        <option value="<?php echo esc_attr__('series', 'flavor-chat-ia'); ?>" <?php selected($_GET['podcast_tipo'] ?? '', 'series'); ?>><?php _e('Solo series', 'flavor-chat-ia'); ?></option>
                        <option value="<?php echo esc_attr__('episodios', 'flavor-chat-ia'); ?>" <?php selected($_GET['podcast_tipo'] ?? '', 'episodios'); ?>><?php _e('Solo episodios', 'flavor-chat-ia'); ?></option>
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

    /**
     * Manejador AJAX principal
     */
    public function handle_ajax_request() {
        check_ajax_referer('flavor_podcast_nonce', 'nonce');

        $accion = sanitize_text_field($_POST['podcast_action'] ?? '');
        $metodo_handler = 'ajax_' . $accion;

        if (method_exists($this, $metodo_handler)) {
            $resultado = $this->$metodo_handler($_POST);
            wp_send_json($resultado);
        }

        wp_send_json_error(['message' => __('Acción no válida', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Registrar reproducción
     */
    private function ajax_registrar_reproduccion($datos) {
        global $wpdb;

        $episodio_id = absint($datos['episodio_id'] ?? 0);
        if ($episodio_id === 0) {
            return ['success' => false, 'message' => __('ID de episodio requerido', 'flavor-chat-ia')];
        }

        $usuario_id = get_current_user_id();
        $sesion_id = sanitize_text_field($datos['sesion_id'] ?? $this->generar_sesion_id());
        $posicion = absint($datos['posicion'] ?? 0);

        $reproduccion_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT id, posicion_actual, duracion_escuchada FROM {$this->tabla_reproducciones}
             WHERE episodio_id = %d AND (usuario_id = %d OR sesion_id = %s)
             AND fecha_fin IS NULL
             ORDER BY fecha_inicio DESC LIMIT 1",
            $episodio_id,
            $usuario_id,
            $sesion_id
        ));

        if ($reproduccion_existente) {
            $wpdb->update(
                $this->tabla_reproducciones,
                [
                    'posicion_actual' => $posicion,
                    'duracion_escuchada' => $reproduccion_existente->duracion_escuchada + max(0, $posicion - $reproduccion_existente->posicion_actual),
                ],
                ['id' => $reproduccion_existente->id]
            );
            $reproduccion_id = $reproduccion_existente->id;
        } else {
            $episodio = $this->obtener_episodio($episodio_id);
            $es_unica = !$this->tiene_reproduccion_previa($episodio_id, $usuario_id, $sesion_id);

            $wpdb->insert($this->tabla_reproducciones, [
                'episodio_id' => $episodio_id,
                'usuario_id' => $usuario_id ?: null,
                'sesion_id' => $sesion_id,
                'ip_address' => $this->obtener_ip_cliente(),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'dispositivo' => $this->detectar_dispositivo(),
                'posicion_actual' => $posicion,
                'fuente' => sanitize_text_field($datos['fuente'] ?? 'web'),
            ]);
            $reproduccion_id = $wpdb->insert_id;

            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tabla_episodios} SET reproducciones = reproducciones + 1 WHERE id = %d",
                $episodio_id
            ));

            if ($es_unica) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$this->tabla_episodios} SET reproducciones_unicas = reproducciones_unicas + 1 WHERE id = %d",
                    $episodio_id
                ));
            }
        }

        return [
            'success' => true,
            'reproduccion_id' => $reproduccion_id,
            'sesion_id' => $sesion_id,
        ];
    }

    /**
     * AJAX: Actualizar progreso de reproducción
     */
    private function ajax_actualizar_progreso($datos) {
        global $wpdb;

        $reproduccion_id = absint($datos['reproduccion_id'] ?? 0);
        $posicion = absint($datos['posicion'] ?? 0);
        $duracion_total = absint($datos['duracion_total'] ?? 0);

        if ($reproduccion_id === 0) {
            return ['success' => false];
        }

        $porcentaje_completado = $duracion_total > 0 ? min(100, ($posicion / $duracion_total) * 100) : 0;
        $completado = $porcentaje_completado >= 90 ? 1 : 0;

        $wpdb->update(
            $this->tabla_reproducciones,
            [
                'posicion_actual' => $posicion,
                'porcentaje_completado' => $porcentaje_completado,
                'completado' => $completado,
                'velocidad_reproduccion' => floatval($datos['velocidad'] ?? 1),
            ],
            ['id' => $reproduccion_id]
        );

        if ($completado) {
            $reproduccion = $wpdb->get_row($wpdb->prepare(
                "SELECT episodio_id FROM {$this->tabla_reproducciones} WHERE id = %d",
                $reproduccion_id
            ));

            if ($reproduccion) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$this->tabla_episodios}
                     SET tiempo_escucha_total = tiempo_escucha_total + %d
                     WHERE id = %d",
                    $posicion,
                    $reproduccion->episodio_id
                ));
            }
        }

        return ['success' => true, 'completado' => $completado];
    }

    /**
     * AJAX: Finalizar reproducción
     */
    private function ajax_finalizar_reproduccion($datos) {
        global $wpdb;

        $reproduccion_id = absint($datos['reproduccion_id'] ?? 0);
        if ($reproduccion_id === 0) {
            return ['success' => false];
        }

        $wpdb->update(
            $this->tabla_reproducciones,
            ['fecha_fin' => current_time('mysql')],
            ['id' => $reproduccion_id]
        );

        return ['success' => true];
    }

    /**
     * AJAX: Toggle suscripción
     */
    private function ajax_toggle_suscripcion($datos) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        if ($usuario_id === 0) {
            return ['success' => false, 'message' => __('Debes iniciar sesión', 'flavor-chat-ia'), 'requiere_login' => true];
        }

        $serie_id = absint($datos['serie_id'] ?? 0);
        if ($serie_id === 0) {
            return ['success' => false, 'message' => __('Serie no especificada', 'flavor-chat-ia')];
        }

        $suscripcion_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tabla_suscripciones} WHERE serie_id = %d AND usuario_id = %d",
            $serie_id,
            $usuario_id
        ));

        if ($suscripcion_existente) {
            $wpdb->delete($this->tabla_suscripciones, ['id' => $suscripcion_existente]);
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tabla_series} SET suscriptores = GREATEST(0, suscriptores - 1) WHERE id = %d",
                $serie_id
            ));
            $esta_suscrito = false;
        } else {
            $wpdb->insert($this->tabla_suscripciones, [
                'serie_id' => $serie_id,
                'usuario_id' => $usuario_id,
            ]);
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tabla_series} SET suscriptores = suscriptores + 1 WHERE id = %d",
                $serie_id
            ));
            $esta_suscrito = true;
        }

        $serie = $this->obtener_serie($serie_id);

        return [
            'success' => true,
            'suscrito' => $esta_suscrito,
            'total_suscriptores' => $serie ? $serie->suscriptores : 0,
        ];
    }

    /**
     * AJAX: Dar me gusta a episodio
     */
    private function ajax_toggle_me_gusta($datos) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        if ($usuario_id === 0) {
            return ['success' => false, 'requiere_login' => true];
        }

        $episodio_id = absint($datos['episodio_id'] ?? 0);
        if ($episodio_id === 0) {
            return ['success' => false];
        }

        $clave_meta = '_flavor_podcast_like_' . $episodio_id;
        $tiene_like = get_user_meta($usuario_id, $clave_meta, true);

        if ($tiene_like) {
            delete_user_meta($usuario_id, $clave_meta);
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tabla_episodios} SET me_gusta = GREATEST(0, me_gusta - 1) WHERE id = %d",
                $episodio_id
            ));
            $me_gusta = false;
        } else {
            update_user_meta($usuario_id, $clave_meta, 1);
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tabla_episodios} SET me_gusta = me_gusta + 1 WHERE id = %d",
                $episodio_id
            ));
            $me_gusta = true;
        }

        $episodio = $this->obtener_episodio($episodio_id);

        return [
            'success' => true,
            'me_gusta' => $me_gusta,
            'total_me_gusta' => $episodio ? $episodio->me_gusta : 0,
        ];
    }

    /**
     * AJAX: Buscar podcasts y episodios
     */
    private function ajax_buscar($datos) {
        $termino_busqueda = sanitize_text_field($datos['termino'] ?? '');
        $categoria = sanitize_text_field($datos['categoria'] ?? '');
        $tipo = sanitize_text_field($datos['tipo'] ?? '');
        $limite = min(50, absint($datos['limite'] ?? 10));

        $resultados = ['series' => [], 'episodios' => []];

        if ($tipo !== 'episodios') {
            $resultados['series'] = $this->buscar_series($termino_busqueda, $categoria, $limite);
        }

        if ($tipo !== 'series') {
            $resultados['episodios'] = $this->buscar_episodios($termino_busqueda, $categoria, $limite);
        }

        return [
            'success' => true,
            'resultados' => $resultados,
            'total' => count($resultados['series']) + count($resultados['episodios']),
        ];
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes() {
        $namespace = 'flavor-podcast/v1';

        register_rest_route($namespace, '/series', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_series'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/series/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_serie'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/series/(?P<id>\d+)/episodios', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_episodios_serie'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/episodios/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_episodio'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route($namespace, '/series', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_crear_serie'],
            'permission_callback' => [$this, 'verificar_permiso_crear'],
        ]);

        register_rest_route($namespace, '/episodios', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_crear_episodio'],
            'permission_callback' => [$this, 'verificar_permiso_crear'],
        ]);

        register_rest_route($namespace, '/estadisticas/serie/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_estadisticas'],
            'permission_callback' => [$this, 'verificar_permiso_estadisticas'],
        ]);

        register_rest_route($namespace, '/buscar', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_buscar'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * REST: Obtener series
     */
    public function rest_obtener_series($request) {
        $series = $this->obtener_series([
            'limite' => $request->get_param('limite') ?: 10,
            'offset' => (($request->get_param('pagina') ?: 1) - 1) * ($request->get_param('limite') ?: 10),
            'categoria' => $request->get_param('categoria') ?: '',
            'orden' => $request->get_param('orden') ?: 'recientes',
        ]);

        $respuesta = [
            'success' => true,
            'data' => array_map([$this, 'formatear_serie_respuesta'], $series),
        ];

        return rest_ensure_response($this->sanitize_public_podcast_response($respuesta));
    }

    /**
     * REST: Obtener serie individual
     */
    public function rest_obtener_serie($request) {
        $serie = $this->obtener_serie($request->get_param('id'));
        if (!$serie) {
            return new WP_Error('not_found', __('Serie no encontrada', 'flavor-chat-ia'), ['status' => 404]);
        }

        $respuesta = [
            'success' => true,
            'data' => $this->formatear_serie_respuesta($serie, true),
        ];

        return rest_ensure_response($this->sanitize_public_podcast_response($respuesta));
    }

    /**
     * REST: Obtener episodios de serie
     */
    public function rest_obtener_episodios_serie($request) {
        $serie_id = $request->get_param('id');
        $limite = $request->get_param('limite') ?: 20;
        $offset = (($request->get_param('pagina') ?: 1) - 1) * $limite;

        $episodios = $this->obtener_episodios_serie($serie_id, $limite, $offset);

        $respuesta = [
            'success' => true,
            'data' => array_map([$this, 'formatear_episodio_respuesta'], $episodios),
            'total' => $this->contar_episodios_serie($serie_id),
        ];

        return rest_ensure_response($this->sanitize_public_podcast_response($respuesta));
    }

    /**
     * REST: Obtener episodio individual
     */
    public function rest_obtener_episodio($request) {
        $episodio = $this->obtener_episodio($request->get_param('id'));
        if (!$episodio) {
            return new WP_Error('not_found', __('Episodio no encontrado', 'flavor-chat-ia'), ['status' => 404]);
        }

        $respuesta = [
            'success' => true,
            'data' => $this->formatear_episodio_respuesta($episodio, true),
        ];

        return rest_ensure_response($this->sanitize_public_podcast_response($respuesta));
    }

    /**
     * REST: Crear serie
     */
    public function rest_crear_serie($request) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        $datos = $request->get_json_params();

        $titulo = sanitize_text_field($datos['titulo'] ?? '');
        $descripcion = wp_kses_post($datos['descripcion'] ?? '');

        if (empty($titulo) || empty($descripcion)) {
            return new WP_Error('missing_data', __('Título y descripción son requeridos', 'flavor-chat-ia'), ['status' => 400]);
        }

        $series_usuario = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_series} WHERE autor_id = %d",
            $usuario_id
        ));

        $limite_series = $this->get_setting('limite_series_por_usuario');
        if ($series_usuario >= $limite_series) {
            return new WP_Error('limit_reached', sprintf(__('Has alcanzado el límite de %d series', 'flavor-chat-ia'), $limite_series), ['status' => 403]);
        }

        $slug = wp_unique_post_slug(sanitize_title($titulo), 0, 'publish', 'podcast_serie', 0);

        $resultado_insercion = $wpdb->insert($this->tabla_series, [
            'titulo' => $titulo,
            'slug' => $slug,
            'descripcion' => $descripcion,
            'descripcion_corta' => sanitize_text_field($datos['descripcion_corta'] ?? ''),
            'autor_id' => $usuario_id,
            'imagen_url' => esc_url_raw($datos['imagen_url'] ?? ''),
            'categoria' => sanitize_text_field($datos['categoria'] ?? ''),
            'idioma' => sanitize_text_field($datos['idioma'] ?? 'es'),
            'estado' => $this->get_setting('requiere_moderacion') ? 'borrador' : 'publicado',
        ]);

        if (!$resultado_insercion) {
            return new WP_Error('db_error', __('Error al crear la serie', 'flavor-chat-ia'), ['status' => 500]);
        }

        $serie = $this->obtener_serie($wpdb->insert_id);

        return rest_ensure_response([
            'success' => true,
            'data' => $this->formatear_serie_respuesta($serie),
            'message' => __('Serie creada correctamente', 'flavor-chat-ia'),
        ]);
    }

    /**
     * REST: Crear episodio
     */
    public function rest_crear_episodio($request) {
        global $wpdb;

        $usuario_id = get_current_user_id();
        $datos = $request->get_json_params();

        $serie_id = absint($datos['serie_id'] ?? 0);
        $serie = $this->obtener_serie($serie_id);

        if (!$serie || $serie->autor_id != $usuario_id) {
            return new WP_Error('forbidden', __('No tienes permiso para añadir episodios', 'flavor-chat-ia'), ['status' => 403]);
        }

        $titulo = sanitize_text_field($datos['titulo'] ?? '');
        $archivo_url = esc_url_raw($datos['archivo_url'] ?? '');

        if (empty($titulo) || empty($archivo_url)) {
            return new WP_Error('missing_data', __('Título y archivo son requeridos', 'flavor-chat-ia'), ['status' => 400]);
        }

        $ultimo_numero_episodio = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(numero_episodio) FROM {$this->tabla_episodios} WHERE serie_id = %d AND temporada = %d",
            $serie_id,
            absint($datos['temporada'] ?? 1)
        ));

        $numero_episodio = ($ultimo_numero_episodio ?: 0) + 1;
        $guid = $this->generar_guid_episodio($serie_id, $numero_episodio);
        $slug = wp_unique_post_slug(sanitize_title($titulo), 0, 'publish', 'podcast_episodio', 0);

        $resultado_insercion = $wpdb->insert($this->tabla_episodios, [
            'serie_id' => $serie_id,
            'temporada' => absint($datos['temporada'] ?? 1),
            'numero_episodio' => $numero_episodio,
            'guid' => $guid,
            'titulo' => $titulo,
            'slug' => $slug,
            'descripcion' => wp_kses_post($datos['descripcion'] ?? ''),
            'archivo_url' => $archivo_url,
            'tipo_archivo' => sanitize_text_field($datos['tipo_archivo'] ?? 'audio/mpeg'),
            'duracion_segundos' => absint($datos['duracion_segundos'] ?? 0),
            'tamano_bytes' => absint($datos['tamano_bytes'] ?? 0),
            'imagen_url' => esc_url_raw($datos['imagen_url'] ?? ''),
            'estado' => $this->get_setting('requiere_moderacion') ? 'borrador' : 'publicado',
            'fecha_publicacion' => current_time('mysql'),
        ]);

        if (!$resultado_insercion) {
            return new WP_Error('db_error', __('Error al crear el episodio', 'flavor-chat-ia'), ['status' => 500]);
        }

        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tabla_series}
             SET total_episodios = total_episodios + 1,
                 duracion_total_segundos = duracion_total_segundos + %d,
                 fecha_ultimo_episodio = %s
             WHERE id = %d",
            absint($datos['duracion_segundos'] ?? 0),
            current_time('mysql'),
            $serie_id
        ));

        $episodio = $this->obtener_episodio($wpdb->insert_id);

        return rest_ensure_response([
            'success' => true,
            'data' => $this->formatear_episodio_respuesta($episodio),
        ]);
    }

    /**
     * REST: Obtener estadísticas
     */
    public function rest_obtener_estadisticas($request) {
        $serie_id = $request->get_param('id');
        $periodo = $request->get_param('periodo') ?: 30;

        $estadisticas = $this->obtener_estadisticas_serie($serie_id, $periodo);

        return rest_ensure_response(['success' => true, 'data' => $estadisticas]);
    }

    /**
     * REST: Buscar
     */
    public function rest_buscar($request) {
        $termino_busqueda = sanitize_text_field($request->get_param('q') ?? '');
        $categoria = sanitize_text_field($request->get_param('categoria') ?? '');
        $tipo = sanitize_text_field($request->get_param('tipo') ?? '');
        $limite = min(50, absint($request->get_param('limite') ?? 10));

        $resultados = ['series' => [], 'episodios' => []];

        if ($tipo !== 'episodios') {
            $resultados['series'] = array_map(
                [$this, 'formatear_serie_respuesta'],
                $this->buscar_series($termino_busqueda, $categoria, $limite)
            );
        }

        if ($tipo !== 'series') {
            $resultados['episodios'] = array_map(
                [$this, 'formatear_episodio_respuesta'],
                $this->buscar_episodios($termino_busqueda, $categoria, $limite)
            );
        }

        $respuesta = ['success' => true, 'data' => $resultados];

        return rest_ensure_response($this->sanitize_public_podcast_response($respuesta));
    }

    private function sanitize_public_podcast_response($respuesta) {
        if (is_user_logged_in() || empty($respuesta['success'])) {
            return $respuesta;
        }

        if (!empty($respuesta['data']) && is_array($respuesta['data'])) {
            $respuesta['data'] = $this->sanitize_public_podcast_data($respuesta['data']);
        }

        return $respuesta;
    }

    private function sanitize_public_podcast_data($data) {
        if (is_array($data) && isset($data['series']) && isset($data['episodios'])) {
            $data['series'] = array_map([$this, 'sanitize_public_podcast_item'], $data['series']);
            $data['episodios'] = array_map([$this, 'sanitize_public_podcast_item'], $data['episodios']);
            return $data;
        }

        if (is_array($data) && array_key_exists('id', $data)) {
            return $this->sanitize_public_podcast_item($data);
        }

        if (is_array($data)) {
            return array_map([$this, 'sanitize_public_podcast_item'], $data);
        }

        return $data;
    }

    private function sanitize_public_podcast_item($item) {
        if (!is_array($item)) {
            return $item;
        }

        unset($item['autor_id']);
        return $item;
    }

    /**
     * Verificar permiso para crear contenido
     */
    public function verificar_permiso_crear($request) {
        return is_user_logged_in() && current_user_can('publish_posts');
    }

    /**
     * Verificar permiso para ver estadísticas
     */
    public function verificar_permiso_estadisticas($request) {
        if (!is_user_logged_in()) {
            return false;
        }
        $serie = $this->obtener_serie($request->get_param('id'));
        return $serie && ($serie->autor_id == get_current_user_id() || current_user_can('manage_options'));
    }

    /**
     * Genera el feed RSS para Apple Podcasts/Spotify
     */
    public function render_rss_feed() {
        global $wpdb;

        $serie_id = absint(get_query_var('serie_id'));
        if ($serie_id === 0) {
            wp_die(__('Serie no especificada', 'flavor-chat-ia'), '', ['response' => 404]);
        }

        $serie = $this->obtener_serie($serie_id);
        if (!$serie || $serie->estado !== 'publicado') {
            wp_die(__('Serie no encontrada', 'flavor-chat-ia'), '', ['response' => 404]);
        }

        $episodios = $this->obtener_episodios_serie($serie_id, $this->get_setting('rss_items_limite'), 0, 'recientes');
        $autor = get_userdata($serie->autor_id);
        $autor_nombre = $autor ? $autor->display_name : get_bloginfo('name');
        $autor_email = $autor ? $autor->user_email : get_bloginfo('admin_email');

        header('Content-Type: application/rss+xml; charset=' . get_option('blog_charset'), true);

        echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?>' . "\n";
        ?>
<rss version="2.0"
     xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
     xmlns:googleplay="http://www.google.com/schemas/play-podcasts/1.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:podcast="https://podcastindex.org/namespace/1.0">
<channel>
    <title><?php echo esc_html($serie->titulo); ?></title>
    <link><?php echo esc_url($this->obtener_url_serie($serie)); ?></link>
    <language><?php echo esc_html($serie->idioma ?: 'es'); ?></language>
    <copyright><?php echo esc_html($serie->copyright ?: '© ' . date('Y') . ' ' . $autor_nombre); ?></copyright>
    <description><?php echo esc_html($serie->descripcion); ?></description>
    <atom:link href="<?php echo esc_url($this->obtener_url_feed($serie_id)); ?>" rel="self" type="application/rss+xml"/>

    <itunes:author><?php echo esc_html($autor_nombre); ?></itunes:author>
    <itunes:summary><?php echo esc_html($serie->descripcion_corta ?: wp_trim_words($serie->descripcion, 100)); ?></itunes:summary>
    <itunes:type><?php echo $serie->tipo === 'serial' ? 'serial' : 'episodic'; ?></itunes:type>
    <itunes:owner>
        <itunes:name><?php echo esc_html($autor_nombre); ?></itunes:name>
        <itunes:email><?php echo esc_html($autor_email); ?></itunes:email>
    </itunes:owner>
    <itunes:explicit><?php echo $serie->explicito ? 'true' : 'false'; ?></itunes:explicit>
    <?php if ($serie->imagen_url): ?>
    <itunes:image href="<?php echo esc_url($serie->imagen_url); ?>"/>
    <image>
        <url><?php echo esc_url($serie->imagen_url); ?></url>
        <title><?php echo esc_html($serie->titulo); ?></title>
        <link><?php echo esc_url($this->obtener_url_serie($serie)); ?></link>
    </image>
    <?php endif; ?>
    <?php if ($serie->categoria): ?>
    <itunes:category text="<?php echo esc_attr($this->obtener_categoria_itunes($serie->categoria)); ?>">
        <?php if ($serie->subcategoria): ?>
        <itunes:category text="<?php echo esc_attr($serie->subcategoria); ?>"/>
        <?php endif; ?>
    </itunes:category>
    <?php endif; ?>

    <googleplay:author><?php echo esc_html($autor_nombre); ?></googleplay:author>
    <googleplay:description><?php echo esc_html($serie->descripcion_corta ?: wp_trim_words($serie->descripcion, 100)); ?></googleplay:description>
    <googleplay:explicit><?php echo $serie->explicito ? 'yes' : 'no'; ?></googleplay:explicit>
    <?php if ($serie->imagen_url): ?>
    <googleplay:image href="<?php echo esc_url($serie->imagen_url); ?>"/>
    <?php endif; ?>

    <?php foreach ($episodios as $episodio): ?>
    <item>
        <title><?php echo esc_html($episodio->titulo); ?></title>
        <link><?php echo esc_url($this->obtener_url_episodio($episodio)); ?></link>
        <guid isPermaLink="false"><?php echo esc_html($episodio->guid); ?></guid>
        <pubDate><?php echo esc_html(mysql2date('D, d M Y H:i:s O', $episodio->fecha_publicacion)); ?></pubDate>
        <description><![CDATA[<?php echo wp_kses_post($episodio->descripcion); ?>]]></description>
        <content:encoded><![CDATA[<?php echo wp_kses_post($episodio->descripcion); ?>]]></content:encoded>

        <enclosure url="<?php echo esc_url($episodio->archivo_url); ?>"
                   length="<?php echo esc_attr($episodio->tamano_bytes ?: 0); ?>"
                   type="<?php echo esc_attr($episodio->tipo_archivo ?: 'audio/mpeg'); ?>"/>

        <itunes:title><?php echo esc_html($episodio->titulo); ?></itunes:title>
        <itunes:summary><?php echo esc_html($episodio->descripcion_corta ?: wp_trim_words($episodio->descripcion, 100)); ?></itunes:summary>
        <itunes:duration><?php echo esc_html($this->formatear_duracion_iso($episodio->duracion_segundos)); ?></itunes:duration>
        <itunes:explicit><?php echo $episodio->explicito ? 'true' : 'false'; ?></itunes:explicit>
        <itunes:episodeType><?php echo esc_html($episodio->tipo_episodio ?: 'full'); ?></itunes:episodeType>
        <?php if ($serie->tipo === 'serial'): ?>
        <itunes:season><?php echo esc_html($episodio->temporada); ?></itunes:season>
        <itunes:episode><?php echo esc_html($episodio->numero_episodio); ?></itunes:episode>
        <?php endif; ?>
        <?php if ($episodio->imagen_url): ?>
        <itunes:image href="<?php echo esc_url($episodio->imagen_url); ?>"/>
        <?php endif; ?>
    </item>
    <?php endforeach; ?>

</channel>
</rss>
        <?php
        exit;
    }

    // ==================== MÉTODOS HELPER ====================

    /**
     * Obtiene una serie por ID
     */
    private function obtener_serie($serie_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_series} WHERE id = %d",
            absint($serie_id)
        ));
    }

    /**
     * Obtiene series con filtros
     */
    private function obtener_series($args = []) {
        global $wpdb;

        $defaults = [
            'limite' => 10,
            'offset' => 0,
            'categoria' => '',
            'orden' => 'recientes',
            'estado' => 'publicado',
        ];
        $args = wp_parse_args($args, $defaults);

        $where = ['estado = %s'];
        $params = [$args['estado']];

        if (!empty($args['categoria'])) {
            $where[] = 'categoria = %s';
            $params[] = $args['categoria'];
        }

        $order_by = match($args['orden']) {
            'populares' => 'suscriptores DESC',
            'alfabetico' => 'titulo ASC',
            'episodios' => 'total_episodios DESC',
            default => 'fecha_ultimo_episodio DESC, fecha_creacion DESC',
        };

        $sql = "SELECT * FROM {$this->tabla_series}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY {$order_by}
                LIMIT %d OFFSET %d";

        $params[] = $args['limite'];
        $params[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare($sql, ...$params));
    }

    /**
     * Obtiene un episodio por ID
     */
    private function obtener_episodio($episodio_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_episodios} WHERE id = %d",
            absint($episodio_id)
        ));
    }

    /**
     * Obtiene el último episodio de una serie
     */
    private function obtener_ultimo_episodio($serie_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_episodios}
             WHERE serie_id = %d AND estado = 'publicado'
             ORDER BY fecha_publicacion DESC LIMIT 1",
            absint($serie_id)
        ));
    }

    /**
     * Obtiene episodios de una serie
     */
    private function obtener_episodios_serie($serie_id, $limite = 10, $offset = 0, $orden = 'recientes', $temporada = 0) {
        global $wpdb;

        $where = ['serie_id = %d', "estado = 'publicado'"];
        $params = [absint($serie_id)];

        if ($temporada > 0) {
            $where[] = 'temporada = %d';
            $params[] = $temporada;
        }

        $order_by = match($orden) {
            'antiguos' => 'fecha_publicacion ASC',
            'populares' => 'reproducciones DESC',
            default => 'fecha_publicacion DESC',
        };

        $sql = "SELECT * FROM {$this->tabla_episodios}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY {$order_by}
                LIMIT %d OFFSET %d";

        $params[] = $limite;
        $params[] = $offset;

        return $wpdb->get_results($wpdb->prepare($sql, ...$params));
    }

    /**
     * Cuenta episodios de una serie
     */
    private function contar_episodios_serie($serie_id, $temporada = 0) {
        global $wpdb;

        $where = ['serie_id = %d', "estado = 'publicado'"];
        $params = [absint($serie_id)];

        if ($temporada > 0) {
            $where[] = 'temporada = %d';
            $params[] = $temporada;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_episodios} WHERE " . implode(' AND ', $where),
            ...$params
        ));
    }

    /**
     * Obtiene número de temporadas de una serie
     */
    private function obtener_temporadas_serie($serie_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(temporada) FROM {$this->tabla_episodios} WHERE serie_id = %d AND estado = 'publicado'",
            absint($serie_id)
        ));
    }

    /**
     * Obtiene transcripción de un episodio
     */
    private function obtener_transcripcion($episodio_id, $idioma = 'es') {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_transcripciones}
             WHERE episodio_id = %d AND idioma = %s AND estado = 'completado'",
            absint($episodio_id),
            $idioma
        ));
    }

    /**
     * Verifica si un usuario está suscrito a una serie
     */
    private function esta_suscrito($usuario_id, $serie_id) {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tabla_suscripciones} WHERE usuario_id = %d AND serie_id = %d",
            $usuario_id,
            $serie_id
        ));
    }

    /**
     * Busca series por término
     */
    private function buscar_series($termino, $categoria = '', $limite = 10) {
        global $wpdb;

        $where = ["estado = 'publicado'"];
        $params = [];

        if (!empty($termino)) {
            $where[] = "(titulo LIKE %s OR descripcion LIKE %s)";
            $like = '%' . $wpdb->esc_like($termino) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($categoria)) {
            $where[] = 'categoria = %s';
            $params[] = $categoria;
        }

        $params[] = $limite;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tabla_series}
             WHERE " . implode(' AND ', $where) . "
             ORDER BY suscriptores DESC, fecha_creacion DESC
             LIMIT %d",
            ...$params
        ));
    }

    /**
     * Busca episodios por término
     */
    private function buscar_episodios($termino, $categoria = '', $limite = 10) {
        global $wpdb;

        $where = ["e.estado = 'publicado'"];
        $params = [];

        if (!empty($termino)) {
            $where[] = "(e.titulo LIKE %s OR e.descripcion LIKE %s)";
            $like = '%' . $wpdb->esc_like($termino) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($categoria)) {
            $where[] = 's.categoria = %s';
            $params[] = $categoria;
        }

        $params[] = $limite;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, s.titulo as serie_titulo, s.imagen_url as serie_imagen
             FROM {$this->tabla_episodios} e
             JOIN {$this->tabla_series} s ON e.serie_id = s.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY e.reproducciones DESC, e.fecha_publicacion DESC
             LIMIT %d",
            ...$params
        ));
    }

    /**
     * Obtiene estadísticas de una serie
     */
    private function obtener_estadisticas_serie($serie_id, $dias = 30) {
        global $wpdb;

        $fecha_inicio = date('Y-m-d H:i:s', strtotime("-{$dias} days"));

        $reproducciones_totales = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(e.reproducciones)
             FROM {$this->tabla_episodios} e
             WHERE e.serie_id = %d",
            $serie_id
        ));

        $reproducciones_periodo = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$this->tabla_reproducciones} r
             JOIN {$this->tabla_episodios} e ON r.episodio_id = e.id
             WHERE e.serie_id = %d AND r.fecha_inicio >= %s",
            $serie_id,
            $fecha_inicio
        ));

        $tiempo_escucha_total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(r.duracion_escuchada)
             FROM {$this->tabla_reproducciones} r
             JOIN {$this->tabla_episodios} e ON r.episodio_id = e.id
             WHERE e.serie_id = %d",
            $serie_id
        ));

        $porcentaje_completado_promedio = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(r.porcentaje_completado)
             FROM {$this->tabla_reproducciones} r
             JOIN {$this->tabla_episodios} e ON r.episodio_id = e.id
             WHERE e.serie_id = %d AND r.porcentaje_completado > 0",
            $serie_id
        ));

        $serie = $this->obtener_serie($serie_id);

        $reproducciones_por_dia = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(r.fecha_inicio) as fecha, COUNT(*) as total
             FROM {$this->tabla_reproducciones} r
             JOIN {$this->tabla_episodios} e ON r.episodio_id = e.id
             WHERE e.serie_id = %d AND r.fecha_inicio >= %s
             GROUP BY DATE(r.fecha_inicio)
             ORDER BY fecha ASC",
            $serie_id,
            $fecha_inicio
        ));

        return [
            'reproducciones_totales' => (int) $reproducciones_totales,
            'reproducciones_periodo' => (int) $reproducciones_periodo,
            'suscriptores' => $serie ? (int) $serie->suscriptores : 0,
            'tiempo_escucha_total' => (int) $tiempo_escucha_total,
            'porcentaje_completado_promedio' => round((float) $porcentaje_completado_promedio, 2),
            'reproducciones_por_dia' => $reproducciones_por_dia,
            'periodo_dias' => $dias,
        ];
    }

    /**
     * Verifica si tiene reproducción previa
     */
    private function tiene_reproduccion_previa($episodio_id, $usuario_id, $sesion_id) {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tabla_reproducciones}
             WHERE episodio_id = %d AND (usuario_id = %d OR sesion_id = %s)
             LIMIT 1",
            $episodio_id,
            $usuario_id,
            $sesion_id
        ));
    }

    /**
     * Genera ID de sesión único
     */
    private function generar_sesion_id() {
        return 'sess_' . bin2hex(random_bytes(16));
    }

    /**
     * Genera GUID único para episodio
     */
    private function generar_guid_episodio($serie_id, $numero_episodio) {
        return sprintf('podcast-%d-ep%d-%s', $serie_id, $numero_episodio, uniqid());
    }

    /**
     * Obtiene IP del cliente
     */
    private function obtener_ip_cliente() {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }
        return '0.0.0.0';
    }

    /**
     * Detecta tipo de dispositivo
     */
    private function detectar_dispositivo() {
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (strpos($user_agent, 'mobile') !== false || strpos($user_agent, 'android') !== false) {
            return 'mobile';
        }
        if (strpos($user_agent, 'tablet') !== false || strpos($user_agent, 'ipad') !== false) {
            return 'tablet';
        }
        return 'desktop';
    }

    /**
     * Formatea duración en formato legible
     */
    private function formatear_duracion($segundos) {
        if (!$segundos || $segundos < 0) {
            return '0:00';
        }
        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $segs = $segundos % 60;

        if ($horas > 0) {
            return sprintf('%d:%02d:%02d', $horas, $minutos, $segs);
        }
        return sprintf('%d:%02d', $minutos, $segs);
    }

    /**
     * Formatea duración en formato ISO para RSS
     */
    private function formatear_duracion_iso($segundos) {
        if (!$segundos || $segundos < 0) {
            return '00:00:00';
        }
        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $segs = $segundos % 60;
        return sprintf('%02d:%02d:%02d', $horas, $minutos, $segs);
    }

    /**
     * Obtiene URL de una serie
     */
    private function obtener_url_serie($serie) {
        return home_url('/podcast/' . $serie->slug . '/');
    }

    /**
     * Obtiene URL de un episodio
     */
    private function obtener_url_episodio($episodio) {
        $serie = $this->obtener_serie($episodio->serie_id);
        if ($serie) {
            return home_url('/podcast/' . $serie->slug . '/' . $episodio->slug . '/');
        }
        return home_url('/podcast/episodio/' . $episodio->id . '/');
    }

    /**
     * Obtiene URL del feed RSS
     */
    private function obtener_url_feed($serie_id) {
        return home_url('/feed/podcast/' . $serie_id . '/');
    }

    /**
     * Mapea categoría interna a categoría iTunes
     */
    private function obtener_categoria_itunes($categoria) {
        $mapa_categorias = [
            'noticias' => 'News',
            'entrevistas' => 'Society & Culture',
            'historias' => 'Society & Culture',
            'debates' => 'News',
            'cultura' => 'Arts',
            'educacion' => 'Education',
            'tecnologia' => 'Technology',
            'deportes' => 'Sports',
            'musica' => 'Music',
            'comedia' => 'Comedy',
        ];
        return $mapa_categorias[$categoria] ?? 'Society & Culture';
    }

    /**
     * Formatea serie para respuesta API
     */
    private function formatear_serie_respuesta($serie, $detallado = false) {
        $respuesta = [
            'id' => (int) $serie->id,
            'titulo' => $serie->titulo,
            'slug' => $serie->slug,
            'descripcion_corta' => $serie->descripcion_corta ?: wp_trim_words($serie->descripcion, 30),
            'imagen_url' => $serie->imagen_url,
            'categoria' => $serie->categoria,
            'suscriptores' => (int) $serie->suscriptores,
            'total_episodios' => (int) $serie->total_episodios,
            'url' => $this->obtener_url_serie($serie),
            'feed_url' => $this->obtener_url_feed($serie->id),
        ];

        if ($detallado) {
            $respuesta['descripcion'] = $serie->descripcion;
            $respuesta['autor_id'] = (int) $serie->autor_id;
            $respuesta['idioma'] = $serie->idioma;
            $respuesta['tipo'] = $serie->tipo;
            $respuesta['explicito'] = (bool) $serie->explicito;
            $respuesta['total_reproducciones'] = (int) $serie->total_reproducciones;
            $respuesta['duracion_total'] = $this->formatear_duracion($serie->duracion_total_segundos);
            $respuesta['fecha_creacion'] = $serie->fecha_creacion;
            $respuesta['fecha_ultimo_episodio'] = $serie->fecha_ultimo_episodio;
        }

        return $respuesta;
    }

    /**
     * Formatea episodio para respuesta API
     */
    private function formatear_episodio_respuesta($episodio, $detallado = false) {
        $respuesta = [
            'id' => (int) $episodio->id,
            'serie_id' => (int) $episodio->serie_id,
            'titulo' => $episodio->titulo,
            'slug' => $episodio->slug,
            'temporada' => (int) $episodio->temporada,
            'numero_episodio' => (int) $episodio->numero_episodio,
            'duracion' => $this->formatear_duracion($episodio->duracion_segundos),
            'duracion_segundos' => (int) $episodio->duracion_segundos,
            'imagen_url' => $episodio->imagen_url,
            'archivo_url' => $episodio->archivo_url,
            'reproducciones' => (int) $episodio->reproducciones,
            'me_gusta' => (int) $episodio->me_gusta,
            'fecha_publicacion' => $episodio->fecha_publicacion,
            'url' => $this->obtener_url_episodio($episodio),
        ];

        if ($detallado) {
            $respuesta['descripcion'] = $episodio->descripcion;
            $respuesta['descripcion_corta'] = $episodio->descripcion_corta;
            $respuesta['notas_episodio'] = $episodio->notas_episodio;
            $respuesta['tipo_archivo'] = $episodio->tipo_archivo;
            $respuesta['tamano_bytes'] = (int) $episodio->tamano_bytes;
            $respuesta['explicito'] = (bool) $episodio->explicito;
            $respuesta['tipo_episodio'] = $episodio->tipo_episodio;

            $transcripcion = $this->obtener_transcripcion($episodio->id);
            $respuesta['tiene_transcripcion'] = (bool) $transcripcion;
        }

        return $respuesta;
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_series' => [
                'description' => 'Listar series de podcast disponibles',
                'params' => ['categoria', 'limite', 'orden'],
            ],
            'ver_serie' => [
                'description' => 'Ver detalles de una serie',
                'params' => ['serie_id'],
            ],
            'listar_episodios' => [
                'description' => 'Listar episodios de una serie',
                'params' => ['serie_id', 'limite', 'temporada'],
            ],
            'reproducir_episodio' => [
                'description' => 'Obtener datos para reproducir episodio',
                'params' => ['episodio_id'],
            ],
            'suscribirse' => [
                'description' => 'Suscribirse a una serie',
                'params' => ['serie_id'],
            ],
            'crear_serie' => [
                'description' => 'Crear nueva serie de podcast',
                'params' => ['titulo', 'descripcion', 'categoria'],
            ],
            'subir_episodio' => [
                'description' => 'Subir nuevo episodio',
                'params' => ['serie_id', 'titulo', 'archivo_url'],
            ],
            'buscar' => [
                'description' => 'Buscar series y episodios',
                'params' => ['termino', 'categoria', 'tipo'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $metodo_accion = 'action_' . $action_name;
        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($params);
        }
        return ['success' => false, 'error' => "Acción no implementada: {$action_name}"];
    }

    /**
     * Acción: Listar series
     */
    private function action_listar_series($params) {
        $series = $this->obtener_series([
            'limite' => absint($params['limite'] ?? 20),
            'categoria' => sanitize_text_field($params['categoria'] ?? ''),
            'orden' => sanitize_text_field($params['orden'] ?? 'recientes'),
        ]);

        return [
            'success' => true,
            'total' => count($series),
            'series' => array_map([$this, 'formatear_serie_respuesta'], $series),
        ];
    }

    /**
     * Componentes web del módulo
     */
    public function get_web_components() {
        return [
            'hero_podcast' => [
                'label' => __('Hero Podcasts', 'flavor-chat-ia'),
                'description' => __('Sección hero para podcasts comunitarios', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-microphone',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Podcasts de la Comunidad', 'flavor-chat-ia')],
                    'subtitulo' => ['type' => 'textarea', 'label' => __('Subtítulo', 'flavor-chat-ia'), 'default' => __('Historias, conversaciones y conocimiento local', 'flavor-chat-ia')],
                    'imagen_fondo' => ['type' => 'image', 'label' => __('Imagen de fondo', 'flavor-chat-ia'), 'default' => ''],
                    'serie_destacada_id' => ['type' => 'number', 'label' => __('ID de la serie destacada', 'flavor-chat-ia'), 'default' => 0],
                ],
                'template' => 'podcast/hero',
            ],
            'podcast_grid' => [
                'label' => __('Grid de Series', 'flavor-chat-ia'),
                'description' => __('Listado de series en tarjetas', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Explora Nuestros Podcasts', 'flavor-chat-ia')],
                    'columnas' => ['type' => 'select', 'label' => __('Columnas', 'flavor-chat-ia'), 'options' => [2, 3, 4], 'default' => 3],
                    'limite' => ['type' => 'number', 'label' => __('Número de series', 'flavor-chat-ia'), 'default' => 6],
                    'categoria' => ['type' => 'text', 'label' => __('Filtrar por categoría', 'flavor-chat-ia'), 'default' => ''],
                ],
                'template' => 'podcast/grid',
            ],
            'episodios_recientes' => [
                'label' => __('Episodios Recientes', 'flavor-chat-ia'),
                'description' => __('Lista de últimos episodios publicados', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-playlist-audio',
                'fields' => [
                    'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => __('Últimos Episodios', 'flavor-chat-ia')],
                    'limite' => ['type' => 'number', 'label' => __('Número de episodios', 'flavor-chat-ia'), 'default' => 5],
                    'estilo' => ['type' => 'select', 'label' => __('Estilo', 'flavor-chat-ia'), 'options' => ['lista', 'tarjetas'], 'default' => 'tarjetas'],
                ],
                'template' => 'podcast/episodios-recientes',
            ],
            'podcast_player_embed' => [
                'label' => __('Player Embebido', 'flavor-chat-ia'),
                'description' => __('Reproductor de podcast embebido', 'flavor-chat-ia'),
                'category' => 'media',
                'icon' => 'dashicons-format-audio',
                'fields' => [
                    'serie_id' => ['type' => 'number', 'label' => __('ID de la serie', 'flavor-chat-ia'), 'default' => 0],
                    'episodio_id' => ['type' => 'number', 'label' => __('ID del episodio', 'flavor-chat-ia'), 'default' => 0],
                    'mostrar_lista' => ['type' => 'toggle', 'label' => __('Mostrar lista', 'flavor-chat-ia'), 'default' => true],
                ],
                'template' => 'podcast/player-embed',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'podcast_listar_series',
                'description' => 'Ver lista de series de podcast comunitarios',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'categoria' => ['type' => 'string', 'description' => 'Filtrar por categoría'],
                        'limite' => ['type' => 'integer', 'description' => 'Número máximo de resultados'],
                    ],
                ],
            ],
            [
                'name' => 'podcast_buscar',
                'description' => 'Buscar podcasts y episodios',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'termino' => ['type' => 'string', 'description' => 'Término de búsqueda'],
                        'tipo' => ['type' => 'string', 'enum' => ['series', 'episodios', 'todos']],
                    ],
                    'required' => ['termino'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Plataforma de Podcast Comunitario**

Sistema completo para crear, gestionar y escuchar podcasts de la comunidad.

**Características principales:**
- Crea series de podcast con múltiples episodios
- Organiza por temporadas
- Feed RSS compatible con Apple Podcasts, Spotify y Google Podcasts
- Reproductor HTML5 con controles de velocidad
- Transcripciones automáticas opcionales
- Estadísticas detalladas de reproducciones
- Sistema de suscripciones con notificaciones

**Categorías disponibles:**
- Noticias Locales, Entrevistas, Historias del Barrio
- Debates Comunitarios, Cultura y Arte, Educación
- Tecnología, Deportes, Música, Comedia

**Shortcodes:**
- [podcast_player serie_id="X"] - Reproductor completo
- [podcast_lista_episodios serie_id="X"] - Lista de episodios
- [podcast_series] - Grid de series disponibles
- [podcast_suscribirse serie_id="X"] - Botón de suscripción
- [podcast_buscar] - Buscador de podcasts

**Feed RSS:**
Cada serie tiene su propio feed RSS: /feed/podcast/{id}/
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            ['pregunta' => '¿Quién puede crear un podcast?', 'respuesta' => 'Cualquier miembro verificado puede crear hasta 5 series de podcast.'],
            ['pregunta' => '¿Cuánto puede durar un episodio?', 'respuesta' => 'La duración máxima es de 2 horas por episodio.'],
            ['pregunta' => '¿Qué formatos de audio se aceptan?', 'respuesta' => 'MP3, MP4, OGG, M4A y WAV.'],
            ['pregunta' => '¿Cómo suscribirse desde Apple Podcasts?', 'respuesta' => 'Copia el enlace del feed RSS de la serie y añádelo en Apple Podcasts.'],
            ['pregunta' => '¿Las estadísticas son en tiempo real?', 'respuesta' => 'Las reproducciones se registran en tiempo real, las estadísticas se actualizan cada hora.'],
        ];
    }

    /**
     * Configuración para el panel de administración unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id'         => 'podcast',
            'label'      => __('Podcast', 'flavor-chat-ia'),
            'icon'       => 'dashicons-playlist-audio',
            'capability' => 'manage_options',
            'categoria'  => 'comunicacion',
            'paginas'    => [
                [
                    'slug'     => 'flavor-podcast-dashboard',
                    'titulo'   => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_dashboard'],
                ],
                [
                    'slug'     => 'flavor-podcast-episodios',
                    'titulo'   => __('Episodios', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_episodios'],
                ],
                [
                    'slug'     => 'flavor-podcast-programas',
                    'titulo'   => __('Programas', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_programas'],
                ],
            ],
        ];
    }

    /**
     * Renderiza el dashboard de administración de Podcast
     */
    public function render_admin_dashboard() {
        global $wpdb;

        $total_series = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_series}");
        $total_episodios = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_episodios}");
        $total_reproducciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_reproducciones}");
        $total_suscripciones = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tabla_suscripciones}");

        $this->render_page_header(__('Dashboard de Podcast', 'flavor-chat-ia'), [
            [
                'label' => __('Nuevo Programa', 'flavor-chat-ia'),
                'url'   => $this->admin_page_url('flavor-podcast-programas') . '&action=nuevo',
                'class' => 'button-primary',
            ],
        ]);
        ?>
        <div class="flavor-admin-stats-grid">
            <div class="stat-card">
                <span class="dashicons dashicons-microphone"></span>
                <div class="stat-content">
                    <span class="stat-value"><?php echo esc_html(number_format_i18n($total_series)); ?></span>
                    <span class="stat-label"><?php esc_html_e('Programas', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <div class="stat-card">
                <span class="dashicons dashicons-playlist-audio"></span>
                <div class="stat-content">
                    <span class="stat-value"><?php echo esc_html(number_format_i18n($total_episodios)); ?></span>
                    <span class="stat-label"><?php esc_html_e('Episodios', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <div class="stat-card">
                <span class="dashicons dashicons-controls-play"></span>
                <div class="stat-content">
                    <span class="stat-value"><?php echo esc_html(number_format_i18n($total_reproducciones)); ?></span>
                    <span class="stat-label"><?php esc_html_e('Reproducciones', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <div class="stat-card">
                <span class="dashicons dashicons-groups"></span>
                <div class="stat-content">
                    <span class="stat-value"><?php echo esc_html(number_format_i18n($total_suscripciones)); ?></span>
                    <span class="stat-label"><?php esc_html_e('Suscripciones', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la página de administración de episodios
     */
    public function render_admin_episodios() {
        global $wpdb;

        $this->render_page_header(__('Episodios', 'flavor-chat-ia'), [
            [
                'label' => __('Nuevo Episodio', 'flavor-chat-ia'),
                'url'   => $this->admin_page_url('flavor-podcast-episodios') . '&action=nuevo',
                'class' => 'button-primary',
            ],
        ]);

        $episodios = $wpdb->get_results(
            "SELECT e.*, s.titulo AS serie_titulo
             FROM {$this->tabla_episodios} e
             LEFT JOIN {$this->tabla_series} s ON e.serie_id = s.id
             ORDER BY e.fecha_publicacion DESC
             LIMIT 50"
        );
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Título', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Programa', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Duración', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($episodios)): ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e('No hay episodios disponibles.', 'flavor-chat-ia'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($episodios as $episodio): ?>
                        <tr>
                            <td><strong><?php echo esc_html($episodio->titulo); ?></strong></td>
                            <td><?php echo esc_html($episodio->serie_titulo); ?></td>
                            <td><?php echo esc_html(gmdate('H:i:s', $episodio->duracion)); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($episodio->fecha_publicacion))); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($episodio->estado); ?>">
                                    <?php echo esc_html(ucfirst($episodio->estado)); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Renderiza la página de administración de programas (series)
     */
    public function render_admin_programas() {
        global $wpdb;

        $this->render_page_header(__('Programas', 'flavor-chat-ia'), [
            [
                'label' => __('Nuevo Programa', 'flavor-chat-ia'),
                'url'   => $this->admin_page_url('flavor-podcast-programas') . '&action=nuevo',
                'class' => 'button-primary',
            ],
        ]);

        $programas = $wpdb->get_results(
            "SELECT s.*,
                    (SELECT COUNT(*) FROM {$this->tabla_episodios} e WHERE e.serie_id = s.id) AS total_episodios,
                    (SELECT COUNT(*) FROM {$this->tabla_suscripciones} sub WHERE sub.serie_id = s.id) AS total_suscriptores
             FROM {$this->tabla_series} s
             ORDER BY s.fecha_creacion DESC
             LIMIT 50"
        );
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Programa', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Autor', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Episodios', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Suscriptores', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($programas)): ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e('No hay programas disponibles.', 'flavor-chat-ia'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($programas as $programa): ?>
                        <?php $autor = get_userdata($programa->autor_id); ?>
                        <tr>
                            <td><strong><?php echo esc_html($programa->titulo); ?></strong></td>
                            <td><?php echo $autor ? esc_html($autor->display_name) : '-'; ?></td>
                            <td><?php echo esc_html(number_format_i18n($programa->total_episodios)); ?></td>
                            <td><?php echo esc_html(number_format_i18n($programa->total_suscriptores)); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($programa->estado); ?>">
                                    <?php echo esc_html(ucfirst($programa->estado)); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }

    /**
     * Crea páginas frontend automáticamente
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('podcast');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('podcast');
        if (!$pagina && !get_option('flavor_podcast_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['podcast']);
            update_option('flavor_podcast_pages_created', 1, false);
        }
    }

    /**
     * Obtiene estadísticas para el dashboard del cliente
     *
     * @return array Estadísticas del módulo
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $estadisticas = [];

        $tabla_series = $wpdb->prefix . 'flavor_podcast_series';
        $tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
        $tabla_suscripciones = $wpdb->prefix . 'flavor_podcast_suscripciones';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_series)) {
            return $estadisticas;
        }

        // Total de series publicadas
        $total_series = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_series} WHERE estado = 'publicado'"
        );

        $estadisticas['series'] = [
            'icon' => 'dashicons-microphone',
            'valor' => $total_series,
            'label' => __('Series', 'flavor-chat-ia'),
            'color' => 'purple',
        ];

        // Episodios disponibles
        if (Flavor_Chat_Helpers::tabla_existe($tabla_episodios)) {
            $total_episodios = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_episodios} WHERE estado = 'publicado'"
            );

            $estadisticas['episodios'] = [
                'icon' => 'dashicons-playlist-audio',
                'valor' => $total_episodios,
                'label' => __('Episodios', 'flavor-chat-ia'),
                'color' => 'blue',
            ];
        }

        $usuario_id = get_current_user_id();
        if ($usuario_id && Flavor_Chat_Helpers::tabla_existe($tabla_suscripciones)) {
            // Mis suscripciones (todas las suscripciones activas, no hay columna estado)
            $mis_suscripciones = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_suscripciones}
                 WHERE usuario_id = %d",
                $usuario_id
            ));

            if ($mis_suscripciones > 0) {
                $estadisticas['suscripciones'] = [
                    'icon' => 'dashicons-rss',
                    'valor' => $mis_suscripciones,
                    'label' => __('Suscripciones', 'flavor-chat-ia'),
                    'color' => 'green',
                ];
            }
        }

        return $estadisticas;
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Podcasts', 'flavor-chat-ia'),
                'slug' => 'podcast',
                'content' => '<h1>' . __('Podcasts', 'flavor-chat-ia') . '</h1>
<p>' . __('Descubre y escucha los mejores podcasts de nuestra comunidad. Explora programas sobre diversos temas, suscríbete a tus favoritos y disfruta de contenido de calidad.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="podcast" action="listar" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Crear Podcast', 'flavor-chat-ia'),
                'slug' => 'crear-podcast',
                'content' => '<h1>' . __('Crear Podcast', 'flavor-chat-ia') . '</h1>
<p>' . __('Crea tu propio programa de podcast y comparte tu contenido con la comunidad. Sube episodios, gestiona suscriptores y haz crecer tu audiencia.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="podcast" action="crear"]',
                'parent' => 'podcast',
            ],
            [
                'title' => __('Mis Podcasts', 'flavor-chat-ia'),
                'slug' => 'mis-podcasts',
                'content' => '<h1>' . __('Mis Podcasts', 'flavor-chat-ia') . '</h1>
<p>' . __('Gestiona tus programas de podcast, revisa estadísticas y administra tus episodios publicados.', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="podcast" action="mis_items"]',
                'parent' => 'podcast',
            ],
        ];
    }
}
