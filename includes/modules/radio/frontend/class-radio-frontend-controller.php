<?php
/**
 * Frontend Controller para Radio Comunitaria
 *
 * @package FlavorPlatform
 * @since 3.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador frontend del módulo Radio
 */
class Flavor_Radio_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Radio_Frontend_Controller|null
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
        $this->config = flavor_get_main_settings();
        $this->init();
    }

    /**
     * Obtiene instancia singleton
     * @return Flavor_Radio_Frontend_Controller
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
            'flavor_radio_player' => 'shortcode_player',
            'flavor_radio_programacion' => 'shortcode_programacion',
            'flavor_radio_programa_actual' => 'shortcode_programa_actual',
            'flavor_radio_dedicatorias' => 'shortcode_dedicatorias',
            'flavor_radio_chat' => 'shortcode_chat',
            'flavor_radio_proponer_programa' => 'shortcode_proponer_programa',
            'flavor_radio_podcasts' => 'shortcode_podcasts',
            'flavor_radio_estadisticas' => 'shortcode_estadisticas',
            // Nuevos shortcodes v2.0
            'flavor_radio_locutor' => 'shortcode_locutor_perfil',
            'flavor_radio_calendario' => 'shortcode_calendario',
            'flavor_radio_favoritos' => 'shortcode_mis_favoritos',
            'flavor_radio_canales' => 'shortcode_canales',
        ];

        foreach ($shortcodes as $tag => $method) {
            if (!shortcode_exists($tag)) {
                add_shortcode($tag, [$this, $method]);
            }
        }

        // AJAX handlers (complementarios a los del módulo)
        add_action('wp_ajax_flavor_radio_frontend_dedicatoria', [$this, 'ajax_enviar_dedicatoria']);
        add_action('wp_ajax_flavor_radio_frontend_proponer', [$this, 'ajax_proponer_programa']);
        add_action('wp_ajax_flavor_radio_frontend_chat', [$this, 'ajax_enviar_chat']);

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
        $version = FLAVOR_PLATFORM_VERSION ?? '1.0.0';

        wp_register_style(
            'flavor-radio-frontend',
            $base_url . 'assets/css/radio-frontend.css',
            [],
            $version
        );

        wp_register_script(
            'flavor-radio-frontend',
            $base_url . 'assets/js/radio-frontend.js',
            ['jquery'],
            $version,
            true
        );

        // Obtener configuración del módulo
        $radio_settings = $this->obtener_config_radio();

        wp_localize_script('flavor-radio-frontend', 'flavorRadioConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_radio_nonce'),
            'streamUrl' => $radio_settings['url_stream'] ?? '',
            'streamHdUrl' => $radio_settings['url_stream_hd'] ?? '',
            'nombreRadio' => $radio_settings['nombre_radio'] ?? 'Radio Comunitaria',
            'chatHabilitado' => !empty($radio_settings['chat_en_vivo']),
            'strings' => [
                'reproduciendo' => __('En vivo', 'flavor-platform'),
                'pausado' => __('Pausado', 'flavor-platform'),
                'cargando' => __('Conectando...', 'flavor-platform'),
                'error' => __('Error de conexión', 'flavor-platform'),
                'enviado' => __('Mensaje enviado', 'flavor-platform'),
                'dedicatoriaEnviada' => __('Dedicatoria enviada correctamente', 'flavor-platform'),
            ],
        ]);
    }

    /**
     * Obtiene configuración del módulo radio
     */
    private function obtener_config_radio() {
        $config = flavor_get_main_settings();
        return $config['radio'] ?? [];
    }

    /**
     * Carga assets en frontend
     */
    private function cargar_assets() {
        wp_enqueue_style('flavor-radio-frontend');
        wp_enqueue_script('flavor-radio-frontend');
    }

    /**
     * Registra tabs en dashboard del usuario
     */
    public function registrar_dashboard_tabs($tabs) {
        $tabs['radio'] = [
            'titulo' => __('Radio', 'flavor-platform'),
            'icono' => 'dashicons-format-audio',
            'prioridad' => 60,
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
        $tabla_dedicatorias = $wpdb->prefix . 'flavor_radio_dedicatorias';
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

        $radio_settings = $this->obtener_config_radio();

        // Mis dedicatorias
        $mis_dedicatorias = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_dedicatorias)) {
            $mis_dedicatorias = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_dedicatorias WHERE usuario_id = %d ORDER BY fecha_creacion DESC LIMIT 10",
                $usuario_id
            ));
        }

        // Programa actual
        $programa_actual = null;
        if (Flavor_Platform_Helpers::tabla_existe($tabla_programas)) {
            $ahora = current_time('H:i');
            $dia = strtolower(date('l'));
            $programa_actual = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_programas WHERE dia_semana = %s AND hora_inicio <= %s AND hora_fin > %s AND estado = 'activo'",
                $dia, $ahora, $ahora
            ));
        }

        ob_start();
        ?>
        <div class="flavor-radio-dashboard">
            <!-- Player en vivo -->
            <div class="flavor-radio-player-section">
                <div class="flavor-radio-en-vivo">
                    <div class="flavor-radio-visual">
                        <div class="flavor-radio-ondas">
                            <span></span><span></span><span></span><span></span><span></span>
                        </div>
                        <div class="flavor-radio-status" id="radio-status">
                            <span class="flavor-radio-badge-live"><?php _e('EN VIVO', 'flavor-platform'); ?></span>
                        </div>
                    </div>
                    <div class="flavor-radio-info">
                        <h2><?php echo esc_html($radio_settings['nombre_radio'] ?? __('Radio Comunitaria', 'flavor-platform')); ?></h2>
                        <p class="flavor-radio-slogan"><?php echo esc_html($radio_settings['slogan'] ?? ''); ?></p>
                        <?php if ($programa_actual): ?>
                        <div class="flavor-ahora-suena">
                            <strong><?php _e('Ahora:', 'flavor-platform'); ?></strong>
                            <?php echo esc_html($programa_actual->nombre); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flavor-radio-controles">
                        <button type="button" id="btn-play-radio" class="flavor-btn-play-radio">
                            <span class="dashicons dashicons-controls-play"></span>
                        </button>
                        <div class="flavor-volumen-control">
                            <span class="dashicons dashicons-controls-volumeon"></span>
                            <input type="range" id="radio-volumen" min="0" max="100" value="80">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="flavor-radio-acciones">
                <button type="button" class="flavor-accion-card" id="btn-dedicatoria">
                    <span class="dashicons dashicons-heart"></span>
                    <span><?php _e('Enviar dedicatoria', 'flavor-platform'); ?></span>
                </button>
                <a href="#programacion" class="flavor-accion-card">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <span><?php _e('Programación', 'flavor-platform'); ?></span>
                </a>
                <a href="#podcasts" class="flavor-accion-card">
                    <span class="dashicons dashicons-microphone"></span>
                    <span><?php _e('Podcasts', 'flavor-platform'); ?></span>
                </a>
            </div>

            <!-- Mis dedicatorias -->
            <div class="flavor-panel">
                <h3 class="flavor-panel-titulo">
                    <span class="dashicons dashicons-heart"></span>
                    <?php _e('Mis dedicatorias', 'flavor-platform'); ?>
                </h3>
                <?php if (!empty($mis_dedicatorias)): ?>
                <div class="flavor-dedicatorias-lista">
                    <?php foreach ($mis_dedicatorias as $ded): ?>
                    <div class="flavor-dedicatoria-item">
                        <div class="flavor-dedicatoria-texto">
                            <p>"<?php echo esc_html($ded->mensaje); ?>"</p>
                            <?php if (!empty($ded->cancion_solicitada)): ?>
                            <small><?php _e('Canción:', 'flavor-platform'); ?> <?php echo esc_html($ded->cancion_solicitada); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="flavor-dedicatoria-meta">
                            <span class="flavor-badge flavor-badge-<?php echo esc_attr($ded->estado); ?>">
                                <?php echo esc_html($this->obtener_etiqueta_estado($ded->estado)); ?>
                            </span>
                            <span class="flavor-fecha"><?php echo date_i18n('j M', strtotime($ded->fecha_creacion)); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="flavor-vacio"><?php _e('Aún no has enviado dedicatorias', 'flavor-platform'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Modal dedicatoria -->
            <div id="modal-dedicatoria" class="flavor-modal" style="display:none;">
                <div class="flavor-modal-contenido">
                    <button type="button" class="flavor-modal-cerrar">&times;</button>
                    <h3><?php _e('Enviar dedicatoria', 'flavor-platform'); ?></h3>
                    <form id="form-dedicatoria">
                        <div class="flavor-form-grupo">
                            <label><?php _e('Para quién', 'flavor-platform'); ?></label>
                            <input type="text" name="destinatario" required placeholder="<?php esc_attr_e('Nombre del destinatario', 'flavor-platform'); ?>">
                        </div>
                        <div class="flavor-form-grupo">
                            <label><?php _e('Tu mensaje', 'flavor-platform'); ?></label>
                            <textarea name="mensaje" rows="3" required placeholder="<?php esc_attr_e('Escribe tu dedicatoria...', 'flavor-platform'); ?>"></textarea>
                        </div>
                        <div class="flavor-form-grupo">
                            <label><?php _e('Canción solicitada (opcional)', 'flavor-platform'); ?></label>
                            <input type="text" name="cancion" placeholder="<?php esc_attr_e('Artista - Canción', 'flavor-platform'); ?>">
                        </div>
                        <div class="flavor-form-acciones">
                            <button type="button" class="flavor-btn flavor-btn-outline flavor-modal-cerrar-btn">
                                <?php _e('Cancelar', 'flavor-platform'); ?>
                            </button>
                            <button type="submit" class="flavor-btn flavor-btn-primary">
                                <span class="dashicons dashicons-heart"></span>
                                <?php _e('Enviar', 'flavor-platform'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Player de radio
     */
    public function shortcode_player($atts) {
        $this->cargar_assets();
        $radio_settings = $this->obtener_config_radio();

        $atts = shortcode_atts([
            'estilo' => 'completo', // compacto, completo, mini
            'autoplay' => 'false',
        ], $atts);

        ob_start();
        ?>
        <div class="flavor-radio-player flavor-radio-player-<?php echo esc_attr($atts['estilo']); ?>"
             data-autoplay="<?php echo esc_attr($atts['autoplay']); ?>">
            <audio id="flavor-radio-audio" preload="none">
                <source src="<?php echo esc_url($radio_settings['url_stream'] ?? ''); ?>" type="audio/mpeg">
            </audio>

            <?php if ($atts['estilo'] === 'completo'): ?>
            <div class="flavor-radio-header">
                <?php if (!empty($radio_settings['logo_url'])): ?>
                <img src="<?php echo esc_url($radio_settings['logo_url']); ?>" alt="" class="flavor-radio-logo">
                <?php endif; ?>
                <div class="flavor-radio-titulo">
                    <h3><?php echo esc_html($radio_settings['nombre_radio'] ?? 'Radio Comunitaria'); ?></h3>
                    <p><?php echo esc_html($radio_settings['slogan'] ?? ''); ?></p>
                </div>
                <span class="flavor-radio-badge-live" id="badge-live" style="display:none;">
                    <?php _e('EN VIVO', 'flavor-platform'); ?>
                </span>
            </div>
            <?php endif; ?>

            <div class="flavor-radio-controles-player">
                <button type="button" id="radio-play-btn" class="flavor-radio-btn-play">
                    <span class="dashicons dashicons-controls-play"></span>
                </button>

                <div class="flavor-radio-info-actual" id="info-actual">
                    <span class="flavor-radio-estado"><?php _e('Haz clic para escuchar', 'flavor-platform'); ?></span>
                </div>

                <div class="flavor-radio-volumen">
                    <button type="button" id="radio-mute" class="flavor-radio-btn-mute">
                        <span class="dashicons dashicons-controls-volumeon"></span>
                    </button>
                    <input type="range" id="radio-vol" min="0" max="100" value="80">
                </div>
            </div>

            <!-- Visualizador de onda (Canvas) -->
            <canvas id="radio-visualizer-canvas" class="radio-visualizer-canvas <?php echo $atts['estilo'] === 'compacto' ? 'compact' : ''; ?>"></canvas>

            <!-- Canción actual (metadatos del stream) -->
            <div class="radio-now-playing" id="radio-now-playing" style="display: none;">
                <div class="now-playing-icon">
                    <span class="dashicons dashicons-format-audio"></span>
                </div>
                <div class="now-playing-info">
                    <div class="now-playing-title">-</div>
                    <div class="now-playing-artist">-</div>
                </div>
                <button type="button" class="btn-share-song" title="<?php esc_attr_e('Compartir', 'flavor-platform'); ?>">
                    <span class="dashicons dashicons-share"></span>
                </button>
            </div>

            <?php if ($atts['estilo'] === 'completo'): ?>
            <div class="flavor-radio-programa-actual" id="programa-actual-container">
                <!-- Se carga via AJAX -->
            </div>

            <!-- Botones de compartir -->
            <div class="radio-share-buttons">
                <button type="button" class="btn-share twitter" data-red="twitter" data-tipo="radio" title="Twitter">
                    <span class="dashicons dashicons-twitter"></span>
                </button>
                <button type="button" class="btn-share facebook" data-red="facebook" data-tipo="radio" title="Facebook">
                    <span class="dashicons dashicons-facebook"></span>
                </button>
                <button type="button" class="btn-share whatsapp" data-red="whatsapp" data-tipo="radio" title="WhatsApp">
                    <span class="dashicons dashicons-whatsapp"></span>
                </button>
                <button type="button" class="btn-share copy-link" data-red="copy" data-tipo="radio" title="<?php esc_attr_e('Copiar enlace', 'flavor-platform'); ?>">
                    <span class="dashicons dashicons-admin-links"></span>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Programación semanal
     */
    public function shortcode_programacion($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_programas)) {
            return '<p class="flavor-aviso">' . __('Programación no disponible', 'flavor-platform') . '</p>';
        }

        $atts = shortcode_atts([
            'dia' => '',
            'limite' => 50,
        ], $atts);

        $dias = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $dias_es = [
            'monday' => __('Lunes', 'flavor-platform'),
            'tuesday' => __('Martes', 'flavor-platform'),
            'wednesday' => __('Miércoles', 'flavor-platform'),
            'thursday' => __('Jueves', 'flavor-platform'),
            'friday' => __('Viernes', 'flavor-platform'),
            'saturday' => __('Sábado', 'flavor-platform'),
            'sunday' => __('Domingo', 'flavor-platform'),
        ];

        $programacion = [];
        foreach ($dias as $dia) {
            $programas = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_programas WHERE dia_semana = %s AND estado = 'activo' ORDER BY hora_inicio ASC",
                $dia
            ));
            $programacion[$dia] = $programas;
        }

        $dia_actual = strtolower(date('l'));

        ob_start();
        ?>
        <div class="flavor-radio-programacion" id="programacion">
            <h2><?php _e('Programación', 'flavor-platform'); ?></h2>

            <div class="flavor-programacion-tabs">
                <?php foreach ($dias as $dia): ?>
                <button type="button" class="flavor-tab-dia <?php echo $dia === $dia_actual ? 'activo' : ''; ?>"
                        data-dia="<?php echo esc_attr($dia); ?>">
                    <?php echo esc_html($dias_es[$dia]); ?>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="flavor-programacion-contenido">
                <?php foreach ($dias as $dia): ?>
                <div class="flavor-programacion-dia <?php echo $dia === $dia_actual ? 'activo' : ''; ?>"
                     id="programacion-<?php echo esc_attr($dia); ?>">
                    <?php if (!empty($programacion[$dia])): ?>
                        <?php foreach ($programacion[$dia] as $programa): ?>
                        <div class="flavor-programa-item">
                            <div class="flavor-programa-hora">
                                <?php echo esc_html(substr($programa->hora_inicio, 0, 5)); ?>
                                <span>-</span>
                                <?php echo esc_html(substr($programa->hora_fin, 0, 5)); ?>
                            </div>
                            <div class="flavor-programa-info">
                                <h4><?php echo esc_html($programa->nombre); ?></h4>
                                <?php if (!empty($programa->descripcion)): ?>
                                <p><?php echo esc_html($programa->descripcion); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($programa->conductor)): ?>
                                <span class="flavor-conductor">
                                    <span class="dashicons dashicons-admin-users"></span>
                                    <?php echo esc_html($programa->conductor); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($programa->categoria)): ?>
                            <span class="flavor-badge"><?php echo esc_html($programa->categoria); ?></span>
                            <?php endif; ?>
                            <?php if (is_user_logged_in()): ?>
                            <button type="button" class="btn-favorito" data-programa-id="<?php echo esc_attr($programa->id); ?>"
                                    title="<?php esc_attr_e('Añadir a favoritos', 'flavor-platform'); ?>">
                                <span class="dashicons dashicons-heart"></span>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <p class="flavor-vacio"><?php _e('Sin programación para este día', 'flavor-platform'); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Formulario de dedicatorias
     */
    public function shortcode_dedicatorias($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-aviso">' . __('Inicia sesión para enviar dedicatorias', 'flavor-platform') . '</p>';
        }

        $this->cargar_assets();

        ob_start();
        ?>
        <div class="flavor-radio-dedicatorias">
            <h3><?php _e('Enviar dedicatoria', 'flavor-platform'); ?></h3>
            <form id="form-dedicatoria-standalone" class="flavor-form-dedicatoria">
                <div class="flavor-form-grupo">
                    <label><?php _e('Para', 'flavor-platform'); ?></label>
                    <input type="text" name="destinatario" required placeholder="<?php esc_attr_e('Nombre del destinatario', 'flavor-platform'); ?>">
                </div>
                <div class="flavor-form-grupo">
                    <label><?php _e('Mensaje', 'flavor-platform'); ?></label>
                    <textarea name="mensaje" rows="3" required placeholder="<?php esc_attr_e('Tu dedicatoria...', 'flavor-platform'); ?>"></textarea>
                </div>
                <div class="flavor-form-grupo">
                    <label><?php _e('Canción (opcional)', 'flavor-platform'); ?></label>
                    <input type="text" name="cancion" placeholder="<?php esc_attr_e('Artista - Título', 'flavor-platform'); ?>">
                </div>
                <button type="submit" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-heart"></span>
                    <?php _e('Enviar dedicatoria', 'flavor-platform'); ?>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Chat en vivo
     */
    public function shortcode_chat($atts) {
        $this->cargar_assets();
        $radio_settings = $this->obtener_config_radio();

        if (empty($radio_settings['chat_en_vivo'])) {
            return '';
        }

        ob_start();
        ?>
        <div class="flavor-radio-chat">
            <div class="flavor-chat-header">
                <h4>
                    <span class="dashicons dashicons-admin-comments"></span>
                    <?php _e('Chat en vivo', 'flavor-platform'); ?>
                </h4>
                <span class="flavor-chat-oyentes" id="contador-oyentes">0 oyentes</span>
            </div>
            <div class="flavor-chat-mensajes" id="chat-mensajes">
                <!-- Mensajes se cargan via AJAX -->
            </div>
            <?php if (is_user_logged_in()): ?>
            <form id="form-chat-radio" class="flavor-chat-form">
                <input type="text" name="mensaje" placeholder="<?php esc_attr_e('Escribe un mensaje...', 'flavor-platform'); ?>" autocomplete="off" maxlength="200">
                <button type="submit" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </button>
            </form>
            <?php else: ?>
            <p class="flavor-chat-login"><?php _e('Inicia sesión para participar en el chat', 'flavor-platform'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Podcasts/grabaciones
     */
    public function shortcode_podcasts($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_grabaciones = $wpdb->prefix . 'flavor_radio_grabaciones';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_grabaciones)) {
            return '';
        }

        $atts = shortcode_atts([
            'limite' => 12,
        ], $atts);

        $grabaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_grabaciones WHERE estado = 'publicado' ORDER BY fecha_grabacion DESC LIMIT %d",
            $atts['limite']
        ));

        if (empty($grabaciones)) {
            return '';
        }

        ob_start();
        ?>
        <div class="flavor-radio-podcasts" id="podcasts">
            <h2><?php _e('Programas grabados', 'flavor-platform'); ?></h2>
            <div class="flavor-podcasts-grid">
                <?php foreach ($grabaciones as $grabacion): ?>
                <article class="flavor-podcast-card">
                    <div class="flavor-podcast-fecha">
                        <span class="dia"><?php echo date_i18n('j', strtotime($grabacion->fecha_grabacion)); ?></span>
                        <span class="mes"><?php echo date_i18n('M', strtotime($grabacion->fecha_grabacion)); ?></span>
                    </div>
                    <div class="flavor-podcast-info">
                        <h4><?php echo esc_html($grabacion->titulo); ?></h4>
                        <?php if (!empty($grabacion->programa_nombre)): ?>
                        <span class="flavor-podcast-programa"><?php echo esc_html($grabacion->programa_nombre); ?></span>
                        <?php endif; ?>
                        <span class="flavor-duracion"><?php echo $this->formatear_duracion($grabacion->duracion_segundos / 60); ?></span>
                    </div>
                    <button type="button" class="flavor-btn-play-podcast" data-audio="<?php echo esc_url($grabacion->audio_url); ?>">
                        <span class="dashicons dashicons-controls-play"></span>
                    </button>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Estadísticas
     */
    public function shortcode_estadisticas($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';
        $tabla_dedicatorias = $wpdb->prefix . 'flavor_radio_dedicatorias';
        $tabla_oyentes = $wpdb->prefix . 'flavor_radio_oyentes';

        $stats = (object)[
            'programas' => 0,
            'dedicatorias' => 0,
            'oyentes_unicos' => 0,
        ];

        if (Flavor_Platform_Helpers::tabla_existe($tabla_programas)) {
            $stats->programas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_programas WHERE estado = 'activo'");
        }
        if (Flavor_Platform_Helpers::tabla_existe($tabla_dedicatorias)) {
            $stats->dedicatorias = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_dedicatorias WHERE estado = 'emitida'");
        }
        if (Flavor_Platform_Helpers::tabla_existe($tabla_oyentes)) {
            $stats->oyentes_unicos = $wpdb->get_var("SELECT COUNT(DISTINCT ip) FROM $tabla_oyentes");
        }

        ob_start();
        ?>
        <div class="flavor-radio-estadisticas">
            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-microphone"></span>
                    <div class="flavor-stat-valor"><?php echo intval($stats->programas); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Programas', 'flavor-platform'); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-heart"></span>
                    <div class="flavor-stat-valor"><?php echo number_format($stats->dedicatorias); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Dedicatorias', 'flavor-platform'); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-groups"></span>
                    <div class="flavor-stat-valor"><?php echo number_format($stats->oyentes_unicos); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Oyentes', 'flavor-platform'); ?></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Programa actual
     */
    public function shortcode_programa_actual($atts) {
        $this->cargar_assets();

        $atts = shortcode_atts([
            'mostrar_siguiente' => 'true',
        ], $atts);

        global $wpdb;
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_programas)) {
            return '';
        }

        $dia_actual = strtolower(date('l'));
        $hora_actual = date('H:i:s');

        // Programa actual
        $programa_actual = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_programas
             WHERE dia_semana = %s
               AND hora_inicio <= %s
               AND hora_fin > %s
               AND estado = 'activo'
             LIMIT 1",
            $dia_actual, $hora_actual, $hora_actual
        ));

        // Siguiente programa
        $programa_siguiente = null;
        if ($atts['mostrar_siguiente'] === 'true') {
            $programa_siguiente = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_programas
                 WHERE dia_semana = %s
                   AND hora_inicio > %s
                   AND estado = 'activo'
                 ORDER BY hora_inicio ASC
                 LIMIT 1",
                $dia_actual, $hora_actual
            ));
        }

        ob_start();
        ?>
        <div class="flavor-radio-programa-actual-widget">
            <?php if ($programa_actual): ?>
                <div class="flavor-programa-actual">
                    <span class="flavor-badge flavor-badge-live"><?php _e('EN DIRECTO', 'flavor-platform'); ?></span>
                    <h4><?php echo esc_html($programa_actual->nombre); ?></h4>
                    <?php if (!empty($programa_actual->conductor)): ?>
                        <p class="flavor-programa-conductor">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php echo esc_html($programa_actual->conductor); ?>
                        </p>
                    <?php endif; ?>
                    <p class="flavor-programa-horario">
                        <?php echo esc_html(substr($programa_actual->hora_inicio, 0, 5)); ?> -
                        <?php echo esc_html(substr($programa_actual->hora_fin, 0, 5)); ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="flavor-sin-programa">
                    <p><?php _e('Música continua', 'flavor-platform'); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($programa_siguiente): ?>
                <div class="flavor-programa-siguiente">
                    <span class="flavor-label"><?php _e('A continuación:', 'flavor-platform'); ?></span>
                    <strong><?php echo esc_html($programa_siguiente->nombre); ?></strong>
                    <span class="flavor-hora"><?php echo esc_html(substr($programa_siguiente->hora_inicio, 0, 5)); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // ==========================================
    // AJAX HANDLERS
    // ==========================================

    /**
     * AJAX: Enviar dedicatoria
     */
    public function ajax_enviar_dedicatoria() {
        check_ajax_referer('flavor_radio_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla_dedicatorias = $wpdb->prefix . 'flavor_radio_dedicatorias';

        $destinatario = sanitize_text_field($_POST['destinatario'] ?? '');
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
        $cancion = sanitize_text_field($_POST['cancion'] ?? '');

        if (empty($mensaje)) {
            wp_send_json_error(['message' => __('El mensaje es obligatorio', 'flavor-platform')]);
        }

        $resultado = $wpdb->insert($tabla_dedicatorias, [
            'usuario_id' => get_current_user_id(),
            'destinatario' => $destinatario,
            'mensaje' => $mensaje,
            'cancion_solicitada' => $cancion,
            'estado' => 'pendiente',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado === false) {
            wp_send_json_error(['message' => __('Error al enviar la dedicatoria', 'flavor-platform')]);
        }

        wp_send_json_success([
            'message' => __('Dedicatoria enviada correctamente. Será emitida pronto.', 'flavor-platform'),
        ]);
    }

    /**
     * AJAX: Proponer programa
     */
    public function ajax_proponer_programa() {
        check_ajax_referer('flavor_radio_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_radio_propuestas';

        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $duracion = absint($_POST['duracion'] ?? 60);

        if (empty($nombre)) {
            wp_send_json_error(['message' => __('El nombre es obligatorio', 'flavor-platform')]);
        }

        if (Flavor_Platform_Helpers::tabla_existe($tabla_propuestas)) {
            $wpdb->insert($tabla_propuestas, [
                'usuario_id' => get_current_user_id(),
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'categoria' => $categoria,
                'duracion_minutos' => $duracion,
                'estado' => 'pendiente',
                'fecha_creacion' => current_time('mysql'),
            ]);
        }

        wp_send_json_success([
            'message' => __('Propuesta enviada. El equipo la revisará pronto.', 'flavor-platform'),
        ]);
    }

    /**
     * AJAX: Enviar mensaje de chat
     */
    public function ajax_enviar_chat() {
        check_ajax_referer('flavor_radio_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla_chat = $wpdb->prefix . 'flavor_radio_chat';

        $mensaje = sanitize_text_field($_POST['mensaje'] ?? '');

        if (empty($mensaje)) {
            wp_send_json_error(['message' => __('Mensaje vacío', 'flavor-platform')]);
        }

        if (Flavor_Platform_Helpers::tabla_existe($tabla_chat)) {
            $wpdb->insert($tabla_chat, [
                'usuario_id' => get_current_user_id(),
                'mensaje' => $mensaje,
                'fecha' => current_time('mysql'),
            ]);

            $user = wp_get_current_user();

            wp_send_json_success([
                'mensaje' => [
                    'id' => $wpdb->insert_id,
                    'usuario' => $user->display_name,
                    'texto' => $mensaje,
                    'fecha' => current_time('H:i'),
                ],
            ]);
        }

        wp_send_json_error(['message' => __('Error al enviar mensaje', 'flavor-platform')]);
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Formatea duración
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

    /**
     * Obtiene etiqueta de estado
     */
    private function obtener_etiqueta_estado($estado) {
        $etiquetas = [
            'pendiente' => __('Pendiente', 'flavor-platform'),
            'aprobada' => __('Aprobada', 'flavor-platform'),
            'emitida' => __('Emitida', 'flavor-platform'),
            'rechazada' => __('Rechazada', 'flavor-platform'),
        ];
        return $etiquetas[$estado] ?? ucfirst($estado);
    }

    // =========================================================================
    // NUEVOS SHORTCODES v2.0
    // =========================================================================

    /**
     * Shortcode: Perfil de locutor
     */
    public function shortcode_locutor_perfil($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';

        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);

        $locutor_id = absint($atts['id']);
        if (!$locutor_id) {
            return '<p class="flavor-aviso">' . __('Locutor no especificado', 'flavor-platform') . '</p>';
        }

        $user = get_userdata($locutor_id);
        if (!$user) {
            return '<p class="flavor-aviso">' . __('Locutor no encontrado', 'flavor-platform') . '</p>';
        }

        // Obtener programas
        $programas = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_programas)) {
            $programas = $wpdb->get_results($wpdb->prepare(
                "SELECT id, nombre, descripcion, imagen_url, categoria, oyentes_promedio, total_episodios
                 FROM $tabla_programas
                 WHERE locutor_id = %d AND estado = 'activo'
                 ORDER BY nombre",
                $locutor_id
            ));
        }

        // Stats
        $total_programas = count($programas);
        $total_episodios = array_sum(array_column($programas, 'total_episodios'));
        $oyentes_promedio = $total_programas > 0
            ? round(array_sum(array_column($programas, 'oyentes_promedio')) / $total_programas)
            : 0;

        // Meta
        $bio = get_user_meta($locutor_id, 'description', true);
        $redes = get_user_meta($locutor_id, 'flavor_redes_sociales', true) ?: [];

        ob_start();
        ?>
        <div class="locutor-perfil">
            <div class="locutor-header">
                <img src="<?php echo esc_url(get_avatar_url($locutor_id, ['size' => 200])); ?>" alt="" class="locutor-avatar">
                <h2 class="locutor-nombre"><?php echo esc_html($user->display_name); ?></h2>
                <p class="locutor-rol"><?php _e('Locutor', 'flavor-platform'); ?></p>

                <?php if (!empty($redes)): ?>
                <div class="locutor-redes">
                    <?php foreach ($redes as $red => $url): ?>
                        <?php if (!empty($url)): ?>
                        <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
                            <span class="dashicons dashicons-<?php echo esc_attr($red); ?>"></span>
                        </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="locutor-content">
                <?php if (!empty($bio)): ?>
                <p class="locutor-bio"><?php echo wp_kses_post($bio); ?></p>
                <?php endif; ?>

                <div class="locutor-stats">
                    <div class="locutor-stat">
                        <div class="locutor-stat-valor"><?php echo esc_html($total_programas); ?></div>
                        <div class="locutor-stat-label"><?php _e('Programas', 'flavor-platform'); ?></div>
                    </div>
                    <div class="locutor-stat">
                        <div class="locutor-stat-valor"><?php echo esc_html($total_episodios); ?></div>
                        <div class="locutor-stat-label"><?php _e('Episodios', 'flavor-platform'); ?></div>
                    </div>
                    <div class="locutor-stat">
                        <div class="locutor-stat-valor"><?php echo esc_html($oyentes_promedio); ?></div>
                        <div class="locutor-stat-label"><?php _e('Oyentes/ep', 'flavor-platform'); ?></div>
                    </div>
                </div>

                <?php if (!empty($programas)): ?>
                <div class="locutor-programas">
                    <h4><span class="dashicons dashicons-playlist-audio"></span> <?php _e('Sus programas', 'flavor-platform'); ?></h4>
                    <div class="mis-favoritos-grid">
                        <?php foreach ($programas as $prog): ?>
                        <div class="favorito-card">
                            <?php if (!empty($prog->imagen_url)): ?>
                            <img src="<?php echo esc_url($prog->imagen_url); ?>" class="programa-thumb" alt="">
                            <?php endif; ?>
                            <div class="programa-info">
                                <div class="programa-nombre"><?php echo esc_html($prog->nombre); ?></div>
                                <div class="programa-horario"><?php echo esc_html($prog->categoria); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Calendario de eventos
     */
    public function shortcode_calendario($atts) {
        $this->cargar_assets();

        $atts = shortcode_atts([
            'mes' => date('n'),
            'año' => date('Y'),
        ], $atts);

        ob_start();
        ?>
        <div class="radio-calendario" data-mes="<?php echo esc_attr($atts['mes']); ?>" data-año="<?php echo esc_attr($atts['año']); ?>">
            <div class="calendario-header">
                <div class="calendario-nav">
                    <button type="button" class="calendario-nav-prev">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </button>
                    <button type="button" class="calendario-nav-next">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </div>
                <span class="calendario-titulo"></span>
            </div>
            <div class="calendario-grid">
                <!-- Se carga via JS -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis programas favoritos
     */
    public function shortcode_mis_favoritos($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-aviso">' . __('Inicia sesión para ver tus favoritos', 'flavor-platform') . '</p>';
        }

        $this->cargar_assets();
        global $wpdb;
        $tabla_favoritos = $wpdb->prefix . 'flavor_radio_favoritos';
        $tabla_programas = $wpdb->prefix . 'flavor_radio_programas';
        $usuario_id = get_current_user_id();

        $favoritos = [];
        if (Flavor_Platform_Helpers::tabla_existe($tabla_favoritos)) {
            $favoritos = $wpdb->get_results($wpdb->prepare(
                "SELECT f.programa_id, f.notificaciones, p.nombre, p.imagen_url, p.categoria, p.hora_inicio
                 FROM $tabla_favoritos f
                 LEFT JOIN $tabla_programas p ON f.programa_id = p.id
                 WHERE f.usuario_id = %d
                 ORDER BY p.nombre",
                $usuario_id
            ));
        }

        ob_start();
        ?>
        <div class="flavor-radio-favoritos">
            <h3>
                <span class="dashicons dashicons-heart"></span>
                <?php _e('Mis programas favoritos', 'flavor-platform'); ?>
            </h3>

            <?php if (!empty($favoritos)): ?>
            <div class="mis-favoritos-grid">
                <?php foreach ($favoritos as $fav): ?>
                <div class="favorito-card">
                    <?php if (!empty($fav->imagen_url)): ?>
                    <img src="<?php echo esc_url($fav->imagen_url); ?>" class="programa-thumb" alt="">
                    <?php else: ?>
                    <div class="programa-thumb" style="background: var(--radio-gray-200); display: flex; align-items: center; justify-content: center;">
                        <span class="dashicons dashicons-microphone"></span>
                    </div>
                    <?php endif; ?>
                    <div class="programa-info">
                        <div class="programa-nombre"><?php echo esc_html($fav->nombre); ?></div>
                        <div class="programa-horario">
                            <?php echo esc_html($fav->categoria); ?>
                            <?php if (!empty($fav->hora_inicio)): ?>
                                · <?php echo esc_html(substr($fav->hora_inicio, 0, 5)); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="button" class="btn-notificacion <?php echo $fav->notificaciones ? 'activo' : ''; ?>"
                            data-programa-id="<?php echo esc_attr($fav->programa_id); ?>"
                            title="<?php esc_attr_e('Notificaciones', 'flavor-platform'); ?>">
                        <span class="dashicons dashicons-bell"></span>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="flavor-vacio"><?php _e('No tienes programas favoritos todavía', 'flavor-platform'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Selector de canales
     */
    public function shortcode_canales($atts) {
        $this->cargar_assets();
        global $wpdb;
        $tabla_canales = $wpdb->prefix . 'flavor_radio_canales';

        $canales = [];

        if (Flavor_Platform_Helpers::tabla_existe($tabla_canales)) {
            $canales = $wpdb->get_results(
                "SELECT id, nombre, descripcion, url_stream, url_stream_hd, logo_url
                 FROM $tabla_canales
                 WHERE activo = 1
                 ORDER BY orden ASC"
            );
        }

        // Si no hay canales, no mostrar nada
        if (count($canales) <= 1) {
            return '';
        }

        ob_start();
        ?>
        <div class="radio-canales">
            <?php foreach ($canales as $index => $canal): ?>
            <button type="button" class="canal-tab <?php echo $index === 0 ? 'activo' : ''; ?>"
                    data-canal-id="<?php echo esc_attr($canal->id); ?>"
                    data-stream="<?php echo esc_url($canal->url_stream); ?>"
                    data-stream-hd="<?php echo esc_url($canal->url_stream_hd); ?>">
                <span class="canal-nombre"><?php echo esc_html($canal->nombre); ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Inicializar
Flavor_Radio_Frontend_Controller::get_instance();
