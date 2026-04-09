<?php
/**
 * Dashboard Tab para el módulo Podcast
 *
 * Proporciona tabs de usuario para gestionar suscripciones, historial,
 * favoritos y descargas de podcasts.
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que gestiona los tabs del dashboard de usuario para Podcast
 */
class Flavor_Podcast_Dashboard_Tab {

    /**
     * Instancia singleton
     * @var Flavor_Podcast_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * Prefijo de tablas
     * @var string
     */
    private $prefijo_tabla;

    /**
     * Nombres de tablas
     * @var array
     */
    private $tablas = [];

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;

        $this->prefijo_tabla = $wpdb->prefix;
        $this->tablas = [
            'series'          => $this->prefijo_tabla . 'flavor_podcast_series',
            'episodios'       => $this->prefijo_tabla . 'flavor_podcast_episodios',
            'suscripciones'   => $this->prefijo_tabla . 'flavor_podcast_suscripciones',
            'reproducciones'  => $this->prefijo_tabla . 'flavor_podcast_reproducciones',
            'favoritos'       => $this->prefijo_tabla . 'flavor_podcast_favoritos',
            'descargas'       => $this->prefijo_tabla . 'flavor_podcast_descargas',
        ];

        $this->init();
    }

    /**
     * Obtiene instancia singleton
     *
     * @return Flavor_Podcast_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa hooks y filtros
     */
    private function init() {
        // Registrar tabs en el dashboard del usuario
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 55);

        // Crear tablas adicionales si no existen
        add_action('init', [$this, 'maybe_create_tables']);

        // AJAX handlers para acciones del dashboard
        add_action('wp_ajax_flavor_podcast_toggle_favorito', [$this, 'ajax_toggle_favorito']);
        add_action('wp_ajax_flavor_podcast_registrar_descarga', [$this, 'ajax_registrar_descarga']);
        add_action('wp_ajax_flavor_podcast_eliminar_historial', [$this, 'ajax_eliminar_historial']);
        add_action('wp_ajax_flavor_podcast_cancelar_suscripcion', [$this, 'ajax_cancelar_suscripcion']);
        add_action('wp_ajax_flavor_podcast_eliminar_descarga', [$this, 'ajax_eliminar_descarga']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);
    }

    /**
     * Crea tablas adicionales para favoritos y descargas
     */
    public function maybe_create_tables() {
        global $wpdb;

        // Verificar si la tabla de favoritos existe
        $tabla_favoritos = $this->tablas['favoritos'];
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_favoritos'") !== $tabla_favoritos) {
            $this->crear_tabla_favoritos();
        }

        // Verificar si la tabla de descargas existe
        $tabla_descargas = $this->tablas['descargas'];
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_descargas'") !== $tabla_descargas) {
            $this->crear_tabla_descargas();
        }
    }

    /**
     * Crea la tabla de favoritos
     */
    private function crear_tabla_favoritos() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->tablas['favoritos']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            episodio_id bigint(20) unsigned NOT NULL,
            fecha_agregado datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY usuario_episodio (usuario_id, episodio_id),
            KEY episodio_id (episodio_id),
            KEY fecha_agregado (fecha_agregado)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Crea la tabla de descargas
     */
    private function crear_tabla_descargas() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->tablas['descargas']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            usuario_id bigint(20) unsigned NOT NULL,
            episodio_id bigint(20) unsigned NOT NULL,
            dispositivo varchar(100) DEFAULT NULL,
            fecha_descarga datetime DEFAULT CURRENT_TIMESTAMP,
            estado enum('descargado','eliminado') DEFAULT 'descargado',
            PRIMARY KEY (id),
            UNIQUE KEY usuario_episodio (usuario_id, episodio_id),
            KEY episodio_id (episodio_id),
            KEY fecha_descarga (fecha_descarga)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Registra assets CSS y JS
     */
    public function registrar_assets() {
        $modulo_url = plugin_dir_url(__FILE__);
        $version = defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '1.0.0';

        wp_register_style(
            'flavor-podcast-dashboard',
            $modulo_url . 'assets/css/podcast-dashboard.css',
            [],
            $version
        );

        wp_register_script(
            'flavor-podcast-dashboard',
            $modulo_url . 'assets/js/podcast-dashboard.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-podcast-dashboard', 'flavorPodcastDashboard', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_podcast_dashboard_nonce'),
            'strings' => [
                'confirmEliminar' => __('¿Eliminar este elemento del historial?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmCancelar' => __('¿Cancelar esta suscripción?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'eliminado' => __('Eliminado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Ha ocurrido un error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'favoritoAgregado' => __('Agregado a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'favoritoEliminado' => __('Eliminado de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descargaIniciada' => __('Descarga iniciada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Carga assets en frontend
     */
    private function cargar_assets() {
        wp_enqueue_style('flavor-podcast-dashboard');
        wp_enqueue_script('flavor-podcast-dashboard');
        wp_enqueue_style('dashicons');
    }

    /**
     * Registra los tabs en el dashboard del usuario
     *
     * @param array $tabs Tabs existentes
     * @return array Tabs modificados
     */
    public function registrar_tabs($tabs) {
        // Tab de Suscripciones
        $tabs['podcast-suscripciones'] = [
            'titulo'    => __('Mis Suscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono'     => 'dashicons-rss',
            'prioridad' => 56,
            'callback'  => [$this, 'render_tab_suscripciones'],
            'grupo'     => 'podcast',
        ];

        // Tab de Historial
        $tabs['podcast-historial'] = [
            'titulo'    => __('Historial', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono'     => 'dashicons-backup',
            'prioridad' => 57,
            'callback'  => [$this, 'render_tab_historial'],
            'grupo'     => 'podcast',
        ];

        // Tab de Favoritos
        $tabs['podcast-favoritos'] = [
            'titulo'    => __('Favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono'     => 'dashicons-heart',
            'prioridad' => 58,
            'callback'  => [$this, 'render_tab_favoritos'],
            'grupo'     => 'podcast',
        ];

        // Tab de Descargas
        $tabs['podcast-descargas'] = [
            'titulo'    => __('Descargas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono'     => 'dashicons-download',
            'prioridad' => 59,
            'callback'  => [$this, 'render_tab_descargas'],
            'grupo'     => 'podcast',
        ];

        return $tabs;
    }

    /**
     * Renderiza el tab de Suscripciones
     *
     * @return string HTML del tab
     */
    public function render_tab_suscripciones() {
        $this->cargar_assets();
        global $wpdb;

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return $this->render_mensaje_login();
        }

        // Obtener suscripciones del usuario
        $suscripciones = $wpdb->get_results($wpdb->prepare(
            "SELECT
                sus.*,
                s.titulo AS serie_titulo,
                s.descripcion AS serie_descripcion,
                s.imagen_url AS serie_imagen,
                s.categoria AS serie_categoria,
                s.total_episodios,
                s.suscriptores,
                u.display_name AS autor_nombre,
                (SELECT COUNT(*) FROM {$this->tablas['episodios']}
                 WHERE serie_id = s.id
                 AND estado = 'publicado'
                 AND fecha_publicacion > sus.fecha_ultima_actividad) AS episodios_nuevos,
                (SELECT e.titulo FROM {$this->tablas['episodios']} e
                 WHERE e.serie_id = s.id AND e.estado = 'publicado'
                 ORDER BY e.fecha_publicacion DESC LIMIT 1) AS ultimo_episodio_titulo,
                (SELECT e.fecha_publicacion FROM {$this->tablas['episodios']} e
                 WHERE e.serie_id = s.id AND e.estado = 'publicado'
                 ORDER BY e.fecha_publicacion DESC LIMIT 1) AS ultimo_episodio_fecha
            FROM {$this->tablas['suscripciones']} sus
            JOIN {$this->tablas['series']} s ON sus.serie_id = s.id
            JOIN {$wpdb->users} u ON s.autor_id = u.ID
            WHERE sus.usuario_id = %d
            ORDER BY sus.fecha_suscripcion DESC",
            $usuario_id
        ));

        ob_start();
        ?>
        <div class="flavor-podcast-dashboard-tab flavor-suscripciones-tab">
            <div class="flavor-tab-header">
                <h2>
                    <span class="dashicons dashicons-rss"></span>
                    <?php _e('Series Suscritas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <span class="flavor-badge flavor-badge-count">
                    <?php echo count($suscripciones); ?>
                </span>
            </div>

            <?php if (empty($suscripciones)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-rss"></span>
                    <h3><?php _e('No tienes suscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Explora nuestro catálogo y suscríbete a las series que te interesen.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-suscripciones-grid">
                    <?php foreach ($suscripciones as $suscripcion): ?>
                        <div class="flavor-suscripcion-card" data-serie-id="<?php echo intval($suscripcion->serie_id); ?>">
                            <div class="flavor-suscripcion-imagen">
                                <?php if (!empty($suscripcion->serie_imagen)): ?>
                                    <img src="<?php echo esc_url($suscripcion->serie_imagen); ?>"
                                         alt="<?php echo esc_attr($suscripcion->serie_titulo); ?>">
                                <?php else: ?>
                                    <div class="flavor-imagen-placeholder">
                                        <span class="dashicons dashicons-microphone"></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($suscripcion->episodios_nuevos > 0): ?>
                                    <span class="flavor-badge-nuevo">
                                        <?php echo intval($suscripcion->episodios_nuevos); ?>
                                        <?php _e('nuevo(s)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="flavor-suscripcion-contenido">
                                <h3 class="flavor-suscripcion-titulo">
                                    <?php echo esc_html($suscripcion->serie_titulo); ?>
                                </h3>
                                <p class="flavor-suscripcion-autor">
                                    <?php _e('Por', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    <?php echo esc_html($suscripcion->autor_nombre); ?>
                                </p>

                                <?php if (!empty($suscripcion->serie_categoria)): ?>
                                    <span class="flavor-badge flavor-badge-categoria">
                                        <?php echo esc_html(ucfirst($suscripcion->serie_categoria)); ?>
                                    </span>
                                <?php endif; ?>

                                <div class="flavor-suscripcion-meta">
                                    <span>
                                        <span class="dashicons dashicons-playlist-audio"></span>
                                        <?php echo intval($suscripcion->total_episodios); ?>
                                        <?php _e('episodios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                    <span>
                                        <span class="dashicons dashicons-groups"></span>
                                        <?php echo number_format_i18n($suscripcion->suscriptores); ?>
                                    </span>
                                </div>

                                <?php if (!empty($suscripcion->ultimo_episodio_titulo)): ?>
                                    <div class="flavor-ultimo-episodio">
                                        <strong><?php _e('Último:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                        <?php echo esc_html(wp_trim_words($suscripcion->ultimo_episodio_titulo, 8)); ?>
                                        <span class="flavor-fecha">
                                            <?php echo human_time_diff(strtotime($suscripcion->ultimo_episodio_fecha)); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flavor-suscripcion-acciones">
                                <a href="<?php echo esc_url(add_query_arg('serie', $suscripcion->serie_id)); ?>"
                                   class="flavor-btn flavor-btn-sm flavor-btn-primary">
                                    <span class="dashicons dashicons-controls-play"></span>
                                    <?php _e('Escuchar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                                <button type="button"
                                        class="flavor-btn flavor-btn-sm flavor-btn-outline flavor-btn-cancelar-suscripcion"
                                        data-serie-id="<?php echo intval($suscripcion->serie_id); ?>">
                                    <span class="dashicons dashicons-no"></span>
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
     * Renderiza el tab de Historial
     *
     * @return string HTML del tab
     */
    public function render_tab_historial() {
        $this->cargar_assets();
        global $wpdb;

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return $this->render_mensaje_login();
        }

        // Obtener historial de reproducciones (agrupado por episodio, solo el más reciente)
        $historial = $wpdb->get_results($wpdb->prepare(
            "SELECT
                r.id AS reproduccion_id,
                r.episodio_id,
                r.posicion_actual,
                r.duracion_escuchada,
                r.porcentaje_completado,
                r.completado,
                r.fecha_inicio,
                r.fecha_ultima_actualizacion,
                e.titulo AS episodio_titulo,
                e.descripcion AS episodio_descripcion,
                e.archivo_url,
                e.duracion_segundos,
                e.imagen_url AS episodio_imagen,
                e.temporada,
                e.numero_episodio,
                s.id AS serie_id,
                s.titulo AS serie_titulo,
                s.imagen_url AS serie_imagen
            FROM {$this->tablas['reproducciones']} r
            JOIN {$this->tablas['episodios']} e ON r.episodio_id = e.id
            JOIN {$this->tablas['series']} s ON e.serie_id = s.id
            WHERE r.usuario_id = %d
            AND r.id IN (
                SELECT MAX(id) FROM {$this->tablas['reproducciones']}
                WHERE usuario_id = %d
                GROUP BY episodio_id
            )
            ORDER BY r.fecha_ultima_actualizacion DESC
            LIMIT 50",
            $usuario_id,
            $usuario_id
        ));

        ob_start();
        ?>
        <div class="flavor-podcast-dashboard-tab flavor-historial-tab">
            <div class="flavor-tab-header">
                <h2>
                    <span class="dashicons dashicons-backup"></span>
                    <?php _e('Historial de Reproducción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <?php if (!empty($historial)): ?>
                    <button type="button" class="flavor-btn flavor-btn-sm flavor-btn-outline flavor-btn-limpiar-historial">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Limpiar todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                <?php endif; ?>
            </div>

            <?php if (empty($historial)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-backup"></span>
                    <h3><?php _e('Sin historial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Los episodios que escuches aparecerán aquí.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-historial-lista">
                    <?php foreach ($historial as $item): ?>
                        <div class="flavor-historial-item" data-reproduccion-id="<?php echo intval($item->reproduccion_id); ?>">
                            <?php echo $this->render_mini_player($item); ?>

                            <div class="flavor-historial-progreso">
                                <div class="flavor-progreso-bar">
                                    <div class="flavor-progreso-completado"
                                         style="width: <?php echo floatval($item->porcentaje_completado); ?>%"></div>
                                </div>
                                <span class="flavor-progreso-texto">
                                    <?php if ($item->completado): ?>
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php _e('Completado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    <?php else: ?>
                                        <?php echo $this->formatear_duracion($item->posicion_actual); ?> /
                                        <?php echo $this->formatear_duracion($item->duracion_segundos); ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="flavor-historial-meta">
                                <span class="flavor-fecha">
                                    <?php echo human_time_diff(strtotime($item->fecha_ultima_actualizacion)); ?>
                                </span>
                            </div>

                            <div class="flavor-historial-acciones">
                                <button type="button"
                                        class="flavor-btn-icon flavor-btn-continuar"
                                        data-episodio-id="<?php echo intval($item->episodio_id); ?>"
                                        data-posicion="<?php echo intval($item->posicion_actual); ?>"
                                        title="<?php esc_attr_e('Continuar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-controls-play"></span>
                                </button>
                                <button type="button"
                                        class="flavor-btn-icon flavor-btn-eliminar-historial"
                                        data-reproduccion-id="<?php echo intval($item->reproduccion_id); ?>"
                                        title="<?php esc_attr_e('Eliminar del historial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-no-alt"></span>
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
     * Renderiza el tab de Favoritos
     *
     * @return string HTML del tab
     */
    public function render_tab_favoritos() {
        $this->cargar_assets();
        global $wpdb;

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return $this->render_mensaje_login();
        }

        // Obtener episodios favoritos
        $favoritos = $wpdb->get_results($wpdb->prepare(
            "SELECT
                f.id AS favorito_id,
                f.fecha_agregado,
                e.id AS episodio_id,
                e.titulo AS episodio_titulo,
                e.descripcion AS episodio_descripcion,
                e.archivo_url,
                e.duracion_segundos,
                e.imagen_url AS episodio_imagen,
                e.temporada,
                e.numero_episodio,
                e.reproducciones,
                e.me_gusta,
                e.fecha_publicacion,
                s.id AS serie_id,
                s.titulo AS serie_titulo,
                s.imagen_url AS serie_imagen,
                s.categoria AS serie_categoria
            FROM {$this->tablas['favoritos']} f
            JOIN {$this->tablas['episodios']} e ON f.episodio_id = e.id
            JOIN {$this->tablas['series']} s ON e.serie_id = s.id
            WHERE f.usuario_id = %d
            ORDER BY f.fecha_agregado DESC",
            $usuario_id
        ));

        ob_start();
        ?>
        <div class="flavor-podcast-dashboard-tab flavor-favoritos-tab">
            <div class="flavor-tab-header">
                <h2>
                    <span class="dashicons dashicons-heart"></span>
                    <?php _e('Mis Favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <span class="flavor-badge flavor-badge-count">
                    <?php echo count($favoritos); ?>
                </span>
            </div>

            <?php if (empty($favoritos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-heart"></span>
                    <h3><?php _e('Sin favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Marca episodios como favoritos para encontrarlos fácilmente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-favoritos-grid">
                    <?php foreach ($favoritos as $favorito): ?>
                        <div class="flavor-favorito-card" data-favorito-id="<?php echo intval($favorito->favorito_id); ?>">
                            <?php echo $this->render_mini_player($favorito, true); ?>

                            <div class="flavor-favorito-meta">
                                <span>
                                    <span class="dashicons dashicons-controls-play"></span>
                                    <?php echo number_format_i18n($favorito->reproducciones); ?>
                                </span>
                                <span>
                                    <span class="dashicons dashicons-heart"></span>
                                    <?php echo number_format_i18n($favorito->me_gusta); ?>
                                </span>
                                <span class="flavor-fecha">
                                    <?php _e('Guardado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>:
                                    <?php echo human_time_diff(strtotime($favorito->fecha_agregado)); ?>
                                </span>
                            </div>

                            <div class="flavor-favorito-acciones">
                                <button type="button"
                                        class="flavor-btn-icon flavor-btn-quitar-favorito"
                                        data-episodio-id="<?php echo intval($favorito->episodio_id); ?>"
                                        title="<?php esc_attr_e('Quitar de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-heart"></span>
                                </button>
                                <button type="button"
                                        class="flavor-btn-icon flavor-btn-descargar"
                                        data-episodio-id="<?php echo intval($favorito->episodio_id); ?>"
                                        data-url="<?php echo esc_url($favorito->archivo_url); ?>"
                                        title="<?php esc_attr_e('Descargar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-download"></span>
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
     * Renderiza el tab de Descargas
     *
     * @return string HTML del tab
     */
    public function render_tab_descargas() {
        $this->cargar_assets();
        global $wpdb;

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return $this->render_mensaje_login();
        }

        // Obtener episodios descargados
        $descargas = $wpdb->get_results($wpdb->prepare(
            "SELECT
                d.id AS descarga_id,
                d.fecha_descarga,
                d.dispositivo,
                d.estado AS descarga_estado,
                e.id AS episodio_id,
                e.titulo AS episodio_titulo,
                e.descripcion AS episodio_descripcion,
                e.archivo_url,
                e.duracion_segundos,
                e.tamano_bytes,
                e.imagen_url AS episodio_imagen,
                e.temporada,
                e.numero_episodio,
                s.id AS serie_id,
                s.titulo AS serie_titulo,
                s.imagen_url AS serie_imagen
            FROM {$this->tablas['descargas']} d
            JOIN {$this->tablas['episodios']} e ON d.episodio_id = e.id
            JOIN {$this->tablas['series']} s ON e.serie_id = s.id
            WHERE d.usuario_id = %d
            AND d.estado = 'descargado'
            ORDER BY d.fecha_descarga DESC",
            $usuario_id
        ));

        // Calcular espacio total
        $espacio_total = array_sum(array_column($descargas, 'tamano_bytes'));

        ob_start();
        ?>
        <div class="flavor-podcast-dashboard-tab flavor-descargas-tab">
            <div class="flavor-tab-header">
                <h2>
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Mis Descargas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <div class="flavor-header-info">
                    <span class="flavor-badge flavor-badge-count">
                        <?php echo count($descargas); ?>
                        <?php _e('episodios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                    <span class="flavor-espacio-total">
                        <?php echo $this->formatear_tamano($espacio_total); ?>
                    </span>
                </div>
            </div>

            <?php if (empty($descargas)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-download"></span>
                    <h3><?php _e('Sin descargas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p><?php _e('Descarga episodios para escucharlos sin conexión.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>
                <div class="flavor-descargas-lista">
                    <?php foreach ($descargas as $descarga): ?>
                        <div class="flavor-descarga-item" data-descarga-id="<?php echo intval($descarga->descarga_id); ?>">
                            <?php echo $this->render_mini_player($descarga); ?>

                            <div class="flavor-descarga-info">
                                <span class="flavor-tamano">
                                    <span class="dashicons dashicons-media-audio"></span>
                                    <?php echo $this->formatear_tamano($descarga->tamano_bytes); ?>
                                </span>
                                <?php if (!empty($descarga->dispositivo)): ?>
                                    <span class="flavor-dispositivo">
                                        <span class="dashicons dashicons-smartphone"></span>
                                        <?php echo esc_html($descarga->dispositivo); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="flavor-fecha">
                                    <?php echo human_time_diff(strtotime($descarga->fecha_descarga)); ?>
                                </span>
                            </div>

                            <div class="flavor-descarga-acciones">
                                <a href="<?php echo esc_url($descarga->archivo_url); ?>"
                                   class="flavor-btn-icon"
                                   download
                                   title="<?php esc_attr_e('Descargar de nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-download"></span>
                                </a>
                                <button type="button"
                                        class="flavor-btn-icon flavor-btn-eliminar-descarga"
                                        data-descarga-id="<?php echo intval($descarga->descarga_id); ?>"
                                        title="<?php esc_attr_e('Eliminar descarga', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-trash"></span>
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
     * Renderiza un mini player para un episodio
     *
     * @param object $episodio Datos del episodio
     * @param bool $mostrar_serie Mostrar información de la serie
     * @return string HTML del mini player
     */
    private function render_mini_player($episodio, $mostrar_serie = false) {
        $imagen = !empty($episodio->episodio_imagen) ? $episodio->episodio_imagen : $episodio->serie_imagen;
        $numero_formato = '';

        if (!empty($episodio->temporada) || !empty($episodio->numero_episodio)) {
            $numero_formato = sprintf('S%dE%d',
                intval($episodio->temporada),
                intval($episodio->numero_episodio)
            );
        }

        ob_start();
        ?>
        <div class="flavor-mini-player"
             data-episodio-id="<?php echo intval($episodio->episodio_id); ?>"
             data-audio-url="<?php echo esc_url($episodio->archivo_url); ?>">

            <div class="flavor-mini-player-imagen">
                <?php if (!empty($imagen)): ?>
                    <img src="<?php echo esc_url($imagen); ?>"
                         alt="<?php echo esc_attr($episodio->episodio_titulo); ?>">
                <?php else: ?>
                    <div class="flavor-imagen-placeholder">
                        <span class="dashicons dashicons-format-audio"></span>
                    </div>
                <?php endif; ?>
                <button type="button" class="flavor-mini-player-btn-play">
                    <span class="dashicons dashicons-controls-play flavor-icon-play"></span>
                    <span class="dashicons dashicons-controls-pause flavor-icon-pause" style="display:none;"></span>
                </button>
            </div>

            <div class="flavor-mini-player-info">
                <?php if ($mostrar_serie && !empty($episodio->serie_titulo)): ?>
                    <span class="flavor-mini-player-serie">
                        <?php echo esc_html($episodio->serie_titulo); ?>
                    </span>
                <?php endif; ?>

                <h4 class="flavor-mini-player-titulo">
                    <?php if (!empty($numero_formato)): ?>
                        <span class="flavor-episodio-numero"><?php echo esc_html($numero_formato); ?></span>
                    <?php endif; ?>
                    <?php echo esc_html($episodio->episodio_titulo); ?>
                </h4>

                <div class="flavor-mini-player-duracion">
                    <span class="dashicons dashicons-clock"></span>
                    <?php echo $this->formatear_duracion($episodio->duracion_segundos); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza mensaje de login requerido
     *
     * @return string HTML del mensaje
     */
    private function render_mensaje_login() {
        ob_start();
        ?>
        <div class="flavor-login-required">
            <span class="dashicons dashicons-lock"></span>
            <h3><?php _e('Inicia sesión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Necesitas iniciar sesión para ver esta sección.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="<?php echo wp_login_url(flavor_current_request_url()); ?>" class="flavor-btn flavor-btn-primary">
                <?php _e('Iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Formatea duración en segundos a formato legible
     *
     * @param int $segundos Segundos
     * @return string Duración formateada
     */
    private function formatear_duracion($segundos) {
        $segundos = intval($segundos);

        if ($segundos < 0) {
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
     * Formatea tamaño en bytes a formato legible
     *
     * @param int $bytes Bytes
     * @return string Tamaño formateado
     */
    private function formatear_tamano($bytes) {
        $bytes = intval($bytes);

        if ($bytes <= 0) {
            return '0 B';
        }

        $unidades = ['B', 'KB', 'MB', 'GB'];
        $indice = 0;

        while ($bytes >= 1024 && $indice < count($unidades) - 1) {
            $bytes /= 1024;
            $indice++;
        }

        return round($bytes, 2) . ' ' . $unidades[$indice];
    }

    // =========================================================================
    // AJAX Handlers
    // =========================================================================

    /**
     * AJAX: Toggle favorito
     */
    public function ajax_toggle_favorito() {
        check_ajax_referer('flavor_podcast_dashboard_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $episodio_id = intval($_POST['episodio_id'] ?? 0);
        if (!$episodio_id) {
            wp_send_json_error(['message' => __('Episodio no válido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        // Verificar si ya es favorito
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tablas['favoritos']}
             WHERE usuario_id = %d AND episodio_id = %d",
            $usuario_id,
            $episodio_id
        ));

        if ($existe) {
            // Eliminar de favoritos
            $wpdb->delete(
                $this->tablas['favoritos'],
                [
                    'usuario_id' => $usuario_id,
                    'episodio_id' => $episodio_id,
                ],
                ['%d', '%d']
            );

            // Decrementar contador en episodio
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tablas['episodios']} SET me_gusta = GREATEST(0, me_gusta - 1) WHERE id = %d",
                $episodio_id
            ));

            wp_send_json_success([
                'action' => 'removed',
                'message' => __('Eliminado de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        } else {
            // Agregar a favoritos
            $wpdb->insert(
                $this->tablas['favoritos'],
                [
                    'usuario_id' => $usuario_id,
                    'episodio_id' => $episodio_id,
                    'fecha_agregado' => current_time('mysql'),
                ],
                ['%d', '%d', '%s']
            );

            // Incrementar contador en episodio
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tablas['episodios']} SET me_gusta = me_gusta + 1 WHERE id = %d",
                $episodio_id
            ));

            wp_send_json_success([
                'action' => 'added',
                'message' => __('Agregado a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }
    }

    /**
     * AJAX: Registrar descarga
     */
    public function ajax_registrar_descarga() {
        check_ajax_referer('flavor_podcast_dashboard_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $episodio_id = intval($_POST['episodio_id'] ?? 0);
        $dispositivo = sanitize_text_field($_POST['dispositivo'] ?? '');

        if (!$episodio_id) {
            wp_send_json_error(['message' => __('Episodio no válido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        // Insertar o actualizar registro de descarga
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tablas['descargas']}
             WHERE usuario_id = %d AND episodio_id = %d",
            $usuario_id,
            $episodio_id
        ));

        if ($existe) {
            $wpdb->update(
                $this->tablas['descargas'],
                [
                    'fecha_descarga' => current_time('mysql'),
                    'dispositivo' => $dispositivo,
                    'estado' => 'descargado',
                ],
                [
                    'usuario_id' => $usuario_id,
                    'episodio_id' => $episodio_id,
                ],
                ['%s', '%s', '%s'],
                ['%d', '%d']
            );
        } else {
            $wpdb->insert(
                $this->tablas['descargas'],
                [
                    'usuario_id' => $usuario_id,
                    'episodio_id' => $episodio_id,
                    'dispositivo' => $dispositivo,
                    'fecha_descarga' => current_time('mysql'),
                    'estado' => 'descargado',
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );

            // Incrementar contador de descargas en episodio
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tablas['episodios']} SET descargas = descargas + 1 WHERE id = %d",
                $episodio_id
            ));
        }

        wp_send_json_success([
            'message' => __('Descarga registrada', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * AJAX: Eliminar del historial
     */
    public function ajax_eliminar_historial() {
        check_ajax_referer('flavor_podcast_dashboard_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $reproduccion_id = intval($_POST['reproduccion_id'] ?? 0);
        $limpiar_todo = isset($_POST['limpiar_todo']) && $_POST['limpiar_todo'] === 'true';

        global $wpdb;

        if ($limpiar_todo) {
            // Eliminar todo el historial del usuario
            $eliminados = $wpdb->delete(
                $this->tablas['reproducciones'],
                ['usuario_id' => $usuario_id],
                ['%d']
            );

            wp_send_json_success([
                'message' => sprintf(__('%d elementos eliminados', FLAVOR_PLATFORM_TEXT_DOMAIN), $eliminados),
                'cleared_all' => true,
            ]);
        } elseif ($reproduccion_id) {
            // Verificar que pertenece al usuario
            $pertenece = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->tablas['reproducciones']} WHERE id = %d AND usuario_id = %d",
                $reproduccion_id,
                $usuario_id
            ));

            if (!$pertenece) {
                wp_send_json_error(['message' => __('No autorizado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }

            $wpdb->delete(
                $this->tablas['reproducciones'],
                ['id' => $reproduccion_id],
                ['%d']
            );

            wp_send_json_success([
                'message' => __('Eliminado del historial', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        } else {
            wp_send_json_error(['message' => __('Parámetros inválidos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Cancelar suscripción
     */
    public function ajax_cancelar_suscripcion() {
        check_ajax_referer('flavor_podcast_dashboard_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $serie_id = intval($_POST['serie_id'] ?? 0);
        if (!$serie_id) {
            wp_send_json_error(['message' => __('Serie no válida', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        // Eliminar suscripción
        $eliminado = $wpdb->delete(
            $this->tablas['suscripciones'],
            [
                'usuario_id' => $usuario_id,
                'serie_id' => $serie_id,
            ],
            ['%d', '%d']
        );

        if ($eliminado) {
            // Decrementar contador de suscriptores
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->tablas['series']} SET suscriptores = GREATEST(0, suscriptores - 1) WHERE id = %d",
                $serie_id
            ));

            wp_send_json_success([
                'message' => __('Suscripción cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        } else {
            wp_send_json_error(['message' => __('No se pudo cancelar la suscripción', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Eliminar descarga
     */
    public function ajax_eliminar_descarga() {
        check_ajax_referer('flavor_podcast_dashboard_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $descarga_id = intval($_POST['descarga_id'] ?? 0);
        if (!$descarga_id) {
            wp_send_json_error(['message' => __('Descarga no válida', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;

        // Verificar que pertenece al usuario
        $pertenece = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tablas['descargas']} WHERE id = %d AND usuario_id = %d",
            $descarga_id,
            $usuario_id
        ));

        if (!$pertenece) {
            wp_send_json_error(['message' => __('No autorizado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Marcar como eliminado (soft delete)
        $actualizado = $wpdb->update(
            $this->tablas['descargas'],
            ['estado' => 'eliminado'],
            ['id' => $descarga_id],
            ['%s'],
            ['%d']
        );

        if ($actualizado !== false) {
            wp_send_json_success([
                'message' => __('Descarga eliminada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        } else {
            wp_send_json_error(['message' => __('No se pudo eliminar la descarga', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }
}
