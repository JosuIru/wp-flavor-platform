<?php
/**
 * Frontend Controller para Radio Comunitaria
 *
 * @package FlavorChatIA
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
        $this->config = get_option('flavor_chat_ia_settings', []);
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
        add_shortcode('flavor_radio_player', [$this, 'shortcode_player']);
        add_shortcode('flavor_radio_programacion', [$this, 'shortcode_programacion']);
        add_shortcode('flavor_radio_programa_actual', [$this, 'shortcode_programa_actual']);
        add_shortcode('flavor_radio_dedicatorias', [$this, 'shortcode_dedicatorias']);
        add_shortcode('flavor_radio_chat', [$this, 'shortcode_chat']);
        add_shortcode('flavor_radio_proponer_programa', [$this, 'shortcode_proponer_programa']);
        add_shortcode('flavor_radio_podcasts', [$this, 'shortcode_podcasts']);
        add_shortcode('flavor_radio_estadisticas', [$this, 'shortcode_estadisticas']);

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
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

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
                'reproduciendo' => __('En vivo', 'flavor-chat-ia'),
                'pausado' => __('Pausado', 'flavor-chat-ia'),
                'cargando' => __('Conectando...', 'flavor-chat-ia'),
                'error' => __('Error de conexión', 'flavor-chat-ia'),
                'enviado' => __('Mensaje enviado', 'flavor-chat-ia'),
                'dedicatoriaEnviada' => __('Dedicatoria enviada correctamente', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Obtiene configuración del módulo radio
     */
    private function obtener_config_radio() {
        $config = get_option('flavor_chat_ia_settings', []);
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
            'titulo' => __('Radio', 'flavor-chat-ia'),
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
        if (Flavor_Chat_Helpers::tabla_existe($tabla_dedicatorias)) {
            $mis_dedicatorias = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_dedicatorias WHERE usuario_id = %d ORDER BY fecha_creacion DESC LIMIT 10",
                $usuario_id
            ));
        }

        // Programa actual
        $programa_actual = null;
        if (Flavor_Chat_Helpers::tabla_existe($tabla_programas)) {
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
                            <span class="flavor-radio-badge-live"><?php _e('EN VIVO', 'flavor-chat-ia'); ?></span>
                        </div>
                    </div>
                    <div class="flavor-radio-info">
                        <h2><?php echo esc_html($radio_settings['nombre_radio'] ?? __('Radio Comunitaria', 'flavor-chat-ia')); ?></h2>
                        <p class="flavor-radio-slogan"><?php echo esc_html($radio_settings['slogan'] ?? ''); ?></p>
                        <?php if ($programa_actual): ?>
                        <div class="flavor-ahora-suena">
                            <strong><?php _e('Ahora:', 'flavor-chat-ia'); ?></strong>
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
                    <span><?php _e('Enviar dedicatoria', 'flavor-chat-ia'); ?></span>
                </button>
                <a href="#programacion" class="flavor-accion-card">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <span><?php _e('Programación', 'flavor-chat-ia'); ?></span>
                </a>
                <a href="#podcasts" class="flavor-accion-card">
                    <span class="dashicons dashicons-microphone"></span>
                    <span><?php _e('Podcasts', 'flavor-chat-ia'); ?></span>
                </a>
            </div>

            <!-- Mis dedicatorias -->
            <div class="flavor-panel">
                <h3 class="flavor-panel-titulo">
                    <span class="dashicons dashicons-heart"></span>
                    <?php _e('Mis dedicatorias', 'flavor-chat-ia'); ?>
                </h3>
                <?php if (!empty($mis_dedicatorias)): ?>
                <div class="flavor-dedicatorias-lista">
                    <?php foreach ($mis_dedicatorias as $ded): ?>
                    <div class="flavor-dedicatoria-item">
                        <div class="flavor-dedicatoria-texto">
                            <p>"<?php echo esc_html($ded->mensaje); ?>"</p>
                            <?php if (!empty($ded->cancion_solicitada)): ?>
                            <small><?php _e('Canción:', 'flavor-chat-ia'); ?> <?php echo esc_html($ded->cancion_solicitada); ?></small>
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
                <p class="flavor-vacio"><?php _e('Aún no has enviado dedicatorias', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Modal dedicatoria -->
            <div id="modal-dedicatoria" class="flavor-modal" style="display:none;">
                <div class="flavor-modal-contenido">
                    <button type="button" class="flavor-modal-cerrar">&times;</button>
                    <h3><?php _e('Enviar dedicatoria', 'flavor-chat-ia'); ?></h3>
                    <form id="form-dedicatoria">
                        <div class="flavor-form-grupo">
                            <label><?php _e('Para quién', 'flavor-chat-ia'); ?></label>
                            <input type="text" name="destinatario" required placeholder="<?php esc_attr_e('Nombre del destinatario', 'flavor-chat-ia'); ?>">
                        </div>
                        <div class="flavor-form-grupo">
                            <label><?php _e('Tu mensaje', 'flavor-chat-ia'); ?></label>
                            <textarea name="mensaje" rows="3" required placeholder="<?php esc_attr_e('Escribe tu dedicatoria...', 'flavor-chat-ia'); ?>"></textarea>
                        </div>
                        <div class="flavor-form-grupo">
                            <label><?php _e('Canción solicitada (opcional)', 'flavor-chat-ia'); ?></label>
                            <input type="text" name="cancion" placeholder="<?php esc_attr_e('Artista - Canción', 'flavor-chat-ia'); ?>">
                        </div>
                        <div class="flavor-form-acciones">
                            <button type="button" class="flavor-btn flavor-btn-outline flavor-modal-cerrar-btn">
                                <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                            </button>
                            <button type="submit" class="flavor-btn flavor-btn-primary">
                                <span class="dashicons dashicons-heart"></span>
                                <?php _e('Enviar', 'flavor-chat-ia'); ?>
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
                    <?php _e('EN VIVO', 'flavor-chat-ia'); ?>
                </span>
            </div>
            <?php endif; ?>

            <div class="flavor-radio-controles-player">
                <button type="button" id="radio-play-btn" class="flavor-radio-btn-play">
                    <span class="dashicons dashicons-controls-play"></span>
                </button>

                <div class="flavor-radio-info-actual" id="info-actual">
                    <span class="flavor-radio-estado"><?php _e('Haz clic para escuchar', 'flavor-chat-ia'); ?></span>
                </div>

                <div class="flavor-radio-volumen">
                    <button type="button" id="radio-mute" class="flavor-radio-btn-mute">
                        <span class="dashicons dashicons-controls-volumeon"></span>
                    </button>
                    <input type="range" id="radio-vol" min="0" max="100" value="80">
                </div>
            </div>

            <?php if ($atts['estilo'] === 'completo'): ?>
            <div class="flavor-radio-programa-actual" id="programa-actual-container">
                <!-- Se carga via AJAX -->
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

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_programas)) {
            return '<p class="flavor-aviso">' . __('Programación no disponible', 'flavor-chat-ia') . '</p>';
        }

        $atts = shortcode_atts([
            'dia' => '',
            'limite' => 50,
        ], $atts);

        $dias = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $dias_es = [
            'monday' => __('Lunes', 'flavor-chat-ia'),
            'tuesday' => __('Martes', 'flavor-chat-ia'),
            'wednesday' => __('Miércoles', 'flavor-chat-ia'),
            'thursday' => __('Jueves', 'flavor-chat-ia'),
            'friday' => __('Viernes', 'flavor-chat-ia'),
            'saturday' => __('Sábado', 'flavor-chat-ia'),
            'sunday' => __('Domingo', 'flavor-chat-ia'),
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
            <h2><?php _e('Programación', 'flavor-chat-ia'); ?></h2>

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
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <p class="flavor-vacio"><?php _e('Sin programación para este día', 'flavor-chat-ia'); ?></p>
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
            return '<p class="flavor-aviso">' . __('Inicia sesión para enviar dedicatorias', 'flavor-chat-ia') . '</p>';
        }

        $this->cargar_assets();

        ob_start();
        ?>
        <div class="flavor-radio-dedicatorias">
            <h3><?php _e('Enviar dedicatoria', 'flavor-chat-ia'); ?></h3>
            <form id="form-dedicatoria-standalone" class="flavor-form-dedicatoria">
                <div class="flavor-form-grupo">
                    <label><?php _e('Para', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="destinatario" required placeholder="<?php esc_attr_e('Nombre del destinatario', 'flavor-chat-ia'); ?>">
                </div>
                <div class="flavor-form-grupo">
                    <label><?php _e('Mensaje', 'flavor-chat-ia'); ?></label>
                    <textarea name="mensaje" rows="3" required placeholder="<?php esc_attr_e('Tu dedicatoria...', 'flavor-chat-ia'); ?>"></textarea>
                </div>
                <div class="flavor-form-grupo">
                    <label><?php _e('Canción (opcional)', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="cancion" placeholder="<?php esc_attr_e('Artista - Título', 'flavor-chat-ia'); ?>">
                </div>
                <button type="submit" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-heart"></span>
                    <?php _e('Enviar dedicatoria', 'flavor-chat-ia'); ?>
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
                    <?php _e('Chat en vivo', 'flavor-chat-ia'); ?>
                </h4>
                <span class="flavor-chat-oyentes" id="contador-oyentes">0 oyentes</span>
            </div>
            <div class="flavor-chat-mensajes" id="chat-mensajes">
                <!-- Mensajes se cargan via AJAX -->
            </div>
            <?php if (is_user_logged_in()): ?>
            <form id="form-chat-radio" class="flavor-chat-form">
                <input type="text" name="mensaje" placeholder="<?php esc_attr_e('Escribe un mensaje...', 'flavor-chat-ia'); ?>" autocomplete="off" maxlength="200">
                <button type="submit" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </button>
            </form>
            <?php else: ?>
            <p class="flavor-chat-login"><?php _e('Inicia sesión para participar en el chat', 'flavor-chat-ia'); ?></p>
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

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_grabaciones)) {
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
            <h2><?php _e('Programas grabados', 'flavor-chat-ia'); ?></h2>
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

        if (Flavor_Chat_Helpers::tabla_existe($tabla_programas)) {
            $stats->programas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_programas WHERE estado = 'activo'");
        }
        if (Flavor_Chat_Helpers::tabla_existe($tabla_dedicatorias)) {
            $stats->dedicatorias = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_dedicatorias WHERE estado = 'emitida'");
        }
        if (Flavor_Chat_Helpers::tabla_existe($tabla_oyentes)) {
            $stats->oyentes_unicos = $wpdb->get_var("SELECT COUNT(DISTINCT ip) FROM $tabla_oyentes");
        }

        ob_start();
        ?>
        <div class="flavor-radio-estadisticas">
            <div class="flavor-stats-grid">
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-microphone"></span>
                    <div class="flavor-stat-valor"><?php echo intval($stats->programas); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Programas', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-heart"></span>
                    <div class="flavor-stat-valor"><?php echo number_format($stats->dedicatorias); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Dedicatorias', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-stat-card">
                    <span class="flavor-stat-icono dashicons dashicons-groups"></span>
                    <div class="flavor-stat-valor"><?php echo number_format($stats->oyentes_unicos); ?></div>
                    <div class="flavor-stat-etiqueta"><?php _e('Oyentes', 'flavor-chat-ia'); ?></div>
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

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_programas)) {
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
                    <span class="flavor-badge flavor-badge-live"><?php _e('EN DIRECTO', 'flavor-chat-ia'); ?></span>
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
                    <p><?php _e('Música continua', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($programa_siguiente): ?>
                <div class="flavor-programa-siguiente">
                    <span class="flavor-label"><?php _e('A continuación:', 'flavor-chat-ia'); ?></span>
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
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_dedicatorias = $wpdb->prefix . 'flavor_radio_dedicatorias';

        $destinatario = sanitize_text_field($_POST['destinatario'] ?? '');
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');
        $cancion = sanitize_text_field($_POST['cancion'] ?? '');

        if (empty($mensaje)) {
            wp_send_json_error(['message' => __('El mensaje es obligatorio', 'flavor-chat-ia')]);
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
            wp_send_json_error(['message' => __('Error al enviar la dedicatoria', 'flavor-chat-ia')]);
        }

        wp_send_json_success([
            'message' => __('Dedicatoria enviada correctamente. Será emitida pronto.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Proponer programa
     */
    public function ajax_proponer_programa() {
        check_ajax_referer('flavor_radio_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_propuestas = $wpdb->prefix . 'flavor_radio_propuestas';

        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $duracion = absint($_POST['duracion'] ?? 60);

        if (empty($nombre)) {
            wp_send_json_error(['message' => __('El nombre es obligatorio', 'flavor-chat-ia')]);
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_propuestas)) {
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
            'message' => __('Propuesta enviada. El equipo la revisará pronto.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Enviar mensaje de chat
     */
    public function ajax_enviar_chat() {
        check_ajax_referer('flavor_radio_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla_chat = $wpdb->prefix . 'flavor_radio_chat';

        $mensaje = sanitize_text_field($_POST['mensaje'] ?? '');

        if (empty($mensaje)) {
            wp_send_json_error(['message' => __('Mensaje vacío', 'flavor-chat-ia')]);
        }

        if (Flavor_Chat_Helpers::tabla_existe($tabla_chat)) {
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

        wp_send_json_error(['message' => __('Error al enviar mensaje', 'flavor-chat-ia')]);
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
            'pendiente' => __('Pendiente', 'flavor-chat-ia'),
            'aprobada' => __('Aprobada', 'flavor-chat-ia'),
            'emitida' => __('Emitida', 'flavor-chat-ia'),
            'rechazada' => __('Rechazada', 'flavor-chat-ia'),
        ];
        return $etiquetas[$estado] ?? ucfirst($estado);
    }
}

// Inicializar
Flavor_Radio_Frontend_Controller::get_instance();
