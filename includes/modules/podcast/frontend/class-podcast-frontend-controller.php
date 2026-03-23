<?php
/**
 * Frontend Controller para Podcast
 *
 * @package FlavorChatIA
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador frontend del módulo Podcast
 */
class Flavor_Podcast_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Podcast_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * Configuración del módulo
     * @var array
     */
    private $config = [];

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->config = get_option('flavor_chat_ia_settings', []);
        $this->init();
    }

    /**
     * Obtiene instancia singleton
     * @return Flavor_Podcast_Frontend_Controller
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa el controlador
     */
    private function init() {
        // Shortcodes
        $shortcodes = [
            'flavor_podcast_catalogo' => 'shortcode_catalogo',
            'flavor_podcast_series' => 'shortcode_series',
            'flavor_podcast_serie' => 'shortcode_serie',
            'flavor_podcast_episodio' => 'shortcode_episodio',
            'flavor_podcast_player' => 'shortcode_player',
            'flavor_podcast_mis_suscripciones' => 'shortcode_mis_suscripciones',
            'flavor_podcast_crear_serie' => 'shortcode_crear_serie',
            'flavor_podcast_subir_episodio' => 'shortcode_subir_episodio',
            'flavor_podcast_estadisticas' => 'shortcode_estadisticas',
        ];
        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }

        // AJAX handlers
        add_action('wp_ajax_flavor_podcast_suscribir', [$this, 'ajax_suscribir']);
        add_action('wp_ajax_flavor_podcast_crear_serie', [$this, 'ajax_crear_serie']);
        add_action('wp_ajax_flavor_podcast_subir_episodio', [$this, 'ajax_subir_episodio']);
        add_action('wp_ajax_flavor_podcast_registrar_reproduccion', [$this, 'ajax_registrar_reproduccion']);
        add_action('wp_ajax_nopriv_flavor_podcast_registrar_reproduccion', [$this, 'ajax_registrar_reproduccion']);
        add_action('wp_ajax_flavor_podcast_like', [$this, 'ajax_like']);
        add_action('wp_ajax_flavor_podcast_comentar', [$this, 'ajax_comentar']);
        add_action('wp_ajax_flavor_podcast_buscar', [$this, 'ajax_buscar']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_dashboard_tabs']);
    }

    /**
     * Registra assets CSS y JS
     */
    public function registrar_assets() {
        $base_url = plugin_dir_url(dirname(__FILE__));
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_register_style(
            'flavor-podcast-frontend',
            $base_url . 'assets/css/podcast-frontend.css',
            [],
            $version
        );

        wp_register_script(
            'flavor-podcast-frontend',
            $base_url . 'assets/js/podcast-frontend.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-podcast-frontend', 'flavorPodcastConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_podcast_nonce'),
            'strings' => [
                'procesando' => __('Procesando...', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'suscrito' => __('Te has suscrito correctamente', 'flavor-chat-ia'),
                'desuscrito' => __('Suscripción cancelada', 'flavor-chat-ia'),
                'reproduciendo' => __('Reproduciendo', 'flavor-chat-ia'),
                'pausado' => __('Pausado', 'flavor-chat-ia'),
                'cargando' => __('Cargando audio...', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Carga assets en frontend
     */
    private function cargar_assets() {
        wp_enqueue_style('flavor-podcast-frontend');
        wp_enqueue_script('flavor-podcast-frontend');
    }

    /**
     * Registra tabs en dashboard del usuario
     */
    public function registrar_dashboard_tabs($tabs) {
        $tabs['podcast'] = [
            'titulo' => __('Podcast', 'flavor-chat-ia'),
            'icono' => 'dashicons-microphone',
            'prioridad' => 55,
            'callback' => [$this, 'render_dashboard_tab'],
        ];
        return $tabs;
    }

    /**
     * Renderiza tab del dashboard
     */
    public function render_dashboard_tab() {
        $this->cargar_assets();
        global $wpdb;
        $usuario_id = get_current_user_id();
        $tabla_series = $wpdb->prefix . 'flavor_podcast_series';
        $tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
        $tabla_suscripciones = $wpdb->prefix . 'flavor_podcast_suscripciones';
        $tabla_reproducciones = $wpdb->prefix . 'flavor_podcast_reproducciones';

        // Mis series
        $mis_series = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*,
                    (SELECT COUNT(*) FROM $tabla_episodios WHERE serie_id = s.id) as total_episodios
             FROM $tabla_series s
             WHERE s.autor_id = %d
             ORDER BY s.fecha_creacion DESC",
            $usuario_id
        ));

        // Mis suscripciones
        $suscripciones = $wpdb->get_results($wpdb->prepare(
            "SELECT sus.*, s.titulo, s.imagen_url, s.autor_id,
                    (SELECT COUNT(*) FROM $tabla_episodios WHERE serie_id = s.id AND fecha_publicacion > sus.fecha_suscripcion) as nuevos
             FROM $tabla_suscripciones sus
             JOIN $tabla_series s ON sus.serie_id = s.id
             WHERE sus.usuario_id = %d AND sus.estado = 'activa'
             ORDER BY sus.fecha_suscripcion DESC",
            $usuario_id
        ));

        // Estadísticas
        $estadisticas = $wpdb->get_row($wpdb->prepare(
            "SELECT
                (SELECT COUNT(*) FROM $tabla_series WHERE autor_id = %d) as series_creadas,
                (SELECT COUNT(*) FROM $tabla_episodios e JOIN $tabla_series s ON e.serie_id = s.id WHERE s.autor_id = %d) as episodios_publicados,
                (SELECT SUM(r.duracion_escuchada) FROM $tabla_reproducciones r JOIN $tabla_episodios e ON r.episodio_id = e.id JOIN $tabla_series s ON e.serie_id = s.id WHERE s.autor_id = %d) as minutos_escuchados,
                (SELECT COUNT(*) FROM $tabla_suscripciones WHERE usuario_id = %d AND estado = 'activa') as suscripciones
            ",
            $usuario_id, $usuario_id, $usuario_id, $usuario_id
        ));

        // Episodios recientes de suscripciones
        $episodios_recientes = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, s.titulo as serie_titulo, s.imagen_url as serie_imagen
             FROM $tabla_episodios e
             JOIN $tabla_series s ON e.serie_id = s.id
             JOIN $tabla_suscripciones sus ON s.id = sus.serie_id
             WHERE sus.usuario_id = %d AND sus.estado = 'activa' AND e.estado = 'publicado'
             ORDER BY e.fecha_publicacion DESC
             LIMIT 5",
            $usuario_id
        ));

        ob_start();
        ?>
        <div class="flavor-podcast-dashboard">
            <!-- KPIs -->
            <div class="flavor-kpi-grid">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icono dashicons dashicons-microphone"></span>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo intval($estadisticas->series_creadas ?? 0); ?></span>
                        <span class="flavor-kpi-etiqueta"><?php _e('Mis series', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icono dashicons dashicons-format-audio"></span>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo intval($estadisticas->episodios_publicados ?? 0); ?></span>
                        <span class="flavor-kpi-etiqueta"><?php _e('Episodios', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icono dashicons dashicons-heart"></span>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo intval($estadisticas->suscripciones ?? 0); ?></span>
                        <span class="flavor-kpi-etiqueta"><?php _e('Suscripciones', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icono dashicons dashicons-clock"></span>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo $this->formatear_duracion($estadisticas->minutos_escuchados ?? 0); ?></span>
                        <span class="flavor-kpi-etiqueta"><?php _e('Escuchados', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Episodios recientes de suscripciones -->
            <?php if (!empty($episodios_recientes)): ?>
            <div class="flavor-panel">
                <h3 class="flavor-panel-titulo">
                    <span class="dashicons dashicons-playlist-audio"></span>
                    <?php _e('Novedades', 'flavor-chat-ia'); ?>
                </h3>
                <div class="flavor-episodios-lista">
                    <?php foreach ($episodios_recientes as $episodio): ?>
                    <div class="flavor-episodio-item" data-id="<?php echo intval($episodio->id); ?>">
                        <div class="flavor-episodio-cover">
                            <?php if (!empty($episodio->serie_imagen)): ?>
                            <img src="<?php echo esc_url($episodio->serie_imagen); ?>" alt="">
                            <?php else: ?>
                            <span class="dashicons dashicons-format-audio"></span>
                            <?php endif; ?>
                            <button class="flavor-btn-play" data-audio="<?php echo esc_url($episodio->audio_url); ?>">
                                <span class="dashicons dashicons-controls-play"></span>
                            </button>
                        </div>
                        <div class="flavor-episodio-info">
                            <h4><?php echo esc_html($episodio->titulo); ?></h4>
                            <span class="flavor-serie-nombre"><?php echo esc_html($episodio->serie_titulo); ?></span>
                            <div class="flavor-episodio-meta">
                                <span><?php echo date_i18n('j M', strtotime($episodio->fecha_publicacion)); ?></span>
                                <span><?php echo $this->formatear_duracion($episodio->duracion_segundos / 60); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Mis series -->
            <div class="flavor-panel">
                <div class="flavor-panel-header">
                    <h3 class="flavor-panel-titulo">
                        <span class="dashicons dashicons-microphone"></span>
                        <?php _e('Mis series', 'flavor-chat-ia'); ?>
                    </h3>
                    <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-sm" id="btn-crear-serie">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Nueva serie', 'flavor-chat-ia'); ?>
                    </button>
                </div>
                <?php if (!empty($mis_series)): ?>
                <div class="flavor-series-grid">
                    <?php foreach ($mis_series as $serie): ?>
                    <div class="flavor-serie-card">
                        <div class="flavor-serie-cover">
                            <?php if (!empty($serie->imagen_url)): ?>
                            <img src="<?php echo esc_url($serie->imagen_url); ?>" alt="">
                            <?php else: ?>
                            <span class="dashicons dashicons-microphone"></span>
                            <?php endif; ?>
                        </div>
                        <div class="flavor-serie-info">
                            <h4><?php echo esc_html($serie->titulo); ?></h4>
                            <span class="flavor-badge"><?php echo intval($serie->total_episodios); ?> episodios</span>
                        </div>
                        <div class="flavor-serie-acciones">
                            <a href="?serie=<?php echo intval($serie->id); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                <?php _e('Ver', 'flavor-chat-ia'); ?>
                            </a>
                            <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-primary flavor-btn-subir-episodio"
                                    data-serie-id="<?php echo intval($serie->id); ?>">
                                <span class="dashicons dashicons-plus"></span>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="flavor-vacio"><?php _e('Aún no has creado ninguna serie', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Suscripciones -->
            <div class="flavor-panel">
                <h3 class="flavor-panel-titulo">
                    <span class="dashicons dashicons-heart"></span>
                    <?php _e('Suscripciones', 'flavor-chat-ia'); ?>
                </h3>
                <?php if (!empty($suscripciones)): ?>
                <div class="flavor-suscripciones-lista">
                    <?php foreach ($suscripciones as $sus): ?>
                    <div class="flavor-suscripcion-item">
                        <div class="flavor-suscripcion-cover">
                            <?php if (!empty($sus->imagen_url)): ?>
                            <img src="<?php echo esc_url($sus->imagen_url); ?>" alt="">
                            <?php else: ?>
                            <span class="dashicons dashicons-microphone"></span>
                            <?php endif; ?>
                        </div>
                        <div class="flavor-suscripcion-info">
                            <h4><?php echo esc_html($sus->titulo); ?></h4>
                            <?php if ($sus->nuevos > 0): ?>
                            <span class="flavor-badge flavor-badge-nuevo"><?php echo intval($sus->nuevos); ?> nuevos</span>
                            <?php endif; ?>
                        </div>
                        <a href="?serie=<?php echo intval($sus->serie_id); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                            <?php _e('Escuchar', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="flavor-vacio"><?php _e('No tienes suscripciones activas', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Catálogo de podcasts
     */
    public function shortcode_catalogo($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_series = $wpdb->prefix . 'flavor_podcast_series';
        $tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';

        $atts = shortcode_atts([
            'categoria' => '',
            'limite' => 12,
            'orden' => 'recientes',
        ], $atts);

        $where = ["s.estado = 'publicada'"];
        $params = [];

        if (!empty($atts['categoria'])) {
            $where[] = "s.categoria = %s";
            $params[] = sanitize_text_field($atts['categoria']);
        }

        $orden = $atts['orden'] === 'populares'
            ? "ORDER BY s.total_suscriptores DESC"
            : "ORDER BY s.fecha_creacion DESC";

        $sql = "SELECT s.*, u.display_name as autor_nombre,
                       (SELECT COUNT(*) FROM $tabla_episodios WHERE serie_id = s.id AND estado = 'publicado') as total_episodios
                FROM $tabla_series s
                JOIN {$wpdb->users} u ON s.autor_id = u.ID
                WHERE " . implode(' AND ', $where) . "
                $orden
                LIMIT " . intval($atts['limite']);

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $series = $wpdb->get_results($sql);

        ob_start();
        ?>
        <div class="flavor-podcast-catalogo">
            <!-- Filtros -->
            <div class="flavor-filtros">
                <select id="filtro-categoria-podcast" class="flavor-select">
                    <option value=""><?php _e('Todas las categorías', 'flavor-chat-ia'); ?></option>
                    <option value="noticias"><?php _e('Noticias', 'flavor-chat-ia'); ?></option>
                    <option value="entrevistas"><?php _e('Entrevistas', 'flavor-chat-ia'); ?></option>
                    <option value="historias"><?php _e('Historias', 'flavor-chat-ia'); ?></option>
                    <option value="debates"><?php _e('Debates', 'flavor-chat-ia'); ?></option>
                    <option value="cultura"><?php _e('Cultura', 'flavor-chat-ia'); ?></option>
                    <option value="educacion"><?php _e('Educación', 'flavor-chat-ia'); ?></option>
                </select>
                <div class="flavor-busqueda">
                    <input type="text" id="buscar-podcast" placeholder="<?php esc_attr_e('Buscar podcasts...', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-search"></span>
                </div>
            </div>

            <!-- Grid de series -->
            <div class="flavor-series-grid" id="series-container">
                <?php foreach ($series as $serie): ?>
                <article class="flavor-serie-card" data-categoria="<?php echo esc_attr($serie->categoria); ?>">
                    <a href="?serie=<?php echo intval($serie->id); ?>" class="flavor-serie-link">
                        <div class="flavor-serie-cover">
                            <?php if (!empty($serie->imagen_url)): ?>
                            <img src="<?php echo esc_url($serie->imagen_url); ?>" alt="<?php echo esc_attr($serie->titulo); ?>">
                            <?php else: ?>
                            <span class="dashicons dashicons-microphone"></span>
                            <?php endif; ?>
                            <div class="flavor-serie-overlay">
                                <span class="dashicons dashicons-controls-play"></span>
                            </div>
                        </div>
                        <div class="flavor-serie-info">
                            <h3><?php echo esc_html($serie->titulo); ?></h3>
                            <span class="flavor-autor"><?php echo esc_html($serie->autor_nombre); ?></span>
                            <div class="flavor-serie-meta">
                                <span class="flavor-badge"><?php echo esc_html(ucfirst($serie->categoria)); ?></span>
                                <span><?php echo intval($serie->total_episodios); ?> ep.</span>
                            </div>
                        </div>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de serie
     */
    public function shortcode_serie($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_series = $wpdb->prefix . 'flavor_podcast_series';
        $tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
        $tabla_suscripciones = $wpdb->prefix . 'flavor_podcast_suscripciones';

        $atts = shortcode_atts([
            'id' => isset($_GET['serie']) ? absint($_GET['serie']) : 0,
        ], $atts);

        if (!$atts['id']) {
            return $this->shortcode_catalogo([]);
        }

        $serie = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, u.display_name as autor_nombre
             FROM $tabla_series s
             JOIN {$wpdb->users} u ON s.autor_id = u.ID
             WHERE s.id = %d",
            $atts['id']
        ));

        if (!$serie) {
            return '<p class="flavor-error">' . __('Serie no encontrada', 'flavor-chat-ia') . '</p>';
        }

        $episodios = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_episodios WHERE serie_id = %d AND estado = 'publicado' ORDER BY numero_episodio DESC",
            $atts['id']
        ));

        // Verificar suscripción
        $suscrito = false;
        if (is_user_logged_in()) {
            $suscrito = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_suscripciones WHERE serie_id = %d AND usuario_id = %d AND estado = 'activa'",
                $atts['id'], get_current_user_id()
            )) > 0;
        }

        ob_start();
        ?>
        <div class="flavor-podcast-serie">
            <!-- Header de la serie -->
            <header class="flavor-serie-header">
                <div class="flavor-serie-cover-grande">
                    <?php if (!empty($serie->imagen_url)): ?>
                    <img src="<?php echo esc_url($serie->imagen_url); ?>" alt="">
                    <?php else: ?>
                    <span class="dashicons dashicons-microphone"></span>
                    <?php endif; ?>
                </div>
                <div class="flavor-serie-detalles">
                    <span class="flavor-badge"><?php echo esc_html(ucfirst($serie->categoria)); ?></span>
                    <h1><?php echo esc_html($serie->titulo); ?></h1>
                    <p class="flavor-autor">
                        <?php _e('Por', 'flavor-chat-ia'); ?> <?php echo esc_html($serie->autor_nombre); ?>
                    </p>
                    <?php if (!empty($serie->descripcion)): ?>
                    <div class="flavor-descripcion"><?php echo wp_kses_post($serie->descripcion); ?></div>
                    <?php endif; ?>
                    <div class="flavor-serie-stats">
                        <span><strong><?php echo count($episodios); ?></strong> <?php _e('episodios', 'flavor-chat-ia'); ?></span>
                        <span><strong><?php echo intval($serie->total_suscriptores); ?></strong> <?php _e('suscriptores', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="flavor-serie-acciones">
                        <?php if (is_user_logged_in()): ?>
                        <button type="button" class="flavor-btn <?php echo $suscrito ? 'flavor-btn-outline flavor-suscrito' : 'flavor-btn-primary'; ?> flavor-btn-suscribir"
                                data-serie-id="<?php echo intval($serie->id); ?>">
                            <span class="dashicons dashicons-<?php echo $suscrito ? 'yes' : 'heart'; ?>"></span>
                            <?php echo $suscrito ? __('Suscrito', 'flavor-chat-ia') : __('Suscribirse', 'flavor-chat-ia'); ?>
                        </button>
                        <?php endif; ?>
                        <?php if (!empty($serie->rss_url)): ?>
                        <a href="<?php echo esc_url($serie->rss_url); ?>" class="flavor-btn flavor-btn-outline" target="_blank">
                            <span class="dashicons dashicons-rss"></span>
                            RSS
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <!-- Lista de episodios -->
            <section class="flavor-episodios-seccion">
                <h2><?php _e('Episodios', 'flavor-chat-ia'); ?></h2>
                <?php if (!empty($episodios)): ?>
                <div class="flavor-episodios-lista-completa">
                    <?php foreach ($episodios as $episodio): ?>
                    <article class="flavor-episodio-row" data-id="<?php echo intval($episodio->id); ?>">
                        <div class="flavor-episodio-numero">
                            <?php echo intval($episodio->numero_episodio); ?>
                        </div>
                        <button class="flavor-btn-play-mini" data-audio="<?php echo esc_url($episodio->audio_url); ?>">
                            <span class="dashicons dashicons-controls-play"></span>
                        </button>
                        <div class="flavor-episodio-contenido">
                            <h3><?php echo esc_html($episodio->titulo); ?></h3>
                            <?php if (!empty($episodio->descripcion)): ?>
                            <p><?php echo wp_trim_words($episodio->descripcion, 20); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flavor-episodio-meta-col">
                            <span class="flavor-fecha"><?php echo date_i18n('j M Y', strtotime($episodio->fecha_publicacion)); ?></span>
                            <span class="flavor-duracion"><?php echo $this->formatear_duracion($episodio->duracion_segundos / 60); ?></span>
                        </div>
                        <div class="flavor-episodio-acciones">
                            <a href="?episodio=<?php echo intval($episodio->id); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                <?php _e('Detalles', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="flavor-vacio"><?php _e('Esta serie aún no tiene episodios', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </section>
        </div>

        <!-- Player flotante -->
        <div id="flavor-player-flotante" class="flavor-player" style="display:none;">
            <audio id="flavor-audio-player"></audio>
            <div class="flavor-player-info">
                <span id="player-titulo"></span>
            </div>
            <div class="flavor-player-controles">
                <button id="player-prev" class="flavor-player-btn"><span class="dashicons dashicons-controls-skipback"></span></button>
                <button id="player-play" class="flavor-player-btn flavor-player-btn-main"><span class="dashicons dashicons-controls-play"></span></button>
                <button id="player-next" class="flavor-player-btn"><span class="dashicons dashicons-controls-skipforward"></span></button>
            </div>
            <div class="flavor-player-progreso">
                <span id="player-tiempo-actual">0:00</span>
                <input type="range" id="player-barra" min="0" max="100" value="0">
                <span id="player-duracion">0:00</span>
            </div>
            <div class="flavor-player-volumen">
                <span class="dashicons dashicons-controls-volumeon"></span>
                <input type="range" id="player-volumen" min="0" max="100" value="80">
            </div>
            <button id="player-cerrar" class="flavor-player-cerrar"><span class="dashicons dashicons-no"></span></button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de episodio
     */
    public function shortcode_episodio($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
        $tabla_series = $wpdb->prefix . 'flavor_podcast_series';

        $atts = shortcode_atts([
            'id' => isset($_GET['episodio']) ? absint($_GET['episodio']) : 0,
        ], $atts);

        if (!$atts['id']) {
            return '';
        }

        $episodio = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, s.titulo as serie_titulo, s.imagen_url as serie_imagen, s.autor_id,
                    u.display_name as autor_nombre
             FROM $tabla_episodios e
             JOIN $tabla_series s ON e.serie_id = s.id
             JOIN {$wpdb->users} u ON s.autor_id = u.ID
             WHERE e.id = %d",
            $atts['id']
        ));

        if (!$episodio) {
            return '<p class="flavor-error">' . __('Episodio no encontrado', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-episodio-detalle">
            <div class="flavor-episodio-header">
                <a href="?serie=<?php echo intval($episodio->serie_id); ?>" class="flavor-volver">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php echo esc_html($episodio->serie_titulo); ?>
                </a>
            </div>

            <div class="flavor-episodio-contenido-principal">
                <div class="flavor-episodio-cover-grande">
                    <?php if (!empty($episodio->serie_imagen)): ?>
                    <img src="<?php echo esc_url($episodio->serie_imagen); ?>" alt="">
                    <?php endif; ?>
                </div>

                <div class="flavor-episodio-info-detalle">
                    <span class="flavor-episodio-num">Episodio <?php echo intval($episodio->numero_episodio); ?></span>
                    <h1><?php echo esc_html($episodio->titulo); ?></h1>
                    <p class="flavor-autor"><?php echo esc_html($episodio->autor_nombre); ?></p>

                    <div class="flavor-episodio-meta-detalle">
                        <span><?php echo date_i18n('j F Y', strtotime($episodio->fecha_publicacion)); ?></span>
                        <span><?php echo $this->formatear_duracion($episodio->duracion_segundos / 60); ?></span>
                        <span><?php echo number_format($episodio->reproducciones ?? 0); ?> reproducciones</span>
                    </div>

                    <!-- Player inline -->
                    <div class="flavor-player-inline">
                        <audio controls class="flavor-audio-inline" src="<?php echo esc_url($episodio->audio_url); ?>"></audio>
                    </div>

                    <div class="flavor-episodio-acciones-detalle">
                        <?php if (is_user_logged_in()): ?>
                        <button type="button" class="flavor-btn flavor-btn-outline flavor-btn-like" data-episodio-id="<?php echo intval($episodio->id); ?>">
                            <span class="dashicons dashicons-heart"></span>
                            <?php _e('Me gusta', 'flavor-chat-ia'); ?>
                        </button>
                        <?php endif; ?>
                        <button type="button" class="flavor-btn flavor-btn-outline flavor-btn-compartir">
                            <span class="dashicons dashicons-share"></span>
                            <?php _e('Compartir', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <?php if (!empty($episodio->descripcion)): ?>
            <div class="flavor-episodio-descripcion">
                <h2><?php _e('Descripción', 'flavor-chat-ia'); ?></h2>
                <?php echo wp_kses_post($episodio->descripcion); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($episodio->notas)): ?>
            <div class="flavor-episodio-notas">
                <h2><?php _e('Notas del episodio', 'flavor-chat-ia'); ?></h2>
                <?php echo wp_kses_post($episodio->notas); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Player embebido
     */
    public function shortcode_player($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
        $tabla_series = $wpdb->prefix . 'flavor_podcast_series';

        $atts = shortcode_atts([
            'episodio_id' => 0,
            'serie_id' => 0,
            'estilo' => 'compacto', // compacto, completo
        ], $atts);

        if ($atts['episodio_id']) {
            $episodio = $wpdb->get_row($wpdb->prepare(
                "SELECT e.*, s.titulo as serie_titulo, s.imagen_url
                 FROM $tabla_episodios e
                 JOIN $tabla_series s ON e.serie_id = s.id
                 WHERE e.id = %d",
                $atts['episodio_id']
            ));

            if (!$episodio) return '';

            ob_start();
            ?>
            <div class="flavor-player-embed <?php echo esc_attr($atts['estilo']); ?>">
                <div class="flavor-player-cover">
                    <?php if (!empty($episodio->imagen_url)): ?>
                    <img src="<?php echo esc_url($episodio->imagen_url); ?>" alt="">
                    <?php endif; ?>
                </div>
                <div class="flavor-player-content">
                    <span class="flavor-player-serie"><?php echo esc_html($episodio->serie_titulo); ?></span>
                    <h4><?php echo esc_html($episodio->titulo); ?></h4>
                    <audio controls src="<?php echo esc_url($episodio->audio_url); ?>"></audio>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        return '';
    }

    /**
     * Shortcode: Estadísticas globales
     */
    public function shortcode_estadisticas($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_series = $wpdb->prefix . 'flavor_podcast_series';
        $tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
        $tabla_reproducciones = $wpdb->prefix . 'flavor_podcast_reproducciones';

        $stats = $wpdb->get_row(
            "SELECT
                (SELECT COUNT(*) FROM $tabla_series WHERE estado = 'publicada') as total_series,
                (SELECT COUNT(*) FROM $tabla_episodios WHERE estado = 'publicado') as total_episodios,
                (SELECT COUNT(*) FROM $tabla_reproducciones) as total_reproducciones,
                (SELECT COUNT(DISTINCT autor_id) FROM $tabla_series) as total_creadores"
        );

        ob_start();
        ?>
        <div class="flavor-podcast-estadisticas">
            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-microphone"></span>
                    <div class="flavor-stat-valor"><?php echo intval($stats->total_series ?? 0); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Series', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-format-audio"></span>
                    <div class="flavor-stat-valor"><?php echo intval($stats->total_episodios ?? 0); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Episodios', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-controls-play"></span>
                    <div class="flavor-stat-valor"><?php echo number_format($stats->total_reproducciones ?? 0); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Reproducciones', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-groups"></span>
                    <div class="flavor-stat-valor"><?php echo intval($stats->total_creadores ?? 0); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Creadores', 'flavor-chat-ia'); ?></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // ==========================================
    // AJAX HANDLERS
    // ==========================================

    /**
     * AJAX: Suscribirse a serie
     */
    public function ajax_suscribir() {
        check_ajax_referer('flavor_podcast_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $serie_id = absint($_POST['serie_id'] ?? 0);
        $usuario_id = get_current_user_id();
        $tabla_suscripciones = $wpdb->prefix . 'flavor_podcast_suscripciones';
        $tabla_series = $wpdb->prefix . 'flavor_podcast_series';

        // Verificar suscripción existente
        $existente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_suscripciones WHERE serie_id = %d AND usuario_id = %d",
            $serie_id, $usuario_id
        ));

        if ($existente) {
            if ($existente->estado === 'activa') {
                // Cancelar suscripción
                $wpdb->update(
                    $tabla_suscripciones,
                    ['estado' => 'cancelada'],
                    ['id' => $existente->id]
                );
                $wpdb->query($wpdb->prepare(
                    "UPDATE $tabla_series SET total_suscriptores = GREATEST(0, total_suscriptores - 1) WHERE id = %d",
                    $serie_id
                ));
                wp_send_json_success([
                    'message' => __('Suscripción cancelada', 'flavor-chat-ia'),
                    'suscrito' => false,
                ]);
            } else {
                // Reactivar
                $wpdb->update(
                    $tabla_suscripciones,
                    ['estado' => 'activa', 'fecha_suscripcion' => current_time('mysql')],
                    ['id' => $existente->id]
                );
                $wpdb->query($wpdb->prepare(
                    "UPDATE $tabla_series SET total_suscriptores = total_suscriptores + 1 WHERE id = %d",
                    $serie_id
                ));
                wp_send_json_success([
                    'message' => __('Te has suscrito correctamente', 'flavor-chat-ia'),
                    'suscrito' => true,
                ]);
            }
        } else {
            // Nueva suscripción
            $wpdb->insert($tabla_suscripciones, [
                'serie_id' => $serie_id,
                'usuario_id' => $usuario_id,
                'estado' => 'activa',
                'fecha_suscripcion' => current_time('mysql'),
            ]);
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_series SET total_suscriptores = total_suscriptores + 1 WHERE id = %d",
                $serie_id
            ));
            wp_send_json_success([
                'message' => __('Te has suscrito correctamente', 'flavor-chat-ia'),
                'suscrito' => true,
            ]);
        }
    }

    /**
     * AJAX: Crear serie
     */
    public function ajax_crear_serie() {
        check_ajax_referer('flavor_podcast_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_series = $wpdb->prefix . 'flavor_podcast_series';

        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = wp_kses_post($_POST['descripcion'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? 'noticias');

        if (empty($titulo)) {
            wp_send_json_error(['message' => __('El título es obligatorio', 'flavor-chat-ia')]);
        }

        $imagen_url = '';
        if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $attachment_id = media_handle_upload('imagen', 0);
            if (!is_wp_error($attachment_id)) {
                $imagen_url = wp_get_attachment_url($attachment_id);
            }
        }

        $resultado = $wpdb->insert($tabla_series, [
            'autor_id' => get_current_user_id(),
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'categoria' => $categoria,
            'imagen_url' => $imagen_url,
            'estado' => 'publicada',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al crear la serie', 'flavor-chat-ia')]);
        }

        wp_send_json_success([
            'message' => __('Serie creada correctamente', 'flavor-chat-ia'),
            'serie_id' => $wpdb->insert_id,
        ]);
    }

    /**
     * AJAX: Subir episodio
     */
    public function ajax_subir_episodio() {
        check_ajax_referer('flavor_podcast_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';
        $tabla_series = $wpdb->prefix . 'flavor_podcast_series';

        $serie_id = absint($_POST['serie_id'] ?? 0);
        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = wp_kses_post($_POST['descripcion'] ?? '');

        // Verificar que el usuario es autor de la serie
        $serie = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_series WHERE id = %d AND autor_id = %d",
            $serie_id, get_current_user_id()
        ));

        if (!$serie) {
            wp_send_json_error(['message' => __('No tienes permisos para esta serie', 'flavor-chat-ia')]);
        }

        if (empty($titulo)) {
            wp_send_json_error(['message' => __('El título es obligatorio', 'flavor-chat-ia')]);
        }

        // Subir audio
        if (empty($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __('El archivo de audio es obligatorio', 'flavor-chat-ia')]);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_upload('audio', 0);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()]);
        }

        $audio_url = wp_get_attachment_url($attachment_id);
        $metadata = wp_get_attachment_metadata($attachment_id);
        $duracion = isset($metadata['length']) ? intval($metadata['length']) : 0;

        // Obtener número de episodio
        $numero = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(MAX(numero_episodio), 0) + 1 FROM $tabla_episodios WHERE serie_id = %d",
            $serie_id
        ));

        $resultado = $wpdb->insert($tabla_episodios, [
            'serie_id' => $serie_id,
            'numero_episodio' => $numero,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'audio_url' => $audio_url,
            'duracion_segundos' => $duracion,
            'estado' => 'publicado',
            'fecha_publicacion' => current_time('mysql'),
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al crear el episodio', 'flavor-chat-ia')]);
        }

        wp_send_json_success([
            'message' => __('Episodio publicado correctamente', 'flavor-chat-ia'),
            'episodio_id' => $wpdb->insert_id,
        ]);
    }

    /**
     * AJAX: Registrar reproducción
     */
    public function ajax_registrar_reproduccion() {
        global $wpdb;
        $tabla_reproducciones = $wpdb->prefix . 'flavor_podcast_reproducciones';
        $tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';

        $episodio_id = absint($_POST['episodio_id'] ?? 0);
        $duracion = absint($_POST['duracion'] ?? 0);
        $completado = !empty($_POST['completado']);

        $wpdb->insert($tabla_reproducciones, [
            'episodio_id' => $episodio_id,
            'usuario_id' => get_current_user_id() ?: null,
            'duracion_escuchada' => $duracion,
            'completado' => $completado ? 1 : 0,
            'fecha' => current_time('mysql'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        // Incrementar contador
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_episodios SET reproducciones = reproducciones + 1 WHERE id = %d",
            $episodio_id
        ));

        wp_send_json_success();
    }

    /**
     * AJAX: Like
     */
    public function ajax_like() {
        check_ajax_referer('flavor_podcast_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $episodio_id = absint($_POST['episodio_id'] ?? 0);
        $tabla_likes = $wpdb->prefix . 'flavor_podcast_likes';
        $tabla_episodios = $wpdb->prefix . 'flavor_podcast_episodios';

        if (Flavor_Chat_Helpers::tabla_existe($tabla_likes)) {
            $existente = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla_likes WHERE episodio_id = %d AND usuario_id = %d",
                $episodio_id, get_current_user_id()
            ));

            if ($existente) {
                $wpdb->delete($tabla_likes, ['id' => $existente]);
                wp_send_json_success(['liked' => false]);
            } else {
                $wpdb->insert($tabla_likes, [
                    'episodio_id' => $episodio_id,
                    'usuario_id' => get_current_user_id(),
                    'fecha' => current_time('mysql'),
                ]);
                wp_send_json_success(['liked' => true]);
            }
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Buscar
     */
    public function ajax_buscar() {
        global $wpdb;
        $termino = sanitize_text_field($_POST['termino'] ?? '');
        $tabla_series = $wpdb->prefix . 'flavor_podcast_series';

        if (strlen($termino) < 2) {
            wp_send_json_success(['series' => []]);
        }

        $series = $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, imagen_url, categoria FROM $tabla_series
             WHERE estado = 'publicada' AND (titulo LIKE %s OR descripcion LIKE %s)
             ORDER BY total_suscriptores DESC
             LIMIT 10",
            '%' . $wpdb->esc_like($termino) . '%',
            '%' . $wpdb->esc_like($termino) . '%'
        ));

        wp_send_json_success(['series' => $series]);
    }

    // ==========================================
    // SHORTCODES ADICIONALES
    // ==========================================

    /**
     * Shortcode: Mis suscripciones a series
     */
    public function shortcode_mis_suscripciones($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-aviso">' . __('Inicia sesión para ver tus suscripciones.', 'flavor-chat-ia') . '</div>';
        }

        $this->cargar_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_suscripciones = $wpdb->prefix . 'flavor_podcast_suscripciones';
        $tabla_series = $wpdb->prefix . 'flavor_podcast_series';

        $suscripciones = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_suscripciones)) {
            $suscripciones = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, ps.nombre, ps.descripcion, ps.imagen, ps.autor
                 FROM $tabla_suscripciones s
                 INNER JOIN $tabla_series ps ON s.serie_id = ps.id
                 WHERE s.usuario_id = %d
                 ORDER BY s.fecha_suscripcion DESC",
                $usuario_id
            ));
        }

        ob_start();
        ?>
        <div class="flavor-podcast-mis-suscripciones">
            <h3><?php _e('Mis Suscripciones', 'flavor-chat-ia'); ?></h3>

            <?php if (empty($suscripciones)): ?>
                <div class="flavor-vacio">
                    <p><?php _e('No estás suscrito a ninguna serie.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(home_url('/podcast/')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php _e('Explorar series', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-podcast-series-grid">
                    <?php foreach ($suscripciones as $sub): ?>
                        <div class="flavor-podcast-serie-card">
                            <?php if (!empty($sub->imagen)): ?>
                                <img src="<?php echo esc_url($sub->imagen); ?>" alt="" class="flavor-serie-imagen">
                            <?php endif; ?>
                            <div class="flavor-serie-info">
                                <h4><?php echo esc_html($sub->nombre); ?></h4>
                                <p class="flavor-serie-autor"><?php echo esc_html($sub->autor); ?></p>
                                <p class="flavor-serie-fecha">
                                    <?php printf(__('Suscrito desde %s', 'flavor-chat-ia'),
                                        date_i18n('d/m/Y', strtotime($sub->fecha_suscripcion))); ?>
                                </p>
                            </div>
                            <div class="flavor-serie-acciones">
                                <a href="<?php echo esc_url(add_query_arg('serie_id', $sub->serie_id)); ?>"
                                   class="flavor-btn flavor-btn-sm"><?php _e('Ver serie', 'flavor-chat-ia'); ?></a>
                                <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-outline flavor-cancelar-suscripcion"
                                        data-serie-id="<?php echo esc_attr($sub->serie_id); ?>">
                                    <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Crear serie de podcast
     */
    public function shortcode_crear_serie($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-aviso">' . __('Inicia sesión para crear una serie.', 'flavor-chat-ia') . '</div>';
        }

        $this->cargar_assets();

        ob_start();
        ?>
        <div class="flavor-podcast-crear-serie">
            <h3><?php _e('Crear Nueva Serie', 'flavor-chat-ia'); ?></h3>
            <p class="flavor-intro"><?php _e('Crea tu propia serie de podcast para compartir contenido de audio.', 'flavor-chat-ia'); ?></p>

            <form id="form-crear-serie-podcast" class="flavor-form" enctype="multipart/form-data">
                <?php wp_nonce_field('flavor_podcast_nonce', 'podcast_nonce'); ?>

                <div class="flavor-form-grupo">
                    <label for="serie_nombre"><?php _e('Nombre de la serie *', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="serie_nombre" name="nombre" required
                           placeholder="<?php esc_attr_e('Ej: Mi podcast sobre tecnología', 'flavor-chat-ia'); ?>">
                </div>

                <div class="flavor-form-grupo">
                    <label for="serie_descripcion"><?php _e('Descripción *', 'flavor-chat-ia'); ?></label>
                    <textarea id="serie_descripcion" name="descripcion" rows="4" required
                              placeholder="<?php esc_attr_e('Describe de qué trata tu serie...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="flavor-form-grupo">
                    <label for="serie_categoria"><?php _e('Categoría', 'flavor-chat-ia'); ?></label>
                    <select id="serie_categoria" name="categoria">
                        <option value=""><?php _e('Selecciona...', 'flavor-chat-ia'); ?></option>
                        <option value="cultura"><?php _e('Cultura', 'flavor-chat-ia'); ?></option>
                        <option value="tecnologia"><?php _e('Tecnología', 'flavor-chat-ia'); ?></option>
                        <option value="sociedad"><?php _e('Sociedad', 'flavor-chat-ia'); ?></option>
                        <option value="entretenimiento"><?php _e('Entretenimiento', 'flavor-chat-ia'); ?></option>
                        <option value="educacion"><?php _e('Educación', 'flavor-chat-ia'); ?></option>
                        <option value="otro"><?php _e('Otro', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>

                <div class="flavor-form-grupo">
                    <label for="serie_imagen"><?php _e('Imagen de portada', 'flavor-chat-ia'); ?></label>
                    <input type="file" id="serie_imagen" name="imagen" accept="image/*">
                    <p class="flavor-ayuda"><?php _e('Recomendado: 1400x1400px', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="flavor-form-acciones">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Crear Serie', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Subir episodio
     */
    public function shortcode_subir_episodio($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-aviso">' . __('Inicia sesión para subir episodios.', 'flavor-chat-ia') . '</div>';
        }

        $this->cargar_assets();
        $usuario_id = get_current_user_id();

        // Obtener series del usuario
        global $wpdb;
        $tabla_series = $wpdb->prefix . 'flavor_podcast_series';
        $mis_series = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_series)) {
            $mis_series = $wpdb->get_results($wpdb->prepare(
                "SELECT id, nombre FROM $tabla_series WHERE usuario_id = %d ORDER BY nombre ASC",
                $usuario_id
            ));
        }

        ob_start();
        ?>
        <div class="flavor-podcast-subir-episodio">
            <h3><?php _e('Subir Episodio', 'flavor-chat-ia'); ?></h3>

            <?php if (empty($mis_series)): ?>
                <div class="flavor-aviso">
                    <p><?php _e('Primero debes crear una serie para poder subir episodios.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('podcast', 'crear-serie')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php _e('Crear serie', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <form id="form-subir-episodio" class="flavor-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('flavor_podcast_nonce', 'podcast_nonce'); ?>

                    <div class="flavor-form-grupo">
                        <label for="episodio_serie"><?php _e('Serie *', 'flavor-chat-ia'); ?></label>
                        <select id="episodio_serie" name="serie_id" required>
                            <option value=""><?php _e('Selecciona una serie...', 'flavor-chat-ia'); ?></option>
                            <?php foreach ($mis_series as $serie): ?>
                                <option value="<?php echo esc_attr($serie->id); ?>"><?php echo esc_html($serie->nombre); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flavor-form-grupo">
                        <label for="episodio_titulo"><?php _e('Título del episodio *', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="episodio_titulo" name="titulo" required>
                    </div>

                    <div class="flavor-form-grupo">
                        <label for="episodio_descripcion"><?php _e('Descripción', 'flavor-chat-ia'); ?></label>
                        <textarea id="episodio_descripcion" name="descripcion" rows="3"></textarea>
                    </div>

                    <div class="flavor-form-grupo">
                        <label for="episodio_audio"><?php _e('Archivo de audio *', 'flavor-chat-ia'); ?></label>
                        <input type="file" id="episodio_audio" name="audio" accept="audio/*" required>
                        <p class="flavor-ayuda"><?php _e('Formatos: MP3, M4A, WAV. Máximo 100MB.', 'flavor-chat-ia'); ?></p>
                    </div>

                    <div class="flavor-form-acciones">
                        <button type="submit" class="flavor-btn flavor-btn-primary">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Subir Episodio', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Formatea duración en minutos
     */
    private function formatear_duracion($minutos) {
        $minutos = intval($minutos);
        if ($minutos < 60) {
            return $minutos . ' min';
        }
        $horas = floor($minutos / 60);
        $mins = $minutos % 60;
        return $horas . 'h ' . $mins . 'min';
    }
}

// Inicializar
Flavor_Podcast_Frontend_Controller::get_instance();
